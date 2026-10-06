<?php
declare(strict_types=1);

/**
 * ============================================================
 * ADMIN – IMPORT PROGRAMME Q4 2026
 * Fichier : /admin/formations/import-q4-2026.php
 * ============================================================
 * Insère les 15 formations Oct–Déc 2026 en base.
 * Vérifie les doublons par slug avant insertion.
 * ============================================================
 */

ob_start();

require_once __DIR__ . '/../_init.php';
require_once __DIR__ . '/../auth/middleware.php';

Middleware::requireAuth();

$pageTitle  = 'Import Programme Q4 2026';
$activeMenu = 'formations';

$pdo = Database::connect();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

/* ===============================
   HELPER SLUGIFY
================================ */
function slugify_import(string $text): string {
    $text = mb_strtolower(trim($text), 'UTF-8');
    $text = preg_replace('/[^\p{L}\p{Nd}]+/u', '-', $text);
    return trim((string)$text, '-');
}

/* ===============================
   DÉFINITION DES 15 FORMATIONS
================================ */
// Tarif En ligne = tarifBase (inscription 50 000 incluse)
// Tarif Hybride  = tarifBase + 25 000
// Tarif Présentiel = tarifBase + 50 000
$formations = [
    [
        'ref'             => 2,
        'titre'           => 'Responsable QHSE',
        'domaine'         => 'Qualité, Hygiène, Sécurité & Environnement',
        'type_certificat' => 'Certificat de Formation Professionnelle',
        'duree'           => '30h',
        'date_debut'      => '2026-10-12',
        'date_fin'        => '2026-11-13',
        'mois'            => 'Octobre',
        'annee'           => 2026,
        'tarif_base'      => 200000,
    ],
    [
        'ref'             => 4,
        'titre'           => 'Transport, Logistique & Supply Chain Management',
        'domaine'         => 'Logistique & Transport',
        'type_certificat' => 'Certificat de Formation Professionnelle',
        'duree'           => '30h',
        'date_debut'      => '2026-10-19',
        'date_fin'        => '2026-11-20',
        'mois'            => 'Octobre',
        'annee'           => 2026,
        'tarif_base'      => 200000,
    ],
    [
        'ref'             => 6,
        'titre'           => 'De Comptable à Chef Comptable',
        'domaine'         => 'Comptabilité & Finance',
        'type_certificat' => 'Certificat de Formation Professionnelle',
        'duree'           => '30h',
        'date_debut'      => '2026-10-26',
        'date_fin'        => '2026-11-27',
        'mois'            => 'Octobre',
        'annee'           => 2026,
        'tarif_base'      => 200000,
    ],
    [
        'ref'             => 7,
        'titre'           => 'DAF — Directeur Administratif & Financier',
        'domaine'         => 'Finance & Management',
        'type_certificat' => 'Diplôme Professionnel',
        'duree'           => '60h',
        'date_debut'      => '2026-11-02',
        'date_fin'        => '2026-12-18',
        'mois'            => 'Novembre',
        'annee'           => 2026,
        'tarif_base'      => 400000,
    ],
    [
        'ref'             => 8,
        'titre'           => 'Responsable Commercial & Marketing',
        'domaine'         => 'Commercial & Marketing',
        'type_certificat' => 'Certificat de Formation Professionnelle',
        'duree'           => '30h',
        'date_debut'      => '2026-11-02',
        'date_fin'        => '2026-12-04',
        'mois'            => 'Novembre',
        'annee'           => 2026,
        'tarif_base'      => 200000,
    ],
    [
        'ref'             => 9,
        'titre'           => 'Transformation Digitale des PME',
        'domaine'         => 'Digital & Innovation',
        'type_certificat' => 'Certificat de Formation Professionnelle',
        'duree'           => '25h',
        'date_debut'      => '2026-11-05',
        'date_fin'        => '2026-12-10',
        'mois'            => 'Novembre',
        'annee'           => 2026,
        'tarif_base'      => 200000,
    ],
    [
        'ref'             => 10,
        'titre'           => 'Gestion Immobilière Professionnelle',
        'domaine'         => 'Immobilier',
        'type_certificat' => 'Certificat Professionnel Immobilier',
        'duree'           => '25h',
        'date_debut'      => '2026-11-09',
        'date_fin'        => '2026-12-11',
        'mois'            => 'Novembre',
        'annee'           => 2026,
        'tarif_base'      => 200000,
    ],
    [
        'ref'             => 11,
        'titre'           => 'Gestion de Projets Humanitaires & ONG',
        'domaine'         => 'Gestion de Projets',
        'type_certificat' => 'Certificat de Formation Professionnelle',
        'duree'           => '25h',
        'date_debut'      => '2026-11-09',
        'date_fin'        => '2026-12-11',
        'mois'            => 'Novembre',
        'annee'           => 2026,
        'tarif_base'      => 200000,
    ],
    [
        'ref'             => 12,
        'titre'           => 'Certificat du Chargé d\'Affaires',
        'domaine'         => 'Commerce & Développement des Affaires',
        'type_certificat' => 'Certificat Professionnel',
        'duree'           => '25h',
        'date_debut'      => '2026-11-16',
        'date_fin'        => '2026-12-18',
        'mois'            => 'Novembre',
        'annee'           => 2026,
        'tarif_base'      => 200000,
    ],
    [
        'ref'             => 13,
        'titre'           => 'Fiscalité des Entreprises Ivoiriennes',
        'domaine'         => 'Droit & Fiscalité',
        'type_certificat' => 'Certificat de Formation Professionnelle',
        'duree'           => '20h',
        'date_debut'      => '2026-11-19',
        'date_fin'        => '2026-12-17',
        'mois'            => 'Novembre',
        'annee'           => 2026,
        'tarif_base'      => 200000,
    ],
    [
        'ref'             => 1,
        'titre'           => 'Certificat Pro en Management des Entreprises',
        'domaine'         => 'Management',
        'type_certificat' => 'Certificat Professionnel',
        'duree'           => '25h',
        'date_debut'      => '2026-11-23',
        'date_fin'        => '2026-12-18',
        'mois'            => 'Novembre',
        'annee'           => 2026,
        'tarif_base'      => 200000,
    ],
    [
        'ref'             => 14,
        'titre'           => 'Droit des Affaires OHADA',
        'domaine'         => 'Droit des Affaires',
        'type_certificat' => 'Certificat de Formation Professionnelle',
        'duree'           => '25h',
        'date_debut'      => '2026-11-23',
        'date_fin'        => '2026-12-18',
        'mois'            => 'Novembre',
        'annee'           => 2026,
        'tarif_base'      => 200000,
    ],
    [
        'ref'             => 15,
        'titre'           => 'Création et Gestion d\'Entreprise',
        'domaine'         => 'Entrepreneuriat',
        'type_certificat' => 'Certificat de Formation Professionnelle',
        'duree'           => '25h',
        'date_debut'      => '2026-11-30',
        'date_fin'        => '2026-12-18',
        'mois'            => 'Novembre',
        'annee'           => 2026,
        'tarif_base'      => 200000,
    ],
    [
        'ref'             => 5,
        'titre'           => 'Audit et Contrôle de Gestion',
        'domaine'         => 'Finance & Contrôle de Gestion',
        'type_certificat' => 'Certificat Professionnel',
        'duree'           => '40h',
        'date_debut'      => '2026-12-07',
        'date_fin'        => '2027-01-09',
        'mois'            => 'Décembre',
        'annee'           => 2026,
        'tarif_base'      => 230000,
    ],
    [
        'ref'             => 3,
        'titre'           => 'Data Analytique & Gestion des Ressources Humaines',
        'domaine'         => 'Ressources Humaines & Data Analytics',
        'type_certificat' => 'Certificat de Formation Professionnelle',
        'duree'           => '25h',
        'date_debut'      => '2026-12-14',
        'date_fin'        => '2027-01-16',
        'mois'            => 'Décembre',
        'annee'           => 2026,
        'tarif_base'      => 200000,
    ],
];

