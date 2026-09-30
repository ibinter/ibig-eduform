<?php
declare(strict_types=1);

/**
 * ============================================================
 * IBIG EDUFORM — CATALOGUE SAMEDI PRO (page premium)
 * - Affiche UNIQUEMENT les formations Samedi Pro à venir
 * - Présentiel OU en ligne (les samedis)
 * - Filtre par mois + recherche
 * - Prix en ligne + présentiel, offre early-bird, places réelles
 * ============================================================
 */

require_once __DIR__ . '/core/bootstrap.php';   // config + Database + helpers
require_once __DIR__ . '/core/promo.php';

$pdo = Database::connect();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

/* ---------- Entrées (filtres) ---------- */
$moisSel = isset($_GET['mois']) && is_numeric($_GET['mois']) ? (int)$_GET['mois'] : 0;
$q       = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
$q       = preg_replace('/\s+/u', ' ', $q);

/* ---------- Requête Samedi Pro (à venir) ---------- */
$sql = "
  SELECT id, slug, titre, domaine, description, duree, mode,
         date_debut, tarif_en_ligne, tarif_presentiel, tarif_hybride,
         capacite, inscriptions_actuelles, is_samedi_pro
  FROM formations
  WHERE statut = 'active'
    AND is_samedi_pro = 1
    AND date_debut IS NOT NULL
    AND date_debut >= CURDATE()
";
$params = [];
if ($moisSel >= 1 && $moisSel <= 12) {
    $sql .= " AND MONTH(date_debut) = :mois";
    $params['mois'] = $moisSel;
}
if ($q !== '') {
    $sql .= " AND (titre LIKE :q1 OR domaine LIKE :q2 OR description LIKE :q3)";
    $params['q1'] = '%' . $q . '%';
    $params['q2'] = '%' . $q . '%';
    $params['q3'] = '%' . $q . '%';
}
$sql .= " ORDER BY date_debut ASC LIMIT 100";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$sessions = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

/* ---------- Places déjà payées (tolérant) ---------- */
$paid = [];
try {
    $rows = $pdo->query("SELECT formation_id, COUNT(*) c FROM paiements_inscription WHERE statut='paye' GROUP BY formation_id")
                ->fetchAll(PDO::FETCH_KEY_PAIR);
    if (is_array($rows)) { $paid = $rows; }
} catch (Throwable $e) { $paid = []; }

/* ---------- Outils ---------- */
$moisDispo = [1=>'Janvier',2=>'Février',3=>'Mars',4=>'Avril',5=>'Mai',6=>'Juin',
              7=>'Juillet',8=>'Août',9=>'Septembre',10=>'Octobre',11=>'Novembre',12=>'Décembre'];
$frMonths  = [1=>'janvier',2=>'février',3=>'mars',4=>'avril',5=>'mai',6=>'juin',
              7=>'juillet',8=>'août',9=>'septembre',10=>'octobre',11=>'novembre',12=>'décembre'];
