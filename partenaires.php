<?php
require_once __DIR__ . '/core/bootstrap.php';

$pageTitle = "Partenaires stratégiques – IBIG EDUFORM";
$ogDesc    = "IBIG EDUFORM noue des partenariats durables avec des entreprises, institutions, ONG et experts pour des formations crédibles, certifiantes et orientées impact.";

$pdo = Database::connect();
$partenaires = [];
try {
    $partenaires = $pdo->query("SELECT nom, logo, site_web FROM partenaires ORDER BY nom ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) { $partenaires = []; }
$nbPartners = count($partenaires);

include __DIR__ . '/partials/header.php';
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
/* =====================================================
   PARTENAIRES — REFONTE PREMIUM (namespacé .pa)
===================================================== */
.pa{
  --brand:#1f3fe0;--brand2:#4f6bff;--accent:#e8242c;--accent2:#ff5763;
  --navy:#0a1733;--navy2:#0b2552;--ink:#0e1530;--muted:#5b647c;--line:rgba(14,21,48,.10);
  --bg:#f6f8fe;--card:#fff;--gold:#f5a524;--ok:#0fae6e;
  --r:22px;--r2:16px;--shadow:0 26px 64px rgba(14,21,48,.14);--shadow-sm:0 12px 30px rgba(14,21,48,.08);
  --font:Inter,"Plus Jakarta Sans",system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;
  background:var(--bg);color:var(--ink);font-family:var(--font);
}
.pa *{box-sizing:border-box}
.pa .wrap{max-width:1180px;margin:auto;padding:0 22px}
.pa a{text-decoration:none}
.pa .eyebrow{display:inline-flex;align-items:center;gap:8px;font-size:12px;font-weight:800;letter-spacing:.5px;
  text-transform:uppercase;color:var(--brand);border:1px solid rgba(31,63,224,.22);background:rgba(31,63,224,.06);
  padding:7px 14px;border-radius:999px}
.pa .eyebrow.light{color:#bcd2ff;border-color:rgba(188,210,255,.3);background:rgba(31,63,224,.18)}
.pa .eyebrow .d{width:8px;height:8px;border-radius:50%;background:linear-gradient(135deg,var(--brand2),var(--accent))}

/* HERO */
.pa .hero{position:relative;overflow:hidden;color:#fff;border-radius:28px;margin:30px auto 0;max-width:1180px;
  padding:74px 60px;box-shadow:0 35px 80px rgba(10,23,51,.45);background:linear-gradient(125deg,var(--navy),var(--navy2))}
.pa .hero::before{content:"";position:absolute;right:-120px;top:-120px;width:420px;height:420px;border-radius:50%;background:radial-gradient(circle,rgba(31,63,224,.45),transparent 62%)}
.pa .hero::after{content:"";position:absolute;left:-100px;bottom:-140px;width:360px;height:360px;border-radius:50%;background:radial-gradient(circle,rgba(232,36,44,.28),transparent 60%)}
.pa .hero .in{position:relative;z-index:1;max-width:840px}
.pa .hero h1{margin:18px 0 16px;font-size:clamp(30px,4.6vw,50px);line-height:1.08;letter-spacing:-1px;font-weight:800}
.pa .hero h1 .hl{background:linear-gradient(120deg,#ffd9b0,#ff9d6b);-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent}
.pa .hero p{font-size:17px;line-height:1.75;opacity:.94;max-width:68ch}
.pa .hero .cta{display:flex;flex-wrap:wrap;gap:12px;margin-top:26px}

.pa .btn{display:inline-flex;align-items:center;justify-content:center;gap:9px;padding:14px 24px;border-radius:14px;font-size:15px;font-weight:800;border:1px solid var(--line);background:#fff;color:var(--ink);transition:.16s}
.pa .btn:hover{transform:translateY(-2px);box-shadow:var(--shadow-sm)}
.pa .btn.accent{background:linear-gradient(135deg,var(--accent),var(--accent2));color:#fff;border-color:transparent;box-shadow:0 16px 34px rgba(232,36,44,.32)}
.pa .btn.primary{background:linear-gradient(135deg,var(--brand),var(--brand2));color:#fff;border-color:transparent;box-shadow:0 16px 34px rgba(31,63,224,.32)}
.pa .btn.glass{background:rgba(255,255,255,.10);border-color:rgba(255,255,255,.28);color:#fff}

/* STATS */
.pa .stats{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin:-44px auto 0;max-width:880px;position:relative;z-index:5}
.pa .stat{background:var(--card);border:1px solid var(--line);border-radius:var(--r2);box-shadow:var(--shadow-sm);padding:22px;text-align:center}
.pa .stat b{display:block;font-size:30px;font-weight:800;letter-spacing:-.6px;background:linear-gradient(120deg,var(--brand),var(--accent));-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent}
.pa .stat span{font-size:13px;color:var(--muted);font-weight:600}

/* SECTIONS */
.pa section{padding:58px 0}
.pa .head{max-width:780px;margin:0 auto 34px;text-align:center}
.pa .head h2{margin:14px 0 10px;font-size:clamp(26px,3.4vw,38px);font-weight:800;letter-spacing:-.6px;color:var(--ink)}
.pa .head p{margin:0;color:var(--muted);font-size:16px;line-height:1.7}

.pa .grid3{display:grid;grid-template-columns:repeat(3,1fr);gap:20px}
.pa .grid4{display:grid;grid-template-columns:repeat(4,1fr);gap:18px}
.pa .fcard{background:var(--card);border:1px solid var(--line);border-radius:var(--r);box-shadow:var(--shadow-sm);padding:26px;transition:.2s}
.pa .fcard:hover{transform:translateY(-6px);box-shadow:var(--shadow)}
.pa .fcard .ico{width:52px;height:52px;border-radius:15px;display:grid;place-items:center;font-size:22px;color:#fff;margin-bottom:16px;background:linear-gradient(135deg,var(--brand),var(--brand2))}
.pa .fcard:nth-child(2) .ico{background:linear-gradient(135deg,var(--accent),var(--accent2))}
.pa .fcard:nth-child(3) .ico{background:linear-gradient(135deg,var(--ok),#27d39a)}
.pa .fcard:nth-child(4) .ico{background:linear-gradient(135deg,var(--gold),#fbbf24)}
.pa .fcard h3{margin:0 0 8px;font-size:18px;font-weight:800;color:var(--ink)}
.pa .fcard p{margin:0;color:var(--muted);font-size:14.5px;line-height:1.65}

/* LOGOS PARTENAIRES */
.pa .logos{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:20px}
.pa .pcard{background:var(--card);border:1px solid var(--line);border-radius:var(--r);box-shadow:var(--shadow-sm);padding:26px;text-align:center;transition:.2s}
.pa .pcard:hover{transform:translateY(-5px);box-shadow:var(--shadow)}
.pa .plogo{height:90px;display:flex;align-items:center;justify-content:center;margin-bottom:14px}
.pa .plogo img{max-height:72px;max-width:100%;filter:grayscale(100%);opacity:.85;transition:.3s}
.pa .pcard:hover .plogo img{filter:none;opacity:1;transform:scale(1.05)}
.pa .plogo .ph{width:64px;height:64px;border-radius:16px;display:grid;place-items:center;font-weight:800;font-size:24px;color:#fff;background:linear-gradient(135deg,var(--brand),var(--accent))}
.pa .pname{font-weight:800;color:var(--ink);font-size:15px}
.pa .plink{display:inline-flex;align-items:center;gap:6px;margin-top:8px;font-size:12.5px;font-weight:800;color:var(--brand)}
.pa .plink:hover{text-decoration:underline}
.pa .empty{grid-column:1/-1;text-align:center;padding:40px 20px;border:1px dashed var(--line);border-radius:var(--r);background:#fff;color:var(--muted)}

/* CTA */
.pa .final{position:relative;overflow:hidden;border-radius:28px;padding:64px 50px;text-align:center;color:#fff;max-width:1180px;margin:0 auto;
  background:linear-gradient(130deg,var(--navy),var(--navy2));box-shadow:0 35px 80px rgba(10,23,51,.4)}
.pa .final::before{content:"";position:absolute;right:-90px;top:-90px;width:340px;height:340px;border-radius:50%;background:radial-gradient(circle,rgba(31,63,224,.4),transparent 62%)}
.pa .final::after{content:"";position:absolute;left:-90px;bottom:-120px;width:320px;height:320px;border-radius:50%;background:radial-gradient(circle,rgba(232,36,44,.3),transparent 60%)}
.pa .final .in{position:relative;z-index:1}
.pa .final h2{margin:14px 0 12px;font-size:clamp(26px,3.6vw,40px);font-weight:800;letter-spacing:-.6px}
.pa .final p{margin:0 auto;max-width:62ch;font-size:16px;line-height:1.7;opacity:.92}
.pa .final .cta{display:flex;flex-wrap:wrap;gap:12px;justify-content:center;margin-top:26px}

@media(max-width:980px){.pa .grid3,.pa .grid4{grid-template-columns:1fr 1fr}.pa .stats{grid-template-columns:1fr}}
@media(max-width:680px){.pa .hero{padding:54px 26px;margin-top:18px}.pa .grid3,.pa .grid4{grid-template-columns:1fr}}
</style>

<div class="pa">
<main>

<!-- HERO -->
<section style="padding-top:0">
  <div class="hero">
    <div class="in">
      <span class="eyebrow light"><span class="d"></span> Écosystème &amp; alliances stratégiques</span>
      <h1>Des partenariats<br><span class="hl">à forte valeur institutionnelle.</span></h1>
      <p>
        IBIG EDUFORM construit des partenariats durables avec des entreprises, institutions, ONG et experts,
        afin de garantir des formations <strong>crédibles, certifiantes et orientées impact réel</strong> sur le terrain.
      </p>
      <div class="cta">
        <a class="btn accent" href="/contact.php"><i class="fa-solid fa-handshake"></i> Proposer un partenariat</a>
        <a class="btn glass" href="/entreprises.php"><i class="fa-solid fa-building"></i> Offre entreprises</a>
      </div>
    </div>
  </div>

  <!-- STATS -->
  <div class="stats">
    <div class="stat"><b><?= $nbPartners > 0 ? (int)$nbPartners.'+' : '15+'; ?></b><span>Partenaires &amp; intervenants</span></div>
    <div class="stat"><b>10+</b><span>Secteurs couverts</span></div>
    <div class="stat"><b>100%</b><span>Vision impact</span></div>
  </div>
</section>

<!-- POURQUOI -->
<section>
  <div class="wrap">
    <div class="head">
      <span class="eyebrow"><span class="d"></span> Pourquoi des partenariats</span>
      <h2>Un pilier de notre modèle</h2>
      <p>Crédibilité, expertise complémentaire et opportunités concrètes pour nos apprenants.</p>
    </div>
    <div class="grid3">
      <div class="fcard"><div class="ico"><i class="fa-solid fa-landmark"></i></div>
        <h3>Crédibilité institutionnelle</h3><p>Des alliances solides renforcent la reconnaissance et la légitimité de nos certifications.</p></div>
      <div class="fcard"><div class="ico"><i class="fa-solid fa-puzzle-piece"></i></div>
        <h3>Expertise complémentaire</h3><p>Chaque partenaire apporte une valeur technique, sectorielle ou institutionnelle spécifique.</p></div>
      <div class="fcard"><div class="ico"><i class="fa-solid fa-briefcase"></i></div>
        <h3>Insertion professionnelle</h3><p>Stages, missions, emplois et projets concrets pour nos apprenants.</p></div>
    </div>
  </div>
</section>

<!-- TYPES DE PARTENAIRES -->
<section>
  <div class="wrap">
    <div class="head">
      <span class="eyebrow"><span class="d"></span> Avec qui</span>
      <h2>Nos types de partenaires</h2>
    </div>
    <div class="grid4">
      <div class="fcard"><div class="ico"><i class="fa-solid fa-building"></i></div>
        <h3>Entreprises &amp; PME</h3><p>Terrains de stage, missions et recrutement de nos talents.</p></div>
      <div class="fcard"><div class="ico"><i class="fa-solid fa-landmark-dome"></i></div>
        <h3>Institutions &amp; administrations</h3><p>Renforcement de capacités et programmes structurés.</p></div>
      <div class="fcard"><div class="ico"><i class="fa-solid fa-hand-holding-heart"></i></div>
        <h3>ONG &amp; projets</h3><p>Suivi-évaluation, gestion de projet et gouvernance.</p></div>
      <div class="fcard"><div class="ico"><i class="fa-solid fa-user-tie"></i></div>
        <h3>Experts &amp; cabinets</h3><p>Formateurs praticiens et intervenants de haut niveau.</p></div>
    </div>
  </div>
</section>

<!-- NOS PARTENAIRES -->
<section>
  <div class="wrap">
    <div class="head">
      <span class="eyebrow"><span class="d"></span> Ils nous accompagnent</span>
      <h2>Nos partenaires</h2>
    </div>
    <div class="logos">
      <?php if (empty($partenaires)): ?>
        <div class="empty">
          <p style="font-size:16px;font-weight:700;margin:0 0 6px">Partenariats en cours de structuration</p>
          <p style="margin:0">Vous souhaitez collaborer avec IBIG EDUFORM ? <a href="/contact.php" style="color:#1f3fe0;font-weight:800">Proposez un partenariat →</a></p>
        </div>
      <?php else: foreach ($partenaires as $p):
        $nom = trim((string)($p['nom'] ?? ''));
        $logo = trim((string)($p['logo'] ?? ''));
        $site = trim((string)($p['site_web'] ?? ''));
        $ini = strtoupper(mb_substr($nom !== '' ? $nom : 'P', 0, 1, 'UTF-8'));
      ?>
        <div class="pcard">
          <div class="plogo">
            <?php if ($logo !== ''): ?>
              <img src="/uploads/<?= e($logo); ?>" alt="<?= e($nom); ?>" loading="lazy" decoding="async">
            <?php else: ?>
              <span class="ph"><?= e($ini); ?></span>
            <?php endif; ?>
          </div>
          <div class="pname"><?= e($nom); ?></div>
          <?php if ($site !== ''): ?>
            <a class="plink" href="<?= e($site); ?>" target="_blank" rel="noopener"><i class="fa-solid fa-arrow-up-right-from-square"></i> Visiter le site</a>
          <?php endif; ?>
        </div>
      <?php endforeach; endif; ?>
    </div>
  </div>
</section>

<!-- DEVENIR PARTENAIRE -->
<section>
  <div class="wrap">
    <div class="head">
      <span class="eyebrow"><span class="d"></span> Devenir partenaire</span>
      <h2>Ce que vous y gagnez</h2>
    </div>
    <div class="grid3">
      <div class="fcard"><div class="ico"><i class="fa-solid fa-users-viewfinder"></i></div>
        <h3>Accès à des talents formés</h3><p>Recrutez parmi des profils certifiés, opérationnels et évalués.</p></div>
      <div class="fcard"><div class="ico"><i class="fa-solid fa-bullhorn"></i></div>
        <h3>Visibilité &amp; image</h3><p>Votre marque associée à un institut crédible et orienté impact.</p></div>
      <div class="fcard"><div class="ico"><i class="fa-solid fa-diagram-project"></i></div>
        <h3>Co-construction de programmes</h3><p>Des formations sur mesure alignées sur les besoins de votre secteur.</p></div>
    </div>
  </div>
</section>

<!-- CTA -->
<section>
  <div class="final">
    <div class="in">
      <span class="eyebrow light"><span class="d"></span> Collaborons</span>
      <h2>Construisons un partenariat structurant</h2>
      <p>Rejoindre IBIG EDUFORM, c'est intégrer un écosystème professionnel orienté compétences, impact durable et performance mesurable.</p>
      <div class="cta">
        <a class="btn accent" href="/contact.php"><i class="fa-solid fa-handshake"></i> Proposer un partenariat</a>
        <a class="btn primary" href="/entreprises.php"><i class="fa-solid fa-briefcase"></i> Offre entreprises</a>
      </div>
    </div>
  </div>
</section>

</main>
</div>

<?php include __DIR__ . '/partials/footer.php'; ?>
