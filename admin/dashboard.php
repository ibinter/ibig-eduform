<?php
declare(strict_types=1);

/* ================= BOOTSTRAP ================= */
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/auth/middleware.php';

Middleware::requireAuth();

$u    = auth_user();
$role = $u['role'] ?? 'user';

/* ================= ROUTAGE PAR RÔLE =================
   Chaque rôle a son tableau de bord. Repli sûr sur le
   dashboard "user" si le fichier du rôle est absent
   (évite tout require sur un fichier inexistant). */
$map = [
  'super_admin' => 'super_admin.php',
  'admin'       => 'admin.php',
  'rh'          => 'dashboard_rh.php',
  'commercial'  => 'commercial.php',
  'user'        => 'dashboard_user.php',
];

$file = $map[$role] ?? 'dashboard_user.php';
$path = __DIR__ . '/dashboards/' . $file;

if (!is_file($path)) {
  $path = __DIR__ . '/dashboards/dashboard_user.php';
}

require $path;
