<?php
declare(strict_types=1);
/* ============================================================
   ADMIN — LEADS (capture de prospects) — filtres + contact direct
============================================================ */
require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../core/csrf.php';
require_once __DIR__ . '/../auth/middleware.php';
require_once __DIR__ . '/../../core/auth.php';

Middleware::requireAuth();
$u   = auth_user();
$pdo = Database::connect();

$canDelete = (($u['role'] ?? '') === 'super_admin');

/* ============================================================
   HELPERS
============================================================ */
if (!function_exists('pre_wa_number')) {
    function pre_wa_number(?string $phone): string {
        $d = preg_replace('/\D+/', '', (string)$phone);
        if ($d === '') return '';
        if (strpos($d, '225') === 0) return $d;
        if (strlen($d) <= 10) return '225' . $d;
        return $d;
    }
}
if (!function_exists('lead_contacts')) {
    /* Extrait email + téléphone d'un champ contact libre
       (ex. "jean@x.com · WhatsApp: +225 07…", ou juste un numéro/email). */
    function lead_contacts(string $c): array {
        $email = '';
        if (preg_match('/[\w.\-+]+@[\w.\-]+\.[A-Za-z]{2,}/', $c, $m)) { $email = $m[0]; }
        $phone = '';
        if (preg_match('/whatsapp\s*:?\s*([+\d][\d\s().\-]{6,})/i', $c, $m)) {
            $phone = $m[1];
        } else {
            $cNoEmail = $email !== '' ? str_replace($email, ' ', $c) : $c;
            if (preg_match('/[+]?\d[\d\s().\-]{6,}\d/', $cNoEmail, $m)) { $phone = $m[0]; }
        }
        return ['email' => trim($email), 'phone' => trim($phone)];
    }
}

/* ============================================================
   FILTRES
============================================================ */
$type   = (string)($_GET['type'] ?? '');
$statut = (string)($_GET['statut'] ?? '');
$q      = trim((string)($_GET['q'] ?? ''));
$dFrom  = trim((string)($_GET['from'] ?? ''));
$dTo    = trim((string)($_GET['to'] ?? ''));

$where = []; $params = [];
if (in_array($type, ['calendrier','rappel','alerte','tdr'], true)) { $where[] = 'type = :type'; $params[':type'] = $type; }
if (in_array($statut, ['nouveau','traite'], true))                 { $where[] = 'statut = :statut'; $params[':statut'] = $statut; }
if ($q !== '') { $where[] = '(nom LIKE :q OR contact LIKE :q OR formation_titre LIKE :q)'; $params[':q'] = '%' . $q . '%'; }
if ($dFrom !== '') { $where[] = 'DATE(created_at) >= :dfrom'; $params[':dfrom'] = $dFrom; }
if ($dTo !== '')   { $where[] = 'DATE(created_at) <= :dto';   $params[':dto']   = $dTo; }
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

/* filtres courants (pour liens pagination / export / redirections) */
$curFilters = array_filter([
    'type' => $type, 'statut' => $statut, 'q' => $q, 'from' => $dFrom, 'to' => $dTo,
    'per'  => (string)($_GET['per'] ?? ''),
], static fn($v) => $v !== '');

