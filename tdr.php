<?php
declare(strict_types=1);
/* =========================================================
   IBIG EDUFORM — TDR (Termes de Référence) PREMIUM
   Document professionnel généré depuis formations + formation_landings.

   ACCÈS CONTRÔLÉ : ce fichier n'est plus accessible directement.
   Le TDR est réservé aux prospects inscrits (via /formation/{slug}).
*/
/*
   - Contenu dense, ton expert
   - Téléchargement PDF DIRECT (html2pdf), sans dialogue d'impression
   URL : /tdr.php?formation=ID  (ou ?slug=...)
========================================================= */
require_once __DIR__ . '/core/bootstrap.php';
$pdo = Database::connect();

$id   = (int)($_GET['formation'] ?? $_GET['id'] ?? 0);
$slug = trim((string)($_GET['slug'] ?? ''));
$f = null;
if ($slug !== '') { $st = $pdo->prepare("SELECT * FROM formations WHERE slug=? LIMIT 1"); $st->execute([$slug]); $f = $st->fetch(PDO::FETCH_ASSOC) ?: null; }
elseif ($id > 0)  { $st = $pdo->prepare("SELECT * FROM formations WHERE id=? LIMIT 1");   $st->execute([$id]);   $f = $st->fetch(PDO::FETCH_ASSOC) ?: null; }
/* ── Helpers génération contenu TDR pour formations API ── */

/* Extrait les thèmes listés dans une description ibigpartners (après le ":") */
function _extract_topics(string $desc): array {
    /* Nettoyer les phrases de fin inutiles */
    $desc = preg_replace('/\b(Pour |Disponible en |À partir de |Code\s*:)[^.]+\./ui', '', $desc);
    /* Trouver le contenu après ":" */
    if (!preg_match('/:\s*(.+)$/su', $desc, $m)) return [];
    /* Couper à la première phrase "Pour X" restante */
    $raw = preg_split('/\.\s+[A-ZÀÂÉÈÊÙÛÎÏ]/u', trim($m[1]))[0];
    /* Supprimer les contenus entre parenthèses AVANT de splitter par virgule
       pour éviter que "(CDI, CDD, avenants)" ne crée de faux thèmes */
    $raw = preg_replace('/\s*\([^)]*\)/', '', $raw);
    /* Splitter par virgule ou point-virgule */
    $topics = array_map('trim', preg_split('/[,;]\s*/u', $raw));
    /* Garder seulement les thèmes de longueur raisonnable */
    return array_values(array_filter($topics, fn($t) =>
        mb_strlen($t, 'UTF-8') >= 8 && mb_strlen($t, 'UTF-8') <= 90
    ));
}

