-- =====================================================================
-- IBIG EDUFORM — Programme officiel JUIN -> DECEMBRE 2026 (26 formations)
-- A importer dans phpMyAdmin (base eduform).
-- Etape 1 : desactive TOUTES les formations existantes.
-- Etape 2 : insere le nouveau programme (actif).
-- Sur (re-executable) : la desactivation epargne le programme 2026.
-- =====================================================================

-- ETAPE 0 — Ajouter la colonne public_cible si elle n'existe pas
ALTER TABLE formations ADD COLUMN IF NOT EXISTS public_cible TEXT NULL AFTER modules;

-- ETAPE 1 — Desactiver toutes les formations actuellement en base
UPDATE formations
   SET statut = 'inactive', updated_at = NOW()
 WHERE code IS NULL OR code NOT LIKE 'IBIG-2026%';

-- ETAPE 2 — Inserer le nouveau programme (JUIN -> DECEMBRE 2026)

INSERT INTO formations
  (titre, slug, domaine, type_certificat, description, objectifs, modules, duree, mode,
   tarif_presentiel, tarif_en_ligne, tarif_hybride, frais_inscription, paiement_lien,
   date_debut, date_fin, mois, annee, session_label, statut, is_samedi_pro, code, public_cible,
   created_at, updated_at)
VALUES
  ('Comptabilité & Finance 4 en 1', 'comptabilite-et-finance-4-en-1-2026-06-13', 'Comptabilité & Finance', 'Pack Premium', 'Pack Premium — Comptable Professionnel, Chef Comptable, RAF, Comptabilité des ONG & Associations (SYCEBNL).', '', 'Comptable Professionnel; Chef Comptable; RAF; Comptabilité des ONG & Associations (SYCEBNL)', '65H', 'hybride',
   475000, 400000, 0, 50000, '',
   '2026-06-13', '2026-06-13', 'Juin', 2026, 'Session du 13 juin 2026', 'active', 0, 'IBIG-20260613', 'Comptables, aides-comptables, assistants comptables, chefs comptables, gestionnaires financiers, étudiants en comptabilité-finance, auditeurs débutants, entrepreneurs et responsables administratifs.',
   NOW(), NOW());

INSERT INTO formations
  (titre, slug, domaine, type_certificat, description, objectifs, modules, duree, mode,
   tarif_presentiel, tarif_en_ligne, tarif_hybride, frais_inscription, paiement_lien,
   date_debut, date_fin, mois, annee, session_label, statut, is_samedi_pro, code, public_cible,
   created_at, updated_at)
VALUES
  ('Excel Professionnel pour l''Entreprise', 'excel-professionnel-pour-l-entreprise-2026-06-20', 'Bureautique & Data', 'Samedi Pro', 'Samedi Pro — Excel Avancé, TCD, Tableaux de bord, Reporting.', '', 'Excel Avancé; TCD; Tableaux de bord; Reporting', '7H', 'hybride',
   25000, 20000, 0, 0, '',
   '2026-06-20', '2026-06-20', 'Juin', 2026, 'Session du 20 juin 2026', 'active', 1, 'IBIG-20260620', 'Tous professionnels, comptables, RH, commerciaux, gestionnaires et étudiants.',
   NOW(), NOW());

INSERT INTO formations
  (titre, slug, domaine, type_certificat, description, objectifs, modules, duree, mode,
   tarif_presentiel, tarif_en_ligne, tarif_hybride, frais_inscription, paiement_lien,
   date_debut, date_fin, mois, annee, session_label, statut, is_samedi_pro, code, public_cible,
   created_at, updated_at)
VALUES
  ('GESCOM Business 4 en 1', 'gescom-business-4-en-1-2026-06-27', 'Commerce & Marketing', 'Pack Premium', 'Pack Premium — Gestion Commerciale, Responsable Commercial, Responsable Marketing, Responsable Communication.', '', 'Gestion Commerciale; Responsable Commercial; Responsable Marketing; Responsable Communication', '65H', 'hybride',
   475000, 400000, 0, 50000, '',
   '2026-06-27', '2026-06-27', 'Juin', 2026, 'Session du 27 juin 2026', 'active', 0, 'IBIG-20260627', 'Commerciaux, agents et responsables commerciaux, marketeurs, chargés clientèle, responsables communication, entrepreneurs, promoteurs de PME et étudiants en commerce et marketing.',
   NOW(), NOW());

