<?php
require_once __DIR__ . '/../core/bootstrap.php';
header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><title>Insertion formations lot 2</title>
<style>
body{font-family:sans-serif;max-width:900px;margin:40px auto;padding:0 20px}
.ok{color:#16a34a;font-weight:bold}.err{color:#dc2626;font-weight:bold}
.skip{color:#d97706}.row{padding:4px 0;border-bottom:1px solid #e5e7eb;font-size:.9rem}
h2{margin-top:30px;color:#1e3a5f}
</style>
</head>
<body>
<h1>Insertion formations manquantes — Lot 2 (31 formations)</h1>
<?php

$formations = [

    /* ── COMPTABILITÉ & FINANCE ── */
    [
        'titre'            => 'Agent de Crédit & Microfinance — Instruction et suivi des prêts',
        'domaine'          => 'Comptabilité & Finance',
        'description'      => 'Les Systèmes Financiers Décentralisés (SFD), coopératives d\'épargne-crédit et institutions de microfinance constituent un acteur majeur du financement en Côte d\'Ivoire. L\'Agent de Crédit est le professionnel clé de ce secteur : il analyse la situation financière des demandeurs, monte les dossiers de prêt, évalue la capacité de remboursement, effectue les visites terrain et assure le suivi des échéances. Cette formation couvre l\'analyse crédit (particuliers et micro-entreprises), les procédures de montage et d\'octroi, la gestion du portefeuille, les techniques de recouvrement amiable et les outils digitaux de gestion des prêts. Conforme aux normes BCEAO et au cadre réglementaire OHADA.',
        'duree'            => '4 jours (32h)',
        'mode'             => 'hybride',
        'tarif_en_ligne'   => 210000,
        'tarif_presentiel' => 260000,
        'slug'             => 'agent-credit-microfinance-instruction-suivi-prets',
    ],
    [
        'titre'            => 'Contrôleur de Gestion Opérationnel — Tableaux de bord & reporting',
        'domaine'          => 'Comptabilité & Finance',
        'description'      => 'Le Contrôleur de Gestion Opérationnel est le garant de la performance économique de l\'entreprise : il élabore les budgets, suit les réalisations, analyse les écarts et produit les tableaux de bord destinés à la direction. Cette formation pratique couvre le cycle budgétaire complet, la comptabilité analytique et le calcul des coûts, la conception de tableaux de bord sous Excel et Power BI, le reporting mensuel et les prévisions glissantes. Les cas pratiques sont issus du contexte ivoirien (commerce, industrie, services) et s\'appuient sur le plan comptable SYSCOHADA. Idéal pour les comptables, chefs comptables ou assistants financiers souhaitant évoluer vers le contrôle de gestion.',
        'duree'            => '5 jours (40h)',
        'mode'             => 'hybride',
        'tarif_en_ligne'   => 230000,
        'tarif_presentiel' => 280000,
        'slug'             => 'controleur-gestion-operationnel-tableaux-bord-reporting',
    ],
    [
        'titre'            => 'Trésorier d\'Entreprise — Gestion de trésorerie & relations bancaires',
        'domaine'          => 'Comptabilité & Finance',
        'description'      => 'La trésorerie est le nerf de la guerre pour toute entreprise : un résultat bénéficiaire ne protège pas d\'une crise de liquidité. Cette formation forme aux fondamentaux de la gestion de trésorerie : établissement du budget de trésorerie, suivi des flux encaissements/décaissements, optimisation du besoin en fonds de roulement (BFR), gestion des excédents (placements), négociation des lignes de crédit bancaires et gestion des risques de change. Pratique et orientée PME, elle inclut des exercices sur Excel et les outils de cash management. Adapté aux directeurs financiers débutants, responsables administratifs et comptables prenant en charge la fonction trésorerie.',
        'duree'            => '3 jours (24h)',
        'mode'             => 'hybride',
        'tarif_en_ligne'   => 210000,
        'tarif_presentiel' => 260000,
        'slug'             => 'tresorier-entreprise-gestion-tresorerie-relations-bancaires',
    ],

    /* ── BANQUE & ASSURANCE ── */
    [
        'titre'            => 'Chargé de Clientèle Bancaire — Accueil, conseil & vente de produits financiers',
        'domaine'          => 'Banque & Assurance',
        'description'      => 'Première interface entre la banque et ses clients, le Chargé de Clientèle Bancaire accueille, oriente, conseille et vend les produits et services financiers de l\'établissement. Cette formation couvre les fondamentaux bancaires (types de comptes, crédits à la consommation, crédits immobiliers, épargne, assurance-vie), les techniques de vente en agence, la connaissance réglementaire (KYC, lutte anti-blanchiment, conformité BCEAO), la gestion des réclamations et la fidélisation client. Contenu adapté à l\'environnement bancaire ouest-africain, incluant les opérations de banque mobile et les services de paiement digital (Wave, Orange Money, MTN MoMo).',
        'duree'            => '4 jours (32h)',
        'mode'             => 'hybride',
        'tarif_en_ligne'   => 210000,
        'tarif_presentiel' => 260000,
        'slug'             => 'charge-clientele-bancaire-accueil-conseil-vente-produits-financiers',
    ],
    [
        'titre'            => 'Gestionnaire Sinistres — Assurance IARD & Assurance Vie',
        'domaine'          => 'Banque & Assurance',
        'description'      => 'Le Gestionnaire Sinistres est au cœur de la promesse d\'une compagnie d\'assurance : il instruit les dossiers sinistres, mandate les experts, évalue les préjudices et règle les indemnisations. Cette formation couvre les deux grandes branches : l\'assurance IARD (automobile, habitation, risques professionnels) et l\'assurance vie. Le contenu inclut l\'ouverture et l\'instruction des dossiers, la gestion des relations avec les assurés et les prestataires, l\'analyse de la responsabilité, la négociation des règlements et les recours. Adapté aux réglementations de la zone CIMA (Conférence Interafricaine des Marchés d\'Assurances).',
        'duree'            => '4 jours (32h)',
        'mode'             => 'hybride',
        'tarif_en_ligne'   => 210000,
        'tarif_presentiel' => 260000,
        'slug'             => 'gestionnaire-sinistres-assurance-iard-assurance-vie',
    ],

    /* ── GESTION COMMERCIALE & MARKETING ── */
    [
        'titre'            => 'Manager de Point de Vente — Animation, stocks & performance commerciale',
        'domaine'          => 'Gestion Commerciale & Marketing',
        'description'      => 'Gérer un magasin, une boutique, une pharmacie ou un supermarché requiert des compétences à la fois commerciales, managériales et logistiques. Cette formation couvre la gestion quotidienne d\'un point de vente : animation de l\'équipe, gestion des stocks et des approvisionnements, organisation du merchandising, suivi des ventes et des marges, gestion des encaissements et des réclamations clients, et reporting à la direction. Les participants apprennent à utiliser les indicateurs clés (CA, marge, taux de rotation, indice de vente) pour piloter la performance et mettre en place des actions correctives. Adapté aux réalités du commerce de détail en Afrique de l\'Ouest.',
        'duree'            => '3 jours (24h)',
        'mode'             => 'hybride',
        'tarif_en_ligne'   => 210000,
        'tarif_presentiel' => 260000,
        'slug'             => 'manager-point-vente-animation-stocks-performance-commerciale',
    ],
    [
        'titre'            => 'Délégué Médical & Représentant Pharmaceutique',
        'domaine'          => 'Gestion Commerciale & Marketing',
        'description'      => 'Le Délégué Médical est l\'ambassadeur d\'un laboratoire pharmaceutique auprès des prescripteurs (médecins, pharmaciens, professionnels de santé) : il présente les produits, argumente sur les bénéfices cliniques, gère son secteur géographique et prend les commandes. Cette formation spécialisée couvre les techniques de visite médicale, la présentation scientifique des produits, la réglementation pharmaceutique en Afrique de l\'Ouest, la gestion du portefeuille clients, le reporting d\'activité et les techniques de vente adaptées au secteur santé. Programme conçu en partenariat avec les pratiques des principaux grossistes répartiteurs et laboratoires présents en Côte d\'Ivoire.',
        'duree'            => '4 jours (32h)',
        'mode'             => 'hybride',
        'tarif_en_ligne'   => 220000,
        'tarif_presentiel' => 270000,
        'slug'             => 'delegue-medical-representant-pharmaceutique',
    ],
    [
        'titre'            => 'Téléconseiller & Gestion d\'un Centre d\'Appels',
        'domaine'          => 'Gestion Commerciale & Marketing',
        'description'      => 'Les centres d\'appels sont devenus un pilier du service client, des télécoms, des banques et du e-commerce en Afrique. Cette formation couvre les deux dimensions du métier : la performance individuelle du téléconseiller (communication téléphonique professionnelle, gestion des appels entrants/sortants, scripts de vente, traitement des objections et des réclamations) et les fondamentaux du management de centre d\'appels (organisation des équipes, outils CRM, indicateurs de performance comme le DMT, le taux de décrochage, la satisfaction client). Axé sur la pratique avec simulations d\'appels et coaching en temps réel.',
        'duree'            => '3 jours (24h)',
        'mode'             => 'hybride',
        'tarif_en_ligne'   => 200000,
        'tarif_presentiel' => 250000,
        'slug'             => 'teleconseiller-gestion-centre-appels',
    ],
    [
        'titre'            => 'Promoteur des Ventes & Animateur Commercial en Point de Vente',
        'domaine'          => 'Gestion Commerciale & Marketing',
        'description'      => 'Le Promoteur des Ventes anime et met en valeur les produits directement en point de vente (grande surface, marché, pharmacie, station-service). Il assure la mise en rayon, le facing, les démonstrations produits, les dégustations et les opérations promotionnelles. Cette formation développe les techniques d\'animation commerciale terrain, la communication produit percutante, la gestion des relations avec les chefs de rayon, le reporting des ventes et des actions de la concurrence. Programme très adapté aux professionnels du FMCG, des boissons, des cosmétiques, des télécoms et de l\'agroalimentaire en Afrique de l\'Ouest.',
        'duree'            => '2 jours (16h)',
        'mode'             => 'presentiel',
        'tarif_en_ligne'   => 200000,
        'tarif_presentiel' => 250000,
        'slug'             => 'promoteur-ventes-animateur-commercial-point-vente',
    ],

    /* ── BTP & CONSTRUCTION ── */
    [
        'titre'            => 'Conducteur de Travaux Junior — Gestion et suivi de chantier',
        'domaine'          => 'BTP & Construction',
        'description'      => 'Le Conducteur de Travaux est le chef d\'orchestre du chantier : il planifie les interventions, coordonne les corps de métiers, gère les approvisionnements, contrôle la qualité des réalisations et assure la sécurité des travailleurs. Cette formation accompagne les techniciens BTP, contremaîtres et ingénieurs juniors dans la prise en main du rôle de conducteur de travaux : lecture des plans d\'exécution, établissement du planning de chantier (GANTT), gestion du budget chantier, réunions de chantier, rédaction des comptes rendus et gestion des relations avec le maître d\'ouvrage. Cas pratiques sur des projets de construction résidentielle et infrastructure en contexte africain.',
        'duree'            => '4 jours (32h)',
        'mode'             => 'hybride',
        'tarif_en_ligne'   => 220000,
        'tarif_presentiel' => 270000,
        'slug'             => 'conducteur-travaux-junior-gestion-suivi-chantier',
    ],
    [
        'titre'            => 'Métreur-Estimateur BTP — Devis, quantitatifs & prix de revient',
        'domaine'          => 'BTP & Construction',
        'description'      => 'La maîtrise des coûts est un enjeu critique dans le secteur du bâtiment. Le Métreur-Estimateur quantifie les matériaux, établit les devis détaillés, analyse les prix de revient et prépare les réponses aux appels d\'offres. Cette formation couvre les techniques de métré (lecture et interprétation des plans, méthodes de quantification), l\'établissement des avant-métrés et devis quantitatifs, l\'analyse des prix unitaires, la constitution des dossiers d\'appel d\'offres et l\'utilisation des logiciels de métrés (Excel avancé, logiciels spécialisés). Axée sur les normes et pratiques du marché ivoirien et de la zone CEDEAO.',
        'duree'            => '3 jours (24h)',
        'mode'             => 'hybride',
        'tarif_en_ligne'   => 210000,
        'tarif_presentiel' => 260000,
        'slug'             => 'metreur-estimateur-btp-devis-quantitatifs-prix-revient',
    ],

    /* ── INFORMATIQUE & DIGITAL ── */
    [
        'titre'            => 'Technicien de Maintenance Informatique & Réseaux — Support IT niveau 1 & 2',
        'domaine'          => 'Informatique & Digital',
        'description'      => 'Le Technicien de Maintenance Informatique assure le bon fonctionnement du parc informatique d\'une entreprise : installation et configuration des postes de travail, maintenance préventive et curative, support utilisateurs (helpdesk), administration des réseaux locaux (LAN/WiFi) et gestion des imprimantes et périphériques. Cette formation couvre le hardware (composants PC, diagnostics de pannes), les systèmes d\'exploitation (Windows, Linux bases), les réseaux informatiques (TCP/IP, DHCP, DNS, VPN), la cybersécurité de base et les outils de ticketing. À l\'issue de la formation, le technicien est autonome pour gérer le parc informatique d\'une PME ou d\'une filiale d\'entreprise.',
        'duree'            => '5 jours (40h)',
        'mode'             => 'hybride',
        'tarif_en_ligne'   => 220000,
        'tarif_presentiel' => 270000,
        'slug'             => 'technicien-maintenance-informatique-reseaux-support-it',
    ],
    [
        'titre'            => 'Création de Site Web avec WordPress — Pour Non-Développeurs',
        'domaine'          => 'Informatique & Digital',
        'description'      => 'WordPress propulse plus de 40 % des sites web dans le monde et reste la solution la plus accessible pour créer une présence en ligne professionnelle sans coder. Cette formation pratique permet à tout professionnel de créer, personnaliser et administrer son propre site : installation et configuration, choix et personnalisation d\'un thème professionnel, création de pages et articles, intégration de formulaires de contact, référencement SEO de base, sécurisation du site et gestion des extensions indispensables (WooCommerce pour le e-commerce, Elementor pour le design). Idéal pour les PME, associations, entrepreneurs et chargés de communication souhaitant gérer leur site web en autonomie.',
        'duree'            => '3 jours (24h)',
        'mode'             => 'hybride',
        'tarif_en_ligne'   => 200000,
        'tarif_presentiel' => 250000,
        'slug'             => 'creation-site-web-wordpress-non-developpeurs',
    ],
    [
        'titre'            => 'Développeur d\'Applications No-Code & Low-Code — Make, Bubble & Glide',
        'domaine'          => 'IA & Digitalisation',
        'description'      => 'La révolution no-code permet de créer des applications métier, automatiser des processus et développer des outils numériques sans écrire une seule ligne de code. Cette formation couvre les principales plateformes : Make (ex-Integromat) pour l\'automatisation des flux de travail, Bubble pour la création d\'applications web complètes, et Glide pour les applications mobiles à partir de Google Sheets. Les participants apprennent à identifier les processus automatisables dans leur entreprise, concevoir des flux d\'automatisation, créer des interfaces utilisateurs fonctionnelles et connecter leurs outils (CRM, comptabilité, e-mail, WhatsApp). Formation très pratique avec un projet réel réalisé pendant le parcours.',
        'duree'            => '4 jours (32h)',
        'mode'             => 'hybride',
        'tarif_en_ligne'   => 220000,
        'tarif_presentiel' => 270000,
        'slug'             => 'developpeur-applications-no-code-low-code-make-bubble-glide',
    ],
    [
        'titre'            => 'Analyste de Données Business — Excel Avancé, Power Query & SQL',
        'domaine'          => 'Bureautique & Data',
        'description'      => 'L\'analyse de données est devenue une compétence fondamentale pour tout professionnel qui prend des décisions. Cette formation intensive développe les compétences d\'analyse de données à un niveau opérationnel, sans nécessiter de bagage informatique : maîtrise d\'Excel avancé (tableaux croisés dynamiques, fonctions de recherche et de calcul complexes, graphiques avancés), Power Query pour l\'import et la transformation de données hétérogènes, et initiation au SQL pour interroger des bases de données. À l\'issue du parcours, le stagiaire est capable d\'analyser un jeu de données business, d\'identifier des tendances, de produire des rapports visuels et de formuler des recommandations basées sur les données.',
        'duree'            => '4 jours (32h)',
        'mode'             => 'hybride',
        'tarif_en_ligne'   => 210000,
        'tarif_presentiel' => 260000,
        'slug'             => 'analyste-donnees-business-excel-avance-power-query-sql',
    ],
    [
        'titre'            => 'Photographe & Vidéaste Professionnel d\'Entreprise',
        'domaine'          => 'Création de Contenu',
        'description'      => 'Dans un monde où le visuel domine la communication, la capacité à produire des photos et vidéos professionnelles est un atout stratégique pour toute organisation. Cette formation couvre les fondamentaux de la prise de vue professionnelle (composition, lumière, réglages appareil), la photographie corporate (portraits, événements, produits), la vidéo d\'entreprise (cadrage, mouvement, son), le montage vidéo avec des outils accessibles (CapCut, DaVinci Resolve, Adobe Premiere Rush) et la retouche photo avec Lightroom. Le stagiaire repart avec un portfolio réalisé pendant la formation et la capacité à produire des contenus visuels pour les réseaux sociaux, le site web et les communications internes.',
        'duree'            => '3 jours (24h)',
        'mode'             => 'presentiel',
        'tarif_en_ligne'   => 200000,
        'tarif_presentiel' => 250000,
        'slug'             => 'photographe-videaste-professionnel-entreprise',
    ],

    /* ── HÔTELLERIE, TOURISME & RESTAURATION ── */
    [
        'titre'            => 'Réceptionniste Hôtelier & Agent d\'Accueil Professionnel',
        'domaine'          => 'Direction & Administration',
        'description'      => 'Le Réceptionniste est le premier visage de l\'établissement hôtelier : il assure l\'accueil des clients, gère les réservations, effectue les opérations de check-in/check-out, traite les demandes et les réclamations, et coordonne les services internes (ménage, restauration, conciergerie). Cette formation couvre les standards internationaux de l\'accueil hôtelier, les systèmes de gestion hôtelière (PMS), les procédures d\'arrivée et de départ, la facturation, les techniques de communication avec une clientèle internationale, et les bases de l\'anglais hôtelier. Adapté aux hôtels, résidences de tourisme et établissements d\'hébergement en Côte d\'Ivoire et dans la sous-région.',
        'duree'            => '3 jours (24h)',
        'mode'             => 'hybride',
        'tarif_en_ligne'   => 200000,
        'tarif_presentiel' => 250000,
        'slug'             => 'receptionniste-hotelier-agent-accueil-professionnel',
    ],
    [
        'titre'            => 'Manager de Restauration — Gestion de salle & coordination F&B',
        'domaine'          => 'Direction & Administration',
        'description'      => 'Le Manager de Restauration est responsable de la qualité du service, de la rentabilité et de la satisfaction client dans un établissement de restauration. Cette formation couvre la gestion opérationnelle d\'un restaurant : organisation du service en salle, management de l\'équipe (serveurs, commis), gestion des coûts Food & Beverage (calcul du food cost, gestion des stocks alimentaires), hygiène et sécurité alimentaire (HACCP), relations avec les fournisseurs, caisse et reporting quotidien. Programme adapté aux réalités de la restauration africaine : restauration rapide, maquis, restaurants d\'entreprise, traiteurs événementiels et restaurants d\'hôtel.',
        'duree'            => '3 jours (24h)',
        'mode'             => 'hybride',
        'tarif_en_ligne'   => 200000,
        'tarif_presentiel' => 250000,
        'slug'             => 'manager-restauration-gestion-salle-coordination-fb',
    ],
    [
        'titre'            => 'Agent de Voyages & Tourisme — Billetterie, GDS & conception de séjours',
        'domaine'          => 'Direction & Administration',
        'description'      => 'L\'Agent de Voyages est le professionnel qui conçoit, vend et organise des voyages et séjours pour une clientèle individuelle ou professionnelle. Cette formation couvre l\'utilisation des systèmes de distribution globaux (GDS Amadeus), la billetterie aérienne internationale, la conception et la tarification de packages touristiques, les procédures de visa et de documentation de voyage, la vente de produits d\'assurance voyage et les outils digitaux de réservation. Elle inclut également les fondamentaux du tourisme d\'affaires (MICE : réunions, incentives, conférences et événements). Adapté aux agences de voyages, tour-opérateurs et services voyages d\'entreprise.',
        'duree'            => '4 jours (32h)',
        'mode'             => 'hybride',
        'tarif_en_ligne'   => 210000,
        'tarif_presentiel' => 260000,
        'slug'             => 'agent-voyages-tourisme-billetterie-gds-conception-sejours',
    ],

    /* ── AGRICULTURE & AGROBUSINESS ── */
    [
        'titre'            => 'Responsable de Coopérative Agricole — Gouvernance & gestion financière',
        'domaine'          => 'Agriculture & Agrobusiness',
        'description'      => 'La Côte d\'Ivoire compte des milliers de coopératives agricoles dans les filières cacao, café, anacarde, hévéa et palmier à huile. Beaucoup peinent à se structurer et à satisfaire aux exigences de leurs donneurs d\'ordres et des certifications (Fairtrade, UTZ, Rainforest). Cette formation accompagne les dirigeants et gestionnaires de coopératives dans la maîtrise des fondamentaux : gouvernance coopérative et rôle des organes, gestion comptable et financière simplifiée (SYSCOHADA), accès aux financements institutionnels, relations avec les acheteurs et les exportateurs, et conformité aux exigences des certifications durables. Programme adapté aux coopératives de producteurs de la zone forestière et de savane.',
        'duree'            => '4 jours (32h)',
        'mode'             => 'hybride',
        'tarif_en_ligne'   => 200000,
        'tarif_presentiel' => 250000,
        'slug'             => 'responsable-cooperative-agricole-gouvernance-gestion-financiere',
    ],
    [
        'titre'            => 'Commercialisation des Produits Agricoles & Agro-transformation',
        'domaine'          => 'Agriculture & Agrobusiness',
        'description'      => 'Produire ne suffit pas : savoir vendre et valoriser sa production est un enjeu stratégique pour les agriculteurs, groupements et entreprises agro-industrielles. Cette formation couvre la mise en marché des produits agricoles bruts et transformés : analyse des filières et des circuits de commercialisation, stratégie de prix, accès aux marchés locaux et régionaux (marchés de gros, supermarchés, restauration), développement de l\'agro-transformation à valeur ajoutée, normes de qualité et traçabilité, emballage et étiquetage professionnels, et initiation à l\'export vers les marchés régionaux (CEDEAO) et internationaux.',
        'duree'            => '3 jours (24h)',
        'mode'             => 'hybride',
        'tarif_en_ligne'   => 200000,
        'tarif_presentiel' => 250000,
        'slug'             => 'commercialisation-produits-agricoles-agro-transformation',
    ],

    /* ── SECTEUR PUBLIC & ONG ── */
    [
        'titre'            => 'Rédacteur Administratif & Communication Officielle pour l\'Administration Publique',
        'domaine'          => 'Direction & Administration',
        'description'      => 'La maîtrise de la rédaction administrative est une compétence fondamentale dans les services de l\'État, les collectivités locales, les établissements publics et les grandes organisations. Cette formation couvre les règles et les codes de la communication administrative officielle : les différents types d\'actes (circulaires, notes de service, arrêtés, comptes rendus, procès-verbaux), la structure et le style administratifs, les formules de politesse codifiées, la gestion du courrier entrant et sortant, l\'archivage réglementaire et la correspondance officielle par voie électronique. Conforme aux pratiques de l\'administration ivoirienne et aux standards de la CEDEAO.',
        'duree'            => '3 jours (24h)',
        'mode'             => 'hybride',
        'tarif_en_ligne'   => 200000,
        'tarif_presentiel' => 250000,
        'slug'             => 'redacteur-administratif-communication-officielle-administration-publique',
    ],
    [
        'titre'            => 'Suivi-Évaluation & Reporting de Projets de Développement',
        'domaine'          => 'ONG & Développement',
        'description'      => 'Le Suivi-Évaluation (S&E) est une compétence clé pour toute organisation qui met en œuvre des projets financés par des bailleurs internationaux (USAID, Union Européenne, Banque Mondiale, Nations Unies, AFD). Cette formation couvre les fondamentaux du cycle de projet, la conception d\'un cadre logique (logframe), la définition des indicateurs SMART, la mise en place d\'un système de collecte de données, la rédaction des rapports d\'avancement (progress reports) et la préparation des évaluations à mi-parcours et finales. Inclut une initiation aux outils de collecte de données digitale (KoBoToolbox, ODK) et au reporting selon les standards des principaux bailleurs.',
        'duree'            => '4 jours (32h)',
        'mode'             => 'hybride',
        'tarif_en_ligne'   => 210000,
        'tarif_presentiel' => 260000,
        'slug'             => 'suivi-evaluation-reporting-projets-developpement',
    ],
    [
        'titre'            => 'Montage de Dossiers de Financement & Recherche de Subventions',
        'domaine'          => 'Entrepreneuriat',
        'description'      => 'Obtenir un financement est souvent l\'obstacle principal pour les PME, startups, ONG et coopératives. Cette formation pratique développe la capacité à identifier les sources de financement adaptées (banques de développement, fonds d\'investissement, appels à projets institutionnels, business angels, crowdfunding) et à constituer des dossiers solides. Le programme couvre la rédaction du business plan finançable, l\'élaboration du plan financier prévisionnel, la préparation des documents administratifs requis, la présentation du projet aux décideurs financiers (pitch) et le suivi post-dépôt. Cas pratiques sur des appels à projets réels de la BCEAO, de l\'Agence Française de Développement et du Programme d\'Appui aux PME ivoiriennes.',
        'duree'            => '3 jours (24h)',
        'mode'             => 'hybride',
        'tarif_en_ligne'   => 210000,
        'tarif_presentiel' => 260000,
        'slug'             => 'montage-dossiers-financement-recherche-subventions',
    ],

    /* ── SANTÉ & SÉCURITÉ AU TRAVAIL ── */
    [
        'titre'            => 'Secouriste du Travail (SST) — Premiers Secours & Gestion des Urgences',
        'domaine'          => 'QHSE',
        'description'      => 'Le Secouriste du Travail (SST) est le premier intervenant en cas d\'accident ou de malaise sur le lieu de travail. Cette formation pratique et réglementaire couvre les techniques de premiers secours : la Protection-Alerte-Secours (PAS), la réanimation cardio-pulmonaire (RCP) et le défibrillateur automatisé externe (DAE), les gestes face aux hémorragies, pertes de connaissance, brûlures, fractures et malaises. Elle inclut également la prévention des risques en entreprise : identification des situations dangereuses, mise en place des équipements de premiers secours et rédaction d\'un plan d\'urgence interne. Conforme aux standards de la Croix-Rouge et de la réglementation travail ivoirienne.',
        'duree'            => '2 jours (16h)',
        'mode'             => 'presentiel',
        'tarif_en_ligne'   => 200000,
        'tarif_presentiel' => 250000,
        'slug'             => 'secouriste-travail-sst-premiers-secours-gestion-urgences',
    ],
    [
        'titre'            => 'Gestion Administrative d\'un Établissement de Santé — Clinique & Hôpital Privé',
        'domaine'          => 'Santé & Médecine',
        'description'      => 'La gestion administrative d\'une clinique ou d\'un hôpital privé est un métier à part entière : facturation patients (assurances, CNAM, paiements directs), gestion des dossiers médicaux, coordination entre les services administratifs et soignants, gestion des stocks de médicaments et consommables, suivi des conventions avec les mutuelles et compagnies d\'assurance. Cette formation couvre les spécificités administratives du secteur santé en Côte d\'Ivoire : le cadre réglementaire (MSLS, CNAM), les procédures de prise en charge, la facturation CNAM, la gestion informatisée des dossiers patients et le reporting d\'activité. Adapté aux responsables administratifs, secrétaires médicales et gestionnaires de cliniques privées.',
        'duree'            => '3 jours (24h)',
        'mode'             => 'hybride',
        'tarif_en_ligne'   => 210000,
        'tarif_presentiel' => 260000,
        'slug'             => 'gestion-administrative-etablissement-sante-clinique-hopital-prive',
    ],

    /* ── MANAGEMENT & LEADERSHIP ── */
    [
        'titre'            => 'Prise de Parole en Public & Présentation Professionnelle Impactante',
        'domaine'          => 'Management & Leadership',
        'description'      => 'Prendre la parole devant un auditoire — client, direction, partenaires ou collaborateurs — est une compétence qui s\'apprend et se développe. Cette formation intensive et très pratique libère la parole et construit la confiance : maîtrise du stress et de la respiration, techniques de préparation d\'une prise de parole, structure d\'un discours percutant, langage corporel et gestuelle, utilisation des supports visuels (PowerPoint, Prezi), gestion des questions-réponses et prise de parole improvisée. Chaque participant prend la parole plusieurs fois lors de la formation et reçoit un feedback individualisé du formateur. Indispensable pour les managers, commerciaux, consultants et toute personne amenée à représenter son organisation.',
        'duree'            => '2 jours (16h)',
        'mode'             => 'presentiel',
        'tarif_en_ligne'   => 200000,
        'tarif_presentiel' => 250000,
        'slug'             => 'prise-de-parole-public-presentation-professionnelle-impactante',
    ],
    [
        'titre'            => 'Intelligence Émotionnelle & Relations Interpersonnelles au Travail',
        'domaine'          => 'Management & Leadership',
        'description'      => 'L\'intelligence émotionnelle (IE) est désormais reconnue comme un facteur déterminant de la performance professionnelle et du bien-être au travail. Cette formation aide les professionnels à mieux se connaître, réguler leurs émotions, développer l\'empathie et améliorer la qualité de leurs relations avec leurs collègues, collaborateurs et clients. Le programme couvre les 5 composantes de l\'IE selon le modèle de Goleman (connaissance de soi, maîtrise de soi, motivation, empathie, compétences sociales), les techniques de gestion du stress et des conflits interpersonnels, et la communication non-violente (CNV). Format participatif avec mises en situation et ateliers de groupe.',
        'duree'            => '2 jours (16h)',
        'mode'             => 'hybride',
        'tarif_en_ligne'   => 200000,
        'tarif_presentiel' => 250000,
        'slug'             => 'intelligence-emotionnelle-relations-interpersonnelles-travail',
    ],
    [
        'titre'            => 'Gestion du Stress & Prévention du Burn-out — Performance durable',
        'domaine'          => 'Développement Personnel',
        'description'      => 'Le stress professionnel et le burn-out sont des réalités croissantes dans les entreprises africaines. Cette formation donne aux participants les outils concrets pour identifier leurs sources de stress, réguler leurs réactions émotionnelles et retrouver un équilibre durable entre performance et bien-être. Le programme couvre la compréhension des mécanismes du stress (physiologie, psychologie), les signaux d\'alerte du burn-out, les techniques de régulation immédiate (respiration, ancrage, relaxation) et les stratégies de prévention à long terme (organisation personnelle, limites professionnelles, récupération). Contenu adapté aux managers, commerciaux, soignants et tout professionnel exposé à une forte charge de travail.',
        'duree'            => '2 jours (16h)',
        'mode'             => 'hybride',
        'tarif_en_ligne'   => 200000,
        'tarif_presentiel' => 250000,
        'slug'             => 'gestion-stress-prevention-burn-out-performance-durable',
    ],
    [
        'titre'            => 'Anglais Professionnel des Affaires — Communication orale & écrite en entreprise',
        'domaine'          => 'Direction & Administration',
        'description'      => 'L\'anglais est incontournable dans les échanges commerciaux internationaux, les réunions avec des partenaires étrangers et la rédaction de correspondances professionnelles. Cette formation intensive est axée sur la pratique de l\'anglais en contexte professionnel : réunions et téléconférences en anglais, rédaction d\'e-mails, de rapports et de comptes rendus, présentation orale de projets, négociation commerciale et entretiens professionnels. Le niveau visé correspond à B1-B2 du CECRL. Programme conçu pour des professionnels francophones ayant des bases en anglais et souhaitant gagner en aisance et en efficacité dans leurs échanges professionnels internationaux.',
        'duree'            => '4 jours (32h)',
        'mode'             => 'hybride',
        'tarif_en_ligne'   => 210000,
        'tarif_presentiel' => 260000,
        'slug'             => 'anglais-professionnel-affaires-communication-orale-ecrite-entreprise',
    ],
    [
        'titre'            => 'Protocole d\'État & Étiquette Professionnelle — Événementiel & Relations officielles',
        'domaine'          => 'Direction & Administration',
        'description'      => 'Le protocole et l\'étiquette professionnelle sont des compétences essentielles pour les assistants de direction, les chargés de communication, les responsables d\'organisations recevant des personnalités ou organisant des événements officiels. Cette formation couvre les règles du protocole d\'État (préséances, présentations officielles, cérémonies), l\'organisation d\'événements institutionnels (réceptions, inaugurations, cérémonies de remise de prix), les codes de l\'étiquette en réunion et à table, la gestion des invitations officielles, le placement à table et les relations avec les autorités. Adapté aux entreprises, ONG, institutions et collectivités organisant des événements à dimension officielle.',
        'duree'            => '2 jours (16h)',
        'mode'             => 'presentiel',
        'tarif_en_ligne'   => 200000,
        'tarif_presentiel' => 250000,
        'slug'             => 'protocole-etat-etiquette-professionnelle-evenementiel-relations-officielles',
    ],

];

// ── Connexion DB ────────────────────────────────────────────────
try {
    $pdo = Database::connect();
} catch (Throwable $e) {
    die('<p class="err">Erreur DB : ' . htmlspecialchars($e->getMessage()) . '</p>');
}

$insertSql = "INSERT INTO formations
    (titre, domaine, description, duree, mode, tarif_en_ligne, tarif_presentiel, slug, statut, is_samedi_pro)
    VALUES (:titre, :domaine, :description, :duree, :mode, :tarif_en_ligne, :tarif_presentiel, :slug, 'active', 0)
    ON DUPLICATE KEY UPDATE
        titre=VALUES(titre), description=VALUES(description),
        duree=VALUES(duree), mode=VALUES(mode),
        tarif_en_ligne=VALUES(tarif_en_ligne),
        tarif_presentiel=VALUES(tarif_presentiel),
        statut='active'";

$stmtCheck  = $pdo->prepare("SELECT id FROM formations WHERE slug = :slug LIMIT 1");
$stmtInsert = $pdo->prepare($insertSql);

$nbInserted = $nbUpdated = $nbError = 0;

echo "<h2>Résultats</h2>";
foreach ($formations as $f) {
    if ($f['tarif_en_ligne'] < 200000 || $f['tarif_presentiel'] < 250000) {
        echo "<div class='row'><span class='err'>⛔ TARIF INVALIDE</span> — {$f['titre']}</div>";
        $nbError++; continue;
    }
    $stmtCheck->execute([':slug' => $f['slug']]);
    $exists = $stmtCheck->fetchColumn();
    try {
        $stmtInsert->execute([
            ':titre'           => $f['titre'],
            ':domaine'         => $f['domaine'],
            ':description'     => $f['description'],
            ':duree'           => $f['duree'],
            ':mode'            => $f['mode'],
            ':tarif_en_ligne'  => $f['tarif_en_ligne'],
            ':tarif_presentiel'=> $f['tarif_presentiel'],
            ':slug'            => $f['slug'],
        ]);
        if ($exists) {
            echo "<div class='row'><span class='skip'>🔄 MIS À JOUR</span> — " . htmlspecialchars($f['titre']) . " <small>({$f['domaine']})</small></div>";
            $nbUpdated++;
        } else {
            echo "<div class='row'><span class='ok'>✅ INSÉRÉ</span> — " . htmlspecialchars($f['titre']) . " <small>({$f['domaine']})</small></div>";
            $nbInserted++;
        }
    } catch (Throwable $e) {
        echo "<div class='row'><span class='err'>❌ ERREUR</span> — " . htmlspecialchars($f['titre']) . " : " . htmlspecialchars($e->getMessage()) . "</div>";
        $nbError++;
    }
}
echo "<h2>Bilan</h2><p><strong class='ok'>✅ {$nbInserted} insérée(s)</strong> | <strong class='skip'>🔄 {$nbUpdated} mise(s) à jour</strong> | <strong class='err'>❌ {$nbError} erreur(s)</strong></p>";
?>
</body></html>