/* ============================================================
   ACTIONS (POST)
============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $act = (string)($_POST['action'] ?? '');
    $id  = (int)($_POST['id'] ?? 0);
    $back = 'index.php' . ($curFilters ? ('?' . http_build_query($curFilters)) : '');
    if ($id > 0 && $act === 'toggle') {
        $pdo->prepare("UPDATE leads SET statut = IF(statut='nouveau','traite','nouveau') WHERE id=?")->execute([$id]);
        redirect($back);
    }
    if ($id > 0 && $act === 'delete' && $canDelete) {
        $pdo->prepare("DELETE FROM leads WHERE id=?")->execute([$id]);
        redirect('index.php' . ($curFilters ? ('?' . http_build_query($curFilters) . '&deleted=1') : '?deleted=1'));
    }
    redirect($back);
}

/* ============================================================
   EXPORT CSV (respecte les filtres)
============================================================ */
if (($_GET['export'] ?? '') === 'csv') {
    $st = $pdo->prepare("SELECT created_at,type,nom,contact,formation_titre,statut FROM leads $whereSql ORDER BY created_at DESC");
    $st->execute($params);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=leads-' . date('Ymd-His') . '.csv');
    $out = fopen('php://output', 'w');
    fprintf($out, "\xEF\xBB\xBF");
    fputcsv($out, ['Date','Type','Nom','Contact','Formation','Statut'], ';');
    while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($out, [$r['created_at'],$r['type'],$r['nom'],$r['contact'],$r['formation_titre'],$r['statut']], ';');
    }
    fclose($out); exit;
}

/* ============================================================
   COMPTEURS (sur le périmètre filtré)
============================================================ */
$total = 0; $nbNouveau = 0; $nbTraite = 0; $nbToday = 0;
try {
    $agg = $pdo->prepare("
        SELECT
            COUNT(*) AS total,
            SUM(CASE WHEN statut='nouveau' THEN 1 ELSE 0 END) AS nouveau,
            SUM(CASE WHEN statut='traite'  THEN 1 ELSE 0 END) AS traite,
            SUM(CASE WHEN DATE(created_at)=CURDATE() THEN 1 ELSE 0 END) AS today
        FROM leads $whereSql
    ");
    $agg->execute($params);
    $k = $agg->fetch(PDO::FETCH_ASSOC) ?: [];
    $total     = (int)($k['total'] ?? 0);
    $nbNouveau = (int)($k['nouveau'] ?? 0);
    $nbTraite  = (int)($k['traite'] ?? 0);
    $nbToday   = (int)($k['today'] ?? 0);
} catch (Throwable $e) {}

/* ============================================================
   PAGINATION
============================================================ */
$allowedPer = [50,100,500,1000];
$perPage = (int)($_GET['per'] ?? 50);
if (!in_array($perPage, $allowedPer, true)) { $perPage = 50; }
$totalPages = max(1, (int)ceil($total / $perPage));
$page = max(1, (int)($_GET['page'] ?? 1));
if ($page > $totalPages) { $page = $totalPages; }
$offset = ($page - 1) * $perPage;

$sql = "SELECT * FROM leads $whereSql ORDER BY created_at DESC LIMIT " . (int)$perPage . " OFFSET " . (int)$offset;
$st = $pdo->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);

$pageUrl = static function (int $p) use ($curFilters): string {
    return '?' . http_build_query(array_merge($curFilters, ['page' => $p]));
};
$from = $total ? ($offset + 1) : 0;
$to   = min($offset + $perPage, $total);

/* Badges TYPE : texte + couleurs (pas d'emoji => toujours lisible) */
$typeMeta = [
  'tdr'        => ['TDR',        '#6d28d9', '#ede9fe', '#ddd6fe'],
  'rappel'     => ['Rappel',     '#1e40af', '#e0ecff', '#bfdbfe'],
  'alerte'     => ['Alerte',     '#b45309', '#fff7ed', '#fed7aa'],
  'calendrier' => ['Calendrier', '#166534', '#dcfce7', '#bbf7d0'],
];

