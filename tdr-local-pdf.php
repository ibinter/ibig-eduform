<?php
declare(strict_types=1);
/**
 * IBIG EDUFORM — /tdr-local-pdf.php
 * Génère le TDR officiel d'une formation stockée dans la DB locale (non issue de l'API ibigpartners).
 * Accessible publiquement (identique au TDR public de formation-detail.php).
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/core/tdr_generator.php';
require_once __DIR__ . '/core/secrets.php';

if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

$mpdfTempDir = __DIR__ . '/uploads/tmp_mpdf';
if (!is_dir($mpdfTempDir)) {
    @mkdir($mpdfTempDir, 0755, true);
}
if (!is_writable($mpdfTempDir)) {
    $mpdfTempDir = sys_get_temp_dir();
}

$slug     = trim(strip_tags((string)($_GET['slug'] ?? '')));
$mode     = trim($_GET['mode'] ?? 'en_ligne');
$fmt      = trim($_GET['fmt']  ?? 'individuel');
$niv_get  = trim(strtolower((string)($_GET['niveau'] ?? '')));  // debutant|intermediaire|expert

if ($slug === '') {
    http_response_code(400);
    exit('<p style="font-family:sans-serif;text-align:center;margin-top:60px">Paramètre <code>slug</code> manquant.</p>');
}

/* ── Récupère la formation dans la DB locale ── */
try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (\PDOException $e) {
    http_response_code(500);
    exit('<p style="font-family:sans-serif;text-align:center;margin-top:60px;color:#dc2626">Erreur DB.</p>');
}

$stmt = $pdo->prepare(
    "SELECT titre, domaine, description, tarif_en_ligne, tarif_presentiel, duree, mode, slug
     FROM formations WHERE slug = ? AND statut = 'active' LIMIT 1"
);
$stmt->execute([$slug]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    http_response_code(404);
    exit('<p style="font-family:sans-serif;text-align:center;margin-top:60px;color:#dc2626">Formation introuvable : <strong>' . htmlspecialchars($slug) . '</strong></p>');
}

/* Mapping domaine DB → category affichée (même logique que catalogue-formations.php) */
$domMap = [
    'Comptabilité & Finance'   => 'Comptabilité & Finance',
    'Ressources Humaines'      => 'GRH',
    'Assistanat'               => 'Direction & Administration',
    'Management & Leadership'  => 'Management & Leadership',
    'Marketing & Commercial'   => 'Marketing & Commercial',
    'Logistique & Supply Chain'=> 'Logistique & Supply Chain',
    'Informatique & Digital'   => 'Informatique & Digital',
    'Droit des Affaires'       => 'Droit des Affaires',
    'IA & Digitalisation'      => 'IA & Digitalisation',
    'Digital & IA'             => 'IA & Digitalisation',
    'Intelligence Artificielle'=> 'IA & Digitalisation',
];
$domaine  = (string)($row['domaine'] ?? '');
$category = $domMap[$domaine] ?? $domaine;

/* ── Charger le niveau si demandé ── */
$niveau_row = null;
if (in_array($niv_get, ['debutant', 'intermediaire', 'expert'])) {
    // Récupérer l'id de la formation d'abord
    $stmt_fid = $pdo->prepare("SELECT id FROM formations WHERE slug = ? LIMIT 1");
    $stmt_fid->execute([$slug]);
    $fid = (int)($stmt_fid->fetchColumn() ?: 0);
    if ($fid) {
        $stmt_niv = $pdo->prepare("
            SELECT n.id AS niveau_id, n.duree_heures, n.tarif_en_ligne, n.tarif_presentiel,
                   n.objectifs, n.prerequis, n.public_cible
            FROM formation_niveaux n
            WHERE n.formation_id = :fid AND n.niveau = :niv AND n.statut = 'actif'
            LIMIT 1
        ");
        $stmt_niv->execute([':fid' => $fid, ':niv' => $niv_get]);
        $niveau_row = $stmt_niv->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}

$prix_base = (int)($row['tarif_en_ligne'] > 0 ? $row['tarif_en_ligne'] : ($row['tarif_presentiel'] ?? 0));
$prix_pres_base = (int)($row['tarif_presentiel'] ?? 0);
$duree_base = (string)($row['duree'] ?? '');

if ($niveau_row) {
    if ($niveau_row['tarif_en_ligne'] > 0)   $prix_base      = (int)$niveau_row['tarif_en_ligne'];
    if ($niveau_row['tarif_presentiel'] > 0) $prix_pres_base = (int)$niveau_row['tarif_presentiel'];
    if ($niveau_row['duree_heures'] > 0)     $duree_base     = $niveau_row['duree_heures'] . 'H';
}

$formation = [
    'name'             => (string)($row['titre']          ?? ''),
    'category'         => $category,
    'slug'             => (string)($row['slug']            ?? ''),
    'price'            => $prix_base,
    'description'      => (string)($row['description']     ?? ''),
    'prix_pres'        => $prix_pres_base,
    'duree'            => $duree_base,
    /* Données niveau */
    'niveau'           => $niv_get,
    'niveau_id'        => $niveau_row ? (int)$niveau_row['niveau_id'] : null,
    'objectifs_niveau' => $niveau_row['objectifs']   ?? '',
    'prerequis_niveau' => $niveau_row['prerequis']   ?? '',
    'public_niveau'    => $niveau_row['public_cible'] ?? '',
];

$opts = [
    'mode_formation'   => in_array($mode, ['en_ligne', 'presentiel', 'hybride']) ? $mode : 'en_ligne',
    'format_formation' => in_array($fmt, ['individuel', 'groupe_3_5', 'groupe_6_10', 'groupe_10p', 'groupe_devis']) ? $fmt : 'individuel',
    'date_debut'       => '',
    'creneau'          => '',
];

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

$logoPath    = __DIR__ . '/assets/images/logo.png';
$logoB64     = is_file($logoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath)) : '';
$logoSarlPath = __DIR__ . '/assets/images/logo-ibig-sarl.jpg';
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

    $mpdf->SetProtection(['print', 'print-highres'], '', 'IBIG@Eduform2026#Secure', 128);
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
    /* Générer le PDF en mémoire AVANT d'envoyer les headers pour que le catch puisse renvoyer du HTML */
    $pdfContent = $mpdf->Output($filename, \Mpdf\Output\Destination::STRING_RETURN);
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . $filename . '"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    echo $pdfContent;
    exit;

} catch (\Throwable $e) {
    error_log('[TDR_LOCAL_PDF] ' . $e->getMessage());
    http_response_code(500);
    exit('<p style="font-family:sans-serif;text-align:center;margin-top:60px;color:#dc2626">Erreur PDF : ' . htmlspecialchars($e->getMessage()) . '</p>');
}
