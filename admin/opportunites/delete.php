<?php
declare(strict_types=1);

require_once __DIR__ . '/../_init.php';

$mw = realpath(__DIR__ . '/../auth/middleware.php');
if (!$mw) { die('Middleware introuvable'); }
require_once $mw;
Middleware::requireAuth();
csrf_verify();

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
  header('Location: index.php');
  exit;
}

$pdo = Database::connect();
$stmt = $pdo->prepare("DELETE FROM opportunites_emploi WHERE id = ? LIMIT 1");
$stmt->execute([$id]);

header('Location: index.php');
exit;
