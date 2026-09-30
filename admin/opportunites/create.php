<?php
declare(strict_types=1);

require_once __DIR__ . '/../_init.php';

/* ============================================================
   AUTH / MIDDLEWARE
============================================================ */
$mw = realpath(__DIR__ . '/../auth/middleware.php');
if (!$mw) { die('Middleware introuvable'); }
require_once $mw;
Middleware::requireAuth();

/* ============================================================
   META
============================================================ */
$pageTitle  = 'Créer une annonce d’emploi';
$activeMenu = 'opportunites';

/* ============================================================
   DB
============================================================ */
$pdo = Database::connect();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

/* ============================================================
   HELPERS
============================================================ */
if (!function_exists('e')) {
  function e(string $v): string {
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
  }
}

function slugify(string $s): string {
  $s = trim($s);
  if ($s === '') return 'opportunite';
  $s = mb_strtolower($s, 'UTF-8');
  $s = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s) ?: $s;
  $s = preg_replace('~[^a-z0-9]+~', '-', $s);
  return trim($s, '-') ?: 'opportunite';
}

function unique_slug(PDO $pdo, string $base): string {
  $slug = $base; $i = 2;
  while (true) {
    $st = $pdo->prepare("SELECT id FROM opportunites_emploi WHERE slug=? LIMIT 1");
    $st->execute([$slug]);
    if (!$st->fetchColumn()) return $slug;
    $slug = $base . '-' . $i++;
  }
}

/* ============================================================
   DEFAULTS
============================================================ */
$error = '';

$titre=$entreprise=$domaine=$lieu=$slug='';
$type='CDI';
$date_expiration='';

$description=$missions=$exigences=$conditions=$instructions='';
$lien=$email='';
$reserve=0;

$allowedTypes = ['CDI','CDD','Stage','Freelance','Mission'];

