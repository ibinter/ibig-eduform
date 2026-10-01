<?php
declare(strict_types=1);
/*
 * AJAX handler — popup exit-intent préinscription rapide
 * POST : csrf, nom, email, telephone?, formation_label, formation_autre?
 * Répond JSON : {"ok":true} | {"ok":false,"message":"..."}
 */

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

require_once __DIR__ . '/core/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

/* ---- CSRF ---- */
if (!isset($_POST['csrf']) || !hash_equals((string)csrf_token(), (string)$_POST['csrf'])) {
    echo json_encode(['ok' => false, 'message' => 'Token de sécurité invalide.']);
    exit;
}

/* ---- Validation ---- */
$nom    = trim((string)($_POST['nom']   ?? ''));
$email  = trim((string)($_POST['email'] ?? ''));
$tel    = trim((string)($_POST['telephone'] ?? ''));
$label  = trim((string)($_POST['formation_label'] ?? ''));
if (!$label) $label = trim((string)($_POST['formation_autre'] ?? ''));

if ($nom === '') {
    echo json_encode(['ok' => false, 'message' => 'Le nom est requis.']);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['ok' => false, 'message' => 'Email invalide.']);
    exit;
}
if ($label === '') {
    echo json_encode(['ok' => false, 'message' => 'Veuillez préciser la formation souhaitée.']);
    exit;
}

/* ---- Session key ---- */
if (empty($_SESSION['session_key'])) {
    $_SESSION['session_key'] = bin2hex(random_bytes(16));
}

/* ---- Insertion ---- */
try {
    $pdo = Database::connect();

    /* Chercher formation_id si on a un titre exact */
    $formationId = null;
    $stmt = $pdo->prepare("SELECT id FROM formations WHERE titre = ? LIMIT 1");
    $stmt->execute([$label]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) $formationId = (int)$row['id'];

    $ins = $pdo->prepare("
        INSERT INTO preinscriptions
          (session_key, formation_id, type_preinscription, nom, prenoms, email,
           telephone, domaine_interet, mode_formation, statut_professionnel,
           niveau, objectif, source, statut, message, ip_address, created_at, updated_at)
        VALUES
          (:sk, :fid, 'generale', :nom, '', :email,
           :tel, :label, 'en_ligne', 'demandeur_emploi',
           'debutant', 'monter_competence', 'popup_exit', 'nouvelle', :msg, :ip, NOW(), NOW())
    ");

    $ins->execute([
        ':sk'    => $_SESSION['session_key'],
        ':fid'   => $formationId,
        ':nom'   => $nom,
        ':email' => $email,
        ':tel'   => $tel ?: null,
        ':label' => $label,
        ':msg'   => 'Via popup exit-intent — Formation souhaitée : ' . $label,
        ':ip'    => $_SERVER['REMOTE_ADDR'] ?? null,
    ]);

    echo json_encode(['ok' => true]);

} catch (Exception $e) {
    error_log('[popup-preinscription] ' . $e->getMessage());
    echo json_encode(['ok' => false, 'message' => 'Erreur serveur. Réessayez dans un instant.']);
}