// Calcul des tarifs et slugs
foreach ($formations as &$f) {
    $f['tarif_en_ligne']    = $f['tarif_base'];
    $f['tarif_hybride']     = $f['tarif_base'] + 25000;
    $f['tarif_presentiel']  = $f['tarif_base'] + 50000;
    $f['frais_inscription'] = 50000;
    $f['slug']              = slugify_import($f['titre']);
    $f['session_label']     = 'Session Q4 2026';
}
unset($f);

/* ===============================
   VÉRIFICATION DOUBLONS
   Si le slug existe déjà, on ajoute le suffixe -q4-2026
================================ */
foreach ($formations as &$f) {
    $chk = $pdo->prepare("SELECT id FROM formations WHERE slug = ? LIMIT 1");
    $chk->execute([$f['slug']]);
    $existing = $chk->fetch();
    if ($existing) {
        // Essayer avec le suffixe -q4-2026
        $slugAlt = $f['slug'] . '-q4-2026';
        $chk2 = $pdo->prepare("SELECT id FROM formations WHERE slug = ? LIMIT 1");
        $chk2->execute([$slugAlt]);
        $existing2 = $chk2->fetch();
        if ($existing2) {
            $f['_exists'] = (int)$existing2['id']; // doublon exact de cette session aussi
        } else {
            $f['slug']    = $slugAlt; // on utilise le slug suffixé
            $f['_exists'] = null;
        }
        $f['_slug_conflict'] = (int)$existing['id'];
    } else {
        $f['_exists']        = null;
        $f['_slug_conflict'] = null;
    }
}
unset($f);

