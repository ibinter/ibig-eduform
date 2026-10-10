<?php
declare(strict_types=1);
/**
 * IBIG EDUFORM — /formation-seo.php
 * Page SEO dynamique par ville et/ou secteur/domaine.
 * URLs générées via .htaccess :
 *   /formation-[ville]                → ville seulement
 *   /formation-[domaine]-[ville]      → domaine + ville
 *   /formation-[domaine]              → domaine seulement
 *
 * Paramètre interne :
 *   ?ville=abidjan&domaine=comptabilite
 */

require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/core/seo.php';

if (!function_exists('e')) {
    function e($v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }
}

$pdo = Database::connect();

/* ── Paramètres ── */
$ville   = strtolower(trim((string)($_GET['ville']   ?? '')));
$domaine = strtolower(trim((string)($_GET['domaine'] ?? '')));

/* Normalisation slugs → libellés */
$villesMap = [
    'abidjan'    => 'Abidjan',
    'bouake'     => 'Bouaké',
    'yamoussoukro' => 'Yamoussoukro',
    'san-pedro'  => 'San-Pédro',
    'daloa'      => 'Daloa',
    'en-ligne'   => 'en ligne',
];

$domainesMap = [
    'comptabilite'   => 'Comptabilité & Finance',
    'management'     => 'Management & Leadership',
    'ressources-humaines' => 'Ressources Humaines',
    'rh'             => 'Ressources Humaines',
    'informatique'   => 'Informatique & Digital',
    'qhse'           => 'QHSE & Sécurité',
    'logistique'     => 'Logistique & Supply Chain',
    'marketing'      => 'Marketing & Communication',
    'droit'          => 'Droit & Fiscalité',
    'ohada'          => 'Droit OHADA',
    'excel'          => 'Bureautique & Excel',
    'sap'            => 'SAP & ERP',
    'ia'             => 'Intelligence Artificielle',
];

$villeLbl   = $villesMap[$ville]   ?? ucfirst(str_replace('-', ' ', $ville));
$domaineLbl = $domainesMap[$domaine] ?? ucfirst(str_replace('-', ' ', $domaine));

/* ── Requête formations ── */
$conditions = ["f.statut = 'active'"];
$params     = [];

if ($domaine !== '') {
    $conditions[] = "LOWER(f.domaine) LIKE :domaine";
    $params[':domaine'] = '%' . $domaine . '%';
}

/* Pour les villes présentiel (hors en-ligne) */
if ($ville !== '' && $ville !== 'en-ligne') {
    /* On inclut toutes les formations présentiel + hybride */
    /* (aucun filtre supplémentaire : toutes les formations actives) */
}
if ($ville === 'en-ligne') {
    $conditions[] = "f.tarif_en_ligne > 0";
}

$sql = "SELECT f.id, f.titre, f.slug, f.domaine, f.description, f.duree,
               f.date_debut, f.tarif_en_ligne, f.tarif_presentiel,
               f.type_certificat
        FROM formations f
        WHERE " . implode(' AND ', $conditions) . "
          AND f.date_debut >= CURDATE()
        ORDER BY f.date_debut ASC
        LIMIT 40";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$formations = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ── Fallback si aucune formation ── */
