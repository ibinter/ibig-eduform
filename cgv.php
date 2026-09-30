<?php
require_once __DIR__ . '/core/config.php';

$pageSlug = 'cgv';
require __DIR__ . '/partials/cms_page.php';

$pageTitle = "Conditions Générales de Vente – IBIG EDUFORM";
include __DIR__ . '/partials/header.php';
?>

<style>
/* =========================================
   CGV — IBIG EDUFORM
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
.cgv-note{
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
    <h1>Conditions Générales de Vente</h1>
    <p>
      Les présentes Conditions Générales de Vente (CGV) régissent l’ensemble
      des relations commerciales et contractuelles entre
      <strong>IBIG EDUFORM</strong> (INTERMARK BUSINESS INTERNATIONAL GROUP SARL)
      et toute personne physique ou morale souhaitant s’inscrire à une formation
      ou bénéficier d’un service proposé par l’institution.
      Elles prévalent sur tout autre document, sauf accord écrit préalable.
    </p>
  </section>

  <section class="card" data-n="1">
    <h2>Identification du prestataire</h2>
    <ul>
      <li><strong>Raison sociale :</strong> INTERMARK BUSINESS INTERNATIONAL GROUP SARL</li>
      <li><strong>Enseigne commerciale :</strong> IBIG EDUFORM</li>
      <li><strong>Forme juridique :</strong> SARL de droit ivoirien</li>
      <li><strong>Siège :</strong> Abidjan, République de Côte d’Ivoire</li>
      <li><strong>Email :</strong> <a href="mailto:formation@ibig-eduform.com" style="color:#f5a623">formation@ibig-eduform.com</a></li>
      <li><strong>Téléphone / WhatsApp :</strong> <a href="https://wa.me/2250778882592" style="color:#f5a623">+225 07 78 88 25 92</a></li>
    </ul>
  </section>

  <section class="card" data-n="2">
    <h2>Offres de formation &amp; modalités pédagogiques</h2>
    <p>IBIG EDUFORM propose des formations professionnelles selon les modalités suivantes :</p>
    <ul>
      <li><strong>Présentiel :</strong> formation en salle à Abidjan ou dans les villes partenaires, avec formateur en direct</li>
      <li><strong>En ligne (e-learning) :</strong> accès à la plateforme d’apprentissage depuis tout terminal connecté</li>
      <li><strong>Hybride :</strong> combinaison de séances en présentiel et de modules en ligne</li>
      <li><strong>Intra-entreprise :</strong> formation organisée dans les locaux du client, sur mesure, sur devis uniquement</li>
    </ul>
    <p style="margin-top:10px">
      Les contenus, durées, objectifs pédagogiques et modalités d’évaluation de chaque
      formation sont décrits sur le site ou dans le programme détaillé (TDR)
      disponible sur demande.
    </p>
  </section>

  <section class="card" data-n="3">
    <h2>Processus d’inscription</h2>
    <p>L’inscription suit les étapes suivantes :</p>
    <ul>
      <li><strong>Étape 1 — Préinscription :</strong> remplissage du formulaire en ligne sur ibig-eduform.com</li>
      <li><strong>Étape 2 — Confirmation :</strong> réception d’un email de confirmation et de la convocation provisoire</li>
      <li><strong>Étape 3 — Paiement :</strong> règlement total ou partiel des frais d’inscription selon les conditions convenues</li>
      <li><strong>Étape 4 — Validation définitive :</strong> l’inscription est confirmée à réception du paiement validé</li>
    </ul>
    <p style="margin-top:10px">
      IBIG EDUFORM se réserve le droit de refuser ou d’annuler une inscription
      en cas de non-paiement, de dossier incomplet ou de comportement préjudiciable
      à la communauté apprenante.
    </p>
  </section>

  <section class="card" data-n="4">
    <h2>Tarifs &amp; devis</h2>
    <p>
      Les tarifs de formation sont exprimés en <strong>Francs CFA (FCFA)</strong>,
      toutes taxes comprises. Ils sont affichés sur les fiches de formation du site.
    </p>
    <ul>
      <li><strong>Formation en ligne :</strong> tarifs à partir de 200 000 FCFA par participant</li>
      <li><strong>Formation en présentiel :</strong> tarifs à partir de 250 000 FCFA par participant</li>
      <li><strong>Formation intra-entreprise / groupe :</strong> sur devis uniquement — contactez-nous à <a href="mailto:formation@ibig-eduform.com" style="color:#f5a623">formation@ibig-eduform.com</a></li>
    </ul>
    <p style="margin-top:10px">
      Les tarifs peuvent être révisés à tout moment sans préavis.
      Les modifications ne s’appliquent pas aux inscriptions déjà validées et payées.
      Des remises peuvent être accordées dans le cadre du
      <a href="/parrainage.php" style="color:#f5a623">programme de parrainage</a>
      (10 % de réduction sur présentation d’un code parrain valide).
    </p>
  </section>

  <section class="card" data-n="5">
    <h2>Modalités de paiement</h2>
    <p>IBIG EDUFORM accepte les modes de paiement suivants :</p>
    <ul>
      <li><strong>Mobile Money :</strong> Orange Money, Wave, MTN Money (numéros communiqués à l’inscription)</li>
      <li><strong>Virement bancaire :</strong> coordonnées bancaires fournies sur la facture pro forma</li>
      <li><strong>Espèces :</strong> uniquement en nos bureaux, contre reçu officiel</li>
      <li><strong>Paiement en ligne :</strong> via la plateforme sécurisée du site (selon disponibilité)</li>
    </ul>
    <p style="margin-top:10px">
      Un <strong>paiement échelonné</strong> peut être accordé sous conditions :
      minimum 50 % à l’inscription, le solde au plus tard à la première séance.
      Tout retard de paiement entraîne la suspension de l’accès à la formation
      jusqu’à régularisation.
    </p>
  </section>

  <section class="card" data-n="6">
    <h2>Annulation &amp; report par le participant</h2>
    <ul>
      <li><strong>Plus de 7 jours avant le démarrage :</strong> remboursement possible à hauteur de 80 % des frais versés, sur demande écrite justifiée</li>
      <li><strong>Entre 3 et 7 jours avant le démarrage :</strong> report possible sur la prochaine session sans pénalité, ou remboursement de 50 %</li>
      <li><strong>Moins de 3 jours avant le démarrage :</strong> aucun remboursement, report possible avec frais de dossier de 15 000 FCFA</li>
      <li><strong>Après le démarrage :</strong> aucun remboursement ni report, sauf cas de force majeure dûment justifié</li>
    </ul>
    <p style="margin-top:10px">
      Toute demande d’annulation ou de report doit être adressée par email à
      <a href="mailto:formation@ibig-eduform.com" style="color:#f5a623">formation@ibig-eduform.com</a>
      avec la mention <em>« Annulation – [Nom – Formation] »</em>.
    </p>
  </section>

  <section class="card" data-n="7">
    <h2>Annulation ou report par IBIG EDUFORM</h2>
    <p>
      IBIG EDUFORM se réserve le droit d’annuler ou de reporter une session
      dans les cas suivants : nombre de participants insuffisant, indisponibilité
      du formateur, cas de force majeure.
    </p>
    <ul>
      <li>Le participant est informé par email ou téléphone dans les meilleurs délais</li>
      <li>Il lui est proposé une inscription sur la prochaine session disponible</li>
      <li>En cas de refus, un remboursement intégral des sommes versées est effectué dans un délai de 15 jours</li>
    </ul>
  </section>

  <section class="card" data-n="8">
    <h2>Obligations des participants</h2>
    <p>En s’inscrivant à une formation IBIG EDUFORM, le participant s’engage à :</p>
    <ul>
      <li>Respecter le calendrier de formation (horaires, sessions, délais de rendu)</li>
      <li>Participer activement aux exercices pratiques et évaluations</li>
      <li>Adopter un comportement respectueux envers les formateurs et les autres participants</li>
      <li>Ne pas enregistrer, reproduire ou diffuser les contenus pédagogiques sans autorisation</li>
      <li>Fournir des informations exactes lors de l’inscription</li>
    </ul>
    <p style="margin-top:10px">
      Le non-respect de ces obligations peut entraîner l’exclusion de la formation
      sans remboursement et, le cas échéant, des poursuites judiciaires.
    </p>
  </section>

  <section class="card" data-n="9">
    <h2>Certification &amp; attestations</h2>
    <p>
      À l’issue de la formation, les participants reçoivent, selon les conditions d’obtention :
    </p>
    <ul>
      <li><strong>Certificat de réussite IBIG EDUFORM :</strong> délivré après succès aux évaluations (note minimale communiquée en début de formation)</li>
      <li><strong>Attestation de participation :</strong> délivrée à tout participant ayant suivi au moins 80 % de la formation</li>
      <li><strong>Diplôme ou certification co-labellisée :</strong> selon les partenariats institutionnels actifs</li>
    </ul>
    <p style="margin-top:10px">
      Les certifications IBIG EDUFORM sont reconnues dans les 17 pays membres
      de l’espace OHADA (Afrique francophone). La vérification d’authenticité
      est disponible sur <a href="/verifier-certificat.php" style="color:#f5a623">ibig-eduform.com/verifier-certificat.php</a>.
    </p>
  </section>

  <section class="card" data-n="10">
    <h2>Propriété intellectuelle des supports</h2>
    <p>
      Tous les supports pédagogiques (présentations, fiches, exercices, vidéos, quiz)
      remis ou accessibles lors d’une formation IBIG EDUFORM sont protégés
      par le droit d’auteur et restent la propriété exclusive d’IBIG EDUFORM
      ou de ses partenaires pédagogiques.
    </p>
    <p style="margin-top:10px">
      Les supports sont concédés à titre de licence personnelle, non exclusive et non cessible,
      pour un usage strictement personnel et non commercial. Toute reproduction, diffusion,
      vente ou utilisation à des fins d’enseignement sans autorisation est interdite.
    </p>
  </section>

  <section class="card" data-n="11">
    <h2>Limitation de responsabilité</h2>
    <p>
      IBIG EDUFORM s’engage à dispenser des formations de qualité avec des intervenants qualifiés.
      Cependant, elle ne saurait être tenue responsable de :
    </p>
    <ul>
      <li>L’utilisation que le participant fait des compétences acquises</li>
      <li>L’obtention d’un emploi, d’une promotion ou d’un résultat professionnel spécifique</li>
      <li>Des dommages indirects liés à la formation ou à son absence</li>
      <li>Des incidents matériels ou techniques hors de son contrôle lors des sessions</li>
    </ul>
  </section>

  <section class="card" data-n="12">
    <h2>Droit applicable &amp; règlement des litiges</h2>
    <p>
      Les présentes CGV sont soumises au <strong>droit de la République de Côte d’Ivoire</strong>,
      notamment aux dispositions du droit commun des contrats et aux actes uniformes OHADA.
    </p>
    <p style="margin-top:10px">
      En cas de litige, les parties s’efforceront de trouver une solution amiable
      dans un délai de 30 jours à compter de la notification du différend.
      À défaut, tout litige sera soumis à la compétence exclusive
      des juridictions d’<strong>Abidjan (Côte d’Ivoire)</strong>.
    </p>
    <p style="margin-top:10px">
      Réclamations :
      <a href="mailto:formation@ibig-eduform.com" style="color:#f5a623">formation@ibig-eduform.com</a>
      — mention <em>« Réclamation CGV »</em>
    </p>
  </section>

  <div class="cgv-note">
    &#x2139;&#xFE0F; <strong>Dernière mise à jour : septembre 2026.</strong>
    Les présentes Conditions Générales de Vente peuvent être modifiées à tout moment.
    La version applicable est celle publiée sur le site ibig-eduform.com à la date de commande ou d’inscription.
  </div>

</main>

<?php include __DIR__ . '/partials/footer.php'; ?>
