<?php
declare(strict_types=1);
/**
 * IBIG EDUFORM — /outils/devis-liste.php
 * Tableau de bord des devis / proformas.
 * Actions : changer statut, retélécharger PDF, copier lien vérif QR.
 * Protégé par .htpasswd du dossier /outils/.
 */
require_once __DIR__ . '/../core/config.php';

/* ─────────────────────────────────────────────
   ACTION AJAX — changement de statut
───────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $id      = (int)($_POST['id'] ?? 0);
    $action  = $_POST['action'];
    $allowed = ['marquer_envoye','marquer_accepte','marquer_refuse','marquer_annule','marquer_brouillon','save_notes'];

    if (!$id || !in_array($action, $allowed, true)) {
        echo json_encode(['ok' => false, 'error' => 'Paramètres invalides']); exit;
    }

    /* Cas spécial : enregistrement des notes internes */
    if ($action === 'save_notes') {
        $notes = trim(strip_tags((string)($_POST['notes'] ?? '')));
        try {
            $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4',
                           DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $pdo->prepare("UPDATE devis SET notes_internes = ? WHERE id = ?")->execute([$notes ?: null, $id]);
            echo json_encode(['ok' => true]); exit;
        } catch (\Throwable $e) {
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]); exit;
        }
    }

    $newStatut = match($action) {
        'marquer_envoye'    => 'envoye',
        'marquer_accepte'   => 'accepte',
        'marquer_refuse'    => 'refuse',
        'marquer_annule'    => 'annule',
        'marquer_brouillon' => 'brouillon',
    };
    $dateField = match($action) {
        'marquer_envoye'  => ', date_envoi = NOW()',
        'marquer_accepte',
        'marquer_refuse'  => ', date_reponse = NOW()',
        default           => '',
    };

    try {
        $pdo = new PDO(
            'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4',
            DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $pdo->prepare("UPDATE devis SET statut = ? $dateField WHERE id = ?")->execute([$newStatut, $id]);
        echo json_encode(['ok' => true, 'statut' => $newStatut]); exit;
    } catch (\Throwable $e) {
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]); exit;
    }
}

/* ─────────────────────────────────────────────
   DONNÉES
───────────────────────────────────────────── */
$rows    = [];
$stats   = ['total'=>0,'brouillon'=>0,'envoye'=>0,'accepte'=>0,'refuse'=>0,'expire'=>0,'annule'=>0];
$dbError = '';
$search  = trim($_GET['q'] ?? '');
$statutF = trim($_GET['statut'] ?? '');
$ca      = 0; // chiffre d'affaires acceptés

