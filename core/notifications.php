<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/config.php';

/**
 * Envoi EMAIL simple via mail()
 * (Si ton serveur est bien configuré, c’est OK. Sinon on passe à PHPMailer SMTP.)
 */
function send_email(string $to, string $subject, string $html): bool
{
  $from = MAIL_FROM;
  $name = MAIL_FROM_NAME;

  $headers  = "MIME-Version: 1.0\r\n";
  $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
  $headers .= "From: " . $name . " <" . $from . ">\r\n";
  $headers .= "Reply-To: " . $from . "\r\n";

  return @mail($to, $subject, $html, $headers);
}

/** Log */
function log_notification(string $canal, string $dest, string $subject, string $message, string $statut='success', ?string $err=null): void
{
  try {
    $pdo = Database::connect();
    $stmt = $pdo->prepare("
      INSERT INTO notification_logs (canal, destinataire, sujet, message, statut, erreur)
      VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$canal, $dest, $subject, $message, $statut, $err]);
  } catch (\Throwable $e) {
    // on évite de casser le flux si le log échoue
  }
}

/** Ajouter à la queue */
function queue_email(string $to, string $subject, string $html): void
{
  $pdo = Database::connect();
  $stmt = $pdo->prepare("
    INSERT INTO notification_queue (destinataire, sujet, message)
    VALUES (?, ?, ?)
  ");
  $stmt->execute([$to, $subject, $html]);
}

/** Envoi direct + log */
function notify_email(string $to, string $subject, string $html): bool
{
  $ok = send_email($to, $subject, $html);
  log_notification('email', $to, $subject, strip_tags($html), $ok ? 'success' : 'failed', $ok ? null : 'mail() failed');
  return $ok;
}

/** WhatsApp (sans API) : lien prêt à cliquer */
function whatsapp_link(string $phoneE164NoPlus, string $text): string
{
  // wa.me exige un numéro sans +
  $msg = rawurlencode($text);
  return "https://wa.me/" . $phoneE164NoPlus . "?text=" . $msg;
}
