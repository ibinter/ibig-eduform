<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';
Middleware::requireAuth();
$pdo = Database::connect();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$action    = $_GET['action']    ?? 'preview';
$delete_id = isset($_GET['delete_id']) ? (int)$_GET['delete_id'] : 0;

// ─── SUPPRESSION ─────────────────────────────────────────────────────────────
if ($action === 'delete' && $delete_id > 0) {
    $pdo->beginTransaction();
    try {
        $pdo->prepare("DELETE FROM formation_niveaux WHERE formation_id = ?")->execute([$delete_id]);
        $pdo->prepare("DELETE FROM formations WHERE id = ?")->execute([$delete_id]);
        $pdo->commit();
    } catch (Throwable $e) { $pdo->rollBack(); }
    header('Location: ?msg=deleted#deleted');
    exit;
}

// ─── MOTS VIDES (stop words français) ────────────────────────────────────────
$stop = ['de','du','des','le','la','les','et','en','au','aux','un','une',
         'pour','par','sur','dans','avec','à','l','d','est','sont',
         'ses','leur','leurs','ce','cette','ces','mon','ma','mes',
         'comment','comment','vers','plus','sans','sous','entre'];

function tokenize(string $titre, array $stop): array {
    $titre = mb_strtolower($titre, 'UTF-8');
    $titre = preg_replace('/[^a-z0-9àâäéèêëîïôùûüœç\s]/u', ' ', $titre) ?? $titre;
    $words = preg_split('/\s+/', trim($titre)) ?: [];
    return array_filter($words, fn($w) => strlen($w) > 2 && !in_array($w, $stop));
}

function similarite(array $a, array $b): float {
    if (!$a || !$b) return 0.0;
    $inter = count(array_intersect($a, $b));
    $union = count(array_unique(array_merge($a, $b)));
    return $union > 0 ? round($inter / $union, 2) : 0.0;
}

