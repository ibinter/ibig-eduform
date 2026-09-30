<?php
if (($_GET['k'] ?? '') !== 'ibig-bulk3-2026') { http_response_code(403); exit('Forbidden'); }
header('Content-Type: text/plain; charset=utf-8');

require_once __DIR__ . '/core/config.php';
require_once __DIR__ . '/core/database.php';
$pdo = Database::connect();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function slugify3(string $t): string {
    $t = mb_strtolower(trim($t), 'UTF-8');
    $map = ['à'=>'a','â'=>'a','ä'=>'a','á'=>'a','ã'=>'a','è'=>'e','é'=>'e','ê'=>'e','ë'=>'e',
            'î'=>'i','ï'=>'i','ô'=>'o','ö'=>'o','û'=>'u','ü'=>'u','ù'=>'u','ú'=>'u',
            'ç'=>'c','ñ'=>'n','œ'=>'oe','æ'=>'ae'];
    $t = strtr($t, $map);
    $t = preg_replace('/[^a-z0-9]+/', '-', $t);
    return trim((string)$t, '-');
}

// Format : [titre, domaine, duree_h, tarif_en_ligne, tarif_presentiel]
$formations = [
  // ── MANAGEMENT & LEADERSHIP ──────────────────────────────────────────────
  ['Négociation avancée et closing commercial','Management & Leadership',20,225000,275000],
  ['Gestion des conflits et médiation professionnelle','Management & Leadership',20,225000,275000],
  ['Leadership féminin et femmes managers','Management & Leadership',20,225000,275000],
  ['Management de la génération Z en entreprise','Management & Leadership',20,225000,275000],
  ['Accountability et responsabilité managériale','Management & Leadership',20,225000,275000],

  // ── MARKETING & VENTE ─────────────────────────────────────────────────────
  ['Vente consultative et solution selling','Gestion Commerciale & Marketing',20,225000,275000],
  ['Négociation grands comptes et key account','Gestion Commerciale & Marketing',25,225000,280000],
  ['Stratégie de prix et revenue management','Gestion Commerciale & Marketing',20,225000,275000],
  ['Affiliation et marketing de réseau MLM','Gestion Commerciale & Marketing',20,225000,275000],
  ['Géolocalisation et marketing de proximité','Gestion Commerciale & Marketing',20,225000,275000],

  // ── IA & DIGITALISATION ───────────────────────────────────────────────────
  ['Automatisation avec Zapier et Make','IA & Digitalisation',20,225000,275000],
  ['Notion pour la gestion de projets','IA & Digitalisation',20,225000,275000],
  ['Cybersécurité — Sécurisation des réseaux','IA & Digitalisation',25,250000,310000],
  ['Intelligence artificielle en santé','IA & Digitalisation',25,250000,310000],
  ['Blockchain et crypto-actifs pour entreprises','IA & Digitalisation',25,250000,310000],

  // ── COMPTABILITÉ & FINANCE ────────────────────────────────────────────────
  ['Tableaux de bord KPI avec Power BI Finance','Comptabilité & Finance',25,225000,280000],
  ['Comptabilité de gestion pour décideurs','Comptabilité & Finance',20,225000,275000],
  ['Facturation normalisée et gestion TVA numérique','Comptabilité & Finance',20,225000,275000],
  ['Contrôle budgétaire et écarts','Comptabilité & Finance',20,225000,275000],
  ['Gestion de trésorerie au quotidien','Comptabilité & Finance',20,225000,275000],

  // ── GRH ──────────────────────────────────────────────────────────────────
  ['Gestion des performances individuelles','GRH',20,225000,275000],
  ['Plan de développement des compétences','GRH',20,225000,275000],
  ['Gestion du absentéisme et présentéisme','GRH',20,225000,275000],
  ['Gestion des contrats de travail CDD CDI','GRH',20,225000,275000],
  ['Rupture conventionnelle et licenciement','GRH',20,225000,275000],

  // ── DROIT & JURIDIQUE ─────────────────────────────────────────────────────
  ['Droit de la famille et successions','Droit & Juridique',20,250000,310000],
  ['Régulation des télécommunications en Afrique','Droit & Juridique',20,250000,310000],
  ['Droit des ONG et associations','Droit & Juridique',20,225000,280000],
  ['Droit fiscal international et conventions fiscales','Droit & Juridique',25,250000,310000],
  ['Droit numérique et cybercriminalité','Droit & Juridique',20,250000,310000],

  // ── LOGISTIQUE & SUPPLY CHAIN ─────────────────────────────────────────────
  ['Sourcing international et sélection fournisseurs','Logistique & Supply Chain',20,225000,275000],
  ['Gestion des contrats de transport','Logistique & Supply Chain',20,225000,275000],
  ['Optimisation des coûts logistiques','Logistique & Supply Chain',20,225000,275000],

  // ── QHSE ─────────────────────────────────────────────────────────────────
  ['QHSE en secteur minier et extractif','QHSE',25,250000,310000],
  ['Hygiène et sécurité dans les hôtels','QHSE',20,225000,275000],
  ['Gestion environnementale des projets','QHSE',20,225000,275000],

  // ── BANQUE & ASSURANCE ────────────────────────────────────────────────────
  ['Gestion des créances irrécouvrables','Banque & Assurance',20,250000,310000],
  ['Financement de l\'agriculture — crédit rural','Banque & Assurance',20,250000,310000],
  ['Analyse de rentabilité et scoring crédit','Banque & Assurance',25,250000,310000],

  // ── AGRICULTURE ───────────────────────────────────────────────────────────
  ['Semences améliorées et biotechnologie agricole','Agriculture',20,225000,275000],
  ['Pastoralisme et gestion des troupeaux','Agriculture',20,225000,275000],
  ['Agriculture de conservation des sols','Agriculture',20,225000,275000],

  // ── BTP & IMMOBILIER ──────────────────────────────────────────────────────
  ['Carrelage pose et revêtements de sol','BTP & Construction',20,225000,275000],
  ['Peinture en bâtiment et décoration intérieure','BTP & Construction',20,225000,275000],
  ['Froid et climatisation — installation maintenance','BTP & Construction',25,225000,280000],

  // ── SANTÉ & PHARMACIE ─────────────────────────────────────────────────────
  ['Soins infirmiers avancés et pratique clinique','Santé & Pharmacie',30,250000,310000],
  ['Santé maternelle et néonatale','Santé & Pharmacie',25,250000,310000],
  ['Gestion des maladies chroniques diabète HTA','Santé & Pharmacie',20,225000,275000],

  // ── MINES ÉNERGIE PÉTROLE ─────────────────────────────────────────────────
  ['Forage pétrolier et techniques de puits','Mines, Énergie & Pétrole',35,280000,340000],
  ['Géologie appliquée à l\'exploration minière','Mines, Énergie & Pétrole',30,280000,340000],
  ['Gestion de l\'énergie en entreprise','Mines, Énergie & Pétrole',20,225000,275000],

  // ── TOURISME & HÔTELLERIE ─────────────────────────────────────────────────
  ['Œnotourisme et agrotourisme en Afrique','Tourisme & Hôtellerie',20,225000,275000],
  ['Gestion des réservations et booking en ligne','Tourisme & Hôtellerie',20,225000,275000],
  ['Traiteur événementiel et banquets','Tourisme & Hôtellerie',20,225000,275000],

  // ── INFOGRAPHIE & DESIGN ──────────────────────────────────────────────────
  ['DaVinci Resolve — Montage vidéo professionnel','Infographie & Design',25,225000,280000],
  ['Figma pour designers et développeurs','Infographie & Design',25,225000,280000],
  ['Stratégie de contenu visuel pour marques','Infographie & Design',20,225000,275000],

  // ── DIRECTION & ADMINISTRATION ────────────────────────────────────────────
  ['Gestion des appels et standard téléphonique','Direction & Administration',20,225000,275000],
  ['Organisation de voyages d\'affaires','Direction & Administration',20,225000,275000],

  // ── ÉDUCATION & FORMATION ─────────────────────────────────────────────────
  ['Mentorat et accompagnement des jeunes','Éducation & Formation',20,225000,275000],
  ['Gestion d\'une école maternelle et primaire','Éducation & Formation',25,225000,280000],
  ['Orientation scolaire et professionnelle','Éducation & Formation',20,225000,275000],

  // ── CRÉATION DE CONTENU ───────────────────────────────────────────────────
  ['Twitch et streaming en direct professionnel','Création de Contenu',20,225000,275000],
  ['Rédaction de livres blancs et rapports','Création de Contenu',20,225000,275000],
  ['Ghostwriting et rédaction pour dirigeants','Création de Contenu',20,225000,275000],

  // ── BEAUTÉ & BIEN-ÊTRE ────────────────────────────────────────────────────
  ['Microblading et maquillage permanent','Beauté & Bien-être',20,225000,275000],
  ['Soins capillaires naturels et traitements','Beauté & Bien-être',20,225000,275000],
  ['Développement d\'une marque cosmétique','Beauté & Bien-être',20,225000,275000],

  // ── COMMUNICATION INSTITUTIONNELLE ───────────────────────────────────────
  ['Communication gouvernementale et diplomatique','Communication Professionnelle',25,225000,280000],
  ['Lobbying et plaidoyer institutionnel','Communication Professionnelle',20,225000,275000],
  ['Relations avec les médias et attaché de presse','Communication Professionnelle',20,225000,275000],
  ['Communication de crise institutionnelle','Communication Professionnelle',20,225000,275000],

  // ── DÉVELOPPEMENT PERSONNEL ───────────────────────────────────────────────
  ['Gestion du stress et pleine conscience','Développement Personnel',20,225000,275000],
  ['Équilibre vie pro vie perso','Développement Personnel',20,225000,275000],
  ['Leadership spirituel et valeurs','Développement Personnel',20,225000,275000],
];

