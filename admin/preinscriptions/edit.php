<?php
declare(strict_types=1);

require_once __DIR__ . '/../auth/guard.php';
require_permission('manage_preinscriptions');

require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../auth/middleware.php';

Middleware::requireAuth();

$pdo = Database::connect();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) redirect('index.php');

/* =========================
   RÉCUPÉRATION
========================= */
$stmt = $pdo->prepare("
  SELECT 
    p.*,
    f.titre AS formation
  FROM preinscriptions p
  LEFT JOIN formations f ON f.id = p.formation_id
  WHERE p.id = ?
  LIMIT 1
");
$stmt->execute([$id]);
$p = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$p) redirect('index.php');

$pageTitle  = "Modifier préinscription";
$activeMenu = "preinscriptions";

/* =========================
   TRAITEMENT POST
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();
  $nom        = trim($_POST['nom'] ?? '');
  $prenoms   = trim($_POST['prenoms'] ?? '');
  $telephone = trim($_POST['telephone'] ?? '');
  $email     = trim($_POST['email'] ?? '');
  $ville     = trim($_POST['ville'] ?? '');
  $niveau    = trim($_POST['niveau'] ?? '');
  $statut    = trim($_POST['statut'] ?? 'nouvelle');
  $message   = trim($_POST['message'] ?? '');

  if ($nom === '') {
    $error = "Le nom est obligatoire.";
  } else {
    $upd = $pdo->prepare("
      UPDATE preinscriptions SET
        nom = ?,
        prenoms = ?,
        telephone = ?,
        email = ?,
        ville = ?,
        niveau = ?,
        statut = ?,
        message = ?
      WHERE id = ?
      LIMIT 1
    ");
    $upd->execute([
      $nom,
      $prenoms,
      $telephone,
      $email,
      $ville,
      $niveau,
      $statut,
      $message,
      $id
    ]);

    redirect('view.php?id='.$id);
  }
}

ob_start();
?>

<style>
/* =========================
   UI – EDIT PRÉINSCRIPTION
========================= */
.form-grid{
  display:grid;
  grid-template-columns:repeat(2,1fr);
  gap:12px;
}
.form-group label{
  font-size:11px;
  text-transform:uppercase;
  color:#6b7280;
}
.form-group input,
.form-group select,
.form-group textarea{
  width:100%;
  padding:6px 8px;
  font-size:13px;
  border:1px solid #d1d5db;
  border-radius:6px;
}
.form-group textarea{min-height:90px}

.form-actions{
  display:flex;
  gap:8px;
  margin-top:16px;
}
.btn{
  padding:6px 12px;
  font-size:12px;
  border-radius:6px;
  text-decoration:none;
  border:0;
  cursor:pointer;
}
.btn-primary{background:#1d4ed8;color:#fff}
.btn-secondary{background:#e5e7eb;color:#111827}
.error{color:#b91c1c;font-size:12px;margin-bottom:8px}
</style>

<div class="card">

  <h2>&#9998; Modifier la préinscription</h2>

  <?php if (!empty($error)): ?>
    <div class="error"><?= e($error); ?></div>
  <?php endif; ?>

  <form method="post">
    <?= csrf_field(); ?>

    <div class="form-grid">

      <div class="form-group">
        <label>Nom *</label>
        <input type="text" name="nom" value="<?= e($p['nom']); ?>" required>
      </div>

      <div class="form-group">
        <label>Prénoms</label>
        <input type="text" name="prenoms" value="<?= e($p['prenoms']); ?>">
      </div>

      <div class="form-group">
        <label>Téléphone</label>
        <input type="text" name="telephone" value="<?= e($p['telephone']); ?>">
      </div>

      <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" value="<?= e($p['email']); ?>">
      </div>

      <div class="form-group">
        <label>Ville</label>
        <input type="text" name="ville" value="<?= e($p['ville']); ?>">
      </div>

      <div class="form-group">
        <label>Niveau</label>
        <input type="text" name="niveau" value="<?= e($p['niveau']); ?>">
      </div>

      <div class="form-group">
        <label>Statut</label>
        <select name="statut">
          <option value="nouvelle" <?= $p['statut']==='nouvelle'?'selected':''; ?>>Nouvelle</option>
          <option value="traitee" <?= $p['statut']==='traitee'?'selected':''; ?>>Traitée</option>
          <option value="rejete" <?= $p['statut']==='rejete'?'selected':''; ?>>Rejetée</option>
        </select>
      </div>

    </div>

    <div class="form-group" style="margin-top:12px">
      <label>Message</label>
      <textarea name="message"><?= e($p['message']); ?></textarea>
    </div>

    <div class="form-actions">
      <button class="btn btn-primary">Enregistrer</button>
      <a class="btn btn-secondary" href="view.php?id=<?= (int)$p['id']; ?>">Annuler</a>
    </div>

  </form>

</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout/layout.php';