$toInsert  = array_filter($formations, fn($f) => $f['_exists'] === null);
$skipped   = array_filter($formations, fn($f) => $f['_exists'] !== null);

/* ===============================
   TRAITEMENT POST (INSERTION)
================================ */
$results = [];
$errors  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $sql = "
        INSERT INTO formations (
            titre, slug, domaine, type_certificat, duree,
            mode, tarif_en_ligne, tarif_hybride, tarif_presentiel,
            frais_inscription, date_debut, date_fin,
            mois, annee, session_label, statut, is_samedi_pro,
            created_at, updated_at
        ) VALUES (
            :titre, :slug, :domaine, :type_certificat, :duree,
            'hybride', :tel, :th, :tp,
            :fi, :dd, :df,
            :mois, :annee, :session_label, 'active', 0,
            NOW(), NOW()
        )
    ";
    $stmt = $pdo->prepare($sql);

    foreach ($toInsert as $f) {
        try {
            $stmt->execute([
                ':titre'         => $f['titre'],
                ':slug'          => $f['slug'],
                ':domaine'       => $f['domaine'],
                ':type_certificat' => $f['type_certificat'],
                ':duree'         => $f['duree'],
                ':tel'           => $f['tarif_en_ligne'],
                ':th'            => $f['tarif_hybride'],
                ':tp'            => $f['tarif_presentiel'],
                ':fi'            => $f['frais_inscription'],
                ':dd'            => $f['date_debut'],
                ':df'            => $f['date_fin'],
                ':mois'          => $f['mois'],
                ':annee'         => $f['annee'],
                ':session_label' => $f['session_label'],
            ]);
            $results[] = ['titre' => $f['titre'], 'id' => $pdo->lastInsertId()];
        } catch (Throwable $e) {
            $errors[] = $f['titre'] . ' — ' . $e->getMessage();
        }
    }
}

/* ===============================
   UI
================================ */
ob_start();

function fmtF(int $n): string {
    return number_format($n, 0, ',', ' ') . ' FCFA';
}
?>

