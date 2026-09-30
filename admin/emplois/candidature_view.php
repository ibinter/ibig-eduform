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
$pageTitle  = "D&eacute;tail candidature";
$activeMenu = "emplois_candidatures";

$pdo = Database::connect();

/*
 |--------------------------------------------------
 | VALIDATION ID
 |--------------------------------------------------
*/
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  die("Candidature invalide.");
}

/*
 |--------------------------------------------------
 | RÉCUPÉRATION CANDIDATURE
 |--------------------------------------------------
*/
$stmt = $pdo->prepare("
  SELECT 
    c.*,
    o.titre AS offre,
    e.nom_legal AS entreprise
  FROM candidatures c
  JOIN offres_emploi o ON o.id = c.offre_id
  JOIN entreprises e ON e.id = o.entreprise_id
  WHERE c.id = ?
  LIMIT 1
");
$stmt->execute([$id]);
$c = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$c) {
  die("Candidature introuvable.");
}

/*
 |--------------------------------------------------
 | ACTION ADMIN — MAJ STATUT
 |--------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  csrf_check();

  if (!empty($_POST['statut'])) {
    $pdo->prepare("
      UPDATE candidatures
      SET statut = ?
      WHERE id = ?
    ")->execute([
      $_POST['statut'],
      $id
    ]);
  }

  header("Location: candidatures.php");
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

  <h2>&#128221; D&eacute;tail de la candidature</h2>

  <h3 style="margin-top:10px">
    <?= e($c['nom']); ?>
  </h3>

  <p>
    <strong>Email :</strong> <?= e($c['email']); ?><br>
    <strong>T&eacute;l&eacute;phone :</strong> <?= e($c['telephone']); ?>
  </p>

  <p>
    <strong>Offre :</strong> <?= e($c['offre']); ?><br>
    <strong>Entreprise :</strong> <?= e($c['entreprise']); ?>
  </p>

  <p>
    <strong>Statut actuel :</strong>
    <span class="pill <?= $c['statut']==='retenu'?'ok':'wait'; ?>">
      <?= e($c['statut']); ?>
    </span>
  </p>

  <?php if (!empty($c['cv'])): ?>
    <p style="margin-top:10px">
      <a class="btn btn-outline"
         href="<?= e($c['cv']); ?>"
         target="_blank">
        &#128196; T&eacute;l&eacute;charger le CV
      </a>
    </p>
  <?php endif; ?>

  <?php if (!empty($c['message'])): ?>
    <h4 style="margin-top:16px">Message du candidat</h4>
    <p><?= nl2br(e($c['message'])); ?></p>
  <?php endif; ?>

  <hr>

  <form method="post" style="max-width:360px">
    <?= csrf_field(); ?>

    <label class="label">
      Changer le statut
    </label>

    <select class="input" name="statut">
      <?php
      $stats = ['recu','en_cours','retenu','rejete'];
      foreach ($stats as $s):
      ?>
        <option value="<?= $s; ?>" <?= $c['statut']===$s?'selected':''; ?>>
          <?= strtoupper($s); ?>
        </option>
      <?php endforeach; ?>
    </select>

    <div style="margin-top:16px;display:flex;gap:10px">

      <button class="btn btn-primary" type="submit">
        &#128190; Mettre &agrave; jour
      </button>

      <a class="btn btn-secondary" href="candidatures.php">
        &larr; Retour
      </a>

    </div>

  </form>

</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout/layout.php';
