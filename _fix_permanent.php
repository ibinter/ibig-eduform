<?php
declare(strict_types=1);
require_once __DIR__ . '/core/secrets.php';
$pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
header('Content-Type: text/plain; charset=utf-8');

echo "═══════════════════════════════════════════════════\n";
echo "   IBIG EDUFORM — CORRECTION FORMATIONS PERMANENTES\n";
echo "   (annee=0 ou NULL uniquement — jamais 2026 ni Samedi Pro)\n";
echo "═══════════════════════════════════════════════════\n\n";

/* ════════════════════════════════════════════════════════
   CLAUSE DE PROTECTION — formations JAMAIS modifiées
   ════════════════════════════════════════════════════════
   EXCLUS :
     - annee != 0 et annee IS NOT NULL  → calendriers (2026, etc.)
     - slug LIKE 'archive-%'            → Samedi Pro historiques
     - duree LIKE '%samedi%'            → Samedi Pro par durée
     - is_samedi_pro = 1                → flag Samedi Pro
   ════════════════════════════════════════════════════════ */

$WHERE_PERMANENT = "
    statut = 'active'
    AND (annee = 0 OR annee IS NULL)
    AND slug NOT LIKE 'archive-%'
    AND (duree NOT LIKE '%samedi%' OR duree IS NULL)
    AND (is_samedi_pro IS NULL OR is_samedi_pro = 0)
";

/* ════════════════════════════════════════════════════════
   VÉRIF AVANT : combien de formations permanentes ciblées
   ════════════════════════════════════════════════════════ */
$total_cibles = $pdo->query("SELECT COUNT(*) FROM formations WHERE $WHERE_PERMANENT")->fetchColumn();
echo "Formations permanentes ciblées : $total_cibles\n";

