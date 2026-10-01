-- IBIG EDUFORM — Formulaire de satisfaction post-formation
-- Migration idempotente

CREATE TABLE IF NOT EXISTS satisfaction_reponses (
  id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  preinscription_id BIGINT UNSIGNED NOT NULL,
  email            VARCHAR(255) NOT NULL,
  formation_titre  VARCHAR(500) DEFAULT NULL,
  note_globale     TINYINT UNSIGNED NOT NULL COMMENT '1 à 5',
  note_contenu     TINYINT UNSIGNED DEFAULT NULL COMMENT '1 à 5',
  note_formateur   TINYINT UNSIGNED DEFAULT NULL COMMENT '1 à 5',
  note_logistique  TINYINT UNSIGNED DEFAULT NULL COMMENT '1 à 5',
  points_positifs  TEXT DEFAULT NULL,
  points_ameliorer TEXT DEFAULT NULL,
  recommande       TINYINT(1) DEFAULT NULL COMMENT '1=oui 0=non',
  commentaire      TEXT DEFAULT NULL,
  created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_preinsc (preinscription_id),
  INDEX idx_email   (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Token d'accès unique par apprenant/inscription pour le formulaire
CREATE TABLE IF NOT EXISTS satisfaction_tokens (
  id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  preinscription_id BIGINT UNSIGNED NOT NULL UNIQUE,
  email            VARCHAR(255) NOT NULL,
  token            CHAR(64) NOT NULL UNIQUE,
  expires_at       DATETIME NOT NULL,
  used_at          DATETIME DEFAULT NULL,
  created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_token  (token),
  INDEX idx_email  (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
