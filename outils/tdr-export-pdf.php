<?php
declare(strict_types=1);
/**
 * IBIG EDUFORM — /outils/tdr-export-pdf.php
 * Génère un PDF professionnel (3 niveaux) avec filigrane, chiffrement et historique.
 * Protégé par .htpasswd du dossier /outils/.
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/tdr_generator.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method Not Allowed'); }

/* ─────────────────────────────────────────────
   HELPERS
───────────────────────────────────────────── */
function p(string $k, string $def = ''): string {
    $v = trim(strip_tags((string)($_POST[$k] ?? '')));
    return $v !== '' ? $v : $def;
}
function pRaw(string $k, string $def = ''): string { return trim((string)($_POST[$k] ?? $def)); }
function fcfa(int $n): string { return number_format($n, 0, ',', "\u{202F}") . ' F CFA'; }
function li(string $raw): string {
    $lines = array_filter(array_map('trim', explode("\n", $raw)));
    if (!$lines) return '';
    return '<ul>' . implode('', array_map(fn($l) => '<li>' . htmlspecialchars($l) . '</li>', $lines)) . '</ul>';
}
function subSec(string $title, string $body): string {
    return '<div class="sec-h2">' . htmlspecialchars($title) . '</div>' . $body;
}
function secTitle(int $n, string $text): string {
    return '<div class="sec-h">' . $n . '. ' . htmlspecialchars($text) . '</div>';
}

/* ─────────────────────────────────────────────
   DONNÉES
───────────────────────────────────────────── */
$formatNiveau   = p('format_niveau', 'detaille'); // simplifie | moyen | detaille
$titre          = p('titre', 'Formation professionnelle IBIG EDUFORM');
$sousTitre      = p('sous_titre');
$typeDoc        = p('type_doc', 'formation');
$categorie      = p('categorie', 'Formation professionnelle');
$duree          = p('duree', '25 heures');
$lieu           = p('lieu', 'En ligne · Abidjan · Cotonou · Dakar');
$modeF          = p('mode_formation', 'en_ligne');
$prospect       = p('prospect_name', 'À compléter');
$nbParticipants = p('nb_participants');
$dateDebut      = p('date_debut');
$certif         = p('certif', 'Certificat de compétences IBIG EDUFORM');
$contexte       = p('contexte');
$objGen         = p('obj_gen');
$objSpec        = pRaw('obj_spec');
$resultats      = pRaw('resultats');
$modules        = pRaw('f_modules');
$methode        = pRaw('f_methode');
$cible          = p('f_cible');
$prerequis      = p('prerequis');
$livrable       = p('livrable');
$evaluation     = p('f_eval');
$prixPres       = (int)($_POST['prix_presentiel'] ?? 0);
$prixLigne      = (int)($_POST['prix_en_ligne']   ?? 0);
$fmtTarifPost   = p('fmt_tarif', 'devis');

/* ── Validation tarifaire IBIG EDUFORM (règle absolue) ── */
if ($fmtTarifPost === 'individuel') {
    if ($prixLigne > 0 && $prixLigne < 200000) {
        http_response_code(422);
        exit(json_encode(['error' => 'Prix en ligne inférieur au minimum autorisé (200 000 F CFA).']));
    }
    if ($prixPres > 0 && $prixPres < 250000) {
        http_response_code(422);
        exit(json_encode(['error' => 'Prix présentiel inférieur au minimum autorisé (250 000 F CFA).']));
    }
}
$prixRef        = p('ref_doc', 'IBIG-TDR/' . strtoupper(substr($categorie,0,3)) . '/' . date('Y'));
$sigIbig        = p('sig_ibig', 'Le Directeur Général');
$sigClient      = p('sig_client', $prospect ?: 'Le Bénéficiaire');

$typeLabels = ['formation'=>'Formation Professionnelle','mission'=>'Mission de Conseil','projet'=>'Projet','evaluation'=>'Évaluation'];
$typeLabel  = $typeLabels[$typeDoc] ?? 'Formation';

preg_match('/(\d+)/', $duree, $dm);
$heures   = (int)($dm[1] ?? 25);
$seances  = max(1, (int)ceil($heures / 2.5));
$semaines = max(1, (int)ceil($seances / 2));

/* ── Auto-remplissage depuis les templates IBIG EDUFORM ── */
$_tpl   = tdr_templates();
$_tdata = $_tpl[$categorie] ?? $_tpl['_default'];
$nomCourt = preg_replace('/\s*[\(\[].*?[\)\]]\s*/', '', $titre);
function _tpl_to_text(string $html, string $nom = ''): string {
    $t = $nom ? str_replace('{NOM}', $nom, $html) : $html;
    $t = preg_replace('#</p>\s*<p[^>]*>#i', "\n\n", $t);
    $t = preg_replace('#<br\s*/?>|</p>|<p[^>]*>#i', "\n", $t);
    return trim(strip_tags($t));
}
if (!$contexte)  $contexte = _tpl_to_text($_tdata['contexte'] ?? '', $nomCourt);
if (!$objGen)    $objGen   = _tpl_to_text($_tdata['objectif_general'] ?? '', $nomCourt);
if (!$objSpec) {
    $objSpec = implode("\n", $_tdata['objectifs_specifiques'] ?? []);
}
if (!$cible)     $cible    = _tpl_to_text($_tdata['public_cible']   ?? '');
if (!$prerequis) $prerequis = _tpl_to_text($_tdata['prerequis']     ?? '');
$_methodoTpl = $_tdata['methodologie'] ?? [];
/* Modules : auto-générés si non saisis */
if (!$modules) {
    $_mods = tdr_modules($titre, $categorie, $heures, '');
    $modules = implode("\n", array_map(fn($m) => 'Module : ' . $m['titre'], $_mods));
    $_modulesAutoData = $_mods;
} else {
    $_modulesAutoData = null;
}

