<?php
declare(strict_types=1);
require_once __DIR__ . '/_auth.php';

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 15;
$offset = ($page - 1) * $limit;

$q = trim((string)($_GET['q'] ?? ''));
$stat = trim((string)($_GET['statut'] ?? ''));

$where = "WHERE entreprise_id = ?";
$bind = [$entrepriseId];

if ($q !== '') {
  $where .= " AND (titre LIKE ? OR lieu LIKE ?)";
  $bind[] = "%$q%"; $bind[] = "%$q%";
}
$allowedStat = ['brouillon','soumise','en_revision','approuvee','refusee','expiree'];
if ($stat !== '' && in_array($stat, $allowedStat, true)) {
  $where .= " AND statut = ?";
  $bind[] = $stat;
}

/* Total */
$t = $pdo->prepare("SELECT COUNT(*) FROM offres_emploi $where");
$t->execute($bind);
$total = (int)$t->fetchColumn();
$pages = max(1, (int)ceil($total / $limit));

/* Liste */
$stmt = $pdo->prepare("
  SELECT id, titre, type_contrat, lieu, date_limite, statut, publie_le, cree_le
  FROM offres_emploi
  $where
  ORDER BY cree_le DESC
  LIMIT $limit OFFSET $offset
");
$stmt->execute($bind);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

function pill(string $s): array {
  $map = [
    'brouillon'=>['wait','BROUILLON'],
    'soumise'=>['wait','SOUMISE'],
    'en_revision'=>['wait','REVISION'],
    'approuvee'=>['ok','APPROUVEE'],
    'refusee'=>['bad','REFUSEE'],
    'expiree'=>['bad','EXPIREE'],
  ];
  return $map[$s] ?? ['wait', strtoupper($s)];
}
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Mes offres – IBIG EDUFORM</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
:root{--blue:#0b5ed7;--dark:#1e293b;--gray:#f1f5f9}
body{margin:0;font-family:Inter,Arial;background:var(--gray);color:#111827}
.header{background:linear-gradient(90deg,#0b5ed7,#1d4ed8);color:#fff;padding:18px 26px;display:flex;justify-content:space-between;align-items:center}
.header a{color:#fff;text-decoration:none;font-weight:800}
.container{max-width:1200px;margin:28px auto;padding:0 20px}
.nav{display:flex;gap:10px;flex-wrap:wrap;margin:14px 0 22px}
.btn{padding:10px 14px;border-radius:10px;background:var(--blue);color:#fff;text-decoration:none;font-size:13px;font-weight:800;border:0;cursor:pointer}
.btn.alt{background:#475569}
.card{background:#fff;border-radius:16px;padding:18px;box-shadow:0 10px 30px rgba(0,0,0,.08)}
.table{background:#fff;border-radius:16px;box-shadow:0 10px 30px rgba(0,0,0,.08);overflow:hidden;margin-top:14px}
table{width:100%;border-collapse:collapse}
th,td{padding:14px;border-bottom:1px solid #e5e7eb;font-size:14px}
th{background:#f8fafc;text-align:left}
.pill{display:inline-block;padding:6px 10px;border-radius:999px;font-size:11px;font-weight:900;text-transform:uppercase}
.pill.ok{background:#dcfce7;color:#166534}
.pill.wait{background:#fef3c7;color:#92400e}
.pill.bad{background:#fee2e2;color:#991b1b}
.input,select{padding:11px 12px;border-radius:10px;border:1px solid #cbd5e1}
.pager{display:flex;gap:10px;flex-wrap:wrap;margin-top:12px;align-items:center}
.small{color:#64748b;font-size:13px}
.actions{display:flex;gap:8px;flex-wrap:wrap}
a.link{color:#0b5ed7;text-decoration:none;font-weight:800}
</style>
</head>
<body>

<div class="header">
  <div>IBIG EDUFORM — Espace Entreprise</div>
  <div><?= htmlspecialchars($entreprise['nom_legal'] ?? 'Entreprise'); ?> | <a href="logout.php">Déconnexion</a></div>
</div>

<div class="container">

  <div class="nav">
    <a class="btn alt" href="dashboard.php">Dashboard</a>
    <a class="btn" href="offres.php">Mes offres</a>
    <a class="btn alt" href="candidatures.php">Candidatures</a>
    <a class="btn alt" href="profil.php">Profil</a>
  </div>

  <div class="card">
    <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:center">
      <div>
        <h2 style="margin:0;color:var(--dark)">Mes offres</h2>
        <div class="small"><strong>Total :</strong> <?= $total; ?> offre(s)</div>
      </div>
      <a class="btn" href="offre_edit.php">+ Nouvelle offre</a>
    </div>

    <form method="get" style="display:flex;gap:10px;flex-wrap:wrap;margin-top:14px">
      <input class="input" name="q" placeholder="Rechercher (titre, lieu)" value="<?= htmlspecialchars($q); ?>">
      <select name="statut">
        <option value="">-- Statut --</option>
        <?php foreach (['brouillon','soumise','en_revision','approuvee','refusee','expiree'] as $s): ?>
          <option value="<?= $s; ?>" <?= $stat===$s?'selected':''; ?>><?= $s; ?></option>
        <?php endforeach; ?>
      </select>
      <button class="btn alt" type="submit">Filtrer</button>
      <a class="btn alt" href="offres.php">Reset</a>
    </form>
  </div>

  <div class="table">
    <table>
      <thead>
        <tr>
          <th>Offre</th>
          <th>Contrat</th>
          <th>Lieu</th>
          <th>Date limite</th>
          <th>Statut</th>
          <th style="width:220px">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$rows): ?>
          <tr><td colspan="6">Aucune offre trouvée.</td></tr>
        <?php endif; ?>

        <?php foreach ($rows as $r): ?>
          <?php [$cls,$lbl] = pill((string)$r['statut']); ?>
          <tr>
            <td><strong><?= htmlspecialchars($r['titre']); ?></strong><div class="small">Créée: <?= date('d/m/Y', strtotime((string)$r['cree_le'])); ?></div></td>
            <td><?= htmlspecialchars($r['type_contrat']); ?></td>
            <td><?= htmlspecialchars($r['lieu'] ?? ''); ?></td>
            <td><?= !empty($r['date_limite']) ? date('d/m/Y', strtotime((string)$r['date_limite'])) : '-'; ?></td>
            <td><span class="pill <?= $cls; ?>"><?= $lbl; ?></span></td>
            <td class="actions">
              <a class="link" href="offre_edit.php?id=<?= (int)$r['id']; ?>">Éditer</a>
              <a class="link" href="candidatures.php?offre_id=<?= (int)$r['id']; ?>">Candidatures</a>

              <?php if (in_array((string)$r['statut'], ['brouillon','refusee'], true)): ?>
                <form method="post" action="offre_action.php" style="display:inline">
                  <input type="hidden" name="id" value="<?= (int)$r['id']; ?>">
                  <input type="hidden" name="do" value="submit">
                  <button class="btn alt" type="submit" onclick="return confirm('Soumettre cette offre ?');">Soumettre</button>
                </form>
              <?php endif; ?>

              <form method="post" action="offre_action.php" style="display:inline">
                <input type="hidden" name="id" value="<?= (int)$r['id']; ?>">
                <input type="hidden" name="do" value="delete">
                <button class="btn alt" type="submit" onclick="return confirm('Supprimer cette offre ?');">Supprimer</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>

      </tbody>
    </table>
  </div>

  <?php if ($pages > 1): ?>
    <div class="pager">
      <?php if ($page > 1): ?><a class="btn alt" href="?<?= http_build_query(['q'=>$q,'statut'=>$stat,'page'=>$page-1]); ?>">&laquo; Précédent</a><?php endif; ?>
      <div class="small">Page <?= $page; ?> / <?= $pages; ?></div>
      <?php if ($page < $pages): ?><a class="btn alt" href="?<?= http_build_query(['q'=>$q,'statut'=>$stat,'page'=>$page+1]); ?>">Suivant &raquo;</a><?php endif; ?>
    </div>
  <?php endif; ?>

</div>
</body>
</html>
