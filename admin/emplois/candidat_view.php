<?php
declare(strict_types=1);

require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/notifications.php';

session_start();
if (empty($_SESSION['admin_id'])) {
  header("Location: /admin/login.php");
  exit;
}

$pdo = Database::connect();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) exit("Candidat invalide");

/* ============================
   CANDIDAT
============================ */
$stmt = $pdo->prepare("SELECT * FROM candidats WHERE id = ?");
$stmt->execute([$id]);
$candidat = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$candidat) exit("Candidat introuvable");

/* ============================
   COMPÉTENCES
============================ */
$competences = $pdo->query("
  SELECT cc.id, c.nom, cc.niveau
  FROM candidat_competences cc
  JOIN competences c ON c.id = cc.competence_id
  WHERE cc.candidat_id = $id
")->fetchAll(PDO::FETCH_ASSOC);

$allSkills = $pdo->query("
  SELECT id, nom FROM competences ORDER BY nom
")->fetchAll(PDO::FETCH_ASSOC);

/* ============================
   CANDIDATURES
============================ */
$stmt = $pdo->prepare("
  SELECT 
    ca.statut,
    ca.created_at,
    o.titre,
    e.nom_legal
  FROM candidatures ca
  JOIN offres_emploi o ON o.id = ca.offre_id
  JOIN entreprises e ON e.id = o.entreprise_id
  WHERE ca.email = ?
  ORDER BY ca.created_at DESC
");
$stmt->execute([$candidat['email']]);
$cands = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ============================
   RECOMMANDATIONS FORMATION
============================ */
$stmt = $pdo->prepare("
  SELECT * FROM formation_recos
  WHERE candidat_id = ?
  ORDER BY created_at DESC
");
$stmt->execute([$id]);
$formations = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ============================
   AJOUT COMPÉTENCE
============================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_skill'])) {

  $pdo->prepare("
    INSERT IGNORE INTO candidat_competences
      (candidat_id, competence_id, niveau)
    VALUES (?, ?, ?)
  ")->execute([
    $id,
    (int)$_POST['competence_id'],
    $_POST['niveau']
  ]);

  header("Location: candidat_view.php?id=".$id);
  exit;
}

/* ============================
   AJOUT RECO FORMATION
============================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_reco'])) {

  $pdo->prepare("
    INSERT INTO formation_recos
      (candidat_id, titre, description, priorite)
    VALUES (?, ?, ?, ?)
  ")->execute([
    $id,
    trim($_POST['titre']),
    trim($_POST['description']),
    $_POST['priorite']
  ]);

  header("Location: candidat_view.php?id=".$id);
  exit;
}

/* ============================
   NOTIFICATION CANDIDAT
============================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_statut'])) {

  $statut = $_POST['statut'] ?? '';

  if (in_array($statut, ['retenu', 'rejete'], true)) {

    if ($statut === 'retenu') {
      $subject = "Bonne nouvelle – Suite à votre candidature";
      $html = "
        <p>Bonjour {$candidat['nom']},</p>
        <p>Nous avons le plaisir de vous informer que votre candidature a été
        <strong>retenue</strong> pour la suite du processus.</p>
        <p>L’équipe <strong>IBIG EDUFORM</strong> vous contactera prochainement.</p>
        <p>Cordialement,<br>IBIG EDUFORM</p>
      ";
    } else {
      $subject = "Suite à votre candidature – IBIG EDUFORM";
      $html = "
        <p>Bonjour {$candidat['nom']},</p>
        <p>Après analyse de votre dossier, votre candidature n’a pas été retenue
        pour cette opportunité.</p>
        <p>Nous vous encourageons à continuer à postuler.</p>
        <p>Cordialement,<br>IBIG EDUFORM</p>
      ";
    }

    queue_email(
      $candidat['email'],
      $subject,
      $html
    );
  }

  header("Location: candidat_view.php?id=".$id);
  exit;
}

require_once __DIR__ . '/../partials/header.php';
?>

<h1>Fiche candidat</h1>

<h2><?= htmlspecialchars($candidat['nom']); ?></h2>

<p>
  <strong>Email :</strong> <?= htmlspecialchars($candidat['email']); ?><br>
  <strong>Téléphone :</strong> <?= htmlspecialchars($candidat['telephone']); ?><br>
  <strong>Localisation :</strong> <?= htmlspecialchars($candidat['ville'].' '.$candidat['pays']); ?><br>
  <strong>Expérience :</strong> <?= htmlspecialchars($candidat['annees_experience']); ?> ans
</p>

<?php if (!empty($candidat['cv'])): ?>
  <p>
    <a href="<?= htmlspecialchars($candidat['cv']); ?>" target="_blank">
      📄 Télécharger le CV
    </a>
  </p>
<?php endif; ?>

<hr>

<h3>Compétences</h3>
<ul>
<?php foreach ($competences as $c): ?>
  <li><?= htmlspecialchars($c['nom']); ?> — <em><?= strtoupper($c['niveau']); ?></em></li>
<?php endforeach; ?>
</ul>

<form method="post">
  <?= csrf_field(); ?>
  <strong>Ajouter une compétence</strong><br>
  <select name="competence_id" required>
    <?php foreach ($allSkills as $s): ?>
      <option value="<?= $s['id']; ?>"><?= htmlspecialchars($s['nom']); ?></option>
    <?php endforeach; ?>
  </select>
  <select name="niveau">
    <option value="debutant">Débutant</option>
    <option value="intermediaire">Intermédiaire</option>
    <option value="avance">Avancé</option>
  </select>
  <button name="add_skill">Ajouter</button>
</form>

<hr>

<h3>Historique des candidatures</h3>
<ul>
<?php foreach ($cands as $ca): ?>
  <li>
    <?= htmlspecialchars($ca['titre']); ?> —
    <?= htmlspecialchars($ca['nom_legal']); ?>
    (<strong><?= strtoupper($ca['statut']); ?></strong>)
  </li>
<?php endforeach; ?>
</ul>

<hr>

<h3>Notifier le candidat</h3>
<form method="post">
  <?= csrf_field(); ?>
  <select name="statut" required>
    <option value="">-- Choisir un statut --</option>
    <option value="retenu">Retenu</option>
    <option value="rejete">Rejeté</option>
  </select>
  <button name="update_statut">Envoyer notification</button>
</form>

<hr>

<h3>Recommandations de formation (IBIG)</h3>

<?php if (!$formations): ?>
  <p>Aucune recommandation pour le moment.</p>
<?php endif; ?>

<ul>
<?php foreach ($formations as $f): ?>
  <li>
    <strong><?= htmlspecialchars($f['titre']); ?></strong>
    (<?= strtoupper($f['priorite']); ?>)<br>
    <?= htmlspecialchars($f['description']); ?>
  </li>
<?php endforeach; ?>
</ul>

<form method="post">
  <?= csrf_field(); ?>
  <strong>Nouvelle recommandation</strong><br>
  <input name="titre" required placeholder="Formation recommandée"><br>
  <textarea name="description" rows="3" placeholder="Justification"></textarea><br>
  <select name="priorite">
    <option value="haute">Haute</option>
    <option value="moyenne" selected>Moyenne</option>
    <option value="basse">Basse</option>
  </select><br>
  <button name="add_reco">Ajouter</button>
</form>

<hr>

<p>
  👉 <a href="matching.php">Retour au matching</a> |
  <a href="insertions.php">Créer une insertion</a>
</p>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
