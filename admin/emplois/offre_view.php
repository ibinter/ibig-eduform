<?php
declare(strict_types=1);

/*
 |--------------------------------------------------
 | INIT ADMIN (PIPELINE INTERNE)
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
$pageTitle  = "Détails de l’offre";
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
 | RÉCUPÉRATION OFFRE + ENTREPRISE
 |--------------------------------------------------
*/
$stmt = $pdo->prepare("
  SELECT 
    o.*,
    e.nom_legal,
    e.email AS entreprise_email
  FROM offres_emploi o
  JOIN entreprises e ON e.id = o.entreprise_id
  WHERE o.id = ?
  LIMIT 1
");
$stmt->execute([$id]);
$offre = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$offre) {
  die("Offre introuvable.");
}

/*
 |--------------------------------------------------
 | CONTENU (BUFFER)
 |--------------------------------------------------
*/
ob_start();
?>

<div class="card">

  <h2>&#128188; Détails de l’offre</h2>

  <hr>

  <h3><?= e($offre['titre']); ?></h3>

  <p>
    <strong>Entreprise :</strong>
    <?= e($offre['nom_legal']); ?>
    (<?= e($offre['entreprise_email']); ?>)
  </p>

  <p>
    <strong>Statut :</strong>
    <span class="pill <?= $offre['statut']==='approuvee'?'ok':'wait'; ?>">
      <?= e($offre['statut']); ?>
    </span>
  </p>

  <hr>

  <p><strong>Type de contrat :</strong> <?= e($offre['type_contrat']); ?></p>
  <p><strong>Lieu :</strong> <?= e($offre['lieu']); ?></p>

  <?php if (!empty($offre['date_limite'])): ?>
    <p><strong>Date limite :</strong> <?= e($offre['date_limite']); ?></p>
  <?php endif; ?>

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

  <?php if (!empty($offre['niveau_experience'])): ?>
    <p>
      <strong>Niveau d’expérience :</strong>
      <?= e($offre['niveau_experience']); ?>
    </p>
  <?php endif; ?>

  <hr>

  <div style="display:flex;gap:10px;flex-wrap:wrap">

    <a class="btn btn-secondary"
       href="offres.php">
      ← Retour aux offres
    </a>

    <a class="btn btn-primary"
       href="actions.php?id=<?= (int)$offre['id']; ?>">
      Examiner l’offre
    </a>

  </div>

</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout/layout.php';
