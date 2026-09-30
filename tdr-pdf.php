<?php
declare(strict_types=1);
/**
 * IBIG EDUFORM — tdr-pdf.php
 * Génère et télécharge le TDR en PDF sécurisé via mPDF.
 * Accès par token signé (même que tdr-download.php).
 * PDF : filigrane, logos, chiffrement 128-bit, interdiction de copie/modification.
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/core/config.php';
require_once __DIR__ . '/core/tdr_generator.php';

/* ── Validation token ── */
$token = trim((string)($_GET['t'] ?? ''));
if ($token === '') { http_response_code(404); exit('Lien invalide.'); }

$payload64 = str_replace(['-','_'],['+','/'], $token);
$pad = strlen($payload64) % 4;
if ($pad) $payload64 .= str_repeat('=', 4 - $pad);
$json = base64_decode($payload64, true);
if ($json === false) { http_response_code(403); exit('Token invalide.'); }

$data = json_decode($json, true);
if (!is_array($data)) { http_response_code(403); exit('Token corrompu.'); }

if (!isset($data['exp']) || time() > (int)$data['exp']) {
    http_response_code(410);
    exit('<p style="font-family:sans-serif;text-align:center;margin-top:80px;color:#dc2626">Ce lien a expiré (validité 72 heures).<br>Contactez-nous : <a href="mailto:formation@ibig-eduform.com">formation@ibig-eduform.com</a></p>');
}

$sig = hash_hmac('sha256', $data['slug'] . '|' . $data['exp'] . '|' . ($data['fmt'] ?? '') . '|' . ($data['mode'] ?? ''), TDR_SECRET);
if (!hash_equals($sig, (string)($data['sig'] ?? ''))) {
    http_response_code(403); exit('Signature invalide.');
}

/* ── Données formation ── */
$nid = isset($data['nid']) && (int)$data['nid'] > 0 ? (int)$data['nid'] : null;

$formation = [
    'name'             => (string)($data['nom']  ?? ''),
    'category'         => (string)($data['cat']  ?? ''),
    'slug'             => (string)($data['slug'] ?? ''),
    'price'            => (int)($data['prix']    ?? 0),
    'description'      => (string)($data['desc'] ?? ''),
    'prix_pres'        => 0,
    'prix_hyb'         => 0,
    'duree'            => '',
    'niveau'           => '',
    'niveau_id'        => $nid,
    'objectifs_niveau' => '',
    'prerequis_niveau' => '',
    'public_niveau'    => '',
];

