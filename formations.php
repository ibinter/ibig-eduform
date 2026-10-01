<?php
require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/core/referral.php';
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
$pdo = Database::connect();
referral_capture($pdo); // capte ?ref=CODE (parrainage) AVANT toute sortie

$pageTitle    = "Formations professionnelles certifiantes 2026 – IBIG EDUFORM";
$ogDesc       = "Découvrez les formations professionnelles certifiantes IBIG EDUFORM 2026 : management, comptabilité, RH, QHSE, logistique, SAP, intelligence artificielle. Présentiel à Abidjan ou en ligne. Certifications reconnues dans 17 pays OHADA.";
$pageKeywords = "formations professionnelles 2026 Abidjan, formation certifiante en ligne Afrique, formation management Côte d'Ivoire, formation comptabilité OHADA, formation RH Abidjan, formation SAP Afrique, IBIG EDUFORM programmes";
include __DIR__ . '/partials/header.php';

/* =====================================================
   FORMAT DATE FR (SANS PROBLÈME D’ENCODAGE)
===================================================== */
$fmtDate = new IntlDateFormatter(
    'fr_FR',
    IntlDateFormatter::LONG,
    IntlDateFormatter::NONE
);

/* =====================================================
   FILTRE PAR MOIS (SOURCE = date_debut)
===================================================== */
$moisSelectionne = isset($_GET['mois']) ? (int)$_GET['mois'] : 0;

$sql = "
      SELECT
      id,
      code,
      titre,
      domaine,
      description,
      duree,
      date_debut,
      tarif_en_ligne,
      tarif_presentiel,
      type_certificat,
      is_samedi_pro
    FROM formations
    WHERE statut = 'active'
      AND annee = YEAR(CURDATE())
      AND date_debut IS NOT NULL
      AND date_debut >= CURDATE()
      AND YEAR(date_debut) = YEAR(CURDATE())
";

$params = [];

if ($moisSelectionne >= 1 && $moisSelectionne <= 12) {
  $sql .= " AND date_debut IS NOT NULL AND MONTH(date_debut) = :mois";
  $params['mois'] = $moisSelectionne;
}

$sql .= " ORDER BY date_debut ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$formations = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =====================================================
   MOIS DISPONIBLES
===================================================== */
$moisDisponibles = [
  1  => 'Janvier',
  2  => 'Février',
  3  => 'Mars',
  4  => 'Avril',
  5  => 'Mai',
  6  => 'Juin',
  7  => 'Juillet',
  8  => 'Août',
  9  => 'Septembre',
  10 => 'Octobre',
  11 => 'Novembre',
  12 => 'Décembre'
];
?>

<style>
/* =========================
   BASE
========================= */
body{
  background:#020617;
  color:#e5e7eb;
  font-family:Inter,system-ui,sans-serif;
}

/* =========================
   TITRE PAGE
========================= */
.page-title{
  text-align:center;
  margin:80px 0 30px;
}
.page-title h1{
  font-size:2.6rem;
  font-weight:900;
  color:#ffffff;
}

/* =========================
   FILTRE
========================= */
.filter{
  text-align:center;
  margin-bottom:40px;
}
.filter select{
  padding:14px 18px;
  border-radius:12px;
  font-weight:700;
  border:none;
  background:#0b3c5d;
  color:#ffffff;
}

/* =========================
   GRID
========================= */
.grid{
  display:grid;
  grid-template-columns:repeat(auto-fit,minmax(320px,1fr));
  gap:28px;
  max-width:1200px;
  margin:auto;
  padding:0 20px 100px;
}