INSERT INTO formations
  (titre, slug, domaine, type_certificat, description, objectifs, modules, duree, mode,
   tarif_presentiel, tarif_en_ligne, tarif_hybride, frais_inscription, paiement_lien,
   date_debut, date_fin, mois, annee, session_label, statut, is_samedi_pro, code, public_cible,
   created_at, updated_at)
VALUES
  ('Sage 100 Comptabilité', 'sage-100-comptabilite-2026-07-04', 'Logiciels de Gestion (Sage)', 'Samedi Pro', 'Samedi Pro — Paramétrage, Écritures, Journaux, États Financiers.', '', 'Paramétrage; Écritures; Journaux; États Financiers', '7H', 'hybride',
   30000, 25000, 0, 0, '',
   '2026-07-04', '2026-07-04', 'Juillet', 2026, 'Session du 4 juillet 2026', 'active', 1, 'IBIG-20260704', 'Comptables, aides-comptables, chefs comptables et RAF.',
   NOW(), NOW());

INSERT INTO formations
  (titre, slug, domaine, type_certificat, description, objectifs, modules, duree, mode,
   tarif_presentiel, tarif_en_ligne, tarif_hybride, frais_inscription, paiement_lien,
   date_debut, date_fin, mois, annee, session_label, statut, is_samedi_pro, code, public_cible,
   created_at, updated_at)
VALUES
  ('DAF Dirigeant', 'daf-dirigeant-2026-07-11', 'Finance & Direction', 'Pack Premium', 'Pack Premium — Direction Financière, Contrôle Budgétaire, Fiscalité, Trésorerie, Reporting.', '', 'Direction Financière; Contrôle Budgétaire; Fiscalité; Trésorerie; Reporting', '100H', 'hybride',
   525000, 450000, 0, 50000, '',
   '2026-07-11', '2026-07-11', 'Juillet', 2026, 'Session du 11 juillet 2026', 'active', 0, 'IBIG-20260711', 'Chefs comptables, RAF, DAF, contrôleurs de gestion, auditeurs, dirigeants d''entreprise, entrepreneurs, cadres financiers et responsables administratifs.',
   NOW(), NOW());

INSERT INTO formations
  (titre, slug, domaine, type_certificat, description, objectifs, modules, duree, mode,
   tarif_presentiel, tarif_en_ligne, tarif_hybride, frais_inscription, paiement_lien,
   date_debut, date_fin, mois, annee, session_label, statut, is_samedi_pro, code, public_cible,
   created_at, updated_at)
VALUES
  ('Sage 100 Paie & RH', 'sage-100-paie-et-rh-2026-07-18', 'Logiciels de Gestion (Sage)', 'Samedi Pro', 'Samedi Pro — Paie, CNPS, Congés, Administration du Personnel.', '', 'Paie; CNPS; Congés; Administration du Personnel', '7H', 'hybride',
   30000, 25000, 0, 0, '',
   '2026-07-18', '2026-07-18', 'Juillet', 2026, 'Session du 18 juillet 2026', 'active', 1, 'IBIG-20260718', 'RH, gestionnaires de paie et assistants RH.',
   NOW(), NOW());

INSERT INTO formations
  (titre, slug, domaine, type_certificat, description, objectifs, modules, duree, mode,
   tarif_presentiel, tarif_en_ligne, tarif_hybride, frais_inscription, paiement_lien,
   date_debut, date_fin, mois, annee, session_label, statut, is_samedi_pro, code, public_cible,
   created_at, updated_at)
VALUES
  ('GRH Expert 3 en 1', 'grh-expert-3-en-1-2026-07-25', 'Ressources Humaines', 'Pack Premium', 'Pack Premium — Gestion de la Paie, Management RH, RH Digitale.', '', 'Gestion de la Paie; Management RH; RH Digitale', '55H', 'hybride',
   425000, 350000, 0, 50000, '',
   '2026-07-25', '2026-07-25', 'Juillet', 2026, 'Session du 25 juillet 2026', 'active', 0, 'IBIG-20260725', 'Gestionnaires RH, assistants RH, responsables RH, chargés de recrutement, responsables administratifs, gestionnaires de paie, étudiants en GRH et cadres souhaitant évoluer vers les RH.',
   NOW(), NOW());

