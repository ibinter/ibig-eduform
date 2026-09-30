<?php
require_once __DIR__ . '/../../core/database.php';
session_start();
if (empty($_SESSION['admin_id'])) exit;

$pdo = Database::connect();
$id = (int)($_GET['id'] ?? 0);

$ent = $pdo->prepare("SELECT * FROM entreprises WHERE id=?");
$ent->execute([$id]);
$e = $ent->fetch(PDO::FETCH_ASSOC);

$doc = $pdo->prepare("
  SELECT * FROM entreprise_documents
  WHERE entreprise_id=? AND type_document='registre_commerce'
");
$doc->execute([$id]);
$rccm = $doc->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  csrf_check();

  if (isset($_POST['valider'])) {
    $pdo->prepare("UPDATE entreprises SET statut='actif' WHERE id=?")->execute([$id]);
    $pdo->prepare("
      UPDATE entreprise_documents
      SET statut='valide'
      WHERE entreprise_id=? AND type_document='registre_commerce'
    ")->execute([$id]);
  }

  if (isset($_POST['refuser'])) {
    $pdo->prepare("UPDATE entreprises SET statut='refuse' WHERE id=?")->execute([$id]);
  }

  header("Location: entreprises.php");
  exit;
}

require_once __DIR__ . '/../partials/header.php';
?>

<h1><?= htmlspecialchars($e['nom_legal']); ?></h1>

<p><strong>Email :</strong> <?= htmlspecialchars($e['email']); ?></p>
<p><strong>Statut :</strong> <?= $e['statut']; ?></p>

<h3>RCCM</h3>
<?php if ($rccm): ?>
  <p>Numéro : <?= htmlspecialchars($rccm['numero_document']); ?></p>
  <a href="<?= htmlspecialchars($rccm['fichier']); ?>" target="_blank">
    Voir le document
  </a>
<?php endif; ?>

<form method="post" style="margin-top:20px;">
  <?= csrf_field(); ?>
  <button name="valider">Valider entreprise & RCCM</button>
  <button name="refuser"
          onclick="return confirm('Refuser cette entreprise ?');">
    Refuser
  </button>
</form>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
