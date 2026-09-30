<?php
require_once __DIR__ . '/core/bootstrap.php';

/* ── AJAX : sauvegarde du lead ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_lead') {
    header('Content-Type: application/json');
    $nom      = trim(strip_tags($_POST['nom']      ?? ''));
    $prenom   = trim(strip_tags($_POST['prenom']   ?? ''));
    $email    = filter_var(trim($_POST['email']    ?? ''), FILTER_VALIDATE_EMAIL);
    $indicatif= trim(strip_tags($_POST['indicatif']?? '+225'));
    $whatsapp = trim(strip_tags($_POST['whatsapp'] ?? ''));
    $besoin   = trim(strip_tags($_POST['besoin']   ?? ''));
    $domain   = trim(strip_tags($_POST['domain']   ?? ''));
    $niveau   = trim(strip_tags($_POST['niveau']   ?? ''));
    $objectif = trim(strip_tags($_POST['objectif'] ?? ''));
    $mode     = trim(strip_tags($_POST['mode']     ?? ''));
    $score    = intval($_POST['score'] ?? 0);
    if (!$email) { echo json_encode(['ok'=>false]); exit; }
    $tel_complet = $indicatif . ' ' . $whatsapp;
    try {
        $pdo = Database::connect();
        $pdo->exec("CREATE TABLE IF NOT EXISTS diagnostic_leads (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nom VARCHAR(100), prenom VARCHAR(100),
            email VARCHAR(255), indicatif VARCHAR(10), whatsapp VARCHAR(30),
            besoin TEXT, domaine VARCHAR(100), niveau VARCHAR(60),
            objectif VARCHAR(120), mode_formation VARCHAR(60), score TINYINT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        /* Ajout colonnes si table existante (migration silencieuse) */
        foreach(['nom VARCHAR(100)','indicatif VARCHAR(10)','whatsapp VARCHAR(30)','besoin TEXT'] as $col) {
            try { $pdo->exec("ALTER TABLE diagnostic_leads ADD COLUMN $col"); } catch(Throwable $e){}
        }
        $pdo->prepare("INSERT INTO diagnostic_leads
            (nom,prenom,email,indicatif,whatsapp,besoin,domaine,niveau,objectif,mode_formation,score)
            VALUES (?,?,?,?,?,?,?,?,?,?,?)")
            ->execute([$nom,$prenom,$email,$indicatif,$whatsapp,$besoin,$domain,$niveau,$objectif,$mode,$score]);
    } catch (Throwable $e) { /* silent */ }
    echo json_encode(['ok'=>true]);
    exit;
}

