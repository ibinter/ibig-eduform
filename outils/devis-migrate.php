<?php
declare(strict_types=1);
/**
 * IBIG EDUFORM — /outils/devis-migrate.php
 * Applique la migration 2026_devis.sql en un clic.
 * Protégé par .htpasswd du dossier /outils/.
 * À supprimer après exécution réussie.
 */
require_once __DIR__ . '/../core/config.php';

$sqlFile = __DIR__ . '/../migrations/2026_devis.sql';
$results = [];
$ok = true;

try {
    $pdo = new PDO(
        'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4',
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $sql = file_get_contents($sqlFile);
    // Supprimer les lignes de commentaires SQL (--) puis découper sur ';'
    $sqlClean = preg_replace('/^--.*$/m', '', $sql);         // retirer les commentaires
    $sqlClean = preg_replace('/\n{3,}/', "\n\n", $sqlClean); // compacter les lignes vides
    $stmts = array_filter(
        array_map('trim', preg_split('/;\s*[\r\n]+/', $sqlClean)),
        fn($s) => $s !== ''
    );

    foreach ($stmts as $stmt) {
        try {
            $pdo->exec($stmt);
            $results[] = ['ok' => true,  'sql' => mb_substr($stmt, 0, 80)];
        } catch (\PDOException $e) {
            $results[] = ['ok' => false, 'sql' => mb_substr($stmt, 0, 80), 'err' => $e->getMessage()];
            $ok = false;
        }
    }
} catch (\Throwable $e) {
    $ok = false;
    $results[] = ['ok' => false, 'sql' => 'Connexion BD', 'err' => $e->getMessage()];
}
?><!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Migration devis — IBIG EDUFORM</title>
<style>
body{font-family:system-ui,sans-serif;background:#0d1f3c;color:#f1f5f9;padding:32px;max-width:800px;margin:0 auto}
h1{color:#f59e0b;margin-bottom:24px}
.row{display:flex;gap:10px;align-items:flex-start;padding:8px 12px;border-radius:6px;margin-bottom:6px;font-size:.85rem}
.row.ok{background:#064e3b}
.row.fail{background:#7f1d1d}
.icon{font-size:1rem;flex-shrink:0}
.stmt{color:#a7f3d0;font-family:monospace;word-break:break-all}
.err{color:#fca5a5;font-family:monospace;font-size:.78rem;margin-top:4px}
.summary{margin-top:24px;padding:16px;border-radius:8px;font-size:1rem;font-weight:700}
.summary.ok{background:#065f46;color:#6ee7b7}
.summary.fail{background:#991b1b;color:#fca5a5}
a{color:#f59e0b}
</style>
</head>
<body>
<h1>⚙️ Migration — Table devis</h1>
<?php foreach ($results as $r): ?>
  <div class="row <?= $r['ok'] ? 'ok' : 'fail' ?>">
    <span class="icon"><?= $r['ok'] ? '✅' : '❌' ?></span>
    <div>
      <div class="stmt"><?= htmlspecialchars($r['sql']) ?>…</div>
      <?php if (!$r['ok']): ?>
        <div class="err"><?= htmlspecialchars($r['err']) ?></div>
      <?php endif; ?>
    </div>
  </div>
<?php endforeach; ?>
<div class="summary <?= $ok ? 'ok' : 'fail' ?>">
  <?= $ok ? '✅ Migration réussie — table devis et séquence créées.' : '❌ Certaines requêtes ont échoué — vérifiez les erreurs ci-dessus.' ?>
</div>
<?php if ($ok): ?>
<p style="margin-top:16px;color:#94a3b8;font-size:.85rem">
  ✔ Vous pouvez maintenant <strong>supprimer ce fichier</strong> du serveur.<br>
  <a href="tdr-historique.php">← Retour à l'historique TDR</a>
</p>
<?php endif; ?>
</body>
</html>
