<?php
require_once __DIR__ . '/core/config.php';
require_once __DIR__ . '/core/database.php';
$pdo = Database::connect();
try {
    $rows = $pdo->query("SELECT id, kicker, is_active, position FROM hero_slides ORDER BY position")->fetchAll(PDO::FETCH_ASSOC);
    echo count($rows) . " slide(s) en DB:\n";
    foreach($rows as $r) echo "  id={$r['id']} active={$r['is_active']} pos={$r['position']} — ".htmlspecialchars($r['kicker'])."\n";
} catch(Throwable $e){ echo "Table absente ou erreur: ".$e->getMessage(); }
unlink(__FILE__);
