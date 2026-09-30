<?php
declare(strict_types=1);

/**
 * ============================================================
 * IBIG EDUFORM — SUPPRESSION PRÉINSCRIPTION (SÉCURISÉE)
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
if (($u['role'] ?? '') !== 'super_admin') {
  http_response_code(403);
  exit('Accès refusé');
}

/* =========================
   MÉTHODE HTTP
========================= */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  exit('Méthode non autorisée');
}

/* =========================
   INPUT
========================= */
$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
  http_response_code(400);
  exit('ID invalide');
}

$pdo = Database::connect();

/* =========================
   EXISTENCE
========================= */
$check = $pdo->prepare("SELECT id FROM preinscriptions WHERE id = ? LIMIT 1");
$check->execute([$id]);
if (!$check->fetchColumn()) {
  http_response_code(404);
  exit('Préinscription introuvable');
}

/* =========================
   DELETE
========================= */
$stmt = $pdo->prepare("DELETE FROM preinscriptions WHERE id = ?");
$stmt->execute([$id]);

/* =========================
   REDIRECT
========================= */
redirect('index.php');