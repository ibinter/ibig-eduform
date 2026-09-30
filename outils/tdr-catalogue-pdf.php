<?php
declare(strict_types=1);
/**
 * IBIG EDUFORM — /outils/tdr-catalogue-pdf.php
 * Génère le TDR officiel d'une formation du catalogue (identique au TDR public),
 * sans inscription, pour usage admin uniquement.
 * Protégé par .htpasswd du dossier /outils/.
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../core/tdr_generator.php';

/* Libère immédiatement le verrou de session si une session est active
   (évite le blocage des workers PHP-FPM pendant la génération PDF) */
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

/* Répertoire temp dédié au projet (évite les conflits sur hébergement mutualisé) */
$mpdfTempDir = __DIR__ . '/../uploads/tmp_mpdf';
if (!is_dir($mpdfTempDir)) {
    @mkdir($mpdfTempDir, 0755, true);
}
if (!is_writable($mpdfTempDir)) {
    $mpdfTempDir = sys_get_temp_dir();
}

$slug = trim(strip_tags((string)($_GET['slug'] ?? '')));
$mode = trim($_GET['mode'] ?? 'en_ligne');   // en_ligne | presentiel | hybride
$fmt  = trim($_GET['fmt']  ?? 'individuel'); // individuel | groupe_3_5 | groupe_6_10 | groupe_10p

if ($slug === '') {
    http_response_code(400);
    exit('<p style="font-family:sans-serif;text-align:center;margin-top:60px">Paramètre <code>slug</code> manquant.</p>');
}

/* ── Récupère la formation depuis l'API (avec cache fichier 10 min) ── */
$cacheFile  = sys_get_temp_dir() . '/ibig_catalogue_cache.json';
$formations = [];

if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 600) {
    $cached = json_decode(file_get_contents($cacheFile), true);
    $formations = $cached['formations'] ?? [];
} else {
    $ctx = stream_context_create([
        'http' => ['timeout' => 8, 'ignore_errors' => true, 'method' => 'GET',
                   'header'  => "Accept: application/json\r\n"],
        'ssl'  => ['verify_peer' => true],
    ]);
    $json = @file_get_contents('https://www.ibigpartners.com/api/catalogue', false, $ctx);
    if ($json) {
        $data = json_decode($json, true);
        if (!empty($data['ok']) && isset($data['formations'])) {
            $formations = $data['formations'];
            file_put_contents($cacheFile, $json);
        }
    }
}

/* ── Trouve la formation par slug ── */
$found = null;
foreach ($formations as $f) {
    if (($f['slug'] ?? '') === $slug) { $found = $f; break; }
}

if (!$found) {
    http_response_code(404);
    exit('<p style="font-family:sans-serif;text-align:center;margin-top:60px;color:#dc2626">Formation introuvable : <strong>' . htmlspecialchars($slug) . '</strong></p>');
}

/* Extrait prix_pres depuis la grille API si disponible */
$apiGrille   = $found['grille'] ?? [];
$apiPrixPres = 0;
$apiPrixHyb  = 0;
foreach ($apiGrille as $_gl) {
    $_lbl = strtolower((string)($_gl['label'] ?? ''));
    if ($apiPrixPres === 0 && strpos($_lbl, 'individu') !== false && strpos($_lbl, 'sent') !== false)
        $apiPrixPres = (int)($_gl['price'] ?? 0);
    if ($apiPrixHyb === 0 && strpos($_lbl, 'hybri') !== false && strpos($_lbl, 'individu') !== false)
        $apiPrixHyb = (int)($_gl['price'] ?? 0);
}
/* Extrait duree depuis la description : "(XXh)" */
$apiDuree = '';
if (preg_match('/\((\d+)\s*h\)/i', (string)($found['description'] ?? ''), $_dm))
    $apiDuree = $_dm[1] . 'H';

$formation = [
    'name'        => (string)($found['name']        ?? ''),
    'category'    => (string)($found['category']    ?? ''),
    'slug'        => (string)($found['slug']        ?? ''),
    'price'       => (int)   ($found['price']       ?? 0),
    'description' => (string)($found['description'] ?? ''),
    'prix_pres'   => $apiPrixPres,
    'prix_hyb'    => $apiPrixHyb,
    'duree'       => $apiDuree,
];

$opts = [
    'mode_formation'   => in_array($mode, ['en_ligne','presentiel','hybride']) ? $mode : 'en_ligne',
    'format_formation' => in_array($fmt, ['individuel','groupe_3_5','groupe_6_10','groupe_10p','groupe_devis']) ? $fmt : 'individuel',
    'date_debut'       => '',
    'creneau'          => '',
];

/* ── Génère le HTML (même fonction que le TDR public) ── */
$tdrHtml = generate_tdr_html($formation, '', $opts);