// ─── CHARGER TOUTES LES FORMATIONS ───────────────────────────────────────────
$all = $pdo->query("
    SELECT id, titre, slug, domaine, duree, tarif_en_ligne, tarif_presentiel, statut, created_at
    FROM formations
    ORDER BY domaine, titre
")->fetchAll(PDO::FETCH_ASSOC);

// Tokeniser tous les titres
foreach ($all as &$r) {
    $r['tokens'] = array_values(tokenize($r['titre'], $stop));
}
unset($r);

// ─── DÉTECTER LES PAIRES SIMILAIRES ─────────────────────────────────────────
$seuil  = 0.40; // 40 % de chevauchement Jaccard = suspect
$groupes = []; // liste de paires [id_a, id_b, score]

$n = count($all);
for ($i = 0; $i < $n; $i++) {
    for ($j = $i + 1; $j < $n; $j++) {
        // Comparer dans le même domaine OU globalement si score très élevé
        $score = similarite($all[$i]['tokens'], $all[$j]['tokens']);
        $meme_domaine = $all[$i]['domaine'] === $all[$j]['domaine'];
        if ($score >= $seuil && ($meme_domaine || $score >= 0.65)) {
            $groupes[] = ['a' => $all[$i], 'b' => $all[$j], 'score' => $score];
        }
    }
}

// Trier par score décroissant
usort($groupes, fn($x, $y) => $y['score'] <=> $x['score']);

// ─── INDEX id → formation ─────────────────────────────────────────────────────
$idx = [];
foreach ($all as $r) $idx[$r['id']] = $r;

$msg = $_GET['msg'] ?? '';
$stats = $pdo->query("SELECT COUNT(*) as total, COUNT(DISTINCT domaine) as nb_dom FROM formations")->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Formations similaires — audit</title>
<style>
*{box-sizing:border-box}
body{font-family:monospace;background:#0d1f3c;color:#e2e8f0;padding:24px;font-size:13px;margin:0}
h1{color:#f59e0b;margin-bottom:4px}
h2{color:#93c5fd;margin-top:32px;border-bottom:1px solid #1e3a6e;padding-bottom:6px}
.ok{color:#34d399}.warn{color:#fbbf24}.err{color:#f87171}
.stat{display:inline-block;background:#1e3a6e;padding:8px 16px;border-radius:6px;margin:4px;text-align:center}
.stat strong{display:block;font-size:20px;color:#f59e0b}
.pair{background:#0f2540;border:1px solid #1e3a6e;border-radius:8px;padding:14px 16px;margin-bottom:16px;position:relative}
.pair.haut{border-left:4px solid #ef4444}
.pair.moyen{border-left:4px solid #f59e0b}
.pair.bas{border-left:4px solid #3b82f6}
.score{position:absolute;top:14px;right:16px;font-size:18px;font-weight:bold}
.score.haut{color:#ef4444}
.score.moyen{color:#f59e0b}
.score.bas{color:#3b82f6}
.fiche{background:#1a3358;border-radius:6px;padding:10px 14px;margin:8px 0;display:flex;align-items:flex-start;gap:12px;flex-wrap:wrap}
.fiche-info{flex:1;min-width:200px}
.fiche-title{color:#e2e8f0;font-weight:bold;font-size:14px}
.fiche-slug{color:#7dd3fc;font-size:11px}
.fiche-meta{color:#94a3b8;font-size:11px;margin-top:3px}
.fiche-tarif{color:#34d399;font-size:12px;margin-top:3px}
.actions{display:flex;gap:6px;align-items:center;flex-shrink:0}
.btn-del{background:#ef4444;color:#fff;border:none;padding:5px 14px;border-radius:4px;cursor:pointer;font-size:12px;text-decoration:none;display:inline-block;white-space:nowrap}
.btn-edit{background:#3b82f6;color:#fff;border:none;padding:5px 14px;border-radius:4px;cursor:pointer;font-size:12px;text-decoration:none;display:inline-block;white-space:nowrap}
.vs{text-align:center;color:#475569;font-size:12px;padding:4px 0}
.legend{display:flex;gap:16px;flex-wrap:wrap;margin:12px 0}
.legend-item{display:flex;align-items:center;gap:6px;font-size:12px}
.dot{width:12px;height:12px;border-radius:50%}
.filters{margin:16px 0;display:flex;gap:12px;flex-wrap:wrap;align-items:center}
.filters select,.filters input{background:#1e3a6e;color:#e2e8f0;border:1px solid #2d4a7a;padding:6px 12px;border-radius:4px;font-size:13px}
.badge{display:inline-block;background:#1e3a6e;padding:1px 8px;border-radius:3px;font-size:11px;margin-left:6px;color:#93c5fd}
.notice{background:#1e3a6e;border-left:3px solid #f59e0b;padding:10px 16px;margin:16px 0;border-radius:4px}
</style>
</head>
<body>
<h1>🔀 Formations similaires — audit</h1>

<?php if ($msg === 'deleted'): ?>
<p class="ok" id="deleted">✅ Formation supprimée.</p>
<?php endif; ?>

<div style="margin:12px 0">
    <div class="stat"><strong><?= $stats['total'] ?></strong>formations total</div>
    <div class="stat"><strong><?= $stats['nb_dom'] ?></strong>domaines</div>
    <div class="stat"><strong class="<?= count($groupes) > 0 ? 'warn' : 'ok' ?>"><?= count($groupes) ?></strong>paires suspectes</div>
</div>

<div class="legend">
    <div class="legend-item"><div class="dot" style="background:#ef4444"></div><span class="err">≥ 65 % similitude — très suspect (quasi-doublon)</span></div>
    <div class="legend-item"><div class="dot" style="background:#f59e0b"></div><span class="warn">50–64 % — similaire (vérifier)</span></div>
    <div class="legend-item"><div class="dot" style="background:#3b82f6"></div><span style="color:#93c5fd">40–49 % — proche (même thème)</span></div>
</div>

<div class="notice">
    Pour chaque paire : <strong>Supprimer</strong> retire définitivement la formation du catalogue |
    <strong>Éditer</strong> ouvre le formulaire d'édition pour modifier le titre, la durée ou les tarifs.
</div>

<?php if (!$groupes): ?>
<p class="ok" style="font-size:15px">✅ Aucune formation suffisamment similaire détectée dans le catalogue.</p>
<?php else: ?>

<div class="filters">
    <label style="color:#94a3b8">Filtrer par domaine :</label>
    <select id="sel-dom" onchange="filterPairs()">
        <option value="">— Tous les domaines —</option>
        <?php
        $doms = array_unique(array_merge(
            array_column(array_column($groupes, 'a'), 'domaine'),
            array_column(array_column($groupes, 'b'), 'domaine')
        ));
        sort($doms);
        foreach ($doms as $d): ?>
        <option value="<?= htmlspecialchars($d) ?>"><?= htmlspecialchars($d) ?></option>
        <?php endforeach; ?>
    </select>
    <label style="color:#94a3b8">Similitude min :</label>
    <select id="sel-score" onchange="filterPairs()">
        <option value="0">≥ 40 %</option>
        <option value="0.5">≥ 50 %</option>
        <option value="0.65" selected>≥ 65 % (très suspect)</option>
    </select>
</div>

<div id="pairs-container">
<?php foreach ($groupes as $g):
    $score = $g['score'];
    $pct   = (int)($score * 100);
    $cls   = $score >= 0.65 ? 'haut' : ($score >= 0.50 ? 'moyen' : 'bas');
    $a     = $g['a'];
    $b     = $g['b'];
    $tokens_common = array_intersect($a['tokens'], $b['tokens']);
    $same_dom = $a['domaine'] === $b['domaine'];
?>
<div class="pair <?= $cls ?>"
     data-dom-a="<?= htmlspecialchars($a['domaine']) ?>"
     data-dom-b="<?= htmlspecialchars($b['domaine']) ?>"
     data-score="<?= $score ?>">
    <div class="score <?= $cls ?>"><?= $pct ?>%</div>

    <!-- Formation A -->
    <div class="fiche">
        <div class="fiche-info">
            <div class="fiche-title"><?= htmlspecialchars($a['titre']) ?></div>
            <div class="fiche-slug"><?= htmlspecialchars($a['slug']) ?></div>
            <div class="fiche-meta">
                <?= htmlspecialchars($a['domaine']) ?> · <?= htmlspecialchars($a['duree']) ?> · ID <?= $a['id'] ?>
                <?= $a['statut'] !== 'active' ? '<span class="warn"> [' . $a['statut'] . ']</span>' : '' ?>
            </div>
            <div class="fiche-tarif">
                En ligne : <?= number_format((int)$a['tarif_en_ligne'], 0, ',', ' ') ?> FCFA |
                Présentiel : <?= number_format((int)$a['tarif_presentiel'], 0, ',', ' ') ?> FCFA
            </div>
        </div>
        <div class="actions">
            <a class="btn-edit" href="formations/edit.php?id=<?= $a['id'] ?>" target="_blank">✏️ Éditer</a>
            <a class="btn-del" href="?action=delete&delete_id=<?= $a['id'] ?>"
               onclick="return confirm('Supprimer #<?= $a['id'] ?> — <?= addslashes(htmlspecialchars($a['titre'])) ?> ?')">🗑 Supprimer</a>
        </div>
    </div>

    <div class="vs">⇅ mots en commun : <strong><?= htmlspecialchars(implode(', ', $tokens_common)) ?></strong><?= !$same_dom ? ' <span class="warn">(domaines différents)</span>' : '' ?></div>

    <!-- Formation B -->
    <div class="fiche">
        <div class="fiche-info">
            <div class="fiche-title"><?= htmlspecialchars($b['titre']) ?></div>
            <div class="fiche-slug"><?= htmlspecialchars($b['slug']) ?></div>
            <div class="fiche-meta">
                <?= htmlspecialchars($b['domaine']) ?> · <?= htmlspecialchars($b['duree']) ?> · ID <?= $b['id'] ?>
                <?= $b['statut'] !== 'active' ? '<span class="warn"> [' . $b['statut'] . ']</span>' : '' ?>
            </div>
            <div class="fiche-tarif">
                En ligne : <?= number_format((int)$b['tarif_en_ligne'], 0, ',', ' ') ?> FCFA |
                Présentiel : <?= number_format((int)$b['tarif_presentiel'], 0, ',', ' ') ?> FCFA
            </div>
        </div>
        <div class="actions">
            <a class="btn-edit" href="formations/edit.php?id=<?= $b['id'] ?>" target="_blank">✏️ Éditer</a>
            <a class="btn-del" href="?action=delete&delete_id=<?= $b['id'] ?>"
               onclick="return confirm('Supprimer #<?= $b['id'] ?> — <?= addslashes(htmlspecialchars($b['titre'])) ?> ?')">🗑 Supprimer</a>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<script>
function filterPairs() {
    const dom   = document.getElementById('sel-dom').value.toLowerCase();
    const score = parseFloat(document.getElementById('sel-score').value) || 0;
    document.querySelectorAll('.pair').forEach(el => {
        const da = el.dataset.domA.toLowerCase();
        const db = el.dataset.domB.toLowerCase();
        const s  = parseFloat(el.dataset.score);
        const domOk   = !dom || da.includes(dom) || db.includes(dom);
        const scoreOk = s >= score;
        el.style.display = domOk && scoreOk ? '' : 'none';
    });
}
filterPairs();
</script>
</body>
</html>
