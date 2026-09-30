<?php
if (empty($_GET['run'])) die('?run=1');
require_once __DIR__ . '/core/config.php';
require_once __DIR__ . '/core/database.php';
$pdo = Database::connect();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

/**
 * FORMATIONS 100% E-LEARNING IBIG EDUFORM
 * mode = 'en_ligne' | tarif_presentiel = 0 | statut = 'inactive'
 *
 * Grille tarifaire e-learning :
 *   7H          →  45 000 FCFA
 *   2 semaines  →  55 000 FCFA
 *   3 semaines  →  65 000 FCFA
 *   4 semaines  →  80 000 FCFA
 *   6 semaines  →  95 000 FCFA
 *   8 semaines  → 120 000 FCFA
 */

// [titre, slug, domaine, type_certificat, description, objectifs, modules, duree, tarif_en_ligne]
$formations = [

  // ══════════════════════════════════════
  // BUREAUTIQUE & OUTILS (7H — 45 000)
  // ══════════════════════════════════════
  ['Excel de Base — Maîtriser les Fondamentaux',
   'elearning-excel-de-base-maitrise-fondamentaux',
   'Bureautique & Data','E-Learning Certifié',
   'Formation 100% en ligne pour maîtriser Excel de A à Z : interface, formules essentielles, tableaux, graphiques et mise en forme professionnelle.',
   'Créer et mettre en forme des tableaux, utiliser les formules de base (SOMME, SI, RECHERCHEV), créer des graphiques et gérer des données dans Excel.',
   'Interface Excel et navigation; Saisie et mise en forme des données; Formules essentielles (SOMME, MOYENNE, SI, NB.SI); Graphiques et visuels; Tableaux croisés dynamiques initiation; Impression et partage; Exercices pratiques corrigés',
   '7H', 45000],

  ['Excel Intermédiaire — Formules Avancées & Tableaux Croisés',
   'elearning-excel-intermediaire-formules-avancees-tcd',
   'Bureautique & Data','E-Learning Certifié',
   'Passer au niveau supérieur sur Excel : formules avancées, RECHERCHEV/X, tableaux croisés dynamiques, mise en forme conditionnelle et automatisation de base.',
   'Utiliser les formules avancées d\'Excel, exploiter les tableaux croisés dynamiques, automatiser les tâches répétitives et créer des tableaux de bord simples.',
   'Formules avancées (RECHERCHEV, INDEX/EQUIV, SIERREUR); Tableaux croisés dynamiques avancés; Mise en forme conditionnelle; Validation des données; Fonctions de texte et de date; Graphiques combinés; Introduction aux macros; Cas pratiques comptabilité et reporting',
   '7H', 45000],

  ['Word Professionnel — Rédaction & Mise en Page',
   'elearning-word-professionnel-redaction-mise-en-page',
   'Bureautique & Data','E-Learning Certifié',
   'Créer des documents professionnels avec Microsoft Word : rapports, lettres, contrats et présentations avec une mise en page soignée et professionnelle.',
   'Rédiger et mettre en forme des documents professionnels, insérer des tableaux et images, créer des modèles et utiliser le suivi des modifications.',
   'Mise en forme de caractères et de paragraphes; Styles et thèmes; En-têtes, pieds de page et numérotation; Tableaux et listes; Images et objets; Table des matières automatique; Modèles professionnels; Publipostage (mailing); Suivi des modifications et commentaires; Export PDF',
   '7H', 45000],

  ['PowerPoint — Présentations Percutantes & Professionnelles',
   'elearning-powerpoint-presentations-percutantes-professionnelles',
   'Bureautique & Data','E-Learning Certifié',
   'Créer des présentations PowerPoint visuellement impactantes : design, animations, graphiques, diapositives maîtres et présentation orale convaincante.',
   'Concevoir des présentations structurées et visuellement attractives, utiliser les animations et transitions, créer des diapositives maîtres et exporter en PDF/vidéo.',
   'Structure d\'une présentation efficace; Thèmes, couleurs et polices; Diapositives maîtres et modèles; Insertion d\'images, icônes et graphiques; Animations et transitions; SmartArt et organigrammes; Narration et présentateur; Export PDF et vidéo; Bonnes pratiques du storytelling visuel',
   '7H', 45000],

  ['Canva Pro — Créer ses Visuels Marketing Professionnels',
   'elearning-canva-pro-visuels-marketing-professionnels',
   'Bureautique & Data','E-Learning Certifié',
   'Maîtriser Canva pour créer tous vos visuels professionnels : publications réseaux sociaux, flyers, brochures, bannières, présentations et logos.',
   'Créer des visuels professionnels pour les réseaux sociaux, l\'impression et le web en utilisant les fonctionnalités avancées de Canva Pro.',
   'Interface Canva et bibliothèque de templates; Publications réseaux sociaux (Facebook, Instagram, LinkedIn, TikTok); Flyers, affiches et brochures; Présentations Canva; Logos et identité visuelle; Canva Brand Kit; Fonctionnalités IA (Magic Design, Text to Image); Travail en équipe et partage; Export et formats de sortie',
   '7H', 45000],

  ['Google Workspace — Gmail, Drive, Docs, Sheets & Meet',
   'elearning-google-workspace-gmail-drive-docs-sheets-meet',
   'Bureautique & Data','E-Learning Certifié',
   'Maîtriser la suite Google Workspace pour travailler efficacement en équipe : Gmail, Google Drive, Docs, Sheets, Slides, Forms et Google Meet.',
   'Utiliser les outils Google Workspace pour organiser, collaborer et communiquer efficacement en entreprise ou à distance.',
   'Gmail : organisation, filtres, signatures professionnelles; Google Drive : stockage, partage et droits d\'accès; Google Docs : collaboration en temps réel; Google Sheets : tableaux, formules et tableaux croisés; Google Slides : présentations collaboratives; Google Forms : sondages et collectes de données; Google Meet : réunions et webinaires; Google Agenda : organisation du temps',
   '7H', 45000],

  ['Vendre sur WhatsApp Business — Stratégie & Outils',
   'elearning-vendre-whatsapp-business-strategie-outils',
   'Marketing','E-Learning Certifié',
   'Utiliser WhatsApp Business comme outil de vente et de service client en Afrique : catalogue, diffusion, automatisation et stratégie de conversion.',
   'Configurer WhatsApp Business, créer un catalogue de produits, gérer les clients, automatiser les réponses et développer les ventes via WhatsApp.',
   'Configuration de WhatsApp Business; Profil professionnel et catalogue de produits; Messages automatiques (accueil, absence, réponses rapides); Listes de diffusion et groupes; Étiquettes et organisation des contacts; WhatsApp Pay et liens de paiement; Stratégie de contenu et engagement; Statistiques et suivi des performances; WhatsApp API pour PME',
   '7H', 45000],

  ['Booster son CV & Profil LinkedIn pour l\'Emploi',
   'elearning-booster-cv-profil-linkedin-emploi',
   'Développement Personnel','E-Learning Certifié',
   'Optimiser son CV et son profil LinkedIn pour décrocher des opportunités professionnelles en Côte d\'Ivoire et en Afrique de l\'Ouest.',
   'Créer un CV percutant adapté au marché africain, optimiser son profil LinkedIn pour être trouvé par les recruteurs et développer sa visibilité professionnelle.',
   'Les codes du CV en Afrique de l\'Ouest; Structure et contenu d\'un CV percutant; Design et mise en forme (Word, Canva); Lettre de motivation impactante; Profil LinkedIn : photo, titre, résumé; Expériences et compétences LinkedIn; Recommandations et validations; Stratégie de visibilité et networking; Postuler efficacement en ligne',
   '7H', 45000],

  ['Comprendre son Bulletin de Paie — Droits & Calculs',
   'elearning-comprendre-bulletin-paie-droits-calculs',
   'Développement Personnel','E-Learning Certifié',
   'Lire et comprendre son bulletin de paie ivoirien : cotisations CNPS, IRVM, congés payés, heures supplémentaires et vérification des calculs.',
   'Lire et vérifier son bulletin de paie, comprendre les différentes rubriques, connaître ses droits sociaux et identifier les erreurs éventuelles.',
   'Structure d\'un bulletin de paie ivoirien; Salaire brut vs salaire net; Cotisations CNPS (salarié et patronal); IRVM : calcul et tranches; Avantages en nature; Heures supplémentaires et primes; Congés payés et indemnités; ITS et impôts sur salaire; Vérifier ses droits à la retraite CNPS; Cas pratiques de calcul de paie',
   '7H', 45000],

  // ══════════════════════════════════════
  // COMPTABILITÉ & FINANCE (2-3 semaines)
  // ══════════════════════════════════════
  ['Comptabilité pour Non-Comptables — Les Bases Essentielles',
   'elearning-comptabilite-non-comptables-bases-essentielles',
   'Comptabilité','E-Learning Certifié',
   'Comprendre les bases de la comptabilité pour managers et entrepreneurs non spécialistes : lecture des comptes, bilans, résultats et indicateurs financiers clés.',
   'Lire et comprendre les états financiers d\'une entreprise, maîtriser le vocabulaire comptable et prendre de meilleures décisions de gestion.',
   'Pourquoi la comptabilité est utile aux non-comptables; Principes comptables fondamentaux; Le bilan : actif et passif; Le compte de résultat : charges et produits; La trésorerie et les flux de liquidités; Ratios financiers clés (liquidité, solvabilité, rentabilité); Lire un bilan SYSCOHADA; Analyse financière simplifiée; Quiz et exercices corrigés',
   '2 semaines', 55000],

  ['SYSCOHADA Initiation — La Comptabilité OHADA de A à Z',
   'elearning-syscohada-initiation-comptabilite-ohada',
   'Comptabilité','E-Learning Certifié',
   'Initiation complète au SYSCOHADA : plan de comptes OHADA, journaux comptables, grand livre, balance et états financiers de base.',
   'Enregistrer les opérations comptables courantes selon le SYSCOHADA, produire une balance et préparer les états financiers de base.',
   'Système comptable OHADA et ses objectifs; Plan de comptes SYSCOHADA (classes 1 à 8); Règle de la partie double; Journal comptable : saisie des opérations; Grand livre et balance; TVA : comptabilisation et déclaration; Achats, ventes et règlements; Salaires et charges sociales; Amortissements linéaires; Clôture et états financiers annuels',
   '3 semaines', 65000],

  ['Lire & Analyser les États Financiers pour Managers',
   'elearning-lire-analyser-etats-financiers-managers',
   'Finance','E-Learning Certifié',
   'Formation en ligne pour managers et dirigeants non financiers : comprendre et analyser un bilan, un compte de résultat et un tableau de trésorerie.',
   'Interpréter les états financiers d\'une entreprise, calculer les indicateurs de performance clés et utiliser la finance pour piloter son activité.',
   'Logique des états financiers; Bilan : que révèle la structure financière; Compte de résultat : soldes intermédiaires de gestion; Tableau de flux de trésorerie; Les 10 ratios financiers indispensables; Rentabilité économique et financière; Seuil de rentabilité (point mort); Plan de trésorerie prévisionnel; Dialogue avec son expert-comptable; Cas pratiques secteur ivoirien',
   '2 semaines', 55000],

  ['Déclarer sa TVA en Côte d\'Ivoire — Guide Pratique',
   'elearning-declarer-tva-cote-ivoire-guide-pratique',
   'Fiscalité','E-Learning Certifié',
   'Comprendre et déclarer la TVA en Côte d\'Ivoire : bases imposables, taux applicables, déclaration mensuelle, remboursement et contentieux fiscal.',
   'Maîtriser le mécanisme de la TVA ivoirienne, remplir la déclaration mensuelle, gérer les crédits TVA et éviter les pénalités fiscales.',
   'Mécanisme de la TVA (collectée, déductible, nette); Taux TVA applicables en Côte d\'Ivoire; Opérations imposables et exonérées; Fait générateur et exigibilité; Formulaire de déclaration mensuelle DGI; TVA sur importations et exportations; Prorata de déduction; Crédit de TVA et remboursement; Contrôle fiscal TVA et régularisations; Exercices pratiques sur déclarations réelles',
   '2 semaines', 55000],

  ['Budget Personnel & Épargne — Gérer son Argent en Afrique',
   'elearning-budget-personnel-epargne-gerer-argent-afrique',
   'Finance','E-Learning Certifié',
   'Prendre le contrôle de ses finances personnelles : établir un budget, éliminer les dettes, constituer une épargne et investir en Afrique de l\'Ouest.',
   'Créer et suivre un budget personnel, réduire les dépenses superflues, constituer une épargne régulière et commencer à investir.',
   'Bilan de sa situation financière actuelle; Méthode des enveloppes et du 50/30/20; Créer son budget mensuel (tableau Excel fourni); Réduire les dépenses non essentielles; Gérer et rembourser ses dettes; Épargne de précaution : objectifs et méthodes; Produits d\'épargne disponibles en CI (DAT, assurance-vie, tontine); Introduction à l\'investissement (BRVM, immobilier, business); Automatiser ses finances',
   '2 semaines', 55000],

  // ══════════════════════════════════════
  // MARKETING DIGITAL (2-3 semaines)
  // ══════════════════════════════════════
  ['Facebook & Instagram Pro — Créer sa Présence en Ligne',
   'elearning-facebook-instagram-pro-presence-en-ligne',
   'Marketing','E-Learning Certifié',
   'Créer et gérer une présence professionnelle sur Facebook et Instagram : page business, contenus engageants, communauté et premières publicités.',
   'Créer et optimiser une page Facebook/Instagram professionnelle, publier des contenus engageants, développer sa communauté et lancer ses premières publicités.',
   'Créer et optimiser sa page Facebook Business; Algorithme Facebook/Instagram : comment ça marche; Types de contenus (Reels, Stories, Posts, Lives); Calendrier éditorial et planification; Hashtags et référencement social; Booster une publication (premiers pas); Meta Business Suite : gérer les deux plateformes; Statistiques et insights; Répondre aux avis et gérer les commentaires; Cas pratiques PME ivoirienne',
   '2 semaines', 55000],

  ['Facebook & Instagram Ads — Lancer ses Premières Campagnes',
   'elearning-facebook-instagram-ads-premieres-campagnes',
   'Marketing','E-Learning Certifié',
   'Créer et optimiser des campagnes publicitaires sur Meta (Facebook & Instagram) : ciblage, budgets, créatifs, audiences et mesure des résultats.',
   'Lancer des campagnes Meta Ads rentables, maîtriser le Gestionnaire de publicités, créer des audiences ciblées et analyser les performances.',
   'Structure des campagnes Meta Ads (campagne, ensemble, publicité); Objectifs publicitaires (notoriété, trafic, leads, ventes); Ciblage des audiences (intérêts, comportements, lookalike); Création des visuels et copies publicitaires; Pixel Meta et suivi des conversions; Budgets et enchères; A/B Testing des créatifs; Analyse des performances (CPC, CTR, ROAS); Retargeting et audiences personnalisées; Cas pratiques avec budgets réels',
   '3 semaines', 65000],

  ['LinkedIn pour Professionnels Africains — Réseau & Opportunités',
   'elearning-linkedin-professionnels-africains-reseau-opportunites',
   'Marketing','E-Learning Certifié',
   'Exploiter LinkedIn pour développer sa carrière et son business en Afrique : profil optimisé, contenu de valeur, prospection B2B et opportunités d\'emploi.',
   'Optimiser son profil LinkedIn, publier du contenu qui génère de la visibilité, développer son réseau professionnel et prospecter des clients ou opportunités.',
   'Profil LinkedIn optimisé (photo, titre, résumé, expériences); LinkedIn pour les professionnels africains : spécificités; Types de contenus performants (articles, posts, carrousels); Algorithme LinkedIn et visibilité organique; Développer son réseau : stratégie de connexions; Prospection B2B avec LinkedIn Sales Navigator; LinkedIn pour chercher un emploi; LinkedIn Ads : les bases; Mesurer ses performances (SSI, analytics)',
   '2 semaines', 55000],

  ['TikTok Business — Créer du Contenu Viral pour son Entreprise',
   'elearning-tiktok-business-contenu-viral-entreprise',
   'Marketing','E-Learning Certifié',
   'Utiliser TikTok comme outil marketing pour son entreprise en Afrique : créer du contenu viral, développer sa communauté et vendre via TikTok Shop.',
   'Créer une stratégie TikTok pour son entreprise, produire des vidéos courtes engageantes, comprendre l\'algorithme et développer ses ventes sur TikTok.',
   'TikTok en Afrique : chiffres et tendances; Créer un compte TikTok Business; Comprendre l\'algorithme TikTok; Format des vidéos performantes (hook, contenu, CTA); Outils de montage (CapCut, TikTok intégré); Tendances et sons viraux; TikTok Shop et vente en direct; Influenceurs et collaborations; TikTok Ads : présentation; Analyser ses statistiques',
   '2 semaines', 55000],

  ['Email Marketing — Créer des Campagnes qui Convertissent',
   'elearning-email-marketing-campagnes-qui-convertissent',
   'Marketing','E-Learning Certifié',
   'Maîtriser l\'email marketing pour fidéliser ses clients et développer ses ventes : liste de contacts, newsletters, automatisations et mesure des résultats.',
   'Constituer une liste de contacts qualifiés, créer des newsletters engageantes avec Mailchimp ou Brevo, automatiser les séquences et analyser les performances.',
   'Pourquoi l\'email reste le canal le plus rentable; RGPD et lois anti-spam; Outils (Mailchimp, Brevo/Sendinblue, Zoho Mail); Constituer sa liste de contacts légalement; Concevoir un email qui convertit (objet, structure, CTA); Segmentation et personnalisation; Séquences d\'emails automatisées (welcome, nurturing, relance); Campagnes promotionnelles; Taux d\'ouverture, clic, désinscription : analyser et améliorer',
   '2 semaines', 55000],

  // ══════════════════════════════════════
  // IA & NUMÉRIQUE (7H — 2 semaines)
  // ══════════════════════════════════════
  ['ChatGPT pour le Travail Quotidien — Initiation Pratique',
   'elearning-chatgpt-travail-quotidien-initiation-pratique',
   'Digital & IA','E-Learning Certifié',
   'Utiliser ChatGPT au quotidien pour gagner du temps : rédaction, résumés, traduction, idées créatives, recherche et automatisation des tâches répétitives.',
   'Utiliser ChatGPT efficacement dans ses tâches quotidiennes, écrire des prompts précis et exploiter l\'IA pour être plus productif.',
   'Qu\'est-ce que ChatGPT et comment ça marche; Créer son compte et interface; Écrire des prompts efficaces (les 5 règles d\'or); ChatGPT pour la rédaction (emails, rapports, contenus); ChatGPT pour la recherche et les résumés; ChatGPT pour la traduction; ChatGPT pour les idées et le brainstorming; ChatGPT pour les calculs et l\'analyse de données; Limites et vérification des informations; IA dans son secteur d\'activité',
   '7H', 45000],

  ['IA & Productivité — Automatiser son Travail avec l\'IA',
   'elearning-ia-productivite-automatiser-travail-ia',
   'Digital & IA','E-Learning Certifié',
   'Exploiter les outils d\'IA pour multiplier sa productivité : ChatGPT, Gemini, Perplexity, Canva IA, Notion IA, Make et automatisations no-code.',
   'Identifier les tâches automatisables avec l\'IA, maîtriser les meilleurs outils IA par catégorie et créer des workflows IA sans coder.',
   'Panorama des meilleurs outils IA en 2026; ChatGPT, Gemini et Claude : différences et usages; IA pour le contenu (texte, image, vidéo); Perplexity et recherche augmentée; Notion IA et gestion de projets; Canva IA et création visuelle; Make/Zapier : automatisations no-code; Créer son assistant IA personnalisé (GPTs); Mesurer le gain de productivité; Plan d\'action personnel pour l\'IA',
   '2 semaines', 55000],

  ['Excel + ChatGPT — Maîtriser la Productivité par les Données',
   'elearning-excel-chatgpt-productivite-donnees',
   'Bureautique & Data','E-Learning Certifié',
   'Combiner Excel et ChatGPT pour analyser les données plus rapidement : formules générées par IA, macros VBA, analyse de données et tableaux de bord.',
   'Utiliser ChatGPT pour écrire des formules Excel complexes, créer des macros VBA et analyser des données avec l\'aide de l\'IA.',
   'ChatGPT pour générer des formules Excel; Formules complexes décodées par IA (INDEX/EQUIV, LAMBDA); Macros VBA générées par ChatGPT; Nettoyer et transformer des données avec IA; Analyse de données guidée par ChatGPT; Tableaux de bord automatisés; Power Query + ChatGPT; Import et analyse de données CSV/Excel avec IA; Cas pratiques comptabilité, RH, commercial',
   '2 semaines', 55000],

  // ══════════════════════════════════════
  // LANGUES (4-6 semaines)
  // ══════════════════════════════════════
  ['Business English — Niveau Débutant (A1 → A2)',
   'elearning-business-english-niveau-debutant-a1-a2',
   'Développement Personnel','E-Learning Certifié',
   'Acquérir les bases de l\'anglais professionnel : vocabulaire courant des affaires, emails simples, présentations de base et communication orale élémentaire.',
   'Communiquer en anglais dans des situations professionnelles simples, rédiger des emails basiques et comprendre les conversations courantes d\'affaires.',
   'Alphabet phonétique et prononciation; Vocabulaire des affaires par thème (entreprise, finance, RH, IT); Se présenter et présenter son entreprise; Emails professionnels simples (demandes, confirmations); Chiffres, dates et mesures; Vocabulaire des réunions; Conversations téléphoniques basiques; Expressions courantes des affaires; 20 minutes de pratique orale par jour; Quiz et exercices corrigés quotidiens',
   '4 semaines', 80000],

  ['Business English — Niveau Intermédiaire (B1 → B2)',
   'elearning-business-english-niveau-intermediaire-b1-b2',
   'Développement Personnel','E-Learning Certifié',
   'Progresser en anglais professionnel : rédiger des rapports, animer des réunions, négocier et faire des présentations en anglais avec confiance.',
   'Rédiger des rapports et propositions en anglais, animer des réunions et négocier dans les situations professionnelles courantes.',
   'Vocabulaire avancé par secteur (finance, droit, logistique, IT); Emails formels : réclamations, propositions, relances; Rapports professionnels en anglais (structure et style); Animer et participer aux réunions en anglais; Présentations orales en anglais (structure et delivery); Négociation : expressions et stratégies; Anglais pour les contrats et documents juridiques; Conference calls et visioconférences; IELTS/TOEIC : introduction et conseils; Pratique orale avec feedback IA',
   '4 semaines', 80000],

  ['Français Professionnel Avancé — Écrire & Communiquer avec Impact',
   'elearning-francais-professionnel-avance-ecrire-communiquer-impact',
   'Communication Professionnelle','E-Learning Certifié',
   'Perfectionner son français professionnel écrit et oral : rédaction administrative, rapports, notes de synthèse, discours et communication institutionnelle sans fautes.',
   'Rédiger des documents professionnels sans fautes, maîtriser le style administratif ivoirien et UEMOA, et communiquer avec aisance dans les contextes formels.',
   'Grammaire et orthographe professionnelle; Style administratif ivoirien et UEMOA; La note de service et la circulaire; Le rapport d\'activité et le compte-rendu; La note de synthèse et le mémo; La correspondance officielle; Discours et allocutions; Éviter les anglicismes et barbarismes; Ponctuation et structure des phrases; Exercices de rédaction corrigés',
   '3 semaines', 65000],

  // ══════════════════════════════════════
  // ENTREPRENEURIAT (2-3 semaines)
  // ══════════════════════════════════════
  ['Lancer son Business en Côte d\'Ivoire — Guide Complet',
   'elearning-lancer-son-business-cote-ivoire-guide-complet',
   'Entrepreneuriat','E-Learning Certifié',
   'Guide pratique pas à pas pour créer son entreprise en Côte d\'Ivoire : idée, étude de marché, statut juridique, financement, CEPICI et premiers clients.',
   'Valider son idée de business, créer légalement son entreprise en CI, trouver les premiers clients et éviter les erreurs des débutants.',
   'Trouver et valider son idée de business; Étude de marché simplifiée; Business Model Canvas; Choisir son statut juridique (EI, SARL, SA); Formalités CEPICI et immatriculation RCCM; Obligations fiscales et comptables de départ; Trouver ses premiers clients; Stratégie de communication de lancement; Financement initial (fonds propres, famille, IMF); Plan d\'action des 100 premiers jours',
   '3 semaines', 65000],

  ['Business Model Canvas — De l\'Idée au Projet Viable',
   'elearning-business-model-canvas-idee-projet-viable',
   'Entrepreneuriat','E-Learning Certifié',
   'Utiliser le Business Model Canvas pour structurer son projet entrepreneurial : propositions de valeur, segments clients, canaux, revenus et ressources clés.',
   'Remplir et analyser un Business Model Canvas, identifier les hypothèses clés de son business et valider son modèle économique avant d\'investir.',
   'Introduction au Business Model Canvas (Osterwalder); Les 9 blocs du BMC; Segment de clientèle : qui sont vos clients; Proposition de valeur : pourquoi vous choisissent-ils; Canaux de distribution et de communication; Relations clients; Flux de revenus et modèles économiques; Ressources et activités clés; Partenaires clés; Structure de coûts; Valider son BMC sur le terrain (lean startup)',
   '2 semaines', 55000],

  ['Instagram Shopping & Vente en Ligne sans Site Web',
   'elearning-instagram-shopping-vente-en-ligne-sans-site-web',
   'Marketing','E-Learning Certifié',
   'Vendre ses produits directement sur Instagram et WhatsApp sans avoir de site web : boutique Instagram, stories, lives shopping et paiements mobiles.',
   'Créer une boutique Instagram, vendre en live, encaisser par Mobile Money et développer ses ventes en ligne sans investissement technique.',
   'Créer sa boutique Instagram Shopping; Configurer le catalogue produits; Taguer ses produits dans les publications; Instagram Live Shopping; Stories de vente performantes; Lien en bio et outils (Linktree, Beacons); Paiement par Mobile Money (Orange Money, MTN, Wave); Gestion des commandes et livraison; Service client par messages privés; Passer à l\'échelle : vers un vrai e-shop',
   '2 semaines', 55000],

  // ══════════════════════════════════════
  // DROIT & RH PRATIQUE (7H — 2 semaines)
  // ══════════════════════════════════════
  ['Droit du Travail — Connaître ses Droits de Salarié en CI',
   'elearning-droit-travail-connaitre-droits-salarie-ci',
   'Droit & Juridique','E-Learning Certifié',
   'Connaître ses droits en tant que salarié en Côte d\'Ivoire : contrat de travail, congés, licenciement, indemnités, CNPS et recours en cas de litige.',
   'Identifier ses droits fondamentaux en tant que salarié, comprendre son contrat de travail et savoir comment réagir en cas de litige avec l\'employeur.',
   'Types de contrats de travail (CDI, CDD) et leurs différences; Période d\'essai : droits et limites; Rémunération : SMIG, primes, avantages; Congés payés, maladie et maternité; Heures supplémentaires et repos compensateur; Procédure disciplinaire : droits du salarié; Licenciement : procédure légale et indemnités; CNPS : droits à la retraite et à la prévoyance; Saisir l\'Inspection du Travail; Conciliation et prud\'hommes',
   '2 semaines', 55000],

  ['Créer son Entreprise en CI — Pas à Pas en 7 Heures',
   'elearning-creer-entreprise-ci-pas-a-pas',
   'Entrepreneuriat','E-Learning Certifié',
   'Créer son entreprise en Côte d\'Ivoire en une journée de formation : CEPICI, choix du statut, statuts, compte bancaire et premières obligations.',
   'Réaliser toutes les formalités de création d\'entreprise au CEPICI, choisir le bon statut juridique et remplir ses premières obligations légales.',
   'Quelle forme juridique choisir (EI, SARL, SA, GIE) et pourquoi; Avantages et inconvénients de chaque statut; Le CEPICI : guichet unique, délais et coûts; Documents nécessaires à la création; Rédiger des statuts simples; Immatriculation RCCM et numéro SIUCEN; Affiliation CNPS et DGI; Ouvrir son compte bancaire professionnel; Obligations du premier mois (comptabilité, déclarations); Ressources et aides disponibles',
   '7H', 45000],

  ['Gestion du Temps & Organisation Personnelle au Travail',
   'elearning-gestion-temps-organisation-personnelle-travail',
   'Développement Personnel','E-Learning Certifié',
   'Maîtriser son temps et s\'organiser efficacement au travail : méthodes GTD, Pomodoro, Eisenhower, outils digitaux et équilibre vie pro/personnelle.',
   'Identifier ses voleurs de temps, prioriser ses tâches, planifier sa semaine idéale et utiliser les bons outils pour être plus productif.',
   'Diagnostic de sa gestion du temps actuelle; Les 4 quadrants d\'Eisenhower; Méthode GTD (Getting Things Done); Technique Pomodoro et deep work; Planifier sa semaine idéale; Dire non avec assertivité; Les voleurs de temps numériques (emails, réseaux sociaux); Outils de productivité (Notion, Trello, Todoist, Google Agenda); Déléguer efficacement; Équilibre vie professionnelle et personnelle',
   '7H', 45000],

  ['Préparer ses Entretiens d\'Embauche — Techniques & Pratique',
   'elearning-preparer-entretiens-embauche-techniques-pratique',
   'Développement Personnel','E-Learning Certifié',
   'Réussir ses entretiens d\'embauche en Côte d\'Ivoire et en Afrique : préparation, questions pièges, mise en valeur de son parcours et négociation salariale.',
   'Se préparer efficacement à un entretien d\'embauche, répondre aux questions difficiles, valoriser son parcours et négocier son salaire avec confiance.',
   'Types d\'entretiens (RH, technique, panel, visio); Rechercher et analyser l\'entreprise avant l\'entretien; Se présenter en 2 minutes (elevator pitch); Questions fréquentes et réponses STAR; Questions pièges et comment y répondre; Parler de ses points faibles positivement; Négocier son salaire : techniques et fourchettes CI; Questions à poser au recruteur; Entretien en anglais : les bases; Suivi post-entretien (email de remerciement)',
   '7H', 45000],

  // ══════════════════════════════════════
  // CERTIFICATIONS E-LEARNING (4-8 semaines)
  // ══════════════════════════════════════
  ['Certification Community Manager — Gérer les Réseaux Sociaux en Pro',
   'elearning-certification-community-manager-reseaux-sociaux',
   'Marketing','E-Learning Certifié',
   'Certification complète Community Manager en ligne : stratégie social media, création de contenu, publicité, gestion de crise et reporting client.',
   'Maîtriser toutes les compétences du Community Manager professionnel, gérer les réseaux sociaux d\'une entreprise et produire des résultats mesurables.',
   'Rôle et missions du Community Manager; Stratégie social media (audit, objectifs, cibles); Facebook, Instagram, LinkedIn, TikTok, YouTube : spécificités; Création de contenu (texte, visuel, vidéo); Calendrier éditorial et planification Hootsuite/Buffer; Publicité social media (Meta Ads, LinkedIn Ads); Gestion de communauté et modération; Gestion de crise et bad buzz; Reporting et présentation des résultats; Construire son portfolio et trouver des clients',
   '6 semaines', 95000],

  ['Certification Marketing Digital & E-Commerce',
   'elearning-certification-marketing-digital-ecommerce',
   'Marketing','E-Learning Certifié',
   'Certification complète en marketing digital et e-commerce : SEO, Google Ads, Meta Ads, email marketing, e-commerce et analytics.',
   'Maîtriser les leviers du marketing digital, lancer des campagnes rentables, créer une boutique en ligne et analyser les performances.',
   'Stratégie de marketing digital; SEO et référencement naturel; Google Ads (Search et Display); Meta Ads (Facebook et Instagram); Email marketing et automation; Marketing de contenu et inbound; E-commerce : WooCommerce et Shopify; Analyse de données (GA4, Meta Insights); Stratégie d\'influence et UGC; Construire une agence digitale ou carrière en marketing; Projet final : campagne complète',
   '8 semaines', 120000],

  ['Certification Excel Expert — Du Débutant au Niveau Avancé',
   'elearning-certification-excel-expert-debutant-avance',
   'Bureautique & Data','E-Learning Certifié',
   'Certification Excel complète : des bases aux fonctions avancées, Power Query, Power Pivot, tableaux de bord dynamiques et introduction aux macros VBA.',
   'Maîtriser Excel du niveau débutant au niveau expert, automatiser les tâches, créer des tableaux de bord professionnels et décrocher la certification Microsoft.',
   'Fondamentaux Excel (révision intensive); Formules avancées (XLOOKUP, LAMBDA, FILTER, SORT); Tableaux croisés dynamiques avancés; Power Query : import et transformation de données; Power Pivot et modèle de données; Tableaux de bord dynamiques; Visualisation avancée des données; Introduction aux macros VBA; Collaboration et partage Excel Online; Préparation certification Microsoft Office Specialist (MOS); 5 projets pratiques notés',
   '6 semaines', 95000],

  ['Certification Data Analyst Débutant — Python, SQL & Visualisation',
   'elearning-certification-data-analyst-debutant-python-sql-visualisation',
   'Digital & IA','E-Learning Certifié',
   'Devenir Data Analyst sans expérience préalable : Python, SQL, Excel, visualisation de données et premier portfolio de projets pour décrocher un emploi.',
   'Acquérir les compétences fondamentales du Data Analyst, manipuler des données avec Python et SQL et présenter des insights via des visualisations.',
   'Qu\'est-ce qu\'un Data Analyst : rôle et marché; Statistiques descriptives pour l\'analyse; Excel avancé pour les données; SQL : requêtes de base à avancées; Python : Pandas et NumPy pour les données; Visualisation avec Matplotlib, Seaborn et Power BI; Nettoyage et préparation des données; Analyse exploratoire et storytelling; Construire son portfolio GitHub; Préparer son entretien Data Analyst; Projet final noté',
   '8 semaines', 120000],

  ['Certification Comptabilité SYSCOHADA — Niveau Opérationnel',
   'elearning-certification-comptabilite-syscohada-niveau-operationnel',
   'Comptabilité','E-Learning Certifié',
   'Certification complète en comptabilité SYSCOHADA : saisie comptable, déclarations fiscales, états financiers et préparation au poste de comptable junior.',
   'Tenir la comptabilité d\'une PME selon le SYSCOHADA, produire les déclarations fiscales courantes et préparer les états financiers annuels.',
   'Plan de comptes SYSCOHADA révisé (révision); Saisie des opérations courantes; Gestion des immobilisations et amortissements; Stocks et inventaire; Salaires et charges sociales; TVA : déclaration mensuelle complète; IS et acomptes; États de rapprochement bancaire; Clôture annuelle et états financiers; Liasse fiscale simplifiée; Utilisation d\'un logiciel (Sage Compta); Projet final : comptabilité complète d\'une PME sur 3 mois',
   '8 semaines', 120000],
];

