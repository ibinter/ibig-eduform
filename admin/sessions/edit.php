<?php
declare(strict_types=1);

require_once __DIR__ . '/../../core/bootstrap.php';
require_once __DIR__ . '/../auth/middleware.php';
Middleware::requireAuth();

$pageTitle  = "Modifier session";
$activeMenu = "formations";

$pdo   = Database::connect();
$error = '';

$id          = (int)($_GET['id'] ?? 0);
$formationId = (int)($_GET['formation_id'] ?? 0);
if ($id <= 0 || $formationId <= 0) redirect('/admin/formations/index.php');

$stmt = $pdo->prepare("SELECT * FROM calendrier_formations WHERE id=? LIMIT 1");
$stmt->execute([$id]);
$s = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$s) redirect('/admin/formations/index.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  csrf_check();

  $dateDebut = $_POST['date_debut'] ?? '';
  $dateFin   = $_POST['date_fin'] ?? null;
  $duree     = trim($_POST['duree'] ?? '');
  $mode      = $_POST['mode'] ?? 'presentiel';
  $statut    = $_POST['statut'] ?? 'a_venir';

  if ($dateDebut === '' || $duree === '') {
    $error = "Date de d&eacute;but et dur&eacute;e obligatoires.";
  } else {

    $stmt = $pdo->prepare("
      UPDATE calendrier_formations SET
        date_debut = ?,
        date_fin   = ?,
        duree      = ?,
        mode       = ?,
        statut     = ?
      WHERE id = ?
    ");
    $stmt->execute([
      $dateDebut,
      $dateFin ?: null,
      $duree,
      $mode,
      $statut,
      $id
    ]);

    redirect("index.php?formation_id=".$formationId);
  }
}

ob_start();
?>

<div class="card">
  <h2>&#9998; Modifier la session</h2>

  <?php if ($error): ?>
    <div class="pill wait"><?= e($error); ?></div>
  <?php endif; ?>

  <form method="post">
    <?= csrf_field(); ?>

    <label>Date de d&eacute;but *</label>
    <input type="date" name="date_debut"
           value="<?= e($s['date_debut']); ?>" required>

    <label>Date de fin</label>
    <input type="date" name="date_fin"
           value="<?= e($s['date_fin']); ?>">

    <label>Dur&eacute;e *</label>
    <input name="duree" value="<?= e($s['duree']); ?>" required>

    <label>Mode</label>
    <select name="mode">
      <option value="presentiel" <?= $s['mode']==='presentiel'?'selected':''; ?>>Pr&eacute;sentiel</option>
      <option value="en_ligne" <?= $s['mode']==='en_ligne'?'selected':''; ?>>En ligne</option>
      <option value="hybride" <?= $s['mode']==='hybride'?'selected':''; ?>>Hybride</option>
    </select>

    <label>Statut</label>
    <select name="statut">
      <option value="a_venir" <?= $s['statut']==='a_venir'?'selected':''; ?>>&Agrave; venir</option>
      <option value="ouvert" <?= $s['statut']==='ouvert'?'selected':''; ?>>Ouvert</option>
      <option value="ferme" <?= $s['statut']==='ferme'?'selected':''; ?>>Ferm&eacute;</option>
    </select>

    <div style="margin-top:20px">
      <button class="btn btn-primary">
        &#128190; Mettre &agrave; jour
      </button>
      <a href="index.php?formation_id=<?= (int)$formationId; ?>"
         class="btn btn-secondary">
        &#8592; Retour
      </a>
    </div>

  </form>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout/layout.php';
