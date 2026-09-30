<?php
if (empty($_GET['run'])) die('Ajoutez ?run=1 pour exécuter.');
require_once __DIR__ . '/core/config.php';
require_once __DIR__ . '/core/database.php';

$pdo = Database::connect();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$formations = [

  // ══════════════════════════════════════════════
  // FINANCE AVANCÉE & MODÉLISATION
  // ══════════════════════════════════════════════
  ['Modélisation Financière sous Excel — LBO, Valorisation, Projections',
   'modelisation-financiere-excel-lbo-valorisation-projections',
   'Finance','Formation Certifiante',
   'Construire des modèles financiers robustes sous Excel : projections P&L, bilan, flux de trésorerie, valorisation DCF, LBO et analyse de sensibilité.',
   'Construire, auditer et présenter des modèles financiers dynamiques utilisés en finance d\'entreprise, M&A, Private Equity et levées de fonds.',
   'Bonnes pratiques de modélisation Excel; Projections P&L et bilan intégrés; Tableau des flux de trésorerie (indirect et direct); Modèle DCF (Discounted Cash Flow) et WACC; Valorisation par multiples (EV/EBITDA, P/E); Modèle LBO (Leveraged Buy-Out); Analyse de sensibilité et scénarios; Présentation du modèle aux investisseurs',
   '4 jours','hybride',250000,150000],

  ['Évaluation d\'Entreprise & Fusions-Acquisitions en Afrique',
   'evaluation-entreprise-fusions-acquisitions-afrique',
   'Finance','Formation Certifiante',
   'Maîtriser les méthodes d\'évaluation d\'entreprise et les étapes d\'un processus M&A dans le contexte africain : due diligence, négociation, structuration et closing.',
   'Évaluer une entreprise selon plusieurs méthodes, analyser un dossier de cession, conduire une due diligence financière et structurer une opération M&A.',
   'Méthodes d\'évaluation (DCF, multiples, actif net réévalué, dividendes); Évaluation en contexte africain (primes de risque, illiquidité); Processus M&A : mandat, data-room, offre indicative; Due diligence financière, juridique et fiscale; Structuration de la transaction (earn-out, garanties d\'actif-passif); Négociation et SPA (Share Purchase Agreement); Intégration post-acquisition; Cas pratiques Afrique de l\'Ouest',
   '4 jours','hybride',280000,170000],

  ['Comptabilité Publique & Gestion Budgétaire de l\'État (UEMOA)',
   'comptabilite-publique-gestion-budgetaire-etat-uemoa',
   'Comptabilité','Formation Certifiante',
   'Maîtriser la comptabilité publique et les directives UEMOA sur la gestion budgétaire : nomenclatures, procédures d\'exécution, contrôle interne et reddition des comptes.',
   'Appliquer les directives UEMOA de finances publiques, exécuter le budget selon les règles de la comptabilité publique et préparer les états financiers de l\'État.',
   'Cadre harmonisé des finances publiques UEMOA (6 directives); Nomenclature budgétaire et comptable; Budget-programme vs budget de moyens; Procédures d\'exécution de la dépense publique; Contrôle interne et contrôle de régularité; Comptabilité générale de l\'État (règle de la partie double); États financiers de l\'État; CBMT, CDMT et programmation pluriannuelle',
   '5 jours','hybride',300000,180000],

  ['IFRS pour PME — Normes Comptables Internationales',
   'ifrs-pme-normes-comptables-internationales',
   'Comptabilité','Formation Certifiante',
   'Appliquer les normes IFRS pour PME : cadre conceptuel, principes de reconnaissance, évaluation des actifs et passifs, présentation des états financiers.',
   'Préparer et présenter des états financiers conformes aux IFRS pour PME, appliquer les principes de juste valeur et de coût amorti.',
   'Cadre conceptuel IFRS pour PME vs SYSCOHADA; Actifs immobilisés : évaluation et dépréciation; Instruments financiers; Stocks et contrats à long terme; Provisions et passifs éventuels; Avantages du personnel (IAS 19); Impôts différés; Présentation des états financiers IFRS; Exercices pratiques de conversion SYSCOHADA → IFRS',
   '4 jours','hybride',250000,150000],

  // ══════════════════════════════════════════════
  // IA & TRANSFORMATION NUMÉRIQUE
  // ══════════════════════════════════════════════
  ['ChatGPT & IA Générative pour les Entreprises — Cas Pratiques',
   'chatgpt-ia-generative-entreprises-cas-pratiques',
   'Digital & IA','Formation Certifiante',
   'Exploiter ChatGPT, Gemini et les outils d\'IA générative pour automatiser les tâches métier : rédaction, analyse, code, tableaux de bord et service client.',
   'Utiliser les LLM (ChatGPT, Gemini, Claude) pour gagner en productivité, créer des workflows IA dans les fonctions métier et comprendre les enjeux éthiques.',
   'Panorama des IA génératives (ChatGPT, Gemini, Claude, Mistral); Prompt engineering : techniques avancées et chaînes de prompts; IA pour la rédaction (contrats, rapports, emails, articles); IA pour l\'analyse de données et résumé de documents; Automatisation avec GPT API et n8n; IA dans les fonctions RH, Finance, Marketing; Enjeux éthiques, RGPD et biais algorithmiques; Démo live et exercices en temps réel',
   '2 jours','hybride',120000,75000],

  ['Automatisation des Processus Métier — RPA, n8n & Zapier',
   'automatisation-processus-metier-rpa-n8n-zapier',
   'Digital & IA','Formation Certifiante',
   'Automatiser les tâches répétitives en entreprise avec des outils no-code et RPA : n8n, Zapier, Make (Integromat) et UiPath pour la productivité opérationnelle.',
   'Identifier les processus automatisables, créer des workflows no-code avec n8n ou Zapier et déployer des bots RPA pour les tâches administratives.',
   'Cartographie des processus automatisables; Introduction au RPA (Robotic Process Automation); UiPath Studio : enregistrement et scripts; n8n : création de workflows API-to-API; Zapier et Make (Integromat) : intégrations SaaS; Automatisation des reportings (Google Sheets, Excel, email); Chatbots et assistants virtuels; Mesure ROI de l\'automatisation; Cas pratiques comptabilité, RH, logistique',
   '3 jours','hybride',180000,110000],

  ['Data Science avec Python — De l\'Analyse à la Prédiction',
   'data-science-python-analyse-prediction',
   'Digital & IA','Formation Certifiante',
   'Analyser des données, construire des modèles prédictifs et visualiser les résultats avec Python : Pandas, NumPy, Scikit-learn, Matplotlib et Seaborn.',
   'Collecter et nettoyer des données avec Pandas, construire des modèles de machine learning avec Scikit-learn et communiquer les résultats avec des visualisations.',
   'Python pour la data : Pandas, NumPy, Jupyter; Nettoyage et préparation des données; Statistiques descriptives et exploratoires; Visualisation (Matplotlib, Seaborn, Plotly); Machine Learning supervisé (régression, classification); Machine Learning non supervisé (clustering); Évaluation des modèles; Déploiement d\'un modèle simple (Flask API); Cas pratiques sur données africaines',
   '5 jours','hybride',300000,180000],

  ['Google Analytics 4 & Mesure de la Performance Digitale',
   'google-analytics-4-mesure-performance-digitale',
   'Digital & IA','Formation Certifiante',
   'Configurer Google Analytics 4, analyser le comportement des visiteurs, mesurer les conversions et optimiser les campagnes digitales grâce à la data.',
   'Configurer GA4 sur un site web, créer des rapports et tableaux de bord, analyser les entonnoirs de conversion et connecter les données aux campagnes Google Ads.',
   'Différences GA4 vs Universal Analytics; Installation et configuration GA4 (balises, événements, conversions); Interface GA4 : rapports et explorateur; Segmentation des audiences et cohortes; Entonnoirs de conversion et analyse comportementale; Google Tag Manager : gestion des balises; Connexion GA4 ↔ Google Ads; Tableaux de bord Looker Studio; Exercices pratiques sur données réelles',
   '2 jours','hybride',120000,75000],

  // ══════════════════════════════════════════════
  // MARKETING & COMMERCIAL
  // ══════════════════════════════════════════════
  ['Marketing Digital & Réseaux Sociaux — Stratégie Afrique',
   'marketing-digital-reseaux-sociaux-strategie-afrique',
   'Marketing','Formation Certifiante',
   'Construire une stratégie de marketing digital adaptée au marché africain : Community Management, publicité sur Meta/TikTok/LinkedIn, SEO et email marketing.',
   'Créer et gérer une présence digitale efficace, lancer des campagnes publicitaires rentables sur les réseaux sociaux et mesurer le ROI des actions marketing.',
   'Panorama du digital en Afrique (pénétration, comportements); Stratégie digitale et persona client; Community Management (Facebook, Instagram, TikTok, LinkedIn); Publicité Meta Ads (Facebook & Instagram) : structure, ciblage, créatifs; LinkedIn Ads pour le B2B; TikTok Ads et marketing d\'influence; Email marketing (Mailchimp, Brevo); Google Ads (Search, Display); Analytics et reporting des campagnes',
   '4 jours','hybride',200000,130000],

  ['Développement Commercial B2B en Afrique de l\'Ouest',
   'developpement-commercial-b2b-afrique-ouest',
   'Marketing','Formation Certifiante',
   'Prospecter et développer un portefeuille clients B2B en Afrique de l\'Ouest : techniques de vente, négociation, gestion des grands comptes et cycles de vente longs.',
   'Construire un pipeline commercial B2B, maîtriser les techniques de vente consultative, négocier et conclure des contrats avec des entreprises et institutions.',
   'Cartographie du marché B2B en Afrique de l\'Ouest; Persona client B2B et proposition de valeur; Prospection multicanale (téléphone, LinkedIn, terrain); Techniques de vente consultative (SPIN Selling, MEDDIC); Gestion du pipeline et CRM (Salesforce, HubSpot); Négociation commerciale gagnant-gagnant; Grands comptes et Key Account Management; Suivi, fidélisation et upsell; KPIs commerciaux et reporting',
   '3 jours','hybride',200000,130000],

  ['Pricing & Revenue Management — Optimiser ses Tarifs',
   'pricing-revenue-management-optimiser-tarifs',
   'Marketing','Formation Certifiante',
   'Définir et optimiser sa stratégie tarifaire : méthodes de pricing, yield management, segmentation tarifaire, promotions et impact sur la rentabilité.',
   'Choisir la bonne stratégie de prix pour ses produits et services, mettre en place une segmentation tarifaire et maximiser le revenu par client.',
   'Fondamentaux du pricing : coûts, valeur, concurrence; Méthodes de pricing (cost-plus, value-based, concurrentiel); Élasticité prix et analyse de la demande; Segmentation tarifaire et price discrimination; Yield management et tarification dynamique; Promotions et réductions : impact financier; Tarification en B2B : offres et packages; Pricing digital (SaaS, abonnements, freemium); Cas pratiques secteurs ivoiriens',
   '2 jours','hybride',150000,95000],

  // ══════════════════════════════════════════════
  // MANAGEMENT AVANCÉ & LEADERSHIP
  // ══════════════════════════════════════════════
  ['Design Thinking & Innovation — Résoudre les Problèmes par l\'Humain',
   'design-thinking-innovation-resolution-problemes',
   'Management','Formation Certifiante',
   'Appliquer le Design Thinking pour innover, concevoir de nouveaux services et résoudre les problèmes complexes de l\'entreprise de façon créative et centrée utilisateur.',
   'Animer des sessions de Design Thinking, passer de l\'empathie au prototype testé, lancer un MVP et intégrer la démarche innovation dans l\'entreprise.',
   'Les 5 phases du Design Thinking (Empathise, Define, Ideate, Prototype, Test); Techniques de recherche utilisateur (interviews, observation, persona); Cadrage du problème (How Might We); Idéation : brainstorming, SCAMPER, carte mentale; Prototypage rapide (wireframe, maquette, role-play); Tests utilisateurs et itération; De l\'idée au MVP; Déploiement et culture innovation en entreprise; Atelier complet sur un cas réel',
   '3 jours','hybride',200000,130000],

  ['Management des Équipes à Distance & Travail Hybride',
   'management-equipes-distance-travail-hybride',
   'Management','Formation Certifiante',
   'Manager des équipes en mode hybride ou 100% distanciel : communication, maintien de la cohésion, productivité, outils collaboratifs et bien-être au travail.',
   'Adapter son style de management aux équipes hybrides, utiliser les outils collaboratifs (Teams, Notion, Asana) et maintenir l\'engagement et la performance.',
   'Enjeux du management hybride en Afrique; Styles de management adaptatifs; Communication asynchrone et synchrone; Outils collaboratifs (Teams, Slack, Notion, Asana, Trello); Réunions hybrides efficaces; Maintien de la culture d\'équipe à distance; Suivi de la performance en mode hybride; Bien-être et prévention du burnout; Confiance, autonomie et responsabilisation; Cas pratiques et simulations',
   '2 jours','hybride',150000,95000],

  ['Intelligence Émotionnelle & Leadership Authentique',
   'intelligence-emotionnelle-leadership-authentique',
   'Leadership','Formation Certifiante',
   'Développer son intelligence émotionnelle pour mieux se gérer, comprendre les autres, renforcer ses relations professionnelles et exercer un leadership authentique.',
   'Identifier et réguler ses émotions, développer l\'empathie, gérer les conflits interpersonnels et exercer un leadership inspirant fondé sur l\'authenticité.',
   'Fondamentaux de l\'intelligence émotionnelle (Goleman); Auto-connaissance et régulation émotionnelle; Empathie et écoute active; Motivation intrinsèque et résilience; Gestion des émotions en situation de crise; Relations interpersonnelles et influence positive; Feedback constructif et gestion des conflits; Leadership authentique et storytelling; Développement du plan personnel IQ → EQ',
   '3 jours','hybride',180000,110000],

  ['Négociation Commerciale & Gestion des Conflits',
   'negociation-commerciale-gestion-conflits',
   'Management','Formation Certifiante',
   'Maîtriser les techniques de négociation commerciale et la gestion des conflits : Harvard Model, BATNA, négociation raisonnée et médiation professionnelle.',
   'Préparer et conduire des négociations gagnant-gagnant, sortir des positions conflictuelles et transformer les oppositions en accords durables.',
   'Fondamentaux de la négociation (distributive vs intégrative); Modèle Harvard de négociation raisonnée; BATNA (Best Alternative to a Negotiated Agreement); Préparation de la négociation (objectifs, marges, concessions); Tactiques et contre-tactiques de négociation; Communication non-verbale et rapport de force; Sources de conflits en entreprise; Styles de gestion des conflits (Thomas-Kilmann); Médiation et arbitrage interne; Mises en situation et simulations vidéo',
   '3 jours','hybride',180000,110000],

  // ══════════════════════════════════════════════
  // RESSOURCES HUMAINES AVANCÉ
  // ══════════════════════════════════════════════
  ['GPEC — Gestion Prévisionnelle des Emplois et Compétences',
   'gpec-gestion-previsionnelle-emplois-competences',
   'Ressources Humaines','Formation Certifiante',
   'Mettre en place une démarche GPEC : cartographie des compétences, référentiels métiers, plans de développement et anticipation des besoins en emplois.',
   'Réaliser un diagnostic des compétences, construire les référentiels métiers, élaborer le plan de formation et anticiper les évolutions de l\'organisation.',
   'Cadre légal et enjeux de la GPEC; Cartographie des métiers et des emplois; Référentiel de compétences (savoir, savoir-faire, savoir-être); Évaluation des compétences et entretiens annuels; Analyse des écarts compétences actuelles vs futures; Plan de développement des compétences (PDC); Mobilité interne et gestion des carrières; Tableau de bord RH et indicateurs GPEC; Lien GPEC-stratégie d\'entreprise',
   '3 jours','hybride',200000,130000],

  ['Système d\'Information RH (SIRH) — Digitaliser la Fonction RH',
   'sirh-digitaliser-fonction-rh',
   'Ressources Humaines','Formation Certifiante',
   'Choisir, implémenter et exploiter un SIRH pour digitaliser la gestion RH : paie, recrutement, formation, évaluations, congés et tableaux de bord RH.',
   'Définir les besoins en SIRH, piloter un projet de déploiement, utiliser les fonctionnalités clés et extraire des analyses RH pour la prise de décision.',
   'Panorama des SIRH (Sage RH, Odoo HR, Workday, Factorial); Besoins et cahier des charges SIRH; Module Paie et gestion administrative; Module Recrutement et onboarding; Module Formation et développement; Module Évaluations et performances; Module Congés et absences; Tableaux de bord et analytics RH; Conduite du changement et formation utilisateurs; ROI d\'un SIRH',
   '3 jours','hybride',180000,110000],

  // ══════════════════════════════════════════════
  // SECTEUR PUBLIC & GOUVERNANCE
  // ══════════════════════════════════════════════
  ['Gouvernance Locale & Décentralisation en Côte d\'Ivoire',
   'gouvernance-locale-decentralisation-cote-ivoire',
   'ONG & Développement','Formation Certifiante',
   'Comprendre et gérer les collectivités territoriales en Côte d\'Ivoire : compétences des communes et districts, budget local, passation des marchés et services publics locaux.',
   'Maîtriser le cadre institutionnel de la décentralisation ivoirienne, gérer les finances locales et conduire des projets de développement local.',
   'Cadre juridique de la décentralisation en CI; Compétences des communes, régions et districts; Budget local : préparation, exécution, contrôle; Recettes propres et transferts de l\'État; Passation des marchés publics locaux; Gestion des services publics locaux (eau, assainissement, voirie); Coopération décentralisée; Participation citoyenne et redevabilité; Monitoring et évaluation des politiques locales',
   '4 jours','hybride',220000,140000],

  ['Performance des Services Publics — Outils de Pilotage',
   'performance-services-publics-outils-pilotage',
   'ONG & Développement','Formation Certifiante',
   'Améliorer la performance des administrations publiques : tableaux de bord, indicateurs de performance, gestion axée résultats (GAR) et qualité du service public.',
   'Définir des indicateurs de performance pour un service public, construire un tableau de bord, piloter par les résultats et améliorer la qualité de service.',
   'Gestion axée sur les résultats (GAR) dans le secteur public; Cadre de mesure de la performance; Indicateurs d\'efficacité, d\'efficience et d\'impact; Tableaux de bord de la performance publique; Plan stratégique et plan d\'action annuel; Reporting et reddition des comptes; Qualité du service public et satisfaction des usagers; Benchmarking inter-administrations; Réforme administrative et modernisation',
   '3 jours','hybride',200000,130000],

  // ══════════════════════════════════════════════
  // ÉNERGIE & DÉVELOPPEMENT DURABLE
  // ══════════════════════════════════════════════
  ['Énergies Renouvelables & Électrification Rurale en Afrique',
   'energies-renouvelables-electrification-rurale-afrique',
   'Mines & Énergie','Formation Certifiante',
   'Concevoir et financer des projets d\'énergies renouvelables en Afrique : solaire, éolien, mini-grids, financement carbone et cadre réglementaire.',
   'Dimensionner un système solaire photovoltaïque, comprendre les enjeux des mini-grids, accéder aux financements verts et gérer un projet d\'électrification rurale.',
   'Panorama des énergies renouvelables en Afrique (solaire, éolien, hydro, biogaz); Dimensionnement d\'un système solaire PV (on-grid, off-grid, hybride); Mini-grids : conception, business model, tarification; Cadre réglementaire en CI (Autorité Nationale de Régulation); Financement vert : fonds climatiques, carbon credits; Partenariats public-privé (PPP) dans l\'énergie; Maintenance et exploitation des installations; Études de cas de projets réussis en Afrique',
   '4 jours','hybride',250000,150000],

  ['RSE & Développement Durable pour Entreprises',
   'rse-developpement-durable-entreprises',
   'QHSE','Formation Certifiante',
   'Construire et piloter une stratégie RSE (Responsabilité Sociétale des Entreprises) : diagnostic, plan d\'action, reporting extra-financier et création de valeur partagée.',
   'Réaliser un diagnostic RSE de l\'entreprise, définir une politique RSE ambitieuse, rédiger un rapport de développement durable et intégrer les ODD.',
   'Cadre international de la RSE (ISO 26000, ODD, Accord de Paris); Matrice de matérialité et identification des enjeux; Volets environnemental, social et gouvernance (ESG); Plan d\'action RSE et intégration dans la stratégie; Reporting extra-financier (CSRD, GRI, SDG Compass); Certification et labels RSE; Chaîne d\'approvisionnement responsable; Communication RSE et prévention du greenwashing; Cas de grandes entreprises ivoiriennes',
   '3 jours','hybride',180000,110000],

  // ══════════════════════════════════════════════
  // IMMOBILIER & BTP
  // ══════════════════════════════════════════════
  ['Promotion Immobilière & Montage d\'Opérations en Côte d\'Ivoire',
   'promotion-immobiliere-montage-operations-cote-ivoire',
   'Immobilier','Formation Certifiante',
   'Monter une opération de promotion immobilière en Côte d\'Ivoire : foncier, permis, financement, commercialisation et livraison d\'un programme immobilier.',
   'Acquérir un terrain en conformité, monter le dossier de permis de construire, financer l\'opération, commercialiser les lots et livrer le programme.',
   'Marché immobilier ivoirien (CI-Logement, habitat social); Acquisition foncière et titrement (titre foncier en CI); Études de faisabilité technique et financière; Plan d\'Urbanisme et règles d\'occupation des sols; Permis de construire : constitution du dossier; Financement de la promotion immobilière (banques, CRRH-UEMOA); Commercialisation VEFA (Vente en État Futur d\'Achèvement); Gestion de chantier et réception des travaux',
   '4 jours','hybride',250000,150000],

  ['Gestion de Chantier BTP — Planification & Contrôle des Coûts',
   'gestion-chantier-btp-planification-controle-couts',
   'BTP','Formation Certifiante',
   'Planifier, suivre et contrôler un chantier de construction : planification Gantt, gestion des coûts, qualité, sécurité et coordination des intervenants.',
   'Élaborer un planning de chantier, contrôler les coûts et la qualité, gérer les sous-traitants et assurer la sécurité sur un chantier de BTP.',
   'Préparation du chantier : organisation et installation; Planification avec MS Project et Gantt; Décomposition du budget chantier (DPM, métrés); Gestion des approvisionnements et matériaux; Coordination et contractualisation des sous-traitants; Contrôle qualité et réception des ouvrages; HSE sur chantier (OSHA, normes CI); Gestion des avenants et contentieux chantier; Réunions de chantier et PV; Clôture et décompte final',
   '4 jours','hybride',220000,140000],

  // ══════════════════════════════════════════════
  // SANTÉ & SECTEUR HOSPITALIER
  // ══════════════════════════════════════════════
  ['Gestion Hospitalière & Administration de la Santé',
   'gestion-hospitaliere-administration-sante',
   'Santé','Formation Certifiante',
   'Manager un établissement de santé : gouvernance hospitalière, gestion financière, ressources humaines médicales, qualité des soins et réglementation sanitaire.',
   'Assurer la gestion efficace d\'un établissement de santé, maîtriser les outils de pilotage hospitalier et garantir la qualité et la sécurité des soins.',
   'Organisation du système de santé en Côte d\'Ivoire; Gouvernance hospitalière et organes de direction; Gestion budgétaire et financière d\'un hôpital; Ressources humaines médicales et paramédicales; Accréditation et certification qualité (HAS); Gestion des risques cliniques et sécurité des patients; Systèmes d\'information hospitaliers (SIH); Tarification à l\'acte (T2A) et CNAM; Projets de développement hospitalier',
   '4 jours','hybride',250000,150000],

  // ══════════════════════════════════════════════
  // COMMUNICATION & INFLUENCE
  // ══════════════════════════════════════════════
  ['Prise de Parole en Public & Art Oratoire',
   'prise-parole-public-art-oratoire-professionnel',
   'Communication Professionnelle','Formation Certifiante',
   'Maîtriser la prise de parole en public : structuration du discours, gestion du stress, langage non-verbal, présentations PowerPoint impactantes et storytelling.',
   'Préparer et délivrer des discours et présentations professionnelles avec aisance, confiance et impact, en toutes circonstances (réunions, conférences, pitch).',
   'Diagnostic de son profil orateur; Structuration d\'un discours (SPRI, règle des 3); Gestion du stress et techniques de respiration; Communication non-verbale (posture, gestuelle, regard); Voix : travail du débit, rythme et articulation; Storytelling et anecdotes percutantes; Créer des visuels PowerPoint impactants; Pitch d\'entreprise et présentation à des décideurs; Exercices filmés et debriefing vidéo',
   '3 jours','hybride',180000,110000],

  ['Rédaction Professionnelle & Communication Écrite en Entreprise',
   'redaction-professionnelle-communication-ecrite-entreprise',
   'Communication Professionnelle','Formation Certifiante',
   'Améliorer sa rédaction professionnelle : emails, rapports, notes de synthèse, comptes-rendus, propositions commerciales et communications institutionnelles.',
   'Rédiger des documents professionnels clairs, concis et adaptés à chaque destinataire, en maîtrisant les codes de l\'écrit professionnel.',
   'Diagnostic de son style d\'écriture; Adapter son écrit à son lecteur et son objectif; Structuration des documents professionnels; L\'email professionnel efficace; Rapport d\'activité et rapport d\'expertise; Note de synthèse et note de service; Compte-rendu de réunion; Proposition commerciale convaincante; Communication institutionnelle; Grammaire et expression écrite avancées',
   '2 jours','hybride',130000,80000],

  ['Anglais des Affaires — Business English pour Professionnels',
   'anglais-affaires-business-english-professionnels',
   'Développement Personnel','Formation Certifiante',
   'Développer son anglais professionnel pour les contextes d\'entreprise : réunions, présentations, emails, négociations et conférences internationales.',
   'Communiquer efficacement en anglais dans un environnement professionnel, rédiger des emails clairs, animer des réunions et négocier en anglais.',
   'Diagnostic et positionnement (A2 → C1); Vocabulaire des affaires par secteur (finance, RH, commerce); Emails professionnels en anglais (structure, formules, tons); Réunions et téléconférences en anglais; Présentations professionnelles en anglais; Négociation et argumentation en anglais; Anglais pour les documents contractuels; Lecture et compréhension de rapports en anglais; Entraînement à l\'oral en conditions réelles',
   '4 jours','hybride',200000,130000],

  // ══════════════════════════════════════════════
  // SECTEURS SPÉCIALISÉS
  // ══════════════════════════════════════════════
  ['Opérations Portuaires & Logistique Maritime — Port d\'Abidjan',
   'operations-portuaires-logistique-maritime-port-abidjan',
   'Logistique & Supply Chain','Formation Certifiante',
   'Maîtriser les opérations portuaires et la logistique maritime au Port Autonome d\'Abidjan : manutention, Incoterms maritimes, connaissement, charte-partie et sécurité portuaire.',
   'Gérer les opérations de manutention portuaire, utiliser les documents de transport maritime et assurer la sécurité des marchandises et des personnels portuaires.',
   'Organisation du Port Autonome d\'Abidjan (PAA); Types de navires et marchandises; Connaissement maritime (Bill of Lading) et documents de transport; Charte-partie et types d\'affrètement; Incoterms 2020 : FAS, FOB, CIF, CFR; Manutention et opérations de terminal à conteneurs; Gestion des risques portuaires (ISPS, sécurité); Tarification portuaire et calcul du coût de passage; Sinistres et avaries maritimes; CNUCED et accords maritimes africains',
   '4 jours','hybride',220000,140000],

  ['Pétrole & Gaz en Afrique — Contrats Pétroliers & Réglementation',
   'petrole-gaz-afrique-contrats-petroliers-reglementation',
   'Mines & Énergie','Formation Certifiante',
   'Comprendre les contrats pétroliers en Afrique (PSC, concessions, joint-ventures), la réglementation sectorielle et la gestion des revenus pétroliers.',
   'Lire et analyser un contrat de partage de production (PSC), comprendre les enjeux de la souveraineté pétrolière africaine et évaluer les revenus de l\'État.',
   'Géologie pétrolière et types de gisements; Exploration et production : les phases du cycle; Contrats pétroliers : concession, PSC, service risk; Fiscalité pétrolière (royalties, profit oil, impôts); Initiative EITI et transparence des revenus; Souveraineté pétrolière et contenu local; Environnement : impact et réglementation HSE; Marché mondial du pétrole brut et OPEC; Cas d\'étude Côte d\'Ivoire, Nigeria, Sénégal',
   '4 jours','hybride',280000,170000],

  ['Gestion des Ressources Minières & Industrie Extractive',
   'gestion-ressources-minieres-industrie-extractive',
   'Mines & Énergie','Formation Certifiante',
   'Comprendre l\'industrie minière en Afrique : code minier, fiscalité extractive, contenu local, impact environnemental et gouvernance des ressources naturelles.',
   'Analyser un code minier, négocier les conventions minières, gérer les impacts environnementaux et sociaux et assurer la traçabilité des minerais.',
   'Panorama de l\'industrie minière en Afrique (or, bauxite, manganèse, cobalt); Code minier de Côte d\'Ivoire (SODEMI, BRGM); Phases du projet minier : exploration, développement, exploitation, fermeture; Fiscalité minière et régimes douaniers; Contenu local et transfert de compétences; Due diligence et traçabilité (OCDE, ICGLR); Gestion environnementale et sociale (ESIA, PGES); Revenus miniers et ITIE; Cas d\'études : mines d\'or en CI et en Afrique de l\'Ouest',
   '4 jours','hybride',280000,170000],

  // ══════════════════════════════════════════════
  // FORMATION & PÉDAGOGIE PROFESSIONNELLE
  // ══════════════════════════════════════════════
  ['Ingénierie Pédagogique & Conception de Formations',
   'ingenierie-pedagogique-conception-formations',
   'ONG & Développement','Formation Certifiante',
   'Concevoir des formations professionnelles efficaces : analyse des besoins, ingénierie pédagogique, méthodes actives, e-learning et évaluation des apprentissages.',
   'Analyser un besoin de formation, rédiger un cahier des charges pédagogique, concevoir les supports, animer avec pédagogie active et évaluer les acquis.',
   'Analyse du besoin de formation (entretiens, questionnaire); Objectifs pédagogiques (taxonomie de Bloom); Ingénierie de formation : séquencement et durée; Méthodes pédagogiques actives (cas pratiques, jeux de rôle, simulation); Conception des supports (diaporama, guides, fiches); E-learning et outils auteur (Articulate, Genially); Évaluation à chaud, à froid et mesure des transferts; Certification de formateur professionnel FDFP',
   '4 jours','hybride',220000,140000],

  // ══════════════════════════════════════════════
  // TOURISME & ÉVÉNEMENTIEL
  // ══════════════════════════════════════════════
  ['Management Hôtelier & Yield Management — Hôtellerie en Afrique',
   'management-hotelier-yield-management-hotelerie-afrique',
   'Tourisme & Hôtellerie','Formation Certifiante',
   'Gérer un établissement hôtelier en Afrique : opérations, yield management, service client d\'exception, Revenue Management et normes de classification.',
   'Optimiser le taux d\'occupation et le RevPAR d\'un hôtel, gérer les équipes d\'hébergement et de restauration et appliquer les normes de classification hôtelière.',
   'Organisation et opérations d\'un hôtel; Normes de classification hôtelière (UEHOA, étoiles); Service client d\'exception (LQA, guest experience); Revenue Management et yield management; Indicateurs clés : RevPAR, ADR, taux d\'occupation; Canaux de distribution (OTA, direct, GDS); Gestion de la réputation en ligne (TripAdvisor, Booking); F&B management et gestion des coûts restauration; Développement du tourisme d\'affaires (MICE)',
   '3 jours','hybride',180000,110000],

  ['Organisation d\'Événements & Gestion de Projets Événementiels',
   'organisation-evenements-gestion-projets-evenementiels',
   'Gestion Commerciale & Marketing','Formation Certifiante',
   'Concevoir et produire des événements professionnels : conférences, séminaires, galas, salons et lancements de produits, de la conception au bilan.',
   'Planifier un événement de A à Z, gérer le budget, coordonner les prestataires, assurer la logistique le jour J et mesurer l\'impact post-événement.',
   'Cadrage de l\'événement : brief, objectifs, cibles; Budget événementiel : postes de coûts et négociation; Sélection et gestion des prestataires (traiteur, son/lumière, sécurité); Logistique et plan de salle; Communication et promotion de l\'événement; Accueil, protocole et gestion VIP; Coordination du Jour J : équipes et imprévus; Événementiel digital et webinaires professionnels; Bilan et mesure de l\'impact; Réglementation et autorisations en CI',
   '3 jours','hybride',180000,110000],

  // ══════════════════════════════════════════════
  // DÉVELOPPEMENT PERSONNEL & CARRIÈRE
  // ══════════════════════════════════════════════
  ['Personal Branding & Développement de la Carrière Professionnelle',
   'personal-branding-developpement-carriere-professionnelle',
   'Développement Personnel','Formation Certifiante',
   'Construire et valoriser son personal branding professionnel : LinkedIn, CV, portefeuille de compétences, réseau, entretiens et stratégie de carrière long terme.',
   'Définir son positionnement professionnel unique, optimiser son profil LinkedIn, développer son réseau, préparer ses entretiens et piloter sa carrière.',
   'Bilan de compétences et positionnement professionnel; Construire sa proposition de valeur unique (USP); Optimisation du profil LinkedIn; CV percutant et lettre de motivation; Développement du réseau professionnel (networking); Personal Branding sur les réseaux sociaux; Préparation aux entretiens d\'embauche; Négociation salariale; Plan de carrière à 3-5 ans; Reconversion et pivot professionnel',
   '2 jours','hybride',130000,80000],

  ['Gestion du Stress & Prévention du Burnout au Travail',
   'gestion-stress-prevention-burnout-travail',
   'Développement Personnel','Formation Certifiante',
   'Identifier et gérer les sources de stress professionnel, prévenir le burnout, développer sa résilience et maintenir un équilibre vie pro/vie personnelle durable.',
   'Reconnaître les signes du stress et du burnout, appliquer des techniques de gestion du stress, développer la résilience et mettre en place des routines de bien-être.',
   'Mécanismes du stress professionnel (physiologie et psychologie); Facteurs de risques psychosociaux (FPS); Symptômes du burnout et des troubles musculo-squelettiques; Techniques de relaxation (cohérence cardiaque, mindfulness, sophrologie); Gestion du temps et des priorités (GTD, Eisenhower); Équilibre vie professionnelle / vie personnelle; Résilience et développement d\'un mindset positif; Plan personnel anti-burnout; Rôle des managers dans la prévention',
   '2 jours','hybride',130000,80000],
];

