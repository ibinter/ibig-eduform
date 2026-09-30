<?php
declare(strict_types=1);

require_once __DIR__ . '/core/config.php';
require_once __DIR__ . '/core/database.php';
require_once __DIR__ . '/core/helpers.php';
require_once __DIR__ . '/core/intent_engine.php';

header('Content-Type: application/json; charset=utf-8');

try {
  $pdo = Database::connect();

  $sessionKey  = trim((string)($_POST['session_key'] ?? ''));
  $action      = trim((string)($_POST['action'] ?? ''));
  $label       = trim((string)($_POST['label'] ?? ''));
  $formationId = trim((string)($_POST['formation_id'] ?? ''));
  $utmSource   = trim((string)($_POST['utm_source'] ?? ''));
  $utmCampaign = trim((string)($_POST['utm_campaign'] ?? ''));
  $pageUrl     = trim((string)($_POST['page_url'] ?? ''));

  if ($sessionKey === '' || $action === '') {
    echo json_encode(['ok' => false, 'error' => 'missing_params']);
    exit;
  }

  // 1) Log event
  // NOTE: si tu n'as pas ajouté formation_id/page_url à site_events, enlève-les ici.
  $stmt = $pdo->prepare("
    INSERT INTO site_events (session_key, formation_id, action, label, utm_source, utm_campaign, page_url, created_at)
    VALUES (?, NULLIF(?,''), ?, NULLIF(?,''), NULLIF(?,''), NULLIF(?,''), NULLIF(?,''), NOW())
  ");
  $stmt->execute([$sessionKey, $formationId, $action, $label, $utmSource, $utmCampaign, $pageUrl]);

  // 2) Apply ICE
  ice_apply_event($pdo, [
    'session_key'  => $sessionKey,
    'action'       => $action,
    'label'        => $label,
    'formation_id' => $formationId !== '' ? (int)$formationId : null,
    'utm_source'   => $utmSource,
    'utm_campaign' => $utmCampaign,
    'page_url'     => $pageUrl
  ]);

  echo json_encode(['ok' => true]);
} catch (Throwable $e) {
  echo json_encode(['ok' => false, 'error' => 'server_error']);
}
