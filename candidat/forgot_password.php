<?php
declare(strict_types=1);
define('PUBLIC_PAGE', true);

require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/helpers.php';

$pdo = Database::connect();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email'] ?? '');

  if ($email === '') {
    $error = "Veuillez saisir votre adresse email.";
  } else {
    $check = $pdo->prepare("SELECT id FROM candidats WHERE email=? LIMIT 1");
    $check->execute([$email]);

    if ($check->fetch()) {

      // Nettoyage anciens tokens
      $pdo->prepare("DELETE FROM password_resets WHERE email=?")->execute([$email]);

      $token = bin2hex(random_bytes(32));
      $expires = date('Y-m-d H:i:s', time() + 3600); // 1h

      $ins = $pdo->prepare("
        INSERT INTO password_resets (email, token, expires_at)
        VALUES (?, ?, ?)
      ");
      $ins->execute([$email, $token, $expires]);

      // Lien de reset
      $link = "https://ibig-eduform.com/candidat/reset_password.php?token=" . $token;

      // 👉 À brancher plus tard sur email réel
      // mail($email, "Réinitialisation mot de passe", "Lien : $link");

      $success = "Un lien de réinitialisation a été généré.<br>
                  <small><strong>DEV :</strong> <a href='$link'>Clique ici</a></small>";
    } else {
      $error = "Aucun compte associé à cet email.";
    }
  }
}
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Mot de passe oublié – IBIG EDUFORM</title>
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
  <h2>Mot de passe oublié</h2>

  <?php if ($error): ?><div class="msg err"><?= e($error); ?></div><?php endif; ?>
  <?php if ($success): ?><div class="msg ok"><?= $success; ?></div><?php endif; ?>

  <form method="post">
    <input type="email" name="email" placeholder="Votre email" required>
    <button type="submit">Envoyer le lien</button>
  </form>

  <p style="margin-top:14px">
    <a href="login.php">Retour à la connexion</a>
  </p>
</div>

</body>
</html>
