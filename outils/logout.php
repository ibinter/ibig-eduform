<?php
/**
 * IBIG EDUFORM — /outils/logout.php
 * Force la déconnexion Basic Auth en renvoyant un 401 avec WWW-Authenticate.
 * Le navigateur efface alors ses credentials mis en cache pour ce realm.
 */
if (!isset($_SERVER['PHP_AUTH_USER']) || $_SERVER['PHP_AUTH_USER'] !== 'logged_out') {
    header('WWW-Authenticate: Basic realm="IBIG OUTILS — Déconnexion en cours"');
    header('HTTP/1.1 401 Unauthorized');
}
?><!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Déconnexion — IBIG EDUFORM</title>
<style>
  body{font-family:'Segoe UI',Arial,sans-serif;background:#f8fafc;display:flex;
       align-items:center;justify-content:center;min-height:100vh;margin:0;}
  .box{background:#fff;border-radius:12px;box-shadow:0 4px 24px rgba(0,0,0,.08);
       padding:40px 48px;text-align:center;max-width:360px;}
  .icon{font-size:48px;margin-bottom:12px;}
  h1{font-size:20px;color:#0d1f3c;margin-bottom:8px;}
  p{font-size:13px;color:#64748b;margin-bottom:24px;}
  a{display:inline-block;padding:10px 24px;background:#0d1f3c;color:#fff;
    border-radius:8px;text-decoration:none;font-size:13px;font-weight:600;}
  a:hover{background:#1e3a5f;}
</style>
</head>
<body>
<div class="box">
  <div class="icon">🔒</div>
  <h1>Déconnexion réussie</h1>
  <p>Vos identifiants ont été effacés.<br>Vous pouvez fermer cet onglet ou vous reconnecter.</p>
  <a href="tdr-generator.html">🔑 Se reconnecter</a>
</div>
</body>
</html>
