<?php
declare(strict_types=1);

/**
 * ============================================================
 * ADMIN — STATISTIQUES GLOBALES (FULL SINGLE FILE)
 * Fichier : /admin/statistics/index.php
 * ============================================================
 *
 * PREREQUIS SQL (1 fois) :
 *   ALTER TABLE site_events ADD is_bot TINYINT(1) DEFAULT 0;
 */

require_once __DIR__ . '/../_init.php';

/* =========================
   AUTH / MIDDLEWARE
========================= */
$mw = realpath(__DIR__ . '/../auth/middleware.php');
if (!$mw) { die('Middleware introuvable'); }
require_once $mw;
Middleware::requireAuth();

/* =========================
   META (utilisé par layout)
========================= */
$pageTitle  = 'Statistiques';
$activeMenu = 'statistics';

/* =========================
   DB
========================= */
$pdo = Database::connect();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

/* =========================
   HELPERS SAFE
========================= */
if (!function_exists('e')) {
  function e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('qi')) {
  function qi(string $v): string {
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $v)) {
      throw new RuntimeException('Identifiant SQL invalide : ' . $v);
    }
    return '`' . $v . '`';
  }
}

function fetch_one(PDO $pdo, string $sql, array $params = []) {
  $st = $pdo->prepare($sql);
  $st->execute($params);
  return $st->fetchColumn();
}
function fetch_all(PDO $pdo, string $sql, array $params = []): array {
  $st = $pdo->prepare($sql);
  $st->execute($params);
  return $st->fetchAll(PDO::FETCH_ASSOC);
}
function fmt_duration(float $s): string {
  $s = (int)$s;
  if ($s < 60) return $s . 's';
  if ($s < 3600) return floor($s / 60) . 'm ' . ($s % 60) . 's';
  return floor($s / 3600) . 'h ' . floor(($s % 3600) / 60) . 'm';
}

/* ============================================================
   TABLE & COLONNES — PROD (site_events)
============================================================ */
$table = 'site_events';
$qTable = qi($table);

$colDate = 'created_at';
$colPage = 'page_url';
$colSess = 'session_key';
$colDev  = 'device';
$colPays = 'country';
$colRef  = null;          // ex: 'referrer' si tu l’ajoutes plus tard
$colBrowser = null;       // ex: 'browser' si tu l’ajoutes plus tard

/* BOT : colonne existe (ALTER TABLE déjà fait) */
$hasBot = true;
$colBot = 'is_bot';

$qDate = qi($colDate);
$qPage = qi($colPage);
$qSess = qi($colSess);

$qBot  = $hasBot ? qi($colBot) : null;
$qDev  = !empty($colDev)  ? qi($colDev)  : null;
$qPays = !empty($colPays) ? qi($colPays) : null;
$qRef  = !empty($colRef)  ? qi($colRef)  : null;

/* expr session avec alias v */
$sessExpr = "v.$qSess";

/* ============================================================
   FILTRES (PÉRIODE + RECHERCHE) + WHERE + PARAMS
============================================================ */
function clamp_period(string $p): string {
  $allowed = ['today','7d','30d','90d','custom'];
  return in_array($p, $allowed, true) ? $p : '30d';
}
function norm_ymd(string $s): string {
  $s = trim($s);
  if ($s === '') return '';
  $dt = DateTime::createFromFormat('Y-m-d', $s);
  return $dt ? $dt->format('Y-m-d') : '';
}
function period_label(string $period, string $start, string $end): string {
  if ($period === 'today') return "Aujourd’hui";
  if ($period === '7d')    return "7 jours";
  if ($period === '30d')   return "30 jours";
  if ($period === '90d')   return "90 jours";
  if ($period === 'custom') return ($start && $end) ? "Du $start au $end" : "Personnalisée";
  return "30 jours";
}

/**
 * Retourne:
 * - rangeFrom (Y-m-d)
 * - rangeTo   (Y-m-d)
 * - rangeDays (liste Y-m-d inclusive)
 */
function compute_range(string $period, string $start, string $end): array {
  if ($period === 'today') {
    $from = new DateTime('today'); $to = new DateTime('today');
  } elseif ($period === '7d') {
    $from = (new DateTime('today'))->modify('-6 days'); $to = new DateTime('today');
  } elseif ($period === '30d') {
    $from = (new DateTime('today'))->modify('-29 days'); $to = new DateTime('today');
  } elseif ($period === '90d') {
    $from = (new DateTime('today'))->modify('-89 days'); $to = new DateTime('today');
  } elseif ($period === 'custom' && $start && $end) {
    $from = DateTime::createFromFormat('Y-m-d', $start) ?: (new DateTime('today'))->modify('-29 days');
    $to   = DateTime::createFromFormat('Y-m-d', $end)   ?: new DateTime('today');
  } else {
    $from = (new DateTime('today'))->modify('-29 days'); $to = new DateTime('today');
  }

  if ($from > $to) { $tmp = $from; $from = $to; $to = $tmp; }

  $days = [];
  $dp = new DatePeriod($from, new DateInterval('P1D'), (clone $to)->modify('+1 day'));
  foreach ($dp as $d) $days[] = $d->format('Y-m-d');

  return [$from->format('Y-m-d'), $to->format('Y-m-d'), $days];
}

