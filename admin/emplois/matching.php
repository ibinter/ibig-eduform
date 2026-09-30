<?php
require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/database.php';

session_start();
if (empty($_SESSION['admin_id'])) exit;

$pdo = Database::connect();

/* =========================
   OFFRES APPROUVÉES
========================= */
$offres = $pdo->query("
  SELECT id, titre
  FROM offres_emploi
  WHERE statut='approuvee'
  ORDER BY cree_le DESC
")->fetchAll(PDO::FETCH_ASSOC);

$offre_id = (int)($_GET['offre_id'] ?? 0);

/* =========================
   MATCHING SI OFFRE CHOISIE
========================= */
$matches = [];
if ($offre_id > 0) {
  $stmt = $pdo->prepare("
    SELECT 
      m.score,
      c.nom,
      c.email,
      c.telephone,
      c.id AS candidat_id
    FROM matching_scores m
    JOIN candidats c ON c.id = m.candidat_id
    WHERE m.offre_id = ?
    ORDER BY m.score DESC
  ");
  $stmt->execute([$offre_id]);
  $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

require_once __DIR__ . '/../partials/header.php';
?>

<h1>Matching & Insertion professionnelle</h1>

<form method="get" style="margin-bottom:20px;">
  <label>
    <strong>Sélectionner une offre :</strong><br>
    <select name="offre_id" required>
      <option value="">— Choisir —</option>
      <?php foreach ($offres as $o): ?>
        <option value="<?= $o['id']; ?>" <?= $offre_id===$o['id']?'selected':''; ?>>
          <?= htmlspecialchars($o['titre']); ?>
        </option>
      <?php endforeach; ?>
    </select>
  </label>

  <button type="submit">Afficher</button>

  <?php if ($offre_id): ?>
    <a href="compute_matching.php?offre_id=<?= $offre_id; ?>"
       onclick="return confirm('Recalculer les scores de matching ?');"
       style="margin-left:15px;">
      🔄 Recalculer le matching
    </a>
  <?php endif; ?>
</form>

<?php if ($offre_id): ?>

<h2>Résultats du matching</h2>

<?php if (!$matches): ?>
  <p>Aucun candidat matché pour cette offre.</p>
<?php else: ?>

<table border="1" cellpadding="8" width="100%">
<tr>
  <th>Score</th>
  <th>Candidat</th>
  <th>Email</th>
  <th>Téléphone</th>
  <th>Action</th>
</tr>

<?php foreach ($matches as $m): ?>
<tr>
  <td><strong><?= $m['score']; ?>%</strong></td>
  <td><?= htmlspecialchars($m['nom']); ?></td>
  <td><?= htmlspecialchars($m['email']); ?></td>
  <td><?= htmlspecialchars($m['telephone']); ?></td>
  <td>
    <a href="candidat_view.php?id=<?= (int)$m['candidat_id']; ?>">Voir</a>
  </td>
</tr>
<?php endforeach; ?>
</table>

<?php endif; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
