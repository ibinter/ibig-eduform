<?php /* Widget convertisseur de devises — IBIG EDUFORM
   Flottant bas-droite. Inclure dans footer.php avant </body>. */ ?>
<style>
#ibig-cw-btn{
  position:fixed;bottom:90px;right:14px;z-index:9998;
  background:#1f3fe0;color:#fff;border:none;border-radius:50%;
  padding:0;width:40px;height:40px;font-size:11px;font-weight:700;cursor:pointer;
  box-shadow:0 3px 12px rgba(31,63,224,.35);display:flex;align-items:center;justify-content:center;
  transition:transform .15s,box-shadow .15s;
}
#ibig-cw-btn:hover{transform:translateY(-2px);box-shadow:0 5px 16px rgba(31,63,224,.45)}
#ibig-cw-btn .ibig-cw-flag{font-size:18px}
#ibig-cw-btn-label{display:none}
@media(max-width:600px){
  #ibig-cw-btn{bottom:80px;right:10px;width:36px;height:36px;}
}

#ibig-cw-panel{
  position:fixed;bottom:150px;right:18px;z-index:9999;
  width:320px;background:#fff;border-radius:18px;
  box-shadow:0 12px 48px rgba(10,23,51,.18);
  font-family:Inter,system-ui,sans-serif;overflow:hidden;
  display:none;flex-direction:column;
  animation:ibig-cw-in .2s ease;
}
@keyframes ibig-cw-in{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:none}}
#ibig-cw-head{
  background:linear-gradient(135deg,#1f3fe0,#3b5bdb);
  color:#fff;padding:14px 16px 12px;display:flex;align-items:center;gap:8px;
}
#ibig-cw-head h4{margin:0;font-size:14.5px;font-weight:800;flex:1}
#ibig-cw-close{background:rgba(255,255,255,.2);border:none;color:#fff;
  width:26px;height:26px;border-radius:50%;cursor:pointer;font-size:14px;
  display:flex;align-items:center;justify-content:center}
#ibig-cw-close:hover{background:rgba(255,255,255,.35)}

.ibig-cw-body{padding:14px 16px}
.ibig-cw-label{font-size:11px;font-weight:700;color:#6b7a99;text-transform:uppercase;
  letter-spacing:.06em;margin:0 0 5px}
#ibig-cw-select{width:100%;padding:9px 12px;border:1.5px solid #e0e7f3;border-radius:10px;
  font-size:13.5px;font-weight:600;background:#f7f9ff;color:#0a1733;cursor:pointer;
  appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath fill='%231f3fe0' d='M1 1l5 5 5-5'/%3E%3C/svg%3E");
  background-repeat:no-repeat;background-position:right 12px center;padding-right:32px}
#ibig-cw-select:focus{outline:none;border-color:#1f3fe0}

.ibig-cw-rate-box{background:#f0f4ff;border-radius:10px;padding:10px 12px;margin:10px 0 0;
  font-size:12.5px;color:#3b4f7c;line-height:1.5;display:flex;justify-content:space-between;
  align-items:center}
