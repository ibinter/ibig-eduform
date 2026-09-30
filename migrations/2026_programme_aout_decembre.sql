-- =====================================================================
-- IBIG EDUFORM — NOUVEAU programme officiel AOUT -> DECEMBRE 2026
-- 30 evenements : 21 Certifications Certifiantes + 9 Samedis Pro.
-- Source : IBIG_EDUFORM_Programme_Aout_Decembre_2026_v3.docx
-- JUIN et JUILLET sont CONSERVES tels quels (non modifies).
-- A importer dans phpMyAdmin (base eduform).
-- Etape 1 : desactive l'ancien programme (sauf juin, juillet et le nouveau programme).
-- Etape 2 : UPSERT par code (met a jour si present, insere sinon) — actif.
-- Idempotent : re-executable sans doublon ni suppression (preinscriptions preservees).
-- =====================================================================

-- ETAPE 0 — Colonne public_cible si absente
ALTER TABLE formations ADD COLUMN IF NOT EXISTS public_cible TEXT NULL AFTER modules;

-- ETAPE 1 — Desactiver l'ancien programme, en CONSERVANT juin, juillet
--           et les sessions du nouveau programme aout->decembre.
--           On reattribue un slug unique (archive-<id>) aux lignes archivees
--           pour LIBERER les slugs (index unique NOT NULL) que le nouveau
--           programme va reutiliser (ex. Power BI deplace du 01 au 08 aout).
UPDATE formations
   SET statut = 'inactive', slug = CONCAT('archive-', id), updated_at = NOW()
 WHERE code NOT LIKE 'IBIG-202606%'
   AND code NOT LIKE 'IBIG-202607%'
   AND (code IS NULL OR code NOT IN ('IBIG-20260808', 'IBIG-20260810', 'IBIG-20260810B', 'IBIG-20260815', 'IBIG-20260817', 'IBIG-20260824', 'IBIG-20260829', 'IBIG-20260907', 'IBIG-20260914', 'IBIG-20260919', 'IBIG-20260921', 'IBIG-20260928', 'IBIG-20261003', 'IBIG-20261005', 'IBIG-20261012', 'IBIG-20261017', 'IBIG-20261019', 'IBIG-20261026', 'IBIG-20261031', 'IBIG-20261102', 'IBIG-20261109', 'IBIG-20261116', 'IBIG-20261121', 'IBIG-20261123', 'IBIG-20261130', 'IBIG-20261207', 'IBIG-20261212', 'IBIG-20261214', 'IBIG-20261221', 'IBIG-20261228'));

-- ETAPE 2 — Nouveau programme AOUT -> DECEMBRE 2026 (UPSERT par code)

-- Session du 8 août 2026 — Microsoft Power BI (Samedi Pro)
UPDATE formations SET
  titre='Microsoft Power BI', slug='microsoft-power-bi', domaine='Bureautique & Data', type_certificat='Samedi Pro',
  description='Samedi Pro — Prise en main de Power BI et import des données, Nettoyer et transformer avec Power Query, Modélisation et calculs DAX, Visualisation et tableaux de bord, Publication et partage.', objectifs='Rendre le participant capable de collecter, transformer et visualiser des données dans Power BI pour produire des tableaux de bord interactifs et fiables.', modules='Prise en main de Power BI et import des données; Nettoyer et transformer avec Power Query; Modélisation et calculs DAX; Visualisation et tableaux de bord; Publication et partage', duree='14H', mode='hybride',
  tarif_presentiel=65000, tarif_en_ligne=40000, tarif_hybride=0, frais_inscription=0,
  date_debut='2026-08-08', date_fin='2026-08-08', mois='Août', annee=2026, session_label='Session du 8 août 2026',
  statut='active', is_samedi_pro=1, public_cible='Managers, analystes, contrôleurs de gestion, commerciaux, entrepreneurs et étudiants souhaitant exploiter leurs données efficacement. Idéal pour toute personne confrontée à des reportings réguliers.', updated_at=NOW()
 WHERE code='IBIG-20260808';
INSERT INTO formations
  (titre, slug, domaine, type_certificat, description, objectifs, modules, duree, mode,
   tarif_presentiel, tarif_en_ligne, tarif_hybride, frais_inscription, paiement_lien,
   date_debut, date_fin, mois, annee, session_label, statut, is_samedi_pro, code, public_cible,
   created_at, updated_at)
SELECT 'Microsoft Power BI', 'microsoft-power-bi', 'Bureautique & Data', 'Samedi Pro', 'Samedi Pro — Prise en main de Power BI et import des données, Nettoyer et transformer avec Power Query, Modélisation et calculs DAX, Visualisation et tableaux de bord, Publication et partage.', 'Rendre le participant capable de collecter, transformer et visualiser des données dans Power BI pour produire des tableaux de bord interactifs et fiables.', 'Prise en main de Power BI et import des données; Nettoyer et transformer avec Power Query; Modélisation et calculs DAX; Visualisation et tableaux de bord; Publication et partage', '14H', 'hybride',
   65000, 40000, 0, 0, '',
   '2026-08-08', '2026-08-08', 'Août', 2026, 'Session du 8 août 2026', 'active', 1, 'IBIG-20260808', 'Managers, analystes, contrôleurs de gestion, commerciaux, entrepreneurs et étudiants souhaitant exploiter leurs données efficacement. Idéal pour toute personne confrontée à des reportings réguliers.',
   NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM (SELECT id FROM formations WHERE code = 'IBIG-20260808') AS x);

-- Session du 10 août 2026 — Gestion de Trésorerie & Cash Flow (Formation Certifiante)
UPDATE formations SET
  titre='Gestion de Trésorerie & Cash Flow', slug='gestion-de-tresorerie-et-cash-flow', domaine='Finance', type_certificat='Formation Certifiante',
  description='Formation Certifiante — Construire et piloter le plan de trésorerie, Maîtriser les encaissements et les décaissements, Le rapprochement bancaire au service du suivi, Optimiser le cash flow et sécuriser la liquidité.', objectifs='Rendre le participant capable de bâtir, suivre et optimiser la trésorerie d''une entreprise afin de sécuriser sa liquidité et d''éclairer les décisions financières.', modules='Construire et piloter le plan de trésorerie; Maîtriser les encaissements et les décaissements; Le rapprochement bancaire au service du suivi; Optimiser le cash flow et sécuriser la liquidité', duree='20H', mode='hybride',
  tarif_presentiel=225000, tarif_en_ligne=200000, tarif_hybride=0, frais_inscription=50000,
  date_debut='2026-08-10', date_fin='2026-08-10', mois='Août', annee=2026, session_label='Session du 10 août 2026',
  statut='active', is_samedi_pro=0, public_cible='Comptables, trésoriers, contrôleurs de gestion, responsables administratifs et financiers, chefs d''entreprise, gérants de PME et étudiants en finance souhaitant maîtriser le pilotage du cash.', updated_at=NOW()
 WHERE code='IBIG-20260810';
INSERT INTO formations
  (titre, slug, domaine, type_certificat, description, objectifs, modules, duree, mode,
   tarif_presentiel, tarif_en_ligne, tarif_hybride, frais_inscription, paiement_lien,
   date_debut, date_fin, mois, annee, session_label, statut, is_samedi_pro, code, public_cible,
   created_at, updated_at)
SELECT 'Gestion de Trésorerie & Cash Flow', 'gestion-de-tresorerie-et-cash-flow', 'Finance', 'Formation Certifiante', 'Formation Certifiante — Construire et piloter le plan de trésorerie, Maîtriser les encaissements et les décaissements, Le rapprochement bancaire au service du suivi, Optimiser le cash flow et sécuriser la liquidité.', 'Rendre le participant capable de bâtir, suivre et optimiser la trésorerie d''une entreprise afin de sécuriser sa liquidité et d''éclairer les décisions financières.', 'Construire et piloter le plan de trésorerie; Maîtriser les encaissements et les décaissements; Le rapprochement bancaire au service du suivi; Optimiser le cash flow et sécuriser la liquidité', '20H', 'hybride',
   225000, 200000, 0, 50000, '',
   '2026-08-10', '2026-08-10', 'Août', 2026, 'Session du 10 août 2026', 'active', 0, 'IBIG-20260810', 'Comptables, trésoriers, contrôleurs de gestion, responsables administratifs et financiers, chefs d''entreprise, gérants de PME et étudiants en finance souhaitant maîtriser le pilotage du cash.',
   NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM (SELECT id FROM formations WHERE code = 'IBIG-20260810') AS x);

-- Session du 10 août 2026 — Audit Interne & Gestion des Risques (Formation Certifiante)
UPDATE formations SET
  titre='Audit Interne & Gestion des Risques', slug='audit-interne-et-gestion-des-risques', domaine='Audit & Contrôle', type_certificat='Formation Certifiante',
  description='Formation Certifiante — Cadre et fondamentaux de l''audit interne, Méthodologie et démarche d''une mission d''audit, Contrôle interne et cartographie des risques, Audit opérationnel et financier, Rapport d''audit, recommandations et cas pratique.', objectifs='Rendre le participant capable de conduire une mission d''audit interne complète et d''évaluer le dispositif de maîtrise des risques d''une organisation.', modules='Cadre et fondamentaux de l''audit interne; Méthodologie et démarche d''une mission d''audit; Contrôle interne et cartographie des risques; Audit opérationnel et financier; Rapport d''audit, recommandations et cas pratique', duree='25H', mode='hybride',
  tarif_presentiel=275000, tarif_en_ligne=250000, tarif_hybride=0, frais_inscription=50000,
  date_debut='2026-08-10', date_fin='2026-08-10', mois='Août', annee=2026, session_label='Session du 10 août 2026',
  statut='active', is_samedi_pro=0, public_cible='Auditeurs internes, contrôleurs internes, comptables, contrôleurs de gestion, responsables qualité et conformité, cadres administratifs et financiers, et étudiants souhaitant se spécialiser en audit et gestion des risques.', updated_at=NOW()
 WHERE code='IBIG-20260810B';
INSERT INTO formations
  (titre, slug, domaine, type_certificat, description, objectifs, modules, duree, mode,
   tarif_presentiel, tarif_en_ligne, tarif_hybride, frais_inscription, paiement_lien,
   date_debut, date_fin, mois, annee, session_label, statut, is_samedi_pro, code, public_cible,
   created_at, updated_at)
SELECT 'Audit Interne & Gestion des Risques', 'audit-interne-et-gestion-des-risques', 'Audit & Contrôle', 'Formation Certifiante', 'Formation Certifiante — Cadre et fondamentaux de l''audit interne, Méthodologie et démarche d''une mission d''audit, Contrôle interne et cartographie des risques, Audit opérationnel et financier, Rapport d''audit, recommandations et cas pratique.', 'Rendre le participant capable de conduire une mission d''audit interne complète et d''évaluer le dispositif de maîtrise des risques d''une organisation.', 'Cadre et fondamentaux de l''audit interne; Méthodologie et démarche d''une mission d''audit; Contrôle interne et cartographie des risques; Audit opérationnel et financier; Rapport d''audit, recommandations et cas pratique', '25H', 'hybride',
   275000, 250000, 0, 50000, '',
   '2026-08-10', '2026-08-10', 'Août', 2026, 'Session du 10 août 2026', 'active', 0, 'IBIG-20260810B', 'Auditeurs internes, contrôleurs internes, comptables, contrôleurs de gestion, responsables qualité et conformité, cadres administratifs et financiers, et étudiants souhaitant se spécialiser en audit et gestion des risques.',
   NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM (SELECT id FROM formations WHERE code = 'IBIG-20260810B') AS x);

