<?php
declare(strict_types=1);
require_once __DIR__ . '/_auth.php';

$rows = $pdo->prepare("
  SELECT statut, ip, user_agent, created_at
  FROM candidat_login_logs
  WHERE candidat_id = ?
  ORDER BY created_at DESC
  LIMIT 100
");
$rows->execute([$candidatId]);
$logs = $rows->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Historique – IBIG EDUFORM</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
body{margin:0;font-family:Inter,Arial;background:#f1f5f9}
.header{background:linear-gradient(90deg,#0b5ed7,#1d4ed8);color:#fff;padding:18px 26px;display:flex;justify-content:space-between;align-items:center}
.header a{color:#fff;text-decoration:none;font-weight:800}
.container{max-width:1100px;margin:26px auto;padding:0 20px}
.card{background:#fff;border-radius:16px;padding:18px;box-shadow:0 10px 30px rgba(0,0,0,.08)}
table{width:100%;border-collapse:collapse}
th,td{padding:12px;border-bottom:1px solid #e5e7eb;font-size:14px}
th{background:#f8fafc;text-align:left}
.pill{padding:4px 10px;border-radius:999px;font-size:11px;font-weight:900;text-transform:uppercase}
.ok{background:#dcfce7;color:#166534}
.fail{background:#fee2e2;color:#991b1b}
.small{color:#64748b;font-size:13px}
</style>
</head>
<body>
<div class="header">
  <div>IBIG EDUFORM — Historique connexions</div>
  <div><a href="dashboard.php">Dashboard</a> | <a href="logout.php">Déconnexion</a></div>
</div>

<div class="container">
  <div class="card">
    <div class="small">Dernières 100 connexions.</div>
    <table style="margin-top:12px">
      <thead><tr><th>Statut</th><th>IP</th><th>Agent</th><th>Date</th></tr></thead>
      <tbody>
      <?php if (!$logs): ?>
        <tr><td colspan="4">Aucune donnée.</td></tr>
      <?php endif; ?>
      <?php foreach ($logs as $l): ?>
        <tr>
          <td>
            <span class="pill <?= $l['statut']==='success'?'ok':'fail'; ?>">
              <?= strtoupper(e($l['statut'])); ?>
            </span>
          </td>
          <td><?= e($l['ip'] ?? ''); ?></td>
          <td><?= e($l['user_agent'] ?? ''); ?></td>
          <td><?= e(date('d/m/Y H:i', strtotime($l['created_at']))); ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
</body>
</html>
