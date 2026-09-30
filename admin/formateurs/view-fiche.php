<?php
declare(strict_types=1);

require_once __DIR__ . '/../_init.php';

$mw = realpath(__DIR__ . '/../auth/middleware.php');
if (!$mw) { die("Middleware introuvable"); }
require_once $mw;
Middleware::requireAuth();

$pageTitle  = "Fiche formateur";
$activeMenu = "formateurs";

$pdo = Database::connect();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) redirect('index.php');

$stmt = $pdo->prepare("
  SELECT *
  FROM formateurs
  WHERE candidature_id = ?
  LIMIT 1
");
$stmt->execute([$id]);
$f = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$f) redirect('index.php');

ob_start();
?>

<div class="card">
  <div style="display:flex;justify-content:space-between;align-items:center">
    <h2>Fiche formateur</h2>
    <a class="btn btn-outline" href="index.php">Retour</a>
  </div>

  <div style="margin-top:16px;display:grid;grid-template-columns:1fr 1fr;gap:16px">
    <div><strong>Nom :</strong><br><?= e($f['nom']); ?></div>
    <div><strong>Domaine :</strong><br><?= e($f['domaine']); ?></div>
    <div><strong>Email :</strong><br><?= e($f['email'] ?? ''); ?></div>
    <div><strong>T&eacute;l&eacute;phone :</strong><br><?= e($f['telephone'] ?? ''); ?></div>
    <div><strong>Statut :</strong><br><?= e($f['statut']); ?></div>
    <div><strong>Date :</strong><br><?= e(date('d/m/Y H:i', strtotime((string)$f['created_at']))); ?></div>
  </div>

  <?php if (!empty($f['experience'])): ?>
    <div style="margin-top:16px">
      <strong>Exp&eacute;rience :</strong>
      <div class="box" style="margin-top:6px"><?= nl2br(e($f['experience'])); ?></div>
    </div>
  <?php endif; ?>

  <?php if (!empty($f['bio'])): ?>
    <div style="margin-top:16px">
      <strong>Bio :</strong>
      <div class="box" style="margin-top:6px"><?= nl2br(e($f['bio'])); ?></div>
    </div>
  <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout/layout.php';
