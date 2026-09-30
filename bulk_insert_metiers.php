<?php
if (($_GET['k'] ?? '') !== 'ibig-metiers-2026') { http_response_code(403); exit('Forbidden'); }
header('Content-Type: text/plain; charset=utf-8');

require_once __DIR__ . '/core/config.php';
require_once __DIR__ . '/core/database.php';
$pdo = Database::connect();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function slugifyM(string $t): string {
    $t = mb_strtolower(trim($t), 'UTF-8');
    $map = [
        'à'=>'a','â'=>'a','ä'=>'a','á'=>'a','ã'=>'a',
        'è'=>'e','é'=>'e','ê'=>'e','ë'=>'e',
        'î'=>'i','ï'=>'i','ô'=>'o','ö'=>'o',
        'û'=>'u','ü'=>'u','ù'=>'u','ú'=>'u',
        'ç'=>'c','ñ'=>'n','œ'=>'oe','æ'=>'ae',
        "'"=>' ',"'"=>' ',
    ];
    $t = strtr($t, $map);
    $t = preg_replace('/[^a-z0-9]+/', '-', $t);
    return trim((string)$t, '-');
}

function desc(string $titre): string {
    return 'Certification professionnelle IBIG EDUFORM — '.$titre.
           '. Programme complet et opérationnel, reconnu dans les 17 pays membres de l\'espace OHADA. '.
           'Délivré à l\'issue d\'une évaluation pratique conforme aux standards internationaux. '.
           'Idéal pour les salariés en poste, les demandeurs d\'emploi et les professionnels en reconversion.';
}

