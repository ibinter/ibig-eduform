<?php
declare(strict_types=1);

$pageTitle  = 'Mon espace';
$activeMenu = 'dashboard';

$pdo = Database::connect();

ob_start();
?>

<div class="card">
  <h3>Bienvenue 👋</h3>
  <div class="muted">
    Consultez les formations, vos informations et notifications.
  </div>

  <div style="margin-top:16px">
    <a class="btn btn-secondary" href="/admin/calendrier/index.php">
      Voir les formations
    </a>
  </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout/layout.php';