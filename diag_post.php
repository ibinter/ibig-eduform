<?php
// Diagnostic: affiche les données POST reçues
echo '<pre>';
echo "POST:\n";
foreach ($_POST as $k => $v) {
    if ($k === 'csrf') continue;
    echo "  " . htmlspecialchars($k) . " = " . htmlspecialchars(substr((string)$v, 0, 80)) . "\n";
}
echo "\nGET:\n";
foreach ($_GET as $k => $v) {
    echo "  " . htmlspecialchars($k) . " = " . htmlspecialchars(substr((string)$v, 0, 80)) . "\n";
}
echo '</pre>';
