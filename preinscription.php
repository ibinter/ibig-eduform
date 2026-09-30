<?php
declare(strict_types=1);

/**
 * ============================================================================
 * IBIG EDUFORM — PREINSCRIPTION.PHP (FORMATION SPÉCIFIQUE) — VERSION CENTRALISÉE
 * ----------------------------------------------------------------------------
 * âÂÂ Centralise l'enregistrement via core/preinscription_service.php
 * âÂÂ PHP 7.4+ compatible (PAS de "mixed")
 * âÂÂ Zéro warning "Undefined variable"
 * âÂÂ Upload CV sécurisé (PDF/DOC/DOCX, 2Mo)
 * âÂÂ Upload CNI/Passeport optionnel (PDF/JPG/JPEG/PNG, 3Mo) — non bloquant
 * âÂÂ WhatsApp candidat + admin (si notifications/whatsapp.php existe)
 * âÂÂ Anti double submit via redirect success=1
 * ============================================================================
 */

/* =====================================================
   SESSION (AVANT TOUT)
===================================================== */
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/* =====================================================
   BOOTSTRAP + DB + SERVICES
===================================================== */
require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/core/preinscription_service.php';

$pdo = Database::connect();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

/* =====================================================
   DEBUG (désactiver en prod)
===================================================== */
$DEBUG = false;
if ($DEBUG) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
}

/* =====================================================
   HELPERS LOCAUX (SAFE)
===================================================== */
if (!function_exists('h')) {
    function h($v): string {
        return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('post')) {
    function post(string $key, $default = '') {
        return $_POST[$key] ?? $default;
    }
}

if (!function_exists('normalize_phone')) {
    function normalize_phone(string $phone): string {
        $phone = trim($phone);
        $phone = preg_replace('/\s+/', '', $phone);
        $phone = str_replace(['-','(',')'], '', $phone);
        return $phone;
    }
}

if (!function_exists('is_valid_email')) {
    function is_valid_email(string $email): bool {
        return (bool)filter_var($email, FILTER_VALIDATE_EMAIL);
    }
}

/**
 * Upload sécurisé (retourne [path|null, error|null])
 * $errorMessageMode = 'blocking' => retourne une erreur si invalide
 * $errorMessageMode = 'silent'   => aucune erreur bloquante (retourne path null si invalide)
 */
if (!function_exists('secure_upload')) {
    function secure_upload(array $file, array $allowedExt, int $maxSize, string $uploadDirAbs, string $prefix, string $errorMessageMode = 'blocking'): array {
        $origName = (string)($file['name'] ?? '');
        $tmpName  = (string)($file['tmp_name'] ?? '');
        $size     = (int)($file['size'] ?? 0);
        $errUp    = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);

        if ($origName === '' || $errUp === UPLOAD_ERR_NO_FILE) {
            return [null, null]; // pas de fichier => ok
        }

        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

        if ($errUp !== UPLOAD_ERR_OK) {
            $msg = "Erreur lors de l’envoi du fichier (code: {$errUp}).";
            return [null, ($errorMessageMode === 'blocking') ? $msg : null];
        }
        if (!in_array($ext, $allowedExt, true)) {
            $msg = "Format de fichier non autorisé.";
            return [null, ($errorMessageMode === 'blocking') ? $msg : null];
        }
        if ($size <= 0 || $size > $maxSize) {
            $msg = "Fichier trop volumineux.";
            return [null, ($errorMessageMode === 'blocking') ? $msg : null];
        }

        if (!is_dir($uploadDirAbs)) {
            @mkdir($uploadDirAbs, 0775, true);
        }

        if (!is_dir($uploadDirAbs) || !is_writable($uploadDirAbs)) {
            $msg = "Dossier d’upload indisponible (permissions).";
            return [null, ($errorMessageMode === 'blocking') ? $msg : null];
        }

        try {
            $filename    = $prefix . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
        } catch (Throwable $e) {
            $filename    = $prefix . '_' . date('Ymd_His') . '_' . bin2hex(openssl_random_pseudo_bytes(6)) . '.' . $ext;
        }

        $destination = rtrim($uploadDirAbs, '/\\') . DIRECTORY_SEPARATOR . $filename;

        if (!is_uploaded_file($tmpName)) {
            $msg = "Upload invalide.";
            return [null, ($errorMessageMode === 'blocking') ? $msg : null];
        }

        if (!@move_uploaded_file($tmpName, $destination)) {
            $msg = "Erreur lors de l’enregistrement du fichier.";
            return [null, ($errorMessageMode === 'blocking') ? $msg : null];
        }

        // Path relatif (depuis la racine du site)
        // Ici on suppose que $uploadDirAbs est sous __DIR__ (document root du script)
        $rel = str_replace('\\', '/', $destination);
        $base = str_replace('\\', '/', __DIR__) . '/';
        $relPath = (strpos($rel, $base) === 0) ? substr($rel, strlen($base)) : basename($destination);

        return [$relPath, null];
    }
}

