<?php
if (($_GET['k'] ?? '') !== 'ibig-bulk2-2026') { http_response_code(403); exit('Forbidden'); }
header('Content-Type: text/plain; charset=utf-8');

require_once __DIR__ . '/core/config.php';
require_once __DIR__ . '/core/database.php';
$pdo = Database::connect();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function slugify2(string $t): string {
    $t = mb_strtolower(trim($t), 'UTF-8');
    $map = ['à'=>'a','â'=>'a','ä'=>'a','á'=>'a','ã'=>'a','å'=>'a',
            'è'=>'e','é'=>'e','ê'=>'e','ë'=>'e','ě'=>'e',
            'î'=>'i','ï'=>'i','í'=>'i','ì'=>'i',
            'ô'=>'o','ö'=>'o','ó'=>'o','ò'=>'o','õ'=>'o',
            'û'=>'u','ü'=>'u','ú'=>'u','ù'=>'u',
            'ç'=>'c','ñ'=>'n','œ'=>'oe','æ'=>'ae'];
    $t = strtr($t, $map);
    $t = preg_replace('/[^a-z0-9]+/', '-', $t);
    return trim((string)$t, '-');
}

// Format : [titre, domaine, duree_h, tarif_en_ligne, tarif_presentiel]
$formations = [
  // ── INFORMATIQUE & TECH ──────────────────────────────────────────────────
  ['Développement web HTML CSS JavaScript','Informatique & Tech',40,225000,280000],
  ['Développement d\'applications mobiles Android','Informatique & Tech',40,225000,280000],
  ['Python programmation pour débutants','Informatique & Tech',30,225000,280000],
  ['SQL et bases de données relationnelles','Informatique & Tech',25,225000,280000],
  ['Administration réseaux et systèmes Linux','Informatique & Tech',35,225000,280000],
  ['Cloud computing AWS fondamentaux','Informatique & Tech',30,250000,310000],
  ['Microsoft Azure pour entreprises','Informatique & Tech',30,250000,310000],
  ['Cybersécurité — Ethical Hacking','Informatique & Tech',35,250000,310000],
  ['Développement d\'applications avec Flutter','Informatique & Tech',35,225000,280000],
  ['WordPress — Créer et gérer son site web','Informatique & Tech',20,225000,275000],
  ['Maintenance informatique et dépannage','Informatique & Tech',25,225000,280000],
  ['Architecture logicielle et design patterns','Informatique & Tech',30,250000,310000],
  ['Tests logiciels et assurance qualité','Informatique & Tech',25,225000,280000],
  ['Développement d\'API REST avec Node.js','Informatique & Tech',30,225000,280000],
  ['React.js pour le développement frontend','Informatique & Tech',35,225000,280000],
  ['DevOps et intégration continue','Informatique & Tech',35,250000,310000],
  ['Sécurité des applications web OWASP','Informatique & Tech',25,250000,310000],
  ['Administration des bases de données MySQL','Informatique & Tech',25,225000,280000],
  ['Programmation Python avancée','Informatique & Tech',35,250000,310000],
  ['IoT et objets connectés pour entreprises','Informatique & Tech',30,225000,280000],

  // ── COMPTABILITÉ & FINANCE (complément) ──────────────────────────────────
  ['Chef comptable — Gestion complète','Comptabilité & Finance',35,250000,310000],
  ['Gestion financière des PME','Comptabilité & Finance',25,225000,280000],
  ['Levée de fonds et financement des entreprises','Comptabilité & Finance',25,250000,310000],
  ['Gestion des risques financiers et opérationnels','Comptabilité & Finance',30,250000,310000],
  ['Tableaux de bord financiers avec Excel','Comptabilité & Finance',25,225000,280000],
  ['Gestion des dettes et restructuration financière','Comptabilité & Finance',25,250000,310000],
  ['Comptabilité des établissements publics','Comptabilité & Finance',30,225000,280000],
  ['Finances publiques et budget de l\'État','Comptabilité & Finance',30,250000,310000],
  ['Gestion d\'actifs et patrimoine','Comptabilité & Finance',30,250000,310000],
  ['Déclarations fiscales et obligations comptables','Comptabilité & Finance',20,225000,275000],
  ['Green finance et finance durable','Comptabilité & Finance',25,250000,310000],
  ['Crowdfunding et financement participatif','Comptabilité & Finance',20,225000,275000],
  ['Marché financier régional BRVM','Comptabilité & Finance',25,250000,310000],
  ['Prévisions financières et modélisation','Comptabilité & Finance',30,250000,310000],
  ['Secrétaire comptable — Pratique complète','Comptabilité & Finance',25,225000,280000],

  // ── GRH (complément) ─────────────────────────────────────────────────────
  ['Gestion du changement et transformation RH','GRH',25,225000,280000],
  ['Droit disciplinaire et gestion des sanctions','GRH',20,225000,275000],
  ['Gestion des expatriés et mobilité internationale','GRH',25,250000,310000],
  ['Employer branding et marque employeur','GRH',20,225000,275000],
  ['Assessment center et bilan de compétences','GRH',25,225000,280000],
  ['Plan de formation et budget formation','GRH',20,225000,275000],
  ['Gestion des retraites et avantages sociaux','GRH',20,225000,275000],
  ['HR Tech et outils RH digitaux','GRH',25,225000,280000],
  ['Diversité équité et inclusion en entreprise','GRH',20,225000,275000],
  ['Gestion des talents et succession planning','GRH',25,225000,280000],

  // ── MANAGEMENT & LEADERSHIP (complément) ─────────────────────────────────
  ['Management de la performance organisationnelle','Management & Leadership',25,225000,280000],
  ['Gestion des risques stratégiques','Management & Leadership',25,250000,310000],
  ['Facilitation et animation de groupes','Management & Leadership',20,225000,275000],
  ['Gestion d\'une entreprise familiale','Management & Leadership',25,225000,280000],
  ['Management par projet — Méthode PMI','Management & Leadership',30,250000,310000],
  ['Planification et gestion par objectifs','Management & Leadership',20,225000,275000],
  ['Management de la qualité totale TQM','Management & Leadership',25,225000,280000],
  ['Gestion de crise et communication de crise','Management & Leadership',25,225000,280000],
  ['Management des équipes multiculturelles','Management & Leadership',25,225000,280000],
  ['Prince2 — Gestion de projets certifiée','Management & Leadership',35,280000,340000],

  // ── IA & DIGITALISATION (complément) ─────────────────────────────────────
  ['Data science appliquée aux métiers','IA & Digitalisation',35,250000,310000],
  ['Big data et architecture de données','IA & Digitalisation',35,250000,310000],
  ['Tableau Desktop — Visualisation avancée','IA & Digitalisation',25,225000,280000],
  ['Looker Studio — Rapports et dashboards','IA & Digitalisation',20,225000,275000],
  ['Machine learning avec Python scikit-learn','IA & Digitalisation',35,250000,310000],
  ['Digitalisation des processus métiers','IA & Digitalisation',25,225000,280000],
  ['Transformation numérique des PME africaines','IA & Digitalisation',25,225000,280000],
  ['IA appliquée à la finance et au risque','IA & Digitalisation',25,250000,310000],
  ['IA pour les ressources humaines','IA & Digitalisation',20,225000,275000],
  ['Chatbots et assistants virtuels d\'entreprise','IA & Digitalisation',25,225000,280000],
  ['Microsoft Copilot pour la productivité','IA & Digitalisation',20,225000,275000],
  ['Prompt engineering pour professionnels','IA & Digitalisation',20,225000,275000],

  // ── DROIT & JURIDIQUE (complément) ───────────────────────────────────────
  ['Veille juridique et réglementaire','Droit & Juridique',20,225000,280000],
  ['Propriété intellectuelle en Afrique francophone','Droit & Juridique',25,250000,310000],
  ['Droit de la concurrence OHADA','Droit & Juridique',25,250000,310000],
  ['Contrats commerciaux internationaux','Droit & Juridique',25,250000,310000],
  ['Droit de la consommation et protection du consommateur','Droit & Juridique',20,250000,310000],
  ['Droit de l\'environnement et RSE réglementaire','Droit & Juridique',25,250000,310000],
  ['Contentieux commercial — Médiation et arbitrage','Droit & Juridique',25,250000,310000],
  ['Lutte anti-blanchiment et financement du terrorisme','Droit & Juridique',25,250000,310000],
  ['Réglementation des marchés financiers en Afrique','Droit & Juridique',25,250000,310000],
  ['Rédaction de contrats et actes juridiques','Droit & Juridique',20,250000,310000],

  // ── LOGISTIQUE & SUPPLY CHAIN (complément) ───────────────────────────────
  ['Gestion des risques supply chain','Logistique & Supply Chain',25,225000,280000],
  ['Lean supply chain et optimisation','Logistique & Supply Chain',25,225000,280000],
  ['Gestion des transports et flotte de véhicules','Logistique & Supply Chain',25,225000,280000],
  ['Logistique de la dernière phase last mile','Logistique & Supply Chain',20,225000,275000],
  ['Gestionnaire de flotte et parc automobile','Logistique & Supply Chain',20,225000,275000],
  ['Gestion de projet logistique','Logistique & Supply Chain',25,225000,280000],
  ['Commerce intra-africain et ZLECAF','Logistique & Supply Chain',25,225000,280000],
  ['Responsable d\'entrepôt et magasinier','Logistique & Supply Chain',20,225000,275000],

  // ── ENTREPRENEURIAT (complément) ─────────────────────────────────────────
  ['Intrapreneuriat et innovation en entreprise','Entrepreneuriat',20,225000,275000],
  ['Franchise en Afrique — créer son réseau','Entrepreneuriat',25,225000,280000],
  ['Business model canvas et lean startup','Entrepreneuriat',20,225000,275000],
  ['Entrepreneuriat féminin en Afrique','Entrepreneuriat',25,225000,280000],
  ['Accélérer sa startup — de l\'idée au marché','Entrepreneuriat',25,225000,280000],
  ['Gestion d\'une PME — pratique complète','Entrepreneuriat',30,225000,280000],
  ['Commerce électronique transfrontalier en Afrique','Entrepreneuriat',25,225000,280000],
  ['Diaspora entrepreneuriat et investissement','Entrepreneuriat',25,225000,280000],
  ['Scale-up — Faire grandir son entreprise','Entrepreneuriat',25,225000,280000],

  // ── BANQUE & ASSURANCE (complément) ──────────────────────────────────────
  ['Gestion actif-passif ALM bancaire','Banque & Assurance',30,250000,310000],
  ['Tontine numérique et finance communautaire','Banque & Assurance',20,225000,275000],
  ['Assurance des entreprises et gestion des sinistres','Banque & Assurance',25,250000,310000],
  ['Retraite complémentaire et prévoyance collective','Banque & Assurance',20,250000,310000],
  ['Crédit immobilier et financement du logement','Banque & Assurance',25,250000,310000],
  ['Bancassurance numérique et insurtech','Banque & Assurance',25,250000,310000],
  ['Gestion d\'agence bancaire','Banque & Assurance',25,250000,310000],
  ['Lutte contre la fraude bancaire','Banque & Assurance',25,250000,310000],

  // ── QHSE (complément) ────────────────────────────────────────────────────
  ['ISO 22000 Sécurité des denrées alimentaires','QHSE',30,250000,310000],
  ['ISO 50001 Management de l\'énergie','QHSE',25,250000,310000],
  ['Gestion des déchets industriels et dangereux','QHSE',25,225000,280000],
  ['Lean Six Sigma Black Belt','QHSE',55,395000,450000],
  ['Auditeur interne QHSE','QHSE',30,250000,310000],
  ['Responsable QHSE en industrie','QHSE',35,250000,310000],
  ['Sécurité incendie et évacuation','QHSE',20,225000,275000],
  ['QHSE dans l\'industrie agroalimentaire','QHSE',25,225000,280000],

  // ── COMMUNICATION & SOFT SKILLS (complément) ─────────────────────────────
  ['Anglais professionnel — niveau intermédiaire','Développement Personnel',30,225000,280000],
  ['Anglais des affaires — Business English','Développement Personnel',30,225000,280000],
  ['Français professionnel pour non francophones','Communication Professionnelle',25,225000,280000],
  ['Confiance en soi et développement de carrière','Développement Personnel',20,225000,275000],
  ['Gestion de carrière et employabilité','Développement Personnel',20,225000,275000],
  ['Mindset entrepreneurial et résilience','Développement Personnel',20,225000,275000],
  ['Préparation aux entretiens d\'embauche','Développement Personnel',20,225000,275000],
  ['Relations interpersonnelles et écoute active','Développement Personnel',20,225000,275000],
  ['Gestion du temps et organisation personnelle','Développement Personnel',20,225000,275000],
  ['Protocole et étiquette professionnelle','Communication Professionnelle',20,225000,275000],
  ['Communication en situation de crise','Communication Professionnelle',20,225000,275000],
  ['Réseautage professionnel et networking','Développement Personnel',20,225000,275000],

  // ── AGRICULTURE (complément) ──────────────────────────────────────────────
  ['Apiculture et production de miel','Agriculture',25,225000,280000],
  ['Aviculture moderne et production de volailles','Agriculture',25,225000,280000],
  ['Horticulture ornementale et paysagisme','Agriculture',25,225000,280000],
  ['Agriculture urbaine et jardins productifs','Agriculture',20,225000,275000],
  ['Nutrition animale et alimentation du bétail','Agriculture',25,225000,280000],
  ['Gestion de l\'eau et irrigation agricole','Agriculture',25,225000,280000],
  ['Transformation et conservation des produits agricoles','Agriculture',25,225000,280000],
  ['Accès aux marchés et commercialisation agricole','Agriculture',20,225000,275000],

  // ── BTP & IMMOBILIER (complément) ────────────────────────────────────────
  ['Plomberie et installations sanitaires','BTP & Construction',25,225000,280000],
  ['Électricité du bâtiment et domotique','BTP & Construction',30,225000,280000],
  ['Menuiserie bois et aluminium','BTP & Construction',25,225000,280000],
  ['Conduite d\'engins de chantier','BTP & Construction',25,250000,310000],
  ['Urbanisme et planification territoriale','BTP & Construction',25,250000,310000],
  ['Gestion d\'une entreprise de BTP','BTP & Construction',25,225000,280000],
  ['Topographie et GPS pour BTP','BTP & Construction',30,225000,280000],
  ['Réhabilitation et rénovation de bâtiments','BTP & Construction',25,225000,280000],
  ['Immobilier durable et éco-construction','BTP & Construction',25,250000,310000],
  ['Gestionnaire de patrimoine immobilier','BTP & Construction',25,225000,280000],
  ['Promoteur immobilier junior','BTP & Construction',30,225000,280000],
  ['Agent immobilier professionnel','BTP & Construction',25,225000,280000],

  // ── SANTÉ & PHARMACIE (complément) ───────────────────────────────────────
  ['Pharmacovigilance et réglementation pharmaceutique','Santé & Pharmacie',25,250000,310000],
  ['Gestion des déchets médicaux et biosécurité','Santé & Pharmacie',20,225000,275000],
  ['Médecine du travail et santé en entreprise','Santé & Pharmacie',25,250000,310000],
  ['Nutrition clinique et thérapeutique','Santé & Pharmacie',25,225000,280000],
  ['Gestion des urgences médicales','Santé & Pharmacie',25,250000,310000],
  ['Gestion des laboratoires d\'analyses médicales','Santé & Pharmacie',25,250000,310000],

  // ── MINES ÉNERGIE PÉTROLE (complément) ───────────────────────────────────
  ['Valorisation des ressources minières africaines','Mines, Énergie & Pétrole',30,280000,340000],
  ['Énergie solaire — Installation et maintenance','Mines, Énergie & Pétrole',30,250000,310000],
  ['Gestion de l\'eau et traitement des eaux','Mines, Énergie & Pétrole',25,250000,310000],
  ['Transition énergétique et décarbonation','Mines, Énergie & Pétrole',25,250000,310000],
  ['Automatisation industrielle et PLC','Mines, Énergie & Pétrole',35,250000,310000],
  ['Maintenance industrielle préventive','Mines, Énergie & Pétrole',25,225000,280000],

  // ── TOURISME & HÔTELLERIE (complément) ───────────────────────────────────
  ['Techniques de réception et accueil hôtelier','Tourisme & Hôtellerie',20,225000,275000],
  ['Arts de la table et restauration gastronomique','Tourisme & Hôtellerie',25,225000,280000],
  ['Guide touristique professionnel','Tourisme & Hôtellerie',20,225000,275000],
  ['Tourisme culturel et patrimonial en Afrique','Tourisme & Hôtellerie',20,225000,275000],
  ['Gestion d\'un restaurant et food service','Tourisme & Hôtellerie',25,225000,280000],

  // ── INFOGRAPHIE & DESIGN (complément) ────────────────────────────────────
  ['Photographie professionnelle et retouche','Infographie & Design',25,225000,280000],
  ['Création de vidéos publicitaires','Infographie & Design',30,225000,280000],
  ['Design d\'interface mobile UI','Infographie & Design',30,225000,280000],
  ['Animation 2D et illustration numérique','Infographie & Design',35,225000,280000],
  ['Typographie et mise en page éditoriale','Infographie & Design',20,225000,275000],
  ['Impression 3D et modélisation','Infographie & Design',30,225000,280000],

  // ── DIRECTION & ADMINISTRATION (complément) ──────────────────────────────
  ['Gestion documentaire et archivage numérique','Direction & Administration',20,225000,275000],
  ['Services généraux et facility management','Direction & Administration',20,225000,275000],
  ['Coordination administrative et reporting','Direction & Administration',20,225000,275000],
  ['Gestion du courrier et protocole administratif','Direction & Administration',20,225000,275000],
  ['Secrétaire juridique et assistante notariale','Direction & Administration',25,225000,280000],
  ['Responsable administratif et juridique','Direction & Administration',30,250000,310000],

  // ── ÉDUCATION & FORMATION (complément) ───────────────────────────────────
  ['Gamification et pédagogie par le jeu','Éducation & Formation',20,225000,275000],
  ['Conception de modules e-learning','Éducation & Formation',25,225000,280000],
  ['Évaluation des acquis et certification','Éducation & Formation',20,225000,275000],
  ['Gestion des partenariats bailleurs ONG','Éducation & Formation',25,225000,280000],
  ['Suivi et évaluation de projets MEAL','Éducation & Formation',25,225000,280000],
  ['Pédagogie différenciée et inclusion scolaire','Éducation & Formation',20,225000,275000],

  // ── CRÉATION DE CONTENU (complément) ─────────────────────────────────────
  ['Journalisme digital et fact-checking','Création de Contenu',20,225000,275000],
  ['Relations presse et RP digitales','Création de Contenu',20,225000,275000],
  ['Newsletter et email marketing de contenu','Création de Contenu',20,225000,275000],
  ['Créer et monétiser une chaîne YouTube','Création de Contenu',25,225000,280000],
  ['Publicité Facebook et Instagram Ads','Gestion Commerciale & Marketing',20,225000,275000],
  ['Google Ads et publicité digitale','Gestion Commerciale & Marketing',20,225000,275000],
  ['LinkedIn pour professionnels et B2B','Gestion Commerciale & Marketing',20,225000,275000],
  ['Marketing par SMS et WhatsApp Business','Gestion Commerciale & Marketing',20,225000,275000],
  ['Gestion des avis clients et e-réputation','Gestion Commerciale & Marketing',20,225000,275000],
  ['Trade marketing et distribution moderne','Gestion Commerciale & Marketing',25,225000,280000],
  ['Category management et merchandising','Gestion Commerciale & Marketing',20,225000,275000],

  // ── IMMOBILIER (domaine dédié) ────────────────────────────────────────────
  ['Droit immobilier et litiges fonciers','Droit & Juridique',25,250000,310000],
  ['Fiscalité immobilière','Comptabilité & Finance',25,250000,310000],
  ['Financement immobilier et crédit hypothécaire','Banque & Assurance',25,250000,310000],
  ['Aménagement urbain et foncier','BTP & Construction',25,250000,310000],
  ['Développement durable en immobilier','BTP & Construction',25,250000,310000],

  // ── BEAUTÉ & BIEN-ÊTRE ────────────────────────────────────────────────────
  ['Esthétique et soins du visage','Beauté & Bien-être',25,225000,280000],
  ['Coiffure professionnelle et tresses africaines','Beauté & Bien-être',25,225000,280000],
  ['Maquillage professionnel et maquillage de mariée','Beauté & Bien-être',20,225000,275000],
  ['Massage bien-être et techniques de relaxation','Beauté & Bien-être',25,225000,280000],
  ['Gestion d\'un salon de beauté','Beauté & Bien-être',20,225000,275000],
  ['Onglerie et nail art professionnel','Beauté & Bien-être',20,225000,275000],
  ['Cosmétologie naturelle et produits bio','Beauté & Bien-être',20,225000,275000],
  ['Spa management et bien-être holistique','Beauté & Bien-être',20,225000,275000],

  // ── MODE & TEXTILE ────────────────────────────────────────────────────────
  ['Stylisme et création de mode africaine','Création de Contenu',30,225000,280000],
  ['Couture et modélisme professionnel','Création de Contenu',30,225000,280000],
  ['Gestion d\'une marque de mode','Entrepreneuriat',25,225000,280000],
  ['Broderie et travaux manuels créatifs','Création de Contenu',20,225000,275000],
];

