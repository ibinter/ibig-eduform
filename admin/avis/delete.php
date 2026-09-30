<?php
require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../auth/middleware.php';

Middleware::requireAuth();
csrf_verify();

$id = (int)($_GET['id'] ?? 0);
$pdo = Database::connect();

$stmt = $pdo->prepare("DELETE FROM avis_clients WHERE id=?");
$stmt->execute([$id]);

header('Location: index.php');
exit;
