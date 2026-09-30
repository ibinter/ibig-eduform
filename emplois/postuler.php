<?php
require_once __DIR__ . '/../../core/database.php';
$pdo = Database::connect();

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("
  SELECT id, titre FROM offres_emploi
  WHERE id=? AND statut='approuvee'
");
$stmt->execute([$id]);
$offre = $stmt->fetch();

if (!$offre) exit("Offre invalide.");

$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $cvPath = null;
  if (!empty($_FILES['cv']['tmp_name'])) {
    $name = time().'_'.basename($_FILES['cv']['name']);
    $dest = __DIR__ . '/../../uploads/cv/'.$name;
    move_uploaded_file($_FILES['cv']['tmp_name'], $dest);
    $cvPath = '/uploads/cv/'.$name;
  }

  $stmt = $pdo->prepare("
    INSERT INTO candidatures
      (offre_id, nom, email, telephone, cv, message)
    VALUES (?, ?, ?, ?, ?, ?)
  ");
  $stmt->execute([
    $id,
    $_POST['nom'],
    $_POST['email'],
    $_POST['telephone'],
    $cvPath,
    $_POST['message']
  ]);

  $success = true;
}

require_once __DIR__ . '/../../core/notifications.php';

$subjectAdmin = "Nouvelle candidature – ".$offre['titre'];
$htmlAdmin = "
  <h2>Nouvelle candidature reçue</h2>
  <p><strong>Offre :</strong> ".htmlspecialchars($offre['titre'])."</p>
  <p><strong>Candidat :</strong> ".htmlspecialchars($_POST['nom'])."</p>
  <p><strong>Email :</strong> ".htmlspecialchars($_POST['email'])."</p>
  <p><strong>Téléphone :</strong> ".htmlspecialchars($_POST['telephone'])."</p>
  <p><strong>CV :</strong> ".$cvPath."</p>
";

queue_email(ADMIN_EMAIL, $subjectAdmin, $htmlAdmin); // ou notify_email(...)

$subjectCand = "Candidature bien reçue – IBIG EDUFORM";
$htmlCand = "
  <p>Bonjour ".htmlspecialchars($_POST['nom']).",</p>
  <p>Nous accusons réception de votre candidature pour :</p>
  <p><strong>".htmlspecialchars($offre['titre'])."</strong></p>
  <p>Votre dossier est en cours de traitement par IBIG EDUFORM.</p>
  <p>Cordialement,<br><strong>IBIG EDUFORM</strong></p>
";
queue_email($_POST['email'], $subjectCand, $htmlCand);

?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Postuler – <?= htmlspecialchars($offre['titre']); ?></title>
</head>
<body>

<h1>Postuler : <?= htmlspecialchars($offre['titre']); ?></h1>

<?php if ($success): ?>
  <p style="color:green;">Candidature envoyée avec succès.</p>
<?php else: ?>

<form method="post" enctype="multipart/form-data">
  <input name="nom" placeholder="Nom complet" required><br>
  <input type="email" name="email" placeholder="Email" required><br>
  <input name="telephone" placeholder="Téléphone"><br>
  <input type="file" name="cv" required><br>
  <textarea name="message" placeholder="Message (optionnel)"></textarea><br>
  <button type="submit">Envoyer ma candidature</button>
</form>

<?php endif; ?>

</body>
</html>
