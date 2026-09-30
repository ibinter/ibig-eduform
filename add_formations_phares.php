<?php
if (($_GET['k'] ?? '') !== 'ibig-add-phares-2026') { http_response_code(403); exit('Forbidden'); }
header('Content-Type: text/plain; charset=utf-8');

require_once __DIR__ . '/core/config.php';
require_once __DIR__ . '/core/database.php';
$pdo = Database::connect();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function r5(float $v): int { return (int)(round($v / 5000) * 5000); }
function tarifs(int $h): array {
    $PF = [20=>225000,25=>280000,28=>315000,30=>340000,35=>395000,40=>450000,45=>505000,55=>620000,65=>730000,72=>810000,80=>900000];
    if (isset($PF[$h])) { $tel = $PF[$h]; }
    else {
        $keys = array_keys($PF); sort($keys);
        $inf = $sup = null;
        foreach ($keys as $k) { if ($k <= $h) $inf=$k; if ($k >= $h && $sup===null) $sup=$k; }
        $tel = ($inf !== null && $sup !== null && $inf !== $sup)
            ? r5($PF[$inf] + ($h-$inf)/($sup-$inf) * ($PF[$sup]-$PF[$inf]))
            : r5($h * 11250);
    }
    $tel = max($tel, 200000);
    $tp  = max(r5($tel * 10 / 7), 250000);
    $th  = r5(($tel + $tp) / 2);
    return ['tel'=>$tel,'tp'=>$tp,'th'=>$th];
}

// Vérifier si un slug existe déjà
function slugExists(PDO $pdo, string $slug): bool {
    $st = $pdo->prepare("SELECT COUNT(*) FROM formations WHERE slug=:s");
    $st->execute([':s'=>$slug]);
    return (int)$st->fetchColumn() > 0;
}

