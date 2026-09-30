<?php
declare(strict_types=1);
require_once __DIR__ . '/../auth/guard.php';
require_permission('manage_admins');
require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../core/csrf.php';
require_once __DIR__ . '/../auth/middleware.php';
require_once __DIR__ . '/../../core/auth.php';

Middleware::requireAuth();
$me = auth_user();
if (($me['role'] ?? '') !== 'super_admin') {
    http_response_code(403); exit('Accès réservé au super administrateur.');
}

$pageTitle  = "Nouvel administrateur";
$activeMenu = "admins";
$pdo = Database::connect();
$errors = []; $success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $firstName = trim((string)($_POST['first_name'] ?? ''));
    $lastName  = trim((string)($_POST['last_name']  ?? ''));
    $email     = trim((string)($_POST['email']      ?? ''));
    $role      = trim((string)($_POST['role']       ?? 'admin'));
    $status    = trim((string)($_POST['status']     ?? 'active'));
    $password  = (string)($_POST['password'] ?? '');

    if ($firstName === '')                              { $errors[] = "Le prénom est obligatoire."; }
    if ($lastName === '')                               { $errors[] = "Le nom est obligatoire."; }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))     { $errors[] = "Email invalide."; }
    if (strlen($password) < 8)                          { $errors[] = "Mot de passe : 8 caractères minimum."; }
    if (!in_array($role,   ['super_admin','admin','commercial','rh'], true)) { $errors[] = "Rôle invalide."; }
    if (!in_array($status, ['active','inactive'], true))                     { $errors[] = "Statut invalide."; }

    if (empty($errors)) {
        $chk = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $chk->execute([$email]);
        if ($chk->fetch()) { $errors[] = "Cet email est déjà utilisé."; }
    }

    if (empty($errors)) {
        $pdo->prepare("INSERT INTO users (first_name,last_name,email,password_hash,role,status,created_at)
                        VALUES (?,?,?,?,?,?,NOW())")
            ->execute([$firstName, $lastName, $email, password_hash($password, PASSWORD_DEFAULT), $role, $status]);
        $success = true;
    }
}

ob_start();
?>
<div class="card" style="max-width:700px">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
    <h2 style="margin:0">&#43; Nouvel administrateur</h2>
    <a href="index.php" style="padding:8px 16px;border-radius:10px;background:#f1f5f9;color:#334155;font-size:13px;font-weight:700;text-decoration:none">&#8592; Retour</a>
  </div>

  <?php if ($success): ?>
    <div style="background:#dcfce7;border:1px solid #bbf7d0;color:#166534;border-radius:10px;padding:14px 16px;font-weight:700;margin-bottom:16px">
      &#9989; Administrateur créé avec succès. <a href="index.php" style="color:#166534;margin-left:8px">← Voir la liste</a>
    </div>
  <?php endif; ?>

  <?php if (!empty($errors)): ?>
    <div style="background:#fee2e2;border:1px solid #fecaca;color:#991b1b;border-radius:10px;padding:14px 16px;margin-bottom:16px">
      <?php foreach ($errors as $err): ?><div>&#8226; <?= e($err) ?></div><?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if (!$success): ?>
  <form method="post" autocomplete="off">
    <?= csrf_field() ?>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
      <div>
        <label style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.3px;display:block;margin-bottom:5px">Prénom *</label>
        <input name="first_name" required value="<?= e($_POST['first_name'] ?? '') ?>" style="width:100%;padding:10px 12px;border:1px solid #e2e8f0;border-radius:10px;font-size:14px;box-sizing:border-box">
      </div>
      <div>
        <label style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.3px;display:block;margin-bottom:5px">Nom *</label>
        <input name="last_name" required value="<?= e($_POST['last_name'] ?? '') ?>" style="width:100%;padding:10px 12px;border:1px solid #e2e8f0;border-radius:10px;font-size:14px;box-sizing:border-box">
      </div>
    </div>
    <div style="margin-top:14px">
      <label style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.3px;display:block;margin-bottom:5px">Email *</label>
      <input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>" style="width:100%;padding:10px 12px;border:1px solid #e2e8f0;border-radius:10px;font-size:14px;box-sizing:border-box">
    </div>
    <div style="margin-top:14px">
      <label style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.3px;display:block;margin-bottom:5px">Mot de passe * (min. 8 caractères)</label>
      <input type="password" name="password" required minlength="8" style="width:100%;padding:10px 12px;border:1px solid #e2e8f0;border-radius:10px;font-size:14px;box-sizing:border-box">
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-top:14px">
      <div>
        <label style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.3px;display:block;margin-bottom:5px">Rôle</label>
        <select name="role" style="width:100%;padding:10px 12px;border:1px solid #e2e8f0;border-radius:10px;font-size:14px;background:#fff">
          <option value="admin"       <?= ($_POST['role'] ?? '') === 'admin'       ? 'selected' : '' ?>>Admin</option>
          <option value="commercial"  <?= ($_POST['role'] ?? '') === 'commercial'  ? 'selected' : '' ?>>Commercial</option>
          <option value="rh"          <?= ($_POST['role'] ?? '') === 'rh'          ? 'selected' : '' ?>>RH</option>
          <option value="super_admin" <?= ($_POST['role'] ?? '') === 'super_admin' ? 'selected' : '' ?>>Super Admin</option>
        </select>
      </div>
      <div>
        <label style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.3px;display:block;margin-bottom:5px">Statut</label>
        <select name="status" style="width:100%;padding:10px 12px;border:1px solid #e2e8f0;border-radius:10px;font-size:14px;background:#fff">
          <option value="active"   <?= ($_POST['status'] ?? 'active') === 'active'   ? 'selected' : '' ?>>Actif</option>
          <option value="inactive" <?= ($_POST['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactif</option>
        </select>
      </div>
    </div>
    <div style="margin-top:20px">
      <button type="submit" style="background:linear-gradient(135deg,#1f3fe0,#3b82f6);color:#fff;border:0;padding:11px 24px;border-radius:10px;font-weight:800;font-size:14px;cursor:pointer">
        &#128190; Créer l'administrateur
      </button>
    </div>
  </form>
  <?php endif; ?>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout/layout.php';
