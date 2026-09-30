-- =====================================================================
-- IBIG EDUFORM — Parrainage (-10% parrain & filleul)
-- À exécuter UNE FOIS dans phpMyAdmin (base eduform).
-- =====================================================================

-- Codes de parrainage (un par parrain)
CREATE TABLE IF NOT EXISTS parrainages (
  id               INT AUTO_INCREMENT PRIMARY KEY,
  code             VARCHAR(20)  NOT NULL UNIQUE,
  parrain_nom      VARCHAR(190) NOT NULL,
  parrain_contact  VARCHAR(190) NOT NULL,           -- email ou WhatsApp
  ip_address       VARCHAR(45)  NULL,
  created_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (parrain_contact)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Utilisations d'un code (un filleul qui en bénéficie)
CREATE TABLE IF NOT EXISTS parrainage_usages (
  id               INT AUTO_INCREMENT PRIMARY KEY,
  code             VARCHAR(20)  NOT NULL,
  filleul_nom      VARCHAR(190) NULL,
  filleul_contact  VARCHAR(190) NULL,
  formation_id     INT          NULL,
  formation_titre  VARCHAR(255) NULL,
  montant_base     INT          NOT NULL DEFAULT 0,  -- montant avant remise parrainage
  remise           INT          NOT NULL DEFAULT 0,  -- -10% accordé au filleul
  montant_net      INT          NOT NULL DEFAULT 0,  -- montant facturé
  reference        VARCHAR(80)  NULL,
  statut           ENUM('en_attente','paye','annule') NOT NULL DEFAULT 'en_attente',
  created_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (code),
  INDEX (statut),
  INDEX (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
