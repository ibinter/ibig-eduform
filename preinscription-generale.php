<?php
declare(strict_types=1);

/**
 * ============================================================================
 * IBIG EDUFORM — preinscription_generale.php — MONOLITHIQUE (PROD) — A → Z
 * ----------------------------------------------------------------------------
 * â Préinscription générale (orientation libre OU formation choisie)
 * â CV optionnel (PDF/DOC/DOCX, 2 Mo)
 * â CNI/Passeport optionnel (PDF/JPG/JPEG/PNG, 3 Mo)
 * â Enregistrement centralisé via core/preinscription_service.php
 * â Sécurité ENUM (fallbacks si champs vides => évite "Erreur lors de l'enregistrement")
 * â Anti double submit (redirect success=1)
 * â UI Premium (Hero + Card + Grid + Mobile)
 * â Zéro warning "Undefined variable"
 * â PHP 7.4+
 * ============================================================================
 *
 * IMPORTANT :
 * - Ce fichier est monolithique (PHP + HTML + CSS dans le même fichier).
 * - Il dépend de : /core/preinscription_service.php (fonction enregistrer_preinscription)
 * - Il utilise : core/helpers.php / core/functions.php (is_post(), post(), clean(), e(), etc.)
 */

/* =============================================================================
   0) SESSION
============================================================================= */
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/* =============================================================================
   1) BOOTSTRAP (CORE)
============================================================================= */
require_once __DIR__ . '/core/config.php';
require_once __DIR__ . '/core/database.php';
require_once __DIR__ . '/core/helpers.php';
require_once __DIR__ . '/core/functions.php';
require_once __DIR__ . '/core/csrf.php';            // csrf_check()/csrf_field() — requis AVANT le POST (sinon fatal sur submit)
require_once __DIR__ . '/core/preinscription_service.php';

$pdo = Database::connect();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

/* =============================================================================
   2) DEBUG (désactiver en prod)
============================================================================= */
$DEBUG = false;
if ($DEBUG) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
}

/* =============================================================================
   3) HELPERS LOCAUX (SAFE) — sans casser tes helpers globaux
============================================================================= */
if (!function_exists('h')) {
    function h($v): string {
        return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Upload sécurisé générique
 * - Retourne [relativePath|null, errorMessage|null]
 * - Si pas de fichier => [null, null]
 */
if (!function_exists('secure_upload')) {
    function secure_upload(array $file, array $allowedExt, int $maxSize, string $dirAbs, string $prefix): array
    {
        $name = (string)($file['name'] ?? '');
        $tmp  = (string)($file['tmp_name'] ?? '');
        $size = (int)($file['size'] ?? 0);
        $err  = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);

        if ($name === '' || $err === UPLOAD_ERR_NO_FILE) {
            return [null, null]; // pas de fichier
        }

        if ($err !== UPLOAD_ERR_OK) {
            return [null, "Erreur d'envoi du fichier (code: {$err})."];
        }

        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExt, true)) {
            return [null, "Format de fichier non autorisé."];
        }

        if ($size <= 0 || $size > $maxSize) {
            return [null, "Fichier trop volumineux."];
        }

        if (!is_dir($dirAbs)) {
            @mkdir($dirAbs, 0775, true);
        }

        if (!is_dir($dirAbs) || !is_writable($dirAbs)) {
            return [null, "Dossier d'upload indisponible (permissions)."];
        }

        // Nom sécurisé
        try {
            $filename = $prefix . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
        } catch (Throwable $e) {
            $filename = $prefix . '_' . date('Ymd_His') . '_' . bin2hex(openssl_random_pseudo_bytes(6)) . '.' . $ext;
        }

        $destAbs = rtrim($dirAbs, '/\\') . DIRECTORY_SEPARATOR . $filename;

        if (!is_uploaded_file($tmp)) {
            return [null, "Upload invalide."];
        }

        if (!@move_uploaded_file($tmp, $destAbs)) {
            return [null, "Erreur lors de l'enregistrement du fichier."];
        }

        // Chemin relatif attendu (ex: uploads/cv/xxx.pdf)
        // On normalise : si ton $dirAbs est ".../uploads/cv", basename($dirAbs) => "cv"
        $rel = 'uploads/' . basename(str_replace('\\', '/', $dirAbs)) . '/' . $filename;

        return [$rel, null];
    }
}

/* =============================================================================
   4) UI STATE + STICKY VALUES
============================================================================= */
$pageTitle    = "Préinscription générale – IBIG EDUFORM";
$ogDesc       = "Déposez votre demande de préinscription à IBIG EDUFORM. Notre équipe vous recontacte sous 24h pour vous orienter vers la formation la mieux adaptée à vos objectifs professionnels.";
$success      = false;
$error        = '';
$waLink       = '';
$waAdminLink  = '';
$cvPath       = null;
$cniPath      = null;

// Anti double submit : si success=1 on affiche succès sans retraiter POST
if (isset($_GET['success']) && $_GET['success'] === '1') {
    $success = true;
}

// Pré-remplissage depuis le catalogue général (catalogue-formations.php)
$catalogue_nom    = isset($_GET['catalogue_nom'])    ? trim(strip_tags((string)$_GET['catalogue_nom']))    : '';
$catalogue_domaine= isset($_GET['domaine'])           ? trim(strip_tags((string)$_GET['domaine']))          : '';
$catalogue_slug   = isset($_GET['formation_slug'])   ? trim(strip_tags((string)$_GET['formation_slug']))   : '';
$catalogue_prix   = isset($_GET['catalogue_prix'])   ? (int)$_GET['catalogue_prix']                        : 0;

// Sticky values (pour éviter champs vides en cas d'erreur)
$old = [
    'formation_id'         => '',
    'domaine_interet'      => $catalogue_domaine, // pré-rempli depuis catalogue
    'nom'                  => '',
    'prenoms'              => '',
    'email'                => '',
    'telephone'            => '',
    'mode_formation'       => '',
    'format_formation'     => '',
    'date_debut_souhaitee' => '',
    'creneau_prefere'      => '',
    'statut_professionnel' => '',
    'objectif'             => '',
    'disponibilite'        => '',
    'ville'                => '',
    'pays'                 => "Côte d'Ivoire",
    'message'              => '',
    'domaine_activite'     => '',
    'niveau_etude'         => '',
    'fonction'             => '',
    'annees_experience'    => '',
];

