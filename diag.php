<?php
// Diagnostic temporaire — à supprimer après
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    echo "<b>ERREUR PHP [$errno]:</b> $errstr<br>Fichier: $errfile ligne $errline<br><br>";
    return true;
});
set_exception_handler(function($e) {
    echo "<b>EXCEPTION:</b> " . $e->getMessage() . "<br>Fichier: " . $e->getFile() . " ligne " . $e->getLine();
});

echo "<pre style='font-family:monospace;padding:20px;background:#0a1733;color:#e8edf8'>";
echo "PHP version: " . PHP_VERSION . "\n";
echo "secrets.php existe: " . (file_exists(__DIR__ . '/core/secrets.php') ? 'OUI' : 'NON') . "\n";
echo "config.php existe: " . (file_exists(__DIR__ . '/core/config.php') ? 'OUI' : 'NON') . "\n";
echo "database.php existe: " . (file_exists(__DIR__ . '/core/database.php') ? 'OUI' : 'NON') . "\n";
echo "SQL migration existe: " . (file_exists(__DIR__ . '/migrations/2026_niveaux_formations.sql') ? 'OUI' : 'NON') . "\n";
echo "\n--- Test inclusion config.php ---\n";
try {
    require_once __DIR__ . '/core/config.php';
    echo "config.php OK\n";
    echo "TDR_SECRET défini: " . (defined('TDR_SECRET') ? 'OUI = ' . TDR_SECRET : 'NON') . "\n";
} catch (Throwable $e) {
    echo "ERREUR config.php: " . $e->getMessage() . "\n";
}
echo "\n--- Test connexion DB ---\n";
try {
    require_once __DIR__ . '/core/database.php';
    $pdo = Database::connect();
    echo "Connexion DB OK\n";
} catch (Throwable $e) {
    echo "ERREUR DB: " . $e->getMessage() . "\n";
}
echo "</pre>";
