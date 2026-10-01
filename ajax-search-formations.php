<?php
declare(strict_types=1);
/*
 * AJAX — recherche formations pour popup exit-intent
 * GET ?q=texte  → JSON [{id, titre, domaine}, ...]
 */

/* Bloquer toute sortie parasite avant les headers */
error_reporting(0);
@ob_start();

require_once __DIR__ . '/core/bootstrap.php';

@ob_end_clean();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$q = trim((string)($_GET['q'] ?? ''));

if (strlen($q) < 2) {
    echo '[]';
    exit;
}

try {
    $pdo  = Database::connect();
    $like = '%' . $q . '%';

    $stmt = $pdo->prepare("
        SELECT id, titre, domaine
        FROM formations
        WHERE statut = 'active'
          AND (titre LIKE ? OR domaine LIKE ?)
        ORDER BY
          CASE WHEN titre LIKE ? THEN 0 ELSE 1 END,
          titre
        LIMIT 40
    ");
    $stmt->execute([$like, $like, $like]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $out = [];
    foreach ($rows as $r) {
        $out[] = ['id' => (int)$r['id'], 'titre' => $r['titre'], 'domaine' => $r['domaine']];
    }
    echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

} catch (Exception $e) {
    echo '[]';
}
