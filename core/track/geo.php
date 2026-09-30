<?php
declare(strict_types=1);

function geo_country_cached(PDO $pdo, ?string $ip, ?string $ipHash): array
{
    if (!$ip || !$ipHash) return [null, null];

    $c = $pdo->prepare("SELECT country_code, country_name FROM ip_geo_cache WHERE ip_hash = ?");
    $c->execute([$ipHash]);
    if ($row = $c->fetch(PDO::FETCH_ASSOC)) {
        return [$row['country_code'], $row['country_name']];
    }

    // Lookup léger (timeout court)
    $ctx = stream_context_create(['http'=>['timeout'=>2]]);
    $raw = @file_get_contents("https://ipapi.co/{$ip}/json/", false, $ctx);
    if ($raw) {
        $d = json_decode($raw, true);
        if (!empty($d['country_name'])) {
            $pdo->prepare("
              INSERT INTO ip_geo_cache (ip_hash, country_code, country_name, last_lookup)
              VALUES (?, ?, ?, NOW())
            ")->execute([$ipHash, strtoupper($d['country_code'] ?? ''), $d['country_name']]);
            return [strtoupper($d['country_code'] ?? ''), $d['country_name']];
        }
    }
    return [null, null];
}
