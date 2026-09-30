<?php
require_once __DIR__ . '/../core/security.php';
require_once __DIR__ . '/../core/functions.php';
require_once __DIR__ . '/../core/seo.php';
require_once __DIR__ . '/../core/track_visit.php';

/* ===============================
   FALLBACKS SEO / OPEN GRAPH
================================ */

$pageTitle = $pageTitle ?? 'IBIG EDUFORM – Institut international de formation professionnelle et de certifications orientées impact, performance et employabilité.';

$ogTitle = $ogTitle ?? $pageTitle;

$ogDesc = $ogDesc ?? 'Formations professionnelles certifiantes orientées comp&eacute;tences, performance et employabilit&eacute; en Afrique.';

$ogImage = $ogImage ?? (
  'https://' . $_SERVER['HTTP_HOST'] . '/assets/images/logo.png'
);

$ogUrl = $ogUrl ?? (
  'https://' . $_SERVER['HTTP_HOST'] . ($_SERVER['REQUEST_URI'] ?? '/')
);

/* Sécurité HTML */
if (!function_exists('h')) {
  function h($s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
  }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title><?= h($pageTitle); ?></title>

<!-- OPEN GRAPH -->
<meta property="og:type" content="website">
<meta property="og:title" content="<?= h($ogTitle); ?>">
<meta property="og:description" content="<?= h($ogDesc); ?>">
<meta property="og:image" content="<?= h($ogImage); ?>">
<meta property="og:url" content="<?= h($ogUrl); ?>">
<meta property="og:site_name" content="IBIG EDUFORM">

<!-- TWITTER -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= h($ogTitle); ?>">
<meta name="twitter:description" content="<?= h($ogDesc); ?>">
<meta name="twitter:image" content="<?= h($ogImage); ?>">

<?php
seo_meta(
  $pageTitle,
  "IBIG EDUFORM – Institut international de formation professionnelle et de certifications orientées impact, performance et employabilité.",
  "institut de formation, certifications professionnelles, programmes, insertion professionnelle, entreprises, institutions"
);
?>

<!-- FAVICONS -->
<link rel="icon" href="/favicon.ico">
<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">

<!-- CSS GLOBAL -->
<link rel="stylesheet" href="/assets/css/home-eduform.css">
<link rel="stylesheet" href="/assets/css/intent-popup.css">
<link rel="stylesheet" href="/assets/css/responsive-safety.css">

<style>
/* =====================================================
   HEADER IBIG EDUFORM — REFONTE PREMIUM (charte)
   bleu #1f3fe0 · rouge #e8242c · navy #0a1733
   (markup & IDs inchangés)
===================================================== */
*{box-sizing:border-box}
html,body{margin:0;padding:0;font-family:Inter,"Plus Jakarta Sans",system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;color:#0e1530}

/* ===== TOP BAR (défilant) ===== */
.topbar{
  height:34px;
  background:linear-gradient(90deg,#0a1733,#0b2552 55%,#10227f);
  color:#cdd7ea;
  display:flex;align-items:center;overflow:hidden;
  font-size:12px;letter-spacing:.3px;font-weight:500;
}
.topbar span{white-space:nowrap;padding-left:100%;animation:tbScroll 55s linear infinite}
.topbar span:hover{animation-play-state:paused}
.tb-lnk{color:#f59e0b;text-decoration:none;font-weight:600}
.tb-lnk:hover{color:#fbbf24;text-decoration:underline}
@keyframes tbScroll{to{transform:translateX(-100%)}}

/* ===== HEADER ===== */
.header-eduform{
  position:sticky;top:0;z-index:10000;
  background:rgba(255,255,255,.92);
  backdrop-filter:blur(12px);
  border-bottom:1px solid #eef2f7;
  box-shadow:0 6px 24px rgba(14,21,48,.05);
}
.header-inner{
  max-width:1340px;margin:auto;
  padding:12px 24px;min-height:74px;
  display:flex;align-items:center;justify-content:space-between;gap:22px;
}

/* ===== LOGO ===== */
.logo-eduform{display:inline-flex;align-items:center;flex-shrink:0}
.logo-eduform img{height:62px;width:auto;display:block}

/* ===== MENU DESKTOP ===== */
.menu-eduform{display:flex;align-items:center;gap:22px}
.menu-eduform a,
.menu-eduform button{
  background:none;border:0;cursor:pointer;
  font-size:14.5px;font-weight:600;color:#1e293b;
  text-decoration:none;position:relative;padding:8px 2px;font-family:inherit;
  transition:color .18s ease;
}
/* soulignement animé (charte) */
.menu-eduform a::after{
  content:"";position:absolute;left:0;right:0;bottom:0;height:2px;border-radius:2px;
  background:linear-gradient(90deg,#1f3fe0,#4f6bff);
  transform:scaleX(0);transform-origin:left;transition:transform .22s ease;
}
.menu-eduform a:hover{color:#1f3fe0}
.menu-eduform a:hover::after{transform:scaleX(1)}

/* ===== MENU PLUS (dropdown) ===== */
.menu-more{position:relative}
.menu-more>button{
  padding:8px 14px;border-radius:999px;background:#f1f5fb;color:#1e293b;
  font-weight:700;display:inline-flex;align-items:center;gap:7px;transition:.18s ease;
}
.menu-more>button::after{content:"▾";font-size:11px;color:#1f3fe0;transition:transform .2s ease}
.menu-more.open>button::after{transform:rotate(180deg)}
.menu-more>button:hover{background:#e7edf8;color:#1f3fe0}

.menu-more-dropdown{
  position:absolute;top:140%;right:0;min-width:272px;
  background:#fff;border:1px solid #eef2f7;border-radius:16px;
  box-shadow:0 24px 52px rgba(14,21,48,.16);padding:10px;display:none;z-index:50;
}
.menu-more.open .menu-more-dropdown{display:block;animation:ddIn .18s ease}
@keyframes ddIn{from{opacity:0;transform:translateY(-6px)}to{opacity:1;transform:none}}
.menu-more-dropdown a{
  display:block;padding:11px 14px;border-radius:10px;font-size:14px;font-weight:600;color:#1e293b;text-decoration:none;
}
.menu-more-dropdown a:hover{background:#f1f5fb;color:#1f3fe0}
.menu-more-dropdown hr{border:0;border-top:1px solid #eef2f7;margin:8px 4px}

/* ===== CTA PRÉINSCRIPTION (rouge charte) ===== */
.cta-eduform a{
  display:inline-flex;align-items:center;gap:8px;
  background:linear-gradient(135deg,#e8242c,#ff5763);color:#fff;
  padding:12px 22px;border-radius:14px;font-weight:800;font-size:14px;text-decoration:none;
  box-shadow:0 12px 28px rgba(232,36,44,.30);transition:transform .18s ease, box-shadow .18s ease;
}
.cta-eduform a:hover{transform:translateY(-2px);box-shadow:0 16px 36px rgba(232,36,44,.42)}

/* ===== BURGER ===== */
.burger-eduform{display:none;flex-direction:column;gap:6px;background:none;border:0;cursor:pointer;padding:6px}
.burger-eduform span{width:26px;height:3px;background:#0e1530;border-radius:3px;transition:transform .25s ease,opacity .2s ease}
.burger-eduform.is-open span:nth-child(1){transform:translateY(9px) rotate(45deg)}
.burger-eduform.is-open span:nth-child(2){opacity:0}
.burger-eduform.is-open span:nth-child(3){transform:translateY(-9px) rotate(-45deg)}

/* ===== RESPONSIVE (bascule mobile) ===== */
@media(max-width:1024px){
  .menu-eduform,.cta-eduform{display:none}
  .burger-eduform{display:flex}
  .logo-eduform img{height:54px}
}

/* ===== MENU MOBILE (drawer) ===== */
.menu-mobile-eduform{
  position:fixed;inset:0;background:#fff;
  padding:84px 24px 100px;overflow-y:auto;
  display:flex;flex-direction:column;gap:22px;
  transform:translateX(100%);transition:transform .35s ease;z-index:10001;
}
.menu-mobile-eduform.open{transform:translateX(0)}

.mobile-group{border-bottom:1px solid #eef2f7;padding-bottom:16px}
.mobile-title{
  display:block;margin-bottom:10px;
  font-size:.72rem;font-weight:800;letter-spacing:.14em;text-transform:uppercase;color:#1f3fe0;
}
.menu-mobile-eduform a{
  display:block;padding:11px 0;font-size:16px;font-weight:600;color:#0e1530;text-decoration:none;transition:color .15s ease;
}
.menu-mobile-eduform a:hover{color:#1f3fe0}

.menu-mobile-eduform .mobile-cta{
  margin-top:6px;
  background:linear-gradient(135deg,#e8242c,#ff5763);color:#fff;
  text-align:center;padding:16px;border-radius:14px;font-weight:800;font-size:16px;
  box-shadow:0 12px 28px rgba(232,36,44,.30);
}

.menu-close-eduform{
  position:absolute;top:16px;right:18px;width:46px;height:46px;border:0;background:transparent;
  font-size:34px;line-height:1;color:#0e1530;cursor:pointer;z-index:2;
}
.menu-close-eduform:hover{color:#e8242c}
</style>

</head>
<body>

<div class="topbar">
  <span>
    Bienvenue chez IBIG EDUFORM – Institut international de formation professionnelle et de certifications orientées impact, performance et employabilité.
    &nbsp;&nbsp;|&nbsp;&nbsp;
    ✉ <a href="mailto:formation@ibig-eduform.com" class="tb-lnk">formation@ibig-eduform.com</a>
    &nbsp;&nbsp;|&nbsp;&nbsp;
    ✉ <a href="mailto:formation@intermark-business.com" class="tb-lnk">formation@intermark-business.com</a>
    &nbsp;&nbsp;|&nbsp;&nbsp;
    📞 <a href="tel:+2252722276014" class="tb-lnk">+225 27 22 27 60 14</a>
    &nbsp;&nbsp;|&nbsp;&nbsp;
    💬 <a href="https://wa.me/2250778882592" class="tb-lnk" target="_blank">+225 07 78 88 25 92 (WhatsApp)</a>
    &nbsp;&nbsp;|&nbsp;&nbsp;
    📞 <a href="tel:+2250565904779" class="tb-lnk">+225 05 65 90 47 79</a>
    &nbsp;&nbsp;|&nbsp;&nbsp;
    📞 <a href="tel:+2250153595544" class="tb-lnk">+225 01 53 59 55 44</a>
  </span>
</div>

<header class="header-eduform">
  <div class="header-inner">

    <a href="/index.php" class="logo-eduform">
      <picture>
        <source srcset="/assets/images/logo.webp" type="image/webp">
        <img src="/assets/images/logo.png" alt="IBIG EDUFORM">
      </picture>
    </a>

    <nav class="menu-eduform">

      <a href="/index.php">Accueil</a>
      <a href="/formations.php">Programmes & Certifications</a>
      <a href="/calendrier.php">Calendrier Académique</a>
      <a href="https://ibig-eduform.com/samedi-pro.php">Samedi Pro</a>
      <a href="/opportunites-emploi.php">Opportunités & Emploi</a>
      <a href="/entreprises.php">Entreprises & Institutions</a>
      <a href="/besoin-formation.php">Formations sur mesure</a>

      <div class="menu-more" id="menuMore">
        <button type="button" id="btnPlus">Plus</button>

        <div class="menu-more-dropdown">
          <a href="/certifications.php">Nos certifications</a>
          <a href="https://verify.intermark-business.com/" target="_blank">Certifications & Diplômes</a>
          <a href="/blog.php">Publications & Actualités</a>
          <a href="/partenaires.php">Partenariats institutionnels</a>
          <a href="/a-propos.php">À propos de l’Institut</a>
          <a href="/contact.php">Contact institutionnel</a>
          <hr>
          <a href="/candidat/login.php">Espace Candidat</a>
          <a href="/entreprise/login.php">Espace Entreprise</a>
        </div>
      </div>

    </nav>

    <div class="cta-eduform">
      <a href="/preinscription-generale.php">Préinscription</a>
    </div>

    <button class="burger-eduform" id="burger">
      <span></span><span></span><span></span>
    </button>

  </div>
</header>

<nav class="menu-mobile-eduform" id="menuMobile">

  <button type="button" class="menu-close-eduform" id="menuClose" aria-label="Fermer le menu">&times;</button>

  <!-- GROUPE : ACADÉMIQUE -->
  <div class="mobile-group">
    <span class="mobile-title">Académique</span>

    <a href="/formations.php">Programmes & Certifications</a>
    <a href="/calendrier.php">Calendrier académique</a>
    <a href="https://ibig-eduform.com/samedi-pro.php">Samedi Pro</a>
    <a href="/opportunites-emploi.php">Insertion professionnelle</a>
    <a href="/besoin-formation.php">Formations sur mesure</a>
  </div>

  <!-- GROUPE : INSTITUTION -->
  <div class="mobile-group">
    <span class="mobile-title">Institution</span>

    <a href="/certifications.php">Nos certifications</a>
    <a href="https://verify.intermark-business.com/">Certifications & Diplômes</a>
    <a href="/blog.php">Publications & Actualités</a>
    <a href="/partenaires.php">Partenariats institutionnels</a>
    <a href="/a-propos.php">À propos de l’Institut</a>
    <a href="/contact.php">Contact institutionnel</a>
  </div>

  <!-- GROUPE : ACCÈS -->
  <div class="mobile-group">
    <span class="mobile-title">Accès sécurisés</span>

    <a href="/candidat/login.php">Espace Candidat</a>
    <a href="/entreprise/login.php">Espace Entreprise</a>
  </div>

  <!-- CTA -->
  <a href="/preinscription-generale.php" class="mobile-cta">
    Préinscription
  </a>

</nav>

<script>
document.addEventListener("DOMContentLoaded",()=>{
  const btnPlus=document.getElementById("btnPlus");
  const menuMore=document.getElementById("menuMore");
  const burger=document.getElementById("burger");
  const mobile=document.getElementById("menuMobile");

  btnPlus.addEventListener("click",e=>{
    e.stopPropagation();
    menuMore.classList.toggle("open");
  });
  document.addEventListener("click",()=>{
    menuMore.classList.remove("open");
  });

  const menuClose=document.getElementById("menuClose");

  const setOpen=(open)=>{
    mobile.classList.toggle("open", open);
    if (burger) burger.classList.toggle("is-open", open);
    document.body.style.overflow = open ? "hidden" : "";
  };

  if (burger) burger.addEventListener("click",()=> setOpen(!mobile.classList.contains("open")));
  if (menuClose) menuClose.addEventListener("click",()=> setOpen(false));

  /* Fermer en cliquant sur un lien du menu */
  mobile.querySelectorAll("a").forEach(a => a.addEventListener("click",()=> setOpen(false)));

  /* Fermer avec la touche Échap */
  document.addEventListener("keydown", e => { if (e.key === "Escape") setOpen(false); });
});
</script>

<script>
function trackEvent(type, label = ''){
  fetch('/track.php', {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify({
      event_type: type,
      label: label,
      page_url: location.pathname
    })
  });
}
</script>

</body>
</html>
