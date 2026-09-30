<?php
declare(strict_types=1);
require_once __DIR__ . '/core/secrets.php';
$pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
header('Content-Type: text/plain; charset=utf-8');

$f = [
    'code'             => 'LOG-GST-25',
    'titre'            => 'Gestion des Stocks',
    'slug'             => 'gestion-des-stocks',
    'domaine'          => 'Logistique & Supply Chain',
    'description'      => 'Maîtrisez les fondamentaux et les méthodes avancées de gestion des stocks : optimisation des niveaux de stock, réduction des ruptures et des surstocks, indicateurs de performance (taux de rotation, taux de service), méthodes ABC/XYZ, gestion des approvisionnements et outils numériques de pilotage des stocks.',
    'objectifs'        => "- Maîtriser les concepts fondamentaux de la gestion des stocks\n- Appliquer les méthodes de classification ABC et XYZ\n- Calculer et optimiser les niveaux de stock de sécurité\n- Réduire les coûts de stockage tout en évitant les ruptures\n- Utiliser les indicateurs clés de performance (KPI) stocks\n- Mettre en place un système de gestion des approvisionnements efficace",
    'public_cible'     => 'Responsables logistiques, gestionnaires de stocks, acheteurs, supply chain managers, assistants logistique souhaitant progresser',
    'prerequis'        => 'Notions de base en logistique ou gestion d\'entreprise',
    'modules'          => "Module 1 : Fondamentaux de la gestion des stocks\nModule 2 : Classification ABC / XYZ\nModule 3 : Calcul des stocks de sécurité et point de commande\nModule 4 : Méthodes de réapprovisionnement\nModule 5 : Coûts de stockage et optimisation\nModule 6 : KPI et tableaux de bord stocks",
    'duree'            => '25H',
    'mode'             => 'presentiel',
    'type_formation'   => 'certifiante',
    'tarif_en_ligne'   => 250000,
    'tarif_presentiel' => 300000,
    'tarif_hybride'    => 275000,
    'type_certificat'  => 'Attestation IBIG EDUFORM reconnue OHADA',
    'statut'           => 'active',
    'annee'            => 0,
    'mois'             => 0,
];

// Vérifier si déjà présent
$exists = $pdo->prepare("SELECT id FROM formations WHERE slug = ?");
$exists->execute([$f['slug']]);
if ($exists->fetch()) {
    echo "DÉJÀ PRÉSENT : " . $f['slug'] . "\n";
} else {
    $sql = "INSERT INTO formations
        (code,titre,slug,domaine,description,objectifs,public_cible,prerequis,modules,
         duree,mode,type_formation,tarif_en_ligne,tarif_presentiel,tarif_hybride,
         type_certificat,statut,annee,mois)
        VALUES
        (:code,:titre,:slug,:domaine,:description,:objectifs,:public_cible,:prerequis,:modules,
         :duree,:mode,:type_formation,:tarif_en_ligne,:tarif_presentiel,:tarif_hybride,
         :type_certificat,:statut,:annee,:mois)";
    $pdo->prepare($sql)->execute($f);
    echo "INSÉRÉ : " . $f['titre'] . " — " . $f['duree'] . "\n";
    echo "Tarif en ligne  : " . number_format($f['tarif_en_ligne'], 0, ',', ' ') . " F CFA\n";
    echo "Tarif présentiel: " . number_format($f['tarif_presentiel'], 0, ',', ' ') . " F CFA\n";
}

$total = $pdo->query("SELECT COUNT(*) FROM formations")->fetchColumn();
echo "\nTotal DB : $total formations\nSUPPRIMEZ CE FICHIER.\n";
