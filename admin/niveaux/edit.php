<?php
declare(strict_types=1);

require_once __DIR__ . '/../_init.php';

Middleware::requireAuth();
$u = auth_user();

$pdo = Database::connect();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

/* ── Formation ── */
$fid = (int)($_GET['id'] ?? 0);
if ($fid <= 0) { header('Location: index.php'); exit; }

$formation = $pdo->prepare("SELECT id, titre, slug, domaine, duree, tarif_en_ligne, tarif_presentiel FROM formations WHERE id = ? LIMIT 1");
$formation->execute([$fid]);
$formation = $formation->fetch(PDO::FETCH_ASSOC);
if (!$formation) { header('Location: index.php'); exit; }

$pageTitle  = "Niveaux — " . $formation['titre'];
$activeMenu = "niveaux";

/* ── Niveaux de cette formation ── */
$niv_stmt = $pdo->prepare("SELECT * FROM formation_niveaux WHERE formation_id = ? ORDER BY ordre_affichage ASC");
$niv_stmt->execute([$fid]);
$niveaux = $niv_stmt->fetchAll(PDO::FETCH_ASSOC);
$niveaux_map = [];
foreach ($niveaux as $n) $niveaux_map[$n['niveau']] = $n;

/* ── POST : sauvegarde ── */
$flash = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $niveau = trim((string)($_POST['niveau'] ?? ''));
    if (!in_array($niveau, ['debutant','intermediaire','expert'], true)) {
        $flash = ['err', 'Niveau invalide.'];
    } else {
        $duree  = max(1, (int)($_POST['duree_heures'] ?? 20));
        $t_ol   = max(0, (int)($_POST['tarif_en_ligne'] ?? 0));
        $t_pr   = max(0, (int)($_POST['tarif_presentiel'] ?? 0));
        $t_hy   = max(0, (int)($_POST['tarif_hybride'] ?? 0));
        $statut = in_array($_POST['statut'] ?? '', ['actif','brouillon','archive'], true) ? $_POST['statut'] : 'brouillon';
        $obj    = trim((string)($_POST['objectifs'] ?? ''));
        $pre    = trim((string)($_POST['prerequis'] ?? ''));
        $pub    = trim((string)($_POST['public_cible'] ?? ''));

        $ordre  = ['debutant'=>1,'intermediaire'=>2,'expert'=>3][$niveau];

        $existing = $niveaux_map[$niveau] ?? null;
        if ($existing) {
            $pdo->prepare("
                UPDATE formation_niveaux SET
                    duree_heures=:dh, tarif_en_ligne=:ol, tarif_presentiel=:pr, tarif_hybride=:hy,
                    statut=:st, objectifs=:obj, prerequis=:pre, public_cible=:pub, ordre_affichage=:ord
                WHERE id=:id
            ")->execute([':dh'=>$duree,':ol'=>$t_ol,':pr'=>$t_pr,':hy'=>$t_hy,':st'=>$statut,
                         ':obj'=>$obj ?: null,':pre'=>$pre ?: null,':pub'=>$pub ?: null,':ord'=>$ordre,
                         ':id'=>(int)$existing['id']]);
        } else {
            $pdo->prepare("
                INSERT INTO formation_niveaux
                    (formation_id,niveau,duree_heures,tarif_en_ligne,tarif_presentiel,tarif_hybride,
                     statut,objectifs,prerequis,public_cible,ordre_affichage)
                VALUES(:fid,:niv,:dh,:ol,:pr,:hy,:st,:obj,:pre,:pub,:ord)
            ")->execute([':fid'=>$fid,':niv'=>$niveau,':dh'=>$duree,':ol'=>$t_ol,':pr'=>$t_pr,':hy'=>$t_hy,
                         ':st'=>$statut,':obj'=>$obj ?: null,':pre'=>$pre ?: null,':pub'=>$pub ?: null,':ord'=>$ordre]);
        }
        // Recharger niveaux
        $niv_stmt2 = $pdo->prepare("SELECT * FROM formation_niveaux WHERE formation_id = ? ORDER BY ordre_affichage ASC");
        $niv_stmt2->execute([$fid]);
        $niveaux = $niv_stmt2->fetchAll(PDO::FETCH_ASSOC);
        $niveaux_map = [];
        foreach ($niveaux as $n) $niveaux_map[$n['niveau']] = $n;
        $flash = ['ok', 'Niveau "' . $niveau . '" enregistré.'];
    }
}

$niv_labels  = ['debutant'=>'Débutant','intermediaire'=>'Intermédiaire','expert'=>'Expert'];
$niv_classes = ['debutant'=>'niv-d','intermediaire'=>'niv-i','expert'=>'niv-e'];

