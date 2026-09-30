<?php
require_once __DIR__ . '/core/config.php';
require_once __DIR__ . '/core/database.php';
require_once __DIR__ . '/core/helpers.php';

$pdo = Database::connect();

/* ===============================
   RÉCUPÉRATION DU SLUG
================================ */
$slug = trim($_GET['slug'] ?? '');

if ($slug === '') {
  http_response_code(404);
  exit('Article introuvable.');
}

/* ===============================
   ARTICLE PUBLIC (PUBLISHED)
================================ */
$stmt = $pdo->prepare("
  SELECT 
    id,
    titre,
    slug,
    contenu,
    image,
    created_at,
    views
  FROM blog_articles
  WHERE slug = ?
    AND statut = 'published'
  LIMIT 1
");
$stmt->execute([$slug]);
$article = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$article) {
  http_response_code(404);
  exit('Article non trouvé ou non publié.');
}

/* ===============================
   INCRÉMENTATION DES VUES
================================ */
$pdo->prepare("
  UPDATE blog_articles 
  SET views = views + 1 
  WHERE id = ?
")->execute([$article['id']]);

/* ===============================
   META SEO
================================ */
$pageTitle = e($article['titre'])." – IBIG EDUFORM";
include __DIR__ . '/partials/header.php';
?>

<style>
/* ===============================
   ARTICLE PUBLIC — IBIG EDUFORM
================================ */
.article-wrap{
  max-width:900px;
  margin:90px auto;
  padding:0 20px 100px;
  font-family:Inter,system-ui,sans-serif;
}

/* BREADCRUMB */
.breadcrumb{
  font-size:.85rem;
  margin-bottom:20px;
  color:#64748b;
}
.breadcrumb a{
  color:#2563eb;
  text-decoration:none;
  font-weight:700;
}
.breadcrumb span{margin:0 6px}

/* TITRE */
.article-title{
  font-size:2.6rem;
  line-height:1.2;
  margin-bottom:14px;
  color:#0b3c5d;
}

/* META */
.article-meta{
  font-size:.9rem;
  color:#64748b;
  margin-bottom:30px;
}

/* IMAGE */
.article-cover img{
  width:100%;
  border-radius:22px;
  box-shadow:0 20px 50px rgba(2,6,23,.18);
  margin-bottom:34px;
}

/* CONTENU */
.article-content{
  font-size:1.05rem;
  line-height:1.9;
  color:#334155;
}
.article-content h2,
.article-content h3{
  margin:36px 0 14px;
  color:#0b3c5d;
}
.article-content ul{
  padding-left:20px;
  margin:16px 0;
}
.article-content li{margin-bottom:8px}

/* CTA */
.article-cta{
  margin-top:70px;
  background:linear-gradient(135deg,#0b3c5d,#1e40af);
  color:#fff;
  padding:50px;
  border-radius:28px;
  text-align:center;
  box-shadow:0 25px 60px rgba(2,6,23,.35);
}
.article-cta h3{
  font-size:1.9rem;
  margin-bottom:12px;
}
.article-cta p{
  max-width:700px;
  margin:0 auto 26px;
  opacity:.95;
}
.article-cta a{
  display:inline-block;
  background:#fff;
  color:#0b3c5d;
  font-weight:900;
  padding:16px 34px;
  border-radius:18px;
  text-decoration:none;
}

/* NAV */
.article-nav{
  margin-top:70px;
  display:flex;
  gap:20px;
}
.article-nav a{
  flex:1;
  background:#f1f5f9;
  padding:18px;
  border-radius:18px;
  text-decoration:none;
  font-weight:800;
  color:#0f172a;
  text-align:center;
}
.article-nav a:hover{background:#e2e8f0}

@media(max-width:700px){
  .article-title{font-size:2.1rem}
  .article-cta{padding:40px}
}
</style>

<main class="article-wrap">

  <!-- FIL D’ARIANE -->
  <div class="breadcrumb">
    <a href="/">Accueil</a>
    <span>›</span>
    <a href="/blog.php">Blog</a>
    <span>›</span>
    Article
  </div>

  <!-- TITRE -->
  <h1 class="article-title"><?= e($article['titre']); ?></h1>

  <!-- META -->
  <div class="article-meta">
    Publié le <?= date('d/m/Y', strtotime($article['created_at'])); ?>
    · <?= (int)$article['views'] + 1 ?> vues
  </div>

  <!-- IMAGE -->
  <?php if(!empty($article['image'])): ?>
    <div class="article-cover">
      <img src="<?= e($article['image']); ?>"
           alt="<?= e($article['titre']); ?>">
    </div>
  <?php endif; ?>

  <!-- CONTENU -->
  <div class="article-content">
    <?= $article['contenu']; ?>
  </div>

  <!-- CTA -->
  <section class="article-cta">
    <h3>Se former avec IBIG EDUFORM</h3>
    <p>
      Découvrez nos formations professionnelles certifiantes,
      orientées résultats et employabilité réelle.
    </p>
    <a href="/formations.php">Voir les formations</a>
  </section>

  <!-- NAV -->
  <div class="article-nav">
    <a href="/blog.php">← Retour au blog</a>
    <a href="/preinscription.php">Préinscription →</a>
  </div>

</main>

<?php include __DIR__ . '/partials/footer.php'; ?>
