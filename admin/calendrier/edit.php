<?php
require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../auth/middleware.php';

Middleware::requireAuth();

$id = (int)($_GET['id'] ?? 0);
$pdo = Database::connect();

$stmt = $pdo->prepare("SELECT * FROM calendrier_formations WHERE id=?");
$stmt->execute([$id]);
$s = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$s) redirect('index.php');

$formations = $pdo->query("SELECT id, titre FROM formations WHERE statut='active'")->fetchAll(PDO::FETCH_ASSOC);

if($_SERVER['REQUEST_METHOD']==='POST'){
  csrf_check();
  $pdo->prepare("
    UPDATE calendrier_formations
    SET formation_id=?, date_debut=?, date_fin=?, duree=?, mode=?, statut=?
    WHERE id=?
  ")->execute([
    $_POST['formation_id'],
    $_POST['date_debut'],
    $_POST['date_fin'] ?: null,
    $_POST['duree'],
    $_POST['mode'],
    $_POST['statut'],
    $id
  ]);
  redirect('index.php');
}

$pageTitle="Modifier session";
$activeMenu="calendrier";
ob_start();
?>
<div class="card">
<h2>✏️ Modifier session</h2>

<form method="post">
  <?= csrf_field(); ?>
  <label>Formation</label>
  <select name="formation_id">
    <?php foreach($formations as $f): ?>
      <option value="<?= $f['id']; ?>" <?= $f['id']==$s['formation_id']?'selected':''; ?>>
        <?= e($f['titre']); ?>
      </option>
    <?php endforeach; ?>
  </select>

  <label>Date de début</label>
  <input type="date" name="date_debut" value="<?= $s['date_debut']; ?>" required>

  <label>Date de fin</label>
  <input type="date" name="date_fin" value="<?= $s['date_fin']; ?>">

  <label>Durée</label>
  <input name="duree" value="<?= e($s['duree']); ?>">

  <label>Mode</label>
  <select name="mode">
    <option value="presentiel" <?= $s['mode']=='presentiel'?'selected':''; ?>>Présentiel</option>
    <option value="en_ligne" <?= $s['mode']=='en_ligne'?'selected':''; ?>>En ligne</option>
    <option value="hybride" <?= $s['mode']=='hybride'?'selected':''; ?>>Hybride</option>
  </select>

  <label>Statut</label>
  <select name="statut">
    <option value="ouvert" <?= $s['statut']=='ouvert'?'selected':''; ?>>Ouvert</option>
    <option value="ferme" <?= $s['statut']=='ferme'?'selected':''; ?>>Fermé</option>
    <option value="termine" <?= $s['statut']=='termine'?'selected':''; ?>>Terminé</option>
  </select>

  <button class="btn">Mettre à jour</button>
</form>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout/layout.php';
