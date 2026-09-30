-- =====================================================================
-- IBIG EDUFORM — Nouvelles formations catalogue (Sept 2026)
-- 37 formations manquantes : OHADA, Fiscalité, IT, Droit, Logistique,
--   Agriculture, Banque, GRH, Management, ONG, Entrepreneuriat
-- Statut = 'inactive' : visibles dans le catalogue, sans session planifiée.
-- Idempotent : INSERT IGNORE par slug unique.
-- =====================================================================

INSERT IGNORE INTO formations
  (titre, slug, domaine, type_certificat, description, objectifs, modules,
   duree, mode, tarif_presentiel, tarif_en_ligne, tarif_hybride, frais_inscription,
   statut, created_at, updated_at)
VALUES

-- ==============================
-- COMPTABILITÉ & FINANCE
-- ==============================
(
  'SYSCOHADA Révisé — Comptabilité selon le Référentiel OHADA',
  'syscohada-revise-comptabilite-ohada',
  'Comptabilité',
  'Formation Certifiante',
  'Maîtriser le référentiel comptable OHADA révisé : plan de comptes, états financiers, consolidation et conformité réglementaire pour entreprises de la zone UEMOA.',
  'Produire des états financiers conformes au SYSCOHADA révisé, maîtriser le plan de comptes OHADA, réaliser les écritures de régularisation et préparer les documents de synthèse.',
  'Principes fondamentaux du SYSCOHADA révisé; Plan comptable OHADA (classe 1 à 8); Opérations courantes et écritures de base; Amortissements et provisions; Régularisations de fin d exercice; Bilan, Compte de résultat et Tableau des flux; Notes annexes et liasse fiscale; Exercices pratiques sur cas réels ivoiriens',
  '5 jours', 'hybride', 300000, 180000, 0, 0,
  'inactive', NOW(), NOW()
),
(
  'Déclaration Fiscale DGI Côte d\'Ivoire — TVA, IS, IRVM',
  'declaration-fiscale-dgi-cote-ivoire',
  'Fiscalité',
  'Formation Certifiante',
  'Maîtriser les obligations fiscales envers la DGI Côte d\'Ivoire : TVA, Impôt sur les Sociétés, IRVM, patente, télédéclaration et gestion des contrôles fiscaux.',
  'Produire les déclarations fiscales conformes à la réglementation ivoirienne, utiliser les formulaires DGI, gérer les échéances et anticiper les risques fiscaux.',
  'Panorama du système fiscal ivoirien; TVA : base imposable, taux, déclaration mensuelle; IS et BNC : calcul, acomptes, déclaration annuelle; IRVM et retenues à la source; Patente, taxe foncière et autres impôts locaux; Télédéclaration sur le portail DGI-net; Gestion du contrôle fiscal et contentieux; Exercices pratiques sur formulaires DGI',
  '3 jours', 'hybride', 180000, 110000, 0, 0,
  'inactive', NOW(), NOW()
),
(
  'Fiscalité des PME en Côte d\'Ivoire',
  'fiscalite-pme-cote-ivoire',
  'Fiscalité',
  'Formation Certifiante',
  'Optimiser la fiscalité des PME ivoiriennes : régimes d\'imposition, obligations déclaratives, TVA, IS, patente et stratégies d\'optimisation fiscale légale.',
  'Identifier le régime fiscal adapté à la PME, maîtriser les obligations déclaratives, optimiser la charge fiscale dans le respect de la loi ivoirienne.',
  'Régimes fiscaux PME (réel simplifié, réel normal, taxe d impôt synthétique); Critères de choix du régime; Obligations déclaratives et délais; Comptabilisation de la TVA; Optimisation fiscale légale; Gestion des avantages fiscaux (zones franches, CIE, exonérations); Cas pratiques PME ivoiriennes',
  '3 jours', 'hybride', 180000, 110000, 0, 0,
  'inactive', NOW(), NOW()
),
(
  'Audit Interne selon les Normes IIA',
  'audit-interne-normes-iia',
  'Audit & Contrôle',
  'Formation Certifiante',
  'Conduire des missions d\'audit interne conformément aux Normes Internationales pour la Pratique Professionnelle de l\'Audit Interne (IPPF / IIA).',
  'Planifier et conduire une mission d\'audit interne, rédiger des rapports d\'audit conformes aux normes IIA et formuler des recommandations à valeur ajoutée.',
  'Cadre de référence IIA (IPPF) et code d éthique; Cartographie des risques et plan d audit annuel; Phases d une mission : planification, terrain, rapport; Techniques de collecte et d analyse des preuves; Rédaction du rapport d audit et des recommandations; Suivi des recommandations et mesure de performance; Audit des processus clés (achats, paie, trésorerie); Exercices pratiques sur cas d audit réels',
  '5 jours', 'hybride', 280000, 170000, 0, 0,
  'inactive', NOW(), NOW()
),
(
  'Microfinance & Gestion des Systèmes Financiers Décentralisés (SFD)',
  'microfinance-gestion-sfd',
  'Microfinance',
  'Formation Certifiante',
  'Gérer et développer une institution de microfinance (IMF/SFD) : réglementation BCEAO, produits financiers, gestion du risque de crédit et pilotage de la performance.',
  'Maîtriser le cadre réglementaire des SFD, gérer le portefeuille de crédit, analyser la performance financière et assurer la conformité aux normes BCEAO/UMOA.',
  'Environnement réglementaire BCEAO et loi PARMEC; Gouvernance et organisation d une SFD; Produits financiers (épargne, crédit, assurance solidaire); Instruction et analyse des dossiers de crédit; Gestion du risque de crédit et recouvrement; Ratios prudentiels et performance financière; Système d information de gestion (SIG); Exercices pratiques sur cas de terrain',
  '4 jours', 'hybride', 220000, 140000, 0, 0,
  'inactive', NOW(), NOW()
),
(
  'Analyse Financière des États SYSCOHADA',
  'analyse-financiere-etats-syscohada',
  'Finance',
  'Formation Certifiante',
  'Analyser les états financiers SYSCOHADA : bilan, compte de résultat, tableau de flux de trésorerie, pour diagnostiquer la santé financière d\'une entreprise.',
  'Lire et interpréter les états financiers SYSCOHADA, calculer les ratios clés, établir un diagnostic financier et formuler des recommandations stratégiques.',
  'Lecture et retraitement du bilan SYSCOHADA; Analyse du compte de résultat (SIG); Tableau de financement et flux de trésorerie; Ratios de liquidité, solvabilité et rentabilité; Scoring et notation financière; Analyse de la structure financière (FDR, BFR, TN); Exercices pratiques sur liasses fiscales réelles; Rédaction du rapport de diagnostic financier',
  '3 jours', 'hybride', 200000, 130000, 0, 0,
  'inactive', NOW(), NOW()
),

