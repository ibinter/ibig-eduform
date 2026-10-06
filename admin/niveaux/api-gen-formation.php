<?php
declare(strict_types=1);
/**
 * ADMIN — Génère objectifs/prérequis/public_cible pour TOUS les niveaux d'une formation
 * en UN SEUL appel API (3x moins cher que api-gen-content.php appelé 3 fois).
 * POST : formation_id (int), csrf
 * Retourne JSON {ok, updated:[{niveau_id, niveau},...]}
 */
require_once __DIR__ . '/../_init.php';
Middleware::requireAuth();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); echo json_encode(['error' => 'POST requis']); exit;
}
csrf_check();

$formation_id = (int)($_POST['formation_id'] ?? 0);
if ($formation_id <= 0) {
    http_response_code(400); echo json_encode(['error' => 'formation_id invalide']); exit;
}

$pdo = Database::connect();

/* Formation */
$fRow = $pdo->prepare("SELECT titre, domaine, description FROM formations WHERE id = ? LIMIT 1");
$fRow->execute([$formation_id]);
$f = $fRow->fetch(PDO::FETCH_ASSOC);
if (!$f) { http_response_code(404); echo json_encode(['error' => 'Formation introuvable']); exit; }

/* Niveaux actifs sans contenu */
$nStmt = $pdo->prepare("
    SELECT id, niveau, duree_heures
    FROM formation_niveaux
    WHERE formation_id = ? AND statut = 'actif'
      AND (objectifs IS NULL OR objectifs = '')
    ORDER BY CASE niveau WHEN 'debutant' THEN 1 WHEN 'intermediaire' THEN 2 WHEN 'expert' THEN 3 END
");
$nStmt->execute([$formation_id]);
$niveaux = $nStmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($niveaux)) {
    echo json_encode(['ok' => true, 'updated' => [], 'skipped' => 'Déjà renseigné']); exit;
}

$titre  = $f['titre'];
$domain = (string)($f['domaine'] ?? '');
$desc   = mb_substr((string)($f['description'] ?? ''), 0, 200);

$niv_labels = ['debutant'=>'Débutant','intermediaire'=>'Intermédiaire','expert'=>'Expert'];
$niv_hints  = [
    'debutant'      => 'aucun prérequis, vocabulaire expliqué, objectifs d\'initiation',
    'intermediaire' => 'bases acquises, cas pratiques, objectifs d\'autonomie',
    'expert'        => 'maîtrise confirmée, cas complexes, objectifs de leadership',
];

/* Construire la demande pour chaque niveau présent */
$niv_list = implode(', ', array_map(fn($n) => $niv_labels[$n['niveau']] ?? $n['niveau'], $niveaux));
$niv_keys = implode(', ', array_map(fn($n) => '"'.$n['niveau'].'"', $niveaux));

$niv_detail = '';
foreach ($niveaux as $n) {
    $lbl  = $niv_labels[$n['niveau']] ?? $n['niveau'];
    $hint = $niv_hints[$n['niveau']] ?? '';
    $niv_detail .= "- {$lbl} ({$n['duree_heures']}h) : {$hint}\n";
}

$prompt = "Formation : {$titre}\nDomaine : {$domain}\nDescription : {$desc}\n\n"
    . "Génère objectifs, prérequis et public_cible pour chacun de ces niveaux :\n{$niv_detail}\n"
    . "Réponds en JSON strict avec les clés {$niv_keys}, chacune contenant exactement : objectifs, prerequis, public_cible.\n"
    . "Chaque champ = 1-2 phrases courtes adaptées au niveau. JSON pur sans markdown.";

require_once __DIR__ . '/../../core/config.php';
$apiKey = defined('ANTHROPIC_API_KEY') ? ANTHROPIC_API_KEY : (getenv('ANTHROPIC_API_KEY') ?: '');
if (!$apiKey) {
    http_response_code(500); echo json_encode(['error' => 'Clé API non configurée']); exit;
}

$payload = json_encode([
    'model'       => 'claude-haiku-4-5-20251001',
    'max_tokens'  => 600,
    'system'      => 'Ingénieur pédagogique. Réponds TOUJOURS en JSON pur, sans markdown.',
    'messages'    => [['role' => 'user', 'content' => $prompt]],
    'temperature' => 0.3,
]);

$ch = curl_init('https://api.anthropic.com/v1/messages');
$curl_opts = [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_TIMEOUT        => 90,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'x-api-key: ' . $apiKey,
        'anthropic-version: 2023-06-01',
    ],
];
if (file_exists('/root/.ccr/ca-bundle.crt')) {
    $curl_opts[CURLOPT_CAINFO] = '/root/.ccr/ca-bundle.crt';
}
curl_setopt_array($ch, $curl_opts);
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

if (!is_array($data)) {
    http_response_code(500);
    echo json_encode(['error' => 'Réponse IA invalide', 'raw' => mb_substr($text, 0, 400)]);
    exit;
}

$upd = $pdo->prepare("
    UPDATE formation_niveaux SET objectifs=:obj, prerequis=:pre, public_cible=:pub, updated_at=NOW()
    WHERE id=:id
");

$updated = [];
foreach ($niveaux as $n) {
    $niv = $n['niveau'];
    if (empty($data[$niv])) continue;
    $obj = mb_substr(trim((string)($data[$niv]['objectifs']    ?? '')), 0, 1000);
    $pre = mb_substr(trim((string)($data[$niv]['prerequis']    ?? '')), 0, 500);
    $pub = mb_substr(trim((string)($data[$niv]['public_cible'] ?? '')), 0, 500);
    if (!$obj) continue;
    $upd->execute([':obj'=>$obj, ':pre'=>$pre, ':pub'=>$pub, ':id'=>$n['id']]);
    $updated[] = ['niveau_id' => $n['id'], 'niveau' => $niv];
}

echo json_encode(['ok' => true, 'updated' => $updated, 'formation_id' => $formation_id]);
