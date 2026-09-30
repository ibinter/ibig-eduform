<?php
declare(strict_types=1);

require_once __DIR__ . '/core/bootstrap.php';

$pdo = Database::connect();

/* ============================
   VALIDATION PARAMÈTRE
============================ */
$formationId = isset($_GET['formation']) ? (int) $_GET['formation'] : 0;

if ($formationId <= 0) {
    http_response_code(400);
    exit('Accès invalide.');
}

/* ============================
   RÉCUPÉRATION INFOS FORMATION
============================ */
$stmt = $pdo->prepare("
    SELECT tdr_pdf
    FROM formations
    WHERE id = ?
    LIMIT 1
");
$stmt->execute([$formationId]);
$f = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$f || empty($f['tdr_pdf'])) {
    http_response_code(404);
    exit('TDR indisponible.');
}

/* ============================
   SÉCURITÉ NOM FICHIER
============================ */
$filename = basename($f['tdr_pdf']);
$filePath = __DIR__ . '/uploads/tdr/' . $filename;

/* Bloque toute tentative hors dossier */
$realBase = realpath(__DIR__ . '/uploads/tdr/');
$realFile = realpath($filePath);

if (!$realFile || strpos($realFile, $realBase) !== 0) {
    http_response_code(403);
    exit('Accès refusé.');
}

/* ============================
   VÉRIFICATION EXISTENCE
============================ */
if (!file_exists($realFile) || !is_readable($realFile)) {
    http_response_code(404);
    exit('Fichier introuvable.');
}

/* ============================
   HEADERS PDF
============================ */
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="TDR-IBIG-' . $filename . '"');
header('Content-Length: ' . filesize($realFile));
header('Cache-Control: public, max-age=86400');
header('Pragma: public');
header('X-Content-Type-Options: nosniff');

/* ============================
   SORTIE FICHIER
============================ */
readfile($realFile);
exit;
