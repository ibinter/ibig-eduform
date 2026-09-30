-- =====================================================================
-- IBIG EDUFORM — Publics cibles (mise a jour, programme deja importe)
-- 100% sans risque : ajout de colonne tolerant + UPDATE cibles par code.
-- Si l'ALTER renvoie 'Duplicate column public_cible', c'est NORMAL
-- (la colonne existe deja) : ignorez et laissez tourner les UPDATE.
-- =====================================================================

ALTER TABLE formations ADD COLUMN IF NOT EXISTS public_cible TEXT NULL AFTER modules;

UPDATE formations SET public_cible = 'Comptables, aides-comptables, assistants comptables, chefs comptables, gestionnaires financiers, étudiants en comptabilité-finance, auditeurs débutants, entrepreneurs et responsables administratifs.' WHERE code = 'IBIG-20260613';
UPDATE formations SET public_cible = 'Tous professionnels, comptables, RH, commerciaux, gestionnaires et étudiants.' WHERE code = 'IBIG-20260620';
UPDATE formations SET public_cible = 'Commerciaux, agents et responsables commerciaux, marketeurs, chargés clientèle, responsables communication, entrepreneurs, promoteurs de PME et étudiants en commerce et marketing.' WHERE code = 'IBIG-20260627';
UPDATE formations SET public_cible = 'Comptables, aides-comptables, chefs comptables et RAF.' WHERE code = 'IBIG-20260704';
UPDATE formations SET public_cible = 'Chefs comptables, RAF, DAF, contrôleurs de gestion, auditeurs, dirigeants d''entreprise, entrepreneurs, cadres financiers et responsables administratifs.' WHERE code = 'IBIG-20260711';
UPDATE formations SET public_cible = 'RH, gestionnaires de paie et assistants RH.' WHERE code = 'IBIG-20260718';
UPDATE formations SET public_cible = 'Gestionnaires RH, assistants RH, responsables RH, chargés de recrutement, responsables administratifs, gestionnaires de paie, étudiants en GRH et cadres souhaitant évoluer vers les RH.' WHERE code = 'IBIG-20260725';
UPDATE formations SET public_cible = 'Analystes, contrôleurs de gestion, comptables, managers et consultants.' WHERE code = 'IBIG-20260801';
UPDATE formations SET public_cible = 'Auditeurs, contrôleurs de gestion, comptables, RAF, DAF, responsables financiers, responsables contrôle interne, consultants et étudiants en finance et audit.' WHERE code = 'IBIG-20260808';
UPDATE formations SET public_cible = 'ONG, consultants, statisticiens, enquêteurs et chercheurs.' WHERE code = 'IBIG-20260815';
UPDATE formations SET public_cible = 'Responsables achats, acheteurs, logisticiens, gestionnaires de contrats, agents des collectivités et de l''État, ONG, projets financés et entreprises soumissionnaires.' WHERE code = 'IBIG-20260822';
UPDATE formations SET public_cible = 'Chefs de projet, coordinateurs de projet et consultants.' WHERE code = 'IBIG-20260829';
UPDATE formations SET public_cible = 'Gestionnaires et coordinateurs de projets, ONG, associations, consultants, agents de développement, responsables programmes et étudiants en gestion de projet.' WHERE code = 'IBIG-20260912';
UPDATE formations SET public_cible = 'Commerciaux, gestionnaires de stocks et responsables commerciaux.' WHERE code = 'IBIG-20260919';
UPDATE formations SET public_cible = 'Animateurs, superviseurs et responsables HSE, responsables qualité et QHSE, chefs de chantier, ingénieurs, techniciens et professionnels du BTP, de l''industrie, des mines et du pétrole.' WHERE code = 'IBIG-20260926';
UPDATE formations SET public_cible = 'Comptables, chefs comptables, consultants SAP et RAF.' WHERE code = 'IBIG-20261003';
UPDATE formations SET public_cible = 'Comptables, aides-comptables, assistants comptables, chefs comptables, gestionnaires financiers, étudiants en comptabilité-finance, auditeurs débutants, entrepreneurs et responsables administratifs.' WHERE code = 'IBIG-20261010';
UPDATE formations SET public_cible = 'Caissiers, comptables, trésoriers et gestionnaires financiers.' WHERE code = 'IBIG-20261017';
UPDATE formations SET public_cible = 'Agents, gestionnaires et promoteurs immobiliers, courtiers, investisseurs, entrepreneurs immobiliers, responsables patrimoine et étudiants en immobilier.' WHERE code = 'IBIG-20261024';
UPDATE formations SET public_cible = 'Tous professionnels, entrepreneurs, consultants et étudiants.' WHERE code = 'IBIG-20261031';
UPDATE formations SET public_cible = 'Chefs comptables, RAF, DAF, contrôleurs de gestion, auditeurs, dirigeants d''entreprise, entrepreneurs, cadres financiers et responsables administratifs.' WHERE code = 'IBIG-20261114';
UPDATE formations SET public_cible = 'Comptables, chefs comptables, RAF et fiscalistes.' WHERE code = 'IBIG-20261121';
UPDATE formations SET public_cible = 'Gestionnaires RH, assistants RH, responsables RH, chargés de recrutement, responsables administratifs, gestionnaires de paie, étudiants en GRH et cadres souhaitant évoluer vers les RH.' WHERE code = 'IBIG-20261128';
UPDATE formations SET public_cible = 'Auditeurs, contrôleurs de gestion, comptables, RAF, DAF, responsables financiers, responsables contrôle interne, consultants et étudiants en finance et audit.' WHERE code = 'IBIG-20261205';
UPDATE formations SET public_cible = 'Community managers, marketeurs, communicants et entrepreneurs.' WHERE code = 'IBIG-20261212';
UPDATE formations SET public_cible = 'Gestionnaires de stocks, magasiniers, logisticiens, responsables entrepôts, responsables achats, approvisionneurs, transporteurs, supply chain managers et étudiants en logistique.' WHERE code = 'IBIG-20261219';
