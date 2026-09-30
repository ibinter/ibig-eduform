<?php
declare(strict_types=1);

require_once __DIR__ . '/../_init.php';

$mw = realpath(__DIR__ . '/../auth/middleware.php');
if (!$mw) { die("Middleware introuvable"); }
require_once $mw;
Middleware::requireAuth();
csrf_verify();

$pdo = Database::connect();

$id     = (int)($_POST['id'] ?? 0);
$statut = trim((string)($_POST['statut'] ?? ''));

$allowed = ['nouvelle','en_cours','retenue','rejete'];

if ($id <= 0 || !in_array($statut, $allowed, true)) {
  redirect('index.php');
}

$stmt = $pdo->prepare("
  UPDATE candidatures_formateurs
  SET statut = ?
  WHERE id = ?
  LIMIT 1
");
$stmt->execute([$statut, $id]);

redirect('index.php');
