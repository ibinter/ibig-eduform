<?php
declare(strict_types=1);
require_once __DIR__ . '/core/secrets.php';
$pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
header('Content-Type: text/plain; charset=utf-8');

echo "=== CORRECTION SAMEDI PRO + RESTAURATION PACKS PREMIUM ===\n\n";

// 1. Restaurer les Packs Premium (is_samedi_pro=0) à leurs vrais tarifs
$packs = [
    185 => ['ligne' => 315000, 'pres' => 450000],
    187 => ['ligne' => 315000, 'pres' => 450000],
    189 => ['ligne' => 365000, 'pres' => 520000],
    193 => ['ligne' => 365000, 'pres' => 520000],
    197 => ['ligne' => 315000, 'pres' => 450000],
    198 => ['ligne' => 365000, 'pres' => 520000],
];
echo "--- Restauration Packs Premium ---\n";
foreach ($packs as $id => $t) {
    $pdo->prepare("UPDATE formations SET tarif_en_ligne=?, tarif_presentiel=?, tarif_hybride=? WHERE id=?")
        ->execute([$t['ligne'], $t['pres'], (int)(($t['ligne']+$t['pres'])/2), $id]);
    echo "✅ ID=$id → ligne={$t['ligne']} | pres={$t['pres']}\n";
}

// 2. Corriger uniquement les vrais Samedi Pro (is_samedi_pro=1)
echo "\n--- Correction Samedi Pro (is_samedi_pro=1) ---\n";
$rows = $pdo->query("
    SELECT id, titre, duree FROM formations
    WHERE slug LIKE 'archive-%' AND annee = 2026 AND is_samedi_pro = 1
    ORDER BY id ASC
")->fetchAll(PDO::FETCH_ASSOC);

foreach ($rows as $r) {
    $duree = strtolower(trim((string)$r['duree']));
    if (preg_match('/^(6h|7h|1\s*samedi)/i', $duree)) {
        $pres = 95000;
    } elseif (preg_match('/^(12h|14h|2\s*samedi)/i', $duree)) {
        $pres = 135000;
    } elseif (preg_match('/^(18h|21h|3\s*samedi)/i', $duree)) {
        $pres = 190000;
    } elseif (preg_match('/^(24h|28h|4\s*samedi)/i', $duree)) {
        $pres = 240000;
    } else {
        $pres = 95000;
    }
    $pdo->prepare("UPDATE formations SET tarif_en_ligne=0, tarif_presentiel=?, tarif_hybride=0 WHERE id=?")
        ->execute([$pres, (int)$r['id']]);
    echo "✅ ID={$r['id']} | duree={$r['duree']} → pres=$pres F | {$r['titre']}\n";
}

// Vérif finale
echo "\n--- Vérification finale ---\n";
$all = $pdo->query("SELECT id, titre, duree, tarif_en_ligne, tarif_presentiel, is_samedi_pro FROM formations WHERE slug LIKE 'archive-%' AND annee=2026 ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
foreach ($all as $r) {
    echo "ID={$r['id']} | is_sp={$r['is_samedi_pro']} | duree={$r['duree']} | ligne={$r['tarif_en_ligne']} | pres={$r['tarif_presentiel']} | {$r['titre']}\n";
}
echo "\n✅ SUPPRIMEZ CE FICHIER.\n";
