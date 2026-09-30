<?php
require_once __DIR__ . '/core/config.php';
require_once __DIR__ . '/core/database.php';

$pageTitle = "Nos formateurs – IBIG EDUFORM";
include __DIR__ . '/partials/header.php';

$pdo = Database::connect();

/* ============================
   FORMATEURS PUBLICS ACTIFS
============================ */
$stmt = $pdo->query("
  SELECT
    nom,
    domaine,
    experience,
    bio,
    photo
  FROM formateurs
  WHERE statut = 'actif'
  ORDER BY nom
");
$formateurs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<section class="section">
  <div class="container">

    <h1>Nos formateurs partenaires</h1>

    <?php if (empty($formateurs)): ?>
      <p class="muted">Aucun formateur n’est encore publié.</p>
    <?php endif; ?>

    <div class="grid-3">

      <?php foreach ($formateurs as $f): ?>
        <?php
          $photo = !empty($f['photo'])
            ? '/' . ltrim($f['photo'], '/')
            : '/assets/images/formateur-default.png';
        ?>

        <div class="card">

          <!-- PHOTO -->
          <div style="text-align:center;margin-bottom:12px">
            <img src="<?= htmlspecialchars($photo); ?>"
                 loading="lazy" decoding="async"
                 alt="<?= htmlspecialchars($f['nom']); ?>"
                 style="width:100%;max-width:160px;border-radius:50%;object-fit:cover">
          </div>

          <!-- NOM -->
          <h3><?= htmlspecialchars($f['nom'], ENT_QUOTES, 'UTF-8'); ?></h3>

          <!-- DOMAINE -->
          <p>
            <strong><?= htmlspecialchars($f['domaine'], ENT_QUOTES, 'UTF-8'); ?></strong>
          </p>

          <!-- EXPERIENCE -->
          <?php if (!empty($f['experience'])): ?>
            <p><?= htmlspecialchars($f['experience'], ENT_QUOTES, 'UTF-8'); ?></p>
          <?php endif; ?>

          <!-- BIO -->
          <?php if (!empty($f['bio'])): ?>
            <p><?= nl2br(htmlspecialchars($f['bio'], ENT_QUOTES, 'UTF-8')); ?></p>
          <?php endif; ?>

        </div>

      <?php endforeach; ?>

    </div>

  </div>
</section>

<?php include __DIR__ . '/partials/footer.php'; ?>
