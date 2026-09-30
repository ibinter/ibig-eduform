<?php
/**
 * IBIG EDUFORM — Insertion des formations manquantes au catalogue
 * Protégé par le .htaccess outils/
 * À supprimer après exécution.
 */
require_once __DIR__ . '/../core/bootstrap.php';
header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><title>Insertion formations manquantes</title>
<style>
body{font-family:sans-serif;max-width:900px;margin:40px auto;padding:0 20px}
.ok{color:#16a34a;font-weight:bold}.err{color:#dc2626;font-weight:bold}
.skip{color:#d97706}.row{padding:4px 0;border-bottom:1px solid #e5e7eb;font-size:.9rem}
h2{margin-top:30px;color:#1e3a5f}
</style>
</head>
<body>
<h1>Insertion des formations manquantes — IBIG EDUFORM</h1>
<?php

$formations = [

    /* ═══════════════════════════════════════════════════════
       GESTION COMMERCIALE & MARKETING — Rôles manquants
       ═══════════════════════════════════════════════════════ */

    [
        'titre'           => 'Chargé d\'Affaires — Développement commercial & gestion de comptes',
        'domaine'         => 'Gestion Commerciale & Marketing',
        'description'     => 'Le Chargé d\'Affaires est le pilier du développement commercial en entreprise : il identifie les opportunités, négocie les contrats, coordonne les projets et fidélise les clients stratégiques. Cette formation certifiante couvre l\'ensemble du cycle d\'affaires — prospection B2B, élaboration d\'offres, pilotage des projets clients, reporting commercial et gestion de la relation grands comptes. À l\'issue du parcours, le stagiaire maîtrise les outils de CRM, les techniques de négociation avancée et le suivi de la rentabilité de son portefeuille clients.',
        'duree'           => '5 jours (40h)',
        'mode'            => 'hybride',
        'tarif_en_ligne'  => 220000,
        'tarif_presentiel'=> 270000,
        'slug'            => 'charge-affaires-developpement-commercial-gestion-comptes',
    ],

    [
        'titre'           => 'Technico-Commercial — Vente de solutions techniques et services',
        'domaine'         => 'Gestion Commerciale & Marketing',
        'description'     => 'Le Technico-Commercial combine expertise technique et compétences commerciales pour vendre des produits ou services à fort contenu technologique (équipements industriels, solutions informatiques, matériaux de construction, équipements médicaux…). La formation développe la capacité à comprendre les besoins techniques du client, concevoir une offre sur mesure, argumenter sur les aspects techniques et commerciaux, et conclure des ventes à cycle long. Idéal pour les ingénieurs souhaitant évoluer vers des fonctions commerciales ou les commerciaux issus de secteurs techniques.',
        'duree'           => '4 jours (32h)',
        'mode'            => 'hybride',
        'tarif_en_ligne'  => 210000,
        'tarif_presentiel'=> 260000,
        'slug'            => 'technico-commercial-vente-solutions-techniques-services',
    ],

    [
        'titre'           => 'Administration des Ventes (ADV) & Chargé de Facturation',
        'domaine'         => 'Gestion Commerciale & Marketing',
        'description'     => 'L\'Administration des Ventes est une fonction support indispensable à toute équipe commerciale : gestion des commandes, émission des factures, suivi des livraisons, traitement des réclamations clients et coordination avec les équipes logistique, finance et production. Cette formation pratique couvre la chaîne administrative depuis la réception de la commande jusqu\'au paiement : saisie, validation, expédition, facturation, relances et archivage. Adapté aux PME, entreprises commerciales et structures de distribution.',
        'duree'           => '3 jours (24h)',
        'mode'            => 'hybride',
        'tarif_en_ligne'  => 200000,
        'tarif_presentiel'=> 250000,
        'slug'            => 'administration-ventes-adv-charge-facturation',
    ],

    [
        'titre'           => 'Animateur & Superviseur des Ventes — Pilotage d\'équipe terrain',
        'domaine'         => 'Gestion Commerciale & Marketing',
        'description'     => 'Le Superviseur ou Animateur des Ventes encadre et motive une équipe de commerciaux terrain pour atteindre les objectifs de vente. Cette formation développe les compétences en management commercial de proximité : organisation des tournées, briefing et debriefing des équipes, analyse des performances individuelles, coaching terrain, remontée des informations marché et reporting à la direction. Programme centré sur les réalités du terrain en Afrique de l\'Ouest, avec des cas pratiques issus de la grande distribution, du FMCG et des réseaux de vente directe.',
        'duree'           => '3 jours (24h)',
        'mode'            => 'hybride',
        'tarif_en_ligne'  => 210000,
        'tarif_presentiel'=> 260000,
        'slug'            => 'animateur-superviseur-ventes-pilotage-equipe-terrain',
    ],

    /* ═══════════════════════════════════════════════════════
       COMPTABILITÉ & FINANCE — Rôles opérationnels manquants
       ═══════════════════════════════════════════════════════ */

    [
        'titre'           => 'Chargé de Recouvrement & Gestion du Crédit Client',
        'domaine'         => 'Comptabilité & Finance',
        'description'     => 'Le Chargé de Recouvrement est en charge de réduire les impayés, optimiser le délai moyen de règlement (DSO) et maintenir de bonnes relations avec les clients débiteurs. Cette formation couvre l\'analyse du risque crédit, la mise en place d\'une politique de recouvrement, la rédaction de relances (amiable, précontentieux, contentieux), la négociation de plans de paiement et le suivi juridique des créances. Essentiel pour les entreprises confrontées à des problèmes de trésorerie liés aux créances clients.',
        'duree'           => '3 jours (24h)',
        'mode'            => 'hybride',
        'tarif_en_ligne'  => 200000,
        'tarif_presentiel'=> 250000,
        'slug'            => 'charge-recouvrement-gestion-credit-client',
    ],

    [
        'titre'           => 'Secrétaire Comptable — Fonctions administratives et comptables',
        'domaine'         => 'Comptabilité & Finance',
        'description'     => 'La Secrétaire Comptable est un profil polyvalent très recherché dans les PME et TPE : elle assure à la fois des tâches administratives (accueil, correspondance, archivage) et des fonctions comptables de base (saisie des pièces comptables, rapprochements bancaires, préparation des factures, suivi des règlements). Cette formation combine les fondamentaux de la comptabilité générale (plan SYSCOHADA) et les outils bureautiques et logiciels de comptabilité (Sage, Excel), adaptés au contexte ivoirien et OHADA.',
        'duree'           => '4 jours (32h)',
        'mode'            => 'hybride',
        'tarif_en_ligne'  => 200000,
        'tarif_presentiel'=> 250000,
        'slug'            => 'secretaire-comptable-fonctions-administratives-comptables',
    ],

    [
        'titre'           => 'Caissier & Gestionnaire de Caisse — Commerce et services',
        'domaine'         => 'Comptabilité & Finance',
        'description'     => 'Indispensable dans le commerce de détail, la restauration, les hôtels, les stations-service et les institutions financières, le Caissier est garant de la fiabilité des encaissements. Cette formation couvre les techniques de tenue de caisse, la gestion des fonds de caisse, les procédures d\'ouverture/fermeture, la vérification des billets, la gestion des écarts et le reporting caisse. Le stagiaire acquiert également les réflexes de prévention des fraudes et les procédures de sécurité monétaire adaptées aux standards bancaires et commerciaux en Côte d\'Ivoire.',
        'duree'           => '2 jours (16h)',
        'mode'            => 'presentiel',
        'tarif_en_ligne'  => 200000,
        'tarif_presentiel'=> 250000,
        'slug'            => 'caissier-gestionnaire-caisse-commerce-services',
    ],

    [
        'titre'           => 'Responsable Administratif et Financier (RAF) — PME & entreprises en croissance',
        'domaine'         => 'Comptabilité & Finance',
        'description'     => 'Le Responsable Administratif et Financier (RAF) est le bras droit de la direction pour tout ce qui concerne la gestion financière et administrative de l\'entreprise. À la différence du DAF de grande entreprise, le RAF d\'une PME cumule les fonctions : tenue de la comptabilité générale, gestion de trésorerie, élaboration du budget, supervision de la paie, conformité fiscale et sociale, et coordination administrative. Cette formation intensive permet aux comptables, assistants de direction ou chefs comptables d\'acquérir la dimension managériale et stratégique du poste de RAF.',
        'duree'           => '5 jours (40h)',
        'mode'            => 'hybride',
        'tarif_en_ligne'  => 240000,
        'tarif_presentiel'=> 290000,
        'slug'            => 'responsable-administratif-financier-raf-pme-entreprises-croissance',
    ],

    /* ═══════════════════════════════════════════════════════
       DIRECTION & ADMINISTRATION — Rôles manquants
       ═══════════════════════════════════════════════════════ */

    [
        'titre'           => 'Chargé de Communication — Institutionnelle et Digitale',
        'domaine'         => 'Direction & Administration',
        'description'     => 'Le Chargé de Communication conçoit et met en œuvre la stratégie de communication interne et externe de l\'organisation. Cette formation couvre la communication institutionnelle (relations presse, discours officiels, corporate), la communication digitale (réseaux sociaux, site web, newsletter), la gestion de l\'image de marque et la communication de crise. Le stagiaire apprend à produire des supports de communication professionnels, animer des réseaux sociaux dans une logique de marque employeur et mesurer l\'impact de ses actions. Adapté aux entreprises, ONG, institutions publiques et collectivités.',
        'duree'           => '4 jours (32h)',
        'mode'            => 'hybride',
        'tarif_en_ligne'  => 210000,
        'tarif_presentiel'=> 260000,
        'slug'            => 'charge-communication-institutionnelle-digitale',
    ],

    [
        'titre'           => 'Agent Administratif Polyvalent — Bureautique et gestion de bureau',
        'domaine'         => 'Direction & Administration',
        'description'     => 'L\'Agent Administratif Polyvalent est le premier niveau d\'emploi administratif en entreprise : accueil physique et téléphonique, gestion du courrier, saisie de documents, classement et archivage, coordination de réunions et suivi des commandes de fournitures. Cette formation donne les fondamentaux pratiques du métier : maîtrise des outils bureautiques (Word, Excel, Outlook), rédaction administrative, protocole de communication professionnel et gestion du temps. Idéal pour les primo-entrants sur le marché du travail et les personnes en reconversion vers les fonctions support.',
        'duree'           => '3 jours (24h)',
        'mode'            => 'hybride',
        'tarif_en_ligne'  => 200000,
        'tarif_presentiel'=> 250000,
        'slug'            => 'agent-administratif-polyvalent-bureautique-gestion-bureau',
    ],

    [
        'titre'           => 'Responsable des Services Généraux & Patrimoine',
        'domaine'         => 'Direction & Administration',
        'description'     => 'Le Responsable des Services Généraux assure le bon fonctionnement des infrastructures, équipements et services de l\'entreprise : gestion des locaux, entretien, sécurité des biens, contrats de maintenance, parc automobile, fournitures et gestion des prestataires. Cette formation couvre la planification des travaux, la gestion des contrats de services, le suivi budgétaire, la négociation avec les fournisseurs et les obligations réglementaires (sécurité incendie, hygiène, normes ERP). Essentiel pour les entreprises disposant de plusieurs sites ou d\'un parc important.',
        'duree'           => '3 jours (24h)',
        'mode'            => 'hybride',
        'tarif_en_ligne'  => 210000,
        'tarif_presentiel'=> 260000,
        'slug'            => 'responsable-services-generaux-patrimoine',
    ],

    /* ═══════════════════════════════════════════════════════
       LOGISTIQUE & SUPPLY CHAIN — Rôles manquants
       ═══════════════════════════════════════════════════════ */

    [
        'titre'           => 'Réceptionnaire & Contrôleur de Marchandises — Entrepôt et distribution',
        'domaine'         => 'Logistique & Supply Chain',
        'description'     => 'Le Réceptionnaire est le gardien de la qualité et de la conformité des marchandises à l\'entrée en entrepôt ou en magasin. Cette formation couvre les procédures de réception, les techniques de contrôle quantitatif et qualitatif, la gestion des litiges fournisseurs, l\'étiquetage et le rangement en stock, ainsi que l\'utilisation des outils de gestion de stocks (logiciels, codes-barres, WMS). Très demandé dans la grande distribution, les industries agroalimentaires, les entrepôts logistiques et le commerce général.',
        'duree'           => '2 jours (16h)',
        'mode'            => 'presentiel',
        'tarif_en_ligne'  => 200000,
        'tarif_presentiel'=> 250000,
        'slug'            => 'receptionnaire-controleur-marchandises-entrepot-distribution',
    ],

    [
        'titre'           => 'Acheteur Opérationnel & Gestion des Fournisseurs — PME & ETI',
        'domaine'         => 'Logistique & Supply Chain',
        'description'     => 'L\'Acheteur Opérationnel gère au quotidien les commandes d\'achats, la relation fournisseurs et le suivi des livraisons. Distinct du responsable achats stratégique, il traite les besoins récurrents d\'une PME ou d\'un département : consultation des fournisseurs, comparaison des offres, passation de commandes, suivi de la conformité, relances et évaluation des performances fournisseurs. Cette formation pratique donne les outils pour rationaliser les coûts d\'achats, négocier les conditions tarifaires et mettre en place un panel fournisseurs fiable. Inclut la gestion des achats sous système SYSCOHADA.',
        'duree'           => '3 jours (24h)',
        'mode'            => 'hybride',
        'tarif_en_ligne'  => 210000,
        'tarif_presentiel'=> 260000,
        'slug'            => 'acheteur-operationnel-gestion-fournisseurs-pme-eti',
    ],

    /* ═══════════════════════════════════════════════════════
       RESSOURCES HUMAINES — Rôles manquants
       ═══════════════════════════════════════════════════════ */

    [
        'titre'           => 'Chargé des Ressources Humaines — Prise de poste et fondamentaux',
        'domaine'         => 'Ressources Humaines',
        'description'     => 'Conçue pour les professionnels qui débutent en RH ou qui prennent en charge la fonction RH dans une PME sans équipe dédiée, cette formation couvre l\'essentiel opérationnel : rédaction des contrats de travail, gestion administrative du personnel, suivi des absences et congés, préparation de la paie, déclarations sociales CNPS, procédures disciplinaires et relations avec l\'inspection du travail. Le stagiaire repart avec des modèles documentaires conformes au droit du travail ivoirien et une feuille de route claire pour structurer la fonction RH dans son organisation.',
        'duree'           => '4 jours (32h)',
        'mode'            => 'hybride',
        'tarif_en_ligne'  => 210000,
        'tarif_presentiel'=> 260000,
        'slug'            => 'charge-ressources-humaines-prise-poste-fondamentaux',
    ],

    /* ═══════════════════════════════════════════════════════
       MANAGEMENT & LEADERSHIP — Rôles manquants
       ═══════════════════════════════════════════════════════ */

    [
        'titre'           => 'Chef d\'Équipe & Superviseur Opérationnel — Passer du technique au management',
        'domaine'         => 'Management & Leadership',
        'description'     => 'La première promotion vers l\'encadrement est souvent la plus difficile : passer de technicien ou opérateur expert à responsable d\'une équipe exige de nouvelles compétences relationnelles et managériales. Cette formation accompagne les nouveaux chefs d\'équipe, superviseurs et contremaîtres dans leur prise de rôle : légitimité managériale, organisation du travail collectif, délégation et contrôle, gestion des conflits dans l\'équipe, animation des réunions opérationnelles et communication avec la hiérarchie. Cas pratiques issus de l\'industrie, de la logistique, du commerce et des services en Afrique.',
        'duree'           => '3 jours (24h)',
        'mode'            => 'hybride',
        'tarif_en_ligne'  => 210000,
        'tarif_presentiel'=> 260000,
        'slug'            => 'chef-equipe-superviseur-operationnel-passer-technique-management',
    ],

    [
        'titre'           => 'Responsable des Opérations — Pilotage et performance opérationnelle',
        'domaine'         => 'Management & Leadership',
        'description'     => 'Le Responsable des Opérations coordonne l\'ensemble des activités opérationnelles d\'une entreprise ou d\'une unité : production, logistique, qualité, maintenance et ressources humaines de terrain. Il optimise les processus, réduit les coûts, gère les priorités et garantit la qualité de service. Cette formation couvre le pilotage par les KPIs opérationnels, les outils Lean, la gestion des aléas (ruptures, pannes, pics d\'activité), la coordination interservices et le reporting à la direction. Adapté aux managers de sites, directeurs d\'agence ou responsables de filiales PME.',
        'duree'           => '4 jours (32h)',
        'mode'            => 'hybride',
        'tarif_en_ligne'  => 240000,
        'tarif_presentiel'=> 290000,
        'slug'            => 'responsable-operations-pilotage-performance-operationnelle',
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
        titre=VALUES(titre),
        description=VALUES(description),
        duree=VALUES(duree),
        mode=VALUES(mode),
        tarif_en_ligne=VALUES(tarif_en_ligne),
        tarif_presentiel=VALUES(tarif_presentiel),
        statut='active'";

$checkSql = "SELECT id FROM formations WHERE slug = :slug LIMIT 1";
$stmtCheck  = $pdo->prepare($checkSql);
$stmtInsert = $pdo->prepare($insertSql);

$nbInserted = 0;
$nbUpdated  = 0;
$nbError    = 0;

echo "<h2>Résultats de l'insertion</h2>";

foreach ($formations as $f) {
    // Vérif règle tarifaire absolue
    if ($f['tarif_en_ligne'] < 200000 || $f['tarif_presentiel'] < 250000) {
        echo "<div class='row'><span class='err'>⛔ TARIF INVALIDE</span> — {$f['titre']}</div>";
        $nbError++;
        continue;
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

echo "<h2>Bilan</h2>";
echo "<p><strong class='ok'>✅ {$nbInserted} insérée(s)</strong> | <strong class='skip'>🔄 {$nbUpdated} mise(s) à jour</strong> | <strong class='err'>❌ {$nbError} erreur(s)</strong></p>";
echo "<p><em>Script à supprimer après utilisation.</em></p>";
?>
</body></html>
