<?php
declare(strict_types=1);

require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../auth/middleware.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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

/* =========================
   FILTRES
========================= */
$q            = trim($_GET['q'] ?? '');
$formation_id = (int)($_GET['formation_id'] ?? ($_GET['formation'] ?? 0));
$statut       = trim($_GET['statut'] ?? '');
$niveau       = trim($_GET['niveau'] ?? '');
$ville        = trim($_GET['ville'] ?? '');
$from         = trim($_GET['from'] ?? '');
$to           = trim($_GET['to'] ?? '');

$where  = []; $params = [];
if ($q !== '') {
  $scols = ['p.nom','p.prenoms','p.email','p.telephone','p.ville'];
  foreach ($PROFIL_COLS as $c) { $scols[] = 'p.'.$c; }
  $where[] = '(' . implode(' OR ', array_map(fn($c) => $c.' LIKE ?', $scols)) . ')';
  foreach ($scols as $_) { $params[] = "%$q%"; }
}
if ($formation_id > 0) { $where[] = "p.formation_id = ?"; $params[] = $formation_id; }
if ($statut !== '')    { $where[] = "p.statut = ?";       $params[] = $statut; }
if ($niveau !== '')    { $where[] = "p.niveau = ?";       $params[] = $niveau; }
if ($ville !== '')     { $where[] = "p.ville LIKE ?";     $params[] = "%$ville%"; }
if ($from !== '')      { $where[] = "DATE(p.created_at) >= ?"; $params[] = $from; }
if ($to !== '')        { $where[] = "DATE(p.created_at) <= ?"; $params[] = $to; }
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$extraSel = '';
foreach ($PROFIL_COLS as $c) { $extraSel .= ", p.$c"; }

$sql = "
  SELECT p.nom, p.prenoms, p.email, p.telephone, p.ville,
         f.titre AS formation, p.source, p.statut, p.created_at $extraSel
  FROM preinscriptions p
  LEFT JOIN formations f ON f.id = p.formation_id
  $whereSql
  ORDER BY p.created_at DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   XLSX
========================= */
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Préinscriptions');

$data = [];
$data[] = [
  'Nom','Prénoms','Email','Téléphone','Ville','Formation',
  "Domaine d'activité","Niveau d'étude",'Fonction',"Années d'expérience",
  'Source','Statut','Date'
];
foreach ($rows as $r) {
  $data[] = [
    $r['nom'] ?? '',
    $r['prenoms'] ?? '',
    $r['email'] ?? '',
    $r['telephone'] ?? '',
    $r['ville'] ?? '',
    $r['formation'] ?? '',
    $r['domaine_activite'] ?? '',
    $r['niveau_etude'] ?? '',
    $r['fonction'] ?? '',
    $r['annees_experience'] ?? '',
    $r['source'] ?? '',
    $r['statut'] ?? '',
    !empty($r['created_at']) ? date('d/m/Y H:i', strtotime($r['created_at'])) : ''
  ];
}
$sheet->fromArray($data, null, 'A1');

/* En-têtes en gras + auto-size (A..M) */
$sheet->getStyle('A1:M1')->getFont()->setBold(true);
foreach (range('A','M') as $col) {
  $sheet->getColumnDimension($col)->setAutoSize(true);
}

/* =========================
   SORTIE
========================= */
$filename = 'preinscriptions_' . date('Y-m-d_His') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="'.$filename.'"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
