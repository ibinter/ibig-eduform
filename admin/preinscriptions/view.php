<?php
declare(strict_types=1);

require_once __DIR__ . '/../auth/guard.php';
require_permission('view_preinscriptions');

require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../auth/middleware.php';

Middleware::requireAuth();

$u = auth_user();

/* =========================
   ID
========================= */
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) redirect('index.php');

$pdo = Database::connect();

/* =========================
   PRÉINSCRIPTION
========================= */
$stmt = $pdo->prepare("
  SELECT p.*, f.titre AS formation
  FROM preinscriptions p
  LEFT JOIN formations f ON f.id = p.formation_id
  WHERE p.id = ?
  LIMIT 1
");
$stmt->execute([$id]);
$p = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$p) redirect('index.php');

$statut = strtolower((string)($p['statut'] ?? 'nouvelle'));

$pageTitle  = "Détail de la préinscription";
$activeMenu = "preinscriptions";
$canEdit = in_array($u['role'], ['admin','super_admin','commercial'], true);

/* =========================
   HELPERS CONTACT
========================= */
if (!function_exists('pre_wa_number')) {
    function pre_wa_number(?string $phone): string {
        $d = preg_replace('/\D+/', '', (string)$phone);
        if ($d === '') return '';
        if (strpos($d, '225') === 0) return $d;
        if (strlen($d) <= 10) return '225' . $d;
        return $d;
    }
}

$nomComplet = trim((string)($p['nom'] ?? '') . ' ' . (string)($p['prenoms'] ?? ''));
$prenom     = trim((string)($p['prenoms'] ?? '')) ?: trim((string)($p['nom'] ?? '')) ?: 'cher candidat';
$form       = trim((string)($p['formation'] ?? ''));
$email      = trim((string)($p['email'] ?? ''));
$tel        = trim((string)($p['telephone'] ?? ''));
$waNum      = pre_wa_number($tel);
$ini        = strtoupper(mb_substr($nomComplet !== '' ? $nomComplet : 'P', 0, 1, 'UTF-8'));

$waMsg = "Bonjour " . $prenom . ", ici l'équipe IBIG EDUFORM. "
       . ($form !== '' ? "Nous revenons vers vous au sujet de votre préinscription à la formation « " . $form . " ». " : "Nous revenons vers vous au sujet de votre préinscription. ")
       . "Comment pouvons-nous vous accompagner ?";
$waHref = $waNum !== '' ? 'https://wa.me/' . $waNum . '?text=' . rawurlencode($waMsg) : '';

$mailSubject = "IBIG EDUFORM — Votre préinscription" . ($form !== '' ? " : " . $form : "");
$mailBody = "Bonjour " . $prenom . ",\n\n"
          . "Nous vous remercions pour votre préinscription" . ($form !== '' ? " à la formation « " . $form . " »" : "") . " auprès d'IBIG EDUFORM.\n\n"
          . "Nous revenons vers vous pour finaliser votre inscription et répondre à vos éventuelles questions.\n\n"
          . "Bien cordialement,\nL'équipe IBIG EDUFORM";
$mailHref = $email !== '' ? 'mailto:' . $email . '?subject=' . rawurlencode($mailSubject) . '&body=' . rawurlencode($mailBody) : '';
$telHref  = $tel !== '' ? 'tel:' . preg_replace('/[^\d+]/', '', $tel) : '';

ob_start();
?>

