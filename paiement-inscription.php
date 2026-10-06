<?php
declare(strict_types=1);

require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/core/promo.php';
require_once __DIR__ . '/core/referral.php';
require_once __DIR__ . '/core/geniuspay.php';
require_once __DIR__ . '/core/payment_plan.php';
require_once __DIR__ . '/includes/ibig-affiliate.php';
ibig_affiliate_tracker_init();

$pdo = Database::connect();

/* =====================================================
   1) FORMATION
===================================================== */
$formationId = isset($_GET['formation']) ? (int)$_GET['formation'] : (int)($_GET['formation_id'] ?? 0);

$stmt = $pdo->prepare("
  SELECT id, titre, slug, frais_inscription, tarif_presentiel, tarif_en_ligne, is_samedi_pro, date_debut, paiement_lien
  FROM formations
  WHERE id = ? AND statut = 'active'
  LIMIT 1
");
$stmt->execute([$formationId]);
$f = $stmt->fetch(PDO::FETCH_ASSOC);

$pageTitle = "Paiement des frais d'inscription – IBIG EDUFORM";

if (!$f) {
    include __DIR__ . '/partials/header.php';
    echo "<div style='max-width:640px;margin:120px auto;text-align:center'><h2>Formation introuvable</h2></div>";
    include __DIR__ . '/partials/footer.php';
    exit;
}

/* =====================================================
   2) MONTANT DE BASE DE LA FORMATION
   - Samedi Pro  → tarif total (présentiel ou en ligne)
   - Certification → frais d'inscription (acompte 50 000 FCFA)
===================================================== */
$isSamediPro = !empty($f['is_samedi_pro']);

if ($isSamediPro) {
    $montantBase = (int)($f['tarif_presentiel'] ?? 0);
    if ($montantBase <= 0) { $montantBase = (int)($f['tarif_en_ligne'] ?? 0); }
} else {
    $montantBase = (int)$f['frais_inscription'];
    if ($montantBase <= 0) { $montantBase = 50000; }
}

/* Parrainage : -10% filleul */
$refCode    = referral_active_code();
$refParrain = $refCode !== '' ? referral_lookup($pdo, $refCode) : null;
$refRemise  = 0;
if ($refParrain) {
    $refRemise = referral_remise($montantBase);
    if ($refRemise > 0) { $montantBase -= $refRemise; }
}

if ($montantBase <= 0) {
    include __DIR__ . '/partials/header.php';
    echo "<div style='max-width:640px;margin:120px auto;text-align:center'><h2>Aucun montant à régler pour cette formation.</h2></div>";
    include __DIR__ . '/partials/footer.php';
    exit;
}

/* =====================================================
   3) PLAN D'ÉCHÉANCES
===================================================== */
$plan = payment_plan($montantBase);   // type, acompte, tranches

/* Mode de paiement choisi (GET ou POST : 'plan'|'total'|'libre') */
$modeChoisi = in_array($_REQUEST['mode'] ?? '', ['plan', 'total', 'libre'], true)
    ? $_REQUEST['mode']
    : 'plan';

/* Montant NET de la première transaction */
if ($modeChoisi === 'total') {
    $montantNet = $montantBase;
} elseif ($modeChoisi === 'libre') {
    $montantNet = max($plan['acompte'], (int)($_REQUEST['montant_libre'] ?? 0));
} else {
    $montantNet = $plan['acompte'];
}

$montantNet = max(1, $montantNet);

/* Frais GeniusPay + montant facturé au client */
$fee   = geniuspay_fee($montantNet);
$total = geniuspay_gross($montantNet);   // ce que le client paie réellement

$error = '';

/* =====================================================
   4) SOUMISSION → CRÉATION PAIEMENT GENIUSPAY
===================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $prenom        = trim((string)($_POST['prenom']       ?? ''));
    $nom           = trim((string)($_POST['nom']          ?? ''));
    $email         = trim((string)($_POST['email']        ?? ''));
    $tel           = trim((string)($_POST['telephone']    ?? ''));
    $modePost      = in_array($_POST['mode'] ?? '', ['plan', 'total', 'libre'], true)
                     ? $_POST['mode'] : 'plan';
    $montantLibre  = (int)($_POST['montant_libre'] ?? 0);

    /* Recalcul avec valeurs POST */
    if ($modePost === 'total') {
        $montantNet = $montantBase;
    } elseif ($modePost === 'libre') {
        $montantNet = max($plan['acompte'], $montantLibre);
    } else {
        $montantNet = $plan['acompte'];
    }
    $montantNet = max(1, $montantNet);
    $fee   = geniuspay_fee($montantNet);
    $total = geniuspay_gross($montantNet);

    if ($prenom === '' || $nom === '' || $email === '' || $tel === '') {
        $error = "Merci de remplir tous les champs.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Adresse email invalide.";
    } elseif ($montantNet < $plan['acompte']) {
        $error = "Le montant choisi est inférieur au minimum requis (" . number_format($plan['acompte'], 0, ',', ' ') . " FCFA).";
    } else {
        payment_plan_ensure_tables($pdo);

        /* Référence unique */
        $reference = strtoupper('INS-' . $formationId . '-' . bin2hex(random_bytes(4)));

        /* Créer ou retrouver le plan de paiement */
        $planId = payment_plan_create($pdo, [
            'formation_id'    => $formationId,
            'etudiant_nom'    => trim($prenom . ' ' . $nom),
            'etudiant_email'  => $email,
            'etudiant_phone'  => $tel,
            'montant_total'   => $montantBase,
            'plan_type'       => $modePost === 'total' ? 'unique' : $plan['type'],
            'acompte_minimum' => $plan['acompte'],
        ]);

        /* Libellé de l'échéance */
        if ($modePost === 'total') {
            $echeanceLabel = 'Paiement intégral';
        } elseif ($modePost === 'libre') {
            $echeanceLabel = 'Acompte libre';
        } else {
            $echeanceLabel = $plan['tranches'][0]['label'] ?? 'Acompte 1';
        }

        /* Enregistrement en base (en_attente) */
        $pdo->prepare("
            INSERT INTO paiements_inscription
              (formation_id, montant, reference, statut, provider,
               customer_name, customer_email, customer_phone,
               plan_id, echeance_num, echeance_label, montant_net)
            VALUES (?, ?, ?, 'en_attente', 'geniuspay', ?, ?, ?, ?, 1, ?, ?)
        ")->execute([
            $formationId, $total, $reference,
            trim($prenom . ' ' . $nom), $email, $tel,
            $planId, $echeanceLabel, $montantNet,
        ]);

        /* Parrainage */
        if ($refParrain) {
            try {
                $pdo->prepare("INSERT INTO parrainage_usages
                    (code, filleul_nom, filleul_contact, formation_id, formation_titre, montant_base, remise, montant_net, reference, statut, created_at)
                    VALUES (?,?,?,?,?,?,?,?,?, 'en_attente', NOW())")
                  ->execute([
                      $refCode, trim($prenom . ' ' . $nom), $email, $formationId, (string)$f['titre'],
                      $montantBase + $refRemise, $refRemise, $montantNet, $reference,
                  ]);
            } catch (Throwable $e) { error_log('[PARRAINAGE] ' . $e->getMessage()); }
        }

        /* Affilié IBIG PARTNERS */
        $ibigAffiliateRef = ibig_affiliate_get_ref();
        if ($ibigAffiliateRef) {
            try {
                $pdo->prepare("UPDATE paiements_inscription SET ibig_ref=? WHERE reference=?")
                    ->execute([$ibigAffiliateRef, $reference]);
            } catch (Throwable $e) {}
        }

        /* Initialisation GeniusPay */
        $returnUrl    = rtrim(APP_URL, '/') . '/paiement-retour.php?ref=' . urlencode($reference);
        $gpMeta = ['reference' => $reference, 'formation_id' => (string)$formationId, 'plan_id' => (string)$planId, 'echeance' => '1'];
        if ($ibigAffiliateRef) { $gpMeta['ibig_ref'] = $ibigAffiliateRef; }

        $pay = geniuspay_initialize(
            $total,
            "Frais d'inscription – " . (string)$f['titre'],
            ['email' => $email, 'first_name' => $prenom, 'last_name' => $nom, 'phone' => $tel],
            $returnUrl,
            $gpMeta
        );

        if ($pay && !empty($pay['checkout_url'])) {
            if (!empty($pay['id'])) {
                try {
                    $pdo->prepare("UPDATE paiements_inscription SET provider_payment_id=? WHERE reference=?")
                        ->execute([$pay['id'], $reference]);
                } catch (Throwable $e) {}
            }
            header('Location: ' . $pay['checkout_url']);
            exit;
        }

        $error = "Le paiement en ligne est momentanément indisponible. Merci de réessayer dans un instant.";
    }
}

