<?php
require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../auth/middleware.php';

Middleware::requireAuth();
csrf_verify();
$pdo = Database::connect();

$id = (int)($_POST['id'] ?? 0);
$statut = $_POST['statut'] ?? '';

if ($id>0 && in_array($statut,['nouvelle','traitee','rejete'],true)) {
  $stmt = $pdo->prepare("UPDATE preinscriptions SET statut=? WHERE id=?");
  $stmt->execute([$statut,$id]);
}

header('Location: index.php');
exit;
