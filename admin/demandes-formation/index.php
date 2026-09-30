<?php
declare(strict_types=1);

/* =====================================================
   BOOTSTRAP
===================================================== */
require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../auth/middleware.php';

Middleware::requireAuth();

$pageTitle  = 'Demandes de formation';
$activeMenu = 'demandes_formation';

$pdo = Database::connect();

/* =====================================================
   HELPERS CONTACT
===================================================== */
if (!function_exists('pre_wa_number')) {
    function pre_wa_number(?string $phone): string {
        $d = preg_replace('/\D+/', '', (string)$phone);
        if ($d === '') return '';
        if (strpos($d, '225') === 0) return $d;
        if (strlen($d) <= 10) return '225' . $d;
        return $d;
    }
}

/* =====================================================
   FILTRES
===================================================== */
$q      = trim((string)($_GET['q'] ?? ''));
$statut = (string)($_GET['statut'] ?? '');
$typeD  = (string)($_GET['type'] ?? '');
$mode   = (string)($_GET['mode'] ?? '');
$dFrom  = trim((string)($_GET['from'] ?? ''));
$dTo    = trim((string)($_GET['to'] ?? ''));

$statutsEnum = ['nouvelle','traitee','devis_envoye','cloturee'];

$where  = []; $params = [];
if ($q !== '') {
  $where[] = "(nom LIKE :q OR prenoms LIKE :q OR telephone LIKE :q OR email LIKE :q OR domaine_formation LIKE :q OR theme_formation LIKE :q OR structure_nom LIKE :q)";
  $params[':q'] = "%$q%";
}
if (in_array($statut, $statutsEnum, true)) { $where[] = "statut = :statut"; $params[':statut'] = $statut; }
if ($typeD !== '') { $where[] = "type_demandeur = :td"; $params[':td'] = $typeD; }
if ($mode  !== '') { $where[] = "mode_formation = :mode"; $params[':mode'] = $mode; }
if ($dFrom !== '') { $where[] = "DATE(created_at) >= :dfrom"; $params[':dfrom'] = $dFrom; }
if ($dTo   !== '') { $where[] = "DATE(created_at) <= :dto";   $params[':dto']   = $dTo; }
$whereSql = $where ? 'WHERE '.implode(' AND ', $where) : '';

$curFilters = array_filter([
  'q'=>$q,'statut'=>$statut,'type'=>$typeD,'mode'=>$mode,'from'=>$dFrom,'to'=>$dTo,'per'=>(string)($_GET['per'] ?? ''),
], static fn($v) => $v !== '');

/* listes pour filtres (tolérant) */
$typesList = []; $modesList = [];
try { $typesList = $pdo->query("SELECT DISTINCT type_demandeur FROM demandes_formation WHERE type_demandeur IS NOT NULL AND type_demandeur<>'' ORDER BY type_demandeur")->fetchAll(PDO::FETCH_COLUMN); } catch(Throwable $e){}
try { $modesList = $pdo->query("SELECT DISTINCT mode_formation FROM demandes_formation WHERE mode_formation IS NOT NULL AND mode_formation<>'' ORDER BY mode_formation")->fetchAll(PDO::FETCH_COLUMN); } catch(Throwable $e){}

