<?php
declare(strict_types=1);

/* =====================================================
   BOOTSTRAP ADMIN
===================================================== */
require_once __DIR__ . '/../auth/middleware.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/helpers.php';

Middleware::requireAuth();

/* =====================================================
   CONFIG PAGE
===================================================== */
$pageTitle  = 'Alertes & Intentions';
$activeMenu = 'stats_alerts';

$pdo = Database::connect();

/* =====================================================
   ACTION : marquer comme lue
===================================================== */
if (isset($_POST['mark_read'])) {
  csrf_check();
  $id = (int)($_POST['id'] ?? 0);
  if ($id > 0) {
    $pdo->prepare("UPDATE admin_alerts SET is_read = 1 WHERE id = ?")
        ->execute([$id]);
  }
  header('Location: alerts.php');
  exit;
}

/* =====================================================
   ALERTES NON LUES
===================================================== */
$alerts = $pdo->query("
  SELECT a.*, f.titre AS formation_titre
  FROM admin_alerts a
  LEFT JOIN formations f ON f.id = a.formation_id
  ORDER BY a.is_read ASC, a.created_at DESC
  LIMIT 100
")->fetchAll(PDO::FETCH_ASSOC);

ob_start();
?>

<h2>&#128276; Alertes & Intentions visiteurs</h2>

<?php if (empty($alerts)): ?>
  <div class="card muted">
    Aucune alerte pour le moment.
  </div>
<?php else: ?>

  <table class="table">
    <thead>
      <tr>
        <th>Type</th>
        <th>Message</th>
        <th>Formation</th>
        <th>Date</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($alerts as $a): ?>
        <tr class="<?= $a['is_read'] ? 'muted' : ''; ?>">
          <td>
            <strong><?= e($a['type']); ?></strong>
          </td>

          <td>
            <?= e($a['message']); ?>
            <?php if (!empty($a['meta_json'])): ?>
              <div class="muted" style="font-size:12px;margin-top:4px">
                <?= e($a['meta_json']); ?>
              </div>
            <?php endif; ?>
          </td>

          <td>
            <?= e($a['formation_titre'] ?? '—'); ?>
          </td>

          <td class="muted">
            <?= e(date('d/m/Y H:i', strtotime((string)$a['created_at']))); ?>
          </td>

          <td>
            <?php if (!$a['is_read']): ?>
              <form method="post" style="display:inline">
                <?= csrf_field(); ?>
                <input type="hidden" name="id" value="<?= (int)$a['id']; ?>">
                <button name="mark_read" class="btn btn-secondary btn-sm">
                  Marquer comme lue
                </button>
              </form>
            <?php else: ?>
              <span class="muted">Lue</span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout/layout.php';
