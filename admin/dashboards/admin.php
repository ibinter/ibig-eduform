<?php
declare(strict_types=1);

/* =====================================================
   DASHBOARD — ADMIN
===================================================== */

$pageTitle  = 'Tableau de bord administrateur';
$activeMenu = 'dashboard';

$pdo = Database::connect();
$u2  = function_exists('auth_user') ? (auth_user() ?? []) : [];
$prenomAdmin = trim((string)($u2['first_name'] ?? '')) ?: 'Admin';

/* =====================================================
   KPIs
===================================================== */
$nbFormations = (int)$pdo->query("SELECT COUNT(*) FROM formations WHERE statut='active'")->fetchColumn();

$nbPreinsc = 0; $nbPreinscToday = 0; $nbPreinscNouvelles = 0;
try {
  $k = $pdo->query("
    SELECT COUNT(*) AS total,
           SUM(CASE WHEN DATE(created_at)=CURDATE() THEN 1 ELSE 0 END) AS today,
           SUM(CASE WHEN LOWER(COALESCE(statut,'')) NOT IN ('traitee','traitée','confirme','confirmee','valide','rejete','rejetee','rejeté','rejetée','refuse','refusee') THEN 1 ELSE 0 END) AS nouvelles
    FROM preinscriptions
  ")->fetch(PDO::FETCH_ASSOC) ?: [];
  $nbPreinsc          = (int)($k['total'] ?? 0);
  $nbPreinscToday     = (int)($k['today'] ?? 0);
  $nbPreinscNouvelles = (int)($k['nouvelles'] ?? 0);
} catch (Throwable $e) {}

$nbDemandesNouvelles = 0;
try {
  $nbDemandesNouvelles = (int)$pdo->query("SELECT COUNT(*) FROM demandes_formation WHERE statut='nouvelle'")->fetchColumn();
} catch (Throwable $e) {}

$preinscParNiveau = ['debutant' => 0, 'intermediaire' => 0, 'expert' => 0];
try {
  $kpn = $pdo->query("SELECT COALESCE(niveau,'') AS niv, COUNT(*) AS nb FROM preinscriptions GROUP BY niveau")->fetchAll(PDO::FETCH_ASSOC);
  foreach ($kpn as $row) { $nv = strtolower(trim((string)$row['niv'])); if (isset($preinscParNiveau[$nv])) $preinscParNiveau[$nv] += (int)$row['nb']; }
} catch (Throwable $e) {}

$topFormations = [];
try {
  $topFormations = $pdo->query("SELECT f.titre, COUNT(p.id) AS nb FROM preinscriptions p JOIN formations f ON f.id=p.formation_id GROUP BY p.formation_id ORDER BY nb DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}

/* Inscriptions par semaine sur les 8 dernières semaines */
$inscrSemaines = [];
try {
  $rows = $pdo->query("
    SELECT YEARWEEK(created_at, 1) AS yw,
           DATE_FORMAT(MIN(created_at), '%d/%m') AS label,
           COUNT(*) AS nb
    FROM preinscriptions
    WHERE created_at >= NOW() - INTERVAL 8 WEEK
    GROUP BY yw ORDER BY yw ASC
  ")->fetchAll(PDO::FETCH_ASSOC);
  foreach ($rows as $r) { $inscrSemaines[] = ['label' => $r['label'], 'nb' => (int)$r['nb']]; }
} catch (Throwable $e) {}

/* Répartition statuts inscriptions */
$statutsChart = ['nouvelle' => 0, 'traitee' => 0, 'confirme' => 0, 'rejete' => 0];
try {
  $rs = $pdo->query("SELECT statut, COUNT(*) AS nb FROM preinscriptions GROUP BY statut")->fetchAll(PDO::FETCH_ASSOC);
  foreach ($rs as $r) {
    $s = strtolower((string)$r['statut']);
    if (in_array($s, ['traitee','traitée'], true)) $statutsChart['traitee'] += (int)$r['nb'];
    elseif (in_array($s, ['confirme','confirmee','valide','inscrit'], true)) $statutsChart['confirme'] += (int)$r['nb'];
    elseif (in_array($s, ['rejete','rejetee','rejeté','refuse','refusee'], true)) $statutsChart['rejete'] += (int)$r['nb'];
    else $statutsChart['nouvelle'] += (int)$r['nb'];
  }
} catch (Throwable $e) {}

/* Moyenne notes satisfaction */
$satStats = ['nb' => 0, 'avg' => 0, 'recommande_pct' => 0];
try {
  $ss = $pdo->query("
    SELECT COUNT(*) AS nb,
           ROUND(AVG(note_globale),1) AS avg_note,
           ROUND(SUM(CASE WHEN recommande=1 THEN 1 ELSE 0 END)*100.0/NULLIF(COUNT(CASE WHEN recommande IS NOT NULL THEN 1 END),0),0) AS recommande_pct
    FROM satisfaction_reponses
  ")->fetch(PDO::FETCH_ASSOC);
  if ($ss && (int)$ss['nb'] > 0) {
    $satStats = ['nb' => (int)$ss['nb'], 'avg' => (float)($ss['avg_note'] ?? 0), 'recommande_pct' => (int)($ss['recommande_pct'] ?? 0)];
  }
} catch (Throwable $e) {}

/* =====================================================
   LISTES
===================================================== */
$lastPreinsc = [];
try {
  $lastPreinsc = $pdo->query("
    SELECT p.id, p.nom, p.prenoms, p.statut, p.created_at, f.titre AS formation
    FROM preinscriptions p
    LEFT JOIN formations f ON f.id = p.formation_id
    ORDER BY p.id DESC LIMIT 8
  ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}

ob_start();
?>
<style>
.da-hello{font-size:20px;font-weight:800;margin:0 0 2px;color:#0f172a}
.da-sub{color:#6b7280;font-size:13px;margin:0 0 20px}
.da-kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:22px}
.da-kpi{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:18px 16px;box-shadow:0 4px 14px rgba(15,23,42,.06);display:flex;flex-direction:column;gap:3px}
.da-kpi b{font-size:28px;font-weight:900;line-height:1;color:#0f172a}
.da-kpi span{font-size:11px;color:#6b7280;font-weight:700;text-transform:uppercase;letter-spacing:.3px}
.da-kpi .hint{font-size:11px;color:#94a3b8;margin-top:1px}
.da-kpi.k-blue  {border-top:3px solid #3b82f6}.da-kpi.k-blue   b{color:#1d4ed8}
.da-kpi.k-amber {border-top:3px solid #f59e0b}.da-kpi.k-amber  b{color:#b45309}
.da-kpi.k-green {border-top:3px solid #22c55e}.da-kpi.k-green  b{color:#16a34a}
.da-kpi.k-orange{border-top:3px solid #f97316}.da-kpi.k-orange b{color:#c2410c}

.da-grid{display:grid;grid-template-columns:1.3fr .7fr;gap:18px}
.da-card{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:18px;box-shadow:0 2px 8px rgba(15,23,42,.04)}
.da-card-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:14px}
.da-card-head h3{margin:0;font-size:15px;font-weight:800;color:#0f172a}
.da-card-head a{font-size:12px;font-weight:700;color:#1f3fe0;text-decoration:none}
.da-card-head a:hover{text-decoration:underline}

.da-table{width:100%;border-collapse:collapse;font-size:13px}
.da-table th{font-size:11px;text-transform:uppercase;letter-spacing:.3px;color:#6b7280;text-align:left;padding:8px 10px;border-bottom:2px solid #eef2f7;background:#fafbff}
.da-table td{padding:9px 10px;border-bottom:1px solid #f1f5f9;vertical-align:middle}
.da-table tr:hover td{background:#f8faff}

.da-list{list-style:none;margin:0;padding:0}
.da-list li{display:flex;justify-content:space-between;align-items:flex-start;gap:10px;padding:10px 0;border-bottom:1px solid #f1f5f9}
.da-list li:last-child{border-bottom:0}
.da-list .lbl{font-weight:700;font-size:13px;color:#0f172a}
.da-list .sub{font-size:11.5px;color:#6b7280;margin-top:2px}
.da-list .ts{font-size:11.5px;color:#94a3b8;white-space:nowrap;font-weight:600}

.pill{padding:3px 9px;border-radius:999px;font-size:10.5px;font-weight:800;white-space:nowrap;display:inline-flex;align-items:center;gap:4px}
.pill::before{content:'';width:5px;height:5px;border-radius:50%;background:currentColor;display:inline-block}
.pill.ok    {background:#dcfce7;color:#166534}
.pill.wait  {background:#fff7ed;color:#9a3412}
.pill.rejete{background:#fee2e2;color:#991b1b}
.chip-sp{display:inline-block;font-size:10px;font-weight:800;color:#1f3fe0;background:rgba(31,63,224,.08);border:1px solid rgba(31,63,224,.2);border-radius:999px;padding:1px 7px;margin-left:5px}
.alert-banner{background:#fff7ed;border:1px solid #fed7aa;border-radius:12px;padding:10px 16px;font-size:13px;font-weight:700;color:#9a3412;display:flex;align-items:center;gap:8px;margin-bottom:18px}

@media(max-width:900px){.da-kpis{grid-template-columns:1fr 1fr}.da-grid{grid-template-columns:1fr}}
</style>

<p class="da-hello">Bonjour <?= e($prenomAdmin); ?> &#128075;</p>
<p class="da-sub">Vue d'ensemble &mdash; <?= date('l d F Y'); ?></p>

<?php if ($nbPreinscNouvelles > 0 || $nbDemandesNouvelles > 0): ?>
<div class="alert-banner">
  &#9888;&#65039;
  <?php if ($nbPreinscNouvelles > 0): ?><?= $nbPreinscNouvelles; ?> préinscription(s) en attente<?php endif; ?>
  <?php if ($nbPreinscNouvelles > 0 && $nbDemandesNouvelles > 0): ?> &nbsp;&middot;&nbsp; <?php endif; ?>
  <?php if ($nbDemandesNouvelles > 0): ?><?= $nbDemandesNouvelles; ?> demande(s) de formation sans réponse<?php endif; ?>
</div>
<?php endif; ?>

<!-- KPIs -->
<div class="da-kpis">
  <div class="da-kpi k-blue">
    <b><?= $nbPreinsc; ?></b>
    <span>&#128221; Préinscriptions</span>
    <div class="hint">Aujourd'hui : <strong><?= $nbPreinscToday; ?></strong> &nbsp;·&nbsp; En attente : <strong><?= $nbPreinscNouvelles; ?></strong></div>
  </div>
  <div class="da-kpi k-orange">
    <b><?= $nbDemandesNouvelles; ?></b>
    <span>&#127891; Demandes nouvelles</span>
    <div class="hint">Demandes sans réponse</div>
  </div>
  <div class="da-kpi k-green">
    <b><?= $nbFormations; ?></b>
    <span>&#128196; Formations actives</span>
    <div class="hint">Dans le catalogue</div>
  </div>
  <div class="da-kpi k-amber">
    <b><?= $preinscParNiveau['debutant'] + $preinscParNiveau['intermediaire'] + $preinscParNiveau['expert']; ?></b>
    <span>&#127891; Avec niveau</span>
    <div class="hint">🟢 <?= $preinscParNiveau['debutant']; ?> · 🔵 <?= $preinscParNiveau['intermediaire']; ?> · 🔴 <?= $preinscParNiveau['expert']; ?></div>
  </div>
</div>

<!-- GRILLE -->
<div class="da-grid">

  <!-- PRÉINSCRIPTIONS -->
  <div class="da-card">
    <div class="da-card-head">
      <h3>&#128221; Préinscriptions récentes</h3>
      <a href="/admin/preinscriptions/index.php">Tout voir &rarr;</a>
    </div>
    <?php if (!$lastPreinsc): ?>
      <p style="color:#94a3b8;font-size:13px">Aucune préinscription.</p>
    <?php else: ?>
    <table class="da-table">
      <thead><tr><th>Candidat</th><th>Formation</th><th>Statut</th><th>Date</th></tr></thead>
      <tbody>
      <?php foreach ($lastPreinsc as $p):
        $nom = trim((string)($p['prenoms'] ?? '') . ' ' . (string)($p['nom'] ?? '')) ?: '—';
        $stl = strtolower((string)($p['statut'] ?? ''));
        $cls = in_array($stl, ['traitee','traitée','confirme','confirmee','valide'], true) ? 'ok'
             : (in_array($stl, ['rejete','rejetee','rejeté','rejetée','refuse','refusee'], true) ? 'rejete' : 'wait');
        $lbl = match(true){
          in_array($stl,['traitee','traitée'])=>'Traitée',
          in_array($stl,['confirme','confirmee','valide'])=>'Confirmée',
          in_array($stl,['rejete','rejetee','rejeté','rejetée','refuse','refusee'])=>'Rejetée',
          default=>'Nouvelle'
        };
        $isToday = !empty($p['created_at']) && date('Y-m-d',strtotime((string)$p['created_at']))=== date('Y-m-d');
      ?>
        <tr <?= $isToday ? 'style="background:#fffbeb"' : ''; ?>>
          <td>
            <strong><?= e($nom); ?></strong>
            <?php if ($isToday): ?><span style="font-size:10px;font-weight:800;background:#fde68a;color:#92400e;border-radius:5px;padding:1px 5px;margin-left:4px">AUJOURD'HUI</span><?php endif; ?>
          </td>
          <td style="color:#64748b;font-size:12px;max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= e($p['formation'] ?: '—'); ?></td>
          <td><span class="pill <?= $cls; ?>"><?= $lbl; ?></span></td>
          <td style="color:#94a3b8;font-size:12px;white-space:nowrap"><?= !empty($p['created_at']) ? date('d/m H:i', strtotime((string)$p['created_at'])) : '—'; ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <a href="/admin/preinscriptions/index.php" style="display:inline-block;margin-top:14px;padding:8px 16px;background:#1f3fe0;color:#fff;border-radius:10px;font-weight:800;font-size:13px;text-decoration:none">&#128221; Gérer les préinscriptions</a>
    <?php endif; ?>
  </div>

  <!-- TOP FORMATIONS & NIVEAUX -->
  <div class="da-card">
    <div class="da-card-head">
      <h3>&#127891; Par niveau &amp; Top formations</h3>
      <a href="/admin/preinscriptions/index.php?niveau=debutant">Filtrer &rarr;</a>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px">
      <div style="background:#dcfce7;border:1px solid #86efac;border-radius:9px;padding:8px 14px;text-align:center;min-width:70px">
        <div style="font-size:18px;font-weight:900;color:#166534"><?= $preinscParNiveau['debutant']; ?></div>
        <div style="font-size:10px;font-weight:700;color:#166534">🟢 Débutant</div>
      </div>
      <div style="background:#dbeafe;border:1px solid #93c5fd;border-radius:9px;padding:8px 14px;text-align:center;min-width:70px">
        <div style="font-size:18px;font-weight:900;color:#1e40af"><?= $preinscParNiveau['intermediaire']; ?></div>
        <div style="font-size:10px;font-weight:700;color:#1e40af">🔵 Intermédiaire</div>
      </div>
      <div style="background:#fce7f3;border:1px solid #f9a8d4;border-radius:9px;padding:8px 14px;text-align:center;min-width:70px">
        <div style="font-size:18px;font-weight:900;color:#9d174d"><?= $preinscParNiveau['expert']; ?></div>
        <div style="font-size:10px;font-weight:700;color:#9d174d">🔴 Expert</div>
      </div>
    </div>
    <?php $topMax = $topFormations ? (int)$topFormations[0]['nb'] : 1; ?>
    <?php foreach ($topFormations as $tf): ?>
      <div style="display:flex;align-items:center;gap:8px;padding:6px 0;border-bottom:1px solid #f1f5f9">
        <div style="flex:1;overflow:hidden">
          <div style="font-size:12px;font-weight:700;color:#1e293b;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= e($tf['titre']); ?></div>
          <div style="height:5px;background:#e2e8f0;border-radius:3px;margin-top:3px"><div style="height:5px;background:linear-gradient(90deg,#3b82f6,#1d4ed8);border-radius:3px;width:<?= $topMax > 0 ? round((int)$tf['nb'] / $topMax * 100) : 0; ?>%"></div></div>
        </div>
        <span style="font-size:12px;font-weight:900;color:#1d4ed8;min-width:24px;text-align:right"><?= (int)$tf['nb']; ?></span>
      </div>
    <?php endforeach; ?>
    <div style="margin-top:12px">
      <a href="/admin/demandes-formation/index.php" style="padding:7px 14px;background:#f97316;color:#fff;border-radius:9px;font-weight:800;font-size:12px;text-decoration:none">
        &#127891; Demandes (<?= $nbDemandesNouvelles; ?> nouvelles)
      </a>
    </div>
  </div>

</div>

<!-- GRAPHIQUES -->
<div style="display:grid;grid-template-columns:1.5fr 1fr;gap:18px;margin-top:20px">

  <!-- Inscriptions / semaine -->
  <div class="da-card">
    <div class="da-card-head">
      <h3>📈 Inscriptions (8 dernières semaines)</h3>
    </div>
    <?php if ($inscrSemaines): ?>
    <canvas id="chartSemaines" height="120"></canvas>
    <?php else: ?>
    <p style="color:#94a3b8;font-size:13px">Pas encore de données.</p>
    <?php endif; ?>
  </div>

  <!-- Répartition statuts -->
  <div class="da-card">
    <div class="da-card-head">
      <h3>🥧 Statuts des inscriptions</h3>
    </div>
    <canvas id="chartStatuts" height="160"></canvas>
  </div>

</div>

<!-- Satisfaction -->
<?php if ($satStats['nb'] > 0): ?>
<div class="da-card" style="margin-top:18px">
  <div class="da-card-head">
    <h3>⭐ Satisfaction apprenants</h3>
    <a href="/admin/satisfaction/index.php">Tout voir →</a>
  </div>
  <div style="display:flex;gap:24px;flex-wrap:wrap;align-items:center">
    <div style="text-align:center;min-width:80px">
      <div style="font-size:2.5rem;font-weight:900;color:#f59e0b"><?= number_format($satStats['avg'], 1); ?></div>
      <div style="font-size:11px;font-weight:700;color:#6b7280">Note / 5</div>
      <div style="font-size:11px;color:#94a3b8"><?= $satStats['nb']; ?> avis</div>
    </div>
    <div style="flex:1;min-width:200px">
      <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px">
        <div style="width:120px;height:10px;background:#e5e7eb;border-radius:5px;overflow:hidden">
          <div style="height:10px;background:#f59e0b;width:<?= min(100, (int)round($satStats['avg']/5*100)); ?>%;border-radius:5px"></div>
        </div>
        <span style="font-size:12px;color:#6b7280"><?= min(100, (int)round($satStats['avg']/5*100)); ?>% de satisfaction</span>
      </div>
      <div style="font-size:13px;color:#16a34a;font-weight:700">
        👍 <?= $satStats['recommande_pct']; ?>% recommandent IBIG EDUFORM
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
<?php if ($inscrSemaines): ?>
new Chart(document.getElementById('chartSemaines'), {
  type: 'bar',
  data: {
    labels: <?= json_encode(array_column($inscrSemaines, 'label')); ?>,
    datasets: [{
      label: 'Inscriptions',
      data: <?= json_encode(array_column($inscrSemaines, 'nb')); ?>,
      backgroundColor: 'rgba(31,63,224,.7)',
      borderRadius: 6,
      borderSkipped: false,
    }]
  },
  options: {
    responsive: true,
    plugins: { legend: { display: false } },
    scales: {
      y: { beginAtZero: true, ticks: { stepSize: 1, font: { size: 11 } }, grid: { color: '#f1f5f9' } },
      x: { ticks: { font: { size: 11 } }, grid: { display: false } }
    }
  }
});
<?php endif; ?>

new Chart(document.getElementById('chartStatuts'), {
  type: 'doughnut',
  data: {
    labels: ['Nouvelle', 'Traitée', 'Confirmée', 'Rejetée'],
    datasets: [{
      data: [
        <?= (int)$statutsChart['nouvelle']; ?>,
        <?= (int)$statutsChart['traitee']; ?>,
        <?= (int)$statutsChart['confirme']; ?>,
        <?= (int)$statutsChart['rejete']; ?>
      ],
      backgroundColor: ['#3b82f6', '#f59e0b', '#22c55e', '#ef4444'],
      borderWidth: 2,
      borderColor: '#fff',
    }]
  },
  options: {
    responsive: true,
    cutout: '60%',
    plugins: {
      legend: { position: 'bottom', labels: { font: { size: 11 }, padding: 10 } }
    }
  }
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout/layout.php';