-- ==============================
-- INFORMATIQUE & TECH
-- ==============================
(
  'Power BI — Tableaux de Bord & Business Intelligence',
  'power-bi-tableaux-de-bord-business-intelligence',
  'Data & BI',
  'Formation Certifiante',
  'Créer des tableaux de bord interactifs avec Microsoft Power BI : import de données, modélisation, calculs DAX et publication de rapports professionnels.',
  'Importer et transformer des données, créer des modèles de données, écrire des mesures DAX et publier des rapports interactifs sur Power BI Service.',
  'Prise en main de Power BI Desktop; Connexion aux sources de données (Excel, SQL, API); Nettoyage et transformation avec Power Query; Modélisation des données (relations, schéma étoile); Calculs DAX (mesures, colonnes calculées, KPIs); Visualisations avancées et mises en forme; Publication et partage sur Power BI Service; Tableaux de bord dynamiques et alertes',
  '3 jours', 'hybride', 180000, 110000, 0, 0,
  'inactive', NOW(), NOW()
),
(
  'Odoo ERP — Gestion Intégrée de l\'Entreprise',
  'odoo-erp-gestion-integree-entreprise',
  'Odoo',
  'Formation Certifiante',
  'Déployer et utiliser Odoo ERP pour gérer la comptabilité, les achats, les ventes, les stocks et les ressources humaines d\'une PME.',
  'Configurer et utiliser les modules clés d\'Odoo (comptabilité, CRM, stocks, achats, paie), automatiser les processus et extraire des rapports de gestion.',
  'Architecture Odoo et navigation; Module Comptabilité (plan de comptes, factures, rapprochement); Module Ventes et CRM (devis, commandes, pipeline); Module Achats et gestion fournisseurs; Module Stocks et gestion d entrepôt; Module RH et Paie; Paramétrage et personnalisation; Imports/exports et reporting',
  '4 jours', 'hybride', 220000, 140000, 0, 0,
  'inactive', NOW(), NOW()
),
(
  'Administration Réseau & Cybersécurité pour PME',
  'administration-reseau-cybersecurite-pme',
  'Cybersécurité',
  'Formation Certifiante',
  'Sécuriser l\'infrastructure informatique d\'une PME : administration réseau, pare-feux, VPN, gestion des accès, détection des menaces et réponse aux incidents.',
  'Administrer un réseau local, configurer les équipements de sécurité, identifier les vulnérabilités et mettre en place une politique de sécurité informatique.',
  'Fondamentaux des réseaux (TCP/IP, LAN, WAN, Wi-Fi); Configuration des routeurs et switches; Pare-feux et filtrage (pfSense, iptables); VPN et sécurisation des accès distants; Gestion des droits et des identités (Active Directory); Détection des intrusions (IDS/IPS); Sauvegardes et plan de reprise d activité; Réponse aux incidents et journalisation',
  '4 jours', 'hybride', 200000, 130000, 0, 0,
  'inactive', NOW(), NOW()
),
(
  'WordPress & SEO — Créer et Référencer son Site Professionnel',
  'wordpress-seo-site-professionnel',
  'WordPress & Web',
  'Formation Certifiante',
  'Créer un site web professionnel avec WordPress, l\'optimiser pour les moteurs de recherche (SEO) et gérer son contenu en toute autonomie.',
  'Créer un site WordPress professionnel, configurer les extensions essentielles, rédiger du contenu optimisé SEO et analyser les performances sur Google.',
  'Installation et configuration de WordPress; Choix du thème et personnalisation (Elementor); Pages, articles et gestion des médias; Extensions essentielles (Yoast SEO, WooCommerce, Contact Form 7); Fondamentaux du SEO on-page; Mots-clés et structure de contenu; Google Search Console et Analytics; Sécurité et maintenance du site',
  '3 jours', 'hybride', 120000, 75000, 0, 0,
  'inactive', NOW(), NOW()
),
(
  'Mobile Money & Intégration des Paiements Mobiles en Afrique',
  'mobile-money-integration-paiements-mobiles-afrique',
  'Digital & IA',
  'Formation Certifiante',
  'Comprendre l\'écosystème Mobile Money en Afrique de l\'Ouest et intégrer les APIs de paiement (Orange Money, MTN MoMo, Wave) dans les applications métiers.',
  'Maîtriser les APIs des opérateurs de paiement mobile, intégrer des solutions de paiement dans une application web ou mobile et assurer la conformité réglementaire.',
  'Écosystème Mobile Money en Afrique (Orange, MTN, Wave, Moneroo); Fonctionnement technique des paiements mobiles; APIs REST : authentification, requêtes, webhooks; Intégration Orange Money API et MTN MoMo API; Intégration Wave Business API; Tests, sandbox et passage en production; Réconciliation et gestion des erreurs; Conformité BCEAO et réglementation des paiements',
  '3 jours', 'hybride', 180000, 110000, 0, 0,
  'inactive', NOW(), NOW()
),

