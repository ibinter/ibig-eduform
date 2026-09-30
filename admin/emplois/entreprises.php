<?php
require_once __DIR__ . '/../../core/database.php';
session_start();
if (empty($_SESSION['admin_id'])) exit;

$pdo = Database::connect();

$rows = $pdo->query("
  SELECT e.id, e.nom_legal, e.email, e.statut,
         d.statut AS rccm_statut
  FROM entreprises e
  LEFT JOIN entreprise_documents d
    ON d.entreprise_id = e.id
    AND d.type_document='registre_commerce'
  ORDER BY e.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../partials/header.php';
?>

<h1>Entreprises & Recruteurs</h1>

<table cellpadding="8" border="1">
<tr>
  <th>Entreprise</th>
  <th>Email</th>
  <th>Statut</th>
  <th>RCCM</th>
  <th>Action</th>
</tr>

<?php foreach ($rows as $e): ?>
<tr>
  <td><?= htmlspecialchars($e['nom_legal']); ?></td>
  <td><?= htmlspecialchars($e['email']); ?></td>
  <td><?= $e['statut']; ?></td>
  <td><?= $e['rccm_statut'] ?? '—'; ?></td>
  <td>
    <a href="entreprise_view.php?id=<?= (int)$e['id']; ?>">Voir</a>
  </td>
</tr>
<?php endforeach; ?>
</table>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
