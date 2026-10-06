<?php
declare(strict_types=1);
/**
 * ADMIN — API : génère et sauvegarde les modules d'un niveau
 * Stratégie : tdr_modules() en priorité (local, gratuit), fallback Claude AI si aucune correspondance.
 * POST : niveau_id (int), csrf
 * Retourne JSON {ok, nb_modules, modules[], source}
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

/* ── Données ── */
$titre   = $niv['titre'];
$niveau  = $niv['niveau'];
$duree   = max(1, (int)$niv['duree_heures']);
$domaine = (string)($niv['domaine'] ?? '');
$desc    = mb_substr((string)($niv['description'] ?? ''), 0, 400);

/* ── Stratégie 1 : tdr_modules() local (gratuit, instantané) ── */
require_once __DIR__ . '/../../core/tdr_generator.php';
$tdr_raw = tdr_modules($titre, $domaine, $duree, $desc, $niveau);

$source = 'tdr_local';
$modules = [];

if (!empty($tdr_raw)) {
    /* tdr_modules retourne duree (pas duree_heures) — normaliser */
    foreach ($tdr_raw as $m) {
        $modules[] = [
            'titre'       => (string)($m['titre'] ?? ''),
            'contenus'    => (string)($m['contenus'] ?? ''),
            'duree_heures'=> max(1, min(40, (int)($m['duree'] ?? $m['duree_heures'] ?? 2))),
        ];
    }
}

/* ── Stratégie 2 : fallback Claude AI si tdr_modules() n'a pas de correspondance ── */
if (empty($modules)) {
    $source = 'claude_ai';
    require_once __DIR__ . '/../../core/config.php';
    $apiKey = defined('ANTHROPIC_API_KEY') ? ANTHROPIC_API_KEY : (getenv('ANTHROPIC_API_KEY') ?: '');
    if (!$apiKey) {
        http_response_code(500); echo json_encode(['error' => 'Clé API non configurée et aucun module local disponible']); exit;
    }

    $niv_labels = ['debutant'=>'Débutant','intermediaire'=>'Intermédiaire','expert'=>'Expert'];
    $niv_label  = $niv_labels[$niveau] ?? $niveau;
    $nb_mod = 6;

    $niv_hint = match($niveau) {
        'debutant'      => "Niveau DÉBUTANT : partir de zéro, concepts fondamentaux, vocabulaire de base, exercices guidés. Les débutants ont besoin de plus de temps d'appropriation.",
        'intermediaire' => "Niveau INTERMÉDIAIRE : bases acquises, cas réels, outils professionnels, autonomie.",
        'expert'        => "Niveau EXPERT : maîtrise complète, cas complexes, stratégie, optimisation avancée. Les experts peuvent avoir plus ou moins d'heures selon la complexité du contenu avancé.",
        default => ''
    };

    $prompt = "Génère exactement {$nb_mod} modules de formation PROFESSIONNELS pour :\n"
        . "Formation : {$titre}\nNiveau : {$niv_label}\nDomaine : {$domaine}\n\n"
        . "{$niv_hint}\n\n"
        . "RÈGLES IMPORTANTES : {$nb_mod} modules exactement · "
        . "Assigne à chaque module ses heures SELON SON CONTENU RÉEL (complexité, pratique, exercices) — "
        . "pas de formule : la somme totale doit être cohérente avec l'ampleur du programme · "
        . "titres précis · contenus 4-6 points séparés par ' · ' · JSON pur sans markdown.\n\n"
        . '[{"titre":"...","contenus":"...","duree_heures":X},...]';

    $payload = json_encode([
        'model'      => 'claude-haiku-4-5-20251001',
        'max_tokens' => 2000,
        'system'     => "Tu es un ingénieur pédagogique senior pour PME africaines. Réponds TOUJOURS en JSON pur.",
        'messages'   => [['role' => 'user', 'content' => $prompt]],
        'temperature'=> 0.5,
    ]);

    $ch = curl_init('https://api.anthropic.com/v1/messages');
    $curl_opts_ai = [
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
        $curl_opts_ai[CURLOPT_CAINFO] = '/root/.ccr/ca-bundle.crt';
    }
    curl_setopt_array($ch, $curl_opts_ai);
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
    $ai_modules = json_decode($text, true);
    if (!is_array($ai_modules) || empty($ai_modules)) {
        http_response_code(500); echo json_encode(['error' => 'Réponse IA invalide.', 'raw' => mb_substr($text, 0, 500)]); exit;
    }
    foreach ($ai_modules as $m) {
        $modules[] = [
            'titre'       => mb_substr((string)($m['titre'] ?? ''), 0, 255),
            'contenus'    => mb_substr((string)($m['contenus'] ?? ''), 0, 1000),
            'duree_heures'=> max(1, min(40, (int)($m['duree_heures'] ?? 2))),
        ];
    }
}

if (empty($modules)) {
    http_response_code(500); echo json_encode(['error' => 'Aucun module généré.']); exit;
}

/* ── Sauvegarder en base ── */
try {
    $pdo->beginTransaction();
    $pdo->prepare("DELETE FROM formation_niveau_modules WHERE niveau_id = :nid")->execute([':nid' => $niveau_id]);
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
    // Synchroniser duree_heures du niveau = somme des heures de ses modules (contenu-driven)
    $pdo->prepare("
        UPDATE formation_niveaux
           SET duree_heures = (SELECT COALESCE(SUM(duree_heures), 20) FROM formation_niveau_modules WHERE niveau_id = :nid)
         WHERE id = :nid
    ")->execute([':nid' => $niveau_id]);
} catch (Throwable $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Erreur DB : ' . $e->getMessage()]);
    exit;
}

// Lire les heures réelles après synchronisation
$duree_reelle = (int)$pdo->query("SELECT duree_heures FROM formation_niveaux WHERE id = $niveau_id")->fetchColumn();
echo json_encode(['ok' => true, 'nb_modules' => count($modules), 'modules' => $modules, 'source' => $source, 'duree_heures' => $duree_reelle]);
