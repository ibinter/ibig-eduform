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
$activeMenu = 'stats_conv';
$pageTitle  = 'Conversion Partage &rarr; Pr&eacute;inscription';

$pdo = Database::connect();

/* =====================================================
   DONNÉES
===================================================== */
$data = $pdo->query("
  SELECT
    f.titre,
    COUNT(DISTINCT s.id) partages,
    COUNT(DISTINCT p.id) leads,
    ROUND(
      COUNT(DISTINCT p.id) /
      NULLIF(COUNT(DISTINCT s.id),0) * 100,
      2
    ) taux
  FROM formations f
  LEFT JOIN site_share_events s ON s.formation_id = f.id
  LEFT JOIN preinscriptions p ON p.formation_id = f.id
  GROUP BY f.id
  ORDER BY taux DESC
")->fetchAll(PDO::FETCH_ASSOC);

/* =====================================================
   RENDER
===================================================== */
ob_start();
?>

<h2>&#128257; Conversion Partage &rarr; Pr&eacute;inscription</h2>

<table class="table">
  <thead>
    <tr>
      <th>Formation</th>
      <th>Partages</th>
      <th>Leads</th>
      <th>Taux (%)</th>
    </tr>
  </thead>
  <tbody>
    <?php if (!$data): ?>
      <tr>
        <td colspan="4" style="text-align:center;color:#777">
          Aucune donn&eacute;e disponible
        </td>
      </tr>
    <?php else: ?>
      <?php foreach ($data as $r): ?>
        <tr>
          <td><?= htmlspecialchars($r['titre'], ENT_QUOTES, 'UTF-8'); ?></td>
          <td><?= (int)$r['partages']; ?></td>
          <td><?= (int)$r['leads']; ?></td>
          <td><strong><?= $r['taux']; ?>%</strong></td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </tbody>
</table>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout/layout.php';
