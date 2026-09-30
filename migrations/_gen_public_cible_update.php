<?php
/* Génère les UPDATE des publics cibles (si le programme est déjà importé). */
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
function q($v){ return "'" . str_replace("'", "''", (string)$v) . "'"; }

$o  = "-- =====================================================================\n";
$o .= "-- IBIG EDUFORM — Publics cibles (mise a jour, programme deja importe)\n";
$o .= "-- 100% sans risque : ajout de colonne tolerant + UPDATE cibles par code.\n";
$o .= "-- Si l'ALTER renvoie 'Duplicate column public_cible', c'est NORMAL\n";
$o .= "-- (la colonne existe deja) : ignorez et laissez tourner les UPDATE.\n";
$o .= "-- =====================================================================\n\n";
$o .= "ALTER TABLE formations ADD COLUMN IF NOT EXISTS public_cible TEXT NULL AFTER modules;\n\n";

foreach ($publics as $date => $txt) {
  $code = 'IBIG-' . str_replace('-', '', $date);
  $o .= "UPDATE formations SET public_cible = " . q($txt) . " WHERE code = " . q($code) . ";\n";
}
echo $o;
