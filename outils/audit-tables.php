<?php
require_once __DIR__ . '/../core/secrets.php';
header('Content-Type: application/json; charset=utf-8');
try {
    $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo json_encode(['tables'=>$tables], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode(['error'=>$e->getMessage()]);
}