include __DIR__ . '/partials/header.php';
?>
<style>
:root{--pay-bg:#0b1220;--pay-border:rgba(255,255,255,.09);--pay-accent:#22c55e;--pay-warn:#f5a623;--pay-text:#e5e7eb;--pay-muted:#94a3b8}
.pay-wrap{max-width:600px;margin:60px auto 100px;padding:0 18px}
.pay-card{background:var(--pay-bg);color:var(--pay-text);border-radius:18px;padding:30px;border:1px solid var(--pay-border)}
.pay-card h1{margin:0 0 6px;color:var(--pay-warn);font-size:1.45rem}
.pay-section{margin-top:24px;padding-top:20px;border-top:1px solid var(--pay-border)}
.pay-section h3{margin:0 0 14px;font-size:1rem;color:var(--pay-warn)}
.plan-opts{display:flex;flex-direction:column;gap:10px}
.plan-opt{display:flex;align-items:flex-start;gap:12px;padding:14px;border-radius:12px;border:2px solid var(--pay-border);cursor:pointer;transition:border-color .2s}
.plan-opt:has(input:checked){border-color:var(--pay-accent);background:rgba(34,197,94,.06)}
.plan-opt input[type=radio]{margin-top:3px;accent-color:var(--pay-accent);flex-shrink:0}
.plan-opt-body{flex:1}
.plan-opt-title{font-weight:700;font-size:.95rem}
.plan-opt-desc{font-size:13px;color:var(--pay-muted);margin-top:3px}
.plan-tranches{margin-top:8px;display:flex;flex-direction:column;gap:4px}
.plan-tranche{font-size:12px;padding:4px 10px;border-radius:6px;background:rgba(255,255,255,.06);display:flex;justify-content:space-between}
.plan-tranche.tranche-now{background:rgba(34,197,94,.12);color:#bbf7d0}
.libre-input{margin-top:10px;display:none}
.libre-input input{width:100%;padding:10px 14px;border-radius:10px;border:1px solid var(--pay-border);background:#020617;color:var(--pay-text);font-size:15px}
.summary-row{display:flex;justify-content:space-between;align-items:baseline;margin:6px 0;font-size:14px;color:var(--pay-muted)}
.summary-row b{color:var(--pay-text)}
.summary-total{font-size:1.6rem;font-weight:900;color:var(--pay-accent)}
.pay-card label{display:block;margin-top:14px;font-weight:600;font-size:14px}
.pay-card input[type=text],.pay-card input[type=email],.pay-card input[type=tel]{width:100%;margin-top:6px;padding:12px 14px;border-radius:10px;border:1px solid var(--pay-border);background:#020617;color:var(--pay-text)}
.pay-btn{width:100%;margin-top:22px;padding:15px;border:0;border-radius:12px;background:var(--pay-accent);color:#04210f;font-weight:900;font-size:15px;cursor:pointer}
.pay-btn:hover{filter:brightness(1.08)}
.pay-err{margin-top:14px;padding:12px;border-radius:10px;background:rgba(239,68,68,.14);border:1px solid #ef4444;color:#fecaca;font-size:14px}
.pay-sec{margin-top:14px;font-size:12px;color:var(--pay-muted);text-align:center}
.fee-note{font-size:12px;color:var(--pay-muted);margin-top:4px}
</style>

<div class="pay-wrap">
  <div class="pay-card">
    <h1>💳 Paiement des frais d'inscription</h1>
    <p style="opacity:.85;margin:0"><?= e($f['titre']); ?></p>

    <?php if (!empty($f['slug'])): ?>
    <a href="/tdr-local-pdf.php?slug=<?= urlencode($f['slug']); ?>" target="_blank" rel="noopener"
       style="display:inline-flex;align-items:center;gap:8px;margin:10px 0 0;padding:10px 16px;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.25);color:#fff;border-radius:10px;text-decoration:none;font-weight:700;font-size:13px">
      📄 Consulter le TDR de la formation
    </a>
    <?php endif; ?>

    <!-- CHOIX DU MODE DE PAIEMENT -->
    <div class="pay-section">
      <h3>⚙️ Choisissez votre mode de paiement</h3>

      <?php
      $planType = $plan['type'];
      $tranches = $plan['tranches'];
      ?>

      <form id="payForm" method="post">
        <?= csrf_field(); ?>
        <input type="hidden" name="mode" id="hdMode" value="plan">
        <input type="hidden" name="montant_libre" id="hdLibre" value="0">

        <div class="plan-opts">

          <!-- Option 1 : Plan suggéré (minimum) -->
          <label class="plan-opt">
            <input type="radio" name="_mode_ui" value="plan" checked onchange="setMode('plan')">
            <div class="plan-opt-body">
              <div class="plan-opt-title">
                <?php if ($planType === 'unique'): ?>
                  ✅ Paiement unique — <?= number_format($plan['acompte'], 0, ',', ' '); ?> FCFA
                <?php elseif ($planType === '2tranches'): ?>
                  📅 Plan 2 tranches — Acompte <?= number_format($plan['acompte'], 0, ',', ' '); ?> FCFA
                <?php else: ?>
                  📅 Plan 3 tranches — Acompte <?= number_format($plan['acompte'], 0, ',', ' '); ?> FCFA
                <?php endif; ?>
              </div>
              <div class="plan-opt-desc">
                <?php if ($planType === 'unique'): ?>
                  Montant total de la formation, payé en une fois.
                <?php elseif ($planType === '2tranches'): ?>
                  50 % aujourd'hui, 50 % avant le début de la formation.
                <?php else: ?>
                  40 % aujourd'hui · 30 % au début · 30 % avant la fin.
                <?php endif; ?>
              </div>
              <?php if (count($tranches) > 1): ?>
              <div class="plan-tranches">
                <?php foreach ($tranches as $i => $tr): ?>
                <div class="plan-tranche <?= $i === 0 ? 'tranche-now' : '' ?>">
                  <span><?= $i === 0 ? '✦ ' : '' ?><?= e($tr['label']); ?></span>
                  <b><?= number_format($tr['montant'], 0, ',', ' '); ?> FCFA</b>
                </div>
                <?php endforeach; ?>
              </div>
              <?php endif; ?>
            </div>
          </label>

          <!-- Option 2 : Paiement total immédiat -->
          <?php if ($planType !== 'unique'): ?>
          <label class="plan-opt">
            <input type="radio" name="_mode_ui" value="total" onchange="setMode('total')">
            <div class="plan-opt-body">
              <div class="plan-opt-title">💰 Payer la totalité maintenant — <?= number_format($montantBase, 0, ',', ' '); ?> FCFA</div>
              <div class="plan-opt-desc">Réglez l'intégralité des frais en une seule transaction.</div>
            </div>
          </label>
          <?php endif; ?>

          <!-- Option 3 : Montant libre (≥ minimum) -->
          <label class="plan-opt">
            <input type="radio" name="_mode_ui" value="libre" onchange="setMode('libre')">
            <div class="plan-opt-body">
              <div class="plan-opt-title">✏️ Montant personnalisé (≥ <?= number_format($plan['acompte'], 0, ',', ' '); ?> FCFA)</div>
              <div class="plan-opt-desc">Saisissez un montant supérieur ou égal au minimum requis.</div>
              <div class="libre-input" id="libreBox">
                <input type="number" id="libreAmt" min="<?= $plan['acompte']; ?>" step="500"
                       placeholder="<?= $plan['acompte']; ?>"
                       oninput="updateLibre(this.value)">
              </div>
            </div>
          </label>

        </div><!-- /plan-opts -->

        <!-- RÉCAPITULATIF -->
        <div class="pay-section" id="summaryBox">
          <h3>💼 Récapitulatif</h3>
          <div class="summary-row"><span>Montant NET</span><b id="sumNet"><?= number_format($plan['acompte'], 0, ',', ' '); ?> FCFA</b></div>
          <div class="summary-row"><span>Frais GeniusPay (1 % + 100 FCFA)</span><b id="sumFee"><?= number_format(geniuspay_fee($plan['acompte']), 0, ',', ' '); ?> FCFA</b></div>
          <hr style="border:none;border-top:1px solid var(--pay-border);margin:10px 0">
          <div style="display:flex;justify-content:space-between;align-items:baseline">
            <span style="color:var(--pay-muted)">Total à payer</span>
            <span class="summary-total" id="sumTotal"><?= number_format(geniuspay_gross($plan['acompte']), 0, ',', ' '); ?> FCFA</span>
          </div>
          <div class="fee-note">Les frais de transaction sont à la charge du client.</div>
        </div>

        <?php if ($error): ?><div class="pay-err"><?= e($error); ?></div><?php endif; ?>

        <!-- INFORMATIONS CLIENT -->
        <div class="pay-section">
          <h3>👤 Vos informations</h3>
          <label>Prénom *</label>
          <input type="text" name="prenom" required value="<?= e($_POST['prenom'] ?? ''); ?>">
          <label>Nom *</label>
          <input type="text" name="nom" required value="<?= e($_POST['nom'] ?? ''); ?>">
          <label>Email *</label>
          <input type="email" name="email" required value="<?= e($_POST['email'] ?? ''); ?>">
          <label>Téléphone *</label>
          <input type="tel" name="telephone" required value="<?= e($_POST['telephone'] ?? ''); ?>">
        </div>

        <button class="pay-btn" type="submit">Payer maintenant →</button>
      </form>

      <div class="pay-sec">🔒 Paiement sécurisé via GeniusPay — Mobile Money (Orange, MTN, Moov, Wave) & cartes bancaires.</div>
    </div>
  </div>
</div>

<script>
(function(){
  var base   = <?= $montantBase; ?>;
  var minAmt = <?= $plan['acompte']; ?>;

  function fmt(n){ return n.toLocaleString('fr-FR') + ' FCFA'; }
  function fee(n){ return Math.ceil(n * 0.01) + 100; }
  function gross(n){ return n + fee(n); }
  function round5(n){ return Math.ceil(n / 5) * 5; }

  function updateSummary(net){
    net = Math.max(1, net);
    var f = fee(net);
    var g = round5(gross(net));
    document.getElementById('sumNet').textContent   = fmt(net);
    document.getElementById('sumFee').textContent   = fmt(f);
    document.getElementById('sumTotal').textContent = fmt(g);
    document.getElementById('hdLibre').value = net;
  }

  window.setMode = function(mode){
    document.getElementById('hdMode').value = mode;
    document.getElementById('libreBox').style.display = (mode === 'libre') ? 'block' : 'none';
    if (mode === 'plan')  { updateSummary(minAmt); }
    if (mode === 'total') { updateSummary(base); }
    if (mode === 'libre') {
      var v = parseInt(document.getElementById('libreAmt').value) || minAmt;
      updateSummary(Math.max(minAmt, v));
    }
  };

  window.updateLibre = function(val){
    var n = parseInt(val) || 0;
    updateSummary(Math.max(minAmt, n));
  };

  /* Init */
  updateSummary(minAmt);
})();
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>
