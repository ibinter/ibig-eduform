<?php
declare(strict_types=1);

require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/core/promo.php';
require_once __DIR__ . '/core/referral.php';
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
   MONTANT NET facturé :
   - SAMEDI PRO        -> le MONTANT TOTAL de la formation
   - LONGUE FORMATION  -> les FRAIS D'INSCRIPTION (50 000 FCFA)
===================================================== */
$isSamediPro = !empty($f['is_samedi_pro']);
$promo       = promo_earlybird($f);
if ($isSamediPro) {
  /* Samedi Pro : on encaisse le montant total avec réduction si éligible */
  $frais = (int)($f['tarif_presentiel'] ?? 0);
  if ($frais <= 0) { $frais = (int)($f['tarif_en_ligne'] ?? 0); }
  /* Promo early-bird : stratégie d'affichage uniquement — montant encaissé inchangé */
} else {
  /* Certification : frais d'inscription (acompte) inchangés.
     La réduction (-20 000 en ligne / -25 000 présentiel) s'applique sur le solde. */
  $frais = (int)$f['frais_inscription'];
  if ($frais <= 0) { $frais = 50000; }
}

/* Parrainage : -10% pour le filleul si un code de parrainage est actif */
$refCode    = referral_active_code();
$refParrain = $refCode !== '' ? referral_lookup($pdo, $refCode) : null;
$refRemise  = 0;
if ($refParrain) {
  $refRemise = referral_remise($frais);
  if ($refRemise > 0) { $frais -= $refRemise; }
}

if ($frais <= 0) {
  include __DIR__ . '/partials/header.php';
  echo "<div style='max-width:640px;margin:120px auto;text-align:center'><h2>Aucun montant à régler pour cette formation.</h2></div>";
  include __DIR__ . '/partials/footer.php';
  exit;
}

/* Gross-up : le client paie la commission, l'institut reçoit le NET */
$total      = payment_gross_amount($frais);   // montant réellement facturé au client
$serviceFee = max(0, $total - $frais);        // frais de service répercutés

$error = '';

