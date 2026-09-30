<?php
declare(strict_types=1);

require_once __DIR__ . '/_auth.php';

$error = '';
$success = '';

$allowedTypes = ['entreprise','cabinet_rh','chasseur_tete','ong','institution','projet'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $nom_legal            = trim($_POST['nom_legal'] ?? '');
  $type_entite          = trim($_POST['type_entite'] ?? 'entreprise');
  $secteur              = trim($_POST['secteur'] ?? '');
  $pays                 = trim($_POST['pays'] ?? '');
  $ville                = trim($_POST['ville'] ?? '');
  $telephone            = trim($_POST['telephone'] ?? '');
  $responsable_nom      = trim($_POST['responsable_nom'] ?? '');
  $responsable_fonction = trim($_POST['responsable_fonction'] ?? '');
  $description          = trim($_POST['description'] ?? '');

  if ($nom_legal === '' || strlen($nom_legal) < 2) {
    $error = "Le nom légal est obligatoire.";
  } elseif (!in_array($type_entite, $allowedTypes, true)) {
    $error = "Type d'entité invalide.";
  } else {

    // Upload logo (optionnel)
    $logoPath = $entreprise['logo'] ?? null;

    if (!empty($_FILES['logo']['name']) && is_uploaded_file($_FILES['logo']['tmp_name'])) {
      $max = 3 * 1024 * 1024; // 3MB
      if ((int)$_FILES['logo']['size'] > $max) {
        $error = "Logo trop volumineux (max 3 Mo).";
      } else {
        $ext = strtolower(pathinfo((string)$_FILES['logo']['name'], PATHINFO_EXTENSION));
        $okExt = ['png','jpg','jpeg','webp'];
        if (!in_array($ext, $okExt, true)) {
          $error = "Format logo non supporté (png, jpg, jpeg, webp).";
        } else {
          $dir = __DIR__ . '/../uploads/entreprises/logos';
          if (!is_dir($dir)) @mkdir($dir, 0775, true);

          $file = 'logo_' . $entrepriseId . '_' . date('Ymd_His') . '.' . $ext;
          $dest = $dir . '/' . $file;

          if (@move_uploaded_file($_FILES['logo']['tmp_name'], $dest)) {
            $logoPath = '/uploads/entreprises/logos/' . $file;
          } else {
            $error = "Échec upload du logo.";
          }
        }
      }
    }

    if ($error === '') {
      $up = $pdo->prepare("
        UPDATE entreprises
        SET
          nom_legal = ?,
          type_entite = ?,
          secteur = ?,
          pays = ?,
          ville = ?,
          telephone = ?,
          responsable_nom = ?,
          responsable_fonction = ?,
          description = ?,
          logo = ?,
          updated_at = NOW()
        WHERE id = ?
        LIMIT 1
      ");
      $up->execute([
        $nom_legal,
        $type_entite,
        $secteur,
        $pays,
        $ville,
        $telephone,
        $responsable_nom,
        $responsable_fonction,
        $description,
        $logoPath,
        $entrepriseId
      ]);

      // refresh local
      $entreprise['nom_legal'] = $nom_legal;
      $entreprise['type_entite'] = $type_entite;
      $entreprise['secteur'] = $secteur;
      $entreprise['pays'] = $pays;
      $entreprise['ville'] = $ville;
      $entreprise['telephone'] = $telephone;
      $entreprise['responsable_nom'] = $responsable_nom;
      $entreprise['responsable_fonction'] = $responsable_fonction;
      $entreprise['description'] = $description;
      $entreprise['logo'] = $logoPath;

      $success = "Profil entreprise mis à jour.";
    }
  }
}

// re-fetch complet
$stmt = $pdo->prepare("SELECT * FROM entreprises WHERE id=? LIMIT 1");
$stmt->execute([$entrepriseId]);
$entreprise = $stmt->fetch(PDO::FETCH_ASSOC) ?: $entreprise;

function e(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Profil Entreprise – IBIG EDUFORM</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
:root{--blue:#0b5ed7;--dark:#1e293b;--gray:#f1f5f9}
body{margin:0;font-family:Inter,Arial;background:var(--gray);color:#111827}
.header{background:linear-gradient(90deg,#0b5ed7,#1d4ed8);color:#fff;padding:18px 26px;display:flex;justify-content:space-between;align-items:center}
.header a{color:#fff;text-decoration:none;font-weight:900}
.container{max-width:1100px;margin:28px auto;padding:0 20px}
.nav{display:flex;gap:10px;flex-wrap:wrap;margin:14px 0 22px}
.btn{padding:10px 14px;border-radius:10px;background:var(--blue);color:#fff;text-decoration:none;font-size:13px;font-weight:900}
.btn.alt{background:#475569}
.card{background:#fff;border-radius:16px;padding:20px;box-shadow:0 10px 30px rgba(0,0,0,.08)}
.grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
@media(max-width:900px){.grid{grid-template-columns:1fr}}
label{display:block;font-size:13px;color:#64748b;margin:12px 0 6px}
.input,select,textarea{width:100%;padding:12px 12px;border:1px solid #cbd5e1;border-radius:12px;font-size:14px}
textarea{min-height:110px;resize:vertical}
.actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:14px}
.msg{padding:12px;border-radius:12px;margin-bottom:14px;font-size:14px;font-weight:700}
.ok{background:#dcfce7;color:#166534}
.err{background:#fee2e2;color:#991b1b}
.logoBox{display:flex;gap:14px;align-items:center;margin-top:8px}
.logoBox img{height:52px;width:52px;border-radius:14px;object-fit:cover;border:1px solid #e5e7eb}
.small{color:#64748b;font-size:13px}
</style>
</head>
<body>

<div class="header">
  <div>IBIG EDUFORM — Profil Entreprise</div>
  <div><?= e((string)$entreprise['nom_legal']); ?> | <a href="logout.php">Déconnexion</a></div>
</div>

<div class="container">
  <div class="nav">
    <a class="btn alt" href="dashboard.php">Dashboard</a>
    <a class="btn alt" href="offres.php">Mes offres</a>
    <a class="btn alt" href="candidatures.php">Candidatures</a>
    <a class="btn" href="profil.php">Profil</a>
  </div>

  <div class="card">
    <h2 style="margin:0 0 12px;color:var(--dark);font-size:18px;font-weight:900">Informations entreprise</h2>

    <?php if ($error): ?><div class="msg err"><?= e($error); ?></div><?php endif; ?>
    <?php if ($success): ?><div class="msg ok"><?= e($success); ?></div><?php endif; ?>

    <form method="post" enctype="multipart/form-data" autocomplete="off">

      <div class="grid">
        <div>
          <label>Nom légal (raison sociale)</label>
          <input class="input" name="nom_legal" value="<?= e((string)($entreprise['nom_legal'] ?? '')); ?>" required>

          <label>Type d'entité</label>
          <select name="type_entite">
            <?php foreach (['entreprise','cabinet_rh','chasseur_tete','ong','institution','projet'] as $t): ?>
              <option value="<?= e($t); ?>" <?= (($entreprise['type_entite'] ?? '')===$t)?'selected':''; ?>>
                <?= e($t); ?>
              </option>
            <?php endforeach; ?>
          </select>

          <label>Secteur</label>
          <input class="input" name="secteur" value="<?= e((string)($entreprise['secteur'] ?? '')); ?>" placeholder="Ex: BTP, Finance, Industrie...">

          <label>Téléphone</label>
          <input class="input" name="telephone" value="<?= e((string)($entreprise['telephone'] ?? '')); ?>" placeholder="+225 ...">
        </div>

        <div>
          <label>Pays</label>
          <input class="input" name="pays" value="<?= e((string)($entreprise['pays'] ?? '')); ?>" placeholder="Côte d'Ivoire">

          <label>Ville</label>
          <input class="input" name="ville" value="<?= e((string)($entreprise['ville'] ?? '')); ?>" placeholder="Abidjan">

          <label>Responsable (Nom)</label>
          <input class="input" name="responsable_nom" value="<?= e((string)($entreprise['responsable_nom'] ?? '')); ?>">

          <label>Responsable (Fonction)</label>
          <input class="input" name="responsable_fonction" value="<?= e((string)($entreprise['responsable_fonction'] ?? '')); ?>">
        </div>
      </div>

      <label>Description</label>
      <textarea name="description"><?= e((string)($entreprise['description'] ?? '')); ?></textarea>

      <label>Logo (optionnel)</label>
      <div class="logoBox">
        <?php if (!empty($entreprise['logo'])): ?>
          <img src="<?= e((string)$entreprise['logo']); ?>" alt="Logo">
        <?php else: ?>
          <img src="/assets/images/logo.png" alt="Logo">
        <?php endif; ?>
        <div style="flex:1">
          <input class="input" type="file" name="logo" accept=".png,.jpg,.jpeg,.webp">
          <div class="small">Formats: PNG/JPG/WEBP. Taille max: 3 Mo.</div>
        </div>
      </div>

      <div class="actions">
        <button class="btn" type="submit">Enregistrer</button>
        <a class="btn alt" href="offres.php">Gérer mes offres</a>
        <a class="btn alt" href="candidatures.php">Voir candidatures</a>
      </div>

      <div class="small" style="margin-top:12px">
        Statut du compte : <strong><?= e((string)($entreprise['statut'] ?? '')); ?></strong> —
        Email vérifié : <strong><?= ((int)($entreprise['email_verified'] ?? 0)===1)?'OUI':'NON'; ?></strong>
      </div>

    </form>
  </div>
</div>
</body>
</html>
