<?php
require_once __DIR__ . '/core/bootstrap.php';

$pageSlug = 'pedagogie';
require __DIR__ . '/partials/cms_page.php';

$pageTitle = "Notre pédagogie – IBIG EDUFORM";
$ogDesc    = "Découvrez l'approche pédagogique IBIG EDUFORM : 80% de pratique, formateurs experts en activité, cas réels, certifications métiers et accompagnement vers l'employabilité.";
include __DIR__ . '/partials/header.php';
?>

<style>
/* =========================================
   PÉDAGOGIE — VERSION PREMIUM INSTITUTIONNELLE
========================================= */
body{
  background:#f8fafc;
  color:#0f172a;
  font-family:Inter, system-ui, sans-serif;
}

.wrap{
  max-width:1200px;
  margin:90px auto;
  padding:0 20px 110px;
}

/* HERO */
.hero{
  background:linear-gradient(135deg,#0b3c5d,#1e40af);
  color:#ffffff;
  border-radius:28px;
  padding:70px;
  margin-bottom:90px;
  box-shadow:0 25px 60px rgba(2,6,23,.25);
}

.hero h1{
  font-size:2.8rem;
  margin-bottom:18px;
}

.hero p{
  font-size:1.15rem;
  line-height:1.75;
  max-width:900px;
  opacity:.95;
}

/* SECTION */
.section{
  margin-bottom:90px;
}

.section h2{
  font-size:2.3rem;
  margin-bottom:16px;
  color:#0b3c5d;
}

.section p.intro{
  max-width:950px;
  font-size:1.05rem;
  line-height:1.8;
  color:#334155;
}

/* GRID */
.grid{
  display:grid;
  grid-template-columns:repeat(auto-fit,minmax(280px,1fr));
  gap:32px;
  margin-top:45px;
}

/* CARD */
.card{
  background:#ffffff;
  border-radius:24px;
  padding:36px;
  box-shadow:0 15px 40px rgba(15,23,42,.08);
  transition:transform .25s ease, box-shadow .25s ease;
}

.card:hover{
  transform:translateY(-6px);
  box-shadow:0 25px 55px rgba(15,23,42,.14);
}

.card h3{
  margin-bottom:12px;
  font-size:1.3rem;
  color:#0b3c5d;
}

.card p{
  color:#475569;
  line-height:1.7;
}

/* PROCESS */
.steps{
  display:grid;
  grid-template-columns:repeat(auto-fit,minmax(240px,1fr));
  gap:30px;
  margin-top:50px;
}

.step{
  background:#ffffff;
  border-radius:22px;
  padding:30px;
  box-shadow:0 15px 40px rgba(15,23,42,.08);
  position:relative;
}

.step span{
  display:inline-flex;
  width:44px;
  height:44px;
  border-radius:14px;
  align-items:center;
  justify-content:center;
  background:#2563eb;
  color:#ffffff;
  font-weight:900;
  margin-bottom:14px;
}

.step h4{
  margin-bottom:10px;
  font-size:1.2rem;
  color:#0b3c5d;
}

.step p{
  color:#475569;
  line-height:1.65;
}

/* HIGHLIGHT */
.highlight{
  margin-top:90px;
  background:linear-gradient(135deg,#2563eb,#0b3c5d);
  color:#ffffff;
  padding:70px;
  border-radius:30px;
  text-align:center;
  box-shadow:0 30px 70px rgba(2,6,23,.3);
}

.highlight strong{
  font-size:1.25rem;
}

/* RESPONSIVE */
@media(max-width:900px){
  .hero{padding:50px;}
  .highlight{padding:50px;}
}
</style>

<main class="wrap">

  <!-- HERO -->
  <section class="hero">
    <h1>Notre pédagogie</h1>
    <p>
      La pédagogie IBIG EDUFORM est fondée sur une approche pragmatique,
      orientée compétences et résultats, conçue pour répondre aux exigences
      concrètes des entreprises, institutions, ONG et projets de développement.
    </p>
  </section>

  <!-- APPROCHE -->
  <section class="section">
    <h2>Une pédagogie orientée terrain</h2>
    <p class="intro">
      Nos formations privilégient l’efficacité opérationnelle.
      Chaque programme est structuré pour permettre une montée en compétences
      rapide, mesurable et immédiatement applicable dans l’environnement professionnel.
    </p>

    <div class="grid">
      <div class="card">
        <h3>80 % pratique – 20 % théorie</h3>
        <p>
          L’apprentissage se fait principalement par l’action :
          études de cas réels, simulations professionnelles,
          ateliers pratiques et travaux guidés.
        </p>
      </div>

      <div class="card">
        <h3>Formateurs issus du terrain</h3>
        <p>
          Les formations sont animées par des praticiens en activité :
          managers, consultants et experts métiers,
          garantissant des contenus concrets et actuels.
        </p>
      </div>

      <div class="card">
        <h3>Orientation résultats</h3>
        <p>
          Chaque formation vise un impact mesurable :
          performance, employabilité, évolution de carrière
          ou amélioration des pratiques professionnelles.
        </p>
      </div>

      <div class="card">
        <h3>Évaluations pratiques</h3>
        <p>
          Les acquis sont validés par des évaluations opérationnelles :
          projets appliqués, mises en situation
          et études de cas professionnelles.
        </p>
      </div>

      <div class="card">
        <h3>Certification valorisable</h3>
        <p>
          Les certifications IBIG EDUFORM attestent
          de compétences réellement maîtrisées
          et valorisables sur le marché du travail.
        </p>
      </div>

      <div class="card">
        <h3>Insertion & accompagnement</h3>
        <p>
          Selon les programmes, un accompagnement post-formation
          est proposé : insertion professionnelle,
          missions, emploi ou entrepreneuriat.
        </p>
      </div>
    </div>
  </section>

  <!-- PROCESSUS -->
  <section class="section">
    <h2>Notre processus pédagogique</h2>

    <div class="steps">
      <div class="step">
        <span>1</span>
        <h4>Analyse des besoins</h4>
        <p>
          Diagnostic du profil, des objectifs
          et des exigences professionnelles du participant.
        </p>
      </div>

      <div class="step">
        <span>2</span>
        <h4>Formation intensive</h4>
        <p>
          Apprentissage pratique, outils métiers,
          simulations et accompagnement continu.
        </p>
      </div>

      <div class="step">
        <span>3</span>
        <h4>Évaluation & certification</h4>
        <p>
          Validation des compétences acquises
          à travers des évaluations opérationnelles.
        </p>
      </div>

      <div class="step">
        <span>4</span>
        <h4>Insertion & suivi</h4>
        <p>
          Accompagnement vers l’emploi,
          la mission professionnelle ou l’autonomie.
        </p>
      </div>
    </div>
  </section>

  <!-- ENGAGEMENT -->
  <section class="highlight">
    <p>
      Notre engagement pédagogique est clair :
      <br><br>
      <strong>
        former des professionnels immédiatement opérationnels,
        capables de créer de la valeur durable dans leur environnement.
      </strong>
    </p>
  </section>

</main>

<?php include __DIR__ . '/partials/footer.php'; ?>
