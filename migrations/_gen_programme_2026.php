<?php
/* Générateur du SQL d'import du programme JUIN→DÉCEMBRE 2026.
   Usage : php _gen_programme_2026.php > 2026_programme_juin_decembre.sql */

/* [date, moisLabel, titre, type, enLigne(k), presentiel(k), composition, domaine] */
$rows = [
  ['2026-06-13','Juin','Comptabilité & Finance 4 en 1','Pack Premium',400,475,'Comptable Professionnel • Chef Comptable • RAF • Comptabilité des ONG & Associations (SYCEBNL)','Comptabilité & Finance'],
  ['2026-06-20','Juin','Excel Professionnel pour l\'Entreprise','Samedi Pro',20,25,'Excel Avancé • TCD • Tableaux de bord • Reporting','Bureautique & Data'],
  ['2026-06-27','Juin','GESCOM Business 4 en 1','Pack Premium',400,475,'Gestion Commerciale • Responsable Commercial • Responsable Marketing • Responsable Communication','Commerce & Marketing'],

  ['2026-07-04','Juillet','Sage 100 Comptabilité','Samedi Pro',25,30,'Paramétrage • Écritures • Journaux • États Financiers','Logiciels de Gestion (Sage)'],
  ['2026-07-11','Juillet','DAF Dirigeant','Pack Premium',450,525,'Direction Financière • Contrôle Budgétaire • Fiscalité • Trésorerie • Reporting','Finance & Direction'],
  ['2026-07-18','Juillet','Sage 100 Paie & RH','Samedi Pro',25,30,'Paie • CNPS • Congés • Administration du Personnel','Logiciels de Gestion (Sage)'],
  ['2026-07-25','Juillet','GRH Expert 3 en 1','Pack Premium',350,425,'Gestion de la Paie • Management RH • RH Digitale','Ressources Humaines'],

  ['2026-08-01','Août','Microsoft Power BI','Samedi Pro',35,40,'Analyse de données • Dashboards • Reporting','Bureautique & Data'],
  ['2026-08-08','Août','Audit & Contrôle de Gestion 4 en 1','Pack Premium',350,425,'Audit Interne • Contrôle de Gestion • Audit Comptable • Gestion des Risques','Audit & Contrôle'],
  ['2026-08-15','Août','KoBoToolbox & Collecte de Données','Samedi Pro',30,35,'Enquêtes • Collecte mobile • Analyse','Collecte & Analyse de Données'],
  ['2026-08-22','Août','Passation des Marchés Publics & Gestion des Achats 3 en 1','Pack Premium',300,375,'Passation des Marchés Publics • Gestion des Contrats • Achats, Approvisionnements & Gestion des Fournisseurs','Marchés Publics & Achats'],
  ['2026-08-29','Août','Microsoft Project','Samedi Pro',25,30,'Planification • Gantt • Suivi de Projet','Gestion de Projet'],

  ['2026-09-12','Septembre','Gestion & Management de Projets Humanitaires & ONG 3 en 1','Pack Premium',300,375,'Gestion de projet Humanitaire • ONG & Développement • Management de Projet','Humanitaire & ONG'],
  ['2026-09-19','Septembre','Sage 100 GESCOM','Samedi Pro',25,30,'Achats • Ventes • Stocks • Facturation','Logiciels de Gestion (Sage)'],
  ['2026-09-26','Septembre','QHSE Expert 4 en 1','Pack Premium',350,425,'Animateur HSE • Superviseur HSE • Responsable QHSE • Auditeur ISO','QHSE'],

  ['2026-10-03','Octobre','SAP FI – Comptabilité Financière','Samedi Pro',40,50,'Comptabilité Générale • Clients • Fournisseurs • Reporting','Logiciels de Gestion (SAP)'],
  ['2026-10-10','Octobre','Comptabilité & Finance 4 en 1','Pack Premium',350,425,'Comptable • Chef Comptable • RAF • Comptabilité ONG','Comptabilité & Finance'],
  ['2026-10-17','Octobre','Sage 100 Gestion de Caisse Décentralisée','Samedi Pro',25,30,'Encaissements • Décaissements • Contrôle de caisse','Logiciels de Gestion (Sage)'],
  ['2026-10-24','Octobre','Immobilier Professionnel 3 en 1','Pack Premium',300,375,'Gestion Immobilière Professionnelle • Promotion Immobilière & Montage d\'Opérations • Transaction Immobilière & Techniques de Vente','Immobilier'],
  ['2026-10-31','Octobre','Intelligence Artificielle pour Professionnels','Samedi Pro',25,30,'Claude • ChatGPT • Gemini • Copilot • Automatisation','Intelligence Artificielle'],

  ['2026-11-14','Novembre','DAF Dirigeant','Pack Premium',450,525,'Direction Financière • Contrôle Budgétaire • Fiscalité • Trésorerie','Finance & Direction'],
  ['2026-11-21','Novembre','Sage États Comptables & Fiscaux','Samedi Pro',25,30,'États Financiers • Déclarations Fiscales','Logiciels de Gestion (Sage)'],
  ['2026-11-28','Novembre','Responsable des RH 3 en 1','Pack Premium',300,375,'Gestion de la Paie & Administration du Personnel • Management des Ressources Humaines • RH Digitale & SIRH','Ressources Humaines'],

  ['2026-12-05','Décembre','Audit & Contrôle de Gestion 4 en 1','Pack Premium',450,525,'Audit • Contrôle de Gestion • Audit Comptable • Gestion des Risques','Audit & Contrôle'],
  ['2026-12-12','Décembre','Canva Pro & Design Marketing','Samedi Pro',25,30,'Flyers • Réseaux Sociaux • Présentations • IA Canva','Design & Communication'],
  ['2026-12-19','Décembre','Logistique & Supply Chain Management 4 en 1','Pack Premium',450,525,'Gestion des Stocks • Entrepôts • Logistique • Approvisionnements','Logistique & Supply Chain'],
];

