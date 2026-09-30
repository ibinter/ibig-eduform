<?php
require_once __DIR__ . '/core/config.php';
require_once __DIR__ . '/core/database.php';

$pageTitle = "Réalisations & Impacts – IBIG EDUFORM";
include __DIR__ . '/partials/header.php';

$pdo = Database::connect();

// récupération des réalisations
$stmt = $pdo->query("
  SELECT titre, description, type, image, created_at
  FROM realisations
  ORDER BY created_at DESC
");
$realisations = $stmt->fetchAll();
?>

<style>
/* =========================================
   CSS LOCAL — RÉALISATIONS
========================================= */
body{
  background:#020617;
  color:#e5e7eb;
  font-family:Inter, system-ui, sans-serif;
}

.wrap{
  max-width:1200px;
  margin:70px auto;
  padding:0 20px 80px;
}

h1{
  font-size:2.4rem;
  text-align:center;
  margin-bottom:10px;
}

.subtitle{
  text-align:center;
  opacity:.9;
  margin-bottom:50px;
}

.grid{
  display:grid;
  grid-template-columns:repeat(auto-fit,minmax(320px,1fr));
  gap:28px;
}

.card{
  background:linear-gradient(160deg,#0b3c5d,#020617);
  border-radius:22px;
  padding:26px;
  box-shadow:0 18px 45px rgba(0,0,0,.45);
  transition:transform .3s ease;
}

.card:hover{
  transform:translateY(-6px);
}

.tag{
  display:inline-block;
  background:#f5a623;
  color:#000;
  font-weight:800;
  padding:6px 14px;
  border-radius:999px;
  font-size:.75rem;
  margin-bottom:12px;
}

.card h3{
  margin-bottom:10px;
}

.card p{
  line-height:1.6;
  opacity:.95;
}

.date{
  margin-top:14px;
  font-size:.8rem;
  opacity:.75;
}

.cta-box{
  margin-top:70px;
  text-align:center;
  background:rgba(255,255,255,.05);
  padding:40px;
  border-radius:22px;
}

.cta-box a{
  display:inline-block;
  background:#f5a623;
  color:#000;
  font-weight:900;
  padding:16px 26px;
  border-radius:14px;
  text-decoration:none;
  margin-top:16px;
}
</style>

<main class="wrap">
  <h1>Nos réalisations & impacts</h1>
  <p class="subtitle">
    Formations réalisées, organisations accompagnées et impacts concrets
  </p>

  <section class="grid">
    <?php if (count($realisations) === 0): ?>
      <p>Aucune réalisation publiée pour le moment.</p>
    <?php endif; ?>

    <?php foreach ($realisations as $r): ?>
      <div class="card">
        <span class="tag"><?= strtoupper(htmlspecialchars($r['type'])); ?></span>

        <h3><?= htmlspecialchars($r['titre']); ?></h3>

        <p><?= nl2br(htmlspecialchars($r['description'])); ?></p>

        <div class="date">
          📅 <?= date('d/m/Y', strtotime($r['created_at'])); ?>
        </div>
      </div>
    <?php endforeach; ?>
  </section>

  <div class="cta-box">
    <h2>Vous êtes une entreprise ou une organisation ?</h2>
    <p>
      IBIG EDUFORM conçoit et déploie des formations sur mesure,
      orientées résultats et impact terrain.
    </p>
    <a href="entreprises.php">Découvrir nos offres entreprises</a>
  </div>
</main>

<?php include __DIR__ . '/partials/footer.php'; ?>
