<?php
require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../auth/middleware.php';

Middleware::requireAuth();

$pageTitle  = 'Avis clients';
$activeMenu = 'avis';

$pdo = Database::connect();

/* =========================
   DONNÉES
========================= */
$avis = $pdo->query("
  SELECT *
  FROM avis_clients
  ORDER BY created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

ob_start();
?>

<style>
/* =========================================================
   AVIS CLIENTS — ADMIN — FIX UI
========================================================= */

table{
  width:100%;
  font-size:14px;
  border-collapse:collapse;
}
th,td{
  padding:10px;
  vertical-align:middle;
}
th{
  text-align:left;
  font-weight:600;
  border-bottom:1px solid #e5e7eb;
}
tr:not(:last-child) td{
  border-bottom:1px solid #f1f5f9;
}

.muted{color:#6b7280}

/* Pills statut */
.pill{
  padding:4px 10px;
  border-radius:999px;
  font-size:12px;
  font-weight:500;
}
.pill.ok{background:#dcfce7;color:#166534}
.pill.wait{background:#fff7ed;color:#9a3412}

/* =====================================================
   FIX BOUTONS — TEXTE INVISIBLE
===================================================== */
.btn{
  display:inline-flex;
  align-items:center;
  gap:6px;
  padding:8px 12px;
  border-radius:10px;
  font-size:13px;
  font-weight:500;
  background:#ffffff;
  border:1px solid #e5e7eb;
  color:#111827 !important;      /* TEXTE VISIBLE */
  text-decoration:none;
  opacity:1 !important;
}

.btn:hover{background:#f3f4f6}

.btn-secondary{
  background:#f1f5f9;
  border-color:#e5e7eb;
  color:#111827 !important;
}

.btn-danger{
  background:#fee2e2;
  border-color:#fecaca;
  color:#991b1b !important;
}

.btn-primary{
  background:#2563eb;
  border-color:#2563eb;
  color:#ffffff !important;
}
</style>

<div class="card">

  <!-- HEADER -->
  <div style="display:flex;justify-content:space-between;align-items:center">
    <h2>&#x2B50; Avis clients</h2>
    <a class="btn btn-primary" href="create.php">
      &#x2795; Nouvel avis
    </a>
  </div>

  <!-- TABLE -->
  <table style="margin-top:16px">
    <thead>
      <tr>
        <th>Client</th>
        <th>Secteur</th>
        <th>Message</th>
        <th>Statut</th>
        <th>Date</th>
        <th style="width:220px">Actions</th>
      </tr>
    </thead>
    <tbody>

<?php if (empty($avis)): ?>
  <tr>
    <td colspan="6" class="muted">Aucun avis enregistré.</td>
  </tr>
<?php else: foreach ($avis as $a): ?>
  <tr>

    <td>
      <strong><?= e($a['nom']); ?></strong><br>
      <span class="muted">
        <?= e($a['ville'] ?? ''); ?>
        <?= $a['pays'] ? ', '.e($a['pays']) : ''; ?>
      </span>
    </td>

    <td><?= e($a['secteur'] ?? '—'); ?></td>

    <td class="muted">
      <?= e(mb_strimwidth($a['texte'], 0, 80, '…')); ?>
    </td>

    <td>
      <span class="pill <?= $a['statut']==='publie' ? 'ok' : 'wait'; ?>">
        <?= e($a['statut']); ?>
      </span>
    </td>

    <td class="muted">
      <?= date('d/m/Y', strtotime($a['created_at'])); ?>
    </td>

    <td style="display:flex;gap:6px;flex-wrap:wrap">

      <a class="btn btn-secondary"
         href="edit.php?id=<?= (int)$a['id']; ?>">
        &#x270F; Éditer
      </a>

      <?php if ($a['statut']==='publie'): ?>
        <a class="btn"
           href="toggle.php?id=<?= (int)$a['id']; ?>&statut=brouillon&csrf=<?= csrf_token(); ?>">
          Dépublier
        </a>
      <?php else: ?>
        <a class="btn"
           href="toggle.php?id=<?= (int)$a['id']; ?>&statut=publie&csrf=<?= csrf_token(); ?>">
          Publier
        </a>
      <?php endif; ?>

      <a class="btn btn-danger"
         href="delete.php?id=<?= (int)$a['id']; ?>&csrf=<?= csrf_token(); ?>"
         onclick="return confirm('Supprimer cet avis ?')">
        &#x1F5D1;
      </a>

    </td>

  </tr>
<?php endforeach; endif; ?>

    </tbody>
  </table>

</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout/layout.php';