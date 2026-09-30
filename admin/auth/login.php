<?php
declare(strict_types=1);

/**
 * ============================================================
 * IBIG EDUFORM — AUTH / LOGIN ADMIN (VERSION FINALE)
 * ------------------------------------------------------------
 * â Connexion sécurisée
 * â RBAC compatible (users.role)
 * â Audit logs (login)
 * â Gestion ?next=
 * â Compatible guard.php / audit.php
 * ============================================================
 */

require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../core/csrf.php';
require_once __DIR__ . '/guard.php';
require_once __DIR__ . '/audit.php';

/* ============================================================
   PAGE À REJOINDRE APRÈS LOGIN
   On n'accepte qu'un chemin interne (anti open-redirect).
============================================================ */
$next = $_GET['next'] ?? '/admin/dashboard.php';
if (!preg_match('#^/[A-Za-z0-9/_\-.]*$#', $next)) {
    $next = '/admin/dashboard.php';
}

/* Déjà connecté → redirection directe */
if (!empty($_SESSION['user']['id'])) {
    header('Location: ' . $next);
    exit;
}

$error = '';

/* ============================================================
   TRAITEMENT FORMULAIRE
============================================================ */
/* ============================================================
   ANTI-BRUTE-FORCE (5 tentatives / 15 min par session)
============================================================ */
$now      = time();
$attempts = $_SESSION['login_attempts'] ?? ['count' => 0, 'first' => $now];
if ($now - ($attempts['first'] ?? $now) > 900) {
    $attempts = ['count' => 0, 'first' => $now]; // fenêtre expirée
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* CSRF */
    csrf_check();

    /* Trop de tentatives → blocage temporaire */
    if (($attempts['count'] ?? 0) >= 5) {
        $error = "Trop de tentatives. Réessayez dans quelques minutes.";
    }

    $email    = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if ($error !== '') {
        // bloqué : ne rien faire de plus
    } elseif ($email === '' || $password === '') {
        $error = "Veuillez remplir tous les champs.";
    } else {

        $pdo = Database::connect();

        $stmt = $pdo->prepare("
            SELECT 
                id,
                first_name,
                last_name,
                email,
                password_hash,
                role,
                status
            FROM users
            WHERE email = ?
            LIMIT 1
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        /* Échec authentification */
        if (!$user || !password_verify($password, $user['password_hash'])) {
            $error = "Identifiants incorrects.";
        }
        /* Compte désactivé */
        elseif (($user['status'] ?? '') !== 'active') {
            $error = "Votre compte est désactivé.";
        }
        /* Succès */
        else {

            /* Anti-fixation de session */
            session_regenerate_id(true);
            unset($_SESSION['login_attempts']);

            /* Session utilisateur (minimal & propre) */
            $_SESSION['user'] = [
                'id'         => (int)$user['id'],
                'first_name' => $user['first_name'],
                'last_name'  => $user['last_name'],
                'email'      => $user['email'],
                'role'       => $user['role'],
            ];

            /* Audit log — LOGIN */
            audit_log(
                'login',
                'auth',
                null,
                'Connexion administration'
            );

            header('Location: ' . $next);
            exit;
        }
    }

    /* Échec → on incrémente le compteur de tentatives */
    if ($error !== '') {
        $attempts['count'] = ($attempts['count'] ?? 0) + 1;
        $_SESSION['login_attempts'] = $attempts;
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Connexion — Administration IBIG EDUFORM</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="icon" href="/favicon.ico">

    <style>
:root{
  --edu-blue:#0b4cb8;
  --edu-blue-dark:#0a2e5c;
  --edu-red:#ff2d2d;
  --edu-bg:#020617;
  --edu-card:rgba(255,255,255,.06);
  --edu-border:rgba(255,255,255,.12);
  --edu-muted:#94a3b8;
}

body{
  margin:0;
  min-height:100vh;
  display:flex;
  align-items:center;
  justify-content:center;
  background:
    radial-gradient(800px 400px at 15% 10%, rgba(11,76,184,.25), transparent),
    radial-gradient(700px 500px at 85% 90%, rgba(255,45,45,.18), transparent),
    var(--edu-bg);
  font-family:Inter,system-ui,sans-serif;
  color:#e5e7eb;
}

/* WRAPPER */
.login-wrapper{
  width:100%;
  max-width:440px;
  padding:20px;
}

/* CARD */
.card{
  background:var(--edu-card);
  border:1px solid var(--edu-border);
  border-radius:26px;
  padding:34px 32px 30px;
  box-shadow:
    0 40px 90px rgba(0,0,0,.6),
    inset 0 1px 0 rgba(255,255,255,.05);
  backdrop-filter: blur(12px);
}

/* BRAND */
.brand{
  display:flex;
  align-items:center;
  gap:14px;
  margin-bottom:24px;
}
.brand img{
  width:56px;
  height:56px;
  border-radius:14px;
  background:#ffffff;
  padding:6px;
}
.brand-text{
  display:flex;
  flex-direction:column;
}
.brand-title{
  font-size:20px;
  font-weight:900;
  color:#ffffff;
}
.brand-sub{
  font-size:12px;
  color:var(--edu-muted);
}

/* TITRES */
h1{
  margin:0 0 6px;
  font-size:24px;
  font-weight:900;
  text-align:center;
}
.subtitle{
  text-align:center;
  font-size:13px;
  color:var(--edu-muted);
  margin-bottom:26px;
}

/* FORM */
label{
  display:block;
  margin-top:16px;
  font-size:13px;
  color:var(--edu-muted);
}
input{
  width:100%;
  margin-top:6px;
  padding:14px 16px;
  border-radius:16px;
  border:1px solid rgba(255,255,255,.18);
  background:#020617;
  color:#e5e7eb;
  font-size:14px;
}
input:focus{
  outline:none;
  border-color:var(--edu-blue);
  box-shadow:0 0 0 3px rgba(11,76,184,.35);
}

/* BUTTON */
.btn{
  width:100%;
  margin-top:26px;
  padding:16px;
  border-radius:18px;
  border:none;
  font-weight:900;
  font-size:15px;
  background:linear-gradient(135deg,var(--edu-blue),var(--edu-red));
  color:#ffffff;
  cursor:pointer;
  box-shadow:0 16px 40px rgba(11,76,184,.55);
}
.btn:hover{
  filter:brightness(1.1);
}

/* ERROR */
.error{
  margin-top:16px;
  padding:14px;
  border-radius:16px;
  background:rgba(255,45,45,.14);
  border:1px solid rgba(255,45,45,.45);
  color:#fecaca;
  font-size:13px;
}

/* FOOTER */
.login-footer{
  margin-top:20px;
  text-align:center;
  font-size:12px;
  color:var(--edu-muted);
}
</style>

</head>
<body>

<div class="login-wrapper">
  <div class="card">

    <div class="brand">
      <img src="/assets/images/logo.png" alt="IBIG EDUFORM">
      <div class="brand-text">
        <div class="brand-title">IBIG EDUFORM</div>
        <div class="brand-sub">Formation & Administration</div>
      </div>
    </div>

    <h1>Accès Administration</h1>
    <div class="subtitle">
      Plateforme de gestion des formations
    </div>

    <?php if ($error): ?>
      <div class="error"><?= e($error); ?></div>
    <?php endif; ?>

    <form method="post" autocomplete="off">
      <?= csrf_field(); ?>
      <label>Email professionnel</label>
      <input type="email" name="email" required autofocus>

      <label>Mot de passe</label>
      <input type="password" name="password" required>

      <button class="btn">Se connecter</button>
    </form>

    <div class="login-footer">
      © <?= date('Y'); ?> IBIG EDUFORM — Accès sécurisé
    </div>

  </div>
</div>

</body>
</html>