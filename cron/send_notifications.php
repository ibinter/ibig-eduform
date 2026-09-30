<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli' && ($_GET['key'] ?? '') !== 'ibig-rlz-2026-k7m3q9') {
    http_response_code(403);
    exit;
}

require_once __DIR__ . '/../core/notifications.php';
require_once __DIR__ . '/../core/mail.php';

$pdo = Database::connect();

$rows = $pdo->query("
  SELECT id, destinataire, sujet, message, essais
  FROM notification_queue
  WHERE statut='pending' AND essais < 3
  ORDER BY id ASC
  LIMIT 20
")->fetchAll(PDO::FETCH_ASSOC);

foreach ($rows as $n) {
  $ok = send_mail($n['destinataire'], $n['sujet'], $n['message']);

  if ($ok) {
    $pdo->prepare("
      UPDATE notification_queue
      SET statut='sent', sent_at=NOW()
      WHERE id=?
    ")->execute([$n['id']]);

    log_notification('email', $n['destinataire'], $n['sujet'], strip_tags($n['message']), 'success');
  } else {
    $pdo->prepare("
      UPDATE notification_queue
      SET statut='failed', essais=essais+1, last_error='Resend API error'
      WHERE id=?
    ")->execute([$n['id']]);

    log_notification('email', $n['destinataire'], $n['sujet'], strip_tags($n['message']), 'failed', 'Resend API error');
  }
}
