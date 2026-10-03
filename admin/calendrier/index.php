<?php
declare(strict_types=1);
/* ============================================================
   ADMIN — PLANNING INSCRIPTIONS
   Suivi des préinscriptions et formations programmées
============================================================ */
require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../core/csrf.php';
require_once __DIR__ . '/../auth/middleware.php';
require_once __DIR__ . '/../auth/guard.php';

Middleware::requireAuth();
$u   = auth_user();
$pdo = Database::connect();

/* ── Création de la table si nécessaire ─────────────────── */
$pdo->exec("
CREATE TABLE IF NOT EXISTS `planning_inscriptions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `preinscription_id` INT UNSIGNED DEFAULT NULL,
  `nom_participant` VARCHAR(150) NOT NULL DEFAULT '',
  `email_participant` VARCHAR(150) DEFAULT NULL,
  `telephone_participant` VARCHAR(50) DEFAULT NULL,
  `titre_formation` VARCHAR(255) NOT NULL DEFAULT '',
  `formation_id` INT UNSIGNED DEFAULT NULL,
  `date_preinscription` DATE DEFAULT NULL,
  `date_debut_envisagee` DATE DEFAULT NULL,
  `date_debut_reelle` DATE DEFAULT NULL,
  `montant_total` DECIMAL(12,2) DEFAULT NULL,
  `montant_verse` DECIMAL(12,2) DEFAULT NULL,
  `mode_formation` ENUM('en_ligne','presentiel','hybride') NOT NULL DEFAULT 'en_ligne',
  `format_formation` VARCHAR(50) DEFAULT 'individuel',
  `statut` ENUM('attente','programme','en_cours','acheve','abandonne') NOT NULL DEFAULT 'attente',
  `notes` TEXT DEFAULT NULL,
  `created_by` INT UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_preinscription` (`preinscription_id`),
  KEY `idx_formation` (`formation_id`),
  KEY `idx_statut` (`statut`),
  KEY `idx_date` (`date_debut_envisagee`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

/* ── Traitement POST ────────────────────────────────────── */
$flash = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = trim((string)($_POST['_action'] ?? ''));

    /* ── SAVE (add / edit) ── */
    if ($action === 'save') {
        $id   = (int)($_POST['id'] ?? 0);
        $data = [
            'nom_participant'      => trim((string)($_POST['nom_participant'] ?? '')),
            'email_participant'    => trim((string)($_POST['email_participant'] ?? '')) ?: null,
            'telephone_participant'=> trim((string)($_POST['telephone_participant'] ?? '')) ?: null,
            'titre_formation'      => trim((string)($_POST['titre_formation'] ?? '')),
            'formation_id'         => ((int)($_POST['formation_id'] ?? 0)) ?: null,
            'preinscription_id'    => ((int)($_POST['preinscription_id'] ?? 0)) ?: null,
            'date_preinscription'  => trim((string)($_POST['date_preinscription'] ?? '')) ?: null,
            'date_debut_envisagee' => trim((string)($_POST['date_debut_envisagee'] ?? '')) ?: null,
            'date_debut_reelle'    => trim((string)($_POST['date_debut_reelle'] ?? '')) ?: null,
            'montant_total'        => trim((string)($_POST['montant_total'] ?? '')) !== '' ? (float)$_POST['montant_total'] : null,
            'montant_verse'        => trim((string)($_POST['montant_verse'] ?? '')) !== '' ? (float)$_POST['montant_verse'] : null,
            'mode_formation'       => in_array($_POST['mode_formation'] ?? '', ['en_ligne','presentiel','hybride'], true) ? $_POST['mode_formation'] : 'en_ligne',
            'format_formation'     => trim((string)($_POST['format_formation'] ?? 'individuel')),
            'statut'               => in_array($_POST['statut'] ?? '', ['attente','programme','en_cours','acheve','abandonne'], true) ? $_POST['statut'] : 'attente',
            'notes'                => trim((string)($_POST['notes'] ?? '')) ?: null,
            'created_by'           => (int)($u['id'] ?? 0) ?: null,
        ];
        if ($id > 0) {
            $sets = implode(', ', array_map(fn($k) => "`$k` = :$k", array_keys($data)));
            $stmt = $pdo->prepare("UPDATE planning_inscriptions SET $sets WHERE id = :__id");
            $data[':__id'] = $id;
            $stmt->execute(array_combine(
                array_map(fn($k) => ":$k", array_keys($data)),
                array_values($data)
            ));
            $flash = ['ok', 'Entrée mise à jour.'];
        } else {
            $cols = '`' . implode('`, `', array_keys($data)) . '`';
            $vals = implode(', ', array_map(fn($k) => ":$k", array_keys($data)));
            $stmt = $pdo->prepare("INSERT INTO planning_inscriptions ($cols) VALUES ($vals)");
            $stmt->execute(array_combine(
                array_map(fn($k) => ":$k", array_keys($data)),
                array_values($data)
            ));
            $flash = ['ok', 'Entrée ajoutée.'];
        }
    }

    /* ── DELETE ── */
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $pdo->prepare("DELETE FROM planning_inscriptions WHERE id = ?")->execute([$id]);
            $flash = ['ok', 'Entrée supprimée.'];
        }
    }

    /* ── STATUS ── */
    if ($action === 'status') {
        $id  = (int)($_POST['id'] ?? 0);
        $st  = $_POST['statut'] ?? '';
        $valid = ['attente','programme','en_cours','acheve','abandonne'];
        if ($id > 0 && in_array($st, $valid, true)) {
            $pdo->prepare("UPDATE planning_inscriptions SET statut = ? WHERE id = ?")->execute([$st, $id]);
        }
        header('Content-Type: application/json');
        echo json_encode(['ok' => true]);
        exit;
    }

    /* ── IMPORT depuis preinscriptions ── */
    if ($action === 'import') {
        $ids = array_filter(array_map('intval', (array)($_POST['pre_ids'] ?? [])));
        $imported = 0;
        foreach ($ids as $pid) {
            $pre = $pdo->prepare("SELECT p.*, f.titre AS ftit, f.id AS fid, f.tarif_en_ligne FROM preinscriptions p LEFT JOIN formations f ON f.id = p.formation_id WHERE p.id = ?");
            $pre->execute([$pid]);
            $row = $pre->fetch(PDO::FETCH_ASSOC);
            if (!$row) continue;
            /* Éviter les doublons */
            $exists = $pdo->prepare("SELECT id FROM planning_inscriptions WHERE preinscription_id = ?");
            $exists->execute([$pid]);
            if ($exists->fetchColumn()) continue;
            $ins = $pdo->prepare("INSERT INTO planning_inscriptions
              (preinscription_id, nom_participant, email_participant, telephone_participant,
               titre_formation, formation_id, date_preinscription, montant_total, mode_formation, statut, created_by)
              VALUES (?,?,?,?,?,?,?,?,?,?,?)");
            $ins->execute([
                $pid,
                trim((string)$row['nom'] . ' ' . (string)$row['prenoms']),
                $row['email'] ?? null,
                $row['telephone'] ?? null,
                $row['ftit'] ?? ($row['formation'] ?? ''),
                $row['fid'] ? (int)$row['fid'] : null,
                !empty($row['created_at']) ? date('Y-m-d', strtotime((string)$row['created_at'])) : null,
                $row['tarif_en_ligne'] ? (float)$row['tarif_en_ligne'] : null,
                'en_ligne',
                'attente',
                (int)($u['id'] ?? 0) ?: null,
            ]);
            $imported++;
        }
        $flash = ['ok', "$imported préinscription(s) importée(s)."];
    }
}

