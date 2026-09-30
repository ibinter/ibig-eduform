<?php
declare(strict_types=1);
require_once __DIR__ . '/_auth.php';

$error = '';
$success = '';

/* ============================
   TRAITEMENT FORMULAIRE
============================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $nom       = trim($_POST['nom'] ?? '');
  $prenoms   = trim($_POST['prenoms'] ?? '');
  $telephone = trim($_POST['telephone'] ?? '');

  if ($nom === '') {
    $error = "Le nom est obligatoire.";
  } elseif (strlen($nom) < 2) {
    $error = "Le nom est trop court.";
  } else {

    $up = $pdo->prepare("
      UPDATE candidats
      SET nom = ?, prenoms = ?, telephone = ?
      WHERE id = ?
      LIMIT 1
    ");
    $up->execute([$nom, $prenoms, $telephone, $candidatId]);

    $success = "Profil mis à jour avec succès.";

    // Synchronisation mémoire
    $candidat['nom'] = $nom;
    $candidat['prenoms'] = $prenoms;
    $candidat['telephone'] = $telephone;
    $_SESSION['candidat_nom'] = trim($nom . ' ' . $prenoms);
  }
}
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Mon profil – IBIG EDUFORM</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<style>
:root{
  --blue:#0b5ed7;
  --dark:#1e293b;
}
body{
  margin:0;
  font-family:Inter,Arial,Helvetica,sans-serif;
  background:#f1f5f9;
  color:#111827;
}
.header{
  background:linear-gradient(90deg,#0b5ed7,#1d4ed8);
  color:#fff;
  padding:18px 26px;
  display:flex;
  justify-content:space-between;
  align-items:center;
}
.header a{
  color:#fff;
  text-decoration:none;
  font-weight:800;
}
.container{
  max-width:900px;
  margin:28px auto;
  padding:0 20px;
}
.card{
  background:#fff;
  border-radius:18px;
  padding:22px;
  box-shadow:0 12px 30px rgba(0,0,0,.08);
}
h2{
  margin:0 0 14px;
  font-size:18px;
  color:var(--dark);
}
.input{
  width:100%;
  padding:13px 14px;
  border:1px solid #cbd5e1;
  border-radius:12px;
  margin-top:6px;
  font-size:14px;
}
.input:focus{
  outline:none;
  border-color:var(--blue);
}
label{
  display:block;
  margin-top:14px;
  font-size:13px;
  color:#64748b;
}
.actions{
  display:flex;
  gap:10px;
  flex-wrap:wrap;
  margin-top:18px;
}
.btn{
  padding:11px 16px;
  border-radius:12px;
  background:var(--blue);
  color:#fff;
  text-decoration:none;
  font-size:13px;
  font-weight:800;
  border:0;
  cursor:pointer;
  transition:.2s;
}
.btn.alt{
  background:#475569;
}
.btn:hover{
  transform:translateY(-1px);
  opacity:.95;
}
.msg{
  padding:12px 14px;
  border-radius:12px;
  margin-bottom:14px;
  font-size:14px;
  font-weight:600;
}
.ok{
  background:#dcfce7;
  color:#166534;
}
.err{
  background:#fee2e2;
  color:#991b1b;
}
.note{
  font-size:13px;
  color:#64748b;
  margin-top:6px;
}
.section{
  margin-top:26px;
  padding-top:18px;
  border-top:1px solid #e5e7eb;
}
.links{
  display:flex;
  gap:10px;
  flex-wrap:wrap;
  margin-top:10px;
}
</style>
</head>
<body>

<div class="header">
  <div>IBIG EDUFORM — Mon profil</div>
  <div>
    <a href="dashboard.php">Dashboard</a> |
    <a href="logout.php">Déconnexion</a>
  </div>
</div>

<div class="container">

  <div class="card">

    <h2>Informations personnelles</h2>

    <?php if ($error): ?>
      <div class="msg err"><?= e($error); ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
      <div class="msg ok"><?= e($success); ?></div>
    <?php endif; ?>

    <form method="post" autocomplete="off">

      <label>Nom</label>
      <input class="input" name="nom" value="<?= e($candidat['nom']); ?>" required>

      <label>Prénoms</label>
      <input class="input" name="prenoms" value="<?= e($candidat['prenoms'] ?? ''); ?>">

      <label>Email (non modifiable)</label>
      <input class="input" value="<?= e($candidat['email']); ?>" disabled>
      <div class="note">
        Pour modifier votre email, contactez l’administration IBIG EDUFORM.
      </div>

      <label>Téléphone</label>
      <input class="input" name="telephone" value="<?= e($candidat['telephone'] ?? ''); ?>">

      <div class="actions">
        <button class="btn" type="submit">Enregistrer</button>
        <a class="btn alt" href="password.php">Mot de passe</a>
        <a class="btn alt" href="candidatures.php">Mes candidatures</a>
      </div>

    </form>

    <!-- OPTIONS PREMIUM -->
    <div class="section">
      <h2>Options du compte</h2>
      <div class="note">Gestion avancée de votre profil candidat :</div>

      <div class="links">
        <a class="btn alt" href="avatar.php">Photo de profil</a>
        <a class="btn alt" href="cv.php">CV principal</a>
        <a class="btn alt" href="settings.php">Préférences</a>
        <a class="btn alt" href="activity.php">Historique connexions</a>
        <a class="btn alt" href="delete.php" style="background:#ef4444">Supprimer le compte</a>
      </div>
    </div>

  </div>

</div>

</body>
</html>