/* =====================================================
   2) SOUMISSION -> CRÉATION PAIEMENT MONEROO
===================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();

  $prenom = trim((string)($_POST['prenom'] ?? ''));
  $nom    = trim((string)($_POST['nom'] ?? ''));
  $email  = trim((string)($_POST['email'] ?? ''));
  $tel    = trim((string)($_POST['telephone'] ?? ''));

  if ($prenom === '' || $nom === '' || $email === '' || $tel === '') {
    $error = "Merci de remplir tous les champs.";
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = "Adresse email invalide.";
  } else {
    /* Référence unique + enregistrement (en_attente) */
    $reference = strtoupper('INS-' . $formationId . '-' . bin2hex(random_bytes(4)));
    $pdo->prepare("
      INSERT INTO paiements_inscription (formation_id, montant, reference, statut)
      VALUES (?, ?, ?, 'en_attente')
    ")->execute([$formationId, $total, $reference]);

    /* Infos client (tolérant si colonnes pas encore ajoutées) */
    try {
      $pdo->prepare("
        UPDATE paiements_inscription
        SET provider='moneroo', customer_name=?, customer_email=?, customer_phone=?
        WHERE reference=?
      ")->execute([trim($prenom . ' ' . $nom), $email, $tel, $reference]);
    } catch (Throwable $e) { /* migration non exécutée : on continue */ }

    /* Parrainage : enregistre l'utilisation (récompense du parrain à honorer) */
    if ($refParrain) {
      try {
        $pdo->prepare("INSERT INTO parrainage_usages
            (code, filleul_nom, filleul_contact, formation_id, formation_titre, montant_base, remise, montant_net, reference, statut, created_at)
            VALUES (?,?,?,?,?,?,?,?,?, 'en_attente', NOW())")
          ->execute([
            $refCode, trim($prenom . ' ' . $nom), $email, $formationId, (string)$f['titre'],
            $frais + $refRemise, $refRemise, $frais, $reference,
          ]);
      } catch (Throwable $e) { error_log('[PARRAINAGE] ' . $e->getMessage()); }
    }

    /* Sauvegarder le ref affilié IBIG PARTNERS dans la ligne */
    $ibigAffiliateRef = ibig_affiliate_get_ref();
    if ($ibigAffiliateRef) {
      try {
        $pdo->prepare("UPDATE paiements_inscription SET ibig_ref=? WHERE reference=?")
            ->execute([$ibigAffiliateRef, $reference]);
      } catch (Throwable $e) { /* colonne pas encore ajoutée : ignoré */ }
    }

    /* Création de la transaction Moneroo */
    $returnUrl = rtrim(APP_URL, '/') . '/paiement-retour.php?ref=' . urlencode($reference);
    $monerooMeta = ['reference' => $reference, 'formation_id' => (string)$formationId];
    if ($ibigAffiliateRef) {
      $monerooMeta['ibig_ref'] = $ibigAffiliateRef; // transmis au webhook
    }
    $pay = moneroo_initialize(
      $total,
      "Frais d'inscription – " . (string)$f['titre'],
      ['email' => $email, 'first_name' => $prenom, 'last_name' => $nom, 'phone' => $tel],
      $returnUrl,
      $monerooMeta
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

    /* Aucun repli vers l'ancien opérateur : on affiche une erreur. */
    $error = "Le paiement en ligne est momentanément indisponible. Merci de réessayer dans un instant.";
  }
}

include __DIR__ . '/partials/header.php';
?>
<style>
.pay-wrap{max-width:560px;margin:70px auto 90px;padding:0 18px}
.pay-card{background:#0b1220;color:#e5e7eb;border-radius:18px;padding:30px;border:1px solid rgba(255,255,255,.08)}
.pay-card h1{margin:0 0 6px;color:#f5a623;font-size:1.5rem}
.pay-amount{font-size:1.6rem;font-weight:900;color:#22c55e;margin:10px 0 4px}
.pay-card label{display:block;margin-top:14px;font-weight:600;font-size:14px}
.pay-card input{width:100%;margin-top:6px;padding:12px 14px;border-radius:10px;border:1px solid rgba(255,255,255,.18);background:#020617;color:#e5e7eb}
.pay-btn{width:100%;margin-top:22px;padding:15px;border:0;border-radius:12px;background:#22c55e;color:#04210f;font-weight:900;font-size:15px;cursor:pointer}
.pay-err{margin-top:14px;padding:12px;border-radius:10px;background:rgba(239,68,68,.14);border:1px solid #ef4444;color:#fecaca;font-size:14px}
.pay-sec{margin-top:14px;font-size:12px;color:#94a3b8;text-align:center}
</style>

<div class="pay-wrap">
  <div class="pay-card">
    <h1>💳 Paiement des frais d'inscription</h1>
    <p style="opacity:.85"><?= e($f['titre']); ?></p>

    <?php if (!empty($f['slug'])): ?>
    <a href="/tdr-local-pdf.php?slug=<?= urlencode($f['slug']); ?>" target="_blank" rel="noopener"
       style="display:inline-flex;align-items:center;gap:8px;margin:8px 0 6px;padding:10px 16px;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.28);color:#fff;border-radius:10px;text-decoration:none;font-weight:700;font-size:13.5px">
      📄 Consulter le TDR de la formation
    </a>
    <?php endif; ?>

    <?php if ($serviceFee > 0): ?>
      <div style="margin:12px 0;font-size:14px;color:#cbd5e1">
        <div style="display:flex;justify-content:space-between"><span>Frais d'inscription</span><b><?= number_format($frais, 0, ',', ' '); ?> FCFA</b></div>
        <div style="display:flex;justify-content:space-between"><span>Frais de service</span><b><?= number_format($serviceFee, 0, ',', ' '); ?> FCFA</b></div>
        <hr style="opacity:.2;margin:8px 0">
      </div>
    <?php endif; ?>

    <div style="display:flex;justify-content:space-between;align-items:baseline">
      <span style="color:#94a3b8">Total à payer</span>
      <span class="pay-amount"><?= number_format($total, 0, ',', ' '); ?> FCFA</span>
    </div>

    <?php if ($error): ?><div class="pay-err"><?= e($error); ?></div><?php endif; ?>

    <form method="post">
      <?= csrf_field(); ?>
      <label>Prénom *</label>
      <input name="prenom" required value="<?= e($_POST['prenom'] ?? ''); ?>">
      <label>Nom *</label>
      <input name="nom" required value="<?= e($_POST['nom'] ?? ''); ?>">
      <label>Email *</label>
      <input type="email" name="email" required value="<?= e($_POST['email'] ?? ''); ?>">
      <label>Téléphone *</label>
      <input name="telephone" required value="<?= e($_POST['telephone'] ?? ''); ?>">

      <button class="pay-btn" type="submit">Payer maintenant →</button>
    </form>

    <div class="pay-sec">🔒 Paiement sécurisé — Mobile Money (Orange, MTN, Moov), Wave & cartes via Moneroo.</div>
  </div>
</div>

<?php include __DIR__ . '/partials/footer.php'; ?>
