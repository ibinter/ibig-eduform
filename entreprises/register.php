<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/helpers.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

$pageTitle = "Inscription Entreprise / Recruteur – IBIG EDUFORM";

$pdo = Database::connect();

$success = false;
$error = '';
$errors = [];

function csrf_token(): string {
  if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  }
  return $_SESSION['csrf_token'];
}
function csrf_check(?string $token): bool {
  return is_string($token) && isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/* ============================
   CONFIG UPLOAD
============================ */
$uploadDir = __DIR__ . '/../uploads/entreprises/rccm'; // adapte selon ton arborescence
$maxSize   = 5 * 1024 * 1024; // 5 MB
$allowedMime = [
  'application/pdf' => 'pdf',
  'image/jpeg'      => 'jpg',
  'image/png'       => 'png',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  if (!csrf_check($_POST['csrf_token'] ?? null)) {
    $error = "Session expirée. Veuillez réessayer.";
  } else {

    // Champs
    $type_entite = trim((string)($_POST['type_entite'] ?? ''));
    $nom_legal   = trim((string)($_POST['nom_legal'] ?? ''));
    $secteur     = trim((string)($_POST['secteur'] ?? ''));
    $pays        = trim((string)($_POST['pays'] ?? ''));
    $ville       = trim((string)($_POST['ville'] ?? ''));

    $email       = strtolower(trim((string)($_POST['email'] ?? '')));
    $telephone   = trim((string)($_POST['telephone'] ?? ''));

    $resp_nom    = trim((string)($_POST['responsable_nom'] ?? ''));
    $resp_fct    = trim((string)($_POST['responsable_fonction'] ?? ''));

    $description = trim((string)($_POST['description'] ?? ''));

    $num_rccm    = trim((string)($_POST['numero_rccm'] ?? ''));

    $password    = (string)($_POST['password'] ?? '');
    $password2   = (string)($_POST['password2'] ?? '');

    // Validations
    $allowedTypes = ['entreprise','cabinet_rh','chasseur_tete','ong','institution','projet'];
    if (!in_array($type_entite, $allowedTypes, true)) $errors[] = "Type de structure invalide.";
    if ($nom_legal === '' || mb_strlen($nom_legal) < 3) $errors[] = "Nom légal requis (min. 3 caractères).";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email invalide.";
    if (mb_strlen($password) < 8) $errors[] = "Mot de passe trop court (min. 8 caractères).";
    if ($password !== $password2) $errors[] = "Les mots de passe ne correspondent pas.";

    // RCCM obligatoire (num + fichier)
    if ($num_rccm === '' || mb_strlen($num_rccm) < 4) $errors[] = "Numéro RCCM/RC requis.";
    if (empty($_FILES['rccm_file']) || ($_FILES['rccm_file']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
      $errors[] = "Le fichier RCCM est obligatoire (PDF/JPG/PNG).";
    }

    // Vérif email existant
    if (!$errors) {
      $st = $pdo->prepare("SELECT id FROM entreprises WHERE email = ? LIMIT 1");
      $st->execute([$email]);
      if ($st->fetch()) $errors[] = "Un compte existe déjà avec cet email.";
    }

    // Upload RCCM
    $savedPath = null;

    if (!$errors) {
      $file = $_FILES['rccm_file'];

      if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        $errors[] = "Erreur upload fichier (code: " . (int)$file['error'] . ").";
      } else {
        if ((int)$file['size'] > $maxSize) {
          $errors[] = "Fichier trop lourd. Max 5 Mo.";
        } else {
          // MIME réel
          $finfo = new finfo(FILEINFO_MIME_TYPE);
          $mime  = $finfo->file($file['tmp_name']);
          if (!isset($allowedMime[$mime])) {
            $errors[] = "Format non autorisé. PDF / JPG / PNG uniquement.";
          } else {
            if (!is_dir($uploadDir)) {
              @mkdir($uploadDir, 0755, true);
            }

            $ext = $allowedMime[$mime];
            $safeBase = preg_replace('/[^a-z0-9]+/i', '-', strtolower($nom_legal));
            $safeBase = trim($safeBase, '-');
            $filename = 'rccm-' . $safeBase . '-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.' . $ext;

            $dest = rtrim($uploadDir, '/') . '/' . $filename;

            if (!move_uploaded_file($file['tmp_name'], $dest)) {
              $errors[] = "Impossible d’enregistrer le fichier RCCM.";
            } else {
              // Chemin relatif stocké en DB (recommandé)
              $savedPath = '/uploads/entreprises/rccm/' . $filename;
            }
          }
        }
      }
    }

    // Insert DB (transaction)
    if (!$errors) {
      $passwordHash = password_hash($password, PASSWORD_DEFAULT);
      $verifyToken  = bin2hex(random_bytes(32));

      try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
          INSERT INTO entreprises
            (nom_legal, type_entite, secteur, pays, ville, email, password_hash, telephone,
             responsable_nom, responsable_fonction, description, statut, email_verified, email_verify_token)
          VALUES
            (:nom, :type, :secteur, :pays, :ville, :email, :ph, :tel,
             :rnom, :rfct, :desc, 'en_attente', 0, :token)
        ");
        $stmt->execute([
          ':nom'    => $nom_legal,
          ':type'   => $type_entite,
          ':secteur'=> $secteur ?: null,
          ':pays'   => $pays ?: null,
          ':ville'  => $ville ?: null,
          ':email'  => $email,
          ':ph'     => $passwordHash,
          ':tel'    => $telephone ?: null,
          ':rnom'   => $resp_nom ?: null,
          ':rfct'   => $resp_fct ?: null,
          ':desc'   => $description ?: null,
          ':token'  => $verifyToken,
        ]);

        $entrepriseId = (int)$pdo->lastInsertId();

        $stmt2 = $pdo->prepare("
          INSERT INTO entreprise_documents
            (entreprise_id, type_document, numero_document, fichier, statut)
          VALUES
            (:eid, 'registre_commerce', :num, :fichier, 'en_verification')
        ");
        $stmt2->execute([
          ':eid'    => $entrepriseId,
          ':num'    => $num_rccm,
          ':fichier'=> $savedPath,
        ]);

        $pdo->commit();

        // TODO: envoyer email de vérification avec $verifyToken (phase suivante)
        $success = true;

      } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        // rollback fichier si besoin
        if ($savedPath) {
          $abs = __DIR__ . '/..' . $savedPath;
          if (is_file($abs)) @unlink($abs);
        }
        $error = "Erreur système. Veuillez réessayer.";
      }
    }
  }
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle); ?></title>
  <link rel="stylesheet" href="/assets/css/style.css?v=1.0">
