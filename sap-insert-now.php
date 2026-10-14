<?php
declare(strict_types=1);
/**
 * Script one-shot : insertion des 17 formations SAP dans la DB.
 * Protégé par clé secrète. À supprimer après usage.
 */

if (($_GET['key'] ?? '') !== 'IBIG-SAP-2026-OK') {
    http_response_code(403);
    exit('<p style="font-family:sans-serif;color:#dc2626;text-align:center;margin-top:60px">Accès refusé.</p>');
}

require_once __DIR__ . '/core/bootstrap.php';

$pdo = Database::connect();

$formations = [
    ['titre'=>'SAP Initiation – Découverte de SAP','slug'=>'sap-initiation-decouverte','domaine'=>'ERP & SAP','description'=>'Découvrez l\'univers SAP : navigation, modules principaux, logique ERP. Idéal pour toute personne souhaitant comprendre SAP avant une spécialisation.','objectifs'=>'Naviguer dans SAP GUI · Comprendre la logique ERP · Identifier les modules SAP · Réaliser des transactions de base','duree'=>'10H','mode'=>'en_ligne,presentiel,hybride','tarif_en_ligne'=>140000,'tarif_hybride'=>155000,'tarif_presentiel'=>170000],
    ['titre'=>'SAP FI – Comptabilité financière (Utilisateur)','slug'=>'sap-fi-comptabilite-financiere','domaine'=>'ERP & SAP','description'=>'Maîtrisez les opérations comptables dans SAP FI : saisie de factures, rapprochements bancaires, états financiers. Formation orientée utilisateurs métier.','objectifs'=>'Saisir des pièces comptables · Gérer les comptes fournisseurs et clients · Effectuer des rapprochements · Éditer les états financiers SAP','duree'=>'20H','mode'=>'en_ligne,presentiel,hybride','tarif_en_ligne'=>375000,'tarif_hybride'=>405000,'tarif_presentiel'=>435000],
    ['titre'=>'SAP FI – Consultant fonctionnel','slug'=>'sap-fi-consultant-fonctionnel','domaine'=>'ERP & SAP','description'=>'Paramétrez et déployez le module SAP FI en entreprise. Couvre la configuration du plan comptable, des sociétés, des conditions de paiement et des états de reporting.','objectifs'=>'Configurer SAP FI · Paramétrer les sociétés et plans comptables · Gérer les intégrations FI-CO · Préparer et animer des sessions de formation utilisateurs','duree'=>'40H','mode'=>'en_ligne,presentiel,hybride','tarif_en_ligne'=>655000,'tarif_hybride'=>715000,'tarif_presentiel'=>775000],
    ['titre'=>'SAP CO – Contrôle de gestion (Utilisateur)','slug'=>'sap-co-controle-de-gestion','domaine'=>'ERP & SAP','description'=>'Utilisez SAP CO pour le suivi des coûts, des centres de profit et l\'analyse de rentabilité. Formation pratique pour contrôleurs de gestion.','objectifs'=>'Créer et suivre des ordres internes · Analyser les centres de coûts · Paramétrer des allocations · Générer des rapports CO','duree'=>'16H','mode'=>'en_ligne,presentiel,hybride','tarif_en_ligne'=>320000,'tarif_hybride'=>345000,'tarif_presentiel'=>365000],
    ['titre'=>'SAP CO – Consultant fonctionnel','slug'=>'sap-co-consultant-fonctionnel','domaine'=>'ERP & SAP','description'=>'Configurez SAP CO (Controlling) pour un déploiement en entreprise : centres de coûts, ordres, CO-PA et intégration avec FI.','objectifs'=>'Paramétrer SAP CO · Configurer le CO-PA · Intégrer CO et FI · Accompagner les utilisateurs métier','duree'=>'25H','mode'=>'en_ligne,presentiel,hybride','tarif_en_ligne'=>445000,'tarif_hybride'=>480000,'tarif_presentiel'=>520000],
    ['titre'=>'SAP MM – Achats et gestion des stocks (Utilisateur)','slug'=>'sap-mm-achats-gestion-stocks','domaine'=>'ERP & SAP','description'=>'Gérez les achats, réceptions et stocks dans SAP MM. Couvre le cycle complet procure-to-pay : demande d\'achat, bon de commande, réception, facture.','objectifs'=>'Créer des demandes d\'achat et bons de commande · Gérer les réceptions de marchandises · Suivre les stocks · Effectuer des inventaires','duree'=>'16H','mode'=>'en_ligne,presentiel,hybride','tarif_en_ligne'=>320000,'tarif_hybride'=>345000,'tarif_presentiel'=>365000],
    ['titre'=>'SAP MM – Consultant fonctionnel','slug'=>'sap-mm-consultant-fonctionnel','domaine'=>'ERP & SAP','description'=>'Paramétrez et déployez SAP MM en entreprise : configuration des types de matériaux, sources d\'approvisionnement, stratégies de valorisation des stocks.','objectifs'=>'Configurer le module MM · Paramétrer les flux d\'approvisionnement · Intégrer MM avec FI et SD · Former les utilisateurs','duree'=>'35H','mode'=>'en_ligne,presentiel,hybride','tarif_en_ligne'=>585000,'tarif_hybride'=>640000,'tarif_presentiel'=>690000],
    ['titre'=>'SAP SD – Ventes et distribution (Utilisateur)','slug'=>'sap-sd-ventes-distribution','domaine'=>'ERP & SAP','description'=>'Maîtrisez le cycle order-to-cash dans SAP SD : devis, commandes clients, livraisons et facturation. Formation pour équipes commerciales et ADV.','objectifs'=>'Créer et gérer des commandes clients · Gérer les livraisons · Émettre des factures · Analyser les performances commerciales','duree'=>'16H','mode'=>'en_ligne,presentiel,hybride','tarif_en_ligne'=>320000,'tarif_hybride'=>345000,'tarif_presentiel'=>365000],
    ['titre'=>'SAP SD – Consultant fonctionnel','slug'=>'sap-sd-consultant-fonctionnel','domaine'=>'ERP & SAP','description'=>'Configurez SAP SD pour un déploiement complet : organisation commerciale, conditions tarifaires, expédition et intégration avec MM et FI.','objectifs'=>'Paramétrer l\'organisation commerciale · Configurer les conditions de prix · Gérer les flux de livraison · Intégrer SD-MM-FI','duree'=>'30H','mode'=>'en_ligne,presentiel,hybride','tarif_en_ligne'=>515000,'tarif_hybride'=>560000,'tarif_presentiel'=>605000],
    ['titre'=>'SAP HCM – Ressources humaines et paie (Utilisateur)','slug'=>'sap-hcm-ressources-humaines-paie','domaine'=>'ERP & SAP','description'=>'Gérez les données RH et la paie dans SAP HCM : création de collaborateurs, gestion du temps, calcul des salaires et reporting RH.','objectifs'=>'Créer et maintenir les données employés · Gérer les absences et présences · Lancer les calculs de paie · Éditer les bulletins de salaire','duree'=>'14H','mode'=>'en_ligne,presentiel,hybride','tarif_en_ligne'=>290000,'tarif_hybride'=>310000,'tarif_presentiel'=>335000],
    ['titre'=>'SAP HCM – Consultant fonctionnel','slug'=>'sap-hcm-consultant-fonctionnel','domaine'=>'ERP & SAP','description'=>'Configurez et déployez SAP HCM : structure organisationnelle, schémas de paie, gestion du temps et reporting RH avancé.','objectifs'=>'Paramétrer la structure organisationnelle · Configurer les schémas de paie · Gérer les règles de temps · Préparer les formations utilisateurs','duree'=>'30H','mode'=>'en_ligne,presentiel,hybride','tarif_en_ligne'=>515000,'tarif_hybride'=>560000,'tarif_presentiel'=>605000],
    ['titre'=>'SAP Projet pratique et évaluation','slug'=>'sap-projet-pratique-evaluation','domaine'=>'ERP & SAP','description'=>'Mettez en pratique vos connaissances SAP sur un cas réel d\'entreprise couvrant plusieurs modules. Évaluation finale et remise d\'attestation.','objectifs'=>'Simuler un projet SAP complet · Intégrer plusieurs modules · Documenter et présenter les résultats · Valider les compétences acquises','duree'=>'6H','mode'=>'en_ligne,presentiel,hybride','tarif_en_ligne'=>85000,'tarif_hybride'=>95000,'tarif_presentiel'=>100000],
    ['titre'=>'Pack SAP Comptabilité Pro (FI + CO Utilisateur)','slug'=>'pack-sap-comptabilite-pro','domaine'=>'ERP & SAP','description'=>'Pack complet pour les professionnels de la comptabilité et du contrôle de gestion : maîtrisez SAP FI et CO en une formation structurée et progressive.','objectifs'=>'Maîtriser SAP FI et CO · Réaliser des clôtures comptables · Effectuer des analyses de gestion · Produire des reportings financiers','duree'=>'42H','mode'=>'en_ligne,presentiel,hybride','tarif_en_ligne'=>625000,'tarif_hybride'=>680000,'tarif_presentiel'=>740000],
    ['titre'=>'Pack SAP Consultant Finance (FI + CO Consultant)','slug'=>'pack-sap-consultant-finance','domaine'=>'ERP & SAP','description'=>'Devenez consultant SAP Finance : paramétrage complet de FI et CO, intégrations, gestion de projet et accompagnement utilisateurs.','objectifs'=>'Configurer FI et CO · Gérer les intégrations · Piloter un projet SAP Finance · Animer des formations utilisateurs','duree'=>'71H','mode'=>'en_ligne,presentiel,hybride','tarif_en_ligne'=>990000,'tarif_hybride'=>1085000,'tarif_presentiel'=>1180000],
    ['titre'=>'Pack SAP Utilisateur Logistique (MM + SD)','slug'=>'pack-sap-utilisateur-logistique','domaine'=>'ERP & SAP','description'=>'Pack pour les opérationnels de la supply chain : gérez les achats, stocks et ventes dans SAP MM et SD en une formation complète.','objectifs'=>'Maîtriser les flux d\'achat et de vente · Gérer les stocks · Assurer le suivi des commandes · Utiliser les rapports logistiques','duree'=>'38H','mode'=>'en_ligne,presentiel,hybride','tarif_en_ligne'=>575000,'tarif_hybride'=>625000,'tarif_presentiel'=>675000],
    ['titre'=>'Pack SAP Consultant Logistique (MM + SD Consultant)','slug'=>'pack-sap-consultant-logistique','domaine'=>'ERP & SAP','description'=>'Devenez consultant SAP Logistique : configurez MM et SD, gérez les intégrations et accompagnez les équipes dans leur transformation digitale.','objectifs'=>'Configurer MM et SD · Intégrer les flux logistiques · Gérer un projet SAP Logistique · Former et accompagner les équipes','duree'=>'71H','mode'=>'en_ligne,presentiel,hybride','tarif_en_ligne'=>990000,'tarif_hybride'=>1085000,'tarif_presentiel'=>1180000],
    ['titre'=>'Pack SAP RH – Parcours complet (HCM Utilisateur + Consultant)','slug'=>'pack-sap-rh-parcours-complet','domaine'=>'ERP & SAP','description'=>'Maîtrisez SAP HCM de A à Z : utilisation quotidienne et paramétrage consultant. Le parcours le plus complet pour les professionnels RH.','objectifs'=>'Gérer les données RH et la paie · Configurer HCM · Déployer des projets RH · Former et accompagner les utilisateurs','duree'=>'50H','mode'=>'en_ligne,presentiel,hybride','tarif_en_ligne'=>725000,'tarif_hybride'=>790000,'tarif_presentiel'=>860000],
];

