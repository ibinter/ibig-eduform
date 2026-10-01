<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';
Middleware::requireAuth();
$pdo = Database::connect();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$confirm = $_GET['confirm'] ?? '';

// ─── CATALOGUE FORMATIONS ENTREPRENEURIAT ─────────────────────────────────────
$formations = [

  [
    'slug'            => 'creer-lancer-entreprise-cote-ivoire',
    'titre'           => 'Créer et lancer son entreprise en Côte d\'Ivoire',
    'domaine'         => 'Entrepreneuriat',
    'type_certificat' => 'Certificat professionnel',
    'duree'           => '20H',
    'mode'            => 'hybride',
    'tarif_en_ligne'  => 225000,
    'tarif_presentiel'=> 285000,
    'description'     => "Parcours complet de 20 heures pour transformer une idée en entreprise opérationnelle en Côte d'Ivoire. De la validation de l'idée à l'immatriculation au RCCM, en passant par le business model, le financement de démarrage et les premières ventes. Référence : IBIG-ENT-CRE-CI.",
    'objectifs'       => "Valider la viabilité commerciale d'une idée d'entreprise\nDéfinir son modèle économique (Business Model Canvas)\nConnaître les formes juridiques et choisir la structure adaptée\nImmatriculer son entreprise au RCCM et obtenir ses identifiants fiscaux\nEstimer son capital de démarrage et identifier les sources de financement\nRédiger un plan d'affaires simplifié pour convaincre\nLancer ses premières actions commerciales et acquérir ses premiers clients\nMettre en place une gestion administrative et financière de base",
    'modules'         => "M1 — De l'idée au projet (3h) : validation de l'idée, analyse de marché rapide, étude de la concurrence, choix du positionnement\nM2 — Business Model Canvas (2h) : proposition de valeur, segments clients, canaux, revenus et coûts\nM3 — Cadre juridique et création (3h) : SARL, SAS, EI, RCCM, DFE, identifiant fiscal, compte bancaire professionnel\nM4 — Financement de démarrage (3h) : capital personnel, famille et amis, microfinance, FIDEN, Versus Bank, SFI, subventions ONUDI\nM5 — Plan d'affaires simplifié (3h) : structure, projections financières à 3 ans, présentation aux partenaires\nM6 — Premiers clients et lancement (3h) : stratégie de démarrage, prospection, réseaux sociaux, bouche-à-oreille, premiers contrats\nM7 — Gestion de base et évaluation (3h) : trésorerie, facturation, obligations fiscales initiales, tableau de bord entrepreneur",
  ],

  [
    'slug'            => 'business-plan-rediger-convaincre',
    'titre'           => 'Business Plan : rédiger et convaincre',
    'domaine'         => 'Entrepreneuriat',
    'type_certificat' => 'Certificat professionnel',
    'duree'           => '20H',
    'mode'            => 'hybride',
    'tarif_en_ligne'  => 225000,
    'tarif_presentiel'=> 285000,
    'description'     => "Formation pratique de 20 heures pour rédiger un business plan professionnel convaincant. Travail sur le projet réel du participant : analyse de marché, modèle économique, projections financières sur 3 ans, stratégie de financement. Document finalisé remis à l'issue du parcours. Référence : IBIG-ENT-BP.",
    'objectifs'       => "Structurer un business plan selon les standards attendus par les banques et investisseurs\nRéaliser une étude de marché exploitable\nDéfinir une stratégie commerciale et un plan marketing\nConstruire un prévisionnel financier sur 3 ans (compte de résultat, bilan, trésorerie)\nCalculer le seuil de rentabilité et le besoin en fonds de roulement\nPrésenter son projet à l'oral (pitch de 10 minutes)\nAdapter le document selon le destinataire (banquier, investisseur, concours)",
    'modules'         => "M1 — Structure et méthode (2h) : les parties d'un BP, les erreurs à éviter, adapter selon le lecteur\nM2 — Présentation du projet et de l'équipe (2h) : executive summary, présentation fondateurs, genèse du projet\nM3 — Étude de marché (3h) : analyse macro, concurrence, clients cibles, taille du marché, enquête terrain\nM4 — Offre et stratégie commerciale (3h) : produit/service, pricing, canaux de distribution, plan marketing\nM5 — Prévisionnel financier (5h) : hypothèses, CA prévisionnel, charges, P&L, trésorerie, bilan, ratios clés\nM6 — Seuil de rentabilité et financement (2h) : BEP, BFR, plan de financement, apports et emprunts\nM7 — Présentation et simulation (3h) : pitch 10 min, questions-réponses simulées, finalisation du document",
  ],

  [
    'slug'            => 'lean-startup-tester-valider-idee',
    'titre'           => 'Lean Startup : tester et valider son idée avant d\'investir',
    'domaine'         => 'Entrepreneuriat',
    'type_certificat' => 'Certificat professionnel',
    'duree'           => '15H',
    'mode'            => 'hybride',
    'tarif_en_ligne'  => 170000,
    'tarif_presentiel'=> 215000,
    'description'     => "Formation de 15 heures sur la méthode Lean Startup appliquée au contexte africain. Apprendre à tester une hypothèse commerciale à moindre coût via un MVP, recueillir des retours clients réels et pivoter si nécessaire avant d'engager des ressources importantes. Référence : IBIG-ENT-LS.",
    'objectifs'       => "Comprendre les principes du Lean Startup et du Design Thinking\nIdentifier ses hypothèses clés (clients, problème, solution, canal)\nConstruire un MVP (Minimum Viable Product) adapté au marché africain\nMettre en place des expériences de validation terrain\nAnalyser les retours clients et décider entre persévérer et pivoter\nIntégrer les cycles Build-Measure-Learn dans sa démarche\nRéduire le risque d'échec par l'itération rapide",
    'modules'         => "M1 — Pourquoi les startups échouent (2h) : statistiques, principales causes, le mythe du plan parfait\nM2 — Lean Startup et Design Thinking (2h) : principes, build-measure-learn, empathie client\nM3 — Cartographie des hypothèses (2h) : hypothèses de problème, solution, canal, prix\nM4 — Le MVP en contexte africain (3h) : types de MVP (concierge, wizard of Oz, landing page, prototype papier), exemples locaux\nM5 — Test et collecte de données (3h) : entretiens clients, A/B tests, métriques pirate (AARRR)\nM6 — Pivoter ou persévérer (3h) : analyse des données, décision de pivot, ajustement du business model",
  ],

  [
    'slug'            => 'pitch-communication-entrepreneur',
    'titre'           => 'Pitch & art de convaincre pour entrepreneurs',
    'domaine'         => 'Entrepreneuriat',
    'type_certificat' => 'Certificat professionnel',
    'duree'           => '15H',
    'mode'            => 'hybride',
    'tarif_en_ligne'  => 170000,
    'tarif_presentiel'=> 215000,
    'description'     => "Formation de 15 heures pour maîtriser l'art du pitch et de la communication persuasive en contexte entrepreneurial. De l'elevator pitch de 60 secondes au pitch deck de 10 minutes face à des investisseurs, en passant par la prise de parole en public et la gestion du stress. Référence : IBIG-ENT-PCH.",
    'objectifs'       => "Construire un pitch percutant adapté au temps disponible (30s, 3min, 10min)\nConcevoir un pitch deck visuel et convaincant\nMaîtriser sa communication verbale et non verbale\nGérer le stress et les questions difficiles d'investisseurs\nAdapter son message selon le profil de l'interlocuteur (banquier, investisseur, client, partenaire)\nUtiliser le storytelling pour rendre son projet mémorable\nS'entraîner par des simulations filmées avec feedback",
    'modules'         => "M1 — Les bases de la communication persuasive (2h) : pyramide de Monroe, rhétorique d'Aristote, posture\nM2 — L'elevator pitch (2h) : structure en 60s, les 3 éléments indispensables, exercices chronométrés\nM3 — Pitch deck 10 minutes (3h) : 12 slides essentielles, design, données clés, démonstration\nM4 — Storytelling entrepreneurial (2h) : héros, problème, transformation, preuve sociale\nM5 — Communication non verbale (2h) : voix, regard, posture, gestion du trac\nM6 — Simulations filmées et feedback (4h) : pitchs enregistrés, questions-réponses, amélioration continue",
  ],

  [
    'slug'            => 'financement-entreprise-fonds-banques-investisseurs',
    'titre'           => 'Financement de l\'entreprise : fonds propres, banques et investisseurs',
    'domaine'         => 'Entrepreneuriat',
    'type_certificat' => 'Certificat professionnel',
    'duree'           => '20H',
    'mode'            => 'hybride',
    'tarif_en_ligne'  => 225000,
    'tarif_presentiel'=> 285000,
    'description'     => "Formation de 20 heures sur les sources et stratégies de financement pour entrepreneurs et dirigeants de PME en Afrique. Fonds propres, crédit bancaire, capital-risque, crowdfunding, subventions publiques et organismes de développement : comment accéder à chaque source et préparer un dossier convaincant. Référence : IBIG-ENT-FIN.",
    'objectifs'       => "Identifier les sources de financement adaptées à chaque étape de l'entreprise\nPréparer un dossier de crédit bancaire solide\nComprendre le mécanisme des fonds de capital-risque et business angels\nUtiliser le crowdfunding et les plateformes de financement participatif\nAccéder aux subventions et financements des organismes de développement (BM, BAD, BCEAO, ONUDI)\nNégocier les conditions de financement\nÉviter les pièges de l'endettement excessif",
    'modules'         => "M1 — Cartographie des financements disponibles en Côte d'Ivoire (2h) : panorama, critères d'éligibilité\nM2 — L'autofinancement et les fonds propres (2h) : bootstrapping, famille, réinvestissement\nM3 — Le crédit bancaire (4h) : dossier, garanties, ratios bancaires, renégociation\nM4 — Capital-risque et business angels (3h) : mécanismes, term sheet, dilution, sortie\nM5 — Crowdfunding et financement participatif (2h) : plateformes africaines, campagne réussie\nM6 — Organismes publics et subventions (4h) : FIDEN, FGSP-PME, BCEAO, BAD, ONUDI, AFD, MCA\nM7 — Négociation et montage financier (3h) : optimisation de la structure financière, simulations",
  ],

  [
    'slug'            => 'strategie-croissance-pme-africaines',
    'titre'           => 'Stratégie de croissance pour PME africaines',
    'domaine'         => 'Entrepreneuriat',
    'type_certificat' => 'Certificat professionnel',
    'duree'           => '25H',
    'mode'            => 'hybride',
    'tarif_en_ligne'  => 280000,
    'tarif_presentiel'=> 355000,
    'description'     => "Formation de 25 heures pour dirigeants de PME souhaitant accélérer et structurer leur croissance. Diagnostic stratégique, identification des leviers de croissance, expansion géographique, partenariats, digitalisation et pilotage par les indicateurs. Approche terrain ancrée dans les réalités des marchés africains. Référence : IBIG-ENT-STR.",
    'objectifs'       => "Réaliser un diagnostic stratégique complet de son entreprise\nIdentifier les principaux leviers de croissance rentable\nConcevoir une stratégie d'expansion (nouveaux marchés, nouveaux produits)\nConstruire des partenariats stratégiques durables\nDigitaliser les processus clés pour gagner en efficacité\nMettre en place un tableau de bord stratégique\nAnticiper et gérer les risques liés à la croissance",
    'modules'         => "M1 — Diagnostic stratégique (3h) : SWOT, chaîne de valeur, analyse concurrentielle PESTEL\nM2 — Modèles de croissance (3h) : matrice Ansoff, croissance organique vs acquisition\nM3 — Conquête de nouveaux marchés (4h) : étude d'opportunité, ZLECAF, expansion sous-régionale\nM4 — Partenariats et alliances stratégiques (3h) : identifier, négocier et gérer ses partenaires\nM5 — Digitalisation de la PME (4h) : outils ERP, CRM, paiement mobile, e-commerce B2B\nM6 — Finance et pilotage de la croissance (4h) : indicateurs clés, tableau de bord, gestion du BFR\nM7 — Plan de croissance sur 3 ans (4h) : feuille de route, jalons, ressources et financement",
  ],

  [
    'slug'            => 'marketing-digital-entrepreneurs',
    'titre'           => 'Marketing digital pour entrepreneurs',
    'domaine'         => 'Entrepreneuriat',
    'type_certificat' => 'Certificat professionnel',
    'duree'           => '25H',
    'mode'            => 'hybride',
    'tarif_en_ligne'  => 280000,
    'tarif_presentiel'=> 355000,
    'description'     => "Formation de 25 heures pour entrepreneurs souhaitant maîtriser les outils du marketing digital et générer des clients en ligne. Réseaux sociaux, publicité Meta/Google, email marketing, SEO, WhatsApp Business et stratégie de contenu adaptés au marché ivoirien et africain. Référence : IBIG-ENT-MKT.",
    'objectifs'       => "Élaborer une stratégie de marketing digital adaptée à son secteur\nCréer et optimiser sa présence sur les réseaux sociaux (Facebook, Instagram, LinkedIn, TikTok)\nConcevoir des publicités efficaces sur Meta Ads et Google Ads\nDévelopper une stratégie SEO pour être visible sur Google\nUtiliser WhatsApp Business et email marketing pour fidéliser\nAnalyser les performances et optimiser les campagnes\nGénérer des leads qualifiés à moindre coût",
    'modules'         => "M1 — Stratégie digitale et positionnement (3h) : persona, tunnel de conversion, choix des canaux\nM2 — Réseaux sociaux pour entrepreneurs (4h) : Facebook, Instagram, LinkedIn, TikTok — stratégie de contenu\nM3 — Publicité payante Meta Ads (4h) : ciblage, formats, budget, A/B test, reporting\nM4 — Google Ads et SEO (4h) : référencement naturel, mots-clés, Google My Business, campagnes search\nM5 — WhatsApp Business et email marketing (3h) : automatisation, campagnes, listes, taux d'ouverture\nM6 — Création de contenu et copywriting (4h) : textes qui vendent, visuels, reels, stories, calendrier éditorial\nM7 — Analyse et optimisation (3h) : Meta Pixel, Google Analytics, tableaux de bord, ROI",
  ],

  [
    'slug'            => 'gestion-financiere-entrepreneur',
    'titre'           => 'Gestion financière pour entrepreneur non-financier',
    'domaine'         => 'Entrepreneuriat',
    'type_certificat' => 'Certificat professionnel',
    'duree'           => '20H',
    'mode'            => 'hybride',
    'tarif_en_ligne'  => 225000,
    'tarif_presentiel'=> 285000,
    'description'     => "Formation de 20 heures pour entrepreneurs et dirigeants de PME sans formation comptable. Comprendre ses états financiers, piloter sa trésorerie, calculer sa rentabilité et prendre les bonnes décisions financières. Approche 100 % pratique basée sur les chiffres réels du participant. Référence : IBIG-ENT-GF.",
    'objectifs'       => "Lire et interpréter un bilan et un compte de résultat\nGérer sa trésorerie au quotidien et anticiper les tensions\nCalculer ses marges, son seuil de rentabilité et son BFR\nDistinguer résultat comptable et flux de trésorerie\nPrendre des décisions d'investissement éclairées\nMettre en place un tableau de bord financier simple\nDialoguer efficacement avec son expert-comptable et son banquier",
    'modules'         => "M1 — Les états financiers en clair (3h) : bilan, compte de résultat, flux de trésorerie — lecture et interprétation\nM2 — Trésorerie et prévision (3h) : plan de trésorerie, décalages de paiement, optimisation du BFR\nM3 — Rentabilité et marges (3h) : marge brute, marge nette, seuil de rentabilité, point mort\nM4 — Coûts et pricing (3h) : calcul du coût de revient, fixation du prix de vente, analyse des écarts\nM5 — Financement et investissement (3h) : ROI, VAN, TRI, choix d'investissement, crédit-bail\nM6 — Tableau de bord et pilotage (3h) : KPI financiers, reporting mensuel, alerte sur déviations\nM7 — Simulation sur cas réels (2h) : exercices sur chiffres réels du participant",
  ],

  [
    'slug'            => 'ecommerce-vente-ligne-afrique',
    'titre'           => 'E-commerce et vente en ligne en Afrique',
    'domaine'         => 'Entrepreneuriat',
    'type_certificat' => 'Certificat professionnel',
    'duree'           => '20H',
    'mode'            => 'hybride',
    'tarif_en_ligne'  => 225000,
    'tarif_presentiel'=> 285000,
    'description'     => "Formation de 20 heures pour créer, lancer et développer une boutique en ligne profitable en Afrique. De la création de la boutique (Shopify, WooCommerce, plateformes africaines) à la logistique, en passant par les paiements mobile money et la fidélisation. Référence : IBIG-ENT-ECO.",
    'objectifs'       => "Choisir la plateforme e-commerce adaptée à son marché\nCréer et configurer une boutique en ligne professionnelle\nIntégrer les paiements mobile money (Wave, Orange Money, MTN MoMo)\nGérer les stocks, commandes et livraisons\nAttirer des acheteurs via les réseaux sociaux et la publicité\nFidéliser sa clientèle en ligne\nComprendre les aspects juridiques et fiscaux du e-commerce",
    'modules'         => "M1 — E-commerce en Afrique : opportunités et spécificités (2h) : marché, comportements d'achat, défis logistiques\nM2 — Créer sa boutique en ligne (4h) : Shopify, WooCommerce, Jumia, Afrimarket — comparatif et installation\nM3 — Paiements et sécurité (3h) : Mobile money, CinetPay, Stripe, Wave Business — intégration et sécurité\nM4 — Gestion des stocks et logistique (3h) : gestion des commandes, partenaires livraison, retours\nM5 — Attirer et convertir les visiteurs (4h) : SEO produit, publicité sociale, influenceurs, promotions\nM6 — Service client et fidélisation (2h) : chat, WhatsApp, avis clients, programme de fidélité\nM7 — Fiscalité et juridique e-commerce (2h) : TVA sur services numériques, CGV, RGPD local",
  ],

  [
    'slug'            => 'intrapreneuriat-innover-entreprise',
    'titre'           => 'Intrapreneuriat : innover au sein de son organisation',
    'domaine'         => 'Entrepreneuriat',
    'type_certificat' => 'Certificat professionnel',
    'duree'           => '20H',
    'mode'            => 'hybride',
    'tarif_en_ligne'  => 225000,
    'tarif_presentiel'=> 285000,
    'description'     => "Formation de 20 heures pour développer l'esprit entrepreneurial au sein d'une organisation existante. Identifier des opportunités d'innovation, concevoir des projets intrapreneuriaux, convaincre la hiérarchie et piloter le changement de l'intérieur. Référence : IBIG-ENT-INT.",
    'objectifs'       => "Comprendre les principes de l'intrapreneuriat et ses bénéfices pour l'organisation\nDévelopper un état d'esprit entrepreneurial en contexte salarié\nIdentifier des opportunités d'innovation dans son secteur\nConcevoir et structurer un projet innovant interne\nConvaincre sa hiérarchie et obtenir les ressources nécessaires\nPiloter un projet d'intrapreneuriat de A à Z\nMesurer l'impact et valoriser ses résultats",
    'modules'         => "M1 — L'intrapreneuriat : définition, exemples et enjeux (2h) : Google 20%, Amazon, Orange, Airtel Africa\nM2 — Mindset entrepreneurial en entreprise (3h) : prise d'initiative, tolérance au risque, autonomie\nM3 — Identifier et évaluer les opportunités d'innovation (3h) : Design Thinking, carte d'empathie, benchmark\nM4 — Concevoir son projet interne (4h) : brief innovant, MVP, prototype, test rapide\nM5 — Convaincre et vendre son projet en interne (3h) : pitch management, analyse coûts-bénéfices, plan d'action\nM6 — Piloter le projet et gérer le changement (3h) : méthodes agiles, parties prenantes, indicateurs\nM7 — Valoriser et dupliquer l'expérience (2h) : rapport de résultats, essaimage, reconnaissance",
  ],

  [
    'slug'            => 'formalisation-juridique-rccm-ohada',
    'titre'           => 'Formalisation juridique et RCCM en droit OHADA',
    'domaine'         => 'Entrepreneuriat',
    'type_certificat' => 'Certificat professionnel',
    'duree'           => '15H',
    'mode'            => 'hybride',
    'tarif_en_ligne'  => 170000,
    'tarif_presentiel'=> 215000,
    'description'     => "Formation de 15 heures sur les démarches juridiques pour créer, formaliser et sécuriser une entreprise dans l'espace OHADA. Choix de la forme juridique, rédaction des statuts, immatriculation RCCM, conformité fiscale et sociale. Référence : IBIG-ENT-JUR.",
    'objectifs'       => "Connaître les formes juridiques disponibles dans l'espace OHADA (SARL, SA, SAS, GIE, EI)\nChoisir la structure juridique adaptée à son projet\nComprendre le rôle et le fonctionnement du RCCM\nRédiger ou faire rédiger des statuts conformes\nObtenir ses identifiants fiscaux et numéros d'immatriculation\nConnaître les obligations légales périodiques (assemblées générales, dépôt des comptes)\nProtéger son entreprise et ses associés",
    'modules'         => "M1 — Panorama du droit OHADA des sociétés (2h) : Acte Uniforme, RCCM, CRRG\nM2 — Formes juridiques comparées (3h) : EI, SARL, SAS, SA, GIE — capital, gouvernance, responsabilité, fiscalité\nM3 — Immatriculation RCCM pas à pas (3h) : dossier, délais, frais, DFE, CNPS, patente\nM4 — Statuts et pacte d'associés (3h) : clauses essentielles, droits et obligations, protection des fondateurs\nM5 — Obligations légales périodiques (2h) : AG annuelle, dépôt des comptes, modifications statutaires\nM6 — Cas pratiques et questions-réponses (2h) : simulations, erreurs fréquentes, ressources utiles",
  ],

  [
    'slug'            => 'leadership-management-fondateurs',
    'titre'           => 'Leadership et management pour fondateurs de startup',
    'domaine'         => 'Entrepreneuriat',
    'type_certificat' => 'Certificat professionnel',
    'duree'           => '20H',
    'mode'            => 'hybride',
    'tarif_en_ligne'  => 225000,
    'tarif_presentiel'=> 285000,
    'description'     => "Formation de 20 heures pour founders et dirigeants qui souhaitent développer leurs compétences de leadership et construire une équipe performante. Passer du mode solo à celui de chef d'orchestre : recruter, motiver, déléguer et créer une culture d'entreprise forte. Référence : IBIG-ENT-LDR.",
    'objectifs'       => "Identifier son style de leadership et ses axes de développement\nRecruter les bonnes personnes aux bons postes\nDéléguer efficacement sans perdre le contrôle\nMotiver ses collaborateurs en contexte de ressources limitées\nGérer les conflits et maintenir la cohésion d'équipe\nConstruire une culture d'entreprise alignée sur sa vision\nCommuniquer sa vision et fédérer autour d'objectifs communs",
    'modules'         => "M1 — Du fondateur au leader (2h) : transition, syndromes du fondateur, délégation\nM2 — Styles de leadership (3h) : situationnel, transformationnel, servant leadership — tests et autodiagnostic\nM3 — Recruter et intégrer (3h) : définir le profil, sourcing, entretien structuré, onboarding\nM4 — Déléguer et responsabiliser (3h) : matrice de délégation, suivi sans micro-management, OKR\nM5 — Motivation et engagement (3h) : théories de motivation, reconnaissance non-monétaire, carrière\nM6 — Culture d'entreprise (3h) : valeurs, rituels, communication interne, employer branding\nM7 — Gestion des conflits (3h) : médiation, communication non violente, décisions difficiles",
  ],

  [
    'slug'            => 'negociation-commerciale-entrepreneurs',
    'titre'           => 'Négociation commerciale pour entrepreneurs',
    'domaine'         => 'Entrepreneuriat',
    'type_certificat' => 'Certificat professionnel',
    'duree'           => '20H',
    'mode'            => 'hybride',
    'tarif_en_ligne'  => 225000,
    'tarif_presentiel'=> 285000,
    'description'     => "Formation de 20 heures pour entrepreneurs souhaitant négocier efficacement avec clients, fournisseurs, partenaires et investisseurs. Techniques de négociation raisonnée, gestion des objections, conclusion et suivi des accords. Simulations intensives sur des cas réels du participant. Référence : IBIG-ENT-NEG.",
    'objectifs'       => "Préparer une négociation commerciale de manière structurée\nComprendre les styles et tactiques de négociation\nIdentifier les intérêts de toutes les parties\nGérer les objections avec confiance\nConclure un accord gagnant-gagnant\nNégocier en contexte de rapport de force déséquilibré\nRédiger et sécuriser ses accords commerciaux",
    'modules'         => "M1 — Fondamentaux de la négociation (2h) : positions vs intérêts, BATNA, ZOPA, valeur créée\nM2 — Préparation stratégique (3h) : analyse de la partie adverse, objectifs, concessions planifiées\nM3 — Techniques et tactiques (3h) : ancrage, découpage, silence, réciprocité, urgence\nM4 — Gestion des objections (3h) : écoute active, reformulation, technique CAB, retournement\nM5 — Négociation avec fournisseurs et investisseurs (3h) : spécificités, points sensibles, red lines\nM6 — Simulations en conditions réelles (4h) : jeux de rôle filmés, débriefing, amélioration\nM7 — Formalisation et suivi des accords (2h) : lettres d'intention, contrats simples, suivi",
  ],

  [
    'slug'            => 'entrepreneuriat-feminin-creer-diriger',
    'titre'           => 'Entrepreneuriat féminin : créer, financer et diriger',
    'domaine'         => 'Entrepreneuriat',
    'type_certificat' => 'Certificat professionnel',
    'duree'           => '20H',
    'mode'            => 'hybride',
    'tarif_en_ligne'  => 225000,
    'tarif_presentiel'=> 285000,
    'description'     => "Formation de 20 heures spécialement conçue pour les femmes entrepreneurs en Afrique. Construire son projet, accéder aux financements dédiés, développer son leadership féminin et bâtir un réseau professionnel solide. Formation ancrée dans les réalités et défis spécifiques des femmes entrepreneurs ivoiriennes. Référence : IBIG-ENT-FEM.",
    'objectifs'       => "Valider son projet entrepreneurial et construire son business model\nConnaître les financements spécifiques aux femmes entrepreneurs\nDévelopper son leadership et sa confiance en soi\nConstruire un réseau professionnel et une communauté d'affaires\nConcilier entrepreneuriat et contraintes personnelles\nFaire valoir ses droits en tant que femme entrepreneur (OHADA)\nDevenir une modèle et inspirer d'autres femmes entrepreneures",
    'modules'         => "M1 — État des lieux et opportunités (2h) : statistiques, freins et leviers, succès féminins africains\nM2 — Valider et structurer son projet (3h) : BMC, étude de marché, positionnement\nM3 — Financements dédiés aux femmes (3h) : AFAWA, She Trades, Renew Capital, financement islamique, tontines\nM4 — Leadership féminin (3h) : assertivité, syndrome de l'imposteur, prise de décision, réseautage\nM5 — Gestion quotidienne et organisation (3h) : time management, délégation, outils numériques\nM6 — Communication et visibilité (3h) : personal branding, réseaux sociaux, prise de parole publique\nM7 — Réseau et sororité entrepreneuriale (3h) : associations, mentoring, accélérateurs féminins en CI",
  ],

  [
    'slug'            => 'gestion-crise-resilience-entrepreneuriale',
    'titre'           => 'Gestion de crise et résilience entrepreneuriale',
    'domaine'         => 'Entrepreneuriat',
    'type_certificat' => 'Certificat professionnel',
    'duree'           => '15H',
    'mode'            => 'hybride',
    'tarif_en_ligne'  => 170000,
    'tarif_presentiel'=> 215000,
    'description'     => "Formation de 15 heures pour préparer l'entrepreneur à faire face aux crises : difficultés de trésorerie, perte d'un client majeur, crise sociale interne, catastrophe naturelle, crise sanitaire. Anticiper, réagir vite et rebondir plus fort. Référence : IBIG-ENT-CRS.",
    'objectifs'       => "Identifier les types de crises pouvant toucher une PME\nMettre en place un plan de continuité d'activité (PCA)\nGérer la communication de crise vers les parties prenantes\nPrendre des décisions rapides sous pression\nGérer les difficultés de trésorerie en période de crise\nPréserver ses équipes et maintenir le moral\nRebondir et transformer la crise en opportunité",
    'modules'         => "M1 — Typologies de crises en PME (2h) : financières, commerciales, RH, réputationnelles, externes\nM2 — Anticipation et plan de continuité (3h) : audit des risques, scénarios, PCA simplifié\nM3 — Gestion financière d'urgence (3h) : trésorerie d'urgence, négociation bancaire, délais fournisseurs\nM4 — Communication de crise (3h) : transparence, messages clés, gestion des rumeurs, réseaux sociaux\nM5 — Résilience personnelle du dirigeant (2h) : gestion du stress, réseau de soutien, prise de recul\nM6 — Rebondir après la crise (2h) : analyse post-mortem, réorientation stratégique, nouvelles opportunités",
  ],

  [
    'slug'            => 'transition-numerique-pme',
    'titre'           => 'Transition numérique de la PME',
    'domaine'         => 'Entrepreneuriat',
    'type_certificat' => 'Certificat professionnel',
    'duree'           => '20H',
    'mode'            => 'hybride',
    'tarif_en_ligne'  => 225000,
    'tarif_presentiel'=> 285000,
    'description'     => "Formation de 20 heures pour dirigeants de PME souhaitant digitaliser leur entreprise. Diagnostic numérique, choix des outils prioritaires (ERP, CRM, paiement mobile, cloud), conduite du changement et formation des équipes. Approche pragmatique centrée sur le retour sur investissement. Référence : IBIG-ENT-NUM.",
    'objectifs'       => "Réaliser un diagnostic de maturité numérique de son entreprise\nDéfinir une feuille de route de transformation numérique réaliste\nChoisir et déployer les outils numériques prioritaires\nIntégrer les paiements mobiles et le e-commerce\nFormer et accompagner ses équipes au changement numérique\nSécuriser ses données et systèmes\nMesurer le ROI de la transformation numérique",
    'modules'         => "M1 — Diagnostic numérique PME (2h) : maturité digitale, benchmark sectoriel, quick wins\nM2 — Outils de gestion (ERP, CRM) (4h) : Odoo, Sage, Zoho — choix, déploiement, coûts\nM3 — Paiements digitaux et mobile money (3h) : intégration Wave/Orange Money/MTN, facturation électronique\nM4 — Cloud et collaboration (3h) : Google Workspace, Microsoft 365, stockage, travail à distance\nM5 — Présence en ligne et e-commerce (3h) : site web, boutique, réseaux sociaux, Google My Business\nM6 — Cybersécurité pour PME (2h) : mots de passe, sauvegardes, phishing, protection des données\nM7 — Conduite du changement et ROI (3h) : formation équipes, indicateurs, plan de déploiement",
  ],

  [
    'slug'            => 'entrepreneuriat-social-economie-impact',
    'titre'           => 'Entrepreneuriat social et économie d\'impact',
    'domaine'         => 'Entrepreneuriat',
    'type_certificat' => 'Certificat professionnel',
    'duree'           => '20H',
    'mode'            => 'hybride',
    'tarif_en_ligne'  => 225000,
    'tarif_presentiel'=> 285000,
    'description'     => "Formation de 20 heures pour entrepreneurs souhaitant concilier impact social ou environnemental et viabilité économique. Modèles d'entreprise sociale, mesure d'impact, financement à impact, certification et communication RSE. Référence : IBIG-ENT-SOC.",
    'objectifs'       => "Comprendre les modèles d'entrepreneuriat social et d'économie solidaire\nConcevoir un modèle économique alliant impact et rentabilité\nDéfinir et mesurer l'impact social et environnemental\nAccéder aux financements à impact (fonds ESG, impact investing)\nCommuniquer son impact de manière crédible\nConnaître les certifications et labels disponibles\nS'inspirer de success stories africaines d'entrepreneuriat social",
    'modules'         => "M1 — Panorama de l'entrepreneuriat social en Afrique (2h) : définitions, modèles, exemples\nM2 — Concevoir son modèle socio-économique (3h) : théorie du changement, BMC adapté, double bottom line\nM3 — Mesurer l'impact (3h) : indicateurs, SROI, cartographie des parties prenantes, rapport d'impact\nM4 — Financement à impact (4h) : impact investing, fonds ESG, obligations vertes, blended finance, AFD\nM5 — Communication et transparence (3h) : rapport RSE, labels, certifications B Corp, communication authentique\nM6 — Études de cas africaines (2h) : agrotech, fintech inclusive, santé communautaire, éducation\nM7 — Plan d'impact et présentation (3h) : construction de sa stratégie d'impact sur 3 ans",
  ],

  [
    'slug'            => 'levee-fonds-relations-investisseurs',
    'titre'           => 'Levée de fonds et relations avec les investisseurs',
    'domaine'         => 'Entrepreneuriat',
    'type_certificat' => 'Certificat professionnel',
    'duree'           => '20H',
    'mode'            => 'hybride',
    'tarif_en_ligne'  => 225000,
    'tarif_presentiel'=> 285000,
    'description'     => "Formation de 20 heures pour fondateurs souhaitant lever des fonds auprès de business angels, fonds de capital-risque ou family offices. Due diligence, valorisation, term sheet, négociation des conditions et gestion des relations post-investissement. Référence : IBIG-ENT-LVF.",
    'objectifs'       => "Comprendre l'écosystème des investisseurs en Afrique et en Côte d'Ivoire\nDéterminer si sa startup est investissable et à quel stade\nPréparer son dossier de levée de fonds (data room)\nCalculer la valorisation de son entreprise\nLire et négocier un term sheet\nGérer la relation investisseur après closing\nÉviter les pièges et clauses défavorables",
    'modules'         => "M1 — Ecosystème venture capital en Afrique (2h) : VC, BA, family offices, accélérateurs — qui finance quoi\nM2 — Suis-je prêt pour lever des fonds ? (2h) : critères de sélection, product-market fit, traction\nM3 — La data room (3h) : cap table, financials, contracts, IP, équipe — ce que veut voir un VC\nM4 — Valorisation (4h) : méthodes DCF, comparables, venture method — calculer et défendre sa valorisation\nM5 — Term sheet décryptée (3h) : clauses essentielles, liquidation préférence, anti-dilution, drag-along\nM6 — Négociation et closing (3h) : tactiques, red lines, due diligence investisseur, avocat\nM7 — Post-investissement (3h) : board, reporting, communications investisseurs, préparer la prochaine levée",
  ],

  [
    'slug'            => 'fiscalite-entrepreneur-optimisation-pme',
    'titre'           => 'Fiscalité de l\'entrepreneur et optimisation fiscale PME',
    'domaine'         => 'Entrepreneuriat',
    'type_certificat' => 'Certificat professionnel',
    'duree'           => '20H',
    'mode'            => 'hybride',
    'tarif_en_ligne'  => 225000,
    'tarif_presentiel'=> 285000,
    'description'     => "Formation de 20 heures pour entrepreneurs souhaitant maîtriser leurs obligations fiscales et optimiser légalement la charge fiscale de leur PME en Côte d'Ivoire. Régimes d'imposition, TVA, impôt sur les bénéfices, charges sociales et stratégies d'optimisation dans le cadre légal. Référence : IBIG-ENT-FIS.",
    'objectifs'       => "Choisir le régime fiscal optimal pour son entreprise\nMaîtriser ses obligations fiscales et les calendriers de dépôt\nOptimiser légalement sa charge fiscale\nComprendre la TVA et les retenues à la source\nGérer l'impôt sur les bénéfices et les acomptes\nUtiliser les avantages du Code des Investissements\nSécuriser l'entreprise face à un contrôle fiscal",
    'modules'         => "M1 — Choisir son régime fiscal (2h) : microentreprise, réel simplifié, réel normal — critères et implications\nM2 — TVA pour entrepreneur (3h) : collectée, déductible, télédéclaration, facture normalisée\nM3 — Impôt sur les bénéfices (3h) : passage comptable-fiscal, BIC, BNC, IMF, acomptes\nM4 — Charges sociales et CNPS (3h) : cotisations, déclaration, risques de redressement\nM5 — Optimisation fiscale légale (4h) : amortissements accélérés, provisions, investissements, holding\nM6 — Code des investissements et avantages fiscaux (2h) : exonérations, agréments, zones franches\nM7 — Contrôle fiscal et préparation (3h) : droits du contribuable, organisation du dossier fiscal",
  ],

  [
    'slug'            => 'propriete-intellectuelle-protection-innovation',
    'titre'           => 'Propriété intellectuelle et protection de l\'innovation',
    'domaine'         => 'Entrepreneuriat',
    'type_certificat' => 'Certificat professionnel',
    'duree'           => '15H',
    'mode'            => 'hybride',
    'tarif_en_ligne'  => 170000,
    'tarif_presentiel'=> 215000,
    'description'     => "Formation de 15 heures pour entrepreneurs et inventeurs souhaitant protéger leurs innovations, marques et œuvres créatives. Brevets, marques, droits d'auteur, modèles et dessins industriels dans l'espace OAPI et international. Référence : IBIG-ENT-PI.",
    'objectifs'       => "Comprendre les différents types de propriété intellectuelle\nProtéger sa marque en Côte d'Ivoire et dans l'espace OAPI\nDéposer un brevet pour son invention\nProtéger ses logiciels, créations et œuvres numériques\nGérer les secrets d'affaires et l'information confidentielle\nRédiger des accords de confidentialité et de cession\nGérer une violation de propriété intellectuelle",
    'modules'         => "M1 — Panorama de la PI pour entrepreneurs (2h) : marques, brevets, droits d'auteur, modèles industriels\nM2 — Protéger sa marque (3h) : dépôt OAPI, OMPI, recherche d'antériorité, surveillance\nM3 — Brevets et innovations (3h) : critères de brevetabilité, dossier, coûts, exploitation\nM4 — Droits d'auteur et logiciels (2h) : protection automatique, cession, licences open source\nM5 — Secrets d'affaires et confidentialité (2h) : NDA, clauses de non-concurrence, protection interne\nM6 — Litiges et défense de ses droits (3h) : contrefaçon, recours, médiation, procédures OAPI",
  ],

  [
    'slug'            => 'agribusiness-entrepreneuriat-agricole',
    'titre'           => 'Agribusiness et entrepreneuriat agricole',
    'domaine'         => 'Entrepreneuriat',
    'type_certificat' => 'Certificat professionnel',
    'duree'           => '25H',
    'mode'            => 'hybride',
    'tarif_en_ligne'  => 280000,
    'tarif_presentiel'=> 355000,
    'description'     => "Formation de 25 heures pour entrepreneurs souhaitant créer ou développer une activité dans le secteur agro-alimentaire en Côte d'Ivoire et dans l'espace UEMOA. De la production à la transformation et à la commercialisation, avec un focus sur la chaîne de valeur, le financement agricole et l'agriculture digitale. Référence : IBIG-ENT-AGRO.",
    'objectifs'       => "Analyser les opportunités dans les filières agro-alimentaires ivoiriennes\nConstruire un modèle économique agribusiness viable\nAccéder aux financements agricoles (FIRCA, banques agricoles, fonds verts)\nMaîtriser les bases de la transformation et de la conservation agro-alimentaire\nDévelopper une stratégie de commercialisation et d'export\nUtiliser les outils numériques au service de l'agriculture\nConnaître la réglementation et les certifications du secteur",
    'modules'         => "M1 — Opportunités des filières agricoles en CI (3h) : cacao, cajou, hévéa, maraîchage, élevage — chaînes de valeur\nM2 — Business model agribusiness (3h) : BMC adapté, étude de marché, pricing agricole\nM3 — Financement agricole (4h) : FIRCA, BCEAO, Coris Bank Agri, fonds verts, warrantage\nM4 — Transformation et normes agro-alimentaires (4h) : process, HACCP, normes SPS, emballage\nM5 — Commercialisation et export (4h) : GMS, marchés publics, export UEMOA/international, certifications\nM6 — Agriculture numérique (3h) : drones, capteurs IoT, plateformes de marché digital, e-traçabilité\nM7 — Plan d'affaires agribusiness (4h) : construction du business plan agricole, présentation",
  ],

  [
    'slug'            => 'scale-up-startup-entreprise-perenne',
    'titre'           => 'Scale-up : de la startup à l\'entreprise structurée',
    'domaine'         => 'Entrepreneuriat',
    'type_certificat' => 'Certificat professionnel',
    'duree'           => '25H',
    'mode'            => 'hybride',
    'tarif_en_ligne'  => 280000,
    'tarif_presentiel'=> 355000,
    'description'     => "Formation de 25 heures pour fondateurs dont l'entreprise a trouvé son product-market fit et veut accélérer. Passer de 5 à 50 personnes, structurer ses processus, ouvrir de nouveaux marchés, lever des fonds série A et construire une marque durable. Référence : IBIG-ENT-SCL.",
    'objectifs'       => "Diagnostiquer sa maturité organisationnelle pour scaler\nStructurer ses processus et formaliser sa documentation\nRecruiter et fidéliser des talents en phase de croissance\nMettre en place une gouvernance adaptée aux investisseurs\nOuvrir de nouveaux marchés nationaux et régionaux\nPréparer une levée de fonds Série A ou Série B\nConstruire une marque forte et une culture d'entreprise durable",
    'modules'         => "M1 — Diagnostic de maturité scale-up (2h) : product-market fit, unit economics, NPS, rétention\nM2 — Structuration organisationnelle (4h) : organigramme, processus, SOP, systèmes de gestion\nM3 — Talent et culture en phase de scale (4h) : recrutement, onboarding, culture, rémunération variable\nM4 — Gouvernance et investisseurs (3h) : board, reporting, indicateurs KPI, audit préparatoire\nM5 — Expansion géographique (4h) : go-to-market régional, franchise, JV, filiales OHADA\nM6 — Financement Série A/B (4h) : story à raconter, data room avancée, valorisation, négociation\nM7 — Marque et positionnement durable (4h) : brand strategy, communication institutionnelle, réputation",
  ],

  [
    'slug'            => 'commerce-intra-africain-zlecaf',
    'titre'           => 'Commerce intra-africain et opportunités ZLECAf',
    'domaine'         => 'Entrepreneuriat',
    'type_certificat' => 'Certificat professionnel',
    'duree'           => '20H',
    'mode'            => 'hybride',
    'tarif_en_ligne'  => 225000,
    'tarif_presentiel'=> 285000,
    'description'     => "Formation de 20 heures pour entrepreneurs souhaitant tirer profit de la Zone de Libre-Échange Continentale Africaine (ZLECAf). Identifier les marchés cibles, comprendre les règles d'origine, maîtriser la logistique transfrontalière et accéder aux financements de l'export africain. Référence : IBIG-ENT-ZLC.",
    'objectifs'       => "Comprendre le mécanisme et les opportunités de la ZLECAf\nIdentifier les marchés africains à fort potentiel pour son secteur\nMaîtriser les règles d'origine et les procédures douanières simplifiées\nMontrer la logistique transfrontalière adaptée à son produit\nAccéder aux financements de l'export (Afreximbank, SFI, BRVM)\nDévelopper des partenariats commerciaux intra-africains\nConnaître les pièges et risques du commerce africain",
    'modules'         => "M1 — ZLECAf : mécanisme, état d'avancement et opportunités (3h) : règles, pays signataires, secteurs prioritaires\nM2 — Analyse de marché intra-africaine (3h) : identifier les marchés porteurs, études de cas sectorielles\nM3 — Règles d'origine et procédures douanières (3h) : certificats d'origine, UEMOA, CEDEAO, protocoles\nM4 — Logistique transfrontalière (3h) : transport, entreposage, Incoterms, assurance, corridors logistiques\nM5 — Financement de l'export africain (3h) : Afreximbank, FADEC, crédits documentaires, assurance Coface\nM6 — Partenariats et distribution en Afrique (3h) : distributeurs, agents, représentants, joint ventures\nM7 — Plan d'expansion africaine (2h) : go-to-market, roadmap, premières démarches",
  ],

  [
    'slug'            => 'entrepreneuriat-diaspora-investir',
    'titre'           => 'Entrepreneuriat diaspora : investir et entreprendre depuis l\'étranger',
    'domaine'         => 'Entrepreneuriat',
    'type_certificat' => 'Certificat professionnel',
    'duree'           => '20H',
    'mode'            => 'hybride',
    'tarif_en_ligne'  => 225000,
    'tarif_presentiel'=> 285000,
    'description'     => "Formation de 20 heures pour membres de la diaspora africaine souhaitant investir ou créer une entreprise en Côte d'Ivoire depuis l'étranger. Opportunités sectorielles, cadre juridique pour non-résidents, transferts de fonds, gestion à distance et retour progressif. Référence : IBIG-ENT-DIAS.",
    'objectifs'       => "Identifier les opportunités d'investissement et d'entreprise en Côte d'Ivoire\nComprendre le cadre juridique applicable aux non-résidents (OHADA)\nOptimiser les transferts de fonds et réduire les coûts\nCréer et gérer une entreprise à distance depuis l'étranger\nBénéficier des avantages fiscaux pour la diaspora\nConstruire une équipe et des partenaires de confiance sur place\nPlanifier un retour progressif ou une gestion hybride",
    'modules'         => "M1 — Opportunités pour la diaspora en CI (3h) : immobilier, agroalimentaire, tech, services, commerce\nM2 — Cadre juridique pour non-résidents (3h) : formes sociales, mandataire, statut de résident étranger\nM3 — Transferts de fonds et comptes bancaires (3h) : Western Union, Wave, virement SWIFT, compte en devises\nM4 — Créer et gérer à distance (4h) : outils de gestion en ligne, contrôle à distance, reporting, confiance\nM5 — Fiscalité diaspora (3h) : double imposition, conventions fiscales, optimisation légale\nM6 — Ressources et accompagnement (2h) : Invest in Côte d'Ivoire, CCI, ambassades, réseaux diaspora\nM7 — Plan d'investissement diaspora (2h) : structurer son projet de retour ou d'investissement à distance",
  ],

  [
    'slug'            => 'personal-branding-storytelling-entrepreneur',
    'titre'           => 'Personal Branding et storytelling pour entrepreneur',
    'domaine'         => 'Entrepreneuriat',
    'type_certificat' => 'Certificat professionnel',
    'duree'           => '15H',
    'mode'            => 'hybride',
    'tarif_en_ligne'  => 170000,
    'tarif_presentiel'=> 215000,
    'description'     => "Formation de 15 heures pour entrepreneurs souhaitant construire une marque personnelle forte et utiliser leur histoire pour attirer clients, partenaires et investisseurs. Stratégie de personal branding, storytelling authentique, présence LinkedIn et prise de parole publique. Référence : IBIG-ENT-PB.",
    'objectifs'       => "Définir son positionnement et sa proposition de valeur personnelle\nConstruire une image cohérente sur tous les canaux\nRédiger et raconter son histoire de manière authentique et convaincante\nOptimiser son profil LinkedIn pour l'entrepreneuriat\nDévelopper sa visibilité par la prise de parole et les médias\nUtiliser son personal brand pour générer des opportunités\nMesurer et faire évoluer son image de marque",
    'modules'         => "M1 — Qu'est-ce que le personal brand ? (2h) : définition, exemples africains, enjeux pour l'entrepreneur\nM2 — Définir son positionnement unique (2h) : forces, valeurs, cible, différenciation\nM3 — Son histoire : le storytelling authentique (3h) : arc narratif, héros, épreuves, transformation, preuve\nM4 — LinkedIn pour entrepreneurs (3h) : profil optimisé, publications, commentaires, réseau actif\nM5 — Visibilité et médias (3h) : interviews, podcasts, articles, conférences, réseaux sociaux\nM6 — Plan de personal branding sur 90 jours (2h) : calendrier éditorial, rituels, indicateurs",
  ],

  [
    'slug'            => 'gestion-ressources-humaines-pme',
    'titre'           => 'Gestion des ressources humaines pour PME',
    'domaine'         => 'Entrepreneuriat',
    'type_certificat' => 'Certificat professionnel',
    'duree'           => '20H',
    'mode'            => 'hybride',
    'tarif_en_ligne'  => 225000,
    'tarif_presentiel'=> 285000,
    'description'     => "Formation de 20 heures pour dirigeants de PME souhaitant professionnaliser la gestion de leurs ressources humaines. Recrutement, contrats de travail, paie, droit social ivoirien, gestion des performances et climat social. Référence : IBIG-ENT-RH.",
    'objectifs'       => "Maîtriser le cadre juridique du droit du travail ivoirien\nRédiger des offres d'emploi et conduire des entretiens efficaces\nRédiger les contrats de travail adaptés\nCalculer et gérer la paie dans une PME\nMettre en place un système d'évaluation simple\nGérer les conflits et les ruptures de contrat\nDévelopper le capital humain de son entreprise",
    'modules'         => "M1 — Droit du travail pour PME (3h) : Code du travail CI, conventions collectives, CNPS\nM2 — Recruter efficacement (3h) : définition de poste, sourcing, entretien, intégration\nM3 — Contrats de travail (2h) : CDD, CDI, période d'essai, clauses particulières\nM4 — Paie et charges sociales (4h) : calcul du salaire brut/net, CNPS, IR, ITS, bulletins de paie\nM5 — Gestion des performances (3h) : objectifs, entretiens annuels, feedback continu\nM6 — Conflits et ruptures de contrat (2h) : médiation, licenciement, départ volontaire, procédures\nM7 — Formation et développement des talents (3h) : plan de formation, e-learning, gestion des carrières",
  ],

  [
    'slug'            => 'import-export-commerce-international-ohada',
    'titre'           => 'Import-Export et commerce international dans l\'espace OHADA',
    'domaine'         => 'Entrepreneuriat',
    'type_certificat' => 'Certificat professionnel',
    'duree'           => '25H',
    'mode'            => 'hybride',
    'tarif_en_ligne'  => 280000,
    'tarif_presentiel'=> 355000,
    'description'     => "Formation de 25 heures pour entrepreneurs souhaitant se lancer dans le commerce international depuis la Côte d'Ivoire. Procédures douanières, Incoterms, financement des échanges internationaux, recherche de partenaires et conformité réglementaire dans l'espace OHADA et UEMOA. Référence : IBIG-ENT-IMP.",
    'objectifs'       => "Maîtriser les procédures douanières à l'import et à l'export\nComprendre et utiliser les Incoterms 2020\nFinancer ses opérations d'import-export (crédits documentaires, remises documentaires)\nIdentifier des fournisseurs et acheteurs fiables à l'international\nGérer les risques de change, de transport et de non-paiement\nConnaître les réglementations spécifiques UEMOA et OHADA\nConstituer un dossier d'agréé en douane",
    'modules'         => "M1 — Fondamentaux du commerce international (3h) : acteurs, flux, barrières, UEMOA, CEDEAO\nM2 — Incoterms 2020 (3h) : les 11 Incoterms, choix selon le transport, risques et responsabilités\nM3 — Procédures douanières CI (4h) : importation, exportation, déclaration, tarifs douaniers, régimes spéciaux\nM4 — Financement des échanges (4h) : lettre de crédit, remise documentaire, crédit acheteur, affacturage export\nM5 — Recherche de partenaires internationaux (3h) : plateformes B2B, chambres de commerce, foires\nM6 — Gestion des risques (4h) : risque de change, assurance transport, assurance crédit (Coface)\nM7 — Contrat international et plan d'action (4h) : contrat de vente, incoterm choisi, plan opérationnel",
  ],

  [
    'slug'            => 'creer-gerer-franchise',
    'titre'           => 'Créer et développer une franchise',
    'domaine'         => 'Entrepreneuriat',
    'type_certificat' => 'Certificat professionnel',
    'duree'           => '20H',
    'mode'            => 'hybride',
    'tarif_en_ligne'  => 225000,
    'tarif_presentiel'=> 285000,
    'description'     => "Formation de 20 heures sur la franchise comme modèle de développement rapide. Côté franchiseur : créer son réseau, rédiger le DIP et le contrat, former et animer les franchisés. Côté franchisé : évaluer une franchise, négocier et réussir son lancement. Référence : IBIG-ENT-FRA.",
    'objectifs'       => "Comprendre le modèle de la franchise et ses variantes\nÉvaluer si son concept est franchisable\nConcevoir son manuel opératoire et ses outils de formation\nRédiger le Document d'Information Précontractuel (DIP)\nSélectionner et accompagner ses franchisés\nÉvaluer et rejoindre un réseau de franchise existant\nConnaître le cadre juridique de la franchise dans l'espace OHADA",
    'modules'         => "M1 — La franchise : mécanisme et modèles (2h) : franchise, licence, commission-affiliation, pilotage\nM2 — Évaluer la franchisabilité de son concept (3h) : critères, audit, retour sur expérience\nM3 — Construire son système franchise (4h) : manuel opératoire, processus, outils, formation initiale\nM4 — Le DIP et le contrat de franchise (3h) : contenu légal, rédaction, obligations réciproques\nM5 — Sélectionner et accompagner ses franchisés (3h) : recrutement, animation, contrôle, réseau\nM6 — Rejoindre une franchise : côté franchisé (3h) : analyser le DIP, négocier, évaluer la rentabilité\nM7 — Cas pratiques et simulations (2h) : analyse de réseaux africains existants, questions-réponses",
  ],

  [
    'slug'            => 'entrepreneuriat-numerique-monetisation-ligne',
    'titre'           => 'Entrepreneuriat numérique et monétisation en ligne',
    'domaine'         => 'Entrepreneuriat',
    'type_certificat' => 'Certificat professionnel',
    'duree'           => '20H',
    'mode'            => 'hybride',
    'tarif_en_ligne'  => 225000,
    'tarif_presentiel'=> 285000,
    'description'     => "Formation de 20 heures pour créer et monétiser une activité numérique : infoproduits, coaching en ligne, SaaS, marketplace, contenu digital et services freelance. Stratégies de revenus récurrents, croissance par le contenu et automatisation adaptées au marché africain. Référence : IBIG-ENT-ENUM.",
    'objectifs'       => "Identifier un modèle d'affaires numérique adapté à ses compétences\nCréer et vendre des infoproduits (formations, e-books, templates)\nDévelopper une activité de coaching ou consulting en ligne\nConstruire une audience et une liste email qualifiée\nMettre en place des revenus récurrents (abonnements)\nAutomatiser ses revenus grâce aux funnels de vente\nAccéder aux paiements internationaux depuis l'Afrique",
    'modules'         => "M1 — Modèles d'affaires numériques (2h) : infoproduits, SaaS, marketplace, contenu, freelance\nM2 — Créer son offre numérique (3h) : formation en ligne, e-book, template, consulting — conception et pricing\nM3 — Construire son audience (4h) : newsletter, réseaux sociaux, SEO, YouTube, podcast\nM4 — Vendre en ligne (4h) : landing page, funnel, email séquences, webinaires, vente automatisée\nM5 — Paiements et reversements depuis l'Afrique (3h) : Stripe, PayPal, Flutterwave, Wave, virement SWIFT\nM6 — Revenus récurrents et croissance (2h) : abonnements, upsells, communautés payantes\nM7 — Plan de lancement 90 jours (2h) : roadmap, premières ventes, indicateurs",
  ],

  [
    'slug'            => 'plan-continuite-succession-entreprise',
    'titre'           => 'Plan de continuité et succession d\'entreprise familiale',
    'domaine'         => 'Entrepreneuriat',
    'type_certificat' => 'Certificat professionnel',
    'duree'           => '15H',
    'mode'            => 'hybride',
    'tarif_en_ligne'  => 170000,
    'tarif_presentiel'=> 215000,
    'description'     => "Formation de 15 heures pour dirigeants souhaitant préparer la transmission de leur entreprise ou assurer sa pérennité. Plan de succession, valorisation, aspects juridiques et fiscaux de la cession, transmission familiale et montage holding. Référence : IBIG-ENT-SUC.",
    'objectifs'       => "Diagnostiquer la dépendance de l'entreprise à son fondateur\nÉvaluer la valeur de son entreprise\nPlanifier la succession interne ou externe\nConnaître les montages juridiques de transmission\nOptimiser fiscalement la cession ou la donation\nPréparer ses héritiers ou successeurs\nMettre en place un plan de continuité d'activité",
    'modules'         => "M1 — Pourquoi préparer sa succession dès maintenant (2h) : statistiques, risques, leviers de valeur\nM2 — Évaluer son entreprise (3h) : méthodes patrimoniale, rendement, DCF, prix de marché\nM3 — Options de transmission (3h) : cession externe, transmission familiale, MBO, holding, donation\nM4 — Aspects juridiques et fiscaux (3h) : pacte Dutreil adapté OHADA, plus-values, droits de donation\nM5 — Préparer ses successeurs (2h) : plan de développement, passation de pouvoir progressive\nM6 — Plan de continuité et gouvernance (2h) : documentation, procédures, organigramme de crise",
  ],

];

