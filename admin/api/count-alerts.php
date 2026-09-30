<?php
require_once __DIR__ . '/../../core/database.php';
$pdo = Database::connect();

$total = (int)$pdo->query(
  "SELECT COUNT(*) FROM admin_alerts WHERE is_read = 0"
)->fetchColumn();

echo json_encode(['total' => $total]);
