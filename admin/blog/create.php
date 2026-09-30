<?php
require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../auth/middleware.php';

Middleware::requireAuth();

$pageTitle  = "Nouvel article";
$activeMenu = "blog";

$pdo = Database::connect();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();
  $titre   = trim($_POST['titre'] ?? '');
  $slug    = trim($_POST['slug'] ?? '');
  $contenu = trim($_POST['contenu'] ?? '');
  $statut  = $_POST['statut'] ?? 'brouillon';

  if ($titre === '' || $contenu === '') {
    $error = "Le titre et le contenu sont obligatoires.";
  } else {
    if ($slug === '') {
      $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $titre), '-'));
    }

    $stmt = $pdo->prepare("
      INSERT INTO blog_articles (titre, slug, contenu, statut, created_at)
      VALUES (?,?,?,?,NOW())
    ");
    $stmt->execute([$titre, $slug, $contenu, $statut]);

    header('Location: index.php');
    exit;
  }
}

ob_start();
?>
<div class="card">
  <h2>➕ Nouvel article</h2>

  <?php if($error): ?>
    <div class="alert alert-danger"><?= e($error); ?></div>
  <?php endif; ?>

  <form method="post">
    <?= csrf_field(); ?>

    <label>Titre *</label>
    <input name="titre" required>

    <label>Slug (URL)</label>
    <input name="slug" placeholder="auto-généré si vide">

    <label>Contenu *</label>
    <textarea name="contenu" required></textarea>

    <label>Statut</label>
    <select name="statut">
      <option value="brouillon">Brouillon</option>
      <option value="publie">Publié</option>
    </select>

    <button class="btn">Enregistrer</button>
  </form>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout/layout.php';
