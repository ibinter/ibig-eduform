<?php
declare(strict_types=1);

/**
 * ============================================================
 * ADMIN — OPPORTUNITÉS & EMPLOI — INDEX (filtres + pagination)
 * ============================================================
 */

require_once __DIR__ . '/../_init.php';

$mw = realpath(__DIR__ . '/../auth/middleware.php');
if (!$mw) { die('Middleware introuvable'); }
require_once $mw;
Middleware::requireAuth();

$u = function_exists('auth_user') ? (auth_user() ?? []) : [];

$pageTitle  = 'Opportunités & Emploi';
$activeMenu = 'opportunites';

$pdo = Database::connect();

/* =========================
   FILTRES
========================= */
$q       = trim((string)($_GET['q'] ?? ''));
$statut  = trim((string)($_GET['statut'] ?? ''));
$domaine = trim((string)($_GET['domaine'] ?? ''));
$type    = trim((string)($_GET['type'] ?? ''));
$dFrom   = trim((string)($_GET['from'] ?? ''));
$dTo     = trim((string)($_GET['to'] ?? ''));

$allowedStatuts = ['active','expiree','brouillon'];
$allowedTypes   = ['CDI','CDD','Stage','Freelance','Mission'];

$where = "WHERE 1=1"; $params = [];
if ($q !== '') { $where .= " AND (titre LIKE :q OR entreprise LIKE :q OR lieu LIKE :q)"; $params[':q'] = "%{$q}%"; }
if ($statut !== '' && in_array($statut, $allowedStatuts, true)) { $where .= " AND statut = :statut"; $params[':statut'] = $statut; }
if ($domaine !== '') { $where .= " AND domaine = :domaine"; $params[':domaine'] = $domaine; }
if ($type !== '' && in_array($type, $allowedTypes, true)) { $where .= " AND type = :type"; $params[':type'] = $type; }
if ($dFrom !== '') { $where .= " AND DATE(created_at) >= :dfrom"; $params[':dfrom'] = $dFrom; }
if ($dTo   !== '') { $where .= " AND DATE(created_at) <= :dto";   $params[':dto']   = $dTo; }

$curFilters = array_filter([
  'q'=>$q,'statut'=>$statut,'domaine'=>$domaine,'type'=>$type,'from'=>$dFrom,'to'=>$dTo,'per'=>(string)($_GET['per'] ?? ''),
], static fn($v) => $v !== '');

/* Domaines pour le filtre */
$domaines = [];
try { $domaines = $pdo->query("SELECT DISTINCT domaine FROM opportunites_emploi WHERE domaine IS NOT NULL AND domaine<>'' ORDER BY domaine")->fetchAll(PDO::FETCH_COLUMN); } catch(Throwable $e){}