-- ==============================
-- DROIT & JURIDIQUE
-- ==============================
(
  'Droit OHADA des Sociétés — SARL, SA, GIE',
  'droit-ohada-societes-sarl-sa-gie',
  'Droit OHADA',
  'Formation Certifiante',
  'Maîtriser l\'Acte Uniforme OHADA sur les sociétés commerciales : constitution, gouvernance, responsabilité des dirigeants et dissolution des SARL, SA et GIE.',
  'Rédiger les statuts d\'une société, structurer la gouvernance, gérer les assemblées générales et assurer la conformité aux dispositions de l\'OHADA.',
  'Introduction au droit OHADA et à l\'Acte Uniforme des Sociétés; Constitution des SARL, SA et GIE; Capital social et apports; Gouvernance et organes de direction; Responsabilité des dirigeants; Assemblées générales : convocation, délibérations, PV; Modifications statutaires (augmentation de capital, cession); Dissolution et liquidation; Jurisprudence CCJA',
  '4 jours', 'hybride', 250000, 150000, 0, 0,
  'inactive', NOW(), NOW()
),
(
  'Droit du Travail Ivoirien — Code du Travail & Pratique RH',
  'droit-du-travail-ivoirien-code-pratique-rh',
  'Droit & Juridique',
  'Formation Certifiante',
  'Maîtriser le Code du Travail ivoirien, la gestion des contrats, des licenciements, des conventions collectives et le contentieux prud\'homal.',
  'Rédiger et gérer les contrats de travail conformément au droit ivoirien, prévenir les risques de contentieux et gérer les procédures de rupture du contrat.',
  'Sources du droit du travail ivoirien; Types de contrats (CDI, CDD, intérim, sous-traitance); Clauses essentielles et clauses abusives; Temps de travail, congés et jours fériés; Rémunération, SMIG et avantages; Licenciement individuel et collectif : procédures légales; Sanctions disciplinaires et procédures; Conventions collectives interprofessionnelles; Contentieux prud\'homal; Rôle de l Inspection du Travail',
  '3 jours', 'hybride', 180000, 110000, 0, 0,
  'inactive', NOW(), NOW()
),
(
  'Passation des Marchés Publics selon le Code Ivoirien (ANRMP)',
  'passation-marches-publics-code-ivoirien-anrmp',
  'Marchés Publics',
  'Formation Certifiante',
  'Maîtriser les procédures de passation des marchés publics en Côte d\'Ivoire selon le décret portant Code des Marchés Publics et les directives ANRMP.',
  'Préparer un dossier d\'appel d\'offres, conduire une procédure de passation de marché, évaluer les offres et assurer la conformité réglementaire.',
  'Cadre réglementaire des marchés publics en CI; Principes fondamentaux (transparence, concurrence, égalité); Modes de passation : appel d offres ouvert, restreint, gré à gré; Dossier d Appel d Offres (DAO) : structure et contenu; Évaluation des offres et attribution; Contrat de marché et exécution; Contrôle et audit des marchés; Recours et contentieux devant l ANRMP; Exercices pratiques sur DAO réels',
  '4 jours', 'hybride', 250000, 150000, 0, 0,
  'inactive', NOW(), NOW()
),
(
  'Droit UEMOA & Réglementations BCEAO',
  'droit-uemoa-reglementations-bceao',
  'Droit & Juridique',
  'Formation Certifiante',
  'Comprendre le cadre juridique et réglementaire de l\'UEMOA et les directives de la BCEAO applicables aux entreprises et institutions financières de la zone franc.',
  'Identifier les textes UEMOA applicables à l\'entreprise, assurer la conformité aux directives BCEAO et gérer les opérations transfrontalières dans la zone UEMOA.',
  'Architecture institutionnelle de l UEMOA; Directive UEMOA sur la TVA et les impôts; Réglementation des changes et transfers BCEAO; LBC/FT : loi uniforme UEMOA et obligations de conformité; Règlement financier UEMOA (marchés régionaux); Commerce intracommunautaire et TEC; Libre circulation des personnes et capitaux; Jurisprudence de la Cour de Justice de l UEMOA',
  '3 jours', 'hybride', 200000, 130000, 0, 0,
  'inactive', NOW(), NOW()
),
(
  'Contrats Commerciaux & Contentieux OHADA',
  'contrats-commerciaux-contentieux-ohada',
  'Droit OHADA',
  'Formation Certifiante',
  'Rédiger et sécuriser les contrats commerciaux selon l\'OHADA, prévenir les contentieux et gérer les procédures de recouvrement et d\'arbitrage CCJA.',
  'Rédiger des contrats commerciaux sécurisés, identifier les clauses à risque, gérer les litiges et maîtriser les procédures d\'arbitrage OHADA.',
  'Formation des contrats sous droit OHADA; Clauses essentielles (objet, prix, garanties, pénalités); Contrats spéciaux (vente, bail commercial, mandat, entreprise); Clause compromissoire et arbitrage CCJA; Sûretés OHADA (hypothèque, nantissement, cautionnement); Procédures simplifiées de recouvrement (AUPSRVE); Voies d exécution et saisies; Résolution amiable et médiation commerciale',
  '3 jours', 'hybride', 200000, 130000, 0, 0,
  'inactive', NOW(), NOW()
),

