-- =====================================================================
-- IBIG EDUFORM — Reçu / email d'inscription : drapeau d'idempotence
-- À exécuter UNE FOIS dans phpMyAdmin (base eduform).
-- =====================================================================
ALTER TABLE paiements_inscription
  ADD COLUMN IF NOT EXISTS email_sent TINYINT(1) NOT NULL DEFAULT 0 AFTER paid_at;
