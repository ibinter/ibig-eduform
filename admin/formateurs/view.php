<?php
declare(strict_types=1);

require_once __DIR__ . '/../_init.php';

/* ============================
   MIDDLEWARE ADMIN
============================ */
$mw = realpath(__DIR__ . '/../auth/middleware.php');
if (!$mw) { die('Middleware introuvable'); }
require_once $mw;
Middleware::requireAuth();

$pageTitle  = 'Détail candidature formateur';
$activeMenu = 'formateurs';

$pdo = Database::connect();

/* =========================
   VALIDATION ID
========================= */
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  header('Location: index.php');
  exit;
}

/* =========================
   RÉCUPÉRATION
========================= */
$stmt = $pdo->prepare("
  SELECT
    c.*,
    f.id     AS formateur_id,
    f.statut AS fiche_statut
  FROM candidatures_formateurs c
  LEFT JOIN formateurs f ON f.candidature_id = c.id
  WHERE c.id = ?
  LIMIT 1
");
$stmt->execute([$id]);
$c = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$c) {
  header('Location: index.php');
  exit;
}

/* =========================
   VARIABLES
========================= */
$cvPath     = ltrim((string)($c['cv_path'] ?? ''), '/');
$isVisible  = (int)($c['visible'] ?? 0) === 1;
$ficheCreee = (int)($c['fiche_creee'] ?? 0) === 1;
$hasFiche   = !empty($c['formateur_id']);

ob_start();
?>

<style>
/* =====================================================
   BASE UI
===================================================== */
.muted{color:#6b7280}

.box{
  background:#f8fafc;
  border:1px solid #e5e7eb;
  padding:14px;
  border-radius:10px;
  font-size:14px;
}

/* =====================================================
   PILLS
===================================================== */
.pill{
  display:inline-flex;
  align-items:center;
  padding:0 8px;
  height:22px;
  font-size:11.5px;
  border-radius:999px;
}
.pill.ok{background:#dcfce7;color:#166534}
.pill.danger{background:#fee2e2;color:#991b1b}
.pill.wait{background:#ffedd5;color:#9a3412}

/* =====================================================
   FIX BOUTONS (CRITIQUE)
===================================================== */
.btn{
  display:inline-flex;
  align-items:center;
  justify-content:center;
  height:34px;
  min-width:34px;
  padding:0 12px;
  border-radius:8px;
  font-size:13px;
  border:1px solid #e5e7eb;
  background:#ffffff;
  color:#111827 !important;
  opacity:1 !important;
  cursor:pointer;
  text-decoration:none;
}
.btn:hover{background:#f3f4f6}

.btn-outline{background:#ffffff}
.btn-primary{background:#2563eb;color:#fff!important;border-color:#2563eb}
.btn-secondary{background:#f1f5f9}
.btn-success{background:#dcfce7;color:#166534!important}
.btn-danger{background:#fee2e2;color:#991b1b!important}
.btn-warning{background:#ffedd5;color:#9a3412!important}
.btn-info{background:#e0f2fe;color:#0369a1!important}
.btn-dark{background:#1f2937;color:#ffffff!important}
</style>

<div class="card">

  <!-- HEADER -->
  <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">
    <h2 style="margin:0">Détail candidature</h2>
    <a class="btn btn-outline" href="/admin/formateurs/index.php">
      Retour à la liste
    </a>
  </div>

  <!-- INFOS -->
  <div style="margin-top:18px;display:grid;grid-template-columns:1fr 1fr;gap:16px">

    <div><strong>Nom</strong><br><?= e($c['nom']); ?></div>
    <div><strong>Domaine</strong><br><?= e($c['domaine']); ?></div>

    <div><strong>Email</strong><br><?= e($c['email']); ?></div>
    <div><strong>Téléphone</strong><br><?= e($c['telephone']); ?></div>

    <div>
      <strong>Statut</strong><br>
      <span class="pill <?= $c['statut']==='retenue'?'ok':($c['statut']==='rejete'?'danger':'wait'); ?>">
        <?= e($c['statut']); ?>
      </span>
    </div>

    <div>
      <strong>Visible publiquement</strong><br>
      <span class="pill <?= $isVisible?'ok':'wait'; ?>">
        <?= $isVisible?'Oui':'Non'; ?>
      </span>
    </div>

    <div>
      <strong>Date de candidature</strong><br>
      <?= date('d/m/Y H:i', strtotime((string)$c['created_at'])); ?>
    </div>

    <div>
      <strong>Fiche formateur</strong><br>
      <?php if ($hasFiche): ?>
        <span class="pill <?= $c['fiche_statut']==='inactif'?'danger':'ok'; ?>">
          <?= $c['fiche_statut']==='inactif'?'Inactif':'Active'; ?>
        </span>
      <?php else: ?>
        <span class="pill wait">Non créée</span>
      <?php endif; ?>
    </div>

  </div>

  <!-- MESSAGE -->
  <?php if (!empty($c['message'])): ?>
    <div style="margin-top:20px">
      <strong>Présentation</strong>
      <div class="box" style="margin-top:6px">
        <?= nl2br(e($c['message'])); ?>
      </div>
    </div>
  <?php endif; ?>

  <!-- CV -->
  <div style="margin-top:18px">
    <strong>CV</strong><br>
    <?php if ($cvPath): ?>
      <a class="btn btn-info" href="/<?= e($cvPath); ?>" target="_blank" rel="noopener">
        Télécharger le CV
      </a>
    <?php else: ?>
      <span class="muted">Aucun CV fourni</span>
    <?php endif; ?>
  </div>

  <!-- ACTIONS -->
  <div style="margin-top:28px;display:flex;gap:10px;flex-wrap:wrap">

    <?php foreach (['nouvelle'=>'Nouvelle','en_cours'=>'En cours','retenue'=>'Valider','rejete'=>'Rejeter'] as $k=>$label): ?>
      <?php if ($c['statut'] !== $k): ?>
        <form method="post" action="/admin/formateurs/set-status.php">
          <?= csrf_field(); ?>
          <input type="hidden" name="id" value="<?= $id; ?>">
          <input type="hidden" name="statut" value="<?= $k; ?>">
          <button class="btn <?= $k==='retenue'?'btn-success':($k==='rejete'?'btn-danger':'btn-secondary'); ?>">
            <?= $label; ?>
          </button>
        </form>
      <?php endif; ?>
    <?php endforeach; ?>

    <form method="post" action="/admin/formateurs/toggle-visible.php">
      <?= csrf_field(); ?>
      <input type="hidden" name="id" value="<?= $id; ?>">
      <input type="hidden" name="visible" value="<?= $isVisible?0:1; ?>">
      <button class="btn <?= $isVisible?'btn-dark':'btn-warning'; ?>">
        <?= $isVisible?'Masquer':'Publier'; ?>
      </button>
    </form>

    <?php if ($c['statut']==='retenue' && !$ficheCreee): ?>
      <a class="btn btn-primary" href="/admin/formateurs/create-fiche.php?id=<?= $id; ?>">
        Créer fiche formateur
      </a>
    <?php endif; ?>

    <?php if ($hasFiche): ?>
      <a class="btn btn-outline" href="/admin/formateurs/view-fiche.php?id=<?= $id; ?>">
        Voir fiche formateur
      </a>
    <?php endif; ?>

  </div>

</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout/layout.php';