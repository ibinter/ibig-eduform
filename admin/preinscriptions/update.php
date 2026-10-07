<?php
declare(strict_types=1);

/**
 * ============================================================
 * IBIG EDUFORM — UPDATE STATUT PRÉINSCRIPTION (SECURISÉ)
 * ============================================================
 */

require_once __DIR__ . '/../auth/guard.php';
require_once __DIR__ . '/../auth/middleware.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../core/auth.php';

Middleware::requireAuth();
csrf_verify();

$u = auth_user();

/* =========================
   RBAC STRICT
========================= */
if (!in_array($u['role'] ?? '', ['admin','super_admin','commercial'], true)) {
  http_response_code(403);
  exit('Accès refusé');
}

/* =========================
   INPUT
========================= */
$id = (int)($_POST['id'] ?? 0);
$statut = $_POST['statut'] ?? '';

$allowedStatuts = ['nouvelle','traitee','rejete'];

if ($id <= 0 || !in_array($statut, $allowedStatuts, true)) {
  http_response_code(400);
  exit('Requête invalide');
}

/* =========================
   DB
========================= */
$pdo = Database::connect();

/* Vérifier existence */
$check = $pdo->prepare("SELECT id FROM preinscriptions WHERE id = ? LIMIT 1");
$check->execute([$id]);
if (!$check->fetchColumn()) {
  http_response_code(404);
  exit('Préinscription introuvable');
}

/* Update sécurisé */
$stmt = $pdo->prepare("
  UPDATE preinscriptions
  SET statut = ?, updated_at = NOW()
  WHERE id = ?
");
$stmt->execute([$statut, $id]);

/* =========================
   REDIRECT
========================= */
$back = (string)($_POST['back'] ?? '');
if ($back !== '' && strpos($back, 'index.php') === 0) {
  redirect($back);
}
redirect('view.php?id=' . $id);