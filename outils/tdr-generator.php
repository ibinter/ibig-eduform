<?php /* IBIG EDUFORM — Générateur TDR (outils internes) */ ?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Générateur TDR — IBIG EDUFORM</title>
<style>
:root{--amber:#f59e0b;--navy:#0d1f3c;--bg:#f8fafc;--card:#fff;--border:#e2e8f0;--txt:#1e293b;--muted:#64748b;}
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Segoe UI',Arial,sans-serif;background:var(--bg);color:var(--txt);font-size:14px;}
h2{font-size:15px;font-weight:700;color:var(--navy);margin-bottom:12px;}
label{display:block;font-size:12px;font-weight:600;color:var(--muted);margin-bottom:4px;margin-top:10px;}
input,select,textarea{width:100%;padding:7px 10px;border:1px solid var(--border);border-radius:6px;font-size:13px;color:var(--txt);background:#fff;}
input:focus,select:focus,textarea:focus{outline:none;border-color:var(--amber);}
textarea{resize:vertical;min-height:70px;}
.layout{display:flex;gap:0;height:100vh;overflow:hidden;}
.sidebar{width:370px;min-width:300px;background:var(--card);border-right:1px solid var(--border);overflow-y:auto;padding:16px;flex-shrink:0;}
.preview{flex:1;overflow-y:auto;background:#e5e7eb;padding:20px;}
.sec-block{border:1px solid var(--border);border-radius:8px;padding:14px;margin-bottom:14px;background:#fff;}
.type-btns{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:4px;}
.type-btn{flex:1;min-width:80px;padding:8px 4px;border:2px solid var(--border);border-radius:8px;background:#fff;cursor:pointer;font-size:12px;font-weight:600;text-align:center;color:var(--muted);transition:.15s;}
.type-btn.active{border-color:var(--amber);background:#fffbeb;color:var(--navy);}
.actions{display:flex;gap:8px;margin-top:14px;}
.btn{flex:1;padding:10px;border:none;border-radius:8px;cursor:pointer;font-size:13px;font-weight:600;transition:.15s;}
.btn-primary{background:var(--navy);color:#fff;}
.btn-primary:hover{background:#1e3a5f;}
.btn-amber{background:var(--amber);color:#fff;}
.btn-amber:hover{background:#d97706;}
.btn-outline{background:#fff;color:var(--navy);border:1.5px solid var(--navy);}
.btn-ia{background:linear-gradient(135deg,#7c3aed,#4f46e5);color:#fff;}
.btn-ia:hover{background:linear-gradient(135deg,#6d28d9,#4338ca);}
.btn-ia:disabled{opacity:.6;cursor:not-allowed;}
.ia-block{border:2px solid #7c3aed;border-radius:8px;padding:14px;margin-bottom:14px;background:#faf5ff;}
.ia-block h2{color:#6d28d9;}
.hidden{display:none!important;}
/* ── Bloc catalogue ── */
.cat-block{border:2px solid #0d1f3c;border-radius:8px;padding:14px;margin-bottom:14px;background:#f0f4f8;}
.cat-block h2{color:#0d1f3c;margin-bottom:8px;}
.cat-search-wrap{position:relative;}
.cat-search-wrap input{padding-right:36px;}
.cat-clear{position:absolute;right:8px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#94a3b8;font-size:16px;line-height:1;display:none;}
.cat-dropdown{position:absolute;left:0;right:0;top:100%;z-index:200;background:#fff;border:1px solid #e2e8f0;border-radius:0 0 8px 8px;max-height:260px;overflow-y:auto;box-shadow:0 8px 24px rgba(0,0,0,.12);}
.cat-item{padding:10px 12px;cursor:pointer;border-bottom:1px solid #f1f5f9;display:flex;flex-direction:column;gap:2px;}
.cat-item:last-child{border-bottom:none;}
.cat-item:hover{background:#f8fafc;}
.cat-item .ci-name{font-size:13px;font-weight:600;color:#1e293b;}
.cat-item .ci-cat{font-size:11px;color:#94a3b8;}
.cat-item .ci-prix{font-size:11px;color:#f59e0b;font-weight:700;}
.cat-loaded{background:#d1fae5;border-radius:6px;padding:8px 10px;font-size:12px;color:#065f46;margin-top:8px;display:none;align-items:center;gap:8px;}
.cat-loaded a{color:#059669;font-size:11px;text-decoration:none;}
.cat-loaded a:hover{text-decoration:underline;}
.btn-cat-clear{background:none;border:1px solid #d1fae5;border-radius:4px;color:#065f46;font-size:11px;padding:2px 8px;cursor:pointer;margin-left:auto;}
/* ── Aperçu document ── */
#dw{display:flex;justify-content:center;align-items:flex-start;}
.wrap{width:210mm;min-height:297mm;background:#fff;box-shadow:0 4px 24px rgba(0,0,0,.12);padding:14mm 14mm 12mm;font-family:'Segoe UI',Arial,sans-serif;font-size:11pt;color:#1a1a2e;}
.hd{text-align:center;padding-bottom:10px;border-bottom:3px solid #f59e0b;margin-bottom:14px;}
.hd img{height:52px;margin-bottom:6px;}
.hd-title{font-size:15pt;font-weight:700;color:#0d1f3c;letter-spacing:.5px;}
.hd-inst{font-size:8.5pt;color:#475569;margin:2px 0 8px;}
.tdr-title{font-size:13pt;font-weight:700;color:#0d1f3c;background:#f1f5f9;display:inline-block;padding:4px 18px;border-radius:4px;margin:4px 0 2px;}
.tdr-sub{font-size:9.5pt;color:#64748b;margin-bottom:2px;}
.tdr-nom{font-size:12pt;font-weight:700;color:#b45309;margin-top:4px;}
.sec{margin-bottom:14px;}
.sec-hd{background:#0d1f3c;color:#fff;padding:6px 12px;font-size:10.5pt;font-weight:700;border-radius:4px 4px 0 0;margin-bottom:0;}
.sec-body{border:1px solid #cbd5e1;border-top:none;border-radius:0 0 4px 4px;padding:10px 12px;}
.sec-h2{font-size:10pt;font-weight:700;color:#0d1f3c;margin:8px 0 4px;border-bottom:1px solid #f1f5f9;padding-bottom:2px;}
.fiche{width:100%;border-collapse:collapse;font-size:10pt;}
.fiche td{padding:5px 8px;border:1px solid #e2e8f0;vertical-align:top;}
.fiche td:first-child{width:38%;background:#f8fafc;font-weight:600;color:#334155;}
.tbl{width:100%;border-collapse:collapse;font-size:9.5pt;margin:6px 0;}
.tbl th{background:#0d1f3c;color:#fff;padding:5px 8px;text-align:left;}
.tbl td{padding:5px 8px;border:1px solid #e2e8f0;}
.tbl tr:nth-child(even) td{background:#f8fafc;}
.prix-box{border:2px solid #f59e0b;border-radius:6px;padding:10px 14px;background:#fffbeb;margin-top:6px;}
.prix-box-title{font-weight:700;color:#92400e;font-size:10.5pt;margin-bottom:6px;}
.foot{text-align:center;font-size:8.5pt;color:#94a3b8;border-top:2px solid #f59e0b;margin-top:16px;padding-top:8px;}
.sig-row{display:flex;gap:20px;margin-top:14px;}
.sig-box{flex:1;border:1px solid #e2e8f0;border-radius:4px;padding:10px;text-align:center;font-size:9.5pt;}
.sig-box .sig-title{font-weight:700;color:#0d1f3c;margin-bottom:4px;}
.sig-box .sig-name{color:#475569;}
.sig-space{height:40px;}
</style>
</head>
<body>
<div class="layout">
<!-- ═══ SIDEBAR ═══ -->
<div class="sidebar">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
    <div style="text-align:center;flex:1;">
      <img src="../assets/images/logo.png" alt="IBIG" style="height:38px;">
      <div style="font-size:11px;color:var(--muted);margin-top:4px;">Générateur de TDR</div>
    </div>
    <button onclick="deconnexion()" title="Se déconnecter"
      style="flex-shrink:0;padding:6px 10px;background:#fee2e2;border:1px solid #fca5a5;border-radius:6px;
             color:#b91c1c;font-size:11px;font-weight:600;cursor:pointer;white-space:nowrap;margin-left:8px;">
      🔒 Déconnexion
    </button>
  </div>

  <!-- ══ Import depuis le catalogue ══ -->
  <div class="cat-block">
    <h2>📋 Importer du catalogue</h2>
    <p style="font-size:12px;color:#475569;margin-bottom:10px;">Tapez le nom d'une formation du catalogue — un clic importe les données réelles <strong>(titre, prix, durée)</strong> et génère automatiquement le TDR complet par IA.</p>
    <div class="cat-search-wrap" id="cat-wrap">
      <input id="cat-input" type="text" placeholder="Ex : Excel, Leadership, Marketing…" autocomplete="off"
             oninput="catSearch(this.value)" onfocus="if(this.value.length>=2)catSearch(this.value)">
      <button class="cat-clear" id="cat-clear-btn" onclick="catReset()" title="Effacer">✕</button>
      <div class="cat-dropdown" id="cat-dropdown" style="display:none"></div>
    </div>
    <div class="cat-loaded" id="cat-loaded">
      <span id="cat-loaded-name"></span>
      <a id="cat-loaded-link" href="#" target="_blank">Voir sur le site</a>
      <button class="btn-cat-clear" onclick="catReset()">✕ Effacer</button>
    </div>
  </div>

  <!-- Génération IA -->
  <div class="ia-block">
    <h2>✨ Générer par IA</h2>
    <p style="font-size:12px;color:#6d28d9;margin-bottom:10px;">Remplissez le titre (et optionnellement le contexte), puis laissez l'IA compléter le TDR automatiquement.</p>
    <button class="btn btn-ia" id="btn-ia" onclick="generateIA()" style="width:100%;padding:10px;">✨ Générer le contenu avec l'IA</button>
    <div id="ia-status" style="margin-top:8px;font-size:12px;color:#6d28d9;text-align:center;min-height:18px;"></div>
  </div>

  <!-- Type de TDR -->
  <div class="sec-block">
    <h2>① Type de document</h2>
    <div class="type-btns">
      <button class="type-btn active" data-type="formation" onclick="setType('formation',this)">Formation</button>
      <button class="type-btn" data-type="mission" onclick="setType('mission',this)">Mission</button>
      <button class="type-btn" data-type="projet" onclick="setType('projet',this)">Projet</button>
      <button class="type-btn" data-type="evaluation" onclick="setType('evaluation',this)">Évaluation</button>
    </div>
  </div>

  <!-- Identification -->
  <div class="sec-block">
    <h2>② Identification</h2>
    <label>Titre *</label>
    <input id="titre" type="text" placeholder="Ex : Formation Excel Avancé" oninput="liveUpdate()">
    <label>Client / Bénéficiaire</label>
    <input id="prospect" type="text" placeholder="Nom de l'entreprise ou particulier" oninput="liveUpdate()">
    <label>Référence document</label>
    <input id="ref_doc" type="text" placeholder="Ex : IBIG-TDR/2026/001" oninput="liveUpdate()">
    <label>Catégorie</label>
    <input id="categorie" type="text" placeholder="Ex : Management, Informatique…" oninput="liveUpdate()">
    <label>Date de début</label>
    <input id="date_debut" type="date" oninput="liveUpdate()">
  </div>

  <!-- Contexte & Objectifs -->
  <div class="sec-block">
    <h2>③ Contexte & Objectifs</h2>
    <label>Contexte</label>
    <textarea id="contexte" placeholder="Décrivez le contexte ou la problématique…" oninput="liveUpdate()"></textarea>
    <label>Objectif général</label>
    <textarea id="obj_gen" rows="2" placeholder="Objectif principal de la formation/mission…" oninput="liveUpdate()"></textarea>
    <label>Objectifs spécifiques (un par ligne)</label>
    <textarea id="obj_spec" rows="3" placeholder="Objectif 1&#10;Objectif 2&#10;Objectif 3" oninput="liveUpdate()"></textarea>
    <label>Résultats attendus (un par ligne)</label>
    <textarea id="resultats" rows="3" placeholder="Résultat 1&#10;Résultat 2" oninput="liveUpdate()"></textarea>
  </div>

  <!-- Détails spécifiques par type -->
  <!-- FORMATION -->
  <div id="sec-formation" class="sec-block">
    <h2>④ Détails formation</h2>
    <label>Durée</label>
    <input id="duree" type="text" placeholder="Ex : 25 heures / 5 jours" oninput="liveUpdate()">
    <label>Mode</label>
    <select id="mode_formation" onchange="liveUpdate()">
      <option value="en_ligne">En ligne (classe virtuelle)</option>
      <option value="presentiel">Présentiel</option>
      <option value="hybride">Hybride</option>
    </select>
    <label>Lieu / Plateforme</label>
    <input id="lieu" type="text" placeholder="Ex : Abidjan ou plateforme Zoom" oninput="liveUpdate()">
    <label>Public cible</label>
    <input id="cible" type="text" placeholder="Ex : Managers, comptables…" oninput="liveUpdate()">
    <label>Prérequis</label>
    <input id="prerequis" type="text" placeholder="Ex : Notions de base Excel" oninput="liveUpdate()">
    <label>Modules (un par ligne)</label>
    <textarea id="modules" rows="4" placeholder="Module 1 : Introduction&#10;Module 2 : Tableaux croisés" oninput="liveUpdate()"></textarea>
    <label>Méthode pédagogique</label>
    <textarea id="methode" rows="2" placeholder="Ex : Cours magistraux, exercices pratiques…" oninput="liveUpdate()"></textarea>
    <label>Évaluation</label>
    <input id="evaluation" type="text" placeholder="Ex : QCM + exercice noté" oninput="liveUpdate()">
    <label>Certification</label>
    <input id="certif" type="text" value="Certificat de compétences IBIG EDUFORM" oninput="liveUpdate()">
  </div>

  <!-- MISSION -->
  <div id="sec-mission" class="sec-block hidden">
    <h2>④ Détails mission</h2>
    <label>Périmètre / Étendue de la mission</label>
    <textarea id="m_perimetre" rows="2" placeholder="Décrivez le périmètre…" oninput="liveUpdate()"></textarea>
    <label>Livrables attendus (un par ligne)</label>
    <textarea id="m_livrables" rows="3" placeholder="Livrable 1&#10;Livrable 2" oninput="liveUpdate()"></textarea>
    <label>Durée / Calendrier</label>
    <input id="m_duree" type="text" placeholder="Ex : 3 mois — Jan à Mar 2026" oninput="liveUpdate()">
    <label>Équipe / Intervenants</label>
    <textarea id="m_equipe" rows="2" placeholder="Ex : 1 consultant senior, 1 assistant" oninput="liveUpdate()"></textarea>
  </div>

  <!-- PROJET -->
  <div id="sec-projet" class="sec-block hidden">
    <h2>④ Détails projet</h2>
    <label>Description du projet</label>
    <textarea id="p_desc" rows="3" placeholder="Décrivez le projet…" oninput="liveUpdate()"></textarea>
    <label>Phases / Jalons (un par ligne)</label>
    <textarea id="p_phases" rows="3" placeholder="Phase 1 : Analyse&#10;Phase 2 : Conception" oninput="liveUpdate()"></textarea>
    <label>Délai / Durée</label>
    <input id="p_duree" type="text" placeholder="Ex : 6 mois" oninput="liveUpdate()">
    <label>Ressources nécessaires</label>
    <textarea id="p_ressources" rows="2" placeholder="Ex : 2 développeurs, 1 chef de projet" oninput="liveUpdate()"></textarea>
  </div>

  <!-- ÉVALUATION -->
  <div id="sec-evaluation" class="sec-block hidden">
    <h2>④ Détails évaluation</h2>
    <label>Type d'évaluation</label>
    <input id="e_type" type="text" placeholder="Ex : Audit de compétences, Bilan de formation" oninput="liveUpdate()">
    <label>Critères d'évaluation (un par ligne)</label>
    <textarea id="e_criteres" rows="3" placeholder="Critère 1&#10;Critère 2" oninput="liveUpdate()"></textarea>
    <label>Outils / Méthodes</label>
    <textarea id="e_outils" rows="2" placeholder="Ex : QCM, mises en situation, entretiens" oninput="liveUpdate()"></textarea>
    <label>Durée de l'évaluation</label>
    <input id="e_duree" type="text" placeholder="Ex : 2 heures" oninput="liveUpdate()">
  </div>

  <!-- Tarification -->
  <div class="sec-block">
    <h2>⑤ Tarification</h2>
    <label>Nombre de participants</label>
    <input id="nb_participants" type="number" min="1" max="999" placeholder="Ex : 12" oninput="liveUpdate();updatePrixHint()">
    <label>Format tarifaire</label>
    <select id="fmt_tarif" onchange="liveUpdate();togglePrixFields()">
      <option value="devis">Sur devis — contactez-nous</option>
      <option value="individuel">Individuel (prix fixe par personne)</option>
      <option value="groupe">Groupe / Intra-entreprise (sur devis)</option>
    </select>
    <div id="groupe-hint" class="hidden" style="font-size:11px;color:#92400e;background:#fffbeb;border:1px solid #fde68a;border-radius:5px;padding:5px 8px;margin-top:5px;">
      ℹ️ Remises groupe IBIG EDUFORM : 2 pers. → <strong>5%</strong> · 3–5 pers. → <strong>7%</strong> · 6–10 pers. → <strong>10%</strong> · +10 pers. → <strong>12%</strong>
    </div>
    <div id="prix-fields" class="hidden">
      <label>Prix en ligne (F CFA) — min. 200 000 F</label>
      <input id="prix_en_ligne" type="number" min="200000" step="1000" placeholder="Min. 200 000 F CFA" oninput="liveUpdate();updatePrixHint()">
      <label>Prix présentiel (F CFA) — min. 250 000 F</label>
      <input id="prix_presentiel" type="number" min="250000" step="1000" placeholder="Min. 250 000 F CFA" oninput="liveUpdate();updatePrixHint()">
    </div>
    <div id="prix-hint" style="font-size:11px;border-radius:6px;padding:6px 10px;margin-top:8px;display:none;"></div>
  </div>

  <!-- Signataires -->
  <div class="sec-block">
    <h2>⑥ Signataires</h2>
    <label>Signataire IBIG EDUFORM</label>
    <input id="sig_ibig" type="text" placeholder="Nom du responsable IBIG" oninput="liveUpdate()">
    <label>Signataire Client</label>
    <input id="sig_client" type="text" placeholder="Nom du client / bénéficiaire" oninput="liveUpdate()">
  </div>

  <!-- Format PDF -->
  <div class="sec-block">
    <h2>⑦ Format du document PDF</h2>
    <div style="display:flex;gap:6px">
      <label style="flex:1;margin:0;cursor:pointer">
        <input type="radio" name="fmt_niveau" value="simplifie" style="width:auto"> <span style="font-size:12px">Simplifié<br><span style="color:var(--muted);font-weight:400">~2 pages · Essentiel</span></span>
      </label>
      <label style="flex:1;margin:0;cursor:pointer">
        <input type="radio" name="fmt_niveau" value="moyen" style="width:auto"> <span style="font-size:12px">Standard<br><span style="color:var(--muted);font-weight:400">~5 pages · Complet</span></span>
      </label>
      <label style="flex:1;margin:0;cursor:pointer">
        <input type="radio" name="fmt_niveau" value="detaille" checked style="width:auto"> <span style="font-size:12px">Détaillé<br><span style="color:var(--muted);font-weight:400">~10 pages · Exhaustif</span></span>
      </label>
    </div>
  </div>

  <!-- Actions -->
  <div class="actions">
    <button class="btn btn-primary" onclick="generate()">Aperçu</button>
    <button class="btn btn-amber" onclick="exportPDF()">PDF</button>
    <button class="btn btn-outline" onclick="exportWord()">Word</button>
  </div>
  <div style="display:flex;gap:12px;margin-top:10px;justify-content:center">
    <a href="tdr-historique.php" style="font-size:12px;color:var(--muted);text-decoration:none">📋 Historique</a>
    <a href="tarif-guide.php" style="font-size:12px;color:var(--muted);text-decoration:none">💰 Guide tarifaire</a>
  </div>
  <div id="status-msg" style="margin-top:8px;font-size:12px;color:var(--muted);text-align:center;"></div>
</div>

<!-- ═══ PREVIEW ═══ -->
<div class="preview">
  <div id="dw">
    <div style="text-align:center;color:#94a3b8;padding:80px 20px;">
      <div style="font-size:48px;margin-bottom:12px;">📄</div>
      <div style="font-size:16px;font-weight:600;margin-bottom:6px;">Aperçu du TDR</div>
      <div style="font-size:13px;">Remplissez les champs puis cliquez sur <strong>Aperçu</strong></div>
    </div>
  </div>
</div>
</div>

<script>
const LOGO = 'https://ibig-eduform.com/assets/images/logo.png';
let currentType = 'formation';
let autoRefresh = false;

function setType(t, btn) {
  currentType = t;
  document.querySelectorAll('.type-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  ['formation','mission','projet','evaluation'].forEach(s => {
    const el = document.getElementById('sec-' + s);
    if (el) el.classList.toggle('hidden', s !== t);
  });
  liveUpdate();
}

function togglePrixFields() {
  const fmt = g('fmt_tarif');
  const pf = document.getElementById('prix-fields');
  const gh = document.getElementById('groupe-hint');
  // Champs prix visibles pour individuel ET groupe
  pf.classList.toggle('hidden', fmt === 'devis');
  gh.classList.toggle('hidden', fmt !== 'groupe');
  updatePrixHint();
}

const PRIX_MIN_LIGNE = 200000;
const PRIX_MIN_PRES  = 250000;

/* Grille remises groupe IBIG EDUFORM */
function getGroupeRemise(nb) {
  if (nb >= 11) return {pct: 12, label: '+10 personnes'};
  if (nb >= 6)  return {pct: 10, label: '6–10 personnes'};
  if (nb >= 3)  return {pct:  7, label: '3–5 personnes'};
  if (nb === 2) return {pct:  5, label: '2 personnes'};
  return {pct: 0, label: ''};
}
function applyRemise(prix, pct) { return Math.round(prix * (1 - pct/100)); }

function updatePrixHint() {
  const hint = document.getElementById('prix-hint');
  const fmt = g('fmt_tarif');
  const po = parseInt(g('prix_en_ligne')) || 0;
  const pp = parseInt(g('prix_presentiel')) || 0;
  const nb = parseInt(g('nb_participants')) || 1;
  if (fmt === 'devis' || (!po && !pp)) { hint.style.display = 'none'; return; }
  let errors = [];
  if (fmt === 'individuel') {
    if (po > 0 && po < PRIX_MIN_LIGNE) errors.push('⚠️ Prix en ligne trop bas — minimum ' + fcfa(PRIX_MIN_LIGNE));
    if (pp > 0 && pp < PRIX_MIN_PRES)  errors.push('⚠️ Prix présentiel trop bas — minimum ' + fcfa(PRIX_MIN_PRES));
  }
  if (errors.length) {
    hint.style.cssText = 'font-size:11px;color:#991b1b;background:#fee2e2;border:1px solid #fca5a5;border-radius:6px;padding:6px 10px;margin-top:8px;display:block;';
    hint.innerHTML = errors.join('<br>');
    return;
  }
  let lines = [];
  if (fmt === 'groupe') {
    const r = getGroupeRemise(nb);
    if (r.pct > 0) {
      if (po) lines.push('✅ En ligne : ' + fcfa(applyRemise(po, r.pct)) + ' <span style="color:#92400e">(-' + r.pct + '%)</span>');
      if (pp) lines.push('✅ Présentiel : ' + fcfa(applyRemise(pp, r.pct)) + ' <span style="color:#92400e">(-' + r.pct + '%)</span>');
      lines.push('<em style="color:#92400e">Remise groupe ' + r.label + ' : -' + r.pct + '%</em>');
    } else {
      if (po) lines.push('✅ En ligne : ' + fcfa(po) + ' (tarif individuel — ajoutez ≥ 2 participants pour une remise)');
      if (pp) lines.push('✅ Présentiel : ' + fcfa(pp));
    }
  } else {
    if (po) lines.push('✅ En ligne : ' + fcfa(po));
    if (pp) lines.push('✅ Présentiel : ' + fcfa(pp));
  }
  if (lines.length) {
    hint.style.cssText = 'font-size:11px;color:#065f46;background:#d1fae5;border:1px solid #6ee7b7;border-radius:6px;padding:6px 10px;margin-top:8px;display:block;';
    hint.innerHTML = lines.join('<br>');
  } else hint.style.display = 'none';
}

function g(id) { const el = document.getElementById(id); return el ? el.value.trim() : ''; }
function fcfa(n) { return new Intl.NumberFormat('fr-FR').format(n) + ' F CFA'; }
function toUL(raw) {
  const ls = raw.split('\n').map(l => l.trim()).filter(Boolean);
  return ls.length ? '<ul style="margin:4px 0 4px 18px;padding:0;">' + ls.map(l => '<li>' + esc(l) + '</li>').join('') + '</ul>' : '';
}
function esc(s) { return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
function typeLabel(t) {
  return {formation:'Formation',mission:'Mission de conseil',projet:'Projet',evaluation:'Évaluation'}[t] || t;
}

function liveUpdate() { if (autoRefresh) generate(); }

function generate() {
  autoRefresh = true;
  const t = currentType;
  const titre = g('titre') || 'Sans titre';
  const prospect = g('prospect');
  const refDoc = g('ref_doc');
  const cat = g('categorie');
  const dateD = g('date_debut');
  const contexte = g('contexte');
  const objGen = g('obj_gen');
  const objSpec = g('obj_spec');
  const resultats = g('resultats');
  const nb = g('nb_participants');
  const fmt = g('fmt_tarif');
  const po = parseInt(g('prix_en_ligne')) || 0;
  const pp = parseInt(g('prix_presentiel')) || 0;
  const sigIbig = g('sig_ibig') || 'Responsable IBIG EDUFORM';
  const sigClient = g('sig_client') || (prospect || 'Le Client');

  /* ── Fiche d'identification ── */
  let ficheRows = `<tr><td>Type</td><td>${esc(typeLabel(t))}</td></tr>`;
  if (cat) ficheRows += `<tr><td>Catégorie</td><td>${esc(cat)}</td></tr>`;
  if (prospect) ficheRows += `<tr><td>Bénéficiaire</td><td>${esc(prospect)}</td></tr>`;
  if (nb) ficheRows += `<tr><td>Nombre de participants</td><td>${esc(nb)} participant(s)</td></tr>`;
  if (dateD) ficheRows += `<tr><td>Date de début</td><td>${new Date(dateD).toLocaleDateString('fr-FR',{year:'numeric',month:'long',day:'numeric'})}</td></tr>`;
  if (refDoc) ficheRows += `<tr><td>Référence</td><td>${esc(refDoc)}</td></tr>`;

  /* ── Sections contexte/objectifs ── */
  let sections = '';
  if (contexte || objGen || objSpec || resultats) {
    let body = '';
    if (contexte) body += `<div class="sec-h2">Contexte</div><p>${esc(contexte)}</p>`;
    if (objGen)   body += `<div class="sec-h2">Objectif général</div><p>${esc(objGen)}</p>`;
    if (objSpec)  body += `<div class="sec-h2">Objectifs spécifiques</div>${toUL(objSpec)}`;
    if (resultats)body += `<div class="sec-h2">Résultats attendus</div>${toUL(resultats)}`;
    sections += `<div class="sec"><div class="sec-hd">Contexte &amp; Objectifs</div><div class="sec-body">${body}</div></div>`;
  }

  /* ── Section type-spécifique ── */
  if (t === 'formation') {
    const duree = g('duree'); const mode = g('mode_formation'); const lieu = g('lieu');
    const cible = g('cible'); const prereq = g('prerequis');
    const modules = g('modules'); const methode = g('methode');
    const eval_ = g('evaluation'); const certif = g('certif');
    const modeLabel = {en_ligne:'En ligne (classe virtuelle)',presentiel:'Présentiel',hybride:'Hybride (En ligne & Présentiel)'}[mode] || mode;
    let b = '';
    if (duree||mode||lieu||cible||prereq) {
      let r = '';
      if (duree) r += `<tr><td>Durée</td><td>${esc(duree)}</td></tr>`;
      r += `<tr><td>Mode de formation</td><td>${esc(modeLabel)}</td></tr>`;
      if (lieu) r += `<tr><td>Lieu / Plateforme</td><td>${esc(lieu)}</td></tr>`;
      if (cible) r += `<tr><td>Public cible</td><td>${esc(cible)}</td></tr>`;
      if (prereq) r += `<tr><td>Prérequis</td><td>${esc(prereq)}</td></tr>`;
      b += `<table class="fiche">${r}</table>`;
    }
    if (modules) { b += `<div class="sec-h2">Programme / Modules</div>${toUL(modules)}`; }
    if (methode) { b += `<div class="sec-h2">Méthode pédagogique</div><p>${esc(methode)}</p>`; }
    if (eval_) { b += `<div class="sec-h2">Évaluation</div><p>${esc(eval_)}</p>`; }
    if (certif) { b += `<div class="sec-h2">Certification</div><p>${esc(certif)}</p>`; }
    if (b) sections += `<div class="sec"><div class="sec-hd">Détails de la Formation</div><div class="sec-body">${b}</div></div>`;

  } else if (t === 'mission') {
    const per = g('m_perimetre'); const liv = g('m_livrables');
    const dur = g('m_duree'); const equipe = g('m_equipe');
    let b = '';
    if (per) b += `<div class="sec-h2">Périmètre</div><p>${esc(per)}</p>`;
    if (liv) b += `<div class="sec-h2">Livrables</div>${toUL(liv)}`;
    if (dur) b += `<div class="sec-h2">Calendrier</div><p>${esc(dur)}</p>`;
    if (equipe) b += `<div class="sec-h2">Équipe</div><p>${esc(equipe)}</p>`;
    if (b) sections += `<div class="sec"><div class="sec-hd">Détails de la Mission</div><div class="sec-body">${b}</div></div>`;

  } else if (t === 'projet') {
    const desc = g('p_desc'); const phases = g('p_phases');
    const dur = g('p_duree'); const res = g('p_ressources');
    let b = '';
    if (desc) b += `<div class="sec-h2">Description</div><p>${esc(desc)}</p>`;
    if (phases) b += `<div class="sec-h2">Phases / Jalons</div>${toUL(phases)}`;
    if (dur) b += `<div class="sec-h2">Délai</div><p>${esc(dur)}</p>`;
    if (res) b += `<div class="sec-h2">Ressources</div><p>${esc(res)}</p>`;
    if (b) sections += `<div class="sec"><div class="sec-hd">Détails du Projet</div><div class="sec-body">${b}</div></div>`;

  } else if (t === 'evaluation') {
    const eType = g('e_type'); const crit = g('e_criteres');
    const outils = g('e_outils'); const dur = g('e_duree');
    let b = '';
    if (eType) b += `<div class="sec-h2">Type d'évaluation</div><p>${esc(eType)}</p>`;
    if (crit) b += `<div class="sec-h2">Critères</div>${toUL(crit)}`;
    if (outils) b += `<div class="sec-h2">Outils & Méthodes</div><p>${esc(outils)}</p>`;
    if (dur) b += `<div class="sec-h2">Durée</div><p>${esc(dur)}</p>`;
    if (b) sections += `<div class="sec"><div class="sec-hd">Détails de l'Évaluation</div><div class="sec-body">${b}</div></div>`;
  }

  /* ── Tarification ── */
  let prixHtml = '';
  if (fmt === 'devis') {
    prixHtml = `<div class="prix-box"><div class="prix-box-title">Tarification</div><p>Sur devis — contactez-nous pour obtenir une proposition personnalisée.</p><p style="margin-top:4px;font-size:9.5pt;color:#475569;">📧 contact@ibig-eduform.com &nbsp;|&nbsp; 🌐 www.ibig-eduform.com</p></div>`;
  } else if (po || pp) {
    if (fmt === 'individuel' && ((po > 0 && po < PRIX_MIN_LIGNE) || (pp > 0 && pp < PRIX_MIN_PRES))) {
      document.getElementById('dw').innerHTML = '<div style="text-align:center;padding:40px;color:#991b1b;font-size:13px;"><strong>⚠️ Tarif non conforme au guide IBIG EDUFORM</strong><br>Prix en ligne min. : ' + fcfa(PRIX_MIN_LIGNE) + ' &nbsp;|&nbsp; Présentiel min. : ' + fcfa(PRIX_MIN_PRES) + '</div>';
      return;
    }
    const nbInt = parseInt(nb) || 1;
    const isGroupe = fmt === 'groupe';
    const remise = isGroupe ? getGroupeRemise(nbInt) : {pct:0, label:''};
    let rows = '';
    if (isGroupe && remise.pct > 0) {
      // Ligne tarif de base (barré) + tarif avec remise
      if (po) {
        const appl = applyRemise(po, remise.pct);
        rows += `<tr><td>En ligne / pers.</td><td style="font-weight:700;color:#0d1f3c;">${fcfa(appl)} <span style="font-weight:400;color:#92400e;font-size:9pt">(-${remise.pct}%)</span></td></tr>`;
        rows += `<tr style="background:#fffbeb"><td>Total groupe en ligne (${nbInt} pers.)</td><td style="font-weight:900;color:#b45309">${fcfa(appl * nbInt)}</td></tr>`;
      }
      if (pp) {
        const appl = applyRemise(pp, remise.pct);
        rows += `<tr><td>Présentiel / pers.</td><td style="font-weight:700;color:#0d1f3c;">${fcfa(appl)} <span style="font-weight:400;color:#92400e;font-size:9pt">(-${remise.pct}%)</span></td></tr>`;
        rows += `<tr style="background:#fffbeb"><td>Total groupe présentiel (${nbInt} pers.)</td><td style="font-weight:900;color:#b45309">${fcfa(appl * nbInt)}</td></tr>`;
      }
      const label = `Groupe — ${nbInt} participants (remise ${remise.pct}% · ${remise.label})`;
      prixHtml = `<div class="prix-box"><div class="prix-box-title">Tarification — ${esc(label)}</div><table class="tbl"><thead><tr><th>Formule</th><th>Montant</th></tr></thead><tbody>${rows}</tbody></table></div>`;
    } else {
      if (po) rows += `<tr><td>En ligne</td><td style="font-weight:700;color:#0d1f3c;">${fcfa(po)}</td></tr>`;
      if (pp) rows += `<tr><td>Présentiel</td><td style="font-weight:700;color:#0d1f3c;">${fcfa(pp)}</td></tr>`;
      const label = isGroupe ? `Groupe (${nbInt} participant)` : 'Individuel — par personne';
      prixHtml = `<div class="prix-box"><div class="prix-box-title">Tarification — ${esc(label)}</div><table class="tbl"><thead><tr><th>Format</th><th>Montant</th></tr></thead><tbody>${rows}</tbody></table></div>`;
    }
  }
  if (prixHtml) {
    sections += `<div class="sec"><div class="sec-hd">Conditions Financières</div><div class="sec-body">${prixHtml}</div></div>`;
  }

  /* ── Signatures ── */
  const sig = `<div class="sig-row">
    <div class="sig-box"><div class="sig-title">Pour IBIG EDUFORM</div><div class="sig-space"></div><div class="sig-name">${esc(sigIbig)}</div></div>
    <div class="sig-box"><div class="sig-title">Le Client / Bénéficiaire</div><div class="sig-space"></div><div class="sig-name">${esc(sigClient)}</div></div>
  </div>`;

  /* ── Assemblage final ── */
  document.getElementById('dw').innerHTML = `<div class="wrap" id="tdrdoc">
  <div class="hd">
    <img src="${LOGO}" alt="IBIG EDUFORM" onerror="this.style.display='none'">
    <div class="hd-title">IBIG EDUFORM</div>
    <div class="hd-inst">Institut de Formation Professionnelle — INTERMARK BUSINESS INTERNATIONAL GROUP<br>www.ibig-eduform.com &nbsp;|&nbsp; Abidjan – Côte d'Ivoire</div>
    <div class="tdr-title">TERMES DE RÉFÉRENCE (TDR)</div>
    <div class="tdr-sub">${esc(typeLabel(t))}${cat ? ' — ' + esc(cat) : ''}</div>
    <div class="tdr-nom">${esc(titre)}</div>
  </div>
  <div class="sec">
    <table class="fiche">${ficheRows}</table>
  </div>
  ${sections}
  ${sig}
  <div class="foot">
    Document confidentiel — IBIG EDUFORM &nbsp;|&nbsp; Certificats reconnus dans les 17 pays membres de l'OHADA<br>
    www.ibig-eduform.com &nbsp;·&nbsp; contact@ibig-eduform.com${refDoc ? ' &nbsp;·&nbsp; Réf : ' + esc(refDoc) : ''}
  </div>
</div>`;
}

function exportPDF() {
  const doc = document.getElementById('tdrdoc');
  if (!doc) { alert('Cliquez d\'abord sur Aperçu pour générer le document.'); return; }
  const msg = document.getElementById('status-msg');
  msg.textContent = 'Génération du PDF…';
  const form = new FormData();
  form.append('titre', g('titre'));
  form.append('sous_titre', '');
  form.append('categorie', g('categorie'));
  form.append('prospect_name', g('prospect'));
  form.append('nb_participants', g('nb_participants'));
  form.append('date_debut', g('date_debut'));
  form.append('ref_doc', g('ref_doc'));
  form.append('contexte', g('contexte'));
  form.append('obj_gen', g('obj_gen'));
  form.append('obj_spec', g('obj_spec'));
  form.append('resultats', g('resultats'));
  form.append('duree', g('duree'));
  form.append('mode_formation', g('mode_formation'));
  form.append('lieu', g('lieu'));
  form.append('f_cible', g('cible'));
  form.append('prerequis', g('prerequis'));
  form.append('f_modules', g('modules'));
  form.append('f_methode', g('methode'));
  form.append('f_eval', g('evaluation'));
  form.append('certif', g('certif'));
  const po = parseInt(g('prix_en_ligne')) || 0;
  const pp = parseInt(g('prix_presentiel')) || 0;
  const fmt = g('fmt_tarif');
  form.append('prix_en_ligne', fmt === 'devis' ? '0' : po);
  form.append('prix_presentiel', fmt === 'devis' ? '0' : pp);
  form.append('f_format', currentType === 'formation' ? 'Individuel' : typeLabel(currentType));
  form.append('sig_ibig', g('sig_ibig'));
  form.append('sig_client', g('sig_client'));
  const fmtNiv = document.querySelector('input[name="fmt_niveau"]:checked');
  form.append('format_niveau', fmtNiv ? fmtNiv.value : 'moyen');
  form.append('type_doc', currentType);
  form.append('fmt_tarif', fmt);

  fetch('tdr-export-pdf.php', { method:'POST', body:form })
    .then(r => {
      if (!r.ok) throw new Error('Erreur serveur ' + r.status);
      return r.blob();
    })
    .then(b => {
      const url = URL.createObjectURL(b);
      const a = document.createElement('a');
      const nom = (g('titre') || 'TDR').replace(/\s+/g,'-').replace(/[^a-zA-Z0-9\-_]/g,'');
      a.href = url; a.download = 'TDR-' + nom + '.pdf'; a.click();
      URL.revokeObjectURL(url);
      msg.textContent = 'PDF téléchargé.';
      setTimeout(() => msg.textContent = '', 3000);
    })
    .catch(e => { msg.textContent = 'Erreur : ' + e.message; });
}

function set(id, val) { const el = document.getElementById(id); if (el) el.value = val || ''; }

async function generateIA() {
  const titre = g('titre');
  if (!titre) { alert('Remplissez d\'abord le Titre avant de générer par IA.'); return; }
  const btn = document.getElementById('btn-ia');
  const status = document.getElementById('ia-status');
  btn.disabled = true;
  status.textContent = '⏳ Génération en cours…';
  const form = new FormData();
  form.append('titre', titre);
  form.append('type', currentType);
  form.append('categorie', g('categorie'));
  form.append('contexte', g('contexte'));
  try {
    const r = await fetch('tdr-ai-generate.php', { method:'POST', body:form });
    const json = await r.json();
    if (!r.ok || json.error) throw new Error(json.error || 'Erreur serveur');
    const d = json.data;
    set('contexte',    d.contexte);
    set('obj_gen',     d.obj_gen);
    set('obj_spec',    d.obj_spec);
    set('resultats',   d.resultats);
    set('duree',       d.duree);
    set('lieu',        d.lieu);
    set('cible',       d.cible);
    set('prerequis',   d.prerequis);
    set('modules',     d.modules);
    set('methode',     d.methode);
    set('evaluation',  d.evaluation);
    set('certif',      d.certif);
    status.textContent = '✅ Contenu généré — vérifiez et ajustez si besoin.';
    status.style.color = '#059669';
    generate();
  } catch(e) {
    status.textContent = '❌ ' + e.message;
    status.style.color = '#dc2626';
  } finally {
    btn.disabled = false;
  }
}

/* ══════════════════════════════════════════════
   CATALOGUE SEARCH — recherche & import auto
   ══════════════════════════════════════════════ */
let catTimer = null;
let catCurrentSlug = null;

function catSearch(val) {
  const dd = document.getElementById('cat-dropdown');
  const clearBtn = document.getElementById('cat-clear-btn');
  clearBtn.style.display = val ? 'block' : 'none';
  if (val.length < 2) { dd.style.display = 'none'; return; }
  clearTimeout(catTimer);
  catTimer = setTimeout(async () => {
    dd.innerHTML = '<div style="padding:10px;color:#94a3b8;font-size:12px">Recherche…</div>';
    dd.style.display = 'block';
    try {
      const r = await fetch('tdr-catalogue-search.php?q=' + encodeURIComponent(val));
      const json = await r.json();
      if (!json.ok || !json.results.length) {
        dd.innerHTML = '<div style="padding:10px;color:#94a3b8;font-size:12px">Aucune formation trouvée</div>';
        return;
      }
      dd.innerHTML = json.results.map(f => `
        <div class="cat-item" onclick="catLoad('${f.slug.replace(/'/g,"\\'")}','${f.name.replace(/'/g,"\\'").replace(/"/g,'&quot;')}')">
          <span class="ci-name">${f.name}</span>
          <span class="ci-cat">${f.category}</span>
          <span class="ci-prix">${f.price ? f.price.toLocaleString('fr-FR') + ' F' : ''}</span>
        </div>`).join('');
    } catch(e) {
      dd.innerHTML = '<div style="padding:10px;color:#dc2626;font-size:12px">Erreur de connexion</div>';
    }
  }, 300);
}

function catLoad(slug, name) {
  const dd  = document.getElementById('cat-dropdown');
  const inp = document.getElementById('cat-input');
  dd.style.display = 'none';
  inp.value = name;
  catCurrentSlug = slug;

  const loaded = document.getElementById('cat-loaded');
  const pdfUrl = 'tdr-catalogue-pdf.php?slug=' + encodeURIComponent(slug);

  loaded.innerHTML = `
    <span>✅ <strong>${name}</strong></span>
    <a href="${pdfUrl}" target="_blank"
       style="display:inline-flex;align-items:center;gap:5px;padding:6px 14px;background:#0d1f3c;color:#fff;
              border-radius:6px;font-size:12px;font-weight:600;text-decoration:none;margin-left:10px"
       onclick="document.getElementById('cat-clear-btn').style.display='inline-flex'">
      📥 Télécharger le TDR PDF
    </a>
    <button class="btn-cat-clear" onclick="catReset()">✕ Effacer</button>`;
  loaded.style.display = 'flex';
  document.getElementById('cat-clear-btn').style.display = 'inline-flex';
}

function deconnexion() {
  if (!confirm('Voulez-vous vraiment vous déconnecter ?')) return;
  location.href = 'https://ibig-eduform.com/';
}

function catReset() {
  document.getElementById('cat-input').value = '';
  document.getElementById('cat-dropdown').style.display = 'none';
  document.getElementById('cat-clear-btn').style.display = 'none';
  document.getElementById('cat-loaded').style.display = 'none';
  catCurrentSlug = null;
}

/* Ferme le dropdown au clic extérieur */
document.addEventListener('click', function(e) {
  if (!document.getElementById('cat-wrap').contains(e.target)) {
    document.getElementById('cat-dropdown').style.display = 'none';
  }
});

function exportWord() {
  const doc = document.getElementById('tdrdoc');
  if (!doc) { alert('Cliquez d\'abord sur Aperçu.'); return; }
  const html = `<html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'>
<head><meta charset='utf-8'><style>
body{font-family:Arial;font-size:11pt;color:#1a1a2e;}
.hd{text-align:center;border-bottom:3pt solid #f59e0b;padding-bottom:10pt;margin-bottom:14pt;}
.hd-title{font-size:15pt;font-weight:bold;color:#0d1f3c;}
.hd-inst{font-size:8.5pt;color:#475569;}
.tdr-title{font-size:13pt;font-weight:bold;color:#0d1f3c;}
.tdr-nom{font-size:12pt;font-weight:bold;color:#b45309;}
.sec-hd{background:#0d1f3c;color:#fff;padding:6pt 12pt;font-size:10.5pt;font-weight:bold;}
.sec-body{border:1pt solid #cbd5e1;padding:10pt 12pt;}
.fiche td{padding:5pt 8pt;border:1pt solid #e2e8f0;}
.fiche td:first-child{background:#f8fafc;font-weight:bold;}
.tbl th{background:#0d1f3c;color:#fff;padding:5pt 8pt;}
.tbl td{padding:5pt 8pt;border:1pt solid #e2e8f0;}
.prix-box{border:2pt solid #f59e0b;padding:10pt;background:#fffbeb;}
.foot{text-align:center;font-size:8.5pt;color:#94a3b8;border-top:2pt solid #f59e0b;margin-top:16pt;padding-top:8pt;}
</style></head><body>${doc.outerHTML}</body></html>`;
  const blob = new Blob([html], {type:'application/msword'});
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  const nom = (g('titre') || 'TDR').replace(/\s+/g,'-').replace(/[^a-zA-Z0-9\-_]/g,'');
  a.href = url; a.download = 'TDR-' + nom + '.doc'; a.click();
  URL.revokeObjectURL(url);
}
</script>
</body>
</html>
