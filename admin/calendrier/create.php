<?php
require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../auth/middleware.php';

Middleware::requireAuth();

$pageTitle  = "Nouvelle session";
$activeMenu = "calendrier";

$pdo = Database::connect();
$formations = $pdo->query("SELECT id, titre FROM formations WHERE statut='active'")->fetchAll(PDO::FETCH_ASSOC);

if($_SERVER['REQUEST_METHOD']==='POST'){
  csrf_check();
  $pdo->prepare("
    INSERT INTO calendrier_formations (formation_id,date_debut,date_fin,duree,mode,statut)
    VALUES (?,?,?,?,?,?)
  ")->execute([
    $_POST['formation_id'],
    $_POST['date_debut'],
    $_POST['date_fin'] ?: null,
    $_POST['duree'],
    $_POST['mode'],
    $_POST['statut']
  ]);
  redirect('index.php');
}

ob_start();
?>
<div class="card">
<h2>➕ Nouvelle session</h2>

<form method="post">
  <?= csrf_field(); ?>
  <label>Formation</label>
  <select name="formation_id" required>
    <option value="">— Choisir —</option>
    <?php foreach($formations as $f): ?>
      <option value="<?= $f['id']; ?>"><?= e($f['titre']); ?></option>
    <?php endforeach; ?>
  </select>

  <label>Date de début</label>
  <input type="date" name="date_debut" required>

  <label>Date de fin</label>
  <input type="date" name="date_fin">

  <label>Durée</label>
  <input name="duree" placeholder="Ex : 2 mois / 5 jours">

  <label>Mode</label>
  <select name="mode">
    <option value="presentiel">Présentiel</option>
    <option value="en_ligne">En ligne</option>
    <option value="hybride">Hybride</option>
  </select>

  <label>Statut</label>
  <select name="statut">
    <option value="ouvert">Ouvert</option>
    <option value="ferme">Fermé</option>
    <option value="termine">Terminé</option>
  </select>

  <button class="btn">Enregistrer</button>
</form>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout/layout.php';
