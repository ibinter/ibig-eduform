-- =====================================================================
-- IBIG EDUFORM — Capacités : 15 places (Pack Premium) / 20 (Samedi Pro)
-- À exécuter dans phpMyAdmin (base eduform).
-- =====================================================================
UPDATE formations SET capacite = 15 WHERE code LIKE 'IBIG-2026%' AND is_samedi_pro = 0;
UPDATE formations SET capacite = 20 WHERE code LIKE 'IBIG-2026%' AND is_samedi_pro = 1;
