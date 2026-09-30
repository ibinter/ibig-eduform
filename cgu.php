<?php
require_once __DIR__ . '/core/config.php';

$pageSlug = 'cgu';
require __DIR__ . '/partials/cms_page.php';

$pageTitle = "Conditions d'Utilisation – IBIG EDUFORM";
include __DIR__ . '/partials/header.php';
?>

<style>
/* =========================================
   CGU — IBIG EDUFORM
========================================= */
body{
  background:
    radial-gradient(circle at 15% 0%, #0b3c5d33, transparent 40%),
    radial-gradient(circle at 85% 20%, #1f3fe022, transparent 35%),
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
    inset 0 0 70px rgba(31,63,224,.10);
}
.hero h1{margin:0;font-size:2.6rem;font-weight:900}
.hero p{margin-top:14px;max-width:820px;font-size:1.05rem;color:#94a3b8}

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
.card:hover{transform:translateY(-4px);box-shadow:0 25px 60px rgba(0,0,0,.45)}

/* NUMÉRO */
.card::before{
  content:attr(data-n);
  position:absolute;top:26px;left:26px;width:46px;height:46px;
  display:grid;place-items:center;border-radius:14px;font-weight:900;
  background:linear-gradient(135deg,#1f3fe0,#4f6bff);
  color:#fff;
  box-shadow:0 0 0 6px rgba(31,63,224,.15);
}

/* TITRES */
.card h2{margin:0 0 14px;font-size:1.5rem;font-weight:900;color:#7ea2ff}
.card h2::after{
  content:"";display:block;width:54px;height:3px;margin-top:10px;
  background:linear-gradient(90deg,#1f3fe0,transparent);border-radius:3px;
}

/* TEXTES */
.card p,.card li{line-height:1.9;font-size:1.02rem}
.card ul{padding-left:20px;margin-top:10px}
.card li{margin-bottom:6px}

/* NOTE */
.cgv-note{
  margin-top:70px;padding:30px;border-radius:24px;
  background:rgba(31,63,224,.10);
  border:1px dashed rgba(31,63,224,.45);
  color:#bcd2ff;font-size:.95rem;
}

/* RESPONSIVE */
@media(max-width:720px){
  .hero{padding:36px 26px}
  .hero h1{font-size:2.1rem}
  .card{padding:30px 26px 28px 72px}
}
</style>

<main class="legal-wrap">

  <section class="hero">
    <h1>Conditions d'Utilisation</h1>
    <p>
      Les présentes Conditions Générales d'Utilisation (CGU) définissent les règles d'accès
      et d'usage du site <strong>ibig-eduform.com</strong> et de l'ensemble des services
      en ligne proposés par <strong>IBIG EDUFORM</strong>. Elles s'appliquent à tout utilisateur
      accédant au site, qu'il soit simple visiteur, candidat à une formation ou représentant
      d'une entreprise cliente. En naviguant sur le site, vous reconnaissez avoir pris
      connaissance de ces conditions et les accepter sans réserve.
    </p>
  </section>

  <section class="card" data-n="1">
    <h2>Objet &amp; champ d'application</h2>
    <p>
      Les présentes CGU ont pour objet d'encadrer l'accès et l'utilisation du site
      <strong>ibig-eduform.com</strong> ainsi que l'ensemble de ses fonctionnalités :
    </p>
    <ul>
      <li>Consultation du catalogue de formations (présentiel, en ligne, hybride)</li>
      <li>Formulaire de préinscription individuelle et générale</li>
      <li>Espaces sécurisés : Candidat, Apprenant, Entreprise</li>
      <li>Service de chat en direct (Tawk.to)</li>
      <li>Formulaires de contact, demande de devis et demande de formation sur mesure</li>
      <li>Consultation du blog, du calendrier de formations, des témoignages et certifications</li>
      <li>Programme de parrainage et téléchargements pédagogiques</li>
    </ul>
  </section>

  <section class="card" data-n="2">
    <h2>Acceptation des conditions</h2>
    <p>
      L'accès et l'utilisation du site valent acceptation pleine et entière
      des présentes CGU dans leur version en vigueur au moment de la visite.
      Si vous n'acceptez pas ces conditions, nous vous invitons à ne pas utiliser le site.
    </p>
    <p style="margin-top:10px">
      IBIG EDUFORM se réserve le droit de modifier les présentes CGU à tout moment.
      Les modifications prennent effet dès leur publication sur le site.
      L'utilisation continue du site après modification vaut acceptation des nouvelles conditions.
    </p>
  </section>

  <section class="card" data-n="3">
    <h2>Accès au site &amp; disponibilité</h2>
    <p>
      Le site <strong>ibig-eduform.com</strong> est accessible gratuitement
      à tout utilisateur disposant d'un accès internet, depuis tout territoire.
    </p>
    <ul>
      <li>IBIG EDUFORM s'efforce d'assurer la continuité du service 7j/7, 24h/24</li>
      <li>Des interruptions peuvent survenir pour des opérations de maintenance, mises à jour ou incidents techniques</li>
      <li>IBIG EDUFORM se réserve le droit de suspendre temporairement l'accès sans préavis</li>
      <li>L'accès depuis certains pays peut être soumis aux réglementations locales de l'utilisateur</li>
    </ul>
  </section>

  <section class="card" data-n="4">
    <h2>Création de compte &amp; espaces sécurisés</h2>
    <p>L'accès aux espaces personnels (Candidat, Apprenant, Entreprise) nécessite la création d'un compte :</p>
    <ul>
      <li>L'utilisateur s'engage à fournir des informations exactes, complètes et à jour lors de l'inscription</li>
      <li>L'utilisateur est seul responsable de la confidentialité de ses identifiants de connexion</li>
      <li>Toute connexion et toute action effectuée via un compte sont réputées effectuées par son titulaire</li>
      <li>En cas de suspicion d'utilisation frauduleuse, l'utilisateur doit en informer IBIG EDUFORM immédiatement</li>
      <li>IBIG EDUFORM se réserve le droit de suspendre ou supprimer tout compte en cas de violation des présentes CGU</li>
    </ul>
    <p style="margin-top:10px">
      La création d'un compte implique l'acceptation de la
      <a href="/confidentialite.php" style="color:#f5a623">Politique de confidentialité</a>.
    </p>
  </section>

  <section class="card" data-n="5">
    <h2>Comportements interdits</h2>
    <p>Il est strictement interdit d'utiliser le site pour :</p>
    <ul>
      <li>Diffuser des contenus illicites, diffamatoires, injurieux ou contraires à l'ordre public</li>
      <li>Tenter d'accéder de manière non autorisée aux systèmes ou données d'IBIG EDUFORM</li>
      <li>Perturber le fonctionnement du site par des attaques informatiques (DDoS, injection, etc.)</li>
      <li>Extraire ou utiliser massivement les contenus du catalogue sans autorisation</li>
      <li>Usurper l'identité d'IBIG EDUFORM ou d'un autre utilisateur</li>
      <li>Utiliser des robots d'indexation ou de scraping non autorisés</li>
      <li>Transmettre des virus, malwares ou tout code nuisible</li>
    </ul>
    <p style="margin-top:10px">
      Tout manquement peut entraîner la suspension immédiate du compte,
      et le cas échéant des poursuites pénales conformément au droit ivoirien.
    </p>
  </section>

  <section class="card" data-n="6">
    <h2>Propriété intellectuelle</h2>
    <p>
      L'ensemble des contenus publiés sur le site — programmes de formation, fiches pédagogiques,
      textes, logos, visuels, vidéos, bases de données — est la propriété exclusive
      d'<strong>IBIG EDUFORM</strong> ou de ses partenaires autorisés, protégée par
      la loi ivoirienne n°96-564 du 25 juillet 1996 relative à la propriété intellectuelle.
    </p>
    <p style="margin-top:10px">
      Toute reproduction, représentation, extraction, adaptation, diffusion ou exploitation,
      totale ou partielle, par quelque moyen que ce soit, sans autorisation préalable et écrite,
      est strictement interdite et constitue une contrefaçon sanctionnée par la loi.
    </p>
    <p style="margin-top:10px">
      Une demande d'autorisation peut être adressée à :
      <a href="mailto:formation@ibig-eduform.com" style="color:#f5a623">formation@ibig-eduform.com</a>
    </p>
  </section>

  <section class="card" data-n="7">
    <h2>Données personnelles &amp; cookies</h2>
    <p>
      Les données collectées via les formulaires (préinscription, contact, compte utilisateur)
      sont traitées par IBIG EDUFORM dans le respect de la loi ivoirienne n°2013-450
      relative à la protection des données à caractère personnel.
    </p>
    <p style="margin-top:10px">
      Le site utilise des cookies pour son fonctionnement, la mesure d'audience
      et le service de chat en direct. L'utilisateur peut désactiver les cookies
      non essentiels via les paramètres de son navigateur.
    </p>
    <p style="margin-top:10px">
      Pour plus de détails, consultez notre
      <a href="/confidentialite.php" style="color:#f5a623;font-weight:700">Politique de confidentialité</a>.
    </p>
  </section>

  <section class="card" data-n="8">
    <h2>Limitation de responsabilité</h2>
    <p>IBIG EDUFORM ne pourra être tenue responsable de :</p>
    <ul>
      <li>Interruptions ou indisponibilités du site liées à des événements hors de son contrôle</li>
      <li>Dommages directs ou indirects liés à l'utilisation ou à l'impossibilité d'utiliser le site</li>
      <li>Inexactitudes ou omissions dans les informations publiées malgré la vigilance apportée</li>
      <li>Contenus, pratiques ou politique de confidentialité des sites tiers accessibles via liens</li>
      <li>Pertes de données imputables à l'utilisateur (perte d'identifiants, etc.)</li>
    </ul>
  </section>

  <section class="card" data-n="9">
    <h2>Liens hypertextes &amp; sites tiers</h2>
    <p>
      Le site peut contenir des liens vers des sites tiers (partenaires, réseaux sociaux,
      organismes de certification, médias). IBIG EDUFORM n'exerce aucun contrôle
      sur ces sites et n'est pas responsable de leurs contenus, services ou
      politiques de confidentialité.
    </p>
    <p style="margin-top:10px">
      Tout lien vers ibig-eduform.com depuis un site tiers doit faire l'objet
      d'une autorisation préalable écrite d'IBIG EDUFORM.
    </p>
  </section>

  <section class="card" data-n="10">
    <h2>Droit applicable &amp; règlement des litiges</h2>
    <p>
      Les présentes CGU sont régies par le <strong>droit de la République de Côte d'Ivoire</strong>.
      En cas de litige relatif à leur interprétation ou à leur exécution, les parties
      s'efforceront de trouver une solution amiable avant tout recours judiciaire.
    </p>
    <p style="margin-top:10px">
      À défaut d'accord amiable dans un délai de 30 jours, tout litige sera soumis
      à la compétence exclusive des juridictions d'<strong>Abidjan (Côte d'Ivoire)</strong>.
    </p>
    <p style="margin-top:10px">
      Pour toute réclamation ou question relative aux présentes CGU :
      <a href="mailto:formation@ibig-eduform.com" style="color:#f5a623">formation@ibig-eduform.com</a>
    </p>
  </section>

  <div class="cgv-note">
    &#x2139;&#xFE0F; <strong>Dernière mise à jour : septembre 2026.</strong>
    Les présentes Conditions d'Utilisation peuvent être modifiées à tout moment sans préavis.
    La version applicable est celle publiée sur le site ibig-eduform.com à la date de consultation.
  </div>

</main>

<?php include __DIR__ . '/partials/footer.php'; ?>