-- Session du 15 août 2026 — KoBoToolbox & Collecte de Données (Samedi Pro)
UPDATE formations SET
  titre='KoBoToolbox & Collecte de Données', slug='kobotoolbox-et-collecte-de-donnees', domaine='Collecte & Analyse de Données', type_certificat='Samedi Pro',
  description='Samedi Pro — Concevoir son premier formulaire dans KoBoToolbox, Logiques d''affichage et règles de validation, Collecte mobile hors-ligne avec KoBoCollect, Export, nettoyage et analyse des données.', objectifs='Rendre chaque participant capable de concevoir, déployer et gérer de façon autonome une collecte de données mobile complète avec KoBoToolbox, de la création du formulaire à l''export des résultats.', modules='Concevoir son premier formulaire dans KoBoToolbox; Logiques d''affichage et règles de validation; Collecte mobile hors-ligne avec KoBoCollect; Export, nettoyage et analyse des données', duree='14H', mode='hybride',
  tarif_presentiel=60000, tarif_en_ligne=35000, tarif_hybride=0, frais_inscription=0,
  date_debut='2026-08-15', date_fin='2026-08-15', mois='Août', annee=2026, session_label='Session du 15 août 2026',
  statut='active', is_samedi_pro=1, public_cible='Chargés de suivi-évaluation, enquêteurs, chefs de projet, agents d''ONG, chercheurs, étudiants en sciences sociales et santé publique, et tout professionnel amené à collecter des données terrain. Aucune compétence en programmation n''est requise.', updated_at=NOW()
 WHERE code='IBIG-20260815';
INSERT INTO formations
  (titre, slug, domaine, type_certificat, description, objectifs, modules, duree, mode,
   tarif_presentiel, tarif_en_ligne, tarif_hybride, frais_inscription, paiement_lien,
   date_debut, date_fin, mois, annee, session_label, statut, is_samedi_pro, code, public_cible,
   created_at, updated_at)
SELECT 'KoBoToolbox & Collecte de Données', 'kobotoolbox-et-collecte-de-donnees', 'Collecte & Analyse de Données', 'Samedi Pro', 'Samedi Pro — Concevoir son premier formulaire dans KoBoToolbox, Logiques d''affichage et règles de validation, Collecte mobile hors-ligne avec KoBoCollect, Export, nettoyage et analyse des données.', 'Rendre chaque participant capable de concevoir, déployer et gérer de façon autonome une collecte de données mobile complète avec KoBoToolbox, de la création du formulaire à l''export des résultats.', 'Concevoir son premier formulaire dans KoBoToolbox; Logiques d''affichage et règles de validation; Collecte mobile hors-ligne avec KoBoCollect; Export, nettoyage et analyse des données', '14H', 'hybride',
   60000, 35000, 0, 0, '',
   '2026-08-15', '2026-08-15', 'Août', 2026, 'Session du 15 août 2026', 'active', 1, 'IBIG-20260815', 'Chargés de suivi-évaluation, enquêteurs, chefs de projet, agents d''ONG, chercheurs, étudiants en sciences sociales et santé publique, et tout professionnel amené à collecter des données terrain. Aucune compétence en programmation n''est requise.',
   NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM (SELECT id FROM formations WHERE code = 'IBIG-20260815') AS x);

-- Session du 17 août 2026 — Marketing Digital & Réseaux Sociaux (Formation Certifiante)
UPDATE formations SET
  titre='Marketing Digital & Réseaux Sociaux', slug='marketing-digital-et-reseaux-sociaux', domaine='Marketing Digital', type_certificat='Formation Certifiante',
  description='Formation Certifiante — Poser sa stratégie digitale avant de publier, Maîtriser les grandes plateformes, Créer du contenu qui retient l''attention, Soigner son image de marque, Publicité en ligne et mesure des résultats.', objectifs='Maîtriser les leviers du marketing digital pour concevoir, déployer et mesurer une stratégie de présence sur les réseaux sociaux au service d''objectifs commerciaux clairs.', modules='Poser sa stratégie digitale avant de publier; Maîtriser les grandes plateformes; Créer du contenu qui retient l''attention; Soigner son image de marque; Publicité en ligne et mesure des résultats', duree='20H', mode='hybride',
  tarif_presentiel=225000, tarif_en_ligne=200000, tarif_hybride=0, frais_inscription=50000,
  date_debut='2026-08-17', date_fin='2026-08-17', mois='Août', annee=2026, session_label='Session du 17 août 2026',
  statut='active', is_samedi_pro=0, public_cible='Cette formation s''adresse aux entrepreneurs, responsables marketing, community managers, commerçants et étudiants souhaitant transformer les réseaux sociaux en véritable levier de croissance. Elle convient aussi bien aux débutants motivés qu''aux professionnels désireux de structurer leur pratique.', updated_at=NOW()
 WHERE code='IBIG-20260817';
INSERT INTO formations
  (titre, slug, domaine, type_certificat, description, objectifs, modules, duree, mode,
   tarif_presentiel, tarif_en_ligne, tarif_hybride, frais_inscription, paiement_lien,
   date_debut, date_fin, mois, annee, session_label, statut, is_samedi_pro, code, public_cible,
   created_at, updated_at)
SELECT 'Marketing Digital & Réseaux Sociaux', 'marketing-digital-et-reseaux-sociaux', 'Marketing Digital', 'Formation Certifiante', 'Formation Certifiante — Poser sa stratégie digitale avant de publier, Maîtriser les grandes plateformes, Créer du contenu qui retient l''attention, Soigner son image de marque, Publicité en ligne et mesure des résultats.', 'Maîtriser les leviers du marketing digital pour concevoir, déployer et mesurer une stratégie de présence sur les réseaux sociaux au service d''objectifs commerciaux clairs.', 'Poser sa stratégie digitale avant de publier; Maîtriser les grandes plateformes; Créer du contenu qui retient l''attention; Soigner son image de marque; Publicité en ligne et mesure des résultats', '20H', 'hybride',
   225000, 200000, 0, 50000, '',
   '2026-08-17', '2026-08-17', 'Août', 2026, 'Session du 17 août 2026', 'active', 0, 'IBIG-20260817', 'Cette formation s''adresse aux entrepreneurs, responsables marketing, community managers, commerçants et étudiants souhaitant transformer les réseaux sociaux en véritable levier de croissance. Elle convient aussi bien aux débutants motivés qu''aux professionnels désireux de structurer leur pratique.',
   NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM (SELECT id FROM formations WHERE code = 'IBIG-20260817') AS x);

-- Session du 24 août 2026 — Communication Professionnelle & Art Oratoire (Formation Certifiante)
UPDATE formations SET
  titre='Communication Professionnelle & Art Oratoire', slug='communication-professionnelle-et-art-oratoire', domaine='Communication', type_certificat='Formation Certifiante',
  description='Formation Certifiante — Prendre la parole avec assurance, Construire un message qui marque, Convaincre et passer à l''action.', objectifs='Développer une communication professionnelle percutante et maîtriser les techniques d''art oratoire pour s''exprimer avec aisance et conviction en public.', modules='Prendre la parole avec assurance; Construire un message qui marque; Convaincre et passer à l''action', duree='20H', mode='hybride',
  tarif_presentiel=225000, tarif_en_ligne=200000, tarif_hybride=0, frais_inscription=50000,
  date_debut='2026-08-24', date_fin='2026-08-24', mois='Août', annee=2026, session_label='Session du 24 août 2026',
  statut='active', is_samedi_pro=0, public_cible='Cette formation s''adresse à tous les professionnels, cadres, entrepreneurs, formateurs et étudiants qui souhaitent gagner en impact à l''oral. Elle convient à celles et ceux qui veulent surmonter le trac et convaincre avec plus d''assurance.', updated_at=NOW()
 WHERE code='IBIG-20260824';
INSERT INTO formations
  (titre, slug, domaine, type_certificat, description, objectifs, modules, duree, mode,
   tarif_presentiel, tarif_en_ligne, tarif_hybride, frais_inscription, paiement_lien,
   date_debut, date_fin, mois, annee, session_label, statut, is_samedi_pro, code, public_cible,
   created_at, updated_at)
SELECT 'Communication Professionnelle & Art Oratoire', 'communication-professionnelle-et-art-oratoire', 'Communication', 'Formation Certifiante', 'Formation Certifiante — Prendre la parole avec assurance, Construire un message qui marque, Convaincre et passer à l''action.', 'Développer une communication professionnelle percutante et maîtriser les techniques d''art oratoire pour s''exprimer avec aisance et conviction en public.', 'Prendre la parole avec assurance; Construire un message qui marque; Convaincre et passer à l''action', '20H', 'hybride',
   225000, 200000, 0, 50000, '',
   '2026-08-24', '2026-08-24', 'Août', 2026, 'Session du 24 août 2026', 'active', 0, 'IBIG-20260824', 'Cette formation s''adresse à tous les professionnels, cadres, entrepreneurs, formateurs et étudiants qui souhaitent gagner en impact à l''oral. Elle convient à celles et ceux qui veulent surmonter le trac et convaincre avec plus d''assurance.',
   NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM (SELECT id FROM formations WHERE code = 'IBIG-20260824') AS x);

-- Session du 29 août 2026 — Microsoft Project (Samedi Pro)
UPDATE formations SET
  titre='Microsoft Project', slug='microsoft-project', domaine='Gestion de Projet', type_certificat='Samedi Pro',
  description='Samedi Pro — Planification : tâches, durées et jalons, Ressources et diagramme de Gantt, Suivi d''avancement et reporting.', objectifs='Rendre le participant capable de planifier, structurer et suivre un projet complet avec Microsoft Project, du découpage des tâches au pilotage de l''avancement.', modules='Planification : tâches, durées et jalons; Ressources et diagramme de Gantt; Suivi d''avancement et reporting', duree='7H', mode='hybride',
  tarif_presentiel=55000, tarif_en_ligne=30000, tarif_hybride=0, frais_inscription=0,
  date_debut='2026-08-29', date_fin='2026-08-29', mois='Août', annee=2026, session_label='Session du 29 août 2026',
  statut='active', is_samedi_pro=1, public_cible='Chefs de projet, responsables d''équipe, ingénieurs, coordinateurs d''ONG, entrepreneurs et étudiants impliqués dans la conduite de projets. Adapté à tous les secteurs d''activité.', updated_at=NOW()
 WHERE code='IBIG-20260829';
INSERT INTO formations
  (titre, slug, domaine, type_certificat, description, objectifs, modules, duree, mode,
   tarif_presentiel, tarif_en_ligne, tarif_hybride, frais_inscription, paiement_lien,
   date_debut, date_fin, mois, annee, session_label, statut, is_samedi_pro, code, public_cible,
   created_at, updated_at)
SELECT 'Microsoft Project', 'microsoft-project', 'Gestion de Projet', 'Samedi Pro', 'Samedi Pro — Planification : tâches, durées et jalons, Ressources et diagramme de Gantt, Suivi d''avancement et reporting.', 'Rendre le participant capable de planifier, structurer et suivre un projet complet avec Microsoft Project, du découpage des tâches au pilotage de l''avancement.', 'Planification : tâches, durées et jalons; Ressources et diagramme de Gantt; Suivi d''avancement et reporting', '7H', 'hybride',
   55000, 30000, 0, 0, '',
   '2026-08-29', '2026-08-29', 'Août', 2026, 'Session du 29 août 2026', 'active', 1, 'IBIG-20260829', 'Chefs de projet, responsables d''équipe, ingénieurs, coordinateurs d''ONG, entrepreneurs et étudiants impliqués dans la conduite de projets. Adapté à tous les secteurs d''activité.',
   NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM (SELECT id FROM formations WHERE code = 'IBIG-20260829') AS x);

