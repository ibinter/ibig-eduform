<?php
require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../auth/middleware.php';

Middleware::requireAuth();

$pageTitle  = 'Modifier l’article';
$activeMenu = 'blog';

$pdo = Database::connect();
$id  = (int)($_GET['id'] ?? 0);

/* =========================
   ARTICLE
========================= */
$stmt = $pdo->prepare("SELECT * FROM blog_articles WHERE id=?");
$stmt->execute([$id]);
$article = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$article) {
  exit('Article introuvable.');
}

/* =========================
   UPDATE
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  csrf_check();

  $stmt = $pdo->prepare("
    UPDATE blog_articles
    SET titre=?, slug=?, contenu=?, statut=?, updated_at=NOW()
    WHERE id=?
  ");
  $stmt->execute([
    $_POST['titre'],
    $_POST['slug'],
    $_POST['contenu'],
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
   BLOG — EDIT — ADMIN FORM
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
  min-height:180px;
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

  <h2>&#x270F; Modifier l’article</h2>

  <form method="post">
    <?= csrf_field(); ?>

    <label>Titre</label>
    <input name="titre" value="<?= e($article['titre']); ?>" required>

    <label>Slug</label>
    <input name="slug" value="<?= e($article['slug']); ?>">

    <label>Contenu</label>
    <textarea name="contenu"><?= e($article['contenu']); ?></textarea>

    <label>Statut</label>
    <select name="statut">
      <option value="brouillon" <?= $article['statut']==='brouillon'?'selected':''; ?>>
        Brouillon
      </option>
      <option value="publie" <?= $article['statut']==='publie'?'selected':''; ?>>
        Publié
      </option>
    </select>

    <button class="btn btn-primary" type="submit">
      Mettre à jour
    </button>

  </form>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout/layout.php';