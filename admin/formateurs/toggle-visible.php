<?php
declare(strict_types=1);

require_once __DIR__ . '/../_init.php';

$mw = realpath(__DIR__ . '/../auth/middleware.php');
if (!$mw) { die("Middleware introuvable"); }
require_once $mw;
Middleware::requireAuth();
csrf_verify();

$pdo = Database::connect();

$id      = (int)($_POST['id'] ?? 0);
$visible = (int)($_POST['visible'] ?? 0);
$visible = ($visible === 1) ? 1 : 0;

if ($id <= 0) {
  redirect('index.php');
}

$stmt = $pdo->prepare("
  UPDATE candidatures_formateurs
  SET visible = ?
  WHERE id = ?
  LIMIT 1
");
$stmt->execute([$visible, $id]);

redirect('index.php');
