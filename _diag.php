<?php
declare(strict_types=1);
require_once __DIR__ . '/core/secrets.php';
$pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
header('Content-Type: text/plain; charset=utf-8');

echo "=== DIAGNOSTIC URGENCE ===\n\n";

// Formations annee=2027
$r = $pdo->query("SELECT id, titre, slug, annee, mois, tarif_en_ligne, tarif_presentiel, statut FROM formations WHERE annee=2027 ORDER BY id DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);
echo "--- Formations annee=2027 : " . count($r) . " ---\n";
foreach ($r as $f) {
    echo "ID={$f['id']} | annee={$f['annee']} | mois={$f['mois']} | statut={$f['statut']} | tarif_ligne={$f['tarif_en_ligne']} | tarif_pres={$f['tarif_presentiel']} | {$f['titre']}\n";
}

echo "\n--- Formations Samedi Pro (archive-xxx) — tarifs actuels ---\n";
$r2 = $pdo->query("SELECT id, titre, slug, tarif_en_ligne, tarif_presentiel, statut, annee FROM formations WHERE slug LIKE 'archive-%' ORDER BY id ASC LIMIT 60")->fetchAll(PDO::FETCH_ASSOC);
echo "Total archive-xxx : " . count($r2) . "\n";
foreach ($r2 as $f) {
    echo "ID={$f['id']} | annee={$f['annee']} | statut={$f['statut']} | tarif_ligne={$f['tarif_en_ligne']} | tarif_pres={$f['tarif_presentiel']} | {$f['titre']}\n";
}

echo "\n--- Formations annee=2026 — échantillon (10 premières) ---\n";
$r3 = $pdo->query("SELECT id, titre, tarif_en_ligne, tarif_presentiel, statut, mois FROM formations WHERE annee=2026 ORDER BY id ASC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
foreach ($r3 as $f) {
    echo "ID={$f['id']} | mois={$f['mois']} | statut={$f['statut']} | tarif_ligne={$f['tarif_en_ligne']} | tarif_pres={$f['tarif_presentiel']} | {$f['titre']}\n";
}

echo "\n--- Comptage par annee ---\n";
$r4 = $pdo->query("SELECT annee, COUNT(*) as nb FROM formations GROUP BY annee ORDER BY annee")->fetchAll(PDO::FETCH_ASSOC);
foreach ($r4 as $row) {
    echo "annee={$row['annee']} → {$row['nb']} formations\n";
}
