<?php
declare(strict_types=1);

require_once __DIR__ . '/../auth/guard.php';
require_permission('manage_formateurs');

require_once __DIR__ . '/../_init.php';

/* ============================
   MIDDLEWARE ADMIN
============================ */
$mw = realpath(__DIR__ . '/../auth/middleware.php');
if (!$mw) { die('Middleware introuvable'); }
require_once $mw;
Middleware::requireAuth();

$pageTitle  = 'Candidatures formateurs';
$activeMenu = 'formateurs';

$pdo = Database::connect();

/* ============================
   DONNÉES
============================ */
$stmt = $pdo->query("
  SELECT
    c.id,
    c.nom,
    c.email,
    c.telephone,
    c.domaine,
    c.statut,
    c.visible,
    c.cv_path,
    c.created_at,
    f.id AS formateur_id
  FROM candidatures_formateurs c
  LEFT JOIN formateurs f ON f.candidature_id = c.id
  ORDER BY c.created_at DESC
");
$candidatures = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ============================
   HELPERS
============================ */
function statut_label(string $s): string {
  return match ($s) {
    'retenue'  => 'Retenue',
    'rejete'   => 'Rejetée',
    'en_cours' => 'En cours',
    default    => 'Nouvelle',
  };
}
function statut_pill(string $s): string {
  return match ($s) {
    'retenue' => 'ok',
    'rejete'  => 'danger',
    default   => 'wait',
  };
}

ob_start();
?>

<style>
/* =====================================================
   TABLE
===================================================== */
.admin-table{
  width:100%;
  border-collapse:collapse;
  font-size:14px;
}
.admin-table th{
  font-size:12px;
  text-transform:uppercase;
  letter-spacing:.04em;
  color:#64748b;
  padding:12px;
  border-bottom:1px solid #e5e7eb;
  text-align:left;
}
.admin-table td{
  padding:12px;
  border-bottom:1px solid #eef2f7;
  vertical-align:middle;
}

/* =====================================================
   PILLS
===================================================== */
.pill{
  display:inline-flex;
  align-items:center;
  padding:0 8px;
  height:22px;
  font-size:11.5px;
  border-radius:999px;
}
.pill.ok{background:#dcfce7;color:#166534}
.pill.danger{background:#fee2e2;color:#991b1b}
.pill.wait{background:#ffedd5;color:#9a3412}

/* =====================================================
   FIX BOUTONS (CRITIQUE)
===================================================== */
.btn{
  display:inline-flex;
  align-items:center;
  justify-content:center;
  height:34px;
  min-width:34px;
  padding:0 10px;
  border-radius:8px;
  font-size:13px;
  border:1px solid #e5e7eb;
  background:#ffffff;
  color:#111827 !important;
  opacity:1 !important;
  cursor:pointer;
  text-decoration:none;
}
.btn:hover{background:#f3f4f6}

.btn-primary{background:#2563eb;color:#fff!important;border-color:#2563eb}
.btn-secondary{background:#f1f5f9}
.btn-success{background:#dcfce7;color:#166534!important}
.btn-danger{background:#fee2e2;color:#991b1b!important}
.btn-warning{background:#ffedd5;color:#9a3412!important}
.btn-info{background:#e0f2fe;color:#0369a1!important}
.btn-dark{background:#1f2937;color:#ffffff!important}

/* =====================================================
   ACTIONS CELL
===================================================== */
.actions-cell{
  display:flex;
  gap:6px;
  align-items:center;
  white-space:nowrap;
}
.actions-cell .btn-text{
  padding:0 12px;
  min-width:auto;
}

/* =====================================================
   FIX FINAL — TEXTE BOUTONS ACTIONS INVISIBLE
   (override CSS global layout)
===================================================== */
.actions-cell .btn,
.actions-cell .btn *,
.actions-cell button,
.actions-cell button * {
  color: #111827 !important;
  opacity: 1 !important;
  visibility: visible !important;
}

/* couleurs spécifiques */
.actions-cell .btn-success,
.actions-cell .btn-success * {
  color: #166534 !important;
}

.actions-cell .btn-danger,
.actions-cell .btn-danger * {
  color: #991b1b !important;
}

.actions-cell .btn-warning,
.actions-cell .btn-warning * {
  color: #9a3412 !important;
}

.actions-cell .btn-dark,
.actions-cell .btn-dark * {
  color: #ffffff !important;
}

/* =====================================================
   FIX ULTIME — BOUTON REJETER INVISIBLE
===================================================== */
.actions-cell .btn-danger,
.actions-cell .btn-danger * {
  color: #991b1b !important;
  opacity: 1 !important;
  visibility: visible !important;
}

/* sécurité supplémentaire */
.actions-cell .btn-danger .btn-icon {
  display: inline-block;
  font-weight: 700;
  line-height: 1;
}

/* =====================================================
   FIX FINAL — BOUTON REJETER INVISIBLE
===================================================== */
.actions-cell .btn,
.actions-cell .btn *,
.actions-cell button,
.actions-cell button * {
  color: #111827 !important;
  opacity: 1 !important;
  visibility: visible !important;
}

/* couleurs spécifiques */
.actions-cell .btn-success,
.actions-cell .btn-success * {
  color: #166534 !important;
}

.actions-cell .btn-danger,
.actions-cell .btn-danger * {
  color: #991b1b !important;
}

.actions-cell .btn-warning,
.actions-cell .btn-warning * {
  color: #9a3412 !important;
}

.actions-cell .btn-dark,
.actions-cell .btn-dark * {
  color: #ffffff !important;
}

/* sécurité icône */
.actions-cell .btn-icon{
  display:inline-block;
  font-weight:700;
  line-height:1;
}

</style>

<div class="card">

  <!-- HEADER -->
  <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">
    <h2 style="margin:0">Candidatures formateurs</h2>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
      <a class="btn btn-outline" href="/devenir-formateur.php" target="_blank">Page publique</a>
      <a class="btn btn-primary" href="/devenir-formateur.php" target="_blank">Nouvelle candidature</a>
      <a class="btn btn-info" href="/admin/formateurs/export.php?type=excel">Export Excel</a>
      <a class="btn btn-secondary" href="/admin/formateurs/export.php?type=pdf" target="_blank">Export PDF</a>
    </div>
  </div>

  <!-- TABLE -->
  <table class="admin-table" style="margin-top:16px">
    <thead>
      <tr>
        <th>Nom</th>
        <th>Domaine</th>
        <th>Contact</th>
        <th>Statut</th>
        <th>Visible</th>
        <th>CV</th>
        <th>Fiche</th>
        <th>Date</th>
        <th style="width:240px">Actions</th>
      </tr>
    </thead>
    <tbody>

<?php foreach ($candidatures as $c):
  $id        = (int)$c['id'];
  $cvPath    = trim((string)$c['cv_path']);
  $isVisible = (int)$c['visible'] === 1;
  $hasFiche  = !empty($c['formateur_id']);
?>
<tr>
  <td><strong><?= e($c['nom']); ?></strong></td>
  <td><?= e($c['domaine']); ?></td>
  <td><?= e($c['email']); ?><br><small><?= e($c['telephone']); ?></small></td>

  <td><span class="pill <?= statut_pill($c['statut']); ?>"><?= statut_label($c['statut']); ?></span></td>
  <td><span class="pill <?= $isVisible?'ok':'wait'; ?>"><?= $isVisible?'Oui':'Non'; ?></span></td>

  <td>
    <?= $cvPath
      ? '<a class="btn btn-info" target="_blank" href="/'.e(ltrim($cvPath,'/')).'">CV</a>'
      : '—'; ?>
  </td>

  <td><span class="pill <?= $hasFiche?'ok':'wait'; ?>"><?= $hasFiche?'Créée':'Non créée'; ?></span></td>
  <td><?= date('d/m/Y', strtotime($c['created_at'])); ?></td>

  <td class="actions-cell">
    <a class="btn btn-text" href="/admin/formateurs/view.php?id=<?= $id; ?>">Voir</a>
    <button class="btn btn-warning" title="Éditer">&#x270E;</button>
    <button class="btn btn-success" title="Valider">&#x2713;</button>
    <button class="btn btn-danger" title="Rejeter">
          <span class="btn-icon">&#x2715;</span>
        </button>
    <button class="btn btn-dark" title="Visibilité"><?= $isVisible ? '&#x2212;' : '&#x2B;'; ?></button>
  </td>
</tr>
<?php endforeach; ?>

    </tbody>
  </table>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout/layout.php';