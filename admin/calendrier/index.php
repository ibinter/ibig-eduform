<?php
declare(strict_types=1);

require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../auth/middleware.php';
require_once __DIR__ . '/../auth/guard.php';

Middleware::requireAuth();

/* =====================
   PAGE META
===================== */
$pageTitle  = 'Calendrier des formations';
$activeMenu = 'calendrier';

$pdo = Database::connect();

/* =====================
   FILTRES
===================== */
$formation_id = (int)($_GET['formation_id'] ?? 0);
$page         = max(1, (int)($_GET['page'] ?? 1));
$perPage      = 50;
$offset       = ($page - 1) * $perPage;

$where  = [];
$params = [];

if ($formation_id > 0) {
    $where[]  = 'c.formation_id = ?';
    $params[] = $formation_id;
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

/* =====================
   PAGINATION
===================== */
$countStmt = $pdo->prepare("
  SELECT COUNT(*)
  FROM calendrier_formations c
  $whereSql
");
$countStmt->execute($params);
$total      = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));

/* =====================
   DONNÉES
===================== */
$stmt = $pdo->prepare("
  SELECT c.*, f.titre
  FROM calendrier_formations c
  INNER JOIN formations f ON f.id = c.formation_id
  $whereSql
  ORDER BY c.date_debut DESC
  LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =====================
   LISTE FORMATIONS
===================== */
$formations = $pdo->query("
  SELECT id, titre FROM formations ORDER BY titre
")->fetchAll(PDO::FETCH_ASSOC);

/* =====================
   STATUT AUTO
===================== */
$today = date('Y-m-d');

function autoStatut(array $s, string $today): string {
    if ($s['date_debut'] > $today) return 'a_venir';
    if (!empty($s['date_fin']) && $s['date_fin'] < $today) return 'termine';
    return 'en_cours';
}

ob_start();
?>

<div class="card">

  <!-- HEADER -->
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px">
    <h2>Calendrier des formations</h2>

    <div style="display:flex;gap:8px;flex-wrap:wrap">
      <a class="btn" href="calendar.php">&#128197; Vue mensuelle</a>

      <?php if (has_permission('manage_calendar')): ?>
        <a class="btn" href="create.php">&#10133; Nouvelle session</a>
      <?php endif; ?>
    </div>
  </div>

  <!-- FILTRES -->
  <form method="get" style="display:flex;gap:10px;flex-wrap:wrap;margin:14px 0">
    <select name="formation_id">
      <option value="">Toutes les formations</option>
      <?php foreach ($formations as $f): ?>
        <option value="<?= $f['id']; ?>" <?= $formation_id === (int)$f['id'] ? 'selected' : ''; ?>>
          <?= e($f['titre']); ?>
        </option>
      <?php endforeach; ?>
    </select>
    <button class="btn">&#128269; Filtrer</button>
  </form>

  <!-- TABLE -->
  <table>
    <thead>
      <tr>
        <th>Formation</th>
        <th>Dates</th>
        <th>Durée</th>
        <th>Mode</th>
        <th>Statut</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>

    <?php if (!$sessions): ?>
      <tr>
        <td colspan="6" class="muted">Aucune session.</td>
      </tr>
    <?php endif; ?>

    <?php foreach ($sessions as $s): 
      $auto = autoStatut($s, $today);
    ?>
      <tr>
        <td><strong><?= e($s['titre']); ?></strong></td>

        <td>
          <?= date('d/m/Y', strtotime($s['date_debut'])); ?>
          <?php if (!empty($s['date_fin'])): ?>
            &rarr; <?= date('d/m/Y', strtotime($s['date_fin'])); ?>
          <?php endif; ?>
        </td>

        <td><?= e($s['duree'] ?? '—'); ?></td>
        <td><?= e($s['mode']); ?></td>

        <td>
          <span class="pill
            <?= $auto === 'a_venir' ? 'wait' : ''; ?>
            <?= $auto === 'en_cours' ? 'ok' : ''; ?>">
            <?= ucfirst(str_replace('_', ' ', $auto)); ?>
          </span>
        </td>

        <!-- ACTIONS SÉCURISÉES -->
        <td style="display:flex;gap:6px;flex-wrap:wrap">

          <?php if (has_permission('manage_calendar')): ?>
            <a class="btn" href="edit.php?id=<?= $s['id']; ?>">
              &#9998; Modifier
            </a>
          <?php endif; ?>

          <?php if (has_permission('view_preinscriptions')): ?>
            <a class="btn"
               href="../preinscriptions/index.php?formation_id=<?= $s['formation_id']; ?>">
              &#128221; Préinscriptions
            </a>
          <?php endif; ?>

          <?php if (has_permission('delete_calendar')): ?>
            <a class="btn btn-danger"
               href="delete.php?id=<?= $s['id']; ?>&csrf=<?= csrf_token(); ?>"
               onclick="return confirm('Supprimer cette session ?')">
              &#10006; Supprimer
            </a>
          <?php endif; ?>

        </td>
      </tr>
    <?php endforeach; ?>

    </tbody>
  </table>

  <!-- PAGINATION -->
  <?php if ($totalPages > 1): ?>
    <div style="margin-top:16px;display:flex;gap:6px;flex-wrap:wrap">
      <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a class="btn <?= $i === $page ? 'btn-primary' : ''; ?>"
           href="?<?= http_build_query(array_merge($_GET, ['page' => $i])); ?>">
          <?= $i; ?>
        </a>
      <?php endfor; ?>
    </div>
  <?php endif; ?>

</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout/layout.php';