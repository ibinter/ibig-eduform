<?php
/* Données source UNIQUES du programme AOÛT -> DÉCEMBRE 2026.
   Utilisé par _gen_programme_2026_aout.php (SQL formations)
   et par _gen_landings_2026_aout.php (SQL formation_landings).
   Source : IBIG_EDUFORM_Programme_Aout_Decembre_2026_v3.docx
   Reports d'août : Audit 03->10, Power BI 01->08.
   Retourne ['certifs'=>[...], 'samedis'=>[...]]. */

return [
/* CERTIFICATIONS CERTIFIANTES
   [date, mois, titre, domaine, duree, tarifEnLigne, tarifPresentiel, [modules...]] */
'certifs' => [
  ['2026-08-10','Août','Gestion de Trésorerie & Cash Flow','Finance','20H',200000,225000,
    ['Plan de trésorerie & prévisions','Encaissements & décaissements','Rapprochement bancaire','Optimisation du cash flow']],
  /* Reportee du 03/08 (date passee) au 10/08 — 2e certification le meme jour */
  ['2026-08-10','Août','Audit Interne & Gestion des Risques','Audit & Contrôle','25H',250000,275000,
    ['Méthodologie & démarche d\'audit interne','Gestion & cartographie des risques','Audit opérationnel & financier','Rapport d\'audit & recommandations']],
  ['2026-08-17','Août','Marketing Digital & Réseaux Sociaux','Marketing Digital','20H',200000,225000,
    ['Stratégie digitale & plan d\'action','Facebook, Instagram, LinkedIn, TikTok','Création de contenu & brand image','Publicité en ligne & analytics']],
  ['2026-08-24','Août','Communication Professionnelle & Art Oratoire','Communication','20H',200000,225000,
    ['Techniques de prise de parole en public','Langage corporel, posture & gestuelle','Construction du message & storytelling','Gestion du stress & situations difficiles']],

  ['2026-09-07','Septembre','Fiscalité Pratique & Déclarations DGI','Fiscalité','20H',200000,225000,
    ['TVA & calcul pratique','Impôt sur les Sociétés (IS)','Patente & taxes locales','Liasse fiscale & contrôle fiscal']],
  ['2026-09-14','Septembre','Droit du Travail & Relations Sociales','Droit Social','20H',200000,225000,
    ['Code du Travail ivoirien (COTRA)','Contrats, rupture & licenciement','Congés & protection du salarié','Procédures Inspection du Travail']],
  ['2026-09-21','Septembre','Gestion de Projet & Planification Stratégique','Gestion de Projet','20H',200000,225000,
    ['Cycle de vie & méthodologies de projet','Planification, GANTT & jalons','Suivi-évaluation & reporting','Outils digitaux de gestion de projet']],
  ['2026-09-28','Septembre','Responsable QHSE','QHSE','25H',250000,275000,
    ['Management QHSE (ISO 9001, 14001, 45001)','Analyse des risques & plan de prévention','Audit HSE interne','Indicateurs QHSE & reporting']],

  ['2026-10-05','Octobre','Contrôle de Gestion & Analyse de Performance','Contrôle de Gestion','25H',250000,275000,
    ['Budget, prévisions & analyse des écarts','Tableaux de bord & KPI','Analyse des coûts & marges','Reporting de gestion']],
  ['2026-10-12','Octobre','Chef Comptable & Supervision Financière','Comptabilité','25H',250000,275000,
    ['Supervision comptabilité générale & analytique','Clôtures mensuelles & annuelles','Liasse fiscale & relations CAC','Management d\'équipe comptable']],
  ['2026-10-19','Octobre','Gestion Immobilière Professionnelle','Immobilier','25H',250000,275000,
    ['Gestion locative & baux commerciaux','Transaction & techniques de vente immobilière','Promotion immobilière','Fiscalité immobilière en Côte d\'Ivoire']],
  ['2026-10-26','Octobre','Prise de Parole & Communication Managériale','Communication','25H',250000,275000,
    ['Techniques avancées d\'art oratoire','Communication en réunion & négociation','Présentation professionnelle percutante','Leadership communication & influence']],

  ['2026-11-02','Novembre','Gestion de la Paie & Administration du Personnel','RH & Paie','20H',200000,225000,
    ['Calcul des salaires & CNPS','Bulletins de paie & charges sociales','Déclarations sociales (DTS, DISA)','Registre du personnel & procédures RH']],
  ['2026-11-09','Novembre','Assistant Comptable & SYSCOHADA','Comptabilité','20H',200000,225000,
    ['Saisie comptable & journaux SYSCOHADA','Rapprochement bancaire & lettrage','Gestion factures fournisseurs & clients','Déclarations fiscales de base']],
  ['2026-11-16','Novembre','Responsable Commercial & Marketing Opérationnel','Commerce & Marketing','25H',250000,275000,
    ['Stratégie commerciale & développement des ventes','Techniques de vente & négociation','Marketing opérationnel & promotions','Management d\'équipe commerciale']],
  ['2026-11-23','Novembre','Transport, Logistique & Supply Chain Management','Logistique & SCM','25H',250000,275000,
    ['Organisation du transport & modes de livraison','Gestion des stocks, entrepôts & flux','Approvisionnement & relations fournisseurs','Pilotage de la chaîne logistique (SCM)']],
  ['2026-11-30','Novembre','Leadership & Management d\'Équipe','Management','25H',250000,275000,
    ['Styles de leadership & posture de manager','Motivation, délégation & évaluation','Gestion des conflits & cohésion d\'équipe','Pilotage de la performance collective']],

  ['2026-12-07','Décembre','Élaboration des États Financiers SYSCOHADA','Comptabilité','20H',200000,225000,
    ['Travaux d\'inventaire & régularisations','Bilan, Compte de Résultat & TAFIRE','Notes annexes & liasse fiscale','Lecture & analyse des états financiers']],
  ['2026-12-14','Décembre','Gestion de Projet Humanitaire & ONG','Humanitaire & ONG','20H',200000,225000,
    ['Cycle de projet & logique d\'intervention','Montage de dossiers de financement','Suivi-Évaluation & rapportage','Gestion budgétaire ONG']],
  ['2026-12-21','Décembre','Direction Administrative & Financière (DAF)','Finance & Direction','65H',300000,375000,
    ['Rôle du DAF & pilotage stratégique','Comptabilité & clôture des comptes (SYSCOHADA)','Contrôle de gestion & performance','Trésorerie & relations bancaires','Fiscalité d\'entreprise & FNE/RNE','Audit, contrôle interne & risques','Droit des affaires & OHADA','Financement, investissement & croissance','Leadership & posture de dirigeant']],
  ['2026-12-28','Décembre','Entrepreneuriat & Création d\'Entreprise','Entrepreneuriat','20H',200000,225000,
    ['Idéation & validation du projet d\'entreprise','Business plan & étude de marché','Cadre juridique & fiscal de création en CI','Financement & premiers pas de l\'entrepreneur']],
],

/* SAMEDIS PRO
   [date, mois, titre, domaine, duree, tarifEnLigne, tarifPresentiel] */
'samedis' => [
  /* Reporte du 01/08 (date passee) au 08/08 */
  ['2026-08-08','Août','Microsoft Power BI','Bureautique & Data','14H',40000,65000],
  ['2026-08-15','Août','KoBoToolbox & Collecte de Données','Collecte & Analyse de Données','14H',35000,60000],
  ['2026-08-29','Août','Microsoft Project','Gestion de Projet','7H',30000,55000],
  ['2026-09-19','Septembre','Sage 100 GESCOM','Logiciels de Gestion (Sage)','7H',30000,55000],
  ['2026-10-03','Octobre','SAP FI – Comptabilité Financière','Logiciels de Gestion (SAP)','14H',50000,75000],
  ['2026-10-17','Octobre','Sage 100 Gestion de Caisse','Logiciels de Gestion (Sage)','7H',30000,55000],
  ['2026-10-31','Octobre','Intelligence Artificielle pour Professionnels','Intelligence Artificielle','7H',30000,55000],
  ['2026-11-21','Novembre','Sage États Comptables & Fiscaux','Logiciels de Gestion (Sage)','7H',30000,55000],
  ['2026-12-12','Décembre','Canva Pro & Design Marketing','Design & Communication','7H',30000,55000],
],
];
