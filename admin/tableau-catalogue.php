<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';
Middleware::requireAuth();
$pdo = Database::connect();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

/* ════════════════════════════════════════════════════════════════
   ACTIONS AJAX / POST
════════════════════════════════════════════════════════════════ */
header('X-Content-Type-Options: nosniff');

$act = $_POST['act'] ?? $_GET['act'] ?? '';

// Suppression
if ($act === 'delete' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    $pdo->beginTransaction();
    try {
        $pdo->prepare("DELETE FROM formation_niveaux WHERE formation_id=?")->execute([$id]);
        $pdo->prepare("DELETE FROM formations WHERE id=?")->execute([$id]);
        $pdo->commit();
        echo json_encode(['ok'=>true]); exit;
    } catch (Throwable $e) { $pdo->rollBack(); echo json_encode(['ok'=>false,'err'=>$e->getMessage()]); exit; }
}

// Mise à jour inline (titre, duree, tarif_en_ligne, tarif_presentiel, tarif_hybride)
if ($act === 'update' && isset($_POST['id'])) {
    $id  = (int)$_POST['id'];
    $col = $_POST['col'] ?? '';
    $val = trim($_POST['val'] ?? '');
    $allowed = ['titre','duree','tarif_en_ligne','tarif_presentiel','tarif_hybride','statut'];
    if (!in_array($col, $allowed, true)) { echo json_encode(['ok'=>false,'err'=>'col']); exit; }

    $pdo->beginTransaction();
    try {
        $pdo->prepare("UPDATE formations SET `$col`=?, updated_at=NOW() WHERE id=?")->execute([$val, $id]);
        // Répercuter tarifs sur formation_niveaux
        if (in_array($col, ['tarif_en_ligne','tarif_presentiel','tarif_hybride'], true)) {
            $pdo->prepare("UPDATE formation_niveaux SET `$col`=?, updated_at=NOW() WHERE formation_id=?")->execute([(int)$val, $id]);
        }
        if ($col === 'duree') {
            if (preg_match('/(\d+)/u', $val, $m)) {
                $pdo->prepare("UPDATE formation_niveaux SET duree_heures=?, updated_at=NOW() WHERE formation_id=?")->execute([(int)$m[1], $id]);
            }
        }
        $pdo->commit();
        echo json_encode(['ok'=>true,'val'=>$val]); exit;
    } catch (Throwable $e) { $pdo->rollBack(); echo json_encode(['ok'=>false,'err'=>$e->getMessage()]); exit; }
}

/* ════════════════════════════════════════════════════════════════
   DONNÉES
════════════════════════════════════════════════════════════════ */
function r5(int $v): int { return (int)(round($v / 5000) * 5000); }
function extract_h(string $d): ?int {
    if (preg_match('/(\d+)\s*[Hh]/u', $d, $m)) return (int)$m[1];
    if (preg_match('/^(\d+)$/', trim($d), $m)) return (int)$m[1];
    return null;
}

