<?php
declare(strict_types=1);

require_once __DIR__ . '/core/database.php';
require_once __DIR__ . '/core/security.php';

header('Content-Type: application/json');

$pdo = Database::connect();

/* Sécurité minimale */
$platform = $_POST['platform'] ?? '';
$pageUrl  = $_POST['page_url'] ?? '';
$formationId = isset($_POST['formation_id']) ? (int)$_POST['formation_id'] : null;

$allowed = ['whatsapp','facebook','linkedin','x','copy'];
if (!in_array($platform, $allowed, true)) {
  http_response_code(400);
  echo json_encode(['status'=>'error']);
  exit;
}

$stmt = $pdo->prepare("
  INSERT INTO site_share_events
  (formation_id, platform, page_url, session_key, ip_address, user_agent)
  VALUES (?, ?, ?, ?, ?, ?)
");

$stmt->execute([
  $formationId,
  $platform,
  $pageUrl,
  session_id() ?: null,
  $_SERVER['REMOTE_ADDR'] ?? null,
  substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255)
]);

echo json_encode(['status'=>'ok']);
