<?php
declare(strict_types=1);
require_once __DIR__ . '/_auth.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $pwd = $_POST['password'] ?? '';

  if ($pwd === '') {
    $error = "Veuillez saisir votre mot de passe.";
  } elseif (!password_verify($pwd, $candidat['mot_de_passe'])) {
    $error = "Mot de passe incorrect.";
  } else {
    $pdo->prepare("UPDATE candidats SET deleted_at = NOW() WHERE id=? LIMIT 1")
        ->execute([$candidatId]);

    session_regenerate_id(true);
    unset($_SESSION['candidat_id'], $_SESSION['candidat_nom']);

    header('Location: login.php');
    exit;
  }
}
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Suppression compte – IBIG EDUFORM</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
body{margin:0;font-family:Inter,Arial;background:#f1f5f9}
.header{background:linear-gradient(90deg,#0b5ed7,#1d4ed8);color:#fff;padding:18px 26px;display:flex;justify-content:space-between;align-items:center}
.header a{color:#fff;text-decoration:none;font-weight:800}
.container{max-width:800px;margin:26px auto;padding:0 20px}
.card{background:#fff;border-radius:16px;padding:18px;box-shadow:0 10px 30px rgba(0,0,0,.08)}
.input{width:100%;padding:12px;border:1px solid #cbd5e1;border-radius:12px;margin-top:8px}
.btn{padding:10px 14px;border-radius:12px;background:#ef4444;color:#fff;border:0;font-weight:900;cursor:pointer}
.msg{padding:10px 12px;border-radius:12px;margin:0 0 12px}
.err{background:#fee2e2;color:#991b1b}
.small{color:#64748b;font-size:13px}
</style>
</head>
<body>
<div class="header">
  <div>IBIG EDUFORM — Suppression de compte</div>
  <div><a href="dashboard.php">Dashboard</a> | <a href="logout.php">Déconnexion</a></div>
</div>

<div class="container">
  <div class="card">
    <?php if ($error): ?><div class="msg err"><?= e($error); ?></div><?php endif; ?>

    <div class="small">
      La suppression est définitive dans votre espace. Vos candidatures restent en historique interne pour traçabilité.
    </div>

    <form method="post" onsubmit="return confirm('Confirmer la suppression du compte ?');">
      <label class="small" style="display:block;margin-top:12px">Mot de passe</label>
      <input class="input" type="password" name="password" required>
      <div style="margin-top:12px">
        <button class="btn" type="submit">Supprimer mon compte</button>
      </div>
    </form>
  </div>
</div>
</body>
</html>
