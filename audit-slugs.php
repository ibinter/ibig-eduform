<?php
require_once __DIR__ . '/core/config.php';
require_once __DIR__ . '/core/database.php';

header('Content-Type: application/json; charset=utf-8');

$pdo = Database::connect();

function make_slug($titre) {
    $s = mb_strtolower(trim($titre), 'UTF-8');
    $s2 = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
    if ($s2 !== false && trim($s2) !== '') {
        $s = $s2;
    }
    $s = preg_replace('/[^a-z0-9\s\-]/', '', $s);
    $s = preg_replace('/[\s\-]+/', '-', $s);
    return trim($s, '-');
}

$action = isset($_GET['action']) ? $_GET['action'] : 'preview';

$rows = $pdo->query(
    "SELECT id, titre, slug FROM formations
     WHERE statut = 'active' AND (annee = 0 OR annee IS NULL) AND is_samedi_pro = 0"
)->fetchAll(PDO::FETCH_ASSOC);

$corrupt = array();
foreach ($rows as $r) {
    $expected = make_slug($r['titre']);
    $current  = isset($r['slug']) ? $r['slug'] : '';
    $firstExp = isset($expected[0]) ? $expected[0] : '';
    $firstCur = isset($current[0])  ? $current[0]  : '';
    if ($firstExp !== '' && $firstCur !== $firstExp) {
        $corrupt[] = array(
            'id'       => $r['id'],
            'titre'    => $r['titre'],
            'slug_bad' => $current,
            'slug_new' => $expected,
        );
    }
}

if ($action === 'preview') {
    echo json_encode(array(
        'total'           => count($rows),
        'corrupt_count'   => count($corrupt),
        'samples_corrupt' => array_slice($corrupt, 0, 8),
    ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'fix') {
    $updated = 0;
    $skipped = array();
    $pdo->beginTransaction();
    try {
        foreach ($corrupt as $c) {
            $newSlug = $c['slug_new'];
            $check = $pdo->prepare("SELECT id FROM formations WHERE slug = ? AND id != ?");
            $check->execute(array($newSlug, $c['id']));
            if ($check->fetch()) {
                $skipped[] = array('id' => $c['id'], 'titre' => $c['titre'], 'reason' => 'duplicate:' . $newSlug);
                continue;
            }
            $upd = $pdo->prepare("UPDATE formations SET slug = ? WHERE id = ?");
            $upd->execute(array($newSlug, $c['id']));
            $updated++;
        }
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(array('error' => $e->getMessage()));
        exit;
    }
    echo json_encode(array(
        'updated' => $updated,
        'skipped' => count($skipped),
        'skip_details' => array_slice($skipped, 0, 5),
    ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode(array('error' => 'action inconnu'));
