<?php
declare(strict_types=1);
/* =========================================================
   IBIG EDUFORM — Capture de leads (TDR / calendrier / rappel / alerte)
   Champs enrichis : prénom, nom, pays, email, WhatsApp, mode, format,
   entreprise, poste.
========================================================= */
require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/core/csrf.php';
require_once __DIR__ . '/core/whatsapp.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok' => false, 'error' => 'Méthode non autorisée.']);
  exit;
}

/* ── CSRF ── */
$sent = $_POST['csrf'] ?? '';
if (!is_string($sent) || !hash_equals($_SESSION['csrf'] ?? '', $sent)) {
  http_response_code(419);
  echo json_encode(['ok' => false, 'error' => 'Session expirée. Rechargez la page puis réessayez.']);
  exit;
}

/* ── Champs de base ── */
$prenom   = trim((string)($_POST['prenom']   ?? ''));
$nom      = trim((string)($_POST['nom']      ?? ''));
$email    = trim((string)($_POST['email']    ?? ''));
$whatsapp = trim((string)($_POST['whatsapp'] ?? ''));
$pays     = trim((string)($_POST['pays']     ?? ''));
$modeSouhaite   = trim((string)($_POST['mode_souhaite']   ?? ''));
$formatSouhaite = trim((string)($_POST['format_souhaite'] ?? ''));
$entreprise = trim((string)($_POST['entreprise'] ?? ''));
$poste      = trim((string)($_POST['poste']      ?? ''));

/* Rétrocompat : si ancien champ "nom" envoyé seul */
$nomComplet = $prenom !== '' ? $prenom . ' ' . $nom : $nom;

$type = (string)($_POST['type'] ?? 'rappel');
$allowed = ['calendrier', 'rappel', 'alerte', 'tdr'];
if (!in_array($type, $allowed, true)) { $type = 'rappel'; }

$formationId    = (int)($_POST['formation_id'] ?? 0);
$formationTitre = trim((string)($_POST['formation_titre'] ?? ''));

/* ── Validation ── */
if ($nomComplet === '') {
  echo json_encode(['ok' => false, 'error' => 'Veuillez indiquer votre prénom et nom.']);
  exit;
}
if ($email === '' && $whatsapp === '') {
  echo json_encode(['ok' => false, 'error' => 'Indiquez au moins un email ou un numéro WhatsApp.']);
  exit;
}
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
  echo json_encode(['ok' => false, 'error' => 'Adresse email invalide.']);
  exit;
}
if ($whatsapp !== '') {
  $digits = preg_replace('/\D/', '', $whatsapp);
  if (strlen($digits) < 8) {
    echo json_encode(['ok' => false, 'error' => 'Numéro WhatsApp invalide (minimum 8 chiffres).']);
    exit;
  }
}

/* Contact principal pour rétrocompat colonne "contact" */
$contact = $email !== '' ? $email : $whatsapp;

/* ── Enregistrement DB ── */
try {
  $pdo = Database::connect();
  $st = $pdo->prepare("
    INSERT INTO leads
      (nom, prenom, contact, email, whatsapp, pays,
       mode_souhaite, format_souhaite, entreprise, poste,
       type, formation_id, formation_titre, ip_address, user_agent, created_at)
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())
  ");
  $st->execute([
    mb_substr($nomComplet, 0, 190),
    mb_substr($prenom, 0, 100),
    mb_substr($contact, 0, 190),
    mb_substr($email, 0, 190),
    mb_substr($whatsapp, 0, 30),
    mb_substr($pays, 0, 80),
    mb_substr($modeSouhaite, 0, 30),
    mb_substr($formatSouhaite, 0, 30),
    mb_substr($entreprise, 0, 190),
    mb_substr($poste, 0, 100),
    $type,
    $formationId > 0 ? $formationId : null,
    mb_substr($formationTitre, 0, 255),
    substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45),
    substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
  ]);
} catch (Throwable $e) {
  /* Fallback : colonnes enrichies absentes (avant migration) */
  try {
    $st2 = $pdo->prepare("INSERT INTO leads (nom, contact, type, formation_id, formation_titre, ip_address, user_agent, created_at) VALUES (?,?,?,?,?,?,?,NOW())");
    $st2->execute([
      mb_substr($nomComplet, 0, 190),
      mb_substr($contact, 0, 190),
      $type,
      $formationId > 0 ? $formationId : null,
      mb_substr($formationTitre, 0, 255),
      substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45),
      substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
    ]);
  } catch (Throwable $e2) {
    error_log('[LEAD] ' . $e2->getMessage());
    echo json_encode(['ok' => false, 'error' => 'Une erreur est survenue. Réessayez.']);
    exit;
  }
}

/* ── Notification WhatsApp admin ── */
$modeLabels   = ['en_ligne' => 'En ligne', 'presentiel' => 'Présentiel', 'hybride' => 'Hybride'];
$formatLabels = ['individuel' => 'Individuel', 'groupe_2_5' => 'Groupe 2-5 pers.', 'groupe_6_10' => 'Groupe 6-10 pers.', 'intra' => 'Intra-entreprise'];
$typeLabels   = ['calendrier' => 'Calendrier PDF', 'rappel' => 'Demande de rappel', 'alerte' => 'Alerte sessions', 'tdr' => 'Téléchargement TDR'];

$msg = "🎯 *Nouveau lead IBIG EDUFORM*\n"
     . "━━━━━━━━━━━━━━━━━\n"
     . "👤 *" . $nomComplet . "*" . ($pays !== '' ? " — " . $pays : '') . "\n"
     . ($email    !== '' ? "📧 " . $email    . "\n" : '')
     . ($whatsapp !== '' ? "📱 " . $whatsapp . "\n" : '')
     . ($entreprise !== '' ? "🏢 " . $entreprise . ($poste !== '' ? ' — ' . $poste : '') . "\n" : '')
     . "━━━━━━━━━━━━━━━━━\n"
     . "📋 Type : " . ($typeLabels[$type] ?? $type) . "\n"
     . ($formationTitre !== '' ? "🎓 Formation : " . $formationTitre . "\n" : '')
     . ($modeSouhaite   !== '' ? "💻 Mode : "   . ($modeLabels[$modeSouhaite]     ?? $modeSouhaite)   . "\n" : '')
     . ($formatSouhaite !== '' ? "👥 Format : " . ($formatLabels[$formatSouhaite] ?? $formatSouhaite) . "\n" : '');

try { whatsapp_notify_admin($msg); } catch (Throwable $e) { /* silencieux */ }

/* ── Réponse ── */
$resp = ['ok' => true, 'message' => 'Merci ! Nous revenons vers vous très vite.'];
if ($type === 'calendrier') {
  $resp['message']  = 'Merci ! Votre calendrier complet est prêt.';
  $resp['download'] = '/calendrier-pdf.php';
} elseif ($type === 'tdr' && $formationId > 0) {
  $resp['message']  = 'Merci ! Votre TDR est prêt.';
  $resp['download'] = '/tdr.php?formation=' . $formationId;
}
echo json_encode($resp);
