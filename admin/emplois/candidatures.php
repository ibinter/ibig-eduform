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
$pageTitle  = "Candidatures reçues";
$activeMenu = "emplois_candidatures";

$pdo = Database::connect();

/*
 |--------------------------------------------------
 | RÉCUPÉRATION DES CANDIDATURES
 |--------------------------------------------------
*/
$rows = $pdo->query("
  SELECT 
    c.id,
    c.nom,
    c.email,
    c.statut,
    c.created_at,
    o.titre AS offre,
    e.nom_legal AS entreprise
  FROM candidatures c
  JOIN offres_emploi o ON o.id = c.offre_id
  JOIN entreprises e ON e.id = o.entreprise_id
  ORDER BY c.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

/*
 |--------------------------------------------------
 | CONTENU (BUFFER INTERNE)
 |--------------------------------------------------
*/
ob_start();
?>

<div class="card">

  <h2>&#128196; Candidatures re&ccedil;ues</h2>

  <table style="margin-top:16px;width:100%">
    <thead>
      <tr>
        <th>Candidat</th>
        <th>Email</th>
        <th>Offre</th>
        <th>Entreprise</th>
        <th>Statut</th>
        <th>Date</th>
        <th style="width:120px">Action</th>
      </tr>
    </thead>
    <tbody>

    <?php if (empty($rows)): ?>
      <tr>
        <td colspan="7" class="muted">
          Aucune candidature enregistr&eacute;e.
        </td>
      </tr>
    <?php endif; ?>

    <?php foreach ($rows as $c): ?>
      <tr>

        <td><strong><?= e($c['nom']); ?></strong></td>

        <td><?= e($c['email']); ?></td>

        <td><?= e($c['offre']); ?></td>

        <td><?= e($c['entreprise']); ?></td>

        <td>
          <span class="pill <?= $c['statut']==='retenu'?'ok':'wait'; ?>">
            <?= e($c['statut']); ?>
          </span>
        </td>

        <td><?= e(date('d/m/Y H:i', strtotime($c['created_at']))); ?></td>

        <td>
          <a class="btn btn-outline"
             href="candidature_view.php?id=<?= (int)$c['id']; ?>">
            &#128065; Voir
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
