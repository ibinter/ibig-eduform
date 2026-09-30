<?php
require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../auth/middleware.php';

Middleware::requireAuth();

$pageTitle  = 'Éditer le slide';
$activeMenu = 'hero';
$pdo = Database::connect();
$error = '';

require __DIR__ . '/_upload.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM hero_slides WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$s = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$s) { redirect('index.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    [$image, $upErr] = hero_handle_upload($_POST['image_existing'] ?? $s['image']);
    if ($upErr) {
        $error = $upErr;
    } else {
        $stmt = $pdo->prepare("
            UPDATE hero_slides SET
              position=?, image=?, kicker=?, title=?, lead=?,
              btn1_label=?, btn1_href=?, btn2_label=?, btn2_href=?,
              panel_title=?, panel_text=?, panel_list=?, is_active=?
            WHERE id=?
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
            $id,
        ]);
        redirect('index.php');
    }
    /* En cas d'erreur, on réaffiche les valeurs soumises */
    $s = array_merge($s, $_POST, ['image' => $_POST['image_existing'] ?? $s['image']]);
}

ob_start();
require __DIR__ . '/_form.php';
$content = ob_get_clean();
require __DIR__ . '/../layout/layout.php';