/* Public cible par formation (clé = date) */
$publics = [
  '2026-06-13' => "Comptables, aides-comptables, assistants comptables, chefs comptables, gestionnaires financiers, étudiants en comptabilité-finance, auditeurs débutants, entrepreneurs et responsables administratifs.",
  '2026-06-20' => "Tous professionnels, comptables, RH, commerciaux, gestionnaires et étudiants.",
  '2026-06-27' => "Commerciaux, agents et responsables commerciaux, marketeurs, chargés clientèle, responsables communication, entrepreneurs, promoteurs de PME et étudiants en commerce et marketing.",
  '2026-07-04' => "Comptables, aides-comptables, chefs comptables et RAF.",
  '2026-07-11' => "Chefs comptables, RAF, DAF, contrôleurs de gestion, auditeurs, dirigeants d'entreprise, entrepreneurs, cadres financiers et responsables administratifs.",
  '2026-07-18' => "RH, gestionnaires de paie et assistants RH.",
  '2026-07-25' => "Gestionnaires RH, assistants RH, responsables RH, chargés de recrutement, responsables administratifs, gestionnaires de paie, étudiants en GRH et cadres souhaitant évoluer vers les RH.",
  '2026-08-01' => "Analystes, contrôleurs de gestion, comptables, managers et consultants.",
  '2026-08-08' => "Auditeurs, contrôleurs de gestion, comptables, RAF, DAF, responsables financiers, responsables contrôle interne, consultants et étudiants en finance et audit.",
  '2026-08-15' => "ONG, consultants, statisticiens, enquêteurs et chercheurs.",
  '2026-08-22' => "Responsables achats, acheteurs, logisticiens, gestionnaires de contrats, agents des collectivités et de l'État, ONG, projets financés et entreprises soumissionnaires.",
  '2026-08-29' => "Chefs de projet, coordinateurs de projet et consultants.",
  '2026-09-12' => "Gestionnaires et coordinateurs de projets, ONG, associations, consultants, agents de développement, responsables programmes et étudiants en gestion de projet.",
  '2026-09-19' => "Commerciaux, gestionnaires de stocks et responsables commerciaux.",
  '2026-09-26' => "Animateurs, superviseurs et responsables HSE, responsables qualité et QHSE, chefs de chantier, ingénieurs, techniciens et professionnels du BTP, de l'industrie, des mines et du pétrole.",
  '2026-10-03' => "Comptables, chefs comptables, consultants SAP et RAF.",
  '2026-10-10' => "Comptables, aides-comptables, assistants comptables, chefs comptables, gestionnaires financiers, étudiants en comptabilité-finance, auditeurs débutants, entrepreneurs et responsables administratifs.",
  '2026-10-17' => "Caissiers, comptables, trésoriers et gestionnaires financiers.",
  '2026-10-24' => "Agents, gestionnaires et promoteurs immobiliers, courtiers, investisseurs, entrepreneurs immobiliers, responsables patrimoine et étudiants en immobilier.",
  '2026-10-31' => "Tous professionnels, entrepreneurs, consultants et étudiants.",
  '2026-11-14' => "Chefs comptables, RAF, DAF, contrôleurs de gestion, auditeurs, dirigeants d'entreprise, entrepreneurs, cadres financiers et responsables administratifs.",
  '2026-11-21' => "Comptables, chefs comptables, RAF et fiscalistes.",
  '2026-11-28' => "Gestionnaires RH, assistants RH, responsables RH, chargés de recrutement, responsables administratifs, gestionnaires de paie, étudiants en GRH et cadres souhaitant évoluer vers les RH.",
  '2026-12-05' => "Auditeurs, contrôleurs de gestion, comptables, RAF, DAF, responsables financiers, responsables contrôle interne, consultants et étudiants en finance et audit.",
  '2026-12-12' => "Community managers, marketeurs, communicants et entrepreneurs.",
  '2026-12-19' => "Gestionnaires de stocks, magasiniers, logisticiens, responsables entrepôts, responsables achats, approvisionneurs, transporteurs, supply chain managers et étudiants en logistique.",
];

