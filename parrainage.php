<?php
declare(strict_types=1);
/* =========================================================
   IBIG EDUFORM — Parrainage (parrain & filleul)
   - Le parrain génère un code + un lien à partager.
   - Le parrain suit lui-même ses parrainages (code + contact).
========================================================= */
require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/core/csrf.php';
require_once __DIR__ . '/core/referral.php';

$pdo = Database::connect();

$result   = null;
$error    = '';
$track    = null;
$trackErr = '';

function par_contact_match(string $stored, string $input): bool {
    $s = strtolower(trim($stored)); $i = strtolower(trim($input));
    if ($s !== '' && $s === $i) return true;
    $ds = preg_replace('/\D/', '', $s); $di = preg_replace('/\D/', '', $i);
    return $ds !== '' && strlen($ds) >= 8 && $ds === $di;
}

function par_load_track(PDO $pdo, array $parrain): array {
    $code = (string)$parrain['code'];
    $rows = [];
    try {
        $st = $pdo->prepare("SELECT filleul_nom, formation_titre, remise, montant_net, statut, created_at
                             FROM parrainage_usages WHERE code = ? ORDER BY created_at DESC");
        $st->execute([$code]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {}
    $nb = count($rows); $hon = 0; $att = 0;
    foreach ($rows as $r) {
        if (($r['statut'] ?? '') === 'paye') $hon++;
        elseif (($r['statut'] ?? '') === 'en_attente') $att++;
    }
    return ['parrain' => $parrain, 'code' => $code, 'rows' => $rows,
            'nb' => $nb, 'honored' => $hon, 'pending' => $att];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $do = (string)($_POST['do'] ?? 'create');

    if ($do === 'track') {
        $code    = strtoupper(trim((string)($_POST['code'] ?? '')));
        $contact = trim((string)($_POST['contact_track'] ?? ''));
        if ($code === '' || $contact === '') {
            $trackErr = 'Indiquez votre code et le contact utilisé lors de sa création.';
        } else {
            $parrain = referral_lookup($pdo, $code);
            if (!$parrain) {
                $trackErr = 'Ce code de parrainage est introuvable.';
            } elseif (!par_contact_match((string)($parrain['parrain_contact'] ?? ''), $contact)) {
                $trackErr = 'Le contact ne correspond pas à ce code. Saisissez le numéro / l\'email utilisé à la création.';
            } else {
                $track = par_load_track($pdo, $parrain);
            }
        }
    } else {
        $nom     = trim((string)($_POST['nom'] ?? ''));
        $contact = trim((string)($_POST['contact'] ?? ''));
        $isEmail = (bool)filter_var($contact, FILTER_VALIDATE_EMAIL);
        $digits  = preg_replace('/\D/', '', $contact);
        if ($nom === '' || $contact === '') {
            $error = 'Veuillez indiquer votre nom et votre contact.';
        } elseif (!$isEmail && strlen((string)$digits) < 8) {
            $error = 'Contact invalide : indiquez un email ou un numéro WhatsApp valide.';
        } else {
            $result = referral_create($pdo, $nom, $contact);
            if (!$result) { $error = "Impossible de générer le code pour l'instant. Réessayez."; }
            else {
                $p = referral_lookup($pdo, $result['code']);
                if ($p) { $track = par_load_track($pdo, $p); }
            }
        }
    }
}

$base    = defined('APP_URL') ? rtrim(APP_URL, '/') : ('https://' . (string)($_SERVER['HTTP_HOST'] ?? 'ibig-eduform.com'));
$pct     = referral_percent();
$fcfa    = fn($n) => number_format((int)$n, 0, ',', ' ') . ' FCFA';
$stLabel = ['en_attente' => 'En attente', 'paye' => 'Honorée', 'annule' => 'Annulée'];
$stClass = ['en_attente' => 'wait', 'paye' => 'ok', 'annule' => 'annule'];

$pageTitle = 'Programme parrainage – Gagnez ' . $pct . '% – IBIG EDUFORM';
$ogDesc    = 'Recommandez IBIG EDUFORM à vos proches et gagnez ' . $pct . '% de remise sur votre prochaine formation. Votre filleul en profite aussi. Sans limite.';

require __DIR__ . '/partials/header.php';
?>
<style>
/* ── VARIABLES ──────────────────────── */
:root{
  --pr-blue:#0a1733;
  --pr-accent:#1f3fe0;
  --pr-orange:#f97316;
  --pr-gold:#f59e0b;
  --pr-green:#16a34a;
  --pr-border:#e3ebf6;
  --pr-light:#f4f7fc;
}

/* ── HERO ────────────────────────────── */
.pr-hero{
  background:linear-gradient(135deg,#06102b 0%,#1a0a35 40%,#0f2a6e 100%);
  color:#fff;padding:80px 20px 90px;text-align:center;position:relative;overflow:hidden
}
.pr-hero::before{
  content:'';position:absolute;left:-120px;top:-120px;
  width:420px;height:420px;border-radius:50%;
  background:radial-gradient(circle,rgba(245,158,11,.18) 0%,transparent 70%);
  pointer-events:none
}
.pr-hero::after{
  content:'';position:absolute;right:-80px;bottom:-80px;
  width:320px;height:320px;border-radius:50%;
  background:radial-gradient(circle,rgba(31,63,224,.2) 0%,transparent 70%);
  pointer-events:none
}
.pr-hero-badge{
  display:inline-flex;align-items:center;gap:8px;
  background:rgba(245,158,11,.15);border:1px solid rgba(245,158,11,.35);
  border-radius:999px;padding:7px 18px;font-size:13px;font-weight:700;
  color:#fcd34d;margin-bottom:24px;letter-spacing:.02em
}
.pr-hero h1{
  font-size:clamp(2.1rem,5.5vw,3.5rem);font-weight:900;
  margin:0 0 6px;line-height:1.05;position:relative;z-index:1
}
.pr-hero .big-pct{
  font-size:clamp(4rem,12vw,8rem);font-weight:900;line-height:1;
  background:linear-gradient(135deg,#fcd34d,#f97316);
  -webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;
  display:block;margin:4px 0
}
.pr-hero .sub{
  max-width:640px;margin:16px auto 36px;
  color:#c7d2fe;font-size:clamp(15px,2vw,17px);line-height:1.65;position:relative;z-index:1
}
.pr-hero-btns{
  display:flex;flex-wrap:wrap;gap:12px;justify-content:center;
  position:relative;z-index:1;margin-bottom:48px
}
.pr-hero-btns a,.pr-hero-btn{
  display:inline-flex;align-items:center;gap:8px;
  padding:14px 30px;border-radius:12px;font-weight:800;font-size:15px;text-decoration:none;cursor:pointer;border:none;font-family:inherit
}
.btn-gold{background:linear-gradient(135deg,#f59e0b,#f97316);color:#0a1733}
.btn-gold:hover{filter:brightness(1.08)}
.btn-ghost-w{background:rgba(255,255,255,.1);color:#fff;border:1px solid rgba(255,255,255,.25)}
.btn-ghost-w:hover{background:rgba(255,255,255,.18)}

/* ── DOUBLE BÉNÉFICE ─────────────────── */
.pr-double{
  display:flex;flex-wrap:wrap;gap:0;
  background:#fff;border:1px solid var(--pr-border);
  border-radius:20px;max-width:700px;margin:0 auto;
  box-shadow:0 8px 30px rgba(10,23,51,.08);overflow:hidden
}
.pr-double-half{flex:1;min-width:200px;padding:24px 28px;text-align:center}
.pr-double-half + .pr-double-half{border-left:1px solid var(--pr-border)}
.pr-double-half .who{font-size:12px;font-weight:900;letter-spacing:.08em;text-transform:uppercase;color:#64748b;margin-bottom:8px}
.pr-double-half .pct-big{font-size:2.8rem;font-weight:900;line-height:1}
.pr-double-half .desc{font-size:13.5px;color:#64748b;margin-top:6px;line-height:1.5}

/* ── WRAP ────────────────────────────── */
.pr-wrap{max-width:1060px;margin:0 auto;padding:0 20px 80px}

/* ── SECTION TITRES ──────────────────── */
.pr-section{padding:60px 0 0}
.pr-label{display:inline-block;font-size:11px;font-weight:900;letter-spacing:.1em;text-transform:uppercase;color:var(--pr-accent);margin-bottom:10px}
.pr-h2{font-size:clamp(1.5rem,3vw,2rem);font-weight:900;color:var(--pr-blue);margin:0 0 12px;line-height:1.15}
.pr-lead{font-size:15.5px;color:#475569;line-height:1.65;max-width:680px;margin:0 0 36px}

/* ── ÉTAPES ──────────────────────────── */
.pr-steps{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:20px;margin-bottom:36px}
.pr-step{
  background:#fff;border-radius:20px;padding:26px 22px;
  border:1px solid var(--pr-border);
  box-shadow:0 6px 20px rgba(10,23,51,.05);
  position:relative;transition:.2s
}
.pr-step:hover{box-shadow:0 14px 36px rgba(10,23,51,.1);transform:translateY(-2px)}
.pr-step-num{
  width:44px;height:44px;border-radius:14px;
  display:flex;align-items:center;justify-content:center;
  font-size:18px;font-weight:900;color:#fff;margin-bottom:14px
}
.pr-step h3{font-size:1rem;font-weight:800;color:var(--pr-blue);margin:0 0 7px}
.pr-step p{font-size:13.5px;color:#64748b;line-height:1.6;margin:0}
.pr-step-arrow{
  display:none;position:absolute;right:-14px;top:50%;transform:translateY(-50%);
  font-size:20px;color:#c7d2fe;z-index:1
}
@media(min-width:700px){.pr-step:not(:last-child) .pr-step-arrow{display:block}}

/* ── AVANTAGES ───────────────────────── */
.pr-avantages{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:18px;margin-bottom:16px}
.pr-av{
  display:flex;gap:14px;align-items:flex-start;
  background:#fff;border:1px solid var(--pr-border);border-radius:16px;padding:20px
}
.pr-av .ico{font-size:26px;flex:none}
.pr-av h4{font-size:14.5px;font-weight:800;color:var(--pr-blue);margin:0 0 4px}
.pr-av p{font-size:13px;color:#64748b;line-height:1.55;margin:0}

/* ── CALCULATEUR ─────────────────────── */
.pr-calc{
  background:linear-gradient(135deg,#0a1733,#1f3fe0);
  border-radius:22px;padding:36px 32px;color:#fff;margin:36px 0
}
.pr-calc h3{font-size:1.2rem;font-weight:900;margin:0 0 6px}
.pr-calc .sub{color:#c7d2fe;font-size:14px;margin:0 0 24px}
.pr-calc-row{display:flex;flex-wrap:wrap;gap:16px;align-items:flex-end}
.pr-calc-field{flex:1;min-width:160px}
.pr-calc-field label{display:block;font-size:12px;font-weight:700;color:#c7d2fe;margin-bottom:6px;text-transform:uppercase;letter-spacing:.05em}
.pr-calc-field input[type=range]{width:100%;accent-color:#f59e0b}
.pr-calc-field .val{font-size:1.8rem;font-weight:900;color:#fcd34d;margin-top:4px}
.pr-calc-result{
  background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.2);
  border-radius:14px;padding:18px 22px;min-width:200px;text-align:center
}
.pr-calc-result .label{font-size:12px;color:#c7d2fe;text-transform:uppercase;letter-spacing:.06em}
.pr-calc-result .amount{font-size:2rem;font-weight:900;color:#fcd34d;margin:4px 0}
.pr-calc-result .note{font-size:12px;color:#93c5fd}

/* ── MAIN GRID ───────────────────────── */
.pr-main-grid{display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-top:36px}
@media(max-width:760px){.pr-main-grid{grid-template-columns:1fr}}

/* ── CARDS ───────────────────────────── */
.pr-card{
  background:#fff;border:1px solid var(--pr-border);border-radius:20px;
  padding:32px;box-shadow:0 8px 28px rgba(10,23,51,.06)
}
.pr-card.highlight{
  background:linear-gradient(135deg,#0a1733,#1a2d6e);color:#fff;
  border-color:transparent
}
.pr-card.highlight label,.pr-card.highlight .pr-muted{color:#c7d2fe}
.pr-card h2{margin:0 0 6px;font-size:1.2rem;font-weight:900;color:var(--pr-blue)}
.pr-card.highlight h2{color:#fff}
.pr-muted{color:#64748b;font-size:13.5px;margin:0 0 20px;line-height:1.5}
.pr-card label{display:block;font-size:12px;color:#475569;font-weight:700;margin:16px 0 6px;text-transform:uppercase;letter-spacing:.04em}
.pr-card input[type=text]{
  width:100%;padding:13px 14px;border-radius:11px;
  border:1.5px solid var(--pr-border);font-size:15px;font-family:inherit;
  background:#f8fafc;color:#0f172a;box-sizing:border-box
}
.pr-card.highlight input[type=text]{background:rgba(255,255,255,.1);border-color:rgba(255,255,255,.2);color:#fff}
.pr-card.highlight input[type=text]::placeholder{color:#94a3b8}
.pr-card input:focus{outline:none;border-color:var(--pr-accent);box-shadow:0 0 0 3px rgba(31,63,224,.12);background:#fff}
.pr-card.highlight input:focus{border-color:rgba(255,255,255,.6);box-shadow:0 0 0 3px rgba(255,255,255,.1);background:rgba(255,255,255,.15)}

.pr-btn{
  margin-top:20px;width:100%;border:0;cursor:pointer;font-family:inherit;
  font-weight:800;font-size:15px;color:#fff;
  background:linear-gradient(135deg,var(--pr-accent),#0a1733);
  padding:15px;border-radius:12px;transition:.15s
}
.pr-btn:hover{filter:brightness(1.1)}
.pr-btn.gold{background:linear-gradient(135deg,#f59e0b,#f97316);color:#0a1733}
.pr-btn.alt{background:#fff;color:var(--pr-accent);border:1.5px solid var(--pr-accent)}
.pr-btn.alt:hover{background:#f0f4ff}

.pr-err{background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;padding:13px;border-radius:11px;font-size:13.5px;margin-bottom:14px}

/* ── CODE BOX ────────────────────────── */
.pr-code-box{
  background:linear-gradient(135deg,#f59e0b,#f97316);
  color:#0a1733;border-radius:16px;padding:28px;margin-bottom:20px;text-align:center
}
.pr-code-box .lbl{font-size:11px;letter-spacing:2px;text-transform:uppercase;opacity:.7;font-weight:700}
.pr-code-box .code{font-size:38px;font-weight:900;letter-spacing:5px;margin:8px 0;text-shadow:0 2px 8px rgba(0,0,0,.12)}
.pr-link-row{display:flex;gap:8px;margin:14px 0 6px;flex-wrap:wrap}
.pr-link-row input{
  flex:1;min-width:180px;padding:12px 14px;border-radius:10px;
  border:1.5px solid rgba(255,255,255,.3);font-size:13px;background:rgba(255,255,255,.1);color:#fff
}
.pr-copy-btn{
  border:1.5px solid rgba(255,255,255,.4);background:rgba(255,255,255,.12);color:#fff;
  border-radius:10px;padding:0 16px;font-weight:700;cursor:pointer;font-size:13.5px;
  white-space:nowrap
}
.pr-copy-btn:hover{background:rgba(255,255,255,.22)}
.pr-wa-btn{
  display:inline-flex;align-items:center;gap:10px;
  background:#25D366;color:#06351e;text-decoration:none;
  font-weight:800;padding:13px 22px;border-radius:12px;margin-top:14px;font-size:14.5px
}
.pr-wa-btn:hover{background:#1aad52}
.pr-reward-box{
  background:#f0f9ff;border:1px solid #bae6fd;border-radius:12px;
  padding:16px;font-size:13.5px;color:#0c4a6e;margin-top:16px;line-height:1.65
}
.pr-reward-box.dark{background:rgba(255,255,255,.08);border-color:rgba(255,255,255,.18);color:#c7d2fe}

/* ── SUIVI ───────────────────────────── */
.trk-sum{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin:16px 0 20px}
.trk-kpi{background:var(--pr-light);border:1px solid var(--pr-border);border-radius:12px;padding:14px;text-align:center}
.trk-kpi b{display:block;font-size:24px;color:var(--pr-blue);font-weight:900}
.trk-kpi.ok b{color:var(--pr-green)}.trk-kpi.wait b{color:#b45309}
.trk-kpi span{font-size:12px;color:#64748b}
.trk-table{width:100%;border-collapse:collapse;margin-top:8px;font-size:13.5px}
.trk-table th{font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:#94a3b8;text-align:left;padding:8px 10px;border-bottom:2px solid #eef2f7}
.trk-table td{padding:10px;border-bottom:1px solid #f1f5f9;color:#0f172a}
.pill{padding:4px 10px;border-radius:999px;font-size:11px;font-weight:800;white-space:nowrap}
.pill.wait{background:#fff7ed;color:#9a3412}.pill.ok{background:#dcfce7;color:#166534}.pill.annule{background:#fee2e2;color:#991b1b}

/* ── FAQ ─────────────────────────────── */
.pr-faq{display:flex;flex-direction:column;gap:10px;margin-top:28px}
.pr-faq-item{border:1px solid var(--pr-border);border-radius:14px;overflow:hidden;background:#fff}
.pr-faq-q{
  width:100%;background:none;border:none;cursor:pointer;
  display:flex;align-items:center;justify-content:space-between;gap:12px;
  padding:17px 20px;font-size:14.5px;font-weight:700;color:var(--pr-blue);text-align:left
}
.pr-faq-q svg{flex:none;transition:.25s}
.pr-faq-item.open .pr-faq-q svg{transform:rotate(45deg)}
.pr-faq-a{display:none;padding:0 20px 16px;font-size:14px;color:#475569;line-height:1.7}
.pr-faq-item.open .pr-faq-a{display:block}

/* ── CTA FINAL ───────────────────────── */
.pr-cta{
  margin-top:60px;
  background:linear-gradient(135deg,#f59e0b 0%,#f97316 50%,#e8242c 100%);
  border-radius:24px;padding:52px 40px;text-align:center;
  color:#0a1733;position:relative;overflow:hidden
}
.pr-cta::before{
  content:'';position:absolute;right:-60px;top:-60px;width:250px;height:250px;
  border-radius:50%;background:rgba(255,255,255,.12)
}
.pr-cta h2{font-size:clamp(1.6rem,3vw,2.1rem);font-weight:900;margin:0 0 12px}
.pr-cta p{font-size:16px;line-height:1.65;max-width:600px;margin:0 auto 28px;opacity:.9}
.pr-cta .btns{display:flex;flex-wrap:wrap;gap:12px;justify-content:center}
.pr-cta .btns a,.pr-cta .btns button{
  display:inline-flex;align-items:center;gap:8px;
  padding:14px 28px;border-radius:12px;font-weight:800;font-size:15px;text-decoration:none;
  cursor:pointer;border:none;font-family:inherit
}
.btn-dark{background:#0a1733;color:#fff}
.btn-dark:hover{background:#12275e}
.btn-white{background:#fff;color:#0a1733}
.btn-white:hover{background:#f1f5f9}

@media(max-width:640px){
  .pr-double-half + .pr-double-half{border-left:none;border-top:1px solid var(--pr-border)}
  .pr-calc{padding:24px 18px}
  .pr-cta{padding:36px 22px}
  .trk-sum{grid-template-columns:1fr 1fr}
}
</style>

<!-- ══ HERO ══════════════════════════════════════════ -->
<section class="pr-hero">
  <div class="pr-hero-badge">🎁 Programme parrainage IBIG EDUFORM</div>
  <h1>Recommandez &amp; gagnez</h1>
  <span class="big-pct"><?= (int)$pct; ?>%</span>
  <p class="sub">Parrainez un proche vers IBIG EDUFORM. Lui obtient <strong><?= (int)$pct; ?>% de réduction</strong> sur son inscription. Vous gagnez <strong><?= (int)$pct; ?>% de remise</strong> sur votre prochaine formation. Sans limite de filleuls.</p>
  <div class="pr-hero-btns">
    <button class="pr-hero-btn btn-gold" onclick="document.getElementById('form-creer').scrollIntoView({behavior:'smooth'})">🚀 Obtenir mon code maintenant</button>
    <a class="btn-ghost-w" href="#comment">Comment ça marche ?</a>
  </div>

  <!-- Double bénéfice -->
  <div class="pr-double">
    <div class="pr-double-half">
      <div class="who">Vous (le parrain)</div>
      <div class="pct-big" style="color:var(--pr-accent)"><?= (int)$pct; ?>%</div>
      <div class="desc">de remise sur votre prochaine formation</div>
    </div>
    <div class="pr-double-half">
      <div class="who">Votre filleul</div>
      <div class="pct-big" style="color:var(--pr-orange)"><?= (int)$pct; ?>%</div>
      <div class="desc">de réduction sur son inscription</div>
    </div>
  </div>
</section>

<div class="pr-wrap">

  <!-- ══ COMMENT ÇA MARCHE ════════════════════════════ -->
  <div class="pr-section" id="comment">
    <span class="pr-label">Fonctionnement</span>
    <h2 class="pr-h2">En 3 étapes simples</h2>
    <p class="pr-lead">Pas d'application à installer. Pas de formulaire compliqué. Juste un code à partager et des avantages à encaisser.</p>

    <div class="pr-steps">
      <div class="pr-step">
        <div class="pr-step-num" style="background:linear-gradient(135deg,#f59e0b,#f97316)">1</div>
        <h3>Générez votre code</h3>
        <p>Entrez votre nom et votre numéro WhatsApp ou email. Vous recevez un code unique et un lien de parrainage en quelques secondes.</p>
        <div class="pr-step-arrow">→</div>
      </div>
      <div class="pr-step">
        <div class="pr-step-num" style="background:linear-gradient(135deg,#25D366,#1aad52)">2</div>
        <h3>Partagez votre lien</h3>
        <p>Envoyez votre lien personnel à vos amis, collègues et contacts via WhatsApp, SMS ou email. Chaque inscription via votre lien compte.</p>
        <div class="pr-step-arrow">→</div>
      </div>
      <div class="pr-step">
        <div class="pr-step-num" style="background:linear-gradient(135deg,var(--pr-accent),#0a1733)">3</div>
        <h3>Encaissez vos récompenses</h3>
        <p>Dès que votre filleul s'inscrit, vous gagnez <?= (int)$pct; ?>% de remise sur votre prochaine formation. Consultez vos récompenses à tout moment avec votre code.</p>
      </div>
    </div>
  </div>

  <!-- ══ AVANTAGES ═══════════════════════════════════ -->
  <div class="pr-section">
    <span class="pr-label">Vos avantages</span>
    <h2 class="pr-h2">Pourquoi parrainer ?</h2>

    <div class="pr-avantages">
      <div class="pr-av">
        <div class="ico">♾️</div>
        <div>
          <h4>Sans limite de filleuls</h4>
          <p>Chaque filleul inscrit vous rapporte <?= (int)$pct; ?>%. 3 filleuls = 3 remises. Il n'y a pas de plafond.</p>
        </div>
      </div>
      <div class="pr-av">
        <div class="ico">⚡</div>
        <div>
          <h4>Immédiat &amp; sans paperasse</h4>
          <p>Votre code est généré en quelques secondes. Pas de dossier, pas de signature, pas d'attente.</p>
        </div>
      </div>
      <div class="pr-av">
        <div class="ico">👁️</div>
        <div>
          <h4>Suivi en temps réel</h4>
          <p>Consultez vos filleuls et vos récompenses en ligne, 24h/24, avec votre code et votre contact.</p>
        </div>
      </div>
      <div class="pr-av">
        <div class="ico">🌍</div>
        <div>
          <h4>Valable dans 17 pays OHADA</h4>
          <p>Parrainez vos proches partout dans l'espace OHADA : Côte d'Ivoire, Sénégal, Mali, Cameroun, Bénin…</p>
        </div>
      </div>
      <div class="pr-av">
        <div class="ico">🎓</div>
        <div>
          <h4>Votre filleul économise aussi</h4>
          <p>Il bénéficie de <?= (int)$pct; ?>% de réduction dès l'inscription. Le parrainage est gagnant-gagnant.</p>
        </div>
      </div>
      <div class="pr-av">
        <div class="ico">🤝</div>
        <div>
          <h4>Engagement IBIG EDUFORM</h4>
          <p>Toutes les récompenses sont honorées dans les délais et traçables. Votre code, votre tableau de bord.</p>
        </div>
      </div>
    </div>
  </div>

  <!-- ══ CALCULATEUR ═════════════════════════════════ -->
  <div class="pr-calc">
    <h3>💰 Calculez votre gain potentiel</h3>
    <p class="sub">Bougez le curseur pour voir combien vous pouvez économiser en parrainant.</p>
    <div class="pr-calc-row">
      <div class="pr-calc-field">
        <label>Nombre de filleuls que vous allez parrainer</label>
        <input type="range" id="calcSlider" min="1" max="20" value="3" oninput="updateCalc()">
        <div class="val"><span id="calcNb">3</span> filleul(s)</div>
      </div>
      <div class="pr-calc-field">
        <label>Formation que vous visez (prix moyen)</label>
        <input type="range" id="calcPrice" min="100000" max="1000000" step="25000" value="280000" oninput="updateCalc()">
        <div class="val" style="font-size:1.2rem"><span id="calcPriceVal">280 000</span> FCFA</div>
      </div>
      <div class="pr-calc-result">
        <div class="label">Votre économie estimée</div>
        <div class="amount" id="calcResult">—</div>
        <div class="note">Cumulée sur <span id="calcNb2">3</span> parrainage(s)</div>
      </div>
    </div>
  </div>

  <!-- ══ FORMULAIRES ═════════════════════════════════ -->
  <div class="pr-main-grid" id="form-creer">

    <!-- GÉNÉRER / SUIVI RÉSULTAT -->
    <?php if ($track):
      $link  = $base . '/formations.php?ref=' . urlencode($track['code']);
      $waText= rawurlencode("Bonjour ! Je vous recommande les formations certifiantes IBIG EDUFORM. Avec mon lien de parrainage, vous bénéficiez de " . $pct . "% de réduction : " . $link);
    ?>
    <div class="pr-card highlight" style="grid-column:1/-1">
      <h2 style="font-size:1.35rem"><?= $result ? '✅ Votre code est prêt — commencez à parrainer !' : '📊 Suivi de vos parrainages'; ?></h2>
      <p class="pr-muted">Partagez votre lien et retrouvez ici, en temps réel, les inscriptions de vos filleuls et vos récompenses.</p>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:22px;align-items:start" class="res-grid">
        <div>
          <div class="pr-code-box">
            <div class="lbl">Votre code parrainage</div>
            <div class="code"><?= h($track['code']); ?></div>
          </div>
          <div style="font-size:12px;color:#93c5fd;margin-bottom:8px;font-weight:700;text-transform:uppercase;letter-spacing:.05em">Votre lien à partager</div>
          <div class="pr-link-row">
            <input id="refLink" type="text" readonly value="<?= h($link); ?>">
            <button class="pr-copy-btn" type="button" onclick="(function(b){navigator.clipboard&&navigator.clipboard.writeText(document.getElementById('refLink').value);var o=b.textContent;b.textContent='✓ Copié';setTimeout(function(){b.textContent=o;},1600);})(this)">Copier</button>
          </div>
          <a class="pr-wa-btn" href="https://wa.me/?text=<?= $waText; ?>" target="_blank" rel="noopener">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
            Partager sur WhatsApp
          </a>
        </div>
        <div>
          <div class="trk-sum">
            <div class="trk-kpi"><b><?= (int)$track['nb']; ?></b><span>Filleul(s)<br>inscrit(s)</span></div>
            <div class="trk-kpi ok"><b><?= (int)$track['honored']; ?></b><span>Récompense(s)<br>honorée(s)</span></div>
            <div class="trk-kpi wait"><b><?= (int)$track['pending']; ?></b><span>En<br>attente</span></div>
          </div>
          <?php if ($track['rows']): ?>
            <div style="overflow-x:auto">
            <table class="trk-table">
              <thead><tr><th>Date</th><th>Filleul</th><th>Formation</th><th>Sa remise</th><th>Votre gain</th></tr></thead>
              <tbody>
                <?php foreach ($track['rows'] as $r):
                  $stt = (string)($r['statut'] ?? 'en_attente'); ?>
                  <tr>
                    <td><?= h(!empty($r['created_at']) ? date('d/m/Y', strtotime((string)$r['created_at'])) : '—'); ?></td>
                    <td><strong><?= h($r['filleul_nom'] ?: '—'); ?></strong></td>
                    <td style="max-width:200px;white-space:normal"><?= h($r['formation_titre'] ?: '—'); ?></td>
                    <td><?= h($fcfa($r['remise'] ?? 0)); ?></td>
                    <td><span class="pill <?= $stClass[$stt] ?? 'wait'; ?>"><?= h($stLabel[$stt] ?? $stt); ?></span></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
            </div>
          <?php else: ?>
            <div class="pr-reward-box dark">Aucun filleul inscrit pour l'instant. Partagez votre lien — chaque inscription apparaîtra ici automatiquement.</div>
          <?php endif; ?>
          <div class="pr-reward-box dark" style="margin-top:12px">
            💡 <strong>Comment encaisser :</strong> chaque filleul inscrit via votre lien vous donne <strong><?= (int)$pct; ?>%</strong> de remise sur votre prochaine formation. Présentez simplement votre code <strong><?= h($track['code']); ?></strong> lors de votre inscription.
          </div>
        </div>
      </div>
    </div>

    <?php else: ?>

    <!-- FORMULAIRE CRÉER CODE -->
    <?php if (!$result): ?>
    <div class="pr-card highlight">
      <h2>🎁 Obtenez votre code gratuit</h2>
      <p class="pr-muted">Immédiat. Votre code + lien de parrainage générés en un clic.</p>
      <?php if ($error !== ''): ?><div class="pr-err"><?= h($error); ?></div><?php endif; ?>
      <form method="post" action="parrainage.php">
        <?= csrf_field(); ?>
        <input type="hidden" name="do" value="create">
        <label>Votre nom complet</label>
        <input type="text" name="nom" required placeholder="Ex : Kouamé Adjoumani" value="<?= h((string)($_POST['nom'] ?? '')); ?>">
        <label>Votre WhatsApp ou email</label>
        <input type="text" name="contact" required placeholder="+225 07 00 00 00 00 ou email@exemple.com" value="<?= h((string)($_POST['contact'] ?? '')); ?>">
        <button class="pr-btn gold" type="submit">🚀 Générer mon code →</button>
      </form>
      <div class="pr-reward-box dark" style="margin-top:16px">
        🔒 Vos données sont utilisées uniquement pour associer votre code. Aucun démarchage.
      </div>
    </div>
    <?php endif; ?>

    <!-- FORMULAIRE SUIVI -->
    <div class="pr-card">
      <h2>📊 Déjà parrain ? Suivre mes parrainages</h2>
      <p class="pr-muted">Entrez votre code et le contact utilisé à sa création pour voir vos filleuls et vos récompenses.</p>
      <?php if ($trackErr !== ''): ?><div class="pr-err"><?= h($trackErr); ?></div><?php endif; ?>
      <form method="post" action="parrainage.php">
        <?= csrf_field(); ?>
        <input type="hidden" name="do" value="track">
        <label>Votre code de parrainage</label>
        <input type="text" name="code" required placeholder="Ex: IBIG3A9F2C" value="<?= h((string)($_POST['code'] ?? '')); ?>">
        <label>Votre WhatsApp ou email (utilisé à la création)</label>
        <input type="text" name="contact_track" required placeholder="+225 07 00 00 00 00 ou email" value="<?= h((string)($_POST['contact_track'] ?? '')); ?>">
        <button class="pr-btn alt" type="submit">Voir mes parrainages →</button>
      </form>
    </div>

    <?php endif; ?>
  </div>

  <!-- ══ FAQ ════════════════════════════════════════ -->
  <div class="pr-section" id="faq">
    <span class="pr-label">Questions fréquentes</span>
    <h2 class="pr-h2">Tout ce que vous devez savoir</h2>

    <div class="pr-faq">
      <?php
      $faqs = [
        ["Combien puis-je gagner avec le programme de parrainage ?",
         "Il n'y a pas de limite. Chaque filleul inscrit via votre lien vous rapporte " . $pct . "% de remise sur votre prochaine formation IBIG EDUFORM. Si vous parrainez 5 personnes, vous accumulez 5 récompenses. Les récompenses peuvent être utilisées séparément ou cumulées selon les conditions."],
        ["Comment mon filleul utilise-t-il ma réduction ?",
         "Il doit simplement cliquer sur votre lien personnel avant de s'inscrire. La réduction de " . $pct . "% est automatiquement appliquée à son inscription. Si votre lien n'a pas été utilisé, il peut aussi mentionner votre code lors de la préinscription."],
        ["Quand est-ce que ma récompense est honorée ?",
         "Votre récompense passe de « En attente » à « Honorée » dès que votre filleul a finalisé et payé son inscription. Elle vous est ensuite appliquée sur votre prochaine formation sur simple présentation de votre code parrainage."],
        ["Comment utiliser ma remise de parrainage ?",
         "Lors de votre prochaine inscription ou préinscription chez IBIG EDUFORM, mentionnez votre code parrainage. L'équipe vérifiera votre solde de récompenses et appliquera la remise correspondante sur votre facture."],
        ["Le programme est-il disponible dans tous les pays ?",
         "Oui. Le programme de parrainage IBIG EDUFORM est valable dans les 17 pays de l'espace OHADA : Côte d'Ivoire, Sénégal, Mali, Cameroun, Bénin, Togo, Burkina Faso, Guinée, Congo, Gabon, RDC, Niger, Tchad, Centrafrique, Comores, Guinée Équatoriale et Madagascar."],
        ["Mon code expire-t-il ?",
         "Non. Votre code de parrainage n'a pas de date d'expiration. Vous pouvez l'utiliser et le partager à tout moment. Seules les conditions du programme (pourcentage de remise) peuvent évoluer : les remises acquises avant tout changement restent garanties."],
      ];
      foreach ($faqs as $i => $fq):
      ?>
        <div class="pr-faq-item" id="pfaq-<?= $i; ?>">
          <button class="pr-faq-q" aria-expanded="false" onclick="pfaqToggle(this)">
            <?= htmlspecialchars($fq[0], ENT_QUOTES, 'UTF-8'); ?>
            <svg width="18" height="18" viewBox="0 0 18 18" fill="none"><path d="M9 3v12M3 9h12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
          </button>
          <div class="pr-faq-a"><?= htmlspecialchars($fq[1], ENT_QUOTES, 'UTF-8'); ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- ══ CTA FINAL ══════════════════════════════════ -->
  <div class="pr-cta">
    <h2>🎁 Commencez à parrainer maintenant</h2>
    <p>Générez votre code en 10 secondes et partagez-le à vos proches. Chaque inscription vous rapporte <?= (int)$pct; ?>% de remise — sans limite.</p>
    <div class="btns">
      <button class="btn-dark" onclick="document.getElementById('form-creer').scrollIntoView({behavior:'smooth'})">🚀 Obtenir mon code</button>
      <a class="btn-white" href="/formations.php">📚 Voir les formations</a>
    </div>
  </div>

</div><!-- /pr-wrap -->

<script>
/* Calculateur */
function updateCalc(){
  var nb    = parseInt(document.getElementById('calcSlider').value, 10);
  var price = parseInt(document.getElementById('calcPrice').value, 10);
  var pct   = <?= (int)$pct; ?>;
  var gain  = Math.round(nb * price * pct / 100);
  document.getElementById('calcNb').textContent  = nb;
  document.getElementById('calcNb2').textContent = nb;
  document.getElementById('calcPriceVal').textContent = price.toLocaleString('fr-FR');
  document.getElementById('calcResult').textContent = gain.toLocaleString('fr-FR') + ' FCFA';
}
updateCalc();

/* FAQ */
function pfaqToggle(btn){
  var item   = btn.closest('.pr-faq-item');
  var isOpen = item.classList.contains('open');
  document.querySelectorAll('.pr-faq-item.open').forEach(function(el){
    el.classList.remove('open');
    el.querySelector('button').setAttribute('aria-expanded','false');
  });
  if(!isOpen){ item.classList.add('open'); btn.setAttribute('aria-expanded','true'); }
}

/* Grid responsive simple */
document.querySelector && (function(){
  var g = document.querySelector('.res-grid');
  if(!g) return;
  function check(){ g.style.gridTemplateColumns = window.innerWidth < 700 ? '1fr' : '1fr 1fr'; }
  check();
  window.addEventListener('resize', check, {passive:true});
})();
</script>

<?php require __DIR__ . '/partials/footer.php'; ?>
