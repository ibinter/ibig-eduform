<?php
require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../auth/middleware.php';

Middleware::requireAuth();
csrf_verify();

$id = (int)($_GET['id'] ?? 0);
$statut = $_GET['statut'] ?? '';

if (!in_array($statut, ['publie','brouillon'], true)) {
  exit('Statut invalide');
}

$pdo = Database::connect();
$stmt = $pdo->prepare("UPDATE avis_clients SET statut=? WHERE id=?");
$stmt->execute([$statut, $id]);

header('Location: index.php');
exit;
