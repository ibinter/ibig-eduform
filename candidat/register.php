<?php
declare(strict_types=1);

/*
 |--------------------------------------------------
 | INSCRIPTION CANDIDAT — IBIG EDUFORM
 |--------------------------------------------------
*/

/* Page publique */
define('PUBLIC_PAGE', true);

require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/helpers.php';

/* Déjà connecté ? */
if (!empty($_SESSION['candidat_id'])) {
  header('Location: dashboard.php');
  exit;
}

$pdo = Database::connect();

$error = '';
$success = '';

/*
 |--------------------------------------------------
 | TRAITEMENT FORMULAIRE
 |--------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $nom       = trim($_POST['nom'] ?? '');
  $email     = trim($_POST['email'] ?? '');
  $telephone = trim($_POST['telephone'] ?? '');
  $password  = $_POST['password'] ?? '';
  $confirm   = $_POST['password_confirm'] ?? '';

  if ($nom === '' || $email === '' || $password === '' || $confirm === '') {
    $error = "Veuillez remplir tous les champs obligatoires.";
  }
  elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = "Adresse email invalide.";
  }
  elseif ($password !== $confirm) {
    $error = "Les mots de passe ne correspondent pas.";
  }
  elseif (strlen($password) < 6) {
    $error = "Le mot de passe doit contenir au moins 6 caractères.";
  }
  else {

    /* Vérifier email existant */
    $check = $pdo->prepare("SELECT id FROM candidats WHERE email = ? LIMIT 1");
    $check->execute([$email]);

    if ($check->fetch()) {
      $error = "Un compte existe déjà avec cette adresse email.";
    } else {

      /* Insertion */
      $hash = password_hash($password, PASSWORD_DEFAULT);

      $stmt = $pdo->prepare("
        INSERT INTO candidats
          (nom, email, telephone, mot_de_passe, created_at)
        VALUES
          (?, ?, ?, ?, NOW())
      ");
      $stmt->execute([
        $nom,
        $email,
        $telephone,
        $hash
      ]);

      $success = "Compte créé avec succès. Vous pouvez vous connecter.";
    }
  }
}
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Inscription Candidat – IBIG EDUFORM</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
body{
  font-family:Arial,Helvetica,sans-serif;
  background:#f1f5f9;
  display:flex;
  align-items:center;
  justify-content:center;
  min-height:100vh;
}
.card{
  background:#fff;
  width:100%;
  max-width:460px;
  padding:30px;
  border-radius:10px;
  box-shadow:0 10px 30px rgba(0,0,0,.1);
}
h1{font-size:22px;margin-bottom:6px}
p{color:#555;font-size:14px;margin-bottom:15px}
input{
  width:100%;
  padding:12px;
  margin-top:10px;
  border:1px solid #ccc;
  border-radius:6px;
}
button{
  width:100%;
  padding:12px;
  margin-top:15px;
  border:none;
  background:#0b5ed7;
  color:#fff;
  font-weight:bold;
  border-radius:6px;
}
.error{
  background:#fee2e2;
  color:#991b1b;
  padding:10px;
  border-radius:6px;
  margin-bottom:10px;
  font-size:14px;
}
.success{
  background:#dcfce7;
  color:#166534;
  padding:10px;
  border-radius:6px;
  margin-bottom:10px;
  font-size:14px;
}
.links{
  margin-top:15px;
  font-size:13px;
}
.links a{
  color:#0b5ed7;
  text-decoration:none;
}
</style>
</head>
<body>

<div class="card">

  <h1>Inscription Candidat</h1>
  <p>Créez votre compte IBIG EDUFORM</p>

  <?php if ($error): ?>
    <div class="error"><?= e($error); ?></div>
  <?php endif; ?>

  <?php if ($success): ?>
    <div class="success">
      <?= e($success); ?><br>
      <a href="login.php">Se connecter</a>
    </div>
  <?php endif; ?>

  <form method="post">

    <input type="text" name="nom" placeholder="Nom complet" required value="<?= e($_POST['nom'] ?? ''); ?>">

    <input type="email" name="email" placeholder="Adresse email" required value="<?= e($_POST['email'] ?? ''); ?>">

    <input type="text" name="telephone" placeholder="Téléphone (optionnel)" value="<?= e($_POST['telephone'] ?? ''); ?>">

    <input type="password" name="password" placeholder="Mot de passe" required>

    <input type="password" name="password_confirm" placeholder="Confirmer le mot de passe" required>

    <button type="submit">Créer mon compte</button>

  </form>

  <div class="links">
    <p>
      Déjà inscrit ?
      <a href="login.php">Se connecter</a>
    </p>
  </div>

</div>

</body>
</html>
