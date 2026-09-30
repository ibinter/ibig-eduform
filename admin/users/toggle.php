<?php
declare(strict_types=1);

/**
 * ============================================================
 * IBIG EDUFORM — USERS / TOGGLE STATUS (SECURED)
 * ------------------------------------------------------------
 * ✔ RBAC (manage_users)
 * ✔ Interdit super_admin
 * ✔ Interdit auto-désactivation
 * ✔ Audit log
 * ✔ Redirection propre
 * ============================================================
 */

require_once __DIR__ . '/../auth/guard.php';
require_permission('manage_users');
csrf_verify();

require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../auth/audit.php';

$pdo = Database::connect();

/* ===============================
   ID UTILISATEUR
================================ */
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    exit('ID utilisateur invalide.');
}

/* ===============================
   UTILISATEUR CIBLE
================================ */
$stmt = $pdo->prepare("
    SELECT id, email, role, status
    FROM users
    WHERE id = ?
");
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    http_response_code(404);
    exit('Utilisateur introuvable.');
}

/* ===============================
   RÈGLES DE SÉCURITÉ
================================ */

/* 1️⃣ Interdit de désactiver un super_admin */
if ($user['role'] === 'super_admin') {
    http_response_code(403);
    exit('Action interdite : super administrateur.');
}

/* 2️⃣ Interdit de se désactiver soi-même */
if ((int)$user['id'] === (int)($_SESSION['user']['id'] ?? 0)) {
    http_response_code(403);
    exit('Action interdite : vous ne pouvez pas modifier votre propre statut.');
}

/* ===============================
   TOGGLE STATUT
================================ */
$newStatus = ($user['status'] === 'active') ? 'inactive' : 'active';

$update = $pdo->prepare("
    UPDATE users
    SET status = ?
    WHERE id = ?
");
$update->execute([$newStatus, $id]);

/* ===============================
   AUDIT LOG
================================ */
audit_log(
    'toggle_status',
    'user',
    $id,
    'Changement statut utilisateur : ' . $user['email'] . ' → ' . $newStatus
);

/* ===============================
   REDIRECTION
================================ */
redirect('index.php');