.ibig-cw-rate-box .rate-val{font-weight:800;color:#1f3fe0;font-size:13.5px}

.ibig-cw-sep{border:none;border-top:1px solid #eef1f8;margin:12px 0}

.ibig-cw-sim-label{font-size:11px;font-weight:700;color:#6b7a99;text-transform:uppercase;
  letter-spacing:.06em;margin-bottom:6px}
.ibig-cw-sim-row{display:flex;gap:8px;align-items:center}
#ibig-cw-amount{flex:1;padding:9px 11px;border:1.5px solid #e0e7f3;border-radius:10px;
  font-size:14px;font-weight:700;color:#0a1733;background:#fff;text-align:right}
#ibig-cw-amount:focus{outline:none;border-color:#1f3fe0}
.ibig-cw-sim-arrow{color:#1f3fe0;font-size:18px;flex:none}
.ibig-cw-result-box{background:#e8f0fe;border-radius:10px;padding:10px 12px;margin-top:10px;
  text-align:center;font-size:15px;font-weight:800;color:#1f3fe0;min-height:40px;
  display:flex;align-items:center;justify-content:center}

.ibig-cw-toggle-row{display:flex;align-items:center;gap:9px;margin-top:12px;
  padding:9px 12px;background:#f7f9ff;border-radius:10px;cursor:pointer}
.ibig-cw-toggle-row label{font-size:12.5px;font-weight:600;color:#3b4f7c;cursor:pointer;flex:1}
.ibig-cw-switch{position:relative;width:38px;height:21px;flex:none}
.ibig-cw-switch input{opacity:0;width:0;height:0}
.ibig-cw-slider{position:absolute;inset:0;background:#cbd5e1;border-radius:21px;
  transition:.3s;cursor:pointer}
.ibig-cw-slider:before{content:'';position:absolute;height:15px;width:15px;
  left:3px;bottom:3px;background:#fff;border-radius:50%;transition:.3s}
.ibig-cw-switch input:checked+.ibig-cw-slider{background:#1f3fe0}
.ibig-cw-switch input:checked+.ibig-cw-slider:before{transform:translateX(17px)}

.ibig-cw-note{font-size:10.5px;color:#94a3b8;text-align:center;margin-top:10px;padding-bottom:2px}

/* badge prix converti injecté dans la page */
.ibig-converted-badge{
  display:inline-block;background:#e8f0fe;color:#1f3fe0;
  font-size:.78em;font-weight:700;border-radius:5px;
  padding:1px 6px;margin-left:5px;vertical-align:middle;white-space:nowrap;
}
@media(max-width:380px){#ibig-cw-panel{width:calc(100vw - 24px);right:12px}}
</style>

<!-- Bouton flottant -->
<button id="ibig-cw-btn" aria-label="Convertisseur de devises">
  <span class="ibig-cw-flag">💱</span> <span id="ibig-cw-btn-label">Devises</span>
</button>

<!-- Panel -->
<div id="ibig-cw-panel" role="dialog" aria-label="Convertisseur de devises">
  <div id="ibig-cw-head">
    <span style="font-size:20px">💱</span>
    <h4>Convertisseur de devises</h4>
    <button id="ibig-cw-close" aria-label="Fermer">✕</button>
  </div>
  <div class="ibig-cw-body">
    <p class="ibig-cw-label">Sélectionner une devise</p>
    <select id="ibig-cw-select">
      <option value="XOF">🌍 Franc CFA UEMOA — XOF</option>
      <option value="XAF">🌍 Franc CFA CEMAC — XAF</option>
      <option value="EUR">🇪🇺 Euro — EUR</option>
      <option value="USD">🇺🇸 Dollar américain — USD</option>
      <option value="GBP">🇬🇧 Livre sterling — GBP</option>
      <option value="CAD">🇨🇦 Dollar canadien — CAD</option>
      <option value="CHF">🇨🇭 Franc suisse — CHF</option>
      <option value="GNF">🇬🇳 Franc guinéen — GNF</option>
      <option value="MAD">🇲🇦 Dirham marocain — MAD</option>
      <option value="TND">🇹🇳 Dinar tunisien — TND</option>
      <option value="DZD">🇩🇿 Dinar algérien — DZD</option>
      <option value="MRU">🇲🇷 Ouguiya mauritanien — MRU</option>
      <option value="HTG">🇭🇹 Gourde haïtienne — HTG</option>
      <option value="CDF">🇨🇩 Franc congolais — CDF</option>
      <option value="BIF">🇧🇮 Franc burundais — BIF</option>
      <option value="RWF">🇷🇼 Franc rwandais — RWF</option>
      <option value="KMF">🇰🇲 Franc comorien — KMF</option>
      <option value="DJF">🇩🇯 Franc djiboutien — DJF</option>
      <option value="MGA">🇲🇬 Ariary malgache — MGA</option>
    </select>

    <div class="ibig-cw-rate-box">
      <span id="ibig-cw-rate-label">Taux de change</span>
      <span class="rate-val" id="ibig-cw-rate-val">—</span>
    </div>

    <hr class="ibig-cw-sep">

    <p class="ibig-cw-sim-label">Simuler un montant</p>
    <div class="ibig-cw-sim-row">
      <input type="number" id="ibig-cw-amount" placeholder="Ex : 200 000" min="0" step="1000">
      <span class="ibig-cw-sim-arrow">→</span>
    </div>
    <div class="ibig-cw-result-box" id="ibig-cw-result">Entrez un montant</div>

    <div class="ibig-cw-toggle-row" id="ibig-cw-toggle-row">
      <label for="ibig-cw-convert-chk">Afficher les équivalents sur la page</label>
      <label class="ibig-cw-switch">
        <input type="checkbox" id="ibig-cw-convert-chk">
        <span class="ibig-cw-slider"></span>
      </label>
    </div>

    <p class="ibig-cw-note">Taux indicatifs · mis à jour quotidiennement · source : open.er-api.com</p>
  </div>
</div>

<script>
(function(){
'use strict';

/* ── Devises ── */
var CURRENCIES = {
  XOF:{sym:'FCFA',name:'F CFA UEMOA',flag:'🌍'},
  XAF:{sym:'FCFA',name:'F CFA CEMAC',flag:'🌍'},
  EUR:{sym:'€',name:'Euro',flag:'🇪🇺'},
  USD:{sym:'$',name:'Dollar US',flag:'🇺🇸'},
  GBP:{sym:'£',name:'Livre sterling',flag:'🇬🇧'},
  CAD:{sym:'CA$',name:'Dollar canadien',flag:'🇨🇦'},
  CHF:{sym:'CHF',name:'Franc suisse',flag:'🇨🇭'},
  GNF:{sym:'GNF',name:'Franc guinéen',flag:'🇬🇳'},
  MAD:{sym:'DH',name:'Dirham marocain',flag:'🇲🇦'},
  TND:{sym:'DT',name:'Dinar tunisien',flag:'🇹🇳'},
  DZD:{sym:'DA',name:'Dinar algérien',flag:'🇩🇿'},
  MRU:{sym:'UM',name:'Ouguiya',flag:'🇲🇷'},
  HTG:{sym:'G',name:'Gourde haïtienne',flag:'🇭🇹'},
  CDF:{sym:'FC',name:'Franc congolais',flag:'🇨🇩'},
  BIF:{sym:'BIF',name:'Franc burundais',flag:'🇧🇮'},
  RWF:{sym:'RWF',name:'Franc rwandais',flag:'🇷🇼'},
  KMF:{sym:'KMF',name:'Franc comorien',flag:'🇰🇲'},
  DJF:{sym:'DJF',name:'Franc djiboutien',flag:'🇩🇯'},
  MGA:{sym:'Ar',name:'Ariary malgache',flag:'🇲🇬'}
};

/* Taux fixes XOF→ (fallback si API indisponible) */
var FALLBACK = {
  XOF:1,XAF:1,EUR:0.001524,USD:0.001613,GBP:0.001278,CAD:0.002206,
  CHF:0.001449,GNF:13.8,MAD:0.01602,TND:0.005020,DZD:0.2188,
  MRU:0.06380,HTG:0.2107,CDF:4.527,BIF:4.753,RWF:2.176,
  KMF:0.7497,DJF:0.2868,MGA:7.375
};

var rates = null;
var currentCurrency = 'XOF';
var convertPage = false;
var CACHE_KEY = 'ibig_fx_v2';
var CACHE_TTL = 6 * 3600 * 1000; // 6h

/* ── Éléments ── */
var btn       = document.getElementById('ibig-cw-btn');
var panel     = document.getElementById('ibig-cw-panel');
var closeBtn  = document.getElementById('ibig-cw-close');
var sel       = document.getElementById('ibig-cw-select');
var rateLabel = document.getElementById('ibig-cw-rate-label');
var rateVal   = document.getElementById('ibig-cw-rate-val');
var amtInput  = document.getElementById('ibig-cw-amount');
var result    = document.getElementById('ibig-cw-result');
var convertChk= document.getElementById('ibig-cw-convert-chk');
var btnLabel  = document.getElementById('ibig-cw-btn-label');

/* ── UI toggle ── */
btn.addEventListener('click', function(){
  var open = panel.style.display === 'flex';
  panel.style.display = open ? 'none' : 'flex';
});
closeBtn.addEventListener('click', function(){ panel.style.display = 'none'; });
document.addEventListener('click', function(e){
  if (!panel.contains(e.target) && e.target !== btn && !btn.contains(e.target)) {
    panel.style.display = 'none';
  }
});

/* ── Charger taux ── */
function loadRates() {
  try {
    var cached = JSON.parse(localStorage.getItem(CACHE_KEY) || 'null');
    if (cached && cached.ts && (Date.now() - cached.ts) < CACHE_TTL && cached.rates) {
      rates = cached.rates; updateUI(); return;
    }
  } catch(e){}
  fetch('https://open.er-api.com/v6/latest/XOF')
    .then(function(r){ return r.json(); })
    .then(function(d){
      if (d && d.rates) {
        rates = d.rates;
        // XAF = XOF (parité fixe)
        rates.XOF = 1;
        rates.XAF = 1;
        try { localStorage.setItem(CACHE_KEY, JSON.stringify({ts:Date.now(),rates:rates})); } catch(e){}
      } else { rates = FALLBACK; }
      updateUI();
    })
    .catch(function(){ rates = FALLBACK; updateUI(); });
}

/* ── Convertir un montant FCFA → devise cible ── */
function convert(fcfa, code) {
  if (!rates || !rates[code]) return null;
  return fcfa * rates[code];
}

/* ── Formater selon la devise ── */
function formatAmount(val, code) {
  var c = CURRENCIES[code] || {sym: code};
  var decimals = (val >= 100) ? 0 : (val >= 1 ? 1 : 2);
  var formatted;
  try {
    formatted = new Intl.NumberFormat('fr-FR', {maximumFractionDigits: decimals}).format(val);
  } catch(e) {
    formatted = Math.round(val).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
  }
  // Symbole avant pour €, $, £ ; après pour les autres
  var prefix = ['€','$','£','CA$','CHF'].indexOf(c.sym) >= 0;
  return prefix ? (c.sym + ' ' + formatted) : (formatted + ' ' + c.sym);
}

/* ── Mise à jour affichage ── */
function updateUI() {
  if (!rates) return;
  var code = currentCurrency;
  var c = CURRENCIES[code] || {sym:code, name:code, flag:''};
  var rate = rates[code] || FALLBACK[code] || 1;

  // Taux affiché
  if (code === 'XOF' || code === 'XAF') {
    rateLabel.textContent = '1 FCFA XOF =';
    rateVal.textContent = '1 ' + c.sym;
  } else {
    var inv = rate > 0 ? (1/rate) : 0;
    var invFmt;
    try { invFmt = new Intl.NumberFormat('fr-FR',{maximumFractionDigits:0}).format(Math.round(inv)); } catch(e){ invFmt = Math.round(inv); }
    rateLabel.textContent = '1 ' + c.sym + ' =';
    rateVal.textContent = invFmt + ' FCFA';
  }

  // Simulateur
  updateSim();

  // Bouton flottant
  btnLabel.textContent = c.flag + ' ' + code;

  // Conversion page
  if (convertPage) applyPageConversion();
}

function updateSim() {
  var raw = parseFloat(amtInput.value);
  if (!raw || raw <= 0 || !rates) { result.textContent = 'Entrez un montant'; return; }
  var code = currentCurrency;
  var val = convert(raw, code);
  if (val === null) { result.textContent = '—'; return; }
  if (code === 'XOF' || code === 'XAF') {
    result.textContent = formatAmount(raw, 'XOF');
  } else {
    result.innerHTML = formatAmount(val, code) +
      '<small style="display:block;font-size:11px;color:#3b5bdb;margin-top:3px">≈ ' +
      new Intl.NumberFormat('fr-FR').format(Math.round(raw)) + ' FCFA</small>';
  }
}

sel.addEventListener('change', function(){
  currentCurrency = sel.value;
  try { localStorage.setItem('ibig_fx_cur', currentCurrency); } catch(e){}
  updateUI();
  if (convertPage) { removePageConversion(); applyPageConversion(); }
});
amtInput.addEventListener('input', updateSim);
convertChk.addEventListener('change', function(){
  convertPage = convertChk.checked;
  if (convertPage) applyPageConversion(); else removePageConversion();
});

/* ── Conversion des prix dans la page ── */
// Regex : capture un montant FCFA dans les text nodes
// Formats : "200 000 FCFA", "200 000 F CFA", "200 000 F"
var PRICE_RE = /(\d{1,3}(?:[\s ]\d{3})+)\s*(?:F(?:\s*CFA)?|FCFA)/g;

function parseAmount(str) {
  return parseInt(str.replace(/[\s ]/g,''), 10);
}

function walkTextNodes(node, callback) {
  if (node.nodeType === 3) { callback(node); return; }
  if (node.nodeType !== 1) return;
  var tag = node.tagName ? node.tagName.toUpperCase() : '';
  if (['SCRIPT','STYLE','NOSCRIPT','TEXTAREA','INPUT'].indexOf(tag) >= 0) return;
  if (node.id && node.id.indexOf('ibig-cw') === 0) return;
  for (var i = 0; i < node.childNodes.length; i++) walkTextNodes(node.childNodes[i], callback);
}

function applyPageConversion() {
  if (!rates || currentCurrency === 'XOF' || currentCurrency === 'XAF') {
    removePageConversion(); return;
  }
  // Supprimer badges précédents
  removePageConversion();

  var code = currentCurrency;
  var targets = [];
  walkTextNodes(document.body, function(node){
    var txt = node.textContent || '';
    if (!PRICE_RE.test(txt)) return;
    PRICE_RE.lastIndex = 0;
    targets.push(node);
  });

  targets.forEach(function(node){
    PRICE_RE.lastIndex = 0;
    var txt = node.textContent;
    var frag = document.createDocumentFragment();
    var last = 0, m;
    var found = false;
    while ((m = PRICE_RE.exec(txt)) !== null) {
      found = true;
      var amtStr = m[1];
      var fcfa = parseAmount(amtStr);
      if (fcfa < 5000) continue; // ignorer les petits nombres
      var converted = convert(fcfa, code);
      if (converted === null) continue;

      // texte avant le match
      if (m.index > last) frag.appendChild(document.createTextNode(txt.slice(last, m.index)));
      // texte original
      frag.appendChild(document.createTextNode(m[0]));
      // badge converti
      var badge = document.createElement('span');
      badge.className = 'ibig-converted-badge';
      badge.setAttribute('data-ibig-badge', '1');
      badge.textContent = '≈ ' + formatAmount(converted, code);
      frag.appendChild(badge);
      last = m.index + m[0].length;
    }
    if (found && last > 0) {
      if (last < txt.length) frag.appendChild(document.createTextNode(txt.slice(last)));
      node.parentNode.replaceChild(frag, node);
    }
  });
}

function removePageConversion() {
  var badges = document.querySelectorAll('[data-ibig-badge]');
  badges.forEach(function(b){ b.parentNode && b.parentNode.removeChild(b); });
}

/* ── Init ── */
try {
  var saved = localStorage.getItem('ibig_fx_cur');
  if (saved && CURRENCIES[saved]) {
    currentCurrency = saved;
    sel.value = saved;
  }
} catch(e){}

loadRates();

})();
</script>
