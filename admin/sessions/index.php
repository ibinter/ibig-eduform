<?php
declare(strict_types=1);

/* ============================================================
   BOOTSTRAP & AUTH
============================================================ */
require_once __DIR__ . '/../../core/bootstrap.php';

$mw = __DIR__ . '/../auth/middleware.php';
if (!file_exists($mw)) {
  http_response_code(500);
  die("middleware.php introuvable : $mw");
}
require_once $mw;

if (!class_exists('Middleware')) {
  http_response_code(500);
  die("Classe Middleware introuvable");
}

Middleware::requireAuth();

/* ============================================================
   PAGE META
============================================================ */
$pageTitle  = "Sessions de formation";
$activeMenu = "formations";

$pdo = Database::connect();

/* ============================================================
   HELPERS
============================================================ */
if (!function_exists('e')) {
  function e($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
  }
}

/* ============================================================
   SÉCURITÉ formation_id
============================================================ */
$formationId = (int)($_GET['formation_id'] ?? 0);
if ($formationId <= 0) {
  redirect('/admin/formations/index.php');
}

/* ============================================================
   FORMATION
============================================================ */
$stmt = $pdo->prepare("SELECT id, titre FROM formations WHERE id=? LIMIT 1");
$stmt->execute([$formationId]);
$formation = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$formation) {
  redirect('/admin/formations/index.php');
}

/* ============================================================
   SESSIONS
============================================================ */
$stmt = $pdo->prepare("
  SELECT id, date_debut, date_fin, duree, mode, statut
  FROM calendrier_formations
  WHERE formation_id = ?
  ORDER BY date_debut ASC
");
$stmt->execute([$formationId]);
$sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ============================================================
   RENDU
============================================================ */
ob_start();
?>

<style>
/* =========================
   TABLE SESSIONS – ADMIN COMPACT
========================= */
.table-sessions{
  width:100%;
  border-collapse:separate;
  border-spacing:0 6px;
  font-size:14px;
}

.table-sessions th{
  text-align:left;
  font-size:12px;
  color:#6b7280;
  font-weight:700;
  padding:8px 10px;
}

.table-sessions td{
  background:#fff;
  padding:10px 12px;
  vertical-align:middle;
  border-top:1px solid #e5e7eb;
  border-bottom:1px solid #e5e7eb;
}

.table-sessions tr td:first-child{
  border-left:1px solid #e5e7eb;
  border-radius:10px 0 0 10px;
}

.table-sessions tr td:last-child{
  border-right:1px solid #e5e7eb;
  border-radius:0 10px 10px 0;
}

.cell-date{
  white-space:nowrap;
  font-weight:600;
}

.cell-mode{
  white-space:nowrap;
  text-transform:capitalize;
}

.cell-actions{
  display:flex;
  gap:8px;
  flex-wrap:nowrap;
}

.cell-actions .btn{
  padding:6px 12px;
  font-size:13px;
  border-radius:999px;
}

/* Pills */
.pill{
  padding:4px 10px;
  border-radius:999px;
  font-size:12px;
  font-weight:700;
  white-space:nowrap;
}

.pill.ok{
  background:#dcfce7;
  color:#166534;
}

.pill.wait{
  background:#fff7ed;
  color:#9a3412;
}
</style>

<div class="card">

  <h2>&#128197; Sessions — <?= e($formation['titre']); ?></h2>

  <div style="margin:12px 0 18px;display:flex;gap:10px;flex-wrap:wrap">
    <a class="btn btn-primary"
       href="create.php?formation_id=<?= (int)$formationId; ?>">
      &#10133; Nouvelle session
    </a>

    <a href="/admin/formations/index.php" class="btn btn-secondary">
      &#8592; Retour formations
    </a>
  </div>

  <table class="table-sessions">
    <thead>
      <tr>
        <th>Début</th>
        <th>Fin</th>
        <th>Durée</th>
        <th>Mode</th>
        <th>Statut</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>

    <?php if (!$sessions): ?>
      <tr>
        <td colspan="6" style="background:#fff;border:1px dashed #e5e7eb;border-radius:10px">
          <span class="muted">Aucune session programmée.</span>
        </td>
      </tr>
    <?php endif; ?>

    <?php foreach ($sessions as $s): ?>
      <tr>
        <td class="cell-date">
          <?= e(date('d/m/Y', strtotime($s['date_debut']))); ?>
        </td>

        <td class="cell-date">
          <?= $s['date_fin'] ? e(date('d/m/Y', strtotime($s['date_fin']))) : '—'; ?>
        </td>

        <td><?= e($s['duree']); ?></td>

        <td class="cell-mode">
          <?= e(str_replace('_',' ', $s['mode'])); ?>
        </td>

        <td>
          <span class="pill <?= $s['statut']==='ouvert'?'ok':'wait'; ?>">
            <?= e($s['statut']); ?>
          </span>
        </td>

        <td class="cell-actions">
          <a class="btn btn-secondary"
             href="edit.php?id=<?= (int)$s['id']; ?>&formation_id=<?= (int)$formationId; ?>">
            &#9998; Éditer
          </a>

          <a class="btn btn-dark"
             href="delete.php?id=<?= (int)$s['id']; ?>&formation_id=<?= (int)$formationId; ?>&csrf=<?= csrf_token(); ?>"
             onclick="return confirm('Désactiver cette session ?');">
            &#128274;
          </a>
        </td>
      </tr>
    <?php endforeach; ?>

    </tbody>
  </table>

</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout/layout.php';