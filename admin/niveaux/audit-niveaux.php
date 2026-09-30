<?php
declare(strict_types=1);
/**
 * ADMIN — Audit des niveaux de formation
 * Identifie les formations dont les niveaux assignés ne sont pas cohérents
 * avec le titre/domaine, et propose des corrections.
 */
require_once __DIR__ . '/../_init.php';
Middleware::requireAuth();

$pdo = Database::connect();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$pageTitle  = "Audit niveaux formations";
$activeMenu = "niveaux";

/* ── Règles d'inférence de niveau attendu ── */
function niveaux_attendus(string $titre, string $domaine): array
{
    $t = mb_strtolower($titre, 'UTF-8');
    $d = mb_strtolower($domaine, 'UTF-8');

    /* Mots-clés qui indiquent UNIQUEMENT débutant */
    $debutant_seul = [
        'initiation', 'introduction', 'découverte', 'notions de base',
        'démarrer', 'premiers pas', 'je débute', 'débutant',
        'bases de', 'les bases', 'fondamentaux pour', 'essentiel pour',
    ];
    foreach ($debutant_seul as $kw) {
        if (str_contains($t, $kw)) return ['debutant'];
    }

    /* Mots-clés qui indiquent UNIQUEMENT expert */
    $expert_seul = [
        'chief ', 'cfo ', 'ceo ', 'daf ', 'dg ', 'directeur',
        'master ', 'mastère', 'mba ', 'expert ', ' expert',
        'spécialiste avancé', 'haut niveau', 'confirmé',
        'advanced ', 'senior ', 'certification professionnelle avancée',
        'strategic ', 'stratégique avancé',
    ];
    foreach ($expert_seul as $kw) {
        if (str_contains($t, $kw)) return ['expert'];
    }

    /* Mots-clés qui indiquent débutant + intermédiaire (pas expert) */
    $deb_inter = [
        'pour les nuls', 'pour non-spécialistes', 'pour non-financiers',
        'pour non-comptables', 'pour non-juristes', 'pour managers',
        'pour entrepreneurs', 'pour responsables', 'pour chefs de projet',
        'prise en main', 'prise de fonction',
    ];
    foreach ($deb_inter as $kw) {
        if (str_contains($t, $kw)) return ['debutant', 'intermediaire'];
    }

    /* Mots-clés qui indiquent intermédiaire + expert uniquement */
    $inter_exp = [
        'perfectionnement', 'approfondissement', 'maîtriser', 'maîtrise',
        'approfondi', 'avancé', 'niveau avancé', 'management avancé',
        'technique avancée', 'audit avancé', 'optimisation',
    ];
    foreach ($inter_exp as $kw) {
        if (str_contains($t, $kw)) return ['intermediaire', 'expert'];
    }

    /* Par défaut : les 3 niveaux sont appropriés */
    return ['debutant', 'intermediaire', 'expert'];
}

