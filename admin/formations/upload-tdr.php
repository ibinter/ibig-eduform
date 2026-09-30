<?php
declare(strict_types=1);

/**
 * ============================================================
 * ADMIN — FORMATIONS — GESTION DU TDR (PDF)
 * Fichier : /admin/formations/upload-tdr.php
 * ------------------------------------------------------------
 * - Utilise le layout admin (sidebar)
 * - Upload PDF sécurisé + remplacement ancien fichier
 * - Boutons Retour + Voir TDR
 * - Emojis UNIQUEMENT en HTML (incorruptibles)
 * ============================================================
 */

ob_start();

require_once __DIR__ . '/../_init.php';

$pageTitle  = "Gestion du TDR";
$activeMenu = "formations";

$pdo = Database::connect();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

/* =========================
   HELPERS
========================= */
if (!function_exists('e')) {
  function e($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
  }
}

/* =========================
   SECURITE ID
========================= */
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
  http_response_code(404);
  die('Formation introuvable.');
}

/* =========================
   RECUP FORMATION
========================= */
$stmt = $pdo->prepare("
  SELECT id, titre, tdr_pdf
  FROM formations
  WHERE id = ?
  LIMIT 1
");
$stmt->execute([$id]);
$f = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$f) {
  http_response_code(404);
  die('Formation introuvable.');
}

/* =========================
   CONFIG UPLOAD
========================= */
$uploadDir = __DIR__ . '/../../uploads/tdr/';
if (!is_dir($uploadDir)) {
  mkdir($uploadDir, 0755, true);
}

$error   = '';
$success = '';

/* =========================
   TRAITEMENT POST
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  csrf_check();

  if (empty($_FILES['tdr']['name'])) {
    $error = "Veuillez sélectionner un fichier PDF.";
  } else {

    $file = $_FILES['tdr'];

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
      $error = "Erreur lors de l'envoi du fichier.";
    } else {

      $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
      if ($ext !== 'pdf') {
        $error = "Seuls les fichiers PDF sont autorisés.";
      } else {

        // Optionnel : limite taille (10 Mo)
        $maxSize = 10 * 1024 * 1024;
        if (($file['size'] ?? 0) > $maxSize) {
          $error = "Le fichier est trop volumineux (max 10 Mo).";
        } else {

          // Nom unique
          $filename = 'tdr_formation_' . (int)$f['id'] . '_' . time() . '.pdf';
          $target   = $uploadDir . $filename;

          if (move_uploaded_file($file['tmp_name'], $target)) {

            // Supprimer ancien fichier si existant
            if (!empty($f['tdr_pdf'])) {
              $oldPath = $uploadDir . basename((string)$f['tdr_pdf']);
              if (is_file($oldPath)) {
                @unlink($oldPath);
              }
            }

            // MAJ DB (on stocke le NOM SEUL)
            $up = $pdo->prepare("UPDATE formations SET tdr_pdf = ? WHERE id = ?");
            $up->execute([$filename, (int)$f['id']]);

            $success = "TDR mis à jour avec succès.";
            $f['tdr_pdf'] = $filename;

          } else {
            $error = "Impossible de déplacer le fichier.";
          }
        }
      }
    }
  }
}

/* =========================
   RENDU (CONTENU)
========================= */
ob_start();
?>

<style>
/* ============================================================
   TDR — STYLE ADMIN LIGHT (compact & propre)
   - Ne casse pas ton layout global
============================================================ */
.tdr-wrap{
  max-width: 980px;
}

.tdr-head{
  display:flex;
  align-items:flex-start;
  justify-content:space-between;
  gap:16px;
  margin-bottom:14px;
}

.tdr-head h2{
  margin:0;
  font-size:20px;
  font-weight:800;
  letter-spacing:-.2px;
}

.tdr-sub{
  margin:6px 0 0;
  color:#6b7280;
  font-size:13px;
}

.tdr-actions-top{
  display:flex;
  gap:10px;
  flex-wrap:wrap;
}

.tdr-card{
  background:#fff;
  border:1px solid #e5e7eb;
  border-radius:16px;
  padding:18px;
}

.tdr-row{
  display:grid;
  grid-template-columns: 1fr 1fr;
  gap:14px;
  margin-top:14px;
}
@media(max-width:900px){
  .tdr-row{grid-template-columns:1fr}
  .tdr-head{flex-direction:column; align-items:stretch}
  .tdr-actions-top{justify-content:flex-start}
}

.tdr-kv{
  background:#f9fafb;
  border:1px solid #eef2f7;
  border-radius:14px;
  padding:14px;
}

.tdr-kv b{display:block; font-size:12px; color:#6b7280; margin-bottom:6px}
.tdr-kv .v{font-weight:800; font-size:14px; color:#111827}

.tdr-alert{
  border-radius:14px;
  padding:12px 14px;
  margin:12px 0 0;
  font-weight:700;
  font-size:13px;
}
.tdr-alert.ok{background:#ecfdf5;border:1px solid #10b981;color:#065f46}
.tdr-alert.bad{background:#fff1f2;border:1px solid #fb7185;color:#9f1239}

.tdr-form label{
  display:block;
  margin-top:14px;
  font-weight:800;
  font-size:13px;
  color:#111827;
}
.tdr-form input[type=file]{
  width:100%;
  margin-top:8px;
  padding:12px 12px;
  border-radius:12px;
  border:1px dashed #cbd5e1;
  background:#f8fafc;
}

.tdr-help{
  margin-top:6px;
  font-size:12px;
  color:#6b7280;
}

.tdr-btns{
  margin-top:18px;
  display:flex;
  gap:10px;
  flex-wrap:wrap;
}
</style>

<div class="tdr-wrap">

  <div class="tdr-head">
    <div>
      <h2>&#128196; Gestion du TDR</h2>
      <p class="tdr-sub">Importer / remplacer le document TDR (PDF) pour cette formation.</p>
    </div>

    <div class="tdr-actions-top">
      <a href="index.php" class="btn btn-secondary">
        &#8592; Retour
      </a>

      <?php if (!empty($f['tdr_pdf'])): ?>
        <a class="btn btn-info"
           href="/uploads/tdr/<?= e($f['tdr_pdf']); ?>"
           target="_blank" rel="noopener">
          &#128065; Voir le TDR
        </a>
      <?php endif; ?>
    </div>
  </div>

  <div class="tdr-card">

    <div class="tdr-row">
      <div class="tdr-kv">
        <b>Formation</b>
        <div class="v"><?= e($f['titre']); ?></div>
      </div>

      <div class="tdr-kv">
        <b>Statut TDR</b>
        <div class="v">
          <?php if (!empty($f['tdr_pdf'])): ?>
            Disponible &#10004;
          <?php else: ?>
            Aucun TDR &#10060;
          <?php endif; ?>
        </div>
      </div>
    </div>

    <?php if ($error): ?>
      <div class="tdr-alert bad"><?= e($error); ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
      <div class="tdr-alert ok">&#10004; <?= e($success); ?></div>
    <?php endif; ?>

    <form class="tdr-form" method="post" enctype="multipart/form-data">
      <?= csrf_field(); ?>

      <label>Fichier TDR (PDF uniquement)</label>
      <input type="file" name="tdr" accept="application/pdf" required>
      <div class="tdr-help">Conseil : nomme ton fichier clairement. Taille max recommandée : 10 Mo.</div>

      <div class="tdr-btns">
        <button type="submit" class="btn btn-primary">
          &#128190; Enregistrer le TDR
        </button>

        <a href="index.php" class="btn btn-secondary">
          &#8592; Retour
        </a>
      </div>

    </form>

  </div>

</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout/layout.php';