-- Session du 7 septembre 2026 — Fiscalité Pratique & Déclarations DGI (Formation Certifiante)
UPDATE formations SET
  titre='Fiscalité Pratique & Déclarations DGI', slug='fiscalite-pratique-et-declarations-dgi', domaine='Fiscalité', type_certificat='Formation Certifiante',
  description='Formation Certifiante — Panorama du système fiscal ivoirien, TVA : mécanisme et calcul pratique, Impôt sur les Sociétés (IS), Patente et fiscalité locale, Facture Normalisée Électronique (FNE) & Reçu Normalisé Électronique (RNE), Liasse fiscale et contrôle fiscal.', objectifs='Rendre les participants capables de préparer, calculer et transmettre les principales déclarations fiscales d''une entreprise de l''espace OHADA conformément aux exigences de la DGI. Développer une posture de vigilance face au risque fiscal.', modules='Panorama du système fiscal ivoirien; TVA : mécanisme et calcul pratique; Impôt sur les Sociétés (IS); Patente et fiscalité locale; Facture Normalisée Électronique (FNE) & Reçu Normalisé Électronique (RNE); Liasse fiscale et contrôle fiscal', duree='20H', mode='hybride',
  tarif_presentiel=225000, tarif_en_ligne=200000, tarif_hybride=0, frais_inscription=50000,
  date_debut='2026-09-07', date_fin='2026-09-07', mois='Septembre', annee=2026, session_label='Session du 7 septembre 2026',
  statut='active', is_samedi_pro=0, public_cible='Comptables, aides-comptables, gestionnaires, chefs d''entreprise et étudiants en comptabilité-finance souhaitant maîtriser la fiscalité appliquée ivoirienne. La formation convient aussi aux entrepreneurs qui gèrent eux-mêmes leurs obligations fiscales.', updated_at=NOW()
 WHERE code='IBIG-20260907';
INSERT INTO formations
  (titre, slug, domaine, type_certificat, description, objectifs, modules, duree, mode,
   tarif_presentiel, tarif_en_ligne, tarif_hybride, frais_inscription, paiement_lien,
   date_debut, date_fin, mois, annee, session_label, statut, is_samedi_pro, code, public_cible,
   created_at, updated_at)
SELECT 'Fiscalité Pratique & Déclarations DGI', 'fiscalite-pratique-et-declarations-dgi', 'Fiscalité', 'Formation Certifiante', 'Formation Certifiante — Panorama du système fiscal ivoirien, TVA : mécanisme et calcul pratique, Impôt sur les Sociétés (IS), Patente et fiscalité locale, Facture Normalisée Électronique (FNE) & Reçu Normalisé Électronique (RNE), Liasse fiscale et contrôle fiscal.', 'Rendre les participants capables de préparer, calculer et transmettre les principales déclarations fiscales d''une entreprise de l''espace OHADA conformément aux exigences de la DGI. Développer une posture de vigilance face au risque fiscal.', 'Panorama du système fiscal ivoirien; TVA : mécanisme et calcul pratique; Impôt sur les Sociétés (IS); Patente et fiscalité locale; Facture Normalisée Électronique (FNE) & Reçu Normalisé Électronique (RNE); Liasse fiscale et contrôle fiscal', '20H', 'hybride',
   225000, 200000, 0, 50000, '',
   '2026-09-07', '2026-09-07', 'Septembre', 2026, 'Session du 7 septembre 2026', 'active', 0, 'IBIG-20260907', 'Comptables, aides-comptables, gestionnaires, chefs d''entreprise et étudiants en comptabilité-finance souhaitant maîtriser la fiscalité appliquée ivoirienne. La formation convient aussi aux entrepreneurs qui gèrent eux-mêmes leurs obligations fiscales.',
   NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM (SELECT id FROM formations WHERE code = 'IBIG-20260907') AS x);

-- Session du 14 septembre 2026 — Droit du Travail & Relations Sociales (Formation Certifiante)
UPDATE formations SET
  titre='Droit du Travail & Relations Sociales', slug='droit-du-travail-et-relations-sociales', domaine='Droit Social', type_certificat='Formation Certifiante',
  description='Formation Certifiante — Le Code du Travail ivoirien : cadre et sources, Le contrat de travail : de l''embauche à la rupture, Congés, repos et protection du salarié, Contentieux et procédures devant l''Inspection du Travail.', objectifs='Doter les participants d''une maîtrise pratique du droit du travail ivoirien afin de sécuriser la relation employeur-salarié et de prévenir les contentieux sociaux.', modules='Le Code du Travail ivoirien : cadre et sources; Le contrat de travail : de l''embauche à la rupture; Congés, repos et protection du salarié; Contentieux et procédures devant l''Inspection du Travail', duree='20H', mode='hybride',
  tarif_presentiel=225000, tarif_en_ligne=200000, tarif_hybride=0, frais_inscription=50000,
  date_debut='2026-09-14', date_fin='2026-09-14', mois='Septembre', annee=2026, session_label='Session du 14 septembre 2026',
  statut='active', is_samedi_pro=0, public_cible='Responsables et assistants RH, managers, chefs d''entreprise, gestionnaires administratifs, juristes juniors et consultants souhaitant sécuriser les relations sociales. Toute personne appelée à gérer des salariés en Côte d''Ivoire.', updated_at=NOW()
 WHERE code='IBIG-20260914';
INSERT INTO formations
  (titre, slug, domaine, type_certificat, description, objectifs, modules, duree, mode,
   tarif_presentiel, tarif_en_ligne, tarif_hybride, frais_inscription, paiement_lien,
   date_debut, date_fin, mois, annee, session_label, statut, is_samedi_pro, code, public_cible,
   created_at, updated_at)
SELECT 'Droit du Travail & Relations Sociales', 'droit-du-travail-et-relations-sociales', 'Droit Social', 'Formation Certifiante', 'Formation Certifiante — Le Code du Travail ivoirien : cadre et sources, Le contrat de travail : de l''embauche à la rupture, Congés, repos et protection du salarié, Contentieux et procédures devant l''Inspection du Travail.', 'Doter les participants d''une maîtrise pratique du droit du travail ivoirien afin de sécuriser la relation employeur-salarié et de prévenir les contentieux sociaux.', 'Le Code du Travail ivoirien : cadre et sources; Le contrat de travail : de l''embauche à la rupture; Congés, repos et protection du salarié; Contentieux et procédures devant l''Inspection du Travail', '20H', 'hybride',
   225000, 200000, 0, 50000, '',
   '2026-09-14', '2026-09-14', 'Septembre', 2026, 'Session du 14 septembre 2026', 'active', 0, 'IBIG-20260914', 'Responsables et assistants RH, managers, chefs d''entreprise, gestionnaires administratifs, juristes juniors et consultants souhaitant sécuriser les relations sociales. Toute personne appelée à gérer des salariés en Côte d''Ivoire.',
   NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM (SELECT id FROM formations WHERE code = 'IBIG-20260914') AS x);

-- Session du 19 septembre 2026 — Sage 100 GESCOM (Samedi Pro)
UPDATE formations SET
  titre='Sage 100 GESCOM', slug='sage-100-gescom', domaine='Logiciels de Gestion (Sage)', type_certificat='Samedi Pro',
  description='Samedi Pro — Paramétrage et prise en main de Sage 100 GESCOM, Fiches tiers et articles, Cycle des ventes et facturation, Gestion des stocks et états.', objectifs='Rendre le participant capable d''utiliser Sage 100 GESCOM pour gérer le cycle commercial complet, de la création des pièces de vente au suivi des stocks.', modules='Paramétrage et prise en main de Sage 100 GESCOM; Fiches tiers et articles; Cycle des ventes et facturation; Gestion des stocks et états', duree='7H', mode='hybride',
  tarif_presentiel=55000, tarif_en_ligne=30000, tarif_hybride=0, frais_inscription=0,
  date_debut='2026-09-19', date_fin='2026-09-19', mois='Septembre', annee=2026, session_label='Session du 19 septembre 2026',
  statut='active', is_samedi_pro=1, public_cible='Gestionnaires, commerciaux, assistants administratifs, responsables de PME, entrepreneurs et étudiants en gestion. Idéal pour toute personne en charge de la facturation et des stocks.', updated_at=NOW()
 WHERE code='IBIG-20260919';
INSERT INTO formations
  (titre, slug, domaine, type_certificat, description, objectifs, modules, duree, mode,
   tarif_presentiel, tarif_en_ligne, tarif_hybride, frais_inscription, paiement_lien,
   date_debut, date_fin, mois, annee, session_label, statut, is_samedi_pro, code, public_cible,
   created_at, updated_at)
SELECT 'Sage 100 GESCOM', 'sage-100-gescom', 'Logiciels de Gestion (Sage)', 'Samedi Pro', 'Samedi Pro — Paramétrage et prise en main de Sage 100 GESCOM, Fiches tiers et articles, Cycle des ventes et facturation, Gestion des stocks et états.', 'Rendre le participant capable d''utiliser Sage 100 GESCOM pour gérer le cycle commercial complet, de la création des pièces de vente au suivi des stocks.', 'Paramétrage et prise en main de Sage 100 GESCOM; Fiches tiers et articles; Cycle des ventes et facturation; Gestion des stocks et états', '7H', 'hybride',
   55000, 30000, 0, 0, '',
   '2026-09-19', '2026-09-19', 'Septembre', 2026, 'Session du 19 septembre 2026', 'active', 1, 'IBIG-20260919', 'Gestionnaires, commerciaux, assistants administratifs, responsables de PME, entrepreneurs et étudiants en gestion. Idéal pour toute personne en charge de la facturation et des stocks.',
   NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM (SELECT id FROM formations WHERE code = 'IBIG-20260919') AS x);

-- Session du 21 septembre 2026 — Gestion de Projet & Planification Stratégique (Formation Certifiante)
UPDATE formations SET
  titre='Gestion de Projet & Planification Stratégique', slug='gestion-de-projet-et-planification-strategique', domaine='Gestion de Projet', type_certificat='Formation Certifiante',
  description='Formation Certifiante — Comprendre le projet et son cycle de vie, Cadrage et parties prenantes, Planification, GANTT et gestion des jalons, Suivi-évaluation et reporting, Outils digitaux et clôture de projet.', objectifs='Rendre les participants autonomes dans le pilotage d''un projet, de son cadrage à sa clôture, en s''appuyant sur des méthodologies éprouvées et des outils de planification.', modules='Comprendre le projet et son cycle de vie; Cadrage et parties prenantes; Planification, GANTT et gestion des jalons; Suivi-évaluation et reporting; Outils digitaux et clôture de projet', duree='20H', mode='hybride',
  tarif_presentiel=225000, tarif_en_ligne=200000, tarif_hybride=0, frais_inscription=50000,
  date_debut='2026-09-21', date_fin='2026-09-21', mois='Septembre', annee=2026, session_label='Session du 21 septembre 2026',
  statut='active', is_samedi_pro=0, public_cible='Chefs de projet débutants ou confirmés, coordinateurs, responsables de programme, managers, entrepreneurs et cadres appelés à conduire des initiatives. Profils du privé, du public et des ONG souhaitant professionnaliser leur pilotage.', updated_at=NOW()
 WHERE code='IBIG-20260921';
INSERT INTO formations
  (titre, slug, domaine, type_certificat, description, objectifs, modules, duree, mode,
   tarif_presentiel, tarif_en_ligne, tarif_hybride, frais_inscription, paiement_lien,
   date_debut, date_fin, mois, annee, session_label, statut, is_samedi_pro, code, public_cible,
   created_at, updated_at)