$pageTitle = 'Leads'; $activeMenu = 'leads';
ob_start();
?>
<style>
.lk-kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin:4px 0 16px}
.lk-kpi{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:14px 16px;box-shadow:0 6px 18px rgba(15,23,42,.05)}
.lk-kpi b{display:block;font-size:24px;font-weight:800;line-height:1.1;color:#0f172a}
.lk-kpi span{font-size:12.5px;color:#6b7280;font-weight:600}
.lk-kpi.k-wait b{color:#b45309}.lk-kpi.k-ok b{color:#16a34a}.lk-kpi.k-today b{color:#1e40af}

.lk-filters{background:#f8fafc;border:1px solid #e5e7eb;border-radius:14px;padding:14px;margin:0 0 14px}
.lk-filters .grid{display:grid;grid-template-columns:repeat(4,1fr);gap:10px}
.lk-filters .f{display:flex;flex-direction:column;gap:4px}
.lk-filters label{font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.3px}
.lk-filters input,.lk-filters select{padding:9px 11px;border:1px solid #e5e7eb;border-radius:10px;font-size:13.5px;background:#fff;outline:none;width:100%}
.lk-filters input:focus,.lk-filters select:focus{border-color:#1f3fe0;box-shadow:0 0 0 3px rgba(31,63,224,.12)}
.btn-apply{background:linear-gradient(135deg,#1f3fe0,#3b82f6);color:#fff;border:0;padding:10px 18px;border-radius:10px;font-weight:800;font-size:13.5px;cursor:pointer}
.btn-reset,.btn-csv2{background:#fff;border:1px solid #e5e7eb;color:#334155;padding:10px 16px;border-radius:10px;font-weight:700;font-size:13.5px;text-decoration:none;display:inline-flex;align-items:center;gap:6px}

.lk-bar{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin:0 0 10px;font-size:13px;color:#475569}
.lk-table{width:100%;border-collapse:collapse}
.lk-table th{font-size:12px;text-transform:uppercase;letter-spacing:.4px;color:#6b7280;text-align:left;padding:10px 12px;border-bottom:2px solid #eef2f7}
.lk-table td{padding:12px;border-bottom:1px solid #f1f5f9;vertical-align:top;font-size:14px}
.lk-table tr:hover td{background:#fafbff}
.tchip{display:inline-block;font-size:11.5px;font-weight:800;padding:5px 10px;border-radius:999px;background:rgba(31,63,224,.08);color:#1f3fe0;border:1px solid rgba(31,63,224,.2);white-space:nowrap}
.contact-lines a{color:#1f3fe0;text-decoration:none}.contact-lines a:hover{text-decoration:underline}
.contact-btns{display:flex;gap:6px;margin-top:8px;flex-wrap:wrap}
.cbtn{display:inline-flex;align-items:center;gap:6px;padding:6px 10px;border-radius:9px;font-size:12px;font-weight:700;text-decoration:none;border:1px solid transparent;white-space:nowrap}
.cbtn.wa{background:#dcfce7;color:#15803d;border-color:#bbf7d0}.cbtn.wa:hover{background:#bbf7d0}
.cbtn.mail{background:#e0ecff;color:#1e40af;border-color:#bfdbfe}.cbtn.mail:hover{background:#c7ddff}
.cbtn.dis{opacity:.4;pointer-events:none}
.pill{padding:4px 10px;border-radius:999px;font-size:11px;font-weight:800;white-space:nowrap}
.pill.ok{background:#dcfce7;color:#166534}.pill.wait{background:#fff7ed;color:#9a3412}
.lk-act{display:inline-flex;align-items:center;gap:6px;padding:9px 14px;border-radius:9px;font-size:12.5px;font-weight:800;border:0;cursor:pointer;line-height:1;font-family:inherit;color:#fff !important;opacity:1 !important;box-shadow:0 2px 6px rgba(15,23,42,.15)}
.lk-act.treat{background:#16a34a !important}.lk-act.treat:hover{background:#15803d !important}
.lk-act.reopen{background:#475569 !important}.lk-act.reopen:hover{background:#334155 !important}
.lk-act.del{background:#dc2626 !important}.lk-act.del:hover{background:#b91c1c !important}
.pager{display:flex;gap:6px;justify-content:center;align-items:center;flex-wrap:wrap;margin:18px 0 4px}
.pager a,.pager span{min-width:36px;height:36px;padding:0 10px;border-radius:9px;display:inline-flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;text-decoration:none;border:1px solid #e5e7eb;color:#334155;background:#fff}
.pager a:hover{border-color:#1f3fe0;color:#1f3fe0}.pager .cur{background:linear-gradient(135deg,#1f3fe0,#3b82f6);color:#fff;border-color:transparent}.pager .dis{opacity:.4;pointer-events:none}
@media(max-width:980px){.lk-kpis{grid-template-columns:1fr 1fr}.lk-filters .grid{grid-template-columns:1fr 1fr}}
</style>

<div class="card">

  <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">
    <h2 style="margin:0">📋 Leads (prospects)</h2>
    <a class="btn-csv2" href="?<?= e(http_build_query(array_merge($curFilters, ['export'=>'csv']))); ?>">⬇ Exporter CSV</a>
  </div>

  <?php if (isset($_GET['deleted'])): ?>
    <div style="margin:12px 0;padding:10px 14px;border-radius:10px;background:#DCFCE7;border:1px solid #16a34a;color:#166534">✅ Lead supprimé.</div>
  <?php endif; ?>

  <!-- KPIs -->
  <div class="lk-kpis">
    <div class="lk-kpi"><b><?= (int)$total; ?></b><span>Total (filtré)</span></div>
    <div class="lk-kpi k-wait"><b><?= (int)$nbNouveau; ?></b><span>Nouveaux à traiter</span></div>
    <div class="lk-kpi k-ok"><b><?= (int)$nbTraite; ?></b><span>Traités</span></div>
    <div class="lk-kpi k-today"><b><?= (int)$nbToday; ?></b><span>Aujourd'hui</span></div>
  </div>

  <!-- FILTRES -->
  <form method="get" class="lk-filters">
    <div class="grid">
      <div class="f">
        <label>Recherche</label>
        <input type="text" name="q" value="<?= e($q); ?>" placeholder="Nom, contact, formation…">
      </div>
      <div class="f">
        <label>Type</label>
        <select name="type">
          <option value="">Tous les types</option>
          <?php foreach (['calendrier'=>'Calendrier','rappel'=>'Rappel','alerte'=>'Alerte','tdr'=>'TDR'] as $kk=>$lbl): ?>
            <option value="<?= $kk ?>" <?= $type===$kk?'selected':''; ?>><?= $lbl ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="f">
        <label>Statut</label>
        <select name="statut">
          <option value="">Tous les statuts</option>
          <option value="nouveau" <?= $statut==='nouveau'?'selected':''; ?>>Nouveau</option>
          <option value="traite"  <?= $statut==='traite'?'selected':''; ?>>Traité</option>
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

  <div class="lk-bar">
    <div><b><?= (int)$from; ?></b>–<b><?= (int)$to; ?></b> sur <b><?= (int)$total; ?></b> lead(s) · page <?= (int)$page; ?>/<?= (int)$totalPages; ?></div>
  </div>

  <!-- TABLE -->
  <table class="lk-table">
    <thead>
      <tr><th>Date</th><th>Type</th><th>Nom</th><th>Contact &amp; actions rapides</th><th>Formation</th><th>Statut</th><th>Gestion</th></tr>
    </thead>
    <tbody>
    <?php if (!$rows): ?>
      <tr><td colspan="7" style="padding:22px;text-align:center;color:#64748b">Aucun lead ne correspond à ces filtres.</td></tr>
    <?php endif; ?>
    <?php foreach ($rows as $r):
        $nom    = trim((string)($r['nom'] ?? '')) ?: 'Prospect';
        $form   = trim((string)($r['formation_titre'] ?? ''));
        $cc     = lead_contacts((string)($r['contact'] ?? ''));
        $email  = $cc['email'];
        $tel    = $cc['phone'];
        $waNum  = pre_wa_number($tel);
        $isNouveau = (($r['statut'] ?? '') === 'nouveau');

        $waMsg = "Bonjour " . $nom . ", ici l'équipe IBIG EDUFORM. "
               . ($form !== '' ? "Nous revenons vers vous au sujet de votre demande concernant « " . $form . " ». " : "Nous revenons vers vous au sujet de votre demande. ")
               . "Comment pouvons-nous vous aider ?";
        $waHref = $waNum !== '' ? 'https://wa.me/' . $waNum . '?text=' . rawurlencode($waMsg) : '';

        $mailSubject = "IBIG EDUFORM — Votre demande" . ($form !== '' ? " : " . $form : "");
        $mailBody = "Bonjour " . $nom . ",\n\nNous revenons vers vous suite à votre demande" . ($form !== '' ? " concernant « " . $form . " »" : "") . " auprès d'IBIG EDUFORM.\n\nBien cordialement,\nL'équipe IBIG EDUFORM";
        $mailHref = $email !== '' ? 'mailto:' . $email . '?subject=' . rawurlencode($mailSubject) . '&body=' . rawurlencode($mailBody) : '';
        $telHref  = $tel !== '' ? 'tel:' . preg_replace('/[^\d+]/', '', $tel) : '';
    ?>
      <tr>
        <td><small><?= e(date('d/m/y H:i', strtotime((string)$r['created_at']))); ?></small></td>
        <?php
          $tt = strtolower(trim((string)($r['type'] ?? '')));
          $tm = $typeMeta[$tt] ?? [($tt !== '' ? strtoupper($tt) : 'Lead'), '#334155', '#f1f5f9', '#e2e8f0'];
        ?>
        <td><span class="tchip" style="color:<?= $tm[1]; ?>;background:<?= $tm[2]; ?>;border-color:<?= $tm[3]; ?>"><?= e($tm[0]); ?></span></td>
        <td><strong><?= e($nom); ?></strong></td>
        <td>
          <div class="contact-lines">
            <?php if ($tel !== ''): ?>📞 <a href="<?= e($telHref); ?>"><?= e($tel); ?></a><br><?php endif; ?>
            <?php if ($email !== ''): ?>✉️ <a href="mailto:<?= e($email); ?>"><?= e($email); ?></a><?php endif; ?>
            <?php if ($tel === '' && $email === ''): ?><small class="muted"><?= e($r['contact'] ?: '—'); ?></small><?php endif; ?>
          </div>
          <div class="contact-btns">
            <a class="cbtn wa <?= $waHref === '' ? 'dis' : ''; ?>" <?= $waHref !== '' ? 'href="'.e($waHref).'" target="_blank" rel="noopener"' : ''; ?>>💬 WhatsApp</a>
            <a class="cbtn mail <?= $mailHref === '' ? 'dis' : ''; ?>" <?= $mailHref !== '' ? 'href="'.e($mailHref).'"' : ''; ?>>✉️ Email</a>
          </div>
        </td>
        <td title="<?= e($form); ?>"><?= e($form !== '' ? $form : '—'); ?></td>
        <td><span class="pill <?= $isNouveau ? 'wait' : 'ok'; ?>"><?= $isNouveau ? 'Nouveau' : 'Traité'; ?></span></td>
        <td>
          <div style="display:flex;gap:6px;flex-wrap:wrap">
            <form method="post" style="display:inline">
              <?= csrf_field(); ?>
              <input type="hidden" name="action" value="toggle">
              <input type="hidden" name="id" value="<?= (int)$r['id']; ?>">
              <?php if ($isNouveau): ?>
                <button class="lk-act treat" title="Marquer comme traité">&#10003; Traiter</button>
              <?php else: ?>
                <button class="lk-act reopen" title="Repasser en nouveau">&#8634; Rouvrir</button>
              <?php endif; ?>
            </form>
            <?php if ($canDelete): ?>
              <form method="post" style="display:inline" onsubmit="return confirm('Supprimer ce lead ?');">
                <?= csrf_field(); ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int)$r['id']; ?>">
                <button class="lk-act del" title="Supprimer">&#10006; Supprimer</button>
              </form>
            <?php endif; ?>
          </div>
        </td>
      </tr>
    <?php endforeach; ?>
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
