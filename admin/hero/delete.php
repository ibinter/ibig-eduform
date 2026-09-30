<?php
require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../auth/middleware.php';

Middleware::requireAuth();
csrf_verify();

$id = (int)($_GET['id'] ?? 0);
if ($id > 0) {
    Database::connect()->prepare("DELETE FROM hero_slides WHERE id = ?")->execute([$id]);
}
redirect('index.php');