$inseres = 0;
$ignores = 0;
$erreurs = [];

$chk = $pdo->prepare("SELECT id FROM formations WHERE slug = ? LIMIT 1");
$ins = $pdo->prepare("
    INSERT INTO formations
        (titre, slug, domaine, description, objectifs, duree, mode,
         tarif_en_ligne, tarif_hybride, tarif_presentiel,
         date_debut, date_fin, mois, annee, statut, created_at, updated_at)
    VALUES (?,?,?,?,?,?,?,?,?,?,'0000-00-00','0000-00-00','',0,'active',NOW(),NOW())
");

foreach ($formations as $f) {
    $chk->execute([$f['slug']]);
    if ($chk->fetchColumn()) { $ignores++; continue; }
    try {
        $ins->execute([
            $f['titre'], $f['slug'], $f['domaine'], $f['description'],
            $f['objectifs'], $f['duree'], $f['mode'],
            $f['tarif_en_ligne'], $f['tarif_hybride'], $f['tarif_presentiel'],
        ]);
        $inseres++;
    } catch (Throwable $e) {
        $erreurs[] = $f['slug'] . ' → ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fr"><head><meta charset="UTF-8">
<title>SAP Insert</title>
<style>body{font-family:Arial,sans-serif;max-width:700px;margin:40px auto;padding:20px}
h1{color:#0a1733}ul{margin:0;padding-left:18px}
.ok{color:#166534;background:#dcfce7;padding:14px;border-radius:8px;margin:10px 0}
.skip{color:#92400e;background:#fef3c7;padding:14px;border-radius:8px;margin:10px 0}
.err{color:#991b1b;background:#fee2e2;padding:14px;border-radius:8px;margin:10px 0}</style></head>
<body>
<h1>SAP Formations — Rapport d'insertion</h1>
<div class="ok"><strong>✅ Insérées :</strong> <?= (int)$inseres ?></div>
<div class="skip"><strong>⏭ Ignorées (déjà présentes) :</strong> <?= (int)$ignores ?></div>
<?php if ($erreurs): ?>
<div class="err"><strong>❌ Erreurs :</strong><ul><?php foreach($erreurs as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>
<p style="color:#6b7280;font-size:13px;margin-top:30px">Supprimer ce fichier après usage.</p>
</body></html>
