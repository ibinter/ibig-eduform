<?php
/**
 * IBIG EDUFORM — Script de correction du catalogue
 * Actions : supprimer N-en-1, supprimer doublons, corriger tarifs
 * Usage : ?mode=dry  (aperçu, défaut) | ?mode=exec (exécution réelle)
 */
require_once __DIR__ . '/../core/secrets.php';
header('Content-Type: application/json; charset=utf-8');

$mode = $_GET['mode'] ?? 'dry';
$exec = ($mode === 'exec');

try {
    $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
} catch (Exception $e) { echo json_encode(['error'=>$e->getMessage()]); exit; }

$log = [];

// ─────────────────────────────────────────────────────────────
// 1. SUPPRIMER LES FORMATIONS "N EN 1" (groupées / bundles)
// ─────────────────────────────────────────────────────────────
$sql_nens1 = "SELECT id, titre, domaine, tarif_en_ligne, tarif_presentiel, duree
              FROM formations
              WHERE is_samedi_pro = 0
              AND (
                  titre REGEXP '[0-9][[:space:]]*(en|dans)[[:space:]]*(1|un)[^0-9]'
                  OR titre LIKE '%Certificat % en 1%'
                  OR titre REGEXP 'CERTIFICAT[[:space:]]+[0-9]+[[:space:]]+EN[[:space:]]+1'
                  OR titre LIKE '%4 en 1%' OR titre LIKE '%3 en 1%' OR titre LIKE '%2 en 1%'
                  OR titre LIKE '%4 EN 1%' OR titre LIKE '%3 EN 1%' OR titre LIKE '%2 EN 1%'
              )
              ORDER BY id";
$groupees = $pdo->query($sql_nens1)->fetchAll(PDO::FETCH_ASSOC);
$ids_groupees = array_column($groupees, 'id');

$log['step1_groupees'] = [
    'count' => count($groupees),
    'formations' => $groupees,
    'executed' => false,
];

if ($exec && count($ids_groupees) > 0) {
    $placeholders = implode(',', array_fill(0, count($ids_groupees), '?'));
    $stmt = $pdo->prepare("DELETE FROM formations WHERE id IN ($placeholders)");
    $stmt->execute($ids_groupees);
    $log['step1_groupees']['executed'] = true;
    $log['step1_groupees']['deleted'] = $stmt->rowCount();
}

// ─────────────────────────────────────────────────────────────
// 2. SUPPRIMER LES DOUBLONS EXACTS (même titre normalisé, garder le + petit id)
// ─────────────────────────────────────────────────────────────
// On trouve les titres en double (après suppression des N-en-1)
$sql_dups = "SELECT titre, MIN(id) as id_garder, GROUP_CONCAT(id ORDER BY id ASC) as tous_ids, COUNT(*) as nb
             FROM formations
             WHERE is_samedi_pro = 0
             GROUP BY LOWER(TRIM(titre))
             HAVING COUNT(*) > 1
             ORDER BY nb DESC";
$dup_groups = $pdo->query($sql_dups)->fetchAll(PDO::FETCH_ASSOC);

$ids_a_supprimer = [];
foreach ($dup_groups as $grp) {
    $tous = array_map('intval', explode(',', $grp['tous_ids']));
    $garder = (int)$grp['id_garder'];
    foreach ($tous as $id) {
        if ($id !== $garder) $ids_a_supprimer[] = $id;
    }
}

$log['step2_doublons'] = [
    'groups' => $dup_groups,
    'ids_a_supprimer' => $ids_a_supprimer,
    'count' => count($ids_a_supprimer),
    'executed' => false,
];

if ($exec && count($ids_a_supprimer) > 0) {
    $placeholders = implode(',', array_fill(0, count($ids_a_supprimer), '?'));
    $stmt = $pdo->prepare("DELETE FROM formations WHERE id IN ($placeholders)");
    $stmt->execute($ids_a_supprimer);
    $log['step2_doublons']['executed'] = true;
    $log['step2_doublons']['deleted'] = $stmt->rowCount();
}

// ─────────────────────────────────────────────────────────────
// 3. CORRIGER LES TARIFS NON-CONFORMES
// ─────────────────────────────────────────────────────────────
// Règle absolue : OL >= 200 000 et PR >= 250 000
// Pour les formations très courtes (< 8h) avec prix excessifs : réduire
// Pour les formations 20-40h : OL max acceptable = 400K, PR max = 450K

$sql_tarifs = "SELECT id, titre, duree, tarif_en_ligne, tarif_presentiel
               FROM formations
               WHERE is_samedi_pro = 0
               AND (
                   tarif_en_ligne > 0 AND tarif_en_ligne < 200000
                   OR tarif_presentiel > 0 AND tarif_presentiel < 250000
                   OR (duree <= 8 AND (tarif_en_ligne > 350000 OR tarif_presentiel > 420000))
                   OR (duree > 8 AND duree <= 40 AND (tarif_en_ligne > 400000 OR tarif_presentiel > 480000))
               )";
$tarifs_ko = $pdo->query($sql_tarifs)->fetchAll(PDO::FETCH_ASSOC);

$corrections = [];
foreach ($tarifs_ko as $f) {
    $ol_new = $f['tarif_en_ligne'];
    $pr_new = $f['tarif_presentiel'];
    $h = (int)$f['duree'];

    // Sous le minimum : remonter
    if ($ol_new > 0 && $ol_new < 200000) $ol_new = 200000;
    if ($pr_new > 0 && $pr_new < 250000) $pr_new = 250000;

    // Trop cher pour durée courte (≤ 8h)
    if ($h > 0 && $h <= 8) {
        if ($ol_new > 350000) $ol_new = 300000;
        if ($pr_new > 420000) $pr_new = 350000;
    }
    // Formations 9-40h : plafond raisonnable
    if ($h > 8 && $h <= 40) {
        if ($ol_new > 400000) $ol_new = 350000;
        if ($pr_new > 480000) $pr_new = 450000;
    }

    if ($ol_new != $f['tarif_en_ligne'] || $pr_new != $f['tarif_presentiel']) {
        $corrections[] = [
            'id' => $f['id'],
            'titre' => $f['titre'],
            'h' => $h,
            'ol_avant' => $f['tarif_en_ligne'],
            'pr_avant' => $f['tarif_presentiel'],
            'ol_apres' => $ol_new,
            'pr_apres' => $pr_new,
        ];
    }
}

$log['step3_tarifs'] = [
    'count' => count($corrections),
    'corrections' => $corrections,
    'executed' => false,
];

if ($exec && count($corrections) > 0) {
    $stmt = $pdo->prepare("UPDATE formations SET tarif_en_ligne=?, tarif_presentiel=? WHERE id=?");
    $done = 0;
    foreach ($corrections as $c) {
        $stmt->execute([$c['ol_apres'], $c['pr_apres'], $c['id']]);
        $done++;
    }
    $log['step3_tarifs']['executed'] = true;
    $log['step3_tarifs']['updated'] = $done;
}

// ─────────────────────────────────────────────────────────────
echo json_encode([
    'mode' => $mode,
    'timestamp' => date('Y-m-d H:i:s'),
    'log' => $log,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
