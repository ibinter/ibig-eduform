<?php
/* Bloc conversion réutilisable : Réassurance + « Besoin sur mesure ».
   Optionnel : $f (formation) pour pré-remplir le lien vers besoin-formation.php. */
if (!function_exists('h')) { function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); } }
$ibDomaine = isset($f['domaine']) ? (string)$f['domaine'] : '';
$ibTheme   = isset($f['titre'])   ? (string)$f['titre']   : '';
$ibLien = '/besoin-formation.php'
        . '?domaine=' . rawurlencode($ibDomaine)
        . '&theme='   . rawurlencode($ibTheme);
?>
<style>
.ib-conv{max-width:1180px;margin:40px auto;padding:0 20px;font-family:Inter,system-ui,sans-serif}
.ib-reassure{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:26px}
.ib-reassure .r{display:flex;align-items:flex-start;gap:12px;background:#f4f7fc;border:1px solid #e3ebf6;border-radius:16px;padding:16px 18px}
.ib-reassure .r i{flex:none;width:40px;height:40px;border-radius:12px;background:rgba(31,63,224,.10);color:#1f3fe0;display:flex;align-items:center;justify-content:center;font-size:18px}
.ib-reassure .r b{display:block;color:#0a1733;font-size:15px;font-weight:800}
.ib-reassure .r span{display:block;color:#5b6b8c;font-size:13px;margin-top:2px;line-height:1.4}
.ib-besoin{position:relative;overflow:hidden;background:linear-gradient(135deg,#0a1733,#12275e);border-radius:24px;padding:34px 38px;color:#eaf1ff}
.ib-besoin::after{content:"";position:absolute;right:-60px;top:-60px;width:240px;height:240px;border-radius:50%;background:rgba(245,166,35,.14)}
.ib-besoin h3{font-size:26px;font-weight:900;color:#fff;margin:0 0 8px;letter-spacing:.2px}
.ib-besoin p{font-size:16px;color:#c7d5f0;margin:0 0 18px;max-width:760px;line-height:1.5}
.ib-besoin .tags{display:flex;flex-wrap:wrap;gap:10px;margin-bottom:22px}
.ib-besoin .tags span{background:rgba(255,255,255,.10);border:1px solid rgba(255,255,255,.18);border-radius:999px;padding:9px 16px;font-size:14px;font-weight:700}
.ib-besoin .act{display:flex;flex-wrap:wrap;gap:12px;align-items:center}
.ib-besoin .btn{display:inline-flex;align-items:center;gap:9px;background:linear-gradient(135deg,#f5a623,#ff8a00);color:#231400;
  font-weight:900;font-size:16px;padding:15px 26px;border-radius:14px;text-decoration:none;box-shadow:0 14px 30px rgba(245,166,35,.32);transition:transform .15s ease,box-shadow .15s ease}
.ib-besoin .btn:hover{transform:translateY(-2px);box-shadow:0 18px 40px rgba(245,166,35,.45)}
.ib-besoin .wa{display:inline-flex;align-items:center;gap:8px;color:#eaf1ff;font-weight:700;font-size:15px;text-decoration:none;border:1px solid rgba(255,255,255,.25);border-radius:14px;padding:14px 20px}
.ib-besoin .wa:hover{background:rgba(255,255,255,.08)}
@media(max-width:820px){.ib-reassure{grid-template-columns:repeat(2,1fr)}.ib-besoin h3{font-size:22px}}
@media(max-width:480px){.ib-reassure{grid-template-columns:1fr}}
</style>

<section class="ib-conv">
  <!-- RÉASSURANCE -->
  <div class="ib-reassure">
    <div class="r"><i>🎓</i><div><b>Certificat reconnu</b><span>Vérifiable en ligne</span></div></div>
    <div class="r"><i>👨‍🏫</i><div><b>Formateurs praticiens</b><span>Experts du terrain</span></div></div>
    <div class="r"><i>🚀</i><div><b>Démarrage garanti</b><span>Dès le 1er versement</span></div></div>
    <div class="r"><i>🌍</i><div><b>Ouvert à l'international</b><span>En ligne partout (espace OHADA) · présentiel à Abidjan</span></div></div>
  </div>

  <!-- BESOIN SUR MESURE -->
  <div class="ib-besoin">
    <h3>Une autre formation vous intéresse&nbsp;?</h3>
    <p>En privé, en groupe, en entreprise (intra / inter) ou à l'international&nbsp;: soumettez‑nous votre besoin. IBIG EDUFORM conçoit la formation sur mesure qu'il vous faut.</p>
    <div class="tags">
      <span>👤 En privé</span>
      <span>👥 En groupe</span>
      <span>🏢 En entreprise</span>
      <span>🌍 À l'international</span>
      <span>🧩 Sur mesure</span>
    </div>
    <div class="act">
      <a class="btn" href="<?= h($ibLien); ?>">✍️ Soumettre mon besoin</a>
      <a class="wa" href="https://wa.me/2250778882592?text=<?= rawurlencode('Bonjour IBIG EDUFORM, j\'ai un besoin de formation : ' . $ibTheme); ?>" target="_blank" rel="noopener">💬 En parler sur WhatsApp</a>
    </div>
  </div>
</section>
