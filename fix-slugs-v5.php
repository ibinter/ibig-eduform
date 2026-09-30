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
    $expectedFirst = mb_strtolower(mb_substr(trim($titre), 0, 1, 'UTF-8'), 'UTF-8');
    $expectedFirst2 = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $expectedFirst);
    if ($expectedFirst2 !== false && $expectedFirst2 !== '') {
        $expectedFirst = $expectedFirst2;
    }
    return $slug[0] !== $expectedFirst;
}

$action = isset($_GET['action']) ? $_GET['action'] : 'preview';

$rows = $pdo->query(
    "SELECT id, titre, slug, annee, is_samedi_pro FROM formations
     WHERE statut = 'active' AND is_samedi_pro = 0"
)->fetchAll(PDO::FETCH_ASSOC);

$corrupt = array();
foreach ($rows as $r) {
    if (is_corrupt($r['titre'], $r['slug'])) {
        $corrupt[] = array(
            'id'       => $r['id'],
            'titre'    => $r['titre'],
            'slug_bad' => $r['slug'],
            'slug_new' => make_slug($r['titre']),
            'annee'    => $r['annee'],
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
    $updated  = 0;
    $suffixed = 0;
    $skipped  = array();
    $errors   = array();

    foreach ($corrupt as $c) {
        $newSlug = $c['slug_new'];

        // Vérifier si ce slug exact est déjà pris par UNE AUTRE formation DIFFÉRENTE
        $check = $pdo->prepare(
            "SELECT id FROM formations WHERE slug = ? AND id != ? LIMIT 1"
        );
        $check->execute(array($newSlug, $c['id']));
        $existing = $check->fetch();

        if ($existing) {
            // Doublon → suffixer avec l'id pour rendre unique
            $newSlug = $c['slug_new'] . '-' . $c['id'];
            $suffixed++;
            // Revérifier avec suffixe
            $check2 = $pdo->prepare(
                "SELECT id FROM formations WHERE slug = ? AND id != ? LIMIT 1"
            );
            $check2->execute(array($newSlug, $c['id']));
            if ($check2->fetch()) {
                $skipped[] = array('id' => $c['id'], 'titre' => mb_substr($c['titre'], 0, 40, 'UTF-8'));
                continue;
            }
        }

        $upd = $pdo->prepare("UPDATE formations SET slug = ? WHERE id = ?");
        $ok  = $upd->execute(array($newSlug, $c['id']));
        if ($ok && $upd->rowCount() > 0) {
            $updated++;
        } else {
            $errors[] = array(
                'id'    => $c['id'],
                'slug'  => $newSlug,
                'err'   => $upd->errorInfo(),
            );
        }
    }

    echo json_encode(array(
        'total_corrupt' => count($corrupt),
        'updated'       => $updated,
        'suffixed'      => $suffixed,
        'skipped'       => count($skipped),
        'errors'        => count($errors),
        'error_sample'  => array_slice($errors, 0, 3),
        'skip_sample'   => array_slice($skipped, 0, 3),
    ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode(array('error' => 'action inconnu'));
