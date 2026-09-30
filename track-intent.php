<?php
require_once __DIR__ . '/core/database.php';
$data = json_decode(file_get_contents('php://input'), true);
if (!$data) exit;

$pdo = Database::connect();

$stmt = $pdo->prepare("
  INSERT INTO site_intents (session_key, formation_id, action, value, utm_source, utm_campaign)
  VALUES (?, ?, ?, ?, ?, ?)
");

$stmt->execute([
  $data['session_key'] ?? null,
  $data['formation_id'] ?? null,
  $data['action'] ?? null,
  $data['value'] ?? null,
  $data['utm_source'] ?? null,
  $data['utm_campaign'] ?? null
]);
