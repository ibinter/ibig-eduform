<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';
Middleware::requireAuth();
$pdo = Database::connect();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$confirm = $_GET['confirm'] ?? '';

/*
 * Corrige les fautes de liaison en français :
 * "du A/E/I/O/U..." → "de l'A/E/I/O/U..."
 * "du Entrepreneur" → "de l'Entrepreneur"
 * "du Enseignant"  → "de l'"Enseignant"
 * etc.
 */
function corriger_liaison(string $titre): string {
    // du [Voyelle...] → de l'[Voyelle...]
    return preg_replace_callback(
        '/\bdu\s+([AEIOUÀÂÄÉÈÊËÎÏÔÙÛÜŒaeiouàâäéèêëîïôùûüœ])/u',
        fn($m) => "de l'" . $m[1],
        $titre
    ) ?? $titre;
}

// Récupérer toutes les formations actives
$rows = $pdo->query("SELECT id, titre, slug FROM formations WHERE statut='active' ORDER BY titre")->fetchAll(PDO::FETCH_ASSOC);

$corrections = [];
foreach ($rows as $r) {
    $nouveau = corriger_liaison((string)$r['titre']);
    if ($nouveau !== (string)$r['titre']) {
        $corrections[] = [
            'id'     => (int)$r['id'],
            'slug'   => (string)$r['slug'],
            'avant'  => (string)$r['titre'],
            'apres'  => $nouveau,
        ];
    }
}

// ─── PREVIEW ──────────────────────────────────────────────────────────────────
if ($confirm !== 'oui') {
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Fix liaisons titres</title>
    <style>body{font-family:monospace;background:#0d1f3c;color:#e2e8f0;padding:24px;font-size:13px}
    h1{color:#f59e0b}h2{color:#93c5fd}.ok{color:#34d399}.skip{color:#94a3b8}.none{color:#34d399;font-size:14px}
    table{border-collapse:collapse;width:100%;margin-bottom:20px}
    th{background:#1e3a6e;color:#fff;padding:6px 10px;text-align:left}
    td{padding:5px 10px;border-bottom:1px solid #1e3a6e;vertical-align:top}
    .avant{color:#f87171}.apres{color:#34d399}
    .btn{display:inline-block;padding:12px 28px;background:#f59e0b;color:#000;border-radius:6px;text-decoration:none;font-weight:bold;margin:16px 0}
    </style></head><body>';
    echo '<h1>🔤 Correction liaisons — titres formations</h1>';
    echo '<p class="skip">Règle : <strong>"du A/E/I/O/U"</strong> → <strong>"de l\'A/E/I/O/U"</strong></p>';

    if (!$corrections) {
        echo '<p class="none">✅ Aucune erreur de liaison détectée dans les titres.</p>';
    } else {
        echo '<p class="ok">⚠️ ' . count($corrections) . ' titre(s) à corriger :</p>';
        echo '<table><thead><tr><th>ID</th><th>Slug</th><th>Avant</th><th>Après</th></tr></thead><tbody>';
        foreach ($corrections as $c) {
            echo '<tr>'
                . '<td>' . $c['id'] . '</td>'
                . '<td>' . htmlspecialchars($c['slug']) . '</td>'
                . '<td class="avant">' . htmlspecialchars($c['avant']) . '</td>'
                . '<td class="apres">' . htmlspecialchars($c['apres']) . '</td>'
                . '</tr>';
        }
        echo '</tbody></table>';
        echo '<a class="btn" href="?confirm=oui">🚀 CORRIGER EN BASE</a>';
    }
    echo '</body></html>';
    exit;
}

// ─── UPDATE ───────────────────────────────────────────────────────────────────
if (!$corrections) {
    echo '<html><head><meta charset="utf-8"></head><body style="font-family:monospace;background:#0d1f3c;color:#34d399;padding:20px">';
    echo '<h1>✅ Rien à corriger.</h1></body></html>';
    exit;
}

$ok  = 0;
$err = [];
$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare("UPDATE formations SET titre = ?, updated_at = NOW() WHERE id = ?");
    foreach ($corrections as $c) {
        $stmt->execute([$c['apres'], $c['id']]);
        $ok++;
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
h1{color:#34d399}.ok{color:#34d399}.av{color:#f87171}.ap{color:#34d399}
table{border-collapse:collapse;width:100%}th{background:#1e3a6e;color:#fff;padding:6px 10px;text-align:left}
td{padding:5px 10px;border-bottom:1px solid #1e3a6e}
a{color:#f59e0b;margin-right:12px}</style></head><body>';
echo '<h1>✅ ' . $ok . ' titre(s) corrigé(s)</h1>';
echo '<table><thead><tr><th>ID</th><th>Avant</th><th>Après</th></tr></thead><tbody>';
foreach ($corrections as $c) {
    echo '<tr><td>' . $c['id'] . '</td>'
        . '<td class="av">' . htmlspecialchars($c['avant']) . '</td>'
        . '<td class="ap">' . htmlspecialchars($c['apres']) . '</td>'
        . '</tr>';
}
echo '</tbody></table>';
echo '<p><a href="../catalogue-formations.php">→ Catalogue</a></p>';
echo '</body></html>';
