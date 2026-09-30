<?php
declare(strict_types=1);
require_once __DIR__ . '/../_init.php';

$pageTitle  = "Nouvelle session";
$activeMenu = "formations";

$pdo   = Database::connect();
$error = '';

$formationId = (int)($_GET['formation_id'] ?? 0);
if ($formationId <= 0) redirect('/admin/formations/index.php');

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
      INSERT INTO calendrier_formations
        (formation_id, date_debut, date_fin, duree, mode, statut)
      VALUES (?,?,?,?,?,?)
    ");
    $stmt->execute([
      $formationId,
      $dateDebut,
      $dateFin ?: null,
      $duree,
      $mode,
      $statut
    ]);

    redirect("index.php?formation_id=".$formationId);
  }
}

ob_start();
?>

<div class="card">
  <h2>&#10133; Nouvelle session</h2>

  <?php if ($error): ?>
    <div class="pill wait"><?= e($error); ?></div>
  <?php endif; ?>

  <form method="post">
    <?= csrf_field(); ?>

    <label>Date de d&eacute;but *</label>
    <input type="date" name="date_debut" required>

    <label>Date de fin</label>
    <input type="date" name="date_fin">

    <label>Dur&eacute;e *</label>
    <input name="duree" placeholder="Ex : 2 mois / 48 heures" required>

    <label>Mode</label>
    <select name="mode">
      <option value="presentiel">Pr&eacute;sentiel</option>
      <option value="en_ligne">En ligne</option>
      <option value="hybride">Hybride</option>
    </select>

    <label>Statut</label>
    <select name="statut">
      <option value="a_venir">&Agrave; venir</option>
      <option value="ouvert">Ouvert</option>
      <option value="ferme">Ferm&eacute;</option>
    </select>

    <div style="margin-top:20px">
      <button class="btn btn-primary">
        &#128190; Enregistrer
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
