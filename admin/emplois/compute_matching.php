<?php
declare(strict_types=1);

require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/database.php';

session_start();
if (empty($_SESSION['admin_id'])) exit;

$pdo = Database::connect();

$offre_id = (int)($_GET['offre_id'] ?? 0);
if ($offre_id <= 0) exit("Offre invalide");

/* ============================
   COMPÉTENCES REQUISES OFFRE
============================ */
$req = $pdo->prepare("
  SELECT competence_id, poids, obligatoire
  FROM offre_competences
  WHERE offre_id = ?
");
$req->execute([$offre_id]);
$reqs = $req->fetchAll(PDO::FETCH_ASSOC);

if (!$reqs) {
  exit("Aucune compétence définie pour cette offre.");
}

/* ============================
   TOUS LES CANDIDATS
============================ */
$candidats = $pdo->query("
  SELECT id FROM candidats
")->fetchAll(PDO::FETCH_ASSOC);

/* ============================
   CALCUL SCORE
============================ */
foreach ($candidats as $c) {

  $score = 0;
  $max   = 0;
  $penalite = false;

  foreach ($reqs as $r) {
    $max += ($r['poids'] * 1.0);

    $stmt = $pdo->prepare("
      SELECT niveau
      FROM candidat_competences
      WHERE candidat_id = ?
        AND competence_id = ?
    ");
    $stmt->execute([$c['id'], $r['competence_id']]);
    $niveau = $stmt->fetchColumn();

    if (!$niveau) {
      if ($r['obligatoire']) {
        $penalite = true;
        break;
      }
      continue;
    }

    $coef = match ($niveau) {
      'debutant' => 0.6,
      'intermediaire' => 0.85,
      'avance' => 1.0,
      default => 0.8
    };

    $score += ($r['poids'] * $coef);
  }

  if ($penalite || $max == 0) {
    $final = 0;
  } else {
    $final = round(($score / $max) * 100, 2);
  }

  /* ============================
     UPSERT SCORE
  ============================ */
  $stmt = $pdo->prepare("
    INSERT INTO matching_scores (offre_id, candidat_id, score)
    VALUES (?, ?, ?)
    ON DUPLICATE KEY UPDATE
      score = VALUES(score),
      updated_at = NOW()
  ");
  $stmt->execute([$offre_id, $c['id'], $final]);
}

header("Location: matching.php?offre_id=".$offre_id);
exit;
