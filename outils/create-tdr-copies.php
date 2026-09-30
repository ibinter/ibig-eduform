<?php
require_once __DIR__ . '/../core/bootstrap.php';
header('Content-Type: text/plain; charset=utf-8');
$pdo = Database::connect();

$sql = "CREATE TABLE IF NOT EXISTS tdr_copies (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  preinscription_id INT UNSIGNED DEFAULT NULL,
  prospect        VARCHAR(255) NOT NULL DEFAULT '',
  formation_slug  VARCHAR(255) NOT NULL DEFAULT '',
  formation_nom   VARCHAR(500) NOT NULL DEFAULT '',
  mode_formation  ENUM('en_ligne','presentiel','hybride') NOT NULL DEFAULT 'en_ligne',
  format_formation VARCHAR(50) NOT NULL DEFAULT 'individuel',
  prix            INT UNSIGNED NOT NULL DEFAULT 0,
  pdf_path        VARCHAR(500) NOT NULL DEFAULT '',
  ip_address      VARCHAR(45) DEFAULT NULL,
  downloaded_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_preinscription (preinscription_id),
  INDEX idx_slug (formation_slug),
  INDEX idx_downloaded (downloaded_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

$pdo->exec($sql);
echo "TABLE tdr_copies créée (ou déjà existante). OK\n";

// Vérifier la structure
$cols = $pdo->query("DESCRIBE tdr_copies")->fetchAll(PDO::FETCH_ASSOC);
foreach($cols as $c) echo $c['Field'].' — '.$c['Type']."\n";
?>