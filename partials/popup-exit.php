<?php
/* =========================================================
   POPUP EXIT-INTENT — Préinscription rapide
   Inclure AVANT </body> dans formations.php, calendrier.php, etc.
   Nécessite : core/csrf.php chargé en amont
========================================================= */
?>
<div id="exit-popup-overlay" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="exit-popup-title">
  <div id="exit-popup">

    <button id="exit-popup-close" aria-label="Fermer">&times;</button>

    <div class="ep-icon">🎓</div>
    <h2 id="exit-popup-title">Vous partez sans vous inscrire&nbsp;?</h2>
    <p class="ep-sub">Réservez votre place en 30 secondes — un conseiller vous recontacte sous 24h.</p>

    <form id="exit-popup-form" novalidate>
      <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">

      <div class="ep-row">
        <div class="ep-field">
          <label for="ep-nom">Nom complet *</label>
          <input type="text" id="ep-nom" name="nom" required placeholder="Ex : Kouassi Jean" autocomplete="name">
        </div>
        <div class="ep-field">
          <label for="ep-email">Email *</label>
          <input type="email" id="ep-email" name="email" required placeholder="votre@email.com" autocomplete="email">
        </div>
      </div>

      <div class="ep-field">
        <label for="ep-telephone">Téléphone / WhatsApp</label>
        <input type="tel" id="ep-telephone" name="telephone" placeholder="+225 07 00 00 00 00" autocomplete="tel">
      </div>

      <div class="ep-field">
        <label for="ep-formation">Formation souhaitée *</label>
        <select id="ep-formation" name="formation_label" required>
          <option value="">— Choisissez ou tapez ci-dessous —</option>
          <?php
          /* Populate from DB if $pdo available in calling scope */
          if (!empty($pdo)):
            try {
              $stmt = $pdo->query("SELECT id, titre, domaine FROM formations WHERE statut='active' ORDER BY domaine, titre LIMIT 300");
              $fRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
              $curDomaine = '';
              foreach ($fRows as $fRow):
                if ($fRow['domaine'] !== $curDomaine):
                  if ($curDomaine !== '') echo '</optgroup>';
                  echo '<optgroup label="' . htmlspecialchars($fRow['domaine'], ENT_QUOTES, 'UTF-8') . '">';
                  $curDomaine = $fRow['domaine'];
                endif;
                echo '<option value="' . htmlspecialchars($fRow['titre'], ENT_QUOTES, 'UTF-8') . '" data-id="' . (int)$fRow['id'] . '">'
                   . htmlspecialchars($fRow['titre'], ENT_QUOTES, 'UTF-8') . '</option>';
              endforeach;
              if ($curDomaine !== '') echo '</optgroup>';
            } catch (Exception $e) { /* silencieux */ }
          endif;
          ?>
        </select>
        <input type="text" id="ep-formation-autre" name="formation_autre" placeholder="Ou précisez librement…" style="margin-top:8px">
      </div>

      <div id="ep-msg" role="alert" aria-live="polite"></div>

      <button type="submit" id="ep-submit">
        <span id="ep-submit-txt">Réserver ma place →</span>
        <span id="ep-submit-loader" hidden>⏳ Envoi…</span>
      </button>

      <p class="ep-rgpd">Vos données sont utilisées uniquement pour vous recontacter. Aucun spam.</p>
    </form>

    <div id="exit-popup-success" hidden>
      <div class="ep-success-icon">✅</div>
      <h3>Demande envoyée !</h3>
      <p>Un conseiller IBIG EDUFORM vous contactera sous <strong>24h</strong>.<br>Merci de votre intérêt.</p>
      <button id="ep-success-close" class="ep-btn-close-success">Fermer</button>
    </div>

  </div>
</div>

<style>
#exit-popup-overlay{
  display:none;
  position:fixed;inset:0;
  background:rgba(10,20,40,.72);
  z-index:99999;
  align-items:center;
  justify-content:center;
  padding:16px;
  backdrop-filter:blur(4px);
}
#exit-popup-overlay.visible{display:flex;}

