<?php
if (($_GET['k'] ?? '') !== 'ibig-slugs-2026') { http_response_code(403); exit; }
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/core/config.php';
require_once __DIR__ . '/core/database.php';
$pdo = Database::connect();
$rows = $pdo->query("
    SELECT id, titre, slug FROM formations
    WHERE statut='active' AND (annee IS NULL OR annee=0)
    AND titre LIKE '%Analyse Financière%'
    ORDER BY titre LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($rows, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
@unlink(__FILE__);
