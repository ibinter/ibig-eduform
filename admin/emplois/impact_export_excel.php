<?php
require_once __DIR__ . '/../../core/database.php';

session_start();
if (empty($_SESSION['admin_id'])) exit;

$pdo = Database::connect();

/* Headers Excel */
header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=impact_ibig.xls");

/* En-tête */
echo "Type de contrat\tInsertions\n";

/* Données */
$rows = $pdo->query("
  SELECT
    COALESCE(o.type_contrat,'Non defini') AS type_contrat,
    COUNT(i.id) AS total
  FROM insertions i
  LEFT JOIN offres_emploi o ON o.id = i.offre_id
  GROUP BY type_contrat
  ORDER BY total DESC
")->fetchAll(PDO::FETCH_ASSOC);

/* Sortie */
foreach ($rows as $r) {
  echo $r['type_contrat'] . "\t" . (int)$r['total'] . "\n";
}
