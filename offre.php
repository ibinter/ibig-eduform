<?php
declare(strict_types=1);

/**
 * ============================================================
 * IBIG EDUFORM — offre.php (DÉTAIL OFFRE) — PREMIUM FUTURISTE
 * ------------------------------------------------------------
 * Objectifs :
 *  - Page détail type educarriere / emploi.ci / Indeed (mobile-first)
 *  - ZÉRO emoji direct : UNIQUEMENT codes HTML Unicode (incorruptibles)
 *  - Afficher toutes les infos possibles : délai/date limite, salaire, expérience, etc.
 *  - Offres similaires en bas + Partage social en bas
 *  - Barre sticky mobile "Postuler"
 *  - SEO JobPosting (Google Jobs)
 *  - CSS scoppé : ne casse pas le footer global
 * ============================================================
 */

require_once __DIR__ . '/core/config.php';
require_once __DIR__ . '/core/database.php';

$pageTitle = "Offre d'emploi – IBIG EDUFORM";

$pdo = Database::connect();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

/* ============================================================
   HELPERS (SAFE)
============================================================ */
function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function is_non_empty($v): bool { return trim((string)$v) !== ''; }

function col_exists(PDO $pdo, string $table, string $col): bool {
  static $cache = [];
  $key = $table . '.' . $col;
  if (isset($cache[$key])) return $cache[$key];
  try {
    $st = $pdo->prepare("SHOW COLUMNS FROM $table LIKE ?");
    $st->execute([$col]);
    $cache[$key] = (bool)$st->fetch(PDO::FETCH_ASSOC);
  } catch (Throwable $e) {
    $cache[$key] = false;
  }
  return $cache[$key];
}

function safe_date(?string $dt, string $fmt='d/m/Y'): string {
  $dt = trim((string)$dt);
  if ($dt === '' || $dt === '0000-00-00' || $dt === '0000-00-00 00:00:00') return '—';
  $ts = strtotime($dt);
  return $ts ? date($fmt, $ts) : '—';
}

function current_url(): string {
  $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
  $scheme = $https ? 'https' : 'http';
  $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
  $uri  = $_SERVER['REQUEST_URI'] ?? '/';
  return $scheme . '://' . $host . $uri;
}

function normalize_phone(string $s): string {
  $s = trim($s);
  if ($s === '') return '';
  $s = preg_replace('/[^\d\+]/', '', $s);
  return $s ?: '';
}

function read_first_non_empty(array $row, array $keys, string $default=''): string {
  foreach ($keys as $k) {
    if (array_key_exists($k, $row) && trim((string)$row[$k]) !== '') return (string)$row[$k];
  }
  return $default;
}

/* ============================================================
   CONFIG TABLE
============================================================ */
$table = 'opportunites_emploi';

/* ============================================================
   IDENTIFIANT : id ou slug
============================================================ */
$slug = trim((string)($_GET['slug'] ?? ''));

if ($slug === '') {
  http_response_code(404);
  exit('Offre introuvable');
}
/* ============================================================
   LOAD OFFRE (SELECT *)
============================================================ */

$offre = null;

try {

  $st = $pdo->prepare("SELECT * FROM $table WHERE slug = ? LIMIT 1");
  $st->execute([$slug]);
  $offre = $st->fetch(PDO::FETCH_ASSOC);

} catch (Throwable $e) {
  $offre = null;
}

/* si offre inexistante */
if (!$offre) {

  http_response_code(404);

  include __DIR__ . '/partials/header.php';
  ?>

  <div style="max-width:1000px;margin:0 auto;padding:40px 16px;">
    <h2>Offre introuvable</h2>
    <p>Cette offre est introuvable ou a été retirée.</p>
    <p><a href="opportunites-emploi.php">Retour aux offres</a></p>
  </div>

  <?php
  include __DIR__ . '/partials/footer.php';
  exit;
}

/* ID de l'offre pour les similaires */
$id = (int)$offre['id'];

/* ============================================================
   NORMALISATION : champs (tolérant)
============================================================ */
$titre            = read_first_non_empty($offre, ['titre','title','poste','intitule'], 'Offre d\'emploi');
$entreprise       = read_first_non_empty($offre, ['entreprise','entreprise_nom','societe','company','employeur'], '—');
$lieu             = read_first_non_empty($offre, ['lieu','ville','localisation','location'], '');
$type             = read_first_non_empty($offre, ['type','type_contrat','contrat','employment_type'], '');
$statut           = read_first_non_empty($offre, ['statut','status'], '');
$reference        = read_first_non_empty($offre, ['reference','ref','code'], '');
$secteur          = read_first_non_empty($offre, ['secteur','domaine','categorie','metier'], '');
$niveau           = read_first_non_empty($offre, ['niveau','niveau_etude','niveau_etudes','education_level'], '');
$experience       = read_first_non_empty($offre, ['experience','annees_experience','exp','seniorite'], '');
$salaire          = read_first_non_empty($offre, ['salaire','remuneration','salary','package'], '');
$mode             = read_first_non_empty($offre, ['mode','teletravail','work_mode'], '');
$created_at       = read_first_non_empty($offre, ['created_at','date_publication','published_at','date_pub'], '');
$date_limite = read_first_non_empty(
  $offre,
  ['date_expiration','date_limite','deadline','date_fin','valid_until','delai'],
  ''
);
$description      = read_first_non_empty($offre, ['description','details','contenu','body'], '');
$exigences        = read_first_non_empty($offre, ['exigences','requirements','competences'], '');
$missions_txt     = read_first_non_empty($offre, ['missions','responsabilites','responsabilites_txt','taches'], '');
$avantages_txt    = read_first_non_empty($offre, ['avantages','conditions','benefices'], '');
$candidature_txt  = read_first_non_empty($offre, ['candidature','dossier_candidature','comment_postuler','instructions'], '');
$email_candidature= read_first_non_empty($offre, ['email_candidature','email','mail_recrutement','email_recrutement'], '');
$lien_candidature = read_first_non_empty($offre, ['lien_candidature','url_candidature','lien','apply_url'], '');
$whatsapp         = read_first_non_empty($offre, ['whatsapp','contact_whatsapp','phone','telephone','tel'], '');
$whatsapp_norm    = normalize_phone($whatsapp);