try {
    $pdo = new PDO(
        'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4',
        DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    /* Expirer automatiquement les devis dont date_expiration < aujourd'hui */
    $pdo->exec("UPDATE devis SET statut = 'expire'
                WHERE statut IN ('brouillon','envoye')
                AND date_expiration IS NOT NULL
                AND date_expiration < CURDATE()");

    /* Stats globales */
    $stSt = $pdo->query("SELECT statut, COUNT(*) n, COALESCE(SUM(montant_ttc),0) ca
                          FROM devis GROUP BY statut");
    while ($r = $stSt->fetch(PDO::FETCH_ASSOC)) {
        $stats[(string)$r['statut']] = (int)$r['n'];
        $stats['total'] += (int)$r['n'];
        if ($r['statut'] === 'accepte') $ca = (int)$r['ca'];
    }

    /* Liste filtrée */
    $where  = 'WHERE 1=1';
    $params = [];
    if ($search)  { $where .= ' AND (ref_devis LIKE ? OR prospect LIKE ? OR titre_formation LIKE ? OR contact_nom LIKE ?)';
                    $params = array_merge($params, ["%$search%","%$search%","%$search%","%$search%"]); }
    if ($statutF) { $where .= ' AND statut = ?'; $params[] = $statutF; }

    $st = $pdo->prepare("SELECT id, ref_devis, tdr_ref, prospect, contact_nom, contact_email,
        titre_formation, categorie, nb_participants, mode_formation,
        montant_ttc, tva_pct, remise_pct,
        statut, date_creation, date_envoi, date_reponse, date_expiration,
        qr_token, qr_scans, validite_jours, notes_internes
        FROM devis $where ORDER BY date_creation DESC LIMIT 150");
    $st->execute($params);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);

} catch (\Throwable $e) {
    $dbError = 'Erreur BD : ' . $e->getMessage();
}

function fcfa(int $n): string { return number_format($n, 0, ',', "\u{202F}") . ' F'; }
function dF(?string $d): string { return $d ? date('d/m/Y', strtotime($d)) : '—'; }
function dtF(?string $d): string { return $d ? date('d/m/Y H:i', strtotime($d)) : '—'; }
?><!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Tableau de bord Devis — IBIG EDUFORM</title>
<style>
:root{--navy:#0d1f3c;--amber:#f59e0b;--bg:#f8fafc;--card:#fff;--border:#e2e8f0;--txt:#1e293b;--muted:#64748b;}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Segoe UI',system-ui,sans-serif;background:var(--bg);color:var(--txt);font-size:14px}
/* ── Header ── */
.top-bar{background:var(--navy);padding:14px 24px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px}
.top-bar h1{color:#fff;font-size:1.05rem;font-weight:800;letter-spacing:.04em}
.top-bar a{color:#94a3b8;font-size:.8rem;text-decoration:none}
.top-bar a:hover{color:#f59e0b}
/* ── KPIs ── */
.kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px;padding:20px 24px 0}
.kpi{background:var(--card);border-radius:10px;padding:14px 16px;border-left:4px solid var(--ac);cursor:pointer;transition:.15s}
.kpi:hover{box-shadow:0 2px 12px rgba(0,0,0,.08)}
.kpi.active{box-shadow:0 0 0 2px var(--ac)}
.kpi-num{font-size:1.6rem;font-weight:900;color:var(--ac);line-height:1}
.kpi-label{font-size:.72rem;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;margin-top:3px}
.kpi-sub{font-size:.7rem;color:var(--muted);margin-top:2px}
/* ── Barre de recherche ── */
.toolbar{padding:16px 24px;display:flex;gap:10px;flex-wrap:wrap;align-items:center}
.toolbar input{padding:7px 12px;border:1px solid var(--border);border-radius:6px;font-size:13px;flex:1;min-width:200px;max-width:360px}
.toolbar input:focus{outline:none;border-color:var(--navy)}
.btn-new{padding:7px 16px;background:var(--amber);color:#fff;border:none;border-radius:6px;font-size:12px;font-weight:700;cursor:pointer;white-space:nowrap;text-decoration:none;display:inline-flex;align-items:center;gap:5px}
.btn-new:hover{background:#d97706}
.count-info{font-size:.8rem;color:var(--muted);margin-left:auto}
/* ── Table ── */
.table-wrap{padding:0 24px 32px;overflow-x:auto}
table{width:100%;border-collapse:collapse;background:var(--card);border-radius:10px;overflow:hidden;box-shadow:0 1px 6px rgba(0,0,0,.05)}
thead tr{background:var(--navy)}
th{color:#fff;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;padding:10px 12px;text-align:left;white-space:nowrap}
td{padding:9px 12px;border-bottom:1px solid var(--border);font-size:12.5px;vertical-align:middle}
tr:last-child td{border-bottom:none}
tr:hover td{background:#f8fafc}
.ref{font-family:monospace;font-size:11px;color:var(--muted)}
.prospect-cell{font-weight:700;color:var(--navy);max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.titre-cell{max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--muted);font-size:11.5px}
.ttc-cell{font-weight:800;color:var(--navy);white-space:nowrap}
/* ── Badges statut ── */
.badge{display:inline-block;padding:3px 9px;border-radius:20px;font-size:11px;font-weight:700;white-space:nowrap}
.badge-brouillon{background:#f1f5f9;color:#64748b}
.badge-envoye{background:#dbeafe;color:#1e40af}
.badge-accepte{background:#dcfce7;color:#166534}
.badge-refuse{background:#fee2e2;color:#991b1b}
.badge-expire{background:#fef3c7;color:#92400e}
.badge-annule{background:#f1f5f9;color:#94a3b8}
/* ── Actions ── */
.actions{display:flex;gap:5px;flex-wrap:nowrap}
.btn-act{padding:4px 8px;border:none;border-radius:4px;font-size:11px;font-weight:700;cursor:pointer;white-space:nowrap;transition:.15s}
.btn-pdf{background:#0d1f3c;color:#fff}.btn-pdf:hover{background:#1a3a6e}
.btn-send{background:#3b82f6;color:#fff}.btn-send:hover{background:#2563eb}
.btn-ok{background:#16a34a;color:#fff}.btn-ok:hover{background:#15803d}
.btn-ko{background:#dc2626;color:#fff}.btn-ko:hover{background:#b91c1c}
.btn-qr{background:#0d9488;color:#fff}.btn-qr:hover{background:#0f766e}
.btn-undo{background:#f59e0b;color:#fff}.btn-undo:hover{background:#d97706}
/* ── QR scans ── */
.scans{font-size:10px;color:var(--muted);white-space:nowrap}
/* ── Empty ── */
.empty{text-align:center;padding:60px;color:var(--muted)}
.empty-icon{font-size:48px;margin-bottom:12px}
/* ── Toast ── */
#toast{position:fixed;bottom:24px;left:50%;transform:translateX(-50%);background:#0d1f3c;color:#fff;padding:10px 22px;border-radius:8px;font-size:13px;font-weight:600;box-shadow:0 4px 20px rgba(0,0,0,.2);display:none;z-index:9999}
/* ── Modale notes ── */
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center}
.modal-overlay.open{display:flex}
.modal{background:#fff;border-radius:12px;padding:24px;max-width:420px;width:90%;box-shadow:0 8px 40px rgba(0,0,0,.2)}
.modal h3{font-size:1rem;font-weight:800;color:var(--navy);margin-bottom:14px}
.modal textarea{width:100%;padding:10px;border:1px solid var(--border);border-radius:6px;font-size:13px;resize:vertical;font-family:inherit}
.modal-foot{display:flex;gap:8px;justify-content:flex-end;margin-top:12px}
.btn-cancel{padding:7px 16px;background:#f1f5f9;color:#374151;border:none;border-radius:6px;font-size:13px;font-weight:600;cursor:pointer}
.btn-save{padding:7px 16px;background:var(--navy);color:#fff;border:none;border-radius:6px;font-size:13px;font-weight:700;cursor:pointer}
/* ── Responsive ── */
@media(max-width:600px){.kpis{padding:12px 12px 0}.toolbar,.table-wrap{padding-left:12px;padding-right:12px}}
</style>
</head>
<body>

<!-- HEADER -->
<div class="top-bar">
  <h1>💼 Tableau de bord — Devis &amp; Proformas</h1>
  <div style="display:flex;gap:16px;align-items:center">
    <a href="tdr-historique.php">← Historique TDR</a>
    <a href="devis-migrate.php" style="color:#f59e0b;font-size:.75rem">⚙️ Migration BD</a>
  </div>
</div>

<?php if ($dbError): ?>
<div style="background:#fef2f2;border:1px solid #fecaca;padding:16px 24px;color:#dc2626;font-size:.9rem">
  ❌ <?= htmlspecialchars($dbError) ?>
</div>
<?php else: ?>

<!-- KPIs -->
<div class="kpis">
  <?php
  $kpiDefs = [
    ['key'=>'',         'label'=>'Total',     'ac'=>'#0d1f3c', 'sub'=>'tous statuts'],
    ['key'=>'brouillon','label'=>'Brouillons','ac'=>'#64748b', 'sub'=>'non envoyés'],
    ['key'=>'envoye',   'label'=>'Envoyés',   'ac'=>'#3b82f6', 'sub'=>'en attente'],
    ['key'=>'accepte',  'label'=>'Acceptés',  'ac'=>'#16a34a', 'sub'=>fcfa($ca).' TTC'],
    ['key'=>'refuse',   'label'=>'Refusés',   'ac'=>'#dc2626', 'sub'=>''],
    ['key'=>'expire',   'label'=>'Expirés',   'ac'=>'#d97706', 'sub'=>''],
  ];
  foreach ($kpiDefs as $k):
    $n      = $k['key'] ? ($stats[$k['key']] ?? 0) : $stats['total'];
    $active = ($statutF === $k['key']) ? ' active' : '';
  ?>
  <div class="kpi<?= $active ?>" style="--ac:<?= $k['ac'] ?>"
       onclick="filterStatut('<?= $k['key'] ?>')">
    <div class="kpi-num"><?= $n ?></div>
    <div class="kpi-label"><?= $k['label'] ?></div>
    <?php if ($k['sub']): ?><div class="kpi-sub"><?= $k['sub'] ?></div><?php endif; ?>
  </div>
  <?php endforeach; ?>
</div>

<!-- TOOLBAR -->
<div class="toolbar">
  <input type="text" id="searchInput" placeholder="🔍 Rechercher — référence, client, formation…"
         value="<?= htmlspecialchars($search) ?>" oninput="liveSearch(this.value)">
  <a href="tdr-historique.php" class="btn-new">+ Nouveau devis</a>
  <span class="count-info" id="countInfo"><?= count($rows) ?> devis<?= count($rows) > 1 ? 's' : '' ?></span>
</div>

<!-- TABLE -->
<div class="table-wrap">
<?php if (!$rows): ?>
  <div class="empty"><div class="empty-icon">📋</div>
    <p><?= $search || $statutF ? 'Aucun résultat pour ces critères.' : 'Aucun devis enregistré pour le moment.' ?></p>
    <?php if (!$search && !$statutF): ?><p style="margin-top:8px;font-size:.85rem">Cliquez sur 💼 Devis depuis l'historique TDR pour en créer un.</p><?php endif; ?>
  </div>
<?php else: ?>
  <table id="devisTable">
    <thead>
      <tr>
        <th>Réf.</th>
        <th>Client / Prospect</th>
        <th>Formation</th>
        <th>Part.</th>
        <th>Montant TTC</th>
        <th>Statut</th>
        <th>Créé le</th>
        <th>Expiration</th>
        <th>QR</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($rows as $row):
      $statut  = $row['statut'];
      $ttc     = (int)$row['montant_ttc'];
      $token   = $row['qr_token'];
      $verifyUrl = APP_URL . '/verify/devis/' . $token;
      $expired = $row['date_expiration'] && $row['date_expiration'] < date('Y-m-d');
      $expiringSoon = $row['date_expiration']
        && $row['date_expiration'] >= date('Y-m-d')
        && $row['date_expiration'] <= date('Y-m-d', strtotime('+5 days'));
    ?>
    <tr data-id="<?= (int)$row['id'] ?>" data-statut="<?= $statut ?>"
        data-search="<?= strtolower(htmlspecialchars($row['ref_devis'].' '.$row['prospect'].' '.$row['titre_formation'].' '.($row['contact_nom']??''))) ?>">
      <td>
        <div class="ref"><?= htmlspecialchars($row['ref_devis']) ?></div>
        <?php if ($row['tdr_ref']): ?>
          <div style="font-size:10px;color:#94a3b8">TDR: <?= htmlspecialchars($row['tdr_ref']) ?></div>
        <?php endif; ?>
      </td>
      <td>
        <div class="prospect-cell" title="<?= htmlspecialchars($row['prospect']) ?>"><?= htmlspecialchars($row['prospect']) ?></div>
        <?php if ($row['contact_nom']): ?>
          <div style="font-size:11px;color:var(--muted)"><?= htmlspecialchars($row['contact_nom']) ?></div>
        <?php endif; ?>
        <?php if ($row['contact_email']): ?>
          <div style="font-size:10px;color:#94a3b8"><?= htmlspecialchars($row['contact_email']) ?></div>
        <?php endif; ?>
      </td>
      <td>
        <div class="titre-cell" title="<?= htmlspecialchars($row['titre_formation']) ?>"><?= htmlspecialchars($row['titre_formation']) ?></div>
        <div style="font-size:10px;color:#94a3b8"><?= htmlspecialchars($row['categorie']??'') ?> · <?= match($row['mode_formation']){
          'en_ligne'=>'En ligne','hybride'=>'Hybride',default=>'Présentiel'} ?></div>
      </td>
      <td style="text-align:center;font-weight:700"><?= (int)$row['nb_participants'] ?></td>
      <td class="ttc-cell">
        <?= $ttc > 0 ? fcfa($ttc) : '<span style="color:var(--muted)">Sur devis</span>' ?>
        <?php if ($row['tva_pct'] > 0): ?>
          <div style="font-size:10px;color:var(--muted)">TVA <?= (int)$row['tva_pct'] ?>% incluse</div>
        <?php else: ?>
          <div style="font-size:10px;color:#94a3b8">Exonéré TVA</div>
        <?php endif; ?>
        <?php if ($row['remise_pct'] > 0): ?>
          <div style="font-size:10px;color:#059669">Remise <?= (int)$row['remise_pct'] ?>%</div>
        <?php endif; ?>
      </td>
      <td>
        <span class="badge badge-<?= $statut ?>"><?= match($statut){
          'brouillon'=>'Brouillon','envoye'=>'Envoyé','accepte'=>'✅ Accepté',
          'refuse'=>'❌ Refusé','expire'=>'⚠️ Expiré','annule'=>'Annulé',default=>$statut} ?></span>
        <?php if ($row['date_envoi'] && $statut === 'envoye'): ?>
          <div style="font-size:10px;color:var(--muted);margin-top:2px">Envoyé le <?= dF($row['date_envoi']) ?></div>
        <?php elseif ($row['date_reponse'] && in_array($statut,['accepte','refuse'])): ?>
          <div style="font-size:10px;color:var(--muted);margin-top:2px">Réponse le <?= dF($row['date_reponse']) ?></div>
        <?php endif; ?>
      </td>
      <td style="font-size:11px;color:var(--muted);white-space:nowrap"><?= dF($row['date_creation']) ?></td>
      <td style="font-size:11px;white-space:nowrap">
        <?php if ($row['date_expiration']): ?>
          <span style="color:<?= $expired ? '#dc2626' : ($expiringSoon ? '#d97706' : 'var(--muted)') ?>;font-weight:<?= ($expired||$expiringSoon)?'700':'400' ?>">
            <?= dF($row['date_expiration']) ?>
            <?php if ($expiringSoon && !$expired): ?><br><span style="font-size:9px">⚠️ Bientôt</span><?php endif; ?>
            <?php if ($expired): ?><br><span style="font-size:9px">Expiré</span><?php endif; ?>
          </span>
        <?php else: ?>—<?php endif; ?>
      </td>
      <td>
        <div class="scans">
          <?php if ($row['qr_scans'] > 0): ?>
            👁 <?= (int)$row['qr_scans'] ?> scan<?= $row['qr_scans']>1?'s':'' ?>
          <?php else: ?>Non scanné<?php endif; ?>
        </div>
      </td>
      <td>
        <div class="actions">
          <!-- Re-télécharger PDF -->
          <button class="btn-act btn-pdf" title="Re-télécharger le PDF"
                  onclick="redownloadDevis(<?= (int)$row['id'] ?>)">📥</button>

          <!-- Copier lien vérif QR -->
          <button class="btn-act btn-qr" title="Copier le lien de vérification QR"
                  onclick="copyVerifLink('<?= htmlspecialchars($verifyUrl, ENT_QUOTES) ?>')">🔗 QR</button>

          <?php if ($statut === 'brouillon'): ?>
            <button class="btn-act btn-send" title="Marquer comme envoyé"
                    onclick="changeStatut(<?= (int)$row['id'] ?>,'marquer_envoye',this)">✉️ Envoyé</button>
          <?php endif; ?>

          <?php if (in_array($statut, ['brouillon','envoye'])): ?>
            <button class="btn-act btn-ok" title="Marquer comme accepté"
                    onclick="changeStatut(<?= (int)$row['id'] ?>,'marquer_accepte',this)">✅</button>
            <button class="btn-act btn-ko" title="Marquer comme refusé"
                    onclick="changeStatut(<?= (int)$row['id'] ?>,'marquer_refuse',this)">❌</button>
          <?php endif; ?>

          <?php if (in_array($statut, ['accepte','refuse','expire','annule'])): ?>
            <button class="btn-act btn-undo" title="Remettre en brouillon"
                    onclick="changeStatut(<?= (int)$row['id'] ?>,'marquer_brouillon',this)">↩</button>
          <?php endif; ?>

          <?php if (!in_array($statut, ['annule'])): ?>
            <button class="btn-act" style="background:#e2e8f0;color:#374151" title="Notes internes"
                    onclick="openNotes(<?= (int)$row['id'] ?>,'<?= htmlspecialchars(str_replace("'","\'",$row['notes_internes']??''), ENT_QUOTES) ?>')">📝</button>
          <?php endif; ?>
        </div>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>
</div>
<?php endif; // pas dbError ?>

<!-- Toast notification -->
<div id="toast"></div>

<!-- Modal notes internes -->
<div class="modal-overlay" id="notesOverlay" onclick="if(event.target===this)closeNotes()">
  <div class="modal">
    <h3>📝 Notes internes</h3>
    <textarea id="notesText" rows="5" placeholder="Notes internes (non imprimées dans le PDF)…"></textarea>
    <div class="modal-foot">
      <button class="btn-cancel" onclick="closeNotes()">Annuler</button>
      <button class="btn-save" onclick="saveNotes()">Enregistrer</button>
    </div>
  </div>
</div>

<script>
// ── Filtre par statut (KPIs cliquables) ──
function filterStatut(s) {
  const url = new URL(window.location);
  if (s) url.searchParams.set('statut', s);
  else   url.searchParams.delete('statut');
  url.searchParams.delete('q');
  window.location = url;
}

// ── Recherche live (filtre les lignes sans rechargement) ──
let _searchTimer;
function liveSearch(val) {
  clearTimeout(_searchTimer);
  _searchTimer = setTimeout(() => {
    const q = val.toLowerCase().trim();
    let n = 0;
    document.querySelectorAll('#devisTable tbody tr').forEach(tr => {
      const match = !q || tr.dataset.search.includes(q);
      const statMatch = !_activeStatut || tr.dataset.statut === _activeStatut;
      tr.style.display = (match && statMatch) ? '' : 'none';
      if (match && statMatch) n++;
    });
    document.getElementById('countInfo').textContent = n + ' devis';
  }, 180);
}
const _activeStatut = '<?= addslashes($statutF) ?>';

// ── Changer statut (AJAX) ──
function changeStatut(id, action, btn) {
  if (!confirm('Confirmer cette action ?')) return;
  btn.disabled = true;
  const orig = btn.textContent;
  btn.textContent = '⏳';
  const fd = new FormData();
  fd.append('action', action); fd.append('id', id);
  fetch(window.location.pathname, { method:'POST', body:fd, credentials:'same-origin' })
    .then(r => r.json())
    .then(data => {
      if (data.ok) {
        toast('✅ Statut mis à jour');
        setTimeout(() => location.reload(), 800);
      } else {
        toast('❌ Erreur : ' + (data.error || '?'));
        btn.disabled = false; btn.textContent = orig;
      }
    })
    .catch(() => { toast('❌ Erreur réseau'); btn.disabled = false; btn.textContent = orig; });
}

// ── Copier le lien QR ──
function copyVerifLink(url) {
  if (navigator.clipboard) {
    navigator.clipboard.writeText(url)
      .then(() => toast('🔗 Lien copié — ' + url.slice(-12) + '…'))
      .catch(() => fallbackCopy(url));
  } else { fallbackCopy(url); }
}
function fallbackCopy(text) {
  const ta = document.createElement('textarea');
  ta.value = text; document.body.appendChild(ta); ta.select();
  document.execCommand('copy'); document.body.removeChild(ta);
  toast('🔗 Lien copié');
}

// ── Re-télécharger PDF (form POST natif) ──
function redownloadDevis(id) {
  const form = document.createElement('form');
  form.method = 'POST'; form.action = 'devis-redownload.php'; form.target = '_blank';
  const inp = document.createElement('input');
  inp.type = 'hidden'; inp.name = 'id'; inp.value = id;
  form.appendChild(inp); document.body.appendChild(form);
  form.submit(); document.body.removeChild(form);
}

// ── Notes internes ──
let _notesId = null;
function openNotes(id, current) {
  _notesId = id;
  document.getElementById('notesText').value = current || '';
  document.getElementById('notesOverlay').classList.add('open');
  document.getElementById('notesText').focus();
}
function closeNotes() {
  document.getElementById('notesOverlay').classList.remove('open');
  _notesId = null;
}
function saveNotes() {
  if (!_notesId) return;
  const fd = new FormData();
  fd.append('action', 'save_notes');
  fd.append('id', _notesId);
  fd.append('notes', document.getElementById('notesText').value);
  fetch(window.location.pathname, { method:'POST', body:fd, credentials:'same-origin' })
    .then(r => r.json())
    .then(d => { if (d.ok) { toast('📝 Notes enregistrées'); closeNotes(); } else toast('❌ ' + d.error); })
    .catch(() => toast('❌ Erreur réseau'));
}

// ── Toast ──
function toast(msg, dur = 2800) {
  const t = document.getElementById('toast');
  t.textContent = msg; t.style.display = 'block';
  clearTimeout(t._t);
  t._t = setTimeout(() => t.style.display = 'none', dur);
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeNotes(); });
</script>
</body>
</html>
