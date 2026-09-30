<?php
declare(strict_types=1);
/* ============================================================
   ADMIN — TDR TÉLÉCHARGÉS — copies PDF liées aux pré-inscriptions
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

/* ============================================================
   FILTRES (GET)
============================================================ */
$q      = trim((string)($_GET['q'] ?? ''));
$mode   = trim((string)($_GET['mode'] ?? ''));
$dFrom  = trim((string)($_GET['from'] ?? ''));
$dTo    = trim((string)($_GET['to'] ?? ''));
$pidF   = (isset($_GET['preinscription_id']) && $_GET['preinscription_id'] !== '') ? (int)$_GET['preinscription_id'] : 0;

$where = []; $params = [];
if ($q !== '') {
    $where[]          = '(tc.prospect LIKE :q OR tc.formation_nom LIKE :q OR tc.formation_slug LIKE :q OR p.email LIKE :q OR p.telephone LIKE :q)';
    $params[':q']     = '%' . $q . '%';
}
if (in_array($mode, ['en_ligne','presentiel','hybride'], true)) {
    $where[]          = 'tc.mode_formation = :mode';
    $params[':mode']  = $mode;
}
if ($dFrom !== '') { $where[] = 'DATE(tc.downloaded_at) >= :dfrom'; $params[':dfrom'] = $dFrom; }
if ($dTo !== '')   { $where[] = 'DATE(tc.downloaded_at) <= :dto';   $params[':dto']   = $dTo; }
if ($pidF > 0)     { $where[] = 'tc.preinscription_id = :pid';      $params[':pid']   = $pidF; }

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$curFilters = array_filter([
    'q' => $q, 'mode' => $mode, 'from' => $dFrom, 'to' => $dTo,
    'preinscription_id' => $pidF ?: '',
    'per' => (string)($_GET['per'] ?? ''),
], static fn($v) => $v !== '');

/* ============================================================
   EXPORT CSV
============================================================ */
if (($_GET['export'] ?? '') === 'csv') {
    $st = $pdo->prepare("
        SELECT tc.downloaded_at, tc.prospect, p.email, p.telephone, p.ville,
               tc.formation_nom, tc.mode_formation, tc.format_formation,
               tc.prix, tc.pdf_path, tc.ip_address
        FROM tdr_copies tc
        LEFT JOIN preinscriptions p ON p.id = tc.preinscription_id
        $whereSql
        ORDER BY tc.downloaded_at DESC
    ");
    $st->execute($params);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=tdr-copies-' . date('Ymd-His') . '.csv');
    $out = fopen('php://output', 'w');
    fprintf($out, "\xEF\xBB\xBF");
    fputcsv($out, ['Date téléchargement','Prospect','Email','Téléphone','Ville','Formation','Mode','Format','Prix FCFA','PDF','IP'], ';');
    while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($out, array_values($r), ';');
    }
    fclose($out); exit;
}

/* ============================================================
   COMPTEURS
============================================================ */
$total = $nbToday = $nbAvecPreinscription = 0;
try {
    $agg = $pdo->prepare("
        SELECT
            COUNT(*) AS total,
            SUM(CASE WHEN DATE(tc.downloaded_at) = CURDATE() THEN 1 ELSE 0 END) AS today,
            SUM(CASE WHEN tc.preinscription_id IS NOT NULL THEN 1 ELSE 0 END) AS avec_preinscription
        FROM tdr_copies tc
        LEFT JOIN preinscriptions p ON p.id = tc.preinscription_id
        $whereSql
    ");
    $agg->execute($params);
    $k = $agg->fetch(PDO::FETCH_ASSOC) ?: [];
    $total                = (int)($k['total'] ?? 0);
    $nbToday              = (int)($k['today'] ?? 0);
    $nbAvecPreinscription = (int)($k['avec_preinscription'] ?? 0);
} catch (Throwable $e) {}

/* ============================================================
   PAGINATION
============================================================ */
$allowedPer = [25, 50, 100, 500];
$perPage = (int)($_GET['per'] ?? 50);
if (!in_array($perPage, $allowedPer, true)) { $perPage = 50; }
$totalPages = max(1, (int)ceil($total / $perPage));
$page = max(1, (int)($_GET['page'] ?? 1));
if ($page > $totalPages) { $page = $totalPages; }
$offset = ($page - 1) * $perPage;

$sql = "
    SELECT tc.*, p.nom AS p_nom, p.prenoms AS p_prenoms,
           p.email AS p_email, p.telephone AS p_tel,
           p.ville AS p_ville, p.statut_professionnel AS p_statut_pro
    FROM tdr_copies tc
    LEFT JOIN preinscriptions p ON p.id = tc.preinscription_id
    $whereSql
    ORDER BY tc.downloaded_at DESC
    LIMIT " . (int)$perPage . " OFFSET " . (int)$offset;

$st = $pdo->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);

