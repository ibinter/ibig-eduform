<?php
declare(strict_types=1);

/* ============================================================
   BOOTSTRAP
============================================================ */
require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../core/csrf.php';
require_once __DIR__ . '/../auth/middleware.php';
require_once __DIR__ . '/../../core/auth.php';

Middleware::requireAuth();
$u = auth_user();

$pageTitle  = "Formations";
$activeMenu = "formations";

$pdo = Database::connect();

/* ============================================================
   DROITS
============================================================ */
$canEdit   = in_array($u['role'], ['admin','super_admin'], true);
$canDelete = (($u['role'] ?? '') === 'super_admin');

/* ============================================================
   FILTRES MOIS / ANNÉE
============================================================ */
$mois   = (int)($_GET['mois'] ?? 0);
$annee  = (int)($_GET['annee'] ?? 0);
$statut = trim((string)($_GET['statut'] ?? ''));
$mode   = trim((string)($_GET['mode'] ?? ''));
$q      = trim((string)($_GET['q'] ?? ''));

/* ============================================================
   MOIS (FR) — REMPLACEMENT DE strftime()
============================================================ */
$moisNoms = [
  1  => 'Janvier',
  2  => 'Février',
  3  => 'Mars',
  4  => 'Avril',
  5  => 'Mai',
  6  => 'Juin',
  7  => 'Juillet',
  8  => 'Août',
  9  => 'Septembre',
  10 => 'Octobre',
  11 => 'Novembre',
  12 => 'Décembre',
];

$where  = [];
$params = [];

if ($mois >= 1 && $mois <= 12) {
  $where[] = 'MONTH(date_debut) = :mois';
  $params[':mois'] = $mois;
}
if ($annee >= 2020) {
  $where[] = 'YEAR(date_debut) = :annee';
  $params[':annee'] = $annee;
}
if (in_array($statut, ['active','inactive'], true)) {
  $where[] = 'statut = :statut';
  $params[':statut'] = $statut;
}
if (in_array($mode, ['presentiel','en_ligne','hybride'], true)) {
  $where[] = 'mode = :mode';
  $params[':mode'] = $mode;
}
if ($q !== '') {
  $where[] = 'titre LIKE :q';
  $params[':q'] = '%' . $q . '%';
}

$whereSql = $where ? 'WHERE '.implode(' AND ', $where) : '';

/* ============================================================
   PAGINATION
============================================================ */
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 40;
$offset  = ($page - 1) * $perPage;

/* ============================================================
   TOTAL
============================================================ */
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM formations $whereSql");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));

/* ============================================================
   DONNÉES — ORDRE CHRONOLOGIQUE DÉFINITIF
============================================================ */
$sql = "
  SELECT *
  FROM formations
  $whereSql
  ORDER BY 
    (date_debut IS NULL) ASC,
    date_debut ASC,
    id ASC
  LIMIT :limit OFFSET :offset
";
$stmt = $pdo->prepare($sql);

