<?php
declare(strict_types=1);
require_once __DIR__ . '/_auth.php';

$offreId = (int)($_GET['offre_id'] ?? 0);
$q = trim((string)($_GET['q'] ?? ''));
$stat = trim((string)($_GET['statut'] ?? ''));

$where = "WHERE o.entreprise_id = ?";
$bind = [$entrepriseId];

if ($offreId > 0) {
  $where .= " AND o.id = ?";
  $bind[] = $offreId;
}
if ($q !== '') {
  $where .= " AND (c.nom LIKE ? OR c.email LIKE ? OR o.titre LIKE ?)";
  $bind[] = "%$q%"; $bind[] = "%$q%"; $bind[] = "%$q%";
}
$allowed = ['recu','en_cours','retenu','rejete'];
if ($stat !== '' && in_array($stat, $allowed, true)) {
  $where .= " AND c.statut = ?";
  $bind[] = $stat;
}

$offres = $pdo->prepare("SELECT id, titre FROM offres_emploi WHERE entreprise_id=? ORDER BY cree_le DESC");
$offres->execute([$entrepriseId]);
$offresList = $offres->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("
  SELECT
    c.id, c.nom, c.email, c.telephone, c.cv, c.statut, c.created_at,
    o.id AS offre_id, o.titre
  FROM candidatures c
  JOIN offres_emploi o ON o.id = c.offre_id
  $where
  ORDER BY c.created_at DESC
  LIMIT 200
");
$stmt->execute($bind);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

function badge(string $s): string {
  return match($s){
    'recu' => 'recu',
    'en_cours' => 'en_cours',
    'retenu' => 'retenu',
    'rejete' => 'rejete',
    default => 'recu'
  };
}
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Candidatures – IBIG EDUFORM</title>
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
.input,select{padding:11px 12px;border-radius:10px;border:1px solid #cbd5e1}
.badge{padding:4px 10px;border-radius:999px;font-size:11px;font-weight:900;text-transform:uppercase}
.recu{background:#e0f2fe;color:#0369a1}
.en_cours{background:#fef3c7;color:#92400e}
.retenu{background:#dcfce7;color:#166534}
.rejete{background:#fee2e2;color:#991b1b}
a.link{color:#0b5ed7;text-decoration:none;font-weight:900}
.small{color:#64748b;font-size:13px}
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
    <a class="btn alt" href="offres.php">Mes offres</a>
    <a class="btn" href="candidatures.php">Candidatures</a>
    <a class="btn alt" href="profil.php">Profil</a>
  </div>

  <div class="card">
    <h2 style="margin:0;color:var(--dark)">Candidatures</h2>
    <div class="small">Affichage (max 200). Utilise les filtres pour affiner.</div>

    <form method="get" style="display:flex;gap:10px;flex-wrap:wrap;margin-top:14px">
      <input class="input" name="q" placeholder="Nom, email, offre…" value="<?= htmlspecialchars($q); ?>">
      <select name="offre_id">
        <option value="0">-- Toutes les offres --</option>
        <?php foreach ($offresList as $o): ?>
          <option value="<?= (int)$o['id']; ?>" <?= $offreId===(int)$o['id']?'selected':''; ?>>
            <?= htmlspecialchars($o['titre']); ?>
          </option>
        <?php endforeach; ?>
      </select>
      <select name="statut">
        <option value="">-- Statut --</option>
        <?php foreach (['recu','en_cours','retenu','rejete'] as $s): ?>
          <option value="<?= $s; ?>" <?= $stat===$s?'selected':''; ?>><?= $s; ?></option>
        <?php endforeach; ?>
      </select>
      <button class="btn alt" type="submit">Filtrer</button>
      <a class="btn alt" href="candidatures.php">Reset</a>
    </form>
  </div>

  <div class="table">
    <table>
      <thead>
        <tr>
          <th>Candidat</th>
          <th>Offre</th>
          <th>Statut</th>
          <th>Date</th>
          <th style="width:150px">Action</th>
        </tr>
      </thead>
      <tbody>
      <?php if (!$rows): ?>
        <tr><td colspan="5">Aucune candidature.</td></tr>
      <?php endif; ?>

      <?php foreach ($rows as $r): ?>
        <tr>
          <td>
            <strong><?= htmlspecialchars($r['nom']); ?></strong><br>
            <span class="small"><?= htmlspecialchars($r['email']); ?></span>
          </td>
          <td><?= htmlspecialchars($r['titre']); ?></td>
          <td><span class="badge <?= badge((string)$r['statut']); ?>"><?= strtoupper((string)$r['statut']); ?></span></td>
          <td><?= date('d/m/Y H:i', strtotime((string)$r['created_at'])); ?></td>
          <td><a class="link" href="candidature_view.php?id=<?= (int)$r['id']; ?>">Voir</a></td>
        </tr>
      <?php endforeach; ?>

      </tbody>
    </table>
  </div>

</div>
</body>
</html>
