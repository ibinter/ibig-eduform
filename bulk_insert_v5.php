<?php
if (($_GET['k'] ?? '') !== 'ibig-bulk5-2026') { http_response_code(403); exit('Forbidden'); }
header('Content-Type: text/plain; charset=utf-8');

require_once __DIR__ . '/core/config.php';
require_once __DIR__ . '/core/database.php';
$pdo = Database::connect();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function slugify5(string $t): string {
    $t = mb_strtolower(trim($t), 'UTF-8');
    $map = ['à'=>'a','â'=>'a','ä'=>'a','á'=>'a','ã'=>'a','è'=>'e','é'=>'e','ê'=>'e','ë'=>'e',
            'î'=>'i','ï'=>'i','ô'=>'o','ö'=>'o','û'=>'u','ü'=>'u','ù'=>'u','ú'=>'u',
            'ç'=>'c','ñ'=>'n','œ'=>'oe','æ'=>'ae'];
    $t = strtr($t, $map);
    $t = preg_replace('/[^a-z0-9]+/', '-', $t);
    return trim((string)$t, '-');
}

// 35 formations supplémentaires — domaines variés
$formations = [
    // Management
    ['Gestion des organisations à but non lucratif','Management & Leadership',20,225000,275000],
    ['Management interculturel en Afrique subsaharienne','Management & Leadership',20,225000,275000],
    ['Lean management et amélioration continue','Management & Leadership',25,225000,280000],
    ['Management par objectifs et OKR','Management & Leadership',20,225000,275000],
    ['Conduite du changement organisationnel','Management & Leadership',25,225000,280000],

    // Marketing & Commercial
    ['Marketing de contenu B2B et inbound','Gestion Commerciale & Marketing',20,225000,275000],
    ['Stratégie omnicanale et expérience client','Gestion Commerciale & Marketing',20,225000,275000],
    ['Marketing automation et CRM avancé','Gestion Commerciale & Marketing',25,225000,280000],
    ['Développement commercial en zone OHADA','Gestion Commerciale & Marketing',20,225000,275000],
    ['Négociation et vente en environnement B2G','Gestion Commerciale & Marketing',20,225000,275000],

    // Finance & Compta
    ['Gestion financière des projets de développement','Comptabilité & Finance',25,225000,280000],
    ['Analyse des états financiers consolidés','Comptabilité & Finance',25,250000,310000],
    ['Financement islamique et sukuk','Comptabilité & Finance',20,225000,275000],
    ['Gestion de portefeuille et actifs financiers','Comptabilité & Finance',25,250000,310000],
    ['Fiscalité des entreprises en zone OHADA','Comptabilité & Finance',20,225000,275000],

    // GRH
    ['Digitalisation des processus RH','GRH',20,225000,275000],
    ['Gestion prévisionnelle des emplois GPEC','GRH',25,225000,280000],
    ['Diversité inclusion et équité en entreprise','GRH',20,225000,275000],
    ['Gestion des expatriés et mobilité internationale','GRH',20,225000,275000],
    ['Bien-être au travail et QVT','GRH',20,225000,275000],

    // IA & Digital
    ['Machine learning appliqué aux données métier','IA & Digitalisation',30,250000,310000],
    ['Développement d\'applications mobiles no-code','IA & Digitalisation',20,225000,275000],
    ['Gestion de données et data governance','IA & Digitalisation',25,225000,280000],
    ['Transformation digitale des PME','IA & Digitalisation',20,225000,275000],
    ['SEO technique et audit de site web','IA & Digitalisation',20,225000,275000],

    // Droit
    ['Droit des sociétés OHADA et pratique','Droit & Juridique',25,250000,310000],
    ['Contentieux commercial et arbitrage','Droit & Juridique',20,250000,310000],
    ['Protection des données RGPD en Afrique','Droit & Juridique',20,225000,275000],

    // Logistique
    ['Gestion des achats et appels d\'offres','Logistique & Supply Chain',20,225000,275000],
    ['Logistique e-commerce et last mile delivery','Logistique & Supply Chain',20,225000,275000],

    // QHSE
    ['Audit interne ISO 9001 et management qualité','QHSE',25,250000,310000],
    ['Responsabilité sociétale des entreprises RSE','QHSE',20,225000,275000],

    // Agriculture
    ['Agroécologie et permaculture tropicale','Agriculture',20,225000,275000],

    // Éducation
    ['Création et gestion d\'un centre de formation','Éducation & Formation',25,225000,280000],

    // Entrepreneuriat
    ['Levée de fonds et financement des startups','Entrepreneuriat',20,225000,275000],
];

$existingTitres = array_flip($pdo->query("SELECT LOWER(TRIM(titre)) FROM formations")->fetchAll(PDO::FETCH_COLUMN));
$existingSlugs  = array_flip($pdo->query("SELECT slug FROM formations")->fetchAll(PDO::FETCH_COLUMN));

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

$inserted = 0; $skipped = 0;

foreach ($formations as $f) {
    [$titre, $domaine, $duree, $tel, $tp] = $f;
    $titreKey = strtolower(trim($titre));
    if (isset($existingTitres[$titreKey])) { $skipped++; continue; }

    $slug = slugify5($titre); $base = $slug; $i = 1;
    while (isset($existingSlugs[$slug])) { $slug = $base . '-' . $i++; }

    $ins->execute([
        ':titre'   => $titre,
        ':slug'    => $slug,
        ':domaine' => $domaine,
        ':description' => 'Formation professionnelle certifiante IBIG EDUFORM — '.$titre.'. Reconnue dans les 17 pays de l\'espace OHADA.',
        ':duree'   => $duree.'h',
        ':tel'     => $tel, ':tp' => $tp,
        ':th'      => (int)(($tel + $tp) / 2),
    ]);
    $existingTitres[$titreKey] = true;
    $existingSlugs[$slug]      = true;
    $inserted++;
}

$totalCat = (int)$pdo->query("SELECT COUNT(*) FROM formations WHERE statut='active' AND (annee IS NULL OR annee=0)")->fetchColumn();
$totalAll = (int)$pdo->query("SELECT COUNT(*) FROM formations WHERE statut='active'")->fetchColumn();

echo "=== BULK INSERT V5 ===\n\n";
echo "Dans la liste   : ".count($formations)."\n";
echo "Insérées        : $inserted\n";
echo "Doublons ignorés: $skipped\n";
echo "---\n";
echo "Catalogue MySQL (annee=0) : $totalCat\n";
echo "Total actif MySQL         : $totalAll\n";
echo "Total public attendu      : API(965) + local($totalCat) = ".(965 + $totalCat)."\n";
echo "\n✅ Supprimé.\n";
@unlink(__FILE__);