if (empty($formations)) {
    $stmtAll = $pdo->prepare("
        SELECT f.id, f.titre, f.slug, f.domaine, f.description, f.duree,
               f.date_debut, f.tarif_en_ligne, f.tarif_presentiel, f.type_certificat
        FROM formations f
        WHERE f.statut = 'active' AND f.date_debut >= CURDATE()
        ORDER BY f.date_debut ASC LIMIT 12
    ");
    $stmtAll->execute();
    $formations = $stmtAll->fetchAll(PDO::FETCH_ASSOC);
}

/* ── Villes alternatives pour liens internes ── */
$autresVilles = ['abidjan', 'bouake', 'yamoussoukro', 'en-ligne'];

/* ── SEO ── */
$seoTitre = '';
if ($domaine !== '' && $ville !== '') {
    $seoTitre = "Formation {$domaineLbl} à {$villeLbl} — IBIG EDUFORM 2026";
    $ogDesc   = "Découvrez nos formations {$domaineLbl} à {$villeLbl} avec IBIG EDUFORM. Programmes certifiants, formateurs experts, certificats reconnus. Inscrivez-vous dès maintenant.";
} elseif ($ville !== '') {
    $seoTitre = "Formations professionnelles à {$villeLbl} — IBIG EDUFORM 2026";
    $ogDesc   = "IBIG EDUFORM propose des formations professionnelles certifiantes à {$villeLbl} : management, comptabilité, RH, QHSE, informatique. Certifications reconnues dans 17 pays OHADA.";
} elseif ($domaine !== '') {
    $seoTitre = "Formation {$domaineLbl} — Certifiante & Professionnelle — IBIG EDUFORM";
    $ogDesc   = "Formations {$domaineLbl} avec IBIG EDUFORM : programme structuré, formateurs certifiés, accès en ligne ou en présentiel à Abidjan. Certificat reconnu dans l'espace OHADA.";
} else {
    $seoTitre = "Formations professionnelles certifiantes — IBIG EDUFORM";
    $ogDesc   = "IBIG EDUFORM : centre de formation professionnelle en Côte d'Ivoire. Programmes certifiants en management, comptabilité, RH, informatique, QHSE. Présentiel Abidjan ou en ligne.";
}

$pageTitle    = $seoTitre;
$pageKeywords = "formation professionnelle " . $villeLbl . ", formation " . $domaineLbl . " Côte d'Ivoire, IBIG EDUFORM, formation certifiante Abidjan, OHADA";

/* ── Breadcrumb Schema ── */
$canonical = 'https://ibig-eduform.com';
if ($domaine !== '' && $ville !== '') {
    $canonical .= '/formation-' . $domaine . '-' . $ville;
} elseif ($ville !== '') {
    $canonical .= '/formation-' . $ville;
} elseif ($domaine !== '') {
    $canonical .= '/formation-' . $domaine;
}

include __DIR__ . '/partials/header.php';

$fmtDate = new IntlDateFormatter('fr_FR', IntlDateFormatter::LONG, IntlDateFormatter::NONE);
?>
<link rel="canonical" href="<?= e($canonical); ?>">

<style>
.seo-hero{background:linear-gradient(135deg,#0a1733 0%,#1d3061 60%,#0a1733 100%);color:#fff;padding:56px 20px 40px;text-align:center}
.seo-hero h1{font-size:clamp(1.4rem,4vw,2.2rem);font-weight:900;margin:0 0 10px;line-height:1.3}
.seo-hero p{color:rgba(255,255,255,.75);font-size:1rem;max-width:640px;margin:0 auto 24px;line-height:1.7}
.seo-badge{display:inline-block;background:#f59e0b;color:#0a1733;font-size:.75rem;font-weight:900;padding:4px 14px;border-radius:999px;margin-bottom:14px;text-transform:uppercase;letter-spacing:.5px}
.seo-villes{display:flex;gap:8px;flex-wrap:wrap;justify-content:center;margin-top:10px}
.seo-villes a{padding:6px 16px;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.25);border-radius:999px;color:#fff;font-size:.8rem;font-weight:700;text-decoration:none;transition:all .2s}
.seo-villes a:hover,.seo-villes a.actif{background:#f59e0b;border-color:#f59e0b;color:#0a1733}

.seo-wrap{max-width:1100px;margin:0 auto;padding:40px 18px 80px}
.seo-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(290px,1fr));gap:20px}

.seo-card{background:#fff;border:1px solid #e5e7eb;border-radius:14px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,.06);transition:transform .2s,box-shadow .2s}
.seo-card:hover{transform:translateY(-3px);box-shadow:0 8px 28px rgba(0,0,0,.1)}
.seo-card-top{padding:18px 18px 0;background:linear-gradient(135deg,#f8faff,#eff6ff)}
.seo-card-domaine{font-size:.7rem;font-weight:800;color:#1d4ed8;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px}
.seo-card-titre{font-size:.97rem;font-weight:800;color:#0a1733;line-height:1.4;margin-bottom:10px}
.seo-card-body{padding:14px 18px 16px}
.seo-card-meta{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:12px}
.seo-tag{font-size:.72rem;font-weight:700;padding:3px 10px;border-radius:999px}
.seo-tag-blue{background:#dbeafe;color:#1e40af}
.seo-tag-green{background:#dcfce7;color:#166534}
.seo-tag-amber{background:#fef3c7;color:#92400e}
.seo-card-prix{font-size:.82rem;color:#6b7280;margin-bottom:12px}
.seo-card-prix strong{color:#0a1733;font-size:.95rem}
.seo-card-cta{display:flex;gap:8px}
.seo-btn-primary{flex:1;text-align:center;background:#0a1733;color:#fff;padding:9px;border-radius:8px;font-weight:800;font-size:.8rem;text-decoration:none;transition:background .2s}
.seo-btn-primary:hover{background:#1d4ed8}
.seo-btn-outline{padding:9px 12px;border:1.5px solid #e5e7eb;border-radius:8px;font-weight:700;font-size:.8rem;color:#374151;text-decoration:none;transition:all .2s}
.seo-btn-outline:hover{border-color:#1d4ed8;color:#1d4ed8}

.seo-section-title{font-size:1.3rem;font-weight:900;color:#0a1733;margin:0 0 24px}
.seo-domains{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px;margin-bottom:40px}
.seo-domain-link{display:flex;align-items:center;gap:10px;padding:13px 16px;background:#fff;border:1px solid #e5e7eb;border-radius:10px;text-decoration:none;color:#0a1733;font-weight:700;font-size:.85rem;transition:all .2s}
.seo-domain-link:hover{border-color:#1d4ed8;background:#eff6ff;color:#1d4ed8}
.seo-domain-link .icon{font-size:1.2rem}

.seo-faq{max-width:760px;margin:0 auto 60px}
.seo-faq h2{font-size:1.3rem;font-weight:900;color:#0a1733;margin-bottom:20px}
.faq-item{border:1px solid #e5e7eb;border-radius:10px;margin-bottom:10px;overflow:hidden}
.faq-q{padding:14px 18px;font-weight:700;font-size:.9rem;color:#0a1733;cursor:pointer;display:flex;justify-content:space-between;align-items:center}
.faq-a{padding:0 18px;max-height:0;overflow:hidden;transition:max-height .3s,padding .3s;font-size:.85rem;color:#475569;line-height:1.7}
.faq-item.open .faq-a{max-height:200px;padding:0 18px 14px}
.faq-item.open .faq-q .arrow{transform:rotate(180deg)}
.arrow{transition:transform .3s;font-size:.8rem}
</style>

<!-- HERO SEO -->
<section class="seo-hero">
  <div class="seo-badge">Centre de formation certifié — Côte d'Ivoire</div>
  <h1><?= e($seoTitre); ?></h1>
  <p><?= e($ogDesc); ?></p>

  <?php if ($ville !== ''): ?>
  <div class="seo-villes">
    <a href="/formation-abidjan" class="<?= $ville==='abidjan'?'actif':''; ?>">🏙️ Abidjan</a>
    <a href="/formation-bouake" class="<?= $ville==='bouake'?'actif':''; ?>">🌆 Bouaké</a>
    <a href="/formation-yamoussoukro" class="<?= $ville==='yamoussoukro'?'actif':''; ?>">🏛️ Yamoussoukro</a>
    <a href="/formation-en-ligne" class="<?= $ville==='en-ligne'?'actif':''; ?>">💻 En ligne</a>
  </div>
  <?php endif; ?>
</section>

<div class="seo-wrap">

  <!-- Domaines -->
  <?php if ($domaine === ''): ?>
  <p class="seo-section-title">Nos domaines de formation<?= $ville !== '' ? ' à ' . e($villeLbl) : ''; ?></p>
  <div class="seo-domains">
    <?php
    $domsNav = [
      'comptabilite'  => ['💰', 'Comptabilité & Finance'],
      'management'    => ['🎯', 'Management'],
      'rh'            => ['👥', 'Ressources Humaines'],
      'informatique'  => ['💻', 'Informatique & Digital'],
      'qhse'          => ['🛡️', 'QHSE & Sécurité'],
      'logistique'    => ['📦', 'Logistique'],
      'marketing'     => ['📣', 'Marketing'],
      'ohada'         => ['⚖️', 'Droit OHADA'],
      'excel'         => ['📊', 'Bureautique & Excel'],
      'ia'            => ['🤖', 'Intelligence Artificielle'],
    ];
    $sfx = $ville !== '' ? '-' . $ville : '';
    foreach ($domsNav as $slug => [$icon, $lbl]):
    ?>
    <a href="/formation-<?= e($slug . $sfx); ?>" class="seo-domain-link">
      <span class="icon"><?= $icon; ?></span>
      <?= e($lbl); ?>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- Formations -->
  <p class="seo-section-title">
    <?= count($formations); ?> formation<?= count($formations) > 1 ? 's' : ''; ?>
    <?= $domaine !== '' ? e($domaineLbl) . ' ' : ''; ?>
    <?= $ville !== '' ? 'à ' . e($villeLbl) : ''; ?>
    disponible<?= count($formations) > 1 ? 's' : ''; ?>
  </p>

  <div class="seo-grid">
    <?php foreach ($formations as $f):
      $titreF  = (string)($f['titre'] ?? '');
      $slugF   = (string)($f['slug'] ?? '');
      $domaineF = ucfirst((string)($f['domaine'] ?? ''));
      $prixLine = (int)($f['tarif_en_ligne']    ?? 0);
      $prixPres = (int)($f['tarif_presentiel']  ?? 0);
      $dateDebF = !empty($f['date_debut']) ? $fmtDate->format(new DateTime((string)$f['date_debut'])) : '';
      $dureeF   = (string)($f['duree'] ?? '');
      $certF    = (string)($f['type_certificat'] ?? '');
      $urlF     = $slugF !== '' ? '/formation/' . rawurlencode($slugF) : '/formations';
    ?>
    <div class="seo-card">
      <div class="seo-card-top">
        <div class="seo-card-domaine"><?= e($domaineF); ?></div>
        <h2 class="seo-card-titre"><?= e($titreF); ?></h2>
      </div>
      <div class="seo-card-body">
        <div class="seo-card-meta">
          <?php if ($dateDebF): ?>
          <span class="seo-tag seo-tag-blue">📅 <?= e($dateDebF); ?></span>
          <?php endif; ?>
          <?php if ($dureeF): ?>
          <span class="seo-tag seo-tag-amber">⏱ <?= e($dureeF); ?></span>
          <?php endif; ?>
          <?php if ($certF): ?>
          <span class="seo-tag seo-tag-green">🏅 <?= e($certF); ?></span>
          <?php endif; ?>
        </div>
        <?php if ($prixLine > 0 || $prixPres > 0): ?>
        <div class="seo-card-prix">
          <?php if ($prixLine > 0): ?>
          En ligne : <strong><?= number_format($prixLine, 0, ',', ' '); ?> FCFA</strong>
          <?php endif; ?>
          <?php if ($prixPres > 0): ?>
          <?php if ($prixLine > 0): ?> &nbsp;·&nbsp; <?php endif; ?>
          Présentiel : <strong><?= number_format($prixPres, 0, ',', ' '); ?> FCFA</strong>
          <?php endif; ?>
        </div>
        <?php endif; ?>
        <div class="seo-card-cta">
          <a href="<?= e($urlF); ?>" class="seo-btn-primary">S'inscrire →</a>
          <a href="<?= e($urlF); ?>" class="seo-btn-outline">Détails</a>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- FAQ locale SEO -->
  <div class="seo-faq">
    <h2>Questions fréquentes</h2>

    <?php
    $faqVille = $ville !== '' ? " à {$villeLbl}" : " en Côte d'Ivoire";
    $faqDom   = $domaine !== '' ? " en {$domaineLbl}" : '';
    $faqs = [
      ["Quelles formations propose IBIG EDUFORM{$faqVille} ?",
       "IBIG EDUFORM propose des formations professionnelles certifiantes{$faqDom}{$faqVille} dans les domaines du management, de la comptabilité, des ressources humaines, du QHSE, de l'informatique et du droit OHADA. Toutes les formations sont disponibles en présentiel ou en ligne."],
      ["Les certificats IBIG EDUFORM sont-ils reconnus ?",
       "Oui. Les certificats et attestations IBIG EDUFORM sont délivrés par IBIG SARL, institution reconnue, et valables dans les 17 pays de l'espace OHADA. Ils peuvent être vérifiés en ligne sur ibig-eduform.com."],
      ["Comment s'inscrire à une formation{$faqDom}{$faqVille} ?",
       "Cliquez sur le bouton « S'inscrire » de la formation choisie, complétez le formulaire de préinscription, et notre équipe vous recontacte sous 24h pour finaliser votre inscription et vous communiquer les modalités de paiement."],
      ["Peut-on suivre les formations à distance ?",
       "Absolument. La majorité de nos formations sont disponibles en ligne via notre plateforme e-learning, avec les mêmes contenus et le même accompagnement qu'en présentiel."],
    ];
    foreach ($faqs as [$q, $a]):
    ?>
    <div class="faq-item">
      <div class="faq-q"><?= e($q); ?> <span class="arrow">▼</span></div>
      <div class="faq-a"><?= e($a); ?></div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- CTA final -->
  <div style="text-align:center;margin-top:20px;padding:36px;background:linear-gradient(135deg,#0a1733,#1d3061);border-radius:16px;color:#fff">
    <h3 style="font-size:1.4rem;font-weight:900;margin:0 0 10px">Prêt à vous former avec les meilleurs ?</h3>
    <p style="color:rgba(255,255,255,.75);margin:0 0 22px;line-height:1.6">Plus de 2 000 professionnels formés depuis 2016. Rejoignez la communauté IBIG EDUFORM.</p>
    <a href="/formations" style="display:inline-block;background:#f59e0b;color:#0a1733;font-weight:900;padding:14px 36px;border-radius:10px;text-decoration:none;font-size:1rem">Voir toutes nos formations →</a>
  </div>

</div>

<!-- Schema.org JSON-LD -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "ItemList",
  "name": <?= json_encode($seoTitre); ?>,
  "description": <?= json_encode($ogDesc); ?>,
  "url": <?= json_encode($canonical); ?>,
  "itemListElement": [
    <?php foreach (array_slice($formations, 0, 10) as $idx => $f): ?>
    {
      "@type": "ListItem",
      "position": <?= $idx + 1; ?>,
      "item": {
        "@type": "Course",
        "name": <?= json_encode($f['titre'] ?? ''); ?>,
        "url": <?= json_encode('https://ibig-eduform.com/formation/' . ($f['slug'] ?? '')); ?>,
        "provider": {
          "@type": "Organization",
          "name": "IBIG EDUFORM",
          "url": "https://ibig-eduform.com"
        }
      }
    }<?= $idx < min(9, count($formations)-1) ? ',' : ''; ?>
    <?php endforeach; ?>
  ]
}
</script>

<script>
document.querySelectorAll('.faq-item').forEach(function(item) {
  item.querySelector('.faq-q').addEventListener('click', function() {
    item.classList.toggle('open');
  });
});
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>
