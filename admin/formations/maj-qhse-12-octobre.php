<?php
declare(strict_types=1);
/**
 * ONE-TIME — Formation « Responsable HSE / QHSE » du lundi 12 octobre 2026
 * - Remplace le TDR par la version officielle TDR-IBIG-EDU-2026-09
 * - Met à jour contenu, durée et tarifs officiels nets (250 000 / 275 000 F CFA)
 * À supprimer après exécution.
 */
ob_start();
require_once __DIR__ . '/../_init.php';
Middleware::requireAuth();

$pdo = Database::connect();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$pageTitle  = "MAJ formation QHSE du 12 octobre";
$activeMenu = "formations";

if (!function_exists('e')) {
  function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}

$srcPdf    = __DIR__ . '/_tdr_src/TDR_Responsable_HSE_QHSE_2026-09.pdf';
$uploadDir = __DIR__ . '/../../uploads/tdr/';

/* ── Nouvelles données (source : TDR officiel) ── */
$data = [
  'titre'            => 'Responsable HSE / QHSE',
  'type_certificat'  => 'Certificat de formation professionnelle IBIG EDUFORM',
  'duree'            => '25h',
  'tarif_en_ligne'   => 250000,
  'tarif_presentiel' => 275000,
  'tarif_hybride'    => 0,
  'date_debut'       => '2026-10-12',
  'description'      => "Formation en groupe (3 à 5 participants), en ligne (classe virtuelle) ou en présentiel à Abidjan : 25 heures, 12 séances à raison de 2 à 3 séances par semaine. "
                      . "En Afrique francophone, les grands donneurs d'ordre (mines, BTP, industrie, énergie, organisations internationales) exigent de plus en plus de leurs partenaires des certifications ISO et des preuves concrètes de maîtrise des risques. "
                      . "Cette formation prépare les participants à occuper la fonction de Responsable HSE / QHSE : ISO 9001, ISO 14001, ISO 45001, Document Unique, audit interne, plan de prévention et indicateurs SST.",
  'objectif_general' => "Rendre le participant capable de concevoir, de déployer et d'auditer un système de management QHSE aligné sur les normes ISO 9001, ISO 14001 et ISO 45001, et d'assurer le rôle de référent qualité, sécurité et environnement au sein de son entreprise.",
  'objectifs'        => "Interpréter et appliquer les exigences des normes ISO 9001, ISO 14001 et ISO 45001 dans son entreprise\n"
                      . "Élaborer un Document Unique d'évaluation des risques professionnels sur des cas réels de chantier et d'atelier\n"
                      . "Conduire un audit interne HSE complet : préparation, entretiens sur le terrain, rédaction des constats et suivi des actions correctives\n"
                      . "Construire des indicateurs de performance SST (taux de fréquence, taux de gravité) et piloter un tableau de bord QHSE\n"
                      . "Développer une culture de prévention en animant des causeries sécurité et des exercices d'urgence",
  'modules'          => "M1 — Le métier de Responsable QHSE (4h)\n"
                      . "M2 — Les normes ISO 9001, 14001 et 45001 (4h)\n"
                      . "M3 — Le système de management intégré (4h)\n"
                      . "M4 — L'évaluation des risques (4h)\n"
                      . "M5 — L'audit interne HSE (4h)\n"
                      . "M6 — Pilotage de la performance et certification (5h)",
  'public_cible'     => "Animateurs HSE souhaitant évoluer vers un poste de responsable ; techniciens et ingénieurs désireux de se spécialiser ; responsables de production en charge de la sécurité ; cadres en reconversion. Professionnels de l'industrie, du BTP, des mines, de la logistique, de l'énergie et des services, en Côte d'Ivoire, au Bénin, au Sénégal, au Cameroun et dans l'ensemble de l'espace OHADA.",
];
$prerequis = "Niveau Bac minimum ou expérience professionnelle équivalente. Aucune certification préalable n'est exigée. Pour la formule en ligne : un ordinateur avec webcam et micro, une connexion internet stable et une adresse électronique valide.";