$all = $pdo->query("
    SELECT f.id, f.titre, f.slug, f.domaine, f.duree,
           f.tarif_en_ligne, f.tarif_presentiel, f.tarif_hybride,
           f.mode, f.statut, f.created_at,
           COUNT(n.id) as nb_niveaux
    FROM formations f
    LEFT JOIN formation_niveaux n ON n.formation_id = f.id
    GROUP BY f.id
    ORDER BY f.domaine, f.titre
")->fetchAll(PDO::FETCH_ASSOC);

$alertes = [];
$slug_count = [];
$titre_count = [];
foreach ($all as $r) {
    $slug_count[$r['slug']][] = $r['id'];
    $titre_count[strtolower(trim($r['titre']))][] = $r['id'];
}

foreach ($all as $r) {
    $id   = $r['id'];
    $h    = extract_h((string)$r['duree']);
    $el   = (int)$r['tarif_en_ligne'];
    $pr   = (int)$r['tarif_presentiel'];
    $hy   = (int)$r['tarif_hybride'];
    $hyb_attendu = $el > 0 && $pr > 0 ? r5((int)(($el + $pr) / 2)) : 0;

    $bugs = [];
    // Doublons slug (uniquement vrais doublons, pas les slugs vides)
    if (!empty($r['slug']) && count($slug_count[$r['slug']]) > 1)
        $bugs[] = ['type'=>'doublon_slug','msg'=>'Slug dupliqué : ' . $r['slug']];
    // Doublons titre exact
    if (count($titre_count[strtolower(trim($r['titre']))]) > 1)
        $bugs[] = ['type'=>'doublon_titre','msg'=>'Titre en doublon dans le catalogue'];
    // Durée hors plage (seuil bas abaissé à 8H pour formations courtes légitimes)
    if ($h === null)
        $bugs[] = ['type'=>'duree','msg'=>'Durée illisible : "' . $r['duree'] . '"'];
    elseif ($h < 8)
        $bugs[] = ['type'=>'duree','msg'=>"Durée très courte : {$h}H — à vérifier"];
    elseif ($h > 300)
        $bugs[] = ['type'=>'duree','msg'=>"Durée hors norme : {$h}H — à vérifier"];
    // Tarifs manquants (0 = non renseigné)
    if ($el <= 0)
        $bugs[] = ['type'=>'tarif','msg'=>'Tarif en ligne à 0 — non renseigné'];
    if ($pr <= 0)
        $bugs[] = ['type'=>'tarif','msg'=>'Tarif présentiel à 0 — non renseigné'];
    // Incohérence présentiel < en ligne
    if ($el > 0 && $pr > 0 && $pr < $el)
        $bugs[] = ['type'=>'tarif','msg'=>"Présentiel (" . number_format($pr,0,',',' ') . ") < En ligne (" . number_format($el,0,',',' ') . ") — incohérent"];
    // Hybride incohérent (tolérance 10 000 FCFA, ignoré si hybride = 0)
    if ($hy > 0 && $hyb_attendu > 0 && abs($hy - $hyb_attendu) > 10000)
        $bugs[] = ['type'=>'tarif','msg'=>"Hybride " . number_format($hy,0,',',' ') . " ≠ attendu " . number_format($hyb_attendu,0,',',' ') . " FCFA"];

    if ($bugs) $alertes[$id] = $bugs;
}

// Stats
$stats = [
    'total'    => count($all),
    'actives'  => count(array_filter($all, fn($r) => $r['statut'] === 'active')),
    'domaines' => count(array_unique(array_column($all, 'domaine'))),
    'alertes'  => count($alertes),
];

// Grouper par domaine
$by_dom = [];
foreach ($all as $r) $by_dom[$r['domaine']][] = $r;
ksort($by_dom);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Tableau de bord catalogue</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Segoe UI',monospace,sans-serif;background:#0a1628;color:#e2e8f0;font-size:13px;line-height:1.5}
header{background:#0d1f3c;border-bottom:2px solid #1e3a6e;padding:16px 24px;display:flex;align-items:center;gap:16px;position:sticky;top:0;z-index:100}
header h1{color:#f59e0b;font-size:18px;font-weight:700}
.stats{display:flex;gap:10px;flex-wrap:wrap;margin-left:auto}
.stat{background:#1e3a6e;padding:6px 14px;border-radius:6px;text-align:center}
.stat strong{display:block;font-size:18px;color:#f59e0b;line-height:1.2}
.stat span{font-size:10px;color:#94a3b8;text-transform:uppercase}
.stat.danger strong{color:#f87171}
.content{padding:20px 24px}

/* Alertes */
.alertes-header{display:flex;align-items:center;gap:12px;margin-bottom:12px}
.alertes-header h2{color:#f87171;font-size:15px}
.alerte-row{background:#1a0a0a;border:1px solid #3b1212;border-radius:6px;padding:10px 14px;margin-bottom:8px;display:flex;align-items:flex-start;gap:12px}
.alerte-row:hover{border-color:#7f1d1d}
.alerte-id{color:#94a3b8;font-size:11px;min-width:36px}
.alerte-titre{color:#fca5a5;font-weight:600;font-size:13px}
.alerte-bugs{display:flex;flex-wrap:wrap;gap:6px;margin-top:4px}
.bug{display:inline-block;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:600}
.bug.doublon_slug,.bug.doublon_titre{background:#7c2d12;color:#fed7aa}
.bug.duree{background:#164e63;color:#a5f3fc}
.bug.tarif{background:#3b0764;color:#e9d5ff}
.bug.niveaux{background:#14532d;color:#bbf7d0}
.alerte-actions{margin-left:auto;display:flex;gap:6px;flex-shrink:0}

/* Catalogue */
.dom-section{margin-bottom:28px}
.dom-header{display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid #1e3a6e;margin-bottom:8px;cursor:pointer}
.dom-header h3{color:#7dd3fc;font-size:14px;font-weight:700}
.dom-count{background:#1e3a6e;color:#93c5fd;padding:1px 8px;border-radius:10px;font-size:11px}
.dom-toggle{color:#475569;font-size:11px;margin-left:auto}
table{width:100%;border-collapse:collapse}
th{background:#0d1f3c;color:#64748b;padding:6px 10px;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:.5px;position:sticky;top:56px;z-index:10}
td{padding:6px 10px;border-bottom:1px solid #0f2540;vertical-align:middle}
tr:hover td{background:#0f2540}
tr.has-alert td{background:#1a0a0a}
tr.has-alert:hover td{background:#220d0d}
.cell-titre{font-weight:600;color:#e2e8f0;max-width:280px}
.cell-slug{color:#7dd3fc;font-size:11px}
.cell-duree{font-size:12px;font-weight:600}
.cell-duree.bad{color:#f87171}
.cell-duree.ok{color:#34d399}
.cell-tarif{font-size:12px;white-space:nowrap}
.cell-tarif.bad{color:#f87171}
.cell-tarif.ok{color:#34d399}
.bug-pill{display:inline-block;width:8px;height:8px;border-radius:50%;margin-right:2px}

/* Editable */
[data-edit]{cursor:pointer;border-bottom:1px dashed #475569;transition:background .15s}
[data-edit]:hover{background:rgba(59,130,246,.15);border-radius:3px}
[data-edit].editing{background:transparent;border-bottom:none}
.edit-input{background:#1e3a6e;color:#e2e8f0;border:1px solid #3b82f6;border-radius:4px;padding:3px 8px;font-size:12px;font-family:inherit;width:120px}
.edit-input.large{width:260px}
.edit-input.select{width:auto}

/* Buttons */
.btn{display:inline-flex;align-items:center;gap:4px;padding:4px 10px;border-radius:4px;font-size:12px;cursor:pointer;border:none;font-family:inherit;white-space:nowrap;text-decoration:none}
.btn-del{background:#7f1d1d;color:#fca5a5}.btn-del:hover{background:#991b1b}
.btn-edit{background:#1e3a6e;color:#93c5fd}.btn-edit:hover{background:#1d4ed8}
.btn-view{background:#052e16;color:#6ee7b7;text-decoration:none}.btn-view:hover{background:#064e3b}

/* Toast */
#toast{position:fixed;bottom:20px;right:20px;background:#0f2540;border:1px solid #1e3a6e;padding:12px 20px;border-radius:8px;color:#34d399;font-size:13px;display:none;z-index:999;box-shadow:0 4px 20px #000a}

/* Filter */
.toolbar{display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:16px}
.toolbar input,.toolbar select{background:#1e3a6e;color:#e2e8f0;border:1px solid #2d4a7a;padding:6px 12px;border-radius:5px;font-size:13px;font-family:inherit}
.toolbar label{color:#94a3b8;font-size:12px}
.badge-alert{display:inline-block;background:#7f1d1d;color:#fca5a5;padding:1px 6px;border-radius:3px;font-size:10px;font-weight:700;margin-left:4px;vertical-align:middle}
</style>
</head>
<body>

<header>
    <h1>📋 Tableau de bord — Catalogue formations</h1>
    <div class="stats">
        <div class="stat"><strong><?= $stats['total'] ?></strong><span>formations</span></div>
        <div class="stat"><strong><?= $stats['actives'] ?></strong><span>actives</span></div>
        <div class="stat"><strong><?= $stats['domaines'] ?></strong><span>domaines</span></div>
        <div class="stat <?= $stats['alertes'] > 0 ? 'danger' : '' ?>"><strong><?= $stats['alertes'] ?></strong><span>alertes</span></div>
    </div>
</header>

<div class="content">

<!-- ════ ALERTES ════ -->
<?php if ($alertes):
// Compteurs par type
$nb_by_type = ['doublon_slug'=>0,'doublon_titre'=>0,'duree'=>0,'tarif'=>0,'niveaux'=>0];
foreach ($alertes as $bugs) foreach ($bugs as $b) if (isset($nb_by_type[$b['type']])) $nb_by_type[$b['type']]++;
$idx = [];
foreach ($all as $r) $idx[$r['id']] = $r;
?>
<div style="margin-bottom:28px">
    <div class="alertes-header" style="flex-wrap:wrap;gap:8px">
        <h2>⚠️ <?= count($alertes) ?> formation(s) avec anomalies</h2>
        <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
            <?php if ($nb_by_type['doublon_slug'] || $nb_by_type['doublon_titre']): ?>
            <button class="bug doublon_slug" onclick="filterAlerts('doublon')" style="cursor:pointer;border:none">
                🔁 Doublons (<?= $nb_by_type['doublon_slug'] + $nb_by_type['doublon_titre'] ?>)
            </button>
            <?php endif; ?>
            <?php if ($nb_by_type['duree']): ?>
            <button class="bug duree" onclick="filterAlerts('duree')" style="cursor:pointer;border:none">
                ⏱ Durées (<?= $nb_by_type['duree'] ?>)
            </button>
            <?php endif; ?>
            <?php if ($nb_by_type['tarif']): ?>
            <button class="bug tarif" onclick="filterAlerts('tarif')" style="cursor:pointer;border:none">
                💰 Tarifs (<?= $nb_by_type['tarif'] ?>)
            </button>
            <?php endif; ?>
            <button onclick="filterAlerts('')" style="cursor:pointer;border:none;background:#1e3a6e;color:#93c5fd;padding:2px 8px;border-radius:4px;font-size:11px">Tout afficher</button>
        </div>
        <span style="color:#94a3b8;font-size:11px;margin-left:auto">Cliquez sur un champ pour l'éditer directement</span>
    </div>
    <div id="alertes-list">
    <?php $shown = 0; foreach ($alertes as $fid => $bugs):
        $r = $idx[$fid] ?? null;
        if (!$r) continue;
        $types = implode(' ', array_unique(array_column($bugs, 'type')));
    ?>
    <div class="alerte-row" id="alert-<?= $fid ?>" data-types="<?= htmlspecialchars($types) ?>">
        <div class="alerte-id">#<?= $fid ?></div>
        <div style="flex:1;min-width:0">
            <div class="alerte-titre"><?= htmlspecialchars($r['titre']) ?></div>
            <div style="color:#64748b;font-size:11px;margin-bottom:4px"><?= htmlspecialchars($r['domaine']) ?> · <?= htmlspecialchars($r['duree']) ?></div>
            <div class="alerte-bugs">
                <?php foreach ($bugs as $b): ?>
                <span class="bug <?= $b['type'] ?>"><?= htmlspecialchars($b['msg']) ?></span>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="alerte-actions">
            <a class="btn btn-edit" href="formations/edit.php?id=<?= $fid ?>" target="_blank">✏️ Éditer</a>
            <button class="btn btn-del" onclick="deleteFormation(<?= $fid ?>, <?= json_encode($r['titre']) ?>)">🗑 Supprimer</button>
        </div>
    </div>
    <?php $shown++; endforeach; ?>
    </div>
    <div id="alertes-count" style="color:#64748b;font-size:12px;margin-top:8px;text-align:center">
        <?= count($alertes) ?> anomalies affichées
    </div>
</div>
<?php else: ?>
<div style="background:#052e16;border:1px solid #14532d;border-radius:8px;padding:14px 18px;margin-bottom:24px;color:#34d399">
    ✅ Aucune anomalie détectée dans le catalogue.
</div>
<?php endif; ?>

<!-- ════ TOOLBAR ════ -->
<div class="toolbar">
    <label>Recherche :</label>
    <input type="search" id="q" placeholder="Titre, slug, domaine…" oninput="filterRows()" style="width:220px">
    <label>Domaine :</label>
    <select id="f-dom" onchange="filterRows()">
        <option value="">Tous</option>
        <?php foreach (array_keys($by_dom) as $d): ?>
        <option value="<?= htmlspecialchars($d) ?>"><?= htmlspecialchars($d) ?></option>
        <?php endforeach; ?>
    </select>
    <label>Statut :</label>
    <select id="f-stat" onchange="filterRows()">
        <option value="">Tous</option>
        <option value="active">Active</option>
        <option value="inactive">Inactive</option>
    </select>
    <label><input type="checkbox" id="f-alert" onchange="filterRows()"> Alertes seulement</label>
    <span id="count-display" style="color:#64748b;font-size:12px;margin-left:auto"></span>
</div>

<!-- ════ CATALOGUE ════ -->
<?php foreach ($by_dom as $dom => $rows): ?>
<div class="dom-section" data-dom="<?= htmlspecialchars($dom) ?>">
    <div class="dom-header" onclick="toggleDom(this)">
        <h3><?= htmlspecialchars($dom) ?></h3>
        <span class="dom-count"><?= count($rows) ?></span>
        <?php $dom_alerts = count(array_filter($rows, fn($r) => isset($alertes[$r['id']]))); ?>
        <?php if ($dom_alerts): ?><span class="badge-alert">⚠ <?= $dom_alerts ?></span><?php endif; ?>
        <span class="dom-toggle">▼</span>
    </div>
    <div class="dom-body">
    <table>
    <thead>
        <tr>
            <th>ID</th><th>Titre</th><th>Durée</th>
            <th>En ligne</th><th>Présentiel</th><th>Hybride</th>
            <th>Statut</th><th>Actions</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($rows as $r):
        $id  = $r['id'];
        $h   = extract_h((string)$r['duree']);
        $el  = (int)$r['tarif_en_ligne'];
        $pr  = (int)$r['tarif_presentiel'];
        $hy  = (int)$r['tarif_hybride'];
        $hyb_ok = ($el > 0 && $pr > 0) ? abs($hy - r5((int)(($el+$pr)/2))) <= 5000 : true;
        $h_ok = $h !== null && $h >= 15 && $h <= 200;
        $has_alert = isset($alertes[$id]);
    ?>
    <tr class="<?= $has_alert ? 'has-alert' : '' ?>"
        data-titre="<?= htmlspecialchars(strtolower($r['titre'])) ?>"
        data-dom="<?= htmlspecialchars($r['domaine']) ?>"
        data-stat="<?= $r['statut'] ?>"
        data-alert="<?= $has_alert ? '1' : '0' ?>">
        <td style="color:#64748b;font-size:11px"><?= $id ?><?= $has_alert ? '<span class="badge-alert" style="margin-left:4px">!</span>' : '' ?></td>
        <td>
            <div class="cell-titre"
                 data-edit data-id="<?= $id ?>" data-col="titre"
                 title="Cliquer pour modifier"><?= htmlspecialchars($r['titre']) ?></div>
            <div class="cell-slug"><?= htmlspecialchars($r['slug']) ?></div>
        </td>
        <td>
            <span class="cell-duree <?= $h_ok ? 'ok' : 'bad' ?>"
                  data-edit data-id="<?= $id ?>" data-col="duree"
                  title="Cliquer pour modifier"><?= htmlspecialchars($r['duree']) ?></span>
        </td>
        <td>
            <span class="cell-tarif <?= $el > 0 ? 'ok' : 'bad' ?>"
                  data-edit data-id="<?= $id ?>" data-col="tarif_en_ligne"
                  title="Cliquer pour modifier"><?= number_format($el,0,',',' ') ?></span>
        </td>
        <td>
            <span class="cell-tarif <?= ($pr >= $el && $pr > 0) ? 'ok' : 'bad' ?>"
                  data-edit data-id="<?= $id ?>" data-col="tarif_presentiel"
                  title="Cliquer pour modifier"><?= number_format($pr,0,',',' ') ?></span>
        </td>
        <td>
            <span class="cell-tarif <?= $hyb_ok ? 'ok' : 'bad' ?>"
                  data-edit data-id="<?= $id ?>" data-col="tarif_hybride"
                  title="Cliquer pour modifier"><?= $hy > 0 ? number_format($hy,0,',',' ') : '—' ?></span>
        </td>
        <td>
            <span data-edit data-id="<?= $id ?>" data-col="statut" data-type="select"
                  style="color:<?= $r['statut']==='active'?'#34d399':'#f87171' ?>;cursor:pointer"
                  title="Cliquer pour modifier"><?= $r['statut'] ?></span>
        </td>
        <td>
            <div style="display:flex;gap:4px">
                <a class="btn btn-view" href="../formation-detail.php?slug=<?= urlencode($r['slug']) ?>" target="_blank" title="Page publique">👁</a>
                <a class="btn btn-edit" href="formations/edit.php?id=<?= $id ?>" target="_blank" title="Éditer">✏️</a>
                <button class="btn btn-del" onclick="deleteFormation(<?= $id ?>, <?= json_encode($r['titre']) ?>)" title="Supprimer">🗑</button>
            </div>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
    </table>
    </div>
</div>
<?php endforeach; ?>
</div><!-- /content -->

<div id="toast"></div>

<script>
// ── Toast ──────────────────────────────────────────────────────────────────
function toast(msg, ok=true) {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.style.color = ok ? '#34d399' : '#f87171';
    t.style.display = 'block';
    setTimeout(() => t.style.display='none', 3000);
}

// ── Suppression ────────────────────────────────────────────────────────────
function deleteFormation(id, titre) {
    if (!confirm('Supprimer « ' + titre + ' » ?\nCette action est irréversible et impacte immédiatement le site public.')) return;
    fetch('tableau-catalogue.php', {
        method: 'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body: 'act=delete&id=' + id
    }).then(r=>r.json()).then(d => {
        if (d.ok) {
            // Retirer la ligne et l'alerte
            document.querySelectorAll('tr').forEach(tr => {
                if (tr.dataset.titre !== undefined) {
                    const idCell = tr.querySelector('td:first-child');
                    if (idCell && idCell.textContent.trim().startsWith(String(id))) tr.remove();
                }
            });
            const alert = document.getElementById('alert-' + id);
            if (alert) alert.remove();
            toast('✅ Formation #' + id + ' supprimée — catalogue mis à jour.');
        } else toast('❌ ' + d.err, false);
    });
}

// ── Édition inline ─────────────────────────────────────────────────────────
document.querySelectorAll('[data-edit]').forEach(el => {
    el.addEventListener('click', function(e) {
        if (this.classList.contains('editing')) return;
        this.classList.add('editing');
        const id  = this.dataset.id;
        const col = this.dataset.col;
        const old = this.textContent.trim().replace(/\s/g,'');
        const isSelect = this.dataset.type === 'select';

        let inp;
        if (isSelect) {
            inp = document.createElement('select');
            inp.className = 'edit-input select';
            ['active','inactive'].forEach(v => {
                const o = document.createElement('option');
                o.value = v; o.textContent = v;
                if (v === this.textContent.trim()) o.selected = true;
                inp.appendChild(o);
            });
        } else {
            inp = document.createElement('input');
            inp.className = 'edit-input' + (col==='titre' ? ' large' : '');
            inp.type = col.includes('tarif') ? 'number' : 'text';
            inp.value = col.includes('tarif') ? old.replace(/\s/g,'') : this.textContent.trim();
        }

        const orig = this.innerHTML;
        this.innerHTML = '';
        this.appendChild(inp);
        inp.focus();

        const save = () => {
            const val = inp.value.trim();
            if (val === '' || val === (col.includes('tarif') ? old.replace(/\s/g,'') : this.dataset.orig)) {
                this.innerHTML = orig; this.classList.remove('editing'); return;
            }
            fetch('tableau-catalogue.php', {
                method:'POST',
                headers:{'Content-Type':'application/x-www-form-urlencoded'},
                body: `act=update&id=${id}&col=${encodeURIComponent(col)}&val=${encodeURIComponent(val)}`
            }).then(r=>r.json()).then(d => {
                this.classList.remove('editing');
                if (d.ok) {
                    if (col.includes('tarif')) {
                        const n = parseInt(val);
                        this.textContent = n.toLocaleString('fr-FR') + (col==='tarif_hybride'&&n===0?'—':'');
                    } else {
                        this.textContent = val;
                    }
                    toast('✅ Mis à jour — visible immédiatement sur le site public.');
                } else { this.innerHTML = orig; toast('❌ ' + d.err, false); }
            });
        };

        inp.addEventListener('blur', save);
        inp.addEventListener('keydown', e => { if (e.key==='Enter') { e.preventDefault(); inp.blur(); } if (e.key==='Escape') { this.innerHTML=orig; this.classList.remove('editing'); } });
    });
});

// ── Filtres ────────────────────────────────────────────────────────────────
function filterRows() {
    const q     = document.getElementById('q').value.toLowerCase();
    const dom   = document.getElementById('f-dom').value.toLowerCase();
    const stat  = document.getElementById('f-stat').value;
    const alert = document.getElementById('f-alert').checked;
    let visible = 0;

    document.querySelectorAll('tbody tr').forEach(tr => {
        const t  = (tr.dataset.titre||'').toLowerCase();
        const d  = (tr.dataset.dom||'').toLowerCase();
        const s  = tr.dataset.stat;
        const a  = tr.dataset.alert === '1';
        const ok = (!q || t.includes(q) || d.includes(q))
                && (!dom  || d === dom)
                && (!stat || s === stat)
                && (!alert || a);
        tr.style.display = ok ? '' : 'none';
        if (ok) visible++;
    });

    // Cacher les sections vides
    document.querySelectorAll('.dom-section').forEach(sec => {
        const domVal = sec.dataset.dom.toLowerCase();
        const hasVisible = Array.from(sec.querySelectorAll('tbody tr')).some(tr => tr.style.display !== 'none');
        sec.style.display = hasVisible ? '' : 'none';
    });

    document.getElementById('count-display').textContent = visible + ' formation(s) affichée(s)';
}

// ── Toggle domaine ─────────────────────────────────────────────────────────
function toggleDom(header) {
    const body = header.nextElementSibling;
    const tog  = header.querySelector('.dom-toggle');
    const open = body.style.display !== 'none';
    body.style.display = open ? 'none' : '';
    tog.textContent = open ? '▶' : '▼';
}

// ── Filtre alertes par type ───────────────────────────────────────────────
function filterAlerts(type) {
    const rows = document.querySelectorAll('#alertes-list .alerte-row');
    let visible = 0;
    rows.forEach(row => {
        const types = row.dataset.types || '';
        const show = !type
            || (type === 'doublon' && (types.includes('doublon_slug') || types.includes('doublon_titre')))
            || types.includes(type);
        row.style.display = show ? '' : 'none';
        if (show) visible++;
    });
    const counter = document.getElementById('alertes-count');
    if (counter) counter.textContent = visible + ' anomalie(s) affichée(s)' + (type ? ' — filtre: ' + type : '');
}

// Init count
filterRows();
</script>
</body>
</html>