/* ============================================================
   SUBMIT
============================================================ */
if ($_SERVER['REQUEST_METHOD']==='POST') {

  csrf_check();

  $titre = trim($_POST['titre'] ?? '');
  $entreprise = trim($_POST['entreprise'] ?? '');
  $domaine = trim($_POST['domaine'] ?? '');
  $type = $_POST['type'] ?? 'CDI';
  $lieu = trim($_POST['lieu'] ?? '');

  $description = trim($_POST['description'] ?? '');
  $missions = trim($_POST['missions'] ?? '');
  $exigences = trim($_POST['exigences'] ?? '');
  $conditions = trim($_POST['conditions'] ?? '');
  $instructions = trim($_POST['instructions_candidature'] ?? '');

  $lien = trim($_POST['lien_candidature'] ?? '');
  $email = trim($_POST['email_candidature'] ?? '');
  $date_expiration = trim($_POST['date_expiration'] ?? '');
  $reserve = isset($_POST['reserve_apprenants']) ? 1 : 0;
  $slug = trim($_POST['slug'] ?? '');

  /* VALIDATION */
  if ($titre==='' || $domaine==='' || $description==='') {
    $error = "Titre, domaine et description sont obligatoires.";
  } elseif (!in_array($type,$allowedTypes,true)) {
    $error = "Type de contrat invalide.";
  } elseif ($lien==='' && $email==='') {
    $error = "Lien ou email de candidature requis.";
  } elseif ($email && !filter_var($email,FILTER_VALIDATE_EMAIL)) {
    $error = "Email invalide.";
  }

  /* INSERT */
  if (!$error) {

    if ($slug==='') $slug = slugify($titre);
    $slug = unique_slug($pdo,$slug);

    /* STATUT AUTO COMME SITE PUBLIC */
    $statut = 'active';
    if ($date_expiration && strtotime($date_expiration) < strtotime(date('Y-m-d'))) {
      $statut = 'expiree';
    }

    $stmt = $pdo->prepare("
      INSERT INTO opportunites_emploi (
        titre, entreprise, domaine, type, lieu,
        description, missions, exigences, conditions,
        instructions_candidature,
        lien_candidature, email_candidature,
        reserve_apprenants,
        statut, slug, date_expiration,
        created_at
      ) VALUES (
        :t,:e,:d,:ty,:l,
        :desc,:m,:ex,:c,
        :ins,
        :li,:em,
        :r,
        :s,:slug,:de,
        NOW()
      )
    ");

    $stmt->execute([
      ':t'=>$titre,
      ':e'=>$entreprise ?: null,
      ':d'=>$domaine,
      ':ty'=>$type,
      ':l'=>$lieu ?: null,
      ':desc'=>$description,
      ':m'=>$missions ?: null,
      ':ex'=>$exigences ?: null,
      ':c'=>$conditions ?: null,
      ':ins'=>$instructions ?: null,
      ':li'=>$lien ?: null,
      ':em'=>$email ?: null,
      ':r'=>$reserve,
      ':s'=>$statut,
      ':slug'=>$slug,
      ':de'=>$date_expiration ?: null
    ]);

    header('Location: index.php');
    exit;
  }
}

ob_start();
?>

<style>
/* =========================================================
   CREATE EMPLOI — STYLE PROFESSIONNEL
========================================================= */
.card{background:#fff;border-radius:16px;padding:22px;max-width:1200px}
.section{margin-top:26px}
.section h3{margin:0 0 12px;font-size:18px}
.grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.full{grid-column:1/-1}
label{font-weight:600;margin-bottom:6px;display:block}
input,select,textarea{
  width:100%;padding:11px 13px;border-radius:10px;
  border:1px solid #d1d5db;font-size:14px
}
textarea{min-height:120px;resize:vertical}
input:focus,select:focus,textarea:focus{
  outline:none;border-color:#2563eb;
  box-shadow:0 0 0 2px rgba(37,99,235,.15)
}
.btn-success{
  background:#16a34a;color:#fff;border:none;
  padding:12px 22px;border-radius:10px;
  font-weight:700;cursor:pointer
}
.alert{background:#fee2e2;color:#991b1b;padding:12px;border-radius:10px;margin-top:14px}
</style>

<div class="card">

  <h2>Créer une annonce d’emploi</h2>

  <?php if ($error): ?><div class="alert"><?= e($error) ?></div><?php endif; ?>

  <form method="post">
    <?= csrf_field(); ?>

    <!-- INFOS GÉNÉRALES -->
    <div class="section">
      <h3>Informations générales</h3>
      <div class="grid">
        <div class="full">
          <label>Titre du poste *</label>
          <input name="titre" value="<?= e($titre) ?>" required>
        </div>
        <div>
          <label>Entreprise</label>
          <input name="entreprise" value="<?= e($entreprise) ?>">
        </div>
        <div>
          <label>Domaine *</label>
          <input name="domaine" value="<?= e($domaine) ?>" required>
        </div>
        <div>
          <label>Type de contrat</label>
          <select name="type">
            <?php foreach($allowedTypes as $t): ?>
              <option value="<?= $t ?>" <?= $type===$t?'selected':'' ?>><?= $t ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label>Lieu</label>
          <input name="lieu" value="<?= e($lieu) ?>">
        </div>
        <div>
          <label>Date limite</label>
          <input type="date" name="date_expiration" value="<?= e($date_expiration) ?>">
        </div>
      </div>
    </div>

    <!-- CONTENU DU POSTE -->
    <div class="section">
      <h3>Description du poste</h3>
      <textarea name="description"><?= e($description) ?></textarea>
    </div>

    <div class="section">
      <h3>Missions principales</h3>
      <textarea name="missions"><?= e($missions) ?></textarea>
    </div>

    <div class="section">
      <h3>Profil & exigences</h3>
      <textarea name="exigences"><?= e($exigences) ?></textarea>
    </div>

    <div class="section">
      <h3>Conditions & avantages</h3>
      <textarea name="conditions"><?= e($conditions) ?></textarea>
    </div>

    <!-- CANDIDATURE -->
    <div class="section">
      <h3>Modalités de candidature</h3>
      <div class="grid">
        <div>
          <label>Lien candidature</label>
          <input type="url" name="lien_candidature" value="<?= e($lien) ?>">
        </div>
        <div>
          <label>Email candidature</label>
          <input type="email" name="email_candidature" value="<?= e($email) ?>">
        </div>
        <div class="full">
          <label>Instructions particulières</label>
          <textarea name="instructions_candidature"><?= e($instructions) ?></textarea>
        </div>
      </div>
    </div>

    <!-- OPTIONS ADMIN -->
    <div class="section">
      <h3>Options internes</h3>
      <label>
        <input type="checkbox" name="reserve_apprenants" value="1" <?= $reserve?'checked':'' ?>>
        Réservé aux apprenants
      </label>
      <label>Slug SEO</label>
      <input name="slug" value="<?= e($slug) ?>">
    </div>

    <div class="section" style="text-align:right">
      <button class="btn-success">Publier l’annonce</button>
    </div>

  </form>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout/layout.php';