/* ── Formation concernée : https://ibig-eduform.com/formation/responsable-qhse-hse ── */
$cands = $pdo->query("
  SELECT id, titre, slug, statut, date_debut, date_fin, duree, tarif_en_ligne, tarif_presentiel, tarif_hybride, tdr_pdf
  FROM formations
  WHERE slug = 'responsable-qhse-hse'
  ORDER BY id
")->fetchAll(PDO::FETCH_ASSOC);

$cols = array_flip($pdo->query("SHOW COLUMNS FROM formations")->fetchAll(PDO::FETCH_COLUMN));

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();
  $id = (int)($_POST['id'] ?? 0);
  $ids = array_map('intval', array_column($cands, 'id'));
  try {
    if (!in_array($id, $ids, true)) throw new RuntimeException('Formation non valide.');
    if (!is_file($srcPdf)) throw new RuntimeException('Fichier TDR source introuvable sur le serveur.');
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    $filename = 'tdr_formation_' . $id . '_' . time() . '.pdf';
    if (!copy($srcPdf, $uploadDir . $filename)) throw new RuntimeException('Copie du PDF impossible.');

    $pdo->beginTransaction();

    $old = $pdo->prepare("SELECT tdr_pdf FROM formations WHERE id = ?");
    $old->execute([$id]);
    $oldPdf = (string)($old->fetchColumn() ?: '');

    $set = []; $params = [];
    foreach ($data as $k => $v) {
      if (!isset($cols[$k])) continue;
      $set[] = "$k = :$k"; $params[":$k"] = $v;
    }
    if (isset($cols['prerequis'])) { $set[] = "prerequis = :prerequis"; $params[':prerequis'] = $prerequis; }
    $set[] = "tdr_pdf = :tdr"; $params[':tdr'] = $filename;
    if (isset($cols['updated_at'])) $set[] = "updated_at = NOW()";
    $params[':id'] = $id;
    $pdo->prepare("UPDATE formations SET " . implode(', ', $set) . " WHERE id = :id")->execute($params);

    /* Niveaux éventuels : mêmes tarifs officiels et durée (TDR « tous niveaux ») */
    $nbNiv = 0;
    try {
      $upN = $pdo->prepare("
        UPDATE formation_niveaux
        SET duree_heures = 25, tarif_en_ligne = 250000, tarif_presentiel = 275000,
            objectifs = :obj, prerequis = :pre, public_cible = :pub
        WHERE formation_id = :id
      ");
      $upN->execute([':obj' => $data['objectif_general'], ':pre' => $prerequis, ':pub' => $data['public_cible'], ':id' => $id]);
      $nbNiv = $upN->rowCount();
    } catch (Throwable $ignore) {}

    $pdo->commit();

    if ($oldPdf !== '' && basename($oldPdf) !== $filename && is_file($uploadDir . basename($oldPdf))) {
      @unlink($uploadDir . basename($oldPdf));
    }
    $message = "✅ Formation #$id mise à jour : nouveau TDR, contenu, durée 25h, tarifs 250 000 / 275 000 F CFA"
             . ($nbNiv ? " ($nbNiv niveau(x) alignés)" : '') . '.';
    $cands = $pdo->query("
      SELECT id, titre, slug, statut, date_debut, date_fin, duree, tarif_en_ligne, tarif_presentiel, tarif_hybride, tdr_pdf
      FROM formations WHERE slug = 'responsable-qhse-hse' ORDER BY id
    ")->fetchAll(PDO::FETCH_ASSOC);
  } catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    if (isset($filename) && is_file($uploadDir . $filename)) @unlink($uploadDir . $filename);
    $message = '❌ Erreur : ' . $e->getMessage();
  }
}
?>
<style>
.fix-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:24px;max-width:900px;margin:0 auto}
.fix-card h2{margin:0 0 16px;font-size:17px;font-weight:800}
table{width:100%;border-collapse:collapse;font-size:13px;margin-bottom:20px}
th{background:#f1f5f9;padding:7px 10px;text-align:left;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase}
td{padding:7px 10px;border-bottom:1px solid #f1f5f9;vertical-align:top}
.alert-ok{padding:10px 14px;background:#dcfce7;color:#166534;border-radius:8px;margin-bottom:16px;font-size:13px}
.alert-err{padding:10px 14px;background:#fee2e2;color:#991b1b;border-radius:8px;margin-bottom:16px;font-size:13px}
.btn{padding:8px 16px;background:#1e40af;color:#fff;border:none;border-radius:8px;font-weight:700;cursor:pointer;font-size:13px}
.new{color:#166534;font-weight:700}
</style>
<div class="fix-card">
  <h2>🔧 Formation « Responsable HSE / QHSE » — lundi 12 octobre 2026</h2>

  <?php if ($message): ?>
    <div class="<?= strpos($message, '✅') === 0 ? 'alert-ok' : 'alert-err' ?>"><?= e($message) ?></div>
  <?php endif; ?>

  <p style="font-size:13px;color:#475569">Nouvelles valeurs : <span class="new">titre « Responsable HSE / QHSE » · début lundi 12/10/2026 · 25h (12 séances) · en ligne 250 000 F · présentiel 275 000 F · TDR officiel TDR-IBIG-EDU-2026-09</span>, objectifs, modules M1–M6, public cible et prérequis repris du TDR.</p>

  <?php if (!$cands): ?>
    <div class="alert-err">Formation « responsable-qhse-hse » introuvable.</div>
  <?php else: ?>
  <table>
    <thead><tr><th>ID</th><th>Titre actuel</th><th>Statut</th><th>Dates</th><th>Durée</th><th>Tarifs actuels (ligne / prés. / hyb.)</th><th>TDR actuel</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($cands as $c): ?>
      <tr>
        <td><?= (int)$c['id'] ?></td>
        <td><?= e($c['titre']) ?><br><small style="color:#94a3b8"><?= e($c['slug']) ?></small></td>
        <td><?= e($c['statut']) ?></td>
        <td><?= e($c['date_debut']) ?> → <?= e($c['date_fin']) ?></td>
        <td><?= e($c['duree']) ?></td>
        <td><?= number_format((int)$c['tarif_en_ligne'], 0, ',', ' ') ?> / <?= number_format((int)$c['tarif_presentiel'], 0, ',', ' ') ?> / <?= number_format((int)$c['tarif_hybride'], 0, ',', ' ') ?></td>
        <td><?php if (!empty($c['tdr_pdf'])): ?><a href="/uploads/tdr/<?= e(basename((string)$c['tdr_pdf'])) ?>" target="_blank">voir</a><?php else: ?>—<?php endif; ?></td>
        <td>
          <form method="post" onsubmit="return confirm('Mettre à jour la formation #<?= (int)$c['id'] ?> ?');">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
            <button type="submit" class="btn">✅ Mettre à jour</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout/layout.php';
