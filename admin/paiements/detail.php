<?php
declare(strict_types=1);

require_once __DIR__ . '/../../admin/_init.php';
require_once __DIR__ . '/../../core/geniuspay.php';
require_once __DIR__ . '/../../core/payment_plan.php';

$activeMenu = 'paiements';
$pdo   = Database::connect();
$planId = (int)($_GET['id'] ?? 0);

payment_plan_ensure_tables($pdo);

$stmt = $pdo->prepare("
    SELECT p.*, f.titre AS formation_titre, f.slug AS formation_slug
    FROM paiement_plans p
    LEFT JOIN formations f ON f.id = p.formation_id
    WHERE p.id = ?
    LIMIT 1
");
$stmt->execute([$planId]);
$plan = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$plan) {
    include __DIR__ . '/../partials/header.php';
    echo "<div style='max-width:640px;margin:80px auto;text-align:center'><h2>Plan introuvable</h2></div>";
    include __DIR__ . '/../partials/footer.php';
    exit;
}

/* Lignes de paiements */
$lignes = $pdo->prepare("SELECT * FROM paiements_inscription WHERE plan_id=? ORDER BY id ASC");
$lignes->execute([$planId]);
$lignes = $lignes->fetchAll(PDO::FETCH_ASSOC);

$montantTotal = (int)$plan['montant_total'];
$montantPaye  = payment_plan_paid_amount($pdo, $planId);
$solde        = max(0, $montantTotal - $montantPaye);
$pct          = $montantTotal > 0 ? min(100, round($montantPaye / $montantTotal * 100)) : 0;

