<?php
declare(strict_types=1);

/* =====================================================
   DASHBOARD — SUPER ADMIN
===================================================== */

$pageTitle  = 'Tableau de bord — Super Administrateur';
$activeMenu = 'dashboard';

$pdo = Database::connect();
$u2  = function_exists('auth_user') ? (auth_user() ?? []) : [];
$prenomAdmin = trim((string)($u2['first_name'] ?? '')) ?: 'Admin';

/* =====================================================
   KPIs
===================================================== */
$nbUsers      = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$nbFormations = (int)$pdo->query("SELECT COUNT(*) FROM formations WHERE statut='active'")->fetchColumn();
$nbFormationsTotal = (int)$pdo->query("SELECT COUNT(*) FROM formations")->fetchColumn();

$nbPreinsc      = 0; $nbPreinscToday = 0; $nbPreinscNouvelles = 0;
try {
  $k = $pdo->query("
    SELECT COUNT(*) AS total,
           SUM(CASE WHEN DATE(created_at)=CURDATE() THEN 1 ELSE 0 END) AS today,
           SUM(CASE WHEN LOWER(COALESCE(statut,'')) NOT IN ('traitee','traitée','confirme','confirmee','valide','rejete','rejetee','rejeté','rejetée','refuse','refusee') THEN 1 ELSE 0 END) AS nouvelles
    FROM preinscriptions
  ")->fetch(PDO::FETCH_ASSOC) ?: [];
  $nbPreinsc          = (int)($k['total'] ?? 0);
  $nbPreinscToday     = (int)($k['today'] ?? 0);
  $nbPreinscNouvelles = (int)($k['nouvelles'] ?? 0);
} catch (Throwable $e) {}

$nbDemandesNouvelles = 0;
try {
  $nbDemandesNouvelles = (int)$pdo->query("SELECT COUNT(*) FROM demandes_formation WHERE statut='nouvelle'")->fetchColumn();
} catch (Throwable $e) {}

$nbPaiements = 0; $revenuTotal = 0;
try {
  $kp = $pdo->query("SELECT COUNT(*) AS nb, SUM(montant) AS rev FROM paiements_inscription WHERE statut='success'")->fetch(PDO::FETCH_ASSOC) ?: [];
  $nbPaiements = (int)($kp['nb'] ?? 0);
  $revenuTotal = (int)($kp['rev'] ?? 0);
} catch (Throwable $e) {}

/* =====================================================
   LISTES
===================================================== */
$lastUsers = $pdo->query("
  SELECT first_name, last_name, email, role, created_at FROM users ORDER BY id DESC LIMIT 6
")->fetchAll(PDO::FETCH_ASSOC);

$lastPreinsc = [];
try {
  $lastPreinsc = $pdo->query("
    SELECT p.id, p.nom, p.prenoms, p.statut, p.created_at, f.titre AS formation
    FROM preinscriptions p
    LEFT JOIN formations f ON f.id = p.formation_id
    ORDER BY p.id DESC LIMIT 6
  ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}

$auditLogs = [];
try {
  $auditLogs = $pdo->query("
    SELECT action, entity, entity_id, created_at FROM audit_logs ORDER BY id DESC LIMIT 8
  ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}

$nextFormations = [];
try {
  $nextFormations = $pdo->query("
    SELECT titre, date_debut, domaine, is_samedi_pro FROM formations
    WHERE statut='active' AND date_debut >= CURDATE()
    ORDER BY date_debut ASC LIMIT 5
  ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}

ob_start();
?>
<style>
.sa-hello{font-size:20px;font-weight:800;margin:0 0 2px;color:#0f172a}
.sa-sub{color:#6b7280;font-size:13px;margin:0 0 20px}
.sa-kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:22px}
.sa-kpi{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:18px 16px;box-shadow:0 4px 14px rgba(15,23,42,.06);display:flex;flex-direction:column;gap:3px}
.sa-kpi b{font-size:28px;font-weight:900;line-height:1;color:#0f172a}
.sa-kpi span{font-size:11px;color:#6b7280;font-weight:700;text-transform:uppercase;letter-spacing:.3px}
.sa-kpi.k-blue  {border-top:3px solid #3b82f6}.sa-kpi.k-blue   b{color:#1d4ed8}
.sa-kpi.k-green {border-top:3px solid #22c55e}.sa-kpi.k-green  b{color:#16a34a}
.sa-kpi.k-amber {border-top:3px solid #f59e0b}.sa-kpi.k-amber  b{color:#b45309}
.sa-kpi.k-indigo{border-top:3px solid #6366f1}.sa-kpi.k-indigo b{color:#4338ca}
.sa-kpi.k-red   {border-top:3px solid #ef4444}.sa-kpi.k-red    b{color:#dc2626}
.sa-kpi.k-teal  {border-top:3px solid #14b8a6}.sa-kpi.k-teal   b{color:#0f766e}
.sa-kpi.k-orange{border-top:3px solid #f97316}.sa-kpi.k-orange b{color:#c2410c}
.sa-kpi.k-sky   {border-top:3px solid #0ea5e9}.sa-kpi.k-sky    b{color:#0284c7}
.sa-kpi .hint{font-size:11px;color:#94a3b8;margin-top:1px}

.sa-grid{display:grid;grid-template-columns:1.3fr .7fr;gap:18px;margin-top:0}
.sa-grid2{display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-top:18px}

.sa-card{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:18px;box-shadow:0 2px 8px rgba(15,23,42,.04)}
.sa-card-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;gap:8px}
.sa-card-head h3{margin:0;font-size:15px;font-weight:800;color:#0f172a}
.sa-card-head a{font-size:12px;font-weight:700;color:#1f3fe0;text-decoration:none;white-space:nowrap}
.sa-card-head a:hover{text-decoration:underline}

.sa-table{width:100%;border-collapse:collapse;font-size:13px}
.sa-table th{font-size:11px;text-transform:uppercase;letter-spacing:.3px;color:#6b7280;text-align:left;padding:8px 10px;border-bottom:2px solid #eef2f7;background:#fafbff}
.sa-table td{padding:9px 10px;border-bottom:1px solid #f1f5f9;vertical-align:middle}
.sa-table tr:hover td{background:#f8faff}

.sa-list{list-style:none;margin:0;padding:0}
.sa-list li{display:flex;justify-content:space-between;align-items:flex-start;gap:10px;padding:10px 0;border-bottom:1px solid #f1f5f9}
.sa-list li:last-child{border-bottom:0}
.sa-list .lbl{font-weight:700;font-size:13px;color:#0f172a}
.sa-list .sub{font-size:11.5px;color:#6b7280;margin-top:2px}
.sa-list .ts{font-size:11.5px;color:#94a3b8;white-space:nowrap;font-weight:600}

.pill{padding:3px 9px;border-radius:999px;font-size:10.5px;font-weight:800;white-space:nowrap;display:inline-flex;align-items:center;gap:4px}
.pill::before{content:'';width:5px;height:5px;border-radius:50%;background:currentColor;display:inline-block}
.pill.ok    {background:#dcfce7;color:#166534}
.pill.wait  {background:#fff7ed;color:#9a3412}
.pill.rejete{background:#fee2e2;color:#991b1b}
.pill.role-sa{background:#eef2ff;color:#3730a3}
.pill.role-ad{background:#ecfdf5;color:#047857}
.pill.role-co{background:#fffbeb;color:#92400e}
.pill.role-rh{background:#fdf4ff;color:#7e22ce}
.pill.role-us{background:#f1f5f9;color:#334155}

.chip-sp{display:inline-block;font-size:10px;font-weight:800;color:#1f3fe0;background:rgba(31,63,224,.08);border:1px solid rgba(31,63,224,.2);border-radius:999px;padding:1px 7px;margin-left:5px}

.alert-banner{background:#fff7ed;border:1px solid #fed7aa;border-radius:12px;padding:10px 16px;font-size:13px;font-weight:700;color:#9a3412;display:flex;align-items:center;gap:8px;margin-bottom:18px}

@media(max-width:1100px){.sa-kpis{grid-template-columns:repeat(3,1fr)}}
@media(max-width:700px){.sa-kpis{grid-template-columns:1fr 1fr}.sa-grid,.sa-grid2{grid-template-columns:1fr}}
</style>

<p class="sa-hello">Bonjour <?= e($prenomAdmin); ?> &#128075;</p>
<p class="sa-sub">Vue d'ensemble de la plateforme IBIG EDUFORM &mdash; <?= date('l d F Y'); ?></p>

<?php if ($nbPreinscNouvelles > 0 || $nbDemandesNouvelles > 0): ?>
<div class="alert-banner">
  &#9888;&#65039;
  <?php if ($nbPreinscNouvelles > 0): ?><?= $nbPreinscNouvelles; ?> préinscription(s) en attente<?php endif; ?>
  <?php if ($nbPreinscNouvelles > 0 && $nbDemandesNouvelles > 0): ?> &nbsp;&middot;&nbsp; <?php endif; ?>
  <?php if ($nbDemandesNouvelles > 0): ?><?= $nbDemandesNouvelles; ?> demande(s) de formation sans réponse<?php endif; ?>
</div>
<?php endif; ?>

<!-- KPIs -->
<div class="sa-kpis">
  <div class="sa-kpi k-blue">
    <b><?= $nbPreinsc; ?></b>
    <span>&#128221; Préinscriptions</span>
    <div class="hint">&#128197; Aujourd'hui : <strong><?= $nbPreinscToday; ?></strong> &nbsp;·&nbsp; &#9888; En attente : <strong><?= $nbPreinscNouvelles; ?></strong></div>
  </div>
  <div class="sa-kpi k-orange">
    <b><?= $nbDemandesNouvelles; ?></b>
    <span>&#127891; Demandes nouvelles</span>
    <div class="hint">Demandes de formation sans réponse</div>
  </div>
  <div class="sa-kpi k-green">
    <b><?= $nbFormations; ?></b>
    <span>&#128196; Formations actives</span>
    <div class="hint"><?= $nbFormationsTotal; ?> au total dans le catalogue</div>
  </div>
  <div class="sa-kpi k-indigo">
    <b><?= $nbUsers; ?></b>
    <span>&#128100; Utilisateurs</span>
    <div class="hint">Comptes enregistrés</div>
  </div>
  <?php if ($revenuTotal > 0): ?>
  <div class="sa-kpi k-teal">
    <b><?= number_format($revenuTotal, 0, ',', ' '); ?></b>
    <span>&#128176; Revenus (FCFA)</span>
    <div class="hint"><?= $nbPaiements; ?> paiement(s) validé(s)</div>
  </div>
  <?php endif; ?>
</div>

<!-- GRILLE PRINCIPALE -->
<div class="sa-grid">

  <!-- PRÉINSCRIPTIONS -->
  <div class="sa-card">
    <div class="sa-card-head">
      <h3>&#128221; Préinscriptions récentes</h3>
      <a href="/admin/preinscriptions/index.php">Tout voir &rarr;</a>
    </div>
    <?php if (!$lastPreinsc): ?>
      <p style="color:#94a3b8;font-size:13px">Aucune préinscription.</p>
    <?php else: ?>
    <table class="sa-table">
      <thead><tr><th>Candidat</th><th>Formation</th><th>Statut</th><th>Date</th></tr></thead>
      <tbody>
      <?php foreach ($lastPreinsc as $p):
        $nom = trim((string)($p['prenoms'] ?? '') . ' ' . (string)($p['nom'] ?? '')) ?: '—';
        $stl = strtolower((string)($p['statut'] ?? ''));
        $cls = in_array($stl, ['traitee','traitée','confirme','confirmee','valide'], true) ? 'ok'
             : (in_array($stl, ['rejete','rejetee','rejeté','rejetée','refuse','refusee'], true) ? 'rejete' : 'wait');
        $lbl = match(true){
          in_array($stl,['traitee','traitée'])=>'Traitée',
          in_array($stl,['confirme','confirmee','valide'])=>'Confirmée',
          in_array($stl,['rejete','rejetee','rejeté','rejetée','refuse','refusee'])=>'Rejetée',
          default=>'Nouvelle'
        };
        $isToday = !empty($p['created_at']) && date('Y-m-d',strtotime((string)$p['created_at']))=== date('Y-m-d');
      ?>
        <tr>
          <td>
            <strong><?= e($nom); ?></strong>
            <?php if ($isToday): ?><span style="font-size:10px;font-weight:800;background:#fde68a;color:#92400e;border-radius:5px;padding:1px 5px;margin-left:5px">AUJOURD'HUI</span><?php endif; ?>
          </td>
          <td style="color:#64748b;font-size:12px;max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= e($p['formation'] ?: '—'); ?></td>
          <td><span class="pill <?= $cls; ?>"><?= $lbl; ?></span></td>
          <td style="color:#94a3b8;font-size:12px;white-space:nowrap"><?= !empty($p['created_at']) ? date('d/m H:i', strtotime((string)$p['created_at'])) : '—'; ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <a href="/admin/preinscriptions/index.php" style="display:inline-block;margin-top:14px;padding:8px 16px;background:#1f3fe0;color:#fff;border-radius:10px;font-weight:800;font-size:13px;text-decoration:none">&#128221; Gérer les préinscriptions</a>
    <?php endif; ?>
  </div>

  <!-- PROCHAINES FORMATIONS -->
  <div class="sa-card">
    <div class="sa-card-head">
      <h3>&#128197; Prochaines sessions</h3>
      <a href="/admin/formations/index.php">Gérer &rarr;</a>
    </div>
    <?php if (!$nextFormations): ?>
      <p style="color:#94a3b8;font-size:13px">Aucune session prévue.</p>
    <?php else: ?>
    <ul class="sa-list">
      <?php foreach ($nextFormations as $s): ?>
        <li>
          <span>
            <div class="lbl"><?= e($s['titre']); ?><?php if (!empty($s['is_samedi_pro'])): ?><span class="chip-sp">SP</span><?php endif; ?></div>
            <div class="sub"><?= e($s['domaine'] ?? ''); ?></div>
          </span>
          <span class="ts"><?= !empty($s['date_debut']) ? date('d/m/Y', strtotime((string)$s['date_debut'])) : '—'; ?></span>
        </li>
      <?php endforeach; ?>
    </ul>
    <?php endif; ?>
  </div>

</div>

<div class="sa-grid2">

  <!-- UTILISATEURS -->
  <div class="sa-card">
    <div class="sa-card-head">
      <h3>&#128100; Derniers utilisateurs</h3>
      <a href="/admin/users/index.php">Gérer &rarr;</a>
    </div>
    <?php if (!$lastUsers): ?>
      <p style="color:#94a3b8;font-size:13px">Aucun utilisateur.</p>
    <?php else: ?>
    <table class="sa-table">
      <thead><tr><th>Nom</th><th>Email</th><th>Rôle</th><th>Date</th></tr></thead>
      <tbody>
      <?php foreach ($lastUsers as $usr):
        $roleClass = match($usr['role'] ?? ''){
          'super_admin'=>'role-sa','admin'=>'role-ad','commercial'=>'role-co',
          'rh'=>'role-rh', default=>'role-us'
        };
      ?>
        <tr>
          <td><strong><?= e(trim($usr['first_name'].' '.$usr['last_name'])); ?></strong></td>
          <td style="color:#64748b;font-size:12px"><?= e($usr['email']); ?></td>
          <td><span class="pill <?= $roleClass; ?>"><?= e($usr['role'] ?? '—'); ?></span></td>
          <td style="color:#94a3b8;font-size:12px"><?= !empty($usr['created_at']) ? date('d/m/Y', strtotime((string)$usr['created_at'])) : '—'; ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>

  <!-- JOURNAL D'AUDIT -->
  <div class="sa-card">
    <div class="sa-card-head">
      <h3>&#128221; Journal d'audit</h3>
      <span style="font-size:11.5px;color:#94a3b8">Actions sensibles récentes</span>
    </div>
    <?php if (!$auditLogs): ?>
      <p style="color:#94a3b8;font-size:13px">Aucun log d'audit.</p>
    <?php else: ?>
    <ul class="sa-list">
      <?php foreach ($auditLogs as $log): ?>
        <li>
          <span>
            <div class="lbl"><?= e($log['action']); ?></div>
            <div class="sub"><?= e($log['entity']); ?> #<?= (int)$log['entity_id']; ?></div>
          </span>
          <span class="ts"><?= !empty($log['created_at']) ? date('d/m H:i', strtotime((string)$log['created_at'])) : '—'; ?></span>
        </li>
      <?php endforeach; ?>
    </ul>
    <?php endif; ?>
  </div>

</div>

<!-- RACCOURCIS -->
<div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:20px">
  <a href="/admin/preinscriptions/index.php" style="padding:10px 18px;background:#1f3fe0;color:#fff;border-radius:10px;font-weight:800;font-size:13px;text-decoration:none">&#128221; Préinscriptions</a>
  <a href="/admin/demandes-formation/index.php" style="padding:10px 18px;background:#f97316;color:#fff;border-radius:10px;font-weight:800;font-size:13px;text-decoration:none">&#127891; Demandes</a>
  <a href="/admin/formations/index.php" style="padding:10px 18px;background:#16a34a;color:#fff;border-radius:10px;font-weight:800;font-size:13px;text-decoration:none">&#128196; Formations</a>
  <a href="/admin/statistics/index.php" style="padding:10px 18px;background:#6366f1;color:#fff;border-radius:10px;font-weight:800;font-size:13px;text-decoration:none">&#128202; Statistiques</a>
  <a href="/admin/users/index.php" style="padding:10px 18px;background:#0f172a;color:#fff;border-radius:10px;font-weight:800;font-size:13px;text-decoration:none">&#128100; Utilisateurs</a>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout/layout.php';
