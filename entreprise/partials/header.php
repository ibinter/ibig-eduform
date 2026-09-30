<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

/* ============================
   SÉCURITÉ : ENTREPRISE UNIQUEMENT
============================ */
if (empty($_SESSION['entreprise_id'])) {
  header("Location: /entreprise/login.php");
  exit;
}

$pageTitle = $pageTitle ?? "Espace Entreprise – IBIG EDUFORM";
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- CSS global -->
  <link rel="stylesheet" href="/assets/css/style.css?v=1.0">
  <link rel="stylesheet" href="/assets/css/header-eduform.css?v=1.0">

  <style>
    /* =========================
       LAYOUT ENTREPRISE
    ========================= */
    body{
      margin:0;
      font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
      background:#f8fafc;
      color:#0f172a;
    }

    .ent-header{
      background:linear-gradient(135deg,#0b3c5d,#1e40af);
      color:#ffffff;
      padding:16px 24px;
      display:flex;
      align-items:center;
      justify-content:space-between;
      box-shadow:0 6px 20px rgba(2,6,23,.25);
    }

    .ent-header .brand{
      display:flex;
      align-items:center;
      gap:14px;
      font-weight:900;
      letter-spacing:.3px;
    }

    .ent-header img{
      height:42px;
      display:block;
    }

    .ent-header nav{
      display:flex;
      gap:20px;
    }

    .ent-header nav a{
      color:#e5e7eb;
      text-decoration:none;
      font-weight:600;
      font-size:14.5px;
    }

    .ent-header nav a:hover{
      color:#f5a623;
    }

    .ent-container{
      max-width:1200px;
      margin:28px auto;
      padding:0 20px;
    }

    .badge-status{
      padding:4px 10px;
      border-radius:999px;
      font-size:12px;
      font-weight:700;
      background:#f5a623;
      color:#000000;
      margin-left:8px;
    }
  </style>
</head>
<body>

<header class="ent-header">
  <div class="brand">
    <img src="/assets/images/logo.png" alt="IBIG EDUFORM">
    <span>ESPACE ENTREPRISE</span>

    <?php if (!empty($_SESSION['entreprise_statut'])): ?>
      <span class="badge-status">
        <?= strtoupper(htmlspecialchars($_SESSION['entreprise_statut'], ENT_QUOTES, 'UTF-8')); ?>
      </span>
    <?php endif; ?>
  </div>

  <nav>
    <a href="/entreprise/dashboard.php">Dashboard</a>
    <a href="/entreprise/offres/index.php">Mes offres</a>
    <a href="/entreprise/logout.php"
       onclick="return confirm('Voulez-vous vous déconnecter ?');">
       Déconnexion
    </a>
  </nav>
</header>

<main class="ent-container">