$formations = [

    /* ── BANQUE & ASSURANCE ── */
    [
        'titre'   => 'Analyste Crédit & Risque Bancaire',
        'slug'    => 'analyste-credit-risque-bancaire',
        'domaine' => 'Banque & Assurance',
        'duree'   => '25H',
        'desc'    => 'Maîtriser l\'analyse du risque crédit en banque et institution financière : scoring clients, étude de dossiers entreprises et particuliers, notation interne, gestion des créances douteuses et conformité Bâle. Parcours certifiant orienté pratique métier.',
    ],

    /* ── DIRECTION & ADMINISTRATION ── */
    [
        'titre'   => 'Secrétaire de Direction — Parcours Certifiant',
        'slug'    => 'secretaire-de-direction-parcours-certifiant',
        'domaine' => 'Direction & Administration',
        'duree'   => '20H',
        'desc'    => 'Formation complète au métier de secrétaire de direction : gestion d\'agenda et des priorités, traitement du courrier, organisation de réunions et déplacements, rédaction de comptes rendus, accueil et protocole. Certificat IBIG EDUFORM reconnu dans l\'espace OHADA.',
    ],

    /* ── INFORMATIQUE & TECH ── */
    [
        'titre'   => 'Chef de Projet IT & Transformation Digitale',
        'slug'    => 'chef-de-projet-it-transformation-digitale',
        'domaine' => 'Informatique & Tech',
        'duree'   => '30H',
        'desc'    => 'Piloter des projets informatiques de A à Z : cadrage et cahier des charges, méthodes agiles (Scrum, Kanban), gestion des risques IT, conduite du changement et transformation digitale. Certification pratique pour chefs de projet évoluant dans les DSI et entreprises en mutation numérique.',
    ],
    [
        'titre'   => 'Développeur Mobile — Android & iOS',
        'slug'    => 'developpeur-mobile-android-ios',
        'domaine' => 'Informatique & Tech',
        'duree'   => '35H',
        'desc'    => 'Concevoir et déployer des applications mobiles natives et cross-platform : Flutter, React Native, Android Studio, Xcode. De la maquette UI/UX au déploiement sur les stores. Parcours pratique avec projets réels pour développeurs souhaitant se spécialiser en mobile.',
    ],

    /* ── SANTÉ & PHARMACIE ── */
    [
        'titre'   => 'Pharmacien d\'Officine & Gestion de Pharmacie',
        'slug'    => 'pharmacien-officine-gestion-pharmacie',
        'domaine' => 'Santé & Pharmacie',
        'duree'   => '25H',
        'desc'    => 'Formation pratique pour pharmaciens et gérants d\'officine : conseil pharmaceutique, gestion des stocks et périmés, réglementation des médicaments en zone OHADA, dispensation sous ordonnance, traçabilité et pharmacovigilance. Reconnue dans les 17 pays de l\'espace OHADA.',
    ],
    [
        'titre'   => 'Secrétaire Médicale & Administrative de Santé',
        'slug'    => 'secretaire-medicale-administrative-sante',
        'domaine' => 'Santé & Pharmacie',
        'duree'   => '20H',
        'desc'    => 'Métier essentiel des structures de santé : accueil et orientation des patients, gestion des dossiers médicaux, facturation et prise en charge, terminologie médicale de base, coordination avec les équipes soignantes. Certificat adapté aux cliniques, hôpitaux et cabinets médicaux de l\'espace OHADA.',
    ],

    /* ── BEAUTÉ & BIEN-ÊTRE ── */
    [
        'titre'   => 'Gérant de Salon de Beauté — Management & Gestion',
        'slug'    => 'gerant-salon-beaute-management-gestion',
        'domaine' => 'Beauté & Bien-être',
        'duree'   => '20H',
        'desc'    => 'Créer et gérer un salon de beauté rentable : business plan, gestion de trésorerie, management d\'équipe, approvisionnement et stocks, marketing local et digital, fidélisation clientèle. Pour esthéticiennes, coiffeuses et entrepreneurs du secteur beauté voulant professionnaliser leur activité.',
    ],
    [
        'titre'   => 'Esthéticienne Médicale & Soins Para-médicaux',
        'slug'    => 'estheticienne-medicale-soins-paramedicaux',
        'domaine' => 'Beauté & Bien-être',
        'duree'   => '25H',
        'desc'    => 'Techniques avancées d\'esthétique médicale : soins anti-âge, peeling chimique, épilation laser, mésothérapie esthétique, soins post-opératoires et cicatrisation. Protocoles d\'hygiène et réglementation. Formation pour esthéticiennes souhaitant évoluer vers le para-médical.',
    ],

    /* ── CRÉATION DE CONTENU ── */
    [
        'titre'   => 'Vidéaste & Monteur Vidéo Professionnel',
        'slug'    => 'vidéaste-monteur-video-professionnel',
        'domaine' => 'Création de Contenu',
        'duree'   => '25H',
        'desc'    => 'Maîtriser la chaîne complète de production vidéo : prise de vue (cadrage, lumière, son), montage professionnel avec Premiere Pro et DaVinci Resolve, étalonnage colorimétrique, exports pour les réseaux sociaux et diffusion. Pour créateurs de contenu, agences et entreprises africaines.',
    ],
    [
        'titre'   => 'Podcaster Professionnel & Créateur Audio',
        'slug'    => 'podcaster-professionnel-createur-audio',
        'domaine' => 'Création de Contenu',
        'duree'   => '20H',
        'desc'    => 'Lancer et monétiser son podcast : conception du concept éditorial, enregistrement et montage audio (Audacity, Adobe Audition), distribution sur les plateformes (Spotify, Apple Podcasts), développement de l\'audience et modèles économiques. Formation adaptée au marché africain francophone.',
    ],
    [
        'titre'   => 'Créateur de Cours en Ligne & Ingénierie E-Learning',
        'slug'    => 'createur-cours-en-ligne-ingenierie-elearning',
        'domaine' => 'Création de Contenu',
        'duree'   => '25H',
        'desc'    => 'Concevoir et vendre des formations en ligne : ingénierie pédagogique, outils de création de cours (Articulate, Canva, Loom), plateformes LMS, stratégie de lancement et monétisation. Pour formateurs, consultants et experts souhaitant packager leur savoir en formation digitale.',
    ],

    /* ── INFOGRAPHIE & DESIGN ── */
    [
        'titre'   => 'Brand Designer & Identité Visuelle d\'Entreprise',
        'slug'    => 'brand-designer-identite-visuelle-entreprise',
        'domaine' => 'Infographie & Design',
        'duree'   => '25H',
        'desc'    => 'Créer des identités visuelles percutantes : logo, charte graphique, naming, positionnement de marque, déclinaisons print et digital. Outils : Adobe Illustrator, Figma. Formation orientée marché africain avec études de cas de marques locales réussies. Certificat IBIG EDUFORM.',
    ],
    [
        'titre'   => 'UI/UX Design Mobile & Applications Numériques',
        'slug'    => 'ui-ux-design-mobile-applications-numeriques',
        'domaine' => 'Infographie & Design',
        'duree'   => '25H',
        'desc'    => 'Concevoir des interfaces mobiles ergonomiques et esthétiques : recherche utilisateur, wireframing, prototypage interactif (Figma), tests d\'usabilité, design system. Adapté aux startups, agences digitales et équipes produit souhaitant créer des expériences utilisateur excellentes.',
    ],
    [
        'titre'   => 'Motion Design & Animation Vidéo Professionnelle',
        'slug'    => 'motion-design-animation-video-professionnelle',
        'domaine' => 'Infographie & Design',
        'duree'   => '25H',
        'desc'    => 'Créer des animations vidéo professionnelles pour la communication d\'entreprise et les réseaux sociaux : After Effects, Premiere Pro, animation de logos, explainer videos, infographies animées. Parcours pratique pour graphistes souhaitant maîtriser le mouvement et la narration visuelle.',
    ],

    /* ── IMMOBILIER ── */
    [
        'titre'   => 'Agent Commercial Immobilier Terrain',
        'slug'    => 'agent-commercial-immobilier-terrain',
        'domaine' => 'Immobilier',
        'duree'   => '20H',
        'desc'    => 'Métier de l\'agent immobilier de terrain : prospection foncière, estimation de biens, techniques de vente et négociation immobilière, rédaction de mandats et compromis, droit immobilier pratique OHADA. Formation orientée résultats pour agents commerciaux actifs sur le terrain.',
    ],
    [
        'titre'   => 'Syndic de Copropriété & Gestionnaire d\'Immeuble',
        'slug'    => 'syndic-copropriete-gestionnaire-immeuble',
        'domaine' => 'Immobilier',
        'duree'   => '20H',
        'desc'    => 'Gérer des immeubles en copropriété : règlement de copropriété, assemblées générales, budgets de charges, suivi des travaux et contrats d\'entretien, gestion des conflits entre copropriétaires. Adapté aux gestionnaires d\'immeubles, cabinets immobiliers et promoteurs dans l\'espace OHADA.',
    ],

    /* ── COMPTABILITÉ & FINANCE ── */
    [
        'titre'   => 'Certificat du Caissier de Commerce',
        'slug'    => 'certificat-caissier-commerce',
        'domaine' => 'Comptabilité & Finance',
        'duree'   => '20H',
        'desc'    => 'Formation pratique au métier de caissier en commerce : tenue de caisse et fond de caisse, encaissements (espèces, mobile money, carte bancaire), gestion des écarts, clôture journalière, procédures anti-fraude. Certificat IBIG EDUFORM reconnu dans les 17 pays de l\'espace OHADA.',
    ],

    /* ── GESTION COMMERCIALE & MARKETING ── */
    [
        'titre'   => 'Gestion de Boutique & Commerce de Détail',
        'slug'    => 'gestion-boutique-commerce-de-detail',
        'domaine' => 'Gestion Commerciale & Marketing',
        'duree'   => '20H',
        'desc'    => 'Gérer une boutique ou un point de vente avec professionnalisme : achat et approvisionnement, gestion des stocks et inventaires, merchandising, service client, encaissement et fin de journée, marketing local. Pour gérants de boutiques, supérettes, magasins et commerces de proximité en Afrique.',
    ],

    /* ── AGRICULTURE ── */
    [
        'titre'   => 'Agro-Entrepreneur & Business Agricole en Afrique',
        'slug'    => 'agro-entrepreneur-business-agricole-afrique',
        'domaine' => 'Agriculture',
        'duree'   => '25H',
        'desc'    => 'Créer et développer une entreprise agricole rentable en Afrique : étude de marché agro, business plan agricole, accès au financement (microfinance, fonds agricoles), chaînes de valeur et transformation agro-alimentaire, digitalisation et commercialisation. Pour jeunes entrepreneurs et porteurs de projets agricoles.',
    ],

    /* ── DROIT & JURIDIQUE ── */
    [
        'titre'   => 'Passation des Marchés Publics — Niveau Avancé',
        'slug'    => 'passation-marches-publics-niveau-avance',
        'domaine' => 'Droit & Juridique',
        'duree'   => '30H',
        'desc'    => 'Maîtriser la passation avancée des marchés publics : appels d\'offres complexes, marchés négociés, partenariats public-privé (PPP), recours et contentieux, contrôle a posteriori, pratiques anticorruption et conformité aux directives UEMOA/CEMAC. Pour acheteurs publics confirmés et auditeurs.',
    ],

    /* ── LOGISTIQUE & SUPPLY CHAIN ── */
    [
        'titre'   => 'Responsable d\'Entrepôt & Gestion des Stocks',
        'slug'    => 'responsable-entrepot-gestion-stocks',
        'domaine' => 'Logistique & Supply Chain',
        'duree'   => '20H',
        'desc'    => 'Piloter un entrepôt avec efficacité : organisation physique et zones de stockage, réception et expédition, codification et gestion informatisée des stocks (WMS), inventaires cycliques, indicateurs de performance logistique. Formation pratique pour magasiniers, chefs de dépôt et responsables logistiques terrain.',
    ],
];

