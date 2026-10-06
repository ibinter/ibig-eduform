<?php
declare(strict_types=1);

ob_start();

require_once __DIR__ . '/../_init.php';
require_once __DIR__ . '/../auth/middleware.php';

Middleware::requireAuth();

$pageTitle  = "Nettoyage slugs ASCII";
$activeMenu = "formations";

$pdo = Database::connect();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

function slugify_ascii(string $text): string {
    $text  = trim($text);
    $text  = mb_strtolower($text, 'UTF-8');
    $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
    $text  = $ascii !== false ? $ascii : $text;
    $text  = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim((string)$text, '-');
}

$message = '';
$preview = [];

/* Trouver tous les slugs non-ASCII */
$stmt = $pdo->query("SELECT id, titre, slug FROM formations ORDER BY id DESC");
$rows = $stmt->fetchAll();

foreach ($rows as $r) {
    $clean = slugify_ascii($r['slug']);
    if ($clean !== $r['slug']) {
        $preview[] = ['id' => $r['id'], 'titre' => $r['titre'], 'old' => $r['slug'], 'new' => $clean];
    }
}

/* Exécution de la migration */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm'])) {
    csrf_check();
    $updated = 0;
    foreach ($preview as $item) {
        /* Vérifier unicité du nouveau slug */
        $check = $pdo->prepare("SELECT id FROM formations WHERE slug = ? AND id != ? LIMIT 1");
        $check->execute([$item['new'], $item['id']]);
        if ($check->fetch()) {
            $message .= "⚠️ Slug déjà utilisé, ignoré : {$item['new']} (formation #{$item['id']})<br>";
            continue;
        }
        $upd = $pdo->prepare("UPDATE formations SET slug = ?, updated_at = NOW() WHERE id = ?");
        $upd->execute([$item['new'], $item['id']]);
        $updated++;
    }
    $message = "✅ {$updated} slugs mis à jour." . ($message ? '<br>' . $message : '');
    /* Recharger la liste */
    $stmt = $pdo->query("SELECT id, titre, slug FROM formations ORDER BY id DESC");
    $rows = $stmt->fetchAll();
    $preview = [];
    foreach ($rows as $r) {
        $clean = slugify_ascii($r['slug']);
        if ($clean !== $r['slug']) {
            $preview[] = ['id' => $r['id'], 'titre' => $r['titre'], 'old' => $r['slug'], 'new' => $clean];
        }
    }
}

ob_start();
?>
<style>
.card{background:#fff;border:1px solid #e6eaf2;border-radius:16px;padding:20px;box-shadow:0 10px 30px rgba(15,23,42,.06);margin-bottom:20px}
table{width:100%;border-collapse:collapse;font-size:13px}
th{background:#f1f5f9;padding:8px 10px;text-align:left;font-size:11px;font-weight:800;color:#64748b;text-transform:uppercase}
td{padding:8px 10px;border-bottom:1px solid #f1f5f9;vertical-align:top}
.old{color:#dc2626;font-family:monospace}
.new{color:#16a34a;font-family:monospace}
.btn{display:inline-flex;align-items:center;gap:8px;padding:11px 16px;border-radius:12px;font-weight:800;border:none;cursor:pointer;font-size:14px}
.btn-primary{background:#2563eb;color:#fff}
.btn-secondary{background:#f1f5f9;color:#0f172a}
.alert{padding:12px 16px;border-radius:12px;margin-bottom:16px;font-size:13px}
.alert-ok{background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0}
.alert-warn{background:#fff7ed;color:#9a3412;border:1px solid #fed7aa}
</style>

<div class="card">
  <h2 style="margin:0 0 6px;font-size:18px;font-weight:900;color:#0f172a">&#128295; Nettoyage des slugs — ASCII uniquement</h2>
  <p style="color:#64748b;font-size:13px;margin:0 0 16px">
    Ce script détecte les slugs contenant des caractères accentués et les convertit en ASCII propre.<br>
    <strong>Exemple :</strong> <code>de-comptable-à-chef-comptable-q4-2026</code> → <code>de-comptable-a-chef-comptable-q4-2026</code>
  </p>

  <?php if ($message): ?>
    <div class="alert alert-ok"><?= $message ?></div>
  <?php endif; ?>

  <?php if (empty($preview)): ?>
    <div class="alert alert-ok">&#10003; Tous les slugs sont déjà en ASCII. Rien à corriger.</div>
  <?php else: ?>
    <div class="alert alert-warn">&#9888; <?= count($preview) ?> slug(s) avec accents détecté(s) :</div>

    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Formation</th>
          <th>Slug actuel</th>
          <th>Nouveau slug</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($preview as $item): ?>
        <tr>
          <td><?= (int)$item['id'] ?></td>
          <td><?= htmlspecialchars($item['titre'], ENT_QUOTES, 'UTF-8') ?></td>
          <td class="old"><?= htmlspecialchars($item['old'], ENT_QUOTES, 'UTF-8') ?></td>
          <td class="new"><?= htmlspecialchars($item['new'], ENT_QUOTES, 'UTF-8') ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <form method="post" style="margin-top:20px;display:flex;gap:10px;flex-wrap:wrap">
      <?= csrf_field() ?>
      <input type="hidden" name="confirm" value="1">
      <button type="submit" class="btn btn-primary">&#128295; Appliquer la migration</button>
      <a href="index.php" class="btn btn-secondary">Annuler</a>
    </form>
  <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout/layout.php';
