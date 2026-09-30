<?php
require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../auth/middleware.php';

Middleware::requireAuth();

$pageTitle  = 'Éditer une page';
$activeMenu = 'pages';
$pdo = Database::connect();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM site_pages WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$p = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$p) { redirect('index.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $stmt = $pdo->prepare("
        UPDATE site_pages
        SET title = ?, meta_description = ?, content = ?, is_published = ?
        WHERE id = ?
    ");
    $stmt->execute([
        trim((string)($_POST['title'] ?? '')),
        trim((string)($_POST['meta_description'] ?? '')),
        (string)($_POST['content'] ?? ''),
        isset($_POST['is_published']) ? 1 : 0,
        $id,
    ]);
    redirect('index.php');
}

ob_start();
?>
<div class="page-head" style="margin-bottom:16px">
  <h1 style="margin:0">Éditer : <?= e($p['title']); ?></h1>
  <a href="index.php" class="btn btn-sm">← Retour</a>
</div>

<form method="post" style="max-width:900px;display:grid;gap:14px">
  <?= csrf_field(); ?>

  <label>Titre
    <input name="title" value="<?= e($p['title']); ?>" style="width:100%" required>
  </label>

  <label>Méta-description (SEO)
    <input name="meta_description" value="<?= e($p['meta_description']); ?>" style="width:100%" maxlength="300">
  </label>

  <label>Contenu (HTML autorisé : titres, paragraphes, listes, liens…)
    <textarea name="content" rows="20" style="width:100%;font-family:monospace;font-size:13px"><?= e($p['content']); ?></textarea>
  </label>

  <label style="display:flex;align-items:center;gap:8px">
    <input type="checkbox" name="is_published" <?= $p['is_published'] ? 'checked' : ''; ?>>
    Publier cette version (sinon le site affiche la page d’origine)
  </label>

  <div>
    <button class="btn btn-primary" type="submit">Enregistrer</button>
  </div>
</form>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout/layout.php';
