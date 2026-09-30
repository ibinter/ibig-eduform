<?php
declare(strict_types=1);

/* ============================================
   TRACKING BOOTSTRAP — IBIG (GLOBAL / FINAL)
============================================ */

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/* ---------- Session Key persistante & propre ---------- */
if (empty($_SESSION['session_key'])) {
    $_SESSION['session_key'] = bin2hex(random_bytes(16)); // 32 chars propre
}

/* ---------- UTM persistantes ---------- */
$utmKeys = ['utm_source','utm_medium','utm_campaign','utm_content'];

foreach ($utmKeys as $k) {
    if (!empty($_GET[$k])) {
        $_SESSION[$k] = substr((string)$_GET[$k], 0, 120);
    }
}

/* ---------- Device NORMALISÉ (ENUM site_events) ---------- */
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';

if (preg_match('/bot|crawl|spider|slurp/i', $ua)) {
    $_SESSION['device'] = 'BOT';
} elseif (preg_match('/tablet|ipad/i', $ua)) {
    $_SESSION['device'] = 'TABLET';
} elseif (preg_match('/mobile|android|iphone/i', $ua)) {
    $_SESSION['device'] = 'MOBILE';
} else {
    $_SESSION['device'] = 'DESKTOP';
}