<?php
declare(strict_types=1);
/**
 * ONE-TIME — Renomme formation 2762 + désactive formation 211 (doublon QHSE)
 * À supprimer après exécution.
 */
require_once __DIR__ . '/../_init.php';
Middleware::requireAuth();

$pdo = Database::connect();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$pageTitle  = "Fix QHSE";
$activeMenu = "formations";

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $pdo->beginTransaction();
    try {
        // 1. Renommer formation 2762
        $pdo->prepare("UPDATE formations SET titre = 'Responsable QHSE/HSE', updated_at = NOW() WHERE id = 2762")
            ->execute();
        $n1 = $pdo->rowCount() ?: 1;

        // 2. Désactiver formation 211 (doublon)
        $pdo->prepare("UPDATE formations SET statut = 'inactive', updated_at = NOW() WHERE id = 211")
            ->execute();
        $n2 = $pdo->rowCount() ?: 1;

        $pdo->commit();
        $message = "✅ Formation 2762 renommée en «&nbsp;Responsable QHSE/HSE&nbsp;» · Formation 211 désactivée.";
    } catch (Throwable $e) {
        $pdo->rollBack();
        $message = "❌ Erreur : " . htmlspecialchars($e->getMessage());
    }
}

// Lire état actuel
$f2762 = $pdo->query("SELECT id, titre, statut FROM formations WHERE id = 2762")->fetch(PDO::FETCH_ASSOC);
$f211  = $pdo->query("SELECT id, titre, statut FROM formations WHERE id = 211")->fetch(PDO::FETCH_ASSOC);

ob_start();
?>
<style>
.fix-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:24px;max-width:700px;margin:0 auto}
.fix-card h2{margin:0 0 16px;font-size:17px;font-weight:800}
table{width:100%;border-collapse:collapse;font-size:13px;margin-bottom:20px}
th{background:#f1f5f9;padding:7px 10px;text-align:left;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase}
td{padding:7px 10px;border-bottom:1px solid #f1f5f9}
.alert-ok{padding:10px 14px;background:#dcfce7;color:#166534;border-radius:8px;margin-bottom:16px;font-size:13px}
.alert-err{padding:10px 14px;background:#fee2e2;color:#991b1b;border-radius:8px;margin-bottom:16px;font-size:13px}
.btn{padding:10px 20px;background:#1e40af;color:#fff;border:none;border-radius:8px;font-weight:700;cursor:pointer;font-size:14px}
</style>
<div class="fix-card">
  <h2>🔧 Fix QHSE — one-time</h2>

  <?php if ($message): ?>
    <div class="<?= str_starts_with($message,'✅') ? 'alert-ok' : 'alert-err' ?>"><?= $message ?></div>
  <?php endif; ?>

  <table>
    <thead><tr><th>ID</th><th>Titre actuel</th><th>Statut</th><th>Action</th></tr></thead>
    <tbody>
      <tr>
        <td>2762</td>
        <td><?= htmlspecialchars((string)($f2762['titre'] ?? '—')) ?></td>
        <td><?= htmlspecialchars((string)($f2762['statut'] ?? '—')) ?></td>
        <td>→ Renommer en <strong>Responsable QHSE/HSE</strong></td>
      </tr>
      <tr>
        <td>211</td>
        <td><?= htmlspecialchars((string)($f211['titre'] ?? '—')) ?></td>
        <td><?= htmlspecialchars((string)($f211['statut'] ?? '—')) ?></td>
        <td>→ <strong>Désactiver</strong> (doublon)</td>
      </tr>
    </tbody>
  </table>

  <form method="post">
    <?= csrf_field() ?>
    <button type="submit" class="btn">✅ Appliquer les deux corrections</button>
  </form>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout/layout.php';