/* =====================================================
   RÉCUPÉRATION FORMATION (OBLIGATOIRE)
===================================================== */
$formationId = 0;

if (isset($_GET['formation_id'])) {
    $formationId = (int)$_GET['formation_id'];
} elseif (isset($_GET['id'])) {
    $formationId = (int)$_GET['id'];
} elseif (isset($_GET['formation'])) {
    $formationId = (int)$_GET['formation'];
}

$formation = null;
if ($formationId > 0) {
    $stmt = $pdo->prepare("
        SELECT id, titre, slug
        FROM formations
        WHERE id = ? AND statut = 'active'
        LIMIT 1
    ");
    $stmt->execute([$formationId]);
    $formation = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

if (!$formation) {
    $pageTitle = "Préinscription – IBIG EDUFORM";
    require __DIR__ . '/partials/header.php';
    echo "<div style='max-width:640px;margin:120px auto;text-align:center;color:#fff'>
            <h2>Formation introuvable</h2>
            <p>Merci de revenir au catalogue et de sélectionner une formation valide.</p>
          </div>";
    require __DIR__ . '/partials/footer.php';
    exit;
}

/* =====================================================
   ÉTAT UI + STICKY VALUES
===================================================== */
$success     = false;
$error       = '';
$cvPath      = null;
$cniPath     = null;
$waLink      = '';
$waAdminLink = '';

/* Anti double submit */
if (isset($_GET['success']) && $_GET['success'] === '1') {
    $success = true;
    /* Lien WhatsApp pré-rempli conservé lors de la redirection */
    $waLink = (string)($_SESSION['wa_link'] ?? '');
    unset($_SESSION['wa_link']);
}

/* Valeurs sticky */
$old = [
    'nom' => '',
    'prenom' => '',
    'email' => '',
    'telephone' => '',
    'mode_formation' => '',
    'statut_professionnel' => '',
    'objectif' => '',
    'disponibilite' => '',
    'ville' => '',
    'pays' => "Côte d’Ivoire",
    'message' => '',
    'domaine_activite' => '',
    'niveau_etude' => '',
    'fonction' => '',
    'annees_experience' => ''
];

/* =====================================================
   TRAITEMENT POST
===================================================== */
if (!$success && (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST')) {

    csrf_check();

    $nom       = trim((string)post('nom', ''));
    $prenoms   = trim((string)post('prenom', ''));
    $email     = trim((string)post('email', ''));
    $telephone = normalize_phone(trim((string)post('telephone', '')));

    $mode      = (string)post('mode_formation', '');
    $statutPro = (string)post('statut_professionnel', '');
    $objectif  = (string)post('objectif', '');

    $dispo     = (string)post('disponibilite', '');
    $ville     = trim((string)post('ville', ''));
    $pays      = trim((string)post('pays', "Côte d’Ivoire"));
    $message   = trim((string)post('message', ''));

    $domaineActivite = trim((string)post('domaine_activite', ''));
    $niveauEtude     = trim((string)post('niveau_etude', ''));
    $fonction        = trim((string)post('fonction', ''));
    $anneesExp       = trim((string)post('annees_experience', ''));

    /* Sticky */
    $old = [
        'nom' => $nom,
        'prenom' => $prenoms,
        'email' => $email,
        'telephone' => $telephone,
        'mode_formation' => $mode,
        'statut_professionnel' => $statutPro,
        'objectif' => $objectif,
        'disponibilite' => $dispo,
        'ville' => $ville,
        'pays' => $pays,
        'message' => $message,
        'domaine_activite' => $domaineActivite,
        'niveau_etude' => $niveauEtude,
        'fonction' => $fonction,
        'annees_experience' => $anneesExp
    ];

    /* Validation */
    if (
        $nom === '' ||
        $prenoms === '' ||
        $email === '' ||
        $telephone === '' ||
        $mode === '' ||
        $statutPro === '' ||
        $objectif === ''
    ) {
        $error = "Merci de remplir tous les champs obligatoires.";
    } elseif (!is_valid_email($email)) {
        $error = "Adresse email invalide.";
    }

    /* Normalisation NULL */
    $dispoDb   = ($dispo !== '') ? $dispo : null;
    $messageDb = (trim($message) !== '') ? trim($message) : null;

    /* ============================
       UPLOAD CV (OPTIONNEL) — BLOQUANT SI INVALIDE
    ============================ */
    if (!$error && !empty($_FILES['cv']['name'])) {
        list($cvPathTmp, $cvErr) = secure_upload(
            $_FILES['cv'],
            ['pdf','doc','docx'],
            2 * 1024 * 1024,
            __DIR__ . '/uploads/cv/',
            'cv',
            'blocking'
        );

        if ($cvErr) {
            // message friendly
            if (strpos($cvErr, 'Format') !== false) {
                $error = "Format de CV non autorisé (PDF, DOC, DOCX uniquement).";
            } elseif (strpos($cvErr, 'volumineux') !== false) {
                $error = "Le CV ne doit pas dépasser 2 Mo.";
            } else {
                $error = $cvErr;
            }
        } else {
            $cvPath = $cvPathTmp;
        }
    }

    /* ============================
       UPLOAD CNI / PIÈCE D’IDENTITÉ (OPTIONNEL) — NON BLOQUANT
    ============================ */
    if (!empty($_FILES['cni']['name'])) {
        list($cniPathTmp, $cniErr) = secure_upload(
            $_FILES['cni'],
            ['pdf','jpg','jpeg','png'],
            3 * 1024 * 1024,
            __DIR__ . '/uploads/cni/',
            'cni',
            'silent' // volontairement aucune erreur bloquante
        );
        if ($cniErr === null && $cniPathTmp) {
            $cniPath = $cniPathTmp;
        }
    }

    /* =====================================================
       ENREGISTREMENT CENTRALISÉ (SERVICE)
    ===================================================== */
    if (!$error) {
        try {

            // UTM
            $utmSource = $_SESSION['utm_source'] ?? null;
            $utmCamp   = $_SESSION['utm_campaign'] ?? null;

            // IMPORTANT: on passe aussi cni_path (si votre service/DB le supporte)
            $payload = [
                'type_preinscription'  => 'specifique',   // ⚠️ PAS samedi_pro
                'formation_id'         => (int)$formation['id'],
                'domaine_interet'      => (string)$formation['titre'],
            
                'nom'                  => $nom,
                'prenoms'              => $prenoms,
                'email'                => $email,
                'telephone'            => $telephone,
            
                'mode_formation'       => 'presentiel',   // ⚠️ FORCÉ
                'statut_professionnel' => $statutPro,
                'objectif'             => $objectif,
                'disponibilite'        => $dispoDb,
            
                'niveau'               => 'debutant',
                'ville'                => $ville,
                'pays'                 => $pays,
            
                'message'              => $messageDb,
                'cv_path'              => $cvPath,   // chemin réel du CV uploadé
                'cni_path'             => $cniPath,  // chemin réel de la pièce d'identité
            
                'utm_source'           => $utmSource,
                'utm_campaign'         => $utmCamp,
            
                'source'               => 'site_web',
            ];
            $id = enregistrer_preinscription($pdo, $payload);

            /* Champs profil complémentaires (UPDATE tolérant : ne casse rien
               si les colonnes ne sont pas encore ajoutées en base) */
            if ($id > 0) {
                try {
                    $pdo->prepare("UPDATE preinscriptions
                                   SET domaine_activite = ?, niveau_etude = ?, fonction = ?, annees_experience = ?
                                   WHERE id = ?")
                        ->execute([
                            $domaineActivite !== '' ? $domaineActivite : null,
                            $niveauEtude     !== '' ? $niveauEtude     : null,
                            $fonction        !== '' ? $fonction        : null,
                            $anneesExp       !== '' ? $anneesExp       : null,
                            $id,
                        ]);
                } catch (Throwable $e) { error_log('[PREINSC_PROFIL] ' . $e->getMessage()); }
            }

            /* =========================================================
               WHATSAPP : envoi AUTOMATIQUE côté serveur (CallMeBot)
               + lien wa.me pour le prospect (conservé en session car
               on redirige juste après pour éviter le double envoi).
            ========================================================= */
            if (function_exists('whatsapp_message_preinscription')) {
                $waMsg = whatsapp_message_preinscription([
                    'formation'         => (string)$formation['titre'],
                    'nom'               => $nom,
                    'prenom'            => $prenoms,
                    'telephone'         => $telephone,
                    'email'             => $email,
                    'mode'              => $mode,
                    'ville'             => $ville,
                    'message'           => $messageDb,
                    'preinscription_id' => (int)$id,
                ]);

                whatsapp_notify_admin($waMsg);                       // réception instantanée institut
                $_SESSION['wa_link'] = whatsapp_wa_link($waMsg);     // le prospect envoie aussi à l'institut
            }

            /* Anti double submit */
            header('Location: preinscription.php?formation_id='.(int)$formation['id'].'&success=1');
            exit;

        } catch (Throwable $e) {
            error_log('[PREINSCRIPTION SPECIFIQUE] '.$e->getMessage());
            $error = $DEBUG ? ("Erreur SQL : ".$e->getMessage()) : "Erreur lors de l’enregistrement. Merci de réessayer.";
        }
    }
}

/* =====================================================
   HEADER (APRÈS TRAITEMENT)
===================================================== */
$pageTitle = "Préinscription – " . (string)$formation['titre'] . " | IBIG EDUFORM";
require __DIR__ . '/partials/header.php';
?>

<style>
body{
  background:radial-gradient(circle at top,#0b3c5d,#020617);
  color:#e5e7eb;
  font-family:Inter,system-ui,sans-serif;
}
.container{
  max-width:680px;
  margin:90px auto;
  background:rgba(255,255,255,.06);
  backdrop-filter:blur(14px);
  padding:42px;
  border-radius:24px;
  box-shadow:0 25px 60px rgba(0,0,0,.55);
}
label{font-weight:600;margin-bottom:6px;display:block}
input,select,textarea{
  width:100%;
  padding:14px;
  border-radius:12px;
  border:none;
  margin-bottom:18px;
}
textarea{min-height:110px}
button{
  width:100%;
  background:#25D366;
  color:#fff;
  font-weight:900;
  padding:16px;
  border-radius:16px;
  border:none;
  cursor:pointer;
}
.success{
  background:rgba(37,211,102,.15);
  border-left:6px solid #25D366;
  padding:22px;
  border-radius:16px;
}
.error{
  background:rgba(239,68,68,.15);
  border:1px solid #ef4444;
  padding:18px;
  border-radius:16px;
  margin-bottom:16px;
}
input[disabled]{
  background:rgba(255,255,255,.08);
  color:#fff;
  opacity:1;
}
a{color:#fff;text-decoration:underline}
hr{border:none;border-top:1px solid rgba(255,255,255,.2)}
.hint{opacity:.8;font-size:.92rem;margin-top:-12px;margin-bottom:18px}
</style>

<main>
  <div class="container">

    <?php if ($success): ?>

      <div class="success">
        <h2>&#10004; Préinscription envoyée avec succès</h2>
        <p>Merci pour votre intérêt. L’équipe IBIG EDUFORM vous contactera très rapidement.</p>

        <?php if (!empty($waLink)): ?>
          <hr style="margin:18px 0;opacity:.4">
          <p>&#10145; Continuer sur WhatsApp : <a href="<?= h($waLink); ?>" target="_blank" rel="noopener">cliquer ici</a></p>
          <script>
            setTimeout(function(){ window.open("<?= h($waLink); ?>","_blank","noopener"); }, 800);
          </script>
        <?php endif; ?>

        <?php if (!empty($waAdminLink)): ?>
          <p style="margin-top:12px;">
            &#10145; Notifier directement l’équipe IBIG EDUFORM :
            <br>
            <a href="<?= h($waAdminLink); ?>" target="_blank" rel="noopener" style="color:#22c55e;font-weight:900;">
              Envoyer la notification WhatsApp
            </a>
          </p>
        <?php endif; ?>
      </div>

    <?php else: ?>

      <?php if (!empty($error)): ?>
        <div class="error"><?= h($error); ?></div>
      <?php endif; ?>

      <h1>Préinscription à une formation</h1>

      <form method="post" enctype="multipart/form-data" novalidate>
        <?= csrf_field(); ?>

        <label>Formation choisie</label>
        <input type="text" value="<?= h($formation['titre']); ?>" disabled>

        <?php if (!empty($formation['slug'])): ?>
        <a href="/tdr-local-pdf.php?slug=<?= urlencode($formation['slug']); ?>" target="_blank" rel="noopener"
           style="display:inline-flex;align-items:center;gap:9px;margin:8px 0 16px;padding:12px 18px;background:linear-gradient(135deg,#1f3fe0,#3b82f6);color:#fff;border-radius:12px;text-decoration:none;font-weight:800;font-size:14.5px;box-shadow:0 10px 24px rgba(31,63,224,.28)">
          📄 Consulter le TDR de la formation
        </a>
        <?php endif; ?>

        <label>Nom *</label>
        <input name="nom" required value="<?= h($old['nom']); ?>">

        <label>Prénom(s) *</label>
        <input name="prenom" required value="<?= h($old['prenom']); ?>">

        <label>Email *</label>
        <input type="email" name="email" required value="<?= h($old['email']); ?>">

        <label>Téléphone (WhatsApp) *</label>
        <input name="telephone" required value="<?= h($old['telephone']); ?>">

        <label>Mode de formation *</label>
        <select name="mode_formation" required>
          <option value="">— Choisir —</option>
          <option value="en_ligne"   <?= ($old['mode_formation']==='en_ligne')?'selected':''; ?>>En ligne</option>
          <option value="presentiel" <?= ($old['mode_formation']==='presentiel')?'selected':''; ?>>Présentiel</option>
          <option value="hybride"    <?= ($old['mode_formation']==='hybride')?'selected':''; ?>>Hybride</option>
        </select>

        <label>Statut professionnel *</label>
        <select name="statut_professionnel" required>
          <option value="">— Choisir —</option>
          <option value="etudiant"         <?= ($old['statut_professionnel']==='etudiant')?'selected':''; ?>>Étudiant</option>
          <option value="salarie"          <?= ($old['statut_professionnel']==='salarie')?'selected':''; ?>>Salarié</option>
          <option value="entrepreneur"     <?= ($old['statut_professionnel']==='entrepreneur')?'selected':''; ?>>Entrepreneur</option>
          <option value="demandeur_emploi" <?= ($old['statut_professionnel']==='demandeur_emploi')?'selected':''; ?>>Demandeur d’emploi</option>
          <option value="fonctionnaire"    <?= ($old['statut_professionnel']==='fonctionnaire')?'selected':''; ?>>Fonctionnaire</option>
          <option value="autre"            <?= ($old['statut_professionnel']==='autre')?'selected':''; ?>>Autre</option>
        </select>

        <label>Objectif *</label>
        <select name="objectif" required>
          <option value="">— Choisir —</option>
          <option value="monter_competence" <?= ($old['objectif']==='monter_competence')?'selected':''; ?>>Monter en compétence</option>
          <option value="changer_metier"    <?= ($old['objectif']==='changer_metier')?'selected':''; ?>>Changer de métier</option>
          <option value="promotion"         <?= ($old['objectif']==='promotion')?'selected':''; ?>>Promotion professionnelle</option>
          <option value="lancer_activite"   <?= ($old['objectif']==='lancer_activite')?'selected':''; ?>>Lancer une activité</option>
          <option value="certification"     <?= ($old['objectif']==='certification')?'selected':''; ?>>Certification</option>
          <option value="autre"             <?= ($old['objectif']==='autre')?'selected':''; ?>>Autre</option>
        </select>

        <label>Disponibilité</label>
        <select name="disponibilite">
          <option value="">— Facultatif —</option>
          <option value="journee" <?= ($old['disponibilite']==='journee')?'selected':''; ?>>Journée</option>
          <option value="soir"    <?= ($old['disponibilite']==='soir')?'selected':''; ?>>Soir</option>
          <option value="weekend" <?= ($old['disponibilite']==='weekend')?'selected':''; ?>>Week-end</option>
        </select>

        <label>Ville</label>
        <input name="ville" value="<?= h($old['ville']); ?>">

        <label>Pays</label>
        <input name="pays" value="<?= h($old['pays']); ?>">

        <label>Domaine d’activité</label>
        <input name="domaine_activite" value="<?= h($old['domaine_activite']); ?>" placeholder="Ex : Finance, BTP, Santé, Commerce, Administration…">

        <label>Niveau d’étude</label>
        <select name="niveau_etude">
          <option value="">— Choisir —</option>
          <?php foreach (['Aucun / Primaire','BEPC','Bac','Bac+2','Bac+3','Bac+4','Bac+5','Doctorat'] as $lvl): ?>
            <option value="<?= h($lvl); ?>" <?= ($old['niveau_etude']===$lvl)?'selected':''; ?>><?= h($lvl); ?></option>
          <?php endforeach; ?>
        </select>

        <label>Fonction / Poste actuel</label>
        <input name="fonction" value="<?= h($old['fonction']); ?>" placeholder="Ex : Comptable, Étudiant, Gérant, Assistant RH…">

        <label>Années d’expérience</label>
        <select name="annees_experience">
          <option value="">— Choisir —</option>
          <?php foreach (['Aucune','Moins d’1 an','1 à 3 ans','3 à 5 ans','5 à 10 ans','Plus de 10 ans'] as $exp): ?>
            <option value="<?= h($exp); ?>" <?= ($old['annees_experience']===$exp)?'selected':''; ?>><?= h($exp); ?></option>
          <?php endforeach; ?>
        </select>

        <label>CV <small>(optionnel — PDF/DOC/DOCX, 2 Mo)</small></label>
        <input type="file" name="cv" accept=".pdf,.doc,.docx">

        <label>Pièce d’identité (CNI / Passeport) <small>(optionnel — PDF/JPG/PNG, 3 Mo)</small></label>
        <input type="file" name="cni" accept=".pdf,.jpg,.jpeg,.png">
        <div class="hint">Astuce : si le fichier est trop lourd, prenez une photo plus légère ou envoyez en PDF compressé.</div>

        <label>Message (optionnel)</label>
        <textarea name="message"><?= h($old['message']); ?></textarea>

        <button type="submit">Envoyer ma préinscription</button>
      </form>

    <?php endif; ?>

  </div>
</main>

<?php require __DIR__ . '/partials/footer.php'; ?>