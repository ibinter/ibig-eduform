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
$pageTitle  = "Examen de l’offre";
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
 | ACTIONS ADMIN
 |--------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  csrf_verify();

  if (isset($_POST['approve'])) {
    $pdo->prepare("
      UPDATE offres_emploi
      SET statut = 'approuvee'
      WHERE id = ?
    ")->execute([$id]);
  }

  if (isset($_POST['reject'])) {
    $pdo->prepare("
      UPDATE offres_emploi
      SET statut = 'refusee'
      WHERE id = ?
    ")->execute([$id]);
  }

  header("Location: offres.php");
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

  <h2>&#128188; Examen de l’offre</h2>

  <hr>

  <h3><?= e($offre['titre']); ?></h3>

  <p>
    <strong>Statut :</strong>
    <span class="pill <?= $offre['statut']==='approuvee'?'ok':'wait'; ?>">
      <?= e($offre['statut']); ?>
    </span>
  </p>

  <hr>

  <h4>Description</h4>
  <p><?= nl2br(e($offre['description'])); ?></p>

  <?php if (!empty($offre['missions'])): ?>
    <h4>Missions</h4>
    <p><?= nl2br(e($offre['missions'])); ?></p>
  <?php endif; ?>

  <?php if (!empty($offre['profil_recherche'])): ?>
    <h4>Profil recherché</h4>
    <p><?= nl2br(e($offre['profil_recherche'])); ?></p>
  <?php endif; ?>

  <hr>

  <form method="post" style="display:flex;gap:12px;flex-wrap:wrap">
    <?= csrf_field(); ?>

    <button class="btn btn-success" name="approve">
      &#9989; Approuver
    </button>

    <button class="btn btn-danger"
            name="reject"
            onclick="return confirm('Refuser cette offre ?');">
      &#10060; Refuser
    </button>

    <a class="btn btn-secondary" href="offres.php">
      ← Retour aux offres
    </a>

  </form>

</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout/layout.php';
