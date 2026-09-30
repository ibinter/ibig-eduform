<?php
require_once __DIR__ . '/../../core/database.php';

session_start();
if (empty($_SESSION['admin_id'])) exit;

$pdo = Database::connect();

$rows = $pdo->query("
  SELECT
    COALESCE(o.type_contrat,'Non defini') AS type_contrat,
    COUNT(i.id) AS total
  FROM insertions i
  LEFT JOIN offres_emploi o ON o.id = i.offre_id
  GROUP BY type_contrat
  ORDER BY total DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Rapport Impact – IBIG EDUFORM</title>
<style>
body{font-family:Arial,Helvetica,sans-serif;font-size:14px}
h1{margin-bottom:10px}
table{border-collapse:collapse;width:100%}
th,td{border:1px solid #333;padding:6px;text-align:left}
th{background:#f2f2f2}
</style>
</head>
<body onload="window.print()">

<h1>Rapport Impact – IBIG EDUFORM</h1>

<table>
  <tr>
    <th>Type de contrat</th>
    <th>Insertions</th>
  </tr>

  <?php foreach($rows as $r): ?>
  <tr>
    <td><?= htmlspecialchars($r['type_contrat'], ENT_QUOTES, 'UTF-8'); ?></td>
    <td><?= (int)$r['total']; ?></td>
  </tr>
  <?php endforeach; ?>

  <?php if (empty($rows)): ?>
  <tr>
    <td colspan="2">Aucune donn&eacute;e disponible.</td>
  </tr>
  <?php endif; ?>
</table>

<p style="margin-top:15px;font-size:12px;color:#555">
  Rapport g&eacute;n&eacute;r&eacute; automatiquement &mdash; IBIG EDUFORM
</p>

</body>
</html>
