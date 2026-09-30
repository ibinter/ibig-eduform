<?php
declare(strict_types=1);

function detect_device(?string $ua): string
{
    if (!$ua) return 'desktop';
    if (preg_match('/tablet|ipad/i', $ua)) return 'tablet';
    if (preg_match('/mobile/i', $ua)) return 'mobile';
    return 'desktop';
}
