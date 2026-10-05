<?php
declare(strict_types=1);

require_once __DIR__ . '/../../admin/_init.php';
require_once __DIR__ . '/../../core/geniuspay.php';
require_once __DIR__ . '/../../core/payment_plan.php';

$activeMenu = 'paiements';
$pdo = Database::connect();
payment_plan_ensure_tables($pdo);

/* ── Filtres ── */
$search    = trim((string)($_GET['q']       ?? ''));
$fStatut   = trim((string)($_GET['statut']  ?? ''));
$fPlan     = trim((string)($_GET['plan']    ?? ''));
$page      = max(1, (int)($_GET['page']     ?? 1));
$perPage   = 25;

$where  = ['1=1'];
$params = [];

if ($search !== '') {
    $where[]  = "(p.etudiant_nom LIKE ? OR p.etudiant_email LIKE ? OR p.etudiant_phone LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($fStatut !== '') {
    $where[]  = "p.statut = ?";
    $params[] = $fStatut;
}
if ($fPlan !== '') {
    $where[]  = "p.plan_type = ?";
    $params[] = $fPlan;
}

$whereSql = implode(' AND ', $where);

$total = (int)$pdo->prepare("SELECT COUNT(*) FROM paiement_plans p WHERE $whereSql")->execute($params) ? null : 0;
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM paiement_plans p WHERE $whereSql");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$pages = max(1, (int)ceil($total / $perPage));
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare("
    SELECT p.*,
           f.titre AS formation_titre,
           (SELECT COALESCE(SUM(pi.montant_net),0) FROM paiements_inscription pi WHERE pi.plan_id=p.id AND pi.statut='paye') AS montant_paye,
           (SELECT COUNT(*) FROM paiements_inscription pi WHERE pi.plan_id=p.id AND pi.statut='paye') AS nb_paiements
    FROM paiement_plans p
    LEFT JOIN formations f ON f.id = p.formation_id
    WHERE $whereSql
    ORDER BY p.created_at DESC
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$plans = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* KPIs */
$kpis = $pdo->query("
    SELECT
        COUNT(*) AS total_plans,
        SUM(montant_total) AS ca_total,
        (SELECT COALESCE(SUM(montant_net),0) FROM paiements_inscription WHERE statut='paye') AS ca_encaisse,
        SUM(CASE WHEN statut='complete' THEN 1 ELSE 0 END) AS nb_complete,
        SUM(CASE WHEN statut='en_cours' THEN 1 ELSE 0 END) AS nb_en_cours
    FROM paiement_plans
")->fetch(PDO::FETCH_ASSOC);

$pageTitle = "Paiements Inscriptions — Admin";
include __DIR__ . '/../partials/header.php';
?>
<style>
.pmt-wrap{max-width:1200px;margin:30px auto;padding:0 16px}
.pmt-kpi{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;margin-bottom:24px}
.pmt-kpi-card{background:#0f172a;border:1px solid rgba(255,255,255,.08);border-radius:12px;padding:14px 18px}
.pmt-kpi-card h4{margin:0;font-size:11px;color:#64748b;text-transform:uppercase;letter-spacing:.06em}
.pmt-kpi-card .val{font-size:1.55rem;font-weight:900;margin:6px 0 0;color:#f5a623}
.pmt-kpi-card .val.green{color:#22c55e}
.pmt-filters{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:16px;align-items:center}
.pmt-filters input,.pmt-filters select{padding:8px 12px;border-radius:8px;border:1px solid rgba(255,255,255,.15);background:#0f172a;color:#e5e7eb;font-size:13px}
.pmt-table{width:100%;border-collapse:collapse;font-size:13px}
.pmt-table th{background:#0f172a;padding:10px 12px;text-align:left;white-space:nowrap;color:#94a3b8;font-size:11px;text-transform:uppercase;letter-spacing:.05em}
.pmt-table td{padding:10px 12px;border-bottom:1px solid rgba(255,255,255,.05)}
.pmt-table tr:hover td{background:rgba(255,255,255,.03)}
.badge{display:inline-block;padding:2px 8px;border-radius:20px;font-size:11px;font-weight:700}
.badge-green{background:rgba(34,197,94,.2);color:#86efac}
.badge-orange{background:rgba(245,166,35,.2);color:#fcd34d}
.badge-red{background:rgba(239,68,68,.2);color:#fca5a5}
.badge-blue{background:rgba(59,130,246,.2);color:#93c5fd}
.prog-bar{background:rgba(255,255,255,.1);border-radius:4px;height:5px;overflow:hidden;margin-top:4px}
.prog-bar div{height:100%;border-radius:4px;background:#22c55e}
.pmt-pager{display:flex;gap:6px;justify-content:center;margin-top:20px}
.pmt-pager a,.pmt-pager span{padding:6px 12px;border-radius:8px;font-size:13px;text-decoration:none;border:1px solid rgba(255,255,255,.12);color:#e5e7eb}
.pmt-pager a:hover{background:rgba(255,255,255,.08)}
.pmt-pager span.active{background:#1e40af;border-color:#1e40af}
</style>

<div class="pmt-wrap">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
    <h1 style="margin:0;font-size:1.4rem;color:#f5a623">💳 Paiements Inscriptions</h1>
    <a href="?<?= http_build_query(array_merge($_GET, ['export' => '1'])); ?>"
       style="padding:8px 16px;background:#1e40af;color:#fff;border-radius:8px;text-decoration:none;font-weight:700;font-size:13px">
      ↓ Exporter CSV
    </a>
  </div>

  <!-- KPIs -->
  <div class="pmt-kpi">
    <div class="pmt-kpi-card">
      <h4>Plans actifs</h4>
      <div class="val"><?= number_format((int)($kpis['total_plans'] ?? 0), 0, ',', ' '); ?></div>
    </div>
    <div class="pmt-kpi-card">
      <h4>CA total attendu</h4>
      <div class="val"><?= number_format((int)($kpis['ca_total'] ?? 0), 0, ',', ' '); ?> <small style="font-size:.6em">FCFA</small></div>
    </div>
    <div class="pmt-kpi-card">
      <h4>CA encaissé</h4>
      <div class="val green"><?= number_format((int)($kpis['ca_encaisse'] ?? 0), 0, ',', ' '); ?> <small style="font-size:.6em">FCFA</small></div>
    </div>
    <div class="pmt-kpi-card">
      <h4>Plans complets</h4>
      <div class="val green"><?= (int)($kpis['nb_complete'] ?? 0); ?></div>
    </div>
    <div class="pmt-kpi-card">
      <h4>En cours</h4>
      <div class="val"><?= (int)($kpis['nb_en_cours'] ?? 0); ?></div>
    </div>
  </div>

  <!-- Filtres -->
  <form method="get" class="pmt-filters">
    <input name="q" placeholder="Recherche nom, email, tél…" value="<?= e($search); ?>">
    <select name="statut">
      <option value="">Tous statuts</option>
      <?php foreach (['en_cours' => 'En cours', 'complete' => 'Complet', 'abandonne' => 'Abandonné'] as $v => $l): ?>
        <option value="<?= $v; ?>" <?= $fStatut === $v ? 'selected' : ''; ?>><?= $l; ?></option>
      <?php endforeach; ?>
    </select>
    <select name="plan">
      <option value="">Tous plans</option>
      <?php foreach (['unique' => 'Paiement unique', '2tranches' => '2 tranches', '3tranches' => '3 tranches'] as $v => $l): ?>
        <option value="<?= $v; ?>" <?= $fPlan === $v ? 'selected' : ''; ?>><?= $l; ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" style="padding:8px 14px;border-radius:8px;background:#1e40af;color:#fff;border:0;cursor:pointer;font-weight:700">Filtrer</button>
    <?php if ($search || $fStatut || $fPlan): ?>
      <a href="?" style="padding:8px 14px;border-radius:8px;border:1px solid rgba(255,255,255,.15);color:#94a3b8;text-decoration:none">✕ Reset</a>
    <?php endif; ?>
  </form>

  <!-- Tableau -->
  <div style="overflow-x:auto;background:#0b1220;border-radius:14px;border:1px solid rgba(255,255,255,.08)">
    <table class="pmt-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Participant</th>
          <th>Formation</th>
          <th>Plan</th>
          <th>Montant total</th>
          <th>Payé</th>
          <th>Solde</th>
          <th>Avancement</th>
          <th>Statut</th>
          <th>Date</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($plans)): ?>
        <tr><td colspan="11" style="text-align:center;padding:30px;color:#64748b">Aucun résultat</td></tr>
        <?php else: ?>
        <?php foreach ($plans as $p):
            $mt = (int)$p['montant_total'];
            $mp = (int)($p['montant_paye'] ?? 0);
            $pct = $mt > 0 ? min(100, round($mp / $mt * 100)) : 0;
            $solde = max(0, $mt - $mp);
            $badgeClass = match ($p['statut']) {
                'complete'  => 'badge-green',
                'abandonne' => 'badge-red',
                default     => 'badge-orange',
            };
            $badgeLabel = match ($p['statut']) {
                'complete'  => 'Complet',
                'abandonne' => 'Abandonné',
                default     => 'En cours',
            };
        ?>
        <tr>
          <td style="color:#64748b"><?= (int)$p['id']; ?></td>
          <td>
            <b><?= e($p['etudiant_nom']); ?></b><br>
            <span style="color:#64748b;font-size:11px"><?= e($p['etudiant_email']); ?></span><br>
            <span style="color:#64748b;font-size:11px"><?= e($p['etudiant_phone']); ?></span>
          </td>
          <td style="max-width:200px;font-size:12px"><?= e($p['formation_titre'] ?? '—'); ?></td>
          <td><span class="badge badge-blue"><?= e(payment_plan_label($p['plan_type'])); ?></span></td>
          <td><b><?= number_format($mt, 0, ',', ' '); ?></b> <span style="color:#64748b;font-size:11px">FCFA</span></td>
          <td style="color:#22c55e"><b><?= number_format($mp, 0, ',', ' '); ?></b> <span style="font-size:11px">FCFA</span></td>
          <td style="color:<?= $solde > 0 ? '#f5a623' : '#22c55e'; ?>">
            <b><?= $solde > 0 ? number_format($solde, 0, ',', ' ') . ' FCFA' : '✓'; ?></b>
          </td>
          <td style="min-width:80px">
            <div style="font-size:12px;color:#94a3b8;margin-bottom:3px"><?= $pct; ?> %</div>
            <div class="prog-bar">
              <div style="width:<?= $pct; ?>%;background:<?= $pct >= 100 ? '#22c55e' : '#f5a623'; ?>"></div>
            </div>
          </td>
          <td><span class="badge <?= $badgeClass; ?>"><?= $badgeLabel; ?></span></td>
          <td style="white-space:nowrap;color:#64748b;font-size:12px"><?= date('d/m/Y', strtotime($p['created_at'])); ?></td>
          <td style="white-space:nowrap">
            <a href="detail.php?id=<?= (int)$p['id']; ?>"
               style="padding:5px 10px;border-radius:6px;background:#1e3a5f;color:#93c5fd;text-decoration:none;font-size:12px;font-weight:700">
              Détail
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <?php if ($pages > 1): ?>
  <div class="pmt-pager">
    <?php for ($i = 1; $i <= $pages; $i++): ?>
      <?php $q = http_build_query(array_merge($_GET, ['page' => $i])); ?>
      <?php if ($i === $page): ?>
        <span class="active"><?= $i; ?></span>
      <?php else: ?>
        <a href="?<?= $q; ?>"><?= $i; ?></a>
      <?php endif; ?>
    <?php endfor; ?>
  </div>
  <?php endif; ?>

</div>

<?php include __DIR__ . '/../partials/footer.php'; ?>
