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
$pageTitle  = "Nouvelle insertion professionnelle";
$activeMenu = "emplois_insertions";

$pdo = Database::connect();

/*
 |--------------------------------------------------
 | DONNÉES (CANDIDATS & OFFRES)
 |--------------------------------------------------
*/
$candidats = $pdo->query("
  SELECT id, nom
  FROM candidats
  ORDER BY nom
")->fetchAll(PDO::FETCH_ASSOC);

$offres = $pdo->query("
  SELECT 
    o.id,
    o.titre,
    e.nom_legal
  FROM offres_emploi o
  JOIN entreprises e ON e.id = o.entreprise_id
  WHERE o.statut = 'approuvee'
  ORDER BY o.titre
")->fetchAll(PDO::FETCH_ASSOC);

/*
 |--------------------------------------------------
 | TRAITEMENT FORMULAIRE
 |--------------------------------------------------
*/
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  csrf_check();

  $candidat_id = (int)($_POST['candidat_id'] ?? 0);
  $offre_id    = (int)($_POST['offre_id'] ?? 0);

  if ($candidat_id <= 0 || $offre_id <= 0) {
    $error = "Veuillez s&eacute;lectionner un candidat et une offre.";
  } else {

    $stmt = $pdo->prepare("
      INSERT INTO insertions
        (candidat_id, offre_id, entreprise_id, statut)
      SELECT
        ?, o.id, o.entreprise_id, 'propose'
      FROM offres_emploi o
      WHERE o.id = ?
      LIMIT 1
    ");
    $stmt->execute([$candidat_id, $offre_id]);

    header("Location: insertions.php");
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

  <h2>&#10133; Nouvelle insertion professionnelle</h2>

  <?php if ($error): ?>
    <div class="alert alert-danger">
      <?= e($error); ?>
    </div>
  <?php endif; ?>

  <form method="post" style="max-width:480px;margin-top:16px">
    <?= csrf_field(); ?>

    <label class="label">Candidat</label>
    <select class="input" name="candidat_id" required>
      <option value="">— S&eacute;lectionner —</option>
      <?php foreach ($candidats as $c): ?>
        <option value="<?= (int)$c['id']; ?>">
          <?= e($c['nom']); ?>
        </option>
      <?php endforeach; ?>
    </select>

    <label class="label">Offre approuv&eacute;e</label>
    <select class="input" name="offre_id" required>
      <option value="">— S&eacute;lectionner —</option>
      <?php foreach ($offres as $o): ?>
        <option value="<?= (int)$o['id']; ?>">
          <?= e($o['titre']); ?> — <?= e($o['nom_legal']); ?>
        </option>
      <?php endforeach; ?>
    </select>

    <div style="margin-top:18px;display:flex;gap:12px">

      <button class="btn btn-primary" type="submit">
        &#128190; Cr&eacute;er l&rsquo;insertion
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
