<?php
declare(strict_types=1);
/**
 * ADMIN — Créer / modifier un niveau de formation
 */
ob_start();
require_once __DIR__ . '/../_init.php';
require_once __DIR__ . '/../auth/middleware.php';
Middleware::requireAuth();

$pdo = Database::connect();

// ── Coefficients ────────────────────────────────────────────────────
const COEFF_NIV = ['debutant' => 1.00, 'intermediaire' => 1.15, 'expert' => 1.35];
const DUREE_MIN = ['debutant' => 20, 'intermediaire' => 25, 'expert' => 35];
function r5(float $v): int { return (int)(round($v / 5000) * 5000); }
function fcfa(int $v): string { return number_format($v, 0, ',', ' ') . ' F'; }

$id          = (int)($_GET['id'] ?? 0);
$formation_id = (int)($_GET['formation_id'] ?? 0);
$niv_param   = trim((string)($_GET['niveau'] ?? ''));
$isNew       = $id === 0;

// Charger le niveau existant
$niveau = null;
if (!$isNew) {
    $niveau = $pdo->prepare("
        SELECT n.*, f.titre AS formation_titre, f.tarif_en_ligne AS f_tarif_ol, f.tarif_presentiel AS f_tarif_pr
        FROM formation_niveaux n
        JOIN formations f ON f.id = n.formation_id
        WHERE n.id = :id
    ");
    $niveau->execute([':id' => $id]);
    $niveau = $niveau->fetch();
    if (!$niveau) { http_response_code(404); die('Niveau introuvable.'); }
    $formation_id = (int)$niveau['formation_id'];
}

// Charger la formation de base
$formation = null;
if ($formation_id) {
    $formation = $pdo->prepare("SELECT * FROM formations WHERE id = :id");
    $formation->execute([':id' => $formation_id]);
    $formation = $formation->fetch();
}

$msg = '';
$err = '';

// ── Traitement POST ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fid        = (int)($_POST['formation_id'] ?? 0);
    $niv        = $_POST['niveau'] ?? '';
    $duree      = max(1, (int)($_POST['duree_heures'] ?? 20));
    $t_ol       = max(200000, (int)str_replace(' ', '', $_POST['tarif_en_ligne'] ?? '0'));
    $t_pr       = max(250000, (int)str_replace(' ', '', $_POST['tarif_presentiel'] ?? '0'));
    $t_hy       = max(0, (int)str_replace(' ', '', $_POST['tarif_hybride'] ?? '0'));
    if ($t_hy === 0) $t_hy = r5(($t_ol + $t_pr) / 2);
    $objectifs  = trim($_POST['objectifs'] ?? '');
    $prerequis  = trim($_POST['prerequis'] ?? '');
    $public_cib = trim($_POST['public_cible'] ?? '');
    $statut     = in_array($_POST['statut'] ?? '', ['actif', 'brouillon', 'archive']) ? $_POST['statut'] : 'brouillon';

    if (!in_array($niv, ['debutant', 'intermediaire', 'expert'])) {
        $err = 'Niveau invalide.';
    } elseif (!$fid) {
        $err = 'Formation requise.';
    } else {
        try {
            if ($isNew) {
                $pdo->prepare("
                    INSERT INTO formation_niveaux
                        (formation_id, niveau, duree_heures, tarif_en_ligne, tarif_presentiel, tarif_hybride,
                         objectifs, prerequis, public_cible, statut, ordre_affichage)
                    VALUES
                        (:fid, :niv, :dur, :tol, :tpr, :thy, :obj, :pre, :pub, :stat,
                         :ord)
                ")->execute([
                    ':fid' => $fid, ':niv' => $niv, ':dur' => $duree,
                    ':tol' => $t_ol, ':tpr' => $t_pr, ':thy' => $t_hy,
                    ':obj' => $objectifs, ':pre' => $prerequis, ':pub' => $public_cib,
                    ':stat' => $statut,
                    ':ord' => ['debutant' => 1, 'intermediaire' => 2, 'expert' => 3][$niv],
                ]);
                $new_id = (int)$pdo->lastInsertId();
                header('Location: edit-niveau.php?id=' . $new_id . '&saved=1');
                exit;
            } else {
                $pdo->prepare("
                    UPDATE formation_niveaux SET
                        duree_heures = :dur, tarif_en_ligne = :tol, tarif_presentiel = :tpr,
                        tarif_hybride = :thy, objectifs = :obj, prerequis = :pre,
                        public_cible = :pub, statut = :stat
                    WHERE id = :id
                ")->execute([
                    ':dur' => $duree, ':tol' => $t_ol, ':tpr' => $t_pr, ':thy' => $t_hy,
                    ':obj' => $objectifs, ':pre' => $prerequis, ':pub' => $public_cib,
                    ':stat' => $statut, ':id' => $id,
                ]);
                $msg = '✅ Niveau enregistré.';
                // Recharger
                $niveau = $pdo->prepare("
                    SELECT n.*, f.titre AS formation_titre, f.tarif_en_ligne AS f_tarif_ol, f.tarif_presentiel AS f_tarif_pr
                    FROM formation_niveaux n
                    JOIN formations f ON f.id = n.formation_id
                    WHERE n.id = :id
                ");
                $niveau->execute([':id' => $id]);
                $niveau = $niveau->fetch();
            }
        } catch (PDOException $e) {
            $err = 'Erreur : ' . $e->getMessage();
        }
    }
}

