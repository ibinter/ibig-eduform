<?php
declare(strict_types=1);

/**
 * Middleware — VERSION FINALE STABLE
 */

require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/csrf.php';

class Middleware
{
  public static function requireAuth(): void
  {
    if (!auth_check()) {
      $next = $_SERVER['REQUEST_URI'] ?? '/admin/dashboard.php';
      redirect('/admin/auth/login.php?next=' . urlencode($next));
    }

    $u = auth_user();
    if (!empty($u['status']) && $u['status'] !== 'active') {
      http_response_code(403);
      exit('Compte désactivé.');
    }
  }
}
