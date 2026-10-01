<?php
declare(strict_types=1);
/**
 * IBIG EDUFORM — /apprenant/certificat-pdf.php
 * Génère et télécharge le certificat de participation d'un apprenant
 * pour une inscription confirmée ou inscrite.
 */

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/apprenant_auth.php';
require_once __DIR__ . '/../vendor/autoload.php';

apprenant_require();

$apprenant = apprenant_get();
$email     = strtolower($apprenant['email']);
$pdo       = Database::connect();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { http_response_code(400); exit('Paramètre manquant.'); }

/* ── Vérifie que l'inscription appartient à cet apprenant ── */
$stmt = $pdo->prepare("
    SELECT p.id, p.nom, p.prenoms, p.statut, p.created_at,
           p.mode_formation,
           f.titre AS formation_titre, f.date_debut, f.date_fin, f.duree,
           f.slug
    FROM preinscriptions p
    LEFT JOIN formations f ON f.id = p.formation_id
    WHERE p.id = ? AND LOWER(p.email) = ?
    LIMIT 1
");
$stmt->execute([$id, $email]);
$p = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$p) { http_response_code(403); exit('Inscription introuvable.'); }

$statutOk = in_array(strtolower((string)($p['statut'] ?? '')), ['confirme','confirme','inscrit','valide'], true);
if (!$statutOk) {
    http_response_code(403);
    exit('Certificat disponible uniquement pour les formations confirmées.');
}

/* ── Données ── */
$nomComplet  = trim((string)$p['prenoms'] . ' ' . (string)$p['nom']);
$titreF      = (string)($p['formation_titre'] ?? 'Formation IBIG EDUFORM');
$dateDebut   = $p['date_debut']  ? date('d/m/Y', strtotime((string)$p['date_debut']))  : '';
$dateFin     = $p['date_fin']    ? date('d/m/Y', strtotime((string)$p['date_fin']))    : '';
$duree       = (string)($p['duree'] ?? '');
$mode        = match(strtolower((string)($p['mode_formation'] ?? ''))) {
    'presentiel'  => 'Présentiel',
    'en_ligne'    => 'En ligne',
    'hybride'     => 'Hybride',
    'elearning'   => 'E-learning',
    default       => '',
};
$dateCert    = date('d/m/Y');
$annee       = date('Y');
$codeCert    = 'IBIG-CERT-' . $annee . '-' . str_pad((string)$p['id'], 5, '0', STR_PAD_LEFT);

$periodeTxt = '';
if ($dateDebut && $dateFin)   $periodeTxt = "du {$dateDebut} au {$dateFin}";
elseif ($dateDebut)            $periodeTxt = "à partir du {$dateDebut}";

/* ── Logos base64 ── */
$logoPath = __DIR__ . '/../assets/images/logo.png';
$logoB64  = is_file($logoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath)) : '';

$logoSarlPath = __DIR__ . '/../assets/images/logo-ibig-sarl.jpg';
$logoSarlB64  = is_file($logoSarlPath) ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($logoSarlPath)) : '';

/* ── HTML du certificat ── */
$logoTag = $logoB64
    ? '<img src="' . $logoB64 . '" style="height:60px">'
    : '<span style="font-size:1.4rem;font-weight:900;color:#0a1733">IBIG EDUFORM</span>';

$logoSarlTag = $logoSarlB64
    ? '<img src="' . $logoSarlB64 . '" style="height:50px">'
    : '<span style="font-size:.75rem;font-weight:700;color:#6b7280">IBIG SARL</span>';

