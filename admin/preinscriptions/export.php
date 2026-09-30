<?php
declare(strict_types=1);

require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../auth/middleware.php';

Middleware::requireAuth();

$pdo = Database::connect();

/* Colonnes « profil » disponibles (tolérant) */
$PROFIL_COLS = [];
try {
  $cols = $pdo->query("SHOW COLUMNS FROM preinscriptions")->fetchAll(PDO::FETCH_COLUMN);
  foreach (['domaine_activite','niveau_etude','fonction','annees_experience'] as $c) {
    if (in_array($c, $cols, true)) { $PROFIL_COLS[] = $c; }
  }
} catch (Throwable $e) {}

/* =========================
   FILTRES (alignés avec l'index)
========================= */
$q            = trim($_GET['q'] ?? '');
$formation_id = (int)($_GET['formation_id'] ?? ($_GET['formation'] ?? 0));
$statut       = trim($_GET['statut'] ?? '');
$niveau       = trim($_GET['niveau'] ?? '');
$ville        = trim($_GET['ville'] ?? '');
$from         = trim($_GET['from'] ?? '');
$to           = trim($_GET['to'] ?? '');

$where  = [];
$params = [];

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

/* SELECT (colonnes profil ajoutées si présentes) */
$extraSel = '';
foreach ($PROFIL_COLS as $c) { $extraSel .= ", p.$c"; }

$sql = "
  SELECT
    p.nom, p.prenoms, p.email, p.telephone, p.ville,
    f.titre AS formation, p.niveau,
    p.source, p.statut, p.created_at $extraSel
  FROM preinscriptions p
  LEFT JOIN formations f ON f.id = p.formation_id
  $whereSql
  ORDER BY p.created_at DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   EXPORT CSV (UTF-8 Excel OK)
========================= */
$filename = 'preinscriptions_' . date('Y-m-d_His') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="'.$filename.'"');
header('Pragma: no-cache');
header('Expires: 0');

$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF"); // BOM UTF-8

fputcsv($out, [
  'Nom','Prénoms','Email','Téléphone','Ville','Formation','Niveau formation',
  'Domaine d\'activité','Niveau d\'étude','Fonction','Années d\'expérience',
  'Source','Statut','Date'
], ';');

foreach ($rows as $r) {
  fputcsv($out, [
    $r['nom'] ?? '',
    $r['prenoms'] ?? '',
    $r['email'] ?? '',
    $r['telephone'] ?? '',
    $r['ville'] ?? '',
    $r['formation'] ?? '',
    ['debutant'=>'Débutant','intermediaire'=>'Intermédiaire','expert'=>'Expert'][$r['niveau'] ?? ''] ?? ($r['niveau'] ?? ''),
    $r['domaine_activite'] ?? '',
    $r['niveau_etude'] ?? '',
    $r['fonction'] ?? '',
    $r['annees_experience'] ?? '',
    $r['source'] ?? '',
    $r['statut'] ?? '',
    !empty($r['created_at']) ? date('d/m/Y H:i', strtotime($r['created_at'])) : ''
  ], ';');
}

fclose($out);
exit;