$stmt = $pdo->prepare("
  INSERT IGNORE INTO formations
    (titre, slug, domaine, type_certificat, description, objectifs, modules,
     duree, mode, tarif_presentiel, tarif_en_ligne, tarif_hybride, frais_inscription,
     statut, created_at, updated_at)
  VALUES
    (:titre, :slug, :domaine, :type_certificat, :description, :objectifs, :modules,
     :duree, :mode, :tarif_presentiel, :tarif_en_ligne, 0, 0,
     'inactive', NOW(), NOW())
");

$ok = 0; $skip = 0; $errors = [];
foreach ($formations as $f) {
    try {
        $stmt->execute([
            ':titre'           => $f[0],
            ':slug'            => $f[1],
            ':domaine'         => $f[2],
            ':type_certificat' => $f[3],
            ':description'     => $f[4],
            ':objectifs'       => $f[5],
            ':modules'         => $f[6],
            ':duree'           => $f[7],
            ':mode'            => $f[8],
            ':tarif_presentiel'=> $f[9],
            ':tarif_en_ligne'  => $f[10],
        ]);
        if ($stmt->rowCount() > 0) $ok++; else $skip++;
    } catch (PDOException $e) {
        $errors[] = $f[0] . ' → ' . $e->getMessage();
    }
}

echo "<pre>\n";
echo "✅ Nouvelles formations insérées : $ok / " . count($formations) . "\n";
echo "⏭  Déjà existantes (ignorées)    : $skip\n";
if ($errors) { echo "\n⚠️ Erreurs :\n"; foreach ($errors as $e) echo "  - $e\n"; }
$total = $pdo->query("SELECT COUNT(*) FROM formations")->fetchColumn();
echo "📊 Total formations en base : $total\n";
echo "</pre>";
