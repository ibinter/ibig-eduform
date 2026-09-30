-- ESPACE APPRENANT — migration idempotente

CREATE TABLE IF NOT EXISTS apprenant_tokens (
  id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email      VARCHAR(255) NOT NULL,
  token      CHAR(64) NOT NULL UNIQUE,
  expires_at DATETIME NOT NULL,
  used_at    DATETIME DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_token (token),
  INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Nettoyage automatique des tokens expirés (event optionnel, ignoré si event scheduler off)
-- DELETE FROM apprenant_tokens WHERE expires_at < NOW() - INTERVAL 7 DAY;
