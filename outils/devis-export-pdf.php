<?php
declare(strict_types=1);
/**
 * IBIG EDUFORM — /outils/devis-export-pdf.php
 * Génère un Devis / Proforma PDF authentifié (QR code SHA-256).
 * Insère la ligne dans la table `devis` + incrémente la séquence annuelle.
 * Protégé par .htpasswd du dossier /outils/.
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../core/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method Not Allowed'); }

/* ─────────────────────────────────────────────
   HELPERS
───────────────────────────────────────────── */
function dp(string $k, string $def = ''): string {
    return trim(strip_tags((string)($_POST[$k] ?? $def)));
}
function dpInt(string $k): int {
    return (int)preg_replace('/[^\d]/', '', $_POST[$k] ?? '0');
}
function fcfa(int $n): string {
    return number_format($n, 0, ',', "\u{202F}") . ' F CFA';
}

/* ─────────────────────────────────────────────
   DONNÉES POST
───────────────────────────────────────────── */
$tdrId          = dpInt('tdr_id');
$tdrRef         = dp('tdr_ref');
$titreFormation = dp('titre_formation', 'Formation IBIG EDUFORM');
$typeDoc        = dp('type_doc', 'formation');
$categorie      = dp('categorie');
$duree          = dp('duree', '25 heures');
$prospect       = dp('prospect', 'Client IBIG EDUFORM');
$contactNom     = dp('contact_nom');
$contactEmail   = dp('contact_email');
$nbParticipants = max(1, dpInt('nb_participants'));
$modeFormation  = dp('mode_formation', 'presentiel');
$prixUnitHT     = dpInt('prix_unitaire_ht');
$remisePct      = min(50, max(0, dpInt('remise_pct')));
$tvaPct         = in_array(dpInt('tva_pct'), [0, 18]) ? dpInt('tva_pct') : 0;
$montantHT      = dpInt('montant_ht');
$montantTVA     = dpInt('montant_tva');
$montantTTC     = dpInt('montant_ttc');
$validiteJours  = in_array(dpInt('validite_jours'), [15,30,45,60]) ? dpInt('validite_jours') : 30;
$dateFormation  = dp('date_formation');
$notes          = dp('notes');

/* Recalcul de sécurité côté serveur */
$prixUnitRemise = (int)round($prixUnitHT * (1 - $remisePct / 100) / 1000) * 1000;
$montantHTCalc  = $prixUnitRemise * $nbParticipants;
$montantTVACalc = (int)round($montantHTCalc * $tvaPct / 100);
$montantTTCCalc = $montantHTCalc + $montantTVACalc;
// Utiliser les valeurs calculées côté serveur
$montantHT  = $montantHTCalc;
$montantTVA = $montantTVACalc;
$montantTTC = $montantTTCCalc;
$acompte    = (int)round($montantTTC * 0.5 / 1000) * 1000;

$modeLabel = match($modeFormation) {
    'en_ligne'   => 'En ligne (Classe Virtuelle)',
    'hybride'    => 'Hybride (En ligne & Présentiel)',
    default      => 'Présentiel',
};

/* ─────────────────────────────────────────────
   NUMÉROTATION + TOKEN QR + INSERTION BD
───────────────────────────────────────────── */
$refDevis   = '';
$qrToken    = '';
$dateExpire = date('Y-m-d', strtotime('+' . $validiteJours . ' days'));
$devisId    = null;

/* Mode re-téléchargement : réutiliser les données existantes sans INSERT */
if (defined('DEVIS_REDOWNLOAD_MODE') && DEVIS_REDOWNLOAD_MODE) {
    $refDevis = DEVIS_REDOWNLOAD_REF;
    $qrToken  = DEVIS_REDOWNLOAD_TOKEN;
    $devisId  = DEVIS_REDOWNLOAD_ID;
    goto pdf_generation; // sauter toute la section BD
}

