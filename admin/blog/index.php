<?php
require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../auth/middleware.php';

Middleware::requireAuth();

$pageTitle  = 'Articles du blog';
$activeMenu = 'blog';

$pdo = Database::connect();

/* =========================
   FILTRE RAPIDE PAR STATUT
========================= */
$statut = trim((string)($_GET['statut'] ?? ''));
$where = '';
$params = [];

if ($statut !== '') {
  $where = 'WHERE statut = ?';
  $params[] = $statut;
}

/* =========================
   PAGINATION SIMPLE
========================= */
$perPage = 20;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

/* =========================
   TOTAL ARTICLES
========================= */
$countSql = "SELECT COUNT(*) FROM blog_articles $where";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));

/* =========================
   ARTICLES
========================= */
$sql = "
  SELECT *
  FROM blog_articles
  $where
  ORDER BY created_at DESC
  LIMIT $perPage OFFSET $offset
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$articles = $stmt->fetchAll(PDO::FETCH_ASSOC);

ob_start();
?>

<style>
/* =========================================================
   BLOG — ADMIN — INDEX UI FIX
========================================================= */

table{
  width:100%;
  border-collapse:collapse;
  font-size:14px;
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

/* Pills */
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
  color:#111827 !important;
  text-decoration:none;
  cursor:pointer;
  opacity:1 !important;
}

.btn:hover{background:#f3f4f6}

.btn-secondary{
  background:#f1f5f9;
  border-color:#e5e7eb;
  color:#111827 !important;
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
    <h2>&#x1F4F0; Articles du blog</h2>
    <a class="btn btn-primary" href="create.php">
      &#x2795; Nouvel article
    </a>
  </div>

  <!-- FILTRE STATUT -->
  <form method="get" style="margin:12px 0;display:flex;gap:8px;flex-wrap:wrap">
    <select name="statut">
      <option value="">Tous statuts</option>
      <option value="publie" <?= $statut==='publie'?'selected':''; ?>>Publié</option>
      <option value="brouillon" <?= $statut==='brouillon'?'selected':''; ?>>Brouillon</option>
    </select>
    <button class="btn btn-secondary" type="submit">Filtrer</button>
  </form>

  <!-- TABLE -->
  <table>
    <thead>
      <tr>
        <th>Titre</th>
        <th>Statut</th>
        <th>Date</th>
        <th style="width:220px">Actions</th>
      </tr>
    </thead>
    <tbody>

<?php if (empty($articles)): ?>
  <tr>
    <td colspan="4" class="muted">Aucun article trouvé.</td>
  </tr>
<?php else: foreach ($articles as $a): ?>
  <tr>
    <td><strong><?= e($a['titre']); ?></strong></td>
    <td>
      <span class="pill <?= $a['statut']==='publie'?'ok':'wait'; ?>">
        <?= e($a['statut']); ?>
      </span>
    </td>
    <td class="muted"><?= date('d/m/Y', strtotime($a['created_at'])); ?></td>
    <td style="display:flex;gap:6px;flex-wrap:wrap">

      <a class="btn btn-secondary"
         href="edit.php?id=<?= (int)$a['id']; ?>">
        &#x270F; Éditer
      </a>

      <a class="btn"
         href="delete.php?id=<?= (int)$a['id']; ?>&csrf=<?= csrf_token(); ?>"
         onclick="return confirm('Supprimer cet article ?')">
        &#x1F5D1;
      </a>

    </td>
  </tr>
<?php endforeach; endif; ?>

    </tbody>
  </table>

  <!-- PAGINATION -->
  <?php if ($totalPages > 1): ?>
  <div style="margin-top:16px;display:flex;gap:8px;flex-wrap:wrap">
    <?php for ($i=1; $i <= $totalPages; $i++): ?>
      <a class="btn <?= $i===$page ? 'btn-primary' : ''; ?>"
         href="?<?= http_build_query(array_merge($_GET, ['page'=>$i])); ?>">
        <?= $i; ?>
      </a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>

</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout/layout.php';