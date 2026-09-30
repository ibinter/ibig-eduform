<?php
declare(strict_types=1);

require_once __DIR__ . '/../_init.php';
require_once __DIR__ . '/../auth/middleware.php';

Middleware::requireAuth();

$pdo = Database::connect();

/* ===============================
   RÉCUP ID
=============================== */
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) redirect('index.php');

/* ===============================
   DONNÉES DEMANDE
=============================== */
$stmt = $pdo->prepare("SELECT * FROM demandes_formation WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$d = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$d) redirect('index.php');

/* ===============================
   PAGE CONFIG
=============================== */
$pageTitle  = "Détail demande de formation";
$activeMenu = "demandes_formation";

/* ===============================
   CONTACT ADMIN
=============================== */
$adminEmail = defined('ADMIN_EMAIL')
  ? ADMIN_EMAIL
  : 'formation@intermark-business.com';

/* WhatsApp admin */
$adminWhats = '2250778882592'; // sans +

function wa_link(string $phone, string $msg): string {
  return 'https://wa.me/'.rawurlencode($phone).'?text='.rawurlencode($msg);
}

/* ===============================
   MESSAGE STANDARD
=============================== */
$who = trim(($d['prenoms'] ?? '').' '.($d['nom'] ?? ''));

$msg = "DEMANDE DE FORMATION\n"
     . "Demandeur : {$who}\n"
     . "Type : ".($d['type_demandeur'] ?? '')."\n"
     . "Téléphone : ".($d['telephone'] ?? '')."\n"
     . "Structure : ".($d['structure_nom'] ?? '—')."\n"
     . "Domaine : ".($d['domaine_formation'] ?? '—')."\n"
     . "Thème : ".($d['theme_formation'] ?? '—')."\n"
     . "Objectif : ".($d['objectif'] ?? '—');

$waAdminLink = wa_link($adminWhats, $msg);

/* Mailto */
$subject = "Demande de formation – {$who}";
$mailto  = "mailto:{$adminEmail}?subject="
         . rawurlencode($subject)
         . "&body=".rawurlencode($msg);

/* ===============================
   RENDER
=============================== */
ob_start();
?>

<div class="card">

  <!-- HEADER -->
  <div style="display:flex;justify-content:space-between;gap:16px;flex-wrap:wrap">
    <div>
      <h2 style="margin:0">Détail de la demande</h2>
      <div class="muted" style="margin-top:6px">
        ID : <?= (int)$d['id']; ?> —
        Créée le <?= e(date('d/m/Y H:i', strtotime((string)$d['created_at']))); ?>
      </div>
    </div>

    <div style="display:flex;gap:10px;flex-wrap:wrap">
      <a class="btn btn-secondary" href="<?= e($mailto); ?>">Email admin</a>
      <a class="btn btn-secondary" href="<?= e($waAdminLink); ?>" target="_blank" rel="noopener">WhatsApp</a>
      <a class="btn btn-secondary" href="export_print.php?id=<?= (int)$d['id']; ?>" target="_blank">PDF</a>
    </div>
  </div>

  <hr>

  <!-- IDENTITÉ -->
  <h4>Identité du demandeur</h4>
  <p><strong>Type :</strong> <?= e($d['type_demandeur']); ?></p>
  <p><strong>Nom :</strong> <?= e($who); ?></p>
  <p><strong>Email :</strong> <?= e($d['email'] ?: '—'); ?></p>
  <p><strong>Téléphone :</strong> <?= e($d['telephone']); ?></p>

  <hr>

  <!-- STRUCTURE -->
  <h4>Structure</h4>
  <p><strong>Nom :</strong> <?= e($d['structure_nom'] ?: '—'); ?></p>
  <p><strong>Fonction :</strong> <?= e($d['fonction'] ?: '—'); ?></p>
  <p><strong>Secteur :</strong> <?= e($d['secteur'] ?: '—'); ?></p>

  <hr>

  <!-- BESOIN FORMATION -->
  <h4>Besoin de formation</h4>
  <p><strong>Domaine :</strong> <?= e($d['domaine_formation'] ?: '—'); ?></p>
  <p><strong>Thème :</strong> <?= e($d['theme_formation'] ?: '—'); ?></p>
  <p><strong>Objectif :</strong><br><?= nl2br(e((string)$d['objectif'])); ?></p>

  <p><strong>Niveau :</strong> <?= e($d['niveau'] ?: '—'); ?></p>
  <p><strong>Participants :</strong> <?= e($d['nombre_participants'] ?: '—'); ?></p>
  <p><strong>Mode :</strong> <?= e($d['mode_formation'] ?: '—'); ?></p>
  <p><strong>Lieu :</strong> <?= e($d['lieu'] ?: '—'); ?></p>
  <p><strong>Durée :</strong> <?= e($d['duree'] ?: '—'); ?></p>
  <p><strong>Période :</strong> <?= e($d['periode_souhaitee'] ?: '—'); ?></p>
  <p><strong>Budget :</strong> <?= e($d['budget'] ?: '—'); ?></p>
  <p><strong>Urgence :</strong> <?= e($d['urgence'] ?: '—'); ?></p>

  <hr>

  <!-- MESSAGE -->
  <h4>Message complémentaire</h4>
  <p><?= nl2br(e((string)($d['message'] ?? '—'))); ?></p>

  <hr>

  <!-- STATUT -->
  <form method="post" action="update-status.php">
    <?= csrf_field(); ?>
    <input type="hidden" name="id" value="<?= (int)$d['id']; ?>">

    <label for="statut"><strong>Statut</strong></label>
    <select name="statut" id="statut">
      <?php
      $statuts = ['nouvelle','traitee','devis_envoye','cloturee'];
      foreach ($statuts as $s):
      ?>
        <option value="<?= e($s); ?>" <?= ($d['statut'] === $s ? 'selected' : ''); ?>>
          <?= e(ucfirst(str_replace('_',' ', $s))); ?>
        </option>
      <?php endforeach; ?>
    </select>

    <div style="margin-top:16px">
      <button class="btn btn-primary" type="submit">Mettre à jour</button>
      <a class="btn btn-secondary" href="index.php">Retour</a>
    </div>
  </form>

</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout/layout.php';