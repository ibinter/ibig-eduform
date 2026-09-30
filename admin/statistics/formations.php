<?php
require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../auth/middleware.php';

Middleware::requireAuth();

$pageTitle = "Statistiques par formation";
$activeMenu = "statistics";

$pdo = Database::connect();

$stats = $pdo->query("
  SELECT
    f.id,
    f.titre,
    COUNT(v.id) AS visites,
    COUNT(DISTINCT v.ip_hash) AS uniques
  FROM formations f
  LEFT JOIN site_visits v
    ON v.formation_id = f.id
    AND v.is_bot = 0
    AND v.visited_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
  GROUP BY f.id
  ORDER BY visites DESC
")->fetchAll(PDO::FETCH_ASSOC);

ob_start();
?>

<div class="card">
  <h2>&#127891; Statistiques par formation (30 jours)</h2>

  <table>
    <thead>
      <tr>
        <th>Formation</th>
        <th>Visites</th>
        <th>Visiteurs uniques</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($stats as $s): ?>
        <tr>
          <td><strong><?= e($s['titre']); ?></strong></td>
          <td><?= (int)$s['visites']; ?></td>
          <td><?= (int)$s['uniques']; ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout/layout.php';
