<?php
declare(strict_types=1);

/*
 |--------------------------------------------------
 | INIT ADMIN — PIPELINE INTERNE
 |--------------------------------------------------
*/
require_once __DIR__ . '/../_init.php';

/* 🔐 Middleware */
$mw = realpath(__DIR__ . '/../auth/middleware.php');
if (!$mw) {
  die("Middleware introuvable");
}
require_once $mw;

if (!class_exists('Middleware')) {
  die("Classe Middleware introuvable");
}

Middleware::requireAuth();

/*
 |--------------------------------------------------
 | CONFIG PAGE
 |--------------------------------------------------
*/
$pageTitle  = "Modifier une offre d’emploi";
$activeMenu = "emplois_offres";

$pdo = Database::connect();

/*
 |--------------------------------------------------
 | VALIDATION ID
 |--------------------------------------------------
*/
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  die("Offre invalide.");
}

/*
 |--------------------------------------------------
 | RÉCUPÉRATION OFFRE
 |--------------------------------------------------
*/
$stmt = $pdo->prepare("
  SELECT *
  FROM offres_emploi
  WHERE id = ?
  LIMIT 1
");
$stmt->execute([$id]);
$offre = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$offre) {
  die("Offre introuvable.");
}

/*
 |--------------------------------------------------
 | TRAITEMENT FORMULAIRE
 |--------------------------------------------------
*/
$success = false;
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  csrf_check();

  $titre             = trim($_POST['titre'] ?? '');
  $description       = trim($_POST['description'] ?? '');
  $missions          = trim($_POST['missions'] ?? '');
  $profil_recherche  = trim($_POST['profil_recherche'] ?? '');
  $lieu              = trim($_POST['lieu'] ?? '');
  $type_contrat      = trim($_POST['type_contrat'] ?? '');
  $niveau_experience = trim($_POST['niveau_experience'] ?? '');
  $date_limite       = $_POST['date_limite'] ?: null;

  if ($titre === '' || $description === '') {
    $error = "Le titre et la description sont obligatoires.";
  } else {

    $pdo->prepare("
      UPDATE offres_emploi
      SET
        titre = ?,
        description = ?,
        missions = ?,
        profil_recherche = ?,
        lieu = ?,
        type_contrat = ?,
        niveau_experience = ?,
        date_limite = ?
      WHERE id = ?
    ")->execute([
      $titre,
      $description,
      $missions,
      $profil_recherche,
      $lieu,
      $type_contrat,
      $niveau_experience,
      $date_limite,
      $id
    ]);

    $success = true;

    /* Recharger les données */
    $stmt->execute([$id]);
    $offre = $stmt->fetch(PDO::FETCH_ASSOC);
  }
}

/*
 |--------------------------------------------------
 | CONTENU (BUFFER INTERNE)
 |--------------------------------------------------
*/
ob_start();
?>

<div class="card">

  <h2>&#9998; Modifier l’offre</h2>

  <?php if ($success): ?>
    <div class="alert alert-success">
      L’offre a été mise à jour avec succès.
    </div>
  <?php endif; ?>

  <?php if ($error): ?>
    <div class="alert alert-danger">
      <?= e($error); ?>
    </div>
  <?php endif; ?>

  <form method="post" style="margin-top:16px">
    <?= csrf_field(); ?>

    <label class="label">Titre</label>
    <input class="input"
           type="text"
           name="titre"
           value="<?= e($offre['titre']); ?>"
           required>

    <label class="label">Description</label>
    <textarea class="textarea"
              name="description"
              rows="5"
              required><?= e($offre['description']); ?></textarea>

    <label class="label">Missions</label>
    <textarea class="textarea"
              name="missions"
              rows="4"><?= e($offre['missions']); ?></textarea>

    <label class="label">Profil recherché</label>
    <textarea class="textarea"
              name="profil_recherche"
              rows="4"><?= e($offre['profil_recherche']); ?></textarea>

    <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:12px">

      <div>
        <label class="label">Lieu</label>
        <input class="input"
               type="text"
               name="lieu"
               value="<?= e($offre['lieu']); ?>">
      </div>

      <div>
        <label class="label">Type de contrat</label>
        <input class="input"
               type="text"
               name="type_contrat"
               value="<?= e($offre['type_contrat']); ?>">
      </div>

    </div>

    <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:12px">

      <div>
        <label class="label">Niveau d’expérience</label>
        <input class="input"
               type="text"
               name="niveau_experience"
               value="<?= e($offre['niveau_experience']); ?>">
      </div>

      <div>
        <label class="label">Date limite</label>
        <input class="input"
               type="date"
               name="date_limite"
               value="<?= e($offre['date_limite']); ?>">
      </div>

    </div>

    <div style="margin-top:18px;display:flex;gap:12px;flex-wrap:wrap">

      <button class="btn btn-primary" type="submit">
        💾 Enregistrer
      </button>

      <a class="btn btn-secondary" href="offres.php">
        ← Retour aux offres
      </a>

      <a class="btn btn-outline" href="actions.php?id=<?= (int)$offre['id']; ?>">
        Examiner l’offre
      </a>

    </div>

  </form>

</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout/layout.php';
