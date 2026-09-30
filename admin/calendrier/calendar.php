<?php
require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../auth/middleware.php';

Middleware::requireAuth();

$pageTitle = "Calendrier mensuel";
$activeMenu = "calendrier";

$pdo = Database::connect();

$month = $_GET['month'] ?? date('Y-m');

$stmt = $pdo->prepare("
  SELECT c.*, f.titre
  FROM calendrier_formations c
  INNER JOIN formations f ON f.id = c.formation_id
  WHERE DATE_FORMAT(c.date_debut,'%Y-%m') = ?
  ORDER BY c.date_debut
");
$stmt->execute([$month]);
$sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$byDate = [];
foreach ($sessions as $s) {
    $byDate[$s['date_debut']][] = $s;
}

ob_start();
?>

<h2>Calendrier mensuel — <?= e($month); ?></h2>

<?php foreach ($byDate as $date => $list): ?>
  <div class="card" style="margin-bottom:10px">
    <strong><?= date('d/m/Y', strtotime($date)); ?></strong>
    <ul>
      <?php foreach ($list as $s): ?>
        <li>
          <?= e($s['titre']); ?>
          (<?= e($s['mode']); ?>)
          <a href="edit.php?id=<?= $s['id']; ?>">✎</a>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endforeach; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout/layout.php';
