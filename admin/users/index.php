<?php
declare(strict_types=1);

/**
 * ============================================================
 * IBIG EDUFORM — USERS / LIST
 * ============================================================
 */

require_once __DIR__ . '/../auth/guard.php';
require_permission('manage_users');

require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/helpers.php';

$pageTitle  = "Utilisateurs";
$activeMenu = "users";

$pdo = Database::connect();
$currentUserId = (int)($_SESSION['user']['id'] ?? 0);

/* ===============================
   UTILISATEURS
================================ */
$users = $pdo->query("
  SELECT
    id,
    first_name,
    last_name,
    email,
    role,
    status,
    created_at
  FROM users
  ORDER BY created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

ob_start();
?>

<div class="card">

  <!-- HEADER -->
  <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
    <div>
      <h2 style="margin:0">Utilisateurs</h2>
      <div class="muted" style="font-size:13px;margin-top:4px">
        Gestion des comptes et des accès
      </div>
    </div>

    <a class="btn btn-primary" href="create.php">
      + Nouvel utilisateur
    </a>
  </div>

  <!-- TABLE -->
  <div style="overflow-x:auto">
    <table class="table" style="width:100%;border-collapse:collapse">

      <thead>
        <tr>
          <th>Utilisateur</th>
          <th>Email</th>
          <th>Rôle</th>
          <th>Statut</th>
          <th>Création</th>
          <th style="width:300px">Actions</th>
        </tr>
      </thead>

      <tbody>

      <?php if (empty($users)): ?>
        <tr>
          <td colspan="6" class="muted" style="text-align:center;padding:20px">
            Aucun utilisateur enregistré.
          </td>
        </tr>
      <?php endif; ?>

      <?php foreach ($users as $u): ?>
        <tr>

          <!-- USER -->
          <td>
            <div style="font-weight:700">
              <?= e(trim($u['first_name'].' '.$u['last_name'])); ?>
            </div>
            <div class="muted" style="font-size:12px">
              ID #<?= (int)$u['id']; ?>
            </div>
          </td>

          <!-- EMAIL -->
          <td><?= e($u['email']); ?></td>

          <!-- ROLE -->
          <td>
            <span class="pill info">
              <?= e($u['role']); ?>
            </span>
          </td>

          <!-- STATUS -->
          <td>
            <span class="pill <?= $u['status']==='active' ? 'ok' : 'wait'; ?>">
              <?= $u['status']==='active' ? 'Actif' : 'Inactif'; ?>
            </span>
          </td>

          <!-- DATE -->
          <td><?= date('d/m/Y', strtotime($u['created_at'])); ?></td>

          <!-- ACTIONS -->
          <td style="vertical-align:middle">
            <div class="actions-bar">

              <!-- EDIT -->
              <a class="btn btn-secondary"
                 href="edit.php?id=<?= (int)$u['id']; ?>">
                Éditer
              </a>

              <!-- TOGGLE -->
              <a class="btn <?= $u['status']==='active' ? 'btn-warning' : 'btn-success'; ?>"
                 href="toggle.php?id=<?= (int)$u['id']; ?>&csrf=<?= csrf_token(); ?>">
                <?= $u['status']==='active' ? 'Désactiver' : 'Activer'; ?>
              </a>

              <!-- DELETE -->
              <?php
                $canDelete =
                  $u['role'] !== 'super_admin'
                  && (int)$u['id'] !== $currentUserId;
              ?>

              <?php if ($canDelete): ?>
                <form method="post"
                      action="delete.php?id=<?= (int)$u['id']; ?>"
                      onsubmit="return confirm('⚠️ Supprimer définitivement cet utilisateur ?');"
                      style="display:inline">
                  <?= csrf_field(); ?>
                  <button class="btn btn-danger">
                    Supprimer
                  </button>
                </form>
              <?php else: ?>
                <button class="btn btn-secondary" disabled
                        title="Action interdite">
                  Supprimer
                </button>
              <?php endif; ?>

            </div>
          </td>

        </tr>
      <?php endforeach; ?>

      </tbody>
    </table>
  </div>

</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout/layout.php';