<style>
.pv-head{display:flex;align-items:center;gap:16px;flex-wrap:wrap;margin-bottom:6px}
.pv-av{width:58px;height:58px;border-radius:16px;display:grid;place-items:center;font-weight:800;font-size:24px;color:#fff;
  background:linear-gradient(135deg,#1f3fe0,#e8242c);flex-shrink:0}
.pv-head h2{margin:0;font-size:22px}
.pv-head .sub{font-size:13px;color:#6b7280;margin-top:2px}
.pill{padding:4px 12px;border-radius:999px;font-size:11px;font-weight:800;display:inline-block;text-transform:uppercase;letter-spacing:.3px}
.pill.nouvelle{background:#e0f2fe;color:#075985}
.pill.traitee,.pill.confirme,.pill.confirmee,.pill.valide{background:#dcfce7;color:#166534}
.pill.rejete{background:#fee2e2;color:#991b1b}

.pv-contact{display:flex;gap:10px;flex-wrap:wrap;margin:14px 0 6px}
.cbtn{display:inline-flex;align-items:center;gap:8px;padding:10px 16px;border-radius:11px;font-size:13.5px;font-weight:800;text-decoration:none;border:1px solid transparent}
.cbtn.wa{background:#dcfce7;color:#15803d;border-color:#bbf7d0}.cbtn.wa:hover{background:#bbf7d0}
.cbtn.mail{background:#e0ecff;color:#1e40af;border-color:#bfdbfe}.cbtn.mail:hover{background:#c7ddff}
.cbtn.tel{background:#f1f5f9;color:#334155;border-color:#e2e8f0}.cbtn.tel:hover{background:#e2e8f0}
.cbtn.dis{opacity:.4;pointer-events:none}

.detail-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin:16px 0}
.detail-item{font-size:14px;background:#f8fafc;border:1px solid #eef2f7;border-radius:12px;padding:12px 14px}
.detail-item strong{display:block;font-size:11px;text-transform:uppercase;letter-spacing:.3px;color:#6b7280;margin-bottom:4px}
.detail-item a{color:#1f3fe0;text-decoration:none}.detail-item a:hover{text-decoration:underline}
.detail-msg{background:#fffbeb;border:1px solid #fde68a;border-radius:12px;padding:14px 16px;font-size:14px;line-height:1.6;margin:8px 0 0}

.pv-files{display:flex;gap:12px;flex-wrap:wrap;margin:10px 0}
.btn{padding:9px 16px;font-size:13px;border-radius:10px;text-decoration:none;border:0;cursor:pointer;font-weight:700;display:inline-flex;align-items:center;gap:8px}
.btn-primary{background:linear-gradient(135deg,#1f3fe0,#3b82f6);color:#fff}
.btn-secondary{background:#e5e7eb;color:#111827}
.btn-file{background:#020617;color:#fff}
.btn[disabled]{opacity:.5;cursor:not-allowed}

.pv-status{display:flex;align-items:flex-end;gap:12px;flex-wrap:wrap;margin-top:10px}
.pv-status select{padding:10px 12px;border:1px solid #e5e7eb;border-radius:10px;font-size:14px;font-weight:600;min-width:200px}
.sect-title{font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.4px;color:#94a3b8;margin:0 0 8px}
hr{border:0;border-top:1px solid #eef2f7;margin:18px 0}
@media(max-width:820px){.detail-grid{grid-template-columns:1fr 1fr}}
@media(max-width:520px){.detail-grid{grid-template-columns:1fr}}
</style>

<div class="card">

  <!-- EN-TÊTE -->
  <div class="pv-head">
    <div class="pv-av"><?= e($ini); ?></div>
    <div style="flex:1;min-width:200px">
      <h2><?= e($nomComplet !== '' ? $nomComplet : 'Préinscription #'.(int)$p['id']); ?></h2>
      <div class="sub">
        <?= e($p['ville'] ?? '—'); ?><?php if (!empty($p['niveau'])): ?> · <?= e($p['niveau']); ?><?php endif; ?>
        · <?= !empty($p['created_at']) ? date('d/m/Y H:i', strtotime((string)$p['created_at'])) : '—'; ?>
      </div>
    </div>
    <span class="pill <?= e($statut); ?>"><?= e($p['statut'] ?: 'nouvelle'); ?></span>
  </div>

  <!-- CONTACT DIRECT -->
  <div class="pv-contact">
    <a class="cbtn wa <?= $waHref === '' ? 'dis' : ''; ?>" <?= $waHref !== '' ? 'href="'.e($waHref).'" target="_blank" rel="noopener"' : ''; ?>>💬 Contacter par WhatsApp</a>
    <a class="cbtn mail <?= $mailHref === '' ? 'dis' : ''; ?>" <?= $mailHref !== '' ? 'href="'.e($mailHref).'"' : ''; ?>>✉️ Envoyer un email</a>
    <a class="cbtn tel <?= $telHref === '' ? 'dis' : ''; ?>" <?= $telHref !== '' ? 'href="'.e($telHref).'"' : ''; ?>>📞 Appeler</a>
  </div>

  <hr>

  <!-- INFOS -->
  <p class="sect-title">Informations</p>
  <div class="detail-grid">
    <div class="detail-item"><strong>Nom complet</strong><?= e($nomComplet !== '' ? $nomComplet : '—'); ?></div>
    <div class="detail-item"><strong>Téléphone</strong><?php if ($tel !== ''): ?><a href="<?= e($telHref); ?>"><?= e($tel); ?></a><?php else: ?>—<?php endif; ?></div>
    <div class="detail-item"><strong>Email</strong><?php if ($email !== ''): ?><a href="mailto:<?= e($email); ?>"><?= e($email); ?></a><?php else: ?>—<?php endif; ?></div>
    <div class="detail-item"><strong>Formation</strong><?= e($form !== '' ? $form : '—'); ?></div>
    <div class="detail-item"><strong>Ville</strong><?= e($p['ville'] ?? '—'); ?></div>
    <div class="detail-item"><strong>Niveau</strong><?= e($p['niveau'] ?? '—'); ?></div>
    <?php if (!empty($p['mode'])): ?><div class="detail-item"><strong>Mode</strong><?= e($p['mode']); ?></div><?php endif; ?>
    <?php if (!empty($p['domaine_activite'])): ?><div class="detail-item"><strong>Domaine d'activité</strong><?= e($p['domaine_activite']); ?></div><?php endif; ?>
    <?php if (!empty($p['niveau_etude'])): ?><div class="detail-item"><strong>Niveau d'étude</strong><?= e($p['niveau_etude']); ?></div><?php endif; ?>
    <?php if (!empty($p['fonction'])): ?><div class="detail-item"><strong>Fonction</strong><?= e($p['fonction']); ?></div><?php endif; ?>
    <?php if (!empty($p['annees_experience'])): ?><div class="detail-item"><strong>Années d'expérience</strong><?= e($p['annees_experience']); ?></div><?php endif; ?>
    <div class="detail-item"><strong>Date</strong><?= !empty($p['created_at']) ? date('d/m/Y H:i', strtotime((string)$p['created_at'])) : '—'; ?></div>
  </div>

  <?php if (!empty($p['message'])): ?>
    <p class="sect-title">Message du candidat</p>
    <div class="detail-msg"><?= nl2br(e($p['message'])); ?></div>
  <?php endif; ?>

  <hr>
  <p class="sect-title">Documents</p>
  <div class="pv-files">
    <?php if (!empty($p['cv_path'])): ?>
      <a class="btn btn-file" href="/<?= e($p['cv_path']); ?>" target="_blank">📄 Télécharger le CV</a>
    <?php endif; ?>
    <?php if (!empty($p['cni_path'])): ?>
      <a class="btn btn-file" href="/<?= e($p['cni_path']); ?>" target="_blank">🪪 Télécharger la pièce d'identité</a>
    <?php endif; ?>
    <?php if (empty($p['cv_path']) && empty($p['cni_path'])): ?>
      <span class="muted" style="color:#94a3b8;font-size:13.5px">Aucun document fourni par le candidat.</span>
    <?php endif; ?>
  </div>

  <hr>

  <!-- STATUT -->
  <form method="post" action="update.php">
    <?= csrf_field(); ?>
    <input type="hidden" name="id" value="<?= (int)$p['id']; ?>">
    <p class="sect-title">Gestion du statut</p>
    <div class="pv-status">
      <select name="statut" <?= !$canEdit ? 'disabled' : ''; ?>>
        <option value="nouvelle" <?= $statut==='nouvelle'?'selected':''; ?>>Nouvelle</option>
        <option value="traitee"  <?= $statut==='traitee'?'selected':''; ?>>Traitée</option>
        <option value="rejete"   <?= $statut==='rejete'?'selected':''; ?>>Rejetée</option>
      </select>
      <?php if ($canEdit): ?>
        <button class="btn btn-primary">✓ Mettre à jour</button>
      <?php endif; ?>
      <a class="btn btn-secondary" href="index.php">← Retour à la liste</a>
    </div>
  </form>

</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout/layout.php';
