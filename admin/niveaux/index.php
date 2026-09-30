<?php
declare(strict_types=1);
/**
 * ADMIN — Gestion des niveaux par formation
 * Débutant · Intermédiaire · Expert
 */
ob_start();
require_once __DIR__ . '/../_init.php';
require_once __DIR__ . '/../auth/middleware.php';
Middleware::requireAuth();

$pageTitle  = 'Niveaux de formations';
$activeMenu = 'niveaux';
$pdo = Database::connect();

// ── Filtres ──────────────────────────────────────────────────────────
$q       = trim((string)($_GET['q'] ?? ''));
$filtre  = trim((string)($_GET['niveau'] ?? ''));
$statut  = trim((string)($_GET['statut'] ?? ''));
$page    = max(1, (int)($_GET['page'] ?? 1));
$limit   = 30;
$offset  = ($page - 1) * $limit;

$where = ['f.statut IN (\'active\',\'inactive\')', 'COALESCE(f.is_samedi_pro,0)=0'];
$bind  = [];

if ($q !== '') {
    $where[] = 'f.titre LIKE :q';
    $bind[':q'] = '%' . $q . '%';
}
if ($filtre !== '') {
    $where[] = 'n.niveau = :niv';
    $bind[':niv'] = $filtre;
}
if ($statut !== '') {
    $where[] = 'n.statut = :stat';
    $bind[':stat'] = $statut;
}

