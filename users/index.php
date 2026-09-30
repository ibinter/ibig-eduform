<?php
require_once __DIR__ . '/../../core/bootstrap.php';
Middleware::requireAuth();

if (($_SESSION['user']['role'] ?? '') !== 'super_admin') {
  http_response_code(403);
  die('Acc&egrave;s refus&eacute;');
}

$pdo = Database::connect();

$admins = $pdo->query("
  SELECT id, nom, email, role, status, created_at
  FROM admins
  ORDER BY created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = "Administrateurs";
$activeMenu = "admins";

ob_start();
?>

<div class="card">

  <h2>Gestion des administrateurs</h2>

  <a href="create.php" class="btn btn-success" style="margin-bottom:15px">
    Ajouter un administrateur
  </a>

  <table class="table">
    <thead>
      <tr>
        <th>Nom</th>
        <th>Email</th>
        <th>R&ocirc;le</th>
        <th>Statut</th>
        <th>Date</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($admins as $a): ?>
      <tr>
        <td><?= e($a['nom']); ?></td>
        <td><?= e($a['email']); ?></td>
        <td><?= e($a['role']); ?></td>
        <td><?= e($a['status']); ?></td>
        <td><?= e($a['created_at']); ?></td>
        <td>
          <a href="edit.php?id=<?= (int)$a['id']; ?>" class="btn btn-sm btn-primary">Modifier</a>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout/layout.php';
