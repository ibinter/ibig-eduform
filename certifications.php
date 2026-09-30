<?php
require_once __DIR__ . '/core/bootstrap.php';

$pageTitle    = "Certifications professionnelles reconnues en Afrique | IBIG EDUFORM";
$ogDesc       = "IBIG EDUFORM délivre des certifications professionnelles reconnues dans 17 pays OHADA. Certificat de réussite, attestation de participation, co-certification internationale — délivrés en 48h. Plus de 1 000 professionnels certifiés.";
$pageKeywords = "certification professionnelle Afrique, certificat formation OHADA, certification reconnue Côte d'Ivoire, diplôme IBIG EDUFORM, certifier ses compétences Abidjan, formation certifiante en ligne";

$extraHead = <<<'JSONLD'
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "ItemList",
  "name": "Certifications IBIG EDUFORM",
  "description": "Certifications et attestations délivrées par IBIG EDUFORM, reconnues dans les 17 pays membres de l'espace OHADA.",
  "url": "https://ibig-eduform.com/certifications.php",
  "provider": {
    "@type": "EducationalOrganization",
    "@id": "https://ibig-eduform.com/#organization",
    "name": "IBIG EDUFORM"
  },
  "itemListElement": [
    {
      "@type": "EducationalOccupationalCredential",
      "name": "Certificat de réussite IBIG EDUFORM",
      "description": "Délivré après succès aux évaluations. Reconnu dans les 17 pays OHADA.",
      "credentialCategory": "Certificate",
      "recognizedBy": {"@type": "Organization", "name": "Espace OHADA — 17 pays"}
    },
    {
      "@type": "EducationalOccupationalCredential",
      "name": "Attestation de participation IBIG EDUFORM",
      "description": "Délivrée à tout participant ayant suivi au moins 80 % de la formation.",
      "credentialCategory": "Certificate"
    }
  ]
}
</script>
JSONLD;

include __DIR__ . '/partials/header.php';
?>

