<?php
declare(strict_types=1);

require_once __DIR__ . '/../_init.php';

Middleware::requireAuth();
$u = auth_user();

$pageTitle  = "Gestion des niveaux";
$activeMenu = "niveaux";

$pdo = Database::connect();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

/* ── Filtre recherche ── */
$q      = trim((string)($_GET['q'] ?? ''));
$filtre = trim((string)($_GET['statut'] ?? ''));

/* ── Données : formations catalogue avec leurs niveaux ── */
$where  = ["f.statut IN ('active','inactive')", "COALESCE(f.is_samedi_pro,0) = 0"];
$params = [];
if ($q !== '') {
    $where[]         = 'f.titre LIKE :q';
    $params[':q']    = '%' . $q . '%';
}

$sql = "
    SELECT
        f.id, f.titre, f.slug, f.domaine, f.statut AS statut_formation,
        COUNT(n.id)                                    AS nb_niveaux,
        SUM(n.statut = 'actif')                        AS nb_actifs,
        SUM(n.statut = 'brouillon')                    AS nb_brouillons,
        SUM(n.statut = 'archive')                      AS nb_archives
    FROM formations f
    LEFT JOIN formation_niveaux n ON n.formation_id = f.id
    WHERE " . implode(' AND ', $where) . "
    GROUP BY f.id
    ORDER BY f.titre ASC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$formations = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($filtre === 'sans_niveaux') {
    $formations = array_values(array_filter($formations, fn($f) => (int)$f['nb_niveaux'] === 0));
} elseif ($filtre === 'brouillon') {
    $formations = array_values(array_filter($formations, fn($f) => (int)$f['nb_brouillons'] > 0));
} elseif ($filtre === 'tout_actif') {
    $formations = array_values(array_filter($formations, fn($f) => (int)$f['nb_actifs'] === 3));
}

$total = count($formations);

