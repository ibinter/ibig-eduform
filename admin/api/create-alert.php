<?php
require_once __DIR__ . '/../../core/database.php';

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) exit;

$pdo = Database::connect();

$stmt = $pdo->prepare("
  INSERT INTO admin_alerts
  (type, formation_id, session_key, score, message)
  VALUES (?, ?, ?, ?, ?)
");

$stmt->execute([
  $data['type'],
  $data['formation_id'],
  $data['session_key'],
  $data['score'],
  $data['message']
]);
