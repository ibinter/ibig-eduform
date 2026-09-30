<?php
declare(strict_types=1);

/* ===============================
   BOOTSTRAP ADMIN
================================ */
require_once __DIR__ . '/../auth/middleware.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/helpers.php';

/* ===============================
   SÉCURITÉ
================================ */
Middleware::requireAuth();

/* ===============================
   CONFIG PAGE
================================ */
$pageTitle  = 'Intentions en cours';
$activeMenu = 'intentions';

$pdo = Database::connect();

/* ===============================
   CHARGEMENT DES ALERTES
================================ */
$stmt = $pdo->query("
  SELECT a.*, f.titre
  FROM admin_alerts a
  LEFT JOIN formations f ON f.id = a.formation_id
  ORDER BY a.created_at DESC
  LIMIT 50
");
$alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ===============================
   RENDU
================================ */
ob_start();
?>

<h2>Intentions en cours</h2>

<?php if (empty($alerts)): ?>
  <div class="card muted">Aucune intention détectée.</div>
<?php else: ?>
  <table class="table">
    <thead>
      <tr>
        <th>Date</th>
        <th>Formation</th>
        <th>Score</th>
        <th>Message</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($alerts as $a): ?>
        <tr>
          <td><?= e(date('d/m/Y H:i', strtotime($a['created_at']))); ?></td>
          <td><?= e($a['titre'] ?? '—'); ?></td>
          <td><strong><?= (int)$a['score']; ?></strong></td>
          <td><?= e($a['message']); ?></td>
          <td>
            <?php if (!empty($a['session_key'])): ?>
              <a class="btn btn-success"
                 href="https://wa.me/?text=<?= urlencode('Bonjour, vous étiez intéressé par une formation IBIG EDUFORM'); ?>"
                 target="_blank">
                 Contacter
              </a>
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
