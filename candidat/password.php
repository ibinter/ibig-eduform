<?php
declare(strict_types=1);
require_once __DIR__ . '/_auth.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $current = $_POST['current_password'] ?? '';
  $new     = $_POST['new_password'] ?? '';
  $confirm = $_POST['confirm_password'] ?? '';

  if ($current === '' || $new === '' || $confirm === '') {
    $error = "Veuillez remplir tous les champs.";
  } elseif (!password_verify($current, $candidat['mot_de_passe'])) {
    $error = "Mot de passe actuel incorrect.";
  } elseif ($new !== $confirm) {
    $error = "Les mots de passe ne correspondent pas.";
  } elseif (strlen($new) < 6) {
    $error = "Le nouveau mot de passe doit contenir au moins 6 caractères.";
  } else {
    $hash = password_hash($new, PASSWORD_DEFAULT);
    $up = $pdo->prepare("UPDATE candidats SET mot_de_passe=? WHERE id=?");
    $up->execute([$hash, $candidatId]);
    $success = "Mot de passe mis à jour.";
  }
}
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Mot de passe – IBIG EDUFORM</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
body{margin:0;font-family:Inter,Arial;background:#f1f5f9}
.header{background:linear-gradient(90deg,#0b5ed7,#1d4ed8);color:#fff;padding:16px 24px;display:flex;justify-content:space-between;align-items:center}
.header a{color:#fff;text-decoration:none;font-weight:700}
.container{max-width:700px;margin:26px auto;padding:0 20px}
.card{background:#fff;border-radius:14px;padding:18px;box-shadow:0 10px 30px rgba(0,0,0,.08)}
.input{width:100%;padding:12px;border:1px solid #cbd5e1;border-radius:10px;margin-top:8px}
.btn{padding:10px 14px;border-radius:10px;background:#0b5ed7;color:#fff;text-decoration:none;font-size:13px;font-weight:800;border:0;cursor:pointer}
.btn.alt{background:#475569}
.msg{padding:10px;border-radius:10px;margin:0 0 12px;font-size:14px}
.ok{background:#dcfce7;color:#166534}
.err{background:#fee2e2;color:#991b1b}
.small{color:#64748b;font-size:13px}
</style>
</head>
<body>
<div class="header">
  <div>IBIG EDUFORM — Mot de passe</div>
  <div><a href="dashboard.php">Dashboard</a> | <a href="logout.php">Déconnexion</a></div>
</div>

<div class="container">
  <div class="card">
    <?php if ($error): ?><div class="msg err"><?= e($error); ?></div><?php endif; ?>
    <?php if ($success): ?><div class="msg ok"><?= e($success); ?></div><?php endif; ?>

    <form method="post">
      <label class="small">Mot de passe actuel</label>
      <input class="input" type="password" name="current_password" required>

      <label class="small" style="display:block;margin-top:12px">Nouveau mot de passe</label>
      <input class="input" type="password" name="new_password" required>

      <label class="small" style="display:block;margin-top:12px">Confirmer</label>
      <input class="input" type="password" name="confirm_password" required>

      <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:14px">
        <button class="btn" type="submit">Mettre à jour</button>
        <a class="btn alt" href="profil.php">Mon profil</a>
        <a class="btn alt" href="candidatures.php">Mes candidatures</a>
      </div>
    </form>
  </div>
</div>
</body>
</html>
