<?php
declare(strict_types=1);

require_once __DIR__ . '/../auth/guard.php';
require_permission('view_preinscriptions');

require_once __DIR__ . '/../_init.php';

Middleware::requireAuth();

$u = auth_user();

$pageTitle  = "Préinscriptions";
$activeMenu = "preinscriptions";

$pdo = Database::connect();

/* =========================================================
   HELPERS
========================================================= */
if (!function_exists('pre_wa_number')) {
    /* Numéro WhatsApp international (Côte d'Ivoire par défaut : préfixe 225). */
    function pre_wa_number(?string $phone): string {
        $d = preg_replace('/\D+/', '', (string)$phone);
        if ($d === '') return '';
        if (strpos($d, '225') === 0) return $d;
        if (strlen($d) <= 10) return '225' . $d;
        return $d;
    }
}

/* Colonnes « profil » disponibles (tolérant si la migration n'est pas encore passée) */
$PROFIL_COLS = [];
try {
    $cols = $pdo->query("SHOW COLUMNS FROM preinscriptions")->fetchAll(PDO::FETCH_COLUMN);
    foreach (['domaine_activite','niveau_etude','fonction','annees_experience'] as $c) {
        if (in_array($c, $cols, true)) { $PROFIL_COLS[] = $c; }
    }
} catch (Throwable $e) {}

/* =========================================================
   FILTRES (GET)
========================================================= */
$q       = trim((string)($_GET['q'] ?? ''));
$fid     = (isset($_GET['formation']) && $_GET['formation'] !== '') ? (int)$_GET['formation'] : 0;
$statutF = trim((string)($_GET['statut'] ?? ''));
$niveauF = trim((string)($_GET['niveau'] ?? ''));
$villeF  = trim((string)($_GET['ville'] ?? ''));
$dFrom   = trim((string)($_GET['from'] ?? ''));
$dTo     = trim((string)($_GET['to'] ?? ''));

$allowedPer = [50, 100, 500, 1000];
$per  = (int)($_GET['per'] ?? 50);
if (!in_array($per, $allowedPer, true)) { $per = 50; }
$page = max(1, (int)($_GET['page'] ?? 1));

/* WHERE dynamique */
$where  = [];
$params = [];
if ($q !== '') {
    $cols = ['p.nom','p.prenoms','p.email','p.telephone','p.ville'];
    foreach ($PROFIL_COLS as $c) { $cols[] = 'p.'.$c; }   // domaine d'activité, fonction, niveau d'étude, etc.
    $where[] = '(' . implode(' OR ', array_map(fn($c) => $c.' LIKE :q', $cols)) . ')';
    $params[':q'] = '%'.$q.'%';
}
if ($fid > 0)       { $where[] = 'p.formation_id = :fid'; $params[':fid'] = $fid; }
if ($statutF !== ''){ $where[] = 'p.statut = :statut';    $params[':statut'] = $statutF; }
if ($niveauF !== ''){ $where[] = 'p.niveau = :niveau';    $params[':niveau'] = $niveauF; }
if ($villeF !== '') { $where[] = 'p.ville LIKE :ville';   $params[':ville'] = '%'.$villeF.'%'; }
if ($dFrom !== '')  { $where[] = 'DATE(p.created_at) >= :dfrom'; $params[':dfrom'] = $dFrom; }
if ($dTo !== '')    { $where[] = 'DATE(p.created_at) <= :dto';   $params[':dto'] = $dTo; }
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