SELECT 'Gestion de Projet & Planification Stratégique', 'gestion-de-projet-et-planification-strategique', 'Gestion de Projet', 'Formation Certifiante', 'Formation Certifiante — Comprendre le projet et son cycle de vie, Cadrage et parties prenantes, Planification, GANTT et gestion des jalons, Suivi-évaluation et reporting, Outils digitaux et clôture de projet.', 'Rendre les participants autonomes dans le pilotage d''un projet, de son cadrage à sa clôture, en s''appuyant sur des méthodologies éprouvées et des outils de planification.', 'Comprendre le projet et son cycle de vie; Cadrage et parties prenantes; Planification, GANTT et gestion des jalons; Suivi-évaluation et reporting; Outils digitaux et clôture de projet', '20H', 'hybride',
   225000, 200000, 0, 50000, '',
   '2026-09-21', '2026-09-21', 'Septembre', 2026, 'Session du 21 septembre 2026', 'active', 0, 'IBIG-20260921', 'Chefs de projet débutants ou confirmés, coordinateurs, responsables de programme, managers, entrepreneurs et cadres appelés à conduire des initiatives. Profils du privé, du public et des ONG souhaitant professionnaliser leur pilotage.',
   NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM (SELECT id FROM formations WHERE code = 'IBIG-20260921') AS x);

-- Session du 28 septembre 2026 — Responsable QHSE (Formation Certifiante)
UPDATE formations SET
  titre='Responsable QHSE', slug='responsable-qhse', domaine='QHSE', type_certificat='Formation Certifiante',
  description='Formation Certifiante — Fondamentaux du management QHSE, Système de management intégré, Analyse des risques et plan de prévention, Audit HSE interne, Indicateurs, reporting et pilotage, Mise en situation et certification.', objectifs='Former des responsables capables de concevoir, déployer et auditer un système de management QHSE aligné sur les normes ISO 9001, 14001 et 45001.', modules='Fondamentaux du management QHSE; Système de management intégré; Analyse des risques et plan de prévention; Audit HSE interne; Indicateurs, reporting et pilotage; Mise en situation et certification', duree='25H', mode='hybride',
  tarif_presentiel=275000, tarif_en_ligne=250000, tarif_hybride=0, frais_inscription=50000,
  date_debut='2026-09-28', date_fin='2026-09-28', mois='Septembre', annee=2026, session_label='Session du 28 septembre 2026',
  statut='active', is_samedi_pro=0, public_cible='Animateurs et responsables QHSE, ingénieurs, techniciens, responsables de production, HSE officers et cadres souhaitant se spécialiser. Profils de l''industrie, du BTP, des mines, de la logistique et des services.', updated_at=NOW()
 WHERE code='IBIG-20260928';
INSERT INTO formations
  (titre, slug, domaine, type_certificat, description, objectifs, modules, duree, mode,
   tarif_presentiel, tarif_en_ligne, tarif_hybride, frais_inscription, paiement_lien,
   date_debut, date_fin, mois, annee, session_label, statut, is_samedi_pro, code, public_cible,
   created_at, updated_at)
SELECT 'Responsable QHSE', 'responsable-qhse', 'QHSE', 'Formation Certifiante', 'Formation Certifiante — Fondamentaux du management QHSE, Système de management intégré, Analyse des risques et plan de prévention, Audit HSE interne, Indicateurs, reporting et pilotage, Mise en situation et certification.', 'Former des responsables capables de concevoir, déployer et auditer un système de management QHSE aligné sur les normes ISO 9001, 14001 et 45001.', 'Fondamentaux du management QHSE; Système de management intégré; Analyse des risques et plan de prévention; Audit HSE interne; Indicateurs, reporting et pilotage; Mise en situation et certification', '25H', 'hybride',
   275000, 250000, 0, 50000, '',
   '2026-09-28', '2026-09-28', 'Septembre', 2026, 'Session du 28 septembre 2026', 'active', 0, 'IBIG-20260928', 'Animateurs et responsables QHSE, ingénieurs, techniciens, responsables de production, HSE officers et cadres souhaitant se spécialiser. Profils de l''industrie, du BTP, des mines, de la logistique et des services.',
   NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM (SELECT id FROM formations WHERE code = 'IBIG-20260928') AS x);

-- Session du 3 octobre 2026 — SAP FI – Comptabilité Financière (Samedi Pro)
UPDATE formations SET
  titre='SAP FI – Comptabilité Financière', slug='sap-fi-comptabilite-financiere', domaine='Logiciels de Gestion (SAP)', type_certificat='Samedi Pro',
  description='Samedi Pro — Environnement SAP et module FI, Comptabilité générale (General Ledger), Comptabilité fournisseurs (Accounts Payable), Comptabilité clients (Accounts Receivable), Clôture et états financiers.', objectifs='Rendre le participant capable de naviguer dans SAP FI et de réaliser les opérations comptables essentielles, de la saisie des écritures au suivi des comptes.', modules='Environnement SAP et module FI; Comptabilité générale (General Ledger); Comptabilité fournisseurs (Accounts Payable); Comptabilité clients (Accounts Receivable); Clôture et états financiers', duree='14H', mode='hybride',
  tarif_presentiel=75000, tarif_en_ligne=50000, tarif_hybride=0, frais_inscription=0,
  date_debut='2026-10-03', date_fin='2026-10-03', mois='Octobre', annee=2026, session_label='Session du 3 octobre 2026',
  statut='active', is_samedi_pro=1, public_cible='Comptables, aides-comptables, gestionnaires, contrôleurs de gestion, financiers et étudiants en comptabilité-finance souhaitant s''initier à SAP. Idéal pour se différencier sur le marché de l''emploi.', updated_at=NOW()
 WHERE code='IBIG-20261003';
INSERT INTO formations
  (titre, slug, domaine, type_certificat, description, objectifs, modules, duree, mode,
   tarif_presentiel, tarif_en_ligne, tarif_hybride, frais_inscription, paiement_lien,
   date_debut, date_fin, mois, annee, session_label, statut, is_samedi_pro, code, public_cible,
   created_at, updated_at)
SELECT 'SAP FI – Comptabilité Financière', 'sap-fi-comptabilite-financiere', 'Logiciels de Gestion (SAP)', 'Samedi Pro', 'Samedi Pro — Environnement SAP et module FI, Comptabilité générale (General Ledger), Comptabilité fournisseurs (Accounts Payable), Comptabilité clients (Accounts Receivable), Clôture et états financiers.', 'Rendre le participant capable de naviguer dans SAP FI et de réaliser les opérations comptables essentielles, de la saisie des écritures au suivi des comptes.', 'Environnement SAP et module FI; Comptabilité générale (General Ledger); Comptabilité fournisseurs (Accounts Payable); Comptabilité clients (Accounts Receivable); Clôture et états financiers', '14H', 'hybride',
   75000, 50000, 0, 0, '',
   '2026-10-03', '2026-10-03', 'Octobre', 2026, 'Session du 3 octobre 2026', 'active', 1, 'IBIG-20261003', 'Comptables, aides-comptables, gestionnaires, contrôleurs de gestion, financiers et étudiants en comptabilité-finance souhaitant s''initier à SAP. Idéal pour se différencier sur le marché de l''emploi.',
   NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM (SELECT id FROM formations WHERE code = 'IBIG-20261003') AS x);

-- Session du 5 octobre 2026 — Contrôle de Gestion & Analyse de Performance (Formation Certifiante)
UPDATE formations SET
  titre='Contrôle de Gestion & Analyse de Performance', slug='controle-de-gestion-et-analyse-de-performance', domaine='Contrôle de Gestion', type_certificat='Formation Certifiante',
  description='Formation Certifiante — Le contrôle de gestion et son environnement, Budget, prévisions et analyse des écarts, Analyse des coûts et des marges, Tableaux de bord et indicateurs de performance, Reporting de gestion et communication.', objectifs='Rendre le participant capable de mettre en place et d''animer un dispositif de contrôle de gestion pour piloter la performance de l''entreprise.', modules='Le contrôle de gestion et son environnement; Budget, prévisions et analyse des écarts; Analyse des coûts et des marges; Tableaux de bord et indicateurs de performance; Reporting de gestion et communication', duree='25H', mode='hybride',
  tarif_presentiel=275000, tarif_en_ligne=250000, tarif_hybride=0, frais_inscription=50000,
  date_debut='2026-10-05', date_fin='2026-10-05', mois='Octobre', annee=2026, session_label='Session du 5 octobre 2026',
  statut='active', is_samedi_pro=0, public_cible='Contrôleurs de gestion, comptables, analystes financiers, responsables administratifs et financiers, chefs de service, gérants de PME et étudiants en gestion souhaitant maîtriser le pilotage de la performance.', updated_at=NOW()
 WHERE code='IBIG-20261005';
INSERT INTO formations
  (titre, slug, domaine, type_certificat, description, objectifs, modules, duree, mode,
   tarif_presentiel, tarif_en_ligne, tarif_hybride, frais_inscription, paiement_lien,
   date_debut, date_fin, mois, annee, session_label, statut, is_samedi_pro, code, public_cible,
   created_at, updated_at)
SELECT 'Contrôle de Gestion & Analyse de Performance', 'controle-de-gestion-et-analyse-de-performance', 'Contrôle de Gestion', 'Formation Certifiante', 'Formation Certifiante — Le contrôle de gestion et son environnement, Budget, prévisions et analyse des écarts, Analyse des coûts et des marges, Tableaux de bord et indicateurs de performance, Reporting de gestion et communication.', 'Rendre le participant capable de mettre en place et d''animer un dispositif de contrôle de gestion pour piloter la performance de l''entreprise.', 'Le contrôle de gestion et son environnement; Budget, prévisions et analyse des écarts; Analyse des coûts et des marges; Tableaux de bord et indicateurs de performance; Reporting de gestion et communication', '25H', 'hybride',
   275000, 250000, 0, 50000, '',
   '2026-10-05', '2026-10-05', 'Octobre', 2026, 'Session du 5 octobre 2026', 'active', 0, 'IBIG-20261005', 'Contrôleurs de gestion, comptables, analystes financiers, responsables administratifs et financiers, chefs de service, gérants de PME et étudiants en gestion souhaitant maîtriser le pilotage de la performance.',
   NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM (SELECT id FROM formations WHERE code = 'IBIG-20261005') AS x);

-- Session du 12 octobre 2026 — Chef Comptable & Supervision Financière (Formation Certifiante)
UPDATE formations SET
  titre='Chef Comptable & Supervision Financière', slug='chef-comptable-et-supervision-financiere', domaine='Comptabilité', type_certificat='Formation Certifiante',
  description='Formation Certifiante — Le rôle du chef comptable dans l''organisation, Supervision de la comptabilité générale et analytique, Clôtures mensuelles et annuelles, Liasse fiscale et relations avec le commissaire aux comptes, Management de l''équipe comptable, Cas pratique de synthèse.', objectifs='Rendre le participant capable de superviser l''ensemble d''un service comptable et de garantir la fiabilité des états financiers dans le respect du SYSCOHADA.', modules='Le rôle du chef comptable dans l''organisation; Supervision de la comptabilité générale et analytique; Clôtures mensuelles et annuelles; Liasse fiscale et relations avec le commissaire aux comptes; Management de l''équipe comptable; Cas pratique de synthèse', duree='25H', mode='hybride',
  tarif_presentiel=275000, tarif_en_ligne=250000, tarif_hybride=0, frais_inscription=50000,
  date_debut='2026-10-12', date_fin='2026-10-12', mois='Octobre', annee=2026, session_label='Session du 12 octobre 2026',
  statut='active', is_samedi_pro=0, public_cible='Comptables confirmés, chefs comptables, responsables comptables, collaborateurs de cabinet et cadres administratifs et financiers souhaitant accéder à des fonctions de supervision comptable.', updated_at=NOW()
 WHERE code='IBIG-20261012';
INSERT INTO formations
  (titre, slug, domaine, type_certificat, description, objectifs, modules, duree, mode,
   tarif_presentiel, tarif_en_ligne, tarif_hybride, frais_inscription, paiement_lien,
   date_debut, date_fin, mois, annee, session_label, statut, is_samedi_pro, code, public_cible,
   created_at, updated_at)
