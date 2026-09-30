<?php
declare(strict_types=1);
/**
 * IBIG EDUFORM — Migration : système TDR (historique + guide tarifaire)
 * Exécuter UNE SEULE FOIS : php migrations/tdr_system_2026.php
 */
require_once __DIR__ . '/../core/config.php';

$pdo = new PDO(
    'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
    DB_USER, DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$pdo->exec("
CREATE TABLE IF NOT EXISTS tdr_historique (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ref_doc        VARCHAR(100)  DEFAULT '',
  titre          VARCHAR(255)  NOT NULL,
  type_doc       ENUM('formation','mission','projet','evaluation') DEFAULT 'formation',
  prospect       VARCHAR(255)  DEFAULT '',
  categorie      VARCHAR(150)  DEFAULT '',
  format_niveau  ENUM('simplifie','moyen','detaille') DEFAULT 'detaille',
  nb_participants TINYINT UNSIGNED DEFAULT 0,
  prix_en_ligne  INT UNSIGNED  DEFAULT 0,
  prix_presentiel INT UNSIGNED DEFAULT 0,
  date_debut     DATE          NULL,
  pdf_filename   VARCHAR(255)  DEFAULT '',
  date_generation DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ip_generateur  VARCHAR(45)   DEFAULT '',
  INDEX idx_date  (date_generation),
  INDEX idx_titre (titre(80))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

$pdo->exec("
CREATE TABLE IF NOT EXISTS tarif_guide (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  categorie      VARCHAR(150)  NOT NULL,
  formation      VARCHAR(255)  NOT NULL,
  duree_heures   TINYINT UNSIGNED DEFAULT 25,
  prix_en_ligne  INT UNSIGNED  NOT NULL DEFAULT 200000,
  prix_presentiel INT UNSIGNED NOT NULL DEFAULT 250000,
  remise_groupe  TINYINT UNSIGNED DEFAULT 10 COMMENT 'Remise groupe % (Sur devis public)',
  remise_early   TINYINT UNSIGNED DEFAULT 5  COMMENT 'Remise early-bird %',
  remise_fidel   TINYINT UNSIGNED DEFAULT 8  COMMENT 'Remise client fidèle %',
  remise_max     TINYINT UNSIGNED DEFAULT 20 COMMENT 'Remise max autorisée %',
  notes          TEXT          DEFAULT NULL,
  actif          TINYINT(1)    DEFAULT 1,
  date_maj       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_cat  (categorie(80)),
  INDEX idx_actif(actif)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

/* Données initiales */
$count = (int)$pdo->query("SELECT COUNT(*) FROM tarif_guide")->fetchColumn();
if ($count === 0) {
    $ins = $pdo->prepare("INSERT INTO tarif_guide
        (categorie,formation,duree_heures,prix_en_ligne,prix_presentiel,remise_groupe,remise_early,remise_fidel,remise_max,notes)
        VALUES (?,?,?,?,?,?,?,?,?,?)");
    $data = [
        ['Management & Leadership','Management et Leadership Opérationnel',25,250000,300000,10,5,8,20,'Formation phare — forte demande'],
        ['Management & Leadership','Gestion de Projet Professionnelle',25,250000,300000,10,5,8,20,null],
        ['Comptabilité & Finance','Comptabilité Générale SYSCOHADA Révisé',25,200000,250000,10,5,8,20,null],
        ['Comptabilité & Finance','Fiscalité et Obligations Déclaratives',20,200000,250000,10,5,8,20,null],
        ['Comptabilité & Finance','Analyse Financière et Tableau de Bord',25,250000,300000,10,5,8,20,null],
        ['RH & Paie','Gestion de la Paie en Côte d\'Ivoire',25,200000,250000,10,5,8,20,null],
        ['RH & Paie','Droit du Travail OHADA & CCIAF',20,200000,250000,10,5,8,20,null],
        ['Informatique & Bureautique','Excel Professionnel — Niveau Avancé',20,200000,250000,10,5,8,20,null],
        ['Informatique & Bureautique','Excel — Tableaux Croisés Dynamiques & Power BI',25,220000,270000,10,5,8,20,null],
        ['Intelligence Artificielle','IA pour Professionnels — ChatGPT, Claude, Copilot',20,220000,270000,10,5,8,20,'Formation très demandée'],
        ['Marketing & Communication','Marketing Digital et Stratégie Réseaux Sociaux',25,220000,270000,10,5,8,20,null],
        ['Logistique & Supply Chain','Gestion des Stocks et Supply Chain',25,200000,250000,10,5,8,20,null],
        ['Audit & Contrôle','Audit Interne et Contrôle de Gestion',25,250000,300000,10,5,8,20,null],
        ['Secrétariat & Administration','Secrétariat de Direction Moderne',25,200000,250000,10,5,8,20,null],
    ];
    foreach ($data as $row) $ins->execute($row);
}

echo "✅ Tables tdr_historique et tarif_guide créées avec succès.\n";
echo "   Lignes tarif_guide : " . $pdo->query("SELECT COUNT(*) FROM tarif_guide")->fetchColumn() . "\n";
