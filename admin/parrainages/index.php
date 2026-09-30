<?php
declare(strict_types=1);
/* ============================================================
   ADMIN — PARRAINAGES (-10% parrain & filleul)
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

/* ============================================================
   HELPERS CONTACT
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
    function lead_contacts(string $c): array {
        $email = '';
        if (preg_match('/[\w.\-+]+@[\w.\-]+\.[A-Za-z]{2,}/', $c, $m)) { $email = $m[0]; }
        $phone = '';
        if (preg_match('/whatsapp\s*:?\s*([+\d][\d\s().\-]{6,})/i', $c, $m)) { $phone = $m[1]; }
        else { $cNoEmail = $email !== '' ? str_replace($email, ' ', $c) : $c;
               if (preg_match('/[+]?\d[\d\s().\-]{6,}\d/', $cNoEmail, $m)) { $phone = $m[0]; } }
        return ['email' => trim($email), 'phone' => trim($phone)];
    }
}
/* Construit les liens de contact (WA + mail) pré-remplis */
function par_links(string $contact, string $name, string $ctx): array {
    $cc = lead_contacts($contact);
    $waNum = pre_wa_number($cc['phone']);
    $msg = "Bonjour " . ($name !== '' ? $name : '') . ", ici l'équipe IBIG EDUFORM. " . $ctx;
    $wa  = $waNum !== '' ? 'https://wa.me/' . $waNum . '?text=' . rawurlencode($msg) : '';
    $sub = "IBIG EDUFORM — Parrainage";
    $body= "Bonjour " . ($name !== '' ? $name : '') . ",\n\n" . $ctx . "\n\nBien cordialement,\nL'équipe IBIG EDUFORM";
    $mail= $cc['email'] !== '' ? 'mailto:' . $cc['email'] . '?subject=' . rawurlencode($sub) . '&body=' . rawurlencode($body) : '';
    return ['email' => $cc['email'], 'phone' => $cc['phone'], 'wa' => $wa, 'mail' => $mail];
}

/* ============================================================
   FILTRES
============================================================ */
$q      = trim((string)($_GET['q'] ?? ''));
$statut = (string)($_GET['statut'] ?? '');
$dFrom  = trim((string)($_GET['from'] ?? ''));
$dTo    = trim((string)($_GET['to'] ?? ''));
$where = []; $params = [];
if ($q !== '') { $where[] = '(pu.code LIKE :q OR p.parrain_nom LIKE :q OR pu.filleul_nom LIKE :q OR pu.formation_titre LIKE :q)'; $params[':q'] = '%'.$q.'%'; }
if (in_array($statut, ['en_attente','paye','annule'], true)) { $where[] = 'pu.statut = :st'; $params[':st'] = $statut; }
if ($dFrom !== '') { $where[] = 'DATE(pu.created_at) >= :dfrom'; $params[':dfrom'] = $dFrom; }
if ($dTo !== '')   { $where[] = 'DATE(pu.created_at) <= :dto';   $params[':dto']   = $dTo; }
$whereSql = $where ? 'WHERE '.implode(' AND ', $where) : '';

$curFilters = array_filter([
  'q'=>$q, 'statut'=>$statut, 'from'=>$dFrom, 'to'=>$dTo, 'per'=>(string)($_GET['per'] ?? ''),
], static fn($v) => $v !== '');

