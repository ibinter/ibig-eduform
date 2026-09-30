<?php
declare(strict_types=1);

define('PUBLIC_PAGE', true);
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/database.php';

$pdo = Database::connect();

$token = trim((string)($_GET['token'] ?? ''));
$ok = false;

if ($token !== '' && ctype_xdigit($token) && strlen($token) >= 40) {

  $s = $pdo->prepare("
    SELECT id
    FROM entreprises
    WHERE email_verify_token = ?
    LIMIT 1
  ");
  $s->execute([$token]);
  $id = (int)($s->fetchColumn() ?: 0);

  if ($id > 0) {
    $up = $pdo->prepare("
      UPDATE entreprises
      SET email_verified = 1,
          email_verify_token = NULL
      WHERE id = ?
      LIMIT 1
    ");
    $up->execute([$id]);
    $ok = true;
  }
}
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Vérification email – IBIG EDUFORM</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
:root{--blue:#0b5ed7;--dark:#1e293b;--gray:#f1f5f9}
body{margin:0;font-family:Inter,Arial;background:var(--gray);min-height:100vh;display:flex;align-items:center;justify-content:center}
.card{background:#fff;width:100%;max-width:520px;padding:28px;border-radius:16px;box-shadow:0 15px 40px rgba(0,0,0,.12)}
h1{margin:0 0 10px;color:var(--dark);font-size:20px}
p{margin:0 0 14px;color:#334155}
a.btn{display:inline-block;padding:10px 14px;border-radius:10px;background:var(--blue);color:#fff;text-decoration:none;font-weight:900;font-size:13px}
</style>
</head>
<body>
<div class="card">
  <h1>Vérification email</h1>
  <?php if ($ok): ?>
    <p>Votre adresse email a été vérifiée avec succès.</p>
  <?php else: ?>
    <p>Lien invalide ou expiré.</p>
  <?php endif; ?>
  <a class="btn" href="/entreprise/login.php">Se connecter</a>
</div>
</body>
</html>
