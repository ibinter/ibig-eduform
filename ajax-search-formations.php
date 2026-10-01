<?php
declare(strict_types=1);
/*
 * AJAX — recherche formations pour popup exit-intent
 * GET ?q=daf  → JSON [{id, titre, domaine}, ...]
 */
require_once __DIR__ . '/core/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=120');

$q = trim((string)($_GET['q'] ?? ''));

if (strlen($q) < 2) {
    echo json_encode([]);
    exit;
}

try {
    $pdo  = Database::connect();
    $like = '%' . $q . '%';
    $stmt = $pdo->prepare("
        SELECT id, titre, domaine
        FROM formations
        WHERE statut = 'active'
          AND (titre LIKE :q1 OR domaine LIKE :q2 OR description LIKE :q3)
        ORDER BY
          CASE WHEN titre LIKE :q4 THEN 0 ELSE 1 END,
          titre
        LIMIT 30
    ");
    $stmt->execute([':q1'=>$like,':q2'=>$like,':q3'=>$like,':q4'=>$like]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(array_map(fn($r)=>['id'=>(int)$r['id'],'titre'=>$r['titre'],'domaine'=>$r['domaine']], $rows), JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode([]);
}
