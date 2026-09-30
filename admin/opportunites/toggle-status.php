<?php
declare(strict_types=1);

require_once __DIR__ . '/../_init.php';

$mw = realpath(__DIR__ . '/../auth/middleware.php');
if (!$mw) { die('Middleware introuvable'); }
require_once $mw;
Middleware::requireAuth();
csrf_verify();

$id = (int)($_POST['id'] ?? 0);
$statut = trim((string)($_POST['statut'] ?? ''));

$allowed = ['active','expiree','brouillon'];
if ($id <= 0 || !in_array($statut, $allowed, true)) {
  header('Location: index.php');
  exit;
}

$pdo = Database::connect();
$stmt = $pdo->prepare("UPDATE opportunites_emploi SET statut = ? WHERE id = ? LIMIT 1");
$stmt->execute([$statut, $id]);

header('Location: index.php');
exit;