$pageTitle = "Détail plan paiement #$planId — Admin";
include __DIR__ . '/../partials/header.php';
?>
<style>
.det-wrap{max-width:860px;margin:30px auto;padding:0 16px}
.det-card{background:#0b1220;border:1px solid rgba(255,255,255,.09);border-radius:16px;padding:24px;margin-bottom:20px}
.det-card h3{margin:0 0 16px;font-size:1rem;color:#f5a623}
.info-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px}
.info-row{display:flex;flex-direction:column;gap:3px}
.info-row label{font-size:11px;color:#64748b;text-transform:uppercase;letter-spacing:.05em}
.info-row span{font-size:14px;color:#e5e7eb;font-weight:600}
.badge{display:inline-block;padding:2px 8px;border-radius:20px;font-size:11px;font-weight:700}
.badge-green{background:rgba(34,197,94,.2);color:#86efac}
.badge-orange{background:rgba(245,166,35,.2);color:#fcd34d}
.badge-red{background:rgba(239,68,68,.2);color:#fca5a5}
.badge-blue{background:rgba(59,130,246,.2);color:#93c5fd}
.pay-line{display:flex;align-items:center;gap:12px;padding:12px;border-bottom:1px solid rgba(255,255,255,.05)}
.pay-line:last-child{border-bottom:none}
</style>

<div class="det-wrap">
  <div style="margin-bottom:16px">
    <a href="index.php" style="color:#64748b;text-decoration:none;font-size:13px">← Retour aux paiements</a>
  </div>

  <h1 style="margin:0 0 20px;color:#f5a623;font-size:1.3rem">💳 Plan de paiement #<?= $planId; ?></h1>

  <!-- Infos participant -->
  <div class="det-card">
    <h3>👤 Participant</h3>
    <div class="info-grid">
      <div class="info-row"><label>Nom</label><span><?= e($plan['etudiant_nom']); ?></span></div>
      <div class="info-row"><label>Email</label><span><?= e($plan['etudiant_email']); ?></span></div>
      <div class="info-row"><label>Téléphone</label><span><?= e($plan['etudiant_phone']); ?></span></div>
      <div class="info-row"><label>Formation</label><span><?= e($plan['formation_titre'] ?? '—'); ?></span></div>
      <div class="info-row"><label>Plan</label><span><?= e(payment_plan_label($plan['plan_type'])); ?></span></div>
      <div class="info-row"><label>Date inscription</label><span><?= date('d/m/Y H:i', strtotime($plan['created_at'])); ?></span></div>
      <div class="info-row">
        <label>Statut plan</label>
        <span>
          <?php $sc = match($plan['statut']) { 'complete' => 'badge-green', 'abandonne' => 'badge-red', default => 'badge-orange' }; ?>
          <span class="badge <?= $sc; ?>"><?= ucfirst($plan['statut']); ?></span>
        </span>
      </div>
    </div>
  </div>

  <!-- Progression financière -->
  <div class="det-card">
    <h3>📊 Progression financière</h3>
    <div class="info-grid" style="margin-bottom:16px">
      <div class="info-row"><label>Montant total</label><span><?= number_format($montantTotal, 0, ',', ' '); ?> FCFA</span></div>
      <div class="info-row"><label>Montant payé</label><span style="color:#22c55e"><?= number_format($montantPaye, 0, ',', ' '); ?> FCFA</span></div>
      <div class="info-row"><label>Solde restant</label><span style="color:<?= $solde > 0 ? '#f5a623' : '#22c55e'; ?>"><?= $solde > 0 ? number_format($solde, 0, ',', ' ') . ' FCFA' : '✓ Soldé'; ?></span></div>
    </div>
    <div style="background:rgba(255,255,255,.1);border-radius:6px;height:10px;overflow:hidden">
      <div style="width:<?= $pct; ?>%;height:100%;background:<?= $pct >= 100 ? '#22c55e' : '#f5a623' ?>;border-radius:6px;transition:width .5s"></div>
    </div>
    <div style="text-align:center;font-size:13px;color:#94a3b8;margin-top:8px"><?= $pct; ?> % réglé</div>
  </div>

  <!-- Lignes de paiement -->
  <div class="det-card">
    <h3>📋 Transactions</h3>
    <?php if (empty($lignes)): ?>
      <p style="color:#64748b">Aucune transaction enregistrée.</p>
    <?php else: ?>
    <?php foreach ($lignes as $l):
        $bc = match((string)$l['statut']) { 'paye' => 'badge-green', 'echoue' => 'badge-red', default => 'badge-orange' };
        $bl = match((string)$l['statut']) { 'paye' => 'Payé', 'echoue' => 'Échoué', default => 'En attente' };
    ?>
      <div class="pay-line">
        <div style="flex:1">
          <b style="font-size:13px"><?= e($l['echeance_label'] ?: ('Tranche ' . $l['echeance_num'])); ?></b><br>
          <span style="font-size:11px;color:#64748b">Réf. <?= e($l['reference']); ?></span>
          <?php if (!empty($l['paid_at'])): ?>
            <span style="font-size:11px;color:#64748b"> · Payé le <?= date('d/m/Y', strtotime($l['paid_at'])); ?></span>
          <?php endif; ?>
        </div>
        <div style="text-align:right">
          <b style="color:<?= $l['statut'] === 'paye' ? '#22c55e' : '#94a3b8'; ?>"><?= number_format((int)($l['montant_net'] ?? $l['montant']), 0, ',', ' '); ?> FCFA</b><br>
          <small style="color:#64748b">Facturé : <?= number_format((int)$l['montant'], 0, ',', ' '); ?> FCFA</small><br>
          <span class="badge <?= $bc; ?>"><?= $bl; ?></span>
        </div>
      </div>
    <?php endforeach; ?>
    <?php endif; ?>

    <?php if ($solde > 0): ?>
    <div style="margin-top:16px;text-align:center">
      <a href="/paiement-inscription.php?formation=<?= (int)$plan['formation_id']; ?>"
         style="padding:10px 18px;background:#16a34a;color:#fff;border-radius:10px;text-decoration:none;font-weight:700;font-size:13px">
        💳 Payer la prochaine tranche (<?= number_format($solde, 0, ',', ' '); ?> FCFA restants)
      </a>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php include __DIR__ . '/../partials/footer.php'; ?>
