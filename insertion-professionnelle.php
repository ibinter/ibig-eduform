<?php
declare(strict_types=1);
/* =====================================================================
   IBIG EDUFORM — Insertion professionnelle
   Présente le dispositif d'accompagnement à l'emploi : CV, entretiens,
   mise en relation entreprises, réseau alumni, suivi post-formation.
===================================================================== */
require_once __DIR__ . '/core/config.php';
require_once __DIR__ . '/core/database.php';
require_once __DIR__ . '/core/helpers.php';

$pdo = Database::connect();

/* Dernières formations actives (pour la section "Trouver ma formation") */
try {
  $formations_vedette = $pdo->query("
    SELECT id, titre, slug, domaine, duree, date_debut
    FROM formations
    WHERE statut = 'active' AND date_debut >= CURDATE()
    ORDER BY date_debut ASC LIMIT 6
  ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) { $formations_vedette = []; }

/* Compteurs dynamiques */
try {
  $stats = $pdo->query("
    SELECT
      COUNT(*) AS total_formations,
      COUNT(DISTINCT domaine) AS total_domaines
    FROM formations WHERE statut = 'active'
  ")->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) { $stats = ['total_formations' => 50, 'total_domaines' => 12]; }

$pageTitle      = "Insertion professionnelle — IBIG EDUFORM";
$metaDescription = "IBIG EDUFORM vous accompagne au-delà de la formation : CV professionnel, préparation aux entretiens, mise en relation avec les entreprises et réseau alumni actif dans 17 pays OHADA.";
$ogDesc         = $metaDescription;

ob_start();
?>
<style>
/* ── VARIABLES ───────────────────────────────────────── */
:root{
  --ip-blue:#0a1733;
  --ip-accent:#1f3fe0;
  --ip-green:#16a34a;
  --ip-orange:#f97316;
  --ip-red:#e8242c;
  --ip-light:#f4f7fc;
  --ip-border:#e3ebf6;
}

/* ── HERO ────────────────────────────────────────────── */
.ip-hero{
  background:linear-gradient(135deg,#06102b 0%,#0f2a6e 55%,#1a1035 100%);
  color:#fff;padding:80px 20px 0;text-align:center;position:relative;overflow:hidden
}
.ip-hero::after{
  content:'';display:block;height:60px;
  background:linear-gradient(to bottom right,transparent 49%,#f4f7fc 50%);
}
.ip-hero-eyebrow{
  display:inline-flex;align-items:center;gap:8px;
  background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.2);
  border-radius:999px;padding:6px 16px;font-size:13px;font-weight:700;
  color:#93c5fd;margin-bottom:22px;letter-spacing:.03em
}
.ip-hero h1{
  font-size:clamp(2rem,5vw,3.2rem);font-weight:900;
  margin:0 0 18px;line-height:1.1;max-width:820px;margin-inline:auto
}
.ip-hero h1 em{
  font-style:normal;
  background:linear-gradient(90deg,#60a5fa,#a78bfa);
  -webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text
}
.ip-hero .sub{
  max-width:680px;margin:0 auto 36px;
  color:#c7d2fe;font-size:clamp(15px,2vw,17px);line-height:1.65
}
.ip-hero-btns{display:flex;flex-wrap:wrap;gap:12px;justify-content:center;margin-bottom:48px}
.ip-hero-btns a{
  display:inline-flex;align-items:center;gap:8px;
  padding:14px 28px;border-radius:12px;font-weight:800;font-size:15px;text-decoration:none
}
.btn-hero-main{background:#e8242c;color:#fff}
.btn-hero-main:hover{background:#c91a21}
.btn-hero-alt{background:rgba(255,255,255,.12);color:#fff;border:1px solid rgba(255,255,255,.28)}
.btn-hero-alt:hover{background:rgba(255,255,255,.2)}

/* ── STATS BAND ─────────────────────────────────────── */
.ip-stats-band{
  background:#fff;border-bottom:1px solid var(--ip-border);
  padding:28px 20px
}
.ip-stats-inner{
  max-width:960px;margin:0 auto;
  display:flex;flex-wrap:wrap;gap:12px;justify-content:center
}
.ip-stat{
  display:flex;flex-direction:column;align-items:center;
  background:var(--ip-light);border-radius:16px;padding:18px 28px;min-width:130px;
  border:1px solid var(--ip-border)
}
.ip-stat strong{font-size:2rem;font-weight:900;color:var(--ip-accent);line-height:1}
.ip-stat span{font-size:12.5px;color:#64748b;margin-top:4px;text-align:center}

/* ── SECTIONS COMMUNES ───────────────────────────────── */
.ip-section{padding:72px 20px}
.ip-section.alt{background:var(--ip-light)}
.ip-inner{max-width:1100px;margin:0 auto}
.ip-label{
  display:inline-block;font-size:11.5px;font-weight:900;letter-spacing:.1em;
  text-transform:uppercase;color:var(--ip-accent);margin-bottom:12px
}
.ip-h2{font-size:clamp(1.5rem,3.5vw,2.2rem);font-weight:900;color:var(--ip-blue);margin:0 0 14px;line-height:1.15}
.ip-lead{font-size:16px;color:#475569;line-height:1.7;max-width:700px;margin:0 0 44px}

/* ── PARCOURS ÉTAPES ─────────────────────────────────── */
.ip-steps{display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:22px}
.ip-step{
  background:#fff;border-radius:20px;padding:28px 24px;
  border:1px solid var(--ip-border);position:relative;
  box-shadow:0 6px 22px rgba(10,23,51,.05);transition:.2s
}
.ip-step:hover{box-shadow:0 14px 40px rgba(10,23,51,.1);transform:translateY(-3px)}
.ip-step-num{
  width:42px;height:42px;border-radius:14px;
  display:flex;align-items:center;justify-content:center;
  font-size:17px;font-weight:900;color:#fff;margin-bottom:16px;flex:none
}
.ip-step h3{font-size:1.05rem;font-weight:800;color:var(--ip-blue);margin:0 0 8px}
.ip-step p{font-size:14px;color:#64748b;line-height:1.6;margin:0}
.ip-step .tag{
  display:inline-block;margin-top:14px;font-size:11.5px;font-weight:700;
  padding:3px 10px;border-radius:999px;background:#e0e7ff;color:var(--ip-accent)
}

/* ── SERVICES GRID ───────────────────────────────────── */
.ip-services{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:24px}
.ip-service{
  background:#fff;border-radius:20px;padding:30px 28px;
  border:1px solid var(--ip-border);
  box-shadow:0 6px 22px rgba(10,23,51,.05)
}
.ip-service .ic{
  width:56px;height:56px;border-radius:16px;
  display:flex;align-items:center;justify-content:center;font-size:26px;
  margin-bottom:18px
}
.ip-service h3{font-size:1.1rem;font-weight:800;color:var(--ip-blue);margin:0 0 10px}
.ip-service p{font-size:14.5px;color:#64748b;line-height:1.65;margin:0 0 16px}
.ip-service ul{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:7px}
.ip-service ul li{font-size:13.5px;color:#334155;display:flex;align-items:flex-start;gap:8px}
.ip-service ul li::before{content:'✓';color:var(--ip-green);font-weight:900;flex:none;margin-top:1px}

/* ── ENTREPRISES PARTENAIRES ─────────────────────────── */
.ip-partners-grid{
  display:grid;grid-template-columns:repeat(auto-fill,minmax(170px,1fr));gap:14px;
  margin-bottom:28px
}
.ip-partner{
  background:#fff;border:1px solid var(--ip-border);border-radius:14px;
  padding:18px 16px;text-align:center;transition:.15s
}
.ip-partner:hover{border-color:var(--ip-accent);background:#f0f4ff}
.ip-partner .ico{font-size:28px;margin-bottom:8px}
.ip-partner .name{font-size:13px;font-weight:700;color:var(--ip-blue)}
.ip-partner .sector{font-size:11.5px;color:#64748b;margin-top:3px}

/* ── TÉMOIGNAGES ─────────────────────────────────────── */
.ip-testi-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(290px,1fr));gap:22px}
.ip-testi{
  background:#fff;border-radius:20px;padding:28px 26px;
  border:1px solid var(--ip-border);
  box-shadow:0 6px 22px rgba(10,23,51,.05);position:relative
}
.ip-testi::before{
  content:'"';position:absolute;top:16px;right:20px;
  font-size:60px;color:#e0e7ff;line-height:1;font-family:Georgia,serif
}
.ip-testi-stars{color:#f59e0b;font-size:15px;margin-bottom:12px}
.ip-testi-txt{font-size:14.5px;color:#334155;line-height:1.7;margin:0 0 18px;font-style:italic}
.ip-testi-author{display:flex;align-items:center;gap:12px}
.ip-testi-avatar{
  width:44px;height:44px;border-radius:50%;
  display:flex;align-items:center;justify-content:center;
  font-weight:800;font-size:16px;color:#fff;flex:none
}
.ip-testi-info .name{font-size:14px;font-weight:800;color:var(--ip-blue)}
.ip-testi-info .role{font-size:12.5px;color:#64748b}

/* ── FORMATIONS VEDETTE ──────────────────────────────── */
.ip-fv-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:18px}
.ip-fv-card{
  background:#fff;border:1px solid var(--ip-border);border-radius:16px;
  padding:20px;text-decoration:none;transition:.15s;display:flex;flex-direction:column;gap:8px
}
.ip-fv-card:hover{border-color:var(--ip-accent);background:#f0f4ff;transform:translateY(-2px)}
.ip-fv-card .dom{font-size:11.5px;font-weight:700;color:var(--ip-accent);text-transform:uppercase;letter-spacing:.05em}
.ip-fv-card .titre{font-size:15px;font-weight:800;color:var(--ip-blue);line-height:1.35}
.ip-fv-card .meta{font-size:12.5px;color:#64748b;display:flex;align-items:center;gap:8px;margin-top:auto}
.ip-fv-card .go{margin-left:auto;color:var(--ip-accent);font-weight:900}

/* ── FAQ ─────────────────────────────────────────────── */
.ip-faq{max-width:820px;margin:0 auto;display:flex;flex-direction:column;gap:10px}
.ip-faq-item{border:1px solid var(--ip-border);border-radius:14px;overflow:hidden;background:#fff}
.ip-faq-q{
  width:100%;background:none;border:none;cursor:pointer;
  display:flex;align-items:center;justify-content:space-between;gap:12px;
  padding:18px 22px;font-size:15px;font-weight:700;color:var(--ip-blue);
  text-align:left
}
.ip-faq-q svg{flex:none;transition:.25s}
.ip-faq-item.open .ip-faq-q svg{transform:rotate(45deg)}
.ip-faq-a{display:none;padding:0 22px 18px;font-size:14.5px;color:#475569;line-height:1.7}
.ip-faq-item.open .ip-faq-a{display:block}

/* ── CTA FINAL ───────────────────────────────────────── */
.ip-cta-final{
  background:linear-gradient(135deg,#0a1733 0%,#1f3fe0 100%);
  color:#fff;border-radius:28px;padding:56px 44px;text-align:center;
  position:relative;overflow:hidden
}
.ip-cta-final::before{
  content:'';position:absolute;right:-80px;top:-80px;width:300px;height:300px;
  border-radius:50%;background:rgba(255,255,255,.06)
}
.ip-cta-final h2{font-size:clamp(1.6rem,3vw,2.2rem);font-weight:900;margin:0 0 14px}
.ip-cta-final p{color:#c7d2fe;max-width:640px;margin:0 auto 32px;line-height:1.65;font-size:16px}
.ip-cta-btns{display:flex;flex-wrap:wrap;gap:14px;justify-content:center}
.ip-cta-btns a{
  display:inline-flex;align-items:center;gap:8px;
  padding:15px 30px;border-radius:12px;font-weight:800;font-size:15px;text-decoration:none
}
.btn-cta-red{background:#e8242c;color:#fff}
.btn-cta-red:hover{background:#c91a21}
.btn-cta-white{background:#fff;color:var(--ip-blue)}
.btn-cta-white:hover{background:#f1f5f9}
.btn-cta-outline{background:rgba(255,255,255,.12);color:#fff;border:1px solid rgba(255,255,255,.3)}
.btn-cta-outline:hover{background:rgba(255,255,255,.2)}

/* ── PROCESS TIMELINE ────────────────────────────────── */
.ip-timeline{display:flex;flex-direction:column;gap:0;position:relative;max-width:780px;margin:0 auto}
.ip-timeline::before{
  content:'';position:absolute;left:24px;top:0;bottom:0;width:2px;background:var(--ip-border)
}
.ip-tl-item{display:flex;gap:28px;padding-bottom:36px;position:relative}
.ip-tl-dot{
  flex:none;width:50px;height:50px;border-radius:50%;
  display:flex;align-items:center;justify-content:center;
  font-size:20px;border:3px solid #fff;
  box-shadow:0 0 0 3px var(--ip-border);
  position:relative;z-index:1;background:#fff
}
.ip-tl-body{flex:1;padding-top:10px}
.ip-tl-body h3{font-size:1.05rem;font-weight:800;color:var(--ip-blue);margin:0 0 6px}
.ip-tl-body p{font-size:14.5px;color:#64748b;line-height:1.6;margin:0}
.ip-tl-body .chip{
  display:inline-block;margin-top:10px;font-size:12px;font-weight:700;
  padding:4px 12px;border-radius:999px;background:#dcfce7;color:#15803d
}

/* ── RESPONSIVE ──────────────────────────────────────── */
@media(max-width:720px){
  .ip-hero{padding:56px 20px 0}
  .ip-section{padding:52px 20px}
  .ip-cta-final{padding:36px 22px}
  .ip-timeline::before{left:20px}
  .ip-tl-dot{width:42px;height:42px;font-size:17px}
}
</style>

<!-- ══ HERO ══════════════════════════════════════════ -->
<section class="ip-hero">
  <div class="ip-hero-eyebrow">🎓 Service Insertion &amp; Employabilité IBIG EDUFORM</div>
  <h1>Se former, c'est bien.<br><em>Être embauché, c'est le but.</em></h1>
  <p class="sub">Nous ne nous arrêtons pas à la remise du certificat. Notre dispositif d'insertion vous accompagne activement de la fin de formation jusqu'à votre premier poste — ou votre montée en responsabilité.</p>
  <div class="ip-hero-btns">
    <a class="btn-hero-main" href="/preinscription.php">🚀 Démarrer ma formation</a>
    <a class="btn-hero-alt" href="#parcours">Voir le parcours →</a>
  </div>
</section>

<!-- ══ STATS BAND ══════════════════════════════════════ -->
<div class="ip-stats-band">
  <div class="ip-stats-inner">
    <div class="ip-stat"><strong>17</strong><span>pays couverts<br>espace OHADA</span></div>
    <div class="ip-stat"><strong><?= (int)($stats['total_formations'] ?? 50); ?>+</strong><span>formations certifiantes<br>actives</span></div>
    <div class="ip-stat"><strong>100%</strong><span>certifiantes<br>& vérifiables</span></div>
    <div class="ip-stat"><strong>En ligne</strong><span>présentiel &amp; distanciel<br>partout en OHADA</span></div>
  </div>
</div>

<!-- ══ PARCOURS INSERTION ══════════════════════════════ -->
<section class="ip-section" id="parcours">
  <div class="ip-inner">
    <span class="ip-label">Notre approche</span>
    <h2 class="ip-h2">Un parcours en 5 étapes vers l'emploi</h2>
    <p class="ip-lead">De l'inscription à votre premier poste, chaque étape est structurée pour maximiser vos chances d'insertion dans l'espace OHADA.</p>

    <div class="ip-steps">
      <div class="ip-step">
        <div class="ip-step-num" style="background:linear-gradient(135deg,#1f3fe0,#3b82f6)">1</div>
        <h3>Formation certifiante</h3>
        <p>Un programme 100% orienté compétences opérationnelles, validé par les entreprises du secteur et sanctionné par un certificat reconnu.</p>
        <span class="tag">Dès J+1</span>
      </div>
      <div class="ip-step">
        <div class="ip-step-num" style="background:linear-gradient(135deg,#7c3aed,#a855f7)">2</div>
        <h3>Bilan &amp; profil employabilité</h3>
        <p>À l'issue de la formation, un conseiller dresse votre bilan de compétences et construit avec vous votre profil d'insertion personnalisé.</p>
        <span class="tag">Semaine 1 post-formation</span>
      </div>
      <div class="ip-step">
        <div class="ip-step-num" style="background:linear-gradient(135deg,#0369a1,#0ea5e9)">3</div>
        <h3>CV &amp; préparation entretien</h3>
        <p>Rédaction de votre CV professionnel, lettre de motivation ciblée, simulation d'entretien et coaching individuel avec nos conseillers RH.</p>
        <span class="tag">Semaines 2–3</span>
      </div>
      <div class="ip-step">
        <div class="ip-step-num" style="background:linear-gradient(135deg,#f97316,#eab308)">4</div>
        <h3>Mise en relation entreprises</h3>
        <p>Diffusion de votre profil auprès de nos entreprises partenaires, transmission d'offres ciblées et introduction directe aux recruteurs.</p>
        <span class="tag">Semaines 3–8</span>
      </div>
      <div class="ip-step">
        <div class="ip-step-num" style="background:linear-gradient(135deg,#16a34a,#22c55e)">5</div>
        <h3>Suivi alumni &amp; réseau</h3>
        <p>Intégration à la communauté IBIG EDUFORM, accès aux offres exclusives alumni, mentorat par des diplômés en poste et événements networking.</p>
        <span class="tag">Sur le long terme</span>
      </div>
    </div>
  </div>
</section>

<!-- ══ SERVICES D'INSERTION ════════════════════════════ -->
<section class="ip-section alt" id="services">
  <div class="ip-inner">
    <span class="ip-label">Ce que nous offrons</span>
    <h2 class="ip-h2">Nos services d'accompagnement</h2>
    <p class="ip-lead">Des outils concrets et un accompagnement humain pour transformer votre certificat en opportunité d'emploi réelle.</p>

    <div class="ip-services">

      <div class="ip-service">
        <div class="ic" style="background:#e0e7ff">📄</div>
        <h3>Coaching CV &amp; dossier de candidature</h3>
        <p>Vos documents de candidature revus et optimisés par nos conseillers RH, adaptés aux standards des entreprises de l'espace OHADA.</p>
        <ul>
          <li>CV professionnel en 2 formats (ATS + visuel)</li>
          <li>Lettre de motivation personnalisée par secteur</li>
          <li>Profil LinkedIn optimisé</li>
          <li>Dossier de compétences certifiées IBIG</li>
        </ul>
      </div>

      <div class="ip-service">
        <div class="ic" style="background:#dcfce7">🎤</div>
        <h3>Préparation aux entretiens</h3>
        <p>Simulations d'entretiens en conditions réelles avec des professionnels RH, débriefing personnalisé et techniques de pitch.</p>
        <ul>
          <li>Simulations d'entretiens individuels (1h)</li>
          <li>Tests de personnalité &amp; compétences</li>
          <li>Coaching posture &amp; communication</li>
          <li>Entretiens vidéo pour candidats distanciel</li>
        </ul>
      </div>

      <div class="ip-service">
        <div class="ic" style="background:#fff3d6">🤝</div>
        <h3>Mise en relation &amp; jobboard</h3>
        <p>Accès à notre réseau d'entreprises partenaires et à notre jobboard réservé aux diplômés IBIG EDUFORM.</p>
        <ul>
          <li>Diffusion du profil auprès de nos entreprises partenaires</li>
          <li>Accès jobboard privé diplômés IBIG</li>
          <li>Transmission d'offres ciblées par secteur</li>
          <li>Introduction directe aux responsables RH</li>
        </ul>
      </div>

      <div class="ip-service">
        <div class="ic" style="background:#fce7f3">🌍</div>
        <h3>Réseau alumni &amp; communauté</h3>
        <p>Rejoignez la communauté des diplômés IBIG EDUFORM présents dans l'espace OHADA — entraide, opportunités et réseau professionnel actif.</p>
        <ul>
          <li>Groupe WhatsApp &amp; communauté en ligne</li>
          <li>Événements networking mensuels</li>
          <li>Mentorat par des diplômés en poste</li>
          <li>Accès aux offres exclusives alumni</li>
        </ul>
      </div>

      <div class="ip-service">
        <div class="ic" style="background:#ede9fe">🚀</div>
        <h3>Accompagnement entrepreneurial</h3>
        <p>Pour ceux qui souhaitent créer leur activité, un parcours dédié pour structurer et lancer leur projet avec les bons outils.</p>
        <ul>
          <li>Business plan simplifié &amp; modèle économique</li>
          <li>Mise en relation avec des incubateurs partenaires</li>
          <li>Accès au financement (microfinance, ONG bailleurs)</li>
          <li>Coaching entrepreneur 3 mois post-formation</li>
        </ul>
      </div>

      <div class="ip-service">
        <div class="ic" style="background:#fef3c7">📊</div>
        <h3>Suivi &amp; reporting employabilité</h3>
        <p>IBIG EDUFORM mesure ses résultats. Chaque diplômé est suivi pendant 6 mois après sa formation pour garantir son insertion.</p>
        <ul>
          <li>Suivi mensuel par conseiller dédié</li>
          <li>Rapport d'employabilité par promotion</li>
          <li>Ajustement du plan d'action si nécessaire</li>
          <li>Accès libre au réseau IBIG à vie</li>
        </ul>
      </div>

    </div>
  </div>
</section>

<!-- ══ COMMENT ÇA MARCHE (TIMELINE) ═══════════════════ -->
<section class="ip-section" id="comment">
  <div class="ip-inner" style="text-align:center">
    <span class="ip-label">Processus concret</span>
    <h2 class="ip-h2" style="margin-inline:auto;max-width:600px">De la remise du certificat à la signature du contrat</h2>
    <p class="ip-lead" style="margin:0 auto 48px">Voici exactement ce qui se passe après votre formation.</p>
  </div>
  <div class="ip-inner">
    <div class="ip-timeline">
      <div class="ip-tl-item">
        <div class="ip-tl-dot">🎓</div>
        <div class="ip-tl-body">
          <h3>Remise du certificat IBIG EDUFORM</h3>
          <p>Vous recevez votre certificat vérifiable en ligne, reconnu partout en Afrique francophone et dans l'espace OHADA.</p>
          <span class="chip">Jour J — Fin de formation</span>
        </div>
      </div>
      <div class="ip-tl-item">
        <div class="ip-tl-dot">📋</div>
        <div class="ip-tl-body">
          <h3>Entretien de bilan &amp; orientation</h3>
          <p>Un conseiller vous contacte sous 48h pour dresser votre bilan de compétences et définir votre cible emploi (poste, secteur, géographie).</p>
          <span class="chip">J+2 — 48 heures après</span>
        </div>
      </div>
      <div class="ip-tl-item">
        <div class="ip-tl-dot">✏️</div>
        <div class="ip-tl-body">
          <h3>Création / refonte du dossier de candidature</h3>
          <p>CV, lettre de motivation, profil LinkedIn, dossier de compétences — tout est revu et finalisé avec votre conseiller.</p>
          <span class="chip">Semaine 1–2</span>
        </div>
      </div>
      <div class="ip-tl-item">
        <div class="ip-tl-dot">🎤</div>
        <div class="ip-tl-body">
          <h3>Simulation d'entretien &amp; coaching</h3>
          <p>Séance de préparation en conditions réelles, avec débriefing et conseils ciblés sur votre secteur et votre profil.</p>
          <span class="chip">Semaine 2–3</span>
        </div>
      </div>
      <div class="ip-tl-item">
        <div class="ip-tl-dot">📡</div>
        <div class="ip-tl-body">
          <h3>Diffusion du profil &amp; mise en relation</h3>
          <p>Votre profil est transmis à nos entreprises partenaires. Vous accédez au jobboard privé et recevez des offres ciblées.</p>
          <span class="chip">Semaines 3–8</span>
        </div>
      </div>
      <div class="ip-tl-item">
        <div class="ip-tl-dot">🏆</div>
        <div class="ip-tl-body">
          <h3>Entrée en poste &amp; intégration alumni</h3>
          <p>Vous signez votre contrat. Vous rejoignez la communauté alumni IBIG EDUFORM et continuez à bénéficier du réseau et des offres exclusives.</p>
          <span class="chip">Objectif : 3 à 6 mois</span>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ══ SECTEURS CIBLES ══════════════════════════════════ -->
<section class="ip-section alt" id="secteurs">
  <div class="ip-inner">
    <span class="ip-label">Secteurs d'insertion</span>
    <h2 class="ip-h2">Dans quels secteurs nos diplômés s'insèrent-ils ?</h2>
    <p class="ip-lead">Nos formations couvrent les secteurs les plus porteurs de l'espace OHADA. Nos diplômés s'insèrent principalement dans :</p>

    <div class="ip-partners-grid">
      <?php
      $secteurs = [
        ['🏦','Banques & Assurances'],
        ['🏭','Industries & BTP'],
        ['📦','Logistique & Supply Chain'],
        ['🌱','ONG & Développement'],
        ['💼','Cabinets RH & Conseil'],
        ['🏢','PME & Startups'],
        ['🏛️','Administrations'],
        ['📡','Télécoms & Tech'],
        ['🛒','Commerce & Distribution'],
        ['🌍','Agences de développement'],
        ['⚖️','Cabinets juridiques'],
        ['📊','Cabinets comptables'],
      ];
      foreach ($secteurs as $s):
      ?>
        <div class="ip-partner">
          <div class="ico"><?= $s[0]; ?></div>
          <div class="name"><?= htmlspecialchars($s[1], ENT_QUOTES, 'UTF-8'); ?></div>
        </div>
      <?php endforeach; ?>
    </div>

    <div style="text-align:center;margin-top:20px">
      <a href="/entreprises.php" style="display:inline-flex;align-items:center;gap:8px;padding:12px 24px;border-radius:10px;background:var(--ip-accent);color:#fff;font-weight:800;font-size:14px;text-decoration:none">
        🤝 Vous êtes une entreprise ? Nous contacter →
      </a>
    </div>
  </div>
</section>

<!-- ══ AVIS RÉELS ══════════════════════════════════════ -->
<section class="ip-section" id="temoignages">
  <div class="ip-inner">
    <span class="ip-label">Témoignages</span>
    <h2 class="ip-h2">Ce que disent nos apprenants</h2>
    <p class="ip-lead">Retrouvez les avis authentiques de nos participants sur la page dédiée — non filtrés, non modifiés.</p>
    <div style="text-align:center;margin-top:8px">
      <a href="/avis.php" style="display:inline-flex;align-items:center;gap:8px;padding:14px 28px;border-radius:12px;background:var(--ip-accent);color:#fff;font-weight:800;font-size:15px;text-decoration:none">
        ⭐ Voir tous les avis de nos apprenants →
      </a>
    </div>
  </div>
</section>

<!-- ══ PROCHAINES FORMATIONS ══════════════════════════ -->
<?php if (!empty($formations_vedette)): ?>
<section class="ip-section alt" id="formations">
  <div class="ip-inner">
    <span class="ip-label">Commencer maintenant</span>
    <h2 class="ip-h2">Prochaines sessions disponibles</h2>
    <p class="ip-lead">Choisissez votre formation, obtenez votre certificat, et activez votre parcours d'insertion.</p>

    <div class="ip-fv-grid">
      <?php
      $frMois = [1=>'janv.',2=>'févr.',3=>'mars',4=>'avr.',5=>'mai',6=>'juin',7=>'juil.',8=>'août',9=>'sept.',10=>'oct.',11=>'nov.',12=>'déc.'];
      foreach ($formations_vedette as $f):
        $slug = (string)($f['slug'] ?? '');
        $lien = $slug !== '' ? '/formation/' . rawurlencode($slug) : '/formation.php?id=' . (int)$f['id'];
        $ts   = $f['date_debut'] ? strtotime((string)$f['date_debut']) : 0;
        $date = $ts ? date('j',$ts).' '.($frMois[(int)date('n',$ts)]??'').' '.date('Y',$ts) : '';
      ?>
        <a class="ip-fv-card" href="<?= htmlspecialchars($lien, ENT_QUOTES, 'UTF-8'); ?>">
          <?php if (!empty($f['domaine'])): ?>
            <span class="dom"><?= htmlspecialchars((string)$f['domaine'], ENT_QUOTES, 'UTF-8'); ?></span>
          <?php endif; ?>
          <span class="titre"><?= htmlspecialchars((string)$f['titre'], ENT_QUOTES, 'UTF-8'); ?></span>
          <div class="meta">
            <?php if ($date): ?><span>📅 <?= htmlspecialchars($date, ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?>
            <?php if (!empty($f['duree'])): ?><span>⏱ <?= htmlspecialchars((string)$f['duree'], ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?>
            <span class="go">→</span>
          </div>
        </a>
      <?php endforeach; ?>
    </div>

    <div style="text-align:center;margin-top:32px">
      <a href="/catalogue-formations.php" style="display:inline-flex;align-items:center;gap:8px;padding:14px 30px;border-radius:12px;background:var(--ip-accent);color:#fff;font-weight:800;font-size:15px;text-decoration:none">
        📚 Voir tout le catalogue de formations
      </a>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ══ FAQ ════════════════════════════════════════════ -->
<section class="ip-section" id="faq">
  <div class="ip-inner">
    <span class="ip-label">Questions fréquentes</span>
    <h2 class="ip-h2" style="text-align:center">Tout ce que vous devez savoir</h2>
    <p class="ip-lead" style="margin:0 auto 40px;text-align:center">Sur l'insertion, l'accompagnement et les garanties IBIG EDUFORM.</p>

    <div class="ip-faq">
      <?php
      $faqs = [
        ["L'accompagnement à l'insertion est-il inclus dans le prix de la formation ?",
         "Oui. L'accompagnement de base (coaching CV, simulation d'entretien, accès au jobboard, intégration alumni) est inclus dans toutes nos formations certifiantes. Des services premium sur mesure peuvent être souscrits en complément."],
        ["Combien de temps dure le suivi post-formation ?",
         "Nous assurons un suivi actif de 6 mois après la remise du certificat. Au-delà, vous restez membre à vie de la communauté alumni IBIG EDUFORM et accédez aux offres exclusives du réseau."],
        ["L'accompagnement est-il disponible pour les apprenants en dehors de Côte d'Ivoire ?",
         "Absolument. Tous nos services d'insertion sont disponibles à distance (visioconférence, WhatsApp, email) et couvrent les 17 pays de l'espace OHADA. Notre réseau est présent en Côte d'Ivoire, au Sénégal, au Mali, au Cameroun, au Bénin, au Togo et bien d'autres."],
        ["Comment fonctionne la mise en relation avec les entreprises partenaires ?",
         "Après validation de votre dossier de candidature, votre profil anonymisé est présenté à nos partenaires recruteurs du secteur concerné. En cas d'intérêt, nous organisons l'introduction directe. Vous avez également accès à notre jobboard privé réservé aux diplômés IBIG."],
        ["Je suis déjà en poste. L'insertion me concerne-t-elle ?",
         "Oui. L'insertion ne se limite pas au premier emploi. Nous accompagnons aussi les professionnels en poste qui souhaitent évoluer, changer de secteur, obtenir une promotion ou monter leur propre activité. Notre coaching carrière est adapté à votre situation."],
        ["Le certificat IBIG EDUFORM est-il reconnu par les entreprises ?",
         "Oui. Nos certifications sont reconnues partout en Afrique francophone et dans l'espace OHADA. Elles sont vérifiables en ligne via notre outil de vérification de certificats. Des centaines d'employeurs, d'ONG et d'institutions à travers l'Afrique francophone acceptent nos certificats dans leurs processus de recrutement."],
      ];
      foreach ($faqs as $i => $fq):
      ?>
        <div class="ip-faq-item" id="faq-<?= $i; ?>">
          <button class="ip-faq-q" aria-expanded="false" onclick="toggleFaq(this)">
            <?= htmlspecialchars($fq[0], ENT_QUOTES, 'UTF-8'); ?>
            <svg width="18" height="18" viewBox="0 0 18 18" fill="none"><path d="M9 3v12M3 9h12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
          </button>
          <div class="ip-faq-a"><?= htmlspecialchars($fq[1], ENT_QUOTES, 'UTF-8'); ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ══ CTA FINAL ══════════════════════════════════════ -->
<section class="ip-section" style="padding-top:0;padding-bottom:80px">
  <div class="ip-inner">
    <div class="ip-cta-final">
      <h2>Prêt à transformer votre formation en carrière&nbsp;?</h2>
      <p>Faites confiance à IBIG EDUFORM pour votre montée en compétences et votre insertion professionnelle dans l'espace OHADA.</p>
      <div class="ip-cta-btns">
        <a class="btn-cta-red" href="/preinscription.php">🚀 Démarrer ma formation</a>
        <a class="btn-cta-white" href="/catalogue-formations.php">📚 Voir le catalogue</a>
        <a class="btn-cta-outline" href="https://wa.me/2250778882592?text=<?= rawurlencode('Bonjour IBIG EDUFORM, je voudrais des informations sur votre accompagnement à l\'insertion professionnelle.'); ?>">📱 Nous contacter</a>
      </div>
    </div>
  </div>
</section>

<script>
function toggleFaq(btn) {
  var item = btn.closest('.ip-faq-item');
  var isOpen = item.classList.contains('open');
  document.querySelectorAll('.ip-faq-item.open').forEach(function(el){ el.classList.remove('open'); el.querySelector('button').setAttribute('aria-expanded','false'); });
  if (!isOpen) { item.classList.add('open'); btn.setAttribute('aria-expanded','true'); }
}
</script>

<script type="application/ld+json">
<?= json_encode([
  '@context'    => 'https://schema.org',
  '@type'       => 'EducationalOrganization',
  'name'        => 'IBIG EDUFORM',
  'url'         => 'https://ibig-eduform.com',
  'description' => $metaDescription,
  'areaServed'  => 'Espace OHADA — 17 pays',
  'hasOfferCatalog' => [
    '@type'       => 'OfferCatalog',
    'name'        => 'Formations certifiantes IBIG EDUFORM',
    'url'         => 'https://ibig-eduform.com/catalogue-formations.php',
  ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/partials/header.php';
echo $content;
require __DIR__ . '/partials/footer.php';
