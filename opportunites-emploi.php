<?php
declare(strict_types=1);

require_once __DIR__ . '/core/config.php';
require_once __DIR__ . '/core/database.php';

$pageTitle = "Opportunités & Emploi – IBIG EDUFORM";
include __DIR__ . '/partials/header.php';

$pdo = Database::connect();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

/* ============================================================
   HELPERS (SAFE)
============================================================ */
function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function is_non_empty_string($v): bool { return is_string($v) && trim($v) !== ''; }

/* ============================================================
   FILTRES (INPUT)
============================================================ */
$q    = trim((string)($_GET['q'] ?? ''));
$type = trim((string)($_GET['type'] ?? ''));
$pays = (int)($_GET['pays'] ?? 0);

$allowedTypes = ['CDI','CDD','Stage','Freelance','Mission'];

/* ============================================================
   WHERE + PARAMS (UNE SEULE FOIS -> ZERO HY093)
============================================================ */
$whereParts = [];
$params = [];

/* STATUT */
$whereParts[] = "o.statut = ?";
$params[] = 'active';

/* Recherche */
if ($q !== '') {
  $whereParts[] = "(o.titre LIKE ? OR o.lieu LIKE ? OR e.nom_legal LIKE ?)";
  $like = '%' . $q . '%';
  $params[] = $like;
  $params[] = $like;
  $params[] = $like;
}

/* Type */
if ($type !== '' && in_array($type, $allowedTypes, true)) {
  $whereParts[] = "o.type_contrat = ?";
  $params[] = $type;
}

/* Pays */
if ($pays > 0) {
  $whereParts[] = "o.pays_id = ?";
  $params[] = $pays;
}

$whereSql = 'WHERE ' . implode(' AND ', $whereParts);

