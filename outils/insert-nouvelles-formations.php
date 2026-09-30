<?php
declare(strict_types=1);
require_once __DIR__ . '/../core/config.php';
$pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);

// Sécurité : exécution uniquement sur confirmation
$confirm = $_GET['confirm'] ?? '';

// ─── DONNÉES DES NOUVELLES FORMATIONS ────────────────────────────────────────
// Format : [titre, domaine, description_courte, pitch, avantages, public_cible, debouches, tarif_en_ligne, tarif_presentiel]
// date_debut = 2027-03-01, annee = 2027, mois = Mars, is_samedi_pro = 0

$formations = [

    // ── IBIG DIGITAL BUSINESS ───────────────────────────────────────────────
    ['Marketing Digital & Social Selling', 'IBIG DIGITAL BUSINESS',
     'Vendre sur WhatsApp, Facebook & TikTok',
     "Maîtrisez les stratégies de marketing digital et de social selling pour transformer vos réseaux sociaux en machines à générer des clients et des revenus.",
     "• Stratégies WhatsApp, Facebook & TikTok appliquées\n• Techniques de social selling immédiatement opérationnelles\n• Création de contenus qui attirent et convertissent\n• Outils d'automatisation et de prospection digitale\n• Formation animée par des praticiens du digital",
     "Commerciaux, entrepreneurs, responsables marketing, community managers, dirigeants de PME souhaitant développer leurs ventes via le digital.",
     "Chargé de marketing digital, Social Selling Manager, Business Developer, Entrepreneur digital, Responsable acquisition client.",
     200000, 250000],

    ['Stratégies de Vente sur les Réseaux Sociaux', 'IBIG DIGITAL BUSINESS',
     'Devenir un vendeur puissant sur les réseaux sociaux',
     "Apprenez à transformer votre présence sur les réseaux sociaux en un véritable canal de vente performant, en maîtrisant les techniques de prospection, d'engagement et de closing en ligne.",
     "• Méthodes de prospection sur LinkedIn, Instagram, Facebook\n• Scripts de vente adaptés aux réseaux sociaux\n• Techniques de closing par messagerie\n• Stratégie de contenu orientée conversion\n• Outils de suivi et d'automatisation",
     "Commerciaux, indépendants, consultants, coachs, entrepreneurs et responsables commerciaux souhaitant développer leurs ventes via les réseaux sociaux.",
     "Account Manager Digital, Social Seller, Business Developer, Commercial Digital, Responsable développement commercial.",
     200000, 250000],

    ['WhatsApp Business & Acquisition Client', 'IBIG DIGITAL BUSINESS',
     'WhatsApp Business : transformer ses contacts en clients',
     "Découvrez comment utiliser WhatsApp Business comme un outil professionnel de prospection, de communication et de vente pour transformer chaque contact en client fidèle.",
     "• Configuration optimale de WhatsApp Business\n• Stratégies de prospection et relance automatisée\n• Création de catalogues produits et campagnes\n• Scripts de vente et messages types efficaces\n• Gestion des leads et suivi client via WhatsApp",
     "Commerçants, entrepreneurs, commerciaux, prestataires de services, PME et TPE utilisant WhatsApp pour leur activité.",
     "Commercial Digital, Responsable Acquisition Client, Entrepreneur, Community Manager, Responsable CRM.",
     200000, 250000],

    ['TikTok Marketing & Social Commerce', 'IBIG DIGITAL BUSINESS',
     'TikTok Business : attirer, convaincre et vendre',
     "Maîtrisez TikTok comme levier de croissance pour votre business : créez des contenus viraux, construisez une audience qualifiée et transformez vos vues en ventes concrètes.",
     "• Création de contenu viral adapté à votre secteur\n• Stratégies TikTok Business et TikTok Shop\n• Techniques de storytelling pour vendre\n• Analyse des performances et optimisation\n• Collaboration avec des créateurs et influenceurs",
     "Entrepreneurs, marketers, créateurs de contenu, commerciaux et marques souhaitant exploiter TikTok pour générer des ventes.",
     "Content Creator, TikTok Manager, Social Media Manager, Responsable Marketing Digital, Entrepreneur Digital.",
     200000, 250000],

    ['Facebook Ads & Acquisition Digitale', 'IBIG DIGITAL BUSINESS',
     'Facebook Ads : transformer la publicité en ventes',
     "Apprenez à créer, optimiser et scaler des campagnes publicitaires Facebook et Instagram rentables pour générer des leads, des ventes et des clients de manière prévisible.",
     "• Création de campagnes Facebook & Instagram performantes\n• Ciblage avancé et audiences personnalisées\n• Conception de visuels et copies publicitaires qui convertissent\n• Optimisation du budget et du ROAS\n• Pixel Facebook, retargeting et tracking avancé",
     "Entrepreneurs, responsables marketing, e-commerçants, agences, indépendants souhaitant maîtriser la publicité sur Facebook et Instagram.",
     "Media Buyer, Traffic Manager, Digital Marketing Manager, Responsable Acquisition, Consultant Facebook Ads.",
     200000, 250000],

    ['Intelligence Artificielle appliquée au Marketing', 'IBIG DIGITAL BUSINESS',
     "L'IA au service du marketing et des ventes",
     "Découvrez comment utiliser l'intelligence artificielle pour automatiser vos campagnes marketing, personnaliser votre communication et démultiplier vos résultats commerciaux.",
     "• Outils IA pour la création de contenu (ChatGPT, Midjourney, etc.)\n• Automatisation des campagnes email et réseaux sociaux\n• Analyse prédictive et personnalisation client\n• IA pour la génération de leads et le scoring\n• Intégration IA dans votre stratégie marketing globale",
     "Responsables marketing, digital marketers, commerciaux, entrepreneurs et dirigeants souhaitant intégrer l'IA dans leur stratégie.",
     "AI Marketing Manager, Growth Hacker, Digital Marketing Consultant, Responsable Innovation Marketing.",
     200000, 250000],

    ['Content Marketing & Création de Contenus', 'IBIG DIGITAL BUSINESS',
     'Créer du contenu qui attire et qui vend',
     "Maîtrisez les techniques de création de contenu professionnel pour construire une audience engagée, générer du trafic qualifié et convertir vos lecteurs en clients.",
     "• Stratégie éditoriale et calendrier de contenu\n• Rédaction web et copywriting persuasif\n• Création de visuels, vidéos et podcasts\n• SEO et optimisation pour les moteurs de recherche\n• Distribution et amplification du contenu",
     "Blogueurs, entrepreneurs, responsables marketing, community managers, rédacteurs web et créateurs de contenu.",
     "Content Manager, Responsable Éditorial, Copywriter, Content Strategist, Digital Marketing Manager.",
     200000, 250000],

    ['Personal Branding & Marketing Digital', 'IBIG DIGITAL BUSINESS',
     'Devenir visible, crédible et rentable sur Internet',
     "Construisez une marque personnelle forte qui vous différencie, vous attire des opportunités et vous positionne comme expert incontournable dans votre domaine.",
     "• Définition de votre positionnement et identité de marque\n• Stratégie de présence sur les réseaux sociaux\n• Création de contenu qui reflète votre expertise\n• Développement de votre audience et de votre communauté\n• Monétisation de votre personal brand",
     "Consultants, coachs, dirigeants, professionnels libéraux, entrepreneurs et experts souhaitant développer leur notoriété personnelle.",
     "Consultant Indépendant, Coach, Conférencier, Expert Reconnu, Influenceur Professionnel, Entrepreneur.",
     200000, 250000],

    ['Marketing Digital & Stratégie Commerciale', 'IBIG DIGITAL BUSINESS',
     'Le Marketing Digital de A à Z pour entrepreneurs',
     "Acquérez une vision globale du marketing digital et développez une stratégie commerciale intégrée qui combine les outils digitaux les plus performants pour développer votre activité.",
     "• Vue d'ensemble du marketing digital et de ses leviers\n• Construction d'une stratégie commerciale digitale cohérente\n• Référencement naturel (SEO) et payant (SEA)\n• Email marketing et automation\n• Analyse des données et mesure de la performance",
     "Entrepreneurs, dirigeants de PME, responsables commerciaux et marketing cherchant une maîtrise globale du marketing digital.",
     "Digital Marketing Manager, Responsable Commercial Digital, Directeur Marketing, Entrepreneur Digital.",
     200000, 250000],

    ['IA, Automatisation & Social Selling', 'IBIG DIGITAL BUSINESS',
     'IA + Réseaux sociaux : la nouvelle machine à vendre',
     "Combinez la puissance de l'intelligence artificielle et des réseaux sociaux pour créer un système de vente automatisé et scalable qui génère des clients en continu.",
     "• Outils IA pour identifier et cibler les prospects\n• Automatisation de la prospection sur LinkedIn et Instagram\n• Chatbots et assistants IA pour la gestion des leads\n• Séquences de nurturing automatisées\n• Reporting et optimisation continue",
     "Commerciaux, business developers, entrepreneurs et responsables marketing souhaitant automatiser leur prospection et leurs ventes.",
     "Growth Hacker, Sales Automation Specialist, Business Developer Digital, Responsable Commerciale Digital.",
     200000, 250000],

    // ── IBIG E-COMMERCE ─────────────────────────────────────────────────────
    ['Dropshipping & E-commerce', 'IBIG E-COMMERCE',
     'Dropshipping : lancer sa boutique et vendre sans stock',
     "Créez et développez une activité e-commerce rentable sans avoir à gérer de stock grâce au dropshipping : de la sélection des produits au service client, en passant par la publicité.",
     "• Création de boutique en ligne clé en main\n• Sélection et validation des produits gagnants\n• Gestion des fournisseurs et partenaires\n• Stratégies publicitaires pour le dropshipping\n• Optimisation des marges et scaling",
     "Entrepreneurs, étudiants, personnes en reconversion cherchant à créer une activité en ligne sans investissement en stock.",
     "E-commerçant, Dropshipper, Entrepreneur Digital, Responsable E-commerce.",
     200000, 250000],

    ['Commerce International & Import-Export', 'IBIG E-COMMERCE',
     'De la Chine à votre client : réussir son commerce international',
     "Maîtrisez les fondamentaux et les stratégies avancées du commerce international pour importer, exporter et développer des partenariats commerciaux rentables à l'échelle mondiale.",
     "• Réglementation douanière et incoterms\n• Négociation avec des fournisseurs internationaux\n• Logistique et gestion des expéditions\n• Financement du commerce international\n• Développement de marchés à l'export",
     "Commerçants, importateurs, exportateurs, entrepreneurs, responsables achats internationaux.",
     "Import-Export Manager, Trader International, Responsable Achats Internationaux, Entrepreneur Import-Export.",
     200000, 250000],

    ['Sourcing International & Commerce en Ligne', 'IBIG E-COMMERCE',
     'Alibaba & AliExpress : acheter, importer et revendre',
     "Apprenez à sourcer les meilleurs produits depuis les plateformes chinoises Alibaba et AliExpress pour les importer et les commercialiser avec des marges attractives.",
     "• Navigation et utilisation professionnelle d'Alibaba\n• Évaluation et sélection des fournisseurs fiables\n• Négociation des prix et des conditions\n• Processus d'importation et de dédouanement\n• Revente sur marketplaces et boutique en ligne",
     "Commerçants, e-commerçants, dropshippers et entrepreneurs souhaitant développer une activité d'import depuis la Chine.",
     "Acheteur International, E-commerçant, Importateur, Responsable Sourcing.",
     200000, 250000],

    ['Sourcing, Négociation & Achat International', 'IBIG E-COMMERCE',
     'Trouver les bons fournisseurs en Chine',
     "Développez les compétences essentielles pour identifier, évaluer et négocier avec des fournisseurs chinois fiables afin d'optimiser vos achats internationaux.",
     "• Identification et vérification des fournisseurs sérieux\n• Techniques de négociation avec les fournisseurs asiatiques\n• Contrôle qualité et inspection des marchandises\n• Gestion des risques fournisseurs\n• Outils de sourcing : Alibaba, Global Sources, 1688",
     "Acheteurs, responsables procurement, e-commerçants, importateurs et entrepreneurs réalisant des achats en Chine.",
     "Responsable Sourcing, Acheteur International, Import Manager, Responsable Achats.",
     200000, 250000],

    ['Importation de Produits & Business International', 'IBIG E-COMMERCE',
     'Importer de Chine et construire une activité rentable',
     "Construisez une activité d'importation rentable depuis la Chine : de la recherche de produits à la vente finale, en maîtrisant tous les aspects logistiques, douaniers et commerciaux.",
     "• Business plan d'une activité d'importation\n• Processus complet d'importation depuis la Chine\n• Calcul des coûts, marges et rentabilité\n• Distribution et commercialisation des produits importés\n• Développement et fidélisation de la clientèle",
     "Entrepreneurs, commerçants, porteurs de projets souhaitant créer ou développer une activité d'importation.",
     "Importateur, Entrepreneur, Trader, Responsable Commercial, Distributeur.",
     200000, 250000],

    ['Création & Gestion d\'un E-commerce', 'IBIG E-COMMERCE',
     'Créer son business e-commerce de zéro',
     "Créez votre boutique en ligne professionnelle de A à Z et apprenez à la gérer efficacement pour générer des ventes et développer votre activité e-commerce.",
     "• Choix de la plateforme e-commerce adaptée\n• Création et optimisation de la boutique en ligne\n• Stratégie de référencement et d'acquisition\n• Gestion des commandes, stocks et SAV\n• Analyse des performances et optimisation continue",
     "Entrepreneurs, commerçants, artisans, créateurs cherchant à vendre leurs produits ou services en ligne.",
     "E-commerçant, Responsable E-commerce, Chef de Projet Digital, Entrepreneur.",
     200000, 250000],

    ['Product Research & Stratégie Commerciale', 'IBIG E-COMMERCE',
     'Trouver un produit gagnant et le commercialiser',
     "Découvrez les méthodes et outils professionnels pour identifier des produits à fort potentiel commercial et construire une stratégie de mise sur le marché efficace.",
     "• Méthodes de recherche de produits gagnants\n• Analyse de la concurrence et validation du marché\n• Calcul de la rentabilité et du potentiel\n• Stratégie de lancement produit\n• Outils de product research (Jungle Scout, Helium 10, etc.)",
     "E-commerçants, dropshippers, entrepreneurs souhaitant identifier et lancer des produits rentables sur le marché.",
     "Product Manager, E-commerçant, Entrepreneur, Chef de Produit, Responsable Développement.",
     200000, 250000],

    ['E-commerce, Social Commerce & Vente Digitale', 'IBIG E-COMMERCE',
     'Vendre sans boutique physique',
     "Maîtrisez toutes les formes de vente digitale — e-commerce, social commerce, marketplaces — pour développer votre activité commerciale sans contrainte géographique.",
     "• E-commerce : boutique, marketplace, social commerce\n• Vente sur Facebook Shop, Instagram Shopping, TikTok Shop\n• Stratégies de promotion et de fidélisation en ligne\n• Gestion des paiements et de la livraison\n• Développement et optimisation des ventes en ligne",
     "Commerçants, entrepreneurs, artisans et PME souhaitant développer leurs ventes en ligne sur différentes plateformes.",
     "E-commerçant, Social Commerce Manager, Responsable Vente Digitale, Entrepreneur.",
     200000, 250000],

    // ── IBIG SALES ACADEMY ──────────────────────────────────────────────────
    ['Techniques de Vente & Closing', 'IBIG SALES ACADEMY',
     "L'art de vendre : transformer un prospect en client",
     "Maîtrisez les techniques de vente les plus efficaces et les stratégies de closing pour convertir vos prospects en clients et augmenter significativement votre chiffre d'affaires.",
     "• Les étapes clés du processus de vente\n• Techniques de qualification et découverte client\n• Argumentation et réponse aux objections\n• Techniques de closing et de négociation\n• Fidélisation et développement du portefeuille client",
     "Commerciaux, technico-commerciaux, ingénieurs d'affaires, entrepreneurs et toute personne souhaitant améliorer ses performances commerciales.",
     "Commercial Senior, Account Manager, Business Developer, Responsable Commercial, Directeur Commercial.",
     200000, 250000],

    ['Social Selling & Prospection Digitale', 'IBIG SALES ACADEMY',
     'Vendre plus grâce aux réseaux sociaux',
     "Transformez vos réseaux sociaux en un puissant outil de prospection et de vente pour identifier les bons prospects, créer de la relation et convertir en clients.",
     "• Stratégie de social selling sur LinkedIn, Instagram, Facebook\n• Optimisation du profil pour attirer les prospects\n• Scripts de prise de contact et de qualification\n• Séquences de nurturing et de relance\n• Mesure et optimisation des résultats",
     "Commerciaux, business developers, indépendants, consultants souhaitant développer leurs ventes via les réseaux sociaux.",
     "Social Selling Manager, Business Developer, Commercial Digital, Account Manager, Consultant.",
     200000, 250000],

    ['Influence Marketing & Stratégies de Collaboration', 'IBIG SALES ACADEMY',
     "Le Marketing d'Influence qui convertit",
     "Concevez et déployez des stratégies de marketing d'influence efficaces pour augmenter votre notoriété, toucher de nouvelles audiences et générer des ventes mesurables.",
     "• Identification et sélection des influenceurs pertinents\n• Négociation et contractualisation des partenariats\n• Création de campagnes d'influence impactantes\n• Mesure du ROI et des performances\n• Gestion des relations influenceurs sur le long terme",
     "Responsables marketing, community managers, entrepreneurs, directeurs communication et agences souhaitant développer le marketing d'influence.",
     "Influence Marketing Manager, Brand Partnership Manager, Responsable Partenariats, Community Manager Senior.",
     200000, 250000],

    ['Personal Branding & Influence Digitale', 'IBIG SALES ACADEMY',
     'Devenir influenceur et construire sa communauté',
     "Développez votre influence digitale en construisant une marque personnelle forte, une communauté engagée et des partenariats commerciaux rentables.",
     "• Définition de votre niche et positionnement\n• Construction et engagement de votre communauté\n• Monétisation de votre influence\n• Collaboration avec des marques\n• Développement de vos revenus en tant qu'influenceur",
     "Créateurs de contenu, aspirants influenceurs, experts et professionnels souhaitant monétiser leur présence digitale.",
     "Influenceur, Content Creator, Brand Ambassador, Consultant, Entrepreneur Digital.",
     200000, 250000],

    ['Prospection Commerciale & Fidélisation Client', 'IBIG SALES ACADEMY',
     'Prospecter, convaincre et fidéliser',
     "Développez un système complet de prospection et de fidélisation qui vous permet de générer régulièrement de nouveaux clients tout en maximisant la valeur de votre portefeuille existant.",
     "• Techniques de prospection multicanal (téléphone, email, réseaux sociaux)\n• Qualification et priorisation des prospects\n• Pitch commercial et argumentaire percutant\n• Techniques de fidélisation et développement du compte client\n• Outils CRM et gestion du pipeline",
     "Commerciaux, téléprospecteurs, business developers, indépendants et entrepreneurs cherchant à développer leur clientèle.",
     "Business Developer, Commercial, Account Manager, Responsable Développement Client, Entrepreneur.",
     200000, 250000],

    ['Techniques de Closing & Négociation', 'IBIG SALES ACADEMY',
     'Closing : apprendre à faire dire OUI',
     "Maîtrisez les techniques de closing les plus efficaces pour conclure vos ventes avec assurance et développer vos compétences en négociation commerciale.",
     "• Les techniques de closing éprouvées\n• Gestion des objections et des hésitations\n• Négociation raisonnée et création de valeur\n• Scripts et formulations qui débloquent les décisions\n• Psychologie de la décision d'achat",
     "Commerciaux, indépendants, entrepreneurs et toute personne souhaitant améliorer ses capacités à conclure des ventes.",
     "Commercial Senior, Account Manager, Business Developer, Responsable Commercial, Négociateur.",
     200000, 250000],

    ['Psychologie de la Vente & Comportement Client', 'IBIG SALES ACADEMY',
     'Psychologie du consommateur : comprendre pour mieux vendre',
     "Comprenez les mécanismes psychologiques qui gouvernent les décisions d'achat pour adapter votre approche commerciale et maximiser vos conversions.",
     "• Les biais cognitifs et leur influence sur les achats\n• Techniques de persuasion éthique\n• Profils psychologiques des acheteurs\n• Communication adaptée selon le profil client\n• Storytelling et déclencheurs émotionnels d'achat",
     "Commerciaux, marketers, entrepreneurs et managers souhaitant comprendre la psychologie du client pour mieux vendre.",
     "Commercial Expert, Marketing Manager, Responsable Expérience Client, Consultant.",
     200000, 250000],

    ['Stratégie Commerciale & Développement des Ventes', 'IBIG SALES ACADEMY',
     'Créer une stratégie commerciale qui génère des clients',
     "Construisez une stratégie commerciale structurée et efficace qui vous permet d'atteindre vos objectifs de vente de manière systématique et prévisible.",
     "• Analyse du marché et positionnement commercial\n• Définition des cibles et des segments prioritaires\n• Construction du plan d'action commercial\n• Management de l'équipe commerciale\n• Tableaux de bord et pilotage des performances",
     "Directeurs commerciaux, responsables des ventes, entrepreneurs et managers cherchant à structurer et optimiser leur stratégie commerciale.",
     "Directeur Commercial, Responsable des Ventes, Business Development Manager, Directeur Général.",
     200000, 250000],

    // ── IBIG EXECUTIVE ACADEMY ───────────────────────────────────────────────
    ['Certificat du Dirigeant d\'Entreprise', 'IBIG EXECUTIVE ACADEMY',
     'Piloter, décider et développer une entreprise performante',
     "Le programme certifiant qui transforme les entrepreneurs et dirigeants en véritables chefs d'entreprise capables de piloter leur organisation avec méthode, vision et performance.",
     "• Vision stratégique et pilotage de l'entreprise\n• Management et leadership des équipes\n• Gestion financière et contrôle de gestion\n• Stratégie commerciale et développement\n• Gouvernance et prise de décision stratégique\n• Certification reconnue par les acteurs économiques",
     "Dirigeants, gérants, DG, PDG, entrepreneurs et porteurs de projets souhaitant professionnaliser leur pratique de direction.",
     "Directeur Général, PDG, Entrepreneur Certifié, Directeur d'Exploitation, Manager Senior.",
     200000, 250000],

    ['Leadership & Management des Équipes', 'IBIG EXECUTIVE ACADEMY',
     'Devenir un dirigeant stratégique',
     "Développez les compétences de leadership et de management nécessaires pour inspirer, mobiliser et conduire vos équipes vers la performance et l'excellence.",
     "• Styles de leadership et leur impact\n• Techniques de management situationnel\n• Communication managériale efficace\n• Gestion des conflits et médiation\n• Développement des talents et motivation des équipes",
     "Managers, responsables d'équipes, dirigeants et chefs de projet souhaitant développer leur leadership et améliorer leur management.",
     "Manager, Directeur d'Équipe, Responsable RH, Directeur des Opérations, Chef de Projet Senior.",
     200000, 250000],

    ['Création d\'Entreprise & Business Plan', 'IBIG EXECUTIVE ACADEMY',
     'De l\'idée à l\'entreprise : construire un business viable',
     "Passez de l'idée à l'action en apprenant à structurer votre projet d'entreprise, rédiger un business plan convaincant et poser les bases d'un business durable.",
     "• De l'idée au concept viable : validation et étude de marché\n• Structure juridique et formalités de création\n• Business plan complet et prévisionnel financier\n• Stratégie de lancement et plan d'action\n• Pitching devant des partenaires et investisseurs",
     "Porteurs de projets, futurs entrepreneurs, salariés en reconversion et étudiants souhaitant créer leur entreprise.",
     "Entrepreneur, Chef d'Entreprise, Directeur Fondateur, Startup Founder, Gérant.",
     200000, 250000],

    ['Entrepreneuriat & Développement d\'Entreprise', 'IBIG EXECUTIVE ACADEMY',
     'Entreprendre avec méthode : créer, structurer et développer',
     "Acquérez toutes les compétences clés de l'entrepreneur moderne pour créer, structurer et développer votre activité avec méthode, en évitant les erreurs classiques des créateurs.",
     "• Mindset et posture de l'entrepreneur performant\n• Structuration juridique, fiscale et comptable\n• Développement commercial et marketing\n• Gestion opérationnelle et organisation\n• Croissance, scaling et pivot stratégique",
     "Entrepreneurs en activité, créateurs d'entreprise et dirigeants de TPE/PME cherchant à professionnaliser leur approche.",
     "Entrepreneur, Dirigeant de PME, Directeur Général, Responsable Développement.",
     200000, 250000],

    ['Finance pour Dirigeants Non-Financiers', 'IBIG EXECUTIVE ACADEMY',
     'Gestion financière pour dirigeants non-financiers',
     "Maîtrisez les fondamentaux de la gestion financière pour piloter votre entreprise avec lucidité, prendre les bonnes décisions et dialoguer efficacement avec vos partenaires financiers.",
     "• Lecture et analyse des états financiers\n• Gestion de la trésorerie et du BFR\n• Pilotage par les indicateurs financiers clés\n• Investissement, financement et rentabilité\n• Relations avec les banques et les investisseurs",
     "Dirigeants non-financiers, managers, entrepreneurs et chefs d'entreprise cherchant à maîtriser les dimensions financières de leur activité.",
     "Directeur Général, Entrepreneur, Manager Non-Financier, Responsable Opérationnel.",
     200000, 250000],

    ['Financement d\'Entreprise & Levée de Fonds', 'IBIG EXECUTIVE ACADEMY',
     'Financer son entreprise : stratégies, dossiers et négociation',
     "Découvrez toutes les options de financement disponibles pour votre entreprise et apprenez à constituer des dossiers solides pour convaincre banques et investisseurs.",
     "• Panorama des modes de financement (banques, fonds, subventions)\n• Constitution du dossier de financement\n• Techniques de négociation avec les banquiers\n• Levée de fonds et relations investisseurs\n• Financement de la croissance et du développement",
     "Dirigeants, entrepreneurs et responsables financiers cherchant à financer leur développement ou obtenir des ressources externes.",
     "Directeur Financier, Entrepreneur, Responsable Développement, Directeur Général.",
     200000, 250000],

    ['Stratégie d\'Entreprise & Prise de Décision', 'IBIG EXECUTIVE ACADEMY',
     'Prise de décision stratégique pour dirigeants',
     "Développez votre capacité à prendre des décisions stratégiques éclairées dans un environnement complexe et incertain, en vous appuyant sur des outils et méthodes éprouvés.",
     "• Diagnostic stratégique et analyse de l'environnement\n• Outils de décision stratégique (SWOT, PESTEL, matrices)\n• Intelligence économique et veille concurrentielle\n• Gestion de l'incertitude et des risques\n• Planification stratégique et déploiement",
     "Dirigeants, DG, managers stratégiques et entrepreneurs souhaitant structurer leur réflexion et leurs prises de décision.",
     "Directeur Général, Directeur Stratégique, Consultant, Entrepreneur, Manager Senior.",
     200000, 250000],

    ['Management & Leadership pour Dirigeants', 'IBIG EXECUTIVE ACADEMY',
     'Management & Leadership pour dirigeants',
     "Un programme premium destiné aux dirigeants pour renforcer leur leadership, optimiser leur management et développer une organisation performante et alignée.",
     "• Leadership transformationnel et vision\n• Construction d'une culture d'entreprise forte\n• Gestion des talents et développement des compétences\n• Communication du dirigeant et influence\n• Conduite du changement organisationnel",
     "Dirigeants, DG, fondateurs et top managers souhaitant renforcer leur leadership et leur impact organisationnel.",
     "PDG, Directeur Général, Fondateur, Directeur des Opérations, Manager Senior.",
     200000, 250000],

    ['Entrepreneur 360° Business', 'IBIG EXECUTIVE ACADEMY',
     'Créer, vendre, gérer et développer son business',
     "Une formation complète pour les entrepreneurs qui veulent maîtriser les quatre piliers essentiels du business : création, vente, gestion et développement.",
     "• Création et structuration d'entreprise\n• Stratégie commerciale et marketing\n• Gestion financière et administrative\n• Croissance et développement business\n• Leadership et management d'équipe",
     "Entrepreneurs en activité ou en projet souhaitant avoir une vision complète et des compétences solides dans tous les domaines clés du business.",
     "Entrepreneur Polyvalent, Dirigeant de PME, Business Owner, Fondateur de Startup.",
     200000, 250000],

    // ── IBIG DATA & AI ──────────────────────────────────────────────────────
    ['Data Analyst RH', 'IBIG DATA & AI',
     'Transformer les données RH en décisions stratégiques',
     "Devenez un expert de l'analyse des données RH : collectez, analysez et visualisez les données pour transformer la gestion des ressources humaines en une fonction pilotée par la donnée.",
     "• Fondamentaux de l'analyse de données appliqués aux RH\n• Excel avancé pour l'analyse RH\n• Power BI : tableaux de bord RH interactifs\n• People Analytics et prédiction des tendances RH\n• Communication des insights aux décideurs",
     "Professionnels RH, responsables SIRH, DRH, contrôleurs de gestion sociale et managers souhaitant maîtriser l'analyse des données RH.",
     "Data Analyst RH, HRIS Analyst, People Analytics Manager, Responsable Reporting RH.",
     200000, 250000],

    ['Power BI pour les Professionnels', 'IBIG DATA & AI',
     'Power BI pour les professionnels RH',
     "Maîtrisez Power BI pour concevoir des tableaux de bord professionnels, analyser vos données et créer des rapports visuels percutants pour la prise de décision.",
     "• Prise en main de Power BI Desktop\n• Connexion aux sources de données et transformation\n• Modélisation des données et mesures DAX\n• Création de visualisations et tableaux de bord\n• Publication et partage des rapports Power BI",
     "Contrôleurs de gestion, analystes, responsables RH, managers et toute personne souhaitant maîtriser Power BI pour l'analyse de données.",
     "Data Analyst, Business Intelligence Analyst, Contrôleur de Gestion, Responsable Reporting.",
     200000, 250000],

    ['Excel Avancé & Analyse des Données', 'IBIG DATA & AI',
     'Excel & Power BI : devenir autonome en analyse des données',
     "Maîtrisez Excel à un niveau avancé et intégrez Power BI pour analyser efficacement vos données, automatiser vos reportings et prendre de meilleures décisions.",
     "• Formules avancées et fonctions complexes d'Excel\n• Tableaux croisés dynamiques et analyse multidimensionnelle\n• Macros VBA pour l'automatisation\n• Introduction et prise en main de Power BI\n• Construction de tableaux de bord décisionnels",
     "Professionnels de tous secteurs utilisant Excel et souhaitant passer au niveau supérieur en analyse de données.",
     "Analyste, Contrôleur de Gestion, Responsable Administratif, Responsable RH, Manager.",
     200000, 250000],

    ['People Analytics & Gestion des Talents', 'IBIG DATA & AI',
     'People Analytics : analyser les talents et prédire les tendances',
     "Exploitez la puissance des données pour prendre de meilleures décisions RH : recrutement, rétention, performance, formation et planification des effectifs basés sur les données.",
     "• Introduction au People Analytics et à ses applications\n• Collecte et qualité des données RH\n• Analyse de la performance et de l'engagement\n• Prédiction du turnover et de l'absentéisme\n• Présentation des insights aux dirigeants",
     "DRH, responsables RH, responsables talent, business partners RH souhaitant intégrer l'analytique dans leur pratique.",
     "People Analytics Manager, HRBP Senior, DRH, Responsable Développement RH.",
     200000, 250000],

    ['Intelligence Artificielle pour les Ressources Humaines', 'IBIG DATA & AI',
     'IA pour les Ressources Humaines',
     "Intégrez l'intelligence artificielle dans vos pratiques RH pour automatiser les tâches répétitives, améliorer le recrutement et développer une gestion des talents augmentée.",
     "• IA appliquée au recrutement et à la sélection\n• Chatbots et assistants virtuels RH\n• Analyse prédictive des comportements RH\n• Automatisation des processus administratifs RH\n• Éthique et enjeux de l'IA en RH",
     "DRH, responsables recrutement, chargés RH et professionnels souhaitant intégrer l'IA dans leurs pratiques RH.",
     "AI HR Manager, Responsable Transformation RH, DRH Digital, Consultant RH Innovation.",
     200000, 250000],

    ['Intelligence Artificielle pour les Entreprises', 'IBIG DATA & AI',
     "L'IA au service du recrutement et de la gestion des talents",
     "Comprenez les opportunités et les applications pratiques de l'intelligence artificielle pour votre entreprise et apprenez à déployer des solutions IA concrètes.",
     "• Panorama de l'IA et de ses applications business\n• Identification des cas d'usage pertinents pour votre entreprise\n• Outils IA accessibles aux PME et entrepreneurs\n• Stratégie d'intégration de l'IA\n• ROI et mesure de l'impact IA",
     "Dirigeants, managers, responsables transformation et entrepreneurs souhaitant comprendre et utiliser l'IA dans leur entreprise.",
     "Chief AI Officer, Responsable Transformation Digitale, Directeur Innovation, Entrepreneur.",
     200000, 250000],

    ['Automatisation & Optimisation avec l\'IA', 'IBIG DATA & AI',
     'Automatiser les tâches RH avec l\'IA',
     "Automatisez vos tâches répétitives et chronophages grâce aux outils d'intelligence artificielle pour gagner du temps, réduire les erreurs et vous concentrer sur l'essentiel.",
     "• Identification des tâches automatisables\n• Outils no-code d'automatisation (Zapier, Make, n8n)\n• Intégration de l'IA dans vos workflows\n• Chatbots et assistants IA opérationnels\n• Construction de processus automatisés de A à Z",
     "Professionnels, responsables opérationnels, entrepreneurs et managers souhaitant automatiser leurs processus avec l'IA.",
     "Responsable Automatisation, Process Manager, Digital Operations Manager, Entrepreneur.",
     200000, 250000],

    ['Tableaux de Bord & Pilotage de la Performance', 'IBIG DATA & AI',
     'Tableaux de bord : construire des outils de pilotage efficaces',
     "Concevez et déployez des tableaux de bord décisionnels qui vous permettent de piloter votre activité en temps réel et de prendre des décisions éclairées basées sur des données fiables.",
     "• Identification des KPI pertinents pour votre activité\n• Conception et mise en page des tableaux de bord\n• Outils de data visualisation (Excel, Power BI, Google Data Studio)\n• Automatisation de la collecte et de la mise à jour des données\n• Présentation et communication des résultats",
     "Managers, contrôleurs de gestion, responsables opérationnels et dirigeants souhaitant structurer leur pilotage par les indicateurs.",
     "Contrôleur de Gestion, Business Intelligence Manager, Responsable Pilotage, Manager.",
     200000, 250000],

    // ── IBIG LEADERSHIP & COMMUNICATION ─────────────────────────────────────
    ['Art Oratoire & Prise de Parole en Public', 'IBIG LEADERSHIP & COMMUNICATION',
     'Parler pour convaincre : l\'art oratoire des leaders',
     "Développez votre aisance et votre impact à l'oral pour prendre la parole avec confiance, captiver vos auditoires et convaincre avec autorité dans toutes les situations.",
     "• Gestion du stress et de la trac\n• Techniques de respiration et de présence physique\n• Structuration d'un discours percutant\n• Techniques d'éloquence et de rhétorique\n• Prise de parole en réunion, conférence et médias",
     "Managers, dirigeants, commerciaux, formateurs et toute personne souhaitant améliorer son impact à l'oral.",
     "Conférencier, Manager, Commercial, Directeur, Formateur, Consultant.",
     200000, 250000],

    ['Prise de Parole avec Impact', 'IBIG LEADERSHIP & COMMUNICATION',
     'Prendre la parole avec impact et assurance',
     "Acquérez les compétences pour intervenir avec assurance et impact dans tous les contextes professionnels : réunions, présentations, négociations et prises de parole publique.",
     "• Connaissance de soi et identification de son style oratoire\n• Techniques de structuration et d'improvisation\n• Gestion des questions et situations difficiles\n• Langage corporel et communication non-verbale\n• Mises en situation et coaching individualisé",
     "Professionnels, managers, commerciaux et cadres souhaitant renforcer leur impact à l'oral.",
     "Manager, Directeur, Commercial Senior, Consultant, Entrepreneur.",
     200000, 250000],

    ['Communication Professionnelle & Leadership', 'IBIG LEADERSHIP & COMMUNICATION',
     'Devenir un excellent communicateur professionnel',
     "Développez votre communication professionnelle dans toutes ses dimensions pour renforcer votre leadership, votre influence et votre efficacité relationnelle.",
     "• Communication verbale, non-verbale et écrite\n• Communication managériale et leadership\n• Communication transversale et en équipe\n• Gestion des situations de communication difficile\n• Communication de crise et influence",
     "Managers, dirigeants, RH, commerciaux et professionnels souhaitant améliorer leur communication sous toutes ses formes.",
     "Directeur de la Communication, Manager, DRH, Responsable Relations Publiques, Consultant.",
     200000, 250000],

    ['Pitch & Présentation Percutante', 'IBIG LEADERSHIP & COMMUNICATION',
     'Pitch & Présentation : convaincre en quelques minutes',
     "Apprenez à construire et délivrer des pitchs et présentations mémorables qui captent l'attention, font adhérer et déclenchent l'action chez vos interlocuteurs.",
     "• Structure d'un pitch percutant\n• Conception de supports visuels impactants\n• Storytelling et narration persuasive\n• Livraison et délivrance du pitch\n• Adaptation au contexte : investisseurs, clients, grand public",
     "Entrepreneurs, startupers, commerciaux, managers et professionnels souhaitant maîtriser l'art du pitch et de la présentation.",
     "Entrepreneur, Business Developer, Manager, Directeur Commercial, Consultant.",
     200000, 250000],

    ['Expression Orale & Leadership', 'IBIG LEADERSHIP & COMMUNICATION',
     'Prise de parole en public : captiver, convaincre et marquer',
     "Devenez un orateur d'exception capable de captiver son auditoire, de convaincre avec authenticité et de laisser une impression durable dans tous vos interventions.",
     "• Préparation mentale et physique avant l'intervention\n• Techniques d'accroche et d'engagement de l'auditoire\n• Gestion du temps et du rythme\n• Récupération après une erreur ou un blanc\n• Développement de votre style oratoire personnel",
     "Toute personne souhaitant devenir un excellent orateur pour convaincre, inspirer et influencer son entourage professionnel.",
     "Conférencier, Leader, Manager, Commercial, Formateur, Consultant.",
     200000, 250000],

    ['L\'Art de Convaincre & la Persuasion', 'IBIG LEADERSHIP & COMMUNICATION',
     "L'art de convaincre sans forcer",
     "Maîtrisez les principes de la persuasion et de l'influence pour convaincre vos interlocuteurs sans pression, en créant une adhésion naturelle et durable.",
     "• Principes fondamentaux de la persuasion\n• Techniques d'influence éthique\n• Communication assertive et impact\n• Construction de la crédibilité et de la confiance\n• Persuasion en situation de résistance",
     "Managers, négociateurs, commerciaux, consultants et toute personne souhaitant développer sa capacité à influencer positivement.",
     "Négociateur, Manager, Commercial, Consultant, Directeur.",
     200000, 250000],

    ['Storytelling & Communication Narrative', 'IBIG LEADERSHIP & COMMUNICATION',
     'Storytelling : raconter pour convaincre et vendre',
     "Maîtrisez l'art du storytelling pour transformer vos communications professionnelles en récits captivants qui engagent, convainquent et mémorisent votre message.",
     "• Les structures narratives qui fonctionnent\n• Application du storytelling en entreprise et dans la vente\n• Création de récits autour de votre marque\n• Storytelling digital et réseaux sociaux\n• Techniques de présentation narrative",
     "Managers, marketers, commerciaux, dirigeants et toute personne souhaitant rendre sa communication plus captivante et mémorable.",
     "Responsable Communication, Marketing Manager, Commercial, Entrepreneur, Manager.",
     200000, 250000],

    ['Négociation & Relations Professionnelles', 'IBIG LEADERSHIP & COMMUNICATION',
     'Négociation : obtenir plus sans détériorer la relation',
     "Développez vos compétences de négociateur pour obtenir les meilleurs accords tout en préservant et renforçant vos relations professionnelles.",
     "• Préparation et stratégie de négociation\n• Techniques de négociation raisonnée\n• Gestion des émotions et de la pression\n• Négociation en situation de rapport de force\n• Conclusion et mise en œuvre des accords",
     "Managers, dirigeants, acheteurs, commerciaux, RH et toute personne amenée à négocier dans son activité professionnelle.",
     "Directeur des Achats, Responsable Commercial, Manager, Consultant, Négociateur.",
     200000, 250000],
];