// Format : [titre, domaine, duree_h, tarif_en_ligne, tarif_presentiel]
$formations = [

    // ── FINANCE, COMPTABILITÉ & TRÉSORERIE ────────────────────────────────────
    ['Certificat du Caissier Professionnel',                   'Comptabilité & Finance', 20, 225000, 275000],
    ['Certificat du Trésorier d\'Entreprise',                  'Comptabilité & Finance', 25, 250000, 310000],
    ['Certificat du Comptable d\'Entreprise',                  'Comptabilité & Finance', 30, 250000, 310000],
    ['Certificat du Contrôleur de Gestion',                    'Comptabilité & Finance', 30, 250000, 310000],
    ['Certificat du Gestionnaire de Paie',                     'Comptabilité & Finance', 20, 225000, 275000],
    ['Certificat de l\'Auditeur Interne',                      'Comptabilité & Finance', 30, 280000, 340000],
    ['Certificat du Fiscaliste d\'Entreprise',                 'Comptabilité & Finance', 25, 250000, 310000],
    ['Certificat du Chargé de Recouvrement',                   'Comptabilité & Finance', 20, 225000, 275000],
    ['Certificat du Gestionnaire Budgétaire',                  'Comptabilité & Finance', 25, 225000, 280000],
    ['Certificat du Chargé de Facturation',                    'Comptabilité & Finance', 20, 225000, 275000],

    // ── COMMERCE & VENTE ──────────────────────────────────────────────────────
    ['Certificat du Commercial Terrain',                       'Gestion Commerciale & Marketing', 20, 225000, 275000],
    ['Certificat du Responsable Commercial',                   'Gestion Commerciale & Marketing', 25, 225000, 280000],
    ['Certificat du Chargé de Clientèle',                     'Gestion Commerciale & Marketing', 20, 225000, 275000],
    ['Certificat du Télévendeur Professionnel',                'Gestion Commerciale & Marketing', 20, 225000, 275000],
    ['Certificat du Négociateur Commercial',                   'Gestion Commerciale & Marketing', 25, 225000, 280000],
    ['Certificat du Responsable Grands Comptes',               'Gestion Commerciale & Marketing', 25, 250000, 310000],
    ['Certificat du Chef de Zone Commerciale',                 'Gestion Commerciale & Marketing', 25, 225000, 280000],
    ['Certificat de l\'Agent Commercial Indépendant',          'Gestion Commerciale & Marketing', 20, 225000, 275000],
    ['Certificat du Chargé d\'Appels d\'Offres',              'Gestion Commerciale & Marketing', 20, 225000, 275000],
    ['Certificat du Merchandiser Professionnel',               'Gestion Commerciale & Marketing', 20, 225000, 275000],

    // ── LOGISTIQUE & SUPPLY CHAIN ─────────────────────────────────────────────
    ['Certificat du Logisticien d\'Entreprise',                'Logistique & Supply Chain', 25, 225000, 280000],
    ['Certificat du Magasinier Gestionnaire',                  'Logistique & Supply Chain', 20, 225000, 275000],
    ['Certificat du Gestionnaire des Stocks',                  'Logistique & Supply Chain', 20, 225000, 275000],
    ['Certificat du Responsable des Achats',                   'Logistique & Supply Chain', 25, 250000, 310000],
    ['Certificat du Dispatcher Transport',                     'Logistique & Supply Chain', 20, 225000, 275000],
    ['Certificat de l\'Agent de Transit Douanier',             'Logistique & Supply Chain', 25, 225000, 280000],
    ['Certificat du Responsable Entrepôt',                     'Logistique & Supply Chain', 20, 225000, 275000],
    ['Certificat du Chargé de Planification Logistique',       'Logistique & Supply Chain', 20, 225000, 275000],
    ['Certificat du Chargé Import-Export',                     'Logistique & Supply Chain', 25, 225000, 280000],
    ['Certificat du Technicien en Supply Chain',               'Logistique & Supply Chain', 25, 225000, 280000],

    // ── RESSOURCES HUMAINES ───────────────────────────────────────────────────
    ['Certificat du Gestionnaire RH',                          'GRH', 25, 225000, 280000],
    ['Certificat du Chargé de Recrutement',                    'GRH', 20, 225000, 275000],
    ['Certificat du Chargé de Formation en Entreprise',        'GRH', 20, 225000, 275000],
    ['Certificat de l\'Assistant RH',                          'GRH', 20, 225000, 275000],
    ['Certificat du Responsable Paie et Administration RH',    'GRH', 25, 250000, 310000],
    ['Certificat du Chargé des Relations Sociales',            'GRH', 20, 225000, 275000],
    ['Certificat du Responsable GPEC',                         'GRH', 25, 250000, 310000],
    ['Certificat du Chargé du Bien-être au Travail',           'GRH', 20, 225000, 275000],

    // ── ADMINISTRATION & DIRECTION ────────────────────────────────────────────
    ['Certificat de l\'Assistante de Direction',               'Direction & Administration', 20, 225000, 275000],
    ['Certificat du Secrétaire Professionnel',                 'Direction & Administration', 20, 225000, 275000],
    ['Certificat de l\'Office Manager',                        'Direction & Administration', 25, 225000, 280000],
    ['Certificat de l\'Archiviste Documentaliste',             'Direction & Administration', 20, 225000, 275000],
    ['Certificat du Gestionnaire de Planning',                 'Direction & Administration', 20, 225000, 275000],
    ['Certificat du Responsable Administratif',                'Direction & Administration', 25, 225000, 280000],
    ['Certificat du Chargé des Marchés Publics',               'Direction & Administration', 25, 250000, 310000],
    ['Certificat du Juriste d\'Entreprise',                    'Droit & Juridique', 25, 250000, 310000],

    // ── MARKETING & COMMUNICATION ─────────────────────────────────────────────
    ['Certificat du Community Manager Professionnel',          'Gestion Commerciale & Marketing', 20, 225000, 275000],
    ['Certificat du Chargé de Communication d\'Entreprise',   'Communication Professionnelle', 20, 225000, 275000],
    ['Certificat du Responsable Marketing Digital',            'Gestion Commerciale & Marketing', 25, 225000, 280000],
    ['Certificat du Graphiste Professionnel',                  'Infographie & Design', 25, 225000, 280000],
    ['Certificat du Rédacteur Web Professionnel',              'Création de Contenu', 20, 225000, 275000],
    ['Certificat du Chargé de Relations Presse',               'Communication Professionnelle', 20, 225000, 275000],
    ['Certificat du Responsable de la Marque',                 'Gestion Commerciale & Marketing', 25, 225000, 280000],
    ['Certificat du Chargé de Publicité Digitale',             'Gestion Commerciale & Marketing', 20, 225000, 275000],

    // ── BTP & TECHNIQUE ───────────────────────────────────────────────────────
    ['Certificat du Chef de Chantier BTP',                     'BTP & Construction', 30, 250000, 310000],
    ['Certificat du Conducteur de Travaux',                    'BTP & Construction', 30, 250000, 310000],
    ['Certificat du Métreur-Vérificateur',                     'BTP & Construction', 25, 225000, 280000],
    ['Certificat du Dessinateur en BTP',                       'BTP & Construction', 25, 225000, 280000],
    ['Certificat du Technicien en Électricité Bâtiment',       'BTP & Construction', 25, 225000, 280000],
    ['Certificat du Technicien en Plomberie Sanitaire',        'BTP & Construction', 25, 225000, 280000],
    ['Certificat du Technicien Froid Climatisation',           'BTP & Construction', 25, 225000, 280000],
    ['Certificat du Conducteur d\'Engins de Chantier',         'BTP & Construction', 25, 225000, 280000],

    // ── BANQUE & ASSURANCE ────────────────────────────────────────────────────
    ['Certificat du Chargé de Clientèle Bancaire',             'Banque & Assurance', 20, 225000, 275000],
    ['Certificat du Caissier Bancaire Professionnel',          'Banque & Assurance', 20, 225000, 275000],
    ['Certificat de l\'Agent de Crédit',                       'Banque & Assurance', 25, 250000, 310000],
    ['Certificat du Chargé de Conformité Bancaire',            'Banque & Assurance', 25, 250000, 310000],
    ['Certificat du Conseiller en Assurance Vie',              'Banque & Assurance', 20, 225000, 275000],
    ['Certificat du Gestionnaire de Sinistres',                'Banque & Assurance', 20, 225000, 275000],
    ['Certificat du Chargé de Microfinance',                   'Banque & Assurance', 20, 225000, 275000],
    ['Certificat du Gestionnaire de Portefeuille Client',      'Banque & Assurance', 25, 250000, 310000],

    // ── INFORMATIQUE & DIGITAL ────────────────────────────────────────────────
    ['Certificat du Technicien Informatique',                  'IA & Digitalisation', 25, 225000, 280000],
    ['Certificat de l\'Administrateur Réseau',                 'IA & Digitalisation', 30, 250000, 310000],
    ['Certificat du Développeur Web Junior',                   'IA & Digitalisation', 30, 225000, 280000],
    ['Certificat du Technicien Support IT',                    'IA & Digitalisation', 20, 225000, 275000],
    ['Certificat du Gestionnaire de Base de Données',          'IA & Digitalisation', 25, 250000, 310000],
    ['Certificat du Webmaster Professionnel',                  'IA & Digitalisation', 25, 225000, 280000],
    ['Certificat du Responsable Cybersécurité',                'IA & Digitalisation', 30, 280000, 340000],

    // ── HÔTELLERIE & TOURISME ─────────────────────────────────────────────────
    ['Certificat du Réceptionniste Hôtelier',                  'Tourisme & Hôtellerie', 20, 225000, 275000],
    ['Certificat de la Gouvernante d\'Hôtel',                  'Tourisme & Hôtellerie', 20, 225000, 275000],
    ['Certificat du Serveur Restauration Professionnelle',     'Tourisme & Hôtellerie', 20, 225000, 275000],
    ['Certificat du Barman-Sommelier',                         'Tourisme & Hôtellerie', 20, 225000, 275000],
    ['Certificat du Chef de Rang',                             'Tourisme & Hôtellerie', 20, 225000, 275000],
    ['Certificat du Responsable Hébergement Hôtelier',         'Tourisme & Hôtellerie', 25, 225000, 280000],
    ['Certificat du Guide Touristique Professionnel',          'Tourisme & Hôtellerie', 20, 225000, 275000],
    ['Certificat du Responsable de Salle de Restaurant',       'Tourisme & Hôtellerie', 20, 225000, 275000],

    // ── AGRICULTURE & AGROALIMENTAIRE ─────────────────────────────────────────
    ['Certificat du Technicien Agricole',                      'Agriculture', 25, 225000, 275000],
    ['Certificat du Gérant d\'Exploitation Agricole',          'Agriculture', 25, 225000, 280000],
    ['Certificat de l\'Agro-Dealer',                           'Agriculture', 20, 225000, 275000],
    ['Certificat du Technicien en Élevage',                    'Agriculture', 20, 225000, 275000],
    ['Certificat du Gestionnaire de Coopérative Agricole',     'Agriculture', 25, 225000, 280000],
    ['Certificat du Technicien Agroalimentaire',               'Agriculture', 25, 225000, 280000],

    // ── SANTÉ & SOCIAL ────────────────────────────────────────────────────────
    ['Certificat de l\'Aide-Soignant Professionnel',           'Santé & Pharmacie', 25, 225000, 275000],
    ['Certificat de l\'Agent de Santé Communautaire',          'Santé & Pharmacie', 20, 225000, 275000],
    ['Certificat du Responsable de Pharmacie',                 'Santé & Pharmacie', 25, 250000, 310000],
    ['Certificat de l\'Assistant Médical',                     'Santé & Pharmacie', 20, 225000, 275000],
    ['Certificat du Technicien de Laboratoire Médical',        'Santé & Pharmacie', 25, 250000, 310000],
    ['Certificat du Responsable Hygiène Hospitalière',         'Santé & Pharmacie', 20, 225000, 275000],

    // ── SÉCURITÉ & QHSE ───────────────────────────────────────────────────────
    ['Certificat du Chef Agent de Sécurité',                   'QHSE', 20, 225000, 275000],
    ['Certificat du Superviseur Sécurité Entreprise',          'QHSE', 20, 225000, 275000],
    ['Certificat du Chargé QHSE',                              'QHSE', 25, 250000, 310000],
    ['Certificat du Responsable Hygiène et Sécurité',          'QHSE', 25, 250000, 310000],
    ['Certificat du Responsable Environnement',                'QHSE', 25, 250000, 310000],

    // ── TRANSPORT & MOBILITÉ ──────────────────────────────────────────────────
    ['Certificat du Responsable Transport',                    'Logistique & Supply Chain', 20, 225000, 275000],
    ['Certificat du Gestionnaire de Flotte Automobile',        'Logistique & Supply Chain', 20, 225000, 275000],
    ['Certificat du Coordinateur Mobilité Urbaine',            'Logistique & Supply Chain', 20, 225000, 275000],

    // ── ÉDUCATION & FORMATION ─────────────────────────────────────────────────
    ['Certificat du Formateur Professionnel d\'Adultes',       'Éducation & Formation', 25, 225000, 280000],
    ['Certificat du Responsable Pédagogique',                  'Éducation & Formation', 25, 225000, 280000],
    ['Certificat du Conseiller en Orientation Scolaire',       'Éducation & Formation', 20, 225000, 275000],
    ['Certificat du Directeur d\'Établissement Scolaire',      'Éducation & Formation', 30, 250000, 310000],

    // ── MINES, ÉNERGIE & PÉTROLE ──────────────────────────────────────────────
    ['Certificat du Technicien Minier',                        'Mines, Énergie & Pétrole', 30, 250000, 310000],
    ['Certificat du Technicien en Énergies Renouvelables',     'Mines, Énergie & Pétrole', 25, 250000, 310000],
    ['Certificat du Technicien en Maintenance Industrielle',   'Mines, Énergie & Pétrole', 25, 250000, 310000],

    // ── ENTREPRENEURIAT ───────────────────────────────────────────────────────
    ['Certificat du Entrepreneur Débutant',                    'Entrepreneuriat', 20, 225000, 275000],
    ['Certificat du Gérant de PME',                            'Entrepreneuriat', 25, 225000, 280000],
    ['Certificat du Responsable d\'Auto-Entreprise',           'Entrepreneuriat', 20, 225000, 275000],

    // ── DÉVELOPPEMENT PERSONNEL & LEADERSHIP ─────────────────────────────────
    ['Certificat du Manager de Première Ligne',                'Management & Leadership', 20, 225000, 275000],
    ['Certificat du Superviseur d\'Équipe',                    'Management & Leadership', 20, 225000, 275000],
    ['Certificat du Chef de Projet Junior',                    'Management & Leadership', 25, 225000, 280000],
    ['Certificat du Responsable de Service',                   'Management & Leadership', 25, 225000, 280000],
];

