<?php
declare(strict_types=1);

/*
 |--------------------------------------------------
 | LOGIN CANDIDAT — IBIG EDUFORM
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

/*
 |--------------------------------------------------
 | TRAITEMENT FORMULAIRE
 |--------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $email    = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';

  if ($email === '' || $password === '') {
    $error = "Veuillez remplir tous les champs.";
  } else {

    $stmt = $pdo->prepare("
      SELECT
        id,
        nom,
        email,
        mot_de_passe
      FROM candidats
      WHERE email = ?
      LIMIT 1
    ");
    $stmt->execute([$email]);
    $candidat = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$candidat || !password_verify($password, $candidat['mot_de_passe'])) {
      $error = "Identifiants incorrects.";
    } else {

      /* Session candidat */
      $_SESSION['candidat_id']  = (int)$candidat['id'];
      $_SESSION['candidat_nom'] = $candidat['nom'];

      header('Location: dashboard.php');
      exit;
    }
  }
}
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Connexion Candidat – IBIG EDUFORM</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
body{
  font-family:Arial,Helvetica,sans-serif;
  background:#f1f5f9;
  display:flex;
  align-items:center;
  justify-content:center;
  height:100vh;
}
.card{
  background:#fff;
  width:100%;
  max-width:420px;
  padding:30px;
  border-radius:10px;
  box-shadow:0 10px 30px rgba(0,0,0,.1);
}
h1{font-size:22px;margin-bottom:10px}
p{color:#555;font-size:14px}
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
  cursor:pointer;
}
.error{
  background:#fee2e2;
  color:#991b1b;
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

  <h1>Connexion Candidat</h1>
  <p>Accédez à votre espace personnel IBIG EDUFORM</p>

  <?php if ($error): ?>
    <div class="error"><?= e($error); ?></div>
  <?php endif; ?>

  <form method="post">
    <input type="email" name="email" placeholder="Adresse email" required>
    <input type="password" name="password" placeholder="Mot de passe" required>
    <button type="submit">Se connecter</button>
  </form>

  <div class="links">
    <p>
      <a href="register.php">Créer un compte</a> |
      <a href="forgot_password.php">Mot de passe oublié</a>
    </p>
  </div>

</div>

</body>
</html>