/* =====================================================
   COMPTEURS (périmètre filtré)
===================================================== */
$total=0; $nbNouv=0; $nbDevis=0; $nbToday=0;
try {
  $agg = $pdo->prepare("
    SELECT COUNT(*) total,
           SUM(CASE WHEN statut='nouvelle' THEN 1 ELSE 0 END) nouv,
           SUM(CASE WHEN statut='devis_envoye' THEN 1 ELSE 0 END) devis,
           SUM(CASE WHEN DATE(created_at)=CURDATE() THEN 1 ELSE 0 END) today
    FROM demandes_formation $whereSql");
  $agg->execute($params);
  $k = $agg->fetch(PDO::FETCH_ASSOC) ?: [];
  $total   = (int)($k['total'] ?? 0);
  $nbNouv  = (int)($k['nouv'] ?? 0);
  $nbDevis = (int)($k['devis'] ?? 0);
  $nbToday = (int)($k['today'] ?? 0);
} catch (Throwable $e) {}

/* =====================================================
   PAGINATION
===================================================== */
$allowedPer = [50,100,500,1000];
$perPage = (int)($_GET['per'] ?? 50);
if (!in_array($perPage, $allowedPer, true)) { $perPage = 50; }
$totalPages = max(1,(int)ceil($total/$perPage));
$page = max(1,(int)($_GET['page'] ?? 1));
if ($page > $totalPages) { $page = $totalPages; }
$offset = ($page-1)*$perPage;

$stmt = $pdo->prepare("
  SELECT id,type_demandeur,nom,prenoms,structure_nom,telephone,email,
         domaine_formation,theme_formation,mode_formation,statut,created_at
  FROM demandes_formation
  $whereSql
  ORDER BY id DESC
  LIMIT " . (int)$perPage . " OFFSET " . (int)$offset);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageUrl = static function (int $p) use ($curFilters): string {
    return '?' . http_build_query(array_merge($curFilters, ['page' => $p]));
};
$from = $total ? ($offset + 1) : 0;
$to   = min($offset + $perPage, $total);

$statutMeta = [
  'nouvelle'     => ['Nouvelle',     'wait'],
  'traitee'      => ['Traitée',      'ok'],
  'devis_envoye' => ['Devis envoyé', 'devis'],
  'cloturee'     => ['Clôturée',     'done'],
];

ob_start();
?>
<style>
/* ── KPIs ── */
.df-kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin:16px 0}
.df-kpi{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:16px 18px;box-shadow:0 4px 14px rgba(15,23,42,.06);display:flex;flex-direction:column;gap:4px}
.df-kpi b{font-size:28px;font-weight:900;line-height:1;color:#0f172a}
.df-kpi span{font-size:12px;color:#6b7280;font-weight:600;text-transform:uppercase;letter-spacing:.3px}
.df-kpi.k-total{border-top:3px solid #6366f1}.df-kpi.k-total b{color:#4f46e5}
.df-kpi.k-wait {border-top:3px solid #f59e0b}.df-kpi.k-wait  b{color:#b45309}
.df-kpi.k-devis{border-top:3px solid #f97316}.df-kpi.k-devis b{color:#c2410c}
.df-kpi.k-today{border-top:3px solid #3b82f6}.df-kpi.k-today b{color:#1d4ed8}

/* ── Filtres ── */
.df-filters{background:#f8fafc;border:1px solid #e5e7eb;border-radius:14px;padding:16px;margin:0 0 14px}
.df-filters .grid{display:grid;grid-template-columns:repeat(4,1fr);gap:10px}
.df-filters .f{display:flex;flex-direction:column;gap:4px}
.df-filters label{font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.3px}
.df-filters input,.df-filters select{padding:9px 11px;border:1px solid #e2e8f0;border-radius:10px;font-size:13.5px;background:#fff;outline:none;width:100%;transition:border-color .15s}
.df-filters input:focus,.df-filters select:focus{border-color:#1f3fe0;box-shadow:0 0 0 3px rgba(31,63,224,.1)}
.btn-apply{background:linear-gradient(135deg,#1f3fe0,#3b82f6);color:#fff;border:0;padding:10px 20px;border-radius:10px;font-weight:800;font-size:13.5px;cursor:pointer}
.btn-reset,.btn-csv2{background:#fff;border:1px solid #e5e7eb;color:#334155;padding:10px 16px;border-radius:10px;font-weight:700;font-size:13.5px;text-decoration:none;display:inline-flex;align-items:center;gap:6px}

/* ── Table ── */
.df-bar{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin:0 0 10px;font-size:13px;color:#475569}
.df-table{width:100%;border-collapse:collapse;font-size:13.5px}
.df-table th{font-size:11.5px;text-transform:uppercase;letter-spacing:.4px;color:#6b7280;text-align:left;padding:10px 12px;border-bottom:2px solid #eef2f7;background:#fafbff;white-space:nowrap}
.df-table td{padding:11px 12px;border-bottom:1px solid #f1f5f9;vertical-align:middle}
.df-table tr.row-today td{background:#fffbeb}
.df-table tr:hover td{background:#f0f6ff}

/* ── Demandeur ── */
.who b{font-size:14px;color:#0f172a;display:block}
.who .meta{font-size:11.5px;color:#6b7280;margin-top:3px;line-height:1.4}
.tag{display:inline-block;font-size:10.5px;font-weight:800;padding:2px 8px;border-radius:999px;background:#eef2ff;color:#3730a3;text-transform:capitalize}
.badge-new{display:inline-block;background:#fde68a;color:#92400e;font-size:10px;font-weight:800;border-radius:6px;padding:1px 6px;margin-left:6px;vertical-align:middle}

/* ── Contacts ── */
.contact-lines{font-size:12.5px;line-height:1.8}
.contact-lines a{color:#1f3fe0;text-decoration:none;font-weight:600}
.contact-lines a:hover{text-decoration:underline}
.contact-btns{display:flex;gap:5px;margin-top:7px;flex-wrap:wrap}
.cbtn{display:inline-flex;align-items:center;gap:5px;padding:5px 9px;border-radius:8px;font-size:11.5px;font-weight:700;text-decoration:none;border:1px solid transparent;white-space:nowrap}
.cbtn.wa  {background:#dcfce7;color:#15803d;border-color:#bbf7d0}.cbtn.wa:hover{background:#bbf7d0}
.cbtn.mail{background:#e0ecff;color:#1e40af;border-color:#bfdbfe}.cbtn.mail:hover{background:#bfdbfe}
.cbtn.tel {background:#f3e8ff;color:#7e22ce;border-color:#e9d5ff}.cbtn.tel:hover{background:#e9d5ff}
.cbtn.dis {opacity:.35;pointer-events:none}

/* ── Domaine/Thème ── */
.dom-title{font-weight:700;color:#1e293b;font-size:13px}
.dom-theme{color:#64748b;font-size:12px;margin-top:2px;max-width:200px;line-height:1.35;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}

/* ── Statut ── */
.pill{padding:4px 11px;border-radius:999px;font-size:11px;font-weight:800;white-space:nowrap;display:inline-flex;align-items:center;gap:4px}
.pill::before{content:'';width:6px;height:6px;border-radius:50%;background:currentColor;display:inline-block}
.pill.wait {background:#fff7ed;color:#9a3412}
.pill.ok   {background:#dcfce7;color:#166534}
.pill.devis{background:#ffedd5;color:#c2410c}
.pill.done {background:#f3f4f6;color:#374151}

/* ── Date ── */
.date-cell{font-size:12px;color:#475569;line-height:1.5}
.date-cell .heure{color:#94a3b8;font-size:11px}

/* ── Actions ── */
.df-view{display:inline-flex;align-items:center;gap:6px;background:#0f172a;color:#fff;padding:7px 13px;border-radius:9px;font-size:12px;font-weight:800;text-decoration:none}
.df-view:hover{background:#1e293b}
.muted{color:#94a3b8}

/* ── Pagination ── */
.pager{display:flex;gap:6px;justify-content:center;align-items:center;flex-wrap:wrap;margin:20px 0 4px}
.pager a,.pager span{min-width:36px;height:36px;padding:0 10px;border-radius:9px;display:inline-flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;text-decoration:none;border:1px solid #e5e7eb;color:#334155;background:#fff}
.pager a:hover{border-color:#1f3fe0;color:#1f3fe0}
.pager .cur{background:linear-gradient(135deg,#1f3fe0,#3b82f6);color:#fff;border-color:transparent}
.pager .dis{opacity:.35;pointer-events:none}

@media(max-width:980px){.df-kpis{grid-template-columns:1fr 1fr}.df-filters .grid{grid-template-columns:1fr 1fr}}
</style>

<div class="card">

  <!-- HEADER -->
  <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:4px">
    <h2 style="margin:0">&#127891; Demandes de formation</h2>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
      <a class="btn-csv2" href="export_csv.php?<?= e(http_build_query($curFilters)); ?>">&#128190; CSV</a>
      <a class="btn-csv2" href="export_print.php?<?= e(http_build_query($curFilters)); ?>" target="_blank">&#128438; Imprimer</a>
    </div>
  </div>

  <!-- KPIs -->
  <div class="df-kpis">
    <div class="df-kpi k-total"><b><?= (int)$total; ?></b><span>Total (filtré)</span></div>
    <div class="df-kpi k-wait" ><b><?= (int)$nbNouv; ?></b><span>&#9888;&#65039; À traiter</span></div>
    <div class="df-kpi k-devis"><b><?= (int)$nbDevis; ?></b><span>&#128196; Devis envoyés</span></div>
    <div class="df-kpi k-today"><b><?= (int)$nbToday; ?></b><span>&#128197; Aujourd&rsquo;hui</span></div>
  </div>

  <!-- FILTRES -->
  <form method="get" class="df-filters">
    <div class="grid">
      <div class="f">
        <label>&#128269; Recherche</label>
        <input type="text" name="q" value="<?= e($q); ?>" placeholder="Nom, tél, email, domaine, thème…" autofocus>
      </div>
      <div class="f">
        <label>Type de demandeur</label>
        <select name="type">
          <option value="">Tous</option>
          <?php foreach ($typesList as $t): ?>
            <option value="<?= e($t); ?>" <?= $typeD===(string)$t?'selected':''; ?>><?= e(ucfirst((string)$t)); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="f">
        <label>Statut</label>
        <select name="statut">
          <option value="">Tous les statuts</option>
          <?php foreach ($statutMeta as $key=>$meta): ?>
            <option value="<?= e($key); ?>" <?= $statut===$key?'selected':''; ?>><?= e($meta[0]); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="f">
        <label>Mode</label>
        <select name="mode">
          <option value="">Tous</option>
          <?php foreach ($modesList as $m): ?>
            <option value="<?= e($m); ?>" <?= $mode===(string)$m?'selected':''; ?>><?= e(ucfirst(str_replace('_',' ',(string)$m))); ?></option>
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
    <div style="display:flex;gap:8px;margin-top:12px;align-items:center">
      <button type="submit" class="btn-apply">&#128269; Appliquer</button>
      <a class="btn-reset" href="index.php">&#8635; Réinitialiser</a>
      <?php if ($q || $statut || $typeD || $mode || $dFrom || $dTo): ?>
        <span style="font-size:12px;color:#f59e0b;font-weight:700">&#9888;&#65039; Filtres actifs</span>
      <?php endif; ?>
    </div>
  </form>

  <!-- BARRE INFO -->
  <div class="df-bar">
    <div>Affichage <b><?= (int)$from; ?></b>–<b><?= (int)$to; ?></b> sur <b><?= (int)$total; ?></b> demande(s) &nbsp;&middot;&nbsp; Page <?= (int)$page; ?> / <?= (int)$totalPages; ?></div>
    <?php if ($nbNouv > 0): ?>
      <div style="background:#fff7ed;border:1px solid #fed7aa;color:#9a3412;border-radius:8px;padding:5px 12px;font-size:12px;font-weight:700">
        &#9888;&#65039; <?= (int)$nbNouv; ?> demande(s) en attente de traitement
      </div>
    <?php endif; ?>
  </div>

  <!-- TABLE -->
  <div style="overflow-x:auto">
  <table class="df-table">
    <thead>
      <tr>
        <th>#</th>
        <th>Demandeur</th>
        <th>Contact &amp; actions</th>
        <th>Domaine / Th&egrave;me</th>
        <th>Mode</th>
        <th>Statut</th>
        <th>Re&ccedil;u le</th>
        <th>Gestion</th>
      </tr>
    </thead>
    <tbody>
    <?php if (!$rows): ?>
      <tr><td colspan="8" style="padding:40px;text-align:center;color:#64748b;font-size:14px">&#128269; Aucune demande ne correspond à ces filtres.</td></tr>
    <?php endif; ?>
    <?php foreach ($rows as $idx => $r):
        $nomComplet = trim((string)($r['prenoms'] ?? '') . ' ' . (string)($r['nom'] ?? '')) ?: 'Demandeur';
        $prenom     = trim((string)($r['prenoms'] ?? '')) ?: $nomComplet;
        $entreprise = trim((string)($r['structure_nom'] ?? ''));
        $dom        = trim((string)($r['domaine_formation'] ?? ''));
        $theme      = trim((string)($r['theme_formation'] ?? ''));
        $email      = trim((string)($r['email'] ?? ''));
        $tel        = trim((string)($r['telephone'] ?? ''));
        $waNum      = pre_wa_number($tel);
        $sm         = $statutMeta[(string)($r['statut'] ?? '')] ?? [(string)$r['statut'] ?: 'Nouvelle', 'wait'];
        $isToday    = !empty($r['created_at']) && date('Y-m-d', strtotime((string)$r['created_at'])) === date('Y-m-d');
        $isNew      = ($sm[1] === 'wait');

        $sujet       = $theme !== '' ? $theme : ($dom !== '' ? $dom : 'votre formation');
        $waMsg       = "Bonjour " . $prenom . ", ici l'équipe IBIG EDUFORM. Nous revenons vers vous au sujet de votre demande de formation (" . $sujet . "). Comment pouvons-nous vous accompagner ?";
        $waHref      = $waNum !== '' ? 'https://wa.me/' . $waNum . '?text=' . rawurlencode($waMsg) : '';
        $mailSubject = "IBIG EDUFORM — Votre demande de formation : " . $sujet;
        $mailBody    = "Bonjour " . $prenom . ",\n\nNous vous remercions pour votre demande de formation (" . $sujet . ") auprès d'IBIG EDUFORM.\n\nNous revenons vers vous avec une proposition adaptée.\n\nBien cordialement,\nL'équipe IBIG EDUFORM";
        $mailHref    = $email !== '' ? 'mailto:' . $email . '?subject=' . rawurlencode($mailSubject) . '&body=' . rawurlencode($mailBody) : '';
        $telHref     = $tel   !== '' ? 'tel:' . preg_replace('/[^\d+]/', '', $tel) : '';
    ?>
      <tr class="<?= $isToday ? 'row-today' : ''; ?>">
        <td style="color:#94a3b8;font-size:12px;font-weight:700"><?= $offset + $idx + 1; ?></td>
        <td>
          <span class="who">
            <b>
              <?= e($nomComplet); ?>
              <?php if ($isToday && $isNew): ?><span class="badge-new">AUJOURD'HUI</span><?php endif; ?>
            </b>
            <div class="meta">
              <?php if ($entreprise !== ''): ?><?= e($entreprise); ?> &nbsp;<?php endif; ?>
              <span class="tag"><?= e($r['type_demandeur'] ?: '—'); ?></span>
            </div>
          </span>
        </td>
        <td>
          <div class="contact-lines">
            <?php if ($tel !== ''): ?><a href="<?= e($telHref); ?>">&#128222; <?= e($tel); ?></a><br><?php endif; ?>
            <?php if ($email !== ''): ?><a href="mailto:<?= e($email); ?>">&#9993; <?= e($email); ?></a><?php endif; ?>
            <?php if ($tel === '' && $email === ''): ?><span class="muted">—</span><?php endif; ?>
          </div>
          <div class="contact-btns">
            <a class="cbtn wa   <?= $waHref   === '' ? 'dis' : ''; ?>" <?= $waHref   !== '' ? 'href="'.e($waHref).'" target="_blank" rel="noopener"' : ''; ?>>&#128172; WhatsApp</a>
            <a class="cbtn mail <?= $mailHref === '' ? 'dis' : ''; ?>" <?= $mailHref !== '' ? 'href="'.e($mailHref).'"' : ''; ?>>&#9993; E-mail</a>
            <?php if ($telHref !== ''): ?><a class="cbtn tel" href="<?= e($telHref); ?>">&#128222; Appeler</a><?php endif; ?>
          </div>
        </td>
        <td>
          <div class="dom-title"><?= e($dom !== '' ? $dom : '—'); ?></div>
          <?php if ($theme !== ''): ?><div class="dom-theme" title="<?= e($theme); ?>"><?= e($theme); ?></div><?php endif; ?>
        </td>
        <td style="font-size:12.5px;color:#334155"><?= e($r['mode_formation'] ? ucfirst(str_replace('_', ' ', (string)$r['mode_formation'])) : '—'); ?></td>
        <td><span class="pill <?= e($sm[1]); ?>"><?= e($sm[0]); ?></span></td>
        <td class="date-cell">
          <?php if (!empty($r['created_at'])): ?>
            <?= date('d/m/Y', strtotime((string)$r['created_at'])); ?><br>
            <span class="heure"><?= date('H:i', strtotime((string)$r['created_at'])); ?></span>
          <?php else: ?>—<?php endif; ?>
        </td>
        <td><a class="df-view" href="view.php?id=<?= (int)$r['id']; ?>">&#128065; Ouvrir</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>

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
          if ($i===$page): ?><span class="cur"><?= $i; ?></span><?php
          else: ?><a href="<?= e($pageUrl($i)); ?>"><?= $i; ?></a><?php endif;
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
