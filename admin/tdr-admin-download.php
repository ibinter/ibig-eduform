<?php
declare(strict_types=1);
/* ============================================================
   ADMIN — GÉNÉRATEUR TDR INTERNE
   Télécharger / visualiser n'importe quel TDR sans inscription
============================================================ */
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../core/csrf.php';
require_once __DIR__ . '/auth/middleware.php';
require_once __DIR__ . '/../core/auth.php';

Middleware::requireAuth();
$u   = auth_user();
$pdo = Database::connect();

/* ── Helpers ─────────────────────────────────────────────── */
function tdrAdminToken(array $f, string $mode, string $fmt, string $date = '', string $cren = ''): string
{
    $exp = time() + 72 * 3600;
    $sig = hash_hmac('sha256', $f['slug'] . '|' . $exp . '|' . $fmt . '|' . $mode, TDR_SECRET);
    $payload = base64_encode(json_encode([
        'slug'     => $f['slug'],
        'nom'      => $f['titre'],
        'cat'      => $f['domaine'],
        'prix'     => (int)($f['tarif_en_ligne'] ?? 0),
        'desc'     => mb_substr((string)($f['description'] ?? ''), 0, 400, 'UTF-8'),
        'fmt'      => $fmt,
        'mode'     => $mode,
        'date'     => $date,
        'cren'     => $cren,
        'prospect' => 'IBIG EDUFORM (Admin)',
        'nid'      => null,
        'exp'      => $exp,
        'sig'      => $sig,
    ]));
    return rtrim(strtr($payload, '+/', '-_'), '=');
}

/* ── Génération PDF à la demande (POST) ───────────────────── */
$generated = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $fid  = (int)($_POST['formation_id'] ?? 0);
    $mode = in_array($_POST['mode'] ?? '', ['en_ligne','presentiel','hybride'], true) ? $_POST['mode'] : 'en_ligne';
    $fmt  = in_array($_POST['format'] ?? '', ['individuel','groupe_3_5','groupe_6_10','groupe_10p','groupe_devis'], true) ? $_POST['format'] : 'individuel';
    $date = preg_replace('/[^0-9\-\/\s]/', '', (string)($_POST['date_debut'] ?? ''));
    $cren = preg_replace('/[^a-z_]/', '', strtolower((string)($_POST['creneau'] ?? '')));

    if ($fid > 0) {
        $row = $pdo->prepare("SELECT id, titre, slug, domaine, description, duree, tarif_en_ligne, tarif_presentiel FROM formations WHERE id = ? LIMIT 1");
        $row->execute([$fid]);
        $f = $row->fetch(PDO::FETCH_ASSOC);
        if ($f) {
            $token = tdrAdminToken($f, $mode, $fmt, $date, $cren);
            $base  = defined('BASE_URL') ? rtrim((string)BASE_URL, '/') : 'https://ibig-eduform.com';
            $generated = [
                'formation' => htmlspecialchars($f['titre'], ENT_QUOTES, 'UTF-8'),
                'mode'      => $mode,
                'fmt'       => $fmt,
                'view_url'  => $base . '/tdr-download.php?t=' . $token,
                'pdf_url'   => $base . '/tdr-pdf.php?t=' . $token,
            ];
        }
    }
}

/* ── Filtres formations ──────────────────────────────────── */
$q       = trim((string)($_GET['q'] ?? ''));
$domF    = trim((string)($_GET['dom'] ?? ''));
$where   = ['statut = "active"'];
$params  = [];
if ($q !== '') {
    $where[]        = '(titre LIKE :q OR slug LIKE :q OR domaine LIKE :q)';
    $params[':q']   = '%' . $q . '%';
}
if ($domF !== '') {
    $where[]          = 'domaine = :dom';
    $params[':dom']   = $domF;
}
$whereSql = 'WHERE ' . implode(' AND ', $where);

/* Domaines distincts pour le filtre */
$doms = $pdo->query("SELECT DISTINCT domaine FROM formations WHERE statut='active' ORDER BY domaine ASC")->fetchAll(PDO::FETCH_COLUMN);

