<?php
class MiddlewareRH {

  public static function requireRH(): void
  {
    if (session_status() === PHP_SESSION_NONE) {
      session_start();
    }

    // 🔐 Cas admin historique (IBIG)
    if (
      isset($_SESSION['admin_id'])
      || isset($_SESSION['admin_email'])
      || isset($_SESSION['admin'])
    ) {
      return;
    }

    // 🔐 Cas user connecté (peu importe le rôle)
    if (isset($_SESSION['user'])) {
      return;
    }

    // ❌ Non connecté
    header('Location: /admin/auth/login.php');
    exit;
  }
}
