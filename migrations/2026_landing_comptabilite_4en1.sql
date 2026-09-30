-- =====================================================================
-- IBIG EDUFORM — TDR -> page détaillée (landing) : Comptabilité & Finance 4 en 1
-- Implante le contenu riche du TDR dans formation_landings.
-- Idempotent : n'insère que si la formation n'a pas déjà de landing.
-- À exécuter dans phpMyAdmin (base eduform) APRÈS l'import du programme 2026.
-- =====================================================================
INSERT INTO formation_landings
  (formation_id, hero_title, hero_subtitle, pitch_marketing, promesse, appel_action,
   contexte, objectif_general, objectifs_specifiques, resultats_attendus,
   public_cible, prerequis, contenu_programme, methodologie, duree_organisation,
   formateurs, evaluation, avantages, modalites_participation, moyens_logistiques,
   contacts, validation)
SELECT
  f.id,
  'Certificat 4 en 1 – Comptabilité Professionnelle, Finance et Gestion',
  '3 mois · 70% pratique / 30% théorie · 4 certificats en 1 + initiation SAP FI & CO',
  'Devenez un professionnel comptable et financier immédiatement opérationnel. Une seule formation, 4 certificats : Comptable, Chef Comptable, Responsable Financier et Comptabilité ONG (SYCEBNL), avec une initiation à SAP FI & CO. Conçue pour les réalités du marché africain et international.',
  'En 3 mois, maîtrisez la comptabilité SYSCOHADA, la supervision financière, la gestion budgétaire et les environnements ERP.',
  'Réservez votre place — inscriptions ouvertes.',
  'Dans un environnement marqué par l’exigence de conformité SYSCOHADA, la pression fiscale et la digitalisation des systèmes comptables, les entreprises et ONG recherchent des profils polyvalents capables de maîtriser la comptabilité, la supervision financière et les environnements ERP. Cette formation vise à former des professionnels immédiatement opérationnels et adaptés aux réalités du marché africain et international.',
  'Former des professionnels capables de tenir, superviser et piloter la fonction comptable et financière d’une organisation, tout en comprenant les environnements ERP modernes et la logique SAP FI & CO.',
  '• Maîtriser la comptabilité SYSCOHADA révisée
• Produire les états financiers
• Superviser un service comptable
• Élaborer et suivre un budget
• Analyser la performance financière
• Gérer la comptabilité des ONG (SYCEBNL)
• Comprendre la logique SAP FI & CO',
  '• Comptables immédiatement opérationnels
• Capacité d’intégration en PME, ONG et grandes entreprises
• Maîtrise des outils numériques financiers
• Employabilité renforcée',
  'Comptables, assistants comptables, chefs comptables, responsables financiers, gestionnaires ONG, entrepreneurs, étudiants en finance / gestion.',
  '• Niveau BAC comptabilité minimum recommandé
• Connaissances de base en comptabilité
• Maîtrise basique d’Excel
• Engagement pour une formation professionnalisante',
  'MODULE 1 – Comptabilité Générale (Certificat Comptable)
• Principes comptables SYSCOHADA
• Plan comptable OHADA
• Enregistrements des opérations comptables
• Traitement de la TVA, charges et produits
• Gestion des immobilisations et amortissements
• Travaux d’inventaire et opérations de clôture
• Élaboration des états financiers (Bilan, Compte de résultat, Tableau des flux de trésorerie)

MODULE 2 – Fonction Chef Comptable
• Organisation et structuration du service comptable
• Mise en place des procédures internes comptables
• Contrôle et validation des écritures comptables
• Déclarations fiscales
• Gestion des obligations sociales
• Préparation des travaux de clôture et d’audit

MODULE 3 – Responsable Financier (RF)
• Élaboration budgétaire
• Analyse financière (Ratios, FR, BFR, CAF)
• Gestion de trésorerie
• Tableaux de bord & KPI financiers
• Gestion des risques financiers & contrôle interne
• Préparation de dossiers bancaires & plans de financement

MODULE 4 – Comptabilité ONG & SYCEBNL
• Cadre réglementaire et principes du SYCEBNL
• Comptabilisation des dons, subventions et financements
• Gestion comptable des projets financés
• Élaboration des reportings financiers pour bailleurs
• Préparation et suivi des audits ONG

MODULE 5 – ERP & Initiation SAP FI & CO
• Introduction aux ERP
• Structure SAP (Company Code, GL, AR, AP)
• Logique des écritures SAP FI
• Centres de coûts SAP CO
• Budgétisation sous ERP
• Correspondance OHADA / SAP
• Simulation pédagogique',
  'Formation 70% pratique / 30% théorie : études de cas réels, simulations professionnelles, ateliers Excel & ERP, projet intégré.',
  'Durée totale : 3 mois (≈ 100 heures). Rythme structuré, alternant théorie essentielle et pratique intensive.',
  'Experts-comptables, chefs comptables seniors, responsables financiers et consultants ERP.',
  'Évaluations continues, travaux pratiques, étude de cas finale et projet intégré. Certificat délivré pour une présence ≥ 80%.',
  '• 4 certifications en une seule formation
• Forte valeur professionnelle
• Initiation SAP intégrée
• Adaptée aux réalités africaines',
  'Mode : Présentiel / En ligne / Hybride. Préinscription en ligne, puis paiement des frais d’inscription (50 000 FCFA, inclus).',
  'Planification structurée, supports pédagogiques fournis, modèles Excel, encadrement permanent.',
  '+225 27 22 27 60 14 · +225 07 78 88 25 92 · formation@intermark-business.com · ibig-eduform.com',
  'Document validé par IBIG EDUFORM – INTERMARK BUSINESS INTERNATIONAL GROUP SARL.'
FROM formations f
WHERE f.titre = 'Comptabilité & Finance 4 en 1'
  AND f.id NOT IN (SELECT x.formation_id FROM (SELECT formation_id FROM formation_landings) x);
