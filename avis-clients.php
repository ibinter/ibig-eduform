<?php
require_once __DIR__ . '/core/bootstrap.php';

$pageTitle = "Avis & Témoignages – IBIG EDUFORM";
include __DIR__ . '/partials/header.php';

$pdo = Database::connect();

/* =========================
   AVIS CLIENTS (SQL)
========================= */
$stmt = $pdo->prepare("
  SELECT nom, ville, pays, secteur, texte
  FROM avis_clients
  WHERE statut = 'publie'
  ORDER BY created_at DESC
");
$stmt->execute();
$avis = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
body{
  background:#020617;
  color:#e5e7eb;
  font-family:Inter,system-ui,sans-serif;
}

/* HERO */
.hero{
  text-align:center;
  padding:140px 20px 80px;
}
.hero h1{
  font-size:3rem;
  font-weight:900;
  margin-bottom:18px;
  color:#ffffff;
}
.hero p{
  max-width:900px;
  margin:0 auto;
  font-size:1.1rem;
  line-height:1.8;
  color:#cbd5f5;
}

/* CONTAINER */
.container{
  max-width:1200px;
  margin:auto;
  padding:0 20px 120px;
}

/* CARD */
.card{
  background:linear-gradient(160deg,#0b3c5d,#020617);
  border-radius:20px;
  padding:30px;
  box-shadow:0 15px 45px rgba(10,77,166,.35);
  border:1px solid rgba(255,255,255,.08);
  display:flex;
  flex-direction:column;
}
.card p{
  line-height:1.7;
  margin-bottom:22px;
  flex:1;
}

/* CLIENT */
.client{
  display:flex;
  align-items:center;
  gap:14px;
}
.avatar{
  width:48px;
  height:48px;
  border-radius:50%;
  background:linear-gradient(135deg,#2563eb,#0b3c5d);
  display:flex;
  align-items:center;
  justify-content:center;
  font-weight:900;
  color:#ffffff;
}
.client-info strong{
  display:block;
  color:#ffffff;
}
.client-info span{
  font-size:.9rem;
  color:#cbd5f5;
}

/* SLIDER */
.slider-wrapper{
  position:relative;
  overflow:hidden;
}
.slider{
  display:flex;
  gap:32px;
  transition:transform .5s ease;
}
.slider .card{
  flex:0 0 calc(100% / 3 - 22px);
}

@media (max-width:1024px){
  .slider .card{ flex:0 0 calc(100% / 2 - 16px); }
}
@media (max-width:640px){
  .slider .card{ flex:0 0 100%; }
}

/* NAV */
.nav{
  position:absolute;
  top:50%;
  transform:translateY(-50%);
  background:rgba(15,23,42,.85);
  border:none;
  color:#fff;
  font-size:26px;
  padding:14px 18px;
  border-radius:50%;
  cursor:pointer;
  z-index:10;
}
.nav.prev{ left:-10px; }
.nav.next{ right:-10px; }
.nav:hover{ background:#2563eb; }
</style>

<main>

<section class="hero">
  <h1>Avis & Témoignages</h1>
  <p>
    Des parcours réels, des expériences variées et des résultats concrets
    après une formation professionnelle chez IBIG EDUFORM.
  </p>
</section>

<section class="container">
  <div class="slider-wrapper">

    <button class="nav prev">&#10094;</button>

    <div class="slider">
      <?php if(count($avis) > 0): ?>
        <?php foreach ($avis as $a): ?>
          <div class="card">
            <p>« <?= htmlspecialchars($a['texte']); ?> »</p>
            <div class="client">
              <div class="avatar">
                <?= strtoupper(mb_substr($a['nom'], 0, 1, 'UTF-8')); ?>
              </div>
              <div class="client-info">
                <strong><?= htmlspecialchars($a['nom']); ?></strong>
                <span><?= htmlspecialchars($a['ville']); ?> – <?= htmlspecialchars($a['pays']); ?></span>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p style="opacity:.7;">Aucun avis publié pour le moment.</p>
      <?php endif; ?>
    </div>

    <button class="nav next">&#10095;</button>

  </div>
</section>

<script>
const slider = document.querySelector('.slider');
const cards  = document.querySelectorAll('.slider .card');
const prev   = document.querySelector('.prev');
const next   = document.querySelector('.next');

let index = 0;
let gap = 32;
let cardWidth = cards.length ? cards[0].offsetWidth + gap : 0;

function cardsPerView(){
  if(window.innerWidth < 640) return 1;
  if(window.innerWidth < 1024) return 2;
  return 3;
}

function updateSlider(){
  slider.style.transform = `translateX(-${index * cardWidth}px)`;
}

next?.addEventListener('click', () => {
  if(index < cards.length - cardsPerView()){
    index++;
    updateSlider();
  }
});

prev?.addEventListener('click', () => {
  if(index > 0){
    index--;
    updateSlider();
  }
});

if(cards.length){
  setInterval(() => {
    index = (index < cards.length - cardsPerView()) ? index + 1 : 0;
    updateSlider();
  }, 6000);
}

window.addEventListener('resize', () => {
  if(cards.length){
    cardWidth = cards[0].offsetWidth + gap;
    updateSlider();
  }
});
</script>

</main>

<?php include __DIR__ . '/partials/footer.php'; ?>
