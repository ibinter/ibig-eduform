<?php
require_once __DIR__ . '/core/bootstrap.php';

$pageSlug = 'entreprises';
require __DIR__ . '/partials/cms_page.php';

$pageTitle = "Formation des entreprises, ONG & institutions – IBIG EDUFORM";
$ogDesc    = "IBIG EDUFORM forme vos équipes : programmes sur mesure, intra-entreprise, certifiants, en présentiel ou en ligne. Étude de besoin gratuite et solution alignée sur vos objectifs.";

/* ---------- Données dynamiques (tolérant) ---------- */
$domaines = [];
try {
    $pdo = Database::connect();
    $rows = $pdo->query("
        SELECT domaine, COUNT(*) n
        FROM formations
        WHERE statut='active' AND domaine IS NOT NULL AND domaine <> ''
        GROUP BY domaine ORDER BY n DESC, domaine ASC LIMIT 18
    ")->fetchAll(PDO::FETCH_ASSOC);
    if (is_array($rows)) { $domaines = $rows; }
} catch (Throwable $e) { $domaines = []; }

$avis = function_exists('get_approved_avis') ? get_approved_avis(6) : [];

/* ---------- Contacts (settings) ---------- */
$cEmail   = function_exists('setting') ? (string)setting('contact_email', 'formation@intermark-business.com') : 'formation@intermark-business.com';
$cPhones  = function_exists('setting') ? (string)setting('contact_phones', "+225 27 22 27 60 14\n+225 07 78 88 25 92") : "+225 27 22 27 60 14";
$firstPhone = trim(strtok($cPhones, "\n"));
$telHref  = '+' . preg_replace('/\D/', '', $firstPhone);
$waNum    = function_exists('whatsapp_admin_phone') ? whatsapp_admin_phone() : '2250778882592';
$waLink   = 'https://wa.me/' . $waNum . '?text=' . rawurlencode("Bonjour IBIG EDUFORM, je représente une organisation et souhaite un devis / une étude de besoin pour former nos équipes.");

include __DIR__ . '/partials/header.php';
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
/* =====================================================
   ENTREPRISES — VERSION CABINET PREMIUM (puissante)
===================================================== */
.ent{
  --navy:#0a1733;--navy2:#0b2552;--ink:#0e1530;--muted:#5b647c;
  --brand:#1f3fe0;--brand2:#3b82f6;--accent:#e8242c;--accent2:#ff5763;--gold:#f5a524;--ok:#0fae6e;
  --card:#fff;--bg:#f6f8fe;--line:rgba(14,21,48,.10);
  --r:22px;--r2:16px;--shadow:0 26px 64px rgba(14,21,48,.14);--shadow-sm:0 12px 30px rgba(14,21,48,.08);
  --font:Inter,"Plus Jakarta Sans",system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;
  background:var(--bg);color:var(--ink);font-family:var(--font);
}
.ent *{box-sizing:border-box}
.ent .wrap{max-width:1200px;margin:auto;padding:0 22px}
.ent a{text-decoration:none}
.ent .eyebrow{display:inline-flex;align-items:center;gap:8px;font-size:12px;font-weight:800;letter-spacing:.5px;
  text-transform:uppercase;color:#bcd2ff;border:1px solid rgba(188,210,255,.3);background:rgba(31,63,224,.18);
  padding:7px 14px;border-radius:999px}
.ent .eyebrow.dark{color:var(--brand);border-color:rgba(31,63,224,.22);background:rgba(31,63,224,.06)}
.ent .eyebrow .d{width:8px;height:8px;border-radius:50%;background:linear-gradient(135deg,var(--brand2),var(--accent))}

/* BUTTONS */
.ent .btn{display:inline-flex;align-items:center;justify-content:center;gap:9px;padding:15px 26px;border-radius:14px;
  font-size:15px;font-weight:800;border:1px solid var(--line);background:#fff;color:var(--ink);transition:.16s;cursor:pointer}
.ent .btn:hover{transform:translateY(-2px);box-shadow:var(--shadow-sm)}
.ent .btn.accent{background:linear-gradient(135deg,var(--accent),var(--accent2));color:#fff;border-color:transparent;box-shadow:0 18px 36px rgba(232,36,44,.32)}
.ent .btn.primary{background:linear-gradient(135deg,var(--brand),var(--brand2));color:#fff;border-color:transparent;box-shadow:0 18px 36px rgba(31,63,224,.32)}
.ent .btn.glass{background:rgba(255,255,255,.10);border-color:rgba(255,255,255,.28);color:#fff}
.ent .btn.wa{background:#fff;border-color:rgba(37,211,102,.4);color:#0c7a43}

/* HERO */
.ent .hero{position:relative;overflow:hidden;color:#fff;border-radius:28px;margin:34px auto 0;
  padding:74px 60px;box-shadow:0 35px 80px rgba(10,23,51,.45);
  background:
    linear-gradient(125deg,rgba(10,23,51,.96),rgba(11,37,82,.92)),
    url('/assets/images/hero/hero-2.jpg') center/cover no-repeat}
.ent .hero::before{content:"";position:absolute;right:-120px;top:-120px;width:420px;height:420px;border-radius:50%;
  background:radial-gradient(circle,rgba(31,63,224,.45),transparent 62%)}
.ent .hero::after{content:"";position:absolute;left:-100px;bottom:-140px;width:360px;height:360px;border-radius:50%;
  background:radial-gradient(circle,rgba(232,36,44,.28),transparent 60%)}
.ent .hero .inner{position:relative;z-index:1;max-width:880px}
.ent .hero h1{margin:18px 0 16px;font-size:clamp(30px,4.6vw,52px);line-height:1.08;letter-spacing:-1px;font-weight:800}
.ent .hero h1 .hl{color:#ffd9b0;background:linear-gradient(120deg,#ffd9b0,#ff9d6b);-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent}
.ent .hero p{font-size:17px;line-height:1.75;opacity:.94;max-width:64ch}
.ent .hero .cta{display:flex;flex-wrap:wrap;gap:12px;margin-top:26px}
.ent .hero .trust{display:flex;flex-wrap:wrap;gap:18px;margin-top:26px;font-size:13.5px;font-weight:600;opacity:.92}
.ent .hero .trust span{display:inline-flex;align-items:center;gap:8px}
.ent .hero .trust i{color:#7ee2b8}

/* STATS */
.ent .stats{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin:-44px auto 0;position:relative;z-index:5}
.ent .stat{background:var(--card);border:1px solid var(--line);border-radius:var(--r2);box-shadow:var(--shadow-sm);padding:22px;text-align:center}
.ent .stat b{display:block;font-size:30px;font-weight:800;letter-spacing:-.6px;
  background:linear-gradient(120deg,var(--brand),var(--accent));-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent}
.ent .stat span{font-size:13px;color:var(--muted);font-weight:600}

/* SECTIONS */
.ent section{padding:60px 0}
.ent .head{max-width:760px;margin:0 auto 34px;text-align:center}
.ent .head h2{margin:14px 0 10px;font-size:clamp(26px,3.4vw,38px);font-weight:800;letter-spacing:-.6px;color:var(--ink)}
.ent .head p{margin:0;color:var(--muted);font-size:16px;line-height:1.7}

/* VALUE GRID */
.ent .grid3{display:grid;grid-template-columns:repeat(3,1fr);gap:20px}
.ent .vcard{background:var(--card);border:1px solid var(--line);border-radius:var(--r);box-shadow:var(--shadow-sm);padding:28px;transition:.2s}
.ent .vcard:hover{transform:translateY(-6px);box-shadow:var(--shadow)}
.ent .vcard .ico{width:52px;height:52px;border-radius:15px;display:grid;place-items:center;font-size:22px;color:#fff;margin-bottom:16px;
  background:linear-gradient(135deg,var(--brand),var(--brand2))}
.ent .vcard:nth-child(2) .ico{background:linear-gradient(135deg,var(--accent),var(--accent2))}
.ent .vcard:nth-child(3) .ico{background:linear-gradient(135deg,var(--ok),#27d39a)}
.ent .vcard:nth-child(4) .ico{background:linear-gradient(135deg,var(--gold),#fbbf24)}
.ent .vcard:nth-child(5) .ico{background:linear-gradient(135deg,#7c3aed,#a78bfa)}
.ent .vcard:nth-child(6) .ico{background:linear-gradient(135deg,#0ea5e9,#38bdf8)}
.ent .vcard h3{margin:0 0 8px;font-size:18px;font-weight:800;color:var(--ink)}
.ent .vcard p{margin:0;color:var(--muted);font-size:14.5px;line-height:1.65}

/* TARGET CARDS */
.ent .tcard{position:relative;overflow:hidden;background:var(--card);border:1px solid var(--line);border-radius:var(--r);box-shadow:var(--shadow-sm);padding:30px;transition:.2s}
.ent .tcard:hover{transform:translateY(-6px);box-shadow:var(--shadow)}
.ent .tcard .badge{display:inline-flex;align-items:center;gap:8px;font-size:13px;font-weight:800;color:var(--brand);
  background:rgba(31,63,224,.08);border:1px solid rgba(31,63,224,.2);padding:8px 13px;border-radius:999px}
.ent .tcard h3{margin:16px 0 10px;font-size:20px;font-weight:800;color:var(--ink)}
.ent .tcard ul{margin:14px 0 0;padding:0;list-style:none;display:grid;gap:10px}
.ent .tcard li{display:flex;gap:10px;align-items:flex-start;color:var(--muted);font-size:14.5px;line-height:1.5}
.ent .tcard li i{color:var(--ok);margin-top:3px}

/* DOMAINES */
.ent .domgrid{display:flex;flex-wrap:wrap;gap:12px;justify-content:center}
.ent .dom{display:inline-flex;align-items:center;gap:10px;background:var(--card);border:1px solid var(--line);
  box-shadow:var(--shadow-sm);border-radius:14px;padding:14px 18px;font-weight:700;font-size:14.5px;color:var(--ink);transition:.18s}
.ent .dom:hover{transform:translateY(-3px);border-color:rgba(31,63,224,.4);color:var(--brand)}
.ent .dom i{color:var(--brand)}
.ent .dom .n{font-size:12px;font-weight:800;color:#fff;background:linear-gradient(135deg,var(--brand),var(--brand2));
  border-radius:999px;padding:2px 9px}

/* PROCESS TIMELINE */
.ent .steps{display:grid;grid-template-columns:repeat(4,1fr);gap:18px}
.ent .step{position:relative;background:var(--card);border:1px solid var(--line);border-radius:var(--r);box-shadow:var(--shadow-sm);padding:26px 22px}
.ent .step .n{position:absolute;top:-16px;left:22px;width:40px;height:40px;border-radius:12px;display:grid;place-items:center;
  font-weight:800;color:#fff;background:linear-gradient(135deg,var(--brand),var(--navy2));box-shadow:0 12px 24px rgba(31,63,224,.35)}
.ent .step i{font-size:22px;color:var(--brand);margin:10px 0 10px}
.ent .step h3{margin:0 0 6px;font-size:17px;font-weight:800}
.ent .step p{margin:0;color:var(--muted);font-size:14px;line-height:1.6}

/* SPLIT (modalités / livrables) */
.ent .split{display:grid;grid-template-columns:1fr 1fr;gap:20px}
.ent .panel{background:var(--card);border:1px solid var(--line);border-radius:var(--r);box-shadow:var(--shadow-sm);padding:30px}
.ent .panel h3{margin:0 0 16px;font-size:20px;font-weight:800;display:flex;align-items:center;gap:11px}
.ent .panel h3 i{color:var(--brand)}
.ent .panel ul{margin:0;padding:0;list-style:none;display:grid;gap:13px}
.ent .panel li{display:flex;gap:12px;align-items:flex-start;color:var(--muted);font-size:15px;line-height:1.55}
.ent .panel li i{color:var(--ok);margin-top:3px;font-size:15px}
.ent .panel.dark{background:linear-gradient(135deg,var(--navy),var(--navy2));color:#fff;border-color:transparent}
.ent .panel.dark h3 i,.ent .panel.dark li i{color:#7ee2b8}
.ent .panel.dark li{color:rgba(255,255,255,.9)}

/* TESTIMONIALS */
.ent .temo{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:20px}
.ent .tq{background:var(--card);border:1px solid var(--line);border-radius:var(--r);box-shadow:var(--shadow-sm);padding:26px}
.ent .tq .stars{color:var(--gold);font-size:13px;margin-bottom:10px}
.ent .tq p{margin:0 0 16px;font-size:14.5px;line-height:1.65;color:var(--ink)}
.ent .tq .who{display:flex;align-items:center;gap:12px}
.ent .tq .av{width:42px;height:42px;border-radius:50%;display:grid;place-items:center;font-weight:800;color:#fff;background:linear-gradient(135deg,var(--brand),var(--accent))}
.ent .tq .who b{display:block;font-size:14px}
.ent .tq .who small{font-size:12px;color:var(--muted)}

/* FAQ */
.ent .faq{max-width:860px;margin:auto;display:grid;gap:12px}
.ent details.qa{background:var(--card);border:1px solid var(--line);border-radius:var(--r2);box-shadow:var(--shadow-sm);padding:4px 20px}
.ent details.qa summary{list-style:none;cursor:pointer;padding:17px 0;font-weight:800;font-size:15.5px;display:flex;justify-content:space-between;gap:12px;align-items:center}
.ent details.qa summary::-webkit-details-marker{display:none}
.ent details.qa summary i{transition:.2s;color:var(--brand)}
.ent details.qa[open] summary i{transform:rotate(180deg)}
.ent details.qa .a{padding:0 0 17px;color:var(--muted);font-size:14.5px;line-height:1.7}

/* FINAL CTA */
.ent .final{position:relative;overflow:hidden;border-radius:28px;padding:64px 50px;text-align:center;color:#fff;
  background:linear-gradient(130deg,var(--navy),var(--navy2));box-shadow:0 35px 80px rgba(10,23,51,.4)}
.ent .final::before{content:"";position:absolute;right:-90px;top:-90px;width:340px;height:340px;border-radius:50%;background:radial-gradient(circle,rgba(31,63,224,.4),transparent 62%)}
.ent .final::after{content:"";position:absolute;left:-90px;bottom:-120px;width:320px;height:320px;border-radius:50%;background:radial-gradient(circle,rgba(232,36,44,.3),transparent 60%)}
.ent .final .in{position:relative;z-index:1}
.ent .final h2{margin:14px 0 12px;font-size:clamp(26px,3.6vw,40px);font-weight:800;letter-spacing:-.6px}
.ent .final p{margin:0 auto;max-width:62ch;font-size:16px;line-height:1.7;opacity:.92}
.ent .final .cta{display:flex;flex-wrap:wrap;gap:12px;justify-content:center;margin-top:26px}

@media(max-width:980px){
  .ent .grid3,.ent .steps{grid-template-columns:1fr 1fr}
  .ent .stats{grid-template-columns:repeat(2,1fr)}
  .ent .split{grid-template-columns:1fr}
}
@media(max-width:768px){
  .ent .hero{margin-top:18px;padding:54px 26px}
  .ent .grid3,.ent .steps{grid-template-columns:1fr}
  .ent .stats{margin-top:24px}
}
@media(max-width:430px){ .ent .stats{grid-template-columns:1fr} }
</style>

<div class="ent">
<main>

<!-- HERO -->
<section style="padding-top:0">
  <div class="wrap">
    <div class="hero">
      <div class="inner">
        <span class="eyebrow"><span class="d"></span> Solutions de formation pour organisations</span>
        <h1>Formez vos équipes.<br><span class="hl">Transformez votre performance.</span></h1>
        <p>
          IBIG EDUFORM conçoit et déploie des programmes <strong>sur mesure et certifiants</strong>
          pour les entreprises, ONG et institutions — en <strong>intra-entreprise</strong>, en présentiel
          ou en ligne. Des compétences concrètes, mesurables et directement applicables sur le terrain.
        </p>
        <div class="cta">
          <a class="btn accent" href="/besoin-formation.php"><i class="fa-solid fa-clipboard-check"></i> Étude de besoin gratuite</a>
          <a class="btn wa" href="<?= e($waLink) ?>" target="_blank" rel="noopener"><i class="fa-brands fa-whatsapp"></i> Parler à un conseiller</a>
        </div>
        <div class="trust">
          <span><i class="fa-solid fa-circle-check"></i> Programmes sur mesure</span>
          <span><i class="fa-solid fa-circle-check"></i> Présentiel ou en ligne</span>
          <span><i class="fa-solid fa-circle-check"></i> Formateurs experts en activité</span>
          <span><i class="fa-solid fa-circle-check"></i> Devis sous 48&nbsp;h</span>
        </div>
      </div>
    </div>

    <!-- STATS -->
    <div class="stats">
      <div class="stat"><b><?= e(setting('stat_apprenants','1 000+')) ?></b><span>Professionnels formés</span></div>
      <div class="stat"><b><?= e(setting('stat_satisfaction','+90%')) ?></b><span>Taux de satisfaction</span></div>
      <div class="stat"><b><?= e(setting('stat_certifiantes','100%')) ?></b><span>Programmes certifiants</span></div>
      <div class="stat"><b><?= e(setting('stat_experience','3 ans')) ?></b><span>d'expertise terrain</span></div>
    </div>
  </div>
</section>

<!-- POURQUOI -->
<section>
  <div class="wrap">
    <div class="head">
      <span class="eyebrow dark"><span class="d"></span> Pourquoi IBIG EDUFORM</span>
      <h2>Un partenaire de formation, pas un simple prestataire</h2>
      <p>Nous travaillons comme un cabinet : nous partons de vos objectifs, pas d'un catalogue figé.</p>
    </div>
    <div class="grid3">
      <div class="vcard"><div class="ico"><i class="fa-solid fa-sliders"></i></div>
        <h3>100&nbsp;% sur mesure</h3><p>Chaque programme est conçu à partir de votre contexte, votre secteur et le niveau réel de vos équipes.</p></div>
      <div class="vcard"><div class="ico"><i class="fa-solid fa-chart-line"></i></div>
        <h3>Orienté résultats</h3><p>Cas réels, outils métiers, mises en situation : vos collaborateurs appliquent dès le retour au poste.</p></div>
      <div class="vcard"><div class="ico"><i class="fa-solid fa-user-tie"></i></div>
        <h3>Formateurs experts</h3><p>Des praticiens en activité — DAF, consultants, auditeurs, DRH — qui transmettent du concret.</p></div>
      <div class="vcard"><div class="ico"><i class="fa-solid fa-certificate"></i></div>
        <h3>Certifiant & traçable</h3><p>Attestations et certificats vérifiables, rapport de formation et recommandations à l'appui.</p></div>
      <div class="vcard"><div class="ico"><i class="fa-solid fa-laptop-file"></i></div>
        <h3>Présentiel ou en ligne</h3><p>Dans vos locaux, chez nous à Abidjan ou en visioconférence interactive — selon vos contraintes.</p></div>
      <div class="vcard"><div class="ico"><i class="fa-solid fa-shield-halved"></i></div>
        <h3>Confidentialité & rigueur</h3><p>Cadre contractuel clair, confidentialité de vos données et engagement qualité IBIG EDUFORM.</p></div>
    </div>
  </div>
</section>

<!-- CIBLES -->
<section>
  <div class="wrap">
    <div class="head">
      <span class="eyebrow dark"><span class="d"></span> Pour qui ?</span>
      <h2>Des solutions pour chaque type d'organisation</h2>
    </div>
    <div class="grid3">
      <div class="tcard">
        <span class="badge"><i class="fa-solid fa-building"></i> Entreprises & PME</span>
        <h3>Performance & productivité</h3>
        <ul>
          <li><i class="fa-solid fa-check"></i> Montée en compétences des managers et équipes</li>
          <li><i class="fa-solid fa-check"></i> Finance, comptabilité, fiscalité, contrôle de gestion</li>
          <li><i class="fa-solid fa-check"></i> Vente, marketing, digital et relation client</li>
          <li><i class="fa-solid fa-check"></i> Conformité, qualité et organisation</li>
        </ul>
      </div>
      <div class="tcard">
        <span class="badge"><i class="fa-solid fa-hand-holding-heart"></i> ONG & Projets</span>
        <h3>Renforcement de capacités</h3>
        <ul>
          <li><i class="fa-solid fa-check"></i> Gestion de projets et cycle de projet</li>
          <li><i class="fa-solid fa-check"></i> Suivi-Évaluation (MEAL) et redevabilité</li>
          <li><i class="fa-solid fa-check"></i> Reporting bailleurs et gestion financière</li>
          <li><i class="fa-solid fa-check"></i> Gouvernance et passation de marchés</li>
        </ul>
      </div>
      <div class="tcard">
        <span class="badge"><i class="fa-solid fa-landmark"></i> Institutions publiques</span>
        <h3>Service public efficace</h3>
        <ul>
          <li><i class="fa-solid fa-check"></i> Formation des agents et cadres</li>
          <li><i class="fa-solid fa-check"></i> Gestion administrative et financière publique</li>
          <li><i class="fa-solid fa-check"></i> Management de services et de projets publics</li>
          <li><i class="fa-solid fa-check"></i> Digitalisation et outils de pilotage</li>
        </ul>
      </div>
    </div>
  </div>
</section>

<?php if ($domaines): ?>
<!-- DOMAINES (dynamiques) -->
<section>
  <div class="wrap">
    <div class="head">
      <span class="eyebrow dark"><span class="d"></span> Domaines d'expertise</span>
      <h2>Ce que nous formons</h2>
      <p>Nos formateurs interviennent sur l'ensemble des domaines de notre catalogue — et bien au-delà, sur mesure.</p>
    </div>
    <div class="domgrid">
      <?php foreach ($domaines as $d): ?>
        <span class="dom"><i class="fa-solid fa-graduation-cap"></i> <?= e($d['domaine']) ?>
          <span class="n"><?= (int)$d['n'] ?></span></span>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- APPROCHE -->
<section>
  <div class="wrap">
    <div class="head">
      <span class="eyebrow dark"><span class="d"></span> Notre méthode</span>
      <h2>Une approche cabinet, en 4 étapes</h2>
    </div>
    <div class="steps">
      <div class="step"><span class="n">1</span><i class="fa-solid fa-magnifying-glass-chart"></i>
        <h3>Diagnostic</h3><p>Analyse du contexte, des enjeux et du niveau réel des équipes.</p></div>
      <div class="step"><span class="n">2</span><i class="fa-solid fa-pen-ruler"></i>
        <h3>Conception sur mesure</h3><p>Programme aligné sur votre secteur, vos contraintes et vos priorités.</p></div>
      <div class="step"><span class="n">3</span><i class="fa-solid fa-chalkboard-user"></i>
        <h3>Formation terrain</h3><p>Cas réels, outils métiers, simulations et travaux pratiques.</p></div>
      <div class="step"><span class="n">4</span><i class="fa-solid fa-clipboard-list"></i>
        <h3>Évaluation & livrables</h3><p>Attestations, certificats, rapport de formation et recommandations.</p></div>
    </div>
  </div>
</section>

<!-- MODALITÉS / LIVRABLES -->
<section>
  <div class="wrap">
    <div class="head">
      <span class="eyebrow dark"><span class="d"></span> Concrètement</span>
      <h2>Comment ça se passe & ce que vous recevez</h2>
    </div>
    <div class="split">
      <div class="panel">
        <h3><i class="fa-solid fa-gears"></i> Formats & modalités</h3>
        <ul>
          <li><i class="fa-solid fa-check"></i> <strong>Intra-entreprise</strong> : sessions dédiées à vos équipes, dans vos locaux ou les nôtres</li>
          <li><i class="fa-solid fa-check"></i> <strong>Présentiel à Abidjan</strong> ou <strong>en ligne</strong> en visioconférence interactive</li>
          <li><i class="fa-solid fa-check"></i> <strong>Sur mesure</strong> : durée, contenu et planning adaptés à vos disponibilités</li>
          <li><i class="fa-solid fa-check"></i> <strong>Tarifs de groupe</strong> dégressifs selon le nombre de participants</li>
          <li><i class="fa-solid fa-check"></i> Conventions, factures et cadre contractuel professionnel</li>
        </ul>
      </div>
      <div class="panel dark">
        <h3><i class="fa-solid fa-box-open"></i> Vos livrables</h3>
        <ul>
          <li><i class="fa-solid fa-check"></i> Programme pédagogique détaillé (TDR)</li>
          <li><i class="fa-solid fa-check"></i> Supports de cours, outils et modèles réutilisables</li>
          <li><i class="fa-solid fa-check"></i> Attestations et <strong>certificats vérifiables</strong> par participant</li>
          <li><i class="fa-solid fa-check"></i> Rapport de formation et feuilles de présence</li>
          <li><i class="fa-solid fa-check"></i> Recommandations stratégiques post-formation</li>
        </ul>
      </div>
    </div>
  </div>
</section>

<?php if ($avis): ?>
<!-- TÉMOIGNAGES -->
<section>
  <div class="wrap">
    <div class="head">
      <span class="eyebrow dark"><span class="d"></span> Ils nous font confiance</span>
      <h2>Ce que disent les organisations & participants</h2>
    </div>
    <div class="temo">
      <?php foreach ($avis as $a):
        $nom = trim((string)($a['nom'] ?? '')); if ($nom==='') $nom='Organisation cliente';
        $sec = trim((string)($a['secteur'] ?? ''));
        $ini = strtoupper(mb_substr($nom,0,1,'UTF-8'));
      ?>
        <div class="tq">
          <div class="stars">★★★★★</div>
          <p><?= e((string)($a['texte'] ?? '')) ?></p>
          <div class="who">
            <span class="av"><?= e($ini) ?></span>
            <span><b><?= e($nom) ?></b><?php if ($sec!==''): ?><small><?= e($sec) ?></small><?php endif; ?></span>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- FAQ -->
<section>
  <div class="wrap">
    <div class="head">
      <span class="eyebrow dark"><span class="d"></span> FAQ Entreprises</span>
      <h2>Vos questions, nos réponses</h2>
    </div>
    <div class="faq">
      <details class="qa" open>
        <summary>Comment obtenir un devis ? <i class="fa-solid fa-chevron-down"></i></summary>
        <div class="a">Demandez une <strong>étude de besoin gratuite</strong> : nous analysons votre contexte et vous adressons
          une proposition chiffrée, généralement <strong>sous 48&nbsp;h</strong>.</div>
      </details>
      <details class="qa">
        <summary>Pouvez-vous former dans nos locaux ? <i class="fa-solid fa-chevron-down"></i></summary>
        <div class="a">Oui. Nous intervenons <strong>en intra-entreprise</strong> dans vos locaux, dans nos salles à Abidjan,
          ou <strong>en ligne</strong> — au choix.</div>
      </details>
      <details class="qa">
        <summary>Les programmes sont-ils adaptables à notre secteur ? <i class="fa-solid fa-chevron-down"></i></summary>
        <div class="a">Entièrement. Chaque programme est <strong>conçu sur mesure</strong> : contenus, cas pratiques, durée et
          planning sont ajustés à votre métier et à vos objectifs.</div>
      </details>
      <details class="qa">
        <summary>Combien de participants par session ? <i class="fa-solid fa-chevron-down"></i></summary>
        <div class="a">De quelques cadres à des groupes complets. Les <strong>tarifs de groupe</strong> sont dégressifs ;
          nous recommandons la taille idéale selon l'objectif pédagogique.</div>
      </details>
      <details class="qa">
        <summary>Délivrez-vous des certificats vérifiables ? <i class="fa-solid fa-chevron-down"></i></summary>
        <div class="a">Oui : attestations et <strong>certificats vérifiables en ligne</strong>, accompagnés d'un rapport de
          formation et de recommandations.</div>
      </details>
    </div>
  </div>
</section>

<!-- FINAL CTA -->
<section>
  <div class="wrap">
    <div class="final">
      <div class="in">
        <span class="eyebrow"><span class="d"></span> Passons à l'action</span>
        <h2>Discutons de votre besoin de formation</h2>
        <p>Notre équipe analyse votre contexte et vous propose une solution sur mesure, alignée sur vos objectifs
           stratégiques. Sans engagement.</p>
        <div class="cta">
          <a class="btn accent" href="/besoin-formation.php"><i class="fa-solid fa-clipboard-check"></i> Demander une étude de besoin</a>
          <a class="btn wa" href="<?= e($waLink) ?>" target="_blank" rel="noopener"><i class="fa-brands fa-whatsapp"></i> WhatsApp</a>
          <a class="btn glass" href="mailto:<?= e($cEmail) ?>"><i class="fa-solid fa-envelope"></i> <?= e($cEmail) ?></a>
          <?php if ($telHref !== '+'): ?><a class="btn glass" href="tel:<?= e($telHref) ?>"><i class="fa-solid fa-phone"></i> <?= e($firstPhone) ?></a><?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</section>

</main>
</div>

<?php include __DIR__ . '/partials/footer.php'; ?>
