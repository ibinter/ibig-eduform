<?php
declare(strict_types=1);

ob_start();

/**
 * ADMIN – MODIFIER UNE FORMATION
 */

/* ================= BOOTSTRAP & SÉCURITÉ ================= */
require_once __DIR__ . '/../_init.php';

Middleware::requireAuth();

/* ================= MÉTADONNÉES PAGE ================= */
$pageTitle  = "Modifier la formation";
$activeMenu = "formations";

/* ================= DB ================= */
$pdo = Database::connect();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

/* ================= HELPERS ================= */
if (!function_exists('slugify')) {
  function slugify(string $text): string {
    $text = trim($text);
    $text = mb_strtolower($text, 'UTF-8');
    $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
    $text  = $ascii !== false ? $ascii : $text;
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim((string)$text, '-');
  }
}

if (!function_exists('date_or_null')) {
  function date_or_null($v): ?string {
    $v = trim((string)$v);
    if ($v === '') return null;
    $dt = DateTime::createFromFormat('Y-m-d', $v);
    if (!$dt || $dt->format('Y-m-d') !== $v) return null;
    return $v;
  }
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

$error   = '';
$success = '';

/* ================= TRAITEMENT POST ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();
  try {
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

    $mode   = $_POST['mode'] ?? 'presentiel';
    $statut = $_POST['statut'] ?? 'inactive';

    $allowedModes = ['presentiel','en_ligne','hybride'];
    if (!in_array($mode, $allowedModes, true)) $mode = 'presentiel';

    $allowedStatuts = ['inactive','active'];
    if (!in_array($statut, $allowedStatuts, true)) $statut = 'inactive';

    $isSamediPro = isset($_POST['is_samedi_pro']) ? 1 : 0;

    $tarifPresentiel  = (int)($_POST['tarif_presentiel'] ?? 0);
    $tarifEnLigne     = (int)($_POST['tarif_en_ligne'] ?? 0);
    $tarifHybride     = (int)($_POST['tarif_hybride'] ?? 0);
    $fraisInscription = (int)($_POST['frais_inscription'] ?? 0);

    if ($tarifPresentiel < 0)  $tarifPresentiel = 0;
    if ($tarifEnLigne < 0)     $tarifEnLigne = 0;
    if ($tarifHybride < 0)     $tarifHybride = 0;
    if ($fraisInscription < 0) $fraisInscription = 0;

    $paiementLien = trim($_POST['paiement_lien'] ?? '');
    if ($paiementLien !== '' && !filter_var($paiementLien, FILTER_VALIDATE_URL)) {
      throw new RuntimeException('Lien de paiement invalide.');
    }

    $dateDebut = date_or_null($_POST['date_debut'] ?? '');
    $dateFin   = date_or_null($_POST['date_fin'] ?? '');

    $annee = (int)($_POST['annee'] ?? date('Y'));
    if ($annee < 2000 || $annee > 2100) $annee = (int)date('Y');

    if ($titre === '' || $domaine === '' || $typeCertificat === '') {
      throw new RuntimeException('Titre, domaine et type de certificat obligatoires.');
    }

    $slug = $slug !== '' ? slugify($slug) : slugify($titre);
    if ($slug === '') {
      throw new RuntimeException('Slug invalide.');
    }

    $check = $pdo->prepare("SELECT id FROM formations WHERE slug = ? AND id <> ? LIMIT 1");
    $check->execute([$slug, $id]);
    if ($check->fetch()) {
      throw new RuntimeException('Slug déjà utilisé.');
    }

    if ($dateDebut && $dateFin && $dateFin < $dateDebut) {
      throw new RuntimeException('La date de fin ne peut pas être antérieure à la date de début.');
    }

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
        is_samedi_pro = :isp,
        updated_at = NOW()
      WHERE id = :id
      LIMIT 1
    ");

    $update->execute([
      ':titre'           => $titre,
      ':slug'            => $slug,
      ':domaine'         => $domaine,
      ':type_certificat' => $typeCertificat,
      ':description'     => $description,
      ':objectif'        => $objectif,
      ':modules'         => $modules,
      ':duree'           => $duree,
      ':mode'            => $mode,
      ':tp'              => $tarifPresentiel,
      ':tel'             => $tarifEnLigne,
      ':th'              => $tarifHybride,
      ':fi'              => $fraisInscription,
      ':pl'              => $paiementLien,
      ':dd'              => $dateDebut,
      ':df'              => $dateFin,
      ':mois'            => $mois,
      ':annee'           => $annee,
      ':session'         => $session,
      ':statut'          => $statut,
      ':isp'             => $isSamediPro,
      ':id'              => $id,
    ]);

    $success = 'Modifications enregistrées avec succès.';

    $stmt->execute([$id]);
    $f = $stmt->fetch() ?: $f;

  } catch (Throwable $e) {
    $error = $e->getMessage();
  }
}

/* ================= UI ================= */
ob_start();
?>
<style>
  .edit-form-card{background:#fff;border:1px solid #e6eaf2;border-radius:16px;padding:24px;box-shadow:0 10px 30px rgba(15,23,42,.06)}
  .edit-form-card h2{margin:0 0 4px;font-size:18px;font-weight:900;color:#0f172a}
  .edit-form-card .sub{color:#64748b;font-size:12px;margin-bottom:16px}
  .ef-pill{display:inline-flex;align-items:center;gap:8px;border-radius:999px;padding:10px 14px;font-weight:700;font-size:13px;margin:0 0 14px}
  .ef-pill.ok{background:#ecfdf5;color:#047857;border:1px solid #a7f3d0}
  .ef-pill.err{background:#fff7ed;color:#9a3412;border:1px solid #fed7aa}
  .ef-section{font-size:13px;font-weight:900;color:#0f172a;margin:20px 0 10px;padding-bottom:6px;border-bottom:2px solid #e5e7eb}
  .ef-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;margin-bottom:14px}
  .ef-field{display:flex;flex-direction:column;gap:5px}
  .ef-field label{font-size:12px;font-weight:700;color:#374151}
  .ef-field input:not([type=checkbox]),
  .ef-field textarea,
  .ef-field select{width:100%;padding:10px 12px;border-radius:10px;border:1px solid #d1d5db;font-size:14px;color:#0f172a;background:#fff;box-sizing:border-box;font-family:inherit}
  .ef-field input:focus,.ef-field textarea:focus,.ef-field select:focus{outline:none;border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.12)}
  .ef-field textarea{min-height:90px;resize:vertical}
  .ef-field select{appearance:auto}
  .ef-check{display:flex;align-items:center;gap:8px;cursor:pointer;font-size:14px;font-weight:600;color:#0f172a;margin-top:6px}
  .ef-check input[type=checkbox]{width:16px;height:16px;cursor:pointer}
  .ef-hint{font-size:11px;color:#64748b;margin-top:4px}
  .ef-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:24px;padding-top:16px;border-top:1px solid #e5e7eb}
  .ef-btn{display:inline-flex;align-items:center;gap:8px;padding:11px 18px;border-radius:10px;font-weight:800;font-size:14px;border:1px solid transparent;text-decoration:none;cursor:pointer;transition:filter .15s}
  .ef-btn-primary{background:#2563eb;color:#fff}
  .ef-btn-primary:hover{filter:brightness(.92)}
  .ef-btn-secondary{background:#f8fafc;color:#0f172a;border-color:#e5e7eb}
  .ef-btn-secondary:hover{background:#f1f5f9}
  @media(max-width:820px){.ef-grid{grid-template-columns:1fr}}
</style>

<div class="edit-form-card">
  <h2>&#9998; Modifier la formation</h2>
  <div class="sub">ID : <?= (int)$id ?></div>

  <?php if ($error): ?>
    <div class="ef-pill err">&#9888; <?= e($error) ?></div>
  <?php elseif ($success): ?>
    <div class="ef-pill ok">&#10004; <?= e($success) ?></div>
  <?php endif; ?>

  <form method="post" autocomplete="off">
    <?= csrf_field() ?>

    <div class="ef-section">Informations générales</div>
    <div class="ef-grid">
      <div class="ef-field">
        <label>Titre *</label>
        <input name="titre" value="<?= e($f['titre'] ?? '') ?>" required>
      </div>
      <div class="ef-field">
        <label>Slug</label>
        <input name="slug" value="<?= e($f['slug'] ?? '') ?>" placeholder="auto si vide">
      </div>
      <div class="ef-field">
        <label>Domaine *</label>
        <input name="domaine" value="<?= e($f['domaine'] ?? '') ?>" required>
      </div>
      <div class="ef-field">
        <label>Type de certificat *</label>
        <input name="type_certificat" value="<?= e($f['type_certificat'] ?? '') ?>" required>
      </div>
    </div>

    <div class="ef-field" style="margin-bottom:14px">
      <label>Description</label>
      <textarea name="description"><?= e($f['description'] ?? '') ?></textarea>
    </div>
    <div class="ef-field" style="margin-bottom:14px">
      <label>Objectif général</label>
      <textarea name="objectif_general"><?= e($f['objectif_general'] ?? '') ?></textarea>
    </div>
    <div class="ef-field" style="margin-bottom:14px">
      <label>Programme</label>
      <textarea name="modules" style="min-height:120px"><?= e($f['modules'] ?? '') ?></textarea>
    </div>

    <div class="ef-section">Modalités</div>
    <div class="ef-grid">
      <div class="ef-field">
        <label>Durée</label>
        <input name="duree" value="<?= e($f['duree'] ?? '') ?>" placeholder="Ex: 45h / 1 mois">
      </div>
      <div class="ef-field">
        <label>Mode</label>
        <select name="mode">
          <option value="presentiel" <?= ($f['mode'] ?? '') === 'presentiel' ? 'selected' : '' ?>>Présentiel</option>
          <option value="en_ligne"   <?= ($f['mode'] ?? '') === 'en_ligne'   ? 'selected' : '' ?>>En ligne</option>
          <option value="hybride"    <?= ($f['mode'] ?? '') === 'hybride'    ? 'selected' : '' ?>>Hybride</option>
        </select>
      </div>
    </div>

    <div class="ef-section">&#128176; Tarifs</div>
    <div class="ef-grid">
      <div class="ef-field">
        <label>Tarif présentiel (FCFA)</label>
        <input type="number" min="0" name="tarif_presentiel" value="<?= (int)($f['tarif_presentiel'] ?? 0) ?>">
      </div>
      <div class="ef-field">
        <label>Tarif en ligne (FCFA)</label>
        <input type="number" min="0" name="tarif_en_ligne" value="<?= (int)($f['tarif_en_ligne'] ?? 0) ?>">
      </div>
      <div class="ef-field">
        <label>Tarif hybride (FCFA)</label>
        <input type="number" min="0" name="tarif_hybride" value="<?= (int)($f['tarif_hybride'] ?? 0) ?>">
      </div>
      <div class="ef-field">
        <label>Frais d'inscription (FCFA)</label>
        <input type="number" min="0" name="frais_inscription" value="<?= (int)($f['frais_inscription'] ?? 0) ?>">
      </div>
    </div>
    <div class="ef-field" style="margin-bottom:14px">
      <label>Lien de paiement</label>
      <input name="paiement_lien" value="<?= e($f['paiement_lien'] ?? '') ?>" placeholder="https://...">
    </div>

    <div class="ef-section">&#128197; Session</div>
    <div class="ef-grid">
      <div class="ef-field">
        <label>Date début</label>
        <input type="date" name="date_debut" value="<?= e($f['date_debut'] ?? '') ?>">
      </div>
      <div class="ef-field">
        <label>Date fin</label>
        <input type="date" name="date_fin" value="<?= e($f['date_fin'] ?? '') ?>">
      </div>
      <div class="ef-field">
        <label>Mois</label>
        <input name="mois" value="<?= e($f['mois'] ?? '') ?>" placeholder="Ex: Octobre">
      </div>
      <div class="ef-field">
        <label>Année</label>
        <input type="number" name="annee" value="<?= (int)($f['annee'] ?? date('Y')) ?>" min="2000" max="2100">
      </div>
    </div>
    <div class="ef-field" style="margin-bottom:14px">
      <label>Libellé session</label>
      <input name="session_label" value="<?= e($f['session_label'] ?? '') ?>" placeholder="Ex: Session Q4 2026">
    </div>

    <div class="ef-section">Publication</div>
    <div class="ef-grid">
      <div class="ef-field">
        <label>Statut</label>
        <select name="statut">
          <option value="inactive" <?= ($f['statut'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
          <option value="active"   <?= ($f['statut'] ?? '') === 'active'   ? 'selected' : '' ?>>Active</option>
        </select>
      </div>
      <div class="ef-field">
        <label>Type de formation</label>
        <label class="ef-check">
          <input type="checkbox" name="is_samedi_pro" value="1" <?= !empty($f['is_samedi_pro']) ? 'checked' : '' ?>>
          Formation Samedi Pro
        </label>
        <div class="ef-hint">Samedi Pro = tarif total payé en une fois. Décocher = formation classique avec frais d'inscription.</div>
      </div>
    </div>

    <div class="ef-actions">
      <button type="submit" class="ef-btn ef-btn-primary">&#128190; Enregistrer les modifications</button>
      <a href="index.php" class="ef-btn ef-btn-secondary">&#8592; Retour à la liste</a>
    </div>
  </form>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout/layout.php';
