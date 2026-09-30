<?php
declare(strict_types=1);

require_once __DIR__ . '/core/config.php';
require_once __DIR__ . '/core/database.php';

$pdo = Database::connect();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  header('Location: opportunites-emploi.php');
  exit;
}

/* ============================
   RÉCUPÉRATION OPPORTUNITÉ
============================ */
$stmt = $pdo->prepare("
  SELECT
    titre,
    entreprise,
    domaine,
    type,
    lieu,
    description,
    profil_recherche,
    lien_candidature,
    email_candidature,
    reserve_apprenants,
    created_at
  FROM opportunites_emploi
  WHERE id = ?
    AND statut = 'active'
  LIMIT 1
");
$stmt->execute([$id]);
$o = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$o) {
  header('Location: opportunites-emploi.php');
  exit;
}

$pageTitle = $o['titre'] . " – Opportunités & Emploi | IBIG EDUFORM";
include __DIR__ . '/partials/header.php';

?>

<!-- HERO -->
<section class="hero-emploi">
  <div class="container">
    <h1><?= e($o['titre']); ?></h1>

    <p class="meta">
      <?= e($o['domaine']); ?> • <?= e($o['type']); ?>
      <?php if (!empty($o['lieu'])): ?>
        • <?= e($o['lieu']); ?>
      <?php endif; ?>
    </p>

    <span class="badge <?= $o['reserve_apprenants'] ? 'badge-lock' : 'badge-open'; ?>">
      <?= $o['reserve_apprenants'] ? 'Réservé aux apprenants IBIG EDUFORM' : 'Ouvert au public'; ?>
    </span>
  </div>
</section>

<!-- CONTENU -->
<section class="section emploi-detail">
  <div class="container emploi-container">

    <!-- GAUCHE -->
    <div class="emploi-main">

      <?php if (!empty($o['entreprise'])): ?>
        <h3>Entreprise</h3>
        <p class="bloc"><?= e($o['entreprise']); ?></p>
      <?php endif; ?>

      <h3>Description du poste</h3>
      <div class="bloc">
        <?= nl2br(e($o['description'])); ?>
      </div>

      <?php if (!empty($o['profil_recherche'])): ?>
        <h3>Profil recherché</h3>
        <div class="bloc">
          <?= nl2br(e($o['profil_recherche'])); ?>
        </div>
      <?php endif; ?>

    </div>

    <!-- DROITE -->
    <aside class="emploi-side">

      <div class="emploi-box">
        <h4>Informations clés</h4>

        <ul>
          <li><strong>Domaine :</strong> <?= e($o['domaine']); ?></li>
          <li><strong>Type :</strong> <?= e($o['type']); ?></li>
          <?php if (!empty($o['lieu'])): ?>
            <li><strong>Lieu :</strong> <?= e($o['lieu']); ?></li>
          <?php endif; ?>
          <li>
            <strong>Date de publication :</strong>
            <?= date('d/m/Y', strtotime($o['created_at'])); ?>
          </li>
        </ul>

        <div class="emploi-apply">
          <?php if (!empty($o['lien_candidature'])): ?>
            <a href="<?= e($o['lien_candidature']); ?>" target="_blank" rel="noopener"
               class="btn btn-primary btn-lg">
              Postuler maintenant
            </a>

          <?php elseif (!empty($o['email_candidature'])): ?>
            <a href="mailto:<?= e($o['email_candidature']); ?>"
               class="btn btn-primary btn-lg">
              Envoyer ma candidature
            </a>

          <?php else: ?>
            <p class="muted">Modalité de candidature communiquée après contact</p>
          <?php endif; ?>
        </div>

        <a href="opportunites-emploi.php" class="btn btn-outline btn-block">
          Retour aux opportunités
        </a>

      </div>

    </aside>

  </div>
</section>

<style>
.hero-emploi{
  background:linear-gradient(135deg,#0b3b82,#0a2f66);
  color:#fff;
  padding:72px 16px 64px;
  text-align:center;
}
.hero-emploi h1{font-size:40px;margin-bottom:10px}
.hero-emploi .meta{opacity:.95;margin-bottom:12px}

.badge{
  display:inline-block;
  padding:6px 14px;
  border-radius:999px;
  font-size:13px;
  font-weight:600;
}
.badge-lock{background:#fee2e2;color:#991b1b}
.badge-open{background:#dcfce7;color:#166534}

.emploi-detail{background:#f4f6f9;padding:64px 0}
.emploi-container{
  max-width:1100px;
  margin:auto;
  display:grid;
  grid-template-columns:2fr 1fr;
  gap:32px;
}

.emploi-main h3{margin-top:26px}
.bloc{
  background:#fff;
  padding:18px;
  border-radius:12px;
  line-height:1.65;
  color:#111827;
}

.emploi-box{
  background:#fff;
  padding:26px;
  border-radius:16px;
  box-shadow:0 18px 40px rgba(0,0,0,.08);
}
.emploi-box ul{list-style:none;padding:0}
.emploi-box li{margin-bottom:10px}

.btn-block{
  width:100%;
  margin-top:14px;
}

@media(max-width:900px){
  .emploi-container{grid-template-columns:1fr}
  .hero-emploi h1{font-size:28px}
}
</style>

<?php include __DIR__ . '/partials/footer.php'; ?>