$sous_plancher = $pdo->query("
    SELECT COUNT(*) FROM formations
    WHERE $WHERE_PERMANENT
      AND (tarif_en_ligne < 200000 OR tarif_presentiel < 250000)
")->fetchColumn();
echo "Dont sous le plancher EDUFORM  : $sous_plancher\n\n";

/* ════════════════════════════════════════════════════════
   ACTION 1 — Corriger les tarifs selon grille horaire EDUFORM
   ════════════════════════════════════════════════════════ */
echo "ACTION 1 : Correction tarifs grille horaire EDUFORM\n";
echo str_repeat('-', 51) . "\n";

$pdo->exec("
UPDATE formations
SET
    tarif_en_ligne = CASE
        WHEN duree REGEXP '^(7|8|9|10|11|12|13|14|15)H\$'             THEN GREATEST(COALESCE(tarif_en_ligne,0), 200000)
        WHEN duree REGEXP '^(16|17|18|19|20|21|22|23|24|25)H\$'       THEN GREATEST(COALESCE(tarif_en_ligne,0), 250000)
        WHEN duree REGEXP '^(26|27|28|29|30|31|32|33|34|35)H\$'       THEN GREATEST(COALESCE(tarif_en_ligne,0), 350000)
        WHEN duree REGEXP '^(36|37|38|39|40|41|42|43|44|45)H\$'       THEN GREATEST(COALESCE(tarif_en_ligne,0), 350000)
        WHEN duree REGEXP '^(46|47|48|49|50|51|52|53|54|55)H\$'       THEN GREATEST(COALESCE(tarif_en_ligne,0), 400000)
        WHEN duree REGEXP '^(56|57|58|59|60|65|70|72|80|90|100)H\$'   THEN GREATEST(COALESCE(tarif_en_ligne,0), 450000)
        ELSE GREATEST(COALESCE(tarif_en_ligne,0), 200000)
    END,
    tarif_presentiel = CASE
        WHEN duree REGEXP '^(7|8|9|10|11|12|13|14|15)H\$'             THEN GREATEST(COALESCE(tarif_presentiel,0), 250000)
        WHEN duree REGEXP '^(16|17|18|19|20|21|22|23|24|25)H\$'       THEN GREATEST(COALESCE(tarif_presentiel,0), 300000)
        WHEN duree REGEXP '^(26|27|28|29|30|31|32|33|34|35)H\$'       THEN GREATEST(COALESCE(tarif_presentiel,0), 400000)
        WHEN duree REGEXP '^(36|37|38|39|40|41|42|43|44|45)H\$'       THEN GREATEST(COALESCE(tarif_presentiel,0), 400000)
        WHEN duree REGEXP '^(46|47|48|49|50|51|52|53|54|55)H\$'       THEN GREATEST(COALESCE(tarif_presentiel,0), 450000)
        WHEN duree REGEXP '^(56|57|58|59|60|65|70|72|80|90|100)H\$'   THEN GREATEST(COALESCE(tarif_presentiel,0), 500000)
        ELSE GREATEST(COALESCE(tarif_presentiel,0), 250000)
    END,
    tarif_hybride = CASE
        WHEN duree REGEXP '^(7|8|9|10|11|12|13|14|15)H\$'             THEN GREATEST(COALESCE(tarif_hybride,0), 225000)
        WHEN duree REGEXP '^(16|17|18|19|20|21|22|23|24|25)H\$'       THEN GREATEST(COALESCE(tarif_hybride,0), 275000)
        WHEN duree REGEXP '^(26|27|28|29|30|31|32|33|34|35)H\$'       THEN GREATEST(COALESCE(tarif_hybride,0), 375000)
        WHEN duree REGEXP '^(36|37|38|39|40|41|42|43|44|45)H\$'       THEN GREATEST(COALESCE(tarif_hybride,0), 375000)
        WHEN duree REGEXP '^(46|47|48|49|50|51|52|53|54|55)H\$'       THEN GREATEST(COALESCE(tarif_hybride,0), 425000)
        WHEN duree REGEXP '^(56|57|58|59|60|65|70|72|80|90|100)H\$'   THEN GREATEST(COALESCE(tarif_hybride,0), 475000)
        ELSE GREATEST(COALESCE(tarif_hybride,0), 225000)
    END
WHERE $WHERE_PERMANENT
");
$nb1 = (int)$pdo->query("SELECT ROW_COUNT()")->fetchColumn();
echo "✅ $nb1 formation(s) permanente(s) mises à jour\n\n";

/* ════════════════════════════════════════════════════════
   ACTION 2 — Doublons locaux permanents
   ════════════════════════════════════════════════════════ */
echo "ACTION 2 : Doublons locaux (formations permanentes uniquement)\n";
echo str_repeat('-', 51) . "\n";

$local_perm = $pdo->query("
    SELECT id, titre, tarif_en_ligne
    FROM formations
    WHERE $WHERE_PERMANENT
    ORDER BY tarif_en_ligne DESC, id ASC
")->fetchAll(PDO::FETCH_ASSOC);

$norm_fn = function(string $s): string {
    return mb_strtolower(trim(preg_replace('/[\s\-–—_\/\(\)&]+/', ' ',
        preg_replace('/[^\p{L}\p{N}\s\-]/u', '', $s))), 'UTF-8');
};

$seen = [];
$doublon_ids = [];
foreach ($local_perm as $row) {
    $n = $norm_fn($row['titre']);
    if (isset($seen[$n])) {
        $doublon_ids[] = (int)$row['id'];
    } else {
        $seen[$n] = (int)$row['id'];
    }
}

$nb2 = 0;
if (!empty($doublon_ids)) {
    $phs = implode(',', $doublon_ids);
    $pdo->exec("UPDATE formations SET statut='doublon_supprime' WHERE id IN ($phs)");
    $nb2 = count($doublon_ids);
    echo "✅ $nb2 doublon(s) permanent(s) désactivé(s) (statut → 'doublon_supprime')\n";
    echo "   IDs: " . implode(', ', $doublon_ids) . "\n\n";
} else {
    echo "✅ Aucun doublon permanent détecté.\n\n";
}

/* ════════════════════════════════════════════════════════
   BILAN FINAL
   ════════════════════════════════════════════════════════ */
echo "═══════════════════════════════════════════════════\n";
echo "   BILAN\n";
echo "═══════════════════════════════════════════════════\n";

$apres = $pdo->query("
    SELECT COUNT(*) FROM formations
    WHERE $WHERE_PERMANENT
      AND (tarif_en_ligne < 200000 OR tarif_presentiel < 250000)
")->fetchColumn();
echo "Permanentes encore sous plancher : $apres (doit être 0)\n";

$min = $pdo->query("
    SELECT MIN(tarif_en_ligne) AS ml, MIN(tarif_presentiel) AS mp
    FROM formations
    WHERE $WHERE_PERMANENT AND tarif_en_ligne > 0
")->fetch(PDO::FETCH_ASSOC);
echo "Tarif en ligne minimum (perm.)  : " . number_format((int)$min['ml'], 0, ',', ' ') . " F CFA\n";
echo "Tarif présentiel minimum (perm.): " . number_format((int)$min['mp'], 0, ',', ' ') . " F CFA\n";

// Vérif que les formations 2026 et Samedi Pro sont INTACTES
$check_2026 = $pdo->query("SELECT COUNT(*) FROM formations WHERE annee = 2026")->fetchColumn();
$check_sam  = $pdo->query("SELECT COUNT(*) FROM formations WHERE slug LIKE 'archive-%' AND statut='active'")->fetchColumn();
echo "\nFormations 2026 présentes (non touchées) : $check_2026\n";
echo "Formations archive-xxx actives (non touchées) : $check_sam\n";

echo "\n✅ CORRECTION TERMINÉE — SUPPRIMEZ CE FICHIER.\n";