/* ============================================================
   CALCUL DU DÉLAI (RESTE X JOURS)
============================================================ */
$delai_label = '';
$delai_class = '';

if (is_non_empty($date_limite)) {
  $now = new DateTime('today');
  $end = new DateTime($date_limite);

  $diff = (int)$now->diff($end)->format('%r%a');

  if ($diff > 1) {
    $delai_label = "Il reste $diff jours";
    $delai_class = 'jobx-chip-ok';
  } elseif ($diff === 1) {
    $delai_label = "Dernier jour";
    $delai_class = 'jobx-chip-warn';
  } elseif ($diff === 0) {
    $delai_label = "Clôture aujourd’hui";
    $delai_class = 'jobx-chip-warn';
  } else {
    $delai_label = "Offre expirée";
    $delai_class = 'jobx-chip-expired';
  }
}

/* pays : si pays_id existe -> lookup */
$pays_nom = read_first_non_empty($offre, ['pays_nom','pays'], '');
$pays_id  = (int)($offre['pays_id'] ?? 0);

if ($pays_nom === '' && $pays_id > 0) {
  try {
    $st = $pdo->prepare("SELECT nom FROM pays WHERE id = ? LIMIT 1");
    $st->execute([$pays_id]);
    $r = $st->fetch(PDO::FETCH_ASSOC);
    if ($r && isset($r['nom'])) $pays_nom = (string)$r['nom'];
  } catch (Throwable $e) { /* ignore */ }
}

/* ============================================================
   STRUCTURATION INTELLIGENTE DESCRIPTION
============================================================ */
function split_sections(string $text): array {
  $t = trim($text);
  $t = str_replace(["\r\n","\r"], "\n", $t);

  $out = [
    'description' => '',
    'mission' => '',
    'responsabilites' => '',
    'profil' => '',
    'avantages' => '',
    'candidature' => ''
  ];
  if ($t === '') return $out;

  $lines = explode("\n", $t);
  $cur = 'description';

  $map = [
    'mission' => ['mission','mission principale'],
    'responsabilites' => ['responsabilites','responsabilités','taches','tâches','role','rôle','missions'],
    'profil' => ['profil','profil recherche','profil recherché','competences','compétences','qualifications','exigences'],
    'avantages' => ['avantages','conditions','remuneration','rémunération','benefices','bénéfices','ce que nous offrons'],
    'candidature' => ['candidature','dossier de candidature','comment postuler','pour postuler']
  ];

  foreach ($lines as $ln) {
    $raw = trim($ln);
    $low = mb_strtolower($raw, 'UTF-8');
    $found = null;

    foreach ($map as $k => $arr) {
      foreach ($arr as $needle) {
        $needle = mb_strtolower($needle, 'UTF-8');
        if ($low === $needle || $low === ($needle.':') || preg_match('/^'.preg_quote($needle,'/').'\s*[:\-–—]\s*$/u', $low)) {
          $found = $k;
          break 2;
        }
      }
    }

    if ($found) { $cur = $found; continue; }
    $out[$cur] .= $ln . "\n";
  }

  foreach ($out as $k => $v) $out[$k] = trim($v);
  return $out;
}

/* base : split description, merge champs spécifiques */
$sec = split_sections($description);
if ($missions_txt !== '' && $sec['responsabilites'] === '') $sec['responsabilites'] = trim($missions_txt);
if ($exigences !== '' && $sec['profil'] === '') $sec['profil'] = trim($exigences);
if ($avantages_txt !== '' && $sec['avantages'] === '') $sec['avantages'] = trim($avantages_txt);
if ($candidature_txt !== '' && $sec['candidature'] === '') $sec['candidature'] = trim($candidature_txt);

$main_desc = $sec['description'] !== '' ? $sec['description'] : $description;

/* ============================================================
   SHARE URLS
============================================================ */
$shareUrl   = current_url();
$shareTitle = $titre ?: "Offre d'emploi";
$shareText  = $shareTitle . " – " . ($entreprise ?: "IBIG EDUFORM");

$shareWA = "https://wa.me/?text=" . rawurlencode($shareText . " " . $shareUrl);
$shareFB = "https://www.facebook.com/sharer/sharer.php?u=" . rawurlencode($shareUrl);
$shareLN = "https://www.linkedin.com/sharing/share-offsite/?url=" . rawurlencode($shareUrl);
$shareX  = "https://twitter.com/intent/tweet?text=" . rawurlencode($shareText) . "&url=" . rawurlencode($shareUrl);

