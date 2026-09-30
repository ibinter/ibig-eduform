<?php
declare(strict_types=1);

/**
 * ============================================================
 * ADMIN — EMPLOIS — OFFRES SOUMISES (ULTRA COMPACT UI)
 * Fichier : /admin/emplois/offres.php
 * ============================================================
 */

require_once __DIR__ . '/../_init.php';

/* 🔐 Middleware */
$mw = realpath(__DIR__ . '/../auth/middleware.php');
if (!$mw) {
    die("Middleware introuvable");
}
require_once $mw;

if (!class_exists('Middleware')) {
    die("Classe Middleware introuvable");
}

Middleware::requireAuth();

/* =========================
   META
========================= */
$pageTitle  = "Offres d’emploi soumises";
$activeMenu = "emplois_offres";

/* =========================
   DB
========================= */
$pdo = Database::connect();

/* =========================
   DATA
========================= */
$sql = "
  SELECT
    o.id,
    o.titre,
    o.lieu,
    o.type_contrat,
    o.statut,
    o.cree_le,
    e.nom_legal
  FROM offres_emploi o
  JOIN entreprises e ON e.id = o.entreprise_id
  WHERE o.statut = 'soumise'
  ORDER BY o.cree_le DESC
";
$offres = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

ob_start();
?>

<style>
/* =========================================================
   OFFRES EMPLOI — TABLE PREMIUM COMPACT (SAME ADN AS FORMATIONS)
========================================================= */

table{
  width:100%;
  font-size:14px;
}

th, td{
  vertical-align:middle;
  white-space:nowrap;
}

/* ---- COLONNES ---- */
.cell-title{
  max-width:340px;
}
.cell-title .title{
  font-weight:600;
  overflow:hidden;
  text-overflow:ellipsis;
  white-space:nowrap;
}
.cell-title .subtitle{
  font-size:12px;
  color:#6b7280;
}

.cell-lieu{
  max-width:160px;
}

.cell-date{
  font-size:13px;
  color:#374151;
}

/* ---- ACTIONS ICONES ---- */
.cell-actions{
  display:flex;
  gap:6px;
  align-items:center;
  justify-content:flex-end;
  flex-wrap:nowrap;
}

.action-btn{
  width:36px;
  height:36px;
  border-radius:999px;
  display:flex;
  align-items:center;
  justify-content:center;
  border:1px solid #e5e7eb;
  background:#fff;
  cursor:pointer;
  font-size:16px;
}

.action-btn:hover{
  background:#f3f4f6;
}

/* ---- STATUT ---- */
.pill{
  padding:4px 10px;
  border-radius:999px;
  font-size:12px;
  font-weight:500;
}

.pill.soumise{
  background:#fff7ed;
  color:#9a3412;
}
.pill.approuvee{
  background:#dcfce7;
  color:#166534;
}
.pill.rejetee{
  background:#fee2e2;
  color:#991b1b;
}
</style>

<div class="card">

  <!-- HEADER -->
  <div style="display:flex;justify-content:space-between;align-items:center">
    <h2>💼 Offres d’emploi soumises</h2>

    <a class="btn btn-primary" href="create.php">
      ➕ Nouvelle offre
    </a>
  </div>

  <!-- TABLE -->
  <table style="margin-top:16px">
    <thead>
      <tr>
        <th>Offre / Entreprise</th>
        <th>Lieu</th>
        <th>Contrat</th>
        <th>Date</th>
        <th>Statut</th>
        <th style="width:220px">Actions</th>
      </tr>
    </thead>
    <tbody>

    <?php if (!$offres): ?>
      <tr>
        <td colspan="6" class="muted">Aucune offre soumise.</td>
      </tr>
    <?php endif; ?>

    <?php foreach ($offres as $o): ?>
      <tr>

        <!-- TITRE -->
        <td class="cell-title">
          <div class="title"><?= e($o['titre']); ?></div>
          <div class="subtitle"><?= e($o['nom_legal']); ?></div>
        </td>

        <!-- LIEU -->
        <td class="cell-lieu"><?= e($o['lieu']); ?></td>

        <!-- CONTRAT -->
        <td><?= e($o['type_contrat']); ?></td>

        <!-- DATE -->
        <td class="cell-date">
          <?= date('d/m/Y', strtotime($o['cree_le'])); ?>
        </td>

        <!-- STATUT -->
        <td>
          <span class="pill <?= e($o['statut']); ?>">
            <?= e($o['statut']); ?>
          </span>
        </td>

        <!-- ACTIONS ICON ONLY -->
        <td class="cell-actions">

          <a class="action-btn"
             href="offre_view.php?id=<?= (int)$o['id']; ?>"
             title="Voir l’offre">
            👁️
          </a>

          <a class="action-btn"
             href="edit.php?id=<?= (int)$o['id']; ?>"
             title="Éditer">
            ✏️
          </a>

          <form method="post" action="actions.php?id=<?= (int)$o['id']; ?>" style="display:inline">
            <?= csrf_field(); ?>
            <input type="hidden" name="id" value="<?= (int)$o['id']; ?>">
            <button class="action-btn" name="approve" value="1" title="Approuver">
              ✅
            </button>
          </form>

          <form method="post" action="actions.php?id=<?= (int)$o['id']; ?>" style="display:inline">
            <?= csrf_field(); ?>
            <input type="hidden" name="id" value="<?= (int)$o['id']; ?>">
            <button class="action-btn" name="reject" value="1" title="Rejeter">
              ❌
            </button>
          </form>

        </td>

      </tr>
    <?php endforeach; ?>

    </tbody>
  </table>

</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout/layout.php';