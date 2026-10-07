<?php
declare(strict_types=1);
/**
 * ADMIN — Seeder objectifs/prérequis/public_cible via tdr_objectifs_locaux()
 * Génère et sauvegarde ces champs pour tous les niveaux actifs,
 * en utilisant le générateur LOCAL (sans API externe, sans frais).
 */
require_once __DIR__ . '/../_init.php';
Middleware::requireAuth();

$pdo = Database::connect();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$pageTitle  = "Seeder contenu (local)";
$activeMenu = "niveaux";

require_once __DIR__ . '/../../core/tdr_generator.php';

$flash   = null;
$results = [];

/* ── POST : lancer le seeding ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $mode    = trim((string)($_POST['mode'] ?? 'empty_only'));
    $niv_cib = trim((string)($_POST['niveau_cible'] ?? ''));

    $where  = ["n.statut = 'actif'"];
    $params = [];
    if ($mode === 'empty_only') {
        $where[] = "(n.objectifs IS NULL OR n.objectifs = '')";
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
        WHERE " . implode(' AND ', $where) . "
        ORDER BY f.titre ASC, n.niveau ASC
        LIMIT 10000
    ");
    $rows->execute($params);
    $niveaux = $rows->fetchAll(PDO::FETCH_ASSOC);

    $upd = $pdo->prepare("
        UPDATE formation_niveaux
           SET objectifs    = :obj,
               prerequis    = :pre,
               public_cible = :pub,
               updated_at   = NOW()
         WHERE id = :id
    ");

    $nb_ok = 0; $nb_err = 0;

    foreach ($niveaux as $n) {
        $nid = (int)$n['id'];
        try {
            $data = tdr_objectifs_locaux(
                (string)$n['titre'],
                (string)($n['domaine'] ?? ''),
                max(1, (int)$n['duree_heures']),
                (string)($n['description'] ?? ''),
                (string)($n['niveau'] ?? 'intermediaire')
            );
            $upd->execute([
                ':obj' => mb_substr(trim($data['objectifs']    ?? ''), 0, 1000),
                ':pre' => mb_substr(trim($data['prerequis']    ?? ''), 0, 500),
                ':pub' => mb_substr(trim($data['public_cible'] ?? ''), 0, 500),
                ':id'  => $nid,
            ]);
            $results[] = ['ok',  $n['titre'] . ' [' . $n['niveau'] . ']', 'Contenu généré'];
            $nb_ok++;
        } catch (Throwable $e) {
            $results[] = ['err', $n['titre'] . ' [' . $n['niveau'] . ']', 'DB : ' . $e->getMessage()];
            $nb_err++;
        }
    }

    $flash = $nb_err === 0
        ? ['ok',   "✅ $nb_ok niveaux traités — objectifs / prérequis / public cible générés localement."]
        : ['warn', "⚠️ $nb_ok ok · $nb_err erreurs."];
}

/* ── Stats ── */
$stats = $pdo->query("
    SELECT n.niveau,
           COUNT(n.id)                                                              AS total_actifs,
           SUM(CASE WHEN n.objectifs IS NULL OR n.objectifs = '' THEN 1 ELSE 0 END) AS sans_contenu,
           SUM(CASE WHEN n.objectifs IS NOT NULL AND n.objectifs <> '' THEN 1 ELSE 0 END) AS avec_contenu
    FROM formation_niveaux n
    WHERE n.statut = 'actif'
    GROUP BY n.niveau
    ORDER BY CASE n.niveau WHEN 'debutant' THEN 1 WHEN 'intermediaire' THEN 2 WHEN 'expert' THEN 3 END
")->fetchAll(PDO::FETCH_ASSOC);

$niv_labels = ['debutant'=>'Débutant','intermediaire'=>'Intermédiaire','expert'=>'Expert'];
$csrf = csrf_token();

ob_start();
?>
<style>
.sc-wrap{max-width:900px}
.breadcrumb{font-size:.78rem;color:#64748b;margin-bottom:10px}
.breadcrumb a{color:#1e40af;text-decoration:none}
.sc-wrap h2{font-size:17px;font-weight:700;margin:0 0 4px}
.sc-wrap .sub{font-size:.78rem;color:#64748b;margin-bottom:18px}
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
.btn-seed{padding:8px 20px;background:#7c3aed;color:#fff;border:none;border-radius:6px;font-size:.85rem;font-weight:700;cursor:pointer}
.btn-seed:hover{background:#6d28d9}
.res-table{width:100%;border-collapse:collapse;font-size:.78rem;margin-top:14px}
.res-table th{background:#0a1733;color:#e8edf8;padding:6px 10px;text-align:left;font-size:.73rem}
.res-table td{padding:5px 10px;border-bottom:1px solid #f1f5f9}
.res-ok{color:#166534;font-weight:700}
.res-err{color:#991b1b;font-weight:700}
.badge-local{display:inline-block;background:#f3e8ff;color:#6d28d9;border:1px solid #c4b5fd;border-radius:999px;font-size:.68rem;font-weight:700;padding:2px 10px;margin-left:8px}
</style>

<div class="sc-wrap">

  <div class="breadcrumb"><a href="index.php">← Niveaux</a> / Seeder contenu (local)</div>

  <h2>🎯 Seeder objectifs / prérequis / public cible <span class="badge-local">LOCAL — sans API</span></h2>
  <div class="sub">Génère les champs pédagogiques (objectifs, prérequis, public cible) pour chaque niveau à partir du moteur local, sans aucun appel API. Gratuit, instantané, différencié par niveau et par domaine.</div>

  <?php if ($flash): ?>
    <div class="flash-<?= $flash[0] ?>"><?= e($flash[1]) ?></div>
  <?php endif; ?>

  <!-- Stats -->
  <div class="stats-row">
    <?php foreach ($stats as $s): ?>
    <div class="stat-card">
      <div class="nb" style="color:<?= ['debutant'=>'#166534','intermediaire'=>'#1e40af','expert'=>'#9d174d'][$s['niveau']] ?>"><?= (int)$s['total_actifs'] ?></div>
      <div class="lbl"><?= $niv_labels[$s['niveau']] ?></div>
      <div class="sub2"><?= (int)$s['avec_contenu'] ?> avec contenu · <?= (int)$s['sans_contenu'] ?> sans</div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Formulaire -->
  <div class="form-card">
    <h3>⚡ Lancer le seeding (sans API)</h3>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
      <div class="form-row">
        <div>
          <label>Mode</label>
          <select name="mode">
            <option value="empty_only">Sans contenu seulement (recommandé)</option>
            <option value="all">Tous (écraser le contenu existant)</option>
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
                  onclick="return confirm('Lancer la génération locale du contenu pédagogique ?')">
            ⚡ Lancer le seeding
          </button>
        </div>
      </div>
      <p style="font-size:.75rem;color:#64748b;margin:0">Le moteur tdr_objectifs_locaux() détecte automatiquement le domaine (RH, Finance, Marketing, IT, BTP, Agriculture…) et génère un contenu différencié selon le niveau. Aucune API, aucun crédit consommé.</p>
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
