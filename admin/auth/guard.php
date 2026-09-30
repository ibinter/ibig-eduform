<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/csrf.php';

function require_login(): void {
  if (empty($_SESSION['user'])) {
    header('Location: /admin/auth/login.php');
    exit;
  }
}

function user(): array {
  return $_SESSION['user'];
}

function has_permission(string $perm): bool {
  static $cache = [];

  $u = user();
  if ($u['role'] === 'super_admin') return true;

  if (!isset($cache[$u['role']])) {
    $pdo = Database::connect();
    $stmt = $pdo->prepare("
      SELECT permission_code 
      FROM role_permissions 
      WHERE role = ?
    ");
    $stmt->execute([$u['role']]);
    $cache[$u['role']] = $stmt->fetchAll(PDO::FETCH_COLUMN);
  }

  return in_array($perm, $cache[$u['role']], true);
}

function require_permission(string $perm): void {
  require_login();
  if (!has_permission($perm)) {
    http_response_code(403);
    exit('⛔ Accès refusé');
  }
}