/* ============================================================
   OFFRES SIMILAIRES
============================================================ */
$similaires = [];
try {
  $where = [];
  $params = [$id];

  if ($type !== '') { $where[] = "type = ?"; $params[] = $type; }
  if ($lieu !== '') { $where[] = "lieu = ?"; $params[] = $lieu; }
  if ($secteur !== '' && col_exists($pdo, $table, 'secteur')) { $where[] = "secteur = ?"; $params[] = $secteur; }

  $whereSql = $where ? (" AND (" . implode(" OR ", $where) . ")") : "";
  $sql = "SELECT id, slug, titre, entreprise, lieu, type, created_at
        FROM $table
        WHERE id != ? $whereSql
        ORDER BY created_at DESC, id DESC
        LIMIT 6";
  $st = $pdo->prepare($sql);
  $st->execute($params);
  $similaires = $st->fetchAll(PDO::FETCH_ASSOC);

  if (!$similaires) {
    $st = $pdo->prepare("SELECT id, titre, entreprise, lieu, type, created_at
                         FROM $table
                         WHERE id != ?
                         ORDER BY created_at DESC, id DESC
                         LIMIT 6");
    $st->execute([$id]);
    $similaires = $st->fetchAll(PDO::FETCH_ASSOC);
  }
} catch (Throwable $e) {
  $similaires = [];
}

/* ============================================================
   SEO JobPosting (Google Jobs)
============================================================ */
$jobPosting = [
  "@context" => "https://schema.org",
  "@type" => "JobPosting",
  "title" => $shareTitle,
  "description" => $main_desc ?: $description,
  "datePosted" => ($created_at && strtotime($created_at)) ? date('c', strtotime($created_at)) : null,
  "validThrough" => ($date_limite && strtotime($date_limite)) ? date('c', strtotime($date_limite)) : null,
  "employmentType" => $type ?: null,
  "hiringOrganization" => [
    "@type" => "Organization",
    "name" => $entreprise ?: "IBIG EDUFORM",
  ],
  "jobLocation" => [
    "@type" => "Place",
    "address" => [
      "@type" => "PostalAddress",
      "addressLocality" => $lieu ?: null,
      "addressCountry" => $pays_nom ?: null,
    ]
  ],
];
$jobPosting = array_filter($jobPosting, fn($v) => $v !== null);

$metaLieu = trim($lieu . ($pays_nom ? ', '.$pays_nom : ''));
if ($metaLieu === '') $metaLieu = '—';

$pageTitle = h($titre) . " – " . h($entreprise);
include __DIR__ . '/partials/header.php';

/* ============================================================
   RENDER
============================================================ */
?>

<!-- ============================================================
 OFFRE DETAIL PAGE (SCOPED)
============================================================ -->
<div class="jobx-page">

  <!-- HERO -->
  <header class="jobx-hero">
    <div class="jobx-wrap">
      <div class="jobx-bc">
        <a href="opportunites-emploi.php">Opportunités</a>
        <span class="jobx-sep">&#x203A;</span>
        <span>Détail</span>
      </div>

      <h1 class="jobx-h1"><?= h($titre); ?></h1>

      <div class="jobx-meta">
        <span class="jobx-chip jobx-chip-company"><?= h($entreprise); ?></span>

        <?php if ($metaLieu !== '—'): ?>
          <span class="jobx-chip jobx-chip-soft">
            <span class="jobx-ico" aria-hidden="true">&#x1F4CD;</span>
            <?= h($metaLieu); ?>
          </span>
        <?php endif; ?>

        <?php if (is_non_empty($type)): ?>
          <span class="jobx-chip jobx-chip-type"><?= h($type); ?></span>
        <?php endif; ?>

        <span class="jobx-chip jobx-chip-soft">
          <span class="jobx-ico" aria-hidden="true">&#x1F4C5;</span>
          Publiée le <?= h(safe_date($created_at)); ?>
        </span>

        <?php if (is_non_empty($date_limite)): ?>
          <span class="jobx-chip jobx-chip-deadline">
            <span class="jobx-ico" aria-hidden="true">&#x23F3;</span>
            Date limite : <?= h(safe_date($date_limite)); ?>
          </span>
        <?php endif; ?>
        
        <?php if ($delai_label !== ''): ?>
          <span class="jobx-chip <?= h($delai_class); ?>">
            <span class="jobx-ico" aria-hidden="true">&#x23F3;</span>
            <?= h($delai_label); ?>
          </span>
        <?php endif; ?>

        <?php if (is_non_empty($statut)): ?>
          <span class="jobx-chip jobx-chip-status"><?= h($statut); ?></span>
        <?php endif; ?>

        <?php if (is_non_empty($reference)): ?>
          <span class="jobx-chip jobx-chip-soft">Réf : <?= h($reference); ?></span>
        <?php endif; ?>
      </div>

      <div class="jobx-actions">
        <a class="jobx-btn jobx-btn-primary" href="#jobx-postuler">
          <span class="jobx-ico" aria-hidden="true">&#x1F680;</span>
          Postuler maintenant
        </a>
        <button type="button" class="jobx-btn jobx-btn-ghost" id="jobxCopyTop">
          <span class="jobx-ico" aria-hidden="true">&#x1F4CB;</span>
          Copier le lien
        </button>
        <button type="button" class="jobx-btn jobx-btn-ghost" onclick="window.print()">
          <span class="jobx-ico" aria-hidden="true">&#x1F5A8;</span>
          Imprimer
        </button>
        <button type="button" class="jobx-btn jobx-btn-outline" id="jobxShareTop">
          <span class="jobx-ico" aria-hidden="true">&#x1F4E3;</span>
          Partager
        </button>
      </div>

      <div class="jobx-sharebar" id="jobxSharebarTop" aria-label="Partage">
        <a class="jobx-share jobx-share-wa" href="<?= h($shareWA); ?>" target="_blank" rel="noopener">WhatsApp</a>
        <a class="jobx-share" href="<?= h($shareFB); ?>" target="_blank" rel="noopener">Facebook</a>
        <a class="jobx-share" href="<?= h($shareLN); ?>" target="_blank" rel="noopener">LinkedIn</a>
        <a class="jobx-share" href="<?= h($shareX); ?>" target="_blank" rel="noopener">X</a>
      </div>
    </div>
  </header>

  <!-- MAIN -->
  <main class="jobx-main jobx-wrap">

    <!-- LEFT -->
    <section class="jobx-left">

      <!-- Résumé -->
      <article class="jobx-card">
        <div class="jobx-card-h">
          <h2>Résumé</h2>
          <span class="jobx-badge">1 min</span>
        </div>

        <div class="jobx-kv">
          <div class="jobx-kv-row"><div class="jobx-k">Entreprise</div><div class="jobx-v"><?= h($entreprise); ?></div></div>
          <div class="jobx-kv-row"><div class="jobx-k">Lieu</div><div class="jobx-v"><?= h($metaLieu); ?></div></div>
          <div class="jobx-kv-row"><div class="jobx-k">Type</div><div class="jobx-v"><?= h($type ?: '—'); ?></div></div>
          <div class="jobx-kv-row"><div class="jobx-k">Publié</div><div class="jobx-v"><?= h(safe_date($created_at)); ?></div></div>

          <?php if (is_non_empty($date_limite)): ?>
            <div class="jobx-kv-row"><div class="jobx-k">Date limite</div><div class="jobx-v"><?= h(safe_date($date_limite)); ?></div></div>
          <?php endif; ?>

          <?php if (is_non_empty($secteur)): ?>
            <div class="jobx-kv-row"><div class="jobx-k">Secteur</div><div class="jobx-v"><?= h($secteur); ?></div></div>
          <?php endif; ?>

          <?php if (is_non_empty($niveau)): ?>
            <div class="jobx-kv-row"><div class="jobx-k">Niveau</div><div class="jobx-v"><?= h($niveau); ?></div></div>
          <?php endif; ?>

          <?php if (is_non_empty($experience)): ?>
            <div class="jobx-kv-row"><div class="jobx-k">Expérience</div><div class="jobx-v"><?= h($experience); ?></div></div>
          <?php endif; ?>

          <?php if (is_non_empty($salaire)): ?>
            <div class="jobx-kv-row"><div class="jobx-k">Rémunération</div><div class="jobx-v"><?= h($salaire); ?></div></div>
          <?php endif; ?>

          <?php if (is_non_empty($mode)): ?>
            <div class="jobx-kv-row"><div class="jobx-k">Mode</div><div class="jobx-v"><?= h($mode); ?></div></div>
          <?php endif; ?>
        </div>

        <div class="jobx-cta-row">
          <a class="jobx-btn jobx-btn-primary jobx-btn-block" href="#jobx-postuler">
            <span class="jobx-ico" aria-hidden="true">&#x1F680;</span>
            Postuler
          </a>
          <a class="jobx-btn jobx-btn-ghost jobx-btn-block" href="opportunites-emploi.php">
            Retour aux offres
          </a>
        </div>
      </article>

      <!-- Description structurée -->
      <article class="jobx-card">
        <div class="jobx-card-h">
          <h2>Description du poste</h2>
          <span class="jobx-badge">Détails</span>
        </div>

        <div class="jobx-sections">
          <section class="jobx-block">
            <h3><span class="jobx-ico2" aria-hidden="true">&#x1F4DD;</span> Description</h3>
            <div class="jobx-text"><?= nl2br(h($main_desc ?: '—')); ?></div>
          </section>

          <?php if (is_non_empty($sec['mission'])): ?>
            <section class="jobx-block">
              <h3><span class="jobx-ico2" aria-hidden="true">&#x1F3AF;</span> Mission principale</h3>
              <div class="jobx-text"><?= nl2br(h($sec['mission'])); ?></div>
            </section>
          <?php endif; ?>

          <?php if (is_non_empty($sec['responsabilites'])): ?>
            <section class="jobx-block">
              <h3><span class="jobx-ico2" aria-hidden="true">&#x1F9E9;</span> Responsabilités clés</h3>
              <div class="jobx-text"><?= nl2br(h($sec['responsabilites'])); ?></div>
            </section>
          <?php endif; ?>

          <?php if (is_non_empty($sec['profil'])): ?>
            <section class="jobx-block">
              <h3><span class="jobx-ico2" aria-hidden="true">&#x1F464;</span> Profil recherché</h3>
              <div class="jobx-text"><?= nl2br(h($sec['profil'])); ?></div>
            </section>
          <?php endif; ?>

          <?php if (is_non_empty($sec['avantages'])): ?>
            <section class="jobx-block">
              <h3><span class="jobx-ico2" aria-hidden="true">&#x1F4BC;</span> Conditions &amp; avantages</h3>
              <div class="jobx-text"><?= nl2br(h($sec['avantages'])); ?></div>
            </section>
          <?php endif; ?>
        </div>
      </article>

      <!-- Candidature -->
      <article class="jobx-card" id="jobx-postuler">
        <div class="jobx-card-h">
          <h2>Dossier de candidature</h2>
          <span class="jobx-badge">Postuler</span>
        </div>

        <?php if (is_non_empty($sec['candidature'])): ?>
          <div class="jobx-note"><?= nl2br(h($sec['candidature'])); ?></div>
        <?php else: ?>
          <div class="jobx-note">
            Envoyez votre <strong>CV</strong> et une courte présentation.
            Vous pouvez postuler via le lien ou le contact ci-dessous.
          </div>
        <?php endif; ?>

        <div class="jobx-apply">
          <?php if (is_non_empty($lien_candidature)): ?>
            <a class="jobx-apply-card jobx-apply-primary" href="<?= h($lien_candidature); ?>" target="_blank" rel="noopener">
              <div class="jobx-apply-ico" aria-hidden="true">&#x1F517;</div>
              <div class="jobx-apply-t">Postuler en ligne</div>
              <div class="jobx-apply-s">Ouvrir le lien / formulaire</div>
            </a>
          <?php endif; ?>

          <?php if (is_non_empty($email_candidature)): ?>
            <a class="jobx-apply-card" href="mailto:<?= h($email_candidature); ?>?subject=<?= rawurlencode('Candidature - ' . $shareTitle); ?>">
              <div class="jobx-apply-ico" aria-hidden="true">&#x2709;</div>
              <div class="jobx-apply-t">Envoyer par email</div>
              <div class="jobx-apply-s"><?= h($email_candidature); ?></div>
            </a>
          <?php endif; ?>

          <?php if (is_non_empty($whatsapp_norm)): ?>
            <a class="jobx-apply-card" href="https://wa.me/<?= h(preg_replace('/\D+/', '', $whatsapp_norm)); ?>?text=<?= rawurlencode('Bonjour, je souhaite postuler à : '.$shareTitle.' '.$shareUrl); ?>" target="_blank" rel="noopener">
              <div class="jobx-apply-ico" aria-hidden="true">&#x1F4AC;</div>
              <div class="jobx-apply-t">Postuler via WhatsApp</div>
              <div class="jobx-apply-s"><?= h($whatsapp); ?></div>
            </a>
          <?php endif; ?>

          <?php if (!is_non_empty($lien_candidature) && !is_non_empty($email_candidature) && !is_non_empty($whatsapp_norm)): ?>
            <div class="jobx-apply-card jobx-apply-muted">
              <div class="jobx-apply-ico" aria-hidden="true">&#x26A0;</div>
              <div class="jobx-apply-t">Contact de candidature non défini</div>
              <div class="jobx-apply-s">Ajoutez email_candidature / lien_candidature / whatsapp en base.</div>
            </div>
          <?php endif; ?>
        </div>
      </article>

      <!-- Partage en bas -->
      <article class="jobx-card">
        <div class="jobx-card-h">
          <h2>Partager cette offre</h2>
          <span class="jobx-badge">Réseaux</span>
        </div>

        <p class="jobx-small">Aidez votre réseau : partagez cette opportunité sur vos plateformes.</p>

        <div class="jobx-share-bottom">
          <a class="jobx-sbtn jobx-sbtn-wa" href="<?= h($shareWA); ?>" target="_blank" rel="noopener">WhatsApp</a>
          <a class="jobx-sbtn" href="<?= h($shareFB); ?>" target="_blank" rel="noopener">Facebook</a>
          <a class="jobx-sbtn" href="<?= h($shareLN); ?>" target="_blank" rel="noopener">LinkedIn</a>
          <a class="jobx-sbtn" href="<?= h($shareX); ?>" target="_blank" rel="noopener">X</a>
          <button type="button" class="jobx-sbtn jobx-sbtn-copy" id="jobxCopyBottom">Copier le lien</button>
          <button type="button" class="jobx-sbtn jobx-sbtn-share" id="jobxWebShare">Partager (mobile)</button>
        </div>
      </article>

      <!-- Offres similaires -->
      <?php if (!empty($similaires)): ?>
        <article class="jobx-similar">
          <div class="jobx-similar-h">
            <h2>Offres similaires</h2>
            <p>Suggestions basées sur le type, le lieu et les offres récentes.</p>
          </div>

          <div class="jobx-similar-grid">
            <?php foreach ($similaires as $s): ?>
              <?php
              $sid = (int)($s['id'] ?? 0);
              $stt = (string)($s['titre'] ?? '');
              $sen = (string)($s['entreprise'] ?? '—');
              $sli = (string)($s['lieu'] ?? '');
              $sty = (string)($s['type'] ?? '');
              $sdt = (string)($s['created_at'] ?? '');
              ?>
              <a class="jobx-sim-card" href="/offre/<?= h($s['slug']); ?>">
                <div class="jobx-sim-top">
                  <div class="jobx-sim-title"><?= h($stt); ?></div>
                  <div class="jobx-sim-sub"><?= h($sen); ?></div>
                </div>
                <div class="jobx-sim-tags">
                  <?php if (trim($sty) !== ''): ?><span><?= h($sty); ?></span><?php endif; ?>
                  <?php if (trim($sli) !== ''): ?><span><?= h($sli); ?></span><?php endif; ?>
                  <span><?= h(safe_date($sdt)); ?></span>
                </div>
              </a>
            <?php endforeach; ?>
          </div>
        </article>
      <?php endif; ?>

    </section>

    <!-- RIGHT -->
    <aside class="jobx-right">
      <div class="jobx-sticky">
        <div class="jobx-mini">
          <div class="jobx-mini-h">Action rapide</div>
          <a class="jobx-btn jobx-btn-primary jobx-btn-block" href="#jobx-postuler">
            <span class="jobx-ico" aria-hidden="true">&#x1F680;</span>
            Postuler
          </a>
          <button type="button" class="jobx-btn jobx-btn-ghost jobx-btn-block" id="jobxCopySide">
            <span class="jobx-ico" aria-hidden="true">&#x1F4CB;</span>
            Copier le lien
          </button>
          <button type="button" class="jobx-btn jobx-btn-outline jobx-btn-block" id="jobxShareSide">
            <span class="jobx-ico" aria-hidden="true">&#x1F4E3;</span>
            Partager
          </button>

          <div class="jobx-sharebar jobx-sharebar-side" id="jobxSharebarSide">
            <a class="jobx-share jobx-share-wa" href="<?= h($shareWA); ?>" target="_blank" rel="noopener">WhatsApp</a>
            <a class="jobx-share" href="<?= h($shareFB); ?>" target="_blank" rel="noopener">Facebook</a>
            <a class="jobx-share" href="<?= h($shareLN); ?>" target="_blank" rel="noopener">LinkedIn</a>
            <a class="jobx-share" id="jobxCopyInline" href="#" onclick="return false;">Copier</a>
          </div>

          <a class="jobx-btn jobx-btn-ghost jobx-btn-block" href="opportunites-emploi.php">Retour aux offres</a>
        </div>
      </div>
    </aside>

  </main>

  <!-- Sticky mobile bar -->
  <div class="jobx-mobilebar">
    <a class="jobx-mbtn" href="#jobx-postuler">
      <span class="jobx-ico" aria-hidden="true">&#x1F680;</span>
      Postuler
    </a>
    <button class="jobx-mbtn jobx-mbtn-ghost" type="button" id="jobxCopyMobile">
      <span class="jobx-ico" aria-hidden="true">&#x1F4CB;</span>
      Copier
    </button>
  </div>

</div>

<!-- SEO JobPosting JSON-LD -->
<script type="application/ld+json">
<?= json_encode($jobPosting, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT); ?>
</script>

<style>
/* ============================================================
   JOBX — CSS SCOPÉ (ne casse pas le footer)
============================================================ */
.jobx-page{ overflow-x:hidden; background:#f5f7fb; }
.jobx-page *{ box-sizing:border-box; }
.jobx-wrap{ max-width:1200px; margin:0 auto; padding:0 16px; }
.jobx-page{
  --jb-blue:#0b3b82;
  --jb-blue2:#071e3f;
  --jb-red:#dc2626;
  --jb-ink:#0f172a;
  --jb-muted:#64748b;
  --jb-card:#fff;
  --jb-bd:rgba(15,23,42,.10);
  --jb-shadow:0 16px 40px rgba(0,0,0,.08);
  font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;
}

/* HERO */
.jobx-hero{
  background:
    radial-gradient(1200px 520px at 12% 12%, rgba(255,255,255,.14), rgba(255,255,255,0)),
    linear-gradient(135deg,var(--jb-blue),var(--jb-blue2));
  color:#fff;
  padding:28px 0 22px;
}
.jobx-bc{ display:flex; gap:10px; flex-wrap:wrap; font-size:13px; opacity:.95; }
.jobx-bc a{ color:#fff; text-decoration:none; border-bottom:1px solid rgba(255,255,255,.25); }
.jobx-sep{ opacity:.75; }
.jobx-h1{ margin:12px 0 10px; font-size:32px; line-height:1.12; letter-spacing:.2px; }
.jobx-meta{ display:flex; flex-wrap:wrap; gap:10px; margin-top:10px; }
.jobx-chip{
  display:inline-flex; align-items:center; gap:8px;
  padding:8px 12px; border-radius:999px;
  font-size:12.8px; font-weight:900;
  border:1px solid rgba(255,255,255,.18);
  background:rgba(255,255,255,.12);
}
.jobx-chip-company{ background:rgba(220,38,38,.18); border-color:rgba(220,38,38,.25); }
.jobx-chip-type{ background:rgba(255,255,255,.18); }
.jobx-chip-soft{ background:rgba(255,255,255,.10); }
.jobx-chip-deadline{ background:rgba(234,179,8,.18); border-color:rgba(234,179,8,.25); }
.jobx-chip-status{ background:rgba(34,197,94,.18); border-color:rgba(34,197,94,.25); }
.jobx-ico{ font-size:14px; opacity:.95; }

.jobx-actions{ margin-top:16px; display:flex; gap:10px; flex-wrap:wrap; }
.jobx-btn{
  display:inline-flex; align-items:center; justify-content:center; gap:10px;
  padding:12px 14px; border-radius:14px; border:1px solid transparent;
  cursor:pointer; text-decoration:none;
  font-weight:900; font-size:14.5px; line-height:1;
  user-select:none;
  transition: transform .08s ease, box-shadow .2s ease, opacity .2s ease, background .2s ease;
}
.jobx-btn:active{ transform:scale(.99); }
.jobx-btn-primary{ background:var(--jb-red); color:#fff; box-shadow:0 14px 30px rgba(220,38,38,.25); }
.jobx-btn-primary:hover{ opacity:.96; }
.jobx-btn-ghost{ background:rgba(255,255,255,.14); color:#fff; border-color:rgba(255,255,255,.18); }
.jobx-btn-ghost:hover{ background:rgba(255,255,255,.18); }
.jobx-btn-outline{ background:rgba(255,255,255,.08); color:#fff; border-color:rgba(255,255,255,.18); }
.jobx-btn-outline:hover{ background:rgba(255,255,255,.12); }
.jobx-btn-block{ width:100%; }

.jobx-sharebar{ display:none; margin-top:12px; gap:10px; flex-wrap:wrap; }
.jobx-share{
  display:inline-flex; padding:10px 12px; border-radius:999px;
  background:rgba(255,255,255,.12);
  border:1px solid rgba(255,255,255,.18);
  color:#fff; text-decoration:none;
  font-weight:900; font-size:13px;
}
.jobx-share:hover{ background:rgba(255,255,255,.16); }
.jobx-share-wa{ background:rgba(34,197,94,.18); border-color:rgba(34,197,94,.25); }

/* MAIN */
.jobx-main{
  margin: -14px auto 0;
  display:grid;
  grid-template-columns: 1.55fr .75fr;
  gap:18px;
  padding-bottom:28px;
}
.jobx-left, .jobx-right{ min-width:0; }

/* Cards */
.jobx-card{
  background:var(--jb-card);
  border:1px solid var(--jb-bd);
  border-radius:18px;
  box-shadow: var(--jb-shadow);
  padding:18px;
  margin-bottom:14px;
}
.jobx-card-h{
  display:flex; align-items:center; justify-content:space-between; gap:10px;
  padding-bottom:10px; border-bottom:1px solid rgba(15,23,42,.08);
}
.jobx-card-h h2{ margin:0; font-size:18px; color:var(--jb-ink); }
.jobx-badge{
  padding:6px 10px; border-radius:999px;
  background:rgba(11,59,130,.08); color:var(--jb-blue);
  font-weight:900; font-size:12px;
  border:1px solid rgba(11,59,130,.14);
}
.jobx-kv{ margin-top:12px; display:flex; flex-direction:column; gap:10px; }
.jobx-kv-row{
  display:flex; justify-content:space-between; gap:10px;
  padding:10px 12px; border-radius:14px;
  background:rgba(15,23,42,.04);
  border:1px solid rgba(15,23,42,.06);
}
.jobx-k{ color:var(--jb-muted); font-weight:800; font-size:12.5px; }
.jobx-v{ color:var(--jb-ink); font-weight:900; font-size:12.8px; text-align:right; }
.jobx-cta-row{ margin-top:12px; display:flex; flex-direction:column; gap:10px; }

/* Sections */
.jobx-sections{ margin-top:12px; display:flex; flex-direction:column; gap:12px; }
.jobx-block{ background:#f9fafb; border:1px solid #e5e7eb; border-radius:16px; padding:14px; }
.jobx-block h3{
  margin:0 0 8px; font-size:15.5px; font-weight:900;
  color:var(--jb-ink);
  display:flex; align-items:center; gap:8px;
}
.jobx-ico2{ display:inline-flex; width:1.2em; justify-content:center; transform:translateY(1px); font-size:1.05em; }
.jobx-text{ color:#334155; font-size:15px; line-height:1.7; overflow-wrap:anywhere; }

/* Note */
.jobx-note{
  margin-top:12px;
  background:rgba(11,59,130,.06);
  border:1px solid rgba(11,59,130,.12);
  border-radius:14px;
  padding:12px 14px;
  color:#1f2937;
}

/* Apply cards */
.jobx-apply{ margin-top:12px; display:grid; grid-template-columns: repeat(3, 1fr); gap:12px; }
.jobx-apply-card{
  display:flex; flex-direction:column; gap:6px;
  padding:14px; border-radius:16px;
  border:1px solid rgba(15,23,42,.10);
  background:#fff; text-decoration:none; color:var(--jb-ink);
  box-shadow:0 10px 22px rgba(0,0,0,.05);
}
.jobx-apply-card:hover{ box-shadow:0 16px 34px rgba(0,0,0,.08); }
.jobx-apply-primary{ border-color:rgba(220,38,38,.25); background:rgba(220,38,38,.06); }
.jobx-apply-muted{ background:rgba(148,163,184,.10); border-style:dashed; cursor:default; }
.jobx-apply-ico{ font-size:18px; }
.jobx-apply-t{ font-weight:900; }
.jobx-apply-s{ color:var(--jb-muted); font-size:12.8px; font-weight:700; }

/* Share bottom */
.jobx-small{ margin:12px 0 0; color:var(--jb-muted); }
.jobx-share-bottom{ margin-top:14px; display:flex; gap:10px; flex-wrap:wrap; }
.jobx-sbtn{
  padding:10px 14px; border-radius:999px;
  background:#0b3b82; color:#fff; border:none;
  text-decoration:none; font-weight:900; font-size:13px;
  cursor:pointer;
  transition:opacity .2s ease, transform .08s ease;
}
.jobx-sbtn:hover{ opacity:.92; }
.jobx-sbtn:active{ transform:scale(.98); }
.jobx-sbtn-wa{ background:#16a34a; }
.jobx-sbtn-copy{ background:#334155; }
.jobx-sbtn-share{ background:#dc2626; }

/* Similar */
.jobx-similar{ margin-top:18px; }
.jobx-similar-h h2{ margin:0 0 6px; font-size:20px; color:var(--jb-ink); }
.jobx-similar-h p{ margin:0; color:var(--jb-muted); }
.jobx-similar-grid{ margin-top:12px; display:grid; grid-template-columns: repeat(3, 1fr); gap:14px; }
.jobx-sim-card{
  background:#fff; border:1px solid rgba(15,23,42,.08);
  border-radius:18px; padding:14px; text-decoration:none; color:var(--jb-ink);
  box-shadow:0 16px 34px rgba(0,0,0,.06);
  display:flex; flex-direction:column; gap:10px;
}
.jobx-sim-card:hover{ box-shadow:0 22px 44px rgba(0,0,0,.10); }
.jobx-sim-title{ font-weight:900; font-size:15.5px; line-height:1.25; }
.jobx-sim-sub{ color:var(--jb-muted); font-weight:800; font-size:12.8px; }
.jobx-sim-tags{ display:flex; gap:8px; flex-wrap:wrap; }
.jobx-sim-tags span{
  padding:6px 10px; border-radius:999px;
  background:rgba(11,59,130,.08);
  border:1px solid rgba(11,59,130,.14);
  color:var(--jb-blue);
  font-weight:900; font-size:12px;
}

/* Right sidebar */
.jobx-sticky{ position:sticky; top:14px; }
.jobx-mini{
  background:#fff; border:1px solid rgba(15,23,42,.08);
  border-radius:18px; padding:14px; box-shadow:var(--jb-shadow);
}
.jobx-mini-h{ font-weight:900; margin-bottom:10px; font-size:15px; }
.jobx-sharebar-side{ margin-top:10px; }

/* Mobile sticky bar */
.jobx-mobilebar{
  display:none;
  position:fixed; left:0; right:0; bottom:0;
  z-index:999;
  background:#fff;
  border-top:1px solid rgba(15,23,42,.12);
  padding:10px;
  gap:10px;
}
.jobx-mbtn{
  flex:1;
  display:flex; align-items:center; justify-content:center; gap:8px;
  padding:12px; border-radius:14px;
  background:#dc2626; color:#fff; text-decoration:none;
  font-weight:900; border:none;
}
.jobx-mbtn-ghost{ background:#334155; }

/* Responsive */
@media (max-width: 1024px){
  .jobx-main{ grid-template-columns:1fr; }
  .jobx-right{ display:none; }
  .jobx-similar-grid{ grid-template-columns: repeat(2, 1fr); }
  .jobx-apply{ grid-template-columns:1fr; }
}
@media (max-width: 640px){
  .jobx-h1{ font-size:26px; }
  .jobx-actions .jobx-btn{ width:100%; }
  .jobx-similar-grid{ grid-template-columns:1fr; }
  .jobx-mobilebar{ display:flex; }
  body{ padding-bottom:70px; }
}

/* Print */
@media print{
  .jobx-actions,
  .jobx-sharebar,
  .jobx-mobilebar,
  .jobx-right,
  .jobx-similar{ display:none !important; }
  .jobx-hero{ background:#fff !important; color:#000 !important; }
  .jobx-chip{ background:#f6f6f6 !important; color:#000 !important; }
  .jobx-card{ box-shadow:none !important; }
}

/* ============================================================
   FIX — Boutons sidebar droite (fond blanc)
============================================================ */
.jobx-right .jobx-btn-ghost,
.jobx-right .jobx-btn-outline{
  background:#f8fafc;
  color:#0f172a;
  border:1px solid rgba(15,23,42,.15);
}

.jobx-right .jobx-btn-ghost:hover,
.jobx-right .jobx-btn-outline:hover{
  background:#eef2f7;
}

.jobx-right .jobx-ico{
  color:#0f172a;
}

/* ===============================
   DÉLAI / COUNTDOWN
================================ */
.jobx-chip-ok{
  background:rgba(34,197,94,.22);
  border-color:rgba(34,197,94,.35);
}
.jobx-chip-warn{
  background:rgba(234,179,8,.22);
  border-color:rgba(234,179,8,.35);
}
.jobx-chip-expired{
  background:rgba(148,163,184,.30);
  border-color:rgba(148,163,184,.45);
  text-decoration:line-through;
  opacity:.85;
}
</style>

<script>
(function(){
  const shareTop = document.getElementById('jobxShareTop');
  const shareSide = document.getElementById('jobxShareSide');
  const shareBarTop = document.getElementById('jobxSharebarTop');
  const shareBarSide = document.getElementById('jobxSharebarSide');

  const copy = (btn) => {
    const url = <?= json_encode($shareUrl, JSON_UNESCAPED_UNICODE); ?>;
    try{
      navigator.clipboard.writeText(url);
      btn.innerHTML = 'Lien copié';
      setTimeout(()=>{ btn.innerHTML = 'Copier le lien'; },1200);
    }catch(e){
      window.prompt("Copiez le lien :", url);
    }
  };

  document.getElementById('jobxCopyTop')?.addEventListener('click', function(){ copy(this); });
  document.getElementById('jobxCopyBottom')?.addEventListener('click', function(){ copy(this); });
  document.getElementById('jobxCopySide')?.addEventListener('click', function(){ copy(this); });
  document.getElementById('jobxCopyMobile')?.addEventListener('click', function(){ copy(this); });

  if(shareTop){
    shareTop.addEventListener('click', async ()=>{
      if(navigator.share){
        try{
          await navigator.share({
            title: <?= json_encode($shareTitle, JSON_UNESCAPED_UNICODE); ?>,
            text: <?= json_encode($shareText, JSON_UNESCAPED_UNICODE); ?>,
            url: <?= json_encode($shareUrl, JSON_UNESCAPED_UNICODE); ?>
          });
          return;
        }catch(e){}
      }
      if(shareBarTop) shareBarTop.style.display = shareBarTop.style.display === 'flex' ? 'none' : 'flex';
    });
  }

  if(shareSide){
    shareSide.addEventListener('click', ()=>{
      if(shareBarSide) shareBarSide.style.display = shareBarSide.style.display === 'flex' ? 'none' : 'flex';
    });
  }

  document.getElementById('jobxWebShare')?.addEventListener('click', async ()=>{
    if(navigator.share){
      try{
        await navigator.share({
          title: <?= json_encode($shareTitle, JSON_UNESCAPED_UNICODE); ?>,
          text: <?= json_encode($shareText, JSON_UNESCAPED_UNICODE); ?>,
          url: <?= json_encode($shareUrl, JSON_UNESCAPED_UNICODE); ?>
        });
      }catch(e){}
    }
  });
})();
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>