$whereStr = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Compte total pour pagination
$total_q = $pdo->prepare("
    SELECT COUNT(DISTINCT f.id)
    FROM formations f
    LEFT JOIN formation_niveaux n ON n.formation_id = f.id
    $whereStr
");
$total_q->execute($bind);
$total = (int)$total_q->fetchColumn();
$pages = max(1, (int)ceil($total / $limit));

// Données
$stmt = $pdo->prepare("
    SELECT
        f.id, f.titre, f.slug, f.domaine, f.duree,
        f.tarif_en_ligne, f.tarif_presentiel,
        f.statut AS statut_formation,
        n.id        AS niveau_id,
        n.niveau,
        n.duree_heures,
        n.tarif_en_ligne  AS n_tarif_ol,
        n.tarif_presentiel AS n_tarif_pr,
        n.statut    AS statut_niveau,
        n.ordre_affichage,
        (SELECT COUNT(*) FROM formation_niveau_modules m WHERE m.niveau_id = n.id) AS nb_modules
    FROM formations f
    LEFT JOIN formation_niveaux n ON n.formation_id = f.id
    $whereStr
    ORDER BY f.titre ASC, n.ordre_affichage ASC
    LIMIT :lim OFFSET :off
");
foreach ($bind as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
$stmt->bindValue(':off', $offset, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll();

// Grouper par formation
$formations = [];
foreach ($rows as $r) {
    $fid = $r['id'];
    if (!isset($formations[$fid])) {
        $formations[$fid] = [
            'id' => $r['id'], 'titre' => $r['titre'], 'slug' => $r['slug'],
            'domaine' => $r['domaine'], 'duree' => $r['duree'],
            'tarif_en_ligne' => $r['tarif_en_ligne'],
            'tarif_presentiel' => $r['tarif_presentiel'],
            'statut_formation' => $r['statut_formation'],
            'niveaux' => [],
        ];
    }
    if ($r['niveau_id']) {
        $formations[$fid]['niveaux'][$r['niveau']] = $r;
    }
}

// Stats globales
$stats = $pdo->query("
    SELECT niveau, statut, COUNT(*) nb
    FROM formation_niveaux
    GROUP BY niveau, statut
")->fetchAll();
$stat_map = [];
foreach ($stats as $s) $stat_map[$s['niveau']][$s['statut']] = $s['nb'];

function fcfa(int $v): string {
    return number_format($v, 0, ',', ' ') . ' F';
}
function niv_label(string $n): string {
    return ['debutant' => '🟢 Débutant', 'intermediaire' => '🔵 Intermédiaire', 'expert' => '🟣 Expert'][$n] ?? $n;
}
function niv_badge_class(string $n): string {
    return ['debutant' => 'nv-badge nv-deb', 'intermediaire' => 'nv-badge nv-int', 'expert' => 'nv-badge nv-exp'][$n] ?? 'nv-badge';
}
function statut_pill(string $s): string {
    $map = ['actif' => '<span class="pill-on">Actif</span>', 'brouillon' => '<span class="pill-draft">Brouillon</span>', 'archive' => '<span class="pill-off">Archivé</span>'];
    return $map[$s] ?? $s;
}

require_once __DIR__ . '/../layout/header.php';
?>

<style>
.nv-page { padding: 24px; max-width: 1400px; margin: 0 auto; }
.nv-top { display:flex; gap:16px; align-items:center; flex-wrap:wrap; margin-bottom:24px; }
.nv-title { font-size:1.5rem; font-weight:700; color:#0a1733; flex:1; }
.nv-stats { display:flex; gap:12px; flex-wrap:wrap; margin-bottom:20px; }
.nv-stat-card { background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:12px 18px; text-align:center; min-width:120px; }
.nv-stat-card strong { display:block; font-size:1.4rem; font-weight:700; }
.nv-stat-card span { font-size:.75rem; color:#64748b; }
.nv-filters { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:20px; }
.nv-filters input, .nv-filters select { border:1px solid #d1d5db; border-radius:8px; padding:8px 12px; font-size:.875rem; }
.nv-filters input:focus, .nv-filters select:focus { outline:none; border-color:#0a1733; }
.nv-btn { background:#0a1733; color:#fff; border:none; border-radius:8px; padding:8px 16px; cursor:pointer; font-size:.875rem; text-decoration:none; display:inline-flex; align-items:center; gap:6px; }
.nv-btn:hover { background:#1e3a6e; }
.nv-btn-sm { padding:5px 10px; font-size:.8rem; border-radius:6px; }
.nv-btn-green { background:#059669; }
.nv-btn-green:hover { background:#047857; }
.nv-table-wrap { background:#fff; border:1px solid #e2e8f0; border-radius:12px; overflow:hidden; }
.nv-forma-row { border-bottom:2px solid #e2e8f0; }
.nv-forma-head { display:grid; grid-template-columns: 2fr 1fr 140px 80px; gap:12px; padding:14px 16px; background:#f8fafc; align-items:center; }
.nv-forma-name { font-weight:700; color:#0a1733; }
.nv-forma-meta { font-size:.8rem; color:#64748b; }
.nv-niveaux-row { display:grid; grid-template-columns: repeat(3, 1fr); gap:0; border-top:1px solid #e2e8f0; }
.nv-cell { padding:12px 16px; border-right:1px solid #f1f5f9; }
.nv-cell:last-child { border-right:none; }
.nv-badge { display:inline-block; border-radius:20px; padding:2px 10px; font-size:.75rem; font-weight:600; margin-bottom:6px; }
.nv-deb { background:#dcfce7; color:#166534; }
.nv-int { background:#dbeafe; color:#1e40af; }
.nv-exp { background:#ede9fe; color:#6d28d9; }
.nv-prix { font-weight:700; font-size:.9rem; color:#0a1733; }
.nv-duree { font-size:.8rem; color:#64748b; }
.nv-actions { display:flex; gap:6px; margin-top:8px; flex-wrap:wrap; }
.pill-on { background:#dcfce7; color:#166534; border-radius:12px; padding:2px 8px; font-size:.75rem; font-weight:600; }
.pill-draft { background:#fef9c3; color:#854d0e; border-radius:12px; padding:2px 8px; font-size:.75rem; font-weight:600; }
.pill-off { background:#fee2e2; color:#991b1b; border-radius:12px; padding:2px 8px; font-size:.75rem; font-weight:600; }
.nv-empty { color:#94a3b8; font-size:.8rem; font-style:italic; }
.nv-pag { display:flex; gap:8px; justify-content:center; margin-top:20px; flex-wrap:wrap; }
.nv-pag a, .nv-pag span { border:1px solid #e2e8f0; border-radius:6px; padding:6px 12px; font-size:.875rem; text-decoration:none; color:#0a1733; }
.nv-pag .active { background:#0a1733; color:#fff; border-color:#0a1733; }
.nv-no-niveaux { color:#f59e0b; font-size:.8rem; padding:4px; }
</style>

<div class="nv-page">
  <div class="nv-top">
    <div class="nv-title">🎯 Niveaux de formations</div>
    <a href="edit-niveau.php" class="nv-btn">+ Créer un niveau manuellement</a>
  </div>

  <!-- Stats -->
  <div class="nv-stats">
    <?php
    $niv_labels = ['debutant' => '🟢 Débutant', 'intermediaire' => '🔵 Intermédiaire', 'expert' => '🟣 Expert'];
    foreach ($niv_labels as $nk => $nl):
        $actif    = $stat_map[$nk]['actif'] ?? 0;
        $brouillon = $stat_map[$nk]['brouillon'] ?? 0;
    ?>
    <div class="nv-stat-card">
      <strong><?= $actif ?></strong>
      <span><?= $nl ?> actifs</span>
      <?php if ($brouillon): ?>
      <small style="color:#d97706;display:block"><?= $brouillon ?> brouillons</small>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
    <div class="nv-stat-card">
      <strong><?= $total ?></strong>
      <span>Formations</span>
    </div>
  </div>

  <!-- Filtres -->
  <form method="get" class="nv-filters">
    <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Rechercher une formation…">
    <select name="niveau">
      <option value="">Tous les niveaux</option>
      <?php foreach (['debutant' => 'Débutant', 'intermediaire' => 'Intermédiaire', 'expert' => 'Expert'] as $v => $l): ?>
      <option value="<?= $v ?>" <?= $filtre === $v ? 'selected' : '' ?>><?= $l ?></option>
      <?php endforeach; ?>
    </select>
    <select name="statut">
      <option value="">Tous statuts</option>
      <option value="actif" <?= $statut === 'actif' ? 'selected' : '' ?>>Actif</option>
      <option value="brouillon" <?= $statut === 'brouillon' ? 'selected' : '' ?>>Brouillon</option>
    </select>
    <button type="submit" class="nv-btn">🔍 Filtrer</button>
    <?php if ($q || $filtre || $statut): ?>
    <a href="?" class="nv-btn" style="background:#6b7280">✕ Effacer</a>
    <?php endif; ?>
  </form>

  <!-- Tableau -->
  <div class="nv-table-wrap">
    <?php if (empty($formations)): ?>
    <div style="padding:40px;text-align:center;color:#94a3b8">
      Aucune formation trouvée.
      <?php if (!$total): ?>
      <br><a href="../../run-migration-niveaux.php?token=<?= urlencode(defined('ADMIN_SECRET') ? ADMIN_SECRET : 'run') ?>" class="nv-btn" style="margin-top:12px;display:inline-flex">
        ⚡ Lancer la migration initiale
      </a>
      <?php endif; ?>
    </div>
    <?php else: ?>
    <?php foreach ($formations as $f): ?>
    <div class="nv-forma-row">
      <div class="nv-forma-head">
        <div>
          <div class="nv-forma-name"><?= htmlspecialchars($f['titre']) ?></div>
          <div class="nv-forma-meta"><?= htmlspecialchars($f['domaine'] ?? '') ?> — <?= htmlspecialchars($f['duree'] ?? '') ?></div>
        </div>
        <div class="nv-forma-meta">
          Base : <?= fcfa((int)$f['tarif_en_ligne']) ?> en ligne<br>
          <?= fcfa((int)$f['tarif_presentiel']) ?> présentiel
        </div>
        <div>
          <?php if (empty($f['niveaux'])): ?>
          <span class="nv-no-niveaux">⚠ Aucun niveau</span>
          <?php else: ?>
          <span class="pill-on"><?= count($f['niveaux']) ?>/3 niveaux</span>
          <?php endif; ?>
        </div>
        <div>
          <a href="../formations/edit.php?id=<?= $f['id'] ?>" class="nv-btn nv-btn-sm" title="Éditer la formation">✏️</a>
        </div>
      </div>

      <?php if (!empty($f['niveaux'])): ?>
      <div class="nv-niveaux-row">
        <?php foreach (['debutant', 'intermediaire', 'expert'] as $nk): ?>
        <div class="nv-cell">
          <div class="<?= niv_badge_class($nk) ?>"><?= niv_label($nk) ?></div>
          <?php if (isset($f['niveaux'][$nk])): $n = $f['niveaux'][$nk]; ?>
          <div><?= statut_pill($n['statut_niveau']) ?></div>
          <div class="nv-prix" style="margin-top:4px"><?= fcfa((int)$n['n_tarif_ol']) ?> en ligne</div>
          <div class="nv-prix"><?= fcfa((int)$n['n_tarif_pr']) ?> présentiel</div>
          <div class="nv-duree">⏱ <?= $n['duree_heures'] ?>h · 📦 <?= $n['nb_modules'] ?> modules</div>
          <div class="nv-actions">
            <a href="edit-niveau.php?id=<?= $n['niveau_id'] ?>" class="nv-btn nv-btn-sm">✏️ Éditer</a>
            <a href="modules.php?niveau_id=<?= $n['niveau_id'] ?>&titre=<?= urlencode($f['titre']) ?>&niveau=<?= $nk ?>"
               class="nv-btn nv-btn-sm" style="background:#7c3aed">📦 Modules</a>
            <?php if ($n['statut_niveau'] === 'brouillon'): ?>
            <a href="toggle-statut.php?id=<?= $n['niveau_id'] ?>&action=activer&ret=<?= urlencode($_SERVER['REQUEST_URI']) ?>"
               class="nv-btn nv-btn-sm nv-btn-green" onclick="return confirm('Activer ce niveau ?')">▶ Activer</a>
            <?php else: ?>
            <a href="toggle-statut.php?id=<?= $n['niveau_id'] ?>&action=brouillon&ret=<?= urlencode($_SERVER['REQUEST_URI']) ?>"
               class="nv-btn nv-btn-sm" style="background:#f59e0b" onclick="return confirm('Repasser en brouillon ?')">⏸ Brouillon</a>
            <?php endif; ?>
          </div>
          <?php else: ?>
          <div class="nv-empty">Non créé</div>
          <a href="edit-niveau.php?formation_id=<?= $f['id'] ?>&niveau=<?= $nk ?>" class="nv-btn nv-btn-sm" style="margin-top:8px">+ Créer</a>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- Pagination -->
  <?php if ($pages > 1): ?>
  <nav class="nv-pag">
    <?php for ($i = 1; $i <= $pages; $i++):
      $params = array_filter(['q' => $q, 'niveau' => $filtre, 'statut' => $statut, 'page' => $i > 1 ? $i : null]);
      $url = '?' . http_build_query($params);
    ?>
    <?php if ($i === $page): ?>
    <span class="active"><?= $i ?></span>
    <?php else: ?>
    <a href="<?= htmlspecialchars($url) ?>"><?= $i ?></a>
    <?php endif; ?>
    <?php endfor; ?>
  </nav>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
