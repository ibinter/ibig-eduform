<?php
declare(strict_types=1);
/**
 * ADMIN — Activation en masse des niveaux brouillons
 * Permet d'activer tous les brouillons d'un type de niveau en un clic,
 * ou par sélection manuelle, avec aperçu avant action.
 */
require_once __DIR__ . '/../_init.php';
Middleware::requireAuth();
$u = auth_user();

$pdo = Database::connect();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$pageTitle  = "Activation en masse — Niveaux";
$activeMenu = "niveaux";

$flash = null;

/* ── POST : activation ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $type   = trim((string)($_POST['type_action'] ?? ''));
    $niveau = trim((string)($_POST['niveau_cible'] ?? ''));
    $ids    = array_map('intval', (array)($_POST['ids'] ?? []));

    if ($type === 'activate_all_niveau' && in_array($niveau, ['debutant','intermediaire','expert'], true)) {
        $stmt = $pdo->prepare("UPDATE formation_niveaux SET statut='actif' WHERE niveau=:niv AND statut='brouillon'");
        $stmt->execute([':niv' => $niveau]);
        $nb = $stmt->rowCount();
        $flash = ['ok', "✅ $nb niveaux \"$niveau\" activés."];

    } elseif ($type === 'activate_all' ) {
        $stmt = $pdo->prepare("UPDATE formation_niveaux SET statut='actif' WHERE statut='brouillon'");
        $stmt->execute();
        $nb = $stmt->rowCount();
        $flash = ['ok', "✅ $nb niveaux brouillons activés (tous types)."];

    } elseif ($type === 'activate_ids' && !empty($ids)) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("UPDATE formation_niveaux SET statut='actif' WHERE id IN ($placeholders) AND statut='brouillon'");
        $stmt->execute($ids);
        $nb = $stmt->rowCount();
        $flash = ['ok', "✅ $nb niveaux sélectionnés activés."];

    } else {
        $flash = ['err', 'Action invalide.'];
    }
}

/* ── Stats brouillons ── */
$stats = $pdo->query("
    SELECT niveau, COUNT(*) as nb
    FROM formation_niveaux
    WHERE statut = 'brouillon'
    GROUP BY niveau
    ORDER BY FIELD(niveau,'debutant','intermediaire','expert')
")->fetchAll(PDO::FETCH_ASSOC);

$total_brouillons = array_sum(array_column($stats, 'nb'));

/* ── Liste brouillons (prévisualisation) ── */
$niv_filter = trim((string)($_GET['niv'] ?? ''));
$preview_where = "WHERE statut = 'brouillon'";
$preview_params = [];
if (in_array($niv_filter, ['debutant','intermediaire','expert'], true)) {
    $preview_where .= " AND niveau = :niv";
    $preview_params[':niv'] = $niv_filter;
}
$previews = $pdo->prepare("
    SELECT n.id, n.niveau, n.duree_heures, n.tarif_en_ligne, n.tarif_presentiel,
           f.titre, f.slug
    FROM formation_niveaux n
    JOIN formations f ON f.id = n.formation_id
    $preview_where
    ORDER BY f.titre ASC, n.ordre_affichage ASC
    LIMIT 200
");
$previews->execute($preview_params);
$previews = $previews->fetchAll(PDO::FETCH_ASSOC);

$niv_labels  = ['debutant'=>'Débutant','intermediaire'=>'Intermédiaire','expert'=>'Expert'];
$niv_classes = ['debutant'=>'niv-d','intermediaire'=>'niv-i','expert'=>'niv-e'];
$csrf = csrf_token();

ob_start();
?>
<style>
.bulk-wrap{max-width:1050px}
.bulk-wrap h2{font-size:17px;font-weight:700;margin:0 0 4px}
.bulk-wrap .sub{font-size:.78rem;color:#64748b;margin-bottom:18px}
.breadcrumb{font-size:.78rem;color:#64748b;margin-bottom:10px}
.breadcrumb a{color:#1e40af;text-decoration:none}
.flash-ok{background:#dcfce7;color:#166534;border:1px solid #86efac;border-radius:7px;padding:9px 16px;font-size:.82rem;margin-bottom:14px;font-weight:700}
.flash-err{background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;border-radius:7px;padding:9px 16px;font-size:.82rem;margin-bottom:14px}
.stats-row{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:18px}
.stat-card{background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:12px 20px;min-width:140px;text-align:center}
.stat-card .nb{font-size:1.6rem;font-weight:800;color:#1e40af}
.stat-card .lbl{font-size:.72rem;color:#64748b;font-weight:600;text-transform:uppercase;letter-spacing:.04em}
.action-cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;margin-bottom:24px}
.action-card{background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:16px}
.action-card h4{font-size:.85rem;font-weight:700;margin:0 0 8px;color:#0a1733}
.action-card p{font-size:.75rem;color:#64748b;margin:0 0 12px;line-height:1.4}
.btn-activate{padding:7px 16px;border:none;border-radius:6px;font-size:.8rem;font-weight:700;cursor:pointer}
.btn-act-debutant{background:#dcfce7;color:#166534}
.btn-act-debutant:hover{background:#bbf7d0}
.btn-act-inter{background:#dbeafe;color:#1e40af}
.btn-act-inter:hover{background:#bfdbfe}
.btn-act-expert{background:#fce7f3;color:#9d174d}
.btn-act-expert:hover{background:#fbcfe8}
.btn-act-all{background:#f1f5f9;color:#334155;border:1px solid #e2e8f0}
.btn-act-all:hover{background:#e2e8f0}
.preview-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;flex-wrap:wrap;gap:8px}
.preview-header h3{font-size:.9rem;font-weight:700;margin:0}
.niv-filter-btns{display:flex;gap:6px}
.niv-flt{padding:4px 12px;border-radius:999px;font-size:.74rem;font-weight:700;text-decoration:none;border:1px solid transparent}
.niv-flt-all{background:#f1f5f9;color:#475569;border-color:#e2e8f0}
.niv-flt.niv-d{background:#dcfce7;color:#166534;border-color:#86efac}
.niv-flt.niv-i{background:#dbeafe;color:#1e40af;border-color:#93c5fd}
.niv-flt.niv-e{background:#fce7f3;color:#9d174d;border-color:#f9a8d4}
.prev-table{width:100%;border-collapse:collapse;font-size:.8rem}
.prev-table th{background:#0a1733;color:#e8edf8;padding:6px 10px;text-align:left;font-size:.75rem}
.prev-table td{padding:5px 10px;border-bottom:1px solid #f1f5f9}
.prev-table tr:hover td{background:#f8fafc}
.niv-badge{display:inline-block;font-size:.68rem;font-weight:700;padding:2px 8px;border-radius:999px}
.niv-d{background:#dcfce7;color:#166534;border:1px solid #86efac}
.niv-i{background:#dbeafe;color:#1e40af;border:1px solid #93c5fd}
.niv-e{background:#fce7f3;color:#9d174d;border:1px solid #f9a8d4}
.chk-col{width:32px;text-align:center}
.btn-activate-sel{padding:7px 16px;background:#1e40af;color:#fff;border:none;border-radius:6px;font-size:.8rem;font-weight:700;cursor:pointer;margin-bottom:12px}
.btn-activate-sel:hover{background:#1d4ed8}
</style>

<div class="bulk-wrap">

  <div class="breadcrumb"><a href="index.php">← Niveaux</a> / Activation en masse</div>

  <h2>⚡ Activation en masse des niveaux</h2>
  <div class="sub">Actuellement <?= $total_brouillons ?> niveaux en statut brouillon</div>

  <?php if ($flash): ?>
    <div class="flash-<?= $flash[0] ?>"><?= e($flash[1]) ?></div>
  <?php endif; ?>

  <!-- Stats -->
  <div class="stats-row">
    <div class="stat-card"><div class="nb"><?= $total_brouillons ?></div><div class="lbl">Total brouillons</div></div>
    <?php foreach ($stats as $s): ?>
    <div class="stat-card">
      <div class="nb" style="color:<?= ['debutant'=>'#166534','intermediaire'=>'#1e40af','expert'=>'#9d174d'][$s['niveau']] ?>"><?= (int)$s['nb'] ?></div>
      <div class="lbl"><?= $niv_labels[$s['niveau']] ?></div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Actions rapides -->
  <div class="action-cards">
    <?php foreach (['debutant','intermediaire','expert'] as $nv):
      $nb_nv = 0;
      foreach ($stats as $s) { if ($s['niveau'] === $nv) $nb_nv = (int)$s['nb']; }
    ?>
    <div class="action-card">
      <h4><span class="niv-badge niv-<?= $nv[0] ?>"><?= $niv_labels[$nv] ?></span></h4>
      <p><?= $nb_nv ?> niveaux en brouillon.<br>Tous seront activés immédiatement.</p>
      <form method="post" onsubmit="return confirm('Activer tous les <?= $nb_nv ?> niveaux <?= $niv_labels[$nv] ?> brouillons ?')">
        <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
        <input type="hidden" name="type_action" value="activate_all_niveau">
        <input type="hidden" name="niveau_cible" value="<?= $nv ?>">
        <button type="submit" class="btn-activate btn-act-<?= $nv === 'debutant' ? 'debutant' : ($nv === 'intermediaire' ? 'inter' : 'expert') ?>"
          <?= $nb_nv === 0 ? 'disabled title="Aucun brouillon"' : '' ?>>
          ✅ Activer tous les <?= $niv_labels[$nv] ?>
        </button>
      </form>
    </div>
    <?php endforeach; ?>

    <div class="action-card" style="border-color:#fca5a5">
      <h4>⚡ Tout activer</h4>
      <p><?= $total_brouillons ?> niveaux brouillons au total.<br><strong style="color:#dc2626">Action irréversible.</strong></p>
      <form method="post" onsubmit="return confirm('Activer TOUS les <?= $total_brouillons ?> niveaux brouillons ? Cette action est irréversible.')">
        <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
        <input type="hidden" name="type_action" value="activate_all">
        <button type="submit" class="btn-activate btn-act-all" <?= $total_brouillons === 0 ? 'disabled' : '' ?>>
          ⚡ Activer tous les brouillons
        </button>
      </form>
    </div>
  </div>

  <!-- Prévisualisation + sélection manuelle -->
  <div class="preview-header">
    <h3>📋 Aperçu des brouillons <?= $niv_filter ? '— ' . $niv_labels[$niv_filter] : '' ?></h3>
    <div class="niv-filter-btns">
      <a href="?niv=" class="niv-flt niv-flt-all <?= $niv_filter === '' ? 'active' : '' ?>">Tous</a>
      <?php foreach (['debutant','intermediaire','expert'] as $nv): ?>
      <a href="?niv=<?= $nv ?>" class="niv-flt niv-<?= $nv[0] ?>"><?= $niv_labels[$nv] ?></a>
      <?php endforeach; ?>
    </div>
  </div>

  <?php if (empty($previews)): ?>
    <p style="color:#22c55e;font-weight:700">✅ Aucun brouillon<?= $niv_filter ? ' pour ce niveau' : '' ?>.</p>
  <?php else: ?>
  <form method="post" id="selForm">
    <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
    <input type="hidden" name="type_action" value="activate_ids">
    <button type="submit" class="btn-activate-sel" onclick="return selCount() > 0 || (alert('Sélectionnez au moins un niveau.'), false)">
      ✅ Activer la sélection (<span id="selCount">0</span>)
    </button>
    <div style="overflow-x:auto">
    <table class="prev-table">
      <thead>
        <tr>
          <th class="chk-col"><input type="checkbox" id="chkAll" onchange="toggleAll(this)"></th>
          <th>Formation</th>
          <th>Niveau</th>
          <th>Durée</th>
          <th>Tarif en ligne</th>
          <th>Tarif présentiel</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($previews as $p): ?>
      <tr>
        <td class="chk-col"><input type="checkbox" name="ids[]" value="<?= (int)$p['id'] ?>" class="row-chk" onchange="updateCount()"></td>
        <td>
          <div style="font-weight:600;font-size:.8rem"><?= e($p['titre']) ?></div>
          <div style="font-size:.7rem;color:#94a3b8"><?= e($p['slug']) ?></div>
        </td>
        <td><span class="niv-badge niv-<?= $p['niveau'][0] ?>"><?= $niv_labels[$p['niveau']] ?></span></td>
        <td><?= (int)$p['duree_heures'] ?>h</td>
        <td><?= number_format((int)$p['tarif_en_ligne'], 0, ',', ' ') ?> F</td>
        <td><?= number_format((int)$p['tarif_presentiel'], 0, ',', ' ') ?> F</td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    </div>
  </form>
  <?php endif; ?>

</div>

<script>
function updateCount() {
  var n = document.querySelectorAll('.row-chk:checked').length;
  document.getElementById('selCount').textContent = n;
}
function toggleAll(cb) {
  document.querySelectorAll('.row-chk').forEach(function(c){ c.checked = cb.checked; });
  updateCount();
}
function selCount() {
  return document.querySelectorAll('.row-chk:checked').length;
}
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout/layout.php';
