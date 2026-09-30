<?php
require_once __DIR__ . '/core/config.php';
require_once __DIR__ . '/core/database.php';
require_once __DIR__ . '/core/helpers.php';

$pageTitle = "Publications & Articles – IBIG EDUFORM";
$ogDesc    = "Le blog IBIG EDUFORM : analyses, conseils et contenus professionnels sur la formation, les compétences métiers et l'employabilité.";

$pdo = Database::connect();

/* ── Catégories ── */
try {
  $allCats = $pdo->query("SELECT id, nom FROM blog_categories ORDER BY nom ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) { $allCats = []; }

$catSlug  = trim((string)($_GET['cat'] ?? ''));
$catId    = null;
$catLabel = '';
foreach ($allCats as $c) {
  if (strtolower(preg_replace('/\s+/','_',$c['nom'])) === strtolower($catSlug) || (string)$c['id'] === $catSlug) {
    $catId = (int)$c['id']; $catLabel = $c['nom']; break;
  }
}

/* ===============================
   PAGINATION
================================ */
$perPage = 9;
$page    = max(1, (int)($_GET['page'] ?? 1));

$whereExtra = $catId ? "AND a.category_id = :catId" : "";
$total = (int)$pdo->query("SELECT COUNT(*) FROM blog_articles a WHERE a.statut = 'published' $whereExtra" . ($catId ? " AND a.category_id=$catId" : ""))->fetchColumn();
$pages = max(1, (int)ceil($total / $perPage));
$page  = min($page, $pages);
$start = ($page - 1) * $perPage;

$sql = "
  SELECT a.id, a.titre, a.slug, a.contenu, a.created_at, c.nom AS categorie_nom
  FROM blog_articles a
  LEFT JOIN blog_categories c ON c.id = a.category_id
  WHERE a.statut = 'published'
  " . ($catId ? "AND a.category_id = :catId" : "") . "
  ORDER BY a.created_at DESC
  LIMIT :start, :perPage
";
$st = $pdo->prepare($sql);
if ($catId) $st->bindValue(':catId', $catId, PDO::PARAM_INT);
$st->bindValue(':start', $start, PDO::PARAM_INT);
$st->bindValue(':perPage', $perPage, PDO::PARAM_INT);
$st->execute();
$articles = $st->fetchAll(PDO::FETCH_ASSOC);

function excerpt(string $txt, int $len = 160): string {
  $plain = trim(strip_tags($txt));
  return mb_strlen($plain) > $len ? mb_substr($plain, 0, $len) . '…' : $plain;
}
$frMois = [1=>'janvier',2=>'février',3=>'mars',4=>'avril',5=>'mai',6=>'juin',7=>'juillet',8=>'août',9=>'septembre',10=>'octobre',11=>'novembre',12=>'décembre'];
$fmtDate = function (?string $d) use ($frMois): string {
  if (!$d) return '';
  $ts = strtotime($d); if (!$ts) return '';
  return date('j', $ts) . ' ' . ($frMois[(int)date('n', $ts)] ?? '') . ' ' . date('Y', $ts);
};
/* lecture estimée (≈ 200 mots/min) */
$readMin = function (string $txt): int {
  $w = max(1, str_word_count(strip_tags($txt)));
  return max(1, (int)ceil($w / 200));
};

include __DIR__ . '/partials/header.php';
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
/* =====================================================
   BLOG — REFONTE PREMIUM (namespacé .bl)
===================================================== */
.bl{
  --brand:#1f3fe0;--brand2:#4f6bff;--accent:#e8242c;--accent2:#ff5763;
  --navy:#0a1733;--navy2:#0b2552;--ink:#0e1530;--muted:#5b647c;--line:rgba(14,21,48,.10);
  --bg:#f6f8fe;--card:#fff;
  --r:22px;--r2:16px;--shadow:0 26px 64px rgba(14,21,48,.14);--shadow-sm:0 12px 30px rgba(14,21,48,.08);
  --font:Inter,"Plus Jakarta Sans",system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;
  background:var(--bg);color:var(--ink);font-family:var(--font);
}
.bl *{box-sizing:border-box}
.bl .wrap{max-width:1180px;margin:auto;padding:0 22px 80px}
.bl a{text-decoration:none}
.bl .eyebrow{display:inline-flex;align-items:center;gap:8px;font-size:12px;font-weight:800;letter-spacing:.5px;
  text-transform:uppercase;color:#bcd2ff;border:1px solid rgba(188,210,255,.3);background:rgba(31,63,224,.18);padding:7px 14px;border-radius:999px}
.bl .eyebrow .d{width:8px;height:8px;border-radius:50%;background:linear-gradient(135deg,var(--brand2),var(--accent))}

/* HERO */
.bl .hero{position:relative;overflow:hidden;color:#fff;border-radius:28px;margin:30px auto 0;max-width:1180px;
  padding:58px 56px;box-shadow:0 35px 80px rgba(10,23,51,.45);background:linear-gradient(125deg,var(--navy),var(--navy2))}
.bl .hero::before{content:"";position:absolute;right:-120px;top:-120px;width:420px;height:420px;border-radius:50%;background:radial-gradient(circle,rgba(31,63,224,.45),transparent 62%)}
.bl .hero::after{content:"";position:absolute;left:-100px;bottom:-140px;width:360px;height:360px;border-radius:50%;background:radial-gradient(circle,rgba(232,36,44,.28),transparent 60%)}
.bl .hero .in{position:relative;z-index:1;max-width:760px}
.bl .hero h1{margin:16px 0 12px;font-size:clamp(28px,4.4vw,46px);line-height:1.1;letter-spacing:-1px;font-weight:800}
.bl .hero p{font-size:16.5px;line-height:1.7;opacity:.94;max-width:64ch}

/* CHIP */
.bl .chip{display:inline-flex;align-items:center;gap:6px;font-size:11px;font-weight:800;padding:5px 11px;border-radius:999px;
  background:rgba(31,63,224,.08);color:var(--brand);border:1px solid rgba(31,63,224,.2);text-transform:uppercase;letter-spacing:.3px}
.bl .meta{display:flex;align-items:center;gap:14px;font-size:12.5px;color:var(--muted);font-weight:600}
.bl .meta i{color:var(--brand);margin-right:4px}

/* FEATURED */
.bl section{padding:40px 0 0}
.bl .featured{position:relative;overflow:hidden;border-radius:var(--r);box-shadow:var(--shadow);color:#fff;
  background:linear-gradient(135deg,var(--brand),#10227f);padding:44px;margin-top:8px}
.bl .featured::after{content:"";position:absolute;right:-80px;bottom:-100px;width:300px;height:300px;border-radius:50%;background:radial-gradient(circle,rgba(232,36,44,.35),transparent 60%)}
.bl .featured .in{position:relative;z-index:1;max-width:760px}
.bl .featured .fchip{display:inline-flex;align-items:center;gap:6px;font-size:11px;font-weight:800;padding:5px 12px;border-radius:999px;background:rgba(255,255,255,.16);border:1px solid rgba(255,255,255,.28);text-transform:uppercase;letter-spacing:.4px}
.bl .featured h2{margin:14px 0 10px;font-size:clamp(22px,3vw,32px);font-weight:800;line-height:1.18}
.bl .featured p{margin:0;opacity:.92;font-size:15.5px;line-height:1.7}
.bl .featured .fmeta{display:flex;gap:16px;margin:14px 0 18px;font-size:12.5px;opacity:.85;font-weight:600}
.bl .featured .rd{display:inline-flex;align-items:center;gap:8px;background:#fff;color:var(--brand);font-weight:800;padding:12px 18px;border-radius:12px;font-size:14px}

/* GRID */
.bl .head{display:flex;align-items:center;justify-content:space-between;gap:12px;margin:46px 0 18px}
.bl .head h2{margin:0;font-size:22px;font-weight:800}
.bl .grid{display:grid;grid-template-columns:repeat(3,1fr);gap:22px}
.bl .post{display:flex;flex-direction:column;background:var(--card);border:1px solid var(--line);border-radius:var(--r);box-shadow:var(--shadow-sm);overflow:hidden;transition:.2s}
.bl .post:hover{transform:translateY(-6px);box-shadow:var(--shadow)}
.bl .post .band{height:90px;position:relative}
.bl .post .band::after{content:"";position:absolute;inset:0;background:radial-gradient(circle at 30% 30%,rgba(255,255,255,.22),transparent 55%)}
.bl .post .band i{position:absolute;right:16px;bottom:-18px;width:42px;height:42px;border-radius:12px;display:grid;place-items:center;color:#fff;background:rgba(0,0,0,.18);font-size:16px;z-index:1}
.bl .post .body{padding:20px 20px 22px;display:flex;flex-direction:column;flex:1}
.bl .post h3{margin:12px 0 8px;font-size:17px;font-weight:800;line-height:1.3;color:var(--ink)}
.bl .post p{margin:0 0 14px;color:var(--muted);font-size:14px;line-height:1.6;flex:1}
.bl .post .rd{display:inline-flex;align-items:center;gap:7px;font-size:13px;font-weight:800;color:var(--brand)}
.bl .post .rd:hover{gap:10px}

.bl .empty{text-align:center;padding:50px 20px;border:1px dashed var(--line);border-radius:var(--r);background:#fff;color:var(--muted);margin-top:20px}

/* PAGINATION */
.bl .pager{display:flex;gap:6px;justify-content:center;align-items:center;flex-wrap:wrap;margin:40px 0 0}
.bl .pager a,.bl .pager span{min-width:40px;height:40px;padding:0 12px;border-radius:11px;display:inline-flex;align-items:center;justify-content:center;font-size:14px;font-weight:700;text-decoration:none;border:1px solid var(--line);color:#334155;background:#fff}
.bl .pager a:hover{border-color:var(--brand);color:var(--brand)}
.bl .pager .active{background:linear-gradient(135deg,var(--brand),var(--brand2));color:#fff;border-color:transparent}

/* FILTRES CATÉGORIES */
.bl .cat-bar{display:flex;flex-wrap:wrap;gap:10px;margin:28px 0 0;align-items:center}
.bl .cat-btn{padding:9px 18px;border-radius:999px;border:1px solid var(--line);background:#fff;color:var(--muted);font-size:13px;font-weight:700;cursor:pointer;text-decoration:none;transition:.2s;font-family:var(--font)}
.bl .cat-btn:hover{border-color:var(--brand);color:var(--brand)}
.bl .cat-btn.active{background:linear-gradient(135deg,var(--brand),var(--brand2));color:#fff;border-color:transparent;box-shadow:0 4px 14px rgba(31,63,224,.28)}

@media(max-width:980px){.bl .grid{grid-template-columns:1fr 1fr}}
@media(max-width:680px){.bl .grid{grid-template-columns:1fr}.bl .hero,.bl .featured{padding:40px 24px}.bl .hero{margin-top:18px}}
</style>

<?php
/* couleur de bandeau dérivée du titre (variété visuelle, sans image) */
$bandStyle = function (string $seedTxt): string {
  $h = abs(crc32($seedTxt)) % 360;
  $h2 = ($h + 28) % 360;
  return "background:linear-gradient(135deg,hsl($h,68%,46%),hsl($h2,62%,30%))";
};
?>

<div class="bl">
<main class="wrap" style="padding-top:0">

  <!-- HERO -->
  <section style="padding-top:0">
    <div class="hero">
      <div class="in">
        <span class="eyebrow"><span class="d"></span> Blog &amp; ressources</span>
        <h1>Publications &amp; articles</h1>
        <p>Analyses, conseils et contenus professionnels autour de la formation, des compétences métiers et de l'employabilité.</p>
      </div>
    </div>

    <?php if (!empty($allCats)): ?>
    <div class="cat-bar">
      <a href="/blog.php" class="cat-btn <?= !$catId ? 'active' : '' ?>">Tous les articles</a>
      <?php foreach ($allCats as $c):
        $slug = strtolower(preg_replace('/\s+/','_',$c['nom']));
      ?>
        <a href="/blog.php?cat=<?= urlencode($slug) ?>" class="cat-btn <?= $catId === (int)$c['id'] ? 'active' : '' ?>"><?= e($c['nom']) ?></a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </section>

  <?php if (empty($articles)): ?>
    <div class="empty">
      <p style="font-size:17px;font-weight:700;margin:0 0 6px">Aucun article publié pour le moment.</p>
      <p style="margin:0">Revenez bientôt — de nouveaux contenus arrivent régulièrement.</p>
    </div>
  <?php else: ?>

    <?php
      $featured = ($page === 1) ? $articles[0] : null;
      $others   = ($page === 1) ? array_slice($articles, 1) : $articles;
    ?>

    <?php if ($featured): ?>
      <section>
        <div class="featured">
          <div class="in">
            <span class="fchip"><i class="fa-solid fa-star"></i> À la une<?php if (!empty($featured['categorie_nom'])): ?> · <?= e($featured['categorie_nom']); ?><?php endif; ?></span>
            <h2><?= e($featured['titre']); ?></h2>
            <div class="fmeta">
              <span><i class="fa-solid fa-calendar-day"></i> <?= e($fmtDate($featured['created_at'])); ?></span>
              <span><i class="fa-solid fa-clock"></i> <?= (int)$readMin($featured['contenu']); ?> min de lecture</span>
            </div>
            <p><?= e(excerpt($featured['contenu'], 240)); ?></p>
            <div style="margin-top:18px"><a class="rd" href="/blog/<?= urlencode($featured['slug']); ?>">Lire l'article <i class="fa-solid fa-arrow-right"></i></a></div>
          </div>
        </div>
      </section>
    <?php endif; ?>

    <?php if ($others): ?>
    <section>
      <div class="head">
        <h2><?= $featured ? 'Derniers articles' : 'Tous les articles'; ?></h2>
      </div>
      <div class="grid">
        <?php foreach ($others as $a): ?>
          <article class="post">
            <div class="band" style="<?= $bandStyle((string)$a['titre']); ?>">
              <i class="fa-solid fa-newspaper"></i>
            </div>
            <div class="body">
              <?php if (!empty($a['categorie_nom'])): ?><span class="chip" style="align-self:flex-start"><?= e($a['categorie_nom']); ?></span><?php endif; ?>
              <h3><?= e($a['titre']); ?></h3>
              <p><?= e(excerpt($a['contenu'])); ?></p>
              <div class="meta" style="margin-bottom:12px">
                <span><i class="fa-solid fa-calendar-day"></i><?= e($fmtDate($a['created_at'])); ?></span>
                <span><i class="fa-solid fa-clock"></i><?= (int)$readMin($a['contenu']); ?> min</span>
              </div>
              <a class="rd" href="/blog/<?= urlencode($a['slug']); ?>">Lire <i class="fa-solid fa-arrow-right"></i></a>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>

  <?php endif; ?>

  <?php if ($pages > 1):
    $pagerBase = $catSlug ? ('?cat='.urlencode($catSlug).'&page=') : '?page=';
  ?>
    <div class="pager">
      <?php if ($page > 1): ?><a href="<?= $pagerBase.($page-1) ?>">‹</a><?php endif; ?>
      <?php for ($i=1;$i<=$pages;$i++): ?>
        <a href="<?= $pagerBase.$i ?>" class="<?= $i==$page?'active':''; ?>"><?= $i; ?></a>
      <?php endfor; ?>
      <?php if ($page < $pages): ?><a href="<?= $pagerBase.($page+1) ?>">›</a><?php endif; ?>
    </div>
  <?php endif; ?>

</main>
</div>

<?php include __DIR__ . '/partials/footer.php'; ?>
