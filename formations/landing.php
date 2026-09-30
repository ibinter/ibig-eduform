<?php
// REDIRECTION SIMPLE : Février 2026
if (isset($_GET['slug']) && $_GET['slug'] === 'fevrier-2026') {
    header('Location: /formations.php?mois=2', true, 302);
    exit;
}

require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/helpers.php';

$pdo = Database::connect();

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function slugify(string $text): string {
  $text = iconv('UTF-8', 'ASCII//TRANSLIT', $text);
  $text = strtolower($text);
  $text = preg_replace('~[^a-z0-9]+~', '-', $text);
  return trim($text, '-');
}

/**
 * Rendu HTML du contenu_programme d'une landing.
 * Reconnait les titres de blocs (MODULE / SEMAINE / JOUR / PARTIE / BLOC / CHAPITRE)
 * et les puces (•, -, *, >). Le reste est rendu en paragraphes. Sortie echappee.
 */
if (!function_exists('renderProgrammeCertificats')) {
  function renderProgrammeCertificats(string $raw): string {
    $raw = trim($raw);
    if ($raw === '') return '';
    $lines = preg_split('/\r\n|\r|\n/', $raw);
    $html = '';
    $inList = false;
    foreach ($lines as $line) {
      $t = trim($line);
      if ($t === '') { if ($inList) { $html .= '</ul>'; $inList = false; } continue; }
      if (preg_match('/^(MODULE|SEMAINE|JOUR|PARTIE|BLOC|CHAPITRE|UNIT[ÉE])\b/iu', $t)) {
        if ($inList) { $html .= '</ul>'; $inList = false; }
        $html .= '<h3 class="prog-module">' . h($t) . '</h3>';
        continue;
      }
      $item = preg_replace('/^[\x{2022}\-\*>•]+\s*/u', '', $t);
      if ($item !== $t) {
        if (!$inList) { $html .= '<ul class="prog-list">'; $inList = true; }
        $html .= '<li>' . h($item) . '</li>';
      } else {
        if ($inList) { $html .= '</ul>'; $inList = false; }
        $html .= '<p>' . h($t) . '</p>';
      }
    }
    if ($inList) $html .= '</ul>';
    return $html;
  }
}

/* Paramètres */
$formationId = (int)($_GET['formation_id'] ?? 0);
$slug = trim($_GET['slug'] ?? '');

/* Source tracking (option) : ?src=whatsapp / facebook / google / autre */
$src = trim($_GET['src'] ?? '');
$src = $src !== '' ? strtolower($src) : null;

/* Récup données */
if ($formationId <= 0 && $slug === '') {
  http_response_code(404);
  exit("Formation introuvable.");
}

