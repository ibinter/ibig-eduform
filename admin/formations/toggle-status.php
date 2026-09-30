<?php
declare(strict_types=1);

require_once __DIR__ . '/../_init.php';
csrf_verify();

$pdo = Database::connect();

/* ===============================
   SÉCURITÉ ID
================================ */
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
  http_response_code(400);
  die('ID invalide.');
}

/* ===============================
   RÉCUPÉRATION STATUT ACTUEL
================================ */
$stmt = $pdo->prepare("
  SELECT statut
  FROM formations
  WHERE id = ?
  LIMIT 1
");
$stmt->execute([$id]);
$f = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$f) {
  http_response_code(404);
  die('Formation introuvable.');
}

/* ===============================
   TOGGLE STATUT
================================ */
$newStatus = ($f['statut'] === 'active') ? 'inactive' : 'active';

$stmt = $pdo->prepare("
  UPDATE formations
  SET statut = ?
  WHERE id = ?
");
$stmt->execute([$newStatus, $id]);

/* ===============================
   REDIRECTION
================================ */
header('Location: index.php');
exit;
