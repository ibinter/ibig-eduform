<?php
declare(strict_types=1);

require_once __DIR__ . '/../_init.php';
require_once __DIR__ . '/../auth/middleware.php';
require_once __DIR__ . '/../../core/notifications.php';

Middleware::requireAuth();
csrf_check();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('index.php'); }

$pdo = Database::connect();
$id  = (int)($_POST['id'] ?? 0);
if ($id <= 0) redirect('index.php');

$stmt = $pdo->prepare("SELECT * FROM demandes_formation WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$d = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$d) redirect('index.php');

$to      = trim((string)($d['email'] ?? ''));
$sujet   = trim((string)($_POST['sujet'] ?? ''));
$corps   = trim((string)($_POST['corps'] ?? ''));
$statut  = in_array($_POST['statut'] ?? '', ['nouvelle','traitee','devis_envoye','cloturee'])
           ? $_POST['statut'] : null;

$errors = [];
if ($to === '')    $errors[] = 'Cet interlocuteur n\'a pas fourni d\'email.';
if ($sujet === '') $errors[] = 'Le sujet est obligatoire.';
if ($corps === '') $errors[] = 'Le corps du message est obligatoire.';

if ($errors) {
    $_SESSION['df_error'] = implode(' ', $errors);
    redirect('view.php?id=' . $id);
}

$who   = trim(($d['prenoms'] ?? '') . ' ' . ($d['nom'] ?? ''));
$theme = $d['theme_formation'] ?: ($d['domaine_formation'] ?: 'votre demande de formation');

$html = '<!DOCTYPE html><html lang="fr"><body style="font-family:Arial,sans-serif;color:#1e293b;max-width:620px;margin:0 auto;padding:24px">
<img src="https://ibig-eduform.com/assets/images/logo.png" alt="IBIG EDUFORM" style="height:48px;margin-bottom:20px">
<div style="background:#f8fafc;border-left:4px solid #f59e0b;padding:16px 20px;border-radius:6px;margin-bottom:20px">
  <strong>Référence demande :</strong> #' . (int)$id . ' — ' . htmlspecialchars($theme, ENT_QUOTES) . '
</div>
' . nl2br(htmlspecialchars($corps, ENT_QUOTES)) . '
<hr style="margin:28px 0;border:none;border-top:1px solid #e2e8f0">
<p style="font-size:13px;color:#64748b">
  IBIG EDUFORM — Institut de Formation Professionnelle<br>
  📞 +225 07 78 88 25 92 · ✉️ formation@ibig-eduform.com<br>
  🌐 ibig-eduform.com
</p>
</body></html>';

$ok = notify_email($to, $sujet, $html);

if ($statut) {
    $pdo->prepare("UPDATE demandes_formation SET statut = ? WHERE id = ?")
        ->execute([$statut, $id]);
}

if ($ok) {
    $_SESSION['df_success'] = 'Email envoyé à ' . $to . '.';
} else {
    $_SESSION['df_error'] = 'Échec de l\'envoi email. Vérifiez la configuration mail du serveur.';
}

redirect('view.php?id=' . $id);
