<?php
if (($_GET['k'] ?? '') !== 'ibig-bulk-2026') { http_response_code(403); exit('Forbidden'); }
header('Content-Type: text/plain; charset=utf-8');

require_once __DIR__ . '/core/config.php';
require_once __DIR__ . '/core/database.php';
$pdo = Database::connect();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function slugify(string $t): string {
    $t = mb_strtolower(trim($t), 'UTF-8');
    $map = ['à'=>'a','â'=>'a','ä'=>'a','á'=>'a','ã'=>'a','å'=>'a',
            'è'=>'e','é'=>'e','ê'=>'e','ë'=>'e','ě'=>'e',
            'î'=>'i','ï'=>'i','í'=>'i','ì'=>'i',
            'ô'=>'o','ö'=>'o','ó'=>'o','ò'=>'o','õ'=>'o',
            'û'=>'u','ü'=>'u','ú'=>'u','ù'=>'u',
            'ç'=>'c','ñ'=>'n','œ'=>'oe','æ'=>'ae'];
    $t = strtr($t, $map);
    $t = preg_replace('/[^a-z0-9]+/', '-', $t);
    return trim((string)$t, '-');
}

// ─── CATALOGUE COMPLET DES ⭐ PAR DOMAINE ────────────────────────────────────
// Format : [titre, domaine, type_certificat, duree_heures, tarif_en_ligne, tarif_presentiel]
// tarif_en_ligne >= 200 000 FCFA | tarif_presentiel >= 250 000 FCFA
$formations = [
  // ── COMPTABILITÉ & FINANCE ───────────────────────────────────────────────
  ['Comptabilité générale SYSCOHADA révisé','Comptabilité & Finance','Certificat professionnel',30,225000,280000],
  ['Comptabilité analytique et contrôle de gestion','Comptabilité & Finance','Certificat professionnel',30,225000,280000],
  ['Fiscalité avancée des entreprises OHADA','Comptabilité & Finance','Certificat professionnel',25,225000,280000],
  ['Consolidation des comptes IFRS','Comptabilité & Finance','Certificat professionnel',35,280000,340000],
  ['Comptabilité des ONG et associations','Comptabilité & Finance','Certificat professionnel',25,225000,280000],
  ['Audit interne et contrôle interne','Comptabilité & Finance','Certificat professionnel',30,250000,310000],
  ['Analyse financière avancée','Comptabilité & Finance','Certificat professionnel',25,225000,280000],
  ['Normes IFRS pour PME','Comptabilité & Finance','Certificat professionnel',30,250000,310000],
  ['Gestion de la TVA et déclarations fiscales','Comptabilité & Finance','Certificat professionnel',20,225000,275000],
  ['Contrôle de gestion budgétaire','Comptabilité & Finance','Certificat professionnel',25,225000,280000],
  ['Reporting financier et tableaux de bord','Comptabilité & Finance','Certificat professionnel',25,225000,280000],
  ['Finance islamique et banque participative','Comptabilité & Finance','Certificat professionnel',30,250000,310000],
  ['Gestion de portefeuille et investissements','Comptabilité & Finance','Certificat professionnel',30,250000,310000],
  ['Microfinance et gestion des IMF','Comptabilité & Finance','Certificat professionnel',30,225000,280000],
  ['Évaluation d\'entreprises et transactions M&A','Comptabilité & Finance','Certificat professionnel',35,280000,340000],

  // ── GRH & MANAGEMENT RH ─────────────────────────────────────────────────
  ['Recrutement et sélection du personnel','GRH','Certificat professionnel',25,225000,280000],
  ['Gestion de la paie avancée','GRH','Certificat professionnel',25,225000,280000],
  ['GPEC — Gestion prévisionnelle des emplois et compétences','GRH','Certificat professionnel',30,225000,280000],
  ['Droit du travail OHADA et droit social','GRH','Certificat professionnel',25,225000,280000],
  ['Formation de formateurs','GRH','Certificat professionnel',30,225000,280000],
  ['Évaluation des performances et compétences','GRH','Certificat professionnel',25,225000,280000],
  ['Rémunération et politique salariale','GRH','Certificat professionnel',25,225000,280000],
  ['People Analytics — RH par la data','GRH','Certificat professionnel',25,250000,310000],
  ['Santé et sécurité au travail','GRH','Certificat professionnel',25,225000,280000],
  ['Digitalisation des RH et SIRH','GRH','Certificat professionnel',25,225000,280000],
  ['Management interculturel en Afrique','GRH','Certificat professionnel',25,225000,280000],
  ['Audit RH et tableau de bord social','GRH','Certificat professionnel',25,225000,280000],

  // ── MARKETING & VENTE ───────────────────────────────────────────────────
  ['Marketing digital complet','Gestion Commerciale & Marketing','Certificat professionnel',30,225000,280000],
  ['Growth Hacking et acquisition client','Gestion Commerciale & Marketing','Certificat professionnel',25,225000,280000],
  ['Stratégie commerciale B2B','Gestion Commerciale & Marketing','Certificat professionnel',25,225000,280000],
  ['Techniques de vente et négociation avancée','Gestion Commerciale & Marketing','Certificat professionnel',25,225000,280000],
  ['Marketing des réseaux sociaux','Gestion Commerciale & Marketing','Certificat professionnel',25,225000,280000],
  ['SEO et référencement naturel','Gestion Commerciale & Marketing','Certificat professionnel',25,225000,280000],
  ['CRM et gestion de la relation client','Gestion Commerciale & Marketing','Certificat professionnel',25,225000,280000],
  ['E-commerce et boutique en ligne','Gestion Commerciale & Marketing','Certificat professionnel',30,225000,280000],
  ['Marketing de contenu et storytelling','Gestion Commerciale & Marketing','Certificat professionnel',25,225000,280000],
  ['Étude de marché et analyse concurrentielle','Gestion Commerciale & Marketing','Certificat professionnel',25,225000,280000],
  ['Brand management et identité de marque','Gestion Commerciale & Marketing','Certificat professionnel',25,225000,280000],
  ['Prospection commerciale digitale','Gestion Commerciale & Marketing','Certificat professionnel',25,225000,280000],
  ['Inbound marketing et génération de leads','Gestion Commerciale & Marketing','Certificat professionnel',25,225000,280000],

  // ── MANAGEMENT & LEADERSHIP ─────────────────────────────────────────────
  ['Leadership et management d\'équipe','Management & Leadership','Certificat professionnel',25,225000,280000],
  ['Management de projet certifiant','Management & Leadership','Certificat professionnel',35,280000,340000],
  ['Méthodes agiles Scrum et Kanban','Management & Leadership','Certificat professionnel',25,225000,280000],
  ['Management stratégique d\'entreprise','Management & Leadership','Certificat professionnel',30,250000,310000],
  ['Conduite du changement organisationnel','Management & Leadership','Certificat professionnel',25,225000,280000],
  ['Intelligence émotionnelle pour managers','Management & Leadership','Certificat professionnel',25,225000,280000],
  ['Prise de décision et résolution de problèmes','Management & Leadership','Certificat professionnel',25,225000,280000],
  ['Gouvernance d\'entreprise et conseil d\'administration','Management & Leadership','Certificat professionnel',35,280000,340000],
  ['Lean management et amélioration continue','Management & Leadership','Certificat professionnel',25,225000,280000],
  ['Management par les OKR','Management & Leadership','Certificat professionnel',20,225000,275000],
  ['Communication managériale','Management & Leadership','Certificat professionnel',25,225000,280000],

  // ── IA & DIGITALISATION ─────────────────────────────────────────────────
  ['Intelligence artificielle pour les managers','IA & Digitalisation','Certificat professionnel',25,225000,280000],
  ['ChatGPT et LLM pour professionnels','IA & Digitalisation','Certificat professionnel',20,225000,275000],
  ['Automatisation des processus avec RPA','IA & Digitalisation','Certificat professionnel',25,225000,280000],
  ['Power BI et Business Intelligence','IA & Digitalisation','Certificat professionnel',30,225000,280000],
  ['Excel avancé et Power Query','IA & Digitalisation','Certificat professionnel',25,225000,280000],
  ['Transformation digitale des entreprises','IA & Digitalisation','Certificat professionnel',30,250000,310000],
  ['Data analyse et visualisation','IA & Digitalisation','Certificat professionnel',30,225000,280000],
  ['Python pour la data et l\'automatisation','IA & Digitalisation','Certificat professionnel',35,225000,280000],
  ['Cybersécurité pour non-techniciens','IA & Digitalisation','Certificat professionnel',25,225000,280000],
  ['No-code et outils sans programmation','IA & Digitalisation','Certificat professionnel',25,225000,280000],
  ['Microsoft 365 Teams SharePoint OneNote','IA & Digitalisation','Certificat professionnel',20,225000,275000],
  ['IA générative pour la création de contenu','IA & Digitalisation','Certificat professionnel',20,225000,275000],
  ['Google Workspace pour professionnels','IA & Digitalisation','Certificat professionnel',20,225000,275000],
  ['Gestion de projet avec outils digitaux','IA & Digitalisation','Certificat professionnel',25,225000,280000],
  ['ERP et logiciels de gestion SAP et Sage','IA & Digitalisation','Certificat professionnel',30,250000,310000],
  ['Cybersécurité et protection des données RGPD','IA & Digitalisation','Certificat professionnel',25,225000,280000],

  // ── DROIT & JURIDIQUE ───────────────────────────────────────────────────
  ['Droit des affaires OHADA','Droit & Juridique','Certificat professionnel',30,250000,310000],
  ['Droit des contrats et rédaction contractuelle','Droit & Juridique','Certificat professionnel',25,250000,310000],
  ['Compliance et conformité réglementaire','Droit & Juridique','Certificat professionnel',25,250000,310000],
  ['Contrats publics et marchés publics','Droit & Juridique','Certificat professionnel',25,250000,310000],
  ['Droit des sociétés création et gouvernance','Droit & Juridique','Certificat professionnel',25,250000,310000],
  ['Réglementation du travail en zone OHADA','Droit & Juridique','Certificat professionnel',25,250000,310000],
  ['Arbitrage commercial international','Droit & Juridique','Certificat professionnel',30,280000,340000],
  ['Droit douanier et commerce international','Droit & Juridique','Certificat professionnel',25,250000,310000],
  ['Protection des données personnelles RGPD Afrique','Droit & Juridique','Certificat professionnel',25,250000,310000],
  ['Droit de l\'urbanisme et foncier','Droit & Juridique','Certificat professionnel',25,250000,310000],

  // ── LOGISTIQUE & SUPPLY CHAIN ────────────────────────────────────────────
  ['Supply chain management avancé','Logistique & Supply Chain','Certificat professionnel',30,225000,280000],
  ['Gestion des stocks et entrepôts','Logistique & Supply Chain','Certificat professionnel',25,225000,280000],
  ['Transport et douane import et export','Logistique & Supply Chain','Certificat professionnel',25,225000,280000],
  ['Logistique portuaire et maritime','Logistique & Supply Chain','Certificat professionnel',30,250000,310000],
  ['Achat et procurement professionnel','Logistique & Supply Chain','Certificat professionnel',25,225000,280000],
  ['Gestion de la chaîne du froid','Logistique & Supply Chain','Certificat professionnel',25,225000,280000],
  ['Incoterms et commerce international','Logistique & Supply Chain','Certificat professionnel',25,225000,280000],
  ['Digitalisation de la supply chain','Logistique & Supply Chain','Certificat professionnel',25,225000,280000],
  ['Gestion des appels d\'offres','Logistique & Supply Chain','Certificat professionnel',25,225000,280000],

  // ── ENTREPRENEURIAT & INNOVATION ────────────────────────────────────────
  ['Création d\'entreprise et business plan','Entrepreneuriat','Certificat professionnel',25,225000,280000],
  ['Financement des startups et levée de fonds','Entrepreneuriat','Certificat professionnel',25,250000,310000],
  ['Marketing de lancement de produit','Entrepreneuriat','Certificat professionnel',25,225000,280000],
  ['Entrepreneuriat social et impact','Entrepreneuriat','Certificat professionnel',25,225000,280000],
  ['Gestion financière pour entrepreneurs','Entrepreneuriat','Certificat professionnel',25,225000,280000],
  ['Design thinking et innovation','Entrepreneuriat','Certificat professionnel',25,225000,280000],
  ['Pitch deck et présentation aux investisseurs','Entrepreneuriat','Certificat professionnel',20,225000,275000],
  ['E-commerce lancer et gérer sa boutique','Entrepreneuriat','Certificat professionnel',25,225000,280000],
  ['Personal branding et réputation professionnelle','Entrepreneuriat','Certificat professionnel',20,225000,275000],
  ['Entrepreneuriat agricole et agribusiness','Entrepreneuriat','Certificat professionnel',25,225000,280000],

  // ── QHSE & SÉCURITÉ ─────────────────────────────────────────────────────
  ['ISO 9001 Système de management qualité','QHSE','Certificat professionnel',30,250000,310000],
  ['ISO 45001 Santé et sécurité au travail','QHSE','Certificat professionnel',30,250000,310000],
  ['ISO 14001 Management environnemental','QHSE','Certificat professionnel',30,250000,310000],
  ['HACCP Sécurité alimentaire','QHSE','Certificat professionnel',25,250000,310000],
  ['Auditeur interne qualité','QHSE','Certificat professionnel',30,250000,310000],
  ['Gestion des risques professionnels','QHSE','Certificat professionnel',25,225000,280000],
  ['QHSE pour le BTP et chantiers','QHSE','Certificat professionnel',25,225000,280000],
  ['Lean Six Sigma Green Belt','QHSE','Certificat professionnel',40,280000,340000],
  ['Lean Six Sigma Yellow Belt','QHSE','Certificat professionnel',25,250000,310000],
  ['RSE et développement durable','QHSE','Certificat professionnel',25,225000,280000],
  ['Plan de continuité d\'activité PCA','QHSE','Certificat professionnel',25,225000,280000],

  // ── COMMUNICATION & SOFT SKILLS ─────────────────────────────────────────
  ['Prise de parole en public et éloquence','Communication Professionnelle','Certificat professionnel',25,225000,280000],
  ['Communication professionnelle écrite','Communication Professionnelle','Certificat professionnel',20,225000,275000],
  ['Rédaction administrative et rapports professionnels','Communication Professionnelle','Certificat professionnel',20,225000,275000],
  ['Négociation et persuasion professionnelle','Communication Professionnelle','Certificat professionnel',25,225000,280000],
  ['Intelligence émotionnelle au travail','Développement Personnel','Certificat professionnel',25,225000,280000],
  ['Gestion du stress et prévention du burnout','Développement Personnel','Certificat professionnel',20,225000,275000],
  ['Écriture pour les réseaux sociaux professionnels','Communication','Certificat professionnel',20,225000,275000],
  ['Techniques de coaching professionnel','Développement Personnel','Certificat professionnel',30,225000,280000],

  // ── BANQUE & ASSURANCE ──────────────────────────────────────────────────
  ['Analyse de crédit et risque bancaire','Banque & Assurance','Certificat professionnel',30,250000,310000],
  ['Produits d\'assurance vie et prévoyance','Banque & Assurance','Certificat professionnel',25,250000,310000],
  ['Bancassurance pratiques et vente','Banque & Assurance','Certificat professionnel',25,250000,310000],
  ['Conformité bancaire et lutte anti-blanchiment','Banque & Assurance','Certificat professionnel',25,250000,310000],
  ['Gestion des risques financiers','Banque & Assurance','Certificat professionnel',30,250000,310000],
  ['Mobile money et fintech en Afrique','Banque & Assurance','Certificat professionnel',25,250000,310000],
  ['Microfinance pour inclusion financière','Banque & Assurance','Certificat professionnel',25,250000,310000],
  ['Épargne et investissement pour particuliers','Banque & Assurance','Certificat professionnel',25,225000,280000],
  ['Financement de projets project finance','Banque & Assurance','Certificat professionnel',35,280000,340000],

  // ── AGRICULTURE & AGRIBUSINESS ──────────────────────────────────────────
  ['Agribusiness et chaîne de valeur agricole','Agriculture','Certificat professionnel',30,225000,280000],
  ['Gestion d\'exploitation agricole','Agriculture','Certificat professionnel',25,225000,280000],
  ['Agriculture biologique et certification','Agriculture','Certificat professionnel',25,225000,280000],
  ['Aquaculture et pisciculture commerciale','Agriculture','Certificat professionnel',30,225000,280000],
  ['Transformation agroalimentaire','Agriculture','Certificat professionnel',25,225000,280000],
  ['Financement agricole et crédit rural','Agriculture','Certificat professionnel',25,225000,280000],
  ['Maraîchage intensif et culture hors sol','Agriculture','Certificat professionnel',25,225000,280000],
  ['Gestion de coopératives agricoles','Agriculture','Certificat professionnel',25,225000,280000],
  ['Exportation de produits agricoles','Agriculture','Certificat professionnel',25,225000,280000],

  // ── BTP & IMMOBILIER ────────────────────────────────────────────────────
  ['Gestion de chantier et suivi de travaux','BTP & Construction','Certificat professionnel',30,250000,310000],
  ['Métrés et devis économie de la construction','BTP & Construction','Certificat professionnel',25,225000,280000],
  ['Droit de la construction et contrats de travaux','BTP & Construction','Certificat professionnel',25,250000,310000],
  ['Immobilier achat vente et gestion locative','BTP & Construction','Certificat professionnel',25,225000,280000],
  ['Management QHSE sur chantier','BTP & Construction','Certificat professionnel',25,225000,280000],
  ['AutoCAD pour dessinateurs','BTP & Construction','Certificat professionnel',30,225000,280000],
  ['BIM modélisation de l\'information du bâtiment','BTP & Construction','Certificat professionnel',30,250000,310000],
  ['Expertise immobilière et évaluation foncière','BTP & Construction','Certificat professionnel',25,250000,310000],
  ['Commande publique et marchés de travaux','BTP & Construction','Certificat professionnel',25,250000,310000],

  // ── SANTÉ & PHARMACIE ───────────────────────────────────────────────────
  ['Management hospitalier et clinique','Santé & Pharmacie','Certificat professionnel',35,250000,310000],
  ['Gestion pharmaceutique en officine','Santé & Pharmacie','Certificat professionnel',30,250000,310000],
  ['Santé publique et épidémiologie','Santé & Pharmacie','Certificat professionnel',30,250000,310000],
  ['Nutrition et diététique professionnelle','Santé & Pharmacie','Certificat professionnel',25,225000,280000],
  ['Management de la qualité en milieu de soins','Santé & Pharmacie','Certificat professionnel',25,250000,310000],

  // ── MINES, ÉNERGIE & PÉTROLE ────────────────────────────────────────────
  ['Gestion environnementale des mines','Mines, Énergie & Pétrole','Certificat professionnel',35,280000,340000],
  ['Santé sécurité en milieu minier','Mines, Énergie & Pétrole','Certificat professionnel',30,280000,340000],
  ['Négociation des contrats pétroliers','Mines, Énergie & Pétrole','Certificat professionnel',35,280000,340000],
  ['Énergies renouvelables solaire et éolien','Mines, Énergie & Pétrole','Certificat professionnel',30,250000,310000],
  ['Efficacité énergétique en entreprise','Mines, Énergie & Pétrole','Certificat professionnel',25,250000,310000],
  ['OHSE secteur pétrolier et gazier','Mines, Énergie & Pétrole','Certificat professionnel',35,280000,340000],
  ['Électricité industrielle et maintenance','Mines, Énergie & Pétrole','Certificat professionnel',30,250000,310000],

  // ── TOURISME & HÔTELLERIE ───────────────────────────────────────────────
  ['Management hôtelier','Tourisme & Hôtellerie','Certificat professionnel',25,225000,280000],
  ['Revenue management hôtelier','Tourisme & Hôtellerie','Certificat professionnel',25,225000,280000],
  ['Tourisme digital et e-réputation','Tourisme & Hôtellerie','Certificat professionnel',25,225000,280000],
  ['Agence de voyage création et gestion','Tourisme & Hôtellerie','Certificat professionnel',25,225000,280000],
  ['Gestion événementielle et congrès professionnels','Tourisme & Hôtellerie','Certificat professionnel',25,225000,280000],

  // ── INFOGRAPHIE & DESIGN ────────────────────────────────────────────────
  ['Adobe Photoshop professionnel','Infographie & Design','Certificat professionnel',30,225000,280000],
  ['Adobe Illustrator professionnel','Infographie & Design','Certificat professionnel',30,225000,280000],
  ['Canva Pro pour communication visuelle','Infographie & Design','Certificat professionnel',20,225000,275000],
  ['Identité visuelle et branding graphique','Infographie & Design','Certificat professionnel',25,225000,280000],
  ['Motion design et After Effects','Infographie & Design','Certificat professionnel',35,225000,280000],
  ['Montage vidéo professionnel','Infographie & Design','Certificat professionnel',30,225000,280000],
  ['Web design UI et UX','Infographie & Design','Certificat professionnel',35,225000,280000],

  // ── DIRECTION & ADMINISTRATION ──────────────────────────────────────────
  ['Gouvernance des organisations','Direction & Administration','Certificat professionnel',30,280000,340000],
  ['Secrétariat de direction et assistance exécutive','Direction & Administration','Certificat professionnel',25,225000,280000],
  ['Gestion administrative avancée','Direction & Administration','Certificat professionnel',25,225000,280000],
  ['Gestion des marchés publics','Direction & Administration','Certificat professionnel',30,250000,310000],
  ['Planification stratégique et pilotage de la performance','Direction & Administration','Certificat professionnel',30,250000,310000],
  ['Communication institutionnelle et relations publiques','Direction & Administration','Certificat professionnel',25,225000,280000],

  // ── ÉDUCATION & FORMATION ───────────────────────────────────────────────
  ['Ingénierie pédagogique concevoir une formation','Éducation & Formation','Certificat professionnel',30,225000,280000],
  ['E-learning et formation à distance','Éducation & Formation','Certificat professionnel',25,225000,280000],
  ['Gestion d\'établissement scolaire et universitaire','Éducation & Formation','Certificat professionnel',30,250000,310000],
  ['Formation de formateurs niveau avancé','Éducation & Formation','Certificat professionnel',30,225000,280000],
  ['Coaching professionnel certifié','Éducation & Formation','Certificat professionnel',35,250000,310000],

  // ── CRÉATION DE CONTENU & MÉDIAS ────────────────────────────────────────
  ['Créateur de contenu YouTube et TikTok','Création de Contenu','Certificat professionnel',25,225000,280000],
  ['Podcast professionnel création et monétisation','Création de Contenu','Certificat professionnel',20,225000,275000],
  ['Rédaction web et copywriting','Création de Contenu','Certificat professionnel',25,225000,280000],
  ['Community management professionnel','Création de Contenu','Certificat professionnel',25,225000,280000],
  ['Storytelling et narration visuelle','Création de Contenu','Certificat professionnel',20,225000,275000],
];

