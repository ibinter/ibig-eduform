<?php
declare(strict_types=1);

/**
 * ============================================================
 * IBIG EDUFORM — TRACK VISIT (POINT D’ENTRÉE GLOBAL)
 * ============================================================
 */

if (php_sapi_name() === 'cli') {
    return;
}

/* 1️⃣ Session GARANTIE */
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/* 2️⃣ Dépendances */
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/track/tracker.php';

/* 3️⃣ Tracker */
track_visit();