$modeLabel = match($modeF) {
    'presentiel' => 'Présentiel',
    'hybride'    => 'En ligne & Présentiel (Hybride)',
    default      => 'En ligne (Classe Virtuelle)',
};
$formatNiveauLabel = match($formatNiveau) {
    'simplifie' => 'Simplifié',
    'moyen'     => 'Standard',
    default     => 'Complet',
};

/* ─────────────────────────────────────────────
   LOGOS
───────────────────────────────────────────── */
$logoPath     = __DIR__ . '/../assets/images/logo.png';
$logoSarlPath = __DIR__ . '/../assets/images/logo-ibig-sarl.jpg';
$logoB64      = is_file($logoPath)     ? 'data:image/png;base64,'  . base64_encode(file_get_contents($logoPath))     : '';
$logoSarlB64  = is_file($logoSarlPath) ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($logoSarlPath)) : '';

/* ─────────────────────────────────────────────
   CONSTRUCTEURS HTML
───────────────────────────────────────────── */
function buildModulesTable(string $raw, int $heures, ?array $autoData = null): string {
    /* Si on a les données riches auto-générées, on les utilise */
    if ($autoData) {
        $html = '<table class="tbl"><thead><tr><th style="width:5%">N°</th><th style="width:32%">Module</th><th>Contenus clés</th><th style="width:7%">Durée</th></tr></thead><tbody>';
        foreach ($autoData as $i => $m) {
            $bg = ($i % 2 !== 0) ? ' style="background:#f5f8fc"' : '';
            $html .= "<tr{$bg}><td style='text-align:center;font-weight:bold;color:#0a1733'>M" . ($i+1) . "</td>"
                   . "<td><strong>" . htmlspecialchars($m['titre']) . "</strong></td>"
                   . "<td style='color:#536080;font-size:9pt'>" . htmlspecialchars($m['contenus']) . "</td>"
                   . "<td style='text-align:center;font-weight:600'>" . $m['duree'] . " h</td></tr>";
        }
        $html .= "<tr style='background:#0a1733'><td colspan='3' style='font-weight:bold;text-align:right;padding:6pt 8pt;color:#f59e0b'>TOTAL VOLUME HORAIRE</td><td style='text-align:center;font-weight:900;padding:6pt 8pt;color:#fff'>{$heures} h</td></tr></tbody></table>";
        return $html;
    }
    $lines = array_values(array_filter(array_map('trim', explode("\n", $raw))));
    if (!$lines) return '';
    $count  = count($lines);
    $hParMod = max(2, (int)round($heures / $count));
    $lastH  = $heures - $hParMod * ($count - 1);
    $html   = '<table class="tbl"><thead><tr><th style="width:5%">N°</th><th style="width:32%">Module</th><th>Contenus &amp; Activités</th><th style="width:7%">Durée</th></tr></thead><tbody>';
    foreach ($lines as $i => $line) {
        $h = ($i === $count - 1) ? $lastH : $hParMod;
        if (preg_match('/^Module\s*:?\s*(\d+)\s*[:\-]\s*(.+)/i', $line, $m)) {
            $num = $m[1]; $titreM = htmlspecialchars($m[2]);
        } elseif (preg_match('/^Module\s*:?\s*(.+)/i', $line, $m)) {
            $num = $i + 1; $titreM = htmlspecialchars($m[1]);
        } else { $num = $i + 1; $titreM = htmlspecialchars($line); }
        $bg = ($i % 2 !== 0) ? ' style="background:#f5f8fc"' : '';
        $html .= "<tr{$bg}><td style='text-align:center;font-weight:bold;color:#0a1733'>M{$num}</td><td><strong>{$titreM}</strong></td><td style='color:#536080;font-size:9pt'>Apports théoriques, mises en situation professionnelles, outils pratiques et cas réels du terrain africain.</td><td style='text-align:center;font-weight:600'>{$h} h</td></tr>";
    }
    $html .= "<tr style='background:#0a1733'><td colspan='3' style='font-weight:bold;text-align:right;padding:6pt 8pt;color:#f59e0b'>TOTAL VOLUME HORAIRE</td><td style='text-align:center;font-weight:900;padding:6pt 8pt;color:#fff'>{$heures} h</td></tr></tbody></table>";
    return $html;
}

/* Grille remises groupe IBIG EDUFORM */
function groupeRemise(int $nb): array {
    if ($nb >= 11) return ['pct' => 12, 'label' => '+10 personnes'];
    if ($nb >= 6)  return ['pct' => 10, 'label' => '6–10 personnes'];
    if ($nb >= 3)  return ['pct' =>  7, 'label' => '3–5 personnes'];
    if ($nb === 2) return ['pct' =>  5, 'label' => '2 personnes'];
    return ['pct' => 0, 'label' => ''];
}

