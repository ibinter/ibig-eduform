<?php
declare(strict_types=1);
/**
 * IBIG EDUFORM — /outils/tdr-historique.php
 * Historique des TDR générés.
 * Protégé par .htpasswd du dossier /outils/.
 */
require_once __DIR__ . '/../core/config.php';

try {
    $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
} catch(\Throwable $e) { $pdo = null; }

$rows = [];
$total = 0;
$search = $typeF = $niveauF = '';
$dbError = '';
if ($pdo) {
    $search  = trim($_GET['q'] ?? '');
    $typeF   = trim($_GET['type'] ?? '');
    $niveauF = trim($_GET['niveau'] ?? '');
    $where   = 'WHERE 1=1';
    $params  = [];
    if ($search)  { $where .= ' AND (titre LIKE ? OR prospect LIKE ? OR ref_doc LIKE ?)'; $params = array_merge($params, ["%$search%","%$search%","%$search%"]); }
    if ($typeF)   { $where .= ' AND type_doc = ?'; $params[] = $typeF; }
    if ($niveauF) { $where .= ' AND format_niveau = ?'; $params[] = $niveauF; }
    try {
        $stC = $pdo->prepare("SELECT COUNT(*) FROM tdr_historique $where");
        $stC->execute($params);
        $total = (int)$stC->fetchColumn();
        $st = $pdo->prepare("SELECT * FROM tdr_historique $where ORDER BY date_generation DESC LIMIT 100");
        $st->execute($params);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    } catch (\Throwable $e) {
        $dbError = 'Tables non initialisées. Exécutez d\'abord la migration.';
    }
}

