<?php
declare(strict_types=1);

/* =====================================================
   RETOUR DE PAIEMENT GENIUSPAY
   Le client revient ici après le paiement.
   On vérifie le statut réel via l'API et met à jour la base.
===================================================== */

require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/core/geniuspay.php';
require_once __DIR__ . '/core/payment_plan.php';

$pdo = Database::connect();
$ref = trim((string)($_GET['ref'] ?? ''));

/* GeniusPay ajoute l'ID de transaction dans l'URL de retour */
$paymentId = trim((string)($_GET['transactionId'] ?? $_GET['paymentId'] ?? $_GET['transaction_id'] ?? $_GET['id'] ?? ''));

$pageTitle = "Confirmation de paiement – IBIG EDUFORM";
include __DIR__ . '/partials/header.php';

$etat = 'inconnu';
$row  = null;

/* Ligne en base */
if ($ref !== '') {
    try {
        payment_plan_ensure_tables($pdo);
        $stmt = $pdo->prepare("SELECT * FROM paiements_inscription WHERE reference = ? LIMIT 1");
        $stmt->execute([$ref]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (Throwable $e) { error_log('[RETOUR GP] ' . $e->getMessage()); }
}

/* ID à vérifier : URL en priorité, sinon stocké */
$verifyId = $paymentId !== '' ? $paymentId : (string)($row['provider_payment_id'] ?? '');

/* Déjà marqué payé ? */
if ($row && (string)($row['statut'] ?? '') === 'paye') {
    $etat = 'success';
} elseif ($verifyId !== '') {
    $status = geniuspay_verify($verifyId);   // success | pending | failed | cancelled | null
    if ($status === 'success') {
        $etat = 'success';
        if ($ref !== '') {
            try {
                $pdo->prepare("UPDATE paiements_inscription SET statut='paye', paid_at=NOW() WHERE reference=?")
                    ->execute([$ref]);
            } catch (Throwable $e) {
                try { $pdo->prepare("UPDATE paiements_inscription SET statut='paye' WHERE reference=?")->execute([$ref]); } catch (Throwable $e2) {}
            }
        }
    } elseif (in_array($status, ['failed', 'cancelled'], true)) {
        $etat = 'failed';
        if ($ref !== '') {
            try { $pdo->prepare("UPDATE paiements_inscription SET statut='echoue' WHERE reference=?")->execute([$ref]); } catch (Throwable $e) {}
        }
    } else {
        $etat = 'pending';
    }
} elseif ($row) {
    $etat = 'pending';
}

/* Plan info */
$planInfo   = null;
$planPaye   = 0;
$planTotal  = 0;
if ($etat === 'success' && $ref !== '') {
    /* Re-lire la ligne fraîche */
    try {
        $fresh = $pdo->prepare("SELECT * FROM paiements_inscription WHERE reference = ? LIMIT 1");
        $fresh->execute([$ref]);
        $row = $fresh->fetch(PDO::FETCH_ASSOC) ?: $row;
    } catch (Throwable $e) {}

    /* Plan d'ensemble */
    $planId = (int)($row['plan_id'] ?? 0);
    if ($planId > 0) {
        try {
            $ps = $pdo->prepare("SELECT * FROM paiement_plans WHERE id=? LIMIT 1");
            $ps->execute([$planId]);
            $planInfo  = $ps->fetch(PDO::FETCH_ASSOC) ?: null;
            $planPaye  = payment_plan_paid_amount($pdo, $planId);
            $planTotal = (int)($planInfo['montant_total'] ?? 0);
        } catch (Throwable $e) {}
    }

    /* Envoi du reçu — une seule fois */
    if ($row && empty($row['email_sent'])) {
        require_once __DIR__ . '/core/inscription_email.php';
        if (inscription_send_receipt($pdo, $row)) {
            try { $pdo->prepare("UPDATE paiements_inscription SET email_sent=1 WHERE reference=?")->execute([$ref]); } catch (Throwable $e) {}
        }
    }

    /* Notification affilié IBIG PARTNERS */
    try {
        require_once __DIR__ . '/includes/ibig-affiliate.php';
        $formationSlug = (string)($row['formation_slug'] ?? $row['slug'] ?? 'formation');
        $montant       = (int)($row['montant'] ?? 0);
        $nomClient     = (string)($row['customer_name'] ?? '');
        $emailClient   = (string)($row['customer_email'] ?? '');
        ibig_affiliate_report_sale($formationSlug, $ref, $montant, $nomClient, $emailClient);
    } catch (Throwable $e) { error_log('[IBIG_AFFILIATE] ' . $e->getMessage()); }
}
?>
<div style="max-width:600px;margin:80px auto 100px;padding:0 18px">
  <div style="background:#0b1220;color:#e5e7eb;border-radius:18px;padding:32px;border:1px solid rgba(255,255,255,.08);text-align:center">
    <?php if ($etat === 'success'): ?>
      <h1 style="color:#22c55e;margin-top:0">✅ Paiement confirmé</h1>
      <p>Merci ! Votre paiement a bien été reçu. Un reçu vous a été envoyé par email.</p>
      <?php if ($ref): ?>
        <p>Référence : <strong><?= e($ref); ?></strong></p>
        <a href="/recu-inscription.php?ref=<?= urlencode($ref); ?>" target="_blank" rel="noopener"
           style="display:inline-block;margin-top:12px;padding:12px 18px;background:#16a34a;color:#fff;border-radius:10px;text-decoration:none;font-weight:800">
          📄 Télécharger mon reçu
        </a>
      <?php endif; ?>

      <?php if ($planInfo && $planTotal > 0): ?>
        <?php
          $planPctPaye = $planTotal > 0 ? min(100, round($planPaye / $planTotal * 100)) : 0;
          $planRestant = max(0, $planTotal - $planPaye);
        ?>
        <div style="margin-top:24px;padding:16px;background:rgba(255,255,255,.06);border-radius:12px;text-align:left">
          <h3 style="margin:0 0 12px;font-size:.95rem;color:#f5a623">📊 Suivi de votre plan de paiement</h3>
          <div style="display:flex;justify-content:space-between;font-size:13px;color:#94a3b8">
            <span>Total formation</span><b style="color:#e5e7eb"><?= number_format($planTotal, 0, ',', ' '); ?> FCFA</b>
          </div>
          <div style="display:flex;justify-content:space-between;font-size:13px;color:#94a3b8;margin-top:4px">
            <span>Déjà réglé</span><b style="color:#22c55e"><?= number_format($planPaye, 0, ',', ' '); ?> FCFA</b>
          </div>
          <?php if ($planRestant > 0): ?>
          <div style="display:flex;justify-content:space-between;font-size:13px;color:#94a3b8;margin-top:4px">
            <span>Solde restant</span><b style="color:#f5a623"><?= number_format($planRestant, 0, ',', ' '); ?> FCFA</b>
          </div>
          <?php endif; ?>
          <div style="background:rgba(255,255,255,.12);border-radius:6px;height:8px;margin-top:10px;overflow:hidden">
            <div style="width:<?= $planPctPaye ?>%;height:100%;background:<?= $planPctPaye >= 100 ? '#22c55e' : '#f5a623' ?>;border-radius:6px;transition:width .5s"></div>
          </div>
          <div style="text-align:center;font-size:12px;color:#64748b;margin-top:6px"><?= $planPctPaye; ?> % réglé</div>
          <?php if ($planRestant > 0): ?>
          <div style="margin-top:12px;text-align:center">
            <a href="/paiement-inscription.php?formation=<?= (int)($row['formation_id'] ?? 0); ?>"
               style="display:inline-block;padding:10px 16px;background:#1e40af;color:#fff;border-radius:10px;text-decoration:none;font-weight:700;font-size:13px">
              💳 Régler la prochaine tranche
            </a>
          </div>
          <?php endif; ?>
        </div>
      <?php endif; ?>

    <?php elseif ($etat === 'failed'): ?>
      <h1 style="color:#ef4444;margin-top:0">❌ Paiement non abouti</h1>
      <p>Le paiement a échoué ou a été annulé. Vous pouvez réessayer.</p>
    <?php elseif ($etat === 'pending'): ?>
      <h1 style="color:#f5a623;margin-top:0">⏳ Paiement en cours de validation</h1>
      <p>Nous confirmons votre paiement dès réception. Conservez votre référence<?= $ref ? ' (<strong>'.e($ref).'</strong>)' : ''; ?>.</p>
    <?php else: ?>
      <h1 style="color:#f5a623;margin-top:0">Référence introuvable</h1>
      <p>Impossible de retrouver ce paiement. Contactez-nous si besoin.</p>
    <?php endif; ?>

    <a href="/formations.php" style="display:inline-block;margin-top:18px;padding:12px 18px;background:#1e40af;color:#fff;border-radius:10px;text-decoration:none;font-weight:800">
      ← Retour aux formations
    </a>
  </div>
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>
