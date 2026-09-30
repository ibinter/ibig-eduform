<?php
declare(strict_types=1);
require_once __DIR__ . '/../core/config.php';
$pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);

echo '<html><head><meta charset="utf-8"><title>Schema DB</title>
<style>body{font-family:monospace;background:#0d1f3c;color:#e2e8f0;padding:20px;font-size:12px}
h2{color:#f59e0b}pre{background:#1e3a6e;padding:10px;border-radius:4px;overflow-x:auto;white-space:pre-wrap}
table{border-collapse:collapse;margin-bottom:20px}td,th{padding:5px 10px;border:1px solid #334155;text-align:left}
th{background:#1e3a6e}</style></head><body>';

// Tables list
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
echo '<h2>Tables</h2><pre>' . implode("\n", $tables) . '</pre>';

// Key tables schema
foreach (['formations','formation_landings','categories','collections','formation_categories','formation_themes'] as $t) {
    if (in_array($t, $tables)) {
        echo "<h2>DESCRIBE $t</h2><table><tr>";
        $cols = $pdo->query("DESCRIBE `$t`")->fetchAll(PDO::FETCH_ASSOC);
        foreach (array_keys($cols[0]) as $k) echo "<th>$k</th>";
        echo '</tr>';
        foreach ($cols as $c) {
            echo '<tr>';
            foreach ($c as $v) echo '<td>' . htmlspecialchars((string)$v) . '</td>';
            echo '</tr>';
        }
        echo '</table>';

        // Sample row
        $sample = $pdo->query("SELECT * FROM `$t` LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if ($sample) {
            echo '<pre>' . htmlspecialchars(json_encode($sample, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)) . '</pre>';
        }
    }
}

// Check for any category-like tables
echo '<h2>Colonnes formation avec "categ" ou "theme" ou "collection"</h2>';
$cols2 = $pdo->query("DESCRIBE formations")->fetchAll(PDO::FETCH_ASSOC);
$filtered = array_filter($cols2, fn($c) => preg_match('/categ|theme|collect|domaine|rubrique/i', $c['Field']));
echo '<pre>' . htmlspecialchars(json_encode(array_values($filtered), JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)) . '</pre>';

echo '</body></html>';
