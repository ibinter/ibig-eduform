<?php
declare(strict_types=1);
require_once __DIR__ . '/_auth.php';

/* ============================
   PAGINATION
============================ */
$page  = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

/* ============================
   FILTRE STATUT (SERVER)
============================ */
$allowedStatus = ['recu','en_cours','retenu','rejete'];
$statut = $_GET['statut'] ?? '';
$useStatus = in_array($statut, $allowedStatus, true);

$where = $candWhereSQL;
$params = $candWhereBind;

if ($useStatus) {
  $where .= " AND statut = ?";
  $params[] = $statut;
}

/* ============================
   TOTAL
============================ */
$t = $pdo->prepare("SELECT COUNT(*) FROM candidatures WHERE $where");
$t->execute($params);
$total = (int)$t->fetchColumn();
$pages = max(1, (int)ceil($total / $limit));

/* ============================
   LISTE
============================ */
$sql = "
  SELECT c.id, o.titre, c.statut, c.created_at
  FROM candidatures c
  LEFT JOIN offres_emploi o ON o.id = c.offre_id
  WHERE $where
  ORDER BY c.created_at DESC
  LIMIT $limit OFFSET $offset
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

function page_link(int $p, string $statut=''): string {
  $q = ['page'=>$p];
  if ($statut !== '') $q['statut'] = $statut;
  return 'candidatures.php?' . http_build_query($q);
}
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Mes candidatures – IBIG EDUFORM</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
:root{--blue:#0b5ed7}
body{margin:0;font-family:Inter,Arial;background:#f1f5f9;color:#111827}
.header{
  background:linear-gradient(90deg,#0b5ed7,#1d4ed8);
  color:#fff;padding:18px 26px;
  display:flex;justify-content:space-between;align-items:center
}
.header a{color:#fff;text-decoration:none;font-weight:700}
.container{max-width:1200px;margin:26px auto;padding:0 20px}

.actions{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:14px}
.btn{
  padding:10px 14px;border-radius:10px;
  background:var(--blue);color:#fff;
  text-decoration:none;font-size:13px;font-weight:700;
  transition:.2s
}
.btn.alt{background:#475569}
.btn.ghost{background:#e5e7eb;color:#111827}
.btn:hover{transform:translateY(-1px);opacity:.9}

.filters{
  display:grid;grid-template-columns:1fr auto;
  gap:10px;margin-bottom:12px
}
.search{
  padding:10px;border:1px solid #cbd5e1;
  border-radius:10px;width:100%
}
.select{
  padding:10px;border:1px solid #cbd5e1;
  border-radius:10px;background:#fff
}

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
  font-size:11px;font-weight:900;text-transform:uppercase
}
.recu{background:#e0f2fe;color:#0369a1}
.en_cours{background:#fef3c7;color:#92400e}
.retenu{background:#dcfce7;color:#166534}
.rejete{background:#fee2e2;color:#991b1b}

.pager{
  display:flex;gap:8px;flex-wrap:wrap;
  align-items:center;margin-top:14px
}
.small{color:#64748b;font-size:13px}
</style>
</head>
<body>

<div class="header">
  <div>IBIG EDUFORM — Mes candidatures</div>
  <div>
    <a href="dashboard.php">Dashboard</a> |
    <a href="logout.php">Déconnexion</a>
  </div>
</div>

<div class="container">

  <!-- ACTIONS -->
  <div class="actions">
    <a class="btn alt" href="dashboard.php">← Retour</a>
    <a class="btn ghost" href="profil.php">Mon profil</a>
    <a class="btn ghost" href="password.php">Mot de passe</a>
  </div>

  <!-- FILTRES -->
  <div class="filters">
    <input id="search" class="search" placeholder="Rechercher une offre…">
    <select class="select" onchange="location='<?= e(page_link(1)); ?>&statut='+this.value">
      <option value="">Tous statuts</option>
      <?php foreach (['recu'=>'Reçues','en_cours'=>'En cours','retenu'=>'Retenues','rejete'=>'Rejetées'] as $k=>$v): ?>
        <option value="<?= $k; ?>" <?= $statut===$k?'selected':''; ?>>
          <?= $v; ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="small"><strong>Total :</strong> <?= $total; ?> candidature(s)</div>

  <!-- TABLE -->
  <div class="table" style="margin-top:12px">
    <table id="candTable">
      <thead>
        <tr>
          <th>Offre</th>
          <th>Statut</th>
          <th>Date</th>
        </tr>
      </thead>
      <tbody>
      <?php if (!$rows): ?>
        <tr><td colspan="3">Aucune candidature.</td></tr>
      <?php endif; ?>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= e($r['titre'] ?? 'Offre supprimée'); ?></td>
          <td>
            <span class="badge <?= e($r['statut']); ?>">
              <?= strtoupper(e($r['statut'])); ?>
            </span>
          </td>
          <td><?= e(date('d/m/Y H:i', strtotime($r['created_at']))); ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- PAGINATION -->
  <?php if ($pages > 1): ?>
    <div class="pager">
      <?php if ($page > 1): ?>
        <a class="btn ghost" href="<?= e(page_link($page-1, $statut)); ?>">&laquo; Précédent</a>
      <?php endif; ?>
      <span class="small">Page <?= $page; ?> / <?= $pages; ?></span>
      <?php if ($page < $pages): ?>
        <a class="btn ghost" href="<?= e(page_link($page+1, $statut)); ?>">Suivant &raquo;</a>
      <?php endif; ?>
    </div>
  <?php endif; ?>

</div>

<script>
/* Recherche instantanée */
const search = document.getElementById('search');
search.addEventListener('keyup', ()=>{
  let val = search.value.toLowerCase();
  document.querySelectorAll('#candTable tbody tr').forEach(tr=>{
    tr.style.display = tr.textContent.toLowerCase().includes(val) ? '' : 'none';
  });
});
</script>

</body>
</html>
