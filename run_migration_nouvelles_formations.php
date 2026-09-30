<?php
if (empty($_GET['run'])) die('Ajoutez ?run=1 pour exécuter.');
require_once __DIR__ . '/core/config.php';
require_once __DIR__ . '/core/database.php';

$pdo = Database::connect();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$formations = [
  // titre, slug, domaine, type_certificat, description, objectifs, modules, duree, mode, tarif_presentiel, tarif_en_ligne
  ['SYSCOHADA Révisé — Comptabilité selon le Référentiel OHADA','syscohada-revise-comptabilite-ohada','Comptabilité','Formation Certifiante',
   'Maîtriser le référentiel comptable OHADA révisé : plan de comptes, états financiers, consolidation et conformité réglementaire pour entreprises de la zone UEMOA.',
   'Produire des états financiers conformes au SYSCOHADA révisé, maîtriser le plan de comptes OHADA, réaliser les écritures de régularisation et préparer les documents de synthèse.',
   'Principes fondamentaux SYSCOHADA révisé; Plan comptable OHADA (classes 1 à 8); Opérations courantes et écritures; Amortissements et provisions; Régularisations de fin d\'exercice; Bilan, Compte de résultat et Tableau des flux; Notes annexes et liasse fiscale; Cas pratiques ivoiriens',
   '5 jours','hybride',300000,180000],

  ['Déclaration Fiscale DGI Côte d\'Ivoire — TVA, IS, IRVM','declaration-fiscale-dgi-cote-ivoire','Fiscalité','Formation Certifiante',
   'Maîtriser les obligations fiscales envers la DGI Côte d\'Ivoire : TVA, IS, IRVM, patente, télédéclaration et gestion des contrôles fiscaux.',
   'Produire les déclarations fiscales conformes à la réglementation ivoirienne, utiliser les formulaires DGI, gérer les échéances et anticiper les risques fiscaux.',
   'Système fiscal ivoirien; TVA : base imposable, taux, déclaration mensuelle; IS et BNC : calcul et acomptes; IRVM et retenues à la source; Patente et taxes locales; Télédéclaration DGI-net; Contrôle fiscal et contentieux; Exercices sur formulaires DGI',
   '3 jours','hybride',180000,110000],

  ['Fiscalité des PME en Côte d\'Ivoire','fiscalite-pme-cote-ivoire','Fiscalité','Formation Certifiante',
   'Optimiser la fiscalité des PME ivoiriennes : régimes d\'imposition, obligations déclaratives, TVA, IS, patente et stratégies d\'optimisation fiscale légale.',
   'Identifier le régime fiscal adapté à la PME, maîtriser les obligations déclaratives et optimiser la charge fiscale dans le respect de la loi ivoirienne.',
   'Régimes fiscaux PME (réel simplifié, normal, taxe synthétique); Obligations déclaratives et délais; Comptabilisation de la TVA; Optimisation fiscale légale; Avantages fiscaux (zones franches, exonérations); Cas pratiques PME ivoiriennes',
   '3 jours','hybride',180000,110000],

  ['Audit Interne selon les Normes IIA','audit-interne-normes-iia','Audit & Contrôle','Formation Certifiante',
   'Conduire des missions d\'audit interne conformément aux Normes Internationales pour la Pratique Professionnelle de l\'Audit Interne (IPPF / IIA).',
   'Planifier et conduire une mission d\'audit interne, rédiger des rapports conformes aux normes IIA et formuler des recommandations à valeur ajoutée.',
   'Cadre de référence IIA (IPPF) et code d\'éthique; Cartographie des risques et plan d\'audit; Phases d\'une mission : planification, terrain, rapport; Techniques de collecte des preuves; Rédaction du rapport d\'audit; Suivi des recommandations; Audit des processus clés; Cas d\'audit réels',
   '5 jours','hybride',280000,170000],

  ['Microfinance & Gestion des Systèmes Financiers Décentralisés (SFD)','microfinance-gestion-sfd','Microfinance','Formation Certifiante',
   'Gérer et développer une institution de microfinance : réglementation BCEAO, produits financiers, gestion du risque de crédit et pilotage de la performance.',
   'Maîtriser le cadre réglementaire des SFD, gérer le portefeuille de crédit, analyser la performance financière et assurer la conformité aux normes BCEAO.',
   'Réglementation BCEAO et loi PARMEC; Gouvernance et organisation SFD; Produits financiers (épargne, crédit, assurance solidaire); Analyse des dossiers de crédit; Gestion du risque et recouvrement; Ratios prudentiels; Système d\'information de gestion; Cas de terrain',
   '4 jours','hybride',220000,140000],

  ['Analyse Financière des États SYSCOHADA','analyse-financiere-etats-syscohada','Finance','Formation Certifiante',
   'Analyser les états financiers SYSCOHADA : bilan, compte de résultat, tableau de flux, pour diagnostiquer la santé financière d\'une entreprise.',
   'Lire et interpréter les états financiers SYSCOHADA, calculer les ratios clés, établir un diagnostic financier et formuler des recommandations.',
   'Lecture et retraitement du bilan SYSCOHADA; Analyse du compte de résultat (SIG); Flux de trésorerie; Ratios de liquidité, solvabilité et rentabilité; Scoring financier; Analyse FDR, BFR, TN; Exercices sur liasses fiscales réelles; Rapport de diagnostic financier',
   '3 jours','hybride',200000,130000],

  ['Power BI — Tableaux de Bord & Business Intelligence','power-bi-tableaux-de-bord-business-intelligence','Data & BI','Formation Certifiante',
   'Créer des tableaux de bord interactifs avec Microsoft Power BI : import de données, modélisation, calculs DAX et publication de rapports professionnels.',
   'Importer et transformer des données, créer des modèles, écrire des mesures DAX et publier des rapports interactifs sur Power BI Service.',
   'Prise en main de Power BI Desktop; Connexion aux sources de données; Power Query : nettoyage et transformation; Modélisation (relations, schéma étoile); DAX : mesures, colonnes calculées, KPIs; Visualisations avancées; Publication Power BI Service; Tableaux de bord et alertes',
   '3 jours','hybride',180000,110000],

  ['Odoo ERP — Gestion Intégrée de l\'Entreprise','odoo-erp-gestion-integree-entreprise','Odoo','Formation Certifiante',
   'Déployer et utiliser Odoo ERP pour gérer la comptabilité, les achats, les ventes, les stocks et les ressources humaines d\'une PME.',
   'Configurer et utiliser les modules clés d\'Odoo, automatiser les processus et extraire des rapports de gestion.',
   'Architecture Odoo; Module Comptabilité; Module Ventes et CRM; Module Achats; Module Stocks; Module RH et Paie; Paramétrage; Reporting',
   '4 jours','hybride',220000,140000],

  ['Administration Réseau & Cybersécurité pour PME','administration-reseau-cybersecurite-pme','Cybersécurité','Formation Certifiante',
   'Sécuriser l\'infrastructure informatique d\'une PME : administration réseau, pare-feux, VPN, gestion des accès, détection des menaces et réponse aux incidents.',
   'Administrer un réseau local, configurer les équipements de sécurité, identifier les vulnérabilités et mettre en place une politique de sécurité informatique.',
   'Fondamentaux réseaux (TCP/IP, LAN, WAN, Wi-Fi); Configuration routeurs et switches; Pare-feux (pfSense, iptables); VPN et accès distants sécurisés; Active Directory et gestion des identités; Détection des intrusions (IDS/IPS); Sauvegardes et plan de reprise; Réponse aux incidents et journalisation',
   '4 jours','hybride',200000,130000],

  ['WordPress & SEO — Créer et Référencer son Site Professionnel','wordpress-seo-site-professionnel','WordPress & Web','Formation Certifiante',
   'Créer un site web professionnel avec WordPress, l\'optimiser pour les moteurs de recherche et gérer son contenu en toute autonomie.',
   'Créer un site WordPress professionnel, configurer les extensions essentielles, rédiger du contenu optimisé SEO et analyser les performances.',
   'Installation et configuration WordPress; Thème et personnalisation (Elementor); Pages, articles, médias; Extensions (Yoast SEO, WooCommerce, Contact Form 7); SEO on-page; Mots-clés et structure de contenu; Google Search Console et Analytics; Sécurité et maintenance',
   '3 jours','hybride',120000,75000],

  ['Mobile Money & Intégration des Paiements Mobiles en Afrique','mobile-money-integration-paiements-mobiles-afrique','Digital & IA','Formation Certifiante',
   'Comprendre l\'écosystème Mobile Money en Afrique de l\'Ouest et intégrer les APIs de paiement dans les applications métiers.',
   'Maîtriser les APIs des opérateurs de paiement mobile, intégrer des solutions de paiement dans une application et assurer la conformité réglementaire.',
   'Écosystème Mobile Money (Orange, MTN, Wave, Moneroo); Fonctionnement technique; APIs REST : auth, requêtes, webhooks; Intégration Orange Money et MTN MoMo; Integration Wave Business; Tests et sandbox; Réconciliation et gestion des erreurs; Conformité BCEAO',
   '3 jours','hybride',180000,110000],

  ['Droit OHADA des Sociétés — SARL, SA, GIE','droit-ohada-societes-sarl-sa-gie','Droit OHADA','Formation Certifiante',
   'Maîtriser l\'Acte Uniforme OHADA sur les sociétés commerciales : constitution, gouvernance, responsabilité des dirigeants et dissolution des SARL, SA et GIE.',
   'Rédiger les statuts, structurer la gouvernance, gérer les assemblées générales et assurer la conformité aux dispositions de l\'OHADA.',
   'Acte Uniforme OHADA des Sociétés; Constitution des SARL, SA et GIE; Capital social et apports; Organes de direction et gouvernance; Responsabilité des dirigeants; Assemblées générales; Modifications statutaires; Dissolution et liquidation; Jurisprudence CCJA',
   '4 jours','hybride',250000,150000],

  ['Droit du Travail Ivoirien — Code du Travail & Pratique RH','droit-du-travail-ivoirien-code-pratique-rh','Droit & Juridique','Formation Certifiante',
   'Maîtriser le Code du Travail ivoirien : contrats, licenciements, conventions collectives et contentieux prud\'homal.',
   'Rédiger et gérer les contrats de travail conformément au droit ivoirien, prévenir les risques de contentieux et gérer les procédures de rupture du contrat.',
   'Sources du droit du travail ivoirien; Types de contrats (CDI, CDD, intérim); Rémunération et SMIG; Temps de travail et congés; Licenciement individuel et collectif; Sanctions disciplinaires; Conventions collectives; Contentieux prud\'homal; Inspection du Travail',
   '3 jours','hybride',180000,110000],

  ['Passation des Marchés Publics selon le Code Ivoirien (ANRMP)','passation-marches-publics-code-ivoirien-anrmp','Marchés Publics','Formation Certifiante',
   'Maîtriser les procédures de passation des marchés publics en Côte d\'Ivoire selon le Code des Marchés Publics et les directives ANRMP.',
   'Préparer un dossier d\'appel d\'offres, conduire une procédure de passation, évaluer les offres et assurer la conformité réglementaire.',
   'Cadre réglementaire marchés publics CI; Principes fondamentaux; Modes de passation (appel d\'offres ouvert, restreint, gré à gré); Dossier d\'Appel d\'Offres (DAO); Évaluation des offres et attribution; Exécution du marché; Contrôle et audit; Recours devant l\'ANRMP; Exercices pratiques sur DAO réels',
   '4 jours','hybride',250000,150000],

  ['Droit UEMOA & Réglementations BCEAO','droit-uemoa-reglementations-bceao','Droit & Juridique','Formation Certifiante',
   'Comprendre le cadre juridique de l\'UEMOA et les directives de la BCEAO applicables aux entreprises et institutions financières de la zone franc.',
   'Identifier les textes UEMOA applicables à l\'entreprise, assurer la conformité aux directives BCEAO et gérer les opérations transfrontalières.',
   'Architecture institutionnelle UEMOA; Directives TVA et impôts; Réglementation des changes BCEAO; LBC/FT et obligations KYC; Règlement financier UEMOA; Commerce intracommunautaire et TEC; Libre circulation des personnes; Cour de Justice UEMOA',
   '3 jours','hybride',200000,130000],

  ['Contrats Commerciaux & Contentieux OHADA','contrats-commerciaux-contentieux-ohada','Droit OHADA','Formation Certifiante',
   'Rédiger et sécuriser les contrats commerciaux selon l\'OHADA, prévenir les contentieux et gérer les procédures de recouvrement et d\'arbitrage CCJA.',
   'Rédiger des contrats commerciaux sécurisés, identifier les clauses à risque, gérer les litiges et maîtriser les procédures d\'arbitrage OHADA.',
   'Formation des contrats sous droit OHADA; Clauses essentielles; Contrats spéciaux (vente, bail, mandat); Arbitrage CCJA; Sûretés OHADA; Procédures simplifiées de recouvrement (AUPSRVE); Voies d\'exécution et saisies; Médiation commerciale',
   '3 jours','hybride',200000,130000],

  ['Transit Douanier & Procédures DGD — Port d\'Abidjan','transit-douanier-procedures-dgd-port-abidjan','Logistique & SCM','Formation Certifiante',
   'Maîtriser les procédures douanières DGD Côte d\'Ivoire, les régimes douaniers, les déclarations SYDAM et les opérations de transit au Port d\'Abidjan.',
   'Réaliser les formalités d\'import-export, utiliser SYDAM World, calculer les droits et taxes, et gérer les régimes économiques en douane.',
   'Organisation de la Douane ivoirienne (DGD); Nomenclature tarifaire et SH; Valeur en douane et droits; Régimes douaniers (import, export, transit, entrepôt); SYDAM World : saisie et validation; Documents obligatoires; Régimes économiques; Contrôle douanier et contentieux',
   '4 jours','hybride',220000,140000],

  ['Import-Export Côte d\'Ivoire — Guichet Unique & SYDAM','import-export-cote-ivoire-guichet-unique-sydam','Logistique & Supply Chain','Formation Certifiante',
   'Gérer les opérations d\'import-export en Côte d\'Ivoire : Guichet Unique du Commerce Extérieur, SYDAM World, Incoterms et financement du commerce international.',
   'Maîtriser les procédures d\'import-export ivoiriennes, utiliser le Guichet Unique, choisir les bons Incoterms et financer les opérations de commerce extérieur.',
   'Commerce extérieur ivoirien; Guichet Unique du Commerce Extérieur (GUCE); Licences import et export; Incoterms 2020; Documents commerciaux internationaux; Financement (crédit documentaire, remise documentaire); Modes de transport; Assurance transport; Inspection Cotecna et BIVAC',
   '3 jours','hybride',200000,130000],

  ['Gestion de Flotte & Transport Routier en Afrique','gestion-flotte-transport-routier-afrique','Logistique & Supply Chain','Formation Certifiante',
   'Optimiser la gestion d\'une flotte de véhicules : maintenance préventive, coûts d\'exploitation, suivi GPS et réglementation du transport routier en Afrique de l\'Ouest.',
   'Gérer une flotte de façon rentable, optimiser les coûts kilométriques, planifier la maintenance et assurer la conformité réglementaire.',
   'Organisation et dimensionnement de la flotte; Réglementation transport routier CEDEAO; Coût de revient kilométrique; Maintenance préventive et corrective; Suivi GPS et géolocalisation; Gestion des conducteurs et sécurité routière; Gestion des carburants; KPIs de la flotte; Logiciels de gestion de flotte',
   '3 jours','hybride',150000,95000],

  ['Filière Cacao — Certification & Traçabilité (Côte d\'Ivoire)','filiere-cacao-certification-tracabilite-cote-ivoire','Agrobusiness','Formation Certifiante',
   'Maîtriser les exigences de certification et de traçabilité dans la filière cacao ivoirienne : Rainforest Alliance, UTZ, Fairtrade, Loi EUDR et gestion durable.',
   'Mettre en place un système de traçabilité du cacao, satisfaire aux exigences des certifications internationales et préparer l\'entreprise à la Loi EUDR.',
   'Organisation filière cacao en CI (CCC, coopératives); Certifications (Rainforest Alliance, Fairtrade, UTZ); Traçabilité de la production à l\'exportation; Due Diligence EUDR et cartographie géospatiale; Pratiques agricoles durables; Gestion des coopératives certifiées; Exportation et marchés premium',
   '3 jours','hybride',180000,110000],

  ['Accès au Financement Agricole — Warrantage & Crédit-Stockage','acces-financement-agricole-warrantage-credit-stockage','Agriculture','Formation Certifiante',
   'Accéder aux financements agricoles en Afrique de l\'Ouest : mécanisme du warrantage, crédit-stockage, subventions FIDA/BOAD et financement des coopératives.',
   'Monter un dossier de financement agricole, utiliser le warrantage comme garantie, accéder aux lignes de financement des IMF et institutions de développement.',
   'Financement agricole en Afrique; Mécanisme du warrantage; Gestion des magasins de stockage; IMF et banques agricoles; Subventions FIDA, BOAD, BAD; Financement des coopératives; Constitution d\'un dossier de crédit agricole; Assurance récolte',
   '2 jours','hybride',130000,80000],

  ['Agrobusiness & Transformation Agroalimentaire','agrobusiness-transformation-agroalimentaire','Agrobusiness','Formation Certifiante',
   'Créer et gérer une unité de transformation agroalimentaire : étude de marché, process de transformation, normes sanitaires, commercialisation et financement.',
   'Monter un projet agrobusiness viable, maîtriser les technologies de transformation, respecter les normes sanitaires et commercialiser les produits.',
   'Opportunités agrobusiness en Afrique de l\'Ouest; Étude de marché et faisabilité; Technologies de transformation (séchage, extraction, emballage); Normes HACCP et sécurité alimentaire; Certification et labellisation; Plan d\'affaires agrobusiness; Financement (FDFP, BNDA, impact investors); Commercialisation et accès aux marchés',
   '4 jours','hybride',220000,140000],

  ['Réglementation BCEAO & Conformité Bancaire','reglementation-bceao-conformite-bancaire','Banque & Assurance','Formation Certifiante',
   'Maîtriser le cadre réglementaire BCEAO applicable aux établissements de crédit de l\'UEMOA : ratios prudentiels, LBC/FT, instructions et circulaires BCEAO.',
   'Assurer la conformité réglementaire d\'un établissement bancaire, appliquer les ratios prudentiels BCEAO et mettre en place un dispositif LBC/FT efficace.',
   'Architecture réglementaire BCEAO/UMOA; Loi bancaire UEMOA et agrément; Ratios prudentiels (solvabilité, liquidité); LBC/FT : KYC, déclarations CENTIF; Instructions BCEAO récentes; Supervision bancaire; Gestion des risques bancaires; Fintech et paiements BCEAO',
   '4 jours','hybride',280000,170000],

  ['Assurance CIMA — Code des Assurances Zone CIMA','assurance-cima-code-assurances-zone-cima','Assurance','Formation Certifiante',
   'Maîtriser le Code CIMA des assurances : produits d\'assurance vie et non-vie, contrats, sinistres, réglementation prudentielle et distribution en Afrique.',
   'Comprendre et appliquer le Code CIMA, gérer les contrats d\'assurance, instruire les sinistres et assurer la conformité réglementaire.',
   'Architecture institutionnelle CIMA; Code CIMA : dispositions générales; Assurances vie (Capital décès, Épargne); Assurances non-vie (IARD, RC, Auto); Formation et exécution du contrat; Gestion et règlement des sinistres; Réglementation prudentielle CIMA; Distribution et intermédiation; Microassurance',
   '4 jours','hybride',250000,150000],

  ['Crédit & Recouvrement — Analyse et Gestion du Risque Client','credit-recouvrement-analyse-gestion-risque-client','Banque & Assurance','Formation Certifiante',
   'Évaluer le risque crédit, instruire les dossiers de prêt et mettre en place des stratégies de recouvrement des créances amiables et contentieuses.',
   'Analyser la solvabilité, accorder le crédit dans les règles, prévenir les impayés et gérer le recouvrement jusqu\'à la phase judiciaire.',
   'Analyse financière et scoring crédit; Instruction et décision de crédit; Garanties et sûretés OHADA; Suivi du portefeuille crédit; Stratégies de recouvrement amiable; Recouvrement contentieux (OHADA); Provisions et créances douteuses; Exercices pratiques sur dossiers de crédit',
   '3 jours','hybride',180000,110000],

  ['Paie Ivoirienne — CNPS, IRVM & Bulletins de Salaire','paie-ivoirienne-cnps-irvm-bulletins-salaire','RH & Paie Ivoirienne','Formation Certifiante',
   'Établir les bulletins de paie selon la législation sociale ivoirienne : calcul du salaire net, cotisations CNPS, IRVM, retenues à la source et déclarations sociales.',
   'Calculer les salaires et charges sociales selon le droit ivoirien, produire les bulletins de paie conformes, réaliser les déclarations CNPS.',
   'Cadre légal de la paie en CI (Code du Travail, CNPS); Éléments constitutifs du salaire; SMIG et grilles salariales; Cotisations CNPS (retraite, AT/MP, prestations familiales); IRVM et retenues à la source; Indemnités exonérées; Congés payés et provisions; Solde de tout compte; Logiciels de paie; Déclarations mensuelles et annuelles',
   '3 jours','hybride',200000,130000],

  ['Recrutement & Conduite des Entretiens Structurés','recrutement-conduite-entretiens-structures','Ressources Humaines','Formation Certifiante',
   'Maîtriser les méthodes de recrutement modernes : rédaction des fiches de poste, sourcing, évaluation par compétences et conduite d\'entretiens structurés.',
   'Structurer un processus de recrutement efficace, rédiger une offre attractive, conduire des entretiens par compétences et objectiver les décisions de sélection.',
   'Analyse du besoin et fiche de poste; Rédaction et diffusion des offres; Sourcing et chasse de têtes; Présélection et entretien téléphonique; Entretien structuré par compétences (STAR); Tests psychométriques; Décision finale et onboarding; Droit du recrutement et non-discrimination',
   '2 jours','hybride',150000,95000],

  ['Gestion de Projet selon PMI — Préparation PMP','gestion-projet-pmi-preparation-pmp','Gestion de Projet','Formation Certifiante',
   'Maîtriser le référentiel PMI/PMBOK pour gérer des projets complexes : initiation, planification, exécution, contrôle et clôture selon les meilleures pratiques internationales.',
   'Appliquer le référentiel PMI dans la gestion de projets, utiliser les outils de planification et préparer la certification PMP.',
   'Cadre PMI et PMBOK 7e édition; Groupes de processus; Charte projet et parties prenantes; WBS, planification et chemin critique; Gestion des coûts et valeur acquise (EVM); Gestion des risques; Qualité et ressources humaines; Communications et approvisionnements; Simulation examen PMP',
   '5 jours','hybride',300000,180000],

  ['PRINCE2 — Gestion de Projet pour le Secteur Public & ONG','prince2-gestion-projet-secteur-public-ong','Gestion de Projet','Formation Certifiante',
   'Appliquer la méthode PRINCE2 dans les projets publics et des ONG : structure organisationnelle, plans, risques, qualité et contrôle de projet.',
   'Structurer un projet selon PRINCE2, créer les livrables clés, gérer les exceptions et préparer la certification PRINCE2 Foundation.',
   'Principes et environnement PRINCE2; Structure organisationnelle; Thèmes : Business Case, Organisation, Qualité, Plans, Risques, Changements, Progrès; Processus PRINCE2; Documents clés (PID, Plan de projet, Rapport de fin de phase); Adaptation au contexte africain et ONG; Simulation certification PRINCE2 Foundation',
   '5 jours','hybride',300000,180000],

  ['Conduite du Changement — Accompagner les Transformations','conduite-du-changement-accompagner-transformations','Management','Formation Certifiante',
   'Piloter la conduite du changement dans les organisations : diagnostic, stratégie d\'accompagnement, communication, gestion des résistances et ancrage durable.',
   'Analyser l\'impact d\'un changement, concevoir un plan d\'accompagnement, mobiliser les parties prenantes et mesurer l\'adoption.',
   'Modèles de conduite du changement (Kotter, ADKAR, Lewin); Diagnostic de la maturité au changement; Cartographie des parties prenantes; Plan de communication; Gestion des résistances; Formation et accompagnement des équipes; Leadership du changement; Mesure de l\'adoption; Études de cas de transformations réussies',
   '3 jours','hybride',200000,130000],

  ['Gestion de Projet selon le Cadre Logique AFD, UE & USAID','gestion-projet-cadre-logique-afd-ue-usaid','ONG & Développement','Formation Certifiante',
   'Concevoir et gérer des projets de développement selon le Cadre Logique des principaux bailleurs (AFD, Union Européenne, USAID, PNUD).',
   'Élaborer un cadre logique solide, définir les indicateurs SMART, produire les rapports de suivi et évaluation exigés par les bailleurs.',
   'Projets de développement et environnement bailleur; Cadre logique (LFA); Théorie du changement; Matrice du cadre logique (MCL); Indicateurs SMART et sources de vérification; Suivi-Évaluation (standards AFD/UE/USAID); Plan de travail annuel (PTA) et rapports narratifs; Budget et gestion financière; Revues à mi-parcours et évaluations finales',
   '4 jours','hybride',250000,150000],

  ['Reporting Financier Bailleur — USAID, Banque Mondiale, Union Européenne','reporting-financier-bailleur-usaid-banque-mondiale-ue','ONG & Développement','Formation Certifiante',
   'Produire les rapports financiers conformes aux exigences des principaux bailleurs de fonds internationaux : USAID, Banque Mondiale, UE, PNUD.',
   'Maîtriser les formats de reporting financier des bailleurs, gérer les fonds de projet en conformité et préparer les audits.',
   'Règles d\'éligibilité des coûts par bailleur; Comptabilité de projet et codification des dépenses; Format SF-425 (USAID); Rapport financier UE; Reporting Banque Mondiale (IFR); Gestion des avances et justifications; Gestion des devises; Préparation à l\'audit externe; Exercices pratiques sur tableurs de reporting',
   '3 jours','hybride',200000,130000],

  ['Suivi-Évaluation Avancé — ODK, KoBoCollect & Analyse des Données','suivi-evaluation-avance-odk-kobocollect-analyse-donnees','ONG & Développement','Formation Certifiante',
   'Concevoir un système MEAL robuste, collecter des données terrain avec ODK/KoBoCollect et analyser les indicateurs de performance des projets de développement.',
   'Concevoir des formulaires de collecte sur KoBoCollect, analyser les données avec Excel ou R, produire des tableaux de bord et rédiger des rapports d\'évaluation.',
   'Conception d\'un système MEAL; Indicateurs et plans de collecte; Formulaires numériques (KoBoToolbox, ODK); Collecte terrain mobile; Gestion et nettoyage des données; Analyse quantitative et qualitative; Visualisation (Excel, Power BI, QGIS); Rédaction du rapport d\'évaluation; Restitution aux parties prenantes',
   '3 jours','hybride',200000,130000],

  ['Business Plan orienté Financement Bancaire Ivoirien','business-plan-financement-bancaire-ivoirien','Entrepreneuriat','Formation Certifiante',
   'Rédiger un business plan solide adapté aux exigences des banques ivoiriennes et des structures de financement pour obtenir un financement.',
   'Structurer un business plan convaincant, réaliser les projections financières, identifier les bons financeurs et préparer sa présentation devant un comité de crédit.',
   'Structure d\'un business plan; Étude de marché et analyse concurrentielle; Présentation de l\'offre; Projections financières sur 3-5 ans; Besoin en financement et point mort; Critères d\'évaluation des banques ivoiriennes; Pitch et présentation; Simulation de comité de crédit',
   '3 jours','hybride',180000,110000],

  ['Accès aux Financements FDFP & BPI Côte d\'Ivoire','acces-financements-fdfp-bpi-cote-ivoire','Entrepreneuriat','Formation Certifiante',
   'Accéder aux dispositifs de financement et d\'aide aux entreprises ivoiriennes : FDFP, BPI Côte d\'Ivoire, FGPME, CEPICI et fonds de garantie.',
   'Identifier les financements adaptés à son entreprise, constituer les dossiers complets et maximiser ses chances d\'obtenir les aides et subventions disponibles.',
   'Cartographie des financeurs PME en CI; FDFP et financement de la formation; BPI Côte d\'Ivoire et ses dispositifs; FGPME et fonds de garantie; Aides CEPICI à la création; Financement des jeunes entrepreneurs (PEJEDEC, C2D); Financement participatif africain; Montage de dossier; Simulation de présentation à un fonds',
   '2 jours','hybride',130000,80000],

  ['Commerce Électronique en Afrique — Vendre en Ligne & Marketplace','commerce-electronique-afrique-vendre-en-ligne-marketplace','Entrepreneuriat','Formation Certifiante',
   'Lancer et développer une activité de e-commerce en Afrique : création de boutique en ligne, référencement, vente sur marketplace et Mobile Money.',
   'Créer une boutique en ligne performante, référencer ses produits sur les marketplace africaines, gérer les paiements Mobile Money et développer ses ventes.',
   'E-commerce en Afrique : tendances et opportunités; Boutique WooCommerce ou Shopify; Catalogue, photos et fiches produits; Vente sur Jumia, Amazon Africa; Mobile Money et paiement en ligne; Livraison et logistique; SEO e-commerce et réseaux sociaux; Gestion des avis clients; Fiscalité et aspects légaux du e-commerce en CI',
   '3 jours','hybride',150000,95000],

  ['Création d\'Entreprise en Côte d\'Ivoire — CEPICI & Procédures Légales','creation-entreprise-cote-ivoire-cepici-procedures-legales','Entrepreneuriat','Formation Certifiante',
   'Créer son entreprise en Côte d\'Ivoire : choix de la forme juridique, procédures CEPICI, statuts, immatriculation RCCM et démarches administratives.',
   'Choisir le statut juridique adapté, réaliser les formalités de création au CEPICI, immatriculer la société et effectuer les démarches post-création.',
   'Formes juridiques en CI (SARL, SA, GIE, EI) et critères de choix; Procédures CEPICI (guichet unique); Rédaction des statuts; Immatriculation RCCM et SIUCEN; Affiliation CNPS et DGI; Ouverture du compte bancaire; Licences et autorisations sectorielles; Régime fiscal de démarrage; Plan d\'action des 100 premiers jours',
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
        if ($stmt->rowCount() > 0) $ok++;
        else $skip++;
    } catch (PDOException $e) {
        $errors[] = $f[0] . ' → ' . $e->getMessage();
    }
}

echo "<pre>\n";
echo "✅ Nouvelles formations insérées : $ok / " . count($formations) . "\n";
echo "⏭  Déjà existantes (ignorées)    : $skip\n";
if ($errors) {
    echo "\n⚠️ Erreurs :\n";
    foreach ($errors as $e) echo "  - $e\n";
}
$total = $pdo->query("SELECT COUNT(*) FROM formations")->fetchColumn();
echo "\n📊 Total formations en base : $total\n";
echo "</pre>";
