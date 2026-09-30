<?php
if (($_GET['k'] ?? '') !== 'ibig-check-2026') { http_response_code(403); exit('Forbidden'); }
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/core/config.php';
require_once __DIR__ . '/core/database.php';
$pdo = Database::connect();

// Récupérer tous les titres actifs du catalogue (annee=0/NULL)
$rows = $pdo->query("
    SELECT titre, tarif_en_ligne, tarif_presentiel
    FROM formations
    WHERE statut = 'active' AND (annee IS NULL OR annee = 0)
    ORDER BY titre ASC
")->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['total' => count($rows), 'titres' => array_column($rows, 'titre')], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
@unlink(__FILE__);
