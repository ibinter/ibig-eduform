<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';
Middleware::requireAuth();
$pdo = Database::connect();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$action    = $_GET['action']    ?? 'preview';
$delete_id = isset($_GET['delete_id']) ? (int)$_GET['delete_id'] : 0;
$fix_all   = $action === 'fix_all';
$delete_one = $action === 'delete' && $delete_id > 0;

// Tarif cible validé par l'admin : 40H — 620 000 en ligne / 775 000 présentiel
const DUREE_CIBLE      = '40 heures';
const HEURES_CIBLE     = 40;
const EN_LIGNE_CIBLE   = 620000;
const PRESENTIEL_CIBLE = 775000;

// ─── SUPPRESSION UNITAIRE ─────────────────────────────────────────────────────
if ($delete_one) {
    $pdo->beginTransaction();
    try {
        $pdo->prepare("DELETE FROM formation_niveaux WHERE formation_id = ?")->execute([$delete_id]);
        $pdo->prepare("DELETE FROM formations WHERE id = ?")->execute([$delete_id]);
        $pdo->commit();
    } catch (Throwable $e) { $pdo->rollBack(); }
    header('Location: ?action=preview&msg=deleted');
    exit;
}

// ─── RÉCUPÉRER TOUTES LES FORMATIONS DAF ─────────────────────────────────────
$rows = $pdo->query("
    SELECT f.id, f.titre, f.slug, f.duree, f.tarif_en_ligne, f.tarif_presentiel,
           f.statut, f.created_at,
           GROUP_CONCAT(n.duree_heures ORDER BY n.ordre_affichage SEPARATOR ', ') as niveaux_h,
           GROUP_CONCAT(n.tarif_en_ligne ORDER BY n.ordre_affichage SEPARATOR ', ')  as niveaux_el,
           GROUP_CONCAT(n.tarif_presentiel ORDER BY n.ordre_affichage SEPARATOR ', ') as niveaux_pr
    FROM formations f
    LEFT JOIN formation_niveaux n ON n.formation_id = f.id
    WHERE LOWER(f.titre) LIKE '%daf%'
       OR LOWER(f.slug)  LIKE '%daf%'
    GROUP BY f.id
    ORDER BY f.created_at ASC
")->fetchAll(PDO::FETCH_ASSOC);

// ─── NORMALISER TOUT À 40H / 620k / 775k ─────────────────────────────────────
if ($fix_all) {
    $pdo->beginTransaction();
    try {
        $sf = $pdo->prepare("UPDATE formations SET duree=?, tarif_en_ligne=?, tarif_presentiel=?, updated_at=NOW() WHERE id=?");
        $sn = $pdo->prepare("UPDATE formation_niveaux SET duree_heures=?, tarif_en_ligne=?, tarif_presentiel=?, updated_at=NOW() WHERE formation_id=?");
        foreach ($rows as $r) {
            $sf->execute([DUREE_CIBLE, EN_LIGNE_CIBLE, PRESENTIEL_CIBLE, $r['id']]);
            $sn->execute([HEURES_CIBLE, EN_LIGNE_CIBLE, PRESENTIEL_CIBLE, $r['id']]);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        echo '<body style="font-family:monospace;background:#0d1f3c;color:#f87171;padding:20px"><h1>❌ ' . htmlspecialchars($e->getMessage()) . '</h1></body>';
        exit;
    }
    header('Location: ?action=preview&msg=fixed&nb=' . count($rows));
    exit;
}

$msg = $_GET['msg'] ?? '';
$nb  = (int)($_GET['nb'] ?? 0);
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Formations DAF — audit</title>
<style>
body{font-family:monospace;background:#0d1f3c;color:#e2e8f0;padding:24px;font-size:13px}
h1{color:#f59e0b}h2{color:#93c5fd}
.ok{color:#34d399}.warn{color:#fbbf24}.err{color:#f87171}
table{border-collapse:collapse;width:100%;margin-bottom:20px}
th{background:#1e3a6e;color:#fff;padding:6px 10px;text-align:left}
td{padding:6px 10px;border-bottom:1px solid #1e3a6e;vertical-align:top}
tr:hover td{background:#1a3358}
.slug{color:#7dd3fc;font-size:11px}
.bad{color:#f87171;font-weight:bold}
.good{color:#34d399}
.btn-del{background:#ef4444;color:#fff;border:none;padding:4px 12px;border-radius:4px;cursor:pointer;font-size:12px;text-decoration:none;display:inline-block}
.btn-fix{display:inline-block;padding:12px 28px;background:#f59e0b;color:#000;border-radius:6px;text-decoration:none;font-weight:bold;margin:12px 4px}
.btn-fix:hover{background:#d97706}
.cible{background:#052e16;border-left:3px solid #34d399;padding:10px 16px;border-radius:4px;margin:16px 0}
</style>
</head>
<body>
<h1>🔍 Formations DAF — audit complet</h1>

<?php if ($msg === 'fixed'): ?>
<p class="ok">✅ <?= $nb ?> formation(s) normalisée(s) à 40H / 620 000 / 775 000 FCFA.</p>
<?php elseif ($msg === 'deleted'): ?>
<p class="ok">✅ Formation supprimée.</p>
<?php endif; ?>

<div class="cible">
    <strong class="ok">Cible validée :</strong>
    40H — En ligne <strong>620 000 FCFA</strong> | Présentiel <strong>775 000 FCFA</strong>
</div>

<p><?= count($rows) ?> formation(s) DAF trouvée(s) dans le catalogue :</p>

<table>
<thead>
<tr>
    <th>ID</th><th>Titre</th><th>Slug</th><th>Durée</th>
    <th>En ligne</th><th>Présentiel</th><th>Niveaux (H)</th><th>Créé le</th><th>Action</th>
</tr>
</thead>
<tbody>
<?php foreach ($rows as $r):
    $h_ok  = trim((string)$r['duree']) === DUREE_CIBLE;
    $el_ok = (int)$r['tarif_en_ligne']   === EN_LIGNE_CIBLE;
    $pr_ok = (int)$r['tarif_presentiel'] === PRESENTIEL_CIBLE;
    $all_ok = $h_ok && $el_ok && $pr_ok;
?>
<tr>
    <td><?= $r['id'] ?></td>
    <td><?= htmlspecialchars($r['titre']) ?></td>
    <td class="slug"><?= htmlspecialchars($r['slug']) ?></td>
    <td class="<?= $h_ok  ? 'good' : 'bad' ?>"><?= htmlspecialchars($r['duree']) ?></td>
    <td class="<?= $el_ok ? 'good' : 'bad' ?>"><?= number_format((int)$r['tarif_en_ligne'],   0, ',', ' ') ?></td>
    <td class="<?= $pr_ok ? 'good' : 'bad' ?>"><?= number_format((int)$r['tarif_presentiel'], 0, ',', ' ') ?></td>
    <td><?= htmlspecialchars((string)$r['niveaux_h']) ?></td>
    <td style="font-size:11px;color:#94a3b8"><?= substr($r['created_at'], 0, 10) ?></td>
    <td>
        <a class="btn-del" href="?action=delete&delete_id=<?= $r['id'] ?>"
           onclick="return confirm('Supprimer #<?= $r['id'] ?> — <?= addslashes($r['titre']) ?> ?')">
           Supprimer
        </a>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<?php if ($rows): ?>
<a class="btn-fix" href="?action=fix_all"
   onclick="return confirm('Normaliser TOUTES les formations DAF à 40H / 620 000 / 775 000 FCFA ?')">
    ✅ NORMALISER TOUT (40H / 620k / 775k)
</a>
<?php endif; ?>

</body>
</html>
