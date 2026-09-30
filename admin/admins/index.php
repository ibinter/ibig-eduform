<?php
declare(strict_types=1);

require_once __DIR__ . '/../auth/guard.php';
require_permission('manage_admins');

require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../core/csrf.php';
require_once __DIR__ . '/../auth/middleware.php';
require_once __DIR__ . '/../../core/auth.php';

Middleware::requireAuth();
$me  = auth_user();
$pdo = Database::connect();

$pageTitle  = "Administrateurs";
$activeMenu = "admins";

/* ── Rôles "admin" (exclusion des simples utilisateurs) ── */
$ADMIN_ROLES = ['super_admin', 'admin', 'rh', 'commercial'];

$ROLE_LABELS = [
    'super_admin' => ['Super Admin',  '#7c3aed', '#ede9fe'],
    'admin'       => ['Admin',        '#1e40af', '#e0ecff'],
    'commercial'  => ['Commercial',   '#166534', '#dcfce7'],
    'rh'          => ['RH',           '#9a3412', '#fff7ed'],
    'user'        => ['Utilisateur',  '#374151', '#f3f4f6'],
];

/* ── Filtres ── */
$q      = trim((string)($_GET['q']      ?? ''));
$roleF  = trim((string)($_GET['role']   ?? ''));
$statF  = trim((string)($_GET['statut'] ?? ''));

$allowedPer = [25, 50, 100];
$per  = (int)($_GET['per'] ?? 25);
if (!in_array($per, $allowedPer, true)) { $per = 25; }
$page = max(1, (int)($_GET['page'] ?? 1));

$where = ["u.role IN ('" . implode("','", $ADMIN_ROLES) . "')"];
$params = [];

if ($q !== '') {
    $where[]      = "(u.first_name LIKE :q OR u.last_name LIKE :q OR u.email LIKE :q)";
    $params[':q'] = '%' . $q . '%';
}
if ($roleF !== '' && in_array($roleF, $ADMIN_ROLES, true)) {
    $where[]       = 'u.role = :role';
    $params[':role'] = $roleF;
}
if ($statF !== '') {
    $where[]        = 'u.status = :stat';
    $params[':stat'] = $statF;
}
$whereSql = 'WHERE ' . implode(' AND ', $where);

