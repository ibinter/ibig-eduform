<?php
declare(strict_types=1);
require_once __DIR__ . '/core/config.php';
require_once __DIR__ . '/core/database.php';

header('Content-Type: application/json; charset=utf-8');

$pdo = Database::connect();
$results = [];

$cols = [
    "ALTER TABLE leads ADD COLUMN IF NOT EXISTS prenom       VARCHAR(100)  DEFAULT NULL AFTER nom",
    "ALTER TABLE leads ADD COLUMN IF NOT EXISTS email        VARCHAR(190)  DEFAULT NULL AFTER contact",
    "ALTER TABLE leads ADD COLUMN IF NOT EXISTS whatsapp     VARCHAR(30)   DEFAULT NULL AFTER email",
    "ALTER TABLE leads ADD COLUMN IF NOT EXISTS pays         VARCHAR(80)   DEFAULT NULL AFTER whatsapp",
    "ALTER TABLE leads ADD COLUMN IF NOT EXISTS mode_souhaite   VARCHAR(30) DEFAULT NULL AFTER pays",
    "ALTER TABLE leads ADD COLUMN IF NOT EXISTS format_souhaite VARCHAR(30) DEFAULT NULL AFTER mode_souhaite",
    "ALTER TABLE leads ADD COLUMN IF NOT EXISTS entreprise   VARCHAR(190)  DEFAULT NULL AFTER format_souhaite",
    "ALTER TABLE leads ADD COLUMN IF NOT EXISTS poste        VARCHAR(100)  DEFAULT NULL AFTER entreprise",
];

foreach ($cols as $sql) {
    try {
        $pdo->exec($sql);
        $results[] = ['ok' => true, 'sql' => substr($sql, 0, 60)];
    } catch (Throwable $e) {
        $results[] = ['ok' => false, 'sql' => substr($sql, 0, 60), 'err' => $e->getMessage()];
    }
}

echo json_encode(['migrations' => $results], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
