<?php
if (($_GET['k'] ?? '') !== 'ibig-check-2026') { http_response_code(403); exit; }
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/core/config.php';
require_once __DIR__ . '/core/database.php';
$pdo = Database::connect();
$rows = $pdo->query("
    SELECT id, titre, duree, tarif_en_ligne, tarif_presentiel, tarif_hybride, annee, statut
    FROM formations
    WHERE titre LIKE '%Chef Comptable%' OR titre LIKE '%chef comptable%'
    ORDER BY titre
")->fetchAll(PDO::FETCH_ASSOC);
// Aussi vérifier distribution des tarifs par durée
$dist = $pdo->query("
    SELECT duree,
           COUNT(*) as nb,
           MIN(tarif_en_ligne) as tel_min, MAX(tarif_en_ligne) as tel_max,
           MIN(tarif_presentiel) as tp_min, MAX(tarif_presentiel) as tp_max
    FROM formations
    WHERE statut='active' AND (annee IS NULL OR annee=0) AND duree IS NOT NULL
    GROUP BY duree ORDER BY duree
")->fetchAll(PDO::FETCH_ASSOC);
echo json_encode(['chef_comptable'=>$rows, 'distribution_duree'=>$dist], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
@unlink(__FILE__);
