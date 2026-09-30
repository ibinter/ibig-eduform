<?php
declare(strict_types=1);
require_once __DIR__ . '/core/secrets.php';
$pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
header('Content-Type: text/plain; charset=utf-8');

echo "=== CORRECTION SAMEDI PRO — durées numériques (7H, 14H…) ===\n\n";

// Toutes les formations is_samedi_pro=1 du calendrier 2026 avec durée en heures brutes
$rows = $pdo->query("
    SELECT id, titre, slug, duree, tarif_en_ligne, tarif_presentiel
    FROM formations
    WHERE annee = 2026
      AND is_samedi_pro = 1
      AND duree REGEXP '^[0-9]+H\$'
    ORDER BY id ASC
")->fetchAll(PDO::FETCH_ASSOC);

echo "Formations concernées : " . count($rows) . "\n\n";

foreach ($rows as $r) {
    $h = (int)preg_replace('/H$/i', '', $r['duree']);

    // 1 samedi = 6H ou 7H → 95 000
    // 2 samedis = 12H ou 14H → 135 000
    // 3 samedis = 18H ou 21H → 190 000
    // 4 samedis = 24H ou 28H → 240 000
    if ($h <= 7) {
        $pres = 95000;
    } elseif ($h <= 14) {
        $pres = 135000;
    } elseif ($h <= 21) {
        $pres = 190000;
    } else {
        $pres = 240000;
    }

    $pdo->prepare("UPDATE formations SET tarif_en_ligne=0, tarif_presentiel=?, tarif_hybride=0 WHERE id=?")
        ->execute([$pres, (int)$r['id']]);

    echo "✅ ID={$r['id']} | duree={$r['duree']} → pres=$pres F";
    if ((int)$r['tarif_presentiel'] !== $pres) {
        echo " (était {$r['tarif_presentiel']})";
    }
    echo " | {$r['titre']}\n";
}

echo "\n✅ SUPPRIMEZ CE FICHIER.\n";