function _tdr_programme_api(string $name, int $heures = 25, string $cat = '', string $desc = ''): string {
    /* Nombre cible de modules selon la durée */
    $nb_modules = 5;
    if ($heures >= 25) $nb_modules = 6;
    if ($heures >= 30) $nb_modules = 7;
    if ($heures >= 35) $nb_modules = 8;
    if ($heures >= 45) $nb_modules = 9;
    if ($heures >= 65) $nb_modules = 10;

    /* Extraire les thèmes depuis la description — source de vérité */
    $topics = _extract_topics($desc);
    /* Fallback : dériver depuis le nom si description insuffisante */
    if (count($topics) < 2) {
        $n = preg_replace('/\s*\([^)]*\)/', '', $name);
        $topics = preg_split('/\s*[&,]\s*|\s+[eé]t\s+/ui', $n);
        $topics = array_values(array_filter(array_map('trim', $topics), fn($t) => mb_strlen(trim($t), 'UTF-8') > 4));
    }

    /* 3 jeux de bullets qui tournent — ancrés sur le contenu du module */
    $bullets = [
        ['• Les documents et formulaires associés, comment les lire et les produire',
         '• Étapes de traitement, circuits de validation et acteurs impliqués',
         '• Cas pratiques sur dossiers simulés et corrections commentées'],
        ['• Réglementation en vigueur, obligations et délais à respecter',
         '• Erreurs fréquentes constatées en entreprise et points de vigilance',
         '• Exercices pratiques avec mise en situation professionnelle'],
        ['• Outils, logiciels et tableaux de suivi utilisés dans les entreprises',
         '• Organisation, archivage et traçabilité des dossiers',
         '• Retours d\'expérience d\'entreprises ivoiriennes et de l\'espace OHADA'],
        ['• Principes clés, définitions et ce que le praticien doit savoir en priorité',
         '• Mise en pratique guidée sur cas réels issus du terrain africain',
         '• Points de contrôle, indicateurs de qualité et critères de conformité'],
        ['• Procédures internes types et comment les adapter à son entreprise',
         '• Communication inter-services, reporting et présentation des résultats',
         '• Simulation complète avec correction et plan d\'amélioration individuel'],
    ];

    /* Modules de remplissage par domaine (humains, non génériques) */
    $fillers_par_cat = [
        'GRH' => [
            ['Législation sociale et droit du travail appliqué',
             '• Code du travail ivoirien : contrats, durée légale et congés payés',
             '• Déclarations sociales obligatoires : CNPS, CMU, DISA — qui fait quoi',
             '• Procédure disciplinaire, licenciement et rupture conventionnelle'],
            ['Paie et gestion des rémunérations',
             '• Structure d\'un bulletin de paie : éléments fixes, variables et retenues',
             '• Calcul des charges sociales patronales et salariales',
             '• Traitement des absences, heures supplémentaires et primes'],
            ['Recrutement et intégration',
             '• Rédiger une fiche de poste et un appel à candidatures efficace',
             '• Conduire un entretien de recrutement structuré',
             '• Parcours d\'intégration, période d\'essai et suivi du nouveau collaborateur'],
            ['GPEC et développement des compétences',
             '• Cartographie des compétences et détection des besoins en formation',
             '• Plan de formation annuel : construction, budget et suivi',
             '• Entretien annuel d\'évaluation et entretien professionnel'],
        ],
        'Comptabilité & Finance' => [
            ['Comptabilité courante et journaux comptables',
             '• Saisie des pièces comptables : achats, ventes, banque, caisse',
             '• Lettrage des comptes, rapprochement bancaire et pointage',
             '• Clôture mensuelle et préparation des états de rapprochement'],
            ['Fiscalité des entreprises — SYSCOHADA',
             '• TVA, BIC, patente et taxes locales : calcul et déclaration',
             '• Délais légaux, pénalités et relations avec l\'administration fiscale',
             '• Optimisation fiscale dans le cadre légal en vigueur'],
            ['Analyse financière et ratios de gestion',
             '• Lire et commenter un bilan et un compte de résultat',
             '• Calcul des ratios de liquidité, solvabilité et rentabilité',
             '• Tableau de flux de trésorerie et prévisions de trésorerie'],
            ['Contrôle de gestion et reporting',
             '• Budget prévisionnel : construction et méthodes de chiffrage',
             '• Tableaux de bord de gestion et indicateurs de performance',
             '• Analyse des écarts et présentation des résultats à la direction'],
        ],
        'Informatique & Tech' => [
            ['Architecture des systèmes et infrastructure',
             '• Composants matériels, réseaux et systèmes d\'exploitation',
             '• Configuration, maintenance et résolution des pannes courantes',
             '• Sécurité des accès, sauvegardes et plan de reprise'],
            ['Développement et intégration',
             '• Structure d\'une application : front-end, back-end et base de données',
             '• Versioning avec Git, revue de code et gestion des branches',
             '• Tests, débogage et déploiement en environnement de production'],
            ['Cybersécurité et protection des données',
             '• Cartographie des risques et vecteurs d\'attaque courants',
             '• Politique de mots de passe, authentification forte et chiffrement',
             '• Réponse aux incidents et procédures de notification'],
        ],
        'Gestion Commerciale & Marketing' => [
            ['Prospection et développement commercial',
             '• Identifier sa cible, construire un fichier client et qualifier les leads',
             '• Techniques de prise de contact : appel, email, réseaux sociaux',
             '• Suivi des relances et gestion du pipeline commercial'],
            ['Négociation et closing',
             '• Préparer et structurer un entretien de vente',
             '• Traiter les objections prix, concurrence et délai',
             '• Rédiger une proposition commerciale qui convainc'],
            ['Marketing digital et acquisition',
             '• Réseaux sociaux professionnels : stratégie de contenu et publication',
             '• Publicité en ligne : Facebook Ads, Google Ads — paramétrage de base',
             '• Mesure des résultats : portée, taux d\'engagement, coût par lead'],
        ],
        'Management & Leadership' => [
            ['Animation d\'équipe au quotidien',
             '• Fixer des objectifs clairs et suivre les résultats individuels',
             '• Conduire des réunions efficaces : préparation, animation, compte-rendu',
             '• Déléguer avec méthode et accompagner sans micro-manager'],
            ['Gestion des situations difficiles',
             '• Identifier et désamorcer un conflit avant qu\'il ne s\'envenime',
             '• Recadrer un collaborateur sans briser la relation de confiance',
             '• Gérer les périodes de changement organisationnel et les résistances'],
            ['Stratégie et pilotage de la performance',
             '• Diagnostic interne / externe et choix des priorités',
             '• Construction d\'un plan d\'action avec jalons et responsables',
             '• Suivi des indicateurs clés et ajustements en cours d\'année'],
        ],
        'Immobilier' => [
            ['Marché immobilier africain et réglementation foncière',
             '• Régimes fonciers en Côte d\'Ivoire et dans l\'espace OHADA',
             '• Titres fonciers, ACD, lettres d\'attribution — lecture et vérification',
             '• Procédures notariales et droits d\'enregistrement applicables'],
            ['Evaluation et expertise immobilière',
             '• Méthodes d\'évaluation : comparaison, capitalisation, coût de remplacement',
             '• Visite d\'un bien : grille de contrôle et rédaction du rapport',
             '• Influence de l\'emplacement, de l\'état du bien et du marché local'],
        ],
        'Logistique & Supply Chain' => [
            ['Gestion des stocks et des approvisionnements',
             '• Méthodes de réapprovisionnement : point de commande, recomplètement périodique',
             '• Valorisation des stocks : FIFO, CMUP et inventaires tournants',
             '• Tableau de bord logistique : taux de service, ruptures, rotations'],
            ['Transport et distribution',
             '• Choix du mode de transport : critères coût / délai / fiabilité',
             '• Documents de transport : CMR, connaissement, déclaration en douane',
             '• Gestion des litiges transport et suivi des livraisons'],
        ],
    ];

    /* Fillers génériques (si pas de fillers spécifiques au domaine) */
    $fillers_generiques = [
        ['Organisation du travail et gestion des priorités',
         '• Identifier ce qui est urgent, important et ce qui peut être délégué',
         '• Outils de planification : agenda, to-do list et tableaux de suivi',
         '• Gérer les interruptions, les imprévus et les délais serrés'],
        ['Communication professionnelle écrite et orale',
         '• Rédiger des emails, notes et rapports clairs et bien structurés',
         '• Prendre la parole en réunion et défendre ses idées avec méthode',
         '• Adapter son discours selon l\'interlocuteur — direction, collègues, clients'],
        ['Réglementation applicable et obligations légales',
         '• Textes de référence, autorités de tutelle et jurisprudence récente',
         '• Obligations déclaratives, délais et sanctions en cas de manquement',
         '• Veille réglementaire : comment se tenir informé des évolutions'],
        ['Outils numériques du praticien',
         '• Logiciels métiers de référence utilisés par les entreprises de la place',
         '• Excel avancé appliqué au domaine : tableaux, formules et graphiques utiles',
         '• Automatiser les tâches répétitives pour gagner du temps'],
        ['Lecture et production de documents professionnels',
         '• Identifier les documents clés et savoir les lire rapidement',
         '• Rédiger les documents types du métier sans erreur',
         '• Archivage, classement et traçabilité des dossiers'],
        ['Travail en équipe et coordination inter-services',
         '• Cartographier les interactions avec les autres services',
         '• Gérer les demandes, les urgences et les conflits de priorité',
         '• Compte-rendus, briefings et transmission efficace des informations'],
        ['Suivi, reporting et amélioration continue',
         '• Construire un tableau de bord simple et lisible pour son activité',
         '• Identifier les dysfonctionnements récurrents et leurs causes racines',
         '• Proposer et mettre en place des améliorations concrètes'],
    ];

    $fillers = array_merge($fillers_par_cat[$cat] ?? [], $fillers_generiques);

    $lines = [];
    $bi = 0;

    /* MODULE 1 — introduction spécifique à la formation */
    $lines[] = 'MODULE 1 — ' . $name . ' : périmètre, enjeux et positionnement professionnel';
    $lines[] = '• Ce que recouvre exactement ce domaine et où s\'arrête la responsabilité du praticien';
    $lines[] = '• Acteurs en présence, textes de référence et pratiques du marché en Afrique francophone';
    $lines[] = '• Autodiagnostic et identification des axes prioritaires de progression';
    $idx = 2;

    /* Modules issus des thèmes extraits de la description ou du nom
       Réserve 2 slots pour ateliers + évaluation → max idx = nb_modules - 2 */
    foreach ($topics as $topic) {
        if ($idx > $nb_modules - 2) break;
        /* Capitaliser proprement */
        $t = mb_strtolower(trim($topic), 'UTF-8');
        $t = mb_strtoupper(mb_substr($t, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($t, 1, null, 'UTF-8');
        $bset = $bullets[$bi % count($bullets)]; $bi++;
        $lines[] = 'MODULE ' . $idx . ' — ' . $t;
        foreach ($bset as $b) $lines[] = $b;
        $idx++;
    }

    /* Modules de remplissage domaine-spécifiques si manque de thèmes */
    $fi = 0;
    while ($idx <= $nb_modules - 2 && isset($fillers[$fi])) {
        $fl = $fillers[$fi++];
        $lines[] = 'MODULE ' . $idx . ' — ' . $fl[0];
        for ($k = 1; $k < count($fl); $k++) $lines[] = $fl[$k];
        $idx++;
    }

    /* Avant-dernier : Ateliers */
    $lines[] = 'MODULE ' . $idx . ' — Mise en pratique : cas d\'entreprises et travaux dirigés';
    $lines[] = '• Traitement de dossiers et scénarios tirés d\'entreprises réelles (Côte d\'Ivoire, Afrique de l\'Ouest)';
    $lines[] = '• Travaux individuels et en binôme avec correction commentée par le formateur';
    $lines[] = '• Projet de synthèse : production d\'un livrable professionnel complet';
    $idx++;

    /* Dernier : Évaluation */
    $lines[] = 'MODULE ' . $idx . ' — Évaluation finale et remise du certificat';
    $lines[] = '• Évaluation écrite et pratique couvrant l\'ensemble du programme';
    $lines[] = '• Présentation et défense du projet de synthèse devant le formateur';
    $lines[] = '• Remise du certificat IBIG EDUFORM et construction du plan de développement post-formation';
    return implode("\n", $lines);
}
function _tdr_objectif_api(string $name, string $cat): string {
    $map = [
        'GRH'                             => "Prendre en main la fonction RH avec méthode : gérer les dossiers du personnel, rédiger les documents contractuels, assurer les déclarations sociales obligatoires et appliquer le droit du travail ivoirien au quotidien.",
        'Comptabilité & Finance'          => "Produire une comptabilité fiable et à jour, établir les déclarations fiscales dans les délais, lire les états financiers et alerter la direction sur les indicateurs clés de la santé financière de l'entreprise.",
        'Informatique & Tech'             => "Concevoir, déployer ou maintenir des solutions informatiques adaptées aux besoins réels des entreprises africaines, en appliquant les standards techniques et les bonnes pratiques de sécurité du secteur.",
        'Gestion Commerciale & Marketing' => "Structurer et développer son activité commerciale : prospecter avec méthode, convaincre en entretien, fidéliser ses clients et piloter ses résultats à travers des indicateurs concrets.",
        'Management & Leadership'         => "Diriger une équipe avec clarté, gérer les situations difficiles sans improviser et construire un environnement de travail dans lequel les collaborateurs sont responsabilisés et performants.",
        'Immobilier'                      => "Exercer le métier de l'immobilier en toute sécurité juridique : évaluer les biens, rédiger les documents contractuels, maîtriser la réglementation foncière et accompagner les clients jusqu'à la finalisation des transactions.",
        'Logistique & Supply Chain'       => "Organiser et piloter une chaîne logistique sans rupture : gérer les stocks, coordonner les approvisionnements, maîtriser les documents de transport et optimiser les coûts de la fonction.",
        'Droit & Juridique'               => "Identifier les risques juridiques, rédiger ou relire des contrats courants, conseiller efficacement et orienter vers les bons dispositifs légaux dans le contexte OHADA.",
        'BTP & Construction'              => "Gérer un chantier ou un projet de construction de bout en bout : planification, suivi des coûts, coordination des corps de métier, contrôle qualité et respect des normes en vigueur.",
        'Entrepreneuriat'                 => "Lancer et structurer son projet d'entreprise avec une base solide : valider son modèle économique, lever les financements nécessaires, gérer les premières opérations et construire sa clientèle.",
        'IA & Digitalisation'             => "Intégrer l'intelligence artificielle et les outils numériques dans son activité professionnelle pour gagner en efficacité, automatiser les tâches répétitives et prendre de meilleures décisions.",
        'QHSE'                            => "Déployer et maintenir un système de management QHSE : identifier les risques, animer la démarche qualité, préparer les audits et construire une culture de la sécurité dans l'entreprise.",
        'Agriculture'                     => "Gérer une exploitation agricole ou un projet agro-industriel avec les outils du praticien : planification des cultures, gestion des intrants, maîtrise des coûts et accès aux marchés et aux financements.",
    ];
    return $map[$cat] ?? "Être capable d'exercer la fonction {$name} avec autonomie et rigueur, en appliquant les méthodes reconnues dans le secteur et en produisant des livrables conformes aux standards attendus par les employeurs et donneurs d'ordre.";
}
function _tdr_public_api(string $cat): string {
    $map = [
        'Agriculture'                     => "Agriculteurs, agripreneurs, responsables de coopératives, agents de terrain, porteurs de projets agro-industriels et tout professionnel du secteur agricole.",
        'Informatique & Tech'             => "Développeurs, chefs de projet IT, ingénieurs systèmes, administrateurs réseau, techniciens informatique et toute personne souhaitant évoluer dans le secteur technologique.",
        'IA & Digitalisation'             => "Managers, chefs de projets digitaux, data analysts, responsables IT, entrepreneurs souhaitant intégrer l'IA et le numérique dans leur activité.",
        'Comptabilité & Finance'          => "Comptables, contrôleurs de gestion, responsables financiers, DAF, auditeurs internes et externes, analystes financiers.",
        'GRH'                             => "Responsables et directeurs RH, gestionnaires de personnel, managers d'équipe, responsables de formation.",
        'Management & Leadership'         => "Dirigeants, managers, chefs de projet, cadres supérieurs, entrepreneurs souhaitant renforcer leur leadership et leur performance managériale.",
        'Gestion Commerciale & Marketing' => "Commerciaux, responsables marketing, chefs de produit, directeurs commerciaux, entrepreneurs et toute personne en lien avec la fonction commerciale.",
        'Droit & Juridique'               => "Juristes, avocats, responsables juridiques, dirigeants d'entreprise, cadres administratifs exposés aux enjeux réglementaires.",
        'Logistique & Supply Chain'       => "Responsables logistique, acheteurs, gestionnaires de stock, directeurs supply chain, responsables planification.",
        'BTP & Construction'              => "Ingénieurs, techniciens BTP, conducteurs de travaux, architectes, chefs de chantier, maîtres d'ouvrage.",
        'Immobilier'                      => "Agents immobiliers, promoteurs, gestionnaires de patrimoine, responsables fonciers, notaires, banquiers spécialisés.",
        'Mines, Énergie & Pétrole'        => "Ingénieurs, techniciens, responsables opérationnels et managers du secteur mines, énergie et pétrole.",
        'QHSE'                            => "Responsables QHSE, animateurs sécurité, directeurs développement durable, ingénieurs HSE, responsables RSE.",
        'Santé & Pharmacie'               => "Professionnels de santé, pharmaciens, infirmiers, gestionnaires d'établissements de soins, agents de santé publique.",
        'Tourisme & Hôtellerie'           => "Professionnels du tourisme, hôteliers, gestionnaires d'établissements, guides touristiques, agents de voyages.",
        'Direction & Administration'      => "Assistantes de direction, secrétaires exécutives, responsables administratifs, office managers, directeurs administratifs.",
        'Entrepreneuriat'                 => "Entrepreneurs en démarrage ou en développement, porteurs de projets, dirigeants de PME/startups, intrapreneurs.",
        'Communication Professionnelle'   => "Communicants, chargés de relations publiques, responsables communication, managers, cadres souhaitant améliorer leur communication professionnelle.",
        'Développement Personnel'         => "Tout professionnel, cadre, demandeur d'emploi souhaitant renforcer ses compétences personnelles et développer son potentiel.",
        'Éducation & Formation'           => "Formateurs, enseignants, responsables pédagogiques, ingénieurs de formation, coachs professionnels.",
    ];
    return $map[$cat] ?? "Tout professionnel, cadre, entrepreneur ou demandeur d'emploi souhaitant acquérir des compétences certifiées en {$cat} et développer son employabilité dans l'espace OHADA et au-delà.";
}
function _tdr_resultats_api(string $name, string $cat): string {
    $map = [
        'GRH'                             => "Gérer un dossier du personnel de A à Z sans erreur\nRédiger et suivre les contrats, avenants et procédures disciplinaires\nProduire les déclarations sociales (CNPS, CMU, DISA) dans les délais\nConseiller les managers sur les règles du droit du travail ivoirien\nCertificat professionnel IBIG EDUFORM reconnu dans les 17 pays membres de l'OHADA",
        'Comptabilité & Finance'          => "Saisir, lettrer et justifier les écritures comptables courantes\nProduire le bilan, le compte de résultat et les déclarations fiscales\nLire et commenter les états financiers pour la direction\nConstruire un budget et suivre les écarts mois par mois\nCertificat professionnel IBIG EDUFORM reconnu dans les 17 pays membres de l'OHADA",
        'Informatique & Tech'             => "Concevoir, développer ou administrer une solution informatique opérationnelle\nAppliquer les bonnes pratiques de sécurité et de gestion des accès\nDocumenter, tester et déployer en environnement de production\nRésoudre les incidents courants de manière autonome et structurée\nCertificat professionnel IBIG EDUFORM reconnu dans les 17 pays membres de l'OHADA",
        'Gestion Commerciale & Marketing' => "Prospecter, convaincre et fidéliser une clientèle cible\nRédiger des propositions commerciales et conduire des négociations\nMettre en place et suivre un plan d'action commercial\nMesurer ses résultats et ajuster la stratégie en cours d'année\nCertificat professionnel IBIG EDUFORM reconnu dans les 17 pays membres de l'OHADA",
        'Management & Leadership'         => "Fixer des objectifs clairs et suivre les résultats de son équipe\nConduire des entretiens (évaluation, recadrage, développement)\nGérer les conflits et les situations difficiles sans perdre l'autorité\nPiloter un projet et rendre compte à la direction\nCertificat professionnel IBIG EDUFORM reconnu dans les 17 pays membres de l'OHADA",
        'Immobilier'                       => "Evaluer un bien immobilier et rédiger le rapport d'expertise\nRédiger les mandats, promesses de vente, baux et actes courants\nMaîtriser la réglementation foncière ivoirienne et OHADA\nConduire une transaction de bout en bout en sécurité juridique\nCertificat professionnel IBIG EDUFORM reconnu dans les 17 pays membres de l'OHADA",
        'Logistique & Supply Chain'       => "Gérer les stocks, les réapprovisionnements et les inventaires\nOrganiser et suivre les flux de transport et de distribution\nProduire les documents logistiques et douaniers requis\nConstruire et suivre un tableau de bord logistique\nCertificat professionnel IBIG EDUFORM reconnu dans les 17 pays membres de l'OHADA",
        'Entrepreneuriat'                 => "Formaliser et valider son modèle économique\nRédiger un business plan convaincant pour les financeurs\nStructurer les premières opérations et gérer la trésorerie de démarrage\nConstituer et animer une équipe de démarrage performante\nCertificat professionnel IBIG EDUFORM reconnu dans les 17 pays membres de l'OHADA",
        'IA & Digitalisation'             => "Identifier les usages concrets de l'IA dans son secteur\nDéployer des outils d'automatisation et de traitement des données\nIntégrer le numérique dans les processus métier de l'entreprise\nPiloter une transformation digitale à l'échelle d'une équipe ou d'un service\nCertificat professionnel IBIG EDUFORM reconnu dans les 17 pays membres de l'OHADA",
        'Agriculture'                     => "Planifier une campagne agricole et gérer les intrants avec méthode\nAccéder aux financements agricoles disponibles en Côte d'Ivoire et en Afrique\nCommerialiser sa production et négocier avec les acheteurs\nMesurer la rentabilité d'une exploitation et prendre les décisions correctives\nCertificat professionnel IBIG EDUFORM reconnu dans les 17 pays membres de l'OHADA",
    ];
    return $map[$cat] ?? "Exercer la fonction {$name} de façon autonome et rigoureuse\nProduire les livrables et documents attendus dans le métier\nAppliquer la réglementation en vigueur sans risque d'erreur\nContribuer directement à la performance de son organisation\nCertificat professionnel IBIG EDUFORM reconnu dans les 17 pays membres de l'OHADA";
}
function _tdr_contexte_api(string $name, string $cat): string {
    $map = [
        'GRH'                             => "La gestion administrative du personnel est souvent assurée par des collaborateurs formés sur le tas, sans méthode ni outils structurés. Les erreurs de procédure, les dossiers incomplets et les retards de déclaration sociale coûtent cher aux entreprises — en redressements, en litiges et en temps perdu. Cette formation transmet les méthodes concrètes, les documents types et les réflexes professionnels pour gérer cette fonction avec rigueur.",
        'Comptabilité & Finance'          => "Une comptabilité mal tenue ou des déclarations fiscales en retard exposent l'entreprise à des pénalités, des redressements et une perte de crédibilité auprès des partenaires et des banques. En Côte d'Ivoire comme dans tout l'espace OHADA, la maîtrise du SYSCOHADA et des obligations fiscales est une compétence non négociable pour tout professionnel de la finance.",
        'Informatique & Tech'             => "Les entreprises africaines investissent massivement dans les outils numériques, mais manquent de techniciens et de développeurs capables de les déployer et de les maintenir localement. Cette formation répond à ce déficit en transmettant des compétences directement applicables, sur des outils réellement utilisés dans les entreprises de la place.",
        'Gestion Commerciale & Marketing' => "Sur les marchés africains, la concurrence s'intensifie et les clients sont de plus en plus informés. Prospecter au hasard, négocier sans méthode et ne pas suivre ses chiffres ne suffit plus. Cette formation structure la démarche commerciale de A à Z pour que chaque heure de travail commercial se transforme en résultat mesurable.",
        'Management & Leadership'         => "Beaucoup de managers ont été promus pour leur expertise technique, sans formation au management. Résultat : des équipes peu mobilisées, des conflits non résolus et des objectifs manqués. Cette formation donne les outils pour exercer l'autorité avec méthode, sans improviser face aux situations difficiles.",
        'Immobilier'                       => "Le marché immobilier en Côte d'Ivoire est en plein essor, mais la pratique reste souvent informelle et exposée à des risques juridiques importants — litiges fonciers, actes mal rédigés, évaluations approximatives. Cette formation professionnalise la pratique en donnant les outils techniques, juridiques et commerciaux du métier.",
        'Logistique & Supply Chain'       => "Les ruptures de stock, les retards de livraison et les coûts logistiques incontrôlés pèsent lourdement sur la compétitivité des entreprises africaines. Cette formation donne les méthodes et les outils pour organiser une chaîne logistique fiable, de l'approvisionnement jusqu'à la livraison au client final.",
        'Entrepreneuriat'                 => "Créer une entreprise sans préparation expose à des erreurs coûteuses : modèle économique non validé, trésorerie mal gérée, équipe mal choisie. Cette formation accompagne les porteurs de projet dans la structuration de leur démarche, pour augmenter significativement les chances de succès au démarrage.",
        'IA & Digitalisation'             => "L'intelligence artificielle et la transformation numérique ne sont plus réservées aux grandes entreprises — elles touchent tous les secteurs, y compris en Afrique. Les professionnels qui ne s'y adaptent pas rapidement risquent d'être dépassés. Cette formation rend le numérique accessible et actionnable, sans jargon technique inutile.",
        'Agriculture'                     => "L'agriculture africaine souffre d'un déficit de professionnalisation : accès limité aux financements, pertes post-récolte, faible valorisation des productions. Cette formation donne aux agripreneurs et aux professionnels du secteur les outils de gestion, de financement et de commercialisation pour rendre leur exploitation rentable et durable.",
    ];
    return $map[$cat] ?? "Les entreprises qui recrutent sur le profil {$name} attendent des candidats capables de produire des résultats dès les premières semaines. Cette formation comble l'écart entre la formation initiale et les exigences réelles du terrain, en transmettant les méthodes, les outils et les réflexes professionnels du domaine {$cat}.";
}
function _tdr_grille_html(array $gr, int $p): string {
    function _gcfa(int $v): string { return number_format($v, 0, ',', ' ') . ' FCFA'; }
    $io  = (!empty($gr['individuel_online'])) ? (int)$gr['individuel_online'] : $p;
    $ip  = (!empty($gr['individuel_pres']))   ? (int)$gr['individuel_pres']  : (int)(round($p*10/7/5000)*5000);
    $hyb = (!empty($gr['hybride']))           ? (int)$gr['hybride']          : (int)(round(($io+$ip)/2/5000)*5000);
    $h = '<style>.tdr-gr{width:100%;border-collapse:collapse;font-size:13px;margin-top:8px}.tdr-gr th{background:#1e3a6e;color:#fff;padding:7px 10px;text-align:center;font-size:11px;text-transform:uppercase}.tdr-gr th:first-child{text-align:left}.tdr-gr td{padding:7px 10px;border:1px solid #e6eaf2;text-align:center}.tdr-gr td:first-child{text-align:left;font-weight:700;color:#1e3a6e}.tdr-gr tr.hl td{background:#fffbeb}.tdr-gr tr.devis td{background:#f8fafc;font-style:italic;color:#6b7280}</style>';
    $h .= '<table class="tdr-gr"><thead><tr><th>Modalité</th><th>💻 En ligne</th><th>🏛️ Présentiel</th></tr></thead><tbody>';
    $h .= '<tr class="hl"><td>👤 Individuel (en direct)</td><td>'._gcfa($io).'</td><td>'._gcfa($ip).'</td></tr>';
    $h .= '<tr><td>🔀 Hybride (en ligne + présentiel)</td><td colspan="2" style="text-align:center">'._gcfa($hyb).'</td></tr>';
    $h .= '<tr class="devis"><td colspan="3">👥 Formation groupe &amp; intra-entreprise — <strong>Sur devis personnalisé</strong> (contactez-nous)</td></tr>';
    $h .= '</tbody></table>';
    $h .= '<p style="font-size:11px;color:#6b7280;margin-top:6px">Frais d\'inscription : 50 000 FCFA (non remboursables). Facilités de paiement disponibles sur demande.</p>';
    return $h;
}

/* Fallback ibigpartners : slug "eduform-*" non trouvé en base locale */
if (!$f && $slug !== '' && strpos($slug, 'eduform-') === 0) {
    $ctx = stream_context_create(['http'=>['timeout'=>7,'method'=>'GET','header'=>"Accept: application/json\r\n"],'ssl'=>['verify_peer'=>true]]);
    $raw = @file_get_contents('https://www.ibigpartners.com/api/catalogue', false, $ctx);
    if ($raw) {
        $api = json_decode($raw, true);
        if (!empty($api['ok']) && !empty($api['formations'])) {
            /* Table de correction tarifaire par durée — identique à catalogue-formations.php */
            $_pf_tdr = [20=>225000,25=>280000,28=>315000,30=>340000,35=>395000,40=>450000,45=>505000,55=>620000,65=>730000,72=>810000,80=>900000];
            foreach ($api['formations'] as $af) {
                if (($af['slug'] ?? '') === $slug) {
                    $p   = (int)($af['price'] ?? 0);
                    $gr  = is_array($af['grille'] ?? null) ? (array)$af['grille'] : [];
                    /* ── Correction tarifaire par durée (même règle que catalogue) ── */
                    $texte_af = ($af['name'] ?? '') . ' ' . ($af['description'] ?? '');
                    $prix_corr = max($p, 200000);
                    if (preg_match('/\((\d+)h\)/', $texte_af, $_hm)) {
                        $_h = (int)$_hm[1];
                        $_pmin = isset($_pf_tdr[$_h]) ? $_pf_tdr[$_h] : (int)(round($_h * 11250 / 5000) * 5000);
                        if ($prix_corr < $_pmin) $prix_corr = $_pmin;
                    }
                    if ($prix_corr !== $p) {
                        /* Prix corrigé : recalcul cohérent de la grille (= grille_local()) */
                        $p = $prix_corr;
                        $_gp = (int)(round($p * 10 / 7 / 5000) * 5000);
                        $gr = [
                            'individuel_online' => $p,
                            'individuel_pres'   => $_gp,
                            'hybride'           => (int)(round(($p + $_gp) / 2 / 5000) * 5000),
                        ];
                    }
                    $pip = (!empty($gr['individuel_pres'])) ? (int)$gr['individuel_pres'] : (int)(round($p * 10/7/5000)*5000);
                    $nom_af = (string)($af['name'] ?? '');
                    $cat_af = (string)($af['category'] ?? 'Autres');
                    /* Durée : extraire depuis la description si possible, sinon dériver du prix corrigé */
                    $heures = 20;
                    if (preg_match('/\((\d+)h\)/', $texte_af, $_hm2)) $heures = (int)$_hm2[1];
                    elseif ($p > 0) $heures = max(20, (int)round($p / 11250));
                    $prog   = _tdr_programme_api($nom_af, $heures, $cat_af, (string)($af['description'] ?? ''));
                    $f = [
                        'id'                    => 0,
                        '_api_grille'           => $gr,
                        'titre'                 => $nom_af,
                        'slug'                  => $slug,
                        'domaine'               => $cat_af,
                        'description'           => (string)($af['description'] ?? ''),
                        'tarif_en_ligne'        => $p,
                        'tarif_presentiel'      => $pip,
                        'duree'                 => $heures . ' heures',
                        'duree_organisation'    => $heures . ' heures de formation intensive, organisées en sessions de 3 à 4 heures — en semaine ou week-end selon le planning de la session.',
                        'modules'               => '',
                        'contenu_programme'     => $prog,
                        'objectif_general'      => _tdr_objectif_api($nom_af, $cat_af),
                        'objectifs_specifiques' => '',
                        'public_cible'          => _tdr_public_api($cat_af),
                        'resultats_attendus'    => _tdr_resultats_api($nom_af, $cat_af),
                        'contexte'              => _tdr_contexte_api($nom_af, $cat_af),
                        'pitch_marketing'       => (string)($af['description'] ?? ''),
                        'is_samedi_pro'         => 0,
                        'date_debut'            => '',
                        'frais_inscription'     => 50000,
                        'type_certificat'       => '',
                        'code'                  => '',
                        'methodologie'          => '',
                        'evaluation'            => '',
                        'formateurs'            => '',
                        'prerequis'             => '',
                        'avantages'             => '',
                        'moyens_logistiques'    => '',
                        'validation'            => '',
                    ];
                    break;
                }
            }
        }
    }
}
if (!$f) { http_response_code(404); exit('Formation introuvable'); }

$L = [];
try { $sl = $pdo->prepare("SELECT * FROM formation_landings WHERE formation_id=? LIMIT 1"); $sl->execute([(int)$f['id']]); $L = $sl->fetch(PDO::FETCH_ASSOC) ?: []; }
catch (Throwable $e) { $L = []; }

function hh($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function fcfa($n): string { return number_format((int)$n, 0, ',', ' ') . ' FCFA'; }
$val = function (string $k) use ($L, $f) { $v = trim((string)($L[$k] ?? '')); if ($v === '') $v = trim((string)($f[$k] ?? '')); return $v; };
function bullets(string $t): string {
    $t = trim($t); if ($t === '') return '';
    $lines = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $t))));
    if (count($lines) <= 1) return '<p>' . nl2br(hh($t)) . '</p>';
    $h = '<ul class="li">'; foreach ($lines as $ln) { $ln = preg_replace('/^[•\-\*]\s*/u', '', $ln); $h .= '<li>' . hh($ln) . '</li>'; }
    return $h . '</ul>';
}
function programme_html(string $t): string {
    $t = trim($t); if ($t === '') return '';
    $t = str_replace(["\r\n", "\r"], "\n", $t);
    if (!preg_match('/(MODULE|CERTIFICAT|BLOC|PARTIE)\s+\d+/u', $t)) return bullets($t);
    $lines = explode("\n", $t); $h = ''; $open = false; $k = 0;
    foreach ($lines as $ln) {
        $ln = trim($ln); if ($ln === '') continue;
        if (preg_match('/^(MODULE|CERTIFICAT|BLOC|PARTIE)\s+\d+/ui', $ln)) {
            if ($open) $h .= '</ul></div>';
            $k++; $h .= '<div class="mod"><h4><span class="mn">' . $k . '</span> ' . hh($ln) . '</h4><ul class="li">'; $open = true;
        } else { $ln = preg_replace('/^[•\-\*]\s*/u', '', $ln); $h .= '<li>' . hh($ln) . '</li>'; }
    }
    if ($open) $h .= '</ul></div>';
    return $h;
}