$pageTitle    = "Diagnostic de compétences – IBIG EDUFORM";
$ogDesc       = "Testez vos compétences professionnelles en 5 minutes. Obtenez un rapport personnalisé et les formations IBIG EDUFORM adaptées à votre profil.";
$pageKeywords = "diagnostic compétences, bilan compétences Abidjan, test niveau professionnel, formation recommandée Afrique OHADA";
include __DIR__ . '/partials/header.php';
?>
<style>
:root{
  --blue:#1a3fd0;--red:#e8242c;--navy:#08122b;--gold:#f5a623;
  --bg:#05091a;--card:#0d1630;--border:rgba(255,255,255,.10);
}
body{background:var(--bg);color:#e5e7eb;font-family:Inter,system-ui,sans-serif;min-height:100vh}

/* ── HERO ── */
.dg-hero{
  text-align:center;padding:64px 24px 40px;
  background:radial-gradient(ellipse at 50% 0%,rgba(26,63,208,.28),transparent 65%);
}
.dg-badge{
  display:inline-flex;align-items:center;gap:8px;
  background:rgba(245,166,35,.12);border:1px solid rgba(245,166,35,.35);
  color:#f5a623;font-size:.8rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;
  padding:6px 16px;border-radius:99px;margin-bottom:20px;
}
.dg-hero h1{font-size:clamp(1.8rem,4vw,2.8rem);font-weight:900;margin:0 0 16px;
  background:linear-gradient(135deg,#fff 40%,#f5a623);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.dg-hero p{color:#94a3b8;max-width:620px;margin:0 auto 32px;font-size:1.05rem;line-height:1.7}
.dg-hero-stats{display:flex;justify-content:center;gap:32px;flex-wrap:wrap}
.dg-hs{text-align:center}
.dg-hs strong{display:block;font-size:1.5rem;font-weight:900;color:#f5a623}
.dg-hs span{font-size:.78rem;color:#64748b;text-transform:uppercase;letter-spacing:.06em}

/* ── PROGRESS ── */
.dg-progress-wrap{max-width:680px;margin:0 auto;padding:0 24px}
.dg-progress-bar{
  height:6px;background:rgba(255,255,255,.08);border-radius:99px;overflow:hidden;margin-bottom:10px;
}
.dg-progress-fill{height:100%;background:linear-gradient(90deg,#1a3fd0,#f5a623);border-radius:99px;transition:width .5s ease}
.dg-progress-label{text-align:right;font-size:.78rem;color:#64748b}

/* ── QUIZ WRAP ── */
.dg-wrap{max-width:760px;margin:0 auto;padding:0 24px 100px}

/* ── STEP CARD ── */
.dg-step{display:none}
.dg-step.active{display:block;animation:fadeUp .4s ease}
@keyframes fadeUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:none}}

.dg-card{
  background:var(--card);border:1px solid var(--border);border-radius:24px;
  padding:36px 32px;margin-top:20px;
}
.dg-step-label{font-size:.75rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:#f5a623;margin-bottom:10px}
.dg-card h2{font-size:1.5rem;font-weight:800;margin:0 0 28px;color:#fff}

/* ── OPTION CARDS ── */
.dg-options{display:grid;gap:12px}
.dg-options.cols2{grid-template-columns:repeat(2,1fr)}
.dg-options.cols3{grid-template-columns:repeat(3,1fr)}
@media(max-width:560px){.dg-options.cols2,.dg-options.cols3{grid-template-columns:1fr}}

.dg-opt{
  position:relative;cursor:pointer;
  background:rgba(255,255,255,.04);border:1.5px solid var(--border);border-radius:16px;
  padding:16px 18px 16px 48px;
  font-size:.96rem;font-weight:600;color:#cbd5e1;
  transition:.2s ease;user-select:none;
}
.dg-opt:hover{background:rgba(26,63,208,.12);border-color:rgba(26,63,208,.5);color:#fff}
.dg-opt.selected{background:rgba(26,63,208,.18);border-color:#1a3fd0;color:#fff}
.dg-opt .dg-ico{
  position:absolute;left:14px;top:50%;transform:translateY(-50%);
  font-size:1.2rem;
}
.dg-opt-check{
  position:absolute;right:14px;top:50%;transform:translateY(-50%);
  width:20px;height:20px;border-radius:50%;border:2px solid rgba(255,255,255,.2);
  display:grid;place-items:center;transition:.2s;
}
.dg-opt.selected .dg-opt-check{background:#1a3fd0;border-color:#1a3fd0}
.dg-opt.selected .dg-opt-check::after{content:"✓";font-size:11px;color:#fff;font-weight:900}

/* ── DOMAIN GRID (icônes grandes) ── */
.dg-domains{display:grid;grid-template-columns:repeat(3,1fr);gap:12px}
@media(max-width:560px){.dg-domains{grid-template-columns:repeat(2,1fr)}}
.dg-domain-card{
  cursor:pointer;text-align:center;
  background:rgba(255,255,255,.04);border:1.5px solid var(--border);border-radius:18px;
  padding:20px 12px;transition:.2s ease;
}
.dg-domain-card:hover{background:rgba(26,63,208,.12);border-color:rgba(26,63,208,.5)}
.dg-domain-card.selected{background:rgba(26,63,208,.20);border-color:#1a3fd0}
.dg-domain-card .dg-d-ico{font-size:2rem;margin-bottom:8px}
.dg-domain-card .dg-d-name{font-size:.85rem;font-weight:700;color:#cbd5e1;line-height:1.3}
.dg-domain-card.selected .dg-d-name{color:#fff}

/* ── QUESTION Q&A ── */
.dg-q{margin-bottom:24px}
.dg-q-label{font-weight:700;color:#e2e8f0;margin-bottom:14px;font-size:1rem}
.dg-q-opts{display:grid;gap:8px}
.dg-q-opt{
  cursor:pointer;
  background:rgba(255,255,255,.04);border:1.5px solid var(--border);border-radius:12px;
  padding:12px 16px;font-size:.9rem;color:#94a3b8;
  transition:.2s ease;display:flex;align-items:center;gap:10px;
}
.dg-q-opt:hover{background:rgba(26,63,208,.10);border-color:rgba(26,63,208,.4);color:#cbd5e1}
.dg-q-opt.selected{background:rgba(26,63,208,.16);border-color:#1a3fd0;color:#fff}
.dg-q-opt .dg-ltr{
  width:26px;height:26px;border-radius:8px;flex-shrink:0;
  background:rgba(255,255,255,.08);font-size:.75rem;font-weight:900;
  display:grid;place-items:center;color:#64748b;
}
.dg-q-opt.selected .dg-ltr{background:#1a3fd0;color:#fff}

/* ── FORM ── */
.dg-form{display:grid;gap:16px}
.dg-form-row{display:grid;grid-template-columns:1fr 1fr;gap:14px}
@media(max-width:520px){.dg-form-row{grid-template-columns:1fr}}
.dg-field label{display:block;font-size:.82rem;font-weight:700;color:#94a3b8;margin-bottom:7px;letter-spacing:.04em;text-transform:uppercase}
.dg-field label .req{color:#f5a623;margin-left:2px}
.dg-field input,.dg-field textarea,.dg-indicatif{
  background:rgba(255,255,255,.06);border:1.5px solid var(--border);
  border-radius:12px;color:#fff;font-size:.97rem;font-family:inherit;
  outline:none;transition:.2s;box-sizing:border-box;
}
.dg-field input,.dg-field textarea{width:100%;padding:14px 16px}
.dg-field input:focus,.dg-field textarea:focus{border-color:#1a3fd0;background:rgba(26,63,208,.10)}
.dg-field input::placeholder,.dg-field textarea::placeholder{color:#475569}
.dg-field textarea{resize:vertical;min-height:88px;line-height:1.6}
.dg-phone-wrap{display:flex;gap:8px}
.dg-indicatif{
  padding:14px 12px;flex-shrink:0;width:130px;cursor:pointer;
  -webkit-appearance:none;appearance:none;
  background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath fill='%2394a3b8' d='M1 1l5 5 5-5'/%3E%3C/svg%3E");
  background-repeat:no-repeat;background-position:right 10px center;padding-right:28px;
}
.dg-indicatif:focus{border-color:#1a3fd0;background-color:rgba(26,63,208,.10)}
.dg-indicatif option{background:#0d1630;color:#fff}
.dg-phone-num{flex:1}
.dg-field-hint{display:block;font-size:.76rem;color:#475569;margin-top:6px;line-height:1.5}

/* ── BTN ── */
.dg-btn{
  display:inline-flex;align-items:center;gap:8px;justify-content:center;
  background:linear-gradient(135deg,#1a3fd0,#3b5bff);color:#fff;
  font-size:1rem;font-weight:800;padding:16px 32px;border-radius:14px;border:0;
  cursor:pointer;width:100%;margin-top:10px;transition:.2s ease;font-family:inherit;
}
.dg-btn:hover{transform:translateY(-2px);box-shadow:0 16px 40px rgba(26,63,208,.4)}
.dg-btn:disabled{opacity:.5;cursor:not-allowed;transform:none}

.dg-btn-back{
  background:rgba(255,255,255,.06);color:#94a3b8;
  font-size:.9rem;font-weight:600;padding:12px 20px;border-radius:12px;border:0;
  cursor:pointer;font-family:inherit;margin-top:10px;width:100%;transition:.2s;
}
.dg-btn-back:hover{background:rgba(255,255,255,.10);color:#fff}

/* ── RÉSULTATS ── */
.dg-result{display:none}
.dg-result.active{display:block;animation:fadeUp .5s ease}

.dg-score-wrap{text-align:center;margin-bottom:36px}
.dg-score-ring{
  width:140px;height:140px;border-radius:50%;margin:0 auto 20px;
  display:grid;place-items:center;
  background:conic-gradient(#1a3fd0 var(--pct),rgba(255,255,255,.08) 0);
  position:relative;
}
.dg-score-ring::before{
  content:"";position:absolute;inset:14px;border-radius:50%;background:var(--card);
}
.dg-score-num{position:relative;z-index:1;font-size:2.2rem;font-weight:900;color:#fff}
.dg-score-label{font-size:.78rem;color:#64748b;letter-spacing:.06em;text-transform:uppercase;margin-top:4px}
.dg-profile-badge{
  display:inline-block;padding:8px 22px;border-radius:99px;
  font-weight:800;font-size:.9rem;margin-bottom:8px;
}
.dg-profile-name{font-size:1.6rem;font-weight:900;color:#fff;margin-bottom:8px}
.dg-profile-desc{color:#94a3b8;max-width:500px;margin:0 auto;line-height:1.7;font-size:.95rem}

.dg-recs-title{font-size:1.2rem;font-weight:800;color:#fff;margin:36px 0 16px}
.dg-rec-card{
  background:rgba(255,255,255,.04);border:1.5px solid var(--border);border-radius:18px;
  padding:22px 24px;margin-bottom:12px;display:flex;align-items:flex-start;gap:18px;
  transition:.2s;
}
.dg-rec-card:hover{border-color:rgba(26,63,208,.5);background:rgba(26,63,208,.08)}
.dg-rec-num{
  width:42px;height:42px;border-radius:12px;flex-shrink:0;
  background:linear-gradient(135deg,#1a3fd0,#3b5bff);
  display:grid;place-items:center;font-weight:900;font-size:1.1rem;color:#fff;
}
.dg-rec-body{flex:1}
.dg-rec-name{font-weight:800;font-size:1rem;color:#fff;margin-bottom:6px}
.dg-rec-desc{font-size:.88rem;color:#64748b;line-height:1.6;margin-bottom:10px}
.dg-rec-tags{display:flex;gap:8px;flex-wrap:wrap}
.dg-tag{
  padding:4px 12px;border-radius:99px;font-size:.75rem;font-weight:700;
  background:rgba(245,166,35,.12);color:#f5a623;border:1px solid rgba(245,166,35,.25);
}
.dg-tag.blue{background:rgba(26,63,208,.18);color:#93c5fd;border-color:rgba(26,63,208,.3)}
.dg-rec-link{
  display:inline-flex;align-items:center;gap:6px;
  color:#1a3fd0;font-size:.85rem;font-weight:700;text-decoration:none;
  margin-top:8px;
}
.dg-rec-link:hover{color:#f5a623}

.dg-cta-final{
  margin-top:36px;padding:32px;border-radius:22px;text-align:center;
  background:linear-gradient(135deg,rgba(26,63,208,.2),rgba(232,36,44,.12));
  border:1px solid rgba(26,63,208,.3);
}
.dg-cta-final h3{font-size:1.3rem;font-weight:900;color:#fff;margin:0 0 10px}
.dg-cta-final p{color:#94a3b8;margin:0 0 20px;font-size:.95rem}
.dg-cta-btns{display:flex;gap:12px;justify-content:center;flex-wrap:wrap}
.dg-cta-btns a{
  padding:14px 28px;border-radius:12px;font-weight:800;font-size:.95rem;text-decoration:none;
  transition:.2s;
}
.dg-cta-btns .cta-primary{background:linear-gradient(135deg,#e8242c,#ff5763);color:#fff;box-shadow:0 8px 24px rgba(232,36,44,.3)}
.dg-cta-btns .cta-secondary{background:rgba(255,255,255,.08);color:#cbd5e1;border:1px solid var(--border)}
.dg-cta-btns .cta-primary:hover{transform:translateY(-2px)}
.dg-cta-btns .cta-secondary:hover{background:rgba(255,255,255,.14);color:#fff}

/* ── LOADER ── */
.dg-loader{display:none;text-align:center;padding:40px}
.dg-spinner{
  width:48px;height:48px;border:4px solid rgba(255,255,255,.1);
  border-top-color:#1a3fd0;border-radius:50%;animation:spin .8s linear infinite;margin:0 auto 16px;
}
@keyframes spin{to{transform:rotate(360deg)}}
</style>

<main style="padding-top:20px">

<!-- HERO -->
<section class="dg-hero">
  <div class="dg-badge">🎯 Outil exclusif IBIG EDUFORM</div>
  <h1>Diagnostic de compétences professionnelles</h1>
  <p>5 minutes pour connaître votre niveau, identifier vos lacunes et découvrir les formations qui vont booster votre carrière.</p>
  <div class="dg-hero-stats">
    <div class="dg-hs"><strong>5 min</strong><span>Durée</span></div>
    <div class="dg-hs"><strong>100%</strong><span>Gratuit</span></div>
    <div class="dg-hs"><strong>3</strong><span>Formations recommandées</span></div>
    <div class="dg-hs"><strong>9</strong><span>Domaines couverts</span></div>
  </div>
</section>

<!-- PROGRESS -->
<div class="dg-progress-wrap" id="progressWrap" style="margin-top:32px;display:none">
  <div class="dg-progress-bar"><div class="dg-progress-fill" id="progressFill" style="width:0%"></div></div>
  <div class="dg-progress-label" id="progressLabel">Étape 1 sur 5</div>
</div>

<!-- QUIZ -->
<div class="dg-wrap">

  <!-- STEP 0 : FORMULAIRE D'IDENTIFICATION (AFFICHÉ EN PREMIER) -->
  <div class="dg-step active" id="step0">
    <div class="dg-card">
      <div class="dg-step-label">Pour commencer</div>
      <h2>Renseignez vos informations</h2>
      <p style="color:#64748b;margin-top:-16px;margin-bottom:28px;font-size:.93rem;line-height:1.6">
        Nous préparerons votre rapport personnalisé à partir de ces informations.
      </p>
      <div class="dg-form">
        <div class="dg-form-row">
          <div class="dg-field">
            <label>Nom <span class="req">*</span></label>
            <input type="text" id="f-nom" placeholder="Votre nom de famille" autocomplete="family-name">
          </div>
          <div class="dg-field">
            <label>Prénom <span class="req">*</span></label>
            <input type="text" id="f-prenom" placeholder="Votre prénom" autocomplete="given-name">
          </div>
        </div>
        <div class="dg-field">
          <label>Adresse e-mail <span class="req">*</span></label>
          <input type="email" id="f-email" placeholder="votre@email.com" autocomplete="email">
        </div>
        <div class="dg-field">
          <label>Contact WhatsApp <span class="req">*</span></label>
          <div class="dg-phone-wrap">
            <select id="f-indicatif" class="dg-indicatif">
              <option value="+225">🇨🇮 +225</option>
              <option value="+221">🇸🇳 +221</option>
              <option value="+223">🇲🇱 +223</option>
              <option value="+226">🇧🇫 +226</option>
              <option value="+227">🇳🇪 +227</option>
              <option value="+228">🇹🇬 +228</option>
              <option value="+229">🇧🇯 +229</option>
              <option value="+237">🇨🇲 +237</option>
              <option value="+241">🇬🇦 +241</option>
              <option value="+242">🇨🇬 +242</option>
              <option value="+243">🇨🇩 +243</option>
              <option value="+236">🇨🇫 +236</option>
              <option value="+234">🇳🇬 +234</option>
              <option value="+233">🇬🇭 +233</option>
              <option value="+212">🇲🇦 +212</option>
              <option value="+216">🇹🇳 +216</option>
              <option value="+213">🇩🇿 +213</option>
              <option value="+20">🇪🇬 +20</option>
              <option value="+33">🇫🇷 +33</option>
              <option value="+32">🇧🇪 +32</option>
              <option value="+41">🇨🇭 +41</option>
              <option value="+1">🇺🇸 +1</option>
            </select>
            <input type="tel" id="f-whatsapp" placeholder="07 00 00 00 00" class="dg-phone-num" autocomplete="tel">
          </div>
          <small class="dg-field-hint">Nous vous enverrons votre rapport sur WhatsApp</small>
        </div>
        <div class="dg-field">
          <label>Votre besoin principal</label>
          <textarea id="f-besoin" placeholder="Décrivez brièvement ce que vous recherchez : poste visé, compétence à acquérir, projet professionnel..." rows="3"></textarea>
        </div>
      </div>
      <button class="dg-btn" onclick="startDiagnostic()" style="margin-top:24px">
        🎯 Commencer mon diagnostic →
      </button>
      <p style="color:#475569;font-size:.78rem;margin-top:14px;text-align:center">
        🔒 Vos données sont confidentielles et ne sont jamais revendues.
      </p>
    </div>
  </div>

  <!-- STEP 1 : DOMAINE -->
  <div class="dg-step" id="step1">
    <div class="dg-card">
      <div class="dg-step-label">Étape 1 — Votre domaine</div>
      <h2>Dans quel domaine souhaitez-vous progresser ?</h2>
      <div class="dg-domains" id="domainGrid">
        <div class="dg-domain-card" data-val="Finance & Comptabilité"><div class="dg-d-ico">💰</div><div class="dg-d-name">Finance &amp; Comptabilité</div></div>
        <div class="dg-domain-card" data-val="Ressources Humaines"><div class="dg-d-ico">👥</div><div class="dg-d-name">Ressources Humaines</div></div>
        <div class="dg-domain-card" data-val="Management & Leadership"><div class="dg-d-ico">🏆</div><div class="dg-d-name">Management &amp; Leadership</div></div>
        <div class="dg-domain-card" data-val="Logistique & Supply Chain"><div class="dg-d-ico">🚚</div><div class="dg-d-name">Logistique &amp; Supply Chain</div></div>
        <div class="dg-domain-card" data-val="QHSE & Sécurité"><div class="dg-d-ico">🛡️</div><div class="dg-d-name">QHSE &amp; Sécurité</div></div>
        <div class="dg-domain-card" data-val="Numérique & IA"><div class="dg-d-ico">💻</div><div class="dg-d-name">Numérique &amp; IA</div></div>
        <div class="dg-domain-card" data-val="Commerce & Marketing"><div class="dg-d-ico">📊</div><div class="dg-d-name">Commerce &amp; Marketing</div></div>
        <div class="dg-domain-card" data-val="Droit & Juridique"><div class="dg-d-ico">⚖️</div><div class="dg-d-name">Droit &amp; Juridique</div></div>
        <div class="dg-domain-card" data-val="Autre"><div class="dg-d-ico">🎯</div><div class="dg-d-name">Autre domaine</div></div>
      </div>
      <button class="dg-btn" id="btn1" onclick="goStep(2)" disabled>Continuer →</button>
      <button class="dg-btn-back" onclick="goStep(0)">← Retour</button>
    </div>
  </div>

  <!-- STEP 2 : NIVEAU -->
  <div class="dg-step" id="step2">
    <div class="dg-card">
      <div class="dg-step-label">Étape 2 — Votre niveau</div>
      <h2>Comment évaluez-vous votre niveau actuel ?</h2>
      <div class="dg-options" id="niveauOpts">
        <div class="dg-opt" data-val="Débutant"><span class="dg-ico">🌱</span>Débutant — Moins de 2 ans d'expérience dans ce domaine<span class="dg-opt-check"></span></div>
        <div class="dg-opt" data-val="Intermédiaire"><span class="dg-ico">📈</span>Intermédiaire — Entre 2 et 5 ans, je maîtrise les bases<span class="dg-opt-check"></span></div>
        <div class="dg-opt" data-val="Confirmé"><span class="dg-ico">⭐</span>Confirmé — 5 à 10 ans, je cherche à me perfectionner<span class="dg-opt-check"></span></div>
        <div class="dg-opt" data-val="Expert"><span class="dg-ico">🏅</span>Expert — Plus de 10 ans, je vise les certifications avancées<span class="dg-opt-check"></span></div>
      </div>
      <button class="dg-btn" id="btn2" onclick="goStep(3)" disabled>Continuer →</button>
      <button class="dg-btn-back" onclick="goStep(1)">← Retour</button>
    </div>
  </div>

  <!-- STEP 3 : OBJECTIF -->
  <div class="dg-step" id="step3">
    <div class="dg-card">
      <div class="dg-step-label">Étape 3 — Votre objectif</div>
      <h2>Qu'est-ce qui vous motive à vous former ?</h2>
      <div class="dg-options" id="objectifOpts">
        <div class="dg-opt" data-val="Obtenir une promotion"><span class="dg-ico">🚀</span>Obtenir une promotion ou une augmentation<span class="dg-opt-check"></span></div>
        <div class="dg-opt" data-val="Changer de métier"><span class="dg-ico">🔄</span>Me reconvertir ou changer de secteur<span class="dg-opt-check"></span></div>
        <div class="dg-opt" data-val="Créer mon entreprise"><span class="dg-ico">💡</span>Créer ou développer mon entreprise<span class="dg-opt-check"></span></div>
        <div class="dg-opt" data-val="Monter en compétences"><span class="dg-ico">📚</span>Monter en compétences dans mon poste actuel<span class="dg-opt-check"></span></div>
        <div class="dg-opt" data-val="Exigence employeur"><span class="dg-ico">🏢</span>Répondre à une exigence de mon employeur / client<span class="dg-opt-check"></span></div>
      </div>
      <button class="dg-btn" id="btn3" onclick="goStep(4)" disabled>Continuer →</button>
      <button class="dg-btn-back" onclick="goStep(2)">← Retour</button>
    </div>
  </div>

  <!-- STEP 4 : MODE -->
  <div class="dg-step" id="step4">
    <div class="dg-card">
      <div class="dg-step-label">Étape 4 — Mode de formation</div>
      <h2>Quel format préférez-vous ?</h2>
      <div class="dg-options" id="modeOpts">
        <div class="dg-opt" data-val="Présentiel"><span class="dg-ico">🏫</span>Présentiel — En salle à Abidjan avec formateur en direct<span class="dg-opt-check"></span></div>
        <div class="dg-opt" data-val="En ligne"><span class="dg-ico">💻</span>En ligne — Depuis partout, à mon rythme<span class="dg-opt-check"></span></div>
        <div class="dg-opt" data-val="Hybride"><span class="dg-ico">🔀</span>Hybride — Un mix des deux selon le programme<span class="dg-opt-check"></span></div>
      </div>
      <button class="dg-btn" id="btn4" onclick="goStep(5)" disabled>Continuer →</button>
      <button class="dg-btn-back" onclick="goStep(3)">← Retour</button>
    </div>
  </div>

  <!-- STEP 5 : QUESTIONS TECHNIQUES (générées par JS) -->
  <div class="dg-step" id="step5">
    <div class="dg-card">
      <div class="dg-step-label">Étape 5 — Auto-évaluation</div>
      <h2 id="step5Title">Questions dans votre domaine</h2>
      <div id="step5Questions"></div>
      <button class="dg-btn" id="btn5" onclick="submitAndShowResult()" disabled>🎯 Voir mon diagnostic →</button>
      <button class="dg-btn-back" onclick="goStep(4)">← Retour</button>
    </div>
  </div>


  <!-- LOADER -->
  <div class="dg-loader" id="loader">
    <div class="dg-spinner"></div>
    <p style="color:#64748b">Analyse de votre profil en cours…</p>
  </div>

  <!-- RÉSULTATS -->
  <div class="dg-result" id="result">
    <div class="dg-card" style="margin-top:20px">
      <div class="dg-score-wrap">
        <div class="dg-score-ring" id="scoreRing">
          <div class="dg-score-num" id="scoreNum">—</div>
        </div>
        <div class="dg-score-label">Score de maîtrise</div>
        <div class="dg-profile-badge" id="profileBadge" style="margin-top:16px"></div>
        <div class="dg-profile-name" id="profileName"></div>
        <div class="dg-profile-desc" id="profileDesc"></div>
      </div>

      <div class="dg-recs-title">🎯 Vos 3 formations recommandées</div>
      <div id="recCards"></div>

      <div class="dg-cta-final">
        <h3>Prêt·e à passer à l'action ?</h3>
        <p>Nos conseillers pédagogiques peuvent vous aider à choisir la formation idéale pour votre profil.</p>
        <div class="dg-cta-btns">
          <a href="/preinscription-generale.php" class="cta-primary">✍️ Préinscription gratuite</a>
          <a href="/catalogue-formations.php" class="cta-secondary">📚 Voir tout le catalogue</a>
        </div>
      </div>
    </div>
  </div>

</div><!-- /dg-wrap -->
</main>

<script>
/* ═══════════════════════════════════════════════════
   DONNÉES
═══════════════════════════════════════════════════ */
const QUESTIONS = {
  "Finance & Comptabilité": [
    { q: "Face à un écart de caisse en fin de journée, votre réflexe est :",
      opts: ["J'attends qu'on m'explique quoi faire","Je revérifie les reçus de la journée","Je rapproche les pièces et identifie l'écart","Je procède à un rapprochement bancaire complet"],
      pts:  [1,2,3,4] },
    { q: "Comment calculez-vous le résultat net d'une entreprise ?",
      opts: ["Je ne sais pas encore","CA – Charges totales (approximativement)","Résultat d'exploitation ± éléments financiers et exceptionnels – IS","Je maîtrise les SIG et les retraitements SYSCOHADA"],
      pts:  [1,2,3,4] },
    { q: "La TVA collectée de 100 000 FCFA est enregistrée :",
      opts: ["Je ne suis pas sûr(e)","Au débit du compte ventes","Au crédit du compte TVA collectée","Je gère les déclarations mensuelles et les reports de crédit"],
      pts:  [1,2,3,4] }
  ],
  "Ressources Humaines": [
    { q: "Lors d'un recrutement, votre priorité est :",
      opts: ["Je n'ai pas encore recruté","Publier l'annonce et attendre les CV","Définir la fiche de poste, les critères et conduire des entretiens structurés","Piloter tout le processus RH : sourcing, assessment, intégration, onboarding"],
      pts:  [1,2,3,4] },
    { q: "Le calcul de la paie d'un salarié, c'est pour vous :",
      opts: ["Quelque chose que je n'ai pas encore fait","Je connais les bases (brut, net, cotisations)","Je maîtrise les rubriques : congés, heures sup, avantages en nature","Je gère le DSN, les contrôles CNPS et les déclarations fiscales"],
      pts:  [1,2,3,4] },
    { q: "Face à un conflit social dans votre entreprise, vous :",
      opts: ["Je ne sais pas comment réagir","J'applique les consignes de ma hiérarchie","Je mène une médiation et j'applique le Code du travail","Je pilote le dialogue social, IRP et procédures disciplinaires"],
      pts:  [1,2,3,4] }
  ],
  "Management & Leadership": [
    { q: "Pour motiver votre équipe, vous vous appuyez sur :",
      opts: ["Je n'ai pas encore managé d'équipe","Les primes et avantages matériels","Une combinaison de reconnaissance, autonomie et objectifs clairs","Des approches personnalisées : leadership situationnel, coaching, feedback 360°"],
      pts:  [1,2,3,4] },
    { q: "Lors d'un projet avec deadline serrée, vous :",
      opts: ["Je panique un peu","Je travaille plus longtemps pour tout finir moi-même","Je décompose en tâches, délègue et ajuste les priorités","J'applique une méthodologie (PMBOK, Agile) et gère les risques proactivement"],
      pts:  [1,2,3,4] },
    { q: "Un collaborateur sous-performant dans votre équipe, vous :",
      opts: ["Je ne sais pas comment gérer ça","J'attend que ça s'améliore ou je le signale à la RH","Je fais un entretien individuel pour comprendre et fixer des objectifs clairs","Je construis un PIP, je coach et je documente chaque étape"],
      pts:  [1,2,3,4] }
  ],
  "Logistique & Supply Chain": [
    { q: "Pour optimiser vos stocks, vous utilisez :",
      opts: ["Je ne connais pas encore les méthodes","Je commande quand le stock est bas","La méthode ABC et le réapprovisionnement à point de commande","Les KPIs supply chain : taux de service, rotation, DIO, et des outils WMS/ERP"],
      pts:  [1,2,3,4] },
    { q: "Face à un retard fournisseur impactant votre production, vous :",
      opts: ["Je préviens mon responsable et j'attends","Je contact le fournisseur pour avoir un ETA","J'active un fournisseur de substitution et j'ajuste le plan de production","Je gère les pénalités contractuelles et révise le plan de risque fournisseurs"],
      pts:  [1,2,3,4] },
    { q: "Le transport international d'une marchandise implique :",
      opts: ["Des formalités que je ne connais pas encore","Un transitaire et des documents basiques","La maîtrise des Incoterms, du dédouanement et de l'assurance cargo","Le pilotage complet : tarification, optimisation modale, traçabilité et compliance"],
      pts:  [1,2,3,4] }
  ],
  "QHSE & Sécurité": [
    { q: "Lors d'un audit ISO 9001, votre rôle est :",
      opts: ["Je ne connais pas encore la norme","Je prépare les documents demandés","Je conduis des audits internes et traite les non-conformités","Je pilote le SMQ, j'analyse les risques et je gère les revues de direction"],
      pts:  [1,2,3,4] },
    { q: "Un accident du travail survient dans votre entreprise. Vous :",
      opts: ["Je préviens les secours et attends","Je sécurise la zone et fais la déclaration","J'analyse les causes racines (arbre des causes) et mets en place des actions correctives","Je pilote l'enquête, mets à jour le DUER et assure le reporting réglementaire"],
      pts:  [1,2,3,4] },
    { q: "La démarche RSE dans votre organisation, c'est :",
      opts: ["Un concept que j'entends mais ne maîtrise pas","Planter des arbres et trier les déchets","Intégrer les critères ESG dans la stratégie et les processus métiers","Piloter le reporting extra-financier et la notation ESG"],
      pts:  [1,2,3,4] }
  ],
  "Numérique & IA": [
    { q: "Avec Excel ou un tableur, vous êtes capable de :",
      opts: ["Saisir des données et faire des sommes simples","Utiliser les formules courantes (SOMME.SI, RECHERCHEV)","Créer des tableaux croisés dynamiques, des macros simples et des dashboards","Automatiser avec VBA, Power Query, Power BI ou des outils data avancés"],
      pts:  [1,2,3,4] },
    { q: "Face à l'intelligence artificielle dans votre métier, vous :",
      opts: ["Je ne vois pas encore comment ça me concerne","J'utilise ChatGPT pour des tâches simples","J'intègre des outils IA dans mes processus et je sais prompter efficacement","Je pilote des projets IA / automatisation au niveau de mon organisation"],
      pts:  [1,2,3,4] },
    { q: "En matière de cybersécurité dans votre travail quotidien, vous :",
      opts: ["Je n'y pense pas vraiment","J'utilise des mots de passe et j'évite les liens suspects","J'applique les politiques de sécurité et je forme mon équipe aux bonnes pratiques","Je pilote la sécurité du SI : RGPD, audits, plan de continuité"],
      pts:  [1,2,3,4] }
  ],
  "Commerce & Marketing": [
    { q: "Pour prospecter de nouveaux clients, vous :",
      opts: ["Je ne sais pas encore comment m'y prendre","J'envoie des emails ou je passe des appels","J'utilise une approche multicanal : LinkedIn, SEO, webinaires, partenariats","Je pilote un funnel complet avec CRM, scoring de leads et automatisation"],
      pts:  [1,2,3,4] },
    { q: "Lors d'une négociation commerciale difficile, vous :",
      opts: ["Je baisse le prix pour conclure","J'essaie de défendre mon offre sans technique précise","J'utilise des techniques (BATNA, ancrage, concessions calculées)","Je construis des deals complexes : contrats-cadres, partenariats stratégiques"],
      pts:  [1,2,3,4] },
    { q: "Votre stratégie digitale pour votre activité, c'est :",
      opts: ["Je n'en ai pas encore","Une page Facebook et quelques posts","Un plan marketing digital : SEO, SEA, content, email et KPIs","Je pilote la performance digitale avec ROI, attribution et A/B testing"],
      pts:  [1,2,3,4] }
  ],
  "Droit & Juridique": [
    { q: "Un contrat commercial de votre entreprise, vous :",
      opts: ["Je l'envoie à un avocat sans le lire","Je lis les clauses principales","Je négocie les clauses essentielles : responsabilité, résiliation, pénalités","Je rédige, j'audite et je sécurise tout le portefeuille contractuel"],
      pts:  [1,2,3,4] },
    { q: "En matière de droit OHADA, vous connaissez :",
      opts: ["Le nom mais pas le contenu","L'acte uniforme sur les sociétés commerciales (AUSCGIE)","Plusieurs actes uniformes et leur application pratique","L'ensemble du corpus OHADA et les décisions CCJA récentes"],
      pts:  [1,2,3,4] },
    { q: "Face à un litige fournisseur, votre premier réflexe est :",
      opts: ["Appeler un avocat directement","Relire le contrat pour trouver les recours","Mettre en demeure par LRAR et engager la médiation avant le judiciaire","Analyser les risques, calculer les enjeux et choisir la stratégie optimale"],
      pts:  [1,2,3,4] }
  ],
  "Autre": [
    { q: "Dans votre domaine, vous vous positionnez comme :",
      opts: ["Je débute, j'ai beaucoup à apprendre","J'ai des bases solides mais des lacunes","Je suis opérationnel(le) et cherche à me perfectionner","Je suis expert(e) et cherche une certification reconnue"],
      pts:  [1,2,3,4] },
    { q: "La formation professionnelle, pour vous c'est :",
      opts: ["Une obligation imposée par mon employeur","Une opportunité que je n'ai pas encore saisie","Un investissement régulier dans ma carrière","Un levier stratégique que je pilote activement"],
      pts:  [1,2,3,4] },
    { q: "Vos objectifs professionnels à 2 ans :",
      opts: ["Stabiliser mon poste actuel","Obtenir une responsabilité supplémentaire","Changer de poste ou de secteur avec une certification","Créer ou diriger ma propre structure"],
      pts:  [1,2,3,4] }
  ]
};

const RECOMMENDATIONS = {
  "Finance & Comptabilité": {
    low:  [{n:"Initiation à la comptabilité générale",d:"Maîtrisez les fondamentaux : plan comptable, journaux, balance, bilan.",tags:["Débutant","3 jours"],cat:"Finance"},
           {n:"Fiscalité pratique pour entreprises",d:"TVA, IS, retenues à la source : comprendre et appliquer la fiscalité ivoirienne.",tags:["Pratique","2 jours"],cat:"Finance"},
           {n:"Gestion administrative et financière",d:"Piloter la trésorerie, les factures et les tableaux de bord financiers.",tags:["Gestion","3 jours"],cat:"Finance"}],
    mid:  [{n:"Comptabilité SYSCOHADA révisé",d:"Maîtrisez le référentiel comptable de l'espace OHADA en profondeur.",tags:["Certifiant","4 jours"],cat:"Finance"},
           {n:"Analyse et diagnostic financier",d:"Lire un bilan, calculer les ratios clés et formuler des recommandations.",tags:["Analyse","3 jours"],cat:"Finance"},
           {n:"Contrôle de gestion et reporting",d:"Construire des tableaux de bord, piloter les budgets et les écarts.",tags:["Management","4 jours"],cat:"Finance"}],
    high: [{n:"IFRS pour dirigeants financiers",d:"Normes internationales et réconciliation IFRS / SYSCOHADA.",tags:["Expert","3 jours"],cat:"Finance"},
           {n:"Audit interne et contrôle interne",d:"Méthodologie d'audit, cartographie des risques, rapport d'audit.",tags:["Certifiant","5 jours"],cat:"Finance"},
           {n:"Montage de dossiers de financement",d:"Business plan, analyse bancaire, levée de fonds et investisseurs.",tags:["Avancé","3 jours"],cat:"Finance"}]
  },
  "Ressources Humaines": {
    low:  [{n:"Fondamentaux des RH",d:"Recrutement, contrats, paie de base et gestion administrative du personnel.",tags:["Débutant","3 jours"],cat:"RH"},
           {n:"Droit du travail en Côte d'Ivoire",d:"Code du travail ivoirien, contrats, licenciement, CNPS.",tags:["Juridique","2 jours"],cat:"RH"},
           {n:"Communication et relations professionnelles",d:"Développez vos soft skills pour mieux interagir en milieu professionnel.",tags:["Soft skills","2 jours"],cat:"RH"}],
    mid:  [{n:"Gestion de la paie et déclarations sociales",d:"Maîtriser le bulletin de paie, la CNPS et les déclarations fiscales.",tags:["Pratique","3 jours"],cat:"RH"},
           {n:"Recrutement et gestion des talents",d:"De la définition du besoin à l'intégration : processus et outils.",tags:["Talent","3 jours"],cat:"RH"},
           {n:"Formation professionnelle et ingénierie pédagogique",d:"Concevoir et piloter un plan de formation efficace.",tags:["Ingénierie","3 jours"],cat:"RH"}],
    high: [{n:"DRH stratégique et pilotage social",d:"Tableau de bord RH, dialogue social, GPEC et transformation RH.",tags:["Certifiant","5 jours"],cat:"RH"},
           {n:"Gestion des conflits et médiation sociale",d:"Prévenir et résoudre les conflits en entreprise.",tags:["Expert","3 jours"],cat:"RH"},
           {n:"Management interculturel en Afrique",d:"Manager des équipes multiculturelles dans l'espace OHADA.",tags:["Leadership","3 jours"],cat:"RH"}]
  },
  "Management & Leadership": {
    low:  [{n:"Management d'équipe pour nouveaux managers",d:"Les fondamentaux pour passer de collaborateur à manager.",tags:["Débutant","3 jours"],cat:"Management"},
           {n:"Communication professionnelle",d:"Prendre la parole, rédiger des rapports, animer des réunions efficaces.",tags:["Soft skills","2 jours"],cat:"Management"},
           {n:"Organisation et gestion du temps",d:"Méthodes et outils pour gagner en productivité.",tags:["Efficacité","2 jours"],cat:"Management"}],
    mid:  [{n:"Leadership situationnel",d:"Adapter votre style de management au profil de chaque collaborateur.",tags:["Leadership","3 jours"],cat:"Management"},
           {n:"Gestion de projet (PMP/PMI)",d:"Planifier, exécuter et clôturer des projets avec succès.",tags:["Certifiant","4 jours"],cat:"Management"},
           {n:"Prise de décision et résolution de problèmes",d:"Méthodes structurées pour décider en situation complexe.",tags:["Stratégie","2 jours"],cat:"Management"}],
    high: [{n:"Management stratégique et gouvernance",d:"Définir et déployer une stratégie d'entreprise en Afrique.",tags:["Expert","4 jours"],cat:"Management"},
           {n:"Coaching et développement des talents",d:"Devenir un manager-coach et développer votre équipe.",tags:["Coaching","3 jours"],cat:"Management"},
           {n:"Transformation digitale des organisations",d:"Piloter le changement et la digitalisation de votre structure.",tags:["Digital","3 jours"],cat:"Management"}]
  },
  "Logistique & Supply Chain": {
    low:  [{n:"Introduction à la logistique et supply chain",d:"Comprendre les flux physiques, d'information et financiers.",tags:["Débutant","3 jours"],cat:"Logistique"},
           {n:"Gestion des stocks et approvisionnements",d:"Méthodes ABC, réapprovisionnement, indicateurs de stock.",tags:["Pratique","3 jours"],cat:"Logistique"},
           {n:"Transport et douane en Afrique",d:"Incoterms, fret, dédouanement et réglementation dans l'UEMOA.",tags:["Pratique","2 jours"],cat:"Logistique"}],
    mid:  [{n:"Achats et négociation fournisseurs",d:"Stratégie achats, appels d'offres, négociation et évaluation.",tags:["Certifiant","3 jours"],cat:"Logistique"},
           {n:"Gestion d'entrepôt et optimisation WMS",d:"Organisation, picking, inventaires et outils de gestion.",tags:["Opérationnel","3 jours"],cat:"Logistique"},
           {n:"Logistique humanitaire",d:"Spécificités de la logistique dans les projets de développement et ONG.",tags:["ONG","3 jours"],cat:"Logistique"}],
    high: [{n:"Supply Chain Management avancé",d:"Pilotage global de la chaîne, risk management et KPIs avancés.",tags:["Expert","4 jours"],cat:"Logistique"},
           {n:"Lean Supply Chain",d:"Éliminer les gaspillages et optimiser les flux avec le Lean.",tags:["Performance","3 jours"],cat:"Logistique"},
           {n:"Commerce international et Incoterms 2020",d:"Maîtriser les règles du commerce international.",tags:["Certifiant","3 jours"],cat:"Logistique"}]
  },
  "QHSE & Sécurité": {
    low:  [{n:"Sensibilisation ISO 9001 — Qualité",d:"Comprendre les principes de la qualité et la démarche ISO 9001.",tags:["Sensibilisation","2 jours"],cat:"QHSE"},
           {n:"Sécurité au travail et prévention des risques",d:"Identifier les risques, appliquer les mesures préventives.",tags:["Obligatoire","2 jours"],cat:"QHSE"},
           {n:"Environnement et développement durable",d:"RSE, empreinte carbone et réglementations environnementales.",tags:["RSE","2 jours"],cat:"QHSE"}],
    mid:  [{n:"Auditeur interne ISO 9001",d:"Méthodologie d'audit, rédaction de rapports et traitement des NC.",tags:["Certifiant","3 jours"],cat:"QHSE"},
           {n:"Analyse des risques professionnels (DUER)",d:"Construire et mettre à jour le document unique d'évaluation.",tags:["Obligatoire","2 jours"],cat:"QHSE"},
           {n:"Management de la qualité SYSCOHADA",d:"Adapter les systèmes qualité au contexte des entreprises africaines.",tags:["Pratique","3 jours"],cat:"QHSE"}],
    high: [{n:"Responsable QHSE — Formation complète",d:"Pilotage intégré QSE, audits, indicateurs et reporting.",tags:["Expert","5 jours"],cat:"QHSE"},
           {n:"ISO 14001 — Système de management environnemental",d:"Mettre en place et auditer un SME conforme à la norme.",tags:["Certifiant","3 jours"],cat:"QHSE"},
           {n:"Management des risques ISO 31000",d:"Cartographie, évaluation et traitement des risques d'entreprise.",tags:["Expert","3 jours"],cat:"QHSE"}]
  },
  "Numérique & IA": {
    low:  [{n:"Excel de A à Z — Débutant à avancé",d:"Maîtriser Excel : formules, TCD, graphiques et mise en forme.",tags:["Pratique","3 jours"],cat:"Numérique"},
           {n:"Initiation à l'intelligence artificielle",d:"Comprendre l'IA, ses usages métiers et les outils disponibles.",tags:["IA","2 jours"],cat:"Numérique"},
           {n:"Bureautique et outils collaboratifs",d:"Word, Excel, PowerPoint, Google Workspace, Teams.",tags:["Débutant","3 jours"],cat:"Numérique"}],
    mid:  [{n:"Intelligence artificielle pour managers",d:"ChatGPT, Copilot, automatisation : intégrer l'IA dans votre travail.",tags:["IA","3 jours"],cat:"Numérique"},
           {n:"Power BI — Tableaux de bord et data visualisation",d:"Construire des dashboards interactifs pour piloter votre activité.",tags:["Data","3 jours"],cat:"Numérique"},
           {n:"SAP pour utilisateurs non-techniciens",d:"Naviguer dans SAP, saisir et extraire des données efficacement.",tags:["ERP","3 jours"],cat:"Numérique"}],
    high: [{n:"Transformation digitale des organisations",d:"Piloter la digitalisation : outils, méthodes, conduite du changement.",tags:["Expert","4 jours"],cat:"Numérique"},
           {n:"Cybersécurité pour managers",d:"Protéger votre organisation : politique sécurité, RGPD, incidents.",tags:["Sécurité","2 jours"],cat:"Numérique"},
           {n:"Data Analytics et prise de décision",d:"Exploiter la donnée pour décider : méthodes et outils pratiques.",tags:["Data","3 jours"],cat:"Numérique"}]
  },
  "Commerce & Marketing": {
    low:  [{n:"Techniques de vente et négociation",d:"Maîtriser le cycle de vente : prospection, argumentation, closing.",tags:["Pratique","3 jours"],cat:"Commerce"},
           {n:"Marketing digital pour débutants",d:"Réseaux sociaux, emailing, SEO : les bases du marketing digital.",tags:["Digital","3 jours"],cat:"Commerce"},
           {n:"Service client et relation client",d:"Accueillir, fidéliser et gérer les réclamations avec excellence.",tags:["Service","2 jours"],cat:"Commerce"}],
    mid:  [{n:"Stratégie commerciale et développement business",d:"Construire un plan commercial, segmenter et conquérir de nouveaux marchés.",tags:["Stratégie","3 jours"],cat:"Commerce"},
           {n:"Marketing digital avancé",d:"SEO, SEA, inbound, automation et analytics pour booster votre visibilité.",tags:["Digital","4 jours"],cat:"Commerce"},
           {n:"Gestion de la relation client (CRM)",d:"Déployer et exploiter un CRM pour fidéliser et piloter vos ventes.",tags:["CRM","3 jours"],cat:"Commerce"}],
    high: [{n:"Management commercial et pilotage des équipes de vente",d:"Recruter, animer et piloter une force de vente performante.",tags:["Management","4 jours"],cat:"Commerce"},
           {n:"Marketing stratégique en Afrique",d:"Adapter les stratégies marketing aux réalités du marché africain.",tags:["Afrique","3 jours"],cat:"Commerce"},
           {n:"E-commerce et marketplace en Afrique",d:"Lancer et développer une activité e-commerce en Afrique.",tags:["Digital","3 jours"],cat:"Commerce"}]
  },
  "Droit & Juridique": {
    low:  [{n:"Droit OHADA pour non-juristes",d:"Comprendre les actes uniformes essentiels pour les opérateurs économiques.",tags:["Pratique","3 jours"],cat:"Droit"},
           {n:"Droit du travail ivoirien",d:"Code du travail, contrats, rupture, règlement intérieur.",tags:["Obligatoire","2 jours"],cat:"Droit"},
           {n:"Rédaction de contrats commerciaux",d:"Clauses essentielles, pièges à éviter et modèles pratiques.",tags:["Pratique","2 jours"],cat:"Droit"}],
    mid:  [{n:"Droit des affaires et des sociétés OHADA",d:"Création, gouvernance, transformation et dissolution des sociétés.",tags:["Certifiant","3 jours"],cat:"Droit"},
           {n:"Gestion des litiges commerciaux",d:"Négociation, médiation, arbitrage CCJA et procédures judiciaires.",tags:["Pratique","2 jours"],cat:"Droit"},
           {n:"Fiscalité des entreprises en Afrique",d:"IS, TVA, retenues et optimisation fiscale dans l'UEMOA.",tags:["Fiscalité","3 jours"],cat:"Droit"}],
    high: [{n:"Droit des contrats internationaux",d:"Vienne, Incoterms, arbitrage international et choix de la loi.",tags:["Expert","3 jours"],cat:"Droit"},
           {n:"Compliance et gouvernance d'entreprise",d:"Conformité réglementaire, LBC/FT, éthique des affaires.",tags:["Compliance","3 jours"],cat:"Droit"},
           {n:"Arbitrage OHADA et règlement des différends",d:"Procédures arbitrales, sentence et exécution dans l'espace OHADA.",tags:["Expert","3 jours"],cat:"Droit"}]
  },
  "Autre": {
    low:  [{n:"Communication professionnelle",d:"Développez vos compétences relationnelles pour réussir en entreprise.",tags:["Soft skills","2 jours"],cat:"Management"},
           {n:"Gestion du temps et organisation",d:"Priorisez, planifiez et gagnez en efficacité au quotidien.",tags:["Efficacité","2 jours"],cat:"Management"},
           {n:"Entrepreneuriat en Afrique",d:"De l'idée à l'entreprise : business plan, financement, lancement.",tags:["Entrepreneuriat","3 jours"],cat:"Management"}],
    mid:  [{n:"Management de projet",d:"Planifier, exécuter et clôturer des projets avec les méthodes PMI.",tags:["Certifiant","4 jours"],cat:"Management"},
           {n:"Leadership et intelligence émotionnelle",d:"Développez votre leadership et gérez vos émotions en situation difficile.",tags:["Leadership","3 jours"],cat:"Management"},
           {n:"Négociation et influence",d:"Techniques de négociation pour convaincre et obtenir ce que vous voulez.",tags:["Soft skills","2 jours"],cat:"Management"}],
    high: [{n:"Management stratégique",d:"Vision, stratégie et exécution : les compétences du dirigeant.",tags:["Expert","4 jours"],cat:"Management"},
           {n:"Transformation et conduite du changement",d:"Piloter les transformations organisationnelles avec succès.",tags:["Certifiant","3 jours"],cat:"Management"},
           {n:"Coaching professionnel",d:"Développez vos équipes grâce aux techniques de coaching certifiées.",tags:["Coaching","3 jours"],cat:"Management"}]
  }
};

/* ═══════════════════════════════════════════════════
   STATE
═══════════════════════════════════════════════════ */
const state = {
  nom:'', prenom:'', email:'', indicatif:'+225', whatsapp:'', besoin:'',
  domain:'', niveau:'', objectif:'', mode:'', score:0, qScores:[]
};

/* ═══════════════════════════════════════════════════
   STEP 0 : VALIDATION FORMULAIRE
═══════════════════════════════════════════════════ */
function startDiagnostic() {
  const nom      = document.getElementById('f-nom').value.trim();
  const prenom   = document.getElementById('f-prenom').value.trim();
  const email    = document.getElementById('f-email').value.trim();
  const indicatif= document.getElementById('f-indicatif').value;
  const whatsapp = document.getElementById('f-whatsapp').value.trim();
  const besoin   = document.getElementById('f-besoin').value.trim();

  if (!nom)     { showToastError('Merci de renseigner votre nom.'); document.getElementById('f-nom').focus(); return; }
  if (!prenom)  { showToastError('Merci de renseigner votre prénom.'); document.getElementById('f-prenom').focus(); return; }
  if (!email)   { showToastError('Merci de renseigner votre adresse e-mail.'); document.getElementById('f-email').focus(); return; }
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { showToastError('Adresse e-mail invalide.'); document.getElementById('f-email').focus(); return; }
  if (!whatsapp){ showToastError('Merci de renseigner votre numéro WhatsApp.'); document.getElementById('f-whatsapp').focus(); return; }

  state.nom = nom; state.prenom = prenom; state.email = email;
  state.indicatif = indicatif; state.whatsapp = whatsapp; state.besoin = besoin;

  document.getElementById('progressWrap').style.display = 'block';
  goStep(1);
}

/* ═══════════════════════════════════════════════════
   NAVIGATION
═══════════════════════════════════════════════════ */
function goStep(n) {
  document.querySelectorAll('.dg-step').forEach(s => s.classList.remove('active'));
  const el = document.getElementById('step'+n);
  if (el) { el.classList.add('active'); }
  if (n === 0) {
    document.getElementById('progressWrap').style.display = 'none';
    window.scrollTo({top: 0, behavior:'smooth'});
  } else {
    updateProgress(n, 5);
    window.scrollTo({top: document.querySelector('.dg-progress-wrap').offsetTop - 80, behavior:'smooth'});
  }
}

function updateProgress(current, total) {
  const pct = Math.round((current-1)/total*100);
  document.getElementById('progressFill').style.width = pct+'%';
  document.getElementById('progressLabel').textContent = 'Étape '+current+' sur '+total;
}

/* ═══════════════════════════════════════════════════
   STEP 1 : DOMAINE
═══════════════════════════════════════════════════ */
document.querySelectorAll('.dg-domain-card').forEach(card => {
  card.addEventListener('click', () => {
    document.querySelectorAll('.dg-domain-card').forEach(c => c.classList.remove('selected'));
    card.classList.add('selected');
    state.domain = card.dataset.val;
    document.getElementById('btn1').disabled = false;
    buildStep5();
  });
});

/* ═══════════════════════════════════════════════════
   STEP 2-4 : OPTIONS SIMPLES
═══════════════════════════════════════════════════ */
function setupSingleChoice(containerId, btnId, stateKey) {
  const container = document.getElementById(containerId);
  if (!container) return;
  container.querySelectorAll('.dg-opt').forEach(opt => {
    opt.addEventListener('click', () => {
      container.querySelectorAll('.dg-opt').forEach(o => o.classList.remove('selected'));
      opt.classList.add('selected');
      state[stateKey] = opt.dataset.val;
      document.getElementById(btnId).disabled = false;
    });
  });
}
setupSingleChoice('niveauOpts',   'btn2', 'niveau');
setupSingleChoice('objectifOpts', 'btn3', 'objectif');
setupSingleChoice('modeOpts',     'btn4', 'mode');

/* ═══════════════════════════════════════════════════
   STEP 5 : QUESTIONS TECHNIQUES
═══════════════════════════════════════════════════ */
function buildStep5() {
  const qs = QUESTIONS[state.domain] || QUESTIONS['Autre'];
  document.getElementById('step5Title').textContent = 'Auto-évaluation — '+state.domain;
  const container = document.getElementById('step5Questions');
  const letters = ['A','B','C','D'];
  state.qScores = new Array(qs.length).fill(0);

  container.innerHTML = qs.map((q,qi) => `
    <div class="dg-q">
      <div class="dg-q-label">${qi+1}. ${q.q}</div>
      <div class="dg-q-opts" id="qopts${qi}">
        ${q.opts.map((o,oi) => `
          <div class="dg-q-opt" data-qi="${qi}" data-pts="${q.pts[oi]}" onclick="selectQOpt(this,${qi})">
            <span class="dg-ltr">${letters[oi]}</span>${o}
          </div>`).join('')}
      </div>
    </div>`).join('');

  checkStep5Complete(qs.length);
}

function selectQOpt(el, qi) {
  document.querySelectorAll(`#qopts${qi} .dg-q-opt`).forEach(o => o.classList.remove('selected'));
  el.classList.add('selected');
  state.qScores[qi] = parseInt(el.dataset.pts);
  const qs = QUESTIONS[state.domain] || QUESTIONS['Autre'];
  checkStep5Complete(qs.length);
}

function checkStep5Complete(total) {
  const answered = state.qScores.filter(s => s > 0).length;
  document.getElementById('btn5').disabled = answered < total;
  if (answered === total) {
    state.score = state.qScores.reduce((a,b) => a+b, 0);
  }
}

/* ═══════════════════════════════════════════════════
   SUBMIT + RÉSULTATS
═══════════════════════════════════════════════════ */
function showToastError(msg) {
  let t = document.getElementById('dg-toast');
  if (!t) {
    t = document.createElement('div');
    t.id = 'dg-toast';
    t.style.cssText = 'position:fixed;bottom:32px;left:50%;transform:translateX(-50%);background:#dc2626;color:#fff;padding:14px 28px;border-radius:12px;font-size:.93rem;font-weight:600;z-index:9999;box-shadow:0 8px 24px rgba(0,0,0,.4);transition:opacity .3s';
    document.body.appendChild(t);
  }
  t.textContent = msg;
  t.style.opacity = '1';
  clearTimeout(t._hide);
  t._hide = setTimeout(() => { t.style.opacity = '0'; }, 3500);
}

async function submitAndShowResult() {
  document.querySelectorAll('.dg-step').forEach(s => s.classList.remove('active'));
  document.getElementById('progressWrap').style.display = 'none';
  document.getElementById('loader').style.display = 'block';

  const fd = new FormData();
  fd.append('action','save_lead');
  fd.append('nom', state.nom); fd.append('prenom', state.prenom); fd.append('email', state.email);
  fd.append('indicatif', state.indicatif); fd.append('whatsapp', state.whatsapp); fd.append('besoin', state.besoin);
  fd.append('domain', state.domain); fd.append('niveau', state.niveau);
  fd.append('objectif', state.objectif); fd.append('mode', state.mode);
  fd.append('score', state.score);
  try { await fetch(location.href, {method:'POST', body:fd}); } catch(e){}

  setTimeout(() => {
    document.getElementById('loader').style.display = 'none';
    showResult(state.prenom);
  }, 1800);
}

function showResult(prenom) {
  // Score sur 12 → pourcentage
  const maxScore = 12;
  const pct = Math.round(state.score / maxScore * 100);

  // Niveau calculé
  let tier, profileName, profileDesc, badgeColor;
  if (pct <= 35) {
    tier = 'low';
    profileName = 'Profil Fondamental';
    profileDesc = `${prenom}, vous avez de solides bases à consolider. Des formations pratiques et accessibles vous permettront de monter rapidement en compétences.`;
    badgeColor  = 'background:rgba(245,166,35,.15);color:#f5a623;border:1px solid rgba(245,166,35,.3)';
  } else if (pct <= 70) {
    tier = 'mid';
    profileName = 'Profil Opérationnel';
    profileDesc = `${prenom}, vous avez un bon niveau opérationnel. Des formations certifiantes vous permettront de vous démarquer et d'accélérer votre carrière.`;
    badgeColor  = 'background:rgba(26,63,208,.18);color:#93c5fd;border:1px solid rgba(26,63,208,.3)';
  } else {
    tier = 'high';
    profileName = 'Profil Expert';
    profileDesc = `${prenom}, vous avez un excellent niveau. Des formations avancées et des certifications internationales vont confirmer et valoriser votre expertise.`;
    badgeColor  = 'background:rgba(16,185,129,.15);color:#6ee7b7;border:1px solid rgba(16,185,129,.3)';
  }

  // Affiche score
  document.getElementById('scoreRing').style.setProperty('--pct', pct+'%');
  document.getElementById('scoreNum').textContent = pct+'%';
  document.getElementById('profileBadge').setAttribute('style', badgeColor);
  document.getElementById('profileBadge').textContent = state.domain;
  document.getElementById('profileName').textContent = profileName;
  document.getElementById('profileDesc').textContent = profileDesc;

  // Recommendations
  const recs = (RECOMMENDATIONS[state.domain] || RECOMMENDATIONS['Autre'])[tier];
  const catParam = encodeURIComponent(recs[0]?.cat || state.domain);
  document.getElementById('recCards').innerHTML = recs.map((r,i) => `
    <div class="dg-rec-card">
      <div class="dg-rec-num">${i+1}</div>
      <div class="dg-rec-body">
        <div class="dg-rec-name">${r.n}</div>
        <div class="dg-rec-desc">${r.d}</div>
        <div class="dg-rec-tags">
          ${r.tags.map(t => `<span class="dg-tag">${t}</span>`).join('')}
          <span class="dg-tag blue">${state.mode || 'En ligne / Présentiel'}</span>
        </div>
        <a class="dg-rec-link" href="/catalogue-formations.php?q=${encodeURIComponent(r.n)}">
          Voir cette formation →
        </a>
      </div>
    </div>`).join('');

  document.getElementById('result').classList.add('active');
  window.scrollTo({top:0, behavior:'smooth'});
}
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>