/* =========================================================
   COMPTEURS (sur le périmètre filtré)
========================================================= */
$total = 0; $nbTraitees = 0; $nbRejetees = 0; $nbToday = 0;
try {
    $agg = $pdo->prepare("
        SELECT
            COUNT(*) AS total,
            SUM(CASE WHEN LOWER(COALESCE(statut,'')) IN ('traitee','traitée','confirme','confirmee','valide') THEN 1 ELSE 0 END) AS traitees,
            SUM(CASE WHEN LOWER(COALESCE(statut,'')) IN ('rejete','rejetee','rejeté','rejetée','refuse','refusee') THEN 1 ELSE 0 END) AS rejetees,
            SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) AS today,
            SUM(CASE WHEN EXISTS(SELECT 1 FROM tdr_copies tc WHERE tc.preinscription_id = p.id) THEN 1 ELSE 0 END) AS avec_tdr
        FROM preinscriptions p
        $whereSql
    ");
    $agg->execute($params);
    $k = $agg->fetch(PDO::FETCH_ASSOC) ?: [];
    $total      = (int)($k['total'] ?? 0);
    $nbTraitees = (int)($k['traitees'] ?? 0);
    $nbRejetees = (int)($k['rejetees'] ?? 0);
    $nbToday    = (int)($k['today'] ?? 0);
    $nbAvecTdr  = (int)($k['avec_tdr'] ?? 0);
} catch (Throwable $e) { /* tolérant */ }
/* « À traiter » = tout ce qui n'est ni traité ni rejeté (nouvelle, vide, null…) */
$nbNouvelles = max(0, $total - $nbTraitees - $nbRejetees);

/* =========================================================
   PAGINATION
========================================================= */
$pages  = max(1, (int)ceil($total / $per));
if ($page > $pages) { $page = $pages; }
$offset = ($page - 1) * $per;

/* =========================================================
   LIGNES (page courante)
========================================================= */
$sql = "
  SELECT p.id, p.nom, p.prenoms, p.email, p.telephone, p.ville, p.niveau, p.statut, p.created_at,
         f.titre AS formation,
         COUNT(tc.id) AS nb_tdr
  FROM preinscriptions p
  LEFT JOIN formations f ON f.id = p.formation_id
  LEFT JOIN tdr_copies tc ON tc.preinscription_id = p.id
  $whereSql
  GROUP BY p.id, p.nom, p.prenoms, p.email, p.telephone, p.ville, p.niveau, p.statut, p.created_at, f.titre
  ORDER BY p.created_at DESC
  LIMIT " . (int)$per . " OFFSET " . (int)$offset . "
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================================================
   LISTES POUR LES FILTRES