$titre     = (string)$f['titre'];
$estSamedi = !empty($f['is_samedi_pro']);
$nbCerts   = 0; if (!$estSamedi && preg_match('/(\d+)\s*en\s*1/i', $titre, $mC)) $nbCerts = (int)$mC[1];
$mods      = array_values(array_filter(array_map('trim', explode(';', (string)($f['modules'] ?? '')))));
$app   = defined('APP_URL') ? rtrim(APP_URL, '/') : 'https://ibig-eduform.com';
$email = function_exists('setting') ? setting('contact_email', 'formation@intermark-business.com') : 'formation@intermark-business.com';
$phones= function_exists('setting') ? setting('contact_phones', '+225 27 22 27 60 14') : '+225 27 22 27 60 14';
$statA = function_exists('setting') ? setting('stat_apprenants', '1 000+') : '1 000+';
$statS = function_exists('setting') ? setting('stat_satisfaction', '+90%') : '+90%';
$slugF = (string)($f['slug'] ?? $f['id']);
$dureeTxt = $val('duree_organisation') ?: (string)($f['duree'] ?? '');
$dateTxt = !empty($f['date_debut']) ? date('d/m/Y', strtotime((string)$f['date_debut'])) : '';
$fi = (int)($f['frais_inscription'] ?? 0); if (!$estSamedi && $fi <= 0) $fi = 50000;
if ($estSamedi)        $certLbl = 'Attestation de participation IBIG EDUFORM';
elseif ($nbCerts >= 2) $certLbl = $nbCerts . ' certificats professionnels IBIG EDUFORM (un par spécialité)';
else                   $certLbl = 'Certificat professionnel IBIG EDUFORM';