INSERT INTO formations
  (titre, slug, domaine, type_certificat, description, objectifs, modules, duree, mode,
   tarif_presentiel, tarif_en_ligne, tarif_hybride, frais_inscription, paiement_lien,
   date_debut, date_fin, mois, annee, session_label, statut, is_samedi_pro, code, public_cible,
   created_at, updated_at)
VALUES
  ('Microsoft Power BI', 'microsoft-power-bi-2026-08-01', 'Bureautique & Data', 'Samedi Pro', 'Samedi Pro — Analyse de données, Dashboards, Reporting.', '', 'Analyse de données; Dashboards; Reporting', '14H', 'hybride',
   40000, 35000, 0, 0, '',
   '2026-08-01', '2026-08-01', 'Août', 2026, 'Session du 1 août 2026', 'active', 1, 'IBIG-20260801', 'Analystes, contrôleurs de gestion, comptables, managers et consultants.',
   NOW(), NOW());

INSERT INTO formations
  (titre, slug, domaine, type_certificat, description, objectifs, modules, duree, mode,
   tarif_presentiel, tarif_en_ligne, tarif_hybride, frais_inscription, paiement_lien,
   date_debut, date_fin, mois, annee, session_label, statut, is_samedi_pro, code, public_cible,
   created_at, updated_at)
VALUES
  ('Audit & Contrôle de Gestion 4 en 1', 'audit-et-controle-de-gestion-4-en-1-2026-08-08', 'Audit & Contrôle', 'Pack Premium', 'Pack Premium — Audit Interne, Contrôle de Gestion, Audit Comptable, Gestion des Risques.', '', 'Audit Interne; Contrôle de Gestion; Audit Comptable; Gestion des Risques', '65H', 'hybride',
   425000, 350000, 0, 50000, '',
   '2026-08-08', '2026-08-08', 'Août', 2026, 'Session du 8 août 2026', 'active', 0, 'IBIG-20260808', 'Auditeurs, contrôleurs de gestion, comptables, RAF, DAF, responsables financiers, responsables contrôle interne, consultants et étudiants en finance et audit.',
   NOW(), NOW());

INSERT INTO formations
  (titre, slug, domaine, type_certificat, description, objectifs, modules, duree, mode,
   tarif_presentiel, tarif_en_ligne, tarif_hybride, frais_inscription, paiement_lien,
   date_debut, date_fin, mois, annee, session_label, statut, is_samedi_pro, code, public_cible,
   created_at, updated_at)
VALUES
  ('KoBoToolbox & Collecte de Données', 'kobotoolbox-et-collecte-de-donnees-2026-08-15', 'Collecte & Analyse de Données', 'Samedi Pro', 'Samedi Pro — Enquêtes, Collecte mobile, Analyse.', '', 'Enquêtes; Collecte mobile; Analyse', '14H', 'hybride',
   35000, 30000, 0, 0, '',
   '2026-08-15', '2026-08-15', 'Août', 2026, 'Session du 15 août 2026', 'active', 1, 'IBIG-20260815', 'ONG, consultants, statisticiens, enquêteurs et chercheurs.',
   NOW(), NOW());

INSERT INTO formations
  (titre, slug, domaine, type_certificat, description, objectifs, modules, duree, mode,
   tarif_presentiel, tarif_en_ligne, tarif_hybride, frais_inscription, paiement_lien,
   date_debut, date_fin, mois, annee, session_label, statut, is_samedi_pro, code, public_cible,
   created_at, updated_at)
VALUES
  ('Passation des Marchés Publics & Gestion des Achats 3 en 1', 'passation-des-marches-publics-et-gestion-des-achats-3-en-1-2026-08-22', 'Marchés Publics & Achats', 'Pack Premium', 'Pack Premium — Passation des Marchés Publics, Gestion des Contrats, Achats, Approvisionnements & Gestion des Fournisseurs.', '', 'Passation des Marchés Publics; Gestion des Contrats; Achats, Approvisionnements & Gestion des Fournisseurs', '55H', 'hybride',
   375000, 300000, 0, 50000, '',
   '2026-08-22', '2026-08-22', 'Août', 2026, 'Session du 22 août 2026', 'active', 0, 'IBIG-20260822', 'Responsables achats, acheteurs, logisticiens, gestionnaires de contrats, agents des collectivités et de l''État, ONG, projets financés et entreprises soumissionnaires.',
   NOW(), NOW());

