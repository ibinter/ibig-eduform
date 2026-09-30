<?php
declare(strict_types=1);

require_once __DIR__ . '/../_init.php';

$mw = realpath(__DIR__ . '/../auth/middleware.php');
if (!$mw) die("Middleware introuvable");
require_once $mw;
Middleware::requireAuth();

$pageTitle  = "Entreprises — Validation";
$activeMenu = "emplois_entreprises";

$pdo = Database::connect();

$stat = trim((string)($_GET['statut'] ?? 'en_attente'));
$allowed = ['en_attente','actif','suspendu','refuse'];
if (!in_array($stat, $allowed, true)) $stat = 'en_attente';

$q = trim((string)($_GET['q'] ?? ''));

$where = "WHERE statut = ?";
$bind = [$stat];

if ($q !== '') {
  $where .= " AND (nom_legal LIKE ? OR email LIKE ? OR secteur LIKE ?)";
  $bind[] = "%$q%"; $bind[] = "%$q%"; $bind[] = "%$q%";
}

$rows = $pdo->prepare("
  SELECT id, nom_legal, type_entite, secteur, pays, ville, email, telephone,
         email_verified, statut, created_at
  FROM entreprises
  $where
  ORDER BY created_at DESC
  LIMIT 300
");
$rows->execute($bind);
$entreprises = $rows->fetchAll(PDO::FETCH_ASSOC);

ob_start();
?>
<div class="card">

  <h2>Entreprises — Validation</h2>

  <form method="get" style="display:flex;gap:10px;flex-wrap:wrap;margin:12px 0 18px">
    <select class="input" name="statut">
      <?php foreach (['en_attente','actif','suspendu','refuse'] as $s): ?>
        <option value="<?= $s; ?>" <?= $stat===$s?'selected':''; ?>><?= $s; ?></option>
      <?php endforeach; ?>
    </select>
    <input class="input" name="q" placeholder="Rechercher (nom, email, secteur)" value="<?= e($q); ?>">
    <button class="btn btn-primary" type="submit">Filtrer</button>
    <a class="btn btn-outline" href="index.php">Reset</a>
  </form>

  <table style="margin-top:12px;width:100%">
    <thead>
      <tr>
        <th>Entreprise</th>
        <th>Email</th>
        <th>Localisation</th>
        <th>Vérifié</th>
        <th>Statut</th>
        <th style="width:240px">Actions</th>
      </tr>
    </thead>
    <tbody>

    <?php if (empty($entreprises)): ?>
      <tr><td colspan="6" class="muted">Aucune entreprise.</td></tr>
    <?php endif; ?>

    <?php foreach ($entreprises as $epr): ?>
      <tr>
        <td>
          <strong><?= e($epr['nom_legal']); ?></strong><br>
          <span class="muted"><?= e($epr['type_entite']); ?> — <?= e($epr['secteur'] ?? ''); ?></span>
        </td>
        <td><?= e($epr['email']); ?><br><span class="muted"><?= e($epr['telephone'] ?? ''); ?></span></td>
        <td><?= e(($epr['pays'] ?? '').' / '.($epr['ville'] ?? '')); ?></td>
        <td>
          <span class="pill <?= ((int)$epr['email_verified']===1)?'ok':'wait'; ?>">
            <?= ((int)$epr['email_verified']===1)?'OUI':'NON'; ?>
          </span>
        </td>
        <td><span class="pill <?= $epr['statut']==='actif'?'ok':($epr['statut']==='en_attente'?'wait':'danger'); ?>">
          <?= strtoupper(e($epr['statut'])); ?>
        </span></td>
        <td style="display:flex;gap:8px;flex-wrap:wrap">
          <a class="btn btn-outline" href="action.php?id=<?= (int)$epr['id']; ?>&do=actif" onclick="return confirm('Activer ?');">Activer</a>
          <a class="btn btn-outline" href="action.php?id=<?= (int)$epr['id']; ?>&do=suspendu" onclick="return confirm('Suspendre ?');">Suspendre</a>
          <a class="btn btn-outline" href="action.php?id=<?= (int)$epr['id']; ?>&do=refuse" onclick="return confirm('Refuser ?');">Refuser</a>
        </td>
      </tr>
    <?php endforeach; ?>

    </tbody>
  </table>

</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout/layout.php';
