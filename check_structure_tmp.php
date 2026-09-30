<?php
require_once __DIR__ . '/core/config.php';
require_once __DIR__ . '/core/database.php';

$pdo = Database::connect();

$cols = $pdo->query("DESCRIBE formations")->fetchAll(PDO::FETCH_ASSOC);
echo "=== STRUCTURE formations ===\n";
foreach ($cols as $c) {
    echo $c['Field'] . " | " . $c['Type'] . " | NULL:" . $c['Null'] . " | Default:" . $c['Default'] . "\n";
}

$ex = $pdo->query("SELECT * FROM formations LIMIT 1")->fetch(PDO::FETCH_ASSOC);
echo "\n=== EXEMPLE LIGNE ===\n";
print_r($ex);

$cnt = $pdo->query("SELECT COUNT(*) FROM formations")->fetchColumn();
echo "\n=== TOTAL: $cnt formations ===\n";

$doms = $pdo->query("SELECT DISTINCT domaine, COUNT(*) as n FROM formations GROUP BY domaine ORDER BY n DESC")->fetchAll(PDO::FETCH_ASSOC);
echo "\n=== DOMAINES ===\n";
foreach ($doms as $d) echo $d['domaine'] . " => " . $d['n'] . "\n";
