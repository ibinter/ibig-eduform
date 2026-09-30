<?php
declare(strict_types=1);

require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../auth/middleware.php';

Middleware::requireAuth();

$pdo = Database::connect();

/* =========================
   HEADERS CSV (EXCEL OK)
========================= */
$filename = 'calendrier_formations_' . date('Y-m-d_His') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="'.$filename.'"');
header('Pragma: no-cache');
header('Expires: 0');

$out = fopen('php://output', 'w');

/* BOM UTF-8 pour Excel */
fwrite($out, "\xEF\xBB\xBF");

/* En-têtes */
fputcsv($out, [
  'Formation',
  'Date début',
  'Date fin',
  'Durée',
  'Mode'
], ';');

/* =========================
   DONNÉES
========================= */
$sql = "
  SELECT
    f.titre,
    c.date_debut,
    c.date_fin,
    c.duree,
    c.mode
  FROM calendrier_formations c
  INNER JOIN formations f ON f.id = c.formation_id
  ORDER BY c.date_debut DESC
";

$stmt = $pdo->query($sql);

/* Lignes */
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
  fputcsv($out, [
    $r['titre'] ?? '',
    !empty($r['date_debut']) ? date('d/m/Y', strtotime($r['date_debut'])) : '',
    !empty($r['date_fin'])   ? date('d/m/Y', strtotime($r['date_fin']))   : '',
    $r['duree'] ?? '',
    $r['mode'] ?? ''
  ], ';');
}

fclose($out);
exit;