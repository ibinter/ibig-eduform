<?php
declare(strict_types=1);

/* ==================================================
   CORE SECURITY — IBIG EDUFORM (VERSION STABLE)
================================================== */

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/* =========================
   HEADERS DE SÉCURITÉ
========================= */
if (!headers_sent()) {
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    if (($_SERVER['HTTPS'] ?? '') === 'on'
        || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

/* =========================
   PROTECTION CSRF (front + admin)
========================= */
require_once __DIR__ . '/csrf.php';

/* =========================
   ANALYSE URL
========================= */
$uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';

/* =========================
   FRONT PUBLIC → AUCUN BLOCAGE
========================= */
if (strpos($uriPath, '/admin') !== 0) {
    return;
}

/* =========================
   ADMIN — AUTHENTIFICATION
========================= */
if (!isset($_SESSION['user']) || !is_array($_SESSION['user'])) {
    $_SESSION['redirect_after_login'] = $uriPath;
    header('Location: /admin/auth/login.php');
    exit;
}

/* =========================
   GESTION DES RÔLES
========================= */

/**
 * Vérifie que l'utilisateur a au moins un des rôles requis
 *
 * @param string|array $roles
 */
function requireRole(string|array $roles): void
{
    if (!isset($_SESSION['user']) || !is_array($_SESSION['user'])) {
        header('Location: /admin/auth/login.php');
        exit;
    }

    $roles = (array)$roles;
    $userRole = $_SESSION['user']['role'] ?? null;

    if (!$userRole || !in_array($userRole, $roles, true)) {
        http_response_code(403);
        exit('⛔ Accès refusé — droits insuffisants');
    }
}
