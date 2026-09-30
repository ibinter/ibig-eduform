<?php
declare(strict_types=1);

require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../auth/middleware.php';

$autoload = realpath(__DIR__ . '/../../vendor/autoload.php');
if (!$autoload) {
    die('autoload.php introuvable (vendor manquant ou mauvais chemin)');
}
require_once $autoload;

use Mpdf\Mpdf;

Middleware::requireAuth();

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
   PDF
========================= */
$mpdf = new Mpdf([
  'format' => 'A4',
  'margin_top' => 12,
  'margin_bottom' => 12,
  'margin_left' => 10,
  'margin_right' => 10,
]);

$html = '
<style>
body{font-family:Arial;font-size:10px}
h1{font-size:14px;margin-bottom:6px}
table{width:100%;border-collapse:collapse}
th,td{border:1px solid #333;padding:5px}
th{background:#eee}
.statut-ok{background:#dcfce7}
.statut-wait{background:#fff7ed}
</style>

<h1>Préinscriptions — IBIG EDUFORM</h1>
<p>Export PDF généré le '.date('d/m/Y H:i').'</p>

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
';

foreach ($rows as $r) {

  $bits = [];
  if (!empty($r['domaine_activite']))  { $bits[] = 'Dom. : '  . $r['domaine_activite']; }
  if (!empty($r['niveau_etude']))      { $bits[] = 'Niv. : '  . $r['niveau_etude']; }
  if (!empty($r['fonction']))          { $bits[] = 'Fonc. : ' . $r['fonction']; }
  if (!empty($r['annees_experience'])) { $bits[] = 'Exp. : '  . $r['annees_experience']; }
  $profil = $bits
    ? implode('<br>', array_map(fn($x) => htmlspecialchars($x, ENT_QUOTES, 'UTF-8'), $bits))
    : '—';

  $html .= '
  <tr>
    <td>'.date('d/m/Y H:i', strtotime($r['created_at'])).'</td>

    <td>'.htmlspecialchars(
          trim(($r['prenoms'] ?? '').' '.($r['nom'] ?? '')),
          ENT_QUOTES,
          'UTF-8'
        ).'</td>

    <td>'.htmlspecialchars(($r['telephone'] ?? ''), ENT_QUOTES, 'UTF-8').'<br>'
       .htmlspecialchars(($r['email'] ?? ''), ENT_QUOTES, 'UTF-8').'</td>

    <td>'.htmlspecialchars(($r['formation'] ?? '—'), ENT_QUOTES, 'UTF-8').'</td>

    <td>'.$profil.'</td>

    <td>'.htmlspecialchars(($r['statut'] ?? ''), ENT_QUOTES, 'UTF-8').'</td>
  </tr>';
}

$html .= '</tbody></table>';

$mpdf->WriteHTML($html);

$filename = 'preinscriptions_' . date('Y-m-d_His') . '.pdf';
$mpdf->Output($filename, 'D');
exit;