-- ==============================
-- LOGISTIQUE & SUPPLY CHAIN
-- ==============================
(
  'Transit Douanier & Procédures DGD — Port d\'Abidjan',
  'transit-douanier-procedures-dgd-port-abidjan',
  'Logistique & SCM',
  'Formation Certifiante',
  'Maîtriser les procédures douanières de la DGD Côte d\'Ivoire, les régimes douaniers, les déclarations SYDAM et les opérations de transit au Port d\'Abidjan.',
  'Réaliser les formalités d\'import-export, utiliser SYDAM World, calculer les droits et taxes, et gérer les régimes économiques en douane.',
  'Organisation de la Douane ivoirienne (DGD); Nomenclature tarifaire et classification SH; Valeur en douane et droits de douane; Régimes douaniers (importation, exportation, transit, entrepôt); SYDAM World : saisie et validation de la déclaration; Documents obligatoires (connaissement, facture, certificats); Gestion du dédouanement et enlèvement; Régimes économiques (admission temporaire, perfectionnement); Contrôle douanier et contentieux',
  '4 jours', 'hybride', 220000, 140000, 0, 0,
  'inactive', NOW(), NOW()
),
(
  'Import-Export Côte d\'Ivoire — Guichet Unique & SYDAM',
  'import-export-cote-ivoire-guichet-unique-sydam',
  'Logistique & Supply Chain',
  'Formation Certifiante',
  'Gérer les opérations d\'import-export en Côte d\'Ivoire : Guichet Unique du Commerce Extérieur, SYDAM World, Incoterms et financement du commerce international.',
  'Maîtriser les procédures d\'import-export ivoiriennes, utiliser le Guichet Unique, choisir les bons Incoterms et financer les opérations de commerce extérieur.',
  'Commerce extérieur ivoirien : acteurs et réglementation; Guichet Unique du Commerce Extérieur (GUCE); Licences d importation et d exportation; Incoterms 2020 : choix et impact logistique; Documents du commerce international; Modes de financement (crédit documentaire, remise documentaire); Transport maritime, aérien et terrestre; Assurance transport; Inspection Cotecna et BIVAC; Exercices pratiques sur dossiers réels',
  '3 jours', 'hybride', 200000, 130000, 0, 0,
  'inactive', NOW(), NOW()
),
(
  'Gestion de Flotte & Transport Routier en Afrique',
  'gestion-flotte-transport-routier-afrique',
  'Logistique & Supply Chain',
  'Formation Certifiante',
  'Optimiser la gestion d\'une flotte de véhicules : maintenance préventive, coûts d\'exploitation, suivi GPS, réglementation du transport routier en Afrique de l\'Ouest.',
  'Gérer une flotte de façon rentable, optimiser les coûts kilométriques, planifier la maintenance et assurer la conformité réglementaire du transport routier.',
  'Organisation et dimensionnement de la flotte; Réglementation du transport routier CEDEAO; Coût de revient kilométrique et optimisation; Maintenance préventive et corrective; Suivi et géolocalisation (GPS, GPRS); Gestion des conducteurs et sécurité routière; Gestion des carburants et lubrifiants; Indicateurs de performance de la flotte (KPIs); Logiciels de gestion de flotte',
  '3 jours', 'hybride', 150000, 95000, 0, 0,
  'inactive', NOW(), NOW()
),

