<?php
declare(strict_types=1);

require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/database.php';

session_start();

if (empty($_SESSION['entreprise_id'])) {
  header("Location: /entreprise/login.php");
  exit;
}

if (($_SESSION['entreprise_statut'] ?? '') !== 'actif') {
  exit("Compte non actif.");
}

$pdo = Database::connect();
$eid = (int)$_SESSION['entreprise_id'];

$success = false;
$error   = '';

/* ============================
   TRAITEMENT FORMULAIRE
============================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $titre        = trim($_POST['titre'] ?? '');
  $typeContrat  = $_POST['type_contrat'] ?? '';
  $lieu         = trim($_POST['lieu'] ?? '');
  $description  = trim($_POST['description'] ?? '');

  if ($titre === '') {
    $error = "Le titre du poste est obligatoire.";
  } else {

    $stmt = $pdo->prepare("
      INSERT INTO offres_emploi
        (entreprise_id, titre, type_contrat, lieu, description, statut)
      VALUES
        (:eid, :titre, :type, :lieu, :description, 'brouillon')
    ");
    $stmt->execute([
      ':eid'         => $eid,
      ':titre'       => $titre,
      ':type'        => $typeContrat,
      ':lieu'        => $lieu ?: null,
      ':description' => $description ?: null,
    ]);

    header("Location: index.php");
    exit;
  }
}

/* ============================
   PARTIAL HEADER
============================ */
$pageTitle = "Nouvelle offre – IBIG EDUFORM";
require_once __DIR__ . '/../partials/header.php';
?>

<h1>&#10133; Nouvelle offre</h1>

<?php if ($error): ?>
  <p style="color:#b91c1c;font-weight:600;">
    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
  </p>
<?php endif; ?>

<form method="post" style="max-width:600px;display:grid;gap:12px;">

  <label>
    <strong>Titre du poste</strong><br>
    <input name="titre" required
           value="<?= htmlspecialchars($_POST['titre'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
           style="width:100%;padding:10px;">
  </label>

  <label>
    <strong>Type de contrat</strong><br>
    <select name="type_contrat">
      <?php
      $types = ['CDI','CDD','Stage','Mission','Freelance'];
      foreach ($types as $t):
      ?>
        <option value="<?= $t; ?>" <?= (($$_POST['type_contrat'] ?? '') === $t) ? 'selected' : ''; ?>>
          <?= $t; ?>
        </option>
      <?php endforeach; ?>
    </select>
  </label>

  <label>
    <strong>Lieu</strong><br>
    <input name="lieu"
           value="<?= htmlspecialchars($_POST['lieu'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
           placeholder="Ex : Abidjan"
           style="width:100%;padding:10px;">
  </label>

  <label>
    <strong>Description</strong><br>
    <textarea name="description" rows="5"
      style="width:100%;padding:10px;"><?= htmlspecialchars($_POST['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
  </label>

  <button type="submit"
          style="padding:12px 16px;font-weight:800;background:#f5a623;border:none;border-radius:10px;">
    &#128190; Enregistrer comme brouillon
  </button>

</form>

<p style="margin-top:18px;color:#64748b;">
  &#9888;&#65039;
  L&rsquo;offre sera enregistr&eacute;e comme <strong>brouillon</strong> et devra
  &ecirc;tre soumise &agrave; validation avant publication.
</p>

<p>
  <a href="index.php">&larr; Retour &agrave; mes offres</a>
</p>

<?php
/* ============================
   PARTIAL FOOTER
============================ */
require_once __DIR__ . '/../partials/footer.php';