<style>
  .card{background:#fff;border:1px solid #e6eaf2;border-radius:16px;padding:20px;box-shadow:0 8px 24px rgba(15,23,42,.06);margin-bottom:20px}
  .card h2{margin:0 0 14px;font-size:17px;font-weight:900;color:#0f172a}
  table.imp{width:100%;border-collapse:collapse;font-size:13px}
  table.imp th{background:#f1f5f9;padding:9px 12px;text-align:left;font-size:11px;font-weight:800;letter-spacing:.06em;text-transform:uppercase;color:#64748b;border-bottom:2px solid #e5e7eb}
  table.imp td{padding:9px 12px;border-bottom:1px solid #f1f5f9;vertical-align:middle}
  table.imp tr:last-child td{border-bottom:none}
  table.imp tr:hover td{background:#f8fafc}
  .ref{width:30px;font-size:11px;font-weight:700;color:#94a3b8;text-align:center}
  .badge{display:inline-flex;align-items:center;padding:2px 8px;border-radius:999px;font-size:10px;font-weight:700}
  .badge-ok{background:#ecfdf5;color:#047857}
  .badge-skip{background:#fef3c7;color:#92400e}
  .badge-daf{background:#fff7ed;color:#d97706}
  .pill{display:inline-flex;align-items:center;gap:8px;border-radius:999px;padding:10px 14px;font-weight:800;font-size:13px;margin:10px 0}
  .pill-ok{background:#ecfdf5;color:#047857;border:1px solid #a7f3d0}
  .pill-warn{background:#fff7ed;color:#9a3412;border:1px solid #fed7aa}
  .pill-err{background:#fef2f2;color:#dc2626;border:1px solid #fecaca}
  .btn{display:inline-flex;align-items:center;gap:8px;padding:11px 18px;border-radius:12px;font-weight:900;font-size:14px;border:1px solid transparent;text-decoration:none;cursor:pointer}
  .btn-primary{background:#1d4ed8;color:#fff}
  .btn-primary:hover{background:#1e40af}
  .btn-secondary{background:#fff;color:#0f172a;border-color:#e5e7eb}
  .btn-secondary:hover{background:#f8fafc}
  .tarif-cell{font-size:11px;color:#475569}
  .tarif-cell span{display:block}
  .actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:16px}
  .results-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:10px;margin-top:12px}
  .result-item{background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:10px 14px;font-size:12px;font-weight:600;color:#065f46}
  .result-item .id{font-size:10px;color:#6ee7b7;font-weight:700}
</style>

<div class="card">
  <h2>&#128640; Import Programme Q4 2026</h2>

  <?php if (!empty($results)): ?>
    <div class="pill pill-ok">&#10004; <?= count($results) ?> formation(s) créée(s) avec succès !</div>
    <div class="results-grid">
      <?php foreach ($results as $r): ?>
        <div class="result-item">
          <?= htmlspecialchars($r['titre']) ?>
          <div class="id">ID : <?= (int)$r['id'] ?></div>
        </div>
      <?php endforeach; ?>
    </div>
    <?php if (!empty($errors)): ?>
      <div class="pill pill-err" style="margin-top:12px;">&#9888; <?= count($errors) ?> erreur(s)</div>
      <?php foreach ($errors as $e): ?>
        <div style="font-size:12px;color:#dc2626;margin:4px 0">&#8226; <?= htmlspecialchars($e) ?></div>
      <?php endforeach; ?>
    <?php endif; ?>
    <div class="actions">
      <a href="index.php" class="btn btn-primary">&#8594; Voir toutes les formations</a>
    </div>

  <?php else: ?>

    <?php if (!empty($skipped)): ?>
      <div class="pill pill-warn">&#9888; <?= count($skipped) ?> formation(s) déjà existante(s) ignorée(s)</div>
    <?php endif; ?>

    <p style="font-size:13px;color:#475569;margin:0 0 14px">
      <strong><?= count($toInsert) ?></strong> formation(s) à créer &bull;
      <strong><?= count($skipped) ?></strong> ignorée(s) (doublon Q4 2026 déjà en base) &bull;
      Statut : <strong>active</strong> &bull; Format par défaut : <strong>Hybride</strong>
    </p>

    <div style="overflow-x:auto;">
      <table class="imp">
        <thead>
          <tr>
            <th class="ref">#</th>
            <th>Formation</th>
            <th>Début</th>
            <th>Fin</th>
            <th>Durée</th>
            <th>Tarifs (En ligne / Hybride / Présentiel)</th>
            <th>Statut</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($formations as $f): ?>
            <tr>
              <td class="ref"><?= $f['ref'] ?></td>
              <td>
                <strong><?= htmlspecialchars($f['titre']) ?></strong>
                <div style="font-size:10px;color:#94a3b8"><?= htmlspecialchars($f['domaine']) ?></div>
              </td>
              <td><?= htmlspecialchars($f['date_debut']) ?></td>
              <td style="font-size:12px"><?= htmlspecialchars($f['date_fin']) ?></td>
              <td>
                <span class="badge <?= $f['ref'] === 7 ? 'badge-daf' : 'badge-ok' ?>"><?= $f['duree'] ?></span>
              </td>
              <td class="tarif-cell">
                <span>&#127760; <?= fmtF($f['tarif_en_ligne']) ?></span>
                <span>&#9881;&nbsp; <?= fmtF($f['tarif_hybride']) ?></span>
                <span>&#127970; <?= fmtF($f['tarif_presentiel']) ?></span>
              </td>
              <td>
                <?php if ($f['_exists']): ?>
                  <span class="badge badge-skip">Doublon (ID <?= $f['_exists'] ?>)</span>
                <?php elseif (!empty($f['_slug_conflict'])): ?>
                  <span class="badge badge-ok" title="Slug suffixé -q4-2026 (conflit ID <?= $f['_slug_conflict'] ?>)">À créer *</span>
                <?php else: ?>
                  <span class="badge badge-ok">À créer</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <?php if (count($toInsert) > 0): ?>
      <form method="post">
        <?= csrf_field() ?>
        <div class="actions">
          <button class="btn btn-primary" type="submit" onclick="return confirm('Confirmer la création de <?= count($toInsert) ?> formation(s) ?')">
            &#128640; Créer les <?= count($toInsert) ?> formations
          </button>
          <a href="index.php" class="btn btn-secondary">&#8592; Annuler</a>
        </div>
      </form>
    <?php else: ?>
      <div class="pill pill-warn" style="margin-top:12px;">Toutes les formations existent déjà.</div>
      <a href="index.php" class="btn btn-secondary" style="margin-top:10px">&#8592; Retour</a>
    <?php endif; ?>

  <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout/layout.php';
