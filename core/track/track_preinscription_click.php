<?php
require_once __DIR__ . '/core/database.php';
session_start();

$pdo = Database::connect();
$data = json_decode(file_get_contents('php://input'), true);

$sessionId   = $_SESSION['visit_session'] ?? null;
$formationId = $data['formation_id'] ?? null;

if ($sessionId) {
  $stmt = $pdo->prepare("
    INSERT INTO preinscription_clicks
    (session_id, formation_id, clicked_at)
    VALUES (?, ?, NOW())
  ");
  $stmt->execute([$sessionId, $formationId]);
}
