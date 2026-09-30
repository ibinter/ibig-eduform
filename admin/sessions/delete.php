<?php
declare(strict_types=1);

require_once __DIR__ . '/../../core/bootstrap.php';
require_once __DIR__ . '/../auth/middleware.php';
Middleware::requireAuth();
csrf_verify();

$pdo = Database::connect();

$id          = (int)($_GET['id'] ?? 0);
$formationId = (int)($_GET['formation_id'] ?? 0);
if ($id <= 0) redirect('/admin/formations/index.php');

$stmt = $pdo->prepare("
  UPDATE calendrier_formations
  SET statut = 'ferme'
  WHERE id = ?
");
$stmt->execute([$id]);

redirect("index.php?formation_id=".$formationId);
