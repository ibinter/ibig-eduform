<?php
require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../auth/middleware.php';

Middleware::requireAuth();

$pageTitle = "Nouvel avis client";
$activeMenu = "avis";

$pdo = Database::connect();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();
  $nom     = trim($_POST['nom'] ?? '');
  $ville   = trim($_POST['ville'] ?? '');
  $pays    = trim($_POST['pays'] ?? '');
  $secteur = trim($_POST['secteur'] ?? '');
  $texte   = trim($_POST['texte'] ?? '');
  $statut  = $_POST['statut'] ?? 'brouillon';

  if ($nom === '' || $texte === '') {
    $error = "Le nom et le message sont obligatoires.";
  } else {
    $stmt = $pdo->prepare("
      INSERT INTO avis_clients (nom, ville, pays, secteur, texte, statut)
      VALUES (?,?,?,?,?,?)
    ");
    $stmt->execute([$nom,$ville,$pays,$secteur,$texte,$statut]);
    header('Location: index.php');
    exit;
  }
}

ob_start();
?>
<div class="card">
  <h2>➕ Ajouter un avis client</h2>

  <?php if($error): ?>
    <div class="alert alert-danger"><?= e($error); ?></div>
  <?php endif; ?>

  <form method="post">
    <?= csrf_field(); ?>

    <label>Nom du client *</label>
    <input name="nom" required>

    <label>Ville</label>
    <input name="ville">

    <label>Pays</label>
    <input name="pays" value="Côte d’Ivoire">

    <label>Secteur</label>
    <input name="secteur">

    <label>Message *</label>
    <textarea name="texte" required></textarea>

    <label>Statut</label>
    <select name="statut">
      <option value="brouillon">Brouillon</option>
      <option value="publie">Publié</option>
    </select>

    <button class="btn" type="submit">Enregistrer</button>
  </form>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout/layout.php';
