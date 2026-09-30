<?php
declare(strict_types=1);

require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/database.php';

session_start();

if (empty($_SESSION['entreprise_id'])) {
  header("Location: /entreprise/login.php");
  exit;
}

$pdo = Database::connect();
$eid = (int)$_SESSION['entreprise_id'];

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  exit("Offre invalide.");
}

/* ============================
   RÉCUPÉRATION OFFRE
============================ */
$stmt = $pdo->prepare("
  SELECT *
  FROM offres_emploi
  WHERE id = ?
    AND entreprise_id = ?
  LIMIT 1
");
$stmt->execute([$id, $eid]);
$offre = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$offre) {
  exit("Offre introuvable.");
}

/* ============================
   BLOQUAGE SI PAS BROUILLON
============================ */
if ($offre['statut'] !== 'brouillon') {
  exit("Cette offre n’est plus modifiable.");
}

$success = false;

/* ============================
   TRAITEMENT FORMULAIRE
============================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  // Enregistrer le brouillon
  if (isset($_POST['save'])) {

    $stmt = $pdo->prepare("
      UPDATE offres_emploi SET
        titre = :titre,
        type_contrat = :type,
        lieu = :lieu,
        description = :description,
        missions = :missions,
        profil_recherche = :profil,
        niveau_experience = :niveau,
        date_limite = :date
      WHERE id = :id
        AND entreprise_id = :eid
        AND statut = 'brouillon'
    ");

    $stmt->execute([
      ':titre'   => trim($_POST['titre']),
      ':type'    => $_POST['type_contrat'],
      ':lieu'    => trim($_POST['lieu']),
      ':description' => trim($_POST['description']),
      ':missions'    => trim($_POST['missions']),
      ':profil'      => trim($_POST['profil_recherche']),
      ':niveau'      => trim($_POST['niveau_experience']),
      ':date'        => $_POST['date_limite'] ?: null,
      ':id'          => $id,
      ':eid'         => $eid,
    ]);

    $success = true;
  }

  // Soumettre à validation IBIG
  if (isset($_POST['submit'])) {

    $stmt = $pdo->prepare("
      UPDATE offres_emploi SET
        statut = 'soumise'
      WHERE id = ?
        AND entreprise_id = ?
        AND statut = 'brouillon'
    ");
    $stmt->execute([$id, $eid]);

    header("Location: index.php");
    exit;
  }
}

/* ============================
   PARTIAL HEADER
============================ */
$pageTitle = "Modifier l’offre – IBIG EDUFORM";
require_once __DIR__ . '/../partials/header.php';
?>

<h1>&#9998;&#65039; Modifier l&rsquo;offre</h1>

<p>
  <strong>Statut :</strong>
  <?= htmlspecialchars($offre['statut'], ENT_QUOTES, 'UTF-8'); ?>
</p>

<?php if ($success): ?>
  <div style="background:#ecfdf5;padding:12px;border-radius:10px;color:#065f46;">
    &#10004; Brouillon enregistr&eacute;.
  </div>
<?php endif; ?>

<form method="post" style="max-width:700px;display:grid;gap:12px;">

  <label>
    <strong>Titre du poste</strong><br>
    <input name="titre" required
           value="<?= htmlspecialchars($offre['titre'], ENT_QUOTES, 'UTF-8'); ?>"
           style="width:100%;padding:10px;">
  </label>

  <label>
    <strong>Type de contrat</strong><br>
    <select name="type_contrat">
      <?php
      $types = ['CDI','CDD','Stage','Mission','Freelance'];
      foreach ($types as $t):
      ?>
        <option value="<?= $t; ?>" <?= $offre['type_contrat']===$t?'selected':''; ?>>
          <?= $t; ?>
        </option>
      <?php endforeach; ?>
    </select>
  </label>

  <label>
    <strong>Lieu</strong><br>
    <input name="lieu"
           value="<?= htmlspecialchars($offre['lieu'], ENT_QUOTES, 'UTF-8'); ?>"
           style="width:100%;padding:10px;">
  </label>

  <label>
    <strong>Description g&eacute;n&eacute;rale</strong><br>
    <textarea name="description" rows="5"
      style="width:100%;padding:10px;"><?= htmlspecialchars($offre['description'], ENT_QUOTES, 'UTF-8'); ?></textarea>
  </label>

  <label>
    <strong>Missions principales</strong><br>
    <textarea name="missions" rows="4"
      style="width:100%;padding:10px;"><?= htmlspecialchars($offre['missions'], ENT_QUOTES, 'UTF-8'); ?></textarea>
  </label>

  <label>
    <strong>Profil recherch&eacute;</strong><br>
    <textarea name="profil_recherche" rows="4"
      style="width:100%;padding:10px;"><?= htmlspecialchars($offre['profil_recherche'], ENT_QUOTES, 'UTF-8'); ?></textarea>
  </label>

  <label>
    <strong>Niveau d&rsquo;exp&eacute;rience</strong><br>
    <input name="niveau_experience"
           value="<?= htmlspecialchars($offre['niveau_experience'], ENT_QUOTES, 'UTF-8'); ?>"
           placeholder="Ex : 2 &agrave; 5 ans"
           style="width:100%;padding:10px;">
  </label>

  <label>
    <strong>Date limite de candidature</strong><br>
    <input type="date" name="date_limite"
           value="<?= htmlspecialchars($offre['date_limite'], ENT_QUOTES, 'UTF-8'); ?>">
  </label>

  <div style="display:flex;gap:12px;margin-top:10px;">
    <button type="submit" name="save"
            style="padding:12px 16px;font-weight:700;">
      &#128190; Enregistrer le brouillon
    </button>

    <button type="submit" name="submit"
            onclick="return confirm('Soumettre cette offre à validation IBIG EDUFORM ?');"
            style="padding:12px 16px;font-weight:900;background:#f5a623;">
      &#128640; Soumettre &agrave; validation
    </button>
  </div>

</form>

<p style="margin-top:18px;color:#92400e;">
  &#9888;&#65039;
  Une fois soumise, l&rsquo;offre ne sera plus modifiable sans l&rsquo;intervention d&rsquo;IBIG EDUFORM.
</p>

<p>
  <a href="index.php">&larr; Retour &agrave; mes offres</a>
</p>

<?php
/* ============================
   PARTIAL FOOTER
============================ */
require_once __DIR__ . '/../partials/footer.php';