// ─── GÉNÉRATION DES SLUGS ─────────────────────────────────────────────────────
function makeSlug(string $str): string {
    $str = mb_strtolower($str, 'UTF-8');
    $str = str_replace(['é','è','ê','ë','à','â','ä','î','ï','ô','ö','ù','û','ü','ç','œ','æ'],
                       ['e','e','e','e','a','a','a','i','i','o','o','u','u','u','c','oe','ae'], $str);
    $str = preg_replace('/[^a-z0-9]+/', '-', $str);
    return trim($str, '-');
}

// ─── VÉRIFICATION DES SLUGS EXISTANTS ────────────────────────────────────────
$existingSlugs = $pdo->query("SELECT slug FROM formations")->fetchAll(PDO::FETCH_COLUMN);
$existingSet = array_flip($existingSlugs);

// ─── MODE PREVIEW (sans confirm) ─────────────────────────────────────────────
if ($confirm !== 'oui') {
    echo '<html><head><meta charset="utf-8"><title>Preview insertions</title>
    <style>body{font-family:monospace;background:#0d1f3c;color:#e2e8f0;padding:20px;font-size:12px}
    h1,h2{color:#f59e0b}table{border-collapse:collapse;width:100%;margin-bottom:20px}
    th{background:#1e3a6e;color:#fff;padding:6px 10px;text-align:left}
    td{padding:5px 10px;border-bottom:1px solid #1e3a6e}
    .skip{color:#f87171}.ok{color:#34d399}.btn{display:inline-block;padding:12px 24px;
    background:#f59e0b;color:#000;border-radius:6px;text-decoration:none;font-weight:bold;margin:16px 0}
    </style></head><body>';
    echo '<h1>📋 Preview — Nouvelles formations à insérer</h1>';

    $collections = [];
    $toInsert = 0; $toSkip = 0;
    foreach ($formations as $f) {
        $slug = makeSlug($f[0]);
        $collections[$f[1]][] = ['titre' => $f[0], 'slug' => $slug, 'exists' => isset($existingSet[$slug])];
        if (isset($existingSet[$slug])) $toSkip++; else $toInsert++;
    }

    echo "<p>Formations à insérer : <strong class='ok'>$toInsert</strong> | À ignorer (slug existant) : <strong class='skip'>$toSkip</strong></p>";

    foreach ($collections as $col => $items) {
        echo "<h2>$col (" . count($items) . " formations)</h2><table><tr><th>Titre</th><th>Slug</th><th>Action</th></tr>";
        foreach ($items as $i) {
            $cls = $i['exists'] ? 'skip' : 'ok';
            $action = $i['exists'] ? '⚠ SLUG EXISTANT — ignoré' : '✓ À insérer';
            echo "<tr><td>" . htmlspecialchars($i['titre']) . "</td><td>" . $i['slug'] . "</td><td class='$cls'>$action</td></tr>";
        }
        echo '</table>';
    }

    echo '<a class="btn" href="?confirm=oui">🚀 CONFIRMER ET INSÉRER EN BASE</a>';
    echo '</body></html>';
    exit;
}

// ─── INSERTION RÉELLE ─────────────────────────────────────────────────────────
$stmtF = $pdo->prepare("
    INSERT INTO formations (titre, slug, domaine, description, tarif_en_ligne, tarif_presentiel,
        statut, date_debut, mois, annee, is_samedi_pro, montant_inscription, capacite,
        lien_paiement, type_formation, mode, frais_inscription)
    VALUES (?,?,?,?,?,?,'active','2027-03-01','Mars',2027,0,50000,20,
        'https://ibig-eduform.mychariow.shop/prd_ai7tvt/checkout','GROUPEE','hybride',50000)
");

$stmtL = $pdo->prepare("
    INSERT INTO formation_landings (formation_id, hero_title, hero_subtitle, pitch, avantages, public_cible, debouches,
        objectif_general, seo_title, seo_description, modalites_participation, contacts)
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?)
");

$inserted = []; $skipped = [];

foreach ($formations as $f) {
    [$titre, $domaine, $hero_sub, $pitch, $avantages, $public_cible, $debouches, $tl, $tp] = $f;
    $slug = makeSlug($titre);

    if (isset($existingSet[$slug])) {
        $skipped[] = $titre;
        continue;
    }

    // Insérer formation
    $stmtF->execute([$titre, $slug, $domaine, $pitch, $tl, $tp]);
    $fid = (int)$pdo->lastInsertId();

    // Mettre à jour les tarifs explicites
    $pdo->prepare("UPDATE formations SET tarif_en_ligne=?, tarif_presentiel=? WHERE id=?")->execute([$tl, $tp, $fid]);

    // Landing page
    $seo_title = $titre . ' | Formation Professionnelle IBIG EDUFORM';
    $seo_desc = mb_substr($pitch, 0, 155);
    $modalites = "Tarif en ligne : " . number_format($tl, 0, ',', ' ') . " FCFA · Présentiel : " . number_format($tp, 0, ',', ' ') . " FCFA\nFormule Samedi Pro : règlement en totalité, sans frais d'inscription. Places limitées.";
    $contacts = "📞 +225 07 79 59 98 98 · ✉ contact@ibig-eduform.com · 🌐 www.ibig-eduform.com";

    $stmtL->execute([$fid, $titre, $hero_sub, $pitch, $avantages, $public_cible, $debouches,
        $pitch, $seo_title, $seo_desc, $modalites, $contacts]);

    $inserted[] = "[$domaine] $titre (id=$fid)";
    $existingSet[$slug] = true; // éviter les doublons dans la même session
}

// ─── RÉSULTAT ─────────────────────────────────────────────────────────────────
echo '<html><head><meta charset="utf-8"><title>Résultat insertions</title>
<style>body{font-family:monospace;background:#0d1f3c;color:#e2e8f0;padding:20px;font-size:12px}
h1{color:#f59e0b}h2{color:#34d399}h3{color:#f87171}pre{background:#1e3a6e;padding:10px;border-radius:4px;white-space:pre-wrap}
</style></head><body>';
echo '<h1>✅ Insertion terminée</h1>';
echo '<h2>' . count($inserted) . ' formations insérées</h2>';
echo '<pre>' . implode("\n", $inserted) . '</pre>';
if ($skipped) {
    echo '<h3>' . count($skipped) . ' formations ignorées (slug existant)</h3>';
    echo '<pre>' . implode("\n", $skipped) . '</pre>';
}
echo '</body></html>';
