<?php
declare(strict_types=1);

/*
 |--------------------------------------------------
 | AUTH CANDIDAT — IBIG EDUFORM (VERSION STABLE)
 |--------------------------------------------------
*/

define('PUBLIC_PAGE', true);

require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/helpers.php';

/* --------------------------------------------------
 | SESSION & SÉCURITÉ
-------------------------------------------------- */
if (empty($_SESSION['candidat_id'])) {
  header('Location: login.php');
  exit;
}

$pdo = Database::connect();
$candidatId = (int) $_SESSION['candidat_id'];

/* --------------------------------------------------
 | PROFIL CANDIDAT (SOURCE UNIQUE)
-------------------------------------------------- */
$stmt = $pdo->prepare("
  SELECT
    id,
    nom,
    prenoms,
    email,
    telephone,
    cv,
    avatar,
    notify_email,
    notify_whatsapp,
    deleted_at,
    created_at,
    mot_de_passe
  FROM candidats
  WHERE id = ?
  LIMIT 1
");
$stmt->execute([$candidatId]);
$candidat = $stmt->fetch(PDO::FETCH_ASSOC);

/* --------------------------------------------------
 | COMPTE INVALIDE / SUPPRIMÉ
-------------------------------------------------- */
if (!$candidat || !empty($candidat['deleted_at'])) {
  session_regenerate_id(true);
  unset($_SESSION['candidat_id'], $_SESSION['candidat_nom']);
  header('Location: login.php');
  exit;
}

/* --------------------------------------------------
 | NOM D’AFFICHAGE GLOBAL
-------------------------------------------------- */
$displayName = trim(
  ($candidat['nom'] ?? '') . ' ' . ($candidat['prenoms'] ?? '')
);
if ($displayName === '') {
  $displayName = $candidat['email'];
}

/* --------------------------------------------------
 | DÉTECTION DYNAMIQUE DE candidatures.candidat_id
 | (compatibilité AVANT / APRÈS migration)
-------------------------------------------------- */
$hasCandidatId = (bool) $pdo->query("
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'candidatures'
    AND COLUMN_NAME = 'candidat_id'
")->fetchColumn();

/* --------------------------------------------------
 | WHERE & BIND POUR LES CANDIDATURES
-------------------------------------------------- */
if ($hasCandidatId) {
  $candWhereSQL  = 'candidat_id = ?';
  $candWhereBind = [$candidatId];
} else {
  // fallback ancien modèle (email)
  $candWhereSQL  = 'email = ?';
  $candWhereBind = [$candidat['email']];
}

/* --------------------------------------------------
 | VARIABLES GLOBALES DISPONIBLES PARTOUT
-------------------------------------------------- */
/*
  $candidat        → tableau complet du candidat
  $candidatId      → ID candidat
  $displayName     → nom affichable
  $candWhereSQL    → WHERE candidatures
  $candWhereBind   → bind candidatures
*/
