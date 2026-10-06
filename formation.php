<?php
declare(strict_types=1);

/**
 * formation.php — Fiche formation (slug ou id) — Version CLEAN & STABLE
 * - Pas de "headers already sent" (buffer)
 * - Compatible PHP 7.4+
 * - Canonical SEO avant HTML
 * - Tout dans un seul fichier (pas de header/footer include)
 */

ob_start();

require_once __DIR__ . '/core/bootstrap.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$pdo = Database::connect();

require_once __DIR__ . '/core/formation_inscription.php';
require_once __DIR__ . '/core/promo.php';
require_once __DIR__ . '/core/referral.php';
referral_capture($pdo); // capte ?ref=CODE (parrainage) si présent

/* ===========================================================
   APERÇU DE PARTAGE (Open Graph) — calculé AVANT le header
   pour que WhatsApp / Facebook affichent titre + prix + date.
=========================================================== */
$ogSlug = isset($_GET['slug']) ? trim((string)$_GET['slug']) : '';
$ogId   = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($ogSlug !== '' && ctype_digit($ogSlug)) { $ogId = (int)$ogSlug; $ogSlug = ''; }
$fHead = null;
try {
  if ($ogSlug !== '') {
    $stH = $pdo->prepare("SELECT titre, slug, description, tarif_en_ligne, date_debut FROM formations WHERE slug = ? AND statut = 'active' LIMIT 1");
    $stH->execute([$ogSlug]);
    $fHead = $stH->fetch(PDO::FETCH_ASSOC);
  } elseif ($ogId > 0) {
    $stH = $pdo->prepare("SELECT titre, slug, description, tarif_en_ligne, date_debut FROM formations WHERE id = ? AND statut = 'active' LIMIT 1");
    $stH->execute([$ogId]);
    $fHead = $stH->fetch(PDO::FETCH_ASSOC);
  }
} catch (Throwable $e) { $fHead = null; }

if ($fHead) {
  $hostH = (string)($_SERVER['HTTP_HOST'] ?? 'ibig-eduform.com');
  $pageTitle = $fHead['titre'] . ' – IBIG EDUFORM';
  $ogTitle   = $fHead['titre'] . ' – Formation certifiante IBIG EDUFORM';
  $parts = [];
  if (!empty($fHead['description']))      { $parts[] = trim(strip_tags((string)$fHead['description'])); }
  if ((int)$fHead['tarif_en_ligne'] > 0)  { $parts[] = 'À partir de ' . number_format((int)$fHead['tarif_en_ligne'], 0, ',', ' ') . ' FCFA.'; }
  if (!empty($fHead['date_debut']))       { $parts[] = 'Démarrage : ' . date('d/m/Y', strtotime((string)$fHead['date_debut'])) . '.'; }
  $ogDesc  = mb_substr(trim(implode(' ', $parts)), 0, 200);
  $ogImage = 'https://' . $hostH . '/assets/images/logo.png';
  $ogUrl   = 'https://' . $hostH . '/formation/' . (string)($fHead['slug'] ?: $ogId);
}

if (!isset($pageTitle)) {
  $pageTitle = (string)($f['titre'] ?? 'Formation') . ' – IBIG EDUFORM';
}

