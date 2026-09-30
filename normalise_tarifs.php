<?php
/**
 * GRILLE TARIFAIRE OFFICIELLE IBIG EDUFORM
 * ─────────────────────────────────────────
 * Règle : tarif_en_ligne = tarif_presentiel × 62%  (réduction ~38%)
 * Exceptions : formations longues (mois, 55H+) = 70% du présentiel
 *
 * Niveaux :
 *  Samedi Pro 6H / 7H       : 75 000 /  45 000
 *  2 samedis 12H / 14H      : 100 000 /  65 000
 *  2 jours                  : 130 000 /  80 000
 *  3 jours  Standard        : 180 000 / 110 000
 *  4 jours  Intensif        : 220 000 / 140 000
 *  5 jours  Certifiant      : 280 000 / 170 000
 *  5 jours  Premium OHADA   : 300 000 / 180 000
 *  20H Cycle court          : 250 000 / 160 000
 *  25H Cycle moyen          : 300 000 / 200 000
 *  1 mois   Intensif        : 200 000 / 140 000
 *  2 mois   Certifiant long : 350 000 / 245 000
 *  3 mois   Expert          : 450 000 / 315 000
 *  4 mois   Master class    : 550 000 / 385 000
 *  55H  Spécialisé          : 450 000 / 315 000
 *  65H  Expert              : 520 000 / 365 000
 *  100H Avancé              : 600 000 / 420 000
 */

if (empty($_GET['run'])) {
    echo '<pre>SIMULATION (aucune écriture). Ajoutez ?run=1&apply=1 pour appliquer.</pre>';
}

require_once __DIR__ . '/core/config.php';
require_once __DIR__ . '/core/database.php';
$pdo = Database::connect();

// Grille officielle : duree => [presentiel, en_ligne]
$GRILLE = [
    '6H'               => [75000,   45000],
    '7H'               => [75000,   45000],
    '1 samedi (6h)'    => [75000,   45000],
    '2 samedis (12h)'  => [100000,  65000],
    '12H'              => [100000,  65000],
    '14H'              => [100000,  65000],
    '2 jours'          => [130000,  80000],
    '3 jours'          => [180000, 110000],
    '4 jours'          => [220000, 140000],
    '5 jours'          => [280000, 170000],
    '20H'              => [250000, 160000],
    '25H'              => [300000, 200000],
    '1 mois'           => [200000, 140000],
    '2 mois'           => [350000, 245000],
    '3 mois'           => [450000, 315000],
    '4 mois'           => [550000, 385000],
    '55H'              => [450000, 315000],
    '65H'              => [520000, 365000],
    '100H'             => [600000, 420000],
];

// Slugs "premium" à 300 000 / 180 000 (5 jours haute valeur)
$PREMIUM_5J = [
    'syscohada-revise-comptabilite-ohada',
    'audit-interne-normes-iia',
    'data-science-python-analyse-prediction',
    'gestion-projet-pmi-preparation-pmp',
    'prince2-gestion-projet-secteur-public-ong',
    'comptabilite-publique-gestion-budgetaire-etat-uemoa',
    'ifrs-pme-normes-comptables-internationales',
    'evaluation-entreprise-fusions-acquisitions-afrique',
    'modelisation-financiere-excel-lbo-valorisation-projections',
    'reglementation-bceao-conformite-bancaire',
    'petrole-gaz-afrique-contrats-petroliers-reglementation',
    'gestion-ressources-minieres-industrie-extractive',
];

$rows = $pdo->query("SELECT id, titre, slug, duree, tarif_presentiel, tarif_en_ligne FROM formations ORDER BY duree, tarif_presentiel")->fetchAll(PDO::FETCH_ASSOC);

$apply = !empty($_GET['apply']) && !empty($_GET['run']);
$updated = 0; $skipped = 0; $missing = [];

echo '<pre>';
echo "=== GRILLE TARIFAIRE OFFICIELLE IBIG EDUFORM ===\n";
echo str_pad('Durée',18).' | '.str_pad('Présentiel',10).' | '.str_pad('En ligne',10)." | Réduction\n";
echo str_repeat('-',65)."\n";
foreach ($GRILLE as $d => [$p,$e]) {
    printf("%-18s | %10s | %10s | -%d%%\n", $d, number_format($p,0,',',' '), number_format($e,0,',',' '), round((1-$e/$p)*100));
}
echo str_repeat('-',65)."\n\n";

echo ($apply ? "=== CORRECTIONS APPLIQUÉES ===\n" : "=== CORRECTIONS SIMULÉES ===\n");

foreach ($rows as $r) {
    $duree = trim($r['duree'] ?? '');
    if (!isset($GRILLE[$duree])) {
        if ($r['tarif_presentiel'] == 0) $missing[] = $r['titre'].' ('.$duree.')';
        continue;
    }
    [$target_p, $target_e] = $GRILLE[$duree];

    // Cas premium 5 jours
    if ($duree === '5 jours' && in_array($r['slug'], $PREMIUM_5J)) {
        $target_p = 300000;
        $target_e = 180000;
    }

    $diff_p = $r['tarif_presentiel'] != $target_p;
    $diff_e = $r['tarif_en_ligne']   != $target_e;

    if ($diff_p || $diff_e) {
        printf("  %-50s | %s | %6d→%6d / %6d→%6d\n",
            substr($r['titre'],0,50), str_pad($duree,16),
            $r['tarif_presentiel'], $target_p,
            $r['tarif_en_ligne'],   $target_e);

        if ($apply) {
            $pdo->prepare("UPDATE formations SET tarif_presentiel=?, tarif_en_ligne=?, updated_at=NOW() WHERE id=?")
                ->execute([$target_p, $target_e, $r['id']]);
        }
        $updated++;
    } else {
        $skipped++;
    }
}

echo "\n✅ Conformes (aucune modification)  : $skipped\n";
echo ($apply ? "✅ Mis à jour" : "⚠️  À corriger") . "                          : $updated\n";
if ($missing) {
    echo "\n⚠️  Durées non couvertes par la grille (".count($missing)."):\n";
    foreach ($missing as $m) echo "  - $m\n";
}

if (!$apply && $updated > 0) {
    echo "\n👉 Ajoutez ?run=1&apply=1 pour appliquer les $updated corrections.\n";
}
echo '</pre>';