-- ==============================
-- AGRICULTURE & AGROBUSINESS
-- ==============================
(
  'Filière Cacao — Certification & Traçabilité (Côte d\'Ivoire)',
  'filiere-cacao-certification-tracabilite-cote-ivoire',
  'Agrobusiness',
  'Formation Certifiante',
  'Maîtriser les exigences de certification et de traçabilité dans la filière cacao ivoirienne : Rainforest Alliance, UTZ, Fairtrade, Loi EUDR et gestion durable.',
  'Mettre en place un système de traçabilité du cacao, satisfaire aux exigences des certifications internationales et préparer l\'entreprise à la Loi EUDR.',
  'Organisation de la filière cacao en Côte d Ivoire (CCC, coopératives); Exigences des certifications (Rainforest Alliance, Fairtrade, UTZ); Traçabilité de la production à l exportation; Due Diligence EUDR et cartographie géospatiale; Pratiques agricoles durables (GAP); Gestion des coopératives certifiées; Systèmes d information de traçabilité; Exportation et accès aux marchés premium',
  '3 jours', 'hybride', 180000, 110000, 0, 0,
  'inactive', NOW(), NOW()
),
(
  'Accès au Financement Agricole — Warrantage & Crédit-Stockage',
  'acces-financement-agricole-warrantage-credit-stockage',
  'Agriculture',
  'Formation Certifiante',
  'Accéder aux financements agricoles en Afrique de l\'Ouest : mécanisme du warrantage, crédit-stockage, subventions FIDA/BOAD et financement des coopératives.',
  'Monter un dossier de financement agricole, utiliser le warrantage comme garantie de crédit, accéder aux lignes de financement des IMF et institutions de développement.',
  'Environnement du financement agricole en Afrique; Mécanisme du warrantage (dépôt, crédit, remboursement); Gestion des magasins de stockage; IMF et banques agricoles (CNCA, Advans, COFINA); Subventions et appuis des projets (FIDA, BOAD, BAD); Financement des coopératives agricoles; Constitution d un dossier de crédit agricole; Gestion des risques agricoles (assurance récolte)',
  '2 jours', 'hybride', 130000, 80000, 0, 0,
  'inactive', NOW(), NOW()
),
(
  'Agrobusiness & Transformation Agroalimentaire',
  'agrobusiness-transformation-agroalimentaire',
  'Agrobusiness',
  'Formation Certifiante',
  'Créer et gérer une unité de transformation agroalimentaire : étude de marché, process de transformation, normes sanitaires, commercialisation et financement.',
  'Monter un projet agrobusiness viable, maîtriser les technologies de transformation, respecter les normes sanitaires et commercialiser les produits sur les marchés locaux et régionaux.',
  'Opportunités de l agrobusiness en Afrique de l Ouest; Étude de marché et faisabilité technique; Technologies de transformation (séchage, extraction, emballage); Normes HACCP et sécurité alimentaire; Certification et labellisation des produits; Plan d affaires agrobusiness; Financement (FDFP, BNDA, impact investors); Commercialisation et accès aux marchés (GMS, export)',
  '4 jours', 'hybride', 220000, 140000, 0, 0,
  'inactive', NOW(), NOW()
),

-- ==============================
-- BANQUE & ASSURANCE
-- ==============================
(
  'Réglementation BCEAO & Conformité Bancaire',
  'reglementation-bceao-conformite-bancaire',
  'Banque & Assurance',
  'Formation Certifiante',
  'Maîtriser le cadre réglementaire BCEAO applicable aux établissements de crédit de l\'UEMOA : ratios prudentiels, LBC/FT, instructions et circulaires BCEAO.',
  'Assurer la conformité réglementaire d\'un établissement bancaire, appliquer les ratios prudentiels BCEAO et mettre en place un dispositif LBC/FT efficace.',
  'Architecture réglementaire BCEAO/UMOA; Loi bancaire UEMOA et agrément; Ratios prudentiels (solvabilité, liquidité, division des risques); Dispositif LBC/FT : obligations KYC, déclarations CENTIF; Instructions et circulaires BCEAO (récentes); Supervision bancaire et contrôle sur place; Gestion des risques bancaires (crédit, marché, opérationnel); Fintech et paiements électroniques : cadre BCEAO',
  '4 jours', 'hybride', 280000, 170000, 0, 0,
  'inactive', NOW(), NOW()
),
(
  'Assurance CIMA — Code des Assurances Zone CIMA',
  'assurance-cima-code-assurances-zone-cima',
  'Assurance',
  'Formation Certifiante',
  'Maîtriser le Code CIMA des assurances : produits d\'assurance vie et non-vie, contrats, sinistres, réglementation prudentielle et distribution en Afrique.',
  'Comprendre et appliquer le Code CIMA, gérer les contrats d\'assurance, instruire les sinistres et assurer la conformité réglementaire dans la zone CIMA.',
  'Architecture institutionnelle CIMA; Code CIMA : structure et dispositions générales; Assurances vie (Vie entière, Capital décès, Épargne); Assurances non-vie (IARD, RC, Automobile); Formation et exécution du contrat d assurance; Gestion et règlement des sinistres; Réglementation prudentielle CIMA (marges, provisions); Distribution et intermédiation (agents, courtiers); Assurance agricole et microassurance',
  '4 jours', 'hybride', 250000, 150000, 0, 0,
  'inactive', NOW(), NOW()
),
(
  'Crédit & Recouvrement — Analyse et Gestion du Risque Client',
  'credit-recouvrement-analyse-gestion-risque-client',
  'Banque & Assurance',
  'Formation Certifiante',
  'Évaluer le risque crédit, instruire les dossiers de prêt et mettre en place des stratégies efficaces de recouvrement des créances amiables et contentieuses.',
  'Analyser la solvabilité d\'un client, accorder le crédit dans les règles, prévenir les impayés et gérer le recouvrement jusqu\'à la phase judiciaire.',
  'Analyse financière et scoring crédit; Instruction et décision de crédit; Garanties et sûretés (hypothèque, nantissement, cautionnement); Suivi du portefeuille et détection précoce des défauts; Stratégies de recouvrement amiable; Procédures de recouvrement contentieux (OHADA); Provisions et gestion des créances douteuses; Exercices pratiques sur dossiers de crédit',
  '3 jours', 'hybride', 180000, 110000, 0, 0,
  'inactive', NOW(), NOW()
),

