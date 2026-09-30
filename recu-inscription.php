<?php
declare(strict_types=1);
/* =========================================================
   IBIG EDUFORM — Reçu d'inscription (imprimable / PDF)
   Affiché uniquement pour un paiement confirmé (statut payé).
========================================================= */
require_once __DIR__ . '/core/bootstrap.php';
$pdo = Database::connect();

$ref = trim((string)($_GET['ref'] ?? ''));
$row = null; $formation = null;
if ($ref !== '') {
    $st = $pdo->prepare("SELECT * FROM paiements_inscription WHERE reference = ? LIMIT 1");
    $st->execute([$ref]);
    $row = $st->fetch(PDO::FETCH_ASSOC) ?: null;
    if ($row && !empty($row['formation_id'])) {
        $sf = $pdo->prepare("SELECT titre, type_certificat FROM formations WHERE id = ? LIMIT 1");
        $sf->execute([(int)$row['formation_id']]);
        $formation = $sf->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}
$paye = $row && (string)($row['statut'] ?? '') === 'paye';
function hh($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
$app = defined('APP_URL') ? rtrim(APP_URL, '/') : '';
?>
<!doctype html>
<html lang="fr"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Reçu d'inscription — IBIG EDUFORM</title>
<link rel="icon" href="/favicon.ico">
<style>
  body{font-family:Inter,Arial,Helvetica,sans-serif;color:#0f172a;background:#eef2fb;margin:0;padding:24px 14px}
  .sheet{max-width:720px;margin:auto;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 24px 60px rgba(15,23,42,.15)}
  .head{background:linear-gradient(135deg,#0F2742,#0B4AA6);color:#fff;padding:28px 32px;display:flex;align-items:center;gap:16px;border-bottom:4px solid #f5a623}
  .head img{height:50px} .head .ph{width:50px;height:50px;border-radius:12px;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-weight:800}
  .head h1{margin:0;font-size:20px;font-weight:800} .head p{margin:3px 0 0;font-size:12.5px;opacity:.9}
  .body{padding:30px 32px}
  .tag{display:inline-block;background:#dcfce7;color:#166534;padding:6px 14px;border-radius:999px;font-weight:800;font-size:13px}
  table{width:100%;border-collapse:collapse;margin:18px 0}
  td{padding:12px 4px;border-bottom:1px solid #eef1f8;font-size:15px}
  td.k{color:#64748b} td.v{text-align:right;font-weight:700}
  .tot{font-size:1.5rem;font-weight:900;color:#16a34a}
  .foot{padding:18px 32px;background:#f6f8fe;color:#64748b;font-size:12px;text-align:center;line-height:1.7}
  .actions{max-width:720px;margin:18px auto;text-align:center}
  .btn{background:linear-gradient(135deg,#0B4AA6,#0F2742);color:#fff;border:0;padding:13px 24px;border-radius:10px;font-weight:800;cursor:pointer;font-size:15px;text-decoration:none}
  .warn{max-width:720px;margin:60px auto;background:#fff;border-radius:16px;padding:40px;text-align:center;color:#475569}
  @media print{.actions{display:none}body{background:#fff;padding:0}.sheet{box-shadow:none;border-radius:0}.head{-webkit-print-color-adjust:exact;print-color-adjust:exact}}
</style></head><body>
<?php if (!$paye): ?>
  <div class="warn">
    <h2>Reçu indisponible</h2>
    <p>Aucun paiement confirmé pour cette référence. Si vous venez de payer, patientez quelques instants puis rechargez.</p>
    <p><a class="btn" href="/formations.php">← Retour aux formations</a></p>
  </div>
<?php else: ?>
  <div class="sheet">
    <div class="head">
      <img src="/assets/images/logo.png" alt="IBIG EDUFORM" onerror="this.outerHTML='<div class=&quot;ph&quot;>IB</div>'">
      <div><h1>Reçu d'inscription</h1><p>IBIG EDUFORM — Institut de formation professionnelle</p></div>
    </div>
    <div class="body">
      <span class="tag">✅ Paiement confirmé</span>
      <table>
        <tr><td class="k">Référence</td><td class="v"><?= hh($row['reference']); ?></td></tr>
        <tr><td class="k">Participant</td><td class="v"><?= hh($row['customer_name']); ?></td></tr>
        <?php if (!empty($row['customer_email'])): ?><tr><td class="k">Email</td><td class="v"><?= hh($row['customer_email']); ?></td></tr><?php endif; ?>
        <?php if (!empty($row['customer_phone'])): ?><tr><td class="k">Téléphone</td><td class="v"><?= hh($row['customer_phone']); ?></td></tr><?php endif; ?>
        <?php if ($formation): ?><tr><td class="k">Formation</td><td class="v"><?= hh($formation['titre']); ?></td></tr><?php endif; ?>
        <tr><td class="k">Date</td><td class="v"><?= hh(!empty($row['paid_at']) ? date('d/m/Y à H:i', strtotime((string)$row['paid_at'])) : date('d/m/Y')); ?></td></tr>
        <tr><td class="k">Montant payé</td><td class="v tot"><?= number_format((int)$row['montant'], 0, ',', ' '); ?> FCFA</td></tr>
      </table>
      <p style="color:#64748b;font-size:13px">Ce document atteste du paiement reçu par IBIG EDUFORM. Conservez-le comme preuve d'inscription.</p>
    </div>
    <div class="foot">
      📞 +225 07 78 88 25 92 · ✉️ formation@intermark-business.com · 🌐 ibig-eduform.com<br>
      © <?= hh(date('Y')); ?> IBIG EDUFORM — Tous droits réservés.
    </div>
  </div>
  <div class="actions">
    <a href="#" class="btn" onclick="window.print();return false;">📄 Télécharger / Imprimer le reçu (PDF)</a>
  </div>
<?php endif; ?>
</body></html>
