<?php
declare(strict_types=1);
require_once __DIR__ . '/core/secrets.php';
$pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
header('Content-Type: text/plain; charset=utf-8');

echo "═══════════════════════════════════════════════════\n";
echo "   IBIG EDUFORM — CORRECTION CATALOGUE COMPLÈTE\n";
echo "═══════════════════════════════════════════════════\n\n";

/* ════════════════════════════════════════════════════════
   ACTION 1 — Corriger les tarifs locaux selon grille EDUFORM par durée
   ════════════════════════════════════════════════════════ */
echo "ACTION 1 : Correction tarifs selon grille horaire EDUFORM\n";
echo str_repeat('-', 51) . "\n";

$sql_fix_tarifs = "
UPDATE formations
SET
    tarif_en_ligne = CASE
        WHEN duree REGEXP '^(7|8|9|10|11|12|13|14|15)H$'             THEN GREATEST(tarif_en_ligne, 200000)
        WHEN duree REGEXP '^(16|17|18|19|20|21|22|23|24|25)H$'       THEN GREATEST(tarif_en_ligne, 250000)
        WHEN duree REGEXP '^(26|27|28|29|30|31|32|33|34|35)H$'       THEN GREATEST(tarif_en_ligne, 350000)
        WHEN duree REGEXP '^(36|37|38|39|40|41|42|43|44|45)H$'       THEN GREATEST(tarif_en_ligne, 350000)
        WHEN duree REGEXP '^(46|47|48|49|50|51|52|53|54|55)H$'       THEN GREATEST(tarif_en_ligne, 400000)
        WHEN duree REGEXP '^(56|57|58|59|60|65|70|72|80|90|100)H$'   THEN GREATEST(tarif_en_ligne, 450000)
        ELSE GREATEST(COALESCE(tarif_en_ligne,0), 200000)
    END,
    tarif_presentiel = CASE
        WHEN duree REGEXP '^(7|8|9|10|11|12|13|14|15)H$'             THEN GREATEST(tarif_presentiel, 250000)
        WHEN duree REGEXP '^(16|17|18|19|20|21|22|23|24|25)H$'       THEN GREATEST(tarif_presentiel, 300000)
        WHEN duree REGEXP '^(26|27|28|29|30|31|32|33|34|35)H$'       THEN GREATEST(tarif_presentiel, 400000)
        WHEN duree REGEXP '^(36|37|38|39|40|41|42|43|44|45)H$'       THEN GREATEST(tarif_presentiel, 400000)
        WHEN duree REGEXP '^(46|47|48|49|50|51|52|53|54|55)H$'       THEN GREATEST(tarif_presentiel, 450000)
        WHEN duree REGEXP '^(56|57|58|59|60|65|70|72|80|90|100)H$'   THEN GREATEST(tarif_presentiel, 500000)
        ELSE GREATEST(COALESCE(tarif_presentiel,0), 250000)
    END,
    tarif_hybride = CASE
        WHEN duree REGEXP '^(7|8|9|10|11|12|13|14|15)H$'             THEN GREATEST(tarif_hybride, 225000)
        WHEN duree REGEXP '^(16|17|18|19|20|21|22|23|24|25)H$'       THEN GREATEST(tarif_hybride, 275000)
        WHEN duree REGEXP '^(26|27|28|29|30|31|32|33|34|35)H$'       THEN GREATEST(tarif_hybride, 375000)
        WHEN duree REGEXP '^(36|37|38|39|40|41|42|43|44|45)H$'       THEN GREATEST(tarif_hybride, 375000)
        WHEN duree REGEXP '^(46|47|48|49|50|51|52|53|54|55)H$'       THEN GREATEST(tarif_hybride, 425000)
        WHEN duree REGEXP '^(56|57|58|59|60|65|70|72|80|90|100)H$'   THEN GREATEST(tarif_hybride, 475000)
        ELSE GREATEST(COALESCE(tarif_hybride,0), 225000)
    END
WHERE statut = 'active'
  AND (annee = 0 OR annee IS NULL OR annee = YEAR(CURDATE()))
  AND duree IS NOT NULL
  AND duree NOT LIKE '%samedi%'
  AND slug NOT LIKE 'archive-%'
";

$pdo->exec($sql_fix_tarifs);
$nb1 = (int)$pdo->query("SELECT ROW_COUNT()")->fetchColumn();

