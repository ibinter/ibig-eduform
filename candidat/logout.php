<?php
declare(strict_types=1);

/*
 |--------------------------------------------------
 | LOGOUT CANDIDAT — IBIG EDUFORM
 |--------------------------------------------------
*/

/* Page publique */
define('PUBLIC_PAGE', true);

require_once __DIR__ . '/../core/config.php';

/*
 |--------------------------------------------------
 | DESTRUCTION SESSION CANDIDAT
 |--------------------------------------------------
*/

/* Supprimer uniquement les variables candidat */
unset(
  $_SESSION['candidat_id'],
  $_SESSION['candidat_nom'],
  $_SESSION['candidat_prenoms']
);

/* Optionnel : détruire toute la session */
session_regenerate_id(true);

/*
 |--------------------------------------------------
 | REDIRECTION
 |--------------------------------------------------
*/
header('Location: login.php');
exit;