function translit(string $s): string {
  $map = ['à'=>'a','â'=>'a','ä'=>'a','á'=>'a','ã'=>'a','ç'=>'c','é'=>'e','è'=>'e','ê'=>'e','ë'=>'e',
          'î'=>'i','ï'=>'i','í'=>'i','ô'=>'o','ö'=>'o','ó'=>'o','õ'=>'o','ù'=>'u','û'=>'u','ü'=>'u','ú'=>'u',
          'ñ'=>'n','’'=>' ','\''=>' ','–'=>'-','&'=>' et '];
  $s = mb_strtolower($s, 'UTF-8');
  $s = strtr($s, $map);
  return $s;
}
function slugify(string $s): string {
  $s = translit($s);
  $s = preg_replace('/[^a-z0-9]+/', '-', $s);
  return trim($s, '-');
}
function q($v): string {
  if ($v === null) return 'NULL';
  if (is_int($v)) return (string)$v;
  return "'" . str_replace("'", "''", (string)$v) . "'";
}

$out  = "-- =====================================================================\n";
$out .= "-- IBIG EDUFORM — Programme officiel JUIN -> DECEMBRE 2026 (26 formations)\n";
$out .= "-- A importer dans phpMyAdmin (base eduform).\n";
$out .= "-- Etape 1 : desactive TOUTES les formations existantes.\n";
$out .= "-- Etape 2 : insere le nouveau programme (actif).\n";
$out .= "-- Sur (re-executable) : la desactivation epargne le programme 2026.\n";
$out .= "-- =====================================================================\n\n";

