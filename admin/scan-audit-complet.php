<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';
Middleware::requireAuth();
$pdo = Database::connect();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// ─── ACTION : RESET SAMEDI PRO ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reset_samedi_pro') {
    csrf_check();
    $nb = $pdo->exec("UPDATE formations SET is_samedi_pro = 0 WHERE is_samedi_pro = 1");
    header('Location: scan-audit-complet.php?reset_ok=' . (int)$nb);
    exit;
}
$resetOk = isset($_GET['reset_ok']) ? (int)$_GET['reset_ok'] : null;

// ─── ACTION : ACTIVER FORMATIONS Q4 2026 ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'activer_q4_2026') {
    csrf_check();
    $nb = $pdo->exec("
        UPDATE formations
        SET statut = 'active'
        WHERE COALESCE(is_samedi_pro, 0) = 0
          AND statut = 'inactive'
          AND (
            (annee = 2026 AND mois IN ('Octobre','Novembre','Décembre'))
            OR (date_debut IS NOT NULL AND YEAR(date_debut) = 2026 AND MONTH(date_debut) IN (10,11,12))
          )
    ");
    header('Location: scan-audit-complet.php?activer_ok=' . (int)$nb);
    exit;
}
$activerOk = isset($_GET['activer_ok']) ? (int)$_GET['activer_ok'] : null;

