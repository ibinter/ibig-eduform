<?php
declare(strict_types=1);

require_once __DIR__ . '/../_init.php';
require_once __DIR__ . '/../auth/middleware.php';

Middleware::requireAuth();

$pdo = Database::connect();

$id = (int)($_GET['id'] ?? 0);

$statut = trim((string)($_GET['statut'] ?? ''));
$q      = trim((string)($_GET['q'] ?? ''));

$allowedStatus = ['nouvelle','en_cours','traitee','rejete'];

$where  = "WHERE 1=1";
$params = [];

if ($id > 0) {
  $where .= " AND id = :id";
  $params['id'] = $id;
} else {
  if ($statut !== '' && in_array($statut, $allowedStatus, true)) {
    $where .= " AND statut = :statut";
    $params['statut'] = $statut;
  }
  if ($q !== '') {
    $where .= " AND (
      CONCAT(COALESCE(prenoms,''),' ',COALESCE(nom,'')) LIKE :q
      OR nom LIKE :q
      OR prenoms LIKE :q
      OR COALESCE(structure_nom,'') LIKE :q
      OR COALESCE(domaine_formation,'') LIKE :q
      OR COALESCE(theme_formation,'') LIKE :q
    )";
    $params['q'] = '%'.$q.'%';
  }
}

$sql = "
  SELECT
    id, created_at, statut, type_demandeur,
    nom, prenoms, email, telephone,
    structure_nom, fonction, secteur,
    domaine_formation, theme_formation,
    objectif, niveau, nombre_participants,
    mode_formation, lieu, duree, periode_souhaitee,
    budget, urgence, message
  FROM demandes_formation
  $where
  ORDER BY created_at DESC
  LIMIT 2000
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

?><!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Export demandes formation</title>
<style>
body{font-family:Arial, sans-serif; color:#111; margin:20px}
h1{margin:0 0 10px}
.small{font-size:12px;color:#555}
table{width:100%;border-collapse:collapse;margin-top:14px}
th,td{border:1px solid #ddd;padding:8px;font-size:12px;vertical-align:top}
th{background:#f3f4f6;text-align:left}
.badge{display:inline-block;padding:2px 8px;border-radius:999px;border:1px solid #ddd;font-size:11px}
@media print{
  .no-print{display:none}
  body{margin:0}
}
</style>
</head>
<body>

<div class="no-print" style="margin-bottom:10px">
  <button onclick="window.print()">Imprimer / Enregistrer en PDF</button>
</div>

<h1>Demandes de formation</h1>
<div class="small">
  Genere le <?= htmlspecialchars(date('d/m/Y H:i')); ?> —
  Total: <?= (int)count($rows); ?>
</div>

<?php if (empty($rows)): ?>
  <p>Aucune donnee.</p>
<?php else: ?>

<table>
  <thead>
    <tr>
      <th>ID</th>
      <th>Date</th>
      <th>Statut</th>
      <th>Demandeur</th>
      <th>Structure</th>
      <th>Domaine / Theme</th>
      <th>Objectif</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= (int)$r['id']; ?></td>
        <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime((string)$r['created_at']))); ?></td>
        <td><span class="badge"><?= htmlspecialchars((string)$r['statut']); ?></span></td>
        <td>
          <?= htmlspecialchars(trim(($r['prenoms'] ?? '').' '.($r['nom'] ?? ''))); ?><br>
          <span class="small"><?= htmlspecialchars((string)($r['telephone'] ?? '')); ?></span>
        </td>
        <td><?= htmlspecialchars((string)($r['structure_nom'] ?: '—')); ?></td>
        <td>
          <strong><?= htmlspecialchars((string)($r['domaine_formation'] ?: '—')); ?></strong><br>
          <span class="small"><?= htmlspecialchars((string)($r['theme_formation'] ?: '')); ?></span>
        </td>
        <td><?= nl2br(htmlspecialchars((string)($r['objectif'] ?? ''))); ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<?php endif; ?>

</body>
</html>
