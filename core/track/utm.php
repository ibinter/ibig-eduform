<?php
declare(strict_types=1);

function get_utm_data(): array
{
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    $keys = ['utm_source','utm_medium','utm_campaign','utm_content'];
    $out = [];
    foreach ($keys as $k) {
        if (!empty($_GET[$k])) {
            $out[$k] = substr((string)$_GET[$k], 0, 120);
            $_SESSION[$k] = $out[$k];
        } elseif (!empty($_SESSION[$k])) {
            $out[$k] = $_SESSION[$k];
        }
    }
    return $out;
}