/* ===== Construction des sections (ton expert + données) ===== */
$S = [];
$S[] = ['Intitulé du programme', '<p><b>' . hh($titre) . '</b> — Domaine : ' . hh($f['domaine'] ?? '') . '.</p><p>Le présent document constitue les <b>Termes de Référence (TDR)</b> officiels du programme. Il définit le cadre, les objectifs, le contenu pédagogique, les modalités d\'évaluation et de certification, ainsi que les conditions d\'admission, de financement et d\'accompagnement. Il engage IBIG EDUFORM sur un standard de qualité orienté <b>résultats, employabilité et impact</b>.'];

if ($val('contexte'))              $S[] = ['Contexte &amp; justification', bullets($val('contexte'))];
if ($val('objectif_general'))      $S[] = ['Objectif général', bullets($val('objectif_general'))];
if ($val('objectifs_specifiques')) $S[] = ['Objectifs pédagogiques spécifiques', bullets($val('objectifs_specifiques'))];

/* Compétences visées (référentiel) — dérivé */
$comp = '';
if ($val('objectifs_specifiques')) {
    $lines = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $val('objectifs_specifiques')))));
    if ($lines) { $comp = '<p>À l\'issue du programme, le participant sera capable de :</p><ul class="li">'; foreach ($lines as $ln) { $ln = preg_replace('/^[•\-\*]\s*/u', '', $ln); $comp .= '<li>' . hh($ln) . '</li>'; } $comp .= '</ul>'; }
}
if ($comp) $S[] = ['Compétences visées (référentiel)', $comp];

