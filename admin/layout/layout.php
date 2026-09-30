<?php
declare(strict_types=1);

require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../core/auth.php';

$u = auth_user();

$pageTitle  = $pageTitle  ?? 'Administration';
$activeMenu = $activeMenu ?? 'dashboard';
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="color-scheme" content="dark">
  <title><?= e($pageTitle); ?> — IBIG EDUFORM</title>

  <link rel="icon" href="/favicon.ico">
  <link rel="stylesheet" href="/assets/css/admin.css?v=<?= @filemtime(dirname(__DIR__, 2) . '/assets/css/admin.css') ?: '1'; ?>">
  <link rel="stylesheet" href="/assets/css/responsive-safety.css">
</head>

<body class="admin-body">

<!-- ================= TOPBAR ================= -->
<header class="topbar" role="banner">
  <?php require __DIR__ . '/topbar.php'; ?>
</header>

<!-- ============ OVERLAY MOBILE ============ -->
<div class="sidebar-overlay" data-action="close-sidebar"></div>

<!-- ================= APP ================= -->
<div class="admin-app">

  <!-- ============ SIDEBAR ============ -->
  <?php require __DIR__ . '/sidebar.php'; ?>

  <!-- ============ MAIN ============ -->
  <main class="admin-main" role="main">
    <div class="admin-container">
      <?= $content ?? '' ?>
    </div>
  </main>

</div>

<!-- ================= JS ================= -->
<script src="/assets/js/admin-ui.js?v=<?= time(); ?>" defer></script>

</body>
</html>