if ($formationId > 0) {
  $stmt = $pdo->prepare("
    SELECT f.*, l.*
    FROM formations f
    INNER JOIN formation_landings l ON l.formation_id = f.id
    WHERE f.id = ? AND f.statut='active'
    LIMIT 1
  ");
  $stmt->execute([$formationId]);
} else {
  $stmt = $pdo->prepare("
    SELECT f.*, l.*
    FROM formations f
    INNER JOIN formation_landings l ON l.formation_id = f.id
    WHERE f.statut='active'
      AND (f.slug = ? OR f.slug IS NULL AND LOWER(REPLACE(f.titre,' ','-')) = ?)
    LIMIT 1
  ");
  $stmt->execute([$slug, $slug]);
}

$d = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$d) { http_response_code(404); exit("Landing non disponible."); }

/* Canonical slug */
$finalSlug = $d['slug'] ?: slugify($d['titre']);
$canonicalPath = "/formations/" . $finalSlug;

/* SEO */
$pageTitle = $d['seo_title'] ?: ($d['hero_title'] ?: $d['titre']);
$pageDescription = $d['seo_description']
  ?: substr(strip_tags($d['pitch_marketing'] ?: $d['pitch'] ?: ''), 0, 160);

$img = !empty($d['hero_image']) ? ('/' . ltrim($d['hero_image'], '/')) : null;

/* Tracking vue */
try{
  $ip = $_SERVER['REMOTE_ADDR'] ?? null;
  $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
  $ref = substr($_SERVER['HTTP_REFERER'] ?? '', 0, 255);

  $stmtV = $pdo->prepare("
    INSERT INTO landing_views (formation_id, ip_address, user_agent, referer, source)
    VALUES (?, ?, ?, ?, ?)
  ");
  $stmtV->execute([(int)$d['id'], $ip, $ua ?: null, $ref ?: null, $src]);
}catch(Exception $e){
  // silencieux
}

/* CTA WhatsApp */
$whatsNumber = "2250778882592"; // adapte si tu veux un numéro différent
$waText = "Nouvelle préinscription IBIG EDUFORM\n\n"
        . "Formation : ".$d['titre']."\n"
        . "Nom : \nTéléphone : \nEmail : \n\n"
        . "Lien : ".(isset($_SERVER['HTTP_HOST']) ? ("https://".$_SERVER['HTTP_HOST'].$canonicalPath) : $canonicalPath);
$waLink = "https://wa.me/".$whatsNumber."?text=".urlencode($waText);

/* CTA Appel */
$callLink = "tel:+2250778882592";

/* PDF */
$pdfLink = "/formations/pdf.php?formation_id=".(int)$d['id'];
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title><?= h($pageTitle); ?></title>
<meta name="description" content="<?= h($pageDescription); ?>">
<link rel="canonical" href="<?= h($canonicalPath); ?>">
<meta name="viewport" content="width=device-width,initial-scale=1">

<!-- Open Graph / WhatsApp / Facebook -->
<meta property="og:type" content="website">
<meta property="og:title" content="<?= h($pageTitle); ?>">
<meta property="og:description" content="<?= h($pageDescription); ?>">
<meta property="og:url" content="<?= h($canonicalPath); ?>">
<?php if($img): ?><meta property="og:image" content="<?= h($img); ?>"><?php endif; ?>
<meta name="twitter:card" content="summary_large_image">

<!-- Schema.org (Course / EducationalOccupationalProgram) -->
<script type="application/ld+json">
<?= json_encode([
  "@context" => "https://schema.org",
  "@type" => "Course",
  "name" => $d["titre"],
  "description" => $pageDescription,
  "provider" => [
    "@type" => "Organization",
    "name" => "IBIG EDUFORM",
    "url" => (isset($_SERVER['HTTP_HOST']) ? ("https://".$_SERVER['HTTP_HOST']) : "")
  ],
], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT); ?>
</script>

<style>
body{margin:0;font-family:Inter,system-ui,sans-serif;background:#020617;color:#e5e7eb}
.container{max-width:1200px;margin:auto;padding:50px 20px}
.hero{display:grid;grid-template-columns:1.2fr .8fr;gap:40px;align-items:center}
.hero img{width:100%;border-radius:24px}
.badge{display:inline-block;background:#f5a623;color:#111;padding:6px 14px;border-radius:999px;font-weight:800}
h1{font-size:3rem;margin:14px 0}
h2{color:#f5a623;margin-bottom:14px}
.section{margin-top:60px}
.card{background:rgba(255,255,255,.06);padding:26px;border-radius:24px}
.grid2{display:grid;grid-template-columns:1fr 1fr;gap:26px}
ul{padding-left:18px}
li{margin-bottom:8px}
.meta{opacity:.85;margin-bottom:10px}
.actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:18px}
.btn{display:inline-block;padding:14px 18px;border-radius:14px;font-weight:900;text-decoration:none}
.btn-green{background:#22c55e;color:#06210f}
.btn-amber{background:#f5a623;color:#111}
.btn-dark{background:rgba(255,255,255,.10);color:#e5e7eb;border:1px solid rgba(255,255,255,.12)}
.cta{text-align:center;margin-top:60px}
@media(max-width:900px){.hero,.grid2{grid-template-columns:1fr}}

.programme-certificat{
  color:#f5a623;
  font-weight:900;
  margin:22px 0 10px;
  font-size:1.05rem;
  letter-spacing:.04em;
  text-transform:uppercase;
}

.programme-certificat::after{
  content:"";
  display:block;
  width:42px;
  height:3px;
  background:#f5a623;
  margin-top:6px;
  border-radius:2px;
}

.programme-list{
  margin:0 0 18px 22px;
  padding:0;
}

.programme-list li{
  color:#e5e7eb;
  margin-bottom:6px;
  line-height:1.7;
}

</style>
</head>

<body>
<div class="container">

<!-- HERO -->
<section class="hero">
  <div>
    <span class="badge"><?= h($d['domaine']); ?></span>
    <h1><?= h($d['hero_title'] ?: $d['titre']); ?></h1>

    <?php if(!empty($d['hero_subtitle'])): ?>
      <p class="meta"><?= nl2br(h($d['hero_subtitle'])); ?></p>
    <?php endif; ?>

    <?php if(!empty($d['pitch_marketing'])): ?>
      <p><?= nl2br(h($d['pitch_marketing'])); ?></p>
    <?php elseif(!empty($d['pitch'])): ?>
      <p><?= nl2br(h($d['pitch'])); ?></p>
    <?php endif; ?>

    <?php if(!empty($d['promesse'])): ?>
      <div class="card">
        <strong>Promesse de la formation</strong>
        <p><?= nl2br(h($d['promesse'])); ?></p>
      </div>
    <?php endif; ?>

    <div class="actions">
      <a class="btn btn-green" href="/preinscription.php?formation_id=<?= (int)$d['id']; ?>">Préinscription</a>
      <a class="btn btn-amber" href="<?= h($waLink); ?>" target="_blank" rel="noopener">WhatsApp</a>
      <a class="btn btn-dark" href="<?= h($callLink); ?>">Appeler</a>
      <a class="btn btn-dark" href="<?= h($pdfLink); ?>" target="_blank" rel="noopener">PDF</a>
    </div>

    <p class="meta" style="margin-top:14px">
      Présentiel : <?= number_format((int)$d['tarif_presentiel']); ?> FCFA |
      En ligne : <?= number_format((int)$d['tarif_en_ligne']); ?> FCFA
    </p>
  </div>

  <?php if(!empty($d['hero_image'])): ?>
    <img src="/<?= h($d['hero_image']); ?>" alt="<?= h($d['titre']); ?>">
  <?php endif; ?>
</section>

<!-- AVANTAGES -->
<?php if(!empty($d['avantages'])): ?>
<section class="section">
  <h2>Pourquoi choisir cette formation ?</h2>
  <div class="card">
    <ul>
      <?php foreach(explode("\n",$d['avantages']) as $a): ?>
        <?php if(trim($a)): ?><li><?= h($a); ?></li><?php endif; ?>
      <?php endforeach; ?>
    </ul>
  </div>
</section>
<?php endif; ?>

<!-- CONTENU PÉDAGOGIQUE -->
<section class="section">
  <h2>Contenu pédagogique</h2>
  <div class="grid2">
    <div class="card">
      <?php if(!empty($d['contexte'])): ?><strong>Contexte & justification</strong><p><?= nl2br(h($d['contexte'])); ?></p><?php endif; ?>
      <?php if(!empty($d['objectif_general'])): ?><strong>Objectif général</strong><p><?= nl2br(h($d['objectif_general'])); ?></p><?php endif; ?>
      <?php if(!empty($d['objectifs_specifiques'])): ?><strong>Objectifs spécifiques</strong><p><?= nl2br(h($d['objectifs_specifiques'])); ?></p><?php endif; ?>
      <?php if(!empty($d['resultats_attendus'])): ?><strong>Résultats attendus</strong><p><?= nl2br(h($d['resultats_attendus'])); ?></p><?php endif; ?>
    </div>
    <div class="card">
      <?php if(!empty($d['public_cible'])): ?><strong>Public cible</strong><p><?= nl2br(h($d['public_cible'])); ?></p><?php endif; ?>
      <?php if(!empty($d['prerequis'])): ?><strong>Prérequis</strong><p><?= nl2br(h($d['prerequis'])); ?></p><?php endif; ?>
      <?php if(!empty($d['contenu_programme'])): ?>
          <div class="card programme-premium">
            <h2 class="programme-title">Programme</h2>
        
            <div class="programme-content">
              <?= renderProgrammeCertificats($d['contenu_programme']); ?>
            </div>
          </div>
        <?php endif; ?>
    </div>
  </div>
</section>

<!-- ORGANISATION -->
<section class="section">
  <h2>Organisation & déroulement</h2>
  <div class="card">
    <?php if(!empty($d['methodologie'])): ?><strong>Méthodologie</strong><p><?= nl2br(h($d['methodologie'])); ?></p><?php endif; ?>
    <?php if(!empty($d['duree_organisation'])): ?><strong>Durée & organisation</strong><p><?= nl2br(h($d['duree_organisation'])); ?></p><?php endif; ?>
    <?php if(!empty($d['formateurs'])): ?><strong>Formateurs / intervenants</strong><p><?= nl2br(h($d['formateurs'])); ?></p><?php endif; ?>
    <?php if(!empty($d['evaluation'])): ?><strong>Modalités d’évaluation</strong><p><?= nl2br(h($d['evaluation'])); ?></p><?php endif; ?>
    <?php if(!empty($d['moyens_logistiques'])): ?><strong>Moyens logistiques</strong><p><?= nl2br(h($d['moyens_logistiques'])); ?></p><?php endif; ?>
  </div>
</section>

<!-- DÉBOUCHÉS -->
<?php if(!empty($d['debouches'])): ?>
<section class="section">
  <h2>Débouchés professionnels</h2>
  <div class="card">
    <p><?= nl2br(h($d['debouches'])); ?></p>
  </div>
</section>
<?php endif; ?>

<!-- TARIFS -->
<section class="section">
  <h2>Tarifs & participation</h2>
  <div class="card">
    <?php if(!empty($d['tarifs'])): ?><p><?= nl2br(h($d['tarifs'])); ?></p><?php endif; ?>
    <p>
      <strong>Présentiel :</strong> <?= number_format((int)$d['tarif_presentiel']); ?> FCFA<br>
      <strong>En ligne :</strong> <?= number_format((int)$d['tarif_en_ligne']); ?> FCFA
    </p>
    <?php if(!empty($d['modalites_participation'])): ?><p><?= nl2br(h($d['modalites_participation'])); ?></p><?php endif; ?>
    <?php if(!empty($d['contacts'])): ?><p><?= nl2br(h($d['contacts'])); ?></p><?php endif; ?>
    <?php if(!empty($d['validation'])): ?><p><strong>Validation :</strong><br><?= nl2br(h($d['validation'])); ?></p><?php endif; ?>
  </div>
</section>

<!-- CTA bas -->
<section class="cta">
  <a class="btn btn-green" href="/preinscription.php?formation_id=<?= (int)$d['id']; ?>">
    <?= h($d['appel_action'] ?: 'Je me préinscris maintenant'); ?>
  </a>
</section>

<?php $f = ['domaine' => ($d['domaine'] ?? ''), 'titre' => ($d['titre'] ?? ''), 'is_samedi_pro' => ($d['is_samedi_pro'] ?? 0), 'tarif_en_ligne' => ($d['tarif_en_ligne'] ?? 0)]; ?>
<?php include __DIR__ . '/../partials/fiche_conversion.php'; ?>
<?php include __DIR__ . '/../partials/besoin_cta.php'; ?>

</div>
</body>
</html>
