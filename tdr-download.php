<?php
declare(strict_types=1);
/**
 * IBIG EDUFORM — tdr-download.php
 * Page de téléchargement/impression du TDR.
 * Accès sécurisé par token signé (valide 72 h).
 * L'inscrit clique "Télécharger en PDF" → imprime depuis le navigateur.
 */

require_once __DIR__ . '/core/config.php';

$token = trim((string)($_GET['t'] ?? ''));
if ($token === '') { http_response_code(404); exit('Lien invalide.'); }

/* Décode le payload : base64url → JSON */
$payload64 = str_replace(['-','_'],['+','/'], $token);
$pad = strlen($payload64) % 4;
if ($pad) $payload64 .= str_repeat('=', 4 - $pad);
$json = base64_decode($payload64, true);
if ($json === false) { http_response_code(403); exit('Token invalide.'); }

$data = json_decode($json, true);
if (!is_array($data)) { http_response_code(403); exit('Token corrompu.'); }

/* Vérifie expiration (72h) */
if (!isset($data['exp']) || time() > (int)$data['exp']) {
    http_response_code(410);
    exit('<p style="font-family:sans-serif;text-align:center;margin-top:80px;color:#dc2626">Ce lien a expiré (validité 72 heures).<br>Contactez-nous : <a href="mailto:formation@ibig-eduform.com">formation@ibig-eduform.com</a></p>');
}

/* Vérifie la signature */
$sig = hash_hmac('sha256', $data['slug'] . '|' . $data['exp'] . '|' . ($data['fmt'] ?? '') . '|' . ($data['mode'] ?? ''), TDR_SECRET);
if (!hash_equals($sig, (string)($data['sig'] ?? ''))) {
    http_response_code(403); exit('Signature invalide.');
}

require_once __DIR__ . '/core/tdr_generator.php';

$formation = [
    'name'        => (string)($data['nom']   ?? ''),
    'category'    => (string)($data['cat']   ?? ''),
    'slug'        => (string)($data['slug']  ?? ''),
    'price'       => (int)($data['prix']     ?? 0),
    'description' => (string)($data['desc'] ?? ''),
    'niveau_id'   => isset($data['nid']) && (int)$data['nid'] > 0 ? (int)$data['nid'] : null,
];
$opts = [
    'mode_formation'   => (string)($data['mode'] ?? 'en_ligne'),
    'format_formation' => (string)($data['fmt']  ?? 'individuel'),
    'date_debut'       => (string)($data['date'] ?? ''),
    'creneau'          => (string)($data['cren'] ?? ''),
];
$prospect = (string)($data['prospect'] ?? '');

header('Content-Type: text/html; charset=UTF-8');

$tdrHtml = generate_tdr_html($formation, $prospect, $opts);

$nomEsc  = htmlspecialchars($formation['name'], ENT_QUOTES, 'UTF-8');
$nomSlug = preg_replace('/[^a-z0-9]+/', '-', strtolower($formation['name']));
$nomSlug = trim($nomSlug, '-') ?: 'tdr-ibig';
$waUrl   = 'https://wa.me/2250778882592?text=' . rawurlencode('Bonjour IBIG EDUFORM, j\'ai reçu mon TDR pour la formation "' . $formation['name'] . '" et je souhaite confirmer mon inscription.');

/* URL de téléchargement PDF sécurisé (mPDF serveur) */
$pdfUrl = '/tdr-pdf.php?t=' . urlencode($token);

$printBar = '
<style>
.tdr-topbar{
  position:fixed; top:0; left:0; right:0; z-index:9999;
  background:#0a1733; color:#fff; padding:0 20px;
  display:flex; align-items:center; gap:12px; height:56px;
  font-family:Arial,sans-serif; font-size:13px; box-shadow:0 3px 16px rgba(0,0,0,.5);
}
.tdr-topbar-title { flex:1; font-size:13px; font-weight:700; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.btn-pdf {
  background:#f59e0b; color:#0a1733; font-weight:900; border:none;
  padding:10px 22px; border-radius:8px; cursor:pointer; font-size:13px;
  text-decoration:none; display:inline-flex; align-items:center; gap:7px;
  white-space:nowrap; flex-shrink:0;
}
.btn-pdf:hover { background:#d97706; color:#fff; }
.btn-wa {
  background:#25d366; color:#fff; font-weight:700; border:none;
  padding:10px 18px; border-radius:8px; font-size:13px;
  text-decoration:none; display:inline-flex; align-items:center; gap:6px;
  white-space:nowrap; flex-shrink:0;
}
.tdr-dl-banner {
  background:linear-gradient(135deg,#0a1733,#1e3a6e);
  color:#fff; padding:18px 30px; margin-bottom:0;
  display:flex; align-items:center; gap:16px; flex-wrap:wrap;
  font-family:Arial,sans-serif;
}
.tdr-dl-banner-text { flex:1; min-width:200px; }
.tdr-dl-banner-text h3 { margin:0 0 4px; font-size:15px; }
.tdr-dl-banner-text p  { margin:0; font-size:12px; opacity:.75; }
.btn-pdf-big {
  background:#f59e0b; color:#0a1733; font-weight:900;
  padding:14px 32px; border-radius:10px; font-size:15px;
  text-decoration:none; display:inline-flex; align-items:center; gap:9px;
  white-space:nowrap; box-shadow:0 4px 18px rgba(245,158,11,.4);
  border:none; cursor:pointer;
}
.btn-pdf-big:hover { background:#d97706; color:#fff; }
body { padding-top:56px; }
@media print { .tdr-topbar,.tdr-dl-banner { display:none!important; } body { padding-top:0!important; } }
@media(max-width:600px) { .tdr-topbar { height:auto; flex-wrap:wrap; padding:10px; } body { padding-top:80px; } }
</style>

<!-- Barre fixe en haut -->
<div class="tdr-topbar">
  <span class="tdr-topbar-title">📄 TDR — ' . $nomEsc . '</span>
  <a class="btn-pdf" href="' . htmlspecialchars($pdfUrl, ENT_QUOTES) . '" download>
    ⬇ Télécharger PDF
  </a>
  <a class="btn-wa" href="' . $waUrl . '" target="_blank" rel="noopener">
    💬 WhatsApp
  </a>
</div>

<!-- Bannière de téléchargement au-dessus du TDR -->
<div class="tdr-dl-banner">
  <div class="tdr-dl-banner-text">
    <h3>📄 Votre programme de formation est prêt</h3>
    <p>Cliquez sur le bouton pour télécharger le PDF sécurisé sur votre ordinateur.</p>
  </div>
  <a class="btn-pdf-big" href="' . htmlspecialchars($pdfUrl, ENT_QUOTES) . '" download>
    ⬇&nbsp;&nbsp;Télécharger mon TDR en PDF
  </a>
  <a class="btn-wa" href="' . $waUrl . '" target="_blank" rel="noopener" style="padding:14px 22px;font-size:14px">
    💬&nbsp;Confirmer sur WhatsApp
  </a>
</div>';

/* Injecte la barre d'impression après <body ...> (robuste aux attributs) */
$tdrHtml = preg_replace('/<body([^>]*)>/i', '<body$1>' . $printBar, $tdrHtml, 1);

echo $tdrHtml;
