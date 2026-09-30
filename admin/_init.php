<?php
declare(strict_types=1);

/**
 * INIT ADMIN — à inclure au début de toutes les pages /admin/*
 * Charge bootstrap + middleware + enforce auth.
 */

require_once __DIR__ . '/../core/bootstrap.php';

/* =========================================================
   HELPERS GLOBAUX ADMIN
   ========================================================= */

/**
 * Échappement HTML sécurisé
 * - Compatible PHP 7 / 8+
 * - Accepte NULL (évite Fatal error htmlspecialchars)
 * - À utiliser PARTOUT dans l’admin
 */
if (!function_exists('e')) {
    function e($v): string {
        if ($v === null) {
            return '';
        }
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    }
}

/* =========================================================
   MIDDLEWARE AUTH
   ========================================================= */

// Charge Middleware (chemin absolu fiable)
$mw = __DIR__ . '/auth/middleware.php';
if (!file_exists($mw)) {
    http_response_code(500);
    die("Fichier middleware introuvable : " . $mw);
}
require_once $mw;

if (!class_exists('Middleware')) {
    http_response_code(500);
    die("Classe Middleware introuvable dans /admin/auth/middleware.php (nom de classe différent ?)");
}

// Protège la page
Middleware::requireAuth();