-- =====================================================================
-- IBIG EDUFORM — FAQ administrable
-- À exécuter UNE FOIS dans phpMyAdmin (base eduform).
-- La table reste VIDE au départ : la page faq.php affiche alors la FAQ
-- par défaut. Utilisez le bouton « Importer la FAQ par défaut » dans
-- l'admin pour la charger et l'éditer.
-- =====================================================================
CREATE TABLE IF NOT EXISTS faq (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  question    VARCHAR(255) NOT NULL,
  reponse     TEXT NOT NULL,
  ordre       INT NOT NULL DEFAULT 0,
  actif       TINYINT(1) NOT NULL DEFAULT 1,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX (actif),
  INDEX (ordre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
