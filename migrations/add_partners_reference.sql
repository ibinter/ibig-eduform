-- Migration : colonnes pour inscriptions via IBIG PARTNERS
ALTER TABLE paiements_inscription
  ADD COLUMN IF NOT EXISTS partners_reference VARCHAR(60) NULL DEFAULT NULL COMMENT 'Référence vente IBIG PARTNERS (ex: VTE-0042)',
  ADD COLUMN IF NOT EXISTS provider VARCHAR(30) NULL DEFAULT NULL COMMENT 'Origine paiement: moneroo, partners...';

CREATE INDEX IF NOT EXISTS idx_paiements_partners_ref ON paiements_inscription (partners_reference);
