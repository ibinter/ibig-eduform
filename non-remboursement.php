<?php
require_once __DIR__ . '/core/config.php';

$pageSlug = 'non-remboursement';
require __DIR__ . '/partials/cms_page.php';

$pageTitle = "Politique de non-remboursement – IBIG EDUFORM";
include __DIR__ . '/partials/header.php';
?>

<style>
/* =========================================
   POLITIQUE DE NON-REMBOURSEMENT — IBIG EDUFORM
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
  max-width:1180px;
  margin:100px auto;
  padding:0 22px 120px;
}

/* HERO */
.hero{
  position:relative;
  padding:48px 42px;
  border-radius:28px;
  background:linear-gradient(160deg,#0b3c5d,#020617 70%);
  border:1px solid rgba(255,255,255,.10);
  box-shadow:
    0 0 0 1px rgba(255,255,255,.04),
    0 30px 75px rgba(0,0,0,.55),
    inset 0 0 70px rgba(245,166,35,.08);
}

.hero h1{
  margin:0;
  font-size:2.6rem;
  font-weight:900;
}

.hero p{
  margin-top:14px;
  max-width:820px;
  font-size:1.05rem;
  color:#94a3b8;
}

/* CARD */
.card{
  position:relative;
  margin-top:40px;
  padding:36px 34px 34px 88px;
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
  width:46px;
  height:46px;
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
  font-size:1.5rem;
  font-weight:900;
  color:#f5a623;
}

.card h2::after{
  content:"";
  display:block;
  width:54px;
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
.refund-note{
  margin-top:70px;
  padding:30px;
  border-radius:24px;
  background:rgba(245,166,35,.10);
  border:1px dashed rgba(245,166,35,.45);
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

<main class="legal-wrap">

  <section class="hero">
    <h1>Politique de non-remboursement</h1>
    <p>
      La présente politique précise les règles applicables en matière
      de remboursement des frais liés aux formations et services
      proposés par IBIG EDUFORM.
    </p>
  </section>

  <section class="card" data-n="1">
    <h2>Principe général</h2>
    <p>
      Les frais de formation, d’inscription et de participation versés
      à IBIG EDUFORM correspondent à des prestations de services
      mobilisant des ressources pédagogiques, humaines et logistiques.
      À ce titre, ces frais ne donnent en principe pas lieu à remboursement,
      sauf disposition exceptionnelle expressément accordée par IBIG EDUFORM.
    </p>
  </section>

  <section class="card" data-n="2">
    <h2>Engagements de l’organisme</h2>
    <p>
      Dès validation de l’inscription, IBIG EDUFORM engage des moyens
      organisationnels, techniques et pédagogiques afin d’assurer
      le bon déroulement de la formation, indépendamment de la présence
      effective du participant.
    </p>
  </section>

  <section class="card" data-n="3">
    <h2>Désistement ou abandon du participant</h2>
    <p>
      En cas de désistement, d’abandon, de retard ou d’absence du participant,
      avant ou après le démarrage de la formation, les frais engagés
      demeurent acquis à IBIG EDUFORM, compte tenu des coûts
      déjà mobilisés pour l’organisation de la session.
    </p>
  </section>

  <section class="card" data-n="4">
    <h2>Situations personnelles ou imprévues</h2>
    <p>
      Les contraintes personnelles, professionnelles, financières
      ou techniques du participant ne constituent pas, en elles-mêmes,
      un motif automatique de remboursement.
    </p>
  </section>

  <section class="card" data-n="5">
    <h2>Situations exceptionnelles</h2>
    <p>
      À titre exceptionnel et sans que cela ne constitue un droit automatique,
      IBIG EDUFORM peut examiner certaines situations particulières
      soumises par écrit par le participant, notamment en cas
      d’événement imprévisible et indépendant de sa volonté.
    </p>
    <p>
      Toute décision prise dans ce cadre relève de l’appréciation exclusive
      de IBIG EDUFORM et ne saurait créer un précédent pour des situations similaires.
    </p>
  </section>

  <section class="card" data-n="6">
    <h2>Report ou annulation par IBIG EDUFORM</h2>
    <p>
      En cas de report ou d’annulation d’une formation à l’initiative
      de IBIG EDUFORM, une solution alternative pourra être proposée,
      telle qu’un report de session, un avoir ou une inscription
      à une autre formation, sans obligation de remboursement en numéraire.
    </p>
  </section>

  <section class="card" data-n="7">
    <h2>Acceptation de la politique</h2>
    <p>
      Toute inscription à une formation ou tout paiement effectué
      implique la prise de connaissance et l’acceptation
      de la présente politique de non-remboursement.
    </p>
  </section>

  <section class="card" data-n="8">
    <h2>Référence contractuelle</h2>
    <p>
      La présente politique s’applique conjointement aux
      Conditions Générales de Vente (CGV), aux Conditions Générales
      d’Utilisation et aux autres documents contractuels
      publiés par IBIG EDUFORM.
    </p>
  </section>

  <div class="refund-note">
    Cette politique de non-remboursement peut faire l’objet de mises à jour.
    La version applicable est celle publiée sur le site IBIG EDUFORM.
  </div>

</main>

<?php include __DIR__ . '/partials/footer.php'; ?>
