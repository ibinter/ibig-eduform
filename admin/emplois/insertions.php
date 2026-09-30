<?php
declare(strict_types=1);

/*
 |--------------------------------------------------
 | INIT ADMIN — PIPELINE INTERNE
 |--------------------------------------------------
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

/*
 |--------------------------------------------------
 | CONFIG PAGE
 |--------------------------------------------------
*/
$pageTitle  = "Insertions professionnelles";
$activeMenu = "emplois_insertions";

$pdo = Database::connect();

/*
 |--------------------------------------------------
 | RÉCUPÉRATION DES INSERTIONS
 |--------------------------------------------------
*/
$rows = $pdo->query("
  SELECT 
    i.id,
    i.statut,
    i.date_debut,
    c.nom AS candidat,
    o.titre AS offre,
    e.nom_legal AS entreprise
  FROM insertions i
  JOIN candidats c ON c.id = i.candidat_id
  JOIN offres_emploi o ON o.id = i.offre_id
  JOIN entreprises e ON e.id = i.entreprise_id
  ORDER BY i.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

/*
 |--------------------------------------------------
 | CONTENU (BUFFER INTERNE)
 |--------------------------------------------------
*/
ob_start();
?>

<div class="card">

  <div style="display:flex;justify-content:space-between;align-items:center">
    <h2>&#127919; Pipeline d&rsquo;insertion professionnelle</h2>

    <a class="btn btn-primary"
       href="insertion_create.php">
      &#10133; Nouvelle insertion
    </a>
  </div>

  <table style="margin-top:16px;width:100%">
    <thead>
      <tr>
        <th>Candidat</th>
        <th>Offre</th>
        <th>Entreprise</th>
        <th>Statut</th>
        <th>Date d&eacute;but</th>
        <th style="width:120px">Action</th>
      </tr>
    </thead>
    <tbody>

    <?php if (empty($rows)): ?>
      <tr>
        <td colspan="6" class="muted">
          Aucune insertion enregistr&eacute;e.
        </td>
      </tr>
    <?php endif; ?>

    <?php foreach ($rows as $i): ?>
      <tr>

        <td><strong><?= e($i['candidat']); ?></strong></td>

        <td><?= e($i['offre']); ?></td>

        <td><?= e($i['entreprise']); ?></td>

        <td>
          <span class="pill
            <?= in_array($i['statut'], ['place','termine']) ? 'ok' : 'wait'; ?>">
            <?= strtoupper(e($i['statut'])); ?>
          </span>
        </td>

        <td>
          <?= $i['date_debut']
              ? e(date('d/m/Y', strtotime($i['date_debut'])))
              : '&mdash;'; ?>
        </td>

        <td>
          <a class="btn btn-outline"
             href="insertion_view.php?id=<?= (int)$i['id']; ?>">
            &#128221; Suivi
          </a>
        </td>

      </tr>
    <?php endforeach; ?>

    </tbody>
  </table>

</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout/layout.php';
