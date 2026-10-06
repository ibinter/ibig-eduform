<?php
declare(strict_types=1);

/**
 * ============================================================
 * ADMIN – CRÉER UNE FORMATION (VERSION FINALE STABLE A→Z)
 * ============================================================
 * - PDO strict
 * - Sécurité middleware
 * - Slug auto + unicité
 * - paiement_lien (URL) + validation
 * - Dates session + mois/année + libellé
 * - UI premium compacte
 * - Emojis UNIQUEMENT en HTML (incorruptibles)
 *
 * Prérequis DB (table formations) : colonnes utilisées ci-dessous doivent exister
 * ============================================================
 */

ob_start();

/* ================= BOOTSTRAP & SÉCURITÉ ================= */
require_once __DIR__ . '/../_init.php';
require_once __DIR__ . '/../auth/middleware.php';

Middleware::requireAuth();

/* ================= MÉTADONNÉES PAGE ================= */
$pageTitle  = "Nouvelle formation";
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
  $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
  $text  = $ascii !== false ? $ascii : $text;
  $text = preg_replace('/[^a-z0-9]+/', '-', $text);
  return trim((string)$text, '-');
}

function date_or_null($v): ?string {
  $v = trim((string)$v);
  if ($v === '') return null;
  $dt = DateTime::createFromFormat('Y-m-d', $v);
  if (!$dt || $dt->format('Y-m-d') !== $v) return null;
  return $v;
}

function pick(string $key, array $src, $default = '') {
  return $src[$key] ?? $default;
}

/* ================= DEFAULTS ================= */
$error = '';
$old = $_POST ?? [];

/* ================= TRAITEMENT POST ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();
  try {
    $titre          = trim((string)pick('titre', $old));
    $slugInput      = trim((string)pick('slug', $old));
    $domaine        = trim((string)pick('domaine', $old));
    $typeCertificat = trim((string)pick('type_certificat', $old));
    $description    = trim((string)pick('description', $old));
    $objectifs      = trim((string)pick('objectifs', $old));
    $modules        = trim((string)pick('modules', $old));
    $duree          = trim((string)pick('duree', $old));
    $mode           = (string)pick('mode', $old, 'presentiel');
    $statut         = (string)pick('statut', $old, 'inactive');

    $tarifPresentiel  = max(0, (int)pick('tarif_presentiel', $old, 0));
    $tarifEnLigne     = max(0, (int)pick('tarif_en_ligne', $old, 0));
    $tarifHybride     = max(0, (int)pick('tarif_hybride', $old, 0));
    $fraisInscription = max(0, (int)pick('frais_inscription', $old, 0));

    $paiementLien = trim((string)pick('paiement_lien', $old));
    if ($paiementLien !== '' && !filter_var($paiementLien, FILTER_VALIDATE_URL)) {
      throw new RuntimeException('Lien de paiement invalide.');
    }

    $dateDebut = date_or_null(pick('date_debut', $old, ''));
    $dateFin   = date_or_null(pick('date_fin', $old, ''));
    $mois      = trim((string)pick('mois', $old));
    $annee     = (int)pick('annee', $old, (int)date('Y'));
    $session   = trim((string)pick('session_label', $old));

    if ($annee < 2000 || $annee > 2100) $annee = (int)date('Y');

    $allowedModes = ['presentiel','en_ligne','hybride'];
    if (!in_array($mode, $allowedModes, true)) $mode = 'presentiel';

    $allowedStatuts = ['inactive','active'];
    if (!in_array($statut, $allowedStatuts, true)) $statut = 'inactive';

    if ($titre === '' || $domaine === '' || $typeCertificat === '') {
      throw new RuntimeException('Titre, domaine et type de certificat obligatoires.');
    }

    $slug = $slugInput !== '' ? slugify($slugInput) : slugify($titre);
    if ($slug === '') throw new RuntimeException('Slug invalide.');

    // Unicité slug
    $check = $pdo->prepare("SELECT id FROM formations WHERE slug = ? LIMIT 1");
    $check->execute([$slug]);
    if ($check->fetch()) {
      throw new RuntimeException('Slug déjà utilisé. Modifiez le titre ou le slug.');
    }

    // Cohérence dates
    if ($dateDebut && $dateFin && $dateFin < $dateDebut) {
      throw new RuntimeException('La date de fin ne peut pas être antérieure à la date de début.');
    }

    // INSERT
    $ins = $pdo->prepare("
      INSERT INTO formations
      (
        titre, slug, domaine, type_certificat,
        description, objectifs, modules, duree,
        mode,
        tarif_presentiel, tarif_en_ligne, tarif_hybride,
        frais_inscription, paiement_lien,
        date_debut, date_fin, mois, annee, session_label,
        statut,
        created_at, updated_at
      )
      VALUES
      (
        :titre, :slug, :domaine, :type_certificat,
        :description, :objectifs, :modules, :duree,
        :mode,
        :tp, :tel, :th,
        :fi, :pl,
        :dd, :df, :mois, :annee, :session,
        :statut,
        NOW(), NOW()
      )
    ");

    $ins->execute([
      ':titre' => $titre,
      ':slug' => $slug,
      ':domaine' => $domaine,
      ':type_certificat' => $typeCertificat,
      ':description' => $description,
      ':objectifs' => $objectifs,
      ':modules' => $modules,
      ':duree' => $duree,
      ':mode' => $mode,
      ':tp' => $tarifPresentiel,
      ':tel' => $tarifEnLigne,
      ':th' => $tarifHybride,
      ':fi' => $fraisInscription,
      ':pl' => $paiementLien,
      ':dd' => $dateDebut,
      ':df' => $dateFin,
      ':mois' => $mois,
      ':annee' => $annee,
      ':session' => $session,
      ':statut' => $statut
    ]);

    redirect('index.php');
    exit;

  } catch (Throwable $e) {
    $error = $e->getMessage();
  }
}

/* ================= AFFICHAGE ================= */
ob_start();
?>

