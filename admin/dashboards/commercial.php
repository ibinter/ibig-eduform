<?php
declare(strict_types=1);

$pageTitle  = 'Tableau de bord commercial';
$activeMenu = 'dashboard';

$pdo = Database::connect();
$u   = function_exists('auth_user') ? (auth_user() ?? []) : [];
$prenomAdmin = trim((string)($u['first_name'] ?? '')) ?: 'Commercial';

/* ================= COMPTEURS PRÉINSCRIPTIONS ================= */
$total = 0; $nbTraitees = 0; $nbRejetees = 0; $nbToday = 0;
try {
  $k = $pdo->query("
    SELECT
      COUNT(*) AS total,
      SUM(CASE WHEN LOWER(COALESCE(statut,'')) IN ('traitee','traitée','confirme','confirmee','valide') THEN 1 ELSE 0 END) AS traitees,
      SUM(CASE WHEN LOWER(COALESCE(statut,'')) IN ('rejete','rejetee','rejeté','rejetée','refuse','refusee') THEN 1 ELSE 0 END) AS rejetees,
      SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) AS today
    FROM preinscriptions
  ")->fetch(PDO::FETCH_ASSOC) ?: [];
  $total      = (int)($k['total'] ?? 0);
  $nbTraitees = (int)($k['traitees'] ?? 0);
  $nbRejetees = (int)($k['rejetees'] ?? 0);
  $nbToday    = (int)($k['today'] ?? 0);
} catch (Throwable $e) {}
$nbNouvelles = max(0, $total - $nbTraitees - $nbRejetees);