// ─── RÉCUPÉRER TOUS LES TITRES ET SLUGS EXISTANTS ────────────────────────
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

    if (isset($existingTitres[$titreKey])) {
        $skipped++;
        $skippedList[] = $titre;
        continue;
    }

    $slug = slugify2($titre);
    $base = $slug; $i = 1;
    while (isset($existingSlugs[$slug])) { $slug = $base . '-' . $i++; }

    $ins->execute([
        ':titre'   => $titre,
        ':slug'    => $slug,
        ':domaine' => $domaine,
        ':description' => 'Formation professionnelle certifiante IBIG EDUFORM — ' . $titre . '. Reconnue dans les 17 pays de l\'espace OHADA.',
        ':duree'   => $duree . 'h',
        ':tel'     => $tel,
        ':tp'      => $tp,
        ':th'      => (int)(($tel + $tp) / 2),
    ]);

    $existingTitres[$titreKey] = true;
    $existingSlugs[$slug]      = true;
    $inserted++;
}

$totalCat = (int)$pdo->query("SELECT COUNT(*) FROM formations WHERE statut='active' AND (annee IS NULL OR annee=0)")->fetchColumn();
$totalAll = (int)$pdo->query("SELECT COUNT(*) FROM formations WHERE statut='active'")->fetchColumn();

echo "=== BULK INSERT V2 — FORMATIONS CATALOGUE ===\n\n";
echo "Formations dans la liste      : " . count($formations) . "\n";
echo "Insérées (nouvelles)          : $inserted\n";
echo "Ignorées (doublons)           : $skipped\n";
echo "---\n";
echo "Catalogue actif (annee=0)     : $totalCat\n";
echo "Total actif toutes formations : $totalAll\n";
echo "Total attendu catalogue public: " . ($totalAll * 2) . " (API + local)\n";

if ($skipped > 0) {
    echo "\n--- Doublons ignorés ---\n";
    foreach ($skippedList as $t) echo "  - $t\n";
}

echo "\n✅ Script supprimé.\n";
@unlink(__FILE__);
