<?php
require_once __DIR__ . '/core/bootstrap.php';

$pageSlug = 'a-propos';
require __DIR__ . '/partials/cms_page.php';

$pageTitle    = "À propos – IBIG EDUFORM | Institut de formation professionnelle en Afrique";
$ogDesc       = "IBIG EDUFORM est un institut de formation professionnelle certifiante fondé par le groupe INTERMARK BUSINESS. Présent dans 17 pays OHADA, nous formons plus de 1 000 professionnels par an en présentiel, en ligne et intra-entreprise.";
$pageKeywords = "IBIG EDUFORM, institut formation Abidjan, INTERMARK BUSINESS GROUP, formation certifiante Afrique OHADA, qui sommes-nous, formation professionnelle Côte d'Ivoire";

$extraHead = <<<'JSONLD'
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "EducationalOrganization",
  "@id": "https://ibig-eduform.com/#organization",
  "name": "IBIG EDUFORM",
  "legalName": "INTERMARK BUSINESS INTERNATIONAL GROUP SARL",
  "url": "https://ibig-eduform.com/",
  "logo": "https://ibig-eduform.com/assets/images/logo.png",
  "foundingDate": "2023",
  "description": "Institut de formation professionnelle et de certifications orientées impact, performance et employabilité. Actif dans 17 pays membres de l'espace OHADA.",
  "address": {
    "@type": "PostalAddress",
    "addressLocality": "Abidjan",
    "addressCountry": "CI"
  },
  "telephone": "+2250778882592",
  "email": "formation@ibig-eduform.com",
  "numberOfEmployees": {"@type": "QuantitativeValue", "minValue": 10},
  "areaServed": "Espace OHADA — 17 pays d'Afrique francophone"
}
</script>
JSONLD;

$st = static function (string $k, string $d): string {
    return function_exists('setting') ? (string)setting($k, $d) : $d;
};
$sti = static function (string $k, int $d): int {
    return function_exists('setting_int') ? setting_int($k, $d) : $d;
};
$stApprenants = $st('stat_apprenants', '1 000+');
$stSatisf     = $st('stat_satisfaction', '+90%');
$stExperience = $st('stat_experience', '3 ans');
$stInseres    = $sti('home_inseres', 250);
$stDomaines   = $sti('home_domaines', 26);
$stPratique   = $sti('home_pratique', 80);

include __DIR__ . '/partials/header.php';
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800;900&family=Inter:wght@400;500;600;700&display=swap">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>
/* ── RESET & TOKENS ── */
.ap{
  --blue:#1a3fd0;--blue2:#4f6bff;--red:#e8242c;--red2:#ff5763;
  --navy:#08122b;--navy2:#0c1e4a;--ink:#0c1528;--muted:#5b647c;
  --line:rgba(12,21,40,.09);--bg:#f4f6fc;--card:#fff;
  --gold:#f5a623;--ok:#0fae6e;
  --r:20px;--r2:14px;
  --sh:0 20px 56px rgba(8,18,43,.12);--sh-sm:0 8px 24px rgba(8,18,43,.08);
  font-family:'Plus Jakarta Sans',Inter,system-ui,sans-serif;
  background:var(--bg);color:var(--ink);
}
.ap *{box-sizing:border-box;margin:0;padding:0}
.ap a{text-decoration:none;color:inherit}
.ap img{display:block;max-width:100%}

