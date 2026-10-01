<?php
declare(strict_types=1);
/**
 * IBIG EDUFORM — admin/preinscriptions/send-satisfaction.php
 * Envoie un lien de formulaire de satisfaction à un apprenant confirmé.
 * Appelé via POST depuis la page de détail de préinscription.
 */

require_once __DIR__ . '/../_init.php';
require_once __DIR__ . '/../../core/notifications.php';

Middleware::requireAuth();
csrf_check();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('index.php'); }

$pdo = Database::connect();
$id  = (int)($_POST['id'] ?? 0);
if ($id <= 0) redirect('index.php');

/* ── Crée les tables si besoin ── */
$pdo->exec("CREATE TABLE IF NOT EXISTS satisfaction_tokens (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  preinscription_id BIGINT UNSIGNED NOT NULL UNIQUE,
  email VARCHAR(255) NOT NULL,
  token CHAR(64) NOT NULL UNIQUE,
  expires_at DATETIME NOT NULL,
  used_at DATETIME DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_token (token), INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

/* ── Récupère la préinscription ── */
$stmt = $pdo->prepare("
    SELECT p.id, p.nom, p.prenoms, p.email, p.statut,
           f.titre AS formation_titre
    FROM preinscriptions p
    LEFT JOIN formations f ON f.id = p.formation_id
    WHERE p.id = ? LIMIT 1
");
$stmt->execute([$id]);
$p = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$p || empty($p['email'])) {
    $_SESSION['flash_error'] = 'Préinscription introuvable ou email manquant.';
    redirect('view.php?id=' . $id);
}

/* ── Génère (ou renouvelle) le token ── */
$token = bin2hex(random_bytes(32));

$pdo->prepare("
    INSERT INTO satisfaction_tokens (preinscription_id, email, token, expires_at)
    VALUES (?, ?, ?, NOW() + INTERVAL 30 DAY)
    ON DUPLICATE KEY UPDATE
      token = VALUES(token), expires_at = VALUES(expires_at), used_at = NULL
")->execute([$id, $p['email'], $token]);

/* ── Construit le lien ── */
$baseUrl = defined('APP_URL') ? rtrim(APP_URL, '/') : 'https://ibig-eduform.com';
$link = $baseUrl . '/satisfaction.php?t=' . rawurlencode($token);

$nom = trim((string)$p['prenoms'] . ' ' . (string)$p['nom']);
$titreF = (string)($p['formation_titre'] ?? 'votre formation');

/* ── Email HTML ── */
$html = '<!DOCTYPE html><html lang="fr"><body style="font-family:Arial,sans-serif;color:#1e293b;max-width:620px;margin:0 auto;padding:24px">
<img src="https://ibig-eduform.com/assets/images/logo.png" alt="IBIG EDUFORM" style="height:48px;margin-bottom:20px">
<h2 style="color:#0a1733;font-size:1.2rem;margin:0 0 16px">Votre avis nous est précieux !</h2>
<p style="line-height:1.7;margin-bottom:16px">
  Bonjour <strong>' . htmlspecialchars($nom, ENT_QUOTES) . '</strong>,<br><br>
  Merci d\'avoir participé à la formation <strong>' . htmlspecialchars($titreF, ENT_QUOTES) . '</strong>.<br>
  Nous espérons que cette expérience a répondu à vos attentes.
</p>
<p style="line-height:1.7;margin-bottom:24px">
  Afin d\'améliorer continuellement la qualité de nos formations, nous vous invitons à prendre <strong>2 minutes</strong> pour compléter notre formulaire d\'évaluation.
</p>
<div style="text-align:center;margin:28px 0">
  <a href="' . htmlspecialchars($link, ENT_QUOTES) . '"
     style="display:inline-block;background:#f59e0b;color:#0a1733;font-weight:700;padding:14px 32px;border-radius:10px;text-decoration:none;font-size:15px">
    ⭐ Donner mon avis
  </a>
</div>
<p style="font-size:12px;color:#94a3b8;margin-top:24px">Ce lien est valable 30 jours. Si vous ne souhaitez pas répondre, ignorez cet email.</p>
<hr style="margin:24px 0;border:none;border-top:1px solid #e2e8f0">
<p style="font-size:12px;color:#6b7280">
  IBIG EDUFORM — Institut de Formation Professionnelle<br>
  📞 +225 07 78 88 25 92 · ✉️ formation@ibig-eduform.com
</p>
</body></html>';

$ok = notify_email(
    (string)$p['email'],
    'Votre avis sur la formation ' . $titreF . ' — IBIG EDUFORM',
    $html
);

if ($ok) {
    $_SESSION['flash_success'] = 'Email de satisfaction envoyé à ' . $p['email'] . '.';
} else {
    $_SESSION['flash_error'] = 'Échec de l\'envoi. Vérifiez la configuration email du serveur.';
}

redirect('view.php?id=' . $id);
