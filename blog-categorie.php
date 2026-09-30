<?php
require_once __DIR__ . '/core/config.php';
require_once __DIR__ . '/core/database.php';
require_once __DIR__ . '/core/helpers.php';

$pdo = Database::connect();

/* ===============================
   SLUG CATÉGORIE
================================ */
$slug = trim($_GET['slug'] ?? '');

if ($slug === '') {
  http_response_code(404);
  exit('Catégorie introuvable.');
}

/* ===============================
   CATÉGORIE
================================ */
$stCat = $pdo->prepare("
  SELECT id, nom, slug
  FROM blog_categories
  WHERE slug = ?
  LIMIT 1
");
$stCat->execute([$slug]);
$category = $stCat->fetch(PDO::FETCH_ASSOC);

if (!$category) {
  http_response_code(404);
  exit('Catégorie inexistante.');
}

/* ===============================
   PAGINATION
================================ */
$perPage = 6;
$page    = max(1, (int)($_GET['page'] ?? 1));

$stTotal = $pdo->prepare("
  SELECT COUNT(*)
  FROM blog_articles
  WHERE category_id = ?
    AND statut = 'published'
");
$stTotal->execute([$category['id']]);
$total = (int)$stTotal->fetchColumn();

$pages = max(1, ceil($total / $perPage));
$page  = min($page, $pages);
$start = ($page - 1) * $perPage;

/* ===============================
   ARTICLES DE LA CATÉGORIE
================================ */
$st = $pdo->prepare("
  SELECT 
    id,
    titre,
    slug,
    contenu,
    created_at
  FROM blog_articles
  WHERE category_id = ?
    AND statut = 'published'
  ORDER BY created_at DESC
  LIMIT :start, :perPage
");
$st->bindValue(1, $category['id'], PDO::PARAM_INT);
$st->bindValue(':start', $start, PDO::PARAM_INT);
$st->bindValue(':perPage', $perPage, PDO::PARAM_INT);
$st->execute();
$articles = $st->fetchAll(PDO::FETCH_ASSOC);

/* ===============================
   META SEO
================================ */
$pageTitle = e($category['nom'])." – Blog IBIG EDUFORM";
include __DIR__ . '/partials/header.php';

/* ===============================
   EXTRAIT
================================ */
function excerpt(string $txt, int $len = 170): string {
  $plain = trim(strip_tags($txt));
  return mb_strlen($plain) > $len
    ? mb_substr($plain, 0, $len).'…'
    : $plain;
}
?>

<style>
/* ===============================
   BLOG CATÉGORIE — IBIG EDUFORM
================================ */
.blog-wrap{
  max-width:1200px;
  margin:90px auto;
  padding:0 20px 80px;
  font-family:Inter,system-ui,sans-serif;
}

.blog-hero{
  margin-bottom:50px;
}

.blog-hero h1{
  font-size:2.6rem;
  color:#0b3c5d;
  margin-bottom:10px;
}

.blog-hero p{
  max-width:780px;
  font-size:1.05rem;
  color:#475569;
}

/* GRID */
.blog-grid{
  display:grid;
  grid-template-columns:repeat(auto-fit,minmax(260px,1fr));
  gap:26px;
}

.blog-card{
  background:#ffffff;
  border-radius:20px;
  padding:26px;
  box-shadow:0 15px 40px rgba(15,23,42,.08);
}

.blog-card h3{
  font-size:1.25rem;
  color:#0b3c5d;
  margin-bottom:10px;
}

.blog-card p{
  font-size:.95rem;
  color:#475569;
  line-height:1.6;
}

.blog-card a{
  display:inline-block;
  margin-top:10px;
  font-weight:800;
  color:#2563eb;
  text-decoration:none;
}

/* PAGINATION */
.pagination{
  margin-top:50px;
  text-align:center;
}
.pagination a{
  display:inline-block;
  margin:0 4px;
  padding:8px 14px;
  background:#e2e8f0;
  color:#0f172a;
  border-radius:10px;
  font-weight:700;
  text-decoration:none;
}
.pagination a.active{
  background:#0b3c5d;
  color:#fff;
}
</style>

<main class="blog-wrap">

  <!-- FIL D’ARIANE -->
  <div class="breadcrumb" style="font-size:.85rem;color:#64748b;margin-bottom:20px">
    <a href="/" style="color:#2563eb;font-weight:700;text-decoration:none">Accueil</a>
    <span> › </span>
    <a href="/blog.php" style="color:#2563eb;font-weight:700;text-decoration:none">Blog</a>
    <span> › </span>
    <?= e($category['nom']); ?>
  </div>

  <!-- HERO -->
  <section class="blog-hero">
    <h1><?= e($category['nom']); ?></h1>
    <p>Articles liés à cette catégorie.</p>
  </section>

  <?php if(empty($articles)): ?>
    <p>Aucun article publié dans cette catégorie.</p>
  <?php else: ?>

    <section class="blog-grid">
      <?php foreach($articles as $a): ?>
        <article class="blog-card">
          <h3><?= e($a['titre']); ?></h3>
          <p><?= e(excerpt($a['contenu'])); ?></p>
          <a href="/blog/<?= urlencode($a['slug']); ?>">Lire →</a>
        </article>
      <?php endforeach; ?>
    </section>

  <?php endif; ?>

  <?php if($pages > 1): ?>
  <div class="pagination">
    <?php for($i=1;$i<=$pages;$i++): ?>
      <a href="?slug=<?= urlencode($slug); ?>&page=<?= $i ?>"
         class="<?= $i==$page?'active':'' ?>">
        <?= $i ?>
      </a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>

</main>

<?php include __DIR__ . '/partials/footer.php'; ?>
