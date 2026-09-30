<?php
declare(strict_types=1);
/**
 * API EDUFORM — Inscription depuis IBIG PARTNERS
 * POST /api/register-from-partners.php
 *
 * En-tête obligatoire : x-partners-api-key: <PARTNERS_REGISTER_SECRET>
 * Body JSON :
 *   formation_slug  string  ex: "eduform-cybersecurite-reseaux"
 *   customer_name   string
 *   customer_email  string
 *   customer_phone  string (optionnel)
 *   amount          int     montant payé en FCFA
 *   reference       string  référence vente Partners (ex: VTE-0042)
 *   partner_code    string  code affilié (ex: AFF-KOUAKOU-006)
 */

require_once __DIR__ . '/../core/bootstrap.php';

/* ── 0. Méthode ──────────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

/* ── 1. Authentification ─────────────────────────────────────────── */
$expectedKey = defined('PARTNERS_REGISTER_SECRET') ? PARTNERS_REGISTER_SECRET : '';
if ($expectedKey === '') {
    // Si la constante n'est pas définie, refuser
    http_response_code(500);
    echo json_encode(['error' => 'Server misconfigured']);
    exit;
}
$providedKey = $_SERVER['HTTP_X_PARTNERS_API_KEY'] ?? '';
if (!hash_equals($expectedKey, $providedKey)) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

/* ── 2. Lecture JSON ─────────────────────────────────────────────── */
$raw = (string)file_get_contents('php://input');
$body = json_decode($raw, true);
if (!is_array($body)) {
    http_response_code(400);
    echo json_encode(['error' => 'JSON invalide']);
    exit;
}

$formationSlug      = trim((string)($body['formation_slug']      ?? ''));
$customerName       = trim((string)($body['customer_name']       ?? ''));
$customerEmail      = trim((string)($body['customer_email']      ?? ''));
$customerPhone      = trim((string)($body['customer_phone']      ?? ''));
$amount             = (int)($body['amount']                      ?? 0);
$reference          = trim((string)($body['reference']           ?? ''));
$partnerCode        = trim((string)($body['partner_code']        ?? ''));
$modeFormation      = trim((string)($body['mode_formation']      ?? ''));
$statutPro          = trim((string)($body['statut_professionnel']?? ''));
$objectif           = trim((string)($body['objectif']            ?? ''));
$disponibilite      = trim((string)($body['disponibilite']       ?? ''));
$ville              = trim((string)($body['ville']               ?? ''));
$pays               = trim((string)($body['pays']                ?? ''));
$domaineActivite    = trim((string)($body['domaine_activite']    ?? ''));
$niveauEtude        = trim((string)($body['niveau_etude']        ?? ''));
$fonction           = trim((string)($body['fonction']            ?? ''));
$anneesExperience   = trim((string)($body['annees_experience']   ?? ''));
$message            = trim((string)($body['message']             ?? ''));

if ($formationSlug === '' || $customerName === '' || $customerEmail === '') {
    http_response_code(422);
    echo json_encode(['error' => 'Champs obligatoires manquants (formation_slug, customer_name, customer_email)']);
    exit;
}

/* ── 3. Trouver la formation en base ─────────────────────────────── */
$pdo = Database::connect();

// Le slug Partners inclut "eduform-" comme préfixe = slug en base EDUFORM
$stmt = $pdo->prepare("SELECT id, titre FROM formations WHERE slug = ? AND statut = 'active' LIMIT 1");
$stmt->execute([$formationSlug]);
$formation = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

if (!$formation) {
    // Chercher sans le préfixe "eduform-" au cas où
    $slugSansPrefixe = preg_replace('/^eduform-/', '', $formationSlug);
    $stmt2 = $pdo->prepare("SELECT id, titre FROM formations WHERE slug = ? AND statut = 'active' LIMIT 1");
    $stmt2->execute([$slugSansPrefixe]);
    $formation = $stmt2->fetch(PDO::FETCH_ASSOC) ?: null;
}

if (!$formation) {
    http_response_code(404);
    echo json_encode(['error' => 'Formation introuvable', 'slug' => $formationSlug]);
    exit;
}

