-- Migration : ajout colonne ibig_ref dans paiements_inscription
-- Exécuter une seule fois sur la base de données ibig-eduform.com

ALTER TABLE paiements_inscription
  ADD COLUMN IF NOT EXISTS ibig_ref VARCHAR(60) NULL DEFAULT NULL COMMENT 'Code affilié IBIG PARTNERS (AFF-XXXX-001)';

-- Index pour retrouver rapidement les ventes d'un partenaire
CREATE INDEX IF NOT EXISTS idx_paiements_ibig_ref ON paiements_inscription (ibig_ref);
