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
    $s = str_replace('&', '', $s);
    $s = preg_replace('/[^a-z0-9\s\-]/', '', $s);
    $s = preg_replace('/[\s\-]+/', '-', $s);
    return trim($s, '-');
}

function is_corrupt($titre, $slug) {
    if (empty($slug) || empty($titre)) return false;
    // Le premier char du slug doit correspondre au premier char du titre normalisé
    $expectedFirst = mb_strtolower(mb_substr(trim($titre), 0, 1, 'UTF-8'), 'UTF-8');
    $expectedFirst2 = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $expectedFirst);
    if ($expectedFirst2 !== false && $expectedFirst2 !== '') {
        $expectedFirst = $expectedFirst2;
    }
    return $slug[0] !== $expectedFirst;
}

$action = isset($_GET['action']) ? $_GET['action'] : 'preview';

// Formations actives NON Samedi Pro (on ne touche jamais is_samedi_pro=1)
$rows = $pdo->query(
    "SELECT id, titre, slug, annee, is_samedi_pro FROM formations
     WHERE statut = 'active' AND is_samedi_pro = 0"
)->fetchAll(PDO::FETCH_ASSOC);

$corrupt = array();
foreach ($rows as $r) {
    if (is_corrupt($r['titre'], $r['slug'])) {
        $corrupt[] = array(
            'id'         => $r['id'],
            'titre'      => $r['titre'],
            'slug_bad'   => $r['slug'],
            'slug_new'   => make_slug($r['titre']),
            'annee'      => $r['annee'],
            'samedi_pro' => $r['is_samedi_pro'],
        );
    }
}

if ($action === 'preview') {
    echo json_encode(array(
        'total'           => count($rows),
        'corrupt_count'   => count($corrupt),
        'samples_corrupt' => array_slice($corrupt, 0, 10),
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
            // Éviter les doublons
            $check = $pdo->prepare("SELECT id FROM formations WHERE slug = ? AND id != ?");
            $check->execute(array($newSlug, $c['id']));
            if ($check->fetch()) {
                // Ajouter un suffixe numérique pour éviter le doublon
                $newSlug = $newSlug . '-' . $c['id'];
                // Vérifier encore
                $check2 = $pdo->prepare("SELECT id FROM formations WHERE slug = ? AND id != ?");
                $check2->execute(array($newSlug, $c['id']));
                if ($check2->fetch()) {
                    $skipped[] = array('id' => $c['id'], 'titre' => mb_substr($c['titre'],0,40,'UTF-8'));
                    continue;
                }
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
        'skip_sample' => array_slice($skipped, 0, 5),
    ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode(array('error' => 'action inconnu'));
