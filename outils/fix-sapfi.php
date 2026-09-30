<?php
declare(strict_types=1);
require_once __DIR__ . '/../core/config.php';

$pdo = new PDO(
    'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4',
    DB_USER, DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// Lister les formations SAP FI
$rows = $pdo->query("SELECT id, titre, tarif_en_ligne, tarif_presentiel, is_samedi_pro, description FROM formations WHERE titre LIKE '%SAP FI%' ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);

echo '<pre style="font-family:monospace;background:#0d1f3c;color:#f1f5f9;padding:20px">';
echo "=== Formations SAP FI ===\n\n";
foreach ($rows as $r) {
    echo "ID: {$r['id']}\n";
    echo "Titre: {$r['titre']}\n";
    echo "En ligne: {$r['tarif_en_ligne']} | Présentiel: {$r['tarif_presentiel']}\n";
    echo "Samedi Pro: {$r['is_samedi_pro']}\n";
    echo "Description: " . mb_substr($r['description'] ?? '', 0, 80) . "\n";
    echo "---\n";
}

// Suppression si id passé en GET
if (isset($_GET['delete_id'])) {
    $id = (int)$_GET['delete_id'];
    $pdo->prepare("DELETE FROM formations WHERE id = ? AND is_samedi_pro = 1")->execute([$id]);
    echo "\n✅ Formation ID $id supprimée.\n";
}
echo '</pre>';