try {
    $pdo = new PDO(
        'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4',
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    /* Incrément séquence annuelle — verrou atomique */
    $annee = (int)date('Y');
    $pdo->exec("INSERT INTO devis_sequence (annee, compteur) VALUES ($annee, 0)
                ON DUPLICATE KEY UPDATE compteur = compteur + 1");
    $num = (int)$pdo->query("SELECT compteur FROM devis_sequence WHERE annee = $annee")->fetchColumn();
    $refDevis = 'DEV-' . $annee . '-' . str_pad((string)($num + 1), 3, '0', STR_PAD_LEFT);

    /* Token QR : SHA-256(ref + date + sel serveur) */
    $qrToken = hash('sha256', $refDevis . date('Y-m-d') . TDR_SECRET . 'DEVIS_QR_2026');

    /* Insertion devis */
    $stmt = $pdo->prepare("INSERT INTO devis
        (ref_devis, tdr_id, tdr_ref, prospect, contact_nom, contact_email,
         titre_formation, categorie, duree, nb_participants, fmt_tarif, mode_formation,
         date_formation, prix_unitaire_ht, remise_pct, tva_pct,
         montant_ht, montant_tva, montant_ttc, acompte_pct,
         validite_jours, date_expiration, statut, qr_token, notes_internes)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->execute([
        $refDevis,
        $tdrId > 0 ? $tdrId : null,
        $tdrRef ?: null,
        $prospect, $contactNom ?: null, $contactEmail ?: null,
        $titreFormation, $categorie ?: null, $duree,
        $nbParticipants,
        $nbParticipants > 1 ? 'groupe' : 'individuel',
        $modeFormation,
        $dateFormation ?: null,
        $prixUnitHT, $remisePct, $tvaPct,
        $montantHT, $montantTVA, $montantTTC,
        50, // acompte 50% par défaut
        $validiteJours, $dateExpire,
        'brouillon',
        $qrToken,
        $notes ?: null,
    ]);
    $devisId = (int)$pdo->lastInsertId();

} catch (\Throwable $e) {
    error_log('[DEVIS_EXPORT] BD : ' . $e->getMessage());
    // On continue même si la BD échoue — le PDF est généré quand même
    if (!$refDevis) {
        $refDevis = 'DEV-' . date('Y') . '-TMP';
        $qrToken  = hash('sha256', $refDevis . date('Y-m-d') . TDR_SECRET);
    }
}

pdf_generation:
/* ─────────────────────────────────────────────
   QR CODE IMAGE (via TCPDF barcodes)
───────────────────────────────────────────── */
$qrUrl   = APP_URL . '/verify/devis/' . $qrToken;
$qrB64   = '';
$qrWidth = 80; // pixels

try {
    require_once __DIR__ . '/../vendor/tecnickcom/tcpdf/include/barcodes/qrcode.php';
    $qr  = new QRcode($qrUrl, 'H'); // H = highest error correction
    $data = $qr->getBarcode();
    $rows = $data['num_rows'];
    $cols = $data['num_cols'];
    $cell = (int)ceil($qrWidth / $cols);
    $w    = $cols * $cell;
    $h    = $rows * $cell;
    $img  = imagecreatetruecolor($w, $h);
    $white = imagecolorallocate($img, 255, 255, 255);
    $black = imagecolorallocate($img, 13, 31, 60);
    imagefill($img, 0, 0, $white);
    foreach ($data['bcode'] as $ry => $row) {
        foreach ($row as $cx => $v) {
            if ($v) imagefilledrectangle($img, $cx*$cell, $ry*$cell, ($cx+1)*$cell-1, ($ry+1)*$cell-1, $black);
        }
    }
    ob_start();
    imagepng($img);
    $qrB64 = 'data:image/png;base64,' . base64_encode(ob_get_clean());
    imagedestroy($img);
} catch (\Throwable $e) {
    error_log('[DEVIS_QR] ' . $e->getMessage());
}

/* ─────────────────────────────────────────────
   LOGOS
───────────────────────────────────────────── */
$logoPath     = __DIR__ . '/../assets/images/logo.png';
$logoSarlPath = __DIR__ . '/../assets/images/logo-ibig-sarl.jpg';
$logoB64      = is_file($logoPath)     ? 'data:image/png;base64,'  . base64_encode(file_get_contents($logoPath))     : '';
$logoSarlB64  = is_file($logoSarlPath) ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($logoSarlPath)) : '';

/* ─────────────────────────────────────────────
   CSS
───────────────────────────────────────────── */
$css = '
body{margin:0;padding:0;background:#fff;font-family:Arial,Helvetica,sans-serif;font-size:10.5pt;color:#1a1a2e;line-height:1.6;}
.wrap{max-width:100%;background:#fff;}
/* En-tête */
.hd-band{background:#0d1f3c;color:#fff;padding:14pt 20pt 10pt;display:table;width:100%}
.hd-left{display:table-cell;vertical-align:middle;width:55%}
.hd-right{display:table-cell;vertical-align:middle;text-align:right;width:45%}
.hd-doc-type{font-size:8pt;color:#f59e0b;font-weight:700;letter-spacing:.12em;text-transform:uppercase;margin-bottom:2pt}
.hd-ref{font-size:18pt;font-weight:900;color:#fff;letter-spacing:.04em}
.hd-date{font-size:8pt;color:#94a3b8;margin-top:2pt}
/* Bloc client / émetteur */
.parties-tbl{width:100%;border-collapse:collapse;margin:16pt 0 12pt}
.parties-tbl td{width:50%;vertical-align:top;padding:12pt 14pt;border:1px solid #d1d5db}
.parties-tbl td:first-child{background:#f0f7ff;border-right:2px solid #0d1f3c}
.partie-label{font-size:7.5pt;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8;margin-bottom:4pt}
.partie-name{font-size:11.5pt;font-weight:900;color:#0d1f3c}
.partie-detail{font-size:9pt;color:#536080;margin-top:2pt;line-height:1.5}
/* Objet */
.objet-box{background:#fffbeb;border-left:4px solid #f59e0b;padding:10pt 14pt;margin:0 0 14pt}
.objet-label{font-size:7.5pt;font-weight:700;color:#92400e;text-transform:uppercase;letter-spacing:.08em}
.objet-titre{font-size:12pt;font-weight:900;color:#0d1f3c;margin-top:2pt}
.objet-detail{font-size:9pt;color:#536080;margin-top:3pt}
/* Tableau des prestations */
.sec-h{font-size:10pt;font-weight:900;color:#0d1f3c;text-transform:uppercase;letter-spacing:.05em;
  border-bottom:2px solid #f59e0b;padding-bottom:4pt;margin:14pt 0 8pt}
.tbl{width:100%;border-collapse:collapse;margin:0 0 8pt}
.tbl th{background:#0d1f3c;color:#fff;font-size:8.5pt;font-weight:700;padding:6pt 8pt;text-align:left}
.tbl td{font-size:9.5pt;color:#374151;padding:6pt 8pt;border:1px solid #d1d5db;vertical-align:top}
.tbl tr:nth-child(even) td{background:#f9fafb}
.tbl .tbl-sub td{background:#f5f8ff;font-size:8.5pt;color:#536080;font-style:italic}
/* Tableau financier */
.fin-tbl{width:100%;border-collapse:collapse;margin:8pt 0}
.fin-tbl td{padding:6pt 10pt;font-size:10pt;border:1px solid #d1d5db}
.fin-tbl td:first-child{color:#536080;width:60%}
.fin-tbl td:last-child{text-align:right;font-weight:700;color:#0d1f3c}
.fin-tbl .remise td{background:#f0fdf4}
.fin-tbl .remise td:last-child{color:#059669}
.fin-tbl .ttc td{background:#0d1f3c;color:#fff;font-size:12pt;font-weight:900}
.fin-tbl .ttc td:first-child{color:#f59e0b}
.fin-tbl .ttc td:last-child{color:#fff}
.fin-tbl .acompte td{background:#fffbeb}
.fin-tbl .acompte td:last-child{color:#b45309}
/* Validité et conditions */
.cond-box{background:#f8fafc;border:1px solid #e2e8f0;border-radius:3pt;padding:10pt 14pt;margin:10pt 0}
.cond-box p{font-size:9pt;color:#374151;margin:0 0 3pt}
.cond-box ul{margin:4pt 0 0 12pt;padding:0}
.cond-box ul li{font-size:9pt;color:#374151;margin-bottom:2pt}
/* QR + authentification */
.auth-tbl{width:100%;border-collapse:collapse;margin-top:16pt;border:2px solid #0d1f3c}
.auth-qr{width:100pt;text-align:center;padding:10pt;vertical-align:middle;background:#f0f7ff}
.auth-info{padding:10pt 14pt;vertical-align:top}
.auth-title{font-size:9pt;font-weight:900;color:#0d1f3c;text-transform:uppercase;letter-spacing:.06em}
.auth-url{font-size:7.5pt;color:#536080;word-break:break-all;margin-top:2pt}
.auth-token{font-size:6.5pt;font-family:monospace;color:#94a3b8;margin-top:2pt}
.auth-valid{font-size:8pt;color:#059669;font-weight:700;margin-top:6pt}
/* Signature */
.sig-tbl{width:100%;border-collapse:collapse;margin-top:14pt}
.sig-tbl td{border:2px solid #0d1f3c;padding:16pt;width:50%;vertical-align:top;font-size:9pt}
.sig-tbl td:first-child{background:#0d1f3c;color:#fff;font-weight:700;text-align:center;font-size:10pt}
/* Pied */
.mention{font-size:7.5pt;text-align:center;color:#94a3b8;margin-top:14pt;font-style:italic}
p{margin:0 0 4pt}
ul{margin:3pt 0 4pt 14pt;padding:0}
li{margin-bottom:2pt}
';

/* ─────────────────────────────────────────────
   HTML CORPS
───────────────────────────────────────────── */
ob_start();
$dateAujourdhui = date('d/m/Y');
$dateExpirationAff = date('d/m/Y', strtotime($dateExpire));
$remiseGrpPct  = (int)dp('remise_grp_pct');
$remiseExtraPct = max(0, $remisePct - $remiseGrpPct);
?>
<div class="wrap">

<!-- EN-TÊTE DOCUMENT -->
<table class="hd-band" cellpadding="0" cellspacing="0">
  <tr>
    <td class="hd-left">
      <?php if ($logoB64): ?>
        <img src="<?= $logoB64 ?>" style="height:30pt;margin-bottom:4pt" alt="IBIG EDUFORM"><br>
      <?php else: ?>
        <span style="font-size:13pt;font-weight:900;color:#fff">IBIG EDUFORM</span><br>
      <?php endif; ?>
      <span style="font-size:7.5pt;color:#94a3b8">Institut de Formation Professionnelle &amp; Conseil</span>
    </td>
    <td class="hd-right">
      <div class="hd-doc-type">Devis / Proforma</div>
      <div class="hd-ref"><?= htmlspecialchars($refDevis) ?></div>
      <div class="hd-date">Émis le <?= $dateAujourdhui ?> · Valable jusqu'au <?= $dateExpirationAff ?></div>
      <?php if ($tdrRef): ?>
        <div style="font-size:7.5pt;color:#64748b;margin-top:3pt">TDR de référence : <?= htmlspecialchars($tdrRef) ?></div>
      <?php endif; ?>
    </td>
  </tr>
</table>

<!-- PARTIES -->
<table class="parties-tbl" cellpadding="0" cellspacing="0">
  <tr>
    <td>
      <div class="partie-label">Prestataire</div>
      <div class="partie-name">IBIG EDUFORM</div>
      <div class="partie-detail">
        IBIG SARL — Institut de Formation Professionnelle &amp; Conseil<br>
        Abidjan, Côte d'Ivoire<br>
        📧 formation@ibig-eduform.com<br>
        📞 +225 07 78 88 25 92<br>
        🌐 www.ibig-eduform.com
      </div>
    </td>
    <td>
      <div class="partie-label">Destinataire</div>
      <div class="partie-name"><?= htmlspecialchars($prospect) ?></div>
      <div class="partie-detail">
        <?php if ($contactNom): ?><?= htmlspecialchars($contactNom) ?><br><?php endif; ?>
        <?php if ($contactEmail): ?>📧 <?= htmlspecialchars($contactEmail) ?><br><?php endif; ?>
        &nbsp;
      </div>
    </td>
  </tr>
</table>

<!-- OBJET -->
<div class="objet-box">
  <div class="objet-label">Objet du devis</div>
  <div class="objet-titre"><?= htmlspecialchars($titreFormation) ?></div>
  <div class="objet-detail">
    <?= htmlspecialchars($categorie ?: 'Formation Professionnelle') ?> · <?= htmlspecialchars($modeLabel) ?> · <?= htmlspecialchars($duree) ?>
    <?php if ($dateFormation): ?> · Date : <?= htmlspecialchars($dateFormation) ?><?php endif; ?>
  </div>
</div>

<!-- DÉTAIL PRESTATION -->
<div class="sec-h">Détail de la prestation</div>
<table class="tbl">
  <thead>
    <tr>
      <th style="width:5%">N°</th>
      <th style="width:45%">Désignation</th>
      <th style="width:15%;text-align:center">Qté</th>
      <th style="width:20%;text-align:right">Prix unitaire HT</th>
      <th style="width:15%;text-align:right">Total HT</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td style="text-align:center;font-weight:900;color:#0d1f3c">01</td>
      <td>
        <strong><?= htmlspecialchars($titreFormation) ?></strong><br>
        <span style="font-size:8.5pt;color:#536080"><?= htmlspecialchars($modeLabel) ?> · <?= htmlspecialchars($duree) ?></span>
      </td>
      <td style="text-align:center"><?= $nbParticipants ?> participant<?= $nbParticipants > 1 ? 's' : '' ?></td>
      <td style="text-align:right"><?= fcfa($prixUnitHT) ?></td>
      <td style="text-align:right;font-weight:700"><?= fcfa($prixUnitHT * $nbParticipants) ?></td>
    </tr>
    <?php if ($remisePct > 0): ?>
    <tr class="tbl-sub">
      <td></td>
      <td colspan="3" style="color:#059669;font-style:italic">
        ✓ Remise appliquée :
        <?php if ($remiseGrpPct > 0): ?><?= $remiseGrpPct ?>% remise groupe<?php endif; ?>
        <?php if ($remiseGrpPct > 0 && $remiseExtraPct > 0): ?> + <?php endif; ?>
        <?php if ($remiseExtraPct > 0): ?><?= $remiseExtraPct ?>% remise commerciale<?php endif; ?>
        — Total : <?= $remisePct ?>%
      </td>
      <td style="text-align:right;color:#059669;font-weight:700">- <?= fcfa($prixUnitHT * $nbParticipants - $montantHT) ?></td>
    </tr>
    <?php endif; ?>
  </tbody>
</table>

<!-- TABLEAU FINANCIER -->
<div class="sec-h">Récapitulatif financier</div>
<table class="fin-tbl">
  <tr><td>Montant HT <?= $remisePct > 0 ? '(après remise ' . $remisePct . '%)' : '' ?></td><td><?= fcfa($montantHT) ?></td></tr>
  <?php if ($tvaPct > 0): ?>
  <tr><td>TVA (<?= $tvaPct ?>%)</td><td><?= fcfa($montantTVA) ?></td></tr>
  <?php else: ?>
  <tr><td>TVA</td><td style="color:#536080;font-weight:400">Exonéré</td></tr>
  <?php endif; ?>
  <tr class="ttc"><td>MONTANT TOTAL TTC</td><td><?= fcfa($montantTTC) ?></td></tr>
  <tr class="acompte"><td>Acompte à la signature (50%)</td><td><?= fcfa($acompte) ?></td></tr>
</table>

<!-- CONDITIONS -->
<div class="cond-box">
  <p><strong>Conditions de règlement</strong></p>
  <ul>
    <li>50 % à la signature de la convention, valant confirmation du calendrier ;</li>
    <li>50 % à l'issue de la formation ou à mi-parcours selon accord ;</li>
    <li>Modalités : virement bancaire, mobile money (Orange Money / Wave) ou espèces contre reçu ;</li>
    <li>Ce devis est valable <?= $validiteJours ?> jours à compter du <?= $dateAujourdhui ?> (jusqu'au <?= $dateExpirationAff ?>) ;</li>
    <li>Tout dépassement du délai de validité fera l'objet d'une révision tarifaire.</li>
  </ul>
  <?php if ($notes): ?>
    <p style="margin-top:6pt"><strong>Conditions particulières :</strong> <?= nl2br(htmlspecialchars($notes)) ?></p>
  <?php endif; ?>
</div>

<!-- AUTHENTIFICATION QR -->
<table class="auth-tbl" cellpadding="0" cellspacing="0">
  <tr>
    <td class="auth-qr">
      <?php if ($qrB64): ?>
        <img src="<?= $qrB64 ?>" style="width:70pt;height:70pt" alt="QR Code vérification">
      <?php else: ?>
        <span style="font-size:8pt;color:#94a3b8">QR indisponible</span>
      <?php endif; ?>
    </td>
    <td class="auth-info">
      <div class="auth-title">🔐 Vérification d'authenticité</div>
      <div style="font-size:8.5pt;color:#374151;margin-top:4pt">
        Scannez ce QR code pour vérifier l'authenticité de ce document sur le site officiel IBIG EDUFORM.
      </div>
      <div class="auth-url"><?= htmlspecialchars($qrUrl) ?></div>
      <div class="auth-token">Jeton : <?= substr($qrToken, 0, 24) ?>…</div>
      <div class="auth-valid">✅ Document certifié IBIG EDUFORM — Réf. <?= htmlspecialchars($refDevis) ?></div>
    </td>
  </tr>
</table>

<!-- SIGNATURE -->
<p style="text-align:center;font-style:italic;font-weight:700;font-size:10.5pt;color:#0d1f3c;margin:16pt 0 6pt">— Bon pour accord —</p>
<table class="sig-tbl" cellpadding="0" cellspacing="0">
  <tr>
    <td>
      <p>Pour IBIG EDUFORM</p>
      <?php if ($logoB64): ?><img src="<?= $logoB64 ?>" style="height:18pt;margin:6pt 0" alt=""><br><?php endif; ?>
      <br><p>Le Directeur Général</p>
      <p style="font-size:7.5pt;opacity:.7">Signature &amp; Cachet</p>
    </td>
    <td>
      <p><strong>Le Commanditaire</strong></p>
      <p style="font-size:9pt;color:#6b7280;margin-top:6pt">
        Nom &amp; Prénoms : ……………………………………<br>
        Fonction : ……………………………………<br>
        Date : ………………………………………<br><br>
        Signature &amp; Cachet :<br><br><br>
      </p>
      <p style="font-size:8pt;font-style:italic">« Bon pour accord »</p>
    </td>
  </tr>
</table>

<p class="mention">
  Document confidentiel — IBIG EDUFORM · Réf. <?= htmlspecialchars($refDevis) ?><br>
  Institut de Formation Professionnelle &amp; Conseil · Abidjan, Côte d'Ivoire · www.ibig-eduform.com<br>
  Ce document a été généré le <?= $dateAujourdhui ?> et est vérifiable en ligne via le QR code ci-dessus.
</p>

</div>
<?php
$bodyContent = ob_get_clean();

/* ─────────────────────────────────────────────
   GÉNÉRATION PDF MPDF
───────────────────────────────────────────── */
$slugProspect = preg_replace('/[^a-z0-9]+/', '-', strtolower($prospect));
$slugTitre    = substr(preg_replace('/[^a-z0-9]+/', '-', strtolower($titreFormation)), 0, 30);
$filename     = 'DEVIS-IBIG-' . strtoupper(trim($slugProspect, '-')) . '-' . $refDevis . '.pdf';

try {
    $mpdf = new \Mpdf\Mpdf([
        'mode'          => 'utf-8',
        'format'        => 'A4',
        'margin_top'    => 28,
        'margin_bottom' => 22,
        'margin_left'   => 15,
        'margin_right'  => 15,
        'margin_header' => 5,
        'margin_footer' => 5,
        'default_font'  => 'Arial',
        'tempDir'       => sys_get_temp_dir(),
    ]);

    $mpdf->SetProtection(['print', 'print-highres'], '', 'IBIG@Devis2026#Secure', 128);
    $mpdf->SetTitle('Devis ' . $refDevis . ' — ' . $titreFormation . ' — IBIG EDUFORM');
    $mpdf->SetAuthor('IBIG EDUFORM — Institut de Formation Professionnelle');
    $mpdf->SetCreator('IBIG EDUFORM · ibig-eduform.com');
    $mpdf->SetSubject('Devis / Proforma — ' . $titreFormation . ' — ' . $prospect);
    $mpdf->SetKeywords('devis, proforma, formation, IBIG EDUFORM, OHADA, ' . $categorie);

    $mpdf->SetWatermarkText('PROFORMA');
    $mpdf->showWatermarkText  = true;
    $mpdf->watermarkTextAlpha = 0.04;
    $mpdf->watermarkAngle     = 45;

    $hdrLeft  = $logoB64 ? '<img src="' . $logoB64 . '" style="height:22px">'
                         : '<b style="font-size:10px;color:#0d1f3c">IBIG EDUFORM</b>';

    $mpdf->SetHTMLHeader('<table width="100%" style="border-bottom:2px solid #f59e0b;padding-bottom:3px"><tr>
      <td style="width:50%;vertical-align:middle">' . $hdrLeft . '</td>
      <td style="width:50%;text-align:right;vertical-align:middle;font-size:7.5px;color:#94a3b8">Devis / Proforma — ' . htmlspecialchars($refDevis) . ' — ' . htmlspecialchars($prospect) . '</td>
    </tr></table>');

    $mpdf->SetHTMLFooter('<table width="100%" style="border-top:1px solid #e5e7eb;padding-top:3px"><tr>
      <td style="font-size:7px;color:#9ca3af">IBIG EDUFORM · formation@ibig-eduform.com · +225 07 78 88 25 92</td>
      <td style="text-align:right;font-size:7px;color:#9ca3af">Page {PAGENO} / {nbpg}</td>
    </tr></table>');

    $mpdf->WriteHTML($css, \Mpdf\HTMLParserMode::HEADER_CSS);
    $mpdf->WriteHTML($bodyContent, \Mpdf\HTMLParserMode::HTML_BODY);
    $mpdf->Output($filename, \Mpdf\Output\Destination::DOWNLOAD);
    exit;

} catch (\Throwable $e) {
    error_log('[DEVIS_EXPORT_PDF] ' . $e->getMessage());
    http_response_code(500);
    exit('<p style="font-family:sans-serif;text-align:center;margin-top:80px;color:#dc2626">Erreur PDF : ' . htmlspecialchars($e->getMessage()) . '</p>');
}
