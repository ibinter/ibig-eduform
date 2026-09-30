<?php
require_once __DIR__ . '/core/bootstrap.php';

$pageTitle = "Contact – IBIG EDUFORM";
$ogDesc    = "Contactez IBIG EDUFORM : formation, partenariat, information. Réponse rapide par WhatsApp, email ou téléphone.";

$pdo = Database::connect();
$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    try {
        $stmt = $pdo->prepare("INSERT INTO contacts (nom, email, message, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            trim((string)($_POST['nom'] ?? '')),
            trim((string)($_POST['email'] ?? '')),
            trim((string)($_POST['message'] ?? '')),
            $_SERVER['REMOTE_ADDR'] ?? null
        ]);
        $success = true;
    } catch (Throwable $e) {
        $error = "Une erreur est survenue. Merci de réessayer.";
    }
}

/* ---------- Coordonnées dynamiques (réglages) ---------- */
$st = static fn(string $k, string $d): string => function_exists('setting') ? (string)setting($k, $d) : $d;
$cEmail   = $st('contact_email', 'formation@intermark-business.com');
$cPhones  = $st('contact_phones', "+225 27 22 27 60 14\n+225 07 78 88 25 92");
$cAdresse = $st('contact_adresse', 'Abidjan, Cocody Riviera Palmeraie (non loin de la pharmacie Rue Ministre)');
$cMaps    = $st('contact_maps', '');
$firstPhone = trim(strtok($cPhones, "\n"));
$telHref  = '+' . preg_replace('/\D/', '', $firstPhone);
$waNum    = function_exists('whatsapp_admin_phone') ? whatsapp_admin_phone() : preg_replace('/\D/', '', $firstPhone);
$waLink   = 'https://wa.me/' . $waNum . '?text=' . rawurlencode("Bonjour IBIG EDUFORM, je souhaite des informations.");

