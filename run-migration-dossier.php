<?php
/**
 * Script temporaire — à supprimer après exécution.
 * Ajoute les colonnes dossier d'inscription à paiements_inscription.
 * Accès : https://ibig-eduform.com/run-migration-dossier.php?token=MIGRATE_DOSSIER_2026
 */
if (($_GET['token'] ?? '') !== 'MIGRATE_DOSSIER_2026') {
    http_response_code(403); exit('Accès refusé.');
}

require_once __DIR__ . '/core/bootstrap.php';
$pdo = Database::connect();

$cols = [
    "ALTER TABLE paiements_inscription ADD COLUMN IF NOT EXISTS mode_formation VARCHAR(30) NULL DEFAULT NULL",
    "ALTER TABLE paiements_inscription ADD COLUMN IF NOT EXISTS statut_professionnel VARCHAR(30) NULL DEFAULT NULL",
    "ALTER TABLE paiements_inscription ADD COLUMN IF NOT EXISTS objectif VARCHAR(30) NULL DEFAULT NULL",
    "ALTER TABLE paiements_inscription ADD COLUMN IF NOT EXISTS disponibilite VARCHAR(20) NULL DEFAULT NULL",
    "ALTER TABLE paiements_inscription ADD COLUMN IF NOT EXISTS ville VARCHAR(100) NULL DEFAULT NULL",
    "ALTER TABLE paiements_inscription ADD COLUMN IF NOT EXISTS pays VARCHAR(100) NULL DEFAULT NULL",
    "ALTER TABLE paiements_inscription ADD COLUMN IF NOT EXISTS domaine_activite VARCHAR(100) NULL DEFAULT NULL",
    "ALTER TABLE paiements_inscription ADD COLUMN IF NOT EXISTS niveau_etude VARCHAR(50) NULL DEFAULT NULL",
    "ALTER TABLE paiements_inscription ADD COLUMN IF NOT EXISTS fonction VARCHAR(100) NULL DEFAULT NULL",
    "ALTER TABLE paiements_inscription ADD COLUMN IF NOT EXISTS annees_experience VARCHAR(30) NULL DEFAULT NULL",
    "ALTER TABLE paiements_inscription ADD COLUMN IF NOT EXISTS message TEXT NULL DEFAULT NULL",
];

echo "<pre>\n";
foreach ($cols as $sql) {
    try {
        $pdo->exec($sql);
        preg_match('/ADD COLUMN IF NOT EXISTS (\w+)/', $sql, $m);
        echo "OK : colonne " . ($m[1] ?? '?') . " ajoutée\n";
    } catch (Throwable $e) {
        echo "ERREUR : " . $e->getMessage() . "\n";
    }
}
echo "\nMigration terminée. Supprimez ce fichier.\n</pre>";