// ── ANTI-DOUBLONS ──────────────────────────────────────────────────────────
$existingTitres = array_flip(
    $pdo->query("SELECT LOWER(TRIM(titre)) FROM formations")->fetchAll(PDO::FETCH_COLUMN)
);
$existingSlugs = array_flip(
    $pdo->query("SELECT slug FROM formations")->fetchAll(PDO::FETCH_COLUMN)
);

$ins = $pdo->prepare("
    INSERT INTO formations
        (titre, slug, domaine, type_certificat, description, duree,
         mode, tarif_presentiel, tarif_en_ligne, tarif_hybride,
         frais_inscription, annee, statut, created_at, updated_at)
    VALUES
        (:titre, :slug, :domaine, 'Certificat professionnel', :description, :duree,
         'hybride', :tp, :tel, :th,
         50000, 0, 'active', NOW(), NOW())
");

$inserted = 0; $skipped = 0; $skippedList = [];

foreach ($formations as $f) {
    [$titre, $domaine, $duree, $tel, $tp] = $f;
    $key = strtolower(trim($titre));

    if (isset($existingTitres[$key])) {
        $skipped++;
        $skippedList[] = $titre;
        continue;
    }

    $slug = slugifyM($titre);
    $base = $slug; $i = 1;
    while (isset($existingSlugs[$slug])) { $slug = $base . '-' . $i++; }

    $ins->execute([
        ':titre'       => $titre,
        ':slug'        => $slug,
        ':domaine'     => $domaine,
        ':description' => desc($titre),
        ':duree'       => $duree . 'h',
        ':tel'         => $tel,
        ':tp'          => $tp,
        ':th'          => (int)(($tel + $tp) / 2),
    ]);

    $existingTitres[$key] = true;
    $existingSlugs[$slug]  = true;
    $inserted++;
}

$totalCat = (int)$pdo->query(
    "SELECT COUNT(*) FROM formations WHERE statut='active' AND (annee IS NULL OR annee=0)"
)->fetchColumn();
$totalAll = (int)$pdo->query(
    "SELECT COUNT(*) FROM formations WHERE statut='active'"
)->fetchColumn();

// Récap par domaine
$byDomain = [];
foreach ($formations as $f) { $byDomain[$f[1]] = ($byDomain[$f[1]] ?? 0) + 1; }
ksort($byDomain);

echo "╔══════════════════════════════════════════════╗\n";
echo "║   BULK INSERT — CERTIFICATS MÉTIER IBIG      ║\n";
echo "╚══════════════════════════════════════════════╝\n\n";
echo "Dans la liste    : ".count($formations)."\n";
echo "✅ Insérées      : $inserted\n";
echo "⏭  Doublons igno : $skipped\n\n";
echo "── Par domaine ──────────────────────────────\n";
foreach ($byDomain as $dom => $n) { printf("  %-40s %d\n", $dom, $n); }
echo "\n── Totaux MySQL ─────────────────────────────\n";
echo "Catalogue (annee=0) : $totalCat formations\n";
echo "Total MySQL actif   : $totalAll formations\n";
echo "\n── Catalogue public attendu ─────────────────\n";
echo "API(965) + local($totalAll) = ".(965 + $totalAll)." formations\n";

if ($skipped) {
    echo "\n── Doublons ignorés ─────────────────────────\n";
    foreach ($skippedList as $t) { echo "  ⏭  $t\n"; }
}

echo "\n✅ Script auto-supprimé.\n";
@unlink(__FILE__);
