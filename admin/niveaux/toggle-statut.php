<?php
declare(strict_types=1);
/**
 * ADMIN — Basculer le statut d'un niveau (actif ↔ brouillon)
 */
require_once __DIR__ . '/../_init.php';
require_once __DIR__ . '/../auth/middleware.php';
Middleware::requireAuth();

$pdo    = Database::connect();
$id     = (int)($_GET['id'] ?? 0);
$action = $_GET['action'] ?? '';
$ret    = $_GET['ret'] ?? 'index.php';

if ($id && in_array($action, ['activer', 'brouillon', 'archiver'])) {
    $statut = match($action) {
        'activer'   => 'actif',
        'brouillon' => 'brouillon',
        'archiver'  => 'archive',
    };
    $pdo->prepare("UPDATE formation_niveaux SET statut = :s WHERE id = :id")
        ->execute([':s' => $statut, ':id' => $id]);
}

header('Location: ' . $ret);
exit;
