<?php
declare(strict_types=1);

/*
 |--------------------------------------------------
 | LOGOUT ENTREPRISE — IBIG EDUFORM
 |--------------------------------------------------
 | La session est déjà démarrée dans core/config.php
*/

require_once __DIR__ . '/../core/config.php';

/* ============================
   NETTOYAGE SESSION
============================ */

// Vider toutes les données de session
$_SESSION = [];

// Supprimer le cookie de session (si utilisé)
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

// Détruire la session
session_destroy();

/* ============================
   REDIRECTION
============================ */
header('Location: /entreprise/login.php?logout=1');
exit;