/* ============================================================
   LISTE PAYS
============================================================ */
$paysList = [];
try {
  $paysList = $pdo->query("
    SELECT id, nom
    FROM pays
    ORDER BY nom
  ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  $paysList = [];
}

/* ============================================================
   OFFRES
============================================================ */
$stmt = $pdo->prepare("
  SELECT
  o.id,
  o.slug,
  o.titre,
  o.type,
  o.lieu,
  o.description,
  o.created_at,
  o.entreprise
  FROM opportunites_emploi o
  $whereSql
  ORDER BY o.created_at DESC, o.id DESC
");

$stmt->execute($params);
$offres = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ============================================================
   META (optionnel mais utile)
============================================================ */
$totalOffres = is_array($offres) ? count($offres) : 0;
$activeFilters = [
  'q'    => $q !== '',
  'type' => $type !== '',
  'pays' => $pays > 0,
];
$hasFilter = in_array(true, $activeFilters, true);
?>

<!-- ============================================================
     PAGE WRAPPER (SCOPED CSS = NE CASSE PAS LE FOOTER GLOBAL)
============================================================ -->
<div class="opps-page">

  <!-- HERO -->
  <header class="opps-hero">
    <div class="opps-container">
      <div class="opps-hero-inner">
        <div class="opps-hero-left">
          <h1 class="opps-h1">Opportunités &amp; Emploi</h1>
          <p class="opps-sub">
            Explorez les opportunités d’affaires, d’emplois, de stages, de missions et de collaborations disponibles au sein de IBIG SARL ou proposées par notre réseau d’entreprises partenaires.
          </p>

          <div class="opps-hero-stats">
            <div class="opps-stat">
              <div class="opps-stat-num"><?= (int)$totalOffres; ?></div>
              <div class="opps-stat-label">offre(s) trouvée(s)</div>
            </div>
            <div class="opps-stat">
              <div class="opps-stat-label">statut filtré</div>
            </div>
          </div>
        </div>

        <div class="opps-hero-right">
          <div class="opps-hero-card">
            <div class="opps-hero-card-title">Conseil</div>
            <div class="opps-hero-card-text">
              Utilisez le filtre Pays + Type pour affiner rapidement vos résultats.
              Les nouvelles offres apparaissent en haut.
            </div>
          </div>
        </div>
      </div>
    </div>
  </header>

  <!-- CONTENU -->
  <section class="opps-section">
    <div class="opps-container">

      <!-- FILTRES -->
      <form method="get" class="opps-filters" autocomplete="off">

        <div class="opps-field">
          <label class="opps-label" for="q">Recherche</label>
          <div class="opps-input-wrap">
            <!-- CORRECTION EMOJI (loupe) -->
            <span class="opps-ico" aria-hidden="true">&#x1F50E;</span>
            <input
              id="q"
              type="text"
              name="q"
              class="opps-input"
              placeholder="Titre, lieu ou entreprise"
              value="<?= h($q); ?>"
            >
          </div>
        </div>

        <div class="opps-field">
          <label class="opps-label" for="type">Type de contrat</label>
          <div class="opps-select-wrap">
            <select id="type" name="type" class="opps-select">
              <option value="">Tous</option>
              <?php foreach ($allowedTypes as $t): ?>
                <option value="<?= h($t); ?>" <?= $type === $t ? 'selected' : ''; ?>>
                  <?= h($t); ?>
                </option>
              <?php endforeach; ?>
            </select>
            <!-- CORRECTION FLECHE SELECT -->
            <span class="opps-select-arrow" aria-hidden="true">&#x25BE;</span>
          </div>
        </div>

        <div class="opps-field">
          <label class="opps-label" for="pays">Pays</label>
          <div class="opps-select-wrap">
            <select id="pays" name="pays" class="opps-select">
              <option value="">Tous</option>
              <?php foreach ($paysList as $p): ?>
                <option value="<?= (int)$p['id']; ?>" <?= ((int)$pays === (int)$p['id']) ? 'selected' : ''; ?>>
                  <?= h($p['nom']); ?>
                </option>
              <?php endforeach; ?>
            </select>
            <!-- CORRECTION FLECHE SELECT -->
            <span class="opps-select-arrow" aria-hidden="true">&#x25BE;</span>
          </div>
        </div>

        <div class="opps-actions">
          <button type="submit" class="opps-btn opps-btn-primary">Filtrer</button>
          <a href="opportunites-emploi.php" class="opps-btn opps-btn-ghost">Réinitialiser</a>
        </div>

        <?php if ($hasFilter): ?>
          <div class="opps-chips" aria-label="Filtres actifs">
            <?php if ($q !== ''): ?>
              <span class="opps-chip">Recherche : <strong><?= h($q); ?></strong></span>
            <?php endif; ?>
            <?php if ($type !== ''): ?>
              <span class="opps-chip">Type : <strong><?= h($type); ?></strong></span>
            <?php endif; ?>
            <?php if ($pays > 0): ?>
              <?php
                $paysLabel = '';
                foreach ($paysList as $pp) { if ((int)$pp['id'] === (int)$pays) { $paysLabel = (string)$pp['nom']; break; } }
              ?>
              <span class="opps-chip">Pays : <strong><?= h($paysLabel); ?></strong></span>
            <?php endif; ?>
          </div>
        <?php endif; ?>

      </form>

      <!-- LISTE -->
      <?php if (!$offres): ?>
        <div class="opps-empty">
          <div class="opps-empty-ico">!</div>
          <div>
            <div class="opps-empty-title">Aucune offre disponible</div>
            <div class="opps-empty-sub">
              Aucune offre ne correspond à vos critères pour le moment.
              <?php if ($hasFilter): ?>
                Essayez “Réinitialiser” ou changez le pays / type.
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php else: ?>

        <div class="opps-grid">
          <?php foreach ($offres as $o): ?>
            <?php
              $titre = (string)($o['titre'] ?? '');
              $entreprise = (string)($o['entreprise'] ?? '—');
              $lieu = (string)($o['lieu'] ?? '');
              $typeContrat = (string)($o['type'] ?? '');
              $desc = (string)($o['description'] ?? '');
              $publieLe = (string)($o['created_at'] ?? '');
              $dateAff = $publieLe ? date('d/m/Y', strtotime($publieLe)) : '—';
            ?>
            <article class="opps-card">

              <div class="opps-card-top">
                <h3 class="opps-title"><?= h($titre); ?></h3>

                <div class="opps-meta">
                  <?php if ($typeContrat !== ''): ?>
                    <span class="opps-pill"><?= h($typeContrat); ?></span>
                  <?php endif; ?>

                  <?php if ($lieu !== ''): ?>
                    <span class="opps-pill opps-pill-soft"><?= h($lieu); ?></span>
                  <?php endif; ?>
                </div>

                <div class="opps-company"><?= h($entreprise); ?></div>

                <p class="opps-excerpt">
                  <?= nl2br(h(mb_strimwidth($desc, 0, 190, '…'))); ?>
                </p>
              </div>

              <div class="opps-card-bottom">
                <small class="opps-date">Publiée le <?= h($dateAff); ?></small>

                <a href="/offre/<?= h($o['slug']); ?>" class="opps-btn opps-btn-outline">
                  Voir l’offre
                </a>
              </div>

            </article>
          <?php endforeach; ?>
        </div>

      <?php endif; ?>

    </div>
  </section>

</div>

<style>
/* ============================================================
   OPPORTUNITÉS & EMPLOI — CSS SCOPÉ (NE CASSE PAS LE FOOTER)
   - Aucun .container / .btn global
   - Responsive mobile-first
   - Zéro overflow horizontal
============================================================ */

/* Sécurité : évite débordements horizontaux */
.opps-page{ overflow-x:hidden; }
.opps-page *{ box-sizing:border-box; }
.opps-container{ max-width:1200px; margin:0 auto; padding:0 16px; }

/* HERO */
.opps-hero{
  background:
    radial-gradient(1200px 520px at 15% 15%, rgba(255,255,255,.18), rgba(255,255,255,0)),
    linear-gradient(135deg,#0b3b82,#0a2f66);
  color:#fff;
  padding:58px 0 48px;
}
.opps-hero-inner{
  display:grid;
  grid-template-columns: 1.4fr .9fr;
  gap:18px;
  align-items:stretch;
}
.opps-h1{
  margin:0 0 10px;
  font-size:36px;
  line-height:1.12;
  letter-spacing:.2px;
}
.opps-sub{
  margin:0;
  max-width:820px;
  opacity:.95;
  font-size:15.5px;
  line-height:1.55;
}
.opps-hero-stats{
  margin-top:18px;
  display:flex;
  flex-wrap:wrap;
  gap:10px;
}
.opps-stat{
  background:rgba(255,255,255,.12);
  border:1px solid rgba(255,255,255,.18);
  padding:10px 12px;
  border-radius:14px;
  min-width:170px;
}
.opps-stat-num{
  font-weight:900;
  font-size:18px;
  line-height:1.1;
}
.opps-stat-label{
  opacity:.92;
  font-size:12.5px;
  margin-top:3px;
}
.opps-hero-card{
  height:100%;
  background:rgba(15,23,42,.22);
  border:1px solid rgba(255,255,255,.18);
  border-radius:18px;
  padding:14px;
  backdrop-filter: blur(6px);
}
.opps-hero-card-title{
  font-weight:900;
  margin-bottom:8px;
}
.opps-hero-card-text{
  opacity:.95;
  font-size:13.8px;
  line-height:1.5;
}

/* SECTION */
.opps-section{ padding:34px 0 56px; background:#f6f8fc; }

/* FILTRES (CARD) */
.opps-filters{
  background:#fff;
  border:1px solid rgba(15,23,42,.08);
  border-radius:18px;
  padding:14px;
  box-shadow:0 18px 40px rgba(0,0,0,.07);
  display:grid;
  grid-template-columns: 1fr 1fr 1fr;
  gap:12px;
  margin-top:-26px;
  position:relative;
}
.opps-field{ min-width:0; }
.opps-label{
  display:block;
  font-size:12px;
  font-weight:900;
  color:#0f172a;
  margin:4px 0 8px;
}

/* input */
.opps-input-wrap{
  position:relative;
}
.opps-ico{
  position:absolute;
  left:12px;
  top:50%;
  transform:translateY(-50%);
  font-size:14px;
  opacity:.7;
}
.opps-input{
  width:100%;
  min-width:0;
  border:1px solid #d7dde7;
  background:#fff;
  padding:12px 12px 12px 34px;
  border-radius:12px;
  font-size:15px;
  outline:none;
  transition: box-shadow .2s ease, border-color .2s ease;
}
.opps-input:focus{
  border-color: rgba(11,59,130,.55);
  box-shadow: 0 0 0 4px rgba(11,59,130,.12);
}

/* select */
.opps-select-wrap{
  position:relative;
}
.opps-select{
  width:100%;
  min-width:0;
  appearance:none;
  border:1px solid #d7dde7;
  background:#fff;
  padding:12px 34px 12px 12px;
  border-radius:12px;
  font-size:15px;
  outline:none;
  transition: box-shadow .2s ease, border-color .2s ease;
}
.opps-select:focus{
  border-color: rgba(11,59,130,.55);
  box-shadow: 0 0 0 4px rgba(11,59,130,.12);
}
.opps-select-arrow{
  position:absolute;
  right:12px;
  top:50%;
  transform:translateY(-50%);
  opacity:.7;
  font-size:13px;
  pointer-events:none;
}

/* Actions */
.opps-actions{
  grid-column: 1 / -1;
  display:flex;
  gap:12px;
  flex-wrap:wrap;
  margin-top:2px;
}

/* Buttons (scopés) */
.opps-btn{
  display:inline-flex;
  align-items:center;
  justify-content:center;
  gap:8px;
  padding:12px 16px;
  border-radius:14px;
  text-decoration:none;
  font-size:15px;
  font-weight:900;
  line-height:1;
  border:1px solid transparent;
  user-select:none;
  white-space:nowrap;
  transition: transform .08s ease, box-shadow .2s ease, opacity .2s ease, background .2s ease;
}
.opps-btn:active{ transform: scale(.99); }

.opps-btn-primary{
  background:#dc2626;
  color:#fff;
  box-shadow:0 14px 30px rgba(220,38,38,.25);
}
.opps-btn-primary:hover{ opacity:.96; }
.opps-btn-ghost{
  background:#f1f5f9;
  color:#0f172a;
  border-color:#e2e8f0;
}
.opps-btn-ghost:hover{ background:#eaf0f7; }
.opps-btn-outline{
  background:#fff;
  color:#0f172a;
  border-color:#d7dde7;
}
.opps-btn-outline:hover{ box-shadow:0 10px 24px rgba(15,23,42,.08); }

/* Chips */
.opps-chips{
  grid-column: 1 / -1;
  display:flex;
  gap:8px;
  flex-wrap:wrap;
  margin-top:2px;
}
.opps-chip{
  display:inline-flex;
  gap:6px;
  align-items:center;
  padding:8px 10px;
  border-radius:999px;
  border:1px solid rgba(15,23,42,.10);
  background:#fff;
  font-size:12.5px;
  color:#0f172a;
}

/* Empty state */
.opps-empty{
  margin-top:18px;
  background:#fff;
  border:1px dashed rgba(15,23,42,.18);
  border-radius:18px;
  padding:22px;
  display:flex;
  gap:14px;
  align-items:flex-start;
  color:#0f172a;
}
.opps-empty-ico{
  width:38px;
  height:38px;
  border-radius:12px;
  display:flex;
  align-items:center;
  justify-content:center;
  font-weight:900;
  background: rgba(11,59,130,.10);
  color:#0b3b82;
  flex:0 0 auto;
}
.opps-empty-title{ font-weight:900; margin-bottom:4px; }
.opps-empty-sub{ color:#64748b; font-size:14px; }

/* Grid */
.opps-grid{
  margin-top:18px;
  display:grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap:18px;
}

/* Cards */
.opps-card{
  background:#fff;
  border:1px solid rgba(15,23,42,.08);
  border-radius:18px;
  padding:18px;
  box-shadow:0 18px 40px rgba(0,0,0,.06);
  display:flex;
  flex-direction:column;
  min-width:0;
}
.opps-title{
  margin:0 0 10px;
  font-size:18px;
  line-height:1.25;
  color:#0f172a;
}
.opps-meta{
  display:flex;
  flex-wrap:wrap;
  gap:8px;
  margin:0 0 10px;
}
.opps-pill{
  display:inline-flex;
  align-items:center;
  padding:6px 10px;
  border-radius:999px;
  font-size:12.5px;
  font-weight:900;
  background:rgba(220,38,38,.12);
  color:#991b1b;
}
.opps-pill-soft{
  background:rgba(11,59,130,.10);
  color:#0b3b82;
}
.opps-company{
  font-weight:900;
  color:#0f172a;
  margin:0 0 10px;
}
.opps-excerpt{
  margin:0;
  color:#334155;
  font-size:14.5px;
  line-height:1.6;
  overflow-wrap:anywhere;
}
.opps-card-bottom{
  margin-top:16px;
  padding-top:14px;
  border-top:1px solid rgba(15,23,42,.08);
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:12px;
  flex-wrap:wrap;
}
.opps-date{
  color:#64748b;
  font-size:12.5px;
}

/* Responsive */
@media (max-width: 980px){
  .opps-hero-inner{ grid-template-columns: 1fr; }
  .opps-hero-card{ display:none; }
}
@media (max-width: 900px){
  .opps-filters{ grid-template-columns: 1fr 1fr; }
}
@media (max-width: 640px){
  .opps-h1{ font-size:28px; }
  .opps-hero{ padding:52px 0 44px; }
  .opps-filters{ grid-template-columns: 1fr; margin-top:-22px; }
  .opps-actions{ flex-direction:column; }
  .opps-actions .opps-btn{ width:100%; }
  .opps-card-bottom{ flex-direction:column; align-items:flex-start; }
  .opps-card-bottom .opps-btn{ width:100%; text-align:center; }
}

/* WhatsApp float safety */
.whatsapp-float{ right:16px !important; max-width:calc(100vw - 32px); }
</style>

<?php include __DIR__ . '/partials/footer.php'; ?>