// ─── ANTI-DOUBLONS ────────────────────────────────────────────────────────
$existingTitres = $pdo->query("SELECT LOWER(TRIM(titre)) FROM formations")->fetchAll(PDO::FETCH_COLUMN);
$existingTitres = array_flip($existingTitres);
$existingSlugs  = $pdo->query("SELECT slug FROM formations")->fetchAll(PDO::FETCH_COLUMN);
$existingSlugs  = array_flip($existingSlugs);

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
    $titreKey = strtolower(trim($titre));
    if (isset($existingTitres[$titreKey])) { $skipped++; $skippedList[] = $titre; continue; }

    $slug = slugify3($titre); $base = $slug; $i = 1;
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

echo "=== BULK INSERT V3 ===\n\n";
echo "Dans la liste   : ".count($formations)."\n";
echo "Insérées        : $inserted\n";
echo "Doublons ignorés: $skipped\n";
echo "---\n";
echo "Catalogue (annee=0) : $totalCat\n";
echo "Total actif MySQL   : $totalAll\n";
echo "Total public attendu: ".($totalAll * 2)." (API + local)\n";
if ($skipped) { echo "\nDoublons :\n"; foreach ($skippedList as $t) echo "  - $t\n"; }
echo "\n✅ Supprimé.\n";
@unlink(__FILE__);