/* ── LAYOUT ── */
.ap .wrap{max-width:1160px;margin:0 auto;padding:0 24px}
.ap section{padding:72px 0}
.ap .alt-bg{background:#fff}

/* ── TYPOGRAPHY ── */
.ap .eyebrow{
  display:inline-flex;align-items:center;gap:7px;
  font-size:11px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;
  color:var(--blue);background:rgba(26,63,208,.07);border:1px solid rgba(26,63,208,.18);
  padding:5px 14px;border-radius:100px;
}
.ap .eyebrow.light{color:#c8d8ff;background:rgba(255,255,255,.12);border-color:rgba(255,255,255,.25)}
.ap .sec-title{font-size:clamp(24px,3.2vw,36px);font-weight:800;letter-spacing:-.5px;line-height:1.15;color:var(--ink)}
.ap .sec-sub{color:var(--muted);font-size:15.5px;line-height:1.75;margin-top:12px}
.ap .center{text-align:center}
.ap .center .sec-title,.ap .center .sec-sub{max-width:660px;margin-left:auto;margin-right:auto}
.ap .center .sec-sub{margin-top:10px}

/* ── BUTTONS ── */
.ap .btn{display:inline-flex;align-items:center;gap:8px;padding:13px 22px;border-radius:12px;font-size:14.5px;font-weight:700;border:1px solid var(--line);background:var(--card);color:var(--ink);transition:.18s;cursor:pointer}
.ap .btn:hover{transform:translateY(-2px);box-shadow:var(--sh-sm)}
.ap .btn-accent{background:linear-gradient(135deg,var(--red),var(--red2));color:#fff;border-color:transparent;box-shadow:0 12px 28px rgba(232,36,44,.28)}
.ap .btn-accent:hover{filter:brightness(1.08)}
.ap .btn-blue{background:linear-gradient(135deg,var(--blue),var(--blue2));color:#fff;border-color:transparent;box-shadow:0 12px 28px rgba(26,63,208,.28)}
.ap .btn-glass{background:rgba(255,255,255,.13);border-color:rgba(255,255,255,.3);color:#fff}
.ap .btn-glass:hover{background:rgba(255,255,255,.22)}
.ap .cta-row{display:flex;flex-wrap:wrap;gap:12px;margin-top:28px}
.ap .cta-row.center{justify-content:center}

/* ════════════════════════════════════
   STICKY NAV SECONDAIRE
════════════════════════════════════ */
.ap-nav{
  position:sticky;top:0;z-index:200;
  background:rgba(255,255,255,.92);backdrop-filter:blur(14px);
  border-bottom:1px solid var(--line);
}
.ap-nav-inner{
  max-width:1160px;margin:0 auto;padding:0 24px;
  display:flex;align-items:center;gap:2px;overflow-x:auto;
  scrollbar-width:none;
}
.ap-nav-inner::-webkit-scrollbar{display:none}
.ap-nav-inner a{
  flex-shrink:0;font-size:13px;font-weight:700;color:var(--muted);
  padding:14px 16px;border-bottom:2px solid transparent;white-space:nowrap;transition:.15s;
}
.ap-nav-inner a:hover,.ap-nav-inner a.active{color:var(--blue);border-bottom-color:var(--blue)}

/* ════════════════════════════════════
   HERO
════════════════════════════════════ */
.ap-hero{
  position:relative;overflow:hidden;
  background:linear-gradient(130deg,var(--navy) 0%,var(--navy2) 55%,#1a2e6e 100%);
  color:#fff;margin-top:28px;border-radius:24px;
  padding:80px 64px 60px;
  box-shadow:0 40px 100px rgba(8,18,43,.55);
}
.ap-hero::before{
  content:'';position:absolute;right:-80px;top:-80px;
  width:480px;height:480px;border-radius:50%;
  background:radial-gradient(circle,rgba(26,63,208,.5),transparent 64%);
}
.ap-hero::after{
  content:'';position:absolute;left:-60px;bottom:-100px;
  width:320px;height:320px;border-radius:50%;
  background:radial-gradient(circle,rgba(232,36,44,.32),transparent 62%);
}
.ap-hero-inner{position:relative;z-index:1;display:grid;grid-template-columns:1fr auto;gap:40px;align-items:center}
.ap-hero-text .eyebrow{margin-bottom:20px}
.ap-hero h1{font-size:clamp(28px,4.2vw,52px);font-weight:900;line-height:1.08;letter-spacing:-1.5px;margin:0 0 18px}
.ap-hero h1 em{font-style:normal;background:linear-gradient(120deg,#ffcf86,#ff9055);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.ap-hero p{font-size:16.5px;line-height:1.78;color:rgba(255,255,255,.88);max-width:58ch}

.ap-hero-badge{
  display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;
  background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.15);
  border-radius:20px;padding:28px 32px;min-width:160px;flex-shrink:0;
}
.ap-hero-badge-num{font-size:3rem;font-weight:900;line-height:1;color:#f5a623}
.ap-hero-badge-sub{font-size:12px;font-weight:700;color:rgba(255,255,255,.6);margin-top:6px;text-transform:uppercase;letter-spacing:.06em}

/* ════════════════════════════════════
   STATS BAND
════════════════════════════════════ */
.ap-stats-band{
  display:grid;grid-template-columns:repeat(4,1fr);gap:1px;
  background:var(--line);
  border:1px solid var(--line);border-radius:var(--r);overflow:hidden;
  margin-top:32px;box-shadow:var(--sh-sm);
}
.ap-stat{
  background:var(--card);padding:28px 24px;text-align:center;
}
.ap-stat-num{
  font-size:2.4rem;font-weight:900;letter-spacing:-.5px;line-height:1;
  background:linear-gradient(135deg,var(--blue),var(--red));
  -webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;
}
.ap-stat-lbl{font-size:12.5px;color:var(--muted);font-weight:700;margin-top:6px;text-transform:uppercase;letter-spacing:.04em}

/* ════════════════════════════════════
   QUI SOMMES-NOUS — Split layout
════════════════════════════════════ */
.ap-split{display:grid;grid-template-columns:1fr 1fr;gap:48px;align-items:center}
.ap-split-img{
  border-radius:var(--r);overflow:hidden;
  background:linear-gradient(135deg,var(--navy),var(--navy2));
  min-height:340px;display:flex;flex-direction:column;justify-content:flex-end;
  padding:36px;color:#fff;position:relative;
}
.ap-split-img::before{
  content:'';position:absolute;inset:0;
  background:linear-gradient(135deg,rgba(26,63,208,.35),rgba(232,36,44,.2));
}
.ap-split-img-inner{position:relative;z-index:1}
.ap-split-img-icon{font-size:3.5rem;margin-bottom:14px;line-height:1}
.ap-split-img h3{font-size:1.5rem;font-weight:800;margin-bottom:8px}
.ap-split-img p{font-size:14.5px;line-height:1.7;color:rgba(255,255,255,.85)}

.ap-pillars{display:flex;flex-direction:column;gap:18px}
.ap-pillar{
  display:flex;gap:16px;align-items:flex-start;
  background:var(--card);border:1px solid var(--line);
  border-radius:var(--r2);padding:20px 22px;
  transition:.2s;
}
.ap-pillar:hover{border-color:rgba(26,63,208,.3);box-shadow:var(--sh-sm)}
.ap-pillar-ico{
  width:46px;height:46px;border-radius:12px;flex-shrink:0;
  display:grid;place-items:center;font-size:19px;color:#fff;
}
.ap-pillar:nth-child(1) .ap-pillar-ico{background:linear-gradient(135deg,var(--blue),var(--blue2))}
.ap-pillar:nth-child(2) .ap-pillar-ico{background:linear-gradient(135deg,var(--red),var(--red2))}
.ap-pillar:nth-child(3) .ap-pillar-ico{background:linear-gradient(135deg,var(--ok),#27d39a)}
.ap-pillar-text h4{font-size:15.5px;font-weight:800;color:var(--ink);margin-bottom:4px}
.ap-pillar-text p{font-size:13.5px;color:var(--muted);line-height:1.6}

/* ════════════════════════════════════
   MISSION & VISION
════════════════════════════════════ */
.ap-mv{display:grid;grid-template-columns:1fr 1fr;gap:20px}
.ap-mv-box{
  border-radius:var(--r);padding:40px 36px;color:#fff;position:relative;overflow:hidden;
}
.ap-mv-box::before{
  content:'';position:absolute;right:-40px;top:-40px;
  width:200px;height:200px;border-radius:50%;
  background:rgba(255,255,255,.07);
}
.ap-mv-box.m{background:linear-gradient(135deg,#0f2d8a,#1a3fd0)}
.ap-mv-box.v{background:linear-gradient(135deg,#8c1018,#e8242c)}
.ap-mv-box-icon{font-size:2rem;margin-bottom:18px;opacity:.9}
.ap-mv-box h3{font-size:22px;font-weight:800;margin-bottom:12px}
.ap-mv-box p{font-size:15px;line-height:1.75;color:rgba(255,255,255,.9)}
.ap-mv-box .ap-mv-tag{
  display:inline-block;margin-top:20px;font-size:11.5px;font-weight:800;
  background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.2);
  border-radius:100px;padding:5px 14px;letter-spacing:.05em;text-transform:uppercase;
}

/* ════════════════════════════════════
   PÉDAGOGIE — Grille accent
════════════════════════════════════ */
.ap-ped{display:grid;grid-template-columns:repeat(4,1fr);gap:18px}
.ap-ped-card{
  border-radius:var(--r2);padding:28px 22px;border:1px solid var(--line);
  background:var(--card);transition:.22s;
}
.ap-ped-card:hover{transform:translateY(-5px);box-shadow:var(--sh)}
.ap-ped-num{
  font-size:2.6rem;font-weight:900;line-height:1;
  background:linear-gradient(135deg,var(--blue),var(--blue2));
  -webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;
}
.ap-ped-card h4{font-size:16px;font-weight:800;color:var(--ink);margin:14px 0 8px}
.ap-ped-card p{font-size:13.5px;color:var(--muted);line-height:1.65}

/* ════════════════════════════════════
   17 PAYS — Bande géo
════════════════════════════════════ */
.ap-geo{
  background:linear-gradient(130deg,var(--navy),var(--navy2));
  color:#fff;border-radius:var(--r);padding:56px 52px;
  display:grid;grid-template-columns:1fr auto;gap:40px;align-items:center;
}
.ap-geo h2{font-size:clamp(22px,3vw,34px);font-weight:800;letter-spacing:-.4px;line-height:1.2;margin-bottom:12px}
.ap-geo p{color:rgba(255,255,255,.8);font-size:15px;line-height:1.75;max-width:52ch}
.ap-geo-map{
  display:flex;flex-direction:column;align-items:center;gap:8px;
  background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.14);
  border-radius:16px;padding:28px 36px;text-align:center;flex-shrink:0;
}
.ap-geo-num{font-size:3.2rem;font-weight:900;color:var(--gold);line-height:1}
.ap-geo-lbl{font-size:12px;font-weight:700;color:rgba(255,255,255,.6);text-transform:uppercase;letter-spacing:.08em}
.ap-pays{display:flex;flex-wrap:wrap;gap:8px;margin-top:20px}
.ap-pays span{
  font-size:12px;font-weight:600;padding:5px 12px;
  background:rgba(255,255,255,.09);border:1px solid rgba(255,255,255,.14);
  border-radius:100px;color:rgba(255,255,255,.85);
}

/* ════════════════════════════════════
   TIMELINE
════════════════════════════════════ */
.ap-tl{max-width:780px;margin:0 auto;position:relative;padding-left:52px}
.ap-tl::before{
  content:'';position:absolute;left:18px;top:8px;bottom:8px;width:2px;
  background:linear-gradient(var(--blue),var(--red));
}
.ap-tl-item{
  position:relative;margin-bottom:28px;
  background:var(--card);border:1px solid var(--line);
  border-radius:var(--r2);padding:24px 26px;
  box-shadow:var(--sh-sm);transition:.2s;
}
.ap-tl-item:hover{border-color:rgba(26,63,208,.25);box-shadow:var(--sh)}
.ap-tl-item::before{
  content:'';position:absolute;left:-40px;top:26px;
  width:18px;height:18px;border-radius:50%;
  background:#fff;border:3px solid var(--blue);
  box-shadow:0 0 0 5px rgba(26,63,208,.1);
}
.ap-tl-yr{
  display:inline-block;font-size:11.5px;font-weight:800;letter-spacing:.06em;
  background:linear-gradient(135deg,var(--blue),var(--blue2));color:#fff;
  border-radius:100px;padding:3px 12px;margin-bottom:10px;
}
.ap-tl-item h3{font-size:17px;font-weight:800;color:var(--ink);margin-bottom:6px}
.ap-tl-item p{font-size:14px;color:var(--muted);line-height:1.65}

/* ════════════════════════════════════
   VALEURS — Icon + texte horizontal
════════════════════════════════════ */
.ap-values{display:grid;grid-template-columns:repeat(2,1fr);gap:16px}
.ap-value{
  display:flex;gap:18px;align-items:flex-start;
  background:var(--card);border:1px solid var(--line);border-radius:var(--r2);padding:22px 24px;
}
.ap-value-ico{
  width:48px;height:48px;border-radius:13px;flex-shrink:0;
  display:grid;place-items:center;font-size:20px;color:#fff;
  background:linear-gradient(135deg,var(--blue),var(--blue2));
}
.ap-value:nth-child(2) .ap-value-ico{background:linear-gradient(135deg,var(--red),var(--red2))}
.ap-value:nth-child(3) .ap-value-ico{background:linear-gradient(135deg,var(--ok),#27d39a)}
.ap-value:nth-child(4) .ap-value-ico{background:linear-gradient(135deg,var(--gold),#fbbf24)}
.ap-value-body h4{font-size:15.5px;font-weight:800;color:var(--ink);margin-bottom:5px}
.ap-value-body p{font-size:13.5px;color:var(--muted);line-height:1.6}

/* ════════════════════════════════════
   ENGAGEMENTS
════════════════════════════════════ */
.ap-engagements{display:grid;grid-template-columns:repeat(3,1fr);gap:16px}
.ap-eng{
  display:flex;gap:14px;align-items:flex-start;
  border-left:3px solid var(--blue);padding:18px 20px;
  background:var(--card);border-radius:0 var(--r2) var(--r2) 0;
  box-shadow:var(--sh-sm);
}
.ap-eng i{color:var(--blue);margin-top:2px;font-size:17px;flex-shrink:0}
.ap-eng span{font-size:14px;color:var(--ink);line-height:1.6;font-weight:500}

/* ════════════════════════════════════
   CTA FINAL
════════════════════════════════════ */
.ap-final{
  position:relative;overflow:hidden;
  background:linear-gradient(128deg,var(--navy) 0%,var(--navy2) 60%,#1a2e6e 100%);
  color:#fff;border-radius:24px;padding:72px 60px;text-align:center;
  box-shadow:0 40px 100px rgba(8,18,43,.5);
}
.ap-final::before{content:'';position:absolute;right:-60px;top:-60px;width:360px;height:360px;border-radius:50%;background:radial-gradient(circle,rgba(26,63,208,.45),transparent 62%)}
.ap-final::after{content:'';position:absolute;left:-60px;bottom:-80px;width:280px;height:280px;border-radius:50%;background:radial-gradient(circle,rgba(232,36,44,.32),transparent 60%)}
.ap-final-inner{position:relative;z-index:1}
.ap-final h2{font-size:clamp(24px,3.5vw,40px);font-weight:900;letter-spacing:-.6px;margin:16px 0 12px}
.ap-final p{font-size:16px;color:rgba(255,255,255,.85);line-height:1.75;max-width:56ch;margin:0 auto}

/* ════════════════════════════════════
   RESPONSIVE
════════════════════════════════════ */
@media(max-width:960px){
  .ap-hero-inner{grid-template-columns:1fr;gap:28px}
  .ap-hero-badge{flex-direction:row;justify-content:center;gap:20px;width:100%}
  .ap-split{grid-template-columns:1fr}
  .ap-mv{grid-template-columns:1fr}
  .ap-ped{grid-template-columns:1fr 1fr}
  .ap-geo{grid-template-columns:1fr}
  .ap-geo-map{flex-direction:row;justify-content:center;width:100%}
  .ap-stats-band{grid-template-columns:repeat(2,1fr)}
  .ap-engagements{grid-template-columns:1fr 1fr}
}
@media(max-width:680px){
  .ap-hero{padding:48px 24px 40px;margin-top:16px;border-radius:16px}
  .ap-hero h1{font-size:28px}
  .ap-ped{grid-template-columns:1fr}
  .ap-values{grid-template-columns:1fr}
  .ap-engagements{grid-template-columns:1fr}
  .ap-geo{padding:36px 24px;border-radius:16px}
  .ap-final{padding:48px 24px;border-radius:16px}
  .ap-tl{padding-left:36px}
  .ap-stats-band{grid-template-columns:1fr 1fr}
  .ap-split-img{min-height:240px}
}
</style>

<div class="ap">
<main>

<!-- ══ HERO ══ -->
<section style="padding:28px 0 0">
  <div class="wrap">
    <div class="ap-hero">
      <div class="ap-hero-inner">
        <div class="ap-hero-text">
          <span class="eyebrow light">Institut de formation professionnelle</span>
          <h1 style="margin-top:18px">IBIG EDUFORM —<br><em>former pour l'impact réel.</em></h1>
          <p>Institut de formation professionnelle du groupe <strong>INTERMARK BUSINESS INTERNATIONAL GROUP SARL</strong>, IBIG EDUFORM développe des compétences immédiatement opérationnelles, certifiantes et orientées impact — pour les particuliers, les entreprises, les ONG et les institutions.</p>
          <div class="cta-row">
            <a class="btn btn-accent" href="/formations.php"><i class="fa-solid fa-graduation-cap"></i> Découvrir les formations</a>
            <a class="btn btn-glass" href="/entreprises.php"><i class="fa-solid fa-building"></i> Offre entreprises</a>
          </div>
        </div>
        <div class="ap-hero-badge">
          <div class="ap-hero-badge-num">17</div>
          <div class="ap-hero-badge-sub">Pays<br>OHADA</div>
        </div>
      </div>
    </div>

    <!-- Stats band -->
    <div class="ap-stats-band">
      <div class="ap-stat">
        <div class="ap-stat-num" data-count="<?= preg_replace('/\D/','',$stApprenants) ?>" data-suffix="+"><?= e($stApprenants) ?></div>
        <div class="ap-stat-lbl">Professionnels formés</div>
      </div>
      <div class="ap-stat">
        <div class="ap-stat-num" data-count="<?= (int)$stInseres ?>" data-suffix="+"><?= (int)$stInseres ?>+</div>
        <div class="ap-stat-lbl">Apprenants insérés</div>
      </div>
      <div class="ap-stat">
        <div class="ap-stat-num" data-count="<?= (int)$stDomaines ?>" data-suffix="+"><?= (int)$stDomaines ?>+</div>
        <div class="ap-stat-lbl">Domaines métiers</div>
      </div>
      <div class="ap-stat">
        <div class="ap-stat-num" data-count="<?= (int)$stPratique ?>" data-suffix="%"><?= (int)$stPratique ?>%</div>
        <div class="ap-stat-lbl">Pratique terrain</div>
      </div>
    </div>
  </div>
</section>

<!-- ══ NAV SECONDAIRE ══ -->
<nav class="ap-nav" id="apNav" aria-label="Navigation de la page">
  <div class="ap-nav-inner">
    <a href="#qui-sommes-nous">Qui sommes-nous</a>
    <a href="#mission-vision">Mission & vision</a>
    <a href="#pedagogie">Pédagogie</a>
    <a href="#ancrage">Ancrage OHADA</a>
    <a href="#histoire">Notre histoire</a>
    <a href="#valeurs">Valeurs</a>
    <a href="#engagements">Engagements</a>
  </div>
</nav>

<!-- ══ QUI SOMMES-NOUS ══ -->
<section id="qui-sommes-nous" class="alt-bg">
  <div class="wrap">
    <div class="ap-split">
      <div class="ap-split-img">
        <div class="ap-split-img-inner">
          <div class="ap-split-img-icon">🏛️</div>
          <h3>Un institut structuré, pas un simple centre</h3>
          <p>IBIG EDUFORM fait partie du groupe IBIG SARL, acteur panafricain de conseil et de formation, implanté depuis 2019.</p>
        </div>
      </div>
      <div class="ap-pillars">
        <div class="ap-pillar">
          <div class="ap-pillar-ico"><i class="fa-solid fa-sitemap"></i></div>
          <div class="ap-pillar-text">
            <h4>Organisation structurée</h4>
            <p>Des programmes cohérents, une vision long terme et des processus pédagogiques formalisés.</p>
          </div>
        </div>
        <div class="ap-pillar">
          <div class="ap-pillar-ico"><i class="fa-solid fa-screwdriver-wrench"></i></div>
          <div class="ap-pillar-text">
            <h4>Orientation terrain</h4>
            <p>Chaque formation est construite à partir de cas réels issus du terrain professionnel africain.</p>
          </div>
        </div>
        <div class="ap-pillar">
          <div class="ap-pillar-ico"><i class="fa-solid fa-briefcase"></i></div>
          <div class="ap-pillar-text">
            <h4>Employabilité réelle</h4>
            <p>Insertion, missions, accompagnement et évolution : la formation ne s'arrête pas à la certification.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ══ MISSION & VISION ══ -->
<section id="mission-vision">
  <div class="wrap">
    <div class="center" style="margin-bottom:36px">
      <span class="eyebrow">Notre raison d'être</span>
      <h2 class="sec-title" style="margin-top:14px">Mission &amp; vision</h2>
    </div>
    <div class="ap-mv">
      <div class="ap-mv-box m">
        <div class="ap-mv-box-icon"><i class="fa-solid fa-bullseye"></i></div>
        <h3>Notre mission</h3>
        <p>Former des professionnels capables de créer de la valeur dès leur retour en milieu professionnel — avec des compétences mesurables, utiles et immédiatement exploitables.</p>
        <span class="ap-mv-tag">Impact immédiat</span>
      </div>
      <div class="ap-mv-box v">
        <div class="ap-mv-box-icon"><i class="fa-solid fa-eye"></i></div>
        <h3>Notre vision</h3>
        <p>Devenir la référence africaine en formation professionnelle orientée performance — une qualité africaine à exigence internationale, au service des talents de tout l'espace OHADA.</p>
        <span class="ap-mv-tag">Référence OHADA</span>
      </div>
    </div>
  </div>
</section>

<!-- ══ PÉDAGOGIE ══ -->
<section id="pedagogie" class="alt-bg">
  <div class="wrap">
    <div class="center" style="margin-bottom:36px">
      <span class="eyebrow">Notre pédagogie</span>
      <h2 class="sec-title" style="margin-top:14px">Apprendre vite, appliquer tout de suite</h2>
      <p class="sec-sub">Une approche pragmatique, orientée compétences et résultats mesurables dès la fin de la formation.</p>
    </div>
    <div class="ap-ped">
      <div class="ap-ped-card">
        <div class="ap-ped-num"><?= (int)$stPratique ?>%</div>
        <h4>Pratique terrain</h4>
        <p>Cas réels, exercices métiers, simulations et projets appliqués directement à votre contexte.</p>
      </div>
      <div class="ap-ped-card">
        <div class="ap-ped-num" style="background:linear-gradient(135deg,var(--red),var(--red2));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text">01</div>
        <h4>Formateurs praticiens</h4>
        <p>Des experts actifs en entreprise, issus du conseil et de la gestion — pas uniquement des académiciens.</p>
      </div>
      <div class="ap-ped-card">
        <div class="ap-ped-num" style="background:linear-gradient(135deg,var(--ok),#27d39a);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text">✓</div>
        <h4>Certification reconnue</h4>
        <p>Chaque programme débouche sur un certificat IBIG EDUFORM vérifiable, remis en fin de parcours.</p>
      </div>
      <div class="ap-ped-card">
        <div class="ap-ped-num" style="background:linear-gradient(135deg,var(--gold),#fbbf24);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text">🎯</div>
        <h4>Suivi post-formation</h4>
        <p>Accompagnement à l'insertion, accès au réseau alumni et possibilité de retour en formation.</p>
      </div>
    </div>
  </div>
</section>

<!-- ══ 17 PAYS OHADA ══ -->
<section id="ancrage">
  <div class="wrap">
    <div class="ap-geo">
      <div>
        <span class="eyebrow light">Présence panafricaine</span>
        <h2 style="margin-top:16px">Valable dans tout l'espace OHADA</h2>
        <p style="margin-top:10px">Nos formations sont accessibles en présentiel à Abidjan et en ligne depuis toute l'Afrique francophone. Nos certificats sont reconnus dans les 17 pays membres de l'OHADA.</p>
        <div class="ap-pays">
          <?php foreach(['Côte d\'Ivoire','Sénégal','Mali','Cameroun','Bénin','Togo','Burkina Faso','Guinée','Gabon','Congo','RDC','Niger','Tchad','Centrafrique','Comores','Guinée Éq.','Madagascar'] as $p): ?>
          <span><?= htmlspecialchars($p) ?></span>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="ap-geo-map">
        <div class="ap-geo-num">17</div>
        <div class="ap-geo-lbl">Pays<br>membres</div>
      </div>
    </div>
  </div>
</section>

<!-- ══ TIMELINE ══ -->
<section id="histoire" class="alt-bg">
  <div class="wrap">
    <div class="center" style="margin-bottom:44px">
      <span class="eyebrow">Notre histoire</span>
      <h2 class="sec-title" style="margin-top:14px">Une montée en puissance maîtrisée</h2>
    </div>
    <div class="ap-tl">
      <div class="ap-tl-item">
        <span class="ap-tl-yr">2023</span>
        <h3>Création d'IBIG EDUFORM</h3>
        <p>Lancement du pôle formation professionnelle d'INTERMARK BUSINESS INTERNATIONAL GROUP, avec une orientation résolument pratique et terrain.</p>
      </div>
      <div class="ap-tl-item">
        <span class="ap-tl-yr">2024</span>
        <h3>Structuration des programmes certifiants</h3>
        <p>Déploiement des certificats métiers multi-compétences (3 en 1, 4 en 1) et premières collaborations professionnelles.</p>
      </div>
      <div class="ap-tl-item">
        <span class="ap-tl-yr">2025</span>
        <h3>Montée en puissance &amp; partenariats</h3>
        <p>Renforcement des offres entreprises, ONG et institutions, et intégration de l'accompagnement à l'insertion professionnelle.</p>
      </div>
      <div class="ap-tl-item" style="margin-bottom:0">
        <span class="ap-tl-yr" style="background:linear-gradient(135deg,var(--red),var(--red2))">2026</span>
        <h3>Programme stratégique 2026</h3>
        <p>Lancement du programme annuel structuré, positionnant IBIG EDUFORM comme l'institut de référence pour la formation professionnelle orientée résultats dans l'espace OHADA.</p>
      </div>
    </div>
  </div>
</section>

<!-- ══ VALEURS ══ -->
<section id="valeurs">
  <div class="wrap">
    <div class="center" style="margin-bottom:36px">
      <span class="eyebrow">Ce qui nous guide</span>
      <h2 class="sec-title" style="margin-top:14px">Nos valeurs fondamentales</h2>
    </div>
    <div class="ap-values">
      <div class="ap-value">
        <div class="ap-value-ico"><i class="fa-solid fa-award"></i></div>
        <div class="ap-value-body">
          <h4>Exigence professionnelle</h4>
          <p>Des standards élevés, une pédagogie rigoureuse et des formateurs sélectionnés pour leur expertise réelle.</p>
        </div>
      </div>
      <div class="ap-value">
        <div class="ap-value-ico"><i class="fa-solid fa-bullseye"></i></div>
        <div class="ap-value-body">
          <h4>Orientation résultats</h4>
          <p>Un impact concret et mesurable à chaque formation — la compétence acquise doit se voir dès le premier jour.</p>
        </div>
      </div>
      <div class="ap-value">
        <div class="ap-value-ico"><i class="fa-solid fa-toolbox"></i></div>
        <div class="ap-value-body">
          <h4>Utilité terrain</h4>
          <p>Des compétences directement exploitables au quotidien, construites à partir de cas réels du marché africain.</p>
        </div>
      </div>
      <div class="ap-value">
        <div class="ap-value-ico"><i class="fa-solid fa-lightbulb"></i></div>
        <div class="ap-value-body">
          <h4>Innovation pédagogique</h4>
          <p>Des méthodes modernes, des outils actuels et une adaptation permanente aux évolutions des métiers.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ══ ENGAGEMENTS ══ -->
<section id="engagements" class="alt-bg">
  <div class="wrap">
    <div class="center" style="margin-bottom:36px">
      <span class="eyebrow">Qualité &amp; confiance</span>
      <h2 class="sec-title" style="margin-top:14px">Nos engagements envers vous</h2>
    </div>
    <div class="ap-engagements">
      <div class="ap-eng"><i class="fa-solid fa-circle-check"></i><span>Programmes structurés, contenus actualisés et évaluations rigoureuses.</span></div>
      <div class="ap-eng"><i class="fa-solid fa-circle-check"></i><span>Respect des apprenants, des partenaires et des engagements contractuels.</span></div>
      <div class="ap-eng"><i class="fa-solid fa-circle-check"></i><span>Mise à jour permanente des contenus selon l'évolution des métiers.</span></div>
      <div class="ap-eng"><i class="fa-solid fa-circle-check"></i><span>Des compétences utiles, responsables et créatrices de valeur durable.</span></div>
      <div class="ap-eng"><i class="fa-solid fa-circle-check"></i><span>Transparence et confidentialité dans le traitement de vos données.</span></div>
      <div class="ap-eng"><i class="fa-solid fa-circle-check"></i><span>Certificats vérifiables et accompagnement après la formation.</span></div>
    </div>
  </div>
</section>

<!-- ══ CTA FINAL ══ -->
<section style="padding-bottom:80px">
  <div class="wrap">
    <div class="ap-final">
      <div class="ap-final-inner">
        <span class="eyebrow light">Passons à l'action</span>
        <h2>Rejoignez une formation<br>qui fait la différence</h2>
        <p>IBIG EDUFORM vous accompagne vers des compétences utiles, reconnues et immédiatement valorisables — en ligne ou en présentiel.</p>
        <div class="cta-row center">
          <a class="btn btn-accent" href="/formations.php"><i class="fa-solid fa-graduation-cap"></i> Voir les formations</a>
          <a class="btn btn-blue" href="/preinscription-generale.php"><i class="fa-solid fa-pen-to-square"></i> Préinscription</a>
          <a class="btn btn-glass" href="/contact.php"><i class="fa-solid fa-headset"></i> Nous contacter</a>
        </div>
      </div>
    </div>
  </div>
</section>

</main>
</div>

<script>
/* Nav active sur scroll */
(function(){
  var links = document.querySelectorAll('.ap-nav-inner a');
  var sections = Array.from(links).map(function(a){ return document.querySelector(a.getAttribute('href')); });
  function update(){
    var y = window.scrollY + 120;
    var active = 0;
    sections.forEach(function(s,i){ if(s && s.offsetTop <= y) active = i; });
    links.forEach(function(a,i){ a.classList.toggle('active', i === active); });
  }
  window.addEventListener('scroll', update, {passive:true});
  update();
})();

/* Count-up stats */
(function(){
  var els = document.querySelectorAll('.ap-stat-num[data-count]');
  if(!els.length) return;
  var done = false;
  function run(){
    if(done) return; done = true;
    els.forEach(function(el){
      var target = parseInt(el.dataset.count, 10);
      var suffix = el.dataset.suffix || '';
      var steps = 50, cur = 0;
      var delay = Math.max(1, Math.round(1000/steps));
      var inc = Math.max(1, Math.ceil(target/steps));
      var id = setInterval(function(){
        cur = Math.min(cur + inc, target);
        el.textContent = (cur < target ? cur : target) + suffix;
        if(cur >= target) clearInterval(id);
      }, delay);
    });
  }
  var band = document.querySelector('.ap-stats-band');
  if(!band){ run(); return; }
  var obs = new IntersectionObserver(function(entries){
    if(entries[0].isIntersecting){ run(); obs.disconnect(); }
  },{threshold:.3});
  obs.observe(band);
  setTimeout(run, 1500);
})();
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>