function buildPriceTable(int $pp, int $po, string $mode, string $fmtTarif, int $nbPart = 1): string {
    if ($fmtTarif === 'devis' || (!$pp && !$po)) {
        return '<div class="prix-devis"><p><strong>Tarification sur devis personnalisé</strong></p><p>Contactez-nous pour obtenir une proposition adaptée à votre contexte.</p><p style="margin-top:6pt">📧 formation@ibig-eduform.com &nbsp;|&nbsp; 📞 +225 07 78 88 25 92</p></div>';
    }

    if ($fmtTarif === 'groupe') {
        $r = groupeRemise($nbPart);
        $html = '';
        if ($r['pct'] > 0) {
            $poR = $po > 0 ? (int)round($po * (1 - $r['pct'] / 100)) : 0;
            $ppR = $pp > 0 ? (int)round($pp * (1 - $r['pct'] / 100)) : 0;
            $titre = 'Groupe — ' . $nbPart . ' participants · Remise ' . $r['pct'] . '% (' . $r['label'] . ')';
            $html .= '<table class="tbl"><thead><tr><th>Formule</th><th>Tarif / pers.</th><th>Total groupe</th></tr></thead><tbody>';
            if ($poR > 0) $html .= '<tr><td>En ligne</td><td style="font-weight:700;color:#0a1733">' . fcfa($poR) . ' <span style="color:#92400e;font-size:8.5pt">(-' . $r['pct'] . '%)</span></td><td style="font-weight:900;color:#b45309">' . fcfa($poR * $nbPart) . '</td></tr>';
            if ($ppR > 0) $html .= '<tr><td>Présentiel</td><td style="font-weight:700;color:#0a1733">' . fcfa($ppR) . ' <span style="color:#92400e;font-size:8.5pt">(-' . $r['pct'] . '%)</span></td><td style="font-weight:900;color:#b45309">' . fcfa($ppR * $nbPart) . '</td></tr>';
            $html .= '</tbody></table>';
            $html .= '<p style="font-size:8.5pt;color:#92400e;font-style:italic;margin-top:4pt">Tarif groupe préférentiel IBIG EDUFORM — ' . htmlspecialchars($titre) . '.</p>';
        } else {
            $on = $po > 0 ? fcfa($po) : '<em>N/A</em>';
            $pr = $pp > 0 ? fcfa($pp) : '<em>N/A</em>';
            $html .= '<table class="tbl"><thead><tr><th>Format</th><th>Tarif</th></tr></thead><tbody>';
            if ($po > 0) $html .= "<tr><td>En ligne</td><td style='font-weight:700'>{$on}</td></tr>";
            if ($pp > 0) $html .= "<tr><td>Présentiel</td><td style='font-weight:700'>{$pr}</td></tr>";
            $html .= '</tbody></table>';
            $html .= '<p style="font-size:8.5pt;color:#888;font-style:italic;margin-top:4pt">Tarif groupe (1 participant — remise appliquée dès 2 participants).</p>';
        }
        return $html;
    }

    /* Tarif individuel */
    $html = '<table class="tbl"><thead><tr><th>Formule</th><th>Tarif en ligne</th><th>Tarif présentiel</th></tr></thead><tbody>';
    $on = $po > 0 ? '<strong>' . fcfa($po) . '</strong>' : '<em>N/A</em>';
    $pr = $pp > 0 ? '<strong>' . fcfa($pp) . '</strong>' : '<em>N/A</em>';
    $hybr = ($po > 0 && $pp > 0) ? fcfa((int)(($po + $pp) / 2)) : '<em>N/A</em>';
    $html .= "<tr><td>✅ Individuel (accompagnement dédié)</td><td style='text-align:right'>{$on}</td><td style='text-align:right'>{$pr}</td></tr>";
    $html .= "<tr style='background:#f5f8fc'><td>✅ Hybride (en ligne et présentiel)</td><td colspan='2' style='text-align:center'>{$hybr}</td></tr>";
    $html .= '</tbody></table>';
    $html .= '<p style="font-size:8.5pt;color:#888;font-style:italic;margin-top:4pt">Tarifs par personne. Inclus : diagnostic · animation · supports · certification · coaching post-formation.</p>';
    return $html;
}

