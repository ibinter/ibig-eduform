<?php
declare(strict_types=1);

/**
 * ============================================================
 * IBIG EDUFORM — TRACKER EVENTS (FINAL / STABLE)
 * Table : site_events
 * ============================================================
 */

require_once __DIR__ . '/database.php';

try {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    // 1️⃣ Générer une session_key propre
    if (empty($_SESSION['session_key'])) {
        $_SESSION['session_key'] = bin2hex(random_bytes(16));
    }

    // 2️⃣ Lire les données JSON
    $raw = json_decode(file_get_contents('php://input'), true);
    if (!$raw || empty($raw['event_type'])) {
        exit;
    }

    // 3️⃣ Détection device simple
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $device = preg_match('/mobile|android|iphone/i', $ua)
        ? 'MOBILE'
        : 'DESKTOP';

    // 4️⃣ Données normalisées
    $eventType   = substr($raw['event_type'], 0, 50);
    $eventLabel  = isset($raw['event_label']) ? substr($raw['event_label'], 0, 255) : null;
    $page        = $_SERVER['REQUEST_URI'] ?? null;
    $formationId = isset($raw['formation_id']) ? (int)$raw['formation_id'] : null;

    $pdo = Database::connect();

    // 5️⃣ INSERT CORRECT
    $stmt = $pdo->prepare("
        INSERT INTO site_events
        (
          session_key,
          event_type,
          event_label,
          page_url,
          page,
          formation_id,
          is_bot,
          device,
          created_at
        )
        VALUES
        (?, ?, ?, ?, ?, ?, 0, ?, NOW())
    ");

    $stmt->execute([
        $_SESSION['session_key'],
        $eventType,
        $eventLabel,
        $page,
        $page,
        $formationId,
        $device
    ]);

} catch (Throwable $e) {
    // silence volontaire (prod)
}