INSERT INTO formations
  (titre, slug, domaine, type_certificat, description, objectifs, modules, duree, mode,
   tarif_presentiel, tarif_en_ligne, tarif_hybride, frais_inscription, paiement_lien,
   date_debut, date_fin, mois, annee, session_label, statut, is_samedi_pro, code, public_cible,
   created_at, updated_at)
VALUES
  ('Microsoft Project', 'microsoft-project-2026-08-29', 'Gestion de Projet', 'Samedi Pro', 'Samedi Pro — Planification, Gantt, Suivi de Projet.', '', 'Planification; Gantt; Suivi de Projet', '7H', 'hybride',
   30000, 25000, 0, 0, '',
   '2026-08-29', '2026-08-29', 'Août', 2026, 'Session du 29 août 2026', 'active', 1, 'IBIG-20260829', 'Chefs de projet, coordinateurs de projet et consultants.',
   NOW(), NOW());

INSERT INTO formations
  (titre, slug, domaine, type_certificat, description, objectifs, modules, duree, mode,
   tarif_presentiel, tarif_en_ligne, tarif_hybride, frais_inscription, paiement_lien,
   date_debut, date_fin, mois, annee, session_label, statut, is_samedi_pro, code, public_cible,
   created_at, updated_at)
VALUES
  ('Gestion & Management de Projets Humanitaires & ONG 3 en 1', 'gestion-et-management-de-projets-humanitaires-et-ong-3-en-1-2026-09-12', 'Humanitaire & ONG', 'Pack Premium', 'Pack Premium — Gestion de projet Humanitaire, ONG & Développement, Management de Projet.', '', 'Gestion de projet Humanitaire; ONG & Développement; Management de Projet', '55H', 'hybride',
   375000, 300000, 0, 50000, '',
   '2026-09-12', '2026-09-12', 'Septembre', 2026, 'Session du 12 septembre 2026', 'active', 0, 'IBIG-20260912', 'Gestionnaires et coordinateurs de projets, ONG, associations, consultants, agents de développement, responsables programmes et étudiants en gestion de projet.',
   NOW(), NOW());

INSERT INTO formations
  (titre, slug, domaine, type_certificat, description, objectifs, modules, duree, mode,
   tarif_presentiel, tarif_en_ligne, tarif_hybride, frais_inscription, paiement_lien,
   date_debut, date_fin, mois, annee, session_label, statut, is_samedi_pro, code, public_cible,
   created_at, updated_at)
VALUES
  ('Sage 100 GESCOM', 'sage-100-gescom-2026-09-19', 'Logiciels de Gestion (Sage)', 'Samedi Pro', 'Samedi Pro — Achats, Ventes, Stocks, Facturation.', '', 'Achats; Ventes; Stocks; Facturation', '7H', 'hybride',
   30000, 25000, 0, 0, '',
   '2026-09-19', '2026-09-19', 'Septembre', 2026, 'Session du 19 septembre 2026', 'active', 1, 'IBIG-20260919', 'Commerciaux, gestionnaires de stocks et responsables commerciaux.',
   NOW(), NOW());

INSERT INTO formations
  (titre, slug, domaine, type_certificat, description, objectifs, modules, duree, mode,
   tarif_presentiel, tarif_en_ligne, tarif_hybride, frais_inscription, paiement_lien,
   date_debut, date_fin, mois, annee, session_label, statut, is_samedi_pro, code, public_cible,
   created_at, updated_at)