$stmt = $pdo->prepare("
  INSERT IGNORE INTO formations
    (titre, slug, domaine, type_certificat, description, objectifs, modules,
     duree, mode, tarif_presentiel, tarif_en_ligne, tarif_hybride, frais_inscription,
     statut, created_at, updated_at)
  VALUES
    (:titre, :slug, :domaine, :type_certificat, :description, :objectifs, :modules,
     :duree, 'en_ligne', 0, :tarif_en_ligne, 0, 0,
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
            ':tarif_en_ligne'  => $f[8],
        ]);
        if ($stmt->rowCount() > 0) $ok++; else $skip++;
    } catch (PDOException $e) {
        $errors[] = $f[0] . ' → ' . $e->getMessage();
    }
}

echo "<pre>\n";
echo "✅ Formations e-learning insérées : $ok / " . count($formations) . "\n";
echo "⏭  Déjà existantes (ignorées)    : $skip\n";
if ($errors) { echo "\n⚠️ Erreurs :\n"; foreach ($errors as $e) echo "  - $e\n"; }
$total = $pdo->query("SELECT COUNT(*) FROM formations")->fetchColumn();
$elearning = $pdo->query("SELECT COUNT(*) FROM formations WHERE mode='en_ligne'")->fetchColumn();
echo "📊 Total formations en base  : $total\n";
echo "🖥️  Dont e-learning (en_ligne) : $elearning\n";
echo "</pre>";
