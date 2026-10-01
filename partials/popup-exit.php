<?php
/* =========================================================
   POPUP EXIT-INTENT — Préinscription rapide
   Inclus via footer.php — recherche AJAX via ajax-search-formations.php
========================================================= */
<div id="exit-popup-overlay" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="exit-popup-title">
  <div id="exit-popup">

    <button id="exit-popup-close" aria-label="Fermer">&times;</button>

    <div class="ep-icon">🎓</div>
    <h2 id="exit-popup-title">Vous partez sans vous inscrire&nbsp;?</h2>
    <p class="ep-sub">Réservez votre place en 30 secondes — un conseiller vous recontacte sous 24h.</p>

    <form id="exit-popup-form" novalidate>
      <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
      <input type="hidden" id="ep-formation-id"    name="formation_id"    value="">
      <input type="hidden" id="ep-formation-label" name="formation_label" value="">

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

      <!-- RECHERCHE FORMATION -->
      <div class="ep-field ep-search-wrap">
        <label for="ep-search">Formation souhaitée *</label>
        <div class="ep-search-box">
          <span class="ep-search-icon">🔍</span>
          <input type="text" id="ep-search" placeholder="Tapez pour rechercher…" autocomplete="off" aria-autocomplete="list" aria-controls="ep-results">
          <button type="button" id="ep-search-clear" aria-label="Effacer" hidden>&times;</button>
        </div>
        <ul id="ep-results" role="listbox" aria-label="Formations disponibles"></ul>
        <p id="ep-selected-label" class="ep-selected-label" hidden></p>
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
  display:none;position:fixed;inset:0;
  background:rgba(10,20,40,.72);z-index:99999;
  align-items:center;justify-content:center;padding:16px;
  backdrop-filter:blur(4px);
}
#exit-popup-overlay.visible{display:flex;}
#exit-popup{
  background:#fff;border-radius:24px;padding:32px 36px 28px;
  max-width:540px;width:100%;position:relative;
  box-shadow:0 32px 80px rgba(10,20,40,.28);
  animation:epIn .28s cubic-bezier(.22,1,.36,1);
  max-height:92vh;overflow-y:auto;overflow-x:visible;
}
@keyframes epIn{from{opacity:0;transform:translateY(-24px) scale(.97)}to{opacity:1;transform:none}}
#exit-popup-close{
  position:absolute;top:14px;right:18px;
  background:none;border:none;font-size:1.6rem;color:#94a3b8;cursor:pointer;
}
#exit-popup-close:hover{color:#0f172a;}
.ep-icon{font-size:2.2rem;text-align:center;display:block;margin-bottom:8px;}
#exit-popup h2{text-align:center;font-size:1.25rem;color:#0b3c5d;margin:0 0 6px;font-weight:800;}
.ep-sub{text-align:center;color:#64748b;font-size:.85rem;margin:0 0 20px;}
.ep-row{display:grid;grid-template-columns:1fr 1fr;gap:10px;}
.ep-field{display:flex;flex-direction:column;margin-bottom:12px;}
.ep-field label{font-size:.75rem;font-weight:700;color:#334155;margin-bottom:4px;}
.ep-field input[type=text],
.ep-field input[type=email],
.ep-field input[type=tel]{
  border:1.5px solid #e2e8f0;border-radius:10px;
  padding:9px 12px;font-size:.88rem;color:#0f172a;
  outline:none;background:#f8fafc;transition:border-color .15s;
}
.ep-field input:focus{border-color:#2563eb;background:#fff;}

/* SEARCH BOX */
.ep-search-wrap{position:relative;}
.ep-search-box{position:relative;display:flex;align-items:center;}
.ep-search-icon{position:absolute;left:11px;font-size:.85rem;pointer-events:none;}
#ep-search{
  width:100%;border:1.5px solid #e2e8f0;border-radius:10px;
  padding:9px 36px 9px 32px;font-size:.88rem;color:#0f172a;
  outline:none;background:#f8fafc;transition:border-color .15s;
}
#ep-search:focus{border-color:#2563eb;background:#fff;}
#ep-search-clear{
  position:absolute;right:10px;background:none;border:none;
  font-size:1.1rem;color:#94a3b8;cursor:pointer;padding:0;line-height:1;
}
#ep-search-clear:hover{color:#ef4444;}

#ep-results{
  list-style:none;margin:4px 0 0;padding:0;
  border:1.5px solid #e2e8f0;border-radius:12px;
  background:#f8fafc;max-height:190px;overflow-y:auto;
  display:none;
}
#ep-results.open{display:block;}
#ep-results li{
  padding:9px 14px;font-size:.84rem;color:#334155;cursor:pointer;
  border-bottom:1px solid #f1f5f9;line-height:1.3;
}
#ep-results li:last-child{border-bottom:none;}
#ep-results li:hover,#ep-results li.active{background:#eff6ff;color:#1d4ed8;}
#ep-results li .ep-domaine{font-size:.7rem;color:#94a3b8;display:block;}
#ep-results li.ep-no-result{color:#94a3b8;cursor:default;font-style:italic;}
#ep-results li.ep-no-result:hover{background:none;}

