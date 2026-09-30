<?php
/* Bloc conversion — réutilisable (fiche simple + landing). Nécessite $f. */
if (!function_exists('h')) { function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); } }
$modsList = array_values(array_filter(array_map('trim', explode(';', (string)($f['modules'] ?? '')))));
$titreFmt = (string)($f['titre'] ?? '');
$waMsg    = rawurlencode("Bonjour, je souhaite des informations sur la formation : " . $titreFmt);
$estSamedi = !empty($f['is_samedi_pro']);
$nbCerts   = 0;
if (!$estSamedi && preg_match('/(\d+)\s*en\s*1/i', $titreFmt, $mCert)) { $nbCerts = (int)$mCert[1]; }
$STATS = [
  [setting('stat_apprenants',   '1 000+'), 'Apprenants formés'],
  [setting('stat_satisfaction', '+90%'),   'Taux de satisfaction'],
  [setting('stat_experience',   '3 ans'),  "d'expérience"],
  [setting('stat_certifiantes', '100%'),   'Formations certifiantes'],
];
$secteurs = [
  'Banques & microfinance', 'ONG & projets de développement', 'Cabinets comptables & d\'audit',
  'PME & grandes entreprises', 'Administrations & collectivités', 'Indépendants & entrepreneurs',
];
$temoignages = [];
foreach (get_approved_avis(12) as $a) {
  $loc = trim((string)($a['ville'] ?? '') . ' ' . (string)($a['pays'] ?? ''));
  $meta = trim(implode(' · ', array_filter([(string)($a['secteur'] ?? ''), $loc])));
  $temoignages[] = [
    'texte'  => (string)($a['texte'] ?? ''),
    'auteur' => trim((string)($a['nom'] ?? 'Apprenant') . ($meta !== '' ? ' — ' . $meta : '')),
    'note'   => 5,
  ];
}
$waNum = function_exists('setting') ? preg_replace('/\D/', '', (string)setting('whatsapp_number', '2250778882592')) : '2250778882592';
?>
<style>
  .conv{margin-top:30px;display:grid;gap:22px;min-width:0}
  .conv>*{min-width:0;max-width:100%}
  .conv-card{background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.09);border-radius:16px;padding:24px;min-width:0;overflow:hidden}
  .conv-card h3{margin:0 0 16px;font-size:17px;display:flex;align-items:center;gap:9px;color:#fff}
  .benefits{list-style:none;margin:0;padding:0;display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:11px 20px}
  .benefits li{display:flex;gap:10px;align-items:flex-start;font-size:14px;line-height:1.5;color:#d7dff1}
  .benefits li i{color:#34d399;margin-top:3px}
  .cert-row{display:flex;flex-wrap:wrap;gap:14px}
  .cert-box{flex:1;min-width:220px;background:linear-gradient(135deg,rgba(245,158,11,.14),rgba(232,36,44,.08));border:1px solid rgba(245,158,11,.32);border-radius:14px;padding:18px;color:#f3e6cf}
  .cert-box .ico{font-size:24px;display:block;margin-bottom:8px}
  .cert-box b{color:#fff}
  .bonus{list-style:none;margin:14px 0 0;padding:0;display:grid;gap:9px}
  .bonus li{display:flex;gap:10px;align-items:center;font-size:14px;color:#d7dff1}
  .bonus li i{color:#fbbf24;width:18px;text-align:center}
  .stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:16px;text-align:center}
  .stats .s b{display:block;font-size:27px;font-weight:800;background:linear-gradient(135deg,#fbbf24,#fb7185);-webkit-background-clip:text;background-clip:text;color:transparent}
  .stats .s span{font-size:12px;color:#9fb0c9}
  .faq details{border:1px solid rgba(255,255,255,.09);border-radius:12px;margin-bottom:10px;background:rgba(255,255,255,.03)}
  .faq summary{cursor:pointer;padding:14px 16px;font-weight:600;font-size:14px;color:#eaf0fb;list-style:none;display:flex;justify-content:space-between;gap:12px;align-items:center}
  .faq summary::-webkit-details-marker{display:none}
  .faq summary::after{content:'+';color:#fbbf24;font-weight:800;font-size:18px}
  .faq details[open] summary::after{content:'\2013'}
  .faq p{margin:0;padding:0 16px 14px;font-size:13.5px;color:#c3cee0;line-height:1.6}
  .advisor-wrap{display:flex;flex-wrap:wrap;align-items:center;gap:14px;margin-top:6px}
  .advisor{display:inline-flex;align-items:center;gap:10px;background:#25D366;color:#06351e;text-decoration:none;font-weight:800;padding:13px 20px;border-radius:12px;font-size:14px}
  .advisor:hover{filter:brightness(1.06)}
  .rating{display:flex;flex-wrap:wrap;align-items:center;gap:10px 16px;margin-bottom:16px}
  .rating .stars{font-size:22px;letter-spacing:2px;color:#fbbf24;line-height:1}
  .rating .stars .dim{color:rgba(251,191,36,.35)}
  .rating .txt{font-size:14px;color:#d7dff1}
  .rating .txt b{color:#fff;font-size:16px}
  .secteurs p{margin:0 0 10px;font-size:13.5px;color:#c3cee0}
  .secteurs .chips{display:flex;flex-wrap:wrap;gap:8px}
  .secteurs .chips span{background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:999px;padding:7px 13px;font-size:12.5px;color:#e7edf8}
  .temo-marquee{overflow:hidden;position:relative;min-width:0;max-width:100%;-webkit-mask-image:linear-gradient(90deg,transparent,#000 7%,#000 93%,transparent);mask-image:linear-gradient(90deg,transparent,#000 7%,#000 93%,transparent)}
  .temo-track{display:flex;gap:16px;width:max-content;animation:temoScroll 45s linear infinite}
  .temo-marquee:hover .temo-track{animation-play-state:paused}
  .temo-track .temo{flex:0 0 300px;width:300px}
  @keyframes temoScroll{from{transform:translateX(0)}to{transform:translateX(-50%)}}
  @media(prefers-reduced-motion:reduce){.temo-track{animation:none;flex-wrap:wrap;width:auto}}
  .temo{margin:0;background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.09);border-radius:14px;padding:18px}
  .temo .stars{color:#fbbf24;font-size:14px;letter-spacing:1px;margin-bottom:8px}
  .temo blockquote{margin:0 0 10px;font-size:14px;line-height:1.6;color:#e7edf8;font-style:italic}
  .temo figcaption{font-size:12.5px;color:#9fb0c9;font-weight:600}
  .temo-note{margin:14px 0 0;font-size:11.5px;color:#7f8ca5;font-style:italic}
  .lead-choices{display:flex;flex-wrap:wrap;gap:8px 18px;margin-bottom:14px}
  .lead-choices label{display:inline-flex;align-items:center;gap:7px;font-size:13px;color:#d7dff1;cursor:pointer}
  .lead-choices input{accent-color:#4d6bff}
  .lead-fields{display:grid;grid-template-columns:1fr 1fr auto;gap:10px}
  .lead-fields input{padding:12px 13px;border-radius:11px;border:1.5px solid rgba(255,255,255,.12);background:rgba(255,255,255,.05);color:#fff;font-size:14px;font-family:inherit}
  .lead-fields input::placeholder{color:#8c98b0}
  .lead-fields input:focus{outline:none;border-color:#4d6bff;background:rgba(255,255,255,.08)}
  #leadBtn{border:0;border-radius:11px;cursor:pointer;font-family:inherit;font-weight:700;font-size:14px;color:#06351e;background:#fbbf24;padding:0 20px;white-space:nowrap}
  #leadBtn:hover{filter:brightness(1.05)} #leadBtn:disabled{opacity:.6}
  .lead-msg{margin:12px 0 0;font-size:13px;min-height:18px}
  @media(max-width:560px){.lead-fields{grid-template-columns:1fr}}
  .atouts{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px}
  .atout{background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.09);border-radius:14px;padding:18px;min-width:0}
  .atout .ai{font-size:26px;margin-bottom:8px}
  .atout b{color:#fff;display:block;margin-bottom:6px}
  .atout p{margin:0 0 10px;font-size:13.5px;color:#c3cee0;line-height:1.55}
  .atout-cta{display:inline-block;font-weight:700;font-size:13px;color:#fbbf24;text-decoration:none}
  .atout-cta:hover{text-decoration:underline}
</style>

<div class="conv">

  <!-- BÉNÉFICES + CERTIFICAT + BONUS -->
  <div class="conv-card">
    <?php if ($modsList): ?>
      <h3><i class="fa-solid fa-circle-check" style="color:#34d399"></i> Ce que vous saurez faire</h3>
      <ul class="benefits">
        <?php foreach ($modsList as $mod): ?>
          <li><i class="fa-solid fa-check"></i> Maîtriser <?= h($mod); ?></li>
        <?php endforeach; ?>
      </ul>
      <hr style="border:0;border-top:1px solid rgba(255,255,255,.08);margin:22px 0">
    <?php endif; ?>

    <div class="cert-row">
      <div class="cert-box">
        <span class="ico">🎓</span>
        <?php if ($estSamedi): ?>
          <b>Attestation délivrée</b><br>
          Une <b>attestation de participation IBIG EDUFORM</b> vous est remise à l'issue de la formation, valorisable sur votre CV.
        <?php elseif ($nbCerts >= 2): ?>
          <b><?= $nbCerts; ?> certificats à la clé</b><br>
          Ce programme <b><?= $nbCerts; ?> en 1</b> délivre <b><?= $nbCerts; ?> certificats IBIG EDUFORM</b> à l'issue de la formation :
          <ul class="bonus" style="margin-top:12px">
            <?php foreach ($modsList as $mod): ?>
              <li><i class="fa-solid fa-certificate"></i> Certificat «&nbsp;<?= h($mod); ?>&nbsp;»</li>
            <?php endforeach; ?>
          </ul>
        <?php else: ?>
          <b>1 certificat à la clé</b><br>
          Un <b>certificat IBIG EDUFORM aux métiers de «&nbsp;<?= h($titreFmt); ?>&nbsp;»</b> vous est délivré à l'issue de la formation, valorisable sur votre CV.
        <?php endif; ?>
      </div>
      <div class="cert-box" style="background:linear-gradient(135deg,rgba(31,63,224,.16),rgba(122,47,206,.10));border-color:rgba(77,107,255,.35)">
        <span class="ico">🎁</span>
        <b>Bonus inclus</b>
        <ul class="bonus">
          <li><i class="fa-solid fa-file-arrow-down"></i> Support de cours &amp; ressources</li>
          <li><i class="fa-solid fa-toolbox"></i> Outils &amp; modèles pratiques</li>
          <li><i class="fa-brands fa-whatsapp"></i> Groupe d'entraide &amp; suivi</li>
          <li><i class="fa-solid fa-headset"></i> Accompagnement post-formation</li>
        </ul>
      </div>
    </div>
  </div>

  <!-- CHIFFRES CLÉS -->
  <div class="conv-card">
    <h3><i class="fa-solid fa-chart-line" style="color:#fbbf24"></i> Ils nous font confiance</h3>
    <div class="stats">
      <?php foreach ($STATS as $st): ?>
        <div class="s"><b><?= h($st[0]); ?></b><span><?= h($st[1]); ?></span></div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- AVIS AGRÉGÉ + SECTEURS -->
  <div class="conv-card">
    <h3><i class="fa-solid fa-star" style="color:#fbbf24"></i> Avis &amp; débouchés</h3>
    <div class="rating">
      <div class="stars" aria-label="4,5 sur 5">★★★★<span class="dim">★</span></div>
      <div class="txt"><b>≈ 4,5/5</b> &middot; <?= h(setting('stat_satisfaction','+90%')); ?> de satisfaction sur <?= h(setting('stat_apprenants','1 000+')); ?> apprenants formés</div>
    </div>
    <div class="secteurs">
      <p>Nos diplômés exercent dans :</p>
      <div class="chips">
        <?php foreach ($secteurs as $sec): ?><span><?= h($sec); ?></span><?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- TÉMOIGNAGES (carrousel défilant, toutes formations) -->
  <?php if (!empty($temoignages)): ?>
  <div class="conv-card">
    <h3><i class="fa-solid fa-quote-left" style="color:#34d399"></i> Ils témoignent</h3>
    <div class="temo-marquee">
      <div class="temo-track">
        <?php for ($pass = 0; $pass < 2; $pass++): foreach ($temoignages as $t): $note = max(1, min(5, (int)($t['note'] ?? 5))); ?>
          <figure class="temo">
            <div class="stars"><?= str_repeat('★', $note) . str_repeat('☆', 5 - $note); ?></div>
            <blockquote>«&nbsp;<?= h($t['texte'] ?? ''); ?>&nbsp;»</blockquote>
            <figcaption>— <?= h($t['auteur'] ?? 'Apprenant IBIG EDUFORM'); ?></figcaption>
          </figure>
        <?php endforeach; endfor; ?>
      </div>
    </div>
    <p class="temo-note">Avis de participants IBIG EDUFORM — toutes formations.</p>
  </div>
  <?php endif; ?>

  <!-- FAQ -->
  <div class="conv-card faq">
    <h3><i class="fa-solid fa-circle-question" style="color:#4d6bff"></i> Questions fréquentes</h3>
    <details open>
      <summary>La formation est-elle en ligne ou en présentiel ?</summary>
      <p>Les deux ! Vous choisissez le format qui vous convient : <b>présentiel</b> à Abidjan ou <b>en ligne</b> en visioconférence interactive. Les tarifs des deux formats sont indiqués sur cette page.</p>
    </details>
    <details>
      <summary>Vais-je recevoir un certificat ?</summary>
      <p>
        <?php if ($estSamedi): ?>
          Oui, une <b>attestation de participation IBIG EDUFORM</b> vous est délivrée à la fin de la formation.
        <?php elseif ($nbCerts >= 2): ?>
          Oui — ce programme <b><?= $nbCerts; ?> en 1</b> délivre <b><?= $nbCerts; ?> certificats IBIG EDUFORM</b> à l'issue de la formation (un par spécialité).
        <?php else: ?>
          Oui, un <b>certificat IBIG EDUFORM aux métiers de «&nbsp;<?= h($titreFmt); ?>&nbsp;»</b> vous est délivré à la fin de la formation.
        <?php endif; ?>
      </p>
    </details>
    <details>
      <summary>Comment se passe le paiement ?</summary>
      <p>Vous réglez d'abord les <b>frais d'inscription</b> pour réserver votre place. Des <b>facilités de paiement</b> sont possibles : parlez-en à un conseiller.</p>
    </details>
    <details>
      <summary>Faut-il un niveau particulier ?</summary>
      <p>Nos formations sont accessibles, du débutant au confirmé. Le <b>public cible</b> est précisé plus haut sur cette page.</p>
    </details>
    <details>
      <summary>Et si je manque une séance ?</summary>
      <p>En format en ligne, les ressources vous permettent de rattraper. En cas d'absence, contactez-nous : nous trouvons une solution.</p>
    </details>

    <div class="advisor-wrap">
      <a class="advisor" href="https://wa.me/<?= h($waNum); ?>?text=<?= $waMsg; ?>" target="_blank" rel="noopener">
        <i class="fa-brands fa-whatsapp" style="font-size:18px"></i> Parler à un conseiller
      </a>
      <span style="color:#9fb0c9;font-size:13px">Une question ? Réponse rapide sur WhatsApp.</span>
    </div>
  </div>

  <!-- ENTREPRISE / EXPERTS / INSERTION -->
  <div class="conv-card">
    <h3><i class="fa-solid fa-people-group" style="color:#fbbf24"></i> Aller plus loin avec IBIG EDUFORM</h3>
    <div class="atouts">
      <div class="atout">
        <div class="ai">🏢</div>
        <b>Groupe &amp; Entreprise</b>
        <p>Vous êtes une entreprise ou un groupe intéressé par cette formation ? Vous êtes les bienvenus : <b>remise dédiée</b> pour les inscriptions groupées et les formations sur mesure.</p>
        <a class="atout-cta" href="https://wa.me/<?= h($waNum); ?>?text=<?= rawurlencode('Bonjour, je représente une entreprise / un groupe intéressé par la formation : ' . $titreFmt . '. Pouvez-vous m\'envoyer les conditions de groupe ?'); ?>" target="_blank" rel="noopener">Demander une remise groupe →</a>
      </div>
      <div class="atout">
        <div class="ai">🎓</div>
        <b>Formateurs experts</b>
        <p>Des <b>professionnels en activité</b> (experts-comptables, DAF, consultants…) qui transmettent des compétences directement applicables sur le terrain.</p>
      </div>
      <div class="atout">
        <div class="ai">🚀</div>
        <b>Insertion professionnelle</b>
        <p>À l'issue de la formation, IBIG EDUFORM vous accompagne dans votre quête d'emploi : <b>coaching, suivi et recommandation</b>.</p>
        <a class="atout-cta" href="/opportunites-emploi.php">Découvrir l'accompagnement →</a>
      </div>
    </div>
  </div>

  <!-- CAPTURE DE LEADS -->
  <div class="conv-card" id="leadCard">
    <h3><i class="fa-solid fa-paper-plane" style="color:#fbbf24"></i> Recevez le programme &amp; restez informé</h3>
    <div class="lead-choices">
      <label><input type="radio" name="lead_type" value="calendrier" checked> 📄 Calendrier complet (PDF)</label>
      <label><input type="radio" name="lead_type" value="rappel"> 📞 Être rappelé(e)</label>
      <label><input type="radio" name="lead_type" value="alerte"> 🔔 Alerte nouvelles sessions</label>
    </div>
    <div class="lead-fields">
      <input id="lead_nom" type="text" placeholder="Votre nom" autocomplete="name">
      <input id="lead_contact" type="text" placeholder="WhatsApp ou email" autocomplete="tel">
      <button id="leadBtn" type="button">Recevoir →</button>
    </div>
    <p class="lead-msg" id="leadMsg"></p>
  </div>
  <script>
    var LEAD_CSRF = <?= json_encode(function_exists('csrf_token') ? csrf_token() : ''); ?>;
    var LEAD_FID = <?= (int)($f['id'] ?? 0); ?>;
    var LEAD_FTITRE = <?= json_encode((string)($f['titre'] ?? '')); ?>;
    (function(){
      var btn=document.getElementById('leadBtn'); if(!btn) return;
      btn.addEventListener('click',function(){
        var nom=document.getElementById('lead_nom').value.trim();
        var contact=document.getElementById('lead_contact').value.trim();
        var sel=document.querySelector('input[name=lead_type]:checked');
        var type=sel?sel.value:'rappel';
        var msg=document.getElementById('leadMsg');
        if(!nom||!contact){ msg.style.color='#fb7185'; msg.textContent='Indiquez votre nom et votre contact.'; return; }
        btn.disabled=true; var old=btn.textContent; btn.textContent='Envoi…';
        var fd=new FormData();
        fd.append('csrf',LEAD_CSRF); fd.append('nom',nom); fd.append('contact',contact);
        fd.append('type',type); fd.append('formation_id',LEAD_FID); fd.append('formation_titre',LEAD_FTITRE);
        fetch('/lead.php',{method:'POST',body:fd})
          .then(function(r){return r.json();})
          .then(function(d){
            btn.disabled=false; btn.textContent=old;
            if(d&&d.ok){ msg.style.color='#34d399'; msg.textContent=d.message||'Merci !';
              document.getElementById('lead_nom').value=''; document.getElementById('lead_contact').value='';
              if(d.download){ window.open(d.download,'_blank'); } }
            else { msg.style.color='#fb7185'; msg.textContent=(d&&d.error)||'Erreur, réessayez.'; }
          })
          .catch(function(){ btn.disabled=false; btn.textContent=old; msg.style.color='#fb7185'; msg.textContent='Connexion impossible, réessayez.'; });
      });
    })();
  </script>

</div>