$fmtFr = function (?string $d) use ($frMonths): string {
    if (!$d) return 'Date à confirmer';
    $ts = strtotime($d);
    if (!$ts) return 'Date à confirmer';
    return date('j', $ts) . ' ' . ($frMonths[(int)date('n', $ts)] ?? '') . ' ' . date('Y', $ts);
};
$waNum  = function_exists('whatsapp_admin_phone') ? whatsapp_admin_phone() : '2250778882592';
$waLink = 'https://wa.me/' . $waNum . '?text=' . rawurlencode("Bonjour IBIG EDUFORM, je souhaite des informations sur les sessions SAMEDI PRO.");
$baseUrl = strtok($_SERVER['REQUEST_URI'] ?? '/formations-samedi-pro.php', '?');
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>Catalogue SAMEDI PRO – Formations courtes, présentiel ou en ligne | IBIG EDUFORM</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Toutes les formations SAMEDI PRO d'IBIG EDUFORM : courtes, 100% pratiques, en présentiel ou en ligne, les samedis. Filtrez par mois, comparez les tarifs et réservez votre place.">
  <meta name="robots" content="index,follow">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

  <style>
    :root{
      --bg:#f6f8fe;--bg2:#eef2fc;--card:#fff;--ink:#0e1530;--muted:#5b647c;--line:rgba(14,21,48,.10);
      --brand:#1f3fe0;--brand-2:#4f6bff;--accent:#e8242c;--accent-2:#ff5763;--ok:#0fae6e;--gold:#f59e0b;
      --shadow:0 24px 60px rgba(14,21,48,.12);--shadow-sm:0 10px 28px rgba(14,21,48,.08);
      --r:20px;--r2:16px;--r3:12px;--max:1160px;
      --font:"Plus Jakarta Sans","Inter",system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;
    }
    *{box-sizing:border-box}
    html{scroll-behavior:smooth}
    body{margin:0;color:var(--ink);font-family:var(--font);
      background:
        radial-gradient(1100px 560px at 8% -5%, rgba(31,63,224,.12), transparent 60%),
        radial-gradient(1000px 520px at 100% 0%, rgba(232,36,44,.08), transparent 58%),
        linear-gradient(180deg,var(--bg2),var(--bg));
      -webkit-font-smoothing:antialiased}
    a{text-decoration:none;color:inherit}
    .wrap{max-width:var(--max);margin:auto;padding:0 20px}
    .eyebrow{display:inline-flex;align-items:center;gap:8px;font-size:12px;font-weight:800;letter-spacing:.4px;
      text-transform:uppercase;color:var(--brand);border:1px solid rgba(31,63,224,.22);background:rgba(31,63,224,.06);
      padding:7px 14px;border-radius:999px}
    .eyebrow .dot{width:8px;height:8px;border-radius:50%;background:linear-gradient(135deg,var(--brand),var(--accent))}

    header.site{position:sticky;top:0;z-index:30;background:rgba(246,248,254,.82);backdrop-filter:blur(12px);border-bottom:1px solid var(--line)}
    .topbar{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:12px 0}
    .brand{display:flex;align-items:center;gap:11px}
    .brand img{width:46px;height:auto;display:block}
    .brand b{display:block;font-size:15px;font-weight:800;letter-spacing:-.2px}
    .brand small{display:block;font-size:11.5px;color:var(--muted);font-weight:600}
    .nav{display:flex;align-items:center;gap:6px}
    .nav a{font-size:13.5px;font-weight:600;color:var(--muted);padding:9px 13px;border-radius:999px;transition:.15s}
    .nav a:hover{background:rgba(31,63,224,.08);color:var(--ink)}
    .nav a.cta{background:linear-gradient(135deg,var(--brand),var(--brand-2));color:#fff;font-weight:800;box-shadow:0 12px 26px rgba(31,63,224,.30)}
    .navtoggle{display:none;border:1px solid var(--line);background:#fff;border-radius:10px;width:42px;height:40px;font-size:18px;color:var(--ink);cursor:pointer}

    .btn{display:inline-flex;align-items:center;justify-content:center;gap:9px;padding:13px 20px;border-radius:13px;
      font-size:14px;font-weight:800;border:1px solid var(--line);background:#fff;color:var(--ink);transition:.16s;cursor:pointer}
    .btn:hover{transform:translateY(-2px);box-shadow:var(--shadow-sm)}
    .btn.primary{background:linear-gradient(135deg,var(--brand),var(--brand-2));color:#fff;border-color:transparent;box-shadow:0 16px 34px rgba(31,63,224,.32)}
    .btn.ghost{background:rgba(31,63,224,.06);border-color:rgba(31,63,224,.22);color:var(--brand)}
    .btn.wa{background:#fff;border-color:rgba(37,211,102,.4);color:#0c7a43}
    .btn.lg{padding:15px 26px;font-size:15px}

    /* HERO */
    .hero{padding:50px 0 8px;text-align:center}
    .hero h1{margin:16px auto 12px;font-size:clamp(28px,4vw,44px);line-height:1.1;letter-spacing:-1px;font-weight:800;max-width:18ch}
    .hero h1 .grad{background:linear-gradient(120deg,var(--brand),var(--accent));-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent}
    .hero p.lead{margin:0 auto;max-width:62ch;font-size:16px;line-height:1.7;color:var(--muted)}

    /* FILTER BAR */
    .filterbar{margin:26px auto 6px;max-width:920px}
    .filterbar form{display:flex;flex-wrap:wrap;gap:10px;justify-content:center;align-items:center;
      background:var(--card);border:1px solid var(--line);box-shadow:var(--shadow-sm);border-radius:var(--r);padding:14px}
    .field{display:flex;align-items:center;gap:8px;flex:1;min-width:200px;background:var(--bg);border:1px solid var(--line);
      border-radius:12px;padding:0 12px}
    .field i{color:var(--brand)}
    .field select,.field input{border:0;outline:0;background:transparent;padding:13px 4px;font-size:14px;font-weight:600;
      color:var(--ink);width:100%;font-family:inherit}
    .filterbar .btn{flex:0 0 auto}
    .count{margin:14px 0 0;text-align:center;color:var(--muted);font-weight:700;font-size:13.5px}
    .count b{color:var(--brand)}

    /* GRID + CARDS (identique à la page programme) */
    section{padding:26px 0 70px}
    .sgrid{display:grid;grid-template-columns:repeat(auto-fill,minmax(330px,1fr));gap:20px}
    .scard{display:flex;flex-direction:column;background:var(--card);border:1px solid var(--line);border-radius:var(--r);box-shadow:var(--shadow-sm);overflow:hidden;transition:.2s}
    .scard:hover{transform:translateY(-5px);box-shadow:var(--shadow)}
    .scard .top{padding:18px 18px 0;display:flex;flex-wrap:wrap;gap:8px;align-items:center}
    .chip{display:inline-flex;align-items:center;gap:6px;font-size:11.5px;font-weight:800;padding:6px 11px;border-radius:999px;border:1px solid var(--line);background:rgba(14,21,48,.04);color:var(--muted)}
    .chip.pro{background:linear-gradient(135deg,rgba(31,63,224,.12),rgba(79,107,255,.10));border-color:rgba(31,63,224,.25);color:var(--brand)}
    .chip.soon{background:rgba(245,158,11,.12);border-color:rgba(245,158,11,.3);color:#a86a06}
    .scard h3{margin:14px 18px 0;font-size:17.5px;font-weight:800;line-height:1.25;letter-spacing:-.3px}
    .scard .desc{margin:8px 18px 0;color:var(--muted);font-size:13.5px;line-height:1.6;
      display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
    .scard .meta{margin:12px 18px 0;display:grid;gap:7px;font-size:13px;color:var(--muted);font-weight:600}
    .scard .meta div{display:flex;align-items:center;gap:9px}
    .scard .meta i{color:var(--brand);width:16px;text-align:center}
    .scard .price{margin:14px 18px 0;padding-top:14px;border-top:1px dashed var(--line);display:flex;justify-content:space-between;align-items:flex-end;gap:10px}
    .scard .price .amt{font-size:22px;font-weight:800;letter-spacing:-.4px}
    .scard .price .amt s{font-size:13px;color:var(--muted);font-weight:600;margin-right:6px}
    .scard .price .amt small{display:block;font-size:11.5px;color:var(--muted);font-weight:700;margin-top:1px}
    .eb{display:inline-flex;align-items:center;gap:6px;font-size:11.5px;font-weight:800;color:#a86a06;background:rgba(245,158,11,.12);border:1px solid rgba(245,158,11,.3);padding:5px 9px;border-radius:999px;white-space:nowrap}
    .scard .acts{margin:16px 18px 18px;display:flex;gap:10px}
    .scard .acts a{flex:1;text-align:center;padding:11px;border-radius:12px;font-size:13px;font-weight:800;transition:.15s}
    .scard .acts a.view{background:rgba(31,63,224,.07);border:1px solid rgba(31,63,224,.22);color:var(--brand)}
    .scard .acts a.book{background:linear-gradient(135deg,var(--accent),var(--accent-2));color:#fff;box-shadow:0 12px 26px rgba(232,36,44,.26)}
    .scard .acts a:hover{transform:translateY(-1px)}
    .empty{grid-column:1/-1;text-align:center;padding:44px 20px;border:1px dashed var(--line);border-radius:var(--r);background:#fff;color:var(--muted)}

    @media(max-width:760px){
      .nav{display:none}
      .nav.open{display:flex;position:absolute;top:64px;left:0;right:0;flex-direction:column;align-items:stretch;
        background:rgba(246,248,254,.98);backdrop-filter:blur(12px);border-bottom:1px solid var(--line);padding:12px 20px;gap:8px}
      .navtoggle{display:inline-block}
      .field{min-width:0;flex:1 1 100%}
      .filterbar .btn{flex:1 1 100%}
    }
    @media(max-width:430px){ .scard .acts{flex-direction:column} }
  </style>
</head>
<body>

<header class="site">
  <div class="wrap topbar">
    <a class="brand" href="https://ibig-eduform.com">
      <img src="/assets/images/logo.png" alt="IBIG EDUFORM">
      <span><b>IBIG EDUFORM</b><small>Catalogue SAMEDI PRO</small></span>
    </a>
    <button class="navtoggle" aria-label="Menu" onclick="document.getElementById('nav').classList.toggle('open')">
      <i class="fa-solid fa-bars"></i>
    </button>
    <nav class="nav" id="nav">
      <a href="https://ibig-eduform.com">Accueil</a>
      <a href="/samedi-pro.php">Le concept</a>
      <a href="/formations.php">Tous les programmes</a>
      <a href="<?= e($waLink) ?>" target="_blank" rel="noopener">Nous écrire</a>
      <a href="/preinscription-samedi-pro.php" class="cta">Préinscription</a>
    </nav>
  </div>
</header>

<main>

<!-- HERO + FILTRES -->
<section class="hero" style="padding-bottom:0">
  <div class="wrap">
    <span class="eyebrow"><span class="dot"></span> Présentiel ou en ligne · Les samedis</span>
    <h1>Catalogue <span class="grad">SAMEDI PRO</span></h1>
    <p class="lead">
      Des formations courtes et 100&nbsp;% pratiques, en présentiel à Abidjan ou en ligne.
      Choisissez votre thème, comparez les dates et réservez votre place.
    </p>

    <div class="filterbar">
      <form method="get" action="<?= e($baseUrl) ?>">
        <div class="field">
          <i class="fa-solid fa-calendar"></i>
          <select name="mois" onchange="this.form.submit()">
            <option value="0">Tous les mois</option>
            <?php foreach ($moisDispo as $num => $label): ?>
              <option value="<?= (int)$num ?>" <?= $num === $moisSel ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <i class="fa-solid fa-magnifying-glass"></i>
          <input type="text" name="q" value="<?= e($q) ?>" placeholder="Ex : Excel, Paie, KPI, Marketing…">
        </div>
        <button type="submit" class="btn primary"><i class="fa-solid fa-filter"></i> Filtrer</button>
        <?php if ($moisSel || $q !== ''): ?>
          <a class="btn ghost" href="<?= e($baseUrl) ?>"><i class="fa-solid fa-rotate-left"></i> Réinitialiser</a>
        <?php endif; ?>
      </form>
      <p class="count"><i class="fa-solid fa-layer-group"></i> <b><?= count($sessions) ?></b> session(s) à venir</p>
    </div>
  </div>
</section>

<!-- GRILLE -->
<section>
  <div class="wrap">
    <div class="sgrid">
      <?php if (!$sessions): ?>
        <div class="empty">
          <p style="font-size:16px;font-weight:700;margin:0 0 8px">Aucune session ne correspond à votre recherche.</p>
          <p style="margin:0 0 16px">Modifiez le filtre, ou contactez-nous pour connaître les prochaines dates.</p>
          <a class="btn wa" href="<?= e($waLink) ?>" target="_blank" rel="noopener"><i class="fa-brands fa-whatsapp"></i> Nous écrire</a>
        </div>
      <?php else: foreach ($sessions as $f):
        $fid    = (int)($f['id'] ?? 0);
        $slug   = (string)($f['slug'] ?? '');
        $titre  = (string)($f['titre'] ?? '');
        $dom    = (string)($f['domaine'] ?? '');
        $desc   = (string)($f['description'] ?? '');
        $duree  = (string)($f['duree'] ?? '7H');
        $enligne= (int)($f['tarif_en_ligne'] ?? 0);
        $presen = (int)($f['tarif_presentiel'] ?? 0);
        $ref    = (int)($f['tarif_hybride'] ?? 0); // prix de référence barré si gratuit
        $left   = function_exists('places_disponibles') ? (int)places_disponibles($f) : 0;
        $promo  = promo_earlybird($f);
        $hasEb  = !empty($promo['eligible']) && ($enligne > 0 || $presen > 0);
        $url    = $slug !== '' ? '/formation/' . rawurlencode($slug) : '/formation.php?id=' . $fid;
      ?>
        <article class="scard">
          <div class="top">
            <span class="chip pro"><i class="fa-solid fa-star"></i> Samedi Pro</span>
            <?php if ($dom !== ''): ?><span class="chip"><i class="fa-solid fa-folder-open"></i> <?= e($dom) ?></span><?php endif; ?>
            <?php if ($left > 0 && $left <= 6): ?><span class="chip soon"><i class="fa-solid fa-fire"></i> Plus que <?= $left ?> places</span><?php endif; ?>
          </div>

          <h3><?= e($titre) ?></h3>
          <?php if ($desc !== ''): ?><p class="desc"><?= e($desc) ?></p><?php endif; ?>

          <div class="meta">
            <div><i class="fa-solid fa-calendar-day"></i> <?= e($fmtFr($f['date_debut'] ?? null)) ?></div>
            <div><i class="fa-solid fa-clock"></i> <?= e($duree) ?> · 09h00 – 16h00</div>
            <div><i class="fa-solid fa-location-dot"></i> Présentiel (Abidjan) ou en ligne</div>
            <?php if ($left > 0): ?>
              <div><i class="fa-solid fa-users"></i> <?= $left ?> place<?= $left > 1 ? 's' : '' ?> disponible<?= $left > 1 ? 's' : '' ?></div>
            <?php else: ?>
              <div><i class="fa-solid fa-circle-info"></i> Liste d'attente — contactez-nous</div>
            <?php endif; ?>
          </div>

          <div class="price">
            <?php if ($enligne <= 0 && $presen <= 0): ?>
              <div class="amt" style="color:#0fae6e"><?php if ($ref>0): ?><s style="color:#94a3b8;font-weight:600"><?= number_format($ref,0,',',' ') ?> FCFA</s> <?php endif; ?>Gratuit<small>Formation offerte</small></div>
              <span class="eb" style="color:#0c7a43;background:rgba(15,174,110,.12);border-color:rgba(15,174,110,.35)"><i class="fa-solid fa-gift"></i> Offert</span>
            <?php else: ?>
            <div class="amt">
              <?php if ($hasEb && $enligne > 0): ?><s><?= number_format($enligne + PROMO_REMISE_EN_LIGNE,0,',',' ') ?></s><?= number_format($enligne,0,',',' ') ?>
              <?php else: ?><?= number_format($enligne ?: $presen,0,',',' ') ?><?php endif; ?>
              <small>FCFA · en ligne<?php if ($presen>0): ?> · <?php if ($hasEb): ?><s><?= number_format($presen + PROMO_REMISE_PRESENTIEL,0,',',' ') ?></s> <?php endif; ?><?= number_format($presen,0,',',' ') ?> présentiel<?php endif; ?></small>
            </div>
            <?php if ($hasEb): ?><span class="eb"><i class="fa-solid fa-dove"></i> <?= e($promo['label']) ?></span><?php endif; ?>
            <?php endif; ?>
          </div>

          <div class="acts">
            <a class="view" href="<?= e($url) ?>"><i class="fa-solid fa-circle-info"></i> Programme</a>
            <a class="book" href="/preinscription.php?formation_id=<?= $fid ?>"><i class="fa-solid fa-bolt"></i> Réserver</a>
          </div>
        </article>
      <?php endforeach; endif; ?>
    </div>
  </div>
</section>

</main>

<?php include __DIR__ . '/partials/footer.php'; ?>

<script>
  document.querySelectorAll('#nav a').forEach(function(a){
    a.addEventListener('click', function(){ document.getElementById('nav').classList.remove('open'); });
  });
</script>
</body>
</html>
