<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';
Middleware::requireAuth();
$pdo = Database::connect();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$confirm = $_GET['confirm'] ?? '';

$corrections = [
    [
        'slug'    => 'droit-ohada-affaires-pratique-entreprises',
        'search'  => 'dirigeants de PME, DAF, juristes',
        'replace' => 'dirigeants de PME, directeurs financiers, juristes',
    ],
    [
        'slug'    => 'contentieux-commercial-arbitrage-ohada',
        'search'  => 'juristes, DAF, dirigeants',
        'replace' => 'juristes, dirigeants',
    ],
];

// Charger les descriptions actuelles
$rows = [];
foreach ($corrections as $i => $c) {
    $stmt = $pdo->prepare("SELECT id, slug, description FROM formations WHERE slug = ?");
    $stmt->execute([$c['slug']]);
    $rows[$i] = $stmt->fetch(PDO::FETCH_ASSOC);
}

if ($confirm !== 'oui') {
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Fix descriptions OHADA</title>
    <style>body{font-family:monospace;background:#0d1f3c;color:#e2e8f0;padding:24px;font-size:13px}
    h1{color:#f59e0b}.ok{color:#34d399}.av{color:#f87171}.ap{color:#34d399}
    .btn{display:inline-block;padding:12px 28px;background:#f59e0b;color:#000;border-radius:6px;text-decoration:none;font-weight:bold;margin:16px 0}
    .none{color:#94a3b8}.box{background:#1e3a6e;padding:12px;border-radius:6px;margin:16px 0}
    </style></head><body>';
    echo '<h1>🔤 Fix descriptions OHADA — retrait de "DAF"</h1>';

    $nb = 0;
    foreach ($corrections as $i => $c) {
        $r = $rows[$i];
        if (!$r) { echo '<p class="none">⚠️ Formation non trouvée : ' . htmlspecialchars($c['slug']) . '</p>'; continue; }
        $new_desc = str_replace($c['search'], $c['replace'], $r['description']);
        if ($new_desc === $r['description']) { echo '<p class="ok">✅ ' . htmlspecialchars($r['slug']) . ' — déjà correct.</p>'; continue; }
        $nb++;
        echo '<div class="box">';
        echo '<p><strong>' . htmlspecialchars($r['slug']) . '</strong> (ID ' . $r['id'] . ')</p>';
        echo '<p class="av">Avant : ' . htmlspecialchars(substr($r['description'], 0, 200)) . '…</p>';
        echo '<p class="ap">Après : ' . htmlspecialchars(substr($new_desc, 0, 200)) . '…</p>';
        echo '</div>';
    }

    if ($nb > 0) {
        echo '<a class="btn" href="?confirm=oui">🚀 CORRIGER ' . $nb . ' DESCRIPTION(S)</a>';
    } else {
        echo '<p class="ok">✅ Aucune correction nécessaire.</p>';
    }
    echo '</body></html>';
    exit;
}

// Appliquer les corrections
$ok = 0;
$pdo->beginTransaction();
try {
    foreach ($corrections as $i => $c) {
        $r = $rows[$i];
        if (!$r) continue;
        $new_desc = str_replace($c['search'], $c['replace'], $r['description']);
        if ($new_desc === $r['description']) continue;
        $pdo->prepare("UPDATE formations SET description = ?, updated_at = NOW() WHERE id = ?")
            ->execute([$new_desc, $r['id']]);
        $ok++;
    }
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    echo '<html><body style="font-family:monospace;background:#0d1f3c;color:#f87171;padding:20px"><h1>❌ Erreur</h1><pre>' . htmlspecialchars($e->getMessage()) . '</pre></body></html>';
    exit;
}

echo '<html><head><meta charset="utf-8"></head><body style="font-family:monospace;background:#0d1f3c;color:#34d399;padding:20px">';
echo '<h1>✅ ' . $ok . ' description(s) corrigée(s)</h1>';
echo '<p>"DAF" retiré des descriptions OHADA.</p>';
echo '<p><a style="color:#f59e0b" href="scan-catalogue.php">→ Scan catalogue</a></p>';
echo '</body></html>';
