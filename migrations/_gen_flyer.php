<?php
/* Générateur de FLYER (HTML autonome, portrait 1080x1350) pour une formation.
   Usage : php _gen_flyer.php IBIG-20260810B > flyer.html
   Sert de base au rendu HD (capture navigateur) puis à l'intégration. */

$base = dirname(__DIR__);
$DATA = require __DIR__ . '/_data_programme_2026_aout.php';
$LAND = require __DIR__ . '/_landings_content_2026_aout.php';

/* Logo IBIG EDUFORM en base64 (autonome, aucun appel externe) */
$logoPath = $base . '/assets/images/logo.png';
$logo64 = file_exists($logoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath)) : '';

$moisFr = [1=>'janvier',2=>'février',3=>'mars',4=>'avril',5=>'mai',6=>'juin',7=>'juillet',
           8=>'août',9=>'septembre',10=>'octobre',11=>'novembre',12=>'décembre'];

/* Reconstruit la liste (code -> data) comme les autres générateurs */
$records = [];
foreach ($DATA['certifs'] as $c) { [$d,$m,$t,$dom,$du,$tel,$tp,$mods]=$c; $records[]=compact('d','m','t','dom','du','tel','tp')+['samedi'=>0]; }
foreach ($DATA['samedis'] as $s){ [$d,$m,$t,$dom,$du,$tel,$tp]=$s;       $records[]=compact('d','m','t','dom','du','tel','tp')+['samedi'=>1]; }
usort($records, fn($a,$b)=>strcmp($a['d'],$b['d']));
$seen=[]; foreach($records as $i=>$r){ $code='IBIG-'.str_replace('-','',$r['d']); if(isset($seen[$code])){$seen[$code]++;$code.=chr(64+$seen[$code]);}else{$seen[$code]=1;} $records[$i]['code']=$code; }

$want = $argv[1] ?? 'IBIG-20260810B';
$r = null; foreach($records as $x){ if($x['code']===$want){ $r=$x; break; } }
if(!$r){ fwrite(STDERR,"Code introuvable: $want\n"); exit(1); }

$b = $LAND[$want] ?? [];
$titre  = $r['t'];
$domaine= $r['dom'];
$duree  = $r['du'];
$isSam  = $r['samedi'];
$tel    = (int)$r['tel'];
$tp     = (int)$r['tp'];
$jour   = (int)substr($r['d'],8,2);
$moisNum= (int)substr($r['d'],5,2);
$moisTxt= mb_strtoupper($moisFr[$moisNum],'UTF-8');
$dateFr = sprintf('%02d/%02d/2026',$jour,$moisNum);

/* Titres de modules (4 max pour rester aéré) */
$mods = [];
if (!empty($b['contenu_programme']) && preg_match_all('/^\s*MODULE\s+\d+\s*[–\-]\s*(.+?)\s*$/mu',$b['contenu_programme'],$mm)) {
  $mods = array_slice(array_map('trim',$mm[1]), 0, 4);
}

$categorie = $isSam ? 'SAMEDI PRO' : 'FORMATION CERTIFIANTE';
$accent    = $isSam ? '#0EA5A5' : '#E8232C';       // sarcelle pour Samedi, rouge pour certif
$dureeH    = $isSam ? ($duree==='7H'?'1 journée · 7 h':'2 journées · 14 h') : ($duree==='65H'?'65 heures · parcours complet':($duree==='25H'?'25 heures':'20 heures'));

/* Offre anticipée (montant selon tranche pour les certifs) */
$eb = 0;
if(!$isSam){ $tier=max($tp,$tel); $eb = ($tier>=250000)?20000:15000; }
$offre = $isSam ? '−10 % en réservation anticipée' : ('−'.number_format($eb,0,',',' ').' FCFA · Offre Anticipée');

/* Promesse courte pour la bande valeur */
$promesse = (string)($b['promesse'] ?? '');
$promesseCourt = mb_substr($promesse, 0, 155, 'UTF-8');
if (mb_strlen($promesse,'UTF-8') > 155) { $promesseCourt = rtrim($promesseCourt) . '…'; }
$perk1 = $isSam ? 'Attestation IBIG EDUFORM' : 'Certification reconnue';

