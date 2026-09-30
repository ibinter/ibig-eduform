<?php
declare(strict_types=1);

/*
 |--------------------------------------------------
 | INSCRIPTION ENTREPRISE — IBIG EDUFORM
 |--------------------------------------------------
*/

define('PUBLIC_PAGE', true);

require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/database.php';

$pdo = Database::connect();

$error = '';
$success = '';

/* ============================
   TRAITEMENT FORMULAIRE
============================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $nomLegal = trim($_POST['nom_legal'] ?? '');
  $email    = strtolower(trim($_POST['email'] ?? ''));
  $pass     = (string)($_POST['password'] ?? '');
  $confirm  = (string)($_POST['password_confirm'] ?? '');

  if ($nomLegal === '' || $email === '' || $pass === '' || $confirm === '') {
    $error = "Tous les champs sont obligatoires.";
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = "Adresse email invalide.";
  } elseif (strlen($pass) < 8) {
    $error = "Le mot de passe doit contenir au moins 8 caractères.";
  } elseif ($pass !== $confirm) {
    $error = "Les mots de passe ne correspondent pas.";
  } else {

    // Vérifier unicité email
    $chk = $pdo->prepare("SELECT id FROM entreprises WHERE email = ? LIMIT 1");
    $chk->execute([$email]);

    if ($chk->fetch()) {
      $error = "Un compte entreprise existe déjà avec cet email.";
    } else {

      $hash = password_hash($pass, PASSWORD_DEFAULT);

      $ins = $pdo->prepare("
        INSERT INTO entreprises
          (nom_legal, email, password_hash, statut, created_at)
        VALUES
          (?, ?, ?, 'en_attente', NOW())
      ");
      $ins->execute([$nomLegal, $email, $hash]);

      $success = "Compte entreprise créé avec succès. Vous pouvez vous connecter.";
    }
    
    $token = bin2hex(random_bytes(32));

    $upd = $pdo->prepare("
      UPDATE entreprises
      SET email_verify_token = ?
      WHERE email = ?
      LIMIT 1
    ");
    $upd->execute([$token, $email]);
    
    $verifyLink = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')
      . '/entreprise/verify_email.php?token=' . urlencode($token);
    
    /* Envoi email (si mail() est configuré) */
    $subject = "Vérification de votre email — IBIG EDUFORM";
    $message = "Bonjour,\n\nVeuillez confirmer votre email en cliquant sur le lien:\n$verifyLink\n\nIBIG EDUFORM";
    $headers = "From: IBIG EDUFORM <noreply@ibig-eduform.com>\r\n";
    @mail($email, $subject, $message, $headers);
    
    $success = "Compte créé. Un email de vérification a été envoyé.";

  }
}
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Créer un compte entreprise – IBIG EDUFORM</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<style>
:root{
  --blue:#0b5ed7;
  --dark:#1e293b;
  --gray:#f1f5f9;
}
body{
  margin:0;
  font-family:Inter,Arial,Helvetica,sans-serif;
  background:var(--gray);
  min-height:100vh;
  display:flex;
  align-items:center;
  justify-content:center;
}
.card{
  background:#fff;
  width:100%;
  max-width:460px;
  padding:32px;
  border-radius:16px;
  box-shadow:0 15px 40px rgba(0,0,0,.12);
}
.logo{text-align:center;margin-bottom:16px}
.logo img{height:46px}
h1{
  margin:0 0 22px;
  font-size:20px;
  font-weight:800;
  text-align:center;
  color:var(--dark);
}
.input{
  width:100%;
  padding:14px;
  border-radius:10px;
  border:1px solid #cbd5e1;
  margin-top:12px;
  font-size:14px;
}
.input:focus{outline:none;border-color:var(--blue)}
.btn{
  width:100%;
  margin-top:22px;
  padding:14px;
  border-radius:10px;
  border:0;
  background:var(--blue);
  color:#fff;
  font-size:14px;
  font-weight:800;
  cursor:pointer;
}
.btn:hover{opacity:.95}
.err{
  background:#fee2e2;
  color:#991b1b;
  padding:12px;
  border-radius:10px;
  margin-bottom:14px;
  font-size:14px;
  text-align:center;
}
.ok{
  background:#dcfce7;
  color:#166534;
  padding:12px;
  border-radius:10px;
  margin-bottom:14px;
  font-size:14px;
  text-align:center;
}
.links{
  margin-top:18px;
  text-align:center;
  font-size:13px;
}
.links a{
  color:var(--blue);
  text-decoration:none;
  font-weight:700;
}
</style>
</head>
<body>

<div class="card">

  <div class="logo">
    <img src="/assets/images/logo.png" alt="IBIG EDUFORM">
  </div>

  <h1>Créer un compte entreprise</h1>

  <?php if ($error): ?><div class="err"><?= htmlspecialchars($error); ?></div><?php endif; ?>
  <?php if ($success): ?><div class="ok"><?= htmlspecialchars($success); ?></div><?php endif; ?>

  <form method="post" autocomplete="off">

    <input class="input" type="text" name="nom_legal"
           placeholder="Nom légal de l’entreprise"
           value="<?= htmlspecialchars($nomLegal ?? ''); ?>"
           required>

    <input class="input" type="email" name="email"
           placeholder="Adresse email"
           value="<?= htmlspecialchars($email ?? ''); ?>"
           required>

    <input class="input" type="password" name="password"
           placeholder="Mot de passe (8 caractères min.)"
           required>

    <input class="input" type="password" name="password_confirm"
           placeholder="Confirmer le mot de passe"
           required>

    <button class="btn" type="submit">Créer le compte</button>

  </form>

  <div class="links">
    <a href="/entreprise/login.php">Déjà inscrit ? Se connecter</a>
  </div>

</div>

</body>
</html>
