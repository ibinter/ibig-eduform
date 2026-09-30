<?php
declare(strict_types=1);
/* Télécharge la copie PDF serveur d'un TDR — accès admin uniquement */
require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../auth/middleware.php';
require_once __DIR__ . '/../../core/auth.php';

Middleware::requireAuth();

$id  = (int)($_GET['id'] ?? 0);
if ($id <= 0) { http_response_code(400); exit('ID invalide.'); }

$pdo = Database::connect();
$row = $pdo->prepare("SELECT pdf_path, prospect, formation_nom, downloaded_at FROM tdr_copies WHERE id = ? LIMIT 1");
$row->execute([$id]);
$r = $row->fetch(PDO::FETCH_ASSOC);

if (!$r || $r['pdf_path'] === '') { http_response_code(404); exit('Introuvable.'); }

$base    = realpath(__DIR__ . '/../../uploads/tdr-copies/') ?: '';
$file    = realpath(__DIR__ . '/../../' . $r['pdf_path']) ?: '';

if ($base === '' || $file === '' || strpos($file, $base) !== 0) {
    http_response_code(403); exit('Accès refusé.');
}
if (!is_readable($file)) { http_response_code(404); exit('Fichier introuvable sur le serveur.'); }

$slug = preg_replace('/[^a-z0-9\-]/', '', strtolower(str_replace(' ', '-', $r['formation_nom'] ?? 'tdr')));
$slug = trim($slug, '-') ?: 'tdr';
$date = $r['downloaded_at'] ? date('Ymd', strtotime($r['downloaded_at'])) : date('Ymd');
$filename = 'TDR-' . $slug . '-' . $date . '-ID' . $id . '.pdf';

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . $filename . '"');
header('Content-Length: ' . filesize($file));
header('Cache-Control: private, no-cache');
readfile($file);
exit;
