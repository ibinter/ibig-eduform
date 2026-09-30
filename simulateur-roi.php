<?php
require_once __DIR__ . '/core/bootstrap.php';
$pageTitle    = "Simulateur ROI Formation – IBIG EDUFORM";
$ogDesc       = "Calculez en temps réel le retour sur investissement de votre formation : gain salarial, délai de rentabilité, projection 5 ans.";
$pageKeywords = "simulateur ROI formation, retour investissement formation, gain salarial Afrique, rentabilité formation IBIG EDUFORM";
include __DIR__ . '/partials/header.php';
?>
<style>
:root{
  --blue:#1a3fd0;--red:#e8242c;--navy:#08122b;--gold:#f5a623;
  --bg:#05091a;--card:#0d1630;--border:rgba(255,255,255,.10);
  --green:#10b981;
}
body{background:var(--bg);color:#e5e7eb;font-family:Inter,system-ui,sans-serif;min-height:100vh}

/* ── HERO ── */
.roi-hero{text-align:center;padding:60px 24px 36px;background:radial-gradient(ellipse at 50% 0%,rgba(16,185,129,.18),transparent 60%)}
.roi-badge{display:inline-flex;align-items:center;gap:8px;background:rgba(16,185,129,.12);border:1px solid rgba(16,185,129,.35);color:#6ee7b7;font-size:.8rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;padding:6px 16px;border-radius:99px;margin-bottom:18px}
.roi-hero h1{font-size:clamp(1.9rem,4vw,2.9rem);font-weight:900;margin:0 0 14px;background:linear-gradient(135deg,#fff 40%,#10b981);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.roi-hero p{color:#94a3b8;max-width:600px;margin:0 auto 28px;font-size:1.05rem;line-height:1.7}
.roi-hero-stats{display:flex;justify-content:center;gap:36px;flex-wrap:wrap}
.roi-hs strong{display:block;font-size:1.6rem;font-weight:900;color:#10b981}
.roi-hs span{font-size:.75rem;color:#64748b;text-transform:uppercase;letter-spacing:.06em}

/* ── LAYOUT ── */
.roi-wrap{max-width:1100px;margin:0 auto;padding:32px 24px 100px;display:grid;grid-template-columns:400px 1fr;gap:28px;align-items:start}
@media(max-width:880px){.roi-wrap{grid-template-columns:1fr}}
.roi-form-col{position:sticky;top:88px}
@media(max-width:880px){.roi-form-col{position:static}}

/* ── CARD ── */
.roi-card{background:var(--card);border:1px solid var(--border);border-radius:22px;padding:26px}
.roi-card+.roi-card{margin-top:14px}
.roi-card-title{font-size:1rem;font-weight:800;color:#fff;margin:0 0 18px;display:flex;align-items:center;gap:9px}

/* ── FIELD ── */
.roi-field{margin-bottom:16px}
.roi-field label{display:block;font-size:.77rem;font-weight:700;color:#64748b;letter-spacing:.06em;text-transform:uppercase;margin-bottom:7px}
.roi-field select{
  width:100%;background:rgba(255,255,255,.06);border:1.5px solid var(--border);
  border-radius:12px;padding:12px 36px 12px 14px;color:#fff;font-size:.95rem;font-family:inherit;
  outline:none;transition:.2s;box-sizing:border-box;cursor:pointer;
  appearance:none;-webkit-appearance:none;
  background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='7'%3E%3Cpath fill='%2364748b' d='M0 0l6 7 6-7z'/%3E%3C/svg%3E");
  background-repeat:no-repeat;background-position:right 13px center;
}
.roi-field select:focus{border-color:var(--green);background-color:rgba(16,185,129,.07)}
.roi-field select option{background:#0d1630;color:#fff}

/* ── SLIDER ── */
.roi-slider{
  width:100%;-webkit-appearance:none;appearance:none;height:6px;border-radius:99px;
  background:linear-gradient(90deg,var(--green) var(--pct,8%),rgba(255,255,255,.1) var(--pct,8%));
  outline:none;cursor:pointer;display:block;margin-bottom:10px;
}
.roi-slider::-webkit-slider-thumb{-webkit-appearance:none;width:22px;height:22px;border-radius:50%;background:linear-gradient(135deg,#10b981,#6ee7b7);box-shadow:0 0 0 4px rgba(16,185,129,.25);cursor:pointer}
.roi-salary-display{text-align:center;font-size:1.85rem;font-weight:900;color:#fff}
.roi-salary-display small{font-size:.8rem;color:#64748b;font-weight:400;margin-left:4px}
.slider-marks{display:flex;justify-content:space-between;margin-top:4px;padding:0 2px}
.slider-marks span{font-size:.68rem;color:#475569;text-align:center;line-height:1.3}

/* ── MODE BTNS ── */
.roi-mode-btns{display:grid;grid-template-columns:1fr 1fr;gap:8px}
.roi-mode-btn{cursor:pointer;background:rgba(255,255,255,.04);border:1.5px solid var(--border);border-radius:12px;padding:12px;text-align:center;transition:.2s;user-select:none}
.roi-mode-btn:hover{border-color:rgba(16,185,129,.45);background:rgba(16,185,129,.06)}
.roi-mode-btn.active{border-color:var(--green);background:rgba(16,185,129,.13)}
.roi-mode-btn strong{display:block;font-size:.92rem;font-weight:800;color:#fff;margin-bottom:3px}
.roi-mode-btn span{font-size:.75rem;color:#64748b}
.roi-mode-btn.active span{color:#6ee7b7}

/* ── LIVE BADGE ── */
.live-badge{display:inline-flex;align-items:center;gap:5px;background:rgba(16,185,129,.12);border:1px solid rgba(16,185,129,.25);color:#6ee7b7;font-size:.7rem;font-weight:700;padding:3px 9px;border-radius:99px;margin-left:auto}
.live-dot{width:5px;height:5px;border-radius:50%;background:#10b981;animation:pls 1.4s infinite}
@keyframes pls{0%,100%{opacity:1}50%{opacity:.4}}

/* ── EMPTY STATE ── */
.roi-empty{display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:280px;text-align:center;padding:32px;color:#475569}
.roi-empty-ico{font-size:3.2rem;margin-bottom:14px;opacity:.3}
.roi-empty p{font-size:.9rem;max-width:240px;line-height:1.6;margin:0}

/* ── QUALITY BADGE ── */
.roi-quality{display:inline-flex;align-items:center;gap:8px;padding:8px 18px;border-radius:99px;font-weight:800;font-size:.88rem}
.roi-quality.excellent{background:rgba(16,185,129,.18);border:1.5px solid rgba(16,185,129,.4);color:#6ee7b7}
.roi-quality.bon{background:rgba(26,63,208,.18);border:1.5px solid rgba(26,63,208,.4);color:#93c5fd}
.roi-quality.correct{background:rgba(245,166,35,.15);border:1.5px solid rgba(245,166,35,.4);color:#fcd34d}

/* ── SCORE BAND ── */
.roi-score-band{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:20px}
.roi-score-item{background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:14px;padding:14px 10px;text-align:center}
.roi-score-val{font-size:1.5rem;font-weight:900;margin-bottom:3px}
.roi-score-val.green{color:#10b981}
.roi-score-val.gold{color:#f5a623}
.roi-score-val.blue{color:#93c5fd}
.roi-score-lbl{font-size:.68rem;color:#64748b;text-transform:uppercase;letter-spacing:.05em}

/* ── CHART ── */
#roi-chart-svg{width:100%;height:auto;display:block;overflow:visible}
.chart-caption{font-size:.77rem;color:#475569;text-align:center;margin:7px 0 0}

/* ── CMP TABLE ── */
.roi-cmp-table{width:100%;border-collapse:collapse;font-size:.85rem}
.roi-cmp-table th{padding:9px 10px;text-align:center;color:#64748b;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;border-bottom:1px solid var(--border)}
.roi-cmp-table th:first-child{text-align:left}
.roi-cmp-table td{padding:10px;text-align:center;border-bottom:1px solid rgba(255,255,255,.04);color:#cbd5e1}
.roi-cmp-table td:first-child{text-align:left;color:#64748b;font-size:.82rem}
.roi-cmp-table tr:last-child td{border-bottom:none}
.roi-cmp-table .hl{color:#10b981;font-weight:800}
.th-ol{color:#6ee7b7 !important}
.th-pr{color:#93c5fd !important}

/* ── MARKET ── */
.mkt-row{display:flex;justify-content:space-between;font-size:.78rem;color:#64748b;margin-bottom:6px}
.mkt-track{height:8px;background:rgba(255,255,255,.06);border-radius:99px;position:relative;margin-bottom:8px}
.mkt-fill-user{height:100%;border-radius:99px;background:linear-gradient(90deg,#1a3fd0,#3b5bff);position:absolute;top:0;left:0;transition:width .7s ease}
.mkt-median-line{position:absolute;top:-3px;height:14px;width:2.5px;background:#f5a623;border-radius:2px;transform:translateX(-50%);transition:left .7s ease}
.mkt-legend{display:flex;gap:14px;flex-wrap:wrap;margin-bottom:8px}
.mkt-leg{display:flex;align-items:center;gap:5px;font-size:.74rem;color:#64748b}
.mkt-dot{width:9px;height:9px;border-radius:50%}
.mkt-note{font-size:.8rem;color:#94a3b8;line-height:1.5}
.mkt-note strong{color:#6ee7b7}

/* ── DEBOUCHES ── */
.roi-deb{display:flex;align-items:center;gap:11px;background:rgba(255,255,255,.03);border:1px solid var(--border);border-radius:11px;padding:11px 13px;margin-bottom:8px}
.roi-deb:last-child{margin-bottom:0}
.roi-deb-ico{font-size:1.25rem;flex-shrink:0}
.roi-deb-name{font-weight:700;font-size:.88rem;color:#fff;margin-bottom:2px}
.roi-deb-sal{font-size:.75rem;color:#10b981;font-weight:600}

/* ── MOTIVATION ── */
.roi-motiv{padding:18px;border-radius:14px;background:linear-gradient(135deg,rgba(16,185,129,.1),rgba(26,63,208,.1));border:1px solid rgba(16,185,129,.2);text-align:center;margin-bottom:14px}
.roi-motiv p{margin:0;color:#e2e8f0;font-size:.9rem;line-height:1.7}
.roi-motiv strong{color:#6ee7b7}

/* ── CTA ── */
.roi-cta{display:grid;grid-template-columns:1fr 1fr 1fr;gap:9px}
@media(max-width:480px){.roi-cta{grid-template-columns:1fr 1fr}}
.roi-cta a,.roi-cta button{padding:12px 8px;border-radius:11px;font-weight:800;font-size:.85rem;text-decoration:none;text-align:center;transition:.2s;cursor:pointer;border:0;font-family:inherit;display:flex;align-items:center;justify-content:center;gap:5px}
.c-prim{background:linear-gradient(135deg,#e8242c,#ff5763);color:#fff}
.c-sec{background:rgba(255,255,255,.07);color:#cbd5e1;border:1px solid var(--border) !important}
.c-copy{background:rgba(16,185,129,.14);color:#6ee7b7;border:1px solid rgba(16,185,129,.28) !important}
.roi-cta a:hover,.roi-cta button:hover{transform:translateY(-2px)}

/* ── SECTION TITLE ── */
.sec-ttl{font-size:.74rem;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.08em;margin:0 0 11px;display:flex;align-items:center;gap:8px}
.sec-ttl::after{content:"";flex:1;height:1px;background:var(--border)}
</style>

<main style="padding-top:20px">

<section class="roi-hero">
  <div class="roi-badge">📈 Outil exclusif IBIG EDUFORM</div>
  <h1>Simulateur ROI Formation</h1>
  <p>Renseignez votre profil et voyez <strong style="-webkit-text-fill-color:#6ee7b7;color:#6ee7b7">en temps réel</strong> combien votre formation va vous rapporter.</p>
  <div class="roi-hero-stats">
    <div class="roi-hs"><strong>+28%</strong><span>Gain moyen constaté</span></div>
    <div class="roi-hs"><strong>4,2 mois</strong><span>ROI moyen</span></div>
    <div class="roi-hs"><strong>⚡ Live</strong><span>Mise à jour en direct</span></div>
  </div>
</section>

<div class="roi-wrap">

  <!-- FORMULAIRE -->
  <div class="roi-form-col">
    <div class="roi-card">
      <div class="roi-card-title">
        ⚙️ Votre profil
        <div class="live-badge"><div class="live-dot"></div>Live</div>
      </div>

      <div class="roi-field">
        <label>Domaine de formation</label>
        <select id="roi-domain" onchange="calculate()">
          <option value="">— Choisissez un domaine —</option>
          <option value="Finance & Comptabilité">Finance &amp; Comptabilité</option>
          <option value="Ressources Humaines">Ressources Humaines</option>
          <option value="Management & Leadership">Management &amp; Leadership</option>
          <option value="Logistique & Supply Chain">Logistique &amp; Supply Chain</option>
          <option value="QHSE & Sécurité">QHSE &amp; Sécurité</option>
          <option value="Numérique & IA">Numérique &amp; IA</option>
          <option value="Commerce & Marketing">Commerce &amp; Marketing</option>
          <option value="Droit & Juridique">Droit &amp; Juridique</option>
        </select>
      </div>

      <div class="roi-field">
        <label>Votre niveau actuel</label>
        <select id="roi-niveau" onchange="calculate()">
          <option value="debutant">Débutant — 0 à 2 ans</option>
          <option value="intermediaire" selected>Intermédiaire — 2 à 5 ans</option>
          <option value="confirme">Confirmé — 5 à 10 ans</option>
          <option value="expert">Expert — 10 ans et plus</option>
        </select>
      </div>

      <div class="roi-field">
        <label>Salaire mensuel actuel (FCFA)</label>
        <input type="range" class="roi-slider" id="roi-salary"
          min="150000" max="3000000" step="50000" value="400000"
          oninput="updateSalary(this.value);calculate()">
        <div class="roi-salary-display" id="salaryDisplay">400 000 <small>FCFA/mois</small></div>
        <div class="slider-marks">
          <span>Débutant<br>150k</span>
          <span>Cadre Jr<br>400k</span>
          <span>Cadre<br>800k</span>
          <span>Senior<br>1,5M</span>
          <span>Directeur<br>3M</span>
        </div>
      </div>

      <div class="roi-field" style="margin-bottom:0">
        <label>Mode de formation</label>
        <div class="roi-mode-btns">
          <div class="roi-mode-btn active" id="mode-online" onclick="selectMode('online')">
            <strong>💻 En ligne</strong><span>200 000 FCFA</span>
          </div>
          <div class="roi-mode-btn" id="mode-presential" onclick="selectMode('presential')">
            <strong>🏫 Présentiel</strong><span>250 000 FCFA</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- RÉSULTATS -->
  <div>
    <div class="roi-card" id="roi-empty-card">
      <div class="roi-empty">
        <div class="roi-empty-ico">📊</div>
        <p>Choisissez un domaine pour voir votre projection s'afficher ici en temps réel.</p>
      </div>
    </div>

    <div id="roi-results" style="display:none">

      <!-- KPIs -->
      <div class="roi-card" style="margin-bottom:14px">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:16px;flex-wrap:wrap">
          <div class="roi-card-title" style="margin:0">🎯 Votre projection</div>
          <div class="roi-quality" id="roi-quality">⭐ Excellent</div>
        </div>
        <div class="roi-score-band">
          <div class="roi-score-item">
            <div class="roi-score-val green" id="res-pct">—</div>
            <div class="roi-score-lbl">Gain salarial</div>
          </div>
          <div class="roi-score-item">
            <div class="roi-score-val gold" id="res-roi">—</div>
            <div class="roi-score-lbl">Mois pour ROI</div>
          </div>
          <div class="roi-score-item">
            <div class="roi-score-val blue" id="res-5y">—</div>
            <div class="roi-score-lbl">Gain net 5 ans</div>
          </div>
        </div>
      </div>

      <!-- GRAPHIQUE -->
      <div class="roi-card" style="margin-bottom:14px">
        <div class="sec-ttl">📈 Projection cumulative sur 60 mois</div>
        <svg id="roi-chart-svg" viewBox="0 0 520 190" xmlns="http://www.w3.org/2000/svg"></svg>
        <p class="chart-caption" id="chart-cap"></p>
      </div>

      <!-- COMPARAISON -->
      <div class="roi-card" style="margin-bottom:14px">
        <div class="sec-ttl">⚖️ En ligne vs Présentiel</div>
        <table class="roi-cmp-table">
          <thead>
            <tr>
              <th></th>
              <th class="th-ol">💻 En ligne</th>
              <th class="th-pr">🏫 Présentiel</th>
            </tr>
          </thead>
          <tbody>
            <tr><td>Coût</td><td id="c-cost-ol">—</td><td id="c-cost-pr">—</td></tr>
            <tr><td>Gain/mois</td><td id="c-gain-ol">—</td><td id="c-gain-pr">—</td></tr>
            <tr><td>ROI</td><td id="c-roi-ol">—</td><td id="c-roi-pr">—</td></tr>
            <tr><td>Gain net 5 ans</td><td id="c-5y-ol">—</td><td id="c-5y-pr">—</td></tr>
          </tbody>
        </table>
      </div>

      <!-- PROFIL VS MARCHÉ -->
      <div class="roi-card" style="margin-bottom:14px">
        <div class="sec-ttl">🏢 Votre salaire vs. marché</div>
        <div class="mkt-row">
          <span id="mkt-u-lbl">Vous</span>
          <span id="mkt-m-lbl">Médiane secteur</span>
        </div>
        <div class="mkt-track">
          <div class="mkt-fill-user" id="mkt-user" style="width:0%"></div>
          <div class="mkt-median-line" id="mkt-median" style="left:50%"></div>
        </div>
        <div class="mkt-legend">
          <div class="mkt-leg"><div class="mkt-dot" style="background:#3b5bff"></div>Votre salaire</div>
          <div class="mkt-leg"><div class="mkt-dot" style="background:#f5a623"></div>Médiane du secteur</div>
        </div>
        <div class="mkt-note" id="mkt-note"></div>
      </div>

      <!-- DÉBOUCHÉS -->
      <div class="roi-card" style="margin-bottom:14px">
        <div class="sec-ttl">💼 Débouchés métiers</div>
        <div id="roi-debouches"></div>
      </div>

      <!-- MOTIVATION + CTA -->
      <div class="roi-card">
        <div class="roi-motiv"><p id="roi-motiv-text">—</p></div>
        <div class="roi-cta">
          <a href="/preinscription-generale.php" class="c-prim">✍️ Me préinscrire</a>
          <a href="/catalogue-formations.php" class="c-sec">📚 Catalogue</a>
          <button class="c-copy" onclick="copyResults()">📋 Copier</button>
        </div>
      </div>

    </div>
  </div>
</div>
</main>

<script>
const DATA = {
  "Finance & Comptabilité":{
    gains:{debutant:28,intermediaire:22,confirme:16,expert:12},median:550000,
    debouches:[{ico:"💼",n:"Responsable Comptable",s:"350 000 – 600 000 FCFA/mois"},{ico:"📊",n:"Contrôleur de Gestion",s:"400 000 – 750 000 FCFA/mois"},{ico:"🔍",n:"Auditeur Interne / Externe",s:"450 000 – 900 000 FCFA/mois"}],
    motiv:"La maîtrise de la finance est le passeport n°1 vers la direction générale en Afrique francophone."
  },
  "Ressources Humaines":{
    gains:{debutant:24,intermediaire:18,confirme:14,expert:10},median:420000,
    debouches:[{ico:"👥",n:"Responsable RH",s:"300 000 – 550 000 FCFA/mois"},{ico:"📋",n:"Chargé de Recrutement",s:"250 000 – 450 000 FCFA/mois"},{ico:"🏢",n:"DRH / Directeur RH",s:"600 000 – 1 500 000 FCFA/mois"}],
    motiv:"Les professionnels RH certifiés sont parmi les plus recherchés dans l'espace OHADA, notamment dans les multinationales."
  },
  "Management & Leadership":{
    gains:{debutant:32,intermediaire:24,confirme:18,expert:12},median:600000,
    debouches:[{ico:"🏆",n:"Chef de Projet / Manager",s:"400 000 – 700 000 FCFA/mois"},{ico:"🎯",n:"Directeur de Division",s:"600 000 – 1 200 000 FCFA/mois"},{ico:"🚀",n:"Directeur Général / CEO",s:"1 000 000 – 3 000 000 FCFA/mois"}],
    motiv:"Les compétences managériales font gagner en moyenne 2 niveaux hiérarchiques en 3 ans selon nos anciens apprenants."
  },
  "Logistique & Supply Chain":{
    gains:{debutant:26,intermediaire:20,confirme:15,expert:11},median:480000,
    debouches:[{ico:"🚚",n:"Responsable Logistique",s:"300 000 – 600 000 FCFA/mois"},{ico:"📦",n:"Supply Chain Manager",s:"450 000 – 850 000 FCFA/mois"},{ico:"🌍",n:"Directeur Achats & Logistique",s:"700 000 – 1 400 000 FCFA/mois"}],
    motiv:"Le commerce intra-africain explose : les experts supply chain sont en pénurie dans tous les pays OHADA."
  },
  "QHSE & Sécurité":{
    gains:{debutant:28,intermediaire:20,confirme:14,expert:10},median:450000,
    debouches:[{ico:"🛡️",n:"Responsable QHSE",s:"300 000 – 550 000 FCFA/mois"},{ico:"✅",n:"Auditeur Qualité ISO",s:"350 000 – 650 000 FCFA/mois"},{ico:"🏭",n:"Directeur QHSE",s:"600 000 – 1 100 000 FCFA/mois"}],
    motiv:"Les certifications ISO sont désormais exigées par les donneurs d'ordre publics et les bailleurs internationaux."
  },
  "Numérique & IA":{
    gains:{debutant:42,intermediaire:32,confirme:22,expert:15},median:580000,
    debouches:[{ico:"💻",n:"Data / Business Analyst",s:"350 000 – 700 000 FCFA/mois"},{ico:"🤖",n:"Resp. Transformation Digitale",s:"500 000 – 1 000 000 FCFA/mois"},{ico:"🧠",n:"Chief Digital Officer (CDO)",s:"900 000 – 2 500 000 FCFA/mois"}],
    motiv:"L'IA est le domaine avec la plus forte croissance salariale en Afrique (+40% en moyenne). Les compétences numériques valent de l'or."
  },
  "Commerce & Marketing":{
    gains:{debutant:30,intermediaire:22,confirme:16,expert:11},median:480000,
    debouches:[{ico:"📈",n:"Responsable Commercial",s:"300 000 – 600 000 FCFA/mois"},{ico:"🎯",n:"Directeur Marketing",s:"450 000 – 900 000 FCFA/mois"},{ico:"🌐",n:"Directeur Commercial",s:"600 000 – 1 500 000 FCFA/mois"}],
    motiv:"Un commercial certifié génère en moyenne 8× son salaire en chiffre d'affaires."
  },
  "Droit & Juridique":{
    gains:{debutant:26,intermediaire:20,confirme:14,expert:10},median:520000,
    debouches:[{ico:"⚖️",n:"Juriste d'Entreprise",s:"350 000 – 650 000 FCFA/mois"},{ico:"📜",n:"Responsable Conformité",s:"400 000 – 750 000 FCFA/mois"},{ico:"🏛️",n:"Directeur Juridique",s:"700 000 – 1 600 000 FCFA/mois"}],
    motiv:"La conformité OHADA et la compliance sont en plein essor avec l'intégration économique africaine (ZLECAf)."
  }
};

const COST={online:200000,presential:250000};
let currentMode='online', lastResult=null;

/* ── FORMAT ── */
function fmt(n){return Math.round(n).toLocaleString('fr-FR')}

/* ── SALARY SLIDER ── */
function updateSalary(v){
  const n=parseInt(v);
  document.getElementById('salaryDisplay').innerHTML=fmt(n)+' <small>FCFA/mois</small>';
  const pct=Math.round((n-150000)/(3000000-150000)*100);
  document.getElementById('roi-salary').style.setProperty('--pct',pct+'%');
}

/* ── MODE ── */
function selectMode(m){
  currentMode=m;
  document.getElementById('mode-online').classList.toggle('active',m==='online');
  document.getElementById('mode-presential').classList.toggle('active',m==='presential');
  calculate();
}

/* ── TOAST ── */
function toast(msg,type){
  let t=document.getElementById('_toast');
  if(!t){t=document.createElement('div');t.id='_toast';t.style.cssText='position:fixed;bottom:28px;left:50%;transform:translateX(-50%);padding:13px 26px;border-radius:12px;font-size:.92rem;font-weight:700;z-index:9999;box-shadow:0 8px 24px rgba(0,0,0,.4);transition:opacity .3s;pointer-events:none;white-space:nowrap';document.body.appendChild(t);}
  t.style.background=type==='ok'?'#059669':'#dc2626';t.style.color='#fff';
  t.textContent=msg;t.style.opacity='1';
  clearTimeout(t._h);t._h=setTimeout(()=>t.style.opacity='0',3200);
}

/* ── ANIMATED COUNTER ── */
function animCount(el,to,prefix,suffix,dur){
  const t0=performance.now();
  (function step(now){
    const p=Math.min((now-t0)/dur,1);
    const e=1-Math.pow(1-p,3);
    el.textContent=prefix+fmt(Math.round(to*e))+suffix;
    if(p<1)requestAnimationFrame(step);
  })(t0);
}

/* ── SVG CHART ── */
function buildChart(gainMonthly,cost){
  const W=520,H=190,PL=58,PR=18,PT=16,PB=30;
  const iW=W-PL-PR,iH=H-PT-PB,M=60;
  const maxV=gainMonthly*M-cost, minV=-cost, range=maxV-minV||1;
  const px=m=>PL+(m/M)*iW, py=v=>PT+(1-(v-minV)/range)*iH;
  const zY=py(0), beM=cost/gainMonthly, beX=px(Math.min(beM,M));

  // Full area path (for clip-based fills)
  let areaD='';
  for(let m=0;m<=M;m++){const v=gainMonthly*m-cost;areaD+=(m?'L':'M')+px(m).toFixed(1)+','+py(v).toFixed(1)+' ';}
  areaD+=`L${px(M).toFixed(1)},${zY.toFixed(1)} L${px(0).toFixed(1)},${zY.toFixed(1)} Z`;

  // Line path
  let lineD='';
  for(let m=0;m<=M;m++){const v=gainMonthly*m-cost;lineD+=(m?'L':'M')+px(m).toFixed(1)+','+py(v).toFixed(1)+' ';}

  // X ticks
  const xticks=[0,12,24,36,48,60];
  const xgrid=xticks.map(m=>`<line x1="${px(m).toFixed(1)}" y1="${PT}" x2="${px(m).toFixed(1)}" y2="${PT+iH}" stroke="rgba(255,255,255,.05)" stroke-width="1"/>`).join('');
  const xlbls=xticks.map(m=>`<text x="${px(m).toFixed(1)}" y="${H-5}" fill="#475569" font-size="9" font-family="Inter,sans-serif" text-anchor="middle">${m?m+'m':'J0'}</text>`).join('');

  // Y labels
  const ylbls=[
    `<text x="${PL-4}" y="${(PT+5).toFixed(1)}" fill="#10b981" font-size="9" font-family="Inter,sans-serif" text-anchor="end">+${Math.round(maxV/1000)}k</text>`,
    `<text x="${PL-4}" y="${(zY+3).toFixed(1)}" fill="#94a3b8" font-size="9" font-family="Inter,sans-serif" text-anchor="end">0</text>`,
    `<text x="${PL-4}" y="${(PT+iH).toFixed(1)}" fill="#ef4444" font-size="9" font-family="Inter,sans-serif" text-anchor="end">-${Math.round(-minV/1000)}k</text>`,
  ].join('');

  // Breakeven marker
  const beMark = beM<=M ? `
    <line x1="${beX.toFixed(1)}" y1="${PT}" x2="${beX.toFixed(1)}" y2="${(PT+iH).toFixed(1)}" stroke="#f5a623" stroke-width="1.5" stroke-dasharray="5,3"/>
    <circle cx="${beX.toFixed(1)}" cy="${zY.toFixed(1)}" r="5" fill="#f5a623" stroke="#05091a" stroke-width="2"/>
    <text x="${(beX+8).toFixed(1)}" y="${(zY-8).toFixed(1)}" fill="#f5a623" font-size="10" font-weight="700" font-family="Inter,sans-serif">Mois ${Math.ceil(beM)}</text>` : '';

  return `<defs>
    <linearGradient id="gNeg" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#ef4444" stop-opacity=".22"/><stop offset="100%" stop-color="#ef4444" stop-opacity=".03"/></linearGradient>
    <linearGradient id="gPos" x1="0" y1="1" x2="0" y2="0"><stop offset="0%" stop-color="#10b981" stop-opacity=".04"/><stop offset="100%" stop-color="#10b981" stop-opacity=".22"/></linearGradient>
    <clipPath id="clipNeg"><rect x="${PL}" y="${zY.toFixed(1)}" width="${iW}" height="${(PT+iH-zY).toFixed(1)}"/></clipPath>
    <clipPath id="clipPos"><rect x="${PL}" y="${PT}" width="${iW}" height="${(zY-PT).toFixed(1)}"/></clipPath>
  </defs>
  ${xgrid}
  <line x1="${PL}" y1="${zY.toFixed(1)}" x2="${(PL+iW).toFixed(1)}" y2="${zY.toFixed(1)}" stroke="rgba(255,255,255,.18)" stroke-width="1" stroke-dasharray="4,4"/>
  <path d="${areaD}" fill="url(#gNeg)" clip-path="url(#clipNeg)"/>
  <path d="${areaD}" fill="url(#gPos)" clip-path="url(#clipPos)"/>
  <path d="${lineD}" fill="none" stroke="#10b981" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round"/>
  ${beMark}
  ${xlbls}${ylbls}`;
}

/* ── CALCULATE ── */
function calculate(){
  const domain=document.getElementById('roi-domain').value;
  const niveau=document.getElementById('roi-niveau').value;
  const salary=parseInt(document.getElementById('roi-salary').value);
  if(!domain){
    document.getElementById('roi-empty-card').style.display='block';
    document.getElementById('roi-results').style.display='none';
    return;
  }
  document.getElementById('roi-empty-card').style.display='none';
  document.getElementById('roi-results').style.display='block';

  const d=DATA[domain];
  const gainPct=d.gains[niveau];
  const gainM=Math.round(salary*gainPct/100);
  const cost=COST[currentMode];
  const costOL=COST.online, costPR=COST.presential;
  const roiM=Math.ceil(cost/gainM);
  const gain5y=gainM*60-cost;
  const roiOL=Math.ceil(costOL/gainM), roiPR=Math.ceil(costPR/gainM);
  const g5OL=gainM*60-costOL, g5PR=gainM*60-costPR;

  lastResult={domain,niveau,salary,gainPct,gainM,cost,roiM,gain5y};

  /* KPIs */
  document.getElementById('res-pct').textContent='+'+gainPct+'%';
  document.getElementById('res-roi').textContent=roiM+' mois';
  animCount(document.getElementById('res-5y'),Math.round(gain5y/1000),'+','k',700);

  /* Quality badge */
  const qEl=document.getElementById('roi-quality');
  qEl.className='roi-quality';
  if(roiM<=5){qEl.classList.add('excellent');qEl.textContent='⭐ Excellent investissement';}
  else if(roiM<=10){qEl.classList.add('bon');qEl.textContent='👍 Bon investissement';}
  else{qEl.classList.add('correct');qEl.textContent='📈 Investissement rentable';}

  /* Chart */
  document.getElementById('roi-chart-svg').innerHTML=buildChart(gainM,cost);
  document.getElementById('chart-cap').textContent=
    `Investissement de ${fmt(cost)} FCFA récupéré au mois ${roiM} — chaque mois après rapporte +${fmt(gainM)} FCFA supplémentaires.`;

  /* Comparison table */
  const bOL=roiOL<=roiPR;
  document.getElementById('c-cost-ol').textContent=fmt(costOL)+' F';
  document.getElementById('c-cost-pr').textContent=fmt(costPR)+' F';
  document.getElementById('c-gain-ol').innerHTML=`<span class="hl">+${fmt(gainM)} F</span>`;
  document.getElementById('c-gain-pr').innerHTML=`<span class="hl">+${fmt(gainM)} F</span>`;
  document.getElementById('c-roi-ol').innerHTML=roiOL+' mois'+(bOL?' <span class="hl">✓</span>':'');
  document.getElementById('c-roi-pr').innerHTML=roiPR+' mois'+(!bOL?' <span class="hl">✓</span>':'');
  document.getElementById('c-5y-ol').innerHTML=`<span class="hl">+${fmt(Math.round(g5OL/1000))}k</span>`;
  document.getElementById('c-5y-pr').textContent='+'+fmt(Math.round(g5PR/1000))+'k';

  /* Market */
  const med=d.median;
  const maxM=Math.max(salary,med)*1.05;
  document.getElementById('mkt-user').style.width=Math.round(salary/maxM*100)+'%';
  document.getElementById('mkt-median').style.left=Math.round(med/maxM*100)+'%';
  document.getElementById('mkt-u-lbl').textContent='Vous : '+fmt(salary)+' F';
  document.getElementById('mkt-m-lbl').textContent='Médiane : '+fmt(med)+' F';
  const diff=Math.round((salary-med)/med*100);
  document.getElementById('mkt-note').innerHTML=diff>=0
    ?`Votre salaire est <strong>+${diff}% au-dessus</strong> de la médiane du secteur ${domain}.`
    :`Votre salaire est <strong>${Math.abs(diff)}% sous</strong> la médiane — forte marge de progression avec une formation.`;

  /* Débouchés */
  document.getElementById('roi-debouches').innerHTML=d.debouches.map(x=>`
    <div class="roi-deb"><div class="roi-deb-ico">${x.ico}</div><div>
      <div class="roi-deb-name">${x.n}</div>
      <div class="roi-deb-sal">${x.s}</div>
    </div></div>`).join('');

  /* Motivation */
  document.getElementById('roi-motiv-text').innerHTML=
    `En <strong>${domain}</strong>, une formation vous rapporte <strong style="color:#6ee7b7">+${fmt(gainM*12)} FCFA par an</strong>.
     Investissement de <strong>${fmt(cost)} FCFA</strong> récupéré en <strong style="color:#f5a623">${roiM} mois</strong>. ${d.motiv}`;
}

/* ── COPIER ── */
function copyResults(){
  if(!lastResult){toast('Calculez d\'abord votre ROI.','err');return;}
  const r=lastResult;
  const txt=`📊 Mon ROI Formation — IBIG EDUFORM
Domaine : ${r.domain}
Gain salarial : +${r.gainPct}%
Gain mensuel estimé : +${fmt(r.gainM)} FCFA
ROI atteint en : ${r.roiM} mois
Gain net sur 5 ans : +${fmt(Math.round(r.gain5y/1000))}k FCFA
Calculé sur : ${location.href}`;
  navigator.clipboard.writeText(txt)
    .then(()=>toast('✅ Résultats copiés !','ok'))
    .catch(()=>toast('Copie non disponible sur ce navigateur.','err'));
}

/* Init */
updateSalary(400000);
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>