foreach ($params as $k => $v) {
  $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();
$formations = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ============================================================
   STATUT
============================================================ */
$today = date('Y-m-d');
function statutFormation(array $f, string $today): string {
  if (!empty($f['statut'])) return $f['statut'];
  if (!empty($f['date_debut']) && $f['date_debut'] > $today) return 'a_venir';
  if (!empty($f['date_fin']) && $f['date_fin'] < $today) return 'termine';
  return 'en_cours';
}

/* Aperçu : formations actives TERMINÉES (date de fin passée) */
$pastCount = (int)$pdo->query("
  SELECT COUNT(*) FROM formations
  WHERE statut = 'active'
    AND COALESCE(date_fin, date_debut) IS NOT NULL
    AND COALESCE(date_fin, date_debut) < CURDATE()
")->fetchColumn();

/* Aperçu : formations actives dont la date de DÉBUT est passée */
$startedCount = (int)$pdo->query("
  SELECT COUNT(*) FROM formations
  WHERE statut = 'active'
    AND date_debut IS NOT NULL
    AND date_debut < CURDATE()
")->fetchColumn();

/* Messages flash */
$flashDeactivated = isset($_GET['deactivated']) ? (int)$_GET['deactivated'] : null;
$flashDeleted     = isset($_GET['deleted'])     ? (int)$_GET['deleted']     : null;
$flashDelError    = isset($_GET['delerror']);

ob_start();
?>

<style>
/* ============================================================
   ADMIN FORMATIONS — ERP COMPACT / PRO / STABLE
   âÂÂÂÂÂÂÂÂ dense
   âÂÂÂÂÂÂÂÂ aligné
   âÂÂÂÂÂÂÂÂ sans débordement
============================================================ */

/* ===== GLOBAL ===== */
.admin-wrap {
  width:100%;
  max-width:100%;
}

/* ===== TOOLBAR ===== */
.toolbar {
  display:flex;
  justify-content:space-between;
  align-items:center;
  gap:12px;
  margin-bottom:12px;
}
.toolbar h2 {
  margin:0;
  font-size:18px;
  font-weight:600;
}

/* ===== FILTRES ===== */
.filters {
  display:flex;
  align-items:center;
  gap:6px;
  flex-wrap:nowrap;
}

.filters select {
  height:30px;
  padding:4px 8px;
  font-size:13px;
  min-width:110px;
}

.filters .btn,
.filters a.btn {
  height:30px;
  padding:0 10px;
  font-size:13px;
  line-height:28px;
}

.filters .btn-primary {
  width:32px;
  height:32px;
  padding:0;
  display:flex;
  align-items:center;
  justify-content:center;
}

/* ===== TABLE CONTAINER ===== */
.table-wrap {
  overflow-x:auto;
}

.table-card {
  background:#FFFFFF;
  border-radius:12px;
  box-shadow:0 1px 3px rgba(0,0,0,0.05);
  overflow:hidden;
}

/* ===== TABLE ===== */
.admin-table {
  width:100%;
  border-collapse:collapse;
  table-layout:fixed;
}

.admin-table th {
  padding:8px;
  font-size:12px;
  font-weight:600;
  text-transform:uppercase;
  color:#475569;
  border-bottom:2px solid #E5E7EB;
}

.admin-table td {
  padding:6px 8px;
  font-size:13px;
  border-bottom:1px solid #F1F5F9;
  white-space:nowrap;
  overflow:hidden;
  text-overflow:ellipsis;
  vertical-align:middle;
}

.admin-table tr:hover {
  background:#F8FAFC;
}

/* ===== COLONNES ===== */
.col-title   { width:32%; }
.col-date    { width:10%; }
.col-small   { width:9%; }
.col-actions { width:16%; }

/* ===== STATUT ===== */
.badge {
  display:inline-block;
  padding:2px 8px;
  font-size:11px;
  font-weight:600;
  border-radius:999px;
}

.badge.wait {
  background:#FEF3C7;
  color:#92400E;
}
.badge.ok {
  background:#DCFCE7;
  color:#166534;
}
.badge.done {
  background:#E5E7EB;
  color:#374151;
}

/* ===== ACTIONS ===== */
.actions {
  display:flex;
  align-items:center;
  gap:4px;
  justify-content:flex-start;
}

.icon-btn {
  width:26px;
  height:26px;
  padding:0;
  display:flex;
  align-items:center;
  justify-content:center;
  border:1px solid #CBD5E1;
  background:#FFFFFF;
  border-radius:6px;
  font-size:13px;
  cursor:pointer;
}

.icon-btn:hover {
  background:#F1F5F9;
}

.icon-btn.danger {
  border-color:#FCA5A5;
  color:#B91C1C;
}

.icon-btn.danger:hover {
  background:#FEE2E2;
}

/* ===== PAGINATION ===== */
.pagination {
  margin-top:12px;
  display:flex;
  gap:6px;
  flex-wrap:wrap;
}
/* ===== FIX VISIBILITÉ BOUTONS ===== */
.filters .btn,
.filters a.btn,
.icon-btn,
.icon-btn.danger {
  color:#0F172A !important;
}

.filters .btn-primary {
  color:#FFFFFF !important;
}

.icon-btn.danger {
  color:#B91C1C !important;
}

button {
  background:#FFFFFF;
}

/* ===== FIX PAGINATION (CHIFFRES INVISIBLES) ===== */
.pagination .btn {
  background:#FFFFFF;
  color:#0F172A;          /* texte visible */
  border:1px solid #CBD5E1;
}

.pagination .btn:hover {
  background:#F1F5F9;
}

.pagination .btn-primary {
  background:#2563EB;     /* bleu actif */
  color:#FFFFFF;
  border-color:#2563EB;
}

/* ============================================================
   FIX PAGINATION — FORÇAGE TOTAL (THÈME ADMIN)
============================================================ */

/* Boutons pagination INACTIFS */
.pagination a.btn,
.pagination a.btn:not(.btn-primary),
.pagination a.btn:visited {
  background-color:#FFFFFF !important;
  color:#0F172A !important;
  border:1px solid #CBD5E1 !important;
  box-shadow:none !important;
  filter:none !important;
}

/* Hover pagination inactive */
.pagination a.btn:hover {
  background-color:#F1F5F9 !important;
  color:#0F172A !important;
}

/* Bouton pagination ACTIVE */
.pagination a.btn-primary,
.pagination a.btn-primary:hover,
.pagination a.btn-primary:focus {
  background-color:#2563EB !important;
  color:#FFFFFF !important;
  border-color:#2563EB !important;
  box-shadow:none !important;
}

/* Focus / active forcés */
.pagination a.btn:focus,
.pagination a.btn:active {
  outline:none !important;
  box-shadow:none !important;
}

</style>

<div class="admin-wrap">

  <?php if ($flashDeactivated !== null): ?>
    <div style="margin-bottom:12px;padding:12px 14px;border-radius:10px;background:#DCFCE7;border:1px solid #16a34a;color:#166534;font-size:14px">
      ✅ <?= (int)$flashDeactivated; ?> formation(s) terminée(s) désactivée(s).
      Elles n’apparaissent plus sur le site (réactivables via « Éditer »).
    </div>
  <?php endif; ?>

  <?php if ($flashDeleted !== null): ?>
    <div style="margin-bottom:12px;padding:12px 14px;border-radius:10px;background:#DCFCE7;border:1px solid #16a34a;color:#166534;font-size:14px">
      🗑️ <?= (int)$flashDeleted; ?> formation(s) supprimée(s) définitivement (sessions et page d’atterrissage incluses).
      Les préinscriptions liées ont été conservées (dé-liées).
    </div>
  <?php endif; ?>

  <?php if ($flashDelError): ?>
    <div style="margin-bottom:12px;padding:12px 14px;border-radius:10px;background:#FEF2F2;border:1px solid #ef4444;color:#7f1d1d;font-size:14px">
      ⚠️ La suppression a échoué (contrainte de base de données). Aucune donnée n’a été modifiée. Réessayez ou contactez le support.
    </div>
  <?php endif; ?>

  <!-- TOOLBAR -->
  <div class="toolbar">
    <h2>Formations</h2>

    <form method="get" class="filters">
      <select name="mois">
      <option value="">Mois</option>
    
      <?php foreach ($moisNoms as $num => $label): ?>
        <option value="<?= $num; ?>" <?= $mois === $num ? 'selected' : ''; ?>>
          <?= $label; ?>
        </option>
      <?php endforeach; ?>
    </select>

      <select name="annee">
        <option value="">Année</option>
        <?php for ($y=date('Y')-3;$y<=date('Y')+2;$y++): ?>
          <option value="<?= $y; ?>" <?= $annee===$y?'selected':''; ?>><?= $y; ?></option>
        <?php endfor; ?>
      </select>

      <select name="statut">
        <option value="">Statut</option>
        <option value="active"   <?= $statut==='active'?'selected':''; ?>>Actives</option>
        <option value="inactive" <?= $statut==='inactive'?'selected':''; ?>>Inactives</option>
      </select>

      <select name="mode">
        <option value="">Mode</option>
        <option value="presentiel" <?= $mode==='presentiel'?'selected':''; ?>>Présentiel</option>
        <option value="en_ligne"   <?= $mode==='en_ligne'?'selected':''; ?>>En ligne</option>
        <option value="hybride"    <?= $mode==='hybride'?'selected':''; ?>>Hybride</option>
      </select>

      <input type="search" name="q" value="<?= e($q); ?>" placeholder="Rechercher un titre…"
             style="height:30px;padding:4px 10px;font-size:13px;min-width:180px;border:1px solid #cbd5e1;border-radius:8px;color:#0f172a">

      <button class="btn">Filtrer</button>
      <a class="btn" href="index.php">Reset</a>

      <?php if ($canEdit): ?>
        <a class="btn btn-primary" href="create.php">&#x2795;</a>
      <?php endif; ?>
    </form>

    <?php if ($canEdit): ?>
      <?php
        $styBase  = 'background:#ffffff !important;font-weight:700;font-size:13px;padding:0 12px;height:34px;border-radius:8px;white-space:nowrap;';
        $styEnd   = $styBase . 'color:#b45309 !important;border:1px solid #f59e0b !important;' . ($pastCount    === 0 ? 'opacity:.5;cursor:not-allowed;' : 'cursor:pointer;');
        $styStart = $styBase . 'color:#b91c1c !important;border:1px solid #ef4444 !important;' . ($startedCount === 0 ? 'opacity:.5;cursor:not-allowed;' : 'cursor:pointer;');
      ?>
      <form method="post" action="deactivate-past.php" style="margin:0"
            onsubmit="return confirm('Désactiver <?= (int)$pastCount; ?> formation(s) TERMINÉE(S) (date de fin passée) ? Action réversible (statut → inactive).');">
        <?= csrf_field(); ?>
        <input type="hidden" name="mode" value="end">
        <button type="submit" style="<?= $styEnd; ?>"
                title="Désactiver les formations dont la date de fin est passée"
                <?= $pastCount === 0 ? 'disabled' : ''; ?>>
          🗓️ Désactiver les terminées (<?= (int)$pastCount; ?>)
        </button>
      </form>

      <form method="post" action="deactivate-past.php" style="margin:0"
            onsubmit="return confirm('Désactiver <?= (int)$startedCount; ?> formation(s) dont la date de DÉBUT est passée (même si non terminées) ? Action réversible.');">
        <?= csrf_field(); ?>
        <input type="hidden" name="mode" value="start">
        <button type="submit" style="<?= $styStart; ?>"
                title="Désactiver les formations dont la date de début est passée"
                <?= $startedCount === 0 ? 'disabled' : ''; ?>>
          ⏮️ Désactiver les commencées (<?= (int)$startedCount; ?>)
        </button>
      </form>
    <?php endif; ?>
  </div>

  <!-- TABLE -->
  <div class="table-wrap">
    <table class="admin-table">
      <thead>
        <tr>
          <th class="col-title">Formation</th>
          <th class="col-date">Début</th>
          <th class="col-small">Durée</th>
          <th class="col-small">Mode</th>
          <th class="col-small">Statut</th>
          <th class="col-actions">Actions</th>
        </tr>
      </thead>
      <tbody>

      <?php if (empty($formations)): ?>
        <tr><td colspan="6">Aucune formation</td></tr>
      <?php endif; ?>

      <?php foreach ($formations as $f):
        $st = statutFormation($f, $today);
      ?>
        <tr>
          <td class="col-title"><strong><?= e($f['titre']); ?></strong></td>
          <td><?= (!empty($f['date_debut']) && $f['date_debut'] !== '0000-00-00') ? date('d/m/Y', strtotime($f['date_debut'])) : '—'; ?></td>
          <td><?= e($f['duree'] ?? '—'); ?></td>
          <td><?= e($f['mode'] ?? '—'); ?></td>
          <td>
            <span class="badge <?= $st==='a_venir'?'wait':($st==='en_cours'?'ok':'done'); ?>">
              <?= ucfirst(str_replace('_',' ', $st)); ?>
            </span>
          </td>
          <td>
            <div class="actions">

              <?php if ($canEdit): ?>
                <a class="icon-btn" title="Modifier"
                   href="edit.php?id=<?= (int)$f['id']; ?>">&#x270F;</a>
              <?php endif; ?>

              <?php if (!empty($f['slug'])): ?>
                <a class="icon-btn icon-landing"
                   title="Landing (Admin)"
                   aria-label="Landing Admin"
                   href="/admin/formation_landing_edit.php?formation_id=<?= (int)$f['id']; ?>">
                  &#x1F310;
                </a>
              <?php endif; ?>

              <a class="icon-btn icon-tdr"
               title="TDR"
               aria-label="Gestion du TDR"
               href="upload-tdr.php?id=<?= (int)$f['id']; ?>">
              &#x1F4C4;
            </a>

              <a class="icon-btn" title="Préinscriptions"
                 href="../preinscriptions/index.php?formation_id=<?= (int)$f['id']; ?>">&#x1F465;</a>

              <?php if ($canDelete): ?>
                <form method="post" action="delete.php"
                      onsubmit="return confirm('⚠️ SUPPRESSION DÉFINITIVE de « <?= e(addslashes($f['titre'])); ?> » et de ses sessions. Action IRRÉVERSIBLE. Continuer ?');">
                  <?= csrf_field(); ?>
                  <input type="hidden" name="id" value="<?= (int)$f['id']; ?>">
                  <button class="icon-btn danger" title="Supprimer définitivement">&#x2716;</button>
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
  <?php if ($totalPages > 1): ?>
    <div class="pagination">
      <?php for ($i=1;$i<=$totalPages;$i++): ?>
        <a class="btn <?= $i===$page?'btn-primary':''; ?>"
           href="?<?= http_build_query(array_merge($_GET,['page'=>$i])); ?>">
          <?= $i; ?>
        </a>
      <?php endfor; ?>
    </div>
  <?php endif; ?>

</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout/layout.php';