/* ─────────────────────────────────────────────
   CSS — MÊME STYLE QUE LE TDR PUBLIC EDUFORM
───────────────────────────────────────────── */
$css = '
body{margin:0;padding:0;background:#fff;font-family:Arial,Helvetica,sans-serif;font-size:11pt;color:#1a1a2e;line-height:1.65;}
.wrap{max-width:100%;background:#fff;}
/* ── En-tête ── */
.hd{text-align:center;padding:22pt 20pt 16pt;border-bottom:3px solid #f59e0b;}
.hd-title{font-size:13pt;font-weight:normal;color:#555;margin:0 0 2pt;}
.hd-inst{font-size:9pt;color:#777;margin:0;}
.tdr-title{font-size:20pt;font-weight:900;color:#0a1733;text-transform:uppercase;margin:14pt 0 4pt;letter-spacing:.04em;}
.tdr-sub{font-size:11pt;font-weight:700;color:#b45309;text-transform:uppercase;margin:0 0 4pt;}
.tdr-nom{font-size:13pt;font-weight:900;color:#0a1733;margin:0 0 4pt;}
.tdr-acc{font-size:10pt;font-style:italic;color:#6b7280;margin:0;}
/* ── Fiche signalétique ── */
.fiche{width:100%;border-collapse:collapse;margin:16pt 0;}
.fiche-hd{background:#0a1733;color:#fff;font-size:9pt;font-weight:700;text-transform:uppercase;letter-spacing:.06em;padding:7pt 10pt;}
.fiche tr td{font-size:10pt;padding:6pt 10pt;border:1px solid #d1d5db;vertical-align:top;line-height:1.5;}
.fiche tr td:first-child{font-weight:700;color:#374151;width:200pt;background:#f8fafc;}
.fiche .prix-row td:last-child{font-size:12pt;font-weight:900;color:#b45309;}
/* ── Sections ── */
.sec{padding:0 0 16pt;}
.sec-h{font-size:11pt;font-weight:900;color:#0a1733;text-transform:uppercase;letter-spacing:.05em;
  border-bottom:2px solid #f59e0b;padding-bottom:5pt;margin:20pt 0 10pt;}
.sec-h2{font-size:10.5pt;font-weight:700;color:#b45309;margin:12pt 0 5pt;}
.sec p{font-size:10.5pt;color:#374151;line-height:1.7;margin:5pt 0;text-align:justify;}
.sec ul{margin:5pt 0 8pt 16pt;padding:0;}
.sec ul li{font-size:10.5pt;color:#374151;line-height:1.65;margin-bottom:3pt;}
/* ── Tableaux ── */
.tbl{width:100%;border-collapse:collapse;margin:8pt 0;}
.tbl th{background:#0a1733;color:#fff;font-size:9pt;font-weight:700;padding:6pt 8pt;text-align:left;}
.tbl td{font-size:9.5pt;color:#374151;padding:6pt 8pt;border:1px solid #d1d5db;vertical-align:top;}
.tbl td:first-child{font-weight:700;color:#0a1733;background:#f8fafc;}
.tbl tr:nth-child(even) td{background:#f9fafb;}
.tbl .tbl-total td{background:#0a1733;color:#fff;font-weight:900;text-align:right;font-size:9pt;}
.tbl-alt th{background:#b45309;}
.tbl-res th{background:#1e3a6e;}
/* ── Prix ── */
.prix-devis{border:2px solid #f59e0b;border-radius:4pt;padding:10pt 14pt;background:#fffbeb;color:#92400e;}
/* ── Signature ── */
.sig-tbl{width:100%;border-collapse:collapse;margin-top:16pt;}
.sig-tbl td{border:2px solid #0a1733;padding:18pt;width:50%;vertical-align:top;font-size:9.5pt;}
.sig-tbl td:first-child{background:#0a1733;color:#fff;font-weight:700;text-align:center;font-size:10.5pt;}
.accord{text-align:center;font-style:italic;font-weight:700;font-size:11pt;color:#0a1733;margin:16pt 0 6pt;}
/* ── Misc ── */
ul{padding-left:14pt;margin:4pt 0 6pt;}
li{margin-bottom:2pt;}
p{margin:0 0 5pt;}
.mention-conf{font-size:8pt;text-align:center;color:#536080;margin-top:14pt;font-style:italic;}
';

/* ─────────────────────────────────────────────
   CONTENU HTML PAR NIVEAU
───────────────────────────────────────────── */
$prixChoisi = $modeF === 'presentiel' ? $prixPres : $prixLigne;
$fmtTarif   = $fmtTarifPost ?: ($prixChoisi > 0 ? 'individuel' : 'devis');
$n = 1; // compteur de sections

ob_start();
?>
<div class="wrap">

<!-- EN-TÊTE -->
<div class="hd">
  <?php if ($logoB64): ?><img src="<?= $logoB64 ?>" style="height:48pt;margin-bottom:6pt" alt="IBIG EDUFORM"><br><?php endif; ?>
  <p class="hd-title"><strong>IBIG EDUFORM</strong></p>
  <p class="hd-inst">Institut de Formation Professionnelle — INTERMARK BUSINESS INTERNATIONAL GROUP<br>
    www.ibig-eduform.com &nbsp;|&nbsp; Abidjan – Côte d'Ivoire &nbsp;|&nbsp; formation@ibig-eduform.com</p>
  <p class="tdr-title">Termes de Référence (TDR)</p>
  <p class="tdr-sub"><?= htmlspecialchars($typeLabel) ?></p>
  <p class="tdr-nom"><?= htmlspecialchars($titre) ?></p>
  <?php if ($sousTitre): ?><p class="tdr-acc"><?= htmlspecialchars($sousTitre) ?></p><?php endif; ?>
</div>

<!-- FICHE SIGNALÉTIQUE -->
<?php
$_isGroupe   = ($fmtTarif === 'groupe');
$_nbPart     = (int)$nbParticipants ?: 1;
$_remiseFiche = $_isGroupe ? groupeRemise($_nbPart) : ['pct'=>0,'label'=>''];
$_formatLabel = $_isGroupe
    ? 'Groupe / Intra-entreprise (' . $_nbPart . ' participants)' . ($_remiseFiche['pct'] > 0 ? ' — remise ' . $_remiseFiche['pct'] . '%' : '')
    : match($fmtTarif) { 'individuel' => 'Individuel — accompagnement dédié', default => 'Sur devis personnalisé' };
$_prixFiche  = 0;
if ($_isGroupe && $prixLigne > 0 && $_remiseFiche['pct'] > 0) {
    $_prixFiche = (int)round($prixLigne * (1 - $_remiseFiche['pct']/100));
} elseif (!$_isGroupe && $prixChoisi > 0) {
    $_prixFiche = $prixChoisi;
}
$_tranche = $_prixFiche > 0 ? (int)round($_prixFiche * 0.5 / 5000) * 5000 : 0;
?>
<div class="sec">
<table class="fiche">
  <tr><td colspan="2" class="fiche-hd">Fiche signalétique de l'action de formation</td></tr>
  <tr><td>Intitulé</td><td><strong><?= htmlspecialchars($titre) ?></strong></td></tr>
  <tr><td>Bénéficiaire / Client</td><td><strong><?= htmlspecialchars($prospect ?: '………………………………………… (nom et fonction)') ?></strong></td></tr>
  <tr><td>Commanditaire</td><td><?= $prospect ? htmlspecialchars($prospect) : '………………………………………… (à titre personnel ou raison sociale)' ?></td></tr>
  <tr><td>Prestataire</td><td>IBIG EDUFORM — Institut de Formation Professionnelle</td></tr>
  <?php if ($typeDoc === 'formation'): ?>
  <tr><td>Format choisi</td><td><strong><?= htmlspecialchars($_formatLabel) ?></strong></td></tr>
  <tr><td>Modalité choisie</td><td><strong><?= htmlspecialchars($modeLabel) ?></strong></td></tr>
  <?php if ($nbParticipants): ?><tr><td>Nombre de participants</td><td><?= htmlspecialchars($nbParticipants) ?></td></tr><?php endif; ?>
  <?php if ($duree): ?><tr><td>Volume horaire</td><td><?= htmlspecialchars($duree) ?> — <?= $seances ?> séances de 2h30</td></tr><?php endif; ?>
  <tr><td>Rythme</td><td>2 séances par semaine sur <?= max(3,(int)ceil($seances/2)) ?> semaines (ajustable selon disponibilités)</td></tr>
  <?php endif; ?>
  <?php if ($dateDebut): ?><tr><td>Date souhaitée</td><td><?= htmlspecialchars($dateDebut) ?> (à confirmer lors de l'entretien de cadrage)</td></tr><?php else: ?><tr><td>Date souhaitée</td><td>À définir lors de l'entretien de cadrage</td></tr><?php endif; ?>
  <tr><td>Référence</td><td><?= htmlspecialchars($prixRef) ?></td></tr>
  <?php if ($_prixFiche > 0): ?>
  <tr class="prix-row"><td>Coût <?= $_isGroupe ? '(par personne après remise)' : '(formule choisie)' ?></td><td><?= fcfa($_prixFiche) ?><?= ($_tranche > 0 ? ' &nbsp;—&nbsp; 1ère tranche : <strong>' . fcfa($_tranche) . '</strong>' : '') ?></td></tr>
  <?php elseif ($fmtTarif === 'devis'): ?>
  <tr><td>Tarification</td><td><em>Sur devis personnalisé — contactez-nous</em></td></tr>
  <?php endif; ?>
  <tr><td>Période proposée</td><td>………………………………………… (à confirmer)</td></tr>
  <?php if ($certif && $typeDoc === 'formation'): ?><tr><td>Sanction</td><td><?= htmlspecialchars($certif) ?> — nominatif, numéroté, vérifiable en ligne (QR code)</td></tr><?php endif; ?>
</table>
</div>

<?php if ($formatNiveau === 'simplifie'): ?>
<!-- ══════════════ FORMAT SIMPLIFIÉ ══════════════ -->

<?php if ($objGen || $objSpec): ?>
<div class="sec">
  <?= secTitle($n++, 'OBJECTIFS') ?>
  <?php if ($objGen): ?><p><strong>Objectif général :</strong> <?= htmlspecialchars($objGen) ?></p><?php endif; ?>
  <?php if ($objSpec): ?><p><strong>À l'issue de cette formation, le bénéficiaire sera capable de :</strong></p><?= li($objSpec) ?><?php endif; ?>
</div>
<?php endif; ?>

<?php if ($typeDoc === 'formation' && $modules): ?>
<div class="sec">
  <?= secTitle($n++, 'PROGRAMME EN BREF') ?>
  <?= li($modules) ?>
</div>
<?php endif; ?>

<div class="sec">
  <?= secTitle($n++, 'CONDITIONS FINANCIÈRES') ?>
  <?= buildPriceTable($prixPres, $prixLigne, $modeF, $fmtTarif, (int)$nbParticipants ?: 1) ?>
</div>

<?php elseif ($formatNiveau === 'moyen'): ?>
<!-- ══════════════ FORMAT MOYEN ══════════════ -->

<?php if ($contexte): ?>
<div class="sec">
  <?= secTitle($n++, 'CONTEXTE ET JUSTIFICATION') ?>
  <p><?= nl2br(htmlspecialchars($contexte)) ?></p>
</div>
<?php endif; ?>

<?php if ($objGen || $objSpec): ?>
<div class="sec">
  <?= secTitle($n++, 'OBJECTIFS DE LA FORMATION') ?>
  <?php if ($objGen): ?><?= subSec('Objectif général', '<p>' . htmlspecialchars($objGen) . '</p>') ?><?php endif; ?>
  <?php if ($objSpec): ?><?= subSec('Objectifs spécifiques', '<p>À l\'issue du parcours, le bénéficiaire sera capable de :</p>' . li($objSpec)) ?><?php endif; ?>
</div>
<?php endif; ?>

<?php if ($resultats): ?>
<div class="sec">
  <?= secTitle($n++, 'RÉSULTATS ATTENDUS') ?>
  <?= li($resultats) ?>
</div>
<?php endif; ?>

<?php if ($typeDoc === 'formation'): ?>
<div class="sec">
  <?= secTitle($n++, 'PROGRAMME ET ORGANISATION') ?>
  <?php if ($modules): ?>
  <?= buildModulesTable($modules, $heures, $_modulesAutoData) ?>
  <?php endif; ?>
  <?php if ($methode): ?><?= subSec('Approche pédagogique', '<p>' . nl2br(htmlspecialchars($methode)) . '</p>') ?><?php endif; ?>
</div>
<?php endif; ?>

<div class="sec">
  <?= secTitle($n++, 'CONDITIONS FINANCIÈRES') ?>
  <?= buildPriceTable($prixPres, $prixLigne, $modeF, $fmtTarif, (int)$nbParticipants ?: 1) ?>
  <?= subSec('Conditions de règlement', '<ul>
    <li>50 % à la signature de la convention, valant confirmation du calendrier ;</li>
    <li>50 % à l\'issue du parcours ou à mi-parcours selon accord ;</li>
    <li>Offre valable trente (30) jours à compter de la date des présents TDR.</li>
  </ul>') ?>
</div>

<?php if ($typeDoc === 'formation' && $certif): ?>
<div class="sec">
  <?= secTitle($n++, 'CERTIFICATION') ?>
  <p><?= htmlspecialchars($certif) ?> — Certificat nominatif, numéroté et vérifiable en ligne par QR code. Reconnu dans les 17 pays membres de l'OHADA.</p>
</div>
<?php endif; ?>

<?php else: ?>
<!-- ══════════════ FORMAT DÉTAILLÉ (COMPLET) ══════════════ -->

<?php if ($contexte): ?>
<div class="sec">
  <?= secTitle($n++, 'CONTEXTE ET JUSTIFICATION') ?>
  <p><?= nl2br(htmlspecialchars($contexte)) ?></p>
</div>
<?php else: ?>
<div class="sec">
  <?= secTitle($n++, 'CONTEXTE ET JUSTIFICATION') ?>
  <p>La compétence ciblée constitue un atout stratégique pour les professionnels de l'espace OHADA. Face aux mutations économiques, ce parcours IBIG EDUFORM apporte une réponse terrain, directement applicable dans le quotidien professionnel du bénéficiaire.</p>
</div>
<?php endif; ?>

<div class="sec">
  <?= secTitle($n++, 'OBJECTIFS DE LA FORMATION') ?>
  <?= subSec('Objectif général', '<p>' . ($objGen ?: 'Permettre au bénéficiaire d\'acquérir une maîtrise opérationnelle du domaine, en conformité avec les standards de l\'espace OHADA.') . '</p>') ?>
  <?php if ($objSpec): ?>
  <?= subSec('Objectifs spécifiques', '<p>À l\'issue du parcours, le bénéficiaire sera capable de :</p>' . li($objSpec)) ?>
  <?php endif; ?>
</div>

<div class="sec">
  <?= secTitle($n++, 'RÉSULTATS ATTENDUS') ?>
  <?php
  $resLines = array_values(array_filter(array_map('trim', explode("\n", $resultats))));
  if ($resLines): ?>
  <table class="tbl tbl-res">
    <thead><tr><th>Résultat attendu</th><th>Indicateur de vérification</th></tr></thead>
    <tbody>
    <?php foreach ($resLines as $r): ?>
    <tr><td><?= htmlspecialchars($r) ?></td><td>Validation par le formateur à l'issue du parcours</td></tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php else: ?>
  <table class="tbl tbl-res">
    <thead><tr><th>Résultat attendu</th><th>Indicateur de vérification</th></tr></thead>
    <tbody>
    <tr><td>Maîtrise opérationnelle du domaine <?= htmlspecialchars($categorie) ?></td><td>Note ≥ 12/20 à l'évaluation de certification</td></tr>
    <tr><td>Transposition des acquis sur le terrain professionnel</td><td>Résolution de 3 situations professionnelles documentées</td></tr>
    <tr><td>Outils pratiques immédiatement utilisables</td><td>Boîte à outils numérique remise et mise en service</td></tr>
    <tr><td>Plan de progression individuel formalisé</td><td>Plan d'action présenté en séance de clôture</td></tr>
    </tbody>
  </table>
  <?php endif; ?>
</div>

<?php if ($typeDoc === 'formation'): ?>
<div class="sec">
  <?= secTitle($n++, 'BÉNÉFICIAIRE, PRÉREQUIS ET ACCESSIBILITÉ') ?>
  <?= subSec('Profil concerné', '<p>' . ($cible ?: 'Tout professionnel souhaitant renforcer ou acquérir des compétences opérationnelles dans le domaine <strong>' . htmlspecialchars($categorie) . '</strong>.') . '</p>') ?>
  <?= subSec('Prérequis pédagogiques', '<p>' . ($prerequis ?: 'Niveau Bac ou expérience équivalente. Aucun prérequis technique spécifique — accessible dès le niveau débutant confirmé.') . '</p>') ?>
  <?php if ($modeF !== 'presentiel'): ?>
  <?= subSec('Prérequis techniques', '<ul><li>Ordinateur, webcam et micro ;</li><li>Connexion internet ≥ 2 Mbps ;</li><li>Espace de travail calme ;</li><li>Adresse e-mail valide.</li></ul>') ?>
  <?php endif; ?>
  <?= subSec('Accessibilité', '<p>Modalités adaptables à toute situation de handicap, sur signalement au moins 7 jours avant le démarrage.</p>') ?>
</div>

<div class="sec">
  <?= secTitle($n++, 'CONTENU PÉDAGOGIQUE DÉTAILLÉ') ?>
  <p>Programme organisé en modules complémentaires, articulés selon une progression logique des fondamentaux vers la maîtrise opérationnelle.</p>
  <?= buildModulesTable($modules, $heures, $_modulesAutoData) ?>
</div>

<div class="sec">
  <?= secTitle($n++, 'APPROCHE MÉTHODOLOGIQUE') ?>
  <?php if ($methode): ?>
  <p><?= nl2br(htmlspecialchars($methode)) ?></p>
  <?php else: ?>
  <p>Le parcours est conçu comme un accompagnement de type <strong>formation-coaching</strong> : 20 % d'apports théoriques structurants et 80 % de travail sur les situations réelles du bénéficiaire. Le bénéficiaire n'apprend pas en écoutant — il apprend en agissant.</p>
  <?php if (!empty($_methodoTpl)): ?>
  <ul><?php foreach ($_methodoTpl as $_mt): ?><li><?= htmlspecialchars($_mt) ?></li><?php endforeach; ?></ul>
  <?php else: ?>
  <ul>
    <li>Diagnostic préalable et personnalisation du parcours selon le profil et les objectifs du bénéficiaire ;</li>
    <li>Études de cas et exercices tirés de l'environnement professionnel réel du bénéficiaire ;</li>
    <li>Outils, modèles et trames directement utilisables en entreprise ;</li>
    <li>Travaux intersessions sur documents et situations réels ;</li>
    <li>Boîte à outils numérique complète (modèles, guides, références OHADA) ;</li>
    <li>Plan d'action individuel formalisé et soutenu en séance de clôture.</li>
  </ul>
  <?php endif; ?>
  <?php endif; ?>
</div>

<?php if ($modeF !== 'presentiel'): ?>
<div class="sec">
  <?= secTitle($n++, 'DISPOSITIF TECHNIQUE À DISTANCE') ?>
  <table class="tbl">
    <thead><tr><th>Composante</th><th>Description</th></tr></thead>
    <tbody>
    <tr><td>Classe virtuelle</td><td>Sessions via visioconférence professionnelle — lien nominatif transmis avant chaque séance.</td></tr>
    <tr><td>Espace apprenant</td><td>Accès 24h/24 aux supports, outils et ressources complémentaires.</td></tr>
    <tr><td>Enregistrements</td><td>Chaque séance est enregistrée et mise à disposition du bénéficiaire.</td></tr>
    <tr><td>Souplesse</td><td>Reports acceptés sous 24 h, créneaux ajustables selon disponibilités.</td></tr>
    </tbody>
  </table>
</div>
<?php endif; ?>

<div class="sec">
  <?= secTitle($n++, 'DÉROULEMENT ET ORGANISATION') ?>
  <table class="tbl">
    <thead><tr><th>Étape</th><th>Activité</th><th>Repère</th></tr></thead>
    <tbody>
    <tr><td>Préparation</td><td>Entretien de cadrage — validation des objectifs et du calendrier</td><td>Avant démarrage</td></tr>
    <tr><td>Préparation</td><td>Diagnostic de positionnement et personnalisation du parcours</td><td>Avant 1re séance</td></tr>
    <tr><td>Parcours</td><td><?= $seances ?> séances de 2h30 — <?= htmlspecialchars($duree) ?> d'accompagnement</td><td>Séances 1 à <?= $seances ?></td></tr>
    <tr><td>Clôture</td><td>Évaluation finale, remise du certificat et rapport de formation</td><td>Dernière séance</td></tr>
    <tr><td>Post-formation</td><td>2 séances de coaching de suivi — <strong>OFFERTES</strong></td><td>Après le parcours</td></tr>
    </tbody>
  </table>
</div>

<div class="sec">
  <?= secTitle($n++, 'ÉVALUATION ET CERTIFICATION') ?>
  <table class="tbl">
    <thead><tr><th>Type</th><th>Modalité</th></tr></thead>
    <tbody>
    <tr><td>Diagnostique</td><td>Questionnaire de positionnement en amont</td></tr>
    <tr><td>Formative</td><td><?= htmlspecialchars($evaluation ?: 'Travaux intersessions et mises en situation à chaque séance') ?></td></tr>
    <tr><td>Sommative</td><td>QCM + étude de cas + soutenance du plan d'action individuel</td></tr>
    <tr><td>Satisfaction</td><td>Questionnaire à chaud en clôture de parcours</td></tr>
    </tbody>
  </table>
  <p style="margin-top:6pt">Certificat délivré sous conditions : ≥ 80 % d'assiduité, note ≥ 12/20, remise du plan d'action. <strong><?= htmlspecialchars($certif) ?></strong> — nominatif, numéroté, vérifiable par QR code dans les 17 pays OHADA.</p>
</div>
<?php endif; // typeDoc === 'formation' ?>

<div class="sec">
  <?= secTitle($n++, 'LIVRABLES DU PRESTATAIRE') ?>
  <?php if ($livrable): ?>
  <p><?= nl2br(htmlspecialchars($livrable)) ?></p>
  <?php else: ?>
  <ul>
    <li>Rapport de diagnostic des besoins ;</li>
    <li>Support de formation personnalisé (format numérique) ;</li>
    <li>Boîte à outils : modèles, grilles, guides pratiques ;</li>
    <?php if ($typeDoc === 'formation' && $modeF !== 'presentiel'): ?><li>Enregistrements de l'ensemble des séances ;</li><?php endif; ?>
    <li>Plan d'action individuel formalisé ;</li>
    <?php if ($typeDoc === 'formation'): ?><li>Certificat nominatif vérifiable en ligne ;</li><?php endif; ?>
    <li>Rapport de fin de formation (progression, niveau atteint, recommandations).</li>
  </ul>
  <?php endif; ?>
</div>

<div class="sec">
  <?= secTitle($n++, 'OBLIGATIONS DES PARTIES') ?>
  <table class="tbl">
    <thead><tr><th style="width:50%">À la charge d'IBIG EDUFORM</th><th>À la charge du bénéficiaire</th></tr></thead>
    <tbody><tr>
      <td>Diagnostic · Ingénierie pédagogique personnalisée · Animation par un formateur dédié · Supports et outils numériques · Évaluation &amp; certification · Coaching post-formation</td>
      <td>Disposer du matériel requis · Respecter les créneaux convenus · Réaliser les travaux intersessions · Informer de tout report ≥ 24 h à l'avance</td>
    </tr></tbody>
  </table>
</div>

<div class="sec">
  <?= secTitle($n++, 'CONDITIONS FINANCIÈRES') ?>
  <?= buildPriceTable($prixPres, $prixLigne, $modeF, $fmtTarif, (int)$nbParticipants ?: 1) ?>
  <?= subSec('Conditions de règlement', '<ul>
    <li>50 % à la signature de la convention ;</li>
    <li>50 % à mi-parcours (à l\'issue de la 5e séance) ;</li>
    <li>Règlement en 3 mensualités sans majoration, sur demande ;</li>
    <li>Modalités : virement bancaire, mobile money ou espèces contre reçu ;</li>
    <li>Offre valable 30 jours à compter de la date des présents TDR.</li>
  </ul>') ?>
</div>

<div class="sec">
  <?= secTitle($n++, 'ENGAGEMENTS ET GARANTIES IBIG EDUFORM') ?>
  <table class="tbl">
    <thead><tr><th>Engagement</th><th>Ce que cela signifie</th></tr></thead>
    <tbody>
    <tr><td>Parcours 100 % personnalisé</td><td>Contenu reconstruit à partir du diagnostic : vos défis, vos objectifs, votre contexte professionnel réel.</td></tr>
    <tr><td>Formateur dédié</td><td>Un seul formateur tout au long du parcours — continuité, confiance, zéro temps perdu.</td></tr>
    <tr><td>Confidentialité absolue</td><td>Vos situations professionnelles restent strictement confidentielles.</td></tr>
    <tr><td>Certification reconnue OHADA</td><td>Certificat nominatif, numéroté, vérifiable par QR code dans les 17 pays membres.</td></tr>
    <tr><td>Garantie satisfaction</td><td>Si satisfaction < 80 %, une séance complémentaire offerte.</td></tr>
    </tbody>
  </table>
</div>

<div class="sec">
  <?= secTitle($n++, 'CONTACTS ET SUITE À DONNER') ?>
  <table class="tbl">
    <thead><tr><th>Coordonnée</th><th>Information</th></tr></thead>
    <tbody>
    <tr><td>WhatsApp / Tél.</td><td>+225 07 78 88 25 92</td></tr>
    <tr><td>E-mail</td><td>formation@ibig-eduform.com</td></tr>
    <tr><td>Site web</td><td>https://ibig-eduform.com</td></tr>
    <tr><td>Horaires</td><td>Lun–Ven : 8h00–18h00 | Sam : 9h00–13h00</td></tr>
    </tbody>
  </table>
</div>

<?php endif; // format détaillé ?>

<!-- BLOC DE SIGNATURE -->
<p class="accord">— Lu et approuvé —</p>
<table class="sig-tbl">
  <tr>
    <td>
      <p class="sig-ibig-title">Pour IBIG EDUFORM</p>
      <?php if ($logoB64): ?><img src="<?= $logoB64 ?>" style="height:26pt;display:block;margin:10pt auto" alt=""><br><?php endif; ?>
      <br>
      <p><?= htmlspecialchars($sigIbig) ?></p>
      <p style="font-size:8.5pt;opacity:.7">Signature &amp; Cachet</p>
    </td>
    <td>
      <p><strong>Le bénéficiaire / Commanditaire</strong></p>
      <p style="font-size:9pt;color:#6b7280;margin-top:6pt">Nom &amp; Prénoms : ……………………………………<br>
      Fonction : ……………………………………<br>
      Date : ………………………………………<br><br>
      Signature :<br><br><br></p>
      <p style="font-size:8.5pt;font-style:italic">« Bon pour accord »</p>
    </td>
  </tr>
</table>

<p class="mention-conf">Document confidentiel — IBIG EDUFORM — <?= htmlspecialchars($titre) ?><br>
Certificats reconnus dans les 17 pays membres de l'OHADA · www.ibig-eduform.com</p>

</div>
<?php
$bodyContent = ob_get_clean();

/* ─────────────────────────────────────────────
   SAUVEGARDE HISTORIQUE
───────────────────────────────────────────── */
try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT]
    );
    $nomSlugH = preg_replace('/[^a-z0-9]+/', '-', strtolower($titre));
    $nomSlugH = 'TDR-IBIG-' . strtoupper(trim($nomSlugH, '-')) . '.pdf';
    $stmt = $pdo->prepare("INSERT INTO tdr_historique
        (ref_doc,titre,type_doc,prospect,categorie,format_niveau,nb_participants,prix_en_ligne,prix_presentiel,date_debut,pdf_filename,ip_generateur)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->execute([
        $prixRef, $titre, $typeDoc, $prospect, $categorie, $formatNiveau,
        (int)$nbParticipants, $prixLigne, $prixPres,
        $dateDebut ?: null, $nomSlugH,
        substr($_SERVER['REMOTE_ADDR'] ?? '', 0, 45)
    ]);
} catch (\Throwable $e) {
    error_log('[TDR_HISTORIQUE] ' . $e->getMessage());
}

/* ─────────────────────────────────────────────
   GÉNÉRATION PDF MPDF
───────────────────────────────────────────── */
$nomSlug  = preg_replace('/[^a-z0-9]+/', '-', strtolower($titre));
$nomSlug  = strtoupper(trim($nomSlug, '-') ?: 'tdr-ibig');
$filename = 'TDR-IBIG-EDUFORM-' . $nomSlug . '-' . strtoupper(substr($formatNiveau, 0, 3)) . '.pdf';

try {
    $mpdf = new \Mpdf\Mpdf([
        'mode'          => 'utf-8',
        'format'        => 'A4',
        'margin_top'    => 30,
        'margin_bottom' => 22,
        'margin_left'   => 15,
        'margin_right'  => 15,
        'margin_header' => 6,
        'margin_footer' => 5,
        'default_font'  => 'Arial',
        'tempDir'       => sys_get_temp_dir(),
    ]);

    /* ── Sécurité PDF : impression uniquement, pas de copie ni modification ── */
    $mpdf->SetProtection(
        ['print', 'print-highres'],  // seules ces permissions accordées
        '',                          // mot de passe utilisateur (vide = pas requis pour ouvrir)
        'IBIG@Eduform2026#TDR#Secure',  // mot de passe propriétaire
        128                          // chiffrement 128 bits RC4
    );

    /* ── Métadonnées ── */
    $mpdf->SetTitle('TDR — ' . $titre . ' — IBIG EDUFORM');
    $mpdf->SetAuthor('IBIG EDUFORM — Institut de Formation Professionnelle');
    $mpdf->SetCreator('IBIG EDUFORM · ibig-eduform.com');
    $mpdf->SetSubject('Termes de Référence (TDR) — ' . $typeLabel . ' — Format ' . $formatNiveauLabel);
    $mpdf->SetKeywords('TDR, formation, IBIG EDUFORM, OHADA, certification, ' . $categorie);

    /* ── Filigrane discret ── */
    $mpdf->SetWatermarkText('IBIG EDUFORM');
    $mpdf->showWatermarkText  = true;
    $mpdf->watermarkTextAlpha = 0.025;
    $mpdf->watermarkAngle     = 45;

    /* ── En-tête ── */
    $hdrLeft  = $logoB64     ? '<img src="' . $logoB64 . '" style="height:28px">' : '<b style="font-size:11px;color:#0d1f3c">IBIG EDUFORM</b>';
    $hdrRight = $logoSarlB64 ? '<img src="' . $logoSarlB64 . '" style="height:28px">' : '<span style="font-size:9px;color:#6b7280">IBIG SARL</span>';

    $mpdf->SetHTMLHeader('<table width="100%" style="border-bottom:2px solid #f59e0b;padding-bottom:4px"><tr>
      <td style="width:50%;vertical-align:middle">' . $hdrLeft . '</td>
      <td style="width:50%;text-align:right;vertical-align:middle;font-size:8px;color:#94a3b8">' . htmlspecialchars($titre) . '</td>
    </tr></table>');

    /* ── Pied de page ── */
    $mpdf->SetHTMLFooter('<table width="100%" style="border-top:1px solid #e5e7eb;padding-top:3px"><tr>
      <td style="font-size:7.5px;color:#9ca3af">IBIG EDUFORM — Document confidentiel · Réf&nbsp;: ' . htmlspecialchars($prixRef) . '</td>
      <td style="text-align:right;font-size:7.5px;color:#9ca3af">Page {PAGENO} / {nbpg}</td>
    </tr></table>');

    $mpdf->WriteHTML($css, \Mpdf\HTMLParserMode::HEADER_CSS);
    $mpdf->WriteHTML($bodyContent, \Mpdf\HTMLParserMode::HTML_BODY);
    $mpdf->Output($filename, \Mpdf\Output\Destination::DOWNLOAD);
    exit;

} catch (\Throwable $e) {
    error_log('[TDR_EXPORT_PDF] ' . $e->getMessage());
    http_response_code(500);
    exit('<p style="font-family:sans-serif;text-align:center;margin-top:80px;color:#dc2626">Erreur PDF : ' . htmlspecialchars($e->getMessage()) . '</p>');
}
