<?php
declare(strict_types=1);

/**
 * ============================================================
 * ADMIN — EMPLOIS — IMPACT & STATISTIQUES (ULTRA COMPACT UI)
 * Fichier : /admin/emplois/impact.php
 * ============================================================
 */

require_once __DIR__ . '/../_init.php';

/* 🔐 Middleware */
$mw = realpath(__DIR__ . '/../auth/middleware.php');
if (!$mw) {
    die("Middleware introuvable");
}
require_once $mw;

if (!class_exists('Middleware')) {
    die("Classe Middleware introuvable");
}

Middleware::requireAuth();

/* =========================
   META
========================= */
$pageTitle  = "Impact & Statistiques";
$activeMenu = "emplois_impact";

/* =========================
   DB
========================= */
$pdo = Database::connect();

/* =========================
   FILTRES
========================= */
$start = $_GET['start'] ?? '';
$end   = $_GET['end']   ?? '';
$type  = $_GET['type_contrat'] ?? '';

$where  = "1=1";
$params = [];

if ($start !== '') {
    $where   .= " AND i.created_at >= ?";
    $params[] = $start . " 00:00:00";
}
if ($end !== '') {
    $where   .= " AND i.created_at <= ?";
    $params[] = $end . " 23:59:59";
}
if ($type !== '') {
    $where   .= " AND o.type_contrat = ?";
    $params[] = $type;
}

/* =========================
   KPI
========================= */
$stmt = $pdo->prepare("
  SELECT
    COUNT(i.id)                                          AS insertions,
    SUM(i.statut = 'place')                             AS places,
    SUM(i.statut IN ('propose','entretien','en_suivi')) AS en_suivi,
    SUM(i.statut = 'termine')                           AS termine,
    SUM(i.statut = 'rupture')                           AS rupture
  FROM insertions i
  LEFT JOIN offres_emploi o ON o.id = i.offre_id
  WHERE $where
");
$stmt->execute($params);
$k = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

$tauxPlacement = ($k['insertions'] ?? 0) > 0
  ? round(($k['places'] / $k['insertions']) * 100, 1)
  : 0;

/* =========================
   PAR TYPE CONTRAT
========================= */
$stmt = $pdo->prepare("
  SELECT
    COALESCE(o.type_contrat,'Non défini') AS type_contrat,
    COUNT(i.id) AS total
  FROM insertions i
  LEFT JOIN offres_emploi o ON o.id = i.offre_id
  WHERE $where
  GROUP BY type_contrat
  ORDER BY total DESC
");
$stmt->execute($params);
$types = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   RENDER
========================= */
ob_start();
?>

<style>
/* =========================================================
   IMPACT — ULTRA COMPACT ADMIN UI
========================================================= */

.kpi-grid{
  display:grid;
  grid-template-columns:repeat(auto-fit,minmax(160px,1fr));
  gap:12px;
  margin:16px 0;
}

.kpi{
  border:1px solid #e5e7eb;
  border-radius:14px;
  padding:12px;
}

.kpi .label{
  font-size:12px;
  color:#6b7280;
}
.kpi .value{
  font-size:20px;
  font-weight:600;
  margin-top:4px;
}

.kpi.rate{
  background:#f8fafc;
}

.filters{
  display:flex;
  gap:10px;
  flex-wrap:wrap;
  align-items:end;
}

.filters .input{
  height:38px;
  padding:0 12px;
  border-radius:10px;
  border:1px solid #e5e7eb;
  font-size:14px;
}

/* TABLE COMPACT */
table{
  width:100%;
  font-size:14px;
  margin-top:12px;
}

th,td{
  padding:10px 8px;
  vertical-align:middle;
  white-space:nowrap;
}

th{
  font-size:12px;
  color:#6b7280;
  text-transform:uppercase;
}

</style>

<div class="card">

  <!-- HEADER -->
  <div style="display:flex;justify-content:space-between;align-items:center">
    <h2>📊 Impact & Insertions</h2>

    <a class="btn btn-outline"
       href="impact_export_excel.php?<?= http_build_query($_GET); ?>">
      📤 Export Excel
    </a>
  </div>

  <!-- FILTRES -->
  <form method="get" class="filters" style="margin-top:16px">
    <input class="input" type="date" name="start" value="<?= e($start); ?>">
    <input class="input" type="date" name="end" value="<?= e($end); ?>">

    <select class="input" name="type_contrat">
      <option value="">Type contrat</option>
      <?php foreach (['CDI','CDD','Stage','Mission','Freelance'] as $tc): ?>
        <option value="<?= $tc ?>" <?= $type===$tc?'selected':'' ?>>
          <?= $tc ?>
        </option>
      <?php endforeach; ?>
    </select>

    <button class="btn btn-primary">Filtrer</button>
  </form>

  <!-- KPI -->
  <div class="kpi-grid">

    <div class="kpi">
      <div class="label">Insertions</div>
      <div class="value"><?= (int)($k['insertions'] ?? 0); ?></div>
    </div>

    <div class="kpi">
      <div class="label">Placements</div>
      <div class="value"><?= (int)($k['places'] ?? 0); ?></div>
    </div>

    <div class="kpi">
      <div class="label">En suivi</div>
      <div class="value"><?= (int)($k['en_suivi'] ?? 0); ?></div>
    </div>

    <div class="kpi">
      <div class="label">Terminés</div>
      <div class="value"><?= (int)($k['termine'] ?? 0); ?></div>
    </div>

    <div class="kpi">
      <div class="label">Ruptures</div>
      <div class="value"><?= (int)($k['rupture'] ?? 0); ?></div>
    </div>

    <div class="kpi rate">
      <div class="label">Taux placement</div>
      <div class="value"><?= $tauxPlacement ?> %</div>
    </div>

  </div>

  <!-- TABLE -->
  <h3 style="margin-top:10px">Impact par type de contrat</h3>

  <table>
    <thead>
      <tr>
        <th>Type contrat</th>
        <th>Insertions</th>
      </tr>
    </thead>
    <tbody>

    <?php if (!$types): ?>
      <tr>
        <td colspan="2" class="muted">Aucune donnée.</td>
      </tr>
    <?php else: foreach ($types as $t): ?>
      <tr>
        <td><?= e($t['type_contrat']); ?></td>
        <td><strong><?= (int)$t['total']; ?></strong></td>
      </tr>
    <?php endforeach; endif; ?>

    </tbody>
  </table>

</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout/layout.php';