/* =============================================================================
   5) FORMATIONS ACTIVES (OPTIONNEL)
============================================================================= */
$formations = [];
try {
    $formations = $pdo->query("
        SELECT id, titre
        FROM formations
        WHERE statut = 'active'
        ORDER BY titre ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('[PREINSCRIPTION GENERALE] load formations: ' . $e->getMessage());
}

/* =============================================================================
   6) TRAITEMENT FORMULAIRE (POST)
============================================================================= */
if (function_exists('is_post') && is_post()) {
    $success = false; // réinitialiser : un POST doit toujours être traité

    csrf_check();

    // Récupération
    $nom       = trim((string)clean(post('nom')));
    $prenoms   = trim((string)clean(post('prenoms')));
    $email     = trim((string)clean(post('email')));
    $telephone = trim((string)clean(post('telephone')));

    $formationIdRaw = (string)post('formation_id');
    $formationId    = ($formationIdRaw !== '') ? (int)$formationIdRaw : null;

    $domaine   = trim((string)clean(post('domaine_interet')));

    $mode         = (string)post('mode_formation');
    $formatForm   = (string)post('format_formation');
    $dateDebut    = trim((string)post('date_debut_souhaitee'));
    $creneauPref  = (string)post('creneau_prefere');
    $statutPro    = (string)post('statut_professionnel');
    $objectif  = (string)post('objectif');
    $dispo     = (string)post('disponibilite');

    $ville     = trim((string)clean(post('ville')));
    $pays      = trim((string)clean(post('pays', "Côte d'Ivoire")));
    $message   = trim((string)clean(post('message')));

    $domaineActivite = trim((string)clean(post('domaine_activite')));
    $niveauEtude     = trim((string)clean(post('niveau_etude')));
    $fonction        = trim((string)clean(post('fonction')));
    $anneesExp       = trim((string)clean(post('annees_experience')));

    // Sticky
    $old = [
        'formation_id'         => $formationIdRaw,
        'domaine_interet'      => $domaine,
        'nom'                  => $nom,
        'prenoms'              => $prenoms,
        'email'                => $email,
        'telephone'            => $telephone,
        'mode_formation'       => $mode,
        'format_formation'     => (string)post('format_formation'),
        'date_debut_souhaitee' => trim((string)post('date_debut_souhaitee')),
        'creneau_prefere'      => (string)post('creneau_prefere'),
        'statut_professionnel' => $statutPro,
        'objectif'             => $objectif,
        'disponibilite'        => $dispo,
        'ville'                => $ville,
        'pays'                 => $pays !== '' ? $pays : "Côte d'Ivoire",
        'message'              => $message,
        'domaine_activite'     => $domaineActivite,
        'niveau_etude'         => $niveauEtude,
        'fonction'             => $fonction,
        'annees_experience'    => $anneesExp,
    ];

    /* =========================================================================
       â ICI EXACTEMENT : tes lignes de fallback ENUM
       (évite INSERT refusé quand un champ ENUM arrive vide)
       -------------------------------------------------------------------------
       Tu m'as demandé où mettre :
         $mode      = $mode      ?: 'en_ligne';
         $statutPro = $statutPro ?: 'demandeur_emploi';
         $objectif  = $objectif  ?: 'monter_competence';
       => C'est ici, APRÈS lecture POST, AVANT validation et AVANT le service.
    ========================================================================= */
    $mode      = $mode      ?: 'en_ligne';
    $statutPro = $statutPro ?: 'demandeur_emploi';
    $objectif  = $objectif  ?: 'monter_competence';

    // On recopie dans $old pour garder l'affichage cohérent
    $old['mode_formation']       = $mode;
    $old['statut_professionnel'] = $statutPro;
    $old['objectif']             = $objectif;

    /* =========================================================================
       6.1) VALIDATION
    ========================================================================= */
    if ($nom === '' || $prenoms === '' || $telephone === '') {
        $error = "Merci de renseigner au minimum : Nom, Prénoms et Téléphone.";
    }

    // email : optionnel, mais si présent on valide
    if (!$error && $email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Adresse email invalide.";
    }

    // Domaine : conseillé (si orientation libre) => on n'oblige pas, mais on conseille via UI.
    // mode/statutPro/objectif : déjà fallback, mais on peut vérifier les valeurs autorisées
    if (!$error) {
        $allowedMode      = ['en_ligne','presentiel','hybride'];
        $allowedStatutPro = ['etudiant','salarie','entrepreneur','demandeur_emploi','fonctionnaire','autre'];
        $allowedObjectif  = ['monter_competence','changer_metier','promotion','lancer_activite','certification','autre'];
        $allowedDispo     = ['','journee','soir','weekend']; // '' autorisé (optionnel)

        if (!in_array($mode, $allowedMode, true)) {
            $mode = 'en_ligne';
        }
        if (!in_array($statutPro, $allowedStatutPro, true)) {
            $statutPro = 'demandeur_emploi';
        }
        if (!in_array($objectif, $allowedObjectif, true)) {
            $objectif = 'monter_competence';
        }
        if (!in_array($dispo, $allowedDispo, true)) {
            $dispo = '';
        }

        // refresh old
        $old['mode_formation']       = $mode;
        $old['statut_professionnel'] = $statutPro;
        $old['objectif']             = $objectif;
        $old['disponibilite']        = $dispo;
    }

    /* =========================================================================
       6.2) UPLOAD CV (OPTIONNEL — bloquant si fichier invalide)
    ========================================================================= */
    if (!$error) {
        $fileCv = $_FILES['cv'] ?? null;
        if (is_array($fileCv)) {
            [$cvPathTmp, $cvErr] = secure_upload(
                $fileCv,
                ['pdf','doc','docx'],
                2 * 1024 * 1024,
                __DIR__ . '/uploads/cv',
                'cv'
            );
            if ($cvErr) {
                // message friendly
                if (stripos($cvErr, 'Format') !== false) {
                    $error = "Format de CV non autorisé (PDF, DOC, DOCX).";
                } elseif (stripos($cvErr, 'volumineux') !== false) {
                    $error = "Le CV ne doit pas dépasser 2 Mo.";
                } else {
                    $error = $cvErr;
                }
            } else {
                $cvPath = $cvPathTmp;
            }
        }
    }

    /* =========================================================================
       6.3) UPLOAD CNI / PASSEPORT (OPTIONNEL — bloquant si fichier invalide)
    ========================================================================= */
    if (!$error) {
        $fileCni = $_FILES['cni'] ?? null;
        if (is_array($fileCni)) {
            [$cniPathTmp, $cniErr] = secure_upload(
                $fileCni,
                ['pdf','jpg','jpeg','png'],
                3 * 1024 * 1024,
                __DIR__ . '/uploads/cni',
                'cni'
            );
            if ($cniErr) {
                if (stripos($cniErr, 'Format') !== false) {
                    $error = "Format de pièce non autorisé (PDF, JPG, PNG).";
                } elseif (stripos($cniErr, 'volumineux') !== false) {
                    $error = "La pièce d'identité ne doit pas dépasser 3 Mo.";
                } else {
                    $error = $cniErr;
                }
            } else {
                $cniPath = $cniPathTmp;
            }
        }
    }

    /* =========================================================================
       6.4) ENREGISTREMENT CENTRALISÉ
    ========================================================================= */
    if (!$error) {
        try {

            // UTM (si tu les poses en session ailleurs)
            $utmSource = $_SESSION['utm_source'] ?? null;
            $utmCamp   = $_SESSION['utm_campaign'] ?? null;

            // disponibilite : nullable
            $dispoDb   = ($dispo !== '') ? $dispo : null;
            $messageDb = (trim($message) !== '') ? trim($message) : null;

            // Domaine : si vide, et formation choisie => on peut mettre le titre formation (optionnel)
            // Sinon on laisse NULL
            if ($domaine === '' && $formationId) {
                // lookup rapide du titre (optionnel) — sans casser si pas trouvé
                try {
                    $st = $pdo->prepare("SELECT titre FROM formations WHERE id=? LIMIT 1");
                    $st->execute([$formationId]);
                    $titreF = (string)($st->fetchColumn() ?: '');
                    if ($titreF !== '') {
                        $domaine = $titreF;
                        $old['domaine_interet'] = $domaine;
                    }
                } catch (Throwable $ignore) {}
            }

            $payload = [
                'type_preinscription'  => 'generale',
                'formation_id'         => $formationId,
                'domaine_interet'      => $domaine !== '' ? $domaine : null,

                'nom'                  => $nom,
                'prenoms'              => $prenoms,
                'email'                => $email !== '' ? $email : null,
                'telephone'            => $telephone,

                'mode_formation'       => $mode,
                'statut_professionnel' => $statutPro,
                'objectif'             => $objectif,
                'disponibilite'        => $dispoDb,
                'niveau'               => 'debutant',

                'ville'                => $ville !== '' ? $ville : null,
                'pays'                 => $pays !== '' ? $pays : "Côte d'Ivoire",

                'message'              => $messageDb,
                'cv_path'              => $cvPath,
                'cni_path'             => $cniPath,

                'utm_source'           => $utmSource,
                'utm_campaign'         => $utmCamp,

                'source'               => 'site_web',
                'statut'               => 'nouvelle',
            ];

            // IMPORTANT : la fonction se nomme enregistrer_preinscription
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
                            (int)$id,
                        ]);
                } catch (Throwable $e) { error_log('[PREINSC_GEN_PROFIL] ' . $e->getMessage()); }
            }

            /* WhatsApp (optionnel) */
            $waFile = __DIR__ . '/notifications/whatsapp.php';
            if (is_file($waFile)) {
                require_once $waFile;

                $label = ($formationId ? 'Orientation — ' . ($domaine ?: 'Formation choisie') : 'Préinscription générale');

                $data = [
                    'nom'       => $nom,
                    'prenom'    => $prenoms,
                    'telephone' => $telephone,
                    'email'     => $email,
                    'formation' => $label,
                    'preinscription_id' => (int)$id
                ];

                if (function_exists('whatsapp_link_candidat')) {
                    $waLink = (string)whatsapp_link_candidat($data);
                }
                if (function_exists('whatsapp_link_admin')) {
                    $waAdminLink = (string)whatsapp_link_admin($data);
                }
            }

            /* ─── Variables formation & token (toujours si slug présent) ────── */
            @set_time_limit(120);
            require_once __DIR__ . '/core/mail.php';
            require_once __DIR__ . '/core/tdr_generator.php';
            $nomFormation  = trim((string)post('catalogue_nom_display', $catalogue_nom));
            $slugForm      = preg_replace('/[^\w\-]/', '', (string)post('formation_slug', $catalogue_slug));
            $domaineForm   = trim((string)post('catalogue_domaine', (string)post('domaine_interet', '')));
            $nomProspect   = trim($prenoms . ' ' . $nom);
            $modeEmail     = $mode !== '' ? $mode : 'en_ligne';
            $formatEmail   = $formatForm !== '' ? $formatForm : 'individuel';
            $dateEmail     = $dateDebut;
            $creneauEmail  = $creneauPref;
            $tdrLink       = '';
            $prixE         = 0;
            $tranche1      = 0;

            /* Récupère les données depuis l'API catalogue */
            $prixFallback  = (int)post('catalogue_prix', (string)$catalogue_prix);
            $formationData = ['name' => $nomFormation, 'category' => $domaineForm, 'slug' => $slugForm, 'price' => $prixFallback, 'description' => ''];
            if ($slugForm !== '') {
                $ctx = stream_context_create([
                    'http' => ['timeout' => 6, 'ignore_errors' => true, 'method' => 'GET',
                               'header'  => "Accept: application/json\r\n"],
                    'ssl'  => ['verify_peer' => true],
                ]);
                $raw = @file_get_contents('https://www.ibigpartners.com/api/catalogue', false, $ctx);
                if ($raw !== false) {
                    $apiData = json_decode($raw, true);
                    if (is_array($apiData) && !empty($apiData['ok'])) {
                        foreach (($apiData['formations'] ?? []) as $apiF) {
                            if (($apiF['slug'] ?? '') === $slugForm) {
                                $g = $apiF['grille'] ?? [];
                                $indivOnline = 0;
                                foreach ($g as $line) {
                                    if (stripos((string)($line['label'] ?? ''), 'individu') !== false &&
                                        stripos((string)($line['label'] ?? ''), 'ligne') !== false) {
                                        $indivOnline = (int)$line['price'];
                                        break;
                                    }
                                }
                                if ($indivOnline === 0 && !empty($g)) $indivOnline = (int)($g[0]['price'] ?? 0);
                                if ($indivOnline > 0) $formationData['price'] = $indivOnline;
                                $formationData['category']    = (string)($apiF['category'] ?? $domaineForm);
                                $formationData['description'] = (string)($apiF['description'] ?? '');
                                $formationData['name']        = (string)($apiF['name'] ?? $nomFormation);
                                break;
                            }
                        }
                    }
                }
            }

            /* Correction tarifaire */
            if ($formationData['price'] > 0 && stripos($formationData['name'], 'samedi') === false) {
                $_corrTxt = $formationData['name'] . ' ' . $formationData['description'];
                if (preg_match('/\((\d+)h\)/', $_corrTxt, $_cm)) {
                    $_PFORM = [20=>225000,25=>280000,28=>315000,30=>340000,35=>395000,40=>450000,45=>505000,55=>620000,65=>730000,72=>810000,80=>900000];
                    $_ch    = (int)$_cm[1];
                    $_pfx   = isset($_PFORM[$_ch]) ? $_PFORM[$_ch] : (int)(round($_ch * 11250 / 5000) * 5000);
                    if ($formationData['price'] < $_pfx) $formationData['price'] = $_pfx;
                    unset($_PFORM, $_ch, $_pfx);
                }
                if ($formationData['price'] < 200000) $formationData['price'] = 200000;
                unset($_corrTxt, $_cm);
            }

            /* ── Génère le token (dès qu'on a un slug) ── */
            if ($slugForm !== '') {
                $tdrSecret  = TDR_SECRET;
                $tdrExp     = time() + 72 * 3600;
                $tdrSig     = hash_hmac('sha256', $slugForm . '|' . $tdrExp . '|' . $formatEmail . '|' . $modeEmail, $tdrSecret);
                $tdrPayload = base64_encode(json_encode([
                    'slug'     => $slugForm,
                    'nom'      => $formationData['name'],
                    'cat'      => $formationData['category'],
                    'prix'     => $formationData['price'],
                    'desc'     => mb_substr((string)($formationData['description'] ?? ''), 0, 400, 'UTF-8'),
                    'fmt'      => $formatEmail,
                    'mode'     => $modeEmail,
                    'date'     => $dateEmail,
                    'cren'     => $creneauEmail,
                    'prospect' => $nomProspect,
                    'pid'      => (int)$id,
                    'exp'      => $tdrExp,
                    'sig'      => $tdrSig,
                ]));
                $tdrToken   = rtrim(strtr($tdrPayload, '+/', '-_'), '=');
                $baseUrl    = defined('BASE_URL') ? rtrim((string)BASE_URL, '/') : 'https://ibig-eduform.com';
                $tdrLink    = $baseUrl . '/tdr-download.php?t=' . $tdrToken;

                /* Calcul prix pour affichage page & email */
                $r5e   = fn(float $v): int => (int)(round($v / 5000) * 5000);
                $px    = (int)$formationData['price'];
                $ip2   = $px > 0 ? $r5e($px * 10 / 7) : 0;
                $g35e  = $r5e($ip2 * 0.70);
                $g61e  = $r5e($ip2 * 0.55);
                $g10e  = $r5e($ip2 * 0.45);
                $grilleE = $px > 0 ? [
                    'elearning_online'=>$r5e($px*0.5),'individuel_online'=>$px,'individuel_pres'=>$ip2,
                    'hybride'=>$r5e(($px+$ip2)/2),
                    'groupe_3_5_online'=>$r5e($g35e*0.70),'groupe_3_5_pres'=>$g35e,
                    'groupe_6_10_online'=>$r5e($g61e*0.70),'groupe_6_10_pres'=>$g61e,
                    'groupe_10p_online'=>$r5e($g10e*0.70),'groupe_10p_pres'=>$g10e,
                ] : [];
                $fmtKey = ['individuel'=>'individuel_online','groupe_3_5'=>'groupe_3_5_online','groupe_6_10'=>'groupe_6_10_online','groupe_10p'=>'groupe_10p_online'];
                if ($formatEmail === 'groupe_devis') {
                    $prixE = 0;
                } elseif ($modeEmail === 'hybride') {
                    $prixE = $grilleE['hybride'] ?? $px;
                } else {
                    $keyE  = $fmtKey[$formatEmail] ?? 'individuel_online';
                    if ($modeEmail === 'presentiel') $keyE = str_replace('_online','_pres',$keyE);
                    $prixE = $grilleE[$keyE] ?? $px;
                }
                $tranche1 = $prixE > 0 ? (int)(round($prixE * 0.5 / 5000) * 5000) : 0;
            }

            /* ─── Email de confirmation (uniquement si email fourni) ────── */
            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) && $tdrLink !== '') {
                try {
                    $waConfirm  = 'https://wa.me/2250778882592?text=' . rawurlencode('Bonjour IBIG EDUFORM, j\'ai reçu mon programme pour "' . $formationData['name'] . '" et je souhaite confirmer mon inscription.');

                    $modeLabelsE  = ['en_ligne'=>'En ligne','presentiel'=>'Présentiel','hybride'=>'Hybride (En ligne et en présentiel)'];
                    $fmtLabelsE   = ['individuel'=>'Individuel','groupe_3_5'=>'Groupe 3–5','groupe_6_10'=>'Groupe 6–10','groupe_10p'=>'Groupe 10+','groupe_devis'=>'Groupe — sur devis'];
                    $fcfaE  = fn(int $v): string => number_format($v, 0, ',', ' ') . ' F CFA';

                    $nomFH    = htmlspecialchars($nomProspect, ENT_QUOTES, 'UTF-8');
                    $nomFormH = htmlspecialchars($formationData['name'] ?: $nomFormation, ENT_QUOTES, 'UTF-8');
                    $dateH    = $dateEmail !== '' ? htmlspecialchars(date('d/m/Y', strtotime($dateEmail)), ENT_QUOTES, 'UTF-8') : '—';
                    $fmtH     = htmlspecialchars($fmtLabelsE[$formatEmail] ?? $formatEmail, ENT_QUOTES, 'UTF-8');
                    $modeH    = htmlspecialchars($modeLabelsE[$modeEmail] ?? $modeEmail, ENT_QUOTES, 'UTF-8');

                    $emailBody = '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"></head><body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,sans-serif">
<div style="max-width:620px;margin:24px auto;background:#fff;border-radius:10px;overflow:hidden;border:1px solid #e5e7eb">

  <!-- EN-TÊTE -->
  <div style="background:linear-gradient(135deg,#0a1733,#1d4ed8);color:#fff;padding:28px 30px;text-align:center">
    <p style="margin:0 0 4px;font-size:11px;opacity:.7;text-transform:uppercase;letter-spacing:.08em">IBIG EDUFORM — Institut de Formation Professionnelle</p>
    <h1 style="margin:0;font-size:22px">✅ Votre programme est prêt !</h1>
    <p style="margin:8px 0 0;opacity:.85;font-size:14px">Bonjour <strong>' . $nomFH . '</strong>, votre TDR personnalisé est ci-dessous.</p>
  </div>

  <!-- RÉSUMÉ INSCRIT -->
  <div style="padding:22px 28px;background:#fffbeb;border-bottom:2px solid #f59e0b">
    <p style="margin:0 0 10px;font-size:13px;font-weight:700;color:#0a1733">📋 Récapitulatif de votre demande</p>
    <table style="width:100%;font-size:12px;border-collapse:collapse">
      <tr><td style="padding:4px 8px;color:#6b7280;width:160px">Formation</td><td style="padding:4px 8px;font-weight:700;color:#0a1733">' . $nomFormH . '</td></tr>
      <tr><td style="padding:4px 8px;color:#6b7280">Modalité</td><td style="padding:4px 8px">' . $modeH . '</td></tr>
      <tr><td style="padding:4px 8px;color:#6b7280">Format</td><td style="padding:4px 8px">' . $fmtH . '</td></tr>
      ' . ($dateEmail !== '' ? '<tr><td style="padding:4px 8px;color:#6b7280">Date souhaitée</td><td style="padding:4px 8px">' . $dateH . '</td></tr>' : '') . '
      ' . ($prixE > 0 ? '<tr><td style="padding:4px 8px;color:#6b7280">Tarif (forfait)</td><td style="padding:4px 8px;font-weight:700;color:#b45309">' . $fcfaE($prixE) . '</td></tr>' : '') . '
    </table>
  </div>

  <!-- TDR APERÇU + LIEN -->
  <div style="padding:22px 28px">
    <p style="font-size:14px;color:#1f2937;line-height:1.7">Votre <strong>programme détaillé (TDR)</strong> figure dans cet email. Vous pouvez également le <strong>télécharger en PDF</strong> en cliquant ci-dessous :</p>
    <div style="text-align:center;margin:18px 0">
      <a href="' . htmlspecialchars($tdrLink, ENT_QUOTES, 'UTF-8') . '" style="background:#0a1733;color:#fff;text-decoration:none;padding:13px 28px;border-radius:8px;font-weight:700;font-size:14px;display:inline-block">📄 Télécharger mon programme en PDF</a>
    </div>
    <p style="font-size:12px;color:#6b7280;text-align:center">Lien valable 72 heures. Après impression → Fichier → Enregistrer en PDF.</p>
  </div>

  <!-- ÉTAPE SUIVANTE : PAIEMENT -->
  <div style="padding:20px 28px;background:#f0fdf4;border-top:2px solid #22c55e;border-bottom:2px solid #22c55e">
    <p style="margin:0 0 6px;font-size:15px;font-weight:900;color:#15803d">🎯 Étape suivante — Obtenir votre devis</p>
    ' . ($formatEmail === 'groupe_devis'
      ? '<p style="font-size:13px;color:#1f2937;line-height:1.7;margin:0 0 14px">Votre demande de <strong>formation groupe</strong> a bien été reçue. Notre équipe vous contactera sous 24h pour établir un <strong>devis personnalisé</strong> selon le nombre de participants, le lieu et le calendrier souhaités.</p>'
      : '<p style="font-size:13px;color:#1f2937;line-height:1.7;margin:0 0 14px">Pour sécuriser votre place et activer votre parcours, réglez la <strong>première tranche</strong>' . ($tranche1 > 0 ? ' de <strong style="color:#15803d">' . $fcfaE($tranche1) . '</strong>' : '') . ' — soit 50 % du forfait. Le solde est dû à mi-parcours.</p>'
    ) . '
    ' . ($formatEmail !== 'groupe_devis' ? '
    <p style="font-size:13px;font-weight:700;color:#0a1733;margin:0 0 10px">Moyens de paiement acceptés :</p>
    <table style="width:100%;font-size:12px;border-collapse:collapse">
      <tr><td style="padding:5px 10px;background:#fff;border:1px solid #d1fae5;border-radius:4px;margin-bottom:4px">💳 <strong>Virement bancaire</strong> — coordonnées transmises sur demande</td></tr>
      <tr><td style="padding:5px 10px;background:#fff;border:1px solid #d1fae5;margin-top:4px">📱 <strong>Mobile Money</strong> — Orange Money / MTN MoMo / Wave (CI)</td></tr>
      <tr><td style="padding:5px 10px;background:#fff;border:1px solid #d1fae5">💵 <strong>Espèces</strong> — contre reçu au siège IBIG EDUFORM (Abidjan)</td></tr>
    </table>' : '') . '
    <div style="text-align:center;margin-top:16px;display:flex;gap:10px;justify-content:center;flex-wrap:wrap">
      <a href="' . htmlspecialchars($waConfirm, ENT_QUOTES, 'UTF-8') . '" style="background:#25d366;color:#fff;text-decoration:none;padding:11px 22px;border-radius:8px;font-weight:700;font-size:13px">' . ($formatEmail === 'groupe_devis' ? '💬 Demander mon devis groupe' : '💬 Confirmer sur WhatsApp') . '</a>
      <a href="tel:+2252722276014" style="background:#0a1733;color:#fff;text-decoration:none;padding:11px 22px;border-radius:8px;font-weight:700;font-size:13px">📞 Nous appeler</a>
    </div>
  </div>

  <!-- CONTACTS -->
  <div style="padding:16px 28px;font-size:12px;color:#6b7280;text-align:center">
    <strong style="color:#0a1733">IBIG EDUFORM</strong> &nbsp;·&nbsp;
    <a href="mailto:formation@ibig-eduform.com" style="color:#1d4ed8">formation@ibig-eduform.com</a> &nbsp;·&nbsp;
    +225 27 22 27 60 14 &nbsp;·&nbsp; +225 07 78 88 25 92 (WhatsApp)<br>
    <a href="https://ibig-eduform.com" style="color:#1d4ed8">ibig-eduform.com</a>
  </div>

</div>

</body></html>';

                    send_mail($email,
                        'IBIG EDUFORM — Votre programme + étapes pour démarrer : ' . ($formationData['name'] ?: $nomFormation),
                        $emailBody
                    );

                    /* Copie admin */
                    $adminMail = defined('ADMIN_EMAIL') ? (string)ADMIN_EMAIL : 'inscription@ibig-eduform.com';
                    @send_mail($adminMail,
                        'Nouvelle préinscription — ' . ($nomFormation ?: 'Orientation libre') . ' (' . $nom . ' ' . $prenoms . ')',
                        '<p>Nom : ' . htmlspecialchars($nom . ' ' . $prenoms) . '<br>Email : ' . htmlspecialchars($email) . '<br>Tél : ' . htmlspecialchars($telephone) . '<br>Formation : ' . htmlspecialchars($nomFormation ?: 'Orientation libre') . '<br>Domaine : ' . htmlspecialchars($domaineForm) . '</p>'
                    );
                } catch (Throwable $e) {
                    error_log('[PREINSC_EMAIL] ' . $e->getMessage());
                }
            }

            // Anti double submit : redirect success (save TDR link in session)
            if (isset($tdrLink) && $tdrLink !== '') {
                $_SESSION['last_tdr_link']     = $tdrLink;
                $_SESSION['last_tdr_nom']      = $formationData['name'] ?: $nomFormation;
                $_SESSION['last_tdr_prospect'] = $nomProspect;
                $_SESSION['last_tdr_email']    = $email;
                $_SESSION['last_tdr_prix']     = $prixE;
                $_SESSION['last_tdr_tranche']  = $tranche1;
            }
            $redirect = strtok($_SERVER['REQUEST_URI'], '?');
            header('Location: '.$redirect.'?success=1');
            exit;

        } catch (Throwable $e) {
            error_log('[PREINSCRIPTION GENERALE] ' . $e->getMessage());
            $error = $DEBUG ? ("Erreur SQL : " . $e->getMessage()) : "Erreur lors de l'enregistrement.";
        }
    }
}

