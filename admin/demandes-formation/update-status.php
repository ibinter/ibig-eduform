<?php
declare(strict_types=1);

require_once __DIR__ . '/../_init.php';
require_once __DIR__ . '/../auth/middleware.php';

Middleware::requireAuth();

$pdo = Database::connect();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

/* ===============================
   SÉCURITÉ & VALIDATION
=============================== */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  redirect('index.php');
}

csrf_verify();

$id     = (int)($_POST['id'] ?? 0);
$statut = trim($_POST['statut'] ?? '');

if ($id <= 0) {
  redirect('index.php');
}

/* ===============================
   STATUTS AUTORISÉS (DB)
=============================== */
$statutsAutorises = [
  'nouvelle',
  'traitee',
  'devis_envoye',
  'cloturee'
];

if (!in_array($statut, $statutsAutorises, true)) {
  redirect('view.php?id=' . $id);
}

/* ===============================
   VÉRIFIER EXISTENCE
=============================== */
$stmt = $pdo->prepare("SELECT id FROM demandes_formation WHERE id = ? LIMIT 1");
$stmt->execute([$id]);

if (!$stmt->fetchColumn()) {
  redirect('index.php');
}

/* ===============================
   MISE À JOUR
=============================== */
$stmt = $pdo->prepare("
  UPDATE demandes_formation
  SET statut = :statut
  WHERE id = :id
");
$stmt->execute([
  ':statut' => $statut,
  ':id'     => $id
]);

/* ===============================
   REDIRECTION
=============================== */
redirect('view.php?id=' . $id);