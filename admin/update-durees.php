<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';
Middleware::requireAuth();
$pdo = Database::connect();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$confirm = $_GET['confirm'] ?? '';

/*
 * Corrections de durées — liste des corrections validées par l'admin.
 * Format : ['slug_contains' ou 'titre_contains', critère, ancienne_duree, nouvelle_duree, tarifs]
 * Les tarifs sont recalculés selon la grille :
 *   15H → en_ligne 170k / presentiel 215k
 *   20H → 225k / 285k
 *   25H → 280k / 355k
 *   30H → 340k / 430k
 *   35H → 395k / 500k
 *   40H → 450k / 570k
 *   45H → 510k / 645k
 *   50H → 565k / 715k
 */
$corrections = [
    [
        'match_field' => 'slug',
        'match_value' => 'directeur-administratif-financier',
        'label'       => 'DAF — Directeur Administratif et Financier',
        'duree_old'   => '25 heures',
        'duree_new'   => '40 heures',
        'heures_new'  => 40,
        'en_ligne'    => 450000,
        'presentiel'  => 570000,
    ],
];

// ─── Chercher les formations concernées ──────────────────────────────────────
$rows_found = [];
foreach ($corrections as $i => $c) {
    $col = $c['match_field'] === 'slug' ? 'slug' : 'titre';
    $stmt = $pdo->prepare("
        SELECT id, titre, slug, duree, tarif_en_ligne, tarif_presentiel
        FROM formations
        WHERE $col LIKE ?
    ");
    $stmt->execute(['%' . $c['match_value'] . '%']);
    $found = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $rows_found[$i] = $found;
}

// ─── APERÇU ──────────────────────────────────────────────────────────────────
if ($confirm !== 'oui') {
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Correction durées</title>
    <style>body{font-family:monospace;background:#0d1f3c;color:#e2e8f0;padding:24px;font-size:13px}
    h1{color:#f59e0b}h2{color:#93c5fd}.ok{color:#34d399}.warn{color:#fbbf24}.err{color:#f87171}
    table{border-collapse:collapse;width:100%;margin-bottom:20px}
    th{background:#1e3a6e;color:#fff;padding:6px 10px;text-align:left}
    td{padding:5px 10px;border-bottom:1px solid #1e3a6e}
    .av{color:#f87171}.ap{color:#34d399}
    .btn{display:inline-block;padding:12px 28px;background:#f59e0b;color:#000;border-radius:6px;text-decoration:none;font-weight:bold;margin:16px 0}
    .none{color:#94a3b8}
    </style></head><body>';

    echo '<h1>⏱️ Correction des durées formations</h1>';

    $total_a_corriger = 0;
    foreach ($corrections as $i => $c) {
        $found = $rows_found[$i];
        echo '<h2>' . htmlspecialchars($c['label']) . '</h2>';
        echo '<p>Correction : <span class="av">' . htmlspecialchars($c['duree_old']) . '</span> → <span class="ap">' . htmlspecialchars($c['duree_new']) . '</span></p>';
        echo '<p>Tarifs : en_ligne <span class="ap">' . number_format($c['en_ligne'], 0, ',', ' ') . ' FCFA</span> | présentiel <span class="ap">' . number_format($c['presentiel'], 0, ',', ' ') . ' FCFA</span></p>';

        if (!$found) {
            echo '<p class="none">⚠️ Aucune formation trouvée avec ce critère.</p>';
        } else {
            $total_a_corriger += count($found);
            echo '<table><thead><tr><th>ID</th><th>Titre</th><th>Durée actuelle</th><th>Tarif en ligne actuel</th><th>Tarif présentiel actuel</th></tr></thead><tbody>';
            foreach ($found as $r) {
                echo '<tr>'
                    . '<td>' . $r['id'] . '</td>'
                    . '<td>' . htmlspecialchars($r['titre']) . '</td>'
                    . '<td class="av">' . htmlspecialchars($r['duree']) . '</td>'
                    . '<td>' . number_format((int)$r['tarif_en_ligne'], 0, ',', ' ') . '</td>'
                    . '<td>' . number_format((int)$r['tarif_presentiel'], 0, ',', ' ') . '</td>'
                    . '</tr>';
            }
            echo '</tbody></table>';
        }
    }

    if ($total_a_corriger > 0) {
        echo '<a class="btn" href="?confirm=oui">🚀 CORRIGER ' . $total_a_corriger . ' FORMATION(S)</a>';
    } else {
        echo '<p class="ok">✅ Aucune formation à corriger trouvée en base.</p>';
    }
    echo '</body></html>';
    exit;
}

// ─── CORRECTION ──────────────────────────────────────────────────────────────
$ok  = 0;
$err = [];

$pdo->beginTransaction();
try {
    $stmt_f = $pdo->prepare("
        UPDATE formations
        SET duree = ?, tarif_en_ligne = ?, tarif_presentiel = ?, updated_at = NOW()
        WHERE id = ?
    ");
    $stmt_n = $pdo->prepare("
        UPDATE formation_niveaux
        SET duree_heures = ?, tarif_en_ligne = ?, tarif_presentiel = ?, updated_at = NOW()
        WHERE formation_id = ?
    ");

    foreach ($corrections as $i => $c) {
        foreach ($rows_found[$i] as $r) {
            $stmt_f->execute([$c['duree_new'], $c['en_ligne'], $c['presentiel'], $r['id']]);
            $stmt_n->execute([$c['heures_new'], $c['en_ligne'], $c['presentiel'], $r['id']]);
            $ok++;
        }
    }
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    echo '<html><head><meta charset="utf-8"></head><body style="font-family:monospace;background:#0d1f3c;color:#f87171;padding:20px">';
    echo '<h1>❌ Erreur</h1><pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
    echo '</body></html>';
    exit;
}

echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>OK</title>
<style>body{font-family:monospace;background:#0d1f3c;color:#e2e8f0;padding:24px}
h1{color:#34d399}.ok{color:#34d399}a{color:#f59e0b;margin-right:12px}
table{border-collapse:collapse;width:100%}th{background:#1e3a6e;color:#fff;padding:6px 10px;text-align:left}
td{padding:5px 10px;border-bottom:1px solid #1e3a6e}
.ap{color:#34d399}</style></head><body>';
echo '<h1>✅ ' . $ok . ' formation(s) mise(s) à jour</h1>';
echo '<table><thead><tr><th>Formation</th><th>Nouvelle durée</th><th>Tarif en ligne</th><th>Tarif présentiel</th></tr></thead><tbody>';
foreach ($corrections as $i => $c) {
    foreach ($rows_found[$i] as $r) {
        echo '<tr>'
            . '<td>' . htmlspecialchars($r['titre']) . '</td>'
            . '<td class="ap">' . htmlspecialchars($c['duree_new']) . '</td>'
            . '<td class="ap">' . number_format($c['en_ligne'], 0, ',', ' ') . ' FCFA</td>'
            . '<td class="ap">' . number_format($c['presentiel'], 0, ',', ' ') . ' FCFA</td>'
            . '</tr>';
    }
}
echo '</tbody></table>';
echo '<p><a href="scan-catalogue.php">→ Scan catalogue</a></p>';
echo '</body></html>';
