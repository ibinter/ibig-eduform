<?php
require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../auth/middleware.php';

Middleware::requireAuth();

$pageTitle  = 'Modifier un avis';
$activeMenu = 'avis';

$pdo = Database::connect();
$id  = (int)($_GET['id'] ?? 0);

/* =========================
   DONNÉE
========================= */
$stmt = $pdo->prepare("SELECT * FROM avis_clients WHERE id=?");
$stmt->execute([$id]);
$avis = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$avis) {
  exit('Avis introuvable.');
}

/* =========================
   UPDATE
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  csrf_check();

  $stmt = $pdo->prepare("
    UPDATE avis_clients
    SET nom=?, ville=?, pays=?, secteur=?, texte=?, statut=?
    WHERE id=?
  ");
  $stmt->execute([
    $_POST['nom'],
    $_POST['ville'],
    $_POST['pays'],
    $_POST['secteur'],
    $_POST['texte'],
    $_POST['statut'],
    $id
  ]);

  header('Location: index.php');
  exit;
}

ob_start();
?>

<style>
/* =========================================================
   AVIS CLIENTS — EDIT — ADMIN FORM
========================================================= */

.card h2{
  display:flex;
  align-items:center;
  gap:8px;
}

label{
  display:block;
  font-weight:600;
  margin-top:14px;
  margin-bottom:6px;
}

input,
textarea,
select{
  width:100%;
  padding:10px 12px;
  border-radius:10px;
  border:1px solid #e5e7eb;
  font-size:14px;
  background:#ffffff;
  color:#111827;
}

textarea{
  min-height:140px;
  resize:vertical;
}

input:focus,
textarea:focus,
select:focus{
  border-color:#2563eb;
  outline:none;
  box-shadow:0 0 0 2px rgba(37,99,235,.15);
}

/* =====================================================
   FIX BOUTONS — TEXTE INVISIBLE
===================================================== */
.btn{
  margin-top:18px;
  padding:10px 16px;
  border-radius:10px;
  border:1px solid #e5e7eb;
  background:#ffffff;
  color:#111827 !important;     /* TEXTE VISIBLE */
  font-weight:600;
  cursor:pointer;
  opacity:1 !important;
}

.btn:hover{
  background:#f3f4f6;
}

.btn-primary{
  background:#2563eb;
  border-color:#2563eb;
  color:#ffffff !important;
}
</style>

<div class="card">

  <h2>&#x270F; Modifier l’avis</h2>

  <form method="post">
    <?= csrf_field(); ?>

    <label>Nom</label>
    <input name="nom" value="<?= e($avis['nom']); ?>" required>

    <label>Ville</label>
    <input name="ville" value="<?= e($avis['ville']); ?>">

    <label>Pays</label>
    <input name="pays" value="<?= e($avis['pays']); ?>">

    <label>Secteur</label>
    <input name="secteur" value="<?= e($avis['secteur']); ?>">

    <label>Message</label>
    <textarea name="texte"><?= e($avis['texte']); ?></textarea>

    <label>Statut</label>
    <select name="statut">
      <option value="brouillon" <?= $avis['statut']==='brouillon'?'selected':''; ?>>Brouillon</option>
      <option value="publie" <?= $avis['statut']==='publie'?'selected':''; ?>>Publié</option>
    </select>

    <button class="btn btn-primary" type="submit">
      Mettre à jour
    </button>

  </form>

</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout/layout.php';