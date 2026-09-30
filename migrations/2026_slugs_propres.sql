-- =====================================================================
-- IBIG EDUFORM — Slugs PROPRES (basés sur le titre, sans date)
-- À exécuter dans phpMyAdmin (base eduform).
-- Les 3 titres qui reviennent (Comptabilité, DAF, Audit) reçoivent un
-- suffixe « -session-2 » sur leur 2e session (modifiable dans l'admin).
-- =====================================================================

-- JUIN
UPDATE formations SET slug='comptabilite-et-finance-4-en-1'                        WHERE code='IBIG-20260613';
UPDATE formations SET slug='excel-professionnel-pour-l-entreprise'                 WHERE code='IBIG-20260620';
UPDATE formations SET slug='gescom-business-4-en-1'                                WHERE code='IBIG-20260627';

-- JUILLET
UPDATE formations SET slug='sage-100-comptabilite'                                WHERE code='IBIG-20260704';
UPDATE formations SET slug='daf-dirigeant'                                        WHERE code='IBIG-20260711';
UPDATE formations SET slug='sage-100-paie-et-rh'                                  WHERE code='IBIG-20260718';
UPDATE formations SET slug='grh-expert-3-en-1'                                    WHERE code='IBIG-20260725';

-- AOÛT
UPDATE formations SET slug='microsoft-power-bi'                                   WHERE code='IBIG-20260801';
UPDATE formations SET slug='audit-et-controle-de-gestion-4-en-1'                  WHERE code='IBIG-20260808';
UPDATE formations SET slug='kobotoolbox-et-collecte-de-donnees'                   WHERE code='IBIG-20260815';
UPDATE formations SET slug='passation-des-marches-publics-et-gestion-des-achats-3-en-1' WHERE code='IBIG-20260822';
UPDATE formations SET slug='microsoft-project'                                    WHERE code='IBIG-20260829';

-- SEPTEMBRE
UPDATE formations SET slug='gestion-et-management-de-projets-humanitaires-et-ong-3-en-1' WHERE code='IBIG-20260912';
UPDATE formations SET slug='sage-100-gescom'                                      WHERE code='IBIG-20260919';
UPDATE formations SET slug='qhse-expert-4-en-1'                                   WHERE code='IBIG-20260926';

-- OCTOBRE
UPDATE formations SET slug='sap-fi-comptabilite-financiere'                       WHERE code='IBIG-20261003';
UPDATE formations SET slug='comptabilite-et-finance-4-en-1-session-2'             WHERE code='IBIG-20261010';
UPDATE formations SET slug='sage-100-gestion-de-caisse-decentralisee'            WHERE code='IBIG-20261017';
UPDATE formations SET slug='immobilier-professionnel-3-en-1'                      WHERE code='IBIG-20261024';
UPDATE formations SET slug='intelligence-artificielle-pour-professionnels'        WHERE code='IBIG-20261031';

-- NOVEMBRE
UPDATE formations SET slug='daf-dirigeant-session-2'                              WHERE code='IBIG-20261114';
UPDATE formations SET slug='sage-etats-comptables-et-fiscaux'                     WHERE code='IBIG-20261121';
UPDATE formations SET slug='responsable-des-rh-3-en-1'                            WHERE code='IBIG-20261128';

-- DÉCEMBRE
UPDATE formations SET slug='audit-et-controle-de-gestion-4-en-1-session-2'        WHERE code='IBIG-20261205';
UPDATE formations SET slug='canva-pro-et-design-marketing'                        WHERE code='IBIG-20261212';
UPDATE formations SET slug='logistique-et-supply-chain-management-4-en-1'         WHERE code='IBIG-20261219';
