<?php
require_once __DIR__ . '/core/config.php';
$pageSlug = 'samedi-pro';
require __DIR__ . '/partials/cms_page.php'; // rend la version base si publiée, sinon continue

/* ----------------------------------------------------------
   Bootstrap (DB + helpers) — la page CMS n'a pas pris la main.
---------------------------------------------------------- */
require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/core/promo.php';

$pdo = Database::connect();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

/* ----------------------------------------------------------
   Prochaines sessions SAMEDI PRO (réelles, à venir)
---------------------------------------------------------- */
$sessions = [];
try {
    $st = $pdo->query("
        SELECT id, slug, titre, domaine, description, duree, mode,
               date_debut, tarif_en_ligne, tarif_presentiel, tarif_hybride,
               capacite, inscriptions_actuelles, is_samedi_pro
        FROM formations
        WHERE statut = 'active'
          AND is_samedi_pro = 1
          AND date_debut IS NOT NULL
          AND date_debut >= CURDATE()
        ORDER BY date_debut ASC
        LIMIT 24
    ");
    $sessions = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) { $sessions = []; }

/* Places déjà payées (pour calculer le restant) — tolérant */
$paid = [];
try {
    $rows = $pdo->query("SELECT formation_id, COUNT(*) c FROM paiements_inscription WHERE statut='paye' GROUP BY formation_id")
                ->fetchAll(PDO::FETCH_KEY_PAIR);
    if (is_array($rows)) { $paid = $rows; }
} catch (Throwable $e) { $paid = []; }

/* Formateur de date FR (avec repli si intl absent) */
$frMonths = [1=>'janvier',2=>'février',3=>'mars',4=>'avril',5=>'mai',6=>'juin',
            7=>'juillet',8=>'août',9=>'septembre',10=>'octobre',11=>'novembre',12=>'décembre'];
$fmtFr = function (?string $d) use ($frMonths): string {
    if (!$d) return 'Date à confirmer';
    $ts = strtotime($d);
    if (!$ts) return 'Date à confirmer';
    return date('j', $ts) . ' ' . ($frMonths[(int)date('n', $ts)] ?? '') . ' ' . date('Y', $ts);
};

$waNum = function_exists('whatsapp_admin_phone') ? whatsapp_admin_phone() : '2250778882592';
$waLink = 'https://wa.me/' . $waNum . '?text=' . rawurlencode("Bonjour IBIG EDUFORM, je souhaite des informations sur les sessions SAMEDI PRO.");

$nbSessions = count($sessions);
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>SAMEDI PRO – Formations professionnelles courtes, en présentiel ou en ligne | IBIG EDUFORM</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description"
        content="SAMEDI PRO : montez en compétence en un samedi. Formations courtes 100% pratiques, en présentiel ou en ligne, certifiées IBIG EDUFORM. Découvrez les prochaines sessions et réservez votre place.">
  <meta name="robots" content="index,follow">
  <meta property="og:title" content="SAMEDI PRO – Une compétence. Un samedi. Un impact immédiat.">
  <meta property="og:description" content="Formations professionnelles courtes, en présentiel ou en ligne, les samedis. IBIG EDUFORM.">
  <meta property="og:type" content="website">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

  <style>
    :root{
      --bg:#f6f8fe;
      --bg2:#eef2fc;
      --card:#ffffff;
      --ink:#0e1530;
      --muted:#5b647c;
      --line:rgba(14,21,48,.10);

      --brand:#1f3fe0;        /* bleu logo */
      --brand-2:#4f6bff;
      --accent:#e8242c;       /* rouge logo */
      --accent-2:#ff5763;
      --ok:#0fae6e;
      --gold:#f59e0b;

      --shadow:0 24px 60px rgba(14,21,48,.12);
      --shadow-sm:0 10px 28px rgba(14,21,48,.08);
      --r:20px; --r2:16px; --r3:12px;
      --max:1160px;

      --font:"Plus Jakarta Sans","Inter",system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;
    }
    *{box-sizing:border-box}
    html{scroll-behavior:smooth}
    body{
      margin:0;color:var(--ink);font-family:var(--font);
      background:
        radial-gradient(1100px 560px at 8% -5%, rgba(31,63,224,.12), transparent 60%),
        radial-gradient(1000px 520px at 100% 0%, rgba(232,36,44,.08), transparent 58%),
        linear-gradient(180deg,var(--bg2),var(--bg));
      -webkit-font-smoothing:antialiased;
    }
    a{text-decoration:none;color:inherit}
    .wrap{max-width:var(--max);margin:auto;padding:0 20px}
    .eyebrow{display:inline-flex;align-items:center;gap:8px;font-size:12px;font-weight:800;
      letter-spacing:.4px;text-transform:uppercase;color:var(--brand);
      border:1px solid rgba(31,63,224,.22);background:rgba(31,63,224,.06);
      padding:7px 14px;border-radius:999px}
    .eyebrow .dot{width:8px;height:8px;border-radius:50%;
      background:linear-gradient(135deg,var(--brand),var(--accent))}

    /* HEADER */
    header.site{position:sticky;top:0;z-index:30;
      background:rgba(246,248,254,.82);backdrop-filter:blur(12px);
      border-bottom:1px solid var(--line)}
    .topbar{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:12px 0}
    .brand{display:flex;align-items:center;gap:11px}
    .brand img{width:46px;height:auto;display:block}
    .brand b{display:block;font-size:15px;font-weight:800;letter-spacing:-.2px}
    .brand small{display:block;font-size:11.5px;color:var(--muted);font-weight:600}
    .nav{display:flex;align-items:center;gap:6px}
    .nav a{font-size:13.5px;font-weight:600;color:var(--muted);padding:9px 13px;border-radius:999px;transition:.15s}
    .nav a:hover{background:rgba(31,63,224,.08);color:var(--ink)}
    .nav a.cta{background:linear-gradient(135deg,var(--brand),var(--brand-2));color:#fff;font-weight:800;
      box-shadow:0 12px 26px rgba(31,63,224,.30)}
    .nav a.cta:hover{transform:translateY(-1px);background:linear-gradient(135deg,var(--brand),var(--brand-2))}
    .navtoggle{display:none;border:1px solid var(--line);background:#fff;border-radius:10px;
      width:42px;height:40px;font-size:18px;color:var(--ink);cursor:pointer}

    /* BUTTONS */
    .btn{display:inline-flex;align-items:center;justify-content:center;gap:9px;
      padding:13px 20px;border-radius:13px;font-size:14px;font-weight:800;border:1px solid var(--line);
      background:#fff;color:var(--ink);transition:.16s;cursor:pointer}
    .btn:hover{transform:translateY(-2px);box-shadow:var(--shadow-sm)}
    .btn.primary{background:linear-gradient(135deg,var(--brand),var(--brand-2));color:#fff;border-color:transparent;
      box-shadow:0 16px 34px rgba(31,63,224,.32)}
    .btn.accent{background:linear-gradient(135deg,var(--accent),var(--accent-2));color:#fff;border-color:transparent;
      box-shadow:0 16px 34px rgba(232,36,44,.30)}
    .btn.ghost{background:rgba(31,63,224,.06);border-color:rgba(31,63,224,.22);color:var(--brand)}
    .btn.wa{background:#fff;border-color:rgba(37,211,102,.4);color:#0c7a43}
    .btn.lg{padding:15px 26px;font-size:15px}

    /* HERO */
    .hero{padding:54px 0 28px}
    .heroGrid{display:grid;grid-template-columns:1.15fr .85fr;gap:30px;align-items:center}
    .hero h1{margin:16px 0 14px;font-size:clamp(30px,4.4vw,48px);line-height:1.08;letter-spacing:-1px;font-weight:800}
    .hero h1 .grad{background:linear-gradient(120deg,var(--brand),var(--accent));
      -webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent}
    .hero p.lead{font-size:16.5px;line-height:1.75;color:var(--muted);max-width:56ch}
    .hero .cta{display:flex;flex-wrap:wrap;gap:12px;margin-top:24px}
    .heroPoints{display:flex;flex-wrap:wrap;gap:16px;margin-top:22px;font-size:13.5px;color:var(--muted);font-weight:600}
    .heroPoints span{display:inline-flex;align-items:center;gap:8px}
    .heroPoints i{color:var(--ok)}

    .heroCardArt{position:relative;background:var(--card);border:1px solid var(--line);border-radius:var(--r);
      box-shadow:var(--shadow);padding:26px;overflow:hidden}
    .heroCardArt::before{content:"";position:absolute;inset:auto -40% -50% auto;width:320px;height:320px;border-radius:50%;
      background:radial-gradient(circle,rgba(31,63,224,.16),transparent 65%)}
    .heroCardArt .tag{display:inline-flex;align-items:center;gap:8px;font-weight:800;font-size:12.5px;color:var(--accent);
      background:rgba(232,36,44,.08);border:1px solid rgba(232,36,44,.2);padding:6px 12px;border-radius:999px}
    .heroCardArt h3{margin:14px 0 6px;font-size:20px;font-weight:800}
    .heroCardArt .price{font-size:30px;font-weight:800;letter-spacing:-.5px}
    .heroCardArt .price small{font-size:14px;color:var(--muted);font-weight:700}
    .miniList{list-style:none;margin:16px 0 0;padding:0;display:grid;gap:10px}
    .miniList li{display:flex;gap:10px;align-items:flex-start;font-size:13.5px;color:var(--muted);font-weight:600}
    .miniList i{color:var(--brand);margin-top:2px}

    /* STATS STRIP */
    .stats{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-top:30px}
    .stat{background:var(--card);border:1px solid var(--line);border-radius:var(--r2);
      box-shadow:var(--shadow-sm);padding:18px;text-align:center}
    .stat b{display:block;font-size:24px;font-weight:800;letter-spacing:-.5px;
      background:linear-gradient(120deg,var(--brand),var(--accent));-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent}
    .stat span{font-size:12.5px;color:var(--muted);font-weight:600}

    /* SECTIONS */
    section{padding:42px 0}
    .sec-head{text-align:center;max-width:680px;margin:0 auto 28px}
    .sec-head h2{margin:12px 0 8px;font-size:clamp(24px,3.2vw,34px);font-weight:800;letter-spacing:-.6px}
    .sec-head p{margin:0;color:var(--muted);font-size:15px;line-height:1.7}

    /* STEPS */
    .steps{display:grid;grid-template-columns:repeat(3,1fr);gap:18px}
    .step{position:relative;background:var(--card);border:1px solid var(--line);border-radius:var(--r);
      box-shadow:var(--shadow-sm);padding:24px}
    .step .n{position:absolute;top:-14px;left:24px;width:34px;height:34px;border-radius:10px;
      display:grid;place-items:center;font-weight:800;color:#fff;
      background:linear-gradient(135deg,var(--brand),var(--brand-2));box-shadow:0 10px 22px rgba(31,63,224,.3)}
    .step i{font-size:22px;color:var(--brand);margin:8px 0 10px}
    .step h3{margin:0 0 6px;font-size:17px;font-weight:800}
    .step p{margin:0;color:var(--muted);font-size:14px;line-height:1.65}

    /* SESSIONS */
    .sgrid{display:grid;grid-template-columns:repeat(auto-fill,minmax(330px,1fr));gap:20px}
    .scard{display:flex;flex-direction:column;background:var(--card);border:1px solid var(--line);
      border-radius:var(--r);box-shadow:var(--shadow-sm);overflow:hidden;transition:.2s}
    .scard:hover{transform:translateY(-5px);box-shadow:var(--shadow)}
    .scard .top{padding:18px 18px 0;display:flex;flex-wrap:wrap;gap:8px;align-items:center}
    .chip{display:inline-flex;align-items:center;gap:6px;font-size:11.5px;font-weight:800;padding:6px 11px;border-radius:999px;
      border:1px solid var(--line);background:rgba(14,21,48,.04);color:var(--muted)}
    .chip.pro{background:linear-gradient(135deg,rgba(31,63,224,.12),rgba(79,107,255,.10));
      border-color:rgba(31,63,224,.25);color:var(--brand)}
    .chip.soon{background:rgba(245,158,11,.12);border-color:rgba(245,158,11,.3);color:#a86a06}
    .scard h3{margin:14px 18px 0;font-size:17.5px;font-weight:800;line-height:1.25;letter-spacing:-.3px}
    .scard .meta{margin:12px 18px 0;display:grid;gap:7px;font-size:13px;color:var(--muted);font-weight:600}
    .scard .meta div{display:flex;align-items:center;gap:9px}
    .scard .meta i{color:var(--brand);width:16px;text-align:center}
    .scard .price{margin:14px 18px 0;padding-top:14px;border-top:1px dashed var(--line);display:flex;justify-content:space-between;align-items:flex-end;gap:10px}
    .scard .price .amt{font-size:22px;font-weight:800;letter-spacing:-.4px}
    .scard .price .amt s{font-size:13px;color:var(--muted);font-weight:600;margin-right:6px}
    .scard .price .amt small{display:block;font-size:11.5px;color:var(--muted);font-weight:700;margin-top:1px}
    .eb{display:inline-flex;align-items:center;gap:6px;font-size:11.5px;font-weight:800;color:#a86a06;
      background:rgba(245,158,11,.12);border:1px solid rgba(245,158,11,.3);padding:5px 9px;border-radius:999px;white-space:nowrap}
    .scard .acts{margin:16px 18px 18px;display:flex;gap:10px}
    .scard .acts a{flex:1;text-align:center;padding:11px;border-radius:12px;font-size:13px;font-weight:800;transition:.15s}
    .scard .acts a.view{background:rgba(31,63,224,.07);border:1px solid rgba(31,63,224,.22);color:var(--brand)}
    .scard .acts a.book{background:linear-gradient(135deg,var(--accent),var(--accent-2));color:#fff;
      box-shadow:0 12px 26px rgba(232,36,44,.26)}
    .scard .acts a:hover{transform:translateY(-1px)}
    .empty{grid-column:1/-1;text-align:center;padding:40px 20px;border:1px dashed var(--line);
      border-radius:var(--r);background:#fff;color:var(--muted)}

    /* FEATURE GRID */
    .feat{display:grid;grid-template-columns:repeat(3,1fr);gap:18px}
    .fcard{background:var(--card);border:1px solid var(--line);border-radius:var(--r);box-shadow:var(--shadow-sm);padding:24px}
    .fcard .ico{width:48px;height:48px;border-radius:13px;display:grid;place-items:center;font-size:20px;color:#fff;
      background:linear-gradient(135deg,var(--brand),var(--brand-2));margin-bottom:14px}
    .fcard:nth-child(2) .ico{background:linear-gradient(135deg,var(--accent),var(--accent-2))}
    .fcard:nth-child(3) .ico{background:linear-gradient(135deg,var(--ok),#27d39a)}
    .fcard:nth-child(4) .ico{background:linear-gradient(135deg,var(--gold),#fbbf24)}
    .fcard:nth-child(5) .ico{background:linear-gradient(135deg,#7c3aed,#a78bfa)}
    .fcard:nth-child(6) .ico{background:linear-gradient(135deg,#0ea5e9,#38bdf8)}
    .fcard h3{margin:0 0 7px;font-size:17px;font-weight:800}
    .fcard p{margin:0;color:var(--muted);font-size:14px;line-height:1.65}

    /* BAND */
    .band{background:linear-gradient(135deg,var(--brand),#10227f);border-radius:var(--r);
      box-shadow:var(--shadow);color:#fff;padding:28px;display:flex;flex-wrap:wrap;gap:18px;align-items:center;justify-content:space-between;position:relative;overflow:hidden}
    .band::before{content:"";position:absolute;right:-60px;top:-60px;width:260px;height:260px;border-radius:50%;
      background:radial-gradient(circle,rgba(232,36,44,.35),transparent 60%)}
    .band .t{position:relative;z-index:1}
    .band h3{margin:0 0 6px;font-size:20px;font-weight:800}
    .band p{margin:0;opacity:.9;font-size:14.5px;line-height:1.6;max-width:62ch}
    .band .badges{position:relative;z-index:1;display:flex;flex-wrap:wrap;gap:10px}
    .band .badges span{display:inline-flex;align-items:center;gap:8px;font-size:13px;font-weight:700;
      background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.25);padding:9px 13px;border-radius:999px}

    /* TWO COLS */
    .cols2{display:grid;grid-template-columns:1fr 1fr;gap:18px}
    .panel{background:var(--card);border:1px solid var(--line);border-radius:var(--r);box-shadow:var(--shadow-sm);padding:24px}
    .panel h3{margin:0 0 14px;font-size:18px;font-weight:800;display:flex;align-items:center;gap:10px}
    .panel h3 i{color:var(--brand)}
    .panel ul{margin:0;padding:0;list-style:none;display:grid;gap:11px}
    .panel li{display:flex;gap:11px;align-items:flex-start;color:var(--muted);font-size:14.5px;line-height:1.55}
    .panel li i{color:var(--ok);margin-top:3px}

    /* TESTIMONIALS */
    .temo{display:grid;grid-template-columns:repeat(auto-fill,minmax(290px,1fr));gap:18px}
    .tcard{background:var(--card);border:1px solid var(--line);border-radius:var(--r);box-shadow:var(--shadow-sm);padding:22px}
    .tcard .stars{color:var(--gold);font-size:13px;margin-bottom:8px}
    .tcard p{margin:0 0 14px;font-size:14px;line-height:1.65;color:var(--ink)}
    .tcard .who{display:flex;align-items:center;gap:11px}
    .tcard .av{width:40px;height:40px;border-radius:50%;display:grid;place-items:center;font-weight:800;color:#fff;
      background:linear-gradient(135deg,var(--brand),var(--accent))}
    .tcard .who b{display:block;font-size:13.5px}
    .tcard .who small{font-size:12px;color:var(--muted)}

    /* FAQ */
    .faq{max-width:820px;margin:auto;display:grid;gap:12px}
    details.qa{background:var(--card);border:1px solid var(--line);border-radius:var(--r2);box-shadow:var(--shadow-sm);padding:4px 18px}
    details.qa summary{list-style:none;cursor:pointer;padding:16px 0;font-weight:800;font-size:15px;
      display:flex;justify-content:space-between;align-items:center;gap:12px}
    details.qa summary::-webkit-details-marker{display:none}
    details.qa summary i{transition:.2s;color:var(--brand)}
    details.qa[open] summary i{transform:rotate(180deg)}
    details.qa .a{padding:0 0 16px;color:var(--muted);font-size:14.5px;line-height:1.7}

    /* FINAL */
    .final{text-align:center;background:var(--card);border:1px solid var(--line);border-radius:var(--r);
      box-shadow:var(--shadow);padding:40px 28px;position:relative;overflow:hidden}
    .final::before{content:"";position:absolute;inset:auto auto -40% -10%;width:300px;height:300px;border-radius:50%;
      background:radial-gradient(circle,rgba(31,63,224,.14),transparent 60%)}
    .final h2{margin:10px 0 10px;font-size:clamp(24px,3.2vw,32px);font-weight:800;position:relative;z-index:1}
    .final p{margin:0 auto;max-width:60ch;color:var(--muted);font-size:15px;line-height:1.7;position:relative;z-index:1}
    .final .cta{display:flex;flex-wrap:wrap;gap:12px;justify-content:center;margin-top:22px;position:relative;z-index:1}

    @media(max-width:980px){
      .heroGrid{grid-template-columns:1fr}
      .steps,.feat{grid-template-columns:1fr 1fr}
      .stats{grid-template-columns:repeat(2,1fr)}
    }
    @media(max-width:760px){
      .nav{display:none}
      .nav.open{display:flex;position:absolute;top:64px;left:0;right:0;flex-direction:column;align-items:stretch;
        background:rgba(246,248,254,.98);backdrop-filter:blur(12px);border-bottom:1px solid var(--line);padding:12px 20px;gap:8px}
      .navtoggle{display:inline-block}
      .steps,.feat,.cols2{grid-template-columns:1fr}
    }
    @media(max-width:430px){
      .stats{grid-template-columns:1fr 1fr}
      .scard .acts{flex-direction:column}
    }
  </style>
</head>
<body>

<header class="site">
  <div class="wrap topbar">
    <a class="brand" href="https://ibig-eduform.com">
      <img src="/assets/images/logo.png" alt="IBIG EDUFORM">
      <span><b>IBIG EDUFORM</b><small>Programme SAMEDI PRO</small></span>
    </a>
    <button class="navtoggle" aria-label="Menu" onclick="document.getElementById('nav').classList.toggle('open')">
      <i class="fa-solid fa-bars"></i>
    </button>
    <nav class="nav" id="nav">
      <a href="https://ibig-eduform.com">Accueil</a>
      <a href="#sessions">Sessions</a>
      <a href="#concept">Concept</a>
      <a href="#public">Public</a>
      <a href="#faq">FAQ</a>
      <a href="#sessions" class="cta">Réserver ma place</a>
    </nav>
  </div>
</header>

<main>

<!-- HERO -->
<section class="hero">
  <div class="wrap">
    <div class="heroGrid">
      <div>
        <span class="eyebrow"><span class="dot"></span> Présentiel ou en ligne · Les samedis</span>
        <h1>Une compétence. Un samedi.<br><span class="grad">Un impact immédiat.</span></h1>
        <p class="lead">
          <strong>SAMEDI PRO</strong> est le programme de formations courtes et 100&nbsp;% pratiques d'IBIG&nbsp;EDUFORM.
          En une journée — <strong>en présentiel à Abidjan ou en ligne</strong> — vous repartez avec une compétence
          directement applicable lundi matin, et une attestation à la clé.
        </p>
        <div class="cta">
          <a class="btn primary lg" href="#sessions"><i class="fa-solid fa-calendar-check"></i> Voir les prochaines sessions</a>
          <a class="btn wa lg" href="<?= e($waLink) ?>" target="_blank" rel="noopener"><i class="fa-brands fa-whatsapp"></i> Poser une question</a>
        </div>
        <div class="heroPoints">
          <span><i class="fa-solid fa-circle-check"></i> 1 journée intensive (7&nbsp;h)</span>
          <span><i class="fa-solid fa-circle-check"></i> Formateurs experts en activité</span>
          <span><i class="fa-solid fa-circle-check"></i> Attestation IBIG EDUFORM</span>
        </div>
      </div>

      <aside class="heroCardArt">
        <span class="tag"><i class="fa-solid fa-bolt"></i> Format express certifiant</span>
        <h3>Le samedi, vous changez de niveau</h3>
        <div class="price">45 000 – 100 000 FCFA</div>
        <ul class="miniList">
          <li><i class="fa-solid fa-clock"></i> Une journée — 09h00 à 16h00</li>
          <li><i class="fa-solid fa-laptop"></i> En présentiel (Abidjan) ou en visio interactive</li>
          <li><i class="fa-solid fa-users"></i> Groupes limités (20 places)</li>
          <li><i class="fa-solid fa-feather-pointed"></i> 100&nbsp;% pratique, cas réels d'entreprise</li>
          <li><i class="fa-solid fa-gift"></i> Supports & modèles offerts</li>
        </ul>
      </aside>
    </div>

    <!-- STATS -->
    <div class="stats">
      <div class="stat"><b><?= e(setting('stat_apprenants','1 000+')) ?></b><span>Apprenants formés</span></div>
      <div class="stat"><b><?= e(setting('stat_satisfaction','+90%')) ?></b><span>Taux de satisfaction</span></div>
      <div class="stat"><b><?= $nbSessions > 0 ? (int)$nbSessions : '12+' ?></b><span>Sessions programmées</span></div>
      <div class="stat"><b><?= e(setting('stat_experience','3 ans')) ?></b><span>d'expérience terrain</span></div>
    </div>
  </div>
</section>

<!-- ÉTAPES -->
<section id="concept">
  <div class="wrap">
    <div class="sec-head">
      <span class="eyebrow"><span class="dot"></span> Comment ça marche</span>
      <h2>Monter en compétence en 3 étapes</h2>
      <p>Un parcours simple, pensé pour les actifs : pas besoin de bloquer votre semaine de travail.</p>
    </div>
    <div class="steps">
      <div class="step"><span class="n">1</span><i class="fa-solid fa-list-check"></i>
        <h3>Choisissez votre thème</h3>
        <p>Sélectionnez la session qui correspond à votre besoin parmi les prochaines dates ci-dessous.</p>
      </div>
      <div class="step"><span class="n">2</span><i class="fa-solid fa-ticket"></i>
        <h3>Réservez votre place</h3>
        <p>Préinscription en ligne en 2 minutes, puis réservation. Places limitées : on confirme vite.</p>
      </div>
      <div class="step"><span class="n">3</span><i class="fa-solid fa-graduation-cap"></i>
        <h3>Formez-vous le samedi</h3>
        <p>Une journée 100&nbsp;% pratique, en présentiel ou en ligne, et une attestation à la clé.</p>
      </div>
    </div>
  </div>
</section>

<!-- SESSIONS DYNAMIQUES -->
<section id="sessions">
  <div class="wrap">
    <div class="sec-head">
      <span class="eyebrow"><span class="dot"></span> Agenda</span>
      <h2>Prochaines sessions SAMEDI PRO</h2>
      <p>Dates réelles, places limitées. Réservez tant qu'il reste de la place — et profitez de l'<em>Offre Anticipée</em>.</p>
    </div>

    <div class="sgrid">
      <?php if (!$sessions): ?>
        <div class="empty">
          <p style="font-size:16px;font-weight:700;margin:0 0 8px">Aucune session à venir n'est ouverte pour l'instant.</p>
          <p style="margin:0 0 16px">Contactez-nous pour être informé(e) des prochaines dates SAMEDI PRO.</p>
          <a class="btn wa" href="<?= e($waLink) ?>" target="_blank" rel="noopener"><i class="fa-brands fa-whatsapp"></i> Être prévenu(e)</a>
        </div>
      <?php else: foreach ($sessions as $f):
        $fid    = (int)($f['id'] ?? 0);
        $slug   = (string)($f['slug'] ?? '');
        $titre  = (string)($f['titre'] ?? '');
        $dom    = (string)($f['domaine'] ?? '');
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
              <small>FCFA · en ligne<?php if ($presen>0): ?> · <?php if ($hasEb): ?><s><?= number_format($presen + PROMO_REMISE_PRESENTIEL,0,',',' ') ?> FCFA</s> <?php endif; ?><?= number_format($presen,0,',',' ') ?> FCFA présentiel<?php endif; ?></small>
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

    <div style="text-align:center;margin-top:26px">
      <a class="btn ghost lg" href="/formations-samedi-pro.php"><i class="fa-solid fa-table-cells-large"></i> Voir tout le catalogue Samedi Pro</a>
    </div>
  </div>
</section>

<!-- POURQUOI -->
<section>
  <div class="wrap">
    <div class="sec-head">
      <span class="eyebrow"><span class="dot"></span> Pourquoi Samedi Pro</span>
      <h2>Le format qui respecte votre temps</h2>
      <p>Court, concret, certifiant — et compatible avec une vie professionnelle bien remplie.</p>
    </div>
    <div class="feat">
      <div class="fcard"><div class="ico"><i class="fa-solid fa-bolt"></i></div>
        <h3>Impact immédiat</h3><p>Une compétence ciblée, applicable dès le lundi sur votre poste ou dans votre activité.</p></div>
      <div class="fcard"><div class="ico"><i class="fa-solid fa-hand-holding-dollar"></i></div>
        <h3>Tarif accessible</h3><p>Un investissement maîtrisé pour une journée intensive à forte valeur ajoutée.</p></div>
      <div class="fcard"><div class="ico"><i class="fa-solid fa-laptop-file"></i></div>
        <h3>Présentiel ou en ligne</h3><p>Participez à Abidjan ou en visioconférence interactive, au choix, sans rien perdre.</p></div>
      <div class="fcard"><div class="ico"><i class="fa-solid fa-screwdriver-wrench"></i></div>
        <h3>100&nbsp;% pratique</h3><p>Des cas réels d'entreprise, des exercices guidés et des modèles prêts à l'emploi.</p></div>
      <div class="fcard"><div class="ico"><i class="fa-solid fa-user-tie"></i></div>
        <h3>Formateurs experts</h3><p>Des praticiens en activité qui partagent ce qui fonctionne vraiment sur le terrain.</p></div>
      <div class="fcard"><div class="ico"><i class="fa-solid fa-certificate"></i></div>
        <h3>Attestation à la clé</h3><p>Une attestation IBIG EDUFORM valorisable sur votre CV, remise en fin de session.</p></div>
    </div>
  </div>
</section>

<!-- FORMAT BAND -->
<section>
  <div class="wrap">
    <div class="band">
      <div class="t">
        <h3><i class="fa-regular fa-circle-check"></i> Format officiel SAMEDI PRO</h3>
        <p>Une journée par thème, le samedi, en présentiel ou en ligne. Groupes limités, supports fournis,
           attestation IBIG EDUFORM délivrée à chaque participant.</p>
      </div>
      <div class="badges">
        <span><i class="fa-solid fa-calendar-day"></i> Le samedi</span>
        <span><i class="fa-solid fa-clock"></i> 09h – 16h</span>
        <span><i class="fa-solid fa-laptop"></i> Présentiel ou en ligne</span>
        <span><i class="fa-solid fa-users"></i> 20 places</span>
      </div>
    </div>
  </div>
</section>

<!-- PUBLIC -->
<section id="public">
  <div class="wrap">
    <div class="sec-head">
      <span class="eyebrow"><span class="dot"></span> Pour qui ?</span>
      <h2>Conçu pour les professionnels pressés</h2>
    </div>
    <div class="cols2">
      <div class="panel">
        <h3><i class="fa-solid fa-user-group"></i> Public concerné</h3>
        <ul>
          <li><i class="fa-solid fa-check"></i> Salariés et agents d'entreprise qui veulent monter en compétence</li>
          <li><i class="fa-solid fa-check"></i> Entrepreneurs et dirigeants de PME</li>
          <li><i class="fa-solid fa-check"></i> Comptables, gestionnaires, assistants, commerciaux</li>
          <li><i class="fa-solid fa-check"></i> Étudiants en fin de cycle et jeunes diplômés</li>
          <li><i class="fa-solid fa-check"></i> Porteurs de projets et freelances</li>
        </ul>
      </div>
      <div class="panel">
        <h3><i class="fa-solid fa-bullseye"></i> Ce que vous y gagnez</h3>
        <ul>
          <li><i class="fa-solid fa-check"></i> Un gain de temps maximal : une seule journée</li>
          <li><i class="fa-solid fa-check"></i> Une compétence concrète, immédiatement exploitable</li>
          <li><i class="fa-solid fa-check"></i> Un coût accessible, sans engagement long</li>
          <li><i class="fa-solid fa-check"></i> Un réseau et un groupe d'entraide</li>
          <li><i class="fa-solid fa-check"></i> Un format compatible avec votre emploi du temps</li>
        </ul>
      </div>
    </div>
  </div>
</section>

<?php
$avis = function_exists('get_approved_avis') ? get_approved_avis(6) : [];
if ($avis):
?>
<!-- TÉMOIGNAGES -->
<section>
  <div class="wrap">
    <div class="sec-head">
      <span class="eyebrow"><span class="dot"></span> Ils l'ont fait</span>
      <h2>Ce qu'en disent les participants</h2>
    </div>
    <div class="temo">
      <?php foreach ($avis as $a):
        $nom = trim((string)($a['nom'] ?? '')); if ($nom==='') $nom='Participant';
        $sec = trim((string)($a['secteur'] ?? ''));
        $ini = strtoupper(mb_substr($nom,0,1,'UTF-8'));
      ?>
        <div class="tcard">
          <div class="stars">★★★★★</div>
          <p><?= e((string)($a['texte'] ?? '')) ?></p>
          <div class="who">
            <span class="av"><?= e($ini) ?></span>
            <span><b><?= e($nom) ?></b><?php if ($sec!==''): ?><small><?= e($sec) ?></small><?php endif; ?></span>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- FAQ -->
<section id="faq">
  <div class="wrap">
    <div class="sec-head">
      <span class="eyebrow"><span class="dot"></span> FAQ</span>
      <h2>Vos questions, nos réponses</h2>
    </div>
    <div class="faq">
      <details class="qa" open>
        <summary>Les sessions sont-elles en présentiel ou en ligne ? <i class="fa-solid fa-chevron-down"></i></summary>
        <div class="a">Les deux sont possibles. Chaque SAMEDI PRO peut se suivre <strong>en présentiel à Abidjan</strong> ou
          <strong>en ligne</strong> en visioconférence interactive. Vous choisissez la formule qui vous convient à la réservation.</div>
      </details>
      <details class="qa">
        <summary>Combien de temps dure une session ? <i class="fa-solid fa-chevron-down"></i></summary>
        <div class="a">Une journée intensive, généralement de <strong>09h00 à 16h00</strong> (7&nbsp;h). Certaines thématiques peuvent s'étendre sur deux samedis.</div>
      </details>
      <details class="qa">
        <summary>Reçoit-on une attestation ? <i class="fa-solid fa-chevron-down"></i></summary>
        <div class="a">Oui. Une <strong>attestation de participation IBIG EDUFORM</strong> est remise à chaque participant à l'issue de la session.</div>
      </details>
      <details class="qa">
        <summary>Comment régler ma place ? <i class="fa-solid fa-chevron-down"></i></summary>
        <div class="a">La préinscription est gratuite et sans engagement. La réservation se règle ensuite en ligne de façon
          <strong>100&nbsp;% sécurisée</strong>. Des facilités de paiement sont possibles — écrivez-nous.</div>
      </details>
      <details class="qa">
        <summary>Y a-t-il des réductions ? <i class="fa-solid fa-chevron-down"></i></summary>
        <div class="a">Oui : une <strong>Offre Anticipée (−10&nbsp;%)</strong> s'applique aux inscriptions anticipées, et des
          <strong>tarifs de groupe</strong> sont disponibles pour les entreprises. Contactez-nous pour un devis.</div>
      </details>
    </div>
  </div>
</section>

<!-- FINAL -->
<section>
  <div class="wrap">
    <div class="final">
      <span class="eyebrow"><span class="dot"></span> Prêt(e) ?</span>
      <h2>Réservez votre prochain SAMEDI PRO</h2>
      <p>Une compétence de plus, ça change une carrière. Choisissez votre date, réservez votre place — il en reste peu.</p>
      <div class="cta">
        <a class="btn accent lg" href="#sessions"><i class="fa-solid fa-calendar-check"></i> Choisir ma session</a>
        <a class="btn primary lg" href="/preinscription-samedi-pro.php"><i class="fa-solid fa-pen-to-square"></i> Préinscription</a>
        <a class="btn wa lg" href="<?= e($waLink) ?>" target="_blank" rel="noopener"><i class="fa-brands fa-whatsapp"></i> WhatsApp</a>
      </div>
    </div>
  </div>
</section>

</main>

<?php include __DIR__ . '/partials/footer.php'; ?>

<script>
  /* fermeture du menu mobile au clic sur un lien */
  document.querySelectorAll('#nav a').forEach(function(a){
    a.addEventListener('click', function(){ document.getElementById('nav').classList.remove('open'); });
  });
</script>
</body>
</html>
