<?php
declare(strict_types=1);
require_once __DIR__ . '/_auth.php';

/* ============================
   STATS
============================ */
$stats = ['total'=>0,'recu'=>0,'en_cours'=>0,'retenu'=>0,'rejete'=>0];

$q = $pdo->prepare("
  SELECT
    COUNT(*) AS total,
    SUM(statut='recu') AS recu,
    SUM(statut='en_cours') AS en_cours,
    SUM(statut='retenu') AS retenu,
    SUM(statut='rejete') AS rejete
  FROM candidatures
  WHERE $candWhereSQL
");
$q->execute($candWhereBind);
$data = $q->fetch(PDO::FETCH_ASSOC) ?: [];
foreach ($stats as $k => $v) {
  $stats[$k] = (int)($data[$k] ?? 0);
}

/* ============================
   10 DERNIÈRES CANDIDATURES
============================ */
$list = $pdo->prepare("
  SELECT c.id, o.titre, c.statut, c.created_at
  FROM candidatures c
  LEFT JOIN offres_emploi o ON o.id = c.offre_id
  WHERE $candWhereSQL
  ORDER BY c.created_at DESC
  LIMIT 10
");
$list->execute($candWhereBind);
$candidatures = $list->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Dashboard Candidat – IBIG EDUFORM</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<style>
:root{
  --blue:#0b5ed7;
  --dark:#1e293b;
}
body{margin:0;font-family:Inter,Arial;background:#f1f5f9;color:#111827}
.header{
  background:linear-gradient(90deg,#0b5ed7,#1d4ed8);
  color:#fff;padding:18px 26px;
  display:flex;justify-content:space-between;align-items:center
}
.header a{color:#fff;text-decoration:none;font-weight:700}
.container{max-width:1200px;margin:28px auto;padding:0 20px}

.nav{display:flex;gap:10px;flex-wrap:wrap;margin:14px 0 22px}
.btn{
  padding:10px 14px;border-radius:10px;
  background:var(--blue);color:#fff;
  text-decoration:none;font-size:13px;font-weight:700;
  transition:.2s
}
.btn.alt{background:#475569}
.btn:hover{opacity:.9;transform:translateY(-1px)}

.cards{
  display:grid;
  grid-template-columns:repeat(auto-fit,minmax(200px,1fr));
  gap:18px
}
.card{
  background:#fff;border-radius:16px;padding:20px;
  box-shadow:0 10px 30px rgba(0,0,0,.08);
  transition:.25s
}
.card:hover{transform:translateY(-3px)}
.card h3{margin:0;font-size:13px;color:#64748b}
.card p{
  font-size:30px;font-weight:900;
  margin:10px 0 0;color:var(--dark)
}

.grid{
  display:grid;
  grid-template-columns:2fr 1fr;
  gap:20px;
  margin-top:26px
}
@media(max-width:900px){.grid{grid-template-columns:1fr}}

.table{
  background:#fff;border-radius:16px;
  box-shadow:0 10px 30px rgba(0,0,0,.08);
  overflow:hidden
}
table{width:100%;border-collapse:collapse}
th,td{
  padding:14px;border-bottom:1px solid #e5e7eb;
  font-size:14px
}
th{background:#f8fafc;text-align:left}
tbody tr:hover{background:#f9fafb}

.badge{
  padding:4px 10px;border-radius:999px;
  font-size:11px;font-weight:900;
  text-transform:uppercase
}
.recu{background:#e0f2fe;color:#0369a1}
.en_cours{background:#fef3c7;color:#92400e}
.retenu{background:#dcfce7;color:#166534}
.rejete{background:#fee2e2;color:#991b1b}

.profile{
  margin-top:26px;background:#fff;
  border-radius:16px;padding:20px;
  box-shadow:0 10px 30px rgba(0,0,0,.08)
}
.small{color:#64748b;font-size:13px}

.search{
  padding:10px;border:1px solid #cbd5e1;
  border-radius:10px;width:100%;margin-bottom:10px
}
.avatar{
  width:42px;height:42px;border-radius:999px;
  object-fit:cover;border:2px solid rgba(255,255,255,.6);
  margin-right:10px
}
.header-left{display:flex;align-items:center}
</style>
</head>
<body>

<div class="header">
  <div class="header-left">
    <img class="avatar"
         src="<?= e($candidat['avatar'] ?: '/assets/images/logo.png'); ?>"
         alt="Avatar">
    <div>IBIG EDUFORM — Espace Candidat</div>
  </div>
  <div><?= e($displayName); ?> | <a href="logout.php">Déconnexion</a></div>
</div>

<div class="container">

  <!-- NAV -->
  <div class="nav">
    <a class="btn" href="dashboard.php">Dashboard</a>
    <a class="btn alt" href="candidatures.php">Mes candidatures</a>
    <a class="btn alt" href="profil.php">Mon profil</a>
    <a class="btn alt" href="password.php">Mot de passe</a>
  </div>

  <!-- KPI -->
  <div class="cards">
    <div class="card"><h3>Total candidatures</h3><p data-count="<?= $stats['total']; ?>">0</p></div>
    <div class="card"><h3>Reçues</h3><p data-count="<?= $stats['recu']; ?>">0</p></div>
    <div class="card"><h3>En cours</h3><p data-count="<?= $stats['en_cours']; ?>">0</p></div>
    <div class="card"><h3>Retenues</h3><p data-count="<?= $stats['retenu']; ?>">0</p></div>
    <div class="card"><h3>Rejetées</h3><p data-count="<?= $stats['rejete']; ?>">0</p></div>
  </div>

  <!-- GRID -->
  <div class="grid">

    <!-- TABLE -->
    <div class="table">
      <div style="padding:14px">
        <input class="search" placeholder="Filtrer par offre..." onkeyup="filterTable(this)">
      </div>
      <table id="candTable">
        <thead><tr><th>Offre</th><th>Statut</th><th>Date</th></tr></thead>
        <tbody>
        <?php if (!$candidatures): ?>
          <tr><td colspan="3">Aucune candidature enregistrée.</td></tr>
        <?php endif; ?>
        <?php foreach ($candidatures as $c): ?>
          <tr>
            <td><?= e($c['titre'] ?? 'Offre supprimée'); ?></td>
            <td>
              <span class="badge <?= e($c['statut']); ?>">
                <?= strtoupper(e($c['statut'])); ?>
              </span>
            </td>
            <td><?= e(date('d/m/Y H:i', strtotime($c['created_at']))); ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- CHART -->
    <div class="card">
      <h3>Répartition des candidatures</h3>
      <canvas id="chart" height="220"></canvas>
    </div>

  </div>

  <!-- PROFIL -->
  <div class="profile">
    <h3 style="margin:0 0 10px">Profil</h3>
    <div class="small">
      <strong>Nom :</strong> <?= e($displayName); ?> —
      <strong>Email :</strong> <?= e($candidat['email']); ?> —
      <strong>Tél :</strong> <?= e($candidat['telephone'] ?? ''); ?>
    </div>
  </div>

</div>

<script>
/* Compteurs animés */
document.querySelectorAll('[data-count]').forEach(el=>{
  let target = +el.dataset.count, i = 0;
  let step = Math.max(1, Math.ceil(target/30));
  let timer = setInterval(()=>{
    i += step;
    if(i >= target){ i = target; clearInterval(timer); }
    el.textContent = i;
  },20);
});

/* Filtre tableau */
function filterTable(input){
  let val = input.value.toLowerCase();
  document.querySelectorAll('#candTable tbody tr').forEach(tr=>{
    tr.style.display = tr.textContent.toLowerCase().includes(val) ? '' : 'none';
  });
}

/* Graphique */
new Chart(document.getElementById('chart'),{
  type:'doughnut',
  data:{
    labels:['Reçues','En cours','Retenues','Rejetées'],
    datasets:[{
      data:[
        <?= $stats['recu']; ?>,
        <?= $stats['en_cours']; ?>,
        <?= $stats['retenu']; ?>,
        <?= $stats['rejete']; ?>
      ],
      backgroundColor:['#38bdf8','#facc15','#22c55e','#ef4444']
    }]
  },
  options:{plugins:{legend:{position:'bottom'}}}
});
</script>

</body>
</html>
