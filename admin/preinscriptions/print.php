<?php
declare(strict_types=1);

/**
 * PRINT — Préinscriptions (impression navigateur)
 * - Sans PDF
 * - Sans Excel
 * - Compatible PHP 8+
 */

require_once __DIR__ . '/../_init.php';

$pageTitle  = "Préinscriptions — Impression";
$activeMenu = "preinscriptions";

$pdo = Database::connect();

/* Colonnes profil disponibles (tolérant) */
$PROFIL_COLS = [];
try {
  $cols = $pdo->query("SHOW COLUMNS FROM preinscriptions")->fetchAll(PDO::FETCH_COLUMN);
  foreach (['domaine_activite','niveau_etude','fonction','annees_experience'] as $c) {
    if (in_array($c, $cols, true)) { $PROFIL_COLS[] = $c; }
  }
} catch (Throwable $e) {}
$extraSel = '';
foreach ($PROFIL_COLS as $c) { $extraSel .= ", p.$c"; }

/* =========================
   DONNÉES
========================= */
$sql = "
  SELECT
    p.created_at,
    p.nom,
    p.prenoms,
    p.telephone,
    p.email,
    p.ville,
    f.titre AS formation,
    p.source,
    p.statut $extraSel
  FROM preinscriptions p
  LEFT JOIN formations f ON f.id = p.formation_id
  ORDER BY p.created_at DESC
";
$rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   AIDE NULL-SAFE
========================= */
function s($v): string {
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}

?><!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Préinscriptions — Impression</title>

<style>
  body{
    font-family: Arial, Helvetica, sans-serif;
    font-size: 11px;
    color: #000;
  }
  h1{
    font-size: 16px;
    margin: 0 0 6px;
  }
  .meta{
    font-size: 10px;
    margin-bottom: 12px;
  }
  table{
    width: 100%;
    border-collapse: collapse;
  }
  th, td{
    border: 1px solid #000;
    padding: 6px;
    vertical-align: top;
  }
  th{
    background: #eee;
    text-align: left;
  }
  .statut-CONFIRME{ background:#dcfce7; }
  .statut-ANNULE{ background:#fee2e2; }
  .statut-NOUVEAU{ background:#fff7ed; }

  @media print {
    .no-print{ display:none; }
    body{ margin:0; }
  }
</style>
</head>

<body onload="window.print()">

<div class="no-print" style="margin-bottom:10px">
  <button onclick="window.print()">🖨️ Imprimer</button>
  <a href="index.php" style="margin-left:10px">⬅ Retour</a>
</div>

<h1>Préinscriptions — IBIG EDUFORM</h1>
<div class="meta">
  Généré le <?= date('d/m/Y H:i'); ?> |
  Total : <?= count($rows); ?>
</div>

<table>
<thead>
<tr>
  <th>Date</th>
  <th>Nom & Prénoms</th>
  <th>Contact</th>
  <th>Formation</th>
  <th>Profil</th>
  <th>Statut</th>
</tr>
</thead>
<tbody>

<?php if (!$rows): ?>
<tr>
  <td colspan="6">Aucune préinscription enregistrée.</td>
</tr>
<?php endif; ?>

<?php foreach ($rows as $r):
  $bits = [];
  if (!empty($r['domaine_activite']))  { $bits[] = 'Dom. : '  . $r['domaine_activite']; }
  if (!empty($r['niveau_etude']))      { $bits[] = 'Niv. : '  . $r['niveau_etude']; }
  if (!empty($r['fonction']))          { $bits[] = 'Fonc. : ' . $r['fonction']; }
  if (!empty($r['annees_experience'])) { $bits[] = 'Exp. : '  . $r['annees_experience']; }
?>
<tr class="statut-<?= s($r['statut']); ?>">
  <td><?= date('d/m/Y H:i', strtotime($r['created_at'])); ?></td>
  <td><?= s(trim(($r['prenoms'] ?? '').' '.($r['nom'] ?? ''))); ?></td>
  <td>
    <?= s($r['telephone']); ?><br>
    <?= s($r['email']); ?>
  </td>
  <td><?= s($r['formation'] ?? '—'); ?></td>
  <td><?php if ($bits): foreach ($bits as $i=>$b) { echo ($i?'<br>':'') . s($b); } else: ?>—<?php endif; ?></td>
  <td><?= s($r['statut']); ?></td>
</tr>
<?php endforeach; ?>

</tbody>
</table>

</body>
</html>