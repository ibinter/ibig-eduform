<?php
/* Générateur du SQL des LANDINGS (formation_landings) du programme AOÛT->DÉCEMBRE 2026.
   Une landing par formation (30). Idempotent : n'insère que si la formation
   n'a pas déjà de landing. Lien par f.code (précis, gère les 2 sessions du 10/08).
   À exécuter APRÈS l'import de 2026_programme_aout_decembre.sql.
   Usage : php _gen_landings_2026_aout.php > 2026_landings_aout_decembre.sql */

$DATA = require __DIR__ . '/_data_programme_2026_aout.php';

/* Contenu redige sur-mesure par formation (cle = code). Optionnel : sert de source
   prioritaire ; a defaut, gabarit de secours ci-dessous. */
$CONTENT = file_exists(__DIR__ . '/_landings_content_2026_aout.php')
  ? (require __DIR__ . '/_landings_content_2026_aout.php')
  : [];

/* Flyers HD par formation (fichiers dans /uploads/flyers/). Chemin relatif au webroot. */
$FLYERS = [
  'IBIG-20260808' => 'uploads/flyers/02-power-bi.png',
  'IBIG-20260810' => 'uploads/flyers/03-tresorerie-cash-flow.png',
  'IBIG-20260810B'=> 'uploads/flyers/04-audit-interne-risques.png',
  'IBIG-20260815' => 'uploads/flyers/05-kobotoolbox-collecte.png',
  'IBIG-20260817' => 'uploads/flyers/06-marketing-digital.png',
  'IBIG-20260824' => 'uploads/flyers/07-communication-art-oratoire.png',
  'IBIG-20260829' => 'uploads/flyers/08-microsoft-project.png',
  'IBIG-20260907' => 'uploads/flyers/09-fiscalite-declarations-dgi.png',
  'IBIG-20260914' => 'uploads/flyers/10-droit-du-travail.png',
  'IBIG-20260919' => 'uploads/flyers/11-sage-100-gescom.png',
  'IBIG-20260921' => 'uploads/flyers/12-gestion-de-projet-planification.png',
  'IBIG-20260928' => 'uploads/flyers/13-responsable-qhse.png',
  'IBIG-20261003' => 'uploads/flyers/14-sap-fi.png',
  'IBIG-20261005' => 'uploads/flyers/15-controle-de-gestion.png',
  'IBIG-20261012' => 'uploads/flyers/16-chef-comptable.png',
  'IBIG-20261017' => 'uploads/flyers/17-sage-100-gestion-de-caisse.png',
  'IBIG-20261019' => 'uploads/flyers/18-gestion-immobiliere.png',
  'IBIG-20261026' => 'uploads/flyers/19-prise-de-parole-manageriale.png',
  'IBIG-20261031' => 'uploads/flyers/20-intelligence-artificielle.png',
  'IBIG-20261102' => 'uploads/flyers/21-gestion-de-la-paie.png',
  'IBIG-20261109' => 'uploads/flyers/22-assistant-comptable-syscohada.png',
  'IBIG-20261116' => 'uploads/flyers/23-responsable-commercial.png',
  'IBIG-20261121' => 'uploads/flyers/24-sage-etats-comptables-fiscaux.png',
  'IBIG-20261123' => 'uploads/flyers/25-transport-logistique-scm.png',
  'IBIG-20261130' => 'uploads/flyers/26-leadership-management-equipe.png',
  'IBIG-20261207' => 'uploads/flyers/27-etats-financiers-syscohada.png',
  'IBIG-20261212' => 'uploads/flyers/28-canva-pro-design.png',
  'IBIG-20261214' => 'uploads/flyers/29-projet-humanitaire-ong.png',
  'IBIG-20261221' => 'uploads/flyers/30-daf.png',
  'IBIG-20261228' => 'uploads/flyers/31-entrepreneuriat-creation-entreprise.png',
];

function q($v): string {
  if ($v === null) return 'NULL';
  if (is_int($v)) return (string)$v;
  return "'" . str_replace("'", "''", (string)$v) . "'";
}
function fcfa(int $n): string { return number_format($n, 0, ',', ' ') . ' FCFA'; }
$moisFr = [1=>'janvier',2=>'février',3=>'mars',4=>'avril',5=>'mai',6=>'juin',7=>'juillet',
           8=>'août',9=>'septembre',10=>'octobre',11=>'novembre',12=>'décembre'];

