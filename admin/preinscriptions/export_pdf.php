<?php
declare(strict_types=1);
ob_start();

require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/auth.php';

/* =========================
   SÉCURITÉ
========================= */
if (!auth_check()) {
  http_response_code(403);
  exit('Accès interdit');
}

$pdo = Database::connect();

/* =========================
   DONNÉES (AJUSTABLE)
========================= */
$limit = 200; // ajuste si besoin
$sql = "
  SELECT
    p.created_at,
    p.nom,
    p.prenoms,
    p.telephone,
    p.email,
    f.titre AS formation,
    p.source,
    p.statut
  FROM preinscriptions p
  LEFT JOIN formations f ON f.id = p.formation_id
  ORDER BY p.created_at DESC
  LIMIT $limit
";
$rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

function h(string $v): string {
  return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Préinscriptions — IBIG EDUFORM</title>

<style>
/* =========================
   BASE
========================= */
@page { size: A4; margin: 12mm; }
body{
  font-family: Arial, Helvetica, sans-serif;
  font-size: 11px;
  color:#111;
}
h1{font-size:16px;margin:0 0 6px}
.meta{font-size:10px;color:#555;margin-bottom:10px}

/* =========================
   TABLE
========================= */
table{
  width:100%;
  border-collapse:collapse;
}
th,td{
  border:1px solid #333;
  padding:5px 6px;
  vertical-align:top;
}
th{
  background:#f0f0f0;
  font-size:11px;
}
td{font-size:10.5px}

/* =========================
   STATUTS
========================= */
.statut{
  padding:2px 6px;
  border-radius:10px;
  font-size:10px;
  font-weight:700;
  display:inline-block;
}
.s-nouvelle{background:#e0f2fe}
.s-traitee{background:#dcfce7}
.s-rejete{background:#fee2e2}

/* =========================
   PRINT
========================= */
@media print{
  thead{display:table-header-group;}
  tfoot{display:table-footer-group;}
  body{margin:0}
  .no-print{display:none}
}
</style>
</head>

<body>

<h1>Préinscriptions — IBIG EDUFORM</h1>
<div class="meta">
  Export imprimable · <?= count($rows); ?> enregistrements · Généré le <?= date('d/m/Y H:i'); ?>
</div>

<table>
<thead>
<tr>
  <th style="width:80px">Date</th>
  <th>Nom &amp; Prénoms</th>
  <th style="width:130px">Contact</th>
  <th>Formation</th>
  <th style="width:80px">Source</th>
  <th style="width:70px">Statut</th>
</tr>
</thead>
<tbody>

<?php foreach ($rows as $r): ?>
<tr>
  <td><?= date('d/m/Y H:i', strtotime($r['created_at'])); ?></td>

  <td><?= h(trim(($r['prenoms'] ?? '').' '.($r['nom'] ?? ''))); ?></td>

  <td>
    <?= h($r['telephone'] ?? '—'); ?>
    <?= !empty($r['email']) ? '<br>'.h($r['email']) : ''; ?>
  </td>

  <td><?= h($r['formation'] ?? '—'); ?></td>

  <td><?= h($r['source'] ?? '—'); ?></td>

  <td>
    <span class="statut s-<?= h($r['statut']); ?>">
      <?= ucfirst(h($r['statut'])); ?>
    </span>
  </td>
</tr>
<?php endforeach; ?>

</tbody>
</table>

<script>
  window.print();
</script>

</body>
</html>
<?php ob_end_flush(); ?>