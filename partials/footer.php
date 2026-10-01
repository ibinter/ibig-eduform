<?php
/**
 * FOOTER OFFICIEL — IBIG EDUFORM
 * Tawk.to GAUCHE / WhatsApp DROITE — version stable
 */
?>

<style>
/* =========================================
   FOOTER — IBIG EDUFORM
========================================= */
.footer{
  background:linear-gradient(180deg,#020617 0%, #0b1f3a 100%);
  border-top:4px solid #f5a623;
  margin-top:120px;
  color:#e5e7eb;
  font-family:Inter,system-ui,sans-serif;
}
.footer-wrap{
  max-width:1200px;
  margin:auto;
  padding:80px 20px 60px;
  display:grid;
  grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
  gap:48px;
}
.footer-col h4{
  margin-bottom:16px;
  font-size:1.05rem;
  font-weight:900;
  letter-spacing:.06em;
  text-transform:uppercase;
  color:#fff;
}
.footer-col h4::after{
  content:"";display:block;width:34px;height:3px;
  background:#f5a623;margin-top:8px;border-radius:2px;
}
.footer-col p{ font-size:.95rem; line-height:1.7; opacity:.9; margin:0 0 10px; }
.footer-col ul{ list-style:none; padding:0; margin:0; }
.footer-col li{ margin-bottom:10px; }
.footer-col a{
  color:#e5e7eb;text-decoration:none;font-size:.95rem;opacity:.9;
  transition:color .15s, opacity .15s;
}
.footer-col a:hover{ color:#f5a623; opacity:1; }

.footer-legal{
  border-top:1px solid rgba(255,255,255,.08);
  padding:22px 20px;
  display:flex;flex-wrap:wrap;
  justify-content:space-between;align-items:center;
  font-size:.85rem;max-width:1200px;margin:auto;
}
.footer-legal-left{ opacity:.85; }
.footer-legal-right{ display:flex; gap:18px; flex-wrap:wrap; }
.footer-legal-right a{ color:#e5e7eb; text-decoration:none; opacity:.85; }
.footer-legal-right a:hover{ color:#f5a623; opacity:1; }

@media(max-width:700px){
  .footer-legal{ flex-direction:column; gap:12px; text-align:center; }
}

/* ===== RÉSEAUX SOCIAUX ===== */
.footer-social{
  border-top:1px solid rgba(255,255,255,.08);
  padding:28px 20px;
  display:flex;flex-wrap:wrap;
  justify-content:center;align-items:center;
  gap:14px;
  max-width:1200px;margin:auto;
}
.footer-social a{
  display:flex;align-items:center;justify-content:center;
  width:44px;height:44px;border-radius:50%;
  background:rgba(255,255,255,.07);
  border:1px solid rgba(255,255,255,.13);
  transition:all .2s ease;
  text-decoration:none;
}
.footer-social a:hover{ background:#f5a623; border-color:#f5a623; transform:translateY(-3px); }
.footer-social a svg{ width:20px;height:20px;fill:#e5e7eb; }
.footer-social a:hover svg{ fill:#020617; }

/* ===== WHATSAPP — DROITE ===== */
.whatsapp-wrap{
  position:fixed;right:22px;bottom:22px;z-index:9999;
  display:flex;align-items:center;gap:14px;
}
.whatsapp-bubble{
  background:#020617;color:#fff;
  font-size:.85rem;font-weight:800;
  padding:10px 14px;border-radius:14px;
  border:1px solid rgba(255,255,255,.15);
  white-space:nowrap;opacity:0;transform:translateX(10px);
  animation:wb 6s ease-in-out infinite;
  box-shadow:0 8px 22px rgba(0,0,0,.45);
}
@keyframes wb{
  0%,20%{opacity:0;transform:translateX(10px);}
  30%,70%{opacity:1;transform:translateX(0);}
  100%{opacity:0;transform:translateX(10px);}
}
.whatsapp-float{
  width:60px;height:60px;background:#25D366;
  border-radius:50%;display:flex;align-items:center;justify-content:center;
  box-shadow:0 12px 30px rgba(37,211,102,.45);
  transition:all .25s ease;text-decoration:none;
}
.whatsapp-float:hover{
  transform:scale(1.1);
  box-shadow:0 18px 40px rgba(37,211,102,.6);
}
.whatsapp-float svg{ width:30px; height:30px; fill:#fff; }
@media(max-width:480px){
  .whatsapp-wrap{ right:16px; bottom:16px; }
}
</style>

<footer class="footer">
  <div class="footer-wrap">

    <div class="footer-col">
      <h4>IBIG EDUFORM</h4>
      <p>
        IBIG EDUFORM est un institut de formation professionnelle
        orienté résultats, employabilité et certification.
        Nos programmes répondent aux besoins réels des entreprises
        et des professionnels.
      </p>
    </div>

    <div class="footer-col">
      <h4>Formations</h4>
      <ul>
        <li><a href="/formations.php">Catalogue des formations</a></li>
        <li><a href="/calendrier.php">Calendrier des sessions</a></li>
        <li><a href="/certifications.php">Certifications</a></li>
        <li><a href="/preinscription-generale.php">Préinscription</a></li>
        <li><a href="/parrainage.php">🎁 Parrainage − gagnez 10%</a></li>
        <li><a href="/avis-clients.php">Avis des participants</a></li>
      </ul>
    </div>

    <div class="footer-col">
      <h4>Ressources</h4>
      <ul>
        <li><a href="/diagnostic.php" style="color:#f59e0b;font-weight:800">🎯 Diagnostic de compétences</a></li>
        <li><a href="/simulateur-roi.php" style="color:#10b981;font-weight:800">📈 Simulateur ROI Formation</a></li>
        <li style="border-top:1px solid rgba(255,255,255,.1);margin-top:8px;padding-top:8px"><a href="/blog.php">Blog & actualités</a></li>
        <li><a href="/faq.php">Foire aux questions</a></li>
        <li><a href="/telechargements.php">Documents & supports</a></li>
        <li><a href="/verifier-certificat.php">Vérifier un certificat</a></li>
      </ul>
    </div>

    <div class="footer-col">
      <h4>Contacts</h4>
      <p>Email :<br>
        <a href="mailto:formation@ibig-eduform.com">formation@ibig-eduform.com</a><br>
        <a href="mailto:formation@ibig-eduform.com">formation@ibig-eduform.com</a>
      </p>
      <p>
        Téléphones :<br>
        +225 07 78 88 25 92<br>
        +225 07 78 88 25 92 (WhatsApp)<br>
        +225 05 65 90 47 79<br>
        +225 01 53 59 55 44
      </p>
      <p>
        Site institutionnel :<br>
        <a href="https://intermark-business.com" target="_blank" rel="noopener">intermark-business.com</a>
      </p>
    </div>

  </div>

  <!-- RÉSEAUX SOCIAUX -->
  <div class="footer-social">
    <!-- Facebook -->
    <a href="https://www.facebook.com/IBInterOfficiel" target="_blank" rel="noopener" aria-label="Facebook IBIG">
      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M22 12c0-5.52-4.48-10-10-10S2 6.48 2 12c0 4.84 3.44 8.87 8 9.8V15H8v-3h2V9.5C10 7.57 11.57 6 13.5 6H16v3h-2c-.55 0-1 .45-1 1v2h3l-.5 3H13v6.8c4.56-.93 8-4.96 8-9.8z"/></svg>
    </a>
    <!-- Instagram -->
    <a href="https://www.instagram.com/ib_inter_officiel/" target="_blank" rel="noopener" aria-label="Instagram IBIG">
      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 1.366.062 2.633.334 3.608 1.308.975.975 1.246 2.242 1.308 3.608.058 1.266.07 1.646.07 4.851s-.012 3.584-.07 4.85c-.062 1.366-.334 2.633-1.308 3.608-.975.975-2.242 1.246-3.608 1.308-1.266.058-1.646.07-4.85.07s-3.584-.012-4.85-.07c-1.366-.062-2.633-.334-3.608-1.308-.975-.975-1.246-2.242-1.308-3.608C2.175 15.584 2.163 15.204 2.163 12s.012-3.584.07-4.85c.062-1.366.334-2.633 1.308-3.608.975-.975 2.242-1.246 3.608-1.308C8.416 2.175 8.796 2.163 12 2.163zm0-2.163C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 1 0 0 12.324 6.162 6.162 0 0 0 0-12.324zM12 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm6.406-11.845a1.44 1.44 0 1 0 0 2.881 1.44 1.44 0 0 0 0-2.881z"/></svg>
    </a>
    <!-- LinkedIn -->
    <a href="https://www.linkedin.com/company/intermark-business-international/" target="_blank" rel="noopener" aria-label="LinkedIn IBIG">
      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 0 1-2.063-2.065 2.064 2.064 0 1 1 2.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
    </a>
    <!-- YouTube -->
    <a href="https://www.youtube.com/@ibigroupsarl" target="_blank" rel="noopener" aria-label="YouTube IBIG">
      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
    </a>
    <!-- TikTok -->
    <a href="https://www.tiktok.com/@ibigroupsarl" target="_blank" rel="noopener" aria-label="TikTok IBIG">
      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-2.88 2.5 2.89 2.89 0 0 1-2.89-2.89 2.89 2.89 0 0 1 2.89-2.89c.28 0 .54.04.79.1V9.01a6.33 6.33 0 0 0-.79-.05 6.34 6.34 0 0 0-6.34 6.34 6.34 6.34 0 0 0 6.34 6.34 6.34 6.34 0 0 0 6.33-6.34V8.69a8.18 8.18 0 0 0 4.78 1.52V6.75a4.85 4.85 0 0 1-1.01-.06z"/></svg>
    </a>
    <!-- X / Twitter -->
    <a href="https://x.com/InterMarkB" target="_blank" rel="noopener" aria-label="X (Twitter) IBIG">
      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.746l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
    </a>
  </div>

  <div class="footer-legal">
    <div class="footer-legal-left">
      © <?= date('Y'); ?> IBIG EDUFORM — Tous droits réservés
    </div>
    <div class="footer-legal-right">
      <a href="/mentions-legales.php">Mentions légales</a>
      <a href="/confidentialite.php">Politique de confidentialité</a>
      <a href="/cgu.php">Conditions d'utilisation</a>
      <a href="/cgv.php">Conditions de vente</a>
    </div>
  </div>

  <!-- SOUS-FOOTER : mention groupe + PWA -->
  <div class="footer-subfooter">
    <span class="footer-ibig-mention">
      IBIG EDUFORM est la branche Formation de l'entreprise
      <a href="https://intermark-business.com" target="_blank" rel="noopener">INTERMARK BUSINESS INTERNATIONAL GROUP SARL (IBIG SARL)</a>
      — <a href="https://intermark-business.com" target="_blank" rel="noopener">intermark-business.com</a>
    </span>
    <a id="pwaInstallBtn" class="footer-pwa-btn" href="/catalogue-formations.php" aria-label="Installer l'application IBIG EDUFORM">
      📲 Installer l'app IBIG EDUFORM
    </a>
  </div>
</footer>

<style>
.footer-subfooter{
  border-top:1px solid rgba(255,255,255,.06);
  padding:16px 20px 28px;
  max-width:1200px;margin:auto;
  display:flex;flex-direction:column;align-items:center;gap:12px;
  font-size:.8rem;color:rgba(229,231,235,.6);
  text-align:center;
  /* Espace pour que les widgets flottants (WA + Tawk) ne masquent pas le contenu */
  padding-bottom:90px;
}
@media(min-width:701px){.footer-subfooter{padding-bottom:28px;}}
.footer-ibig-mention a{color:rgba(229,231,235,.75);text-decoration:underline;text-underline-offset:3px;}
.footer-ibig-mention a:hover{color:#f5a623;}
.footer-pwa-btn{
  background:linear-gradient(135deg,#f5a623,#e08f10);
  color:#020617;border:none;border-radius:20px;
  padding:9px 22px;font-size:.85rem;font-weight:800;
  cursor:pointer;white-space:nowrap;
  box-shadow:0 4px 14px rgba(245,166,35,.35);
  transition:all .2s;
  display:inline-flex;align-items:center;gap:6px;
}
.footer-pwa-btn:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(245,166,35,.5);}
</style>

<script>
(function(){
  var btn = document.getElementById('pwaInstallBtn');
  if (!btn) return;
  var deferredPrompt = null;
  /* Quand le navigateur propose l'installation, on transforme le lien en vrai bouton d'install */
  window.addEventListener('beforeinstallprompt', function(e){
    e.preventDefault();
    deferredPrompt = e;
    btn.removeAttribute('href');
    btn.addEventListener('click', function(ev){
      ev.preventDefault();
      deferredPrompt.prompt();
      deferredPrompt.userChoice.then(function(){ deferredPrompt = null; });
    });
  });
  window.addEventListener('appinstalled', function(){
    btn.textContent = '✅ Application installée !';
    btn.style.background = 'linear-gradient(135deg,#10b981,#059669)';
    setTimeout(function(){ btn.style.display = 'none'; }, 3000);
  });
})();
</script>

<!-- WHATSAPP FLOAT — DROITE -->
<div class="whatsapp-wrap">
  <span class="whatsapp-bubble">Besoin d'aide&nbsp;?</span>
  <a href="https://wa.me/2250778882592"
     class="whatsapp-float"
     target="_blank"
     rel="noopener"
     aria-label="Discuter sur WhatsApp avec IBIG EDUFORM">
    <svg viewBox="0 0 32 32" aria-hidden="true">
      <path d="M16 3.2C9.38 3.2 4 8.58 4 15.2c0 2.08.54 4.1 1.56 5.88L4 28.8l7.93-1.53c1.72.94 3.66 1.43 5.66 1.43 6.62 0 12-5.38 12-12S22.62 3.2 16 3.2z"/>
    </svg>
  </a>
</div>

<!-- POP-UP DE SORTIE (capture d'intention) — inclus via popup-exit.php sur les pages concernées -->
<?php if (function_exists('csrf_token')): ?>
<?php include_once __DIR__ . '/popup-exit.php'; ?>
<?php endif; ?>

<!--Start of Tawk.to Script — IBIG EDUFORM-->
<script type="text/javascript">
var Tawk_API = Tawk_API || {}, Tawk_LoadStart = new Date();

Tawk_API.customStyle = {
  visibility: {
    desktop: { position: 'bl', xOffset: 15, yOffset: 15 },
    mobile:  { position: 'bl', xOffset: 5,  yOffset: 5  }
  }
};

Tawk_API.visitor = {
  name:  'Visiteur EDUFORM',
  email: 'visitor@ibig-eduform.com'
};

Tawk_API.onLoad = function(){
  if (typeof Tawk_API.addTags === 'function') {
    Tawk_API.addTags(['eduform', 'formation'], function(){});
  }
};

(function(){
  var s1=document.createElement("script"),s0=document.getElementsByTagName("script")[0];
  s1.async=true;
  s1.src='https://embed.tawk.to/6a1ee383d0b6e01c2e34b6be/1jq4ahf1s';
  s1.charset='UTF-8';
  s1.setAttribute('crossorigin','*');
  s0.parentNode.insertBefore(s1,s0);
})();
</script>
<!--End of Tawk.to Script-->

<?php
/* PSEUDO-CRON : relances automatiques (1x/heure max, déclenché par les visites) */
if (!defined('PSEUDO_CRON_SKIP') && function_exists('Database::connect') || class_exists('Database')) {
    try {
        if (!function_exists('pseudo_cron_run')) {
            require_once __DIR__ . '/../core/pseudo_cron.php';
        }
        $__pdo_cron = Database::connect();
        pseudo_cron_run($__pdo_cron);
        unset($__pdo_cron);
    } catch (Throwable $e) { /* silencieux */ }
}
?>

<!-- TRACKING CROSS-SITE — vers IBIG SARL Analytics -->
<script>
(function(){
  'use strict';
  var site = 'eduform';
  var endpoint = 'https://intermark-business.com/api/track.php';
  var SESS_KEY = '_ibig_xsess';
  var sess = '', isUnique = 0;
  try {
    sess = localStorage.getItem(SESS_KEY) || '';
    if (!sess) {
      sess = Array.from(crypto.getRandomValues(new Uint8Array(16)))
        .map(function(b){return b.toString(16).padStart(2,'0');}).join('');
      localStorage.setItem(SESS_KEY, sess);
      isUnique = 1;
    }
  } catch(e){}
  var payload = {site:site,url:location.href,path:location.pathname,referer:document.referrer||'',session:sess,unique:isUnique};
  try {
    if (window.fetch) {
      fetch(endpoint,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload),credentials:'omit',keepalive:true,mode:'cors'}).catch(function(){});
    } else if (navigator.sendBeacon) {
      navigator.sendBeacon(endpoint, new Blob([JSON.stringify(payload)],{type:'application/json'}));
    }
  } catch(e){}
})();
</script>

<?php require_once __DIR__ . '/currency_widget.php'; ?>