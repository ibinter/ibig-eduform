<?php
declare(strict_types=1);
require_once __DIR__ . '/core/secrets.php';
$pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
header('Content-Type: text/plain; charset=utf-8');

echo "=== GRILLE EDUFORM — FORMATIONS STANDARD CALENDRIER 2026 ===\n";
echo "(annee=2026, is_samedi_pro=0, hors archive-xxx, durée en heures)\n\n";

/* ── Grille EDUFORM par nombre d'heures ─────────────────────
   7–15H   : 200 000 / 250 000
   16–25H  : 250 000 / 300 000
   26–45H  : 350 000 / 400 000
   46–55H  : 400 000 / 450 000
   56–100H : 450 000 / 500 000
   ─────────────────────────────────────────────────────────── */

$pdo->exec("
UPDATE formations
SET
    tarif_en_ligne = CASE
        WHEN duree REGEXP '^(7|8|9|10|11|12|13|14|15)H\$'              THEN GREATEST(COALESCE(tarif_en_ligne,0), 200000)
        WHEN duree REGEXP '^(1[6-9]|2[0-5])H\$'                        THEN GREATEST(COALESCE(tarif_en_ligne,0), 250000)
        WHEN duree REGEXP '^(2[6-9]|3[0-9]|4[0-5])H\$'                THEN GREATEST(COALESCE(tarif_en_ligne,0), 350000)
        WHEN duree REGEXP '^(4[6-9]|5[0-5])H\$'                        THEN GREATEST(COALESCE(tarif_en_ligne,0), 400000)
        WHEN duree REGEXP '^(5[6-9]|6[0-5]|70|72|80|90|100)H\$'       THEN GREATEST(COALESCE(tarif_en_ligne,0), 450000)
        ELSE tarif_en_ligne
    END,
    tarif_presentiel = CASE
        WHEN duree REGEXP '^(7|8|9|10|11|12|13|14|15)H\$'              THEN GREATEST(COALESCE(tarif_presentiel,0), 250000)
        WHEN duree REGEXP '^(1[6-9]|2[0-5])H\$'                        THEN GREATEST(COALESCE(tarif_presentiel,0), 300000)
        WHEN duree REGEXP '^(2[6-9]|3[0-9]|4[0-5])H\$'                THEN GREATEST(COALESCE(tarif_presentiel,0), 400000)
        WHEN duree REGEXP '^(4[6-9]|5[0-5])H\$'                        THEN GREATEST(COALESCE(tarif_presentiel,0), 450000)
        WHEN duree REGEXP '^(5[6-9]|6[0-5]|70|72|80|90|100)H\$'       THEN GREATEST(COALESCE(tarif_presentiel,0), 500000)
        ELSE tarif_presentiel
    END
WHERE annee = 2026
  AND (is_samedi_pro IS NULL OR is_samedi_pro = 0)
  AND slug NOT LIKE 'archive-%'
  AND statut = 'active'
  AND duree REGEXP '^[0-9]+H\$'
");

$nb = (int)$pdo->query("SELECT ROW_COUNT()")->fetchColumn();
echo "✅ $nb formation(s) mises à jour selon grille EDUFORM\n\n";

// Vérification : sous plancher ?
$reste = $pdo->query("
    SELECT COUNT(*) FROM formations
    WHERE annee = 2026
      AND (is_samedi_pro IS NULL OR is_samedi_pro = 0)
      AND slug NOT LIKE 'archive-%'
      AND statut = 'active'
      AND duree REGEXP '^[0-9]+H\$'
      AND (tarif_en_ligne < 200000 OR tarif_presentiel < 250000)
")->fetchColumn();
echo "Encore sous plancher : $reste (doit être 0)\n\n";

// Échantillon des tarifs résultants par durée
echo "--- Échantillon par durée ---\n";
$sample = $pdo->query("
    SELECT duree, MIN(tarif_en_ligne) AS min_ligne, MIN(tarif_presentiel) AS min_pres, COUNT(*) AS nb
    FROM formations
    WHERE annee = 2026
      AND (is_samedi_pro IS NULL OR is_samedi_pro = 0)
      AND slug NOT LIKE 'archive-%'
      AND statut = 'active'
      AND duree REGEXP '^[0-9]+H\$'
    GROUP BY duree
    ORDER BY CAST(duree AS UNSIGNED) ASC
")->fetchAll(PDO::FETCH_ASSOC);
foreach ($sample as $r) {
    echo "duree={$r['duree']} ({$r['nb']} form.) | min ligne={$r['min_ligne']} | min pres={$r['min_pres']}\n";
}

echo "\n✅ SUPPRIMEZ CE FICHIER.\n";
