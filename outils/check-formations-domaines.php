<?php
require_once __DIR__ . '/../core/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
try {
    $pdo = Database::connect();
    $rows = $pdo->query("SELECT domaine, COUNT(*) as nb FROM formations WHERE is_samedi_pro=0 AND statut='active' GROUP BY domaine ORDER BY domaine")->fetchAll(PDO::FETCH_ASSOC);
    $titres = $pdo->query("SELECT domaine, LOWER(TRIM(titre)) as t FROM formations WHERE is_samedi_pro=0 AND statut='active' ORDER BY domaine, titre")->fetchAll(PDO::FETCH_ASSOC);
    $byDom = [];
    foreach($titres as $r){ $byDom[$r['domaine']][] = $r['t']; }
    echo json_encode(['stats'=>$rows,'titres'=>$byDom], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
} catch(Throwable $e){ echo json_encode(['error'=>$e->getMessage()]); }
