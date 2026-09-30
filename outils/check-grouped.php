<?php
require_once __DIR__ . '/../core/secrets.php';
header('Content-Type: application/json; charset=utf-8');
$pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS);
$rows = $pdo->query("SELECT id, titre, duree, tarif_en_ligne, tarif_presentiel FROM formations WHERE is_samedi_pro=0 AND (titre LIKE '%en 1%' OR titre LIKE '%EN 1%' OR titre LIKE '%en un%' OR titre LIKE '%pack%' OR titre LIKE '%bundle%' OR titre LIKE '%combo%' OR titre REGEXP '[0-9]+[[:space:]]*(formations?|certif)[[:space:]]') ORDER BY titre")->fetchAll(PDO::FETCH_ASSOC);
echo json_encode(['count'=>count($rows),'rows'=>$rows], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
