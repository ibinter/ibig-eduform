<?php
declare(strict_types=1);
/**
 * ADMIN — API : génère et sauvegarde les modules d'un niveau via Claude AI
 * POST : niveau_id (int), csrf
 * Retourne JSON {ok, nb_modules, modules[]}
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

/* ── Charger le niveau + formation ── */
$row = $pdo->prepare("
    SELECT n.id, n.niveau, n.duree_heures, n.objectifs, n.prerequis, n.public_cible,
           f.titre, f.domaine, f.description
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

/* ── Clé API Anthropic ── */
require_once __DIR__ . '/../../core/config.php';
$apiKey = defined('ANTHROPIC_API_KEY') ? ANTHROPIC_API_KEY : (getenv('ANTHROPIC_API_KEY') ?: '');
if (!$apiKey) {
    http_response_code(500); echo json_encode(['error' => 'Clé API non configurée']); exit;
}

/* ── Données ── */
$titre   = $niv['titre'];
$niveau  = $niv['niveau'];  // debutant | intermediaire | expert
$duree   = (int)$niv['duree_heures'];
$domaine = $niv['domaine'] ?? '';
$desc    = mb_substr((string)($niv['description'] ?? ''), 0, 400);

$niv_labels = [
    'debutant'      => 'Débutant',
    'intermediaire' => 'Intermédiaire',
    'expert'        => 'Expert',
];
$niv_label = $niv_labels[$niveau] ?? $niveau;

/* Nombre de modules selon durée */
$nb_mod = 6;
if ($duree >= 30) $nb_mod = 7;
if ($duree >= 40) $nb_mod = 8;

/* Orientation pédagogique par niveau */
$niv_hint = match($niveau) {
    'debutant' => "Niveau DÉBUTANT : partir de zéro. Commencer par les concepts fondamentaux, le vocabulaire de base, les outils essentiels. Progression lente, exemples simples, exercices guidés. PAS de contenu avancé.",
    'intermediaire' => "Niveau INTERMÉDIAIRE : les bases sont acquises. Approfondir les compétences, travailler sur des cas réels, maîtriser les outils professionnels courants, développer l'autonomie.",
    'expert' => "Niveau EXPERT : maîtrise complète attendue. Cas complexes, stratégie, leadership, audit, optimisation avancée, préparation à des responsabilités senior. Contenu exigeant et pointu.",
    default => ''
};

/* Détection domaine métier */
$hints = '';
if (preg_match('/comptab|syscohada|ifrs|bilan|fiscal|tva|paie|comptable|finance|trésorerie|budget/ui', $titre . $domaine)) {
    $hints = "Domaine : Comptabilité, Finance, Fiscalité (SYSCOHADA, OHADA, DGI, CNPS, TVA).";
} elseif (preg_match('/rh\b|ressources humaines|recrutement|grh|sirh|personnel|talent/ui', $titre . $domaine)) {
    $hints = "Domaine : Gestion des Ressources Humaines (Code du travail ivoirien, CNPS, CMU, GPEC).";
} elseif (preg_match('/marketing|réseaux sociaux|digital|whatsapp|facebook|seo|branding/ui', $titre . $domaine)) {
    $hints = "Domaine : Marketing Digital (WhatsApp Business, Meta Ads, Canva, PME africaines).";
} elseif (preg_match('/vente|closing|commercial|négociation|crm|sales/ui', $titre . $domaine)) {
    $hints = "Domaine : Commerce & Vente (techniques SPIN, BANT, CRM, contexte africain).";
} elseif (preg_match('/leadership|management|dirigeant|stratégie|entrepreneur|gouvernance/ui', $titre . $domaine)) {
    $hints = "Domaine : Leadership & Management (PME africaines, BSC, OKR, tableaux de bord).";
} elseif (preg_match('/data|power bi|excel|analytics|bi\b|intelligence artificielle|ia\b|automatisation/ui', $titre . $domaine)) {
    $hints = "Domaine : Data & IA (Power BI, Excel, transformation numérique en Afrique).";
} elseif (preg_match('/logistique|supply chain|achat|procurement|stock/ui', $titre . $domaine)) {
    $hints = "Domaine : Logistique & Supply Chain (corridors africains, ERP, incoterms).";
}

/* ── Prompt ── */
$prompt = <<<PROMPT
Génère exactement {$nb_mod} modules de formation PROFESSIONNELS et SPÉCIFIQUES pour :

**Formation** : {$titre}
**Niveau** : {$niv_label}
**Durée totale** : {$duree}h
**Domaine** : {$domaine}
{$hints}

**INSTRUCTIONS PÉDAGOGIQUES** :
{$niv_hint}

RÈGLES ABSOLUES :
- {$nb_mod} modules EXACTEMENT — ni plus ni moins
- La somme des durées doit ÉGALER {$duree}h (distribue les heures intelligemment)
- Chaque titre de module doit être PRÉCIS et MÉTIER (pas "Introduction", pas "Conclusion générale")
- Chaque "contenus" doit avoir 4 à 6 points clés séparés par " · " — SPÉCIFIQUES à ce domaine et ce niveau
- Progression logique : du plus fondamental au plus appliqué
- RÉPONDS UNIQUEMENT en JSON valide (sans markdown)

Format JSON attendu :
[
  {"titre": "Titre précis module 1", "contenus": "Point 1 · Point 2 · Point 3 · Point 4", "duree_heures": X},
  {"titre": "Titre précis module 2", "contenus": "Point 1 · Point 2 · Point 3 · Point 4", "duree_heures": X},
  ...
]
PROMPT;

/* ── Appel Claude API ── */
$payload = json_encode([
    'model'       => 'claude-haiku-4-5-20251001',
    'max_tokens'  => 2000,
    'system'      => "Tu es un ingénieur pédagogique senior spécialisé en formations professionnelles pour les PME africaines. Tu génères des modules de formation précis, progressifs et adaptés au niveau indiqué. Réponds TOUJOURS en JSON pur sans markdown.",
    'messages'    => [['role' => 'user', 'content' => $prompt]],
    'temperature' => 0.5,
]);

$ch = curl_init('https://api.anthropic.com/v1/messages');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_TIMEOUT        => 90,
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

if ($err) {
    http_response_code(502);
    echo json_encode(['error' => 'Réseau : ' . $err]);
    exit;
}

$resp = json_decode($raw, true);
if ($code !== 200 || empty($resp['content'][0]['text'])) {
    $msg = $resp['error']['message'] ?? ('API erreur ' . $code);
    http_response_code(502);
    echo json_encode(['error' => $msg]);
    exit;
}

$text = trim($resp['content'][0]['text']);
$text = preg_replace('/^```(?:json)?\s*/i', '', $text);
$text = preg_replace('/\s*```\s*$/m', '', $text);

$modules = json_decode($text, true);
if (!is_array($modules) || empty($modules)) {
    http_response_code(500);
    echo json_encode(['error' => 'Réponse IA invalide.', 'raw' => mb_substr($text, 0, 500)]);
    exit;
}

/* ── Sauvegarder en base ── */
try {
    $pdo->beginTransaction();
    // Supprimer anciens modules
    $pdo->prepare("DELETE FROM formation_niveau_modules WHERE niveau_id = :nid")->execute([':nid' => $niveau_id]);
    // Insérer nouveaux
    $ins = $pdo->prepare("INSERT INTO formation_niveau_modules (niveau_id, ordre, titre, contenus, duree_heures) VALUES (:nid, :ord, :t, :c, :d)");
    foreach ($modules as $i => $m) {
        $ins->execute([
            ':nid' => $niveau_id,
            ':ord' => $i + 1,
            ':t'   => mb_substr((string)($m['titre'] ?? ''), 0, 255),
            ':c'   => mb_substr((string)($m['contenus'] ?? ''), 0, 1000),
            ':d'   => max(1, min(40, (int)($m['duree_heures'] ?? 2))),
        ]);
    }
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Erreur DB : ' . $e->getMessage()]);
    exit;
}

echo json_encode(['ok' => true, 'nb_modules' => count($modules), 'modules' => $modules]);
