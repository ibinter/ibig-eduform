-- =====================================================================
-- IBIG EDUFORM — Landing / TDR : Excel Professionnel pour l'Entreprise (Samedi Pro)
-- Idempotent : n'insère que si la formation n'a pas déjà de landing.
-- À exécuter dans phpMyAdmin (base eduform) APRÈS l'import du programme 2026.
-- =====================================================================
INSERT INTO formation_landings
  (formation_id, hero_title, hero_subtitle, pitch_marketing, promesse, appel_action,
   contexte, objectif_general, objectifs_specifiques, resultats_attendus,
   public_cible, prerequis, contenu_programme, methodologie, duree_organisation,
   formateurs, evaluation, avantages, modalites_participation, moyens_logistiques, validation)
SELECT
  f.id,
  'Excel Professionnel pour l’Entreprise',
  '1 journée intensive (7 h) · 100 % pratique · Samedi Pro',
  'Transformez Excel en véritable outil de pilotage. En une seule journée intensive, maîtrisez les fonctions avancées, les tableaux croisés dynamiques, les tableaux de bord et le reporting professionnel : gagnez des heures chaque semaine et fiabilisez vos décisions.',
  'Repartez le soir même avec vos propres modèles Excel et des automatisations directement réutilisables au travail.',
  'Réservez votre place — Samedi Pro, places limitées.',
  'Excel est l’outil le plus utilisé en entreprise, mais la plupart des professionnels n’en exploitent qu’une fraction. Résultat : des formules fragiles, des fichiers lourds, des reportings manuels et chronophages, et des erreurs coûteuses. Cette formation comble ce fossé en une journée, avec une approche 100 % pratique orientée productivité et fiabilité des données.',
  'Rendre chaque participant autonome et performant sur Excel pour analyser des données, automatiser les calculs et produire des tableaux de bord et reportings professionnels, fiables et clairs.',
  '• Maîtriser les fonctions avancées (RECHERCHEX / RECHERCHEV, SI imbriqués, SOMME.SI.ENS, INDEX/EQUIV…)
• Construire et exploiter des tableaux croisés dynamiques (TCD)
• Concevoir des tableaux de bord visuels et interactifs
• Automatiser et fiabiliser le reporting
• Nettoyer, structurer et sécuriser ses données',
  '• Un gain de temps immédiat sur les tâches répétitives
• Des reportings fiables, clairs et automatisés
• La capacité d’analyser rapidement de gros volumes de données
• Des modèles Excel réutilisables dès le lundi matin',
  'Tous professionnels : comptables, financiers, RH, commerciaux, gestionnaires, analystes, assistants de direction — ainsi que les étudiants souhaitant exploiter pleinement Excel.',
  '• Savoir utiliser Excel au niveau basique (saisie, formules simples)
• Disposer d’un ordinateur avec Excel (matériel possible sur place en présentiel)
• Aucune connaissance avancée requise — la progression part des fondamentaux utiles',
  'MODULE 1 – Excel Avancé & fonctions clés
• Références relatives / absolues et bonnes pratiques de construction
• Fonctions de recherche : RECHERCHEX, RECHERCHEV, INDEX / EQUIV
• Fonctions conditionnelles : SI, SI imbriqués, SOMME.SI.ENS, NB.SI.ENS
• Fonctions texte, date et logiques utiles au quotidien
• Mise en forme conditionnelle avancée

MODULE 2 – Tableaux Croisés Dynamiques (TCD)
• Préparer et structurer une base de données propre
• Créer, regrouper, trier et filtrer un TCD
• Champs & éléments calculés, segments (slicers) et chronologies
• Graphiques croisés dynamiques

MODULE 3 – Tableaux de bord & visualisation
• Concevoir un tableau de bord clair et interactif
• Choisir les bons indicateurs (KPI) et graphiques
• Listes déroulantes, contrôles et interactivité
• Mise en page professionnelle prête à présenter

MODULE 4 – Reporting & automatisation
• Automatiser les calculs et la consolidation de données
• Fiabiliser et sécuriser les fichiers (validation, protection)
• Construire des modèles de reporting réutilisables
• Astuces de productivité & raccourcis indispensables',
  'Formation 100 % pratique : chaque notion est appliquée immédiatement sur des cas réels d’entreprise. Les participants travaillent sur des fichiers fournis et repartent avec leurs propres modèles opérationnels.',
  '1 journée intensive de 7 heures (Samedi Pro). Format : présentiel à Abidjan ou en ligne en visioconférence interactive.',
  'Formateurs experts Excel & Data, praticiens en entreprise (contrôle de gestion, finance, business intelligence).',
  'Exercices pratiques tout au long de la journée et cas de synthèse final. Une attestation de participation IBIG EDUFORM est délivrée.',
  '• 1 journée = une compétence immédiatement opérationnelle
• Fichiers et modèles Excel offerts
• 100 % pratique, orienté gain de temps et fiabilité
• Tarif accessible (formule Samedi Pro)
• Attestation IBIG EDUFORM',
  'Format : présentiel / en ligne. Réservation en ligne puis règlement (la formule Samedi Pro se règle en totalité). Places limitées par session.',
  'Supports et fichiers d’exercices fournis, modèles Excel réutilisables, attestation de participation, encadrement permanent.',
  'Document validé par IBIG EDUFORM — INTERMARK BUSINESS INTERNATIONAL GROUP SARL.'
FROM formations f
WHERE f.titre = 'Excel Professionnel pour l''Entreprise'
  AND f.id NOT IN (SELECT x.formation_id FROM (SELECT formation_id FROM formation_landings) x);
