<?php
declare(strict_types=1);

/* ============================================================
   DÉSACTIVATION EN MASSE DES FORMATIONS TERMINÉES
   - Critère : statut='active' ET date de fin (ou début) < aujourd'hui
   - Action réversible : statut passe à 'inactive'
   - POST + CSRF + rôle admin/super_admin
============================================================ */

require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../core/csrf.php';
require_once __DIR__ . '/../auth/middleware.php';
require_once __DIR__ . '/../../core/auth.php';

Middleware::requireAuth();

/* Méthode POST uniquement */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}

csrf_check();

/* Droits : admin ou super_admin */
$u = auth_user();
if (!in_array($u['role'] ?? '', ['admin', 'super_admin'], true)) {
    http_response_code(403);
    exit('Accès refusé.');
}

$pdo = Database::connect();

/* Mode : 'start' = date de début passée ; sinon 'end' = date de fin passée */
$mode = ($_POST['mode'] ?? 'end') === 'start' ? 'start' : 'end';

if ($mode === 'start') {
    // Sessions de calendrier (annee != 0) dont la date de DÉBUT est passée
    $sql = "
        UPDATE formations
        SET statut = 'inactive'
        WHERE statut = 'active'
          AND annee IS NOT NULL AND annee != 0
          AND date_debut IS NOT NULL
          AND date_debut < CURDATE()
    ";
} else {
    // Sessions de calendrier (annee != 0) réellement TERMINÉES
    $sql = "
        UPDATE formations
        SET statut = 'inactive'
        WHERE statut = 'active'
          AND annee IS NOT NULL AND annee != 0
          AND COALESCE(date_fin, date_debut) IS NOT NULL
          AND COALESCE(date_fin, date_debut) < CURDATE()
    ";
}

$stmt = $pdo->prepare($sql);
$stmt->execute();
$n = $stmt->rowCount();

redirect('index.php?deactivated=' . (int)$n);
