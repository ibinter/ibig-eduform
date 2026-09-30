<?php
require_once __DIR__ . '/core/config.php';

$pageSlug = 'mentions-legales';
require __DIR__ . '/partials/cms_page.php';

$pageTitle = "Mentions légales – IBIG EDUFORM";
include __DIR__ . '/partials/header.php';
?>

<style>
/* =========================================
   MENTIONS LÉGALES — IBIG EDUFORM
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
.legal-note{
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
    <h1>Mentions légales</h1>
    <p>
      Conformément aux dispositions légales et réglementaires en vigueur en République de Côte d’Ivoire,
      notamment la loi n°2013-546 du 30 juillet 2013 relative aux transactions électroniques,
      les présentes mentions légales définissent l’identité, les droits et les responsabilités
      de l’éditeur du site <strong>ibig-eduform.com</strong>.
    </p>
  </section>

  <section class="card" data-n="1">
    <h2>Éditeur du site</h2>
    <p>
      Le présent site est édité par <strong>INTERMARK BUSINESS INTERNATIONAL GROUP SARL (IBIG SARL)</strong>,
      société de droit ivoirien exerçant ses activités de formation professionnelle
      sous l’enseigne commerciale <strong>IBIG EDUFORM</strong>.
    </p>
    <ul>
      <li><strong>Dénomination sociale :</strong> INTERMARK BUSINESS INTERNATIONAL GROUP SARL</li>
      <li><strong>Nom commercial :</strong> IBIG EDUFORM</li>
      <li><strong>Forme juridique :</strong> Société à Responsabilité Limitée (SARL) au capital social variable</li>
      <li><strong>Pays d’immatriculation :</strong> République de Côte d’Ivoire</li>
      <li><strong>Numéro RCCM :</strong> CI-ABJ-[sur demande]</li>
      <li><strong>Numéro de contribuable (NIF/CC) :</strong> Disponible sur demande auprès de nos services</li>
      <li><strong>Agrément formation :</strong> Organisme de formation professionnelle continue reconnu</li>
    </ul>
  </section>

  <section class="card" data-n="2">
    <h2>Siège social &amp; coordonnées</h2>
    <ul>
      <li><strong>Siège social :</strong> Abidjan, République de Côte d’Ivoire</li>
      <li><strong>Adresse opérationnelle :</strong> Abidjan, Côte d’Ivoire</li>
      <li><strong>Email :</strong> <a href="mailto:formation@ibig-eduform.com" style="color:#f5a623">formation@ibig-eduform.com</a></li>
      <li><strong>Téléphone / WhatsApp :</strong> <a href="https://wa.me/2250778882592" style="color:#f5a623">+225 07 78 88 25 92</a></li>
      <li><strong>Site internet :</strong> <a href="https://ibig-eduform.com" style="color:#f5a623">ibig-eduform.com</a></li>
    </ul>
    <p style="margin-top:14px">
      IBIG EDUFORM opère également à travers son réseau de partenaires institutionnels
      dans les 17 États membres de l’espace OHADA (Afrique francophone).
    </p>
  </section>

  <section class="card" data-n="3">
    <h2>Responsable de la publication</h2>
    <p>
      Le responsable de la publication est le Directeur Général de
      <strong>INTERMARK BUSINESS INTERNATIONAL GROUP SARL</strong>.
      Il est joignable à l’adresse : <a href="mailto:formation@ibig-eduform.com" style="color:#f5a623">formation@ibig-eduform.com</a>.
    </p>
    <p style="margin-top:10px">
      En cas de signalement de contenu litigieux ou inexact, vous pouvez adresser
      une demande écrite à l’adresse email ci-dessus avec la mention
      <em>« Signalement – Mentions légales »</em>.
    </p>
  </section>

  <section class="card" data-n="4">
    <h2>Hébergement du site</h2>
    <p>Le site <strong>ibig-eduform.com</strong> est hébergé par :</p>
    <ul>
      <li><strong>Hébergeur :</strong> LWS (LWS SAS)</li>
      <li><strong>Siège social :</strong> 2, rue Jules Ferry – 88100 Saint-Dié-des-Vosges, France</li>
      <li><strong>Site web :</strong> <a href="https://www.lws.fr" target="_blank" rel="noopener" style="color:#f5a623">www.lws.fr</a></li>
    </ul>
    <p style="margin-top:10px">
      L’hébergeur assure la disponibilité technique, la sécurité de l’infrastructure
      et la maintenance des serveurs sur lesquels repose le site. Il est soumis
      à la législation française et européenne en matière d’hébergement de données.
    </p>
  </section>

  <section class="card" data-n="5">
    <h2>Propriété intellectuelle</h2>
    <p>
      L’ensemble des contenus publiés sur le site IBIG EDUFORM — incluant, sans s’y limiter,
      les textes, programmes de formation, fiches pédagogiques, logos, visuels, photographies,
      graphiques, vidéos, typographies et architecture du site — est protégé par :
    </p>
    <ul>
      <li>Le droit d’auteur (loi n°96-564 du 25 juillet 1996 relative à la propriété intellectuelle en Côte d’Ivoire)</li>
      <li>Les droits voisins et les droits sui generis sur les bases de données</li>
      <li>Les droits relatifs aux marques commerciales déposées</li>
    </ul>
    <p style="margin-top:12px">
      Toute reproduction, représentation, modification, adaptation, traduction,
      extraction ou réutilisation, totale ou partielle, par quelque procédé que ce soit,
      sans l’autorisation préalable et écrite d’IBIG EDUFORM est strictement interdite
      et constitue une contrefaçon sanctionnée par la loi.
    </p>
    <p style="margin-top:10px">
      Les marques tierces mentionnées sur le site sont la propriété de leurs détenteurs respectifs.
    </p>
  </section>

  <section class="card" data-n="6">
    <h2>Limitation de responsabilité</h2>
    <p>
      IBIG EDUFORM s’efforce de maintenir les informations publiées sur le site
      exactes, complètes et à jour. Cependant, l’institution ne saurait être tenue
      responsable des cas suivants :
    </p>
    <ul>
      <li>Erreurs ou imprécisions ponctuelles dans les contenus</li>
      <li>Interruptions de service dues à des opérations de maintenance ou à des incidents techniques</li>
      <li>Dommages directs ou indirects résultant d’une utilisation inappropriée du site</li>
      <li>Contenus ou pratiques des sites tiers accessibles via des liens hypertextes</li>
      <li>Accès non autorisé aux données de l’utilisateur malgré les mesures de sécurité mises en place</li>
    </ul>
    <p style="margin-top:12px">
      L’utilisateur est seul responsable de l’usage qu’il fait des informations
      et services mis à disposition sur le site.
    </p>
  </section>

  <section class="card" data-n="7">
    <h2>Liens hypertextes</h2>
    <p>
      Le site IBIG EDUFORM peut contenir des liens vers des sites tiers
      (partenaires, réseaux sociaux, organismes de certification, etc.).
      Ces liens sont fournis à titre informatif. IBIG EDUFORM n’exerce
      aucun contrôle sur le contenu de ces sites et décline toute responsabilité
      quant à leur contenu, leur disponibilité ou leur politique de confidentialité.
    </p>
    <p style="margin-top:10px">
      Tout lien entrant vers le site ibig-eduform.com doit faire l’objet
      d’une autorisation préalable écrite de la part d’IBIG EDUFORM.
    </p>
  </section>

  <section class="card" data-n="8">
    <h2>Données personnelles &amp; cookies</h2>
    <p>
      Le site collecte des données personnelles dans le cadre des formulaires
      de préinscription, de contact et de compte utilisateur.
      Le traitement de ces données est détaillé dans la
      <a href="/confidentialite.php" style="color:#f5a623;font-weight:700">Politique de confidentialité</a>.
    </p>
    <p style="margin-top:10px">
      L’utilisateur est informé que le site utilise des cookies à des fins
      de fonctionnement, de mesure d’audience et d’amélioration de l’expérience.
      Les modalités de gestion des cookies sont précisées dans les
      <a href="/cgu.php" style="color:#f5a623;font-weight:700">Conditions d’Utilisation</a>.
    </p>
  </section>

  <section class="card" data-n="9">
    <h2>Droit applicable &amp; juridiction compétente</h2>
    <p>
      Les présentes mentions légales sont régies par le <strong>droit de la République de Côte d’Ivoire</strong>,
      notamment :
    </p>
    <ul>
      <li>La loi n°2013-546 du 30 juillet 2013 relative aux transactions électroniques</li>
      <li>La loi n°2013-450 du 19 juin 2013 relative à la protection des données à caractère personnel</li>
      <li>Le droit commun des obligations tel qu’applicable en Côte d’Ivoire</li>
      <li>Les actes uniformes OHADA applicables aux sociétés commerciales</li>
    </ul>
    <p style="margin-top:12px">
      En cas de litige relatif à l’interprétation ou à l’exécution des présentes,
      et à défaut de règlement amiable, les parties conviennent que les juridictions
      compétentes de la ville d’<strong>Abidjan (Côte d’Ivoire)</strong> sont seules compétentes.
    </p>
  </section>

  <div class="legal-note">
    &#x2139;&#xFE0F; <strong>Dernière mise à jour : septembre 2026.</strong>
    Les présentes mentions légales peuvent être modifiées à tout moment sans préavis.
    La version en vigueur est celle publiée sur le site ibig-eduform.com à la date de consultation.
    Nous vous invitons à les consulter régulièrement.
  </div>

</main>

<?php include __DIR__ . '/partials/footer.php'; ?>
