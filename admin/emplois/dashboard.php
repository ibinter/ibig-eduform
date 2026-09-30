<?php
require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/database.php';
session_start();
if (empty($_SESSION['admin_id'])) exit;

$pdo = Database::connect();

$stats = [
  'entreprises_attente' => $pdo->query("
    SELECT COUNT(*) FROM entreprises WHERE statut='en_attente'
  ")->fetchColumn(),

  'rccm_attente' => $pdo->query("
    SELECT COUNT(*) FROM entreprise_documents
    WHERE type_document='registre_commerce'
      AND statut='en_verification'
  ")->fetchColumn(),

  'offres_soumises' => $pdo->query("
    SELECT COUNT(*) FROM offres_emploi WHERE statut='soumise'
  ")->fetchColumn(),
];

require_once __DIR__ . '/../partials/header.php';
?>

<h1>Dashboard Emploi & Insertion</h1>

<ul>
  <li>🏢 Entreprises en attente : <strong><?= (int)$stats['entreprises_attente']; ?></strong></li>
  <li>📄 RCCM à vérifier : <strong><?= (int)$stats['rccm_attente']; ?></strong></li>
  <li>📢 Offres à valider : <strong><?= (int)$stats['offres_soumises']; ?></strong></li>
</ul>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
