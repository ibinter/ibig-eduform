<?php
declare(strict_types=1);

/*
 |--------------------------------------------------
 | AUTH ENTREPRISE — IBIG EDUFORM
 |--------------------------------------------------
 | Middleware de protection des pages entreprise
*/

require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/database.php';

/* ============================
   VÉRIFICATION SESSION
============================ */
if (empty($_SESSION['entreprise_id'])) {
  header('Location: /entreprise/login.php');
  exit;
}

$pdo = Database::connect();
$entrepriseId = (int) $_SESSION['entreprise_id'];

/* ============================
   RÉCUPÉRATION ENTREPRISE
============================ */
$stmt = $pdo->prepare("
  SELECT
    id,
    nom_legal,
    email,
    statut,
    created_at
  FROM entreprises
  WHERE id = ?
  LIMIT 1
");
$stmt->execute([$entrepriseId]);
$entreprise = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$entreprise) {
  // Compte supprimé ou invalide
  $_SESSION = [];
  session_destroy();
  header('Location: /entreprise/login.php');
  exit;
}

/* ============================
   CONTRÔLE STATUT
============================ */
if ($entreprise['statut'] === 'suspendu') {
  $_SESSION = [];
  session_destroy();
  header('Location: /entreprise/login.php?blocked=1');
  exit;
}

/*
 |--------------------------------------------------
 | VARIABLES DISPONIBLES DANS LES PAGES
 |--------------------------------------------------
 | $entrepriseId → ID entreprise
 | $entreprise   → Infos entreprise
 |   - $entreprise['nom_legal']
 |   - $entreprise['email']
 |   - $entreprise['statut']
*/
