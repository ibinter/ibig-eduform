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

/* ============================
   RÉCUPÉRATION DES OFFRES
============================ */
$stmt = $pdo->prepare("
  SELECT id, titre, statut, cree_le
  FROM offres_emploi
  WHERE entreprise_id = ?
  ORDER BY cree_le DESC
");
$stmt->execute([$eid]);
$offres = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ============================
   PARTIAL HEADER
============================ */
$pageTitle = "Mes offres – Espace Entreprise";
require_once __DIR__ . '/../partials/header.php';
?>

<h1>Mes offres</h1>

<?php if (!$offres): ?>
  <p>Aucune offre enregistr&eacute;e pour le moment.</p>
<?php else: ?>

<table cellpadding="10" cellspacing="0" style="width:100%;border-collapse:collapse;">
  <thead style="background:#020617;color:#e5e7eb;">
    <tr>
      <th align="left">Titre</th>
      <th align="left">Statut</th>
      <th align="left">Date</th>
      <th align="left">Action</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($offres as $o): ?>
    <tr style="border-bottom:1px solid #e2e8f0;">
      <td><?= htmlspecialchars($o['titre'], ENT_QUOTES, 'UTF-8'); ?></td>
      <td><?= htmlspecialchars($o['statut'], ENT_QUOTES, 'UTF-8'); ?></td>
      <td><?= htmlspecialchars($o['cree_le'], ENT_QUOTES, 'UTF-8'); ?></td>
      <td>
        <?php if ($o['statut'] === 'brouillon'): ?>
          <a href="edit.php?id=<?= (int)$o['id']; ?>"
             style="font-weight:600;">
            &#9998;&#65039; Modifier
          </a>
        <?php else: ?>
          &mdash;
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>

<?php endif; ?>

<?php
/* ============================
   PARTIAL FOOTER
============================ */
require_once __DIR__ . '/../partials/footer.php';