if (isset($_GET['saved'])) $msg = '✅ Niveau créé avec succès.';

$pageTitle  = $isNew ? 'Créer un niveau' : 'Modifier le niveau';
$activeMenu = 'niveaux';

// Calcul automatique des tarifs suggérés
function suggested_prix(array $formation, string $niv): array {
    $base_ol = max(200000, (int)$formation['tarif_en_ligne']);
    $base_pr = max(250000, (int)$formation['tarif_presentiel']);
    $c = COEFF_NIV[$niv];
    $t_ol = max(
        ['debutant' => 200000, 'intermediaire' => 250000, 'expert' => 300000][$niv],
        r5((float)$base_ol * $c)
    );
    $t_pr = max(
        ['debutant' => 250000, 'intermediaire' => 300000, 'expert' => 400000][$niv],
        r5((float)$base_pr * $c)
    );
    $t_hy = r5(($t_ol + $t_pr) / 2);
    return ['ol' => $t_ol, 'pr' => $t_pr, 'hy' => $t_hy];
}

require_once __DIR__ . '/../layout/header.php';
?>

<style>
.en-page { max-width: 900px; margin: 24px auto; padding: 0 16px; }
.en-card { background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:28px; }
.en-title { font-size:1.3rem; font-weight:700; color:#0a1733; margin-bottom:20px; }
.en-grid { display:grid; grid-template-columns:1fr 1fr; gap:20px; }
.en-field { margin-bottom:16px; }
.en-field label { display:block; font-size:.8rem; font-weight:600; color:#374151; margin-bottom:5px; text-transform:uppercase; letter-spacing:.03em; }
.en-field input, .en-field select, .en-field textarea {
    width:100%; border:1px solid #d1d5db; border-radius:8px; padding:10px 12px;
    font-size:.9rem; box-sizing:border-box;
}
.en-field input:focus, .en-field select:focus, .en-field textarea:focus {
    outline:none; border-color:#0a1733;
}
.en-field textarea { min-height:90px; resize:vertical; }
.en-prix-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; }
.en-suggest { font-size:.75rem; color:#0891b2; margin-top:3px; cursor:pointer; }
.en-suggest:hover { text-decoration:underline; }
.en-niv-pills { display:flex; gap:10px; margin-bottom:20px; }
.en-niv-pill { border:2px solid #e2e8f0; border-radius:8px; padding:10px 18px; cursor:pointer; text-align:center; flex:1; transition:.15s; }
.en-niv-pill.sel-deb { border-color:#059669; background:#dcfce7; }
.en-niv-pill.sel-int { border-color:#1e40af; background:#dbeafe; }
.en-niv-pill.sel-exp { border-color:#6d28d9; background:#ede9fe; }
.en-niv-pill input { display:none; }
.en-actions { display:flex; gap:12px; align-items:center; margin-top:20px; }
.en-btn { background:#0a1733; color:#fff; border:none; border-radius:8px; padding:10px 20px; cursor:pointer; font-size:.9rem; }
.en-btn:hover { background:#1e3a6e; }
.en-msg { padding:10px 14px; border-radius:8px; margin-bottom:16px; }
.en-msg.ok { background:#dcfce7; color:#166534; }
.en-msg.err { background:#fee2e2; color:#991b1b; }
.en-breadcrumb { font-size:.8rem; color:#64748b; margin-bottom:16px; }
.en-breadcrumb a { color:#0a1733; }
.en-coeff { font-size:.75rem; color:#64748b; margin-top:4px; }
.en-modules-cta { background:#f0ecff; border:1px solid #c4b5fd; border-radius:8px; padding:12px 16px; margin-top:16px; display:flex; align-items:center; gap:12px; }
</style>

<div class="en-page">
  <div class="en-breadcrumb">
    <a href="index.php">Niveaux</a> › <?= $pageTitle ?>
  </div>

  <?php if ($msg): ?><div class="en-msg ok"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <?php if ($err): ?><div class="en-msg err"><?= htmlspecialchars($err) ?></div><?php endif; ?>

  <div class="en-card">
    <div class="en-title"><?= $pageTitle ?></div>

    <?php if ($formation): ?>
    <div style="background:#f8fafc;border-radius:8px;padding:12px 16px;margin-bottom:20px;font-size:.875rem;">
      <strong>Formation :</strong> <?= htmlspecialchars($formation['titre']) ?><br>
      <span style="color:#64748b">Tarif actuel : <?= fcfa((int)$formation['tarif_en_ligne']) ?> en ligne · <?= fcfa((int)$formation['tarif_presentiel']) ?> présentiel</span>
    </div>
    <?php endif; ?>

    <form method="post">
      <input type="hidden" name="formation_id" value="<?= (int)($niveau['formation_id'] ?? $formation_id) ?>">

      <!-- Niveau -->
      <div class="en-field">
        <label>Niveau</label>
        <div class="en-niv-pills">
          <?php foreach (['debutant' => ['🟢', 'Débutant', 'Fondamentaux', 'deb'], 'intermediaire' => ['🔵', 'Intermédiaire', 'Pratique', 'int'], 'expert' => ['🟣', 'Expert', 'Maîtrise', 'exp']] as $nk => [$ico, $nl, $sub, $cls]): ?>
          <?php $sel = ($niveau['niveau'] ?? $niv_param) === $nk; ?>
          <label class="en-niv-pill <?= $sel ? 'sel-'.$cls : '' ?>" onclick="selectNiv('<?= $nk ?>')">
            <input type="radio" name="niveau" value="<?= $nk ?>" <?= $sel ? 'checked' : '' ?>>
            <div style="font-size:1.3rem"><?= $ico ?></div>
            <div style="font-weight:700"><?= $nl ?></div>
            <div style="font-size:.75rem;color:#64748b"><?= $sub ?></div>
            <?php if ($formation): $s = suggested_prix($formation, $nk); ?>
            <div class="en-coeff">× <?= COEFF_NIV[$nk] ?> → à partir de <?= fcfa($s['ol']) ?></div>
            <?php endif; ?>
          </label>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Durée + Statut -->
      <div class="en-grid">
        <div class="en-field">
          <label>Durée (heures)</label>
          <input type="number" name="duree_heures" id="dureeInput"
            value="<?= (int)($niveau['duree_heures'] ?? DUREE_MIN[$niv_param ?: 'debutant']) ?>"
            min="1" max="500">
        </div>
        <div class="en-field">
          <label>Statut</label>
          <select name="statut">
            <?php foreach (['brouillon' => 'Brouillon', 'actif' => 'Actif', 'archive' => 'Archivé'] as $v => $l): ?>
            <option value="<?= $v ?>" <?= ($niveau['statut'] ?? 'brouillon') === $v ? 'selected' : '' ?>><?= $l ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <!-- Tarifs -->
      <div class="en-field">
        <label>Tarifs</label>
        <div class="en-prix-grid">
          <div>
            <label style="font-size:.75rem;color:#64748b">💻 En ligne (F CFA)</label>
            <input type="number" name="tarif_en_ligne" id="tarifOl"
              value="<?= (int)($niveau['tarif_en_ligne'] ?? ($formation ? suggested_prix($formation, $niv_param ?: 'debutant')['ol'] : 200000)) ?>"
              min="200000" step="5000">
            <?php if ($formation): $s = suggested_prix($formation, $niv_param ?: ($niveau['niveau'] ?? 'debutant')); ?>
            <div class="en-suggest" onclick="document.getElementById('tarifOl').value=<?= $s['ol'] ?>">
              Suggéré : <?= fcfa($s['ol']) ?>
            </div>
            <?php endif; ?>
          </div>
          <div>
            <label style="font-size:.75rem;color:#64748b">🏛️ Présentiel (F CFA)</label>
            <input type="number" name="tarif_presentiel" id="tarifPr"
              value="<?= (int)($niveau['tarif_presentiel'] ?? ($formation ? suggested_prix($formation, $niv_param ?: 'debutant')['pr'] : 250000)) ?>"
              min="250000" step="5000">
            <?php if ($formation): ?>
            <div class="en-suggest" onclick="document.getElementById('tarifPr').value=<?= $s['pr'] ?>">
              Suggéré : <?= fcfa($s['pr']) ?>
            </div>
            <?php endif; ?>
          </div>
          <div>
            <label style="font-size:.75rem;color:#64748b">🔀 Hybride (F CFA)</label>
            <input type="number" name="tarif_hybride" id="tarifHy"
              value="<?= (int)($niveau['tarif_hybride'] ?? ($formation ? suggested_prix($formation, $niv_param ?: 'debutant')['hy'] : 0)) ?>"
              min="0" step="5000">
            <?php if ($formation): ?>
            <div class="en-suggest" onclick="document.getElementById('tarifHy').value=<?= $s['hy'] ?>">
              Suggéré : <?= fcfa($s['hy']) ?>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Objectifs -->
      <div class="en-field">
        <label>Objectifs spécifiques à ce niveau</label>
        <textarea name="objectifs" placeholder="Ex : À l'issue de ce niveau Débutant, le participant saura…"><?= htmlspecialchars($niveau['objectifs'] ?? '') ?></textarea>
      </div>

      <!-- Prérequis -->
      <div class="en-field">
        <label>Prérequis</label>
        <textarea name="prerequis" rows="3" placeholder="Ex : Aucun prérequis · Niveau Débutant validé ou test de positionnement…"><?= htmlspecialchars($niveau['prerequis'] ?? '') ?></textarea>
      </div>

      <!-- Public cible -->
      <div class="en-field">
        <label>Public cible</label>
        <textarea name="public_cible" rows="3" placeholder="Ex : Professionnels en reconversion · Cadres souhaitant approfondir…"><?= htmlspecialchars($niveau['public_cible'] ?? '') ?></textarea>
      </div>

      <div class="en-actions">
        <button type="submit" class="en-btn">💾 Enregistrer</button>
        <a href="index.php" style="color:#64748b;font-size:.875rem">← Retour à la liste</a>
      </div>
    </form>

    <?php if (!$isNew && $id): ?>
    <div class="en-modules-cta">
      <span style="font-size:1.5rem">📦</span>
      <div>
        <strong>Modules de ce niveau</strong><br>
        <a href="modules.php?niveau_id=<?= $id ?>&titre=<?= urlencode((string)($niveau['formation_titre'] ?? '')) ?>&niveau=<?= htmlspecialchars($niveau['niveau'] ?? '') ?>">
          Gérer les modules →
        </a>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<script>
function selectNiv(niv) {
    document.querySelectorAll('.en-niv-pill').forEach(el => {
        el.classList.remove('sel-deb','sel-int','sel-exp');
    });
    const map = {debutant:'deb', intermediaire:'int', expert:'exp'};
    document.querySelectorAll('input[name="niveau"]').forEach(r => {
        if (r.value === niv) {
            r.checked = true;
            r.closest('.en-niv-pill').classList.add('sel-'+map[niv]);
        }
    });
    // Mettre à jour durée minimum suggérée
    const mins = {debutant:20, intermediaire:25, expert:35};
    const d = document.getElementById('dureeInput');
    if (d && parseInt(d.value) < mins[niv]) d.value = mins[niv];
}
// Calcul hybride automatique
['tarifOl','tarifPr'].forEach(id => {
    document.getElementById(id)?.addEventListener('input', () => {
        const ol = parseInt(document.getElementById('tarifOl').value)||0;
        const pr = parseInt(document.getElementById('tarifPr').value)||0;
        const hy = Math.round((ol + pr) / 2 / 5000) * 5000;
        const h = document.getElementById('tarifHy');
        if (h && !h.dataset.manual) h.value = hy;
    });
});
document.getElementById('tarifHy')?.addEventListener('input', function() {
    this.dataset.manual = '1';
});
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