$out .= "-- ETAPE 0 — Ajouter la colonne public_cible si elle n'existe pas\n";
$out .= "ALTER TABLE formations ADD COLUMN IF NOT EXISTS public_cible TEXT NULL AFTER modules;\n\n";

$out .= "-- ETAPE 1 — Desactiver toutes les formations actuellement en base\n";
$out .= "UPDATE formations\n";
$out .= "   SET statut = 'inactive', updated_at = NOW()\n";
$out .= " WHERE code IS NULL OR code NOT LIKE 'IBIG-2026%';\n\n";

$out .= "-- ETAPE 2 — Inserer le nouveau programme (JUIN -> DECEMBRE 2026)\n\n";

$seenSlug = [];
foreach ($rows as $r) {
  [$date,$mois,$titre,$type,$tel,$tp,$compo,$domaine] = $r;
  $isSamedi = ($type === 'Samedi Pro') ? 1 : 0;
  /* Slug propre basé sur le titre (sans date). Doublons -> -session-N */
  $base = slugify($titre);
  if (isset($seenSlug[$base])) { $seenSlug[$base]++; $slug = $base . '-session-' . $seenSlug[$base]; }
  else { $seenSlug[$base] = 1; $slug = $base; }
  $code = 'IBIG-' . str_replace('-', '', $date);    // ex IBIG-20260613
  $code = 'IBIG-' . str_replace('-', '', $date);    // ex IBIG-20260613
  $modules = str_replace(' • ', '; ', $compo);
  $description = $type . ' — ' . str_replace(' • ', ', ', $compo) . '.';
  $jour = (int)substr($date, 8, 2);
  $session = 'Session du ' . $jour . ' ' . mb_strtolower($mois,'UTF-8') . ' 2026';
  $public  = $publics[$date] ?? '';
  $tarifEnLigne   = $tel * 1000;
  $tarifPresentiel= $tp  * 1000;
  /* Frais d'inscription : 50 000 pour les longues formations, 0 pour les Samedi Pro (paiement = total) */
  $fraisInscription = $isSamedi ? 0 : 50000;

  /* Duree :
     - Samedi Pro : <= 30 000 FCFA -> 7H ; > 30 000 FCFA -> 14H
     - Pack Premium : "4 en 1" -> 65H ; "3 en 1" -> 55H ; sinon (DAF Dirigeant) -> 65H */
  if ($isSamedi) {
    $duree = ($tarifPresentiel <= 30000) ? '7H' : '14H';
  } elseif (strpos($titre, '4 en 1') !== false) {
    $duree = '65H';
  } elseif (strpos($titre, '3 en 1') !== false) {
    $duree = '55H';
  } else {
    $duree = '100H'; // DAF Dirigeant (programme phare)
  }

  $out .= "INSERT INTO formations\n";
  $out .= "  (titre, slug, domaine, type_certificat, description, objectifs, modules, duree, mode,\n";
  $out .= "   tarif_presentiel, tarif_en_ligne, tarif_hybride, frais_inscription, paiement_lien,\n";
  $out .= "   date_debut, date_fin, mois, annee, session_label, statut, is_samedi_pro, code, public_cible,\n";
  $out .= "   created_at, updated_at)\n";
  $out .= "VALUES\n  (";
  $out .= q($titre) . ", " . q($slug) . ", " . q($domaine) . ", " . q($type) . ", ";
  $out .= q($description) . ", " . q('') . ", " . q($modules) . ", " . q($duree) . ", " . q('hybride') . ",\n   ";
  $out .= q((int)$tarifPresentiel) . ", " . q((int)$tarifEnLigne) . ", 0, " . q((int)$fraisInscription) . ", " . q('') . ",\n   ";
  $out .= q($date) . ", " . q($date) . ", " . q($mois) . ", 2026, " . q($session) . ", " . q('active') . ", " . q((int)$isSamedi) . ", " . q($code) . ", " . q($public) . ",\n   ";
  $out .= "NOW(), NOW());\n\n";
}

echo $out;
