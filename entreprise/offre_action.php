<?php
declare(strict_types=1);
require_once __DIR__ . '/_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: offres.php'); exit;
}

$id = (int)($_POST['id'] ?? 0);
$do = (string)($_POST['do'] ?? '');

if ($id <= 0) { header('Location: offres.php'); exit; }

if ($do === 'submit') {
  // brouillon/refusee -> soumise
  $up = $pdo->prepare("
    UPDATE offres_emploi
    SET statut='soumise'
    WHERE id=? AND entreprise_id=? AND statut IN ('brouillon','refusee')
    LIMIT 1
  ");
  $up->execute([$id, $entrepriseId]);
  header('Location: offres.php'); exit;
}

if ($do === 'delete') {
  // Supprimer offre + candidatures liées (optionnel)
  $pdo->beginTransaction();
  try {
    $delC = $pdo->prepare("
      DELETE c FROM candidatures c
      JOIN offres_emploi o ON o.id=c.offre_id
      WHERE c.offre_id=? AND o.entreprise_id=?
    ");
    $delC->execute([$id, $entrepriseId]);

    $del = $pdo->prepare("DELETE FROM offres_emploi WHERE id=? AND entreprise_id=? LIMIT 1");
    $del->execute([$id, $entrepriseId]);

    $pdo->commit();
  } catch (Throwable $e) {
    $pdo->rollBack();
  }
  header('Location: offres.php'); exit;
}

header('Location: offres.php'); exit;
