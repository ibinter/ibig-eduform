<?php
declare(strict_types=1);
/**
 * ADMIN — Modules d'un niveau de formation
 */
require_once __DIR__ . '/../_init.php';
Middleware::requireAuth();

$pdo = Database::connect();

$niveau_id = (int)($_GET['niveau_id'] ?? 0);
$titre     = trim((string)($_GET['titre'] ?? ''));
$niv       = trim((string)($_GET['niveau'] ?? ''));

if (!$niveau_id) { http_response_code(400); die('niveau_id requis.'); }

// Charger le niveau
$niveau = $pdo->prepare("
    SELECT n.*, f.titre AS f_titre, f.slug AS f_slug, f.id AS f_id
    FROM formation_niveaux n
    JOIN formations f ON f.id = n.formation_id
    WHERE n.id = :id
");
$niveau->execute([':id' => $niveau_id]);
$niveau = $niveau->fetch();
if (!$niveau) { http_response_code(404); die('Niveau introuvable.'); }

$msg = '';
$err = '';

// ── Actions POST ─────────────────────────────────────────────────────
$action = $_POST['action'] ?? '';

if ($action === 'add') {
    $ordre    = max(1, (int)$_POST['ordre'] ?? 1);
    $t        = trim($_POST['titre_module'] ?? '');
    $contenus = trim($_POST['contenus'] ?? '');
    $duree    = max(1, (int)($_POST['duree_heures'] ?? 2));
    if ($t !== '') {
        // Décaler les ordres existants
        $pdo->prepare("UPDATE formation_niveau_modules SET ordre = ordre + 1 WHERE niveau_id = :nid AND ordre >= :ord")
            ->execute([':nid' => $niveau_id, ':ord' => $ordre]);
        $pdo->prepare("INSERT INTO formation_niveau_modules (niveau_id, ordre, titre, contenus, duree_heures) VALUES (:nid,:ord,:t,:c,:d)")
            ->execute([':nid' => $niveau_id, ':ord' => $ordre, ':t' => $t, ':c' => $contenus, ':d' => $duree]);
        $msg = '✅ Module ajouté.';
    }
} elseif ($action === 'update') {
    $mid      = (int)$_POST['module_id'];
    $t        = trim($_POST['titre_module'] ?? '');
    $contenus = trim($_POST['contenus'] ?? '');
    $duree    = max(1, (int)($_POST['duree_heures'] ?? 2));
    $ord      = max(1, (int)($_POST['ordre'] ?? 1));
    $pdo->prepare("UPDATE formation_niveau_modules SET titre=:t, contenus=:c, duree_heures=:d, ordre=:o WHERE id=:id AND niveau_id=:nid")
        ->execute([':t' => $t, ':c' => $contenus, ':d' => $duree, ':o' => $ord, ':id' => $mid, ':nid' => $niveau_id]);
    $msg = '✅ Module mis à jour.';
} elseif ($action === 'delete') {
    $mid = (int)$_POST['module_id'];
    $pdo->prepare("DELETE FROM formation_niveau_modules WHERE id=:id AND niveau_id=:nid")
        ->execute([':id' => $mid, ':nid' => $niveau_id]);
    $msg = 'Module supprimé.';
} elseif ($action === 'reorder') {
    // Reorder via AJAX (ids[]=1&ids[]=2…)
    $ids = array_map('intval', $_POST['ids'] ?? []);
    foreach ($ids as $i => $mid) {
        $pdo->prepare("UPDATE formation_niveau_modules SET ordre=:o WHERE id=:id AND niveau_id=:nid")
            ->execute([':o' => $i + 1, ':id' => $mid, ':nid' => $niveau_id]);
    }
    echo json_encode(['ok' => true]);
    exit;
}

// Charger les modules
$modules = $pdo->prepare("
    SELECT * FROM formation_niveau_modules
    WHERE niveau_id = :nid
    ORDER BY ordre ASC
");
$modules->execute([':nid' => $niveau_id]);
$modules = $modules->fetchAll();

$total_h = array_sum(array_column($modules, 'duree_heures'));

$niv_labels = ['debutant' => '🟢 Débutant', 'intermediaire' => '🔵 Intermédiaire', 'expert' => '🟣 Expert'];

$pageTitle  = 'Modules — ' . ($niv_labels[$niveau['niveau']] ?? $niveau['niveau']);
$activeMenu = 'niveaux';

ob_start();
?>

<style>
.mo-page { max-width:1100px; margin:24px auto; padding:0 16px; }
.mo-header { display:flex; align-items:center; gap:16px; margin-bottom:20px; flex-wrap:wrap; }
.mo-title { font-size:1.3rem; font-weight:700; color:#0a1733; flex:1; }
.mo-meta { background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:12px 16px; margin-bottom:20px; font-size:.875rem; }
.mo-meta strong { display:block; margin-bottom:4px; }
.mo-list { border:1px solid #e2e8f0; border-radius:12px; overflow:hidden; margin-bottom:24px; }
.mo-item { display:grid; grid-template-columns:30px 40px 1fr 60px 120px; gap:12px; padding:14px 16px; align-items:start; border-bottom:1px solid #f1f5f9; background:#fff; cursor:grab; }
.mo-item:last-child { border-bottom:none; }
.mo-item:hover { background:#fafafa; }
.mo-drag { color:#94a3b8; font-size:1.1rem; align-self:center; }
.mo-num { color:#64748b; font-size:.875rem; font-weight:700; align-self:center; text-align:center; }
.mo-titre { font-weight:600; color:#0a1733; font-size:.9rem; }
.mo-contenus { font-size:.8rem; color:#64748b; margin-top:3px; white-space:pre-wrap; }
.mo-duree { text-align:center; align-self:center; }
.mo-duree strong { display:block; color:#0a1733; }
.mo-duree small { color:#64748b; font-size:.75rem; }
.mo-actions { display:flex; gap:6px; align-self:center; }
.mo-btn { border:1px solid #e2e8f0; background:#fff; border-radius:6px; padding:5px 9px; cursor:pointer; font-size:.8rem; }
.mo-btn:hover { background:#f1f5f9; }
.mo-btn-del { border-color:#fca5a5; color:#dc2626; }
.mo-btn-del:hover { background:#fee2e2; }
.mo-add-card { background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:20px; }
.mo-add-title { font-size:1rem; font-weight:700; color:#0a1733; margin-bottom:16px; }
.mo-field { margin-bottom:12px; }
.mo-field label { display:block; font-size:.8rem; font-weight:600; color:#374151; margin-bottom:4px; }
.mo-field input, .mo-field textarea { width:100%; border:1px solid #d1d5db; border-radius:8px; padding:8px 12px; font-size:.875rem; box-sizing:border-box; }
.mo-field textarea { min-height:70px; resize:vertical; }
.mo-field input:focus, .mo-field textarea:focus { outline:none; border-color:#0a1733; }
.mo-grid3 { display:grid; grid-template-columns:1fr 1fr 80px; gap:12px; }
.mo-sbtn { background:#0a1733; color:#fff; border:none; border-radius:8px; padding:10px 20px; cursor:pointer; font-size:.875rem; }
.mo-sbtn:hover { background:#1e3a6e; }
.mo-msg { padding:10px 14px; border-radius:8px; margin-bottom:16px; font-size:.875rem; }
.mo-msg.ok { background:#dcfce7; color:#166534; }
.mo-msg.err { background:#fee2e2; color:#991b1b; }
.mo-total { text-align:right; font-size:.875rem; color:#64748b; padding:10px 16px; border-top:1px solid #f1f5f9; }
.mo-edit-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:1000; align-items:center; justify-content:center; }
.mo-edit-overlay.open { display:flex; }
.mo-edit-box { background:#fff; border-radius:12px; padding:24px; width:100%; max-width:600px; max-height:90vh; overflow-y:auto; }
</style>

<div class="mo-page">
  <div class="mo-header">
    <div class="mo-title"><?= $pageTitle ?></div>
    <a href="edit.php?id=<?= (int)$niveau['f_id'] ?>" style="color:#64748b;font-size:.875rem">← Retour formation</a>
    <a href="index.php" style="color:#64748b;font-size:.875rem;margin-left:8px">↑ Liste niveaux</a>
  </div>

  <?php if ($msg): ?><div class="mo-msg ok"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <?php if ($err): ?><div class="mo-msg err"><?= htmlspecialchars($err) ?></div><?php endif; ?>

  <div class="mo-meta">
    <strong><?= htmlspecialchars($niveau['f_titre']) ?> — <?= $niv_labels[$niveau['niveau']] ?? $niveau['niveau'] ?></strong>
    <span>
      ⏱ Durée du niveau : <?= $niveau['duree_heures'] ?>h prévues —
      Modules en base : <?= $total_h ?>h (<?= count($modules) ?> modules)
      <?php if ($total_h !== (int)$niveau['duree_heures']): ?>
      <span style="color:#d97706;font-weight:600"> ⚠ Écart de <?= abs($total_h - (int)$niveau['duree_heures']) ?>h</span>
      <?php else: ?>
      <span style="color:#059669"> ✅ Durée équilibrée</span>
      <?php endif; ?>
    </span>
  </div>

  <!-- Liste des modules -->
  <div class="mo-list" id="moList">
    <?php if (empty($modules)): ?>
    <div style="padding:30px;text-align:center;color:#94a3b8">Aucun module. Ajoutez-en un ci-dessous.</div>
    <?php else: ?>
    <?php foreach ($modules as $m): ?>
    <div class="mo-item" data-id="<?= $m['id'] ?>">
      <span class="mo-drag">⠿</span>
      <span class="mo-num"><?= $m['ordre'] ?></span>
      <div>
        <div class="mo-titre"><?= htmlspecialchars($m['titre']) ?></div>
        <?php if ($m['contenus']): ?>
        <div class="mo-contenus"><?= htmlspecialchars(mb_substr($m['contenus'], 0, 180)) ?><?= strlen($m['contenus']) > 180 ? '…' : '' ?></div>
        <?php endif; ?>
      </div>
      <div class="mo-duree">
        <strong><?= $m['duree_heures'] ?>h</strong>
        <small>durée</small>
      </div>
      <div class="mo-actions">
        <button type="button" class="mo-btn" onclick="openEdit(<?= $m['id'] ?>,<?= $m['ordre'] ?>,<?= json_encode($m['titre']) ?>,<?= json_encode($m['contenus'] ?? '') ?>,<?= $m['duree_heures'] ?>)">✏️</button>
        <form method="post" style="margin:0" onsubmit="return confirm('Supprimer ce module ?')">
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="module_id" value="<?= $m['id'] ?>">
          <button type="submit" class="mo-btn mo-btn-del">🗑</button>
        </form>
      </div>
    </div>
    <?php endforeach; ?>
    <div class="mo-total">Total : <strong><?= $total_h ?>h</strong> / <?= $niveau['duree_heures'] ?>h prévues</div>
    <?php endif; ?>
  </div>

  <!-- Ajout d'un module -->
  <div class="mo-add-card">
    <div class="mo-add-title">➕ Ajouter un module</div>
    <form method="post">
      <input type="hidden" name="action" value="add">
      <div class="mo-grid3">
        <div class="mo-field">
          <label>Titre du module *</label>
          <input type="text" name="titre_module" placeholder="Ex : Introduction et fondamentaux" required>
        </div>
        <div class="mo-field">
          <label>Contenu (points-clés)</label>
          <input type="text" name="contenus" placeholder="Point 1 · Point 2 · Point 3">
        </div>
        <div class="mo-field">
          <label>Durée (h)</label>
          <input type="number" name="duree_heures" value="2" min="1" max="40">
        </div>
      </div>
      <div class="mo-field">
        <label>Position (ordre)</label>
        <input type="number" name="ordre" value="<?= count($modules) + 1 ?>" min="1" max="50" style="width:100px">
      </div>
      <button type="submit" class="mo-sbtn">Ajouter le module</button>
    </form>
  </div>
</div>

<!-- Modal édition -->
<div class="mo-edit-overlay" id="editOverlay" onclick="if(event.target===this)closeEdit()">
  <div class="mo-edit-box">
    <div style="font-size:1.1rem;font-weight:700;margin-bottom:16px">✏️ Modifier le module</div>
    <form method="post">
      <input type="hidden" name="action" value="update">
      <input type="hidden" name="module_id" id="editId">
      <div class="mo-field"><label>Titre *</label><input type="text" name="titre_module" id="editTitre" required></div>
      <div class="mo-field"><label>Contenus</label><textarea name="contenus" id="editContenus"></textarea></div>
      <div class="mo-grid3" style="grid-template-columns:1fr 80px">
        <div class="mo-field"><label>Ordre</label><input type="number" name="ordre" id="editOrdre" min="1"></div>
        <div class="mo-field"><label>Durée (h)</label><input type="number" name="duree_heures" id="editDuree" min="1" max="40"></div>
      </div>
      <div style="display:flex;gap:12px;margin-top:8px">
        <button type="submit" class="mo-sbtn">💾 Enregistrer</button>
        <button type="button" onclick="closeEdit()" style="background:none;border:1px solid #d1d5db;border-radius:8px;padding:8px 16px;cursor:pointer">Annuler</button>
      </div>
    </form>
  </div>
</div>

<script>
function openEdit(id, ordre, titre, contenus, duree) {
    document.getElementById('editId').value = id;
    document.getElementById('editOrdre').value = ordre;
    document.getElementById('editTitre').value = titre;
    document.getElementById('editContenus').value = contenus;
    document.getElementById('editDuree').value = duree;
    document.getElementById('editOverlay').classList.add('open');
}
function closeEdit() { document.getElementById('editOverlay').classList.remove('open'); }

// Drag-and-drop simple
let dragged = null;
document.querySelectorAll('.mo-item').forEach(el => {
    el.addEventListener('dragstart', () => { dragged = el; el.style.opacity = '.4'; });
    el.addEventListener('dragend', () => { el.style.opacity = '1'; saveOrder(); });
    el.addEventListener('dragover', e => { e.preventDefault(); el.style.background = '#f0f9ff'; });
    el.addEventListener('dragleave', () => { el.style.background = ''; });
    el.addEventListener('drop', e => {
        e.preventDefault(); el.style.background = '';
        if (dragged && dragged !== el) {
            const list = el.parentNode;
            const items = [...list.querySelectorAll('.mo-item')];
            const di = items.indexOf(dragged), ti = items.indexOf(el);
            if (di < ti) list.insertBefore(dragged, el.nextSibling);
            else list.insertBefore(dragged, el);
        }
    });
    el.setAttribute('draggable', 'true');
});

function saveOrder() {
    const ids = [...document.querySelectorAll('.mo-item')].map(el => el.dataset.id);
    fetch('', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'action=reorder&' + ids.map((id,i) => `ids[${i}]=${id}`).join('&')
    }).then(r => r.json()).then(d => { if (!d.ok) alert('Erreur sauvegarde ordre.'); });
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout/layout.php';

