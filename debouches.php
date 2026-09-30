<?php
declare(strict_types=1);
/* =====================================================================
   IBIG EDUFORM — Débouchés & métiers par pôle
===================================================================== */
require_once __DIR__ . '/core/config.php';
require_once __DIR__ . '/core/database.php';
require_once __DIR__ . '/core/helpers.php';

$pdo = Database::connect();

$rows = $pdo->query("
  SELECT id, titre, slug, domaine, duree, is_samedi_pro, date_debut
  FROM formations
  WHERE statut = 'active'
  ORDER BY COALESCE(date_debut, '2100-01-01') ASC, titre ASC
")->fetchAll(PDO::FETCH_ASSOC);

$POLES = [
  [
    'cle'     => 'finance',
    'icone'   => '📊',
    'couleur' => '#1f3fe0,#3b82f6',
    'nom'     => 'Finance, Direction & Pilotage',
    'cat'     => 'Direction & Administration',
    'match'   => ['finance', 'direction', 'contrôle de gestion', 'controle de gestion'],
    'pitch'   => "Piloter la performance économique, sécuriser la trésorerie et éclairer les décisions de la direction générale.",
    'demande' => 'Très forte', 'demande_color' => '#16a34a',
    'salaire' => '350 000 – 1 500 000 FCFA / mois',
    'metiers' => ['Directeur Administratif & Financier (DAF)', 'Contrôleur de gestion', 'Responsable financier', 'Analyste financier', 'Trésorier d\'entreprise', 'Business partner finance'],
    'secteurs' => 'Grandes entreprises, PME en croissance, cabinets, institutions financières, ONG.',
  ],
  [
    'cle'     => 'compta',
    'icone'   => '📚',
    'couleur' => '#0f766e,#14b8a6',
    'nom'     => 'Comptabilité & Fiscalité',
    'cat'     => 'Comptabilité & Finance',
    'match'   => ['comptab', 'fiscal'],
    'pitch'   => "Produire une information financière fiable au référentiel SYSCOHADA révisé et maîtriser l'obligation fiscale.",
    'demande' => 'Forte', 'demande_color' => '#2563eb',
    'salaire' => '200 000 – 700 000 FCFA / mois',
    'metiers' => ['Comptable', 'Chef comptable', 'Responsable fiscal', 'Collaborateur de cabinet comptable', 'Assistant SYSCOHADA', 'Consultant fiscalité'],
    'secteurs' => 'Cabinets d\'expertise comptable, directions comptables, administrations fiscales, PME.',
  ],
  [
    'cle'     => 'audit',
    'icone'   => '🔍',
    'couleur' => '#7c3aed,#a855f7',
    'nom'     => 'Audit & Contrôle interne',
    'cat'     => 'Comptabilité & Finance',
    'match'   => ['audit', 'contrôle interne', 'controle interne'],
    'pitch'   => "Évaluer la maîtrise des risques, fiabiliser les processus et renforcer la gouvernance.",
    'demande' => 'Forte', 'demande_color' => '#2563eb',
    'salaire' => '300 000 – 900 000 FCFA / mois',
    'metiers' => ['Auditeur interne', 'Contrôleur interne', 'Risk manager', 'Auditeur qualité', 'Consultant en organisation', 'Inspecteur'],
    'secteurs' => 'Directions d\'audit interne, cabinets, banques, assurances, secteur public.',
  ],
  [
    'cle'     => 'rh',
    'icone'   => '👥',
    'couleur' => '#b45309,#f59e0b',
    'nom'     => 'Ressources Humaines, Paie & Droit social',
    'cat'     => 'GRH',
    'match'   => ['ressources humaines', 'rh', 'paie', 'droit social'],
    'pitch'   => "Administrer le capital humain, sécuriser la paie et appliquer le droit du travail de l'espace OHADA.",
    'demande' => 'Forte', 'demande_color' => '#2563eb',
    'salaire' => '250 000 – 800 000 FCFA / mois',
    'metiers' => ['Responsable RH', 'Gestionnaire de paie', 'Chargé d\'administration du personnel', 'Juriste social', 'Responsable formation', 'Talent manager'],
    'secteurs' => 'Directions RH, cabinets de conseil social, entreprises de toutes tailles.',
  ],
  [
    'cle'     => 'marketing',
    'icone'   => '🚀',
    'couleur' => '#dc2626,#f97316',
    'nom'     => 'Marketing, Digital, Commerce & Communication',
    'cat'     => 'Gestion Commerciale & Marketing',
    'match'   => ['marketing', 'commerce', 'communication', 'design'],
    'pitch'   => "Générer de la demande, développer les ventes et construire une marque forte sur les canaux digitaux.",
    'demande' => 'Très forte', 'demande_color' => '#16a34a',
    'salaire' => '200 000 – 700 000 FCFA / mois',
    'metiers' => ['Responsable marketing', 'Community manager', 'Chargé de communication', 'Commercial / Responsable des ventes', 'Traffic manager', 'Growth marketer'],
    'secteurs' => 'Entreprises, agences, e-commerce, médias, indépendants.',
  ],
  [
    'cle'     => 'management',
    'icone'   => '🎯',
    'couleur' => '#0369a1,#0ea5e9',
    'nom'     => 'Management & Gestion de Projet',
    'cat'     => 'Management & Leadership',
    'match'   => ['management', 'projet', 'entrepreneuriat', 'leadership'],
    'pitch'   => "Conduire les équipes, structurer les projets et transformer une idée en organisation viable.",
    'demande' => 'Très forte', 'demande_color' => '#16a34a',
    'salaire' => '300 000 – 1 000 000 FCFA / mois',
    'metiers' => ['Chef de projet', 'Manager d\'équipe', 'Responsable opérationnel', 'Chef d\'entreprise / Entrepreneur', 'PMO', 'Coordinateur de programme'],
    'secteurs' => 'Tous secteurs, start-up, ONG, cabinets de conseil, structures publiques.',
  ],
  [
    'cle'     => 'qhse',
    'icone'   => '🛡️',
    'couleur' => '#166534,#22c55e',
    'nom'     => 'QHSE, Logistique & Supply Chain',
    'cat'     => 'QHSE',
    'match'   => ['qhse', 'logistique', 'scm', 'supply', 'transport'],
    'pitch'   => "Garantir qualité, sécurité et environnement, et fluidifier les flux logistiques de bout en bout.",
    'demande' => 'Forte', 'demande_color' => '#2563eb',
    'salaire' => '250 000 – 900 000 FCFA / mois',
    'metiers' => ['Responsable QHSE', 'Animateur sécurité', 'Responsable logistique', 'Supply chain manager', 'Gestionnaire d\'approvisionnement', 'Responsable entrepôt'],
    'secteurs' => 'Industrie, BTP, distribution, transport, mines & énergie, agro-industrie.',
  ],
  [
    'cle'     => 'data',
    'icone'   => '🤖',
    'couleur' => '#4338ca,#818cf8',
    'nom'     => 'Data, Intelligence Artificielle & Bureautique',
    'cat'     => 'IA & Digitalisation',
    'match'   => ['data', 'donnée', 'donnees', 'intelligence artificielle', 'bureautique', 'power bi', 'excel'],
    'pitch'   => "Exploiter la donnée, automatiser les tâches et tirer parti de l'IA pour décider plus vite.",
    'demande' => 'Explosive', 'demande_color' => '#e8242c',
    'salaire' => '300 000 – 1 200 000 FCFA / mois',
    'metiers' => ['Data analyst', 'Analyste Power BI', 'Assistant de direction outillé', 'Chargé de reporting', 'Consultant IA appliquée', 'Automatisation & productivité'],
    'secteurs' => 'Tous secteurs — la data et l\'IA sont devenues transversales.',
  ],
  [
    'cle'     => 'logiciels',
    'icone'   => '🖥️',
    'couleur' => '#0e7490,#22d3ee',
    'nom'     => 'Logiciels de Gestion (Sage, SAP, Odoo)',
    'cat'     => 'Informatique & Tech',
    'match'   => ['sage', 'sap', 'odoo', 'erp', 'logiciel'],
    'pitch'   => "Maîtriser les outils ERP et logiciels de gestion les plus demandés par les entreprises de l'espace OHADA.",
    'demande' => 'Forte', 'demande_color' => '#2563eb',
    'salaire' => '250 000 – 850 000 FCFA / mois',
    'metiers' => ['Comptable Sage', 'Gestionnaire SAP FI', 'Consultant ERP', 'Opérateur Odoo', 'Administrateur Sage 100', 'Intégrateur logiciel'],
    'secteurs' => 'PME, grandes entreprises, cabinets comptables, ESN, directions informatiques.',
  ],
  [
    'cle'     => 'immo',
    'icone'   => '🏢',
    'couleur' => '#92400e,#f59e0b',
    'nom'     => 'Immobilier & BTP',
    'cat'     => 'Immobilier',
    'match'   => ['immobil', 'btp'],
    'pitch'   => "Développer, commercialiser et gérer des actifs immobiliers dans un marché en forte croissance.",
    'demande' => 'Croissante', 'demande_color' => '#d97706',
    'salaire' => '250 000 – 800 000 FCFA / mois',
    'metiers' => ['Gestionnaire immobilier', 'Négociateur / Agent immobilier', 'Responsable de programme', 'Syndic', 'Conseiller en investissement immobilier'],
    'secteurs' => 'Promoteurs, agences, sociétés de gestion, investisseurs privés.',
  ],
  [
    'cle'     => 'droit',
    'icone'   => '⚖️',
    'couleur' => '#1e3a5f,#3b82f6',
    'nom'     => 'Droit, Administration & Juridique',
    'cat'     => 'Droit & Juridique',
    'match'   => ['droit', 'juridique', 'administratif', 'assistant juridique'],
    'pitch'   => "Sécuriser les actes juridiques, gérer les contentieux et assurer la conformité dans l'espace OHADA.",
    'demande' => 'Modérée', 'demande_color' => '#7c3aed',
    'salaire' => '200 000 – 700 000 FCFA / mois',
    'metiers' => ['Assistant juridique', 'Juriste d\'entreprise', 'Responsable administratif', 'Gestionnaire de contentieux', 'Compliance officer', 'Secrétaire juridique'],
    'secteurs' => 'Cabinets d\'avocats, directions juridiques, administrations, banques, assurances.',
  ],
  [
    'cle'     => 'ong',
    'icone'   => '🌍',
    'couleur' => '#065f46,#10b981',
    'nom'     => 'Humanitaire, ONG & Développement',
    'cat'     => 'Management & Leadership',
    'match'   => ['humanitaire', 'ong', 'suivi-évaluation', 'suivi-evaluation', 'bailleurs'],
    'pitch'   => "Gérer des projets à impact, mobiliser des financements et rendre compte aux bailleurs.",
    'demande' => 'Forte', 'demande_color' => '#2563eb',
    'salaire' => '300 000 – 1 000 000 FCFA / mois',
    'metiers' => ['Chargé de projet ONG', 'Grants / Fundraising officer', 'Responsable suivi-évaluation', 'Coordinateur terrain', 'Gestionnaire de subventions'],
    'secteurs' => 'ONG nationales et internationales, agences de développement, fondations.',
  ],
];

/* Répartition des formations dans les pôles */
$byPole = [];
$autres = [];
foreach ($rows as $f) {
  $dom = mb_strtolower((string)($f['domaine'] ?? ''), 'UTF-8');
  $placed = false;
  foreach ($POLES as $p) {
    foreach ($p['match'] as $frag) {
      if ($dom !== '' && mb_strpos($dom, $frag) !== false) {
        $byPole[$p['cle']][] = $f;
        $placed = true;
        break 2;
      }
    }
  }
  if (!$placed) { $autres[] = $f; }
}

$totalFormations = count($rows);
$totalPoles      = count($POLES);

$fmtDuree = function ($d) {
  $d = trim((string)$d);
  return $d !== '' ? $d : null;
};

$pageTitle      = "Débouchés & métiers par pôle — IBIG EDUFORM";
$metaDescription = "Découvrez les métiers et débouchés visés par les formations certifiantes IBIG EDUFORM, pôle par pôle : finance, comptabilité, audit, RH, marketing, data, QHSE… pour tout l'espace OHADA.";
$ogDesc         = $metaDescription;

ob_start();
?>
<style>
/* ── HERO ────────────────────────────────────────────── */
.db-hero{
  background:linear-gradient(135deg,#06102b 0%,#0f2a6e 60%,#1a1035 100%);
  color:#fff;padding:72px 20px 56px;text-align:center;position:relative;overflow:hidden
}
.db-hero::before{
  content:'';position:absolute;inset:0;
  background:url("data:image/svg+xml,%3Csvg width='60' height='60' xmlns='http://www.w3.org/2000/svg'%3E%3Ccircle cx='30' cy='30' r='1' fill='rgba(255,255,255,.06)'/%3E%3C/svg%3E");
  pointer-events:none
}
.db-hero h1{font-size:clamp(1.8rem,4.5vw,2.9rem);font-weight:900;margin:0 0 16px;line-height:1.12;max-width:800px;margin-inline:auto}
.db-hero .sub{max-width:700px;margin:0 auto 28px;color:#c7d2fe;font-size:clamp(15px,2vw,17px);line-height:1.65}
.db-hero .tags{display:flex;flex-wrap:wrap;gap:10px;justify-content:center;margin-bottom:36px}
.db-hero .tags span{background:rgba(255,255,255,.11);border:1px solid rgba(255,255,255,.22);border-radius:999px;padding:7px 16px;font-size:13px;font-weight:600}
.db-stats{display:flex;flex-wrap:wrap;gap:20px;justify-content:center}
.db-stat{background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.15);border-radius:16px;padding:16px 28px;min-width:140px}
.db-stat strong{display:block;font-size:2rem;font-weight:900;color:#38bdf8;line-height:1}
.db-stat span{font-size:13px;color:#c7d2fe;margin-top:4px;display:block}

/* ── POLE NAV ────────────────────────────────────────── */
.db-nav-wrap{background:#f1f5f9;border-bottom:1px solid #e2e8f0;position:sticky;top:0;z-index:99;overflow-x:auto}
.db-nav{display:flex;gap:6px;padding:10px 20px;max-width:1180px;margin:0 auto;white-space:nowrap}
.db-nav a{display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border-radius:999px;font-size:13px;font-weight:700;color:#475569;text-decoration:none;background:#fff;border:1px solid #e2e8f0;transition:.15s;flex:none}
.db-nav a:hover{background:#e0e7ff;color:#1e3fe0;border-color:#c7d2fe}
.db-nav a.active{background:linear-gradient(135deg,#1f3fe0,#3b82f6);color:#fff;border-color:transparent}

/* ── WRAP & CARDS ────────────────────────────────────── */
.db-wrap{max-width:1180px;margin:0 auto;padding:44px 20px 80px}
.db-pole{background:#fff;border:1px solid #e3ebf6;border-radius:24px;margin-bottom:28px;overflow:hidden;box-shadow:0 8px 28px rgba(10,23,51,.06);transition:.2s}
.db-pole:hover{box-shadow:0 18px 48px rgba(10,23,51,.11);transform:translateY(-2px)}

.db-pole-head{
  padding:24px 30px 20px;
  border-bottom:1px solid #f0f4fb;
  display:flex;align-items:center;gap:18px;flex-wrap:wrap
}
.db-pole-ic{
  flex:none;width:62px;height:62px;border-radius:18px;
  display:flex;align-items:center;justify-content:center;font-size:30px;
  box-shadow:0 6px 20px rgba(0,0,0,.15)
}
.db-pole-titles{flex:1;min-width:0}
.db-pole-titles h2{font-size:clamp(1.1rem,2.2vw,1.45rem);font-weight:900;color:#0a1733;margin:0 0 4px;line-height:1.2}
.db-pitch{color:#64748b;font-size:14.5px;line-height:1.55;margin:0}
.db-pole-meta{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-left:auto}
.db-demande{font-size:12px;font-weight:800;padding:4px 12px;border-radius:999px;border:1px solid currentColor}
.db-count{font-size:12px;font-weight:700;color:#64748b;background:#f1f5f9;border-radius:999px;padding:4px 11px}

.db-cols{display:grid;grid-template-columns:1fr 1fr;gap:0}
.db-block{padding:26px 30px}
.db-block + .db-block{border-left:1px solid #f0f4fb}
.db-block h3{font-size:11px;font-weight:900;letter-spacing:.07em;text-transform:uppercase;color:#1f3fe0;margin:0 0 14px;display:flex;align-items:center;gap:6px}
.db-block h3::after{content:'';flex:1;height:1px;background:#e8eef8}

.db-metiers{list-style:none;margin:0 0 16px;padding:0;display:flex;flex-direction:column;gap:8px}
.db-metiers li{
  display:flex;align-items:center;gap:9px;
  padding:8px 12px;border-radius:10px;
  background:#f8fafc;border:1px solid #e8eef8;
  color:#0f172a;font-size:14px;font-weight:600
}
.db-metiers li::before{content:'';width:8px;height:8px;border-radius:50%;background:#22c55e;flex:none}

.db-secteurs{
  font-size:13px;color:#64748b;
  background:#f8fafc;border-radius:10px;padding:10px 14px;
  border-left:3px solid #e2e8f0
}
.db-secteurs b{color:#0a1733;display:block;margin-bottom:2px;font-size:11px;text-transform:uppercase;letter-spacing:.05em}

.db-forms{display:flex;flex-direction:column;gap:8px}
.db-form{
  display:flex;align-items:center;gap:10px;
  background:#f8fafc;border:1px solid #e3ebf6;border-radius:12px;
  padding:11px 13px;text-decoration:none;transition:.15s
}
.db-form:hover{border-color:#1f3fe0;background:#eef3ff;transform:translateX(3px)}
.db-form .t{font-weight:700;color:#0a1733;font-size:13.5px;line-height:1.3}
.db-form .m{margin-top:2px;font-size:11.5px;color:#7a8aa8}
.db-form .go{margin-left:auto;color:#1f3fe0;font-weight:900;flex:none;font-size:16px}
.db-badge{flex:none;font-size:10.5px;font-weight:800;padding:3px 8px;border-radius:999px;background:#e0e7ff;color:#1f3fe0;white-space:nowrap}
.db-badge.sam{background:#fff3d6;color:#8a5a00}

.db-pole-cta{
  padding:16px 30px;
  background:#fafbff;
  border-top:1px solid #f0f4fb;
  display:flex;align-items:center;gap:12px;flex-wrap:wrap
}
.db-pole-cta a{
  display:inline-flex;align-items:center;gap:7px;
  padding:9px 18px;border-radius:10px;font-size:13px;font-weight:800;
  text-decoration:none;transition:.15s
}
.db-pole-cta .btn-primary{background:linear-gradient(135deg,#1f3fe0,#3b82f6);color:#fff}
.db-pole-cta .btn-primary:hover{opacity:.88}
.db-pole-cta .btn-ghost{background:#fff;color:#1f3fe0;border:1px solid #c7d2fe}
.db-pole-cta .btn-ghost:hover{background:#e0e7ff}
.db-pole-cta .salaire{margin-left:auto;font-size:12.5px;color:#64748b;display:flex;align-items:center;gap:5px}
.db-pole-cta .salaire strong{color:#0a1733}

.db-empty{font-size:13.5px;color:#94a3b8;font-style:italic;padding:10px}

/* ── AUTRES ──────────────────────────────────────────── */
.db-autres-head{display:flex;align-items:center;gap:14px;padding:24px 30px 16px;border-bottom:1px solid #f0f4fb}
.db-autres-ic{width:52px;height:52px;border-radius:14px;background:linear-gradient(135deg,#475569,#94a3b8);display:flex;align-items:center;justify-content:center;font-size:24px;color:#fff}
.db-autres-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;padding:20px 30px}

/* ── CTA FINAL ───────────────────────────────────────── */
.db-cta{
  margin-top:20px;
  background:linear-gradient(135deg,#0a1733,#12275e);
  color:#fff;border-radius:24px;padding:48px 40px;text-align:center;position:relative;overflow:hidden
}
.db-cta::before{
  content:'';position:absolute;right:-60px;top:-60px;width:220px;height:220px;
  border-radius:50%;background:rgba(255,255,255,.05)
}
.db-cta h2{font-size:clamp(1.4rem,3vw,2rem);font-weight:900;margin:0 0 12px}
.db-cta p{color:#c7d2fe;margin:0 auto 28px;max-width:640px;line-height:1.65;font-size:16px}
.db-cta-btns{display:flex;flex-wrap:wrap;gap:12px;justify-content:center}
.db-cta a{
  display:inline-flex;align-items:center;gap:8px;
  text-decoration:none;font-weight:800;padding:14px 28px;border-radius:12px;font-size:15px
}
.db-cta .btn-red{background:#e8242c;color:#fff}
.db-cta .btn-red:hover{background:#c91a21}
.db-cta .btn-green{background:#22c55e;color:#04210f}
.db-cta .btn-green:hover{background:#16a34a;color:#fff}
.db-cta .btn-outline{background:rgba(255,255,255,.1);color:#fff;border:1px solid rgba(255,255,255,.3)}
.db-cta .btn-outline:hover{background:rgba(255,255,255,.18)}

/* ── RESPONSIVE ──────────────────────────────────────── */
@media(max-width:820px){
  .db-cols{grid-template-columns:1fr}
  .db-block + .db-block{border-left:none;border-top:1px solid #f0f4fb}
  .db-autres-grid{grid-template-columns:1fr}
  .db-cta{padding:36px 24px}
  .db-pole-cta .salaire{margin-left:0;width:100%}
}
</style>

<!-- HERO -->
<section class="db-hero">
  <h1>Quels métiers après une certification IBIG EDUFORM&nbsp;?</h1>
  <p class="sub">Chaque parcours est conçu pour l'emploi réel. Explorez pôle par pôle les métiers visés, les fourchettes salariales et les formations certifiantes qui y mènent — partout dans l'espace OHADA.</p>
  <div class="tags">
    <span>🌍 17 pays de l'espace OHADA</span>
    <span>🎓 Certificats vérifiables</span>
    <span>💼 Compétences opérationnelles</span>
    <span>🏆 Reconnu par les entreprises</span>
  </div>
  <div class="db-stats">
    <div class="db-stat"><strong><?= $totalPoles; ?></strong><span>Pôles métiers</span></div>
    <div class="db-stat"><strong><?= $totalFormations; ?>+</strong><span>Formations actives</span></div>
    <div class="db-stat"><strong>17</strong><span>Pays couverts</span></div>
    <div class="db-stat"><strong>100%</strong><span>Certifiantes</span></div>
  </div>
</section>

<!-- NAV PÔLES -->
<nav class="db-nav-wrap" aria-label="Navigation par pôle">
  <div class="db-nav">
    <?php foreach ($POLES as $p): ?>
      <a href="#pole-<?= e($p['cle']); ?>"><?= $p['icone']; ?> <?= e($p['nom']); ?></a>
    <?php endforeach; ?>
  </div>
</nav>

<div class="db-wrap">
  <?php foreach ($POLES as $p):
    $forms  = $byPole[$p['cle']] ?? [];
    $nbForms = count($forms);
    [$c1,$c2] = explode(',', $p['couleur']);
  ?>
    <div class="db-pole" id="pole-<?= e($p['cle']); ?>">

      <!-- EN-TÊTE PÔLE -->
      <div class="db-pole-head">
        <div class="db-pole-ic" style="background:linear-gradient(135deg,<?= e($c1); ?>,<?= e($c2); ?>)"><?= $p['icone']; ?></div>
        <div class="db-pole-titles">
          <h2><?= e($p['nom']); ?></h2>
          <p class="db-pitch"><?= e($p['pitch']); ?></p>
        </div>
        <div class="db-pole-meta">
          <span class="db-demande" style="color:<?= e($p['demande_color']); ?>;border-color:<?= e($p['demande_color']); ?>;background:<?= e($p['demande_color']); ?>18">🔥 <?= e($p['demande']); ?></span>
          <span class="db-count"><?= $nbForms; ?> formation<?= $nbForms > 1 ? 's' : ''; ?></span>
        </div>
      </div>

      <!-- COLONNES -->
      <div class="db-cols">
        <!-- Métiers -->
        <div class="db-block">
          <h3>Métiers &amp; débouchés</h3>
          <ul class="db-metiers">
            <?php foreach ($p['metiers'] as $m): ?><li><?= e($m); ?></li><?php endforeach; ?>
          </ul>
          <div class="db-secteurs"><b>Où exercer ?</b><?= e($p['secteurs']); ?></div>
        </div>

        <!-- Formations -->
        <div class="db-block">
          <h3>Formations qui y mènent</h3>
          <div class="db-forms">
            <?php if ($forms): foreach ($forms as $f):
              $slug = (string)($f['slug'] ?? '');
              $lien = $slug !== '' ? '/formation/' . rawurlencode($slug) : '/formation.php?id=' . (int)$f['id'];
              $sam  = !empty($f['is_samedi_pro']);
              $dur  = trim((string)($f['duree'] ?? ''));
            ?>
              <a class="db-form" href="<?= e($lien); ?>">
                <span class="db-badge <?= $sam ? 'sam' : ''; ?>"><?= $sam ? 'Samedi Pro' : 'Certifiante'; ?></span>
                <span>
                  <span class="t"><?= e($f['titre']); ?></span>
                  <?php if ($dur !== ''): ?><span class="m"><?= e($dur); ?></span><?php endif; ?>
                </span>
                <span class="go">→</span>
              </a>
            <?php endforeach; else: ?>
              <p class="db-empty">Prochaine session bientôt programmée — <a href="/besoin-formation.php?domaine=<?= e(rawurlencode($p['nom'])); ?>" style="color:#1f3fe0">demandez une session sur mesure</a>.</p>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- CTA PÔLE -->
      <div class="db-pole-cta">
        <?php $catParam = rawurlencode($p['cat']); ?>
        <a class="btn-primary" href="/catalogue-formations.php?cat=<?= $catParam; ?>">📚 Voir les formations dans le catalogue</a>
        <a class="btn-ghost" href="/besoin-formation.php?domaine=<?= rawurlencode($p['nom']); ?>">Sur mesure →</a>
        <div class="salaire">💰 Fourchette salariale estimée&nbsp;: <strong><?= e($p['salaire']); ?></strong></div>
      </div>

    </div>
  <?php endforeach; ?>

  <!-- AUTRES FORMATIONS -->
  <?php if ($autres): ?>
    <div class="db-pole" id="pole-autres">
      <div class="db-autres-head">
        <div class="db-autres-ic">✨</div>
        <div>
          <h2 style="margin:0;font-size:1.3rem;font-weight:900;color:#0a1733">Autres formations disponibles</h2>
          <p style="margin:4px 0 0;color:#64748b;font-size:14px"><?= count($autres); ?> formation<?= count($autres)>1?'s':''; ?> dans des domaines variés</p>
        </div>
      </div>
      <div class="db-autres-grid">
        <?php foreach ($autres as $f):
          $slug = (string)($f['slug'] ?? '');
          $lien = $slug !== '' ? '/formation/' . rawurlencode($slug) : '/formation.php?id=' . (int)$f['id'];
        ?>
          <a class="db-form" href="<?= e($lien); ?>">
            <span>
              <span class="t"><?= e($f['titre']); ?></span>
              <?php if (!empty($f['domaine'])): ?><span class="m"><?= e($f['domaine']); ?></span><?php endif; ?>
            </span>
            <span class="go">→</span>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

  <!-- CTA FINAL -->
  <div class="db-cta">
    <h2>Votre métier ou votre secteur n'est pas encore listé&nbsp;?</h2>
    <p>Nous construisons des parcours sur mesure — en individuel, en groupe ou en entreprise, en présentiel comme à distance, partout dans l'espace OHADA. Nos conseillers vous orientent gratuitement.</p>
    <div class="db-cta-btns">
      <a class="btn-red" href="/besoin-formation.php">✍️ Exprimer mon besoin</a>
      <a class="btn-green" href="/catalogue-formations.php">📚 Voir tout le catalogue</a>
      <a class="btn-outline" href="https://wa.me/2250778882592?text=<?= rawurlencode('Bonjour IBIG EDUFORM, je souhaite m\'informer sur vos formations et débouchés.') ?>">📱 Nous contacter</a>
    </div>
  </div>
</div>

<script>
/* Surligner l'ancre active dans la nav */
(function(){
  var links = document.querySelectorAll('.db-nav a');
  var poles = document.querySelectorAll('.db-pole[id]');
  if (!poles.length || !links.length) return;
  var active = null;
  function setActive(id){
    if (active === id) return; active = id;
    links.forEach(function(a){ a.classList.toggle('active', a.getAttribute('href') === '#' + id); });
  }
  window.addEventListener('scroll', function(){
    var mid = window.scrollY + window.innerHeight * 0.3;
    poles.forEach(function(p){ if (p.offsetTop <= mid) setActive(p.id); });
  }, {passive:true});
})();
</script>

<script type="application/ld+json">
<?= json_encode([
  '@context' => 'https://schema.org',
  '@type'    => 'CollectionPage',
  'name'     => 'Débouchés & métiers par pôle — IBIG EDUFORM',
  'description' => $metaDescription,
  'url'      => (defined('APP_URL') ? rtrim(APP_URL, '/') : 'https://ibig-eduform.com') . '/debouches.php',
  'hasPart'  => array_map(function ($p) {
    return ['@type' => 'CreativeWork', 'name' => $p['nom'], 'about' => implode(', ', $p['metiers'])];
  }, $POLES),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/partials/header.php';
echo $content;
require __DIR__ . '/partials/footer.php';
