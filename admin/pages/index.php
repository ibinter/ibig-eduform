<?php
require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../auth/middleware.php';

Middleware::requireAuth();

$pageTitle  = 'Pages du site';
$activeMenu = 'pages';
$pdo = Database::connect();

try {
    $pages = $pdo->query("SELECT * FROM site_pages ORDER BY title ASC")->fetchAll(PDO::FETCH_ASSOC);
    $tableOk = true;
} catch (Throwable $e) {
    $pages = [];
    $tableOk = false;
}

ob_start();
?>
<div class="page-head" style="margin-bottom:18px">
  <h1 style="margin:0">Pages éditables</h1>
  <p style="color:#666;margin:6px 0 0">
    Tant qu’une page n’est pas <strong>publiée</strong>, le site affiche sa version d’origine.
    Publiez-la pour afficher votre contenu saisi ici.
  </p>
</div>

<?php if (!$tableOk): ?>
  <div style="padding:16px;border:1px solid #f5c518;background:#fff8e1;color:#7a5b00;border-radius:10px">
    La table <code>site_pages</code> n’existe pas encore. Exécutez la migration
    <code>migrations/2026_content_admin.sql</code> puis rechargez.
  </div>
<?php else: ?>
  <table class="table" style="width:100%;border-collapse:collapse">
    <thead>
      <tr>
        <th style="text-align:left">Page</th>
        <th style="text-align:left">Slug</th>
        <th style="text-align:left">Statut</th>
        <th style="text-align:right">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($pages as $p): ?>
        <tr>
          <td><?= e($p['title']); ?></td>
          <td><code><?= e($p['slug']); ?></code></td>
          <td>
            <a href="toggle.php?id=<?= (int)$p['id']; ?>&csrf=<?= csrf_token(); ?>"
               class="badge <?= $p['is_published'] ? 'badge-success' : 'badge-muted'; ?>">
              <?= $p['is_published'] ? 'Publiée (base)' : 'Version d’origine'; ?>
            </a>
          </td>
          <td style="text-align:right;white-space:nowrap">
            <a class="btn btn-sm" target="_blank" href="/<?= e($p['slug']); ?>.php">Voir</a>
            <a class="btn btn-sm btn-primary" href="edit.php?id=<?= (int)$p['id']; ?>">Éditer</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout/layout.php';