/* Liste formations */
$stmt = $pdo->prepare("SELECT id, titre, slug, domaine, duree, tarif_en_ligne, tarif_presentiel FROM formations $whereSql ORDER BY titre ASC LIMIT 200");
$stmt->execute($params);
$formations = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ── Layout ──────────────────────────────────────────────── */
$pageTitle  = 'Générateur TDR Admin';
$activeMenu = 'tdr_admin';
ob_start();
?>
<style>
.tga-wrap{max-width:1100px}
.tga-result{background:linear-gradient(135deg,#0a1733,#1e3a6e);color:#fff;border-radius:16px;padding:30px 32px;margin-bottom:28px}
.tga-result h2{font-size:1.3rem;font-weight:800;margin:0 0 6px}
.tga-result p{font-size:.9rem;opacity:.8;margin:0 0 18px}
.tga-btns{display:flex;gap:12px;flex-wrap:wrap}
.btn-tdr-view{background:#f59e0b;color:#0a1733;font-weight:900;padding:13px 28px;border-radius:10px;text-decoration:none;font-size:14px;display:inline-flex;align-items:center;gap:8px}
.btn-tdr-view:hover{background:#d97706;color:#fff}
.btn-tdr-pdf{background:#10b981;color:#fff;font-weight:900;padding:13px 28px;border-radius:10px;text-decoration:none;font-size:14px;display:inline-flex;align-items:center;gap:8px}
.btn-tdr-pdf:hover{background:#059669}
.btn-tdr-copy{background:rgba(255,255,255,.15);color:#fff;font-weight:700;padding:13px 20px;border-radius:10px;border:1px solid rgba(255,255,255,.3);font-size:14px;cursor:pointer;display:inline-flex;align-items:center;gap:8px}
.btn-tdr-copy:hover{background:rgba(255,255,255,.25)}

.tga-filters{background:#f8fafc;border:1px solid #e5e7eb;border-radius:14px;padding:14px 16px;margin-bottom:16px;display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end}
.tga-filters label{font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.3px;display:block;margin-bottom:4px}
.tga-filters input,.tga-filters select{padding:9px 12px;border:1px solid #e5e7eb;border-radius:9px;font-size:13.5px;background:#fff;outline:none}
.tga-filters input:focus,.tga-filters select:focus{border-color:#1f3fe0;box-shadow:0 0 0 3px rgba(31,63,224,.1)}
.btn-filter{background:linear-gradient(135deg,#1f3fe0,#3b82f6);color:#fff;border:0;padding:10px 18px;border-radius:9px;font-weight:800;font-size:13px;cursor:pointer}
.btn-clear{background:#fff;border:1px solid #e5e7eb;color:#64748b;padding:10px 14px;border-radius:9px;font-weight:700;font-size:13px;text-decoration:none}

.tga-count{font-size:13px;color:#64748b;margin-bottom:10px}

.tga-table{width:100%;border-collapse:collapse}
.tga-table th{font-size:11.5px;text-transform:uppercase;letter-spacing:.4px;color:#6b7280;text-align:left;padding:10px 12px;border-bottom:2px solid #eef2f7}
.tga-table td{padding:11px 12px;border-bottom:1px solid #f1f5f9;vertical-align:middle;font-size:14px}
.tga-table tr:hover td{background:#fafbff}
.ftitle{font-weight:700;color:#0f172a}
.fdom{font-size:12px;color:#64748b;margin-top:2px}
.fprix{font-size:12.5px;color:#374151}

/* Modal */
.tga-modal-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:1000;align-items:center;justify-content:center}
.tga-modal-bg.open{display:flex}
.tga-modal{background:#fff;border-radius:18px;width:100%;max-width:520px;padding:30px;box-shadow:0 30px 80px rgba(0,0,0,.25);position:relative}
.tga-modal h3{font-size:1.15rem;font-weight:900;color:#0f172a;margin:0 0 4px}
.tga-modal .sub{font-size:13px;color:#64748b;margin:0 0 22px}
.tga-modal .f{display:flex;flex-direction:column;gap:5px;margin-bottom:16px}
.tga-modal label{font-size:12px;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.3px}
.tga-modal select,.tga-modal input{padding:10px 12px;border:1px solid #e5e7eb;border-radius:9px;font-size:14px;outline:none;background:#f8fafc;width:100%}
.tga-modal select:focus,.tga-modal input:focus{border-color:#1f3fe0;background:#fff;box-shadow:0 0 0 3px rgba(31,63,224,.1)}
.tga-modal-footer{display:flex;gap:10px;margin-top:20px}
.btn-gen{flex:1;background:linear-gradient(135deg,#0a1733,#1e3a6e);color:#fff;font-weight:900;padding:13px;border-radius:10px;border:0;font-size:14.5px;cursor:pointer}
.btn-gen:hover{background:#1e3a6e}
.btn-close-modal{background:#f1f5f9;color:#64748b;font-weight:700;padding:13px 18px;border-radius:10px;border:0;font-size:14px;cursor:pointer}
.modal-x{position:absolute;top:14px;right:16px;background:none;border:0;font-size:20px;color:#9ca3af;cursor:pointer;line-height:1}
@media(max-width:600px){.tga-modal{margin:12px;padding:22px}}
</style>

<?php if ($generated): ?>
<div class="tga-result">
  <h2>✅ TDR Généré — <?= $generated['formation'] ?></h2>
  <p>Mode : <?= htmlspecialchars(['en_ligne'=>'En ligne','presentiel'=>'Présentiel','hybride'=>'Hybride'][$generated['mode']] ?? $generated['mode']) ?> &nbsp;|&nbsp; Format : <?= htmlspecialchars($generated['fmt']) ?></p>
  <div class="tga-btns">
    <a href="<?= htmlspecialchars($generated['view_url']) ?>" target="_blank" class="btn-tdr-view">👁 Visualiser TDR</a>
    <a href="<?= htmlspecialchars($generated['pdf_url']) ?>" target="_blank" download class="btn-tdr-pdf">⬇ Télécharger PDF</a>
    <button type="button" class="btn-tdr-copy" onclick="navigator.clipboard.writeText('<?= htmlspecialchars($generated['pdf_url'], ENT_QUOTES) ?>').then(()=>this.textContent='✔ Copié!')">🔗 Copier lien PDF</button>
  </div>
</div>
<?php endif; ?>

<div class="admin-content-header">
  <div>
    <h1>📄 Générateur TDR Admin</h1>
    <p class="sub">Générez et téléchargez n'importe quel TDR sans inscription.</p>
  </div>
</div>

<!-- FILTRES -->
<form method="GET" class="tga-filters">
  <div>
    <label>Recherche</label>
    <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Titre, slug, domaine…" style="width:240px">
  </div>
  <div>
    <label>Domaine</label>
    <select name="dom">
      <option value="">Tous les domaines</option>
      <?php foreach ($doms as $d): ?>
        <option value="<?= htmlspecialchars($d) ?>" <?= $domF === $d ? 'selected' : '' ?>><?= htmlspecialchars($d) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div style="display:flex;gap:8px;align-items:flex-end">
    <button type="submit" class="btn-filter">Filtrer</button>
    <a href="?" class="btn-clear">✕</a>
  </div>
</form>

<div class="tga-count"><?= count($formations) ?> formation<?= count($formations) > 1 ? 's' : '' ?></div>

<?php if (empty($formations)): ?>
  <div style="text-align:center;padding:60px 20px;color:#9ca3af">
    <div style="font-size:2.5rem;margin-bottom:12px">🔍</div>
    <p style="font-size:1rem;font-weight:600">Aucune formation trouvée.</p>
  </div>
<?php else: ?>
<div style="overflow-x:auto">
<table class="tga-table">
  <thead>
    <tr>
      <th>Formation</th>
      <th>Domaine</th>
      <th>Durée</th>
      <th>Tarif en ligne</th>
      <th>Tarif présentiel</th>
      <th style="text-align:center">TDR</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($formations as $f): ?>
    <tr>
      <td>
        <div class="ftitle"><?= htmlspecialchars($f['titre']) ?></div>
        <div class="fdom">slug: <?= htmlspecialchars($f['slug']) ?></div>
      </td>
      <td style="font-size:13px;color:#374151"><?= htmlspecialchars($f['domaine']) ?></td>
      <td style="font-size:13px;color:#64748b"><?= htmlspecialchars($f['duree'] ?? '—') ?></td>
      <td class="fprix"><?= $f['tarif_en_ligne'] ? number_format((int)$f['tarif_en_ligne']) . ' F' : '—' ?></td>
      <td class="fprix"><?= $f['tarif_presentiel'] ? number_format((int)$f['tarif_presentiel']) . ' F' : '—' ?></td>
      <td style="text-align:center">
        <button type="button"
          class="btn-gen-modal"
          style="background:linear-gradient(135deg,#0a1733,#1e3a6e);color:#fff;border:0;padding:9px 18px;border-radius:9px;font-weight:800;font-size:13px;cursor:pointer;white-space:nowrap"
          data-id="<?= (int)$f['id'] ?>"
          data-titre="<?= htmlspecialchars($f['titre'], ENT_QUOTES) ?>"
        >📄 Générer TDR</button>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php endif; ?>

<!-- MODAL -->
<div class="tga-modal-bg" id="tgaModal">
  <div class="tga-modal">
    <button type="button" class="modal-x" onclick="closeModal()">✕</button>
    <h3 id="modalTitle">Générer TDR</h3>
    <p class="sub" id="modalSub">Choisissez les options du TDR</p>
    <form method="POST">
      <?= csrf_field() ?>
      <input type="hidden" name="formation_id" id="modalFid">

      <div class="f">
        <label>Mode de formation</label>
        <select name="mode" id="modalMode">
          <option value="en_ligne">En ligne (classe virtuelle)</option>
          <option value="presentiel">Présentiel</option>
          <option value="hybride">Hybride</option>
        </select>
      </div>
      <div class="f">
        <label>Format</label>
        <select name="format" id="modalFmt">
          <option value="individuel">Individuel</option>
          <option value="groupe_3_5">Groupe 3–5 personnes</option>
          <option value="groupe_6_10">Groupe 6–10 personnes</option>
          <option value="groupe_10p">Groupe 10+ personnes</option>
          <option value="groupe_devis">Groupe — tarif sur devis</option>
        </select>
      </div>
      <div class="f">
        <label>Date de début <span style="font-weight:400;text-transform:none">(optionnel)</span></label>
        <input type="text" name="date_debut" id="modalDate" placeholder="Ex : Lundi 14 octobre 2026">
      </div>
      <div class="f">
        <label>Créneau préféré <span style="font-weight:400;text-transform:none">(optionnel)</span></label>
        <select name="creneau" id="modalCren">
          <option value="">Non précisé</option>
          <option value="matin_gmt">Matin 7h–10h GMT</option>
          <option value="journee_gmt">Journée 10h–15h GMT</option>
          <option value="aprem_gmt">Après-midi 15h–18h GMT</option>
          <option value="soir_gmt">Soir 18h–21h GMT</option>
          <option value="week_end">Week-end</option>
        </select>
      </div>
      <div class="tga-modal-footer">
        <button type="submit" class="btn-gen">📄 Générer le TDR</button>
        <button type="button" class="btn-close-modal" onclick="closeModal()">Annuler</button>
      </div>
    </form>
  </div>
</div>

<script>
document.querySelectorAll('.btn-gen-modal').forEach(btn => {
  btn.addEventListener('click', () => {
    document.getElementById('modalFid').value   = btn.dataset.id;
    document.getElementById('modalTitle').textContent = btn.dataset.titre;
    document.getElementById('modalSub').textContent   = 'Choisissez les options du TDR';
    document.getElementById('tgaModal').classList.add('open');
  });
});
function closeModal() {
  document.getElementById('tgaModal').classList.remove('open');
}
document.getElementById('tgaModal').addEventListener('click', e => {
  if (e.target === document.getElementById('tgaModal')) closeModal();
});
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/layout/layout.php';
