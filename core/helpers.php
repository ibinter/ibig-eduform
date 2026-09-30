<?php
declare(strict_types=1);

/* ============================
   HELPERS GLOBAUX – IBIG CORE
============================ */

function e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
}

function post(string $key, mixed $default = ''): mixed
{
    return $_POST[$key] ?? $default;
}

function clean(?string $v): string
{
    return preg_replace('/\s+/u', ' ', trim((string)$v));
}

function redirect(string $url): never
{
    if (!headers_sent()) {
        header('Location: '.$url);
    }
    exit;
}

/* ============================
   RÉGLAGES (table settings)
   setting('clé', défaut) — lecture en cache, tolérante.
============================ */
function setting(string $key, $default = '')
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        try {
            if (class_exists('Database')) {
                $pdo  = Database::connect();
                $rows = $pdo->query("SELECT setting_key, setting_value FROM settings")->fetchAll(PDO::FETCH_KEY_PAIR);
                if (is_array($rows)) { $cache = $rows; }
            }
        } catch (Throwable $e) { $cache = []; }
    }
    $v = $cache[$key] ?? null;
    return ($v === null || $v === '') ? $default : $v;
}

function setting_int(string $key, int $default = 0): int
{
    $v = setting($key, null);
    return ($v === null) ? $default : (int)$v;
}

/* Avis clients publiés (témoignages) — tolérant. */
function get_approved_avis(int $limit = 0): array
{
    try {
        if (!class_exists('Database')) return [];
        $pdo = Database::connect();
        $sql = "SELECT nom, ville, pays, secteur, texte FROM avis_clients WHERE statut='publie' ORDER BY created_at DESC";
        if ($limit > 0) { $sql .= ' LIMIT ' . (int)$limit; }
        $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        return is_array($rows) ? $rows : [];
    } catch (Throwable $e) { return []; }
}