<?php
declare(strict_types=1);
require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/apprenant_auth.php';
apprenant_require();

$apprenant = apprenant_get();
$email     = $apprenant['email'];
$pdo       = Database::connect();
$pageTitle = 'Mon espace apprenant — IBIG EDUFORM';

/* ── Inscriptions (avec tdr_pdf et statut paiement) ── */
$stmtIns = $pdo->prepare("
    SELECT p.id, p.created_at, p.type_preinscription, p.mode_formation, p.statut,
           p.domaine_interet, p.formation_id,
           f.titre AS formation_titre, f.slug AS formation_slug,
           f.date_debut, f.duree, f.tdr_pdf,
           MAX(CASE WHEN pi.statut='paye' THEN 1 ELSE 0 END) AS est_paye
    FROM preinscriptions p
    LEFT JOIN formations f ON f.id = p.formation_id
    LEFT JOIN paiements_inscription pi ON pi.formation_id = p.formation_id
              AND LOWER(pi.customer_email) = LOWER(p.email)
    WHERE LOWER(p.email) = ?
    GROUP BY p.id
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

/* ── Stats globales ── */
$nbActives   = count(array_filter($inscriptions, fn($i) => !in_array($i['statut'], ['annule'])));
$nbConfirmes = count(array_filter($inscriptions, fn($i) => in_array($i['statut'], ['confirme','inscrit'])));
$nbPayes     = count(array_filter($paiements, fn($p) => $p['statut'] === 'paye'));

/* ── Prochaine formation ── */
$prochaine = null;
foreach ($inscriptions as $ins) {
    if (!empty($ins['date_debut']) && !in_array($ins['statut'], ['annule'])) {
        $ts = strtotime($ins['date_debut']);
        if ($ts > time()) { $prochaine = $ins; break; }
    }
}

/* ── Helpers ── */
$modeLabel = [
    'presentiel' => '🏫 Présentiel',
    'en_ligne'   => '💻 En ligne',
    'hybride'    => '🔀 Hybride',
    'elearning'  => '🎓 E-learning',
];
$statutPay = [
    'en_attente' => ['⏳ En attente', '#f59e0b'],
    'paye'       => ['✅ Payé',       '#22c55e'],
    'echoue'     => ['❌ Échoué',     '#ef4444'],
];

include __DIR__ . '/../partials/header.php';
?>
<style>
:root { --ap-bg:#050d1a; --ap-card:#0b1220; --ap-border:rgba(255,255,255,.08); --ap-gold:#f59e0b; --ap-text:#e5e7eb; --ap-muted:#94a3b8; }
.ap-layout { max-width:960px; margin:0 auto; padding:80px 18px 100px; }

/* Hero */
.ap-hero { background:var(--ap-card); border:1px solid var(--ap-border); border-radius:16px; padding:28px 32px; display:flex; align-items:center; gap:20px; margin-bottom:16px; flex-wrap:wrap; }
.ap-avatar { width:60px; height:60px; border-radius:50%; background:linear-gradient(135deg,#f59e0b,#d97706); display:flex; align-items:center; justify-content:center; font-size:1.6rem; font-weight:900; color:#0a1733; flex-shrink:0; }
.ap-hero-info h1 { margin:0 0 4px; color:var(--ap-gold); font-size:1.4rem; }
.ap-hero-info p  { margin:0; color:var(--ap-muted); font-size:.9rem; }
.ap-logout { margin-left:auto; padding:8px 16px; border:1px solid rgba(255,255,255,.18); border-radius:8px; color:var(--ap-muted); text-decoration:none; font-size:.82rem; white-space:nowrap; }
.ap-logout:hover { border-color:#ef4444; color:#fca5a5; }

/* Stats bar */
.ap-stats { display:flex; gap:12px; margin-bottom:20px; flex-wrap:wrap; }
.ap-stat { background:var(--ap-card); border:1px solid var(--ap-border); border-radius:12px; padding:14px 20px; flex:1; min-width:120px; }
.ap-stat-val { font-size:1.6rem; font-weight:900; color:var(--ap-gold); line-height:1; }
.ap-stat-lbl { font-size:.75rem; color:var(--ap-muted); margin-top:4px; }

/* Prochaine formation */
.ap-next { background:linear-gradient(135deg,rgba(245,158,11,.12),rgba(245,158,11,.04)); border:1px solid rgba(245,158,11,.3); border-radius:14px; padding:18px 22px; margin-bottom:20px; display:flex; align-items:center; gap:16px; flex-wrap:wrap; }
.ap-next-badge { background:var(--ap-gold); color:#0a1733; font-size:.72rem; font-weight:800; padding:3px 10px; border-radius:20px; white-space:nowrap; }
.ap-countdown { font-size:1.1rem; font-weight:800; color:var(--ap-gold); }
.ap-countdown small { font-size:.72rem; font-weight:400; color:var(--ap-muted); display:block; }

/* Section title */
.ap-section-title { font-size:1rem; font-weight:700; color:var(--ap-gold); margin:28px 0 14px; letter-spacing:.03em; text-transform:uppercase; }

/* Cards */
.ins-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(290px,1fr)); gap:16px; }
.ins-card { background:var(--ap-card); border:1px solid var(--ap-border); border-radius:14px; padding:20px; display:flex; flex-direction:column; gap:14px; }
.ins-card-top { display:flex; justify-content:space-between; align-items:flex-start; gap:10px; }
.ins-titre { font-weight:700; color:var(--ap-text); font-size:.95rem; line-height:1.4; flex:1; }
.ins-badge { font-size:.72rem; padding:3px 10px; border-radius:20px; font-weight:700; white-space:nowrap; flex-shrink:0; }
.ins-meta { font-size:.8rem; color:var(--ap-muted); display:flex; flex-direction:column; gap:5px; }
.ins-meta span { display:flex; align-items:center; gap:6px; }

/* Stepper */
.ins-stepper { display:flex; align-items:center; gap:0; }
.step { display:flex; flex-direction:column; align-items:center; flex:1; }
.step-dot { width:22px; height:22px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:.65rem; font-weight:800; flex-shrink:0; }
.step-dot.done  { background:#22c55e; color:#fff; }
.step-dot.curr  { background:var(--ap-gold); color:#0a1733; }
.step-dot.wait  { background:rgba(255,255,255,.08); color:var(--ap-muted); border:1px solid rgba(255,255,255,.15); }
.step-lbl { font-size:.58rem; color:var(--ap-muted); margin-top:4px; text-align:center; line-height:1.2; }
.step-lbl.done  { color:#4ade80; }
.step-lbl.curr  { color:var(--ap-gold); }
.step-line { flex:1; height:2px; background:rgba(255,255,255,.08); margin-bottom:14px; }
.step-line.done { background:#22c55e44; }

/* Countdown inline */
.ins-countdown { font-size:.78rem; background:rgba(245,158,11,.1); border:1px solid rgba(245,158,11,.25); border-radius:8px; padding:6px 10px; color:var(--ap-gold); font-weight:700; text-align:center; }

/* Actions */
.ins-actions { display:flex; gap:8px; flex-wrap:wrap; margin-top:auto; }
.ins-btn { padding:7px 14px; border-radius:8px; font-size:.78rem; font-weight:700; text-decoration:none; border:none; cursor:pointer; display:inline-flex; align-items:center; gap:5px; }
.ins-btn-primary { background:var(--ap-gold); color:#0a1733; }
.ins-btn-outline { background:transparent; border:1px solid rgba(255,255,255,.2); color:var(--ap-text); }
.ins-btn:hover { opacity:.85; }

/* Paiements */
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

  <!-- Stats -->
  <div class="ap-stats">
    <div class="ap-stat">
      <div class="ap-stat-val"><?= $nbActives ?></div>
      <div class="ap-stat-lbl">Inscription<?= $nbActives > 1 ? 's' : '' ?> active<?= $nbActives > 1 ? 's' : '' ?></div>
    </div>
    <div class="ap-stat">
      <div class="ap-stat-val"><?= $nbConfirmes ?></div>
      <div class="ap-stat-lbl">Formation<?= $nbConfirmes > 1 ? 's' : '' ?> confirmée<?= $nbConfirmes > 1 ? 's' : '' ?></div>
    </div>
    <div class="ap-stat">
      <div class="ap-stat-val"><?= $nbPayes ?></div>
      <div class="ap-stat-lbl">Paiement<?= $nbPayes > 1 ? 's' : '' ?> effectué<?= $nbPayes > 1 ? 's' : '' ?></div>
    </div>
  </div>

  <!-- Prochaine formation -->
  <?php if ($prochaine): ?>
  <?php
    $tsDebut   = strtotime($prochaine['date_debut']);
    $joursRest = (int)ceil(($tsDebut - time()) / 86400);
    $titreProch = $prochaine['formation_titre'] ?: ($prochaine['domaine_interet'] ?: 'Formation');
  ?>
  <div class="ap-next">
    <div style="font-size:1.8rem">🎓</div>
    <div style="flex:1">
      <div style="font-size:.72rem;color:var(--ap-muted);margin-bottom:4px">PROCHAINE FORMATION</div>
      <div style="font-weight:700;color:var(--ap-text);margin-bottom:6px"><?= htmlspecialchars($titreProch) ?></div>
      <span class="ap-next-badge">🗓️ <?= date('d/m/Y', $tsDebut) ?></span>
    </div>
    <div class="ap-countdown" data-ts="<?= $tsDebut ?>">
      <?= $joursRest ?> jour<?= $joursRest > 1 ? 's' : '' ?>
      <small>avant le début</small>
    </div>
  </div>
  <?php endif; ?>

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
        $titre     = $ins['formation_titre'] ?: ($ins['domaine_interet'] ?: 'Formation');
        $statut    = $ins['statut'];
        $estPaye   = (bool)$ins['est_paye'];
        $estConfirme = in_array($statut, ['confirme', 'inscrit']);
        $estAnnule   = $statut === 'annule';

        /* Statut badge */
        $stColors = [
            'en_attente' => ['⏳ En attente', '#f59e0b'],
            'confirme'   => ['✅ Confirmé',   '#22c55e'],
            'annule'     => ['❌ Annulé',     '#ef4444'],
            'inscrit'    => ['✅ Inscrit',    '#22c55e'],
            'liste_att'  => ['📋 Liste d\'att.', '#a78bfa'],
        ];
        $st = $stColors[$statut] ?? ['⏳ ' . $statut, '#94a3b8'];

        $mode       = $modeLabel[$ins['mode_formation']] ?? ($ins['mode_formation'] ?: '—');
        $date       = $ins['created_at'] ? date('d/m/Y', strtotime($ins['created_at'])) : '—';
        $dateDebut  = !empty($ins['date_debut']) ? strtotime($ins['date_debut']) : null;
        $dateLisible = $dateDebut ? date('d/m/Y', $dateDebut) : null;
        $joursAvant = $dateDebut ? (int)ceil(($dateDebut - time()) / 86400) : null;
        $aVenir     = $dateDebut && $dateDebut > time() && !$estAnnule;

        $lienDetail  = $ins['formation_slug'] ? '/formation/' . urlencode($ins['formation_slug']) : null;
        $lienTDRdyn  = !empty($ins['formation_slug']) ? '/tdr-local-pdf.php?slug=' . urlencode($ins['formation_slug']) : null;
        $lienTDRpdf  = !empty($ins['tdr_pdf']) ? '/uploads/tdr/' . urlencode(basename($ins['tdr_pdf'])) : null;
        $lienTDR     = $lienTDRpdf ?: $lienTDRdyn;
        $lienPay     = $ins['formation_id'] && !$estAnnule && !$estPaye ? '/paiement-inscription.php?formation=' . (int)$ins['formation_id'] : null;

        /* Stepper : 4 étapes */
        $s1 = 'done'; // inscription toujours faite
        $s2 = $estPaye ? 'done' : ($estAnnule ? 'wait' : 'curr');
        $s3 = $estConfirme ? 'done' : ($estPaye ? 'curr' : 'wait');
        $s4 = ($estConfirme && $dateDebut && $dateDebut <= time()) ? 'done'
            : ($estConfirme ? 'curr' : 'wait');
      ?>
      <div class="ins-card">

        <!-- Top : titre + badge statut -->
        <div class="ins-card-top">
          <div class="ins-titre"><?= htmlspecialchars($titre) ?></div>
          <?php if (!$estAnnule): ?>
          <span class="ins-badge" style="background:<?= $st[1] ?>22;color:<?= $st[1] ?>;border:1px solid <?= $st[1] ?>44">
            <?= $st[0] ?>
          </span>
          <?php else: ?>
          <span class="ins-badge" style="background:#ef444422;color:#ef4444;border:1px solid #ef444444">❌ Annulé</span>
          <?php endif; ?>
        </div>

        <!-- Stepper progression -->
        <?php if (!$estAnnule): ?>
        <div class="ins-stepper">
          <div class="step">
            <div class="step-dot done">✓</div>
            <div class="step-lbl done">Inscrit</div>
          </div>
          <div class="step-line <?= $s2 === 'done' ? 'done' : '' ?>"></div>
          <div class="step">
            <div class="step-dot <?= $s2 ?>">
              <?= $s2 === 'done' ? '✓' : ($s2 === 'curr' ? '2' : '2') ?>
            </div>
            <div class="step-lbl <?= $s2 ?>">Paiement</div>
          </div>
          <div class="step-line <?= $s3 === 'done' ? 'done' : '' ?>"></div>
          <div class="step">
            <div class="step-dot <?= $s3 ?>">
              <?= $s3 === 'done' ? '✓' : '3' ?>
            </div>
            <div class="step-lbl <?= $s3 ?>">Confirmé</div>
          </div>
          <div class="step-line <?= $s4 === 'done' ? 'done' : '' ?>"></div>
          <div class="step">
            <div class="step-dot <?= $s4 ?>">
              <?= $s4 === 'done' ? '✓' : '4' ?>
            </div>
            <div class="step-lbl <?= $s4 ?>">Formation</div>
          </div>
        </div>
        <?php endif; ?>

        <!-- Méta -->
        <div class="ins-meta">
          <span>📅 Inscrit le <?= $date ?></span>
          <span><?= $mode ?></span>
          <?php if ($dateLisible): ?><span>🗓️ Début : <?= $dateLisible ?></span><?php endif; ?>
          <?php if ($ins['duree']): ?><span>⏱️ <?= htmlspecialchars($ins['duree']) ?></span><?php endif; ?>
        </div>

        <!-- Countdown si formation à venir et confirmée -->
        <?php if ($aVenir && $joursAvant !== null && $joursAvant <= 30 && $estConfirme): ?>
        <div class="ins-countdown" data-ts="<?= $dateDebut ?>">
          🕐 Dans <?= $joursAvant ?> jour<?= $joursAvant > 1 ? 's' : '' ?>
        </div>
        <?php elseif ($aVenir && $joursAvant !== null && $joursAvant <= 14 && !$estConfirme): ?>
        <div class="ins-countdown" style="border-color:rgba(245,158,11,.5);background:rgba(245,158,11,.15)">
          ⚠️ Début dans <?= $joursAvant ?> jour<?= $joursAvant > 1 ? 's' : '' ?> — confirmez votre place
        </div>
        <?php endif; ?>

        <!-- Actions -->
        <div class="ins-actions">
          <?php if ($lienDetail): ?>
            <a href="<?= $lienDetail ?>" class="ins-btn ins-btn-outline" target="_blank">🔍 Formation</a>
          <?php endif; ?>
          <?php if ($lienTDR): ?>
            <a href="<?= $lienTDR ?>" class="ins-btn ins-btn-outline" target="_blank">📄 TDR</a>
          <?php endif; ?>
          <?php if ($lienPay): ?>
            <a href="<?= $lienPay ?>" class="ins-btn ins-btn-primary">💳 Payer</a>
          <?php elseif ($estPaye && !$estConfirme): ?>
            <span class="ins-btn" style="background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.3);color:#4ade80;cursor:default">✅ Paiement reçu</span>
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
          $ps      = $statutPay[$pay['statut']] ?? ['⏳ ' . $pay['statut'], '#94a3b8'];
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

<script>
/* Countdown dynamique (mise à jour toutes les heures) */
document.querySelectorAll('[data-ts]').forEach(function(el) {
  var ts = parseInt(el.dataset.ts, 10) * 1000;
  function update() {
    var diff = ts - Date.now();
    if (diff <= 0) return;
    var days = Math.ceil(diff / 86400000);
    var countdownEl = el.querySelector('.ap-countdown') || el;
    /* ne modifier que les éléments de countdown top-level */
    if (el.classList.contains('ap-countdown') || el.classList.contains('ap-next')) {
      /* laisse PHP gérer l'affichage initial */
    }
  }
});
</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>
