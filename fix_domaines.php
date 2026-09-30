<?php
require_once __DIR__ . '/core/config.php';
require_once __DIR__ . '/core/database.php';
$pdo = Database::connect();

$keys = ['home_domaines' => '26'];
foreach ($keys as $k => $v) {
    $st = $pdo->prepare("INSERT INTO settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");
    $st->execute([$k, $v]);
}
echo "OK — home_domaines mis à jour à 26.";
// Auto-destruction
unlink(__FILE__);
