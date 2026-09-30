-- =====================================================================
-- IBIG EDUFORM — Migration : contenu administrable (carrousel + pages)
-- À exécuter UNE FOIS dans phpMyAdmin (onglet SQL) ou via la console MySQL.
-- Sans risque : si une table existe déjà, elle n'est pas recréée.
-- =====================================================================

-- ---------------------------------------------------------------------
-- 1) CARROUSEL D'ACCUEIL
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS hero_slides (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  position     INT           NOT NULL DEFAULT 0,
  image        VARCHAR(255)  NOT NULL DEFAULT 'hero-1.jpg',  -- nom de fichier dans /assets/images/hero/
  kicker       VARCHAR(190)  NULL,
  title        VARCHAR(255)  NOT NULL,                       -- peut contenir <span>...</span>
  lead         TEXT          NULL,
  btn1_label   VARCHAR(120)  NULL,
  btn1_href    VARCHAR(255)  NULL,
  btn2_label   VARCHAR(120)  NULL,
  btn2_href    VARCHAR(255)  NULL,
  panel_title  VARCHAR(190)  NULL,
  panel_text   TEXT          NULL,
  panel_list   TEXT          NULL,                           -- un élément par ligne
  is_active    TINYINT(1)    NOT NULL DEFAULT 1,
  created_at   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Slides actuels pré-remplis (vous pourrez les éditer dans l'admin)
INSERT INTO hero_slides
  (position, image, kicker, title, lead, btn1_label, btn1_href, btn2_label, btn2_href, panel_title, panel_text, panel_list)
SELECT * FROM (
  SELECT 1 AS position, 'hero-1.jpg' AS image,
    'Institut de formation professionnelle' AS kicker,
    'Former des professionnels <span>immédiatement opérationnels</span>' AS title,
    'Des formations certifiantes, pratiques et orientées résultats, employabilité et impact terrain.' AS lead,
    'Explorer les formations' AS btn1_label, 'formations.php' AS btn1_href,
    'Se préinscrire' AS btn2_label, 'preinscription.php' AS btn2_href,
    'Ce qui nous différencie' AS panel_title,
    'Une pédagogie pragmatique : cas réels, outils métiers, évaluations et accompagnement.' AS panel_text,
    '80% pratique terrain\nFormateurs experts en activité\nCertifications métiers modulaires\nInsertion & suivi' AS panel_list
  UNION ALL SELECT 2, 'hero-2.jpg',
    'Pédagogie innovante',
    'Des compétences <span>utiles dès le premier jour</span>',
    'Apprendre vite, appliquer tout de suite : mises en situation, projets guidés, outils professionnels.',
    'Notre approche', 'a-propos.php', 'Former mon équipe', 'entreprises.php',
    'Formats flexibles',
    'Présentiel • Hybride • Distanciel — adaptés aux entreprises, ONG et institutions.',
    'Intra-entreprise sur mesure\nProgrammes certifiants\nRenforcement de capacités\nCo-certification'
  UNION ALL SELECT 3, 'hero-3.jpg',
    'Insertion professionnelle',
    'Former pour <span>l’emploi et la performance</span>',
    'Stages, missions, emploi, entrepreneuriat : une formation utile qui ouvre des opportunités.',
    'Démarrer mon parcours', 'preinscription.php', 'Parler à un conseiller', 'contact.php',
    'Objectif : impact',
    'Notre priorité : des compétences mesurables et valorisables dès la fin de la session.',
    'Coaching carrière\nProjets & cas concrets\nÉvaluations rigoureuses\nRéseau partenaires'
  UNION ALL SELECT 4, 'hero-4.jpg',
    'Vision futuriste',
    'L’avenir des compétences <span>commence maintenant</span>',
    'Préparer les talents aux métiers d’aujourd’hui et de demain : digital, IA, data, performance.',
    'Voir le catalogue 2026', 'formations.php', 'Devenir partenaire', 'partenaires.php',
    'Institut du groupe IBIG',
    'IBIG EDUFORM est le pôle formation professionnelle d’INTERMARK BUSINESS INTERNATIONAL GROUP SARL.',
    'Institutionnel & structuré\nQualité & rigueur\nInnovation pédagogique\nVision africaine'
) AS seed
WHERE NOT EXISTS (SELECT 1 FROM hero_slides);

-- ---------------------------------------------------------------------
-- 2) PAGES ÉDITABLES (institutionnelles + légales)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS site_pages (
  id               INT AUTO_INCREMENT PRIMARY KEY,
  slug             VARCHAR(120) NOT NULL UNIQUE,   -- ex: 'a-propos', 'faq', 'cgv'
  title            VARCHAR(255) NOT NULL,
  meta_description VARCHAR(300) NULL,
  content          LONGTEXT     NULL,              -- HTML
  is_published     TINYINT(1)   NOT NULL DEFAULT 0,-- 0 = repli sur la page actuelle figée
  updated_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Lignes (non publiées : tant que is_published=0, la page actuelle s'affiche)
INSERT INTO site_pages (slug, title, is_published)
SELECT * FROM (
  SELECT 'a-propos'         AS slug, 'À propos'                 AS title, 0 AS is_published
  UNION ALL SELECT 'pedagogie',        'Notre pédagogie',        0
  UNION ALL SELECT 'entreprises',      'Entreprises',            0
  UNION ALL SELECT 'catalogue-formations','Catalogue des formations', 0
  UNION ALL SELECT 'samedi-pro',       'Samedi Pro',             0
  UNION ALL SELECT 'faq',              'FAQ',                    0
  UNION ALL SELECT 'cgv',              'Conditions générales de vente', 0
  UNION ALL SELECT 'conditions',       'Conditions d''utilisation', 0
  UNION ALL SELECT 'confidentialite',  'Politique de confidentialité', 0
  UNION ALL SELECT 'cookies',          'Politique cookies',      0
  UNION ALL SELECT 'mentions-legales', 'Mentions légales',       0
  UNION ALL SELECT 'non-remboursement','Politique de non-remboursement', 0
) AS seed
WHERE NOT EXISTS (SELECT 1 FROM site_pages);