/* =========================
   INPUTS GET
========================= */
$period = clamp_period((string)($_GET['period'] ?? '30d'));
$start  = norm_ymd((string)($_GET['start'] ?? ''));
$end    = norm_ymd((string)($_GET['end'] ?? ''));
$q      = trim((string)($_GET['q'] ?? ''));

/* custom uniquement */
if ($period !== 'custom') { $start = ''; $end = ''; }

$labelPeriod = period_label($period, $start, $end);
[$rangeFrom, $rangeTo, $rangeDays] = compute_range($period, $start, $end);

/* =========================
   WHERE + PARAMS (ALL vs NOBOT)
   IMPORTANT : on utilise alias v partout dans les requêtes
========================= */
$paramsAll   = [];
$paramsNoBot = [];

$whereAll   = "WHERE 1=1";
$whereNoBot = "WHERE 1=1";

if ($hasBot && $qBot !== null) {
  $whereNoBot .= " AND v.$qBot = 0";
}

/* filtre période */
if ($period === 'today') {
  $whereAll   .= " AND DATE(v.$qDate) = CURDATE()";
  $whereNoBot .= " AND DATE(v.$qDate) = CURDATE()";
} elseif ($period === '7d') {
  $whereAll   .= " AND v.$qDate >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
  $whereNoBot .= " AND v.$qDate >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
} elseif ($period === '30d') {
  $whereAll   .= " AND v.$qDate >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
  $whereNoBot .= " AND v.$qDate >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
} elseif ($period === '90d') {
  $whereAll   .= " AND v.$qDate >= DATE_SUB(NOW(), INTERVAL 90 DAY)";
  $whereNoBot .= " AND v.$qDate >= DATE_SUB(NOW(), INTERVAL 90 DAY)";
} elseif ($period === 'custom' && $start && $end) {
  $whereAll   .= " AND DATE(v.$qDate) BETWEEN ? AND ?";
  $whereNoBot .= " AND DATE(v.$qDate) BETWEEN ? AND ?";
  $paramsAll[]   = $start; $paramsAll[]   = $end;
  $paramsNoBot[] = $start; $paramsNoBot[] = $end;
} else {
  $whereAll   .= " AND v.$qDate >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
  $whereNoBot .= " AND v.$qDate >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
}

/* recherche page contient */
if ($q !== '') {
  if (mb_strlen($q, 'UTF-8') > 120) $q = mb_substr($q, 0, 120, 'UTF-8');
  $whereAll   .= " AND COALESCE(v.$qPage,'') LIKE ?";
  $whereNoBot .= " AND COALESCE(v.$qPage,'') LIKE ?";
  $like = '%' . $q . '%';
  $paramsAll[]   = $like;
  $paramsNoBot[] = $like;
}

/* =========================
   LIENS FILTRES (UI)
========================= */
function build_qs(array $extra): string {
  $base = $_GET;
  foreach ($extra as $k => $v) {
    if ($v === null || $v === '') unset($base[$k]);
    else $base[$k] = (string)$v;
  }
  return '?' . http_build_query($base);
}

$linkToday  = build_qs(['period'=>'today','start'=>null,'end'=>null]);
$link7d     = build_qs(['period'=>'7d','start'=>null,'end'=>null]);
$link30d    = build_qs(['period'=>'30d','start'=>null,'end'=>null]);
$link90d    = build_qs(['period'=>'90d','start'=>null,'end'=>null]);
$linkCustom = build_qs(['period'=>'custom']);

$customIncomplete = ($period === 'custom' && (!$start || !$end));

/* =========================
   DEBUG MODE
========================= */
$debug = ((int)($_GET['debug'] ?? 0) === 1);

/* ============================================================
   DEFAULTS (KPIs + CHARTS + TABLES)
============================================================ */
$errors = [];
$diag = [
  'table' => $table,
  'date_col' => $colDate,
  'page_col' => $colPage,
  'bot_col'  => $hasBot ? $colBot : null,
  'period'   => $period,
  'rangeFrom'=> $rangeFrom,
  'rangeTo'  => $rangeTo,
  'q'        => $q,
  'last_hit' => null,
  'last_rows'=> [],
];

$totalAll = 0;
$totalNoBot = 0;
$botCount = 0;
$botRate = 0.0;

$sessions = 0;
$pagesPerSession = 0.0;
$avgSessionSeconds = 0.0;

$bounce = 0;
$bounceRate = 0.0;
$engagementRate = 0.0;

$dailyLabels = $rangeDays ?? [];
$dailyValues = array_fill(0, count($dailyLabels), 0);

$hourLabels = [];
$hourValues = array_fill(0, 24, 0);

$weekLabels = ['Dimanche','Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'];
$weekValues = array_fill(0, 7, 0);

$countryLabels = [];
$countryValues = [];

$devLabels = [];
$devValues = [];

$refLabels = [];
$refValues = [];

$topPages = [];
$latestPages = [];
$entryPages = [];
$exitPages = [];
$browserTop = [];

