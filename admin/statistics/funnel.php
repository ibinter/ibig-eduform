<?php
declare(strict_types=1);

require_once __DIR__ . '/../auth/middleware.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/helpers.php';

Middleware::requireAuth();

$pageTitle  = 'Funnel ROI (Visite &#8594; Intention &#8594; Lead)';
$activeMenu = 'stats_funnel';

$pdo = Database::connect();

/* ==========================
   KPI GLOBAL
========================== */

/* Sessions (site_visits) */
$kpiSessions = (int)$pdo->query("
  SELECT COUNT(DISTINCT v.session_id)
  FROM site_visits v
  WHERE v.is_bot = 0
")->fetchColumn();

/* Intentions (score >= 6) */
$kpiIntentions = (int)$pdo->query("
  SELECT COUNT(*) FROM (
    SELECT i.session_key,
      SUM(
        CASE
          WHEN i.action IN ('time_30s','time_60s') THEN 2
          WHEN i.action = 'scroll' THEN 1
          WHEN i.action = 'cta_click' THEN 3
          WHEN i.action = 'whatsapp_click' THEN 5
          ELSE 0
        END
      ) score
    FROM site_intents i
    GROUP BY i.session_key
    HAVING score >= 6
  ) t
")->fetchColumn();

/* Leads */
$kpiLeads = (int)$pdo->query("
  SELECT COUNT(*) FROM preinscriptions
")->fetchColumn();

$tauxVisiteIntention = $kpiSessions > 0 ? round($kpiIntentions / $kpiSessions * 100, 2) : 0;
$tauxIntentionLead   = $kpiIntentions > 0 ? round($kpiLeads / $kpiIntentions * 100, 2) : 0;

/* ==========================
   ROI PAR SOURCE/CAMPAGNE
========================== */

$rows = $pdo->query("
  SELECT
    COALESCE(v.utm_source,'direct')   utm_source,
    COALESCE(v.utm_campaign,'-')     utm_campaign,

    COUNT(DISTINCT v.session_id)     sessions,

    COUNT(DISTINCT it.session_key)   intentions,

    COUNT(DISTINCT p.id)             leads,

    ROUND(COUNT(DISTINCT it.session_key) / NULLIF(COUNT(DISTINCT v.session_id),0) * 100, 2) taux_intention,
    ROUND(COUNT(DISTINCT p.id) / NULLIF(COUNT(DISTINCT it.session_key),0) * 100, 2)         taux_lead
  FROM site_visits v

  /* Intentions = sessions dont score >= 6 */
  LEFT JOIN (
    SELECT i.session_key
    FROM site_intents i
    GROUP BY i.session_key
    HAVING SUM(
      CASE
        WHEN i.action IN ('time_30s','time_60s') THEN 2
        WHEN i.action = 'scroll' THEN 1
        WHEN i.action = 'cta_click' THEN 3
        WHEN i.action = 'whatsapp_click' THEN 5
        ELSE 0
      END
    ) >= 6
  ) it ON it.session_key = v.session_id

  /* Leads reliés par session_key */
  LEFT JOIN preinscriptions p ON p.session_key = v.session_id

  WHERE v.is_bot = 0
  GROUP BY utm_source, utm_campaign
  ORDER BY leads DESC, taux_lead DESC
  LIMIT 200
")->fetchAll(PDO::FETCH_ASSOC);

ob_start();
?>

<div class="grid kpi-grid">

  <div class="card kpi-card">
    <div class="kpi-title">Sessions</div>
    <div class="kpi-value"><?= (int)$kpiSessions; ?></div>
    <div class="muted">Trafic r&#233;el (bots exclus)</div>
  </div>

  <div class="card kpi-card">
    <div class="kpi-title">Intentions</div>
    <div class="kpi-value"><?= (int)$kpiIntentions; ?></div>
    <div class="muted">Score &#8805; 6</div>
  </div>

  <div class="card kpi-card">
    <div class="kpi-title">Leads</div>
    <div class="kpi-value"><?= (int)$kpiLeads; ?></div>
    <div class="muted">Pr&#233;inscriptions</div>
  </div>

  <div class="card kpi-card">
    <div class="kpi-title">Taux Visite &#8594; Intention</div>
    <div class="kpi-value"><?= $tauxVisiteIntention; ?>%</div>
    <div class="muted">Conversion attention</div>
  </div>

  <div class="card kpi-card">
    <div class="kpi-title">Taux Intention &#8594; Lead</div>
    <div class="kpi-value"><?= $tauxIntentionLead; ?>%</div>
    <div class="muted">Conversion acquisition</div>
  </div>

</div>

<div class="card" style="margin-top:18px">
  <h2 style="margin:0 0 10px">&#128200; ROI par source / campagne</h2>

  <table class="table">
    <thead>
      <tr>
        <th>UTM Source</th>
        <th>Campagne</th>
        <th>Sessions</th>
        <th>Intentions</th>
        <th>Leads</th>
        <th>Taux intention</th>
        <th>Taux lead</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($rows)): ?>
        <tr><td colspan="7" class="muted">Aucune donn&#233;e.</td></tr>
      <?php else: ?>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><strong><?= e($r['utm_source']); ?></strong></td>
            <td class="muted"><?= e($r['utm_campaign']); ?></td>
            <td><?= (int)$r['sessions']; ?></td>
            <td><?= (int)$r['intentions']; ?></td>
            <td><strong><?= (int)$r['leads']; ?></strong></td>
            <td><?= e($r['taux_intention']); ?>%</td>
            <td><strong><?= e($r['taux_lead']); ?>%</strong></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout/layout.php';