if ($val('resultats_attendus'))    $S[] = ['Résultats attendus', bullets($val('resultats_attendus'))];
if ($val('public_cible'))          $S[] = ['Public cible', bullets($val('public_cible'))];

/* Prérequis + admission */
$prq = $val('prerequis') ? bullets($val('prerequis')) : '';
$prq .= '<p style="margin-top:8px"><b>Modalités d\'admission :</b> préinscription en ligne, étude du profil et validation du dossier par IBIG EDUFORM. Les places étant limitées par session afin de garantir la qualité de l\'encadrement, l\'inscription est confirmée après validation et règlement des frais d\'inscription.</p>';
$S[] = ['Prérequis &amp; conditions d\'admission', $prq];

$prog = $val('contenu_programme') ?: (string)($f['modules'] ?? '');
if ($prog) $S[] = ['Contenu pédagogique détaillé', '<p>Le programme s\'articule autour de modules progressifs, chacun associant apports théoriques essentiels et mise en pratique intensive :</p>' . programme_html($prog)];

/* Approche pédagogique (expert) */
$S[] = ['Approche &amp; ingénierie pédagogique',
    '<p>Notre dispositif repose sur une pédagogie <b>active et professionnalisante (70% pratique / 30% théorie)</b>. Chaque notion est immédiatement mise en application à travers :</p>'
    . '<ul class="li"><li>des <b>études de cas réels</b> issus d\'entreprises et d\'organisations ;</li>'
    . '<li>des <b>simulations professionnelles</b> reproduisant les situations de terrain ;</li>'
    . '<li>des <b>ateliers outillés</b> (Excel, logiciels métiers, ERP) ;</li>'
    . '<li>un <b>projet fil rouge</b> évalué, consolidant l\'ensemble des acquis ;</li>'
    . '<li>un <b>encadrement permanent</b> et l\'accès à des supports &amp; modèles professionnels.</li></ul>'
    . ($val('methodologie') ? '<p style="margin-top:8px">' . nl2br(hh($val('methodologie'))) . '</p>' : '')];

