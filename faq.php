<?php
require_once __DIR__ . '/core/config.php';

$pageSlug = 'faq';
require __DIR__ . '/partials/cms_page.php';

$pageTitle = "FAQ – Questions fréquentes | IBIG EDUFORM";
$ogDesc    = "Trouvez les réponses aux questions fréquentes sur les formations IBIG EDUFORM : inscriptions, certifications, financement, modalités, délais et accompagnement professionnel.";
include __DIR__ . '/partials/header.php';
?>

<style>
/* =========================================
   FAQ — IBIG EDUFORM
========================================= */
body{
  background:
    radial-gradient(circle at 15% 0%, #0b3c5d33, transparent 40%),
    radial-gradient(circle at 85% 20%, #f5a62322, transparent 35%),
    #020617;
  color:#e5e7eb;
  font-family:Inter,system-ui,-apple-system,sans-serif;
}

/* WRAP */
.faq-wrap{
  max-width:1100px;
  margin:100px auto;
  padding:0 22px 120px;
}

/* HERO */
.faq-hero{
  padding:48px 42px;
  border-radius:28px;
  background:linear-gradient(160deg,#0b3c5d,#020617 70%);
  border:1px solid rgba(255,255,255,.10);
  box-shadow:
    0 0 0 1px rgba(255,255,255,.04),
    0 28px 70px rgba(0,0,0,.55);
}

.faq-hero h1{
  margin:0;
  font-size:2.6rem;
  font-weight:900;
}

.faq-hero p{
  margin-top:14px;
  max-width:760px;
  font-size:1.05rem;
  color:#94a3b8;
}

/* LIST */
.faq-list{
  margin-top:60px;
  display:flex;
  flex-direction:column;
  gap:18px;
}

/* ITEM */
.faq-item{
  border-radius:22px;
  background:linear-gradient(180deg,rgba(255,255,255,.06),transparent 130%);
  border:1px solid rgba(255,255,255,.10);
  overflow:hidden;
  transition:.3s ease;
}

.faq-item.open{
  box-shadow:0 20px 50px rgba(0,0,0,.45);
}

/* QUESTION */
.faq-question{
  cursor:pointer;
  padding:22px 26px;
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:16px;
}

.faq-question h3{
  margin:0;
  font-size:1.05rem;
  font-weight:800;
}

/* ICON */
.faq-icon{
  width:26px;
  height:26px;
  border-radius:50%;
  background:#f5a623;
  color:#020617;
  font-weight:900;
  display:flex;
  align-items:center;
  justify-content:center;
  transition:.3s ease;
}

.faq-item.open .faq-icon{
  transform:rotate(45deg);
}

/* ANSWER */
.faq-answer{
  max-height:0;
  overflow:hidden;
  transition:max-height .4s ease;
}

.faq-answer-inner{
  padding:0 26px 26px;
  font-size:.98rem;
  line-height:1.8;
  color:#e5e7eb;
}

/* RESPONSIVE */
@media(max-width:720px){
  .faq-hero{
    padding:36px 26px;
  }
  .faq-hero h1{
    font-size:2.1rem;
  }
}
</style>

<?php
/* ── Catégories FAQ ── */
$faqCategories = [
  'Inscriptions'   => ['s\'inscrire','préinscription','place','annul','report','absence','liste'],
  'Paiements'      => ['paiement','frais','échelonn','rembours','mobile money','virement','moyen'],
  'Certifications' => ['certif','attestat','diplôme','certificat','reconn','numérique','physique'],
  'Modalités'      => ['ligne','présentiel','hybride','support','partager','international','calendrier','délai'],
  'Entreprises'    => ['entreprise','salarié','sur mesure','organisation','équipe','groupe'],
];
function faq_cat(string $q, array $cats): string {
  $ql = mb_strtolower($q, 'UTF-8');
  foreach ($cats as $cat => $kws) {
    foreach ($kws as $kw) { if (str_contains($ql, $kw)) return $cat; }
  }
  return 'Autres';
}

/* ── Lecture FAQ ── */
$faqs = [];
try {
    $pdoFaq = Database::connect();
    $rowsFaq = $pdoFaq->query("SELECT question, reponse FROM faq WHERE actif = 1 ORDER BY ordre ASC, id ASC")->fetchAll(PDO::FETCH_NUM);
    if ($rowsFaq) { $faqs = $rowsFaq; }
} catch (Throwable $e) { $faqs = []; }
if (!$faqs) { $faqs = require __DIR__ . '/core/faq_default.php'; }

/* ── Catégorisation ── */
$grouped = [];
foreach ($faqs as $faq) {
  $cat = faq_cat((string)$faq[0], $faqCategories);
  $grouped[$cat][] = $faq;
}
$cats = array_keys($grouped);

/* ── Schema.org FAQPage ── */
$schemaItems = [];
foreach ($faqs as $faq) {
  $schemaItems[] = [
    '@type' => 'Question',
    'name'  => strip_tags((string)$faq[0]),
    'acceptedAnswer' => ['@type'=>'Answer','text'=>strip_tags((string)$faq[1])],
  ];
}
$schema = json_encode(['@context'=>'https://schema.org','@type'=>'FAQPage','mainEntity'=>$schemaItems], JSON_UNESCAPED_UNICODE);
?>
<script type="application/ld+json"><?= $schema ?></script>

<style>
/* Filtre catégories */
.faq-cats{display:flex;flex-wrap:wrap;gap:10px;margin:36px 0 0}
.faq-cat-btn{padding:9px 18px;border-radius:999px;border:1px solid rgba(255,255,255,.15);background:transparent;color:#94a3b8;font-size:13px;font-weight:700;cursor:pointer;transition:.2s;font-family:inherit}
.faq-cat-btn:hover,.faq-cat-btn.active{background:#f5a623;color:#020617;border-color:#f5a623}
/* Recherche */
.faq-search{width:100%;margin-top:22px;padding:14px 18px;border-radius:14px;border:1px solid rgba(255,255,255,.15);background:rgba(255,255,255,.06);color:#e5e7eb;font-size:15px;font-family:inherit;outline:none;transition:.2s}
.faq-search:focus{border-color:#f5a623;background:rgba(245,166,35,.06)}
.faq-search::placeholder{color:#4b5563}
/* Groupe label */
.faq-group-label{font-size:11px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:#f5a623;margin:34px 0 14px;padding-left:4px}
/* item caché */
.faq-item.hidden{display:none}
/* contact CTA */
.faq-cta{margin-top:56px;padding:28px 30px;border-radius:20px;background:linear-gradient(135deg,rgba(31,63,224,.18),rgba(245,166,35,.10));border:1px solid rgba(255,255,255,.10);display:flex;align-items:center;gap:20px;flex-wrap:wrap}
.faq-cta-text h3{margin:0 0 4px;font-size:1.1rem;font-weight:800;color:#fff}
.faq-cta-text p{margin:0;font-size:.88rem;color:#94a3b8}
.faq-cta-btn{padding:12px 22px;border-radius:10px;font-weight:800;font-size:.9rem;text-decoration:none;white-space:nowrap}
</style>

<main class="faq-wrap">

  <section class="faq-hero">
    <h1>Foire aux questions</h1>
    <p>Réponses aux questions les plus fréquentes sur les formations, inscriptions, paiements et certifications IBIG EDUFORM.</p>

    <!-- Filtres catégories -->
    <div class="faq-cats">
      <button class="faq-cat-btn active" data-cat="tous">Toutes (<?= count($faqs) ?>)</button>
      <?php foreach ($cats as $cat): ?>
        <button class="faq-cat-btn" data-cat="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?> (<?= count($grouped[$cat]) ?>)</button>
      <?php endforeach; ?>
    </div>

    <!-- Recherche -->
    <input class="faq-search" type="search" placeholder="🔍  Rechercher une question…" id="faqSearch" autocomplete="off">
  </section>

  <section class="faq-list" id="faqList">
    <?php $n = 0; foreach ($grouped as $cat => $items): ?>
      <div class="faq-group-label faq-group" data-group="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></div>
      <?php foreach ($items as $faq): $n++; ?>
        <div class="faq-item" data-cat="<?= htmlspecialchars($cat) ?>" data-q="<?= htmlspecialchars(mb_strtolower((string)$faq[0],'UTF-8')) ?>">
          <div class="faq-question">
            <h3><?= htmlspecialchars((string)$faq[0]) ?></h3>
            <div class="faq-icon">+</div>
          </div>
          <div class="faq-answer">
            <div class="faq-answer-inner"><?= $faq[1] ?></div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endforeach; ?>
  </section>

  <!-- CTA contact -->
  <div class="faq-cta">
    <div style="font-size:2rem">💬</div>
    <div class="faq-cta-text" style="flex:1">
      <h3>Vous ne trouvez pas votre réponse ?</h3>
      <p>Notre équipe répond en moins de 24h du lundi au vendredi.</p>
    </div>
    <a href="https://wa.me/2250778882592?text=<?= rawurlencode('Bonjour IBIG EDUFORM, j\'ai une question :') ?>" class="faq-cta-btn" style="background:#25d366;color:#fff">📱 WhatsApp</a>
    <a href="/contact.php" class="faq-cta-btn" style="background:rgba(255,255,255,.1);color:#e5e7eb;border:1px solid rgba(255,255,255,.15)">✉️ Email</a>
  </div>

</main>

<script>
/* Accordéon */
document.querySelectorAll('.faq-question').forEach(q=>{
  q.addEventListener('click', ()=>{
    const item = q.parentElement;
    const answer = item.querySelector('.faq-answer');
    if(item.classList.contains('open')){
      item.classList.remove('open');
      answer.style.maxHeight = null;
    }else{
      document.querySelectorAll('.faq-item.open').forEach(o=>{
        o.classList.remove('open');
        o.querySelector('.faq-answer').style.maxHeight = null;
      });
      item.classList.add('open');
      answer.style.maxHeight = answer.scrollHeight + "px";
    }
  });
});

/* Filtre par catégorie */
document.querySelectorAll('.faq-cat-btn').forEach(btn=>{
  btn.addEventListener('click', ()=>{
    document.querySelectorAll('.faq-cat-btn').forEach(b=>b.classList.remove('active'));
    btn.classList.add('active');
    var cat = btn.dataset.cat;
    document.querySelectorAll('.faq-item').forEach(item=>{
      item.classList.toggle('hidden', cat !== 'tous' && item.dataset.cat !== cat);
    });
    document.querySelectorAll('.faq-group').forEach(g=>{
      g.style.display = (cat === 'tous' || g.dataset.group === cat) ? '' : 'none';
    });
    document.getElementById('faqSearch').value = '';
  });
});

/* Recherche */
document.getElementById('faqSearch').addEventListener('input', function(){
  var q = this.value.toLowerCase().trim();
  var count = 0;
  document.querySelectorAll('.faq-item').forEach(item=>{
    var match = !q || item.dataset.q.includes(q) || item.querySelector('.faq-answer-inner').textContent.toLowerCase().includes(q);
    item.classList.toggle('hidden', !match);
    if(match) count++;
  });
  document.querySelectorAll('.faq-group').forEach(g=>{ g.style.display = q ? 'none' : ''; });
  document.querySelectorAll('.faq-cat-btn').forEach(b=>b.classList.remove('active'));
  document.querySelector('.faq-cat-btn[data-cat="tous"]').classList.add('active');
});
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>