/* =============================================================================
   7) HEADER (APRES TRAITEMENT)
============================================================================= */
require __DIR__ . '/partials/header.php';
?>

<style>
/* =============================================================================
   IBIG EDUFORM — UI PREMIUM (MONOLITHIQUE)
   - Dark glassmorphism + contrast + mobile-first
   - Select/option visible (Windows)
============================================================================= */

/* ===== Base ===== */
:root{
  --bg0:#020617;
  --bg1:#071429;
  --glass:rgba(255,255,255,.06);
  --glass2:rgba(255,255,255,.10);
  --stroke:rgba(255,255,255,.14);
  --text:#e5e7eb;
  --muted:rgba(229,231,235,.78);
  --muted2:rgba(229,231,235,.60);
  --green:#22c55e;
  --green2:#16a34a;
  --red:#ef4444;
  --amber:#f59e0b;
  --shadow:0 25px 60px rgba(0,0,0,.55);
  --radius:26px;
  --radius2:18px;
  --radius3:14px;
  --focus:0 0 0 3px rgba(34,197,94,.35);
  --font: Inter, system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
}

html,body{height:100%}
body{
  margin:0;
  color:var(--text);
  font-family:var(--font);
  background:
    radial-gradient(1200px 520px at 20% 0%, rgba(34,197,94,.12), transparent 55%),
    radial-gradient(900px 420px at 85% 10%, rgba(59,130,246,.10), transparent 55%),
    radial-gradient(900px 520px at 55% 100%, rgba(245,158,11,.08), transparent 55%),
    radial-gradient(circle at top,#0b3c5d,var(--bg0));
}

/* ===== Layout ===== */
.ibig-wrap{
  max-width: 1040px;
  margin: 0 auto;
  padding: 26px 16px 90px;
}

/* ===== Hero ===== */
.hero{
  max-width: 980px;
  margin: 72px auto 26px;
  text-align:center;
  padding: 0 12px;
}
.hero-badge{
  display:inline-flex;
  align-items:center;
  gap:10px;
  padding: 10px 14px;
  border-radius: 999px;
  background: rgba(34,197,94,.10);
  border: 1px solid rgba(34,197,94,.28);
  color: rgba(229,231,235,.92);
  font-weight: 800;
  letter-spacing: .2px;
}
.hero h1{
  margin: 14px 0 10px;
  font-size: 2.25rem;
  line-height: 1.1;
  font-weight: 950;
}
.hero p{
  margin: 0 auto;
  max-width: 760px;
  color: var(--muted);
  font-size: 1.06rem;
  line-height: 1.55;
}
.hero-note{
  margin: 14px auto 0;
  max-width: 840px;
  color: rgba(229,231,235,.72);
  font-size: .95rem;
}
.hero-note strong{color:rgba(229,231,235,.92)}

/* ===== Card ===== */
.card{
  max-width: 980px;
  margin: 0 auto;
  background: var(--glass);
  backdrop-filter: blur(14px);
  border: 1px solid var(--stroke);
  border-radius: var(--radius);
  box-shadow: var(--shadow);
  overflow: hidden;
}
.card-head{
  padding: 22px 26px 18px;
  border-bottom: 1px solid rgba(255,255,255,.10);
  display:flex;
  align-items:flex-start;
  justify-content: space-between;
  gap: 18px;
}
.card-head .title{
  display:flex;
  flex-direction:column;
  gap:6px;
}
.card-head .title h2{
  margin:0;
  font-size: 1.35rem;
  font-weight: 950;
}
.card-head .title .sub{
  margin:0;
  color: var(--muted);
  font-size: .96rem;
}
.card-head .trust{
  display:flex;
  align-items:center;
  gap:10px;
  color: rgba(229,231,235,.80);
  font-weight: 800;
  font-size: .92rem;
  padding: 10px 12px;
  border-radius: 999px;
  background: rgba(255,255,255,.06);
  border: 1px solid rgba(255,255,255,.10);
  white-space: nowrap;
}
.dot{
  width:10px;height:10px;border-radius:999px;
  background: rgba(34,197,94,.9);
  box-shadow: 0 0 0 3px rgba(34,197,94,.16);
}

/* ===== Body ===== */
.card-body{
  padding: 26px;
}

/* ===== Alerts ===== */
.alert{
  border-radius: 18px;
  padding: 18px 18px;
  margin: 0 0 18px;
  border: 1px solid transparent;
}
.alert.error{
  background: rgba(239,68,68,.13);
  border-color: rgba(239,68,68,.38);
}
.alert.error .t{
  font-weight: 950;
  margin: 0 0 6px;
}
.alert.error .d{
  margin: 0;
  color: rgba(255,255,255,.88);
}
.alert.success{
  background: rgba(34,197,94,.14);
  border-color: rgba(34,197,94,.35);
}
.alert.success .t{
  font-weight: 950;
  margin: 0 0 8px;
}
.alert.success .d{
  margin: 0;
  color: rgba(255,255,255,.88);
}

/* ===== Form grid ===== */
.grid{
  display:grid;
  grid-template-columns: 1fr 1fr;
  gap: 18px 18px;
}
.grid-full{ grid-column: 1 / -1; }

/* ===== Fields ===== */
.field label{
  display:block;
  margin: 0 0 8px;
  font-weight: 900;
  color: rgba(229,231,235,.92);
  font-size: .92rem;
  letter-spacing: .15px;
}
.req{
  color: rgba(34,197,94,.92);
  font-weight: 950;
  margin-left: 6px;
}
.input, select, textarea{
  width: 100%;
  box-sizing: border-box;
  padding: 14px 14px;
  border-radius: var(--radius3);
  border: 1px solid rgba(255,255,255,.12);
  background: rgba(255,255,255,.10);
  color: var(--text);
  outline: none;
  transition: .18s ease;
  font-size: 1rem;
}
textarea{ min-height: 120px; resize: vertical; }
.input::placeholder, textarea::placeholder{
  color: rgba(229,231,235,.48);
}
.input:focus, select:focus, textarea:focus{
  border-color: rgba(34,197,94,.55);
  box-shadow: var(--focus);
}

/* ===== Select options visible ===== */
select{
  appearance:auto;
  -webkit-appearance:auto;
  -moz-appearance:auto;
  background-color: rgba(255,255,255,.12);
}
select option{
  background: #0b1220;
  color: #ffffff;
  padding: 12px;
}
select option[value=""]{
  color: #9ca3af;
}

/* ===== File input styling ===== */
.file{
  padding: 12px 12px;
}
.hint{
  margin-top: 8px;
  color: rgba(229,231,235,.68);
  font-size: .92rem;
  line-height: 1.35;
}
.kpi-row{
  display:flex;
  flex-wrap: wrap;
  gap: 10px;
  margin: 0 0 18px;
}
.kpi{
  flex: 1 1 auto;
  min-width: 160px;
  border-radius: 18px;
  background: rgba(255,255,255,.06);
  border: 1px solid rgba(255,255,255,.10);
  padding: 12px 14px;
}
.kpi .k{ font-weight: 950; margin:0 0 4px; }
.kpi .v{ margin:0; color: rgba(229,231,235,.75); font-size:.95rem; }

/* ===== Actions ===== */
.actions{
  display:flex;
  gap: 12px;
  align-items:center;
  justify-content: space-between;
  margin-top: 8px;
}
.btn{
  width: 100%;
  display:inline-flex;
  align-items:center;
  justify-content:center;
  gap: 10px;
  padding: 16px 18px;
  border-radius: 18px;
  border: 1px solid rgba(34,197,94,.25);
  background: linear-gradient(135deg, var(--green), var(--green2));
  color: #fff;
  font-weight: 950;
  font-size: 1.05rem;
  cursor:pointer;
  transition: .18s ease;
}
.btn:hover{
  transform: translateY(-1px);
  filter: brightness(1.02);
}
.btn:active{ transform: translateY(0); }

/* ===== Links ===== */
.a{
  color: rgba(229,231,235,.92);
  text-decoration: underline;
}
.a.green{ color: rgba(34,197,94,.95); font-weight: 950; }

/* ===== Footer strip inside card ===== */
.card-foot{
  padding: 16px 26px;
  border-top: 1px solid rgba(255,255,255,.10);
  color: rgba(229,231,235,.70);
  font-size: .92rem;
  display:flex;
  flex-wrap: wrap;
  gap: 10px;
  align-items:center;
  justify-content: space-between;
}
.pill{
  padding: 8px 10px;
  border-radius: 999px;
  background: rgba(255,255,255,.06);
  border: 1px solid rgba(255,255,255,.10);
  font-weight: 900;
}

/* ===== Mobile ===== */
@media (max-width: 900px){
  .hero h1{ font-size: 2rem; }
}
@media (max-width: 768px){
  .grid{ grid-template-columns: 1fr; }
  .card-head{ flex-direction: column; align-items:flex-start; }
  .card-body{ padding: 20px; }
  .card-head{ padding: 18px 20px 14px; }
}
</style>

<main class="ibig-wrap">

  <section class="hero">
    <div class="hero-badge">
      <span style="display:inline-block;width:10px;height:10px;border-radius:999px;background:rgba(34,197,94,.95);box-shadow:0 0 0 3px rgba(34,197,94,.18)"></span>
      IBIG EDUFORM — Orientation & Préinscription
    </div>

    <h1>Préinscription Générale</h1>

    <p>
      Vous hésitez sur la formation idéale ?
      Déposez votre demande et notre équipe vous recontacte rapidement pour vous orienter et finaliser votre inscription.
    </p>

    <div class="hero-note">
      <strong>Conseil :</strong> si vous n'avez pas choisi de formation, renseignez au moins un <strong>domaine d'intérêt</strong>
      (ex: Finance, QHSE, Informatique, Management…).
    </div>
  </section>

  <section class="card">
    <div class="card-head">
      <div class="title">
        <h2>Formulaire de préinscription</h2>
        <p class="sub">Remplissez les informations. Les champs marqués * sont recommandés.</p>
      </div>
      <div class="trust">
        <span class="dot"></span>
        Traitement rapide
      </div>
    </div>

    <div class="card-body">

      <?php if ($success):
          // Récupère infos TDR depuis la session (générées au moment du POST)
          $sTdrLink     = (string)($_SESSION['last_tdr_link']     ?? '');
          $sTdrNom      = (string)($_SESSION['last_tdr_nom']      ?? '');
          $sTdrProspect = (string)($_SESSION['last_tdr_prospect'] ?? '');
          $sTdrEmail    = (string)($_SESSION['last_tdr_email']    ?? '');
          $sTdrPrix     = (int)($_SESSION['last_tdr_prix']        ?? 0);
          $sTdrTranche  = (int)($_SESSION['last_tdr_tranche']     ?? 0);
          $sFcfa = fn(int $v): string => number_format($v, 0, ',', ' ') . ' FCFA';
          // Efface après lecture pour éviter réaffichage sur refresh
          unset($_SESSION['last_tdr_link'],$_SESSION['last_tdr_nom'],$_SESSION['last_tdr_prospect'],
                $_SESSION['last_tdr_email'],$_SESSION['last_tdr_prix'],$_SESSION['last_tdr_tranche']);
      ?>

        <div class="alert success">
          <p class="t">✅ Préinscription envoyée avec succès !</p>
          <p class="d">Merci<?= $sTdrProspect ? ', <strong>' . h($sTdrProspect) . '</strong>' : ''; ?>. L'équipe IBIG EDUFORM vous contactera très rapidement.</p>
          <?php if ($sTdrEmail !== ''): ?>
            <p class="d" style="margin-top:6px">📧 Un email avec votre programme détaillé a été envoyé à <strong><?= h($sTdrEmail) ?></strong>.</p>
          <?php endif; ?>
        </div>

        <?php if ($sTdrLink !== ''): ?>
        <!-- Bloc TDR download -->
        <div style="background:linear-gradient(135deg,#0a1733,#1d4ed8);border-radius:16px;padding:22px 24px;margin:18px 0;color:#fff">
          <p style="margin:0 0 6px;font-size:1rem;font-weight:900">📄 Votre programme de formation (TDR) est prêt</p>
          <?php if ($sTdrNom !== ''): ?><p style="margin:0 0 14px;opacity:.8;font-size:.92rem"><?= h($sTdrNom) ?></p><?php endif; ?>
          <div style="display:flex;flex-wrap:wrap;gap:10px;align-items:center">
            <a href="<?= h($sTdrLink) ?>" target="_blank" rel="noopener"
               style="background:#f59e0b;color:#0a1733;font-weight:900;padding:12px 24px;border-radius:10px;text-decoration:none;font-size:.97rem;display:inline-flex;align-items:center;gap:8px">
              📥 Voir &amp; Télécharger mon TDR en PDF
            </a>
            <?php if ($sTdrPrix > 0): ?>
            <span style="opacity:.75;font-size:.88rem">Tarif : <strong><?= $sFcfa($sTdrPrix) ?></strong><?= $sTdrTranche > 0 ? ' — 1ère tranche : <strong>' . $sFcfa($sTdrTranche) . '</strong>' : '' ?></span>
            <?php endif; ?>
          </div>
          <p style="margin:12px 0 0;font-size:.8rem;opacity:.6">Lien valable 72 heures. Après ouverture → Fichier → Enregistrer en PDF.</p>
        </div>
        <?php elseif ($catalogue_nom !== ''): ?>
        <!-- Pas de slug → lien générique vers l'outil TDR -->
        <div style="background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.15);border-radius:14px;padding:18px 22px;margin:16px 0">
          <p style="margin:0 0 10px;font-weight:900;font-size:.97rem">📄 Consultez votre programme de formation</p>
          <p style="margin:0 0 12px;color:rgba(229,231,235,.78);font-size:.91rem">Notre équipe vous enverra le programme détaillé par email ou WhatsApp.</p>
          <a href="/outils/" target="_blank" rel="noopener"
             style="background:#1d4ed8;color:#fff;font-weight:700;padding:10px 20px;border-radius:8px;text-decoration:none;font-size:.91rem">
            🛠️ Générateur de TDR IBIG EDUFORM
          </a>
        </div>
        <?php endif; ?>

        <?php if (!empty($waLink)): ?>
          <div style="margin-top:14px">
            <a class="a green" href="<?= h($waLink); ?>" target="_blank" rel="noopener">Continuer sur WhatsApp</a>
          </div>
          <script>
            setTimeout(function(){
              try { window.open("<?= h($waLink); ?>","_blank","noopener"); } catch(e){}
            }, 800);
          </script>
        <?php endif; ?>

        <?php if (!empty($waAdminLink)): ?>
          <div style="margin-top:10px">
            <a class="a" href="<?= h($waAdminLink); ?>" target="_blank" rel="noopener">Notifier l'équipe (WhatsApp)</a>
          </div>
        <?php endif; ?>

      <?php else: ?>

        <?php if ($error): ?>
          <div class="alert error">
            <p class="t">Impossible d'envoyer</p>
            <p class="d"><?= h($error); ?></p>

            <?php if ($DEBUG): ?>
              <p class="d" style="margin-top:10px;color:rgba(255,255,255,.65)">
                Debug activé. Vérifiez les logs serveur.
              </p>
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <div class="kpi-row">
          <div class="kpi">
            <p class="k">Réponse</p>
            <p class="v">Sous 24h (souvent plus rapide)</p>
          </div>
          <div class="kpi">
            <p class="k">Orientation</p>
            <p class="v">Conseil + parcours adapté</p>
          </div>
          <div class="kpi">
            <p class="k">Sécurité</p>
            <p class="v">Données protégées (ERP/CRM)</p>
          </div>
        </div>

        <?php if ($catalogue_nom !== ''): ?>
        <!-- Bannière formation choisie depuis le catalogue -->
        <div style="background:linear-gradient(90deg,#0a1733,#0d2260);color:#fff;border-radius:12px;padding:14px 18px;margin-bottom:20px;display:flex;align-items:center;gap:12px">
          <span style="font-size:1.4rem">🎓</span>
          <div>
            <div style="font-size:.72rem;color:#b8ccf0;font-weight:700;text-transform:uppercase;letter-spacing:.05em">Formation sélectionnée</div>
            <div style="font-size:.97rem;font-weight:900;margin-top:2px"><?= h($catalogue_nom) ?></div>
            <?php if ($catalogue_domaine): ?><div style="font-size:.75rem;color:#f5b73d;margin-top:2px"><?= h($catalogue_domaine) ?></div><?php endif; ?>
          </div>
          <a href="/catalogue-formations.php" style="margin-left:auto;font-size:.75rem;color:#b8ccf0;text-decoration:underline;white-space:nowrap">← Retour au catalogue</a>
        </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" novalidate>
        <?= csrf_field(); ?>
        <?php if ($catalogue_nom !== ''): ?>
          <input type="hidden" name="catalogue_nom" value="<?= h($catalogue_nom) ?>">
        <?php endif; ?>

          <div class="grid">

            <div class="field">
              <label>Formation souhaitée</label>
              <?php if ($catalogue_nom !== ''): ?>
              <!-- Formation du catalogue — champ texte (non dans la liste MySQL) -->
              <input class="input" type="text" name="catalogue_nom_display" value="<?= h($catalogue_nom) ?>" readonly
                     style="font-weight:700;cursor:default;opacity:.85">
              <input type="hidden" name="formation_slug" value="<?= h($catalogue_slug) ?>">
              <input type="hidden" name="catalogue_domaine" value="<?= h($catalogue_domaine) ?>">
              <input type="hidden" name="catalogue_prix" value="<?= (int)$catalogue_prix ?>">
              <div class="hint">Formation issue du catalogue général. Nos conseillers confirmeront les détails par téléphone.</div>
              <?php else: ?>
              <select name="formation_id">
                <option value="">Orientation libre</option>
                <?php foreach ($formations as $f): ?>
                  <?php
                    $fid = (int)($f['id'] ?? 0);
                    $sel = ((string)$fid === (string)$old['formation_id']) ? 'selected' : '';
                  ?>
                  <option value="<?= $fid; ?>" <?= $sel; ?>><?= h($f['titre'] ?? ''); ?></option>
                <?php endforeach; ?>
              </select>
              <div class=”hint”>Choisissez une formation si vous l'avez déjà identifiée (sinon laissez “Orientation libre”).</div>
              <?php endif; ?>
            </div>

            <div class="field">
              <label>Domaine d'intérêt</label>
              <input class="input" name="domaine_interet" placeholder="Ex : Finance, QHSE, Informatique, RH..."
                     value="<?= h($old['domaine_interet']); ?>">
              <div class="hint">Utile si vous laissez “Orientation libre”.</div>
            </div>

            <div class="field">
              <label>Nom <span class="req">*</span></label>
              <input class="input" name="nom" required value="<?= h($old['nom']); ?>" placeholder="Votre nom">
            </div>

            <div class="field">
              <label>Prénoms <span class="req">*</span></label>
              <input class="input" name="prenoms" required value="<?= h($old['prenoms']); ?>" placeholder="Vos prénoms">
            </div>

            <div class="field">
              <label>Email</label>
              <input class="input" type="email" name="email" value="<?= h($old['email']); ?>" placeholder="ex: vous@email.com">
              <div class="hint">Optionnel (mais recommandé pour un retour plus rapide).</div>
            </div>

            <div class="field">
              <label>Téléphone (WhatsApp) <span class="req">*</span></label>
              <input class="input" name="telephone" required value="<?= h($old['telephone']); ?>" placeholder="Ex : +225 07 00 00 00 00">
            </div>

            <div class="field">
              <label>Mode de formation</label>
              <select name="mode_formation">
                <option value="en_ligne"   <?= ($old['mode_formation']==='en_ligne')?'selected':''; ?>>En ligne</option>
                <option value="presentiel" <?= ($old['mode_formation']==='presentiel')?'selected':''; ?>>Présentiel</option>
                <option value="hybride"    <?= ($old['mode_formation']==='hybride')?'selected':''; ?>>Hybride</option>
              </select>
              <div class="hint">Par défaut : En ligne (si non choisi).</div>
            </div>

            <div class="field">
              <label>Format souhaité</label>
              <select name="format_formation">
                <option value="individuel"  <?= (($old['format_formation'] ?? '')==='individuel')?'selected':''; ?>>👤 Individuel — accompagnement dédié</option>
                <option value="groupe_devis" <?= (in_array($old['format_formation'] ?? '', ['groupe_3_5','groupe_6_10','groupe_10p','groupe_devis']))?'selected':''; ?>>👥 Formation groupe — sur devis</option>
              </select>
              <div class="hint">Formations groupe : tarif personnalisé sur devis — notre équipe vous rappelle sous 24h.</div>
            </div>

            <div class="field">
              <label>Date souhaitée de démarrage</label>
              <input class="input" type="date" name="date_debut_souhaitee"
                     value="<?= htmlspecialchars($old['date_debut_souhaitee'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                     min="<?= date('Y-m-d', strtotime('+3 days')) ?>">
              <div class="hint">Indicative — nous confirmons ensemble lors de l'entretien de cadrage.</div>
            </div>

            <div class="field">
              <label>Créneaux préférés (heure GMT)</label>
              <select name="creneau_prefere">
                <option value=""           <?= (($old['creneau_prefere'] ?? '')==='') ?'selected':''; ?>>— Aucune préférence —</option>
                <option value="matin_gmt"  <?= (($old['creneau_prefere'] ?? '')==='matin_gmt')?'selected':''; ?>>🌅 Matin : 7h–10h GMT</option>
                <option value="journee_gmt"<?= (($old['creneau_prefere'] ?? '')==='journee_gmt')?'selected':''; ?>>☀️ Journée : 10h–15h GMT</option>
                <option value="aprem_gmt"  <?= (($old['creneau_prefere'] ?? '')==='aprem_gmt')?'selected':''; ?>>🌤️ Après-midi : 15h–18h GMT</option>
                <option value="soir_gmt"   <?= (($old['creneau_prefere'] ?? '')==='soir_gmt')?'selected':''; ?>>🌙 Soir : 18h–21h GMT</option>
                <option value="week_end"   <?= (($old['creneau_prefere'] ?? '')==='week_end')?'selected':''; ?>>📅 Week-end uniquement</option>
              </select>
              <div class="hint">Tous les créneaux sont en heure GMT. Ex : 10h GMT = 10h en Côte d'Ivoire.</div>
            </div>

            <div class="field">
              <label>Statut professionnel</label>
              <select name="statut_professionnel">
                <option value="demandeur_emploi" <?= ($old['statut_professionnel']==='demandeur_emploi')?'selected':''; ?>>Demandeur d'emploi</option>
                <option value="etudiant"         <?= ($old['statut_professionnel']==='etudiant')?'selected':''; ?>>Étudiant</option>
                <option value="salarie"          <?= ($old['statut_professionnel']==='salarie')?'selected':''; ?>>Salarié</option>
                <option value="entrepreneur"     <?= ($old['statut_professionnel']==='entrepreneur')?'selected':''; ?>>Entrepreneur</option>
                <option value="fonctionnaire"    <?= ($old['statut_professionnel']==='fonctionnaire')?'selected':''; ?>>Fonctionnaire</option>
                <option value="autre"            <?= ($old['statut_professionnel']==='autre')?'selected':''; ?>>Autre</option>
              </select>
              <div class="hint">Par défaut : Demandeur d'emploi (si non choisi).</div>
            </div>

            <div class="field">
              <label>Objectif</label>
              <select name="objectif">
                <option value="monter_competence" <?= ($old['objectif']==='monter_competence')?'selected':''; ?>>Monter en compétence</option>
                <option value="changer_metier"    <?= ($old['objectif']==='changer_metier')?'selected':''; ?>>Changer de métier</option>
                <option value="promotion"         <?= ($old['objectif']==='promotion')?'selected':''; ?>>Promotion professionnelle</option>
                <option value="lancer_activite"   <?= ($old['objectif']==='lancer_activite')?'selected':''; ?>>Lancer une activité</option>
                <option value="certification"     <?= ($old['objectif']==='certification')?'selected':''; ?>>Certification</option>
                <option value="autre"             <?= ($old['objectif']==='autre')?'selected':''; ?>>Autre</option>
              </select>
              <div class="hint">Par défaut : Monter en compétence (si non choisi).</div>
            </div>

            <div class="field">
              <label>Disponibilité</label>
              <select name="disponibilite">
                <option value=""        <?= ($old['disponibilite']==='')?'selected':''; ?>>— Facultatif —</option>
                <option value="journee" <?= ($old['disponibilite']==='journee')?'selected':''; ?>>Journée</option>
                <option value="soir"    <?= ($old['disponibilite']==='soir')?'selected':''; ?>>Soir</option>
                <option value="weekend" <?= ($old['disponibilite']==='weekend')?'selected':''; ?>>Week-end</option>
              </select>
            </div>

            <div class="field">
              <label>Ville</label>
              <input class="input" name="ville" value="<?= h($old['ville']); ?>" placeholder="Ex : Abidjan">
            </div>

            <div class="field">
              <label>Pays</label>
              <input class="input" name="pays" value="<?= h($old['pays']); ?>" placeholder="Côte d'Ivoire">
            </div>

            <div class="field">
              <label>Domaine d'activité</label>
              <input class="input" name="domaine_activite" value="<?= h($old['domaine_activite']); ?>" placeholder="Ex : Finance, BTP, Santé, Commerce…">
              <div class="hint">Votre secteur d'activité actuel.</div>
            </div>

            <div class="field">
              <label>Niveau d'étude</label>
              <select name="niveau_etude">
                <option value="">— Choisir —</option>
                <?php foreach (['Aucun / Primaire','BEPC','Bac','Bac+2','Bac+3','Bac+4','Bac+5','Doctorat'] as $lvl): ?>
                  <option value="<?= h($lvl); ?>" <?= ($old['niveau_etude']===$lvl)?'selected':''; ?>><?= h($lvl); ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="field">
              <label>Fonction / Poste actuel</label>
              <input class="input" name="fonction" value="<?= h($old['fonction']); ?>" placeholder="Ex : Comptable, Étudiant, Gérant…">
            </div>

            <div class="field">
              <label>Années d'expérience</label>
              <select name="annees_experience">
                <option value="">— Choisir —</option>
                <?php foreach (['Aucune',"Moins d'1 an",'1 à 3 ans','3 à 5 ans','5 à 10 ans','Plus de 10 ans'] as $exp): ?>
                  <option value="<?= h($exp); ?>" <?= ($old['annees_experience']===$exp)?'selected':''; ?>><?= h($exp); ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="field">
              <label>CV (optionnel)</label>
              <input class="input file" type="file" name="cv" accept=".pdf,.doc,.docx">
              <div class="hint">Formats : PDF/DOC/DOCX — max 2 Mo.</div>
            </div>

            <div class="field">
              <label>Pièce d'identité (optionnel)</label>
              <input class="input file" type="file" name="cni" accept=".pdf,.jpg,.jpeg,.png">
              <div class="hint">Formats : PDF/JPG/PNG — max 3 Mo.</div>
            </div>

            <div class="field grid-full">
              <label>Message (optionnel)</label>
              <textarea name="message" placeholder="Expliquez brièvement votre besoin…"><?= h($old['message']); ?></textarea>
              <div class="hint">Plus votre besoin est clair, plus notre orientation sera rapide.</div>
            </div>

            <div class="field grid-full">
              <button class="btn" type="submit">Envoyer ma préinscription</button>
            </div>

          </div>
        </form>

      <?php endif; ?>

    </div>

    <div class="card-foot">
      <span class="pill">IBIG EDUFORM</span>
      <span>Vos données sont utilisées uniquement pour l'orientation et le suivi de votre demande.</span>
    </div>

  </section>

</main>

<?php require __DIR__ . '/partials/footer.php'; ?>