$ins = $pdo->prepare("
    INSERT INTO formations
        (titre, slug, domaine, duree, description, tarif_en_ligne, tarif_presentiel, tarif_hybride,
         statut, annee, is_samedi_pro, created_at, updated_at)
    VALUES
        (:titre, :slug, :domaine, :duree, :desc, :tel, :tp, :th,
         'active', 0, 0, NOW(), NOW())
");

$ajoute = 0; $ignore = 0; $log = [];

foreach ($formations as $f) {
    if (slugExists($pdo, $f['slug'])) {
        $log[] = "⏭  EXISTE DÉJÀ : " . $f['titre'];
        $ignore++;
        continue;
    }
    preg_match('/(\d+)/i', $f['duree'], $m);
    $h = isset($m[1]) ? (int)$m[1] : 20;
    $t = tarifs($h);
    $ins->execute([
        ':titre'  => $f['titre'],
        ':slug'   => $f['slug'],
        ':domaine'=> $f['domaine'],
        ':duree'  => $f['duree'],
        ':desc'   => $f['desc'],
        ':tel'    => $t['tel'],
        ':tp'     => $t['tp'],
        ':th'     => $t['th'],
    ]);
    $log[] = sprintf("✅ AJOUTÉ : %-55s %s | %dH | %s FCFA", $f['titre'], $f['domaine'], $h, number_format($t['tel'],0,',',' '));
    $ajoute++;
}

echo "=== AJOUT FORMATIONS PHARES IBIG EDUFORM ===\n\n";
echo "Total à traiter : " . count($formations) . "\n";
echo "Ajoutées        : $ajoute\n";
echo "Ignorées (exist): $ignore\n\n";
echo "=== DÉTAIL ===\n\n";
foreach ($log as $l) echo $l . "\n";
echo "\n✅ Terminé. Lancez le sync Supabase pour propager.\n";
@unlink(__FILE__);
