<?php
require_once __DIR__ . '/core/bootstrap.php';

$pageSlug = 'conditions';
require __DIR__ . '/partials/cms_page.php';

$pageTitle = "Conditions d’utilisation – IBIG EDUFORM";
include __DIR__ . '/partials/header.php';
?>

<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Conditions générales – IBIG EDUFORM</title>
<meta name="description" content="Conditions générales d'utilisation et de vente du site IBIG EDUFORM.">
<meta name="robots" content="index,follow">

<style>
:root{
  --bg:#020617;
  --bg-soft:#020b1a;
  --card:rgba(255,255,255,.06);
  --border:rgba(255,255,255,.10);
  --text:#e5e7eb;
  --muted:#94a3b8;
  --accent:#f5a623;
  --accent-glow:rgba(245,166,35,.35);
}

/* GLOBAL */
body{
  margin:0;
  font-family:Inter,system-ui,-apple-system,sans-serif;
  background:
    radial-gradient(circle at 20% 0%, #0b3c5d33, transparent 40%),
    radial-gradient(circle at 80% 20%, #f5a62322, transparent 35%),
    var(--bg);
  color:var(--text);
}

/* WRAP */
.legal-wrap{
  max-width:1150px;
  margin:100px auto;
  padding:0 22px;
}

/* HERO */
.hero{
  position:relative;
  padding:48px 42px;
  border-radius:28px;
  background:
    linear-gradient(160deg,#0b3c5d,#020617 70%);
  border:1px solid var(--border);
  box-shadow:
    0 0 0 1px rgba(255,255,255,.04),
    0 30px 80px rgba(0,0,0,.55),
    inset 0 0 80px rgba(245,166,35,.08);
  overflow:hidden;
}

.hero::after{
  content:"";
  position:absolute;
  inset:auto -30% -60% -30%;
  height:120%;
  background:radial-gradient(circle,var(--accent-glow),transparent 60%);
  pointer-events:none;
}

.hero h1{
  margin:0;
  font-size:2.6rem;
  font-weight:900;
  letter-spacing:-.02em;
}

.hero h1 span{
  color:var(--accent);
}

.hero p{
  margin-top:14px;
  max-width:700px;
  font-size:1.05rem;
  color:var(--muted);
}

/* CARD */
.card{
  position:relative;
  margin-top:36px;
  padding:36px 36px 34px 86px;
  border-radius:26px;
  background:linear-gradient(180deg,var(--card),transparent 140%);
  border:1px solid var(--border);
  backdrop-filter:blur(6px);
  transition:.35s ease;
}

.card:hover{
  transform:translateY(-4px);
  box-shadow:0 25px 60px rgba(0,0,0,.45);
}

/* NUMERO */
.card::before{
  content:attr(data-n);
  position:absolute;
  top:28px;
  left:26px;
  width:44px;
  height:44px;
  display:grid;
  place-items:center;
  border-radius:14px;
  font-weight:900;
  color:#020617;
  background:linear-gradient(135deg,var(--accent),#ffcc70);
  box-shadow:0 0 0 6px rgba(245,166,35,.15);
}

/* TITRES */
.card h2{
  margin:0 0 14px;
  font-size:1.5rem;
  font-weight:900;
  color:var(--accent);
}

.card h2::after{
  content:"";
  display:block;
  width:52px;
  height:3px;
  margin-top:10px;
  background:linear-gradient(90deg,var(--accent),transparent);
  border-radius:3px;
}

.card p,
.card li{
  line-height:1.9;
  font-size:1.02rem;
  color:#e5e7eb;
}

.card ul{
  padding-left:20px;
  margin:10px 0 0;
}

.card li{
  margin-bottom:6px;
}

/* FOOT NOTE */
.legal-note{
  margin-top:60px;
  padding:26px;
  border-radius:22px;
  background:rgba(245,166,35,.08);
  border:1px dashed rgba(245,166,35,.4);
  color:#fde68a;
  font-size:.95rem;
}

/* RESPONSIVE */
@media(max-width:720px){
  .hero{
    padding:36px 26px;
  }
  .hero h1{
    font-size:2.1rem;
  }
  .card{
    padding:30px 26px 28px 72px;
  }
}
</style>
</head>

<body>

<main class="legal-wrap">

<section class="hero">
  <h1>Conditions <span>générales</span></h1>
  <p>
    Conditions d’utilisation, d’inscription et de vente applicables aux formations,
    services pédagogiques et contenus proposés par IBIG EDUFORM.
  </p>
</section>

<section class="card" data-n="1">
  <h2>Objet</h2>
  <p>
    Les présentes conditions générales ont pour objet de définir les modalités d’accès,
    d’utilisation et de commercialisation des formations et services proposés par IBIG EDUFORM.
  </p>
</section>

<section class="card" data-n="2">
  <h2>Champ d’application</h2>
  <p>
    Toute navigation sur le site, toute préinscription ou inscription à une formation
    implique l’acceptation sans réserve des présentes conditions.
  </p>
</section>

<section class="card" data-n="3">
  <h2>Accès aux formations</h2>
  <ul>
    <li>Accès conditionné à une préinscription validée.</li>
    <li>Confirmation définitive après paiement des frais requis.</li>
    <li>IBIG EDUFORM se réserve le droit de reporter ou annuler une session.</li>
  </ul>
</section>

<section class="card" data-n="4">
  <h2>Tarifs et paiement</h2>
  <ul>
    <li>Les tarifs sont exprimés en FCFA.</li>
    <li>Les frais d’inscription sont obligatoires sauf mention contraire.</li>
    <li>Aucun remboursement après le démarrage de la formation.</li>
  </ul>
</section>

<section class="card" data-n="5">
  <h2>Propriété intellectuelle</h2>
  <p>
    Tous les supports pédagogiques sont protégés par le droit de la propriété intellectuelle.
    Toute reproduction ou diffusion sans autorisation écrite est strictement interdite.
  </p>
</section>

<section class="card" data-n="6">
  <h2>Responsabilité</h2>
  <p>
    IBIG EDUFORM met en œuvre tous les moyens pédagogiques nécessaires mais ne saurait être
    tenue à une obligation de résultat professionnel ou d’embauche.
  </p>
</section>

<section class="card" data-n="7">
  <h2>Données personnelles</h2>
  <p>
    Les données collectées sont traitées conformément à la politique de confidentialité
    en vigueur et à la réglementation applicable.
  </p>
</section>

<section class="card" data-n="8">
  <h2>Droit applicable</h2>
  <p>
    Les présentes conditions sont régies par le droit ivoirien.
    Tout litige relève de la compétence exclusive des juridictions d’Abidjan.
  </p>
</section>

<div class="legal-note">
   Ces conditions peuvent être modifiées à tout moment.
  La version en vigueur est celle publiée sur le site IBIG EDUFORM.
</div>

</main>

</body>
</html>
<?php include __DIR__ . '/partials/footer.php'; ?>