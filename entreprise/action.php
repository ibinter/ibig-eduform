<?php
declare(strict_types=1);

require_once __DIR__ . '/../_init.php';

$mw = realpath(__DIR__ . '/../auth/middleware.php');
if (!$mw) die("Middleware introuvable");
require_once $mw;
Middleware::requireAuth();

$pdo = Database::connect();

$id = (int)($_GET['id'] ?? 0);
$do = (string)($_GET['do'] ?? '');

$allowed = ['actif','suspendu','refuse'];
if ($id <= 0 || !in_array($do, $allowed, true)) {
  header('Location: index.php'); exit;
}

$up = $pdo->prepare("
  UPDATE entreprises
  SET statut = ?
  WHERE id = ?
  LIMIT 1
");
$up->execute([$do, $id]);

header('Location: index.php');
exit;