/* ================= PRÉINSCRIPTIONS RÉCENTES ================= */
$lastPreinsc = [];
try {
  $lastPreinsc = $pdo->query("
    SELECT p.id, p.nom, p.prenoms, p.statut, p.created_at, f.titre AS formation
    FROM preinscriptions p
    LEFT JOIN formations f ON f.id = p.formation_id
    ORDER BY p.id DESC
    LIMIT 8
  ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}

/* ================= PROCHAINES SESSIONS (source = formations) ================= */
$nextSessions = [];
try {
  $nextSessions = $pdo->query("
    SELECT titre, date_debut, domaine, is_samedi_pro
    FROM formations
    WHERE statut='active' AND date_debut IS NOT NULL AND date_debut >= CURDATE()
    ORDER BY date_debut ASC
    LIMIT 6
  ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}

ob_start();
?>

<style>
.dc-hello{font-size:20px;font-weight:800;margin:0 0 2px;color:#0f172a}
.dc-sub{color:#6b7280;font-size:13px;margin:0 0 20px}
.dc-kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:22px}
.dc-kpi{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:18px 16px;box-shadow:0 4px 14px rgba(15,23,42,.06);display:flex;flex-direction:column;gap:3px}
.dc-kpi b{font-size:28px;font-weight:900;line-height:1;color:#0f172a}
.dc-kpi span{font-size:11px;color:#6b7280;font-weight:700;text-transform:uppercase;letter-spacing:.3px}
.dc-kpi .hint{font-size:11px;color:#94a3b8;margin-top:1px}
.dc-kpi.k-total {border-top:3px solid #6366f1}.dc-kpi.k-total b{color:#4338ca}
.dc-kpi.k-wait  {border-top:3px solid #f59e0b}.dc-kpi.k-wait  b{color:#b45309}
.dc-kpi.k-ok    {border-top:3px solid #22c55e}.dc-kpi.k-ok    b{color:#16a34a}
.dc-kpi.k-today {border-top:3px solid #3b82f6}.dc-kpi.k-today b{color:#1d4ed8}
.dc-grid{display:grid;grid-template-columns:1.3fr .7fr;gap:18px}
.dc-list{margin:12px 0 0;padding:0;list-style:none}
.dc-list li{display:flex;justify-content:space-between;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid #f1f5f9}
.dc-list li:last-child{border-bottom:0}
.dc-list .who b{font-size:14px;color:#0f172a}
.dc-list .who small{display:block;color:#6b7280;font-size:12px}
.pill{padding:3px 9px;border-radius:999px;font-size:10.5px;font-weight:800;white-space:nowrap;display:inline-flex;align-items:center;gap:4px}
.pill::before{content:'';width:5px;height:5px;border-radius:50%;background:currentColor;display:inline-block}
.pill.ok    {background:#dcfce7;color:#166534}
.pill.wait  {background:#fff7ed;color:#9a3412}
.pill.rejete{background:#fee2e2;color:#991b1b}
.dc-open{font-size:12px;font-weight:800;color:#1f3fe0;text-decoration:none;white-space:nowrap}
.dc-open:hover{text-decoration:underline}
.chip-sp{display:inline-block;font-size:10px;font-weight:800;color:#1f3fe0;background:rgba(31,63,224,.08);border:1px solid rgba(31,63,224,.2);border-radius:999px;padding:1px 7px;margin-left:6px}
.alert-banner{background:#fff7ed;border:1px solid #fed7aa;border-radius:12px;padding:10px 16px;font-size:13px;font-weight:700;color:#9a3412;display:flex;align-items:center;gap:8px;margin-bottom:18px}
@media(max-width:980px){.dc-kpis{grid-template-columns:1fr 1fr}.dc-grid{grid-template-columns:1fr}}
</style>

<p class="dc-hello">Bonjour <?= e($prenomAdmin); ?> &#128075;</p>
<p class="dc-sub">Voici l&rsquo;activité des préinscriptions &mdash; <?= date('l d F Y'); ?>. Contactez et traitez vos prospects directement.</p>

<?php if ($nbNouvelles > 0): ?>
<div class="alert-banner">&#9888;&#65039; <?= (int)$nbNouvelles; ?> préinscription(s) en attente de traitement</div>
<?php endif; ?>

<!-- KPIs -->
<div class="dc-kpis">
  <div class="dc-kpi k-total"><b><?= (int)$total; ?></b><span>&#128221; Total préinscriptions</span><div class="hint">Toutes les demandes</div></div>
  <div class="dc-kpi k-wait" ><b><?= (int)$nbNouvelles; ?></b><span>&#9888; À traiter</span><div class="hint">Nouvelles non traitées</div></div>
  <div class="dc-kpi k-ok"   ><b><?= (int)$nbTraitees; ?></b><span>&#9989; Traitées</span><div class="hint">Confirmées ou validées</div></div>
  <div class="dc-kpi k-today"><b><?= (int)$nbToday; ?></b><span>&#128197; Aujourd&rsquo;hui</span><div class="hint">Reçues ce jour</div></div>
</div>

<div class="dc-grid">

  <!-- PRÉINSCRIPTIONS RÉCENTES -->
  <div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center">
      <h3 style="margin:0">Préinscriptions récentes</h3>
      <a class="dc-open" href="/admin/preinscriptions/index.php">Tout voir →</a>
    </div>
    <?php if (!$lastPreinsc): ?>
      <div class="muted" style="margin-top:10px">Aucune préinscription.</div>
    <?php else: ?>
      <ul class="dc-list">
        <?php foreach ($lastPreinsc as $p):
          $nom = trim((string)($p['prenoms'] ?? '') . ' ' . (string)($p['nom'] ?? '')) ?: 'Prospect';
          $stl = strtolower((string)($p['statut'] ?? ''));
          $cls = in_array($stl, ['traitee','traitée','confirme','confirmee','valide'], true) ? 'ok'
               : (in_array($stl, ['rejete','rejetee','rejeté','rejetée','refuse','refusee'], true) ? 'rejete' : 'wait');
        ?>
          <li>
            <span class="who">
              <b><?= e($nom); ?></b>
              <small><?= e($p['formation'] ?: '—'); ?> · <?= !empty($p['created_at']) ? date('d/m/Y H:i', strtotime((string)$p['created_at'])) : '—'; ?></small>
            </span>
            <span style="display:flex;align-items:center;gap:10px">
              <span class="pill <?= $cls; ?>"><?= e($p['statut'] ?: 'nouvelle'); ?></span>
              <a class="dc-open" href="/admin/preinscriptions/view.php?id=<?= (int)$p['id']; ?>">Ouvrir</a>
            </span>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
    <a class="btn btn-primary" style="margin-top:14px;display:inline-block" href="/admin/preinscriptions/index.php">
      📋 Gérer les préinscriptions
    </a>
  </div>

  <!-- PROCHAINES SESSIONS -->
  <div class="card">
    <h3 style="margin:0">Prochaines sessions</h3>
    <div class="muted" style="font-size:12px">Pour conseiller vos prospects</div>
    <?php if (!$nextSessions): ?>
      <div class="muted" style="margin-top:10px">Aucune session à venir.</div>
    <?php else: ?>
      <ul class="dc-list">
        <?php foreach ($nextSessions as $s): ?>
          <li>
            <span class="who">
              <b><?= e($s['titre']); ?><?php if (!empty($s['is_samedi_pro'])): ?><span class="chip-sp">Samedi Pro</span><?php endif; ?></b>
              <small><?= e($s['domaine'] ?? ''); ?></small>
            </span>
            <span class="muted" style="font-size:12.5px;font-weight:700;white-space:nowrap">
              <?= !empty($s['date_debut']) ? date('d/m/Y', strtotime((string)$s['date_debut'])) : '—'; ?>
            </span>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
    <a class="btn btn-secondary" style="margin-top:14px;display:inline-block" href="/admin/formations/index.php">
      Voir les formations
    </a>
  </div>

</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout/layout.php';
