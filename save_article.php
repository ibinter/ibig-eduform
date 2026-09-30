$imagePath = null;

if (!empty($_FILES['image']['name'])) {

  $allowed = ['jpg','jpeg','png','webp'];
  $maxSize = 2 * 1024 * 1024; // 2 Mo

  $file = $_FILES['image'];
  $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

  if (!in_array($ext, $allowed)) {
    throw new Exception("Format d'image non autorisé.");
  }

  if ($file['size'] > $maxSize) {
    throw new Exception("Image trop lourde (max 2 Mo).");
  }

  if (!is_uploaded_file($file['tmp_name'])) {
    throw new Exception("Upload invalide.");
  }

  $newName = 'blog_' . time() . '_' . rand(1000,9999) . '.' . $ext;
  $target  = __DIR__ . '/../uploads/blog/' . $newName;

  if (!move_uploaded_file($file['tmp_name'], $target)) {
    throw new Exception("Impossible d'enregistrer l'image.");
  }

  // Chemin stocké en base
  $imagePath = 'uploads/blog/' . $newName;
}