</head>
<body>

<main style="max-width:900px;margin:40px auto;padding:0 18px;">
  <h1 style="margin:0 0 10px;">Inscription Entreprise / Recruteur</h1>
  <p style="margin:0 0 22px;color:#475569;">
    Créez votre compte et déposez votre <strong>Registre de Commerce (RCCM/RC)</strong>.  
    Votre profil et vos annonces seront publiés uniquement après validation par IBIG EDUFORM.
  </p>

  <?php if ($success): ?>
    <div style="padding:14px 16px;border-radius:12px;background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46;">
      ✅ Inscription reçue. Votre compte est <strong>en attente de validation</strong>.  
      Vous serez notifié après vérification du RCCM.
    </div>
  <?php else: ?>

    <?php if ($error): ?>
      <div style="padding:14px 16px;border-radius:12px;background:#fef2f2;border:1px solid #fecaca;color:#991b1b;margin:0 0 14px;">
        <?= e($error); ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
      <div style="padding:14px 16px;border-radius:12px;background:#fff7ed;border:1px solid #fed7aa;color:#9a3412;margin:0 0 14px;">
        <strong>Veuillez corriger :</strong>
        <ul style="margin:10px 0 0 18px;">
          <?php foreach ($errors as $msg): ?>
            <li><?= e($msg); ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" style="display:grid;gap:14px;">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">

      <div style="display:grid;gap:8px;">
        <label><strong>Type de structure</strong></label>
        <select name="type_entite" required style="padding:12px;border-radius:10px;border:1px solid #cbd5e1;">
          <option value="entreprise">Entreprise</option>
          <option value="cabinet_rh">Cabinet RH</option>
          <option value="chasseur_tete">Chasseur de tête</option>
          <option value="ong">ONG</option>
          <option value="institution">Institution</option>
          <option value="projet">Projet</option>
        </select>
      </div>

      <div style="display:grid;gap:8px;">
        <label><strong>Nom légal (raison sociale)</strong></label>
        <input name="nom_legal" required placeholder="Ex : XYZ SARL"
               style="padding:12px;border-radius:10px;border:1px solid #cbd5e1;">
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
        <div style="display:grid;gap:8px;">
          <label>Secteur</label>
          <input name="secteur" placeholder="Ex : BTP, Banque, IT..."
                 style="padding:12px;border-radius:10px;border:1px solid #cbd5e1;">
        </div>
        <div style="display:grid;gap:8px;">
          <label>Téléphone</label>
          <input name="telephone" placeholder="+225 ..."
                 style="padding:12px;border-radius:10px;border:1px solid #cbd5e1;">
        </div>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
        <div style="display:grid;gap:8px;">
          <label>Pays</label>
          <input name="pays" placeholder="Côte d’Ivoire"
                 style="padding:12px;border-radius:10px;border:1px solid #cbd5e1;">
        </div>
        <div style="display:grid;gap:8px;">
          <label>Ville</label>
          <input name="ville" placeholder="Abidjan"
                 style="padding:12px;border-radius:10px;border:1px solid #cbd5e1;">
        </div>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
        <div style="display:grid;gap:8px;">
          <label><strong>Email professionnel</strong></label>
          <input type="email" name="email" required placeholder="contact@entreprise.com"
                 style="padding:12px;border-radius:10px;border:1px solid #cbd5e1;">
        </div>
        <div style="display:grid;gap:8px;">
          <label>Fonction du responsable</label>
          <input name="responsable_fonction" placeholder="DRH, Directeur..."
                 style="padding:12px;border-radius:10px;border:1px solid #cbd5e1;">
        </div>
      </div>

      <div style="display:grid;gap:8px;">
        <label>Nom du responsable</label>
        <input name="responsable_nom" placeholder="Nom & prénom"
               style="padding:12px;border-radius:10px;border:1px solid #cbd5e1;">
      </div>

      <div style="display:grid;gap:8px;">
        <label>Description (facultatif)</label>
        <textarea name="description" rows="4" placeholder="Décrivez votre structure..."
                  style="padding:12px;border-radius:10px;border:1px solid #cbd5e1;"></textarea>
      </div>

      <hr style="border:none;border-top:1px solid #e2e8f0;margin:6px 0;">

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
        <div style="display:grid;gap:8px;">
          <label><strong>Numéro RCCM / Registre de Commerce</strong></label>
          <input name="numero_rccm" required placeholder="Ex : CI-ABJ-2023-B-XXXX"
                 style="padding:12px;border-radius:10px;border:1px solid #cbd5e1;">
        </div>
        <div style="display:grid;gap:8px;">
          <label><strong>Fichier RCCM</strong> (PDF/JPG/PNG, 5Mo max)</label>
          <input type="file" name="rccm_file" accept=".pdf,.jpg,.jpeg,.png" required
                 style="padding:10px;border-radius:10px;border:1px solid #cbd5e1;background:#fff;">
        </div>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
        <div style="display:grid;gap:8px;">
          <label><strong>Mot de passe</strong></label>
          <input type="password" name="password" required
                 style="padding:12px;border-radius:10px;border:1px solid #cbd5e1;">
        </div>
        <div style="display:grid;gap:8px;">
          <label><strong>Confirmer</strong></label>
          <input type="password" name="password2" required
                 style="padding:12px;border-radius:10px;border:1px solid #cbd5e1;">
        </div>
      </div>

      <label style="display:flex;gap:10px;align-items:flex-start;color:#334155;">
        <input type="checkbox" required style="margin-top:4px;">
        <span>
          Je certifie l’exactitude des informations fournies et j’accepte que toute publication
          soit soumise à validation par <strong>IBIG EDUFORM</strong>.
        </span>
      </label>

      <button type="submit"
              style="padding:14px 16px;border:none;border-radius:12px;background:#f5a623;font-weight:900;cursor:pointer;">
        Créer le compte & soumettre le RCCM
      </button>

      <p style="margin:0;color:#64748b;font-size:13px;">
        ⚠️ Votre compte restera <strong>en attente</strong> jusqu’à validation du document par IBIG EDUFORM.
      </p>
    </form>
  <?php endif; ?>
</main>

</body>
</html>