/* Public cible générique par domaine */
$publicParDomaine = [
  'Finance' => "Comptables, gestionnaires financiers, trésoriers, contrôleurs de gestion, RAF, DAF et étudiants en finance.",
  'Audit & Contrôle' => "Auditeurs, contrôleurs de gestion, comptables, RAF, DAF, responsables du contrôle interne et consultants.",
  'Marketing Digital' => "Community managers, marketeurs, commerciaux, entrepreneurs, chargés de communication et étudiants.",
  'Communication' => "Cadres, managers, commerciaux, formateurs, entrepreneurs et toute personne devant s'exprimer en public.",
  'Fiscalité' => "Comptables, chefs comptables, fiscalistes, RAF, DAF et gestionnaires d'entreprise.",
  'Droit Social' => "Responsables et gestionnaires RH, chefs d'entreprise, managers, juristes et délégués du personnel.",
  'Gestion de Projet' => "Chefs de projet, coordinateurs, responsables de programmes, consultants et étudiants.",
  'QHSE' => "Animateurs, superviseurs et responsables QHSE, responsables qualité, chefs de chantier, ingénieurs et techniciens.",
  'Contrôle de Gestion' => "Contrôleurs de gestion, comptables, RAF, DAF, analystes et responsables financiers.",
  'Comptabilité' => "Comptables, aides-comptables, chefs comptables, RAF et étudiants en comptabilité.",
  'Immobilier' => "Agents, gestionnaires et promoteurs immobiliers, investisseurs et responsables patrimoine.",
  'RH & Paie' => "Gestionnaires de paie, assistants et responsables RH, responsables administratifs et étudiants en GRH.",
  'Commerce & Marketing' => "Commerciaux, responsables commerciaux et marketing, chargés de clientèle et entrepreneurs.",
  'Logistique & SCM' => "Logisticiens, gestionnaires de stocks, responsables achats, approvisionneurs, transporteurs et supply chain managers.",
  'Management' => "Managers, chefs d'équipe, responsables de service, cadres et dirigeants.",
  'Humanitaire & ONG' => "Gestionnaires et coordinateurs de projets, ONG, associations, agents de développement et consultants.",
  'Finance & Direction' => "Dirigeants, DAF, chefs comptables, RAF, contrôleurs de gestion et cadres financiers.",
  'Entrepreneuriat' => "Porteurs de projets, entrepreneurs, créateurs d'entreprise, dirigeants de PME et étudiants.",
  'Bureautique & Data' => "Tous professionnels manipulant des données : analystes, contrôleurs de gestion, comptables, managers et consultants.",
  'Collecte & Analyse de Données' => "ONG, consultants, statisticiens, enquêteurs, chargés de suivi-évaluation et chercheurs.",
  'Logiciels de Gestion (Sage)' => "Comptables, gestionnaires, caissiers, trésoriers et utilisateurs des logiciels de gestion Sage.",
  'Logiciels de Gestion (SAP)' => "Comptables, chefs comptables, consultants SAP et RAF.",
  'Intelligence Artificielle' => "Tous professionnels, entrepreneurs, consultants et étudiants souhaitant exploiter l'IA au quotidien.",
  'Design & Communication' => "Community managers, marketeurs, communicants, entrepreneurs et graphistes débutants.",
];
function publicCible(array $map, string $domaine): string {
  return $map[$domaine] ?? "Professionnels, cadres, entrepreneurs et étudiants concernés par le domaine.";
}

function dureeHumaine(string $d): string {
  return match ($d) {
    '7H'  => "1 journée intensive (7 h)",
    '14H' => "2 journées intensives (14 h)",
    '20H' => "20 heures (parcours certifiant)",
    '25H' => "25 heures (parcours certifiant)",
    '65H' => "65 heures (parcours complet)",
    default => $d,
  };
}
function dureeHeures(string $d): int { return (int)$d; }

/* Construit la liste des enregistrements avec code (meme logique que le programme) */
$records = [];
foreach ($DATA['certifs'] as $c) {
  [$date,$mois,$titre,$domaine,$duree,$tel,$tp,$mods] = $c;
  $records[] = compact('date','mois','titre','domaine','duree','tel','tp') + ['modules'=>$mods,'is_samedi'=>0];
}
foreach ($DATA['samedis'] as $s) {
  [$date,$mois,$titre,$domaine,$duree,$tel,$tp] = $s;
  $records[] = compact('date','mois','titre','domaine','duree','tel','tp') + ['modules'=>[],'is_samedi'=>1];
}
usort($records, fn($a,$b) => strcmp($a['date'],$b['date']));
$seenCode = [];
foreach ($records as $i => $r) {
  $code = 'IBIG-'.str_replace('-','',$r['date']);
  if (isset($seenCode[$code])) { $seenCode[$code]++; $code .= chr(64 + $seenCode[$code]); }
  else { $seenCode[$code] = 1; }
  $records[$i]['code'] = $code;
}