if ($dureeTxt) $S[] = ['Durée &amp; organisation', bullets($dureeTxt) . '<p style="margin-top:6px">Format : <b>Présentiel</b> (à Abidjan), <b>En ligne</b> (visioconférence interactive) ou <b>Hybride</b>, selon la session choisie.</p>'];

/* Évaluation & certification (expert) */
$S[] = ['Dispositif d\'évaluation &amp; de certification',
    '<p>L\'évaluation est <b>continue</b> (travaux pratiques, quiz, participation) et sanctionnée par une <b>étude de cas finale</b> et un <b>projet intégré</b>.</p>'
    . ($val('evaluation') ? '<p>' . nl2br(hh($val('evaluation'))) . '</p>' : '')
    . '<div class="cert"><div class="ttl">🎓 ' . hh($certLbl) . '</div>'
    . (($nbCerts >= 2 && $mods) ? ('<ul class="li" style="margin-top:8px">' . implode('', array_map(fn($m) => '<li>Certificat « ' . hh($m) . ' »</li>', $mods)) . '</ul>') : '')
    . '<p style="margin:8px 0 0;font-size:13px;color:#5b6b8c">Délivré aux participants justifiant d\'une assiduité ≥ 80% et de la validation des évaluations.</p></div>'];

/* Tarifs */
if (!empty($f['_api_grille']) && is_array($f['_api_grille'])) {
    $tar = _tdr_grille_html((array)$f['_api_grille'], (int)$f['tarif_en_ligne']);
} else {
    $tar = '<div class="tarifs">';
    if ((int)$f['tarif_en_ligne'] > 0)   $tar .= '<div class="tarif"><span>En ligne</span><b>' . fcfa($f['tarif_en_ligne']) . '</b></div>';
    if ((int)$f['tarif_presentiel'] > 0) $tar .= '<div class="tarif"><span>Présentiel</span><b>' . fcfa($f['tarif_presentiel']) . '</b></div>';
    if ($fi > 0)                         $tar .= '<div class="tarif"><span>Frais d\'inscription</span><b>' . fcfa($fi) . '</b></div>';
    $tar .= '</div>';
}
$S[] = ['Tarifs &amp; participation', $tar];

/* Financement (expert) */
$S[] = ['Financement &amp; facilités',
    '<ul class="li"><li><b>Facilités de paiement</b> possibles (échelonnement selon modalités).</li>'
    . '<li><b>Tarifs préférentiels</b> pour les inscriptions de <b>groupe</b> et les <b>entreprises</b> (formations sur mesure possibles).</li>'
    . '<li><b>Offre Early-bird</b> pour les inscriptions anticipées.</li>'
    . '<li>Le règlement s\'effectue de façon sécurisée (Mobile Money, carte, virement, espèces).</li></ul>'];

/* Accompagnement & insertion (expert) */
$S[] = ['Accompagnement &amp; insertion professionnelle',
    '<p>Au-delà des compétences, IBIG EDUFORM s\'engage sur la <b>trajectoire professionnelle</b> de ses diplômés :</p>'
    . '<ul class="li"><li>optimisation du <b>CV</b> et du profil professionnel ;</li>'
    . '<li><b>coaching</b> et simulations d\'entretien ;</li>'
    . '<li><b>recommandation</b> auprès de notre réseau d\'entreprises et d\'organisations partenaires ;</li>'
    . '<li><b>suivi post-formation</b> et accès à la communauté des apprenants.</li></ul>'
    . '<p>Notre finalité : transformer une compétence acquise en <b>opportunité concrète d\'emploi ou d\'évolution</b>.</p>'];

if ($val('formateurs')) $S[] = ['Formateurs &amp; intervenants', bullets($val('formateurs')) . '<p style="margin-top:6px">Des <b>professionnels en activité</b>, sélectionnés pour leur expertise et leur pédagogie.</p>'];
if ($val('moyens_logistiques')) $S[] = ['Organisation &amp; logistique', bullets($val('moyens_logistiques'))];

/* Engagement qualité (expert) */
$S[] = ['Engagement qualité IBIG EDUFORM',
    '<ul class="li"><li>Formateurs experts en activité &amp; contenus actualisés (évolutions réglementaires et technologiques) ;</li>'
    . '<li>Supports pédagogiques complets, outils et modèles professionnels ;</li>'
    . '<li>Encadrement permanent, groupe d\'entraide et suivi post-formation ;</li>'
    . '<li><b>' . hh($statS) . ' de satisfaction</b> sur <b>' . hh($statA) . ' apprenants formés</b>.</li></ul>'];

/* Pourquoi IBIG */
$S[] = ['Pourquoi choisir IBIG EDUFORM',
    '<p>IBIG EDUFORM est un <b>institut international de formation professionnelle et de certifications</b> orienté impact, performance et employabilité. Nos programmes sont conçus <b>avec et pour les professionnels</b>, ancrés dans les réalités africaines et alignés sur les standards internationaux.</p>'
    . '<p>Un réseau d\'entreprises et d\'organisations partenaires, des formateurs experts, et une approche résolument tournée vers le <b>résultat terrain</b>.</p>'];

if ($val('avantages')) $S[] = ['Avantages clés du programme', bullets($val('avantages'))];

/* Débouchés professionnels — dérivés des métiers/modules */
$debs = array_values(array_filter(array_map('trim', explode(';', (string)($f['modules'] ?? '')))));
if ($debs) {
  $S[] = ['Débouchés professionnels',
    '<p>À l’issue de ce programme, vous pourrez exercer ou évoluer vers les métiers suivants :</p><ul class="li">'
    . implode('', array_map(fn($d) => '<li>' . hh($d) . '</li>', $debs)) . '</ul>'];
}

$S[] = ['Contacts', '<p>📞 ' . nl2br(hh($phones)) . '<br>✉️ ' . hh($email) . '<br>🌐 ' . hh(preg_replace('#^https?://#', '', $app)) . '</p>'];
if (!empty($f['id'])) {
    $preinscURL = $app . '/preinscription.php?formation_id=' . (int)$f['id'];
} else {
    $preinscURL = $app . '/preinscription-generale.php?' . http_build_query([
        'catalogue_nom'   => (string)($f['titre'] ?? ''),
        'formation_slug'  => (string)($f['slug']  ?? ''),
        'domaine'         => (string)($f['domaine'] ?? ''),
        'catalogue_prix'  => (int)($f['tarif_en_ligne'] ?? 0),
    ]);
}
$ficheURL = $app . '/formation/' . rawurlencode($slugF);
$S[] = ['Préinscription en ligne', '<p>Préinscrivez-vous (gratuit et sans engagement) à cette formation :</p><p><a href="' . hh($preinscURL) . '"><b>' . hh($preinscURL) . '</b></a></p><p style="font-size:13px;color:#5b6b8c">Fiche complète : ' . hh($ficheURL) . '</p>'];
$S[] = ['Validation / approbation', '<p>' . hh($val('validation') ?: 'Document validé par IBIG EDUFORM — INTERMARK BUSINESS INTERNATIONAL GROUP SARL.') . '</p>'];

$lead = $val('pitch_marketing') ?: $val('description');

/* ===== THÈME VISUEL UNIQUE PAR FORMATION (futuriste) =====
   Teinte (hue) dérivée de la formation → couleur propre à chacune. */