#exit-popup{
  background:#fff;
  border-radius:24px;
  padding:36px 40px 32px;
  max-width:560px;
  width:100%;
  position:relative;
  box-shadow:0 32px 80px rgba(10,20,40,.28);
  animation:epSlideIn .3s cubic-bezier(.22,1,.36,1);
}
@keyframes epSlideIn{
  from{opacity:0;transform:translateY(-28px) scale(.97);}
  to{opacity:1;transform:none;}
}

#exit-popup-close{
  position:absolute;top:16px;right:20px;
  background:none;border:none;font-size:1.6rem;
  color:#94a3b8;cursor:pointer;line-height:1;
  transition:color .15s;
}
#exit-popup-close:hover{color:#0f172a;}

.ep-icon{font-size:2.4rem;margin-bottom:10px;display:block;text-align:center;}

#exit-popup h2{
  text-align:center;
  font-size:1.35rem;
  color:#0b3c5d;
  margin:0 0 8px;
  font-weight:800;
  line-height:1.3;
}
.ep-sub{
  text-align:center;
  color:#64748b;
  font-size:.88rem;
  margin:0 0 24px;
}

.ep-row{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
.ep-field{display:flex;flex-direction:column;margin-bottom:14px;}
.ep-field label{font-size:.78rem;font-weight:700;color:#334155;margin-bottom:5px;}
.ep-field input,
.ep-field select{
  border:1.5px solid #e2e8f0;
  border-radius:10px;
  padding:10px 13px;
  font-size:.88rem;
  color:#0f172a;
  outline:none;
  transition:border-color .15s;
  background:#f8fafc;
}
.ep-field input:focus,
.ep-field select:focus{border-color:#2563eb;background:#fff;}

#ep-msg{
  font-size:.82rem;
  border-radius:8px;
  padding:0;
  margin-bottom:0;
  min-height:0;
  transition:all .2s;
}
#ep-msg.error{color:#dc2626;background:#fef2f2;padding:10px 14px;margin-bottom:12px;}
#ep-msg.ok{color:#15803d;background:#f0fdf4;padding:10px 14px;margin-bottom:12px;}

#ep-submit{
  width:100%;
  background:linear-gradient(135deg,#2563eb,#1e40af);
  color:#fff;
  border:none;
  border-radius:14px;
  padding:14px;
  font-size:.95rem;
  font-weight:800;
  cursor:pointer;
  box-shadow:0 10px 28px rgba(37,99,235,.35);
  transition:transform .15s,box-shadow .15s;
  margin-top:4px;
}
#ep-submit:hover{transform:translateY(-2px);box-shadow:0 16px 40px rgba(37,99,235,.45);}
#ep-submit:disabled{opacity:.65;cursor:not-allowed;transform:none;}

.ep-rgpd{
  text-align:center;
  font-size:.72rem;
  color:#94a3b8;
  margin:10px 0 0;
}

/* SUCCESS */
#exit-popup-success{text-align:center;padding:16px 0;}
.ep-success-icon{font-size:3rem;margin-bottom:12px;}
#exit-popup-success h3{color:#0b3c5d;font-size:1.3rem;margin:0 0 10px;}
#exit-popup-success p{color:#475569;font-size:.9rem;line-height:1.6;}
.ep-btn-close-success{
  margin-top:20px;
  background:#2563eb;color:#fff;
  border:none;border-radius:12px;
  padding:12px 32px;font-size:.9rem;font-weight:700;
  cursor:pointer;
}

/* MOBILE */
@media(max-width:540px){
  #exit-popup{padding:28px 20px 24px;}
  .ep-row{grid-template-columns:1fr;}
  #exit-popup h2{font-size:1.15rem;}
}
</style>

<script>
(function(){
  const STORAGE_KEY = 'ep_shown';
  const overlay     = document.getElementById('exit-popup-overlay');
  const closeBtn    = document.getElementById('exit-popup-close');
  const form        = document.getElementById('exit-popup-form');
  const msgEl       = document.getElementById('ep-msg');
  const submitBtn   = document.getElementById('ep-submit');
  const submitTxt   = document.getElementById('ep-submit-txt');
  const submitLoader= document.getElementById('ep-submit-loader');
  const successBox  = document.getElementById('exit-popup-success');
  const successClose= document.getElementById('ep-success-close');

  /* ---- Ne montrer qu'une fois par session ---- */
  if (sessionStorage.getItem(STORAGE_KEY)) return;

  /* ---- Exit intent : souris vers le haut ---- */
  let triggered = false;
  document.addEventListener('mouseleave', function(e){
    if (triggered) return;
    if (e.clientY > 12) return; /* uniquement sortie vers barre d'onglets */
    triggered = true;
    showPopup();
  });

  /* ---- Délai fallback mobile : 45s sans interaction ---- */
  let mobileTimer = null;
  function resetTimer(){
    clearTimeout(mobileTimer);
    mobileTimer = setTimeout(function(){
      if(!triggered){ triggered=true; showPopup(); }
    }, 45000);
  }
  if ('ontouchstart' in window) {
    document.addEventListener('touchstart', resetTimer, {passive:true});
    resetTimer();
  }

  function showPopup(){
    sessionStorage.setItem(STORAGE_KEY, '1');
    overlay.classList.add('visible');
    overlay.setAttribute('aria-hidden','false');
    document.getElementById('ep-nom').focus();
  }

  function hidePopup(){
    overlay.classList.remove('visible');
    overlay.setAttribute('aria-hidden','true');
  }

  closeBtn.addEventListener('click', hidePopup);
  overlay.addEventListener('click', function(e){ if(e.target===overlay) hidePopup(); });
  document.addEventListener('keydown', function(e){ if(e.key==='Escape') hidePopup(); });
  successClose.addEventListener('click', hidePopup);

  /* ---- Pré-remplir avec la formation active si dispo ---- */
  const urlParams = new URLSearchParams(window.location.search);
  const fid = urlParams.get('formation_id');
  if (fid) {
    const opt = document.querySelector('#ep-formation option[data-id="'+fid+'"]');
    if (opt) opt.selected = true;
  }

  /* ---- Submit AJAX ---- */
  form.addEventListener('submit', async function(e){
    e.preventDefault();
    msgEl.className = '';
    msgEl.textContent = '';

    const nom   = document.getElementById('ep-nom').value.trim();
    const email = document.getElementById('ep-email').value.trim();
    const formation = document.getElementById('ep-formation').value
                   || document.getElementById('ep-formation-autre').value.trim();

    if (!nom)       { showErr('Veuillez indiquer votre nom.'); return; }
    if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email))
                    { showErr('Email invalide.'); return; }
    if (!formation) { showErr('Veuillez choisir une formation.'); return; }

    submitBtn.disabled = true;
    submitTxt.hidden   = true;
    submitLoader.hidden= false;

    const body = new FormData(form);
    /* formation_label peut être le select OU le champ libre */
    if (!body.get('formation_label') && document.getElementById('ep-formation-autre').value.trim()){
      body.set('formation_label', document.getElementById('ep-formation-autre').value.trim());
    }

    try {
      const res  = await fetch('ajax-popup-preinscription.php', {method:'POST', body});
      const data = await res.json();
      if (data.ok) {
        form.hidden         = true;
        successBox.hidden   = false;
      } else {
        showErr(data.message || 'Erreur. Veuillez réessayer.');
        submitBtn.disabled  = false;
        submitTxt.hidden    = false;
        submitLoader.hidden = true;
      }
    } catch(err) {
      showErr('Connexion impossible. Réessayez dans un instant.');
      submitBtn.disabled  = false;
      submitTxt.hidden    = false;
      submitLoader.hidden = true;
    }
  });

  function showErr(msg){
    msgEl.className = 'error';
    msgEl.textContent = msg;
  }
})();
</script>