.ep-selected-label{
  margin:6px 0 0;font-size:.8rem;font-weight:700;
  color:#15803d;background:#f0fdf4;border-radius:8px;
  padding:6px 10px;display:flex;align-items:center;gap:6px;
}
.ep-selected-label::before{content:'✓ Sélectionné : ';}

/* MSG / SUBMIT */
#ep-msg{font-size:.82rem;border-radius:8px;min-height:0;transition:all .2s;}
#ep-msg.error{color:#dc2626;background:#fef2f2;padding:10px 14px;margin-bottom:10px;}
#ep-submit{
  width:100%;background:linear-gradient(135deg,#2563eb,#1e40af);
  color:#fff;border:none;border-radius:14px;padding:13px;
  font-size:.93rem;font-weight:800;cursor:pointer;
  box-shadow:0 10px 28px rgba(37,99,235,.35);
  transition:transform .15s,box-shadow .15s;margin-top:4px;
}
#ep-submit:hover{transform:translateY(-2px);box-shadow:0 16px 40px rgba(37,99,235,.45);}
#ep-submit:disabled{opacity:.6;cursor:not-allowed;transform:none;}
.ep-rgpd{text-align:center;font-size:.7rem;color:#94a3b8;margin:8px 0 0;}

/* SUCCESS */
#exit-popup-success{text-align:center;padding:10px 0;}
.ep-success-icon{font-size:3rem;margin-bottom:10px;}
#exit-popup-success h3{color:#0b3c5d;font-size:1.2rem;margin:0 0 8px;}
#exit-popup-success p{color:#475569;font-size:.88rem;line-height:1.6;}
.ep-btn-close-success{
  margin-top:18px;background:#2563eb;color:#fff;border:none;
  border-radius:12px;padding:11px 28px;font-size:.9rem;font-weight:700;cursor:pointer;
}
@media(max-width:520px){
  #exit-popup{padding:24px 18px 20px;}
  .ep-row{grid-template-columns:1fr;}
}
</style>

<script>
(function(){
  var STORAGE_KEY = 'ep_shown_v2';
  var overlay   = document.getElementById('exit-popup-overlay');
  var closeBtn  = document.getElementById('exit-popup-close');
  var form      = document.getElementById('exit-popup-form');
  var msgEl     = document.getElementById('ep-msg');
  var submitBtn = document.getElementById('ep-submit');
  var submitTxt = document.getElementById('ep-submit-txt');
  var submitLdr = document.getElementById('ep-submit-loader');
  var successBox= document.getElementById('exit-popup-success');
  var successCls= document.getElementById('ep-success-close');

  var searchInp = document.getElementById('ep-search');
  var clearBtn  = document.getElementById('ep-search-clear');
  var resultsList = document.getElementById('ep-results');
  var selectedLabel = document.getElementById('ep-selected-label');
  var hiddenId  = document.getElementById('ep-formation-id');
  var hiddenLbl = document.getElementById('ep-formation-label');

  if (!overlay) return;

  /* ---- Ne montrer qu'une fois par session ---- */
  var alreadySeen = false;
  try { alreadySeen = sessionStorage.getItem(STORAGE_KEY) === '1'; } catch(e){}
  if (alreadySeen) return;

  /* ---- Trigger exit-intent ---- */
  var triggered = false;
  document.addEventListener('mouseleave', function(e){
    if (triggered || e.clientY > 10) return;
    triggered = true; showPopup();
  });
  /* Mobile fallback 45s */
  if ('ontouchstart' in window) {
    setTimeout(function(){ if (!triggered){ triggered=true; showPopup(); } }, 45000);
  }

  function showPopup(){
    try { sessionStorage.setItem(STORAGE_KEY,'1'); } catch(e){}
    overlay.classList.add('visible');
    overlay.setAttribute('aria-hidden','false');
    setTimeout(function(){ searchInp.focus(); }, 200);
  }
  function hidePopup(){
    overlay.classList.remove('visible');
    overlay.setAttribute('aria-hidden','true');
  }

  closeBtn.addEventListener('click', hidePopup);
  overlay.addEventListener('click', function(e){ if(e.target===overlay) hidePopup(); });
  document.addEventListener('keydown', function(e){ if(e.key==='Escape') hidePopup(); });
  successCls.addEventListener('click', hidePopup);

  /* ---- Pré-sélection via URL formation_id ---- */
  var urlFid = new URLSearchParams(location.search).get('formation_id');
  if (urlFid) {
    fetch('/ajax-search-formations.php?q=' + encodeURIComponent(urlFid))
      .then(function(r){ return r.json(); })
      .then(function(rows){
        var found = rows.find(function(f){ return f.id == urlFid; });
        if (found) selectFormation(found);
      }).catch(function(){});
  }

  /* ---- RECHERCHE AJAX ---- */
  var activeIdx = -1;
  var debounceTimer = null;

  searchInp.addEventListener('input', function(){
    var q = this.value.trim();
    clearBtn.hidden = !q;
    clearTimeout(debounceTimer);
    if (q.length < 2) { closeList(); return; }
    debounceTimer = setTimeout(function(){ doSearch(q); }, 220);
  });

  function doSearch(q){
    fetch('/ajax-search-formations.php?q=' + encodeURIComponent(q))
      .then(function(r){ return r.json(); })
      .then(function(rows){ renderList(rows, q); })
      .catch(function(){ renderList([], q); });
  }

  searchInp.addEventListener('keydown', function(e){
    var items = resultsList.querySelectorAll('li:not(.ep-no-result)');
    if (!items.length) return;
    if (e.key === 'ArrowDown'){
      e.preventDefault();
      activeIdx = Math.min(activeIdx+1, items.length-1);
      highlightItem(items);
    } else if (e.key === 'ArrowUp'){
      e.preventDefault();
      activeIdx = Math.max(activeIdx-1, 0);
      highlightItem(items);
    } else if (e.key === 'Enter'){
      e.preventDefault();
      if (activeIdx >= 0 && items[activeIdx]) items[activeIdx].click();
    } else if (e.key === 'Escape'){
      closeList();
    }
  });

  function highlightItem(items){
    items.forEach(function(it,i){ it.classList.toggle('active', i===activeIdx); });
    if (items[activeIdx]) items[activeIdx].scrollIntoView({block:'nearest'});
  }

  clearBtn.addEventListener('click', function(){
    searchInp.value = '';
    clearBtn.hidden = true;
    hiddenId.value = '';
    hiddenLbl.value = '';
    selectedLabel.hidden = true;
    selectedLabel.textContent = '';
    closeList();
    searchInp.focus();
  });

  document.addEventListener('click', function(e){
    if (!e.target.closest('.ep-search-wrap')) closeList();
  });

  function renderList(matches, q){
    resultsList.innerHTML = '';
    activeIdx = -1;
    if (!matches.length){
      var li = document.createElement('li');
      li.className = 'ep-no-result';
      li.textContent = 'Aucune formation trouvée pour "'+q+'"';
      resultsList.appendChild(li);
    } else {
      matches.forEach(function(f){
        var li = document.createElement('li');
        li.setAttribute('role','option');
        li.setAttribute('data-id', f.id);
        li.setAttribute('data-titre', f.titre);
        /* Highlight matching text */
        var hl = f.titre.replace(new RegExp('('+escapeReg(q)+')','gi'),'<mark>$1</mark>');
        li.innerHTML = hl + '<span class="ep-domaine">'+escapeHtml(f.domaine)+'</span>';
        li.addEventListener('click', function(){ selectFormation(f); });
        resultsList.appendChild(li);
      });
    }
    resultsList.classList.add('open');
  }

  function selectFormation(f){
    hiddenId.value  = f.id;
    hiddenLbl.value = f.titre;
    searchInp.value = f.titre;
    selectedLabel.textContent = f.titre;
    selectedLabel.hidden = false;
    clearBtn.hidden = false;
    closeList();
  }

  function closeList(){
    resultsList.classList.remove('open');
    resultsList.innerHTML = '';
    activeIdx = -1;
  }

  function escapeReg(s){ return s.replace(/[.*+?^${}()|[\]\\]/g,'\\$&'); }
  function escapeHtml(s){ var d=document.createElement('div'); d.textContent=s; return d.innerHTML; }

  /* ---- Submit ---- */
  form.addEventListener('submit', async function(e){
    e.preventDefault();
    msgEl.className = '';
    msgEl.textContent = '';

    var nom   = document.getElementById('ep-nom').value.trim();
    var email = document.getElementById('ep-email').value.trim();
    var label = hiddenLbl.value.trim() || searchInp.value.trim();

    if (!nom)   { showErr('Veuillez indiquer votre nom.'); return; }
    if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email))
                { showErr('Email invalide.'); return; }
    if (!label) { showErr('Veuillez sélectionner une formation dans la liste.'); return; }

    submitBtn.disabled = true;
    submitTxt.hidden   = true;
    submitLdr.hidden   = false;

    var body = new FormData(form);

    try {
      var res  = await fetch('/ajax-popup-preinscription.php', {method:'POST', body});
      var data = await res.json();
      if (data.ok){
        form.hidden      = true;
        successBox.hidden = false;
      } else {
        showErr(data.message || 'Erreur. Réessayez.');
        submitBtn.disabled = false; submitTxt.hidden = false; submitLdr.hidden = true;
      }
    } catch(err){
      showErr('Connexion impossible. Réessayez dans un instant.');
      submitBtn.disabled = false; submitTxt.hidden = false; submitLdr.hidden = true;
    }
  });

  function showErr(msg){ msgEl.className='error'; msgEl.textContent=msg; }
})();
</script>
