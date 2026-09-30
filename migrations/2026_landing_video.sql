-- =====================================================================
-- IBIG EDUFORM — Espace vidéo (optionnel) sur les pages détaillées
-- À exécuter UNE FOIS dans phpMyAdmin (base eduform).
-- video_url accepte : un lien YouTube, un lien Vimeo, ou l'URL d'un
-- fichier .mp4 (ex. /uploads/landing-videos/ma-video.mp4).
-- =====================================================================
ALTER TABLE formation_landings
  ADD COLUMN IF NOT EXISTS video_url VARCHAR(255) NULL;
