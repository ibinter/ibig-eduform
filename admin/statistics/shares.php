<?php
declare(strict_types=1);

/* =====================================================
   BOOTSTRAP ADMIN (OBLIGATOIRE)
===================================================== */
require_once __DIR__ . '/../auth/middleware.php';
require_once __DIR__ . '/../../core/database.php';

/* =====================================================
   SÉCURITÉ
===================================================== */
Middleware::requireAuth();

/* =====================================================
   PAGE CONFIG
===================================================== */
$activeMenu = 'stats_shares';
$pageTitle  = 'Partages & R&eacute;seaux';

$pdo = Database::connect();

/* =====================================================
   DONNÉES
===================================================== */
$platforms = $pdo->query("
  SELECT platform, COUNT(*) total
  FROM site_share_events
  GROUP BY platform
  ORDER BY total DESC
")->fetchAll(PDO::FETCH_ASSOC);

$total = (int)$pdo->query("
  SELECT COUNT(*) FROM site_share_events
")->fetchColumn();

/* =====================================================
   RENDER
===================================================== */
ob_start();
?>

<h2>&#128202; Partages des formations</h2>

<div class="card" style="margin-bottom:16px">
  <strong>Total des partages :</strong> <?= $total; ?>
</div>

<table class="table">
  <thead>
    <tr>
      <th>Plateforme</th>
      <th>Total</th>
    </tr>
  </thead>
  <tbody>
    <?php if (!$platforms): ?>
      <tr>
        <td colspan="2" style="text-align:center;color:#777">
          Aucun partage enregistr&eacute;
        </td>
      </tr>
    <?php else: ?>
      <?php foreach ($platforms as $p): ?>
        <tr>
          <td><?= htmlspecialchars(ucfirst($p['platform']), ENT_QUOTES, 'UTF-8'); ?></td>
          <td><?= (int)$p['total']; ?></td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </tbody>
</table>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout/layout.php';
