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
   MESSAGES SESSION
=============================== */
$flashSuccess = $_SESSION['df_success'] ?? null; unset($_SESSION['df_success']);
$flashError   = $_SESSION['df_error']   ?? null; unset($_SESSION['df_error']);

/* Message pré-rempli pour le prospect */
$who   = trim(($d['prenoms'] ?? '') . ' ' . ($d['nom'] ?? ''));
$sujetDefault = 'Suite à votre demande de formation — IBIG EDUFORM';
$corpsDefault  = 'Bonjour ' . ($d['prenoms'] ?: $who) . ",\n\n"
    . "Nous avons bien reçu votre demande concernant : " . ($d['theme_formation'] ?: $d['domaine_formation'] ?: 'votre formation') . ".\n\n"
    . "Notre équipe a étudié votre besoin et revient vers vous très prochainement avec une proposition adaptée.\n\n"
    . "N'hésitez pas à nous contacter au +225 07 78 88 25 92 pour toute question.\n\n"
    . "Cordialement,\nL'équipe IBIG EDUFORM";

/* ===============================
   RENDER
=============================== */
ob_start();
?>

<?php if ($flashSuccess): ?>
<div style="background:#dcfce7;border:1px solid #86efac;color:#166534;padding:12px 16px;border-radius:8px;margin-bottom:16px">✅ <?= e($flashSuccess); ?></div>
<?php endif; ?>
<?php if ($flashError): ?>
<div style="background:#fee2e2;border:1px solid #fca5a5;color:#991b1b;padding:12px 16px;border-radius:8px;margin-bottom:16px">❌ <?= e($flashError); ?></div>
<?php endif; ?>

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
      <a class="btn btn-secondary" href="<?= e($waAdminLink); ?>" target="_blank" rel="noopener">📱 WhatsApp</a>
      <a class="btn btn-secondary" href="export_print.php?id=<?= (int)$d['id']; ?>" target="_blank">🖨️ PDF</a>
      <?php
        $devisParams = http_build_query([
          'from_demande' => $d['id'],
          'nom'          => $d['nom'] ?? '',
          'prenoms'      => $d['prenoms'] ?? '',
          'email'        => $d['email'] ?? '',
          'telephone'    => $d['telephone'] ?? '',
          'structure'    => $d['structure_nom'] ?? '',
          'theme'        => $d['theme_formation'] ?? $d['domaine_formation'] ?? '',
          'participants' => $d['nombre_participants'] ?? '',
          'mode'         => $d['mode_formation'] ?? '',
        ]);
      ?>
      <a class="btn btn-primary" href="/outils/devis-liste.php?<?= $devisParams; ?>" target="_blank">📋 Créer un devis</a>
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

  <!-- RÉPONDRE PAR EMAIL -->
  <h4>✉️ Répondre au prospect</h4>
  <?php if (empty($d['email'])): ?>
    <p class="muted">Aucun email fourni par le demandeur.</p>
  <?php else: ?>
  <form method="post" action="send-reply.php" style="display:flex;flex-direction:column;gap:12px">
    <?= csrf_field(); ?>
    <input type="hidden" name="id" value="<?= (int)$d['id']; ?>">

    <div>
      <label style="font-size:13px;font-weight:600;color:#374151">Destinataire</label>
      <input type="text" value="<?= e($d['email']); ?>" readonly
             style="width:100%;padding:8px 12px;border:1px solid #d1d5db;border-radius:6px;background:#f9fafb;font-size:13px;color:#6b7280">
    </div>

    <div>
      <label style="font-size:13px;font-weight:600;color:#374151">Sujet *</label>
      <input type="text" name="sujet" required value="<?= e($sujetDefault); ?>"
             style="width:100%;padding:8px 12px;border:1px solid #d1d5db;border-radius:6px;font-size:13px">
    </div>

    <div>
      <label style="font-size:13px;font-weight:600;color:#374151">Message *</label>
      <textarea name="corps" required rows="8"
                style="width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:6px;font-size:13px;font-family:inherit;resize:vertical"><?= e($corpsDefault); ?></textarea>
    </div>

    <div>
      <label style="font-size:13px;font-weight:600;color:#374151">Passer le statut à</label>
      <select name="statut" style="padding:8px 12px;border:1px solid #d1d5db;border-radius:6px;font-size:13px">
        <option value="">— Garder le statut actuel —</option>
        <option value="traitee">Traitée</option>
        <option value="devis_envoye">Devis envoyé</option>
        <option value="cloturee">Clôturée</option>
      </select>
    </div>

    <div>
      <button type="submit" class="btn btn-primary">📤 Envoyer l'email</button>
    </div>
  </form>
  <?php endif; ?>

  <hr>

  <!-- STATUT -->
  <h4>Changer le statut</h4>
  <form method="post" action="update-status.php">
    <?= csrf_field(); ?>
    <input type="hidden" name="id" value="<?= (int)$d['id']; ?>">

    <select name="statut" id="statut" style="padding:8px 12px;border:1px solid #d1d5db;border-radius:6px;font-size:13px">
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