// ─── DONNÉES COMPLÈTES ────────────────────────────────────────────────────────
$all = $pdo->query("
    SELECT f.id, f.titre, f.slug, f.domaine, f.duree, f.description, f.mode,
           f.tarif_en_ligne, f.tarif_presentiel, f.tarif_hybride,
           f.statut, f.is_samedi_pro, f.date_debut, f.mois, f.annee, f.session_label,
           f.created_at,
           COUNT(n.id) AS nb_niveaux,
           SUM(COALESCE(n.duree_heures,0)) AS total_h_niveaux
    FROM formations f
    LEFT JOIN formation_niveaux n ON n.formation_id = f.id
    GROUP BY f.id
    ORDER BY f.domaine, f.titre
")->fetchAll(PDO::FETCH_ASSOC);

$total = count($all);

// ─── INDEX POUR DOUBLONS ──────────────────────────────────────────────────────
$slug_map  = [];
$titre_map = [];
foreach ($all as $r) {
    if (!empty($r['slug'])) $slug_map[$r['slug']][] = $r['id'];
    $titre_map[mb_strtolower(trim($r['titre']),'UTF-8')][] = $r['id'];
}

// ─── CATÉGORIES D'ANOMALIES ───────────────────────────────────────────────────
$cats = [
    'doublons_slug'  => ['label'=>'Doublons de slug',       'color'=>'#ef4444', 'icon'=>'🔁', 'rows'=>[]],
    'doublons_titre' => ['label'=>'Doublons de titre',      'color'=>'#f97316', 'icon'=>'📋', 'rows'=>[]],
    'sans_tarif'     => ['label'=>'Sans tarif (0/0)',        'color'=>'#eab308', 'icon'=>'💰', 'rows'=>[]],
    'incoherence'    => ['label'=>'Présentiel < En ligne',   'color'=>'#f59e0b', 'icon'=>'⚖️',  'rows'=>[]],
    'hybride_faux'   => ['label'=>'Hybride incohérent',      'color'=>'#8b5cf6', 'icon'=>'🔢', 'rows'=>[]],
    'sans_desc'      => ['label'=>'Sans description',        'color'=>'#64748b', 'icon'=>'📝', 'rows'=>[]],
    'sans_duree'     => ['label'=>'Sans durée (ni niveaux)', 'color'=>'#0ea5e9', 'icon'=>'⏱',  'rows'=>[]],
    'sans_niveaux'   => ['label'=>'Sans niveaux tarifaires', 'color'=>'#06b6d4', 'icon'=>'📊', 'rows'=>[]],
    'samedi_pro'     => ['label'=>'Samedi Pro dans catalogue','color'=>'#10b981','icon'=>'📅', 'rows'=>[]],
    'inactive'       => ['label'=>'Formations inactives',    'color'=>'#475569', 'icon'=>'🔒', 'rows'=>[]],
    'slug_vide'      => ['label'=>'Slug vide',               'color'=>'#dc2626', 'icon'=>'🔗', 'rows'=>[]],
];

function r5i(int $v): int { return (int)(round($v / 5000) * 5000); }

foreach ($all as $r) {
    $el  = (int)$r['tarif_en_ligne'];
    $pr  = (int)$r['tarif_presentiel'];
    $hy  = (int)$r['tarif_hybride'];
    $hyb = ($el > 0 && $pr > 0) ? r5i((int)(($el + $pr) / 2)) : 0;
    $duree_vide   = trim((string)$r['duree']) === '' || trim((string)$r['duree']) === '0';
    $niveaux_sans_h = (int)$r['total_h_niveaux'] === 0;

    if (!empty($r['slug']) && count($slug_map[$r['slug']]) > 1)
        $cats['doublons_slug']['rows'][] = $r;

    if (count($titre_map[mb_strtolower(trim($r['titre']),'UTF-8')]) > 1)
        $cats['doublons_titre']['rows'][] = $r;

    if ($el <= 0 && $pr <= 0)
        $cats['sans_tarif']['rows'][] = $r;

    if ($el > 0 && $pr > 0 && $pr < $el)
        $cats['incoherence']['rows'][] = $r;

    if ($hy > 0 && $hyb > 0 && abs($hy - $hyb) > 15000)
        $cats['hybride_faux']['rows'][] = $r;

    if (strlen(trim((string)$r['description'])) < 30)
        $cats['sans_desc']['rows'][] = $r;

    if ($duree_vide && $niveaux_sans_h)
        $cats['sans_duree']['rows'][] = $r;

    if ((int)$r['nb_niveaux'] === 0)
        $cats['sans_niveaux']['rows'][] = $r;

    if (!empty($r['is_samedi_pro']) && (int)$r['is_samedi_pro'] === 1)
        $cats['samedi_pro']['rows'][] = $r;

    if ($r['statut'] !== 'active')
        $cats['inactive']['rows'][] = $r;

    if (empty(trim((string)$r['slug'])))
        $cats['slug_vide']['rows'][] = $r;
}

// Stats domaines
$dom_stats = [];
foreach ($all as $r) {
    $d = $r['domaine'] ?: '(sans domaine)';
    if (!isset($dom_stats[$d])) $dom_stats[$d] = ['total'=>0,'active'=>0];
    $dom_stats[$d]['total']++;
    if ($r['statut'] === 'active') $dom_stats[$d]['active']++;
}
arsort($dom_stats);

$nb_alertes = count(array_filter($cats, fn($c) => count($c['rows']) > 0));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Audit complet du catalogue</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Segoe UI',monospace,sans-serif;background:#080f1e;color:#e2e8f0;font-size:13px;line-height:1.5}
header{background:#0d1f3c;border-bottom:2px solid #1e3a6e;padding:16px 24px;position:sticky;top:0;z-index:100;display:flex;align-items:center;gap:16px;flex-wrap:wrap}
header h1{color:#f59e0b;font-size:17px;font-weight:700}
.stats{display:flex;gap:8px;flex-wrap:wrap;margin-left:auto}
.stat{background:#1e3a6e;padding:5px 14px;border-radius:6px;text-align:center;min-width:70px}
.stat strong{display:block;font-size:17px;color:#f59e0b;line-height:1.2}
.stat span{font-size:10px;color:#94a3b8;text-transform:uppercase}
.content{padding:20px 24px}

/* Résumé */
.summary-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px;margin-bottom:28px}
.cat-card{background:#0f1e35;border:1px solid #1e3a6e;border-radius:8px;padding:14px 16px;cursor:pointer;transition:border-color .2s}
.cat-card:hover{border-color:#3b82f6}
.cat-card.empty{opacity:.4;cursor:default}
.cat-card.empty:hover{border-color:#1e3a6e}
.cat-nb{font-size:28px;font-weight:800;line-height:1}
.cat-label{color:#94a3b8;font-size:12px;margin-top:4px}
.cat-icon{font-size:18px;float:right;margin-top:-2px}

/* Sections */
.section{margin-bottom:32px;background:#0a1628;border:1px solid #1e3a6e;border-radius:10px;overflow:hidden}
.section-header{display:flex;align-items:center;gap:10px;padding:12px 18px;border-bottom:1px solid #1e3a6e;cursor:pointer;background:#0d1f3c}
.section-header h2{font-size:14px;font-weight:700}
.section-nb{background:#1e3a6e;color:#93c5fd;padding:2px 10px;border-radius:10px;font-size:12px;font-weight:700}
.section-body{padding:0;display:none}
.section-body.open{display:block}
table{width:100%;border-collapse:collapse}
th{background:#071424;color:#475569;padding:6px 12px;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:.5px}
td{padding:6px 12px;border-bottom:1px solid #0f2540;vertical-align:middle}
tr:hover td{background:#0f2540}
.slug{color:#7dd3fc;font-size:11px}
.dom{color:#94a3b8;font-size:11px}
.tarif{font-size:12px;white-space:nowrap}
.ok{color:#34d399}.bad{color:#f87171}.warn{color:#fbbf24}
.btn-edit{background:#1e3a6e;color:#93c5fd;padding:3px 10px;border-radius:4px;font-size:11px;text-decoration:none;display:inline-block}
.btn-del{background:#7f1d1d;color:#fca5a5;padding:3px 10px;border-radius:4px;font-size:11px;text-decoration:none;display:inline-block;cursor:pointer;border:none;font-family:inherit}

/* Donut by domaine */
.dom-table{width:100%;border-collapse:collapse;margin-bottom:24px}
.dom-table th{background:#071424;color:#475569;padding:5px 12px;text-align:left;font-size:11px;text-transform:uppercase}
.dom-table td{padding:5px 12px;border-bottom:1px solid #0f2540;font-size:12px}
.bar{height:6px;border-radius:3px;background:#1e3a6e;margin-top:3px}
.bar-inner{height:6px;border-radius:3px;background:#3b82f6}

/* Boutons fix */
.fix-btn{display:inline-block;background:#f59e0b;color:#000;padding:8px 18px;border-radius:5px;font-weight:700;font-size:12px;text-decoration:none;margin:12px 16px}
.fix-btn:hover{background:#d97706}
.notice{background:#0f2540;border-left:3px solid #f59e0b;padding:8px 14px;margin:10px 16px;font-size:12px;color:#94a3b8;border-radius:0 4px 4px 0}
</style>
</head>
<body>

<header>
    <h1>🔬 Audit complet — Catalogue formations</h1>
    <div class="stats">
        <div class="stat"><strong><?= $total ?></strong><span>total</span></div>
        <div class="stat"><strong><?= count(array_filter($all,fn($r)=>$r['statut']==='active')) ?></strong><span>actives</span></div>
        <div class="stat"><strong><?= count($dom_stats) ?></strong><span>domaines</span></div>
        <div class="stat" style="border-color:<?= $nb_alertes > 0 ? '#ef4444' : '#34d399' ?>"><strong style="color:<?= $nb_alertes > 0 ? '#f87171' : '#34d399' ?>"><?= $nb_alertes ?></strong><span>catégories en alerte</span></div>
    </div>
</header>

<div class="content">

<?php if ($resetOk !== null): ?>
<div style="background:#064e3b;border:1px solid #10b981;border-radius:8px;padding:12px 18px;margin-bottom:16px;color:#34d399;font-weight:700">
  ✅ Flag Samedi Pro désactivé sur <?= $resetOk ?> formation(s). Elles sont maintenant des formations classiques avec bouton de paiement.
</div>
<?php endif; ?>

<?php if ($activerOk !== null): ?>
<div style="background:#064e3b;border:1px solid #10b981;border-radius:8px;padding:12px 18px;margin-bottom:16px;color:#34d399;font-weight:700">
  ✅ <?= $activerOk ?> formation(s) activée(s) pour Octobre / Novembre / Décembre 2026.
</div>
<?php endif; ?>

<!-- ACTIONS RAPIDES -->
<div style="background:#0d1f3c;border:1px solid #1e3a6e;border-radius:10px;padding:16px 20px;margin-bottom:20px">
  <div style="font-weight:700;color:#f59e0b;margin-bottom:12px">⚡ Actions rapides</div>
  <div style="display:flex;flex-wrap:wrap;gap:10px">
    <form method="post" onsubmit="return confirm('Activer toutes les formations standards d\'octobre, novembre et décembre 2026 ?')">
      <input type="hidden" name="action" value="activer_q4_2026">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      <button type="submit" style="background:#2563eb;color:#fff;border:none;padding:8px 16px;border-radius:6px;font-size:13px;font-weight:700;cursor:pointer">
        📅 Activer formations Oct · Nov · Déc 2026
      </button>
    </form>
  </div>
</div>

<!-- RÉSUMÉ -->
<div class="summary-grid">
<?php foreach ($cats as $key => $cat):
    $n = count($cat['rows']);
?>
<div class="cat-card <?= $n === 0 ? 'empty' : '' ?>" onclick="<?= $n > 0 ? "toggleSection('$key')" : '' ?>">
    <span class="cat-icon"><?= $cat['icon'] ?></span>
    <div class="cat-nb" style="color:<?= $n > 0 ? $cat['color'] : '#475569' ?>"><?= $n ?></div>
    <div class="cat-label"><?= $cat['label'] ?></div>
</div>
<?php endforeach; ?>
</div>

<!-- ACTION RAPIDE HYBRIDE -->
<div style="margin-bottom:24px">
    <a class="fix-btn" href="tableau-catalogue.php?act=fix_hybride_all&csrf=<?= csrf_token() ?>"
       onclick="return confirm('Recalculer le tarif hybride de toutes les formations ?')">
        ⚡ Recalculer tous les hybrides
    </a>
    <a class="fix-btn" style="background:#1e3a6e;color:#93c5fd" href="tableau-catalogue.php">
        📋 Ouvrir le tableau de bord
    </a>
    <a class="fix-btn" style="background:#052e16;color:#34d399" href="scan-similaires.php">
        🔀 Scan formations similaires
    </a>
</div>

<!-- DOUBLONS SLUG -->
<?php if ($cats['doublons_slug']['rows']): ?>
<div class="section" id="section-doublons_slug">
<div class="section-header" onclick="toggleSection('doublons_slug')">
    <span><?= $cats['doublons_slug']['icon'] ?></span>
    <h2 style="color:<?= $cats['doublons_slug']['color'] ?>"><?= $cats['doublons_slug']['label'] ?></h2>
    <span class="section-nb"><?= count($cats['doublons_slug']['rows']) ?></span>
    <span style="color:#475569;font-size:11px;margin-left:auto">▼</span>
</div>
<div class="section-body" id="body-doublons_slug">
    <div class="notice">Deux formations avec le même slug → conflit d'URL, la page publique n'affichera qu'une seule. Supprimer ou renommer.</div>
    <table><thead><tr><th>ID</th><th>Titre</th><th>Slug</th><th>Domaine</th><th>Actions</th></tr></thead><tbody>
    <?php foreach ($cats['doublons_slug']['rows'] as $r): ?>
    <tr>
        <td style="color:#64748b;font-size:11px"><?= $r['id'] ?></td>
        <td><?= htmlspecialchars($r['titre']) ?></td>
        <td class="slug"><?= htmlspecialchars($r['slug']) ?></td>
        <td class="dom"><?= htmlspecialchars($r['domaine']) ?></td>
        <td><a class="btn-edit" href="formations/edit.php?id=<?= $r['id'] ?>" target="_blank">✏️ Éditer</a></td>
    </tr>
    <?php endforeach; ?>
    </tbody></table>
</div>
</div>
<?php endif; ?>

<!-- DOUBLONS TITRE -->
<?php if ($cats['doublons_titre']['rows']): ?>
<div class="section" id="section-doublons_titre">
<div class="section-header" onclick="toggleSection('doublons_titre')">
    <span><?= $cats['doublons_titre']['icon'] ?></span>
    <h2 style="color:<?= $cats['doublons_titre']['color'] ?>"><?= $cats['doublons_titre']['label'] ?></h2>
    <span class="section-nb"><?= count($cats['doublons_titre']['rows']) ?></span>
    <span style="color:#475569;font-size:11px;margin-left:auto">▼</span>
</div>
<div class="section-body" id="body-doublons_titre">
    <div class="notice">Formations avec exactement le même titre — à fusionner ou différencier. Utiliser <a href="scan-similaires.php" style="color:#7dd3fc">scan-similaires.php</a> pour les quasi-doublons.</div>
    <table><thead><tr><th>ID</th><th>Titre</th><th>Domaine</th><th>Tarif EL / PR</th><th>Statut</th><th>Actions</th></tr></thead><tbody>
    <?php foreach ($cats['doublons_titre']['rows'] as $r): ?>
    <tr>
        <td style="color:#64748b;font-size:11px"><?= $r['id'] ?></td>
        <td><?= htmlspecialchars($r['titre']) ?><br><span class="slug"><?= htmlspecialchars($r['slug']) ?></span></td>
        <td class="dom"><?= htmlspecialchars($r['domaine']) ?></td>
        <td class="tarif"><?= number_format((int)$r['tarif_en_ligne'],0,',',' ') ?> / <?= number_format((int)$r['tarif_presentiel'],0,',',' ') ?></td>
        <td><span style="color:<?= $r['statut']==='active'?'#34d399':'#94a3b8' ?>"><?= $r['statut'] ?></span></td>
        <td><a class="btn-edit" href="formations/edit.php?id=<?= $r['id'] ?>" target="_blank">✏️ Éditer</a></td>
    </tr>
    <?php endforeach; ?>
    </tbody></table>
</div>
</div>
<?php endif; ?>

<!-- SANS TARIF -->
<?php if ($cats['sans_tarif']['rows']): ?>
<div class="section" id="section-sans_tarif">
<div class="section-header" onclick="toggleSection('sans_tarif')">
    <span><?= $cats['sans_tarif']['icon'] ?></span>
    <h2 style="color:<?= $cats['sans_tarif']['color'] ?>"><?= $cats['sans_tarif']['label'] ?></h2>
    <span class="section-nb"><?= count($cats['sans_tarif']['rows']) ?></span>
    <span style="color:#475569;font-size:11px;margin-left:auto">▼</span>
</div>
<div class="section-body" id="body-sans_tarif">
    <div class="notice">Tarif en ligne ET présentiel = 0. La formation affiche "0 FCFA" sur les pages publiques.</div>
    <table><thead><tr><th>ID</th><th>Titre</th><th>Domaine</th><th>Durée</th><th>Statut</th><th>Actions</th></tr></thead><tbody>
    <?php foreach ($cats['sans_tarif']['rows'] as $r): ?>
    <tr>
        <td style="color:#64748b;font-size:11px"><?= $r['id'] ?></td>
        <td><?= htmlspecialchars($r['titre']) ?></td>
        <td class="dom"><?= htmlspecialchars($r['domaine']) ?></td>
        <td><?= htmlspecialchars($r['duree']) ?></td>
        <td><span style="color:<?= $r['statut']==='active'?'#34d399':'#94a3b8' ?>"><?= $r['statut'] ?></span></td>
        <td><a class="btn-edit" href="formations/edit.php?id=<?= $r['id'] ?>" target="_blank">✏️ Éditer</a></td>
    </tr>
    <?php endforeach; ?>
    </tbody></table>
</div>
</div>
<?php endif; ?>

<!-- INCOHÉRENCE TARIF -->
<?php if ($cats['incoherence']['rows']): ?>
<div class="section" id="section-incoherence">
<div class="section-header" onclick="toggleSection('incoherence')">
    <span><?= $cats['incoherence']['icon'] ?></span>
    <h2 style="color:<?= $cats['incoherence']['color'] ?>"><?= $cats['incoherence']['label'] ?></h2>
    <span class="section-nb"><?= count($cats['incoherence']['rows']) ?></span>
    <span style="color:#475569;font-size:11px;margin-left:auto">▼</span>
</div>
<div class="section-body" id="body-incoherence">
    <div class="notice">Le tarif présentiel devrait être ≥ au tarif en ligne (présentiel inclut transport/salle).</div>
    <table><thead><tr><th>ID</th><th>Titre</th><th>Domaine</th><th>En ligne</th><th>Présentiel</th><th>Différence</th><th>Actions</th></tr></thead><tbody>
    <?php foreach ($cats['incoherence']['rows'] as $r):
        $el=(int)$r['tarif_en_ligne']; $pr=(int)$r['tarif_presentiel'];
    ?>
    <tr>
        <td style="color:#64748b;font-size:11px"><?= $r['id'] ?></td>
        <td><?= htmlspecialchars($r['titre']) ?></td>
        <td class="dom"><?= htmlspecialchars($r['domaine']) ?></td>
        <td class="tarif ok"><?= number_format($el,0,',',' ') ?></td>
        <td class="tarif bad"><?= number_format($pr,0,',',' ') ?></td>
        <td class="tarif bad">-<?= number_format($el-$pr,0,',',' ') ?> FCFA</td>
        <td><a class="btn-edit" href="formations/edit.php?id=<?= $r['id'] ?>" target="_blank">✏️ Éditer</a></td>
    </tr>
    <?php endforeach; ?>
    </tbody></table>
</div>
</div>
<?php endif; ?>

<!-- HYBRIDE FAUX -->
<?php if ($cats['hybride_faux']['rows']): ?>
<div class="section" id="section-hybride_faux">
<div class="section-header" onclick="toggleSection('hybride_faux')">
    <span><?= $cats['hybride_faux']['icon'] ?></span>
    <h2 style="color:<?= $cats['hybride_faux']['color'] ?>"><?= $cats['hybride_faux']['label'] ?></h2>
    <span class="section-nb"><?= count($cats['hybride_faux']['rows']) ?></span>
    <span style="color:#475569;font-size:11px;margin-left:auto">▼</span>
</div>
<div class="section-body" id="body-hybride_faux">
    <div class="notice">Hybride devrait être = arrondi((en_ligne + présentiel) / 2 / 5 000) × 5 000. Utiliser le bouton "Recalculer tous les hybrides" ci-dessus.</div>
    <table><thead><tr><th>ID</th><th>Titre</th><th>En ligne</th><th>Présentiel</th><th>Hybride actuel</th><th>Hybride attendu</th><th>Actions</th></tr></thead><tbody>
    <?php foreach ($cats['hybride_faux']['rows'] as $r):
        $el=(int)$r['tarif_en_ligne']; $pr=(int)$r['tarif_presentiel']; $hy=(int)$r['tarif_hybride'];
        $att = r5i((int)(($el+$pr)/2));
    ?>
    <tr>
        <td style="color:#64748b;font-size:11px"><?= $r['id'] ?></td>
        <td><?= htmlspecialchars($r['titre']) ?></td>
        <td class="tarif"><?= number_format($el,0,',',' ') ?></td>
        <td class="tarif"><?= number_format($pr,0,',',' ') ?></td>
        <td class="tarif bad"><?= number_format($hy,0,',',' ') ?></td>
        <td class="tarif ok"><?= number_format($att,0,',',' ') ?></td>
        <td><a class="btn-edit" href="formations/edit.php?id=<?= $r['id'] ?>" target="_blank">✏️ Éditer</a></td>
    </tr>
    <?php endforeach; ?>
    </tbody></table>
</div>
</div>
<?php endif; ?>

<!-- SANS DESCRIPTION -->
<?php if ($cats['sans_desc']['rows']): ?>
<div class="section" id="section-sans_desc">
<div class="section-header" onclick="toggleSection('sans_desc')">
    <span><?= $cats['sans_desc']['icon'] ?></span>
    <h2 style="color:<?= $cats['sans_desc']['color'] ?>"><?= $cats['sans_desc']['label'] ?></h2>
    <span class="section-nb"><?= count($cats['sans_desc']['rows']) ?></span>
    <span style="color:#475569;font-size:11px;margin-left:auto">▼</span>
</div>
<div class="section-body" id="body-sans_desc">
    <div class="notice">Description vide ou trop courte (< 30 caractères). Impacte le SEO et la page de détail de la formation.</div>
    <table><thead><tr><th>ID</th><th>Titre</th><th>Domaine</th><th>Description actuelle</th><th>Actions</th></tr></thead><tbody>
    <?php foreach ($cats['sans_desc']['rows'] as $r): ?>
    <tr>
        <td style="color:#64748b;font-size:11px"><?= $r['id'] ?></td>
        <td><?= htmlspecialchars($r['titre']) ?></td>
        <td class="dom"><?= htmlspecialchars($r['domaine']) ?></td>
        <td style="color:#475569;font-size:11px;font-style:italic"><?= htmlspecialchars(substr((string)$r['description'],0,60)) ?: '(vide)' ?></td>
        <td><a class="btn-edit" href="formations/edit.php?id=<?= $r['id'] ?>" target="_blank">✏️ Éditer</a></td>
    </tr>
    <?php endforeach; ?>
    </tbody></table>
</div>
</div>
<?php endif; ?>

<!-- SANS DURÉE -->
<?php if ($cats['sans_duree']['rows']): ?>
<div class="section" id="section-sans_duree">
<div class="section-header" onclick="toggleSection('sans_duree')">
    <span><?= $cats['sans_duree']['icon'] ?></span>
    <h2 style="color:<?= $cats['sans_duree']['color'] ?>"><?= $cats['sans_duree']['label'] ?></h2>
    <span class="section-nb"><?= count($cats['sans_duree']['rows']) ?></span>
    <span style="color:#475569;font-size:11px;margin-left:auto">▼</span>
</div>
<div class="section-body" id="body-sans_duree">
    <div class="notice">Aucune durée dans formations.duree ET aucune heure dans formation_niveaux. La durée n'apparaît pas sur la page publique.</div>
    <table><thead><tr><th>ID</th><th>Titre</th><th>Domaine</th><th>Niveaux</th><th>Actions</th></tr></thead><tbody>
    <?php foreach ($cats['sans_duree']['rows'] as $r): ?>
    <tr>
        <td style="color:#64748b;font-size:11px"><?= $r['id'] ?></td>
        <td><?= htmlspecialchars($r['titre']) ?></td>
        <td class="dom"><?= htmlspecialchars($r['domaine']) ?></td>
        <td style="color:#94a3b8"><?= (int)$r['nb_niveaux'] ?> niveau(x)</td>
        <td><a class="btn-edit" href="formations/edit.php?id=<?= $r['id'] ?>" target="_blank">✏️ Éditer</a></td>
    </tr>
    <?php endforeach; ?>
    </tbody></table>
</div>
</div>
<?php endif; ?>

<!-- SANS NIVEAUX -->
<?php if ($cats['sans_niveaux']['rows']): ?>
<div class="section" id="section-sans_niveaux">
<div class="section-header" onclick="toggleSection('sans_niveaux')">
    <span><?= $cats['sans_niveaux']['icon'] ?></span>
    <h2 style="color:<?= $cats['sans_niveaux']['color'] ?>"><?= $cats['sans_niveaux']['label'] ?></h2>
    <span class="section-nb"><?= count($cats['sans_niveaux']['rows']) ?></span>
    <span style="color:#475569;font-size:11px;margin-left:auto">▼</span>
</div>
<div class="section-body" id="body-sans_niveaux">
    <div class="notice">Aucune entrée dans formation_niveaux. Le TDR et la page de détail lisent les tarifs depuis formation_niveaux — sans niveaux, ces données peuvent manquer.</div>
    <table><thead><tr><th>ID</th><th>Titre</th><th>Domaine</th><th>Tarif EL / PR</th><th>Statut</th><th>Actions</th></tr></thead><tbody>
    <?php foreach ($cats['sans_niveaux']['rows'] as $r): ?>
    <tr>
        <td style="color:#64748b;font-size:11px"><?= $r['id'] ?></td>
        <td><?= htmlspecialchars($r['titre']) ?></td>
        <td class="dom"><?= htmlspecialchars($r['domaine']) ?></td>
        <td class="tarif"><?= number_format((int)$r['tarif_en_ligne'],0,',',' ') ?> / <?= number_format((int)$r['tarif_presentiel'],0,',',' ') ?></td>
        <td><span style="color:<?= $r['statut']==='active'?'#34d399':'#94a3b8' ?>"><?= $r['statut'] ?></span></td>
        <td><a class="btn-edit" href="formations/edit.php?id=<?= $r['id'] ?>" target="_blank">✏️ Éditer</a></td>
    </tr>
    <?php endforeach; ?>
    </tbody></table>
</div>
</div>
<?php endif; ?>

<!-- SAMEDI PRO -->
<?php if ($cats['samedi_pro']['rows']): ?>
<div class="section" id="section-samedi_pro">
<div class="section-header" onclick="toggleSection('samedi_pro')">
    <span><?= $cats['samedi_pro']['icon'] ?></span>
    <h2 style="color:<?= $cats['samedi_pro']['color'] ?>"><?= $cats['samedi_pro']['label'] ?></h2>
    <span class="section-nb"><?= count($cats['samedi_pro']['rows']) ?></span>
    <span style="color:#475569;font-size:11px;margin-left:auto">▼</span>
</div>
<div class="section-body" id="body-samedi_pro">
    <div class="notice">Ces formations sont marquées <code>is_samedi_pro=1</code> — elles doivent apparaître uniquement dans le calendrier, pas dans le catalogue général.
    <a href="scan-catalogue.php?action=purge_samedi&csrf=<?= csrf_token() ?>" onclick="return confirm('Supprimer toutes les formations Samedi Pro du catalogue ?')" style="color:#f87171;margin-left:8px">🗑 Supprimer toutes</a>
    <form method="post" style="display:inline" onsubmit="return confirm('Désactiver le flag Samedi Pro sur toutes ces formations ? Elles deviendront des formations classiques.')">
      <input type="hidden" name="action" value="reset_samedi_pro">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      <button type="submit" style="background:#d97706;color:#fff;border:none;padding:3px 12px;border-radius:4px;font-size:11px;cursor:pointer;margin-left:8px">⚡ Désactiver flag Samedi Pro sur toutes</button>
    </form>
    </div>
    <table><thead><tr><th>ID</th><th>Titre</th><th>Domaine</th><th>Date</th><th>Actions</th></tr></thead><tbody>
    <?php foreach ($cats['samedi_pro']['rows'] as $r): ?>
    <tr>
        <td style="color:#64748b;font-size:11px"><?= $r['id'] ?></td>
        <td><?= htmlspecialchars($r['titre']) ?></td>
        <td class="dom"><?= htmlspecialchars($r['domaine']) ?></td>
        <td style="font-size:11px;color:#94a3b8"><?= substr($r['date_debut']??'',0,10) ?></td>
        <td><a class="btn-edit" href="formations/edit.php?id=<?= $r['id'] ?>" target="_blank">✏️ Éditer</a></td>
    </tr>
    <?php endforeach; ?>
    </tbody></table>
</div>
</div>
<?php endif; ?>

<!-- INACTIVES -->
<?php if ($cats['inactive']['rows']): ?>
<div class="section" id="section-inactive">
<div class="section-header" onclick="toggleSection('inactive')">
    <span><?= $cats['inactive']['icon'] ?></span>
    <h2 style="color:<?= $cats['inactive']['color'] ?>"><?= $cats['inactive']['label'] ?></h2>
    <span class="section-nb"><?= count($cats['inactive']['rows']) ?></span>
    <span style="color:#475569;font-size:11px;margin-left:auto">▼</span>
</div>
<div class="section-body" id="body-inactive">
    <div class="notice">Ces formations ne sont pas visibles sur le site public. Les activer si elles sont prêtes, les supprimer si elles sont obsolètes.</div>
    <table><thead><tr><th>ID</th><th>Titre</th><th>Domaine</th><th>Statut</th><th>Créé le</th><th>Actions</th></tr></thead><tbody>
    <?php foreach ($cats['inactive']['rows'] as $r): ?>
    <tr>
        <td style="color:#64748b;font-size:11px"><?= $r['id'] ?></td>
        <td><?= htmlspecialchars($r['titre']) ?></td>
        <td class="dom"><?= htmlspecialchars($r['domaine']) ?></td>
        <td class="warn"><?= $r['statut'] ?></td>
        <td style="font-size:11px;color:#475569"><?= substr($r['created_at'],0,10) ?></td>
        <td><a class="btn-edit" href="formations/edit.php?id=<?= $r['id'] ?>" target="_blank">✏️ Éditer</a></td>
    </tr>
    <?php endforeach; ?>
    </tbody></table>
</div>
</div>
<?php endif; ?>

<!-- SLUG VIDE -->
<?php if ($cats['slug_vide']['rows']): ?>
<div class="section" id="section-slug_vide">
<div class="section-header" onclick="toggleSection('slug_vide')">
    <span><?= $cats['slug_vide']['icon'] ?></span>
    <h2 style="color:<?= $cats['slug_vide']['color'] ?>"><?= $cats['slug_vide']['label'] ?></h2>
    <span class="section-nb"><?= count($cats['slug_vide']['rows']) ?></span>
    <span style="color:#475569;font-size:11px;margin-left:auto">▼</span>
</div>
<div class="section-body" id="body-slug_vide">
    <div class="notice">Slug vide = pas d'URL publique. La formation ne peut pas être accédée via formation-detail.php.</div>
    <table><thead><tr><th>ID</th><th>Titre</th><th>Domaine</th><th>Actions</th></tr></thead><tbody>
    <?php foreach ($cats['slug_vide']['rows'] as $r): ?>
    <tr>
        <td style="color:#64748b;font-size:11px"><?= $r['id'] ?></td>
        <td><?= htmlspecialchars($r['titre']) ?></td>
        <td class="dom"><?= htmlspecialchars($r['domaine']) ?></td>
        <td><a class="btn-edit" href="formations/edit.php?id=<?= $r['id'] ?>" target="_blank">✏️ Éditer</a></td>
    </tr>
    <?php endforeach; ?>
    </tbody></table>
</div>
</div>
<?php endif; ?>

<!-- RÉPARTITION PAR DOMAINE -->
<div class="section" id="section-domaines">
<div class="section-header" onclick="toggleSection('domaines')">
    <span>🗂</span>
    <h2 style="color:#7dd3fc">Répartition par domaine</h2>
    <span class="section-nb"><?= count($dom_stats) ?> domaines</span>
    <span style="color:#475569;font-size:11px;margin-left:auto">▼</span>
</div>
<div class="section-body" id="body-domaines">
<table class="dom-table">
<thead><tr><th>Domaine</th><th>Total</th><th>Actives</th><th>Volume</th></tr></thead>
<tbody>
<?php
$max_total = max(array_column($dom_stats, 'total')) ?: 1;
foreach ($dom_stats as $dom => $s):
    $pct = (int)(($s['total'] / $max_total) * 100);
?>
<tr>
    <td><?= htmlspecialchars($dom) ?></td>
    <td><strong><?= $s['total'] ?></strong></td>
    <td style="color:#34d399"><?= $s['active'] ?></td>
    <td style="width:200px">
        <div class="bar"><div class="bar-inner" style="width:<?= $pct ?>%"></div></div>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>

<?php if ($nb_alertes === 0): ?>
<div style="background:#052e16;border:1px solid #14532d;border-radius:8px;padding:20px;text-align:center;color:#34d399;font-size:16px;font-weight:700;margin-top:24px">
    ✅ Catalogue propre — aucune anomalie détectée
</div>
<?php endif; ?>

</div><!-- /content -->

<script>
function toggleSection(key) {
    const body = document.getElementById('body-' + key);
    if (!body) return;
    const isOpen = body.classList.contains('open');
    body.classList.toggle('open', !isOpen);
    // scroll to section
    if (!isOpen) {
        document.getElementById('section-' + key)?.scrollIntoView({behavior:'smooth', block:'start'});
    }
}
// Ouvrir auto les sections urgentes (doublons + tarifs)
['doublons_slug','doublons_titre','sans_tarif','incoherence','slug_vide'].forEach(k => {
    const b = document.getElementById('body-' + k);
    if (b) b.classList.add('open');
});
</script>
</body>
</html>