SELECT 'Chef Comptable & Supervision Financière', 'chef-comptable-et-supervision-financiere', 'Comptabilité', 'Formation Certifiante', 'Formation Certifiante — Le rôle du chef comptable dans l''organisation, Supervision de la comptabilité générale et analytique, Clôtures mensuelles et annuelles, Liasse fiscale et relations avec le commissaire aux comptes, Management de l''équipe comptable, Cas pratique de synthèse.', 'Rendre le participant capable de superviser l''ensemble d''un service comptable et de garantir la fiabilité des états financiers dans le respect du SYSCOHADA.', 'Le rôle du chef comptable dans l''organisation; Supervision de la comptabilité générale et analytique; Clôtures mensuelles et annuelles; Liasse fiscale et relations avec le commissaire aux comptes; Management de l''équipe comptable; Cas pratique de synthèse', '25H', 'hybride',
   275000, 250000, 0, 50000, '',
   '2026-10-12', '2026-10-12', 'Octobre', 2026, 'Session du 12 octobre 2026', 'active', 0, 'IBIG-20261012', 'Comptables confirmés, chefs comptables, responsables comptables, collaborateurs de cabinet et cadres administratifs et financiers souhaitant accéder à des fonctions de supervision comptable.',
   NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM (SELECT id FROM formations WHERE code = 'IBIG-20261012') AS x);

-- Session du 17 octobre 2026 — Sage 100 Gestion de Caisse (Samedi Pro)
UPDATE formations SET
  titre='Sage 100 Gestion de Caisse', slug='sage-100-gestion-de-caisse', domaine='Logiciels de Gestion (Sage)', type_certificat='Samedi Pro',
  description='Samedi Pro — Paramétrer la caisse dans Sage 100, Saisir les encaissements et les décaissements, Contrôle, clôture et états de caisse.', objectifs='Permettre au participant de gérer de façon autonome et rigoureuse l''ensemble des opérations de caisse dans Sage 100, de la saisie des mouvements à la clôture et au reporting.', modules='Paramétrer la caisse dans Sage 100; Saisir les encaissements et les décaissements; Contrôle, clôture et états de caisse', duree='7H', mode='hybride',
  tarif_presentiel=55000, tarif_en_ligne=30000, tarif_hybride=0, frais_inscription=0,
  date_debut='2026-10-17', date_fin='2026-10-17', mois='Octobre', annee=2026, session_label='Session du 17 octobre 2026',
  statut='active', is_samedi_pro=1, public_cible='Caissiers, comptables, gestionnaires de trésorerie, responsables administratifs et financiers de PME, commerces et associations. Toute personne appelée à manipuler et justifier des flux de caisse.', updated_at=NOW()
 WHERE code='IBIG-20261017';
INSERT INTO formations
  (titre, slug, domaine, type_certificat, description, objectifs, modules, duree, mode,
   tarif_presentiel, tarif_en_ligne, tarif_hybride, frais_inscription, paiement_lien,
   date_debut, date_fin, mois, annee, session_label, statut, is_samedi_pro, code, public_cible,
   created_at, updated_at)
SELECT 'Sage 100 Gestion de Caisse', 'sage-100-gestion-de-caisse', 'Logiciels de Gestion (Sage)', 'Samedi Pro', 'Samedi Pro — Paramétrer la caisse dans Sage 100, Saisir les encaissements et les décaissements, Contrôle, clôture et états de caisse.', 'Permettre au participant de gérer de façon autonome et rigoureuse l''ensemble des opérations de caisse dans Sage 100, de la saisie des mouvements à la clôture et au reporting.', 'Paramétrer la caisse dans Sage 100; Saisir les encaissements et les décaissements; Contrôle, clôture et états de caisse', '7H', 'hybride',
   55000, 30000, 0, 0, '',
   '2026-10-17', '2026-10-17', 'Octobre', 2026, 'Session du 17 octobre 2026', 'active', 1, 'IBIG-20261017', 'Caissiers, comptables, gestionnaires de trésorerie, responsables administratifs et financiers de PME, commerces et associations. Toute personne appelée à manipuler et justifier des flux de caisse.',
   NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM (SELECT id FROM formations WHERE code = 'IBIG-20261017') AS x);

-- Session du 19 octobre 2026 — Gestion Immobilière Professionnelle (Formation Certifiante)
UPDATE formations SET
  titre='Gestion Immobilière Professionnelle', slug='gestion-immobiliere-professionnelle', domaine='Immobilier', type_certificat='Formation Certifiante',
  description='Formation Certifiante — Gestion locative et baux commerciaux, Transaction et techniques de vente, Promotion immobilière, Fiscalité immobilière en Côte d''Ivoire.', objectifs='Rendre les participants capables d''exercer les principaux métiers de l''immobilier — gestion locative, transaction et promotion — avec professionnalisme dans le contexte OHADA. Sécuriser leurs pratiques sur les plans juridique et fiscal.', modules='Gestion locative et baux commerciaux; Transaction et techniques de vente; Promotion immobilière; Fiscalité immobilière en Côte d''Ivoire', duree='25H', mode='hybride',
  tarif_presentiel=275000, tarif_en_ligne=250000, tarif_hybride=0, frais_inscription=50000,
  date_debut='2026-10-19', date_fin='2026-10-19', mois='Octobre', annee=2026, session_label='Session du 19 octobre 2026',
  statut='active', is_samedi_pro=0, public_cible='Agents et gestionnaires immobiliers, négociateurs, propriétaires bailleurs et personnes en reconversion vers l''immobilier. La formation s''adresse aussi aux étudiants et porteurs de projet du secteur.', updated_at=NOW()
 WHERE code='IBIG-20261019';
INSERT INTO formations
  (titre, slug, domaine, type_certificat, description, objectifs, modules, duree, mode,
   tarif_presentiel, tarif_en_ligne, tarif_hybride, frais_inscription, paiement_lien,
   date_debut, date_fin, mois, annee, session_label, statut, is_samedi_pro, code, public_cible,
   created_at, updated_at)
SELECT 'Gestion Immobilière Professionnelle', 'gestion-immobiliere-professionnelle', 'Immobilier', 'Formation Certifiante', 'Formation Certifiante — Gestion locative et baux commerciaux, Transaction et techniques de vente, Promotion immobilière, Fiscalité immobilière en Côte d''Ivoire.', 'Rendre les participants capables d''exercer les principaux métiers de l''immobilier — gestion locative, transaction et promotion — avec professionnalisme dans le contexte OHADA. Sécuriser leurs pratiques sur les plans juridique et fiscal.', 'Gestion locative et baux commerciaux; Transaction et techniques de vente; Promotion immobilière; Fiscalité immobilière en Côte d''Ivoire', '25H', 'hybride',
   275000, 250000, 0, 50000, '',
   '2026-10-19', '2026-10-19', 'Octobre', 2026, 'Session du 19 octobre 2026', 'active', 0, 'IBIG-20261019', 'Agents et gestionnaires immobiliers, négociateurs, propriétaires bailleurs et personnes en reconversion vers l''immobilier. La formation s''adresse aussi aux étudiants et porteurs de projet du secteur.',
   NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM (SELECT id FROM formations WHERE code = 'IBIG-20261019') AS x);

-- Session du 26 octobre 2026 — Prise de Parole & Communication Managériale (Formation Certifiante)
UPDATE formations SET
  titre='Prise de Parole & Communication Managériale', slug='prise-de-parole-et-communication-manageriale', domaine='Communication', type_certificat='Formation Certifiante',
  description='Formation Certifiante — Techniques avancées d''art oratoire, Communiquer en réunion et négocier, Réussir une présentation percutante, La communication au service du leadership.', objectifs='Maîtriser une communication managériale d''influence et perfectionner ses techniques de prise de parole pour fédérer, convaincre et diriger avec impact.', modules='Techniques avancées d''art oratoire; Communiquer en réunion et négocier; Réussir une présentation percutante; La communication au service du leadership', duree='25H', mode='hybride',
  tarif_presentiel=275000, tarif_en_ligne=250000, tarif_hybride=0, frais_inscription=50000,
  date_debut='2026-10-26', date_fin='2026-10-26', mois='Octobre', annee=2026, session_label='Session du 26 octobre 2026',
  statut='active', is_samedi_pro=0, public_cible='Cette formation s''adresse aux managers, chefs d''équipe, responsables et cadres dirigeants souhaitant renforcer leur impact communicationnel. Elle convient également aux professionnels en évolution vers des fonctions d''encadrement.', updated_at=NOW()
 WHERE code='IBIG-20261026';
INSERT INTO formations
  (titre, slug, domaine, type_certificat, description, objectifs, modules, duree, mode,
   tarif_presentiel, tarif_en_ligne, tarif_hybride, frais_inscription, paiement_lien,
   date_debut, date_fin, mois, annee, session_label, statut, is_samedi_pro, code, public_cible,
   created_at, updated_at)
SELECT 'Prise de Parole & Communication Managériale', 'prise-de-parole-et-communication-manageriale', 'Communication', 'Formation Certifiante', 'Formation Certifiante — Techniques avancées d''art oratoire, Communiquer en réunion et négocier, Réussir une présentation percutante, La communication au service du leadership.', 'Maîtriser une communication managériale d''influence et perfectionner ses techniques de prise de parole pour fédérer, convaincre et diriger avec impact.', 'Techniques avancées d''art oratoire; Communiquer en réunion et négocier; Réussir une présentation percutante; La communication au service du leadership', '25H', 'hybride',
   275000, 250000, 0, 50000, '',
   '2026-10-26', '2026-10-26', 'Octobre', 2026, 'Session du 26 octobre 2026', 'active', 0, 'IBIG-20261026', 'Cette formation s''adresse aux managers, chefs d''équipe, responsables et cadres dirigeants souhaitant renforcer leur impact communicationnel. Elle convient également aux professionnels en évolution vers des fonctions d''encadrement.',
   NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM (SELECT id FROM formations WHERE code = 'IBIG-20261026') AS x);

-- Session du 31 octobre 2026 — Intelligence Artificielle pour Professionnels (Samedi Pro)
UPDATE formations SET
  titre='Intelligence Artificielle pour Professionnels', slug='intelligence-artificielle-pour-professionnels', domaine='Intelligence Artificielle', type_certificat='Samedi Pro',
  description='Samedi Pro — Découvrir le paysage des IA génératives, L''art du prompt, Produire et analyser au bureau, Automatiser et rester responsable.', objectifs='Rendre le participant capable d''intégrer les principaux outils d''IA générative à son travail quotidien pour gagner en productivité, en qualité et en rapidité.', modules='Découvrir le paysage des IA génératives; L''art du prompt; Produire et analyser au bureau; Automatiser et rester responsable', duree='7H', mode='hybride',
  tarif_presentiel=55000, tarif_en_ligne=30000, tarif_hybride=0, frais_inscription=0,
  date_debut='2026-10-31', date_fin='2026-10-31', mois='Octobre', annee=2026, session_label='Session du 31 octobre 2026',
  statut='active', is_samedi_pro=1, public_cible='Cadres, entrepreneurs, assistants de direction, chargés de communication, enseignants, consultants, étudiants et tout professionnel souhaitant travailler plus vite grâce à l''IA. Aucune compétence technique requise.', updated_at=NOW()
 WHERE code='IBIG-20261031';
INSERT INTO formations
  (titre, slug, domaine, type_certificat, description, objectifs, modules, duree, mode,
   tarif_presentiel, tarif_en_ligne, tarif_hybride, frais_inscription, paiement_lien,
   date_debut, date_fin, mois, annee, session_label, statut, is_samedi_pro, code, public_cible,
   created_at, updated_at)
