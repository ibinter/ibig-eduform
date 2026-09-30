<?php
declare(strict_types=1);

/**
 * ============================================================
 * IBIG EDUFORM — AUTH / LOGOUT ADMIN (VERSION FINALE)
 * ------------------------------------------------------------
 * ✔ Déconnexion sécurisée
 * ✔ Destruction complète de la session
 * ✔ Audit log (logout)
 * ✔ Redirection propre vers login
 * ============================================================
 */

require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/guard.php';
require_once __DIR__ . '/audit.php';

/* ============================================================
   SI UTILISATEUR CONNECTÉ → LOG
============================================================ */
if (!empty($_SESSION['user']['id'])) {
    audit_log(
        'logout',
        'auth',
        null,
        'Déconnexion administration'
    );
}

/* ============================================================
   DESTRUCTION SÉCURISÉE DE LA SESSION
============================================================ */
$_SESSION = [];

/* Suppression cookie PHPSESSID */
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

session_destroy();

/* ============================================================
   REDIRECTION
============================================================ */
redirect('/admin/auth/login.php');