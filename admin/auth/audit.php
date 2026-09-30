<?php
declare(strict_types=1);

/**
 * ============================================================
 * IBIG EDUFORM — AUDIT LOGS
 * ============================================================
 */

require_once __DIR__ . '/../../core/database.php';

function audit_log(
  string $action,
  string $entity,
  ?int $entityId = null,
  ?string $details = null
): void {

  if (empty($_SESSION['user']['id'])) {
    return; // sécurité : on ne log pas sans user
  }

  $pdo = Database::connect();

  $stmt = $pdo->prepare("
    INSERT INTO audit_logs
      (user_id, user_role, action, entity, entity_id, details, ip_address, user_agent)
    VALUES
      (?, ?, ?, ?, ?, ?, ?, ?)
  ");

  $stmt->execute([
    (int) $_SESSION['user']['id'],
    $_SESSION['user']['role'],
    $action,
    $entity,
    $entityId,
    $details,
    $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 250)
  ]);
}