<?php
declare(strict_types=1);
/**
 * IBIG EDUFORM — admin/satisfaction/index.php
 * Vue des réponses au formulaire de satisfaction.
 */

require_once __DIR__ . '/../_init.php';

Middleware::requireAuth();

$pdo = Database::connect();

/* Auto-create tables si pas encore migrées */
$pdo->exec("CREATE TABLE IF NOT EXISTS satisfaction_reponses (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  preinscription_id BIGINT UNSIGNED NOT NULL,
  email VARCHAR(255) NOT NULL,
  formation_titre VARCHAR(500) DEFAULT NULL,
  note_globale TINYINT UNSIGNED NOT NULL,
  note_contenu TINYINT UNSIGNED DEFAULT NULL,
  note_formateur TINYINT UNSIGNED DEFAULT NULL,
  note_logistique TINYINT UNSIGNED DEFAULT NULL,
  points_positifs TEXT DEFAULT NULL,
  points_ameliorer TEXT DEFAULT NULL,
  recommande TINYINT(1) DEFAULT NULL,
  commentaire TEXT DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_preinsc (preinscription_id),
  INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

/* ── Filtres ── */
$search = trim((string)($_GET['q'] ?? ''));

$where  = '1=1';
$params = [];
if ($search !== '') {
    $where .= " AND (s.email LIKE :q OR s.formation_titre LIKE :q)";
    $params[':q'] = '%' . $search . '%';
}

/* ── Stats globales ── */
$stats = ['nb' => 0, 'avg' => 0, 'recommande' => 0, 'pct_recommande' => 0];
try {
    $ss = $pdo->query("
        SELECT COUNT(*) AS nb,
               ROUND(AVG(note_globale),1) AS avg_note,
               SUM(CASE WHEN recommande=1 THEN 1 ELSE 0 END) AS recommande,
               ROUND(SUM(CASE WHEN recommande=1 THEN 1 ELSE 0 END)*100.0/NULLIF(COUNT(CASE WHEN recommande IS NOT NULL THEN 1 END),0),0) AS pct
        FROM satisfaction_reponses
    ")->fetch(PDO::FETCH_ASSOC) ?: [];
    $stats = [
        'nb'            => (int)($ss['nb'] ?? 0),
        'avg'           => (float)($ss['avg_note'] ?? 0),
        'recommande'    => (int)($ss['recommande'] ?? 0),
        'pct_recommande'=> (int)($ss['pct'] ?? 0),
    ];
} catch (Throwable $e) {}

/* ── Réponses ── */
$stmt = $pdo->prepare("
    SELECT s.*, p.nom AS p_nom, p.prenoms AS p_prenoms
    FROM satisfaction_reponses s
    LEFT JOIN preinscriptions p ON p.id = s.preinscription_id
    WHERE {$where}
    ORDER BY s.created_at DESC
    LIMIT 200
");
$stmt->execute($params);
$reponses = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle  = 'Satisfaction apprenants';
$activeMenu = 'satisfaction';

ob_start();
?>
<style>
.sat-kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:22px}
@media(max-width:700px){.sat-kpis{grid-template-columns:1fr 1fr}}
.sat-kpi{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:16px;text-align:center;box-shadow:0 2px 8px rgba(0,0,0,.04)}
.sat-kpi b{display:block;font-size:2rem;font-weight:900;color:#0a1733;line-height:1}
.sat-kpi span{font-size:11px;color:#6b7280;font-weight:700;text-transform:uppercase;margin-top:4px;display:block}
.stars{color:#f59e0b;font-size:1rem}
.sat-table{width:100%;border-collapse:collapse;font-size:13px}
.sat-table th{font-size:11px;text-transform:uppercase;letter-spacing:.3px;color:#6b7280;padding:8px 10px;border-bottom:2px solid #eef2f7;text-align:left;background:#fafbff}
.sat-table td{padding:9px 10px;border-bottom:1px solid #f1f5f9;vertical-align:top}
.sat-table tr:hover td{background:#f8faff}
.note-badge{display:inline-block;width:28px;height:28px;border-radius:50%;text-align:center;line-height:28px;font-weight:900;font-size:12px}
.n5{background:#dcfce7;color:#166534}.n4{background:#d1fae5;color:#065f46}.n3{background:#fef9c3;color:#854d0e}.n2{background:#fee2e2;color:#991b1b}.n1{background:#fee2e2;color:#991b1b}
.pill-rec{padding:3px 9px;border-radius:999px;font-size:11px;font-weight:700}
.pill-rec.oui{background:#dcfce7;color:#166534}.pill-rec.non{background:#fee2e2;color:#991b1b}
</style>

<!-- Stats -->
<div class="sat-kpis">
  <div class="sat-kpi">
    <b><?= $stats['nb']; ?></b>
    <span>Avis reçus</span>
  </div>
  <div class="sat-kpi">
    <b style="color:#f59e0b"><?= number_format($stats['avg'], 1); ?>/5</b>
    <span>Note moyenne</span>
    <div class="stars"><?= str_repeat('★', (int)round($stats['avg'])) . str_repeat('☆', 5 - (int)round($stats['avg'])); ?></div>
  </div>
  <div class="sat-kpi">
    <b><?= $stats['recommande']; ?></b>
    <span>Recommandent</span>
  </div>
  <div class="sat-kpi">
    <b style="color:#22c55e"><?= $stats['pct_recommande']; ?>%</b>
    <span>Taux satisfaction</span>
  </div>
</div>

<!-- Filtre -->
<div style="margin-bottom:16px;display:flex;gap:10px;flex-wrap:wrap">
  <form method="get" style="display:flex;gap:8px;flex:1;min-width:200px">
    <input type="text" name="q" value="<?= e($search); ?>" placeholder="Rechercher par email ou formation…"
           style="flex:1;padding:8px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:13px">
    <button class="btn btn-primary" type="submit">🔍</button>
    <?php if ($search): ?><a class="btn btn-secondary" href="index.php">✕</a><?php endif; ?>
  </form>
</div>

<?php if (!$reponses): ?>
<div class="card" style="text-align:center;padding:48px;color:#94a3b8">
  <p style="font-size:2rem">⭐</p>
  <p>Aucune réponse<?= $search ? ' pour cette recherche' : ' encore reçue'; ?>.</p>
  <p style="font-size:12px;margin-top:8px">Envoyez le formulaire de satisfaction depuis la fiche d'une préinscription.</p>
</div>
<?php else: ?>
<div class="card" style="overflow-x:auto">
  <table class="sat-table">
    <thead>
      <tr>
        <th>Apprenant</th>
        <th>Formation</th>
        <th>Glob.</th>
        <th>Contenu</th>
        <th>Format.</th>
        <th>Logist.</th>
        <th>Recommande</th>
        <th>Points positifs</th>
        <th>À améliorer</th>
        <th>Date</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($reponses as $r):
      $nom = trim((string)($r['p_prenoms'] ?? '') . ' ' . (string)($r['p_nom'] ?? '')) ?: $r['email'];
      $ng  = (int)$r['note_globale'];
      $nClass = 'n' . max(1, min(5, $ng));
    ?>
      <tr>
        <td>
          <div style="font-weight:700;font-size:12px"><?= e($nom); ?></div>
          <div style="font-size:11px;color:#94a3b8"><?= e($r['email']); ?></div>
        </td>
        <td style="font-size:12px;color:#475569;max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= e($r['formation_titre'] ?: '—'); ?></td>
        <td><span class="note-badge <?= $nClass; ?>"><?= $ng; ?></span></td>
        <td style="font-size:12px;color:#6b7280"><?= $r['note_contenu'] ? (int)$r['note_contenu'] . '/5' : '—'; ?></td>
        <td style="font-size:12px;color:#6b7280"><?= $r['note_formateur'] ? (int)$r['note_formateur'] . '/5' : '—'; ?></td>
        <td style="font-size:12px;color:#6b7280"><?= $r['note_logistique'] ? (int)$r['note_logistique'] . '/5' : '—'; ?></td>
        <td>
          <?php if ($r['recommande'] === null): ?>—
          <?php elseif ((int)$r['recommande'] === 1): ?><span class="pill-rec oui">👍 Oui</span>
          <?php else: ?><span class="pill-rec non">👎 Non</span>
          <?php endif; ?>
        </td>
        <td style="font-size:11px;color:#475569;max-width:180px"><?= e(mb_substr((string)($r['points_positifs'] ?? '—'), 0, 80)); ?><?= mb_strlen((string)($r['points_positifs'] ?? '')) > 80 ? '…' : ''; ?></td>
        <td style="font-size:11px;color:#475569;max-width:180px"><?= e(mb_substr((string)($r['points_ameliorer'] ?? '—'), 0, 80)); ?><?= mb_strlen((string)($r['points_ameliorer'] ?? '')) > 80 ? '…' : ''; ?></td>
        <td style="font-size:11px;color:#94a3b8;white-space:nowrap"><?= date('d/m/Y', strtotime((string)$r['created_at'])); ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout/layout.php';
