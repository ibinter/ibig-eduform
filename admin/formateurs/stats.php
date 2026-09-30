<?php
require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../auth/middleware_rh.php';

MiddlewareRH::requireRH();
$pdo = Database::connect();

// Par domaine
$byDomain = $pdo->query("
  SELECT domaine, COUNT(*) total
  FROM candidatures_formateurs
  GROUP BY domaine
")->fetchAll(PDO::FETCH_ASSOC);

// Par statut
$byStatus = $pdo->query("
  SELECT statut, COUNT(*) total
  FROM candidatures_formateurs
  GROUP BY statut
")->fetchAll(PDO::FETCH_ASSOC);
?>

<h1>📊 Statistiques – Formateurs</h1>

<h3>Par domaine</h3>
<ul>
<?php foreach ($byDomain as $d): ?>
  <li><?= htmlspecialchars($d['domaine']); ?> : <?= $d['total']; ?></li>
<?php endforeach; ?>
</ul>

<h3>Par statut</h3>
<ul>
<?php foreach ($byStatus as $s): ?>
  <li><?= ucfirst($s['statut']); ?> : <?= $s['total']; ?></li>
<?php endforeach; ?>
</ul>
