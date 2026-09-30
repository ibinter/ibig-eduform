<?php
declare(strict_types=1);

/**
 * ============================================================
 * ADMIN – MODIFIER UNE FORMATION (VERSION FINALE STABLE)
 * Fichier : /admin/formations/edit.php   (exemple)
 * ============================================================
 * - PDO strict
 * - Sécurité OK
 * - UI premium compacte
 * - Emojis UNIQUEMENT en HTML (incorruptibles)
 * - Champ paiement_lien (URL) ajouté
 * ============================================================
 */

ob_start();

/* ================= BOOTSTRAP & SÉCURITÉ ================= */
require_once __DIR__ . '/../_init.php';
require_once __DIR__ . '/../auth/middleware.php';

Middleware::requireAuth();

/* ================= MÉTADONNÉES PAGE ================= */
$pageTitle  = "Modifier la formation";
$activeMenu = "formations";

/* ================= DB ================= */
$pdo = Database::connect();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

/* ================= HELPERS ================= */
if (!function_exists('e')) {
  function e($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
  }
}

function slugify(string $text): string {
  $text = trim($text);
  $text = mb_strtolower($text, 'UTF-8');
  $text = preg_replace('/[^\p{L}\p{Nd}]+/u', '-', $text);
  return trim((string)$text, '-');
}

/** Valide une date YYYY-MM-DD (retourne null si vide) */
function date_or_null($v): ?string {
  $v = trim((string)$v);
  if ($v === '') return null;
  $dt = DateTime::createFromFormat('Y-m-d', $v);
  if (!$dt || $dt->format('Y-m-d') !== $v) return null;
  return $v;
}

/* ================= CHARGEMENT FORMATION ================= */
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  http_response_code(400);
  die('ID de formation invalide');
}

