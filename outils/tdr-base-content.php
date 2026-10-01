<?php
declare(strict_types=1);
/**
 * IBIG EDUFORM — /outils/tdr-base-content.php
 * Retourne le contenu statique (sans IA) d'un TDR pour pré-remplir le générateur.
 * Utilise les templates de core/tdr_generator.php.
 */
require_once __DIR__ . '/../core/auth.php';
if (!auth_check()) { http_response_code(403); echo json_encode(['error' => 'Non autorisé.']); exit; }

header('Content-Type: application/json; charset=utf-8');

$slug = trim($_GET['slug'] ?? '');
if (!$slug) { echo json_encode(['ok'=>false,'error'=>'slug requis']); exit; }

require_once __DIR__ . '/../core/tdr_generator.php';
require_once __DIR__ . '/../core/secrets.php';

try {
    $pdo = new PDO(
        'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4',
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (\PDOException $e) {
    echo json_encode(['ok'=>false,'error'=>'DB error']); exit;
}

$stmt = $pdo->prepare("SELECT titre, domaine, description, tarif_en_ligne, tarif_presentiel, duree, slug
    FROM formations WHERE slug=? AND statut='active' LIMIT 1");
$stmt->execute([$slug]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) { echo json_encode(['ok'=>false,'error'=>'Formation introuvable']); exit; }

$domMap = [
    'Comptabilité & Finance'=>'Comptabilité & Finance',
    'Ressources Humaines'=>'GRH',
    'Assistanat'=>'Direction & Administration',
    'Management & Leadership'=>'Management & Leadership',
    'Marketing & Commercial'=>'Marketing & Commercial',
    'Logistique & Supply Chain'=>'Logistique & Supply Chain',
    'Informatique & Digital'=>'Informatique & Digital',
    'Droit des Affaires'=>'Droit des Affaires',
    'IA & Digitalisation'=>'IA & Digitalisation',
    'Digital & IA'=>'IA & Digitalisation',
    'Intelligence Artificielle'=>'IA & Digitalisation',
];
$nom      = (string)($row['titre'] ?? '');
$domaine  = (string)($row['domaine'] ?? '');
$cat      = $domMap[$domaine] ?? $domaine;
$desc     = (string)($row['description'] ?? '');
$prix_ol  = max(200000, (int)($row['tarif_en_ligne']   ?? 200000));
$prix_pr  = max(250000, (int)($row['tarif_presentiel'] ?? 250000));
$dureeDB  = (string)($row['duree'] ?? '');

/* Volume horaire */
if ($dureeDB !== '' && preg_match('/(\d+)/u', $dureeDB, $dm)) {
    $heures = (int)$dm[1];
} else {
    $heures = max(20, (int)(round($prix_ol / 11250 / 5) * 5));
}

/* Modules via tdr_modules() */
$modules_raw = tdr_modules($nom, $cat, $heures, $desc);
$modules = array_map(function($m, $i) {
    return [
        'num'      => 'M' . ($i + 1),
        'titre'    => $m['titre'],
        'contenus' => $m['contenus'],
        'duree'    => $m['duree'] . 'h',
    ];
}, $modules_raw, array_keys($modules_raw));

/* Template par catégorie */
$tpl  = tdr_templates();
$nomCourt = preg_replace('/\s*[\(\[].*?[\)\]]\s*/', '', $nom);
if (preg_match('/\bclaude\b/ui', $nom)) {
    $data = $tpl['Claude (Anthropic)'] ?? $tpl['_default'];
} else {
    $data = $tpl[$cat] ?? $tpl['_default'];
}

$contexte_html = str_replace('{NOM}', $nomCourt, $data['contexte'] ?? '');
/* Extraire le texte brut depuis le HTML de contexte */
$contexte_txt = trim(strip_tags(str_replace(['</p>','<br>','<br/>'], "\n", $contexte_html)));

$obj_gen = str_replace('{NOM}', $nomCourt, $data['objectif_general'] ?? '');
$obj_spec = $data['objectifs_specifiques'] ?? [];
$cible    = $data['public_cible'] ?? '';
$prereqs  = $data['prerequis']    ?? '';
$methodo  = $data['methodologie'] ?? [];

/* Livrables standards */
$livrables = [
    'Rapport de diagnostic des besoins établi avant le démarrage',
    'Support de formation complet et personnalisé au format numérique',
    'Boîte à outils numérique spécifique au domaine',
    'Enregistrements de l\'intégralité des séances',
    'Plan d\'action individuel formalisé et validé',
    'Certificat nominatif vérifiable en ligne',
    'Relevé d\'assiduité et rapport de fin de formation',
];

/* Résultats standards */
$resultats = [
    ['resultat'=>'Le bénéficiaire maîtrise les fondamentaux du domaine '.$cat,'indicateur'=>'Note ≥ 12/20 à l\'évaluation finale'],
    ['resultat'=>'Les acquis sont transposés sur le terrain professionnel réel','indicateur'=>'Résolution documentée d\'au moins 3 situations professionnelles'],
    ['resultat'=>'Le bénéficiaire dispose d\'outils opérationnels immédiatement utilisables','indicateur'=>'Boîte à outils numérique remise et mise en service durant le parcours'],
    ['resultat'=>'Un plan de progression personnel est formalisé','indicateur'=>'Plan d\'action individuel présenté en séance de clôture'],
];

echo json_encode([
    'ok'              => true,
    'contexte'        => $contexte_txt,
    'obj_general'     => $obj_gen,
    'obj_specifiques' => $obj_spec,
    'resultats'       => $resultats,
    'profil_concerne' => $cible,
    'prerequis_ped'   => $prereqs,
    'prerequis_tech'  => [
        'Un ordinateur équipé d\'une webcam et d\'un micro',
        'Une connexion internet d\'un débit minimal de 2 Mbps',
        'Une adresse e-mail valide pour l\'accès à l\'espace apprenant',
    ],
    'modules'         => $modules,
    'volume_total'    => $heures . ' heures',
    'approche'        => $methodo,
    'livrables'       => $livrables,
    'certification'   => 'Certificat de compétences IBIG EDUFORM — ' . $nom . '. Délivré aux participants justifiant d\'une assiduité ≥ 80 % et de la validation des évaluations. Reconnu dans les 17 pays membres de l\'OHADA.',
    'evaluation_types'=> [
        ['type'=>'Évaluation diagnostique','modalite'=>'Questionnaire de positionnement mesurant le niveau d\'entrée'],
        ['type'=>'Évaluation formative','modalite'=>'Travaux intersessions et mises en situation à chaque séance'],
        ['type'=>'Évaluation sommative','modalite'=>'Épreuve finale (QCM + étude de cas) et soutenance du plan d\'action'],
        ['type'=>'Évaluation de satisfaction','modalite'=>'Questionnaire à chaud à l\'issue de la dernière séance'],
    ],
    'valeur_ajoutee'  => [
        ['point'=>'Un parcours entièrement recentré sur vous','detail'=>'Le contenu est reconstruit à partir de votre diagnostic : vos défis, vos objectifs, votre contexte.'],
        ['point'=>'Un formateur pour vous seul','detail'=>'Aucun temps d\'attente, aucune question laissée de côté.'],
        ['point'=>'Une confidentialité totale','detail'=>'Vous pouvez exposer sans réserve des situations sensibles.'],
        ['point'=>'Un rythme qui s\'adapte à votre activité','detail'=>'Créneaux choisis avec vous, reports acceptés sous 24h.'],
        ['point'=>'Une certification reconnue OHADA','detail'=>'Certificat nominatif, numéroté et vérifiable en ligne par QR code.'],
    ],
    'obligations_ibig'         => "Fournir un formateur qualifié et dédié\nRemettre les supports pédagogiques complets\nAssurer le suivi et la certification\nGarantir la confidentialité absolue",
    'obligations_beneficiaire' => "Assurer sa présence aux créneaux convenus\nRéaliser les travaux intersessions\nPrévenir de tout report au moins 24h à l'avance\nRégler les frais selon les modalités convenues",
    'confidentialite'          => 'Ce document est confidentiel et destiné exclusivement à son destinataire. Toute reproduction sans autorisation écrite d\'IBIG EDUFORM est interdite.',
]);
