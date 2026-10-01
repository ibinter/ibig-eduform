<?php
declare(strict_types=1);
/**
 * IBIG EDUFORM — Migration : tables satisfaction
 * Accès : /run_migration_satisfaction.php?run=1
 * À exécuter UNE SEULE FOIS, puis laisser en place (idempotent).
 */

require_once __DIR__ . '/core/auth.php';
if (!auth_check()) {
    http_response_code(403);
    die('<p style="font-family:sans-serif">Accès non autorisé. <a href="/admin/auth/login.php">Se connecter</a></p>');
}

if (empty($_GET['run'])) {
    die('<p style="font-family:sans-serif;padding:20px">Ajoutez <strong>?run=1</strong> à l\'URL pour exécuter la migration.</p>');
}

require_once __DIR__ . '/core/database.php';
$pdo = Database::connect();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$results = [];

$sqls = [
    'satisfaction_tokens' => "CREATE TABLE IF NOT EXISTS satisfaction_tokens (
        id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        preinscription_id BIGINT UNSIGNED NOT NULL UNIQUE,
        email             VARCHAR(255) NOT NULL,
        token             CHAR(64) NOT NULL UNIQUE,
        expires_at        DATETIME NOT NULL,
        used_at           DATETIME DEFAULT NULL,
        created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_token   (token),
        INDEX idx_email   (email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    'satisfaction_reponses' => "CREATE TABLE IF NOT EXISTS satisfaction_reponses (
        id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        preinscription_id BIGINT UNSIGNED NOT NULL,
        email             VARCHAR(255) NOT NULL,
        formation_titre   VARCHAR(500) DEFAULT NULL,
        note_globale      TINYINT UNSIGNED NOT NULL COMMENT '1 à 5',
        note_contenu      TINYINT UNSIGNED DEFAULT NULL,
        note_formateur    TINYINT UNSIGNED DEFAULT NULL,
        note_logistique   TINYINT UNSIGNED DEFAULT NULL,
        points_positifs   TEXT DEFAULT NULL,
        points_ameliorer  TEXT DEFAULT NULL,
        recommande        TINYINT(1) DEFAULT NULL,
        commentaire       TEXT DEFAULT NULL,
        created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_preinsc (preinscription_id),
        INDEX idx_email   (email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
];

foreach ($sqls as $table => $sql) {
    try {
        $pdo->exec($sql);
        $results[] = ['ok', "✅ Table <strong>{$table}</strong> créée ou déjà existante."];
    } catch (Throwable $e) {
        $results[] = ['err', "❌ Erreur sur <strong>{$table}</strong> : " . htmlspecialchars($e->getMessage())];
    }
}

?><!DOCTYPE html>
<html lang="fr">
<head><meta charset="utf-8"><title>Migration Satisfaction — IBIG EDUFORM</title>
<style>body{font-family:Arial,sans-serif;max-width:700px;margin:40px auto;padding:0 20px;color:#1e293b}
h1{color:#0a1733;font-size:1.4rem}
.ok{background:#dcfce7;border:1px solid #86efac;color:#166534;padding:10px 14px;border-radius:8px;margin-bottom:8px}
.err{background:#fee2e2;border:1px solid #fca5a5;color:#991b1b;padding:10px 14px;border-radius:8px;margin-bottom:8px}
a{color:#1d4ed8;font-weight:700}
</style></head>
<body>
<h1>Migration — Formulaire de satisfaction</h1>
<?php foreach ($results as [$cls, $msg]): ?>
<div class="<?= $cls ?>"><?= $msg ?></div>
<?php endforeach; ?>
<p style="margin-top:20px">
  <a href="/admin/satisfaction/index.php">→ Voir les avis satisfaction</a> &nbsp;|&nbsp;
  <a href="/admin/dashboard.php">→ Tableau de bord</a>
</p>
</body></html>