-- ==============================
-- GRH
-- ==============================
(
  'Paie Ivoirienne — CNPS, IRVM & Bulletins de Salaire',
  'paie-ivoirienne-cnps-irvm-bulletins-salaire',
  'RH & Paie Ivoirienne',
  'Formation Certifiante',
  'Établir les bulletins de paie selon la législation sociale ivoirienne : calcul du salaire net, cotisations CNPS, IRVM, retenues à la source et déclarations sociales.',
  'Calculer les salaires et charges sociales selon le droit ivoirien, produire les bulletins de paie conformes, réaliser les déclarations CNPS et gérer les variables de paie.',
  'Cadre légal de la paie en Côte d Ivoire (Code du Travail, CNPS); Éléments constitutifs du salaire (brut, net, charges); SMIG et grilles salariales; Calcul des cotisations CNPS (retraite, AT/MP, prestations familiales); IRVM et retenues à la source; Indemnités exonérées et soumises; Congés payés et provisions; Solde de tout compte; Logiciels de paie (Sage Paie, Excel paie); Déclarations mensuelles et annuelles',
  '3 jours', 'hybride', 200000, 130000, 0, 0,
  'inactive', NOW(), NOW()
),
(
  'Recrutement & Conduite des Entretiens Structurés',
  'recrutement-conduite-entretiens-structures',
  'Ressources Humaines',
  'Formation Certifiante',
  'Maîtriser les méthodes de recrutement modernes : rédaction des fiches de poste, sourcing, évaluation par compétences et conduite d\'entretiens structurés.',
  'Structurer un processus de recrutement efficace, rédiger une offre d\'emploi attractive, conduire des entretiens par compétences et objectiver les décisions de sélection.',
  'Analyse du besoin et fiche de poste; Rédaction et diffusion des offres (JobBoard, LinkedIn, CVthèque); Sourcing et chasse de têtes; Présélection des CV et entretien téléphonique; Entretien structuré par compétences (STAR); Tests psychométriques et techniques; Présentation au jury et décision finale; Onboarding et intégration du candidat; Droit du recrutement et non-discrimination',
  '2 jours', 'hybride', 150000, 95000, 0, 0,
  'inactive', NOW(), NOW()
),

-- ==============================
-- MANAGEMENT & LEADERSHIP
-- ==============================
(
  'Gestion de Projet selon PMI — Préparation PMP',
  'gestion-projet-pmi-preparation-pmp',
  'Gestion de Projet',
  'Formation Certifiante',
  'Maîtriser le référentiel PMI/PMBOK pour gérer des projets complexes : initiation, planification, exécution, contrôle et clôture selon les meilleures pratiques internationales.',
  'Appliquer le référentiel PMI dans la gestion de projets, utiliser les outils de planification (WBS, Gantt, chemin critique) et préparer la certification PMP.',
  'Cadre PMI et PMBOK 7e édition; Groupes de processus et domaines de connaissance; Charte projet et registre des parties prenantes; WBS, planification des délais et chemin critique; Gestion des coûts et valeur acquise (EVM); Gestion des risques et registre des risques; Gestion de la qualité et des ressources humaines; Gestion des communications et des approvisionnements; Exercices pratiques et simulation examen PMP',
  '5 jours', 'hybride', 300000, 180000, 0, 0,
  'inactive', NOW(), NOW()
),
(
  'PRINCE2 — Gestion de Projet pour le Secteur Public & ONG',
  'prince2-gestion-projet-secteur-public-ong',
  'Gestion de Projet',
  'Formation Certifiante',
  'Appliquer la méthode PRINCE2 dans les projets publics, de développement et des ONG : structure organisationnelle, plans, risques, qualité et contrôle de projet.',
  'Structurer un projet selon PRINCE2, créer les livrables clés, gérer les exceptions et préparer la certification PRINCE2 Foundation ou Practitioner.',
  'Principes et environnement PRINCE2; Structure organisationnelle d un projet PRINCE2; Thèmes : Business Case, Organisation, Qualité, Plans, Risques, Changements, Progrès; Processus : démarrage, initialisation, direction, contrôle, gestion livraisons, clôture; Documents clés (PID, Plan de projet, Rapport de fin de phase); Adaptation de PRINCE2 au contexte africain et ONG; Simulation certification PRINCE2 Foundation',
  '5 jours', 'hybride', 300000, 180000, 0, 0,
  'inactive', NOW(), NOW()
),
(
  'Conduite du Changement — Accompagner les Transformations',
  'conduite-du-changement-accompagner-transformations',
  'Management',
  'Formation Certifiante',
  'Piloter la conduite du changement dans les organisations : diagnostic, stratégie d\'accompagnement, communication, gestion des résistances et ancrage durable.',
  'Analyser l\'impact d\'un changement, concevoir un plan d\'accompagnement, mobiliser les parties prenantes et mesurer l\'adoption des nouvelles pratiques.',
  'Modèles de conduite du changement (Kotter, ADKAR, Lewin); Diagnostic de la maturité au changement; Cartographie et analyse des parties prenantes; Stratégie et plan de communication du changement; Gestion des résistances et des craintes; Formation et accompagnement des équipes; Leadership du changement; Mesure de l adoption et indicateurs de transformation; Études de cas de transformations réussies',
  '3 jours', 'hybride', 200000, 130000, 0, 0,
  'inactive', NOW(), NOW()
),