$pageUrl = static function (int $p) use ($curFilters): string {
    return '?' . http_build_query(array_merge($curFilters, ['page' => $p]));
};
$from = $total ? ($offset + 1) : 0;
$to   = min($offset + $perPage, $total);

$modeMeta = [
    'en_ligne'   => ['En ligne',    '#1e40af', '#e0ecff', '#bfdbfe'],
    'presentiel' => ['Présentiel',  '#166534', '#dcfce7', '#bbf7d0'],
    'hybride'    => ['Hybride',     '#6d28d9', '#ede9fe', '#ddd6fe'],
];

$pageTitle = 'TDR Téléchargés'; $activeMenu = 'tdr_copies';
ob_start();
?>
<style>
.tc-kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin:4px 0 16px}
.tc-kpi{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:14px 16px;box-shadow:0 6px 18px rgba(15,23,42,.05)}
.tc-kpi b{display:block;font-size:24px;font-weight:800;line-height:1.1;color:#0f172a}
.tc-kpi span{font-size:12.5px;color:#6b7280;font-weight:600}
.tc-kpi.k-today b{color:#1e40af}.tc-kpi.k-linked b{color:#16a34a}

.tc-filters{background:#f8fafc;border:1px solid #e5e7eb;border-radius:14px;padding:14px;margin:0 0 14px}
.tc-filters .grid{display:grid;grid-template-columns:repeat(4,1fr);gap:10px}
.tc-filters .f{display:flex;flex-direction:column;gap:4px}
.tc-filters label{font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.3px}
.tc-filters input,.tc-filters select{padding:9px 11px;border:1px solid #e5e7eb;border-radius:10px;font-size:13.5px;background:#fff;outline:none;width:100%}
.tc-filters input:focus,.tc-filters select:focus{border-color:#1f3fe0;box-shadow:0 0 0 3px rgba(31,63,224,.12)}
.btn-apply{background:linear-gradient(135deg,#1f3fe0,#3b82f6);color:#fff;border:0;padding:10px 18px;border-radius:10px;font-weight:800;font-size:13.5px;cursor:pointer}
.btn-reset,.btn-csv{background:#fff;border:1px solid #e5e7eb;color:#334155;padding:10px 16px;border-radius:10px;font-weight:700;font-size:13.5px;text-decoration:none;display:inline-flex;align-items:center;gap:6px}

.tc-bar{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin:0 0 10px;font-size:13px;color:#475569}
.tc-table{width:100%;border-collapse:collapse}
.tc-table th{font-size:12px;text-transform:uppercase;letter-spacing:.4px;color:#6b7280;text-align:left;padding:10px 12px;border-bottom:2px solid #eef2f7}
.tc-table td{padding:12px;border-bottom:1px solid #f1f5f9;vertical-align:top;font-size:14px}
.tc-table tr:hover td{background:#fafbff}

.modechip{display:inline-block;font-size:11.5px;font-weight:800;padding:4px 10px;border-radius:999px;white-space:nowrap}
.prospect-name{font-weight:800;color:#0f172a;font-size:14px}
.prospect-sub{font-size:12px;color:#6b7280;margin-top:2px}
.formation-title{font-weight:700;color:#0f172a;font-size:13.5px;line-height:1.35}
.formation-meta{font-size:12px;color:#6b7280;margin-top:3px}
.contact-btns{display:flex;gap:6px;margin-top:8px;flex-wrap:wrap}
.cbtn{display:inline-flex;align-items:center;gap:6px;padding:6px 10px;border-radius:9px;font-size:12px;font-weight:700;text-decoration:none;border:1px solid transparent;white-space:nowrap}
.cbtn.wa{background:#dcfce7;color:#15803d;border-color:#bbf7d0}.cbtn.wa:hover{background:#bbf7d0}
.cbtn.mail{background:#e0ecff;color:#1e40af;border-color:#bfdbfe}.cbtn.mail:hover{background:#c7ddff}
.cbtn.pdf{background:#fff7ed;color:#b45309;border-color:#fed7aa}.cbtn.pdf:hover{background:#fed7aa}
.cbtn.pre{background:#f5f3ff;color:#6d28d9;border-color:#ddd6fe}.cbtn.pre:hover{background:#ddd6fe}
.cbtn.dis{opacity:.4;pointer-events:none}
.linked-badge{display:inline-flex;align-items:center;gap:4px;font-size:11px;font-weight:700;color:#15803d;background:#dcfce7;border:1px solid #bbf7d0;border-radius:999px;padding:2px 8px}
.unlinked-badge{display:inline-flex;align-items:center;gap:4px;font-size:11px;font-weight:700;color:#9ca3af;background:#f9fafb;border:1px solid #e5e7eb;border-radius:999px;padding:2px 8px}
.prix-val{font-size:13px;font-weight:800;color:#0f172a}
.prix-fcfa{font-size:11px;color:#6b7280}

/* Pagination */
.pg{display:flex;gap:6px;flex-wrap:wrap;align-items:center;justify-content:center;margin-top:24px}
.pg a,.pg span{padding:7px 13px;border-radius:9px;font-size:13px;font-weight:700;text-decoration:none;border:1px solid #e5e7eb;color:#334155;background:#fff}
.pg .cur{background:linear-gradient(135deg,#1f3fe0,#3b82f6);color:#fff;border-color:transparent}
.pg a:hover{background:#f0f4ff;border-color:#bfdbfe}

@media(max-width:900px){
  .tc-kpis{grid-template-columns:repeat(2,1fr)}
  .tc-filters .grid{grid-template-columns:1fr 1fr}
  .tc-table{font-size:12px}
}
@media(max-width:600px){
  .tc-filters .grid{grid-template-columns:1fr}
}
</style>

<?php if ($pidF > 0): ?>
<div style="background:#ede9fe;border:1px solid #ddd6fe;border-radius:12px;padding:10px 16px;margin-bottom:12px;display:flex;align-items:center;justify-content:space-between;gap:10px">
  <span style="font-size:13px;font-weight:700;color:#6d28d9">
    &#128196; Filtré sur la préinscription #<?= $pidF ?>
  </span>
  <a href="index.php" style="font-size:12px;color:#6d28d9;font-weight:700;text-decoration:none;background:#fff;border:1px solid #ddd6fe;border-radius:8px;padding:4px 10px">&#8635; Tout afficher</a>
</div>
<?php endif; ?>

<div class="admin-content-header">
  <div>
    <h1>📥 TDR Téléchargés</h1>
    <p class="sub">Chaque ligne = un prospect qui a téléchargé son TDR — avec la copie exacte du document reçu.</p>
  </div>
  <a href="?<?= http_build_query(array_merge($curFilters, ['export' => 'csv'])) ?>" class="btn-csv">
    ⬇ Export CSV
  </a>
</div>

<!-- KPIs -->
<div class="tc-kpis">
  <div class="tc-kpi">
    <b><?= number_format($total) ?></b>
    <span>Total téléchargements</span>
  </div>
  <div class="tc-kpi k-today">
    <b><?= number_format($nbToday) ?></b>
    <span>Aujourd'hui</span>
  </div>
  <div class="tc-kpi k-linked">
    <b><?= number_format($nbAvecPreinscription) ?></b>
    <span>Liés à une pré-inscription</span>
  </div>
  <div class="tc-kpi">
    <b><?= $total > 0 ? number_format(round($nbAvecPreinscription / $total * 100)) . ' %' : '—' ?></b>
    <span>Taux de liaison</span>
  </div>
</div>

<!-- FILTRES -->
<form method="GET" class="tc-filters">
  <div class="grid">
    <div class="f">
      <label>Recherche</label>
      <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Prospect, formation, email, tél…">
    </div>
    <div class="f">
      <label>Mode</label>
      <select name="mode">
        <option value="">Tous les modes</option>
        <option value="en_ligne"   <?= $mode === 'en_ligne'   ? 'selected' : '' ?>>En ligne</option>
        <option value="presentiel" <?= $mode === 'presentiel' ? 'selected' : '' ?>>Présentiel</option>
        <option value="hybride"    <?= $mode === 'hybride'    ? 'selected' : '' ?>>Hybride</option>
      </select>
    </div>
    <div class="f">
      <label>Du</label>
      <input type="date" name="from" value="<?= htmlspecialchars($dFrom) ?>">
    </div>
    <div class="f">
      <label>Au</label>
      <input type="date" name="to" value="<?= htmlspecialchars($dTo) ?>">
    </div>
  </div>
  <div style="display:flex;gap:8px;margin-top:10px;flex-wrap:wrap;align-items:center">
    <button type="submit" class="btn-apply">Filtrer</button>
    <a href="?" class="btn-reset">✕ Réinitialiser</a>
    <label style="font-size:12px;color:#64748b;font-weight:600;margin-left:8px">Afficher</label>
    <select name="per" style="padding:6px 10px;border:1px solid #e5e7eb;border-radius:8px;font-size:13px" onchange="this.form.submit()">
      <?php foreach ([25,50,100,500] as $pp): ?>
        <option value="<?= $pp ?>" <?= $perPage === $pp ? 'selected' : '' ?>><?= $pp ?> / page</option>
      <?php endforeach; ?>
    </select>
  </div>
</form>

<!-- BARRE RÉSULTATS -->
<div class="tc-bar">
  <span>
    <?php if ($total > 0): ?>
      <?= $from ?>–<?= $to ?> sur <strong><?= number_format($total) ?></strong> téléchargement<?= $total > 1 ? 's' : '' ?>
    <?php else: ?>
      Aucun résultat
    <?php endif; ?>
  </span>
  <?php if ($totalPages > 1): ?>
    <span>Page <?= $page ?> / <?= $totalPages ?></span>
  <?php endif; ?>
</div>

<!-- TABLEAU -->
<?php if (empty($rows)): ?>
  <div style="text-align:center;padding:60px 20px;color:#9ca3af">
    <div style="font-size:2.5rem;margin-bottom:12px">📭</div>
    <p style="font-size:1rem;font-weight:600">Aucun téléchargement enregistré pour le moment.</p>
    <p style="font-size:.85rem">Les prochains téléchargements de TDR apparaîtront ici automatiquement.</p>
  </div>
<?php else: ?>
<div style="overflow-x:auto">
<table class="tc-table">
  <thead>
    <tr>
      <th>Date</th>
      <th>Prospect</th>
      <th>Formation</th>
      <th>Mode / Prix</th>
      <th>Contact</th>
      <th>PDF copie</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($rows as $r):
        $waNum   = pre_wa_number($r['p_tel'] ?? '');
        $email   = htmlspecialchars($r['p_email'] ?? '', ENT_QUOTES);
        $pdfPath = $r['pdf_path'] ?? '';
        $pdfOk   = $pdfPath !== '' && file_exists(__DIR__ . '/../../' . $pdfPath);
        $pdfUrl  = $pdfOk ? '/admin/tdr-copies/download.php?id=' . (int)$r['id'] : '';
        $preId   = (int)($r['preinscription_id'] ?? 0);
        $mode    = $r['mode_formation'] ?? 'en_ligne';
        $mc      = $modeMeta[$mode] ?? ['?', '#6b7280', '#f9fafb', '#e5e7eb'];
        $nomProspect = trim(($r['p_prenoms'] ?? '') . ' ' . ($r['p_nom'] ?? ''));
        if ($nomProspect === '' || $nomProspect === ' ') { $nomProspect = htmlspecialchars($r['prospect'] ?? '—'); }
        $dt = $r['downloaded_at'] ? date('d/m/Y H:i', strtotime($r['downloaded_at'])) : '—';
    ?>
    <tr>
      <!-- DATE -->
      <td style="white-space:nowrap;color:#64748b;font-size:13px"><?= $dt ?></td>

      <!-- PROSPECT -->
      <td>
        <div class="prospect-name"><?= htmlspecialchars($nomProspect) ?></div>
        <?php if ($r['p_ville'] ?? ''): ?>
          <div class="prospect-sub">📍 <?= htmlspecialchars($r['p_ville']) ?></div>
        <?php endif; ?>
        <?php if ($preId > 0): ?>
          <div style="margin-top:5px">
            <span class="linked-badge">✔ Fiche pré-inscription #<?= $preId ?></span>
          </div>
        <?php else: ?>
          <div style="margin-top:5px">
            <span class="unlinked-badge">Sans fiche liée</span>
          </div>
        <?php endif; ?>
      </td>

      <!-- FORMATION -->
      <td>
        <div class="formation-title"><?= htmlspecialchars($r['formation_nom'] ?? '—') ?></div>
        <div class="formation-meta">Format : <?= htmlspecialchars($r['format_formation'] ?? '—') ?></div>
      </td>

      <!-- MODE / PRIX -->
      <td style="white-space:nowrap">
        <span class="modechip" style="background:<?= $mc[2] ?>;color:<?= $mc[1] ?>;border:1px solid <?= $mc[3] ?>"><?= $mc[0] ?></span>
        <div style="margin-top:6px">
          <span class="prix-val"><?= number_format((int)$r['prix']) ?></span>
          <span class="prix-fcfa"> FCFA</span>
        </div>
      </td>

      <!-- CONTACT -->
      <td>
        <?php if ($r['p_tel'] ?? ''): ?>
          <div style="font-size:13px;color:#334155">📞 <?= htmlspecialchars($r['p_tel']) ?></div>
        <?php endif; ?>
        <?php if ($r['p_email'] ?? ''): ?>
          <div style="font-size:12px;color:#6b7280">✉ <?= $email ?></div>
        <?php endif; ?>
        <div class="contact-btns">
          <?php if ($waNum !== ''): ?>
            <a href="https://wa.me/<?= $waNum ?>?text=<?= rawurlencode('Bonjour, suite à votre téléchargement du TDR "' . ($r['formation_nom'] ?? '') . '" sur IBIG EDUFORM, nous souhaitons vous accompagner dans votre inscription.') ?>"
               target="_blank" class="cbtn wa">💬 WhatsApp</a>
          <?php else: ?>
            <span class="cbtn wa dis">💬 WhatsApp</span>
          <?php endif; ?>
          <?php if ($email !== ''): ?>
            <a href="mailto:<?= $email ?>?subject=<?= rawurlencode('Votre TDR IBIG EDUFORM — ' . ($r['formation_nom'] ?? '')) ?>" class="cbtn mail">✉ Email</a>
          <?php else: ?>
            <span class="cbtn mail dis">✉ Email</span>
          <?php endif; ?>
          <?php if ($preId > 0): ?>
            <a href="/admin/preinscriptions/view.php?id=<?= $preId ?>" target="_blank" class="cbtn pre">👤 Fiche</a>
          <?php endif; ?>
        </div>
      </td>

      <!-- PDF COPIE -->
      <td>
        <?php if ($pdfOk): ?>
          <a href="<?= htmlspecialchars($pdfUrl) ?>" target="_blank" class="cbtn pdf">⬇ Même PDF</a>
          <div style="font-size:11px;color:#9ca3af;margin-top:4px">Copie serveur</div>
        <?php else: ?>
          <span style="font-size:12px;color:#9ca3af">—</span>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</div>

<!-- PAGINATION -->
<?php if ($totalPages > 1): ?>
<nav class="pg">
  <?php if ($page > 1): ?>
    <a href="<?= $pageUrl(1) ?>">«</a>
    <a href="<?= $pageUrl($page - 1) ?>">‹</a>
  <?php endif; ?>
  <?php
  $start = max(1, $page - 2);
  $end   = min($totalPages, $page + 2);
  for ($i = $start; $i <= $end; $i++):
  ?>
    <?php if ($i === $page): ?>
      <span class="cur"><?= $i ?></span>
    <?php else: ?>
      <a href="<?= $pageUrl($i) ?>"><?= $i ?></a>
    <?php endif; ?>
  <?php endfor; ?>
  <?php if ($page < $totalPages): ?>
    <a href="<?= $pageUrl($page + 1) ?>">›</a>
    <a href="<?= $pageUrl($totalPages) ?>">»</a>
  <?php endif; ?>
</nav>
<?php endif; ?>
<?php endif; ?>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layout/layout.php';
