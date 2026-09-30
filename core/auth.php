<?php
declare(strict_types=1);

/**
 * Auth helper — VERSION STABLE & COMPLÈTE
 * âÂÂ Une seule clé de session : $_SESSION['user']
 */

if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

/* ============================
   BASE AUTH
============================ */

function auth_check(): bool {
  return isset($_SESSION['user']) && is_array($_SESSION['user']);
}

function auth_user(): ?array {
  return auth_check() ? $_SESSION['user'] : null;
}

function auth_login(array $user): void {
  $_SESSION['user'] = [
    'id'         => (int)($user['id'] ?? 0),
    'email'      => (string)($user['email'] ?? ''),
    'role'       => (string)($user['role'] ?? 'admin'),
    'first_name' => (string)($user['first_name'] ?? ''),
    'last_name'  => (string)($user['last_name'] ?? ''),
    'status'     => (string)($user['status'] ?? 'active'),
  ];
}

function auth_logout(): void {
  unset($_SESSION['user']);
}

/* ============================
   PROTECTION DES PAGES
============================ */

function requireAuth(): void {
  if (!auth_check()) {
    header('Location: /admin/auth/login.php');
    exit;
  }
}

/* requireRole() peut déjà être défini par core/security.php
   (signature string|array, compatible). On évite la redéclaration fatale. */
if (!function_exists('requireRole')) {
function requireRole(string|array $role): void {
  requireAuth();

  $user = auth_user();
  $roles = (array)$role;

  if (!$user || !in_array($user['role'] ?? '', $roles, true)) {
    http_response_code(403);
    die('âÂÂ Accès refusé — permissions insuffisantes');
  }
}
} // fin if(!function_exists('requireRole'))

if (!function_exists('requireRoles')) {
function requireRoles(array $roles): void
{
  requireAuth();
  $userRole = $_SESSION['user']['role'] ?? '';
  if (!in_array($userRole, $roles, true)) {
    http_response_code(403);
    exit('Accès interdit');
  }
}
} // fin if(!function_exists('requireRoles'))

