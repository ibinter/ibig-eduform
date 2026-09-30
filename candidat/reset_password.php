<?php
declare(strict_types=1);
define('PUBLIC_PAGE', true);

require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/helpers.php';

$pdo = Database::connect();
$token = $_GET['token'] ?? '';
$error = '';
$success = '';

$stmt = $pdo->prepare("
  SELECT email
  FROM password_resets
  WHERE token=? AND expires_at > NOW()
  LIMIT 1
");
$stmt->execute([$token]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
  die("Lien invalide ou expiré.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $p1 = $_POST['password'] ?? '';
  $p2 = $_POST['confirm'] ?? '';

  if ($p1 === '' || $p2 === '') {
    $error = "Veuillez remplir tous les champs.";
  } elseif ($p1 !== $p2) {
    $error = "Les mots de passe ne correspondent pas.";
  } elseif (strlen($p1) < 6) {
    $error = "Mot de passe trop court (6 caractères minimum).";
  } else {
    $hash = password_hash($p1, PASSWORD_DEFAULT);

    $pdo->prepare("UPDATE candidats SET mot_de_passe=? WHERE email=?")
        ->execute([$hash, $row['email']]);

    $pdo->prepare("DELETE FROM password_resets WHERE email=?")
        ->execute([$row['email']]);

    $success = "Mot de passe réinitialisé avec succès.";
  }
}
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Réinitialisation mot de passe</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
body{font-family:Inter,Arial;background:#f1f5f9;display:flex;align-items:center;justify-content:center;height:100vh}
.card{background:#fff;padding:30px;border-radius:14px;width:100%;max-width:420px;box-shadow:0 10px 30px rgba(0,0,0,.1)}
input{width:100%;padding:12px;margin-top:10px;border-radius:10px;border:1px solid #cbd5e1}
button{width:100%;padding:12px;margin-top:14px;border-radius:10px;border:0;background:#0b5ed7;color:#fff;font-weight:800}
.msg{padding:10px;border-radius:10px;margin-bottom:12px;font-size:14px}
.err{background:#fee2e2;color:#991b1b}
.ok{background:#dcfce7;color:#166534}
a{color:#0b5ed7;text-decoration:none;font-size:13px}
</style>
</head>
<body>

<div class="card">
  <h2>Nouveau mot de passe</h2>

  <?php if ($error): ?><div class="msg err"><?= e($error); ?></div><?php endif; ?>
  <?php if ($success): ?>
    <div class="msg ok"><?= e($success); ?><br>
      <a href="login.php">Se connecter</a>
    </div>
  <?php endif; ?>

  <?php if (!$success): ?>
  <form method="post">
    <input type="password" name="password" placeholder="Nouveau mot de passe" required>
    <input type="password" name="confirm" placeholder="Confirmer" required>
    <button type="submit">Réinitialiser</button>
  </form>
  <?php endif; ?>
</div>

</body>
</html>
