<?php
declare(strict_types=1);

require_once __DIR__ . '/../_init.php';

$mw = realpath(__DIR__ . '/../auth/middleware.php');
if (!$mw) { die("Middleware introuvable"); }
require_once $mw;
Middleware::requireAuth();
csrf_verify();

$pdo = Database::connect();

$candidatureId = (int)($_POST['candidature_id'] ?? 0);
$statut        = trim((string)($_POST['statut'] ?? ''));

if ($candidatureId <= 0) redirect('index.php');
if ($statut !== 'actif' && $statut !== 'inactif') redirect('index.php');

$stmt = $pdo->prepare("
  UPDATE formateurs
  SET statut = ?
  WHERE candidature_id = ?
  LIMIT 1
");
$stmt->execute([$statut, $candidatureId]);

redirect('index.php');