VALUES
  ('QHSE Expert 4 en 1', 'qhse-expert-4-en-1-2026-09-26', 'QHSE', 'Pack Premium', 'Pack Premium — Animateur HSE, Superviseur HSE, Responsable QHSE, Auditeur ISO.', '', 'Animateur HSE; Superviseur HSE; Responsable QHSE; Auditeur ISO', '65H', 'hybride',
   425000, 350000, 0, 50000, '',
   '2026-09-26', '2026-09-26', 'Septembre', 2026, 'Session du 26 septembre 2026', 'active', 0, 'IBIG-20260926', 'Animateurs, superviseurs et responsables HSE, responsables qualité et QHSE, chefs de chantier, ingénieurs, techniciens et professionnels du BTP, de l''industrie, des mines et du pétrole.',
   NOW(), NOW());

INSERT INTO formations
  (titre, slug, domaine, type_certificat, description, objectifs, modules, duree, mode,
   tarif_presentiel, tarif_en_ligne, tarif_hybride, frais_inscription, paiement_lien,
   date_debut, date_fin, mois, annee, session_label, statut, is_samedi_pro, code, public_cible,
   created_at, updated_at)
VALUES
  ('SAP FI – Comptabilité Financière', 'sap-fi-comptabilite-financiere-2026-10-03', 'Logiciels de Gestion (SAP)', 'Samedi Pro', 'Samedi Pro — Comptabilité Générale, Clients, Fournisseurs, Reporting.', '', 'Comptabilité Générale; Clients; Fournisseurs; Reporting', '14H', 'hybride',
   50000, 40000, 0, 0, '',
   '2026-10-03', '2026-10-03', 'Octobre', 2026, 'Session du 3 octobre 2026', 'active', 1, 'IBIG-20261003', 'Comptables, chefs comptables, consultants SAP et RAF.',
   NOW(), NOW());

INSERT INTO formations
  (titre, slug, domaine, type_certificat, description, objectifs, modules, duree, mode,
   tarif_presentiel, tarif_en_ligne, tarif_hybride, frais_inscription, paiement_lien,
   date_debut, date_fin, mois, annee, session_label, statut, is_samedi_pro, code, public_cible,
   created_at, updated_at)
VALUES
  ('Comptabilité & Finance 4 en 1', 'comptabilite-et-finance-4-en-1-2026-10-10', 'Comptabilité & Finance', 'Pack Premium', 'Pack Premium — Comptable, Chef Comptable, RAF, Comptabilité ONG.', '', 'Comptable; Chef Comptable; RAF; Comptabilité ONG', '65H', 'hybride',
   425000, 350000, 0, 50000, '',
   '2026-10-10', '2026-10-10', 'Octobre', 2026, 'Session du 10 octobre 2026', 'active', 0, 'IBIG-20261010', 'Comptables, aides-comptables, assistants comptables, chefs comptables, gestionnaires financiers, étudiants en comptabilité-finance, auditeurs débutants, entrepreneurs et responsables administratifs.',
   NOW(), NOW());

INSERT INTO formations
  (titre, slug, domaine, type_certificat, description, objectifs, modules, duree, mode,
   tarif_presentiel, tarif_en_ligne, tarif_hybride, frais_inscription, paiement_lien,
   date_debut, date_fin, mois, annee, session_label, statut, is_samedi_pro, code, public_cible,
   created_at, updated_at)
VALUES
  ('Sage 100 Gestion de Caisse Décentralisée', 'sage-100-gestion-de-caisse-decentralisee-2026-10-17', 'Logiciels de Gestion (Sage)', 'Samedi Pro', 'Samedi Pro — Encaissements, Décaissements, Contrôle de caisse.', '', 'Encaissements; Décaissements; Contrôle de caisse', '7H', 'hybride',
   30000, 25000, 0, 0, '',
   '2026-10-17', '2026-10-17', 'Octobre', 2026, 'Session du 17 octobre 2026', 'active', 1, 'IBIG-20261017', 'Caissiers, comptables, trésoriers et gestionnaires financiers.',
   NOW(), NOW());

INSERT INTO formations
  (titre, slug, domaine, type_certificat, description, objectifs, modules, duree, mode,
   tarif_presentiel, tarif_en_ligne, tarif_hybride, frais_inscription, paiement_lien,
   date_debut, date_fin, mois, annee, session_label, statut, is_samedi_pro, code, public_cible,
   created_at, updated_at)
