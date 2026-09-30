<?php
declare(strict_types=1);

/*
 |--------------------------------------------------
 | LOGIN ENTREPRISE — IBIG EDUFORM
 |--------------------------------------------------
*/

define('PUBLIC_PAGE', true);

require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/database.php';

$pdo = Database::connect();
$error = '';

/* ============================
   TRAITEMENT FORMULAIRE
============================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $email = strtolower(trim($_POST['email'] ?? ''));
  $pass  = (string)($_POST['password'] ?? '');

  if ($email === '' || $pass === '') {
    $error = "Veuillez renseigner votre email et votre mot de passe.";
  } else {

    $stmt = $pdo->prepare("
      SELECT id, email, password_hash, statut
      FROM entreprises
      WHERE email = ?
      LIMIT 1
    ");
    $stmt->execute([$email]);
    $ent = $stmt->fetch(PDO::FETCH_ASSOC);

    if (
      !$ent ||
      empty($ent['password_hash']) ||
      !password_verify($pass, $ent['password_hash'])
    ) {
      $error = "Identifiants incorrects.";
    } elseif ($ent['statut'] === 'suspendue') {
      $error = "Compte suspendu. Contactez l’administration.";
    } else {

      session_regenerate_id(true);

      $_SESSION['entreprise_id']     = (int)$ent['id'];
      $_SESSION['entreprise_email']  = $ent['email'];
      $_SESSION['entreprise_statut'] = $ent['statut'];

      header('Location: /entreprise/dashboard.php');
      exit;
    }
  }
}
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Connexion Entreprise – IBIG EDUFORM</title>
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
  color:#111827;
}

.card{
  background:#fff;
  width:100%;
  max-width:420px;
  padding:32px 30px;
  border-radius:16px;
  box-shadow:0 15px 40px rgba(0,0,0,.12);
}

.logo{
  text-align:center;
  margin-bottom:18px;
}
.logo img{
  height:46px;
}

h1{
  margin:0 0 22px;
  font-size:20px;
  font-weight:800;
  text-align:center;
  color:var(--dark);
}

.input{
  width:100%;
  padding:14px 14px;
  border-radius:10px;
  border:1px solid #cbd5e1;
  margin-top:12px;
  font-size:14px;
}
.input:focus{
  outline:none;
  border-color:var(--blue);
}

.btn{
  width:100%;
  margin-top:20px;
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
.links span{
  color:#94a3b8;
  margin:0 6px;
}
</style>

</head>
<body>

<div class="card">

    <div class="logo">
      <img src="/assets/images/logo.png" alt="IBIG EDUFORM">
    </div>

  <h1>Connexion Entreprise</h1>

  <?php if ($error): ?>
    <div class="err"><?= htmlspecialchars($error); ?></div>
  <?php endif; ?>

  <form method="post" autocomplete="off">

    <input class="input" type="email" name="email"
           placeholder="Adresse email"
           required>

    <input class="input" type="password" name="password"
           placeholder="Mot de passe"
           required>

    <button class="btn" type="submit">
      Se connecter
    </button>

  </form>

  <div class="links">
    <a href="/entreprise/register.php">Créer un compte</a>
    &nbsp;|&nbsp;
    <a href="/entreprise/forgot.php">Mot de passe oublié</a>
  </div>

</div>

</body>
</html>
