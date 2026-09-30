<?php
declare(strict_types=1);

/* ============================================================
   SUPPRESSION DÉFINITIVE D'UNE FORMATION (super_admin)
   - Supprime la formation + ses sessions + sa landing
   - Préserve les préinscriptions (formation_id -> NULL) : on ne
     perd pas les prospects, on les délie seulement.
   - Transaction : tout réussit ou rien n'est modifié.
   - Accepte l'id en POST (formulaire) ou GET (lien).
============================================================ */

require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../core/csrf.php';
require_once __DIR__ . '/../auth/middleware.php';
require_once __DIR__ . '/../../core/auth.php';

Middleware::requireAuth();
csrf_verify();

/* Suppression définitive = réservée au super_admin */
$u = auth_user();
if (($u['role'] ?? '') !== 'super_admin') {
    http_response_code(403);
    exit('Suppression définitive réservée au super administrateur.');
}

$id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
if ($id <= 0) {
    redirect('index.php');
}

$pdo = Database::connect();

try {
    $pdo->beginTransaction();

    /* 1) Préserver les préinscriptions : on délie (pas de perte de prospects) */
    try {
        $pdo->prepare("UPDATE preinscriptions SET formation_id = NULL WHERE formation_id = ?")->execute([$id]);
    } catch (Throwable $e) { /* table/colonne absente : on ignore */ }

    /* 2) Supprimer les données réellement propres à la formation */
    try {
        $pdo->prepare("DELETE FROM calendrier_formations WHERE formation_id = ?")->execute([$id]);
    } catch (Throwable $e) {}

    try {
        $pdo->prepare("DELETE FROM formation_landings WHERE formation_id = ?")->execute([$id]);
    } catch (Throwable $e) {}

    /* 3) Supprimer la formation elle-même */
    $stmt = $pdo->prepare("DELETE FROM formations WHERE id = ?");
    $stmt->execute([$id]);
    $deleted = $stmt->rowCount();

    $pdo->commit();
    redirect('index.php?deleted=' . (int)$deleted);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[FORMATION DELETE] ' . $e->getMessage());
    redirect('index.php?delerror=1');
}
