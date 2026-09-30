<?php
declare(strict_types=1);

require_once __DIR__ . '/../_init.php';

/* ============================================================
   AUTH
============================================================ */
$mw = realpath(__DIR__ . '/../auth/middleware.php');
if (!$mw) { die('Middleware introuvable'); }
require_once $mw;
Middleware::requireAuth();

/* ============================================================
   META
============================================================ */
$pageTitle  = "Modifier une annonce d’emploi";
$activeMenu = "opportunites";

/* ============================================================
   DB
============================================================ */
$pdo = Database::connect();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

/* ============================================================
   HELPERS SAFE
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

function unique_slug(PDO $pdo, string $base, int $ignoreId): string {
  $slug = $base; $i = 2;
  while (true) {
    $q = $pdo->prepare(
      "SELECT id FROM opportunites_emploi WHERE slug=? AND id<>? LIMIT 1"
    );
    $q->execute([$slug, $ignoreId]);
    if (!$q->fetchColumn()) return $slug;
    $slug = $base.'-'.$i++;
  }
}

/* ============================================================
   LOAD OPPORTUNITÉ
============================================================ */
$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM opportunites_emploi WHERE id=? LIMIT 1");
$stmt->execute([$id]);
$o = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$o) {
  header('Location:index.php');
  exit;
}

/* ============================================================
   CONSTANTES
============================================================ */
$types = ['CDI','CDD','Stage','Freelance','Mission'];
$error = '';

/* ============================================================
   SUBMIT
============================================================ */
if ($_SERVER['REQUEST_METHOD']==='POST') {

  csrf_check();

  $titre   = trim($_POST['titre'] ?? '');
  $entreprise = trim($_POST['entreprise'] ?? '');
  $domaine = trim($_POST['domaine'] ?? '');
  $type    = $_POST['type'] ?? 'CDI';
  $lieu    = trim($_POST['lieu'] ?? '');

  $description = trim($_POST['description'] ?? '');
  $missions    = trim($_POST['missions'] ?? '');
  $exigences   = trim($_POST['exigences'] ?? '');
  $conditions  = trim($_POST['conditions'] ?? '');
  $instructions = trim($_POST['instructions_candidature'] ?? '');

  $lien  = trim($_POST['lien_candidature'] ?? '');
  $email = trim($_POST['email_candidature'] ?? '');
  $date_exp = trim($_POST['date_expiration'] ?? '');
  $reserve  = isset($_POST['reserve_apprenants']) ? 1 : 0;
  $slug     = trim($_POST['slug'] ?? '');

  /* ===== VALIDATION ===== */
  if ($titre==='' || $domaine==='' || $description==='') {
    $error = "Titre, domaine et description sont obligatoires.";
  } elseif ($lien==='' && $email==='') {
    $error = "Lien ou email de candidature requis.";
  } elseif ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = "Email invalide.";
  }

  /* ===== UPDATE ===== */
  if (!$error) {

    if ($slug==='') $slug = slugify($titre);
    $slug = unique_slug($pdo, $slug, $id);

    $statut = 'active';
    if ($date_exp && strtotime($date_exp) < strtotime(date('Y-m-d'))) {
      $statut = 'expiree';
    }

    $upd = $pdo->prepare("
      UPDATE opportunites_emploi SET
        titre=:t,
        entreprise=:e,
        domaine=:d,
        type=:ty,
        lieu=:l,
        description=:desc,
        missions=:m,
        exigences=:ex,
        conditions=:c,
        instructions_candidature=:ins,
        lien_candidature=:li,
        email_candidature=:em,
        reserve_apprenants=:r,
        slug=:slug,
        statut=:s,
        date_expiration=:de,
        updated_at=NOW()
      WHERE id=:id
      LIMIT 1
    ");

    $upd->execute([
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
      ':slug'=>$slug,
      ':s'=>$statut,
      ':de'=>$date_exp ?: null,
      ':id'=>$id
    ]);

    header('Location:index.php');
    exit;
  }
}

/* ============================================================
   RENDER
============================================================ */
ob_start();
?>

<style>
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
textarea{min-height:120px}
.btn-success{
  background:#16a34a;color:#fff;border:none;
  padding:12px 22px;border-radius:10px;font-weight:700
}
.alert{background:#fee2e2;color:#991b1b;padding:12px;border-radius:10px}
</style>

<div class="card">
  <h2>Modifier une annonce d’emploi</h2>

  <?php if ($error): ?><div class="alert"><?= e($error) ?></div><?php endif; ?>

  <form method="post">
    <?= csrf_field(); ?>

    <div class="section">
      <h3>Informations générales</h3>
      <div class="grid">
        <div class="full">
          <label>Titre *</label>
          <input name="titre" value="<?= e($o['titre']) ?>">
        </div>
        <div>
          <label>Entreprise</label>
          <input name="entreprise" value="<?= e($o['entreprise'] ?? '') ?>">
        </div>
        <div>
          <label>Domaine *</label>
          <input name="domaine" value="<?= e($o['domaine']) ?>">
        </div>
        <div>
          <label>Type</label>
          <select name="type">
            <?php foreach($types as $t): ?>
              <option value="<?= $t ?>" <?= $o['type']===$t?'selected':'' ?>><?= $t ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label>Lieu</label>
          <input name="lieu" value="<?= e($o['lieu'] ?? '') ?>">
        </div>
        <div>
          <label>Date limite</label>
          <input type="date" name="date_expiration" value="<?= e($o['date_expiration'] ?? '') ?>">
        </div>
      </div>
    </div>

    <div class="section"><h3>Description</h3><textarea name="description"><?= e($o['description']) ?></textarea></div>
    <div class="section"><h3>Missions</h3><textarea name="missions"><?= e($o['missions'] ?? '') ?></textarea></div>
    <div class="section"><h3>Exigences</h3><textarea name="exigences"><?= e($o['exigences'] ?? '') ?></textarea></div>
    <div class="section"><h3>Conditions</h3><textarea name="conditions"><?= e($o['conditions'] ?? '') ?></textarea></div>

    <div class="section">
      <h3>Candidature</h3>
      <div class="grid">
        <div><label>Lien</label><input name="lien_candidature" value="<?= e($o['lien_candidature'] ?? '') ?>"></div>
        <div><label>Email</label><input name="email_candidature" value="<?= e($o['email_candidature'] ?? '') ?>"></div>
        <div class="full"><label>Instructions</label><textarea name="instructions_candidature"><?= e($o['instructions_candidature'] ?? '') ?></textarea></div>
      </div>
    </div>

    <div class="section">
      <label><input type="checkbox" name="reserve_apprenants" value="1" <?= (int)$o['reserve_apprenants']===1?'checked':'' ?>> Réservé aux apprenants</label>
      <label>Slug SEO</label>
      <input name="slug" value="<?= e($o['slug']) ?>">
    </div>

    <div class="section" style="text-align:right">
      <button class="btn-success">Mettre à jour</button>
    </div>

  </form>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout/layout.php';