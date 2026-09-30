<?php
require_once __DIR__ . '/core/database.php';

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) exit;

$pdo = Database::connect();

$pdo->prepare("
  INSERT INTO site_intents (session_key, formation_id, action)
  VALUES (?, ?, 'whatsapp_click')
")->execute([
  $data['session_key'],
  $data['formation_id']
]);
