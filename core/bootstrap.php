<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| BOOTSTRAP GLOBAL — IBIG EDUFORM
|--------------------------------------------------------------------------
| Chargé AVANT toute sortie HTML
| Centralise TOUS les require_once
|--------------------------------------------------------------------------
*/

/* ===============================
   SESSION (OBLIGATOIRE POUR FR/EN)
=============================== */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ===============================
   CONFIG & CORE
=============================== */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/content.php';
require_once __DIR__ . '/whatsapp.php';
require_once __DIR__ . '/moneroo.php';
require_once __DIR__ . '/seo.php';
require_once __DIR__ . '/promo.php';

/* ===============================
   LANGUE (FR / EN)
   ⚠️ DOIT ÊTRE AVANT LE HEADER
=============================== */
require_once __DIR__ . '/lang.php';