ob_start();
?>
<style>
.niv-edit-wrap{max-width:960px}
.niv-edit-wrap h2{font-size:17px;font-weight:700;margin:0 0 4px}
.niv-edit-wrap .sub{font-size:.78rem;color:#64748b;margin-bottom:18px}
.niv-tabs{display:flex;gap:8px;margin-bottom:18px;border-bottom:2px solid #e2e8f0;padding-bottom:0}
.niv-tab-btn{padding:7px 20px;border:none;border-radius:8px 8px 0 0;font-size:.82rem;font-weight:700;cursor:pointer;background:#f1f5f9;color:#475569;border-bottom:2px solid transparent;margin-bottom:-2px}
.niv-tab-btn.active-d{background:#dcfce7;color:#166534;border-bottom-color:#16a34a}
.niv-tab-btn.active-i{background:#dbeafe;color:#1e40af;border-bottom-color:#2563eb}
.niv-tab-btn.active-e{background:#fce7f3;color:#9d174d;border-bottom-color:#db2777}
.niv-panel{display:none}.niv-panel.active{display:block}
.form-grid{display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-bottom:16px}
.form-group{display:flex;flex-direction:column;gap:4px}
.form-group label{font-size:.76rem;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.04em}
.form-group input,.form-group select,.form-group textarea{padding:7px 10px;border:1px solid #e2e8f0;border-radius:6px;font-size:.83rem;color:#0f172a;background:#fff}
.form-group textarea{resize:vertical;min-height:72px}
.form-full{grid-column:1/-1}
.statut-bar{display:flex;gap:8px;align-items:center;margin-bottom:14px}
.statut-opt{display:flex;align-items:center;gap:5px;padding:5px 14px;border-radius:999px;font-size:.78rem;font-weight:700;cursor:pointer;border:2px solid transparent}
.statut-opt input{display:none}
.statut-opt.sel-actif{background:#dcfce7;color:#166534;border-color:#16a34a}
.statut-opt.sel-brouillon{background:#fef9c3;color:#713f12;border-color:#eab308}
.statut-opt.sel-archive{background:#f1f5f9;color:#94a3b8;border-color:#cbd5e1}
.btn-save{padding:8px 22px;background:#1e40af;color:#fff;border:none;border-radius:7px;font-size:.85rem;font-weight:700;cursor:pointer}
.btn-save:hover{background:#1d4ed8}
.flash-ok{background:#dcfce7;color:#166534;border:1px solid #86efac;border-radius:7px;padding:9px 16px;font-size:.82rem;margin-bottom:14px;font-weight:700}
.flash-err{background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;border-radius:7px;padding:9px 16px;font-size:.82rem;margin-bottom:14px}
.breadcrumb{font-size:.78rem;color:#64748b;margin-bottom:10px}
.breadcrumb a{color:#1e40af;text-decoration:none}
.no-niv{background:#fffbeb;border:1px solid #fde68a;border-radius:7px;padding:10px 14px;font-size:.8rem;color:#92400e;margin-bottom:8px}
</style>

<div class="niv-edit-wrap">

  <div class="breadcrumb">
    <a href="index.php">← Niveaux</a> / <?= e($formation['titre']) ?>
  </div>

  <h2>✏️ <?= e($formation['titre']) ?></h2>
  <div class="sub">Slug : <?= e($formation['slug']) ?> · Domaine : <?= e($formation['domaine'] ?? '—') ?></div>

  <?php if ($flash): ?>
    <div class="flash-<?= $flash[0] ?>"><?= $flash[0] === 'ok' ? '✅' : '⚠️' ?> <?= e($flash[1]) ?></div>
  <?php endif; ?>

  <!-- Onglets niveaux -->
  <div class="niv-tabs">
    <?php foreach (['debutant','intermediaire','expert'] as $i => $nv): ?>
    <?php $has = isset($niveaux_map[$nv]); $cls = $has ? 'active-'.$nv[0] : ''; ?>
    <button type="button" class="niv-tab-btn <?= $cls ?>" onclick="showNivPanel('<?= $nv ?>')" id="tab-<?= $nv ?>">
      <?= $niv_labels[$nv] ?><?= $has ? '' : ' <small style="font-weight:400;font-size:.65rem">(non créé)</small>' ?>
    </button>
    <?php endforeach; ?>
  </div>

  <?php foreach (['debutant','intermediaire','expert'] as $nv):
    $n = $niveaux_map[$nv] ?? null;
    $statut_n = $n['statut'] ?? 'brouillon';
    $duree_n = $n['duree_heures'] ?? 20;
    $ol_n = $n['tarif_en_ligne'] ?? 200000;
    $pr_n = $n['tarif_presentiel'] ?? 250000;
    $hy_n = $n['tarif_hybride'] ?? 0;
    $obj_n = $n['objectifs'] ?? '';
    $pre_n = $n['prerequis'] ?? '';
    $pub_n = $n['public_cible'] ?? '';
    $csrf  = csrf_token();
  ?>
  <div class="niv-panel" id="panel-<?= $nv ?>">

    <?php if (!$n): ?>
    <div class="no-niv">⚠️ Ce niveau n'a pas encore été créé. Remplissez le formulaire ci-dessous pour le créer.</div>
    <?php endif; ?>

    <form method="post">
      <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
      <input type="hidden" name="niveau" value="<?= $nv ?>">

      <div class="statut-bar">
        <strong style="font-size:.78rem;color:#475569">Statut :</strong>
        <?php foreach (['actif','brouillon','archive'] as $st): ?>
        <label class="statut-opt sel-<?= $st ?>" style="<?= $statut_n !== $st ? 'opacity:.45;border-color:transparent' : '' ?>">
          <input type="radio" name="statut" value="<?= $st ?>" <?= $statut_n === $st ? 'checked' : '' ?>
                 onchange="this.closest('.statut-bar').querySelectorAll('.statut-opt').forEach(function(el){el.style.opacity='.45';el.style.borderColor='transparent'});this.closest('.statut-opt').style.opacity='1';this.closest('.statut-opt').style.borderColor=''">
          <?= $st === 'actif' ? '✅ Actif' : ($st === 'brouillon' ? '✏️ Brouillon' : '📦 Archivé') ?>
        </label>
        <?php endforeach; ?>
      </div>

      <div class="form-grid">
        <div class="form-group">
          <label>Durée (heures)</label>
          <input type="number" name="duree_heures" value="<?= (int)$duree_n ?>" min="1" max="999">
        </div>
        <div class="form-group">
          <label>Tarif en ligne (F CFA)</label>
          <input type="number" name="tarif_en_ligne" value="<?= (int)$ol_n ?>" min="0" step="5000">
        </div>
        <div class="form-group">
          <label>Tarif présentiel (F CFA)</label>
          <input type="number" name="tarif_presentiel" value="<?= (int)$pr_n ?>" min="0" step="5000">
        </div>
        <div class="form-group">
          <label>Tarif hybride (F CFA)</label>
          <input type="number" name="tarif_hybride" value="<?= (int)$hy_n ?>" min="0" step="5000">
        </div>
        <div class="form-group form-full">
          <label>Objectifs spécifiques à ce niveau</label>
          <textarea name="objectifs" rows="3"><?= e($obj_n) ?></textarea>
        </div>
        <div class="form-group form-full">
          <label>Prérequis</label>
          <textarea name="prerequis" rows="2"><?= e($pre_n) ?></textarea>
        </div>
        <div class="form-group form-full">
          <label>Public cible</label>
          <textarea name="public_cible" rows="2"><?= e($pub_n) ?></textarea>
        </div>
      </div>

      <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
        <button type="submit" class="btn-save">💾 Enregistrer <?= $niv_labels[$nv] ?></button>
        <?php if ($n):
            $mc = $pdo->prepare("SELECT COUNT(*) FROM formation_niveau_modules WHERE niveau_id=:i");
            $mc->execute([':i' => (int)$n['id']]);
            $nb_mod = (int)$mc->fetchColumn();
        ?>
        <a href="modules.php?niveau_id=<?= (int)$n['id'] ?>" style="font-size:.8rem;color:#1e40af;text-decoration:none;border:1px solid #93c5fd;padding:6px 14px;border-radius:6px;font-weight:700">📋 Modules (<?= $nb_mod ?>)</a>
        <?php endif; ?>
      </div>
    </form>
  </div>
  <?php endforeach; ?>

</div>

<script>
function showNivPanel(nv) {
  document.querySelectorAll('.niv-panel').forEach(function(p){ p.classList.remove('active'); });
  document.querySelectorAll('.niv-tab-btn').forEach(function(b){ b.classList.remove('active-d','active-i','active-e'); });
  document.getElementById('panel-'+nv).classList.add('active');
  var btn = document.getElementById('tab-'+nv);
  btn.classList.add('active-'+nv.charAt(0));
}
// Ouvrir le premier onglet par défaut
document.addEventListener('DOMContentLoaded', function(){
  showNivPanel('debutant');
});
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout/layout.php';
