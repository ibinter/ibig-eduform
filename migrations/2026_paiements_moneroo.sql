-- =====================================================================
-- IBIG EDUFORM — Migration : suivi des paiements Moneroo
-- À exécuter UNE FOIS dans phpMyAdmin (onglet SQL).
-- Ajoute les colonnes de suivi à la table paiements_inscription.
-- =====================================================================

ALTER TABLE paiements_inscription
  ADD COLUMN provider            VARCHAR(30)  NULL AFTER statut,
  ADD COLUMN provider_payment_id VARCHAR(150) NULL AFTER provider,
  ADD COLUMN customer_name       VARCHAR(190) NULL AFTER provider_payment_id,
  ADD COLUMN customer_email      VARCHAR(190) NULL AFTER customer_name,
  ADD COLUMN customer_phone      VARCHAR(40)  NULL AFTER customer_email,
  ADD COLUMN paid_at             DATETIME     NULL AFTER customer_phone;

-- Si une colonne existe déjà, MySQL renverra une erreur sur CETTE ligne :
-- relancez en retirant la/les colonnes déjà présentes.