$seed      = abs(crc32((string)($f['slug'] ?? ($f['code'] ?? $titre)))); // stable & unique par formation
$hue       = $seed % 360;
$hueAcc    = ($hue + 38) % 360;     // accent (contraste chaud)
$hueAcc2   = ($hue + 205) % 360;    // accent2 (complémentaire)
$TH = [
  "hsl($hue,72%,44%)",              // --blue  (primaire vif)
  "hsl($hue,52%,11%)",              // --dark  (fond sombre teinté)
  "hsl($hueAcc,90%,60%)",           // --gold  (accent lumineux)
  "hsl($hueAcc2,82%,62%)",          // --acc2  (accent secondaire)
];
$variant  = ($seed >> 4) % 4;                        // motif de couverture (0..3)
$gradAngle= [120, 135, 160, 205][($seed >> 2) % 4];   // angle du dégradé
$numShape = ($seed >> 6) % 3;                         // forme des numéros (0 carré, 1 rond, 2 organique)
$wmHex    = '1f2a44';                                 // filigrane neutre très clair
/* Filigrane diagonal « IBIG EDUFORM » répété (SVG, teinté thème) */
$WM = "data:image/svg+xml,%3Csvg%20xmlns='http://www.w3.org/2000/svg'%20width='440'%20height='260'%3E%3Ctext%20x='6'%20y='150'%20transform='rotate(-28%20220%20130)'%20fill='%23{$wmHex}'%20fill-opacity='0.05'%20font-size='30'%20font-family='Arial,sans-serif'%20font-weight='bold'%3EIBIG%20EDUFORM%3C/text%3E%3C/svg%3E";
/* Motif de couverture futuriste selon $variant (couches CSS) */
$acc = ltrim($TH[2], '#');
$patterns = [
  /* 0 — lignes diagonales */ "repeating-linear-gradient(45deg,rgba(255,255,255,.06) 0 2px,transparent 2px 16px)",
  /* 1 — points (grille)   */ "radial-gradient(rgba(255,255,255,.10) 1.4px,transparent 1.5px)",
  /* 2 — grille fine       */ "linear-gradient(rgba(255,255,255,.07) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.07) 1px,transparent 1px)",
  /* 3 — halos néon        */ "radial-gradient(120px 120px at 85% 15%,rgba(255,255,255,.12),transparent 70%),radial-gradient(160px 160px at 10% 90%,rgba(255,255,255,.08),transparent 70%)",
];
$patternSize = ['auto', '18px 18px', '26px 26px', 'auto'][$variant];
$coverBg = $patterns[$variant];
/* Réf. document (anti-falsification) */
$ref = 'TDR-' . strtoupper(substr(md5(($f['code'] ?? '') . $slugF . $titre), 0, 8));
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>TDR — <?= hh($titre); ?> — IBIG EDUFORM</title>
<link rel="icon" href="/favicon.ico">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<style>
  :root{--blue:<?= $TH[0]; ?>;--dark:<?= $TH[1]; ?>;--gold:<?= $TH[2]; ?>;--acc2:<?= $TH[3]; ?>;--ink:#16233d;--muted:#5b6b8c;--line:#e6eaf2;--soft:#f6f8fe}
  *{box-sizing:border-box}
  body{margin:0;font-family:'Manrope',system-ui,Segoe UI,Arial,sans-serif;color:var(--ink);background:#dfe5f1;padding:22px 12px}
  .sheet{max-width:900px;margin:0 auto;background:#fff url("<?= $WM; ?>") repeat;border-radius:14px;overflow:hidden;box-shadow:0 30px 80px rgba(15,27,61,.28)}
  /* COUVERTURE FUTURISTE — unique par formation */
  .cover{position:relative;color:#fff;padding:48px 46px 38px;border-bottom:none;overflow:hidden;
    background:<?= $coverBg; ?>,linear-gradient(<?= $gradAngle; ?>deg,var(--dark) 0%,var(--blue) 78%,var(--acc2) 140%);
    <?php if ($patternSize !== 'auto'): ?>background-size:<?= $patternSize; ?>,<?= $patternSize; ?>,cover;<?php endif; ?>}
  .cover::after{content:"";position:absolute;left:0;right:0;bottom:0;height:5px;background:linear-gradient(90deg,var(--gold),var(--acc2),var(--gold))}
  .cover::before{content:"";position:absolute;width:280px;height:280px;right:-90px;top:-110px;border-radius:50%;
    background:radial-gradient(circle,var(--acc2),transparent 65%);opacity:.30;filter:blur(6px)}
  .cover>*{position:relative;z-index:1}
  /* Entête / pied de page courants (visibles aussi dans le PDF) */
  .runhead{display:flex;justify-content:space-between;align-items:center;padding:10px 46px;background:var(--dark);color:#fff;font-size:11px;letter-spacing:.4px}
  .runhead b{color:var(--gold)}
  .runfoot{padding:10px 46px;background:#0b1322;color:#9fb0c9;font-size:10.5px;display:flex;justify-content:space-between;flex-wrap:wrap;gap:6px}
  .cover .wm{position:absolute;right:-26px;top:-44px;font-size:240px;opacity:.06;font-weight:800;line-height:1}
  .cover .brand{display:flex;align-items:center;gap:14px;margin-bottom:22px}
  .cover .brand img{height:56px}
  .cover .brand .ph{width:56px;height:56px;border-radius:13px;background:rgba(255,255,255,.18);display:flex;align-items:center;justify-content:center;font-weight:800;font-size:20px}
  .cover .brand b{font-size:18px;font-weight:800;letter-spacing:.4px}
  .cover .brand span{display:block;font-size:11px;opacity:.85;font-weight:500}
  .kicker{display:inline-block;background:linear-gradient(90deg,var(--gold),var(--acc2));color:#10131c;font-weight:800;font-size:11px;letter-spacing:1.6px;padding:6px 13px;border-radius:999px;text-transform:uppercase;box-shadow:0 4px 16px rgba(0,0,0,.28)}
  .cover h1{font-size:33px;font-weight:800;margin:14px 0 8px;line-height:1.12;max-width:92%;text-shadow:0 3px 24px rgba(0,0,0,.40)}
  .cover .lead{font-size:13.5px;opacity:.92;max-width:84%;line-height:1.55}
  .badges{display:flex;flex-wrap:wrap;gap:10px;margin-top:22px}
  .badge{background:rgba(0,0,0,.32);border:1px solid rgba(255,255,255,.42);border-radius:12px;padding:9px 14px;font-size:12.5px;font-weight:700;color:#fff;text-shadow:0 1px 4px rgba(0,0,0,.35)}
  .badge b{color:#ffd27d}
  /* Sommaire */
  .toc{padding:26px 46px;background:var(--soft);border-bottom:1px solid var(--line);page-break-after:always;break-after:page}
  .toc h2{margin:0 0 12px;font-size:13px;letter-spacing:1.5px;text-transform:uppercase;color:var(--muted)}
  .toc ol{margin:0;padding:0;list-style:none;columns:2;column-gap:30px}
  .toc li{font-size:13px;padding:5px 0;color:#2a3a5c;break-inside:avoid}
  .toc li b{color:var(--blue);font-weight:800;margin-right:8px}
  .body{padding:34px 46px}
  .sec{margin:0 0 26px;opacity:0;transform:translateY(14px);transition:opacity .6s ease,transform .6s ease}
  .sec.in,.printing .sec{opacity:1;transform:none}
  .sec h3{display:flex;align-items:center;gap:12px;font-size:15px;font-weight:800;color:var(--dark);margin:0 0 10px;text-transform:uppercase;letter-spacing:.4px;page-break-after:avoid;break-after:avoid;page-break-inside:avoid;break-inside:avoid}
  /* Pagination PDF : ne jamais couper une carte/un module au milieu */
  .mod,.cert,.tarif{page-break-inside:avoid;break-inside:avoid}
  ul.li li,.sec .c p{page-break-inside:avoid;break-inside:avoid}
  /* Mode capture PDF : on neutralise centrage, ombre, marges et fond */
  body.pdf-mode{background:#fff;padding:0;margin:0}
  body.pdf-mode .sheet{max-width:none;width:100%;margin:0;border-radius:0;box-shadow:none}
  body.pdf-mode .bar,body.pdf-mode .pad{display:none}
  .sec h3 .n{flex:0 0 auto;width:32px;height:32px;border-radius:<?= ['10px','50%','45% 55% 50% 50%'][$numShape]; ?>;background:linear-gradient(135deg,var(--blue),var(--acc2));color:#fff;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:800;box-shadow:0 6px 16px rgba(0,0,0,.20)}
  .sec .c{font-size:14px;line-height:1.68;color:#2a3a5c} .sec .c p{margin:0 0 9px}
  ul.li{margin:0;padding-left:20px} ul.li li{margin:5px 0;font-size:14px;line-height:1.55}
  .mod{background:var(--soft);border:1px solid var(--line);border-left:4px solid var(--gold);border-radius:10px;padding:14px 16px;margin:10px 0}
  .mod h4{margin:0 0 8px;color:var(--blue);font-size:14px;font-weight:800;display:flex;align-items:center;gap:9px}
  .mod h4 .mn{width:22px;height:22px;border-radius:6px;background:var(--gold);color:#1a1205;display:inline-flex;align-items:center;justify-content:center;font-size:12px}
  .tarifs{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px}
  .tarif{border:1px solid var(--line);border-radius:12px;padding:14px;text-align:center;background:var(--soft)}
  .tarif span{display:block;font-size:11.5px;color:var(--muted);text-transform:uppercase;letter-spacing:.5px}
  .tarif b{display:block;font-size:18px;color:var(--dark);margin-top:4px}
  .cert{background:linear-gradient(135deg,rgba(245,166,35,.14),rgba(11,74,166,.06));border:1px solid rgba(245,166,35,.4);border-radius:12px;padding:16px;margin-top:10px}
  .cert .ttl{font-weight:800;color:var(--dark)}
  .footer{background:var(--dark);color:#cdd7ea;padding:24px 46px;font-size:12px;line-height:1.7}
  .footer b{color:#fff} .footer .cta{display:inline-block;margin-top:8px;background:var(--gold);color:#1a1205;font-weight:800;text-decoration:none;padding:9px 15px;border-radius:9px}
  /* Barre d'actions flottante */
  .bar{position:fixed;left:0;right:0;bottom:0;background:rgba(15,39,66,.96);backdrop-filter:blur(8px);padding:12px;display:flex;gap:10px;justify-content:center;z-index:50}
  .btn{background:linear-gradient(135deg,#22c55e,#16a34a);color:#022c22;border:0;padding:13px 26px;border-radius:11px;font-weight:800;cursor:pointer;font-size:15px;font-family:inherit}
  .btn.sec2{background:#fff;color:var(--dark)}
  .btn:disabled{opacity:.6}
  .pad{height:74px}
  @media print{ .bar,.pad{display:none} body{background:#fff;padding:0} .sheet{box-shadow:none;border-radius:0} .sec{opacity:1!important;transform:none!important} }
  @media(max-width:560px){.cover,.toc,.body,.footer{padding-left:22px;padding-right:22px}.cover h1{font-size:24px}.toc ol{columns:1}}
</style>
</head>
<body>
<div class="sheet" id="tdrSheet">
  <div class="runhead">
    <span><b>IBIG EDUFORM</b> · Termes de Référence</span>
    <span>Réf. <?= hh($ref); ?></span>
  </div>
  <div class="cover">
    <div class="wm">IBIG</div>
    <div class="brand">
      <img src="/assets/images/logo.png" alt="IBIG EDUFORM" onerror="this.outerHTML='<div class=&quot;ph&quot;>IB</div>'">
      <div><b>IBIG EDUFORM</b><span>Institut international de formation professionnelle &amp; certifications</span></div>
    </div>
    <span class="kicker">Termes de Référence — Programme officiel</span>
    <h1><?= hh($titre); ?></h1>
    <?php if ($lead):
      $leadShort = trim($lead);
      if (mb_strlen($leadShort) > 240) {
        $leadShort = mb_substr($leadShort, 0, 240);
        $sp = mb_strrpos($leadShort, ' ');
        if ($sp !== false) { $leadShort = mb_substr($leadShort, 0, $sp); }
        $leadShort = rtrim($leadShort, " ,;:–—-") . '…';
      }
    ?><p class="lead"><?= hh($leadShort); ?></p><?php endif; ?>
    <div class="badges">
      <span class="badge">🏷️ <b><?= hh($f['type_certificat'] ?: ($estSamedi ? 'Samedi Pro' : 'Pack Premium')); ?></b></span>
      <?php $dureeShort = trim((string)($f['duree'] ?? '')); if ($dureeShort !== ''): ?><span class="badge">⏱️ <b><?= hh($dureeShort); ?></b></span><?php endif; ?>
      <?php if ($nbCerts >= 2): ?><span class="badge">🎓 <b><?= (int)$nbCerts; ?> certificats</b></span><?php endif; ?>
      <?php if ($dateTxt): ?><span class="badge">📅 Démarrage : <?= hh($dateTxt); ?></span><?php endif; ?>
      <span class="badge">🌐 Présentiel · En ligne · Hybride</span>
    </div>
  </div>

  <div class="toc">
    <h2>Sommaire</h2>
    <ol>
      <?php foreach ($S as $i => $s): ?><li><b><?= ($i + 1); ?>.</b><?= $s[0]; ?></li><?php endforeach; ?>
    </ol>
  </div>

  <div class="body">
    <?php foreach ($S as $i => $s): ?>
      <div class="sec">
        <h3><span class="n"><?= ($i + 1); ?></span> <?= $s[0]; ?></h3>
        <div class="c"><?= $s[1]; ?></div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="footer">
    <b>IBIG EDUFORM</b> — Institut international de formation professionnelle &amp; certifications orientées impact, performance et employabilité.<br>
    📞 <?= hh(str_replace("\n", ' · ', $phones)); ?> &nbsp;·&nbsp; ✉️ <?= hh($email); ?> &nbsp;·&nbsp; 🌐 <?= hh(preg_replace('#^https?://#', '', $app)); ?><br>
    <a class="cta" href="<?= hh($preinscURL); ?>">Se préinscrire en ligne →</a>
    <div style="margin-top:10px;opacity:.7;font-style:italic">« Chez IBIG EDUFORM, c'est l'excellence à travers la formation professionnelle. »</div>
  </div>
  <div class="runfoot">
    <span>Document officiel IBIG EDUFORM — Réf. <?= hh($ref); ?> — généré le <?= hh(date('d/m/Y')); ?></span>
    <span>Document protégé — toute reproduction ou modification est interdite.</span>
  </div>
</div>
<div class="pad"></div>

<div class="bar">
  <button class="btn" id="dlBtn" type="button">⬇ Télécharger le TDR (PDF)</button>
  <button class="btn sec2" type="button" onclick="window.print()">🖨️ Imprimer</button>
</div>

<script>
  (function(){
    var btn=document.getElementById('dlBtn'), sheet=document.getElementById('tdrSheet');
    /* Révélation au défilement (dynamisme) */
    var secs=document.querySelectorAll('.sec');
    if('IntersectionObserver' in window){
      var io=new IntersectionObserver(function(es){es.forEach(function(e){if(e.isIntersecting){e.target.classList.add('in');io.unobserve(e.target);}});},{threshold:.12});
      secs.forEach(function(s){io.observe(s);});
    } else { secs.forEach(function(s){s.classList.add('in');}); }

    var fname = 'TDR-' + (<?= json_encode($slugF); ?> || 'formation') + '.pdf';
    btn.addEventListener('click',function(){
      var old=btn.textContent; btn.textContent='⏳ Génération du PDF…'; btn.disabled=true;
      /* 1) Remonter en haut (html2canvas capture depuis le scroll) + mode capture */
      window.scrollTo(0,0);
      document.body.classList.add('printing','pdf-mode');
      function done(){ btn.textContent=old; btn.disabled=false; document.body.classList.remove('printing','pdf-mode'); }
      if(typeof html2pdf==='undefined'){ window.print(); done(); return; }
      /* Pied de page (réf. document) — répété sur chaque page */
      var FOOT_REF = <?= json_encode('Réf. ' . $ref); ?>;
      var opt={
        /* marge basse réservée (16 mm) : évite que le contenu soit coupé
           et laisse la place au pied de page. Côtés à 0 = design pleine largeur. */
        margin:[8,0,16,0],
        filename:fname,
        image:{type:'jpeg',quality:0.98},
        html2canvas:{scale:2,useCORS:true,backgroundColor:'#ffffff',scrollX:0,scrollY:0,windowWidth:sheet.scrollWidth},
        jsPDF:{unit:'mm',format:'a4',orientation:'portrait',
          encryption:{userPassword:'',ownerPassword:<?= json_encode('IBIG-EDUFORM-' . $ref . '-LOCK-' . bin2hex(random_bytes(4))); ?>,userPermissions:['print']}},
        pagebreak:{mode:['css','legacy']}
      };
      /* petit délai pour laisser le reflow du mode capture se faire */
      setTimeout(function(){
        html2pdf().set(opt).from(sheet).toPdf().get('pdf').then(function(pdf){
          var total = pdf.internal.getNumberOfPages();
          var w = pdf.internal.pageSize.getWidth();
          var h = pdf.internal.pageSize.getHeight();
          for(var i=1;i<=total;i++){
            pdf.setPage(i);
            /* filet séparateur */
            pdf.setDrawColor(206,214,230); pdf.setLineWidth(0.25);
            pdf.line(12, h-11, w-12, h-11);
            /* texte du pied */
            pdf.setFont('helvetica','normal'); pdf.setFontSize(7.5); pdf.setTextColor(120,132,156);
            pdf.text('IBIG EDUFORM', 12, h-6.5);
            pdf.text(FOOT_REF, w/2, h-6.5, {align:'center'});
            pdf.text('Page ' + i + ' / ' + total, w-12, h-6.5, {align:'right'});
          }
        }).save().then(done).catch(function(){ window.print(); done(); });
      },120);
    });
  })();
</script>
</body>
</html>
