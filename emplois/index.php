<?php
require_once __DIR__ . '/../../core/database.php';
$pdo = Database::connect();

$offres = $pdo->query("
  SELECT o.id, o.titre, o.lieu, o.type_contrat, e.nom_legal
  FROM offres_emploi o
  JOIN entreprises e ON e.id = o.entreprise_id
  WHERE o.statut = 'approuvee'
  ORDER BY o.cree_le DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Offres d’emploi – IBIG EDUFORM</title>
</head>
<body>

<h1>Offres d’emploi disponibles</h1>

<?php if (!$offres): ?>
  <p>Aucune offre disponible actuellement.</p>
<?php endif; ?>

<?php foreach ($offres as $o): ?>
  <article style="border:1px solid #e5e7eb;padding:14px;margin-bottom:12px;">
    <h2><?= htmlspecialchars($o['titre']); ?></h2>
    <p>
      <strong>Entreprise :</strong> <?= htmlspecialchars($o['nom_legal']); ?><br>
      <strong>Lieu :</strong> <?= htmlspecialchars($o['lieu']); ?><br>
      <strong>Contrat :</strong> <?= htmlspecialchars($o['type_contrat']); ?>
    </p>
    <a href="offre.php?id=<?= (int)$o['id']; ?>">Voir l’offre</a>
  </article>
<?php endforeach; ?>

</body>
</html>
