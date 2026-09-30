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
$me  = auth_user();
if (($me['role'] ?? '') !== 'super_admin') { http_response_code(403); exit('Accès refusé.'); }

csrf_check();
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0 || $id === (int)($me['id'] ?? 0)) { header('Location: index.php'); exit; }

$pdo = Database::connect();
$row = $pdo->prepare("SELECT status FROM users WHERE id = ? AND role IN ('super_admin','admin','commercial','rh') LIMIT 1");
$row->execute([$id]);
$u = $row->fetch(PDO::FETCH_ASSOC);
if (!$u) { header('Location: index.php'); exit; }

$new = $u['status'] === 'active' ? 'inactive' : 'active';
$pdo->prepare("UPDATE users SET status = ? WHERE id = ?")->execute([$new, $id]);
header('Location: index.php');
exit;
