<?php
declare(strict_types=1);
require_once __DIR__ . '/_auth.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: candidatures.php'); exit; }

$stmt = $pdo->prepare("
  SELECT
    c.*, o.titre, o.id AS offre_id
  FROM candidatures c
  JOIN offres_emploi o ON o.id = c.offre_id
  WHERE c.id = ? AND o.entreprise_id = ?
  LIMIT 1
");
$stmt->execute([$id, $entrepriseId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) die("Candidature introuvable.");

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $new = (string)($_POST['statut'] ?? '');
  $allowed = ['recu','en_cours','retenu','rejete'];
  if (!in_array($new, $allowed, true)) {
    $error = "Statut invalide.";
  } else {
    $up = $pdo->prepare("
      UPDATE candidatures
      SET statut = ?
      WHERE id = ?
      LIMIT 1
    ");
    $up->execute([$new, $id]);
    $success = "Statut mis à jour.";
    $row['statut'] = $new;
  }
}

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
<title>Détail candidature – IBIG EDUFORM</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
:root{--blue:#0b5ed7;--dark:#1e293b;--gray:#f1f5f9}
body{margin:0;font-family:Inter,Arial;background:var(--gray);color:#111827}
.header{background:linear-gradient(90deg,#0b5ed7,#1d4ed8);color:#fff;padding:18px 26px;display:flex;justify-content:space-between;align-items:center}
.header a{color:#fff;text-decoration:none;font-weight:800}
.container{max-width:1000px;margin:28px auto;padding:0 20px}
.btn{padding:10px 14px;border-radius:10px;background:var(--blue);color:#fff;text-decoration:none;font-size:13px;font-weight:800;border:0;cursor:pointer}
.btn.alt{background:#475569}
.card{background:#fff;border-radius:16px;padding:20px;box-shadow:0 10px 30px rgba(0,0,0,.08)}
.small{color:#64748b;font-size:13px}
.badge{padding:4px 10px;border-radius:999px;font-size:11px;font-weight:900;text-transform:uppercase}
.recu{background:#e0f2fe;color:#0369a1}
.en_cours{background:#fef3c7;color:#92400e}
.retenu{background:#dcfce7;color:#166534}
.rejete{background:#fee2e2;color:#991b1b}
.msg{padding:12px 14px;border-radius:12px;margin:0 0 12px;font-size:14px;font-weight:700}
.ok{background:#dcfce7;color:#166534}
.err{background:#fee2e2;color:#991b1b}
hr{border:0;border-top:1px solid #e5e7eb;margin:14px 0}
select{padding:11px 12px;border-radius:10px;border:1px solid #cbd5e1}
</style>
</head>
<body>

<div class="header">
  <div>IBIG EDUFORM — Candidature</div>
  <div><a href="candidatures.php">Retour</a> | <a href="logout.php">Déconnexion</a></div>
</div>

<div class="container">

  <div class="card">
    <?php if ($success): ?><div class="msg ok"><?= htmlspecialchars($success); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="msg err"><?= htmlspecialchars($error); ?></div><?php endif; ?>

    <h2 style="margin:0;color:var(--dark)"><?= htmlspecialchars($row['nom']); ?></h2>
    <div class="small">
      Offre : <strong><?= htmlspecialchars($row['titre']); ?></strong><br>
      Email : <?= htmlspecialchars($row['email']); ?> — Tél : <?= htmlspecialchars($row['telephone'] ?? ''); ?><br>
      Date : <?= date('d/m/Y H:i', strtotime((string)$row['created_at'])); ?>
    </div>

    <hr>

    <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
      <div>Statut : <span class="badge <?= badge((string)$row['statut']); ?>"><?= strtoupper((string)$row['statut']); ?></span></div>

      <form method="post" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin:0">
        <select name="statut">
          <?php foreach (['recu','en_cours','retenu','rejete'] as $s): ?>
            <option value="<?= $s; ?>" <?= ((string)$row['statut']===$s)?'selected':''; ?>><?= $s; ?></option>
          <?php endforeach; ?>
        </select>
        <button class="btn" type="submit">Mettre à jour</button>
        <a class="btn alt" href="candidatures.php?offre_id=<?= (int)$row['offre_id']; ?>">Voir toutes pour cette offre</a>
      </form>
    </div>

    <hr>

    <h3 style="margin:0 0 8px">Message</h3>
    <div style="white-space:pre-wrap"><?= htmlspecialchars((string)($row['message'] ?? '')); ?></div>

    <hr>

    <h3 style="margin:0 0 8px">CV</h3>
    <?php if (!empty($row['cv'])): ?>
      <a class="btn alt" href="<?= htmlspecialchars((string)$row['cv']); ?>" target="_blank" rel="noopener">Ouvrir le CV</a>
    <?php else: ?>
      <div class="small">Aucun CV fourni.</div>
    <?php endif; ?>

  </div>

</div>
</body>
</html>
