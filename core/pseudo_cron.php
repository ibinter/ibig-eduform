<?php
declare(strict_types=1);

/**
 * PSEUDO-CRON — déclenché à chaque visite, exécuté au plus 1x/heure.
 * Usage : require_once dans le footer (après le rendu HTML).
 * Utilise la table settings (clé cron_last_run) comme verrou.
 */
if (!function_exists('pseudo_cron_run')) {
    function pseudo_cron_run(PDO $pdo): void
    {
        // Vérifie la dernière exécution
        try {
            $stmt = $pdo->prepare("SELECT val FROM settings WHERE `key` = 'cron_last_run' LIMIT 1");
            $stmt->execute();
            $last = $stmt->fetchColumn();
        } catch (Throwable $e) {
            return; // table settings absente : on abandonne
        }

        // Moins d'1h depuis la dernière exécution : on ne fait rien
        if ($last && (time() - (int)$last) < 3600) {
            return;
        }

        // Pose le verrou immédiatement (évite les exécutions concurrentes)
        try {
            $pdo->prepare("INSERT INTO settings (`key`, val) VALUES ('cron_last_run', ?)
                           ON DUPLICATE KEY UPDATE val = VALUES(val)")
                ->execute([(string)time()]);
        } catch (Throwable $e) {
            return;
        }

        // Lance la relance en arrière-plan (non-bloquant)
        $base = defined('APP_URL') ? rtrim(APP_URL, '/') : 'https://ibig-eduform.com';

        foreach ([
            '/cron/relance_preinscriptions.php?key=ibig-rlz-2026-k7m3q9',
            '/cron/send_notifications.php?key=ibig-rlz-2026-k7m3q9',
            '/api/sync-to-partners.php?key=' . substr((defined('PARTNERS_REGISTER_SECRET') ? PARTNERS_REGISTER_SECRET : ''), 0, 16),
        ] as $path) {
            $ch = curl_init($base . $path);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 1,
                CURLOPT_CONNECTTIMEOUT => 1,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_SSL_VERIFYPEER => false,
            ]);
            curl_exec($ch);
            curl_close($ch);
        }
    }
}