$stmt = $pdo->prepare("SELECT * FROM formations WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$f = $stmt->fetch();

if (!$f) {
  http_response_code(404);
  die('Formation introuvable');
}

$error = '';
$success = '';

/* ================= TRAITEMENT POST ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();
  try {
    // Champs texte
    $titre          = trim($_POST['titre'] ?? '');
    $slug           = trim($_POST['slug'] ?? '');
    $domaine        = trim($_POST['domaine'] ?? '');
    $typeCertificat = trim($_POST['type_certificat'] ?? '');
    $description    = trim($_POST['description'] ?? '');
    $objectif       = trim($_POST['objectif_general'] ?? '');
    $modules        = trim($_POST['modules'] ?? '');
    $duree          = trim($_POST['duree'] ?? '');
    $session        = trim($_POST['session_label'] ?? '');
    $mois           = trim($_POST['mois'] ?? '');

    // Champs select
    $mode   = $_POST['mode'] ?? 'presentiel';
    $statut = $_POST['statut'] ?? 'inactive';

    $allowedModes = ['presentiel','en_ligne','hybride'];
    if (!in_array($mode, $allowedModes, true)) $mode = 'presentiel';

    $allowedStatuts = ['inactive','active'];
    if (!in_array($statut, $allowedStatuts, true)) $statut = 'inactive';

    // Tarifs
    $tarifPresentiel  = (int)($_POST['tarif_presentiel'] ?? 0);
    $tarifEnLigne     = (int)($_POST['tarif_en_ligne'] ?? 0);
    $tarifHybride     = (int)($_POST['tarif_hybride'] ?? 0);
    $fraisInscription = (int)($_POST['frais_inscription'] ?? 0);

    if ($tarifPresentiel < 0)  $tarifPresentiel = 0;
    if ($tarifEnLigne < 0)     $tarifEnLigne = 0;
    if ($tarifHybride < 0)     $tarifHybride = 0;
    if ($fraisInscription < 0) $fraisInscription = 0;

    // Paiement lien
    $paiementLien = trim($_POST['paiement_lien'] ?? '');
    if ($paiementLien !== '' && !filter_var($paiementLien, FILTER_VALIDATE_URL)) {
      throw new RuntimeException('Lien de paiement invalide.');
    }

    // Session / dates
    $dateDebut = date_or_null($_POST['date_debut'] ?? '');
    $dateFin   = date_or_null($_POST['date_fin'] ?? '');

    // Année
    $annee = (int)($_POST['annee'] ?? date('Y'));
    if ($annee < 2000 || $annee > 2100) $annee = (int)date('Y');

    // Validations minimales
    if ($titre === '' || $domaine === '' || $typeCertificat === '') {
      throw new RuntimeException('Titre, domaine et type de certificat obligatoires.');
    }

    // Slug
    $slug = $slug !== '' ? slugify($slug) : slugify($titre);
    if ($slug === '') {
      throw new RuntimeException('Slug invalide.');
    }

    // Unicité slug
    $check = $pdo->prepare("SELECT id FROM formations WHERE slug = ? AND id <> ? LIMIT 1");
    $check->execute([$slug, $id]);
    if ($check->fetch()) {
      throw new RuntimeException('Slug déjà utilisé.');
    }

    // Cohérence dates (si les 2 existent)
    if ($dateDebut && $dateFin && $dateFin < $dateDebut) {
      throw new RuntimeException('La date de fin ne peut pas être antérieure à la date de début.');
    }

    // UPDATE
    $update = $pdo->prepare("
      UPDATE formations SET
        titre = :titre,
        slug = :slug,
        domaine = :domaine,
        type_certificat = :type_certificat,
        description = :description,
        objectif_general = :objectif,
        modules = :modules,
        duree = :duree,
        mode = :mode,
        tarif_presentiel = :tp,
        tarif_en_ligne = :tel,
        tarif_hybride = :th,
        frais_inscription = :fi,
        paiement_lien = :pl,
        date_debut = :dd,
        date_fin = :df,
        mois = :mois,
        annee = :annee,
        session_label = :session,
        statut = :statut,
        updated_at = NOW()
      WHERE id = :id
      LIMIT 1
    ");

    $update->execute([
      ':titre'   => $titre,
      ':slug'    => $slug,
      ':domaine' => $domaine,
      ':type_certificat' => $typeCertificat,
      ':description' => $description,
      ':objectif' => $objectif,
      ':modules'  => $modules,
      ':duree'    => $duree,
      ':mode'     => $mode,
      ':tp'       => $tarifPresentiel,
      ':tel'      => $tarifEnLigne,
      ':th'       => $tarifHybride,
      ':fi'       => $fraisInscription,
      ':pl'       => $paiementLien,
      ':dd'       => $dateDebut,
      ':df'       => $dateFin,
      ':mois'     => $mois,
      ':annee'    => $annee,
      ':session'  => $session,
      ':statut'   => $statut,
      ':id'       => $id
    ]);

    $success = 'Modifications enregistrées avec succès.';

    // Reload formation
    $stmt->execute([$id]);
    $f = $stmt->fetch() ?: $f;

  } catch (Throwable $e) {
    $error = $e->getMessage();
  }
}

/* ================= UI (CONTENT) ================= */
ob_start();
?>

<style>
  .card{background:#fff;border:1px solid #e6eaf2;border-radius:16px;padding:18px;box-shadow:0 10px 30px rgba(15,23,42,.06)}
  .card h2{margin:0 0 12px;font-size:18px;font-weight:900;color:#0f172a}
  .pill{display:inline-flex;align-items:center;gap:8px;border-radius:999px;padding:10px 12px;font-weight:800;font-size:13px;margin:10px 0}
  .pill.ok{background:#ecfdf5;color:#047857;border:1px solid #a7f3d0}
  .pill.wait{background:#fff7ed;color:#9a3412;border:1px solid #fed7aa}
  .form{display:block}
  .form label{display:block;font-weight:800;font-size:12px;margin:10px 0 6px;color:#0f172a}
  .form input,.form textarea,.form select{
    width:100%;padding:11px 12px;border-radius:12px;border:1px solid #e5e7eb;
    outline:none;background:#fff;font-size:14px;color:#0f172a
  }
  .form input:focus,.form textarea:focus,.form select:focus{border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.12)}
  textarea{min-height:96px;resize:vertical}
  .form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
  .h3{margin:14px 0 8px;font-size:14px;font-weight:900;color:#0f172a}
  .muted{color:#64748b;font-size:12px}
  .actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:14px}
  .btn{display:inline-flex;align-items:center;gap:10px;padding:11px 14px;border-radius:12px;font-weight:900;border:1px solid transparent;text-decoration:none;cursor:pointer}
  .btn-primary{background:#2563eb;color:#fff}
  .btn-primary:hover{filter:brightness(.96)}
  .btn-secondary{background:#fff;color:#0f172a;border-color:#e5e7eb}
  .btn-secondary:hover{background:#f8fafc}
  @media (max-width: 820px){.form-grid{grid-template-columns:1fr}}
</style>

<div class="card">

  <h2>&#9998; Modifier la formation</h2>
  <div class="muted">ID : <?= (int)$id ?></div>

  <?php if ($error): ?>
    <div class="pill wait">&#9888; <?= e($error); ?></div>
  <?php elseif ($success): ?>
    <div class="pill ok">&#10004; <?= e($success); ?></div>
  <?php endif; ?>

  <form method="post" class="form" autocomplete="off">
    <?= csrf_field(); ?>

    <div class="form-grid">
      <div>
        <label>Titre *</label>
        <input name="titre" value="<?= e($f['titre'] ?? ''); ?>" required>
      </div>
      <div>
        <label>Slug</label>
        <input name="slug" value="<?= e($f['slug'] ?? ''); ?>" placeholder="auto si vide">
      </div>
      <div>
        <label>Domaine *</label>
        <input name="domaine" value="<?= e($f['domaine'] ?? ''); ?>" required>
      </div>
      <div>
        <label>Type de certificat *</label>
        <input name="type_certificat" value="<?= e($f['type_certificat'] ?? ''); ?>" required>
      </div>
    </div>

    <label>Description</label>
    <textarea name="description"><?= e($f['description'] ?? ''); ?></textarea>

    <label>Objectif général</label>
    <textarea name="objectif_general"><?= e($f['objectif_general'] ?? ''); ?></textarea>

    <label>Programme</label>
    <textarea name="modules"><?= e($f['modules'] ?? ''); ?></textarea>

    <div class="form-grid">
      <div>
        <label>Durée</label>
        <input name="duree" value="<?= e($f['duree'] ?? ''); ?>" placeholder="Ex: 45h / 1 mois">
      </div>
      <div>
        <label>Mode</label>
        <select name="mode">
          <option value="presentiel" <?= (($f['mode'] ?? '')==='presentiel')?'selected':''; ?>>Présentiel</option>
          <option value="en_ligne" <?= (($f['mode'] ?? '')==='en_ligne')?'selected':''; ?>>En ligne</option>
          <option value="hybride" <?= (($f['mode'] ?? '')==='hybride')?'selected':''; ?>>Hybride</option>
        </select>
      </div>
    </div>

    <div class="h3">&#128176; Tarifs</div>
    <div class="form-grid">
      <div>
        <label>Tarif présentiel (FCFA)</label>
        <input type="number" min="0" name="tarif_presentiel" value="<?= (int)($f['tarif_presentiel'] ?? 0); ?>">
      </div>
      <div>
        <label>Tarif en ligne (FCFA)</label>
        <input type="number" min="0" name="tarif_en_ligne" value="<?= (int)($f['tarif_en_ligne'] ?? 0); ?>">
      </div>
      <div>
        <label>Tarif hybride (FCFA)</label>
        <input type="number" min="0" name="tarif_hybride" value="<?= (int)($f['tarif_hybride'] ?? 0); ?>">
      </div>
      <div>
        <label>Frais d’inscription (FCFA)</label>
        <input type="number" min="0" name="frais_inscription" value="<?= (int)($f['frais_inscription'] ?? 0); ?>">
      </div>
    </div>

    <label>Lien de paiement</label>
    <input name="paiement_lien" value="<?= e($f['paiement_lien'] ?? ''); ?>" placeholder="https://...">

    <div class="h3">&#128197; Session</div>
    <div class="form-grid">
      <div>
        <label>Date début</label>
        <input type="date" name="date_debut" value="<?= e($f['date_debut'] ?? ''); ?>">
      </div>
      <div>
        <label>Date fin</label>
        <input type="date" name="date_fin" value="<?= e($f['date_fin'] ?? ''); ?>">
      </div>
      <div>
        <label>Mois</label>
        <input name="mois" value="<?= e($f['mois'] ?? ''); ?>" placeholder="Ex: Février">
      </div>
      <div>
        <label>Année</label>
        <input type="number" name="annee" value="<?= (int)($f['annee'] ?? date('Y')); ?>" min="2000" max="2100">
      </div>
    </div>

    <label>Libellé session</label>
    <input name="session_label" value="<?= e($f['session_label'] ?? ''); ?>" placeholder="Ex: Session 1 - 2026">

    <div class="form-grid">
      <div>
        <label>Statut</label>
        <select name="statut">
          <option value="inactive" <?= (($f['statut'] ?? '')==='inactive')?'selected':''; ?>>Inactive</option>
          <option value="active" <?= (($f['statut'] ?? '')==='active')?'selected':''; ?>>Active</option>
        </select>
      </div>
      <div>
        <label>&nbsp;</label>
        <div class="muted">Astuce : laisse “Slug” vide pour qu’il soit généré automatiquement à partir du titre.</div>
      </div>
    </div>

    <div class="actions">
      <button class="btn btn-primary" type="submit">&#128190; Mettre &agrave; jour</button>
      <a href="index.php" class="btn btn-secondary">&#8592; Retour</a>
    </div>

  </form>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout/layout.php';
