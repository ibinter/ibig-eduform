<?php
declare(strict_types=1);

require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../auth/middleware_rh.php';
require_once __DIR__ . '/../../core/emails.php';
require_once __DIR__ . '/../../core/csrf.php';

MiddlewareRH::requireRH();
csrf_verify();

$pdo = Database::connect();

/* =========================
   VALIDATION INPUT
========================= */
$id     = (int)($_POST['id'] ?? 0);
$statut = trim((string)($_POST['statut'] ?? ''));

$allowedStatuts = ['nouvelle', 'en_cours', 'retenue', 'rejete'];

if ($id <= 0 || !in_array($statut, $allowedStatuts, true)) {
  header('Location: index.php');
  exit;
}

/* =========================
   RÉCUPÉRATION CANDIDATURE
========================= */
$stmt = $pdo->prepare("
  SELECT nom, email
  FROM candidatures_formateurs
  WHERE id = ?
  LIMIT 1
");
$stmt->execute([$id]);
$candidat = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$candidat) {
  header('Location: index.php');
  exit;
}

/* =========================
   TRANSACTION SÉCURISÉE
========================= */
try {
  $pdo->beginTransaction();

  /* Mise à jour statut */
  $update = $pdo->prepare("
    UPDATE candidatures_formateurs
    SET statut = ?
    WHERE id = ?
    LIMIT 1
  ");
  $update->execute([$statut, $id]);

  /* =========================
     EMAIL (SI EMAIL VALIDE)
  ========================= */
  if (!empty($candidat['email']) && filter_var($candidat['email'], FILTER_VALIDATE_EMAIL)) {

    $emailData = emailFormateur($statut, [
      'nom'    => $candidat['nom'],
      'statut' => $statut
    ]);

    if (!empty($emailData['subject']) && !empty($emailData['message'])) {

      $headers  = "MIME-Version: 1.0\r\n";
      $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
      $headers .= "From: IBIG EDUFORM <no-reply@ibig-eduform.com>\r\n";
      $headers .= "Reply-To: contact@ibig-eduform.com\r\n";

      @mail(
        $candidat['email'],
        $emailData['subject'],
        $emailData['message'],
        $headers
      );
    }
  }

  $pdo->commit();

} catch (Throwable $e) {
  $pdo->rollBack();
  header('Location: index.php?error=update_failed');
  exit;
}

/* =========================
   REDIRECTION
========================= */
header('Location: view.php?id=' . $id);
exit;
