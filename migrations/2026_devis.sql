-- IBIG EDUFORM — Devis / Proforma
-- Migration idempotente

-- ─────────────────────────────────────────────────────────────────────────────
-- 1. Table principale des devis
-- ─────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS devis (
  id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

  -- Référence lisible unique : DEV-2026-001
  ref_devis        VARCHAR(20)  NOT NULL UNIQUE,

  -- Lien vers le TDR d'origine (NULL autorisé = devis standalone futur)
  tdr_id           BIGINT UNSIGNED DEFAULT NULL,
  tdr_ref          VARCHAR(80)  DEFAULT NULL,    -- copie de ref_doc pour lisibilité

  -- Identification client
  prospect         VARCHAR(255) NOT NULL DEFAULT '',
  contact_nom      VARCHAR(255) DEFAULT NULL,
  contact_email    VARCHAR(255) DEFAULT NULL,
  contact_tel      VARCHAR(50)  DEFAULT NULL,
  adresse_client   TEXT         DEFAULT NULL,

  -- Formation / Objet
  titre_formation  VARCHAR(400) NOT NULL,
  categorie        VARCHAR(120) DEFAULT NULL,
  duree            VARCHAR(80)  DEFAULT NULL,
  nb_participants  TINYINT UNSIGNED NOT NULL DEFAULT 1,
  fmt_tarif        ENUM('individuel','groupe','devis') NOT NULL DEFAULT 'individuel',
  mode_formation   ENUM('en_ligne','presentiel','hybride') NOT NULL DEFAULT 'presentiel',
  date_formation   VARCHAR(80)  DEFAULT NULL,    -- texte libre : "15 novembre 2026"

  -- Tarification
  prix_unitaire_ht INT UNSIGNED NOT NULL DEFAULT 0,  -- prix HT par participant
  remise_pct       TINYINT UNSIGNED NOT NULL DEFAULT 0,
  remise_motif     VARCHAR(255) DEFAULT NULL,
  tva_pct          TINYINT UNSIGNED NOT NULL DEFAULT 0,  -- 0 = exonéré, 18 = TVA normale
  montant_ht       INT UNSIGNED NOT NULL DEFAULT 0,
  montant_tva      INT UNSIGNED NOT NULL DEFAULT 0,
  montant_ttc      INT UNSIGNED NOT NULL DEFAULT 0,
  acompte_pct      TINYINT UNSIGNED NOT NULL DEFAULT 50,  -- % acompte demandé

  -- Validité & statut
  validite_jours   TINYINT UNSIGNED NOT NULL DEFAULT 30,
  date_expiration  DATE             DEFAULT NULL,
  statut           ENUM('brouillon','envoye','accepte','refuse','expire','annule')
                   NOT NULL DEFAULT 'brouillon',
  date_creation    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  date_envoi       DATETIME DEFAULT NULL,
  date_reponse     DATETIME DEFAULT NULL,

  -- Authentification QR
  qr_token         CHAR(64) NOT NULL UNIQUE,     -- SHA-256(ref+date+sel)
  qr_scans         SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  derniere_verif   DATETIME DEFAULT NULL,

  -- Notes internes (non imprimées)
  notes_internes   TEXT DEFAULT NULL,

  INDEX idx_statut     (statut),
  INDEX idx_tdr_id     (tdr_id),
  INDEX idx_prospect   (prospect(80)),
  INDEX idx_date_crea  (date_creation),
  INDEX idx_qr_token   (qr_token)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────────────────────
-- 2. Séquence auto de numérotation (compteur annuel)
-- ─────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS devis_sequence (
  annee   YEAR         NOT NULL,
  compteur MEDIUMINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (annee)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Initialiser l'année courante si absente
INSERT IGNORE INTO devis_sequence (annee, compteur) VALUES (YEAR(NOW()), 0);