/* =========================
   CARD
========================= */
.card{
  background:linear-gradient(160deg,#0b3c5d,#020617);
  border-radius:18px;
  padding:26px;
  box-shadow:0 12px 35px rgba(10,77,166,.35);
  display:flex;
  flex-direction:column;
  border:1px solid rgba(255,255,255,.08);
  transition:transform .25s ease, box-shadow .25s ease;
}
.card:hover{
  transform:translateY(-6px);
  box-shadow:0 20px 50px rgba(10,77,166,.45);
}

/* =========================
   BADGE CERTIFICAT
========================= */
.badge{
  background:linear-gradient(90deg,#0a4da6,#0b3c5d);
  color:#ffffff;
  padding:6px 14px;
  border-radius:999px;
  font-size:.75rem;
  font-weight:900;
  align-self:flex-start;
}

/* =========================
   TEXTE
========================= */
.card h3{
  margin:14px 0 10px;
  color:#ffffff;
}
.card p{
  font-size:.95rem;
  line-height:1.6;
  opacity:.9;
  flex:1;
}

.meta{
  margin:14px 0;
  font-size:.9rem;
  color:#cbd5f5;
}

.price{
  margin:14px 0;
  font-weight:800;
  color:#ffffff;
}

/* =========================
   ACTIONS
========================= */
.actions{
  display:flex;
  gap:12px;
  margin-top:16px;
}

.actions a{
  flex:1;
  text-align:center;
  padding:12px;
  border-radius:10px;
  font-weight:800;
  text-decoration:none;
  transition:all .2s ease;
}

/* =========================
   BOUTON "VOIR DÉTAILS" – FIX LISIBILITÉ
========================= */
.btn-secondary{
  background: rgba(255,255,255,0.08);
  border: 2px solid #38bdf8;
  color: #ffffff;
  font-weight: 800;
}

.btn-secondary:hover{
  background: #38bdf8;
  color: #020617;
}

/* Préinscription */
.btn-primary{
  background:linear-gradient(135deg,#ff2e2e,#e60000);
  color:#ffffff;
  box-shadow:0 6px 20px rgba(255,46,46,.4);
}
.btn-primary:hover{
  background:#ff1a1a;
}

@media (max-width: 640px){
  .page-title h1{
    font-size:2rem;
  }
  .card{
    padding:22px;
  }
}

.price{
  margin:14px 0;
  display:flex;
  flex-direction:column;
  gap:8px;
}

.price-row{
  display:grid;
  grid-template-columns: 26px 1fr auto;
  align-items:center;
  column-gap:10px;
  font-weight:700;
  color:#ffffff;
}

.price-icon{
  text-align:center;
}

.price-label{
  opacity:.85;
}

.price-value{
  font-weight:900;
  white-space:nowrap;
}

.meta{
  margin:14px 0;
  display:flex;
  flex-direction:column;
  gap:6px;
  font-size:.9rem;
  color:#cbd5f5;
}

.meta-row{
  display:grid;
  grid-template-columns: 24px 70px 1fr;
  align-items:center;
  column-gap:8px;
}

.meta-icon{
  text-align:center;
}

.meta-label{
  opacity:.8;
  white-space:nowrap;
}

.meta-value{
  font-weight:600;
  color:#ffffff;
}

/* FORCE CENTRAGE TITRE FORMATIONS (DESKTOP + MOBILE) */
.page-title{
  display: block !important;
  text-align: center !important;
}

.page-title h1{
  display: block !important;
  width: 100% !important;
  text-align: center !important;
  margin: 0 auto !important;
}

.page-title p{
  display: block !important;
  text-align: center !important;
  margin: 20px auto 0 !important;
  max-width: 900px;
}

.hero-header{
  display: flex;
  justify-content: center;
}

.page-title{
  text-align: center;
}
/* ===============================
   FORMATIONS STANDARD
=============================== */
.card.formation-standard{
  border:1px solid rgba(255,255,255,.08);
  background:linear-gradient(160deg,#0b3c5d,#020617);
}

/* ===============================
   FORMATIONS SAMEDI PRO
=============================== */
.card.samedi-pro{
  border:2px solid #22c55e;
  background:linear-gradient(160deg,#0f3d2e,#020617);
  box-shadow:0 0 0 1px rgba(34,197,94,.35),
              0 14px 40px rgba(0,0,0,.45);
  position:relative;
}

.card.samedi-pro::before{
  content:"SAMEDI PRO";
  position:absolute;
  top:14px;
  right:-36px;
  background:#22c55e;
  color:#022c22;
  font-weight:900;
  font-size:.7rem;
  padding:6px 48px;
  transform:rotate(8deg);
}

/* Bouton réservation */
.samedi-pro-btn{
  background:linear-gradient(135deg,#22c55e,#16a34a);
  color:#022c22;
}
/* =========================
   BOUTON VOIR DÉTAILS (FIX LISIBILITÉ)
========================= */
.btn-secondary{
  background: rgba(255,255,255,0.12);
  color: #ffffff;
  border: 2px solid rgba(255,255,255,0.35);
  font-weight: 900;
}

.btn-secondary:hover{
  background: rgba(255,255,255,0.25);
  color: #ffffff;
  border-color: #ffffff;
  transform: translateY(-2px);
}

.btn-secondary{
  background: transparent;
  color: #38bdf8;
  border: 2px solid #38bdf8;
  font-weight: 900;
}

.btn-secondary:hover{
  background: #38bdf8;
  color: #020617;
}

/* ======================================
   FIX FINAL – BOUTONS D’ACTION (GRILLE)
====================================== */

.actions{
  display:flex;
  gap:12px;
  margin-top:18px;
  z-index:5;
}

.actions a{
  flex:1;
  min-height:44px;
  display:flex;
  align-items:center;
  justify-content:center;
  text-align:center;
  font-weight:900;
  font-size:.9rem;
  border-radius:10px;
  text-decoration:none;
  white-space:nowrap;
}

/* VOIR DÉTAILS */
.actions .btn-secondary{
  background:rgba(255,255,255,0.12);
  border:2px solid #38bdf8;
  color:#ffffff;
}

.actions .btn-secondary:hover{
  background:#38bdf8;
  color:#020617;
}

/* PRÉINSCRIPTION (STANDARD) */
.actions .btn-primary{
  background:linear-gradient(135deg,#ff2e2e,#e60000);
  color:#ffffff;
}

.actions .btn-primary:hover{
  background:#ff1a1a;
}

/* SAMEDI PRO – RÉSERVATION */
.actions .samedi-pro-btn{
  background:linear-gradient(135deg,#22c55e,#16a34a);
  color:#022c22;
}

.actions .samedi-pro-btn:hover{
  filter:brightness(1.05);
}

/* MOBILE */
@media(max-width:520px){
  .actions{
    flex-direction:column;
  }
}

/* =================================================
   FIX CRITIQUE – AFFICHAGE DES BOUTONS (CARDS)
================================================= */

/* La carte NE DOIT PAS masquer son contenu */
.card{
  position: relative;
  overflow: visible !important;
}

/* Zone actions toujours visible */
.actions{
  position: relative;
  z-index: 10;
  display: flex;
  gap: 12px;
  margin-top: 20px;
}

/* Boutons visibles quoi qu’il arrive */
.actions a{
  flex: 1;
  min-height: 44px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 900;
  font-size: .9rem;
  border-radius: 10px;
  text-decoration: none;
  opacity: 1 !important;
  visibility: visible !important;
}

/* Voir détails */
.actions .btn-secondary{
  background: rgba(255,255,255,.15);
  border: 2px solid #38bdf8;
  color: #ffffff;
}

/* Préinscription standard */
.actions .btn-primary{
  background: linear-gradient(135deg,#ff2e2e,#e60000);
  color: #ffffff;
}

/* Réservation SAMEDI PRO */
.actions .samedi-pro-btn{
  background: linear-gradient(135deg,#22c55e,#16a34a);
  color: #022c22;
}

/* Mobile */
@media(max-width:520px){
  .actions{
    flex-direction: column;
  }
}

</style>

<main>

<section class="hero-header">
  <div class="page-title">
    <h1>Formations professionnelles 2026</h1>
    <p>Catalogue officiel des formations professionnelles IBIG EDUFORM…</p>
  </div>
</section>

<!-- FILTRE PAR MOIS -->
<div class="filter">
  <form method="get">
    <select name="mois" onchange="this.form.submit()">
      <option value="0">&#x1F4C5; Toutes les formations</option>
      <?php foreach ($moisDisponibles as $num => $label): ?>
        <option value="<?= $num ?>" <?= $num === $moisSelectionne ? 'selected' : '' ?>>
          <?= htmlspecialchars($label); ?>
        </option>
      <?php endforeach; ?>
    </select>
  </form>
</div>

<!-- LISTE DES FORMATIONS -->
<section class="grid">

<?php if (empty($formations)): ?>

  <p style="grid-column:1/-1;text-align:center;opacity:.8;">
    Aucune formation programmée pour ce mois.
  </p>

<?php else: foreach ($formations as $f): ?>

  <div class="card <?= !empty($f['is_samedi_pro']) ? 'samedi-pro' : 'formation-standard'; ?>">

  <!-- BADGE -->
  <span class="badge">
    <?= !empty($f['is_samedi_pro'])
        ? '&#x1F7E2; SAMEDI PRO'
        : '&#x1F393; ' . htmlspecialchars($f['type_certificat'], ENT_QUOTES, 'UTF-8'); ?>
  </span>

  <!-- TITRE -->
  <h3><?= htmlspecialchars($f['titre'], ENT_QUOTES, 'UTF-8'); ?></h3>

  <!-- DESCRIPTION -->
  <p><?= nl2br(htmlspecialchars($f['description'], ENT_QUOTES, 'UTF-8')); ?></p>

  <!-- META -->
  <div class="meta">
    <div class="meta-row">
      <span class="meta-icon">&#x1F4C5;</span>
      <span class="meta-label">Début</span>
      <span class="meta-value"><?= $fmtDate->format(strtotime($f['date_debut'])); ?></span>
    </div>

    <div class="meta-row">
      <span class="meta-icon">&#x23F1;</span>
      <span class="meta-label">Durée</span>
      <span class="meta-value"><?= htmlspecialchars($f['duree'], ENT_QUOTES, 'UTF-8'); ?></span>
    </div>

    <div class="meta-row">
      <span class="meta-icon">&#x1F4C2;</span>
      <span class="meta-label">Domaine</span>
      <span class="meta-value"><?= htmlspecialchars($f['domaine'], ENT_QUOTES, 'UTF-8'); ?></span>
    </div>
  </div>

  <!-- PRIX -->
  <?php $promo = promo_earlybird($f); ?>
  <div class="price">

    <?php if (!empty($f['is_samedi_pro'])): ?>

      <?php $tp = (int)$f['tarif_presentiel']; ?>
      <div class="price-row">
        <span class="price-icon">&#x1F3EB;</span>
        <span class="price-label">Présentiel</span>
        <span class="price-value">
          <?php if (!empty($promo['eligible'])): ?>
            <span style="text-decoration:line-through;opacity:.55;font-size:.88em;margin-right:4px"><?= number_format($tp + PROMO_REMISE_PRESENTIEL, 0, ',', ' '); ?></span>
            <strong><?= number_format($tp, 0, ',', ' '); ?> FCFA</strong>
          <?php else: ?>
            <?= number_format($tp, 0, ',', ' '); ?> FCFA
          <?php endif; ?>
        </span>
      </div>

    <?php else: ?>

      <?php $tl = (int)$f['tarif_en_ligne']; $tp = (int)$f['tarif_presentiel']; ?>

      <div class="price-row">
        <span class="price-icon">&#x1F4BB;</span>
        <span class="price-label">En ligne</span>
        <span class="price-value">
          <?php if (!empty($promo['eligible'])): ?>
            <span style="text-decoration:line-through;opacity:.55;font-size:.88em;margin-right:4px"><?= number_format($tl + PROMO_REMISE_EN_LIGNE, 0, ',', ' '); ?></span>
            <strong><?= number_format($tl, 0, ',', ' '); ?> FCFA</strong>
          <?php else: ?>
            <?= number_format($tl, 0, ',', ' '); ?> FCFA
          <?php endif; ?>
        </span>
      </div>

      <div class="price-row">
        <span class="price-icon">&#x1F3EB;</span>
        <span class="price-label">Présentiel</span>
        <span class="price-value">
          <?php if (!empty($promo['eligible'])): ?>
            <span style="text-decoration:line-through;opacity:.55;font-size:.88em;margin-right:4px"><?= number_format($tp + PROMO_REMISE_PRESENTIEL, 0, ',', ' '); ?></span>
            <strong><?= number_format($tp, 0, ',', ' '); ?> FCFA</strong>
          <?php else: ?>
            <?= number_format($tp, 0, ',', ' '); ?> FCFA
          <?php endif; ?>
        </span>
      </div>

    <?php endif; ?>

  </div>

  <!-- BADGE RÉDUCTION -->
  <?php if (!empty($promo['eligible'])): ?>
  <div style="margin-top:10px;background:linear-gradient(90deg,#e67e22,#f39c12);color:#fff;border-radius:8px;padding:8px 12px;font-size:0.85rem;font-weight:700;display:flex;align-items:center;gap:6px;">
    <span>&#x1F3F7;&#xFE0F;</span>
    <?php if (!empty($f['is_samedi_pro'])): ?>
      <span>R&eacute;duction &minus;25&nbsp;000 FCFA &mdash; jusqu&rsquo;au <?= date('d/m/Y', $promo['deadline']); ?></span>
    <?php else: ?>
      <span>R&eacute;duction &minus;20&nbsp;000 FCFA (en ligne) &bull; &minus;25&nbsp;000 FCFA (pr&eacute;sentiel) &mdash; jusqu&rsquo;au <?= date('d/m/Y', $promo['deadline']); ?></span>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <!-- ACTIONS (LES BOUTONS AVAIENT DISPARU ICI) -->
  <div class="actions">

    <!-- VOIR DÉTAILS -->
    <a href="formation.php?id=<?= (int)$f['id']; ?>"
       class="btn-secondary">
      Voir détails
    </a>

    <?php if (!empty($f['is_samedi_pro'])): ?>

      <!-- RÉSERVATION SAMEDI PRO -->
      <a href="preinscription.php?formation_id=<?= (int)$f['id']; ?>"
         class="samedi-pro-btn">
        Réserver ma place
      </a>

    <?php else: ?>

      <!-- PRÉINSCRIPTION CLASSIQUE (formulaire interne) -->
    <a href="preinscription.php?formation_id=<?= (int)$f['id']; ?>"
       class="btn-primary">
       Préinscription
    </a>

    <?php endif; ?>

  </div>

</div>

<?php endforeach; endif; ?>

</section>
</main>

<?php include __DIR__ . '/partials/footer.php'; ?>
