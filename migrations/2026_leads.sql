-- =====================================================================
-- IBIG EDUFORM — Table des leads (capture de prospects)
-- À exécuter UNE FOIS dans phpMyAdmin (base eduform).
-- =====================================================================
CREATE TABLE IF NOT EXISTS leads (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  nom             VARCHAR(190) NOT NULL,
  contact         VARCHAR(190) NOT NULL,                 -- email ou téléphone WhatsApp
  type            ENUM('calendrier','rappel','alerte') NOT NULL DEFAULT 'rappel',
  formation_id    INT          NULL,
  formation_titre VARCHAR(255) NULL,
  ip_address      VARCHAR(45)  NULL,
  user_agent      VARCHAR(255) NULL,
  statut          ENUM('nouveau','traite') NOT NULL DEFAULT 'nouveau',
  created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (type),
  INDEX (statut),
  INDEX (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