$niveauLabels = ['simplifie'=>'Simplifié','moyen'=>'Standard','detaille'=>'Complet'];
$typeLabels   = ['formation'=>'Formation','mission'=>'Mission','projet'=>'Projet','evaluation'=>'Évaluation'];
$typeColors   = ['formation'=>'#dbeafe','mission'=>'#d1fae5','projet'=>'#fef3c7','evaluation'=>'#ede9fe'];
$typeText     = ['formation'=>'#1e40af','mission'=>'#065f46','projet'=>'#92400e','evaluation'=>'#5b21b6'];
?><!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Historique TDR — IBIG EDUFORM</title>
<style>
:root{--navy:#0d1f3c;--amber:#f59e0b;--bg:#f8fafc;--card:#fff;--border:#e2e8f0;--txt:#1e293b;--muted:#64748b;}
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Segoe UI',Arial,sans-serif;background:var(--bg);color:var(--txt);font-size:14px;}
.top-bar{background:var(--navy);color:#fff;padding:12px 24px;display:flex;align-items:center;justify-content:space-between;}
.top-bar a{color:#fbbf24;text-decoration:none;font-size:13px;font-weight:600;}
.top-bar a:hover{text-decoration:underline;}
.container{max-width:1100px;margin:0 auto;padding:24px 16px;}
h1{font-size:20px;font-weight:700;color:var(--navy);margin-bottom:4px;}
.subtitle{font-size:13px;color:var(--muted);margin-bottom:20px;}
.filters{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:20px;background:var(--card);padding:14px;border-radius:8px;border:1px solid var(--border);}
.filters input,.filters select{padding:7px 10px;border:1px solid var(--border);border-radius:6px;font-size:13px;flex:1;min-width:140px;}
.filters button{padding:7px 16px;background:var(--navy);color:#fff;border:none;border-radius:6px;cursor:pointer;font-weight:600;font-size:13px;}
.stats{display:flex;gap:12px;margin-bottom:20px;flex-wrap:wrap;}
.stat-card{background:var(--card);border:1px solid var(--border);border-radius:8px;padding:12px 18px;flex:1;min-width:120px;}
.stat-val{font-size:24px;font-weight:800;color:var(--navy);}
.stat-lbl{font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;}
table{width:100%;border-collapse:collapse;background:var(--card);border-radius:8px;overflow:hidden;border:1px solid var(--border);}
thead tr{background:var(--navy);}
th{color:#fff;padding:10px 12px;text-align:left;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;}
td{padding:10px 12px;border-bottom:1px solid var(--border);font-size:13px;vertical-align:middle;}
tr:last-child td{border-bottom:none;}
tr:hover td{background:#f1f5f9;}
.badge{display:inline-block;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:700;}
.empty{text-align:center;padding:60px;color:var(--muted);}
.empty-icon{font-size:48px;margin-bottom:12px;}
.prix-cell{font-weight:700;color:var(--navy);}
.btn-dl{display:inline-flex;align-items:center;gap:5px;padding:5px 11px;background:var(--navy);color:#fff;border:none;border-radius:5px;font-size:12px;font-weight:700;cursor:pointer;text-decoration:none;white-space:nowrap;}
.btn-dl:hover{background:#1a3a6e;}
.btn-dl.loading{opacity:.6;cursor:wait;}
.btn-edit{display:inline-flex;align-items:center;gap:4px;padding:5px 10px;background:var(--amber);color:#fff;border:none;border-radius:5px;font-size:12px;font-weight:700;cursor:pointer;white-space:nowrap;margin-left:5px;}
.btn-edit:hover{background:#d97706;}
.btn-devis{display:inline-flex;align-items:center;gap:4px;padding:5px 10px;background:#0d9488;color:#fff;border:none;border-radius:5px;font-size:12px;font-weight:700;cursor:pointer;white-space:nowrap;margin-left:5px;}
.btn-devis:hover{background:#0f766e;}

/* ── Modal reconfiguration ── */
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:1000;align-items:center;justify-content:center;}
.modal-overlay.open{display:flex;}
.modal{background:#fff;border-radius:12px;width:100%;max-width:520px;margin:16px;box-shadow:0 20px 60px rgba(0,0,0,.3);overflow:hidden;}
.modal-head{background:var(--navy);color:#fff;padding:16px 20px;display:flex;align-items:flex-start;justify-content:space-between;gap:12px;}
.modal-head h2{font-size:15px;font-weight:700;line-height:1.3;}
.modal-head p{font-size:12px;opacity:.75;margin-top:3px;}
.modal-close{background:none;border:none;color:#fff;font-size:20px;cursor:pointer;line-height:1;padding:0;opacity:.7;flex-shrink:0;}
.modal-close:hover{opacity:1;}
.modal-body{padding:20px;}
.m-section{background:#f8fafc;border:1px solid var(--border);border-radius:8px;padding:14px;margin-bottom:14px;}
.m-section-title{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:var(--muted);margin-bottom:10px;}
.m-row{display:flex;gap:10px;margin-bottom:10px;align-items:center;}
.m-row:last-child{margin-bottom:0;}
.m-label{font-size:12px;font-weight:600;color:var(--txt);min-width:120px;flex-shrink:0;}
.m-row input[type=number],.m-row input[type=text],.m-row select{flex:1;padding:7px 10px;border:1px solid var(--border);border-radius:6px;font-size:13px;font-family:inherit;}
.m-row input[type=number]:focus,.m-row input[type=text]:focus,.m-row select:focus{outline:none;border-color:var(--navy);}
.remise-badge{display:inline-block;background:#fef3c7;color:#92400e;border-radius:4px;padding:2px 8px;font-size:11px;font-weight:700;margin-left:6px;}
.groupe-info{font-size:11px;color:var(--muted);margin-top:4px;font-style:italic;}
.modal-foot{padding:14px 20px;border-top:1px solid var(--border);display:flex;gap:10px;justify-content:flex-end;background:#f8fafc;}
.btn-cancel{padding:8px 18px;background:#fff;border:1px solid var(--border);border-radius:6px;font-size:13px;font-weight:600;color:var(--muted);cursor:pointer;}
.btn-cancel:hover{background:#f1f5f9;}
.btn-generate{padding:8px 20px;background:var(--navy);color:#fff;border:none;border-radius:6px;font-size:13px;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:6px;}
.btn-generate:hover{background:#1a3a6e;}
.btn-generate:disabled{opacity:.5;cursor:wait;}
.format-pills{display:flex;gap:8px;}
.format-pill input{display:none;}
.format-pill label{padding:5px 14px;border:2px solid var(--border);border-radius:20px;font-size:12px;font-weight:700;cursor:pointer;transition:.15s;}
.format-pill input:checked+label{border-color:var(--navy);background:var(--navy);color:#fff;}
</style>
</head>
<body>
<div class="top-bar">
  <div>
    <img src="../assets/images/logo.png" alt="IBIG" style="height:28px;vertical-align:middle;margin-right:10px">
    <strong>IBIG EDUFORM</strong> · Historique des TDR
  </div>
  <div style="display:flex;gap:16px">
    <a href="tdr-generator.html">← Générateur TDR</a>
    <a href="tarif-guide.php">Guide Tarifaire</a>
  </div>
</div>

<div class="container">
  <h1>Historique des TDR générés</h1>
  <p class="subtitle"><?= $total ?> document<?= $total > 1 ? 's' : '' ?> enregistré<?= $total > 1 ? 's' : '' ?></p>

  <?php if (!$pdo || $dbError): ?>
  <div class="empty">
    <div class="empty-icon">⚠️</div>
    <div><?= $dbError ?: 'Base de données non disponible.' ?><br><br>
    <strong>Action requise :</strong> connectez-vous en SSH ou via le gestionnaire de fichiers et exécutez :<br>
    <code style="background:#f1f5f9;padding:4px 8px;border-radius:4px;display:inline-block;margin-top:8px">php migrations/tdr_system_2026.php</code>
    </div>
  </div>
  <?php else: ?>

  <!-- Filtres -->
  <form method="get" class="filters">
    <input type="text" name="q" value="<?= htmlspecialchars($_GET['q']??'') ?>" placeholder="Rechercher titre, client, référence…">
    <select name="type">
      <option value="">Tous types</option>
      <?php foreach ($typeLabels as $k=>$v): ?>
      <option value="<?= $k ?>"<?= ($typeF===$k)?'selected':'' ?>><?= $v ?></option>
      <?php endforeach; ?>
    </select>
    <select name="niveau">
      <option value="">Tous niveaux</option>
      <?php foreach ($niveauLabels as $k=>$v): ?>
      <option value="<?= $k ?>"<?= ($niveauF===$k)?'selected':'' ?>><?= $v ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit">Filtrer</button>
    <a href="tdr-historique.php" style="padding:7px 12px;color:var(--muted);font-size:13px;text-decoration:none;align-self:center">Réinitialiser</a>
  </form>

  <!-- Stats rapides -->
  <?php
  $stats = [];
  try { $stStats = $pdo->query("SELECT type_doc, COUNT(*) as cnt FROM tdr_historique GROUP BY type_doc"); $stats = $stStats ? $stStats->fetchAll(PDO::FETCH_KEY_PAIR) : []; } catch(\Throwable $e) {}
  ?>
  <div class="stats">
    <div class="stat-card"><div class="stat-val"><?= $total ?></div><div class="stat-lbl">Total TDR</div></div>
    <?php foreach ($typeLabels as $k=>$v): ?>
    <div class="stat-card"><div class="stat-val"><?= $stats[$k]??0 ?></div><div class="stat-lbl"><?= $v ?></div></div>
    <?php endforeach; ?>
  </div>

  <?php if (empty($rows)): ?>
  <div class="empty"><div class="empty-icon">📄</div><div>Aucun TDR trouvé<?= ($search||$typeF||$niveauF) ? ' pour ces critères' : '' ?>.</div></div>
  <?php else: ?>
  <table>
    <thead>
      <tr>
        <th>#</th>
        <th>Référence</th>
        <th>Titre</th>
        <th>Client</th>
        <th>Type</th>
        <th>Niveau</th>
        <th>Prix</th>
        <th>Date</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php
    $dureeMap = ['simplifie'=>'15 heures','moyen'=>'25 heures','detaille'=>'40 heures'];
    foreach ($rows as $i => $row):
      $tc = $typeColors[$row['type_doc']] ?? '#f1f5f9';
      $tt = $typeText[$row['type_doc']]   ?? '#374151';
      $po = (int)$row['prix_en_ligne'];
      $pp = (int)$row['prix_presentiel'];
      $nb = (int)($row['nb_participants'] ?? 1) ?: 1;
      $prixAff  = $pp > 0 ? number_format($pp,0,',',' ').' F' : ($po > 0 ? number_format($po,0,',',' ').' F' : '—');
      $fmtTarif = ($pp > 0 || $po > 0) ? 'individuel' : 'devis';
      $dureeEst = $dureeMap[$row['format_niveau']] ?? '25 heures';
      $modalite = $pp > 0 ? 'presentiel' : 'en_ligne';
      // Attributs data pour la modal
      $dataAttrs = implode(' ', [
        'data-id="'.(int)$row['id'].'"',
        'data-ref="'.htmlspecialchars($row['ref_doc']?:'',ENT_QUOTES).'"',
        'data-titre="'.htmlspecialchars($row['titre'],ENT_QUOTES).'"',
        'data-type="'.htmlspecialchars($row['type_doc'],ENT_QUOTES).'"',
        'data-prospect="'.htmlspecialchars($row['prospect']?:'',ENT_QUOTES).'"',
        'data-categorie="'.htmlspecialchars($row['categorie']?:'',ENT_QUOTES).'"',
        'data-niveau="'.htmlspecialchars($row['format_niveau'],ENT_QUOTES).'"',
        'data-duree="'.htmlspecialchars($dureeEst,ENT_QUOTES).'"',
        'data-nb="'.$nb.'"',
        'data-po="'.$po.'"',
        'data-pp="'.$pp.'"',
        'data-fmt="'.$fmtTarif.'"',
        'data-mode="'.$modalite.'"',
        'data-date="'.htmlspecialchars($row['date_debut']?:'',ENT_QUOTES).'"',
      ]);
    ?>
    <tr>
      <td style="color:var(--muted);font-size:11px"><?= $total - $i ?></td>
      <td style="font-size:11px;font-family:monospace;color:var(--muted)"><?= htmlspecialchars($row['ref_doc']?:'—') ?></td>
      <td style="font-weight:600;max-width:220px"><?= htmlspecialchars($row['titre']) ?></td>
      <td style="color:var(--muted)"><?= htmlspecialchars($row['prospect']?:'—') ?></td>
      <td><span class="badge" style="background:<?= $tc ?>;color:<?= $tt ?>"><?= $typeLabels[$row['type_doc']]??$row['type_doc'] ?></span></td>
      <td><span class="badge" style="background:#f1f5f9;color:#374151"><?= $niveauLabels[$row['format_niveau']]??$row['format_niveau'] ?></span></td>
      <td class="prix-cell"><?= $prixAff ?></td>
      <td style="font-size:11px;color:var(--muted);white-space:nowrap"><?= date('d/m/Y H:i', strtotime($row['date_generation'])) ?></td>
      <td style="white-space:nowrap">
        <!-- Bouton re-téléchargement direct -->
        <form method="post" action="tdr-export-pdf.php" target="_blank" style="display:inline">
          <input type="hidden" name="titre"           value="<?= htmlspecialchars($row['titre']) ?>">
          <input type="hidden" name="type_doc"        value="<?= htmlspecialchars($row['type_doc']) ?>">
          <input type="hidden" name="prospect_name"   value="<?= htmlspecialchars($row['prospect']?:'') ?>">
          <input type="hidden" name="categorie"       value="<?= htmlspecialchars($row['categorie']?:'') ?>">
          <input type="hidden" name="format_niveau"   value="<?= htmlspecialchars($row['format_niveau']) ?>">
          <input type="hidden" name="nb_participants" value="<?= $nb ?>">
          <input type="hidden" name="prix_en_ligne"   value="<?= $po ?>">
          <input type="hidden" name="prix_presentiel" value="<?= $pp ?>">
          <input type="hidden" name="fmt_tarif"       value="<?= $fmtTarif ?>">
          <input type="hidden" name="mode_formation"  value="<?= $modalite ?>">
          <input type="hidden" name="duree"           value="<?= $dureeEst ?>">
          <input type="hidden" name="date_debut"      value="<?= htmlspecialchars($row['date_debut']?:'') ?>">
          <button type="submit" class="btn-dl" onclick="this.classList.add('loading');this.textContent='⏳'">
            📥 PDF
          </button>
        </form>
        <!-- Bouton reconfigurer -->
        <button class="btn-edit" <?= $dataAttrs ?> onclick="openModal(this)">
          ✏️ Modifier
        </button>
        <!-- Bouton devis -->
        <button class="btn-devis" <?= $dataAttrs ?> onclick="openDevisModal(this)">
          💼 Devis
        </button>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
  <?php endif; ?>
</div>

<!-- ══════════════════════════════════════════
     MODAL RECONFIGURATION TDR
══════════════════════════════════════════ -->
<div class="modal-overlay" id="modalOverlay" onclick="if(event.target===this)closeModal()">
  <div class="modal">
    <div class="modal-head">
      <div>
        <h2 id="modalTitre">Reconfigurer le TDR</h2>
        <p id="modalProspect"></p>
      </div>
      <button class="modal-close" onclick="closeModal()">✕</button>
    </div>
    <div class="modal-body">

      <!-- Format -->
      <div class="m-section">
        <div class="m-section-title">Format de la formation</div>
        <div class="m-row">
          <span class="m-label">Format</span>
          <div class="format-pills">
            <div class="format-pill">
              <input type="radio" name="mFmt" id="fmtIndiv" value="individuel">
              <label for="fmtIndiv">👤 Individuel</label>
            </div>
            <div class="format-pill">
              <input type="radio" name="mFmt" id="fmtGroupe" value="groupe">
              <label for="fmtGroupe">👥 Groupe</label>
            </div>
          </div>
        </div>
        <div class="m-row" id="rowNbPart">
          <span class="m-label">Nb participants</span>
          <input type="number" id="mNbPart" min="1" max="200" value="1" style="max-width:90px">
          <span class="remise-badge" id="remiseBadge" style="display:none"></span>
        </div>
        <div class="groupe-info" id="groupeInfo" style="display:none"></div>
      </div>

      <!-- Modalité -->
      <div class="m-section">
        <div class="m-section-title">Modalité & Tarification</div>
        <div class="m-row">
          <span class="m-label">Modalité</span>
          <select id="mMode">
            <option value="en_ligne">En ligne (Classe Virtuelle)</option>
            <option value="presentiel">Présentiel</option>
            <option value="hybride">Hybride</option>
          </select>
        </div>
        <div class="m-row">
          <span class="m-label">Prix en ligne</span>
          <input type="number" id="mPo" min="0" step="1000" placeholder="ex: 260000">
          <span style="font-size:12px;color:var(--muted);margin-left:6px">F CFA</span>
        </div>
        <div class="m-row">
          <span class="m-label">Prix présentiel</span>
          <input type="number" id="mPp" min="0" step="1000" placeholder="ex: 320000">
          <span style="font-size:12px;color:var(--muted);margin-left:6px">F CFA</span>
        </div>
        <div class="groupe-info" id="prixInfo" style="color:#0369a1"></div>
      </div>

      <!-- Date -->
      <div class="m-section">
        <div class="m-section-title">Planification</div>
        <div class="m-row">
          <span class="m-label">Date souhaitée</span>
          <input type="text" id="mDate" placeholder="ex: 15/11/2026">
        </div>
      </div>

    </div>
    <div class="modal-foot">
      <button class="btn-cancel" onclick="closeModal()">Annuler</button>
      <button class="btn-generate" id="btnGenerate" onclick="generatePDF()">
        <span id="btnGenerateIcon">📄</span> Générer le nouveau PDF
      </button>
    </div>
  </div>
</div>

<script>
// ── Données de la ligne active ──
let _row = {};

// ── Grille remises groupe IBIG EDUFORM ──
function groupeRemise(nb) {
  if (nb >= 21) return { pct: 0,  label: '21+ participants — tarif sur devis' };
  if (nb >= 11) return { pct: 12, label: nb + ' participants · remise 12 %' };
  if (nb >= 6)  return { pct: 10, label: nb + ' participants · remise 10 %' };
  if (nb >= 3)  return { pct: 7,  label: nb + ' participants · remise 7 %' };
  if (nb === 2) return { pct: 5,  label: '2 participants · remise 5 %' };
  return { pct: 0, label: '' };
}

function fmt(n) {
  if (!n) return '';
  return new Intl.NumberFormat('fr-FR').format(n) + ' F CFA';
}

function applyRemise(base, pct) {
  if (!base || !pct) return base;
  return Math.round(base * (1 - pct / 100) / 1000) * 1000;
}

// ── Ouvrir la modal ──
function openModal(btn) {
  _row = {
    titre:     btn.dataset.titre,
    type:      btn.dataset.type,
    prospect:  btn.dataset.prospect,
    categorie: btn.dataset.categorie,
    niveau:    btn.dataset.niveau,
    duree:     btn.dataset.duree,
    nb:        parseInt(btn.dataset.nb) || 1,
    po:        parseInt(btn.dataset.po) || 0,
    pp:        parseInt(btn.dataset.pp) || 0,
    fmt:       btn.dataset.fmt,
    mode:      btn.dataset.mode,
    date:      btn.dataset.date,
  };

  document.getElementById('modalTitre').textContent = _row.titre;
  document.getElementById('modalProspect').textContent = _row.prospect || 'Client à compléter';
  document.getElementById('mNbPart').value = _row.nb;
  document.getElementById('mMode').value   = _row.mode;
  document.getElementById('mPo').value     = _row.po || '';
  document.getElementById('mPp').value     = _row.pp || '';
  document.getElementById('mDate').value   = _row.date || '';

  // Format pills
  const isGroupe = (_row.fmt === 'groupe' || _row.nb > 1);
  document.getElementById(isGroupe ? 'fmtGroupe' : 'fmtIndiv').checked = true;

  updateModal();
  document.getElementById('modalOverlay').classList.add('open');
  document.getElementById('mNbPart').focus();
}

function closeModal() {
  document.getElementById('modalOverlay').classList.remove('open');
  const btn = document.getElementById('btnGenerate');
  btn.disabled = false;
  btn.querySelector('#btnGenerateIcon').textContent = '📄';
  btn.lastChild.textContent = ' Générer le nouveau PDF';
}

// ── Mise à jour dynamique ──
function updateModal() {
  const nb     = parseInt(document.getElementById('mNbPart').value) || 1;
  const fmt    = document.querySelector('input[name=mFmt]:checked')?.value || 'individuel';
  const isGrp  = (fmt === 'groupe');
  const r      = groupeRemise(nb);

  // Badge remise
  const badge = document.getElementById('remiseBadge');
  if (isGrp && r.pct > 0) {
    badge.textContent = '- ' + r.pct + ' %';
    badge.style.display = 'inline-block';
  } else if (isGrp && nb >= 21) {
    badge.textContent = 'Sur devis';
    badge.style.display = 'inline-block';
  } else {
    badge.style.display = 'none';
  }

  // Info groupe
  const info = document.getElementById('groupeInfo');
  if (isGrp && r.label) {
    info.textContent = r.label;
    info.style.display = 'block';
  } else {
    info.style.display = 'none';
  }

  // Auto-calcul prix
  const prixInfo = document.getElementById('prixInfo');
  if (isGrp && r.pct > 0 && (_row.po > 0 || _row.pp > 0)) {
    const newPo = _row.po > 0 ? applyRemise(_row.po, r.pct) : 0;
    const newPp = _row.pp > 0 ? applyRemise(_row.pp, r.pct) : 0;
    document.getElementById('mPo').value = newPo || '';
    document.getElementById('mPp').value = newPp || '';
    let msg = '💡 Prix calculés automatiquement après remise ' + r.pct + '% :';
    if (newPo) msg += '  En ligne → ' + fmt(newPo);
    if (newPp) msg += '  |  Présentiel → ' + fmt(newPp);
    prixInfo.textContent = msg;
    prixInfo.style.display = 'block';
  } else if (!isGrp && _row.po === 0 && _row.pp === 0) {
    // Tarif devis — laisser vide
    prixInfo.textContent = 'Aucun prix fixé — le TDR sera généré sur devis.';
    prixInfo.style.display = 'block';
  } else {
    prixInfo.style.display = 'none';
  }
}

// ── Générer le PDF ──
function generatePDF() {
  const nb   = parseInt(document.getElementById('mNbPart').value) || 1;
  const fmt  = document.querySelector('input[name=mFmt]:checked')?.value || 'individuel';
  const mode = document.getElementById('mMode').value;
  const po   = parseInt(document.getElementById('mPo').value) || 0;
  const pp   = parseInt(document.getElementById('mPp').value) || 0;
  const date = document.getElementById('mDate').value.trim();

  // Validation minimale
  if (fmt === 'individuel' && po > 0 && po < 200000) {
    alert('⚠️ Prix en ligne minimum : 200 000 F CFA'); return;
  }
  if (fmt === 'individuel' && pp > 0 && pp < 250000) {
    alert('⚠️ Prix présentiel minimum : 250 000 F CFA'); return;
  }

  // Form POST natif (target=_blank) — évite le 401 Basic Auth sur fetch
  const form = document.createElement('form');
  form.method = 'POST';
  form.action = 'tdr-export-pdf.php';
  form.target = '_blank';
  const fields = {
    titre:           _row.titre,
    type_doc:        _row.type,
    prospect_name:   _row.prospect,
    categorie:       _row.categorie,
    format_niveau:   _row.niveau,
    duree:           _row.duree,
    nb_participants: String(nb),
    fmt_tarif:       fmt === 'groupe' && nb >= 21 ? 'devis' : fmt,
    mode_formation:  mode,
    prix_en_ligne:   String(po),
    prix_presentiel: String(pp),
    date_debut:      date,
  };
  for (const [k, v] of Object.entries(fields)) {
    const inp = document.createElement('input');
    inp.type = 'hidden'; inp.name = k; inp.value = v;
    form.appendChild(inp);
  }
  document.body.appendChild(form);
  form.submit();
  document.body.removeChild(form);
  closeModal();
  // Recharger l'historique pour voir la nouvelle entrée
  setTimeout(() => location.reload(), 2000);
}

// ── Événements ──
document.getElementById('mNbPart').addEventListener('input', function() {
  const nb = parseInt(this.value) || 1;
  // Auto-basculer le format
  if (nb >= 2) document.getElementById('fmtGroupe').checked = true;
  else         document.getElementById('fmtIndiv').checked  = true;
  updateModal();
});
document.querySelectorAll('input[name=mFmt]').forEach(r => r.addEventListener('change', function() {
  if (this.value === 'individuel') document.getElementById('mNbPart').value = 1;
  else if (parseInt(document.getElementById('mNbPart').value) < 2)
    document.getElementById('mNbPart').value = 2;
  updateModal();
}));
document.getElementById('mPo').addEventListener('input', () => {
  document.getElementById('prixInfo').style.display = 'none';
});
document.getElementById('mPp').addEventListener('input', () => {
  document.getElementById('prixInfo').style.display = 'none';
});
// Fermer avec Escape
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') { closeModal(); closeDevisModal(); }
});
</script>

<!-- ══════════════════════════════════════════
     MODAL DEVIS / PROFORMA
══════════════════════════════════════════ -->
<style>
/* ── Styles spécifiques au modal devis ── */
#devisOverlay .modal{max-width:540px;width:100%}
.tva-pills{display:flex;gap:8px;flex-wrap:wrap}
.tva-pill input[type=radio]{display:none}
.tva-pill label{display:inline-block;padding:5px 14px;border-radius:20px;font-size:12px;font-weight:700;cursor:pointer;border:2px solid #e2e8f0;color:var(--muted);transition:.15s}
.tva-pill input:checked+label{border-color:var(--navy);background:var(--navy);color:#fff}
.devis-total{background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:12px 16px;margin-top:4px}
.devis-total-row{display:flex;justify-content:space-between;font-size:13px;padding:2px 0;color:var(--txt)}
.devis-total-row.ttc{font-weight:800;font-size:15px;color:var(--navy);border-top:1px solid #bbf7d0;padding-top:8px;margin-top:6px}
.devis-info{font-size:11px;color:var(--muted);margin-top:6px;font-style:italic}
</style>

<div class="modal-overlay" id="devisOverlay" onclick="if(event.target===this)closeDevisModal()">
  <div class="modal">
    <div class="modal-head">
      <div>
        <h2 id="dModalTitre">Générer un Devis / Proforma</h2>
        <p id="dModalProspect" style="color:var(--muted);font-size:12px"></p>
      </div>
      <button class="modal-close" onclick="closeDevisModal()">✕</button>
    </div>
    <div class="modal-body">

      <!-- Client -->
      <div class="m-section">
        <div class="m-section-title">Identification client</div>
        <div class="m-row">
          <span class="m-label">Entreprise / Prospect</span>
          <input type="text" id="dProspect" placeholder="Nom entreprise ou client">
        </div>
        <div class="m-row">
          <span class="m-label">Contact (nom)</span>
          <input type="text" id="dContactNom" placeholder="Nom du responsable">
        </div>
        <div class="m-row">
          <span class="m-label">Email contact</span>
          <input type="email" id="dContactEmail" placeholder="email@entreprise.com">
        </div>
      </div>

      <!-- Formation -->
      <div class="m-section">
        <div class="m-section-title">Formation & Tarification</div>
        <div class="m-row">
          <span class="m-label">Nb participants</span>
          <input type="number" id="dNb" min="1" max="200" value="1" style="max-width:90px" oninput="updateDevisTotal()">
          <span class="remise-badge" id="dRemiseBadge" style="display:none;margin-left:8px"></span>
        </div>
        <div class="m-row">
          <span class="m-label">Modalité</span>
          <select id="dMode" onchange="updateDevisTotal()">
            <option value="presentiel">Présentiel</option>
            <option value="en_ligne">En ligne (Classe Virtuelle)</option>
            <option value="hybride">Hybride</option>
          </select>
        </div>
        <div class="m-row">
          <span class="m-label">Prix unitaire HT (F)</span>
          <input type="number" id="dPrixUnit" min="0" step="1000" placeholder="ex: 350000" oninput="updateDevisTotal()">
          <span style="font-size:12px;color:var(--muted);margin-left:6px">F CFA / participant</span>
        </div>
        <div class="m-row">
          <span class="m-label">Remise supplémentaire</span>
          <input type="number" id="dRemiseExtra" min="0" max="50" value="0" style="max-width:70px" oninput="updateDevisTotal()">
          <span style="font-size:12px;color:var(--muted);margin-left:6px">%</span>
        </div>
        <div class="m-row">
          <span class="m-label">TVA</span>
          <div class="tva-pills">
            <div class="tva-pill"><input type="radio" name="dTva" id="tva0" value="0" checked onchange="updateDevisTotal()"><label for="tva0">Exonéré (0%)</label></div>
            <div class="tva-pill"><input type="radio" name="dTva" id="tva18" value="18" onchange="updateDevisTotal()"><label for="tva18">TVA 18%</label></div>
          </div>
        </div>
      </div>

      <!-- Récapitulatif -->
      <div class="m-section">
        <div class="m-section-title">Récapitulatif financier</div>
        <div class="devis-total" id="devisRecap">
          <div class="devis-total-row"><span>Montant HT</span><span id="dHt">—</span></div>
          <div class="devis-total-row"><span id="dTvaLabel">TVA (0%)</span><span id="dTvaVal">0 F</span></div>
          <div class="devis-total-row ttc"><span>TOTAL TTC</span><span id="dTtc">—</span></div>
          <div class="devis-total-row"><span>Acompte 50%</span><span id="dAcompte">—</span></div>
        </div>
        <p class="devis-info" id="dRemiseInfo"></p>
      </div>

      <!-- Conditions -->
      <div class="m-section">
        <div class="m-section-title">Conditions du devis</div>
        <div class="m-row">
          <span class="m-label">Validité</span>
          <select id="dValidite">
            <option value="15">15 jours</option>
            <option value="30" selected>30 jours</option>
            <option value="45">45 jours</option>
            <option value="60">60 jours</option>
          </select>
        </div>
        <div class="m-row">
          <span class="m-label">Date de formation</span>
          <input type="text" id="dDateForm" placeholder="ex: 15 novembre 2026">
        </div>
        <div class="m-row" style="align-items:flex-start">
          <span class="m-label" style="padding-top:4px">Notes / Conditions spéciales</span>
          <textarea id="dNotes" rows="2" style="flex:1;padding:6px 10px;border:1px solid #e2e8f0;border-radius:6px;font-size:13px;resize:vertical" placeholder="Conditions particulières, délai paiement..."></textarea>
        </div>
      </div>

    </div>
    <div class="modal-foot">
      <button class="btn-cancel" onclick="closeDevisModal()">Annuler</button>
      <button class="btn-generate" id="btnDevisGenerate" onclick="generateDevis()" style="background:#0d9488">
        <span id="btnDevisIcon">💼</span> Générer le Proforma
      </button>
    </div>
  </div>
</div>

<script>
// ── Données de la ligne active pour le devis ──
let _drow = {};

function fmtF(n) {
  if (!n && n !== 0) return '—';
  return new Intl.NumberFormat('fr-FR').format(Math.round(n)) + ' F CFA';
}

function openDevisModal(btn) {
  _drow = {
    id:        btn.dataset.id || '',
    ref:       btn.dataset.ref || '',
    titre:     btn.dataset.titre,
    type:      btn.dataset.type,
    prospect:  btn.dataset.prospect,
    categorie: btn.dataset.categorie,
    niveau:    btn.dataset.niveau,
    duree:     btn.dataset.duree,
    nb:        parseInt(btn.dataset.nb) || 1,
    po:        parseInt(btn.dataset.po) || 0,
    pp:        parseInt(btn.dataset.pp) || 0,
    fmt:       btn.dataset.fmt,
    mode:      btn.dataset.mode,
    date:      btn.dataset.date,
  };

  document.getElementById('dModalTitre').textContent   = _drow.titre;
  document.getElementById('dModalProspect').textContent = 'Ref TDR : ' + (_drow.ref || '—') + ' · ' + (_drow.prospect || 'Client à compléter');
  document.getElementById('dProspect').value    = _drow.prospect || '';
  document.getElementById('dContactNom').value  = '';
  document.getElementById('dContactEmail').value = '';
  document.getElementById('dNb').value          = _drow.nb;
  document.getElementById('dMode').value        = _drow.mode === 'en_ligne' ? 'en_ligne' : 'presentiel';
  document.getElementById('dDateForm').value    = _drow.date || '';
  document.getElementById('dRemiseExtra').value = 0;
  document.getElementById('dNotes').value       = '';
  document.getElementById('tva0').checked       = true;

  // Prix unitaire par défaut : prix présentiel ou en ligne du TDR
  const prixBase = _drow.pp > 0 ? _drow.pp : (_drow.po > 0 ? _drow.po : 0);
  document.getElementById('dPrixUnit').value = prixBase || '';

  updateDevisTotal();
  document.getElementById('devisOverlay').classList.add('open');
  document.getElementById('dProspect').focus();
}

function closeDevisModal() {
  document.getElementById('devisOverlay').classList.remove('open');
  document.getElementById('btnDevisGenerate').disabled = false;
  document.getElementById('btnDevisIcon').textContent = '💼';
}

function updateDevisTotal() {
  const nb         = parseInt(document.getElementById('dNb').value) || 1;
  const prixUnit   = parseInt(document.getElementById('dPrixUnit').value) || 0;
  const remExtra   = parseInt(document.getElementById('dRemiseExtra').value) || 0;
  const tvaPct     = parseInt(document.querySelector('input[name=dTva]:checked')?.value || '0');

  // Remise groupe automatique
  const rGrp = groupeRemise(nb);
  const badge = document.getElementById('dRemiseBadge');
  if (nb >= 2 && rGrp.pct > 0) {
    badge.textContent = '- ' + rGrp.pct + ' % groupe';
    badge.style.display = 'inline-block';
  } else { badge.style.display = 'none'; }

  // Calcul
  const remiseTotale = Math.min(rGrp.pct + remExtra, 50);
  const htUnit       = Math.round(prixUnit * (1 - remiseTotale / 100) / 1000) * 1000;
  const montantHT    = htUnit * nb;
  const montantTVA   = Math.round(montantHT * tvaPct / 100);
  const montantTTC   = montantHT + montantTVA;
  const acompte      = Math.round(montantTTC * 0.5 / 1000) * 1000;

  document.getElementById('dHt').textContent       = fmtF(montantHT);
  document.getElementById('dTvaLabel').textContent = 'TVA (' + tvaPct + '%)';
  document.getElementById('dTvaVal').textContent   = fmtF(montantTVA);
  document.getElementById('dTtc').textContent      = fmtF(montantTTC);
  document.getElementById('dAcompte').textContent  = fmtF(acompte);

  let info = '';
  if (rGrp.pct > 0)   info += 'Remise groupe ' + rGrp.pct + '%';
  if (remExtra > 0)    info += (info ? ' + ' : '') + 'remise supplémentaire ' + remExtra + '%';
  if (remiseTotale > 0) info += ' → remise totale ' + remiseTotale + '%';
  document.getElementById('dRemiseInfo').textContent = info;
}

function generateDevis() {
  const nb        = parseInt(document.getElementById('dNb').value) || 1;
  const prixUnit  = parseInt(document.getElementById('dPrixUnit').value) || 0;
  const remExtra  = parseInt(document.getElementById('dRemiseExtra').value) || 0;
  const tvaPct    = parseInt(document.querySelector('input[name=dTva]:checked')?.value || '0');
  const prospect  = document.getElementById('dProspect').value.trim();
  const contactNom   = document.getElementById('dContactNom').value.trim();
  const contactEmail = document.getElementById('dContactEmail').value.trim();
  const mode      = document.getElementById('dMode').value;
  const validite  = document.getElementById('dValidite').value;
  const dateForm  = document.getElementById('dDateForm').value.trim();
  const notes     = document.getElementById('dNotes').value.trim();

  if (!prixUnit || prixUnit < 1) { alert('⚠️ Veuillez saisir un prix unitaire HT.'); return; }
  if (!prospect)                  { alert('⚠️ Veuillez saisir le nom du client / prospect.'); return; }

  const rGrp         = groupeRemise(nb);
  const remiseTotale = Math.min(rGrp.pct + remExtra, 50);
  const htUnit       = Math.round(prixUnit * (1 - remiseTotale / 100) / 1000) * 1000;
  const montantHT    = htUnit * nb;
  const montantTVA   = Math.round(montantHT * tvaPct / 100);
  const montantTTC   = montantHT + montantTVA;

  const btn = document.getElementById('btnDevisGenerate');
  btn.disabled = true;
  document.getElementById('btnDevisIcon').textContent = '⏳';

  // Form POST natif → devis-export-pdf.php (même pattern que TDR)
  const form = document.createElement('form');
  form.method = 'POST';
  form.action = 'devis-export-pdf.php';
  form.target = '_blank';

  const fields = {
    tdr_id:          _drow.id,
    tdr_ref:         _drow.ref,
    titre_formation: _drow.titre,
    type_doc:        _drow.type,
    categorie:       _drow.categorie,
    duree:           _drow.duree,
    prospect:        prospect,
    contact_nom:     contactNom,
    contact_email:   contactEmail,
    nb_participants: String(nb),
    mode_formation:  mode,
    prix_unitaire_ht: String(prixUnit),
    remise_pct:      String(remiseTotale),
    remise_grp_pct:  String(rGrp.pct),
    remise_extra_pct: String(remExtra),
    tva_pct:         String(tvaPct),
    montant_ht:      String(montantHT),
    montant_tva:     String(montantTVA),
    montant_ttc:     String(montantTTC),
    validite_jours:  validite,
    date_formation:  dateForm,
    notes:           notes,
  };

  for (const [k, v] of Object.entries(fields)) {
    const inp = document.createElement('input');
    inp.type = 'hidden'; inp.name = k; inp.value = v;
    form.appendChild(inp);
  }
  document.body.appendChild(form);
  form.submit();
  document.body.removeChild(form);

  closeDevisModal();
  // Pas de reload ici — le PDF s'ouvre dans un nouvel onglet
  setTimeout(() => { btn.disabled = false; document.getElementById('btnDevisIcon').textContent = '💼'; }, 3000);
}
</script>
</body>
</html>
