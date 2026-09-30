<?php
declare(strict_types=1);
/**
 * IBIG EDUFORM — Audit complet du catalogue
 * Analyse les 1 534 formations : doublons, tarifs, catégories, incohérences
 */
header('Content-Type: text/html; charset=utf-8');

require_once __DIR__ . '/core/secrets.php';

/* ── Charger formations API ── */
$cacheFile = sys_get_temp_dir() . '/ibig_catalogue_cache.json';
$api_formations = [];
if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 600) {
    $data = json_decode(file_get_contents($cacheFile), true);
    $api_formations = $data['formations'] ?? [];
} else {
    $ctx = stream_context_create(['http' => ['timeout' => 10, 'method' => 'GET', 'header' => "Accept: application/json\r\n"]]);
    $json = @file_get_contents('https://www.ibigpartners.com/api/catalogue', false, $ctx);
    if ($json) {
        $data = json_decode($json, true);
        $api_formations = $data['formations'] ?? [];
        file_put_contents($cacheFile, $json);
    }
}

/* ── Charger formations locales ── */
$pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$local_rows = $pdo->query("
    SELECT id, titre AS name, slug, domaine AS category, tarif_en_ligne AS price, tarif_presentiel AS price_pres, duree, statut, annee
    FROM formations
    WHERE statut = 'active' AND (annee = 0 OR annee IS NULL OR annee = YEAR(CURDATE()))
    ORDER BY titre ASC
")->fetchAll(PDO::FETCH_ASSOC);

/* ── Normalisation ── */
$norm = fn(string $s): string => mb_strtolower(trim(preg_replace('/[\s\-–—_\/\(\)&]+/', ' ', preg_replace('/[^\p{L}\p{N}\s\-]/u', '', $s))), 'UTF-8');
$fcfa = fn(int $v): string => number_format($v, 0, ',', ' ') . ' F';
$r5   = fn(float $v): int => (int)(round($v / 5000) * 5000);

/* Grille EDUFORM par durée */
$GRILLE_DUREE = [
    10 => [200000, 250000], 15 => [200000, 250000], 20 => [250000, 300000],
    24 => [250000, 300000], 25 => [250000, 300000], 30 => [350000, 400000],
    36 => [350000, 400000], 40 => [350000, 400000], 48 => [400000, 450000],
    60 => [450000, 500000],
];
$prix_for_duree = function(string $duree) use ($GRILLE_DUREE): ?array {
    if (preg_match('/(\d+)\s*h/i', $duree, $m)) {
        $h = (int)$m[1];
        if (isset($GRILLE_DUREE[$h])) return $GRILLE_DUREE[$h];
        // interpolation
        $keys = array_keys($GRILLE_DUREE);
        sort($keys);
        foreach ($keys as $k) { if ($k >= $h) return $GRILLE_DUREE[$k]; }
        return $GRILLE_DUREE[max($keys)];
    }
    return null;
};

/* ── Indexer toutes les formations ── */
$all = [];
foreach ($api_formations as $f) {
    $prix_raw = (int)($f['price'] ?? 0);
    $prix = max($prix_raw, 200000);
    $all[] = [
        'source'    => 'API',
        'name'      => (string)($f['name'] ?? ''),
        'slug'      => (string)($f['slug'] ?? ''),
        'category'  => (string)($f['category'] ?? ''),
        'price'     => $prix,
        'price_raw' => $prix_raw,
        'price_pres'=> 0,
        'duree'     => '',
        'norm'      => $norm((string)($f['name'] ?? '')),
    ];
}
foreach ($local_rows as $r) {
    $all[] = [
        'source'    => 'LOCAL',
        'name'      => (string)($r['name'] ?? ''),
        'slug'      => (string)($r['slug'] ?? ''),
        'category'  => (string)($r['category'] ?? ''),
        'price'     => (int)($r['price'] ?? 0),
        'price_raw' => (int)($r['price'] ?? 0),
        'price_pres'=> (int)($r['price_pres'] ?? 0),
        'duree'     => (string)($r['duree'] ?? ''),
        'norm'      => $norm((string)($r['name'] ?? '')),
    ];
}

$total = count($all);

/* ════════════════════════════════════════
   ANALYSE 1 : DOUBLONS EXACTS
   ════════════════════════════════════════ */
$by_norm = [];
foreach ($all as $f) {
    $by_norm[$f['norm']][] = $f;
}
$doublons_exacts = array_filter($by_norm, fn($g) => count($g) > 1);

/* ════════════════════════════════════════
   ANALYSE 2 : DOUBLONS APPROCHÉS (80% mots communs)
   ════════════════════════════════════════ */
$doublons_approches = [];
$norms = array_keys($by_norm);
$checked = [];
for ($i = 0; $i < count($norms); $i++) {
    for ($j = $i + 1; $j < count($norms); $j++) {
        $key = $norms[$i] . '||' . $norms[$j];
        if (isset($checked[$key])) continue;
        $checked[$key] = true;
        $w1 = array_filter(explode(' ', $norms[$i]), fn($w) => strlen($w) > 3);
        $w2 = array_filter(explode(' ', $norms[$j]), fn($w) => strlen($w) > 3);
        if (count($w1) < 2 || count($w2) < 2) continue;
        $common = count(array_intersect($w1, $w2));
        $ratio  = $common / max(count($w1), count($w2));
        if ($ratio >= 0.75 && $norms[$i] !== $norms[$j]) {
            $doublons_approches[] = [
                'a' => $by_norm[$norms[$i]][0],
                'b' => $by_norm[$norms[$j]][0],
                'ratio' => round($ratio * 100),
            ];
        }
    }
}

/* ════════════════════════════════════════
   ANALYSE 3 : TARIFS SOUS PLANCHER
   ════════════════════════════════════════ */
$sous_plancher = array_filter($all, fn($f) => $f['price_raw'] > 0 && $f['price_raw'] < 200000);
$pres_sous_plancher = array_filter($all, fn($f) => $f['source'] === 'LOCAL' && $f['price_pres'] > 0 && $f['price_pres'] < 250000);

/* ════════════════════════════════════════
   ANALYSE 4 : INCOHÉRENCE TARIF / DURÉE (formations locales)
   ════════════════════════════════════════ */
$incoherence_tarif_duree = [];
foreach ($all as $f) {
    if ($f['source'] !== 'LOCAL' || empty($f['duree'])) continue;
    $grille = $prix_for_duree($f['duree']);
    if (!$grille) continue;
    [$min_ligne, $min_pres] = $grille;
    if ($f['price'] < $min_ligne || ($f['price_pres'] > 0 && $f['price_pres'] < $min_pres)) {
        $incoherence_tarif_duree[] = [
            'f'         => $f,
            'attendu_ligne' => $min_ligne,
            'attendu_pres'  => $min_pres,
        ];
    }
}

/* ════════════════════════════════════════
   ANALYSE 5 : DISTRIBUTION CATÉGORIES
   ════════════════════════════════════════ */
$by_cat = [];
foreach ($all as $f) {
    $by_cat[$f['category']][] = $f['name'];
}
arsort($by_cat);
$cats_suspects = array_filter($by_cat, fn($g) => count($g) > 80 || count($g) < 2);

/* ════════════════════════════════════════
   ANALYSE 6 : FORMATIONS SANS TARIF
   ════════════════════════════════════════ */
$sans_tarif = array_filter($all, fn($f) => $f['price'] === 0);

/* ════════════════════════════════════════
   RÉSUMÉ HTML
   ════════════════════════════════════════ */
$nb_doublons_exacts   = array_sum(array_map(fn($g) => count($g) - 1, $doublons_exacts));
$nb_doublons_approches = count($doublons_approches);
$nb_sous_plancher     = count($sous_plancher);
$nb_pres_sous         = count($pres_sous_plancher);
$nb_incoherence_td    = count($incoherence_tarif_duree);
$nb_sans_tarif        = count($sans_tarif);
$nb_ok = $total - $nb_doublons_exacts - $nb_sous_plancher - $nb_incoherence_td;

?><!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Audit Catalogue IBIG EDUFORM</title>
<style>
body{font-family:Arial,sans-serif;max-width:1100px;margin:0 auto;padding:20px;background:#f8fafc;color:#1e293b}
h1{color:#0a1733;border-bottom:3px solid #f59e0b;padding-bottom:10px}
h2{color:#0a1733;margin-top:30px;font-size:1.1rem;border-left:4px solid #f59e0b;padding-left:10px}
.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin:20px 0}
.card{background:#fff;border-radius:8px;padding:16px;box-shadow:0 1px 4px rgba(0,0,0,.1);text-align:center}
.card .val{font-size:2rem;font-weight:bold;display:block}
.card .lbl{font-size:.8rem;color:#64748b;margin-top:4px}
.ok{color:#16a34a}.warn{color:#d97706}.err{color:#dc2626}
table{width:100%;border-collapse:collapse;font-size:.85rem;margin-top:8px;background:#fff;border-radius:6px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,.08)}
th{background:#0a1733;color:#fff;padding:8px 10px;text-align:left}
td{padding:7px 10px;border-bottom:1px solid #e2e8f0;vertical-align:top}
tr:last-child td{border-bottom:none}
tr:nth-child(even){background:#f8fafc}
.badge{display:inline-block;padding:2px 8px;border-radius:4px;font-size:.75rem;font-weight:bold}
.api{background:#dbeafe;color:#1e40af}.local{background:#dcfce7;color:#166534}
.red{background:#fee2e2;color:#991b1b}.yellow{background:#fef9c3;color:#854d0e}
.section{background:#fff;border-radius:8px;padding:16px;margin-bottom:20px;box-shadow:0 1px 4px rgba(0,0,0,.08)}
</style>
</head>
<body>
<h1>📊 Audit Catalogue IBIG EDUFORM</h1>
<p style="color:#64748b">Généré le <?= date('d/m/Y à H:i') ?> — <?= $total ?> formations analysées (<?= count($api_formations) ?> API + <?= count($local_rows) ?> locales)</p>

<!-- RÉSUMÉ -->
<div class="grid">
  <div class="card"><span class="val"><?= $total ?></span><span class="lbl">Formations totales</span></div>
  <div class="card"><span class="val <?= $nb_doublons_exacts > 0 ? 'err' : 'ok' ?>"><?= $nb_doublons_exacts ?></span><span class="lbl">Doublons exacts</span></div>
  <div class="card"><span class="val <?= $nb_doublons_approches > 0 ? 'warn' : 'ok' ?>"><?= $nb_doublons_approches ?></span><span class="lbl">Doublons approchés</span></div>
  <div class="card"><span class="val <?= $nb_sous_plancher > 0 ? 'err' : 'ok' ?>"><?= $nb_sous_plancher ?></span><span class="lbl">Tarifs API bruts &lt; 200K</span></div>
  <div class="card"><span class="val <?= $nb_incoherence_td > 0 ? 'warn' : 'ok' ?>"><?= $nb_incoherence_td ?></span><span class="lbl">Incoh. tarif/durée (local)</span></div>
  <div class="card"><span class="val <?= $nb_sans_tarif > 0 ? 'warn' : 'ok' ?>"><?= $nb_sans_tarif ?></span><span class="lbl">Sans tarif (prix=0)</span></div>
</div>

<!-- DOUBLONS EXACTS -->
<div class="section">
<h2>🔴 Doublons exacts (<?= count($doublons_exacts) ?> groupes)</h2>
<?php if (empty($doublons_exacts)): ?>
  <p class="ok">✅ Aucun doublon exact détecté.</p>
<?php else: ?>
<table>
<tr><th>Formation</th><th>Sources</th><th>Prix affichés</th></tr>
<?php foreach ($doublons_exacts as $norm_key => $grp): ?>
<tr>
  <td><strong><?= htmlspecialchars($grp[0]['name']) ?></strong><br><small style="color:#64748b"><?= htmlspecialchars($norm_key) ?></small></td>
  <td><?php foreach ($grp as $g): ?><span class="badge <?= strtolower($g['source']) ?>"><?= $g['source'] ?></span> <?php endforeach; ?></td>
  <td><?php foreach ($grp as $g): ?><?= $fcfa($g['price']) ?> <?php endforeach; ?></td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>
</div>

<!-- DOUBLONS APPROCHÉS -->
<div class="section">
<h2>🟡 Doublons approchés (<?= $nb_doublons_approches ?> paires — similarité ≥ 75%)</h2>
<?php if (empty($doublons_approches)): ?>
  <p class="ok">✅ Aucun doublon approché détecté.</p>
<?php else: ?>
<table>
<tr><th>Formation A</th><th>Formation B</th><th>Similarité</th><th>Tarifs</th></tr>
<?php foreach (array_slice($doublons_approches, 0, 60) as $d): ?>
<tr>
  <td><span class="badge <?= strtolower($d['a']['source']) ?>"><?= $d['a']['source'] ?></span> <?= htmlspecialchars($d['a']['name']) ?></td>
  <td><span class="badge <?= strtolower($d['b']['source']) ?>"><?= $d['b']['source'] ?></span> <?= htmlspecialchars($d['b']['name']) ?></td>
  <td><?= $d['ratio'] ?>%</td>
  <td><?= $fcfa($d['a']['price']) ?> / <?= $fcfa($d['b']['price']) ?></td>
</tr>
<?php endforeach; ?>
<?php if (count($doublons_approches) > 60): ?>
<tr><td colspan="4" style="color:#64748b;text-align:center">... et <?= count($doublons_approches) - 60 ?> autres paires</td></tr>
<?php endif; ?>
</table>
<?php endif; ?>
</div>

<!-- TARIFS BRUTS API SOUS PLANCHER -->
<div class="section">
<h2>🔴 Formations API avec tarif brut &lt; 200 000 F (corrigées automatiquement à l'affichage)</h2>
<?php if (empty($sous_plancher)): ?>
  <p class="ok">✅ Aucun tarif API sous 200 000 F.</p>
<?php else: ?>
<p style="color:#64748b;font-size:.85rem">Ces formations sont corrigées automatiquement à l'affichage (plancher 200 000 F). Leur tarif original dans l'API est indiqué.</p>
<table>
<tr><th>Formation</th><th>Catégorie</th><th>Tarif brut API</th><th>Tarif affiché</th></tr>
<?php foreach (array_values($sous_plancher) as $f): ?>
<tr>
  <td><?= htmlspecialchars($f['name']) ?></td>
  <td><?= htmlspecialchars($f['category']) ?></td>
  <td style="color:#dc2626"><strong><?= $fcfa($f['price_raw']) ?></strong></td>
  <td style="color:#16a34a"><?= $fcfa($f['price']) ?></td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>
</div>

<!-- INCOHÉRENCES TARIF/DURÉE LOCALES -->
<div class="section">
<h2>🟡 Incohérences tarif/durée — formations locales</h2>
<?php if (empty($incoherence_tarif_duree)): ?>
  <p class="ok">✅ Toutes les formations locales respectent la grille tarifaire horaire IBIG EDUFORM.</p>
<?php else: ?>
<table>
<tr><th>Formation</th><th>Durée</th><th>Tarif actuel</th><th>Tarif attendu EDUFORM</th></tr>
<?php foreach ($incoherence_tarif_duree as $item): ?>
<?php $f = $item['f']; ?>
<tr>
  <td><?= htmlspecialchars($f['name']) ?><br><small style="color:#64748b">slug: <?= $f['slug'] ?></small></td>
  <td><?= $f['duree'] ?></td>
  <td style="color:#dc2626"><?= $fcfa($f['price']) ?> / <?= $fcfa($f['price_pres']) ?></td>
  <td style="color:#16a34a"><?= $fcfa($item['attendu_ligne']) ?> / <?= $fcfa($item['attendu_pres']) ?></td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>
</div>

<!-- SANS TARIF -->
<div class="section">
<h2>🟡 Formations sans tarif (prix = 0) — <?= $nb_sans_tarif ?></h2>
<?php if (empty($sans_tarif)): ?>
  <p class="ok">✅ Aucune formation sans tarif.</p>
<?php else: ?>
<table>
<tr><th>Formation</th><th>Source</th><th>Catégorie</th></tr>
<?php foreach (array_slice(array_values($sans_tarif), 0, 40) as $f): ?>
<tr>
  <td><?= htmlspecialchars($f['name']) ?></td>
  <td><span class="badge <?= strtolower($f['source']) ?>"><?= $f['source'] ?></span></td>
  <td><?= htmlspecialchars($f['category']) ?></td>
</tr>
<?php endforeach; ?>
<?php if ($nb_sans_tarif > 40): ?><tr><td colspan="3" style="color:#64748b;text-align:center">... et <?= $nb_sans_tarif - 40 ?> autres</td></tr><?php endif; ?>
</table>
<?php endif; ?>
</div>

<!-- DISTRIBUTION CATÉGORIES -->
<div class="section">
<h2>📂 Distribution par catégorie (<?= count($by_cat) ?> catégories)</h2>
<table>
<tr><th>Catégorie</th><th>Nb formations</th><th>Alerte</th></tr>
<?php foreach ($by_cat as $cat => $names): ?>
<tr>
  <td><?= htmlspecialchars($cat ?: '(vide)') ?></td>
  <td><?= count($names) ?></td>
  <td><?php
    if (count($names) > 100) echo '<span class="badge red">⚠️ Trop de formations</span>';
    elseif (count($names) < 3) echo '<span class="badge yellow">⚠️ Très peu</span>';
    else echo '<span style="color:#16a34a">✅</span>';
  ?></td>
</tr>
<?php endforeach; ?>
</table>
</div>

<p style="color:#94a3b8;font-size:.8rem;text-align:center;margin-top:30px">SUPPRIMEZ CE FICHIER APRÈS CONSULTATION — _audit_catalogue.php</p>
</body>
</html>