/* ── Pré-charger niveaux actifs de toutes les formations listées ── */
$fids = array_column($formations, 'id');
$actifs_map = [];
if ($fids) {
    $phs = implode(',', array_fill(0, count($fids), '?'));
    $actifs_rows = $pdo->prepare("
        SELECT formation_id, id AS niveau_id, niveau
        FROM formation_niveaux
        WHERE formation_id IN ($phs) AND statut='actif'
        ORDER BY ordre_affichage ASC
    ");
    $actifs_rows->execute($fids);
    foreach ($actifs_rows->fetchAll(PDO::FETCH_ASSOC) as $ar) {
        $actifs_map[(int)$ar['formation_id']][] = $ar;
    }
}

ob_start();
?>
<style>
.niv-wrap{width:100%}
.toolbar{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:14px;flex-wrap:wrap}
.toolbar h2{margin:0;font-size:18px;font-weight:700}
.filters{display:flex;align-items:center;gap:6px;flex-wrap:wrap}
.filters input,.filters select{padding:5px 10px;border:1px solid #e2e8f0;border-radius:6px;font-size:.82rem;background:#fff;color:#0f172a}
.filters button,.btn-sm{padding:5px 14px;border-radius:6px;font-size:.8rem;font-weight:700;cursor:pointer;border:none}
.btn-primary{background:#1e40af;color:#fff}
.btn-sm{background:#f1f5f9;color:#334155}
.stat-bar{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:14px}
.stat-pill{background:#f1f5f9;border:1px solid #e2e8f0;border-radius:8px;padding:6px 16px;font-size:.78rem;font-weight:700;color:#475569;display:flex;align-items:center;gap:6px}
.stat-pill span{font-size:1rem;color:#1e40af}

.niv-table{width:100%;border-collapse:collapse;font-size:.82rem}
.niv-table th{background:#0a1733;color:#e8edf8;font-weight:700;padding:7px 10px;text-align:left;white-space:nowrap}
.niv-table td{padding:6px 10px;border-bottom:1px solid #f1f5f9;vertical-align:middle}
.niv-table tr:hover td{background:#f8fafc}
.niv-badge{display:inline-flex;align-items:center;gap:4px;font-size:.7rem;font-weight:700;padding:2px 9px;border-radius:999px;white-space:nowrap}
.niv-d{background:#dcfce7;color:#166534;border:1px solid #86efac}
.niv-i{background:#dbeafe;color:#1e40af;border:1px solid #93c5fd}
.niv-e{background:#fce7f3;color:#9d174d;border:1px solid #f9a8d4}
.niv-brouillon{background:#fef9c3;color:#713f12;border:1px solid #fde047}
.niv-archive{background:#f1f5f9;color:#94a3b8;border:1px solid #cbd5e1}
.tag-actif{background:#dcfce7;color:#166534}
.tag-inactif{background:#fef9c3;color:#713f12}
.btn-edit{display:inline-block;padding:3px 10px;background:#1e40af;color:#fff;border-radius:5px;font-size:.75rem;font-weight:700;text-decoration:none}
.btn-edit:hover{background:#1d4ed8}
.empty-row td{text-align:center;padding:30px;color:#94a3b8;font-style:italic}
.stf{font-size:.68rem;font-weight:700;padding:2px 7px;border-radius:4px}
.stf-active{background:#dcfce7;color:#166534}.stf-inactive{background:#fef3c7;color:#92400e}
</style>

<div class="niv-wrap">

  <div class="toolbar">
    <h2>📊 Niveaux des formations catalogue</h2>
    <div style="display:flex;gap:8px;align-items:center">
      <a href="bulk-activate.php" style="padding:6px 14px;background:#166534;color:#fff;border-radius:6px;font-size:.8rem;font-weight:700;text-decoration:none">⚡ Activation en masse</a>
      <a href="generate-modules.php" style="padding:6px 14px;background:#7c3aed;color:#fff;border-radius:6px;font-size:.8rem;font-weight:700;text-decoration:none">🤖 Générer modules IA</a>
    </div>
    <form method="get" class="filters">
      <input type="text" name="q" value="<?= e($q) ?>" placeholder="Rechercher une formation…" style="width:220px">
      <select name="statut">
        <option value="">Tous</option>
        <option value="tout_actif"<?= $filtre === 'tout_actif' ? ' selected' : '' ?>>3 niveaux actifs</option>
        <option value="brouillon"<?= $filtre === 'brouillon' ? ' selected' : '' ?>>Avec brouillon(s)</option>
        <option value="sans_niveaux"<?= $filtre === 'sans_niveaux' ? ' selected' : '' ?>>Sans niveaux</option>
      </select>
      <button type="submit" class="btn-primary filters">Filtrer</button>
      <?php if ($q !== '' || $filtre !== ''): ?>
        <a href="index.php" class="btn-sm">✕ Reset</a>
      <?php endif; ?>
    </form>
  </div>

  <div class="stat-bar">
    <div class="stat-pill">Total formations <span><?= $total ?></span></div>
    <div class="stat-pill">Avec niveaux <span><?= count(array_filter($formations, fn($f) => (int)$f['nb_niveaux'] > 0)) ?></span></div>
    <div class="stat-pill" style="color:#166534">Tout actif <span><?= count(array_filter($formations, fn($f) => (int)$f['nb_actifs'] === 3)) ?></span></div>
    <div class="stat-pill" style="color:#713f12">Brouillons <span><?= count(array_filter($formations, fn($f) => (int)$f['nb_brouillons'] > 0)) ?></span></div>
  </div>

  <div style="overflow-x:auto">
  <table class="niv-table">
    <thead>
      <tr>
        <th>#</th>
        <th>Formation</th>
        <th>Domaine</th>
        <th>Niveaux actifs</th>
        <th>Brouillons</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
    <?php if (empty($formations)): ?>
      <tr class="empty-row"><td colspan="6">Aucune formation trouvée.</td></tr>
    <?php else: ?>
      <?php foreach ($formations as $f): ?>
      <tr>
        <td style="color:#94a3b8;font-size:.72rem"><?= (int)$f['id'] ?></td>
        <td>
          <div style="font-weight:700;font-size:.83rem"><?= e($f['titre']) ?></div>
          <div style="font-size:.7rem;color:#64748b"><?= e($f['slug']) ?></div>
        </td>
        <td><span style="font-size:.75rem;color:#475569"><?= e($f['domaine'] ?? '—') ?></span></td>
        <td>
          <?php
          $actifs = $actifs_map[(int)$f['id']] ?? [];
          $niv_class = ['debutant'=>'niv-d','intermediaire'=>'niv-i','expert'=>'niv-e'];
          $niv_label = ['debutant'=>'Débutant','intermediaire'=>'Intermédiaire','expert'=>'Expert'];
          if (empty($actifs)): ?>
            <span style="color:#94a3b8;font-size:.75rem">Aucun</span>
          <?php else: foreach ($actifs as $ar): ?>
            <a href="modules.php?niveau_id=<?= (int)$ar['niveau_id'] ?>" class="niv-badge <?= $niv_class[$ar['niveau']] ?? '' ?>" style="text-decoration:none" title="Gérer les modules">
              <?= $niv_label[$ar['niveau']] ?? $ar['niveau'] ?>
            </a>
          <?php endforeach; endif; ?>
        </td>
        <td>
          <?php if ((int)$f['nb_brouillons'] > 0): ?>
            <span class="niv-badge niv-brouillon"><?= (int)$f['nb_brouillons'] ?> brouillon<?= (int)$f['nb_brouillons'] > 1 ? 's' : '' ?></span>
          <?php else: ?>
            <span style="color:#94a3b8;font-size:.75rem">—</span>
          <?php endif; ?>
        </td>
        <td>
          <a href="edit.php?id=<?= (int)$f['id'] ?>" class="btn-edit">✏️ Gérer</a>
        </td>
      </tr>
      <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
  </table>
  </div>

</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout/layout.php';