/* ── Chargement des formations avec leurs niveaux actifs ── */
$formations = $pdo->query("
    SELECT f.id, f.titre, f.domaine, f.slug,
           GROUP_CONCAT(n.niveau ORDER BY n.ordre_affichage SEPARATOR ',') AS niveaux_actifs,
           COUNT(n.id) AS nb_niv
    FROM formations f
    LEFT JOIN formation_niveaux n ON n.formation_id = f.id AND n.statut = 'actif'
    WHERE f.statut IN ('active','inactive') AND COALESCE(f.is_samedi_pro,0) = 0
    GROUP BY f.id, f.titre, f.domaine, f.slug
    ORDER BY f.titre ASC
")->fetchAll(PDO::FETCH_ASSOC);

/* ── Analyse : comparer niveaux réels vs attendus ── */
$anomalies   = [];
$ok          = 0;
$sans_niveau = 0;

$ALL_NIVEAUX = ['debutant', 'intermediaire', 'expert'];

foreach ($formations as $f) {
    $actifs   = $f['niveaux_actifs'] ? explode(',', $f['niveaux_actifs']) : [];
    $attendus = niveaux_attendus($f['titre'], $f['domaine'] ?? '');

    $manquants  = array_values(array_diff($attendus, $actifs));
    $en_trop    = array_values(array_diff($actifs, $attendus));
    $sans_aucun = empty($actifs);

    if ($sans_aucun) {
        $sans_niveau++;
        $anomalies[] = ['type' => 'sans_niveau', 'f' => $f, 'attendus' => $attendus, 'manquants' => [], 'en_trop' => []];
    } elseif (!empty($en_trop) || !empty($manquants)) {
        $anomalies[] = ['type' => 'desequilibre', 'f' => $f, 'actifs' => $actifs, 'attendus' => $attendus, 'manquants' => $manquants, 'en_trop' => $en_trop];
    } else {
        $ok++;
    }
}

/* ── Statistiques globales ── */
$stats_dist = $pdo->query("
    SELECT nb_niv, COUNT(*) AS nb_formations FROM (
        SELECT f.id, COUNT(n.id) AS nb_niv
        FROM formations f
        LEFT JOIN formation_niveaux n ON n.formation_id = f.id AND n.statut = 'actif'
        WHERE f.statut IN ('active','inactive') AND COALESCE(f.is_samedi_pro,0) = 0
        GROUP BY f.id
    ) t GROUP BY nb_niv ORDER BY nb_niv ASC
")->fetchAll(PDO::FETCH_ASSOC);

$niv_labels = ['debutant' => 'Débutant', 'intermediaire' => 'Intermédiaire', 'expert' => 'Expert'];
$niv_colors = ['debutant' => '#166534', 'intermediaire' => '#1e40af', 'expert' => '#9d174d'];
$niv_bg     = ['debutant' => '#dcfce7', 'intermediaire' => '#dbeafe', 'expert' => '#fce7f3'];

ob_start();
?>
<style>
.aud-wrap{max-width:1100px}
.aud-wrap h2{font-size:17px;font-weight:800;margin:0 0 4px}
.aud-sub{font-size:.78rem;color:#64748b;margin-bottom:18px}
.breadcrumb{font-size:.78rem;color:#64748b;margin-bottom:10px}
.breadcrumb a{color:#1e40af;text-decoration:none}
.stat-row{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:20px}
.stat-card{background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:12px 20px;text-align:center;min-width:110px}
.stat-card .nb{font-size:1.5rem;font-weight:800}
.stat-card .lbl{font-size:.7rem;color:#64748b;font-weight:600;text-transform:uppercase}
.section-title{font-size:.88rem;font-weight:800;color:#0a1733;margin:24px 0 10px;display:flex;align-items:center;gap:8px}
.anomaly-table{width:100%;border-collapse:collapse;font-size:.78rem;background:#fff;border:1px solid #e2e8f0;border-radius:10px;overflow:hidden}
.anomaly-table th{background:#0a1733;color:#e8edf8;padding:8px 12px;text-align:left;font-size:.73rem;font-weight:700}
.anomaly-table td{padding:8px 12px;border-bottom:1px solid #f1f5f9;vertical-align:top}
.anomaly-table tr:last-child td{border-bottom:none}
.anomaly-table tr:hover td{background:#f8fafc}
.niv-pill{display:inline-block;padding:2px 8px;border-radius:999px;font-size:.68rem;font-weight:700;margin:1px}
.niv-ok{background:#dcfce7;color:#166534}
.niv-trop{background:#fee2e2;color:#991b1b;text-decoration:line-through}
.niv-manq{background:#fef9c3;color:#713f12;border:1px dashed #fde047}
.badge-type{display:inline-block;padding:2px 8px;border-radius:6px;font-size:.68rem;font-weight:700}
.badge-deseq{background:#fef9c3;color:#713f12}
.badge-sans{background:#fee2e2;color:#991b1b}
.badge-ok{background:#dcfce7;color:#166534}
.dist-bar{display:flex;gap:8px;align-items:center;margin-bottom:6px;font-size:.8rem}
.dist-fill{height:18px;border-radius:4px;background:#e2e8f0;overflow:hidden}
.dist-fill-inner{height:100%;background:#1e40af}
.info-box{background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:12px 16px;font-size:.8rem;color:#1e3a8a;margin-bottom:18px;line-height:1.6}
.info-box strong{display:block;font-weight:700;margin-bottom:4px}
</style>

<div class="aud-wrap">

  <div class="breadcrumb"><a href="index.php">← Niveaux</a> / Audit des niveaux</div>

  <h2>🔍 Audit des niveaux de formation</h2>
  <div class="aud-sub">Détecte les formations avec des niveaux incohérents (trop de niveaux ou niveaux manquants) selon les mots-clés du titre.</div>

  <div class="info-box">
    <strong>Comment fonctionne l'analyse ?</strong>
    Le système examine le titre de chaque formation et déduit les niveaux logiquement attendus :<br>
    • "Introduction / Initiation / Découverte" → <strong>Débutant uniquement</strong><br>
    • "Expert / Chief / Directeur / MBA / Maîtrise" → <strong>Expert uniquement</strong><br>
    • "Pour non-spécialistes / Pour managers / Prise en main" → <strong>Débutant + Intermédiaire</strong><br>
    • "Perfectionnement / Avancé / Approfondi" → <strong>Intermédiaire + Expert</strong><br>
    • Tout autre titre → <strong>3 niveaux (normal)</strong><br><br>
    ⚠️ Ce sont des <em>suggestions</em> — vous validez les corrections manuellement depuis la page d'édition.
  </div>

  <!-- Statistiques -->
  <div class="stat-row">
    <div class="stat-card">
      <div class="nb" style="color:#0a1733"><?= count($formations) ?></div>
      <div class="lbl">Formations total</div>
    </div>
    <div class="stat-card">
      <div class="nb" style="color:#166534"><?= $ok ?></div>
      <div class="lbl">Niveaux cohérents ✅</div>
    </div>
    <div class="stat-card">
      <div class="nb" style="color:#b45309"><?= count(array_filter($anomalies, fn($a) => $a['type']==='desequilibre')) ?></div>
      <div class="lbl">Déséquilibrés ⚠️</div>
    </div>
    <div class="stat-card">
      <div class="nb" style="color:#dc2626"><?= $sans_niveau ?></div>
      <div class="lbl">Sans niveau ❌</div>
    </div>
  </div>

  <!-- Distribution -->
  <div class="section-title">📊 Distribution</div>
  <?php
  $total_f = count($formations);
  foreach ($stats_dist as $sd):
      $pct = $total_f > 0 ? round((int)$sd['nb_formations'] / $total_f * 100) : 0;
  ?>
  <div class="dist-bar">
    <span style="width:80px;color:#64748b"><?= (int)$sd['nb_niv'] ?> niveau(x)</span>
    <div class="dist-fill" style="width:200px"><div class="dist-fill-inner" style="width:<?= $pct ?>%"></div></div>
    <span style="color:#0a1733;font-weight:700"><?= $sd['nb_formations'] ?> formations</span>
    <span style="color:#94a3b8">(<?= $pct ?>%)</span>
  </div>
  <?php endforeach; ?>

  <!-- Anomalies -->
  <?php if (!empty($anomalies)): ?>
  <div class="section-title">⚠️ Formations avec niveaux à revoir (<?= count($anomalies) ?>)</div>
  <div style="overflow-x:auto">
  <table class="anomaly-table">
    <thead>
      <tr>
        <th>#</th>
        <th>Formation</th>
        <th>Domaine</th>
        <th>Niveaux actuels</th>
        <th>Niveaux suggérés</th>
        <th>Problème détecté</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($anomalies as $i => $a):
        $f = $a['f'];
        $actifs  = $a['actifs'] ?? [];
        $attendus = $a['attendus'];
        $manquants = $a['manquants'] ?? [];
        $en_trop   = $a['en_trop'] ?? [];
    ?>
    <tr>
      <td style="color:#94a3b8"><?= $i + 1 ?></td>
      <td>
        <a href="/formation-detail.php?slug=<?= e($f['slug']) ?>" target="_blank" style="color:#1e40af;text-decoration:none;font-weight:600">
          <?= e($f['titre']) ?>
        </a>
      </td>
      <td style="color:#64748b;font-size:.73rem"><?= e($f['domaine'] ?? '—') ?></td>
      <td>
        <?php if ($a['type'] === 'sans_niveau'): ?>
          <span style="color:#dc2626;font-weight:700">Aucun</span>
        <?php else: ?>
          <?php foreach ($actifs as $nv):
              $isTrop = in_array($nv, $en_trop);
          ?>
          <span class="niv-pill <?= $isTrop ? 'niv-trop' : 'niv-ok' ?>"><?= $niv_labels[$nv] ?? $nv ?></span>
          <?php endforeach; ?>
        <?php endif; ?>
      </td>
      <td>
        <?php foreach ($attendus as $nv): ?>
        <span class="niv-pill niv-ok"><?= $niv_labels[$nv] ?? $nv ?></span>
        <?php endforeach; ?>
      </td>
      <td>
        <?php if ($a['type'] === 'sans_niveau'): ?>
          <span class="badge-type badge-sans">❌ Aucun niveau</span>
        <?php else: ?>
          <?php if (!empty($en_trop)): ?>
            <span class="badge-type badge-deseq">➖ <?= count($en_trop) ?> niveau(x) en trop</span><br>
          <?php endif; ?>
          <?php if (!empty($manquants)): ?>
            <span class="badge-type" style="background:#fef9c3;color:#92400e">➕ <?= count($manquants) ?> niveau(x) manquant(s)</span>
          <?php endif; ?>
        <?php endif; ?>
      </td>
      <td>
        <a href="edit.php?formation_id=<?= (int)$f['id'] ?>"
           style="display:inline-block;padding:4px 10px;background:#1e40af;color:#fff;border-radius:5px;font-size:.72rem;font-weight:700;text-decoration:none">
          ✏️ Éditer
        </a>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>

  <?php else: ?>
  <div style="background:#dcfce7;border:1px solid #86efac;border-radius:8px;padding:14px 18px;color:#166534;font-weight:700;font-size:.88rem">
    ✅ Toutes les formations ont des niveaux cohérents avec leur titre.
  </div>
  <?php endif; ?>

  <!-- Formations OK -->
  <div class="section-title">✅ Formations avec niveaux cohérents (<?= $ok ?>)</div>
  <p style="font-size:.78rem;color:#64748b">Ces <?= $ok ?> formations ont exactement les niveaux attendus selon leur titre — aucune action requise.</p>

</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout/layout.php';