if (preg_match('/<body[^>]*>(.*)<\/body>/si', $tdrHtml, $bm)) {
    $bodyContent = $bm[1];
} else {
    $bodyContent = $tdrHtml;
}

$cssContent = '';
if (preg_match('/<style[^>]*>(.*?)<\/style>/si', $tdrHtml, $cm)) {
    $cssContent = $cm[1];
}
$cssContent .= '
html, body { margin:0; padding:0; background:#ffffff !important; font-family: Arial, Helvetica, sans-serif; }
.wrap { max-width:100%; margin:0; background:#ffffff !important; border:none !important; box-shadow:none !important; }
.sec { padding: 0 10px 14px; }
.fiche tr td { font-size: 11px; padding: 5px 8px; }
.tbl td, .tbl th { font-size: 10px; padding: 5px 7px; }
.sec p { font-size: 11px; }
.sec ul li { font-size: 11px; }
.tdr-title { font-size: 17px; }
.tdr-nom { font-size: 14px; }
';

/* ── Logos base64 ── */
$logoPath = __DIR__ . '/../assets/images/logo.png';
$logoB64  = is_file($logoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath)) : '';

$logoSarlPath = __DIR__ . '/../assets/images/logo-ibig-sarl.jpg';
$logoSarlB64  = is_file($logoSarlPath) ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($logoSarlPath)) : '';

$headerLeft  = $logoB64
    ? '<img src="' . $logoB64 . '" style="height:32px;vertical-align:middle">'
    : '<span style="font-size:13px;font-weight:bold;color:#0a1733">IBIG EDUFORM</span>';
$headerRight = $logoSarlB64
    ? '<img src="' . $logoSarlB64 . '" style="height:32px;vertical-align:middle">'
    : '<span style="font-size:9px;color:#6b7280;font-weight:bold">IBIG SARL</span>';

$mpdfHeader = '
<table width="100%" style="border-bottom:2px solid #f59e0b;padding-bottom:5px;margin-bottom:4px">
  <tr>
    <td style="width:50%;vertical-align:middle">' . $headerLeft . '</td>
    <td style="width:50%;text-align:right;vertical-align:middle">' . $headerRight . '</td>
  </tr>
</table>';

$mpdfFooter = '
<table width="100%" style="border-top:1px solid #e5e7eb;padding-top:4px">
  <tr>
    <td style="font-size:8px;color:#9ca3af">IBIG EDUFORM — Document confidentiel</td>
    <td style="text-align:right;font-size:8px;color:#9ca3af">Page {PAGENO} / {nbpg}</td>
  </tr>
</table>';

$nomSlug  = preg_replace('/[^a-z0-9]+/', '-', strtolower($formation['name']));
$nomSlug  = trim($nomSlug, '-') ?: 'tdr-ibig';
$filename = 'TDR-IBIG-EDUFORM-' . strtoupper($nomSlug) . '.pdf';

try {
    $mpdf = new \Mpdf\Mpdf([
        'mode'          => 'utf-8',
        'format'        => 'A4',
        'margin_top'    => 28,
        'margin_bottom' => 22,
        'margin_left'   => 14,
        'margin_right'  => 14,
        'margin_header' => 5,
        'margin_footer' => 5,
        'default_font'  => 'Arial',
        'tempDir'       => $mpdfTempDir,
    ]);

    $mpdf->SetProtection(['print','print-highres'], '', 'IBIG@Eduform2026#Secure', 128);
    $mpdf->SetTitle('TDR — ' . $formation['name'] . ' — IBIG EDUFORM');
    $mpdf->SetAuthor('IBIG EDUFORM — Institut de Formation Professionnelle');
    $mpdf->SetCreator('ibig-eduform.com');
    $mpdf->SetSubject('Termes de Référence (TDR) — Parcours certifiant');
    $mpdf->SetKeywords('TDR, formation, IBIG EDUFORM, OHADA, certification');

    $mpdf->SetWatermarkText('IBIG EDUFORM');
    $mpdf->showWatermarkText  = true;
    $mpdf->watermarkTextAlpha = 0.025;
    $mpdf->watermark_font     = 'Arial';

    $mpdf->SetHTMLHeader($mpdfHeader);
    $mpdf->SetHTMLFooter($mpdfFooter);
    $mpdf->WriteHTML($cssContent, \Mpdf\HTMLParserMode::HEADER_CSS);
    $mpdf->WriteHTML($bodyContent, \Mpdf\HTMLParserMode::HTML_BODY);
    $mpdf->Output($filename, \Mpdf\Output\Destination::DOWNLOAD);
    exit;

} catch (\Throwable $e) {
    error_log('[TDR_CATALOGUE_PDF] ' . $e->getMessage());
    http_response_code(500);
    exit('<p style="font-family:sans-serif;text-align:center;margin-top:60px;color:#dc2626">Erreur PDF : ' . htmlspecialchars($e->getMessage()) . '</p>');
}
