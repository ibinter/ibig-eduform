<?php
require_once __DIR__ . '/core/bootstrap.php';

$pageTitle = "Calendrier des formations – IBIG EDUFORM";
include __DIR__ . '/partials/header.php';

$pdo = Database::connect();

/* =========================
   RÉCUPÉRATION FORMATIONS
========================= */
$stmt = $pdo->query("
  SELECT
    f.id AS formation_id,
    f.titre,
    f.domaine,
    f.date_debut,
    f.date_fin,
    f.duree,
    f.mode,
    f.capacite,
    f.inscriptions_actuelles
  FROM formations f
  WHERE f.statut = 'active'
    AND f.date_debut IS NOT NULL
    AND COALESCE(f.date_fin, f.date_debut) >= CURDATE()
  ORDER BY f.date_debut ASC
");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$today = new DateTime(date('Y-m-d'));
?>

<style>
/* =========================
   BASE VISUELLE
========================= */
body{
  background:#f8fafc;
  color:#0f172a;
  font-family:Inter,system-ui,sans-serif;
}
.wrap{
  max-width:1180px;
  margin:80px auto;
  padding:0 20px 110px;
}

h1{
  text-align:center;
  color:#0b3c5d;
  font-size:2.2rem;
  margin-bottom:8px;
}
.subtitle{
  text-align:center;
  color:#64748b;
  font-size:.95rem;
  margin-bottom:46px;
}

/* =========================
   TABLE
========================= */
.table{
  width:100%;
  border-collapse:separate;
  border-spacing:0 14px;
}
thead th{
  padding:14px;
  font-size:.72rem;
  color:#64748b;
  text-transform:uppercase;
  letter-spacing:.06em;
  text-align:left;
}

tbody tr{
  background:linear-gradient(180deg,#ffffff,#f8fafc);
  border-radius:18px;
  box-shadow:0 14px 34px rgba(15,23,42,.08);
  transition:transform .15s ease, box-shadow .15s ease;
}
tbody tr:hover{
  transform:translateY(-3px);
  box-shadow:0 24px 54px rgba(15,23,42,.12);
}
tbody td{
  padding:16px 14px;
  font-size:.88rem;
  color:#334155;
}

/* TITRE FORMATION */
tbody td strong{
  font-size:.95rem;
  color:#0b3c5d;
  line-height:1.4;
}

/* DOMAINE */
tbody td:nth-child(2){
  font-weight:700;
  color:#2563eb;
  font-size:.85rem;
}

/* DATE */
tbody td:nth-child(3){
  font-weight:800;
  font-size:.85rem;
}

/* =========================
   BADGES
========================= */
.badge{
  padding:6px 14px;
  border-radius:999px;
  font-size:.65rem;
  font-weight:900;
  letter-spacing:.05em;
  text-transform:uppercase;
  display:inline-block;
  margin-right:6px;
}

.badge.a_venir{
  background:rgba(37,99,235,.12);
  color:#2563eb;
  border:1px solid rgba(37,99,235,.25);
}
.badge.ouvert{
  background:rgba(34,197,94,.14);
  color:#15803d;
  border:1px solid rgba(34,197,94,.28);
}
.badge.termine{
  background:rgba(100,116,139,.14);
  color:#475569;
}
.badge.places{
  background:rgba(245,158,11,.15);
  color:#92400e;
  border:1px solid rgba(245,158,11,.35);
}

/* =========================
   MODE
========================= */
.mode{
  padding:6px 14px;
  border-radius:999px;
  font-size:.65rem;
  font-weight:900;
  letter-spacing:.05em;
  text-transform:uppercase;
}
.mode.presentiel{
  background:#ecfeff;
  color:#0369a1;
  border:1px solid #38bdf8;
}
.mode.en_ligne{
  background:#f0fdf4;
  color:#15803d;
  border:1px solid #4ade80;
}
.mode.hybride{
  background:#fef9c3;
  color:#854d0e;
  border:1px solid #fde047;
}

/* =========================
   BOUTONS
========================= */
.btn{
  padding:10px 16px;
  border-radius:12px;
  font-size:.82rem;
  font-weight:900;
  text-decoration:none;
  display:inline-block;
}

.btn-primary{
  background:linear-gradient(135deg,#2563eb,#1e40af);
  color:#fff;
  box-shadow:0 10px 26px rgba(37,99,235,.35);
  transition:transform .15s ease, box-shadow .15s ease;
}
.btn-primary:hover{
  transform:translateY(-2px);
  box-shadow:0 18px 42px rgba(37,99,235,.45);
}
.btn-disabled{
  background:#e5e7eb;
  color:#9ca3af;
}

/* J-X */
small.note{
  display:inline-block;
  margin-top:6px;
  padding:6px 12px;
  background:rgba(245,158,11,.12);
  color:#92400e;
  font-size:.65rem;
  font-weight:900;
  border-radius:999px;
}

/* =========================
   MOBILE
========================= */
@media(max-width:900px){
  thead{display:none;}
  tbody tr{display:block;padding:18px;}
  tbody td{
    display:flex;
    justify-content:space-between;
    align-items:center;
    padding:8px 0;
    font-size:.85rem;
  }
  .btn-primary{
    width:100%;
    text-align:center;
    margin-top:10px;
  }
}
</style>

<main class="wrap">

<h1>Calendrier des formations 2026</h1>
<p class="subtitle">Formations ouvertes et planifiées — IBIG EDUFORM</p>

<table class="table">
<thead>
<tr>
  <th>Formation</th>
  <th>Domaine</th>
  <th>Début</th>
  <th>Durée</th>
  <th>Mode</th>
  <th>Statut</th>
  <th></th>
</tr>
</thead>
<tbody>

<?php foreach ($rows as $r): ?>
<?php
$dateDebut = new DateTime($r['date_debut']);
$dateFin   = !empty($r['date_fin']) ? new DateTime($r['date_fin']) : null;

/* STATUT */
$badge = 'a_venir';
$label = 'À VENIR';
if ($dateDebut <= $today) {
  $badge = 'ouvert';
  $label = 'OUVERT';
}
if ($dateFin && $dateFin < $today) {
  $badge = 'termine';
  $label = 'TERMINÉ';
}

/* J-X */
$joursAvant = (int)$today->diff($dateDebut)->format('%r%a');

/* PLACES */
$placesRestantes = function_exists('places_disponibles') ? places_disponibles($r) : null;

/* FERMETURE AUTO J-2 */
$fermetureAuto = ($joursAvant <= 2);
?>

<tr>
  <td><strong><?= htmlspecialchars($r['titre']); ?></strong></td>
  <td><?= htmlspecialchars($r['domaine']); ?></td>
  <td><?= $dateDebut->format('d/m/Y'); ?></td>
  <td><?= htmlspecialchars($r['duree'] ?? '—'); ?></td>
  <td>
    <span class="mode <?= htmlspecialchars($r['mode']); ?>">
      <?= ucfirst(str_replace('_',' ', $r['mode'])); ?>
    </span>
  </td>
  <td>
    <span class="badge <?= $badge; ?>"><?= $label; ?></span>
    <?php if ($placesRestantes !== null && $placesRestantes <= 5 && $placesRestantes > 0): ?>
      <span class="badge places">Places limitées</span>
    <?php endif; ?>
  </td>
  <td>
    <?php if (in_array($badge,['ouvert','a_venir'],true) && !$fermetureAuto): ?>
      <a href="preinscription.php?formation_id=<?= (int)$r['formation_id']; ?>" class="btn btn-primary">
        Préinscription
      </a>
      <?php if ($badge==='a_venir' && $joursAvant>0): ?>
        <small class="note">Démarrage dans <?= $joursAvant; ?> jours</small>
      <?php endif; ?>
    <?php else: ?>
      <span class="btn btn-disabled">Inscriptions closes</span>
    <?php endif; ?>
  </td>
</tr>
<?php endforeach; ?>

</tbody>
</table>

</main>

<?php include __DIR__ . '/partials/footer.php'; ?>
