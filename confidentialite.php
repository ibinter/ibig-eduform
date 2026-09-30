<?php
require_once __DIR__ . '/core/config.php';

$pageSlug = 'confidentialite';
require __DIR__ . '/partials/cms_page.php';

$pageTitle = "Politique de confidentialité – IBIG EDUFORM";
include __DIR__ . '/partials/header.php';
?>

<style>
/* =========================================
   POLITIQUE DE CONFIDENTIALITÉ — IBIG EDUFORM
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
  padding:0 22px 110px;
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
  max-width:780px;
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
.privacy-note{
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
    <h1>Politique de confidentialité</h1>
    <p>
      La présente politique décrit la manière dont <strong>IBIG EDUFORM</strong>
      (INTERMARK BUSINESS INTERNATIONAL GROUP SARL) collecte, utilise, stocke
      et protège vos données personnelles dans le cadre de l’utilisation
      du site <strong>ibig-eduform.com</strong> et de ses services associés.
      Elle est établie conformément à la loi ivoirienne n°2013-450 du 19 juin 2013
      relative à la protection des données à caractère personnel.
    </p>
  </section>

  <section class="card" data-n="1">
    <h2>Responsable du traitement</h2>
    <ul>
      <li><strong>Entité :</strong> INTERMARK BUSINESS INTERNATIONAL GROUP SARL (IBIG SARL)</li>
      <li><strong>Enseigne :</strong> IBIG EDUFORM</li>
      <li><strong>Adresse :</strong> Abidjan, République de Côte d’Ivoire</li>
      <li><strong>Email DPO :</strong> <a href="mailto:formation@ibig-eduform.com" style="color:#f5a623">formation@ibig-eduform.com</a></li>
    </ul>
    <p style="margin-top:12px">
      En tant que responsable du traitement, IBIG EDUFORM détermine les finalités
      et les moyens du traitement de vos données personnelles.
    </p>
  </section>

  <section class="card" data-n="2">
    <h2>Données collectées</h2>
    <p>Selon votre usage du site, IBIG EDUFORM peut collecter les catégories de données suivantes :</p>
    <ul>
      <li><strong>Données d’identification :</strong> nom, prénoms, date de naissance (si fournie)</li>
      <li><strong>Données de contact :</strong> adresse email, numéro de téléphone (fixe ou mobile), pays de résidence</li>
      <li><strong>Données professionnelles :</strong> fonction, entreprise ou organisation, secteur d’activité, niveau d’études</li>
      <li><strong>Données de formation :</strong> formations choisies, historique d’inscription, résultats, certificats obtenus</li>
      <li><strong>Données de paiement :</strong> mode de paiement utilisé (aucune donnée bancaire n’est stockée directement)</li>
      <li><strong>Données de navigation :</strong> adresse IP, type de navigateur, pages consultées, durée de visite, cookies</li>
      <li><strong>Données de compte :</strong> identifiant de connexion, mot de passe chiffré, historique des actions</li>
    </ul>
    <p style="margin-top:10px">
      Les données marquées comme obligatoires dans les formulaires sont indispensables au traitement
      de votre demande. Les autres champs sont facultatifs.
    </p>
  </section>

  <section class="card" data-n="3">
    <h2>Finalités du traitement</h2>
    <p>Vos données sont collectées pour les finalités suivantes :</p>
    <ul>
      <li><strong>Gestion des préinscriptions et inscriptions</strong> aux formations (en ligne et en présentiel)</li>
      <li><strong>Suivi pédagogique :</strong> communication des convocations, supports de cours, résultats et certifications</li>
      <li><strong>Relation client :</strong> réponse aux demandes de contact, devis, réclamations</li>
      <li><strong>Facturation et paiement :</strong> émission de reçus, gestion des échéanciers</li>
      <li><strong>Accès aux espaces sécurisés</strong> (espace candidat, espace apprenant, espace entreprise)</li>
      <li><strong>Communication marketing :</strong> envoi de newsletters, informations sur les nouvelles formations (avec consentement)</li>
      <li><strong>Amélioration des services :</strong> analyse des statistiques d’usage, amélioration du catalogue</li>
      <li><strong>Obligations légales :</strong> conservation des contrats de formation, déclarations aux autorités compétentes</li>
    </ul>
  </section>

  <section class="card" data-n="4">
    <h2>Base légale des traitements</h2>
    <p>Chaque traitement repose sur l’une des bases légales suivantes :</p>
    <ul>
      <li><strong>Consentement :</strong> pour l’envoi de communications commerciales et l’utilisation de cookies non essentiels</li>
      <li><strong>Exécution d’un contrat :</strong> pour le traitement lié aux inscriptions, paiements et accès aux formations</li>
      <li><strong>Obligation légale :</strong> pour la conservation des contrats de formation et les déclarations obligatoires</li>
      <li><strong>Intérêt légitime :</strong> pour la sécurité du site, la prévention des fraudes et l’amélioration des services</li>
    </ul>
  </section>

  <section class="card" data-n="5">
    <h2>Durée de conservation</h2>
    <ul>
      <li><strong>Données de compte actif :</strong> pendant toute la durée de la relation contractuelle</li>
      <li><strong>Données de formation :</strong> 10 ans après la fin de la formation (obligations légales liées aux certifications)</li>
      <li><strong>Données de contact / prospects :</strong> 3 ans à compter du dernier contact ou de la fin de la relation</li>
      <li><strong>Données de navigation / cookies :</strong> 13 mois maximum</li>
      <li><strong>Données de paiement :</strong> durée légale de conservation des pièces comptables (10 ans)</li>
      <li><strong>Après désinscription :</strong> les données sont anonymisées ou supprimées dans un délai de 30 jours</li>
    </ul>
  </section>

  <section class="card" data-n="6">
    <h2>Cookies et traceurs</h2>
    <p>Le site ibig-eduform.com utilise les types de cookies suivants :</p>
    <ul>
      <li><strong>Cookies essentiels :</strong> nécessaires au fonctionnement du site (session, authentification) — ne peuvent pas être désactivés</li>
      <li><strong>Cookies de performance :</strong> mesure d’audience anonyme pour améliorer le site</li>
      <li><strong>Cookies de chat en direct :</strong> utilisés par le widget Tawk.to pour le service de chat en temps réel</li>
      <li><strong>Cookies de réseaux sociaux :</strong> boutons de partage (WhatsApp, Facebook, LinkedIn)</li>
    </ul>
    <p style="margin-top:10px">
      Vous pouvez configurer votre navigateur pour bloquer ou supprimer les cookies.
      Certaines fonctionnalités du site peuvent alors être limitées.
    </p>
  </section>

  <section class="card" data-n="7">
    <h2>Partage des données &amp; tiers</h2>
    <p>IBIG EDUFORM ne vend pas vos données personnelles. Les données peuvent être partagées avec :</p>
    <ul>
      <li><strong>Prestataires techniques :</strong> hébergeur LWS (infrastructure), Tawk.to (chat en direct), services d’emailing</li>
      <li><strong>Partenaires de certification :</strong> dans le strict cadre de la délivrance de votre certificat (nom, prénom, formation)</li>
      <li><strong>Autorités compétentes :</strong> sur réquisition légale ou judiciaire (ARTCI, Justice ivoirienne)</li>
    </ul>
    <p style="margin-top:10px">
      Tout prestataire tiers accédant à vos données est soumis à des engagements contractuels
      de confidentialité et de sécurité conformes à la réglementation applicable.
    </p>
  </section>

  <section class="card" data-n="8">
    <h2>Sécurité des données</h2>
    <p>IBIG EDUFORM met en œuvre les mesures de sécurité suivantes :</p>
    <ul>
      <li>Connexion sécurisée HTTPS (certificat SSL) sur l’ensemble du site</li>
      <li>Chiffrement des mots de passe (hachage bcrypt)</li>
      <li>Accès aux données restreint aux seuls collaborateurs habilités</li>
      <li>Sauvegardes régulières des bases de données</li>
      <li>Surveillance et journalisation des accès aux systèmes</li>
    </ul>
    <p style="margin-top:10px">
      En cas de violation de données susceptible d’engendrer un risque pour vos droits et libertés,
      IBIG EDUFORM s’engage à vous en informer dans les meilleurs délais.
    </p>
  </section>

  <section class="card" data-n="9">
    <h2>Vos droits</h2>
    <p>
      Conformément à la loi ivoirienne n°2013-450 et aux bonnes pratiques internationales,
      vous disposez des droits suivants sur vos données personnelles :
    </p>
    <ul>
      <li><strong>Droit d’accès :</strong> obtenir la confirmation du traitement de vos données et en recevoir une copie</li>
      <li><strong>Droit de rectification :</strong> corriger des données inexactes ou incomplètes</li>
      <li><strong>Droit à l’effacement :</strong> demander la suppression de vos données dans les cas prévus par la loi</li>
      <li><strong>Droit d’opposition :</strong> vous opposer au traitement pour des raisons tenant à votre situation particulière</li>
      <li><strong>Droit à la portabilité :</strong> recevoir vos données dans un format structuré et lisible</li>
      <li><strong>Droit au retrait du consentement :</strong> à tout moment pour les traitements basés sur votre consentement</li>
    </ul>
    <p style="margin-top:12px">
      Pour exercer vos droits, adressez une demande écrite à :
      <a href="mailto:formation@ibig-eduform.com" style="color:#f5a623;font-weight:700">formation@ibig-eduform.com</a>
      avec la mention <em>« Droits données personnelles »</em> et une copie d’un justificatif d’identité.
      Nous nous engageons à répondre dans un délai de <strong>30 jours ouvrés</strong>.
    </p>
    <p style="margin-top:10px">
      En cas de litige non résolu, vous pouvez saisir l’<strong>Autorité de Protection des Données
      à caractère Personnel (APDP)</strong> de Côte d’Ivoire ou toute autorité compétente.
    </p>
  </section>

  <div class="privacy-note">
    &#x2139;&#xFE0F; <strong>Dernière mise à jour : septembre 2026.</strong>
    La présente politique de confidentialité peut être modifiée à tout moment.
    La version applicable est celle publiée sur le site ibig-eduform.com à la date de consultation.
  </div>

</main>

<?php include __DIR__ . '/partials/footer.php'; ?>
