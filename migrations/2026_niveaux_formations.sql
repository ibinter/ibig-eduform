-- =====================================================================
-- IBIG EDUFORM — Système 3 niveaux par formation (catalogue)
-- Débutant · Intermédiaire · Expert
-- Idempotent — exécuter via run-migration-niveaux.php
-- Ne touche PAS aux formations du calendrier (formations.php)
-- =====================================================================

-- ─── Table : formation_niveaux ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS formation_niveaux (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  formation_id     INT UNSIGNED NOT NULL,
  niveau           ENUM('debutant','intermediaire','expert') NOT NULL,
  duree_heures     SMALLINT UNSIGNED NOT NULL DEFAULT 20,
  tarif_en_ligne   INT UNSIGNED NOT NULL DEFAULT 200000,
  tarif_presentiel INT UNSIGNED NOT NULL DEFAULT 250000,
  tarif_hybride    INT UNSIGNED NOT NULL DEFAULT 0,
  objectifs        TEXT NULL COMMENT 'Objectifs spécifiques à ce niveau',
  prerequis        TEXT NULL COMMENT 'Prérequis pour ce niveau',
  public_cible     TEXT NULL COMMENT 'Public visé pour ce niveau',
  statut           ENUM('actif','brouillon','archive') NOT NULL DEFAULT 'brouillon',
  ordre_affichage  TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '1=debutant 2=intermediaire 3=expert',
  created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_formation_niveau (formation_id, niveau),
  INDEX idx_statut (statut),
  INDEX idx_formation (formation_id),
  CONSTRAINT fk_fniveaux_formation
    FOREIGN KEY (formation_id) REFERENCES formations(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Déclinaisons par niveau de chaque formation du catalogue';

-- ─── Table : formation_niveau_modules ────────────────────────────────
CREATE TABLE IF NOT EXISTS formation_niveau_modules (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  niveau_id    INT UNSIGNED NOT NULL,
  ordre        TINYINT UNSIGNED NOT NULL DEFAULT 1,
  titre        VARCHAR(255) NOT NULL,
  contenus     TEXT NULL   COMMENT 'Contenu détaillé du module (points-clés séparés par ·)',
  duree_heures TINYINT UNSIGNED NOT NULL DEFAULT 2,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_niveau (niveau_id),
  CONSTRAINT fk_fnmodules_niveau
    FOREIGN KEY (niveau_id) REFERENCES formation_niveaux(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Modules de contenu par niveau de formation';

-- ─── Vue : catalogue avec niveaux actifs ─────────────────────────────
CREATE OR REPLACE VIEW v_catalogue_niveaux AS
SELECT
  f.id              AS formation_id,
  f.titre,
  f.slug,
  f.domaine,
  f.description,
  f.statut          AS statut_formation,
  n.id              AS niveau_id,
  n.niveau,
  n.duree_heures,
  n.tarif_en_ligne,
  n.tarif_presentiel,
  n.tarif_hybride,
  n.objectifs       AS objectifs_niveau,
  n.prerequis       AS prerequis_niveau,
  n.public_cible    AS public_niveau,
  n.statut          AS statut_niveau,
  n.ordre_affichage,
  (SELECT COUNT(*) FROM formation_niveau_modules m WHERE m.niveau_id = n.id) AS nb_modules
FROM formations f
INNER JOIN formation_niveaux n ON n.formation_id = f.id
WHERE f.statut IN ('active','inactive')
  AND n.statut = 'actif'
ORDER BY f.titre ASC, n.ordre_affichage ASC;
