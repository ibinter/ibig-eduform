<?php
declare(strict_types=1);

/*
 |--------------------------------------------------
 | INIT ADMIN — PIPELINE INTERNE
 |--------------------------------------------------
*/
require_once __DIR__ . '/../_init.php';

/* &#128274; Middleware */
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
$pageTitle  = "Centre de notifications";
$activeMenu = "emplois_notifications";

$pdo = Database::connect();

/*
 |--------------------------------------------------
 | LOGS DE NOTIFICATIONS
 |--------------------------------------------------
*/
$logs = $pdo->query("
  SELECT
    canal,
    destinataire,
    sujet,
    statut,
    created_at
  FROM notification_logs
  ORDER BY created_at DESC
  LIMIT 200
")->fetchAll(PDO::FETCH_ASSOC);

/*
 |--------------------------------------------------
 | FILE D’ATTENTE (QUEUE)
 |--------------------------------------------------
*/
$queue = $pdo->query("
  SELECT
    id,
    destinataire,
    sujet,
    statut,
    essais,
    created_at
  FROM notification_queue
  WHERE statut IN ('pending','failed')
  ORDER BY created_at ASC
")->fetchAll(PDO::FETCH_ASSOC);

/*
 |--------------------------------------------------
 | CONTENU (BUFFER INTERNE)
 |--------------------------------------------------
*/
ob_start();
?>

<div class="card">

  <h2>&#128276; Centre de notifications</h2>

  <hr>

  <!-- FILE D’ATTENTE -->
  <h3>&#9203; Notifications en attente / &eacute;chou&eacute;es</h3>

  <?php if (empty($queue)): ?>
    <p class="muted">Aucune notification en attente.</p>
  <?php else: ?>

    <table style="margin-top:12px;width:100%">
      <thead>
        <tr>
          <th>Destinataire</th>
          <th>Sujet</th>
          <th>Statut</th>
          <th>Essais</th>
          <th>Date</th>
          <th style="width:120px">Action</th>
        </tr>
      </thead>
      <tbody>

      <?php foreach ($queue as $q): ?>
        <tr>

          <td><?= e($q['destinataire']); ?></td>

          <td><?= e($q['sujet']); ?></td>

          <td>
            <span class="pill <?= $q['statut']==='pending' ? 'wait' : 'danger'; ?>">
              <?= strtoupper(e($q['statut'])); ?>
            </span>
          </td>

          <td><?= (int)$q['essais']; ?></td>

          <td><?= e(date('d/m/Y H:i', strtotime($q['created_at']))); ?></td>

          <td>
            <a class="btn btn-outline"
               href="notification_retry.php?id=<?= (int)$q['id']; ?>"
               onclick="return confirm('Relancer cette notification ?');">
              &#128260; Relancer
            </a>
          </td>

        </tr>
      <?php endforeach; ?>

      </tbody>
    </table>

  <?php endif; ?>

  <hr>

  <!-- HISTORIQUE -->
  <h3>&#128221; Historique des notifications</h3>

  <table style="margin-top:12px;width:100%">
    <thead>
      <tr>
        <th>Canal</th>
        <th>Destinataire</th>
        <th>Sujet</th>
        <th>Statut</th>
        <th>Date</th>
      </tr>
    </thead>
    <tbody>

    <?php if (empty($logs)): ?>
      <tr>
        <td colspan="5" class="muted">
          Aucun historique disponible.
        </td>
      </tr>
    <?php endif; ?>

    <?php foreach ($logs as $l): ?>
      <tr>
    
        <td><?= strtoupper(e($l['canal'])); ?></td>
    
        <td><?= e($l['destinataire']); ?></td>
    
        <td><?= e($l['sujet']); ?></td>
    
        <td>
          <span class="pill <?= $l['statut']==='success' ? 'ok' : 'danger'; ?>">
            <?= strtoupper(e($l['statut'])); ?>
          </span>
        </td>
    
        <td><?= e(date('d/m/Y H:i', strtotime($l['created_at']))); ?></td>
    
      </tr>
    <?php endforeach; ?>

    </tbody>
  </table>

</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout/layout.php';