include __DIR__ . '/partials/header.php';
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
/* =====================================================
   CONTACT — REFONTE PREMIUM (namespacé .ct)
===================================================== */
.ct{
  --brand:#1f3fe0;--brand2:#4f6bff;--accent:#e8242c;--accent2:#ff5763;
  --navy:#0a1733;--navy2:#0b2552;--ink:#0e1530;--muted:#5b647c;--line:rgba(14,21,48,.10);
  --bg:#f6f8fe;--card:#fff;--ok:#0fae6e;
  --r:22px;--r2:16px;--shadow:0 26px 64px rgba(14,21,48,.14);--shadow-sm:0 12px 30px rgba(14,21,48,.08);
  --font:Inter,"Plus Jakarta Sans",system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;
  background:var(--bg);color:var(--ink);font-family:var(--font);
}
.ct *{box-sizing:border-box}
.ct .wrap{max-width:1140px;margin:auto;padding:0 22px 80px}
.ct a{text-decoration:none}
.ct .eyebrow{display:inline-flex;align-items:center;gap:8px;font-size:12px;font-weight:800;letter-spacing:.5px;
  text-transform:uppercase;color:#bcd2ff;border:1px solid rgba(188,210,255,.3);background:rgba(31,63,224,.18);padding:7px 14px;border-radius:999px}
.ct .eyebrow .d{width:8px;height:8px;border-radius:50%;background:linear-gradient(135deg,var(--brand2),var(--accent))}

/* HERO */
.ct .hero{position:relative;overflow:hidden;color:#fff;border-radius:28px;margin:30px auto 0;max-width:1140px;
  padding:62px 56px;box-shadow:0 35px 80px rgba(10,23,51,.45);background:linear-gradient(125deg,var(--navy),var(--navy2))}
.ct .hero::before{content:"";position:absolute;right:-120px;top:-120px;width:420px;height:420px;border-radius:50%;background:radial-gradient(circle,rgba(31,63,224,.45),transparent 62%)}
.ct .hero::after{content:"";position:absolute;left:-100px;bottom:-140px;width:360px;height:360px;border-radius:50%;background:radial-gradient(circle,rgba(232,36,44,.28),transparent 60%)}
.ct .hero .in{position:relative;z-index:1;max-width:760px}
.ct .hero h1{margin:16px 0 12px;font-size:clamp(28px,4.4vw,46px);line-height:1.1;letter-spacing:-1px;font-weight:800}
.ct .hero p{font-size:16.5px;line-height:1.7;opacity:.94;max-width:62ch}
.ct .hero .chips{display:flex;flex-wrap:wrap;gap:10px;margin-top:22px}
.ct .hero .chips a{display:inline-flex;align-items:center;gap:8px;padding:11px 16px;border-radius:12px;font-size:14px;font-weight:800;border:1px solid rgba(255,255,255,.25);color:#fff;background:rgba(255,255,255,.10);transition:.16s}
.ct .hero .chips a:hover{transform:translateY(-2px)}
.ct .hero .chips a.wa{background:#16a34a;border-color:transparent}

/* GRID */
.ct .grid{display:grid;grid-template-columns:1.2fr .8fr;gap:22px;margin-top:30px}
.ct .box{background:var(--card);border:1px solid var(--line);border-radius:var(--r);box-shadow:var(--shadow-sm);padding:32px}
.ct .box h3{margin:0 0 6px;font-size:20px;font-weight:800;color:var(--ink)}
.ct .box .sub{margin:0 0 20px;color:var(--muted);font-size:14px}

/* FORM */
.ct label{display:block;font-weight:700;margin-bottom:7px;color:#334155;font-size:13.5px}
.ct input,.ct textarea{width:100%;padding:13px 14px;border-radius:12px;border:1px solid #e5e7eb;background:#fff;color:var(--ink);margin-bottom:18px;font-size:15px;font-family:inherit;outline:none;transition:.15s}
.ct input:focus,.ct textarea:focus{border-color:var(--brand);box-shadow:0 0 0 3px rgba(31,63,224,.12)}
.ct textarea{min-height:150px;resize:vertical}
.ct button{width:100%;background:linear-gradient(135deg,var(--brand),var(--brand2));color:#fff;font-weight:800;padding:16px;border-radius:14px;border:none;cursor:pointer;font-size:16px;box-shadow:0 16px 34px rgba(31,63,224,.30);transition:.16s;display:inline-flex;align-items:center;justify-content:center;gap:9px}
.ct button:hover{transform:translateY(-2px);filter:brightness(1.03)}

/* ALERTS */
.ct .success{background:#ecfdf3;border:1px solid #abefc6;color:#166534;padding:18px;border-radius:14px;margin-bottom:18px;font-weight:600;display:flex;gap:10px;align-items:flex-start}
.ct .error{background:#fef2f2;border:1px solid #fecaca;color:#991b1b;padding:16px;border-radius:14px;margin-bottom:18px;font-weight:600}

/* INFO card */
.ct .info-list{list-style:none;margin:0;padding:0;display:grid;gap:16px}
.ct .info-list li{display:flex;gap:13px;align-items:flex-start}
.ct .info-list .ico{width:42px;height:42px;border-radius:12px;flex-shrink:0;display:grid;place-items:center;font-size:17px;color:#fff;background:linear-gradient(135deg,var(--brand),var(--brand2))}
.ct .info-list li:nth-child(2) .ico{background:linear-gradient(135deg,#16a34a,#22c55e)}
.ct .info-list li:nth-child(3) .ico{background:linear-gradient(135deg,var(--accent),var(--accent2))}
.ct .info-list li:nth-child(4) .ico{background:linear-gradient(135deg,#0ea5e9,#38bdf8)}
.ct .info-list .k{font-size:11.5px;font-weight:800;text-transform:uppercase;letter-spacing:.3px;color:#94a3b8}
.ct .info-list .v{font-weight:700;color:var(--ink);font-size:14.5px;line-height:1.5}
.ct .info-list a{color:var(--brand)}
.ct .info-list a:hover{text-decoration:underline}
.ct .map-btn{display:inline-flex;align-items:center;gap:8px;margin-top:18px;padding:11px 16px;border-radius:12px;font-weight:800;font-size:13.5px;background:rgba(31,63,224,.08);border:1px solid rgba(31,63,224,.22);color:var(--brand)}

/* cross links */
.ct .quick{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-top:22px}
.ct .qcard{display:flex;gap:12px;align-items:center;background:var(--card);border:1px solid var(--line);border-radius:var(--r2);box-shadow:var(--shadow-sm);padding:18px;transition:.18s}
.ct .qcard:hover{transform:translateY(-4px);box-shadow:var(--shadow)}
.ct .qcard .ico{width:42px;height:42px;border-radius:12px;display:grid;place-items:center;font-size:17px;color:#fff;background:linear-gradient(135deg,var(--brand),var(--accent))}
.ct .qcard b{display:block;font-size:14.5px;color:var(--ink)}
.ct .qcard span{font-size:12.5px;color:var(--muted)}

@media(max-width:900px){.ct .grid{grid-template-columns:1fr}.ct .quick{grid-template-columns:1fr}.ct .hero{padding:48px 26px;margin-top:18px}}
</style>

<div class="ct">
<main class="wrap" style="padding-top:0">

  <!-- HERO -->
  <section class="hero">
    <div class="in">
      <span class="eyebrow"><span class="d"></span> Nous sommes à votre écoute</span>
      <h1>Contactez IBIG EDUFORM</h1>
      <p>Un besoin de formation, un projet de partenariat ou une simple question ? Notre équipe vous répond rapidement — choisissez le canal qui vous convient.</p>
      <div class="chips">
        <a class="wa" href="<?= e($waLink); ?>" target="_blank" rel="noopener"><i class="fa-brands fa-whatsapp"></i> WhatsApp</a>
        <a href="mailto:<?= e($cEmail); ?>"><i class="fa-solid fa-envelope"></i> Email</a>
        <?php if ($telHref !== '+'): ?><a href="tel:<?= e($telHref); ?>"><i class="fa-solid fa-phone"></i> Appeler</a><?php endif; ?>
      </div>
    </div>
  </section>

  <section class="grid">

    <!-- FORMULAIRE -->
    <div class="box">
      <h3>Envoyez-nous un message</h3>
      <p class="sub">Nous revenons vers vous dans les meilleurs délais.</p>

      <?php if ($success): ?>
        <div class="success"><i class="fa-solid fa-circle-check" style="margin-top:2px"></i> <span>Votre message a bien été envoyé. Notre équipe vous contactera très rapidement.</span></div>
      <?php endif; ?>
      <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error); ?></div>
      <?php endif; ?>

      <form method="post">
        <?= csrf_field(); ?>
        <label>Nom complet</label>
        <input name="nom" required placeholder="Votre nom et prénoms">

        <label>Email</label>
        <input type="email" name="email" required placeholder="vous@email.com">

        <label>Message</label>
        <textarea name="message" required placeholder="Décrivez votre besoin, votre projet ou votre question…"></textarea>

        <button type="submit"><i class="fa-solid fa-paper-plane"></i> Envoyer le message</button>
      </form>
    </div>

    <!-- COORDONNÉES -->
    <div class="box">
      <h3>Coordonnées</h3>
      <p class="sub">IBIG EDUFORM — Institut de formation professionnelle.</p>

      <ul class="info-list">
        <li>
          <span class="ico"><i class="fa-solid fa-phone"></i></span>
          <span><span class="k">Téléphone</span><span class="v"><?= nl2br(e($cPhones)); ?></span></span>
        </li>
        <li>
          <span class="ico"><i class="fa-brands fa-whatsapp"></i></span>
          <span><span class="k">WhatsApp</span><span class="v"><a href="<?= e($waLink); ?>" target="_blank" rel="noopener">Écrire sur WhatsApp →</a></span></span>
        </li>
        <li>
          <span class="ico"><i class="fa-solid fa-envelope"></i></span>
          <span><span class="k">Email</span><span class="v"><a href="mailto:<?= e($cEmail); ?>"><?= e($cEmail); ?></a></span></span>
        </li>
        <li>
          <span class="ico"><i class="fa-solid fa-location-dot"></i></span>
          <span><span class="k">Adresse</span><span class="v"><?= e($cAdresse); ?></span></span>
        </li>
      </ul>

      <?php if ($cMaps !== ''): ?>
        <a class="map-btn" href="<?= e($cMaps); ?>" target="_blank" rel="noopener"><i class="fa-solid fa-map-location-dot"></i> Voir sur la carte</a>
      <?php endif; ?>
    </div>

  </section>

  <!-- CROSS LINKS -->
  <section class="quick">
    <a class="qcard" href="/preinscription-generale.php"><span class="ico"><i class="fa-solid fa-pen-to-square"></i></span><span><b>Préinscription</b><span>Réservez votre place</span></span></a>
    <a class="qcard" href="/entreprises.php"><span class="ico"><i class="fa-solid fa-building"></i></span><span><b>Entreprises &amp; ONG</b><span>Formez vos équipes</span></span></a>
    <a class="qcard" href="/besoin-formation.php"><span class="ico"><i class="fa-solid fa-clipboard-check"></i></span><span><b>Formation sur mesure</b><span>Demandez un devis</span></span></a>
  </section>

</main>
</div>

<?php include __DIR__ . '/partials/footer.php'; ?>