SELECT 'Intelligence Artificielle pour Professionnels', 'intelligence-artificielle-pour-professionnels', 'Intelligence Artificielle', 'Samedi Pro', 'Samedi Pro — Découvrir le paysage des IA génératives, L''art du prompt, Produire et analyser au bureau, Automatiser et rester responsable.', 'Rendre le participant capable d''intégrer les principaux outils d''IA générative à son travail quotidien pour gagner en productivité, en qualité et en rapidité.', 'Découvrir le paysage des IA génératives; L''art du prompt; Produire et analyser au bureau; Automatiser et rester responsable', '7H', 'hybride',
   55000, 30000, 0, 0, '',
   '2026-10-31', '2026-10-31', 'Octobre', 2026, 'Session du 31 octobre 2026', 'active', 1, 'IBIG-20261031', 'Cadres, entrepreneurs, assistants de direction, chargés de communication, enseignants, consultants, étudiants et tout professionnel souhaitant travailler plus vite grâce à l''IA. Aucune compétence technique requise.',
   NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM (SELECT id FROM formations WHERE code = 'IBIG-20261031') AS x);

-- Session du 2 novembre 2026 — Gestion de la Paie & Administration du Personnel (Formation Certifiante)
UPDATE formations SET
  titre='Gestion de la Paie & Administration du Personnel', slug='gestion-de-la-paie-et-administration-du-personnel', domaine='RH & Paie', type_certificat='Formation Certifiante',
  description='Formation Certifiante — Cadre de la paie et calcul des salaires, Le bulletin de paie et les charges sociales, Déclarations sociales et fiscales, Administration du personnel, Procédures RH et outils de paie.', objectifs='Rendre les participants capables de gérer l''intégralité du processus de paie et l''administration du personnel conformément à la réglementation sociale ivoirienne.', modules='Cadre de la paie et calcul des salaires; Le bulletin de paie et les charges sociales; Déclarations sociales et fiscales; Administration du personnel; Procédures RH et outils de paie', duree='20H', mode='hybride',
  tarif_presentiel=225000, tarif_en_ligne=200000, tarif_hybride=0, frais_inscription=50000,
  date_debut='2026-11-02', date_fin='2026-11-02', mois='Novembre', annee=2026, session_label='Session du 2 novembre 2026',
  statut='active', is_samedi_pro=0, public_cible='Gestionnaires et assistants de paie, collaborateurs RH, comptables, secrétaires administratifs et entrepreneurs gérant des salariés. Toute personne souhaitant se professionnaliser en paie ivoirienne.', updated_at=NOW()
 WHERE code='IBIG-20261102';
INSERT INTO formations
  (titre, slug, domaine, type_certificat, description, objectifs, modules, duree, mode,
   tarif_presentiel, tarif_en_ligne, tarif_hybride, frais_inscription, paiement_lien,
   date_debut, date_fin, mois, annee, session_label, statut, is_samedi_pro, code, public_cible,
   created_at, updated_at)
SELECT 'Gestion de la Paie & Administration du Personnel', 'gestion-de-la-paie-et-administration-du-personnel', 'RH & Paie', 'Formation Certifiante', 'Formation Certifiante — Cadre de la paie et calcul des salaires, Le bulletin de paie et les charges sociales, Déclarations sociales et fiscales, Administration du personnel, Procédures RH et outils de paie.', 'Rendre les participants capables de gérer l''intégralité du processus de paie et l''administration du personnel conformément à la réglementation sociale ivoirienne.', 'Cadre de la paie et calcul des salaires; Le bulletin de paie et les charges sociales; Déclarations sociales et fiscales; Administration du personnel; Procédures RH et outils de paie', '20H', 'hybride',
   225000, 200000, 0, 50000, '',
   '2026-11-02', '2026-11-02', 'Novembre', 2026, 'Session du 2 novembre 2026', 'active', 0, 'IBIG-20261102', 'Gestionnaires et assistants de paie, collaborateurs RH, comptables, secrétaires administratifs et entrepreneurs gérant des salariés. Toute personne souhaitant se professionnaliser en paie ivoirienne.',
   NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM (SELECT id FROM formations WHERE code = 'IBIG-20261102') AS x);

-- Session du 9 novembre 2026 — Assistant Comptable & SYSCOHADA (Formation Certifiante)
UPDATE formations SET
  titre='Assistant Comptable & SYSCOHADA', slug='assistant-comptable-et-syscohada', domaine='Comptabilité', type_certificat='Formation Certifiante',
  description='Formation Certifiante — Saisie comptable et journaux SYSCOHADA, Rapprochement, lettrage et gestion des tiers, Déclarations fiscales de base.', objectifs='Rendre le participant autonome dans la tenue quotidienne d''une comptabilité conforme au SYSCOHADA et dans la préparation des déclarations fiscales de base.', modules='Saisie comptable et journaux SYSCOHADA; Rapprochement, lettrage et gestion des tiers; Déclarations fiscales de base', duree='20H', mode='hybride',
  tarif_presentiel=225000, tarif_en_ligne=200000, tarif_hybride=0, frais_inscription=50000,
  date_debut='2026-11-09', date_fin='2026-11-09', mois='Novembre', annee=2026, session_label='Session du 9 novembre 2026',
  statut='active', is_samedi_pro=0, public_cible='Jeunes diplômés, aides-comptables, assistants administratifs, personnes en reconversion, entrepreneurs souhaitant tenir leur comptabilité et étudiants visant un premier emploi en comptabilité.', updated_at=NOW()
 WHERE code='IBIG-20261109';
INSERT INTO formations
  (titre, slug, domaine, type_certificat, description, objectifs, modules, duree, mode,
   tarif_presentiel, tarif_en_ligne, tarif_hybride, frais_inscription, paiement_lien,
   date_debut, date_fin, mois, annee, session_label, statut, is_samedi_pro, code, public_cible,
   created_at, updated_at)
SELECT 'Assistant Comptable & SYSCOHADA', 'assistant-comptable-et-syscohada', 'Comptabilité', 'Formation Certifiante', 'Formation Certifiante — Saisie comptable et journaux SYSCOHADA, Rapprochement, lettrage et gestion des tiers, Déclarations fiscales de base.', 'Rendre le participant autonome dans la tenue quotidienne d''une comptabilité conforme au SYSCOHADA et dans la préparation des déclarations fiscales de base.', 'Saisie comptable et journaux SYSCOHADA; Rapprochement, lettrage et gestion des tiers; Déclarations fiscales de base', '20H', 'hybride',
   225000, 200000, 0, 50000, '',
   '2026-11-09', '2026-11-09', 'Novembre', 2026, 'Session du 9 novembre 2026', 'active', 0, 'IBIG-20261109', 'Jeunes diplômés, aides-comptables, assistants administratifs, personnes en reconversion, entrepreneurs souhaitant tenir leur comptabilité et étudiants visant un premier emploi en comptabilité.',
   NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM (SELECT id FROM formations WHERE code = 'IBIG-20261109') AS x);

-- Session du 16 novembre 2026 — Responsable Commercial & Marketing Opérationnel (Formation Certifiante)
UPDATE formations SET
  titre='Responsable Commercial & Marketing Opérationnel', slug='responsable-commercial-et-marketing-operationnel', domaine='Commerce & Marketing', type_certificat='Formation Certifiante',
  description='Formation Certifiante — Bâtir sa stratégie commerciale, Vendre et négocier avec méthode, Le marketing opérationnel au quotidien, Manager son équipe commerciale.', objectifs='Maîtriser le pilotage commercial et le marketing opérationnel pour développer les ventes, animer un marché et manager une équipe orientée performance.', modules='Bâtir sa stratégie commerciale; Vendre et négocier avec méthode; Le marketing opérationnel au quotidien; Manager son équipe commerciale', duree='25H', mode='hybride',
  tarif_presentiel=275000, tarif_en_ligne=250000, tarif_hybride=0, frais_inscription=50000,
  date_debut='2026-11-16', date_fin='2026-11-16', mois='Novembre', annee=2026, session_label='Session du 16 novembre 2026',
  statut='active', is_samedi_pro=0, public_cible='Cette formation s''adresse aux responsables commerciaux, chefs des ventes, commerciaux confirmés et entrepreneurs souhaitant structurer leur démarche commerciale et marketing. Elle convient aussi aux professionnels visant une fonction de responsable commercial.', updated_at=NOW()
 WHERE code='IBIG-20261116';
INSERT INTO formations
  (titre, slug, domaine, type_certificat, description, objectifs, modules, duree, mode,
   tarif_presentiel, tarif_en_ligne, tarif_hybride, frais_inscription, paiement_lien,
   date_debut, date_fin, mois, annee, session_label, statut, is_samedi_pro, code, public_cible,
   created_at, updated_at)
SELECT 'Responsable Commercial & Marketing Opérationnel', 'responsable-commercial-et-marketing-operationnel', 'Commerce & Marketing', 'Formation Certifiante', 'Formation Certifiante — Bâtir sa stratégie commerciale, Vendre et négocier avec méthode, Le marketing opérationnel au quotidien, Manager son équipe commerciale.', 'Maîtriser le pilotage commercial et le marketing opérationnel pour développer les ventes, animer un marché et manager une équipe orientée performance.', 'Bâtir sa stratégie commerciale; Vendre et négocier avec méthode; Le marketing opérationnel au quotidien; Manager son équipe commerciale', '25H', 'hybride',
   275000, 250000, 0, 50000, '',
   '2026-11-16', '2026-11-16', 'Novembre', 2026, 'Session du 16 novembre 2026', 'active', 0, 'IBIG-20261116', 'Cette formation s''adresse aux responsables commerciaux, chefs des ventes, commerciaux confirmés et entrepreneurs souhaitant structurer leur démarche commerciale et marketing. Elle convient aussi aux professionnels visant une fonction de responsable commercial.',
   NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM (SELECT id FROM formations WHERE code = 'IBIG-20261116') AS x);

-- Session du 21 novembre 2026 — Sage États Comptables & Fiscaux (Samedi Pro)
UPDATE formations SET
  titre='Sage États Comptables & Fiscaux', slug='sage-etats-comptables-et-fiscaux', domaine='Logiciels de Gestion (Sage)', type_certificat='Samedi Pro',
  description='Samedi Pro — Le module États comptables et fiscaux et la balance source, Générer le bilan et le compte de résultat, Liasse fiscale et export.', objectifs='Permettre au participant de produire de façon autonome et conforme les états comptables et fiscaux réglementaires à partir de Sage, dans le respect du référentiel SYSCOHADA révisé.', modules='Le module États comptables et fiscaux et la balance source; Générer le bilan et le compte de résultat; Liasse fiscale et export', duree='7H', mode='hybride',
  tarif_presentiel=55000, tarif_en_ligne=30000, tarif_hybride=0, frais_inscription=0,
  date_debut='2026-11-21', date_fin='2026-11-21', mois='Novembre', annee=2026, session_label='Session du 21 novembre 2026',
  statut='active', is_samedi_pro=1, public_cible='Comptables, chefs comptables, collaborateurs de cabinets d''expertise, responsables financiers et fiscalistes. Toute personne impliquée dans la production des états financiers et déclarations fiscales.', updated_at=NOW()
 WHERE code='IBIG-20261121';
INSERT INTO formations
  (titre, slug, domaine, type_certificat, description, objectifs, modules, duree, mode,
   tarif_presentiel, tarif_en_ligne, tarif_hybride, frais_inscription, paiement_lien,
   date_debut, date_fin, mois, annee, session_label, statut, is_samedi_pro, code, public_cible,
   created_at, updated_at)
