<?php
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../auth/middleware.php';

Middleware::requireAuth();
csrf_verify();

$id = (int)($_GET['id'] ?? 0);
$pdo = Database::connect();

$pdo->prepare("DELETE FROM calendrier_formations WHERE id=?")->execute([$id]);

header('Location: index.php');
exit;
