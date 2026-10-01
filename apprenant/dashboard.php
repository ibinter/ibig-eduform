<?php
declare(strict_types=1);
require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/apprenant_auth.php';
apprenant_require();

$apprenant  = apprenant_get();
$email      = $apprenant['email'];
$pdo        = Database::connect();
$pageTitle  = 'Mon espace apprenant — IBIG EDUFORM';

/* ── Inscriptions ── */
$stmtIns = $pdo->prepare("
    SELECT p.id, p.created_at, p.type_preinscription, p.mode_formation, p.statut,
           p.domaine_interet, p.formation_id,
           f.titre AS formation_titre, f.slug AS formation_slug,
           f.date_debut, f.duree
    FROM preinscriptions p
    LEFT JOIN formations f ON f.id = p.formation_id
    WHERE LOWER(p.email) = ?
    ORDER BY p.created_at DESC
");
$stmtIns->execute([strtolower($email)]);
$inscriptions = $stmtIns->fetchAll(PDO::FETCH_ASSOC);

/* ── Paiements ── */
$stmtPay = $pdo->prepare("
    SELECT pi.reference, pi.montant, pi.statut, pi.created_at, pi.paid_at,
           f.titre AS formation_titre
    FROM paiements_inscription pi
    LEFT JOIN formations f ON f.id = pi.formation_id
    WHERE LOWER(pi.customer_email) = ?
    ORDER BY pi.created_at DESC
");
$stmtPay->execute([strtolower($email)]);
$paiements = $stmtPay->fetchAll(PDO::FETCH_ASSOC);

/* ── Helpers ── */
$statutLabel = [
    'en_attente'  => ['⏳ En attente', '#f59e0b'],
    'confirme'    => ['✅ Confirmé',   '#22c55e'],
    'annule'      => ['❌ Annulé',     '#ef4444'],
    'inscrit'     => ['✅ Inscrit',    '#22c55e'],
    'liste_att'   => ['📋 Liste d\'attente', '#a78bfa'],
];
$statutPay = [
    'en_attente' => ['⏳ En attente', '#f59e0b'],
    'paye'       => ['✅ Payé',       '#22c55e'],
    'echoue'     => ['❌ Échoué',     '#ef4444'],
];
$modeLabel = [
    'presentiel' => '🏫 Présentiel',
    'en_ligne'   => '💻 En ligne',
    'hybride'    => '🔀 Hybride',
    'elearning'  => '🎓 E-learning',
];

include __DIR__ . '/../partials/header.php';
?>
<style>
:root { --ap-bg:#050d1a; --ap-card:#0b1220; --ap-border:rgba(255,255,255,.08); --ap-gold:#f59e0b; --ap-text:#e5e7eb; --ap-muted:#94a3b8; }
.ap-layout { max-width:960px; margin:0 auto; padding:80px 18px 100px; }
.ap-hero { background:var(--ap-card); border:1px solid var(--ap-border); border-radius:16px; padding:28px 32px; display:flex; align-items:center; gap:20px; margin-bottom:28px; }
.ap-avatar { width:60px; height:60px; border-radius:50%; background:linear-gradient(135deg,#f59e0b,#d97706); display:flex; align-items:center; justify-content:center; font-size:1.6rem; font-weight:900; color:#0a1733; flex-shrink:0; }
.ap-hero-info h1 { margin:0 0 4px; color:var(--ap-gold); font-size:1.4rem; }
.ap-hero-info p  { margin:0; color:var(--ap-muted); font-size:.9rem; }
.ap-logout { margin-left:auto; padding:8px 16px; border:1px solid rgba(255,255,255,.18); border-radius:8px; color:var(--ap-muted); text-decoration:none; font-size:.82rem; white-space:nowrap; }
.ap-logout:hover { border-color:#ef4444; color:#fca5a5; }

.ap-section-title { font-size:1rem; font-weight:700; color:var(--ap-gold); margin:28px 0 14px; letter-spacing:.03em; text-transform:uppercase; }

/* Cards inscription */
.ins-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:16px; }
.ins-card { background:var(--ap-card); border:1px solid var(--ap-border); border-radius:14px; padding:20px; }
.ins-card-top { display:flex; justify-content:space-between; align-items:flex-start; gap:10px; margin-bottom:12px; }
.ins-titre { font-weight:700; color:var(--ap-text); font-size:.95rem; line-height:1.4; flex:1; }
.ins-badge { font-size:.72rem; padding:3px 10px; border-radius:20px; font-weight:700; white-space:nowrap; }
.ins-meta { font-size:.8rem; color:var(--ap-muted); display:flex; flex-direction:column; gap:5px; margin-bottom:14px; }
.ins-meta span { display:flex; align-items:center; gap:6px; }
.ins-actions { display:flex; gap:8px; flex-wrap:wrap; }
.ins-btn { padding:7px 14px; border-radius:8px; font-size:.78rem; font-weight:700; text-decoration:none; border:none; cursor:pointer; }
.ins-btn-primary { background:var(--ap-gold); color:#0a1733; }
.ins-btn-outline { background:transparent; border:1px solid rgba(255,255,255,.2); color:var(--ap-text); }
.ins-btn:hover { opacity:.85; }

/* Tableau paiements */
.pay-table { width:100%; border-collapse:collapse; font-size:.85rem; }
.pay-table th { text-align:left; padding:10px 14px; color:var(--ap-muted); font-weight:600; border-bottom:1px solid var(--ap-border); }
.pay-table td { padding:12px 14px; border-bottom:1px solid rgba(255,255,255,.04); color:var(--ap-text); }
.pay-table tr:last-child td { border-bottom:none; }
.pay-wrap-table { background:var(--ap-card); border:1px solid var(--ap-border); border-radius:14px; overflow:hidden; }

.ap-empty { text-align:center; padding:40px 20px; color:var(--ap-muted); }
.ap-empty .icon { font-size:2.5rem; margin-bottom:10px; }
</style>

<div class="ap-layout">

  <!-- Hero -->
  <div class="ap-hero">
    <div class="ap-avatar"><?= mb_strtoupper(mb_substr($apprenant['prenoms'] ?: $apprenant['nom'] ?: 'A', 0, 1), 'UTF-8') ?></div>
    <div class="ap-hero-info">
      <h1>Bonjour, <?= htmlspecialchars($apprenant['prenoms'] ?: $apprenant['nom']) ?> 👋</h1>
      <p><?= htmlspecialchars($email) ?> · Espace apprenant IBIG EDUFORM</p>
    </div>
    <a href="/apprenant/logout.php" class="ap-logout">Déconnexion</a>
  </div>

  <!-- Mes inscriptions -->
  <div class="ap-section-title">📋 Mes inscriptions (<?= count($inscriptions) ?>)</div>

  <?php if (empty($inscriptions)): ?>
    <div class="ap-empty">
      <div class="icon">📭</div>
      <p>Aucune inscription trouvée pour cet email.</p>
      <a href="/catalogue-formations.php" style="color:var(--ap-gold)">Parcourir le catalogue →</a>
    </div>
  <?php else: ?>
    <div class="ins-grid">
      <?php foreach ($inscriptions as $ins):
        $titre = $ins['formation_titre'] ?: ($ins['domaine_interet'] ?: 'Formation');
        $st    = $statutLabel[$ins['statut']] ?? ['⏳ ' . $ins['statut'], '#94a3b8'];
        $mode  = $modeLabel[$ins['mode_formation']] ?? ($ins['mode_formation'] ?: '—');
        $date  = $ins['created_at'] ? date('d/m/Y', strtotime($ins['created_at'])) : '—';
        $dateDebut = $ins['date_debut'] ? date('d/m/Y', strtotime($ins['date_debut'])) : null;
        $lienTDR   = !empty($ins['formation_slug']) ? '/tdr-local-pdf.php?slug=' . urlencode($ins['formation_slug']) : null;
        $lienDetail = $ins['formation_slug'] ? '/formation/' . urlencode($ins['formation_slug']) : null;
        $lienPay    = $ins['formation_id'] ? '/paiement-inscription.php?formation=' . (int)$ins['formation_id'] : null;
      ?>
      <div class="ins-card">
        <div class="ins-card-top">
          <div class="ins-titre"><?= htmlspecialchars($titre) ?></div>
          <span class="ins-badge" style="background:<?= $st[1] ?>22;color:<?= $st[1] ?>;border:1px solid <?= $st[1] ?>44">
            <?= $st[0] ?>
          </span>
        </div>
        <div class="ins-meta">
          <span>📅 Inscrit le <?= $date ?></span>
          <span><?= $mode ?></span>
          <?php if ($dateDebut): ?><span>🗓️ Début prévu : <?= $dateDebut ?></span><?php endif; ?>
          <?php if ($ins['duree']): ?><span>⏱️ Durée : <?= htmlspecialchars($ins['duree']) ?></span><?php endif; ?>
        </div>
        <div class="ins-actions">
          <?php if ($lienDetail): ?>
            <a href="<?= $lienDetail ?>" class="ins-btn ins-btn-outline" target="_blank">Voir la formation</a>
          <?php endif; ?>
          <?php if ($lienTDR): ?>
            <a href="<?= $lienTDR ?>" class="ins-btn ins-btn-outline" target="_blank">📄 TDR</a>
          <?php endif; ?>
          <?php if ($lienPay && $ins['statut'] !== 'annule'): ?>
            <a href="<?= $lienPay ?>" class="ins-btn ins-btn-primary">💳 Payer</a>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <!-- Mes paiements -->
  <?php if (!empty($paiements)): ?>
  <div class="ap-section-title">💳 Mes paiements (<?= count($paiements) ?>)</div>
  <div class="pay-wrap-table">
    <table class="pay-table">
      <thead>
        <tr>
          <th>Formation</th>
          <th>Référence</th>
          <th>Montant</th>
          <th>Statut</th>
          <th>Date</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($paiements as $pay):
          $ps    = $statutPay[$pay['statut']] ?? ['⏳ ' . $pay['statut'], '#94a3b8'];
          $datePay = $pay['paid_at'] ? date('d/m/Y', strtotime($pay['paid_at']))
                   : ($pay['created_at'] ? date('d/m/Y', strtotime($pay['created_at'])) : '—');
          $montant = $pay['montant'] > 0 ? number_format((int)$pay['montant'], 0, ',', ' ') . ' F CFA' : '—';
        ?>
        <tr>
          <td><?= htmlspecialchars($pay['formation_titre'] ?: '—') ?></td>
          <td style="font-size:.78rem;color:var(--ap-muted);font-family:monospace"><?= htmlspecialchars($pay['reference']) ?></td>
          <td style="font-variant-numeric:tabular-nums"><?= $montant ?></td>
          <td>
            <span style="font-size:.75rem;padding:3px 10px;border-radius:20px;font-weight:700;
                         background:<?= $ps[1] ?>22;color:<?= $ps[1] ?>;border:1px solid <?= $ps[1] ?>44">
              <?= $ps[0] ?>
            </span>
          </td>
          <td style="color:var(--ap-muted)"><?= $datePay ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>

  <!-- Contact support -->
  <div style="margin-top:36px;padding:20px 24px;background:var(--ap-card);border:1px solid var(--ap-border);border-radius:14px;display:flex;align-items:center;gap:16px;flex-wrap:wrap">
    <div style="font-size:1.6rem">💬</div>
    <div style="flex:1">
      <div style="font-weight:700;color:var(--ap-text);margin-bottom:3px">Besoin d'aide ?</div>
      <div style="color:var(--ap-muted);font-size:.85rem">Notre équipe est disponible pour toute question sur votre inscription ou vos formations.</div>
    </div>
    <a href="https://wa.me/2250778882592" target="_blank" rel="noopener"
       style="padding:10px 20px;background:#25d366;color:#fff;font-weight:700;border-radius:10px;text-decoration:none;font-size:.88rem;white-space:nowrap">
      📱 WhatsApp
    </a>
    <a href="mailto:formation@ibig-eduform.com"
       style="padding:10px 20px;background:rgba(255,255,255,.08);border:1px solid var(--ap-border);color:var(--ap-text);font-weight:700;border-radius:10px;text-decoration:none;font-size:.88rem;white-space:nowrap">
      ✉️ Email
    </a>
  </div>

</div>

<?php include __DIR__ . '/../partials/footer.php'; ?>
