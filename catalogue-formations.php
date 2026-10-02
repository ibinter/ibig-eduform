<?php
declare(strict_types=1);
// v2026-09-27
/**
 * IBIG EDUFORM — Catalogue des formations
 * Base 100% locale — toutes formations en DB IBIG EDUFORM
 */

if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/core/csrf.php';

ob_start();

$pageTitle = 'Catalogue des formations | IBIG EDUFORM';
$ogUrl     = 'https://ibig-eduform.com/catalogue-formations.php';

/* Parse catégorie avant header pour SEO (canonical + ogDesc) */
$_cat_seo = isset($_GET['cat']) ? trim(strip_tags((string)$_GET['cat'])) : '';

$ogDesc = $_cat_seo !== ''
    ? 'Formations professionnelles ' . $_cat_seo . ' certifiantes — disponibles en ligne et en présentiel dans l\'espace OHADA. Certifications reconnues, formateurs experts. Inscriptions ouvertes sur IBIG EDUFORM.'
    : 'Catalogue de plus de 1 500 formations professionnelles certifiantes IBIG EDUFORM — management, finance, RH, QHSE, logistique, numérique. En ligne ou en présentiel, reconnues dans 17 pays OHADA.';
$pageKeywords = $_cat_seo !== ''
    ? 'formation ' . $_cat_seo . ' certifiante Afrique, formation ' . $_cat_seo . ' en ligne OHADA, IBIG EDUFORM catalogue'
    : 'catalogue formations professionnelles Afrique, formations certifiantes OHADA, formations en ligne Côte d\'Ivoire, formations management finance RH IBIG EDUFORM';

/* Canonical sans ?page= pour éviter le contenu dupliqué en pagination */
$_canon = 'https://ibig-eduform.com/catalogue-formations.php';
if ($_cat_seo !== '') { $_canon .= '?cat=' . urlencode($_cat_seo); }
$extraHead = '<link rel="canonical" href="' . htmlspecialchars($_canon, ENT_QUOTES, 'UTF-8') . '">'
           . '<link rel="stylesheet" href="/assets/css/catalogue-formations.css?v=20261002">';

require_once __DIR__ . '/partials/header.php';

/* Toutes les formations sont désormais en base locale IBIG EDUFORM */
$all_formations = [];
$api_ok         = true;
$total_all      = 0;

/* ─── Correction tarifaire par durée (formule officielle) ───
   Prix individuel en ligne = heures × 11 250 FCFA, arrondi à 5 000.
   Les formations dont le prix API est inférieur au tarif de leur durée
   sont recalées au tarif officiel. */
$PRIX_FORMULE = [
    20 => 225000, 25 => 280000, 28 => 315000, 30 => 340000,
    35 => 395000, 40 => 450000, 45 => 505000, 55 => 620000,
    65 => 730000, 72 => 810000, 80 => 900000,
];
/* ─── Slugs classés Immobilier (recatégorisation locale) ─────────── */
$IMMO_SLUGS = [
    'agent-immobilier-professionnel','charge-financement-immobilier',
    'charge-programmes-immobiliers','directeur-programme-immobilier',
    'droit-immobilier-litiges-fonciers','evaluation-expertise-immobiliere',
    'facility-management-maintenance','fiscalite-immobiliere',
    'foncier-urbanisme-droit-ci','gestionnaire-patrimoine-immobilier',
    'gestionnaire-immobilier','immobilier-durable-eco-construction',
    'immobilier-3en1','immobilier-social-logement-abordable',
    'negociateur-immobilier','promoteur-immobilier-lotissement',
    'promoteur-immobilier-junior','promotion-immobiliere',
    'specialiste-portefeuille-immobilier',
];
$IMMO_SLUGS_SET = array_flip($IMMO_SLUGS);

/* ─── Recatégorisation "Autres" par mots-clés ────────── */
function recategorise_autres(string $name): string {
    $n = mb_strtolower($name, 'UTF-8');
    // Informatique & Tech
    if (preg_match('/accessibilité web|architecture logicielle|clean code|développement (saas|d.application)|cybersecurity|cybersécurité|ethical hacking|digital forensics|incident response|pentesting|soc analyst|threat intelligence|phishing|tests automatisés|looker studio|microsoft 365|microsoft project|tableau (desktop|software)|tableaux de bord|sage 100|sage états|power bi|business intelligence|excel (avancé|pour)|power query|google workspace|erp.*logiciel|logiciel.*erp|sap.*sage|sage.*sap|administrateur réseau|support it\b|technicien (informatique|support)|webmaster|développeur web|gestionnaire de base de données|data governance|big data|architecture de données|robotique collaborative/ui', $n)) {
        return 'Informatique & Tech';
    }
    if (preg_match('/\b(cloud|aws|azure|docker|kubernetes|linux|devops|git\b|node\.?js|react|vue\.?js|django|laravel|\bphp\b|python|fastapi|typescript|\bsql\b|nosql|mongodb|postgresql|\bapi\b|graphql|mlops|\bsre\b|ci\/cd|ansible|terraform|low.code|no.code|webflow|flutter|kotlin|android|ios|smart contract|test logiciel|\bqa\b|arduino|\bplc\b|automate|\biot\b|capteur|impression 3d|next\.js|typescript)/ui', $n)) {
        return 'Informatique & Tech';
    }
    // IA & Digitalisation
    if (preg_match('/chief (data|digital)|decision making.*data|détection de fraude.*data|marketplace.*digital|livestreaming|marketplace.*africaine/ui', $n)) {
        return 'IA & Digitalisation';
    }
    if (preg_match('/\b(ia\b|intelligence artificielle|machine learning|deep learning|llm|nlp|chatbot|chatgpt|gpt\b|prompt engin|copilot|\bai\b|data (analys|scien|engineer|visual|story)|data analyst|digitalisa|digital twin|smart (farm|factor|city)|voice ai|\brag\b|pytorch|tensorflow|gemini|\bclaude\b|anthropic|midjourney|stable diffusion|mlops|vertex ai|sagemaker|databricks|computer vision|fine.tuning|finops|rev.?ops|sales (auto|oper)|procurement digital|automatisation.*processus|automatisation.*avec|rpa\b|zapier|transformation (digit|numérique))/ui', $n)) {
        return 'IA & Digitalisation';
    }
    // Comptabilité & Finance
    if (preg_match('/conseiller financier|contrôle budgétaire|finance de projets|gestion d.actifs|gestion financière pour non|rolling forecast|facture normalisée|gestion d.une coopérative|gestion des risques stratégiques/ui', $n)) {
        return 'Comptabilité & Finance';
    }
    if (preg_match('/\b(comptabl|fiscal|fiscalit|budget|trésorerie|audit|contrôl.*gest|finance (dur|décen|d.entr|pour|projets|intern|verte|islamique|internationale)|financial|fin\.\s*plan|reporting (financ|ifrs)|consolidation|valorisation|fusions.acqui|marchés financ|brvm|gestionnaire comptable|secrétaire comptable|chef comptable|directeur financ|cfo|green finance|impact invest|lutte.*fraude financ|lbc.ft|crypto.actif|token|syscohada|actifs immobilisés|risques de change|analyse financière|tva\b|déclarations fiscales)/ui', $n)) {
        return 'Comptabilité & Finance';
    }
    // GRH
    if (preg_match('/future of work|gestion des conflits.*climat|gestion des compétences par la data/ui', $n)) {
        return 'GRH';
    }
    if (preg_match('/\b(ressources humaines|\bgrh\b|recrutement|paie\b|talent|workforce|personnel admin|people analytic|employer brand|employee engag|expérience collabor|diversité.*équit|mobilité.*salar|outplacement|reskilling|upskilling|succession plan|politique salar|gestionnaire de personnel|digital hr|hr tech|assessment center|gpec\b|gestion prévisionnelle.*emploi|relations sociales|contrats de travail|absentéisme|présentéisme|expatriés.*mobilité|retraites.*avantages|performances individuelles|plan de développement des compétences|rupture conventionnelle|licenciement|marque employeur|transformation.*rh\b|changement.*rh\b|assistant rh|gestionnaire rh|chargé.*rh|responsable rh|chargé des relations sociales)/ui', $n)) {
        return 'GRH';
    }
    // Management & Leadership
    if (preg_match('/gestion d.entreprise familiale|gestion des risques stratégiques|lean six sigma/ui', $n)) {
        return 'Management & Leadership';
    }
    if (preg_match('/\b(management|leadership|\bceo\b|gouvernance|okr\b|kpi.*dirigeant|intelligence écon|veille concurr|gestion de crise|direction de centre|corporate ventur|facilitation.*réunion|résilience.*chang|strategic foresight|scale.up|succession plan|mindset.*entrepreneur|méthodes agiles|scrum|kanban\b|prince2|gestion de projet|gestion des projets|chef de projet|superviseur|manager de|responsable de service|conduite du changement|conduite de transformation|performance organisationnelle|six sigma|lean management|amélioration continue|planification stratégique|pilotage.*performance|gestion par objectifs|customer experience|\bcx\b|directeur commercial|pilotage des ventes)/ui', $n)) {
        return 'Management & Leadership';
    }
    // Droit & Juridique
    if (preg_match('/\b(droit\b|juridique|réglementaire|rgpd|protection des données|reg.?tech|arbitrage|veille légale|marchés publics|passation.*marchés|appels d.offres|lutte.*blanchiment|lbc.ft|transit douanier|dédouanement|transport (aérien|maritime)|incoterms|fret\b|associations.*fondations|organisations.*but non lucratif|droit disciplinaire|sanctions disciplinaires|contrats de travaux)/ui', $n)) {
        return 'Droit & Juridique';
    }
    // Logistique & Supply Chain
    if (preg_match('/\b(logistique|supply chain|achats\b|approvisionnement|procurement|gestion des stocks|gestion des entrepôts|gestion d.entrepôt|flotte automobile|transport routier|transitaire|transit\b|dédouanement|incoterms|fret\b|douane\b|strategic sourc|e.procurement)/ui', $n)) {
        return 'Logistique & Supply Chain';
    }
    // BTP & Construction
    if (preg_match('/\b(btp\b|construction|bâtiment|bim\b|plomberie|menuiserie|carrelage|peinture décor|électrotechnic|automatisation industrielle|génie (civil|sanitaire|climati)|aménagement urbain|urbanisme|permis de construire|visualisation 3d architect|chantier|conducteur de travaux|métreur|topographie|réhabilitation.*bâtiment|rénovation.*bâtiment|froid.climatisation|électricité (du bâtiment|industrielle|bâtiment)|domotique|menuiserie|revêtement)/ui', $n)) {
        return 'BTP & Construction';
    }
    // Agriculture
    if (preg_match('/\b(agricol|agriculture\b|agroforest|agritech|agrobusiness|agro.alimentaire|transformation agroalimentaire|apiculture|aviculture|élevage|zootechnie|hévéacult|maraîch|horticulture|permaculture|smart farm|irrigation|financement.*agricol|exploitation agricole)/ui', $n)) {
        return 'Agriculture';
    }
    // Mines, Énergie & Pétrole
    if (preg_match('/\b(pétrole|mines\b|mine\b|minier|énergie (solaire|éolien|renouvelable)|énergies renouvelables|hydrogène|hse oil|transition énergét|décarbona|carbon account|net zero|industrie 4\.0|maintenance (industr|4\.0)|robotique industrielle|électrotechnicien industr|hydrocarbure|forage|puits pétrolier|géologie|contrats pétrolier|sécurité.*extractive|santé.*minier|efficacité énergétique)/ui', $n)) {
        return 'Mines, Énergie & Pétrole';
    }
    // QHSE
    if (preg_match('/gestion des déchets|gestion des produits chimiques|lean six sigma|écoconception/ui', $n)) {
        return 'QHSE';
    }
    if (preg_match('/\b(qhse|hse\b|rse\b|développement durable|éco.concep|économie circulaire|gouvernance durable|green it|reporting (esg|extra)|responsabil.*sociét|responsable (rse|développement durable)|risques climatiques|carbon|csrd|esg\b|animateur hse|odd\b|agenda 2030|objectifs.*développement durable|certification bio|certification.*qualité.*organisme|gestion de l.eau|ressources naturelles.*durable)/ui', $n)) {
        return 'QHSE';
    }
    // Santé & Pharmacie
    if (preg_match('/\b(santé|médecine|nutrition|paludisme|diabète|oncologie|maladies|naturopathie|herborist|secourisme|thérapie|hypnose|pharmacovigilance|gestion des médicaments|facturation.*assurance maladie|qualité.*hospitalière|accréditation hospitalière|esthéticienne médicale|aide.soignant|infirmier|bloc opératoire|stérilisation|kinésithérap)/ui', $n)) {
        return 'Santé & Pharmacie';
    }
    // Beauté & Bien-être
    if (preg_match('/\b(beauté|bien.être|onglerie|soins du chev|coiffure|esthétique|cosmétologie|marque cosmétique|maquillage professionnel|microblading|soins capillaires|artisanat|production musicale|nail art)/ui', $n)) {
        return 'Beauté & Bien-être';
    }
    // Tourisme & Hôtellerie
    if (preg_match('/\b(tourisme|hôtellerie|gastronomie|chef de rang|restauration|mice\b|circuits touristiques|animation touristique|sommellerie)/ui', $n)) {
        return 'Tourisme & Hôtellerie';
    }
    // Immobilier
    if (preg_match('/gestion immobilière|gestion locative|copropriété|locatif|syndic|marchand de biens|home staging|foncier|aménagement intérieur|immobilier|expertise immobilière|évaluation.*immobilière|montage financier.*immobilier|promoteur immobilier|commercial immobilier|décoration intérieure|architecture d.intérieur|urbanisme.*aménagement|aménagement du territoire/ui', $n)) {
        return 'Immobilier';
    }
    // Infographie & Design
    if (preg_match('/after effects|vfx|\bux\b|ui.?ux|ux.?ui|typographie|design graphique|\b3d\b|visualisation 3d|revit|archicad|infographie|adobe (illustrator|photoshop|premiere|indesign)|canva\b|figma\b|autocad|davinci resolve|photographie|montage vidéo|vidéaste|motion design|animation (2d|vidéo|numérique)|brand designer|identité visuelle|graphiste|mise en page (éditoriale|professionnel)|rédacteur web|couture|modélisme|stylisme|design de mode|mode africaine|broderie/ui', $n)) {
        return 'Infographie & Design';
    }
    // Gestion Commerciale & Marketing
    if (preg_match('/gescom|événementiel digital|meta ads|responsable centre d.appels|relation client/ui', $n)) {
        return 'Gestion Commerciale & Marketing';
    }
    if (preg_match('/\b(marketing|branding|brand content|storytelling|copywriting|social selling|linkedin|image de marque|pricing|sales|e.commerce|\bcrm\b|parcours d.achat|avis clients|e.réputation|gestion des fournisseurs|seo\b|google ads|vente (b2b|au détail|complexe|flash)|négociation commerciale|négociation avancée|fidélisation client|trade marketing|merchandising|growth hacking|email marketing|newsletter.*marketing|influence marketing|performance commerciale|développement.*réseau commercial|export.*marketing|grand compte|vente.*international|amazon.*marketplace)/ui', $n)) {
        return 'Gestion Commerciale & Marketing';
    }
    // Direction & Administration
    if (preg_match('/la fonction d.assistant.*direction/ui', $n)) {
        return 'Direction & Administration';
    }
    if (preg_match('/\b(secrétaire|assistante? de direction|secrétariat|administration générale|responsable admin|coordination admin|services généraux|gestion documentaire|archivage|archiviste|office manager|gestion administrative|protocole administratif|protocole.*cérémoni|voyages d.affaires|sécurité routière|gestion des associations|organisation.*associations|suivi.*admin|suivi.*budgét|responsable.*opérationnel)/ui', $n)) {
        return 'Direction & Administration';
    }
    // Entrepreneuriat
    if (preg_match('/\b(entrepreneuriat|startup|start.up|\bpme\b|plan d.affaires|diaspora entrepreneur|crowdfunding|financement.*pme|scale.up|commerce intra.africain|zlecaf|corporate ventur|intrapreneuriat|coopérative)/ui', $n)) {
        return 'Entrepreneuriat';
    }
    // Communication Professionnelle
    if (preg_match('/communication (prof|managér|instit|écrite|orale|de crise|en situation de crise|d.entreprise|gouvernement|stratégique)|prise de parole|\bart oratoire\b|rédaction (admin|corporate|en ligne|de cont|de propos|de livres?|de rapports?)|création.*livre blanc|livres? blancs?|lobbying|plaidoyer|attaché de presse|relations avec les médias|protocole.*étiquette|étiquette professionnel|négociation.*persuasion|persuasion.*professionnel|chargé de communication|français professionnel|personal branding|marque personnelle/ui', $n)) {
        return 'Communication Professionnelle';
    }
    // Éducation & Formation
    if (preg_match('/gestion des partenariats.*bailleurs|rédaction de propositions.*ong|évaluation des acquis/ui', $n)) {
        return 'Éducation & Formation';
    }
    if (preg_match('/\b(formation (à distance|internat|de formateurs|professionnelle)|coaching (scolaire|pédag)|mentorat|tutorat|gamification|consultant formateur|déplacement formateur|formateur professionnel|ingénierie pédagogique|e.learning\b|pédagogie (active|différenciée|bienveillante)|gestion d.établissement scolaire|gestion d.une école|conception de modules|créateur de cours|évaluation des apprentissages|certification.*organisme.*formation|chargé de formation|suivi.évaluation|meal\b|kobo|renforcement des capacités)/ui', $n)) {
        return 'Éducation & Formation';
    }
    // Développement Personnel
    if (preg_match('/\b(anglais|développement (de la carrière|personnel)|découverte.*domaine|empathie|écoute active|gestion de vie|mindset|motivation.*disciplin|préparation.*concours|résilience|développ.*carrière|employabilité|gestion du burnout|bien.être (au travail|holistique)|qvt\b|coaching (professionnel|certifié|sportif)|intelligence émotionnelle|prise de décision|résolution de problème|leadership féminin|diversité.*équité.*inclusion|diversité.*inclusion|mentorat.*jeunes|orientation scolaire|orientation.*professionnel|spa management|massage.*bien.être)/ui', $n)) {
        return 'Développement Personnel';
    }
    // Banque & Assurance (fintech, marchés financiers)
    if (preg_match('/banking|insurtech|fintech|digital lending|embedded finance|open banking|mobile money|blockchain.*(finance|invest|affaires|crypto)|marchés financ|bourse\b|\bbrvm\b|private equity|finance islamique|finance verte|microfinance|capital.invest|digital.*bancaire|banque.*digital|services financiers digital/ui', $n)) {
        return 'Banque & Assurance';
    }
    // Communication Professionnelle (community, journalisme, podcast, création contenu)
    if (preg_match('/community management|journalisme|audiovisuel\b|radio\b|relations presse|\bpr\b|communication (audiovisuelle|gouvernementale|managériale|institutionnelle)|podcast|twitch|streaming (en direct|professionnel)|créateur de contenu|youtube|tiktok|newsletter.*contenu|rédaction.*(web|copywriting)|ghostwriting|influence.*marketing|personal branding.*communication/ui', $n)) {
        return 'Communication Professionnelle';
    }
    return 'Autres';
}