/* =========================
   COMPTEURS (filtré)
========================= */
$total=0; $nbActive=0; $nbExpiree=0; $nbBrouillon=0;
try {
  $agg = $pdo->prepare("
    SELECT COUNT(*) total,
           SUM(CASE WHEN statut='active' THEN 1 ELSE 0 END) act,
           SUM(CASE WHEN statut='expiree' THEN 1 ELSE 0 END) exp,
           SUM(CASE WHEN statut='brouillon' THEN 1 ELSE 0 END) bro
    FROM opportunites_emploi $where");
  $agg->execute($params);
  $k = $agg->fetch(PDO::FETCH_ASSOC) ?: [];
  $total       = (int)($k['total'] ?? 0);
  $nbActive    = (int)($k['act'] ?? 0);
  $nbExpiree   = (int)($k['exp'] ?? 0);
  $nbBrouillon = (int)($k['bro'] ?? 0);
} catch (Throwable $e) {}

/* =========================
   PAGINATION
========================= */
$allowedPer = [50,100,500,1000];
$perPage = (int)($_GET['per'] ?? 50);
if (!in_array($perPage, $allowedPer, true)) { $perPage = 50; }
$totalPages = max(1,(int)ceil($total/$perPage));
$page = max(1,(int)($_GET['page'] ?? 1));
if ($page > $totalPages) { $page = $totalPages; }
$offset = ($page-1)*$perPage;

$stmt = $pdo->prepare("
  SELECT id, titre, entreprise, domaine, type, lieu, reserve_apprenants, statut, created_at
  FROM opportunites_emploi
  {$where}
  ORDER BY created_at DESC
  LIMIT " . (int)$perPage . " OFFSET " . (int)$offset);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageUrl = static function (int $p) use ($curFilters): string {
    return '?' . http_build_query(array_merge($curFilters, ['page' => $p]));
};
$from = $total ? ($offset + 1) : 0;
$to   = min($offset + $perPage, $total);

$stMeta = ['active'=>['Active','ok'],'expiree'=>['Expirée','annule'],'brouillon'=>['Brouillon','wait']];

ob_start();
?>
<style>
.op-kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin:4px 0 16px}
.op-kpi{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:14px 16px;box-shadow:0 6px 18px rgba(15,23,42,.05)}
.op-kpi b{display:block;font-size:24px;font-weight:800;line-height:1.1;color:#0f172a}
.op-kpi span{font-size:12.5px;color:#6b7280;font-weight:600}
.op-kpi.k-ok b{color:#16a34a}.op-kpi.k-exp b{color:#dc2626}.op-kpi.k-bro b{color:#b45309}

.op-filters{background:#f8fafc;border:1px solid #e5e7eb;border-radius:14px;padding:14px;margin:0 0 14px}
.op-filters .grid{display:grid;grid-template-columns:repeat(4,1fr);gap:10px}
.op-filters .f{display:flex;flex-direction:column;gap:4px}
.op-filters label{font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.3px}
.op-filters input,.op-filters select{padding:9px 11px;border:1px solid #e5e7eb;border-radius:10px;font-size:13.5px;background:#fff;outline:none;width:100%}
.op-filters input:focus,.op-filters select:focus{border-color:#1f3fe0;box-shadow:0 0 0 3px rgba(31,63,224,.12)}
.btn-apply{background:linear-gradient(135deg,#1f3fe0,#3b82f6);color:#fff;border:0;padding:10px 18px;border-radius:10px;font-weight:800;font-size:13.5px;cursor:pointer}
.btn-reset,.op-top a{background:#fff;border:1px solid #e5e7eb;color:#334155;padding:10px 16px;border-radius:10px;font-weight:700;font-size:13.5px;text-decoration:none;display:inline-flex;align-items:center;gap:6px}
.op-top a.new{background:linear-gradient(135deg,#1f3fe0,#3b82f6);color:#fff;border-color:transparent}

.op-bar{margin:0 0 10px;font-size:13px;color:#475569}
.op-table{width:100%;border-collapse:collapse}
.op-table th{font-size:12px;text-transform:uppercase;letter-spacing:.4px;color:#6b7280;text-align:left;padding:10px 12px;border-bottom:2px solid #eef2f7}
.op-table td{padding:12px;border-bottom:1px solid #f1f5f9;vertical-align:middle;font-size:13.5px}
.op-table tr:hover td{background:#fafbff}
.op-title b{color:#0f172a}.op-title small{display:block;color:#64748b;font-size:12px;margin-top:1px}
.tag{display:inline-block;font-size:11px;font-weight:800;padding:3px 9px;border-radius:999px;background:#eef2ff;color:#3730a3}
.pill{padding:4px 10px;border-radius:999px;font-size:11px;font-weight:800;white-space:nowrap;display:inline-block}
.pill.ok{background:#dcfce7;color:#166534}.pill.wait{background:#fff7ed;color:#9a3412}.pill.annule{background:#fee2e2;color:#991b1b}
.pill.res{background:#fef9c3;color:#854d0e}.pill.no{background:#f1f5f9;color:#475569}
.op-acts{display:flex;gap:6px;flex-wrap:wrap}
.abtn{display:inline-flex;align-items:center;gap:5px;padding:7px 11px;border-radius:9px;font-size:12px;font-weight:800;border:0;cursor:pointer;text-decoration:none;color:#fff !important;line-height:1;font-family:inherit}
.abtn.edit{background:#1f3fe0 !important}.abtn.view{background:#475569 !important}
.abtn.on{background:#16a34a !important}.abtn.off{background:#b45309 !important}.abtn.del{background:#dc2626 !important}
.pager{display:flex;gap:6px;justify-content:center;align-items:center;flex-wrap:wrap;margin:18px 0 4px}
.pager a,.pager span{min-width:36px;height:36px;padding:0 10px;border-radius:9px;display:inline-flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;text-decoration:none;border:1px solid #e5e7eb;color:#334155;background:#fff}
.pager a:hover{border-color:#1f3fe0;color:#1f3fe0}.pager .cur{background:linear-gradient(135deg,#1f3fe0,#3b82f6);color:#fff;border-color:transparent}.pager .dis{opacity:.4;pointer-events:none}
@media(max-width:980px){.op-kpis{grid-template-columns:1fr 1fr}.op-filters .grid{grid-template-columns:1fr 1fr}}
</style>

<div class="card">

  <!-- HEADER -->
  <div class="op-top" style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">
    <h2 style="margin:0">💼 Opportunités &amp; Emploi</h2>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
      <a class="new" href="create.php">➕ Nouvelle offre</a>
      <a target="_blank" href="/opportunites-emploi.php">🌐 Voir sur le site</a>
    </div>
  </div>

  <!-- KPIs -->
  <div class="op-kpis">
    <div class="op-kpi"><b><?= (int)$total; ?></b><span>Total (filtré)</span></div>
    <div class="op-kpi k-ok"><b><?= (int)$nbActive; ?></b><span>Actives</span></div>
    <div class="op-kpi k-exp"><b><?= (int)$nbExpiree; ?></b><span>Expirées</span></div>
    <div class="op-kpi k-bro"><b><?= (int)$nbBrouillon; ?></b><span>Brouillons</span></div>
  </div>

  <!-- FILTRES -->
  <form method="get" class="op-filters">
    <div class="grid">
      <div class="f">
        <label>Recherche</label>
        <input type="text" name="q" value="<?= e($q) ?>" placeholder="Titre, entreprise, lieu…">
      </div>
      <div class="f">
        <label>Statut</label>
        <select name="statut">
          <option value="">Tous</option>
          <?php foreach ($allowedStatuts as $s): ?>
            <option value="<?= $s ?>" <?= $statut===$s?'selected':'' ?>><?= e(ucfirst($s)) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="f">
        <label>Domaine</label>
        <select name="domaine">
          <option value="">Tous</option>
          <?php foreach ($domaines as $d): ?>
            <option value="<?= e($d) ?>" <?= $domaine===(string)$d?'selected':'' ?>><?= e($d) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="f">
        <label>Type de contrat</label>
        <select name="type">
          <option value="">Tous</option>
          <?php foreach ($allowedTypes as $t): ?>
            <option value="<?= $t ?>" <?= $type===$t?'selected':'' ?>><?= $t ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="f">
        <label>Par page</label>
        <select name="per" onchange="this.form.submit()">
          <?php foreach ($allowedPer as $pp): ?>
            <option value="<?= $pp; ?>" <?= $perPage===$pp?'selected':''; ?>><?= $pp; ?> / page</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="f">
        <label>Du</label>
        <input type="date" name="from" value="<?= e($dFrom); ?>">
      </div>
      <div class="f">
        <label>Au</label>
        <input type="date" name="to" value="<?= e($dTo); ?>">
      </div>
    </div>
    <div style="display:flex;gap:8px;margin-top:12px">
      <button type="submit" class="btn-apply">🔎 Appliquer</button>
      <a class="btn-reset" href="index.php">↺ Réinitialiser</a>
    </div>
  </form>

  <div class="op-bar"><b><?= (int)$from; ?></b>–<b><?= (int)$to; ?></b> sur <b><?= (int)$total; ?></b> offre(s) · page <?= (int)$page; ?>/<?= (int)$totalPages; ?></div>

  <!-- TABLE -->
  <table class="op-table">
    <thead>
      <tr><th>Titre / Entreprise</th><th>Domaine</th><th>Type</th><th>Lieu</th><th>Réservé apprenants</th><th>Statut</th><th>Date</th><th>Gestion</th></tr>
    </thead>
    <tbody>
    <?php if (!$rows): ?>
      <tr><td colspan="8" style="padding:22px;text-align:center;color:#64748b">Aucune opportunité ne correspond à ces filtres.</td></tr>
    <?php else: foreach ($rows as $r):
        $id = (int)$r['id'];
        $st = (string)$r['statut'];
        $sm = $stMeta[$st] ?? [ucfirst($st), 'wait'];
        $reserve = (int)($r['reserve_apprenants'] ?? 0) === 1;
        $isActive = ($st === 'active');
    ?>
      <tr>
        <td class="op-title"><b><?= e($r['titre']) ?></b><small><?= e((string)$r['entreprise']) ?></small></td>
        <td><?= e($r['domaine'] ?: '—') ?></td>
        <td><span class="tag"><?= e($r['type'] ?: '—') ?></span></td>
        <td><?= e((string)$r['lieu'] ?: '—') ?></td>
        <td><span class="pill <?= $reserve ? 'res' : 'no' ?>"><?= $reserve ? 'Oui' : 'Non' ?></span></td>
        <td><span class="pill <?= e($sm[1]) ?>"><?= e($sm[0]) ?></span></td>
        <td><small><?= !empty($r['created_at']) ? date('d/m/Y', strtotime((string)$r['created_at'])) : '—' ?></small></td>
        <td>
          <div class="op-acts">
            <a class="abtn edit" href="edit.php?id=<?= $id ?>" title="Éditer">✏ Éditer</a>
            <a class="abtn view" target="_blank" href="/opportunite.php?id=<?= $id ?>" title="Voir en ligne">👁 Voir</a>
            <form method="post" action="toggle-status.php" style="display:inline">
              <?= csrf_field(); ?>
              <input type="hidden" name="id" value="<?= $id ?>">
              <input type="hidden" name="statut" value="<?= $isActive ? 'expiree' : 'active' ?>">
              <button class="abtn <?= $isActive ? 'off' : 'on' ?>" title="<?= $isActive ? 'Marquer expirée' : 'Activer' ?>"><?= $isActive ? '⏸ Expirer' : '▶ Activer' ?></button>
            </form>
            <form method="post" action="delete.php" onsubmit="return confirm('Supprimer définitivement cette offre ?');" style="display:inline">
              <?= csrf_field(); ?>
              <input type="hidden" name="id" value="<?= $id ?>">
              <button class="abtn del" title="Supprimer">✕</button>
            </form>
          </div>
        </td>
      </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>

  <!-- PAGINATION -->
  <?php if ($totalPages > 1): ?>
    <div class="pager">
      <?php if ($page > 1): ?>
        <a href="<?= e($pageUrl(1)); ?>">«</a><a href="<?= e($pageUrl($page-1)); ?>">‹</a>
      <?php else: ?><span class="dis">«</span><span class="dis">‹</span><?php endif; ?>
      <?php
        $start = max(1, $page-2); $end = min($totalPages, $page+2);
        if ($start > 1) echo '<span class="dis">…</span>';
        for ($i=$start;$i<=$end;$i++):
          if ($i===$page): ?><span class="cur"><?= $i; ?></span><?php else: ?><a href="<?= e($pageUrl($i)); ?>"><?= $i; ?></a><?php endif;
        endfor;
        if ($end < $totalPages) echo '<span class="dis">…</span>';
      ?>
      <?php if ($page < $totalPages): ?>
        <a href="<?= e($pageUrl($page+1)); ?>">›</a><a href="<?= e($pageUrl($totalPages)); ?>">»</a>
      <?php else: ?><span class="dis">›</span><span class="dis">»</span><?php endif; ?>
    </div>
  <?php endif; ?>

</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout/layout.php';
