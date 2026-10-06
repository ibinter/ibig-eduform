<?php
declare(strict_types=1);
/**
 * ADMIN — Génération batch objectifs/prérequis/public cible par IA
 */
require_once __DIR__ . '/../_init.php';
Middleware::requireAuth();

$pdo = Database::connect();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$pageTitle  = "Génération contenu niveaux";
$activeMenu = "niveaux";

$niv_filter = trim((string)($_GET['niv'] ?? ''));
$only_empty = (($_GET['vides'] ?? '1') !== '0');

$stats_row = $pdo->query("
    SELECT
        n.niveau,
        COUNT(n.id) AS total,
        SUM(CASE WHEN n.statut = 'actif' THEN 1 ELSE 0 END) AS actifs,
        SUM(CASE WHEN (n.objectifs IS NULL OR n.objectifs = '') THEN 1 ELSE 0 END) AS sans_contenu
    FROM formation_niveaux n
    WHERE n.statut = 'actif'
    GROUP BY n.niveau
    ORDER BY CASE n.niveau WHEN 'debutant' THEN 1 WHEN 'intermediaire' THEN 2 WHEN 'expert' THEN 3 END
")->fetchAll(PDO::FETCH_ASSOC);

$where  = ["n.statut = 'actif'"];
$params = [];
if (in_array($niv_filter, ['debutant','intermediaire','expert'], true)) {
    $where[] = "n.niveau = :niv";
    $params[':niv'] = $niv_filter;
}
if ($only_empty) {
    $where[] = "(n.objectifs IS NULL OR n.objectifs = '')";
}

$stmt = $pdo->prepare("
    SELECT n.id, n.niveau, n.duree_heures, n.objectifs, f.titre, f.domaine
    FROM formation_niveaux n
    JOIN formations f ON f.id = n.formation_id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY f.titre ASC, n.niveau ASC
    LIMIT 500
");
$stmt->execute($params);
$niveaux = $stmt->fetchAll(PDO::FETCH_ASSOC);

$niv_labels  = ['debutant'=>'Débutant','intermediaire'=>'Intermédiaire','expert'=>'Expert'];
$niv_classes = ['debutant'=>'niv-d','intermediaire'=>'niv-i','expert'=>'niv-e'];
$csrf  = csrf_token();
$total = count($niveaux);

ob_start();
?>
<style>
.gc-wrap{max-width:1150px}
.breadcrumb{font-size:.78rem;color:#64748b;margin-bottom:10px}
.breadcrumb a{color:#1e40af;text-decoration:none}
.gc-wrap h2{font-size:17px;font-weight:700;margin:0 0 4px}
.gc-sub{font-size:.78rem;color:#64748b;margin-bottom:18px}
.stats-row{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:18px}
.stat-card{background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:10px 18px;text-align:center;min-width:120px}
.stat-card .nb{font-size:1.4rem;font-weight:800}
.stat-card .lbl{font-size:.7rem;color:#64748b;font-weight:600;text-transform:uppercase}
.stat-card .sub{font-size:.65rem;color:#94a3b8}
.toolbar{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:14px}
.niv-flt{padding:5px 14px;border-radius:999px;font-size:.75rem;font-weight:700;text-decoration:none;border:1px solid #e2e8f0;background:#f1f5f9;color:#475569}
.niv-flt.active{background:#0a1733;color:#fff;border-color:#0a1733}
.niv-d{background:#dcfce7;color:#166534;border:1px solid #86efac}
.niv-i{background:#dbeafe;color:#1e40af;border:1px solid #93c5fd}
.niv-e{background:#fce7f3;color:#9d174d;border:1px solid #f9a8d4}
.toggle-vides{font-size:.75rem;padding:5px 12px;border:1px solid #e2e8f0;border-radius:6px;background:#fff;cursor:pointer;color:#475569;text-decoration:none}
.toggle-vides.on{background:#fef9c3;border-color:#fde047;color:#713f12}
.gc-table{width:100%;border-collapse:collapse;font-size:.81rem}
.gc-table th{background:#0a1733;color:#e8edf8;padding:7px 10px;text-align:left;white-space:nowrap;font-size:.75rem}
.gc-table td{padding:6px 10px;border-bottom:1px solid #f1f5f9;vertical-align:top}
.gc-table tr:hover td{background:#f8fafc}
.niv-badge{display:inline-block;font-size:.68rem;font-weight:700;padding:2px 8px;border-radius:999px;white-space:nowrap}
.has-content{font-size:.7rem;color:#166534;background:#dcfce7;border:1px solid #86efac;padding:2px 8px;border-radius:4px}
.no-content{font-size:.7rem;color:#991b1b;background:#fee2e2;border:1px solid #fca5a5;padding:2px 8px;border-radius:4px}
.btn-gen{padding:4px 12px;background:#7c3aed;color:#fff;border:none;border-radius:5px;font-size:.73rem;font-weight:700;cursor:pointer;white-space:nowrap}
.btn-gen:hover{background:#6d28d9}
.btn-gen:disabled{opacity:.4;cursor:not-allowed}
.btn-gen-all{padding:6px 16px;background:#1e40af;color:#fff;border:none;border-radius:6px;font-size:.8rem;font-weight:700;cursor:pointer}
.btn-gen-all:hover{background:#1d4ed8}
.btn-gen-all:disabled{opacity:.5;cursor:not-allowed}
.row-status{display:inline-block;font-size:.7rem;font-weight:700;padding:2px 8px;border-radius:4px;margin-left:6px}
.status-ok{background:#dcfce7;color:#166534}
.status-err{background:#fee2e2;color:#991b1b}
.status-loading{background:#dbeafe;color:#1e40af;animation:pulse .8s infinite alternate}
@keyframes pulse{from{opacity:.7}to{opacity:1}}
.progress-bar{height:4px;background:#e2e8f0;border-radius:2px;margin-top:12px;overflow:hidden;display:none}
.progress-fill{height:100%;background:#7c3aed;border-radius:2px;transition:width .3s}
.batch-info{font-size:.8rem;color:#475569;margin-top:8px}
</style>

<div class="gc-wrap">

  <div class="breadcrumb"><a href="index.php">← Niveaux</a> / Génération contenu IA</div>

  <h2>🎯 Génération objectifs / prérequis / public cible</h2>
  <div class="gc-sub">Génère automatiquement les contenus différenciés par niveau (objectifs pédagogiques, prérequis, public cible) via Claude AI.</div>

  <div class="stats-row">
    <?php foreach ($stats_row as $s): ?>
    <div class="stat-card">
      <div class="nb" style="color:<?= ['debutant'=>'#166534','intermediaire'=>'#1e40af','expert'=>'#9d174d'][$s['niveau']] ?>"><?= (int)$s['sans_contenu'] ?></div>
      <div class="lbl"><?= $niv_labels[$s['niveau']] ?></div>
      <div class="sub">sans contenu / <?= (int)$s['actifs'] ?> actifs</div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="toolbar">
    <a href="?niv=&vides=<?= $only_empty ? '1' : '0' ?>" class="niv-flt <?= $niv_filter === '' ? 'active' : '' ?>">Tous</a>
    <?php foreach (['debutant','intermediaire','expert'] as $nv): ?>
    <a href="?niv=<?= $nv ?>&vides=<?= $only_empty ? '1' : '0' ?>" class="niv-flt niv-<?= $nv[0] ?>"><?= $niv_labels[$nv] ?></a>
    <?php endforeach; ?>
    <a href="?niv=<?= e($niv_filter) ?>&vides=<?= $only_empty ? '0' : '1' ?>" class="toggle-vides <?= $only_empty ? 'on' : '' ?>">
      <?= $only_empty ? '✅ Sans contenu seulement' : '📋 Tous' ?>
    </a>
    <span style="margin-left:auto;font-size:.75rem;color:#64748b"><?= $total ?> niveaux affichés</span>
  </div>

  <?php if ($total > 0): ?>
  <div style="margin-bottom:14px;display:flex;align-items:center;gap:12px;flex-wrap:wrap">
    <button id="btnBatchAll" class="btn-gen-all" onclick="genAll()">
      🤖 Générer tous (<?= $total ?>) — séquentiel
    </button>
    <span class="batch-info" id="batchInfo"></span>
  </div>
  <div class="progress-bar" id="progressBar">
    <div class="progress-fill" id="progressFill" style="width:0%"></div>
  </div>
  <?php endif; ?>

  <div style="overflow-x:auto;margin-top:12px">
  <table class="gc-table" id="gcTable">
    <thead>
      <tr>
        <th>#</th>
        <th>Formation</th>
        <th>Niveau</th>
        <th>Durée</th>
        <th>Contenu</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
    <?php if (empty($niveaux)): ?>
      <tr><td colspan="6" style="text-align:center;padding:30px;color:#94a3b8;font-style:italic">
        <?= $only_empty ? '✅ Tous les niveaux actifs ont déjà leur contenu !' : 'Aucun niveau trouvé.' ?>
      </td></tr>
    <?php else: ?>
      <?php foreach ($niveaux as $n): $hasContent = !empty($n['objectifs']); ?>
      <tr id="row-<?= (int)$n['id'] ?>">
        <td style="color:#94a3b8;font-size:.7rem"><?= (int)$n['id'] ?></td>
        <td>
          <div style="font-weight:700;font-size:.82rem"><?= e($n['titre']) ?></div>
          <div style="font-size:.68rem;color:#94a3b8"><?= e($n['domaine'] ?? '') ?></div>
        </td>
        <td><span class="niv-badge <?= $niv_classes[$n['niveau']] ?? '' ?>"><?= $niv_labels[$n['niveau']] ?? $n['niveau'] ?></span></td>
        <td style="color:#475569"><?= (int)$n['duree_heures'] ?>h</td>
        <td>
          <span id="content-status-<?= (int)$n['id'] ?>" class="<?= $hasContent ? 'has-content' : 'no-content' ?>">
            <?= $hasContent ? '✓ Renseigné' : '⚠ Vide' ?>
          </span>
        </td>
        <td>
          <button class="btn-gen" id="btn-<?= (int)$n['id'] ?>"
                  onclick="genOne(<?= (int)$n['id'] ?>, this)"
                  data-id="<?= (int)$n['id'] ?>">
            🤖 Générer
          </button>
          <span class="row-status" id="status-<?= (int)$n['id'] ?>" style="display:none"></span>
        </td>
      </tr>
      <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
  </table>
  </div>

</div>

<script>
const CSRF = <?= json_encode($csrf) ?>;
const API  = 'api-gen-content.php';
let batchRunning = false;

async function genOne(nid, btn) {
    if (btn) btn.disabled = true;
    const statusEl  = document.getElementById('status-' + nid);
    const csEl      = document.getElementById('content-status-' + nid);
    statusEl.textContent = '⏳ Génération…';
    statusEl.className   = 'row-status status-loading';
    statusEl.style.display = '';

    try {
        const fd = new FormData();
        fd.append('csrf', CSRF);
        fd.append('niveau_id', nid);
        const r = await fetch(API, {method:'POST', body: fd});
        const d = await r.json();
        if (d.ok) {
            statusEl.textContent = '✅ Contenu généré';
            statusEl.className   = 'row-status status-ok';
            if (csEl) { csEl.textContent = '✓ Renseigné'; csEl.className = 'has-content'; }
        } else {
            statusEl.textContent = '❌ ' + (d.error || 'Erreur');
            statusEl.className   = 'row-status status-err';
            if (btn) btn.disabled = false;
        }
    } catch(e) {
        statusEl.textContent = '❌ Réseau';
        statusEl.className   = 'row-status status-err';
        if (btn) btn.disabled = false;
    }
}

async function genAll() {
    if (batchRunning) return;
    batchRunning = true;
    const allBtns = document.querySelectorAll('.btn-gen:not(:disabled)');
    const ids = [...allBtns].map(b => parseInt(b.dataset.id)).filter(x => x > 0);
    if (!ids.length) { alert('Aucun niveau à générer.'); batchRunning = false; return; }
    if (!confirm('Générer le contenu de ' + ids.length + ' niveaux séquentiellement ?\nCette opération peut prendre plusieurs minutes.')) {
        batchRunning = false; return;
    }

    document.getElementById('btnBatchAll').disabled = true;
    const bar  = document.getElementById('progressBar');
    const fill = document.getElementById('progressFill');
    const info = document.getElementById('batchInfo');
    bar.style.display = '';
    let done = 0, ok = 0, err = 0;

    for (const nid of ids) {
        info.textContent = (done + 1) + ' / ' + ids.length + ' — en cours…';
        fill.style.width = Math.round((done / ids.length) * 100) + '%';
        const btn = document.getElementById('btn-' + nid);
        await genOne(nid, btn);
        done++;
        const s = document.getElementById('status-' + nid);
        if (s && s.classList.contains('status-ok')) ok++;
        else err++;
        await new Promise(r => setTimeout(r, 600));
    }
    fill.style.width = '100%';
    info.textContent = '✅ Terminé : ' + ok + ' générés, ' + err + ' erreurs.';
    batchRunning = false;
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout/layout.php';