-- ==============================
-- ONG & DÉVELOPPEMENT
-- ==============================
(
  'Gestion de Projet selon le Cadre Logique AFD, UE & USAID',
  'gestion-projet-cadre-logique-afd-ue-usaid',
  'ONG & Développement',
  'Formation Certifiante',
  'Concevoir et gérer des projets de développement selon le Cadre Logique des principaux bailleurs (AFD, Union Européenne, USAID, PNUD) : LFA, théorie du changement et MEAL.',
  'Élaborer un cadre logique solide, définir les indicateurs SMART, produire les rapports de suivi et évaluation exigés par les bailleurs et les parties prenantes.',
  'Environnement des projets de développement; Cadre logique (LFA) : objectifs, résultats, activités, indicateurs; Théorie du changement et chaîne de résultats; Matrice du cadre logique (MCL); Indicateurs SMART et sources de vérification; Suivi-Évaluation selon les standards AFD/UE/USAID; Plan de travail annuel (PTA) et rapports narratifs; Budget et gestion financière des projets; Revues à mi-parcours et évaluations finales',
  '4 jours', 'hybride', 250000, 150000, 0, 0,
  'inactive', NOW(), NOW()
),
(
  'Reporting Financier Bailleur — USAID, Banque Mondiale, Union Européenne',
  'reporting-financier-bailleur-usaid-banque-mondiale-ue',
  'ONG & Développement',
  'Formation Certifiante',
  'Produire les rapports financiers conformes aux exigences des principaux bailleurs de fonds internationaux : USAID, Banque Mondiale, UE, PNUD et agences onusiennes.',
  'Maîtriser les formats de reporting financier des bailleurs, gérer les fonds de projet en conformité et préparer les audits des projets de développement.',
  'Règles d éligibilité des coûts par bailleur; Comptabilité de projet et codification des dépenses; Format SF-425 (USAID) et Financial Monitoring Reports; Rapport financier UE (annexes financières); Reporting Banque Mondiale (IFR, FM Reports); Gestion des avances et justifications; Gestion des devises et des taux de change; Préparation à l audit externe (Single Audit, ACA); Exercices pratiques sur tableurs de reporting',
  '3 jours', 'hybride', 200000, 130000, 0, 0,
  'inactive', NOW(), NOW()
),
(
  'Suivi-Évaluation Avancé — ODK, KoBoCollect & Analyse des Données',
  'suivi-evaluation-avance-odk-kobocollect-analyse-donnees',
  'ONG & Développement',
  'Formation Certifiante',
  'Concevoir un système MEAL robuste, collecter des données terrain avec ODK/KoBoCollect et analyser les indicateurs de performance des projets de développement.',
  'Concevoir des formulaires de collecte sur KoBoCollect, analyser les données avec Excel ou R, produire des tableaux de bord et rédiger des rapports d\'évaluation.',
  'Conception d un système MEAL; Définition des indicateurs et plans de collecte; Formulaires de collecte numérique (KoBoToolbox, ODK); Déploiement mobile et collecte terrain; Gestion et nettoyage des données; Analyse quantitative et qualitative; Visualisation des résultats (Excel, Power BI, QGIS pour le mapping); Rédaction du rapport d évaluation; Restitution des résultats aux parties prenantes',
  '3 jours', 'hybride', 200000, 130000, 0, 0,
  'inactive', NOW(), NOW()
),

