<?php
declare(strict_types=1);

/**
 * ============================================================================
 * IBIG EDUFORM — PREINSCRIPTION SERVICE (CENTRAL & UNIQUE) — PROD STABLE
 * ----------------------------------------------------------------------------
 * ✔ Gère : specifique / generale / samedi_pro / sur_mesure
 * ✔ PHP 7.4+
 * ✔ FK formation_id respectée (NULL safe)
 * ✔ ENUM sécurisés
 * ✔ session_key stable
 * ✔ Logs clairs en cas d’erreur
 * ============================================================================
 */

if (!function_exists('enregistrer_preinscription')) {

  function enregistrer_preinscription(PDO $pdo, array $data): int
  {
    /* =====================================================
       SESSION
    ===================================================== */
    if (session_status() !== PHP_SESSION_ACTIVE) {
      session_start();
    }

    if (empty($_SESSION['session_key'])) {
      $_SESSION['session_key'] = bin2hex(random_bytes(16));
    }
    $sessionKey = (string)$_SESSION['session_key'];

    /* =====================================================
       NORMALISATION
    ===================================================== */
    $norm = static function ($v) {
      if (is_string($v)) {
        $v = trim($v);
      }
      return ($v === '' || $v === false) ? null : $v;
    };

    /* =====================================================
       DONNÉES OBLIGATOIRES
    ===================================================== */
    $type      = trim((string)($data['type_preinscription'] ?? ''));
    $nom       = trim((string)($data['nom'] ?? ''));
    $prenoms   = trim((string)($data['prenoms'] ?? ''));
    $telephone = trim((string)($data['telephone'] ?? ''));

    if ($type === '' || $nom === '' || $prenoms === '' || $telephone === '') {
      throw new InvalidArgumentException('Données de préinscription incomplètes.');
    }

    /* =====================================================
       ENUMS AUTORISÉS
    ===================================================== */
    $allowedType = ['specifique','generale','samedi_pro','sur_mesure'];
    if (!in_array($type, $allowedType, true)) {
      throw new InvalidArgumentException('Type de préinscription invalide.');
    }

    $allowedMode = ['en_ligne','presentiel','hybride'];
    $mode = (string)($data['mode_formation'] ?? '');
    if (!in_array($mode, $allowedMode, true)) {
      $mode = 'en_ligne';
    }

    $allowedStatutPro = ['etudiant','salarie','entrepreneur','demandeur_emploi','fonctionnaire','autre'];
    $statutPro = (string)($data['statut_professionnel'] ?? '');
    if (!in_array($statutPro, $allowedStatutPro, true)) {
      $statutPro = 'demandeur_emploi';
    }

    $allowedObjectif = ['monter_competence','changer_metier','promotion','lancer_activite','certification','autre'];
    $objectif = (string)($data['objectif'] ?? '');
    if (!in_array($objectif, $allowedObjectif, true)) {
      $objectif = 'monter_competence';
    }

    $allowedNiveau = ['debutant','intermediaire','avance'];
    $niveau = (string)($data['niveau'] ?? '');
    if (!in_array($niveau, $allowedNiveau, true)) {
      $niveau = 'debutant';
    }

    $allowedStatut = ['nouvelle','traitee','rejete'];
    $statut = (string)($data['statut'] ?? 'nouvelle');
    if (!in_array($statut, $allowedStatut, true)) {
      $statut = 'nouvelle';
    }

    /* =====================================================
       FORMATION ID — FK SAFE
    ===================================================== */
    $formationId = $data['formation_id'] ?? null;
    if (!is_numeric($formationId) || (int)$formationId <= 0) {
      $formationId = null;
    }

    /* =====================================================
       AUTRES DONNÉES
    ===================================================== */
    $domaineInteret = $norm($data['domaine_interet'] ?? null);
    $email          = $norm($data['email'] ?? null);
    $ville          = $norm($data['ville'] ?? null);
    $pays           = $norm($data['pays'] ?? 'CI');
    $source         = $norm($data['source'] ?? 'site_web');
    $message        = $norm($data['message'] ?? null);
    $cvPath         = $norm($data['cv_path'] ?? null);
    $cniPath        = $norm($data['cni_path'] ?? null);

    $utmSource   = $norm($data['utm_source'] ?? ($_SESSION['utm_source'] ?? null));
    $utmCampaign = $norm($data['utm_campaign'] ?? ($_SESSION['utm_campaign'] ?? null));

    $ip = $_SERVER['REMOTE_ADDR'] ?? null;

    /* =====================================================
       INSERT SQL FINAL
    ===================================================== */
    $sql = "
      INSERT INTO preinscriptions (
        session_key,
        formation_id,
        type_preinscription,
        domaine_interet,
        nom,
        prenoms,
        email,
        telephone,
        mode_formation,
        statut_professionnel,
        niveau,
        objectif,
        ville,
        pays,
        source,
        utm_source,
        utm_campaign,
        statut,
        message,
        cv_path,
        cni_path,
        ip_address,
        created_at,
        updated_at
      ) VALUES (
        :session_key,
        :formation_id,
        :type_preinscription,
        :domaine_interet,
        :nom,
        :prenoms,
        :email,
        :telephone,
        :mode_formation,
        :statut_professionnel,
        :niveau,
        :objectif,
        :ville,
        :pays,
        :source,
        :utm_source,
        :utm_campaign,
        :statut,
        :message,
        :cv_path,
        :cni_path,
        :ip_address,
        NOW(),
        NOW()
      )
    ";

    try {
      $stmt = $pdo->prepare($sql);
      $stmt->execute([
        ':session_key'          => $sessionKey,
        ':formation_id'         => $formationId,
        ':type_preinscription'  => $type,
        ':domaine_interet'      => $domaineInteret,
        ':nom'                  => $nom,
        ':prenoms'              => $prenoms,
        ':email'                => $email,
        ':telephone'            => $telephone,
        ':mode_formation'       => $mode,
        ':statut_professionnel' => $statutPro,
        ':niveau'               => $niveau,
        ':objectif'             => $objectif,
        ':ville'                => $ville,
        ':pays'                 => $pays,
        ':source'               => $source,
        ':utm_source'           => $utmSource,
        ':utm_campaign'         => $utmCampaign,
        ':statut'               => $statut,
        ':message'              => $message,
        ':cv_path'              => $cvPath,
        ':cni_path'             => $cniPath,
        ':ip_address'           => $ip,
      ]);

      return (int)$pdo->lastInsertId();

    } catch (Throwable $e) {
      error_log('[PREINSCRIPTION_SERVICE] ' . $e->getMessage());
      throw $e;
    }
  }
}