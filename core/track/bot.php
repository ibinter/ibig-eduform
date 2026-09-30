<?php
declare(strict_types=1);

function is_bot(?string $ua): bool
{
    if (!$ua) return false;
    return (bool) preg_match('/bot|crawl|spider|slurp|bingpreview|facebook|whatsapp|telegram/i', strtolower($ua));
}
