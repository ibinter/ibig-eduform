<?php
declare(strict_types=1);
/**
 * IBIG EDUFORM — Générateur automatique de TDR (Termes de Référence)
 * Génère un email HTML professionnel reproduisant fidèlement le style du TDR IBIG.
 * Contenu adapté automatiquement à la catégorie et au nom de la formation.
 */

if (!function_exists('generate_tdr_html')) {

function generate_tdr_html(array $f, string $nomProspect = '', array $opts = []): string
{
    $h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');

    $nom        = (string)($f['name'] ?? '');
    $cat        = (string)($f['category'] ?? 'Autres');
    $desc       = (string)($f['description'] ?? '');
    $prix       = (int)($f['price'] ?? 0);
    $slug       = (string)($f['slug'] ?? '');
    $prixPresDB = (int)($f['prix_pres'] ?? 0);

    /* ── Niveau (optionnel) ──────────────────────────────────────────── */
    $niveau_code = trim(strtolower((string)($f['niveau'] ?? '')));
    $niveau_labels = ['debutant' => 'Débutant', 'intermediaire' => 'Intermédiaire', 'expert' => 'Expert'];
    $niveau_label  = $niveau_labels[$niveau_code] ?? '';
    $niveau_emojis = ['debutant' => '🟢', 'intermediaire' => '🔵', 'expert' => '🟣'];
    $niveau_emoji  = $niveau_emojis[$niveau_code] ?? '';

    /* Modules depuis la base si un niveau_id est fourni */
    $_niveau_modules_db = [];
    if (!empty($f['niveau_id'])) {
        try {
            require_once __DIR__ . '/database.php';
            $_pdo_tdr = Database::connect();
            $_niveau_modules_db = $_pdo_tdr->prepare(
                "SELECT titre, contenus, duree_heures FROM formation_niveau_modules WHERE niveau_id = :nid ORDER BY ordre ASC"
            );
            $_niveau_modules_db->execute([':nid' => (int)$f['niveau_id']]);
            $_niveau_modules_db = $_niveau_modules_db->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $_e) { /* silencieux */ }
    }
    /* Prérequis et objectifs niveau si fournis */
    $prerequis_niveau  = trim((string)($f['prerequis_niveau'] ?? ''));
    $objectifs_niveau  = trim((string)($f['objectifs_niveau'] ?? ''));
    $public_niveau     = trim((string)($f['public_niveau'] ?? ''));
    $dureeDB    = (string)($f['duree'] ?? '');

    /* Options inscrit */
    $modeForm    = (string)($opts['mode_formation'] ?? 'en_ligne');
    $formatForm  = (string)($opts['format_formation'] ?? 'individuel');
    $dateDebut   = (string)($opts['date_debut'] ?? '');
    $creneauPref = (string)($opts['creneau'] ?? '');

    /* Labels lisibles */
    $modeLabels   = ['en_ligne'=>'En ligne (classe virtuelle)','presentiel'=>'Présentiel','hybride'=>'Hybride (En ligne et en présentiel)'];
    $formatLabels = ['individuel'=>'Individuel — un(e) apprenant(e), un(e) formateur/trice dédié(e)',
                     'groupe_3_5'=>'Groupe 3–5 personnes','groupe_6_10'=>'Groupe 6–10 personnes',
                     'groupe_10p'=>'Groupe 10+ personnes',
                     'groupe_devis'=>'Formation groupe — tarif sur devis'];
    $creneauLabels= ['matin_gmt'=>'Matin 7h–10h GMT','journee_gmt'=>'Journée 10h–15h GMT',
                     'aprem_gmt'=>'Après-midi 15h–18h GMT','soir_gmt'=>'Soir 18h–21h GMT','week_end'=>'Week-end'];
    $modeLabel   = $modeLabels[$modeForm]   ?? $modeForm;
    $formatLabel = $formatLabels[$formatForm] ?? $formatForm;
    $creneauLabel= $creneauLabels[$creneauPref] ?? 'Selon disponibilités mutuelles';

    $prixHybDB = (int)($f['prix_hyb'] ?? 0);

    $r5 = fn(float $v): int => (int)(round($v / 5000) * 5000);
    $fcfa = fn(int $v): string => number_format($v, 0, ',', ' ') . ' F CFA';

    /* ── Volume horaire : DB en priorité, sinon extraction du nom/desc, sinon inférence ── */
    if ($dureeDB !== '' && preg_match('/^(\d+)H$/i', $dureeDB, $_dm)) {
        // Ex : "25H"
        $heures = (int)$_dm[1];
    } elseif ($dureeDB !== '' && preg_match('/\((\d+)h\)/i', $dureeDB, $_dm)) {
        // Ex : "1 samedi (6h)", "2 samedis (12h)"
        $heures = (int)$_dm[1];
    } elseif ($dureeDB !== '' && preg_match('/^(\d+)\s*mois?$/i', $dureeDB, $_dm)) {
        // Ex : "1 mois", "2 mois", "4 mois" — 25h par mois
        $heures = (int)$_dm[1] * 25;
        // Grille EDUFORM basée sur les heures (DB non corrigée pour durées en mois)
        if ($heures <= 15)     { $prix = 200000; $prixPresDB = 250000; }
        elseif ($heures <= 25) { $prix = 250000; $prixPresDB = 300000; }
        elseif ($heures <= 45) { $prix = 350000; $prixPresDB = 400000; }
        elseif ($heures <= 55) { $prix = 400000; $prixPresDB = 450000; }
        else                   { $prix = 450000; $prixPresDB = 500000; }
    } elseif (preg_match('/\((\d+)h\)/i', $nom . ' ' . $desc, $_hm)) {
        $heures = (int)$_hm[1];
    } else {
        // Inférence : prix ÷ 11 250, arrondi à 5h, minimum 20h
        $heures = $prix > 0 ? max(20, (int)(round($prix / 11250 / 5) * 5)) : 25;
    }
    $seances = (int)ceil($heures / 2.5);

    /* ── Calcul grille — même formule que formation-detail.php / catalogue ── */
    $grille = [];
    if ($prix > 0) {
        $ip  = $prixPresDB > 0 ? $prixPresDB : $r5($prix * 10 / 7);
        $g35 = $r5($ip * 0.70);
        $g61 = $r5($ip * 0.55);
        $g10 = $r5($ip * 0.45);
        $grille = [
            'elearning_online'   => $r5($prix * 0.5),
            'individuel_online'  => $prix,
            'individuel_pres'    => $ip,
            'hybride'            => $prixHybDB > 0 ? $prixHybDB : $r5(($prix + $ip) / 2),
            'groupe_3_5_online'  => $r5($g35 * 0.70),
            'groupe_3_5_pres'    => $g35,
            'groupe_6_10_online' => $r5($g61 * 0.70),
            'groupe_6_10_pres'   => $g61,
            'groupe_10p_online'  => $r5($g10 * 0.70),
            'groupe_10p_pres'    => $g10,
        ];
    }
    $prixPres = $grille['individuel_pres'] ?? 0;

    /* ── Prix selon format choisi ── */
    $prixChoisi = 0;
    if ($prix > 0 && !empty($grille)) {
        $mapFormat = [
            'individuel'  => 'individuel_online',
            'groupe_3_5'  => 'groupe_3_5_online',
            'groupe_6_10' => 'groupe_6_10_online',
            'groupe_10p'  => 'groupe_10p_online',
        ];
        $keyOnl  = $mapFormat[$formatForm] ?? 'individuel_online';
        $keyPres = str_replace('_online','_pres',$keyOnl);
        if ($modeForm === 'hybride' && isset($grille['hybride'])) {
            $prixChoisi = (int)$grille['hybride'];
        } elseif ($modeForm === 'presentiel' && isset($grille[$keyPres])) {
            $prixChoisi = (int)$grille[$keyPres];
        } elseif (isset($grille[$keyOnl])) {
            $prixChoisi = (int)$grille[$keyOnl];
        }
    }
    $isGroupeDevis = ($formatForm === 'groupe_devis');
    $prixAffiche = $isGroupeDevis ? 0 : ($prixChoisi > 0 ? $prixChoisi : $prix);
    $premiereTranche = $prixAffiche > 0 ? (int)round($prixAffiche * 0.5 / 5000) * 5000 : 0;

    /* ── Référence offre ── */
    $catCode = mb_strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $cat), 0, 4), 'UTF-8');
    $ref = 'IBIG-EDU/' . $catCode . '/' . date('Y') . '/' . strtoupper(substr(preg_replace('/[^a-z0-9]/', '', strtolower($slug)), 0, 8));

    /* ── Contenu par catégorie (avec surcharge par mot-clé dans le nom) ── */
    $tpl = tdr_templates();
    /* Détection spécifique : formations Claude d'Anthropic */
    if (preg_match('/\bclaude\b/ui', $nom)) {
        $data = $tpl['Claude (Anthropic)'];
    } else {
        $data = $tpl[$cat] ?? $tpl['_default'];
    }

    /* Adapter le contexte et l'objectif général avec le nom réel */
    $nomCourt = preg_replace('/\s*[\(\[].*?[\)\]]\s*/', '', $nom); // supprimer parenthèses
    $contexte = str_replace('{NOM}', $nomCourt, $data['contexte']);
    $objGen   = str_replace('{NOM}', $nomCourt, $data['objectif_general']);
    $objSpec  = $data['objectifs_specifiques'];
    $public   = $public_niveau !== '' ? $public_niveau : $data['public_cible'];
    $prereqs  = $prerequis_niveau !== '' ? $prerequis_niveau : $data['prerequis'];
    /* Modules : priorité aux modules DB du niveau, sinon génération auto */
    if (!empty($_niveau_modules_db)) {
        $modules = array_map(fn($m) => [
            'titre'    => $m['titre'],
            'contenus' => $m['contenus'] ?? '',
            'duree'    => (int)$m['duree_heures'],
        ], $_niveau_modules_db);
    } else {
        $modules = tdr_modules($nom, $cat, $heures, $desc, $niveau_code);
    }
    /* Surcharger objectifs spécifiques avec ceux du niveau si dispo */
    if ($objectifs_niveau !== '') {
        $objSpec = array_map('trim', explode("\n", $objectifs_niveau));
        $objSpec = array_filter($objSpec);
    }
    /* Ajouter le niveau au titre affiché si applicable */
    if ($niveau_label !== '') {
        $nom = $nom . ' — Niveau ' . $niveau_label;
    }
    $methodo  = $data['methodologie'];
    $debouches= str_replace('{NOM}', $nomCourt, $data['debouches']);

    /* ── HTML ── */
    $style = '
    <style>
    body{margin:0;padding:0;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif}
    .wrap{max-width:760px;margin:24px auto;background:#fff;border:1px solid #ddd}
    /* En-tête */
    .hd{text-align:center;padding:22px 30px 16px;border-bottom:3px solid #f59e0b}
    .hd-title{font-size:13px;font-weight:normal;color:#555;margin:0 0 2px}
    .hd-inst{font-size:11px;color:#777;margin:0}
    .tdr-title{font-size:20px;font-weight:900;color:#0a1733;text-transform:uppercase;margin:18px 0 4px;letter-spacing:.04em}
    .tdr-sub{font-size:14px;font-weight:700;color:#b45309;text-transform:uppercase;margin:0 0 4px}
    .tdr-nom{font-size:16px;font-weight:900;color:#0a1733;margin:0 0 4px}
    .tdr-acc{font-size:12px;font-style:italic;color:#6b7280;margin:0}
    /* Fiche */
    .fiche{width:100%;border-collapse:collapse;margin:18px 0}
    .fiche-hd{background:#0a1733;color:#fff;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;padding:8px 12px}
    .fiche tr td{font-size:12px;padding:7px 12px;border:1px solid #d1d5db;vertical-align:top}
    .fiche tr td:first-child{font-weight:700;color:#374151;width:200px;background:#f8fafc}
    /* Sections */
    .sec{padding:0 30px 20px}
    .sec-h{font-size:13px;font-weight:900;color:#0a1733;text-transform:uppercase;letter-spacing:.05em;border-bottom:2px solid #f59e0b;padding-bottom:5px;margin:24px 0 12px}
    .sec-h2{font-size:12px;font-weight:700;color:#b45309;margin:14px 0 6px}
    .sec p{font-size:12px;color:#374151;line-height:1.7;margin:6px 0;text-align:justify}
    .sec ul{margin:6px 0 10px 18px;padding:0}
    .sec ul li{font-size:12px;color:#374151;line-height:1.65;margin-bottom:3px}
    /* Tableaux */
    .tbl{width:100%;border-collapse:collapse;margin:10px 0}
    .tbl th{background:#0a1733;color:#fff;font-size:11px;font-weight:700;padding:8px 10px;text-align:center}
    .tbl th:first-child{text-align:left}
    .tbl td{font-size:11px;color:#374151;padding:7px 10px;border:1px solid #e5e7eb;vertical-align:top}
    .tbl td:first-child{font-weight:700;color:#0a1733;background:#f8fafc}
    .tbl tr:nth-child(even) td{background:#f9fafb}
    .tbl .tbl-total td{background:#0a1733;color:#fff;font-weight:900;text-align:center}
    .tbl-alt th{background:#b45309}
    /* Résultats */
    .res-tbl th{background:#1e3a6e}
    /* Budget */
    .bud-tbl td:nth-child(3),.bud-tbl td:nth-child(4),.bud-tbl td:nth-child(5){text-align:center}
    /* Obligations */
    .obl-tbl th{background:#374151}
    /* Garanties */
    .gar-tbl td:first-child{font-weight:700;color:#0a1733;background:#fffbeb;width:220px}
    /* Footer section */
    .foot{background:#f3f4f6;padding:16px 30px;font-size:11px;color:#6b7280;text-align:center;border-top:1px solid #e5e7eb}
    /* Signature */
    .sig-tbl{width:100%;border-collapse:collapse;margin:16px 0}
    .sig-tbl td{border:2px solid #0a1733;padding:20px;width:50%;vertical-align:top;font-size:11px}
    .sig-tbl td:first-child{background:#e8edf5;color:#0a1733;font-weight:700;text-align:center;font-size:12px;-webkit-print-color-adjust:exact;print-color-adjust:exact}
    .sig-tbl .sig-ibig-title{font-size:13px;font-weight:900;margin-bottom:8px;color:#0a1733}
    .accord{text-align:center;font-style:italic;font-weight:700;font-size:13px;color:#0a1733;margin:18px 0 8px}
    </style>';

    $html = '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8">' . $style . '</head><body>
<div class="wrap">

  <!-- EN-TÊTE -->
  <div class="hd">
    <p class="hd-title"><strong>IBIG EDUFORM</strong></p>
    <p class="hd-inst">Institut de Formation Professionnelle — INTERMARK BUSINESS INTERNATIONAL GROUP<br>
    www.ibig-eduform.com &nbsp;|&nbsp; Abidjan – Côte d\'Ivoire</p>
    <p class="tdr-title">Termes de Référence (TDR)</p>
    <p class="tdr-sub">Parcours Certifiant &mdash; ' . $h($modeLabel) . '</p>
    <p class="tdr-nom">' . $h($nom) . '</p>
    <p class="tdr-acc">Accompagnement personnalisé — ' . $heures . ' heures — ' . $h($modeLabel) . '</p>
  </div>

  <!-- FICHE SIGNALÉTIQUE -->
  <div class="sec">
    <table class="fiche">
      <tr><td colspan="2" class="fiche-hd">Fiche signalétique de l\'action de formation</td></tr>
      <tr><td>Intitulé</td><td>' . $h($nom) . ' — Parcours individuel certifiant</td></tr>
      <tr><td>Bénéficiaire</td><td>' . ($nomProspect !== '' ? '<strong>' . $h($nomProspect) . '</strong>' : '………………………………………… (nom et fonction)') . '</td></tr>
      <tr><td>Commanditaire</td><td>………………………………………… (à titre personnel ou raison sociale)</td></tr>
      <tr><td>Prestataire</td><td>IBIG EDUFORM — Institut de Formation Professionnelle</td></tr>
      <tr><td>Format choisi</td><td><strong>' . $h($formatLabel) . '</strong></td></tr>
      <tr><td>Modalité choisie</td><td><strong>' . $h($modeLabel) . '</strong></td></tr>
      <tr><td>Volume horaire</td><td>' . $heures . ' heures réparties en ' . $seances . ' séances de 2 h 30</td></tr>
      <tr><td>Rythme</td><td>2 séances par semaine sur ' . max(3, (int)ceil($seances / 2)) . ' semaines (ajustable selon disponibilités)</td></tr>
      <tr><td>Créneaux préférés</td><td>' . $h($creneauLabel) . ' <em style="font-size:10px;color:#6b7280">(tous les horaires IBIG EDUFORM sont en GMT)</em></td></tr>
      <tr><td>Date souhaitée</td><td>' . ($dateDebut !== '' ? '<strong>' . $h(date('d/m/Y', strtotime($dateDebut))) . '</strong> (à confirmer lors de l\'entretien de cadrage)' : 'À définir lors de l\'entretien de cadrage') . '</td></tr>
      <tr><td>Sanction</td><td>Certificat de compétences nominatif, numéroté et vérifiable en ligne (QR code)</td></tr>
      <tr><td>Coût (formule choisie)</td><td>' . ($prixAffiche > 0 ? '<strong style="font-size:14px;color:#b45309">' . $fcfa($prixAffiche) . '</strong> &nbsp;—&nbsp; 1ère tranche : <strong>' . $fcfa($premiereTranche) . '</strong>' : 'Sur devis personnalisé') . '</td></tr>
      <tr><td>Période proposée</td><td>………………………………………… (à confirmer)</td></tr>
      <tr><td>Référence offre</td><td>' . $h($ref) . '</td></tr>
    </table>
  </div>

  <!-- 1. CONTEXTE -->
  <div class="sec">
    <div class="sec-h">1. Contexte et justification</div>
    ' . $contexte . '
  </div>

  <!-- 2. OBJECTIFS -->
  <div class="sec">
    <div class="sec-h">2. Objectifs de la formation</div>
    <div class="sec-h2">2.1. Objectif général</div>
    <p>' . $h($objGen) . '</p>
    <div class="sec-h2">2.2. Objectifs spécifiques</div>
    <p>À l\'issue du parcours, le bénéficiaire sera capable de :</p>
    <ul>' . implode('', array_map(fn($o) => '<li>' . $h($o) . '</li>', $objSpec)) . '</ul>
  </div>

  <!-- 3. RÉSULTATS -->
  <div class="sec">
    <div class="sec-h">3. Résultats attendus et indicateurs</div>
    <table class="tbl res-tbl">
      <tr><th>Résultat attendu</th><th>Indicateur de vérification</th></tr>
      <tr><td>Le bénéficiaire maîtrise les fondamentaux du domaine <em>' . $h($cat) . '</em></td><td>Note égale ou supérieure à 12/20 à l\'évaluation finale de certification</td></tr>
      <tr><td>Les acquis sont transposés sur le terrain professionnel réel</td><td>Résolution documentée d\'au moins 3 situations professionnelles vécues durant le parcours</td></tr>
      <tr><td>Le bénéficiaire dispose d\'outils opérationnels immédiatement utilisables</td><td>Boîte à outils numérique complète remise et mise en service durant le parcours</td></tr>
      <tr><td>Un plan de progression personnel est formalisé</td><td>1 plan d\'action individuel présenté et soutenu en séance de clôture</td></tr>
    </table>
  </div>

  <!-- 4. BÉNÉFICIAIRE -->
  <div class="sec">
    <div class="sec-h">4. Bénéficiaire, prérequis et accessibilité</div>
    <div class="sec-h2">4.1. Profil concerné</div>
    <p>' . $h($public) . '</p>
    <div class="sec-h2">4.2. Prérequis pédagogiques</div>
    <p>' . $h($prereqs) . '</p>
    <div class="sec-h2">4.3. Prérequis techniques</div>
    <ul>
      <li>Un ordinateur équipé d\'une webcam et d\'un micro (casque audio vivement recommandé) ;</li>
      <li>Une connexion internet d\'un débit minimal de 2 Mbps en réception ;</li>
      <li>Un espace de travail calme permettant des échanges confidentiels ;</li>
      <li>Une adresse e-mail valide pour l\'accès à l\'espace apprenant en ligne.</li>
    </ul>
    <div class="sec-h2">4.4. Accessibilité</div>
    <p>Les modalités pédagogiques et techniques peuvent être adaptées à un bénéficiaire en situation de handicap, sur signalement préalable au plus tard sept (07) jours avant le démarrage.</p>
  </div>

  <!-- 5. MODULES -->
  <div class="sec">
    <div class="sec-h">5. Contenu pédagogique détaillé</div>
    <p>Le parcours est organisé en ' . count($modules) . ' modules complémentaires, articulés selon une progression logique allant des fondamentaux vers la maîtrise opérationnelle. La répartition horaire est indicative et peut être ajustée après le diagnostic préalable.</p>
    <table class="tbl">
      <tr><th style="width:50px">N°</th><th>Module</th><th>Contenus clés</th><th style="width:60px;text-align:center">Durée</th></tr>
      ' . implode('', array_map(function($m, $i) use ($h) {
          return '<tr><td style="text-align:center">M' . ($i + 1) . '</td><td>' . $h($m['titre']) . '</td><td>' . $h($m['contenus']) . '</td><td style="text-align:center">' . $m['duree'] . ' h</td></tr>';
      }, $modules, array_keys($modules))) . '<tr class="tbl-total"><td colspan="3" style="background:#0a1733;-webkit-print-color-adjust:exact;print-color-adjust:exact;color:#fff;font-size:11px;padding:8px 10px">TOTAL VOLUME HORAIRE</td><td style="background:#0a1733;-webkit-print-color-adjust:exact;print-color-adjust:exact;color:#fff;text-align:center">' . $heures . ' h</td></tr>
    </table>
  </div>

  <!-- 6. MÉTHODOLOGIE -->
  <div class="sec">
    <div class="sec-h">6. Approche méthodologique</div>
    <p>Le parcours ne reproduit pas un cours magistral devant une caméra. Il est conçu comme un accompagnement de type « formation-coaching » : 20 % d\'apports théoriques structurants et 80 % de travail sur les situations réelles du bénéficiaire. Le bénéficiaire n\'apprend pas en écoutant — il apprend en agissant.</p>
    <ul>' . implode('', array_map(fn($m) => '<li>' . $h($m) . '</li>', $methodo)) . '</ul>
  </div>

  <!-- 7. DISPOSITIF TECHNIQUE -->
  <div class="sec">
    <div class="sec-h">7. Dispositif technique et organisation à distance</div>
    <table class="tbl tbl-alt">
      <tr><th>Composante</th><th>Description</th></tr>
      <tr><td>Plateforme de classe virtuelle</td><td>Séances animées via une solution de visioconférence professionnelle (ZOOM). Un lien de connexion nominatif est transmis avant chaque séance.</td></tr>
      <tr><td>Espace apprenant en ligne</td><td>Accès à un espace numérique dédié regroupant les supports, les outils, les travaux intersessions et les ressources complémentaires, consultables 24h/24.</td></tr>
      <tr><td>Enregistrement des séances</td><td>Chaque séance est enregistrée et mise à disposition du bénéficiaire, qui peut ainsi revoir les points clés à son rythme.</td></tr>
      <tr><td>Suivi de l\'assiduité</td><td>Relevé de connexion horodaté par séance, tenant lieu de feuille de présence et joint au rapport de fin de formation.</td></tr>
      <tr><td>Continuité du service</td><td>En cas d\'incident technique majeur imputable au prestataire, la séance est reportée sans frais et sans imputation sur le volume horaire contractuel.</td></tr>
      <tr><td>Souplesse de planification</td><td>Les séances sont programmées d\'un commun accord, y compris en horaires décalés ou en fin de journée. Tout report demandé au moins 24 h à l\'avance est accepté sans pénalité.</td></tr>
    </table>
  </div>

  <!-- 8. DÉROULEMENT -->
  <div class="sec">
    <div class="sec-h">8. Déroulement et organisation du parcours</div>
    <p>Le parcours se déroule en trois étapes : une phase de préparation, le parcours de formation proprement dit (' . $heures . ' heures), puis une phase de consolidation. Aucune date n\'est imposée : l\'ensemble du calendrier est arrêté d\'un commun accord lors de l\'entretien de cadrage.</p>
    <table class="tbl">
      <tr><th>Étape</th><th>Activité</th><th>Repère</th></tr>
      <tr><td>Préparation</td><td>Entretien de cadrage en visioconférence : validation des objectifs personnels, du rythme et du calendrier des séances</td><td>Avant le démarrage</td></tr>
      <tr><td>Préparation</td><td>Diagnostic : questionnaire de positionnement et analyse du contexte professionnel du bénéficiaire</td><td>Avant le démarrage</td></tr>
      <tr><td>Préparation</td><td>Personnalisation du parcours et des cas pratiques, ouverture de l\'espace apprenant en ligne</td><td>Avant la première séance</td></tr>
      <tr><td>Parcours</td><td>Déroulement des ' . $seances . ' séances de 2 h 30, soit ' . $heures . ' heures d\'accompagnement individuel</td><td>Séances 1 à ' . $seances . '</td></tr>
      <tr><td>Parcours</td><td>Évaluation de certification et soutenance du plan d\'action individuel</td><td>Dernière séance</td></tr>
      <tr><td>Clôture</td><td>Édition et remise du certificat, transmission du rapport de fin de formation</td><td>À l\'issue du parcours</td></tr>
      <tr><td>Consolidation</td><td>Deux (02) séances de coaching de suivi à distance — OFFERTES</td><td>Aux dates choisies par le bénéficiaire</td></tr>
    </table>
    <div class="sec-h2">8.1. Formules de rythme proposées</div>
    <table class="tbl">
      <tr><th>Formule</th><th>Fréquence</th><th>Durée totale du parcours</th></tr>
      <tr><td>Intensive</td><td>3 à 4 séances par semaine</td><td>Environ ' . max(2, (int)ceil($seances / 3.5)) . ' semaines</td></tr>
      <tr><td>Standard</td><td>2 séances par semaine</td><td>Environ ' . max(3, (int)ceil($seances / 2)) . ' semaines</td></tr>
      <tr><td>Étalée</td><td>1 séance par semaine</td><td>Environ ' . $seances . ' semaines</td></tr>
    </table>
  </div>

  <!-- 9. PROFIL FORMATEUR -->
  <div class="sec">
    <div class="sec-h">9. Profil du formateur</div>
    <p>Le parcours est confié à un formateur-consultant unique et dédié, garant de la continuité pédagogique et de la relation de confiance indispensable à un accompagnement individuel. Il est sélectionné selon les critères suivants :</p>
    <ul>
      <li>Diplôme de niveau Bac+4/5 en ' . $h($cat) . ' ou discipline connexe ;</li>
      <li>Expérience confirmée d\'au moins cinq (05) années en entreprise dans le domaine concerné ;</li>
      <li>Expérience avérée en animation de formations et d\'accompagnements individuels ;</li>
      <li>Maîtrise des techniques de pédagogie active à distance et des outils de classe virtuelle ;</li>
      <li>Engagement de confidentialité absolue sur les situations professionnelles exposées par le bénéficiaire.</li>
    </ul>
  </div>

  <!-- 10. ÉVALUATION -->
  <div class="sec">
    <div class="sec-h">10. Modalités d\'évaluation et de certification</div>
    <div class="sec-h2">10.1. Dispositif d\'évaluation</div>
    <table class="tbl">
      <tr><th>Type d\'évaluation</th><th>Modalité</th></tr>
      <tr><td>Évaluation diagnostique</td><td>Questionnaire de positionnement en amont, mesurant le niveau d\'entrée</td></tr>
      <tr><td>Évaluation formative</td><td>Travaux intersessions et mises en situation débriefées à chaque séance</td></tr>
      <tr><td>Évaluation sommative</td><td>Épreuve en ligne (QCM et étude de cas) et soutenance orale du plan d\'action individuel</td></tr>
      <tr><td>Évaluation de satisfaction</td><td>Questionnaire à chaud à l\'issue de la dernière séance</td></tr>
    </table>
    <div class="sec-h2">10.2. Conditions de certification</div>
    <p>Le certificat est délivré au bénéficiaire remplissant cumulativement les conditions suivantes : participation effective à au moins 80 % du volume horaire, note finale égale ou supérieure à 12/20 et remise du plan d\'action individuel. Le certificat est nominatif, numéroté et doté d\'un code de vérification en ligne (QR code) garantissant son authenticité. En cas de note inférieure au seuil requis, une attestation de participation est délivrée et une séance de rattrapage est organisée sans frais supplémentaires.</p>
  </div>

  <!-- 11. LIVRABLES -->
  <div class="sec">
    <div class="sec-h">11. Livrables du prestataire</div>
    <ul>
      <li>Un rapport de diagnostic des besoins, établi avant le démarrage ;</li>
      <li>Un support de formation complet et personnalisé, au format numérique ;</li>
      <li>Une boîte à outils numérique : modèles, grilles, trames et guides pratiques du domaine ;</li>
      <li>Les enregistrements de l\'intégralité des séances ;</li>
      <li>Le plan d\'action individuel formalisé et validé ;</li>
      <li>Le certificat nominatif vérifiable en ligne ;</li>
      <li>Le relevé d\'assiduité et le rapport de fin de formation (niveau atteint, progression observée, recommandations).</li>
    </ul>
  </div>

  <!-- 12. OBLIGATIONS -->
  <div class="sec">
    <div class="sec-h">12. Obligations des parties</div>
    <table class="tbl obl-tbl">
      <tr><th>À la charge d\'IBIG EDUFORM</th><th>À la charge du bénéficiaire</th></tr>
      <tr>
        <td>Diagnostic et ingénierie pédagogique personnalisée · Mise à disposition de la plateforme et de l\'espace apprenant · Animation par un formateur dédié · Supports et boîte à outils numériques · Enregistrement des séances · Évaluation, certification et rapports · Coaching de suivi post-formation</td>
        <td>Disposer du matériel et de la connexion requis · Se rendre disponible aux créneaux convenus · Réaliser les travaux intersessions · Prévenir de tout report au moins 24 h à l\'avance · Fournir les éléments de contexte nécessaires à la personnalisation des cas pratiques</td>
      </tr>
    </table>
  </div>

  <!-- 13. BUDGET -->
  <div class="sec">
    <div class="sec-h">13. Budget et conditions financières</div>
    ' . ($prix > 0 ? '<p>Les tarifs ci-dessous sont ceux du catalogue officiel IBIG EDUFORM, en FCFA par personne :</p>
    <table class="tbl bud-tbl">
      <tr><th>Formule</th><th>En ligne</th><th>Présentiel</th></tr>

      <tr style="background:#fffbeb"><td><strong>👤 Individuel (accompagné)</strong></td><td style="text-align:center"><strong>' . $fcfa($grille['individuel_online']) . '</strong></td><td style="text-align:center"><strong>' . $fcfa($grille['individuel_pres']) . '</strong></td></tr>
      <tr><td>🔀 Hybride (En ligne et en présentiel)</td><td colspan="2" style="text-align:center">' . $fcfa($grille['hybride'] ?? 0) . '</td></tr>
      <tr><td colspan="3" style="text-align:center;font-style:italic;color:#6b7280">👥 Formation groupe &amp; intra-entreprise — <strong>Sur devis personnalisé</strong></td></tr>
    </table>
    <p style="font-size:11px;color:#6b7280;margin-top:6px">Prix par personne, indicatifs. Forfait tout compris : diagnostic · animation · supports · certification · coaching post-formation.</p>' : '<p>Le coût de ce parcours est établi sur devis personnalisé. Contactez-nous pour un chiffrage adapté à votre profil et vos objectifs.</p>') . '
    <div class="sec-h2">13.1. Conditions de règlement</div>
    <ul>
      <li>50 % à la signature de la convention, valant confirmation du calendrier ;</li>
      <li>50 % à l\'issue de la cinquième séance, soit à mi-parcours ;</li>
      <li>Possibilité de règlement en trois (03) mensualités sans majoration, sur demande expresse du bénéficiaire ;</li>
      <li>Règlement par virement bancaire, mobile money ou espèces, contre reçu ;</li>
      <li>Offre valable trente (30) jours à compter de la date de transmission des présents Termes de Référence.</li>
    </ul>
  </div>

  <!-- 14. VALEUR AJOUTÉE -->
  <div class="sec">
    <div class="sec-h">14. Valeur ajoutée et garanties d\'IBIG EDUFORM</div>
    <table class="tbl gar-tbl">
      <tr><td>Un parcours entièrement recentré sur vous</td><td>Le contenu est reconstruit à partir de votre diagnostic : vos défis, vos objectifs, votre contexte. Vous ne travaillez jamais sur des cas fictifs.</td></tr>
      <tr><td>Un formateur pour vous seul</td><td>Aucun temps d\'attente, aucune question laissée de côté, aucune concurrence d\'attention. Les ' . $heures . ' heures vous sont intégralement consacrées.</td></tr>
      <tr><td>Une confidentialité totale</td><td>Vous pouvez exposer sans réserve des situations sensibles — ce qu\'un groupe ne permet jamais.</td></tr>
      <tr><td>Un rythme qui s\'adapte à votre activité</td><td>Créneaux choisis avec vous, reports acceptés sous 24 h, formule accélérée possible. Votre travail n\'est pas interrompu.</td></tr>
      <tr><td>Une certification qui a de la valeur</td><td>Certificat nominatif, numéroté et vérifiable en ligne par QR code : une reconnaissance opposable à tout employeur ou partenaire.</td></tr>
      <tr><td>Une garantie de satisfaction</td><td>Si votre évaluation de satisfaction est inférieure à 80 %, une séance complémentaire de 2 h 30 vous est assurée sans frais.</td></tr>
    </table>
  </div>

  <!-- 15. CONFIDENTIALITÉ -->
  <div class="sec">
    <div class="sec-h">15. Confidentialité et propriété intellectuelle</div>
    <p>IBIG EDUFORM et son formateur s\'engagent à la stricte confidentialité de toute information relative au bénéficiaire, à son employeur et aux situations professionnelles exposées durant le parcours. Les enregistrements de séances sont à usage exclusif du bénéficiaire et ne font l\'objet d\'aucune diffusion. Les supports pédagogiques demeurent la propriété intellectuelle d\'IBIG EDUFORM et sont concédés au bénéficiaire pour un usage personnel.</p>
  </div>

  <!-- 16. CONTACTS -->
  <div class="sec">
    <div class="sec-h">16. Contacts et suite à donner</div>
    <p>Pour toute précision, ajustement du parcours ou planification de l\'entretien de cadrage :</p>
    <table class="tbl tbl-alt">
      <tr><th>Coordonnée</th><th>Information</th></tr>
      <tr><td>Structure</td><td>IBIG EDUFORM — Institut de Formation Professionnelle</td></tr>
      <tr><td>Téléphone / WhatsApp</td><td>+225 07 78 88 25 92</td></tr>
      <tr><td>Téléphones</td><td>+225 07 78 88 25 92 &nbsp;·&nbsp; +225 05 65 90 47 79 &nbsp;·&nbsp; +225 01 53 59 55 44</td></tr>
      <tr><td>E-mail</td><td>formation@ibig-eduform.com &nbsp;·&nbsp; formation@intermark-business.com</td></tr>
      <tr><td>Site web</td><td>https://ibig-eduform.com</td></tr>
      <tr><td>Horaires</td><td>Lundi – Vendredi : 8h00 – 18h00 | Samedi : 9h00 – 13h00</td></tr>
    </table>
    <p style="font-weight:700;text-align:justify;margin-top:14px">La validation des présents Termes de Référence vaut accord de principe et déclenche l\'entretien de cadrage. Le calendrier des séances est arrêté conjointement dans les trois (03) jours suivant cette validation, pour un démarrage possible dès la semaine suivante.</p>
    <table class="sig-tbl">
      <tr>
        <td class="sig-tbl">
          <div class="sig-ibig-title">Pour IBIG EDUFORM</div>
          <p style="font-size:11px;margin:8px 0 0">Le Directeur Général<br><br><br><br>Signature &amp; Cachet</p>
        </td>
        <td>
          <p style="font-size:12px;font-weight:700;color:#0a1733;margin:0 0 10px">Pour le bénéficiaire / le commanditaire</p>
          <p style="font-size:11px;line-height:2;margin:0">
            Nom et prénoms : ' . ($nomProspect !== '' ? '…………………………………' : '…………………………………') . '<br>
            Fonction : …………………………………<br>
            Date : …………………………………<br>
            Signature
          </p>
        </td>
      </tr>
    </table>
    <p class="accord">« Bon pour accord »</p>
  </div>

  <!-- FOOTER -->
  <div class="foot">
    IBIG EDUFORM — TDR Parcours individuel · ' . $h($nom) . '<br>
    Ce document est confidentiel et destiné exclusivement à son destinataire.
  </div>

</div>
</body></html>';

    return $html;
}

/* ══════════════════════════════════════════════════════════
   GÉNÉRATEUR DE MODULES PAR FORMATION
══════════════════════════════════════════════════════════ */

function _extract_topics_gen(string $desc): array {
    $desc = preg_replace('/\b(Pour |Disponible en |À partir de |Code\s*:)[^.]+\./ui', '', $desc);
    if (!preg_match('/:\s*(.+)$/su', $desc, $m)) return [];
    $raw = preg_split('/\.\s+[A-ZÀÂÉÈÊÙÛÎÏ]/u', trim($m[1]))[0];
    $raw = preg_replace('/\s*\([^)]*\)/', '', $raw);
    $topics = array_map('trim', preg_split('/[,;]\s*/u', $raw));
    return array_values(array_filter($topics, function($t) {
        $len = mb_strlen($t, 'UTF-8');
        if ($len < 20 || $len > 90) return false;               // trop court = fragment
        $first = mb_substr($t, 0, 1, 'UTF-8');
        if ($first === mb_strtolower($first, 'UTF-8')) return false; // commence par minuscule = verbe impératif
        return true;
    }));
}

function _get_fillers_for_cat(string $cat, string $nom): array {
    $n = mb_strtolower($nom, 'UTF-8');
    if ($cat === 'Management & Leadership' || str_contains($n, 'management') || str_contains($n, 'leadership')) {
        return [
            ['titre' => 'Communication managériale et animation d\'équipe', 'contenus' => 'Communication assertive · Conduire une réunion efficace · Écoute active et rituels managériaux'],
            ['titre' => 'Gestion des conflits et situations difficiles', 'contenus' => 'Origines du conflit · Stratégies de résolution · Entretien de recadrage · Régulation émotionnelle'],
            ['titre' => 'Pilotage de la performance et tableau de bord', 'contenus' => 'Indicateurs clés (KPI) · Tableau de bord managérial · Conduite du changement · Plan d\'action'],
        ];
    }
    if ($cat === 'Comptabilité & Finance' || str_contains($n, 'comptab') || str_contains($n, 'fiscal') || str_contains($n, 'audit') || str_contains($n, 'finance')) {
        return [
            ['titre' => 'Analyse financière et tableaux de bord', 'contenus' => 'Ratios financiers · Solvabilité et liquidité · Analyse de la rentabilité · Reporting de gestion'],
            ['titre' => 'Obligations fiscales et déclarations', 'contenus' => 'TVA, IS, IRVM · Déclarations dans les délais réglementaires · Contrôle fiscal · Optimisation légale'],
            ['titre' => 'Trésorerie et gestion financière opérationnelle', 'contenus' => 'Budget de trésorerie · Cash-flow · Relations bancaires · Gestion des créances et dettes'],
        ];
    }
    if ($cat === 'GRH' || str_contains($n, 'personnel') || str_contains($n, 'ressources humaines') || str_contains($n, 'recrutement')) {
        return [
            ['titre' => 'Gestion de la paie et déclarations sociales', 'contenus' => 'Calcul de la paie · CNPS, CMU, DISA · Bulletins de paie · Archivage RH'],
            ['titre' => 'Formation et développement des compétences', 'contenus' => 'Plan de formation · GPEC · Évaluation des formations · Référentiel de compétences'],
            ['titre' => 'Évaluation des performances et entretiens', 'contenus' => 'Entretien annuel d\'évaluation · Grilles d\'évaluation · Feedback constructif · Fidélisation des talents'],
        ];
    }
    if ($cat === 'Gestion Commerciale & Marketing' || str_contains($n, 'marketing') || str_contains($n, 'commercial') || str_contains($n, 'vente')) {
        return [
            ['titre' => 'Marketing digital et réseaux sociaux', 'contenus' => 'SEO/SEA · Réseaux sociaux professionnels · Email marketing · Publicité en ligne · Analytics'],
            ['titre' => 'CRM et pilotage de l\'activité commerciale', 'contenus' => 'Outils CRM · Tableau de bord commercial · KPIs vente · Plan d\'action commercial'],
            ['titre' => 'Fidélisation client et gestion de la relation', 'contenus' => 'Stratégies de fidélisation · Satisfaction client · Gestion des réclamations · Programme de loyalty'],
        ];
    }
    if ($cat === 'Informatique & Tech') {
        return [
            ['titre' => 'Architecture, sécurité et bonnes pratiques', 'contenus' => 'Bonnes pratiques professionnelles · Sécurité applicative · Documentation technique'],
            ['titre' => 'Tests, déploiement et maintenance', 'contenus' => 'Tests et validation · Déploiement en production · Monitoring · Gestion des incidents'],
            ['titre' => 'Projets réels et portfolio professionnel', 'contenus' => 'Réalisation de bout en bout · Code review · Portfolio · Valorisation des compétences'],
        ];
    }
    if ($cat === 'Immobilier') {
        return [
            ['titre' => 'Droit immobilier et réglementation foncière', 'contenus' => 'Réglementation foncière ivoirienne · OHADA et immobilier · Titres fonciers · Procédures d\'enregistrement'],
            ['titre' => 'Gestion locative et relation avec les clients', 'contenus' => 'Gestion des baux · Relation bailleur/locataire · Charges récupérables · Contentieux locatif'],
            ['titre' => 'Financement et montage immobilier', 'contenus' => 'Financement bancaire · Montage de dossier · Rentabilité locative · Promotion immobilière'],
        ];
    }
    if ($cat === 'IA & Digitalisation' || str_contains($n, 'data') || str_contains($n, 'intelligence artificielle') || str_contains($n, 'ia ') || str_contains($n, 'digital')) {
        return [
            ['titre' => 'Outils IA et plateformes de données : panorama et prise en main', 'contenus' => 'Cartographie des outils IA et data du marché · Installation et configuration · Cas d\'usage prioritaires en entreprise africaine · Évaluation des risques et opportunités'],
            ['titre' => 'Traitement et analyse des données avec les outils numériques', 'contenus' => 'Import, nettoyage et structuration des données · Formules avancées et automatisation · Tableaux croisés dynamiques et visualisation · Interprétation des résultats pour la décision'],
            ['titre' => 'Intégration opérationnelle et déploiement en contexte professionnel', 'contenus' => 'Intégration dans les processus métier existants · Automatisation des tâches répétitives · Mesure de l\'impact et ajustement · Communication des résultats aux parties prenantes'],
        ];
    }
    if ($cat === 'Logistique & Supply Chain') {
        return [
            ['titre' => 'Transport, distribution et optimisation des coûts', 'contenus' => 'Flux de transport · Choix du prestataire · Coûts logistiques · Tableau de bord'],
            ['titre' => 'Douane, commerce international et incoterms', 'contenus' => 'Procédures douanières · Incoterms 2020 · Documents d\'exportation · Crédits documentaires'],
            ['titre' => 'Digitalisation de la chaîne logistique', 'contenus' => 'WMS, TMS, ERP · Traçabilité et code-barres · Tableaux de bord logistiques'],
        ];
    }
    return [
        ['titre' => 'Outils avancés et applications professionnelles', 'contenus' => 'Outils et logiciels du domaine · Organisation et traçabilité · Bonnes pratiques sectorielles'],
        ['titre' => 'Cas complexes et résolution de problèmes', 'contenus' => 'Analyse de situations réelles · Diagnostic et recommandations · Mise en pratique intensive'],
        ['titre' => 'Communication et restitution professionnelle', 'contenus' => 'Rédaction de rapports · Présentation des résultats · Communication avec les parties prenantes'],
    ];
}

function tdr_modules(string $nom, string $cat, int $heures, string $desc = '', string $niveau = 'debutant'): array
{
    $n_low = mb_strtolower($nom, 'UTF-8');

    /* ─────────────────────────────────────────────────────────────────────
       TABLE DES HEURES NATURELLES PAR TYPE DE FORMATION ET PAR NIVEAU
       Format : [regex, deb_h, inter_h, exp_h]
       Heures déterminées par la nature du contenu — pas de formule universelle.
       Certaines formations sont ascendantes (IT/outils), d'autres descendantes
       (droit/réglementation), d'autres mixtes (audit, gestion de projet).
    ───────────────────────────────────────────────────────────────────── */
    $_nh = [
        // Comptabilité / SYSCOHADA — fondations lourdes, expert plus ciblé
        ['/syscohada|syst[eè]me\s+comptable\s+ohada|comptabilit[eé]\s+(selon|ohada|syscohada)/ui', 40, 32, 28],
        // Fiscalité DGI — réglementation, expert maîtrise plus vite
        ['/d[eé]claration\s+fiscale|tva.*is\b|fiscalit[eé]\s+(des\s+)?pme|dgi.*c[oô]te|imp[oô]t\s+soci[eé]t[eé]|irvm/ui', 32, 28, 24],
        // Audit Interne — expert a plus de cas complexes
        ['/audit\s+interne|normes\s+iia|ippf|audit.*contr[oô]le\s+interne/ui', 40, 40, 45],
        // Analyse Financière — expert : modèles complexes, plus de cas
        ['/analyse\s+financi[eè]re|diagnostic\s+financier|[eé]tats\s+financiers.*syscohada/ui', 30, 28, 35],
        // Power BI / BI — plus de features à maîtriser au niveau supérieur
        ['/power\s*bi|business\s+intelligence|tableau.{0,10}bord|reporting|data.*visualis/ui', 20, 25, 30],
        // Droit du travail — réglementation, expert connaît les bases
        ['/droit\s+du\s+travail|code\s+du\s+travail.*ivoir|contrat\s+de\s+travail|licenciement|prud\'?hom/ui', 25, 20, 18],
        // Paie / CNPS — calculs techniques, expert maîtrise les automatismes
        ['/paie\s+ivoi|bulletin\s+de\s+(paie|salaire)|cnps.*irvm|calcul\s+salaire|gestion\s+(de\s+la\s+)?paie/ui', 28, 24, 20],
        // Recrutement / Entretiens — expert : assessment centers, tests psycho
        ['/recrutement|conduite\s+d.{1,5}entretien|entretien\s+structur[eé]|talent\s+acquisition|s[eé]lection\s+de\s+candidat/ui', 20, 18, 22],
        // Gestion de Projet / PMP — expert : PMBOK complet, cas complexes
        ['/gestion\s+de\s+projet|pmp\b|pmbok|prince\s*2|m[eé]thode\s+agile|agile.*scrum|scrum.*agile/ui', 35, 35, 40],
        // Conduite du changement — expert : programmes de transformation lourds
        ['/conduite\s+du\s+changement|accompagner\s+les?\s+transformation|change\s+management/ui', 20, 20, 25],
        // Marchés Publics — réglementation, expert procédures avancées
        ['/march[eé]s?\s+publics|anrmp|appel\s+d.offres|passation\s+de\s+march[eé]/ui', 28, 24, 20],
        // Droit OHADA — réglementation complexe, même niveau expert
        ['/droit\s+ohada|acte\s+uniforme|sarl.*sa.*gie|contrats?\s+commerciaux.*ohada|contentieux.*ohada|ccja|s[uû]ret[eé]s\s+ohada/ui', 30, 25, 25],
        // Transit / Import-Export — procédures, expert maîtrise les flux
        ['/transit\s+douanier|sydam|dgd\s+c[oô]te|import[- ]export|guichet\s+unique|incoterms|d[eé]douanement/ui', 28, 24, 20],
        // BCEAO / Conformité bancaire — expert : LBC-FT avancé, contrôles
        ['/r[eé]glementation\s+bceao|conformit[eé]\s+bancaire|lbc.ft|kyc.*aml|banque.*r[eé]glementation|compliance\s+bancaire/ui', 28, 25, 30],
        // ONG / Cadre Logique — expert : multi-bailleurs, MEAL complexe
        ['/cadre\s+logique|afd.*usaid|ue.*usaid|proj.{1,10}d[eé]veloppement|ong.*projet|rapportage.*bailleur|suivi.?[eé]valuation|kobo|odk\b|meal\b/ui', 28, 25, 30],
        // Business Plan / Entrepreneuriat — expert : pitch investisseur, scale-up
        ['/business\s+plan|cr[eé]ation\s+d.entreprise|cepici|entrepreneuriat|lancer\s+son|monter\s+son\s+projet/ui', 25, 22, 20],
        // Microfinance / SFD — réglementation, expert processus avancés
        ['/microfinance|syst[eè]mes?\s+financiers?\s+d[eé]centralis[eé]s?|sfd\b|imf\b.*cr[eé]dit|warrantage|cr[eé]dit.{1,10}stockage/ui', 28, 24, 22],
        // Agrobusiness / Cacao — terrain + certification
        ['/cacao|agrob[uo]siness|agro.{0,5}aliment|fili[eè]re.*certif|tracabilit[eé]|eudr|rainforest|fairtrade/ui', 24, 20, 20],
        // Odoo ERP — plus de modules à configurer au niveau expert
        ['/odoo|erp.*gestion|gestion.{1,10}int[eé]gr[eé]e/ui', 24, 28, 35],
        // Cybersécurité — pentest, threat hunting : plus lourd en expert
        ['/cybers[eé]curit[eé]|s[eé]curit[eé]\s+inform|administration\s+r[eé]seau|hacking|pentest|pfsense|firewall.*pme/ui', 25, 30, 40],
        // WordPress / SEO — technique avancée en expert
        ['/wordpress|woocommerce|seo\b|r[eé]f[eé]rencement\s+(naturel|web)|site\s+(web|professionnel).*cr[eé]er/ui', 20, 18, 25],
        // Mobile Money / API — intégrations complexes en expert
        ['/mobile\s+money|paiements?\s+mobiles?|orange\s+money\s+api|mtn\s+momo|wave\s+(business|api)|moneroo/ui', 20, 22, 30],
        // IA générative / ChatGPT — API, fine-tuning en expert
        ['/chatgpt|gpt[-\s]?[34o]|ia\s+g[eé]n[eé]rative|intelligence\s+artificielle.*pratique|prompt\s+engineering|copilot|gemini|mistral|outils?\s+ia/ui', 18, 20, 28],
        // Data Analyst RH — analyse avancée, modèles prédictifs en expert
        ['/data analyst\s*(rh|grh|ressources humaines|hr\b)?/ui', 25, 28, 35],
        // Excel / outils bureautiques — plus de fonctionnalités au niveau expert
        ['/excel|tableur|power\s*query/ui', 18, 22, 28],
        // Logistique / Supply Chain
        ['/logistique|supply\s+chain|approvisionnement|gestion\s+(des\s+)?stocks?/ui', 22, 22, 25],
    ];
    $_nh_matched = false;
    foreach ($_nh as [$_pat, $_d, $_i, $_e]) {
        if (preg_match($_pat, $nom)) {
            $heures = ['debutant' => $_d, 'intermediaire' => $_i, 'expert' => $_e][$niveau] ?? $_d;
            $_nh_matched = true;
            break;
        }
    }

    /* ── Fallback catégorie : si aucun pattern spécifique ne correspond,
       on utilise des heures naturelles par domaine × niveau pour casser
       la dépendance circulaire sur les valeurs par défaut de la DB.     ── */
    if (!$_nh_matched) {
        $_cat_nh = [
            'Comptabilité & Finance'          => [30, 25, 22],
            'GRH'                             => [25, 22, 20],
            'Management & Leadership'         => [20, 22, 25],
            'Gestion Commerciale & Marketing' => [20, 20, 22],
            'Informatique & Tech'             => [20, 25, 30],
            'IA & Digitalisation'             => [18, 22, 28],
            'Logistique & Supply Chain'       => [22, 22, 25],
            'Droit & Juridique'               => [25, 22, 20],
            'QHSE'                            => [20, 20, 22],
            'Immobilier'                      => [25, 22, 20],
            'Entrepreneuriat'                 => [20, 20, 22],
            'Banque & Assurance'              => [25, 22, 20],
            'Communication Professionnelle'   => [18, 18, 20],
            'Développement Personnel'         => [15, 15, 18],
            'Éducation & Formation'           => [20, 20, 22],
            'Direction & Administration'      => [22, 22, 25],
            'BTP & Construction'              => [22, 22, 25],
            'Santé & Pharmacie'               => [20, 22, 25],
            'Agriculture'                     => [20, 20, 22],
            'Mines, Énergie & Pétrole'        => [22, 22, 25],
            'Tourisme & Hôtellerie'           => [20, 20, 22],
            'Infographie & Design'            => [18, 20, 22],
        ];
        if (isset($_cat_nh[$cat])) {
            [$_d, $_i, $_e] = $_cat_nh[$cat];
        } else {
            [$_d, $_i, $_e] = [20, 22, 25];
        }
        $heures = ['debutant' => $_d, 'intermediaire' => $_i, 'expert' => $_e][$niveau] ?? $_d;
    }

    /* ─────────────────────────────────────────────────────────────────────
       HELPER : répartit $heures sur $n modules (dernier toujours 2h = eval)
    ───────────────────────────────────────────────────────────────────── */
    $split = function(int $h, int $n) use (&$split): array {
        $eval = 2;
        $content = max($n - 1, 1);
        $base  = max(2, (int)floor(max($h - $eval, $content * 2) / $content));
        $extra = max($h - $eval, $content * 2) - $base * $content;
        $sizes = [];
        for ($i = 0; $i < $content; $i++) {
            $sizes[] = $base + ($i === 0 && $extra > 0 ? $extra : 0);
        }
        $sizes[] = $eval;
        return $sizes;
    };

    /* ══ SYSCOHADA / Comptabilité OHADA ══ */
    if (preg_match('/syscohada|syst[eè]me\s+comptable\s+ohada|comptabilit[eé]\s+(selon|ohada|syscohada)/ui', $nom)) {
        $s = $split(max(20, $heures), 8);
        return [
            ['titre'=>'Le référentiel SYSCOHADA révisé : principes fondamentaux et champ d\'application','contenus'=>'Historique et objectifs du SYSCOHADA révisé (2017) · Champ d\'application : entreprises concernées, seuils et dispenses · Principes comptables fondamentaux (continuité, prudence, coût historique) · Plan de comptes OHADA : structure et logique des 9 classes · Différences avec le SYSCOA initial et impacts pratiques','duree'=>$s[0]],
            ['titre'=>'Comptabilisation des opérations courantes','contenus'=>'Enregistrement des achats, ventes, charges et produits · Opérations de trésorerie : encaissements, décaissements, rapprochements bancaires · Traitement des avances, acomptes et retenues de garantie · Opérations en devises et règles de conversion · Exercices pratiques sur pièces comptables réelles d\'entreprises ivoiriennes','duree'=>$s[1]],
            ['titre'=>'Immobilisations, amortissements et provisions','contenus'=>'Classification des immobilisations corporelles et incorporelles · Méthodes d\'amortissement (linéaire, dégressif, composants) · Dépréciations d\'actifs : tests et écriture de constatation · Provisions pour risques et charges : conditions, comptabilisation, reprise · Exercices sur le calcul des dotations et tableaux d\'amortissement','duree'=>$s[2]],
            ['titre'=>'Régularisations de fin d\'exercice','contenus'=>'Charges à payer et produits à recevoir · Charges et produits constatés d\'avance · Ajustement des stocks : méthode des inventaires permanents et intermittents · Traitement des écarts de conversion et réévaluations · Construction du tableau des flux de trésorerie','duree'=>$s[3]],
            ['titre'=>'États financiers annuels conformes SYSCOHADA','contenus'=>'Structure et contenu du Bilan (actif/passif, retraitements) · Compte de résultat : SIG, formation du résultat net · Tableau des flux de trésorerie (méthode directe et indirecte) · Notes annexes obligatoires : contenu et présentation · Règles de consolidation pour les groupes : notions de base','duree'=>$s[4]],
            ['titre'=>'Liasse fiscale et articulation comptabilité-fiscalité','contenus'=>'Retraitements fiscaux extra-comptables (réintégrations, déductions) · TVA déductible et collectée : calcul et déclaration · Impôt sur les Sociétés (IS) : base imposable, acomptes provisionnels · Passage du résultat comptable au résultat fiscal · Remplissage de la liasse fiscale sur supports DGI réels','duree'=>$s[5]],
            ['titre'=>'Logiciels comptables et contrôle de la qualité des comptes','contenus'=>'Saisie et lettrage sous Sage 100 Comptabilité / CIEL Compta · Édition des journaux, grand-livre et balance de vérification · Balance âgée et analyse des soldes anormaux · Clôture comptable : processus, check-list et délais légaux · Archivage électronique et conservation des pièces justificatives','duree'=>$s[6]],
            ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : traitement d\'un dossier comptable complet (saisie → états financiers) sur un jeu de données inédit · Correction individualisée et feedback détaillé du formateur · Remise du certificat IBIG EDUFORM "SYSCOHADA Révisé" et construction du plan de développement post-formation','duree'=>$s[7]],
        ];
    }

    /* ══ Fiscalité DGI / TVA / IS / Déclarations fiscales ══ */
    if (preg_match('/d[eé]claration\s+fiscale|tva.*is\b|fiscalit[eé]\s+(des\s+)?pme|dgi.*c[oô]te|imp[oô]t\s+soci[eé]t[eé]|irvm/ui', $nom)) {
        $s = $split(max(16, $heures), 7);
        return [
            ['titre'=>'Panorama du système fiscal ivoirien et obligations des entreprises','contenus'=>'Architecture de la DGI Côte d\'Ivoire : direction, services et interlocuteurs · Régimes d\'imposition : réel normal, réel simplifié, Taxe d\'Impôt Synthétique (TIS) · Critères de rattachement à un régime et seuils 2024-2025 · Calendrier fiscal annuel : toutes les échéances à ne pas manquer · Sanctions et pénalités : majorations, amendes, intérêts de retard','duree'=>$s[0]],
            ['titre'=>'TVA : mécanisme, calcul et déclaration mensuelle','contenus'=>'Base imposable et taux (18 %, 9 %, exonérations) · TVA collectée : fait générateur, facturation, mention obligatoire · TVA déductible : conditions, prorata, régularisations annuelles · Déclaration mensuelle CA (formulaire DGI-net) · Crédit de TVA : restitution et conditions · Exercices pratiques sur opérations réelles d\'achat et de vente','duree'=>$s[1]],
            ['titre'=>'Impôt sur les Sociétés (IS) et Impôt Minimum Forfaitaire (IMF)','contenus'=>'Base imposable IS : passage du résultat comptable au résultat fiscal · Charges déductibles et réintégrations (TVS, amendes, excédents de rémunération) · Calcul de l\'IS et de l\'IMF · Acomptes provisionnels (avril, juin, septembre) · Déclaration annuelle des résultats (formulaire) · Exercices sur la détermination du résultat fiscal','duree'=>$s[2]],
            ['titre'=>'IRVM, retenues à la source et autres impôts','contenus'=>'IRVM sur les dividendes, intérêts et revenus de capitaux · Retenue à la source sur salaires (IRPP/TS) : calcul et reversement · Taxe patronale d\'apprentissage (TPA) et contribution FDFP · Patente : base de calcul, valeur locative et tarif · Taxe foncière et logement locatif · Impôt sur le BNC (professions libérales)','duree'=>$s[3]],
            ['titre'=>'Télédéclaration sur DGI-net et gestion des contrôles','contenus'=>'Inscription et navigation sur la plateforme DGI-net · Saisie, validation et téléchargement des déclarations · Paiement en ligne (Orange Money, virement, carte) · Droit de communication et contrôle fiscal : procédure et droits du contribuable · Contentieux fiscal : réclamation, recours hiérarchique, tribunal · Préparation d\'un dossier de vérification fiscale','duree'=>$s[4]],
            ['titre'=>'Optimisation fiscale légale et cas pratiques de synthèse','contenus'=>'Avantages fiscaux applicables aux PME ivoiriennes · Zones franches industrielles (ZFI) et Code des Investissements · Restructuration de la rémunération des dirigeants · Optimisation du moment de constatation des produits et charges · Traitement fiscal des véhicules, frais de déplacement et cadeaux · Cas pratiques intégraux : de la balance à la liasse fiscale complète','duree'=>$s[5]],
            ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : traitement d\'un dossier fiscal complet avec remplissage des formulaires DGI sur cas inédit · Correction individualisée et feedback détaillé · Remise du certificat IBIG EDUFORM "Fiscalité Ivoirienne" et plan de mise en conformité de l\'organisation du bénéficiaire','duree'=>$s[6]],
        ];
    }

    /* ══ Audit Interne / IIA ══ */
    if (preg_match('/audit\s+interne|normes\s+iia|ippf|audit.*contrôle interne/ui', $nom)) {
        $s = $split(max(20, $heures), 8);
        return [
            ['titre'=>'Cadre de référence de l\'audit interne : IPPF, code d\'éthique et positionnement','contenus'=>'Définition de l\'audit interne selon l\'IIA · Cadre International des Pratiques Professionnelles (IPPF) : normes d\'attribut et de fonctionnement · Code d\'éthique de l\'auditeur interne · Positionnement de la fonction d\'audit : rattachement, indépendance et objectivité · Cartographie des activités auditables : processus, risques et enjeux','duree'=>$s[0]],
            ['titre'=>'Évaluation des risques et planification du plan d\'audit annuel','contenus'=>'Méthode de cartographie des risques (top-down, bottom-up) · Critères de priorisation des missions : probabilité, impact, fréquence · Élaboration du Plan d\'Audit Annuel (PAA) · Définition du budget-temps et allocation des ressources · Comité d\'audit : rôle, communication et gouvernance · Exercice : construction d\'une cartographie des risques sur cas réel','duree'=>$s[1]],
            ['titre'=>'Phase de planification d\'une mission d\'audit','contenus'=>'Lettre de mission et ordre de mission · Prise de connaissance du domaine audité (entretiens, documentation) · Analyse des risques spécifiques et identification des points de contrôle · Programme de travail détaillé : objectifs, tests, responsables · Réunion d\'ouverture : déroulement et posture de l\'auditeur · Référentiels d\'évaluation : COSO, COBIT, normes sectorielles','duree'=>$s[2]],
            ['titre'=>'Techniques de collecte et d\'analyse des preuves','contenus'=>'Techniques d\'entretien structuré avec les audités · Observation, inspection physique et comptage · Confirmation externe (circularisation) · Sondages statistiques et échantillonnage · Tests de cheminement (walkthrough) et tests de détail · Documentation des travaux : papiers de travail, indexation et archivage · Logiciels d\'audit (IDEA, ACL, Excel avancé) : extractions et analyses','duree'=>$s[3]],
            ['titre'=>'Conduite des travaux de terrain : audit des processus clés','contenus'=>'Audit des achats-fournisseurs : séparation des tâches, approvisionnements non conformes · Audit de la paie : fantômes, surcharges, conformité sociale CNPS/OHADA · Audit de la trésorerie : séparation des fonctions, rapprochements, caisses · Audit des stocks : inventaires, valorisation, écarts · Audit du cycle clients-facturation : risques de fraude et de doublons · Feuilles de travail et grilles d\'analyse : modèles pratiques commentés','duree'=>$s[4]],
            ['titre'=>'Rédaction du rapport d\'audit et suivi des recommandations','contenus'=>'Structure type d\'un rapport d\'audit (synthèse dirigeant, constats, recommandations) · Règles de rédaction : clarté, objectivité, hiérarchisation par criticité · Côté « constat » : critère, condition, cause, conséquence · Niveau de criticité et priorisation des recommandations · Réunion de clôture : validation des constats et plans d\'action · Suivi périodique des recommandations : grille, indicateurs et reporting au comité','duree'=>$s[5]],
            ['titre'=>'Audit des systèmes d\'information et fraudes internes','contenus'=>'Spécificités de l\'audit informatique : contrôles généraux IT et contrôles applicatifs · Revue des droits d\'accès, journaux et paramètres de sécurité · Gestion des fraudes : typologies, signaux d\'alerte et investigation · Responsabilité de l\'auditeur face à la fraude (normes IIA 2120) · Plan de prévention des fraudes : contrôles clés à recommander · Exercice : audit d\'accès à un ERP (Sage, Odoo, SAP)','duree'=>$s[6]],
            ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : planification et rapport d\'une mission d\'audit complète sur cas inédit · Correction individualisée par le formateur auditeur certifié · Remise du certificat IBIG EDUFORM "Audit Interne IIA" et plan de développement post-formation','duree'=>$s[7]],
        ];
    }

    /* ══ Analyse Financière ══ */
    if (preg_match('/analyse\s+financi[eè]re|diagnostic\s+financier|[eé]tats\s+financiers.*syscohada/ui', $nom)) {
        $s = $split(max(16, $heures), 7);
        return [
            ['titre'=>'Lecture et retraitement du bilan SYSCOHADA','contenus'=>'Structure du bilan SYSCOHADA : actifs immobilisés, circulants, trésorerie · Retraitements analytiques : crédit-bail, effets escomptés, écarts de conversion · Fonds de Roulement (FDR), Besoin en Fonds de Roulement (BFR) et Trésorerie Nette · Lecture des variations d\'une année sur l\'autre · Exercice sur une liasse fiscale ivoirienne réelle','duree'=>$s[0]],
            ['titre'=>'Analyse du compte de résultat et des soldes intermédiaires de gestion','contenus'=>'Structure du compte de résultat SYSCOHADA · Calcul des SIG : VA, EBE, EBIT, résultat financier, résultat net · Ratios de profitabilité : marge brute, marge opérationnelle, rentabilité nette · Analyse des charges fixes vs variables et du point mort · Exercice : calcul des SIG sur un compte de résultat de PME ivoirienne','duree'=>$s[1]],
            ['titre'=>'Tableau de flux de trésorerie et analyse de la liquidité','contenus'=>'Méthode directe et indirecte de construction des flux · Flux d\'exploitation, d\'investissement et de financement · Capacité d\'Autofinancement (CAF) et Free Cash Flow · Ratios de liquidité : générale, réduite, immédiate · Analyse de la solvabilité à court terme · Exercice pratique : construction du TFT à partir d\'une liasse','duree'=>$s[2]],
            ['titre'=>'Ratios de performance, d\'endettement et de rentabilité','contenus'=>'Ratios de structure financière : autonomie, endettement, capacité de remboursement · Ratios de rotation : stocks, créances clients, dettes fournisseurs · Rentabilité des capitaux propres (ROE) et des actifs (ROA) · Effet de levier financier et risque · Comparaison sectorielle : benchmarks sectoriels OHADA · Construction d\'une scorecard financière','duree'=>$s[3]],
            ['titre'=>'Scoring crédit et notation financière','contenus'=>'Modèles de scoring (Altman Z-Score, méthodes bancaires ivoiriennes) · Analyse qualitative complémentaire : gouvernance, marché, management · Matrice de cotation et grille de décision · Rapport de diagnostic financier : structure et rédaction professionnelle · Présentation des conclusions à un décideur ou à un comité de crédit','duree'=>$s[4]],
            ['titre'=>'Mise en pratique : cas intégraux sur liasses fiscales réelles','contenus'=>'Analyse de 2 à 3 dossiers complets d\'entreprises ivoiriennes de secteurs différents · Production d\'un rapport de diagnostic financier complet pour chaque cas · Comparaison des profils financiers et formulation de recommandations stratégiques · Travail individuel avec correction commentée et plan d\'amélioration','duree'=>$s[5]],
            ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : analyse complète d\'une liasse fiscale inédite avec rapport de diagnostic · Correction individualisée et feedback détaillé · Remise du certificat IBIG EDUFORM "Analyse Financière SYSCOHADA" et plan de développement post-formation','duree'=>$s[6]],
        ];
    }

    /* ══ Power BI / Business Intelligence / Tableaux de bord ══ */
    if (preg_match('/power\s*bi|business\s+intelligence|tableau.{0,10}bord|reporting|data.*visualis/ui', $nom)
        && !preg_match('/data analyst/ui', $nom)) {
        $s = $split(max(16, $heures), 7);
        return [
            ['titre'=>'Prise en main de Power BI Desktop et connexion aux sources de données','contenus'=>'Interface Power BI Desktop : navigation, ruban et volets · Connexion aux sources : Excel, CSV, SQL Server, SharePoint, API Web · Import vs DirectQuery vs Live Connection : choisir le bon mode · Actualisation des données et gestion des credentials · Exercice : connexion à un fichier Excel de données ivoiriennes','duree'=>$s[0]],
            ['titre'=>'Nettoyage et transformation des données avec Power Query','contenus'=>'Éditeur Power Query : fonctionnalités et flux de transformation · Suppression des doublons, valeurs nulles et colonnes inutiles · Fractionnement, fusion et pivotement des colonnes · Ajout de colonnes calculées et colonnes conditionnelles · Fusion de requêtes (jointures) et ajout de tables · Étapes appliquées : traçabilité et maintenance · Exercice : nettoyage d\'un export SIRH ou comptable','duree'=>$s[1]],
            ['titre'=>'Modélisation des données : relations, schéma étoile et hiérarchies','contenus'=>'Vue Modèle : création et gestion des relations · Schéma en étoile vs flocon de neige · Tables de faits et tables de dimensions · Cardinalité et direction du filtre croisé · Tables de dates : création automatique et personnalisée · Hiérarchies personnalisées pour navigation intuitive · Exercice : construction d\'un modèle de données financières','duree'=>$s[2]],
            ['titre'=>'DAX : mesures, colonnes calculées et KPIs avancés','contenus'=>'Différence mesure / colonne calculée · Fonctions de base : SUM, COUNT, AVERAGE, DIVIDE · Fonctions temporelles : SAMEPERIODLASTYEAR, TOTALYTD, CALCULATE · Fonctions de filtrage : ALL, FILTER, RELATED · Mesures de type KPI : taux de croissance, cumul, variation · Variables DAX (VAR) et débogage · Exercice : création d\'un tableau de bord RH ou commercial complet','duree'=>$s[3]],
            ['titre'=>'Visualisations avancées, mise en forme et interactivité','contenus'=>'Visuels natifs : histogramme, courbe, carte, tableau, matrice, jauge · Mise en forme conditionnelle et arrière-plans · Slicers, filtres et drill-through : interactivité avancée · Visuels personnalisés depuis AppSource · Bookmarks et boutons de navigation · Thèmes et charte graphique corporate · Exercice : dashboard de suivi de performance commerciale','duree'=>$s[4]],
            ['titre'=>'Publication, partage sur Power BI Service et gouvernance','contenus'=>'Publication sur Power BI Service (workspace, apps) · Partage de rapports : rôles, liens, intégration SharePoint · Actualisation planifiée et gateway de données · Sécurité au niveau des lignes (Row-Level Security) · Power BI Mobile : optimisation pour smartphone · Bonnes pratiques de gouvernance et documentation des modèles','duree'=>$s[5]],
            ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : conception d\'un tableau de bord complet (modèle + DAX + visuels + publication) sur jeu de données inédit · Correction individualisée et feedback détaillé du formateur · Remise du certificat IBIG EDUFORM "Power BI / BI" et plan de développement post-formation','duree'=>$s[6]],
        ];
    }

    /* ══ Droit du travail ivoirien ══ */
    if (preg_match('/droit\s+du\s+travail|code\s+du\s+travail.*ivoir|contrat\s+de\s+travail|licenciement|prud\'?hom/ui', $nom)) {
        $s = $split(max(16, $heures), 7);
        return [
            ['titre'=>'Sources et architecture du droit du travail ivoirien','contenus'=>'Code du Travail ivoirien (loi n°2015-532) : structure et champ d\'application · Convention Collective Interprofessionnelle (CCI) : portée et application · Conventions collectives sectorielles (BTP, banque, transport, commerce) · Inspection du Travail : rôle, pouvoirs et procédure d\'inspection · Droit OHADA et droit social : articulation et primauté','duree'=>$s[0]],
            ['titre'=>'Les contrats de travail : types, rédaction et clauses','contenus'=>'CDI : définition, essai, mentions obligatoires, formalisation · CDD : conditions de recours, durée maximale, renouvellement et risques de requalification · Contrat saisonnier, d\'apprentissage, de sous-traitance · Clauses spéciales : non-concurrence, confidentialité, mobilité · Travailleur étranger en Côte d\'Ivoire : permis de travail et procédures · Exercice pratique : rédaction et analyse de contrats types','duree'=>$s[1]],
            ['titre'=>'Temps de travail, congés, absences et rémunération','contenus'=>'Durée légale du travail : heures normales, heures supplémentaires, taux de majoration · Organisation du temps : travail de nuit, week-end, aménagements · SMIG 2024 et grilles de classification professionnelle · Congés payés : calcul, période, indemnité de congés payés · Jours fériés légaux en Côte d\'Ivoire · Absences : maladie, maternité, accident de travail, autorisations spéciales','duree'=>$s[2]],
            ['titre'=>'Rupture du contrat de travail : procédures légales','contenus'=>'Démission : conditions, préavis, conséquences juridiques · Licenciement individuel : motifs réels et sérieux, procédure (convocation, entretien, notification) · Licenciement économique : conditions, consultation des délégués, plan social · Départ à la retraite et mise à la retraite · Solde de tout compte : calcul (préavis, congés, indemnités) · Nullité du licenciement et réintégration · Exercice : traitement d\'une procédure de licenciement complète','duree'=>$s[3]],
            ['titre'=>'Sanctions disciplinaires, contentieux et rôle des délégués du personnel','contenus'=>'Pouvoir disciplinaire de l\'employeur : avertissement, mise à pied, licenciement · Procédure disciplinaire conforme au Code du Travail · Délégués du personnel : élections, attributions, protection · Syndicats et liberté syndicale · Tribunal du Travail : compétence, procédure de conciliation et jugement · Prescription et calcul des dommages-intérêts · Exercice : simulation d\'un litige prud\'homal','duree'=>$s[4]],
            ['titre'=>'Conformité sociale : CNPS, CMU et obligations déclaratives','contenus'=>'Obligations CNPS : immatriculation, cotisations, déclarations trimestrielles · Couverture Maladie Universelle (CMU) : affiliation et gestion · Accidents du travail et maladies professionnelles : déclaration et prise en charge CNPS · FDFP : obligation de formation et déclaration annuelle · Contrôle CNPS et Inspection du Travail : droits et obligations · Tableau de bord conformité sociale : check-list pratique','duree'=>$s[5]],
            ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : traitement d\'un cas RH complexe (recrutement → licenciement → contentieux) avec production de documents · Correction individualisée et feedback détaillé · Remise du certificat IBIG EDUFORM "Droit du Travail Ivoirien" et plan de mise en conformité','duree'=>$s[6]],
        ];
    }

    /* ══ Paie ivoirienne / CNPS / Bulletins de salaire ══ */
    if (preg_match('/paie\s+ivoi|bulletin\s+de\s+(paie|salaire)|cnps.*irvm|calcul\s+salaire|gestion\s+(de\s+la\s+)?paie/ui', $nom)) {
        $s = $split(max(16, $heures), 7);
        return [
            ['titre'=>'Cadre légal de la paie en Côte d\'Ivoire','contenus'=>'Code du Travail ivoirien et ses dispositions salariales · SMIG 2024 et conventions collectives sectorielles · Classification professionnelle et grilles de salaire · Registre du personnel et dossier salarié obligatoires · Sanctions en cas de non-conformité sociale · Exercice : vérification de la conformité d\'une structure de rémunération','duree'=>$s[0]],
            ['titre'=>'Structure du bulletin de paie et éléments constitutifs du salaire','contenus'=>'Mentions obligatoires du bulletin de paie · Salaire de base, heures supplémentaires et primes · Éléments bruts : calcul du salaire brut imposable et non imposable · Avantages en nature : valorisation et traitement social/fiscal · Indemnités exonérées (transport, logement, repas) vs imposables · Exercice : décomposition et vérification d\'un bulletin de paie réel','duree'=>$s[1]],
            ['titre'=>'Cotisations CNPS : retraite, AT/MP et prestations familiales','contenus'=>'Architecture des cotisations CNPS : taux employeur et salarié 2024 · Cotisation retraite CNPS : assiette, taux et plafonds · Accidents du Travail et Maladies Professionnelles (AT/MP) · Prestations familiales : allocations, congé maternité · Déclaration Nominative des Salaires (DNS) : procédure et délais · Régularisation annuelle CNPS et rapprochement comptes · Exercice : calcul des cotisations sur plusieurs bulletins','duree'=>$s[2]],
            ['titre'=>'IRVM et retenues à la source sur salaires','contenus'=>'Impôt sur les Revenus des Valeurs Mobilières (IRVM) : champ, base et taux · Impôt sur le Traitement des Salaires (ITS/IRPP) : barème progressif 2024 · Calcul de la retenue mensuelle et abattements · Traitement fiscal des primes, gratifications et indemnités de départ · Déclaration mensuelle à la DGI (formulaire) · Exercice : calcul de l\'IRVM et de l\'ITS sur un ensemble de salariés','duree'=>$s[3]],
            ['titre'=>'Variables de paie, congés payés et solde de tout compte','contenus'=>'Gestion des absences : maladie, maternité, accident de travail · Congés payés : base de calcul, indemnité (maintien vs 1/10) et provision · Indemnités de fin de contrat : préavis, licenciement, départ à la retraite · Calcul du solde de tout compte complet · Charges patronales totales : coût global d\'un salarié · Exercice : établissement du solde de tout compte d\'un départ','duree'=>$s[4]],
            ['titre'=>'Logiciels de paie et déclarations périodiques','contenus'=>'Sage Paie 100 (ou Sage i7) : paramétrage, saisie et édition des bulletins · Paie sous Excel : modèles avancés et automatisation · Déclaration trimestrielle CNPS et déclaration annuelle (bilan social) · Télédéclaration sur DGI-net : ITS et charges salariales · Archivage des bulletins et délais légaux de conservation · Exercice : traitement d\'une paie complète de 10 salariés','duree'=>$s[5]],
            ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : établissement d\'une paie mensuelle complète (bulletins + déclarations) sur données inédites · Correction individualisée et feedback détaillé · Remise du certificat IBIG EDUFORM "Paie Ivoirienne" et plan de mise en conformité','duree'=>$s[6]],
        ];
    }

    /* ══ Recrutement / Conduite des entretiens ══ */
    if (preg_match('/recrutement|conduite\s+d.{1,5}entretien|entretien\s+structur[eé]|talent\s+acquisition|s[eé]lection\s+de\s+candidat/ui', $nom)) {
        $s = $split(max(14, $heures), 6);
        return [
            ['titre'=>'Analyse du besoin et construction de la fiche de poste','contenus'=>'Diagnostic du besoin : création de poste vs remplacement vs réorganisation · Analyse du poste : missions, activités, compétences requises · Rédaction de la fiche de poste (profil compétences, niveau RAQC) · Définition du profil cible et critères de sélection objectifs · Grille de pondération des critères · Exercice : rédaction d\'une fiche de poste sur un cas réel du bénéficiaire','duree'=>$s[0]],
            ['titre'=>'Sourcing, rédaction des annonces et diffusion multicanale','contenus'=>'Rédaction d\'une offre d\'emploi attractive et conforme à la loi (non-discrimination) · Canaux de diffusion en Côte d\'Ivoire : JobnetAfrica, LinkedIn, AfrikTalent, réseaux professionnels · Chasse de têtes et approche directe sur LinkedIn · Bases de candidatures internes et CVthèques · Cooptation : conception d\'un programme de recommandation · Mesure de l\'efficacité des sources de recrutement','duree'=>$s[1]],
            ['titre'=>'Présélection des candidatures et entretien téléphonique','contenus'=>'Grille de présélection CV : critères discriminants et critères différenciants · Prise en compte du biais de sélection : stéréotypes, halo, projection · Entretien téléphonique de qualification : grille et durée standard · Tests à distance : QCM de connaissances, exercice pratique en ligne · Réponses aux candidats non retenus : communication professionnelle · Exercice : simulation de présélection sur un lot de CV','duree'=>$s[2]],
            ['titre'=>'Conduite de l\'entretien structuré par compétences (méthode STAR)','contenus'=>'Types d\'entretiens : non structuré, semi-structuré, structuré par compétences · Méthode STAR : Situation, Tâche, Action, Résultat · Guide d\'entretien : construction des questions comportementales · Posture de l\'interviewer : écoute active, neutralité, relances · Pièges de l\'entretien : questions illicites et risques juridiques · Exercice pratique : simulation d\'entretien filmée avec débriefing immédiat','duree'=>$s[3]],
            ['titre'=>'Tests, évaluation psychométrique et décision de recrutement','contenus'=>'Tests techniques et exercices pratiques métier · Tests psychométriques : raisonnement, personnalité (DISC, MBTI), valeurs · Assessment Center : mise en situation collective, jeu de rôle · Grille de synthèse d\'évaluation et aide à la décision · Présentation au jury et décision finale objective · Offre d\'embauche, période d\'essai et formalités administratives · Onboarding : plan d\'intégration du nouveau collaborateur','duree'=>$s[4]],
            ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : conception d\'un processus de recrutement complet (fiche de poste → guide d\'entretien → grille de synthèse) sur un cas inédit · Correction individualisée et feedback détaillé · Remise du certificat IBIG EDUFORM "Recrutement & Entretiens" et plan de mise en œuvre','duree'=>$s[5]],
        ];
    }

    /* ══ Gestion de Projet / PMP / PMI / PRINCE2 ══ */
    if (preg_match('/gestion\s+de\s+projet|pmp\b|pmbok|prince\s*2|m[eé]thode\s+agile|agile.*scrum|scrum.*agile/ui', $nom)) {
        $s = $split(max(20, $heures), 8);
        return [
            ['titre'=>'Fondamentaux du management de projet et positionnement du chef de projet','contenus'=>'Définition du projet vs opérations : caractéristiques, contraintes, enjeux · Cycle de vie d\'un projet : phases, jalons et livrables · Rôle et compétences du Chef de Projet · Triangle des contraintes (coût, délai, qualité) et marge de manœuvre · Environnement organisationnel : structure fonctionnelle, matricielle, dédiée · Initialisation : note de cadrage, fiche projet et lettre de mission','duree'=>$s[0]],
            ['titre'=>'Initiation et définition du périmètre du projet','contenus'=>'Analyse des parties prenantes (register) et cartographie des influences · Expression des besoins et rédaction du cahier des charges · Structure de Décomposition du Travail (WBS / SDP) · Dictionnaire WBS et lots de travaux · Charte de projet (Project Charter) · Exercice : décomposition d\'un projet réel en WBS avec lots de travaux définis','duree'=>$s[1]],
            ['titre'=>'Planification des délais : Gantt, chemin critique et calendrier','contenus'=>'Séquençage des tâches et identification des dépendances · Estimation des durées : PERT, 3 points, analogie · Réseau PERT/CPM et calcul du chemin critique · Diagramme de Gantt : construction et mise à jour · Gestion des ressources sur le planning · Outils de planification : MS Project, GanttProject, Trello, Asana · Exercice : construction du planning complet d\'un projet du bénéficiaire','duree'=>$s[2]],
            ['titre'=>'Gestion des coûts et de la valeur acquise (Earned Value Management)','contenus'=>'Estimation des coûts du projet : méthodes ascendante et descendante · Budget global (Budget à l\'Achèvement – BAC) et courbe en S · Suivi du coût réel (AC) et de la valeur acquise (EV) · Indices de performance coûts (CPI) et délais (SPI) · Prévision du coût final (EAC) et analyse des écarts · Reporting financier projet : tableau de bord coût/délai · Exercice : analyse de la performance d\'un projet en cours','duree'=>$s[3]],
            ['titre'=>'Gestion des risques et de la qualité','contenus'=>'Identification des risques : brainstorming, AMDEC, registre des risques · Analyse qualitative et quantitative des risques (matrice probabilité/impact) · Stratégies de réponse : évitement, transfert, atténuation, acceptation · Plan de management de la qualité et critères d\'acceptation · Contrôle qualité : revues, inspections, tests d\'acceptation · Non-conformités et gestion des modifications · Exercice : construction d\'un registre des risques complet','duree'=>$s[4]],
            ['titre'=>'Pilotage, communication et clôture du projet','contenus'=>'Réunion de lancement (kick-off) et tableau de bord de pilotage · Reporting d\'avancement : rapport d\'état, réunion de suivi, escalade · Gestion des modifications (change management) : procédure et comité · Communication avec les parties prenantes : plan et fréquence · Procédure de clôture : réception des livrables, libération des ressources · Retour d\'expérience (REX) : bilan de projet et capitalisation des leçons apprises','duree'=>$s[5]],
            ['titre'=>'Méthodes agiles : Scrum, Kanban et hybridation','contenus'=>'Valeurs du Manifeste Agile et différences avec le mode prédictif · Scrum : rôles (Product Owner, Scrum Master, équipe), artefacts et cérémonies · Sprint planning, Daily Stand-Up, Sprint Review et Rétrospective · Backlog produit et user stories · Kanban : flux continu et gestion du WIP · Hybridation Agile/PMI dans les projets complexes · Exercice : simulation d\'un sprint complet sur un projet réel','duree'=>$s[6]],
            ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : planification complète d\'un projet réel (WBS + Gantt + budget + risques + plan de communication) · Simulation de 30 questions PMP/PRINCE2 commentées · Remise du certificat IBIG EDUFORM "Gestion de Projet" et plan de développement post-formation','duree'=>$s[7]],
        ];
    }

    /* ══ Conduite du changement ══ */
    if (preg_match('/conduite\s+du\s+changement|accompagner\s+les?\s+transformation|change\s+management/ui', $nom)) {
        $s = $split(max(16, $heures), 6);
        return [
            ['titre'=>'Comprendre le changement organisationnel : dynamiques, acteurs et résistances','contenus'=>'Nature et types de changements (incrémental, radical, culturel, technologique) · Impacts humains, organisationnels et opérationnels · Courbe du deuil et réactions prévisibles des collaborateurs · Cartographie des acteurs : promoteurs, sceptiques, résistants · Facteurs de succès et d\'échec des transformations en Afrique · Diagnostic initial : évaluation de la maturité au changement de l\'organisation','duree'=>$s[0]],
            ['titre'=>'Modèles et cadres de conduite du changement','contenus'=>'Modèle de Lewin (dégel, changement, regelation) · Modèle de Kotter (8 étapes vers le changement réussi) · Modèle ADKAR (Awareness, Desire, Knowledge, Ability, Reinforcement) · Modèle McKinsey 7S · Choix du modèle selon le contexte · Construction de la vision du changement et du cas de raison d\'être (burning platform) · Exercice : application d\'ADKAR à un projet de transformation réel','duree'=>$s[1]],
            ['titre'=>'Stratégie et plan d\'accompagnement du changement','contenus'=>'Feuille de route de la transformation : phases, jalons et critères de succès · Plan de conduite du changement (PCC) : composantes et livrables · Constitution de l\'équipe de changement (sponsors, agents de changement, équipe RH) · Budget et ressources de la conduite du changement · Gouvernance du changement : comité de pilotage et réunions de suivi · Exercice : construction d\'un PCC pour un projet de transformation spécifique','duree'=>$s[2]],
            ['titre'=>'Communication et gestion des résistances','contenus'=>'Plan de communication du changement : cibles, messages, canaux, fréquence · Techniques de communication descendante, ascendante et horizontale · Gestion des rumeurs et de l\'information informelle · Techniques de gestion des résistances : écoute, co-construction, négociation · Forum ouvert et ateliers participatifs · Communication de crise lors des phases difficiles · Exercice : rédaction d\'un plan de communication pour un changement majeur','duree'=>$s[3]],
            ['titre'=>'Formation, accompagnement des équipes et ancrage','contenus'=>'Analyse des impacts sur les compétences : matrice de gestion des impacts · Plan de formation et d\'accompagnement des collaborateurs · Coaching des managers de proximité : rôle clé dans l\'adoption · Mesure de l\'adoption : indicateurs comportementaux et organisationnels · Ancrage des nouvelles pratiques : rituels, reconnaissance et célébration des succès · Plan de continuation et de consolidation post-déploiement','duree'=>$s[4]],
            ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : conception d\'un plan de conduite du changement complet pour un projet de transformation réel ou simulé · Correction individualisée et retour du formateur · Remise du certificat IBIG EDUFORM "Conduite du Changement" et plan de mise en œuvre dans l\'organisation du bénéficiaire','duree'=>$s[5]],
        ];
    }

    /* ══ Marchés Publics / ANRMP ══ */
    if (preg_match('/march[eé]s?\s+publics|anrmp|appel\s+d.offres|passation\s+de\s+march[eé]/ui', $nom)) {
        $s = $split(max(16, $heures), 7);
        return [
            ['titre'=>'Cadre réglementaire des marchés publics en Côte d\'Ivoire','contenus'=>'Décret portant Code des Marchés Publics : champ d\'application et principes fondamentaux · Acteurs du système : ANRMP, DGMP, autorités contractantes, soumissionnaires · Seuils de passation et règles de compétence · Réglementation CEDEAO/UEMOA et directives communautaires · Infractions et sanctions prévues par la réglementation · Exercice : identification du mode de passation adapté selon le montant et l\'objet','duree'=>$s[0]],
            ['titre'=>'Les modes de passation : appel d\'offres, concours et gré à gré','contenus'=>'Appel d\'offres ouvert : procédure complète et conditions d\'utilisation · Appel d\'offres restreint : conditions de recours et pré-qualification · Demande de cotation : seuils et procédure simplifiée · Marché de gré à gré : cas autorisés et risques de contentieux · Concours : procédure et jury d\'évaluation · Procédure d\'urgence : conditions strictes et documentation requise · Exercice : choix justifié du mode de passation sur 5 cas pratiques','duree'=>$s[1]],
            ['titre'=>'Préparation et rédaction du Dossier d\'Appel d\'Offres (DAO)','contenus'=>'Structure type d\'un DAO conforme ANRMP · Spécifications techniques : rédiger sans discriminer ni favoriser un fournisseur · Critères d\'éligibilité et critères d\'évaluation des offres · Instructions aux soumissionnaires : délais, cautionnement, documents requis · Contrat type : clauses administratives générales et particulières · Exercice pratique : rédaction d\'un DAO complet sur un marché de fournitures ou de services','duree'=>$s[2]],
            ['titre'=>'Évaluation des offres, attribution et signature du marché','contenus'=>'Réception et ouverture des plis : procédure et procès-verbal · Commission d\'analyse des offres : composition, rôle et délibérations · Évaluation de la conformité administrative et technique · Évaluation financière et comparaison des offres · Rapport d\'évaluation et proposition d\'attribution · Approbation et publication de l\'attribution · Signature du marché et cautionnement définitif · Recours des soumissionnaires non retenus','duree'=>$s[3]],
            ['titre'=>'Exécution, suivi et contrôle du marché','contenus'=>'Ordre de service de démarrage et jalons contractuels · Suivi technique et financier : réceptions partielles et décomptes provisoires · Avenant : conditions strictes de recours · Pénalités de retard : calcul et application · Réception provisoire et définitive des prestations · Décompte final et règlement · Gestion des litiges et arbitrage · Archivage des marchés : délais et supports légaux','duree'=>$s[4]],
            ['titre'=>'Audit, contrôle et lutte contre la corruption dans les marchés publics','contenus'=>'Rôle de l\'ANRMP dans le contrôle et la régulation · Audits de passation et audits d\'exécution · Signaux d\'alerte de corruption et fraudes fréquentes · Code d\'éthique du praticien des marchés publics · Plaintes et recours devant l\'ANRMP · Responsabilité pénale des acteurs : sanctions prévues · Bonnes pratiques de gouvernance et transparence · Exercice : audit d\'un dossier de marché sur cas réel ivoirien','duree'=>$s[5]],
            ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : instruction complète d\'une procédure de passation de marché (DAO → évaluation → rapport d\'attribution) sur cas inédit · Correction individualisée et feedback · Remise du certificat IBIG EDUFORM "Marchés Publics ANRMP" et plan de développement','duree'=>$s[6]],
        ];
    }

    /* ══ Droit OHADA des sociétés / Contrats commerciaux OHADA ══ */
    if (preg_match('/droit\s+ohada|acte\s+uniforme|sarl.*sa.*gie|contrats?\s+commerciaux.*ohada|contentieux.*ohada|ccja|sûret[eé]s\s+ohada/ui', $nom)) {
        $s = $split(max(16, $heures), 7);
        return [
            ['titre'=>'Architecture de l\'OHADA et sources du droit des affaires en Afrique','contenus'=>'Traité de l\'OHADA : objectifs, États membres et institutions (CCJA, ERSUMA) · Les Actes Uniformes : valeur, primauté sur les droits nationaux, champ d\'application · Acte Uniforme sur les Sociétés Commerciales et GIE (AUSCGIE) · Acte Uniforme sur les Contrats (AUDCG), les Sûretés (AUS) et le Recouvrement (AUPSRVE) · Jurisprudence de la CCJA : comment accéder et utiliser la base de décisions · Exercice : identification de l\'Acte Uniforme applicable à une situation donnée','duree'=>$s[0]],
            ['titre'=>'Constitution et organisation des sociétés : SARL, SA et GIE','contenus'=>'Choix de la forme juridique : avantages, inconvénients et critères de décision · SARL : capital minimum (1 FCFA), associés, statuts, gérant · SA : capital minimum, actionnaires, conseil d\'administration ou DG seul · GIE : objet particulier, responsabilité et fiscalité spécifique · Rédaction des statuts : mentions obligatoires, clauses libres et clauses dangereuses · Procédure de création : greffe du Tribunal, RCCM et formalités pratiques · Exercice : rédaction d\'une clause statutaire de cession de parts','duree'=>$s[1]],
            ['titre'=>'Gouvernance, direction et responsabilité des dirigeants','contenus'=>'Pouvoirs du gérant de SARL et du DG de SA : étendue et limites · Assemblées générales : convocation, quorum, délibérations et PV obligatoires · AGO et AGE : distinctions, délais et formalités · Responsabilité civile et pénale des dirigeants en droit OHADA · Action sociale ut singuli et ut universi · Conventions réglementées : procédure et sanction de la violation · Exercice : analyse d\'une situation de conflits d\'intérêts de dirigeant','duree'=>$s[2]],
            ['titre'=>'Contrats commerciaux : formation, exécution et litiges','contenus'=>'Formation des contrats OHADA : consentement, objet, cause · Clauses essentielles : prix, délais, pénalités, force majeure, résolution · Contrats spéciaux : bail commercial, vente de fonds de commerce, franchise · Inexécution et résolution : conditions et procédure · Clause compromissoire vs clause attributive de juridiction · Arbitrage CCJA : procédure, sentence arbitrale et exécution · Exercice : rédaction et négociation d\'un contrat de vente ou de prestation de services','duree'=>$s[3]],
            ['titre'=>'Sûretés OHADA et recouvrement des créances','contenus'=>'Réforme des Sûretés OHADA 2010 : panorama des sûretés disponibles · Cautionnement : formation, étendue et recours contre la caution · Hypothèque : constitution, inscription et purge · Nantissement de créances et de fonds de commerce · Droit de rétention et réserve de propriété · Procédures simplifiées de recouvrement (AUPSRVE) : injonction de payer · Voies d\'exécution : saisie-attribution, saisie immobilière · Exercice : choix et mise en œuvre d\'une sûreté adaptée','duree'=>$s[4]],
            ['titre'=>'Difficultés des entreprises et procédures collectives OHADA','contenus'=>'Alerte précoce et mandat ad hoc : quand y recourir · Conciliation : procédure et accord · Règlement préventif (plan de redressement amiable) · Redressement judiciaire : conditions, période d\'observation, plan · Liquidation des biens : conséquences et désintéressement des créanciers · Responsabilité en cas de faute de gestion en période suspecte · Exercice : analyse d\'une situation d\'entreprise en difficulté et recommandations','duree'=>$s[5]],
            ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : traitement d\'un dossier juridique complet (création + contrat + litige + recouvrement) sur cas OHADA inédit · Correction individualisée et feedback · Remise du certificat IBIG EDUFORM "Droit OHADA" et plan de développement','duree'=>$s[6]],
        ];
    }

    /* ══ Transit douanier / Import-Export / Logistique internationale ══ */
    if (preg_match('/transit\s+douanier|sydam|dgd\s+c[oô]te|import[- ]export|guichet\s+unique|incoterms|d[eé]douanement/ui', $nom)) {
        $s = $split(max(16, $heures), 7);
        return [
            ['titre'=>'Organisation du commerce extérieur et acteurs de la chaîne logistique','contenus'=>'Structure du commerce international : exportateurs, importateurs, transitaires, transporteurs, banques · Réglementation du commerce extérieur en Côte d\'Ivoire · Port d\'Abidjan : organisation, terminaux et opérateurs · Guichet Unique du Commerce Extérieur (GUCE) : procédures dématérialisées · Cotecna et BIVAC : inspection avant embarquement · Exercice : cartographie d\'une opération d\'importation complète','duree'=>$s[0]],
            ['titre'=>'Nomenclature tarifaire, classification SH et valeur en douane','contenus'=>'Système Harmonisé (SH) de désignation et de codification des marchandises · Logique de la nomenclature : sections, chapitres, positions · Pratique de la classification : outils, notes explicatives, avis de classement · Valeur en douane : méthode transactionnelle GATT/OMC et méthodes subsidiaires · Éléments constitutifs de la valeur : prix, fret, assurance · Exercice : classification de 10 produits courants et calcul de leur valeur en douane','duree'=>$s[1]],
            ['titre'=>'Régimes douaniers et procédures de dédouanement','contenus'=>'Importation définitive : procédure, documents requis, paiement des droits · Exportation définitive : formalités, certificat d\'origine, remboursements · Régimes économiques en douane : entrepôt, admission temporaire, perfectionnement actif · Transit douanier national et international · Régime de l\'exportateur agréé et procédures simplifiées · SYDAM World : saisie de la déclaration en détail, validation et BAE · Exercice pratique sur SYDAM : remplissage d\'une déclaration d\'importation','duree'=>$s[2]],
            ['titre'=>'Incoterms 2020 : choix stratégique et impact logistique','contenus'=>'Définition et rôle des Incoterms dans le commerce international · Famille EXW, FCA, CPT, CIP : logique multimodale · Famille FOB, CFR, CIF : usage maritime et pièges · Incoterm DDP : responsabilité maximale du vendeur · Choix de l\'Incoterm selon le rapport de force commercial · Impact sur la valeur en douane, l\'assurance et la TVA · Exercice : sélection des Incoterms optimaux sur des cas de négociation réels','duree'=>$s[3]],
            ['titre'=>'Financement du commerce international et documents','contenus'=>'Crédit documentaire (L/C) : mécanisme, types et responsabilité de chaque acteur · Remise documentaire : procédure et risques · Garanties bancaires internationales · Documents du commerce : connaissement maritime (B/L), LTA aérienne, CMR terrestre · Facture commerciale, liste de colisage, certificat d\'origine · Assurance transport : polices flottantes et déclarations d\'aliment · Exercice : montage d\'un dossier documentaire complet','duree'=>$s[4]],
            ['titre'=>'Contrôle douanier, contentieux et optimisation des coûts','contenus'=>'Droits et pouvoirs de la DGD lors des contrôles · Infractions douanières : contrebande, fausse déclaration, sous-évaluation · Procédure contentieuse douanière : transaction et action judiciaire · Restitution des droits indûment payés · Optimisation des coûts douaniers : régimes préférentiels CEDEAO, ACPé-UE · Calcul du coût complet d\'une importation : DDP total · Exercice : calcul des droits et taxes sur une importation réelle','duree'=>$s[5]],
            ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : traitement complet d\'un dossier import ou export (nomenclature → régime → SYDAM → documents → coûts) sur cas inédit · Correction individualisée et feedback · Remise du certificat IBIG EDUFORM "Transit Douanier / Import-Export" et plan de développement','duree'=>$s[6]],
        ];
    }

    /* ══ Réglementation BCEAO / Conformité bancaire ══ */
    if (preg_match('/r[eé]glementation\s+bceao|conformit[eé]\s+bancaire|lbc.ft|kyc.*aml|banque.*r[eé]glementation|compliance\s+bancaire/ui', $nom)) {
        $s = $split(max(16, $heures), 7);
        return [
            ['titre'=>'Architecture réglementaire BCEAO/UEMOA et agrément bancaire','contenus'=>'Organisation institutionnelle : BCEAO, Conseil des Ministres UEMOA, Commission Bancaire · Loi Bancaire UEMOA (2010) : champ d\'application et catégories d\'établissements · Procédure d\'agrément et conditions de capital minimum · Gouvernance des établissements de crédit : Conseil d\'Administration, Comité d\'Audit · Instructions et circulaires BCEAO : comment s\'y référer et les appliquer · Exercice : analyse d\'un texte BCEAO récent et identification des obligations','duree'=>$s[0]],
            ['titre'=>'Ratios prudentiels BCEAO et gestion des risques','contenus'=>'Ratio de solvabilité (Bâle II/III adapté UEMOA) : calcul et exigences · Ratio de liquidité : LCR et exigences BCEAO · Ratio de division des risques : concentration et plafonds · Grands risques et expositions sur les parties liées · Reporting prudentiel : états BCEAO, délais et procédures de transmission · Plan d\'urgence de trésorerie · Exercice : calcul des ratios prudentiels sur des données bilan simulées','duree'=>$s[1]],
            ['titre'=>'Dispositif LBC/FT : obligations KYC et surveillance des opérations','contenus'=>'Cadre légal : Loi Uniforme UEMOA relative à la LBC/FT · Obligations KYC (Know Your Customer) : identification, vérification, mise à jour · Bénéficiaire effectif : définition et procédure de recherche · Surveillance des opérations : transactions inhabituelles et seuils · Obligations déclaratives auprès de la CENTIF/CENTIB · Gel des avoirs et listes de sanctions internationales · Tiers introducteurs : obligations et responsabilités · Exercice : analyse d\'un cas de détection d\'opération suspecte','duree'=>$s[2]],
            ['titre'=>'Gestion des risques bancaires : crédit, marché et opérationnel','contenus'=>'Risque de crédit : classification des créances (saines, en souffrance, compromises) · Provisionnement et plans de désendettement · Risque de marché : risque de taux, de change et de prix · Risque opérationnel : incidents, fraudes, panne informatique · Stress tests et scénarios de crise · Comité de gestion actif-passif (ALCO) · Risk appetite statement et politique des risques · Exercice : classification d\'un portefeuille de crédit et calcul des provisions requises','duree'=>$s[3]],
            ['titre'=>'Fintech, Mobile Money et cadre réglementaire BCEAO','contenus'=>'Instruction BCEAO sur les établissements de monnaie électronique (EME) · Agrément et obligations prudentielles des EME · Interopérabilité Mobile Money en UEMOA · API Banking : enjeux réglementaires et contractuels · Open Banking : état des lieux en Afrique de l\'Ouest · Crypto-actifs : position de la BCEAO · Cybersécurité bancaire : directive BCEAO et obligations · Supervision des risques numériques','duree'=>$s[4]],
            ['titre'=>'Contrôle BCEAO, inspection et préparation aux audits réglementaires','contenus'=>'Mission d\'inspection de la BCEAO : droit de communication, dossiers à préparer · Lettre de recommandation post-inspection : procédure de réponse · Mise en demeure et sanctions de la Commission Bancaire · Reporting FINREP/COREP : format et délais · Préparation d\'un audit réglementaire externe · Check-list de conformité : auto-évaluation par domaine · Exercice : réponse structurée à une lettre de recommandation d\'inspection','duree'=>$s[5]],
            ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : analyse d\'un dossier de conformité bancaire complet (KYC + ratios + LBC/FT + reporting) sur cas inédit · Correction individualisée et feedback · Remise du certificat IBIG EDUFORM "Réglementation BCEAO & Conformité Bancaire"','duree'=>$s[6]],
        ];
    }

    /* ══ Gestion de projet ONG / Cadre Logique AFD/UE/USAID ══ */
    if (preg_match('/cadre\s+logique|afd.*usaid|ue.*usaid|proj.{1,10}d[eé]veloppement|ong.*projet|rapportage.*bailleur|suivi.?[eé]valuation|kobo|odk\b|meal\b/ui', $nom)) {
        $s = $split(max(16, $heures), 7);
        return [
            ['titre'=>'Environnement des projets de développement et cycle de projet','contenus'=>'Acteurs : bailleurs (AFD, UE, USAID, PNUD, Banque Mondiale), ONG, gouvernements, bénéficiaires · Cycle de projet : identification, formulation, financement, mise en œuvre, évaluation · Documents types : Concept Note, Document de Projet (ProDoc), Convention de Financement · Structure de gouvernance d\'un projet (CP, UGP, bénéficiaires) · Sélection et gestion des partenaires de mise en œuvre · Exercice : analyse d\'une Convention de Financement AFD réelle','duree'=>$s[0]],
            ['titre'=>'Théorie du Changement et Cadre Logique (LFA)','contenus'=>'Théorie du Changement : hypothèses de causalité, chaîne de résultats · Approche du Cadre Logique (LFA) : principes et étapes · Matrice du Cadre Logique (MCL) : objectif global, objectif spécifique, résultats, activités · Indicateurs SMART : formulation, baseline et cible · Sources de vérification et fréquence de collecte · Hypothèses et risques dans la MCL · Exercice : construction d\'une MCL complète sur un projet fictif ou réel','duree'=>$s[1]],
            ['titre'=>'Planification opérationnelle : PTA, budget et gestion des ressources','contenus'=>'Plan de Travail Annuel (PTA) : structure, activités, responsables, calendrier · Budgétisation par activités : budget prévisionnel et justification · Règles d\'éligibilité des dépenses par bailleur (per diem, coûts indirects, contingences) · Passation des marchés et achats : procédures PPTE, USAID, UE · Gestion des ressources humaines du projet : recrutement et TDR · Exercice : construction d\'un PTA et d\'un budget prévisionnel d\'un projet ONG','duree'=>$s[2]],
            ['titre'=>'Suivi-Évaluation (S&E) et collecte des données terrain','contenus'=>'Plan de Suivi-Évaluation (PSE) : méthodes quantitatives et qualitatives · Collecte de données numériques : KoBoToolbox / ODK Collect · Conception du formulaire KoBoCollect : logique de saut, validation, types de réponses · Déploiement mobile et collecte sur le terrain · Gestion des données collectées : export, nettoyage, archivage · Visualisation des résultats : tableaux de bord MEAL · Exercice pratique : conception et déploiement d\'un formulaire KoBoCollect','duree'=>$s[3]],
            ['titre'=>'Reporting financier et narratif aux bailleurs','contenus'=>'Rapport narratif intermédiaire et final : structure et exigences des bailleurs · Format SF-425 USAID · Rapports financiers UE (annexes financières C.1 et C.2) · IFR et FM Reports Banque Mondiale · Gestion des avances et justifications de dépenses · Gestion des devises et taux de change de référence · Audit externe des projets (Single Audit USAID, ACA UE) · Exercice : rédaction d\'un rapport narratif intermédiaire sur cas réel','duree'=>$s[4]],
            ['titre'=>'Évaluation des projets : méthodologie, conduite et restitution','contenus'=>'Types d\'évaluation : à mi-parcours, finale, d\'impact, ex-ante · Termes de Référence de l\'évaluation : rédaction et négociation · Méthodes d\'évaluation : enquêtes, focus group, observations, données secondaires · Critères CAD/OCDE : pertinence, cohérence, efficacité, efficience, impact, durabilité · Rapport d\'évaluation : structure et qualité de l\'analyse · Restitution participative aux parties prenantes · Plan d\'apprentissage organisationnel : intégrer les leçons dans la pratique','duree'=>$s[5]],
            ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : construction complète d\'une MCL + PSE + rapport d\'avancement sur un projet inédit · Correction individualisée et feedback · Remise du certificat IBIG EDUFORM "Gestion de Projet ONG / Cadre Logique" et plan de développement','duree'=>$s[6]],
        ];
    }

    /* ══ Business Plan / Entrepreneuriat / Création d'entreprise ══ */
    if (preg_match('/business\s+plan|cr[eé]ation\s+d.entreprise|cepici|entrepreneuriat|lancer\s+son|monter\s+son\s+projet/ui', $nom)) {
        $s = $split(max(16, $heures), 7);
        return [
            ['titre'=>'Valider son idée et construire son modèle d\'affaires','contenus'=>'Passage de l\'idée au concept viable : grille de validation en 10 points · Business Model Canvas (BMC) : les 9 blocs appliqués au contexte ivoirien · Analyse de marché : segmentation client, taille du marché, concurrents · Tests de concept : enquêtes terrain, MVP, prototype à faible coût · Identification des risques majeurs et hypothèses critiques · Exercice : construction du BMC du projet du bénéficiaire','duree'=>$s[0]],
            ['titre'=>'Étude de marché et stratégie commerciale','contenus'=>'Méthodologie de l\'étude de marché : sources primaires et secondaires · Enquêtes clients : rédaction du questionnaire, administration et analyse · Analyse de la concurrence : positionnement, avantages différenciants · Stratégie de prix : coûts, valeur perçue, prix du marché · Stratégie de distribution en Côte d\'Ivoire : circuits formels et informels · Plan de lancement commercial et acquisition des premiers clients','duree'=>$s[1]],
            ['titre'=>'Forme juridique, procédures CEPICI et formalités de création','contenus'=>'Formes juridiques disponibles en CI : SARL, SA, EI, GIE, SAS · Critères de choix selon le projet, les associés et la fiscalité · Procédures CEPICI (guichet unique) : étapes et délais réels · Rédaction des statuts et mentions obligatoires · Immatriculation RCCM, numéro SIUCEN et affiliation DGI · Affiliation CNPS et CMU · Ouverture du compte professionnel · Coûts réels de création en Côte d\'Ivoire','duree'=>$s[2]],
            ['titre'=>'Projections financières et plan de financement','contenus'=>'Compte de résultat prévisionnel sur 3 ans : hypothèses et construction · Bilan prévisionnel de départ et d\'ouverture · Plan de trésorerie mensuel : anticipation des besoins · Calcul du seuil de rentabilité et du délai de récupération · Besoin en Fonds de Roulement (BFR) de démarrage · Plan de financement : ressources propres, emprunts, subventions · Exercice : construction des projections financières du projet du bénéficiaire','duree'=>$s[3]],
            ['titre'=>'Sources de financement PME en Côte d\'Ivoire','contenus'=>'Banques ivoiriennes : critères d\'octroi, dossier type, garanties exigées · Microfinance (COFINA, Advans, UNACOOPEC) : produits et conditions · Fonds propres et love money : quand et comment · Capital-risque et business angels en Afrique : acteurs locaux · FGPME, BPI CI, CFA Franc : dispositifs d\'appui à la PME · Financement des jeunes (PEJEDEC, PNSD, C2D) · Financement participatif (Ulule, Miimosa, GoFundMe Afrique) · Exercice : sélection et montage du dossier de financement adapté','duree'=>$s[4]],
            ['titre'=>'Rédaction du Business Plan et pitch aux investisseurs','contenus'=>'Structure du Business Plan "bancable" : résumé exécutif percutant · Présentation de l\'équipe et des compétences clés · Présentation du marché et de la stratégie · Projections financières : clés à mettre en avant · Annexes convaincantes : lettres d\'intention, démo, données de marché · Elevator pitch (2 min) et pitch deck (10 slides) · Simulation de présentation devant un jury · Questions difficiles des investisseurs : comment y répondre','duree'=>$s[5]],
            ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : présentation orale du Business Plan du bénéficiaire devant un jury simulant un comité de financement · Feedback structuré sur la solidité du projet et du dossier · Remise du certificat IBIG EDUFORM "Entrepreneuriat & Business Plan" et plan d\'action des 90 jours post-formation','duree'=>$s[6]],
        ];
    }

    /* ══ Microfinance / SFD ══ */
    if (preg_match('/microfinance|syst[eè]mes?\s+financiers?\s+d[eé]centralis[eé]s?|sfd\b|imf\b.*cr[eé]dit|warrantage|cr[eé]dit.{1,10}stockage/ui', $nom)) {
        $s = $split(max(16, $heures), 7);
        return [
            ['titre'=>'Environnement réglementaire des SFD en UEMOA','contenus'=>'Loi PARMEC et son évolution : champ d\'application, catégories d\'IMF · Autorité de tutelle : BCEAO, Ministères des Finances, fédérations · Agrément des SFD : conditions, procédure et obligations post-agrément · Ratios prudentiels BCEAO spécifiques aux SFD · Gouvernance coopérative : AG, Conseil d\'Administration, Comité de Surveillance · Exercice : analyse des statuts d\'une coopérative d\'épargne et de crédit','duree'=>$s[0]],
            ['titre'=>'Produits financiers des SFD et analyse des besoins clients','contenus'=>'Épargne volontaire et obligatoire : caractéristiques et gestion · Crédit solidaire de groupe (modèle Grameen) : fonctionnement et gestion du groupe · Crédit individuel : conditions, garanties et suivi · Leasing et crédit-bail pour les petites entreprises · Assurance crédit et assurance-vie emprunteur · Produits saisonniers et warrantage · Mobile Money et portefeuilles numériques dans les SFD · Exercice : conception d\'un produit de crédit adapté à une clientèle cible','duree'=>$s[1]],
            ['titre'=>'Instruction et analyse des dossiers de crédit','contenus'=>'Collecte des informations : visite terrain, entretien, documents · Analyse des revenus et des charges du ménage ou de l\'entreprise · Capacité de remboursement et niveau d\'endettement · Analyse des garanties disponibles (caution, nantissement, warrantage) · Scoring de crédit simplifié adapté aux SFD · Comité de crédit : présentation et décision · Décaissement et gestion contractuelle · Exercice : instruction d\'un dossier de crédit agricole','duree'=>$s[2]],
            ['titre'=>'Gestion du risque de crédit et recouvrement','contenus'=>'Indicateurs de qualité du portefeuille : PAR 30, PAR 90, taux de perte · Classification BCEAO des créances des SFD · Causes des impayés : analyse terrain et préventions · Stratégies de recouvrement amiable : relance, négociation, restructuration · Recouvrement contentieux : voies juridiques (OHADA) · Provisions et passage en pertes · Audit du portefeuille et rapport au CA · Exercice : analyse d\'un portefeuille et calcul des provisions requises','duree'=>$s[3]],
            ['titre'=>'Performance financière et pilotage des SFD','contenus'=>'Compte de résultat d\'un SFD : revenus d\'intérêts, charges financières, frais opérationnels · Taux d\'intérêt effectif global (TIEG) et coût réel du crédit · Autosuffisance opérationnelle (OSS) et autosuffisance financière (FSS) · Indicateurs CGAP de performance sociale et financière · Système d\'Information de Gestion (SIG) : sélection et utilisation · Tableaux de bord de pilotage et rapport au Conseil d\'Administration · Exercice : construction d\'un tableau de bord de performance pour un SFD','duree'=>$s[4]],
            ['titre'=>'Gouvernance, transformation digitale et supervision','contenus'=>'Bonnes pratiques de gouvernance coopérative : prévention des conflits d\'intérêts · Digitalisation des SFD : Mobile Money, paiements numériques, KYC digital · Rapport de supervision BCEAO : préparation et procédure · Fusion et transformation des SFD : enjeux et procédures · Responsabilité sociale des SFD : inclusion financière et clientèle vulnérable · Plan stratégique d\'un SFD : méthodologie et priorités','duree'=>$s[5]],
            ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : instruction d\'un dossier de crédit + calcul du PAR + plan de redressement sur cas inédit · Correction individualisée et feedback · Remise du certificat IBIG EDUFORM "Microfinance & SFD" et plan de développement','duree'=>$s[6]],
        ];
    }

    /* ══ Filière Cacao / Agrobusiness / Agriculture ══ */
    if (preg_match('/cacao|agrob[uo]siness|agro.{0,5}aliment|filière.*certif|tracabilit[eé]|eudr|rainforest|fairtrade/ui', $nom)) {
        $s = $split(max(14, $heures), 6);
        return [
            ['titre'=>'Organisation de la filière et enjeux de la certification','contenus'=>'Structure de la filière cacao ivoirienne : production, traitement, exportation · Acteurs : producteurs, coopératives, pisteurs, exportateurs agréés, Conseil du Café-Cacao (CCC) · Prix bord-champ et mécanismes de fixation des prix · Certifications majeures : Rainforest Alliance (RA), Fairtrade/Max Havelaar, UTZ Certified · Avantages économiques et primes de durabilité · Exigences de base communes aux standards internationaux · Exercice : comparaison des exigences RA vs Fairtrade sur un cahier des charges réel','duree'=>$s[0]],
            ['titre'=>'Systèmes de traçabilité et gestion documentaire','contenus'=>'Principes de la traçabilité : lot par lot vs masse-bilan · SECP (Système Électronique de la Chaîne de Production) du CCC · Registres de production, de collecte et de livraison · Technologies de traçabilité : applications mobiles, QR codes, blockchain · Gestion des documents de certification : audits internes, checklists, plans d\'action · Archivage et durée de conservation · Exercice : mise en place d\'un système de registres pour une coopérative de 500 membres','duree'=>$s[1]],
            ['titre'=>'Loi EUDR et Due Diligence sur la déforestation','contenus'=>'Règlement européen EUDR (2024) : texte, dates d\'application et produits concernés · Obligation de Due Diligence : cartographie géospatiale des parcelles · Systèmes de géolocalisation : GPS, QGIS, satellites · Collecte des coordonnées GPS des parcelles producteurs · Plan de gestion du risque EUDR : évaluation et mesures d\'atténuation · Communication et accompagnement des producteurs · Sanctions et impact sur l\'accès au marché européen','duree'=>$s[2]],
            ['titre'=>'Bonnes Pratiques Agricoles (BPA) et durabilité','contenus'=>'BPA certifiées selon RA : gestion de l\'ombre, agroforesterie, rejuvénation · Lutte intégrée contre les maladies (CSSV, pourriture brune) · Gestion durable des sols et de l\'eau · Fertilisation raisonnée et réduction des intrants chimiques · Travail des enfants : protocole ivoirien et procédures de remédiation · Sécurité et santé au travail agricole : EPI, stockage des pesticides · Exercice : audit interne d\'une exploitation cacao selon le référentiel RA','duree'=>$s[3]],
            ['titre'=>'Gestion des coopératives certifiées et commercialisation premium','contenus'=>'Gouvernance coopérative : AG, Conseil d\'Administration, contrôle interne · Fonds de prime Fairtrade/RA : gestion et projets de développement communautaire · Contractualisation avec les acheteurs internationaux : contrats multi-annuels et prix de référence · Accès aux marchés premium : exportateurs, chocolatiers, grandes marques · Gestion de la trésorerie de campagne : financement et remboursement · Reporting aux organes de certification et préparation des audits externes','duree'=>$s[4]],
            ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : audit interne simulé d\'une coopérative cacao (RA ou Fairtrade) avec plan d\'action correctif · Correction individualisée et feedback · Remise du certificat IBIG EDUFORM "Filière Cacao & Certification" et plan de développement','duree'=>$s[5]],
        ];
    }

    /* ══ Odoo ERP ══ */
    if (preg_match('/odoo|erp.*gestion|gestion.{1,10}int[eé]gr[eé]e/ui', $nom)) {
        $s = $split(max(16, $heures), 7);
        return [
            ['titre'=>'Architecture Odoo et navigation dans l\'interface','contenus'=>'Architecture technique d\'Odoo (MVC, ORM, modules) · Navigation : menus, formulaires, listes, kanban · Gestion des utilisateurs, profils et droits d\'accès · Paramètres généraux : société, devise, langue, exercice fiscal · Base de données : sauvegardes et restauration · Exercice : configuration de base d\'une nouvelle société ivoirienne','duree'=>$s[0]],
            ['titre'=>'Module Comptabilité : plan de comptes, facturation et rapprochement','contenus'=>'Plan de comptes OHADA sous Odoo : import et configuration · Saisie des factures clients et fournisseurs · Rapprochement bancaire : import des relevés et lettrage automatique · TVA ivoirienne : paramétrage des taxes et déclaration · États financiers natifs : bilan, compte de résultat, balance générale · Gestion des paiements partiels et des avoirs · Exercice : saisie d\'un mois de comptabilité d\'une PME ivoirienne','duree'=>$s[1]],
            ['titre'=>'Module Ventes & CRM : devis, commandes et pipeline commercial','contenus'=>'Paramétrage de la liste de prix et des conditions commerciales · Création et envoi de devis professionnels · Confirmation de commande et workflow de validation · Pipeline CRM : étapes, probabilité et prévision de chiffre d\'affaires · Gestion des clients et segmentation · Activités et relances commerciales automatiques · Rapports commerciaux : CA par vendeur, par client, par produit · Exercice : gestion complète d\'un cycle de vente','duree'=>$s[2]],
            ['titre'=>'Module Achats, Stocks et gestion d\'entrepôt','contenus'=>'Création des fournisseurs et conditions d\'achat · Appel d\'offres fournisseurs et comparaison · Réceptions et contrôle de qualité à l\'arrivée · Gestion des stocks : emplacements, lots et numéros de série · Règles de réapprovisionnement (min/max, orderpoints) · Inventaire physique et ajustements de stock · Transferts inter-entrepôts · Exercice : gestion d\'un cycle d\'achat complet','duree'=>$s[3]],
            ['titre'=>'Module RH, Paie et gestion des employés','contenus'=>'Création des fiches employés et structure salariale · Contrats de travail sous Odoo · Gestion des congés et des absences · Feuilles de temps et valorisation des coûts · Paie : règles salariales, cotisations CNPS, IRVM (paramétrages CI) · Évaluations et entretiens annuels · Rapport bilan social · Exercice : établissement d\'un bulletin de paie sous Odoo','duree'=>$s[4]],
            ['titre'=>'Reporting, personnalisation et bonnes pratiques d\'implémentation','contenus'=>'Moteur de reporting Odoo : personnalisation des états existants · Business Intelligence natif : tableaux de bord et filtres personnalisés · Export vers Excel et Power BI · Personnalisation sans code : studio Odoo · Bonnes pratiques d\'implémentation : conduite du changement, formation des utilisateurs · Maintenance évolutive et mises à jour · Exercice : construction d\'un tableau de bord de gestion PME sous Odoo','duree'=>$s[5]],
            ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : paramétrage complet d\'une société fictive et traitement d\'un mois d\'activité (achats + ventes + paie + états financiers) · Correction individualisée et feedback · Remise du certificat IBIG EDUFORM "Odoo ERP" et plan de mise en œuvre','duree'=>$s[6]],
        ];
    }

    /* ══ Cybersécurité / Administration réseau ══ */
    if (preg_match('/cybers[eé]curit[eé]|s[eé]curit[eé]\s+inform|administration\s+r[eé]seau|hacking|pentest|pfsense|firewall.*pme/ui', $nom)) {
        $s = $split(max(16, $heures), 7);
        return [
            ['titre'=>'Fondamentaux des réseaux : TCP/IP, routage et topologies','contenus'=>'Modèle OSI et TCP/IP : couches et protocoles · Adressage IP (IPv4/IPv6), sous-réseaux et masques CIDR · Équipements réseau : routeurs, switches, hubs, points d\'accès · LAN, WAN, VLAN et segmentation réseau · Protocoles essentiels : DNS, DHCP, HTTP/S, SMTP, FTP · Analyse du trafic réseau avec Wireshark · Exercice : conception du plan d\'adressage d\'un réseau de PME','duree'=>$s[0]],
            ['titre'=>'Administration Windows Server et Active Directory','contenus'=>'Installation et configuration de Windows Server 2019/2022 · Active Directory : domaine, UO, groupes et GPO · Gestion des comptes utilisateurs et politiques de mot de passe · Group Policy Objects (GPO) : déploiement de configurations et restrictions · DNS et DHCP intégrés à l\'AD · Sauvegarde et restauration de l\'AD · Exercice : mise en place d\'un domaine Active Directory pour une PME','duree'=>$s[1]],
            ['titre'=>'Pare-feux, VPN et sécurisation du périmètre','contenus'=>'pfSense / OPNsense : installation et configuration · Règles de filtrage : whitelisting, blacklisting, NAT · VPN IPsec et OpenVPN : accès distant sécurisé · Proxy et filtrage de contenu web · DMZ : conception et mise en œuvre · IDS/IPS : Snort/Suricata sur pfSense · Segmentation des réseaux Wi-Fi (WPA3, SSID séparés, 802.1X) · Exercice : configuration complète d\'un pare-feu pfSense sur VM','duree'=>$s[2]],
            ['titre'=>'Audit de sécurité et identification des vulnérabilités','contenus'=>'Méthodologie d\'audit de sécurité : périmètre, règles d\'engagement · Scan de vulnérabilités avec Nessus/OpenVAS · Tests de pénétration éthiques : Kali Linux, Nmap, Metasploit (notions) · Top 10 OWASP : vulnérabilités web et exemples d\'exploitation · Social engineering : phishing, vishing, sensibilisation des employés · Rapport d\'audit de sécurité : structure et recommandations · Exercice : audit simulé d\'un réseau de PME','duree'=>$s[3]],
            ['titre'=>'Protection des données, conformité et politiques de sécurité','contenus'=>'RGPD et loi ivoirienne sur la protection des données (ARTCI) · Politique de Sécurité des Systèmes d\'Information (PSSI) : rédaction · Classification des données et gestion des droits d\'accès · DLP (Data Loss Prevention) : outils et procédures · Chiffrement des données : disques, messagerie, communications · Gestion des mots de passe : gestionnaire, MFA, bonnes pratiques · Charte informatique : contenu et mise en œuvre contractuelle','duree'=>$s[4]],
            ['titre'=>'Réponse aux incidents, sauvegardes et continuité d\'activité','contenus'=>'Plan de Réponse aux Incidents (PRI) : étapes et responsabilités · Détection et qualification d\'un incident de sécurité · Investigation et forensics de base : logs, timeline, preuves · Plan de sauvegarde 3-2-1 : stratégie, outils et tests de restauration · Plan de Reprise d\'Activité (PRA) et Plan de Continuité (PCA) · RTO et RPO : calcul et objectifs · Gestion de la communication de crise en cas de cyberattaque','duree'=>$s[5]],
            ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : audit de sécurité + configuration pare-feu + plan de sécurité sur scénario de PME inédit · Correction individualisée et feedback · Remise du certificat IBIG EDUFORM "Cybersécurité & Réseau" et plan de mise en conformité','duree'=>$s[6]],
        ];
    }

    /* ══ WordPress / SEO ══ */
    if (preg_match('/wordpress|woocommerce|seo\b|r[eé]f[eé]rencement\s+(naturel|web)|site\s+(web|professionnel).*cr[eé]er/ui', $nom)) {
        $s = $split(max(14, $heures), 6);
        return [
            ['titre'=>'Création et configuration d\'un site WordPress professionnel','contenus'=>'Hébergement web en Côte d\'Ivoire / Afrique : critères de choix et coûts · Nom de domaine (.ci, .com, .africa) : enregistrement et DNS · Installation de WordPress via cPanel ou FTP · Paramètres essentiels : langue, timezone, URLs permanentes · Choix du thème : thèmes gratuits vs premium, critères de sélection · Installation et activation d\'Elementor ou Gutenberg · Exercice : création et configuration d\'un site WordPress opérationnel','duree'=>$s[0]],
            ['titre'=>'Construction des pages et gestion du contenu','contenus'=>'Création de pages : Accueil, À propos, Services, Contact, Blog · Construction de mises en page avec Elementor : colonnes, sections, widgets · Articles de blog : catégories, tags, médias · Médiathèque : optimisation des images (WebP, compression) · Menu de navigation : structure et hiérarchie · Formulaire de contact (Contact Form 7 / WPForms) · Page tarifs et galerie photo professionnelle · Exercice : construction complète du site du bénéficiaire','duree'=>$s[1]],
            ['titre'=>'SEO on-page et technique','contenus'=>'Installation et configuration de Yoast SEO ou Rank Math · Recherche de mots-clés : Google Keyword Planner, Ubersuggest · Structure sémantique : balises H1-H6, méta-titre, méta-description · Maillage interne : liens, ancres et hiérarchie des pages · Optimisation des images : alt text, taille et formats · Vitesse de chargement : cache (WP Rocket), lazy load, hébergement · SEO local : Google Business Profile pour les entreprises ivoiriennes · Exercice : audit SEO on-page du site du bénéficiaire et plan d\'optimisation','duree'=>$s[2]],
            ['titre'=>'E-commerce avec WooCommerce et Mobile Money','contenus'=>'Installation et configuration de WooCommerce · Création des fiches produits : description, images, prix, stock · Configuration des modes de livraison et zones · Intégration des paiements : Mobile Money (Orange, MTN, Wave), PayDunya, Flutterwave · Gestion des commandes et processus de livraison · Coupons et promotions · Règles fiscales TVA e-commerce en CI · Exercice : création d\'une boutique e-commerce opérationnelle','duree'=>$s[3]],
            ['titre'=>'Google Analytics, Search Console et suivi des performances','contenus'=>'Configuration de Google Analytics 4 sur WordPress · Google Search Console : vérification, sitemap, erreurs d\'indexation · Suivi des conversions et événements · Lectures des rapports : acquisition, comportement, performance · Heatmaps et analyse de comportement (Hotjar) · Tableau de bord de reporting mensuel · Stratégie de contenu et calendrier éditorial SEO · Exercice : configuration complète des outils de suivi et premier rapport','duree'=>$s[4]],
            ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : audit complet d\'un site WordPress (SEO + performance + sécurité) avec plan d\'action · Correction individualisée et feedback · Remise du certificat IBIG EDUFORM "WordPress & SEO" et plan de développement','duree'=>$s[5]],
        ];
    }

    /* ══ Mobile Money / Intégration paiements mobiles ══ */
    if (preg_match('/mobile\s+money|paiements?\s+mobiles?|orange\s+money\s+api|mtn\s+momo|wave\s+(business|api)|moneroo/ui', $nom)) {
        $s = $split(max(14, $heures), 6);
        return [
            ['titre'=>'Écosystème Mobile Money en Afrique de l\'Ouest : acteurs et enjeux','contenus'=>'Panorama du Mobile Money en UEMOA : Orange Money, MTN MoMo, Wave, Moov Money · Volumes de transactions et pénétration du marché en CI · Cadre réglementaire BCEAO des établissements de monnaie électronique · Modèles économiques : float, commissions, agrégateurs · Cas d\'usage : marchands, b2b, transferts, salaires, assurances · Interopérabilité UEMOA : état d\'avancement et API communes · Exercice : cartographie des solutions de paiement disponibles pour une PME ivoirienne','duree'=>$s[0]],
            ['titre'=>'Fondamentaux des APIs REST et authentification','contenus'=>'Architecture REST : ressources, méthodes HTTP, codes de statut · Authentification : OAuth 2.0, clés API, Bearer Token · Utilisation de Postman pour tester les APIs · Format JSON : structure, parsing et validation · Gestion des erreurs et retry logic · Webhooks vs polling : conception event-driven · Sandbox vs production : bonnes pratiques de transition · Exercice : appels REST simples avec Postman sur une sandbox','duree'=>$s[1]],
            ['titre'=>'Intégration Orange Money API et MTN MoMo API','contenus'=>'Documentation officielle Orange Money Business CI : création du compte, credentials · Flow de paiement marchand : initiation, confirmation, statut · Gestion des callbacks (webhooks) en temps réel · MTN MoMo API (Collection, Disbursement, Remittance) : inscription et authentification · Sandbox MTN : création des utilisateurs et test des flux · Gestion des timeouts et cas d\'erreur · Exercice : intégration d\'un paiement Orange Money sur un formulaire web PHP/JS','duree'=>$s[2]],
            ['titre'=>'Intégration Wave Business et agrégateurs de paiement','contenus'=>'Wave Business API : documentation, endpoints et schéma de données · Paiement marchand Wave : initiation et confirmation · Moneroo (agrégateur CI) : avantages et workflow multi-opérateurs · Flutterwave et CinetPay : intégration simplifiée multi-pays · Comparaison des frais et des temps de traitement · Choix de la solution selon la taille et le secteur · Exercice : intégration de CinetPay ou Moneroo pour accepter plusieurs opérateurs','duree'=>$s[3]],
            ['titre'=>'Réconciliation financière, sécurité et conformité réglementaire','contenus'=>'Réconciliation des transactions : tableaux de bord et rapprochements · Gestion des échecs, doublons et chargebacks · Sécurité des APIs : HTTPS, signatures HMAC, IP whitelisting · Stockage des données de paiement : conformité PCI-DSS (notions) · Obligations BCEAO pour les marchands Mobile Money · Facturation électronique et intégration au logiciel comptable · Exercice : construction d\'un tableau de réconciliation automatisée sous Excel','duree'=>$s[4]],
            ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : intégration complète d\'un flux de paiement Mobile Money (initiation → webhook → réconciliation) sur un scénario marchand inédit · Correction individualisée et feedback · Remise du certificat IBIG EDUFORM "Mobile Money & Paiements Mobiles" et plan de déploiement','duree'=>$s[5]],
        ];
    }

    /* ══ IA générative / ChatGPT / Claude / Outils IA ══ */
    if (preg_match('/chatgpt|gpt[-\s]?[34o]|ia\s+g[eé]n[eé]rative|intelligence\s+artificielle.*pratique|prompt\s+engineering|copilot|gemini|mistral|outils?\s+ia/ui', $nom)
        && !preg_match('/\bclaude\b/ui', $nom)) {
        $s = $split(max(14, $heures), 6);
        return [
            ['titre'=>'Panorama de l\'IA générative et grands modèles de langage','contenus'=>'Qu\'est-ce qu\'un LLM (Large Language Model) : fonctionnement simplifié, tokens, context window · Principaux modèles : ChatGPT (GPT-4o), Claude 3.5/4, Gemini 1.5, Mistral · Différences clés : raisonnement, fiabilité, coût, fenêtre de contexte, multimodalité · Utilisation via interface web vs API · Limites des LLMs : hallucinations, biais, date de coupure · Enjeux éthiques : droits d\'auteur, données personnelles, biais · Positionnement stratégique de l\'IA dans le contexte africain','duree'=>$s[0]],
            ['titre'=>'Prompt engineering : techniques fondamentales et avancées','contenus'=>'Structure d\'un prompt efficace : rôle, contexte, tâche, format, contraintes · Zero-shot, one-shot et few-shot prompting · Chain-of-thought (CoT) et prompting pas à pas · Prompts négatifs et contraintes de format · Itération et amélioration progressive des prompts · Techniques avancées : XML structuring, personas, meta-prompting · Bibliothèque de prompts réutilisables pour les cas d\'usage du bénéficiaire · Exercice : conception et optimisation de 10 prompts métier','duree'=>$s[1]],
            ['titre'=>'Applications métier : rédaction, analyse et productivité','contenus'=>'Rédaction professionnelle : emails, rapports, propositions commerciales, TDR · Résumé et extraction d\'information de documents longs · Analyse de données avec interpréteur de code (Advanced Data Analysis) · Génération de code et de formules Excel/SQL · Traduction et adaptation culturelle pour le marché africain · Recherche et veille : stratégies pour fiabiliser les résultats · Création de contenu marketing : posts, newsletters, scripts · Exercice : automatisation de 5 tâches récurrentes du bénéficiaire','duree'=>$s[2]],
            ['titre'=>'Outils IA spécialisés : image, voix, vidéo et automatisation','contenus'=>'Génération d\'images : DALL-E 3, Midjourney, Stable Diffusion · Génération vidéo : Sora, Runway, Pika · Clonage vocal et podcasts IA : ElevenLabs, NotebookLM · Transcription et résumé de réunions : Otter.ai, Fireflies · Automatisation no-code : Zapier AI, Make (Integromat), n8n · Outils de présentation IA : Gamma, Beautiful.ai · Exercice : construction d\'un workflow d\'automatisation avec Make + ChatGPT','duree'=>$s[3]],
            ['titre'=>'Intégration IA dans les processus organisationnels','contenus'=>'Audit de l\'organisation : identification des tâches automatisables par l\'IA · ROI de l\'IA : gain de temps, réduction des erreurs, valeur créée · Cas d\'usage par domaine : RH, finance, commercial, marketing, juridique · Résistances au changement et conduite de l\'adoption IA · Politique d\'utilisation de l\'IA en entreprise : règles, formation, gouvernance · Souveraineté des données : que ne pas confier à une IA externe · Veille technologique : comment suivre l\'évolution des outils IA · Plan de transformation par l\'IA de l\'organisation du bénéficiaire','duree'=>$s[4]],
            ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : conception d\'un workflow IA complet appliqué au domaine du bénéficiaire (prompts + outils + automatisation + plan d\'adoption) · Correction individualisée et feedback · Remise du certificat IBIG EDUFORM "IA Générative & Outils IA" et plan d\'intégration IA personnalisé','duree'=>$s[5]],
        ];
    }

    /* ══ Data Analyst RH ══ */
    if (preg_match('/data analyst\s*(rh|grh|ressources humaines|hr\b)?/ui', $nom)) {
        $h      = max(20, $heures);
        $eval_h = 2;                                        // évaluation finale = 2h fixes
        $dh     = max(3, (int)floor(($h - $eval_h) / 6)); // 6 modules de formation
        $extra  = ($h - $eval_h) - ($dh * 6);             // heures restantes → 1er module
        return [
            ['titre'=>'Fondamentaux de la Data RH : périmètre, enjeux et indicateurs clés','contenus'=>'Ce que recouvre le rôle de Data Analyst RH · Indicateurs stratégiques : turnover, absentéisme, masse salariale, pyramide des âges, index égalité F/H · Positionnement dans l\'organigramme RH et interfaces avec la DRH et la direction','duree'=>$dh + max(0,$extra)],
            ['titre'=>'Sources de données RH et architecture SIRH','contenus'=>'Cartographie des sources : paie, pointage, recrutement, formation, CNPS/CMU, DUE · Formats de données (CSV, Excel, SQL) · Évaluation de la qualité et de la cohérence des données · Connexion aux principaux SIRH utilisés en Afrique francophone','duree'=>$dh],
            ['titre'=>'Collecte, nettoyage et structuration des données RH','contenus'=>'Import et consolidation de données multi-sources · Nettoyage sous Excel et Power Query : doublons, valeurs manquantes, formats incohérents · Modélisation d\'un référentiel salarié · Automatisation des mises à jour mensuelles','duree'=>$dh],
            ['titre'=>'Analyse des indicateurs RH clés et détection de signaux','contenus'=>'Calcul et interprétation du taux de turnover, de l\'absentéisme, du coût de recrutement · Analyse de la masse salariale et des écarts de rémunération · Lecture des données CNPS, CMU et obligations sociales OHADA · Identification des signaux d\'alerte RH','duree'=>$dh],
            ['titre'=>'Visualisation et tableaux de bord RH sous Excel et Power BI','contenus'=>'Graphiques avancés sous Excel : sparklines, jauges, histogrammes dynamiques · Création de tableaux de bord RH interactifs sous Power BI · Mise en page et narration des données pour le CODIR · Export et diffusion sécurisée des rapports','duree'=>$dh],
            ['titre'=>'Mise en pratique : projet complet d\'analyse RH sur données réelles','contenus'=>'Traitement d\'un jeu de données RH réelles d\'une entreprise de l\'espace OHADA · Construction d\'un rapport d\'analyse complet (diagnostic + recommandations) · Corrections et plan d\'amélioration individuel fourni par le formateur','duree'=>$dh],
            ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : analyse d\'un cas RH inédit et production d\'un tableau de bord en temps limité · Correction individualisée et feedback détaillé du formateur · Remise du certificat IBIG EDUFORM "Data Analyst RH" et construction du plan de développement post-formation','duree'=>$eval_h],
        ];
    }

    /* ══ Modules spécifiques : Excel / Power BI / Tableaux de bord ══ */
    if (preg_match('/excel|power\s*bi|tableau.{0,10}bord|reporting|data.*visualis/ui', $nom)) {
        $h      = max(16, $heures);
        $eval_h = 2;
        $dh     = max(2, (int)floor(($h - $eval_h) / 5));
        $extra  = ($h - $eval_h) - ($dh * 5);
        return [
            ['titre'=>'Prise en main et environnement de travail','contenus'=>'Interface, raccourcis et ergonomie · Organisation des classeurs et feuilles · Mise en forme professionnelle · Paramètres d\'affichage et d\'impression','duree'=>$dh + max(0,$extra)],
            ['titre'=>'Fonctions avancées et traitement des données','contenus'=>'Fonctions RECHERCHEV, INDEX/EQUIV, SI imbriqués, SOMME.SI.ENS · Formules matricielles et fonctions texte · Nettoyage et transformation de données · Power Query : import et consolidation multi-sources','duree'=>$dh],
            ['titre'=>'Tableaux croisés dynamiques et analyse multidimensionnelle','contenus'=>'Création et configuration des TCD · Segments et chronologies · Champs calculés · Analyse des données RH, financières et commerciales','duree'=>$dh],
            ['titre'=>'Graphiques, visualisation et storytelling par les données','contenus'=>'Graphiques avancés : cascade, radar, bulles · Mise en forme conditionnelle avancée · Sparklines et mini-graphiques · Principes de data storytelling pour décideurs','duree'=>$dh],
            ['titre'=>'Tableaux de bord dynamiques et automatisation','contenus'=>'Architecture d\'un tableau de bord professionnel · Contrôles de formulaire et listes déroulantes · Liaisons entre feuilles et classeurs · Introduction aux macros VBA · Diffusion et protection du tableau de bord','duree'=>$dh],
            ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : conception d\'un tableau de bord complet sur un jeu de données inédit · Correction individualisée et feedback détaillé du formateur · Remise du certificat IBIG EDUFORM et plan d\'action individuel','duree'=>$eval_h],
        ];
    }

    /* Nombre de modules selon la durée */
    $nb_modules = 5;
    if ($heures >= 25) $nb_modules = 6;
    if ($heures >= 30) $nb_modules = 7;
    if ($heures >= 35) $nb_modules = 8;
    if ($heures >= 45) $nb_modules = 9;
    if ($heures >= 65) $nb_modules = 10;

    /* Extraction des vrais sujets depuis la description */
    $topics = _extract_topics_gen($desc);
    if (count($topics) < 2) {
        $n = preg_replace('/\s*\([^)]*\)/', '', $nom);
        $parts = preg_split('/\s*[&,]\s*|\s+[eé]t\s+/ui', $n);
        $topics = array_values(array_filter(array_map('trim', $parts), fn($t) => mb_strlen(trim($t), 'UTF-8') > 4));
    }

    /* Contenus rotatifs pour les modules intermédiaires */
    $contenus_sets = [
        'Documents et outils associés · Procédures et circuits de validation · Cas pratiques sur dossiers simulés et corrections commentées',
        'Réglementation en vigueur, obligations et délais à respecter · Erreurs fréquentes constatées en entreprise · Exercices pratiques avec mise en situation professionnelle',
        'Outils, logiciels et tableaux de suivi utilisés dans les entreprises · Organisation, archivage et traçabilité des dossiers · Retours d\'expérience d\'entreprises ivoiriennes et de l\'espace OHADA',
        'Principes clés, définitions et ce que le praticien doit maîtriser en priorité · Mise en pratique guidée sur cas réels issus du terrain africain · Points de contrôle, indicateurs de qualité et critères de conformité',
        'Procédures internes types et comment les adapter à son entreprise · Communication inter-services, reporting et présentation des résultats · Simulation complète avec correction et plan d\'amélioration individuel',
    ];

    /* Répartition horaire — évaluation finale = 2h fixes, reste réparti sur les autres modules */
    $heures_contenu = max(($nb_modules - 1) * 2, $heures - 2);
    $duree_base = max(2, (int)floor($heures_contenu / ($nb_modules - 1)));
    $extra = $heures_contenu - ($duree_base * ($nb_modules - 1));
    $give_extra = function() use (&$extra): int {
        if ($extra > 0) { $extra--; return 1; }
        return 0;
    };

    $modules = [];

    /* MODULE 1 — toujours une introduction spécifique */
    $modules[] = [
        'titre'    => $nom . ' : périmètre, enjeux et positionnement professionnel',
        'contenus' => 'Ce que recouvre exactement ce domaine et la responsabilité du praticien · Acteurs, textes de référence et pratiques du marché en Afrique francophone · Autodiagnostic et identification des axes prioritaires de progression',
        'duree'    => $duree_base + $give_extra(),
    ];

    /* Modules intermédiaires — titres issus de la description réelle */
    $idx = 2;
    $setIdx = 0;
    foreach ($topics as $topic) {
        if ($idx > $nb_modules - 2) break;
        $modules[] = [
            'titre'    => $topic,
            'contenus' => $contenus_sets[$setIdx % count($contenus_sets)],
            'duree'    => $duree_base + $give_extra(),
        ];
        $setIdx++;
        $idx++;
    }

    /* Compléter avec des fillers par catégorie si les topics ne suffisent pas */
    if ($idx <= $nb_modules - 2) {
        $fillers = _get_fillers_for_cat($cat, $nom);
        $fi = 0;
        while ($idx <= $nb_modules - 2) {
            $filler = $fillers[$fi % count($fillers)];
            $modules[] = [
                'titre'    => $filler['titre'],
                'contenus' => $filler['contenus'],
                'duree'    => $duree_base + $give_extra(),
            ];
            $fi++;
            $idx++;
        }
    }

    /* Avant-dernier module — ateliers pratiques */
    $modules[] = [
        'titre'    => 'Mise en pratique : cas d\'entreprises et travaux dirigés',
        'contenus' => 'Traitement de dossiers et scénarios tirés d\'entreprises réelles (Côte d\'Ivoire, Afrique de l\'Ouest) · Travaux individuels et en binôme avec correction commentée · Projet de synthèse : production d\'un livrable professionnel complet',
        'duree'    => $duree_base + $give_extra(),
    ];

    /* Dernier module — évaluation : toujours 2h */
    $modules[] = [
        'titre'    => 'Évaluation finale et remise du certificat',
        'contenus' => 'Épreuve d\'évaluation couvrant l\'ensemble du programme · Correction individualisée et feedback détaillé du formateur · Remise du certificat IBIG EDUFORM et construction du plan de développement post-formation',
        'duree'    => 2,
    ];

    /* Ajuster l'avant-dernier module (pratique) pour atteindre exactement $heures */
    $total = array_sum(array_column($modules, 'duree'));
    if ($total !== $heures && count($modules) >= 2) {
        $idx_pratique = count($modules) - 2;
        $modules[$idx_pratique]['duree'] = max(2, $modules[$idx_pratique]['duree'] + ($heures - $total));
    }

    return $modules;
}

/* ══════════════════════════════════════════════════════════
   TEMPLATES PAR CATÉGORIE
══════════════════════════════════════════════════════════ */
function tdr_templates(): array
{
    $t = [];

    /* ── Management & Leadership ── */
    $t['Management & Leadership'] = [
        'contexte' => '<p>La performance d\'une organisation repose moins sur la qualité technique de ses collaborateurs que sur la capacité de ses managers à les fédérer, à les orienter et à les faire progresser. Or, l\'accès aux fonctions d\'encadrement se fait le plus souvent par promotion technique : on nomme manager le meilleur spécialiste, sans lui transmettre les compétences relationnelles, organisationnelles et décisionnelles que la fonction exige.</p>
<p>Cette transition mal accompagnée produit des coûts invisibles mais bien réels : démotivation des collaborateurs, conflits non traités, décisions différées, retards de livrables, perte de crédibilité du manager auprès de son équipe. À l\'inverse, un encadrant outillé transforme rapidement la dynamique de son équipe — et cette transformation est mesurable.</p>
<p>Le présent programme "{NOM}" est conçu pour accompagner individuellement le bénéficiaire dans l\'acquisition et la mise en pratique des compétences managériales essentielles, directement appliquées à ses situations professionnelles réelles.</p>',
        'objectif_general' => 'Doter le bénéficiaire d\'un socle complet de compétences managériales lui permettant de piloter ses équipes de manière structurée, de mobiliser durablement ses collaborateurs et d\'atteindre les objectifs de performance qui lui sont assignés.',
        'objectifs_specifiques' => [
            'Clarifier son rôle, son périmètre de responsabilité et sa posture de manager ;',
            'Identifier son style de management dominant et l\'adapter au niveau d\'autonomie de chaque collaborateur ;',
            'Fixer des objectifs clairs, mesurables et négociés, puis en assurer le suivi ;',
            'Organiser le travail de son équipe, prioriser les activités et déléguer efficacement ;',
            'Conduire les entretiens managériaux clés : cadrage, feedback, recadrage, évaluation ;',
            'Animer des réunions d\'équipe utiles, courtes et orientées décision ;',
            'Détecter, prévenir et traiter les conflits et les situations difficiles ;',
            'Développer la motivation, la cohésion et l\'engagement de son équipe ;',
            'Piloter son activité à l\'aide d\'indicateurs simples et d\'un tableau de bord managérial.',
        ],
        'public_cible' => 'Le parcours s\'adresse à tout manager, chef d\'équipe, superviseur, responsable de service, chef de projet ou entrepreneur encadrant du personnel, ainsi qu\'à tout collaborateur récemment promu ou en préparation d\'une prise de fonction managériale.',
        'prerequis' => 'Aucun prérequis académique n\'est exigé. Une expérience, même courte, en situation d\'encadrement ou de coordination d\'activité est souhaitée afin de nourrir les mises en situation avec des cas réels.',
        'methodologie' => [
            'Diagnostic préalable : questionnaire de positionnement et entretien de cadrage d\'une heure pour contextualiser l\'ensemble du parcours ;',
            'Séances synchrones en visioconférence : échange direct et continu avec le formateur, sans concurrence d\'attention ;',
            'Études de cas personnalisées : chaque module est illustré par des situations vécues par le bénéficiaire dans son environnement de travail ;',
            'Jeux de rôle en direct : entretiens de recadrage, de feedback et d\'évaluation simulés avec le formateur, suivis d\'un débriefing immédiat ;',
            'Autodiagnostics : style de management, gestion du temps, posture face au conflit, intelligence relationnelle ;',
            'Travaux intersessions : entre deux séances, une mise en pratique concrète est confiée au bénéficiaire, puis analysée à la séance suivante ;',
            'Boîte à outils numérique : trames, grilles et modèles prêts à l\'emploi, personnalisés à son contexte ;',
            'Plan d\'action individuel : formalisation de trois engagements concrets à mettre en œuvre dans les 30 jours suivant la clôture.',
        ],
        'debouches' => 'Ce parcours ouvre des perspectives d\'évolution vers des postes de direction d\'équipe, de département ou de division. Il permet également de renforcer son positionnement en tant que manager reconnu et efficace au sein de son organisation, ou de développer une activité de conseil en management.',
    ];

    /* ── GRH ── */
    $t['GRH'] = [
        'contexte' => '<p>La gestion des ressources humaines constitue l\'un des piliers stratégiques de la performance organisationnelle. Dans l\'espace OHADA, les entreprises font face à des défis croissants : évolution du cadre juridique du travail, attentes nouvelles des collaborateurs, nécessité de fidéliser les talents dans un marché compétitif.</p>
<p>Les professionnels RH doivent aujourd\'hui maîtriser un spectre de compétences très large : droit du travail, gestion administrative, recrutement, formation, évaluation et pilotage stratégique. Le programme "{NOM}" répond à ce besoin en offrant un accompagnement individualisé, ancré dans les réalités de l\'entreprise africaine.</p>',
        'objectif_general' => 'Permettre au bénéficiaire de maîtriser les outils et méthodes de la gestion des ressources humaines, en conformité avec le cadre légal OHADA, afin de contribuer efficacement à la performance et à l\'attractivité de son organisation.',
        'objectifs_specifiques' => [
            'Maîtriser le cadre juridique du travail applicable dans l\'espace OHADA ;',
            'Concevoir et mettre en œuvre un processus de recrutement structuré et efficace ;',
            'Gérer l\'administration du personnel et produire les documents réglementaires ;',
            'Établir et contrôler les bulletins de paie dans le respect des obligations légales ;',
            'Construire et piloter un plan de formation aligné sur les besoins organisationnels ;',
            'Conduire des entretiens d\'évaluation professionnelle et de développement ;',
            'Mettre en place des outils de suivi RH et des tableaux de bord pertinents ;',
            'Gérer les conflits individuels et collectifs dans le respect des procédures légales.',
        ],
        'public_cible' => 'Ce parcours s\'adresse aux responsables et assistants RH, aux directeurs administratifs en charge du personnel, aux chefs d\'entreprise souhaitant structurer leur GRH, ainsi qu\'à toute personne amenée à prendre en charge une fonction RH.',
        'prerequis' => 'Niveau Bac+2 minimum ou expérience équivalente en administration ou gestion. Une première expérience en entreprise est souhaitée.',
        'methodologie' => [
            'Diagnostic préalable sur les pratiques RH actuelles du bénéficiaire et identification des axes prioritaires ;',
            'Études de cas réels tirés de l\'environnement professionnel du bénéficiaire ;',
            'Modèles et outils opérationnels (contrats types, fiches de poste, grilles d\'évaluation, bulletins de paie) ;',
            'Travaux intersessions : application immédiate sur des documents ou situations réels ;',
            'Boîte à outils RH complète : tous les modèles et guides pratiques OHADA ;',
            'Plan d\'action individuel pour la structuration de la fonction RH dans l\'organisation du bénéficiaire.',
        ],
        'debouches' => 'Ce parcours ouvre des perspectives vers les postes de Responsable RH, DRH adjoint, Chargé de recrutement et de formation, ou Consultant RH indépendant dans l\'espace OHADA.',
    ];

    /* ── Comptabilité & Finance ── */
    $t['Comptabilité & Finance'] = [
        'contexte' => '<p>La maîtrise des finances constitue le socle de toute décision de gestion éclairée. Dans l\'espace OHADA, le SYSCOHADA révisé impose aux entreprises une rigueur comptable et fiscale croissante. Pourtant, de nombreux professionnels exercent leurs fonctions avec des lacunes qui exposent leur organisation à des risques fiscaux, financiers et réputationnels.</p>
<p>Le programme "{NOM}" est conçu pour combler ces lacunes de manière ciblée et pratique, en partant des situations réelles du bénéficiaire pour développer une maîtrise opérationnelle immédiatement applicable.</p>',
        'objectif_general' => 'Doter le bénéficiaire des compétences comptables, fiscales et financières nécessaires à la tenue rigoureuse des comptes, à l\'analyse de la performance financière et à la prise de décisions de gestion fondées sur des données fiables.',
        'objectifs_specifiques' => [
            'Maîtriser les principes fondamentaux du SYSCOHADA révisé et leur application pratique ;',
            'Enregistrer correctement les opérations courantes et les opérations complexes ;',
            'Établir les états financiers annuels conformes aux normes OHADA ;',
            'Appliquer les règles fiscales en vigueur (TVA, IS, IRVM, impôt sur les traitements) ;',
            'Produire les déclarations fiscales dans les délais légaux ;',
            'Analyser la situation financière d\'une entreprise à partir des états financiers ;',
            'Concevoir et suivre un budget de trésorerie ;',
            'Mettre en place un tableau de bord de gestion adapté à l\'organisation.',
        ],
        'public_cible' => 'Ce parcours s\'adresse aux comptables, gestionnaires financiers, directeurs administratifs et financiers, chefs d\'entreprise et toute personne amenée à traiter des données financières ou à superviser une fonction comptable.',
        'prerequis' => 'Notions de base en comptabilité ou en gestion. Un niveau Bac ou équivalent est recommandé.',
        'methodologie' => [
            'Exercices pratiques sur des pièces comptables et des situations fiscales réelles ;',
            'Utilisation des outils bureautiques (tableurs) et des logiciels de comptabilité ;',
            'Études de cas d\'entreprises de l\'espace OHADA ;',
            'Correction détaillée et feedback personnalisé sur chaque travail intersession ;',
            'Modèles de déclarations fiscales et d\'états financiers fournis et commentés ;',
            'Plan d\'action pour la mise en conformité comptable et fiscale de l\'organisation du bénéficiaire.',
        ],
        'debouches' => 'Ce parcours ouvre des perspectives vers les postes de Comptable confirmé, Responsable financier, Contrôleur de gestion, Expert-comptable stagiaire ou Consultant en gestion financière.',
    ];

    /* ── Informatique & Tech ── */
    $t['Informatique & Tech'] = [
        'contexte' => '<p>La transformation numérique modifie en profondeur tous les secteurs d\'activité. Les professionnels qui maîtrisent les outils et langages du numérique disposent d\'un avantage concurrentiel décisif sur le marché du travail africain. Pourtant, l\'accès à des formations de qualité, ancrées dans des pratiques professionnelles réelles, reste limité.</p>
<p>Le programme "{NOM}" répond à cette demande en proposant un accompagnement individualisé, centré sur la pratique, qui permet au bénéficiaire d\'acquérir rapidement des compétences opérationnelles et vérifiables.</p>',
        'objectif_general' => 'Permettre au bénéficiaire d\'acquérir une maîtrise technique et pratique dans le domaine "{NOM}", directement applicable dans son environnement professionnel, et sanctionnée par une certification reconnue.',
        'objectifs_specifiques' => [
            'Comprendre les fondamentaux et l\'architecture du domaine technique abordé ;',
            'Installer, configurer et utiliser les principaux outils et environnements de travail ;',
            'Concevoir et développer des solutions adaptées aux besoins réels identifiés ;',
            'Appliquer les bonnes pratiques de sécurité, de performance et de maintenabilité ;',
            'Réaliser des projets concrets de bout en bout, de la conception à la livraison ;',
            'Diagnostiquer et résoudre des problèmes techniques courants ;',
            'Documenter ses réalisations et communiquer efficacement sur ses travaux ;',
            'Se positionner sur le marché du travail numérique avec un portfolio de réalisations.',
        ],
        'public_cible' => 'Ce parcours s\'adresse aux étudiants en informatique ou en sciences, aux professionnels souhaitant se reconvertir vers le numérique, aux développeurs juniors souhaitant se spécialiser, et à tout professionnel dont l\'activité nécessite des compétences techniques avancées.',
        'prerequis' => 'Aisance avec l\'outil informatique. Une première expérience ou des bases dans le domaine sont souhaitées mais non obligatoires selon le niveau du parcours.',
        'methodologie' => [
            'Apprentissage par la pratique : chaque concept est immédiatement mis en œuvre dans un projet réel ;',
            'Environnement de travail professionnel mis en place dès la première séance ;',
            'Code review et retour détaillé du formateur sur chaque réalisation ;',
            'Projets progressifs : du cas simple à l\'application complexe ;',
            'Accès à une bibliothèque de ressources (documentation, tutoriels, exemples) ;',
            'Constitution d\'un portfolio de réalisations documentées valorisables auprès d\'employeurs ou clients.',
        ],
        'debouches' => 'Ce parcours ouvre des perspectives vers des postes de développeur, intégrateur, administrateur système ou réseau, consultant IT, ou vers une activité freelance dans le domaine du numérique.',
    ];

    /* ── Gestion Commerciale & Marketing ── */
    $t['Gestion Commerciale & Marketing'] = [
        'contexte' => '<p>Dans un contexte de concurrence accrue et de digitalisation des marchés, la maîtrise des techniques commerciales et marketing est devenue indispensable pour toute organisation souhaitant développer son chiffre d\'affaires et fidéliser sa clientèle. Les professionnels du commerce et du marketing doivent aujourd\'hui conjuguer techniques de vente éprouvées et maîtrise des outils digitaux.</p>
<p>Le programme "{NOM}" offre un accompagnement personnalisé ancré dans les réalités du marché africain, pour développer une compétence commerciale immédiatement opérationnelle.</p>',
        'objectif_general' => 'Permettre au bénéficiaire de maîtriser les techniques commerciales et marketing essentielles pour développer un portefeuille clients, atteindre ses objectifs de vente et contribuer à la croissance de son organisation.',
        'objectifs_specifiques' => [
            'Comprendre les mécanismes du marché et analyser son environnement concurrentiel ;',
            'Identifier, cibler et qualifier des prospects à fort potentiel ;',
            'Construire et délivrer un pitch commercial percutant et adapté ;',
            'Maîtriser les techniques de négociation et de traitement des objections ;',
            'Fidéliser sa clientèle et développer une relation durable et rentable ;',
            'Utiliser les outils CRM pour piloter son activité commerciale ;',
            'Concevoir et mettre en œuvre des actions marketing adaptées à son marché ;',
            'Analyser ses résultats commerciaux et ajuster sa stratégie.',
        ],
        'public_cible' => 'Ce parcours s\'adresse aux commerciaux, chargés de clientèle, responsables commerciaux, entrepreneurs, chefs d\'entreprise et tout professionnel amené à développer une activité commerciale.',
        'prerequis' => 'Aucun prérequis technique. Une première expérience dans une fonction en contact avec la clientèle est souhaitée.',
        'methodologie' => [
            'Jeux de rôle et simulations d\'entretiens de vente avec débriefing immédiat ;',
            'Analyse de situations commerciales réelles vécues par le bénéficiaire ;',
            'Construction d\'outils personnalisés : pitch, argumentaire, scripts téléphoniques ;',
            'Travaux intersessions : application terrain avec restitution et analyse à la séance suivante ;',
            'Boîte à outils commerciale : modèles de propositions, scripts, grilles de qualification ;',
            'Plan d\'action commercial individuel formalisé et engagé.',
        ],
        'debouches' => 'Ce parcours ouvre des perspectives vers les postes de Commercial senior, Responsable commercial, Directeur des ventes, Chargé de développement ou Consultant marketing indépendant.',
    ];

    /* ── Entrepreneuriat ── */
    $t['Entrepreneuriat'] = [
        'contexte' => '<p>L\'Afrique connaît une dynamique entrepreneuriale sans précédent. Pourtant, la majorité des nouvelles entreprises échouent dans les trois premières années, faute d\'une préparation suffisante, d\'une maîtrise des outils de gestion et d\'un accompagnement adapté. L\'idée ne suffit pas : ce qui fait la différence, c\'est l\'exécution.</p>
<p>Le programme "{NOM}" accompagne le porteur de projet ou l\'entrepreneur en activité dans la structuration rigoureuse de son projet ou de son entreprise, de la validation de l\'idée au lancement opérationnel.</p>',
        'objectif_general' => 'Permettre au bénéficiaire de structurer, valider et lancer son projet entrepreneurial ou de renforcer son entreprise existante, en maîtrisant les outils essentiels de la création, de la gestion et du développement d\'activité.',
        'objectifs_specifiques' => [
            'Valider la viabilité de son idée ou de son modèle d\'affaires par une étude de marché ciblée ;',
            'Construire un business model solide et un business plan convaincant ;',
            'Identifier et approcher les sources de financement adaptées à son projet ;',
            'Mettre en place les structures juridiques et administratives adaptées ;',
            'Développer une stratégie commerciale et marketing pour acquérir ses premiers clients ;',
            'Gérer la trésorerie et les finances de son activité avec rigueur ;',
            'Constituer et animer une équipe de démarrage performante ;',
            'Piloter son activité grâce à des indicateurs de performance pertinents.',
        ],
        'public_cible' => 'Ce parcours s\'adresse aux porteurs de projet en phase d\'idéation ou de lancement, aux entrepreneurs en activité souhaitant structurer leur développement, et aux professionnels souhaitant créer leur propre activité.',
        'prerequis' => 'Avoir une idée de projet ou une activité en cours. Aucun prérequis académique particulier.',
        'methodologie' => [
            'Travail directement sur le projet réel du bénéficiaire : chaque séance produit des livrables concrets ;',
            'Outils Business Model Canvas, Lean Startup, étude de marché adaptés au contexte africain ;',
            'Mise en situation face à des investisseurs ou partenaires simulés ;',
            'Réseau IBIG : mise en relation avec des partenaires, experts et financeurs si pertinent ;',
            'Boîte à outils entrepreneuriale : business plan type, tableau de trésorerie, contrats de base ;',
            'Plan de lancement opérationnel formalisé et engagé à la clôture du parcours.',
        ],
        'debouches' => 'Ce parcours conduit au lancement ou à la consolidation d\'une entreprise viable, à l\'obtention de financements, ou à l\'intégration de programmes d\'accélération et d\'incubation.',
    ];

    /* ── Agriculture ── */
    $t['Agriculture'] = [
        'contexte' => '<p>Le secteur agricole représente un pilier fondamental des économies de l\'espace OHADA, contribuant significativement au PIB et à l\'emploi. Pourtant, la modernisation des pratiques agricoles, la maîtrise des chaînes de valeur et l\'accès aux marchés restent des défis majeurs pour les acteurs du secteur.</p>
<p>Le programme "{NOM}" est conçu pour apporter au bénéficiaire les compétences techniques et de gestion nécessaires à la professionnalisation de son activité agricole, de la production à la commercialisation.</p>',
        'objectif_general' => 'Permettre au bénéficiaire de maîtriser les techniques et les outils de gestion nécessaires au développement d\'une activité agricole rentable, durable et compétitive dans l\'espace OHADA.',
        'objectifs_specifiques' => [
            'Maîtriser les techniques de production agricole adaptées au contexte local ;',
            'Gérer financièrement une exploitation agricole : coûts, rentabilité, trésorerie ;',
            'Accéder aux marchés locaux, régionaux et d\'exportation ;',
            'Appliquer les normes de qualité et de sécurité alimentaire en vigueur ;',
            'Développer une stratégie de commercialisation adaptée aux produits agricoles ;',
            'Mobiliser les financements disponibles pour l\'agriculture (microfinance, subventions) ;',
            'Gérer les risques climatiques et sanitaires liés à l\'activité agricole ;',
            'Structurer une coopérative ou une organisation de producteurs.',
        ],
        'public_cible' => 'Ce parcours s\'adresse aux agriculteurs et agro-entrepreneurs, aux gérants de coopératives agricoles, aux chargés de projets agricoles dans des ONG ou des institutions, et aux professionnels souhaitant investir dans l\'agroalimentaire.',
        'prerequis' => 'Une expérience dans le secteur agricole ou agroalimentaire est souhaitée. Aucun prérequis académique particulier.',
        'methodologie' => [
            'Travail sur des situations et des exploitations réelles présentées par le bénéficiaire ;',
            'Outils de gestion agricole adaptés au contexte des pays OHADA ;',
            'Études de cas de filières agricoles performantes en Afrique de l\'Ouest ;',
            'Modèles de business plan agricole, de budget d\'exploitation et de plans de financement ;',
            'Mise en relation avec des partenaires techniques et financiers du secteur agricole si pertinent.',
        ],
        'debouches' => 'Ce parcours ouvre des perspectives de professionnalisation de l\'exploitation agricole, d\'accès à des marchés d\'exportation, de création de coopératives, ou d\'intégration à des projets de développement agricole.',
    ];

    /* ── IA & Digitalisation ── */
    $t['IA & Digitalisation'] = [
        'contexte' => '<p>L\'intelligence artificielle et la transformation numérique redéfinissent les règles du jeu dans tous les secteurs d\'activité. Les organisations qui intègrent ces technologies dans leurs processus gagnent en productivité, en compétitivité et en capacité d\'innovation. Celles qui n\'agissent pas prennent le risque d\'être marginalisées.</p>
<p>Le programme "{NOM}" permet au bénéficiaire de comprendre et d\'exploiter concrètement les outils de l\'IA et du numérique dans son contexte professionnel, sans nécessiter de background technique préalable.</p>',
        'objectif_general' => 'Permettre au bénéficiaire de comprendre les enjeux de l\'IA et de la digitalisation, d\'identifier les opportunités dans son secteur et de mettre en œuvre des outils numériques concrets pour améliorer la performance de son organisation.',
        'objectifs_specifiques' => [
            'Comprendre les fondements de l\'intelligence artificielle et ses applications métiers ;',
            'Identifier les opportunités de digitalisation dans son secteur d\'activité ;',
            'Utiliser les outils d\'IA générative (ChatGPT, Copilot, etc.) dans un contexte professionnel ;',
            'Automatiser des tâches répétitives grâce aux outils no-code et low-code ;',
            'Concevoir et piloter un projet de transformation numérique ;',
            'Gérer les données de son organisation et en tirer des insights décisionnels ;',
            'Appréhender les enjeux de cybersécurité liés à la digitalisation ;',
            'Élaborer une feuille de route de digitalisation adaptée à son organisation.',
        ],
        'public_cible' => 'Ce parcours s\'adresse aux dirigeants, managers, responsables de services et professionnels souhaitant comprendre et exploiter l\'IA et le numérique dans leur activité, sans prérequis technique avancé.',
        'prerequis' => 'Aisance avec l\'outil informatique de base. Aucune compétence en programmation requise.',
        'methodologie' => [
            'Approche 100 % pratique : chaque outil est testé et appliqué au contexte réel du bénéficiaire ;',
            'Démonstrations live des outils d\'IA avec mise en pratique guidée ;',
            'Cas d\'usage concrets tirés du secteur d\'activité du bénéficiaire ;',
            'Travaux intersessions : mise en œuvre d\'au moins un outil par semaine ;',
            'Veille technologique partagée : les dernières innovations présentées et évaluées ;',
            'Plan de digitalisation personnalisé formalisé et priorisé.',
        ],
        'debouches' => 'Ce parcours ouvre des perspectives vers les fonctions de responsable de la transformation digitale, chef de projet numérique, consultant en digitalisation, ou entrepreneur dans le secteur des technologies.',
    ];

    /* ── Claude (Anthropic) ── */
    $t['Claude (Anthropic)'] = [
        'contexte' => '<p>Claude, développé par Anthropic, est l\'un des assistants IA les plus avancés du marché mondial. Reconnu pour la qualité de son raisonnement, la richesse de ses réponses et sa fiabilité professionnelle, Claude se distingue par une fenêtre de contexte étendue permettant l\'analyse de documents volumineux, une capacité de rédaction longue sans perte de cohérence, et une conception orientée sécurité et alignement.</p>
<p>Le programme "{NOM}" permet au bénéficiaire de maîtriser Claude de manière opérationnelle : de la prise en main de l\'interface aux usages avancés (Projects, Artefacts, API, MCP), en passant par le prompt engineering spécifique à ce modèle et les cas d\'usage métier adaptés au contexte africain.</p>',
        'objectif_general' => 'Permettre au bénéficiaire de maîtriser Claude d\'Anthropic dans son contexte professionnel : comprendre ses spécificités par rapport aux autres LLM, développer des prompts efficaces, exploiter ses fonctionnalités avancées et l\'intégrer dans des workflows métier concrets.',
        'objectifs_specifiques' => [
            'Comprendre l\'architecture et les principes de fonctionnement de Claude (Anthropic) ;',
            'Comparer Claude avec les autres grands modèles (ChatGPT, Gemini, Mistral) et choisir le bon outil selon le contexte ;',
            'Maîtriser les techniques de prompt engineering spécifiques à Claude : XML structuring, chain-of-thought, few-shot ;',
            'Exploiter la fenêtre de contexte étendue de Claude pour l\'analyse de documents longs (contrats, rapports, études) ;',
            'Utiliser Claude Projects pour organiser ses travaux et maintenir un contexte persistent ;',
            'Créer des Artefacts Claude : tableaux, visualisations, mini-applications et dashboards ;',
            'Intégrer Claude dans des workflows professionnels via Make, Zapier ou le MCP ;',
            'Appliquer Claude à son domaine métier spécifique avec des cas pratiques réels.',
        ],
        'public_cible' => 'Ce parcours s\'adresse aux professionnels de tous secteurs souhaitant intégrer Claude d\'Anthropic dans leur pratique quotidienne : managers, consultants, juristes, financiers, RH, marketeurs, enseignants, et tout professionnel désireux de démultiplier sa productivité grâce à l\'IA.',
        'prerequis' => 'Aisance avec l\'outil informatique (navigation web, traitement de texte). Aucune compétence en programmation requise. Une familiarité basique avec les assistants IA (ChatGPT ou équivalent) est un plus.',
        'methodologie' => [
            'Apprentissage par la pratique : chaque fonctionnalité de Claude testée en conditions réelles sur des documents et cas du bénéficiaire ;',
            'Ateliers de prompt engineering : rédaction, test et optimisation de prompts sur des tâches professionnelles concrètes ;',
            'Bibliothèque de prompts sectoriels fournie et commentée (finance, juridique, RH, marketing, etc.) ;',
            'Mise en situation : analyse de documents réels (contrats, rapports financiers, études de marché) avec Claude ;',
            'Exercices de création d\'Artefacts et de workflows automatisés avec Make/Zapier ;',
            'Plan d\'intégration personnalisé : feuille de route d\'adoption de Claude adaptée au contexte du bénéficiaire.',
        ],
        'debouches' => 'Ce parcours ouvre des perspectives vers des rôles de référent IA en entreprise, consultant en transformation par l\'IA, formateur interne sur les outils d\'IA générative, ou entrepreneur exploitant les possibilités offertes par Claude pour créer de nouveaux services à valeur ajoutée.',
    ];

    /* ── Droit & Juridique ── */
    $t['Droit & Juridique'] = [
        'contexte' => '<p>La maîtrise du cadre juridique est un impératif pour toute organisation opérant dans l\'espace OHADA. Les risques juridiques sont omniprésents : contrats mal rédigés, litiges commerciaux, non-conformité réglementaire, conflits de travail. Les conséquences d\'une méconnaissance du droit peuvent être considérables.</p>
<p>Le programme "{NOM}" apporte au bénéficiaire les connaissances juridiques opérationnelles nécessaires pour sécuriser son activité et prendre des décisions éclairées dans son environnement professionnel.</p>',
        'objectif_general' => 'Permettre au bénéficiaire de maîtriser les fondamentaux juridiques applicables à son activité dans l\'espace OHADA, afin de prévenir les risques légaux, sécuriser ses contrats et prendre des décisions éclairées.',
        'objectifs_specifiques' => [
            'Comprendre l\'organisation judiciaire et les sources du droit dans l\'espace OHADA ;',
            'Rédiger et analyser les contrats commerciaux courants avec rigueur ;',
            'Appliquer le droit OHADA des sociétés dans la gestion courante ;',
            'Maîtriser les bases du droit du travail et prévenir les contentieux sociaux ;',
            'Gérer les litiges commerciaux et les voies de règlement des différends ;',
            'Identifier et prévenir les principaux risques juridiques de son activité ;',
            'Collaborer efficacement avec des conseils juridiques ;',
            'Mettre en place une veille juridique adaptée à son secteur.',
        ],
        'public_cible' => 'Ce parcours s\'adresse aux dirigeants, responsables administratifs, juristes d\'entreprise, responsables RH, commerciaux et tout professionnel exposé aux risques juridiques dans son activité.',
        'prerequis' => 'Aucun prérequis juridique. Un niveau Bac+2 ou une expérience professionnelle significative est recommandé.',
        'methodologie' => [
            'Analyse de contrats et de cas jurisprudentiels réels de l\'espace OHADA ;',
            'Exercices de rédaction contractuelle avec correction détaillée ;',
            'Mise en situation : simulation de litiges et de négociations amiables ;',
            'Modèles de contrats types adaptés à l\'environnement OHADA fournis et commentés ;',
            'Veille réglementaire sur les évolutions du droit OHADA.',
        ],
        'debouches' => 'Ce parcours ouvre des perspectives vers les fonctions de juriste d\'entreprise, compliance officer, responsable juridique, ou vers une spécialisation en droit des affaires OHADA.',
    ];

    /* ── Développement Personnel ── */
    $t['Développement Personnel'] = [
        'contexte' => '<p>Les compétences techniques ne suffisent plus pour réussir dans un environnement professionnel en constante évolution. Les soft skills — intelligence émotionnelle, communication, gestion du stress, leadership personnel — constituent des facteurs différenciants décisifs. Pourtant, ces compétences s\'acquièrent rarement dans un cadre formel.</p>
<p>Le programme "{NOM}" offre un espace d\'introspection et de pratique guidée pour que le bénéficiaire développe les compétences comportementales qui transformeront son efficacité professionnelle et sa qualité de vie au travail.</p>',
        'objectif_general' => 'Permettre au bénéficiaire de développer ses compétences comportementales et relationnelles essentielles pour améliorer son efficacité professionnelle, renforcer sa confiance en lui et atteindre ses objectifs personnels et professionnels.',
        'objectifs_specifiques' => [
            'Identifier ses forces, ses axes de développement et son style comportemental ;',
            'Développer sa confiance en soi et son assertivité dans les interactions professionnelles ;',
            'Gérer ses émotions et son stress dans les situations de pression ;',
            'Communiquer avec clarté et impact dans les contextes professionnels variés ;',
            'Établir des objectifs personnels et professionnels SMART et les atteindre ;',
            'Développer sa capacité d\'écoute active et d\'empathie ;',
            'Gérer efficacement son temps et ses priorités ;',
            'Construire et entretenir un réseau professionnel de qualité.',
        ],
        'public_cible' => 'Ce parcours s\'adresse à tout professionnel souhaitant améliorer son efficacité personnelle, sa communication, sa gestion du stress ou son positionnement professionnel.',
        'prerequis' => 'Aucun prérequis. L\'ouverture d\'esprit et la volonté de progresser sont les seuls pré-requis.',
        'methodologie' => [
            'Introspection guidée et autodiagnostics validés scientifiquement ;',
            'Exercices comportementaux avec feedback bienveillant du formateur-coach ;',
            'Jeux de rôle et simulations de situations professionnelles réelles ;',
            'Techniques de pleine conscience et de gestion du stress appliquées ;',
            'Journal de bord personnel pour suivre les progrès entre les séances ;',
            'Plan de développement personnel formalisé avec engagements concrets.',
        ],
        'debouches' => 'Ce parcours impacte positivement toutes les dimensions de la carrière professionnelle : prise de responsabilités, amélioration des relations au travail, confiance dans les prises de parole, et épanouissement professionnel global.',
    ];

    /* ── Logistique & Supply Chain ── */
    $t['Logistique & Supply Chain'] = [
        'contexte' => '<p>La logistique et la gestion de la chaîne d\'approvisionnement représentent un enjeu stratégique majeur pour la compétitivité des entreprises dans l\'espace OHADA. Des délais maîtrisés, des coûts logistiques optimisés et une chaîne d\'approvisionnement résiliente constituent des avantages concurrentiels durables.</p>
<p>Le programme "{NOM}" apporte au bénéficiaire les compétences opérationnelles nécessaires pour piloter efficacement les flux physiques et informationnels de son organisation.</p>',
        'objectif_general' => 'Permettre au bénéficiaire de maîtriser les concepts, outils et méthodes de la logistique et du supply chain management pour optimiser les flux de son organisation et réduire les coûts opérationnels.',
        'objectifs_specifiques' => [
            'Comprendre les enjeux et les composantes d\'une chaîne logistique globale ;',
            'Gérer les stocks avec rigueur : méthodes d\'approvisionnement et de valorisation ;',
            'Optimiser les flux de transport et de distribution ;',
            'Maîtriser les procédures douanières et les incoterms dans le commerce international ;',
            'Utiliser les outils informatiques de gestion logistique (WMS, TMS, ERP) ;',
            'Gérer les relations avec les fournisseurs et les prestataires logistiques ;',
            'Mettre en place des indicateurs de performance logistique ;',
            'Développer une supply chain résiliente et durable.',
        ],
        'public_cible' => 'Ce parcours s\'adresse aux responsables logistiques, gestionnaires de stocks, acheteurs, responsables import/export et tout professionnel impliqué dans la chaîne d\'approvisionnement.',
        'prerequis' => 'Une expérience dans une fonction logistique ou supply chain est souhaitée. Niveau Bac+2 minimum.',
        'methodologie' => [
            'Cas pratiques tirés de situations logistiques réelles du bénéficiaire ;',
            'Exercices de calcul de stocks, de coûts logistiques et d\'optimisation des flux ;',
            'Simulation de processus d\'import/export avec documents réels ;',
            'Boîte à outils logistique : modèles de cahier des charges transporteur, grilles de cotation fournisseur ;',
            'Plan d\'optimisation logistique personnalisé pour l\'organisation du bénéficiaire.',
        ],
        'debouches' => 'Ce parcours ouvre des perspectives vers les postes de Responsable logistique, Supply chain manager, Responsable achats, Transit manager ou Consultant en logistique.',
    ];

    /* ── QHSE ── */
    $t['QHSE'] = [
        'contexte' => '<p>Les exigences en matière de qualité, d\'hygiène, de sécurité et d\'environnement ne cessent de croître dans l\'espace OHADA, sous l\'effet des réglementations nationales, des exigences des donneurs d\'ordre et des attentes des parties prenantes. Les organisations qui intègrent une démarche QHSE rigoureuse réduisent leurs risques, améliorent leur performance et renforcent leur crédibilité.</p>
<p>Le programme "{NOM}" apporte au bénéficiaire les compétences techniques et méthodologiques pour déployer et piloter un système de management QHSE efficace.</p>',
        'objectif_general' => 'Permettre au bénéficiaire de concevoir, mettre en œuvre et piloter un système de management QHSE conforme aux normes internationales et adapté aux réalités de son organisation.',
        'objectifs_specifiques' => [
            'Comprendre les référentiels QHSE applicables (ISO 9001, ISO 14001, ISO 45001, HACCP) ;',
            'Réaliser un diagnostic QHSE de son organisation et identifier les écarts ;',
            'Concevoir et déployer des procédures et des instructions de travail adaptées ;',
            'Mettre en place un système d\'identification et d\'évaluation des risques ;',
            'Former et sensibiliser les équipes aux exigences QHSE ;',
            'Gérer les non-conformités, les incidents et les accidents ;',
            'Préparer et piloter des audits QHSE internes et externes ;',
            'Construire et suivre des indicateurs de performance QHSE.',
        ],
        'public_cible' => 'Ce parcours s\'adresse aux responsables QHSE, animateurs sécurité, responsables qualité, directeurs de production et tout professionnel en charge de la gestion des risques dans son organisation.',
        'prerequis' => 'Une expérience professionnelle dans un secteur industriel ou de services est souhaitée.',
        'methodologie' => [
            'Réalisation d\'un vrai diagnostic QHSE de l\'organisation du bénéficiaire en début de parcours ;',
            'Construction de documents QHSE réels : procédures, fiches de risques, plans d\'action ;',
            'Simulation d\'audits avec jeux de rôle formateur/audité ;',
            'Cas d\'accidents du travail et de non-conformités analysés et traités ;',
            'Boîte à outils QHSE : modèles de documents conformes aux normes ISO.',
        ],
        'debouches' => 'Ce parcours ouvre des perspectives vers les postes de Responsable QHSE, Auditeur qualité interne, Consultant QHSE, Responsable de la conformité réglementaire.',
    ];

    /* ── Banque & Assurance ── */
    $t['Banque & Assurance'] = [
        'contexte' => '<p>Le secteur bancaire et assurantiel de l\'espace OHADA connaît une transformation profonde : digitalisation des services, montée du mobile money, durcissement des exigences de conformité et renforcement de la réglementation prudentielle. Les professionnels du secteur doivent adapter en permanence leurs compétences pour rester performants et conformes.</p>
<p>Le programme "{NOM}" apporte une réponse sur mesure à ces besoins en proposant un accompagnement individualisé centré sur les pratiques et les réalités du secteur financier africain.</p>',
        'objectif_general' => 'Permettre au bénéficiaire de maîtriser les fondamentaux et les pratiques avancées du secteur bancaire et assurantiel dans l\'espace OHADA, afin d\'améliorer ses performances professionnelles et de se conformer aux exigences réglementaires.',
        'objectifs_specifiques' => [
            'Comprendre l\'environnement réglementaire et les acteurs du secteur financier OHADA ;',
            'Maîtriser les produits et services bancaires et assurantiels courants ;',
            'Analyser la solvabilité et le risque crédit d\'un client ou d\'un dossier de financement ;',
            'Appliquer les procédures KYC/AML de conformité et de lutte anti-blanchiment ;',
            'Gérer la relation client dans un contexte financier exigeant ;',
            'Maîtriser les opérations de commerce international et les crédits documentaires ;',
            'Analyser et gérer les risques financiers courants ;',
            'Mettre en œuvre les normes de reporting réglementaire applicable.',
        ],
        'public_cible' => 'Ce parcours s\'adresse aux chargés de clientèle bancaire, conseillers en assurance, analystes crédit, responsables conformité, directeurs d\'agence et professionnels du secteur financier.',
        'prerequis' => 'Une expérience dans le secteur bancaire ou assurantiel est requise. Niveau Bac+2 minimum.',
        'methodologie' => [
            'Analyse de dossiers de crédit réels et exercices de scoring ;',
            'Études de cas de conformité et simulation d\'alertes AML ;',
            'Travail sur des situations clients réelles présentées par le bénéficiaire ;',
            'Modèles d\'analyse financière et de reporting réglementaire fournis et commentés ;',
            'Veille réglementaire sur les évolutions BCEAO, CIMA et marchés financiers OHADA.',
        ],
        'debouches' => 'Ce parcours ouvre des perspectives vers les postes de Chargé de clientèle senior, Analyste crédit, Responsable conformité, Directeur d\'agence ou Consultant financier.',
    ];

    /* ── Commerce & Vente ── */
    $t['Commerce & Vente'] = [
        'contexte' => '<p>Dans un environnement de plus en plus concurrentiel, la performance commerciale ne s\'improvise pas. Elle repose sur des techniques éprouvées, une posture professionnelle affirmée et une capacité à comprendre et à convaincre ses interlocuteurs. En Côte d\'Ivoire et dans l\'espace OHADA, les équipes commerciales qui maîtrisent ces fondamentaux surpassent durablement leurs concurrents.</p>
<p>Le programme "{NOM}" propose un accompagnement individualisé qui part des situations de vente réelles du bénéficiaire pour développer des réflexes commerciaux immédiatement opérationnels — de la prospection à la fidélisation, en passant par la négociation et le closing.</p>',
        'objectif_general' => 'Permettre au bénéficiaire de maîtriser l\'ensemble du cycle de vente — prospection, qualification, présentation, négociation, closing et fidélisation — afin d\'atteindre ses objectifs commerciaux et de développer durablement son portefeuille clients.',
        'objectifs_specifiques' => [
            'Structurer et optimiser son processus de prospection commerciale ;',
            'Qualifier efficacement les prospects et identifier les besoins réels ;',
            'Construire et délivrer une présentation commerciale percutante et adaptée ;',
            'Maîtriser les techniques de négociation et de traitement des objections ;',
            'Conclure des ventes avec méthode et assurance (techniques de closing) ;',
            'Mettre en place un suivi client rigoureux pour fidéliser et développer les comptes ;',
            'Utiliser un CRM pour piloter son activité commerciale et ses indicateurs ;',
            'Analyser ses résultats, identifier ses axes d\'amélioration et ajuster sa stratégie.',
        ],
        'public_cible' => 'Ce parcours s\'adresse aux commerciaux, technico-commerciaux, chargés de clientèle, responsables de compte, agents commerciaux, entrepreneurs et tout professionnel ayant des objectifs de développement commercial.',
        'prerequis' => 'Aucun prérequis technique. Une première expérience dans une fonction en contact avec la clientèle est souhaitée mais non obligatoire.',
        'methodologie' => [
            'Jeux de rôle et simulations d\'entretiens commerciaux filmés avec débriefing immédiat ;',
            'Analyse de situations de vente réelles vécues par le bénéficiaire (succès et échecs) ;',
            'Construction d\'outils personnalisés : pitch, argumentaire, scripts téléphoniques, trames de négociation ;',
            'Travaux intersessions : prospection terrain ou appels clients réels, restitués à la séance suivante ;',
            'Boîte à outils commerciale : modèles de propositions, scripts d\'objections, grilles de qualification ;',
            'Plan d\'action commercial individuel formalisé avec objectifs, cibles et calendrier.',
        ],
        'debouches' => 'Ce parcours ouvre des perspectives vers les postes de Commercial senior, Responsable grands comptes, Manager commercial, Business Developer ou Directeur des ventes.',
    ];

    /* ── Techniques de Vente ── */
    $t['Techniques de Vente'] = $t['Commerce & Vente'];
    $t['Vente & Négociation'] = $t['Commerce & Vente'];
    $t['Développement Commercial'] = $t['Commerce & Vente'];

    /* ── Marketing ── */
    $t['Marketing'] = [
        'contexte' => '<p>Le marketing est devenu le moteur central de la croissance des organisations. Dans un marché africain en pleine digitalisation, les entreprises qui maîtrisent les outils du marketing moderne — digital, contenu, réseaux sociaux, data — disposent d\'un avantage décisif pour attirer, convertir et fidéliser leurs clients. Pourtant, de nombreux professionnels exercent leurs fonctions marketing sans méthode structurée ni maîtrise des outils adaptés.</p>
<p>Le programme "{NOM}" apporte au bénéficiaire une maîtrise opérationnelle du marketing dans son contexte spécifique, combinant les fondamentaux stratégiques et les outils pratiques du marketing contemporain appliqués au marché africain.</p>',
        'objectif_general' => 'Permettre au bénéficiaire de concevoir, mettre en œuvre et piloter une stratégie marketing cohérente et efficace, combinant les outils du marketing traditionnel et digital pour développer la notoriété, l\'acquisition clients et la fidélisation de son organisation.',
        'objectifs_specifiques' => [
            'Analyser son marché, sa concurrence et son positionnement avec les bons outils ;',
            'Définir une stratégie marketing adaptée à ses cibles et à ses ressources ;',
            'Concevoir et déployer des campagnes de communication multicanales ;',
            'Maîtriser les réseaux sociaux professionnels (LinkedIn, Facebook, Instagram) pour le business ;',
            'Créer du contenu à forte valeur ajoutée pour attirer et engager ses audiences ;',
            'Gérer le budget marketing et mesurer le retour sur investissement des actions ;',
            'Utiliser les outils d\'analyse (Google Analytics, Meta Ads Manager) pour optimiser les campagnes ;',
            'Développer une identité de marque forte et cohérente sur tous les points de contact.',
        ],
        'public_cible' => 'Ce parcours s\'adresse aux responsables marketing, chargés de communication, community managers, entrepreneurs, dirigeants de PME et tout professionnel souhaitant maîtriser le marketing pour développer son activité.',
        'prerequis' => 'Aisance avec les outils numériques de base. Une première expérience en communication ou en commerce est un atout.',
        'methodologie' => [
            'Travail directement sur la marque, les produits et les cibles réels du bénéficiaire ;',
            'Ateliers pratiques de création de contenu, rédaction publicitaire et design de campagnes ;',
            'Analyse en direct des performances marketing actuelles du bénéficiaire avec recommandations ;',
            'Utilisation des outils professionnels : Canva, Meta Ads Manager, Google Analytics, Mailchimp ;',
            'Études de cas de marques africaines et internationales ayant réussi leur stratégie marketing ;',
            'Plan marketing annuel formalisé avec calendrier éditorial, budget et KPI de suivi.',
        ],
        'debouches' => 'Ce parcours ouvre des perspectives vers les postes de Responsable marketing, Directeur marketing, Chargé de communication digitale, Consultant marketing ou Entrepreneur maîtrisant son acquisition clients.',
    ];

    /* ── Marketing Digital ── */
    $t['Marketing Digital'] = $t['Marketing'];
    $t['Communication & Marketing'] = $t['Marketing'];
    $t['Communication'] = $t['Marketing'];

    /* ── Juridique ── */
    $t['Juridique'] = [
        'contexte' => '<p>Dans l\'espace OHADA, le cadre juridique qui régit les affaires est à la fois dense, évolutif et spécifique. La méconnaissance des règles juridiques expose les organisations à des risques considérables : contrats nuls ou déséquilibrés, litiges coûteux, sanctions réglementaires, conflits sociaux non maîtrisés. À l\'inverse, une maîtrise juridique opérationnelle transforme le professionnel en un acteur sécurisé et crédible.</p>
<p>Le programme "{NOM}" apporte au bénéficiaire les connaissances juridiques pratiques indispensables pour sécuriser ses actes professionnels quotidiens, prévenir les risques légaux et interagir avec efficacité avec les conseils juridiques et les juridictions compétentes.</p>',
        'objectif_general' => 'Permettre au bénéficiaire d\'acquérir une maîtrise juridique opérationnelle dans son domaine d\'activité, afin de sécuriser ses contrats, prévenir les contentieux et prendre des décisions éclairées dans un environnement légal OHADA.',
        'objectifs_specifiques' => [
            'Comprendre l\'organisation judiciaire, les sources du droit et les actes uniformes OHADA ;',
            'Rédiger, analyser et négocier les contrats commerciaux courants avec rigueur ;',
            'Appliquer le droit OHADA des sociétés dans les actes de gestion courante ;',
            'Maîtriser les bases du droit du travail et prévenir les contentieux sociaux ;',
            'Identifier et gérer les principales obligations fiscales et réglementaires ;',
            'Gérer les litiges commerciaux et choisir les modes alternatifs de règlement des différends ;',
            'Prévenir les risques juridiques liés à son secteur d\'activité ;',
            'Collaborer efficacement avec des conseils juridiques externes.',
        ],
        'public_cible' => 'Ce parcours s\'adresse aux dirigeants d\'entreprise, directeurs administratifs, responsables juridiques, responsables RH, commerciaux et tout professionnel exposé à des enjeux juridiques dans son activité quotidienne.',
        'prerequis' => 'Aucun prérequis juridique particulier. Un niveau Bac+2 minimum ou une expérience professionnelle significative est recommandé.',
        'methodologie' => [
            'Analyse de contrats et de décisions de justice réels tirés du contexte OHADA ;',
            'Exercices pratiques de rédaction contractuelle avec correction détaillée et commentée ;',
            'Mise en situation : simulation de litiges, de négociations et de procédures amiables ;',
            'Modèles de contrats types OHADA fournis, commentés et adaptables au contexte du bénéficiaire ;',
            'Études de cas de contentieux récents dans l\'espace OHADA avec analyse des décisions ;',
            'Veille juridique sur les évolutions du droit OHADA et des réglementations nationales.',
        ],
        'debouches' => 'Ce parcours ouvre des perspectives vers les fonctions de Juriste d\'entreprise, Compliance Officer, Responsable juridique, Directeur administratif, ou vers une spécialisation en droit des affaires OHADA.',
    ];

    /* ── Droit (alias) ── */
    $t['Droit'] = $t['Juridique'];
    $t['Droit des Affaires'] = $t['Juridique'];
    $t['Droit & Contrats'] = $t['Juridique'];

    /* ── Alias catégories courantes ── */
    $t['Ressources Humaines'] = $t['GRH'];
    $t['RH'] = $t['GRH'];
    $t['Management'] = $t['Management & Leadership'];
    $t['Leadership'] = $t['Management & Leadership'];
    $t['Informatique & Technologies'] = $t['Informatique & Tech'];
    $t['Informatique'] = $t['Informatique & Tech'];
    $t['Finance'] = $t['Comptabilité & Finance'];
    $t['Comptabilité'] = $t['Comptabilité & Finance'];
    $t['IA'] = $t['IA & Digitalisation'];
    $t['Intelligence Artificielle'] = $t['IA & Digitalisation'];

    /* ── Template par défaut ── */
    $t['_default'] = [
        'contexte' => '<p>Dans un environnement professionnel en constante évolution, les organisations de l\'espace OHADA font face à des défis croissants qui exigent des compétences pointues, directement opérationnelles et immédiatement valorisables. La formation professionnelle individualisée est la réponse la plus efficace à ces besoins : elle s\'adapte au rythme, au contexte et aux objectifs spécifiques de chaque bénéficiaire.</p>
<p>Le programme "{NOM}" est conçu pour apporter au bénéficiaire une maîtrise complète de son domaine, à travers un accompagnement personnalisé qui part de sa réalité professionnelle pour développer des compétences durables et vérifiables.</p>',
        'objectif_general' => 'Permettre au bénéficiaire d\'acquérir une maîtrise opérationnelle et vérifiable dans le domaine "{NOM}", directement applicable dans son environnement professionnel et sanctionnée par une certification reconnue.',
        'objectifs_specifiques' => [
            'Comprendre les fondamentaux et les enjeux actuels du domaine ;',
            'Acquérir les outils et méthodes directement applicables en contexte professionnel ;',
            'Développer des compétences pratiques validées par des mises en situation réelles ;',
            'Analyser des cas complexes et proposer des solutions adaptées ;',
            'Mettre en place des outils de suivi et de pilotage de son activité ;',
            'Communiquer efficacement sur son expertise auprès de ses parties prenantes ;',
            'Formaliser un plan d\'action individuel engagé et mesurable ;',
            'Obtenir une certification professionnelle reconnue partout en Afrique francophone.',
        ],
        'public_cible' => 'Ce parcours s\'adresse aux professionnels en activité souhaitant renforcer leurs compétences, aux demandeurs d\'emploi qualifiés, aux étudiants en fin de cursus et aux entrepreneurs souhaitant professionnaliser leur activité.',
        'prerequis' => 'Niveau Bac ou expérience professionnelle équivalente. Une motivation à progresser et à appliquer les acquis constitue le principal prérequis.',
        'methodologie' => [
            'Diagnostic préalable sur le niveau et les objectifs spécifiques du bénéficiaire ;',
            'Apprentissage par la pratique : chaque concept est immédiatement mis en œuvre sur des cas réels ;',
            'Études de cas adaptées au contexte professionnel du bénéficiaire ;',
            'Travaux intersessions avec feedback personnalisé du formateur ;',
            'Boîte à outils complète : modèles, guides et ressources du domaine ;',
            'Plan d\'action individuel formalisé et engagé à la clôture du parcours.',
        ],
        'debouches' => 'Ce parcours renforce le positionnement professionnel du bénéficiaire dans le domaine "{NOM}" et ouvre des perspectives d\'évolution de carrière, de prise de responsabilités élargies, ou de développement d\'une activité indépendante.',
    ];

    return $t;
}

} // end if !function_exists