/* ============================================================
   ACTION : changer le statut récompense
============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $id  = (int)($_POST['id'] ?? 0);
    $new = (string)($_POST['statut'] ?? '');
    if ($id > 0 && in_array($new, ['en_attente','paye','annule'], true)) {
        $pdo->prepare("UPDATE parrainage_usages SET statut=? WHERE id=?")->execute([$new, $id]);
    }
    redirect('index.php' . ($curFilters ? ('?' . http_build_query($curFilters)) : ''));
}

/* ============================================================
   EXPORT CSV (respecte les filtres)
============================================================ */
if (($_GET['export'] ?? '') === 'csv') {
    $st = $pdo->prepare("SELECT pu.created_at,pu.code,p.parrain_nom,p.parrain_contact,pu.filleul_nom,pu.filleul_contact,pu.formation_titre,pu.montant_base,pu.remise,pu.montant_net,pu.statut
                         FROM parrainage_usages pu LEFT JOIN parrainages p ON p.code=pu.code $whereSql ORDER BY pu.created_at DESC");
    $st->execute($params);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=parrainages-' . date('Ymd-His') . '.csv');
    $out = fopen('php://output','w'); fprintf($out, "\xEF\xBB\xBF");
    fputcsv($out, ['Date','Code','Parrain','Contact parrain','Filleul','Contact filleul','Formation','Montant base','Remise','Montant net','Statut'], ';');
    while ($r = $st->fetch(PDO::FETCH_ASSOC)) { fputcsv($out, array_values($r), ';'); }
    fclose($out); exit;
}

/* ============================================================
   COMPTEURS (sur le périmètre filtré)
============================================================ */
$total=0; $nbAttente=0; $nbPaye=0; $sumRemise=0;
try {
    $agg = $pdo->prepare("
      SELECT COUNT(*) total,
             SUM(CASE WHEN pu.statut='en_attente' THEN 1 ELSE 0 END) attente,
             SUM(CASE WHEN pu.statut='paye' THEN 1 ELSE 0 END) paye,
             COALESCE(SUM(pu.remise),0) remise
      FROM parrainage_usages pu LEFT JOIN parrainages p ON p.code=pu.code $whereSql");
    $agg->execute($params);
    $k = $agg->fetch(PDO::FETCH_ASSOC) ?: [];
    $total     = (int)($k['total'] ?? 0);
    $nbAttente = (int)($k['attente'] ?? 0);
    $nbPaye    = (int)($k['paye'] ?? 0);
    $sumRemise = (int)($k['remise'] ?? 0);
} catch (Throwable $e) {}

/* ============================================================
   PAGINATION
============================================================ */
$allowedPer = [50,100,500,1000];
$perPage = (int)($_GET['per'] ?? 50);
if (!in_array($perPage, $allowedPer, true)) { $perPage = 50; }
$totalPages = max(1,(int)ceil($total/$perPage));
$page = max(1,(int)($_GET['page'] ?? 1));
if ($page > $totalPages) { $page = $totalPages; }
$offset = ($page-1)*$perPage;

$st = $pdo->prepare("SELECT pu.*, p.parrain_nom, p.parrain_contact
                     FROM parrainage_usages pu LEFT JOIN parrainages p ON p.code=pu.code
                     $whereSql ORDER BY pu.created_at DESC LIMIT " . (int)$perPage . " OFFSET " . (int)$offset);
$st->execute($params);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);

$pageUrl = static function (int $p) use ($curFilters): string {
    return '?' . http_build_query(array_merge($curFilters, ['page' => $p]));
};
$from = $total ? ($offset + 1) : 0;
$to   = min($offset + $perPage, $total);
$fcfa = fn($n) => number_format((int)$n,0,',',' ').' F';

$pageTitle='Parrainages'; $activeMenu='parrainages';
ob_start();
?>
<style>
.pp-kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin:4px 0 16px}
.pp-kpi{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:14px 16px;box-shadow:0 6px 18px rgba(15,23,42,.05)}
.pp-kpi b{display:block;font-size:23px;font-weight:800;line-height:1.1;color:#0f172a}
.pp-kpi span{font-size:12.5px;color:#6b7280;font-weight:600}
.pp-kpi.k-wait b{color:#b45309}.pp-kpi.k-ok b{color:#16a34a}.pp-kpi.k-blue b{color:#1f3fe0}

.pp-filters{background:#f8fafc;border:1px solid #e5e7eb;border-radius:14px;padding:14px;margin:0 0 14px}
.pp-filters .grid{display:grid;grid-template-columns:repeat(4,1fr);gap:10px}
.pp-filters .f{display:flex;flex-direction:column;gap:4px}
.pp-filters label{font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.3px}
.pp-filters input,.pp-filters select{padding:9px 11px;border:1px solid #e5e7eb;border-radius:10px;font-size:13.5px;background:#fff;outline:none;width:100%}
.pp-filters input:focus,.pp-filters select:focus{border-color:#1f3fe0;box-shadow:0 0 0 3px rgba(31,63,224,.12)}
.btn-apply{background:linear-gradient(135deg,#1f3fe0,#3b82f6);color:#fff;border:0;padding:10px 18px;border-radius:10px;font-weight:800;font-size:13.5px;cursor:pointer}
.btn-reset,.btn-csv2{background:#fff;border:1px solid #e5e7eb;color:#334155;padding:10px 16px;border-radius:10px;font-weight:700;font-size:13.5px;text-decoration:none;display:inline-flex;align-items:center;gap:6px}

.pp-bar{margin:0 0 10px;font-size:13px;color:#475569}
.pp-table{width:100%;border-collapse:collapse}
.pp-table th{font-size:12px;text-transform:uppercase;letter-spacing:.4px;color:#6b7280;text-align:left;padding:10px 12px;border-bottom:2px solid #eef2f7}
.pp-table td{padding:12px;border-bottom:1px solid #f1f5f9;vertical-align:top;font-size:13.5px}
.pp-table tr:hover td{background:#fafbff}
.codepill{font-family:ui-monospace,Menlo,monospace;background:#ede9fe;color:#6d28d9;border-radius:6px;padding:3px 8px;font-size:12px;font-weight:800;white-space:nowrap}
.who b{font-size:13.5px;color:#0f172a}.who small{display:block;color:#64748b;font-size:12px;margin-top:1px}
.contact-btns{display:flex;gap:5px;margin-top:6px;flex-wrap:wrap}
.cbtn{display:inline-flex;align-items:center;gap:5px;padding:5px 9px;border-radius:8px;font-size:11px;font-weight:800;text-decoration:none;border:1px solid transparent;white-space:nowrap}
.cbtn.wa{background:#16a34a !important;color:#fff !important}.cbtn.mail{background:#1f3fe0 !important;color:#fff !important}
.cbtn.dis{opacity:.35 !important;pointer-events:none}
.pill{padding:4px 10px;border-radius:999px;font-size:11px;font-weight:800;white-space:nowrap;display:inline-block}
.pill.wait{background:#fff7ed;color:#9a3412}.pill.ok{background:#dcfce7;color:#166534}.pill.annule{background:#fee2e2;color:#991b1b}
.stsel{padding:7px 9px;border:1px solid #cbd5e1;border-radius:8px;font-size:12px;font-weight:700;margin-top:6px;background:#fff}
.remise{font-weight:800;color:#15803d}
.pager{display:flex;gap:6px;justify-content:center;align-items:center;flex-wrap:wrap;margin:18px 0 4px}
.pager a,.pager span{min-width:36px;height:36px;padding:0 10px;border-radius:9px;display:inline-flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;text-decoration:none;border:1px solid #e5e7eb;color:#334155;background:#fff}
.pager a:hover{border-color:#1f3fe0;color:#1f3fe0}.pager .cur{background:linear-gradient(135deg,#1f3fe0,#3b82f6);color:#fff;border-color:transparent}.pager .dis{opacity:.4;pointer-events:none}
@media(max-width:980px){.pp-kpis{grid-template-columns:1fr 1fr}.pp-filters .grid{grid-template-columns:1fr 1fr}}
</style>

<div class="card">

  <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">
    <h2 style="margin:0">🤝 Parrainages</h2>
    <a class="btn-csv2" href="?<?= e(http_build_query(array_merge($curFilters, ['export'=>'csv']))); ?>">⬇ Exporter CSV</a>
  </div>

  <!-- KPIs -->
  <div class="pp-kpis">
    <div class="pp-kpi"><b><?= (int)$total; ?></b><span>Parrainages (filtré)</span></div>
    <div class="pp-kpi k-wait"><b><?= (int)$nbAttente; ?></b><span>Récompenses en attente</span></div>
    <div class="pp-kpi k-ok"><b><?= (int)$nbPaye; ?></b><span>Récompenses honorées</span></div>
    <div class="pp-kpi k-blue"><b><?= e($fcfa($sumRemise)); ?></b><span>Remises accordées</span></div>
  </div>

  <!-- FILTRES -->
  <form method="get" class="pp-filters">
    <div class="grid">
      <div class="f">
        <label>Recherche</label>
        <input type="text" name="q" value="<?= e($q); ?>" placeholder="Code, parrain, filleul, formation…">
      </div>
      <div class="f">
        <label>Statut récompense</label>
        <select name="statut">
          <option value="">Tous les statuts</option>
          <option value="en_attente" <?= $statut==='en_attente'?'selected':''; ?>>En attente</option>
          <option value="paye"       <?= $statut==='paye'?'selected':''; ?>>Honoré</option>
          <option value="annule"     <?= $statut==='annule'?'selected':''; ?>>Annulé</option>
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

  <div class="pp-bar"><b><?= (int)$from; ?></b>–<b><?= (int)$to; ?></b> sur <b><?= (int)$total; ?></b> parrainage(s) · page <?= (int)$page; ?>/<?= (int)$totalPages; ?></div>

  <!-- TABLE -->
  <table class="pp-table">
    <thead>
      <tr><th>Date</th><th>Code</th><th>Parrain</th><th>Filleul</th><th>Formation</th><th>Remise</th><th>Récompense</th></tr>
    </thead>
    <tbody>
    <?php if (!$rows): ?>
      <tr><td colspan="7" style="padding:22px;text-align:center;color:#64748b">Aucun parrainage ne correspond à ces filtres.</td></tr>
    <?php endif; ?>
    <?php foreach ($rows as $r):
        $code   = (string)($r['code'] ?? '');
        $form   = trim((string)($r['formation_titre'] ?? ''));
        $st0    = (string)($r['statut'] ?? 'en_attente');
        $pillCls = $st0==='paye' ? 'ok' : ($st0==='annule' ? 'annule' : 'wait');
        $pName  = trim((string)($r['parrain_nom'] ?? '')) ?: '—';
        $fName  = trim((string)($r['filleul_nom'] ?? '')) ?: '—';
        $pL = par_links((string)($r['parrain_contact'] ?? ''), $pName !== '—' ? $pName : '',
                "Bonne nouvelle : votre filleul" . ($fName!=='—' ? " " . $fName : "") . " s'est inscrit grâce à votre code de parrainage" . ($code!=='' ? " « ".$code." »" : "") . ". Votre remise de parrainage est en cours de traitement.");
        $fL = par_links((string)($r['filleul_contact'] ?? ''), $fName !== '—' ? $fName : '',
                "Merci d'avoir rejoint IBIG EDUFORM via un parrainage" . ($form!=='' ? " pour « ".$form." »" : "") . ". Nous restons à votre disposition pour finaliser votre inscription.");
    ?>
      <tr>
        <td><small><?= e(date('d/m/y H:i', strtotime((string)$r['created_at']))); ?></small></td>
        <td><span class="codepill"><?= e($code ?: '—'); ?></span></td>

        <!-- PARRAIN -->
        <td>
          <span class="who"><b><?= e($pName); ?></b><small><?= e($pL['phone'] ?: $pL['email'] ?: '—'); ?></small></span>
          <div class="contact-btns">
            <a class="cbtn wa <?= $pL['wa']===''?'dis':''; ?>" <?= $pL['wa']!=='' ? 'href="'.e($pL['wa']).'" target="_blank" rel="noopener"' : ''; ?>>💬 WA</a>
            <a class="cbtn mail <?= $pL['mail']===''?'dis':''; ?>" <?= $pL['mail']!=='' ? 'href="'.e($pL['mail']).'"' : ''; ?>>✉️ Mail</a>
          </div>
        </td>

        <!-- FILLEUL -->
        <td>
          <span class="who"><b><?= e($fName); ?></b><small><?= e($fL['phone'] ?: $fL['email'] ?: '—'); ?></small></span>
          <div class="contact-btns">
            <a class="cbtn wa <?= $fL['wa']===''?'dis':''; ?>" <?= $fL['wa']!=='' ? 'href="'.e($fL['wa']).'" target="_blank" rel="noopener"' : ''; ?>>💬 WA</a>
            <a class="cbtn mail <?= $fL['mail']===''?'dis':''; ?>" <?= $fL['mail']!=='' ? 'href="'.e($fL['mail']).'"' : ''; ?>>✉️ Mail</a>
          </div>
        </td>

        <td title="<?= e($form); ?>"><?= e($form !== '' ? $form : '—'); ?></td>
        <td><span class="remise"><?= e($fcfa($r['remise'] ?? 0)); ?></span></td>
        <td>
          <form method="post" style="display:flex;flex-direction:column;gap:0;align-items:flex-start">
            <?= csrf_field(); ?>
            <input type="hidden" name="id" value="<?= (int)$r['id']; ?>">
            <span class="pill <?= $pillCls; ?>"><?= $st0==='paye'?'Honoré':($st0==='annule'?'Annulé':'En attente'); ?></span>
            <select name="statut" class="stsel" onchange="this.form.submit()">
              <option value="en_attente" <?= $st0==='en_attente'?'selected':''; ?>>En attente</option>
              <option value="paye"       <?= $st0==='paye'?'selected':''; ?>>Honoré</option>
              <option value="annule"     <?= $st0==='annule'?'selected':''; ?>>Annulé</option>
            </select>
          </form>
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
