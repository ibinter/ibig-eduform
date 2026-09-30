<?php
declare(strict_types=1);

require_once __DIR__ . '/../_init.php';

$mw = realpath(__DIR__ . '/../auth/middleware.php');
if (!$mw) { die("Middleware introuvable"); }
require_once $mw;
Middleware::requireAuth();

$pdo = Database::connect();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) redirect('index.php');

/* candidature retenue */
$stmt = $pdo->prepare("
  SELECT *
  FROM candidatures_formateurs
  WHERE id = ?
    AND statut = 'retenue'
  LIMIT 1
");
$stmt->execute([$id]);
$c = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$c) redirect('index.php');
if ((int)$c['fiche_creee'] === 1) redirect('index.php');

/* insertion fiche (candidature_id UNIQUE => pas de doublon) */
$stmt = $pdo->prepare("
  INSERT INTO formateurs
    (candidature_id, nom, domaine, email, telephone, bio, experience, statut)
  VALUES (?, ?, ?, ?, ?, ?, ?, 'actif')
");
$stmt->execute([
  (int)$c['id'],
  (string)$c['nom'],
  (string)$c['domaine'],
  (string)$c['email'],
  (string)$c['telephone'],
  (string)($c['bio'] ?: $c['message']),
  (string)($c['experience'] ?? '')
]);

/* marquer candidature */
$pdo->prepare("
  UPDATE candidatures_formateurs
  SET fiche_creee = 1,
      visible = 1
  WHERE id = ?
  LIMIT 1
")->execute([$id]);

redirect('index.php');
