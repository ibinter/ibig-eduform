<?php
declare(strict_types=1);

/**
 * ============================================================
 * IBIG EDUFORM — USERS / CREATE (SUPER ADMIN ONLY)
 * VERSION SIMPLE & STABLE
 * ============================================================
 */

require_once __DIR__ . '/../auth/guard.php';
require_permission('manage_users');

require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../core/auth.php';

/* 🔒 SUPER ADMIN UNIQUEMENT */
$u = auth_user();
if (($u['role'] ?? '') !== 'super_admin') {
    http_response_code(403);
    exit('Accès réservé au super administrateur.');
}

$pageTitle  = "Créer un utilisateur";
$activeMenu = "users";

$pdo = Database::connect();

$errors  = [];
$success = false;

/* =========================
   TRAITEMENT
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    csrf_check();

    $firstName = trim((string)($_POST['first_name'] ?? ''));
    $lastName  = trim((string)($_POST['last_name'] ?? ''));
    $email     = trim((string)($_POST['email'] ?? ''));
    $role      = trim((string)($_POST['role'] ?? 'user'));
    $status    = trim((string)($_POST['status'] ?? 'active'));
    $password  = (string)($_POST['password'] ?? '');

    /* VALIDATIONS */
    if ($firstName === '') $errors[] = "Le prénom est obligatoire.";
    if ($lastName === '')  $errors[] = "Le nom est obligatoire.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Adresse email invalide.";
    }
    if (strlen($password) < 6) {
        $errors[] = "Le mot de passe doit contenir au moins 6 caractères.";
    }

    $allowedRoles  = ['super_admin','admin','rh','commercial','user'];
    $allowedStatus = ['active','inactive'];

    if (!in_array($role, $allowedRoles, true)) {
        $errors[] = "Rôle invalide.";
    }
    if (!in_array($status, $allowedStatus, true)) {
        $errors[] = "Statut invalide.";
    }

    /* EMAIL UNIQUE */
    $check = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $check->execute([$email]);
    if ($check->fetch()) {
        $errors[] = "Un utilisateur avec cet email existe déjà.";
    }

    /* INSERT */
    if (empty($errors)) {

        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("
            INSERT INTO users (
                first_name,
                last_name,
                email,
                password_hash,
                role,
                status,
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");

        $stmt->execute([
            $firstName,
            $lastName,
            $email,
            $hash,
            $role,
            $status
        ]);

        $success = true;
    }
}

/* =========================
   AFFICHAGE
========================= */
ob_start();
?>

<div class="card" style="max-width:720px">

  <div style="display:flex;justify-content:space-between;align-items:center">
    <h2>Créer un utilisateur</h2>
    <a class="btn btn-secondary" href="index.php">← Retour</a>
  </div>

  <?php if ($success): ?>
    <div class="pill ok" style="margin-top:16px">
      Utilisateur créé avec succès.
    </div>
  <?php endif; ?>

  <?php if (!empty($errors)): ?>
    <div class="pill wait" style="margin-top:16px">
      <ul style="margin:0;padding-left:18px">
        <?php foreach ($errors as $e): ?>
          <li><?= e($e); ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <?php if (!$success): ?>
    <form method="post" style="margin-top:20px" autocomplete="off">
      <?= csrf_field(); ?>

      <div class="form-group">
        <label>Prénom</label>
        <input name="first_name" required value="<?= e($_POST['first_name'] ?? ''); ?>">
      </div>

      <div class="form-group">
        <label>Nom</label>
        <input name="last_name" required value="<?= e($_POST['last_name'] ?? ''); ?>">
      </div>

      <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" required value="<?= e($_POST['email'] ?? ''); ?>">
      </div>

      <div class="form-group">
        <label>Mot de passe</label>
        <input type="password" name="password" required>
      </div>

      <div class="form-group">
        <label>Rôle</label>
        <select name="role">
          <option value="user">Utilisateur</option>
          <option value="commercial">Commercial</option>
          <option value="rh">RH</option>
          <option value="admin">Admin</option>
          <option value="super_admin">Super Admin</option>
        </select>
      </div>

      <div class="form-group">
        <label>Statut</label>
        <select name="status">
          <option value="active">Actif</option>
          <option value="inactive">Inactif</option>
        </select>
      </div>

      <button class="btn btn-success" style="margin-top:10px">
        Créer l’utilisateur
      </button>

    </form>
  <?php endif; ?>

</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout/layout.php';