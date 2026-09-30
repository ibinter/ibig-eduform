<?php
declare(strict_types=1);

/* =====================================================
   RETOUR DE PAIEMENT MONEROO
   Le client revient ici après le paiement. On vérifie
   le statut réel via l'API et on met à jour la base.
===================================================== */

require_once __DIR__ . '/core/bootstrap.php';

$pdo = Database::connect();
$ref = trim((string)($_GET['ref'] ?? ''));

/* Moneroo ajoute l'ID du paiement dans l'URL de retour */
$paymentId = trim((string)($_GET['paymentId'] ?? $_GET['payment_id'] ?? $_GET['id'] ?? ''));

$pageTitle = "Confirmation de paiement – IBIG EDUFORM";
include __DIR__ . '/partials/header.php';

$etat = 'inconnu';
$row  = null;

/* Ligne en base (si elle existe) */
if ($ref !== '') {
    $stmt = $pdo->prepare("SELECT * FROM paiements_inscription WHERE reference = ? LIMIT 1");
    $stmt->execute([$ref]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

/* ID à vérifier : celui de l'URL en priorité, sinon celui stocké */
$verifyId = $paymentId !== '' ? $paymentId : (string)($row['provider_payment_id'] ?? '');

/* Déjà marqué payé en base ? */
if ($row && (string)($row['statut'] ?? '') === 'paye') {
    $etat = 'success';
} elseif ($verifyId !== '') {
    /* Source de vérité = Moneroo */
    $status = moneroo_verify($verifyId);   // success / pending / failed / cancelled
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

/* Envoi du reçu par email — une seule fois (idempotent via email_sent) */
if ($etat === 'success' && $ref !== '') {
    try {
        $fresh = $pdo->prepare("SELECT * FROM paiements_inscription WHERE reference = ? LIMIT 1");
        $fresh->execute([$ref]);
        $row = $fresh->fetch(PDO::FETCH_ASSOC) ?: $row;
    } catch (Throwable $e) { /* ignore */ }
    if ($row && empty($row['email_sent'])) {
        require_once __DIR__ . '/core/inscription_email.php';
        if (inscription_send_receipt($pdo, $row)) {
            try { $pdo->prepare("UPDATE paiements_inscription SET email_sent=1 WHERE reference=?")->execute([$ref]); } catch (Throwable $e) {}
        }
    }

    /* Notification affilié IBIG PARTNERS — une seule fois */
    try {
        require_once __DIR__ . '/includes/ibig-affiliate.php';
        $formationSlug = (string)($row['formation_slug'] ?? $row['slug'] ?? 'formation');
        $montant       = (int)($row['montant'] ?? $row['amount'] ?? 0);
        $nomClient     = trim(($row['prenom'] ?? '') . ' ' . ($row['nom'] ?? '')) ?: ($row['nom_complet'] ?? '');
        $emailClient   = (string)($row['email'] ?? '');
        ibig_affiliate_report_sale($formationSlug, $ref, $montant, $nomClient, $emailClient);
    } catch (Throwable $e) {
        error_log('[IBIG_AFFILIATE] paiement-retour exception: ' . $e->getMessage());
    }
}
?>
<div style="max-width:560px;margin:80px auto 100px;padding:0 18px">
  <div style="background:#0b1220;color:#e5e7eb;border-radius:18px;padding:32px;border:1px solid rgba(255,255,255,.08);text-align:center">
    <?php if ($etat === 'success'): ?>
      <h1 style="color:#22c55e;margin-top:0">✅ Paiement confirmé</h1>
      <p>Merci ! Votre paiement des frais d'inscription a bien été reçu. Un reçu vous a été envoyé par email.</p>
      <?php if ($ref): ?><p>Référence : <strong><?= e($ref); ?></strong></p>
        <a href="/recu-inscription.php?ref=<?= urlencode($ref); ?>" target="_blank" rel="noopener" style="display:inline-block;margin-top:12px;padding:12px 18px;background:#16a34a;color:#fff;border-radius:10px;text-decoration:none;font-weight:800">📄 Télécharger mon reçu</a>
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

    <a href="/formations.php" style="display:inline-block;margin-top:18px;padding:12px 18px;background:#1e40af;color:#fff;border-radius:10px;text-decoration:none;font-weight:800">← Retour aux formations</a>
  </div>
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>
