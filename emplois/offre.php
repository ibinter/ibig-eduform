<?php
declare(strict_types=1);

require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/database.php';

$pdo = Database::connect();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) exit("Offre invalide.");

$stmt = $pdo->prepare("
  SELECT 
    o.*,
    e.nom_legal
  FROM offres_emploi o
  JOIN entreprises e ON e.id = o.entreprise_id
  WHERE o.id = ? AND o.statut = 'approuvee'
  LIMIT 1
");
$stmt->execute([$id]);
$o = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$o) exit("Offre indisponible.");

$pageTitle = $o['titre']." – IBIG EDUFORM";
require_once __DIR__ . '/../partials/header.php';
?>

<main class="container" style="max-width:900px; margin:auto; padding:40px 20px;">

  <h1><?= htmlspecialchars($o['titre']); ?></h1>

  <p>
    <strong>Entreprise :</strong> <?= htmlspecialchars($o['nom_legal']); ?><br>
    <strong>Lieu :</strong> <?= htmlspecialchars($o['lieu']); ?><br>
    <strong>Type de contrat :</strong> <?= htmlspecialchars($o['type_contrat']); ?>
  </p>

  <hr>

  <h3>Description</h3>
  <p><?= nl2br(htmlspecialchars($o['description'])); ?></p>

  <?php if (!empty($o['missions'])): ?>
    <h3>Missions</h3>
    <p><?= nl2br(htmlspecialchars($o['missions'])); ?></p>
  <?php endif; ?>

  <?php if (!empty($o['profil_recherche'])): ?>
    <h3>Profil recherché</h3>
    <p><?= nl2br(htmlspecialchars($o['profil_recherche'])); ?></p>
  <?php endif; ?>

  <hr>

  <p style="margin-top:30px;">
    <a href="postuler.php?id=<?= (int)$o['id']; ?>" class="btn-primary">
      📩 Postuler à cette offre
    </a>
  </p>

</main>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