// Vérif : combien restent en dessous du plancher
$reste = $pdo->query("
    SELECT COUNT(*) FROM formations
    WHERE statut='active'
      AND (annee=0 OR annee IS NULL OR annee=YEAR(CURDATE()))
      AND (tarif_en_ligne < 200000 OR tarif_presentiel < 250000)
      AND slug NOT LIKE 'archive-%'
      AND duree NOT LIKE '%samedi%'
")->fetchColumn();

echo "✅ Tarifs corrigés selon grille horaire EDUFORM\n";
echo "   Formations encore sous plancher : $reste\n\n";

/* ════════════════════════════════════════════════════════
   ACTION 2 — Résoudre les doublons LOCAL vs LOCAL
   (formations locales avec noms quasi-identiques, garder le tarif le plus élevé)
   ════════════════════════════════════════════════════════ */
echo "ACTION 2 : Nettoyage doublons exacts API / LOCAL\n";
echo str_repeat('-', 51) . "\n";

// Les doublons exacts LOCAL+API : la déduplication dans catalogue-formations.php
// supprime déjà la version API quand local existe. Le vrai problème ce sont les
// doublons LOCAL+LOCAL (même nom, deux lignes en DB) et les entrées dupliquées dans l'API
// qui n'ont rien à voir avec nous.
// On cherche les doublons LOCAUX (même titre normalisé, plusieurs lignes)

$local_all = $pdo->query("
    SELECT id, titre, tarif_en_ligne, tarif_presentiel, statut, created_at
    FROM formations
    ORDER BY tarif_en_ligne DESC, id ASC
")->fetchAll(PDO::FETCH_ASSOC);

$norm_fn = function(string $s): string {
    return mb_strtolower(trim(preg_replace('/[\s\-–—_\/\(\)&]+/', ' ',
        preg_replace('/[^\p{L}\p{N}\s\-]/u', '', $s))), 'UTF-8');
};

$seen_local = [];
$doublons_local_ids = [];
foreach ($local_all as $row) {
    $n = $norm_fn($row['titre']);
    if (isset($seen_local[$n])) {
        // Garder celui avec le tarif le plus élevé (premier grâce au ORDER BY DESC)
        $doublons_local_ids[] = $row['id'];
    } else {
        $seen_local[$n] = $row['id'];
    }
}

$nb2 = 0;
if (!empty($doublons_local_ids)) {
    $phs = implode(',', array_fill(0, count($doublons_local_ids), '?'));
    $stmt = $pdo->prepare("UPDATE formations SET statut='doublon_supprime' WHERE id IN ($phs)");
    $stmt->execute($doublons_local_ids);
    $nb2 = count($doublons_local_ids);
    echo "✅ $nb2 doublon(s) local(aux) désactivé(s) (statut → 'doublon_supprime')\n";
    echo "   IDs: " . implode(', ', $doublons_local_ids) . "\n\n";
} else {
    echo "✅ Aucun doublon local-local détecté.\n\n";
}

/* ════════════════════════════════════════════════════════
   ACTION 3 — Désactiver les formations à 0 F / archives Samedi Pro
   ════════════════════════════════════════════════════════ */
echo "ACTION 3 : Traitement formations à prix 0 F / archives\n";
echo str_repeat('-', 51) . "\n";

// Formations archive-xxx (Samedi Pro historiques) → désactiver
$stmt3a = $pdo->prepare("
    UPDATE formations SET statut='archive'
    WHERE slug LIKE 'archive-%'
      AND statut = 'active'
");
$stmt3a->execute();
$nb3a = $stmt3a->rowCount();
echo "✅ $nb3a formation(s) 'archive-xxx' masquée(s) du catalogue (statut → 'archive')\n";

// Formations avec tarif_en_ligne = 0 ET duree contient 'samedi' → archive
$stmt3b = $pdo->prepare("
    UPDATE formations SET statut='archive'
    WHERE tarif_en_ligne = 0
      AND (duree LIKE '%samedi%' OR duree LIKE '%6h%' OR duree LIKE '%12h%')
      AND statut = 'active'
");
$stmt3b->execute();
$nb3b = $stmt3b->rowCount();
echo "✅ $nb3b formation(s) Samedi Pro (0 F) masquée(s) du catalogue\n";

// Formations avec tarif_en_ligne ≤ 50000 (Canva Pro, SAP 7H, etc.) → corriger le tarif
// car ce sont des formations courtes (7H, 14H) avec vrais tarifs à 0
// On les passe à 200000/250000 minimum si elles sont courtes mais actives
$stmt3c = $pdo->prepare("
    UPDATE formations
    SET tarif_en_ligne   = 200000,
        tarif_presentiel = GREATEST(COALESCE(tarif_presentiel,0), 250000),
        tarif_hybride    = 225000
    WHERE tarif_en_ligne < 200000
      AND tarif_en_ligne > 0
      AND statut = 'active'
      AND slug NOT LIKE 'archive-%'
");
$stmt3c->execute();
$nb3c = $stmt3c->rowCount();
echo "✅ $nb3c formation(s) avec tarif brut < 200 000 F corrigée(s) au plancher EDUFORM\n\n";

/* ════════════════════════════════════════════════════════
   BILAN FINAL
   ════════════════════════════════════════════════════════ */
echo "═══════════════════════════════════════════════════\n";
echo "   BILAN FINAL\n";
echo "═══════════════════════════════════════════════════\n";

$stats = $pdo->query("
    SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN statut='active' AND (annee=0 OR annee IS NULL OR annee=YEAR(CURDATE())) THEN 1 ELSE 0 END) AS actives,
        SUM(CASE WHEN statut='archive' THEN 1 ELSE 0 END) AS archives,
        SUM(CASE WHEN statut='doublon_supprime' THEN 1 ELSE 0 END) AS doublons,
        SUM(CASE WHEN statut='active' AND tarif_en_ligne < 200000 THEN 1 ELSE 0 END) AS sous_plancher
    FROM formations
")->fetch(PDO::FETCH_ASSOC);

echo "Total DB              : " . $stats['total'] . " formations\n";
echo "Actives dans catalogue: " . $stats['actives'] . " formations\n";
echo "Archivées (masquées)  : " . $stats['archives'] . " formations\n";
echo "Doublons supprimés    : " . $stats['doublons'] . " formations\n";
echo "Encore sous plancher  : " . $stats['sous_plancher'] . " formations\n";

$min_tarifs = $pdo->query("
    SELECT MIN(tarif_en_ligne) AS min_ligne, MIN(tarif_presentiel) AS min_pres
    FROM formations
    WHERE statut='active'
      AND (annee=0 OR annee IS NULL OR annee=YEAR(CURDATE()))
      AND tarif_en_ligne > 0
")->fetch(PDO::FETCH_ASSOC);

echo "\nTarif en ligne minimum : " . number_format((int)$min_tarifs['min_ligne'], 0, ',', ' ') . " F CFA\n";
echo "Tarif présentiel min   : " . number_format((int)$min_tarifs['min_pres'], 0, ',', ' ') . " F CFA\n";
echo "\n✅ CORRECTION TERMINÉE — SUPPRIMEZ CE FICHIER.\n";
