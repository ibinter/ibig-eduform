<?php
require_once __DIR__ . '/core/config.php';
require_once __DIR__ . '/core/database.php';
require_once __DIR__ . '/core/helpers.php';
require_once __DIR__ . '/core/csrf.php';

$pdo = Database::connect();

/* ── Soumission ── */
$avisMsg = ''; $avisOk = false; $avisOld = ['nom'=>'','ville'=>'','secteur'=>'','note'=>'5','texte'=>''];
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['avis_submit'])) {
  $tokenOk = function_exists('csrf_token') ? hash_equals((string)csrf_token(), (string)($_POST['csrf'] ?? '')) : true;
  $avisOld = [
    'nom'     => trim((string)($_POST['nom'] ?? '')),
    'ville'   => trim((string)($_POST['ville'] ?? '')),
    'secteur' => trim((string)($_POST['secteur'] ?? '')),
    'note'    => trim((string)($_POST['note'] ?? '5')),
    'texte'   => trim((string)($_POST['texte'] ?? '')),
  ];
  if (!$tokenOk) {
    $avisMsg = "Session expirée, merci de renvoyer le formulaire.";
  } elseif (!empty($_POST['website'])) {
    $avisOk = true; $avisMsg = "Merci pour votre retour !";
  } elseif ($avisOld['nom'] === '' || mb_strlen($avisOld['texte'], 'UTF-8') < 15) {
    $avisMsg = "Merci d'indiquer votre nom et un avis d'au moins 15 caractères.";
  } else {
    try {
      $note = max(1, min(5, (int)$avisOld['note']));
      $st = $pdo->prepare("INSERT INTO avis_clients (nom, ville, pays, secteur, texte, note, statut, created_at)
                           VALUES (?, ?, '', ?, ?, ?, 'brouillon', NOW())");
      $st->execute([
        mb_substr($avisOld['nom'], 0, 80, 'UTF-8'),
        mb_substr($avisOld['ville'], 0, 80, 'UTF-8'),
        mb_substr($avisOld['secteur'], 0, 90, 'UTF-8'),
        mb_substr($avisOld['texte'], 0, 600, 'UTF-8'),
        $note,
      ]);
      $avisOk = true;
      $avisMsg = "Merci ! Votre témoignage a bien été envoyé. Il sera publié après validation par notre équipe.";
      $avisOld = ['nom'=>'','ville'=>'','secteur'=>'','note'=>'5','texte'=>''];
    } catch (Throwable $e) {
      try {
        $st2 = $pdo->prepare("INSERT INTO avis_clients (nom, ville, pays, secteur, texte, statut, created_at)
                               VALUES (?, ?, '', ?, ?, 'brouillon', NOW())");
        $st2->execute([
          mb_substr($avisOld['nom'], 0, 80, 'UTF-8'),
          mb_substr($avisOld['ville'], 0, 80, 'UTF-8'),
          mb_substr($avisOld['secteur'], 0, 90, 'UTF-8'),
          mb_substr($avisOld['texte'], 0, 600, 'UTF-8'),
        ]);
        $avisOk = true;
        $avisMsg = "Merci ! Votre témoignage a bien été envoyé. Il sera publié après validation.";
        $avisOld = ['nom'=>'','ville'=>'','secteur'=>'','note'=>'5','texte'=>''];
      } catch (Throwable $e2) {
        $avisMsg = "Une erreur est survenue, merci de réessayer plus tard.";
      }
    }
  }
}

/* ── Avis publiés ── */
try {
  $avis = $pdo->query("
    SELECT nom, ville, pays, secteur, texte,
           COALESCE(note, 5) AS note,
           created_at
    FROM avis_clients
    WHERE statut='publie'
    ORDER BY created_at DESC
  ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  $avis = $pdo->query("
    SELECT nom, ville, pays, secteur, texte,
           5 AS note,
           created_at
    FROM avis_clients
    WHERE statut='publie'
    ORDER BY created_at DESC
  ")->fetchAll(PDO::FETCH_ASSOC);
}

/* ── Stats réelles uniquement ── */
$totalAvis = count($avis);
$avgNote   = $totalAvis ? round(array_sum(array_column($avis,'note')) / $totalAvis, 1) : 0;

/* ── Distribution des notes ── */
$noteDist = [5=>0, 4=>0, 3=>0, 2=>0, 1=>0];
foreach ($avis as $a) {
  $n = max(1, min(5, (int)($a['note'] ?? 5)));
  $noteDist[$n]++;
}

/* ── Secteurs présents dans les avis ── */
$secteurs = [];
foreach ($avis as $a) {
  $s = trim((string)($a['secteur'] ?? ''));
  if ($s !== '' && !in_array($s, $secteurs)) $secteurs[] = $s;
}
sort($secteurs);

$pageTitle = "Témoignages & Avis — IBIG EDUFORM";
$ogDesc    = "Lisez les témoignages authentiques des participants aux formations IBIG EDUFORM. Partagez votre expérience.";
include __DIR__ . '/partials/header.php';
?>

<style>
/* =========================================
   AVIS / TÉMOIGNAGES — IBIG EDUFORM
========================================= */
body {
  background:
    radial-gradient(ellipse 70% 40% at 10% -5%, #0f2a4a28, transparent),
    radial-gradient(ellipse 50% 30% at 90% 10%, #f5a62310, transparent),
    #030b18;
  color:#e5e7eb;
  font-family:Inter,system-ui,-apple-system,sans-serif;
}
.av-wrap { max-width:1120px; margin:100px auto; padding:0 22px 120px; }

/* ── HERO ── */
.av-hero {
  position:relative; overflow:hidden;
  background:linear-gradient(155deg,#0c2e4e 0%,#061120 70%);
  border:1px solid rgba(255,255,255,.09);
  border-radius:28px;
  padding:60px 52px;
  box-shadow:0 40px 100px rgba(0,0,0,.6);
}
.av-hero::before {
  content:'';
  position:absolute; inset:0;
  background:radial-gradient(ellipse 60% 80% at 100% 50%, rgba(245,166,35,.06), transparent);
  pointer-events:none;
}
.av-hero-inner { position:relative; display:flex; align-items:center; gap:48px; flex-wrap:wrap; }
.av-hero-text { flex:1; min-width:260px; }
.av-hero-eyebrow {
  display:inline-flex; align-items:center; gap:8px;
  font-size:11px; font-weight:800; letter-spacing:.12em; text-transform:uppercase;
  color:#f5a623; background:rgba(245,166,35,.10); border:1px solid rgba(245,166,35,.22);
  padding:5px 14px; border-radius:100px; margin-bottom:20px;
}
.av-hero h1 {
  margin:0; font-size:2.9rem; font-weight:900; line-height:1.1;
  background:linear-gradient(135deg,#fff 60%,#cbd5e1);
  -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text;
}
.av-hero p { margin:16px 0 0; font-size:1.02rem; color:#94a3b8; max-width:560px; line-height:1.75; }

/* Bloc note globale */
.av-rating-block {
  display:flex; flex-direction:column; align-items:center; justify-content:center;
  background:rgba(255,255,255,.05); border:1px solid rgba(245,166,35,.22);
  border-radius:20px; padding:28px 36px; text-align:center; min-width:180px;
  flex-shrink:0;
}
.av-rating-big {
  font-size:4rem; font-weight:900; line-height:1;
  background:linear-gradient(135deg,#f5a623,#f97316);
  -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text;
}
.av-rating-stars { color:#f5a623; font-size:20px; letter-spacing:3px; margin:6px 0 4px; }
.av-rating-sub { font-size:12px; color:#94a3b8; font-weight:600; }

/* Distribution barres */
.av-dist { margin-top:28px; display:flex; flex-direction:column; gap:6px; }
.av-dist-row { display:flex; align-items:center; gap:10px; font-size:12px; }
.av-dist-lbl { color:#94a3b8; width:14px; text-align:right; flex-shrink:0; }
.av-dist-bar-wrap {
  flex:1; height:6px; background:rgba(255,255,255,.08); border-radius:100px; overflow:hidden;
}
.av-dist-bar { height:100%; background:linear-gradient(90deg,#f5a623,#f97316); border-radius:100px; transition:.6s ease; }
.av-dist-cnt { color:#64748b; font-size:11px; width:20px; flex-shrink:0; }

/* ── FILTRES ── */
.av-filters {
  display:flex; gap:10px; flex-wrap:wrap; margin-top:40px;
}
.av-filter-btn {
  padding:7px 16px; border-radius:100px; border:1px solid rgba(255,255,255,.14);
  background:rgba(255,255,255,.05); color:#94a3b8;
  font-size:12px; font-weight:700; cursor:pointer;
  transition:.2s;
}
.av-filter-btn:hover { border-color:rgba(245,166,35,.4); color:#f5a623; }
.av-filter-btn.active { background:rgba(245,166,35,.12); border-color:#f5a623; color:#f5a623; }

/* ── GRILLE AVIS ── */
.av-grid {
  display:grid;
  grid-template-columns:repeat(auto-fill,minmax(310px,1fr));
  gap:20px;
  margin-top:28px;
}
.av-card {
  background:linear-gradient(160deg,rgba(255,255,255,.065) 0%,rgba(255,255,255,.025) 100%);
  border:1px solid rgba(255,255,255,.09);
  border-radius:22px; padding:28px;
  display:flex; flex-direction:column; gap:14px;
  transition:.25s ease;
}
.av-card:hover { border-color:rgba(245,166,35,.30); box-shadow:0 12px 40px rgba(0,0,0,.4); transform:translateY(-2px); }
.av-card[hidden] { display:none; }

.av-card-header { display:flex; align-items:center; justify-content:space-between; }
.av-stars-sm { color:#f5a623; font-size:13px; letter-spacing:1.5px; }
.av-card-date { font-size:11px; color:#475569; }

.av-card-quote {
  font-style:italic; font-size:.96rem; line-height:1.78; color:#cbd5e1;
  position:relative; padding-left:16px;
  border-left:2px solid rgba(245,166,35,.25);
  margin:0;
}

.av-card-foot { display:flex; align-items:center; gap:12px; margin-top:auto; padding-top:6px; border-top:1px solid rgba(255,255,255,.06); }
.av-avatar {
  width:42px; height:42px; border-radius:50%;
  background:linear-gradient(135deg,#1a3a60,#0c2e4e);
  border:2px solid rgba(245,166,35,.35);
  display:flex; align-items:center; justify-content:center;
  font-weight:900; font-size:1rem; color:#f5a623;
  flex-shrink:0;
}
.av-name { font-weight:800; font-size:.92rem; color:#fff; }
.av-meta { font-size:11px; color:#64748b; margin-top:2px; }

/* Secteur badge */
.av-badge {
  display:inline-block; font-size:10px; font-weight:700; padding:3px 9px;
  background:rgba(245,166,35,.10); color:#f5a623;
  border-radius:100px; margin-top:4px;
}

/* ── EMPTY STATE ── */
.av-empty {
  text-align:center; padding:80px 40px;
  border:1px dashed rgba(245,166,35,.20); border-radius:24px; margin-top:28px;
}
.av-empty-icon { font-size:3.5rem; margin-bottom:16px; }
.av-empty h2 { margin:0 0 10px; font-size:1.5rem; font-weight:900; color:#fff; }
.av-empty p  { color:#94a3b8; max-width:480px; margin:0 auto 28px; line-height:1.7; }
.btn-primary {
  display:inline-block; padding:14px 26px;
  background:linear-gradient(135deg,#f5a623,#f97316);
  color:#020617; font-weight:900; font-size:.92rem;
  border-radius:12px; text-decoration:none; transition:.2s;
}
.btn-primary:hover { filter:brightness(1.1); transform:translateY(-1px); }

/* ── NO RESULTS (filtre) ── */
.av-no-result {
  grid-column:1/-1; text-align:center; padding:48px 24px;
  color:#64748b; font-size:.95rem;
}

/* ── FORMULAIRE ── */
.av-form-section {
  margin-top:80px;
  display:grid; grid-template-columns:1fr 1.4fr; gap:40px; align-items:start;
}
.av-form-pitch h2 { margin:0 0 14px; font-size:2rem; font-weight:900; line-height:1.15; }
.av-form-pitch p  { color:#94a3b8; font-size:.95rem; line-height:1.75; margin:0 0 20px; }
.av-pitch-item {
  display:flex; align-items:flex-start; gap:12px;
  font-size:.88rem; color:#94a3b8; line-height:1.6; margin-bottom:12px;
}
.av-pitch-icon { font-size:1.2rem; flex-shrink:0; margin-top:1px; }

.av-form-card {
  background:linear-gradient(160deg,rgba(11,44,78,.5),rgba(3,11,24,.95));
  border:1px solid rgba(255,255,255,.10);
  border-radius:24px; padding:40px 40px;
  box-shadow:0 24px 60px rgba(0,0,0,.40);
}
.av-form-card h3 { margin:0 0 6px; font-size:1.3rem; font-weight:900; }
.av-form-card > p { margin:0 0 28px; color:#64748b; font-size:.88rem; }

.av-field { display:flex; flex-direction:column; gap:6px; }
.av-field label { font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.06em; color:#64748b; }
.av-input {
  padding:12px 16px; border-radius:12px;
  border:1px solid rgba(255,255,255,.12);
  background:rgba(255,255,255,.05);
  color:#fff; font-size:14px; font-family:inherit;
  outline:none; transition:.2s;
}
.av-input:focus { border-color:#f5a623; background:rgba(245,166,35,.05); }
.av-input::placeholder { color:#334155; }
.av-form-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
textarea.av-input { resize:vertical; min-height:110px; }

.av-star-pick { display:flex; gap:6px; margin-top:4px; cursor:pointer; }
.av-star-pick span { font-size:26px; color:#1e3a5f; transition:.12s; }
.av-star-pick span.lit { color:#f5a623; }

.av-btn-submit {
  width:100%;
  background:linear-gradient(135deg,#f5a623,#f97316);
  color:#020617; font-weight:900; font-size:.95rem;
  border:0; border-radius:14px; padding:14px 24px;
  cursor:pointer; transition:.2s;
}
.av-btn-submit:hover { filter:brightness(1.08); transform:translateY(-1px); }

.av-alert {
  padding:13px 16px; border-radius:11px;
  font-size:13px; margin-bottom:18px;
}
.av-alert.ok  { background:rgba(34,197,94,.10); color:#86efac; border:1px solid rgba(34,197,94,.25); }
.av-alert.err { background:rgba(232,36,44,.10); color:#fca5a5; border:1px solid rgba(232,36,44,.25); }

/* ── CTA WHATSAPP ── */
.av-whatsapp {
  margin-top:40px; padding:24px 28px; border-radius:18px;
  background:rgba(37,211,102,.06); border:1px solid rgba(37,211,102,.15);
  display:flex; align-items:center; gap:16px; flex-wrap:wrap;
}
.av-whatsapp-text { flex:1; }
.av-whatsapp-text strong { font-size:.95rem; color:#fff; display:block; }
.av-whatsapp-text span { font-size:.83rem; color:#64748b; }
.av-wa-btn {
  background:#25d366; color:#022c22; font-weight:900; padding:11px 20px;
  border-radius:11px; text-decoration:none; font-size:.85rem; white-space:nowrap;
  transition:.2s;
}
.av-wa-btn:hover { filter:brightness(1.08); }

/* ── SECTION TITRE ── */
.av-section-title {
  display:flex; align-items:center; gap:14px; margin:56px 0 0;
}
.av-section-title h2 { margin:0; font-size:1.6rem; font-weight:900; }
.av-section-title span { flex:1; height:1px; background:rgba(255,255,255,.07); }

/* ── RESPONSIVE ── */
@media(max-width:900px){
  .av-form-section { grid-template-columns:1fr; }
}
@media(max-width:760px){
  .av-hero { padding:36px 24px; }
  .av-hero h1 { font-size:2rem; }
  .av-hero-inner { flex-direction:column; gap:28px; }
  .av-rating-block { width:100%; padding:22px 28px; flex-direction:row; text-align:left; gap:20px; }
  .av-form-card { padding:28px 22px; }
  .av-form-grid { grid-template-columns:1fr; }
}
</style>

<main class="av-wrap">

  <!-- HERO -->
  <section class="av-hero">
    <div class="av-hero-inner">
      <div class="av-hero-text">
        <div class="av-hero-eyebrow">⭐ Témoignages authentiques</div>
        <h1>Ce que disent<br>nos participants</h1>
        <p>Plus de <strong style="color:#f5a623">1 000 professionnels</strong> ont suivi nos formations. Chaque témoignage publié ici provient d'un participant réel, vérifié par notre équipe avant publication.</p>

        <?php if ($totalAvis > 0): ?>
        <!-- Distribution des notes -->
        <div class="av-dist" style="margin-top:24px;max-width:320px;">
          <?php for($n=5;$n>=1;$n--):
            $pct = $totalAvis ? round($noteDist[$n] / $totalAvis * 100) : 0;
          ?>
          <div class="av-dist-row">
            <div class="av-dist-lbl"><?= $n ?></div>
            <div class="av-dist-bar-wrap">
              <div class="av-dist-bar" style="width:<?= $pct ?>%"></div>
            </div>
            <div class="av-dist-cnt"><?= $noteDist[$n] ?></div>
          </div>
          <?php endfor; ?>
        </div>
        <?php endif; ?>
      </div>

      <?php if ($totalAvis > 0): ?>
      <div class="av-rating-block">
        <div class="av-rating-big"><?= $avgNote ?></div>
        <div class="av-rating-stars"><?= str_repeat('★', (int)round($avgNote)) . str_repeat('☆', 5 - (int)round($avgNote)) ?></div>
        <div class="av-rating-sub"><?= $totalAvis ?> avis publié<?= $totalAvis > 1 ? 's' : '' ?></div>
      </div>
      <?php else: ?>
      <div class="av-rating-block">
        <div style="font-size:2.5rem">✍️</div>
        <div style="font-size:.82rem;color:#64748b;margin-top:8px;text-align:center;max-width:140px;line-height:1.5;">Soyez parmi les premiers à témoigner</div>
      </div>
      <?php endif; ?>
    </div>
  </section>

  <!-- SECTION AVIS -->
  <div class="av-section-title">
    <h2><?= $totalAvis > 0 ? 'Témoignages publiés' : 'Aucun témoignage pour l\'instant' ?></h2>
    <span></span>
    <?php if ($totalAvis > 0): ?>
    <a href="#laisser-avis" class="btn-primary" style="font-size:.82rem;padding:10px 18px;">✍️ Témoigner</a>
    <?php endif; ?>
  </div>

  <?php if (empty($avis)): ?>
    <div class="av-empty">
      <div class="av-empty-icon">🌟</div>
      <h2>Soyez parmi les premiers à témoigner</h2>
      <p>Vous avez suivi une formation IBIG EDUFORM ? Votre retour d'expérience authentique aide d'autres professionnels à choisir la bonne formation.</p>
      <a href="#laisser-avis" class="btn-primary">✍️ Partager mon expérience</a>
    </div>

  <?php else: ?>

    <!-- Filtres par secteur -->
    <?php if (count($secteurs) > 1): ?>
    <div class="av-filters" id="avFilters">
      <button class="av-filter-btn active" data-filter="all">Tous (<?= $totalAvis ?>)</button>
      <?php
        $noteGroups = ['5'=>'5 étoiles', '4'=>'4 étoiles', '3'=>'3 étoiles'];
        foreach($noteGroups as $nVal => $nLabel):
          if ($noteDist[(int)$nVal] > 0): ?>
      <button class="av-filter-btn" data-filter="note<?= $nVal ?>">⭐ <?= $nLabel ?> (<?= $noteDist[(int)$nVal] ?>)</button>
          <?php endif; endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Grille -->
    <div class="av-grid" id="avGrid">
      <?php foreach($avis as $a):
        $initiale = mb_strtoupper(mb_substr((string)$a['nom'], 0, 1, 'UTF-8'), 'UTF-8');
        $note     = max(1, min(5, (int)($a['note'] ?? 5)));
        $stars    = str_repeat('★', $note) . str_repeat('☆', 5-$note);
        $loc      = array_filter([$a['ville'], $a['pays']]); $loc = implode(', ', $loc);
        $dateStr  = '';
        if (!empty($a['created_at'])) {
          try { $dateStr = (new DateTime((string)$a['created_at']))->format('M Y'); } catch(Throwable $e){}
        }
      ?>
        <article class="av-card" data-note="<?= $note ?>">
          <div class="av-card-header">
            <div class="av-stars-sm"><?= $stars ?></div>
            <?php if($dateStr): ?><div class="av-card-date"><?= e($dateStr) ?></div><?php endif; ?>
          </div>
          <p class="av-card-quote"><?= e((string)$a['texte']) ?></p>
          <div class="av-card-foot">
            <div class="av-avatar"><?= $initiale ?></div>
            <div>
              <div class="av-name"><?= e((string)$a['nom']) ?></div>
              <?php if(!empty($a['secteur'])): ?>
              <div class="av-badge"><?= e((string)$a['secteur']) ?></div>
              <?php endif; ?>
              <?php if($loc): ?><div class="av-meta"><?= e($loc) ?></div><?php endif; ?>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
      <div class="av-no-result" id="avNoResult" style="display:none">
        Aucun avis dans cette catégorie pour le moment.
      </div>
    </div>

  <?php endif; ?>

  <!-- FORMULAIRE -->
  <div class="av-form-section" id="laisser-avis">

    <!-- Pitch gauche -->
    <div class="av-form-pitch">
      <h2>Votre expérience compte</h2>
      <p>Un témoignage sincère aide vos pairs à choisir la bonne formation. Chaque avis est lu et vérifié par notre équipe avant publication.</p>
      <div class="av-pitch-item">
        <div class="av-pitch-icon">🔒</div>
        <div>Vos coordonnées restent confidentielles. Seuls votre prénom, secteur et ville peuvent apparaître.</div>
      </div>
      <div class="av-pitch-item">
        <div class="av-pitch-icon">✅</div>
        <div>Modération humaine avant publication — pas de bot, pas de faux avis.</div>
      </div>
      <div class="av-pitch-item">
        <div class="av-pitch-icon">⏱️</div>
        <div>Délai de publication habituel : 24 à 48 heures ouvrées.</div>
      </div>

      <!-- WhatsApp -->
      <div class="av-whatsapp">
        <div style="font-size:1.6rem">💬</div>
        <div class="av-whatsapp-text">
          <strong>Préférez parler directement ?</strong>
          <span>Notre équipe est joignable du lundi au vendredi.</span>
        </div>
        <a href="https://wa.me/2250778882592?text=<?= rawurlencode('Bonjour IBIG EDUFORM, je voudrais partager mon retour sur la formation que j\'ai suivie.') ?>" class="av-wa-btn">📱 WhatsApp</a>
      </div>
    </div>

    <!-- Formulaire -->
    <div class="av-form-card">
      <h3>Partager mon expérience</h3>
      <p>Publié après validation · Aucun démarchage</p>

      <?php if ($avisMsg): ?>
        <div class="av-alert <?= $avisOk ? 'ok' : 'err' ?>">
          <?= $avisOk ? '✅ ' : '⚠️ ' ?><?= e($avisMsg) ?>
        </div>
      <?php endif; ?>

      <form method="post" style="display:flex;flex-direction:column;gap:18px">
        <?= csrf_field(); ?>
        <input type="text" name="website" value="" tabindex="-1" autocomplete="off" aria-hidden="true" style="position:absolute;left:-9999px">

        <div class="av-form-grid">
          <div class="av-field">
            <label>Votre nom *</label>
            <input class="av-input" name="nom" required maxlength="80" placeholder="Marie Kouassi" value="<?= e($avisOld['nom']) ?>">
          </div>
          <div class="av-field">
            <label>Ville / Pays</label>
            <input class="av-input" name="ville" maxlength="80" placeholder="Abidjan, Côte d'Ivoire" value="<?= e($avisOld['ville']) ?>">
          </div>
        </div>

        <div class="av-field">
          <label>Secteur / Fonction</label>
          <input class="av-input" name="secteur" maxlength="90" placeholder="Ex : Responsable RH, Comptable, DAF…" value="<?= e($avisOld['secteur']) ?>">
        </div>

        <div class="av-field">
          <label>Note globale</label>
          <div class="av-star-pick" id="starPick">
            <?php for($s=1;$s<=5;$s++): ?>
              <span data-val="<?= $s ?>" class="<?= $avisOld['note'] >= $s ? 'lit' : '' ?>">★</span>
            <?php endfor; ?>
          </div>
          <input type="hidden" name="note" id="noteInput" value="<?= (int)$avisOld['note'] ?: 5 ?>">
        </div>

        <div class="av-field">
          <label>Votre témoignage * <span style="font-weight:400;color:#334155">(min. 15 caractères)</span></label>
          <textarea class="av-input" name="texte" required rows="5" maxlength="600"
            placeholder="Décrivez votre expérience : la qualité de la formation, les formateurs, ce que vous avez appris et comment cela a impacté votre activité…"><?= e($avisOld['texte']) ?></textarea>
        </div>

        <button type="submit" name="avis_submit" value="1" class="av-btn-submit">✉️ Envoyer mon témoignage</button>
      </form>
    </div>
  </div>

</main>

<script>
/* Étoiles interactives */
(function(){
  var pick = document.getElementById('starPick');
  var inp  = document.getElementById('noteInput');
  if (!pick) return;
  var stars = pick.querySelectorAll('span');
  function setNote(n){
    inp.value = n;
    stars.forEach(function(s){ s.classList.toggle('lit', parseInt(s.dataset.val) <= n); });
  }
  stars.forEach(function(s){
    s.addEventListener('click', function(){ setNote(parseInt(s.dataset.val)); });
    s.addEventListener('mouseenter', function(){
      stars.forEach(function(ss){ ss.classList.toggle('lit', parseInt(ss.dataset.val) <= parseInt(s.dataset.val)); });
    });
  });
  pick.addEventListener('mouseleave', function(){ setNote(parseInt(inp.value)); });
  setNote(parseInt(inp.value) || 5);
})();

/* Filtres avis */
(function(){
  var btns  = document.querySelectorAll('#avFilters .av-filter-btn');
  var cards = document.querySelectorAll('#avGrid .av-card');
  var noRes = document.getElementById('avNoResult');
  if (!btns.length) return;
  btns.forEach(function(btn){
    btn.addEventListener('click', function(){
      btns.forEach(function(b){ b.classList.remove('active'); });
      btn.classList.add('active');
      var f = btn.dataset.filter;
      var visible = 0;
      cards.forEach(function(c){
        var show = f === 'all' || ('note' + c.dataset.note) === f;
        c.hidden = !show;
        if (show) visible++;
      });
      if (noRes) noRes.style.display = visible === 0 ? 'block' : 'none';
    });
  });
})();
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>
