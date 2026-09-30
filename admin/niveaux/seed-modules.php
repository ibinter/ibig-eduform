<?php
declare(strict_types=1);
/**
 * ADMIN — Seeder de modules via tdr_modules()
 * Génère et sauvegarde les modules de tous les niveaux actifs sans modules,
 * en utilisant le générateur local (sans API externe).
 */
require_once __DIR__ . '/../_init.php';
Middleware::requireAuth();

$pdo = Database::connect();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$pageTitle  = "Seeder modules (tdr_modules)";
$activeMenu = "niveaux";

require_once __DIR__ . '/../../core/tdr_generator.php';

$flash = null;
$results = [];

/* ── POST : lancer le seeding ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $mode    = trim((string)($_POST['mode'] ?? 'empty_only'));
    $niv_cib = trim((string)($_POST['niveau_cible'] ?? ''));

    /* Construire la requête */
    $where = ["n.statut = 'actif'"];
    $params = [];
    if ($mode === 'empty_only') {
        $where[] = "(mc.nb IS NULL OR mc.nb = 0)";
    }
    if (in_array($niv_cib, ['debutant','intermediaire','expert'], true)) {
        $where[] = "n.niveau = :niv";
        $params[':niv'] = $niv_cib;
    }

    $rows = $pdo->prepare("
        SELECT n.id, n.niveau, n.duree_heures,
               f.titre, f.domaine, f.description
        FROM formation_niveaux n
        JOIN formations f ON f.id = n.formation_id
        LEFT JOIN (SELECT niveau_id, COUNT(*) AS nb FROM formation_niveau_modules GROUP BY niveau_id) mc
               ON mc.niveau_id = n.id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY f.titre ASC, n.niveau ASC
        LIMIT 10000
    ");
    $rows->execute($params);
    $niveaux = $rows->fetchAll(PDO::FETCH_ASSOC);

    $ins = $pdo->prepare("INSERT INTO formation_niveau_modules (niveau_id, ordre, titre, contenus, duree_heures) VALUES (:nid, :ord, :t, :c, :d)");
    $del = $pdo->prepare("DELETE FROM formation_niveau_modules WHERE niveau_id = :nid");

    $nb_ok = 0; $nb_err = 0;
    $ids_synced = [];

    $syncDuree = $pdo->prepare("
        UPDATE formation_niveaux
           SET duree_heures = (
               SELECT COALESCE(SUM(duree_heures), 20)
               FROM formation_niveau_modules
               WHERE niveau_id = :nid1
           )
         WHERE id = :nid2
    ");

    foreach ($niveaux as $n) {
        $nid   = (int)$n['id'];
        $duree = max(1, (int)$n['duree_heures']); // hint only — tdr_modules() overrides via lookup
        $modules = tdr_modules(
            (string)$n['titre'],
            (string)($n['domaine'] ?? ''),
            $duree,
            (string)($n['description'] ?? ''),
            (string)($n['niveau'] ?? 'debutant')
        );
        if (empty($modules)) {
            $results[] = ['err', $n['titre'] . ' [' . $n['niveau'] . ']', 'Aucun module généré'];
            $nb_err++;
            continue;
        }
        try {
            $pdo->beginTransaction();
            $del->execute([':nid' => $nid]);
            foreach ($modules as $i => $m) {
                $ins->execute([
                    ':nid' => $nid,
                    ':ord' => $i + 1,
                    ':t'   => mb_substr((string)($m['titre'] ?? ''), 0, 255),
                    ':c'   => mb_substr((string)($m['contenus'] ?? ''), 0, 1000),
                    ':d'   => max(1, min(40, (int)($m['duree'] ?? $m['duree_heures'] ?? 2))),
                ]);
            }
            $pdo->commit();
            // Synchroniser duree_heures du niveau = somme des heures de ses modules
            $syncDuree->execute([':nid1' => $nid, ':nid2' => $nid]);
            $ids_synced[] = $nid;
            $results[] = ['ok', $n['titre'] . ' [' . $n['niveau'] . ']', count($modules) . ' modules'];
            $nb_ok++;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $results[] = ['err', $n['titre'] . ' [' . $n['niveau'] . ']', 'DB : ' . $e->getMessage()];
            $nb_err++;
        }
    }

    $flash = $nb_err === 0
        ? ['ok', "✅ $nb_ok niveaux traités — heures synchronisées depuis les modules."]
        : ['warn', "⚠️ $nb_ok ok · $nb_err erreurs."];
}

/* ── Stats ── */
$stats = $pdo->query("
    SELECT n.niveau,
           COUNT(n.id)                                                       AS total_actifs,
           SUM(CASE WHEN mc.nb IS NULL OR mc.nb = 0 THEN 1 ELSE 0 END)     AS sans_modules,
           SUM(CASE WHEN mc.nb > 0 THEN 1 ELSE 0 END)                       AS avec_modules
    FROM formation_niveaux n
    LEFT JOIN (SELECT niveau_id, COUNT(*) AS nb FROM formation_niveau_modules GROUP BY niveau_id) mc
           ON mc.niveau_id = n.id
    WHERE n.statut = 'actif'
    GROUP BY n.niveau
    ORDER BY CASE n.niveau WHEN 'debutant' THEN 1 WHEN 'intermediaire' THEN 2 WHEN 'expert' THEN 3 END
")->fetchAll(PDO::FETCH_ASSOC);

$niv_labels = ['debutant'=>'Débutant','intermediaire'=>'Intermédiaire','expert'=>'Expert'];
$csrf = csrf_token();

ob_start();
?>
<style>
.seed-wrap{max-width:900px}
.breadcrumb{font-size:.78rem;color:#64748b;margin-bottom:10px}
.breadcrumb a{color:#1e40af;text-decoration:none}
.seed-wrap h2{font-size:17px;font-weight:700;margin:0 0 4px}
.seed-wrap .sub{font-size:.78rem;color:#64748b;margin-bottom:18px}
.flash-ok{background:#dcfce7;color:#166534;border:1px solid #86efac;border-radius:7px;padding:9px 16px;font-size:.82rem;margin-bottom:14px;font-weight:700}
.flash-warn{background:#fef9c3;color:#713f12;border:1px solid #fde047;border-radius:7px;padding:9px 16px;font-size:.82rem;margin-bottom:14px;font-weight:700}
.stats-row{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:18px}
.stat-card{background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:10px 18px;text-align:center;min-width:110px}
.stat-card .nb{font-size:1.4rem;font-weight:800;color:#1e40af}
.stat-card .lbl{font-size:.7rem;color:#64748b;font-weight:600;text-transform:uppercase}
.stat-card .sub2{font-size:.65rem;color:#94a3b8}
.form-card{background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:18px;margin-bottom:18px}
.form-card h3{font-size:.9rem;font-weight:700;margin:0 0 12px}
.form-row{display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;margin-bottom:12px}
.form-row label{font-size:.8rem;font-weight:600;color:#374151;display:block;margin-bottom:4px}
.form-row select{padding:6px 10px;border:1px solid #e2e8f0;border-radius:6px;font-size:.82rem;background:#fff}
.btn-seed{padding:8px 20px;background:#166534;color:#fff;border:none;border-radius:6px;font-size:.85rem;font-weight:700;cursor:pointer}
.btn-seed:hover{background:#14532d}
.res-table{width:100%;border-collapse:collapse;font-size:.78rem;margin-top:14px}
.res-table th{background:#0a1733;color:#e8edf8;padding:6px 10px;text-align:left;font-size:.73rem}
.res-table td{padding:5px 10px;border-bottom:1px solid #f1f5f9}
.res-ok{color:#166534;font-weight:700}
.res-err{color:#991b1b;font-weight:700}
</style>

<div class="seed-wrap">

  <div class="breadcrumb"><a href="index.php">← Niveaux</a> / Seeder modules</div>

  <h2>⚡ Seeder modules (tdr_modules — sans API)</h2>
  <div class="sub">Génère les modules de formation à partir du moteur de contenu local, sans appel API externe. Rapide, gratuit, immédiat.</div>

  <?php if ($flash): ?>
    <div class="flash-<?= $flash[0] ?>"><?= e($flash[1]) ?></div>
  <?php endif; ?>

  <!-- Stats -->
  <div class="stats-row">
    <?php foreach ($stats as $s): ?>
    <div class="stat-card">
      <div class="nb" style="color:<?= ['debutant'=>'#166534','intermediaire'=>'#1e40af','expert'=>'#9d174d'][$s['niveau']] ?>"><?= (int)$s['sans_modules'] ?></div>
      <div class="lbl"><?= $niv_labels[$s['niveau']] ?></div>
      <div class="sub2">sans modules / <?= (int)$s['total_actifs'] ?> actifs</div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Formulaire -->
  <div class="form-card">
    <h3>🚀 Lancer le seeding</h3>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
      <div class="form-row">
        <div>
          <label>Mode</label>
          <select name="mode">
            <option value="empty_only">Sans modules seulement (recommandé)</option>
            <option value="all">Tous (écraser les modules existants)</option>
          </select>
        </div>
        <div>
          <label>Niveau cible</label>
          <select name="niveau_cible">
            <option value="">Tous les niveaux</option>
            <option value="debutant">Débutant uniquement</option>
            <option value="intermediaire">Intermédiaire uniquement</option>
            <option value="expert">Expert uniquement</option>
          </select>
        </div>
        <div>
          <button type="submit" class="btn-seed"
                  onclick="return confirm('Lancer le seeding des modules ? Cette opération peut prendre quelques secondes.')">
            ⚡ Lancer le seeding
          </button>
        </div>
      </div>
      <p style="font-size:.75rem;color:#64748b;margin:0">Le moteur tdr_modules() sélectionne automatiquement le contenu adapté selon le titre et le domaine de chaque formation. Les formations sans correspondance reçoivent un plan générique.</p>
    </form>
  </div>

  <!-- Résultats -->
  <?php if (!empty($results)): ?>
  <div style="overflow-x:auto">
  <table class="res-table">
    <thead><tr><th>#</th><th>Formation [niveau]</th><th>Résultat</th></tr></thead>
    <tbody>
    <?php foreach ($results as $i => $r): ?>
    <tr>
      <td style="color:#94a3b8"><?= $i + 1 ?></td>
      <td><?= e($r[1]) ?></td>
      <td class="res-<?= $r[0] ?>"><?= e($r[2]) ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <?php endif; ?>

</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout/layout.php';
