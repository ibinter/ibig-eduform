<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/csrf.php';

/* =========================
   SÉCURITÉ ADMIN
========================= */
if (!function_exists('auth_check') || !auth_check()) {
  header('Location: /admin/login.php');
  exit;
}

$pdo = Database::connect();

/* =========================
   LISTE DES FORMATIONS
========================= */
$formations = $pdo->query("
  SELECT id, titre
  FROM formations
  WHERE statut='active'
  ORDER BY titre
")->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   FORMATION SÉLECTIONNÉE
========================= */
$formationId = (int)($_GET['formation_id'] ?? 0);

/* =========================
   INFOS DATES FORMATION
========================= */
$formationDates = [];
if ($formationId > 0) {
  $stmt = $pdo->prepare("
    SELECT date_debut, date_fin, mois, annee, session_label
    FROM formations
    WHERE id = ?
    LIMIT 1
  ");
  $stmt->execute([$formationId]);
  $formationDates = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
}

/* =========================
   RÉCUP LANDING EXISTANTE
========================= */
function getLanding(PDO $pdo, int $formationId): array {
  if ($formationId <= 0) return [];
  $stmt = $pdo->prepare("SELECT * FROM formation_landings WHERE formation_id=? LIMIT 1");
  $stmt->execute([$formationId]);
  return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
}
$landing = getLanding($pdo, $formationId);

/* =========================
   OUTILS UPLOAD IMAGES
========================= */
function ensureDir(string $dir): void {
  if (!is_dir($dir)) mkdir($dir, 0755, true);
}

function uploadImage(string $field, string $baseName, int $formationId): ?string {
  if (empty($_FILES[$field]['name'])) return null;

  // erreurs upload
  if (!isset($_FILES[$field]['error']) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
    return null;
  }

  $allowedExt = ['jpg','jpeg','png','webp'];
  $maxSize = 3 * 1024 * 1024; // 3 Mo

  if (($_FILES[$field]['size'] ?? 0) > $maxSize) return null;

  $ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
  if (!in_array($ext, $allowedExt, true)) return null;

  $uploadDir = __DIR__ . '/../uploads/formations/' . $formationId . '/';
  ensureDir($uploadDir);

  // nom stable (remplace l’ancienne image)
  $filename = $baseName . '.' . $ext;
  $target = $uploadDir . $filename;

  if (move_uploaded_file($_FILES[$field]['tmp_name'], $target)) {
    return 'uploads/formations/' . $formationId . '/' . $filename;
  }
  return null;
}

/* =========================
   TRAITEMENT FORMULAIRE
========================= */
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  csrf_check();

  $formationId = (int)($_POST['formation_id'] ?? 0);
  if ($formationId <= 0) {
    $error = "Veuillez sélectionner une formation.";
  } else {

    // recharge landing existante (important si POST direct)
    $landing = getLanding($pdo, $formationId);

    // champs texte (ceux de ta table finale)
    $textFields = [
      'contexte',
      'hero_title','hero_subtitle','video_url',
      'pitch','pitch_marketing',
      'promesse','avantages','bonus','garantie','appel_action',

      'objectif_general','objectifs_specifiques','resultats_attendus',
      'public_cible','prerequis','contenu_programme','methodologie',
      'duree_organisation','formateurs','evaluation','moyens_logistiques',
      'tarifs','modalites_participation','contacts','validation'
    ];

    $data = [];
    foreach ($textFields as $f) {
      $data[$f] = trim((string)($_POST[$f] ?? ''));
      if ($data[$f] === '') $data[$f] = null;
    }

    // SEO (varchar)
    $data['seo_title'] = trim((string)($_POST['seo_title'] ?? '')) ?: null;
    $data['seo_description'] = trim((string)($_POST['seo_description'] ?? '')) ?: null;

    // Images : on conserve l’existant si pas de nouvel upload
    $data['hero_image'] = $landing['hero_image'] ?? null;
    $data['image_1']    = $landing['image_1'] ?? null;
    $data['image_2']    = $landing['image_2'] ?? null;
    $data['image_3']    = $landing['image_3'] ?? null;

    // Upload si fourni (écrase le chemin)
    if ($p = uploadImage('hero_image', 'hero', $formationId))   $data['hero_image'] = $p;
    if ($p = uploadImage('image_1', 'image_1', $formationId))   $data['image_1'] = $p;
    if ($p = uploadImage('image_2', 'image_2', $formationId))   $data['image_2'] = $p;
    if ($p = uploadImage('image_3', 'image_3', $formationId))   $data['image_3'] = $p;

    // Construction SQL
    $columns = array_keys($data);
    $placeholders = array_map(fn($c)=>":$c", $columns);
    $updates = array_map(fn($c)=>"$c=VALUES($c)", $columns);

    $sql = "
      INSERT INTO formation_landings (formation_id," . implode(',', $columns) . ")
      VALUES (:formation_id," . implode(',', $placeholders) . ")
      ON DUPLICATE KEY UPDATE " . implode(',', $updates) . "
    ";

    try {
      $stmt = $pdo->prepare($sql);
      $stmt->execute(array_merge(['formation_id' => $formationId], $data));
      header("Location: formation_landing_edit.php?formation_id=$formationId&saved=1");
      exit;
    } catch (Exception $e) {
      $error = "Erreur lors de l'enregistrement. Vérifiez la table/colonnes et les droits d'écriture uploads.";
    }
  }
}

// recharge landing après POST erreur éventuelle
$landing = getLanding($pdo, $formationId);

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>

<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Landing page formation — Admin</title>
<meta name="viewport" content="width=device-width,initial-scale=1">

<style>
/* =========================
   BASE
========================= */
:root{
  --bg:#020617;
  --card:#0b1220;
  --card-soft:rgba(255,255,255,.05);
  --border:rgba(255,255,255,.08);
  --text:#e5e7eb;
  --muted:#9ca3af;
  --accent:#f5a623;
  --success:#22c55e;
  --danger:#ef4444;
}

*{box-sizing:border-box}
body{
  margin:0;
  font-family:Inter,system-ui,sans-serif;
  background:linear-gradient(180deg,#020617,#020617 60%,#020617);
  color:var(--text);
  padding:110px 40px 60px;
}

/* =========================
   HEADER
========================= */
.header{
  position:fixed;
  top:0;left:0;right:0;
  background:#020617;
  border-bottom:1px solid var(--border);
  z-index:50;
}

.header-inner{
  max-width:1200px;
  margin:auto;
  padding:18px 40px;
  display:flex;
  align-items:center;
  justify-content:space-between;
}

.header h1{
  font-size:20px;
  margin:0;
  font-weight:800;
}

.header p{
  margin:2px 0 0;
  font-size:13px;
  color:var(--muted);
}

/* =========================
   BOUTONS
========================= */
.btn{
  display:inline-flex;
  align-items:center;
  gap:8px;
  padding:12px 18px;
  border-radius:999px;
  font-weight:800;
  font-size:14px;
  text-decoration:none;
  border:none;
  cursor:pointer;
}

.btn-primary{
  background:var(--accent);
  color:#111;
}

.btn-secondary{
  background:transparent;
  border:1px solid var(--border);
  color:var(--text);
}

/* =========================
   CONTENEUR
========================= */
.container{
  max-width:1200px;
  margin:auto;
}

.section{
  margin-top:26px;
  background:var(--card-soft);
  border:1px solid var(--border);
  padding:24px;
  border-radius:20px;
}

.section h2{
  margin:0 0 16px;
  font-size:16px;
  font-weight:900;
  color:var(--accent);
}

/* =========================
   FORM
========================= */
label{
  display:block;
  margin-top:14px;
  font-size:13px;
  font-weight:700;
}

input,textarea,select{
  width:100%;
  padding:13px 14px;
  margin-top:6px;
  border-radius:12px;
  border:1px solid var(--border);
  background:rgba(255,255,255,.06);
  color:var(--text);
  font-size:14px;
}

textarea{min-height:120px;resize:vertical}

input:focus,textarea:focus,select:focus{
  outline:none;
  border-color:var(--accent);
  box-shadow:0 0 0 3px rgba(245,166,35,.15);
}

/* =========================
   GRID
========================= */
.grid2{
  display:grid;
  grid-template-columns:1fr 1fr;
  gap:16px;
}
@media(max-width:900px){
  body{padding:120px 18px 40px}
  .grid2{grid-template-columns:1fr}
}

/* =========================
   ALERTES
========================= */
.success,.error{
  padding:14px 16px;
  border-radius:14px;
  margin-bottom:18px;
  font-weight:600;
}
.success{
  background:rgba(34,197,94,.15);
  border:1px solid var(--success);
}
.error{
  background:rgba(239,68,68,.15);
  border:1px solid var(--danger);
}

/* =========================
   PREVIEW IMAGE
========================= */
.preview img{
  max-width:100%;
  border-radius:14px;
  margin-top:10px;
  border:1px solid var(--border);
}

/* =========================
   ACTIONS
========================= */
.actions{
  margin-top:34px;
  display:flex;
  gap:14px;
}
</style>

</head>
<body>
    
<div class="header">
  <div class="header-inner">
    <div>
      <h1>&#128396; Landing page — Formation</h1>
      <p>Création & édition complète (Marketing, TDR, Images, SEO)</p>
    </div>
    <a href="/admin/formations/index.php" class="btn btn-secondary">
      &#8592; Retour
    </a>
  </div>
</div>

<h1>Landing page — Formation</h1>
<p>Création/édition complète (Marketing + TDR + Images + SEO).</p>

<?php if(isset($_GET['saved'])): ?>
  <div class="success">Landing page enregistrée avec succès.</div>
<?php endif; ?>

<?php if($error): ?>
  <div class="error"><?= h($error); ?></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data">
  <?= csrf_field(); ?>

<label>Formation</label>
<select name="formation_id" required onchange="location='?formation_id='+this.value">
  <option value="">— Choisir —</option>
  <?php foreach($formations as $f): ?>
    <option value="<?= (int)$f['id']; ?>" <?= $formationId==(int)$f['id']?'selected':'' ?>>
      <?= h($f['titre']); ?>
    </option>
  <?php endforeach; ?>
</select>

<div class="section">
  <h2>Hero & Images</h2>

  <div class="grid2">
    <div class="preview">
      <label>Image Hero (JPG/PNG/WEBP, 3 Mo max)</label>
      <input type="file" name="hero_image" accept=".jpg,.jpeg,.png,.webp,image/*">
      <?php if(!empty($landing['hero_image'])): ?>
        <small>Actuelle : <?= h($landing['hero_image']); ?></small>
        <img src="/<?= h($landing['hero_image']); ?>" alt="">
      <?php endif; ?>
    </div>

    <div class="preview">
      <label>Image 1</label>
      <input type="file" name="image_1" accept=".jpg,.jpeg,.png,.webp,image/*">
      <?php if(!empty($landing['image_1'])): ?>
        <small>Actuelle : <?= h($landing['image_1']); ?></small>
        <img src="/<?= h($landing['image_1']); ?>" alt="">
      <?php endif; ?>
    </div>

    <div class="preview">
      <label>Image 2</label>
      <input type="file" name="image_2" accept=".jpg,.jpeg,.png,.webp,image/*">
      <?php if(!empty($landing['image_2'])): ?>
        <small>Actuelle : <?= h($landing['image_2']); ?></small>
        <img src="/<?= h($landing['image_2']); ?>" alt="">
      <?php endif; ?>
    </div>

    <div class="preview">
      <label>Image 3</label>
      <input type="file" name="image_3" accept=".jpg,.jpeg,.png,.webp,image/*">
      <?php if(!empty($landing['image_3'])): ?>
        <small>Actuelle : <?= h($landing['image_3']); ?></small>
        <img src="/<?= h($landing['image_3']); ?>" alt="">
      <?php endif; ?>
    </div>
  </div>

  <div style="margin-top:18px">
    <label>🎬 Vidéo de présentation (optionnel)</label>
    <input type="text" name="video_url" style="width:100%" placeholder="Lien YouTube, Vimeo, ou URL d'un fichier .mp4"
           value="<?= h($landing['video_url'] ?? ''); ?>">
    <small>Collez un lien <b>YouTube</b> / <b>Vimeo</b>, ou l'URL d'un fichier <b>.mp4</b> (ex. /uploads/landing-videos/ma-video.mp4). La vidéo s'affiche dans l'en-tête de la fiche.</small>
  </div>
</div>

<div class="section">
  <h2>Hero & Marketing</h2>

  <label>Titre principal (Hero)</label>
  <input name="hero_title" value="<?= h($landing['hero_title'] ?? ''); ?>">

  <label>Sous-titre</label>
  <input name="hero_subtitle" value="<?= h($landing['hero_subtitle'] ?? ''); ?>">

  <label>Promesse</label>
  <textarea name="promesse"><?= h($landing['promesse'] ?? ''); ?></textarea>

  <label>Pitch (neutre)</label>
  <textarea name="pitch"><?= h($landing['pitch'] ?? ''); ?></textarea>

  <label>Pitch marketing (vente)</label>
  <textarea name="pitch_marketing"><?= h($landing['pitch_marketing'] ?? ''); ?></textarea>

  <label>Avantages</label>
  <textarea name="avantages"><?= h($landing['avantages'] ?? ''); ?></textarea>

  <label>Bonus</label>
  <textarea name="bonus"><?= h($landing['bonus'] ?? ''); ?></textarea>

  <label>Garantie</label>
  <textarea name="garantie"><?= h($landing['garantie'] ?? ''); ?></textarea>

  <label>Appel à l’action</label>
  <textarea name="appel_action"><?= h($landing['appel_action'] ?? ''); ?></textarea>
</div>

<?php if (!empty($formationDates)): ?>
<div class="section">
  <h2>&#128197; Dates de la formation (pilotées depuis l’ADMIN)</h2>

  <?php if (!empty($formationDates['date_debut'])): ?>
    <p>
      <strong>Démarrage :</strong>
      <?= date('d/m/Y', strtotime($formationDates['date_debut'])); ?>
      <?php if (!empty($formationDates['date_fin'])): ?>
        &nbsp;→&nbsp;
        <?= date('d/m/Y', strtotime($formationDates['date_fin'])); ?>
      <?php endif; ?>
    </p>
    <p>
      <strong>Période :</strong>
      <?= h($formationDates['mois']); ?> <?= (int)$formationDates['annee']; ?>
    </p>
    <?php if (!empty($formationDates['session_label'])): ?>
      <p>
        <strong>Session :</strong>
        <?= h($formationDates['session_label']); ?>
      </p>
    <?php endif; ?>
  <?php else: ?>
    <p style="opacity:.7">Dates non encore planifiées.</p>
  <?php endif; ?>

  <small>
    Les dates sont gérées depuis <strong>Formations → Éditer</strong>.
    Toute modification y sera automatiquement reflétée sur la landing publique.
  </small>
</div>
<?php endif; ?>

<div class="section">
  <h2>Contenu pédagogique (TDR)</h2>

  <label>Contexte & justification</label>
  <textarea name="contexte"><?= h($landing['contexte'] ?? ''); ?></textarea>

  <label>Objectif général</label>
  <textarea name="objectif_general"><?= h($landing['objectif_general'] ?? ''); ?></textarea>

  <label>Objectifs spécifiques</label>
  <textarea name="objectifs_specifiques"><?= h($landing['objectifs_specifiques'] ?? ''); ?></textarea>

  <label>Résultats attendus</label>
  <textarea name="resultats_attendus"><?= h($landing['resultats_attendus'] ?? ''); ?></textarea>

  <label>Public cible</label>
  <textarea name="public_cible"><?= h($landing['public_cible'] ?? ''); ?></textarea>

  <label>Prérequis</label>
  <textarea name="prerequis"><?= h($landing['prerequis'] ?? ''); ?></textarea>

  <label>Contenu / programme</label>
  <textarea name="contenu_programme"><?= h($landing['contenu_programme'] ?? ''); ?></textarea>

  <label>Méthodologie pédagogique</label>
  <textarea name="methodologie"><?= h($landing['methodologie'] ?? ''); ?></textarea>

  <label>Durée & organisation</label>
  <textarea name="duree_organisation"><?= h($landing['duree_organisation'] ?? ''); ?></textarea>
</div>

<div class="section">
  <h2>Organisation & Logistique</h2>

  <label>Formateurs / intervenants</label>
  <textarea name="formateurs"><?= h($landing['formateurs'] ?? ''); ?></textarea>

  <label>Modalités d’évaluation</label>
  <textarea name="evaluation"><?= h($landing['evaluation'] ?? ''); ?></textarea>

  <label>Moyens logistiques</label>
  <textarea name="moyens_logistiques"><?= h($landing['moyens_logistiques'] ?? ''); ?></textarea>
</div>

<div class="section">
  <h2>Tarifs & Participation</h2>

  <label>Tarifs & conditions</label>
  <textarea name="tarifs"><?= h($landing['tarifs'] ?? ''); ?></textarea>

  <label>Modalités de participation</label>
  <textarea name="modalites_participation"><?= h($landing['modalites_participation'] ?? ''); ?></textarea>

  <label>Contacts</label>
  <textarea name="contacts"><?= h($landing['contacts'] ?? ''); ?></textarea>

  <label>Validation / approbation</label>
  <textarea name="validation"><?= h($landing['validation'] ?? ''); ?></textarea>
</div>

<div class="section">
  <h2>SEO</h2>

  <label>SEO Title</label>
  <input name="seo_title" value="<?= h($landing['seo_title'] ?? ''); ?>">

  <label>SEO Description</label>
  <textarea name="seo_description"><?= h($landing['seo_description'] ?? ''); ?></textarea>
</div>

<div class="actions">
  <button type="submit" class="btn btn-primary">
    &#128190; Enregistrer la landing page
  </button>
</div>
</form>

</body>
</html>