/* ─── Pré-charger les noms normalisés Samedi Pro depuis la DB ────────
   Nécessaire car les formations Samedi Pro peuvent provenir de l'API
   Partners (sans flag _samedi_pro), mais être marquées is_samedi_pro=1
   en base locale. Sans ce pré-chargement, la déduplication supprime la
   version locale et l'API reçoit la correction de prix par erreur. */
$_sam_norm_fn = fn(string $s): string => mb_strtolower(trim(preg_replace('/[\s\-–—_]+/', ' ', $s)), 'UTF-8');
$_samedi_pro_names = [];
try {
    require_once __DIR__ . '/core/database.php';
    $_pdo_sam = Database::connect();
    $_sam_rows = $_pdo_sam->query("SELECT titre FROM formations WHERE is_samedi_pro = 1 AND statut = 'active'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($_sam_rows as $_sn) $_samedi_pro_names[$_sam_norm_fn((string)$_sn)] = true;
    unset($_pdo_sam, $_sam_rows, $_sn);
} catch (Throwable $_e) { /* silencieux */ }

$all_formations = array_map(function(array $f) use ($PRIX_FORMULE, $IMMO_SLUGS_SET, $_samedi_pro_names, $_sam_norm_fn): array {
    // Recatégorisation Immobilier (par slug)
    if (isset($IMMO_SLUGS_SET[$f['slug'] ?? ''])) {
        $f['category'] = 'Immobilier';
    }
    // Recatégorisation "Autres" par mots-clés du nom
    if (($f['category'] ?? '') === 'Autres') {
        $f['category'] = recategorise_autres((string)($f['name'] ?? ''));
    }
    // Correction tarifaire — Samedi Pro exclus (tarif libre)
    $_nom_api = (string)($f['name'] ?? '');
    if (!empty($f['_samedi_pro'])
        || stripos($_nom_api, 'samedi') !== false
        || isset($_samedi_pro_names[$_sam_norm_fn($_nom_api)])
    ) return $f;
    $prix_actuel = (int)($f['price'] ?? 0);
    if ($prix_actuel <= 0) return $f;
    $texte = ($f['name'] ?? '') . ' ' . ($f['description'] ?? '');
    $prix_corrige = $prix_actuel;
    $h_detect = 0;
    if (preg_match('/\((\d+)h\)/i', $texte, $m)) {
        $h_detect = (int)$m[1];
        if (empty($f['_duree'])) $f['_duree'] = $h_detect . 'H';
    } elseif (preg_match('/\b(\d+)\s*heures?\b/i', $texte, $m)) {
        $h_detect = (int)$m[1];
        if (empty($f['_duree'])) $f['_duree'] = $h_detect . 'H';
    }
    // Fallback : lire la durée déjà définie (champ _duree de la formation API)
    if ($h_detect <= 0 && !empty($f['_duree'])) {
        if (preg_match('/(\d+)/i', (string)$f['_duree'], $md)) $h_detect = (int)$md[1];
    }
    // Appliquer la grille tarifaire officielle pour toute formation avec durée connue
    if ($h_detect >= 20) {
        $pf = isset($PRIX_FORMULE[$h_detect])
            ? $PRIX_FORMULE[$h_detect]
            : (int)(round($h_detect * 11250 / 5000) * 5000);
        $pf = max($pf, 200000);
        if ($prix_corrige !== $pf) {
            $prix_corrige = $pf;
            $f['grille']  = null; // forcer recalcul presentiel via grille_local()
        }
    }
    // Plancher absolu
    if ($prix_corrige < 200000) $prix_corrige = 200000;
    if ($prix_corrige !== $prix_actuel) {
        $f['price'] = $prix_corrige;
        if (!isset($f['grille'])) $f['grille'] = null;
    }
    // Inférence durée depuis le prix si toujours inconnue
    if (empty($f['_duree']) && $prix_corrige >= 200000) {
        $h_inf = max(20, (int)(round($prix_corrige / 11250 / 5) * 5));
        $f['_duree'] = $h_inf . 'H';
    }
    return $f;
}, $all_formations);

/* ─── Suppression des formations groupées (X en 1, packs…) ─────── */
$all_formations = array_values(array_filter($all_formations, function(array $f): bool {
    $nom  = mb_strtolower((string)($f['name'] ?? ''), 'UTF-8');
    $slug = (string)($f['slug'] ?? '');
    if (preg_match('/\d+\s*en\s*1/u', $nom))  return false;
    if (preg_match('/\d+en\d/i',       $slug)) return false;
    return true;
}));

/* ─── Formations locales (base IBIG EDUFORM) ────────── */
try {
    require_once __DIR__ . '/core/database.php';
    $pdo_cat = Database::connect();
    $local_rows = $pdo_cat->query("
        SELECT id, titre, slug, domaine, description, duree,
               GREATEST(COALESCE(tarif_en_ligne, 0), 200000)  AS tarif_en_ligne,
               GREATEST(COALESCE(tarif_presentiel, 0), 250000) AS tarif_presentiel,
               mode,
               COALESCE(is_samedi_pro, 0) AS is_samedi_pro
        FROM formations
        WHERE statut = 'active'
          AND (annee = 0 OR annee IS NULL OR annee = YEAR(CURDATE()))
          AND titre NOT LIKE 'Tarif Groupe%'
        ORDER BY titre ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Charger les niveaux actifs par formation (pour les pills catalogue)
    $niveaux_map = [];
    try {
        $niv_rows = $pdo_cat->query("
            SELECT formation_id, niveau, duree_heures, tarif_en_ligne, tarif_presentiel, tarif_hybride
            FROM formation_niveaux
            WHERE statut = 'actif'
            ORDER BY ordre_affichage ASC
        ")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($niv_rows as $nr) {
            $niveaux_map[(int)$nr['formation_id']][] = [
                'n'  => $nr['niveau'],
                'h'  => (int)$nr['duree_heures'],
                'ol' => (int)$nr['tarif_en_ligne'],
                'pr' => (int)$nr['tarif_presentiel'],
                'hy' => (int)$nr['tarif_hybride'],
            ];
        }
    } catch (Throwable $_e) {}

    // Mapping domaine local → catégorie catalogue
    $DOM_MAP = [
        'QHSE'                              => 'QHSE',
        'Comptabilité'                      => 'Comptabilité & Finance',
        'Comptabilité & Finance'            => 'Comptabilité & Finance',
        'Finance'                           => 'Comptabilité & Finance',
        'Finance & Direction'               => 'Direction & Administration',
        'Finance & Audit'                   => 'Comptabilité & Finance',
        'Finance & Assurance'               => 'Banque & Assurance',
        'Contrôle de Gestion'               => 'Comptabilité & Finance',
        'Audit & Contrôle'                  => 'Comptabilité & Finance',
        'Fiscalité'                         => 'Comptabilité & Finance',
        'Fiscalité & Conformité'            => 'Comptabilité & Finance',
        'Ressources Humaines'               => 'GRH',
        'RH & Paie'                         => 'GRH',
        'Management'                        => 'Management & Leadership',
        'Leadership'                        => 'Management & Leadership',
        'Gestion de Projet'                 => 'Management & Leadership',
        'Gestion de Projets'                => 'Management & Leadership',
        'Logistique'                        => 'Logistique & Supply Chain',
        'Logistique & SCM'                  => 'Logistique & Supply Chain',
        'Logistique & Supply Chain'         => 'Logistique & Supply Chain',
        'Logistique & Data'                 => 'Logistique & Supply Chain',
        'Logiciel'                          => 'Informatique & Tech',
        'Logiciels de Gestion (Sage)'       => 'Informatique & Tech',
        'Logiciels de Gestion (SAP)'        => 'Informatique & Tech',
        'Bureautique & Data'                => 'Informatique & Tech',
        'Bureautique'                       => 'Informatique & Tech',
        'Data & BI'                         => 'Informatique & Tech',
        'Digital & IA'                      => 'IA & Digitalisation',
        'Intelligence Artificielle'         => 'IA & Digitalisation',
        'IA & Digitalisation'               => 'IA & Digitalisation',
        'Entrepreneuriat'                   => 'Entrepreneuriat',
        'Business Development'              => 'Entrepreneuriat',
        'Financement & Partenariats'        => 'Entrepreneuriat',
        'Communication'                     => 'Communication Professionnelle',
        'Communication Institutionnelle'    => 'Communication Professionnelle',
        'Gestion'                           => 'Direction & Administration',
        'Gestion Commerciale & Marketing'   => 'Gestion Commerciale & Marketing',
        'Commerce & Marketing'              => 'Gestion Commerciale & Marketing',
        'Marketing Digital'                 => 'Gestion Commerciale & Marketing',
        'Marketing'                         => 'Gestion Commerciale & Marketing',
        'Droit'                             => 'Droit & Juridique',
        'Droit Social'                      => 'Droit & Juridique',
        'Droit & Administration'            => 'Droit & Juridique',
        'Marchés Publics & Achats'          => 'Droit & Juridique',
        'Marché public'                     => 'Droit & Juridique',
        'Immobilier'                        => 'Immobilier',
        'Banque & Assurance'                => 'Banque & Assurance',
        'Humanitaire & ONG'                 => 'Éducation & Formation',
        'Développement personnel'           => 'Développement Personnel',
        'Développement Personnel'           => 'Développement Personnel',
        'Design & Communication'            => 'Infographie & Design',
        'Collecte & Analyse de Données'     => 'Informatique & Tech',
        'Consulting'                        => 'Direction & Administration',
        'Assistanat'                        => 'Direction & Administration',
        'Stratégie'                         => 'Direction & Administration',
        'IBIG DIGITAL BUSINESS'                        => 'Gestion Commerciale & Marketing',
        'IBIG E-COMMERCE'                              => 'Gestion Commerciale & Marketing',
        'IBIG SALES ACADEMY'                           => 'Gestion Commerciale & Marketing',
        'IBIG EXECUTIVE ACADEMY'                       => 'Entrepreneuriat',
        'IBIG DATA & AI'                               => 'IA & Digitalisation',
        'IBIG LEADERSHIP & COMMUNICATION'              => 'Communication Professionnelle',
        'Comptabilité – Finance – Gestion – ERP – SAP' => 'Comptabilité & Finance',
        'Finance – Comptabilité – Gestion'             => 'Comptabilité & Finance',
        'Gestion & Finance'                            => 'Comptabilité & Finance',
        'Communication & Leadership'                   => 'Communication Professionnelle',
        'Communication Professionnelle'                => 'Communication Professionnelle',
        'RH'                                           => 'GRH',
        'RH & Paie Ivoirienne'                         => 'GRH',
        'Logiciels'                                    => 'Informatique & Tech',
        'SAP'                                          => 'Informatique & Tech',
        'ERP'                                          => 'Informatique & Tech',
        'Sage'                                         => 'Informatique & Tech',
        'Odoo'                                         => 'Informatique & Tech',
        'Informatique & Tech'                          => 'Informatique & Tech',
        'Cybersécurité'                                => 'Informatique & Tech',
        'Réseaux & Sécurité'                           => 'Informatique & Tech',
        'WordPress & Web'                              => 'Informatique & Tech',
        'Humanitaire'                                  => 'Éducation & Formation',
        'ONG & Projets'                                => 'Éducation & Formation',
        'ONG & Développement'                          => 'Éducation & Formation',
        'Suivi-Évaluation'                             => 'Éducation & Formation',
        'Tourisme & Hôtellerie'                        => 'Tourisme & Hôtellerie',
        'BTP'                                          => 'BTP & Construction',
        'Mines & Énergie'                              => 'Mines, Énergie & Pétrole',
        'Santé'                                        => 'Santé & Pharmacie',
        'Agriculture'                                  => 'Agriculture',
        'Agrobusiness'                                 => 'Agriculture',
        'Droit & Juridique'                            => 'Droit & Juridique',
        'Droit OHADA'                                  => 'Droit & Juridique',
        'Marchés Publics'                              => 'Droit & Juridique',
        'Assurance'                                    => 'Banque & Assurance',
        'Microfinance'                                 => 'Banque & Assurance',
        /* ── Domaines assignés par le script d'import (manquants ci-dessus) ── */
        'Banque & Assurance'                           => 'Banque & Assurance',
        'BTP & Construction'                           => 'BTP & Construction',
        'Direction & Management'                       => 'Direction & Administration',
        'Droit & Fiscalité'                            => 'Droit & Juridique',
        'Formation & Pédagogie'                        => 'Éducation & Formation',
        'Infographie & Design'                         => 'Infographie & Design',
        'Informatique & Digital'                       => 'Informatique & Tech',
        'Mines, Énergie & Pétrole'                     => 'Mines, Énergie & Pétrole',
        'Santé & Pharmacie'                            => 'Santé & Pharmacie',
    ];

    // Titres normalisés déjà présents dans l'API pour déduplication robuste
    $normalize = fn(string $s): string => mb_strtolower(trim(preg_replace('/[\s\-–—_]+/', ' ', $s)), 'UTF-8');
    $api_names = [];
    foreach ($all_formations as $af) {
        $api_names[$normalize((string)($af['name'] ?? ''))] = true;
    }

    // Index des titres locaux normalisés — la version locale prime sur l'API
    $local_norms = [];
    foreach ($local_rows as $r) {
        $local_norms[$normalize((string)($r['titre'] ?? ''))] = true;
    }
    // Supprimer de l'API les formations dont le local a une version
    $all_formations = array_values(array_filter($all_formations, function(array $af) use ($normalize, $local_norms): bool {
        return !isset($local_norms[$normalize((string)($af['name'] ?? ''))]);
    }));

    $local_formations = [];
    foreach ($local_rows as $r) {
        $cat   = $DOM_MAP[$r['domaine'] ?? ''] ?? 'Autres';
        $slug  = $r['slug'] ?? ('local-' . $r['id']);
        // Recatégorisation Immobilier par slug (priorité sur domaine DB)
        if (isset($IMMO_SLUGS_SET[$slug])) { $cat = 'Immobilier'; }
        // Recatégorisation par mots-clés si domaine non reconnu
        if ($cat === 'Autres') { $cat = recategorise_autres((string)($r['titre'] ?? '')); }
        $prix  = (int)($r['tarif_en_ligne'] ?? 0);
        // Inférence durée si absente
        $duree_val = trim((string)($r['duree'] ?? ''));
        if ($duree_val === '' && $prix >= 200000) {
            $duree_val = max(20, (int)(round($prix / 11250 / 5) * 5)) . 'H';
        }
        $norm  = $normalize((string)($r['titre'] ?? ''));
        unset($norm); // local prime toujours sur l'API (filtrage fait ci-dessus)
        $local_formations[] = [
            'id'          => 'local_' . $r['id'],
            'name'        => $r['titre'],
            'slug'        => $slug,
            'description' => $r['description'] ?? '',
            'price'       => $prix,
            'rate'        => 0,
            'siteUrl'     => '',
            'category'    => $cat,
            'grille'      => null,
            '_local'      => true,
            '_mode'       => $r['mode'] ?? 'presentiel',
            '_duree'      => $duree_val,
            '_samedi_pro' => !empty($r['is_samedi_pro']),
            '_tarif_pres' => (int)($r['tarif_presentiel'] ?? 0),
            '_niveaux'    => $niveaux_map[(int)$r['id']] ?? [],
        ];
    }
    $all_formations = array_merge($all_formations, $local_formations);
    $total_all     += count($local_formations);
} catch (Throwable $e) { /* silencieux si DB indisponible */ }

/* ─── Catégories (ordre Partners) ───────────────────── */
$CATEGORIES_ORDER = [
    'Agriculture','Banque & Assurance','Beauté & Bien-être','BTP & Construction',
    'Communication','Communication Professionnelle','Comptabilité & Finance',
    'Création de Contenu','Direction & Administration','Droit & Juridique',
    'Développement Personnel','Entrepreneuriat','Éducation & Formation','GRH',
    'Gestion Commerciale & Marketing','IA & Digitalisation','Immobilier',
    'Infographie & Design','Informatique & Tech','Logistique & Supply Chain',
    'Management & Leadership','Mines, Énergie & Pétrole','QHSE',
    'Santé & Pharmacie','Tourisme & Hôtellerie','Autres',
];

$cats_count = [];
foreach ($all_formations as $f) {
    $c = $f['category'] ?? 'Autres';
    $cats_count[$c] = ($cats_count[$c] ?? 0) + 1;
}
// Trier selon l'ordre Partners, n'afficher que les catégories présentes
$active_cats = array_filter($CATEGORIES_ORDER, fn($c) => isset($cats_count[$c]));

/* ─── Couleurs catégories ───────────────────────────── */
$CAT_COLORS = [
    'Agriculture'                  => ['#1a6b3c','#e8f7f0'],
    'Banque & Assurance'           => ['#0a5fa8','#e6f0fb'],
    'Beauté & Bien-être'           => ['#a0257f','#fce9f7'],
    'BTP & Construction'           => ['#c05c1a','#fdf1e8'],
    'Communication'                => ['#7c3aed','#f3eeff'],
    'Communication Professionnelle'=> ['#6d28d9','#f0ecff'],
    'Comptabilité & Finance'       => ['#0a1733','#e8edf8'],
    'Création de Contenu'          => ['#db2777','#fce7f3'],
    'Direction & Administration'   => ['#1d4ed8','#eff6ff'],
    'Droit & Juridique'            => ['#7c2d12','#fff7ed'],
    'Développement Personnel'      => ['#0891b2','#ecfeff'],
    'Entrepreneuriat'              => ['#d97706','#fffbeb'],
    'Éducation & Formation'        => ['#4338ca','#eef2ff'],
    'GRH'                          => ['#0f766e','#f0fdfa'],
    'Gestion Commerciale & Marketing'=> ['#b91c1c','#fef2f2'],
    'IA & Digitalisation'          => ['#1e1b4b','#eef2ff'],
    'Immobilier'                   => ['#92400e','#fef3c7'],
    'Infographie & Design'         => ['#be185d','#fdf2f8'],
    'Informatique & Tech'          => ['#1e40af','#eff6ff'],
    'Logistique & Supply Chain'    => ['#374151','#f9fafb'],
    'Management & Leadership'      => ['#0d2260','#e8edf8'],
    'Mines, Énergie & Pétrole'    => ['#78350f','#fffbeb'],
    'QHSE'                         => ['#065f46','#d1fae5'],
    'Santé & Pharmacie'            => ['#1d6a2a','#dcfce7'],
    'Tourisme & Hôtellerie'        => ['#0369a1','#f0f9ff'],
    'Création de Contenu'          => ['#db2777','#fce7f3'],
    'Autres'                       => ['#374151','#f1f5f9'],
];
function cat_col(string $c, array $m): array { return $m[$c] ?? ['#0a1733','#e8edf8']; }

/* ─── Lecture état initial URL (pour JS init) ────────── */
$cat_sel   = isset($_GET['cat'])   ? trim(strip_tags((string)$_GET['cat']))   : '';
$q_raw     = isset($_GET['q'])     ? trim(strip_tags((string)$_GET['q']))     : '';
$mode_sel  = isset($_GET['mode'])  ? trim(strip_tags((string)$_GET['mode']))  : '';
$prix_sel  = isset($_GET['prix'])  ? trim(strip_tags((string)$_GET['prix']))  : '';
$duree_sel = isset($_GET['duree']) ? trim(strip_tags((string)$_GET['duree'])) : '';

/* ─── Préparer le JSON pour le moteur JS ─────────────── */
$json_data = [];
foreach ($all_formations as $f) {
    $prix     = (int)($f['price'] ?? 0);
    $cat      = (string)($f['category'] ?? 'Autres');
    [$colD, $colL] = cat_col($cat, $CAT_COLORS);
    $isLocal  = !empty($f['_local']);
    $g = null;
    if ($prix > 0) {
        $g = (!empty($f['grille']) && is_array($f['grille']))
            ? $f['grille']
            : grille_local($prix, (int)($f['_tarif_pres'] ?? 0));
        if (empty($g['hybride'])) {
            $g['hybride'] = r5(((int)($g['individuel_online'] ?? $prix) + (int)($g['individuel_pres'] ?? r5($prix * 10 / 7))) / 2);
        }
        if ((int)($g['individuel_online'] ?? 0) < 200000) $g['individuel_online'] = 200000;
        if ((int)($g['individuel_pres']   ?? 0) < 250000) $g['individuel_pres']   = 250000;
        $g['hybride'] = r5(((int)$g['individuel_online'] + (int)$g['individuel_pres']) / 2);
    }
    $insUrl  = preinsc_url((string)($f['name'] ?? ''), $cat, (string)($f['slug'] ?? ''), $prix);
    $tdrUrl  = '/tdr-local-pdf.php?slug=' . urlencode((string)($f['slug'] ?? ''));
    /* "Voir" : toujours vers la fiche détail */
    $lien    = '/formation-detail.php?slug=' . urlencode((string)($f['slug'] ?? ''));
    $dureeStr = strtolower(trim((string)($f['_duree'] ?? '')));
    $dureeH   = 0;
    if (preg_match('/(\d+)\s*h/i', $dureeStr, $md)) $dureeH = (int)$md[1];
    elseif (preg_match('/(\d+)\s*mois/i', $dureeStr, $md)) $dureeH = (int)$md[1] * 80; // ~80h/mois pour trier
    $mode = $isLocal ? ($f['_mode'] ?? 'hybride') : 'hybride';
    $json_data[] = [
        'id'     => (string)($f['id'] ?? ''),
        'name'   => (string)($f['name'] ?? ''),
        'slug'   => (string)($f['slug'] ?? ''),
        'cat'    => $cat,
        'desc'   => mb_substr((string)($f['description'] ?? ''), 0, 220),
        'prix'   => $g ? (int)$g['individuel_online'] : $prix,
        'pres'   => $g ? (int)$g['individuel_pres'] : 0,
        'hyb'    => $g ? (int)$g['hybride'] : 0,
        'mode'   => $mode,
        'duree'  => (string)($f['_duree'] ?? ''),
        'dH'     => $dureeH,
        'colD'   => $colD,
        'colL'   => $colL,
        'local'  => $isLocal,
        'ins'    => $insUrl,
        'lien'   => $lien,
        'tdr'    => $tdrUrl,
        'niveaux'=> $f['_niveaux'] ?? [],
    ];
}
$json_encoded = json_encode($json_data, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);

/* ─── Helpers ───────────────────────────────────────── */
function fcfa(int $v): string { return number_format($v, 0, ',', ' ') . ' F CFA'; }
function r5(float $v): int { return (int)(round($v / 5000) * 5000); }
function grille_local(int $prix, int $prix_pres = 0): array {
    $ind_pres  = $prix_pres > 0 ? $prix_pres : r5($prix * 10 / 7);
    $g35_pres  = r5($ind_pres * 0.70);
    $g610_pres = r5($ind_pres * 0.55);
    $g10p_pres = r5($ind_pres * 0.45);
    return [
        'elearning_online'  => r5($prix * 0.5),
        'individuel_online' => $prix,
        'individuel_pres'   => $ind_pres,
        'hybride'           => r5(($prix + $ind_pres) / 2),
        'groupe_3_5_online' => r5($g35_pres  * 0.70),
        'groupe_3_5_pres'   => $g35_pres,
        'groupe_6_10_online'=> r5($g610_pres * 0.70),
        'groupe_6_10_pres'  => $g610_pres,
        'groupe_10p_online' => r5($g10p_pres * 0.70),
        'groupe_10p_pres'   => $g10p_pres,
    ];
}
function build_url(array $params): string {
    $base = [];
    if (!empty($_GET['cat']))   $base['cat']   = $_GET['cat'];
    if (!empty($_GET['q']))     $base['q']     = $_GET['q'];
    if (!empty($_GET['mode']))  $base['mode']  = $_GET['mode'];
    if (!empty($_GET['prix']))  $base['prix']  = $_GET['prix'];
    if (!empty($_GET['duree'])) $base['duree'] = $_GET['duree'];
    foreach ($params as $k => $v) {
        if ($v !== '' && $v !== null) $base[$k] = $v; else unset($base[$k]);
    }
    $qs = http_build_query($base);
    return '/catalogue-formations.php' . ($qs ? '?' . $qs : '');
}
function preinsc_url(string $name, string $cat, string $slug = '', int $prix = 0): string {
    $params = ['catalogue_nom' => $name, 'domaine' => $cat];
    if ($slug !== '') $params['formation_slug'] = $slug;
    if ($prix > 0)    $params['catalogue_prix']  = $prix;
    return 'https://ibig-eduform.com/preinscription-generale.php?' . http_build_query($params);
}
?>

<!-- ─── Hero ─────────────────────────────────────────── -->
<section class="cg-hero">
  <div class="cg-hero-inner">
    <span class="cg-badge">🎓 IBIG EDUFORM — Catalogue officiel des formations</span>
    <?php if ($api_ok && $total_all > 0): ?>
      <h1 class="cg-h1"><span class="cg-gold"><?= $total_all ?> formations</span><br>en ligne &amp; présentiel — espace OHADA</h1>
    <?php else: ?>
      <h1 class="cg-h1">Catalogue des formations<br><span class="cg-gold">IBIG EDUFORM</span></h1>
    <?php endif; ?>
    <p class="cg-sub">Formations accessibles individuellement, en groupe ou en intra-entreprise — pour particuliers et organisations partout dans l'espace OHADA.</p>
    <?php if ($api_ok && $total_all > 0): ?>
    <div class="cg-stats">
      <div class="cg-stat"><strong><?= $total_all ?></strong><span>Formations</span></div>
      <div class="cg-stat"><strong><?= count($active_cats) ?></strong><span>Domaines</span></div>
      <div class="cg-stat"><strong>17</strong><span>Pays OHADA</span></div>
      <div class="cg-stat"><strong>Présentiel · En ligne · Hybride</strong><span>3 modalités</span></div>
    </div>
    <?php endif; ?>
  </div>
</section>

<?php if ($api_ok): ?>
<!-- ─── Filtres (JS-driven, sans rechargement) ─────── -->
<div class="cg-filters-wrap">
  <div class="cg-fi">

    <!-- Recherche + toggle vue -->
    <div class="cg-top-bar">
      <div class="cg-search-form" role="search">
        <div class="cg-search-box">
          <svg class="cg-sico" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2"><circle cx="8.5" cy="8.5" r="5.5"/><path d="m13.5 13.5 3 3"/></svg>
          <input type="text" id="cgSearch" value="<?= h($q_raw) ?>"
            placeholder="Rechercher une formation, un domaine…" class="cg-si" autocomplete="off"
            oninput="cgDebounce()" onkeydown="if(event.key==='Escape')cgClearSearch()">
          <button type="button" class="cg-sbtn" onclick="cgApply()">Rechercher</button>
          <button type="button" id="cgResetAll" class="cg-reset" onclick="cgClear()" style="<?= ($q_raw||$cat_sel||$mode_sel||$prix_sel||$duree_sel||isset($_GET['niveau']))?'':'display:none' ?>">✕ Effacer</button>
        </div>
      </div>
      <!-- Toggle Grille / Liste -->
      <div class="cg-view-toggle" role="group" aria-label="Mode d'affichage">
        <button type="button" class="cg-vbtn active" id="btnGrid" title="Vue grille" onclick="setView('grid')">
          <svg viewBox="0 0 16 16" fill="currentColor"><rect x="1" y="1" width="6" height="6" rx="1"/><rect x="9" y="1" width="6" height="6" rx="1"/><rect x="1" y="9" width="6" height="6" rx="1"/><rect x="9" y="9" width="6" height="6" rx="1"/></svg>
        </button>
        <button type="button" class="cg-vbtn" id="btnList" title="Vue liste" onclick="setView('list')">
          <svg viewBox="0 0 16 16" fill="currentColor"><rect x="1" y="2" width="14" height="2" rx="1"/><rect x="1" y="7" width="14" height="2" rx="1"/><rect x="1" y="12" width="14" height="2" rx="1"/></svg>
        </button>
      </div>
    </div>

    <!-- Onglets catégories (JS-driven) -->
    <nav class="cg-tabs" id="cgTabs" aria-label="Filtrer par domaine">
      <button type="button" class="cg-tab<?= !$cat_sel ? ' on' : '' ?>" data-cat="" onclick="cgSetCat('')">
        Tous <span class="cg-n"><?= $total_all ?></span>
      </button>
      <?php foreach ($active_cats as $cat):
        [$col] = cat_col($cat, $CAT_COLORS);
        $isOn  = $cat_sel === $cat;
      ?>
      <button type="button"
        class="cg-tab<?= $isOn ? ' on' : '' ?>"
        data-cat="<?= h($cat) ?>" data-col="<?= h($col) ?>"
        onclick="cgSetCat('<?= addslashes(h($cat)) ?>')"
        <?= $isOn ? "style=\"background:{$col};color:#fff;border-color:{$col}\"" : '' ?>>
        <?= h($cat) ?> <span class="cg-n"><?= $cats_count[$cat] ?></span>
      </button>
      <?php endforeach; ?>
    </nav>

    <!-- Filtres avancés (JS-driven) -->
    <div class="cg-adv-filters">
      <div class="cg-af-group">
        <label class="cg-af-lbl" for="cgMode">🖥️ Mode</label>
        <select id="cgMode" class="cg-af-sel" onchange="cgApply()">
          <option value="">Tous les modes</option>
          <option value="en_ligne"   <?= $mode_sel==='en_ligne'  ?'selected':''?>>💻 En ligne</option>
          <option value="hybride"    <?= $mode_sel==='hybride'   ?'selected':''?>>🔀 Hybride</option>
          <option value="presentiel" <?= $mode_sel==='presentiel'?'selected':''?>>🏛️ Présentiel</option>
        </select>
      </div>
      <div class="cg-af-group">
        <label class="cg-af-lbl" for="cgPrix">💰 Budget</label>
        <select id="cgPrix" class="cg-af-sel" onchange="cgApply()">
          <option value="">Tous les budgets</option>
          <option value="200a250" <?= $prix_sel==='200a250'?'selected':''?>>200 000 – 250 000 F</option>
          <option value="250a300" <?= $prix_sel==='250a300'?'selected':''?>>250 000 – 300 000 F</option>
          <option value="plus300" <?= $prix_sel==='plus300'?'selected':''?>>Plus de 300 000 F</option>
        </select>
      </div>
      <div class="cg-af-group">
        <label class="cg-af-lbl" for="cgDuree">⏱️ Durée</label>
        <select id="cgDuree" class="cg-af-sel" onchange="cgApply()">
          <option value="">Toutes durées</option>
          <option value="court"  <?= $duree_sel==='court' ?'selected':''?>>Court (≤ 20h)</option>
          <option value="moyen"  <?= $duree_sel==='moyen' ?'selected':''?>>Moyen (21 – 40h)</option>
          <option value="long"   <?= $duree_sel==='long'  ?'selected':''?>>Long (+ de 40h)</option>
        </select>
      </div>
      <div class="cg-af-group">
        <label class="cg-af-lbl" for="cgNiveau">🎯 Niveau</label>
        <select id="cgNiveau" class="cg-af-sel" onchange="cgApply()">
          <option value="">Tous les niveaux</option>
          <option value="debutant">🟢 Débutant</option>
          <option value="intermediaire">🔵 Intermédiaire</option>
          <option value="expert">🔴 Expert</option>
        </select>
      </div>
      <div class="cg-af-group" id="cgSortGroup">
        <label class="cg-af-lbl" for="cgSort">↕️ Trier</label>
        <select id="cgSort" class="cg-af-sel" onchange="cgApply()">
          <option value="">Par défaut</option>
          <option value="az">A → Z</option>
          <option value="za">Z → A</option>
          <option value="prix_asc">Prix croissant</option>
          <option value="prix_desc">Prix décroissant</option>
        </select>
      </div>
    </div>

    <p class="cg-rbar" id="cgCount">
      <strong><?= number_format($total_all, 0, ',', ' ') ?></strong> formations
    </p>
  </div>
</div>
<?php endif; ?>

<!-- ─── Contenu ──────────────────────────────────────── -->
<main class="cg-main">
<div class="cg-inner">

<?php if (!$api_ok): ?>
  <div class="cg-pending">
    <div style="font-size:3rem;margin-bottom:16px">⏳</div>
    <h2>Catalogue en cours de chargement</h2>
    <p>Notre catalogue complet sera disponible très prochainement.<br>
       Consultez dès maintenant nos <a href="/formations.php">programmes actuels</a> ou contactez-nous.</p>
    <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap;margin-top:20px">
      <a href="/formations.php" class="cg-btn-p">Programmes actuels</a>
      <a href="/besoin-formation.php" class="cg-btn-s">Formation sur mesure</a>
    </div>
  </div>
<?php else: ?>

  <!-- Données formations (moteur JS) -->
  <script>window._cgData=<?= $json_encoded ?>;</script>

  <!-- Conteneur JS switchable -->
  <div class="cg-container" id="cgResults" role="list" aria-live="polite">
    <div class="cg-loading">
      <span class="cg-spin"></span> Chargement des formations…
    </div>
  </div>

  <!-- Pagination JS -->
  <nav class="cg-pag" id="cgPag" aria-label="Pages"></nav>

  <!-- ═══ BANDEAU COMPARATEUR PREMIUM ═══ -->
  <div id="cmpBar">
    <div class="cmpb-inner">
      <div class="cmpb-left">
        <span class="cmpb-icon">⚖️</span>
        <span class="cmpb-label">Comparer</span>
        <span class="cmpb-count" id="cmpCount">0/4</span>
      </div>
      <div id="cmpSlots" class="cmpb-slots"></div>
      <div class="cmpb-actions">
        <button class="cmpb-btn-go" onclick="showCompare()">Lancer la comparaison <span>→</span></button>
        <button class="cmpb-btn-clear" onclick="clearCompare()" title="Vider">✕</button>
      </div>
    </div>
  </div>

  <!-- ═══ MODAL COMPARATEUR PREMIUM ═══ -->
  <div id="cmpModal" class="cmp-overlay" onclick="if(event.target===this)closeCmp()">
    <div class="cmp-panel">
      <div class="cmp-hdr">
        <div class="cmp-hdr-left">
          <span class="cmp-hdr-icon">⚖️</span>
          <div>
            <div class="cmp-hdr-title">Comparaison de formations</div>
            <div class="cmp-hdr-sub">Analysez et choisissez la formation qui vous correspond</div>
          </div>
        </div>
        <button class="cmp-close" onclick="closeCmp()">✕</button>
      </div>
      <div id="cmpBody" class="cmp-body"></div>
    </div>
  </div>

<?php endif; ?>

  <!-- CTA bas -->
  <div class="cg-cta-bloc">
    <h2>Une formation sur mesure ?</h2>
    <p>Nous adaptons toute formation à votre contexte, vos objectifs et votre budget — pour toute organisation dans l'espace OHADA.</p>
    <div class="cg-cta-btns">
      <a href="/besoin-formation.php" class="cg-btn-cta-p">Demander un devis</a>
      <a href="https://wa.me/2250778882592?text=Bonjour%20IBIG%20EDUFORM%2C%20je%20voudrais%20des%20informations%20sur%20vos%20formations" target="_blank" rel="noopener" class="cg-btn-cta-w">💬 WhatsApp</a>
    </div>
  </div>

</div>
</main>

<!-- ─── Schema.org ───────────────────────────────────── -->
<script type="application/ld+json">
{"@context":"https://schema.org","@type":"CollectionPage","name":"Catalogue des formations IBIG EDUFORM","url":"<?= $ogUrl ?>","provider":{"@type":"EducationalOrganization","name":"IBIG EDUFORM","url":"https://ibig-eduform.com"},"numberOfItems":<?= (int)$total_all ?>,"inLanguage":"fr"}
</script>

<!-- ─── JS Comparateur Premium ───────────────────────────────── -->
<script>
var cmpItems = [];
var MAX_CMP = 4;

document.addEventListener('click', function(e){
  var btn = e.target.closest('.cg-btn-cmp');
  if (!btn) return;
  var d = null;
  try { d = JSON.parse(btn.getAttribute('data-cmp')); } catch(ex){}
  if (d) addCompare(d);
});

function fcfaJs(v){ return v > 0 ? new Intl.NumberFormat('fr-FR').format(v)+' F' : '—'; }
function esc2(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

function removeCompare(id){
  cmpItems = cmpItems.filter(function(i){ return i.id !== id; });
  renderCmpBar(); syncBtns();
}
function addCompare(f){
  var idx = cmpItems.findIndex(function(i){ return i.id === f.id; });
  if (idx >= 0){ cmpItems.splice(idx,1); }
  else {
    if (cmpItems.length >= MAX_CMP){
      showToast('Maximum '+MAX_CMP+' formations à comparer'); return;
    }
    cmpItems.push(f);
  }
  renderCmpBar(); syncBtns();
}
function syncBtns(){
  document.querySelectorAll('.cg-btn-cmp').forEach(function(btn){
    var d = null; try{ d = JSON.parse(btn.getAttribute('data-cmp')); }catch(e){}
    if(d) btn.classList.toggle('active', cmpItems.some(function(i){ return i.id===d.id; }));
  });
}
function renderCmpBar(){
  var bar = document.getElementById('cmpBar');
  var slots = document.getElementById('cmpSlots');
  var cnt = document.getElementById('cmpCount');
  if (!cmpItems.length){ bar.classList.remove('visible'); return; }
  bar.classList.add('visible');
  cnt.textContent = cmpItems.length+'/'+MAX_CMP;
  slots.innerHTML = cmpItems.map(function(f){
    var short = f.name.length > 28 ? f.name.substring(0,27)+'…' : f.name;
    return '<div class="cmpb-slot" style="border-left:3px solid '+esc2(f.col)+'">'
      + '<span class="cmpb-slot-name">'+esc2(short)+'</span>'
      + '<button class="cmpb-slot-rm" onclick="removeCompare(\''+f.id+'\');event.stopPropagation()" aria-label="Retirer">✕</button>'
      + '</div>';
  }).join('');
}
function closeCmp(){
  var m = document.getElementById('cmpModal');
  m.classList.remove('open');
  setTimeout(function(){ m.style.display='none'; }, 280);
}
function showCompare(){
  if (cmpItems.length < 2){ showToast('Sélectionnez au moins 2 formations'); return; }
  buildCmpBody();
  var m = document.getElementById('cmpModal');
  m.style.display='flex';
  requestAnimationFrame(function(){ m.classList.add('open'); });
}
function clearCompare(){
  cmpItems=[]; renderCmpBar(); syncBtns();
}

/* ── Score qualité-prix (0-100) ── */
function scoreOf(f){
  if (!f.prix || f.prix <= 0) return 0;
  var hParFranc = (f.dH||0) / f.prix * 100000; // heures pour 100k
  var score = Math.min(100, Math.round(hParFranc * 28));
  return score;
}

/* ── Construction du body comparateur ── */
function buildCmpBody(){
  var items = cmpItems;
  var n = items.length;

  /* calculs pour badges best */
  var minPrix  = Math.min.apply(null, items.filter(function(f){return f.prix>0;}).map(function(f){return f.prix;}));
  var maxDh    = Math.max.apply(null, items.map(function(f){return f.dH||0;}));
  var scores   = items.map(scoreOf);
  var maxScore = Math.max.apply(null, scores);
  var bestScore = scores.indexOf(maxScore);

  var colW = Math.floor(100/n);

  /* ── cartes en-tête ── */
  var cardsHtml = '<div class="cmp-cards-row">';
  items.forEach(function(f, i){
    var isBest = (i === bestScore && maxScore > 0);
    cardsHtml += '<div class="cmp-card-hdr" style="--fc:'+f.col+'">'
      + (isBest ? '<div class="cmp-best-badge">⭐ Meilleur rapport</div>' : '')
      + '<div class="cmp-card-cat">'+esc2(f.cat)+'</div>'
      + '<div class="cmp-card-name">'+esc2(f.name)+'</div>'
      + (f.desc ? '<div class="cmp-card-desc">'+esc2(f.desc)+'</div>' : '')
      + '<div class="cmp-card-score-wrap">'
      + '  <div class="cmp-score-label">Score IBIG</div>'
      + '  <div class="cmp-score-bar-wrap"><div class="cmp-score-bar" style="width:'+scores[i]+'%;background:'+f.col+'"></div></div>'
      + '  <div class="cmp-score-val">'+scores[i]+'/100</div>'
      + '</div>'
      + '</div>';
  });
  cardsHtml += '</div>';

  /* ── lignes de comparaison ── */
  function row(icon, label, valFn, modeFn){
    var vals = items.map(valFn);
    var html = '<div class="cmp-row"><div class="cmp-row-lbl"><span class="cmp-row-icon">'+icon+'</span>'+label+'</div><div class="cmp-row-vals">';
    vals.forEach(function(v,i){
      var cls = modeFn ? modeFn(v, vals, i) : '';
      html += '<div class="cmp-row-val '+cls+'">'+v+'</div>';
    });
    html += '</div></div>';
    return html;
  }

  function bestLow(v, all, i){ /* meilleur = plus bas prix */
    var nums = all.map(function(x){ return parseFloat(String(x).replace(/\D/g,''))||0; });
    var mn = Math.min.apply(null, nums.filter(function(x){return x>0;}));
    return (nums[i]===mn && mn>0) ? 'best' : '';
  }
  function bestHigh(v, all, i){ /* meilleur = plus haute valeur */
    var nums = all.map(function(x){ return parseFloat(String(x).replace(/\D/g,''))||0; });
    var mx = Math.max.apply(null, nums);
    return (nums[i]===mx && mx>0) ? 'best' : '';
  }

  /* barre de prix visuelle */
  function prixBar(f, field){
    var v = field==='prix'?f.prix:(field==='pres'?f.pres:f.hybride);
    if (!v||v<=0) return '<span class="cmp-na">—</span>';
    var allVals = items.map(function(x){ return field==='prix'?x.prix:(field==='pres'?x.pres:x.hybride); }).filter(function(x){return x>0;});
    var mx = Math.max.apply(null,allVals)||1;
    var mn = Math.min.apply(null,allVals)||1;
    var pct = Math.round(v/mx*100);
    var isMin = (v===mn);
    return '<div class="cmp-prix-wrap">'
      + '<span class="cmp-prix-val '+(isMin?'cmp-prix-min':'')+'">'+fcfaJs(v)+'</span>'
      + '<div class="cmp-prix-bar-bg"><div class="cmp-prix-bar-fill" style="width:'+pct+'%;opacity:'+(isMin?.9:.55)+'"></div></div>'
      + (isMin ? '<span class="cmp-prix-tag">Moins cher</span>' : '')
      + '</div>';
  }

  /* durée visuelle */
  function dureeBar(f){
    if (!f.dH||f.dH<=0) return f.duree||'<span class="cmp-na">—</span>';
    var mx = Math.max.apply(null,items.map(function(x){return x.dH||0;}))||1;
    var pct = Math.round(f.dH/mx*100);
    var isMax = (f.dH===mx);
    return '<div class="cmp-dur-wrap">'
      + '<span class="cmp-dur-val '+(isMax?'cmp-dur-max':'')+'">'+esc2(f.duree||f.dH+'h')+'</span>'
      + '<div class="cmp-dur-bar-bg"><div class="cmp-dur-bar-fill" style="width:'+pct+'%"></div></div>'
      + (isMax ? '<span class="cmp-dur-tag">Le plus complet</span>' : '')
      + '</div>';
  }

  /* mode badge */
  function modeBadge(f){
    var m = f.mode||'hybride';
    var map = {en_ligne:'💻 En ligne', presentiel:'🏛️ Présentiel', hybride:'🔀 Hybride'};
    return '<span class="cmp-mode-badge cmp-mode-'+m+'">'+(map[m]||m)+'</span>';
  }

  var rowsHtml = ''
    + '<div class="cmp-section-title">💰 Tarification</div>'
    + row('💻','En ligne', function(f){ return prixBar(f,'prix'); })
    + row('🏛️','Présentiel', function(f){ return prixBar(f,'pres'); })
    + row('🔀','Hybride', function(f){ return prixBar(f,'hybride'); })
    + '<div class="cmp-section-title">⏱️ Durée & Format</div>'
    + row('📅','Volume horaire', function(f){ return dureeBar(f); })
    + row('🎓','Modalité', function(f){ return modeBadge(f); })
    + '<div class="cmp-section-title">📚 Domaine</div>'
    + row('🏷️','Catégorie', function(f){ return '<span class="cmp-cat-chip" style="background:'+f.col+'22;color:'+f.col+';border:1px solid '+f.col+'44">'+esc2(f.cat)+'</span>'; });

  /* ── actions ── */
  var actionsHtml = '<div class="cmp-actions-row">';
  items.forEach(function(f, i){
    var isBest = (i === bestScore && maxScore > 0);
    actionsHtml += '<div class="cmp-action-col">'
      + '<a href="'+esc2(f.ins)+'" class="cmp-btn-inscrire" style="background:'+f.col+'">'
      + (isBest ? '⭐ ' : '') + 'S\'inscrire'
      + '</a>'
      + '<a href="'+esc2(f.lien)+'" class="cmp-btn-fiche">Voir la fiche →</a>'
      + '</div>';
  });
  actionsHtml += '</div>';

  document.getElementById('cmpBody').innerHTML = cardsHtml + rowsHtml + actionsHtml;
}

/* ── Toast notification ── */
function showToast(msg){
  var t = document.getElementById('cmpToast');
  if(!t){ t=document.createElement('div'); t.id='cmpToast'; document.body.appendChild(t); }
  t.textContent=msg; t.classList.add('show');
  clearTimeout(t._tm); t._tm=setTimeout(function(){ t.classList.remove('show'); }, 2400);
}
</script>

<!-- ─── Moteur JS filtre/rendu ────────────────────────── -->
<script>
(function(){
  var ALL = window._cgData || [];
  var PER = 24;
  var vm = 'grid';
  var cur = { q:'', cat:'', mode:'', prix:'', duree:'', niveau:'', sort:'az', page:1 };
  var debTimer = null;

  /* ── Helpers ── */
  function fcfaJs(v){ return v > 0 ? new Intl.NumberFormat('fr-FR').format(v) + ' F CFA' : '—'; }
  function esc(s){ return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

  /* ── Filtre ── */
  function matches(f){
    if (cur.cat && f.cat !== cur.cat) return false;
    if (cur.mode){
      var fm = (f.mode||'hybride').toLowerCase();
      /* hybride = disponible en ligne ET présentiel → inclus dans les 2 filtres */
      if (cur.mode === 'en_ligne'   && fm === 'presentiel') return false;
      if (cur.mode === 'presentiel' && fm === 'en_ligne')   return false;
      if (cur.mode === 'hybride'    && fm !== 'hybride')    return false;
    }
    if (cur.prix){
      var p = f.prix||0;
      if (cur.prix === '200a250' && !(p >= 200000 && p < 250000)) return false;
      if (cur.prix === '250a300' && !(p >= 250000 && p < 300000)) return false;
      if (cur.prix === 'plus300' && !(p >= 300000))               return false;
    }
    if (cur.duree){
      var h = f.dH||0;
      if (cur.duree === 'court'  && !(h > 0 && h <= 20))  return false;
      if (cur.duree === 'moyen'  && !(h >= 21 && h <= 40)) return false;
      if (cur.duree === 'long'   && !(h > 40))             return false;
    }
    if (cur.niveau){
      var hasNiv = false;
      if (f.niveaux && f.niveaux.length > 0) {
        for (var ni=0; ni<f.niveaux.length; ni++) {
          if (f.niveaux[ni].n === cur.niveau) { hasNiv = true; break; }
        }
      }
      if (!hasNiv) return false;
    }
    if (cur.q){
      var needle = cur.q.toLowerCase();
      var hay = (f.name + ' ' + f.cat + ' ' + f.desc).toLowerCase();
      if (hay.indexOf(needle) < 0) return false;
    }
    return true;
  }

  /* ── Tri ── */
  function sortFn(a, b){
    if (cur.sort === 'za')       return a.name < b.name ? 1 : -1;
    if (cur.sort === 'prix_asc') return (a.prix||0) - (b.prix||0);
    if (cur.sort === 'prix_desc')return (b.prix||0) - (a.prix||0);
    return a.name > b.name ? 1 : -1; // az default
  }

  /* ── Share bar commune ── */
  function cgShareBar(f){
    var url  = encodeURIComponent('https://ibig-eduform.com/formation-detail.php?slug='+f.slug);
    var txt  = encodeURIComponent('Découvrez cette formation : '+f.name+' — IBIG EDUFORM');
    var wa   = 'https://wa.me/?text='+encodeURIComponent('Découvrez cette formation : '+f.name+' — IBIG EDUFORM\nhttps://ibig-eduform.com/formation-detail.php?slug='+f.slug);
    var fb   = 'https://www.facebook.com/sharer/sharer.php?u='+url;
    var li   = 'https://www.linkedin.com/sharing/share-offsite/?url='+url;
    var tw   = 'https://twitter.com/intent/tweet?text='+txt+'&url='+url;
    var link = 'https://ibig-eduform.com/formation-detail.php?slug='+f.slug;
    return '<div class="cg-share-bar">'
      +'<span class="cg-share-label">Partager :</span>'
      +'<a href="'+wa+'" target="_blank" rel="noopener" class="cg-sh cg-sh-wa" title="WhatsApp"><svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg></a>'
      +'<a href="'+fb+'" target="_blank" rel="noopener" class="cg-sh cg-sh-fb" title="Facebook"><svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg></a>'
      +'<a href="'+li+'" target="_blank" rel="noopener" class="cg-sh cg-sh-li" title="LinkedIn"><svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg></a>'
      +'<a href="'+tw+'" target="_blank" rel="noopener" class="cg-sh cg-sh-tw" title="X / Twitter"><svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.745l7.73-8.835L1.254 2.25H8.08l4.264 5.638 5.9-5.638zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg></a>'
      +'<button type="button" class="cg-sh cg-sh-cp" title="Copier le lien" onclick="cgCopy(this,\''+link.replace(/'/g,"\\'")+'\')" >📋</button>'
      +'</div>';
  }

  /* ── Bouton TDR : modal lead capture pour TOUTES les formations ── */
  function tdrBtn(f) {
    return '<button type="button" class="cg-btn-tdr" data-tdr-local="1"'
      + ' data-tdr-slug="' + esc(f.slug) + '"'
      + ' data-tdr-titre="' + esc(f.name) + '"'
      + ' title="Télécharger le TDR (programme complet PDF)">📄 TDR</button>';
  }

  /* ── Rendu carte grille ── */
  function cardGrid(f){
    var modeLabel = f.mode === 'en_ligne' ? '💻 En ligne' : (f.mode === 'presentiel' ? '🏛️ Présentiel' : '🔀 En ligne + Présentiel');
    if (!f.local) modeLabel = '🔀 En ligne · Présentiel · Hybride';
    var nivLabels = {debutant:'Débutant',intermediaire:'Intermédiaire',expert:'Expert'};
    /* Niveau actif par défaut : premier niveau disponible */
    var aN = f.niveaux && f.niveaux.length > 0 ? f.niveaux[0] : null;
    var tPrix = aN && aN.ol > 0 ? aN.ol : f.prix;
    var tPres = aN && aN.pr > 0 ? aN.pr : f.pres;
    var tHyb  = aN && aN.hy > 0 ? aN.hy : (f.hyb || Math.round((tPrix + tPres) / 10000) * 5000);
    var tDur  = aN && aN.h > 0 ? aN.h + 'H' : (f.duree || '');
    var durHtml = tDur ? '<div class="cg-dur-inline"><span class="cg-dur-badge cg-niv-dur">⏱️ '+esc(tDur)+'</span></div>' : '';
    var niveauxHtml = '';
    if (f.niveaux && f.niveaux.length > 0) {
      niveauxHtml = '<div class="cg-niveaux">';
      for (var ni=0; ni<f.niveaux.length; ni++) {
        var nv = f.niveaux[ni];
        var actCls = ni === 0 ? ' cg-niv-active' : '';
        niveauxHtml += '<span class="cg-niv cg-niv-'+nv.n+actCls+'" onclick="cgPickNiv(this)"'
          +' data-h="'+nv.h+'" data-ol="'+nv.ol+'" data-pr="'+nv.pr+'" data-hy="'+nv.hy+'"'
          +' title="Cliquez pour voir les tarifs '+nivLabels[nv.n]+'">'+nivLabels[nv.n]+'</span>';
      }
      niveauxHtml += '</div>';
    }
    var prixHtml = '';
    if (tPrix > 0 || (f.niveaux && f.niveaux.length > 0)) {
      prixHtml = '<button class="cg-tarif-toggle" onclick="this.classList.toggle(\'open\');this.nextElementSibling.classList.toggle(\'visible\')" aria-expanded="false">'
        + '💰 Tarifs &amp; Modalités <span class="cg-tarif-arrow">▾</span></button>'
        + '<div class="cg-table-wrap cg-tarif-zone"><table class="cg-tbl"><thead>'
        + '<tr><th class="cg-th-m">Modalité</th><th class="cg-th-p">💻 En ligne</th><th class="cg-th-p">🏛️ Présentiel</th></tr>'
        + '</thead><tbody>'
        + '<tr><td>👤 Individuel (en direct)</td><td data-niv-cell="ol">'+fcfaJs(tPrix)+'</td><td data-niv-cell="pr">'+fcfaJs(tPres)+'</td></tr>'
        + '<tr><td>🔀 Hybride (En ligne et en présentiel)</td><td colspan="2" style="text-align:center" data-niv-cell="hy">'+fcfaJs(tHyb)+'</td></tr>'
        + '<tr class="cg-intra-row"><td colspan="3">👥 Formation groupe &amp; intra-entreprise — <strong>Sur devis</strong></td></tr>'
        + '</tbody></table>'
        + '<p class="cg-tbl-note">* Tarifs indicatifs. Contactez-nous pour un devis personnalisé selon votre profil et le nombre de participants.</p>'
        + '</div>';
    } else {
      prixHtml = '<p class="cg-sur-devis">Tarif sur devis</p>';
    }
    var cmpData = JSON.stringify({id:f.id,name:f.name,cat:f.cat,prix:f.prix,pres:f.pres,hybride:f.hyb,lien:f.lien,ins:f.ins,duree:f.duree||'',dH:f.dH||0,mode:f.mode||'hybride',desc:f.desc?f.desc.substring(0,160):'',col:f.colD||'#0a1733'});
    return '<article class="cg-card cg-grid-item" role="listitem">'
      + '<div class="cg-card-banner" style="background:linear-gradient(135deg,'+esc(f.colD)+' 0%,'+esc(f.colD)+'bb 100%);">'
      + '<span class="cg-dom-badge">'+esc(f.cat)+'</span>'
      + '<span class="cg-mod-badge">'+esc(modeLabel)+'</span>'
      + '</div>'
      + '<div class="cg-card-body">'
      + '<h3 class="cg-card-name">'+esc(f.name)+'</h3>'
      + durHtml
      + niveauxHtml
      + (f.desc ? '<p class="cg-card-pitch">'+esc(f.desc.substring(0,160))+(f.desc.length>160?'…':'')+'</p>' : '')
      + prixHtml
      + '<div class="cg-card-ctas">'
      + '<a href="'+esc(f.ins)+'" class="cg-btn-p" style="background:'+esc(f.colD)+'">✍️ S\'inscrire</a>'
      + '<a href="'+esc(f.lien)+'" class="cg-btn-s">Voir →</a>'
      + tdrBtn(f)
      + '<button type="button" class="cg-btn-cmp" data-cmp=\''+cmpData.replace(/'/g,'&#39;')+'\' title="Ajouter à la comparaison">⚖️ Comparer</button>'
      + '</div>'
      + cgShareBar(f)
      + '</div></article>';
  }

  /* ── Rendu carte liste ── */
  function cardList(f){
    var modeLabel = f.mode === 'en_ligne' ? '💻 En ligne' : (f.mode === 'presentiel' ? '🏛️ Présentiel' : '🔀 En ligne + Présentiel');
    if (!f.local) modeLabel = '🔀 En ligne · Présentiel · Hybride';
    var nivLabels2 = {debutant:'Débutant',intermediaire:'Intermédiaire',expert:'Expert'};
    var aN2 = f.niveaux && f.niveaux.length > 0 ? f.niveaux[0] : null;
    var tPrix2 = aN2 && aN2.ol > 0 ? aN2.ol : f.prix;
    var tPres2 = aN2 && aN2.pr > 0 ? aN2.pr : f.pres;
    var tHyb2  = aN2 && aN2.hy > 0 ? aN2.hy : f.hyb;
    var tDur2  = aN2 && aN2.h > 0 ? aN2.h + 'H' : (f.duree || '');
    var durHtml = tDur2 ? '<span class="cg-dur-badge cg-dur-list cg-niv-dur">⏱️ '+esc(tDur2)+'</span>' : '';
    var niveauxListHtml = '';
    if (f.niveaux && f.niveaux.length > 0) {
      niveauxListHtml = '<div class="cg-niveaux cg-niveaux-list">';
      for (var ni2=0; ni2<f.niveaux.length; ni2++) {
        var nv2 = f.niveaux[ni2];
        var actCls2 = ni2 === 0 ? ' cg-niv-active' : '';
        niveauxListHtml += '<span class="cg-niv cg-niv-'+nv2.n+actCls2+'" onclick="cgPickNiv(this)"'
          +' data-h="'+nv2.h+'" data-ol="'+nv2.ol+'" data-pr="'+nv2.pr+'" data-hy="'+nv2.hy+'"'
          +' title="Cliquez pour voir les tarifs '+nivLabels2[nv2.n]+'">'+nivLabels2[nv2.n]+'</span>';
      }
      niveauxListHtml += '</div>';
    }
    var pricesHtml = '';
    if (tPrix2 > 0 || (f.niveaux && f.niveaux.length > 0)) {
      pricesHtml = '<div class="cg-list-prices cg-tarif-zone">'
        + '<div class="cg-lp-item"><span class="cg-lp-lbl">💻 En ligne</span><strong class="cg-lp-val" style="color:'+esc(f.colD)+'" data-niv-cell="ol">'+fcfaJs(tPrix2)+'</strong><span class="cg-lp-sub">individuel</span></div>'
        + '<div class="cg-lp-sep"></div>'
        + '<div class="cg-lp-item"><span class="cg-lp-lbl">🔀 Hybride</span><strong class="cg-lp-val" style="color:'+esc(f.colD)+'" data-niv-cell="hy">'+fcfaJs(tHyb2)+'</strong><span class="cg-lp-sub">En ligne et présentiel</span></div>'
        + '<div class="cg-lp-sep"></div>'
        + '<div class="cg-lp-item"><span class="cg-lp-lbl">🏛️ Présentiel</span><strong class="cg-lp-val" style="color:'+esc(f.colD)+'" data-niv-cell="pr">'+fcfaJs(tPres2)+'</strong><span class="cg-lp-sub">individuel</span></div>'
        + '</div>';
    }
    return '<div class="cg-list-item" role="listitem">'
      + '<div class="cg-list-color" style="background:'+esc(f.colD)+'"></div>'
      + '<div class="cg-list-body">'
      + '<div class="cg-list-top">'
      + '<span class="cg-list-cat" style="background:'+esc(f.colL)+';color:'+esc(f.colD)+'">'+esc(f.cat)+'</span>'
      + '<span class="cg-list-mod">'+esc(modeLabel)+'</span>'
      + durHtml
      + '</div>'
      + '<h3 class="cg-list-name">'+esc(f.name)+'</h3>'
      + niveauxListHtml
      + (f.desc ? '<p class="cg-list-desc">'+esc(f.desc.substring(0,180))+(f.desc.length>180?'…':'')+'</p>' : '')
      + '</div>'
      + pricesHtml
      + '<div class="cg-list-ctas">'
      + '<a href="'+esc(f.ins)+'" class="cg-btn-p" style="background:'+esc(f.colD)+'">S\'inscrire</a>'
      + '<a href="'+esc(f.lien)+'" class="cg-btn-s">Voir →</a>'
      + tdrBtn(f)
      + cgShareBar(f)
      + '</div></div>';
  }

  /* ── Rendu principal ── */
  function render(){
    var filtered = ALL.filter(matches).sort(sortFn);
    var total = filtered.length;
    var pages = Math.max(1, Math.ceil(total / PER));
    if (cur.page > pages) cur.page = 1;
    var slice = filtered.slice((cur.page-1)*PER, cur.page*PER);

    var cntEl = document.getElementById('cgCount');
    if (cntEl) cntEl.textContent = total + ' formation' + (total > 1 ? 's' : '') + ' trouvée' + (total > 1 ? 's' : '');

    var res = document.getElementById('cgResults');
    if (!res) return;
    if (total === 0){
      res.innerHTML = '<div class="cg-empty" style="grid-column:1/-1"><p style="font-size:2.4rem">🔍</p><strong>Aucune formation trouvée</strong><p><a href="#" onclick="cgClear();return false">Voir tout le catalogue</a></p></div>';
    } else {
      var listMode = vm === 'list';
      res.className = listMode ? 'cg-container list-mode' : 'cg-container';
      res.innerHTML = slice.map(listMode ? cardList : cardGrid).join('');
    }

    /* Pagination */
    var pag = document.getElementById('cgPag');
    if (pag){
      if (pages <= 1){ pag.innerHTML=''; return; }
      var h = '';
      if (cur.page > 1) h += '<a href="#" class="cg-pb" onclick="cgGoPage('+(cur.page-1)+');return false">← Précédent</a>';
      var s = Math.max(1, cur.page-2), e = Math.min(pages, cur.page+2);
      if (s > 1){ h += '<a href="#" class="cg-pb" onclick="cgGoPage(1);return false">1</a>'; if (s>2) h+='<span class="cg-dots">…</span>'; }
      for (var p=s;p<=e;p++) h += '<a href="#" class="cg-pb'+(p===cur.page?' cur':'')+'" onclick="cgGoPage('+p+');return false">'+p+'</a>';
      if (e < pages){ if (e<pages-1) h+='<span class="cg-dots">…</span>'; h += '<a href="#" class="cg-pb" onclick="cgGoPage('+pages+');return false">'+pages+'</a>'; }
      if (cur.page < pages) h += '<a href="#" class="cg-pb" onclick="cgGoPage('+(cur.page+1)+');return false">Suivant →</a>';
      pag.innerHTML = h;
    }

    /* URL state */
    try {
      var params = {};
      if (cur.q)     params.q     = cur.q;
      if (cur.cat)   params.cat   = cur.cat;
      if (cur.mode)  params.mode  = cur.mode;
      if (cur.prix)  params.prix  = cur.prix;
      if (cur.duree) params.duree = cur.duree;
      if (cur.sort && cur.sort !== 'az') params.sort = cur.sort;
      if (cur.page > 1) params.page = cur.page;
      var qs = Object.keys(params).map(function(k){ return k+'='+encodeURIComponent(params[k]); }).join('&');
      history.replaceState(null,'', location.pathname + (qs ? '?'+qs : ''));
    } catch(e){}
  }

  /* ── API publique ── */
  window.cgSetCat = function(cat){
    cur.cat = cat; cur.page = 1;
    document.querySelectorAll('.cg-tab').forEach(function(t){ t.classList.toggle('on', t.getAttribute('data-cat') === cat); });
    render();
  };
  window.cgApply = function(){
    var mSel = document.getElementById('cgMode');
    var pSel = document.getElementById('cgPrix');
    var dSel = document.getElementById('cgDuree');
    var nSel = document.getElementById('cgNiveau');
    var sSel = document.getElementById('cgSort');
    cur.mode  = mSel  ? mSel.value  : '';
    cur.prix  = pSel  ? pSel.value  : '';
    cur.duree = dSel  ? dSel.value  : '';
    cur.niveau= nSel  ? nSel.value  : '';
    cur.sort  = sSel  ? sSel.value  : 'az';
    cur.page = 1;
    render();
  };
  window.cgDebounce = function(){
    clearTimeout(debTimer);
    debTimer = setTimeout(function(){
      var si = document.getElementById('cgSearch');
      cur.q = si ? si.value.trim() : '';
      cur.page = 1;
      render();
    }, 280);
  };
  window.cgClearSearch = function(){
    var si = document.getElementById('cgSearch');
    if (si) si.value = '';
    cur.q = ''; cur.page = 1;
    render();
  };
  window.cgClear = function(){
    var si = document.getElementById('cgSearch');
    if (si) si.value = '';
    var mSel = document.getElementById('cgMode');
    var pSel = document.getElementById('cgPrix');
    var dSel = document.getElementById('cgDuree');
    var nSel = document.getElementById('cgNiveau');
    var sSel = document.getElementById('cgSort');
    if (mSel) mSel.value = '';
    if (pSel) pSel.value = '';
    if (dSel) dSel.value = '';
    if (nSel) nSel.value = '';
    if (sSel) sSel.value = 'az';
    cur = { q:'', cat:'', mode:'', prix:'', duree:'', niveau:'', sort:'az', page:1 };
    document.querySelectorAll('.cg-tab').forEach(function(t){ t.classList.toggle('on', !t.getAttribute('data-cat')); });
    render();
  };
  /* Copier lien partage */
  window.cgCopy = function(btn, url){
    try {
      navigator.clipboard.writeText(url).then(function(){
        btn.classList.add('copied'); btn.textContent='✅';
        setTimeout(function(){ btn.classList.remove('copied'); btn.textContent='📋'; }, 2000);
      });
    } catch(e){
      var ta=document.createElement('textarea'); ta.value=url; document.body.appendChild(ta); ta.select(); document.execCommand('copy'); document.body.removeChild(ta);
      btn.textContent='✅'; setTimeout(function(){ btn.textContent='📋'; }, 2000);
    }
  };

  /* ── Sélection de niveau : mise à jour des tarifs et durée ── */
  window.cgPickNiv = function(pill){
    var card = pill.closest('.cg-card, .cg-list-item');
    if (!card) return;
    card.querySelectorAll('.cg-niv').forEach(function(p){ p.classList.remove('cg-niv-active'); });
    pill.classList.add('cg-niv-active');
    var h  = parseInt(pill.getAttribute('data-h')  || 0, 10);
    var ol = parseInt(pill.getAttribute('data-ol') || 0, 10);
    var pr = parseInt(pill.getAttribute('data-pr') || 0, 10);
    var hy = parseInt(pill.getAttribute('data-hy') || 0, 10);
    if (hy <= 0 && ol > 0 && pr > 0) hy = Math.round((ol + pr) / 10000) * 5000;
    var zone = card.querySelector('.cg-tarif-zone');
    if (zone) {
      zone.querySelectorAll('[data-niv-cell]').forEach(function(cell){
        var t = cell.getAttribute('data-niv-cell');
        if (t === 'ol' && ol > 0) cell.textContent = fcfaJs(ol);
        if (t === 'pr' && pr > 0) cell.textContent = fcfaJs(pr);
        if (t === 'hy' && hy > 0) cell.textContent = fcfaJs(hy);
      });
    }
    if (h > 0) {
      card.querySelectorAll('.cg-niv-dur').forEach(function(b){ b.textContent = '⏱️ ' + h + 'H'; });
    }
  };

  window.cgGoPage = function(p){
    cur.page = p;
    render();
    var res = document.getElementById('cgResults');
    if (res) res.scrollIntoView({behavior:'smooth',block:'start'});
  };
  window.setView = function(v){
    vm = v;
    var btnGrid = document.getElementById('btnGrid');
    var btnList = document.getElementById('btnList');
    if (btnGrid) btnGrid.classList.toggle('active', v === 'grid');
    if (btnList) btnList.classList.toggle('active', v === 'list');
    try { localStorage.setItem('cg_view', v); } catch(e){}
    render();
  };

  /* ── Init ── */
  document.addEventListener('DOMContentLoaded', function(){
    /* Lire URL params */
    var sp = new URLSearchParams(location.search);
    if (sp.get('q'))     cur.q     = sp.get('q');
    if (sp.get('cat'))   cur.cat   = sp.get('cat');
    if (sp.get('mode'))  cur.mode  = sp.get('mode');
    if (sp.get('prix'))  cur.prix  = sp.get('prix');
    if (sp.get('duree'))  cur.duree  = sp.get('duree');
    if (sp.get('niveau')) cur.niveau = sp.get('niveau');
    if (sp.get('sort'))   cur.sort   = sp.get('sort');
    if (sp.get('page'))  cur.page  = parseInt(sp.get('page'),10)||1;
    /* Sync UI */
    var si = document.getElementById('cgSearch'); if (si && cur.q) si.value = cur.q;
    var mSel = document.getElementById('cgMode'); if (mSel && cur.mode) mSel.value = cur.mode;
    var pSel = document.getElementById('cgPrix'); if (pSel && cur.prix) pSel.value = cur.prix;
    var dSel = document.getElementById('cgDuree'); if (dSel && cur.duree) dSel.value = cur.duree;
    var nSel = document.getElementById('cgNiveau'); if (nSel && cur.niveau) nSel.value = cur.niveau;
    var sSel = document.getElementById('cgSort'); if (sSel) sSel.value = cur.sort;
    document.querySelectorAll('.cg-tab').forEach(function(t){ t.classList.toggle('on', t.getAttribute('data-cat') === cur.cat); });
    /* Vue mémorisée */
    try { var sv = localStorage.getItem('cg_view'); if (sv) vm = sv; } catch(e){}
    var btnGrid = document.getElementById('btnGrid');
    var btnList = document.getElementById('btnList');
    if (btnGrid) btnGrid.classList.toggle('active', vm === 'grid');
    if (btnList) btnList.classList.toggle('active', vm === 'list');
    render();

    /* ── Modal TDR lead capture ── */
    var tdrModal     = document.getElementById('tdr-lead-modal');
    var tdrOverlay   = document.getElementById('tdr-lead-overlay');
    var tdrForm      = document.getElementById('tdr-lead-form');
    var tdrSlugInput = document.getElementById('tdr-lead-slug');
    var tdrTitreEl   = document.getElementById('tdr-lead-titre');
    var tdrMsg       = document.getElementById('tdr-lead-msg');
    var tdrSubmitBtn = document.getElementById('tdr-lead-submit');
    var tdrDialEl    = document.getElementById('tdr-dial');
    var tdrPaysEl    = document.getElementById('tdr-pays');

    function openTdrModal(slug, titre) {
      tdrForm.reset();
      tdrSlugInput.value = slug;
      tdrTitreEl.textContent = titre;
      tdrForm.querySelector('[name="formation_titre"]') && (tdrForm.querySelector('[name="formation_titre"]').value = titre);
      tdrMsg.textContent = '';
      tdrDialEl.textContent = '+__';
      tdrSubmitBtn.disabled = false;
      tdrSubmitBtn.textContent = '📄 Télécharger mon TDR';
      tdrModal.hidden = false;
      tdrModal.querySelector('[name="prenom"]').focus();
    }
    function closeTdrModal() { tdrModal.hidden = true; }

    /* Indicatif téléphonique auto selon pays */
    tdrPaysEl.addEventListener('change', function() {
      var opt = tdrPaysEl.options[tdrPaysEl.selectedIndex];
      var dial = opt.getAttribute('data-dial') || '+__';
      tdrDialEl.textContent = dial;
      var wa = tdrForm.querySelector('[name="whatsapp"]');
      if (wa && wa.value === '') wa.placeholder = dial.replace('+','') + ' 00 00 00 00';
    });

    if (tdrOverlay) tdrOverlay.addEventListener('click', closeTdrModal);
    document.getElementById('tdr-lead-close').addEventListener('click', closeTdrModal);
    document.addEventListener('keydown', function(e){ if (e.key === 'Escape' && !tdrModal.hidden) closeTdrModal(); });

    tdrForm.addEventListener('submit', function(e) {
      e.preventDefault();
      tdrMsg.textContent = '';
      var prenom   = tdrForm.querySelector('[name="prenom"]').value.trim();
      var nom      = tdrForm.querySelector('[name="nom"]').value.trim();
      var email    = tdrForm.querySelector('[name="email"]').value.trim();
      var whatsapp = tdrForm.querySelector('[name="whatsapp"]').value.trim();
      var pays     = tdrPaysEl.value;
      var mode     = tdrForm.querySelector('[name="mode_souhaite"]:checked');
      var fmt      = tdrForm.querySelector('[name="format_souhaite"]:checked');
      var slug     = tdrSlugInput.value;

      if (!prenom || !nom) { tdrMsg.textContent = 'Veuillez indiquer votre prénom et nom.'; return; }
      if (!email) { tdrMsg.textContent = 'Veuillez indiquer votre adresse email.'; return; }
      if (!pays)  { tdrMsg.textContent = 'Veuillez sélectionner votre pays.'; return; }
      if (!mode)  { tdrMsg.textContent = 'Veuillez choisir un mode de formation.'; return; }
      if (!fmt)   { tdrMsg.textContent = 'Veuillez choisir un format.'; return; }

      tdrSubmitBtn.disabled = true;
      tdrSubmitBtn.textContent = 'Envoi en cours…';

      var fd = new FormData(tdrForm);
      fd.set('formation_titre', tdrTitreEl.textContent);

      /* Préfixer le WhatsApp avec l'indicatif si pas déjà là */
      if (whatsapp && !whatsapp.startsWith('+')) {
        var dial = tdrDialEl.textContent.replace('__','').trim();
        if (dial && dial !== '+') fd.set('whatsapp', dial + whatsapp.replace(/^0/, ''));
      }

      fetch('/lead.php', { method: 'POST', body: fd })
        .then(function(r){ return r.json(); })
        .then(function(data) {
          if (data.ok) {
            closeTdrModal();
            window.open('/tdr-local-pdf.php?slug=' + encodeURIComponent(slug), '_blank');
          } else {
            tdrMsg.textContent = data.error || 'Une erreur est survenue.';
            tdrSubmitBtn.disabled = false;
            tdrSubmitBtn.textContent = '📄 Télécharger mon TDR';
          }
        })
        .catch(function() {
          tdrMsg.textContent = 'Erreur réseau. Réessayez.';
          tdrSubmitBtn.disabled = false;
          tdrSubmitBtn.textContent = '📄 Télécharger mon TDR';
        });
    });

    /* Délégation : clic sur bouton TDR local */
    document.addEventListener('click', function(e) {
      var btn = e.target.closest('[data-tdr-local]');
      if (!btn) return;
      e.preventDefault();
      openTdrModal(btn.dataset.tdrSlug, btn.dataset.tdrTitre);
    });
  });
})();
</script>

<!-- Modal capture lead TDR — version enrichie -->
<div id="tdr-lead-modal" hidden role="dialog" aria-modal="true" aria-labelledby="tdr-modal-heading">
  <div class="tdr-modal-bg" id="tdr-lead-overlay"></div>
  <div class="tdr-modal-box">
    <button id="tdr-lead-close" class="tdr-modal-close" aria-label="Fermer">✕</button>
    <div class="tdr-modal-icon">📄</div>
    <h2 id="tdr-modal-heading" class="tdr-modal-h">Télécharger le programme</h2>
    <p class="tdr-modal-sub">Formation : <strong id="tdr-lead-titre"></strong></p>
    <form id="tdr-lead-form" novalidate autocomplete="on">
      <input type="hidden" id="tdr-lead-slug">
      <input type="hidden" name="type" value="tdr">
      <input type="hidden" id="tdr-csrf-token" name="csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES) ?>">

      <!-- Identité -->
      <div class="tdr-row">
        <div class="tdr-field">
          <label for="tdr-prenom">Prénom *</label>
          <input id="tdr-prenom" name="prenom" type="text" placeholder="Kouamé" required autocomplete="given-name">
        </div>
        <div class="tdr-field">
          <label for="tdr-nom">Nom *</label>
          <input id="tdr-nom" name="nom" type="text" placeholder="KOUAKOU" required autocomplete="family-name">
        </div>
      </div>

      <!-- Pays -->
      <div class="tdr-field">
        <label for="tdr-pays">Pays *</label>
        <select id="tdr-pays" name="pays" required>
          <option value="">— Sélectionnez votre pays —</option>
          <optgroup label="🌍 Espace OHADA">
            <option value="Bénin" data-dial="+229">🇧🇯 Bénin (+229)</option>
            <option value="Burkina Faso" data-dial="+226">🇧🇫 Burkina Faso (+226)</option>
            <option value="Cameroun" data-dial="+237">🇨🇲 Cameroun (+237)</option>
            <option value="Centrafrique" data-dial="+236">🇨🇫 Centrafrique (+236)</option>
            <option value="Comores" data-dial="+269">🇰🇲 Comores (+269)</option>
            <option value="Congo" data-dial="+242">🇨🇬 Congo (+242)</option>
            <option value="Côte d'Ivoire" data-dial="+225">🇨🇮 Côte d'Ivoire (+225)</option>
            <option value="Gabon" data-dial="+241">🇬🇦 Gabon (+241)</option>
            <option value="Guinée" data-dial="+224">🇬🇳 Guinée (+224)</option>
            <option value="Guinée Équatoriale" data-dial="+240">🇬🇶 Guinée Équatoriale (+240)</option>
            <option value="Guinée-Bissau" data-dial="+245">🇬🇼 Guinée-Bissau (+245)</option>
            <option value="Mali" data-dial="+223">🇲🇱 Mali (+223)</option>
            <option value="Niger" data-dial="+227">🇳🇪 Niger (+227)</option>
            <option value="RD Congo" data-dial="+243">🇨🇩 RD Congo (+243)</option>
            <option value="Sénégal" data-dial="+221">🇸🇳 Sénégal (+221)</option>
            <option value="Tchad" data-dial="+235">🇹🇩 Tchad (+235)</option>
            <option value="Togo" data-dial="+228">🇹🇬 Togo (+228)</option>
          </optgroup>
          <optgroup label="🌍 Afrique francophone">
            <option value="Algérie" data-dial="+213">🇩🇿 Algérie (+213)</option>
            <option value="Burundi" data-dial="+257">🇧🇮 Burundi (+257)</option>
            <option value="Djibouti" data-dial="+253">🇩🇯 Djibouti (+253)</option>
            <option value="Madagascar" data-dial="+261">🇲🇬 Madagascar (+261)</option>
            <option value="Maroc" data-dial="+212">🇲🇦 Maroc (+212)</option>
            <option value="Mauritanie" data-dial="+222">🇲🇷 Mauritanie (+222)</option>
            <option value="Rwanda" data-dial="+250">🇷🇼 Rwanda (+250)</option>
            <option value="Tunisie" data-dial="+216">🇹🇳 Tunisie (+216)</option>
          </optgroup>
          <optgroup label="🌍 Reste de l'Afrique">
            <option value="Angola" data-dial="+244">🇦🇴 Angola (+244)</option>
            <option value="Ghana" data-dial="+233">🇬🇭 Ghana (+233)</option>
            <option value="Kenya" data-dial="+254">🇰🇪 Kenya (+254)</option>
            <option value="Nigeria" data-dial="+234">🇳🇬 Nigeria (+234)</option>
          </optgroup>
          <optgroup label="🌍 Europe / Diaspora">
            <option value="Belgique" data-dial="+32">🇧🇪 Belgique (+32)</option>
            <option value="Canada" data-dial="+1">🇨🇦 Canada (+1)</option>
            <option value="France" data-dial="+33">🇫🇷 France (+33)</option>
            <option value="Suisse" data-dial="+41">🇨🇭 Suisse (+41)</option>
          </optgroup>
        </select>
      </div>

      <!-- Email -->
      <div class="tdr-field">
        <label for="tdr-email">Adresse email *</label>
        <input id="tdr-email" name="email" type="email" placeholder="vous@exemple.com" required autocomplete="email">
      </div>

      <!-- WhatsApp avec indicatif -->
      <div class="tdr-field">
        <label for="tdr-whatsapp">WhatsApp (avec indicatif)</label>
        <div class="tdr-phone-wrap">
          <span id="tdr-dial" class="tdr-dial">+__</span>
          <input id="tdr-whatsapp" name="whatsapp" type="tel" placeholder="07 00 00 00 00" autocomplete="tel">
        </div>
        <small class="tdr-hint">L'indicatif se remplit automatiquement selon le pays.</small>
      </div>

      <!-- Mode souhaité -->
      <div class="tdr-field">
        <label>Mode de formation souhaité *</label>
        <div class="tdr-radios">
          <label class="tdr-radio"><input type="radio" name="mode_souhaite" value="en_ligne" required><span>💻 En ligne</span></label>
          <label class="tdr-radio"><input type="radio" name="mode_souhaite" value="presentiel"><span>🏛️ Présentiel</span></label>
          <label class="tdr-radio"><input type="radio" name="mode_souhaite" value="hybride"><span>🔀 Hybride</span></label>
        </div>
      </div>

      <!-- Format -->
      <div class="tdr-field">
        <label>Format *</label>
        <div class="tdr-radios tdr-radios-4">
          <label class="tdr-radio"><input type="radio" name="format_souhaite" value="individuel" required><span>👤 Individuel</span></label>
          <label class="tdr-radio"><input type="radio" name="format_souhaite" value="groupe_2_5"><span>👥 Groupe 2-5</span></label>
          <label class="tdr-radio"><input type="radio" name="format_souhaite" value="groupe_6_10"><span>👥 Groupe 6-10</span></label>
          <label class="tdr-radio"><input type="radio" name="format_souhaite" value="intra"><span>🏢 Intra-entreprise</span></label>
        </div>
      </div>

      <!-- Entreprise + Poste (optionnels) -->
      <div class="tdr-row">
        <div class="tdr-field">
          <label for="tdr-entreprise">Entreprise <span class="tdr-opt">(optionnel)</span></label>
          <input id="tdr-entreprise" name="entreprise" type="text" placeholder="Nom de votre organisation" autocomplete="organization">
        </div>
        <div class="tdr-field">
          <label for="tdr-poste">Poste / Fonction <span class="tdr-opt">(optionnel)</span></label>
          <input id="tdr-poste" name="poste" type="text" placeholder="Ex : Directeur RH" autocomplete="organization-title">
        </div>
      </div>

      <p id="tdr-lead-msg" class="tdr-msg" role="alert"></p>
      <button id="tdr-lead-submit" type="submit" class="tdr-modal-btn">📄 Télécharger mon TDR</button>
      <p class="tdr-privacy">🔒 Données confidentielles. Non revendues. Utilisées uniquement pour le suivi de votre demande de formation.</p>
    </form>
  </div>
</div>


<?php require_once __DIR__ . '/partials/footer.php'; ?>