// ─── VÉRIFICATIONS ────────────────────────────────────────────────────────────
$checks = [];
foreach ($formations as $i => $f) {
    $chk = $pdo->prepare("SELECT COUNT(*) FROM formations WHERE slug = ?");
    $chk->execute([$f['slug']]);
    $checks[$i] = (int)$chk->fetchColumn() > 0;
}
$a_inserer  = count(array_filter($checks, fn($v) => !$v));
$deja_existants = count(array_filter($checks, fn($v) => $v));

// ─── PREVIEW ──────────────────────────────────────────────────────────────────
if ($confirm !== 'oui') {
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Insert – Entrepreneuriat</title>
    <style>body{font-family:monospace;background:#0d1f3c;color:#e2e8f0;padding:24px;font-size:13px}
    h1{color:#f59e0b}h2{color:#93c5fd}.ok{color:#34d399}.skip{color:#f87171}.warn{color:#fbbf24}
    table{border-collapse:collapse;width:100%;margin-bottom:16px}
    th{background:#1e3a6e;color:#fff;padding:6px 10px;text-align:left}
    td{padding:5px 10px;border-bottom:1px solid #1e3a6e}
    .btn{display:inline-block;padding:12px 28px;background:#f59e0b;color:#000;border-radius:6px;text-decoration:none;font-weight:bold;margin:16px 0}
    </style></head><body>';
    echo '<h1>📋 Preview — ' . count($formations) . ' formations Entrepreneuriat</h1>';
    echo '<p class="ok">✓ À insérer : <strong>' . $a_inserer . '</strong> | ';
    echo '<span class="skip">⚠️ Déjà existantes (ignorées) : ' . $deja_existants . '</span></p>';
    echo '<table><thead><tr><th>#</th><th>Titre</th><th>Durée</th><th>En ligne</th><th>Présentiel</th><th>Statut</th></tr></thead><tbody>';
    foreach ($formations as $i => $f) {
        $exist = $checks[$i];
        $color = $exist ? 'color:#f87171' : 'color:#34d399';
        echo '<tr style="' . $color . '">';
        echo '<td>' . ($i+1) . '</td>';
        echo '<td>' . htmlspecialchars($f['titre']) . '</td>';
        echo '<td>' . $f['duree'] . '</td>';
        echo '<td>' . number_format($f['tarif_en_ligne'],0,',',' ') . ' F</td>';
        echo '<td>' . number_format($f['tarif_presentiel'],0,',',' ') . ' F</td>';
        echo '<td>' . ($exist ? '⚠️ déjà présente' : '✓ à insérer') . '</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
    if ($a_inserer > 0) {
        echo '<a class="btn" href="?confirm=oui">🚀 CONFIRMER ET INSÉRER LES ' . $a_inserer . ' FORMATIONS</a>';
    } else {
        echo '<p class="warn">⚠️ Toutes les formations sont déjà présentes en base.</p>';
    }
    echo '</body></html>';
    exit;
}

// ─── INSERTION ────────────────────────────────────────────────────────────────
$inseres = 0;
$ignores = 0;
$erreurs = [];

foreach ($formations as $i => $f) {
    if ($checks[$i]) { $ignores++; continue; }

    $pdo->beginTransaction();
    try {
        $pdo->prepare("
            INSERT INTO formations (
                titre, slug, domaine, type_certificat,
                description, objectifs, modules, duree,
                mode, tarif_presentiel, tarif_en_ligne, tarif_hybride,
                frais_inscription, paiement_lien,
                date_debut, date_fin, mois, annee, session_label,
                statut, created_at, updated_at
            ) VALUES (
                ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?,
                NULL, NULL, NULL, 0, NULL,
                'active', NOW(), NOW()
            )
        ")->execute([
            $f['titre'], $f['slug'], $f['domaine'], $f['type_certificat'],
            $f['description'], $f['objectifs'], $f['modules'], $f['duree'],
            $f['mode'], $f['tarif_presentiel'], $f['tarif_en_ligne'], 0,
            0, '',
        ]);

        $fid = (int)$pdo->lastInsertId();

        $pdo->prepare("
            INSERT INTO formation_niveaux (
                formation_id, niveau, duree_heures,
                tarif_en_ligne, tarif_presentiel, tarif_hybride,
                statut, ordre_affichage
            ) VALUES (?, 'intermediaire', ?, ?, ?, 0, 'actif', 2)
        ")->execute([$fid, (int)$f['duree'], $f['tarif_en_ligne'], $f['tarif_presentiel']]);

        $pdo->commit();
        $inseres++;

    } catch (Throwable $e) {
        $pdo->rollBack();
        $erreurs[] = $f['slug'] . ' : ' . $e->getMessage();
    }
}

echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>OK — Entrepreneuriat</title>
<style>body{font-family:monospace;background:#0d1f3c;color:#e2e8f0;padding:24px}
h1{color:#34d399}.ok{color:#34d399}.err{color:#f87171}a{color:#f59e0b;margin-right:16px}</style></head><body>';
echo '<h1>✅ Insertion terminée</h1>';
echo '<p class="ok">✓ Formations insérées : <strong>' . $inseres . '</strong></p>';
echo '<p>⚠️ Ignorées (déjà présentes) : ' . $ignores . '</p>';
if ($erreurs) {
    echo '<p class="err">❌ Erreurs (' . count($erreurs) . ') :</p><ul>';
    foreach ($erreurs as $err) echo '<li>' . htmlspecialchars($err) . '</li>';
    echo '</ul>';
}
echo '<p><a href="../catalogue-formations.php?cat=Entrepreneuriat">→ Voir le catalogue Entrepreneuriat</a></p>';
echo '</body></html>';
