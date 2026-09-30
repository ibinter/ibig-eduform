<?php
require_once __DIR__ . '/../../core/bootstrap.php';
Middleware::requireAuth();

if (($_SESSION['user']['role'] ?? '') !== 'super_admin') {
  http_response_code(403);
  die('Acc&egrave;s refus&eacute;');
}

$pdo = Database::connect();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $nom   = trim($_POST['nom'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $role  = $_POST['role'] ?? 'admin';

  if ($nom === '' || $email === '') {
    $errors[] = "Tous les champs sont obligatoires.";
  }

  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Email invalide.";
  }

  if (empty($errors)) {

    $password = bin2hex(random_bytes(4));
    $hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("
      INSERT INTO admins (nom, email, password, role)
      VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$nom, $email, $hash, $role]);

    header("Location: index.php");
    exit;
  }
}

$pageTitle = "Ajouter administrateur";
$activeMenu = "admins";

ob_start();
?>

<div class="card" style="max-width:600px">

  <h2>Ajouter un administrateur</h2>

  <?php if ($errors): ?>
    <div class="pill wait">
      <ul>
        <?php foreach ($errors as $e): ?>
          <li><?= e($e); ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form method="post">

    <div class="form-group">
      <label>Nom</label>
      <input name="nom" required>
    </div>

    <div class="form-group">
      <label>Email</label>
      <input type="email" name="email" required>
    </div>

    <div class="form-group">
      <label>R&ocirc;le</label>
      <select name="role">
        <option value="admin">Admin</option>
        <option value="manager">Manager</option>
        <option value="editor">Editeur</option>
      </select>
    </div>

    <button class="btn btn-success">
      Cr&eacute;er l&rsquo;administrateur
    </button>

  </form>

</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout/layout.php';