$out  = "-- =====================================================================\n";
$out .= "-- IBIG EDUFORM — LANDINGS du programme AOUT -> DECEMBRE 2026 (30 formations)\n";
$out .= "-- Table formation_landings. Idempotent : n'insere que si la formation\n";
$out .= "-- n'a pas deja de landing. Lien par code.\n";
$out .= "-- A EXECUTER APRES 2026_programme_aout_decembre.sql.\n";
$out .= "-- =====================================================================\n\n";

foreach ($records as $r) {
  $titre   = $r['titre'];
  $domaine = $r['domaine'];
  $duree   = $r['duree'];
  $isSamedi= $r['is_samedi'];
  $tel     = (int)$r['tel'];
  $tp      = (int)$r['tp'];
  $code    = $r['code'];
  $heroImage = $FLYERS[$code] ?? '';   // flyer HD de la formation
  $mods    = $r['modules'];
  $jour    = (int)substr($r['date'],8,2);
  $moisNum = (int)substr($r['date'],5,2);
  $moisTxt = $moisFr[$moisNum];
  $dH      = dureeHumaine($duree);
  $heures  = dureeHeures($duree);
  $public  = publicCible($publicParDomaine, $domaine);
  $modLower= $mods ? mb_strtolower($mods[0],'UTF-8') : mb_strtolower($titre,'UTF-8');

  if (!$isSamedi) {
    /* ---------- FORMATION CERTIFIANTE ---------- */
    $hero_subtitle = "Formation Certifiante · {$dH} · Mode hybride (en ligne & présentiel) · Démarrage le {$jour} {$moisTxt} 2026";
    $pitch = "Développez une expertise concrète et immédiatement opérationnelle en {$domaine}. La certification « {$titre} » vous forme en {$heures} heures, à travers un programme 100 % pratique encadré par des experts praticiens, pour renforcer votre employabilité et faire la différence en entreprise.";
    $promesse = "À l'issue du parcours, vous maîtrisez {$modLower} et les bonnes pratiques du métier — avec une certification IBIG EDUFORM valorisable sur le marché de l'emploi.";
    $appel = "Réservez votre place — inscription 50 000 FCFA, places limitées. Le 1er versement de 100 000 FCFA déclenche le démarrage.";
    $contexte = "Dans un environnement professionnel de plus en plus exigeant, les compétences en {$domaine} sont devenues incontournables. Beaucoup de professionnels manquent pourtant d'un cadre pratique et structuré pour se perfectionner. Cette certification répond à ce besoin par une approche concrète, orientée résultats et directement applicable au poste de travail.";
    $objGeneral = "Rendre chaque participant autonome et performant en {$domaine}, capable d'appliquer les méthodes et outils clés du métier dans un contexte réel d'entreprise.";
    $objSpecif = implode("\n", array_map(fn($m)=>'• '.$m, $mods));
    $resultats = "• Une montée en compétence immédiate et opérationnelle\n• La maîtrise des outils et méthodes de {$domaine}\n• Une certification reconnue IBIG EDUFORM\n• Des livrables et modèles réutilisables en entreprise";
    $prerequis = "• Aucun prérequis technique avancé\n• Une expérience ou un intérêt pour le domaine est un plus\n• Ordinateur requis pour les sessions en ligne";
    $contenu = '';
    foreach ($mods as $k=>$m) { $contenu .= "MODULE ".($k+1)." – ".$m."\n"; }
    $contenu = rtrim($contenu);
    $methodologie = "Formation 100 % pratique : chaque notion est appliquée immédiatement à des cas réels d'entreprise. Études de cas, exercices guidés, mises en situation et travaux dirigés rythment le parcours.";
    $dureeOrg = "{$heures} heures réparties en sessions (soirée et/ou week-end). Format hybride : présentiel à Abidjan ou en ligne (partout dans l'espace OHADA) en visioconférence interactive. Démarrage le {$jour} {$moisTxt} 2026.";
    $formateurs = "Formateurs experts et praticiens en {$domaine}, intervenant quotidiennement en entreprise.";
    $evaluation = "Évaluation continue, études de cas et projet final. Une certification IBIG EDUFORM est délivrée à l'issue du parcours.";
    $avantages = "• Certification reconnue IBIG EDUFORM\n• Programme 100 % pratique et opérationnel\n• Formateurs praticiens expérimentés\n• Supports, modèles et ressources fournis\n• Format hybride flexible (en ligne ou présentiel)\n• Accompagnement et suivi personnalisés";
    $reste = max(0, $tel - 150000);
    $planPaiement = ($reste > 100000)
      ? "1er versement 100 000 FCFA avant le démarrage (déclenche la formation), puis le solde en 2 tranches au cours de la formation"
      : "1er versement 100 000 FCFA avant le démarrage (déclenche la formation), solde à mi-parcours";
    $modalites = "Tarif en ligne : ".number_format($tel,0,',',' ')." FCFA · Présentiel : ".number_format($tp,0,',',' ')." FCFA.\nPaiement : inscription 50 000 FCFA (réservation), ".$planPaiement.". Places limitées par session.";
    $moyens = "Supports de cours et ressources numériques, modèles et fichiers pratiques, plateforme de visioconférence, salle équipée en présentiel, attestation et certification IBIG EDUFORM.";
  } else {
    /* ---------- SAMEDI PRO ---------- */
    $hero_subtitle = "Samedi Pro · {$dH} · 100 % pratique · Mode hybride (en ligne & présentiel) · Session du {$jour} {$moisTxt} 2026";
    $pitch = "En une formation courte et intensive, montez en compétence sur {$titre}. Une session 100 % pratique, orientée productivité, pour acquérir des compétences immédiatement applicables à votre travail.";
    $promesse = "Repartez le jour même avec des compétences concrètes et des outils directement réutilisables sur {$titre}.";
    $appel = "Réservez votre place — Samedi Pro, places limitées.";
    $contexte = "Les formats courts « Samedi Pro » d'IBIG EDUFORM permettent aux professionnels de se perfectionner sans interrompre leur activité. Cette session {$titre} propose une montée en compétence rapide, concrète et directement opérationnelle.";
    $objGeneral = "Rendre chaque participant autonome et opérationnel sur {$titre}, à travers une approche 100 % pratique.";
    $objSpecif = "• Comprendre les fondamentaux de {$titre}\n• Mettre en pratique sur des cas concrets\n• Acquérir des réflexes et bonnes pratiques directement réutilisables";
    $resultats = "• Des compétences immédiatement opérationnelles\n• Des outils et modèles réutilisables\n• Une attestation de participation IBIG EDUFORM";
    $prerequis = "• Aucun prérequis avancé\n• Ordinateur requis pour les sessions en ligne";
    $contenu = "MODULE 1 – Fondamentaux de {$titre}\nMODULE 2 – Ateliers pratiques guidés\nMODULE 3 – Cas d'application concrets\nMODULE 4 – Synthèse & bonnes pratiques";
    $methodologie = "Formation 100 % pratique : démonstrations, exercices et ateliers appliqués sur des cas réels. Les participants manipulent l'outil tout au long de la session.";
    $dureeOrg = "{$dH} (Samedi Pro). Format : présentiel à Abidjan ou en ligne (partout dans l'espace OHADA) en visioconférence interactive. Session du {$jour} {$moisTxt} 2026.";
    $formateurs = "Formateurs experts praticiens, spécialistes de {$domaine}.";
    $evaluation = "Exercices pratiques et cas de synthèse. Une attestation de participation IBIG EDUFORM est délivrée.";
    $avantages = "• Format court et intensif\n• 100 % pratique, orienté gain de temps\n• Outils et modèles fournis\n• Tarif accessible (formule Samedi Pro)\n• Attestation IBIG EDUFORM";
    $modalites = "Tarif en ligne : ".number_format($tel,0,',',' ')." FCFA · Présentiel : ".number_format($tp,0,',',' ')." FCFA.\nFormule Samedi Pro : règlement en totalité, sans frais d'inscription. Places limitées.";
    $moyens = "Supports et fichiers d'exercices fournis, modèles réutilisables, plateforme de visioconférence, salle équipée en présentiel, attestation de participation.";
  }
  $validation = "Document validé par IBIG EDUFORM — INTERMARK BUSINESS INTERNATIONAL GROUP SARL.";

  /* Contenu sur-mesure prioritaire (si disponible pour ce code).
     Les tarifs/modalités et la validation restent calculés ci-dessus. */
  if (!empty($CONTENT[$code]) && is_array($CONTENT[$code])) {
    $b = $CONTENT[$code];
    $map = [
      'hero_subtitle'          => &$hero_subtitle,
      'pitch_marketing'        => &$pitch,
      'promesse'               => &$promesse,
      'appel_action'           => &$appel,
      'contexte'               => &$contexte,
      'objectif_general'       => &$objGeneral,
      'objectifs_specifiques'  => &$objSpecif,
      'resultats_attendus'     => &$resultats,
      'public_cible'           => &$public,
      'prerequis'              => &$prerequis,
      'contenu_programme'      => &$contenu,
      'methodologie'           => &$methodologie,
      'duree_organisation'     => &$dureeOrg,
      'formateurs'             => &$formateurs,
      'evaluation'             => &$evaluation,
      'avantages'              => &$avantages,
    ];
    foreach ($map as $key => &$ref) {
      if (isset($b[$key]) && trim((string)$b[$key]) !== '') { $ref = (string)$b[$key]; }
    }
    unset($ref);
  }

  $out .= "-- {$code} — {$titre}\n";

  /* UPSERT 1 : met a jour la landing existante (contenu) + efface les images/SEO
     perimes d'un ancien programme (ex. flyer Audit sur une session devenue Power BI). */
  $out .= "UPDATE formation_landings SET\n";
  $out .= "  hero_title=".q($titre).", hero_subtitle=".q($hero_subtitle).", pitch_marketing=".q($pitch).",\n";
  $out .= "  promesse=".q($promesse).", appel_action=".q($appel).", contexte=".q($contexte).",\n";
  $out .= "  objectif_general=".q($objGeneral).", objectifs_specifiques=".q($objSpecif).", resultats_attendus=".q($resultats).",\n";
  $out .= "  public_cible=".q($public).", prerequis=".q($prerequis).", contenu_programme=".q($contenu).",\n";
  $out .= "  methodologie=".q($methodologie).", duree_organisation=".q($dureeOrg).", formateurs=".q($formateurs).",\n";
  $out .= "  evaluation=".q($evaluation).", avantages=".q($avantages).", modalites_participation=".q($modalites).",\n";
  $out .= "  moyens_logistiques=".q($moyens).", validation=".q($validation).",\n";
  $out .= "  hero_image=".($heroImage!==''?q($heroImage):'NULL').", image_1=NULL, image_2=NULL, image_3=NULL, video_url=NULL, pitch=NULL, seo_title=NULL, seo_description=NULL\n";
  $out .= "WHERE formation_id = (SELECT id FROM (SELECT id FROM formations WHERE code = ".q($code)." LIMIT 1) AS t);\n";

  /* UPSERT 2 : insere la landing si la formation n'en a aucune */
  $out .= "INSERT INTO formation_landings\n";
  $out .= "  (formation_id, hero_title, hero_subtitle, pitch_marketing, promesse, appel_action,\n";
  $out .= "   contexte, objectif_general, objectifs_specifiques, resultats_attendus,\n";
  $out .= "   public_cible, prerequis, contenu_programme, methodologie, duree_organisation,\n";
  $out .= "   formateurs, evaluation, avantages, modalites_participation, moyens_logistiques, validation, hero_image)\n";
  $out .= "SELECT f.id,\n  ";
  $out .= q($titre).",\n  ".q($hero_subtitle).",\n  ".q($pitch).",\n  ".q($promesse).",\n  ".q($appel).",\n  ";
  $out .= q($contexte).",\n  ".q($objGeneral).",\n  ".q($objSpecif).",\n  ".q($resultats).",\n  ";
  $out .= q($public).",\n  ".q($prerequis).",\n  ".q($contenu).",\n  ".q($methodologie).",\n  ".q($dureeOrg).",\n  ";
  $out .= q($formateurs).",\n  ".q($evaluation).",\n  ".q($avantages).",\n  ".q($modalites).",\n  ".q($moyens).",\n  ".q($validation).",\n  ".($heroImage!==''?q($heroImage):'NULL')."\n";
  $out .= "FROM formations f\n";
  $out .= "WHERE f.code = ".q($code)."\n";
  $out .= "  AND f.id NOT IN (SELECT x.formation_id FROM (SELECT formation_id FROM formation_landings) x);\n\n";
}

echo $out;