// ─── RÉCUPÉRER TOUS LES TITRES ET SLUGS EXISTANTS ────────────────────────
$existingTitres = $pdo->query("SELECT LOWER(TRIM(titre)) FROM formations")->fetchAll(PDO::FETCH_COLUMN);
$existingTitres = array_flip($existingTitres);

$existingSlugs  = $pdo->query("SELECT slug FROM formations")->fetchAll(PDO::FETCH_COLUMN);
$existingSlugs  = array_flip($existingSlugs);

// ─── INSERT ───────────────────────────────────────────────────────────────
$ins = $pdo->prepare("
    INSERT INTO formations
    (titre, slug, domaine, type_certificat, description, duree,
     mode, tarif_presentiel, tarif_en_ligne, tarif_hybride,
     frais_inscription, annee, statut, created_at, updated_at)
    VALUES
    (:titre, :slug, :domaine, :type_certificat, :description, :duree,
     'hybride', :tp, :tel, :th,
     50000, 0, 'active', NOW(), NOW())
");

$inserted = 0;
$skipped  = 0;
$skippedList = [];

foreach ($formations as $f) {
    [$titre, $domaine, $cert, $duree, $tel, $tp] = $f;
    $titreKey = strtolower(trim($titre));

    // Anti-doublon titre
    if (isset($existingTitres[$titreKey])) {
        $skipped++;
        $skippedList[] = $titre;
        continue;
    }

    // Slug unique
    $slug = slugify($titre);
    $base = $slug;
    $i = 1;
    while (isset($existingSlugs[$slug])) {
        $slug = $base . '-' . $i++;
    }

    $desc = 'Formation professionnelle certifiante IBIG EDUFORM — ' . $titre
          . '. Reconnue dans les 17 pays de l\'espace OHADA.';

    $ins->execute([
        ':titre'          => $titre,
        ':slug'           => $slug,
        ':domaine'        => $domaine,
        ':type_certificat'=> $cert,
        ':description'    => $desc,
        ':duree'          => $duree . 'h',
        ':tel'            => $tel,
        ':tp'             => $tp,
        ':th'             => (int)(($tel + $tp) / 2),
    ]);

    $existingTitres[$titreKey] = true;
    $existingSlugs[$slug]      = true;
    $inserted++;
}

// ─── RÉSULTAT ─────────────────────────────────────────────────────────────
$totalActif = (int)$pdo->query("SELECT COUNT(*) FROM formations WHERE statut='active' AND (annee IS NULL OR annee=0)")->fetchColumn();

echo "=== BULK INSERT — FORMATIONS CATALOGUE ===\n\n";
echo "Formations dans la liste    : " . count($formations) . "\n";
echo "Insérées (nouvelles)        : $inserted\n";
echo "Ignorées (doublons)         : $skipped\n";
echo "---\n";
echo "Total catalogue actif après : $totalActif\n";

if ($skipped > 0) {
    echo "\n--- Doublons ignorés ---\n";
    foreach ($skippedList as $t) echo "  - $t\n";
}

echo "\n✅ Script supprimé automatiquement.\n";
@unlink(__FILE__);
