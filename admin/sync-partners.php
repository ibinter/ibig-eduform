<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../admin/auth/middleware.php';
requireAdmin();

$result = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ob_start();
    require __DIR__ . '/../cron/sync-to-partners.php';
    $raw = ob_get_clean();
    $result = json_decode($raw, true) ?: ['ok' => false, 'raw' => $raw];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Sync EDUFORM → Partners</title>
<style>
body{font-family:Inter,sans-serif;background:#0f172a;color:#e2e8f0;margin:0;padding:40px 20px}
.card{max-width:700px;margin:auto;background:#1e293b;border-radius:16px;padding:32px;border:1px solid rgba(255,255,255,.08)}
h1{margin:0 0 8px;font-size:1.5rem;color:#fff}
p.sub{color:#94a3b8;margin:0 0 28px;font-size:.9rem}
.btn{background:#2563eb;color:#fff;border:none;padding:14px 28px;border-radius:10px;font-size:1rem;font-weight:700;cursor:pointer;transition:.15s}
.btn:hover{background:#1d4ed8}
.result{margin-top:24px;background:#0f172a;border-radius:10px;padding:20px;border:1px solid rgba(255,255,255,.08)}
.ok{color:#4ade80;font-weight:800;font-size:1.1rem}
.ko{color:#f87171;font-weight:800;font-size:1.1rem}
.stat{display:flex;gap:24px;margin:16px 0}
.stat-item{text-align:center;flex:1;background:rgba(255,255,255,.04);padding:14px;border-radius:8px}
.stat-item .val{font-size:1.6rem;font-weight:900;color:#60a5fa}
.stat-item .lbl{font-size:.8rem;color:#64748b;margin-top:4px}
pre{background:#020617;padding:14px;border-radius:8px;overflow:auto;font-size:.8rem;color:#94a3b8;max-height:300px}
.warn{background:#7c2d12;border-radius:8px;padding:12px 16px;margin-bottom:20px;font-size:.9rem;color:#fca5a5}
</style>
</head>
<body>
<div class="card">
  <h1>🔄 Synchronisation EDUFORM → IBIG PARTNERS</h1>
  <p class="sub">Pousse toutes les formations actives EDUFORM dans Supabase (table Product, branche "eduform").</p>

  <?php if (!defined('SUPABASE_URL') || !defined('SUPABASE_SERVICE_KEY')): ?>
  <div class="warn">
    ⚠️ <strong>Configuration manquante.</strong> Ajoutez dans <code>core/secrets.php</code> :<br><br>
    <code>define('SUPABASE_URL', 'https://zuqjuqpldnnfaoycwkcr.supabase.co');</code><br>
    <code>define('SUPABASE_SERVICE_KEY', 'eyJ...');</code><br><br>
    La clé <strong>service_role</strong> se trouve dans Supabase → Settings → API.
  </div>
  <?php endif; ?>

  <form method="POST">
    <button class="btn" type="submit">▶ Lancer la synchronisation</button>
  </form>

  <?php if ($result): ?>
  <div class="result">
    <?php if ($result['ok'] ?? false): ?>
      <div class="ok">✅ Synchronisation réussie</div>
    <?php else: ?>
      <div class="ko">❌ Erreurs détectées</div>
    <?php endif; ?>

    <?php if (isset($result['total'])): ?>
    <div class="stat">
      <div class="stat-item"><div class="val"><?= (int)($result['total'] ?? 0) ?></div><div class="lbl">Formations lues</div></div>
      <div class="stat-item"><div class="val"><?= (int)($result['synced'] ?? 0) ?></div><div class="lbl">Synchronisées</div></div>
      <div class="stat-item"><div class="val"><?= (int)($result['errors'] ?? 0) ?></div><div class="lbl">Erreurs</div></div>
    </div>
    <?php endif; ?>

    <?php if (!empty($result['error_list'])): ?>
    <pre><?= htmlspecialchars(json_encode($result['error_list'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
    <?php endif; ?>

    <p style="color:#475569;font-size:.8rem;margin:12px 0 0">Synchronisé le : <?= htmlspecialchars((string)($result['synced_at'] ?? date('c'))) ?></p>
  </div>
  <?php endif; ?>
</div>
</body>
</html>
