<?php
require_once __DIR__ . '/../core/database.php';
session_start();

if (empty($_SESSION['candidat_id'])) exit;

$pdo = Database::connect();
$cid = (int)$_SESSION['candidat_id'];

$stmt = $pdo->prepare("
  SELECT id, message, lu, created_at
  FROM notifications_candidats
  WHERE candidat_id = ?
  ORDER BY created_at DESC
");
$stmt->execute([$cid]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h1>🔔 Mes notifications</h1>

<?php if (!$notifications): ?>
  <p>Aucune notification pour le moment.</p>
<?php endif; ?>

<ul style="list-style:none;padding:0;">
<?php foreach ($notifications as $n): ?>
  <li style="
    padding:12px;
    margin-bottom:10px;
    border-radius:10px;
    background:<?= $n['lu'] ? '#f1f5f9' : '#ecfeff'; ?>;
  ">
    <div><?= htmlspecialchars($n['message']); ?></div>
    <small style="color:#64748b;">
      <?= date('d/m/Y H:i', strtotime($n['created_at'])); ?>
    </small>
  </li>
<?php endforeach; ?>
</ul>
