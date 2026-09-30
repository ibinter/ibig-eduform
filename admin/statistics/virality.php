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
$activeMenu = 'stats_viral';
$pageTitle  = 'Formations virales';

$pdo = Database::connect();

/* =====================================================
   DONNÉES
===================================================== */
$rows = $pdo->query("
  SELECT
    f.titre,
    COUNT(DISTINCT s.id) AS partages,
    COUNT(DISTINCT p.id) AS leads,
    (COUNT(DISTINCT s.id) + COUNT(DISTINCT p.id) * 3) AS score
  FROM formations f
  LEFT JOIN site_share_events s ON s.formation_id = f.id
  LEFT JOIN preinscriptions p ON p.formation_id = f.id
  GROUP BY f.id
  ORDER BY score DESC
  LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

/* =====================================================
   RENDER
===================================================== */
ob_start();
?>

<h2>&#129504; Formations les plus virales</h2>

<table class="table">
  <thead>
    <tr>
      <th>#</th>
      <th>Formation</th>
      <th>Partages</th>
      <th>Leads</th>
      <th>Score viral</th>
    </tr>
  </thead>
  <tbody>
    <?php if (!$rows): ?>
      <tr>
        <td colspan="5" style="text-align:center;color:#777">
          Aucune donn&eacute;e disponible
        </td>
      </tr>
    <?php else: ?>
      <?php foreach ($rows as $i => $r): ?>
        <tr>
          <td><?= $i + 1; ?></td>
          <td><?= htmlspecialchars($r['titre'], ENT_QUOTES, 'UTF-8'); ?></td>
          <td><?= (int)$r['partages']; ?></td>
          <td><?= (int)$r['leads']; ?></td>
          <td><strong><?= (int)$r['score']; ?></strong></td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </tbody>
</table>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout/layout.php';
