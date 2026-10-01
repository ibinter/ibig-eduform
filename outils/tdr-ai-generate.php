<?php
declare(strict_types=1);
/**
 * IBIG EDUFORM — /outils/tdr-ai-generate.php
 * Génère un TDR complet (format PDF officiel 7 pages) via Claude Sonnet.
 * Retourne JSON avec toutes les sections structurées.
 */
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/auth.php';
if (!auth_check()) { http_response_code(403); echo json_encode(['error' => 'Non autorisé.']); exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
header('Content-Type: application/json; charset=utf-8');

$apiKey = defined('ANTHROPIC_API_KEY') ? ANTHROPIC_API_KEY : (getenv('ANTHROPIC_API_KEY') ?: '');
if (!$apiKey) { http_response_code(500); echo json_encode(['error' => 'Clé API non configurée.']); exit; }

/* ── Paramètres ── */
$titre  = trim(strip_tags($_POST['titre']        ?? ''));
$type   = trim(strip_tags($_POST['type']         ?? 'formation'));
$ctx    = trim(strip_tags($_POST['contexte']     ?? ''));
$cat    = trim(strip_tags($_POST['categorie']    ?? ''));
$duree  = trim(strip_tags($_POST['duree']        ?? ''));
$prix_ol= (int)($_POST['prix_en_ligne']          ?? 200000);
$prix_pr= (int)($_POST['prix_presentiel']        ?? 250000);

if (!$titre) { http_response_code(400); echo json_encode(['error' => 'Titre requis.']); exit; }
if ($prix_ol < 200000) $prix_ol = 200000;
if ($prix_pr < 250000) $prix_pr = 250000;

$fmt_ol  = number_format($prix_ol, 0, ',', ' ') . ' F CFA';
$fmt_pr  = number_format($prix_pr, 0, ',', ' ') . ' F CFA';
$fmt_hyb = number_format((int)round(($prix_ol + $prix_pr) / 2 / 5000) * 5000, 0, ',', ' ') . ' F CFA';
$duree_v = $duree ?: '25 heures / 5 jours';

/* ── Détection du domaine métier ── */
$hints = '';
if (preg_match('/comptab|syscohada|ifrs|bilan|fiscal|tva|paie|caissier|comptable|finance|trésorerie|budget|contrôle de gestion/ui', $titre)) {
    $hints = "Domaine : Comptabilité, Finance, Fiscalité. Vocabulaire SYSCOHADA, OHADA, DGI, CNPS, TVA, BIC, IS, états financiers. Exemples concrets d'entreprises ivoiriennes et UEMOA. Prérequis techniques : ordinateur + connexion internet + logiciel de comptabilité.";
} elseif (preg_match('/rh\b|ressources humaines|recrutement|paie|grh|sirh|personnel|talent/ui', $titre)) {
    $hints = "Domaine : Gestion des Ressources Humaines. Vocabulaire Code du travail ivoirien, CNPS, CMU, DISA, GPEC, convention collective. Cas pratiques : dossiers du personnel, bulletins de paie, procédures disciplinaires.";
} elseif (preg_match('/marketing|réseaux sociaux|digital|whatsapp|facebook|instagram|seo|branding|contenu/ui', $titre)) {
    $hints = "Domaine : Marketing Digital. Contexte africain : forte pénétration WhatsApp, Mobile Money, social commerce. Exemples de PME ivoiriennes et ouest-africaines. Outils : Canva, Meta Business, Google Analytics.";
} elseif (preg_match('/vente|closing|prospection|commercial|négociation|crm|sales/ui', $titre)) {
    $hints = "Domaine : Commerce & Vente. Contexte africain : cycles de vente longs, importance du relationnel, négociation en face à face. Techniques SPIN, BANT, méthode DISC. Outils CRM simples adaptés aux PME.";
} elseif (preg_match('/leadership|management|dirigeant|stratégie|entrepreneur|gouvernance/ui', $titre)) {
    $hints = "Domaine : Leadership & Management. PME africaines, gouvernance familiale, formalisation, croissance, gestion d'équipes multiculturelles. Outils BSC, OKR, SWOT, tableau de bord de pilotage.";
} elseif (preg_match('/data analyst\s*(rh|grh|rh\b|ressources humaines|hr\b)/ui', $titre)) {
    $hints = "Domaine : Data RH & People Analytics. Analyse des données RH : pyramide des âges, turnover, absentéisme, masse salariale, indicateurs CNPS/CMU, tableaux de bord RH sous Excel/Power BI. Contexte UEMOA/OHADA. Prérequis : Excel intermédiaire + accès SIRH.";
} elseif (preg_match('/data|power bi|excel|analytics|bi\b|intelligence artificielle|ia\b|chatgpt|automatisation/ui', $titre)) {
    $hints = "Domaine : Data, IA & Digitalisation. Transformation numérique en Afrique, Power BI, Excel avancé, automatisation des processus. Prérequis techniques : PC + Excel installé. Cas d'usage africains concrets.";
} elseif (preg_match('/e-commerce|dropshipping|import|export|sourcing|commerce international/ui', $titre)) {
    $hints = "Domaine : E-commerce & Commerce International. ZLECAF, Alibaba, Jumia, incoterms, douane UEMOA. Prérequis : smartphone + connexion internet. Cas pratiques : ouverture boutique en ligne, sourcing produits.";
} elseif (preg_match('/logistique|supply chain|achat|procurement|stock|entrepôt/ui', $titre)) {
    $hints = "Domaine : Logistique & Supply Chain. Corridors africains, ERP, WMS, incoterms, tableaux de bord logistique. Prérequis : notions de gestion de stock.";
} elseif (preg_match('/communication|prise de parole|art oratoire|pitch|storytelling/ui', $titre)) {
    $hints = "Domaine : Communication Professionnelle. Présentations à des investisseurs, négociations, management multiculturel en Afrique. Exercices de prise de parole filmés + feedback.";
}

/* ── System prompt — personnage ── */
$system = "Tu es Dr. Adjoua Konan, directrice pédagogique d'IBIG EDUFORM avec 18 ans d'expérience en ingénierie de formation professionnelle en Afrique francophone. Tu as rédigé plus de 300 TDR (Termes de Référence) professionnels pour des entreprises, ONG et particuliers dans 17 pays OHADA.

Tu rédiges des TDR qui ressemblent à ceux rédigés par de vrais ingénieurs pédagogiques : langage professionnel précis, contenu spécifique au domaine, indicateurs de vérification concrets, livrables tangibles, formulations humaines et variées. JAMAIS de contenu générique ou interchangeable d'une formation à l'autre.";

/* ── Prompt ── */
$ctx_line = $ctx ? "\nDescription de la formation :\n«{$ctx}»\n" : '';
$prompt = <<<PROMPT
Rédige un TDR (Termes de Référence) COMPLET et PROFESSIONNEL pour :

**Formation** : {$titre}
**Catégorie** : {$cat}
**Durée** : {$duree_v}
**Tarif en ligne** : {$fmt_ol} | **Tarif présentiel** : {$fmt_pr} | **Hybride** : {$fmt_hyb}
{$ctx_line}

**Contexte métier à intégrer** : {$hints}

---

INSTRUCTIONS ABSOLUES :
- Chaque section doit être SPÉCIFIQUE à cette formation — rien de générique
- Les indicateurs de vérification doivent être MESURABLES (note, livrable, production, taux)
- Les modules doivent être PROGRESSIFS et COHÉRENTS (du fondamental vers l'appliqué)
- GÉNÈRE AU MINIMUM 6 MODULES et AU MAXIMUM 8 — adapte le nombre à la durée totale
- Chaque module doit avoir 4 à 6 points clés dans "contenus", séparés par " · "
- Les titres de module doivent être PRÉCIS et MÉTIER, pas génériques
- Le profil du formateur doit correspondre EXACTEMENT à ce domaine
- Les livrables doivent être TANGIBLES et NOMMÉS précisément
- La valeur ajoutée doit être DISTINCTIVE d'IBIG EDUFORM (pas de lieux communs)
- JAMAIS mentionner le FDFP
- JAMAIS de formulations bateau : "Dans le contexte actuel", "À l'ère du numérique"
- RÉPONDS AVEC LE JSON COMPLET JUSQU'AU DERNIER } — ne tronque JAMAIS la réponse

Réponds UNIQUEMENT en JSON valide (sans markdown), avec ces clés exactes :

{
  "contexte": "2-3 paragraphes percutants. Commence par un constat chiffré ou une problématique terrain réelle.",
  "obj_general": "Une phrase d'objectif avec verbe d'action fort et résultat mesurable.",
  "obj_specifiques": ["Objectif 1 avec verbe Bloom", "Objectif 2", "Objectif 3", "Objectif 4", "Objectif 5", "Objectif 6", "Objectif 7"],
  "resultats": [
    {"resultat": "Le bénéficiaire maîtrise [compétence spécifique]", "indicateur": "Note ≥ X/20 à l'évaluation finale sur [thème précis]"},
    {"resultat": "...", "indicateur": "..."},
    {"resultat": "...", "indicateur": "..."},
    {"resultat": "...", "indicateur": "..."}
  ],
  "profil_concerne": "Description précise du profil (fonctions, secteurs, niveau d'expérience).",
  "prerequis_ped": "Niveau académique et expérience professionnelle requis.",
  "prerequis_tech": ["Prérequis technique 1 (matériel)", "Prérequis technique 2 (logiciel)", "Prérequis technique 3 (connexion)"],
  "accessibilite": "Comment les modalités peuvent s'adapter aux personnes en situation de handicap.",
  "modules": [
    {"num": "M1", "titre": "Titre précis du module 1 — fondamentaux et positionnement", "contenus": "Point clé 1 spécifique · Point clé 2 · Point clé 3 · Point clé 4", "duree": "Xh"},
    {"num": "M2", "titre": "Titre précis du module 2", "contenus": "Point clé 1 · Point clé 2 · Point clé 3 · Point clé 4", "duree": "Xh"},
    {"num": "M3", "titre": "Titre précis du module 3", "contenus": "Point clé 1 · Point clé 2 · Point clé 3 · Point clé 4", "duree": "Xh"},
    {"num": "M4", "titre": "Titre précis du module 4 — approfondissement", "contenus": "Point clé 1 · Point clé 2 · Point clé 3 · Point clé 4", "duree": "Xh"},
    {"num": "M5", "titre": "Titre précis du module 5 — outils avancés et automatisation", "contenus": "Point clé 1 · Point clé 2 · Point clé 3 · Point clé 4", "duree": "Xh"},
    {"num": "M6", "titre": "Mise en pratique : cas d'entreprises et travaux dirigés", "contenus": "Dossiers réels d'entreprises africaines · Travaux individuels avec correction commentée · Projet de synthèse professionnel complet · Présentation orale des résultats", "duree": "Xh"},
    {"num": "M7", "titre": "Évaluation finale et remise du certificat", "contenus": "Évaluation écrite et pratique couvrant l'ensemble du programme · Présentation et défense du projet de synthèse · Remise du certificat IBIG EDUFORM · Construction du plan de développement post-formation", "duree": "Xh"}
  ],
  "volume_total": "{$duree_v}",
  "approche": ["Méthode pédagogique 1 spécifique à ce domaine", "Méthode 2", "Méthode 3", "Méthode 4", "Méthode 5", "Méthode 6"],
  "dispositif_technique": [
    {"composante": "Plateforme de classe virtuelle", "description": "Séances animées via ZOOM. Lien nominatif transmis avant chaque séance."},
    {"composante": "Espace apprenant en ligne", "description": "Accès à un espace numérique dédié regroupant supports, outils et ressources complémentaires consultables 24h/24."},
    {"composante": "Enregistrement des séances", "description": "Chaque séance est enregistrée et mise à disposition du bénéficiaire."},
    {"composante": "Suivi de l'assiduité", "description": "Relevé de connexion horodaté par séance."},
    {"composante": "Continuité de service", "description": "En cas d'incident technique majeur imputable au prestataire, la séance est reportée sans frais."},
    {"composante": "Souplesse de planification", "description": "Les séances sont programmées d'un commun accord. Tout report demandé au moins 24h à l'avance est accepté sans pénalité."}
  ],
  "deroulement": [
    {"etape": "Préparation", "activite": "Entretien de cadrage en visioconférence : validation des objectifs et du calendrier", "repere": "Avant le démarrage"},
    {"etape": "Préparation", "activite": "Diagnostic de positionnement et analyse du contexte professionnel", "repere": "Avant le démarrage"},
    {"etape": "Préparation", "activite": "Personnalisation du parcours et ouverture de l'espace apprenant", "repere": "Avant la 1ère séance"},
    {"etape": "Parcours", "activite": "Déroulement des séances de formation selon le programme", "repere": "Séances 1 à N"},
    {"etape": "Parcours", "activite": "Évaluation de certification et soutenance du plan d'action individuel", "repere": "Dernière séance"},
    {"etape": "Clôture", "activite": "Édition et remise du certificat, transmission du rapport de fin de formation", "repere": "À l'issue du parcours"},
    {"etape": "Consolidation", "activite": "2 séances de coaching de suivi à distance — OFFERTES", "repere": "Aux dates choisies par le bénéficiaire"}
  ],
  "formules_rythme": [
    {"formule": "Intensive", "frequence": "3 à 4 séances par semaine", "duree_totale": "Environ 2 semaines"},
    {"formule": "Standard", "frequence": "2 séances par semaine", "duree_totale": "Environ 3-4 semaines"},
    {"formule": "Étalée", "frequence": "1 séance par semaine", "duree_totale": "Environ 6-8 semaines"}
  ],
  "profil_formateur": ["Diplôme Bac+4/5 en [domaine exact] ou discipline connexe", "Expérience confirmée d'au moins 5 ans en entreprise dans ce domaine", "Expérience avérée en animation de formations individuelles et accompagnement", "Maîtrise des techniques de pédagogie active et des outils de classe virtuelle", "Engagement de confidentialité absolue sur les situations exposées par le bénéficiaire"],
  "evaluation_types": [
    {"type": "Évaluation diagnostique", "modalite": "Questionnaire de positionnement mesurant le niveau d'entrée"},
    {"type": "Évaluation formative", "modalite": "Travaux intersessions et mises en situation à chaque séance"},
    {"type": "Évaluation sommative", "modalite": "Épreuve finale (QCM + étude de cas) et soutenance du plan d'action"},
    {"type": "Évaluation de satisfaction", "modalite": "Questionnaire à chaud à l'issue de la dernière séance"}
  ],
  "certification": "Conditions précises et spécifiques pour l'obtention du certificat de cette formation.",
  "livrables": ["Rapport de diagnostic des besoins établi avant le démarrage", "Support de formation complet et personnalisé au format numérique", "Boîte à outils numérique spécifique au domaine : [nommer les outils précis]", "Enregistrements de l'intégralité des séances", "Plan d'action individuel formalisé et validé", "Le certificat nominatif vérifiable en ligne", "Relevé d'assiduité et rapport de fin de formation"],
  "obligations_ibig": "Liste des engagements précis d'IBIG EDUFORM pour cette formation.",
  "obligations_beneficiaire": "Liste des engagements du bénéficiaire pour cette formation.",
  "valeur_ajoutee": [
    {"point": "Un parcours entièrement recentré sur vous", "detail": "Le contenu est reconstruit à partir de votre diagnostic : vos défis, vos objectifs, votre contexte."},
    {"point": "Un formateur pour vous seul", "detail": "Aucun temps d'attente, aucune question laissée de côté. Les [X] heures vous sont intégralement consacrées."},
    {"point": "Une confidentialité totale", "detail": "Vous pouvez exposer sans réserve des situations sensibles — ce qu'un groupe ne permet jamais."},
    {"point": "Un rythme qui s'adapte à votre activité", "detail": "Créneaux choisis avec vous, reports acceptés sous 24h, formule accélérée possible."},
    {"point": "Une certification qui a de la valeur", "detail": "Certificat nominatif, numéroté et vérifiable en ligne par QR code — une reconnaissance opposable."},
    {"point": "Une garantie de satisfaction", "detail": "Si votre évaluation de satisfaction est inférieure à 80%, une séance complémentaire de 2h30 vous est assurée sans frais."}
  ],
  "confidentialite": "Texte de confidentialité spécifique précisant les engagements d'IBIG EDUFORM et du formateur."
}
PROMPT;

/* ── Appel API Claude Sonnet ── */
$payload = json_encode([
    'model'       => 'claude-sonnet-4-5',
    'max_tokens'  => 7000,
    'system'      => $system,
    'messages'    => [['role' => 'user', 'content' => $prompt]],
    'temperature' => 0.65,
]);

$ch = curl_init('https://api.anthropic.com/v1/messages');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_TIMEOUT        => 150,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'x-api-key: ' . $apiKey,
        'anthropic-version: 2023-06-01',
    ],
]);

$raw  = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err  = curl_error($ch);
curl_close($ch);

if ($err) { http_response_code(502); echo json_encode(['error' => 'Erreur réseau : ' . $err]); exit; }

$resp = json_decode($raw, true);
if ($code !== 200 || empty($resp['content'][0]['text'])) {
    $msg = $resp['error']['message'] ?? ('Erreur API ' . $code);
    http_response_code(502);
    echo json_encode(['error' => $msg]);
    exit;
}

$text = trim($resp['content'][0]['text']);
$text = preg_replace('/^```(?:json)?\s*/i', '', $text);
$text = preg_replace('/\s*```$/m', '', $text);

$data = json_decode($text, true);
if (!$data) {
    http_response_code(500);
    echo json_encode(['error' => 'Réponse IA non valide. Réessayez.', 'raw' => mb_substr($text, 0, 800)]);
    exit;
}

echo json_encode(['ok' => true, 'data' => $data]);
