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
   PAGE CONFIG
===================================================== */
$pageTitle  = 'Intentions en cours';
$activeMenu = 'stats_intent';

$pdo = Database::connect();

/* =====================================================
   INTENTIONS ACTIVES (15 dernières minutes)
===================================================== */
$rows = $pdo->query("
  SELECT
    f.id                    AS formation_id,
    f.titre                 AS formation,
    COUNT(i.session_key)    AS visiteurs,
    SUM(i.is_hot = 1)       AS visiteurs_chauds,
    ROUND(AVG(i.score),1)   AS score_moyen,
    MAX(i.last_action)      AS derniere_action,
    MAX(i.last_seen)        AS derniere_activite
  FROM intent_sessions i
  LEFT JOIN formations f ON f.id = i.formation_id
  WHERE i.last_seen >= (NOW() - INTERVAL 15 MINUTE)
  GROUP BY f.id
  ORDER BY visiteurs_chauds DESC, score_moyen DESC
")->fetchAll(PDO::FETCH_ASSOC);

ob_start();
?>

<h2>&#129504; Intentions visiteurs en cours</h2>
<p class="muted" style="margin-bottom:16px">
  Données des 15 dernières minutes — intentions réelles détectées
</p>

<?php if (empty($rows)): ?>
  <div class="card muted">
    Aucun visiteur actif pour le moment.
  </div>
<?php else: ?>

<table class="table">
  <thead>
    <tr>
      <th>Formation</th>
      <th>Visiteurs</th>
      <th>Chauds</th>
      <th>Score moyen</th>
      <th>Dernière action</th>
      <th>Dernière activité</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td>
          <strong><?= e($r['formation'] ?? '—'); ?></strong>
        </td>

        <td>
          <?= (int)$r['visiteurs']; ?>
        </td>

        <td>
          <?php if ((int)$r['visiteurs_chauds'] > 0): ?>
            <span class="pill ok">
              <?= (int)$r['visiteurs_chauds']; ?> chaud(s)
            </span>
          <?php else: ?>
            <span class="muted">0</span>
          <?php endif; ?>
        </td>

        <td>
          <strong><?= $r['score_moyen'] ?? '0'; ?></strong>
        </td>

        <td class="muted">
          <?= e($r['derniere_action'] ?? '—'); ?>
        </td>

        <td class="muted">
          <?= e(date('H:i:s', strtotime((string)$r['derniere_activite']))); ?>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout/layout.php';
