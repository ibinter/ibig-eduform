<?php
declare(strict_types=1);
/**
 * ADMIN — Audit & correction des heures par niveau
 * Détecte les formations où les heures sont "ascendantes" (artefact de migration)
 * et propose une redistribution correcte : Débutant ≥ Intermédiaire ≥ Expert.
 */
require_once __DIR__ . '/../_init.php';
Middleware::requireAuth();

$pdo = Database::connect();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$pageTitle  = 'Audit heures par niveau';
$activeMenu = 'niveaux';

/* ─── Helpers ─────────────────────────────────────────────────── */
function r5h(float $v): int {
    return max(5, (int)(round($v / 5) * 5));
}

/**
 * Redistribue les heures de façon descendante depuis la durée de base.
 * Débutant = base × 1.00  (fondations, plus long)
 * Intermédiaire = base × 0.80
 * Expert = base × 0.60    (ciblé, plus court)
 */
function heures_descendantes(int $base): array {
    return [
        'debutant'      => r5h($base * 1.00),
        'intermediaire' => r5h($base * 0.80),
        'expert'        => r5h($base * 0.60),
    ];
}

/* ─── POST : correction ────────────────────────────────────────── */
$flash = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = trim((string)($_POST['action'] ?? ''));

    if ($action === 'fix_one') {
        $fid   = (int)($_POST['formation_id'] ?? 0);
        $base  = max(5, (int)($_POST['base_heures'] ?? 20));
        $scope = trim((string)($_POST['scope'] ?? 'all'));
        if ($fid > 0) {
            $heures = heures_descendantes($base);
            $statut_clause = ($scope === 'brouillon') ? " AND statut = 'brouillon'" : '';
            $nb = 0;
            foreach ($heures as $niv => $h) {
                $stmt = $pdo->prepare("
                    UPDATE formation_niveaux
                       SET duree_heures = :dh
                     WHERE formation_id = :fid AND niveau = :niv $statut_clause
                ");
                $stmt->execute([':dh' => $h, ':fid' => $fid, ':niv' => $niv]);
                $nb += $stmt->rowCount();
            }
            $flash = ['ok', "✅ $nb niveaux corrigés pour cette formation."];
        }

    } elseif ($action === 'fix_all_ascending') {
        /* Corrige toutes les formations où Deb < Expert (logique ascendante artefact) */
        $rows = $pdo->query("
            SELECT n.formation_id,
                   MAX(CASE WHEN n.niveau='debutant'      THEN n.duree_heures END) AS dh_d,
                   MAX(CASE WHEN n.niveau='intermediaire' THEN n.duree_heures END) AS dh_i,
                   MAX(CASE WHEN n.niveau='expert'        THEN n.duree_heures END) AS dh_e,
                   f.duree AS duree_raw
            FROM formation_niveaux n
            JOIN formations f ON f.id = n.formation_id
            GROUP BY n.formation_id, f.duree
            HAVING dh_d IS NOT NULL AND dh_e IS NOT NULL AND dh_d < dh_e
        ")->fetchAll(PDO::FETCH_ASSOC);

        $nb_forma = 0;
        $nb_niv   = 0;
        foreach ($rows as $r) {
            $base = (int)($r['dh_e'] ?? 20);
            if ($base < 5) $base = 20;
            $heures = heures_descendantes($base);
            foreach ($heures as $niv => $h) {
                $stmt = $pdo->prepare("
                    UPDATE formation_niveaux SET duree_heures = :dh
                     WHERE formation_id = :fid AND niveau = :niv
                ");
                $stmt->execute([':dh' => $h, ':fid' => (int)$r['formation_id'], ':niv' => $niv]);
                $nb_niv += $stmt->rowCount();
            }
            $nb_forma++;
        }
        $flash = ['ok', "✅ $nb_forma formations corrigées, $nb_niv niveaux mis à jour."];
    }
}

/* ─── Charger toutes les formations avec leurs heures par niveau ── */
$formations_data = $pdo->query("
    SELECT
        f.id,
        f.titre,
        f.domaine,
        f.duree AS duree_raw,
        MAX(CASE WHEN n.niveau='debutant'      THEN n.duree_heures END) AS dh_d,
        MAX(CASE WHEN n.niveau='intermediaire' THEN n.duree_heures END) AS dh_i,
        MAX(CASE WHEN n.niveau='expert'        THEN n.duree_heures END) AS dh_e,
        COUNT(n.id) AS nb_niveaux
    FROM formations f
    LEFT JOIN formation_niveaux n ON n.formation_id = f.id
    GROUP BY f.id, f.titre, f.domaine, f.duree
    ORDER BY f.titre ASC
")->fetchAll(PDO::FETCH_ASSOC);

/* Classifier */
$stats = ['ascending' => 0, 'ok' => 0, 'partial' => 0];
foreach ($formations_data as &$fd) {
    $d = $fd['dh_d']; $i = $fd['dh_i']; $e = $fd['dh_e'];
    if ($d === null && $i === null && $e === null) {
        $fd['_status'] = 'none';
    } elseif ($d !== null && $e !== null) {
        if ((int)$d < (int)$e) {
            $fd['_status'] = 'ascending'; // problème : Débutant < Expert
            $stats['ascending']++;
        } else {
            $fd['_status'] = 'ok';
            $stats['ok']++;
        }
    } else {
        $fd['_status'] = 'partial';
        $stats['partial']++;
    }
}
unset($fd);

$csrf = csrf_token();

ob_start();
?>
<style>
.ah-wrap { max-width: 1100px }
.ah-wrap h2 { font-size: 17px; font-weight: 700; margin: 0 0 4px }
.ah-sub { font-size: .78rem; color: #64748b; margin-bottom: 18px }
.breadcrumb { font-size: .78rem; color: #64748b; margin-bottom: 10px }
.breadcrumb a { color: #1e40af; text-decoration: none }

.flash-ok  { background:#dcfce7;color:#166534;border:1px solid #86efac;border-radius:7px;padding:9px 16px;font-size:.82rem;margin-bottom:14px;font-weight:700 }
.flash-err { background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;border-radius:7px;padding:9px 16px;font-size:.82rem;margin-bottom:14px }

.stats-row  { display:flex;gap:10px;flex-wrap:wrap;margin-bottom:18px }
.stat-card  { background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:12px 20px;min-width:130px;text-align:center }
.stat-card .nb  { font-size:1.5rem;font-weight:800 }
.stat-card .lbl { font-size:.72rem;color:#64748b;font-weight:600;text-transform:uppercase;letter-spacing:.04em }

.ah-explain { background:#fff7ed;border:1px solid #fed7aa;border-radius:10px;padding:12px 16px;font-size:.8rem;color:#92400e;margin-bottom:18px;line-height:1.6 }
.ah-explain strong { font-weight:800 }

.fix-all-bar { background:#fff;border:1px solid #fca5a5;border-radius:10px;padding:14px 18px;margin-bottom:18px;display:flex;align-items:center;gap:16px;flex-wrap:wrap }
.fix-all-bar p { margin:0;font-size:.82rem;color:#7f1d1d;flex:1 }
.btn-fix-all { padding:8px 18px;background:#dc2626;color:#fff;border:none;border-radius:7px;font-size:.8rem;font-weight:700;cursor:pointer }
.btn-fix-all:hover { background:#b91c1c }
.btn-fix-all:disabled { opacity:.45;cursor:default }

.filter-bar { display:flex;gap:8px;margin-bottom:12px;flex-wrap:wrap;align-items:center }
.filter-bar label { font-size:.78rem;font-weight:700;color:#475569 }
.flt-btn { padding:4px 13px;border-radius:999px;font-size:.75rem;font-weight:700;text-decoration:none;border:1px solid #e2e8f0;color:#475569;background:#f1f5f9;cursor:pointer }
.flt-btn.active,.flt-btn:hover { background:#1e40af;color:#fff;border-color:#1e40af }

.ah-table { width:100%;border-collapse:collapse;font-size:.8rem }
.ah-table th { background:#0a1733;color:#e8edf8;padding:6px 10px;text-align:left;font-size:.75rem;white-space:nowrap }
.ah-table td { padding:7px 10px;border-bottom:1px solid #f1f5f9;vertical-align:middle }
.ah-table tr:hover td { background:#f8fafc }

.h-badge { display:inline-block;padding:2px 9px;border-radius:999px;font-size:.78rem;font-weight:800 }
.h-d { background:#dcfce7;color:#166534 }
.h-i { background:#dbeafe;color:#1e40af }
.h-e { background:#fce7f3;color:#9d174d }

.status-asc { color:#dc2626;font-weight:800;font-size:.76rem }
.status-ok  { color:#16a34a;font-weight:700;font-size:.76rem }
.status-par { color:#b45309;font-weight:700;font-size:.76rem }

.arr-up   { color:#dc2626 }
.arr-down { color:#16a34a }
.arr-eq   { color:#b45309 }

.btn-fix-one { padding:4px 10px;font-size:.74rem;font-weight:700;background:#1e40af;color:#fff;border:none;border-radius:5px;cursor:pointer }
.btn-fix-one:hover { background:#1d4ed8 }

/* Modal */
.modal-bg { display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1000;align-items:center;justify-content:center }
.modal-bg.open { display:flex }
.modal { background:#fff;border-radius:14px;padding:24px 28px;max-width:440px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,.25) }
.modal h4 { margin:0 0 8px;font-size:15px }
.modal .sub { font-size:.78rem;color:#64748b;margin-bottom:16px }
.modal-row { display:flex;gap:10px;margin-bottom:14px }
.modal-row > div { flex:1 }
.modal-row label { font-size:.75rem;font-weight:700;color:#475569;display:block;margin-bottom:3px }
.modal-row input,.modal-row select { width:100%;padding:7px 9px;border:1px solid #e2e8f0;border-radius:6px;font-size:.82rem }
.modal-preview { background:#f8fafc;border-radius:8px;padding:10px 14px;font-size:.78rem;margin-bottom:14px }
.modal-preview .row { display:flex;justify-content:space-between;padding:2px 0 }
.modal-btns { display:flex;gap:10px;justify-content:flex-end }
.btn-cancel { padding:7px 16px;border:1px solid #e2e8f0;border-radius:6px;font-size:.8rem;font-weight:700;background:#fff;color:#475569;cursor:pointer }
.btn-confirm { padding:7px 16px;background:#1e40af;color:#fff;border:none;border-radius:6px;font-size:.8rem;font-weight:700;cursor:pointer }
.btn-confirm:hover { background:#1d4ed8 }
</style>

<div class="ah-wrap">
  <div class="breadcrumb"><a href="index.php">← Niveaux</a> / Audit heures</div>

  <h2>📊 Audit des heures par niveau</h2>
  <div class="ah-sub">Analyse de la distribution Débutant / Intermédiaire / Expert et détection des anomalies ascendantes</div>

  <?php if ($flash): ?>
    <div class="flash-<?= $flash[0] ?>"><?= e($flash[1]) ?></div>
  <?php endif; ?>

  <!-- Explication -->
  <div class="ah-explain">
    <strong>Règle correcte :</strong> Une formation peut librement avoir Débutant&nbsp;=&nbsp;30h, Intermédiaire&nbsp;=&nbsp;25h, Expert&nbsp;=&nbsp;15h.
    Les heures ne doivent PAS être forcées en hausse selon le niveau.<br>
    <strong>Problème détecté :</strong> La migration initiale a appliqué les coefficients × 1,0 / × 1,5 / × 2,0 —
    ce qui crée des niveaux Expert avec <em>plus</em> d'heures que Débutant. C'est l'inverse de la logique pédagogique correcte.<br>
    <strong>Correction proposée :</strong> Débutant&nbsp;=&nbsp;base × 1,00 · Intermédiaire&nbsp;=&nbsp;base × 0,80 · Expert&nbsp;=&nbsp;base × 0,60
    (base = heures actuelles du niveau Expert, qui correspond à la durée d'origine de la formation).
  </div>

  <!-- KPIs -->
  <div class="stats-row">
    <div class="stat-card">
      <div class="nb" style="color:#0f172a"><?= count($formations_data) ?></div>
      <div class="lbl">Total formations</div>
    </div>
    <div class="stat-card">
      <div class="nb" style="color:#dc2626"><?= $stats['ascending'] ?></div>
      <div class="lbl">⚠ Heures ascendantes</div>
    </div>
    <div class="stat-card">
      <div class="nb" style="color:#16a34a"><?= $stats['ok'] ?></div>
      <div class="lbl">✅ Distribution OK</div>
    </div>
    <div class="stat-card">
      <div class="nb" style="color:#b45309"><?= $stats['partial'] ?></div>
      <div class="lbl">Niveaux partiels</div>
    </div>
  </div>

  <!-- Correction globale -->
  <?php if ($stats['ascending'] > 0): ?>
  <div class="fix-all-bar">
    <p>
      <strong><?= $stats['ascending'] ?> formations</strong> ont des heures ascendantes (artefact de la migration initiale).
      La correction applique la formule descendante sur toutes ces formations automatiquement.
    </p>
    <form method="post" onsubmit="return confirm('Corriger les <?= $stats['ascending'] ?> formations avec heures ascendantes ? Cette action modifie les heures en base de données.')">
      <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
      <input type="hidden" name="action" value="fix_all_ascending">
      <button type="submit" class="btn-fix-all">⚡ Corriger toutes les formations ascendantes</button>
    </form>
  </div>
  <?php else: ?>
  <div style="background:#dcfce7;border:1px solid #86efac;border-radius:10px;padding:12px 16px;font-size:.82rem;color:#166534;font-weight:700;margin-bottom:18px">
    ✅ Aucune anomalie ascendante détectée. Toutes les distributions sont correctes.
  </div>
  <?php endif; ?>

  <!-- Filtre -->
  <div class="filter-bar">
    <label>Filtrer :</label>
    <button class="flt-btn active" onclick="filterTable('all', this)">Toutes</button>
    <button class="flt-btn" onclick="filterTable('ascending', this)">⚠ Ascendantes (<?= $stats['ascending'] ?>)</button>
    <button class="flt-btn" onclick="filterTable('ok', this)">✅ OK (<?= $stats['ok'] ?>)</button>
    <button class="flt-btn" onclick="filterTable('partial', this)">⚡ Partielles (<?= $stats['partial'] ?>)</button>
  </div>

  <!-- Tableau -->
  <div style="overflow-x:auto">
  <table class="ah-table" id="ahTable">
    <thead>
      <tr>
        <th>Formation</th>
        <th>Domaine</th>
        <th style="text-align:center">🟢 Débutant</th>
        <th style="text-align:center">→</th>
        <th style="text-align:center">🔵 Intermédiaire</th>
        <th style="text-align:center">→</th>
        <th style="text-align:center">🔴 Expert</th>
        <th style="text-align:center">Statut</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($formations_data as $fd):
        $d  = $fd['dh_d'] !== null ? (int)$fd['dh_d'] : null;
        $im = $fd['dh_i'] !== null ? (int)$fd['dh_i'] : null;
        $e  = $fd['dh_e'] !== null ? (int)$fd['dh_e'] : null;
        $st = $fd['_status'];

        // Flèches D→I et I→E
        $arrow_di = ($d !== null && $im !== null)
            ? ($d > $im ? '<span class="arr-down">↘ OK</span>' : ($d < $im ? '<span class="arr-up">↗ !</span>' : '='))
            : '—';
        $arrow_ie = ($im !== null && $e !== null)
            ? ($im > $e ? '<span class="arr-down">↘ OK</span>' : ($im < $e ? '<span class="arr-up">↗ !</span>' : '='))
            : '—';

        $status_html = match($st) {
            'ascending' => '<span class="status-asc">⚠ Ascendant</span>',
            'ok'        => '<span class="status-ok">✅ OK</span>',
            'partial'   => '<span class="status-par">⚡ Partiel</span>',
            default     => '<span style="color:#94a3b8">—</span>',
        };

        // Base pour correction = heures Expert (durée originale de la formation)
        $base_fix = $e ?? 20;
        $prop = heures_descendantes($base_fix);
    ?>
    <tr data-status="<?= $st ?>">
      <td style="max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
        <strong><?= e($fd['titre']) ?></strong>
        <?php if ($fd['duree_raw']): ?>
        <div style="font-size:.7rem;color:#94a3b8"><?= e($fd['duree_raw']) ?></div>
        <?php endif; ?>
      </td>
      <td style="font-size:.75rem;color:#64748b;white-space:nowrap"><?= e($fd['domaine'] ?? '—') ?></td>

      <td style="text-align:center">
        <?= $d !== null ? '<span class="h-badge h-d">' . $d . 'h</span>' : '<span style="color:#94a3b8">—</span>' ?>
      </td>
      <td style="text-align:center;font-size:.75rem"><?= $arrow_di ?></td>
      <td style="text-align:center">
        <?= $im !== null ? '<span class="h-badge h-i">' . $im . 'h</span>' : '<span style="color:#94a3b8">—</span>' ?>
      </td>
      <td style="text-align:center;font-size:.75rem"><?= $arrow_ie ?></td>
      <td style="text-align:center">
        <?= $e !== null ? '<span class="h-badge h-e">' . $e . 'h</span>' : '<span style="color:#94a3b8">—</span>' ?>
      </td>

      <td style="text-align:center"><?= $status_html ?></td>

      <td>
        <?php if ($st === 'ascending' || $st === 'ok' || $st === 'partial'): ?>
        <button class="btn-fix-one"
          onclick="openModal(<?= (int)$fd['id'] ?>, <?= $base_fix ?>, <?= $prop['debutant'] ?>, <?= $prop['intermediaire'] ?>, <?= $prop['expert'] ?>, '<?= e(addslashes($fd['titre'])) ?>')"
        >Corriger</button>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>

<!-- Modal correction unitaire -->
<div class="modal-bg" id="fixModal">
  <div class="modal">
    <h4>⚙ Corriger les heures</h4>
    <div class="sub" id="modalTitle">Formation</div>
    <form method="post" id="fixForm">
      <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
      <input type="hidden" name="action" value="fix_one">
      <input type="hidden" name="formation_id" id="mFid" value="">
      <div class="modal-row">
        <div>
          <label>Heures base (Expert)</label>
          <input type="number" name="base_heures" id="mBase" min="5" max="999" value="20" oninput="refreshPreview()">
        </div>
        <div>
          <label>Appliquer sur</label>
          <select name="scope">
            <option value="all">Tous les niveaux</option>
            <option value="brouillon">Brouillons seulement</option>
          </select>
        </div>
      </div>
      <div class="modal-preview">
        <div style="font-size:.75rem;font-weight:700;color:#475569;margin-bottom:6px">Résultat prévu (formule descendante)</div>
        <div class="row"><span>🟢 Débutant (× 1,00)</span><span id="prev-d">—</span></div>
        <div class="row"><span>🔵 Intermédiaire (× 0,80)</span><span id="prev-i">—</span></div>
        <div class="row"><span>🔴 Expert (× 0,60)</span><span id="prev-e">—</span></div>
      </div>
      <div class="modal-btns">
        <button type="button" class="btn-cancel" onclick="closeModal()">Annuler</button>
        <button type="submit" class="btn-confirm">✅ Appliquer</button>
      </div>
    </form>
  </div>
</div>

<script>
function r5h(v) { return Math.max(5, Math.round(v / 5) * 5); }

function openModal(fid, base, pd, pi, pe, titre) {
  document.getElementById('mFid').value = fid;
  document.getElementById('mBase').value = base;
  document.getElementById('modalTitle').textContent = titre;
  document.getElementById('prev-d').textContent = pd + 'h';
  document.getElementById('prev-i').textContent = pi + 'h';
  document.getElementById('prev-e').textContent = pe + 'h';
  document.getElementById('fixModal').classList.add('open');
}

function refreshPreview() {
  var base = parseInt(document.getElementById('mBase').value) || 20;
  document.getElementById('prev-d').textContent = r5h(base * 1.00) + 'h';
  document.getElementById('prev-i').textContent = r5h(base * 0.80) + 'h';
  document.getElementById('prev-e').textContent = r5h(base * 0.60) + 'h';
}

function closeModal() {
  document.getElementById('fixModal').classList.remove('open');
}
document.getElementById('fixModal').addEventListener('click', function(e) {
  if (e.target === this) closeModal();
});

function filterTable(status, btn) {
  document.querySelectorAll('.flt-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  document.querySelectorAll('#ahTable tbody tr').forEach(function(tr) {
    tr.style.display = (status === 'all' || tr.dataset.status === status) ? '' : 'none';
  });
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout/layout.php';
