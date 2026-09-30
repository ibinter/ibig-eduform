<?php
require_once __DIR__ . '/../core/bootstrap.php';
header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><title>Lot 3</title>
<style>body{font-family:sans-serif;max-width:900px;margin:40px auto;padding:0 20px}.ok{color:#16a34a;font-weight:bold}.err{color:#dc2626;font-weight:bold}.skip{color:#d97706}.row{padding:4px 0;border-bottom:1px solid #e5e7eb;font-size:.9rem}h2{margin-top:30px;color:#1e3a5f}</style>
</head><body>
<h1>Insertion formations — Lot 3 (34 formations)</h1>
<?php

$formations = [

    /* ── IMMOBILIER ── */
    [
        'titre'            => 'Négociateur Immobilier & Agent Commercial en Immobilier',
        'domaine'          => 'Immobilier',
        'description'      => 'Le marché immobilier ivoirien connaît une effervescence sans précédent, portée par l\'urbanisation rapide d\'Abidjan et des villes secondaires. Le Négociateur Immobilier est au cœur de cette dynamique : il prospecte les biens disponibles à la vente ou à la location, évalue leur valeur marchande, met en relation vendeurs et acheteurs, accompagne les parties à travers les étapes de la transaction (visites, offres, promesse de vente) et assure le suivi jusqu\'à la signature définitive. Cette formation couvre les techniques de prospection immobilière, l\'estimation des biens, le droit immobilier de base (régime foncier ivoirien, titre foncier, bail), les techniques de négociation et de closing, et les outils digitaux de marketing immobilier (annonces, visites virtuelles, réseaux sociaux). Adapté aux agents d\'agences immobilières, promoteurs et indépendants.',
        'duree'            => '4 jours (32h)',
        'mode'             => 'hybride',
        'tarif_en_ligne'   => 210000,
        'tarif_presentiel' => 260000,
        'slug'             => 'negociateur-immobilier-agent-commercial-immobilier',
    ],
    [
        'titre'            => 'Property Manager — Gestion Locative & Administration de Biens',
        'domaine'          => 'Immobilier',
        'description'      => 'Le Property Manager gère un portefeuille de biens immobiliers pour le compte de propriétaires investisseurs : mise en location, sélection des locataires, rédaction des baux, quittancement des loyers, gestion des travaux et de l\'entretien, relations de copropriété et optimisation du rendement locatif. Cette formation couvre les obligations légales du gestionnaire de biens (droit des baux, responsabilités, assurances), les techniques de sélection des locataires, la gestion comptable des mandats de gestion, la gestion des sinistres et des contentieux locatifs, et les outils logiciels de gestion locative. Idéal pour les professionnels de l\'immobilier, les sociétés de gestion et les propriétaires gérant plusieurs biens.',
        'duree'            => '3 jours (24h)',
        'mode'             => 'hybride',
        'tarif_en_ligne'   => 210000,
        'tarif_presentiel' => 260000,
        'slug'             => 'property-manager-gestion-locative-administration-biens',
    ],

    /* ── DROIT & JURIDIQUE ── */
    [
        'titre'            => 'Juriste d\'Entreprise Junior — Contrats, Conformité & Droit OHADA',
        'domaine'          => 'Droit & Juridique',
        'description'      => 'Le Juriste d\'Entreprise est le gardien de la sécurité juridique de l\'organisation : il rédige et analyse les contrats commerciaux, identifie les risques juridiques, assure la conformité réglementaire et accompagne les directions dans leurs décisions. Cette formation pratique couvre les fondamentaux du droit des contrats OHADA (formation, exécution, résiliation, responsabilité), la rédaction des principaux contrats d\'entreprise (vente, prestation de services, bail commercial, contrat de travail), la gestion des litiges (médiation, arbitrage CCJA, voies de recours), et la conformité aux réglementations sectorielle. Inclut des exercices intensifs de rédaction contractuelle sur des cas ivoiriens et de la zone UEMOA. Idéal pour les juristes débutants, assistants juridiques et managers souhaitant maîtriser les enjeux légaux de leurs activités.',
        'duree'            => '5 jours (40h)',
        'mode'             => 'hybride',
        'tarif_en_ligne'   => 230000,
        'tarif_presentiel' => 280000,
        'slug'             => 'juriste-entreprise-junior-contrats-conformite-droit-ohada',
    ],
    [
        'titre'            => 'Assistant Juridique & Clerc de Notaire / d\'Huissier',
        'domaine'          => 'Droit & Juridique',
        'description'      => 'L\'Assistant Juridique et le Clerc sont des profils indispensables dans les cabinets d\'avocats, études notariales et offices d\'huissiers : ils préparent les actes juridiques, constituent les dossiers judiciaires, assurent le suivi des procédures et gèrent la correspondance avec les juridictions et les clients. Cette formation couvre la rédaction d\'actes simples (contrats, procurations, constats), les procédures civiles et commerciales de base, la gestion du calendrier procédural (délais, prescriptions), la signification des actes d\'huissier, les formalités notariales courantes (authenticité, enregistrement, publication foncière) et l\'organisation d\'un cabinet juridique. Adapté aux débutants en droit souhaitant intégrer un cabinet ou une direction juridique en Côte d\'Ivoire.',
        'duree'            => '4 jours (32h)',
        'mode'             => 'hybride',
        'tarif_en_ligne'   => 210000,
        'tarif_presentiel' => 260000,
        'slug'             => 'assistant-juridique-clerc-notaire-huissier',
    ],
    [
        'titre'            => 'Auditeur Interne Junior — Méthodologie & Pratique de l\'Audit',
        'domaine'          => 'Audit & Contrôle',
        'description'      => 'L\'audit interne est une fonction de gouvernance indispensable dans les établissements financiers, les groupes industriels et les institutions publiques. L\'Auditeur Interne évalue l\'efficacité des contrôles internes, identifie les risques opérationnels et financiers, et formule des recommandations d\'amélioration. Cette formation couvre le cadre de référence international de l\'audit interne (normes IIA), la planification et la conduite d\'une mission d\'audit (programme de travail, tests, entretiens), la rédaction du rapport d\'audit, le suivi des recommandations et la cartographie des risques. Des exercices pratiques sur des cas de fraude, d\'erreurs comptables et de défaillances de processus permettent d\'acquérir une vision concrète du métier. Idéal pour les comptables et contrôleurs de gestion souhaitant se reconvertir vers l\'audit.',
        'duree'            => '4 jours (32h)',
        'mode'             => 'hybride',
        'tarif_en_ligne'   => 220000,
        'tarif_presentiel' => 270000,
        'slug'             => 'auditeur-interne-junior-methodologie-pratique-audit',
    ],

    /* ── ÉNERGIE & ENVIRONNEMENT ── */
    [
        'titre'            => 'Technicien en Énergies Renouvelables — Solaire Photovoltaïque',
        'domaine'          => 'Mines, Énergie & Pétrole',
        'description'      => 'L\'Afrique de l\'Ouest connaît un boom du solaire photovoltaïque, portée par les besoins en électrification rurale et la réduction des coûts des panneaux. Le Technicien en Énergies Renouvelables installe, configure et maintient les systèmes solaires photovoltaïques résidentiels et professionnels. Cette formation couvre les fondamentaux de l\'électricité solaire (rayonnement, cellules PV, onduleurs, batteries), le dimensionnement des installations (calcul de la puissance et de la capacité de stockage), la pose des panneaux et câblage électrique, la mise en service et le paramétrage des systèmes, et la maintenance préventive et curative. Inclut des travaux pratiques sur des installations réelles. Conforme aux normes électriques en vigueur en Côte d\'Ivoire (ANARE-CI).',
        'duree'            => '5 jours (40h)',
        'mode'             => 'hybride',
        'tarif_en_ligne'   => 220000,
        'tarif_presentiel' => 270000,
        'slug'             => 'technicien-energies-renouvelables-solaire-photovoltaique',
    ],
    [
        'titre'            => 'Responsable HSE Terrain — Hygiène, Sécurité & Environnement Opérationnel',
        'domaine'          => 'QHSE',
        'description'      => 'Le Responsable HSE Terrain est l\'agent de prévention au plus près des opérations : il identifie et évalue les risques sur les chantiers et sites industriels, réalise les inspections de sécurité, conduit les enquêtes d\'accidents, anime les causeries sécurité, gère les équipements de protection individuelle (EPI) et veille au respect des procédures HSE. Cette formation opérationnelle couvre les méthodes d\'analyse des risques (HAZOP, arbre des causes), la réglementation travail ivoirienne en matière de sécurité, la gestion des situations d\'urgence, la rédaction des rapports d\'incidents et accidents, et la communication HSE auprès des équipes terrain. Adapté aux industries extractives, BTP, agroalimentaire et logistique.',
        'duree'            => '4 jours (32h)',
        'mode'             => 'hybride',
        'tarif_en_ligne'   => 210000,
        'tarif_presentiel' => 260000,
        'slug'             => 'responsable-hse-terrain-hygiene-securite-environnement-operationnel',
    ],
    [
        'titre'            => 'Gestionnaire Eau, Assainissement & Déchets — WASH & Économie Circulaire',
        'domaine'          => 'Développement Durable & RSE',
        'description'      => 'La gestion de l\'eau, de l\'assainissement et des déchets solides est un enjeu majeur pour les collectivités locales, les entreprises industrielles et les organisations de développement en Afrique de l\'Ouest. Cette formation couvre les systèmes d\'adduction d\'eau potable (conception, gestion, maintenance), les solutions d\'assainissement (réseaux d\'égouts, latrines améliorées, biodigesteurs), la gestion des déchets solides (collecte, tri, valorisation, recyclage) et les principes de l\'économie circulaire appliqués au contexte africain. Inclut les outils de diagnostic des systèmes WASH, la mobilisation communautaire et les sources de financement (fonds climatiques, bailleurs sectoriels). Adapté aux communes, ONG, bureaux d\'études et entreprises du secteur.',
        'duree'            => '3 jours (24h)',
        'mode'             => 'hybride',
        'tarif_en_ligne'   => 210000,
        'tarif_presentiel' => 260000,
        'slug'             => 'gestionnaire-eau-assainissement-dechets-wash-economie-circulaire',
    ],

    /* ── MAINTENANCE INDUSTRIELLE & QUALITÉ ── */
    [
        'titre'            => 'Technicien en Maintenance Industrielle — Mécanique, Électrique & Automatisme',
        'domaine'          => 'BTP & Construction',
        'description'      => 'Les industries agroalimentaires, minières, pétrolières et manufacturières ivoiriennes ont un besoin massif de techniciens capables d\'assurer la disponibilité des équipements de production. Le Technicien de Maintenance Industrielle intervient sur les défaillances mécaniques, électriques et automatisées : diagnostic de pannes, remplacement de pièces, réglages et remise en service. Cette formation couvre la maintenance préventive (plan de maintenance, lubrification, contrôles périodiques) et curative (diagnostic, réparation), les bases de l\'électrotechnique industrielle (moteurs, variateurs, capteurs), l\'automatisme de base (automates programmables, lecture de schémas), et la gestion de la GMAO (Gestion de Maintenance Assistée par Ordinateur). Travaux pratiques intensifs sur des équipements réels.',
        'duree'            => '5 jours (40h)',
        'mode'             => 'hybride',
        'tarif_en_ligne'   => 220000,
        'tarif_presentiel' => 270000,
        'slug'             => 'technicien-maintenance-industrielle-mecanique-electrique-automatisme',
    ],
    [
        'titre'            => 'Responsable Qualité Opérationnel — Gestion des Non-Conformités & Amélioration Continue',
        'domaine'          => 'QHSE',
        'description'      => 'Au-delà de la certification ISO 9001, le responsable qualité opérationnel gère au quotidien la performance qualité de l\'entreprise : traitement des non-conformités, analyse des réclamations clients, mise en place des actions correctives et préventives (CAPA), animation des groupes de résolution de problèmes (8D, PDCA, 5 pourquoi), réalisation des audits internes, et pilotage des indicateurs qualité. Cette formation pratique développe les réflexes et outils du responsable qualité terrain, adaptés aux industries agroalimentaires, BTP, logistique et services en Côte d\'Ivoire. À l\'issue du parcours, le stagiaire est autonome pour animer un système qualité opérationnel et préparer les audits de certification.',
        'duree'            => '4 jours (32h)',
        'mode'             => 'hybride',
        'tarif_en_ligne'   => 210000,
        'tarif_presentiel' => 260000,
        'slug'             => 'responsable-qualite-operationnel-non-conformites-amelioration-continue',
    ],

    /* ── FINANCE AVANCÉE ── */
    [
        'titre'            => 'Analyste Financier — Évaluation d\'Entreprises & Décisions d\'Investissement',
        'domaine'          => 'Finance & Comptabilité',
        'description'      => 'L\'Analyste Financier est le professionnel qui éclaire les décisions d\'investissement par une analyse rigoureuse des performances financières et des perspectives de valorisation des entreprises. Cette formation couvre les techniques fondamentales de l\'analyse financière : lecture critique des états financiers (bilan, compte de résultat, flux de trésorerie), calcul des ratios de rentabilité, de liquidité et d\'endettement, modélisation financière sous Excel (DCF — Discounted Cash Flow, méthode des comparables), valorisation d\'entreprises et construction d\'un mémorandum d\'information. Idéal pour les analystes de banques, fonds d\'investissement, directions financières et cabinets de conseil souhaitant renforcer leurs compétences en évaluation et en prise de décision financière.',
        'duree'            => '5 jours (40h)',
        'mode'             => 'hybride',
        'tarif_en_ligne'   => 240000,
        'tarif_presentiel' => 290000,
        'slug'             => 'analyste-financier-evaluation-entreprises-decisions-investissement',
    ],
    [
        'titre'            => 'Finance Islamique & Produits Financiers Halal — Banque & Assurance Takaful',
        'domaine'          => 'Banque & Assurance',
        'description'      => 'La finance islamique est en forte croissance en Afrique de l\'Ouest avec l\'émergence de fenêtres islamiques dans les banques conventionnelles, les sukuk souverains et les produits Takaful. Cette formation couvre les principes fondamentaux de la finance islamique (interdiction du ribâ, du gharar et du maysir, partage des profits et des pertes), les principaux contrats (Mourabaha, Ijara, Moudharaba, Mousharaka, Salam), les produits bancaires islamiques (comptes participatifs, financement immobilier halal, crédit à la consommation conforme), et l\'assurance Takaful. Inclut un panorama des institutions de finance islamique opérant en zone UEMOA et les cadres réglementaires de la BCEAO relatifs à la finance participative.',
        'duree'            => '3 jours (24h)',
        'mode'             => 'hybride',
        'tarif_en_ligne'   => 210000,
        'tarif_presentiel' => 260000,
        'slug'             => 'finance-islamique-produits-financiers-halal-banque-takaful',
    ],
    [
        'titre'            => 'Fiscalité Pratique des Entreprises — TVA, IS & Obligations Déclaratives en Côte d\'Ivoire',
        'domaine'          => 'Comptabilité & Finance',
        'description'      => 'Maîtriser la fiscalité ivoirienne est indispensable pour tout dirigeant, comptable ou responsable financier. Cette formation opérationnelle couvre le cycle fiscal complet d\'une entreprise en Côte d\'Ivoire : la TVA (fait générateur, base imposable, déclaration mensuelle, TVA déductible et collectée, régularisations), l\'Impôt sur les Bénéfices Industriels et Commerciaux (BIC), l\'Impôt sur les Sociétés (IS), les acomptes provisionnels, la patente et les taxes locales, les retenues à la source (RAS) et les déclarations sociales coordonnées. Inclut la procédure de contrôle fiscal, les recours gracieux et contentieux, et les conventions fiscales internationales applicables en CI. Exercices pratiques de remplissage de déclarations réelles.',
        'duree'            => '4 jours (32h)',
        'mode'             => 'hybride',
        'tarif_en_ligne'   => 220000,
        'tarif_presentiel' => 270000,
        'slug'             => 'fiscalite-pratique-entreprises-tva-is-obligations-declaratives-cote-ivoire',
    ],

    /* ── DIGITAL & IA ── */
    [
        'titre'            => 'Python pour l\'Analyse de Données & l\'Automatisation — Débutants',
        'domaine'          => 'Informatique & Digital',
        'description'      => 'Python est devenu le langage de référence mondial pour l\'automatisation des tâches répétitives et l\'analyse de données. Cette formation pratique permet à tout professionnel, sans bagage informatique préalable, d\'acquérir les bases de Python et de les appliquer immédiatement à des cas d\'usage métier concrets : automatisation du traitement de fichiers Excel et CSV, envois d\'e-mails automatiques, scraping de données web, création de tableaux de bord simples avec Pandas et Matplotlib. Aucun prérequis en programmation n\'est nécessaire : la pédagogie part des besoins métier et explique le code pas à pas. À l\'issue, chaque participant repart avec un mini-projet Python opérationnel applicable dans son contexte professionnel.',
        'duree'            => '4 jours (32h)',
        'mode'             => 'hybride',
        'tarif_en_ligne'   => 220000,
        'tarif_presentiel' => 270000,
        'slug'             => 'python-analyse-donnees-automatisation-debutants',
    ],
    [
        'titre'            => 'Prompting Avancé & Usage Stratégique de l\'IA Générative en Entreprise',
        'domaine'          => 'IA & Digitalisation',
        'description'      => 'Au-delà de l\'initiation à l\'IA, cette formation développe une maîtrise avancée des outils d\'intelligence artificielle générative pour une utilisation stratégique en entreprise. Le programme couvre les techniques de prompting avancé (Chain of Thought, Few-Shot Learning, prompts systèmes), la création d\'assistants IA personnalisés (GPTs, Copilots), l\'intégration de l\'IA dans les workflows métier (rédaction, analyse, recherche, relation client), l\'utilisation de Claude, ChatGPT et Gemini pour des cas d\'usage professionnels spécifiques (RH, finance, marketing, juridique), et les enjeux éthiques et de confidentialité liés à l\'usage de l\'IA en entreprise. Chaque participant repart avec une bibliothèque de prompts optimisés pour son domaine professionnel.',
        'duree'            => '3 jours (24h)',
        'mode'             => 'hybride',
        'tarif_en_ligne'   => 210000,
        'tarif_presentiel' => 260000,
        'slug'             => 'prompting-avance-usage-strategique-ia-generative-entreprise',
    ],
    [
        'titre'            => 'Influence Marketing & Monétisation des Réseaux Sociaux',
        'domaine'          => 'Gestion Commerciale & Marketing',
        'description'      => 'L\'influence marketing est devenu un levier commercial incontournable en Afrique : TikTok, Instagram, YouTube et Facebook sont des canaux de vente à part entière. Cette formation couvre les deux dimensions du marché : pour les influenceurs (construire et monétiser son audience, négocier les contrats de partenariat, créer des contenus sponsorisés conformes aux réglementations) et pour les marques (identifier et sélectionner les bons influenceurs, structurer les briefs et les contrats, mesurer le ROI des campagnes). Inclut les spécificités du marché ivoirien et ouest-africain : types de contenus qui performent, tarification locale, plateformes dominantes et tendances de consommation. Formation pratique avec création d\'une campagne d\'influence simulée.',
        'duree'            => '3 jours (24h)',
        'mode'             => 'hybride',
        'tarif_en_ligne'   => 200000,
        'tarif_presentiel' => 250000,
        'slug'             => 'influence-marketing-monetisation-reseaux-sociaux',
    ],
    [
        'titre'            => 'E-commerce Mobile — Vendre sur WhatsApp Business, TikTok Shop & Instagram',
        'domaine'          => 'Gestion Commerciale & Marketing',
        'description'      => 'Le commerce social via mobile est la réalité du petit et moyen commerce africain : des milliers de vendeurs génèrent des revenus significatifs uniquement via WhatsApp, Instagram et TikTok, sans site web ni boutique physique. Cette formation pratique couvre la mise en place et l\'optimisation d\'un catalogue WhatsApp Business, la création d\'une boutique Instagram Shopping et TikTok Shop, les techniques de vente conversationnelle (scripts WhatsApp, relances, témoignages), la gestion des commandes et des livraisons, les solutions de paiement mobile (Wave, Orange Money, MTN MoMo), et les bases de la publicité sociale à petit budget. Adapté aux entrepreneurs, revendeurs, artisans et PME souhaitant développer leur chiffre d\'affaires en ligne sans infrastructure technique.',
        'duree'            => '2 jours (16h)',
        'mode'             => 'hybride',
        'tarif_en_ligne'   => 200000,
        'tarif_presentiel' => 250000,
        'slug'             => 'ecommerce-mobile-vendre-whatsapp-tiktok-instagram',
    ],

    /* ── RH & FORMATION ── */
    [
        'titre'            => 'Recruteur & Chargé de Talent Acquisition — Sourcing, Assessment & Marque Employeur',
        'domaine'          => 'Ressources Humaines',
        'description'      => 'Le Recruteur est le premier ambassadeur de l\'entreprise auprès des candidats. Cette formation spécialisée développe les compétences du recruteur moderne : rédaction d\'annonces attractives, sourcing multicanal (LinkedIn, job boards africains comme Emploi.ci et Jobberman, cooptation, campus), conduite d\'entretiens structurés et techniques d\'assessment, évaluation des compétences comportementales et techniques, gestion des outils ATS (Applicant Tracking System), communication de la marque employeur et gestion de l\'expérience candidat. Inclut les spécificités du marché de l\'emploi ivoirien : tensions sur certains profils, viviers de talents, pratiques salariales et processus de validation hiérarchique. Adapté aux recruteurs en cabinet, aux RH généralistes et aux managers impliqués dans le recrutement.',
        'duree'            => '4 jours (32h)',
        'mode'             => 'hybride',
        'tarif_en_ligne'   => 210000,
        'tarif_presentiel' => 260000,
        'slug'             => 'recruteur-talent-acquisition-sourcing-assessment-marque-employeur',
    ],
    [
        'titre'            => 'Ingénieur Pédagogique & Concepteur de Formations E-learning',
        'domaine'          => 'Éducation & Formation',
        'description'      => 'Avec l\'essor de la formation digitale en Afrique, les entreprises et organismes de formation ont besoin de professionnels capables de concevoir des modules e-learning engageants et efficaces. Cette formation couvre l\'ingénierie pédagogique (analyse des besoins, définition des objectifs, progression pédagogique, évaluation des acquis), la conception et la réalisation de contenus e-learning avec les outils dominants (Articulate Storyline, Rise, Canva), la gestion d\'une plateforme LMS (Moodle, 360Learning, TalentLMS), la création de vidéos pédagogiques et de classes virtuelles, et les principes du microlearning et du blended learning. À l\'issue de la formation, chaque participant aura conçu un module e-learning complet sur un sujet de son choix.',
        'duree'            => '4 jours (32h)',
        'mode'             => 'hybride',
        'tarif_en_ligne'   => 210000,
        'tarif_presentiel' => 260000,
        'slug'             => 'ingenieur-pedagogique-concepteur-formations-elearning',
    ],
    [
        'titre'            => 'Responsable RSE & Développement Durable — Stratégie, Reporting & Parties Prenantes',
        'domaine'          => 'Développement Durable & RSE',
        'description'      => 'La Responsabilité Sociétale des Entreprises (RSE) est devenue une exigence des donneurs d\'ordres internationaux, des banques et des investisseurs. Le Responsable RSE pilote la stratégie de durabilité de l\'entreprise : identification des enjeux matériels, dialogue avec les parties prenantes, intégration des critères ESG (environnementaux, sociaux et de gouvernance) dans les décisions stratégiques, mise en place de programmes sociaux et environnementaux, et production du rapport RSE. Cette formation couvre les référentiels internationaux (GRI, ISO 26000, ODD), les outils de mesure d\'impact, les achats responsables et la gestion de la chaîne d\'approvisionnement durable. Adaptée aux entreprises soumises aux exigences de leurs clients ou bailleurs internationaux.',
        'duree'            => '3 jours (24h)',
        'mode'             => 'hybride',
        'tarif_en_ligne'   => 210000,
        'tarif_presentiel' => 260000,
        'slug'             => 'responsable-rse-developpement-durable-strategie-reporting-parties-prenantes',
    ],

    /* ── COMMUNICATION & ÉVÉNEMENTIEL ── */
    [
        'titre'            => 'Organisateur d\'Événements Professionnels — MICE & Événementiel d\'Entreprise',
        'domaine'          => 'Communication Professionnelle',
        'description'      => 'Organiser un séminaire de direction, une conférence internationale, un gala d\'entreprise ou un lancement de produit est un métier à part entière qui exige rigueur, créativité et gestion du stress. Cette formation couvre l\'intégralité du cycle d\'organisation d\'un événement professionnel : analyse du brief client, conception du concept, sélection des prestataires (traiteur, salle, techniques son/lumière, décoration, hôtesses), gestion du budget événementiel, coordination logistique J-1 et Jour J, communication événementielle (invitations, programme, supports), et débriefing post-événement. Cas pratiques sur des événements réels du marché ivoirien. Adapté aux chargés de communication, assistants de direction, organisateurs d\'événements freelance et responsables commerciaux.',
        'duree'            => '3 jours (24h)',
        'mode'             => 'hybride',
        'tarif_en_ligne'   => 200000,
        'tarif_presentiel' => 250000,
        'slug'             => 'organisateur-evenements-professionnels-mice-evenementiel-entreprise',
    ],
    [
        'titre'            => 'Journaliste & Rédacteur Web — Écriture pour les Médias, la Presse & le Digital',
        'domaine'          => 'Communication Professionnelle',
        'description'      => 'Le journalisme et la rédaction web sont des métiers de plus en plus demandés avec la multiplication des médias en ligne, des newsletters et des contenus éditoriaux d\'entreprise. Cette formation couvre les techniques journalistiques fondamentales (collecte d\'informations, angles éditoriaux, vérification des faits — fact-checking), les formats spécifiques au web (article SEO, brève, reportage multimédia, interview), l\'écriture pour les réseaux sociaux, la gestion d\'un site d\'information et les bases du droit de la presse ivoirien. Exercices intensifs de rédaction d\'articles sur des sujets économiques et sociaux ivoiriens. Adapté aux journalistes débutants, chargés de communication, blogueurs et content managers souhaitant maîtriser les fondamentaux de l\'écriture journalistique.',
        'duree'            => '3 jours (24h)',
        'mode'             => 'hybride',
        'tarif_en_ligne'   => 200000,
        'tarif_presentiel' => 250000,
        'slug'             => 'journaliste-redacteur-web-ecriture-medias-presse-digital',
    ],
    [
        'titre'            => 'Podcasteur & Créateur de Contenu Audio-Visuel Professionnel',
        'domaine'          => 'Création de Contenu',
        'description'      => 'Le podcast et la vidéo sont devenus des formats incontournables pour le thought leadership, la communication d\'entreprise et le personal branding. Cette formation pratique couvre toutes les étapes de création d\'un podcast ou d\'une chaîne YouTube professionnelle : définition du concept et du positionnement, équipement audio et vidéo accessible, techniques d\'enregistrement et de captation, montage audio (Audacity, Descript) et vidéo (CapCut, DaVinci Resolve), distribution sur les plateformes (Spotify, Apple Podcasts, Deezer, YouTube), stratégie d\'audience et monétisation. Chaque participant repart avec son premier épisode enregistré, monté et prêt à publier. Adapté aux entrepreneurs, managers, formateurs et toute personne souhaitant développer son audience professionnelle.',
        'duree'            => '2 jours (16h)',
        'mode'             => 'presentiel',
        'tarif_en_ligne'   => 200000,
        'tarif_presentiel' => 250000,
        'slug'             => 'podcasteur-createur-contenu-audio-visuel-professionnel',
    ],

    /* ── COMMERCE INTERNATIONAL ── */
    [
        'titre'            => 'Responsable Export & Développement Commercial International',
        'domaine'          => 'Logistique & Supply Chain',
        'description'      => 'Le Responsable Export combine les compétences commerciales, logistiques et réglementaires pour développer les ventes d\'une entreprise sur les marchés étrangers. Cette formation couvre la dimension commerciale et stratégique de l\'export : analyse et sélection des marchés étrangers (étude de marché internationale, barrières à l\'entrée), adaptation de l\'offre et de la politique commerciale, prospection des acheteurs étrangers et participation aux salons internationaux, maîtrise des instruments de financement et de sécurisation des transactions export (crédit documentaire, SWIFT, COFACE), et rédaction des contrats internationaux. Inclut les opportunités spécifiques de la ZLECAf (Zone de Libre-Échange Continentale Africaine) pour les exportateurs ivoiriens.',
        'duree'            => '4 jours (32h)',
        'mode'             => 'hybride',
        'tarif_en_ligne'   => 220000,
        'tarif_presentiel' => 270000,
        'slug'             => 'responsable-export-developpement-commercial-international',
    ],
    [
        'titre'            => 'Déclarant en Douane & Procédures Douanières Avancées — SYDONIA & Contentieux',
        'domaine'          => 'Logistique & Supply Chain',
        'description'      => 'Le Déclarant en Douane est l\'expert des procédures douanières : il établit les déclarations en douane, détermine les positions tarifaires SH (Système Harmonisé), calcule les droits et taxes à l\'importation et à l\'exportation, gère les régimes douaniers spéciaux (entrepôt, transit, admission temporaire, régime franc) et représente les opérateurs devant l\'administration douanière. Cette formation avancée couvre le système SYDONIA (logiciel de dédouanement de la douane ivoirienne), la nomenclature SH et le Tarif Extérieur Commun CEDEAO, les procédures de dédouanement accéléré, les recours administratifs et le contentieux douanier. Indispensable pour les commissionnaires agréés en douane (CAD) et les responsables import-export.',
        'duree'            => '4 jours (32h)',
        'mode'             => 'hybride',
        'tarif_en_ligne'   => 220000,
        'tarif_presentiel' => 270000,
        'slug'             => 'declarant-douane-procedures-douanieres-avancees-sydonia-contentieux',
    ],

    /* ── ENTREPRENEURIAT ── */
    [
        'titre'            => 'Gestion Financière Simplifiée pour Artisans & Petits Commerçants',
        'domaine'          => 'Entrepreneuriat',
        'description'      => 'Des millions d\'artisans, couturiers, coiffeurs, mécaniciens auto, vendeuses de marché et revendeurs en Côte d\'Ivoire gèrent leur activité sans jamais séparer leur argent personnel de leur argent professionnel, ni calculer leur rentabilité réelle. Cette formation courte et pratique, sans prérequis comptable, donne les outils essentiels pour piloter une micro-entreprise : tenir un cahier de caisse simple, calculer le prix de revient et le prix de vente, identifier les clients et produits les plus rentables, gérer son stock, épargner pour investir et éviter les pièges de la trésorerie. Formation en français et adaptable en langues locales. Idéal pour les programmes d\'appui aux micro-entrepreneurs, les associations professionnelles et les groupements féminins.',
        'duree'            => '2 jours (16h)',
        'mode'             => 'presentiel',
        'tarif_en_ligne'   => 200000,
        'tarif_presentiel' => 250000,
        'slug'             => 'gestion-financiere-simplifiee-artisans-petits-commercants',
    ],
    [
        'titre'            => 'Créer sa Start-up & Intégrer un Incubateur — Du Lean Startup au Premier Financement',
        'domaine'          => 'Entrepreneuriat',
        'description'      => 'L\'écosystème startup ivoirien est en plein essor avec des structures d\'accompagnement actives (Orange Fab, CTIC Dakar, Bridge CI, Yelen Africa). Cette formation accompagne les porteurs de projets innovants dans les premières étapes de création : validation de l\'idée avec la méthodologie Lean Startup, conception et test du MVP (Minimum Viable Product), business model canvas, pitch deck percutant pour convaincre les investisseurs, processus de candidature aux incubateurs et accélérateurs, premières levées de fonds (love money, subventions publiques, business angels, venture capital). Programme inspiré des meilleures pratiques mondiales et adapté au contexte africain, avec témoignages de fondateurs ivoiriens ayant levé des fonds.',
        'duree'            => '3 jours (24h)',
        'mode'             => 'hybride',
        'tarif_en_ligne'   => 210000,
        'tarif_presentiel' => 260000,
        'slug'             => 'creer-startup-integrer-incubateur-lean-startup-premier-financement',
    ],
    [
        'titre'            => 'Tontines, Coopératives d\'Épargne & Finance Communautaire — Structurer et Gérer',
        'domaine'          => 'Entrepreneuriat',
        'description'      => 'La tontine et la mutuelle communautaire sont les instruments financiers de première proximité pour des millions d\'Africains. Bien structurées, elles constituent un outil puissant d\'accumulation de capital et de solidarité. Cette formation unique accompagne les organisateurs de tontines, groupements de femmes, associations d\'entraide et coopératives informelles dans la structuration rigoureuse de leurs mécanismes financiers communautaires : rédaction d\'un règlement intérieur, tenue d\'une comptabilité transparente, mécanismes de garantie et de recouvrement, transition vers la formalisation (coopérative d\'épargne-crédit, COOPEC, SFD), et accès aux financements institutionnels. Contenu participatif et adapté aux réalités socioculturelles ivoiriennes.',
        'duree'            => '2 jours (16h)',
        'mode'             => 'presentiel',
        'tarif_en_ligne'   => 200000,
        'tarif_presentiel' => 250000,
        'slug'             => 'tontines-cooperatives-epargne-finance-communautaire-structurer-gerer',
    ],

    /* ── SANTÉ & SOCIAL ── */
    [
        'titre'            => 'Aide-Soignant & Assistant Médical — Soins de Base, Hygiène & Accompagnement du Patient',
        'domaine'          => 'Santé & Médecine',
        'description'      => 'Le secteur de la santé privée en Côte d\'Ivoire connaît une expansion rapide avec l\'ouverture de nombreuses cliniques, polycliniques et maisons de retraite. L\'Aide-Soignant est un acteur essentiel de cette chaîne de soins : il assiste les infirmiers et médecins dans les soins d\'hygiène et de confort (toilette, habillage, mobilisation des patients), prend et enregistre les constantes vitales (tension, température, pouls), assure l\'accompagnement des patients pendant les examens et les repas, et veille à la propreté et à l\'hygiène des chambres. Cette formation couvre les gestes et postures professionnels, les règles d\'hygiène hospitalière, les soins de base, la communication bienveillante avec les patients et leurs familles, et les premiers gestes en situation d\'urgence.',
        'duree'            => '4 jours (32h)',
        'mode'             => 'presentiel',
        'tarif_en_ligne'   => 200000,
        'tarif_presentiel' => 250000,
        'slug'             => 'aide-soignant-assistant-medical-soins-base-hygiene-accompagnement-patient',
    ],
    [
        'titre'            => 'Travailleur Social & Accompagnement des Populations Vulnérables',
        'domaine'          => 'ONG & Développement',
        'description'      => 'Le Travailleur Social est un professionnel de l\'accompagnement humain qui intervient auprès des personnes en situation de vulnérabilité : personnes handicapées, femmes victimes de violences, enfants en situation de rue, personnes âgées dépendantes, ménages en situation de pauvreté extrême. Cette formation couvre les fondamentaux du travail social : analyse des situations sociales, techniques d\'entretien d\'aide et de soutien, montage des dossiers d\'accès aux droits et aux prestations sociales, orientation vers les services adaptés, médiation familiale et gestion des crises. Inclut le cadre légal et institutionnel ivoirien (Ministère des Affaires Sociales, structures d\'accueil, SAMU Social) et les approches de protection de l\'enfance et des droits des femmes.',
        'duree'            => '4 jours (32h)',
        'mode'             => 'hybride',
        'tarif_en_ligne'   => 200000,
        'tarif_presentiel' => 250000,
        'slug'             => 'travailleur-social-accompagnement-populations-vulnerables',
    ],

    /* ── MANAGEMENT & SOFT SKILLS ── */
    [
        'titre'            => 'Négociation Salariale & Gestion de Carrière — Valoriser son Profil Professionnel',
        'domaine'          => 'Développement Personnel',
        'description'      => 'Savoir négocier sa rémunération, gérer sa carrière et se valoriser sur le marché de l\'emploi est une compétence rarement enseignée mais fondamentale. Cette formation pratique et confidentielle couvre : l\'auto-évaluation des compétences et la définition d\'un projet professionnel clair, les techniques de recherche d\'emploi efficace (CV percutant, lettre de motivation, LinkedIn optimisé, réseau professionnel), la préparation et la conduite d\'un entretien d\'embauche, les techniques de négociation salariale (ancrage, BATNA, contre-offre), la gestion d\'une mobilité interne ou d\'une reconversion, et les règles de base du personal branding professionnel. Adapté au marché de l\'emploi ivoirien avec des cas pratiques et des simulations d\'entretiens.',
        'duree'            => '2 jours (16h)',
        'mode'             => 'hybride',
        'tarif_en_ligne'   => 200000,
        'tarif_presentiel' => 250000,
        'slug'             => 'negociation-salariale-gestion-carriere-valoriser-profil-professionnel',
    ],
    [
        'titre'            => 'Créativité, Design Thinking & Innovation en Entreprise',
        'domaine'          => 'Management & Leadership',
        'description'      => 'L\'innovation est devenue un impératif de survie pour les entreprises face à la concurrence et aux mutations technologiques. Le Design Thinking est la méthode la plus répandue pour structurer la créativité et résoudre les problèmes complexes de façon centrée sur l\'utilisateur. Cette formation pratique couvre les 5 phases du Design Thinking (empathie, définition, idéation, prototypage, test), les techniques de créativité collective (brainstorming, SCAMPER, carte mentale, carte d\'empathie), le prototypage rapide d\'idées et leur validation auprès des utilisateurs réels. Chaque équipe participant repart avec un prototype d\'innovation testé sur un problème réel de leur entreprise. Adapté aux managers, équipes produit, équipes marketing et tout professionnel cherchant à développer l\'innovation dans son organisation.',
        'duree'            => '3 jours (24h)',
        'mode'             => 'hybride',
        'tarif_en_ligne'   => 210000,
        'tarif_presentiel' => 260000,
        'slug'             => 'creativite-design-thinking-innovation-entreprise',
    ],
    [
        'titre'            => 'Médiation & Résolution des Conflits en Milieu Professionnel',
        'domaine'          => 'Management & Leadership',
        'description'      => 'Les conflits au travail (entre collègues, entre manager et collaborateurs, entre services ou avec des clients) sont inévitables et coûteux s\'ils ne sont pas gérés. Cette formation développe les compétences de médiateur et de gestionnaire de conflits : identification des sources et des types de conflits professionnels, techniques d\'écoute active et de reformulation, posture de médiateur neutre et bienveillant, cadre légal de la médiation en Côte d\'Ivoire, conduite d\'un entretien de médiation, formalisation des accords et suivi. Exercices intensifs de jeux de rôle basés sur des conflits professionnels réels. Indispensable pour les managers, responsables RH, directeurs d\'équipes multiculturelles et tout professionnel en interface relationnelle intense.',
        'duree'            => '2 jours (16h)',
        'mode'             => 'hybride',
        'tarif_en_ligne'   => 200000,
        'tarif_presentiel' => 250000,
        'slug'             => 'mediation-resolution-conflits-milieu-professionnel',
    ],

];

// ── DB ──────────────────────────────────────────────────────────
try { $pdo = Database::connect(); }
catch (Throwable $e) { die('<p style="color:red">Erreur DB : '.htmlspecialchars($e->getMessage()).'</p>'); }

$sql = "INSERT INTO formations
    (titre,domaine,description,duree,mode,tarif_en_ligne,tarif_presentiel,slug,statut,is_samedi_pro)
    VALUES(:titre,:domaine,:description,:duree,:mode,:tarif_en_ligne,:tarif_presentiel,:slug,'active',0)
    ON DUPLICATE KEY UPDATE titre=VALUES(titre),description=VALUES(description),
    duree=VALUES(duree),mode=VALUES(mode),tarif_en_ligne=VALUES(tarif_en_ligne),
    tarif_presentiel=VALUES(tarif_presentiel),statut='active'";

$chk = $pdo->prepare("SELECT id FROM formations WHERE slug=:s LIMIT 1");
$ins = $pdo->prepare($sql);
$ni=$nu=$ne=0;

echo "<h2>Résultats</h2>";
foreach($formations as $f){
    if($f['tarif_en_ligne']<200000||$f['tarif_presentiel']<250000){
        echo "<div class='row'><span class='err'>⛔ TARIF</span> — {$f['titre']}</div>"; $ne++; continue;
    }
    $chk->execute([':s'=>$f['slug']]); $exists=$chk->fetchColumn();
    try{
        $ins->execute([':titre'=>$f['titre'],':domaine'=>$f['domaine'],':description'=>$f['description'],
            ':duree'=>$f['duree'],':mode'=>$f['mode'],':tarif_en_ligne'=>$f['tarif_en_ligne'],
            ':tarif_presentiel'=>$f['tarif_presentiel'],':slug'=>$f['slug']]);
        if($exists){ echo "<div class='row'><span class='skip'>🔄 MÀJ</span> — ".htmlspecialchars($f['titre'])." <small>({$f['domaine']})</small></div>"; $nu++; }
        else        { echo "<div class='row'><span class='ok'>✅ INSÉRÉ</span> — ".htmlspecialchars($f['titre'])." <small>({$f['domaine']})</small></div>"; $ni++; }
    }catch(Throwable $e){
        echo "<div class='row'><span class='err'>❌</span> — ".htmlspecialchars($f['titre'])." : ".htmlspecialchars($e->getMessage())."</div>"; $ne++;
    }
}
echo "<h2>Bilan</h2><p><strong class='ok'>✅ {$ni} insérée(s)</strong> | <strong class='skip'>🔄 {$nu} MÀJ</strong> | <strong class='err'>❌ {$ne} erreur(s)</strong></p>";
?>
</body></html>
