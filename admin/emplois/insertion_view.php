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
$pageTitle  = "Suivi d’insertion";
$activeMenu = "emplois_insertions";

$pdo = Database::connect();

/*
 |--------------------------------------------------
 | VALIDATION ID
 |--------------------------------------------------
*/
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  die("Insertion invalide.");
}

/*
 |--------------------------------------------------
 | RÉCUPÉRATION INSERTION
 |--------------------------------------------------
*/
$stmt = $pdo->prepare("
  SELECT
    i.*,
    c.nom AS candidat,
    o.titre AS offre,
    e.nom_legal AS entreprise
  FROM insertions i
  JOIN candidats c ON c.id = i.candidat_id
  JOIN offres_emploi o ON o.id = i.offre_id
  JOIN entreprises e ON e.id = i.entreprise_id
  WHERE i.id = ?
  LIMIT 1
");
$stmt->execute([$id]);
$i = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$i) {
  die("Insertion introuvable.");
}

/*
 |--------------------------------------------------
 | TRAITEMENT MISE À JOUR
 |--------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  csrf_check();

  $statut     = $_POST['statut'] ?? $i['statut'];
  $dateDebut  = $_POST['date_debut'] ?: null;
  $note_ib    = trim($_POST['note_ib'] ?? '');

  $pdo->prepare("
    UPDATE insertions
    SET
      statut = ?,
      date_debut = ?,
      note_ib = ?
    WHERE id = ?
  ")->execute([
    $statut,
    $dateDebut,
    $note_ib,
    $id
  ]);

  header("Location: insertions.php");
  exit;
}

/*
 |--------------------------------------------------
 | CONTENU (BUFFER INTERNE)
 |--------------------------------------------------
*/
ob_start();
?>

<div class="card">

  <h2>&#127919; Suivi d&rsquo;insertion</h2>

  <p>
    <strong>Candidat :</strong> <?= e($i['candidat']); ?><br>
    <strong>Offre :</strong> <?= e($i['offre']); ?><br>
    <strong>Entreprise :</strong> <?= e($i['entreprise']); ?>
  </p>

  <p>
    <strong>Statut actuel :</strong>
    <span class="pill <?= in_array($i['statut'], ['place','termine']) ? 'ok' : 'wait'; ?>">
      <?= strtoupper(e($i['statut'])); ?>
    </span>
  </p>

  <hr>

  <form method="post" style="max-width:520px">
    <?= csrf_field(); ?>

    <label class="label">Statut</label>
    <select class="input" name="statut">
      <?php
      $stats = ['propose','entretien','place','en_suivi','termine','rupture'];
      foreach ($stats as $s):
      ?>
        <option value="<?= $s; ?>" <?= $i['statut']===$s?'selected':''; ?>>
          <?= strtoupper($s); ?>
        </option>
      <?php endforeach; ?>
    </select>

    <label class="label">Date de d&eacute;but</label>
    <input class="input"
           type="date"
           name="date_debut"
           value="<?= e($i['date_debut']); ?>">

    <label class="label">Note de suivi IBIG</label>
    <textarea class="textarea"
              name="note_ib"
              rows="4"><?= e($i['note_ib']); ?></textarea>

    <div style="margin-top:18px;display:flex;gap:12px">

      <button class="btn btn-primary" type="submit">
        &#128190; Mettre &agrave; jour
      </button>

      <a class="btn btn-secondary" href="insertions.php">
        &larr; Retour
      </a>

    </div>

  </form>

</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout/layout.php';
