<?php
require_once __DIR__ . '/core/config.php';

$pageSlug = 'cookies';
require __DIR__ . '/partials/cms_page.php';

$pageTitle = "Politique des cookies – IBIG EDUFORM";
include __DIR__ . '/partials/header.php';
?>

<style>
/* =========================================
   POLITIQUE DES COOKIES — IBIG EDUFORM
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
.legal-wrap{
  max-width:1150px;
  margin:100px auto;
  padding:0 22px 100px;
}

/* HERO */
.hero{
  position:relative;
  padding:46px 40px;
  border-radius:28px;
  background:linear-gradient(160deg,#0b3c5d,#020617 70%);
  border:1px solid rgba(255,255,255,.10);
  box-shadow:
    0 0 0 1px rgba(255,255,255,.04),
    0 28px 70px rgba(0,0,0,.55),
    inset 0 0 70px rgba(245,166,35,.08);
}

.hero h1{
  margin:0;
  font-size:2.6rem;
  font-weight:900;
}

.hero p{
  margin-top:14px;
  max-width:760px;
  font-size:1.05rem;
  color:#94a3b8;
}

/* CARD */
.card{
  position:relative;
  margin-top:38px;
  padding:34px 32px 32px 86px;
  border-radius:26px;
  background:linear-gradient(180deg,rgba(255,255,255,.06),transparent 140%);
  border:1px solid rgba(255,255,255,.10);
  transition:.35s ease;
}

.card:hover{
  transform:translateY(-4px);
  box-shadow:0 25px 60px rgba(0,0,0,.45);
}

/* NUMÉRO */
.card::before{
  content:attr(data-n);
  position:absolute;
  top:26px;
  left:26px;
  width:44px;
  height:44px;
  display:grid;
  place-items:center;
  border-radius:14px;
  font-weight:900;
  background:linear-gradient(135deg,#f5a623,#ffcc70);
  color:#020617;
  box-shadow:0 0 0 6px rgba(245,166,35,.15);
}

/* TITRES */
.card h2{
  margin:0 0 14px;
  font-size:1.45rem;
  font-weight:900;
  color:#f5a623;
}

.card h2::after{
  content:"";
  display:block;
  width:52px;
  height:3px;
  margin-top:10px;
  background:linear-gradient(90deg,#f5a623,transparent);
  border-radius:3px;
}

/* TEXTES */
.card p,
.card li{
  line-height:1.9;
  font-size:1.02rem;
}

.card ul{
  padding-left:20px;
  margin-top:10px;
}

.card li{
  margin-bottom:6px;
}

/* NOTE */
.cookie-note{
  margin-top:60px;
  padding:28px;
  border-radius:22px;
  background:rgba(245,166,35,.10);
  border:1px dashed rgba(245,166,35,.45);
  color:#fde68a;
  font-size:.95rem;
}

/* RESPONSIVE */
@media(max-width:720px){
  .hero{
    padding:34px 26px;
  }
  .hero h1{
    font-size:2.1rem;
  }
  .card{
    padding:30px 26px 28px 72px;
  }
}
</style>

<main class="legal-wrap">

  <section class="hero">
    <h1>Politique des cookies</h1>
    <p>
      Cette politique explique comment IBIG EDUFORM utilise les cookies
      afin d’assurer le bon fonctionnement du site et d’améliorer votre expérience.
    </p>
  </section>

  <section class="card" data-n="1">
    <h2>Définition</h2>
    <p>
      Un cookie est un petit fichier texte enregistré sur votre appareil
      lors de la consultation d’un site web. Il permet de stocker des informations
      temporaires liées à votre navigation.
    </p>
  </section>

  <section class="card" data-n="2">
    <h2>Cookies utilisés</h2>
    <ul>
      <li>Cookies techniques indispensables au fonctionnement du site</li>
      <li>Cookies de sécurité (sessions, authentification, prévention des abus)</li>
      <li>Cookies de mesure d’audience à des fins statistiques anonymes</li>
    </ul>
  </section>

  <section class="card" data-n="3">
    <h2>Finalités des cookies</h2>
    <ul>
      <li>Garantir le bon fonctionnement des formulaires et services</li>
      <li>Sécuriser l’accès aux espaces et aux paiements</li>
      <li>Améliorer la navigation et l’expérience utilisateur</li>
    </ul>
  </section>

  <section class="card" data-n="4">
    <h2>Gestion des cookies</h2>
    <p>
      Vous pouvez à tout moment configurer votre navigateur pour bloquer,
      limiter ou supprimer les cookies. Le refus de certains cookies
      peut toutefois affecter certaines fonctionnalités du site.
    </p>
  </section>

  <section class="card" data-n="5">
    <h2>Consentement</h2>
    <p>
      La poursuite de la navigation sur le site IBIG EDUFORM vaut
      acceptation de l’utilisation des cookies conformément à la présente politique.
    </p>
  </section>

  <div class="cookie-note">
    Cette politique de cookies peut être mise à jour à tout moment.
    La version en vigueur est celle publiée sur le site IBIG EDUFORM.
  </div>

</main>

<?php include __DIR__ . '/partials/footer.php'; ?>
