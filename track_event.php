<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/tracking_bootstrap.php';

header('Content-Type: application/json; charset=UTF-8');

try {

    // Sécurise la session (sans double start)
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $pdo = Database::connect();

    $raw = file_get_contents('php://input');
    $data = is_string($raw) ? json_decode($raw, true) : null;

    if (!is_array($data) || empty($data['event_type'])) {
        echo json_encode(['status' => 'ignored']);
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO site_events (
            session_key,
            event_type,
            event_label,
            page,
            formation_id,
            utm_source,
            utm_medium,
            utm_campaign,
            utm_content,
            ip_hash,
            device
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
        )
    ");

    $stmt->execute([
        $_SESSION['session_key'] ?? null,
        substr((string)$data['event_type'], 0, 50),
        isset($data['event_label']) ? substr((string)$data['event_label'], 0, 255) : null,

        $_SERVER['REQUEST_URI'] ?? null,
        isset($data['formation_id']) ? (int)$data['formation_id'] : null,

        $_SESSION['utm_source']   ?? null,
        $_SESSION['utm_medium']   ?? null,
        $_SESSION['utm_campaign'] ?? null,
        $_SESSION['utm_content']  ?? null,

        $_SESSION['ip_hash'] ?? null,
        $_SESSION['device']  ?? null
    ]);

    echo json_encode(['status' => 'ok']);

} catch (Throwable $e) {
    // tracking = jamais bloquer l’utilisateur
    http_response_code(204);
    exit;
}