$formationId    = (int)$formation['id'];
$formationTitre = (string)$formation['titre'];

/* ── 4. Idempotence — vérifier si déjà inscrit via cette référence ─ */
try {
    $stmtCheck = $pdo->prepare("SELECT id FROM paiements_inscription WHERE partners_reference = ? LIMIT 1");
    $stmtCheck->execute([$reference]);
    $existing = $stmtCheck->fetch(PDO::FETCH_ASSOC);
    if ($existing) {
        http_response_code(200);
        echo json_encode(['ok' => true, 'message' => 'Déjà enregistré', 'id' => $existing['id']]);
        exit;
    }
} catch (Throwable $e) {
    // La colonne partners_reference n'existe peut-être pas encore — on continue
}

/* ── 5. Insertion inscription ─────────────────────────────────────── */
$refInscription = strtoupper('PART-' . $formationId . '-' . bin2hex(random_bytes(4)));

try {
    $pdo->prepare("
        INSERT INTO paiements_inscription
            (formation_id, montant, reference, statut, provider, customer_name, customer_email,
             customer_phone, ibig_ref, partners_reference,
             mode_formation, statut_professionnel, objectif,
             disponibilite, ville, pays, domaine_activite, niveau_etude, fonction, annees_experience, message,
             paid_at)
        VALUES (?, ?, ?, 'paye', 'partners', ?, ?, ?, ?, ?,
                ?, ?, ?,
                ?, ?, ?, ?, ?, ?, ?, ?,
                NOW())
    ")->execute([
        $formationId, $amount, $refInscription, $customerName,
        $customerEmail ?: null, $customerPhone ?: null,
        $partnerCode ?: null, $reference ?: null,
        $modeFormation ?: null, $statutPro ?: null, $objectif ?: null,
        $disponibilite ?: null, $ville ?: null, $pays ?: null,
        $domaineActivite ?: null, $niveauEtude ?: null, $fonction ?: null,
        $anneesExperience ?: null, $message ?: null,
    ]);
} catch (Throwable $e) {
    // Fallback sans les colonnes optionnelles si migration pas encore exécutée
    try {
        $pdo->prepare("
            INSERT INTO paiements_inscription
                (formation_id, montant, reference, statut, provider, customer_name, customer_email, customer_phone)
            VALUES (?, ?, ?, 'paye', 'partners', ?, ?, ?)
        ")->execute([
            $formationId, $amount, $refInscription,
            $customerName, $customerEmail ?: null, $customerPhone ?: null,
        ]);
    } catch (Throwable $e2) {
        error_log('[register-from-partners] DB error: ' . $e2->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Erreur base de données', 'detail' => $e2->getMessage()]);
        exit;
    }
}

/* ── 6. Email de confirmation au client ──────────────────────────── */
if ($customerEmail !== '') {
    try {
        $sujet  = "✅ Inscription confirmée — {$formationTitre}";
        $prenomNom = htmlspecialchars($customerName);
        $titre  = htmlspecialchars($formationTitre);
        $montantFormate = number_format($amount, 0, ',', ' ') . ' FCFA';
        $corps = "
<p>Bonjour <strong>{$prenomNom}</strong>,</p>
<p>Votre inscription à la formation <strong>{$titre}</strong> est confirmée.</p>
<p><strong>Montant payé :</strong> {$montantFormate}<br>
<strong>Référence :</strong> {$refInscription}</p>
<p>Notre équipe vous contactera sous 24h pour vous communiquer les détails pratiques (date, modalités, accès).</p>
<p>Merci de votre confiance.<br>L'équipe IBIG EDUFORM</p>
        ";
        send_mail($customerEmail, $sujet, $corps);
    } catch (Throwable $e) {
        error_log('[register-from-partners] Email error: ' . $e->getMessage());
        // Non bloquant
    }
}

http_response_code(201);
header('Content-Type: application/json');
echo json_encode([
    'ok'         => true,
    'reference'  => $refInscription,
    'formation'  => $formationTitre,
    'formation_id' => $formationId,
    'message'    => 'Inscription enregistrée et email envoyé',
]);
