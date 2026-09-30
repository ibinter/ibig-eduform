<?php
require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../auth/middleware.php';

Middleware::requireAuth();

$pageTitle  = 'Nouveau slide';
$activeMenu = 'hero';
$pdo = Database::connect();
$error = '';

require __DIR__ . '/_upload.php'; // fournit hero_handle_upload()

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    [$image, $upErr] = hero_handle_upload($_POST['image_existing'] ?? 'hero-1.jpg');
    if ($upErr) {
        $error = $upErr;
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO hero_slides
              (position, image, kicker, title, lead, btn1_label, btn1_href,
               btn2_label, btn2_href, panel_title, panel_text, panel_list, is_active)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)
        ");
        $stmt->execute([
            (int)($_POST['position'] ?? 0),
            $image,
            trim((string)($_POST['kicker'] ?? '')),
            trim((string)($_POST['title'] ?? '')),
            trim((string)($_POST['lead'] ?? '')),
            trim((string)($_POST['btn1_label'] ?? '')),
            trim((string)($_POST['btn1_href'] ?? '')),
            trim((string)($_POST['btn2_label'] ?? '')),
            trim((string)($_POST['btn2_href'] ?? '')),
            trim((string)($_POST['panel_title'] ?? '')),
            trim((string)($_POST['panel_text'] ?? '')),
            trim((string)($_POST['panel_list'] ?? '')),
            isset($_POST['is_active']) ? 1 : 0,
        ]);
        redirect('index.php');
    }
}

$s = [
    'position'=>0,'image'=>'hero-1.jpg','kicker'=>'','title'=>'','lead'=>'',
    'btn1_label'=>'','btn1_href'=>'','btn2_label'=>'','btn2_href'=>'',
    'panel_title'=>'','panel_text'=>'','panel_list'=>'','is_active'=>1,
];

ob_start();
require __DIR__ . '/_form.php';
$content = ob_get_clean();
require __DIR__ . '/../layout/layout.php';
