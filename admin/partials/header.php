<?php
if (!isset($pageTitle)) $pageTitle = APP_NAME;
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($pageTitle); ?></title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body>

<!-- TOP BAR -->
<header class="admin-topbar">
  <strong><?= APP_NAME; ?> — Administration</strong>
</header>

<!-- LAYOUT ADMIN -->
<div class="admin-layout">

  <!-- SIDEBAR -->
  <aside class="admin-sidebar">

    <nav class="admin-menu">

      <a href="/admin/dashboard.php"
         class="<?= ($activeMenu ?? '') === 'dashboard' ? 'active' : ''; ?>">
        Dashboard
      </a>

      <a href="/admin/formations/index.php"
         class="<?= ($activeMenu ?? '') === 'formations' ? 'active' : ''; ?>">
        Formations
      </a>

      <a href="/admin/users/index.php"
         class="<?= ($activeMenu ?? '') === 'users' ? 'active' : ''; ?>">
        Utilisateurs
      </a>

    </nav>

  </aside>

  <!-- CONTENU PRINCIPAL -->
  <main class="admin-content">
