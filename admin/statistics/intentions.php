<?php
Middleware::requireAuth();
$pdo = Database::connect();

$rows = $pdo->query("
  SELECT
    i.session_key,
    f.titre,
    MAX(i.created_at) last_action,
    SUM(
      CASE
        WHEN action LIKE 'time%' THEN 2
        WHEN action='scroll' THEN 1
        WHEN action='cta_click' THEN 3
        WHEN action='whatsapp_click' THEN 5
        ELSE 0
      END
    ) score
  FROM site_intents i
  LEFT JOIN formations f ON f.id = i.formation_id
  GROUP BY i.session_key
  HAVING score >= 4
  ORDER BY score DESC
  LIMIT 30
")->fetchAll();
?>

<h2>&#128293; Intentions en cours</h2>

<table class="table">
  <thead>
    <tr>
      <th>Formation</th>
      <th>Score</th>
      <th>Dernière action</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= htmlspecialchars($r['titre'] ?? '—'); ?></td>
        <td><strong><?= $r['score']; ?></strong></td>
        <td><?= $r['last_action']; ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
