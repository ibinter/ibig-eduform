<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/database.php';

$pdo = Database::connect();

// Par défaut: garder 180 jours (modifiable)
$days = 180;

// Purge
$stmt = $pdo->prepare("DELETE FROM site_visits WHERE visited_at < DATE_SUB(NOW(), INTERVAL ? DAY)");
$stmt->execute([$days]);

echo "OK purge: older than {$days} days\n";