/* =========================
   HELPERS (SAFE)
========================= */
if (!function_exists('h')) {
  function h($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('nl')) {
  function nl($s): string {
    $s = trim((string)$s);
    return $s === '' ? '' : nl2br(h($s));
  }
}
if (!function_exists('video_embed_html')) {
  /** Génère le lecteur vidéo depuis une URL (YouTube, Vimeo, ou fichier MP4 uploadé). */
  function video_embed_html(string $url): string {
    $url = trim($url);
    if ($url === '') return '';
    if (preg_match('~(?:youtube\.com/(?:watch\?v=|embed/)|youtu\.be/)([A-Za-z0-9_-]{6,})~', $url, $m)) {
      return '<div class="video-box"><iframe src="https://www.youtube.com/embed/' . h($m[1]) . '" title="Vidéo de présentation" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen loading="lazy"></iframe></div>';
    }
    if (preg_match('~vimeo\.com/(\d+)~', $url, $m)) {
      return '<div class="video-box"><iframe src="https://player.vimeo.com/video/' . h($m[1]) . '" title="Vidéo de présentation" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen loading="lazy"></iframe></div>';
    }
    if (preg_match('~\.(mp4|webm|ogg)(\?.*)?$~i', $url)) {
      $src = (strpos($url, 'http') === 0 || strpos($url, '/') === 0) ? $url : ('/' . $url);
      return '<div class="video-box"><video src="' . h($src) . '" controls preload="metadata"></video></div>';
    }
    return '<div class="video-box"><iframe src="' . h($url) . '" title="Vidéo de présentation" allowfullscreen loading="lazy"></iframe></div>';
  }
}

/* =========================
   OUTILS MODULES
========================= */
if (!function_exists('afficher_modules_depuis_texte_plat')) {
  function afficher_modules_depuis_texte_plat(string $text): string
  {
      $text = trim($text);
      if ($text === '') return '';

      $text = str_replace(["\r\n", "\r"], "\n", $text);

      // MODULE / CERTIFICAT / BLOC / PARTIE + NUMÉRO
      $pattern = '/(MODULE|CERTIFICAT|BLOC|PARTIE)\s+(\d+)\s*[\p{Pd}:]\s*([^\n]+)/u';

      preg_match_all($pattern, $text, $matches, PREG_OFFSET_CAPTURE);

      if (empty($matches[0])) {
          $lines = array_filter(array_map('trim', explode("\n", $text)));
          if (!$lines) return '';

          $html = '<ul class="module-list">';
          foreach ($lines as $line) {
              $line = preg_replace('/^[•\-]\s*/u', '', $line);
              if ($line !== '') {
                  $html .= '<li>' . htmlspecialchars($line, ENT_QUOTES, 'UTF-8') . '</li>';
              }
          }
          return $html . '</ul>';
      }

      $html = '';
      $blocks = preg_split(
          $pattern,
          $text,
          -1,
          PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY
      );

      for ($i = 0; $i < count($blocks); $i += 4) {
          $type    = $blocks[$i] ?? '';
          $numero  = $blocks[$i + 1] ?? '';
          $titre   = $blocks[$i + 2] ?? '';
          $contenu = $blocks[$i + 3] ?? '';

          $html .= '<div class="module-title">'
                . htmlspecialchars("$type $numero – $titre", ENT_QUOTES, 'UTF-8')
                . '</div>';

          $items = preg_split('/\n/u', trim($contenu));
          if ($items) {
              $html .= '<ul class="module-list">';
              foreach ($items as $item) {
                  $item = preg_replace('/^[•\-]\s*/u', '', trim($item));
                  if ($item !== '') {
                      $html .= '<li>' . htmlspecialchars($item, ENT_QUOTES, 'UTF-8') . '</li>';
                  }
              }
              $html .= '</ul>';
          }
      }

      return $html;
  }
}

if (!function_exists('hasLanding')) {
  function hasLanding($lp): bool {
    if (!$lp) return false;
    $keys = ['hero_title','pitch_marketing','promesse','contexte','objectif_general','contenu_programme'];
    foreach ($keys as $k) {
      if (!empty(trim((string)($lp[$k] ?? '')))) return true;
    }
    return false;
  }
}

/* =========================
   CTA
========================= */
if (!function_exists('formation_cta')) {
  function formation_cta(array $f, array $inscription): void
  {
    $isSamediPro = !empty($f['is_samedi_pro']);

    /* ============================
       CAS SAMEDI PRO
    ============================ */
    if ($isSamediPro) {
      $fid   = (int)($f['id'] ?? 0);
      $prix  = (int)($f['tarif_presentiel'] ?? 0);
      $prixL = (int)($f['tarif_en_ligne'] ?? 0);
      $isFree = ($prix <= 0 && $prixL <= 0);
      $promoBtn = promo_earlybird($f);
      $prixAff  = $prix; /* prix DB inchangé — promo = stratégie d'affichage uniquement */
      ?>
      <div class="cta samedi-pro-cta">

        <?php if ($isFree): ?>
        <a class="pay" href="/preinscription.php?formation_id=<?= $fid; ?>" onclick="trackEvent('preinscription','samedi_pro_gratuit')">
          Réserver ma place
          <small>GRATUIT · SAMEDI PRO</small>
        </a>
        <?php else: ?>
        <a class="pay" href="/paiement-inscription.php?formation=<?= $fid; ?>">
          Réserver ma place
          <small>
            <?php if ($prixAff > 0 && !empty($promoBtn['eligible'])): ?>
              <s style="opacity:.6;font-weight:500"><?= number_format($prixAff + PROMO_REMISE_PRESENTIEL, 0, ',', ' ') ?></s>
              <?= number_format($prixAff, 0, ',', ' ') ?> FCFA –
            <?php elseif ($prixAff > 0): ?>
              <?= number_format($prixAff, 0, ',', ' ') ?> FCFA –
            <?php endif; ?>
            SAMEDI PRO
          </small>
        </a>
        <?php endif; ?>

        <a class="pdf js-tdr" data-formation="<?= (int)$fid; ?>" data-slug="<?= h((string)($f['slug'] ?? '')); ?>" data-titre="<?= h((string)($f['titre'] ?? '')); ?>"
           href="/tdr-local-pdf.php?slug=<?= urlencode((string)($f['slug'] ?? '')); ?>">
          Télécharger le TDR
        </a>

      </div>
      <p class="cta-note" style="margin:12px 0 0;font-size:12.5px;color:#9fb0c9;display:flex;flex-wrap:wrap;gap:6px 18px">
        <?php if ($isFree): ?>
          <span>✅ Réservation 100% gratuite</span>
          <span>🔥 Places limitées — réservez vite</span>
          <span>💬 Une question ? Écrivez-nous</span>
        <?php else: ?>
          <span>🔒 Paiement 100% sécurisé</span>
          <span>🔥 Places limitées — réservez vite</span>
          <span>💬 Une question ? Écrivez-nous</span>
        <?php endif; ?>
      </p>
      <?php
      return;
    }

    /* ============================
       CAS FORMATION CLASSIQUE
    ============================ */
    $id   = (int)($f['id'] ?? 0);

    /* Montant de base : frais_inscription s'il existe, sinon 50 000 FCFA par défaut */
    $montantBase = (int)($f['frais_inscription'] ?? 0);
    if ($montantBase <= 0) { $montantBase = 50000; }

    /* Acompte minimum selon la formule d'échéances */
    $planCta = payment_plan($montantBase);
    $acompte = $planCta['acompte'];   // montant minimum dû à l'inscription

    /* Libellé selon le plan (if/else pour compat PHP 7.x) */
    if ($planCta['type'] === '2tranches') {
        $planLibelle = 'dès ' . number_format($acompte, 0, ',', ' ') . ' FCFA (50 %)';
    } elseif ($planCta['type'] === '3tranches') {
        $planLibelle = 'dès ' . number_format($acompte, 0, ',', ' ') . ' FCFA (40 %)';
    } else {
        $planLibelle = number_format($acompte, 0, ',', ' ') . ' FCFA';
    }
    ?>
    <div class="cta">

      <a class="pre"
       href="/preinscription.php?formation_id=<?= (int)$id; ?>"
       onclick="trackEvent('preinscription')">
       <i class="fa-solid fa-pen-to-square"></i>
       Se préinscrire gratuitement
      </a>

      <a class="pay"
       href="/paiement-inscription.php?formation=<?= (int)$id; ?>"
       onclick="trackEvent('inscription_intent')">
        <i class="fa-solid fa-credit-card"></i>
        Payer les frais d'inscription<br>
        <small><?= $planLibelle; ?></small>
      </a>

      <a class="pdf js-tdr" data-formation="<?= (int)$id; ?>" data-slug="<?= h((string)($f['slug'] ?? '')); ?>" data-titre="<?= h((string)($f['titre'] ?? '')); ?>" href="/tdr-local-pdf.php?slug=<?= urlencode((string)($f['slug'] ?? '')); ?>">
        <i class="fa-solid fa-file-pdf"></i>
        Télécharger le TDR
      </a>

    </div>
    <p class="cta-note" style="margin:12px 0 0;font-size:12.5px;color:#9fb0c9;display:flex;flex-wrap:wrap;gap:6px 18px">
      <span>✅ Préinscription gratuite &amp; sans engagement</span>
      <span>🔒 Paiement 100% sécurisé</span>
      <span>💬 Facilités de paiement possibles</span>
    </p>
    <?php
  }
}

/* =========================
   PARAMS (slug / id)
========================= */
$slug = isset($_GET['slug']) ? trim((string)$_GET['slug']) : '';
$id   = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($slug !== '' && ctype_digit($slug)) { // /formation/65 -> id
  $id = (int)$slug;
  $slug = '';
}

/* =========================
   LOAD FORMATION (avant HTML)
========================= */
$f = null;

if ($slug !== '') {
  $stmt = $pdo->prepare("SELECT * FROM formations WHERE slug = ? AND statut = 'active' LIMIT 1");
  $stmt->execute([$slug]);
  $f = $stmt->fetch(PDO::FETCH_ASSOC);
} elseif ($id > 0) {
  $stmt = $pdo->prepare("SELECT * FROM formations WHERE id = ? AND statut = 'active' LIMIT 1");
  $stmt->execute([$id]);
  $f = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
  http_response_code(404);
  ob_end_clean();
  exit('Formation introuvable');
}

if (!$f) {
  http_response_code(404);
  ob_end_clean();
  exit('Formation introuvable');
}

/* formation_passee() : ne plus rediriger — les fiches et TDR restent accessibles */

/* =========================
   INSCRIPTION (AUTO / ADMIN)
========================= */
$inscription = getInscriptionConfig($f);

if (empty($inscription) || !isset($inscription['montant'], $inscription['lien'])) {
  $inscription = [
    'montant' => (int)($f['frais_inscription'] ?? 0),
    'lien'    => (string)($f['paiement_lien'] ?? '#'),
    'source'  => 'fallback'
  ];
}

/* =========================
   CANONICAL SEO (AVANT HTML)
========================= */
if (!empty($f['slug'])) {
  if ($id > 0 || (isset($_GET['id']) && (int)$_GET['id'] > 0)) {
    header('Location: /formation/' . $f['slug'], true, 301);
    ob_end_flush();
    exit;
  }
  if ($slug !== '' && $slug !== $f['slug']) {
    header('Location: /formation/' . $f['slug'], true, 301);
    ob_end_flush();
    exit;
  }
}

/* =========================
   LANDING
========================= */
$stmt = $pdo->prepare("SELECT * FROM formation_landings WHERE formation_id = ? LIMIT 1");
$stmt->execute([(int)$f['id']]);
$lp = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

/* =========================
   SESSIONS
========================= */
$stmt = $pdo->prepare("
  SELECT date_debut, date_fin, duree, mode, statut
  FROM calendrier_formations
  WHERE formation_id = ?
    AND statut IN ('ouvert','a_venir')
  ORDER BY date_debut ASC
");
$stmt->execute([(int)$f['id']]);
$sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$useLanding = hasLanding($lp);

/* =========================
   NIVEAUX
========================= */
$stmt = $pdo->prepare("
  SELECT n.niveau, n.objectifs, n.prerequis, n.public_cible, n.duree_heures,
         n.tarif_en_ligne, n.tarif_presentiel
  FROM formation_niveaux n
  WHERE n.formation_id = ? AND n.statut = 'actif'
  ORDER BY CASE n.niveau WHEN 'debutant' THEN 1 WHEN 'intermediaire' THEN 2 WHEN 'expert' THEN 3 END
");
$stmt->execute([(int)$f['id']]);
$niveaux_detail = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   SESSION DE RÉFÉRENCE (SAMEDI PRO)
========================= */
$referenceSession = null;
if (!empty($f['is_samedi_pro']) && !empty($sessions)) {
  $referenceSession = $sessions[0]; // la plus proche (ORDER BY ASC)
}

/* =========================
   OPEN GRAPH (PARTAGE SOCIAL)
========================= */
$ogTitle = (string)($f['titre'] ?? 'Formation professionnelle – IBIG EDUFORM');

$ogDesc = '';
if (!empty($lp['pitch_marketing'])) {
  $ogDesc = strip_tags((string)$lp['pitch_marketing']);
} elseif (!empty($f['description'])) {
  $ogDesc = strip_tags((string)$f['description']);
}
$ogDesc = mb_substr($ogDesc, 0, 160);

$host = (string)($_SERVER['HTTP_HOST'] ?? 'ibig-eduform.com');
if (!empty($lp['hero_image'])) {
  $ogImage = 'https://' . $host . '/' . ltrim((string)$lp['hero_image'], '/');
} else {
  $ogImage = 'https://' . $host . '/assets/images/logo.png';
}
$ogUrl = 'https://' . $host . '/formation/' . (string)($f['slug'] ?? '');

$pageTitle = (string)($f['titre'] ?? 'Formation') . ' – IBIG EDUFORM';
$isSamediPro = !empty($f['is_samedi_pro']);

$bodyClass = $isSamediPro ? 'samedi-pro' : 'formation-standard';

/* =========================
   HTML START
========================= */
$pageBodyClass = $bodyClass;
$extraHead  = '<link rel="canonical" href="' . htmlspecialchars($ogUrl, ENT_QUOTES, 'UTF-8') . '">' . "\n";
$extraHead .= '<link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>' . "\n";
$extraHead .= '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">';
require __DIR__ . '/partials/header.php';
?>
<style>
  body{background:#020617;color:#e5e7eb;font-family:Inter,system-ui,sans-serif}
  .wrap{max-width:1100px;margin:70px auto;padding:0 20px;overflow-x:clip}
  .conv,.conv *{min-width:0}
  .conv-card{overflow:hidden}
  .hero{background:linear-gradient(160deg,#0b3c5d,#020617);border-radius:22px;padding:40px;box-shadow:0 20px 50px rgba(0,0,0,.45);overflow:hidden;position:relative}
  .hero-grid{display:grid;grid-template-columns:1fr 1fr;gap:28px;align-items:start}
  .badge{display:inline-block;background:#f5a623;color:#000;padding:6px 14px;border-radius:999px;font-weight:800;font-size:.8rem}
  h1{font-size:2.4rem;margin:14px 0 10px}
  .meta{opacity:.9;margin-bottom:18px}
  .hero-media{display:flex;flex-direction:column;gap:16px}
  .hero-img{width:100%;border-radius:18px;border:1px solid rgba(255,255,255,.12);object-fit:contain;max-height:600px;background:#0a0f1e}
  /* Vidéo de présentation */
  .video-box{position:relative;width:100%;border-radius:18px;overflow:hidden;border:1px solid rgba(255,255,255,.12);background:#0a0f1e;aspect-ratio:16/9}
  .video-box iframe,.video-box video{position:absolute;inset:0;width:100%;height:100%;border:0;display:block}
  .grid{display:grid;grid-template-columns:2fr 1fr;gap:28px;margin-top:30px}
  .box{background:rgba(255,255,255,.06);border-radius:18px;padding:26px;border:1px solid rgba(255,255,255,.06)}
  .price{font-weight:800;line-height:1.6}
  .hr{height:1px;background:rgba(255,255,255,.10);margin:26px 0}
  .section-title{font-size:1.65rem;margin:0 0 14px;color:#f5a623}
  .kv{display:grid;grid-template-columns:1fr;gap:14px}
  .kv .item{background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.07);border-radius:16px;padding:18px}
  .gallery{margin-top:22px;display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:14px}
  .gallery img{width:100%;border-radius:16px;border:1px solid rgba(255,255,255,.12);object-fit:contain;max-height:460px;height:auto;background:#0a0f1e}
  .sessions{margin-top:70px}
  .sessions-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:24px}
  .session-card{background:rgba(255,255,255,.06);border-radius:18px;padding:22px;border:1px solid rgba(255,255,255,.06)}
  .session-badge{display:inline-block;padding:5px 12px;border-radius:999px;font-size:.75rem;font-weight:900;color:#000}
  .session-badge.ouvert{background:#22c55e}
  .session-badge.a_venir{background:#38bdf8}
  @media(max-width:900px){.grid{grid-template-columns:1fr}.hero-grid{grid-template-columns:1fr}.gallery{grid-template-columns:1fr}.gallery img{height:auto}}

  .cta{display:grid;grid-template-columns:1fr;gap:12px;margin-top:18px}
  .cta a{display:flex;flex-direction:column;justify-content:center;align-items:center;text-align:center;height:88px;border-radius:12px;font-weight:900;text-decoration:none;line-height:1.2;padding:10px;transition:transform .15s ease,box-shadow .15s ease,opacity .15s ease}
  .cta a i{font-size:1.3rem;margin-bottom:6px}
  .cta a small{font-size:.75rem;font-weight:700;opacity:.9;margin-top:4px}
  .cta a.pay{background:#22c55e;color:#000}
  .cta a.pre{background:#f5a623;color:#000}
  .cta a.pdf{background:transparent;border:2px solid #38bdf8;color:#38bdf8}
  .cta a:hover{transform:translateY(-2px);box-shadow:0 10px 25px rgba(0,0,0,.35);opacity:.95}
  @media(min-width:900px){.cta{grid-template-columns:1fr 1fr}.cta a.pdf{grid-column:1/-1}}

  .module-title{margin:18px 0 8px;font-weight:900;color:#f5a623}
  .module-title::after{content:"";display:block;width:36px;height:3px;background:#f5a623;margin-top:6px;border-radius:2px}
  .module-list{padding-left:22px;margin-bottom:18px}
  .module-list li{margin-bottom:6px}

  /* TITRES & SOUS-TITRES — UNIFICATION */
  .section-title,
  .box h3,
  .kv .item h3,
  aside.box h3 {
    color: #f5a623;
    font-weight: 900;
    font-size: 1.25rem;
    margin-bottom: 14px;
    position: relative;
    letter-spacing: .02em;
  }
  .section-title { font-size: 1.8rem; }
  .section-title::after,
  .box h3::after,
  .kv .item h3::after,
  aside.box h3::after {
    content: "";
    display: block;
    width: 46px;
    height: 3px;
    margin-top: 6px;
    background: linear-gradient(90deg,#f5a623,#ffd27d);
    border-radius: 3px;
  }
  .kv .item,
  .box,
  aside.box { padding: 24px; margin-bottom: 20px; }
  .kv .item p,
  .box p,
  aside.box p { color: #e5e7eb; line-height: 1.75; font-size: .95rem; }
  .section-title i,
  .box h3 i,
  .kv .item h3 i,
  aside.box h3 i { color: #ffd27d; margin-right: 6px; }
  .kv .item:hover,
  .box:hover,
  aside.box:hover { background: rgba(255,255,255,.08); transition: background .25s ease; }

  /* PROGRAMME PREMIUM */
  .programme-premium{
    background:#0f172a;
    border-radius:18px;
    padding:32px 28px;
    margin-top:30px;
    box-shadow:0 25px 60px rgba(0,0,0,.55);
  }
  .programme-title{
    font-size:1.6rem;
    font-weight:900;
    color:#f59e0b;
    margin-bottom:28px;
    position:relative;
  }
  .programme-title::after{
    content:"";
    display:block;
    width:44px;
    height:3px;
    background:#f59e0b;
    margin-top:10px;
    border-radius:2px;
  }
  .programme-premium .module-title{
    display:block;
    font-size:1.05rem;
    font-weight:900;
    color:#f59e0b !important;
    margin:26px 0 14px;
    text-transform:uppercase;
    letter-spacing:.05em;
  }
  .programme-premium .module-list{
    list-style:none;
    padding-left:0;
    margin:0 0 22px;
  }
  .programme-premium .module-list li{
    color:#e5e7eb;
    font-size:.95rem;
    line-height:1.7;
    padding-left:22px;
    margin-bottom:8px;
    position:relative;
  }
  .programme-premium .module-list li::before{
    content:"•";
    position:absolute;
    left:0;
    color:#f59e0b;
    font-size:1.2rem;
    line-height:1;
  }
  @media(max-width:768px){
    .programme-premium{padding:24px 20px;}
    .programme-title{font-size:1.4rem;}
  }

  /* PARTAGE SOCIAL */
  .share-box{
    margin-top:40px;
    padding:24px;
    border-radius:16px;
    background:rgba(255,255,255,.06);
    border:1px solid rgba(255,255,255,.08);
  }
  .share-title{
    font-weight:900;
    color:#f5a623;
    margin-bottom:14px;
    font-size:1.1rem;
  }
  .share-buttons{
    display:flex;
    flex-wrap:wrap;
    gap:12px;
  }
  .share-buttons a,
  .share-buttons button{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:10px 14px;
    border-radius:999px;
    font-weight:800;
    font-size:.85rem;
    text-decoration:none;
    border:none;
    cursor:pointer;
    transition:transform .15s ease, box-shadow .15s ease, opacity .15s ease;
  }
  .share-buttons a:hover,
  .share-buttons button:hover{
    transform:translateY(-2px);
    box-shadow:0 10px 25px rgba(0,0,0,.35);
    opacity:.95;
  }
  .share-fb{background:#1877f2;color:#fff}
  .share-wa{background:#22c55e;color:#000}
  .share-ln{background:#0a66c2;color:#fff}
  .share-x{background:#000;color:#fff}
  .share-copy{background:#334155;color:#fff}

  /* SAMEDI PRO — CTA sur 1 ligne */
  .samedi-pro-cta{ display:flex; gap:14px; align-items:stretch; }
  .samedi-pro-cta a{
    flex:1;
    height:68px;
    display:flex;
    flex-direction:column;
    justify-content:center;
    align-items:center;
    text-align:center;
    border-radius:12px;
    font-weight:900;
    line-height:1.2;
    padding:10px 12px;
  }
  .samedi-pro-cta a.pay{ background:#22c55e; color:#000; }
  .samedi-pro-cta a.pdf{ background:transparent; border:2px solid #38bdf8; color:#38bdf8; }
  .samedi-pro-cta a small{ font-size:.75rem; font-weight:700; opacity:.9; margin-top:4px; }
  @media (max-width:520px){ .samedi-pro-cta{ flex-direction:column; } }

  /* DIFFÉRENCIATION */
  body.formation-standard .hero { background: linear-gradient(160deg, #0b3c5d, #020617); }
  body.formation-standard .badge { background:#f5a623; color:#000; }

  body.samedi-pro .hero{
    background: linear-gradient(160deg, #0f3d2e, #020617);
    box-shadow: 0 0 0 2px rgba(34,197,94,.25), 0 20px 50px rgba(0,0,0,.45);
  }
  body.samedi-pro .badge { background:#22c55e; color:#022c22; }

  body.formation-standard .cta a.pay{
    background: linear-gradient(135deg, #ff2e2e, #e60000);
    color:#fff;
  }
  body.samedi-pro .samedi-pro-cta a.pay{
    background: linear-gradient(135deg, #22c55e, #16a34a);
    color:#022c22;
  }
  body.samedi-pro .samedi-pro-cta a.pdf{ border-color:#22c55e; color:#22c55e; }

  body.samedi-pro .hero::after{
    content:"FORMATION SAMEDI PRO";
    position:absolute;
    top:18px;
    right:-40px;
    background:#22c55e;
    color:#022c22;
    padding:6px 60px;
    font-size:.75rem;
    font-weight:900;
    transform:rotate(8deg);
    box-shadow:0 6px 20px rgba(34,197,94,.4);
  }
  </style>

<main class="wrap">

<nav aria-label="Fil d'Ariane" style="margin-bottom:18px;font-size:13px;color:#9fb0c9">
  <a href="/" style="color:#9fb0c9;text-decoration:none">Accueil</a>
  <span style="margin:0 6px">›</span>
  <a href="/catalogue-formations.php" style="color:#9fb0c9;text-decoration:none">Catalogue</a>
  <?php if (!empty($f['domaine'])): ?>
  <span style="margin:0 6px">›</span>
  <a href="/catalogue-formations.php?cat=<?= urlencode((string)$f['domaine']); ?>" style="color:#9fb0c9;text-decoration:none"><?= h($f['domaine']); ?></a>
  <?php endif; ?>
  <span style="margin:0 6px">›</span>
  <span style="color:#e5e7eb"><?= h((string)($f['titre'] ?? '')); ?></span>
</nav>

<?php if ($useLanding): ?>

  <section class="hero">
    <div class="hero-grid">
      <div>
        <span class="badge"><?= h($f['type_certificat'] ?? ''); ?></span>

        <h1><?= h(!empty($lp['hero_title']) ? $lp['hero_title'] : ($f['titre'] ?? '')); ?></h1>

        <div class="meta">
          <i class="fa-solid fa-folder-open"></i> Domaine : <?= h($f['domaine'] ?? ''); ?>
          &nbsp;&bull;&nbsp;
          <i class="fa-solid fa-clock"></i> Durée : <?= h($f['duree'] ?? ''); ?>
        </div>

        <?php
          $heroDate = null;
          if (!empty($f['is_samedi_pro']) && !empty($referenceSession['date_debut'])) {
            $heroDate = $referenceSession['date_debut']; // SAMEDI PRO
          } elseif (!empty($f['date_debut'])) {
            $heroDate = $f['date_debut']; // standard
          }
        ?>

        <?php if ($heroDate): ?>
          <div class="meta" style="margin-top:8px">
            <i class="fa-solid fa-calendar-days"></i>
            Démarrage :
            <strong><?= h(date('d/m/Y', strtotime((string)$heroDate))); ?></strong>
          </div>
        <?php endif; ?>

        <br>
        <small>
          <?= h($f['mois'] ?? ''); ?> <?= h((string)($f['annee'] ?? '')); ?>
          <?php if (!empty($f['session_label'])): ?> — <?= h($f['session_label']); ?><?php endif; ?>
        </small>

        <?php if (!empty($lp['hero_subtitle'])): ?>
          <p style="opacity:.9;font-weight:700;margin-top:8px;"><?= nl($lp['hero_subtitle']); ?></p>
        <?php endif; ?>

        <?php if (!empty($lp['promesse'])): ?>
          <p style="margin-top:10px;"><?= nl($lp['promesse']); ?></p>
        <?php endif; ?>

        <?php include __DIR__ . '/partials/formation_urgency.php'; ?>

        <?php formation_cta($f, $inscription); ?>
      </div>

      <div class="hero-media">
        <?php $heroImg = (string)($lp['hero_image'] ?? ''); ?>
        <?php if ($heroImg !== ''):
          $heroWebp   = preg_replace('/\.(png|jpe?g)$/i', '.webp', $heroImg);
          $heroWebpOk = ($heroWebp !== $heroImg) && is_file(__DIR__ . '/' . ltrim($heroWebp, '/'));
        ?>
          <picture>
            <?php if ($heroWebpOk): ?><source srcset="/<?= h($heroWebp); ?>" type="image/webp"><?php endif; ?>
            <img class="hero-img" src="/<?= h($heroImg); ?>" alt="<?= h($f['titre'] ?? ''); ?>" loading="eager">
          </picture>
        <?php else: ?>
          <picture>
            <source srcset="/assets/images/hero/hero-1.webp" type="image/webp">
            <img class="hero-img" src="/assets/images/hero/hero-1.jpg" alt="<?= h($f['titre'] ?? ''); ?>">
          </picture>
        <?php endif; ?>
        <?php if (!empty($lp['video_url'])): ?>
          <?= video_embed_html((string)$lp['video_url']); ?>
        <?php endif; ?>
      </div>
    </div>

    <?php
      $g1 = (string)($lp['image_1'] ?? '');
      $g2 = (string)($lp['image_2'] ?? '');
      $g3 = (string)($lp['image_3'] ?? '');
      $hasGallery = ($g1 !== '' || $g2 !== '' || $g3 !== '');
    ?>
    <?php if ($hasGallery): ?>
      <div class="gallery">
        <?php if ($g1 !== ''): ?><img src="/<?= h($g1); ?>" alt="Image" loading="lazy" decoding="async"><?php endif; ?>
        <?php if ($g2 !== ''): ?><img src="/<?= h($g2); ?>" alt="Image" loading="lazy" decoding="async"><?php endif; ?>
        <?php if ($g3 !== ''): ?><img src="/<?= h($g3); ?>" alt="Image" loading="lazy" decoding="async"><?php endif; ?>
      </div>
    <?php endif; ?>
  </section>

  <section class="grid">
    <div class="box">
      <h2 class="section-title"><i class="fa-solid fa-circle-info"></i> Présentation</h2>

      <?php if (!empty($lp['pitch_marketing'])): ?>
        <p><?= nl($lp['pitch_marketing']); ?></p>
      <?php elseif (!empty($lp['pitch'])): ?>
        <p><?= nl($lp['pitch']); ?></p>
      <?php else: ?>
        <p><?= nl($f['description'] ?? ''); ?></p>
      <?php endif; ?>

      <div class="hr"></div>

      <div class="kv">
        <?php if (!empty($lp['contexte'])): ?>
          <div class="item">
            <h3 style="margin:0 0 8px;"><i class="fa-solid fa-lightbulb"></i> Contexte & justification</h3>
            <p><?= nl($lp['contexte']); ?></p>
          </div>
        <?php endif; ?>

        <?php if (!empty($lp['objectif_general'])): ?>
          <div class="item">
            <h3 style="margin:0 0 8px;"><i class="fa-solid fa-bullseye"></i> Objectif général</h3>
            <p><?= nl($lp['objectif_general']); ?></p>
          </div>
        <?php endif; ?>

        <?php if (!empty($lp['objectifs_specifiques'])): ?>
          <div class="item">
            <h3 style="margin:0 0 8px;"><i class="fa-solid fa-list-check"></i> Objectifs spécifiques</h3>
            <p><?= nl($lp['objectifs_specifiques']); ?></p>
          </div>
        <?php endif; ?>

        <?php if (!empty($lp['resultats_attendus'])): ?>
          <div class="item">
            <h3 style="margin:0 0 8px;"><i class="fa-solid fa-trophy"></i> Résultats attendus</h3>
            <p><?= nl($lp['resultats_attendus']); ?></p>
          </div>
        <?php endif; ?>

        <?php if (!empty($lp['public_cible'])): ?>
          <div class="item">
            <h3 style="margin:0 0 8px;"><i class="fa-solid fa-users"></i> Public cible</h3>
            <p><?= nl($lp['public_cible']); ?></p>
          </div>
        <?php endif; ?>

        <?php if (!empty($lp['prerequis'])): ?>
          <div class="item">
            <h3 style="margin:0 0 8px;"><i class="fa-solid fa-screwdriver-wrench"></i> Prérequis</h3>
            <p><?= nl($lp['prerequis']); ?></p>
          </div>
        <?php endif; ?>

        <div class="item">
          <section class="programme-premium">
            <h2 class="programme-title">
              <i class="fa-solid fa-book"></i> Programme
            </h2>
            <div class="programme-content">
              <?= afficher_modules_depuis_texte_plat((string)($lp['contenu_programme'] ?? '')); ?>
            </div>
          </section>
        </div>

        <?php if (!empty($lp['methodologie'])): ?>
          <div class="item">
            <h3 style="margin:0 0 8px;"><i class="fa-solid fa-graduation-cap"></i> Méthodologie pédagogique</h3>
            <p><?= nl($lp['methodologie']); ?></p>
          </div>
        <?php endif; ?>

        <?php if (!empty($lp['avantages'])): ?>
          <div class="item">
            <h3 style="margin:0 0 8px;"><i class="fa-solid fa-star"></i> Avantages</h3>
            <p><?= nl($lp['avantages']); ?></p>
          </div>
        <?php endif; ?>

        <?php $debParts = array_values(array_filter(array_map('trim', explode(';', (string)($f['modules'] ?? ''))))); ?>
        <?php if ($debParts): ?>
          <div class="item">
            <h3 style="margin:0 0 8px;"><i class="fa-solid fa-briefcase"></i> Débouchés professionnels</h3>
            <p><?= h(implode(' · ', $debParts)); ?></p>
          </div>
        <?php endif; ?>

        <?php if (!empty($lp['formateurs'])): ?>
          <div class="item">
            <h3 style="margin:0 0 8px;"><i class="fa-solid fa-chalkboard-user"></i> Formateurs &amp; intervenants</h3>
            <p><?= nl($lp['formateurs']); ?></p>
          </div>
        <?php endif; ?>

        <?php if (!empty($lp['evaluation'])): ?>
          <div class="item">
            <h3 style="margin:0 0 8px;"><i class="fa-solid fa-clipboard-check"></i> Évaluation &amp; certification</h3>
            <p><?= nl($lp['evaluation']); ?></p>
          </div>
        <?php endif; ?>

        <?php if (!empty($lp['modalites_participation'])): ?>
          <?php
            /* Remplacer les tarifs hardcodés par les vrais tarifs DB */
            $_moda = (string)$lp['modalites_participation'];
            $_tl   = (int)($f['tarif_en_ligne'] ?? 0);
            $_tp   = (int)($f['tarif_presentiel'] ?? 0);
            if ($_tl > 0 || $_tp > 0) {
                $_moda = preg_replace_callback(
                    '/Tarif en ligne\s*:\s*[\d\s]+FCFA\s*·\s*Présentiel\s*:\s*[\d\s]+FCFA/u',
                    function() use ($_tl, $_tp) {
                        $parts = [];
                        if ($_tl > 0) $parts[] = 'Tarif en ligne : ' . number_format($_tl, 0, ',', ' ') . ' FCFA';
                        if ($_tp > 0) $parts[] = 'Présentiel : '     . number_format($_tp, 0, ',', ' ') . ' FCFA';
                        return implode(' · ', $parts);
                    },
                    $_moda
                );
            }
          ?>
          <div class="item">
            <h3 style="margin:0 0 8px;"><i class="fa-solid fa-circle-check"></i> Modalités de participation</h3>
            <p><?= nl($_moda); ?></p>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <?php if (!$isSamediPro): ?>
      <aside class="box">
        <h3><i class="fa-solid fa-money-bill-wave"></i> Tarifs (officiels)</h3>
        <?php
          $promoL  = promo_earlybird($f);
          $tl      = (int)($f['tarif_en_ligne'] ?? 0);
          $tp      = (int)($f['tarif_presentiel'] ?? 0);
          $ref     = (int)($f['tarif_hybride'] ?? 0);
          $uniform = ($tl > 0 && $tl === $tp);
        ?>
        <div class="price">
          <?php if ($tl <= 0 && $tp <= 0): ?>
            <i class="fa-solid fa-circle-info"></i> <em>Tarif sur demande</em>
          <?php elseif ($uniform): ?>
            <i class="fa-solid fa-layer-group"></i> Pack complet :
            <?php if ($ref > $tl): ?><span style="text-decoration:line-through;opacity:.6"><?= number_format($ref,0,',',' '); ?></span> <?php endif; ?>
            <?php if (!empty($promoL['eligible'])): ?>
              <span style="text-decoration:line-through;opacity:.6"><?= number_format($tl + PROMO_REMISE_EN_LIGNE,0,',',' '); ?></span>
              <b style="color:#22c55e"><?= number_format($tl,0,',',' '); ?> FCFA</b>
            <?php else: ?>
              <b><?= number_format($tl,0,',',' '); ?> FCFA</b>
            <?php endif; ?>
          <?php else: ?>
            <?php if ($tl > 0): ?>
            <i class="fa-solid fa-laptop"></i> En ligne :
            <?php if (!empty($promoL['eligible'])): ?>
              <span style="text-decoration:line-through;opacity:.6"><?= number_format($tl + PROMO_REMISE_EN_LIGNE,0,',',' '); ?></span>
              <b style="color:#22c55e"><?= number_format($tl,0,',',' '); ?> FCFA</b><br>
            <?php else: ?>
              <?= number_format($tl,0,',',' '); ?> FCFA<br>
            <?php endif; ?>
            <?php endif; ?>
            <?php if ($tp > 0): ?>
            <i class="fa-solid fa-building"></i> Présentiel :
            <?php if (!empty($promoL['eligible'])): ?>
              <span style="text-decoration:line-through;opacity:.6"><?= number_format($tp + PROMO_REMISE_PRESENTIEL,0,',',' '); ?></span>
              <b style="color:#22c55e"><?= number_format($tp,0,',',' '); ?> FCFA</b>
            <?php else: ?>
              <?= number_format($tp,0,',',' '); ?> FCFA
            <?php endif; ?>
            <?php endif; ?>
          <?php endif; ?>
        </div>
        <?php if (!empty($promoL['eligible'])): ?>
          <div style="margin-top:10px;background:rgba(245,158,11,.15);border:1px solid rgba(245,158,11,.4);border-radius:10px;padding:10px 12px;font-size:12.5px;color:#fbbf24;font-weight:700">&#x1F3F7;&#xFE0F; R&eacute;duction &minus;20&nbsp;000 FCFA (en ligne) &bull; &minus;25&nbsp;000 FCFA (pr&eacute;sentiel) &mdash; jusqu&rsquo;au <?= h(date('d/m/Y',(int)$promoL['deadline'])); ?></div>
        <?php endif; ?>
        <?php formation_cta($f, $inscription); ?>
      </aside>
    <?php else: ?>
      <aside class="box">
        <h3><i class="fa-solid fa-money-bill-wave"></i> Tarifs (Samedi Pro)</h3>
        <?php $promoS = promo_earlybird($f); $sl=(int)($f['tarif_en_ligne']??0); $sp=(int)($f['tarif_presentiel']??0); $isFreeS = ($sl<=0 && $sp<=0); ?>
        <?php if ($isFreeS): ?>
          <?php $refS = (int)($f['tarif_hybride'] ?? 0); ?>
          <div class="price" style="text-align:center">
            <?php if ($refS > 0): ?>
              <span style="text-decoration:line-through;opacity:.55;font-size:1.05rem"><?= number_format($refS,0,',',' '); ?> FCFA</span><br>
            <?php endif; ?>
            <span style="display:inline-block;font-size:1.7rem;font-weight:900;color:#22c55e;letter-spacing:.5px">GRATUIT</span><br>
            <span style="font-size:13px;opacity:.9">Formation offerte par IBIG EDUFORM</span>
          </div>
        <?php else: ?>
        <div class="price">
          <i class="fa-solid fa-building"></i> Présentiel :
          <?php if (!empty($promoS['eligible'])): ?>
            <span style="text-decoration:line-through;opacity:.6"><?= number_format($sp + PROMO_REMISE_PRESENTIEL,0,',',' '); ?></span>
            <b style="color:#22c55e"><?= number_format($sp,0,',',' '); ?> FCFA</b>
          <?php else: ?>
            <?= number_format($sp,0,',',' '); ?> FCFA
          <?php endif; ?>
        </div>
        <?php if (!empty($promoS['eligible'])): ?>
          <div style="margin-top:10px;background:rgba(245,158,11,.15);border:1px solid rgba(245,158,11,.4);border-radius:10px;padding:10px 12px;font-size:12.5px;color:#fbbf24;font-weight:700">&#x1F3F7;&#xFE0F; R&eacute;duction &minus;25&nbsp;000 FCFA &mdash; jusqu&rsquo;au <?= h(date('d/m/Y',(int)$promoS['deadline'])); ?></div>
        <?php endif; ?>
        <?php endif; ?>
        <p style="margin-top:10px;font-size:13px;opacity:.9"><i class="fa-solid fa-circle-check"></i> Disponible <strong>en présentiel (Abidjan) ou en ligne</strong>, le samedi. Réservation obligatoire.</p>
        <?php formation_cta($f, $inscription); ?>
      </aside>
    <?php endif; ?>
  </section>

  <?php include __DIR__ . '/partials/formation_conversion.php'; ?>

<?php else: ?>

  <section class="hero">
    <span class="badge"><?= h($f['type_certificat'] ?? ''); ?></span>
    <h1><?= h($f['titre'] ?? ''); ?></h1>

    <div class="meta">
      <i class="fa-solid fa-folder-open"></i> Domaine : <?= h($f['domaine'] ?? ''); ?>
      &nbsp;&bull;&nbsp;
      <i class="fa-solid fa-clock"></i> Durée : <?= h($f['duree'] ?? ''); ?>
    </div>

    <?php if (!empty($f['date_debut'])): ?>
      <div class="meta" style="margin-top:8px">
        <i class="fa-solid fa-calendar-days"></i>
        Démarrage :
        <strong><?= h(date('d/m/Y', strtotime((string)$f['date_debut']))); ?></strong>
        <?php if (!empty($f['date_fin'])): ?>
          &nbsp;→&nbsp; <?= h(date('d/m/Y', strtotime((string)$f['date_fin']))); ?>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <?php
      /* ===== URGENCE & RARETÉ ===== */
      $dDebutTs   = !empty($f['date_debut']) ? strtotime((string)$f['date_debut'] . ' 09:00:00') : 0;
      $joursAvant = $dDebutTs ? (int)floor(($dDebutTs - time()) / 86400) : null;
      $placesLeft = function_exists('places_disponibles') ? places_disponibles($f) : null;
    ?>
    <?php if ($dDebutTs && $joursAvant !== null && $joursAvant >= 0): ?>
    <style>
      .urgency{display:flex;flex-wrap:wrap;gap:10px;margin:16px 0 6px}
      .u-pill{display:inline-flex;align-items:center;gap:8px;padding:9px 15px;border-radius:999px;font-size:13px;font-weight:700;line-height:1}
      .u-pill.live{background:rgba(16,185,129,.15);color:#34d399;border:1px solid rgba(16,185,129,.45)}
      .u-pill.live .dot{width:9px;height:9px;border-radius:50%;background:#34d399;animation:uPulse 1.6s infinite}
      @keyframes uPulse{0%{box-shadow:0 0 0 0 rgba(52,211,153,.6)}70%{box-shadow:0 0 0 9px rgba(52,211,153,0)}100%{box-shadow:0 0 0 0 rgba(52,211,153,0)}}
      .u-pill.count{background:rgba(245,158,11,.15);color:#fbbf24;border:1px solid rgba(245,158,11,.45)}
      .u-pill.count b{color:#fff;font-variant-numeric:tabular-nums;letter-spacing:.3px}
      .u-pill.places{background:rgba(232,36,44,.14);color:#fb7185;border:1px solid rgba(232,36,44,.45)}
    </style>
    <div class="urgency">
      <span class="u-pill live"><span class="dot"></span> Inscriptions ouvertes</span>
      <span class="u-pill count" data-deadline="<?= $dDebutTs * 1000; ?>">
        <i class="fa-regular fa-clock"></i> Démarre dans&nbsp;<b id="cd-formation">…</b>
      </span>
      <span class="u-pill places">
        <i class="fa-solid fa-fire"></i>
        <?= $placesLeft !== null ? ('Plus que ' . $placesLeft . ' place' . ($placesLeft > 1 ? 's' : '')) : 'Places limitées'; ?>
      </span>
    </div>
    <script>
      (function(){
        var el=document.getElementById('cd-formation'); if(!el) return;
        var box=el.parentElement, deadline=parseInt(box.getAttribute('data-deadline'),10);
        function p(n){return ('0'+n).slice(-2);}
        function tick(){
          var diff=deadline-Date.now();
          if(diff<=0){ el.textContent="aujourd'hui — dernières places !"; return; }
          var d=Math.floor(diff/86400000),h=Math.floor(diff/3600000)%24,m=Math.floor(diff/60000)%60,s=Math.floor(diff/1000)%60;
          el.textContent=(d>0?d+'j ':'')+p(h)+'h '+p(m)+'m '+p(s)+'s';
        }
        tick(); setInterval(tick,1000);
      })();
    </script>
    <?php endif; ?>

    <p><?= nl($f['description'] ?? ''); ?></p>

    <?php formation_cta($f, $inscription); ?>
  </section>

  <?php if (!empty($niveaux_detail)):
    $niv_labels = ['debutant'=>'Débutant','intermediaire'=>'Intermédiaire','expert'=>'Expert'];
    $niv_colors = ['debutant'=>'#166534','intermediaire'=>'#1e40af','expert'=>'#9d174d'];
    $niv_bg     = ['debutant'=>'#dcfce7','intermediaire'=>'#dbeafe','expert'=>'#fce7f3'];
    $niv_border = ['debutant'=>'#86efac','intermediaire'=>'#93c5fd','expert'=>'#f9a8d4'];
    $nd_list    = array_values($niveaux_detail);
    $first_niv  = $nd_list[0]['niveau'] ?? '';
  ?>
  <section style="margin:28px 0 0">
    <h2 style="font-size:17px;font-weight:800;margin:0 0 14px;display:flex;align-items:center;gap:8px">
      <i class="fa-solid fa-layer-group"></i> Contenu par niveau
    </h2>
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px">
      <?php foreach ($nd_list as $nd): ?>
      <button type="button"
        onclick="fnPickNiv(this,'<?= htmlspecialchars($nd['niveau'],ENT_QUOTES) ?>')"
        class="fn-pill<?= $nd['niveau']===$first_niv?' fn-pill-on':'' ?>"
        data-niv="<?= htmlspecialchars($nd['niveau'],ENT_QUOTES) ?>"
        style="padding:7px 20px;border-radius:999px;font-size:.82rem;font-weight:700;cursor:pointer;border:1.5px solid <?= $niv_border[$nd['niveau']]??'#e2e8f0' ?>;background:<?= $nd['niveau']===$first_niv?($niv_bg[$nd['niveau']]??'#f1f5f9'):'#fff' ?>;color:<?= $nd['niveau']===$first_niv?($niv_colors[$nd['niveau']]??'#475569'):'#475569' ?>">
        <?= $niv_labels[$nd['niveau']] ?? ucfirst($nd['niveau']) ?>
      </button>
      <?php endforeach; ?>
    </div>
    <?php foreach ($nd_list as $nd): ?>
    <div id="fn-block-<?= htmlspecialchars($nd['niveau'],ENT_QUOTES) ?>"
         style="display:<?= $nd['niveau']===$first_niv?'block':'none' ?>;background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:20px 24px">

      <?php /* Durée + tarifs du niveau */ ?>
      <div style="display:flex;gap:16px;flex-wrap:wrap;margin-bottom:<?= (!empty($nd['objectifs'])||!empty($nd['prerequis'])||!empty($nd['public_cible']))?'16px':'0' ?>;padding-bottom:<?= (!empty($nd['objectifs'])||!empty($nd['prerequis'])||!empty($nd['public_cible']))?'16px':'0' ?>;border-bottom:<?= (!empty($nd['objectifs'])||!empty($nd['prerequis'])||!empty($nd['public_cible']))?'1px solid #e2e8f0':'none' ?>">
        <?php if (!empty($nd['duree_heures'])): ?>
        <span style="display:inline-flex;align-items:center;gap:6px;font-size:.82rem;font-weight:700;color:#475569">
          <i class="fa-regular fa-clock" style="color:<?= $niv_colors[$nd['niveau']]??'#475569' ?>"></i>
          <?= (int)$nd['duree_heures'] ?>h de formation
        </span>
        <?php endif; ?>
        <?php if (!empty($nd['tarif_en_ligne']) && (int)$nd['tarif_en_ligne'] > 0): ?>
        <span style="display:inline-flex;align-items:center;gap:6px;font-size:.82rem;font-weight:700;color:#475569">
          <i class="fa-solid fa-laptop" style="color:<?= $niv_colors[$nd['niveau']]??'#475569' ?>"></i>
          En ligne : <b style="color:<?= $niv_colors[$nd['niveau']]??'#1e293b' ?>"><?= number_format((int)$nd['tarif_en_ligne'],0,',',' ') ?> FCFA</b>
        </span>
        <?php endif; ?>
        <?php if (!empty($nd['tarif_presentiel']) && (int)$nd['tarif_presentiel'] > 0): ?>
        <span style="display:inline-flex;align-items:center;gap:6px;font-size:.82rem;font-weight:700;color:#475569">
          <i class="fa-solid fa-building" style="color:<?= $niv_colors[$nd['niveau']]??'#475569' ?>"></i>
          Présentiel : <b style="color:<?= $niv_colors[$nd['niveau']]??'#1e293b' ?>"><?= number_format((int)$nd['tarif_presentiel'],0,',',' ') ?> FCFA</b>
        </span>
        <?php endif; ?>
      </div>

      <?php if (!empty($nd['objectifs'])): ?>
      <div style="margin-bottom:14px">
        <div style="font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:<?= $niv_colors[$nd['niveau']]??'#475569' ?>;margin-bottom:5px">
          <i class="fa-solid fa-bullseye"></i> Objectifs
        </div>
        <p style="margin:0;font-size:.9rem;line-height:1.65;color:#1e293b"><?= nl2br(htmlspecialchars((string)$nd['objectifs'],ENT_QUOTES,'UTF-8')) ?></p>
      </div>
      <?php endif; ?>
      <?php if (!empty($nd['prerequis'])): ?>
      <div style="margin-bottom:14px">
        <div style="font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:<?= $niv_colors[$nd['niveau']]??'#475569' ?>;margin-bottom:5px">
          <i class="fa-solid fa-screwdriver-wrench"></i> Prérequis
        </div>
        <p style="margin:0;font-size:.9rem;line-height:1.65;color:#1e293b"><?= nl2br(htmlspecialchars((string)$nd['prerequis'],ENT_QUOTES,'UTF-8')) ?></p>
      </div>
      <?php endif; ?>
      <?php if (!empty($nd['public_cible'])): ?>
      <div>
        <div style="font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:<?= $niv_colors[$nd['niveau']]??'#475569' ?>;margin-bottom:5px">
          <i class="fa-solid fa-users"></i> Public cible
        </div>
        <p style="margin:0;font-size:.9rem;line-height:1.65;color:#1e293b"><?= nl2br(htmlspecialchars((string)$nd['public_cible'],ENT_QUOTES,'UTF-8')) ?></p>
      </div>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
    <script>
    function fnPickNiv(btn, niv) {
      document.querySelectorAll('.fn-pill').forEach(function(p) {
        p.classList.remove('fn-pill-on');
        p.style.background = '#fff';
        p.style.color = '#475569';
      });
      btn.classList.add('fn-pill-on');
      var bg = {'debutant':'#dcfce7','intermediaire':'#dbeafe','expert':'#fce7f3'};
      var cl = {'debutant':'#166534','intermediaire':'#1e40af','expert':'#9d174d'};
      btn.style.background = bg[niv] || '#f1f5f9';
      btn.style.color = cl[niv] || '#475569';
      document.querySelectorAll('[id^="fn-block-"]').forEach(function(b){ b.style.display='none'; });
      var bl = document.getElementById('fn-block-'+niv);
      if (bl) bl.style.display = '';
    }
    </script>
  </section>
  <?php endif; ?>

  <section class="grid">
    <div class="box">
      <h3><i class="fa-solid fa-book"></i> Modules</h3>
      <?= afficher_modules_depuis_texte_plat((string)($f['modules'] ?? '')); ?>
      <?php if (!empty($f['public_cible'])): ?>
        <h3 style="margin-top:20px"><i class="fa-solid fa-users"></i> Public cible</h3>
        <p style="margin:0;line-height:1.6"><?= nl($f['public_cible']); ?></p>
      <?php endif; ?>
    </div>

    <aside class="box">
      <h3><i class="fa-solid fa-money-bill-wave"></i> Tarifs</h3>
      <?php
        $promo = promo_earlybird($f);
        $tEnL  = (int)($f['tarif_en_ligne'] ?? 0);
        $tPres = (int)($f['tarif_presentiel'] ?? 0);
        $on    = !empty($promo['eligible']);
      ?>
      <div class="price">
        <?php if ($tEnL <= 0 && $tPres <= 0): ?>
          <i class="fa-solid fa-circle-info"></i> <em>Tarif sur demande</em>
        <?php else: ?>
        <?php if ($tEnL > 0): ?>
        <i class="fa-solid fa-laptop"></i> En ligne :
        <?php if ($on): ?>
          <span style="text-decoration:line-through;opacity:.6"><?= number_format($tEnL + PROMO_REMISE_EN_LIGNE, 0, ',', ' '); ?></span>
          <b style="color:#16a34a"><?= number_format($tEnL, 0, ',', ' '); ?> FCFA</b><br>
        <?php else: ?>
          <?= number_format($tEnL, 0, ',', ' '); ?> FCFA<br>
        <?php endif; ?>
        <?php endif; ?>
        <?php if ($tPres > 0): ?>
        <i class="fa-solid fa-building"></i> Présentiel :
        <?php if ($on): ?>
          <span style="text-decoration:line-through;opacity:.6"><?= number_format($tPres + PROMO_REMISE_PRESENTIEL, 0, ',', ' '); ?></span>
          <b style="color:#16a34a"><?= number_format($tPres, 0, ',', ' '); ?> FCFA</b>
        <?php else: ?>
          <?= number_format($tPres, 0, ',', ' '); ?> FCFA
        <?php endif; ?>
        <?php endif; ?>
        <?php endif; ?>
      </div>

      <?php if ($on): ?>
        <div style="margin-top:12px;background:linear-gradient(135deg,rgba(245,166,35,.16),rgba(255,46,46,.10));border:1px solid rgba(245,166,35,.4);border-radius:12px;padding:12px 14px;font-size:13px;color:#b45309;font-weight:700">
          🐦 Offre Anticipée : <b><?= h($promo['label']); ?></b><br>
          <span style="font-weight:500;color:#7a5212">en vous inscrivant avant le <?= h(date('d/m/Y', (int)$promo['deadline'])); ?>.</span>
        </div>
      <?php endif; ?>

      <?php if (referral_active_code() !== ''): ?>
        <div style="margin-top:12px;background:linear-gradient(135deg,rgba(11,74,166,.12),rgba(37,211,102,.12));border:1px solid rgba(11,74,166,.35);border-radius:12px;padding:12px 14px;font-size:13px;color:#0c4a6e;font-weight:700">
          🎁 Parrainage actif : <b>−<?= (int)referral_percent(); ?>%</b> appliqué à votre paiement.
        </div>
      <?php endif; ?>

      <?php formation_cta($f, $inscription); ?>
    </aside>
  </section>

  <?php
    /* ===== Données pour les blocs de conversion ===== */
    $modsList = array_values(array_filter(array_map('trim', explode(';', (string)($f['modules'] ?? '')))));
    $titreFmt = (string)($f['titre'] ?? '');
    $waMsg    = rawurlencode("Bonjour, je souhaite des informations sur la formation : " . $titreFmt);
    /* Certificats : Samedi Pro -> attestation ; Pack "X en 1" -> X certificats ; sinon 1 certificat */
    $estSamedi = !empty($f['is_samedi_pro']);
    $nbCerts   = 0;
    if (!$estSamedi && preg_match('/(\d+)\s*en\s*1/i', $titreFmt, $mCert)) { $nbCerts = (int)$mCert[1]; }
    /* Chiffres clés IBIG EDUFORM (éditables en base — settings) */
    $STATS = [
      [setting('stat_apprenants',   '1 000+'), 'Apprenants formés'],
      [setting('stat_satisfaction', '+90%'),   'Taux de satisfaction'],
      [setting('stat_experience',   '3 ans'),  "d'expérience"],
      [setting('stat_certifiantes', '100%'),   'Formations certifiantes'],
    ];
    /* Secteurs où exercent les diplômés (éditable) */
    $secteurs = [
      'Banques & microfinance', 'ONG & projets de développement', 'Cabinets comptables & d\'audit',
      'PME & grandes entreprises', 'Administrations & collectivités', 'Indépendants & entrepreneurs',
    ];
    /* ===== TÉMOIGNAGES RÉELS ANONYMISÉS =====
       ⚠️ Laisser VIDE tant que vous n'avez pas de vrais propos.
       Format : ['texte' => 'la citation réelle', 'auteur' => 'M. K. — Comptable, Abidjan', 'note' => 5]
       Exemple (à REMPLACER par de vrais témoignages, sans le nom complet) :
       ['texte' => 'Formation très concrète, montée en compétence rapide.', 'auteur' => 'Apprenant Pack DAF — 2025', 'note' => 5], */
    /* Témoignages = avis publiés (gérés dans Admin → Avis & Témoignages) */
    $temoignages = [];
    foreach (get_approved_avis(3) as $a) {
      $loc = trim((string)($a['ville'] ?? '') . ' ' . (string)($a['pays'] ?? ''));
      $meta = trim(implode(' · ', array_filter([(string)($a['secteur'] ?? ''), $loc])));
      $temoignages[] = [
        'texte'  => (string)($a['texte'] ?? ''),
        'auteur' => trim((string)($a['nom'] ?? 'Apprenant') . ($meta !== '' ? ' — ' . $meta : '')),
        'note'   => 5,
      ];
    }
  ?>
  <style>
    .conv{margin-top:30px;display:grid;gap:22px}
    .conv-card{background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.09);border-radius:16px;padding:24px}
    .conv-card h3{margin:0 0 16px;font-size:17px;display:flex;align-items:center;gap:9px;color:#fff}
    .benefits{list-style:none;margin:0;padding:0;display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:11px 20px}
    .benefits li{display:flex;gap:10px;align-items:flex-start;font-size:14px;line-height:1.5;color:#d7dff1}
    .benefits li i{color:#34d399;margin-top:3px}
    .cert-row{display:flex;flex-wrap:wrap;gap:14px}
    .cert-box{flex:1;min-width:220px;background:linear-gradient(135deg,rgba(245,158,11,.14),rgba(232,36,44,.08));border:1px solid rgba(245,158,11,.32);border-radius:14px;padding:18px;color:#f3e6cf}
    .cert-box .ico{font-size:24px;display:block;margin-bottom:8px}
    .cert-box b{color:#fff}
    .bonus{list-style:none;margin:14px 0 0;padding:0;display:grid;gap:9px}
    .bonus li{display:flex;gap:10px;align-items:center;font-size:14px;color:#d7dff1}
    .bonus li i{color:#fbbf24;width:18px;text-align:center}
    .stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:16px;text-align:center}
    .stats .s b{display:block;font-size:27px;font-weight:800;background:linear-gradient(135deg,#fbbf24,#fb7185);-webkit-background-clip:text;background-clip:text;color:transparent}
    .stats .s span{font-size:12px;color:#9fb0c9}
    .faq details{border:1px solid rgba(255,255,255,.09);border-radius:12px;margin-bottom:10px;background:rgba(255,255,255,.03)}
    .faq summary{cursor:pointer;padding:14px 16px;font-weight:600;font-size:14px;color:#eaf0fb;list-style:none;display:flex;justify-content:space-between;gap:12px;align-items:center}
    .faq summary::-webkit-details-marker{display:none}
    .faq summary::after{content:'+';color:#fbbf24;font-weight:800;font-size:18px}
    .faq details[open] summary::after{content:'\2013'}
    .faq p{margin:0;padding:0 16px 14px;font-size:13.5px;color:#c3cee0;line-height:1.6}
    .advisor-wrap{display:flex;flex-wrap:wrap;align-items:center;gap:14px;margin-top:6px}
    .advisor{display:inline-flex;align-items:center;gap:10px;background:#25D366;color:#06351e;text-decoration:none;font-weight:800;padding:13px 20px;border-radius:12px;font-size:14px}
    .advisor:hover{filter:brightness(1.06)}
    .rating{display:flex;flex-wrap:wrap;align-items:center;gap:10px 16px;margin-bottom:16px}
    .rating .stars{font-size:22px;letter-spacing:2px;color:#fbbf24;line-height:1}
    .rating .stars .dim{color:rgba(251,191,36,.35)}
    .rating .txt{font-size:14px;color:#d7dff1}
    .rating .txt b{color:#fff;font-size:16px}
    .secteurs p{margin:0 0 10px;font-size:13.5px;color:#c3cee0}
    .secteurs .chips{display:flex;flex-wrap:wrap;gap:8px}
    .secteurs .chips span{background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:999px;padding:7px 13px;font-size:12.5px;color:#e7edf8}
    .temoignages{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:16px}
    .temo{margin:0;background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.09);border-radius:14px;padding:18px}
    .temo .stars{color:#fbbf24;font-size:14px;letter-spacing:1px;margin-bottom:8px}
    .temo blockquote{margin:0 0 10px;font-size:14px;line-height:1.6;color:#e7edf8;font-style:italic}
    .temo figcaption{font-size:12.5px;color:#9fb0c9;font-weight:600}
    .temo-note{margin:14px 0 0;font-size:11.5px;color:#7f8ca5;font-style:italic}
  </style>

  <div class="conv">

    <!-- BÉNÉFICES + CERTIFICAT + BONUS -->
    <div class="conv-card">
      <?php if ($modsList): ?>
        <h3><i class="fa-solid fa-circle-check" style="color:#34d399"></i> Ce que vous saurez faire</h3>
        <ul class="benefits">
          <?php foreach ($modsList as $mod): ?>
            <li><i class="fa-solid fa-check"></i> Maîtriser <?= h($mod); ?></li>
          <?php endforeach; ?>
        </ul>
        <hr style="border:0;border-top:1px solid rgba(255,255,255,.08);margin:22px 0">
      <?php endif; ?>

      <div class="cert-row">
        <div class="cert-box">
          <span class="ico">🎓</span>
          <?php if ($estSamedi): ?>
            <b>Attestation délivrée</b><br>
            Une <b>attestation de participation IBIG EDUFORM</b> vous est remise à l'issue de la formation, valorisable sur votre CV.
          <?php elseif ($nbCerts >= 2): ?>
            <b><?= $nbCerts; ?> certificats à la clé</b><br>
            Ce programme <b><?= $nbCerts; ?> en 1</b> délivre <b><?= $nbCerts; ?> certificats IBIG EDUFORM</b> à l'issue de la formation :
            <ul class="bonus" style="margin-top:12px">
              <?php foreach ($modsList as $mod): ?>
                <li><i class="fa-solid fa-certificate"></i> Certificat «&nbsp;<?= h($mod); ?>&nbsp;»</li>
              <?php endforeach; ?>
            </ul>
          <?php else: ?>
            <b>1 certificat à la clé</b><br>
            Un <b>certificat IBIG EDUFORM aux métiers de «&nbsp;<?= h($titreFmt); ?>&nbsp;»</b> vous est délivré à l'issue de la formation, valorisable sur votre CV.
          <?php endif; ?>
        </div>
        <div class="cert-box" style="background:linear-gradient(135deg,rgba(31,63,224,.16),rgba(122,47,206,.10));border-color:rgba(77,107,255,.35)">
          <span class="ico">🎁</span>
          <b>Bonus inclus</b>
          <ul class="bonus">
            <li><i class="fa-solid fa-file-arrow-down"></i> Support de cours &amp; ressources</li>
            <li><i class="fa-solid fa-toolbox"></i> Outils &amp; modèles pratiques</li>
            <li><i class="fa-brands fa-whatsapp"></i> Groupe d'entraide &amp; suivi</li>
            <li><i class="fa-solid fa-headset"></i> Accompagnement post-formation</li>
          </ul>
        </div>
      </div>
    </div>

    <!-- CHIFFRES CLÉS -->
    <div class="conv-card">
      <h3><i class="fa-solid fa-chart-line" style="color:#fbbf24"></i> Ils nous font confiance</h3>
      <div class="stats">
        <?php foreach ($STATS as $st): ?>
          <div class="s"><b><?= h($st[0]); ?></b><span><?= h($st[1]); ?></span></div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- AVIS AGRÉGÉ + SECTEURS -->
    <div class="conv-card">
      <h3><i class="fa-solid fa-star" style="color:#fbbf24"></i> Avis &amp; débouchés</h3>
      <div class="rating">
        <div class="stars" aria-label="4,5 sur 5">★★★★<span class="dim">★</span></div>
        <div class="txt"><b>≈ 4,5/5</b> &middot; <?= h(setting('stat_satisfaction','+90%')); ?> de satisfaction sur <?= h(setting('stat_apprenants','1 000+')); ?> apprenants formés</div>
      </div>
      <div class="secteurs">
        <p>Nos diplômés exercent dans :</p>
        <div class="chips">
          <?php foreach ($secteurs as $sec): ?><span><?= h($sec); ?></span><?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- TÉMOIGNAGES (réels, anonymisés) — masqué si vide -->
    <?php if (!empty($temoignages)): ?>
    <div class="conv-card">
      <h3><i class="fa-solid fa-quote-left" style="color:#34d399"></i> Ils témoignent</h3>
      <div class="temoignages">
        <?php foreach ($temoignages as $t): $note = max(1, min(5, (int)($t['note'] ?? 5))); ?>
          <figure class="temo">
            <div class="stars"><?= str_repeat('★', $note) . str_repeat('☆', 5 - $note); ?></div>
            <blockquote>«&nbsp;<?= h($t['texte'] ?? ''); ?>&nbsp;»</blockquote>
            <figcaption>— <?= h($t['auteur'] ?? 'Apprenant IBIG EDUFORM'); ?></figcaption>
          </figure>
        <?php endforeach; ?>
      </div>
      <p class="temo-note">Avis de participants IBIG EDUFORM.</p>
    </div>
    <?php endif; ?>

    <!-- FAQ -->
    <div class="conv-card faq">
      <h3><i class="fa-solid fa-circle-question" style="color:#4d6bff"></i> Questions fréquentes</h3>
      <details open>
        <summary>La formation est-elle en ligne ou en présentiel ?</summary>
        <p>Les deux ! Vous choisissez le format qui vous convient : <b>présentiel</b> à Abidjan ou <b>en ligne</b> en visioconférence interactive. Les tarifs des deux formats sont indiqués sur cette page.</p>
      </details>
      <details>
        <summary>Vais-je recevoir un certificat ?</summary>
        <p>
          <?php if ($estSamedi): ?>
            Oui, une <b>attestation de participation IBIG EDUFORM</b> vous est délivrée à la fin de la formation.
          <?php elseif ($nbCerts >= 2): ?>
            Oui — ce programme <b><?= $nbCerts; ?> en 1</b> délivre <b><?= $nbCerts; ?> certificats IBIG EDUFORM</b> à l'issue de la formation (un par spécialité).
          <?php else: ?>
            Oui, un <b>certificat IBIG EDUFORM aux métiers de «&nbsp;<?= h($titreFmt); ?>&nbsp;»</b> vous est délivré à la fin de la formation.
          <?php endif; ?>
        </p>
      </details>
      <details>
        <summary>Comment se passe le paiement ?</summary>
        <p>Vous réglez d'abord les <b>frais d'inscription</b> pour réserver votre place. Des <b>facilités de paiement</b> sont possibles : parlez-en à un conseiller.</p>
      </details>
      <details>
        <summary>Faut-il un niveau particulier ?</summary>
        <p>Nos formations sont accessibles, du débutant au confirmé. Le <b>public cible</b> est précisé plus haut sur cette page.</p>
      </details>
      <details>
        <summary>Et si je manque une séance ?</summary>
        <p>En format en ligne, les ressources vous permettent de rattraper. En cas d'absence, contactez-nous : nous trouvons une solution.</p>
      </details>

      <div class="advisor-wrap">
        <a class="advisor" href="https://wa.me/2250778882592?text=<?= $waMsg; ?>" target="_blank" rel="noopener">
          <i class="fa-brands fa-whatsapp" style="font-size:18px"></i> Parler à un conseiller
        </a>
        <span style="color:#9fb0c9;font-size:13px">Une question ? Réponse rapide sur WhatsApp.</span>
      </div>
    </div>

    <!-- CAPTURE DE LEADS -->
    <style>
      .lead-choices{display:flex;flex-wrap:wrap;gap:8px 18px;margin-bottom:14px}
      .lead-choices label{display:inline-flex;align-items:center;gap:7px;font-size:13px;color:#d7dff1;cursor:pointer}
      .lead-choices input{accent-color:#4d6bff}
      .lead-fields{display:grid;grid-template-columns:1fr 1fr auto;gap:10px}
      .lead-fields input{padding:12px 13px;border-radius:11px;border:1.5px solid rgba(255,255,255,.12);background:rgba(255,255,255,.05);color:#fff;font-size:14px;font-family:inherit}
      .lead-fields input::placeholder{color:#8c98b0}
      .lead-fields input:focus{outline:none;border-color:#4d6bff;background:rgba(255,255,255,.08)}
      #leadBtn{border:0;border-radius:11px;cursor:pointer;font-family:inherit;font-weight:700;font-size:14px;color:#06351e;background:#fbbf24;padding:0 20px;white-space:nowrap}
      #leadBtn:hover{filter:brightness(1.05)} #leadBtn:disabled{opacity:.6}
      .lead-msg{margin:12px 0 0;font-size:13px;min-height:18px}
      @media(max-width:560px){.lead-fields{grid-template-columns:1fr}}
    </style>
    <div class="conv-card" id="leadCard">
      <h3><i class="fa-solid fa-paper-plane" style="color:#fbbf24"></i> Recevez le programme &amp; restez informé</h3>
      <div class="lead-choices">
        <label><input type="radio" name="lead_type" value="calendrier" checked> 📄 Calendrier complet (PDF)</label>
        <label><input type="radio" name="lead_type" value="rappel"> 📞 Être rappelé(e)</label>
        <label><input type="radio" name="lead_type" value="alerte"> 🔔 Alerte nouvelles sessions</label>
      </div>
      <div class="lead-fields">
        <input id="lead_nom" type="text" placeholder="Votre nom" autocomplete="name">
        <input id="lead_contact" type="text" placeholder="WhatsApp ou email" autocomplete="tel">
        <button id="leadBtn" type="button">Recevoir →</button>
      </div>
      <p class="lead-msg" id="leadMsg"></p>
    </div>
    <script>
      var LEAD_CSRF = <?= json_encode(function_exists('csrf_token') ? csrf_token() : ''); ?>;
      var LEAD_FID = <?= (int)($f['id'] ?? 0); ?>;
      var LEAD_FTITRE = <?= json_encode((string)($f['titre'] ?? '')); ?>;
      (function(){
        var btn=document.getElementById('leadBtn'); if(!btn) return;
        btn.addEventListener('click',function(){
          var nom=document.getElementById('lead_nom').value.trim();
          var contact=document.getElementById('lead_contact').value.trim();
          var sel=document.querySelector('input[name=lead_type]:checked');
          var type=sel?sel.value:'rappel';
          var msg=document.getElementById('leadMsg');
          if(!nom||!contact){ msg.style.color='#fb7185'; msg.textContent='Indiquez votre nom et votre contact.'; return; }
          btn.disabled=true; var old=btn.textContent; btn.textContent='Envoi…';
          var fd=new FormData();
          fd.append('csrf',LEAD_CSRF); fd.append('nom',nom); fd.append('contact',contact);
          fd.append('type',type); fd.append('formation_id',LEAD_FID); fd.append('formation_titre',LEAD_FTITRE);
          fetch('/lead.php',{method:'POST',body:fd})
            .then(function(r){return r.json();})
            .then(function(d){
              btn.disabled=false; btn.textContent=old;
              if(d&&d.ok){ msg.style.color='#34d399'; msg.textContent=d.message||'Merci !';
                document.getElementById('lead_nom').value=''; document.getElementById('lead_contact').value='';
                if(d.download){ window.open(d.download,'_blank'); } }
              else { msg.style.color='#fb7185'; msg.textContent=(d&&d.error)||'Erreur, réessayez.'; }
            })
            .catch(function(){ btn.disabled=false; btn.textContent=old; msg.style.color='#fb7185'; msg.textContent='Connexion impossible, réessayez.'; });
        });
      })();
    </script>

  </div>

<?php endif; ?>
<?php if (!empty($sessions)): ?>
<section class="sessions">
  <h2><i class="fa-solid fa-calendar-days"></i> Sessions opérationnelles (inscriptions ouvertes)</h2>

  <div class="sessions-grid">
    <?php foreach ($sessions as $s): ?>
      <?php
        $st = (string)($s['statut'] ?? 'a_venir');
        $badgeLabel = strtoupper(str_replace('_',' ', $st));
      ?>
      <div class="session-card">
        <span class="session-badge <?= h($st); ?>"><?= h($badgeLabel); ?></span>

        <p style="margin-top:12px;">
          <i class="fa-solid fa-calendar"></i> Début :
          <strong><?= h(date('d/m/Y', strtotime((string)($s['date_debut'] ?? '')))); ?></strong><br>

          <?php if (!empty($s['date_fin'])): ?>
            <i class="fa-solid fa-calendar-check"></i> Fin :
            <?= h(date('d/m/Y', strtotime((string)$s['date_fin']))); ?><br>
          <?php endif; ?>

          <i class="fa-solid fa-clock"></i> Durée :
          <?= h($s['duree'] ?? ''); ?><br>

          <i class="fa-solid fa-laptop"></i> Mode :
          <?= h(ucfirst(str_replace('_',' ', (string)($s['mode'] ?? '')))); ?>
        </p>

        <?php formation_cta($f, $inscription); ?>
      </div>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<?php
  $shareUrl  = 'https://' . $host . '/formation/' . (string)($f['slug'] ?? '');
  $shareText = (string)($f['titre'] ?? 'Formation professionnelle');
?>
<div class="share-box">

  <div class="share-title">
    &#128257; Partager cette formation
  </div>

  <div class="share-buttons">

    <a class="share-wa"
       onclick="trackShare('whatsapp')"
       href="https://wa.me/?text=<?= urlencode($shareText . ' ' . $shareUrl); ?>"
       target="_blank" rel="noopener">
      <i class="fa-brands fa-whatsapp"></i>
      WhatsApp
    </a>

    <a class="share-fb"
       onclick="trackShare('facebook')"
       href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($shareUrl); ?>"
       target="_blank" rel="noopener">
      <i class="fa-brands fa-facebook-f"></i>
      Facebook
    </a>

    <a class="share-ln"
       onclick="trackShare('linkedin')"
       href="https://www.linkedin.com/sharing/share-offsite/?url=<?= urlencode($shareUrl); ?>"
       target="_blank" rel="noopener">
      <i class="fa-brands fa-linkedin-in"></i>
      LinkedIn
    </a>

    <a class="share-x"
       onclick="trackShare('x')"
       href="https://twitter.com/intent/tweet?text=<?= urlencode($shareText); ?>&url=<?= urlencode($shareUrl); ?>"
       target="_blank" rel="noopener">
      <i class="fa-brands fa-x-twitter"></i>
      X
    </a>

    <button class="share-copy"
      onclick="trackShare('copy');navigator.clipboard.writeText('<?= h($shareUrl); ?>')">
      <i class="fa-solid fa-link"></i>
      Copier le lien
    </button>

  </div>
</div>

</main>

<script>
function trackShare(platform){
  fetch('/track-share.php', {
    method: 'POST',
    headers: {'Content-Type':'application/x-www-form-urlencoded'},
    body: new URLSearchParams({
      platform: platform,
      formation_id: '<?= (int)$f['id']; ?>',
      page_url: window.location.href
    })
  });
}
</script>

<script>
function iceTrack(action, formationId, label){
  const sk = (localStorage.getItem('session_key') || '');
  if(!sk) return;

  const fd = new FormData();
  fd.append('session_key', sk);
  fd.append('action', action);
  fd.append('formation_id', formationId || '');
  fd.append('label', label || '');
  fd.append('page_url', location.pathname + location.search);

  fetch('/track-event.php', { method:'POST', body: fd, keepalive:true });
}
</script>

<script>
  window.FORMATION_ID = <?= (int)$f['id']; ?>;
  window.SESSION_KEY  = "<?= h($_SESSION['session_key'] ?? session_id()); ?>";
  window.UTM_SOURCE   = "<?= h($_SESSION['utm_source'] ?? ''); ?>";
  window.UTM_CAMPAIGN = "<?= h($_SESSION['utm_campaign'] ?? ''); ?>";
</script>

<script>
function trackEvent(type){
  fetch('/track.php', {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify({
      event_type: type,
      formation_id: <?= (int)$f['id']; ?>
    })
  });
}
</script>

<?php
/* ===========================================================
   DONNÉES STRUCTURÉES (SEO) — Course + FAQ pour Google
=========================================================== */
$ldHost = (string)($_SERVER['HTTP_HOST'] ?? 'ibig-eduform.com');
$ldUrl  = 'https://' . $ldHost . '/formation/' . (string)($f['slug'] ?? ($f['id'] ?? ''));
$ldSamedi = !empty($f['is_samedi_pro']);
$ldNb = 0;
if (!$ldSamedi && preg_match('/(\d+)\s*en\s*1/i', (string)($f['titre'] ?? ''), $mLd)) { $ldNb = (int)$mLd[1]; }
if ($ldSamedi)        { $ldCertAns = "Oui, une attestation de participation IBIG EDUFORM est délivrée à la fin de la formation."; }
elseif ($ldNb >= 2)   { $ldCertAns = "Oui, ce programme {$ldNb} en 1 délivre {$ldNb} certificats IBIG EDUFORM à l'issue de la formation."; }
else                  { $ldCertAns = "Oui, un certificat IBIG EDUFORM est délivré à la fin de la formation."; }

$ldCourse = [
  '@context'    => 'https://schema.org',
  '@type'       => 'Course',
  'name'        => (string)($f['titre'] ?? ''),
  'description' => mb_substr(trim(strip_tags((string)($f['description'] ?? ''))), 0, 300),
  'provider'    => ['@type' => 'Organization', 'name' => 'IBIG EDUFORM', 'url' => 'https://ibig-eduform.com', 'logo' => 'https://' . $ldHost . '/assets/images/logo.png'],
  'url'         => $ldUrl,
  'inLanguage'  => 'fr',
];
if (!empty($ogImage)) { $ldCourse['image'] = $ogImage; }
$ldOffers = [];
if ((int)($f['tarif_en_ligne'] ?? 0) > 0)  { $ldOffers[] = ['@type' => 'Offer', 'category' => 'En ligne',   'price' => (int)$f['tarif_en_ligne'],  'priceCurrency' => 'XOF', 'availability' => 'https://schema.org/InStock', 'url' => $ldUrl]; }
if ((int)($f['tarif_presentiel'] ?? 0) > 0) { $ldOffers[] = ['@type' => 'Offer', 'category' => 'Présentiel', 'price' => (int)$f['tarif_presentiel'], 'priceCurrency' => 'XOF', 'availability' => 'https://schema.org/InStock', 'url' => $ldUrl]; }
if ($ldOffers) { $ldCourse['offers'] = (count($ldOffers) === 1) ? $ldOffers[0] : $ldOffers; }

$ldCi = ['@type' => 'CourseInstance', 'courseMode' => $ldSamedi ? 'Onsite' : 'Blended',
         'location' => ['@type' => 'Place', 'name' => 'IBIG EDUFORM', 'address' => 'Abidjan, Côte d\'Ivoire']];
if (preg_match('/(\d+)\s*H/i', (string)($f['duree'] ?? ''), $mW)) { $ldCi['courseWorkload'] = 'PT' . (int)$mW[1] . 'H'; }
if (!empty($f['date_debut'])) { $ldCi['startDate'] = date('Y-m-d', strtotime((string)$f['date_debut'])); }
if (!empty($f['date_fin']))   { $ldCi['endDate']   = date('Y-m-d', strtotime((string)$f['date_fin'])); }
$ldCourse['hasCourseInstance'] = $ldCi;

$ldFaq = ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => [
  ['@type' => 'Question', 'name' => 'La formation est-elle en ligne ou en présentiel ?', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'Les deux : en présentiel à Abidjan ou 100 % en ligne en visioconférence interactive, accessible partout dans l\'espace OHADA.']],
  ['@type' => 'Question', 'name' => 'Vais-je recevoir un certificat ?', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $ldCertAns]],
  ['@type' => 'Question', 'name' => 'Comment se passe le paiement ?', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => "Vous réglez d'abord les frais d'inscription pour réserver votre place. Des facilités de paiement sont possibles."]],
  ['@type' => 'Question', 'name' => 'Faut-il un niveau particulier ?', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'Nos formations sont accessibles, du débutant au confirmé.']],
]];
echo "\n<script type=\"application/ld+json\">" . json_encode($ldCourse, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "</script>\n";
echo "<script type=\"application/ld+json\">" . json_encode($ldFaq, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "</script>\n";
?>

<!-- ===== MODALE TÉLÉCHARGEMENT TDR (capture email + WhatsApp) ===== -->
<style>
  .tdr-modal{position:fixed;inset:0;background:rgba(2,6,23,.72);display:none;align-items:center;justify-content:center;z-index:99999;padding:18px}
  .tdr-modal.open{display:flex}
  .tdr-box{background:#0b1220;border:1px solid rgba(255,255,255,.12);border-radius:16px;max-width:430px;width:100%;padding:26px;color:#e5e7eb}
  .tdr-box h3{margin:0 0 6px;color:#fff;font-size:19px}
  .tdr-box .sub{margin:0 0 16px;font-size:13px;color:#9fb0c9}
  .tdr-box label{display:block;font-size:12px;margin:10px 0 4px;color:#c3cee0}
  .tdr-box input{width:100%;padding:12px;border-radius:10px;border:1px solid rgba(255,255,255,.15);background:rgba(255,255,255,.05);color:#fff;font-size:14px;font-family:inherit;box-sizing:border-box}
  .tdr-box input:focus{outline:none;border-color:#4d6bff}
  .tdr-msg{font-size:12.5px;min-height:16px;margin-top:8px}
  .tdr-row2{display:flex;gap:10px;margin-top:16px}
  .tdr-row2 button{flex:1;border:0;border-radius:10px;padding:13px;font-weight:800;cursor:pointer;font-family:inherit;font-size:14px}
  .tdr-go{background:#22c55e;color:#022c22}
  .tdr-cancel{background:transparent;color:#9fb0c9;border:1px solid rgba(255,255,255,.15)}
</style>
<div class="tdr-modal" id="tdrModal" role="dialog" aria-modal="true">
  <div class="tdr-box">
    <h3>📄 Télécharger le TDR</h3>
    <p class="sub">Recevez le programme détaillé (TDR). Indiquez vos coordonnées <strong>(une seule fois pour ce TDR)</strong> :</p>
    <label>Nom</label>
    <input id="tdr_nom" type="text" autocomplete="name">
    <label>Email</label>
    <input id="tdr_email" type="email" autocomplete="email">
    <label>WhatsApp</label>
    <input id="tdr_wa" type="tel" placeholder="+225 07 00 00 00 00" autocomplete="tel">
    <p class="tdr-msg" id="tdrMsg"></p>
    <div class="tdr-row2">
      <button class="tdr-cancel" type="button" id="tdrCancel">Annuler</button>
      <button class="tdr-go" type="button" id="tdrGo">Télécharger →</button>
    </div>
  </div>
</div>
<script>
  (function(){
    var TDR_CSRF = <?= json_encode(function_exists('csrf_token') ? csrf_token() : ''); ?>;
    var modal=document.getElementById('tdrModal'), msg=document.getElementById('tdrMsg');
    var tdrFid=0, tdrTitre='';

    /* --- Mémoire par TDR (localStorage) ---------------------------------
       - 'ibig_tdr_unlocked' : liste des formations déjà débloquées (1 fois)
       - 'ibig_tdr_contact'  : dernières coordonnées (pré-remplissage confort)
       Un même TDR ne redemande jamais les coordonnées ; un AUTRE TDR oui. */
    function tdrStore(){ try{ var v=JSON.parse(localStorage.getItem('ibig_tdr_unlocked')||'[]'); return Array.isArray(v)?v:[]; }catch(_){ return []; } }
    function tdrUnlocked(id){ return tdrStore().indexOf(String(id))!==-1; }
    function tdrMarkUnlocked(id){ try{ var s=tdrStore(); if(s.indexOf(String(id))===-1){ s.push(String(id)); localStorage.setItem('ibig_tdr_unlocked',JSON.stringify(s)); } }catch(_){ } }
    function tdrSaveContact(c){ try{ localStorage.setItem('ibig_tdr_contact',JSON.stringify(c)); }catch(_){ } }
    function tdrGetContact(){ try{ return JSON.parse(localStorage.getItem('ibig_tdr_contact')||'null'); }catch(_){ return null; } }
    function tdrPrefill(){
      var c=tdrGetContact(); if(!c) return;
      var n=document.getElementById('tdr_nom'), em=document.getElementById('tdr_email'), w=document.getElementById('tdr_wa');
      if(n&&!n.value&&c.nom)   n.value=c.nom;
      if(em&&!em.value&&c.email) em.value=c.email;
      if(w&&!w.value&&c.wa)    w.value=c.wa;
    }
    function tdrOpen(id,slug){ window.open(slug ? '/tdr-local-pdf.php?slug='+encodeURIComponent(slug) : '/tdr-local-pdf.php?slug='+encodeURIComponent(id),'_blank'); }

    function close(){ modal.classList.remove('open'); }
    document.getElementById('tdrCancel').addEventListener('click',close);
    modal.addEventListener('click',function(e){ if(e.target===modal) close(); });
    document.addEventListener('click',function(e){
      var a=e.target.closest('.js-tdr'); if(!a) return;
      e.preventDefault();
      tdrFid=a.getAttribute('data-formation')||0; tdrTitre=a.getAttribute('data-titre')||'';
      var tdrSlug=a.getAttribute('data-slug')||'';
      /* Déjà débloqué pour CE TDR : téléchargement direct, sans formulaire */
      if(tdrUnlocked(tdrFid)){ tdrOpen(tdrFid,tdrSlug); return; }
      /* Sinon : on demande (en pré-remplissant si on connaît déjà le contact) */
      tdrPrefill();
      msg.textContent=''; modal.classList.add('open');
      setTimeout(function(){var n=document.getElementById('tdr_nom'); if(n&&!n.value){ n.focus(); }},80);
    });
    document.getElementById('tdrGo').addEventListener('click',function(){
      var go=this;
      var nom=document.getElementById('tdr_nom').value.trim();
      var email=document.getElementById('tdr_email').value.trim();
      var wa=document.getElementById('tdr_wa').value.trim();
      if(!nom||!email||!wa){ msg.style.color='#fb7185'; msg.textContent='Renseignez nom, email et WhatsApp.'; return; }
      if(!/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(email)){ msg.style.color='#fb7185'; msg.textContent='Email invalide.'; return; }
      go.disabled=true; var old=go.textContent; go.textContent='…';
      var fd=new FormData();
      fd.append('csrf',TDR_CSRF); fd.append('nom',nom);
      fd.append('email',email); fd.append('whatsapp',wa);
      fd.append('contact',email+' · WhatsApp: '+wa);
      fd.append('type','tdr'); fd.append('formation_id',tdrFid); fd.append('formation_titre',tdrTitre);
      fetch('/lead.php',{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(d){
        go.disabled=false; go.textContent=old;
        if(d&&d.ok){ msg.style.color='#34d399'; msg.textContent='Merci ! Téléchargement en cours…';
          tdrMarkUnlocked(tdrFid); tdrSaveContact({nom:nom,email:email,wa:wa});
          var url=d.download||(tdrSlug ? '/tdr-local-pdf.php?slug='+encodeURIComponent(tdrSlug) : null);
          if(url) window.open(url,'_blank'); setTimeout(close,900); }
        else { msg.style.color='#fb7185'; msg.textContent=(d&&d.error)||'Erreur, réessayez.'; }
      }).catch(function(){ go.disabled=false; go.textContent=old; msg.style.color='#fb7185'; msg.textContent='Connexion impossible, réessayez.'; });
    });
  })();
</script>

<?php include __DIR__ . '/partials/fiche_conversion.php'; ?>

<?php
/* ===== FORMATIONS LIÉES (même domaine) + BLOC CONVERSION ===== */
$related = [];
try {
  $stmtRel = $pdo->prepare("
    SELECT f.id, f.titre, f.slug, f.domaine, f.date_debut, f.is_samedi_pro, l.hero_image
    FROM formations f
    LEFT JOIN formation_landings l ON l.formation_id = f.id
    WHERE f.statut='active' AND f.id <> :id
      AND COALESCE(f.date_fin, f.date_debut) >= CURDATE()
      AND f.domaine = :dom
    ORDER BY f.date_debut ASC
    LIMIT 3");
  $stmtRel->execute([':id' => (int)($f['id'] ?? 0), ':dom' => (string)($f['domaine'] ?? '')]);
  $related = $stmtRel->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) { $related = []; }
$frMoisRel = [1=>'janv.',2=>'févr.',3=>'mars',4=>'avr.',5=>'mai',6=>'juin',7=>'juil.',8=>'août',9=>'sept.',10=>'oct.',11=>'nov.',12=>'déc.'];
?>
<?php if ($related): ?>
<section class="ib-related" style="max-width:1180px;margin:60px auto 0;padding:0 20px;font-family:Inter,system-ui,sans-serif">
  <h2 style="font-size:24px;font-weight:900;color:#0a1733;margin:0 0 20px">Ces formations peuvent aussi vous intéresser</h2>
  <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:20px" class="ib-related-grid">
    <?php foreach ($related as $rl):
      $rlSlug = (string)($rl['slug'] ?? '');
      $rlUrl  = $rlSlug !== '' ? ('/formation/' . rawurlencode($rlSlug)) : ('/formation.php?id=' . (int)$rl['id']);
      $rlImg  = !empty($rl['hero_image']) ? ('/' . ltrim((string)$rl['hero_image'], '/')) : '';
      if ($rlImg !== '') { $rlWebp = preg_replace('/\.(png|jpe?g)$/i', '.webp', $rlImg); if ($rlWebp !== $rlImg && is_file(__DIR__ . '/' . ltrim($rlWebp, '/'))) { $rlImg = $rlWebp; } }
      $rlTs   = !empty($rl['date_debut']) ? strtotime((string)$rl['date_debut']) : 0;
      $rlDate = $rlTs ? ((int)date('j',$rlTs) . ' ' . ($frMoisRel[(int)date('n',$rlTs)] ?? '') . ' ' . date('Y',$rlTs)) : '';
    ?>
    <a href="<?= h($rlUrl); ?>" style="display:block;background:#fff;border:1px solid #e3ebf6;border-radius:18px;overflow:hidden;text-decoration:none;box-shadow:0 10px 26px rgba(10,23,51,.06);transition:transform .15s ease,box-shadow .15s ease" onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='0 18px 40px rgba(10,23,51,.12)'" onmouseout="this.style.transform='';this.style.boxShadow='0 10px 26px rgba(10,23,51,.06)'">
      <?php if ($rlImg): ?><img src="<?= h($rlImg); ?>" alt="<?= h($rl['titre']); ?>" loading="lazy" style="width:100%;aspect-ratio:1/1;object-fit:cover;display:block"><?php endif; ?>
      <div style="padding:16px 18px">
        <span style="display:inline-block;font-size:11px;font-weight:800;letter-spacing:.04em;text-transform:uppercase;color:#1f3fe0;background:rgba(31,63,224,.08);padding:5px 10px;border-radius:999px"><?= h($rl['is_samedi_pro'] ? 'Samedi Pro' : 'Formation Certifiante'); ?></span>
        <b style="display:block;color:#0a1733;font-size:16px;font-weight:800;margin:10px 0 4px;line-height:1.3"><?= h($rl['titre']); ?></b>
        <small style="color:#5b6b8c;font-size:13px">📅 <?= h($rlDate); ?> · <?= h($rl['domaine']); ?></small>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
  <style>@media(max-width:820px){.ib-related-grid{grid-template-columns:1fr !important}}</style>
</section>
<?php endif; ?>

<?php include __DIR__ . '/partials/besoin_cta.php'; ?>

</main>

<?php
require __DIR__ . '/partials/footer.php';
ob_end_flush();