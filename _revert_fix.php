<?php
declare(strict_types=1);
require_once __DIR__ . '/core/secrets.php';
$pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
header('Content-Type: text/plain; charset=utf-8');

echo "REVERT — RESTAURATION FORMATIONS PROTÉGÉES\n\n";

// 1. Restaurer les formations archive-xxx (Samedi Pro historiques) → statut='active'
$r1 = $pdo->exec("UPDATE formations SET statut='active' WHERE slug LIKE 'archive-%'");
echo "Formations archive-xxx restaurées à 'active' : $r1\n";

// 2. Vérif : formations 2026 (annee=2026) — les tarifs ont pu être augmentés
// On ne peut pas restaurer les anciens prix (pas de backup), mais on signale le nombre
$n2026 = $pdo->query("SELECT COUNT(*) FROM formations WHERE annee = 2026")->fetchColumn();
echo "Formations annee=2026 présentes : $n2026 (tarifs potentiellement modifiés par le fix précédent)\n";

echo "\nSUPPRIMEZ CE FICHIER.\n";
