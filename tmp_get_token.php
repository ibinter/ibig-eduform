<?php
require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/core/apprenant_auth.php';
$pdo = Database::connect();
$token = apprenant_create_token($pdo, 'patriceky1er@gmail.com');
$row = $pdo->query("SELECT expires_at, (expires_at > NOW()) as valid FROM apprenant_tokens ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
echo $token . "\n";
echo "expires_at=" . $row['expires_at'] . " | valid=" . $row['valid'];
