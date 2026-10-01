<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';
Middleware::requireAuth();
$pdo = Database::connect();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$action    = $_GET['action']    ?? 'preview';
$delete_id = isset($_GET['delete_id']) ? (int)$_GET['delete_id'] : 0;

// ─── SUPPRESSION UNITAIRE ─────────────────────────────────────────────────────
if ($action === 'delete' && $delete_id > 0) {
    try {
        $pdo->beginTransaction();
        $pdo->prepare("DELETE FROM formation_niveaux WHERE formation_id = ?")->execute([$delete_id]);
        $pdo->prepare("DELETE FROM formations WHERE id = ?")->execute([$delete_id]);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
    }
    header('Location: ?action=preview');
    exit;
}

// ─── SUPPRESSION MASSE : tous les Samedi Pro ─────────────────────────────────
if ($action === 'purge_samedi') {
    $ids = $pdo->query("
        SELECT id FROM formations
        WHERE LOWER(titre) LIKE '%samedi%'
           OR LOWER(slug)  LIKE '%samedi%'
           OR LOWER(domaine) LIKE '%samedi%'
    ")->fetchAll(PDO::FETCH_COLUMN);
    if ($ids) {
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $pdo->prepare("DELETE FROM formation_niveaux WHERE formation_id IN ($ph)")->execute($ids);
        $pdo->prepare("DELETE FROM formations WHERE id IN ($ph)")->execute($ids);
    }
    header('Location: ?action=preview&msg=samedi_purged&nb=' . count($ids));
    exit;
}

// ─── DONNÉES ──────────────────────────────────────────────────────────────────

// Formations Samedi Pro
$samedi = $pdo->query("
    SELECT id, titre, slug, domaine, duree, statut, created_at
    FROM formations
    WHERE LOWER(titre) LIKE '%samedi%'
       OR LOWER(slug)  LIKE '%samedi%'
       OR LOWER(domaine) LIKE '%samedi%'
    ORDER BY domaine, titre
")->fetchAll(PDO::FETCH_ASSOC);

// Toutes les formations avec leur durée pour audit
$all = $pdo->query("
    SELECT f.id, f.titre, f.slug, f.domaine, f.duree, f.statut,
           GROUP_CONCAT(n.duree_heures ORDER BY n.ordre_affichage SEPARATOR ', ') as niveaux_heures
    FROM formations f
    LEFT JOIN formation_niveaux n ON n.formation_id = f.id
    WHERE f.id NOT IN (
        SELECT id FROM formations
        WHERE LOWER(titre) LIKE '%samedi%'
           OR LOWER(slug)  LIKE '%samedi%'
           OR LOWER(domaine) LIKE '%samedi%'
    )
    GROUP BY f.id
    ORDER BY CAST(REGEXP_REPLACE(f.duree, '[^0-9]', '') AS UNSIGNED) ASC, f.titre
")->fetchAll(PDO::FETCH_ASSOC);

// Extraire les heures numériques pour classification
function extract_hours(string $duree): ?int {
    if (preg_match('/(\d+)\s*[Hh]/u', $duree, $m)) return (int)$m[1];
    if (preg_match('/^(\d+)$/', trim($duree), $m)) return (int)$m[1];
    return null;
}

$suspects = [];   // < 15H ou > 200H ou durée invalide
$corrects  = [];  // 15H–200H
foreach ($all as $r) {
    $h = extract_hours((string)$r['duree']);
    if ($h === null || $h < 15 || $h > 200) {
        $suspects[] = $r + ['heures' => $h];
    } else {
        $corrects[] = $r + ['heures' => $h];
    }
}

// Stats
$stats = $pdo->query("
    SELECT COUNT(*) as total,
           SUM(CASE WHEN statut='active' THEN 1 ELSE 0 END) as actives,
           COUNT(DISTINCT domaine) as domaines
    FROM formations
")->fetch(PDO::FETCH_ASSOC);

$msg = $_GET['msg'] ?? '';
$nb  = (int)($_GET['nb'] ?? 0);
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Scan catalogue — Durées & Samedi Pro</title>
<style>
body{font-family:monospace;background:#0d1f3c;color:#e2e8f0;padding:24px;font-size:13px}
h1{color:#f59e0b}h2{color:#93c5fd;margin-top:32px}h3{color:#7dd3fc;margin-top:20px}
.ok{color:#34d399}.warn{color:#fbbf24}.err{color:#f87171}
.stat{display:inline-block;background:#1e3a6e;padding:10px 20px;border-radius:6px;margin:4px;text-align:center}
.stat strong{display:block;font-size:22px;color:#f59e0b}
table{border-collapse:collapse;width:100%;margin-bottom:16px}
th{background:#1e3a6e;color:#fff;padding:6px 10px;text-align:left}
td{padding:5px 10px;border-bottom:1px solid #1e3a6e;vertical-align:top}
tr:hover td{background:#1a3358}
.id{color:#94a3b8;width:45px}
.slug{color:#7dd3fc;font-size:11px}
.h-bad{color:#f87171;font-weight:bold}
.h-ok{color:#34d399}
.btn-del{background:#ef4444;color:#fff;border:none;padding:3px 10px;border-radius:4px;cursor:pointer;font-size:12px;text-decoration:none;display:inline-block}
.btn-del:hover{background:#dc2626}
.btn-big{display:inline-block;padding:12px 28px;background:#ef4444;color:#fff;border-radius:6px;text-decoration:none;font-weight:bold;margin:12px 0}
.btn-big:hover{background:#dc2626}
.notice{background:#1e3a6e;border-left:3px solid #f59e0b;padding:10px 16px;margin:16px 0;border-radius:4px}
details summary{cursor:pointer;color:#93c5fd;padding:6px 0}
details[open] summary{color:#f59e0b}
</style>
</head>
<body>
<h1>🔎 Scan catalogue — Durées & Samedi Pro</h1>

<?php if ($msg === 'samedi_purged'): ?>
<p class="ok">✅ <?= $nb ?> formation(s) Samedi Pro supprimée(s) du catalogue.</p>
<?php endif; ?>

<div style="margin:16px 0">
    <div class="stat"><strong><?= $stats['total'] ?></strong>formations</div>
    <div class="stat"><strong><?= $stats['actives'] ?></strong>actives</div>
    <div class="stat"><strong><?= $stats['domaines'] ?></strong>domaines</div>
    <div class="stat"><strong class="<?= count($suspects) > 0 ? 'err' : 'ok' ?>"><?= count($suspects) ?></strong>durées suspectes</div>
    <div class="stat"><strong class="<?= count($samedi) > 0 ? 'warn' : 'ok' ?>"><?= count($samedi) ?></strong>Samedi Pro à retirer</div>
</div>

<!-- ═══════════════════════ SAMEDI PRO ═══════════════════════════════════════ -->
<h2>📅 Formations Samedi Pro dans le catalogue (<?= count($samedi) ?>)</h2>
<?php if (!$samedi): ?>
<p class="ok">✅ Aucune formation Samedi Pro dans le catalogue général.</p>
<?php else: ?>
<div class="notice">⚠️ Ces formations sont dans le catalogue général mais devraient être <strong>uniquement dans le calendrier</strong>. Cliquer sur "SUPPRIMER TOUT" pour les retirer d'un coup.</div>
<a class="btn-big" href="?action=purge_samedi" onclick="return confirm('Supprimer les <?= count($samedi) ?> formations Samedi Pro du catalogue ? (irréversible)')">
    🗑️ SUPPRIMER TOUT LES SAMEDI PRO (<?= count($samedi) ?>)
</a>
<table>
<thead><tr><th>ID</th><th>Titre</th><th>Domaine</th><th>Durée</th><th>Statut</th><th>Action</th></tr></thead>
<tbody>
<?php foreach ($samedi as $r): ?>
<tr>
    <td class="id"><?= $r['id'] ?></td>
    <td><?= htmlspecialchars($r['titre']) ?><br><span class="slug"><?= htmlspecialchars($r['slug']) ?></span></td>
    <td><?= htmlspecialchars($r['domaine']) ?></td>
    <td><?= htmlspecialchars($r['duree']) ?></td>
    <td><?= $r['statut'] ?></td>
    <td><a class="btn-del" href="?action=delete&delete_id=<?= $r['id'] ?>" onclick="return confirm('Supprimer #<?= $r['id'] ?> ?')">Supprimer</a></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>

<!-- ═══════════════════════ DURÉES SUSPECTES ═════════════════════════════════ -->
<h2>⏱️ Durées suspectes — < 15H ou > 200H ou invalide (<?= count($suspects) ?>)</h2>
<?php if (!$suspects): ?>
<p class="ok">✅ Toutes les durées semblent correctes (15H – 200H).</p>
<?php else: ?>
<div class="notice">Ces formations ont une durée en dehors de la plage normale (15H – 200H). Vérifier et corriger manuellement via le backoffice.</div>
<table>
<thead><tr><th>ID</th><th>Titre</th><th>Domaine</th><th>Durée brute</th><th>Heures</th><th>Niveaux (H)</th></tr></thead>
<tbody>
<?php foreach ($suspects as $r): ?>
<tr>
    <td class="id"><?= $r['id'] ?></td>
    <td><?= htmlspecialchars($r['titre']) ?><br><span class="slug"><?= htmlspecialchars($r['slug']) ?></span></td>
    <td><?= htmlspecialchars($r['domaine']) ?></td>
    <td class="h-bad"><?= htmlspecialchars($r['duree']) ?></td>
    <td class="h-bad"><?= $r['heures'] !== null ? $r['heures'] . 'H' : '—' ?></td>
    <td><?= htmlspecialchars((string)$r['niveaux_heures']) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>

<!-- ═══════════════════════ CATALOGUE COMPLET ════════════════════════════════ -->
<h2>📋 Catalogue complet — toutes les durées (<?= count($corrects) ?> formations hors suspects)</h2>
<details>
<summary>▶ Voir le catalogue complet par domaine</summary>
<?php
$by_dom = [];
foreach (array_merge($corrects, $suspects) as $r) {
    $by_dom[$r['domaine']][] = $r;
}
ksort($by_dom);
foreach ($by_dom as $dom => $rows):
    usort($rows, fn($a, $b) => ($a['heures'] ?? 0) <=> ($b['heures'] ?? 0));
?>
<h3><?= htmlspecialchars($dom) ?> (<?= count($rows) ?>)</h3>
<table>
<thead><tr><th>ID</th><th>Titre</th><th>Durée</th><th>Niveaux (H)</th></tr></thead>
<tbody>
<?php foreach ($rows as $r): $bad = $r['heures'] === null || $r['heures'] < 15 || $r['heures'] > 200; ?>
<tr>
    <td class="id"><?= $r['id'] ?></td>
    <td><?= htmlspecialchars($r['titre']) ?></td>
    <td class="<?= $bad ? 'h-bad' : 'h-ok' ?>"><?= htmlspecialchars($r['duree']) ?></td>
    <td><?= htmlspecialchars((string)($r['niveaux_heures'] ?? '—')) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endforeach; ?>
</details>

</body>
</html>