<style>
  .card{background:#fff;border:1px solid #e6eaf2;border-radius:16px;padding:18px;box-shadow:0 10px 30px rgba(15,23,42,.06)}
  .card h2{margin:0 0 10px;font-size:18px;font-weight:900;color:#0f172a}
  .muted{color:#64748b;font-size:12px;margin:0 0 12px}
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
  .grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
  .full{grid-column:1 / -1}
  .h3{margin:14px 0 8px;font-size:14px;font-weight:900;color:#0f172a}
  .actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:14px}
  .btn{display:inline-flex;align-items:center;gap:10px;padding:11px 14px;border-radius:12px;font-weight:900;border:1px solid transparent;text-decoration:none;cursor:pointer}
  .btn-primary{background:#2563eb;color:#fff}
  .btn-primary:hover{filter:brightness(.96)}
  .btn-secondary{background:#fff;color:#0f172a;border-color:#e5e7eb}
  .btn-secondary:hover{background:#f8fafc}
  .helper{font-size:12px;color:#64748b;margin-top:6px}
  @media (max-width: 820px){.grid{grid-template-columns:1fr}}
</style>

<div class="card">

  <h2>&#10133; Nouvelle formation</h2>
  <p class="muted">
    Cr&eacute;ation du socle de la formation. Les contenus p&eacute;dagogiques, le TDR et le calendrier peuvent &ecirc;tre g&eacute;r&eacute;s ailleurs.
  </p>

  <?php if ($error): ?>
    <div class="pill wait">&#9888; <?= e($error) ?></div>
  <?php endif; ?>

  <form method="post" class="form" autocomplete="off">
    <?= csrf_field(); ?>

    <div class="grid">

      <div class="full">
        <label>Titre *</label>
        <input name="titre" required value="<?= e((string)pick('titre', $old)) ?>" placeholder="Ex : Audit &amp; Contr&ocirc;le de Gestion">
      </div>

      <div class="full">
        <label>Slug (optionnel)</label>
        <input name="slug" value="<?= e((string)pick('slug', $old)) ?>" placeholder="auto si vide">
        <div class="helper">Si vide, le slug est g&eacute;n&eacute;r&eacute; automatiquement &agrave; partir du titre.</div>
      </div>

      <div>
        <label>Domaine *</label>
        <input name="domaine" required value="<?= e((string)pick('domaine', $old)) ?>" placeholder="Ex : Finance, QHSE, Management">
      </div>

      <div>
        <label>Type de certificat *</label>
        <input name="type_certificat" required value="<?= e((string)pick('type_certificat', $old)) ?>" placeholder="Ex : Certificat 3 en 1">
      </div>

      <div class="full">
        <label>Description courte</label>
        <textarea name="description" placeholder="R&eacute;sum&eacute; synth&eacute;tique public"><?= e((string)pick('description', $old)) ?></textarea>
      </div>

      <div class="full">
        <label>Objectifs (aper&ccedil;u)</label>
        <textarea name="objectifs" placeholder="Objectifs principaux de la formation"><?= e((string)pick('objectifs', $old)) ?></textarea>
      </div>

      <div class="full">
        <label>Modules (s&eacute;par&eacute;s par ;)</label>
        <textarea name="modules" placeholder="Module 1 ; Module 2 ; Module 3"><?= e((string)pick('modules', $old)) ?></textarea>
        <div class="helper">Ils pourront &ecirc;tre restructur&eacute;s plus tard dans le TDR.</div>
      </div>

      <div>
        <label>Dur&eacute;e</label>
        <input name="duree" value="<?= e((string)pick('duree', $old)) ?>" placeholder="Ex : 2 mois / 48 heures">
      </div>

      <div>
        <label>Mode</label>
        <select name="mode">
          <?php $m = (string)pick('mode', $old, 'presentiel'); ?>
          <option value="presentiel" <?= $m==='presentiel'?'selected':''; ?>>Pr&eacute;sentiel</option>
          <option value="en_ligne" <?= $m==='en_ligne'?'selected':''; ?>>En ligne</option>
          <option value="hybride" <?= $m==='hybride'?'selected':''; ?>>Hybride</option>
        </select>
      </div>

      <div>
        <label>Statut</label>
        <?php $st = (string)pick('statut', $old, 'inactive'); ?>
        <select name="statut">
          <option value="inactive" <?= $st==='inactive'?'selected':''; ?>>Inactive</option>
          <option value="active" <?= $st==='active'?'selected':''; ?>>Active</option>
        </select>
      </div>

      <div>
        <label>Frais d&rsquo;inscription (FCFA)</label>
        <input type="number" min="0" name="frais_inscription" value="<?= (int)pick('frais_inscription', $old, 0) ?>">
      </div>

      <div>
        <label>Tarif en ligne (FCFA)</label>
        <input type="number" min="0" name="tarif_en_ligne" value="<?= (int)pick('tarif_en_ligne', $old, 0) ?>">
      </div>

      <div>
        <label>Tarif pr&eacute;sentiel (FCFA)</label>
        <input type="number" min="0" name="tarif_presentiel" value="<?= (int)pick('tarif_presentiel', $old, 0) ?>">
      </div>

      <div>
        <label>Tarif hybride (FCFA)</label>
        <input type="number" min="0" name="tarif_hybride" value="<?= (int)pick('tarif_hybride', $old, 0) ?>">
      </div>

      <div class="full">
        <label>Lien de paiement</label>
        <input name="paiement_lien" value="<?= e((string)pick('paiement_lien', $old)) ?>" placeholder="https://...">
      </div>

      <div>
        <label>Date d&eacute;but</label>
        <input type="date" name="date_debut" value="<?= e((string)pick('date_debut', $old)) ?>">
      </div>

      <div>
        <label>Date fin</label>
        <input type="date" name="date_fin" value="<?= e((string)pick('date_fin', $old)) ?>">
      </div>

      <div>
        <label>Mois</label>
        <input name="mois" value="<?= e((string)pick('mois', $old)) ?>" placeholder="Ex : F&eacute;vrier">
      </div>

      <div>
        <label>Ann&eacute;e</label>
        <input type="number" min="2000" max="2100" name="annee" value="<?= (int)pick('annee', $old, (int)date('Y')) ?>">
      </div>

      <div class="full">
        <label>Libell&eacute; session</label>
        <input name="session_label" value="<?= e((string)pick('session_label', $old)) ?>" placeholder="Ex : Session 1 - 2026">
      </div>

    </div>

    <div class="actions">
      <button type="submit" class="btn btn-primary">&#128190; Enregistrer la formation</button>
      <a href="index.php" class="btn btn-secondary">&#8592; Retour</a>
    </div>

  </form>

</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout/layout.php';