SELECT 'Sage États Comptables & Fiscaux', 'sage-etats-comptables-et-fiscaux', 'Logiciels de Gestion (Sage)', 'Samedi Pro', 'Samedi Pro — Le module États comptables et fiscaux et la balance source, Générer le bilan et le compte de résultat, Liasse fiscale et export.', 'Permettre au participant de produire de façon autonome et conforme les états comptables et fiscaux réglementaires à partir de Sage, dans le respect du référentiel SYSCOHADA révisé.', 'Le module États comptables et fiscaux et la balance source; Générer le bilan et le compte de résultat; Liasse fiscale et export', '7H', 'hybride',
   55000, 30000, 0, 0, '',
   '2026-11-21', '2026-11-21', 'Novembre', 2026, 'Session du 21 novembre 2026', 'active', 1, 'IBIG-20261121', 'Comptables, chefs comptables, collaborateurs de cabinets d''expertise, responsables financiers et fiscalistes. Toute personne impliquée dans la production des états financiers et déclarations fiscales.',
   NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM (SELECT id FROM formations WHERE code = 'IBIG-20261121') AS x);

-- Session du 23 novembre 2026 — Transport, Logistique & Supply Chain Management (Formation Certifiante)
UPDATE formations SET
  titre='Transport, Logistique & Supply Chain Management', slug='transport-logistique-et-supply-chain-management', domaine='Logistique & SCM', type_certificat='Formation Certifiante',
  description='Formation Certifiante — Organisation du transport et modes de livraison, Incoterms 2020, douane et commerce international, Gestion des stocks, entrepôts et flux physiques, Approvisionnement et relations fournisseurs, Pilotage de la chaîne logistique (SCM) et digitalisation, Cas de synthèse et certification.', objectifs='Rendre le participant capable d''organiser, gérer et optimiser une chaîne logistique complète, du choix du mode de transport au pilotage global des flux, dans un contexte ouest-africain.', modules='Organisation du transport et modes de livraison; Incoterms 2020, douane et commerce international; Gestion des stocks, entrepôts et flux physiques; Approvisionnement et relations fournisseurs; Pilotage de la chaîne logistique (SCM) et digitalisation; Cas de synthèse et certification', duree='25H', mode='hybride',
  tarif_presentiel=275000, tarif_en_ligne=250000, tarif_hybride=0, frais_inscription=50000,
  date_debut='2026-11-23', date_fin='2026-11-23', mois='Novembre', annee=2026, session_label='Session du 23 novembre 2026',
  statut='active', is_samedi_pro=0, public_cible='Professionnels de la logistique, du transport, des achats, de la distribution ou de la production souhaitant monter en compétence. Également ouvert aux étudiants et jeunes diplômés visant une carrière dans la Supply Chain.', updated_at=NOW()
 WHERE code='IBIG-20261123';
INSERT INTO formations
  (titre, slug, domaine, type_certificat, description, objectifs, modules, duree, mode,
   tarif_presentiel, tarif_en_ligne, tarif_hybride, frais_inscription, paiement_lien,
   date_debut, date_fin, mois, annee, session_label, statut, is_samedi_pro, code, public_cible,
   created_at, updated_at)
SELECT 'Transport, Logistique & Supply Chain Management', 'transport-logistique-et-supply-chain-management', 'Logistique & SCM', 'Formation Certifiante', 'Formation Certifiante — Organisation du transport et modes de livraison, Incoterms 2020, douane et commerce international, Gestion des stocks, entrepôts et flux physiques, Approvisionnement et relations fournisseurs, Pilotage de la chaîne logistique (SCM) et digitalisation, Cas de synthèse et certification.', 'Rendre le participant capable d''organiser, gérer et optimiser une chaîne logistique complète, du choix du mode de transport au pilotage global des flux, dans un contexte ouest-africain.', 'Organisation du transport et modes de livraison; Incoterms 2020, douane et commerce international; Gestion des stocks, entrepôts et flux physiques; Approvisionnement et relations fournisseurs; Pilotage de la chaîne logistique (SCM) et digitalisation; Cas de synthèse et certification', '25H', 'hybride',
   275000, 250000, 0, 50000, '',
   '2026-11-23', '2026-11-23', 'Novembre', 2026, 'Session du 23 novembre 2026', 'active', 0, 'IBIG-20261123', 'Professionnels de la logistique, du transport, des achats, de la distribution ou de la production souhaitant monter en compétence. Également ouvert aux étudiants et jeunes diplômés visant une carrière dans la Supply Chain.',
   NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM (SELECT id FROM formations WHERE code = 'IBIG-20261123') AS x);

-- Session du 30 novembre 2026 — Leadership & Management d'Équipe (Formation Certifiante)
UPDATE formations SET
  titre='Leadership & Management d''Équipe', slug='leadership-et-management-d-equipe', domaine='Management', type_certificat='Formation Certifiante',
  description='Formation Certifiante — Connaître son style de leadership, Motiver, déléguer et évaluer, Gérer les conflits et souder l''équipe, Piloter la performance collective.', objectifs='Développer une posture de leader et maîtriser les outils du management d''équipe pour motiver, fédérer et piloter la performance collective.', modules='Connaître son style de leadership; Motiver, déléguer et évaluer; Gérer les conflits et souder l''équipe; Piloter la performance collective', duree='25H', mode='hybride',
  tarif_presentiel=275000, tarif_en_ligne=250000, tarif_hybride=0, frais_inscription=50000,
  date_debut='2026-11-30', date_fin='2026-11-30', mois='Novembre', annee=2026, session_label='Session du 30 novembre 2026',
  statut='active', is_samedi_pro=0, public_cible='Cette formation s''adresse aux managers, chefs d''équipe, responsables de service et entrepreneurs souhaitant renforcer leur leadership. Elle convient aussi aux professionnels accédant à leurs premières fonctions d''encadrement.', updated_at=NOW()
 WHERE code='IBIG-20261130';
INSERT INTO formations
  (titre, slug, domaine, type_certificat, description, objectifs, modules, duree, mode,
   tarif_presentiel, tarif_en_ligne, tarif_hybride, frais_inscription, paiement_lien,
   date_debut, date_fin, mois, annee, session_label, statut, is_samedi_pro, code, public_cible,
   created_at, updated_at)
SELECT 'Leadership & Management d''Équipe', 'leadership-et-management-d-equipe', 'Management', 'Formation Certifiante', 'Formation Certifiante — Connaître son style de leadership, Motiver, déléguer et évaluer, Gérer les conflits et souder l''équipe, Piloter la performance collective.', 'Développer une posture de leader et maîtriser les outils du management d''équipe pour motiver, fédérer et piloter la performance collective.', 'Connaître son style de leadership; Motiver, déléguer et évaluer; Gérer les conflits et souder l''équipe; Piloter la performance collective', '25H', 'hybride',
   275000, 250000, 0, 50000, '',
   '2026-11-30', '2026-11-30', 'Novembre', 2026, 'Session du 30 novembre 2026', 'active', 0, 'IBIG-20261130', 'Cette formation s''adresse aux managers, chefs d''équipe, responsables de service et entrepreneurs souhaitant renforcer leur leadership. Elle convient aussi aux professionnels accédant à leurs premières fonctions d''encadrement.',
   NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM (SELECT id FROM formations WHERE code = 'IBIG-20261130') AS x);

-- Session du 7 décembre 2026 — Élaboration des États Financiers SYSCOHADA (Formation Certifiante)
UPDATE formations SET
  titre='Élaboration des États Financiers SYSCOHADA', slug='elaboration-des-etats-financiers-syscohada', domaine='Comptabilité', type_certificat='Formation Certifiante',
  description='Formation Certifiante — Travaux d''inventaire et régularisations, Le Bilan et le Compte de Résultat, Le TAFIRE et les flux de l''exercice, Notes annexes et liasse fiscale, Lecture et analyse des états financiers.', objectifs='Permettre aux participants de réaliser l''ensemble des travaux de clôture et d''établir des états financiers annuels conformes au SYSCOHADA révisé. Développer leur capacité à analyser ces états pour éclairer la gestion.', modules='Travaux d''inventaire et régularisations; Le Bilan et le Compte de Résultat; Le TAFIRE et les flux de l''exercice; Notes annexes et liasse fiscale; Lecture et analyse des états financiers', duree='20H', mode='hybride',
  tarif_presentiel=225000, tarif_en_ligne=200000, tarif_hybride=0, frais_inscription=50000,
  date_debut='2026-12-07', date_fin='2026-12-07', mois='Décembre', annee=2026, session_label='Session du 7 décembre 2026',
  statut='active', is_samedi_pro=0, public_cible='Comptables, chefs comptables, collaborateurs de cabinets et étudiants en comptabilité souhaitant maîtriser la clôture des comptes. La formation s''adresse aussi aux gestionnaires désireux de mieux comprendre les états financiers de leur structure.', updated_at=NOW()
 WHERE code='IBIG-20261207';
INSERT INTO formations
  (titre, slug, domaine, type_certificat, description, objectifs, modules, duree, mode,
   tarif_presentiel, tarif_en_ligne, tarif_hybride, frais_inscription, paiement_lien,
   date_debut, date_fin, mois, annee, session_label, statut, is_samedi_pro, code, public_cible,
   created_at, updated_at)
SELECT 'Élaboration des États Financiers SYSCOHADA', 'elaboration-des-etats-financiers-syscohada', 'Comptabilité', 'Formation Certifiante', 'Formation Certifiante — Travaux d''inventaire et régularisations, Le Bilan et le Compte de Résultat, Le TAFIRE et les flux de l''exercice, Notes annexes et liasse fiscale, Lecture et analyse des états financiers.', 'Permettre aux participants de réaliser l''ensemble des travaux de clôture et d''établir des états financiers annuels conformes au SYSCOHADA révisé. Développer leur capacité à analyser ces états pour éclairer la gestion.', 'Travaux d''inventaire et régularisations; Le Bilan et le Compte de Résultat; Le TAFIRE et les flux de l''exercice; Notes annexes et liasse fiscale; Lecture et analyse des états financiers', '20H', 'hybride',
   225000, 200000, 0, 50000, '',
   '2026-12-07', '2026-12-07', 'Décembre', 2026, 'Session du 7 décembre 2026', 'active', 0, 'IBIG-20261207', 'Comptables, chefs comptables, collaborateurs de cabinets et étudiants en comptabilité souhaitant maîtriser la clôture des comptes. La formation s''adresse aussi aux gestionnaires désireux de mieux comprendre les états financiers de leur structure.',
   NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM (SELECT id FROM formations WHERE code = 'IBIG-20261207') AS x);

-- Session du 12 décembre 2026 — Canva Pro & Design Marketing (Samedi Pro)
UPDATE formations SET
  titre='Canva Pro & Design Marketing', slug='canva-pro-et-design-marketing', domaine='Design & Communication', type_certificat='Samedi Pro',
  description='Samedi Pro — Prendre en main Canva Pro, Les fondamentaux du design qui accroche, Identité de marque et déclinaison des supports, Outils IA et diffusion.', objectifs='Rendre le participant capable de concevoir de façon autonome des visuels marketing professionnels et cohérents avec Canva Pro, pour l''ensemble de ses supports de communication.', modules='Prendre en main Canva Pro; Les fondamentaux du design qui accroche; Identité de marque et déclinaison des supports; Outils IA et diffusion', duree='7H', mode='hybride',
  tarif_presentiel=55000, tarif_en_ligne=30000, tarif_hybride=0, frais_inscription=0,
  date_debut='2026-12-12', date_fin='2026-12-12', mois='Décembre', annee=2026, session_label='Session du 12 décembre 2026',
  statut='active', is_samedi_pro=1, public_cible='Entrepreneurs, community managers, chargés de communication, commerçants, responsables associatifs, assistants et étudiants souhaitant produire eux-mêmes des supports visuels de qualité. Aucune compétence en graphisme requise.', updated_at=NOW()
 WHERE code='IBIG-20261212';
