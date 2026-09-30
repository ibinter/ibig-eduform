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
    Database::connect()
        ->prepare("UPDATE site_pages SET is_published = 1 - is_published WHERE id = ?")
        ->execute([$id]);
}
redirect('index.php');
