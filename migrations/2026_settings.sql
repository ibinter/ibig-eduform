-- =====================================================================
-- IBIG EDUFORM — Table des réglages (settings) + valeurs par défaut
-- À exécuter UNE FOIS dans phpMyAdmin (base eduform).
-- Les INSERT IGNORE ne touchent pas une clé déjà personnalisée.
-- =====================================================================
CREATE TABLE IF NOT EXISTS settings (
  setting_key   VARCHAR(190) PRIMARY KEY,
  setting_value LONGTEXT NULL,
  updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Contacts (pied de page)
INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
  ('contact_email',        'formation@intermark-business.com'),
  ('contact_phones',       '+225 27 22 27 60 14\n+225 07 78 88 25 92\n+225 05 56 21 76 16\n+225 05 65 90 47 79'),
  ('whatsapp_number',      '2250778882592'),
  ('site_institutionnel',  'intermark-business.com');

-- Chiffres clés (accueil + fiches)
INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
  ('stat_apprenants',      '1 000+'),
  ('stat_satisfaction',    '+90%'),
  ('stat_experience',      '3 ans'),
  ('stat_certifiantes',    '100%');

-- Chiffres page d'accueil (animation count-up — entiers)
INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
  ('home_apprenants',      '1000'),
  ('home_inseres',         '250'),
  ('home_pratique',        '80'),
  ('home_domaines',        '12');

-- Promotions
INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
  ('earlybird_amount',     '25000'),
  ('earlybird_percent',    '10'),
  ('earlybird_days',       '14'),
  ('referral_percent',     '10');
