<?php
declare(strict_types=1);

/*
 |--------------------------------------------------
 | DASHBOARD ENTREPRISE — IBIG EDUFORM (PREMIUM)
 |--------------------------------------------------
*/

require_once __DIR__ . '/_auth.php';

/* ============================
   STATS GLOBALES
============================ */

// Total offres
$qOffres = $pdo->prepare("
  SELECT COUNT(*) 
  FROM offres_emploi
  WHERE entreprise_id = ?
");
$qOffres->execute([$entrepriseId]);
$totalOffres = (int)$qOffres->fetchColumn();

// Total candidatures
$qCand = $pdo->prepare("
  SELECT COUNT(*) 
  FROM candidatures c
  JOIN offres_emploi o ON o.id = c.offre_id
  WHERE o.entreprise_id = ?
");
$qCand->execute([$entrepriseId]);
$totalCand = (int)$qCand->fetchColumn();

// Stats par statut
$stats = [
  'recu' => 0,
  'en_cours' => 0,
  'retenu' => 0,
  'rejete' => 0
];

$qStats = $pdo->prepare("
  SELECT
    SUM(c.statut='recu')     AS recu,
    SUM(c.statut='en_cours') AS en_cours,
    SUM(c.statut='retenu')  AS retenu,
    SUM(c.statut='rejete')  AS rejete
  FROM candidatures c
  JOIN offres_emploi o ON o.id = c.offre_id
  WHERE o.entreprise_id = ?
");
$qStats->execute([$entrepriseId]);
$data = $qStats->fetch(PDO::FETCH_ASSOC) ?: [];
foreach ($stats as $k => $v) {
  $stats[$k] = (int)($data[$k] ?? 0);
}

// Dernières candidatures
$list = $pdo->prepare("
  SELECT
    c.id,
    c.nom,
    c.email,
    c.statut,
    c.created_at,
    o.titre
  FROM candidatures c
  JOIN offres_emploi o ON o.id = c.offre_id
  WHERE o.entreprise_id = ?
  ORDER BY c.created_at DESC
  LIMIT 8
");
$list->execute([$entrepriseId]);
$candidatures = $list->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Dashboard Entreprise – IBIG EDUFORM</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<style>
:root{
  --blue:#0b5ed7;
  --dark:#1e293b;
  --gray:#f1f5f9;
}
body{
  margin:0;
  font-family:Inter,Arial,Helvetica,sans-serif;
  background:var(--gray);
  color:#111827;
}
.header{
  background:linear-gradient(90deg,#0b5ed7,#1d4ed8);
  color:#fff;
  padding:18px 26px;
  display:flex;
  justify-content:space-between;
  align-items:center;
}
.header a{
  color:#fff;
  text-decoration:none;
  font-weight:800;
}
.container{
  max-width:1200px;
  margin:28px auto;
  padding:0 20px;
}
.nav{
  display:flex;
  gap:10px;
  flex-wrap:wrap;
  margin:14px 0 22px;
}
.btn{
  padding:10px 14px;
  border-radius:10px;
  background:var(--blue);
  color:#fff;
  text-decoration:none;
  font-size:13px;
  font-weight:800;
}
.btn.alt{background:#475569}

.cards{
  display:grid;
  grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
  gap:18px;
}
.card{
  background:#fff;
  border-radius:16px;
  padding:20px;
  box-shadow:0 10px 30px rgba(0,0,0,.08);
}
.card h3{
  margin:0;
  font-size:13px;
  color:#64748b;
}
.card p{
  margin:10px 0 0;
  font-size:30px;
  font-weight:900;
  color:var(--dark);
}

.grid{
  display:grid;
  grid-template-columns:2fr 1fr;
  gap:20px;
  margin-top:26px;
}
@media(max-width:900px){
  .grid{grid-template-columns:1fr}
}

.table{
  background:#fff;
  border-radius:16px;
  box-shadow:0 10px 30px rgba(0,0,0,.08);
  overflow:hidden;
}
table{
  width:100%;
  border-collapse:collapse;
}
th,td{
  padding:14px;
  border-bottom:1px solid #e5e7eb;
  font-size:14px;
}
th{
  background:#f8fafc;
  text-align:left;
}
.badge{
  padding:4px 10px;
  border-radius:999px;
  font-size:11px;
  font-weight:900;
  text-transform:uppercase;
}
.recu{background:#e0f2fe;color:#0369a1}
.en_cours{background:#fef3c7;color:#92400e}
.retenu{background:#dcfce7;color:#166534}
.rejete{background:#fee2e2;color:#991b1b}
</style>
</head>
<body>

<div class="header">
  <div>IBIG EDUFORM — Espace Entreprise</div>
  <div>
    <div>
  <?= htmlspecialchars($entreprise['nom_legal']); ?> |
  <a href="logout.php">Déconnexion</a>
</div>
    <a href="logout.php">Déconnexion</a>
  </div>
</div>

<div class="container">

  <!-- NAV -->
  <div class="nav">
    <a class="btn" href="dashboard.php">Dashboard</a>
    <a class="btn alt" href="offres.php">Mes offres</a>
    <a class="btn alt" href="candidatures.php">Candidatures</a>
    <a class="btn alt" href="profil.php">Profil</a>
  </div>

  <!-- KPI -->
  <div class="cards">
    <div class="card"><h3>Offres publiées</h3><p><?= $totalOffres; ?></p></div>
    <div class="card"><h3>Candidatures reçues</h3><p><?= $totalCand; ?></p></div>
    <div class="card"><h3>Retenues</h3><p><?= $stats['retenu']; ?></p></div>
    <div class="card"><h3>En cours</h3><p><?= $stats['en_cours']; ?></p></div>
  </div>

  <!-- GRID -->
  <div class="grid">

    <!-- TABLE -->
    <div class="table">
      <table>
        <thead>
          <tr>
            <th>Candidat</th>
            <th>Offre</th>
            <th>Statut</th>
            <th>Date</th>
          </tr>
        </thead>
        <tbody>
        <?php if (!$candidatures): ?>
          <tr><td colspan="4">Aucune candidature reçue.</td></tr>
        <?php endif; ?>
        <?php foreach ($candidatures as $c): ?>
          <tr>
            <td><?= htmlspecialchars($c['nom']); ?></td>
            <td><?= htmlspecialchars($c['titre']); ?></td>
            <td><span class="badge <?= $c['statut']; ?>"><?= strtoupper($c['statut']); ?></span></td>
            <td><?= date('d/m/Y', strtotime($c['created_at'])); ?></td>
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

</div>

<script>
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
  options:{
    plugins:{legend:{position:'bottom'}}
  }
});
</script>

</body>
</html>
