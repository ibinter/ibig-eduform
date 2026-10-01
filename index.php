<?php
require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/core/promo.php';

$pageTitle    = "IBIG EDUFORM – Institut de formation professionnelle orienté résultats";
$ogDesc       = "IBIG EDUFORM — Formations professionnelles certifiantes en présentiel, en ligne et intra-entreprise. Plus de 1 000 professionnels formés dans 17 pays OHADA. Certifications reconnues en Afrique.";
$pageKeywords = "formation professionnelle Abidjan, certifications OHADA, formations en ligne Côte d'Ivoire, institut de formation Afrique, IBIG EDUFORM, formation management, formation comptabilité, formation RH";

$extraHead = <<<'JSONLD'
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@graph": [
    {
      "@type": "EducationalOrganization",
      "@id": "https://ibig-eduform.com/#organization",
      "name": "IBIG EDUFORM",
      "legalName": "INTERMARK BUSINESS INTERNATIONAL GROUP SARL",
      "url": "https://ibig-eduform.com/",
      "logo": "https://ibig-eduform.com/assets/images/logo.png",
      "image": "https://ibig-eduform.com/assets/images/logo.png",
      "description": "Institut de formation professionnelle et de certifications orientées impact, performance et employabilité en Afrique.",
      "address": {
        "@type": "PostalAddress",
        "addressLocality": "Abidjan",
        "addressCountry": "CI"
      },
      "telephone": "+2250778882592",
      "email": "formation@ibig-eduform.com",
      "sameAs": [
        "https://www.facebook.com/ibig.eduform",
        "https://www.linkedin.com/company/ibig-eduform",
        "https://www.ibigpartners.com/"
      ],
      "areaServed": {
        "@type": "GeoCircle",
        "name": "Zone OHADA — 17 pays d'Afrique francophone"
      }
    },
    {
      "@type": "WebSite",
      "@id": "https://ibig-eduform.com/#website",
      "url": "https://ibig-eduform.com/",
      "name": "IBIG EDUFORM",
      "publisher": {"@id": "https://ibig-eduform.com/#organization"},
      "potentialAction": {
        "@type": "SearchAction",
        "target": "https://ibig-eduform.com/catalogue-formations.php?q={search_term_string}",
        "query-input": "required name=search_term_string"
      }
    }
  ]
}
</script>
JSONLD;

include __DIR__ . '/partials/header.php';

$pdo = Database::connect();

$stats = [
  'apprenants' => setting_int('home_apprenants', 1000),
  'inseres'    => setting_int('home_inseres', 250),
  'pratique'   => setting_int('home_pratique', 80),
  'domaines'   => setting_int('home_domaines', 26),
];

/* =====================================================
   CALENDRIER DES FORMATIONS 2026 (CORRIGÉ)
===================================================== */

/**
 * Formatage date SAFE (anti-1970 / anti-warning)
 */
function formatDateSafe(?string $date): string
{
    if (!$date || trim($date) === '') {
        return '—'; // ou 'À confirmer'
    }

    $ts = strtotime($date);
    return $ts !== false ? date('d/m/Y', $ts) : '—';
}

$calendrier = [];

/* Source UNIQUE = table `formations` (celle gérée dans l'admin),
   pour que toute modif admin (ajout / édition / désactivation /
   suppression / date) se répercute ici. On affiche le programme
   RESTANT : formations actives dont la date n'est pas passée. */
/* Calendrier home : max 2 mois à partir du mois courant, 5 formations/mois max */
$stmt = $pdo->prepare("
    SELECT
        id AS formation_id,
        titre,
        domaine,
        date_debut,
        date_fin,
        duree,
        mode,
        statut,
        tarif_en_ligne,
        tarif_hybride,
        is_samedi_pro
    FROM formations
    WHERE statut = 'active'
      AND annee = YEAR(CURDATE())
      AND date_debut IS NOT NULL
      AND date_debut >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
      AND date_debut < DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL 2 MONTH), '%Y-%m-01')
    ORDER BY date_debut ASC
    LIMIT 14
");
$stmt->execute();

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

    // Sécurité absolue sur date_debut
    if (empty($row['date_debut'])) {
        continue;
    }

    // Clé mois (YYYY-MM)
    $mois = date('Y-m', strtotime($row['date_debut']));

    $calendrier[$mois][] = $row;
}