function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function fcfa($n){ return number_format((int)$n,0,',',' ').' FCFA'; }
?><!doctype html><html lang="fr"><head><meta charset="utf-8">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  *{margin:0;padding:0;box-sizing:border-box}
  body{width:1080px;height:1350px;font-family:'Inter',system-ui,sans-serif;color:#0f172a;background:#fff;overflow:hidden}
  .flyer{width:1080px;height:1350px;position:relative;display:flex;flex-direction:column;background:
     radial-gradient(1200px 500px at 85% -8%, rgba(29,78,216,.10), transparent 60%),
     radial-gradient(1000px 500px at -10% 110%, rgba(245,158,11,.10), transparent 60%), #ffffff}
  /* HEADER */
  .head{display:flex;align-items:center;justify-content:space-between;padding:40px 56px 24px}
  .brand{display:flex;align-items:center;gap:18px}
  .brand img{width:96px;height:96px;object-fit:contain}
  .brand .n1{font-family:'Montserrat',system-ui,sans-serif;font-weight:900;font-size:30px;color:#0A2A66;letter-spacing:.5px;line-height:1}
  .brand .n2{font-size:15px;color:#475569;font-weight:600;margin-top:6px}
  .sarl{text-align:right;font-size:12.5px;color:#64748b;line-height:1.5;max-width:250px}
  .sarl b{color:#0A2A66;font-family:'Montserrat',system-ui,sans-serif;font-weight:800;display:block;font-size:14px}
  /* RIBBON */
  .ribbon{position:absolute;top:34px;right:-64px;transform:rotate(45deg);background:<?= $accent ?>;color:#fff;
     font-family:'Montserrat',system-ui,sans-serif;font-weight:800;font-size:16px;letter-spacing:1.5px;padding:12px 80px;box-shadow:0 10px 24px rgba(0,0,0,.18)}
  /* HERO */
  .hero{margin:8px 56px 0;background:linear-gradient(135deg,#0A2A66,#123a86);border-radius:28px;padding:44px 48px;color:#fff;position:relative;overflow:hidden}
  .hero::after{content:"";position:absolute;right:-60px;top:-60px;width:240px;height:240px;border-radius:50%;background:rgba(255,255,255,.06)}
  .cat{display:inline-block;background:<?= $accent ?>;color:#fff;font-family:'Montserrat',system-ui,sans-serif;font-weight:800;font-size:15px;letter-spacing:1.5px;padding:9px 18px;border-radius:999px}
  .title{font-family:'Montserrat',system-ui,sans-serif;font-weight:900;font-size:<?= mb_strlen($titre)>40?'50px':'60px' ?>;line-height:1.05;margin:22px 0 20px}
  .chips{display:flex;gap:12px;flex-wrap:wrap}
  .chip{background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.22);border-radius:999px;padding:11px 20px;font-size:18px;font-weight:600;display:flex;align-items:center;gap:9px}
  .chip.hy{background:rgba(245,158,11,.20);border-color:rgba(245,158,11,.5);color:#ffd98a}
  /* DATE BADGE */
  .datebar{display:flex;align-items:center;gap:20px;margin:26px 56px 0}
  .datebox{background:<?= $accent ?>;color:#fff;border-radius:22px;padding:20px 26px;text-align:center;min-width:150px;box-shadow:0 14px 30px rgba(232,35,44,.28)}
  .datebox .m{font-family:'Montserrat',system-ui,sans-serif;font-weight:800;font-size:20px;letter-spacing:2px}
  .datebox .d{font-family:'Montserrat',system-ui,sans-serif;font-weight:900;font-size:40px;line-height:1}
  .sessline{font-size:22px;color:#0A2A66;font-weight:700}
  .sessline small{display:block;color:#64748b;font-weight:500;font-size:17px;margin-top:4px}
  /* PROGRAMME */
  .prog{margin:28px 56px 0}
  .prog h3{font-family:'Montserrat',system-ui,sans-serif;font-weight:800;font-size:24px;color:#0A2A66;margin-bottom:16px;display:flex;align-items:center;gap:12px}
  .prog h3::before{content:"";width:34px;height:5px;border-radius:3px;background:<?= $accent ?>}
  .mods{display:grid;grid-template-columns:1fr 1fr;gap:14px 26px}
  .mod{display:flex;align-items:flex-start;gap:13px;font-size:19px;color:#1e293b;line-height:1.35}
  .mod i{flex:none;width:30px;height:30px;border-radius:8px;background:rgba(29,78,216,.12);color:#1D4ED8;font-weight:800;font-family:'Montserrat',system-ui,sans-serif;display:flex;align-items:center;justify-content:center;font-size:16px;margin-top:2px}
  /* PRICE */
  .price{margin:30px 56px 0;display:flex;gap:20px;align-items:stretch}
  .pcard{flex:1;background:#F4F7FC;border:1.5px solid #e3ebf6;border-radius:20px;padding:22px 26px}
  .pcard .lab{font-size:16px;color:#64748b;font-weight:600}
  .pcard .val{font-family:'Montserrat',system-ui,sans-serif;font-weight:900;font-size:34px;color:#0A2A66;margin-top:4px}
  .pcard.on .val{color:#1D4ED8}
  .offer{display:flex;align-items:center;gap:12px;background:linear-gradient(135deg,#FFF3D6,#FFE7AE);border:1.5px solid #F5C451;border-radius:20px;padding:0 26px;font-family:'Montserrat',system-ui,sans-serif;font-weight:800;color:#8a5a00;font-size:19px;min-width:250px;justify-content:center;text-align:center}
  /* VALEUR */
  .value{margin:26px 56px 0;background:linear-gradient(135deg,#F4F7FC,#eaf1ff);border:1.5px solid #dbe5f7;border-radius:22px;padding:26px 32px}
  .value .promesse{font-size:21px;line-height:1.45;color:#0A2A66;font-weight:600}
  .value .perks{display:flex;gap:14px;flex-wrap:wrap;margin-top:20px}
  .value .perks span{background:#fff;border:1.5px solid #e3ebf6;border-radius:12px;padding:13px 20px;font-weight:700;font-size:17px;color:#0A2A66;display:flex;align-items:center;gap:9px}
  /* FOOTER */
  .foot{margin-top:auto;background:#0A2A66;color:#dbe5f7;padding:26px 56px;display:flex;align-items:center;justify-content:space-between;font-size:18px}
  .foot .cta{background:<?= $accent ?>;color:#fff;font-family:'Montserrat',system-ui,sans-serif;font-weight:800;padding:14px 26px;border-radius:12px;font-size:19px}
  .foot .co b{color:#fff}
</style></head>
<body>
  <div class="flyer">
    <div class="head">
      <div class="brand">
        <?php if($logo64): ?><img src="<?= $logo64 ?>" alt="IBIG EDUFORM"><?php endif; ?>
        <div><div class="n1">IBIG EDUFORM</div><div class="n2">Institut International de Formation Professionnelle</div></div>
      </div>
      <div class="sarl"><b>IBIG SARL</b>INTERMARK BUSINESS INTERNATIONAL GROUP SARL</div>
    </div>

    <div class="hero">
      <span class="cat"><?= e($categorie) ?></span>
      <div class="title"><?= e($titre) ?></div>
      <div class="chips">
        <span class="chip">📁 <?= e($domaine) ?></span>
        <span class="chip">⏱ <?= e($dureeH) ?></span>
        <span class="chip hy">🌐 Hybride · en ligne &amp; présentiel</span>
      </div>
    </div>

    <div class="datebar">
      <div class="datebox"><div class="m"><?= e($moisTxt) ?></div><div class="d"><?= $jour ?></div></div>
      <div class="sessline">Démarrage le <?= e($dateFr) ?><small>Session <?= e(mb_convert_case($moisFr[$moisNum],MB_CASE_TITLE,'UTF-8')) ?> 2026 · Abidjan &amp; en ligne</small></div>
    </div>

    <?php if($mods): ?>
    <div class="prog">
      <h3>Au programme</h3>
      <div class="mods">
        <?php foreach($mods as $k=>$m): ?>
          <div class="mod"><i><?= $k+1 ?></i><span><?= e($m) ?></span></div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <div class="price">
      <div class="pcard on"><div class="lab">💻 En ligne</div><div class="val"><?= fcfa($tel) ?></div></div>
      <div class="pcard"><div class="lab">🏫 Présentiel</div><div class="val"><?= fcfa($tp) ?></div></div>
      <div class="offer">🐦<br><?= e($offre) ?></div>
    </div>

    <div class="value">
      <div class="promesse"><?= e($promesseCourt) ?></div>
      <div class="perks">
        <span>🎓 <?= e($perk1) ?></span>
        <span>👨‍🏫 Formateurs praticiens</span>
        <span>📄 Supports &amp; modèles fournis</span>
        <span>🌐 En ligne ou à Abidjan</span>
      </div>
    </div>

    <div class="foot">
      <div class="co">📞 <b>+225 07 78 88 25 92</b> &nbsp;·&nbsp; 🌍 <b>ibig-eduform.com</b></div>
      <div class="cta">Préinscription ouverte</div>
    </div>
  </div>
</body></html>
