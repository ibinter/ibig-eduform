<?php
declare(strict_types=1);
/**
 * ADMIN — API : génère objectifs, prérequis et public cible par niveau
 * POST : niveau_id (int), csrf
 * Retourne JSON {ok, niveau_id, objectifs, prerequis, public_cible, source}
 */
require_once __DIR__ . '/../_init.php';
Middleware::requireAuth();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); echo json_encode(['error' => 'POST requis']); exit;
}
csrf_check();

$niveau_id = (int)($_POST['niveau_id'] ?? 0);
if ($niveau_id <= 0) {
    http_response_code(400); echo json_encode(['error' => 'niveau_id invalide']); exit;
}

$pdo = Database::connect();

$row = $pdo->prepare("
    SELECT n.id, n.niveau, n.duree_heures, f.titre, f.domaine, f.description, f.objectifs AS obj_global
    FROM formation_niveaux n
    JOIN formations f ON f.id = n.formation_id
    WHERE n.id = :id
    LIMIT 1
");
$row->execute([':id' => $niveau_id]);
$niv = $row->fetch(PDO::FETCH_ASSOC);

if (!$niv) {
    http_response_code(404); echo json_encode(['error' => 'Niveau introuvable']); exit;
}

$titre   = $niv['titre'];
$niveau  = $niv['niveau'];
$duree   = max(1, (int)$niv['duree_heures']);
$domaine = (string)($niv['domaine'] ?? '');
$desc    = mb_substr((string)($niv['description'] ?? ''), 0, 300);
$obj_gl  = mb_substr((string)($niv['obj_global'] ?? ''), 0, 300);

$niv_labels = ['debutant'=>'Débutant','intermediaire'=>'Intermédiaire','expert'=>'Expert'];
$niv_label  = $niv_labels[$niveau] ?? $niveau;

$niv_hint = match($niveau) {
    'debutant'      => "NIVEAU DÉBUTANT : aucun prérequis technique, vocabulaire expliqué, exercices guidés, objectifs d'initiation et de compréhension.",
    'intermediaire' => "NIVEAU INTERMÉDIAIRE : bases acquises, cas pratiques réels, outils professionnels, objectifs d'autonomie et d'application.",
    'expert'        => "NIVEAU EXPERT : maîtrise confirmée, cas complexes, stratégie avancée, objectifs de leadership et d'optimisation.",
    default => ''
};

require_once __DIR__ . '/../../core/config.php';
$apiKey = defined('ANTHROPIC_API_KEY') ? ANTHROPIC_API_KEY : (getenv('ANTHROPIC_API_KEY') ?: '');
if (!$apiKey) {
    http_response_code(500); echo json_encode(['error' => 'Clé API non configurée']); exit;
}

$prompt = "Génère le profil pédagogique pour cette formation professionnelle :\n"
    . "Formation : {$titre}\nNiveau : {$niv_label}\nDomaine : {$domaine}\nDurée : {$duree}h\n\n"
    . "{$niv_hint}\n\n"
    . "Réponds en JSON strict avec exactement ces 3 clés :\n"
    . "- objectifs : 2-3 phrases décrivant ce que l'apprenant saura faire après la formation (adapté au niveau)\n"
    . "- prerequis : liste courte des prérequis nécessaires pour ce niveau (\"Aucun prérequis\" pour débutant)\n"
    . "- public_cible : 1-2 phrases décrivant le profil idéal du participant pour ce niveau\n\n"
    . 'Exemple : {"objectifs":"...","prerequis":"...","public_cible":"..."}'
    . "\nJSON pur, sans markdown.";

$payload = json_encode([
    'model'       => 'claude-haiku-4-5-20251001',
    'max_tokens'  => 800,
    'system'      => "Tu es un ingénieur pédagogique senior spécialisé dans la formation professionnelle en Afrique francophone. Réponds TOUJOURS en JSON pur, adapté au niveau indiqué.",
    'messages'    => [['role' => 'user', 'content' => $prompt]],
    'temperature' => 0.4,
]);

$ch = curl_init('https://api.anthropic.com/v1/messages');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_TIMEOUT        => 60,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'x-api-key: ' . $apiKey,
        'anthropic-version: 2023-06-01',
    ],
    CURLOPT_CAINFO => '/root/.ccr/ca-bundle.crt',
]);
$raw  = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err  = curl_error($ch);
curl_close($ch);

if ($err) { http_response_code(502); echo json_encode(['error' => 'Réseau : ' . $err]); exit; }
$resp = json_decode($raw, true);
if ($code !== 200 || empty($resp['content'][0]['text'])) {
    $msg = $resp['error']['message'] ?? ('API erreur ' . $code);
    http_response_code(502); echo json_encode(['error' => $msg]); exit;
}

$text = preg_replace(['/^```(?:json)?\s*/i', '/\s*```\s*$/m'], '', trim($resp['content'][0]['text']));
$data = json_decode($text, true);

if (!is_array($data) || empty($data['objectifs'])) {
    http_response_code(500);
    echo json_encode(['error' => 'Réponse IA invalide.', 'raw' => mb_substr($text, 0, 500)]);
    exit;
}

$objectifs    = mb_substr(trim((string)($data['objectifs'] ?? '')), 0, 1000);
$prerequis    = mb_substr(trim((string)($data['prerequis'] ?? '')), 0, 500);
$public_cible = mb_substr(trim((string)($data['public_cible'] ?? '')), 0, 500);

$pdo->prepare("
    UPDATE formation_niveaux
       SET objectifs = :obj, prerequis = :pre, public_cible = :pub, updated_at = NOW()
     WHERE id = :id
")->execute([
    ':obj' => $objectifs,
    ':pre' => $prerequis,
    ':pub' => $public_cible,
    ':id'  => $niveau_id,
]);

echo json_encode([
    'ok'           => true,
    'niveau_id'    => $niveau_id,
    'objectifs'    => $objectifs,
    'prerequis'    => $prerequis,
    'public_cible' => $public_cible,
    'source'       => 'claude_ai',
]);
