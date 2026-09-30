<?php
declare(strict_types=1);

function get_session_id(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    if (empty($_SESSION['visit_session'])) {
        $_SESSION['visit_session'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['visit_session'];
}