/* ── KPIs ── */
$total = $nbActifs = $nbInactifs = $nbToday = 0;
try {
    $agg = $pdo->prepare("
        SELECT
            COUNT(*) AS total,
            SUM(CASE WHEN u.status = 'active' THEN 1 ELSE 0 END) AS actifs,
            SUM(CASE WHEN u.status <> 'active' THEN 1 ELSE 0 END) AS inactifs,
            SUM(CASE WHEN DATE(u.created_at) = CURDATE() THEN 1 ELSE 0 END) AS today
        FROM users u $whereSql
    ");
    $agg->execute($params);
    $k = $agg->fetch(PDO::FETCH_ASSOC) ?: [];
    $total     = (int)($k['total']    ?? 0);
    $nbActifs  = (int)($k['actifs']   ?? 0);
    $nbInactifs= (int)($k['inactifs'] ?? 0);
    $nbToday   = (int)($k['today']    ?? 0);
} catch (Throwable $e) {}

/* ── Pagination ── */
$pages  = max(1, (int)ceil($total / $per));
if ($page > $pages) { $page = $pages; }
$offset = ($page - 1) * $per;

/* ── Lignes ── */
$stmt = $pdo->prepare("
    SELECT u.id, u.first_name, u.last_name, u.email, u.role, u.status, u.created_at
    FROM users u
    $whereSql
    ORDER BY
        FIELD(u.role,'super_admin','admin','commercial','rh'),
        u.created_at DESC
    LIMIT " . (int)$per . " OFFSET " . (int)$offset
);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ── Helpers pagination ── */
$curFilters = array_filter([
    'q' => $q, 'role' => $roleF, 'statut' => $statF, 'per' => $per,
], static fn($v) => $v !== '' && $v !== 0);
$pageUrl = static function (int $p) use ($curFilters): string {
    return '?' . http_build_query(array_merge($curFilters, ['page' => $p]));
};
$from = $total ? ($offset + 1) : 0;
$to   = min($offset + $per, $total);

ob_start();
?>

<style>
.adm-kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin:0 0 18px}
.adm-kpi{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:16px 18px;box-shadow:0 4px 14px rgba(15,23,42,.06)}
.adm-kpi b{display:block;font-size:28px;font-weight:900;line-height:1;color:#0f172a}
.adm-kpi span{font-size:12px;color:#6b7280;font-weight:600;text-transform:uppercase;letter-spacing:.3px}
.adm-kpi.k-total{border-top:3px solid #6366f1}.adm-kpi.k-total b{color:#4f46e5}
.adm-kpi.k-ok   {border-top:3px solid #22c55e}.adm-kpi.k-ok    b{color:#16a34a}
.adm-kpi.k-off  {border-top:3px solid #f59e0b}.adm-kpi.k-off   b{color:#b45309}
.adm-kpi.k-new  {border-top:3px solid #3b82f6}.adm-kpi.k-new   b{color:#1d4ed8}

.adm-filters{background:#f8fafc;border:1px solid #e5e7eb;border-radius:14px;padding:14px;margin:0 0 14px}
.adm-filters .grid{display:grid;grid-template-columns:repeat(4,1fr);gap:10px}
.adm-filters .f{display:flex;flex-direction:column;gap:4px}
.adm-filters label{font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.3px}
.adm-filters input,.adm-filters select{padding:9px 11px;border:1px solid #e2e8f0;border-radius:10px;font-size:13.5px;background:#fff;outline:none;width:100%;transition:border-color .15s}
.adm-filters input:focus,.adm-filters select:focus{border-color:#1f3fe0;box-shadow:0 0 0 3px rgba(31,63,224,.1)}
.btn-apply{background:linear-gradient(135deg,#1f3fe0,#3b82f6);color:#fff;border:0;padding:10px 20px;border-radius:10px;font-weight:800;font-size:13.5px;cursor:pointer}
.btn-reset{background:#fff;border:1px solid #e5e7eb;color:#334155;padding:10px 16px;border-radius:10px;font-weight:700;font-size:13.5px;text-decoration:none;display:inline-flex;align-items:center}

table.adm-table{width:100%;border-collapse:collapse;font-size:13.5px}
table.adm-table th{font-size:11.5px;text-transform:uppercase;letter-spacing:.4px;color:#6b7280;text-align:left;padding:10px 12px;border-bottom:2px solid #eef2f7;background:#fafbff;white-space:nowrap}
table.adm-table td{padding:12px;border-bottom:1px solid #f1f5f9;vertical-align:middle}
table.adm-table tr:hover td{background:#f0f6ff}

.avatar{width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,#1f3fe0,#6366f1);color:#fff;font-weight:900;font-size:13px;display:flex;align-items:center;justify-content:center;flex-shrink:0;letter-spacing:.03em}
.user-cell{display:flex;align-items:center;gap:10px}
.user-cell strong{font-size:13.5px;color:#0f172a;display:block}
.user-cell small{font-size:11.5px;color:#94a3b8}

.role-pill{display:inline-flex;padding:4px 11px;border-radius:999px;font-size:11px;font-weight:800;white-space:nowrap}
.status-pill{display:inline-flex;align-items:center;gap:4px;padding:4px 11px;border-radius:999px;font-size:11px;font-weight:800}
.status-pill::before{content:'';width:6px;height:6px;border-radius:50%;background:currentColor;display:inline-block}
.s-active{background:#dcfce7;color:#166534}.s-inactive{background:#fee2e2;color:#991b1b}

.tbl-actions{display:flex;gap:5px;flex-wrap:wrap}
.btn-sm{padding:5px 12px;border-radius:8px;font-size:12px;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:4px;border:1px solid transparent;cursor:pointer;white-space:nowrap}
.btn-edit{background:#e0ecff;color:#1e40af;border-color:#bfdbfe}.btn-edit:hover{background:#bfdbfe}
.btn-on  {background:#dcfce7;color:#166534;border-color:#bbf7d0}.btn-on:hover{background:#bbf7d0}
.btn-off {background:#fff7ed;color:#9a3412;border-color:#fed7aa}.btn-off:hover{background:#fed7aa}
.btn-del {background:#fee2e2;color:#991b1b;border-color:#fecaca}.btn-del:hover{background:#fecaca}
.btn-dis {opacity:.35;pointer-events:none;cursor:default}
.btn-add {background:linear-gradient(135deg,#1f3fe0,#3b82f6);color:#fff;padding:9px 18px;border-radius:10px;font-size:13px;font-weight:800;text-decoration:none;display:inline-flex;align-items:center;gap:6px}

.pager{display:flex;gap:6px;justify-content:center;align-items:center;flex-wrap:wrap;margin:20px 0 4px}
.pager a,.pager span{min-width:36px;height:36px;padding:0 10px;border-radius:9px;display:inline-flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;text-decoration:none;border:1px solid #e5e7eb;color:#334155;background:#fff}
.pager a:hover{border-color:#1f3fe0;color:#1f3fe0}
.pager .cur{background:linear-gradient(135deg,#1f3fe0,#3b82f6);color:#fff;border-color:transparent}
.pager .dis{opacity:.35;pointer-events:none}
.date-cell{font-size:12px;color:#475569;line-height:1.5}
.date-cell .heure{color:#94a3b8;font-size:11px}
@media(max-width:900px){.adm-kpis{grid-template-columns:1fr 1fr}.adm-filters .grid{grid-template-columns:1fr 1fr}}
</style>

<div class="card">

  <!-- HEADER -->
  <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:4px">
    <h2 style="margin:0">&#128100; Administrateurs</h2>
    <?php if (($me['role'] ?? '') === 'super_admin'): ?>
      <a class="btn-add" href="create.php">&#43; Nouvel administrateur</a>
    <?php endif; ?>
  </div>

  <!-- KPIs -->
  <div class="adm-kpis" style="margin-top:16px">
    <div class="adm-kpi k-total"><b><?= $total ?></b><span>Total admins</span></div>
    <div class="adm-kpi k-ok">  <b><?= $nbActifs ?></b><span>&#9989; Actifs</span></div>
    <div class="adm-kpi k-off"> <b><?= $nbInactifs ?></b><span>&#9888;&#65039; Inactifs</span></div>
    <div class="adm-kpi k-new"> <b><?= $nbToday ?></b><span>&#128197; Aujourd'hui</span></div>
  </div>

  <!-- FILTRES -->
  <form method="get" class="adm-filters">
    <div class="grid">
      <div class="f">
        <label>&#128269; Recherche</label>
        <input type="text" name="q" value="<?= e($q) ?>" placeholder="Nom, email…" autofocus>
      </div>
      <div class="f">
        <label>Rôle</label>
        <select name="role">
          <option value="">Tous les rôles</option>
          <?php foreach ($ADMIN_ROLES as $r): ?>
            <option value="<?= $r ?>" <?= $roleF === $r ? 'selected' : '' ?>><?= $ROLE_LABELS[$r][0] ?? $r ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="f">
        <label>Statut</label>
        <select name="statut">
          <option value="">Tous</option>
          <option value="active"   <?= $statF === 'active'   ? 'selected' : '' ?>>&#9989; Actif</option>
          <option value="inactive" <?= $statF === 'inactive' ? 'selected' : '' ?>>&#9940; Inactif</option>
        </select>
      </div>
      <div class="f">
        <label>Par page</label>
        <select name="per" onchange="this.form.submit()">
          <?php foreach ($allowedPer as $pp): ?>
            <option value="<?= $pp ?>" <?= $per === $pp ? 'selected' : '' ?>><?= $pp ?> / page</option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div style="display:flex;gap:8px;margin-top:12px;align-items:center">
      <button type="submit" class="btn-apply">&#128269; Appliquer</button>
      <a class="btn-reset" href="index.php">&#8635; Réinitialiser</a>
    </div>
  </form>

  <!-- BARRE INFO -->
  <div style="display:flex;justify-content:space-between;align-items:center;font-size:13px;color:#475569;margin:0 0 10px">
    <span>Affichage <b><?= $from ?></b>–<b><?= $to ?></b> sur <b><?= $total ?></b> administrateur(s) &nbsp;·&nbsp; Page <?= $page ?> / <?= $pages ?></span>
  </div>

  <!-- TABLE -->
  <div style="overflow-x:auto">
  <table class="adm-table">
    <thead>
      <tr>
        <th>#</th>
        <th>Administrateur</th>
        <th>Email</th>
        <th>Rôle</th>
        <th>Statut</th>
        <th>Créé le</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>

    <?php if (!$rows): ?>
      <tr><td colspan="7" style="padding:40px;text-align:center;color:#6b7280">Aucun administrateur trouvé.</td></tr>
    <?php endif; ?>

    <?php foreach ($rows as $idx => $r):
      $initials = strtoupper(substr($r['first_name'] ?? 'A', 0, 1) . substr($r['last_name'] ?? 'A', 0, 1));
      $fullName = trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? ''));
      $rl = $ROLE_LABELS[$r['role']] ?? ['', '#374151', '#f3f4f6'];
      $isMe    = (int)$r['id'] === (int)($me['id'] ?? 0);
      $isSA    = $r['role'] === 'super_admin';
      $canAct  = ($me['role'] === 'super_admin') && !$isMe;
    ?>
      <tr>
        <td style="color:#94a3b8;font-size:12px;font-weight:700"><?= $offset + $idx + 1 ?></td>
        <td>
          <div class="user-cell">
            <div class="avatar"><?= e($initials) ?></div>
            <div>
              <strong><?= e($fullName ?: '—') ?><?php if ($isMe): ?> <span style="font-size:10px;background:#fde68a;color:#92400e;border-radius:5px;padding:1px 6px;font-weight:800;margin-left:4px">VOUS</span><?php endif; ?></strong>
              <small>ID #<?= (int)$r['id'] ?></small>
            </div>
          </div>
        </td>
        <td style="font-size:13px;color:#334155"><?= e($r['email'] ?? '—') ?></td>
        <td>
          <span class="role-pill" style="background:<?= $rl[2] ?>;color:<?= $rl[1] ?>"><?= e($rl[0]) ?></span>
        </td>
        <td>
          <span class="status-pill <?= $r['status'] === 'active' ? 's-active' : 's-inactive' ?>">
            <?= $r['status'] === 'active' ? 'Actif' : 'Inactif' ?>
          </span>
        </td>
        <td class="date-cell">
          <?php if (!empty($r['created_at'])): ?>
            <?= date('d/m/Y', strtotime($r['created_at'])) ?><br>
            <span class="heure"><?= date('H:i', strtotime($r['created_at'])) ?></span>
          <?php else: ?>—<?php endif; ?>
        </td>
        <td>
          <div class="tbl-actions">
            <?php if ($canAct): ?>
              <a class="btn-sm btn-edit" href="edit.php?id=<?= (int)$r['id'] ?>">&#9998; Éditer</a>
              <?php if ($r['status'] === 'active'): ?>
                <a class="btn-sm btn-off" href="toggle.php?id=<?= (int)$r['id'] ?>&csrf=<?= csrf_token() ?>"
                   onclick="return confirm('Désactiver <?= e(addslashes($fullName)) ?> ?')">&#9940; Désactiver</a>
              <?php else: ?>
                <a class="btn-sm btn-on" href="toggle.php?id=<?= (int)$r['id'] ?>&csrf=<?= csrf_token() ?>">&#9989; Activer</a>
              <?php endif; ?>
              <?php if (!$isSA): ?>
                <form method="post" action="delete.php" onsubmit="return confirm('Supprimer définitivement <?= e(addslashes($fullName)) ?> ?')" style="display:inline">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                  <button type="submit" class="btn-sm btn-del">&#128465; Supprimer</button>
                </form>
              <?php endif; ?>
            <?php elseif ($isMe): ?>
              <a class="btn-sm btn-edit" href="edit.php?id=<?= (int)$r['id'] ?>">&#9998; Mon profil</a>
            <?php else: ?>
              <span style="color:#d1d5db;font-size:12px">—</span>
            <?php endif; ?>
          </div>
        </td>
      </tr>
    <?php endforeach; ?>

    </tbody>
  </table>
  </div>

  <!-- PAGINATION -->
  <?php if ($pages > 1): ?>
    <div class="pager">
      <?php if ($page > 1): ?>
        <a href="<?= e($pageUrl(1)) ?>">«</a>
        <a href="<?= e($pageUrl($page - 1)) ?>">‹</a>
      <?php else: ?>
        <span class="dis">«</span><span class="dis">‹</span>
      <?php endif; ?>
      <?php
        $start = max(1, $page - 2); $end = min($pages, $page + 2);
        if ($start > 1) echo '<span class="dis">…</span>';
        for ($i = $start; $i <= $end; $i++):
      ?>
        <?php if ($i === $page): ?><span class="cur"><?= $i ?></span>
        <?php else: ?><a href="<?= e($pageUrl($i)) ?>"><?= $i ?></a><?php endif; ?>
      <?php endfor; ?>
      <?php if ($end < $pages) echo '<span class="dis">…</span>'; ?>
      <?php if ($page < $pages): ?>
        <a href="<?= e($pageUrl($page + 1)) ?>">›</a>
        <a href="<?= e($pageUrl($pages)) ?>">»</a>
      <?php else: ?>
        <span class="dis">›</span><span class="dis">»</span>
      <?php endif; ?>
    </div>
  <?php endif; ?>

</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout/layout.php';
