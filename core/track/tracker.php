<?php
declare(strict_types=1);

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/device.php';
require_once __DIR__ . '/bot.php';
require_once __DIR__ . '/formation.php';
require_once __DIR__ . '/utm.php';
require_once __DIR__ . '/geo.php';
require_once __DIR__ . '/exclude.php';
require_once dirname(__DIR__) . '/database.php';

function track_visit(): void
{
    try {
        $pdo = Database::connect();

        $page = $_SERVER['REQUEST_URI'] ?? '/';
        $ip   = $_SERVER['REMOTE_ADDR'] ?? null;
        $ua   = $_SERVER['HTTP_USER_AGENT'] ?? null;

        // Sécurité / exclusions
        if ($ip && is_excluded_ip($pdo, $ip)) return;

        // Session normalisée (â ï¸ session_key)
        $sessionKey = get_session_id(); // doit retourner session_key
        if (!$sessionKey) return;

        // Détections
        $device    = detect_device($ua);                 // DESKTOP | MOBILE | TABLET | BOT
        $isBot     = is_bot($ua) ? 1 : 0;
        $formation = detect_formation_id($pdo, $page);

        // Géolocalisation
        $ipHash = $ip ? hash('sha256', $ip) : null;
        [$cc, $cn] = geo_country_cached($pdo, $ip, $ipHash);

        // INSERT DANS site_events (LA TABLE DES STATS)
        $stmt = $pdo->prepare("
            INSERT INTO site_events
            (
              session_key,
              event_type,
              page_url,
              page,
              formation_id,
              is_bot,
              device,
              country,
              ip_hash,
              created_at
            )
            VALUES
            (?, 'pageview', ?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        $stmt->execute([
            $sessionKey,
            substr($page, 0, 255),
            substr($page, 0, 255),
            $formation,
            $isBot,
            $device,
            $cn,
            $ipHash
        ]);

    } catch (Throwable $e) {
        // silence volontaire (prod)
    }
}