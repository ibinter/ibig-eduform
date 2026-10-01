<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';
Middleware::requireAuth();
$pdo = Database::connect();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$action = $_GET['action'] ?? 'preview';
$delete_id = isset($_GET['delete_id']) ? (int)$_GET['delete_id'] : 0;

// ─── SUPPRESSION UNITAIRE ────────────────────────────────────────────────────
if ($action === 'delete' && $delete_id > 0) {
    try {
        $pdo->beginTransaction();
        $pdo->prepare("DELETE FROM formation_niveaux WHERE formation_id = ?")->execute([$delete_id]);
        $pdo->prepare("DELETE FROM formations WHERE id = ?")->execute([$delete_id]);
        $pdo->commit();
        header('Location: ?action=preview&msg=deleted&id=' . $delete_id);
        exit;
    } catch (Throwable $e) {
        $pdo->rollBack();
        header('Location: ?action=preview&msg=error');
        exit;
    }
}

// ─── RÉCUPÉRER TOUS LES DOUBLONS ─────────────────────────────────────────────
// Doublons par slug
$slug_doublons = $pdo->query("
    SELECT slug, COUNT(*) as nb
    FROM formations
    GROUP BY slug
    HAVING nb > 1
    ORDER BY nb DESC, slug
")->fetchAll(PDO::FETCH_ASSOC);

// Doublons par titre normalisé (trim + lowercase)
$titre_doublons = $pdo->query("
    SELECT LOWER(TRIM(titre)) as titre_norm, COUNT(*) as nb
    FROM formations
    GROUP BY titre_norm
    HAVING nb > 1
    ORDER BY nb DESC, titre_norm
")->fetchAll(PDO::FETCH_ASSOC);

// Détails de toutes les formations en doublon
$slug_list = array_column($slug_doublons, 'slug');
$titre_norm_list = array_column($titre_doublons, 'titre_norm');

$doublons_detail = [];

if ($slug_list) {
    $placeholders = implode(',', array_fill(0, count($slug_list), '?'));
    $rows = $pdo->prepare("
        SELECT id, titre, slug, domaine, statut, created_at
        FROM formations
        WHERE slug IN ($placeholders)
        ORDER BY slug, id
    ");
    $rows->execute($slug_list);
    foreach ($rows->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $doublons_detail['slug'][$r['slug']][] = $r;
    }
}

if ($titre_norm_list) {
    $placeholders = implode(',', array_fill(0, count($titre_norm_list), '?'));
    $rows = $pdo->prepare("
        SELECT id, titre, slug, domaine, statut, created_at
        FROM formations
        WHERE LOWER(TRIM(titre)) IN ($placeholders)
        ORDER BY LOWER(TRIM(titre)), id
    ");
    $rows->execute($titre_norm_list);
    $by_titre = [];
    foreach ($rows->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $by_titre[strtolower(trim($r['titre']))][] = $r;
    }
    // Garder seulement ceux pas déjà dans slug_doublons
    foreach ($by_titre as $k => $v) {
        $slugs_in_group = array_unique(array_column($v, 'slug'));
        if (count($slugs_in_group) > 1 || !in_array($slugs_in_group[0], $slug_list)) {
            $doublons_detail['titre'][$k] = $v;
        }
    }
}

// Stats globales
$stats = $pdo->query("
    SELECT
        COUNT(*) as total,
        SUM(CASE WHEN statut='active' THEN 1 ELSE 0 END) as actives,
        COUNT(DISTINCT domaine) as nb_domaines,
        COUNT(DISTINCT slug) as uniq_slugs
    FROM formations
")->fetch(PDO::FETCH_ASSOC);

$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Vérification doublons — Catalogue</title>
<style>
body { font-family: monospace; background: #0d1f3c; color: #e2e8f0; padding: 24px; font-size: 13px; }
h1 { color: #f59e0b; }
h2 { color: #93c5fd; margin-top: 32px; }
.ok { color: #34d399; }
.warn { color: #fbbf24; }
.err { color: #f87171; }
.stat { display: inline-block; background: #1e3a6e; padding: 10px 20px; border-radius: 6px; margin: 4px; text-align: center; }
.stat strong { display: block; font-size: 24px; color: #f59e0b; }
table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
th { background: #1e3a6e; color: #fff; padding: 6px 10px; text-align: left; }
td { padding: 5px 10px; border-bottom: 1px solid #1e3a6e; vertical-align: top; }
tr:hover td { background: #1a3358; }
.id { color: #94a3b8; width: 50px; }
.slug { color: #7dd3fc; }
.date { color: #94a3b8; font-size: 11px; }
.btn-del { background: #ef4444; color: #fff; border: none; padding: 3px 10px; border-radius: 4px; cursor: pointer; font-size: 12px; text-decoration: none; }
.btn-del:hover { background: #dc2626; }
.badge { display: inline-block; background: #f59e0b; color: #000; border-radius: 3px; padding: 1px 6px; font-size: 11px; font-weight: bold; }
.group { background: #0f2540; border-left: 3px solid #f59e0b; margin-bottom: 24px; padding: 12px; }
.group-title { color: #fbbf24; font-weight: bold; margin-bottom: 8px; }
.keep { background: #052e16; }
.duplicate { background: #2d0f0f; }
</style>
</head>
<body>
<h1>🔍 Vérification des doublons — Catalogue général</h1>

<?php if ($msg === 'deleted'): ?>
<p class="ok">✅ Formation #<?= (int)$_GET['id'] ?> supprimée avec succès.</p>
<?php elseif ($msg === 'error'): ?>
<p class="err">❌ Erreur lors de la suppression.</p>
<?php endif; ?>

<div style="margin: 20px 0;">
    <div class="stat"><strong><?= $stats['total'] ?></strong> formations total</div>
    <div class="stat"><strong><?= $stats['actives'] ?></strong> actives</div>
    <div class="stat"><strong><?= $stats['nb_domaines'] ?></strong> domaines</div>
    <div class="stat"><strong><?= $stats['uniq_slugs'] ?></strong> slugs uniques</div>
</div>

<?php
$total_doublons = count($slug_doublons) + count($titre_doublons ?? []);
if ($total_doublons === 0):
?>
<p class="ok" style="font-size:16px;">✅ Aucun doublon détecté dans le catalogue !</p>
<?php else: ?>
<p class="warn">⚠️ <?= $total_doublons ?> groupe(s) de doublons détectés</p>

<?php if (!empty($doublons_detail['slug'])): ?>
<h2>📌 Doublons par SLUG (<?= count($doublons_detail['slug']) ?> groupes)</h2>
<p class="warn">Même slug = même URL = conflit. Garder le plus récent, supprimer les anciens.</p>

<?php foreach ($doublons_detail['slug'] as $slug => $rows): ?>
<div class="group">
    <div class="group-title">slug: <?= htmlspecialchars($slug) ?> <span class="badge"><?= count($rows) ?>x</span></div>
    <table>
    <thead><tr><th>ID</th><th>Titre</th><th>Domaine</th><th>Statut</th><th>Créé le</th><th>Action</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $i => $r): ?>
    <tr class="<?= $i === count($rows) - 1 ? 'keep' : 'duplicate' ?>">
        <td class="id"><?= $r['id'] ?></td>
        <td><?= htmlspecialchars($r['titre']) ?><?= $i === count($rows) - 1 ? ' <span style="color:#34d399;font-size:11px;">[GARDER]</span>' : '' ?></td>
        <td><?= htmlspecialchars($r['domaine']) ?></td>
        <td><?= $r['statut'] ?></td>
        <td class="date"><?= $r['created_at'] ?></td>
        <td><?php if ($i < count($rows) - 1): ?>
            <a class="btn-del" href="?action=delete&delete_id=<?= $r['id'] ?>" onclick="return confirm('Supprimer #<?= $r['id'] ?> - <?= addslashes($r['titre']) ?> ?')">Supprimer</a>
        <?php else: ?>
            <span style="color:#34d399;">✓</span>
        <?php endif; ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
    </table>
</div>
<?php endforeach; ?>
<?php endif; ?>

<?php if (!empty($doublons_detail['titre'])): ?>
<h2>📝 Doublons par TITRE (slugs différents — <?= count($doublons_detail['titre']) ?> groupes)</h2>
<p class="warn">Même titre, slugs différents. Vérifier manuellement lequel garder.</p>

<?php foreach ($doublons_detail['titre'] as $titre_norm => $rows): ?>
<div class="group">
    <div class="group-title">titre: "<?= htmlspecialchars($titre_norm) ?>" <span class="badge"><?= count($rows) ?>x</span></div>
    <table>
    <thead><tr><th>ID</th><th>Titre exact</th><th>Slug</th><th>Domaine</th><th>Statut</th><th>Créé le</th><th>Action</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $i => $r): ?>
    <tr class="<?= $i === count($rows) - 1 ? 'keep' : 'duplicate' ?>">
        <td class="id"><?= $r['id'] ?></td>
        <td><?= htmlspecialchars($r['titre']) ?><?= $i === count($rows) - 1 ? ' <span style="color:#34d399;font-size:11px;">[GARDER]</span>' : '' ?></td>
        <td class="slug"><?= htmlspecialchars($r['slug']) ?></td>
        <td><?= htmlspecialchars($r['domaine']) ?></td>
        <td><?= $r['statut'] ?></td>
        <td class="date"><?= $r['created_at'] ?></td>
        <td><?php if ($i < count($rows) - 1): ?>
            <a class="btn-del" href="?action=delete&delete_id=<?= $r['id'] ?>" onclick="return confirm('Supprimer #<?= $r['id'] ?> - <?= addslashes($r['titre']) ?> ?')">Supprimer</a>
        <?php else: ?>
            <span style="color:#34d399;">✓</span>
        <?php endif; ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
    </table>
</div>
<?php endforeach; ?>
<?php endif; ?>

<?php endif; ?>

<h2>📋 Catalogue complet par domaine</h2>
<?php
$all = $pdo->query("
    SELECT id, titre, slug, domaine, statut, created_at
    FROM formations
    ORDER BY domaine, titre
")->fetchAll(PDO::FETCH_ASSOC);

$by_domain = [];
foreach ($all as $r) {
    $by_domain[$r['domaine']][] = $r;
}
?>
<?php foreach ($by_domain as $domaine => $rows): ?>
<h3 style="color:#7dd3fc;margin-top:24px;">
    <?= htmlspecialchars($domaine) ?>
    <span style="color:#94a3b8;font-size:12px;">(<?= count($rows) ?> formations)</span>
</h3>
<table>
<thead><tr><th>ID</th><th>Titre</th><th>Slug</th><th>Statut</th><th>Créé le</th></tr></thead>
<tbody>
<?php foreach ($rows as $r): ?>
<tr>
    <td class="id"><?= $r['id'] ?></td>
    <td><?= htmlspecialchars($r['titre']) ?></td>
    <td class="slug"><?= htmlspecialchars($r['slug']) ?></td>
    <td class="<?= $r['statut'] === 'active' ? 'ok' : 'warn' ?>"><?= $r['statut'] ?></td>
    <td class="date"><?= $r['created_at'] ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endforeach; ?>

</body>
</html>