INSERT INTO formations
  (titre, slug, domaine, type_certificat, description, objectifs, modules, duree, mode,
   tarif_presentiel, tarif_en_ligne, tarif_hybride, frais_inscription, paiement_lien,
   date_debut, date_fin, mois, annee, session_label, statut, is_samedi_pro, code, public_cible,
   created_at, updated_at)
SELECT 'Canva Pro & Design Marketing', 'canva-pro-et-design-marketing', 'Design & Communication', 'Samedi Pro', 'Samedi Pro — Prendre en main Canva Pro, Les fondamentaux du design qui accroche, Identité de marque et déclinaison des supports, Outils IA et diffusion.', 'Rendre le participant capable de concevoir de façon autonome des visuels marketing professionnels et cohérents avec Canva Pro, pour l''ensemble de ses supports de communication.', 'Prendre en main Canva Pro; Les fondamentaux du design qui accroche; Identité de marque et déclinaison des supports; Outils IA et diffusion', '7H', 'hybride',
   55000, 30000, 0, 0, '',
   '2026-12-12', '2026-12-12', 'Décembre', 2026, 'Session du 12 décembre 2026', 'active', 1, 'IBIG-20261212', 'Entrepreneurs, community managers, chargés de communication, commerçants, responsables associatifs, assistants et étudiants souhaitant produire eux-mêmes des supports visuels de qualité. Aucune compétence en graphisme requise.',
   NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM (SELECT id FROM formations WHERE code = 'IBIG-20261212') AS x);

-- Session du 14 décembre 2026 — Gestion de Projet Humanitaire & ONG (Formation Certifiante)
UPDATE formations SET
  titre='Gestion de Projet Humanitaire & ONG', slug='gestion-de-projet-humanitaire-et-ong', domaine='Humanitaire & ONG', type_certificat='Formation Certifiante',
  description='Formation Certifiante — Cycle de projet humanitaire et logique d''intervention, Montage de dossiers de financement, Suivi-évaluation et rapportage, Gestion budgétaire et conformité ONG.', objectifs='Doter les participants des compétences nécessaires pour concevoir, financer, piloter et évaluer un projet humanitaire ou de développement au sein d''une ONG.', modules='Cycle de projet humanitaire et logique d''intervention; Montage de dossiers de financement; Suivi-évaluation et rapportage; Gestion budgétaire et conformité ONG', duree='20H', mode='hybride',
  tarif_presentiel=225000, tarif_en_ligne=200000, tarif_hybride=0, frais_inscription=50000,
  date_debut='2026-12-14', date_fin='2026-12-14', mois='Décembre', annee=2026, session_label='Session du 14 décembre 2026',
  statut='active', is_samedi_pro=0, public_cible='Chargés de projet, coordinateurs, animateurs et bénévoles d''ONG, agents de développement et étudiants visant le secteur humanitaire. Toute personne souhaitant professionnaliser son action dans la solidarité.', updated_at=NOW()
 WHERE code='IBIG-20261214';
INSERT INTO formations
  (titre, slug, domaine, type_certificat, description, objectifs, modules, duree, mode,
   tarif_presentiel, tarif_en_ligne, tarif_hybride, frais_inscription, paiement_lien,
   date_debut, date_fin, mois, annee, session_label, statut, is_samedi_pro, code, public_cible,
   created_at, updated_at)
SELECT 'Gestion de Projet Humanitaire & ONG', 'gestion-de-projet-humanitaire-et-ong', 'Humanitaire & ONG', 'Formation Certifiante', 'Formation Certifiante — Cycle de projet humanitaire et logique d''intervention, Montage de dossiers de financement, Suivi-évaluation et rapportage, Gestion budgétaire et conformité ONG.', 'Doter les participants des compétences nécessaires pour concevoir, financer, piloter et évaluer un projet humanitaire ou de développement au sein d''une ONG.', 'Cycle de projet humanitaire et logique d''intervention; Montage de dossiers de financement; Suivi-évaluation et rapportage; Gestion budgétaire et conformité ONG', '20H', 'hybride',
   225000, 200000, 0, 50000, '',
   '2026-12-14', '2026-12-14', 'Décembre', 2026, 'Session du 14 décembre 2026', 'active', 0, 'IBIG-20261214', 'Chargés de projet, coordinateurs, animateurs et bénévoles d''ONG, agents de développement et étudiants visant le secteur humanitaire. Toute personne souhaitant professionnaliser son action dans la solidarité.',
   NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM (SELECT id FROM formations WHERE code = 'IBIG-20261214') AS x);

-- Session du 21 décembre 2026 — Direction Administrative & Financière (DAF) (Formation Certifiante)
UPDATE formations SET
  titre='Direction Administrative & Financière (DAF)', slug='direction-administrative-et-financiere-daf', domaine='Finance & Direction', type_certificat='Formation Certifiante',
  description='Formation Certifiante — Rôle du DAF & pilotage stratégique de l''entreprise, Comptabilité générale & clôture des comptes (SYSCOHADA révisé), Contrôle de gestion & pilotage de la performance, Gestion de la trésorerie & relations bancaires, Fiscalité d''entreprise en Côte d''Ivoire & conformité FNE/RNE, Audit interne, contrôle interne & gestion des risques, Droit des affaires & environnement OHADA, Financement, investissement & stratégie de croissance, Leadership, management & posture de dirigeant.', objectifs='Faire du participant un Directeur Administratif et Financier complet, capable de piloter l''ensemble des fonctions financières et administratives et de contribuer à la direction stratégique de l''entreprise.', modules='Rôle du DAF & pilotage stratégique de l''entreprise; Comptabilité générale & clôture des comptes (SYSCOHADA révisé); Contrôle de gestion & pilotage de la performance; Gestion de la trésorerie & relations bancaires; Fiscalité d''entreprise en Côte d''Ivoire & conformité FNE/RNE; Audit interne, contrôle interne & gestion des risques; Droit des affaires & environnement OHADA; Financement, investissement & stratégie de croissance; Leadership, management & posture de dirigeant', duree='65H', mode='hybride',
  tarif_presentiel=375000, tarif_en_ligne=300000, tarif_hybride=0, frais_inscription=50000,
  date_debut='2026-12-21', date_fin='2026-12-21', mois='Décembre', annee=2026, session_label='Session du 21 décembre 2026',
  statut='active', is_samedi_pro=0, public_cible='Chefs comptables, responsables financiers, contrôleurs de gestion et cadres visant une fonction de DAF ou RAF. La formation intéresse aussi les dirigeants souhaitant renforcer leur pilotage financier.', updated_at=NOW()
 WHERE code='IBIG-20261221';
INSERT INTO formations
  (titre, slug, domaine, type_certificat, description, objectifs, modules, duree, mode,
   tarif_presentiel, tarif_en_ligne, tarif_hybride, frais_inscription, paiement_lien,
   date_debut, date_fin, mois, annee, session_label, statut, is_samedi_pro, code, public_cible,
   created_at, updated_at)
SELECT 'Direction Administrative & Financière (DAF)', 'direction-administrative-et-financiere-daf', 'Finance & Direction', 'Formation Certifiante', 'Formation Certifiante — Rôle du DAF & pilotage stratégique de l''entreprise, Comptabilité générale & clôture des comptes (SYSCOHADA révisé), Contrôle de gestion & pilotage de la performance, Gestion de la trésorerie & relations bancaires, Fiscalité d''entreprise en Côte d''Ivoire & conformité FNE/RNE, Audit interne, contrôle interne & gestion des risques, Droit des affaires & environnement OHADA, Financement, investissement & stratégie de croissance, Leadership, management & posture de dirigeant.', 'Faire du participant un Directeur Administratif et Financier complet, capable de piloter l''ensemble des fonctions financières et administratives et de contribuer à la direction stratégique de l''entreprise.', 'Rôle du DAF & pilotage stratégique de l''entreprise; Comptabilité générale & clôture des comptes (SYSCOHADA révisé); Contrôle de gestion & pilotage de la performance; Gestion de la trésorerie & relations bancaires; Fiscalité d''entreprise en Côte d''Ivoire & conformité FNE/RNE; Audit interne, contrôle interne & gestion des risques; Droit des affaires & environnement OHADA; Financement, investissement & stratégie de croissance; Leadership, management & posture de dirigeant', '65H', 'hybride',
   375000, 300000, 0, 50000, '',
   '2026-12-21', '2026-12-21', 'Décembre', 2026, 'Session du 21 décembre 2026', 'active', 0, 'IBIG-20261221', 'Chefs comptables, responsables financiers, contrôleurs de gestion et cadres visant une fonction de DAF ou RAF. La formation intéresse aussi les dirigeants souhaitant renforcer leur pilotage financier.',
   NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM (SELECT id FROM formations WHERE code = 'IBIG-20261221') AS x);

-- Session du 28 décembre 2026 — Entrepreneuriat & Création d'Entreprise (Formation Certifiante)
UPDATE formations SET
  titre='Entrepreneuriat & Création d''Entreprise', slug='entrepreneuriat-et-creation-d-entreprise', domaine='Entrepreneuriat', type_certificat='Formation Certifiante',
  description='Formation Certifiante — De l''idée au projet, Étude de marché et business plan, Cadre juridique et fiscal de la création en Côte d''Ivoire, Financement et accès aux ressources, Lancement et premiers pas.', objectifs='Accompagner les participants dans la transformation d''une idée en projet d''entreprise structuré, viable et finançable dans le contexte OHADA. Développer leur posture et leurs réflexes d''entrepreneur.', modules='De l''idée au projet; Étude de marché et business plan; Cadre juridique et fiscal de la création en Côte d''Ivoire; Financement et accès aux ressources; Lancement et premiers pas', duree='20H', mode='hybride',
  tarif_presentiel=225000, tarif_en_ligne=200000, tarif_hybride=0, frais_inscription=50000,
  date_debut='2026-12-28', date_fin='2026-12-28', mois='Décembre', annee=2026, session_label='Session du 28 décembre 2026',
  statut='active', is_samedi_pro=0, public_cible='Porteurs de projet, futurs entrepreneurs, dirigeants de très petites entreprises et étudiants souhaitant créer leur activité. La formation convient à toute personne désireuse de structurer une idée en projet viable.', updated_at=NOW()
 WHERE code='IBIG-20261228';
INSERT INTO formations
  (titre, slug, domaine, type_certificat, description, objectifs, modules, duree, mode,
   tarif_presentiel, tarif_en_ligne, tarif_hybride, frais_inscription, paiement_lien,
   date_debut, date_fin, mois, annee, session_label, statut, is_samedi_pro, code, public_cible,
   created_at, updated_at)
SELECT 'Entrepreneuriat & Création d''Entreprise', 'entrepreneuriat-et-creation-d-entreprise', 'Entrepreneuriat', 'Formation Certifiante', 'Formation Certifiante — De l''idée au projet, Étude de marché et business plan, Cadre juridique et fiscal de la création en Côte d''Ivoire, Financement et accès aux ressources, Lancement et premiers pas.', 'Accompagner les participants dans la transformation d''une idée en projet d''entreprise structuré, viable et finançable dans le contexte OHADA. Développer leur posture et leurs réflexes d''entrepreneur.', 'De l''idée au projet; Étude de marché et business plan; Cadre juridique et fiscal de la création en Côte d''Ivoire; Financement et accès aux ressources; Lancement et premiers pas', '20H', 'hybride',
   225000, 200000, 0, 50000, '',
   '2026-12-28', '2026-12-28', 'Décembre', 2026, 'Session du 28 décembre 2026', 'active', 0, 'IBIG-20261228', 'Porteurs de projet, futurs entrepreneurs, dirigeants de très petites entreprises et étudiants souhaitant créer leur activité. La formation convient à toute personne désireuse de structurer une idée en projet viable.',
   NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM (SELECT id FROM formations WHERE code = 'IBIG-20261228') AS x);

