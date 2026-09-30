<?php
require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../auth/middleware.php';

Middleware::requireAuth();

$pageTitle  = 'Carrousel d’accueil';
$activeMenu = 'hero';

$pdo = Database::connect();

try {
    $slides = $pdo->query("SELECT * FROM hero_slides ORDER BY position ASC, id ASC")
                  ->fetchAll(PDO::FETCH_ASSOC);
    $tableOk = true;
} catch (Throwable $e) {
    $slides = [];
    $tableOk = false;
}

ob_start();
?>
<div class="page-head" style="display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:18px">
  <h1 style="margin:0">Carrousel d’accueil</h1>
  <a class="btn btn-primary" href="create.php">+ Nouveau slide</a>
</div>

<?php if (!$tableOk): ?>
  <div style="padding:16px;border:1px solid #f5c518;background:#fff8e1;color:#7a5b00;border-radius:10px">
    La table <code>hero_slides</code> n’existe pas encore. Exécutez la migration
    <code>migrations/2026_content_admin.sql</code> dans votre base, puis rechargez.
  </div>
<?php elseif (!$slides): ?>
  <p>Aucun slide. La page d’accueil affiche son contenu par défaut.
     Cliquez sur « Nouveau slide » pour commencer.</p>
<?php else: ?>
  <table class="table" style="width:100%;border-collapse:collapse">
    <thead>
      <tr>
        <th style="text-align:left">#</th>
        <th style="text-align:left">Aperçu</th>
        <th style="text-align:left">Titre</th>
        <th style="text-align:left">Statut</th>
        <th style="text-align:right">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($slides as $s): ?>
        <tr>
          <td><?= (int)$s['position']; ?></td>
          <td>
            <img src="/assets/images/hero/<?= e($s['image']); ?>" alt=""
                 style="width:90px;height:54px;object-fit:cover;border-radius:6px"
                 loading="lazy" decoding="async"
                 onerror="this.style.opacity=.3">
          </td>
          <td><?= strip_tags((string)$s['title']); ?></td>
          <td>
            <a href="toggle.php?id=<?= (int)$s['id']; ?>&csrf=<?= csrf_token(); ?>"
               class="badge <?= $s['is_active'] ? 'badge-success' : 'badge-muted'; ?>">
              <?= $s['is_active'] ? 'Actif' : 'Masqué'; ?>
            </a>
          </td>
          <td style="text-align:right;white-space:nowrap">
            <a class="btn btn-sm" href="edit.php?id=<?= (int)$s['id']; ?>">Éditer</a>
            <a class="btn btn-sm btn-danger"
               href="delete.php?id=<?= (int)$s['id']; ?>&csrf=<?= csrf_token(); ?>"
               onclick="return confirm('Supprimer ce slide ?')">Suppr.</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout/layout.php';