<style>
body{
  background:
    radial-gradient(circle at 10% 0%, #0b3c5d33, transparent 40%),
    radial-gradient(circle at 90% 15%, #f5a62318, transparent 35%),
    #020617;
  color:#e5e7eb;
  font-family:Inter,system-ui,-apple-system,sans-serif;
}

.cert-wrap{
  max-width:1100px;
  margin:100px auto;
  padding:0 22px 120px;
}

/* HERO */
.cert-hero{
  padding:52px 44px;
  border-radius:28px;
  background:linear-gradient(155deg,#0b3c5d 0%,#020617 70%);
  border:1px solid rgba(255,255,255,.10);
  box-shadow:0 0 0 1px rgba(255,255,255,.04), 0 28px 70px rgba(0,0,0,.55);
  position:relative;
  overflow:hidden;
}
.cert-hero::before{
  content:"";
  position:absolute;inset:0;
  background:radial-gradient(circle at 80% 50%, rgba(245,166,35,.07), transparent 60%);
  pointer-events:none;
}
.cert-hero-inner{ position:relative; }
.cert-hero-badge{
  display:inline-flex;align-items:center;gap:8px;
  background:rgba(245,166,35,.12);border:1px solid rgba(245,166,35,.35);
  color:#f5a623;padding:6px 16px;border-radius:999px;
  font-size:.82rem;font-weight:700;letter-spacing:.04em;
  margin-bottom:18px;
}
.cert-hero h1{
  margin:0;font-size:2.7rem;font-weight:900;line-height:1.15;
}
.cert-hero h1 span{ color:#f5a623; }
.cert-hero p{
  margin:18px 0 0;max-width:680px;
  font-size:1.07rem;color:#94a3b8;line-height:1.7;
}
.cert-hero-actions{
  display:flex;flex-wrap:wrap;gap:14px;margin-top:32px;
}
.cert-hero-actions a{
  display:inline-flex;align-items:center;gap:8px;
  padding:13px 26px;border-radius:14px;font-weight:800;
  font-size:.97rem;text-decoration:none;transition:.2s;
}
.btn-primary{
  background:linear-gradient(135deg,#f5a623,#e8920e);
  color:#020617;
  box-shadow:0 8px 24px rgba(245,166,35,.35);
}
.btn-primary:hover{filter:brightness(1.08);transform:translateY(-2px);}
.btn-outline{
  background:rgba(255,255,255,.06);
  border:1px solid rgba(255,255,255,.18);
  color:#e5e7eb;
}
.btn-outline:hover{background:rgba(255,255,255,.12);}

/* WHAT YOU GET */
.cert-section-title{
  font-size:1.85rem;font-weight:900;
  margin:0 0 10px;
}
.cert-section-sub{
  font-size:1rem;color:#94a3b8;margin:0 0 36px;
}

/* CARDS GRID */
.cert-grid{
  display:grid;
  grid-template-columns:repeat(auto-fit,minmax(280px,1fr));
  gap:22px;
  margin-top:0;
}
.cert-card{
  background:#0f1f3d;
  border:1px solid rgba(255,255,255,.09);
  border-radius:22px;
  padding:30px 26px;
  display:flex;flex-direction:column;gap:14px;
  transition:.22s;
}
.cert-card:hover{
  transform:translateY(-5px);
  border-color:rgba(245,166,35,.3);
  box-shadow:0 20px 56px rgba(0,0,0,.45);
}
.cert-icon{
  width:56px;height:56px;border-radius:18px;
  display:flex;align-items:center;justify-content:center;
  font-size:1.6rem;flex-shrink:0;
}
.cert-card h3{margin:0;font-size:1.1rem;font-weight:900;color:#f1f5f9;}
.cert-card p{margin:0;font-size:.93rem;color:#94a3b8;line-height:1.65;}
.cert-tag{
  display:inline-flex;align-items:center;gap:6px;
  padding:5px 12px;border-radius:999px;
  font-size:.78rem;font-weight:800;
  background:rgba(245,166,35,.12);color:#f5a623;
  border:1px solid rgba(245,166,35,.25);
  align-self:flex-start;
}

/* PROCESS */
.cert-process{
  margin-top:72px;
}
.process-steps{
  display:grid;
  grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
  gap:0;
  margin-top:36px;
  position:relative;
}
.process-step{
  display:flex;flex-direction:column;align-items:center;
  text-align:center;padding:28px 20px;
  position:relative;
}
.process-step::after{
  content:"→";
  position:absolute;right:-12px;top:36px;
  font-size:1.4rem;color:#f5a623;opacity:.5;
}
.process-step:last-child::after{ display:none; }
.step-num{
  width:52px;height:52px;border-radius:50%;
  background:linear-gradient(135deg,#f5a623,#e8920e);
  color:#020617;font-size:1.3rem;font-weight:900;
  display:flex;align-items:center;justify-content:center;
  margin-bottom:16px;flex-shrink:0;
  box-shadow:0 8px 20px rgba(245,166,35,.35);
}
.process-step h4{margin:0 0 8px;font-size:1rem;font-weight:800;color:#f1f5f9;}
.process-step p{margin:0;font-size:.88rem;color:#94a3b8;line-height:1.55;}

/* VERIFY */
.cert-verify{
  margin-top:72px;
  padding:40px 40px;
  border-radius:24px;
  background:linear-gradient(135deg,#0f1f3d,#0b3c5d);
  border:1px solid rgba(255,255,255,.1);
  display:flex;flex-wrap:wrap;gap:28px;align-items:center;
  justify-content:space-between;
}
.cert-verify-text h3{
  margin:0 0 10px;font-size:1.4rem;font-weight:900;color:#f1f5f9;
}
.cert-verify-text p{
  margin:0;font-size:.97rem;color:#94a3b8;max-width:540px;line-height:1.6;
}

/* FAQ MINI */
.cert-faq{margin-top:72px;}
.faq-items{margin-top:28px;display:flex;flex-direction:column;gap:12px;}
.faq-item{
  background:#0f1f3d;border:1px solid rgba(255,255,255,.09);
  border-radius:16px;overflow:hidden;
}
.faq-q{
  width:100%;background:none;border:none;color:#f1f5f9;
  font-size:.98rem;font-weight:700;
  padding:18px 22px;
  display:flex;justify-content:space-between;align-items:center;
  cursor:pointer;gap:12px;text-align:left;
}
.faq-q:hover{background:rgba(255,255,255,.04);}
.faq-q .chevron{
  flex-shrink:0;font-size:1rem;color:#f5a623;
  transition:transform .2s;
}
.faq-item.open .chevron{transform:rotate(180deg);}
.faq-a{
  display:none;padding:0 22px 18px;
  font-size:.93rem;color:#94a3b8;line-height:1.7;
}
.faq-item.open .faq-a{display:block;}

/* CTA */
.cert-cta{
  margin-top:72px;text-align:center;
  padding:52px 28px;
  border-radius:28px;
  background:linear-gradient(155deg,#f5a623 0%,#e8920e 100%);
}
.cert-cta h2{margin:0 0 14px;font-size:2rem;font-weight:900;color:#020617;}
.cert-cta p{margin:0 auto 28px;font-size:1.05rem;color:rgba(2,6,23,.75);max-width:560px;}
.cert-cta a{
  display:inline-flex;align-items:center;gap:8px;
  padding:14px 30px;border-radius:14px;
  background:#020617;color:#f5a623;
  font-weight:900;font-size:1rem;text-decoration:none;
  box-shadow:0 10px 28px rgba(0,0,0,.3);
  transition:.2s;
}
.cert-cta a:hover{transform:translateY(-2px);box-shadow:0 16px 40px rgba(0,0,0,.4);}

@media(max-width:700px){
  .cert-hero{padding:36px 24px;}
  .cert-hero h1{font-size:1.9rem;}
  .process-step::after{display:none;}
  .cert-verify{flex-direction:column;gap:20px;}
}
</style>

<div class="cert-wrap">

  <!-- HERO -->
  <div class="cert-hero">
    <div class="cert-hero-inner">
      <div class="cert-hero-badge"><i class="fa-solid fa-award"></i> Certifications IBIG EDUFORM</div>
      <h1>Ce que vous <span>obtenez</span><br>à l'issue de votre formation</h1>
      <p>Des livrables concrets, reconnus et valorisables immédiatement auprès des employeurs, des ONG et des institutions partout en Afrique francophone et dans l'espace OHADA.</p>
      <div class="cert-hero-actions">
        <a href="/formations.php" class="btn-primary"><i class="fa-solid fa-graduation-cap"></i> Explorer les formations</a>
        <a href="/verifier-certificat.php" class="btn-outline"><i class="fa-solid fa-magnifying-glass"></i> Vérifier un certificat</a>
      </div>
    </div>
  </div>

  <!-- CHIFFRES CLÉS -->
  <div style="margin-top:52px;display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:16px">
    <div style="background:#0f1f3d;border:1px solid rgba(245,166,35,.2);border-radius:20px;padding:28px 20px;text-align:center">
      <div style="font-size:2.4rem;font-weight:900;color:#f5a623;white-space:nowrap">1&nbsp;000+</div>
      <div style="font-size:.88rem;color:#94a3b8;margin-top:6px">Professionnels certifiés</div>
    </div>
    <div style="background:#0f1f3d;border:1px solid rgba(245,166,35,.2);border-radius:20px;padding:28px 20px;text-align:center">
      <div style="font-size:2.4rem;font-weight:900;color:#f5a623">17</div>
      <div style="font-size:.88rem;color:#94a3b8;margin-top:6px">Pays OHADA couverts</div>
    </div>
    <div style="background:#0f1f3d;border:1px solid rgba(245,166,35,.2);border-radius:20px;padding:28px 20px;text-align:center">
      <div style="font-size:2.4rem;font-weight:900;color:#f5a623">26</div>
      <div style="font-size:.88rem;color:#94a3b8;margin-top:6px">Domaines certifiants</div>
    </div>
    <div style="background:#0f1f3d;border:1px solid rgba(245,166,35,.2);border-radius:20px;padding:28px 20px;text-align:center">
      <div style="font-size:2.4rem;font-weight:900;color:#f5a623">48h</div>
      <div style="font-size:.88rem;color:#94a3b8;margin-top:6px">Délai de délivrance</div>
    </div>
  </div>

  <!-- CE QUE VOUS OBTENEZ -->
  <div style="margin-top:64px">
    <h2 class="cert-section-title">Les 6 livrables inclus dans chaque formation</h2>
    <p class="cert-section-sub">Tout participant ayant validé son parcours reçoit l'ensemble des éléments ci-dessous.</p>

    <div class="cert-grid">

      <div class="cert-card">
        <div class="cert-icon" style="background:rgba(30,64,175,.15)">🎓</div>
        <h3>Certificat professionnel IBIG EDUFORM</h3>
        <p>Délivré en version numérique sécurisée et en version physique sur demande, à tout participant ayant validé les évaluations. Reconnu partout en Afrique francophone et dans l'espace OHADA.</p>
        <span class="cert-tag">✅ Inclus dans toutes les formations</span>
      </div>

      <div class="cert-card">
        <div class="cert-icon" style="background:rgba(22,163,74,.12)">📋</div>
        <h3>Supports de cours & outils métiers</h3>
        <p>Slides, templates, checklists, fichiers Excel, modèles de documents et outils pratiques utilisés pendant la formation — exploitables dès le retour au bureau.</p>
        <span class="cert-tag">📦 Remis en fin de session</span>
      </div>

      <div class="cert-card">
        <div class="cert-icon" style="background:rgba(245,166,35,.12)">🏆</div>
        <h3>Co-certification internationale</h3>
        <p>Sur certains parcours (QHSE, Comptabilité OHADA, Gestion de projet, Logistique), une co-certification avec des institutions ou organismes internationaux est disponible. Renseignez-vous lors de votre inscription.</p>
        <span class="cert-tag">🌍 Sur certains programmes</span>
      </div>

      <div class="cert-card">
        <div class="cert-icon" style="background:rgba(147,51,234,.12)">🔗</div>
        <h3>Accès au réseau alumni IBIG</h3>
        <p>Intégrez la communauté des anciens participants : événements networking, offres d'emploi partenaires, groupes métiers sur WhatsApp et LinkedIn, accès aux replays de sessions.</p>
        <span class="cert-tag">🤝 À vie</span>
      </div>

      <div class="cert-card">
        <div class="cert-icon" style="background:rgba(239,68,68,.10)">📊</div>
        <h3>Rapport individuel d'évaluation</h3>
        <p>Un rapport personnalisé vous est remis : compétences validées, points forts, axes de progrès et recommandations concrètes pour aller plus loin dans votre développement professionnel.</p>
        <span class="cert-tag">📝 Personnalisé</span>
      </div>

      <div class="cert-card">
        <div class="cert-icon" style="background:rgba(6,182,212,.10)">🎯</div>
        <h3>Coaching & suivi post-formation</h3>
        <p>30 jours de suivi après la formation : questions pratiques, mise en application, orientation professionnelle. Notre équipe reste disponible pour vous accompagner dans vos premières applications terrain.</p>
        <span class="cert-tag">📞 30 jours inclus</span>
      </div>

    </div>
  </div>

  <!-- PROCESSUS D'OBTENTION -->
  <div class="cert-process">
    <h2 class="cert-section-title">Comment obtenir votre certificat ?</h2>
    <p class="cert-section-sub">Un processus simple et transparent, de l'inscription à la délivrance.</p>

    <div class="process-steps">
      <div class="process-step">
        <div class="step-num">1</div>
        <h4>Inscription & paiement</h4>
        <p>Choisissez votre formation et finalisez votre inscription en ligne ou par WhatsApp.</p>
      </div>
      <div class="process-step">
        <div class="step-num">2</div>
        <h4>Formation complète</h4>
        <p>Participez à l'intégralité des modules avec un taux de présence minimum de 80 %.</p>
      </div>
      <div class="process-step">
        <div class="step-num">3</div>
        <h4>Évaluation finale</h4>
        <p>Quiz, étude de cas ou projet pratique selon la formation. Note minimale : 60/100.</p>
      </div>
      <div class="process-step">
        <div class="step-num">4</div>
        <h4>Certificat délivré</h4>
        <p>Réception par e-mail sous 48 h. Version physique disponible sur demande.</p>
      </div>
    </div>
  </div>

  <!-- TÉMOIGNAGES CERTIFIÉS -->
  <div style="margin-top:72px">
    <h2 class="cert-section-title">Ils ont obtenu leur certification</h2>
    <p class="cert-section-sub">Des professionnels de toute l'Afrique francophone témoignent de l'impact de leur certification IBIG EDUFORM.</p>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:20px;margin-top:0">

      <div style="background:#0f1f3d;border:1px solid rgba(255,255,255,.09);border-radius:20px;padding:28px 24px">
        <div style="color:#f5a623;font-size:1.1rem;margin-bottom:14px">★★★★★</div>
        <p style="font-size:.93rem;color:#cbd5e1;line-height:1.7;margin:0 0 18px;font-style:italic">« Mon certificat IBIG EDUFORM en Gestion de Projet m'a permis d'obtenir un poste de chef de projet dans une ONG internationale. Il a immédiatement crédibilisé mon dossier face à des candidats ayant des certifications européennes. »</p>
        <div style="display:flex;align-items:center;gap:12px">
          <div style="width:42px;height:42px;border-radius:50%;background:linear-gradient(135deg,#1d4ed8,#0b3c5d);display:flex;align-items:center;justify-content:center;font-weight:900;color:#fff;font-size:.9rem">AK</div>
          <div>
            <div style="font-weight:800;font-size:.88rem;color:#f1f5f9">A. Koné</div>
            <div style="font-size:.78rem;color:#64748b">Chef de projet ONG · Abidjan, Côte d'Ivoire</div>
          </div>
        </div>
      </div>

      <div style="background:#0f1f3d;border:1px solid rgba(255,255,255,.09);border-radius:20px;padding:28px 24px">
        <div style="color:#f5a623;font-size:1.1rem;margin-bottom:14px">★★★★★</div>
        <p style="font-size:.93rem;color:#cbd5e1;line-height:1.7;margin:0 0 18px;font-style:italic">« En tant que DRH, j'exige désormais des certifications IBIG EDUFORM pour les recrutements dans mon entreprise. La qualité des candidats certifiés parle d'elle-même — ils arrivent avec des compétences immédiatement opérationnelles. »</p>
        <div style="display:flex;align-items:center;gap:12px">
          <div style="width:42px;height:42px;border-radius:50%;background:linear-gradient(135deg,#15803d,#064e3b);display:flex;align-items:center;justify-content:center;font-weight:900;color:#fff;font-size:.9rem">MB</div>
          <div>
            <div style="font-weight:800;font-size:.88rem;color:#f1f5f9">M. Ba</div>
            <div style="font-size:.78rem;color:#64748b">Directrice RH · Dakar, Sénégal</div>
          </div>
        </div>
      </div>

      <div style="background:#0f1f3d;border:1px solid rgba(255,255,255,.09);border-radius:20px;padding:28px 24px">
        <div style="color:#f5a623;font-size:1.1rem;margin-bottom:14px">★★★★★</div>
        <p style="font-size:.93rem;color:#cbd5e1;line-height:1.7;margin:0 0 18px;font-style:italic">« J'ai ajouté ma certification IBIG EDUFORM en Comptabilité OHADA sur LinkedIn. En moins d'un mois, j'ai reçu 3 propositions de missions en freelance. La vérification en ligne donne confiance aux clients. »</p>
        <div style="display:flex;align-items:center;gap:12px">
          <div style="width:42px;height:42px;border-radius:50%;background:linear-gradient(135deg,#9333ea,#4f46e5);display:flex;align-items:center;justify-content:center;font-weight:900;color:#fff;font-size:.9rem">ET</div>
          <div>
            <div style="font-weight:800;font-size:.88rem;color:#f1f5f9">E. Tapsoba</div>
            <div style="font-size:.78rem;color:#64748b">Consultant freelance · Ouagadougou, Burkina Faso</div>
          </div>
        </div>
      </div>

    </div>
  </div>

  <!-- VÉRIFICATION -->
  <div class="cert-verify">
    <div class="cert-verify-text">
      <h3><i class="fa-solid fa-shield-check" style="color:#f5a623;margin-right:10px"></i>Vérification des certificats</h3>
      <p>Vous êtes employeur, RH ou institution ? Vérifiez en quelques secondes l'authenticité d'un certificat IBIG EDUFORM grâce à notre outil de vérification en ligne, disponible 24h/24.</p>
    </div>
    <a href="/verifier-certificat.php" class="btn-primary" style="flex-shrink:0">
      <i class="fa-solid fa-magnifying-glass"></i> Vérifier un certificat
    </a>
  </div>

  <!-- FAQ -->
  <div class="cert-faq">
    <h2 class="cert-section-title">Questions fréquentes sur les certifications</h2>
    <p class="cert-section-sub">Toutes les réponses sur la délivrance et la valeur de nos certificats.</p>

    <div class="faq-items">

      <div class="faq-item">
        <button class="faq-q">
          Les certificats IBIG EDUFORM sont-ils reconnus en Afrique ?
          <span class="chevron"><i class="fa-solid fa-chevron-down"></i></span>
        </button>
        <div class="faq-a">Oui. Nos certificats sont reconnus partout en Afrique francophone — dans les 17 pays membres de l'OHADA et au-delà. Ils attestent de compétences pratiques validées en situation réelle, ce qui les rend directement valorisables auprès des recruteurs, des responsables RH et des institutions.</div>
      </div>

      <div class="faq-item">
        <button class="faq-q">
          Quelle est la différence entre un certificat IBIG et une co-certification internationale ?
          <span class="chevron"><i class="fa-solid fa-chevron-down"></i></span>
        </button>
        <div class="faq-a">Le certificat IBIG EDUFORM est délivré sur tous les programmes et atteste des compétences acquises lors de votre formation. La co-certification internationale, disponible sur certains parcours spécifiques (QHSE, Gestion de projet, Logistique, etc.), est délivrée conjointement avec un organisme ou institution partenaire à l'international, ce qui lui confère une portée supplémentaire.</div>
      </div>

      <div class="faq-item">
        <button class="faq-q">
          Que se passe-t-il si je n'obtiens pas la note minimale à l'évaluation ?
          <span class="chevron"><i class="fa-solid fa-chevron-down"></i></span>
        </button>
        <div class="faq-a">Si vous n'atteignez pas la note minimale de 60/100, vous avez la possibilité de passer une session de rattrapage dans un délai de 30 jours. Notre équipe vous accompagne pour vous préparer à cette seconde évaluation.</div>
      </div>

      <div class="faq-item">
        <button class="faq-q">
          Peut-on obtenir un certificat pour une formation Samedi Pro (1 journée) ?
          <span class="chevron"><i class="fa-solid fa-chevron-down"></i></span>
        </button>
        <div class="faq-a">Oui. Les formations Samedi Pro délivrent une attestation de participation certifiante IBIG EDUFORM à l'issue de la journée, incluant les compétences clés abordées. Le rapport d'évaluation est également fourni pour les sessions qui incluent un quiz de clôture.</div>
      </div>

      <div class="faq-item">
        <button class="faq-q">
          Combien de temps après la formation reçoit-on le certificat numérique ?
          <span class="chevron"><i class="fa-solid fa-chevron-down"></i></span>
        </button>
        <div class="faq-a">Le certificat numérique sécurisé est envoyé par e-mail dans un délai de 48 heures ouvrables après validation de l'évaluation finale. Pour les formations Samedi Pro, il est généralement envoyé le jour même ou le lendemain.</div>
      </div>

      <div class="faq-item">
        <button class="faq-q">
          Comment partager mon certificat sur LinkedIn ?
          <span class="chevron"><i class="fa-solid fa-chevron-down"></i></span>
        </button>
        <div class="faq-a">Votre certificat numérique est accompagné d'un identifiant unique vérifiable en ligne. Vous pouvez l'ajouter directement à la section « Licences et certifications » de votre profil LinkedIn en indiquant IBIG EDUFORM comme organisme émetteur et l'identifiant de vérification comme identifiant de licence.</div>
      </div>

      <div class="faq-item">
        <button class="faq-q">
          Le certificat est-il valable pour les membres de la diaspora africaine ?
          <span class="chevron"><i class="fa-solid fa-chevron-down"></i></span>
        </button>
        <div class="faq-a">Oui. Les certifications IBIG EDUFORM sont accessibles et valables depuis n'importe quel pays. Les membres de la diaspora africaine peuvent suivre les formations en ligne depuis l'Europe, l'Amérique du Nord ou ailleurs, et obtenir un certificat reconnu à leur retour en Afrique ou valorisable auprès d'employeurs internationaux ayant des activités dans l'espace OHADA.</div>
      </div>

      <div class="faq-item">
        <button class="faq-q">
          Peut-on obtenir une version physique du certificat ?
          <span class="chevron"><i class="fa-solid fa-chevron-down"></i></span>
        </button>
        <div class="faq-a">Oui. Le certificat numérique sécurisé est délivré automatiquement. Une version physique (imprimée, signée et tamponnée) peut être obtenue sur demande expresse adressée à formation@ibig-eduform.com, avec les frais d'impression et d'envoi applicables selon votre pays. Le délai est généralement de 7 à 15 jours ouvrés.</div>
      </div>

    </div>
  </div>

  <!-- CTA FINAL -->
  <div class="cert-cta">
    <h2>Prêt à obtenir votre certification ?</h2>
    <p>Rejoignez les 1 000+ professionnels certifiés par IBIG EDUFORM et développez des compétences valorisables dès demain.</p>
    <a href="/preinscription-generale.php">
      <i class="fa-solid fa-arrow-right"></i> Commencer ma préinscription
    </a>
  </div>

</div>

<script>
document.querySelectorAll('.faq-q').forEach(function(btn){
  btn.addEventListener('click', function(){
    var item = this.closest('.faq-item');
    var isOpen = item.classList.contains('open');
    document.querySelectorAll('.faq-item.open').forEach(function(el){ el.classList.remove('open'); });
    if (!isOpen) item.classList.add('open');
  });
});
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>
