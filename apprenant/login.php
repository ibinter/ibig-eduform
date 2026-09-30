<?php
declare(strict_types=1);
require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/apprenant_auth.php';
require_once __DIR__ . '/../core/mail.php';

// Déjà connecté
if (apprenant_check()) {
    header('Location: /apprenant/dashboard.php');
    exit;
}

$pdo     = Database::connect();
$sent    = false;
$error   = '';
$pageTitle = 'Mon espace apprenant — IBIG EDUFORM';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = trim(strtolower((string)($_POST['email'] ?? '')));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Adresse email invalide.';
    } else {
        // Vérifie qu'il existe au moins une preinscription avec cet email
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM preinscriptions WHERE LOWER(email) = ? LIMIT 1");
        $stmt->execute([$email]);
        $count = (int)$stmt->fetchColumn();

        if ($count === 0) {
            $error = 'Aucune inscription trouvée pour cet email. Avez-vous utilisé un autre email lors de votre inscription ?';
        } else {
            $token = apprenant_create_token($pdo, $email);
            $link  = rtrim(APP_URL, '/') . '/apprenant/verify.php?t=' . urlencode($token);

            $html = '
<!DOCTYPE html><html><body style="font-family:Arial,sans-serif;background:#f1f5f9;padding:30px">
<div style="max-width:520px;margin:0 auto;background:#fff;border-radius:14px;overflow:hidden">
  <div style="background:#0a1733;padding:24px 28px">
    <img src="https://ibig-eduform.com/assets/images/logo.png" alt="IBIG EDUFORM" height="40">
  </div>
  <div style="padding:28px">
    <h2 style="color:#0a1733;margin:0 0 12px">Connexion à votre espace apprenant</h2>
    <p style="color:#374151;font-size:15px">Cliquez sur le bouton ci-dessous pour accéder à votre espace. Ce lien est valable <strong>1 heure</strong>.</p>
    <a href="' . $link . '" style="display:inline-block;margin:20px 0;padding:14px 28px;background:#f59e0b;color:#0a1733;font-weight:800;border-radius:10px;text-decoration:none;font-size:15px">
      🎓 Accéder à mon espace apprenant
    </a>
    <p style="color:#6b7280;font-size:13px">Si vous n\'avez pas demandé ce lien, ignorez cet email.</p>
    <hr style="border:none;border-top:1px solid #e5e7eb;margin:20px 0">
    <p style="color:#9ca3af;font-size:12px">IBIG SARL · Institut de Formation Professionnelle · Abidjan, Côte d\'Ivoire</p>
  </div>
</div>
</body></html>';

            send_mail($email, '🎓 Votre lien de connexion — IBIG EDUFORM', $html);
            $sent = true;
        }
    }
}

include __DIR__ . '/../partials/header.php';
?>
<style>
.ap-wrap{max-width:480px;margin:80px auto 100px;padding:0 18px}
.ap-card{background:#0b1220;color:#e5e7eb;border-radius:18px;padding:36px;border:1px solid rgba(255,255,255,.08);text-align:center}
.ap-card h1{color:#f5a623;font-size:1.6rem;margin:0 0 8px}
.ap-card p{color:#94a3b8;margin:0 0 24px;font-size:.95rem}
.ap-input{width:100%;padding:13px 16px;border-radius:10px;border:1px solid rgba(255,255,255,.18);background:#020617;color:#e5e7eb;font-size:15px;box-sizing:border-box}
.ap-btn{width:100%;margin-top:14px;padding:14px;border:0;border-radius:10px;background:#f59e0b;color:#0a1733;font-weight:800;font-size:15px;cursor:pointer}
.ap-btn:hover{background:#fbbf24}
.ap-err{margin-top:12px;padding:12px 14px;border-radius:8px;background:rgba(239,68,68,.14);border:1px solid #ef4444;color:#fecaca;font-size:13.5px;text-align:left}
.ap-ok{margin-top:12px;padding:16px;border-radius:10px;background:rgba(34,197,94,.12);border:1px solid #22c55e;color:#bbf7d0;font-size:14px;text-align:left}
</style>

<div class="ap-wrap">
  <div class="ap-card">
    <div style="font-size:2.5rem;margin-bottom:12px">🎓</div>
    <h1>Mon espace apprenant</h1>
    <p>Entrez votre adresse email d'inscription.<br>Nous vous envoyons un lien de connexion instantané.</p>

    <?php if ($sent): ?>
      <div class="ap-ok">
        <strong>✅ Lien envoyé !</strong><br>
        Vérifiez votre boîte mail (et vos spams). Le lien est valable 1 heure.
      </div>
    <?php else: ?>
      <?php if ($error): ?><div class="ap-err"><?= htmlspecialchars($error) ?></div><?php endif; ?>
      <form method="post" style="margin-top:<?= $error ? '0' : '0' ?>">
        <?= csrf_field() ?>
        <input class="ap-input" type="email" name="email" placeholder="votre@email.com" required
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        <button class="ap-btn" type="submit">Recevoir mon lien de connexion →</button>
      </form>
    <?php endif; ?>

    <p style="margin-top:24px;font-size:12px;color:#4b5563">
      Vous n'avez pas encore de dossier ? <a href="/preinscription-generale.php" style="color:#f59e0b">S'inscrire à une formation →</a>
    </p>
  </div>
</div>

<?php include __DIR__ . '/../partials/footer.php'; ?>
