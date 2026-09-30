<?php
declare(strict_types=1);
require_once __DIR__ . '/_auth.php';

$error = '';
$success = '';

$dir = __DIR__ . '/../uploads/candidats';
if (!is_dir($dir)) @mkdir($dir, 0755, true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (empty($_FILES['avatar']['name'])) {
    $error = "Veuillez choisir une image.";
  } else {
    $f = $_FILES['avatar'];

    if ($f['error'] !== UPLOAD_ERR_OK) {
      $error = "Erreur upload.";
    } elseif ($f['size'] > 2 * 1024 * 1024) {
      $error = "Image trop lourde (max 2MB).";
    } else {
      $tmp = $f['tmp_name'];
      $mime = mime_content_type($tmp) ?: '';

      $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
      if (!isset($allowed[$mime])) {
        $error = "Format non supporté (JPG, PNG, WEBP).";
      } else {
        $ext = $allowed[$mime];
        $name = 'avatar_' . $candidatId . '_' . time() . '.' . $ext;

        $pathRel = '/uploads/candidats/' . $name;
        $pathAbs = $dir . '/' . $name;

        if (!move_uploaded_file($tmp, $pathAbs)) {
          $error = "Impossible d'enregistrer le fichier.";
        } else {
          $pdo->prepare("UPDATE candidats SET avatar=? WHERE id=? LIMIT 1")
              ->execute([$pathRel, $candidatId]);

          $pdo->prepare("
            INSERT INTO candidat_files (candidat_id, type, path, original_name, mime, size)
            VALUES (?, 'avatar', ?, ?, ?, ?)
          ")->execute([$candidatId, $pathRel, $f['name'] ?? null, $mime, (int)$f['size']]);

          $candidat['avatar'] = $pathRel;
          $success = "Photo mise à jour.";
        }
      }
    }
  }
}
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Photo de profil – IBIG EDUFORM</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
body{margin:0;font-family:Inter,Arial;background:#f1f5f9}
.header{background:linear-gradient(90deg,#0b5ed7,#1d4ed8);color:#fff;padding:18px 26px;display:flex;justify-content:space-between;align-items:center}
.header a{color:#fff;text-decoration:none;font-weight:800}
.container{max-width:800px;margin:26px auto;padding:0 20px}
.card{background:#fff;border-radius:16px;padding:18px;box-shadow:0 10px 30px rgba(0,0,0,.08)}
.btn{padding:10px 14px;border-radius:12px;background:#0b5ed7;color:#fff;border:0;font-weight:800;cursor:pointer}
.msg{padding:10px 12px;border-radius:12px;margin:0 0 12px}
.ok{background:#dcfce7;color:#166534}
.err{background:#fee2e2;color:#991b1b}
.preview{width:92px;height:92px;border-radius:999px;object-fit:cover;border:2px solid #e5e7eb}
.small{color:#64748b;font-size:13px}
</style>
</head>
<body>
<div class="header">
  <div>IBIG EDUFORM — Photo de profil</div>
  <div><a href="dashboard.php">Dashboard</a> | <a href="logout.php">Déconnexion</a></div>
</div>

<div class="container">
  <div class="card">
    <?php if ($error): ?><div class="msg err"><?= e($error); ?></div><?php endif; ?>
    <?php if ($success): ?><div class="msg ok"><?= e($success); ?></div><?php endif; ?>

    <div style="display:flex;align-items:center;gap:12px;margin-bottom:10px">
      <img class="preview" src="<?= e($candidat['avatar'] ?: '/assets/images/logo.png'); ?>" alt="Avatar">
      <div class="small">
        Formats: JPG, PNG, WEBP. Taille max: 2MB.
      </div>
    </div>

    <form method="post" enctype="multipart/form-data">
      <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp" required>
      <div style="margin-top:12px">
        <button class="btn" type="submit">Enregistrer</button>
        <a class="btn" style="background:#475569;text-decoration:none" href="profil.php">Retour</a>
      </div>
    </form>
  </div>
</div>
</body>
</html>
