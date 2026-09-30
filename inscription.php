<?php
require_once __DIR__ . '/core/bootstrap.php';

$pageTitle = "Inscription officielle – IBIG EDUFORM";
include __DIR__ . '/partials/header.php';

$pdo = Database::connect();

// Formation depuis l’URL
$formationId = isset($_GET['formation']) ? (int)$_GET['formation'] : 0;
$formation = null;

if ($formationId > 0) {
    $stmt = $pdo->prepare("SELECT id, titre FROM formations WHERE id = ?");
    $stmt->execute([$formationId]);
    $formation = $stmt->fetch();
}

$success = false;
$error = '';

// Traitement formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $numero = 'IBIG-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(3)));

        $stmt = $pdo->prepare("
            INSERT INTO inscriptions
            (numero_inscription, formation_id, nom, prenom, email, telephone, mode_formation)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $numero,
            $_POST['formation_id'],
            trim($_POST['nom']),
            trim($_POST['prenom']),
            trim($_POST['email']),
            trim($_POST['telephone']),
            $_POST['mode_formation']
        ]);

        $success = true;
    } catch (Exception $e) {
        $error = "Une erreur est survenue. Merci de réessayer.";
    }
}
?>

<style>
/* =========================================
   CSS LOCAL — INSCRIPTION OFFICIELLE
========================================= */
body{
  background:radial-gradient(circle at top,#0b3c5d,#020617);
  color:#e5e7eb;
  font-family:Inter,system-ui,sans-serif;
}

.container{
  max-width:650px;
  margin:80px auto;
  background:rgba(255,255,255,.06);
  backdrop-filter:blur(14px);
  padding:42px;
  border-radius:24px;
  box-shadow:0 22px 55px rgba(0,0,0,.5);
}

h1{
  font-size:2.2rem;
  margin-bottom:10px;
}

.subtitle{
  opacity:.9;
  margin-bottom:30px;
}

label{
  font-weight:700;
  display:block;
  margin-bottom:6px;
}

input,select{
  width:100%;
  padding:14px;
  border-radius:12px;
  border:none;
  margin-bottom:18px;
}

button{
  width:100%;
  background:#f5a623;
  color:#000;
  font-weight:900;
  padding:18px;
  border-radius:16px;
  border:none;
  cursor:pointer;
  font-size:1rem;
}

.success{
  background:rgba(34,197,94,.15);
  border:1px solid #22c55e;
  padding:22px;
  border-radius:16px;
}

.error{
  background:rgba(239,68,68,.15);
  border:1px solid #ef4444;
  padding:18px;
  border-radius:16px;
  margin-bottom:20px;
}
</style>

<main>
  <div class="container">

    <?php if ($success): ?>
      <div class="success">
        <h2>Inscription enregistrée avec succès</h2>
        <p>
          Votre inscription officielle a bien été prise en compte.<br>
          Notre équipe vous contactera très rapidement pour les modalités
          (paiement, démarrage, documents).
        </p>
      </div>
    <?php else: ?>

      <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error); ?></div>
      <?php endif; ?>

      <h1>Inscription officielle</h1>
      <p class="subtitle">
        Cette inscription confirme votre engagement à suivre la formation.
      </p>

      <form method="post">
        <input type="hidden" name="formation_id" value="<?= htmlspecialchars($formation['id'] ?? ''); ?>">

        <label>Formation choisie</label>
        <input type="text" value="<?= htmlspecialchars($formation['titre'] ?? ''); ?>" disabled required>

        <label>Nom</label>
        <input name="nom" required>

        <label>Prénom</label>
        <input name="prenom" required>

        <label>Email</label>
        <input type="email" name="email" required>

        <label>Téléphone</label>
        <input name="telephone" required>

        <label>Mode de formation</label>
        <select name="mode_formation" required>
          <option value="">— Choisir —</option>
          <option value="en_ligne">En ligne</option>
          <option value="presentiel">Présentiel</option>
          <option value="hybride">Hybride</option>
        </select>

        <button type="submit">Valider mon inscription</button>
      </form>

    <?php endif; ?>

  </div>
</main>

<?php include __DIR__ . '/partials/footer.php'; ?>
