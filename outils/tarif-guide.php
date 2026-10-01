<?php
declare(strict_types=1);
/**
 * IBIG EDUFORM — /outils/tarif-guide.php
 * Guide tarifaire de référence — document interne IBIG EDUFORM.
 */
require_once __DIR__ . '/../core/auth.php';
if (!auth_check()) { http_response_code(403); exit('Accès non autorisé.'); }

$configFile = __DIR__ . '/tarif-config.json';
$cfg = json_decode(file_get_contents($configFile), true) ?? [];

/* ── Sauvegarde config ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_config'])) {
    $newCfg = json_decode($_POST['json_config'] ?? '{}', true);
    if ($newCfg) {
        $newCfg['date_maj'] = date('Y-m-d');
        file_put_contents($configFile, json_encode($newCfg, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $cfg = $newCfg;
        $saved = true;
    }
}

function fmt(int $n): string { return number_format($n, 0, ',', ' '); }
function fmtF(int $n): string { return fmt($n) . ' F'; }
?><!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Guide Tarifaire — IBIG EDUFORM</title>
<style>
:root{--navy:#0d1f3c;--amber:#f59e0b;--amber-lt:#fef3c7;--bg:#f0f4f8;--card:#fff;--border:#dde3eb;--txt:#1e293b;--muted:#64748b;--green:#059669;--red:#dc2626;--blue:#2563eb;--purple:#7c3aed;}
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Segoe UI',Arial,sans-serif;background:var(--bg);color:var(--txt);font-size:14px;}
.top-bar{background:var(--navy);color:#fff;padding:10px 24px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:100;}
.top-bar .logo{display:flex;align-items:center;gap:10px;font-weight:700;font-size:15px;}
.top-bar nav a{color:#fbbf24;text-decoration:none;font-size:12px;font-weight:600;margin-left:16px;}
.top-bar nav a:hover{text-decoration:underline;}
.layout{display:flex;gap:0;min-height:calc(100vh - 46px);}
.sidebar{width:220px;min-width:220px;background:var(--navy);padding:20px 0;position:sticky;top:46px;height:calc(100vh - 46px);overflow-y:auto;}
.sidebar a{display:flex;align-items:center;gap:8px;padding:9px 18px;color:#cbd5e1;text-decoration:none;font-size:12.5px;border-left:3px solid transparent;transition:all .15s;}
.sidebar a:hover,.sidebar a.active{background:rgba(255,255,255,.08);color:#fbbf24;border-left-color:#f59e0b;}
.sidebar .sidebar-lbl{font-size:10px;text-transform:uppercase;letter-spacing:.8px;color:#475569;padding:16px 18px 6px;font-weight:700;}
.main{flex:1;padding:28px 32px;max-width:960px;}
.doc-header{background:linear-gradient(135deg,var(--navy) 0%,#1a3a6e 100%);color:#fff;padding:28px 32px;border-radius:12px;margin-bottom:28px;position:relative;overflow:hidden;}
.doc-header::after{content:'CONFIDENTIEL';position:absolute;right:20px;top:12px;font-size:10px;letter-spacing:2px;color:rgba(255,255,255,.3);font-weight:700;}
.doc-header h1{font-size:22px;font-weight:800;margin-bottom:4px;}
.doc-header .meta{display:flex;gap:20px;margin-top:12px;flex-wrap:wrap;}
.doc-header .meta span{font-size:12px;color:#94a3b8;}
.doc-header .meta strong{color:#fbbf24;}
.section{background:var(--card);border:1px solid var(--border);border-radius:10px;padding:22px 24px;margin-bottom:20px;scroll-margin-top:60px;}
.section-hd{display:flex;align-items:center;gap:10px;margin-bottom:18px;padding-bottom:12px;border-bottom:2px solid var(--border);}
.section-hd .ico{width:36px;height:36px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0;}
.section-hd h2{font-size:16px;font-weight:700;color:var(--navy);}
.section-hd .badge-ref{background:var(--amber-lt);color:#92400e;font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;letter-spacing:.5px;}
table{width:100%;border-collapse:collapse;font-size:13px;}
th{background:#f1f5f9;color:var(--muted);font-size:11px;text-transform:uppercase;letter-spacing:.4px;padding:8px 10px;text-align:left;border-bottom:1px solid var(--border);}
td{padding:9px 10px;border-bottom:1px solid #f0f4f8;vertical-align:middle;}
tr:last-child td{border-bottom:none;}
tr:hover td{background:#f8fafc;}
.td-price{font-weight:700;color:var(--navy);white-space:nowrap;}
.td-devis{color:var(--muted);font-style:italic;font-size:12px;}
.grid2{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
.grid3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;}
@media(max-width:700px){.grid2,.grid3{grid-template-columns:1fr;}.sidebar{display:none;}}
.card-sm{background:#f8fafc;border:1px solid var(--border);border-radius:8px;padding:14px 16px;}
.card-sm .card-title{font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);margin-bottom:6px;font-weight:700;}
.card-sm .card-val{font-size:18px;font-weight:800;color:var(--navy);}
.card-sm .card-note{font-size:11px;color:var(--muted);margin-top:4px;}
.tag{display:inline-block;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:700;}
.tag-green{background:#d1fae5;color:#065f46;}
.tag-amber{background:var(--amber-lt);color:#92400e;}
.tag-blue{background:#dbeafe;color:#1e40af;}
.tag-purple{background:#ede9fe;color:#5b21b6;}
.tag-red{background:#fee2e2;color:#991b1b;}
.tag-gray{background:#f1f5f9;color:#374151;}
.check-list{list-style:none;display:flex;flex-direction:column;gap:6px;}
.check-list li{display:flex;align-items:flex-start;gap:8px;font-size:13px;}
.check-list li::before{content:'✓';color:var(--green);font-weight:900;flex-shrink:0;margin-top:1px;}
.cross-list li::before{content:'✗';color:var(--red);}
.remise-item{display:flex;align-items:flex-start;gap:12px;padding:10px;border-radius:6px;border:1px solid var(--border);margin-bottom:8px;}
.remise-val{font-size:20px;font-weight:900;color:var(--navy);min-width:44px;text-align:center;}
.remise-body .remise-type{font-weight:700;font-size:13px;color:var(--navy);}
.remise-body .remise-cond{font-size:12px;color:var(--muted);margin-top:2px;}
.remise-cumul{margin-left:auto;flex-shrink:0;}
.garantie-item{display:flex;gap:12px;padding:12px;border-radius:8px;border-left:3px solid var(--green);background:#f0fdf4;margin-bottom:10px;}
.garantie-ico{font-size:20px;flex-shrink:0;}
.garantie-body .garantie-label{font-weight:700;font-size:13px;color:#065f46;}
.garantie-body .garantie-detail{font-size:12px;color:#047857;margin-top:3px;}
.annul-row{display:flex;align-items:center;gap:12px;padding:10px 12px;border-radius:6px;border:1px solid var(--border);margin-bottom:8px;}
.annul-penalite{font-size:18px;font-weight:900;min-width:52px;text-align:center;}
.annul-body .annul-delai{font-weight:700;font-size:12px;color:var(--navy);}
.annul-body .annul-note{font-size:12px;color:var(--muted);margin-top:2px;}
.contact-block{display:flex;flex-direction:column;gap:8px;}
.contact-item{display:flex;align-items:center;gap:10px;font-size:13px;}
.contact-item a{color:var(--navy);font-weight:600;text-decoration:none;}
.contact-item a:hover{color:var(--amber);}
.contact-badge{padding:2px 8px;border-radius:4px;font-size:10px;font-weight:700;}
.legal-block{background:#f8fafc;border-radius:8px;padding:16px;border:1px solid var(--border);}
.legal-block .legal-title{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);margin-bottom:8px;}
.legal-block p{font-size:12.5px;color:var(--txt);line-height:1.6;}
.pack-card{border:1px solid var(--border);border-radius:8px;padding:16px;text-align:center;position:relative;}
.pack-card .pack-h{font-size:20px;font-weight:900;color:var(--navy);}
.pack-card .pack-h2{font-size:13px;font-weight:700;color:var(--muted);margin-bottom:8px;}
.pack-card .pack-price{font-size:24px;font-weight:900;color:var(--amber);margin:10px 0;}
.pack-card .pack-hr{font-size:11px;color:var(--muted);}
.pack-card .pack-valid{font-size:11px;color:var(--green);font-weight:700;margin-top:6px;}
.pack-card .pack-note{font-size:11px;color:var(--muted);margin-top:6px;}
.pack-card.best{border-color:var(--amber);box-shadow:0 0 0 2px var(--amber-lt);}
.pack-card .best-badge{position:absolute;top:-10px;left:50%;transform:translateX(-50%);background:var(--amber);color:#fff;font-size:10px;font-weight:700;padding:2px 10px;border-radius:10px;white-space:nowrap;}
.simulateur{background:var(--amber-lt);border:1px solid #fde68a;border-radius:8px;padding:16px;margin-top:16px;}
.simulateur label{font-size:12px;font-weight:700;color:#92400e;display:block;margin-bottom:4px;}
.simulateur select,.simulateur input{padding:7px 10px;border:1px solid #fde68a;border-radius:6px;font-size:13px;background:#fff;width:100%;margin-bottom:10px;}
.sim-result{background:var(--navy);color:#fff;border-radius:6px;padding:12px 16px;text-align:center;font-size:14px;font-weight:700;}
.alert{padding:12px 16px;border-radius:8px;margin-bottom:16px;font-size:13px;}
.alert-success{background:#d1fae5;color:#065f46;border:1px solid #a7f3d0;}
.alert-info{background:#dbeafe;color:#1e40af;border:1px solid #bfdbfe;}
.btn-edit{background:transparent;border:1px solid var(--border);border-radius:6px;padding:5px 12px;font-size:12px;cursor:pointer;color:var(--muted);}
.btn-edit:hover{background:#f1f5f9;color:var(--navy);}
.btn-save{background:var(--navy);color:#fff;border:none;border-radius:6px;padding:7px 18px;font-size:13px;font-weight:700;cursor:pointer;}
.json-editor{width:100%;height:200px;font-family:monospace;font-size:12px;border:1px solid var(--border);border-radius:6px;padding:8px;margin-top:10px;display:none;}
.devise-row{display:flex;gap:10px;flex-wrap:wrap;margin-top:10px;}
.devise-pill{display:flex;align-items:center;gap:6px;background:#f1f5f9;border-radius:20px;padding:4px 12px;font-size:12px;font-weight:700;}
.service-card{border:1px solid var(--border);border-radius:8px;padding:16px;margin-bottom:14px;}
.service-card .sc-title{font-weight:700;font-size:13px;color:var(--navy);margin-bottom:10px;display:flex;align-items:center;gap:8px;}
</style>
</head>
<body>

<div class="top-bar">
  <div class="logo">
    <img src="../assets/images/logo.png" alt="" style="height:26px" onerror="this.style.display='none'">
    IBIG EDUFORM &middot; Guide Tarifaire
  </div>
  <nav>
    <a href="tdr-generator.php">G&eacute;n&eacute;rateur TDR</a>
    <a href="tdr-historique.php">Historique</a>
  </nav>
</div>

<div class="layout">
<aside class="sidebar">
  <div class="sidebar-lbl">Navigation</div>
  <a href="#grille">📊 Grille horaire</a>
  <a href="#individuel">👤 Individuel</a>
  <a href="#inter">🏛️ Inter-entreprises</a>
  <a href="#groupe">👥 Groupe</a>
  <a href="#intra">🏢 Intra-entreprise</a>
  <a href="#packs">📦 Packs entreprise</a>
  <a href="#services">🔧 Services connexes</a>
  <a href="#international">🌍 International</a>
  <a href="#remises">🎁 Remises</a>
  <a href="#annulation">🔁 Annulation / Report</a>
  <a href="#paiement">💳 Paiement</a>
  <a href="#inclus">✅ Inclus / Non inclus</a>
  <a href="#garanties">🛡️ Garanties qualité</a>
  <a href="#legal">⚖️ Cadre fiscal &amp; légal</a>
  <a href="#contact">📞 Contacts</a>
</aside>

<main class="main">

<?php if (!empty($saved)): ?>
<div class="alert alert-success">✅ Configuration sauvegardée avec succès — <?= date('d/m/Y à H:i') ?></div>
<?php endif; ?>

<div class="doc-header">
  <h1>Guide Tarifaire de Référence</h1>
  <p style="color:#94a3b8;font-size:13px;margin-top:4px">IBIG EDUFORM &mdash; Document interne &middot; Référence pour l'établissement de tous TDR et devis</p>
  <div class="meta">
    <span>Version <strong><?= htmlspecialchars($cfg['version'] ?? '—') ?></strong></span>
    <span>Mis à jour le <strong><?= htmlspecialchars($cfg['date_maj'] ?? '—') ?></strong></span>
    <span>Espace <strong>OHADA (17 pays)</strong></span>
    <span>Tarifs en <strong>F CFA (XOF)</strong></span>
  </div>
</div>

<!-- ① GRILLE HORAIRE -->
<div class="section" id="grille">
  <div class="section-hd">
    <div class="ico" style="background:#dbeafe">📊</div>
    <h2>Grille tarifaire par volume horaire</h2>
    <span class="badge-ref">RÉFÉRENCE PRINCIPALE</span>
  </div>
  <p style="font-size:12.5px;color:var(--muted);margin-bottom:14px">Tarifs par personne — formation individuelle. Le tarif applicable est déterminé par la durée totale du parcours.</p>
  <table>
    <thead><tr><th>Parcours</th><th>Durée</th><th>Séances</th><th>🖥️ En ligne</th><th>🏫 Présentiel</th><th>🔀 Hybride</th></tr></thead>
    <tbody>
      <?php foreach ($cfg['tranches_horaires'] ?? [] as $t): ?>
      <tr>
        <td style="font-weight:700"><?= htmlspecialchars($t['label']) ?></td>
        <td><span class="tag tag-blue"><?= htmlspecialchars($t['duree']) ?></span></td>
        <td style="color:var(--muted);font-size:12px"><?= htmlspecialchars($t['seances']) ?></td>
        <td class="td-price"><?= fmtF($t['en_ligne']) ?></td>
        <td class="td-price"><?= fmtF($t['presentiel']) ?></td>
        <td class="td-price"><?= fmtF($t['hybride']) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- ② INDIVIDUEL -->
<div class="section" id="individuel">
  <div class="section-hd">
    <div class="ico" style="background:#d1fae5">👤</div>
    <h2>Formations individuelles — tarifs minimaux par modalité</h2>
  </div>
  <div class="grid3">
    <?php foreach ($cfg['individuel'] ?? [] as $k => $v): ?>
    <div class="card-sm">
      <div class="card-title"><?= htmlspecialchars($v['label']) ?></div>
      <div class="card-val">≥ <?= fmtF($v['min']) ?></div>
      <div class="card-note"><?= htmlspecialchars($v['note']) ?></div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- ③ INTER-ENTREPRISES -->
<?php $inter = $cfg['inter_entreprises'] ?? []; ?>
<div class="section" id="inter">
  <div class="section-hd">
    <div class="ico" style="background:#ede9fe">🏛️</div>
    <h2>Sessions inter-entreprises (publiques)</h2>
  </div>
  <div class="alert alert-info"><?= htmlspecialchars($inter['description'] ?? '') ?></div>
  <table>
    <thead><tr><th>Formule</th><th>Tarif / personne</th><th>Places max</th><th>Note</th></tr></thead>
    <tbody>
      <?php foreach ($inter['tranches'] ?? [] as $t): ?>
      <tr>
        <td style="font-weight:700"><?= htmlspecialchars($t['label']) ?></td>
        <td class="td-price"><?= fmtF($t['tarif_par_pers']) ?></td>
        <td><span class="tag tag-gray"><?= $t['nb_max'] ?> pers. max</span></td>
        <td style="font-size:12px;color:var(--muted)"><?= htmlspecialchars($t['note']) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php if (!empty($inter['avantages'])): ?>
  <div style="margin-top:12px;padding:10px;background:#f8fafc;border-radius:6px;font-size:12.5px;color:var(--muted)">
    <strong>Avantages :</strong> <?= htmlspecialchars($inter['avantages']) ?>
  </div>
  <?php endif; ?>
  <p style="font-size:12px;color:var(--muted);margin-top:10px;font-style:italic"><?= htmlspecialchars($inter['note'] ?? '') ?></p>
</div>

<!-- ④ GROUPE -->
<div class="section" id="groupe">
  <div class="section-hd">
    <div class="ico" style="background:#fef3c7">👥</div>
    <h2>Formations en groupe</h2>
    <span class="badge-ref">NE PAS AFFICHER PUBLIQUEMENT</span>
  </div>
  <table>
    <thead><tr><th>Tranche</th><th>Remise en ligne</th><th>Remise présentiel</th><th>Note</th></tr></thead>
    <tbody>
      <?php foreach ($cfg['groupe']['tranches'] ?? [] as $t): ?>
      <tr>
        <td style="font-weight:700"><?= htmlspecialchars($t['label']) ?></td>
        <td class="td-price"><?= $t['remise_en_ligne'] > 0 ? '-'.$t['remise_en_ligne'].'%' : '<span class="td-devis">Sur devis</span>' ?></td>
        <td class="td-price"><?= $t['remise_presentiel'] > 0 ? '-'.$t['remise_presentiel'].'%' : '<span class="td-devis">Sur devis</span>' ?></td>
        <td style="font-size:12px;color:var(--muted)"><?= htmlspecialchars($t['note']) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <div class="simulateur">
    <strong style="font-size:13px;color:#92400e">Simulateur de tarif groupe</strong>
    <div class="grid2" style="margin-top:10px">
      <div>
        <label>Nombre de participants</label>
        <input type="number" id="sim_nb" min="3" max="200" value="6">
      </div>
      <div>
        <label>Parcours de référence</label>
        <select id="sim_parcours">
          <?php foreach ($cfg['tranches_horaires'] ?? [] as $t): ?>
          <option value="<?= $t['presentiel'] ?>" data-ol="<?= $t['en_ligne'] ?>"><?= htmlspecialchars($t['label']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div id="sim_result" class="sim-result">Renseignez les champs ci-dessus</div>
  </div>
</div>

<!-- ⑤ INTRA -->
<div class="section" id="intra">
  <div class="section-hd">
    <div class="ico" style="background:#fee2e2">🏢</div>
    <h2>Formations intra-entreprise</h2>
  </div>
  <?php $intra = $cfg['intra_entreprise'] ?? []; ?>
  <?php if (!empty($intra['inclus'])): ?>
  <div class="alert alert-info"><strong>Inclus dans le forfait :</strong> <?= htmlspecialchars($intra['inclus']) ?></div>
  <?php endif; ?>
  <table>
    <thead><tr><th>Configuration</th><th>Fourchette forfait</th></tr></thead>
    <tbody>
      <?php foreach ($intra['tranches'] ?? [] as $t): ?>
      <tr>
        <td style="font-weight:700"><?= htmlspecialchars($t['label']) ?></td>
        <td class="td-price">
          <?= $t['forfait_min'] > 0
            ? fmtF($t['forfait_min']) . ' – ' . fmtF($t['forfait_max'])
            : '<span class="td-devis">Sur devis uniquement</span>' ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php if (!empty($intra['note'])): ?><p style="font-size:12px;color:var(--muted);margin-top:10px;font-style:italic"><?= htmlspecialchars($intra['note']) ?></p><?php endif; ?>
</div>

<!-- ⑥ PACKS -->
<?php $packs = $cfg['packs_entreprise'] ?? []; ?>
<div class="section" id="packs">
  <div class="section-hd">
    <div class="ico" style="background:#dbeafe">📦</div>
    <h2>Packs entreprise — volume d'heures</h2>
  </div>
  <p style="font-size:12.5px;color:var(--muted);margin-bottom:16px"><?= htmlspecialchars($packs['description'] ?? '') ?></p>
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px">
    <?php foreach ($packs['tranches'] ?? [] as $i => $t): ?>
    <div class="pack-card <?= $i===1?'best':'' ?>">
      <?php if ($i===1): ?><div class="best-badge">MEILLEURE VALEUR</div><?php endif; ?>
      <div class="pack-h"><?= $t['heures'] ?>h</div>
      <div class="pack-h2"><?= htmlspecialchars($t['label']) ?></div>
      <div class="pack-price"><?= $t['tarif_total'] > 0 ? fmtF($t['tarif_total']) : 'Sur devis' ?></div>
      <div class="pack-hr"><?= $t['tarif_total'] > 0 ? fmtF($t['tarif_heure']) . '/heure' : '' ?></div>
      <div class="pack-valid">Validité : <?= htmlspecialchars($t['validite']) ?></div>
      <div class="pack-note"><?= htmlspecialchars($t['note']) ?></div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php if (!empty($packs['conditions'])): ?>
  <div style="margin-top:14px;padding:10px 14px;background:#f8fafc;border-radius:6px;font-size:12px;color:var(--muted)">
    <strong>Conditions :</strong> <?= htmlspecialchars($packs['conditions']) ?>
  </div>
  <?php endif; ?>
</div>

<!-- ⑦ SERVICES CONNEXES -->
<?php $sc = $cfg['services_connexes'] ?? []; ?>
<div class="section" id="services">
  <div class="section-hd">
    <div class="ico" style="background:#f3e8ff">🔧</div>
    <h2>Services connexes</h2>
  </div>
  <?php if (!empty($sc['coaching'])): $coaching = $sc['coaching']; ?>
  <div class="service-card">
    <div class="sc-title"><span class="tag tag-purple">Coaching</span> <?= htmlspecialchars($coaching['label']) ?></div>
    <table><thead><tr><th>Formule</th><th>En ligne</th><th>Présentiel</th></tr></thead><tbody>
      <?php foreach ($coaching['tarifs'] as $t): ?>
      <tr><td><?= htmlspecialchars($t['label']) ?></td><td class="td-price"><?= fmtF($t['en_ligne']) ?></td><td class="td-price"><?= fmtF($t['presentiel']) ?></td></tr>
      <?php endforeach; ?>
    </tbody></table>
    <p style="font-size:12px;color:var(--muted);margin-top:8px"><?= htmlspecialchars($coaching['note']) ?></p>
  </div>
  <?php endif; ?>
  <?php if (!empty($sc['ingenierie'])): $ing = $sc['ingenierie']; ?>
  <div class="service-card">
    <div class="sc-title"><span class="tag tag-blue">Ingénierie</span> <?= htmlspecialchars($ing['label']) ?></div>
    <table><thead><tr><th>Prestation</th><th>Tarif</th><th>Note</th></tr></thead><tbody>
      <?php foreach ($ing['tarifs'] as $t): ?>
      <tr><td><?= htmlspecialchars($t['label']) ?></td><td class="td-price"><?= fmtF($t['tarif']) ?></td><td style="font-size:12px;color:var(--muted)"><?= htmlspecialchars($t['note']) ?></td></tr>
      <?php endforeach; ?>
    </tbody></table>
    <p style="font-size:12px;color:var(--muted);margin-top:8px"><?= htmlspecialchars($ing['note']) ?></p>
  </div>
  <?php endif; ?>
  <?php if (!empty($sc['audit_competences'])): $audit = $sc['audit_competences']; ?>
  <div class="service-card">
    <div class="sc-title"><span class="tag tag-green">Bilan</span> <?= htmlspecialchars($audit['label']) ?></div>
    <table><thead><tr><th>Formule</th><th>En ligne</th><th>Présentiel</th></tr></thead><tbody>
      <?php foreach ($audit['tarifs'] as $t): ?>
      <tr>
        <td><?= htmlspecialchars($t['label']) ?></td>
        <td class="td-price"><?= $t['en_ligne'] > 0 ? fmtF($t['en_ligne']) : '<span class="td-devis">Sur devis</span>' ?></td>
        <td class="td-price"><?= $t['presentiel'] > 0 ? fmtF($t['presentiel']) : '<span class="td-devis">Sur devis</span>' ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody></table>
    <p style="font-size:12px;color:var(--muted);margin-top:8px"><?= htmlspecialchars($audit['note']) ?></p>
  </div>
  <?php endif; ?>
</div>

<!-- ⑧ INTERNATIONAL -->
<?php $intl = $cfg['international'] ?? []; ?>
<div class="section" id="international">
  <div class="section-hd">
    <div class="ico" style="background:#d1fae5">🌍</div>
    <h2>Formations internationales</h2>
  </div>
  <table>
    <thead><tr><th>Zone géographique</th><th>Majoration</th><th>Devise</th><th>Note</th></tr></thead>
    <tbody>
      <?php foreach ($intl['zones'] ?? [] as $z): ?>
      <tr>
        <td style="font-weight:700"><?= htmlspecialchars($z['label']) ?></td>
        <td><?= $z['majoration'] > 0 ? '<span class="tag tag-amber">+' . $z['majoration'] . '%</span>' : '<span class="tag tag-green">Aucune</span>' ?></td>
        <td><?= $z['devise'] ? '<span class="tag tag-blue">' . htmlspecialchars($z['devise']) . '</span>' : '—' ?></td>
        <td style="font-size:12px;color:var(--muted)"><?= htmlspecialchars($z['note']) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php if (!empty($intl['taux_reference'])): $taux = $intl['taux_reference']; ?>
  <div class="devise-row">
    <div class="devise-pill">1 EUR = <strong><?= $taux['EUR'] ?> XOF</strong></div>
    <div class="devise-pill">1 USD ≈ <strong><?= $taux['USD'] ?> XOF</strong></div>
    <div class="devise-pill" style="color:var(--muted);font-weight:400;font-size:11px"><?= htmlspecialchars($taux['note']) ?></div>
  </div>
  <?php endif; ?>
  <?php if (!empty($intl['note'])): ?><p style="font-size:12px;color:var(--muted);margin-top:12px;font-style:italic"><?= htmlspecialchars($intl['note']) ?></p><?php endif; ?>
</div>

<!-- ⑨ REMISES -->
<div class="section" id="remises">
  <div class="section-hd">
    <div class="ico" style="background:#fee2e2">🎁</div>
    <h2>Politique de remises commerciales</h2>
  </div>
  <div class="alert alert-info">Remise maximale cumulable : <strong><?= $cfg['remise_max'] ?? 20 ?>%</strong> · Les remises cumulables s'additionnent dans la limite de ce plafond.</div>
  <?php foreach ($cfg['remises'] ?? [] as $r): ?>
  <div class="remise-item">
    <div class="remise-val"><?= $r['valeur'] > 0 ? $r['valeur'] . '%' : '<span style="font-size:14px">Accord</span>' ?></div>
    <div class="remise-body">
      <div class="remise-type"><?= htmlspecialchars($r['type']) ?></div>
      <div class="remise-cond"><?= htmlspecialchars($r['condition']) ?></div>
    </div>
    <div class="remise-cumul">
      <span class="tag <?= $r['cumulable'] ? 'tag-green' : 'tag-gray' ?>"><?= $r['cumulable'] ? 'Cumulable' : 'Non cumulable' ?></span>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- ⑩ ANNULATION / REPORT -->
<?php $annul = $cfg['annulation_report'] ?? []; ?>
<div class="section" id="annulation">
  <div class="section-hd">
    <div class="ico" style="background:#fef3c7">🔁</div>
    <h2>Politique d'annulation et de report</h2>
  </div>
  <?php foreach ($annul['regles'] ?? [] as $r): ?>
  <div class="annul-row">
    <div class="annul-penalite" style="color:<?= $r['penalite']===0?'var(--green)':($r['penalite']>=75?'var(--red)':'#d97706') ?>">
      <?= $r['penalite'] ?>%
    </div>
    <div class="annul-body">
      <div class="annul-delai"><?= htmlspecialchars($r['delai']) ?></div>
      <div class="annul-note"><?= htmlspecialchars($r['note']) ?></div>
    </div>
  </div>
  <?php endforeach; ?>
  <div class="grid2" style="margin-top:14px;gap:10px">
    <?php if (!empty($annul['force_majeure'])): ?>
    <div class="legal-block"><div class="legal-title">⚡ Force majeure</div><p><?= htmlspecialchars($annul['force_majeure']) ?></p></div>
    <?php endif; ?>
    <?php if (!empty($annul['report_gratuit'])): ?>
    <div class="legal-block" style="background:#f0fdf4;border-color:#a7f3d0;"><div class="legal-title" style="color:var(--green)">✅ Report de séance</div><p><?= htmlspecialchars($annul['report_gratuit']) ?></p></div>
    <?php endif; ?>
  </div>
</div>

<!-- ⑪ PAIEMENT -->
<?php $pay = $cfg['paiement'] ?? []; ?>
<div class="section" id="paiement">
  <div class="section-hd">
    <div class="ico" style="background:#dbeafe">💳</div>
    <h2>Conditions de paiement</h2>
  </div>
  <div class="grid2">
    <div>
      <h3 style="font-size:13px;font-weight:700;margin-bottom:10px;color:var(--navy)">Échéancier standard</h3>
      <?php foreach ($pay['tranches'] ?? [] as $t): ?>
      <div style="display:flex;align-items:flex-start;gap:10px;margin-bottom:10px;">
        <div style="font-size:20px;font-weight:900;color:var(--amber);min-width:44px"><?= $t['pourcentage'] ?>%</div>
        <div style="font-size:12.5px;color:var(--muted)"><?= htmlspecialchars($t['echeance']) ?></div>
      </div>
      <?php endforeach; ?>
      <?php if (!empty($pay['mensualites'])): $m = $pay['mensualites']; ?>
      <div style="padding:10px;background:#f8fafc;border-radius:6px;font-size:12px;margin-top:8px">
        <strong>Mensualités :</strong> <?= $m['nb'] ?> fois sans frais — <?= htmlspecialchars($m['condition']) ?>
      </div>
      <?php endif; ?>
    </div>
    <div>
      <h3 style="font-size:13px;font-weight:700;margin-bottom:10px;color:var(--navy)">Modes de paiement</h3>
      <ul class="check-list">
        <?php foreach ($pay['modes'] ?? [] as $mode): ?>
        <li><?= htmlspecialchars($mode) ?></li>
        <?php endforeach; ?>
      </ul>
      <?php if (!empty($pay['devises_acceptees'])): ?>
      <h3 style="font-size:13px;font-weight:700;margin:14px 0 8px;color:var(--navy)">Devises acceptées</h3>
      <div class="devise-row">
        <?php foreach ($pay['devises_acceptees'] as $d): ?>
        <div class="devise-pill"><strong><?= htmlspecialchars($d) ?></strong></div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
      <?php if (!empty($pay['validite_offre_jours'])): ?>
      <div style="margin-top:12px;padding:8px 12px;background:#fef3c7;border-radius:6px;font-size:12px">
        ⏳ Offre valable <strong><?= $pay['validite_offre_jours'] ?> jours</strong> à compter de la date d'émission du TDR
      </div>
      <?php endif; ?>
    </div>
  </div>
  <?php if (!empty($pay['note'])): ?><p style="font-size:12px;color:var(--muted);margin-top:12px;font-style:italic"><?= htmlspecialchars($pay['note']) ?></p><?php endif; ?>
</div>

<!-- ⑫ INCLUS / NON INCLUS -->
<div class="section" id="inclus">
  <div class="section-hd">
    <div class="ico" style="background:#d1fae5">✅</div>
    <h2>Ce qui est inclus dans nos tarifs</h2>
  </div>
  <div class="grid2">
    <div>
      <h3 style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--green);margin-bottom:10px">Inclus (sans surcoût)</h3>
      <ul class="check-list">
        <?php foreach ($cfg['inclus_forfait'] ?? [] as $item): ?>
        <li><?= htmlspecialchars($item) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <div>
      <h3 style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--red);margin-bottom:10px">Non inclus (en sus)</h3>
      <ul class="check-list cross-list">
        <?php foreach ($cfg['non_inclus'] ?? [] as $item): ?>
        <li><?= htmlspecialchars($item) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
</div>

<!-- ⑬ GARANTIES -->
<div class="section" id="garanties">
  <div class="section-hd">
    <div class="ico" style="background:#d1fae5">🛡️</div>
    <h2>Garanties qualité IBIG EDUFORM</h2>
  </div>
  <?php foreach ($cfg['garanties_qualite'] ?? [] as $g): ?>
  <div class="garantie-item">
    <div class="garantie-ico">🏅</div>
    <div class="garantie-body">
      <div class="garantie-label"><?= htmlspecialchars($g['label']) ?></div>
      <div class="garantie-detail"><?= htmlspecialchars($g['detail']) ?></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- ⑭ CADRE LÉGAL -->
<?php $legal = $cfg['cadre_fiscal_legal'] ?? []; ?>
<div class="section" id="legal">
  <div class="section-hd">
    <div class="ico" style="background:#ede9fe">⚖️</div>
    <h2>Cadre fiscal &amp; légal</h2>
  </div>
  <div class="grid2">
    <?php if (!empty($legal['statut_tva'])): ?>
    <div class="legal-block"><div class="legal-title">📋 Statut TVA</div><p><?= htmlspecialchars($legal['statut_tva']) ?></p></div>
    <?php endif; ?>
    <?php if (!empty($legal['convention'])): ?>
    <div class="legal-block"><div class="legal-title">📄 Convention de formation</div><p><?= htmlspecialchars($legal['convention']) ?></p></div>
    <?php endif; ?>
    <?php if (!empty($legal['attestation_fiscale'])): ?>
    <div class="legal-block"><div class="legal-title">🧾 Attestation fiscale</div><p><?= htmlspecialchars($legal['attestation_fiscale']) ?></p></div>
    <?php endif; ?>
    <?php if (!empty($legal['rc'])): ?>
    <div class="legal-block"><div class="legal-title">🏢 Identité légale</div><p><strong><?= htmlspecialchars($legal['rc']) ?></strong></p>
    <?php if (!empty($legal['note'])): ?><p style="margin-top:6px"><?= htmlspecialchars($legal['note']) ?></p><?php endif; ?></div>
    <?php endif; ?>
  </div>
</div>

<!-- ⑮ CONTACTS -->
<?php $contact = $cfg['contact'] ?? []; ?>
<div class="section" id="contact">
  <div class="section-hd">
    <div class="ico" style="background:#fef3c7">📞</div>
    <h2>Contacts IBIG EDUFORM</h2>
  </div>
  <div class="grid2">
    <div>
      <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);margin-bottom:10px">Emails</div>
      <div class="contact-block">
        <?php foreach ($contact['emails'] ?? [] as $email): ?>
        <div class="contact-item"><span style="color:var(--muted)">✉️</span><a href="mailto:<?= htmlspecialchars($email) ?>"><?= htmlspecialchars($email) ?></a></div>
        <?php endforeach; ?>
      </div>
      <?php if (!empty($contact['site'])): ?>
      <div class="contact-item" style="margin-top:10px"><span style="color:var(--muted)">🌐</span><a href="<?= htmlspecialchars($contact['site']) ?>" target="_blank"><?= htmlspecialchars($contact['site']) ?></a></div>
      <?php endif; ?>
    </div>
    <div>
      <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);margin-bottom:10px">Téléphones</div>
      <div class="contact-block">
        <?php
        $typeColors = ['Fixe'=>'#dbeafe','WhatsApp'=>'#d1fae5','Mobile'=>'#fef3c7'];
        $typeTxt    = ['Fixe'=>'#1e40af','WhatsApp'=>'#065f46','Mobile'=>'#92400e'];
        foreach ($contact['telephones'] ?? [] as $tel):
          $bg = $typeColors[$tel['type']] ?? '#f1f5f9';
          $clr = $typeTxt[$tel['type']] ?? '#374151';
        ?>
        <div class="contact-item">
          <span class="contact-badge" style="background:<?= $bg ?>;color:<?= $clr ?>"><?= htmlspecialchars($tel['type']) ?></span>
          <a href="tel:<?= preg_replace('/\s/','',$tel['numero']) ?>"><?= htmlspecialchars($tel['numero']) ?></a>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <div class="grid2" style="margin-top:14px">
    <?php if (!empty($contact['adresse'])): ?>
    <div style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--muted)"><span>📍</span> <?= htmlspecialchars($contact['adresse']) ?></div>
    <?php endif; ?>
    <?php if (!empty($contact['horaires'])): ?>
    <div style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--muted)"><span>🕐</span> <?= htmlspecialchars($contact['horaires']) ?></div>
    <?php endif; ?>
  </div>
</div>

<!-- Config JSON -->
<div class="section" style="border-style:dashed">
  <div class="section-hd">
    <div class="ico" style="background:#f1f5f9">⚙️</div>
    <h2>Modifier la configuration</h2>
  </div>
  <form method="post">
    <button type="button" class="btn-edit" onclick="var e=document.getElementById('jed');e.style.display='block';e.value=JSON.stringify(<?= json_encode($cfg) ?>,null,2);document.getElementById('sbw').style.display='block'">
      Éditer le JSON de configuration
    </button>
    <textarea id="jed" name="json_config" class="json-editor"></textarea>
    <input type="hidden" name="save_config" value="1">
    <div style="margin-top:10px;display:none" id="sbw"><button type="submit" class="btn-save">Sauvegarder</button></div>
  </form>
</div>

</main>
</div>

<script>
function simCalc(){
  const nb = parseInt(document.getElementById('sim_nb').value)||0;
  const sel = document.getElementById('sim_parcours');
  const tarPres = parseInt(sel.value)||0;
  const tarLigne = parseInt(sel.options[sel.selectedIndex].dataset.ol)||0;
  const res = document.getElementById('sim_result');
  const tranches = <?= json_encode(array_values($cfg['groupe']['tranches'] ?? [])) ?>;
  let remisePres = 0, remiseLigne = 0, label = '';
  for(const t of tranches){
    const m = t.label.match(/(\d+)\s*[àa]\s*(\d+)/);
    if(m && nb >= parseInt(m[1]) && nb <= parseInt(m[2])){
      remisePres = t.remise_presentiel; remiseLigne = t.remise_en_ligne; label = t.label; break;
    }
  }
  if(nb < 3){ res.textContent = 'Minimum 3 participants pour tarif groupe'; return; }
  if(remisePres === 0 && nb >= 21){
    res.innerHTML = '<div>' + nb + ' participants → <strong>Sur devis personnalisé</strong><br>Contactez-nous : +225 07 78 88 25 92</div>'; return;
  }
  const pp = Math.round(tarPres * (1 - remisePres/100));
  const po = Math.round(tarLigne * (1 - remiseLigne/100));
  res.innerHTML = '<strong>' + nb + ' participants · ' + label + '</strong><br>'
    + '🏫 Présentiel : ' + pp.toLocaleString('fr-FR') + ' F/pers → Total <strong>' + (pp*nb).toLocaleString('fr-FR') + ' F</strong> <span style="opacity:.7;font-size:11px">(-' + remisePres + '%)</span><br>'
    + '🖥️ En ligne : ' + po.toLocaleString('fr-FR') + ' F/pers → Total <strong>' + (po*nb).toLocaleString('fr-FR') + ' F</strong> <span style="opacity:.7;font-size:11px">(-' + remiseLigne + '%)</span>';
}
document.getElementById('sim_nb').addEventListener('input', simCalc);
document.getElementById('sim_parcours').addEventListener('change', simCalc);
simCalc();

const sections = document.querySelectorAll('.section[id]');
const navLinks = document.querySelectorAll('.sidebar a');
function setActive(){
  let cur = '';
  sections.forEach(s => { if(window.scrollY >= s.offsetTop - 80) cur = s.id; });
  navLinks.forEach(a => a.classList.toggle('active', a.getAttribute('href') === '#' + cur));
}
window.addEventListener('scroll', setActive, {passive:true});
setActive();
</script>
</body>
</html>