$html = '<!DOCTYPE html>
<html lang="fr">
<head><meta charset="utf-8">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family: Arial, Helvetica, sans-serif; background:#fff; color:#0a1733; }
.cert-page { width:210mm; min-height:148mm; padding:0; }
.cert-border { border:10px solid #0a1733; margin:8mm; min-height:132mm; display:flex; flex-direction:column; position:relative; overflow:hidden; }
.cert-inner-border { border:2px solid #f59e0b; margin:5px; flex:1; display:flex; flex-direction:column; padding:10mm 14mm; }

/* Coins décoratifs */
.corner { position:absolute; width:18mm; height:18mm; }
.corner-tl { top:0; left:0; border-top:4px solid #f59e0b; border-left:4px solid #f59e0b; }
.corner-tr { top:0; right:0; border-top:4px solid #f59e0b; border-right:4px solid #f59e0b; }
.corner-bl { bottom:0; left:0; border-bottom:4px solid #f59e0b; border-left:4px solid #f59e0b; }
.corner-br { bottom:0; right:0; border-bottom:4px solid #f59e0b; border-right:4px solid #f59e0b; }

/* Filigranne */
.watermark { position:absolute; top:50%; left:50%; transform:translate(-50%,-50%) rotate(-25deg); font-size:80px; font-weight:900; color:rgba(245,158,11,.04); white-space:nowrap; pointer-events:none; z-index:0; }

.cert-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:8mm; }
.cert-body   { flex:1; text-align:center; z-index:1; position:relative; }
.cert-footer { margin-top:6mm; border-top:1px solid #e5e7eb; padding-top:5mm; display:flex; justify-content:space-between; align-items:flex-end; }

.cert-title { font-size:28px; font-weight:900; color:#0a1733; text-transform:uppercase; letter-spacing:3px; margin-bottom:4mm; }
.cert-ribbon { display:inline-block; background:#f59e0b; color:#0a1733; font-size:9px; font-weight:900; text-transform:uppercase; letter-spacing:2px; padding:3px 18px; border-radius:999px; margin-bottom:6mm; }
.cert-words  { font-size:13px; color:#475569; margin-bottom:5mm; line-height:1.6; }
.cert-name   { font-size:32px; font-weight:900; color:#0a1733; border-bottom:2px solid #f59e0b; display:inline-block; padding-bottom:3px; margin-bottom:5mm; font-style:italic; }
.cert-for    { font-size:12px; color:#475569; margin-bottom:3mm; }
.cert-formation { font-size:18px; font-weight:900; color:#1d4ed8; margin-bottom:3mm; }
.cert-details { font-size:11px; color:#6b7280; }

.sign-line { border-top:1px solid #0a1733; width:120px; margin:0 auto 2mm; }
.sign-name { font-size:10px; font-weight:700; }
.sign-role { font-size:9px; color:#6b7280; }
.cert-code { font-size:9px; color:#94a3b8; font-family:monospace; }
</style>
</head>
<body>
<div class="cert-page">
  <div class="cert-border">
    <div class="corner corner-tl"></div>
    <div class="corner corner-tr"></div>
    <div class="corner corner-bl"></div>
    <div class="corner corner-br"></div>
    <div class="watermark">IBIG EDUFORM</div>

    <div class="cert-inner-border">
      <!-- En-tête -->
      <div class="cert-header">
        ' . $logoTag . '
        ' . $logoSarlTag . '
      </div>

      <!-- Corps -->
      <div class="cert-body">
        <div class="cert-title">Certificat de Participation</div>
        <div class="cert-ribbon">Institut de Formation Professionnelle</div>
        <p class="cert-words">Ce certificat est décerné à</p>
        <div class="cert-name">' . htmlspecialchars($nomComplet, ENT_QUOTES) . '</div>
        <p class="cert-for">pour avoir suivi et complété avec succès la formation</p>
        <div class="cert-formation">' . htmlspecialchars($titreF, ENT_QUOTES) . '</div>
        <p class="cert-details">
          ' . ($periodeTxt ? htmlspecialchars($periodeTxt, ENT_QUOTES) : '') . '
          ' . ($duree ? ' &nbsp;·&nbsp; Durée : ' . htmlspecialchars($duree, ENT_QUOTES) : '') . '
          ' . ($mode  ? ' &nbsp;·&nbsp; ' . htmlspecialchars($mode, ENT_QUOTES) : '') . '
        </p>
      </div>

      <!-- Pied -->
      <div class="cert-footer">
        <div style="text-align:left">
          <div class="cert-code">Code : ' . htmlspecialchars($codeCert, ENT_QUOTES) . '</div>
          <div class="cert-code">Délivré le ' . htmlspecialchars($dateCert, ENT_QUOTES) . '</div>
          <div style="font-size:9px;color:#94a3b8;margin-top:2px">Vérification : ibig-eduform.com/verifier-certificat</div>
        </div>
        <div style="text-align:center">
          <div class="sign-line"></div>
          <div class="sign-name">Direction IBIG EDUFORM</div>
          <div class="sign-role">Institut de Formation Professionnelle</div>
        </div>
        <div style="text-align:right;font-size:9px;color:#6b7280">
          Abidjan, Côte d\'Ivoire<br>
          +225 07 78 88 25 92<br>
          ibig-eduform.com
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>';

/* ── Génère le PDF ── */
$mpdfTempDir = __DIR__ . '/../uploads/tmp_mpdf';
if (!is_dir($mpdfTempDir)) @mkdir($mpdfTempDir, 0755, true);
if (!is_writable($mpdfTempDir)) $mpdfTempDir = sys_get_temp_dir();

$slug    = preg_replace('/[^a-z0-9]+/', '-', strtolower($nomComplet));
$filename = 'CERTIFICAT-IBIG-' . strtoupper(trim($slug, '-')) . '-' . $annee . '.pdf';

try {
    $mpdf = new \Mpdf\Mpdf([
        'mode'          => 'utf-8',
        'format'        => 'A4-L',
        'margin_top'    => 0,
        'margin_bottom' => 0,
        'margin_left'   => 0,
        'margin_right'  => 0,
        'default_font'  => 'Arial',
        'tempDir'       => $mpdfTempDir,
    ]);

    $mpdf->SetTitle('Certificat — ' . $nomComplet . ' — IBIG EDUFORM');
    $mpdf->SetAuthor('IBIG EDUFORM — Institut de Formation Professionnelle');
    $mpdf->SetSubject('Certificat de participation — ' . $titreF);
    $mpdf->SetProtection(['print', 'print-highres'], '', 'IBIG@Cert2026#Secure', 128);

    $mpdf->WriteHTML($html);
    $mpdf->Output($filename, \Mpdf\Output\Destination::DOWNLOAD);
    exit;

} catch (\Throwable $e) {
    error_log('[CERTIFICAT_PDF] ' . $e->getMessage());
    http_response_code(500);
    exit('Erreur lors de la génération du certificat. Veuillez réessayer.');
}
