<?php
declare(strict_types=1);

/*
 |--------------------------------------------------
 | INIT ADMIN — PIPELINE INTERNE (ACTION)
 |--------------------------------------------------
*/
require_once __DIR__ . '/../_init.php';

/* 🔐 Middleware */
$mw = realpath(__DIR__ . '/../auth/middleware.php');
if (!$mw) {
  die("Middleware introuvable");
}
require_once $mw;

if (!class_exists('Middleware')) {
  die("Classe Middleware introuvable");
}

Middleware::requireAuth();

/*
 |--------------------------------------------------
 | DÉPENDANCES
 |--------------------------------------------------
*/
require_once __DIR__ . '/../../core/notifications.php';

$pdo = Database::connect();

/*
 |--------------------------------------------------
 | VALIDATION ID
 |--------------------------------------------------
*/
csrf_verify();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  header("Location: notifications.php");
  exit;
}

/*
 |--------------------------------------------------
 | RÉCUPÉRATION NOTIFICATION
 |--------------------------------------------------
*/
$stmt = $pdo->prepare("
  SELECT *
  FROM notification_queue
  WHERE id = ?
  LIMIT 1
");
$stmt->execute([$id]);
$n = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$n) {
  header("Location: notifications.php");
  exit;
}

/*
 |--------------------------------------------------
 | RELANCE MANUELLE
 |--------------------------------------------------
*/
$success = send_email(
  $n['destinataire'],
  $n['sujet'],
  $n['message']
);

if ($success) {

  $pdo->prepare("
    UPDATE notification_queue
    SET
      statut = 'sent',
      sent_at = NOW()
    WHERE id = ?
  ")->execute([$id]);

  log_notification(
    'email',
    $n['destinataire'],
    $n['sujet'],
    strip_tags($n['message']),
    'success'
  );

} else {

  $pdo->prepare("
    UPDATE notification_queue
    SET
      statut = 'failed',
      essais = essais + 1,
      last_error = 'manual retry failed'
    WHERE id = ?
  ")->execute([$id]);

  log_notification(
    'email',
    $n['destinataire'],
    $n['sujet'],
    strip_tags($n['message']),
    'failed',
    'manual retry failed'
  );
}

/*
 |--------------------------------------------------
 | REDIRECTION
 |--------------------------------------------------
*/
header("Location: notifications.php");
exit;
