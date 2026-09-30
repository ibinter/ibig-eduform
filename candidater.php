<?php
declare(strict_types=1);

require_once __DIR__.'/core/config.php';
require_once __DIR__.'/core/database.php';

$pdo = Database::connect();
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) die('Offre invalide');

if ($_SERVER['REQUEST_METHOD']==='POST') {

  $nom = trim($_POST['nom']);
  $email = trim($_POST['email']);
  $tel = trim($_POST['telephone']);
  $msg = trim($_POST['message']);

  if (!isset($_FILES['cv']) || $_FILES['cv']['error']!==0) {
    $error = "CV requis.";
  } else {

    $ext = strtolower(pathinfo($_FILES['cv']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext,['pdf','doc','docx'])) {
      $error = "Format CV invalide.";
    } else {

      $cvName = uniqid('cv_').'.'.$ext;
      move_uploaded_file($_FILES['cv']['tmp_name'], __DIR__.'/uploads/cv/'.$cvName);

      $ins = $pdo->prepare("
        INSERT INTO candidatures
        (offre_id, nom, email, telephone, cv, message, statut, created_at)
        VALUES (?, ?, ?, ?, ?, ?, 'recu', NOW())
      ");
      $ins->execute([$id,$nom,$email,$tel,$cvName,$msg]);

      header('Location: merci.php');
      exit;
    }
  }
}