========================================================= */
$formationsList = []; $statutsList = []; $niveauxList = [];
try {
    $formationsList = $pdo->query("
        SELECT DISTINCT f.id, f.titre
        FROM preinscriptions p JOIN formations f ON f.id = p.formation_id
        ORDER BY f.titre ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}
try {
    $statutsList = $pdo->query("SELECT DISTINCT statut FROM preinscriptions WHERE statut IS NOT NULL AND statut <> '' ORDER BY statut")->fetchAll(PDO::FETCH_COLUMN);
} catch (Throwable $e) {}
try {
    $niveauxList = $pdo->query("SELECT DISTINCT niveau FROM preinscriptions WHERE niveau IS NOT NULL AND niveau <> '' ORDER BY niveau")->fetchAll(PDO::FETCH_COLUMN);
} catch (Throwable $e) {}

/* Query-string courante (pour pagination & exports) */
$curFilters = array_filter([
    'q' => $q, 'formation' => $fid ?: '', 'statut' => $statutF, 'niveau' => $niveauF,
    'ville' => $villeF, 'from' => $dFrom, 'to' => $dTo, 'per' => $per,
], static fn($v) => $v !== '' && $v !== 0);
$pageUrl = static function (int $p) use ($curFilters): string {
    return '?' . http_build_query(array_merge($curFilters, ['page' => $p]));
};
$exportQs = $curFilters ? ('?' . http_build_query($curFilters)) : '';

$from = $total ? ($offset + 1) : 0;
$to   = min($offset + $per, $total);

ob_start();
?>

<style>
/* ── KPIs ── */
.pre-kpis{display:grid;grid-template-columns:repeat(6,1fr);gap:12px;margin:16px 0}
.pre-kpi{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:16px 18px;box-shadow:0 4px 14px rgba(15,23,42,.06);display:flex;flex-direction:column;gap:4px}
.pre-kpi b{font-size:28px;font-weight:900;line-height:1;color:#0f172a}
.pre-kpi span{font-size:12px;color:#6b7280;font-weight:600;text-transform:uppercase;letter-spacing:.3px}
.pre-kpi.k-total{border-top:3px solid #6366f1}.pre-kpi.k-total b{color:#4f46e5}
.pre-kpi.k-wait {border-top:3px solid #f59e0b}.pre-kpi.k-wait  b{color:#b45309}
.pre-kpi.k-conf {border-top:3px solid #22c55e}.pre-kpi.k-conf  b{color:#16a34a}
.pre-kpi.k-rej  {border-top:3px solid #ef4444}.pre-kpi.k-rej   b{color:#dc2626}
.pre-kpi.k-today{border-top:3px solid #3b82f6}.pre-kpi.k-today b{color:#1d4ed8}
.pre-kpi.k-tdr  {border-top:3px solid #8b5cf6}.pre-kpi.k-tdr   b{color:#7c3aed}

/* ── Exports ── */
.header-actions{display:flex;gap:8px;flex-wrap:wrap}
.btn-export{padding:8px 14px;font-size:12.5px;border-radius:10px;text-decoration:none;font-weight:700;display:inline-flex;align-items:center;gap:5px}
.btn-csv{background:#e0f2fe;color:#075985}.btn-xlsx{background:#dcfce7;color:#166534}
.btn-pdf{background:#fee2e2;color:#991b1b}.btn-print{background:#fef3c7;color:#92400e}

/* ── Filtres ── */
.pre-filters{background:#f8fafc;border:1px solid #e5e7eb;border-radius:14px;padding:16px;margin:0 0 14px}
.pre-filters .grid{display:grid;grid-template-columns:repeat(4,1fr);gap:10px}
.pre-filters .f{display:flex;flex-direction:column;gap:4px}
.pre-filters label{font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.3px}
.pre-filters input,.pre-filters select{padding:9px 11px;border:1px solid #e2e8f0;border-radius:10px;font-size:13.5px;background:#fff;outline:none;width:100%;transition:border-color .15s}
.pre-filters input:focus,.pre-filters select:focus{border-color:#1f3fe0;box-shadow:0 0 0 3px rgba(31,63,224,.1)}
.btn-apply{background:linear-gradient(135deg,#1f3fe0,#3b82f6);color:#fff;border:0;padding:10px 20px;border-radius:10px;font-weight:800;font-size:13.5px;cursor:pointer}
.btn-reset{background:#fff;border:1px solid #e5e7eb;color:#334155;padding:10px 16px;border-radius:10px;font-weight:700;font-size:13.5px;text-decoration:none;display:inline-flex;align-items:center}

/* ── Table ── */
.pre-bar{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin:0 0 10px;font-size:13px;color:#475569}
table.pre-table{width:100%;border-collapse:collapse;font-size:13.5px}
table.pre-table th{font-size:11.5px;text-transform:uppercase;letter-spacing:.4px;color:#6b7280;text-align:left;padding:10px 12px;border-bottom:2px solid #eef2f7;background:#fafbff;white-space:nowrap}
table.pre-table td{padding:11px 12px;border-bottom:1px solid #f1f5f9;vertical-align:middle}
table.pre-table tr.row-today td{background:#fffbeb}
table.pre-table tr:hover td{background:#f0f6ff}
.pre-name strong{font-size:14px;color:#0f172a;display:block}
.pre-name .meta{font-size:11.5px;color:#6b7280;margin-top:2px}
.pre-name .badge-new{display:inline-block;background:#fde68a;color:#92400e;font-size:10px;font-weight:800;border-radius:6px;padding:1px 6px;margin-left:6px;vertical-align:middle}

/* ── Contacts ── */
.contact-lines{font-size:12.5px;line-height:1.8}
.contact-lines a{color:#1f3fe0;text-decoration:none;font-weight:600}
.contact-lines a:hover{text-decoration:underline}
.contact-btns{display:flex;gap:5px;margin-top:7px;flex-wrap:wrap}
.cbtn{display:inline-flex;align-items:center;gap:5px;padding:5px 9px;border-radius:8px;font-size:11.5px;font-weight:700;text-decoration:none;border:1px solid transparent;white-space:nowrap}
.cbtn.wa  {background:#dcfce7;color:#15803d;border-color:#bbf7d0}.cbtn.wa:hover{background:#bbf7d0}
.cbtn.mail{background:#e0ecff;color:#1e40af;border-color:#bfdbfe}.cbtn.mail:hover{background:#bfdbfe}
.cbtn.tel {background:#f3e8ff;color:#7e22ce;border-color:#e9d5ff}.cbtn.tel:hover{background:#e9d5ff}
.cbtn.dis {opacity:.35;pointer-events:none}

/* ── Formation (colonne) ── */
.form-title{font-size:12.5px;font-weight:700;color:#1e293b;max-width:200px;line-height:1.35;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}

/* ── Statut ── */
.pill{padding:4px 11px;border-radius:999px;font-size:11px;font-weight:800;white-space:nowrap;display:inline-flex;align-items:center;gap:4px}
.pill.ok    {background:#dcfce7;color:#166534}
.pill.wait  {background:#fff7ed;color:#9a3412}
.pill.rejete{background:#fee2e2;color:#991b1b}
.pill::before{content:'';width:6px;height:6px;border-radius:50%;background:currentColor;display:inline-block}

/* ── Date ── */
.date-cell{font-size:12px;color:#475569;line-height:1.5}
.date-cell .heure{color:#94a3b8;font-size:11px}

/* ── Actions ── */
.table-actions{display:flex;gap:5px;align-items:center}
.btn-action{width:32px;height:32px;border-radius:9px;display:inline-flex;align-items:center;justify-content:center;font-size:14px;border:1px solid transparent;cursor:pointer;transition:all .15s;text-decoration:none}
.btn-view{background:#e0ecff;color:#1e40af;border-color:#bfdbfe}.btn-view:hover{background:#bfdbfe}
.btn-edit{background:#ecfeff;color:#0f766e;border-color:#99f6e4}.btn-edit:hover{background:#ccfbf1}
.btn-del {background:#fee2e2;color:#991b1b;border-color:#fecaca}.btn-del:hover{background:#fecaca}
.muted{color:#94a3b8}.pre-empty{padding:40px;text-align:center;color:#6b7280;font-size:14px}

/* ── Pagination ── */
.pager{display:flex;gap:6px;justify-content:center;align-items:center;flex-wrap:wrap;margin:20px 0 4px}
.pager a,.pager span{min-width:36px;height:36px;padding:0 10px;border-radius:9px;display:inline-flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;text-decoration:none;border:1px solid #e5e7eb;color:#334155;background:#fff}
.pager a:hover{border-color:#1f3fe0;color:#1f3fe0}
.pager .cur{background:linear-gradient(135deg,#1f3fe0,#3b82f6);color:#fff;border-color:transparent}
.pager .dis{opacity:.35;pointer-events:none}

/* ── TDR badge dans la table ── */
.tdr-badge{display:inline-flex;align-items:center;gap:4px;padding:3px 8px;border-radius:7px;font-size:11px;font-weight:800;background:#ede9fe;color:#6d28d9;border:1px solid #ddd6fe;text-decoration:none;white-space:nowrap}
.tdr-badge:hover{background:#ddd6fe}
.btn-tdr{background:#ede9fe;color:#6d28d9;border-color:#ddd6fe}.btn-tdr:hover{background:#ddd6fe}

@media(max-width:1100px){.pre-kpis{grid-template-columns:repeat(3,1fr)}}
@media(max-width:700px){.pre-kpis{grid-template-columns:1fr 1fr}.pre-filters .grid{grid-template-columns:1fr 1fr}}
</style>

<div class="card">

  <!-- HEADER -->
  <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:4px">
    <h2 style="margin:0">&#128221; Préinscriptions reçues</h2>
    <div class="header-actions">
      <a class="btn-export btn-csv"   href="export.php<?= e($exportQs); ?>">&#128190; CSV</a>
      <a class="btn-export btn-xlsx"  href="export_preinscriptions.xlsx.php<?= e($exportQs); ?>">&#128196; XLSX</a>
      <a class="btn-export btn-pdf"   href="export_preinscriptions.pdf.php<?= e($exportQs); ?>">&#128196; PDF</a>
      <a class="btn-export btn-print" href="print.php<?= e($exportQs); ?>" target="_blank">&#128438; Imprimer</a>
    </div>
  </div>

  <!-- KPIs -->
  <div class="pre-kpis">
    <div class="pre-kpi k-total"><b><?= (int)$total; ?></b><span>Total (filtré)</span></div>
    <div class="pre-kpi k-wait" ><b><?= (int)$nbNouvelles; ?></b><span>&#9888;&#65039; À traiter</span></div>
    <div class="pre-kpi k-conf" ><b><?= (int)$nbTraitees; ?></b><span>&#9989; Traitées</span></div>
    <div class="pre-kpi k-rej"  ><b><?= (int)$nbRejetees; ?></b><span>&#10060; Rejetées</span></div>
    <div class="pre-kpi k-today"><b><?= (int)$nbToday; ?></b><span>&#128197; Aujourd&rsquo;hui</span></div>
    <div class="pre-kpi k-tdr"  ><b><?= (int)$nbAvecTdr; ?></b><span>&#128196; TDR téléchargés</span></div>
  </div>

  <!-- FILTRES -->
  <form method="get" class="pre-filters">
    <div class="grid">
      <div class="f">
        <label>&#128269; Recherche</label>
        <input type="text" name="q" value="<?= e($q); ?>" placeholder="Nom, email, téléphone…" autofocus>
      </div>
      <div class="f">
        <label>Formation</label>
        <select name="formation">
          <option value="">Toutes les formations</option>
          <?php foreach ($formationsList as $fo): ?>
            <option value="<?= (int)$fo['id']; ?>" <?= $fid === (int)$fo['id'] ? 'selected' : ''; ?>><?= e($fo['titre']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="f">
        <label>Statut</label>
        <select name="statut">
          <option value="">Tous les statuts</option>
          <option value="nouvelle"  <?= $statutF === 'nouvelle'  ? 'selected' : ''; ?>>&#128228; Nouvelle</option>
          <option value="traitee"   <?= $statutF === 'traitee'   ? 'selected' : ''; ?>>&#9989; Traitée</option>
          <option value="confirme"  <?= $statutF === 'confirme'  ? 'selected' : ''; ?>>&#9989; Confirmée</option>
          <option value="rejete"    <?= $statutF === 'rejete'    ? 'selected' : ''; ?>>&#10060; Rejetée</option>
          <?php foreach ($statutsList as $s): if (in_array($s, ['nouvelle','traitee','confirme','rejete'], true)) continue; ?>
            <option value="<?= e($s); ?>" <?= $statutF === (string)$s ? 'selected' : ''; ?>><?= e($s); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="f">
        <label>Niveau</label>
        <select name="niveau">
          <option value="">Tous les niveaux</option>
          <option value="debutant"      <?= $niveauF === 'debutant'      ? 'selected' : ''; ?>>🟢 Débutant</option>
          <option value="intermediaire" <?= $niveauF === 'intermediaire' ? 'selected' : ''; ?>>🔵 Intermédiaire</option>
          <option value="expert"        <?= $niveauF === 'expert'        ? 'selected' : ''; ?>>🔴 Expert</option>
        </select>
      </div>
      <div class="f">
        <label>Ville</label>
        <input type="text" name="ville" value="<?= e($villeF); ?>" placeholder="Ex : Abidjan">
      </div>
      <div class="f">
        <label>Du</label>
        <input type="date" name="from" value="<?= e($dFrom); ?>">
      </div>
      <div class="f">
        <label>Au</label>
        <input type="date" name="to" value="<?= e($dTo); ?>">
      </div>
      <div class="f">
        <label>Par page</label>
        <select name="per" onchange="this.form.submit()">
          <?php foreach ($allowedPer as $pp): ?>
            <option value="<?= $pp; ?>" <?= $per === $pp ? 'selected' : ''; ?>><?= $pp; ?> / page</option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div style="display:flex;gap:8px;margin-top:12px;align-items:center">
      <button type="submit" class="btn-apply">&#128269; Appliquer</button>
      <a class="btn-reset" href="index.php">&#8635; Réinitialiser</a>
      <?php if ($q || $fid || $statutF || $niveauF || $villeF || $dFrom || $dTo): ?>
        <span style="font-size:12px;color:#f59e0b;font-weight:700">&#9888;&#65039; Filtres actifs</span>
      <?php endif; ?>
    </div>
  </form>

  <!-- BARRE INFO -->
  <div class="pre-bar">
    <div>
      Affichage <b><?= (int)$from; ?></b>–<b><?= (int)$to; ?></b> sur <b><?= (int)$total; ?></b> préinscription(s)
      &nbsp;·&nbsp; Page <?= (int)$page; ?> / <?= (int)$pages; ?>
    </div>
    <?php if ($nbNouvelles > 0): ?>
      <div style="background:#fff7ed;border:1px solid #fed7aa;color:#9a3412;border-radius:8px;padding:5px 12px;font-size:12px;font-weight:700">
        &#9888;&#65039; <?= (int)$nbNouvelles; ?> préinscription(s) en attente de traitement
      </div>
    <?php endif; ?>
  </div>

  <!-- TABLE -->
  <div style="overflow-x:auto">
  <table class="pre-table">
    <thead>
      <tr>
        <th>#</th>
        <th>Candidat</th>
        <th>Contact &amp; actions</th>
        <th>Formation</th>
        <th>Statut</th>
        <th>Re&ccedil;u le</th>
        <th>TDR</th>
        <th>Gestion</th>
      </tr>
    </thead>
    <tbody>

    <?php if (!$rows): ?>
      <tr><td colspan="8" class="pre-empty">&#128269; Aucune préinscription ne correspond à ces filtres.</td></tr>
    <?php endif; ?>

    <?php foreach ($rows as $idx => $r):
        $nomComplet = trim((string)$r['nom'] . ' ' . (string)$r['prenoms']);
        $prenom     = trim((string)($r['prenoms'] ?? '')) ?: trim((string)($r['nom'] ?? '')) ?: 'cher candidat';
        $form       = trim((string)($r['formation'] ?? ''));
        $email      = trim((string)($r['email'] ?? ''));
        $tel        = trim((string)($r['telephone'] ?? ''));
        $waNum      = pre_wa_number($tel);
        $stl        = strtolower(trim((string)($r['statut'] ?? '')));
        $isOk       = in_array($stl, ['traitee','traitée','confirme','confirmee','valide'], true);
        $isRej      = in_array($stl, ['rejete','rejetee','rejeté','rejetée','refuse','refusee'], true);
        $pillClass  = $isOk ? 'ok' : ($isRej ? 'rejete' : 'wait');
        $isToday    = !empty($r['created_at']) && date('Y-m-d', strtotime((string)$r['created_at'])) === date('Y-m-d');
        $isNew      = !$isOk && !$isRej;

        /* Labels statut lisibles */
        $statutLabel = match(true) {
            in_array($stl, ['traitee','traitée'])          => 'Traitée',
            in_array($stl, ['confirme','confirmee','valide']) => 'Confirmée',
            in_array($stl, ['rejete','rejetee','rejeté','rejetée','refuse','refusee']) => 'Rejetée',
            $stl === 'nouvelle' || $stl === ''              => 'Nouvelle',
            default                                          => ucfirst($stl),
        };

        $waMsg  = "Bonjour " . $prenom . ", ici l'équipe IBIG EDUFORM. "
                . ($form !== '' ? "Nous revenons vers vous au sujet de votre préinscription à la formation « " . $form . " ». " : "Nous revenons vers vous au sujet de votre préinscription. ")
                . "Comment pouvons-nous vous accompagner ?";
        $waHref   = $waNum !== '' ? 'https://wa.me/' . $waNum . '?text=' . rawurlencode($waMsg) : '';
        $mailSubj = "IBIG EDUFORM — Votre préinscription" . ($form !== '' ? " : " . $form : "");
        $mailBody = "Bonjour " . $prenom . ",\n\nNous vous remercions pour votre préinscription" . ($form !== '' ? " à la formation « " . $form . " »" : "") . " auprès d'IBIG EDUFORM.\n\nNous revenons vers vous pour finaliser votre inscription.\n\nBien cordialement,\nL'équipe IBIG EDUFORM";
        $mailHref = $email !== '' ? 'mailto:' . $email . '?subject=' . rawurlencode($mailSubj) . '&body=' . rawurlencode($mailBody) : '';
        $telHref  = $tel  !== '' ? 'tel:' . preg_replace('/[^\d+]/', '', $tel) : '';
    ?>
      <tr class="<?= $isToday ? 'row-today' : ''; ?>">
        <td style="color:#94a3b8;font-size:12px;font-weight:700"><?= $offset + $idx + 1; ?></td>
        <td class="pre-name">
          <strong>
            <?= e($nomComplet ?: '—'); ?>
            <?php if ($isToday && $isNew): ?><span class="badge-new">AUJOURD'HUI</span><?php endif; ?>
          </strong>
          <div class="meta">
            <?php if (!empty($r['ville'])): ?><?= e($r['ville']); ?><?php endif; ?>
            <?php if (!empty($r['niveau'])): ?>
              <?php
                $nLab = ['debutant'=>'Débutant','intermediaire'=>'Intermédiaire','expert'=>'Expert'];
                $nCls = ['debutant'=>'background:#dcfce7;color:#166534','intermediaire'=>'background:#dbeafe;color:#1e40af','expert'=>'background:#fce7f3;color:#9d174d'];
                $nv   = (string)$r['niveau'];
              ?>
              &middot; <span style="font-size:10.5px;font-weight:800;padding:1px 7px;border-radius:999px;<?= $nCls[$nv] ?? 'background:#f1f5f9;color:#475569'; ?>"><?= e($nLab[$nv] ?? ucfirst($nv)); ?></span>
            <?php endif; ?>
          </div>
        </td>
        <td>
          <div class="contact-lines">
            <?php if ($tel !== ''): ?><a href="<?= e($telHref); ?>">&#128222; <?= e($tel); ?></a><br><?php endif; ?>
            <?php if ($email !== ''): ?><a href="mailto:<?= e($email); ?>">&#9993; <?= e($email); ?></a><?php else: ?><span class="muted">Email non fourni</span><?php endif; ?>
          </div>
          <div class="contact-btns">
            <a class="cbtn wa   <?= $waHref   === '' ? 'dis' : ''; ?>" <?= $waHref   !== '' ? 'href="'.e($waHref).'" target="_blank" rel="noopener"' : ''; ?>>&#128172; WhatsApp</a>
            <a class="cbtn mail <?= $mailHref === '' ? 'dis' : ''; ?>" <?= $mailHref !== '' ? 'href="'.e($mailHref).'"' : ''; ?>>&#9993; E-mail</a>
            <?php if ($telHref !== ''): ?><a class="cbtn tel" href="<?= e($telHref); ?>">&#128222; Appeler</a><?php endif; ?>
          </div>
        </td>
        <td>
          <div class="form-title" title="<?= e($form); ?>"><?= e($form !== '' ? $form : '—'); ?></div>
        </td>
        <td><span class="pill <?= $pillClass; ?>"><?= e($statutLabel); ?></span></td>
        <td class="date-cell">
          <?php if (!empty($r['created_at'])): ?>
            <?= date('d/m/Y', strtotime((string)$r['created_at'])); ?><br>
            <span class="heure"><?= date('H:i', strtotime((string)$r['created_at'])); ?></span>
          <?php else: ?>—<?php endif; ?>
        </td>
        <td>
          <?php $nbTdr = (int)($r['nb_tdr'] ?? 0); ?>
          <?php if ($nbTdr > 0): ?>
            <a class="tdr-badge" href="../tdr-copies/index.php?preinscription_id=<?= (int)$r['id']; ?>" title="<?= $nbTdr; ?> TDR téléchargé(s)">
              &#128196; <?= $nbTdr; ?> TDR
            </a>
          <?php else: ?>
            <span style="color:#d1d5db;font-size:12px">—</span>
          <?php endif; ?>
        </td>
        <td>
          <div class="table-actions">
            <a class="btn-action btn-view" title="Voir le détail" href="view.php?id=<?= (int)$r['id']; ?>">&#128065;</a>
            <?php if (in_array($u['role'], ['admin','super_admin','commercial'], true)): ?>
              <a class="btn-action btn-edit" title="Modifier" href="edit.php?id=<?= (int)$r['id']; ?>">&#9998;</a>
            <?php endif; ?>
            <?php if (($u['role'] ?? '') === 'super_admin'): ?>
              <form method="post" action="delete.php" onsubmit="return confirm('Supprimer cette préinscription ?');" style="display:inline">
                <?= csrf_field(); ?>
                <input type="hidden" name="id" value="<?= (int)$r['id']; ?>">
                <button type="submit" class="btn-action btn-del" title="Supprimer">&#128465;</button>
              </form>
            <?php endif; ?>
          </div>
        </td>
      </tr>
    <?php endforeach; ?>

    </tbody>
  </table>
  </div>

  <!-- PAGINATION -->
  <?php if ($pages > 1): ?>
    <div class="pager">
      <?php if ($page > 1): ?>
        <a href="<?= e($pageUrl(1)); ?>" title="Première">«</a>
        <a href="<?= e($pageUrl($page - 1)); ?>" title="Précédente">‹</a>
      <?php else: ?>
        <span class="dis">«</span><span class="dis">‹</span>
      <?php endif; ?>
      <?php
        $start = max(1, $page - 2); $end = min($pages, $page + 2);
        if ($start > 1) echo '<span class="dis">…</span>';
        for ($i = $start; $i <= $end; $i++):
      ?>
        <?php if ($i === $page): ?><span class="cur"><?= $i; ?></span>
        <?php else: ?><a href="<?= e($pageUrl($i)); ?>"><?= $i; ?></a><?php endif; ?>
      <?php endfor; ?>
      <?php if ($end < $pages) echo '<span class="dis">…</span>'; ?>
      <?php if ($page < $pages): ?>
        <a href="<?= e($pageUrl($page + 1)); ?>" title="Suivante">›</a>
        <a href="<?= e($pageUrl($pages)); ?>" title="Dernière">»</a>
      <?php else: ?>
        <span class="dis">›</span><span class="dis">»</span>
      <?php endif; ?>
    </div>
  <?php endif; ?>

</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout/layout.php';