/* Enrichit depuis la DB si un niveau est référencé dans le token */
if ($nid) {
    try {
        $pdoEnrich = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER, DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $stmt = $pdoEnrich->prepare("
            SELECT n.niveau, n.duree_heures, n.tarif_en_ligne, n.tarif_presentiel, n.tarif_hybride,
                   n.objectifs, n.prerequis, n.public_cible
            FROM formation_niveaux n
            WHERE n.id = :nid AND n.statut = 'actif'
            LIMIT 1
        ");
        $stmt->execute([':nid' => $nid]);
        $nr = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($nr) {
            if ((int)$nr['tarif_en_ligne']         > 0) $formation['price']    = (int)$nr['tarif_en_ligne'];
            if ((int)$nr['tarif_presentiel']        > 0) $formation['prix_pres'] = (int)$nr['tarif_presentiel'];
            if ((int)($nr['tarif_hybride'] ?? 0)    > 0) $formation['prix_hyb'] = (int)$nr['tarif_hybride'];
            if ((int)$nr['duree_heures']            > 0) $formation['duree']    = $nr['duree_heures'] . 'H';
            $formation['niveau']           = (string)($nr['niveau']      ?? '');
            $formation['objectifs_niveau'] = (string)($nr['objectifs']   ?? '');
            $formation['prerequis_niveau'] = (string)($nr['prerequis']   ?? '');
            $formation['public_niveau']    = (string)($nr['public_cible'] ?? '');
        }
    } catch (\Throwable $e) {
        error_log('[TDR_PDF] DB enrich: ' . $e->getMessage());
    }
}
$opts = [
    'mode_formation'   => (string)($data['mode'] ?? 'en_ligne'),
    'format_formation' => (string)($data['fmt']  ?? 'individuel'),
    'date_debut'       => (string)($data['date'] ?? ''),
    'creneau'          => (string)($data['cren'] ?? ''),
];
$prospect = (string)($data['prospect'] ?? '');

/* ── Génère le HTML du TDR ── */
$tdrHtml = generate_tdr_html($formation, $prospect, $opts);

/* ── Extrait le contenu <body> pour mPDF ── */
if (preg_match('/<body[^>]*>(.*)<\/body>/si', $tdrHtml, $bm)) {
    $bodyContent = $bm[1];
} else {
    $bodyContent = $tdrHtml;
}

/* ── Extrait le CSS du TDR ── */
$cssContent = '';
if (preg_match('/<style[^>]*>(.*?)<\/style>/si', $tdrHtml, $cm)) {
    $cssContent = $cm[1];
}
/* Corrections CSS pour mPDF */
$cssContent .= '
html, body { margin:0; padding:0; background:#ffffff !important; font-family: Arial, Helvetica, sans-serif; }
.wrap { max-width:100%; margin:0; background:#ffffff !important; border:none !important; box-shadow:none !important; border-radius:0 !important; }
.sec { padding: 0 10px 14px; }
.fiche tr td { font-size: 11px; padding: 5px 8px; }
.tbl td, .tbl th { font-size: 10px; padding: 5px 7px; }
.sec p { font-size: 11px; }
.sec ul li { font-size: 11px; }
.tdr-title { font-size: 17px; }
.tdr-nom { font-size: 14px; }
';

/* ── Logos en base64 pour mPDF ── */
$logoPath = __DIR__ . '/assets/images/logo.png';
$logoB64  = '';
if (is_file($logoPath)) {
    $logoB64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
}

$logoSarlPath = __DIR__ . '/assets/images/logo-ibig-sarl.jpg';
$logoSarlB64  = '';
if (is_file($logoSarlPath)) {
    $logoSarlB64 = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($logoSarlPath));
}

/* ── HTML header mPDF (logos + ligne de séparation) ── */
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
    <td style="font-size:8px;color:#9ca3af">
      IBIG EDUFORM — Document confidentiel
    </td>
    <td style="text-align:right;font-size:8px;color:#9ca3af">
      Page {PAGENO} / {nbpg}
    </td>
  </tr>
</table>';

/* ── Nom du fichier PDF ── */
$nomSlug = preg_replace('/[^a-z0-9]+/', '-', strtolower($formation['name']));
$nomSlug = trim($nomSlug, '-') ?: 'tdr-ibig';
$filename = 'TDR-IBIG-EDUFORM-' . strtoupper($nomSlug) . '.pdf';

/* ── Initialisation mPDF ── */
try {
    $mpdf = new \Mpdf\Mpdf([
        'mode'            => 'utf-8',
        'format'          => 'A4',
        'margin_top'      => 28,
        'margin_bottom'   => 22,
        'margin_left'     => 14,
        'margin_right'    => 14,
        'margin_header'   => 5,
        'margin_footer'   => 5,
        'default_font'    => 'Arial',
        'tempDir'         => sys_get_temp_dir(),
    ]);

    /* ── Sécurité PDF 128-bit ── */
    /* Permissions AUTORISÉES uniquement : impression
       Tout le reste (copie, modification, extraction, assemblage) est BLOQUÉ */
    $mpdf->SetProtection(
        ['print', 'print-highres'],
        '',
        'IBIG@Eduform2026#Secure',
        128
    );

    /* ── Métadonnées PDF ── */
    $mpdf->SetTitle('TDR — ' . $formation['name'] . ' — IBIG EDUFORM');
    $mpdf->SetAuthor('IBIG EDUFORM — Institut de Formation Professionnelle');
    $mpdf->SetCreator('ibig-eduform.com');
    $mpdf->SetSubject('Termes de Référence (TDR) — Parcours certifiant');
    $mpdf->SetKeywords('TDR, formation, IBIG EDUFORM, OHADA, certification');

    /* ── Filigrane diagonal ── */
    $mpdf->SetWatermarkText('IBIG EDUFORM');
    $mpdf->showWatermarkText  = true;
    $mpdf->watermarkTextAlpha = 0.025;
    $mpdf->watermark_font     = 'Arial';

    /* ── En-tête & Pied de page ── */
    $mpdf->SetHTMLHeader($mpdfHeader);
    $mpdf->SetHTMLFooter($mpdfFooter);

    /* ── CSS ── */
    $mpdf->WriteHTML($cssContent, \Mpdf\HTMLParserMode::HEADER_CSS);

    /* ── Contenu TDR ── */
    $mpdf->WriteHTML($bodyContent, \Mpdf\HTMLParserMode::HTML_BODY);

    /* ── Sauvegarde copie serveur (pour l'équipe commerciale) ── */
    try {
        $copyDir = __DIR__ . '/uploads/tdr-copies/';
        if (!is_dir($copyDir)) { @mkdir($copyDir, 0755, true); }

        $copyName = date('Ymd_His') . '_' . preg_replace('/[^a-z0-9\-]/', '', strtolower($nomSlug)) . '_' . substr(md5($token), 0, 6) . '.pdf';
        $copyPath = $copyDir . $copyName;

        $mpdf->Output($copyPath, \Mpdf\Output\Destination::FILE);

        /* Enregistrement en base */
        require_once __DIR__ . '/core/database.php';
        $pdo = \Database::connect();
        $pdo->prepare("
            INSERT INTO tdr_copies
              (preinscription_id, prospect, formation_slug, formation_nom,
               mode_formation, format_formation, prix, pdf_path, ip_address, downloaded_at)
            VALUES
              (:pid, :prospect, :slug, :nom,
               :mode, :fmt, :prix, :pdf, :ip, NOW())
        ")->execute([
            ':pid'      => isset($data['pid']) && $data['pid'] > 0 ? (int)$data['pid'] : null,
            ':prospect' => $prospect,
            ':slug'     => $formation['slug'],
            ':nom'      => $formation['name'],
            ':mode'     => in_array($opts['mode_formation'], ['en_ligne','presentiel','hybride']) ? $opts['mode_formation'] : 'en_ligne',
            ':fmt'      => substr($opts['format_formation'], 0, 50),
            ':prix'     => $formation['price'],
            ':pdf'      => 'uploads/tdr-copies/' . $copyName,
            ':ip'       => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    } catch (\Throwable $eCopy) {
        error_log('[TDR_PDF] Copie non sauvegardée : ' . $eCopy->getMessage());
    }

    /* ── Génération & téléchargement ── */
    $mpdf->Output($filename, \Mpdf\Output\Destination::DOWNLOAD);
    exit;

} catch (\Throwable $e) {
    error_log('[TDR_PDF] mPDF error: ' . $e->getMessage());
    http_response_code(500);
    exit('<p style="font-family:sans-serif;text-align:center;margin-top:80px;color:#dc2626">
        Erreur de génération PDF. Veuillez réessayer ou contacter
        <a href="mailto:formation@ibig-eduform.com">formation@ibig-eduform.com</a>.
        <br><small>(' . htmlspecialchars($e->getMessage()) . ')</small>
    </p>');
}
