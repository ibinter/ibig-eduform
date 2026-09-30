<?php
/* Bloc conversion fiche : Paiement en 3 étapes + Certificat vérifiable + Témoignages.
   Attend $f (formation). Utilise get_approved_avis() si disponible. */
if (!function_exists('h')) { function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); } }
$fcSam = !empty($f['is_samedi_pro']);
$fcTel = (int)($f['tarif_en_ligne'] ?? 0);
if (!$fcSam && $fcTel > 0 && $fcTel < 200000) $fcTel = 200000;
$fcTp  = (int)($f['tarif_presentiel'] ?? 0);
$fcReste = max(0, $fcTel - 150000);        // après inscription 50k + 1er versement 100k
$fcSplit = $fcReste > 100000;              // gros solde => 2 tranches en cours de formation
$fcT1 = (int)floor($fcReste / 2);
$fcT2 = $fcReste - $fcT1;
$fcDiff = max(0, $fcTp - $fcTel);          // supplément présentiel
$fcFcfa = function($n){ return number_format((int)$n, 0, ',', ' ') . ' FCFA'; };
$fcAvis = function_exists('get_approved_avis') ? get_approved_avis(3) : [];
?>
<style>
.ibfc{max-width:1180px;margin:44px auto 0;padding:0 20px;font-family:Inter,system-ui,sans-serif}
.ibfc-grid{display:grid;grid-template-columns:1.3fr 1fr;gap:22px;align-items:start}
.ibfc-card{background:#fff;border:1px solid #e3ebf6;border-radius:20px;padding:26px 28px;box-shadow:0 10px 26px rgba(10,23,51,.05)}
.ibfc-card h3{font-size:20px;font-weight:900;color:#0a1733;margin:0 0 18px;display:flex;align-items:center;gap:10px}
/* Paiement 3 étapes */
.ibfc-steps{display:flex;flex-direction:column;gap:14px}
.ibfc-step{display:flex;gap:14px;align-items:flex-start}
.ibfc-step .n{flex:none;width:34px;height:34px;border-radius:50%;background:#1f3fe0;color:#fff;font-weight:900;display:flex;align-items:center;justify-content:center;font-size:15px}
.ibfc-step .t b{display:block;color:#0a1733;font-size:15.5px;font-weight:800}
.ibfc-step .t span{display:block;color:#5b6b8c;font-size:13.5px;margin-top:2px;line-height:1.4}
.ibfc-step .amt{margin-left:auto;font-weight:900;color:#1f3fe0;font-size:16px;white-space:nowrap}
.ibfc-note{margin-top:14px;font-size:12.5px;color:#7a8aa8}
.ibfc-declenche{background:rgba(245,166,35,.12);border:1px solid rgba(245,166,35,.4);color:#8a5a00;border-radius:10px;padding:8px 12px;font-size:12.5px;font-weight:700;margin-top:12px}
/* Certificat vérifiable */
.ibfc-cert{display:flex;flex-direction:column;gap:14px}
.ibfc-cert .seal{display:flex;align-items:center;gap:14px}
.ibfc-cert .seal .ic{flex:none;width:56px;height:56px;border-radius:16px;background:linear-gradient(135deg,#1f3fe0,#3b82f6);color:#fff;display:flex;align-items:center;justify-content:center;font-size:26px}
.ibfc-cert .seal b{display:block;color:#0a1733;font-size:16px;font-weight:800}
.ibfc-cert .seal span{display:block;color:#5b6b8c;font-size:13.5px;margin-top:2px}
.ibfc-cert a.verify{display:inline-flex;align-items:center;justify-content:center;gap:8px;background:#0a1733;color:#fff;font-weight:800;font-size:14.5px;padding:13px 18px;border-radius:12px;text-decoration:none}
.ibfc-cert a.verify:hover{background:#12275e}
/* Témoignages */
.ibfc-avis{margin-top:22px}
.ibfc-avis .head{display:flex;align-items:center;gap:12px;margin-bottom:16px}
.ibfc-avis .stars{color:#f5a623;font-size:20px;letter-spacing:2px}
.ibfc-avis .head b{color:#0a1733;font-size:17px;font-weight:800}
.ibfc-avis .head span{color:#5b6b8c;font-size:13.5px}
.ibfc-cards{display:grid;grid-template-columns:repeat(3,1fr);gap:18px}
.ibfc-t{background:#f4f7fc;border:1px solid #e3ebf6;border-radius:16px;padding:20px}
.ibfc-t .q{color:#334155;font-size:14.5px;line-height:1.55;font-style:italic}
.ibfc-t .who{margin-top:14px;font-weight:800;color:#0a1733;font-size:14px}
.ibfc-t .who small{display:block;color:#7a8aa8;font-weight:600;font-size:12.5px;margin-top:2px}
@media(max-width:820px){.ibfc-grid,.ibfc-cards{grid-template-columns:1fr}}
</style>

<section class="ibfc">
  <div class="ibfc-grid">
    <!-- PAIEMENT -->
    <div class="ibfc-card">
      <?php if (!$fcSam): ?>
        <h3>💳 Paiement en <?= $fcSplit ? '4' : '3' ?> étapes</h3>
        <div class="ibfc-steps">
          <div class="ibfc-step"><span class="n">1</span><div class="t"><b>À l'inscription</b><span>Réservez votre place</span></div><span class="amt"><?= $fcFcfa(50000) ?></span></div>
          <div class="ibfc-step"><span class="n">2</span><div class="t"><b>Avant le démarrage</b><span>1er versement — déclenche la formation</span></div><span class="amt"><?= $fcFcfa(100000) ?></span></div>
          <?php if ($fcSplit): ?>
            <div class="ibfc-step"><span class="n">3</span><div class="t"><b>1re tranche</b><span>En cours de formation</span></div><span class="amt"><?= $fcFcfa($fcT1) ?></span></div>
            <div class="ibfc-step"><span class="n">4</span><div class="t"><b>2e tranche</b><span>En cours de formation — libère la certification</span></div><span class="amt"><?= $fcFcfa($fcT2) ?></span></div>
          <?php else: ?>
            <div class="ibfc-step"><span class="n">3</span><div class="t"><b>À mi-parcours</b><span>Solde final — libère la certification</span></div><span class="amt"><?= $fcFcfa($fcReste) ?></span></div>
          <?php endif; ?>
        </div>
        <div class="ibfc-declenche">🚀 Le 1er versement de 100 000 FCFA garantit le démarrage de la session.</div>
        <div class="ibfc-note">Montants indiqués sur le tarif en ligne.<?php if ($fcDiff > 0): ?> Présentiel : +<?= $fcFcfa($fcDiff) ?>.<?php endif; ?></div>
      <?php else: ?>
        <h3>💳 Modalité de paiement</h3>
        <div class="ibfc-steps">
          <div class="ibfc-step"><span class="n">✓</span><div class="t"><b>Règlement en totalité</b><span>Sans frais d'inscription — formule Samedi Pro</span></div><span class="amt"><?= $fcFcfa($fcTel) ?></span></div>
        </div>
        <div class="ibfc-declenche">🎟️ Places limitées : réservation confirmée au règlement.</div>
        <div class="ibfc-note">Tarif en ligne. Présentiel selon la session.</div>
      <?php endif; ?>
    </div>

    <!-- CERTIFICAT VÉRIFIABLE -->
    <div class="ibfc-card ibfc-cert">
      <h3>🎓 <?= $fcSam ? 'Attestation officielle' : 'Certificat vérifiable' ?></h3>
      <div class="seal">
        <div class="ic">✅</div>
        <div>
          <b><?= $fcSam ? 'Attestation de participation IBIG EDUFORM' : 'Certificat authentifiable en ligne' ?></b>
          <span><?= $fcSam ? 'Remise à l\'issue de la session' : 'Chaque certificat porte un identifiant vérifiable' ?></span>
        </div>
      </div>
      <a class="verify" href="https://verify.intermark-business.com/" target="_blank" rel="noopener">🔎 Vérifier un certificat</a>
    </div>
  </div>

  <?php if ($fcAvis): ?>
  <div class="ibfc-avis">
    <div class="head" style="flex-wrap:wrap">
      <span class="stars">★★★★★</span>
      <b>Ils ont suivi nos formations</b>
      <span>· Avis vérifiés de nos apprenants</span>
      <a href="/avis.php#laisser-avis" style="margin-left:auto;color:#1f3fe0;font-weight:800;font-size:14px;text-decoration:none">✍️ Laisser un avis</a>
    </div>
    <div class="ibfc-cards">
      <?php foreach ($fcAvis as $a):
        $who = trim((string)($a['nom'] ?? ''));
        $meta = trim(implode(' · ', array_filter([$a['secteur'] ?? '', $a['ville'] ?? '', $a['pays'] ?? ''])));
      ?>
        <div class="ibfc-t">
          <div class="q">“<?= h(mb_strimwidth((string)($a['texte'] ?? ''), 0, 180, '…', 'UTF-8')); ?>”</div>
          <div class="who"><?= h($who !== '' ? $who : 'Apprenant IBIG EDUFORM'); ?><?php if ($meta !== ''): ?><small><?= h($meta); ?></small><?php endif; ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
</section>