/* ── Filtres ────────────────────────────────────────────── */
$fStatut = trim((string)($_GET['statut'] ?? ''));
$fMode   = trim((string)($_GET['mode'] ?? ''));
$fQ      = trim((string)($_GET['q'] ?? ''));
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 40;

$where  = [];
$params = [];
if ($fStatut !== '') { $where[] = 'statut = :st';   $params[':st']  = $fStatut; }
if ($fMode   !== '') { $where[] = 'mode_formation = :md'; $params[':md'] = $fMode; }
if ($fQ      !== '') {
    $where[] = '(nom_participant LIKE :q1 OR email_participant LIKE :q2 OR titre_formation LIKE :q3 OR telephone_participant LIKE :q4)';
    $qp = '%' . $fQ . '%';
    $params[':q1'] = $qp; $params[':q2'] = $qp; $params[':q3'] = $qp; $params[':q4'] = $qp;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

/* Counts */
$cs = $pdo->prepare("SELECT COUNT(*) FROM planning_inscriptions $whereSql");
$cs->execute($params);
$totalRows = (int)$cs->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

/* KPIs */
$kpis = $pdo->query("SELECT
    COUNT(*) AS total,
    SUM(statut='attente') AS attente,
    SUM(statut='programme') AS programme,
    SUM(statut='en_cours') AS en_cours,
    SUM(statut='acheve') AS acheve,
    SUM(statut='abandonne') AS abandonne,
    SUM(montant_total) AS ca_total,
    SUM(montant_verse) AS ca_verse
  FROM planning_inscriptions")->fetch(PDO::FETCH_ASSOC);

/* Rows */
$stmt = $pdo->prepare("SELECT * FROM planning_inscriptions $whereSql ORDER BY created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* Liste formations pour selects */
$formations = $pdo->query("SELECT id, titre FROM formations WHERE statut='active' ORDER BY titre ASC")->fetchAll(PDO::FETCH_ASSOC);

/* Préinscriptions non encore importées (pour la modal d'import) */
$preNonImportees = $pdo->query("
  SELECT p.id, p.nom, p.prenoms, p.email, p.telephone, f.titre AS ftit, p.created_at
  FROM preinscriptions p
  LEFT JOIN formations f ON f.id = p.formation_id
  WHERE p.id NOT IN (SELECT preinscription_id FROM planning_inscriptions WHERE preinscription_id IS NOT NULL)
  ORDER BY p.created_at DESC
  LIMIT 200
")->fetchAll(PDO::FETCH_ASSOC);

/* ── Helpers ─────────────────────────────────────────────── */
$statutLabels = [
    'attente'   => 'En attente',
    'programme' => 'Programmé',
    'en_cours'  => 'En cours',
    'acheve'    => 'Achevé',
    'abandonne' => 'Abandonné',
];
$statutColors = [
    'attente'   => '#b45309',
    'programme' => '#6d28d9',
    'en_cours'  => '#1d4ed8',
    'acheve'    => '#15803d',
    'abandonne' => '#b91c1c',
];
$statutBg = [
    'attente'   => '#fff7ed',
    'programme' => '#ede9fe',
    'en_cours'  => '#eff6ff',
    'acheve'    => '#f0fdf4',
    'abandonne' => '#fef2f2',
];
$modeLabels = [
    'en_ligne'   => 'En ligne',
    'presentiel' => 'Présentiel',
    'hybride'    => 'Hybride',
];
$fcfa = fn(?float $v): string => $v ? number_format((int)$v, 0, ',', ' ') . ' F' : '—';

/* ── Layout ─────────────────────────────────────────────── */
$pageTitle  = 'Planning des formations';
$activeMenu = 'planning';
ob_start();
?>

<style>
/* =====================================================
   PLANNING INSCRIPTIONS — IBIG EDUFORM
===================================================== */
.pl-kpis{display:grid;grid-template-columns:repeat(6,1fr);gap:10px;margin:0 0 20px}
.pl-kpi{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:14px 16px;box-shadow:0 4px 16px rgba(15,23,42,.05)}
.pl-kpi b{display:block;font-size:22px;font-weight:900;line-height:1.1;color:#0f172a}
.pl-kpi span{font-size:11.5px;color:#6b7280;font-weight:600}
.pl-kpi.k-blue b{color:#1d4ed8}
.pl-kpi.k-purple b{color:#6d28d9}
.pl-kpi.k-green b{color:#15803d}
.pl-kpi.k-red b{color:#b91c1c}
.pl-kpi.k-amber b{color:#b45309}

.pl-topbar{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin:0 0 14px}
.pl-topbar h2{margin:0;font-size:1.15rem;font-weight:900;color:#0f172a}
.pl-topbar-btns{display:flex;gap:8px;flex-wrap:wrap}

.pl-filters{display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end;background:#f8fafc;border:1px solid #e5e7eb;border-radius:12px;padding:12px 14px;margin-bottom:14px}
.pl-filters label{font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.3px;display:block;margin-bottom:3px}
.pl-filters input,.pl-filters select{padding:8px 12px;border:1px solid #e5e7eb;border-radius:9px;font-size:13.5px;background:#fff;outline:none}
.pl-filters input:focus,.pl-filters select:focus{border-color:#1f3fe0;box-shadow:0 0 0 3px rgba(31,63,224,.1)}
.btn-filter{background:linear-gradient(135deg,#0a1733,#1e3a6e);color:#fff;border:0;padding:9px 16px;border-radius:9px;font-weight:800;font-size:13px;cursor:pointer;white-space:nowrap}
.btn-clear{background:#fff;border:1px solid #e5e7eb;color:#64748b;padding:9px 13px;border-radius:9px;font-size:13px;text-decoration:none;white-space:nowrap}

.pl-table{width:100%;border-collapse:collapse}
.pl-table th{font-size:11px;text-transform:uppercase;letter-spacing:.4px;color:#6b7280;text-align:left;padding:10px 12px;border-bottom:2px solid #eef2f7;white-space:nowrap}
.pl-table td{padding:11px 12px;border-bottom:1px solid #f1f5f9;vertical-align:middle;font-size:13.5px}
.pl-table tr:hover td{background:#fafbff}

.pl-nom strong{color:#0f172a;font-size:14px}
.pl-nom small{color:#6b7280;font-size:12px}
.pl-contact a{color:#1f3fe0;text-decoration:none;font-size:12.5px}
.pl-contact a:hover{text-decoration:underline}

.pill-st{display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:999px;font-size:11.5px;font-weight:700;white-space:nowrap}
.pill-mode{display:inline-block;padding:3px 8px;border-radius:6px;font-size:11px;font-weight:700;white-space:nowrap}
.pill-en_ligne{background:#e0f2fe;color:#075985}
.pill-presentiel{background:#fef3c7;color:#92400e}
.pill-hybride{background:#f0fdf4;color:#166534}

.stat-sel{padding:5px 8px;border:1px solid #e5e7eb;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;background:#fff;outline:none}
.stat-sel:focus{border-color:#1f3fe0}

.act-btn{width:32px;height:32px;display:inline-flex;align-items:center;justify-content:center;border-radius:9px;font-size:14px;border:1px solid transparent;cursor:pointer;text-decoration:none;transition:all .15s}
.act-edit{background:#e0ecff;color:#1e40af;border-color:#bfdbfe}
.act-edit:hover{background:#c7ddff}
.act-del{background:#fee2e2;color:#991b1b;border-color:#fecaca}
.act-del:hover{background:#fecaca}
.act-wa{background:#dcfce7;color:#15803d;border-color:#bbf7d0}
.act-wa:hover{background:#bbf7d0}

/* Boutons actions rapides */
.btn-add{background:linear-gradient(135deg,#0a1733,#1e3a6e);color:#fff;border:0;padding:10px 18px;border-radius:10px;font-weight:800;font-size:13.5px;cursor:pointer;display:inline-flex;align-items:center;gap:7px;text-decoration:none}
.btn-import{background:linear-gradient(135deg,#6d28d9,#7c3aed);color:#fff;border:0;padding:10px 16px;border-radius:10px;font-weight:800;font-size:13px;cursor:pointer;display:inline-flex;align-items:center;gap:7px}

/* Modal */
.pl-modal-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:1000;align-items:center;justify-content:center;padding:16px}
.pl-modal-bg.open{display:flex}
.pl-modal{background:#fff;border-radius:18px;width:100%;max-width:640px;max-height:92vh;overflow-y:auto;box-shadow:0 30px 80px rgba(0,0,0,.28);position:relative}
.pl-modal-hd{padding:24px 28px 0;display:flex;justify-content:space-between;align-items:flex-start}
.pl-modal-hd h3{font-size:1.1rem;font-weight:900;color:#0f172a;margin:0}
.pl-modal-hd .sub{font-size:13px;color:#6b7280;margin:4px 0 0}
.modal-x{background:none;border:0;font-size:22px;color:#9ca3af;cursor:pointer;line-height:1;padding:0}
.pl-modal-body{padding:20px 28px 28px;display:grid;grid-template-columns:1fr 1fr;gap:14px}
.pl-modal-body .full{grid-column:1/-1}
.pl-modal-body label{font-size:11.5px;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.3px;display:block;margin-bottom:4px}
.pl-modal-body input,.pl-modal-body select,.pl-modal-body textarea{width:100%;padding:10px 12px;border:1px solid #e5e7eb;border-radius:9px;font-size:13.5px;outline:none;background:#f9fafb;font-family:inherit;box-sizing:border-box}
.pl-modal-body input:focus,.pl-modal-body select:focus,.pl-modal-body textarea:focus{border-color:#1f3fe0;background:#fff;box-shadow:0 0 0 3px rgba(31,63,224,.1)}
.pl-modal-body textarea{resize:vertical;min-height:70px}
.pl-modal-footer{padding:0 28px 24px;display:flex;gap:10px}
.btn-submit{flex:1;background:linear-gradient(135deg,#0a1733,#1e3a6e);color:#fff;font-weight:900;padding:13px;border-radius:10px;border:0;font-size:14.5px;cursor:pointer}
.btn-cancel{background:#f1f5f9;color:#64748b;font-weight:700;padding:13px 20px;border-radius:10px;border:0;font-size:14px;cursor:pointer}

/* Modal import */
.imp-list{max-height:340px;overflow-y:auto;border:1px solid #e5e7eb;border-radius:10px;margin:12px 0}
.imp-row{display:flex;align-items:center;gap:10px;padding:10px 14px;border-bottom:1px solid #f1f5f9;cursor:pointer}
.imp-row:last-child{border-bottom:0}
.imp-row:hover{background:#f8faff}
.imp-row input[type=checkbox]{width:16px;height:16px;cursor:pointer;flex-shrink:0}
.imp-row-info strong{font-size:13.5px;color:#0f172a}
.imp-row-info small{display:block;font-size:12px;color:#6b7280}

/* Flash */
.pl-flash{padding:12px 18px;border-radius:10px;font-weight:700;font-size:13.5px;margin-bottom:16px;display:flex;align-items:center;gap:8px}
.pl-flash.ok{background:#dcfce7;color:#15803d;border:1px solid #bbf7d0}
.pl-flash.err{background:#fee2e2;color:#b91c1c;border:1px solid #fecaca}

/* Montants */
.ca-block{display:flex;gap:6px;flex-direction:column}
.ca-total{font-weight:800;color:#0f172a;font-size:13.5px}
.ca-verse{font-size:12px;color:#16a34a;font-weight:700}
.ca-reste{font-size:12px;color:#b45309;font-weight:600}

/* Pagination */
.pl-pag{display:flex;justify-content:center;align-items:center;gap:5px;padding:16px 0;flex-wrap:wrap}
.pl-pag a{padding:7px 12px;border:1px solid #e5e7eb;border-radius:8px;font-size:13px;color:#374151;text-decoration:none;background:#fff}
.pl-pag a.cur{background:#0a1733;color:#fff;border-color:#0a1733;font-weight:700}
.pl-pag a:hover:not(.cur){background:#f1f5f9}

/* CA summary bar */
.ca-bar{display:flex;gap:16px;flex-wrap:wrap;background:#0a1733;border-radius:12px;padding:14px 20px;margin-bottom:18px}
.ca-bar-item{color:#fff}
.ca-bar-item .lbl{font-size:11px;color:rgba(255,255,255,.6);font-weight:600;text-transform:uppercase;letter-spacing:.3px}
.ca-bar-item .val{font-size:18px;font-weight:900;color:#f5a623;line-height:1.2}
.ca-bar-sep{width:1px;background:rgba(255,255,255,.15);align-self:stretch}

@media(max-width:900px){
  .pl-kpis{grid-template-columns:repeat(3,1fr)}
  .ca-bar{gap:10px}
}
@media(max-width:600px){
  .pl-kpis{grid-template-columns:1fr 1fr}
  .pl-modal-body{grid-template-columns:1fr}
}
</style>

<?php if ($flash): ?>
  <div class="pl-flash <?= $flash[0] ?>">
    <?= $flash[0] === 'ok' ? '✅' : '❌' ?> <?= e($flash[1]) ?>
  </div>
<?php endif; ?>

<!-- TOPBAR -->
<div class="pl-topbar">
  <div>
    <h2>📅 Planning des formations</h2>
  </div>
  <div class="pl-topbar-btns">
    <?php if (count($preNonImportees) > 0): ?>
      <button type="button" class="btn-import" onclick="openImport()">
        ⬇ Importer préinscriptions <span style="background:rgba(255,255,255,.25);padding:2px 8px;border-radius:999px;font-size:12px;margin-left:2px"><?= count($preNonImportees) ?></span>
      </button>
    <?php endif; ?>
    <button type="button" class="btn-add" onclick="openAdd()">＋ Ajouter manuellement</button>
  </div>
</div>

<!-- KPIs -->
<div class="pl-kpis">
  <div class="pl-kpi"><b><?= (int)$kpis['total'] ?></b><span>Total</span></div>
  <div class="pl-kpi k-amber"><b><?= (int)$kpis['attente'] ?></b><span>En attente</span></div>
  <div class="pl-kpi k-purple"><b><?= (int)$kpis['programme'] ?></b><span>Programmés</span></div>
  <div class="pl-kpi k-blue"><b><?= (int)$kpis['en_cours'] ?></b><span>En cours</span></div>
  <div class="pl-kpi k-green"><b><?= (int)$kpis['acheve'] ?></b><span>Achevés</span></div>
  <div class="pl-kpi k-red"><b><?= (int)$kpis['abandonne'] ?></b><span>Abandonnés</span></div>
</div>

<!-- BARRE CA -->
<?php if ($kpis['ca_total'] > 0): ?>
<div class="ca-bar">
  <div class="ca-bar-item"><div class="lbl">CA total programmé</div><div class="val"><?= $fcfa((float)$kpis['ca_total']) ?></div></div>
  <div class="ca-bar-sep"></div>
  <div class="ca-bar-item"><div class="lbl">Montant versé</div><div class="val"><?= $fcfa((float)$kpis['ca_verse']) ?></div></div>
  <div class="ca-bar-sep"></div>
  <div class="ca-bar-item">
    <div class="lbl">Reste à percevoir</div>
    <div class="val" style="color:#f87171"><?= $fcfa(max(0, (float)$kpis['ca_total'] - (float)$kpis['ca_verse'])) ?></div>
  </div>
  <div class="ca-bar-sep"></div>
  <div class="ca-bar-item"><div class="lbl">Taux d'achèvement</div>
    <div class="val"><?= $kpis['total'] > 0 ? round((int)$kpis['acheve'] / (int)$kpis['total'] * 100) : 0 ?>%</div>
  </div>
</div>
<?php endif; ?>

<!-- FILTRES -->
<form method="GET" class="pl-filters">
  <div>
    <label>Recherche</label>
    <input type="text" name="q" value="<?= e($fQ) ?>" placeholder="Nom, email, tél, formation…" style="width:220px">
  </div>
  <div>
    <label>Statut</label>
    <select name="statut">
      <option value="">Tous</option>
      <?php foreach ($statutLabels as $sv => $sl): ?>
        <option value="<?= $sv ?>" <?= $fStatut === $sv ? 'selected' : '' ?>><?= $sl ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div>
    <label>Mode</label>
    <select name="mode">
      <option value="">Tous</option>
      <?php foreach ($modeLabels as $mv => $ml): ?>
        <option value="<?= $mv ?>" <?= $fMode === $mv ? 'selected' : '' ?>><?= $ml ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div style="display:flex;gap:7px;align-items:flex-end">
    <button type="submit" class="btn-filter">Filtrer</button>
    <a href="?" class="btn-clear">✕ Réinitialiser</a>
  </div>
  <div class="muted" style="align-self:center;font-size:13px;color:#64748b">
    <?= $totalRows ?> résultat<?= $totalRows > 1 ? 's' : '' ?>
    <?= $totalPages > 1 ? " — page $page/$totalPages" : '' ?>
  </div>
</form>

<!-- TABLE -->
<?php if (empty($rows)): ?>
  <div style="text-align:center;padding:60px 20px;color:#9ca3af">
    <div style="font-size:3rem;margin-bottom:12px">📋</div>
    <p style="font-size:1rem;font-weight:600">Aucune entrée trouvée.</p>
    <p style="font-size:13px">Importez des préinscriptions ou ajoutez une formation manuellement.</p>
  </div>
<?php else: ?>
<div style="overflow-x:auto">
<table class="pl-table">
  <thead>
    <tr>
      <th>#</th>
      <th>Participant</th>
      <th>Contact</th>
      <th>Formation</th>
      <th>Date préinscription</th>
      <th>Début envisagé</th>
      <th>Montant</th>
      <th>Mode</th>
      <th>Statut</th>
      <th>Actions</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($rows as $r):
      $st  = $r['statut'] ?? 'attente';
      $waNum = preg_replace('/\D+/', '', (string)($r['telephone_participant'] ?? ''));
      if ($waNum && !str_starts_with($waNum, '225') && strlen($waNum) <= 10) $waNum = '225' . $waNum;
      $waHref = $waNum ? 'https://wa.me/' . $waNum . '?text=' . rawurlencode('Bonjour ' . $r['nom_participant'] . ', ici l\'équipe IBIG EDUFORM. Nous revenons vers vous au sujet de votre formation « ' . $r['titre_formation'] . ' ». Comment pouvons-nous vous accompagner ?') : '';
      $mt = $r['montant_total'] !== null ? (float)$r['montant_total'] : null;
      $mv = $r['montant_verse'] !== null ? (float)$r['montant_verse'] : null;
    ?>
    <tr>
      <td style="color:#9ca3af;font-size:12px">#<?= $r['id'] ?>
        <?php if (!empty($r['preinscription_id'])): ?>
          <br><a href="../preinscriptions/index.php" style="font-size:11px;color:#6d28d9" title="Préinscription #<?= $r['preinscription_id'] ?>">↗ P<?= $r['preinscription_id'] ?></a>
        <?php endif; ?>
      </td>

      <td class="pl-nom">
        <strong><?= e($r['nom_participant']) ?></strong>
      </td>

      <td class="pl-contact">
        <?php if (!empty($r['telephone_participant'])): ?>
          <a href="tel:<?= e(preg_replace('/[^\d+]/', '', $r['telephone_participant'])) ?>">📞 <?= e($r['telephone_participant']) ?></a><br>
        <?php endif; ?>
        <?php if (!empty($r['email_participant'])): ?>
          <a href="mailto:<?= e($r['email_participant']) ?>">✉ <?= e($r['email_participant']) ?></a>
        <?php endif; ?>
      </td>

      <td style="max-width:200px">
        <strong style="font-size:13px;color:#0f172a"><?= e($r['titre_formation'] ?: '—') ?></strong>
        <?php if (!empty($r['format_formation'])): ?>
          <br><small style="color:#6b7280"><?= e($r['format_formation']) ?></small>
        <?php endif; ?>
      </td>

      <td style="font-size:12.5px;color:#374151;white-space:nowrap">
        <?= !empty($r['date_preinscription']) ? date('d/m/Y', strtotime($r['date_preinscription'])) : '—' ?>
      </td>

      <td style="white-space:nowrap">
        <?php if (!empty($r['date_debut_envisagee'])): ?>
          <strong style="font-size:13px;color:#1d4ed8"><?= date('d/m/Y', strtotime($r['date_debut_envisagee'])) ?></strong>
        <?php else: ?>
          <span style="color:#9ca3af;font-size:12.5px">—</span>
        <?php endif; ?>
        <?php if (!empty($r['date_debut_reelle'])): ?>
          <br><small style="color:#15803d">✓ <?= date('d/m/Y', strtotime($r['date_debut_reelle'])) ?></small>
        <?php endif; ?>
      </td>

      <td class="ca-block">
        <?php if ($mt !== null): ?>
          <span class="ca-total"><?= $fcfa($mt) ?></span>
          <?php if ($mv !== null): ?>
            <span class="ca-verse">✓ <?= $fcfa($mv) ?></span>
            <?php if ($mt > $mv): ?>
              <span class="ca-reste">△ <?= $fcfa($mt - $mv) ?></span>
            <?php endif; ?>
          <?php endif; ?>
        <?php else: ?>
          <span style="color:#9ca3af;font-size:12.5px">—</span>
        <?php endif; ?>
      </td>

      <td>
        <span class="pill-mode pill-<?= $r['mode_formation'] ?>"><?= $modeLabels[$r['mode_formation']] ?? $r['mode_formation'] ?></span>
      </td>

      <td>
        <select class="stat-sel" data-id="<?= $r['id'] ?>" onchange="updateStatut(this)"
          style="color:<?= $statutColors[$st] ?? '#374151' ?>;border-color:<?= $statutColors[$st] ?? '#e5e7eb' ?>">
          <?php foreach ($statutLabels as $sv => $sl): ?>
            <option value="<?= $sv ?>" <?= $st === $sv ? 'selected' : '' ?>><?= $sl ?></option>
          <?php endforeach; ?>
        </select>
      </td>

      <td>
        <div style="display:flex;gap:5px">
          <button type="button" class="act-btn act-edit" onclick="openEdit(<?= htmlspecialchars(json_encode($r), ENT_QUOTES) ?>)" title="Modifier">✏</button>
          <?php if ($waHref): ?>
            <a class="act-btn act-wa" href="<?= e($waHref) ?>" target="_blank" rel="noopener" title="WhatsApp">💬</a>
          <?php endif; ?>
          <form method="POST" style="display:inline" onsubmit="return confirm('Supprimer cette entrée ?')">
            <?= csrf_field() ?>
            <input type="hidden" name="_action" value="delete">
            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
            <button type="submit" class="act-btn act-del" title="Supprimer">🗑</button>
          </form>
        </div>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</div>

<!-- PAGINATION -->
<?php if ($totalPages > 1):
  $qargs = array_filter(['q'=>$fQ,'statut'=>$fStatut,'mode'=>$fMode]);
  $qs = $qargs ? '&' . http_build_query($qargs) : '';
?>
<div class="pl-pag">
  <?php if ($page > 1): ?>
    <a href="?page=1<?= $qs ?>">«</a>
    <a href="?page=<?= $page-1 ?><?= $qs ?>">‹</a>
  <?php endif; ?>
  <?php foreach (range(max(1,$page-3), min($totalPages,$page+3)) as $p): ?>
    <a href="?page=<?= $p ?><?= $qs ?>" class="<?= $p===$page?'cur':'' ?>"><?= $p ?></a>
  <?php endforeach; ?>
  <?php if ($page < $totalPages): ?>
    <a href="?page=<?= $page+1 ?><?= $qs ?>">›</a>
    <a href="?page=<?= $totalPages ?><?= $qs ?>">»</a>
  <?php endif; ?>
</div>
<?php endif; ?>
<?php endif; ?>


<!-- ============================
     MODAL ADD / EDIT
============================= -->
<div class="pl-modal-bg" id="plModal">
  <div class="pl-modal">
    <div class="pl-modal-hd">
      <div>
        <h3 id="plModalTitle">Ajouter une entrée</h3>
        <p class="sub">Renseignez les informations du participant et de la formation.</p>
      </div>
      <button type="button" class="modal-x" onclick="closeModal('plModal')">✕</button>
    </div>
    <form method="POST">
      <?= csrf_field() ?>
      <input type="hidden" name="_action" value="save">
      <input type="hidden" name="id" id="plId" value="">
      <div class="pl-modal-body">

        <div class="full">
          <label>Participant *</label>
          <input type="text" name="nom_participant" id="plNom" required placeholder="Nom complet">
        </div>
        <div>
          <label>Email</label>
          <input type="email" name="email_participant" id="plEmail" placeholder="email@exemple.com">
        </div>
        <div>
          <label>Téléphone</label>
          <input type="text" name="telephone_participant" id="plTel" placeholder="+225 07 XX XX XX XX">
        </div>

        <div class="full">
          <label>Formation *</label>
          <select name="formation_id" id="plFormId" onchange="syncFormTitre(this)">
            <option value="">— Sélectionner ou saisir ci-dessous —</option>
            <?php foreach ($formations as $f): ?>
              <option value="<?= $f['id'] ?>"><?= e($f['titre']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="full">
          <label>Titre formation (libre)</label>
          <input type="text" name="titre_formation" id="plTitre" placeholder="Ou saisir un titre libre">
        </div>

        <div>
          <label>Date préinscription</label>
          <input type="date" name="date_preinscription" id="plDatePre">
        </div>
        <div>
          <label>Début envisagé</label>
          <input type="date" name="date_debut_envisagee" id="plDateEnv">
        </div>
        <div>
          <label>Début réel</label>
          <input type="date" name="date_debut_reelle" id="plDateReel">
        </div>
        <div>
          <label>Statut</label>
          <select name="statut" id="plStatut">
            <?php foreach ($statutLabels as $sv => $sl): ?>
              <option value="<?= $sv ?>"><?= $sl ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label>Mode de formation</label>
          <select name="mode_formation" id="plMode">
            <option value="en_ligne">En ligne</option>
            <option value="presentiel">Présentiel</option>
            <option value="hybride">Hybride</option>
          </select>
        </div>
        <div>
          <label>Format</label>
          <select name="format_formation" id="plFormat">
            <option value="individuel">Individuel</option>
            <option value="groupe_3_5">Groupe 3–5 pers.</option>
            <option value="groupe_6_10">Groupe 6–10 pers.</option>
            <option value="groupe_10p">Groupe 10+ pers.</option>
            <option value="groupe_devis">Groupe sur devis</option>
          </select>
        </div>

        <div>
          <label>Montant total (F CFA)</label>
          <input type="number" name="montant_total" id="plMontant" placeholder="Ex : 250000" min="0" step="1000">
        </div>
        <div>
          <label>Montant versé (F CFA)</label>
          <input type="number" name="montant_verse" id="plVerse" placeholder="Ex : 125000" min="0" step="1000">
        </div>

        <div class="full">
          <label>Notes internes</label>
          <textarea name="notes" id="plNotes" placeholder="Remarques, historique, conditions particulières…"></textarea>
        </div>
        <input type="hidden" name="preinscription_id" id="plPreId">
      </div>
      <div class="pl-modal-footer">
        <button type="button" class="btn-cancel" onclick="closeModal('plModal')">Annuler</button>
        <button type="submit" class="btn-submit">💾 Enregistrer</button>
      </div>
    </form>
  </div>
</div>


<!-- ============================
     MODAL IMPORT
============================= -->
<div class="pl-modal-bg" id="impModal">
  <div class="pl-modal" style="max-width:580px">
    <div class="pl-modal-hd">
      <div>
        <h3>⬇ Importer des préinscriptions</h3>
        <p class="sub"><?= count($preNonImportees) ?> préinscription(s) non encore importée(s). Cochez celles à ajouter au planning.</p>
      </div>
      <button type="button" class="modal-x" onclick="closeModal('impModal')">✕</button>
    </div>
    <form method="POST">
      <?= csrf_field() ?>
      <input type="hidden" name="_action" value="import">
      <div style="padding:0 22px">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px">
          <label style="font-size:12px;font-weight:700;color:#374151">Préinscriptions disponibles</label>
          <button type="button" onclick="toggleAllImp()" style="background:none;border:0;color:#1f3fe0;font-size:12px;font-weight:700;cursor:pointer">Tout sélectionner / désélectionner</button>
        </div>
        <div class="imp-list">
          <?php if (empty($preNonImportees)): ?>
            <div style="padding:24px;text-align:center;color:#9ca3af">Toutes les préinscriptions ont déjà été importées.</div>
          <?php endif; ?>
          <?php foreach ($preNonImportees as $p): ?>
            <label class="imp-row" for="imp_<?= $p['id'] ?>">
              <input type="checkbox" name="pre_ids[]" value="<?= $p['id'] ?>" id="imp_<?= $p['id'] ?>">
              <div class="imp-row-info">
                <strong><?= e(trim($p['nom'] . ' ' . $p['prenoms'])) ?></strong>
                <small>
                  <?= e($p['ftit'] ?? 'Formation non précisée') ?>
                  <?= !empty($p['email']) ? ' · ' . e($p['email']) : '' ?>
                  · <?= !empty($p['created_at']) ? date('d/m/Y', strtotime($p['created_at'])) : '—' ?>
                </small>
              </div>
            </label>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="pl-modal-footer">
        <button type="button" class="btn-cancel" onclick="closeModal('impModal')">Annuler</button>
        <button type="submit" class="btn-submit" style="background:linear-gradient(135deg,#6d28d9,#7c3aed)">⬇ Importer la sélection</button>
      </div>
    </form>
  </div>
</div>


<script>
/* ── Formations map ── */
var formationsMap = {};
<?php foreach ($formations as $f): ?>
formationsMap[<?= $f['id'] ?>] = <?= json_encode($f['titre']) ?>;
<?php endforeach; ?>

function syncFormTitre(sel) {
  var titre = formationsMap[sel.value] || '';
  if (titre) document.getElementById('plTitre').value = titre;
}

/* ── Modal add ── */
function openAdd() {
  document.getElementById('plModalTitle').textContent = 'Ajouter une entrée';
  document.getElementById('plId').value = '';
  document.getElementById('plPreId').value = '';
  ['plNom','plEmail','plTel','plTitre','plNotes'].forEach(function(id){ document.getElementById(id).value=''; });
  ['plDatePre','plDateEnv','plDateReel','plMontant','plVerse'].forEach(function(id){ document.getElementById(id).value=''; });
  document.getElementById('plFormId').value = '';
  document.getElementById('plStatut').value = 'attente';
  document.getElementById('plMode').value = 'en_ligne';
  document.getElementById('plFormat').value = 'individuel';
  openModal('plModal');
}

/* ── Modal edit ── */
function openEdit(r) {
  document.getElementById('plModalTitle').textContent = 'Modifier l\'entrée #' + r.id;
  document.getElementById('plId').value          = r.id || '';
  document.getElementById('plPreId').value       = r.preinscription_id || '';
  document.getElementById('plNom').value         = r.nom_participant || '';
  document.getElementById('plEmail').value       = r.email_participant || '';
  document.getElementById('plTel').value         = r.telephone_participant || '';
  document.getElementById('plTitre').value       = r.titre_formation || '';
  document.getElementById('plFormId').value      = r.formation_id || '';
  document.getElementById('plDatePre').value     = r.date_preinscription || '';
  document.getElementById('plDateEnv').value     = r.date_debut_envisagee || '';
  document.getElementById('plDateReel').value    = r.date_debut_reelle || '';
  document.getElementById('plMontant').value     = r.montant_total || '';
  document.getElementById('plVerse').value       = r.montant_verse || '';
  document.getElementById('plStatut').value      = r.statut || 'attente';
  document.getElementById('plMode').value        = r.mode_formation || 'en_ligne';
  document.getElementById('plFormat').value      = r.format_formation || 'individuel';
  document.getElementById('plNotes').value       = r.notes || '';
  openModal('plModal');
}

/* ── Import ── */
function openImport() { openModal('impModal'); }
function toggleAllImp() {
  var boxes = document.querySelectorAll('#impModal input[type=checkbox]');
  var allChecked = Array.prototype.every.call(boxes, function(b){ return b.checked; });
  boxes.forEach(function(b){ b.checked = !allChecked; });
}

/* ── Statut rapide ── */
var _csrf = <?= json_encode(csrf_token()) ?>;
function updateStatut(sel) {
  var id = sel.dataset.id;
  var st = sel.value;
  var colors = <?= json_encode($statutColors) ?>;
  sel.style.color = colors[st] || '#374151';
  sel.style.borderColor = colors[st] || '#e5e7eb';
  fetch('', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: new URLSearchParams({ _action: 'status', id: id, statut: st, csrf: _csrf })
  });
}

/* ── Helpers modal ── */
function openModal(id){ document.getElementById(id).classList.add('open'); }
function closeModal(id){ document.getElementById(id).classList.remove('open'); }
document.querySelectorAll('.pl-modal-bg').forEach(function(bg){
  bg.addEventListener('click', function(e){ if(e.target===this) closeModal(this.id); });
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout/layout.php';