VALUES
  ('Immobilier Professionnel 3 en 1', 'immobilier-professionnel-3-en-1-2026-10-24', 'Immobilier', 'Pack Premium', 'Pack Premium — Gestion Immobilière Professionnelle, Promotion Immobilière & Montage d''Opérations, Transaction Immobilière & Techniques de Vente.', '', 'Gestion Immobilière Professionnelle; Promotion Immobilière & Montage d''Opérations; Transaction Immobilière & Techniques de Vente', '55H', 'hybride',
   375000, 300000, 0, 50000, '',
   '2026-10-24', '2026-10-24', 'Octobre', 2026, 'Session du 24 octobre 2026', 'active', 0, 'IBIG-20261024', 'Agents, gestionnaires et promoteurs immobiliers, courtiers, investisseurs, entrepreneurs immobiliers, responsables patrimoine et étudiants en immobilier.',
   NOW(), NOW());

INSERT INTO formations
  (titre, slug, domaine, type_certificat, description, objectifs, modules, duree, mode,
   tarif_presentiel, tarif_en_ligne, tarif_hybride, frais_inscription, paiement_lien,
   date_debut, date_fin, mois, annee, session_label, statut, is_samedi_pro, code, public_cible,
   created_at, updated_at)
VALUES
  ('Intelligence Artificielle pour Professionnels', 'intelligence-artificielle-pour-professionnels-2026-10-31', 'Intelligence Artificielle', 'Samedi Pro', 'Samedi Pro — Claude, ChatGPT, Gemini, Copilot, Automatisation.', '', 'Claude; ChatGPT; Gemini; Copilot; Automatisation', '7H', 'hybride',
   30000, 25000, 0, 0, '',
   '2026-10-31', '2026-10-31', 'Octobre', 2026, 'Session du 31 octobre 2026', 'active', 1, 'IBIG-20261031', 'Tous professionnels, entrepreneurs, consultants et étudiants.',
   NOW(), NOW());

INSERT INTO formations
  (titre, slug, domaine, type_certificat, description, objectifs, modules, duree, mode,
   tarif_presentiel, tarif_en_ligne, tarif_hybride, frais_inscription, paiement_lien,
   date_debut, date_fin, mois, annee, session_label, statut, is_samedi_pro, code, public_cible,
   created_at, updated_at)
VALUES
  ('DAF Dirigeant', 'daf-dirigeant-2026-11-14', 'Finance & Direction', 'Pack Premium', 'Pack Premium — Direction Financière, Contrôle Budgétaire, Fiscalité, Trésorerie.', '', 'Direction Financière; Contrôle Budgétaire; Fiscalité; Trésorerie', '100H', 'hybride',
   525000, 450000, 0, 50000, '',
   '2026-11-14', '2026-11-14', 'Novembre', 2026, 'Session du 14 novembre 2026', 'active', 0, 'IBIG-20261114', 'Chefs comptables, RAF, DAF, contrôleurs de gestion, auditeurs, dirigeants d''entreprise, entrepreneurs, cadres financiers et responsables administratifs.',
   NOW(), NOW());

INSERT INTO formations
  (titre, slug, domaine, type_certificat, description, objectifs, modules, duree, mode,
   tarif_presentiel, tarif_en_ligne, tarif_hybride, frais_inscription, paiement_lien,
   date_debut, date_fin, mois, annee, session_label, statut, is_samedi_pro, code, public_cible,
   created_at, updated_at)
VALUES
  ('Sage États Comptables & Fiscaux', 'sage-etats-comptables-et-fiscaux-2026-11-21', 'Logiciels de Gestion (Sage)', 'Samedi Pro', 'Samedi Pro — États Financiers, Déclarations Fiscales.', '', 'États Financiers; Déclarations Fiscales', '7H', 'hybride',
   30000, 25000, 0, 0, '',
   '2026-11-21', '2026-11-21', 'Novembre', 2026, 'Session du 21 novembre 2026', 'active', 1, 'IBIG-20261121', 'Comptables, chefs comptables, RAF et fiscalistes.',
   NOW(), NOW());

INSERT INTO formations
  (titre, slug, domaine, type_certificat, description, objectifs, modules, duree, mode,
   tarif_presentiel, tarif_en_ligne, tarif_hybride, frais_inscription, paiement_lien,
   date_debut, date_fin, mois, annee, session_label, statut, is_samedi_pro, code, public_cible,
   created_at, updated_at)
