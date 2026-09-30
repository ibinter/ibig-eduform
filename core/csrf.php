<?php
declare(strict_types=1);

/* =========================================================
   CSRF — IBIG EDUFORM
   Inclus par core/security.php ET directement par les pages
   de formulaire qui ne passent pas par le guard admin.
========================================================= */

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!function_exists('csrf_token')) {
    /** Jeton CSRF de la session (généré au besoin). */
    function csrf_token(): string
    {
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf'];
    }

    /** Champ caché prêt à insérer dans un <form>. */
    function csrf_field(): string
    {
        return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
    }

    /** Vérifie le jeton sur une requête POST ; coupe la requête si invalide. */
    function csrf_check(): void
    {
        $sent = $_POST['csrf'] ?? '';
        if (!is_string($sent) || !hash_equals($_SESSION['csrf'] ?? '', $sent)) {
            http_response_code(419);
            exit('Session expirée ou requête non autorisée. Rechargez la page.');
        }
    }

    /**
     * Vérifie le jeton qu'il provienne d'un POST (champ csrf)
     * ou d'un lien GET (paramètre csrf). Pour les actions admin
     * delete/toggle/update déclenchées par un lien.
     */
    function csrf_verify(): void
    {
        $sent = $_POST['csrf'] ?? $_GET['csrf'] ?? '';
        if (!is_string($sent) || !hash_equals($_SESSION['csrf'] ?? '', $sent)) {
            http_response_code(419);
            exit('Lien expiré ou non autorisé. Revenez à la liste et réessayez.');
        }
    }

    /** Ajoute le jeton CSRF à une URL (gère le ? / & existant). */
    function csrf_url(string $url): string
    {
        $sep = (strpos($url, '?') === false) ? '?' : '&';
        return $url . $sep . 'csrf=' . urlencode(csrf_token());
    }
}
