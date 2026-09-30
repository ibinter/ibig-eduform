<?php
declare(strict_types=1);

/**
 * ============================================================
 * IBIG EDUFORM — USERS / EDIT (VERSION FINALE)
 * ------------------------------------------------------------
 * ✔ Sécurité RBAC (manage_users)
 * ✔ Validation stricte
 * ✔ Mot de passe optionnel
 * ✔ Audit log (update user)
 * ✔ Rôles officiels uniquement
 * ============================================================
 */

/* ===============================
   SÉCURITÉ & BOOTSTRAP
================================ */
require_once __DIR__ . '/../auth/guard.php';
require_permission('manage_users');

require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../auth/audit.php';

$pageTitle  = "Modifier un utilisateur";
$activeMenu = "users";

$pdo = Database::connect();

/* ===============================
   ID UTILISATEUR
================================ */
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    exit('Utilisateur invalide.');
}

/* ===============================
   UTILISATEUR EXISTANT
================================ */
$stmt = $pdo->prepare("
    SELECT id, first_name, last_name, email, role, status
    FROM users
    WHERE id = ?
");
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    http_response_code(404);
    exit('Utilisateur introuvable.');
}

$errors  = [];
$success = false;

/* ===============================
   TRAITEMENT FORMULAIRE
================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    csrf_check();

    $firstName = trim((string)($_POST['first_name'] ?? ''));
    $lastName  = trim((string)($_POST['last_name'] ?? ''));
    $email     = trim((string)($_POST['email'] ?? ''));
    $role      = trim((string)($_POST['role'] ?? 'user'));
    $status    = trim((string)($_POST['status'] ?? 'active'));
    $password  = (string)($_POST['password'] ?? '');

    /* ---------------------------
       VALIDATIONS
    ---------------------------- */
    if ($firstName === '') {
        $errors[] = "Le prénom est obligatoire.";
    }
    if ($lastName === '') {
        $errors[] = "Le nom est obligatoire.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Adresse email invalide.";
    }

    /* Rôles autorisés */
    $allowedRoles = ['super_admin','admin','rh','commercial','user'];
    if (!in_array($role, $allowedRoles, true)) {
        $errors[] = "Rôle utilisateur invalide.";
    }

    /* Statuts autorisés */
    $allowedStatus = ['active','inactive'];
    if (!in_array($status, $allowedStatus, true)) {
        $errors[] = "Statut invalide.";
    }

    /* Email unique (hors utilisateur courant) */
    $check = $pdo->prepare("
        SELECT id FROM users
        WHERE email = ? AND id != ?
    ");
    $check->execute([$email, $id]);
    if ($check->fetch()) {
        $errors[] = "Cet email est déjà utilisé par un autre utilisateur.";
    }

    /* ---------------------------
       UPDATE
    ---------------------------- */
    if (empty($errors)) {

        $sql = "
            UPDATE users SET
                first_name = ?,
                last_name  = ?,
                email      = ?,
                role       = ?,
                status     = ?
        ";
        $params = [
            $firstName,
            $lastName,
            $email,
            $role,
            $status
        ];

        /* Mot de passe optionnel */
        if ($password !== '') {
            if (strlen($password) < 6) {
                $errors[] = "Le mot de passe doit contenir au moins 6 caractères.";
            } else {
                $sql .= ", password_hash = ?";
                $params[] = password_hash($password, PASSWORD_DEFAULT);
            }
        }

        if (empty($errors)) {
            $sql .= " WHERE id = ?";
            $params[] = $id;

            $update = $pdo->prepare($sql);
            $update->execute($params);

            /* Audit log */
            audit_log(
                'update',
                'user',
                $id,
                'Modification utilisateur : ' . $email
            );

            $success = true;

            /* Recharger données */
            $stmt->execute([$id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }
}

/* ===============================
   AFFICHAGE
================================ */
ob_start();
?>

<div class="card" style="max-width:720px">

  <div style="display:flex;justify-content:space-between;align-items:center">
    <h2>Modifier un utilisateur</h2>
    <a class="btn" href="index.php">← Retour</a>
  </div>

  <?php if ($success): ?>
    <div class="pill ok" style="margin-top:16px">
      Modifications enregistrées avec succès.
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

  <form method="post" style="margin-top:20px" autocomplete="off">
    <?= csrf_field(); ?>

    <div class="form-group">
      <label>Prénom</label>
      <input name="first_name" required value="<?= e($user['first_name']); ?>">
    </div>

    <div class="form-group">
      <label>Nom</label>
      <input name="last_name" required value="<?= e($user['last_name']); ?>">
    </div>

    <div class="form-group">
      <label>Email</label>
      <input type="email" name="email" required value="<?= e($user['email']); ?>">
    </div>

    <div class="form-group">
      <label>Nouveau mot de passe (optionnel)</label>
      <input type="password" name="password" placeholder="Laisser vide pour ne pas changer">
    </div>

    <div class="form-group">
      <label>Rôle</label>
      <select name="role">
        <option value="user" <?= $user['role']==='user'?'selected':''; ?>>Utilisateur</option>
        <option value="commercial" <?= $user['role']==='commercial'?'selected':''; ?>>Commercial</option>
        <option value="rh" <?= $user['role']==='rh'?'selected':''; ?>>RH</option>
        <option value="admin" <?= $user['role']==='admin'?'selected':''; ?>>Admin</option>
        <option value="super_admin" <?= $user['role']==='super_admin'?'selected':''; ?>>Super Admin</option>
      </select>
    </div>

    <div class="form-group">
      <label>Statut</label>
      <select name="status">
        <option value="active" <?= $user['status']==='active'?'selected':''; ?>>Actif</option>
        <option value="inactive" <?= $user['status']==='inactive'?'selected':''; ?>>Inactif</option>
      </select>
    </div>

    <div style="display:flex;gap:10px;margin-top:10px">
      <button class="btn btn-success">Enregistrer</button>
      <a class="btn btn-warning" href="index.php">Annuler</a>
    </div>
    
    <style>
/* =========================================================
   FIX LOCAL BOUTONS — IBIG EDUFORM
   (priorité maximale, sans toucher au global)
========================================================= */
button.btn,
a.btn{
  color:#ffffff !important;
  font-weight:800 !important;
}

/* SUCCESS */
button.btn-success{
  background:#16a34a !important;
  color:#ffffff !important;
  border:none !important;
}
button.btn-success:hover{
  background:#15803d !important;
  color:#ffffff !important;
}

/* WARNING */
a.btn-warning{
  background:#f59e0b !important;
  color:#111827 !important;
  border:none !important;
}
a.btn-warning:hover{
  background:#d97706 !important;
  color:#111827 !important;
}
</style>

  </form>

</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout/layout.php';