VALUES
  ('Responsable des RH 3 en 1', 'responsable-des-rh-3-en-1-2026-11-28', 'Ressources Humaines', 'Pack Premium', 'Pack Premium — Gestion de la Paie & Administration du Personnel, Management des Ressources Humaines, RH Digitale & SIRH.', '', 'Gestion de la Paie & Administration du Personnel; Management des Ressources Humaines; RH Digitale & SIRH', '55H', 'hybride',
   375000, 300000, 0, 50000, '',
   '2026-11-28', '2026-11-28', 'Novembre', 2026, 'Session du 28 novembre 2026', 'active', 0, 'IBIG-20261128', 'Gestionnaires RH, assistants RH, responsables RH, chargés de recrutement, responsables administratifs, gestionnaires de paie, étudiants en GRH et cadres souhaitant évoluer vers les RH.',
   NOW(), NOW());

INSERT INTO formations
  (titre, slug, domaine, type_certificat, description, objectifs, modules, duree, mode,
   tarif_presentiel, tarif_en_ligne, tarif_hybride, frais_inscription, paiement_lien,
   date_debut, date_fin, mois, annee, session_label, statut, is_samedi_pro, code, public_cible,
   created_at, updated_at)
VALUES
  ('Audit & Contrôle de Gestion 4 en 1', 'audit-et-controle-de-gestion-4-en-1-2026-12-05', 'Audit & Contrôle', 'Pack Premium', 'Pack Premium — Audit, Contrôle de Gestion, Audit Comptable, Gestion des Risques.', '', 'Audit; Contrôle de Gestion; Audit Comptable; Gestion des Risques', '65H', 'hybride',
   525000, 450000, 0, 50000, '',
   '2026-12-05', '2026-12-05', 'Décembre', 2026, 'Session du 5 décembre 2026', 'active', 0, 'IBIG-20261205', 'Auditeurs, contrôleurs de gestion, comptables, RAF, DAF, responsables financiers, responsables contrôle interne, consultants et étudiants en finance et audit.',
   NOW(), NOW());

INSERT INTO formations
  (titre, slug, domaine, type_certificat, description, objectifs, modules, duree, mode,
   tarif_presentiel, tarif_en_ligne, tarif_hybride, frais_inscription, paiement_lien,
   date_debut, date_fin, mois, annee, session_label, statut, is_samedi_pro, code, public_cible,
   created_at, updated_at)
VALUES
  ('Canva Pro & Design Marketing', 'canva-pro-et-design-marketing-2026-12-12', 'Design & Communication', 'Samedi Pro', 'Samedi Pro — Flyers, Réseaux Sociaux, Présentations, IA Canva.', '', 'Flyers; Réseaux Sociaux; Présentations; IA Canva', '7H', 'hybride',
   30000, 25000, 0, 0, '',
   '2026-12-12', '2026-12-12', 'Décembre', 2026, 'Session du 12 décembre 2026', 'active', 1, 'IBIG-20261212', 'Community managers, marketeurs, communicants et entrepreneurs.',
   NOW(), NOW());

INSERT INTO formations
  (titre, slug, domaine, type_certificat, description, objectifs, modules, duree, mode,
   tarif_presentiel, tarif_en_ligne, tarif_hybride, frais_inscription, paiement_lien,
   date_debut, date_fin, mois, annee, session_label, statut, is_samedi_pro, code, public_cible,
   created_at, updated_at)
VALUES
  ('Logistique & Supply Chain Management 4 en 1', 'logistique-et-supply-chain-management-4-en-1-2026-12-19', 'Logistique & Supply Chain', 'Pack Premium', 'Pack Premium — Gestion des Stocks, Entrepôts, Logistique, Approvisionnements.', '', 'Gestion des Stocks; Entrepôts; Logistique; Approvisionnements', '65H', 'hybride',
   525000, 450000, 0, 50000, '',
   '2026-12-19', '2026-12-19', 'Décembre', 2026, 'Session du 19 décembre 2026', 'active', 0, 'IBIG-20261219', 'Gestionnaires de stocks, magasiniers, logisticiens, responsables entrepôts, responsables achats, approvisionneurs, transporteurs, supply chain managers et étudiants en logistique.',
   NOW(), NOW());