/* =====================================================
   DERNIERS ARTICLES BLOG
===================================================== */
$blogHome = [];
try {
  $blogHome = $pdo->query("
    SELECT a.id, a.titre, a.slug, a.contenu, a.created_at, c.nom AS cat
    FROM blog_articles a
    LEFT JOIN blog_categories c ON c.id = a.category_id
    WHERE a.statut = 'published'
    ORDER BY a.created_at DESC
    LIMIT 3
  ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) { $blogHome = []; }

/* =====================================================
   FAQ RAPIDE HOME
===================================================== */
$faqHome = [];
try {
  $faqHome = $pdo->query("SELECT question, reponse FROM faq WHERE actif=1 ORDER BY ordre ASC, id ASC LIMIT 5")->fetchAll(PDO::FETCH_NUM);
} catch (Throwable $e) { $faqHome = []; }
if (!$faqHome) {
  $faqHome = [
    ["Comment s'inscrire à une formation ?", "Remplissez le formulaire de préinscription en ligne ou contactez-nous sur WhatsApp (+225 07 78 88 25 92). Notre équipe vous confirme votre place sous 24h."],
    ["Quels sont les modes de paiement acceptés ?", "Nous acceptons Mobile Money (Orange, MTN, Moov), virement bancaire et paiement en ligne sécurisé via Moneroo. Le paiement en plusieurs fois est possible sur demande."],
    ["Les formations sont-elles certifiantes ?", "Oui. Chaque participant qui complète la formation reçoit un certificat IBIG EDUFORM reconnu partout en Afrique francophone et dans l'espace OHADA. Des co-certifications avec des institutions internationales sont disponibles sur certains parcours."],
    ["Puis-je suivre une formation depuis l'étranger ?", "Oui. La plupart de nos programmes sont disponibles en ligne (100% distanciel) ou en hybride, accessibles depuis tout le continent africain et la diaspora."],
    ["Y a-t-il des réductions disponibles ?", "Oui : tarif early bird (inscription tôt), réduction groupe (3+ personnes), et notre programme de parrainage vous offre 10% de remise pour chaque personne que vous nous recommandez."],
  ];
}

/* =====================================================
   FORMATIONS EN AVANT (HOME)
===================================================== */
$formationsHome = [];

try {
    $stmt = $pdo->query("
        SELECT
            id,
            slug,
            titre,
            domaine,
            duree,
            tarif_en_ligne,
            tarif_presentiel,
            tarif_hybride,
            date_debut,
            is_samedi_pro,
            capacite,
            inscriptions_actuelles
        FROM formations
        WHERE statut = 'active'
          AND annee = YEAR(CURDATE())
          AND date_debut IS NOT NULL
          AND date_debut > CURDATE()
        ORDER BY date_debut ASC
        LIMIT 6
    ");
    $formationsHome = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $formationsHome = [];
}

/* Places déjà payées (pour afficher le restant) — tolérant */
$paidHome = [];
try {
    $rows = $pdo->query("SELECT formation_id, COUNT(*) c FROM paiements_inscription WHERE statut='paye' GROUP BY formation_id")
                ->fetchAll(PDO::FETCH_KEY_PAIR);
    if (is_array($rows)) { $paidHome = $rows; }
} catch (Throwable $e) { $paidHome = []; }
?>

<!doctype html>
<html lang="fr">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
/* =====================================================
   THEME / BASE
===================================================== */
:root{
  --dark:#020617;
  --dark2:#071024;
  --blue:#0b3c5d;
  --blue2:#1e40af;
  --sky:#60a5fa;
  --text:#0f172a;
  --muted:#475569;
  --light:#f8fafc;
  --soft:#eef6ff;
  --glass: rgba(255,255,255,.10);
  --border: rgba(255,255,255,.14);
  --shadow: 0 20px 60px rgba(2,6,23,.35);
  --radius: 22px;
}

*{box-sizing:border-box}
main{margin:0;padding:0}
body{background:var(--light); color:var(--text); font-family:Inter,system-ui,sans-serif}

/* container */
.container{
  max-width:1280px;
  margin:0 auto;
  padding:0 22px;
}

/* sections */
.section{
  padding:96px 0;
}
.section-light{
  background:linear-gradient(180deg,var(--light),var(--soft));
  color:var(--text);
}
.section-dark{
  background:linear-gradient(160deg,#0b3c5d 0%,#071828 100%);
  color:#e5e7eb;
}
.section-title{
  font-size:2.4rem;
  line-height:1.15;
  margin:0 0 14px;
}
.section-subtitle{
  font-size:1.05rem;
  line-height:1.7;
  color:rgba(15,23,42,.72);
  max-width:900px;
}
.section-dark .section-subtitle{color:rgba(229,231,235,.82)}

.hr-soft{
  height:1px;
  background:linear-gradient(90deg, transparent, rgba(255,255,255,.18), transparent);
  margin:40px 0 0;
}

/* buttons */
.btn{
  display:inline-flex;
  align-items:center;
  justify-content:center;
  gap:10px;
  padding:14px 22px;
  border-radius:16px;
  font-weight:900;
  text-decoration:none;
  border:1px solid transparent;
  transition:transform .18s ease, box-shadow .18s ease, background .18s ease;
}
.btn:hover{transform:translateY(-2px)}
.btn-primary{
  background:linear-gradient(135deg,#2563eb,#1e40af);
  color:#fff;
  box-shadow:0 15px 40px rgba(37,99,235,.35);
}
.btn-outline{
  background:transparent;
  color:#fff;
  border-color:rgba(255,255,255,.65);
}
.btn-dark{
  background:#0f172a;
  color:#fff;
}
.btn-light{
  background:#fff;
  color:#0b3c5d;
  box-shadow:0 12px 30px rgba(2,6,23,.15);
}
.btn-accent{
  background:linear-gradient(135deg,#e8242c,#ff5763);
  color:#fff;
  box-shadow:0 15px 40px rgba(232,36,44,.35);
}
.btn-sm{padding:11px 16px;border-radius:13px;font-size:.92rem}

/* Session card (Prochaines sessions) — enrichie */
.session-meta{margin:10px 0 0;display:grid;gap:6px;font-size:.92rem;color:rgba(255,255,255,.86)}
.session-meta i{width:16px;text-align:center;color:#7ee2b8}
.session-price{margin:12px 0;display:flex;flex-wrap:wrap;gap:8px;align-items:center;font-weight:800}
.session-price .net{font-size:1.12rem}
.session-price .net s{font-weight:500;opacity:.55;font-size:.85rem;margin-right:6px}
.session-price .unit{font-weight:500;font-size:.76rem;opacity:.8}
.session-actions{margin-top:14px;display:flex;gap:10px;flex-wrap:wrap}
.session-actions .btn{flex:1;min-width:130px}
.pill-soon{padding:6px 11px;border-radius:999px;font-size:.74rem;font-weight:800;background:#fde68a;color:#92400e}
.pill-days{padding:6px 11px;border-radius:999px;font-size:.74rem;font-weight:800;background:#dbeafe;color:#1e40af}
.pill-left{padding:6px 11px;border-radius:999px;font-size:.74rem;font-weight:800;background:rgba(232,36,44,.16);color:#ffd1d4;border:1px solid rgba(232,36,44,.4)}

/* =====================================================
   HERO SLIDER (FULLSCREEN / DYNAMIQUE / PREMIUM)
===================================================== */
.hero{
  position:relative;
  height:100vh;
  min-height:760px;
  overflow:hidden;
  background:var(--dark);
}
.hero-slide{
  position:absolute;
  inset:0;
  background-size:cover;
  background-position:center;
  opacity:0;
  transform:translateX(100%);
  transition:transform .75s cubic-bezier(.77,0,.18,1), opacity .5s ease;
  will-change:transform,opacity;
}
.hero-slide.active{
  opacity:1;
  transform:translateX(0);
  z-index:2;
}
.hero-slide.leaving{
  opacity:0;
  transform:translateX(-100%);
  z-index:1;
}
.hero-slide.prev-enter{
  transform:translateX(-100%);
}
.hero-slide::before{
  content:'';
  position:absolute;
  inset:0;
  background:
    linear-gradient(120deg,rgba(11,60,93,.78),rgba(2,6,23,.92)),
    radial-gradient(circle at 25% 20%, rgba(96,165,250,.26), transparent 45%),
    radial-gradient(circle at 70% 60%, rgba(37,99,235,.18), transparent 50%);
}

.hero-content{
  position:relative;
  z-index:3;
  height:100%;
  display:flex;
  align-items:center;
}
.hero-grid{
  display:grid;
  grid-template-columns: 1.1fr .9fr;
  gap:34px;
  align-items:center;
}
.hero-copy{
  color:#fff;
  max-width:760px;
}
.hero-kicker{
  display:inline-flex;
  gap:10px;
  align-items:center;
  font-size:.78rem;
  letter-spacing:3px;
  text-transform:uppercase;
  opacity:.88;
  margin-bottom:14px;
}
.pill{
  display:inline-flex;
  padding:7px 12px;
  border-radius:999px;
  background:rgba(255,255,255,.10);
  border:1px solid rgba(255,255,255,.16);
  backdrop-filter:blur(10px);
}
.hero h1{
  font-size:3.9rem;
  line-height:1.06;
  margin:0 0 18px;
}
.hero h1 span{
  color:var(--sky);
}
.hero-lead{
  font-size:1.12rem;
  line-height:1.75;
  opacity:.95;
  margin:0 0 28px;
  max-width:700px;
}
.hero-actions{display:flex; gap:14px; flex-wrap:wrap}

/* Right "glass card" */
.hero-panel{
  background:rgba(255,255,255,.10);
  border:1px solid rgba(255,255,255,.16);
  backdrop-filter: blur(16px);
  border-radius:28px;
  padding:26px;
  box-shadow:0 25px 80px rgba(0,0,0,.35);
}
.hero-panel h3{
  color:#fff;
  margin:0 0 10px;
  font-size:1.15rem;
}
.hero-panel p{
  margin:0 0 16px;
  color:rgba(255,255,255,.85);
  line-height:1.6;
  font-size:.98rem;
}
.hero-bullets{
  margin:0;
  padding-left:18px;
  color:rgba(255,255,255,.88);
}
.hero-bullets li{margin:8px 0}

/* metrics */
.hero-metrics{
  position:absolute;
  left:50%;
  bottom:70px;
  transform:translateX(-50%);
  display:flex;
  gap:44px;
  z-index:5;
  width:min(980px, 92vw);
  justify-content:space-between;
}
.metric{
  text-align:center;
  color:#fff;
  min-width:140px;
}
.metric strong{
  display:block;
  font-size:2.05rem;
  letter-spacing:1px;
}
.metric span{
  font-size:.8rem;
  opacity:.92;
}

/* dots */
.hero-dots{
  position:absolute;
  left:50%;
  bottom:28px;
  transform:translateX(-50%);
  display:flex;
  gap:12px;
  z-index:6;
}
.hero-dot{
  width:14px;height:14px;border-radius:50%;
  background:rgba(255,255,255,.35);
  cursor:pointer;
  transition:transform .18s ease, background .18s ease, box-shadow .18s ease;
}
.hero-dot.active{
  background:var(--sky);
  box-shadow:0 0 0 6px rgba(96,165,250,.25);
  transform:scale(1.05);
}

/* =====================================================
   CARDS / GRIDS (LIGHT)
===================================================== */
.grid{
  display:grid;
  grid-template-columns:repeat(auto-fit,minmax(260px,1fr));
  gap:22px;
  margin-top:36px;
}
.card{
  background:#fff;
  border:1px solid rgba(2,6,23,.06);
  border-radius:var(--radius);
  padding:24px;
  box-shadow:0 16px 45px rgba(2,6,23,.08);
  transition:transform .18s ease, box-shadow .18s ease;
}
.card:hover{
  transform:translateY(-4px);
  box-shadow:0 26px 70px rgba(2,6,23,.12);
}
.card h3{
  margin:0 0 10px;
  font-size:1.15rem;
  color:#0b3c5d;
}
.card p{margin:0;color:rgba(15,23,42,.74); line-height:1.65}

/* dark cards */
.card-dark{
  background:rgba(255,255,255,.08);
  border:1px solid rgba(255,255,255,.14);
  color:#fff;
  box-shadow:0 22px 70px rgba(0,0,0,.35);
}
.card-dark h3{color:#fff}
.card-dark p{color:rgba(255,255,255,.85)}

/* =====================================================
   DOMAINS GRID (chips)
===================================================== */
.chips{
  display:flex;
  flex-wrap:wrap;
  gap:10px;
  margin-top:18px;
}
.chip{
  display:inline-flex;
  align-items:center;
  gap:8px;
  padding:10px 14px;
  border-radius:999px;
  background:rgba(2,6,23,.06);
  border:1px solid rgba(2,6,23,.08);
  color:#0f172a;
  font-weight:800;
  font-size:.9rem;
}
.section-dark .chip{
  background:rgba(255,255,255,.10);
  border-color:rgba(255,255,255,.14);
  color:#fff;
}

/* =====================================================
   PROCESS STEPS (premium)
===================================================== */
.steps{
  display:grid;
  grid-template-columns:repeat(auto-fit,minmax(240px,1fr));
  gap:20px;
  margin-top:34px;
}
.step{
  border-radius:24px;
  padding:22px;
  background:#fff;
  border:1px solid rgba(2,6,23,.06);
  box-shadow:0 16px 45px rgba(2,6,23,.08);
  position:relative;
  overflow:hidden;
}
.step::before{
  content:'';
  position:absolute; inset:auto -40px -40px auto;
  width:180px;height:180px;border-radius:50%;
  background:radial-gradient(circle, rgba(37,99,235,.18), transparent 55%);
}
.step-badge{
  width:44px;height:44px;border-radius:14px;
  display:flex;align-items:center;justify-content:center;
  background:linear-gradient(135deg,#2563eb,#1e40af);
  color:#fff;font-weight:900;
  box-shadow:0 12px 30px rgba(37,99,235,.25);
  margin-bottom:12px;
}
.step h3{margin:0 0 8px;color:#0b3c5d}
.step p{margin:0;color:rgba(15,23,42,.72);line-height:1.6}

/* =====================================================
   CTA BLOCKS
===================================================== */
.cta-wide{
  border-radius:28px;
  padding:52px 34px;
  background:linear-gradient(135deg,#2563eb,#0b3c5d);
  color:#fff;
  box-shadow:0 25px 90px rgba(2,6,23,.35);
  overflow:hidden;
  position:relative;
}
.cta-wide::before{
  content:'';
  position:absolute; inset:-120px auto auto -120px;
  width:260px;height:260px;border-radius:50%;
  background:radial-gradient(circle, rgba(255,255,255,.18), transparent 60%);
}
.cta-wide h2{margin:0 0 12px;font-size:2.2rem}
.cta-wide p{margin:0;color:rgba(255,255,255,.88);line-height:1.7}
.cta-actions{margin-top:22px; display:flex; gap:12px; flex-wrap:wrap}

/* =====================================================
   TESTIMONIALS
===================================================== */
.quote{
  font-size:1.05rem;
  line-height:1.75;
  color:rgba(15,23,42,.78);
}
.person{
  margin-top:14px;
  font-weight:900;
  color:#0b3c5d;
  font-size:.95rem;
}
.section-dark .quote{color:rgba(255,255,255,.86)}
.section-dark .person{color:#fff}

/* =====================================================
   REVEAL ANIMATIONS
===================================================== */
.reveal{
  opacity:0;
  transform:translateY(18px);
  transition:opacity .6s ease, transform .6s ease;
}
.reveal.in{
  opacity:1;
  transform:translateY(0);
}

/* =====================================================
   RESPONSIVE
===================================================== */
@media(max-width:980px){
  .hero-grid{grid-template-columns:1fr}
  .hero h1{font-size:2.65rem}
  .hero-metrics{gap:18px; flex-wrap:wrap; justify-content:center}
  .metric{min-width:120px}
  .section{padding:76px 0}
}

/* =====================================================
   BANDEAU ANNONCE
===================================================== */
.announce-bar{
  background:linear-gradient(90deg,#1e40af,#0b3c5d,#1e40af);
  background-size:200% 100%;
  animation:abSlide 6s linear infinite;
  color:#fff;
  padding:11px 22px;
  text-align:center;
  font-size:.88rem;
  font-weight:700;
  letter-spacing:.02em;
  position:relative;
  z-index:200;
}
@keyframes abSlide{0%{background-position:0% 0%}100%{background-position:200% 0%}}
.announce-bar a{color:#fde68a;text-decoration:underline;margin-left:8px}
.announce-bar .ab-sep{opacity:.4;margin:0 12px}

/* =====================================================
   SECTION CERTIFICATIONS / LIVRABLES
===================================================== */
.cert-grid{
  display:grid;
  grid-template-columns:repeat(auto-fit,minmax(260px,1fr));
  gap:22px;
  margin-top:40px;
}
.cert-card{
  border-radius:24px;
  padding:30px 26px;
  background:#fff;
  border:1px solid rgba(2,6,23,.07);
  box-shadow:0 18px 50px rgba(2,6,23,.09);
  display:flex;
  flex-direction:column;
  gap:12px;
  transition:.2s;
}
.cert-card:hover{transform:translateY(-4px);box-shadow:0 28px 70px rgba(2,6,23,.13)}
.cert-icon{
  width:56px;height:56px;border-radius:18px;
  display:flex;align-items:center;justify-content:center;
  font-size:1.6rem;
  flex-shrink:0;
}
.cert-card h3{margin:0;font-size:1.1rem;color:#0b3c5d;font-weight:900}
.cert-card p{margin:0;font-size:.93rem;color:#475569;line-height:1.6}
.cert-tag{
  display:inline-flex;align-items:center;gap:6px;
  padding:5px 12px;border-radius:999px;
  font-size:.78rem;font-weight:800;
  background:#eff6ff;color:#1e40af;
  border:1px solid rgba(30,64,175,.15);
  align-self:flex-start;
}

/* =====================================================
   SECTION BLOG HOME
===================================================== */
.blog-home-grid{
  display:grid;
  grid-template-columns:repeat(auto-fit,minmax(290px,1fr));
  gap:22px;
  margin-top:36px;
}
.bh-card{
  background:#fff;
  border:1px solid rgba(2,6,23,.07);
  border-radius:22px;
  overflow:hidden;
  box-shadow:0 16px 45px rgba(2,6,23,.08);
  text-decoration:none;
  color:inherit;
  display:flex;flex-direction:column;
  transition:.2s;
}
.bh-card:hover{transform:translateY(-5px);box-shadow:0 26px 65px rgba(2,6,23,.13)}
.bh-band{
  height:80px;
  position:relative;
}
.bh-band::after{content:'';position:absolute;inset:0;background:radial-gradient(circle at 30% 40%,rgba(255,255,255,.18),transparent)}
.bh-body{padding:20px 22px 24px;flex:1;display:flex;flex-direction:column;gap:8px}
.bh-cat{font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:#2563eb}
.bh-title{font-size:1.05rem;font-weight:800;color:#0f172a;line-height:1.35;margin:0}
.bh-excerpt{font-size:.88rem;color:#475569;line-height:1.6;margin:0;flex:1}
.bh-meta{font-size:.78rem;color:#94a3b8;font-weight:600;margin-top:auto;padding-top:10px;border-top:1px solid rgba(2,6,23,.06)}
.bh-read{display:inline-flex;align-items:center;gap:6px;font-size:.88rem;font-weight:800;color:#2563eb;margin-top:8px}

/* =====================================================
   FAQ HOME
===================================================== */
.faq-home{
  max-width:860px;
  margin:40px auto 0;
  display:flex;flex-direction:column;gap:12px;
}
.fqh-item{
  background:#fff;
  border:1px solid rgba(2,6,23,.07);
  border-radius:18px;
  overflow:hidden;
  box-shadow:0 8px 28px rgba(2,6,23,.06);
}
.fqh-q{
  padding:18px 22px;
  display:flex;align-items:center;justify-content:space-between;gap:14px;
  cursor:pointer;font-weight:800;font-size:.97rem;color:#0f172a;
}
.fqh-icon{
  width:28px;height:28px;border-radius:50%;
  background:linear-gradient(135deg,#2563eb,#1e40af);
  color:#fff;font-weight:900;font-size:1rem;
  display:flex;align-items:center;justify-content:center;
  flex-shrink:0;transition:.25s;
}
.fqh-item.open .fqh-icon{transform:rotate(45deg)}
.fqh-a{max-height:0;overflow:hidden;transition:max-height .35s ease}
.fqh-a-inner{padding:0 22px 20px;font-size:.93rem;line-height:1.75;color:#475569}

/* =====================================================
   PARRAINAGE CTA
===================================================== */
.parrainage-wrap{
  border-radius:28px;
  background:linear-gradient(135deg,#f5a623 0%,#f97316 50%,#e8242c 100%);
  padding:52px 44px;
  color:#fff;
  position:relative;overflow:hidden;
  box-shadow:0 30px 80px rgba(245,166,35,.35);
}
.parrainage-wrap::before{
  content:'';position:absolute;right:-80px;top:-80px;
  width:280px;height:280px;border-radius:50%;
  background:rgba(255,255,255,.12);
}
.parrainage-wrap h2{margin:0 0 10px;font-size:2.1rem;font-weight:900}
.parrainage-wrap p{margin:0 0 22px;font-size:1.05rem;opacity:.95;max-width:660px;line-height:1.6}
.parrainage-badge{
  display:inline-flex;align-items:center;gap:8px;
  background:rgba(255,255,255,.2);
  border:1px solid rgba(255,255,255,.35);
  padding:10px 18px;border-radius:999px;
  font-size:.88rem;font-weight:800;margin-bottom:18px;
}

/* =====================================================
   CALENDRIER 2026
===================================================== */
.calendar-grid{
  display:grid;
  grid-template-columns:repeat(auto-fit,minmax(280px,1fr));
  gap:26px;
  margin-top:40px;
}

.calendar-month{
  background:#fff;
  border-radius:26px;
  padding:26px;
  box-shadow:0 20px 55px rgba(2,6,23,.10);
}

.calendar-month h3{
  margin:0 0 18px;
  font-size:1.4rem;
  color:#0b3c5d;
  border-bottom:1px solid rgba(2,6,23,.08);
  padding-bottom:10px;
}

.calendar-item{
  padding:16px 14px;
  border-radius:16px;
  background:linear-gradient(135deg,#f8fafc,#eef6ff);
  margin-bottom:14px;
  border:1px solid rgba(2,6,23,.06);
}

.calendar-item h4{
  margin:0 0 6px;
  font-size:1.05rem;
  color:#0f172a;
}

.calendar-meta{
  font-size:.85rem;
  color:#475569;
  line-height:1.5;
}

.calendar-badges{
  display:flex;
  gap:8px;
  margin-top:10px;
  flex-wrap:wrap;
}

.badge{
  padding:6px 10px;
  border-radius:999px;
  font-size:.75rem;
  font-weight:800;
}

.badge-mode{background:#e0f2fe;color:#075985}
.badge-duree{background:#ecfeff;color:#155e75}

.badge-statut{
  background:#fde68a;
  color:#92400e;
}

.badge-statut.en_cours{background:#bbf7d0;color:#166534}
.badge-statut.termine{background:#fecaca;color:#7f1d1d}

.calendar-actions{
  margin-top:14px;
}

.calendar-actions a{
  font-size:.8rem;
  font-weight:900;
  text-decoration:none;
  color:#2563eb;
}

.calendar-month-title{
  color:#e11d48; /* rouge-orangé élégant */
  font-weight:900;
  letter-spacing:.5px;
}

.calendar-month-title{
  color:#e11d48;
  font-weight:900;
  letter-spacing:.5px;
  border-bottom:2px solid rgba(225,29,72,.25);
  padding-bottom:6px;
}

/* =========================================
   NETTOYAGE MOBILE — IBIG EDUFORM
========================================= */
@media (max-width: 768px) {

  /* Masquer UNIQUEMENT les pills/badges DU HERO sur mobile
     (ne plus masquer les .chip / .badge du reste du site) */
  .hero .hero-badges,
  .hero .hero-tags,
  .hero .pill {
    display: none !important;
  }

  /* Masquer stats en overlay */
  .hero .stats,
  .hero-stats,
  .overlay-stats,
  .floating-stats {
    display: none !important;
  }

  /* Masquer pagination slider (points) */
  .swiper-pagination,
  .carousel-dots {
    display: none !important;
  }
}

@media (max-width: 768px) {

  .stats-section {
    padding: 40px 16px;
    background: #0b1220;
  }

  .stats {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
  }

  .stat-box {
    background: rgba(255,255,255,0.06);
    border-radius: 14px;
    padding: 16px;
    text-align: center;
  }

  .stat-number {
    font-size: 1.6rem;
    font-weight: 700;
  }

  .stat-label {
    font-size: 0.85rem;
    opacity: 0.85;
  }
}

@media (max-width: 768px) {

  .hero {
    padding: 90px 16px 50px;
  }

  .hero h1 {
    font-size: 1.9rem;
    line-height: 1.25;
  }

  .hero p {
    font-size: 0.95rem;
    line-height: 1.6;
    margin-top: 12px;
  }

  .hero-cta {
    display: flex;
    flex-direction: column;
    gap: 14px;
    margin-top: 24px;
  }

  .hero-cta a {
    width: 100%;
    text-align: center;
  }
}

/* =========================================
   MASQUER LES CHIFFRES SUR MOBILE
========================================= */
@media (max-width: 768px) {

  /* Chiffres (1000+, 250+, 80%, 26+) */
  .stat-number,
  .stats-numbers,
  .counter,
  .kpi-number {
    display: none !important;
  }

}

/* ==================================================
   MASQUER LES KPI HERO SUR MOBILE (DÉFINITIF)
================================================== */
@media (max-width: 768px) {
  .hero-metrics {
    display: none !important;
  }
}

@media (max-width: 768px) {

  .page-hero,
  .hero,
  .hero-inner {
    position: relative !important;
    top: auto !important;
  }

}

</style>

<main>

<!-- BANDEAU ANNONCE -->
<div class="announce-bar">
  🎓 Inscriptions ouvertes — Programme <?php
$_mfr=['','Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'];
$_m=(int)date('n'); $_y=(int)date('Y');
$_fq=$_m<=3?3:($_m<=6?6:($_m<=9?9:12));
echo $_mfr[$_m].' → '.$_mfr[$_fq].' '.$_y;
?>
  <span class="ab-sep">|</span>
  🐦 Tarif early bird disponible sur certaines formations
  <span class="ab-sep">|</span>
  <a href="/preinscription-generale.php">S'inscrire maintenant →</a>
</div>

<!-- =====================================================
  HERO — 4 SLIDES (images + contenu)
===================================================== -->
<section class="hero" id="top">

  <?php
  $slides = [
    [
      'img'=>'hero-1.jpg',
      'kicker'=>'Institut de formation professionnelle',
      'title'=>'Former des professionnels <span>immédiatement opérationnels</span>',
      'lead'=>"Des formations certifiantes, pratiques et orientées résultats, employabilité et impact terrain.",
      'btn1'=>['href'=>'formations.php','label'=>'Explorer les formations','class'=>'btn btn-primary'],
      'btn2'=>['href'=>'preinscription.php','label'=>'Se préinscrire','class'=>'btn btn-outline'],
      'panel_title'=>"Ce qui nous différencie",
      'panel_text'=>"Une pédagogie pragmatique : cas réels, outils métiers, évaluations et accompagnement.",
      'panel_list'=>['80% pratique terrain','Formateurs experts en activité','Certifications métiers modulaires','Insertion & suivi'],
    ],
    [
      'img'=>'hero-2.jpg',
      'kicker'=>'Pédagogie innovante',
      'title'=>'Des compétences <span>utiles dès le premier jour</span>',
      'lead'=>"Apprendre vite, appliquer tout de suite : mises en situation, projets guidés, outils professionnels.",
      'btn1'=>['href'=>'a-propos.php','label'=>'Notre approche','class'=>'btn btn-primary'],
      'btn2'=>['href'=>'entreprises.php','label'=>'Former mon équipe','class'=>'btn btn-outline'],
      'panel_title'=>"Formats flexibles",
      'panel_text'=>"Présentiel • Hybride • Distanciel — adaptés aux entreprises, ONG et institutions.",
      'panel_list'=>['Intra-entreprise sur mesure','Programmes certifiants','Renforcement de capacités','Co-certification'],
    ],
    [
      'img'=>'hero-3.jpg',
      'kicker'=>'Insertion professionnelle',
      'title'=>'Former pour <span>l’emploi et la performance</span>',
      'lead'=>"Stages, missions, emploi, entrepreneuriat : une formation utile qui ouvre des opportunités.",
      'btn1'=>['href'=>'preinscription.php','label'=>'Démarrer mon parcours','class'=>'btn btn-primary'],
      'btn2'=>['href'=>'contact.php','label'=>'Parler à un conseiller','class'=>'btn btn-outline'],
      'panel_title'=>"Objectif : impact",
      'panel_text'=>"Notre priorité : des compétences mesurables et valorisables dès la fin de la session.",
      'panel_list'=>['Coaching carrière','Projets & cas concrets','Évaluations rigoureuses','Réseau partenaires'],
    ],
    [
      'img'=>'hero-4.jpg',
      'kicker'=>'Vision futuriste',
      'title'=>"L’avenir des compétences <span>commence maintenant</span>",
      'lead'=>"Préparer les talents aux métiers d’aujourd’hui et de demain : digital, IA, data, performance.",
      'btn1'=>['href'=>'formations.php','label'=>'Voir le catalogue 2026','class'=>'btn btn-primary'],
      'btn2'=>['href'=>'partenaires.php','label'=>'Devenir partenaire','class'=>'btn btn-outline'],
      'panel_title'=>"Institut du groupe IBIG",
      'panel_text'=>"IBIG EDUFORM est le pôle formation professionnelle d’INTERMARK BUSINESS INTERNATIONAL GROUP SARL.",
      'panel_list'=>['Institutionnel & structuré','Qualité & rigueur','Innovation pédagogique','Vision africaine'],
    ],
  ];

  /* Carrousel administrable : si des slides existent en base, ils remplacent
     le tableau ci-dessus. Sinon, repli automatique sur ce contenu figé. */
  $dbSlides = function_exists('hero_slides_db') ? hero_slides_db() : [];
  if ($dbSlides) {
    $slides = array_map(static function (array $r): array {
      return [
        'img'         => $r['image'] ?: 'hero-1.jpg',
        'kicker'      => $r['kicker'] ?? '',
        'title'       => $r['title'] ?? '',
        'lead'        => $r['lead'] ?? '',
        'btn1'        => ['href'=>$r['btn1_href'] ?? '#', 'label'=>$r['btn1_label'] ?? '', 'class'=>'btn btn-primary'],
        'btn2'        => ['href'=>$r['btn2_href'] ?? '#', 'label'=>$r['btn2_label'] ?? '', 'class'=>'btn btn-outline'],
        'panel_title' => $r['panel_title'] ?? '',
        'panel_text'  => $r['panel_text'] ?? '',
        'panel_list'  => array_values(array_filter(array_map('trim',
                           preg_split('/\r\n|\r|\n/', (string)($r['panel_list'] ?? ''))))),
      ];
    }, $dbSlides);
  }
  ?>

  <?php foreach($slides as $i=>$s): ?>
    <?php $heroWebp = preg_replace('/\.jpe?g$/i', '.webp', $s['img']); ?>
    <div class="hero-slide <?= $i===0?'active':'' ?>"
         style="background-image:url('/assets/images/hero/<?= htmlspecialchars($s['img']) ?>');background-image:image-set(url('/assets/images/hero/<?= htmlspecialchars($heroWebp) ?>') type('image/webp'), url('/assets/images/hero/<?= htmlspecialchars($s['img']) ?>') type('image/jpeg'))"
         data-parallax="1">
      <div class="container hero-content">
        <div class="hero-grid">
          <div class="hero-copy">
            <div class="hero-kicker">
              <span class="pill">IBIG EDUFORM</span>
              <span><?= htmlspecialchars($s['kicker']) ?></span>
            </div>
            <h1><?= $s['title'] ?></h1>
            <p class="hero-lead"><?= htmlspecialchars($s['lead']) ?></p>
            <div class="hero-actions">
              <?php if (trim((string)$s['btn1']['label']) !== ''): ?>
              <a class="<?= $s['btn1']['class'] ?>" href="<?= htmlspecialchars($s['btn1']['href']) ?>"><?= htmlspecialchars($s['btn1']['label']) ?></a>
              <?php endif; ?>
              <?php if (trim((string)$s['btn2']['label']) !== ''): ?>
              <a class="<?= $s['btn2']['class'] ?>" href="<?= htmlspecialchars($s['btn2']['href']) ?>"><?= htmlspecialchars($s['btn2']['label']) ?></a>
              <?php endif; ?>
            </div>
          </div>

          <aside class="hero-panel">
            <h3><?= htmlspecialchars($s['panel_title']) ?></h3>
            <p><?= htmlspecialchars($s['panel_text']) ?></p>
            <ul class="hero-bullets">
              <?php foreach($s['panel_list'] as $li): ?>
                <li><?= htmlspecialchars($li) ?></li>
              <?php endforeach; ?>
            </ul>
          </aside>
        </div>
      </div>
    </div>
  <?php endforeach; ?>

  <!-- METRICS (COUNT-UP) -->
  <div class="hero-metrics" id="heroMetrics">
    <div class="metric">
      <strong data-target="<?= (int)$stats['apprenants'] ?>" data-suffix="+">0</strong>
      <span>Apprenants</span>
    </div>
    <div class="metric">
      <strong data-target="<?= (int)$stats['inseres'] ?>" data-suffix="+">0</strong>
      <span>Insérés</span>
    </div>
    <div class="metric">
      <strong data-target="<?= (int)$stats['pratique'] ?>" data-suffix="%">0</strong>
      <span>Pratique</span>
    </div>
    <div class="metric">
      <strong data-target="<?= (int)$stats['domaines'] ?>" data-suffix="+">0</strong>
      <span>Domaines</span>
    </div>
  </div>

  <!-- DOTS -->
  <div class="hero-dots" aria-label="Navigation slider">
    <?php foreach ($slides as $i => $s): ?>
      <span class="hero-dot <?= $i===0?'active':'' ?>" data-i="<?= $i ?>"></span>
    <?php endforeach; ?>
  </div>

</section>

<!-- =====================================================
  SECTION DARK — Présentation de l’institut
===================================================== -->
<section class="section section-dark">
  <div class="container">
    <h2 class="section-title reveal">IBIG EDUFORM — Institut de formation professionnelle</h2>
    <p class="section-subtitle reveal">
      IBIG EDUFORM est le pôle formation professionnelle du Groupe <strong>INTERMARK BUSINESS INTERNATIONAL GROUP (IBIG SARL)</strong>,
      acteur panafricain présent dans <strong>17 pays de l’espace OHADA</strong>.
      L’institut conçoit et délivre des programmes certifiants, pratiques et orientés résultats — pour les particuliers,
      les entreprises, les ONG et les institutions publiques.
    </p>

    <div class="grid reveal">
      <div class="card card-dark">
        <h3>🎯 Notre mission</h3>
        <p>Former des professionnels compétents, directement employables, capables de créer de la valeur dès leur premier jour en poste.</p>
      </div>
      <div class="card card-dark">
        <h3>🌍 Notre vision</h3>
        <p>Devenir le référentiel de la formation professionnelle dans l’espace OHADA — qualité africaine, exigence internationale.</p>
      </div>
      <div class="card card-dark">
        <h3>🏛️ Notre ancrage</h3>
        <p>Siège à Abidjan (Cocody), antennes dans les principales villes. Programmes accessibles en présentiel, hybride et en ligne.</p>
      </div>
      <div class="card card-dark">
        <h3>🤝 Nos publics</h3>
        <p>Jeunes diplômés, cadres en reconversion, équipes d’entreprises, agents d’ONG, institutions et administrations publiques.</p>
      </div>
    </div>

    <div class="hr-soft" style="border-color:rgba(255,255,255,.12);margin-top:36px"></div>

    <div class="hero-metrics reveal" style="position:static;background:none;padding:24px 0 8px;justify-content:center">
      <div class="metric"><strong>17</strong><span>Pays OHADA</span></div>
      <div class="metric"><strong>1 000+</strong><span>Professionnels formés</span></div>
      <div class="metric"><strong>80%</strong><span>Pratique</span></div>
      <div class="metric"><strong><?= (int)$stats['domaines'] ?>+</strong><span>Domaines</span></div>
    </div>

    <div style="margin-top:24px;text-align:center" class="reveal">
      <a class="btn btn-primary" href="a-propos.php">En savoir plus sur l’institut</a>
      <a class="btn btn-outline" style="margin-left:10px" href="preinscription-generale.php">Se préinscrire</a>
    </div>

  </div>
</section>

<!-- =====================================================
  SECTION LIGHT — Ce qui nous différencie
===================================================== -->
<section class="section section-light" style="padding-top:0">
  <div class="container">
    <h2 class="section-title reveal">Ce qui nous différencie</h2>
    <p class="section-subtitle reveal">
      Une pédagogie pragmatique, des formateurs experts, une insertion accompagnée — des engagements concrets, pas des promesses.
    </p>

    <div class="grid">
      <div class="card reveal">
        <h3>Orientation résultats</h3>
        <p>Compétences mesurables, immédiatement exploitables, basées sur des cas réels issus du terrain professionnel africain.</p>
      </div>
      <div class="card reveal">
        <h3>Experts terrain</h3>
        <p>Nos formateurs sont des professionnels en activité — directeurs, consultants, praticiens — qui transmettent ce qu’ils vivent.</p>
      </div>
      <div class="card reveal">
        <h3>Certifications métiers</h3>
        <p>Certificats modulaires reconnus, progressifs et valorisables auprès des employeurs, ONG et partenaires institutionnels.</p>
      </div>
      <div class="card reveal">
        <h3>Employabilité réelle</h3>
        <p>Stages, coaching carrière, mise en réseau et suivi post-formation : l’accompagnement ne s’arrête pas à la certification.</p>
      </div>
      <div class="card reveal">
        <h3>Flexibilité totale</h3>
        <p>Présentiel à Abidjan, hybride, ou 100 % en ligne — des formats adaptés à chaque rythme et à chaque ville d’Afrique.</p>
      </div>
      <div class="card reveal">
        <h3>Offre entreprises</h3>
        <p>Formations intra-entreprise sur mesure, renforcement de capacités, co-certification : un interlocuteur unique, une réponse complète.</p>
      </div>
    </div>
  </div>
</section>

<!-- =====================================================
  SECTION LIGHT — Ce que vous obtenez (certifications)
===================================================== -->
<section class="section section-light" style="padding-top:0">
  <div class="container">
    <h2 class="section-title reveal">Ce que vous obtenez à l'issue de la formation</h2>
    <p class="section-subtitle reveal">Des livrables concrets, valorisables immédiatement auprès des employeurs, ONG et partenaires.</p>

    <div class="cert-grid">
      <div class="cert-card reveal">
        <div class="cert-icon" style="background:#eff6ff">🎓</div>
        <h3>Certificat professionnel IBIG EDUFORM</h3>
        <p>Délivré à tout participant ayant validé les évaluations. Reconnu partout en Afrique francophone et dans l'espace OHADA.</p>
        <span class="cert-tag">✅ Inclus dans toutes les formations</span>
      </div>
      <div class="cert-card reveal">
        <div class="cert-icon" style="background:#f0fdf4">📋</div>
        <h3>Supports de cours & outils métiers</h3>
        <p>Chaque participant repart avec les slides, templates, checklists et outils pratiques utilisés pendant la formation — exploitables dès le retour au bureau.</p>
        <span class="cert-tag">📦 Remis en fin de session</span>
      </div>
      <div class="cert-card reveal">
        <div class="cert-icon" style="background:#fef9ec">🏆</div>
        <h3>Co-certification internationale</h3>
        <p>Sur certains parcours, une co-certification avec des institutions ou organismes internationaux est disponible. Renseignez-vous auprès de notre équipe.</p>
        <span class="cert-tag">🌍 Sur certains programmes</span>
      </div>
      <div class="cert-card reveal">
        <div class="cert-icon" style="background:#fdf4ff">🔗</div>
        <h3>Accès au réseau alumni IBIG</h3>
        <p>Intégrez la communauté des anciens participants : événements networking, offres d'emploi partenaires, groupes métiers sur WhatsApp et LinkedIn.</p>
        <span class="cert-tag">🤝 À vie</span>
      </div>
      <div class="cert-card reveal">
        <div class="cert-icon" style="background:#fff1f2">📊</div>
        <h3>Évaluation des compétences acquises</h3>
        <p>Un rapport individuel d'évaluation vous est remis : points forts, axes de progrès et recommandations pour aller plus loin dans votre parcours professionnel.</p>
        <span class="cert-tag">📝 Personnalisé</span>
      </div>
      <div class="cert-card reveal">
        <div class="cert-icon" style="background:#ecfeff">🎯</div>
        <h3>Coaching & suivi post-formation</h3>
        <p>30 jours de suivi après la formation : questions, mise en pratique, orientation professionnelle. Notre équipe reste disponible pour vous accompagner.</p>
        <span class="cert-tag">📞 30 jours inclus</span>
      </div>
    </div>
  </div>
</section>

<!-- =====================================================
  SECTION DARK — Domaines clés (MANQUANT AVANT)
===================================================== -->
<section class="section section-dark">
  <div class="container">
    <h2 class="section-title reveal">Nos domaines de formation</h2>
    <p class="section-subtitle reveal">
      Des programmes structurés sur les compétences demandées par le marché : entreprises, administrations, ONG, projets.
    </p>

    <div class="chips reveal">
      <?php
        /* Domaines dérivés automatiquement des formations actives (repli : liste par défaut) */
        $domaines = [];
        try {
          /* Liste canonique propre — remplace les valeurs incohérentes de la DB */
          $domaines = [
            'Finance & Comptabilité','Audit & Contrôle de Gestion','Ressources Humaines & Paie',
            'QHSE','Logistique & Supply Chain','Gestion de Projets & ONG',
            'Marketing Digital & Communication','Droit & Administration',
            'Logiciels de Gestion (Sage, SAP, Odoo)','Intelligence Artificielle',
            'Immobilier & BTP','Entrepreneuriat & Management',
          ];
        } catch (Throwable $e) { $domaines = []; }
        if (!$domaines) {
          $domaines = ['Finance & Comptabilité','Audit & Contrôle de Gestion','Ressources Humaines & Management','QHSE','Logistique & Supply Chain','Gestion de Projets & ONG','Communication & Relations Publiques','Droit & Conformité','Banque & Assurance','Immobilier & BTP','Digital, Data & IA','Entrepreneuriat & Croissance'];
        }
        foreach ($domaines as $dom): ?>
          <span class="chip"><?= htmlspecialchars($dom) ?></span>
      <?php endforeach; ?>
    </div>

    <div style="margin-top:34px" class="reveal">
      <a class="btn btn-primary" href="formations.php">Voir toutes les formations</a>
      <a class="btn btn-outline" href="entreprises.php">Former une organisation</a>
    </div>
  </div>
</section>

<!-- =====================================================
  SECTION LIGHT — Approche pédagogique (refonte)
===================================================== -->
<section class="section section-light">
  <div class="container">
    <h2 class="section-title reveal">L’approche pédagogique IBIG EDUFORM</h2>
    <p class="section-subtitle reveal">
      Un modèle pragmatique orienté compétences : théorie essentielle + pratique intensive + certification + insertion.
    </p>

    <div class="steps">
      <div class="step reveal">
        <div class="step-badge">1</div>
        <h3>Analyse des besoins</h3>
        <p>Diagnostic du profil, des objectifs, et des exigences terrain pour un parcours ciblé.</p>
      </div>
      <div class="step reveal">
        <div class="step-badge">2</div>
        <h3>Formation intensive</h3>
        <p>Cas réels, simulations, outils métiers, travaux guidés et correction professionnelle.</p>
      </div>
      <div class="step reveal">
        <div class="step-badge">3</div>
        <h3>Évaluation & certification</h3>
        <p>Validation des compétences, évaluations rigoureuses, certificat valorisable.</p>
      </div>
      <div class="step reveal">
        <div class="step-badge">4</div>
        <h3>Insertion & suivi</h3>
        <p>Stages, missions, coaching carrière et orientation : employabilité réelle.</p>
      </div>
    </div>

    <div style="margin-top:40px" class="cta-wide reveal">
      <h2>Vous êtes une entreprise, ONG ou institution ?</h2>
      <p>Nous concevons des formations sur mesure : intra-entreprise, renforcement de capacités, co-certification.</p>
      <div class="cta-actions">
        <a class="btn btn-light" href="entreprises.php">Découvrir l’offre entreprises</a>
        <a class="btn btn-outline" href="contact.php">Demander une proposition</a>
      </div>
    </div>
  </div>
</section>

<!-- =====================================================
  SECTION DARK — Formations en vedette (si dispo DB)
===================================================== -->
<section class="section section-dark">
  <div class="container">
    <h2 class="section-title reveal">Prochaines sessions</h2>
    <p class="section-subtitle reveal">Les formations qui démarrent bientôt. Réservez votre place — les inscriptions sont ouvertes.</p>

    <div class="grid">
      <?php if(count($formationsHome) > 0): ?>
        <?php foreach($formationsHome as $f):
          $promoH = promo_earlybird($f);
          $isSP   = !empty($f['is_samedi_pro']);
          $prix   = $isSP ? 0 : (int)$f['tarif_en_ligne'];
          $presen = (int)($f['tarif_presentiel'] ?? 0);
          $ref    = (int)($f['tarif_hybride'] ?? 0); // prix de référence barré si gratuit
          $net    = ($promoH['eligible'] && promo_remise($prix, $promoH) > 0) ? promo_net($prix, $promoH) : $prix;
          $presNet= ($promoH['eligible'] && $presen > 0) ? promo_net($presen, $promoH) : $presen;
          $jours  = !empty($f['date_debut']) ? (int)floor((strtotime($f['date_debut'].' 09:00:00') - time()) / 86400) : null;
          $fid    = (int)$f['id'];
          $slug   = (string)($f['slug'] ?? '');
          $url    = $slug !== '' ? '/formation/' . rawurlencode($slug) : '/formation.php?id=' . $fid;
          $left   = function_exists('places_disponibles') ? (int)places_disponibles($f) : 0;
        ?>
          <div class="card card-dark reveal">
            <h3><?= htmlspecialchars($f['titre']) ?></h3>
            <div class="session-meta">
              <div><i class="fa-solid fa-folder-open"></i> <?= htmlspecialchars($f['domaine'] ?? '—') ?></div>
              <div><i class="fa-solid fa-clock"></i> <?= htmlspecialchars($f['duree'] ?? '—') ?></div>
              <div><i class="fa-solid fa-calendar-day"></i> <?= !empty($f['date_debut']) ? date('d/m/Y', strtotime($f['date_debut'])) : '—' ?></div>
              <?php if ($left > 0): ?><div><i class="fa-solid fa-users"></i> <?= $left ?> place(s) restante(s)</div><?php endif; ?>
            </div>
            <div class="session-price">
              <?php if ($prix > 0): ?>
                <span class="net">
                  <?php if ($net < $prix): ?><s><?= number_format($prix,0,',',' ') ?></s><?php endif; ?>
                  <?= number_format($net,0,',',' ') ?> FCFA<span class="unit"> / en ligne<?php if ($presen>0): ?> · <?= number_format($presNet,0,',',' ') ?> présentiel<?php endif; ?></span>
                </span>
              <?php elseif ($presen > 0): ?>
                <span class="net">
                  <?php if ($promoH['eligible']): ?><s><?= number_format($presen + PROMO_REMISE_PRESENTIEL,0,',',' ') ?></s><?php endif; ?>
                  <?= number_format($presen,0,',',' ') ?> FCFA<span class="unit"> / présentiel</span>
                </span>
              <?php else: ?>
                <span class="net" style="color:#34d399"><?php if ($ref>0): ?><s style="opacity:.55;font-weight:500;color:inherit"><?= number_format($ref,0,',',' ') ?></s> <?php endif; ?>Gratuit<span class="unit"> · offert</span></span>
              <?php endif; ?>
            </div>
            <div style="display:flex;flex-wrap:wrap;gap:8px;align-items:center">
              <?php if ($promoH['eligible']): ?><span class="pill-soon">🐦 <?= htmlspecialchars($promoH['label']) ?></span><?php endif; ?>
              <?php if ($jours !== null && $jours >= 0): ?><span class="pill-days">⏳ dans <?= (int)$jours ?> j</span><?php endif; ?>
              <?php if ($left > 0 && $left <= 6): ?><span class="pill-left">🔥 Plus que <?= $left ?> places</span><?php endif; ?>
            </div>
            <div class="session-actions">
              <a class="btn btn-light btn-sm" href="<?= htmlspecialchars($url) ?>">Programme</a>
              <a class="btn btn-accent btn-sm" href="/preinscription.php?formation_id=<?= $fid ?>" onclick="trackEvent('clic','home_session_preinscription')">Se préinscrire</a>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="card card-dark reveal">
          <h3>Catalogue 2026</h3>
          <p>Ajoute 6 formations actives dans la table <code>formations</code> pour alimenter automatiquement cette section.</p>
          <div style="margin-top:14px">
            <a class="btn btn-primary" href="formations.php">Voir le catalogue</a>
          </div>
        </div>
      <?php endif; ?>
    </div>

    <div style="margin-top:30px;display:flex;gap:12px;flex-wrap:wrap" class="reveal">
      <a class="btn btn-primary" href="formations.php">Tout le catalogue</a>
      <a class="btn btn-outline" href="samedi-pro.php">⚡ Formations Samedi Pro</a>
      <a class="btn btn-outline" href="calendrier.php">Voir le calendrier</a>
    </div>
  </div>
</section>

<section class="section section-light">
  <div class="container">

    <h2 class="section-title reveal">Calendrier — Septembre & Octobre 2026</h2>
    <p class="section-subtitle reveal">
      Les prochaines sessions disponibles. <a href="/calendrier.php" style="color:#2563eb;font-weight:700">Voir tout le programme annuel →</a>
    </p>

    <div class="calendar-grid">

      <?php if(empty($calendrier)): ?>
        <p>Aucune formation programmée pour 2026.</p>
      <?php endif; ?>

      <?php foreach($calendrier as $mois => $formations): ?>
        <div class="calendar-month reveal">

          <?php
            $formatter = new IntlDateFormatter(
              'fr_FR',
              IntlDateFormatter::LONG,
              IntlDateFormatter::NONE,
              'Africa/Abidjan',
              IntlDateFormatter::GREGORIAN,
              'MMMM yyyy'
            );
            ?>
            
            <h3 class="calendar-month-title">
              <?= ucfirst($formatter->format(strtotime($mois.'-01'))) ?>
            </h3>

          <?php foreach ($formations as $f): ?>
          <div class="calendar-item">
        
            <h4><?= htmlspecialchars($f['titre']) ?></h4>
        
            <div class="calendar-meta">
              <span style="font-weight:700">Domaine :</span>
              <?= htmlspecialchars($f['domaine']) ?><br>
        
              <span style="font-weight:700">Démarrage :</span>
              <?= formatDateSafe($f['date_debut'] ?? null) ?><br>
        
              <span style="font-weight:700">Durée :</span>
              <?= htmlspecialchars($f['duree']) ?><br>

              <span style="font-weight:700">À partir de :</span>
              <?php
                $tEnL = !empty($f['is_samedi_pro']) ? 0 : (int)($f['tarif_en_ligne'] ?? 0);
                $tPres = (int)($f['tarif_presentiel'] ?? 0);
                $tRef  = (int)($f['tarif_hybride'] ?? 0);
              ?>
              <?php if ($tEnL > 0): ?><?= number_format($tEnL, 0, ',', ' ') ?> FCFA
              <?php elseif ($tPres > 0): ?><?= number_format($tPres, 0, ',', ' ') ?> FCFA
              <?php elseif ($tRef > 0): ?><s style="color:#94a3b8"><?= number_format($tRef,0,',',' ') ?> FCFA</s> <strong style="color:#16a34a">Gratuit</strong>
              <?php else: ?><strong style="color:#16a34a">Gratuit</strong><?php endif; ?>
            </div>

              <?php $promoC = promo_earlybird($f); ?>
              <div class="calendar-badges">
                <span class="badge badge-duree"><?= htmlspecialchars($f['duree']) ?></span>
                <span class="badge badge-mode"><?= htmlspecialchars($f['mode']) ?></span>
                <?php if ($promoC['eligible']): ?>
                  <span class="badge" style="background:#fde68a;color:#92400e">🐦 <?= htmlspecialchars($promoC['label']) ?></span>
                <?php endif; ?>
              </div>

              <div class="calendar-actions">
              <a href="preinscription.php?formation_id=<?= (int)$f['formation_id'] ?>"
                 onclick="trackEvent('clic','home_preinscription')">
                 → Se préinscrire
              </a>
            </div>

            </div>
          <?php endforeach; ?>

        </div>
      <?php endforeach; ?>

    </div>

    <div style="margin-top:28px;text-align:center" class="reveal">
      <a class="btn btn-primary" href="/calendrier.php">📅 Voir tout le calendrier 2026</a>
      <a class="btn btn-outline" style="background:#0b3c5d;color:#fff;margin-left:10px" href="/preinscription-generale.php">Se préinscrire</a>
    </div>

  </div>
</section>

<!-- =====================================================
  SECTION LIGHT — Derniers articles du blog
===================================================== -->
<?php if (!empty($blogHome)): ?>
<section class="section section-light">
  <div class="container">
    <h2 class="section-title reveal">Ressources & Publications</h2>
    <p class="section-subtitle reveal">Conseils pratiques, analyses métiers et actualités pour les professionnels en formation continue.</p>

    <div class="blog-home-grid">
      <?php
        $frMoisBlog = [1=>'janv.',2=>'févr.',3=>'mars',4=>'avr.',5=>'mai',6=>'juin',7=>'juil.',8=>'août',9=>'sept.',10=>'oct.',11=>'nov.',12=>'déc.'];
        $blogBandStyle = function(string $s): string {
          $h = abs(crc32($s)) % 360; $h2 = ($h+28)%360;
          return "background:linear-gradient(135deg,hsl($h,62%,46%),hsl($h2,56%,30%))";
        };
        foreach ($blogHome as $art):
          $ts = strtotime((string)$art['created_at']);
          $dateBlog = $ts ? date('j',$ts).' '.($frMoisBlog[(int)date('n',$ts)]??'').' '.date('Y',$ts) : '';
          $plain = strip_tags((string)$art['contenu']);
          $excerpt = function_exists('mb_strtrimwidth')
            ? (mb_strtrimwidth($plain, 0, 120, '…', 'UTF-8') ?: '')
            : (mb_strlen($plain) > 120 ? mb_substr($plain,0,120).'…' : $plain);
          $slug = (string)($art['slug'] ?? '');
          $url  = $slug ? '/blog/'.$slug : '/blog.php';
      ?>
        <a class="bh-card" href="<?= htmlspecialchars($url) ?>">
          <div class="bh-band" style="<?= $blogBandStyle((string)$art['titre']) ?>"></div>
          <div class="bh-body">
            <?php if (!empty($art['cat'])): ?><div class="bh-cat"><?= htmlspecialchars($art['cat']) ?></div><?php endif; ?>
            <h3 class="bh-title"><?= htmlspecialchars($art['titre']) ?></h3>
            <p class="bh-excerpt"><?= htmlspecialchars($excerpt) ?></p>
            <div class="bh-meta"><?= htmlspecialchars($dateBlog) ?></div>
            <span class="bh-read">Lire l'article <i class="fa-solid fa-arrow-right" style="font-size:.8rem"></i></span>
          </div>
        </a>
      <?php endforeach; ?>
    </div>

    <div style="margin-top:28px;text-align:center" class="reveal">
      <a class="btn btn-dark" href="/blog.php">Voir tous les articles →</a>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- =====================================================
  SECTION LIGHT — Témoignages (crédibilité)
===================================================== -->
<section class="section section-light">
  <div class="container">
    <h2 class="section-title reveal">Ce que nos apprenants retiennent</h2>
    <p class="section-subtitle reveal">Des retours concrets sur l’utilité terrain, la pédagogie et l’impact professionnel.</p>

    <div class="grid">
      <?php
        $avisHome = function_exists('get_approved_avis') ? get_approved_avis(3) : [];
        if ($avisHome):
          foreach ($avisHome as $a):
            $loc = trim((string)($a['ville'] ?? '') . ' ' . (string)($a['pays'] ?? ''));
            $meta = trim(implode(' · ', array_filter([(string)($a['secteur'] ?? ''), $loc])));
      ?>
          <div class="card reveal">
            <div class="quote">"<?= htmlspecialchars((string)$a['texte']) ?>"</div>
            <div class="person"><?= htmlspecialchars((string)($a['nom'] ?? 'Participant') . ($meta !== '' ? ' — ' . $meta : '')) ?></div>
          </div>
      <?php endforeach; else: ?>
        <div class="card reveal">
          <div class="quote">"Formation très pratique. J’ai appliqué les outils dès la semaine suivante au travail."</div>
          <div class="person">Participant — Programme certifiant</div>
        </div>
        <div class="card reveal">
          <div class="quote">"Les cas réels et l’accompagnement font toute la différence. Très professionnel."</div>
          <div class="person">Cadre — Renforcement de capacités</div>
        </div>
        <div class="card reveal">
          <div class="quote">"Approche claire, exigence, et méthodologie. On sort avec des compétences utilisables."</div>
          <div class="person">Participant — Parcours métier</div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- =====================================================
  SECTION LIGHT — FAQ rapide
===================================================== -->
<section class="section section-light" style="padding-bottom:60px">
  <div class="container">
    <h2 class="section-title reveal" style="text-align:center">Questions fréquentes</h2>
    <p class="section-subtitle reveal" style="text-align:center;margin:0 auto 0">Tout ce que vous devez savoir avant de vous inscrire.</p>

    <div class="faq-home" id="faqHome">
      <?php foreach ($faqHome as $i => $fq): ?>
        <div class="fqh-item">
          <div class="fqh-q">
            <span><?= htmlspecialchars((string)$fq[0]) ?></span>
            <div class="fqh-icon">+</div>
          </div>
          <div class="fqh-a">
            <div class="fqh-a-inner"><?= nl2br(htmlspecialchars((string)$fq[1])) ?></div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <div style="text-align:center;margin-top:28px" class="reveal">
      <a class="btn btn-dark" href="/faq.php">Toutes les questions →</a>
    </div>
  </div>
</section>

<!-- =====================================================
  SECTION PARRAINAGE
===================================================== -->
<section class="section section-light" style="padding-top:0;padding-bottom:100px">
  <div class="container reveal">
    <div class="parrainage-wrap">
      <div style="position:relative;z-index:1">
        <div class="parrainage-badge">🎁 Programme parrainage IBIG EDUFORM</div>
        <h2>Recommandez un ami,<br>gagnez 10% de remise</h2>
        <p>Pour chaque personne que vous nous recommandez et qui s'inscrit à une formation, vous recevez une remise de <strong>10%</strong> sur votre prochaine formation. Sans limite.</p>
        <div style="display:flex;gap:14px;flex-wrap:wrap">
          <a class="btn btn-light" href="/parrainage.php">Découvrir le programme →</a>
          <a class="btn" style="background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.4)" href="https://wa.me/2250778882592?text=<?= rawurlencode('Bonjour IBIG EDUFORM, je souhaite parrainer quelqu\'un pour une formation.') ?>">📱 Parrainer via WhatsApp</a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- =====================================================
  CTA FINAL (fort)
===================================================== -->
<section class="section section-dark" style="padding:90px 0">
  <div class="container">
    <div class="cta-wide reveal">
      <h2>Construisez votre avenir professionnel dès aujourd’hui</h2>
      <p>IBIG EDUFORM — formations orientées résultats, employabilité et impact terrain.</p>
      <div class="cta-actions">
        <a class="btn btn-accent" href="preinscription-generale.php">Démarrer mon parcours</a>
        <a class="btn btn-outline" href="contact.php">Parler à un conseiller</a>
      </div>
    </div>
  </div>
</section>

</main>

<script>
/* =====================================================
   HERO SLIDER — défilement horizontal + swipe mobile
===================================================== */
(function(){
  var slides  = document.querySelectorAll('.hero-slide');
  var dots    = document.querySelectorAll('.hero-dot');
  var n       = slides.length;
  var current = 0;
  var busy    = false;
  var timer   = null;

  if(n < 2) return; // 1 seul slide : rien à faire

  function goTo(i, dir){
    if(busy || i === current) return;
    busy = true;
    var prev = current;
    current  = (i + n) % n;

    // Positionner le slide entrant hors écran
    slides[current].style.transition = 'none';
    slides[current].classList.remove('leaving','prev-enter');
    slides[current].style.transform  = dir === 'prev' ? 'translateX(-100%)' : 'translateX(100%)';
    slides[current].style.opacity    = '0';

    // Forcer reflow
    void slides[current].offsetWidth;

    // Animer
    slides[current].style.transition = '';
    slides[current].style.transform  = 'translateX(0)';
    slides[current].style.opacity    = '1';
    slides[current].classList.add('active');

    slides[prev].style.transform = dir === 'prev' ? 'translateX(100%)' : 'translateX(-100%)';
    slides[prev].style.opacity   = '0';

    dots[prev].classList.remove('active');
    dots[current].classList.add('active');

    setTimeout(function(){
      slides[prev].classList.remove('active');
      slides[prev].style.transform = '';
      slides[prev].style.opacity   = '';
      slides[prev].style.transition= '';
      slides[current].style.transform = '';
      slides[current].style.opacity   = '';
      slides[current].style.transition= '';
      busy = false;
    }, 820);
  }

  function next(){ goTo(current + 1, 'next'); }
  function prev(){ goTo(current - 1, 'prev'); }

  dots.forEach(function(d){
    d.addEventListener('click', function(){
      var i = +d.dataset.i;
      goTo(i, i > current ? 'next' : 'prev');
      restart();
    });
  });

  function start(){
    stop();
    timer = setInterval(next, 6500);
  }
  function stop(){ if(timer){ clearInterval(timer); timer = null; } }
  function restart(){ stop(); start(); }

  start();

  var hero = document.querySelector('.hero');
  if(hero){
    hero.addEventListener('mouseenter', stop);
    hero.addEventListener('mouseleave', start);

    /* Swipe touch */
    var tx = 0;
    hero.addEventListener('touchstart', function(e){ tx = e.touches[0].clientX; }, {passive:true});
    hero.addEventListener('touchend', function(e){
      var dx = e.changedTouches[0].clientX - tx;
      if(Math.abs(dx) < 40) return;
      if(dx < 0){ next(); } else { prev(); }
      restart();
    }, {passive:true});
  }
})();

/* =====================================================
   (1) COUNT-UP KPI (déclenchement visible)
===================================================== */
function animateCounters(){
  document.querySelectorAll('.metric strong[data-target]').forEach(function(counter){
    var target = parseInt(counter.dataset.target || '0', 10);
    var suffix = counter.dataset.suffix || '';
    var steps  = 60;
    var delay  = Math.max(1, Math.round(1200 / steps));
    var inc    = Math.max(1, Math.ceil(target / steps));
    var cur    = 0;
    counter.textContent = '0';
    var id = setInterval(function(){
      cur = Math.min(cur + inc, target);
      counter.textContent = (cur < target) ? cur : target + suffix;
      if(cur >= target) clearInterval(id);
    }, delay);
  });
}

let countersDone = false;
const metrics = document.getElementById('heroMetrics');
if(metrics){
  /* Démarre immédiatement si déjà visible, sinon attend l'intersection */
  const tryStart = () => {
    if(countersDone) return;
    const r = metrics.getBoundingClientRect();
    if(r.top < window.innerHeight && r.bottom > 0){
      countersDone = true;
      animateCounters();
    }
  };
  const obs = new IntersectionObserver((entries)=>{
    entries.forEach(en=>{ if(en.isIntersecting) tryStart(); });
  }, {threshold:0.1});
  obs.observe(metrics);
  /* Vérifie aussi au premier tick après le chargement */
  requestAnimationFrame(tryStart);
  setTimeout(tryStart, 400);
}

/* =====================================================
   FAQ HOME — accordéon
===================================================== */
document.querySelectorAll('.fqh-q').forEach(function(q){
  q.addEventListener('click', function(){
    var item = q.parentElement;
    var ans  = item.querySelector('.fqh-a');
    var open = item.classList.contains('open');
    document.querySelectorAll('.fqh-item.open').forEach(function(o){
      o.classList.remove('open');
      o.querySelector('.fqh-a').style.maxHeight = null;
    });
    if (!open) {
      item.classList.add('open');
      ans.style.maxHeight = ans.scrollHeight + 'px';
    }
  });
});

/* =====================================================
   (4) SCROLL REVEAL — robuste
===================================================== */
var revealEls = Array.from(document.querySelectorAll('.reveal'));
function revealAll(){ revealEls.forEach(function(el){ el.classList.add('in'); }); }
function revealCheck(){
  revealEls.forEach(function(el){
    var r = el.getBoundingClientRect();
    if(r.top < window.innerHeight + 60){ el.classList.add('in'); }
  });
}
/* Déclenche immédiatement pour ce qui est visible + à chaque scroll */
revealCheck();
window.addEventListener('scroll', revealCheck, {passive:true});
/* Fallback absolu : tout révéler après 1.2s */
setTimeout(revealAll, 1200);
</script>

<script>
function trackEvent(type, label){
  fetch('/track.php', {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify({
      event_type: type,
      label: label
    }),
    keepalive: true
  });
}
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>
