<?php
declare(strict_types=1);

require_once __DIR__ . '/../_init.php';
require_once __DIR__ . '/../auth/middleware.php';

Middleware::requireAuth();

$pdo = Database::connect();

$statut = trim((string)($_GET['statut'] ?? ''));
$q      = trim((string)($_GET['q'] ?? ''));

$allowedStatus = ['nouvelle','en_cours','traitee','rejete'];

$where  = "WHERE 1=1";
$params = [];

if ($statut !== '' && in_array($statut, $allowedStatus, true)) {
  $where .= " AND statut = :statut";
  $params['statut'] = $statut;
}

if ($q !== '') {
  $where .= " AND (
    CONCAT(COALESCE(prenoms,''),' ',COALESCE(nom,'')) LIKE :q
    OR nom LIKE :q
    OR prenoms LIKE :q
    OR COALESCE(structure_nom,'') LIKE :q
    OR COALESCE(domaine_formation,'') LIKE :q
    OR COALESCE(theme_formation,'') LIKE :q
  )";
  $params['q'] = '%'.$q.'%';
}

$sql = "
  SELECT
    id, created_at, statut, type_demandeur,
    nom, prenoms, email, telephone,
    structure_nom, fonction, secteur,
    domaine_formation, theme_formation,
    objectif, niveau, nombre_participants,
    mode_formation, lieu, duree, periode_souhaitee,
    budget, urgence, message
  FROM demandes_formation
  $where
  ORDER BY created_at DESC
  LIMIT 5000
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$filename = 'demandes_formation_'.date('Y-m-d_His').'.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="'.$filename.'"');

$output = fopen('php://output', 'w');

/* BOM UTF-8 pour Excel */
fwrite($output, "\xEF\xBB\xBF");

if (!empty($rows)) {
  fputcsv($output, array_keys($rows[0]), ';');
  foreach ($rows as $r) {
    fputcsv($output, $r, ';');
  }
} else {
  fputcsv($output, ['Aucune donnee'], ';');
}

fclose($output);
exit;
