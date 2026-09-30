<?php
declare(strict_types=1);
require_once __DIR__ . '/_auth.php';

$id = (int)($_GET['id'] ?? 0);
$isEdit = $id > 0;

$error = '';
$success = '';

$offre = [
  'titre'=>'','type_contrat'=>'CDI','lieu'=>'','niveau_experience'=>'',
  'date_limite'=>'','description'=>'','missions'=>'','profil_recherche'=>'','statut'=>'brouillon'
];

if ($isEdit) {
  $s = $pdo->prepare("SELECT * FROM offres_emploi WHERE id=? AND entreprise_id=? LIMIT 1");
  $s->execute([$id, $entrepriseId]);
  $row = $s->fetch(PDO::FETCH_ASSOC);
  if (!$row) { die("Offre introuvable."); }
  $offre = array_merge($offre, $row);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $offre['titre'] = trim((string)($_POST['titre'] ?? ''));
  $offre['type_contrat'] = (string)($_POST['type_contrat'] ?? 'CDI');
  $offre['lieu'] = trim((string)($_POST['lieu'] ?? ''));
  $offre['niveau_experience'] = trim((string)($_POST['niveau_experience'] ?? ''));
  $offre['date_limite'] = (string)($_POST['date_limite'] ?? '');
  $offre['description'] = trim((string)($_POST['description'] ?? ''));
  $offre['missions'] = trim((string)($_POST['missions'] ?? ''));
  $offre['profil_recherche'] = trim((string)($_POST['profil_recherche'] ?? ''));

  $allowedType = ['CDI','CDD','Stage','Mission','Freelance'];

  if ($offre['titre'] === '' || strlen($offre['titre']) < 4) {
    $error = "Titre obligatoire (min 4 caractères).";
  } elseif (!in_array($offre['type_contrat'], $allowedType, true)) {
    $error = "Type de contrat invalide.";
  } elseif ($offre['description'] === '') {
    $error = "La description est obligatoire.";
  } else {

    if ($isEdit) {
      $up = $pdo->prepare("
        UPDATE offres_emploi
        SET titre=?, type_contrat=?, lieu=?, niveau_experience=?, date_limite=?,
            description=?, missions=?, profil_recherche=?
        WHERE id=? AND entreprise_id=?
        LIMIT 1
      ");
      $up->execute([
        $offre['titre'], $offre['type_contrat'], $offre['lieu'], $offre['niveau_experience'],
        ($offre['date_limite'] ?: null),
        $offre['description'], $offre['missions'], $offre['profil_recherche'],
        $id, $entrepriseId
      ]);
      $success = "Offre mise à jour.";
    } else {
      $ins = $pdo->prepare("
        INSERT INTO offres_emploi
          (entreprise_id, titre, type_contrat, lieu, niveau_experience, date_limite,
           description, missions, profil_recherche, statut, cree_le)
        VALUES
          (?, ?, ?, ?, ?, ?, ?, ?, ?, 'brouillon', NOW())
      ");
      $ins->execute([
        $entrepriseId, $offre['titre'], $offre['type_contrat'], $offre['lieu'], $offre['niveau_experience'],
        ($offre['date_limite'] ?: null),
        $offre['description'], $offre['missions'], $offre['profil_recherche']
      ]);
      $id = (int)$pdo->lastInsertId();
      $isEdit = true;
      $success = "Offre créée (statut: brouillon).";
    }
  }
}
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title><?= $isEdit ? 'Éditer' : 'Créer'; ?> une offre – IBIG EDUFORM</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
:root{--blue:#0b5ed7;--dark:#1e293b;--gray:#f1f5f9}
body{margin:0;font-family:Inter,Arial;background:var(--gray);color:#111827}
.header{background:linear-gradient(90deg,#0b5ed7,#1d4ed8);color:#fff;padding:18px 26px;display:flex;justify-content:space-between;align-items:center}
.header a{color:#fff;text-decoration:none;font-weight:800}
.container{max-width:1100px;margin:28px auto;padding:0 20px}
.nav{display:flex;gap:10px;flex-wrap:wrap;margin:14px 0 22px}
.btn{padding:10px 14px;border-radius:10px;background:var(--blue);color:#fff;text-decoration:none;font-size:13px;font-weight:800;border:0;cursor:pointer}
.btn.alt{background:#475569}
.card{background:#fff;border-radius:16px;padding:20px;box-shadow:0 10px 30px rgba(0,0,0,.08)}
.grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
@media(max-width:900px){.grid{grid-template-columns:1fr}}
label{display:block;margin-top:12px;font-size:13px;color:#64748b}
.input,textarea,select{width:100%;padding:12px;border-radius:12px;border:1px solid #cbd5e1;margin-top:6px;font-size:14px}
.input:focus,textarea:focus,select:focus{outline:none;border-color:var(--blue)}
textarea{min-height:140px;resize:vertical}
.msg{padding:12px 14px;border-radius:12px;margin:0 0 12px;font-size:14px;font-weight:700}
.ok{background:#dcfce7;color:#166534}
.err{background:#fee2e2;color:#991b1b}
hr{border:0;border-top:1px solid #e5e7eb;margin:14px 0}
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
    <a class="btn" href="offres.php">Mes offres</a>
    <a class="btn alt" href="candidatures.php">Candidatures</a>
    <a class="btn alt" href="profil.php">Profil</a>
  </div>

  <div class="card">
    <h2 style="margin:0;color:var(--dark)"><?= $isEdit ? 'Éditer l’offre' : 'Créer une offre'; ?></h2>
    <div class="small">Statut actuel: <strong><?= htmlspecialchars((string)($offre['statut'] ?? 'brouillon')); ?></strong></div>

    <hr>

    <?php if ($error): ?><div class="msg err"><?= htmlspecialchars($error); ?></div><?php endif; ?>
    <?php if ($success): ?><div class="msg ok"><?= htmlspecialchars($success); ?></div><?php endif; ?>

    <form method="post" autocomplete="off">
      <div class="grid">
        <div>
          <label>Titre</label>
          <input class="input" name="titre" value="<?= htmlspecialchars($offre['titre']); ?>" required>
        </div>
        <div>
          <label>Type de contrat</label>
          <select name="type_contrat">
            <?php foreach (['CDI','CDD','Stage','Mission','Freelance'] as $tc): ?>
              <option value="<?= $tc; ?>" <?= ($offre['type_contrat']===$tc)?'selected':''; ?>><?= $tc; ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="grid">
        <div>
          <label>Lieu</label>
          <input class="input" name="lieu" value="<?= htmlspecialchars((string)($offre['lieu'] ?? '')); ?>">
        </div>
        <div>
          <label>Niveau d’expérience</label>
          <input class="input" name="niveau_experience" value="<?= htmlspecialchars((string)($offre['niveau_experience'] ?? '')); ?>" placeholder="Ex: Junior, 2 ans, Senior…">
        </div>
      </div>

      <label>Date limite</label>
      <input class="input" type="date" name="date_limite" value="<?= htmlspecialchars((string)($offre['date_limite'] ?? '')); ?>">

      <label>Description</label>
      <textarea name="description" required><?= htmlspecialchars((string)($offre['description'] ?? '')); ?></textarea>

      <label>Missions</label>
      <textarea name="missions"><?= htmlspecialchars((string)($offre['missions'] ?? '')); ?></textarea>

      <label>Profil recherché</label>
      <textarea name="profil_recherche"><?= htmlspecialchars((string)($offre['profil_recherche'] ?? '')); ?></textarea>

      <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:16px">
        <button class="btn" type="submit">Enregistrer</button>

        <?php if ($isEdit): ?>
          <form method="post" action="offre_action.php" style="display:inline">
            <input type="hidden" name="id" value="<?= (int)$id; ?>">
            <input type="hidden" name="do" value="submit">
            <button class="btn alt" type="submit" onclick="return confirm('Soumettre cette offre ?');">Soumettre</button>
          </form>
        <?php endif; ?>

        <a class="btn alt" href="offres.php">Retour</a>
      </div>
    </form>
  </div>

</div>
</body>
</html>
