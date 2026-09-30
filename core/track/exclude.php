<?php
declare(strict_types=1);

function is_excluded_ip(PDO $pdo, string $ip): bool
{
    $q = $pdo->prepare("SELECT 1 FROM excluded_ips WHERE ip_address = ? LIMIT 1");
    $q->execute([$ip]);
    return (bool) $q->fetchColumn();
}
