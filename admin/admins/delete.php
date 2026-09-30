<?php
declare(strict_types=1);
require_once __DIR__ . '/../auth/guard.php';
require_permission('manage_admins');
require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/csrf.php';
require_once __DIR__ . '/../auth/middleware.php';
require_once __DIR__ . '/../../core/auth.php';

Middleware::requireAuth();
$me = auth_user();
if (($me['role'] ?? '') !== 'super_admin') { http_response_code(403); exit('Accès refusé.'); }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php'); exit; }
csrf_check();

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0 || $id === (int)($me['id'] ?? 0)) { header('Location: index.php'); exit; }

$pdo = Database::connect();
$row = $pdo->prepare("SELECT role FROM users WHERE id = ? LIMIT 1");
$row->execute([$id]);
$u = $row->fetch(PDO::FETCH_ASSOC);

/* Interdit de supprimer un super_admin */
if (!$u || $u['role'] === 'super_admin') { header('Location: index.php'); exit; }

$pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
header('Location: index.php?deleted=1');
exit;
