<?php
declare(strict_types=1);
require_once __DIR__ . '/_auth.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $notifyEmail = isset($_POST['notify_email']) ? 1 : 0;
  $notifyWA    = isset($_POST['notify_whatsapp']) ? 1 : 0;

  $pdo->prepare("
    UPDATE candidats
    SET notify_email=?, notify_whatsapp=?
    WHERE id=? LIMIT 1
  ")->execute([$notifyEmail, $notifyWA, $candidatId]);

  $candidat['notify_email'] = $notifyEmail;
  $candidat['notify_whatsapp'] = $notifyWA;
  $success = "Préférences mises à jour.";
}
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Préférences – IBIG EDUFORM</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
body{margin:0;font-family:Inter,Arial;background:#f1f5f9}
.header{background:linear-gradient(90deg,#0b5ed7,#1d4ed8);color:#fff;padding:18px 26px;display:flex;justify-content:space-between;align-items:center}
.header a{color:#fff;text-decoration:none;font-weight:800}
.container{max-width:800px;margin:26px auto;padding:0 20px}
.card{background:#fff;border-radius:16px;padding:18px;box-shadow:0 10px 30px rgba(0,0,0,.08)}
.btn{padding:10px 14px;border-radius:12px;background:#0b5ed7;color:#fff;border:0;font-weight:800;cursor:pointer}
.msg{padding:10px 12px;border-radius:12px;margin:0 0 12px}
.ok{background:#dcfce7;color:#166534}
.small{color:#64748b;font-size:13px}
.row{display:flex;align-items:center;gap:10px;margin-top:10px}
</style>
</head>
<body>
<div class="header">
  <div>IBIG EDUFORM — Préférences</div>
  <div><a href="dashboard.php">Dashboard</a> | <a href="logout.php">Déconnexion</a></div>
</div>

<div class="container">
  <div class="card">
    <?php if ($success): ?><div class="msg ok"><?= e($success); ?></div><?php endif; ?>

    <form method="post">
      <div class="row">
        <input type="checkbox" name="notify_email" id="ne" <?= ((int)$candidat['notify_email']===1)?'checked':''; ?>>
        <label for="ne">Recevoir les notifications par email</label>
      </div>

      <div class="row">
        <input type="checkbox" name="notify_whatsapp" id="nw" <?= ((int)$candidat['notify_whatsapp']===1)?'checked':''; ?>>
        <label for="nw">Recevoir les notifications par WhatsApp</label>
      </div>

      <p class="small" style="margin-top:10px">
        Ces réglages seront utilisés lors des changements de statut de vos candidatures.
      </p>

      <div style="margin-top:14px">
        <button class="btn" type="submit">Enregistrer</button>
      </div>
    </form>
  </div>
</div>
</body>
</html>