/* ============================================================
   BUILD DATA (KPIs + CHARTS + TABLES)
============================================================ */
try {

  /* ---------- DIAGNOSTIC : dernier hit ---------- */
  $diag['last_hit'] = fetch_one($pdo, "SELECT MAX(v.$qDate) FROM $qTable v", []);

  /* construire SELECT last_rows sans ternaires dans la string */
  $selectLast = "
    SELECT
      COALESCE(NULLIF(v.$qPage,''),'(inconnu)') AS page,
      v.$qDate AS visited_at
  ";
  if ($hasBot && $qBot !== null) $selectLast .= ", v.$qBot AS is_bot";
  else $selectLast .= ", 0 AS is_bot";

  if ($qDev)  $selectLast .= ", COALESCE(NULLIF(v.$qDev,''),'unknown') AS device";
  else        $selectLast .= ", NULL AS device";

  if ($qPays) $selectLast .= ", COALESCE(NULLIF(v.$qPays,''),'Inconnu') AS country";
  else        $selectLast .= ", NULL AS country";

  if ($qRef)  $selectLast .= ", COALESCE(NULLIF(v.$qRef,''),'Direct/Unknown') AS ref";
  else        $selectLast .= ", NULL AS ref";

  $selectLast .= "
    FROM $qTable v
    ORDER BY v.$qDate DESC
    LIMIT 5
  ";
  $diag['last_rows'] = fetch_all($pdo, $selectLast, []);

  /* ---------- KPI : total ALL / NOBOT ---------- */
  $totalAll   = (int)fetch_one($pdo, "SELECT COUNT(*) FROM $qTable v $whereAll", $paramsAll);
  $totalNoBot = (int)fetch_one($pdo, "SELECT COUNT(*) FROM $qTable v $whereNoBot", $paramsNoBot);

  /* bots */
  if ($hasBot && $qBot !== null) {
    $botCount = (int)fetch_one($pdo, "SELECT COUNT(*) FROM $qTable v $whereAll AND v.$qBot = 1", $paramsAll);
    $botRate = ($totalAll > 0) ? round(($botCount / $totalAll) * 100, 1) : 0.0;
  }

  /* ---------- KPI : sessions ---------- */
  $sessions = (int)fetch_one($pdo, "
    SELECT COUNT(DISTINCT $sessExpr)
    FROM $qTable v
    $whereNoBot
  ", $paramsNoBot);

  $pagesPerSession = ($sessions > 0) ? round($totalNoBot / $sessions, 2) : 0.0;

  /* ---------- KPI : durée moyenne ---------- */
  $avgSessionSeconds = (float)fetch_one($pdo, "
    SELECT AVG(duration) FROM (
      SELECT TIMESTAMPDIFF(SECOND, MIN(v.$qDate), MAX(v.$qDate)) AS duration
      FROM $qTable v
      $whereNoBot
      GROUP BY $sessExpr
    ) t
  ", $paramsNoBot);

  /* ---------- KPI : bounce / engagement ---------- */
  $bounce = (int)fetch_one($pdo, "
    SELECT SUM(CASE WHEN pages <= 1 OR duration < 10 THEN 1 ELSE 0 END) FROM (
      SELECT COUNT(*) AS pages,
             TIMESTAMPDIFF(SECOND, MIN(v.$qDate), MAX(v.$qDate)) AS duration
      FROM $qTable v
      $whereNoBot
      GROUP BY $sessExpr
    ) s
  ", $paramsNoBot);

  $bounceRate = ($sessions > 0) ? round(($bounce / $sessions) * 100, 1) : 0.0;
  $engagementRate = ($sessions > 0) ? round((1 - ($bounce / $sessions)) * 100, 1) : 0.0;

  /* ============================================================
     CHARTS
  ============================================================ */

  /* DAILY */
  $dailyMap = [];
  $dailyRows = fetch_all($pdo, "
    SELECT DATE(v.$qDate) AS d, COUNT(*) AS total
    FROM $qTable v
    $whereNoBot
    GROUP BY d
    ORDER BY d
  ", $paramsNoBot);

  foreach ($dailyRows as $r) $dailyMap[(string)$r['d']] = (int)$r['total'];

  $dailyLabels = $rangeDays;
  $dailyValues = [];
  foreach ($dailyLabels as $d) $dailyValues[] = $dailyMap[$d] ?? 0;

  /* HOURS */
  $hourLabels = [];
  for ($i=0; $i<24; $i++) $hourLabels[] = sprintf('%02dh', $i);

  $hourRows = fetch_all($pdo, "
    SELECT HOUR(v.$qDate) AS h, COUNT(*) AS total
    FROM $qTable v
    $whereNoBot
    GROUP BY h
  ", $paramsNoBot);

  foreach ($hourRows as $r) {
    $h = (int)$r['h'];
    if ($h >= 0 && $h <= 23) $hourValues[$h] = (int)$r['total'];
  }

  /* WEEK DAYS */
  $weekRows = fetch_all($pdo, "
    SELECT DAYOFWEEK(v.$qDate) AS d, COUNT(*) AS total
    FROM $qTable v
    $whereNoBot
    GROUP BY d
  ", $paramsNoBot);

  foreach ($weekRows as $r) {
    $idx = (int)$r['d'];
    if ($idx >= 1 && $idx <= 7) $weekValues[$idx - 1] = (int)$r['total'];
  }

  /* DEVICES */
  if ($qDev) {
    $devRows = fetch_all($pdo, "
      SELECT COALESCE(NULLIF(v.$qDev,''),'unknown') AS device, COUNT(*) AS total
      FROM $qTable v
      $whereNoBot
      GROUP BY device
      ORDER BY total DESC
      LIMIT 8
    ", $paramsNoBot);

    foreach ($devRows as $r) { $devLabels[] = (string)$r['device']; $devValues[] = (int)$r['total']; }
  }

  /* COUNTRIES */
  if ($qPays) {
    $countryRows = fetch_all($pdo, "
      SELECT COALESCE(NULLIF(v.$qPays,''),'Inconnu') AS country, COUNT(*) AS total
      FROM $qTable v
      $whereNoBot
      GROUP BY country
      ORDER BY total DESC
      LIMIT 15
    ", $paramsNoBot);

    foreach ($countryRows as $r) { $countryLabels[] = (string)$r['country']; $countryValues[] = (int)$r['total']; }
  }

  /* REFERRERS */
  if ($qRef) {
    $refRows = fetch_all($pdo, "
      SELECT COALESCE(NULLIF(v.$qRef,''),'Direct/Unknown') AS ref, COUNT(*) AS total
      FROM $qTable v
      $whereNoBot
      GROUP BY ref
      ORDER BY total DESC
      LIMIT 10
    ", $paramsNoBot);

    foreach ($refRows as $r) { $refLabels[] = (string)$r['ref']; $refValues[] = (int)$r['total']; }
  }

  /* BROWSERS */
  if (!empty($colBrowser)) {
    $qBrowser = qi($colBrowser);
    $browserTop = fetch_all($pdo, "
      SELECT COALESCE(NULLIF(v.$qBrowser,''),'Inconnu') AS browser, COUNT(*) AS total
      FROM $qTable v
      $whereNoBot
      GROUP BY browser
      ORDER BY total DESC
      LIMIT 10
    ", $paramsNoBot);
  }

  /* ============================================================
     TABLES
  ============================================================ */

  /* TOP PAGES */
  $topPages = fetch_all($pdo, "
    SELECT
      COALESCE(NULLIF(v.$qPage,''),'(inconnu)') AS page,
      COUNT(*) AS total,
      COUNT(DISTINCT $sessExpr) AS visiteurs
    FROM $qTable v
    $whereNoBot
    GROUP BY page
    ORDER BY total DESC
    LIMIT 50
  ", $paramsNoBot);

  /* LATEST PAGES (ALL) */
  $sqlLatest = "
    SELECT
      COALESCE(NULLIF(v.$qPage,''),'(inconnu)') AS page,
      v.$qDate AS visited_at
  ";
  if ($hasBot && $qBot !== null) $sqlLatest .= ", v.$qBot AS is_bot";
  else $sqlLatest .= ", 0 AS is_bot";

  if ($qDev)  $sqlLatest .= ", COALESCE(NULLIF(v.$qDev,''),'unknown') AS device";
  else        $sqlLatest .= ", NULL AS device";

  if ($qPays) $sqlLatest .= ", COALESCE(NULLIF(v.$qPays,''),'Inconnu') AS country";
  else        $sqlLatest .= ", NULL AS country";

  if ($qRef)  $sqlLatest .= ", COALESCE(NULLIF(v.$qRef,''),'Direct/Unknown') AS ref";
  else        $sqlLatest .= ", NULL AS ref";

  $sqlLatest .= "
    FROM $qTable v
    $whereAll
    ORDER BY v.$qDate DESC
    LIMIT 50
  ";
  $latestPages = fetch_all($pdo, $sqlLatest, $paramsAll);

  /* ENTRY PAGES */
  $entryPages = fetch_all($pdo, "
    SELECT page, COUNT(*) AS total
    FROM (
      SELECT
        $sessExpr AS session_id,
        SUBSTRING_INDEX(
          GROUP_CONCAT(COALESCE(NULLIF(v.$qPage,''),'(inconnu)') ORDER BY v.$qDate ASC SEPARATOR ','),
          ',', 1
        ) AS page
      FROM $qTable v
      $whereNoBot
      GROUP BY session_id
    ) t
    GROUP BY page
    ORDER BY total DESC
    LIMIT 15
  ", $paramsNoBot);

  /* EXIT PAGES */
  $exitPages = fetch_all($pdo, "
    SELECT page, COUNT(*) AS total
    FROM (
      SELECT
        $sessExpr AS session_id,
        SUBSTRING_INDEX(
          GROUP_CONCAT(COALESCE(NULLIF(v.$qPage,''),'(inconnu)') ORDER BY v.$qDate DESC SEPARATOR ','),
          ',', 1
        ) AS page
      FROM $qTable v
      $whereNoBot
      GROUP BY session_id
    ) t
    GROUP BY page
    ORDER BY total DESC
    LIMIT 15
  ", $paramsNoBot);

} catch (Throwable $ex) {
  $errors[] = $ex->getMessage();
}

/* ============================================================
   UI (HTML + CSS) — BUFFER UNIQUE
============================================================ */
ob_start();
?>

<style>
.stats-wrap{max-width:1400px;margin:0 auto;padding:16px}
.stats-wrap *{box-sizing:border-box}

/* ── Header & filtres ── */
.stats-header{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:20px;flex-wrap:wrap}
.stats-title{font-size:22px;font-weight:900;line-height:1.15;margin:0}
.stats-sub{margin-top:6px;font-size:13px;color:#64748b;display:flex;flex-wrap:wrap;gap:6px;align-items:center}
.filters{display:flex;gap:8px;flex-wrap:wrap;align-items:center}
.filters a{display:inline-flex;align-items:center;gap:5px;padding:8px 12px;border-radius:10px;background:#f1f5f9;color:#0f172a;text-decoration:none;font-weight:800;font-size:12.5px;transition:all .15s}
.filters a:hover{background:#e2e8f0}
.filters a.active{background:#0f172a;color:#fff}
.tools{display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;margin-top:10px}
.tool{display:flex;flex-direction:column;gap:5px}
.tool label{font-size:11.5px;color:#475569;font-weight:700;text-transform:uppercase;letter-spacing:.3px}
.tool input[type="text"],.tool input[type="date"]{height:38px;padding:0 11px;border-radius:10px;border:1px solid #e2e8f0;background:#fff;outline:none;font-size:13px;min-width:200px;transition:border-color .15s}
.tool input[type="date"]{min-width:160px}
.tool input:focus{border-color:#1f3fe0;box-shadow:0 0 0 3px rgba(31,63,224,.1)}
.tool .btn{height:38px;padding:0 16px;border-radius:10px;border:0;background:#0f172a;color:#fff;font-weight:900;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;text-decoration:none;font-size:13px}
.tool .btn:hover{background:#1e293b}
.tool .btn.secondary{background:#e2e8f0;color:#0f172a}
.tool .btn.secondary:hover{background:#cbd5e1}
.tool small{color:#94a3b8;font-size:11px}

/* ── KPIs ── */
.kpi-grid{display:grid;grid-template-columns:repeat(6,1fr);gap:14px;margin-bottom:20px}
@media(max-width:1200px){.kpi-grid{grid-template-columns:repeat(3,1fr)}}
@media(max-width:700px){.kpi-grid{grid-template-columns:repeat(2,1fr)}}
.kpi{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:16px 14px;box-shadow:0 4px 14px rgba(15,23,42,.05);display:flex;flex-direction:column;gap:4px}
.kpi.k-blue  {border-top:3px solid #3b82f6}
.kpi.k-indigo{border-top:3px solid #6366f1}
.kpi.k-green {border-top:3px solid #22c55e}
.kpi.k-sky   {border-top:3px solid #0ea5e9}
.kpi.k-amber {border-top:3px solid #f59e0b}
.kpi.k-red   {border-top:3px solid #ef4444}
.kpi.k-teal  {border-top:3px solid #14b8a6}
.kpi .label{font-size:11px;color:#64748b;font-weight:700;text-transform:uppercase;letter-spacing:.3px}
.kpi .value{font-size:26px;font-weight:900;margin-top:4px;letter-spacing:-.5px;color:#0f172a;line-height:1.1}
.kpi.k-blue   .value{color:#1d4ed8}
.kpi.k-indigo .value{color:#4338ca}
.kpi.k-green  .value{color:#16a34a}
.kpi.k-sky    .value{color:#0284c7}
.kpi.k-amber  .value{color:#b45309}
.kpi.k-red    .value{color:#dc2626}
.kpi.k-teal   .value{color:#0f766e}
.kpi .hint{font-size:11px;color:#94a3b8;margin-top:2px}

/* ── Sections ── */
.section{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:18px;margin-bottom:18px;overflow:hidden;box-shadow:0 2px 8px rgba(15,23,42,.04)}
.section h3{font-size:15px;font-weight:800;margin:0 0 14px;color:#0f172a;display:flex;align-items:center;gap:8px}
.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.grid-3{display:grid;grid-template-columns:repeat(3,1fr);gap:16px}
@media(max-width:900px){.grid-2,.grid-3{grid-template-columns:1fr}}
.section canvas{display:block;width:100% !important;max-height:320px !important}
#chartDaily{max-height:260px !important}

/* ── Tables ── */
.table{width:100%;border-collapse:collapse}
.table th{text-align:left;font-size:11.5px;color:#6b7280;border-bottom:2px solid #eef2f7;padding:9px 10px;white-space:nowrap;background:#fafbff;text-transform:uppercase;letter-spacing:.3px}
.table td{padding:9px 10px;border-bottom:1px solid #f1f5f9;font-size:13px;vertical-align:middle}
.table tr:hover td{background:#fafbff}
.table td.right{text-align:right;white-space:nowrap;font-weight:700}
.table td.num{font-weight:700;color:#1e40af}
.table code{font-size:12px;background:#f1f5f9;color:#334155;padding:3px 7px;border-radius:6px;display:inline-block;max-width:520px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-family:ui-monospace,monospace}
.bar-wrap{display:flex;align-items:center;gap:8px}
.bar-bg{flex:1;height:6px;background:#e2e8f0;border-radius:4px;min-width:60px}
.bar-fill{height:6px;background:#3b82f6;border-radius:4px;transition:width .3s}

/* ── Badges ── */
.badge{padding:3px 9px;border-radius:999px;font-size:11px;font-weight:800;display:inline-flex;align-items:center;gap:5px}
.badge.bot  {background:#fee2e2;color:#991b1b}
.badge.human{background:#dcfce7;color:#166634}
.badge.soft {background:#f1f5f9;color:#334155;border:1px solid #e2e8f0}

/* ── Alertes ── */
.alert{border-radius:12px;padding:12px 14px;margin-bottom:14px;font-size:13px;line-height:1.4;border:1px solid transparent}
.alert.err {background:#fff1f2;border-color:#fecdd3;color:#9f1239}
.alert.warn{background:#fffbeb;border-color:#fde68a;color:#92400e}
.debug{font-size:12px;color:#334155}
.debug pre{background:#0b1220;color:#e5e7eb;padding:12px;border-radius:12px;overflow:auto;font-size:12px}

/* ── Date table ── */
.date-mini{font-size:12px;color:#475569;line-height:1.4}
.date-mini .h{font-size:11px;color:#94a3b8}
</style>

<div class="stats-wrap">

  <?php if (!empty($errors)): ?>
    <div class="alert err">
      <strong>&#x26A0; Erreur :</strong><br>
      <?php foreach ($errors as $msg): ?>
        &#x2022; <?= e($msg) ?><br>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <div class="stats-header">
    <div>
      <h2 class="stats-title">&#x1F4CA; Statistiques globales</h2>

      <div class="stats-sub">
        P&#xE9;riode : <strong><?= e($labelPeriod) ?></strong>
        <span class="badge soft" style="margin-left:8px;">
          &#x1F5D3; <?= e($rangeFrom) ?> &#x2192; <?= e($rangeTo) ?>
        </span>
        <?php if ($q !== ''): ?>
          <span class="badge soft" style="margin-left:8px;">
            &#x1F50E; <?= e($q) ?>
          </span>
        <?php endif; ?>
      </div>

      <form method="get" class="tools">
        <input type="hidden" name="period" value="<?= e($period) ?>"/>

        <div class="tool">
          <label for="q">&#x1F50E; Recherche page</label>
          <input id="q" name="q" type="text" value="<?= e($q) ?>" placeholder="/formation.php, /admin/..." />
          <small>Filtre sur URL/Page</small>
        </div>

        <div class="tool">
          <label for="start">&#x1F4C5; D&#xE9;but</label>
          <input id="start" name="start" type="date" value="<?= e($start) ?>" />
        </div>

        <div class="tool">
          <label for="end">&#x1F4C5; Fin</label>
          <input id="end" name="end" type="date" value="<?= e($end) ?>" />
        </div>

        <div class="tool" style="flex-direction:row; gap:8px; align-items:flex-end;">
          <button class="btn" type="submit" name="period" value="custom">
            &#x1F9FE; Appliquer
          </button>
          <a class="btn secondary" href="<?= e(build_qs(['q'=>null,'period'=>'30d','start'=>null,'end'=>null])) ?>">
            &#x21BA; Reset
          </a>
        </div>
      </form>

      <?php if (!empty($customIncomplete)): ?>
        <div class="alert warn" style="margin-top:10px;">
          <strong>&#x26A0; P&#xE9;riode personnalis&#xE9;e :</strong>
          renseigne <strong>D&#xE9;but</strong> et <strong>Fin</strong>, sinon fallback 30 jours.
        </div>
      <?php endif; ?>
    </div>

    <div class="filters">
      <a href="<?= e($linkToday) ?>" class="<?= $period==='today'?'active':'' ?>">&#x23F1; Aujourd&#x2019;hui</a>
      <a href="<?= e($link7d) ?>" class="<?= $period==='7d'?'active':'' ?>">&#x1F4C6; 7j</a>
      <a href="<?= e($link30d) ?>" class="<?= $period==='30d'?'active':'' ?>">&#x1F4C5; 30j</a>
      <a href="<?= e($link90d) ?>" class="<?= $period==='90d'?'active':'' ?>">&#x1F5D3; 90j</a>
      <a href="<?= e($linkCustom) ?>" class="<?= $period==='custom'?'active':'' ?>">&#x1F9FE; Custom</a>
      <?php if ($debug): ?><span class="badge soft">&#x1F41B; DEBUG</span><?php endif; ?>
    </div>
  </div>

  <div class="kpi-grid">
    <div class="kpi k-blue">
      <div class="label">&#128100; Visites humaines</div>
      <div class="value"><?= number_format($totalNoBot) ?></div>
      <div class="hint">Hors bots · <?= e($labelPeriod) ?></div>
    </div>

    <div class="kpi k-indigo">
      <div class="label">&#128202; Sessions</div>
      <div class="value"><?= number_format($sessions) ?></div>
      <div class="hint">Visiteurs uniques (approx)</div>
    </div>

    <div class="kpi k-sky">
      <div class="label">&#128196; Pages / session</div>
      <div class="value"><?= number_format($pagesPerSession, 1, ',', ' ') ?></div>
      <div class="hint">Moyenne des pages vues</div>
    </div>

    <div class="kpi k-teal">
      <div class="label">&#9201; Durée moyenne</div>
      <div class="value"><?= e(fmt_duration($avgSessionSeconds)) ?></div>
      <div class="hint">Par session</div>
    </div>

    <div class="kpi k-green">
      <div class="label">&#128994; Engagement</div>
      <div class="value"><?= number_format($engagementRate, 1, ',', ' ') ?>%</div>
      <div class="hint">Sessions &gt; 1 page ou &gt; 10s</div>
    </div>

    <div class="kpi k-red">
      <div class="label">&#128308; Rebond</div>
      <div class="value"><?= number_format($bounceRate, 1, ',', ' ') ?>%</div>
      <div class="hint">Bots : <?= $hasBot ? number_format($botRate,1,',', ' ').'%' : 'N/A' ?> (<?= number_format($totalAll) ?> total)</div>
    </div>
  </div>

  <div class="section">
    <h3>&#x1F4C8; &#xC9;volution des visites</h3>
    <canvas id="chartDaily" height="90"></canvas>
  </div>

  <div class="grid-2">
    <div class="section">
      <h3>&#x23F0; Heures de visite</h3>
      <canvas id="chartHours" height="140"></canvas>
    </div>
    <div class="section">
      <h3>&#x1F4C5; Jours de la semaine</h3>
      <canvas id="chartWeek" height="140"></canvas>
    </div>
  </div>

  <div class="grid-3">
    <div class="section">
      <h3>&#x1F30D; Top pays</h3>
      <?php if (!empty($countryLabels)): ?>
        <canvas id="chartCountries" height="180"></canvas>
      <?php else: ?>
        <div class="alert warn" style="margin:0;">&#x26A0; Colonne pays absente (country/pays).</div>
      <?php endif; ?>
    </div>

    <div class="section">
      <h3>&#x1F4BB; Appareils</h3>
      <?php if (!empty($devLabels)): ?>
        <canvas id="chartDevices" height="180"></canvas>
      <?php else: ?>
        <div class="alert warn" style="margin:0;">&#x26A0; Colonne device absente.</div>
      <?php endif; ?>
    </div>

    <div class="section">
      <h3>&#x1F517; R&#xE9;f&#xE9;rents</h3>
      <?php if (!empty($refLabels)): ?>
        <canvas id="chartRefs" height="180"></canvas>
      <?php else: ?>
        <div class="alert warn" style="margin:0;">&#x26A0; Colonne referrer/source absente.</div>
      <?php endif; ?>
    </div>
  </div>

  <div class="section">
    <h3>&#128196; Top 50 pages vues</h3>
    <?php $maxVues = !empty($topPages) ? max(array_column($topPages, 'total')) : 1; ?>
    <table class="table">
      <thead>
        <tr><th>#</th><th>Page</th><th style="min-width:120px">Popularité</th><th class="right">Vues</th><th class="right">Visiteurs</th></tr>
      </thead>
      <tbody>
      <?php if (empty($topPages)): ?>
        <tr><td colspan="5" class="debug" style="padding:20px;text-align:center">Aucune donnée.</td></tr>
      <?php else: ?>
        <?php foreach ($topPages as $i => $r): ?>
          <?php $pct = $maxVues > 0 ? round(((int)$r['total'] / $maxVues) * 100) : 0; ?>
          <tr>
            <td style="color:#94a3b8;font-size:12px;font-weight:700"><?= (int)$i + 1 ?></td>
            <td><code title="<?= e($r['page']) ?>"><?= e($r['page']) ?></code></td>
            <td>
              <div class="bar-wrap">
                <div class="bar-bg"><div class="bar-fill" style="width:<?= $pct ?>%"></div></div>
                <span style="font-size:11px;color:#94a3b8;width:30px;text-align:right"><?= $pct ?>%</span>
              </div>
            </td>
            <td class="right num"><?= number_format((int)$r['total']) ?></td>
            <td class="right" style="color:#64748b"><?= number_format((int)$r['visiteurs']) ?></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>

  <div class="grid-2">
    <div class="section">
      <h3>&#9654; Pages d'entrée <span style="font-size:12px;font-weight:600;color:#6b7280">(première page visitée)</span></h3>
      <table class="table">
        <thead><tr><th>#</th><th>Page</th><th class="right">Sessions</th></tr></thead>
        <tbody>
        <?php if (empty($entryPages)): ?>
          <tr><td colspan="3" class="debug" style="padding:16px;text-align:center">Aucune donnée.</td></tr>
        <?php else: ?>
          <?php foreach ($entryPages as $i => $r): ?>
            <tr>
              <td style="color:#94a3b8;font-size:12px;font-weight:700"><?= (int)$i + 1 ?></td>
              <td><code title="<?= e($r['page']) ?>"><?= e($r['page']) ?></code></td>
              <td class="right num"><?= number_format((int)$r['total']) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
      </table>
    </div>

    <div class="section">
      <h3>&#9664; Pages de sortie <span style="font-size:12px;font-weight:600;color:#6b7280">(dernière page visitée)</span></h3>
      <table class="table">
        <thead><tr><th>#</th><th>Page</th><th class="right">Sessions</th></tr></thead>
        <tbody>
        <?php if (empty($exitPages)): ?>
          <tr><td colspan="3" class="debug" style="padding:16px;text-align:center">Aucune donnée.</td></tr>
        <?php else: ?>
          <?php foreach ($exitPages as $i => $r): ?>
            <tr>
              <td style="color:#94a3b8;font-size:12px;font-weight:700"><?= (int)$i + 1 ?></td>
              <td><code title="<?= e($r['page']) ?>"><?= e($r['page']) ?></code></td>
              <td class="right num"><?= number_format((int)$r['total']) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="section">
    <h3>&#128336; 50 dernières pages vues</h3>
    <div style="overflow-x:auto">
    <table class="table">
      <thead>
        <tr><th>Date</th><th>Page</th><th>Pays</th><th>Appareil</th><th>Type</th></tr>
      </thead>
      <tbody>
      <?php if (empty($latestPages)): ?>
        <tr><td colspan="5" class="debug" style="padding:20px;text-align:center">Aucune donnée.</td></tr>
      <?php else: ?>
        <?php foreach ($latestPages as $r): ?>
          <tr>
            <td class="date-mini">
              <?php
                $ts = !empty($r['visited_at']) ? strtotime((string)$r['visited_at']) : 0;
                echo $ts ? date('d/m/Y', $ts) . '<br><span class="h">' . date('H:i', $ts) . '</span>' : '—';
              ?>
            </td>
            <td><code title="<?= e($r['page']) ?>"><?= e($r['page']) ?></code></td>
            <td style="font-size:12px;color:#475569"><?= e((string)($r['country'] ?? '—')) ?></td>
            <td style="font-size:12px;color:#475569"><?= e((string)($r['device']  ?? '—')) ?></td>
            <td>
              <?php if (!empty($r['is_bot'])): ?>
                <span class="badge bot">&#129302; BOT</span>
              <?php else: ?>
                <span class="badge human">&#129489; HUMAIN</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
    </div>
  </div>

  <?php if ($debug): ?>
    <div class="section debug">
      <strong>&#x1F41B; DEBUG</strong><br><br>
      Table: <code><?= e((string)$diag['table']) ?></code><br>
      Col date: <code><?= e((string)$diag['date_col']) ?></code><br>
      Col page: <code><?= e((string)$diag['page_col']) ?></code><br>
      Col bot: <code><?= e((string)($diag['bot_col'] ?? 'NULL')) ?></code><br>
      P&#xE9;riode: <code><?= e((string)$diag['period']) ?></code><br>
      Range: <code><?= e((string)$diag['rangeFrom']) ?></code> &#x2192; <code><?= e((string)$diag['rangeTo']) ?></code><br>
      Query: <code><?= e((string)$diag['q']) ?></code><br>
      Derni&#xE8;re visite: <code><?= e((string)$diag['last_hit']) ?></code><br><br>
      <strong>5 derniers hits :</strong>
      <pre><?php print_r($diag['last_rows']); ?></pre>
    </div>
  <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function(){
  "use strict";
  function el(id){ return document.getElementById(id); }
  function exists(id){ return !!el(id); }

  Chart.defaults.font.family = 'system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Cantarell,Noto Sans';
  Chart.defaults.color = '#334155';

  if (exists('chartDaily')) {
    new Chart(el('chartDaily'), {
      type: 'line',
      data: {
        labels: <?= json_encode($dailyLabels, JSON_UNESCAPED_UNICODE) ?>,
        datasets: [{
          label: 'Visites',
          data: <?= json_encode($dailyValues) ?>,
          borderColor: '#2563eb',
          backgroundColor: 'rgba(37,99,235,.15)',
          borderWidth: 2,
          tension: .35,
          fill: true,
          pointRadius: 2
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false }, tooltip: { mode: 'index', intersect: false } },
        scales: { x: { grid: { display:false } }, y: { beginAtZero: true } }
      }
    });
  }

  if (exists('chartHours')) {
    new Chart(el('chartHours'), {
      type: 'bar',
      data: { labels: <?= json_encode($hourLabels, JSON_UNESCAPED_UNICODE) ?>,
        datasets: [{ data: <?= json_encode($hourValues) ?>, backgroundColor: '#0ea5e9' }]
      },
      options: { responsive:true, plugins:{ legend:{display:false} }, scales:{ x:{grid:{display:false}}, y:{beginAtZero:true} } }
    });
  }

  if (exists('chartWeek')) {
    new Chart(el('chartWeek'), {
      type: 'bar',
      data: { labels: <?= json_encode($weekLabels, JSON_UNESCAPED_UNICODE) ?>,
        datasets: [{ data: <?= json_encode($weekValues) ?>, backgroundColor: '#22c55e' }]
      },
      options: { responsive:true, plugins:{ legend:{display:false} }, scales:{ x:{grid:{display:false}}, y:{beginAtZero:true} } }
    });
  }

  if (exists('chartCountries')) {
    new Chart(el('chartCountries'), {
      type: 'doughnut',
      data: { labels: <?= json_encode($countryLabels, JSON_UNESCAPED_UNICODE) ?>,
        datasets: [{ data: <?= json_encode($countryValues) ?>,
          backgroundColor: ['#2563eb','#16a34a','#f59e0b','#ef4444','#8b5cf6','#0ea5e9','#22c55e','#e11d48','#14b8a6','#6366f1']
        }]
      },
      options:{ responsive:true, plugins:{ legend:{ position:'bottom' } } }
    });
  }

  if (exists('chartDevices')) {
    new Chart(el('chartDevices'), {
      type: 'pie',
      data: { labels: <?= json_encode($devLabels, JSON_UNESCAPED_UNICODE) ?>,
        datasets: [{ data: <?= json_encode($devValues) ?>, backgroundColor:['#0ea5e9','#22c55e','#f59e0b','#ef4444','#8b5cf6'] }]
      },
      options:{ responsive:true, plugins:{ legend:{ position:'bottom' } } }
    });
  }

  if (exists('chartRefs')) {
    new Chart(el('chartRefs'), {
      type: 'bar',
      data: { labels: <?= json_encode($refLabels, JSON_UNESCAPED_UNICODE) ?>,
        datasets: [{ data: <?= json_encode($refValues) ?>, backgroundColor:'#64748b' }]
      },
      options:{ responsive:true, plugins:{ legend:{display:false} }, scales:{ x:{grid:{display:false}}, y:{beginAtZero:true} } }
    });
  }
})();
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layout/layout.php';