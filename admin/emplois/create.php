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
$pageTitle  = "Créer une offre d’emploi";
$activeMenu = "emplois_offres";

$pdo = Database::connect();

/*
 |--------------------------------------------------
 | ENTREPRISES (POUR SÉLECTION)
 |--------------------------------------------------
*/
$entreprises = $pdo->query("
  SELECT id, nom_legal
  FROM entreprises
  ORDER BY nom_legal
")->fetchAll(PDO::FETCH_ASSOC);

/*
 |--------------------------------------------------
 | TRAITEMENT FORMULAIRE
 |--------------------------------------------------
*/
$error = '';

$titre             = '';
$description       = '';
$missions          = '';
$profil_recherche  = '';
$lieu              = '';
$type_contrat      = '';
$niveau_experience = '';
$date_limite       = '';
$entreprise_id     = '';

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
  $entreprise_id     = (int)($_POST['entreprise_id'] ?? 0);

  if ($titre === '' || $description === '' || $entreprise_id <= 0) {
    $error = "Le titre, la description et l’entreprise sont obligatoires.";
  } else {

    $pdo->prepare("
      INSERT INTO offres_emploi (
        entreprise_id,
        titre,
        description,
        missions,
        profil_recherche,
        lieu,
        type_contrat,
        niveau_experience,
        date_limite,
        statut,
        cree_le
      ) VALUES (
        ?, ?, ?, ?, ?, ?, ?, ?, ?, 'soumise', NOW()
      )
    ")->execute([
      $entreprise_id,
      $titre,
      $description,
      $missions,
      $profil_recherche,
      $lieu,
      $type_contrat,
      $niveau_experience,
      $date_limite
    ]);

    header("Location: offres.php");
    exit;
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

  <h2>&#10133; Cr&eacute;er une offre d&rsquo;emploi</h2>

  <?php if ($error): ?>
    <div class="alert alert-danger">
      <?= e($error); ?>
    </div>
  <?php endif; ?>

  <form method="post" style="margin-top:16px">
    <?= csrf_field(); ?>

    <label class="label">Entreprise *</label>
    <select class="input" name="entreprise_id" required>
      <option value="">— S&eacute;lectionner —</option>
      <?php foreach ($entreprises as $e): ?>
        <option value="<?= (int)$e['id']; ?>"
          <?= $entreprise_id == $e['id'] ? 'selected' : ''; ?>>
          <?= e($e['nom_legal']); ?>
        </option>
      <?php endforeach; ?>
    </select>

    <label class="label">Titre *</label>
    <input class="input"
           type="text"
           name="titre"
           value="<?= e($titre); ?>"
           required>

    <label class="label">Description *</label>
    <textarea class="textarea"
              name="description"
              rows="5"
              required><?= e($description); ?></textarea>

    <label class="label">Missions</label>
    <textarea class="textarea"
              name="missions"
              rows="4"><?= e($missions); ?></textarea>

    <label class="label">Profil recherch&eacute;</label>
    <textarea class="textarea"
              name="profil_recherche"
              rows="4"><?= e($profil_recherche); ?></textarea>

    <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:12px">

      <div>
        <label class="label">Lieu</label>
        <input class="input"
               type="text"
               name="lieu"
               value="<?= e($lieu); ?>">
      </div>

      <div>
        <label class="label">Type de contrat</label>
        <input class="input"
               type="text"
               name="type_contrat"
               value="<?= e($type_contrat); ?>">
      </div>

    </div>

    <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:12px">

      <div>
        <label class="label">Niveau d&rsquo;exp&eacute;rience</label>
        <input class="input"
               type="text"
               name="niveau_experience"
               value="<?= e($niveau_experience); ?>">
      </div>

      <div>
        <label class="label">Date limite</label>
        <input class="input"
               type="date"
               name="date_limite"
               value="<?= e($date_limite); ?>">
      </div>

    </div>

    <div style="margin-top:18px;display:flex;gap:12px;flex-wrap:wrap">

      <button class="btn btn-primary" type="submit">
        &#128190; Cr&eacute;er l&rsquo;offre
      </button>

      <a class="btn btn-secondary" href="offres.php">
        &larr; Retour aux offres
      </a>

    </div>

  </form>

</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout/layout.php';