-- ==============================
-- ENTREPRENEURIAT
-- ==============================
(
  'Business Plan orienté Financement Bancaire Ivoirien',
  'business-plan-financement-bancaire-ivoirien',
  'Entrepreneuriat',
  'Formation Certifiante',
  'Rédiger un business plan solide adapté aux exigences des banques ivoiriennes et des structures de financement (BOAD, BNI, SIB, BICICI) pour obtenir un financement.',
  'Structurer un business plan convaincant, réaliser les projections financières, identifier les bons financeurs et préparer sa présentation devant un comité de crédit.',
  'Structure d un business plan (résumé exécutif, marché, modèle économique, finances); Étude de marché et analyse concurrentielle; Présentation de l offre et avantage concurrentiel; Projections financières (compte de résultat, bilan, flux de trésorerie) sur 3-5 ans; Calcul du besoin en financement et du point mort; Critères d évaluation des banques ivoiriennes; Présentation devant un jury (pitch); Simulation de comité de crédit',
  '3 jours', 'hybride', 180000, 110000, 0, 0,
  'inactive', NOW(), NOW()
),
(
  'Accès aux Financements FDFP & BPI Côte d\'Ivoire',
  'acces-financements-fdfp-bpi-cote-ivoire',
  'Entrepreneuriat',
  'Formation Certifiante',
  'Accéder aux dispositifs de financement et d\'aide aux entreprises ivoiriennes : FDFP (formation professionnelle), BPI Côte d\'Ivoire, FGPME, CEPICI et fonds de garantie.',
  'Identifier les financements adaptés à son entreprise, constituer les dossiers complets et maximiser ses chances d\'obtenir les aides et subventions disponibles en CI.',
  'Cartographie des financeurs PME en Côte d Ivoire; Fonds de Développement de la Formation Professionnelle (FDFP); BPI Côte d Ivoire et ses dispositifs; FGPME et fonds de garantie; Aides CEPICI à la création d entreprise; Financement des jeunes entrepreneurs (PEJEDEC, C2D, PNSD); Financement participatif (crowdfunding en Afrique); Montage de dossier de financement; Simulation de présentation à un fonds',
  '2 jours', 'hybride', 130000, 80000, 0, 0,
  'inactive', NOW(), NOW()
),
(
  'Commerce Électronique en Afrique — Vendre en Ligne & Marketplace',
  'commerce-electronique-afrique-vendre-en-ligne-marketplace',
  'Entrepreneuriat',
  'Formation Certifiante',
  'Lancer et développer une activité de e-commerce en Afrique : création de boutique en ligne, référencement, vente sur marketplace (Jumia, Amazon) et Mobile Money.',
  'Créer une boutique en ligne performante, référencer ses produits sur les marketplace africaines, gérer les paiements Mobile Money et développer ses ventes en ligne.',
  'E-commerce en Afrique : tendances et opportunités; Création de boutique WooCommerce ou Shopify; Catalogue produits, photos et fiches produits; Vente sur Jumia, Amazon Africa, Etsy; Intégration Mobile Money et paiement en ligne; Livraison et gestion de la logistique; SEO e-commerce et réseaux sociaux; Gestion des avis clients et réputation; Fiscalité et aspects légaux du e-commerce en CI',
  '3 jours', 'hybride', 150000, 95000, 0, 0,
  'inactive', NOW(), NOW()
),
(
  'Création d\'Entreprise en Côte d\'Ivoire — CEPICI & Procédures Légales',
  'creation-entreprise-cote-ivoire-cepici-procedures-legales',
  'Entrepreneuriat',
  'Formation Certifiante',
  'Créer son entreprise en Côte d\'Ivoire : choix de la forme juridique, procédures CEPICI, statuts, immatriculation RCCM, ouverture de compte et démarches administratives.',
  'Choisir le statut juridique adapté, réaliser les formalités de création d\'entreprise au CEPICI, immatriculer la société et effectuer les démarches post-création.',
  'Formes juridiques en CI (SARL, SA, GIE, EI, SNC) et critères de choix; Procédures CEPICI (guichet unique de création d entreprise); Rédaction des statuts; Immatriculation RCCM et numéro SIUCEN; Affiliation CNPS et DGI; Ouverture du compte bancaire professionnel; Licences et autorisations sectorielles; Régime fiscal et obligations comptables de démarrage; Plan d action des 100 premiers jours',
  '2 jours', 'hybride', 130000, 80000, 0, 0,
  'inactive', NOW(), NOW()
);

-- Confirmation du nombre d'insertions
SELECT COUNT(*) AS nouvelles_formations_ajoutees FROM formations WHERE slug IN (
  'syscohada-revise-comptabilite-ohada',
  'declaration-fiscale-dgi-cote-ivoire',
  'fiscalite-pme-cote-ivoire',
  'audit-interne-normes-iia',
  'microfinance-gestion-sfd',
  'analyse-financiere-etats-syscohada',
  'power-bi-tableaux-de-bord-business-intelligence',
  'odoo-erp-gestion-integree-entreprise',
  'administration-reseau-cybersecurite-pme',
  'wordpress-seo-site-professionnel',
  'mobile-money-integration-paiements-mobiles-afrique',
  'droit-ohada-societes-sarl-sa-gie',
  'droit-du-travail-ivoirien-code-pratique-rh',
  'passation-marches-publics-code-ivoirien-anrmp',
  'droit-uemoa-reglementations-bceao',
  'contrats-commerciaux-contentieux-ohada',
  'transit-douanier-procedures-dgd-port-abidjan',
  'import-export-cote-ivoire-guichet-unique-sydam',
  'gestion-flotte-transport-routier-afrique',
  'filiere-cacao-certification-tracabilite-cote-ivoire',
  'acces-financement-agricole-warrantage-credit-stockage',
  'agrobusiness-transformation-agroalimentaire',
  'reglementation-bceao-conformite-bancaire',
  'assurance-cima-code-assurances-zone-cima',
  'credit-recouvrement-analyse-gestion-risque-client',
  'paie-ivoirienne-cnps-irvm-bulletins-salaire',
  'recrutement-conduite-entretiens-structures',
  'gestion-projet-pmi-preparation-pmp',
  'prince2-gestion-projet-secteur-public-ong',
  'conduite-du-changement-accompagner-transformations',
  'gestion-projet-cadre-logique-afd-ue-usaid',
  'reporting-financier-bailleur-usaid-banque-mondiale-ue',
  'suivi-evaluation-avance-odk-kobocollect-analyse-donnees',
  'business-plan-financement-bancaire-ivoirien',
  'acces-financements-fdfp-bpi-cote-ivoire',
  'commerce-electronique-afrique-vendre-en-ligne-marketplace',
  'creation-entreprise-cote-ivoire-cepici-procedures-legales'
);
