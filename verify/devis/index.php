<?php
declare(strict_types=1);
/**
 * IBIG EDUFORM — /verify/devis/{token}
 * Page publique de vérification d'authenticité d'un devis.
 * Accessible sans login. Scannée via QR code par le client.
 * URL : ibig-eduform.com/verify/devis/TOKEN
 */
require_once __DIR__ . '/../../core/config.php';
// APP_URL est défini dans config.php — pas besoin de redéfinir

/* ── Extraire le token depuis l'URL (/verify/devis/TOKEN) ── */
$uri   = $_SERVER['REQUEST_URI'] ?? '';
$parts = explode('/', trim($uri, '/'));
// Chercher "devis" dans le chemin et prendre ce qui suit
$token = '';
foreach ($parts as $i => $p) {
    if ($p === 'devis' && isset($parts[$i + 1])) {
        $token = preg_replace('/[^a-f0-9]/', '', $parts[$i + 1]);
        break;
    }
}

$devis   = null;
$erreur  = '';
$status  = 'invalid'; // invalid | valid | expired | cancelled

if (strlen($token) !== 64) {
    $erreur = 'Jeton invalide ou manquant.';
} else {
    try {
        $pdo = new PDO(
            'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4',
            DB_USER, DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        $st = $pdo->prepare("SELECT id, ref_devis, prospect, titre_formation, categorie, duree,
            nb_participants, mode_formation, montant_ttc, tva_pct,
            validite_jours, date_expiration, statut, date_creation, tdr_ref
            FROM devis WHERE qr_token = ? LIMIT 1");
        $st->execute([$token]);
        $devis = $st->fetch(PDO::FETCH_ASSOC);

        if (!$devis) {
            $erreur = 'Aucun document trouvé pour ce jeton.';
        } else {
            // Incrémenter le compteur de scans + date dernière vérif
            $upd = $pdo->prepare("UPDATE devis SET qr_scans = qr_scans + 1, derniere_verif = NOW() WHERE id = ?");
            $upd->execute([$devis['id']]);

            $today = date('Y-m-d');
            if (in_array($devis['statut'], ['annule', 'refuse'])) {
                $status = 'cancelled';
            } elseif ($devis['date_expiration'] && $devis['date_expiration'] < $today
                      && !in_array($devis['statut'], ['accepte'])) {
                $status = 'expired';
            } else {
                $status = 'valid';
            }
        }
    } catch (\Throwable $e) {
        $erreur = 'Erreur de vérification. Veuillez réessayer.';
        error_log('[VERIFY_DEVIS] ' . $e->getMessage());
    }
}

$modeLabel = match($devis['mode_formation'] ?? '') {
    'en_ligne'   => 'En ligne (Classe Virtuelle)',
    'hybride'    => 'Hybride',
    default      => 'Présentiel',
};

$statusInfo = match($status) {
    'valid'     => ['color' => '#059669', 'bg' => '#f0fdf4', 'border' => '#bbf7d0',
                    'icon' => '✅', 'label' => 'Document authentique et valide'],
    'expired'   => ['color' => '#d97706', 'bg' => '#fffbeb', 'border' => '#fde68a',
                    'icon' => '⚠️', 'label' => 'Document expiré — validité dépassée'],
    'cancelled' => ['color' => '#dc2626', 'bg' => '#fef2f2', 'border' => '#fecaca',
                    'icon' => '❌', 'label' => 'Document annulé ou refusé'],
    default     => ['color' => '#6b7280', 'bg' => '#f9fafb', 'border' => '#e5e7eb',
                    'icon' => '❓', 'label' => 'Document introuvable'],
};

$logoPath = __DIR__ . '/../../assets/images/logo.png';
$logoB64  = is_file($logoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath)) : '';
?><!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Vérification Devis — IBIG EDUFORM</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Segoe UI',system-ui,sans-serif;background:#f8fafc;color:#1e293b;min-height:100vh;display:flex;flex-direction:column;align-items:center;padding:24px 16px}
.card{background:#fff;border-radius:16px;box-shadow:0 4px 24px rgba(13,31,60,.10);max-width:520px;width:100%;overflow:hidden}
.card-head{background:#0d1f3c;padding:24px;text-align:center}
.card-head img{height:40px;margin-bottom:10px}
.card-head h1{color:#fff;font-size:1.1rem;font-weight:700;letter-spacing:.04em}
.card-head p{color:#94a3b8;font-size:.8rem;margin-top:4px}
.status-box{margin:20px;padding:16px;border-radius:10px;display:flex;align-items:center;gap:14px;border:2px solid var(--border)}
.status-icon{font-size:2rem;flex-shrink:0}
.status-label{font-size:1rem;font-weight:800;color:var(--color)}
.status-sub{font-size:.8rem;color:#64748b;margin-top:3px}
.section{margin:0 20px 16px}
.section h2{font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8;margin-bottom:10px;padding-bottom:6px;border-bottom:1px solid #f1f5f9}
.row{display:flex;justify-content:space-between;align-items:baseline;padding:5px 0;font-size:.875rem;border-bottom:1px solid #f8fafc}
.row:last-child{border-bottom:none}
.row .label{color:#64748b}
.row .val{font-weight:700;color:#0d1f3c;text-align:right;max-width:60%}
.ttc-row .val{font-size:1.1rem;color:#0d1f3c}
.warn{background:#fff7ed;border:1px solid #fed7aa;border-radius:8px;padding:12px 16px;margin:0 20px 16px;font-size:.82rem;color:#9a3412}
.footer-card{margin:20px;padding-top:16px;border-top:1px solid #f1f5f9;text-align:center}
.footer-card p{font-size:.75rem;color:#94a3b8;line-height:1.6}
.footer-card a{color:#0d1f3c;font-weight:700}
.error-box{background:#fef2f2;border:2px solid #fecaca;border-radius:10px;padding:20px;margin:20px;text-align:center}
.error-box .icon{font-size:2.5rem;margin-bottom:10px}
.error-box p{color:#dc2626;font-size:.9rem}
.page-footer{margin-top:24px;text-align:center;font-size:.75rem;color:#94a3b8}
.page-footer a{color:#0d1f3c;font-weight:600}
</style>
</head>
<body>

<div class="card">
  <div class="card-head">
    <?php if ($logoB64): ?>
      <img src="<?= $logoB64 ?>" alt="IBIG EDUFORM">
    <?php else: ?>
      <div style="font-size:1.2rem;font-weight:900;color:#f59e0b;margin-bottom:8px">IBIG EDUFORM</div>
    <?php endif; ?>
    <h1>Vérification de document</h1>
    <p>Devis / Proforma — Authenticité certifiée</p>
  </div>

  <?php if ($erreur || !$devis): ?>
  <div class="error-box">
    <div class="icon">❓</div>
    <p><?= htmlspecialchars($erreur ?: 'Document introuvable.') ?></p>
    <p style="margin-top:8px;font-size:.8rem;color:#9ca3af">Vérifiez que le QR code a bien été scanné entièrement, ou contactez IBIG EDUFORM.</p>
  </div>

  <?php else: ?>

  <!-- Statut principal -->
  <div class="status-box" style="--color:<?= $statusInfo['color'] ?>;--bg:<?= $statusInfo['bg'] ?>;--border:<?= $statusInfo['border'] ?>;background:<?= $statusInfo['bg'] ?>">
    <div class="status-icon"><?= $statusInfo['icon'] ?></div>
    <div>
      <div class="status-label"><?= $statusInfo['label'] ?></div>
      <div class="status-sub">Réf. <?= htmlspecialchars($devis['ref_devis']) ?> · Vérifié le <?= date('d/m/Y à H:i') ?></div>
    </div>
  </div>

  <?php if ($status === 'expired'): ?>
  <div class="warn">⚠️ Ce devis a expiré le <?= date('d/m/Y', strtotime($devis['date_expiration'])) ?>. Veuillez contacter IBIG EDUFORM pour un devis actualisé.</div>
  <?php endif; ?>

  <!-- Informations du document -->
  <div class="section">
    <h2>Informations du document</h2>
    <div class="row"><span class="label">Référence</span><span class="val"><?= htmlspecialchars($devis['ref_devis']) ?></span></div>
    <div class="row"><span class="label">Émis le</span><span class="val"><?= date('d/m/Y', strtotime($devis['date_creation'])) ?></span></div>
    <div class="row"><span class="label">Valable jusqu'au</span><span class="val"><?= $devis['date_expiration'] ? date('d/m/Y', strtotime($devis['date_expiration'])) : '—' ?></span></div>
    <?php if ($devis['tdr_ref']): ?>
    <div class="row"><span class="label">TDR de référence</span><span class="val"><?= htmlspecialchars($devis['tdr_ref']) ?></span></div>
    <?php endif; ?>
  </div>

  <!-- Destinataire -->
  <div class="section">
    <h2>Destinataire</h2>
    <div class="row"><span class="label">Client / Prospect</span><span class="val"><?= htmlspecialchars($devis['prospect']) ?></span></div>
  </div>

  <!-- Objet -->
  <div class="section">
    <h2>Objet de la formation</h2>
    <div class="row"><span class="label">Formation</span><span class="val"><?= htmlspecialchars($devis['titre_formation']) ?></span></div>
    <div class="row"><span class="label">Domaine</span><span class="val"><?= htmlspecialchars($devis['categorie'] ?: '—') ?></span></div>
    <div class="row"><span class="label">Durée</span><span class="val"><?= htmlspecialchars($devis['duree']) ?></span></div>
    <div class="row"><span class="label">Modalité</span><span class="val"><?= htmlspecialchars($modeLabel) ?></span></div>
    <div class="row"><span class="label">Participants</span><span class="val"><?= (int)$devis['nb_participants'] ?></span></div>
  </div>

  <!-- Montant (affiché intentionnellement sans détail remise/PU) -->
  <div class="section">
    <h2>Montant</h2>
    <div class="row"><span class="label">TVA</span><span class="val"><?= (int)$devis['tva_pct'] > 0 ? (int)$devis['tva_pct'] . '%' : 'Exonéré' ?></span></div>
    <div class="row ttc-row"><span class="label" style="font-weight:700;color:#0d1f3c">TOTAL TTC</span>
      <span class="val"><?= number_format((int)$devis['montant_ttc'], 0, ',', "\u{202F}") ?> F CFA</span></div>
  </div>

  <?php endif; ?>

  <div class="footer-card">
    <p>
      Ce document a été émis par <strong>IBIG EDUFORM</strong><br>
      Institut de Formation Professionnelle &amp; Conseil<br>
      📧 <a href="mailto:formation@ibig-eduform.com">formation@ibig-eduform.com</a> · 📞 +225 07 78 88 25 92<br>
      <a href="<?= APP_URL ?>">www.ibig-eduform.com</a>
    </p>
  </div>
</div>

<p class="page-footer">
  Vérification sécurisée IBIG EDUFORM · <a href="<?= APP_URL ?>">Retour au site</a>
</p>

</body>
</html>
