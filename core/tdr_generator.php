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
        return match($niveau) {
            'debutant' => [
                ['titre'=>'C\'est quoi la comptabilité ? Pourquoi le SYSCOHADA en Afrique ?','contenus'=>'Rôle de la comptabilité dans une PME : à quoi ça sert concrètement ? · Naissance du SYSCOHADA : historique simplifié et pays membres OHADA · Différence entre le commerçant, l\'artisan et la PME formelle · Vocabulaire de base : actif, passif, charge, produit, résultat · Les métiers de la comptabilité en Côte d\'Ivoire · Exercice guidé : reconnaître une charge d\'un produit sur 20 opérations','duree'=>$s[0]],
                ['titre'=>'Le plan de comptes OHADA : comprendre la logique des 9 classes','contenus'=>'Les 9 classes du plan de comptes : à quoi sert chaque classe ? · Classe 1 (ressources) vs Classe 2 (emplois durables) · Classe 3 (stocks), Classe 4 (tiers), Classe 5 (trésorerie) · Classe 6 (charges) et Classe 7 (produits) · Comment trouver le bon numéro de compte · Exercice : affecter 15 opérations à leur classe','duree'=>$s[1]],
                ['titre'=>'Mon premier journal : saisir achats, ventes et trésorerie pas à pas','contenus'=>'La règle débit/crédit expliquée simplement avec des exemples concrets · Saisir une facture d\'achat : étape par étape · Saisir une facture de vente et un encaissement · Enregistrer un paiement fournisseur et un règlement client · Corriger une erreur de saisie · Exercice guidé : tenir le journal d\'une semaine d\'activité d\'une PME ivoirienne fictive','duree'=>$s[2]],
                ['titre'=>'Les documents obligatoires : factures, reçus et pièces justificatives','contenus'=>'La facture légale en Côte d\'Ivoire : mentions obligatoires · Bon de commande, bon de livraison, bon de réception · Note de débit et note de crédit (avoir) · Reçu de caisse et pièce de caisse · Classement et conservation des pièces · Exercice : identifier les anomalies sur 5 factures réelles','duree'=>$s[3]],
                ['titre'=>'Les immobilisations et stocks : comment les enregistrer','contenus'=>'Qu\'est-ce qu\'une immobilisation ? Exemples concrets pour une PME · L\'amortissement linéaire : calcul guidé pas à pas · Enregistrer un achat d\'équipement en comptabilité · Stock initial, achats et stock final : comprendre l\'inventaire · Fiche de stock : comment la tenir correctement · Exercice : établir le tableau d\'amortissement d\'un véhicule','duree'=>$s[4]],
                ['titre'=>'Initiation au logiciel : saisie guidée sous Excel ou Sage','contenus'=>'Présentation du tableau de saisie Excel comptable simplifié · Saisir les opérations courantes dans le modèle fourni · Grand-livre automatique et balance de vérification · Rapprochement bancaire de base : comparer le relevé et les écritures · Premiers pas sous Sage 100 Comptabilité : navigation et saisie · Exercice : saisir un mois complet d\'une PME fictive','duree'=>$s[5]],
                ['titre'=>'Mini-projet : tenir la comptabilité d\'un mois pour une PME ivoirienne','contenus'=>'Remise d\'un dossier complet : factures, relevés, pièces de caisse · Saisie chronologique de toutes les opérations dans le journal · Établissement de la balance de fin de mois · Premières conclusions : l\'entreprise a-t-elle gagné ou perdu de l\'argent ? · Correction commentée et retour individuel du formateur','duree'=>$s[6]],
                ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : traitement guidé d\'un dossier comptable simple sur un jeu de données inédit · Correction individualisée et feedback détaillé du formateur · Remise du certificat IBIG EDUFORM "SYSCOHADA Niveau Débutant" et plan de progression','duree'=>$s[7]],
            ],
            'expert' => [
                ['titre'=>'Révision SYSCOHADA 2017 : impacts sur les groupes, filiales et holdings OHADA','contenus'=>'Principales modifications du SYSCOHADA révisé : nouvelles obligations, suppression de comptes · Champ d\'application des états financiers consolidés OHADA · Traitement des subventions d\'investissement et passifs éventuels · Instruments financiers : évaluation et présentation · Veille normative : convergence IFRS/SYSCOHADA et calendrier · Analyse comparative des positions fiscales et comptables divergentes','duree'=>$s[0]],
                ['titre'=>'Consolidation des comptes : méthodes et retraitements','contenus'=>'Périmètre de consolidation et notion de contrôle exclusif, conjoint et influence notable · Méthode d\'intégration globale : élimination des comptes réciproques · Intégration proportionnelle et mise en équivalence · Goodwill et écarts d\'acquisition : calcul et dépréciation · Retraitements de consolidation : stocks FIFO/LIFO, crédit-bail, IDA · Exercice : consolidation d\'un groupe de 3 sociétés ivoiriennes','duree'=>$s[1]],
                ['titre'=>'IFRS vs SYSCOHADA : retraitements de convergence pour les groupes internationaux','contenus'=>'Principales différences IFRS/SYSCOHADA : IAS 16, IAS 36, IAS 37, IFRS 15, IFRS 16 · Retraitement des contrats de location (IFRS 16) · Comptabilisation des revenus au coût amorti (IFRS 9) · Tests de dépréciation des actifs (IAS 36) selon les CGU · Tableau de passage SYSCOHADA → IFRS : méthodologie et documentation · Exercice : établir le tableau de passage d\'une société cotée','duree'=>$s[2]],
                ['titre'=>'Optimisation fiscale stratégique et gestion des prix de transfert','contenus'=>'Identification des leviers d\'optimisation fiscale légale en Côte d\'Ivoire · Prix de transfert : principes de pleine concurrence, documentation OCDE · Provisions fiscalement déductibles : stratégie de constitution et de reprise · Déficits fiscaux reportables : gestion stratégique pluriannuelle · Conventions fiscales de non-double imposition · Arbitrages dividendes vs salaires pour les dirigeants actionnaires','duree'=>$s[3]],
                ['titre'=>'Contrôle interne comptable et cartographie des risques financiers','contenus'=>'Cartographie des risques comptables par cycle : achats, ventes, trésorerie, paie · Procédures de contrôle interne : séparation des tâches, autorisation, rapprochement · Fraudes comptables les plus fréquentes en PME africaines : détection et prévention · Indicateurs de qualité des états financiers : signaux d\'alerte et points d\'audit · Test de détail et procédures analytiques · Exercice : audit des contrôles du cycle trésorerie d\'une entreprise','duree'=>$s[4]],
                ['titre'=>'Audit des états financiers : préparation du dossier CAC et due diligence','contenus'=>'Processus d\'audit légal en Côte d\'Ivoire : missions du CAC selon l\'AUCOM · Assertions d\'audit par poste : exhaustivité, existence, évaluation, présentation · Dossier permanent et dossier de l\'exercice : structure et indexation · Lettre de mission, plan de mission et programme de travail · Due diligence financière acquisition : ajustements de prix et déclarations et garanties · Exercice : revue analytique d\'un bilan avec identification des zones de risque','duree'=>$s[5]],
                ['titre'=>'Évaluation d\'entreprise et pilotage de la clôture pour les groupes','contenus'=>'Méthodes d\'évaluation : DCF, multiples, actif net réévalué · Modèle financier d\'acquisition : LBO simplifié et financement · Pilotage de la clôture consolidée : calendrier, interlocuteurs, outils · Reporting IFRS mensuel pour les investisseurs institutionnels · Communication financière : rapport annuel, communiqué de résultats · Exercice : évaluation d\'une PME ivoirienne par trois méthodes','duree'=>$s[6]],
                ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : dossier de consolidation et analyse stratégique sur cas inédit · Correction individualisée et feedback du formateur expert · Remise du certificat IBIG EDUFORM "SYSCOHADA Expert" et plan de développement','duree'=>$s[7]],
            ],
            default => [
                ['titre'=>'Le référentiel SYSCOHADA révisé : principes fondamentaux et champ d\'application','contenus'=>'Historique et objectifs du SYSCOHADA révisé (2017) · Champ d\'application : entreprises concernées, seuils et dispenses · Principes comptables fondamentaux (continuité, prudence, coût historique) · Plan de comptes OHADA : structure et logique des 9 classes · Différences avec le SYSCOA initial et impacts pratiques','duree'=>$s[0]],
                ['titre'=>'Comptabilisation des opérations courantes','contenus'=>'Enregistrement des achats, ventes, charges et produits · Opérations de trésorerie : encaissements, décaissements, rapprochements bancaires · Traitement des avances, acomptes et retenues de garantie · Opérations en devises et règles de conversion · Exercices pratiques sur pièces comptables réelles d\'entreprises ivoiriennes','duree'=>$s[1]],
                ['titre'=>'Immobilisations, amortissements et provisions','contenus'=>'Classification des immobilisations corporelles et incorporelles · Méthodes d\'amortissement (linéaire, dégressif, composants) · Dépréciations d\'actifs : tests et écriture de constatation · Provisions pour risques et charges : conditions, comptabilisation, reprise · Exercices sur le calcul des dotations et tableaux d\'amortissement','duree'=>$s[2]],
                ['titre'=>'Régularisations de fin d\'exercice','contenus'=>'Charges à payer et produits à recevoir · Charges et produits constatés d\'avance · Ajustement des stocks : méthode des inventaires permanents et intermittents · Traitement des écarts de conversion et réévaluations · Construction du tableau des flux de trésorerie','duree'=>$s[3]],
                ['titre'=>'États financiers annuels conformes SYSCOHADA','contenus'=>'Structure et contenu du Bilan (actif/passif, retraitements) · Compte de résultat : SIG, formation du résultat net · Tableau des flux de trésorerie (méthode directe et indirecte) · Notes annexes obligatoires : contenu et présentation · Règles de consolidation pour les groupes : notions de base','duree'=>$s[4]],
                ['titre'=>'Liasse fiscale et articulation comptabilité-fiscalité','contenus'=>'Retraitements fiscaux extra-comptables (réintégrations, déductions) · TVA déductible et collectée : calcul et déclaration · Impôt sur les Sociétés (IS) : base imposable, acomptes provisionnels · Passage du résultat comptable au résultat fiscal · Remplissage de la liasse fiscale sur supports DGI réels','duree'=>$s[5]],
                ['titre'=>'Logiciels comptables et contrôle de la qualité des comptes','contenus'=>'Saisie et lettrage sous Sage 100 Comptabilité / CIEL Compta · Édition des journaux, grand-livre et balance de vérification · Balance âgée et analyse des soldes anormaux · Clôture comptable : processus, check-list et délais légaux · Archivage électronique et conservation des pièces justificatives','duree'=>$s[6]],
                ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : traitement d\'un dossier comptable complet (saisie → états financiers) sur un jeu de données inédit · Correction individualisée et feedback détaillé du formateur · Remise du certificat IBIG EDUFORM "SYSCOHADA Révisé" et construction du plan de développement post-formation','duree'=>$s[7]],
            ],
        };
    }

    /* ══ Fiscalité DGI / TVA / IS / Déclarations fiscales ══ */
    if (preg_match('/d[eé]claration\s+fiscale|tva.*is\b|fiscalit[eé]\s+(des\s+)?pme|dgi.*c[oô]te|imp[oô]t\s+soci[eé]t[eé]|irvm/ui', $nom)) {
        $s = $split(max(16, $heures), 7);
        return match($niveau) {
            'debutant' => [
                ['titre'=>'C\'est quoi un impôt ? Le système fiscal ivoirien expliqué simplement','contenus'=>'Pourquoi l\'État collecte des impôts et à quoi ça sert · Les différents impôts en Côte d\'Ivoire : liste et explication simple · Qui paie quoi : particuliers, commerçants, PME · La DGI : son rôle et comment la contacter · Mon entreprise est-elle dans les délais ? Calendrier fiscal illustré · Exercice : identifier les impôts auxquels une PME fictive est soumise','duree'=>$s[0]],
                ['titre'=>'La TVA expliquée : comment ça fonctionne concrètement','contenus'=>'La TVA : je collecte pour l\'État, je récupère ce que j\'ai payé · Taux en vigueur en Côte d\'Ivoire (18 %, 9 %, 0 %) · La facture avec TVA : mentions obligatoires et comment la rédiger · TVA collectée vs TVA déductible : exemple chiffré simple · Ce qu\'on ne peut pas déduire · Exercice guidé : calculer la TVA à payer sur 10 opérations','duree'=>$s[1]],
                ['titre'=>'L\'Impôt sur les Sociétés (IS) : comprendre comment il se calcule','contenus'=>'Résultat comptable vs résultat fiscal : la différence expliquée simplement · Les charges qu\'on ne peut pas déduire (amendes, charges personnelles) · Taux d\'IS et d\'IMF en Côte d\'Ivoire · Calcul pas à pas de l\'IS sur un exemple concret · Les acomptes provisionnels : quand et combien payer · Exercice : calculer l\'IS d\'une PME à partir d\'un compte de résultat simple','duree'=>$s[2]],
                ['titre'=>'Les autres impôts : patente, taxe foncière, IRVM et retenues','contenus'=>'La patente : qui la paie, comment se calcule-t-elle · La taxe foncière : propriétaires et locataires · L\'IRVM : impôt sur les dividendes et intérêts · Les retenues à la source sur salaires : IRPP/ITS · TPA et contribution FDFP : obligations de l\'employeur · Exercice : identifier les impôts à payer dans un scénario d\'entrepreneur','duree'=>$s[3]],
                ['titre'=>'DGI-net : créer son compte et faire sa première déclaration','contenus'=>'Créer son compte contribuable sur DGI-net · Navigation guidée sur la plateforme · Remplir et soumettre sa première déclaration de TVA mensuelle · Payer en ligne avec Orange Money · Télécharger sa quittance · Que faire en cas d\'erreur sur une déclaration déjà soumise ? · Exercice guidé : simulation d\'une déclaration TVA','duree'=>$s[4]],
                ['titre'=>'Les sanctions, le contrôle fiscal et comment s\'en prémunir','contenus'=>'Les pénalités courantes : retard, omission, insuffisance · Comment un contrôle fiscal se passe · Mes droits face à l\'inspecteur des impôts · Les erreurs les plus fréquentes des PME ivoiriennes · Check-list de conformité fiscale mensuelle · Exercice : identifier les risques fiscaux dans un dossier de PME','duree'=>$s[5]],
                ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : remplissage de déclarations simples sur cas inédit · Correction individualisée et feedback détaillé · Remise du certificat IBIG EDUFORM "Fiscalité Ivoirienne Niveau Débutant"','duree'=>$s[6]],
            ],
            'expert' => [
                ['titre'=>'Stratégie fiscale globale : pilotage du risque et optimisation pour groupes et holdings','contenus'=>'Diagnostic fiscal d\'un groupe de PME : cartographie des expositions · Choix de la structure juridique optimale (holding, filiale, SAS) · Prix de transfert intra-groupe : règles OCDE et documentation obligatoire · Conventions fiscales CI avec les pays partenaires (France, Maroc, UEMOA) · Due diligence fiscale acquisition : identification et quantification des passifs · Élaboration d\'une politique fiscale de groupe','duree'=>$s[0]],
                ['titre'=>'TVA avancée : cas complexes, prorata et schémas sectoriels','contenus'=>'Prorata de déduction : calcul et gestion des assujettis partiels · TVA sur les opérations intracommunautaires UEMOA · Régimes sectoriels spécifiques : BTP (TVA sur marge), immobilier, banque · TVA sur importations et régimes en douane · Crédit de TVA : stratégie de restitution et gestion des délais · Contrôle TVA : réponse à une demande de vérification de points précis','duree'=>$s[1]],
                ['titre'=>'IS avancé : provisions, charges exceptionnelles et abus de droit','contenus'=>'Provisions réglementées : stratégie de constitution et de reprise · Dépréciation des créances clients : conditions et preuves requises · Traitement fiscal des abandons de créances · Abus de droit fiscal : risques et protection · Optimisation de la rémunération du dirigeant actionnaire · Gestion des déficits reportables : stratégie pluriannuelle','duree'=>$s[2]],
                ['titre'=>'Code des Investissements, ZFI et financements publics à impact fiscal','contenus'=>'Avantages du Code des Investissements CI : agréments, exonérations, conditions · Zones franches industrielles (ZFI) : entreprises éligibles et bénéfices · Zone franche du Grand Abidjan : opportunités et contraintes · Contrat de performance avec l\'État : enjeux fiscaux · Subventions et aides publiques : traitement fiscal · Exercice : montage d\'un dossier d\'agrément au Code des Investissements','duree'=>$s[3]],
                ['titre'=>'Contrôle fiscal approfondi : défense et gestion du contentieux','contenus'=>'Procédures de contrôle : VASFE, vérification de comptabilité, examen contradictoire · Droits et garanties du contribuable vérifié · Stratégie de réponse aux redressements : terrain de négociation · Transaction fiscale : conditions, modalités et effets · Recours hiérarchique, commission des impôts directs et tribunal · Provision pour risque fiscal : calcul et comptabilisation · Exercice : réponse à une proposition de rectification','duree'=>$s[4]],
                ['titre'=>'Pilotage fiscal du groupe : tableau de bord et veille réglementaire','contenus'=>'Tableau de bord fiscal : indicateurs de taux effectif, risque, conformité · Rapport de tax review annuel pour le comité de direction · Veille fiscale : sources officielles et alertes réglementaires · BEPS et érosion de la base fiscale : impacts pour les PME exportatrices · Facturation électronique obligatoire : préparation et impacts · Automatisation des déclarations : API DGI-net et outils ERP','duree'=>$s[5]],
                ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : dossier de stratégie fiscale complexe avec redressement et contentieux sur cas inédit · Correction individualisée et feedback expert · Remise du certificat IBIG EDUFORM "Fiscalité Expert"','duree'=>$s[6]],
            ],
            default => [
                ['titre'=>'Panorama du système fiscal ivoirien et obligations des entreprises','contenus'=>'Architecture de la DGI Côte d\'Ivoire : direction, services et interlocuteurs · Régimes d\'imposition : réel normal, réel simplifié, Taxe d\'Impôt Synthétique (TIS) · Critères de rattachement à un régime et seuils 2024-2025 · Calendrier fiscal annuel : toutes les échéances à ne pas manquer · Sanctions et pénalités : majorations, amendes, intérêts de retard','duree'=>$s[0]],
                ['titre'=>'TVA : mécanisme, calcul et déclaration mensuelle','contenus'=>'Base imposable et taux (18 %, 9 %, exonérations) · TVA collectée : fait générateur, facturation, mention obligatoire · TVA déductible : conditions, prorata, régularisations annuelles · Déclaration mensuelle CA (formulaire DGI-net) · Crédit de TVA : restitution et conditions · Exercices pratiques sur opérations réelles d\'achat et de vente','duree'=>$s[1]],
                ['titre'=>'Impôt sur les Sociétés (IS) et Impôt Minimum Forfaitaire (IMF)','contenus'=>'Base imposable IS : passage du résultat comptable au résultat fiscal · Charges déductibles et réintégrations (TVS, amendes, excédents de rémunération) · Calcul de l\'IS et de l\'IMF · Acomptes provisionnels (avril, juin, septembre) · Déclaration annuelle des résultats (formulaire) · Exercices sur la détermination du résultat fiscal','duree'=>$s[2]],
                ['titre'=>'IRVM, retenues à la source et autres impôts','contenus'=>'IRVM sur les dividendes, intérêts et revenus de capitaux · Retenue à la source sur salaires (IRPP/TS) : calcul et reversement · Taxe patronale d\'apprentissage (TPA) et contribution FDFP · Patente : base de calcul, valeur locative et tarif · Taxe foncière et logement locatif · Impôt sur le BNC (professions libérales)','duree'=>$s[3]],
                ['titre'=>'Télédéclaration sur DGI-net et gestion des contrôles','contenus'=>'Inscription et navigation sur la plateforme DGI-net · Saisie, validation et téléchargement des déclarations · Paiement en ligne (Orange Money, virement, carte) · Droit de communication et contrôle fiscal : procédure et droits du contribuable · Contentieux fiscal : réclamation, recours hiérarchique, tribunal · Préparation d\'un dossier de vérification fiscale','duree'=>$s[4]],
                ['titre'=>'Optimisation fiscale légale et cas pratiques de synthèse','contenus'=>'Avantages fiscaux applicables aux PME ivoiriennes · Zones franches industrielles (ZFI) et Code des Investissements · Restructuration de la rémunération des dirigeants · Optimisation du moment de constatation des produits et charges · Traitement fiscal des véhicules, frais de déplacement et cadeaux · Cas pratiques intégraux : de la balance à la liasse fiscale complète','duree'=>$s[5]],
                ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : traitement d\'un dossier fiscal complet avec remplissage des formulaires DGI sur cas inédit · Correction individualisée et feedback détaillé · Remise du certificat IBIG EDUFORM "Fiscalité Ivoirienne" et plan de mise en conformité de l\'organisation du bénéficiaire','duree'=>$s[6]],
            ],
        };
    }

    /* ══ Audit Interne / IIA ══ */
    if (preg_match('/audit\s+interne|normes\s+iia|ippf|audit.*contr[oô]le\s+interne/ui', $nom)) {
        $s = $split(max(20, $heures), 8);
        return match($niveau) {
            'debutant' => [
                ['titre'=>'C\'est quoi l\'audit interne ? Rôle, utilité et différence avec l\'audit externe','contenus'=>'Audit interne vs audit externe vs contrôle de gestion : les différences claires · Pourquoi les entreprises ont besoin d\'un audit interne · L\'auditeur interne n\'est pas un policier : sa vraie valeur · Les normes IIA : c\'est quoi et pourquoi ça compte · Exemples d\'audits internes dans des PME ivoiriennes · Exercice : identifier si une situation relève de l\'audit interne ou externe','duree'=>$s[0]],
                ['titre'=>'Les risques en entreprise : identifier et comprendre','contenus'=>'Qu\'est-ce qu\'un risque ? La définition simple et des exemples concrets · Les grandes familles de risques : financier, opérationnel, juridique, réputation · La matrice risque simple : probabilité × impact · Les risques spécifiques aux PME africaines · Comment une PME peut commencer à recenser ses risques · Exercice guidé : identifier 10 risques dans une entreprise fictive','duree'=>$s[1]],
                ['titre'=>'Le contrôle interne : qu\'est-ce que c\'est et comment ça fonctionne','contenus'=>'Définition simple du contrôle interne · Les 5 composantes du COSO expliquées simplement · Exemples de contrôles : séparation des tâches, validation, rapprochement · Les procédures internes : comment les lire et les appliquer · Signaux d\'alerte d\'un contrôle interne défaillant · Exercice : identifier les contrôles manquants dans un processus d\'achat','duree'=>$s[2]],
                ['titre'=>'Préparer une mission d\'audit : les bases essentielles','contenus'=>'La lettre de mission : à quoi ça sert et que contient-elle · Prendre connaissance d\'un processus à auditer : les questions à poser · Le programme de travail simplifié : objectifs et tests · Comment conduire un entretien avec un audité : posture et questions · Documenter ses travaux : la feuille de travail de base · Exercice : préparer les questions pour auditer le processus caisse','duree'=>$s[3]],
                ['titre'=>'Collecter des preuves d\'audit : techniques de base','contenus'=>'Observer et inspecter : comment regarder avec les yeux d\'un auditeur · Vérifier des documents : factures, contrats, pièces justificatives · Compter et recompter : inventaires, caisses, stocks · Poser les bonnes questions lors des entretiens · Tester un échantillon : comment choisir et documenter · Exercice : réaliser un mini-audit de la caisse d\'une PME fictive','duree'=>$s[4]],
                ['titre'=>'Rédiger ses premières observations et recommandations','contenus'=>'Structure d\'une observation d\'audit : fait, critère, cause, conséquence · Hiérarchiser : qu\'est-ce qui est grave, moyen, faible ? · Rédiger une recommandation utile et réaliste · Le rapport d\'audit simplifié : structure et règles de rédaction · Présenter ses conclusions de manière non accusatrice · Exercice : rédiger 3 constats sur un processus audité','duree'=>$s[5]],
                ['titre'=>'Les fraudes courantes en PME : les reconnaître et les signaler','contenus'=>'Les fraudes les plus fréquentes dans les PME africaines : détournements, surfacturations, fantômes · Signaux d\'alerte d\'une fraude · Que faire quand on suspecte une fraude · Rôle et limites de l\'auditeur face à la fraude · Protection du lanceur d\'alerte · Exercice : analyser un cas de fraude supposée et identifier les preuves à chercher','duree'=>$s[6]],
                ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : mini-mission d\'audit complète sur cas guidé · Correction individualisée et feedback du formateur · Remise du certificat IBIG EDUFORM "Audit Interne Niveau Débutant"','duree'=>$s[7]],
            ],
            'expert' => [
                ['titre'=>'Gouvernance de la fonction d\'audit interne : positionnement stratégique et charte','contenus'=>'Positionnement de la Direction de l\'Audit Interne (DAI) dans la gouvernance · Charte d\'audit : rédaction, approbation et révision · Modèle des trois lignes de défense (IIA 2020) : mise en œuvre pratique · Relation DAI / Comité d\'Audit / Conseil d\'Administration · Key Performance Indicators de la DAI : mesurer la valeur ajoutée · Benchmark international : pratiques des DAI dans les groupes africains cotés','duree'=>$s[0]],
                ['titre'=>'Évaluation des risques stratégiques et plan d\'audit basé sur les risques','contenus'=>'Risk-Based Audit Planning (RBAP) : méthodologie complète · Intégration du registre des risques entreprise dans la planification d\'audit · Priorisation des univers d\'audit : critères avancés et scoring · Emerging risks : ESG, cyber, transformation digitale, conformité réglementaire · Ressources et compétences de la DAI : gap analysis et plan de recrutement · Comité d\'audit : présentation du PAA et négociation du budget','duree'=>$s[1]],
                ['titre'=>'Audit des processus stratégiques et à risque élevé','contenus'=>'Audit de la fonction achats : risques de collusion et de conflit d\'intérêts · Audit de la gouvernance IT : contrôles généraux, gestion des accès, continuité · Audit des projets de transformation : contrôle de la conduite du changement · Audit de la conformité réglementaire : BCEAO, DGI, CNPS, OHADA · Audit de la rémunération des dirigeants · Exercice : planification et exécution d\'un audit de projet de transformation','duree'=>$s[2]],
                ['titre'=>'Data Analytics en audit : exploitation des données massives','contenus'=>'Audit par l\'analyse de données : IDEA, ACL Analytics, Python pour auditeurs · Techniques d\'extraction et de requêtage pour identifier les anomalies · Tests de l\'ensemble d\'une population vs échantillonnage : avantages comparatifs · Visualisation des résultats d\'analyse pour le rapport d\'audit · Audit continu : mise en place de scripts de surveillance permanente · Exercice : détection d\'anomalies dans une base de données comptable via SQL','duree'=>$s[3]],
                ['titre'=>'Investigation de fraudes complexes et forensics financier','contenus'=>'Fraud risk assessment : évaluation structurée du risque de fraude · Schémas de fraude avancés : manipulation des états financiers (ISA 240) · Techniques d\'investigation financière : analyse des flux, triangulation des preuves · Digital forensics : analyse des logs, emails, métadonnées · Entretiens d\'investigation : techniques de kinesics et Reid · Rapport d\'investigation : structure légale et communication à la direction · Coordination avec les avocats et les autorités judiciaires','duree'=>$s[4]],
                ['titre'=>'Qualité de la fonction d\'audit : évaluation interne et externe','contenus'=>'Programme d\'assurance et d\'amélioration qualité (QAIP) : conception et déploiement · Évaluation interne continue : supervision, revues par les pairs · Évaluation externe : processus, sélection du validateur, rapport · Maturité de la DAI : modèle de maturité IIA et plan de progression · Conformité aux normes IIA : audit de la conformité de la DAI · Certification CIA : préparation, structure et conseils de réussite','duree'=>$s[5]],
                ['titre'=>'Reporting au Comité d\'Audit et transformation de la DAI','contenus'=>'Communication au Comité d\'Audit : présentation des résultats, opinions et perspectives · Opinion annuelle sur l\'environnement de contrôle · Tableau de bord de la DAI : métriques avancées et benchmarks · Transformation digitale de la DAI : outils IA, RPA et audit continu · Influence et leadership de l\'auditeur interne : positionnement en Business Partner · Plan de transformation de la DAI sur 3 ans','duree'=>$s[6]],
                ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : audit stratégique complexe et rapport au Comité d\'Audit sur cas inédit · Correction individualisée par le formateur certifié CIA · Remise du certificat IBIG EDUFORM "Audit Interne Expert IIA"','duree'=>$s[7]],
            ],
            default => [
                ['titre'=>'Cadre de référence de l\'audit interne : IPPF, code d\'éthique et positionnement','contenus'=>'Définition de l\'audit interne selon l\'IIA · Cadre International des Pratiques Professionnelles (IPPF) : normes d\'attribut et de fonctionnement · Code d\'éthique de l\'auditeur interne · Positionnement de la fonction d\'audit : rattachement, indépendance et objectivité · Cartographie des activités auditables : processus, risques et enjeux','duree'=>$s[0]],
                ['titre'=>'Évaluation des risques et planification du plan d\'audit annuel','contenus'=>'Méthode de cartographie des risques (top-down, bottom-up) · Critères de priorisation des missions : probabilité, impact, fréquence · Élaboration du Plan d\'Audit Annuel (PAA) · Définition du budget-temps et allocation des ressources · Comité d\'audit : rôle, communication et gouvernance · Exercice : construction d\'une cartographie des risques sur cas réel','duree'=>$s[1]],
                ['titre'=>'Phase de planification d\'une mission d\'audit','contenus'=>'Lettre de mission et ordre de mission · Prise de connaissance du domaine audité (entretiens, documentation) · Analyse des risques spécifiques et identification des points de contrôle · Programme de travail détaillé : objectifs, tests, responsables · Réunion d\'ouverture : déroulement et posture de l\'auditeur · Référentiels d\'évaluation : COSO, COBIT, normes sectorielles','duree'=>$s[2]],
                ['titre'=>'Techniques de collecte et d\'analyse des preuves','contenus'=>'Techniques d\'entretien structuré avec les audités · Observation, inspection physique et comptage · Confirmation externe (circularisation) · Sondages statistiques et échantillonnage · Tests de cheminement (walkthrough) et tests de détail · Documentation des travaux : papiers de travail, indexation et archivage · Logiciels d\'audit (IDEA, ACL, Excel avancé) : extractions et analyses','duree'=>$s[3]],
                ['titre'=>'Conduite des travaux de terrain : audit des processus clés','contenus'=>'Audit des achats-fournisseurs : séparation des tâches, approvisionnements non conformes · Audit de la paie : fantômes, surcharges, conformité sociale CNPS/OHADA · Audit de la trésorerie : séparation des fonctions, rapprochements, caisses · Audit des stocks : inventaires, valorisation, écarts · Audit du cycle clients-facturation : risques de fraude et de doublons · Feuilles de travail et grilles d\'analyse : modèles pratiques commentés','duree'=>$s[4]],
                ['titre'=>'Rédaction du rapport d\'audit et suivi des recommandations','contenus'=>'Structure type d\'un rapport d\'audit (synthèse dirigeant, constats, recommandations) · Règles de rédaction : clarté, objectivité, hiérarchisation par criticité · Côté « constat » : critère, condition, cause, conséquence · Niveau de criticité et priorisation des recommandations · Réunion de clôture : validation des constats et plans d\'action · Suivi périodique des recommandations : grille, indicateurs et reporting au comité','duree'=>$s[5]],
                ['titre'=>'Audit des systèmes d\'information et fraudes internes','contenus'=>'Spécificités de l\'audit informatique : contrôles généraux IT et contrôles applicatifs · Revue des droits d\'accès, journaux et paramètres de sécurité · Gestion des fraudes : typologies, signaux d\'alerte et investigation · Responsabilité de l\'auditeur face à la fraude (normes IIA 2120) · Plan de prévention des fraudes : contrôles clés à recommander · Exercice : audit d\'accès à un ERP (Sage, Odoo, SAP)','duree'=>$s[6]],
                ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : planification et rapport d\'une mission d\'audit complète sur cas inédit · Correction individualisée par le formateur auditeur certifié · Remise du certificat IBIG EDUFORM "Audit Interne IIA" et plan de développement post-formation','duree'=>$s[7]],
            ],
        };
    }

    /* ══ Analyse Financière ══ */
    if (preg_match('/analyse\s+financi[eè]re|diagnostic\s+financier|[eé]tats\s+financiers.*syscohada/ui', $nom)) {
        $s = $split(max(16, $heures), 7);
        return match($niveau) {
            'debutant' => [
                ['titre'=>'À quoi servent les états financiers ? Lire un bilan sans être comptable','contenus'=>'Le bilan : à gauche ce que possède l\'entreprise, à droite comment c\'est financé · Les grandes masses du bilan : actifs immobilisés, stocks, créances, trésorerie · Les capitaux propres et les dettes : comprendre la différence · Lecture des chiffres-clés d\'un bilan en 5 minutes · Exercice guidé : lire le bilan d\'une PME ivoirienne réelle','duree'=>$s[0]],
                ['titre'=>'Le compte de résultat : l\'entreprise a-t-elle gagné de l\'argent ?','contenus'=>'Chiffre d\'affaires, charges et résultat : les 3 concepts fondamentaux · La marge brute : ce qui reste après le coût des marchandises · Les charges d\'exploitation : loyer, salaires, fournitures · Le résultat net : c\'est le bénéfice ou la perte · Comment lire un compte de résultat en 10 minutes · Exercice : calculer le résultat net d\'une PME à partir des données brutes','duree'=>$s[1]],
                ['titre'=>'La trésorerie : pourquoi une entreprise rentable peut manquer de cash','contenus'=>'Différence entre résultat et trésorerie : le paradoxe expliqué · Encaissements vs décaissements : le flux de trésorerie simple · Délais clients et délais fournisseurs : leur impact sur la trésorerie · Le besoin en fonds de roulement (BFR) expliqué simplement · Prévoir ses besoins de trésorerie sur un mois · Exercice : analyser pourquoi une PME bénéficiaire est à court de cash','duree'=>$s[2]],
                ['titre'=>'Les ratios financiers essentiels : interpréter les chiffres','contenus'=>'Ratio de liquidité générale : l\'entreprise peut-elle payer ses dettes à court terme ? · Ratio d\'endettement : l\'entreprise est-elle trop endettée ? · Marge nette : combien gagne-t-on par franc de chiffre d\'affaires ? · Rotation des stocks : les marchandises partent-elles vite ? · Lire une grille de ratios et comprendre ce qu\'elle dit · Exercice : calculer et interpréter 5 ratios pour une PME ivoirienne','duree'=>$s[3]],
                ['titre'=>'Comparer deux exercices : analyser l\'évolution d\'une entreprise','contenus'=>'Variation en valeur absolue et en pourcentage · Tableau comparatif N vs N-1 · Signaux positifs et signaux d\'alarme dans l\'évolution des chiffres · Tendances : croissance, stagnation, dégradation · Comment poser les bonnes questions à un dirigeant sur ses chiffres · Exercice : analyser l\'évolution sur 2 ans d\'une PME ivoirienne','duree'=>$s[4]],
                ['titre'=>'Mon premier diagnostic financier : exercice complet guidé','contenus'=>'Remise d\'un dossier complet (bilan + compte de résultat d\'une PME) · Lecture et extraction des données clés · Calcul guidé des ratios essentiels · Rédaction d\'un paragraphe de diagnostic simple · Formuler une recommandation de base · Correction commentée individuelle','duree'=>$s[5]],
                ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve guidée : analyse d\'une liasse simple sur cas inédit · Correction individualisée et feedback détaillé · Remise du certificat IBIG EDUFORM "Analyse Financière Niveau Débutant"','duree'=>$s[6]],
            ],
            'expert' => [
                ['titre'=>'Modélisation financière avancée et prévisions pluriannuelles','contenus'=>'Construction d\'un modèle financier intégré : IS – Bilan – TFT articulés · Hypothèses de modélisation : macro-économiques, sectorielles et opérationnelles · Scénarios et analyses de sensibilité : Monte Carlo simplifié · Modélisation du BFR par composantes (DSO, DPO, DSI) · Covenants bancaires : modélisation et gestion des alertes · Exercice : construction d\'un modèle financier 3 ans pour une PME ivoirienne','duree'=>$s[0]],
                ['titre'=>'Évaluation d\'entreprise : méthodes avancées et cas acquisition','contenus'=>'DCF : construction des flux, taux d\'actualisation WACC en contexte africain · Multiples de valorisation : EV/EBITDA, PER, Price/Book par secteur OHADA · Actif net réévalué (ANR) : méthode et retraitements · Prime de contrôle et décote d\'illiquidité · Réconciliation des méthodes et fourchette de valeur · Exercice : évaluation complète d\'une PME en vue de cession','duree'=>$s[1]],
                ['titre'=>'Analyse de crédit avancée et rating interne','contenus'=>'Modèles de scoring avancés : Altman Z-Score adapté OHADA, modèles bancaires UEMOA · Analyse des flux de trésorerie disponibles pour le service de la dette (DSCR) · Ratios d\'endettement net (levier) et covenant covenants bancaires · Risques sectoriels et pays : intégration dans la notation · Rating interne : conception et calibration du modèle · Comité de crédit : présentation et défense d\'un dossier complexe','duree'=>$s[2]],
                ['titre'=>'Restructuration financière et gestion de crise de liquidité','contenus'=>'Diagnostic de détresse financière : signaux avancés et indicateurs de tension · Leviers de restructuration : cession d\'actifs, refinancement, réduction des charges · Négociation avec les créanciers : banques, fournisseurs, État · Plan de continuation et accord de restructuration · Procédures OHADA : règlement préventif, redressement judiciaire · Exercice : élaboration d\'un plan de restructuration pour une PME en difficulté','duree'=>$s[3]],
                ['titre'=>'Finance ESG et reporting non-financier pour les groupes africains','contenus'=>'Intégration des critères ESG dans l\'analyse financière · Comptabilité verte : Carbon Footprint, SBTi et liens avec les états financiers · Reporting de durabilité (GRI, CSRD pour les filiales d\'entreprises européennes) · Impact des risques climatiques sur la valorisation et les provisions · Green bonds et financement durable sur les marchés africains · Exercice : analyse des risques ESG d\'un groupe agroalimentaire ivoirien','duree'=>$s[4]],
                ['titre'=>'Présentation au CODIR et communication financière aux investisseurs','contenus'=>'Storytelling financier : transformer les chiffres en décisions stratégiques · Tableau de bord financier pour le CODIR : construction et animation · Communication investisseur : quarterly earnings call, communiqué de résultats · Relations avec les analystes et la Commission CREPMF · One-pager financier et mémorandum d\'information (IM) · Exercice : présentation de 15 minutes des résultats annuels à un jury simulant un CODIR','duree'=>$s[5]],
                ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : évaluation d\'entreprise + note de crédit + présentation CODIR sur cas inédit · Correction individualisée et feedback expert · Remise du certificat IBIG EDUFORM "Analyse Financière Expert"','duree'=>$s[6]],
            ],
            default => [
                ['titre'=>'Lecture et retraitement du bilan SYSCOHADA','contenus'=>'Structure du bilan SYSCOHADA : actifs immobilisés, circulants, trésorerie · Retraitements analytiques : crédit-bail, effets escomptés, écarts de conversion · Fonds de Roulement (FDR), Besoin en Fonds de Roulement (BFR) et Trésorerie Nette · Lecture des variations d\'une année sur l\'autre · Exercice sur une liasse fiscale ivoirienne réelle','duree'=>$s[0]],
                ['titre'=>'Analyse du compte de résultat et des soldes intermédiaires de gestion','contenus'=>'Structure du compte de résultat SYSCOHADA · Calcul des SIG : VA, EBE, EBIT, résultat financier, résultat net · Ratios de profitabilité : marge brute, marge opérationnelle, rentabilité nette · Analyse des charges fixes vs variables et du point mort · Exercice : calcul des SIG sur un compte de résultat de PME ivoirienne','duree'=>$s[1]],
                ['titre'=>'Tableau de flux de trésorerie et analyse de la liquidité','contenus'=>'Méthode directe et indirecte de construction des flux · Flux d\'exploitation, d\'investissement et de financement · Capacité d\'Autofinancement (CAF) et Free Cash Flow · Ratios de liquidité : générale, réduite, immédiate · Analyse de la solvabilité à court terme · Exercice pratique : construction du TFT à partir d\'une liasse','duree'=>$s[2]],
                ['titre'=>'Ratios de performance, d\'endettement et de rentabilité','contenus'=>'Ratios de structure financière : autonomie, endettement, capacité de remboursement · Ratios de rotation : stocks, créances clients, dettes fournisseurs · Rentabilité des capitaux propres (ROE) et des actifs (ROA) · Effet de levier financier et risque · Comparaison sectorielle : benchmarks sectoriels OHADA · Construction d\'une scorecard financière','duree'=>$s[3]],
                ['titre'=>'Scoring crédit et notation financière','contenus'=>'Modèles de scoring (Altman Z-Score, méthodes bancaires ivoiriennes) · Analyse qualitative complémentaire : gouvernance, marché, management · Matrice de cotation et grille de décision · Rapport de diagnostic financier : structure et rédaction professionnelle · Présentation des conclusions à un décideur ou à un comité de crédit','duree'=>$s[4]],
                ['titre'=>'Mise en pratique : cas intégraux sur liasses fiscales réelles','contenus'=>'Analyse de 2 à 3 dossiers complets d\'entreprises ivoiriennes de secteurs différents · Production d\'un rapport de diagnostic financier complet pour chaque cas · Comparaison des profils financiers et formulation de recommandations stratégiques · Travail individuel avec correction commentée et plan d\'amélioration','duree'=>$s[5]],
                ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : analyse complète d\'une liasse fiscale inédite avec rapport de diagnostic · Correction individualisée et feedback détaillé · Remise du certificat IBIG EDUFORM "Analyse Financière SYSCOHADA" et plan de développement post-formation','duree'=>$s[6]],
            ],
        };
    }

    /* ══ Power BI / Business Intelligence / Tableaux de bord ══ */
    if (preg_match('/power\s*bi|business\s+intelligence|tableau.{0,10}bord|reporting|data.*visualis/ui', $nom)
        && !preg_match('/data analyst/ui', $nom)) {
        $s = $split(max(16, $heures), 7);
        return match($niveau) {
            'debutant' => [
                ['titre'=>'C\'est quoi Power BI ? Pourquoi les entreprises l\'utilisent','contenus'=>'Power BI vs Excel : la différence expliquée simplement · À quoi sert un tableau de bord : voir l\'essentiel d\'un coup d\'œil · Les composantes de Power BI : Desktop, Service, Mobile · Télécharger et installer Power BI Desktop · Naviguer dans l\'interface : les 3 vues (rapport, données, modèle) · Exercice guidé : ouvrir un fichier exemple et explorer les visuels','duree'=>$s[0]],
                ['titre'=>'Importer ses premières données depuis Excel','contenus'=>'Ouvrir un fichier Excel dans Power BI · Choisir les tables ou feuilles à importer · Aperçu des données : est-ce que c\'est bien importé ? · Actualiser les données quand le fichier Excel change · Erreurs courantes à l\'import et comment les corriger · Exercice : importer un fichier de ventes ivoirien et vérifier les données','duree'=>$s[1]],
                ['titre'=>'Nettoyer ses données avec Power Query : les manipulations de base','contenus'=>'Ouvrir l\'éditeur Power Query · Supprimer les colonnes inutiles · Changer le type de données (nombre, texte, date) · Supprimer les lignes vides et les doublons · Renommer des colonnes · Exercice guidé : nettoyer un tableau de bord de 200 lignes','duree'=>$s[2]],
                ['titre'=>'Créer ses premiers graphiques et visuels','contenus'=>'Les 5 visuels essentiels : histogramme, courbe, camembert, carte, tableau · Glisser-déposer des champs sur le visuel · Changer les couleurs et le titre · Ajouter des étiquettes de données · Mettre en forme : police, taille, couleur · Exercice : créer 4 visuels à partir de données de ventes','duree'=>$s[3]],
                ['titre'=>'Assembler un tableau de bord simple et le rendre interactif','contenus'=>'Organiser les visuels sur une page · Ajouter un filtre (slicer) pour filtrer par date ou région · La magie des interactions : cliquer sur un graphique filtre les autres · Ajouter un titre, un logo et une couleur de fond · Exercice : assembler un dashboard de 4 visuels sur les ventes d\'une PME','duree'=>$s[4]],
                ['titre'=>'Partager son rapport avec son équipe','contenus'=>'Publier sur Power BI Service (en ligne) · Partager un lien avec un collègue · Exporter en PDF pour une réunion · Actualiser automatiquement les données · Exercice final : publier et partager son premier tableau de bord','duree'=>$s[5]],
                ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve guidée : créer un tableau de bord complet sur cas inédit · Correction individualisée et feedback du formateur · Remise du certificat IBIG EDUFORM "Power BI Niveau Débutant"','duree'=>$s[6]],
            ],
            'expert' => [
                ['titre'=>'Architecture BI avancée : lakehouse, dataflows et pipelines de données','contenus'=>'Architecture moderne de données : Bronze/Silver/Gold layers · Dataflows Gen2 : transformation centralisée et réutilisable · Power BI Datamart : base SQL managée pour les équipes · Integration avec Azure Synapse Analytics et Fabric · Gestion des gateways : on-premises, personal, VNet · Incrément al refresh et partitions : traitement des grandes tables','duree'=>$s[0]],
                ['titre'=>'DAX avancé : patterns complexes, optimisation et débogage','contenus'=>'Context transition et CALCULATE : maîtrise approfondie · Table fonctions avancées : SUMMARIZE, ADDCOLUMNS, GENERATE · Patterns Time Intelligence : YTD, QTD, MTD, comparaisons glissantes · Mesures semi-additives : inventaires, soldes, positions ouvertes · DAX Studio : profiler, mesurer et optimiser les requêtes DAX · VertiPaq Analyzer : compression, cardinalité et stratégies de modélisation','duree'=>$s[1]],
                ['titre'=>'Gouvernance Power BI Enterprise : sécurité, RLS et administration','contenus'=>'Row-Level Security statique et dynamique : patterns avancés · Object-Level Security (OLS) : masquer des colonnes par rôle · Workspace Premium et Fabric : capacités, licences et allocation · Pipelines de déploiement : Dev → Test → Prod · Audit logs et gouvernance : qui accède à quoi · Endorsement, certification et catalogue de données · XMLA Endpoint : connexion Excel, SSE, third-party tools','duree'=>$s[2]],
                ['titre'=>'Embedded Analytics et Power BI API','contenus'=>'Power BI Embedded : intégrer des rapports dans une application web · Authentication flows : service principal, App-owns-data, User-owns-data · Power BI REST API : créer, actualiser, exporter par programme · Power Automate + Power BI : alertes, rapports automatiques par email · Custom visuals développement : introduction à pbiviz · Export automatique vers PDF/Excel via API','duree'=>$s[3]],
                ['titre'=>'Fabric et l\'avenir de la BI Microsoft','contenus'=>'Microsoft Fabric : vision unifiée Data + BI + AI · OneLake : stockage unifié et Delta Parquet · Real-Time Analytics : Eventstream et KQL Database · Direct Lake mode : performance sans import · Copilot pour Power BI : génération de visuels et mesures DAX par IA · Roadmap et certification PL-300 / DP-600 · Migration d\'un tenant Power BI vers Fabric','duree'=>$s[4]],
                ['titre'=>'Centre d\'Excellence BI : mise en place et gouvernance organisationnelle','contenus'=>'Rôle et structure d\'un Centre d\'Excellence (COE) BI · Modèle opérationnel : centralisé, décentralisé, fédéré · Formation et enablement des utilisateurs métier · Templates corporate : charte graphique, naming conventions · Mesure de la maturité BI de l\'organisation · ROI de la BI : calcul et présentation au CODIR · Plan de transformation BI sur 2 ans','duree'=>$s[5]],
                ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : solution BI enterprise complète (modèle + DAX avancé + sécurité + publication) sur cas inédit · Correction par un formateur Microsoft Certified · Remise du certificat IBIG EDUFORM "Power BI Expert"','duree'=>$s[6]],
            ],
            default => [
                ['titre'=>'Prise en main de Power BI Desktop et connexion aux sources de données','contenus'=>'Interface Power BI Desktop : navigation, ruban et volets · Connexion aux sources : Excel, CSV, SQL Server, SharePoint, API Web · Import vs DirectQuery vs Live Connection : choisir le bon mode · Actualisation des données et gestion des credentials · Exercice : connexion à un fichier Excel de données ivoiriennes','duree'=>$s[0]],
                ['titre'=>'Nettoyage et transformation des données avec Power Query','contenus'=>'Éditeur Power Query : fonctionnalités et flux de transformation · Suppression des doublons, valeurs nulles et colonnes inutiles · Fractionnement, fusion et pivotement des colonnes · Ajout de colonnes calculées et colonnes conditionnelles · Fusion de requêtes (jointures) et ajout de tables · Étapes appliquées : traçabilité et maintenance · Exercice : nettoyage d\'un export SIRH ou comptable','duree'=>$s[1]],
                ['titre'=>'Modélisation des données : relations, schéma étoile et hiérarchies','contenus'=>'Vue Modèle : création et gestion des relations · Schéma en étoile vs flocon de neige · Tables de faits et tables de dimensions · Cardinalité et direction du filtre croisé · Tables de dates : création automatique et personnalisée · Hiérarchies personnalisées pour navigation intuitive · Exercice : construction d\'un modèle de données financières','duree'=>$s[2]],
                ['titre'=>'DAX : mesures, colonnes calculées et KPIs avancés','contenus'=>'Différence mesure / colonne calculée · Fonctions de base : SUM, COUNT, AVERAGE, DIVIDE · Fonctions temporelles : SAMEPERIODLASTYEAR, TOTALYTD, CALCULATE · Fonctions de filtrage : ALL, FILTER, RELATED · Mesures de type KPI : taux de croissance, cumul, variation · Variables DAX (VAR) et débogage · Exercice : création d\'un tableau de bord RH ou commercial complet','duree'=>$s[3]],
                ['titre'=>'Visualisations avancées, mise en forme et interactivité','contenus'=>'Visuels natifs : histogramme, courbe, carte, tableau, matrice, jauge · Mise en forme conditionnelle et arrière-plans · Slicers, filtres et drill-through : interactivité avancée · Visuels personnalisés depuis AppSource · Bookmarks et boutons de navigation · Thèmes et charte graphique corporate · Exercice : dashboard de suivi de performance commerciale','duree'=>$s[4]],
                ['titre'=>'Publication, partage sur Power BI Service et gouvernance','contenus'=>'Publication sur Power BI Service (workspace, apps) · Partage de rapports : rôles, liens, intégration SharePoint · Actualisation planifiée et gateway de données · Sécurité au niveau des lignes (Row-Level Security) · Power BI Mobile : optimisation pour smartphone · Bonnes pratiques de gouvernance et documentation des modèles','duree'=>$s[5]],
                ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : conception d\'un tableau de bord complet (modèle + DAX + visuels + publication) sur jeu de données inédit · Correction individualisée et feedback détaillé du formateur · Remise du certificat IBIG EDUFORM "Power BI / BI" et plan de développement post-formation','duree'=>$s[6]],
            ],
        };
    }

    /* ══ Droit du travail ivoirien ══ */
    if (preg_match('/droit\s+du\s+travail|code\s+du\s+travail.*ivoir|contrat\s+de\s+travail|licenciement|prud\'?hom/ui', $nom)) {
        $s = $split(max(16, $heures), 7);
        return match($niveau) {
            'debutant' => [
                ['titre'=>'C\'est quoi le droit du travail ? À quoi ça sert en tant qu\'employeur ou salarié','contenus'=>'Le droit du travail : protéger à la fois l\'employeur et le salarié · Le Code du Travail ivoirien : là où trouver les règles · Qui est concerné : salarié, stagiaire, sous-traitant, bénévole ? · Les institutions : Inspection du Travail, Tribunal du Travail, CNPS · Les grandes conventions collectives en Côte d\'Ivoire · Exercice : identifier si des situations courantes relèvent du droit du travail','duree'=>$s[0]],
                ['titre'=>'Le contrat de travail : les bases essentielles','contenus'=>'CDI vs CDD : la différence fondamentale · La période d\'essai : durée, renouvellement, résiliation · Les mentions obligatoires d\'un contrat de travail · Ce que peut et ne peut pas faire l\'employeur dans le contrat · Reconnaître un contrat risqué ou illégal · Exercice : analyser 2 contrats de travail et identifier les problèmes','duree'=>$s[1]],
                ['titre'=>'Les horaires, congés et rémunération : les droits fondamentaux','contenus'=>'La durée légale du travail en Côte d\'Ivoire · Les heures supplémentaires : quand sont-elles dues et à quel tarif ? · Le SMIG 2024 : quel est-il et qui l\'applique ? · Les congés payés : combien de jours et comment les calculer ? · Les jours fériés légaux en CI : liste et règles · Exercice : calculer les droits à congés d\'un salarié','duree'=>$s[2]],
                ['titre'=>'Comment se passe un licenciement ? Les règles à connaître','contenus'=>'Qu\'est-ce qu\'un motif valable de licenciement ? · Les étapes obligatoires : convocation, entretien, notification · Les délais de préavis selon la catégorie professionnelle · Le calcul de l\'indemnité de licenciement · Ce qui se passe si la procédure est mal suivie · Exercice : vérifier si un licenciement fictif est légal ou non','duree'=>$s[3]],
                ['titre'=>'Les obligations sociales : CNPS, CMU et Inspection du Travail','contenus'=>'Immatriculer son entreprise à la CNPS : comment faire · Les cotisations CNPS : qui paie quoi · La CMU : affiliation obligatoire pour les salariés · Que vérifie l\'Inspecteur du Travail lors d\'un contrôle · Les papiers à avoir en ordre : registre du personnel, affichages obligatoires · Exercice : check-list de conformité sociale pour une PME de 5 salariés','duree'=>$s[4]],
                ['titre'=>'Les conflits au travail : les résoudre sans aller au tribunal','contenus'=>'L\'avertissement et la mise à pied : procédure simple · Les délégués du personnel : leur rôle et comment travailler avec eux · La médiation et la conciliation : alternatives au tribunal · Quand saisir le Tribunal du Travail : derniers recours · Les erreurs les plus courantes des employeurs ivoiriens · Exercice : résoudre 3 situations conflictuelles courantes','duree'=>$s[5]],
                ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : traitement guidé d\'une situation RH (embauche → conflit → rupture) sur cas simple · Correction individualisée et feedback · Remise du certificat IBIG EDUFORM "Droit du Travail Niveau Débutant"','duree'=>$s[6]],
            ],
            'expert' => [
                ['titre'=>'Stratégie RH et politique sociale d\'entreprise : vision globale et conformité avancée','contenus'=>'Architecture de la politique sociale : interaction Code du Travail, CCI et conventions sectorielles · Réforme permanente du droit ivoirien : veille légale et impact sur la stratégie RH · Conventions collectives d\'entreprise : négociation, contenu et dépôt · Politique de rémunération globale : structure, équité interne/externe et conformité · Audit social préventif : identification des passifs sociaux · Plan de mise en conformité sociale sur 12 mois','duree'=>$s[0]],
                ['titre'=>'Gestion avancée des contrats atypiques et mobilité internationale','contenus'=>'Contrat de prestations de services vs contrat de travail : requalification et risques · Portage salarial et statut d\'auto-entrepreneur en droit ivoirien · Expatriés et détachés : permis de travail, convention fiscale CI-France, sécurité sociale · Télétravail transfrontalier : risques juridiques et bonnes pratiques · Clauses avancées : non-concurrence, exclusivité, cession de droits intellectuels · Exercice : rédiger un contrat d\'expatrié conforme','duree'=>$s[1]],
                ['titre'=>'Restructurations, plans sociaux et gestion des suppressions de postes','contenus'=>'Conditions légales du licenciement économique collectif · Procédure de consultation des délégués du personnel · Plan de Sauvegarde de l\'Emploi (PSE) : contenu et négociation · Cellule de reclassement et outplacement · Calcul des indemnités supra-légales et négociation globale · Contentieux post-licenciement : gestion du risque et provisions comptables · Exercice : montage d\'un plan de restructuration pour 15 suppressions de postes','duree'=>$s[2]],
                ['titre'=>'Relations collectives avancées : négociation syndicale et accords collectifs','contenus'=>'Dynamique syndicale en Côte d\'Ivoire : principaux syndicats et sectorisation · Négociation d\'un accord collectif d\'entreprise : processus et clauses types · Gestion d\'un préavis de grève : obligations légales et stratégie de communication · Plan de continuité de service en cas de grève · Accords de performance collective : flexibilité et contreparties · Jurisprudence récente du Tribunal du Travail d\'Abidjan : tendances','duree'=>$s[3]],
                ['titre'=>'Droit disciplinaire avancé et gestion des situations sensibles','contenus'=>'Harcèlement moral et sexuel : définition légale, procédure et responsabilité de l\'employeur · Faute grave et lourde : jurisprudence et risques de requalification · Mise à pied conservatoire vs disciplinaire : procédures et délais · Lanceurs d\'alerte : protection légale et procédure interne · Procédures pour salariés protégés (délégués, femmes enceintes) : autorisation inspectorale · Exercice : traitement d\'une procédure disciplinaire pour faute grave d\'un délégué du personnel','duree'=>$s[4]],
                ['titre'=>'Pilotage du risque juridique social et tableau de bord conformité','contenus'=>'Cartographie des risques sociaux de l\'entreprise : matrice probabilité/impact · Provisionnement comptable des passifs sociaux (IAS 19 / SYSCOHADA) · Tableau de bord social : indicateurs de conformité, de climate social, de risque contentieux · Reporting social FDFP, CNPS, Inspection du Travail · Préparation et gestion d\'un contrôle de l\'Inspection du Travail · Gestion du contentieux social : négociation de sortie et relation avec le conseil juridique','duree'=>$s[5]],
                ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : dossier complexe (restructuration + contentieux + accord collectif) sur cas inédit · Correction individualisée et feedback expert · Remise du certificat IBIG EDUFORM "Droit du Travail Expert"','duree'=>$s[6]],
            ],
            default => [
                ['titre'=>'Sources et architecture du droit du travail ivoirien','contenus'=>'Code du Travail ivoirien (loi n°2015-532) : structure et champ d\'application · Convention Collective Interprofessionnelle (CCI) : portée et application · Conventions collectives sectorielles (BTP, banque, transport, commerce) · Inspection du Travail : rôle, pouvoirs et procédure d\'inspection · Droit OHADA et droit social : articulation et primauté','duree'=>$s[0]],
                ['titre'=>'Les contrats de travail : types, rédaction et clauses','contenus'=>'CDI : définition, essai, mentions obligatoires, formalisation · CDD : conditions de recours, durée maximale, renouvellement et risques de requalification · Contrat saisonnier, d\'apprentissage, de sous-traitance · Clauses spéciales : non-concurrence, confidentialité, mobilité · Travailleur étranger en Côte d\'Ivoire : permis de travail et procédures · Exercice pratique : rédaction et analyse de contrats types','duree'=>$s[1]],
                ['titre'=>'Temps de travail, congés, absences et rémunération','contenus'=>'Durée légale du travail : heures normales, heures supplémentaires, taux de majoration · Organisation du temps : travail de nuit, week-end, aménagements · SMIG 2024 et grilles de classification professionnelle · Congés payés : calcul, période, indemnité de congés payés · Jours fériés légaux en Côte d\'Ivoire · Absences : maladie, maternité, accident de travail, autorisations spéciales','duree'=>$s[2]],
                ['titre'=>'Rupture du contrat de travail : procédures légales','contenus'=>'Démission : conditions, préavis, conséquences juridiques · Licenciement individuel : motifs réels et sérieux, procédure (convocation, entretien, notification) · Licenciement économique : conditions, consultation des délégués, plan social · Départ à la retraite et mise à la retraite · Solde de tout compte : calcul (préavis, congés, indemnités) · Nullité du licenciement et réintégration · Exercice : traitement d\'une procédure de licenciement complète','duree'=>$s[3]],
                ['titre'=>'Sanctions disciplinaires, contentieux et rôle des délégués du personnel','contenus'=>'Pouvoir disciplinaire de l\'employeur : avertissement, mise à pied, licenciement · Procédure disciplinaire conforme au Code du Travail · Délégués du personnel : élections, attributions, protection · Syndicats et liberté syndicale · Tribunal du Travail : compétence, procédure de conciliation et jugement · Prescription et calcul des dommages-intérêts · Exercice : simulation d\'un litige prud\'homal','duree'=>$s[4]],
                ['titre'=>'Conformité sociale : CNPS, CMU et obligations déclaratives','contenus'=>'Obligations CNPS : immatriculation, cotisations, déclarations trimestrielles · Couverture Maladie Universelle (CMU) : affiliation et gestion · Accidents du travail et maladies professionnelles : déclaration et prise en charge CNPS · FDFP : obligation de formation et déclaration annuelle · Contrôle CNPS et Inspection du Travail : droits et obligations · Tableau de bord conformité sociale : check-list pratique','duree'=>$s[5]],
                ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : traitement d\'un cas RH complexe (recrutement → licenciement → contentieux) avec production de documents · Correction individualisée et feedback détaillé · Remise du certificat IBIG EDUFORM "Droit du Travail Ivoirien" et plan de mise en conformité','duree'=>$s[6]],
            ],
        };
    }

    /* ══ Paie ivoirienne / CNPS / Bulletins de salaire ══ */
    if (preg_match('/paie\s+ivoi|bulletin\s+de\s+(paie|salaire)|cnps.*irvm|calcul\s+salaire|gestion\s+(de\s+la\s+)?paie/ui', $nom)) {
        $s = $split(max(16, $heures), 7);
        return match($niveau) {
            'debutant' => [
                ['titre'=>'C\'est quoi la paie ? Comprendre comment un salarié est rémunéré','contenus'=>'Le salaire : ce que reçoit le salarié vs ce que coûte le salarié à l\'entreprise · Salaire brut, salaire net, charges patronales : la différence expliquée · Le bulletin de paie : à quoi servent toutes ces lignes ? · Les acteurs : employeur, salarié, CNPS, DGI · Le calendrier de paie en Côte d\'Ivoire · Exercice : lire et expliquer un bulletin de paie réel ligne par ligne','duree'=>$s[0]],
                ['titre'=>'Les éléments constitutifs du salaire : base, primes et indemnités','contenus'=>'Le salaire de base : ce que dit la convention collective · Les primes courantes : ancienneté, assiduité, responsabilité · Les indemnités exonérées : transport, logement, repas · Ce qui est imposable et ce qui ne l\'est pas · Le SMIG 2024 : quel est le minimum légal ? · Exercice : décomposer le salaire d\'un agent de PME','duree'=>$s[1]],
                ['titre'=>'Les cotisations CNPS : comprendre qui paie quoi','contenus'=>'La CNPS : son rôle et ce qu\'elle couvre · Les 3 branches de cotisation : retraite, AT/MP, prestations familiales · Taux employeur et taux salarié 2024 · Calculer la part salariale et la part patronale sur un salaire · S\'immatriculer à la CNPS : étapes pratiques · Exercice guidé : calculer les cotisations CNPS d\'un bulletin simple','duree'=>$s[2]],
                ['titre'=>'Les impôts sur salaire : ITS et IRVM expliqués simplement','contenus'=>'L\'ITS (Impôt sur le Traitement des Salaires) : barème simplifié · Calcul de la retenue ITS sur un salaire · L\'IRVM sur dividendes et intérêts : quand l\'appliquer · Les abattements et déductions courantes · Comment déclarer à la DGI chaque mois · Exercice : calculer l\'ITS d\'un salarié à 300 000, 500 000 et 1 000 000 FCFA','duree'=>$s[3]],
                ['titre'=>'Les congés, absences et solde de tout compte','contenus'=>'Congés payés : combien de jours et comment calculer l\'indemnité · Absences : maladie, maternité, accident de travail · Le solde de tout compte : qu\'est-ce qu\'on doit au salarié qui part ? · Calcul du préavis et de l\'indemnité de licenciement · Les papiers à établir lors d\'un départ · Exercice guidé : calculer le solde de tout compte d\'un départ simple','duree'=>$s[4]],
                ['titre'=>'Mon premier bulletin de paie complet : étape par étape','contenus'=>'Utiliser le modèle Excel de paie fourni · Saisir les éléments du bulletin (brut, cotisations, impôts, net) · Vérifier les calculs · Éditer et envoyer le bulletin au salarié · Archiver les bulletins · Exercice : établir 3 bulletins de paie pour des profils différents','duree'=>$s[5]],
                ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : établir 5 bulletins guidés sur données inédites · Correction individualisée et feedback · Remise du certificat IBIG EDUFORM "Paie Niveau Débutant"','duree'=>$s[6]],
            ],
            'expert' => [
                ['titre'=>'Stratégie de rémunération globale et politique salariale','contenus'=>'Architecture de la rémunération totale : fixe, variable, avantages, épargne · Équité interne : grilles de salaire, classification et pesée des postes · Équité externe : benchmarking salarial par secteur en Côte d\'Ivoire · Rémunération variable : primes à la performance, bonus, intéressement · Budget masse salariale : construction, pilotage et analyse des écarts · Présentation de la politique salariale au CODIR','duree'=>$s[0]],
                ['titre'=>'Paie complexe : dirigeants, expatriés, hauts salaires et stock-options','contenus'=>'Rémunération du dirigeant actionnaire : arbitrage dividendes/salaires · Paie des expatriés : convention de détachement, calcul du différentiel fiscal CI-France · Hauts salaires : gestion des plafonds de cotisation CNPS et optimisation · Avantages en nature des cadres supérieurs : valorisation et déclaration · Stock-options et actions gratuites : traitement fiscal et social · Exercice : établir le bulletin d\'un DG expatrié avec une rémunération mixte','duree'=>$s[1]],
                ['titre'=>'Provisionnement des charges sociales et clôture annuelle','contenus'=>'Provision pour congés payés : méthode de calcul et comptabilisation SYSCOHADA · Provision pour indemnités de fin de carrière (IAS 19 adapté) · Régularisation annuelle CNPS et gestion des écarts · Déclaration annuelle des salaires (DAS) : périmètre et délais · Bilan social : indicateurs obligatoires et tableau de bord RH · Exercice : établir le bilan social annuel d\'une entreprise de 50 salariés','duree'=>$s[2]],
                ['titre'=>'Contrôle CNPS et vérification sociale : défense et régularisation','contenus'=>'Procédure de contrôle CNPS : droits et obligations de l\'employeur · Redressement CNPS : bases de calcul, contestation et transaction · Gestion des rappels de cotisations : impact financier et plans de régularisation · Contrôle de l\'Inspection du Travail : dossiers à préparer · Délai de prescription en matière sociale · Exercice : réponse à un redressement CNPS sur 3 exercices','duree'=>$s[3]],
                ['titre'=>'Digitalisation de la paie : SIRH, automatisation et conformité','contenus'=>'Sélection et déploiement d\'un SIRH paie adapté aux PME africaines · Paramétrage des règles salariales sous Sage Paie / Odoo / Silae · Intégration paie-comptabilité-CNPS : automatisation des exports · API DGI-net pour la télédéclaration automatique · Protection des données de paie (ARTCI) : obligations légales · Tableau de bord paie : indicateurs de pilotage et détection des anomalies','duree'=>$s[4]],
                ['titre'=>'Conduite du changement de SIRH et formation des gestionnaires de paie','contenus'=>'Conduite de projet d\'implémentation d\'un logiciel de paie · Formation des gestionnaires de paie aux nouvelles procédures · Gestion de la transition : double traitement et bascule · Contrôles post-migration : fiabilité des données · Plan de continuité de la paie (PCA) · Formation et certification interne des équipes paie','duree'=>$s[5]],
                ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : dossier complexe de paie (expatrié + dirigeant + redressement CNPS) sur cas inédit · Correction individualisée et feedback expert · Remise du certificat IBIG EDUFORM "Paie Expert"','duree'=>$s[6]],
            ],
            default => [
                ['titre'=>'Cadre légal de la paie en Côte d\'Ivoire','contenus'=>'Code du Travail ivoirien et ses dispositions salariales · SMIG 2024 et conventions collectives sectorielles · Classification professionnelle et grilles de salaire · Registre du personnel et dossier salarié obligatoires · Sanctions en cas de non-conformité sociale · Exercice : vérification de la conformité d\'une structure de rémunération','duree'=>$s[0]],
                ['titre'=>'Structure du bulletin de paie et éléments constitutifs du salaire','contenus'=>'Mentions obligatoires du bulletin de paie · Salaire de base, heures supplémentaires et primes · Éléments bruts : calcul du salaire brut imposable et non imposable · Avantages en nature : valorisation et traitement social/fiscal · Indemnités exonérées (transport, logement, repas) vs imposables · Exercice : décomposition et vérification d\'un bulletin de paie réel','duree'=>$s[1]],
                ['titre'=>'Cotisations CNPS : retraite, AT/MP et prestations familiales','contenus'=>'Architecture des cotisations CNPS : taux employeur et salarié 2024 · Cotisation retraite CNPS : assiette, taux et plafonds · Accidents du Travail et Maladies Professionnelles (AT/MP) · Prestations familiales : allocations, congé maternité · Déclaration Nominative des Salaires (DNS) : procédure et délais · Régularisation annuelle CNPS et rapprochement comptes · Exercice : calcul des cotisations sur plusieurs bulletins','duree'=>$s[2]],
                ['titre'=>'IRVM et retenues à la source sur salaires','contenus'=>'Impôt sur les Revenus des Valeurs Mobilières (IRVM) : champ, base et taux · Impôt sur le Traitement des Salaires (ITS/IRPP) : barème progressif 2024 · Calcul de la retenue mensuelle et abattements · Traitement fiscal des primes, gratifications et indemnités de départ · Déclaration mensuelle à la DGI (formulaire) · Exercice : calcul de l\'IRVM et de l\'ITS sur un ensemble de salariés','duree'=>$s[3]],
                ['titre'=>'Variables de paie, congés payés et solde de tout compte','contenus'=>'Gestion des absences : maladie, maternité, accident de travail · Congés payés : base de calcul, indemnité (maintien vs 1/10) et provision · Indemnités de fin de contrat : préavis, licenciement, départ à la retraite · Calcul du solde de tout compte complet · Charges patronales totales : coût global d\'un salarié · Exercice : établissement du solde de tout compte d\'un départ','duree'=>$s[4]],
                ['titre'=>'Logiciels de paie et déclarations périodiques','contenus'=>'Sage Paie 100 (ou Sage i7) : paramétrage, saisie et édition des bulletins · Paie sous Excel : modèles avancés et automatisation · Déclaration trimestrielle CNPS et déclaration annuelle (bilan social) · Télédéclaration sur DGI-net : ITS et charges salariales · Archivage des bulletins et délais légaux de conservation · Exercice : traitement d\'une paie complète de 10 salariés','duree'=>$s[5]],
                ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : établissement d\'une paie mensuelle complète (bulletins + déclarations) sur données inédites · Correction individualisée et feedback détaillé · Remise du certificat IBIG EDUFORM "Paie Ivoirienne" et plan de mise en conformité','duree'=>$s[6]],
            ],
        };
    }

    /* ══ Recrutement / Conduite des entretiens ══ */
    if (preg_match('/recrutement|conduite\s+d.{1,5}entretien|entretien\s+structur[eé]|talent\s+acquisition|s[eé]lection\s+de\s+candidat/ui', $nom)) {
        $s = $split(max(14, $heures), 6);
        return match($niveau) {
            'debutant' => [
                ['titre'=>'C\'est quoi le recrutement ? Pourquoi une bonne embauche change tout','contenus'=>'La différence entre embaucher vite et embaucher bien · Le coût d\'une mauvaise embauche pour une PME · Les étapes d\'un recrutement de A à Z expliquées simplement · Qui fait quoi : le responsable RH, le manager, le dirigeant · Les erreurs classiques des PME africaines dans le recrutement · Exercice : analyser 2 recrutements réels (un réussi, un raté) et comprendre pourquoi','duree'=>$s[0]],
                ['titre'=>'Décrire le poste : rédiger une fiche de poste simple et efficace','contenus'=>'À quoi sert une fiche de poste concrètement · Les 5 éléments incontournables d\'une fiche de poste · Différence entre missions (ce qu\'on fait) et compétences (ce qu\'on sait faire) · Les qualifications obligatoires vs les qualifications souhaitées · Comment éviter de rédiger une fiche de poste irréaliste · Exercice guidé : rédiger la fiche de poste d\'un poste courant (comptable, commercial, assistant RH)','duree'=>$s[1]],
                ['titre'=>'Trouver des candidats : annonces, réseaux et canaux ivoiriens','contenus'=>'Rédiger une offre d\'emploi claire et attrayante · Les plateformes gratuites et payantes en Côte d\'Ivoire · Publier sur WhatsApp, LinkedIn et les groupes professionnels · La cooptation : demander à ses équipes de recommander · L\'approche directe simple : contacter quelqu\'un qu\'on connaît · Exercice : rédiger et diffuser une offre d\'emploi fictive sur 3 canaux','duree'=>$s[2]],
                ['titre'=>'Lire les CVs et choisir qui recevoir','contenus'=>'Comment lire un CV en 30 secondes : les 3 zones à regarder en premier · Les signaux positifs et les signaux d\'alerte sur un CV · Construire une grille de présélection simple · Comment classer les candidats sans se laisser guider par ses préférences · Communiquer avec les candidats : le message de convocation et le refus poli · Exercice : présélectionner 10 CVs réels avec la grille fournie','duree'=>$s[3]],
                ['titre'=>'Mener mon premier entretien : préparer et conduire la rencontre','contenus'=>'Comment préparer l\'entretien : les 5 questions à préparer avant · Accueillir le candidat et mettre en confiance · Poser des questions ouvertes et laisser le candidat parler · Les 3 choses à ne jamais demander (questions illicites en droit ivoirien) · Prendre des notes pendant l\'entretien · Évaluer après : ma grille de synthèse pour comparer les candidats · Exercice : jeu de rôle entretien complet avec feedback','duree'=>$s[4]],
                ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : processus de recrutement complet (fiche de poste + annonce + présélection + guide d\'entretien) sur un cas inédit · Correction individualisée et feedback · Remise du certificat IBIG EDUFORM "Recrutement Niveau Débutant"','duree'=>$s[5]],
            ],
            'expert' => [
                ['titre'=>'Stratégie d\'acquisition de talents et marque employeur en contexte africain','contenus'=>'People strategy alignée sur la stratégie d\'entreprise : planification des effectifs · Marque employeur : diagnostic, construction et déploiement (EVP) · Talent mapping : identifier les postes critiques et les profils rares · Sourcing stratégique : chasse de têtes, plateformes africaines, réseaux diaspora · Talent pools et viviers : construction et animation d\'une communauté de candidats · Métriques recrutement : TTFH, coût par embauche, qualité d\'embauche (QoH)','duree'=>$s[0]],
                ['titre'=>'Recrutement par compétences avancé : psychométrie et assessment','contenus'=>'Modèles de compétences : conception, validation et intégration au système RH · Méthode STAR avancée : questions de deep-dive et confrontation des réponses · Tests psychométriques valides : SHL, Predictive Index, Hogan Assessment · Assessment Center professionnel : construction des exercices (in-tray, jeu de rôle, présentation) · Calibration inter-évaluateurs : biais cognitifs et accord inter-juge · Décision basée sur les données : pondération et scorecard de décision','duree'=>$s[1]],
                ['titre'=>'Sourcing executive et recrutement de cadres supérieurs','contenus'=>'Recrutement C-suite : spécificités et risques d\'une nomination stratégique · Cabinets de recrutement : sélectionner, briefer et gérer la relation · Executive search : techniques de mapping et d\'approche directe · Due diligence candidat : vérification de références structurée et background check · Négociation d\'une rémunération de cadre supérieur : fixe, variable, avantages · Intégration (onboarding) des dirigeants : les 100 premiers jours','duree'=>$s[2]],
                ['titre'=>'People analytics et automatisation du recrutement','contenus'=>'ATS (Applicant Tracking Systems) : sélection, paramétrage et adoption · IA en recrutement : présélection automatisée, scoring prédictif, chatbots · People analytics : prédiction du turnover, modèles de performance · RGPD et ARTCI : conformité des données de candidature · Tableaux de bord recrutement sous Power BI : KPIs et visualisation · Recrutement inclusif : supprimer les biais algorithmiques et structurels','duree'=>$s[3]],
                ['titre'=>'Onboarding stratégique et rétention des talents clés','contenus'=>'Architecture d\'un programme d\'onboarding à 90 jours · Rétention des hauts potentiels : plans de développement et successeurs · Entretiens de stay et d\'exit : analyse et exploitation des données · Politiques de mobilité interne : conditions de succès · Plans de succession : identification et développement des N+1 · Gestion des départs des talents critiques : risk mitigation et knowledge transfer','duree'=>$s[4]],
                ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : stratégie de recrutement complète (EVP + sourcing + assessment center + onboarding) pour un poste de direction sur cas inédit · Correction individualisée et feedback expert · Remise du certificat IBIG EDUFORM "Recrutement Expert"','duree'=>$s[5]],
            ],
            default => [
                ['titre'=>'Analyse du besoin et construction de la fiche de poste','contenus'=>'Diagnostic du besoin : création de poste vs remplacement vs réorganisation · Analyse du poste : missions, activités, compétences requises · Rédaction de la fiche de poste (profil compétences, niveau RAQC) · Définition du profil cible et critères de sélection objectifs · Grille de pondération des critères · Exercice : rédaction d\'une fiche de poste sur un cas réel du bénéficiaire','duree'=>$s[0]],
                ['titre'=>'Sourcing, rédaction des annonces et diffusion multicanale','contenus'=>'Rédaction d\'une offre d\'emploi attractive et conforme à la loi (non-discrimination) · Canaux de diffusion en Côte d\'Ivoire : JobnetAfrica, LinkedIn, AfrikTalent, réseaux professionnels · Chasse de têtes et approche directe sur LinkedIn · Bases de candidatures internes et CVthèques · Cooptation : conception d\'un programme de recommandation · Mesure de l\'efficacité des sources de recrutement','duree'=>$s[1]],
                ['titre'=>'Présélection des candidatures et entretien téléphonique','contenus'=>'Grille de présélection CV : critères discriminants et critères différenciants · Prise en compte du biais de sélection : stéréotypes, halo, projection · Entretien téléphonique de qualification : grille et durée standard · Tests à distance : QCM de connaissances, exercice pratique en ligne · Réponses aux candidats non retenus : communication professionnelle · Exercice : simulation de présélection sur un lot de CV','duree'=>$s[2]],
                ['titre'=>'Conduite de l\'entretien structuré par compétences (méthode STAR)','contenus'=>'Types d\'entretiens : non structuré, semi-structuré, structuré par compétences · Méthode STAR : Situation, Tâche, Action, Résultat · Guide d\'entretien : construction des questions comportementales · Posture de l\'interviewer : écoute active, neutralité, relances · Pièges de l\'entretien : questions illicites et risques juridiques · Exercice pratique : simulation d\'entretien filmée avec débriefing immédiat','duree'=>$s[3]],
                ['titre'=>'Tests, évaluation psychométrique et décision de recrutement','contenus'=>'Tests techniques et exercices pratiques métier · Tests psychométriques : raisonnement, personnalité (DISC, MBTI), valeurs · Assessment Center : mise en situation collective, jeu de rôle · Grille de synthèse d\'évaluation et aide à la décision · Présentation au jury et décision finale objective · Offre d\'embauche, période d\'essai et formalités administratives · Onboarding : plan d\'intégration du nouveau collaborateur','duree'=>$s[4]],
                ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : conception d\'un processus de recrutement complet (fiche de poste → guide d\'entretien → grille de synthèse) sur un cas inédit · Correction individualisée et feedback détaillé · Remise du certificat IBIG EDUFORM "Recrutement & Entretiens" et plan de mise en œuvre','duree'=>$s[5]],
            ],
        };
    }

    /* ══ Gestion de Projet / PMP / PMI / PRINCE2 ══ */
    if (preg_match('/gestion\s+de\s+projet|pmp\b|pmbok|prince\s*2|m[eé]thode\s+agile|agile.*scrum|scrum.*agile/ui', $nom)) {
        $s = $split(max(20, $heures), 8);
        return match($niveau) {
            'debutant' => [
                ['titre'=>'C\'est quoi un projet ? Pourquoi c\'est différent du travail habituel','contenus'=>'Projet vs opérations : exemples concrets d\'une PME ivoirienne · Les 3 contraintes d\'un projet : délai, budget et qualité · Les 4 phases simples d\'un projet : Démarrer → Planifier → Réaliser → Clôturer · Mon rôle dans un projet : chef de projet, équipe, commanditaire · Pourquoi les projets échouent souvent : les 5 raisons principales · Exercice : identifier si une situation est un projet ou une opération (10 cas)','duree'=>$s[0]],
                ['titre'=>'Définir clairement son projet : objectifs et périmètre','contenus'=>'Pourquoi les projets mal définis finissent toujours mal · La note de cadrage : qu\'est-ce qu\'on fait et pourquoi · Formuler un objectif SMART · Les livrables : ce qu\'on doit produire concrètement · Les parties prenantes : qui sont-elles et que veulent-elles ? · Exercice guidé : rédiger une note de cadrage sur un projet fictif ou réel','duree'=>$s[1]],
                ['titre'=>'Planifier simplement : la liste des tâches et le planning de base','contenus'=>'Décomposer son projet en tâches : la liste de choses à faire · Dans quel ordre faire les tâches ? Les dépendances · Estimer le temps : méthode simple et pratique · Mon premier diagramme de Gantt sous Excel · Qui fait quoi : affecter les tâches à l\'équipe · Exercice : construire le planning d\'un projet de 4 semaines sous Excel','duree'=>$s[2]],
                ['titre'=>'Le budget de projet : estimer et suivre les coûts','contenus'=>'Les types de coûts dans un projet : direct, indirect, fixe, variable · Comment estimer un budget même quand on n\'a pas d\'expérience · Le tableau de suivi des dépenses : construction et mise à jour · Que faire quand on dépasse le budget ? · Rendre compte des coûts à son responsable · Exercice : construire le budget d\'un petit projet et simuler un dépassement','duree'=>$s[3]],
                ['titre'=>'Suivre l\'avancement : est-ce qu\'on avance bien ?','contenus'=>'Le tableau de bord de projet simple : 3 indicateurs essentiels · La réunion de suivi de projet : comment l\'animer efficacement · Identifier un retard et réagir rapidement · Gérer un problème : escalade ou solution directe ? · Le rapport d\'avancement simple que comprend tout le monde · Exercice : analyser l\'avancement d\'un projet avec un tableau de bord fourni','duree'=>$s[4]],
                ['titre'=>'Travailler en équipe et gérer les risques simples','contenus'=>'Communiquer clairement dans son équipe projet · Faire passer un message difficile à un client ou à son responsable · Les risques courants dans un projet PME : liste pratique · Anticiper un risque : que faire avant que le problème arrive ? · La clôture du projet : les 3 étapes finales · Exercice : identifier et traiter 5 risques dans un projet fictif','duree'=>$s[5]],
                ['titre'=>'Méthodes de travail collaboratif : Trello, Asana et outils simples','contenus'=>'Trello : créer son premier tableau de projet en 20 minutes · Asana : gérer les tâches de l\'équipe facilement · WhatsApp vs outils professionnels : quand choisir quoi · Google Drive pour partager les fichiers du projet · La réunion debout quotidienne (stand-up) : 15 minutes qui changent tout · Exercice : déployer un projet fictif sur Trello avec son équipe','duree'=>$s[6]],
                ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : planifier un projet complet (note de cadrage + liste tâches + Gantt + budget + 5 risques) sur cas inédit · Correction individualisée et feedback · Remise du certificat IBIG EDUFORM "Gestion de Projet Niveau Débutant"','duree'=>$s[7]],
            ],
            'expert' => [
                ['titre'=>'Stratégie de portefeuille de projets et gouvernance du PMO','contenus'=>'PMO (Project Management Office) : structures fonctionnelle, de soutien, de contrôle · Gestion de portefeuille (PPM) : priorisation, ressources et arbitrages stratégiques · Portfolio governance : comité de portefeuille, reporting CA/CODIR · Benefits realization management : suivre la valeur créée après la clôture · Maturité PM de l\'organisation : modèles P3M3, OPM3, CMMI · Exercice : construction d\'un tableau de bord de portefeuille de 12 projets','duree'=>$s[0]],
                ['titre'=>'Planification avancée et Earned Value Management (EVM)','contenus'=>'Schedule Network Analysis avancée : compression, fast-tracking, crashing · Resource leveling et resource-constrained scheduling · EVM complet : PV, EV, AC, CPI, SPI, TCPI, EAC, ETC, VAC · Monte Carlo simulation sur les délais et coûts · Reporting financier projet au niveau CODIR : earned schedule · Exercice : analyse EVM d\'un projet industriel complexe avec plan de récupération','duree'=>$s[1]],
                ['titre'=>'Management des risques complexes et opportunités','contenus'=>'Risk management avancé : EMV, arbres de décision, registre dynamique · Analyse quantitative des risques : simulation de Monte Carlo (Primavera Risk, @RISK) · Risk attitude et appétit pour le risque de l\'organisation · Réponse aux risques : plans de contingence vs plans de réponse · Opportunités : identifier et exploiter les événements favorables · Black swans et inconnues inconnues : gestion des imprévus radicaux · Exercice : quantification des risques d\'un projet d\'infrastructure','duree'=>$s[2]],
                ['titre'=>'Leadership de projet et gestion des parties prenantes difficiles','contenus'=>'Leadership situationnel en mode projet · Gestion des conflits dans l\'équipe et avec les parties prenantes · Négociation dans le cadre d\'un projet : intérêts, positions et BATNA · Stakeholder engagement avancé : matrice d\'engagement et stratégies de communication · Gestion des clients difficiles et des demandes hors périmètre · Pouvoir et politique dans les organisations matricielles · Exercice : simulation de gestion d\'un comité de pilotage difficile','duree'=>$s[3]],
                ['titre'=>'Méthodes agiles scaled : SAFe, Scrum at Scale et hybridation','contenus'=>'Scaled Agile Framework (SAFe) : niveaux Essential, Large Solution, Portfolio · LeSS (Large-Scale Scrum) et Nexus : différences et cas d\'application · Disciplined Agile (DA) : toolkit et choix du mode de fonctionnement · Hybridation PMP + Agile : quand et comment combiner les deux · PI Planning (Program Increment) : organisation et facilitation · Transformation agile d\'une organisation traditionnelle : obstacles et facteurs de succès','duree'=>$s[4]],
                ['titre'=>'Gouvernance et reporting avancé : CODIR, CA et parties prenantes institutionnelles','contenus'=>'Dashboard de projet pour le CODIR : signaux, narration et prise de décision · Earned value reporting adapté à la culture africaine des PME · Construction du business case et justification continue du projet · Gate reviews (décisions d\'investissement) : contenu et animation · Benefits tracking post-projet : tableau de bord des bénéfices réalisés · Clôture de projet institutionnelle : rapport final, capitalisation et archivage','duree'=>$s[5]],
                ['titre'=>'PMBOK 7e édition et préparation à la certification PMP','contenus'=>'PMBOK 7e édition : les 8 domaines de performance et les 12 principes · Differentiateurs PMP 2021 : prédictif + hybride + agile dans l\'examen · Écosystème de la certification : PMP, CAPM, PMI-ACP, PRINCE2 Practitioner · Stratégie de passage de l\'examen PMP : préparation, simulateurs, timing · Questions de style PMP : situationnelles et basées sur le jugement · Exercice : 60 questions de simulation PMP commentées','duree'=>$s[6]],
                ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : management complet d\'un projet complexe (planification EVM + risques Monte Carlo + gouvernance PMO + présentation CODIR) sur cas inédit · Correction individualisée et feedback expert · Remise du certificat IBIG EDUFORM "Gestion de Projet Expert"','duree'=>$s[7]],
            ],
            default => [
                ['titre'=>'Fondamentaux du management de projet et positionnement du chef de projet','contenus'=>'Définition du projet vs opérations : caractéristiques, contraintes, enjeux · Cycle de vie d\'un projet : phases, jalons et livrables · Rôle et compétences du Chef de Projet · Triangle des contraintes (coût, délai, qualité) et marge de manœuvre · Environnement organisationnel : structure fonctionnelle, matricielle, dédiée · Initialisation : note de cadrage, fiche projet et lettre de mission','duree'=>$s[0]],
                ['titre'=>'Initiation et définition du périmètre du projet','contenus'=>'Analyse des parties prenantes (register) et cartographie des influences · Expression des besoins et rédaction du cahier des charges · Structure de Décomposition du Travail (WBS / SDP) · Dictionnaire WBS et lots de travaux · Charte de projet (Project Charter) · Exercice : décomposition d\'un projet réel en WBS avec lots de travaux définis','duree'=>$s[1]],
                ['titre'=>'Planification des délais : Gantt, chemin critique et calendrier','contenus'=>'Séquençage des tâches et identification des dépendances · Estimation des durées : PERT, 3 points, analogie · Réseau PERT/CPM et calcul du chemin critique · Diagramme de Gantt : construction et mise à jour · Gestion des ressources sur le planning · Outils de planification : MS Project, GanttProject, Trello, Asana · Exercice : construction du planning complet d\'un projet du bénéficiaire','duree'=>$s[2]],
                ['titre'=>'Gestion des coûts et de la valeur acquise (Earned Value Management)','contenus'=>'Estimation des coûts du projet : méthodes ascendante et descendante · Budget global (Budget à l\'Achèvement – BAC) et courbe en S · Suivi du coût réel (AC) et de la valeur acquise (EV) · Indices de performance coûts (CPI) et délais (SPI) · Prévision du coût final (EAC) et analyse des écarts · Reporting financier projet : tableau de bord coût/délai · Exercice : analyse de la performance d\'un projet en cours','duree'=>$s[3]],
                ['titre'=>'Gestion des risques et de la qualité','contenus'=>'Identification des risques : brainstorming, AMDEC, registre des risques · Analyse qualitative et quantitative des risques (matrice probabilité/impact) · Stratégies de réponse : évitement, transfert, atténuation, acceptation · Plan de management de la qualité et critères d\'acceptation · Contrôle qualité : revues, inspections, tests d\'acceptation · Non-conformités et gestion des modifications · Exercice : construction d\'un registre des risques complet','duree'=>$s[4]],
                ['titre'=>'Pilotage, communication et clôture du projet','contenus'=>'Réunion de lancement (kick-off) et tableau de bord de pilotage · Reporting d\'avancement : rapport d\'état, réunion de suivi, escalade · Gestion des modifications (change management) : procédure et comité · Communication avec les parties prenantes : plan et fréquence · Procédure de clôture : réception des livrables, libération des ressources · Retour d\'expérience (REX) : bilan de projet et capitalisation des leçons apprises','duree'=>$s[5]],
                ['titre'=>'Méthodes agiles : Scrum, Kanban et hybridation','contenus'=>'Valeurs du Manifeste Agile et différences avec le mode prédictif · Scrum : rôles (Product Owner, Scrum Master, équipe), artefacts et cérémonies · Sprint planning, Daily Stand-Up, Sprint Review et Rétrospective · Backlog produit et user stories · Kanban : flux continu et gestion du WIP · Hybridation Agile/PMI dans les projets complexes · Exercice : simulation d\'un sprint complet sur un projet réel','duree'=>$s[6]],
                ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : planification complète d\'un projet réel (WBS + Gantt + budget + risques + plan de communication) · Simulation de 30 questions PMP/PRINCE2 commentées · Remise du certificat IBIG EDUFORM "Gestion de Projet" et plan de développement post-formation','duree'=>$s[7]],
            ],
        };
    }

    /* ══ Conduite du changement ══ */
    if (preg_match('/conduite\s+du\s+changement|accompagner\s+les?\s+transformation|change\s+management/ui', $nom)) {
        $s = $split(max(16, $heures), 6);
        return match($niveau) {
            'debutant' => [
                ['titre'=>'Pourquoi les gens résistent au changement ? Comprendre les réactions humaines','contenus'=>'Le changement fait peur : c\'est normal, voici pourquoi · La courbe du deuil expliquée simplement avec des exemples · Les différentes réactions face au changement : les enthousiasmes, les sceptiques, les résistants · Ce que ressentent vraiment les collaborateurs quand on change quelque chose · Mon propre rapport au changement : autodiagnostic guidé · Exercice : analyser les réactions d\'une équipe face à un changement fictif','duree'=>$s[0]],
                ['titre'=>'Les différents types de changement en entreprise','contenus'=>'Changement de logiciel, de procédure, de structure, de culture : c\'est très différent · Changement imposé vs changement choisi : les conséquences · Les petits changements qui font beaucoup de dégâts · Les grands changements qui se passent bien : ce qu\'on peut en apprendre · Évaluer l\'ampleur d\'un changement avant de le lancer · Exercice : classer 10 changements courants par type et niveau de complexité','duree'=>$s[1]],
                ['titre'=>'Annoncer un changement sans créer de panique','contenus'=>'Quand et comment annoncer un changement · Les 3 erreurs d\'annonce qui créent des résistances inutiles · Préparer son discours de changement : ce qu\'il faut dire et dans quel ordre · La communication en cascade : CODIR → managers → équipes · Que faire avec les questions auxquelles on n\'a pas encore de réponse · Exercice : préparer et jouer un message d\'annonce de changement','duree'=>$s[2]],
                ['titre'=>'Accompagner ses collègues dans un changement','contenus'=>'Le rôle du manager de proximité dans le changement · Écouter pour de vrai : les techniques de l\'écoute active · Identifier les personnes les plus fragilisées par le changement · Ateliers d\'équipe participatifs : faire co-construire plutôt qu\'imposer · Les petites victoires : comment les créer et les célébrer · Exercice : animer un atelier de 30 minutes autour d\'un changement simulé','duree'=>$s[3]],
                ['titre'=>'Suivre le changement et ajuster si ça ne marche pas','contenus'=>'Comment savoir si le changement est adopté : 3 signaux simples · Indicateurs d\'adoption : qualitatifs (ressenti) et quantitatifs (utilisation) · Que faire quand on constate un rejet persistant · Ajuster le plan : ce n\'est pas un signe d\'échec · Célébrer la réussite du changement : pourquoi c\'est important · Exercice : construire un tableau de suivi d\'adoption simple','duree'=>$s[4]],
                ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : plan simplifié de conduite du changement (annonce + communication + suivi) pour un changement d\'outil ou de procédure sur cas inédit · Correction individualisée et feedback · Remise du certificat IBIG EDUFORM "Conduite du Changement Niveau Débutant"','duree'=>$s[5]],
            ],
            'expert' => [
                ['titre'=>'Architecture stratégique de la transformation : vision, urgence et coalition','contenus'=>'Diagnostic de la maturité organisationnelle au changement (modèle Prosci ADKAR, Kotter) · Burning platform et vision du futur : construire un cas convaincant pour le CA · Coalition de leadership : sélection, rôles et rituel de la coalition de guidage · Théorie des systèmes appliquée à la transformation organisationnelle · Stakeholders analysis avancée : cartographie des pouvoirs et stratégies d\'influence · Exercice : construction du cas de transformation pour un comité de direction','duree'=>$s[0]],
                ['titre'=>'Conception d\'un programme de transformation à grande échelle','contenus'=>'Architecture d\'un programme de transformation : workstreams, interdépendances et séquencement · Change impact assessment (CIA) : analyse multi-niveaux et matrice d\'impact · Plan de conduite du changement (Change Management Plan) : structure, livrables, gouvernance · Ressources et budget du changement : ROI de la conduite du changement · Gestion des dépendances entre projets du programme · Exercice : conception du plan de transformation d\'une restructuration de 500 personnes','duree'=>$s[1]],
                ['titre'=>'Gestion des résistances complexes et des jeux de pouvoir','contenus'=>'Psychologie organisationnelle des résistances : défenses, rationalisation, projection · Groupes de résistance organisés : identification des leaders et stratégies d\'engagement · Jeux de pouvoir en période de transformation : acteurs, coalitions, manœuvres · Techniques avancées de co-construction et d\'implication des résistants · Gestion des leaders négatifs : stratégies de neutralisation et de retournement · Communication de crise dans la transformation : incident management','duree'=>$s[2]],
                ['titre'=>'Change analytics : mesurer et piloter l\'adoption par la donnée','contenus'=>'Métriques d\'adoption avancées : matrices d\'adoption par persona et par segment · OKRs de transformation : comment lier les indicateurs de changement aux résultats business · Pulse surveys : conception, fréquence et analyse des signaux faibles · Dashboards de changement : construction et présentation au comité de pilotage · Prédiction des risques de résistance : modèles d\'analyse · Reporting au CA sur l\'avancement de la transformation','duree'=>$s[3]],
                ['titre'=>'Leadership transformationnel et culture du changement continu','contenus'=>'Leadership transformationnel vs transactionnel : impact sur la transformation · Construire une culture d\'agilité organisationnelle permanente · Gestion du changement continu (Agile Change) vs transformations ponctuelles · Coaching exécutif des leaders en période de transformation · Knowledge management post-transformation : capitalisation et mémoire organisationnelle · Change maturity : faire progresser la maturité de l\'organisation sur 5 ans','duree'=>$s[4]],
                ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : programme de transformation complexe (diagnostic + CIA + plan + gouvernance + métriques) pour un groupe de 3 entités sur cas inédit · Correction individualisée et feedback expert · Remise du certificat IBIG EDUFORM "Conduite du Changement Expert"','duree'=>$s[5]],
            ],
            default => [
                ['titre'=>'Comprendre le changement organisationnel : dynamiques, acteurs et résistances','contenus'=>'Nature et types de changements (incrémental, radical, culturel, technologique) · Impacts humains, organisationnels et opérationnels · Courbe du deuil et réactions prévisibles des collaborateurs · Cartographie des acteurs : promoteurs, sceptiques, résistants · Facteurs de succès et d\'échec des transformations en Afrique · Diagnostic initial : évaluation de la maturité au changement de l\'organisation','duree'=>$s[0]],
                ['titre'=>'Modèles et cadres de conduite du changement','contenus'=>'Modèle de Lewin (dégel, changement, regelation) · Modèle de Kotter (8 étapes vers le changement réussi) · Modèle ADKAR (Awareness, Desire, Knowledge, Ability, Reinforcement) · Modèle McKinsey 7S · Choix du modèle selon le contexte · Construction de la vision du changement et du cas de raison d\'être (burning platform) · Exercice : application d\'ADKAR à un projet de transformation réel','duree'=>$s[1]],
                ['titre'=>'Stratégie et plan d\'accompagnement du changement','contenus'=>'Feuille de route de la transformation : phases, jalons et critères de succès · Plan de conduite du changement (PCC) : composantes et livrables · Constitution de l\'équipe de changement (sponsors, agents de changement, équipe RH) · Budget et ressources de la conduite du changement · Gouvernance du changement : comité de pilotage et réunions de suivi · Exercice : construction d\'un PCC pour un projet de transformation spécifique','duree'=>$s[2]],
                ['titre'=>'Communication et gestion des résistances','contenus'=>'Plan de communication du changement : cibles, messages, canaux, fréquence · Techniques de communication descendante, ascendante et horizontale · Gestion des rumeurs et de l\'information informelle · Techniques de gestion des résistances : écoute, co-construction, négociation · Forum ouvert et ateliers participatifs · Communication de crise lors des phases difficiles · Exercice : rédaction d\'un plan de communication pour un changement majeur','duree'=>$s[3]],
                ['titre'=>'Formation, accompagnement des équipes et ancrage','contenus'=>'Analyse des impacts sur les compétences : matrice de gestion des impacts · Plan de formation et d\'accompagnement des collaborateurs · Coaching des managers de proximité : rôle clé dans l\'adoption · Mesure de l\'adoption : indicateurs comportementaux et organisationnels · Ancrage des nouvelles pratiques : rituels, reconnaissance et célébration des succès · Plan de continuation et de consolidation post-déploiement','duree'=>$s[4]],
                ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : conception d\'un plan de conduite du changement complet pour un projet de transformation réel ou simulé · Correction individualisée et retour du formateur · Remise du certificat IBIG EDUFORM "Conduite du Changement" et plan de mise en œuvre dans l\'organisation du bénéficiaire','duree'=>$s[5]],
            ],
        };
    }

    /* ══ Marchés Publics / ANRMP ══ */
    if (preg_match('/march[eé]s?\s+publics|anrmp|appel\s+d.offres|passation\s+de\s+march[eé]/ui', $nom)) {
        $s = $split(max(16, $heures), 7);
        return match($niveau) {
            'debutant' => [
                ['titre'=>'C\'est quoi les marchés publics ? Comprendre le système en Côte d\'Ivoire','contenus'=>'Les marchés publics : quand l\'État achète des biens ou des services · Pourquoi il y a des règles strictes : transparence et lutte contre la corruption · Les acteurs principaux : ANRMP, administration acheteur, et moi le fournisseur · Les montants qui déclenchent l\'appel d\'offres obligatoire · Mon entreprise peut-elle soumissionner ? Les conditions de base · Exercice : identifier parmi 10 situations lesquelles relèvent des marchés publics','duree'=>$s[0]],
                ['titre'=>'Les différents types d\'appels d\'offres : lequel choisir ou rencontrer','contenus'=>'L\'appel d\'offres ouvert : tout le monde peut se présenter · L\'appel d\'offres restreint : invitation sélective · La demande de cotation : pour les petits montants · Le gré à gré : quand c\'est autorisé · Comment trouver les appels d\'offres en Côte d\'Ivoire (site ANRMP, journaux) · Exercice : pour 5 marchés fictifs, identifier le bon type de procédure','duree'=>$s[1]],
                ['titre'=>'Préparer son dossier de candidature : les pièces indispensables','contenus'=>'Les documents administratifs toujours demandés : RCCM, DGI, CNPS, caisse · Comment obtenir son attestation de régularité fiscale · La liasse de présentation de la société · Le cautionnement provisoire : qu\'est-ce que c\'est et comment l\'obtenir · Les erreurs qui font rejeter un dossier d\'office · Exercice : constituer une liste de contrôle de son dossier administratif','duree'=>$s[2]],
                ['titre'=>'Lire un Dossier d\'Appel d\'Offres (DAO) : ce qu\'il faut regarder en premier','contenus'=>'La structure d\'un DAO : 3 parties à connaître absolument · Les critères d\'évaluation : comment les points sont attribués · Les spécifications techniques : comprendre ce qu\'on me demande de fournir · Le délai de soumission et comment ne pas le rater · Les questions à poser avant de soumettre · Exercice : analyser un DAO réel et identifier les points clés','duree'=>$s[3]],
                ['titre'=>'Rédiger son offre technique et financière : les bases','contenus'=>'L\'offre technique : comment montrer que je comprends le besoin · Mon CV entreprise : présenter ses références de manière convaincante · L\'offre financière : comment calculer son prix en tenant compte des marges · La lettre de soumission : ce qu\'elle doit contenir · Présenter son dossier : mise en forme et reliure · Exercice : rédiger une offre technique simplifiée sur un cas de fournitures','duree'=>$s[4]],
                ['titre'=>'Après le dépôt : l\'évaluation, les résultats et les recours','contenus'=>'Comment se passe l\'ouverture des plis · Le délai entre soumission et attribution · Comment recevoir les résultats et les interpréter · Quoi faire si on n\'est pas retenu : demander un débriefing · Déposer une plainte à l\'ANRMP si on soupçonne une irrégularité · Exercice : simuler la réception d\'un résultat et la décision de recours ou non','duree'=>$s[5]],
                ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : constitution d\'un dossier de candidature complet sur un marché fictif + lecture du DAO + offre simplifiée · Correction individualisée et feedback · Remise du certificat IBIG EDUFORM "Marchés Publics Niveau Débutant"','duree'=>$s[6]],
            ],
            'expert' => [
                ['titre'=>'Stratégie de développement dans les marchés publics et partenariats','contenus'=>'Veille stratégique des appels d\'offres : outils, alertes et intelligence concurrentielle · Positionnement différenciateur : construire une expertise sectorielle reconnue · Groupements momentanés d\'entreprises (GME) : montage juridique, rôles et partage des risques · Partenariats public-privé (PPP) : cadre légal, structuration financière et négociation · Accréditation et pré-qualification longue durée : ANRMP, bailleurs internationaux · Stratégie de croissance à partir des marchés publics : de la PME au groupe','duree'=>$s[0]],
                ['titre'=>'Ingénierie financière des offres : costing avancé et marge cible','contenus'=>'Analyse fine du DAO : identifier les zones de risque financier · Méthode de costing avancée : direct costing, full costing, analyse des sous-traitants · Modélisation du prix cible : simulation de la concurrence attendue · Gestion des risques financiers dans l\'exécution : imprévus, révision de prix · Cautionnement et garanties bancaires : négociation et coût · Financement de l\'exécution : avances de démarrage, escompte des décomptes · Exercice : modèle financier complet pour un marché de 500 millions FCFA','duree'=>$s[1]],
                ['titre'=>'Rédaction avancée de l\'offre technique : différenciation et scores maximaux','contenus'=>'Décryptage de la grille d\'évaluation technique : identifier les points de levier · Rédaction de l\'offre technique percutante : structure, preuves et narration · Présentation des références : adapter les fiches projets aux critères · Qualification du personnel clé : CVs conformes et lettres d\'engagement · Méthodologie d\'exécution : un plan qui inspire confiance · Exercice : rédiger une offre technique qui marque les évaluateurs','duree'=>$s[2]],
                ['titre'=>'Contentieux des marchés publics : recours, arbitrage et litiges','contenus'=>'Recours devant l\'ANRMP : conditions, délais et procédure de soumission · Recours administratif auprès de l\'autorité contractante · Recours juridictionnel : tribunal administratif et Cour Suprême · Arbitrage CCJA pour les marchés internationaux · Contentieux d\'exécution : pénalités, résiliation et retenues de garantie · Responsabilité pénale des acteurs : pratiques anticorruption · Exercice : monter un dossier de recours contre une attribution irrégulière','duree'=>$s[3]],
                ['titre'=>'Marchés complexes : PPP, concessions et financements internationaux','contenus'=>'Partenariats Public-Privé (PPP) : BOT, DBFMO, concession · Processus ANRMP pour les PPP : pré-qualification, dialogue compétitif · Marchés Banque Mondiale et BAD : règles de passation et qualification · Marchés UE et AFD : appels d\'offres internationaux, règles d\'éligibilité · Marchés ONUDI, PNUD, organisations du système ONU · Financement de l\'offre pour les grands marchés : émission obligataire, dette senior · Exercice : analyser un appel d\'offres Banque Mondiale et adapter sa stratégie','duree'=>$s[4]],
                ['titre'=>'Audit, conformité et anticorruption dans les marchés publics','contenus'=>'Normes anticorruption OCDE, ISO 37001 et leur application en CI · Audit des marchés publics : méthodologie et indicateurs de corruption · Red flags dans un DAO ou un processus d\'évaluation · Politique de conformité interne (compliance) pour les entreprises soumissionnaires · Signalement des irrégularités : protection du lanceur d\'alerte en droit ivoirien · Plans de vigilance sur les chaînes d\'approvisionnement · Exercice : audit de conformité d\'un processus d\'attribution ivoirien','duree'=>$s[5]],
                ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : stratégie de réponse complète à un appel d\'offres complexe (costing + offre technique + recours éventuel) sur cas inédit · Correction individualisée et feedback expert · Remise du certificat IBIG EDUFORM "Marchés Publics Expert"','duree'=>$s[6]],
            ],
            default => [
                ['titre'=>'Cadre réglementaire des marchés publics en Côte d\'Ivoire','contenus'=>'Décret portant Code des Marchés Publics : champ d\'application et principes fondamentaux · Acteurs du système : ANRMP, DGMP, autorités contractantes, soumissionnaires · Seuils de passation et règles de compétence · Réglementation CEDEAO/UEMOA et directives communautaires · Infractions et sanctions prévues par la réglementation · Exercice : identification du mode de passation adapté selon le montant et l\'objet','duree'=>$s[0]],
                ['titre'=>'Les modes de passation : appel d\'offres, concours et gré à gré','contenus'=>'Appel d\'offres ouvert : procédure complète et conditions d\'utilisation · Appel d\'offres restreint : conditions de recours et pré-qualification · Demande de cotation : seuils et procédure simplifiée · Marché de gré à gré : cas autorisés et risques de contentieux · Concours : procédure et jury d\'évaluation · Procédure d\'urgence : conditions strictes et documentation requise · Exercice : choix justifié du mode de passation sur 5 cas pratiques','duree'=>$s[1]],
                ['titre'=>'Préparation et rédaction du Dossier d\'Appel d\'Offres (DAO)','contenus'=>'Structure type d\'un DAO conforme ANRMP · Spécifications techniques : rédiger sans discriminer ni favoriser un fournisseur · Critères d\'éligibilité et critères d\'évaluation des offres · Instructions aux soumissionnaires : délais, cautionnement, documents requis · Contrat type : clauses administratives générales et particulières · Exercice pratique : rédaction d\'un DAO complet sur un marché de fournitures ou de services','duree'=>$s[2]],
                ['titre'=>'Évaluation des offres, attribution et signature du marché','contenus'=>'Réception et ouverture des plis : procédure et procès-verbal · Commission d\'analyse des offres : composition, rôle et délibérations · Évaluation de la conformité administrative et technique · Évaluation financière et comparaison des offres · Rapport d\'évaluation et proposition d\'attribution · Approbation et publication de l\'attribution · Signature du marché et cautionnement définitif · Recours des soumissionnaires non retenus','duree'=>$s[3]],
                ['titre'=>'Exécution, suivi et contrôle du marché','contenus'=>'Ordre de service de démarrage et jalons contractuels · Suivi technique et financier : réceptions partielles et décomptes provisoires · Avenant : conditions strictes de recours · Pénalités de retard : calcul et application · Réception provisoire et définitive des prestations · Décompte final et règlement · Gestion des litiges et arbitrage · Archivage des marchés : délais et supports légaux','duree'=>$s[4]],
                ['titre'=>'Audit, contrôle et lutte contre la corruption dans les marchés publics','contenus'=>'Rôle de l\'ANRMP dans le contrôle et la régulation · Audits de passation et audits d\'exécution · Signaux d\'alerte de corruption et fraudes fréquentes · Code d\'éthique du praticien des marchés publics · Plaintes et recours devant l\'ANRMP · Responsabilité pénale des acteurs : sanctions prévues · Bonnes pratiques de gouvernance et transparence · Exercice : audit d\'un dossier de marché sur cas réel ivoirien','duree'=>$s[5]],
                ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : instruction complète d\'une procédure de passation de marché (DAO → évaluation → rapport d\'attribution) sur cas inédit · Correction individualisée et feedback · Remise du certificat IBIG EDUFORM "Marchés Publics ANRMP" et plan de développement','duree'=>$s[6]],
            ],
        };
    }

    /* ══ Droit OHADA des sociétés / Contrats commerciaux OHADA ══ */
    if (preg_match('/droit\s+ohada|acte\s+uniforme|sarl.*sa.*gie|contrats?\s+commerciaux.*ohada|contentieux.*ohada|ccja|sûret[eé]s\s+ohada/ui', $nom)) {
        $s = $split(max(16, $heures), 7);
        return match($niveau) {
            'debutant' => [
                ['titre'=>'C\'est quoi l\'OHADA ? Pourquoi c\'est important pour mon entreprise','contenus'=>'L\'OHADA en 5 minutes : une organisation de 17 pays pour faciliter les affaires · À quoi servent les Actes Uniformes dans ma vie d\'entrepreneur · OHADA vs droit ivoirien : qui l\'emporte ? · Les institutions OHADA que je dois connaître : CCJA, ERSUMA · L\'OHADA protège aussi les petits commerçants · Exercice : identifier les textes OHADA applicables à 5 situations courantes','duree'=>$s[0]],
                ['titre'=>'Créer son entreprise légalement : choisir entre SARL, SA, EI et GIE','contenus'=>'L\'entreprise individuelle : simple mais risquée, pourquoi ? · La SARL : la forme la plus adaptée aux PME ivoiriennes · La SA : quand en a-t-on vraiment besoin ? · Le GIE : s\'associer sans créer une société classique · Les étapes de création au CEPICI : ce qui se passe concrètement · Les frais réels de création d\'une société en Côte d\'Ivoire · Exercice : choisir la bonne forme juridique pour 3 projets d\'entreprise différents','duree'=>$s[1]],
                ['titre'=>'Les contrats commerciaux de base : se protéger dans ses relations d\'affaires','contenus'=>'Pourquoi un contrat écrit est indispensable (même avec un ami) · Les 3 éléments d\'un contrat valide · Qu\'est-ce que je dois mettre dans mon contrat de vente ou de prestation ? · Les clauses dangereuses à éviter · Ce qu\'est une force majeure et comment la gérer · Exercice : lire et annoter un contrat commercial simple, identifier les risques','duree'=>$s[2]],
                ['titre'=>'Mes droits et obligations de commerçant en droit OHADA','contenus'=>'Le commerçant OHADA : qui l\'est et qui ne l\'est pas · Mes obligations d\'immatriculation au RCCM · Tenir des livres commerciaux : ce qui est obligatoire · La responsabilité du dirigeant : jusqu\'où va-t-elle ? · Les interdictions et incompatibilités du commerçant · Exercice : vérifier si une entreprise fictive respecte ses obligations légales','duree'=>$s[3]],
                ['titre'=>'Mon client ne paie pas : comment récupérer ma créance','contenus'=>'Tenter d\'abord l\'amiable : lettre de mise en demeure · L\'injonction de payer : la procédure rapide OHADA · Comment se passe une injonction de payer en pratique · La saisie-attribution sur compte bancaire : dernière solution · Combien de temps j\'ai pour agir : les délais de prescription · Exercice : rédiger une lettre de mise en demeure et préparer une demande d\'injonction','duree'=>$s[4]],
                ['titre'=>'Les documents obligatoires de mon entreprise','contenus'=>'Le registre du commerce et les obligations de mise à jour · Les statuts : les conserver et les modifier si nécessaire · PV d\'assemblée générale annuelle : rédiger le PV de base · La comptabilité légale minimum · Comment fermer légalement une entreprise · Exercice : établir la liste de contrôle documentaire d\'une SARL de 3 ans','duree'=>$s[5]],
                ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : création fictive d\'une entreprise + contrat commercial + demande d\'injonction de payer sur cas inédit · Correction individualisée et feedback · Remise du certificat IBIG EDUFORM "Droit OHADA Niveau Débutant"','duree'=>$s[6]],
            ],
            'expert' => [
                ['titre'=>'Structuration juridique avancée des groupes et holdings OHADA','contenus'=>'Architecture des groupes de sociétés en droit OHADA : holding pure, mixte, opérationnelle · Intégration fiscale de groupe sous droit ivoirien : conditions et options · Pactes d\'actionnaires avancés : droits préférentiels, drag-along, tag-along, ratchet · Gouvernance d\'entreprise et compliance OHADA : code de gouvernement d\'entreprise · Restructurations : fusions-absorptions, scissions, apports partiels d\'actif · Exercice : structuration d\'un holding pour 3 PME ivoiriennes','duree'=>$s[0]],
                ['titre'=>'Fusions-acquisitions (M&A) en droit OHADA : processus et enjeux','contenus'=>'Phases d\'une transaction M&A : LOI, due diligence, SPA, closing · Due diligence juridique OHADA : périmètre, livrables et rapport · Share deal vs asset deal : avantages et inconvénients · Garanties de passif : rédaction, scope et durée · Conditions suspensives et conditions résolutoires · Ajustement de prix post-closing · Exercice : rédiger les clauses essentielles d\'un SPA pour une acquisition de SARL ivoirienne','duree'=>$s[1]],
                ['titre'=>'Arbitrage CCJA et OHADA : stratégies de résolution des litiges commerciaux','contenus'=>'Règlement d\'arbitrage CCJA 2017 : procédure, coûts et délais · Rédaction de la clause compromissoire : pièges et bonnes pratiques · Saisine de la CCJA : constitution du tribunal et procédure · Reconnaissance et exécution de la sentence arbitrale dans l\'espace OHADA · Arbitrage institutionnel vs ad hoc en Afrique de l\'Ouest · OHADA vs centres d\'arbitrage internationaux (CCI, LCIA, SIAC) · Exercice : stratégie de défense dans un arbitrage CCJA','duree'=>$s[2]],
                ['titre'=>'Sûretés OHADA révisées et financement structuré','contenus'=>'Réforme des sûretés 2010 : panorama des instruments disponibles · Hypothèque et cession d\'hypothèque : stratégies de financement immobilier · Nantissement de créances professionnelles et de portefeuilles · Fiducie-sûreté et cession de créances professionnelles · Agent des sûretés : rôle dans les financements syndiqués · Réalisation des sûretés : clauses de voie parée, attribution judiciaire · Exercice : montage des sûretés pour un financement de projet de 2 milliards FCFA','duree'=>$s[3]],
                ['titre'=>'Gouvernance avancée des SA et protection des actionnaires minoritaires','contenus'=>'Gouvernance des SA et des SAS : CA, DG, comités spécialisés · Droits et protection des actionnaires minoritaires en droit OHADA · Actions en responsabilité contre les dirigeants : conditions et prescription · Cession et transmission de titres : préemption, agrément, droit de sortie · Rachat d\'actions propres et réduction de capital · Introduction en bourse (BRVM) : parcours juridique et conditions d\'accès · Exercice : rédiger un règlement intérieur de CA conforme à l\'AUSCGIE','duree'=>$s[4]],
                ['titre'=>'Procédures collectives OHADA : stratégies de restructuration amiable et judiciaire','contenus'=>'Alerte précoce : détection des signaux financiers, rôle du commissaire aux comptes · Mandat ad hoc : confidentiel, sans publicité, pour les difficultés précoces · Conciliation : accord, homologation et effets sur les créanciers · Règlement préventif : conditions, plan de remboursement, suspension des poursuites · Redressement judiciaire : masse des créanciers, plan de continuation, règlement · Liquidation des biens : conséquences sociales et fiscales · Exercice : stratégie de restructuration pour une PME en cessation de paiement imminente','duree'=>$s[5]],
                ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : dossier complet M&A OHADA (due diligence + SPA + sûretés + clause d\'arbitrage) sur cas inédit · Correction individualisée et feedback expert · Remise du certificat IBIG EDUFORM "Droit OHADA Expert"','duree'=>$s[6]],
            ],
            default => [
                ['titre'=>'Architecture de l\'OHADA et sources du droit des affaires en Afrique','contenus'=>'Traité de l\'OHADA : objectifs, États membres et institutions (CCJA, ERSUMA) · Les Actes Uniformes : valeur, primauté sur les droits nationaux, champ d\'application · Acte Uniforme sur les Sociétés Commerciales et GIE (AUSCGIE) · Acte Uniforme sur les Contrats (AUDCG), les Sûretés (AUS) et le Recouvrement (AUPSRVE) · Jurisprudence de la CCJA : comment accéder et utiliser la base de décisions · Exercice : identification de l\'Acte Uniforme applicable à une situation donnée','duree'=>$s[0]],
                ['titre'=>'Constitution et organisation des sociétés : SARL, SA et GIE','contenus'=>'Choix de la forme juridique : avantages, inconvénients et critères de décision · SARL : capital minimum (1 FCFA), associés, statuts, gérant · SA : capital minimum, actionnaires, conseil d\'administration ou DG seul · GIE : objet particulier, responsabilité et fiscalité spécifique · Rédaction des statuts : mentions obligatoires, clauses libres et clauses dangereuses · Procédure de création : greffe du Tribunal, RCCM et formalités pratiques · Exercice : rédaction d\'une clause statutaire de cession de parts','duree'=>$s[1]],
                ['titre'=>'Gouvernance, direction et responsabilité des dirigeants','contenus'=>'Pouvoirs du gérant de SARL et du DG de SA : étendue et limites · Assemblées générales : convocation, quorum, délibérations et PV obligatoires · AGO et AGE : distinctions, délais et formalités · Responsabilité civile et pénale des dirigeants en droit OHADA · Action sociale ut singuli et ut universi · Conventions réglementées : procédure et sanction de la violation · Exercice : analyse d\'une situation de conflits d\'intérêts de dirigeant','duree'=>$s[2]],
                ['titre'=>'Contrats commerciaux : formation, exécution et litiges','contenus'=>'Formation des contrats OHADA : consentement, objet, cause · Clauses essentielles : prix, délais, pénalités, force majeure, résolution · Contrats spéciaux : bail commercial, vente de fonds de commerce, franchise · Inexécution et résolution : conditions et procédure · Clause compromissoire vs clause attributive de juridiction · Arbitrage CCJA : procédure, sentence arbitrale et exécution · Exercice : rédaction et négociation d\'un contrat de vente ou de prestation de services','duree'=>$s[3]],
                ['titre'=>'Sûretés OHADA et recouvrement des créances','contenus'=>'Réforme des Sûretés OHADA 2010 : panorama des sûretés disponibles · Cautionnement : formation, étendue et recours contre la caution · Hypothèque : constitution, inscription et purge · Nantissement de créances et de fonds de commerce · Droit de rétention et réserve de propriété · Procédures simplifiées de recouvrement (AUPSRVE) : injonction de payer · Voies d\'exécution : saisie-attribution, saisie immobilière · Exercice : choix et mise en œuvre d\'une sûreté adaptée','duree'=>$s[4]],
                ['titre'=>'Difficultés des entreprises et procédures collectives OHADA','contenus'=>'Alerte précoce et mandat ad hoc : quand y recourir · Conciliation : procédure et accord · Règlement préventif (plan de redressement amiable) · Redressement judiciaire : conditions, période d\'observation, plan · Liquidation des biens : conséquences et désintéressement des créanciers · Responsabilité en cas de faute de gestion en période suspecte · Exercice : analyse d\'une situation d\'entreprise en difficulté et recommandations','duree'=>$s[5]],
                ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : traitement d\'un dossier juridique complet (création + contrat + litige + recouvrement) sur cas OHADA inédit · Correction individualisée et feedback · Remise du certificat IBIG EDUFORM "Droit OHADA" et plan de développement','duree'=>$s[6]],
            ],
        };
    }

    /* ══ Transit douanier / Import-Export / Logistique internationale ══ */
    if (preg_match('/transit\s+douanier|sydam|dgd\s+c[oô]te|import[- ]export|guichet\s+unique|incoterms|d[eé]douanement/ui', $nom)) {
        $s = $split(max(16, $heures), 7);
        return match($niveau) {
            'debutant' => [
                ['titre'=>'C\'est quoi le transit douanier ? Acteurs et vocabulaire de base','contenus'=>'Pourquoi les marchandises passent-elles par la douane ? Rôle et missions de la DGD · Les acteurs clés : transitaire, importateur, exportateur, transporteur, banque · Vocabulaire essentiel : manifeste, déclaration, connaissement, dédouanement, BAE · Le Port d\'Abidjan : terminaux, guichets et déroulement d\'une opération · Guichet Unique du Commerce Extérieur (GUCE) : à quoi ça sert ? · Différence entre importation, exportation et transit · Exercice : identifier les acteurs sur un schéma d\'opération d\'import réel','duree'=>$s[0]],
                ['titre'=>'Les documents indispensables d\'une opération d\'importation','contenus'=>'La facture commerciale : que doit-elle contenir ? · Le connaissement maritime (B/L) : titre de propriété de la marchandise · La liste de colisage : poids, dimensions, colis · Le certificat d\'origine : pourquoi et comment l\'obtenir · La Déclaration d\'Importation (DI) et la procédure GUCE · Documents sanitaires, phytosanitaires et certificats spéciaux · Exercice : vérification d\'un jeu complet de documents d\'importation','duree'=>$s[1]],
                ['titre'=>'Ma première déclaration en douane : étapes pas à pas','contenus'=>'C\'est quoi une déclaration en détail ? Sa structure et ses rubriques · Régimes douaniers simples : importation définitive (IM4) et exportation définitive · Classification tarifaire : comment retrouver le code SH de ma marchandise · Calcul simple des droits et taxes : TEC CEDEAO + TVA ivoirienne · SYDAM World : navigation, saisie et soumission de la déclaration · Le Bon À Enlever (BAE) : ce qui se passe après la déclaration · Exercice guidé : remplissage pas à pas d\'une déclaration d\'importation sur SYDAM','duree'=>$s[2]],
                ['titre'=>'Comprendre les coûts d\'une importation','contenus'=>'Droits de douane : qui les fixe et comment ils sont calculés · Taxe sur la Valeur Ajoutée à l\'importation en Côte d\'Ivoire · Autres prélèvements : RD, PTFC, PRCI, timbre · La valeur en douane : c\'est quoi et comment la calculer simplement · Fret et assurance : leur impact sur le coût total · Coût complet d\'une importation : exemple chiffré avec une PME · Exercice : calcul du coût DDP d\'une importation d\'équipements informatiques','duree'=>$s[3]],
                ['titre'=>'Les Incoterms expliqués simplement','contenus'=>'À quoi servent les Incoterms dans un contrat d\'achat international ? · Les 3 Incoterms les plus utilisés par les PME ivoiriennes : EXW, FOB, DDP · Qui paie quoi et jusqu\'où : tableau visuel simple · Risques et responsabilités : qui supporte quoi si la marchandise est abîmée ? · Comment négocier l\'Incoterm avec un fournisseur étranger · Incoterms et valeur en douane : le lien à comprendre · Exercice : choisir le bon Incoterm sur 3 cas d\'achat réels','duree'=>$s[4]],
                ['titre'=>'Erreurs courantes et bonnes pratiques du débutant','contenus'=>'Top 10 des erreurs qui bloquent les marchandises à la douane · Sous-déclaration de valeur : risques et sanctions réelles · Mauvaise classification SH : comment éviter les redressements · Dossier incomplet : check-list des documents obligatoires · Relations avec le transitaire : ce qu\'il faut vérifier et demander · Comment lire un relevé de frais de transitaire · Suivi de son dossier sur SYDAM et GUCE · Exercice : audit d\'un dossier d\'importation avec erreurs à identifier et corriger','duree'=>$s[5]],
                ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : traitement guidé d\'un dossier import complet (documents → classification → calcul des droits → déclaration SYDAM) sur cas inédit · Correction individualisée et feedback formateur · Remise du certificat IBIG EDUFORM "Transit Douanier / Import-Export" Niveau Débutant et plan de progression personnalisé','duree'=>$s[6]],
            ],
            'expert' => [
                ['titre'=>'Régimes économiques douaniers : stratégie d\'optimisation fiscale','contenus'=>'Perfectionnement actif : suspensif de droits pour transformation et réexportation · Perfectionnement passif : envoi à l\'étranger pour réparation ou amélioration · Admission temporaire totale et partielle : calcul et gestion des apurements · Entrepôt de stockage et entrepôt d\'exportation : montage juridique et opérationnel · Zone Franche d\'Abidjan et zones industrielles spéciales en CEDEAO · Régime du dessin et modèle industriel : applications pour les groupes industriels · Exercice : montage d\'un schéma d\'optimisation douanière pour un groupe agro-industriel','duree'=>$s[0]],
                ['titre'=>'Valeur en douane, prix de transfert et multinationales','contenus'=>'Valeur en douane et prix de transfert : comment la DGD réexamine les flux intra-groupe · Méthodes subsidiaires GATT/OMC : applicabilité et jurisprudence UEMOA · Documentation de prix de transfert (TP doc) et conformité douanière · Advance Pricing Agreement (APA) : négociation avec les autorités douanières · Droits antidumping et compensateurs : identification des risques et défense · Audit douanier par la DGD : préparation, droits et stratégie de réponse · Exercice : analyse d\'un litige de valeur en douane et rédaction de la réponse','duree'=>$s[1]],
                ['titre'=>'Contentieux douanier avancé : défense, transaction et arbitrage','contenus'=>'Procédure contentieuse DGD : flagrant délit, procès-verbal et garde à vue douanière · Transaction douanière : calcul de l\'amende, négociation et quittance · Recours administratif et hiérarchique au sein de la DGD · Recours juridictionnel : Chambre Administrative, Chambre des Appels des Douanes · Prescription douanière en Côte d\'Ivoire · Stratégie de défense : expertises techniques, courrier de contestation, plaidoirie · Exercice : rédaction d\'un mémoire en défense sur un redressement de valeur','duree'=>$s[2]],
                ['titre'=>'Statut OEA et certification opérateur de confiance','contenus'=>'Opérateur Économique Agréé (OEA) : cadre international (OMD) et transposition DGD CI · Trois types d\'agrément OEA : sécurité/sûreté, simplification douanière, combiné · Audit de conformité préalable : exigences financières, documentaires, opérationnelles · Plan d\'action de mise en conformité pour l\'obtention du statut · Avantages opérationnels et financiers : dédouanement prioritaire, mainlevée immédiate · Maintien du statut OEA : audits périodiques et gestion des incidents · Exercice : réalisation d\'un gap analysis OEA pour une entreprise logistique','duree'=>$s[3]],
                ['titre'=>'Commerce numérique transfrontalier et e-commerce international','contenus'=>'Régime douanier des petits envois e-commerce : seuils CEDEAO et en vigueur CI · Drop shipping et marketplaces : responsabilité douanière du vendeur · Règlements douaniers de l\'UE pour les importations africaines (ICS2, IOSS) · Sous-évaluation systémique des plateformes asiatiques : risques et stratégies · Déclaration simplifiée des envois express et postaux · KYC douanier et traçabilité numérique des marchandises · Exercice : cartographie des risques douaniers d\'une activité d\'import e-commerce','duree'=>$s[4]],
                ['titre'=>'Logistique internationale avancée et supply chain resiliente','contenus'=>'Conception de la chaîne logistique internationale multi-modale : air, mer, route, rail · Corridors logistiques CEDEAO : Abidjan–Ouagadougou–Bamako, enjeux et coûts · Gestion des risques supply chain : délais, pénuries, disruptions géopolitiques · Technologies logistiques : TMS (Transport Management System), suivi GPS, blockchain · Logistique inversée : retours transfrontaliers et gestion des rebuts d\'exportation · Indicateurs de performance logistique : coût par km, taux de litige, cycle order-to-cash · Exercice : diagnostic et refonte d\'une supply chain internationale pour un groupe africain','duree'=>$s[5]],
                ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : conception d\'un schéma d\'optimisation douanière complet pour un groupe industriel (régimes économiques + OEA + contentieux + supply chain) sur cas inédit · Correction individualisée et feedback expert · Remise du certificat IBIG EDUFORM "Transit Douanier / Import-Export" Niveau Expert et plan de conseil stratégique','duree'=>$s[6]],
            ],
            default => [
                ['titre'=>'Organisation du commerce extérieur et acteurs de la chaîne logistique','contenus'=>'Structure du commerce international : exportateurs, importateurs, transitaires, transporteurs, banques · Réglementation du commerce extérieur en Côte d\'Ivoire · Port d\'Abidjan : organisation, terminaux et opérateurs · Guichet Unique du Commerce Extérieur (GUCE) : procédures dématérialisées · Cotecna et BIVAC : inspection avant embarquement · Exercice : cartographie d\'une opération d\'importation complète','duree'=>$s[0]],
                ['titre'=>'Nomenclature tarifaire, classification SH et valeur en douane','contenus'=>'Système Harmonisé (SH) de désignation et de codification des marchandises · Logique de la nomenclature : sections, chapitres, positions · Pratique de la classification : outils, notes explicatives, avis de classement · Valeur en douane : méthode transactionnelle GATT/OMC et méthodes subsidiaires · Éléments constitutifs de la valeur : prix, fret, assurance · Exercice : classification de 10 produits courants et calcul de leur valeur en douane','duree'=>$s[1]],
                ['titre'=>'Régimes douaniers et procédures de dédouanement','contenus'=>'Importation définitive : procédure, documents requis, paiement des droits · Exportation définitive : formalités, certificat d\'origine, remboursements · Régimes économiques en douane : entrepôt, admission temporaire, perfectionnement actif · Transit douanier national et international · Régime de l\'exportateur agréé et procédures simplifiées · SYDAM World : saisie de la déclaration en détail, validation et BAE · Exercice pratique sur SYDAM : remplissage d\'une déclaration d\'importation','duree'=>$s[2]],
                ['titre'=>'Incoterms 2020 : choix stratégique et impact logistique','contenus'=>'Définition et rôle des Incoterms dans le commerce international · Famille EXW, FCA, CPT, CIP : logique multimodale · Famille FOB, CFR, CIF : usage maritime et pièges · Incoterm DDP : responsabilité maximale du vendeur · Choix de l\'Incoterm selon le rapport de force commercial · Impact sur la valeur en douane, l\'assurance et la TVA · Exercice : sélection des Incoterms optimaux sur des cas de négociation réels','duree'=>$s[3]],
                ['titre'=>'Financement du commerce international et documents','contenus'=>'Crédit documentaire (L/C) : mécanisme, types et responsabilité de chaque acteur · Remise documentaire : procédure et risques · Garanties bancaires internationales · Documents du commerce : connaissement maritime (B/L), LTA aérienne, CMR terrestre · Facture commerciale, liste de colisage, certificat d\'origine · Assurance transport : polices flottantes et déclarations d\'aliment · Exercice : montage d\'un dossier documentaire complet','duree'=>$s[4]],
                ['titre'=>'Contrôle douanier, contentieux et optimisation des coûts','contenus'=>'Droits et pouvoirs de la DGD lors des contrôles · Infractions douanières : contrebande, fausse déclaration, sous-évaluation · Procédure contentieuse douanière : transaction et action judiciaire · Restitution des droits indûment payés · Optimisation des coûts douaniers : régimes préférentiels CEDEAO, ACPé-UE · Calcul du coût complet d\'une importation : DDP total · Exercice : calcul des droits et taxes sur une importation réelle','duree'=>$s[5]],
                ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : traitement complet d\'un dossier import ou export (nomenclature → régime → SYDAM → documents → coûts) sur cas inédit · Correction individualisée et feedback · Remise du certificat IBIG EDUFORM "Transit Douanier / Import-Export" et plan de développement','duree'=>$s[6]],
            ],
        };
    }

    /* ══ Réglementation BCEAO / Conformité bancaire ══ */
    if (preg_match('/r[eé]glementation\s+bceao|conformit[eé]\s+bancaire|lbc.ft|kyc.*aml|banque.*r[eé]glementation|compliance\s+bancaire/ui', $nom)) {
        $s = $split(max(16, $heures), 7);
        return match($niveau) {
            'debutant' => [
                ['titre'=>'Le secteur bancaire en Afrique de l\'Ouest : qui fait quoi ?','contenus'=>'Qu\'est-ce qu\'une banque, une SFD, un EME ? Différences et rôles · Qui surveille les banques en UEMOA ? BCEAO et Commission Bancaire expliqués · La Loi Bancaire UEMOA : ce que tout professionnel doit savoir · Pourquoi les banques sont réglementées : risques systémiques en langage simple · Les acteurs du système financier ivoirien : BHCI, SIB, SGCI, Ecobank… · Calendrier et structure des textes BCEAO : comment s\'y retrouver · Exercice : identifier les textes réglementaires applicables à un cas concret','duree'=>$s[0]],
                ['titre'=>'C\'est quoi un ratio prudentiel ? Comprendre les règles de base','contenus'=>'Pourquoi les banques doivent avoir suffisamment de fonds propres : notion simple de solvabilité · Le ratio de fonds propres : explication visuelle avec des exemples chiffrés · La liquidité bancaire : pourquoi une banque peut être solvable mais illiquide · Ratio de division des risques : ne pas prêter trop à un seul client · Reporting à la BCEAO : quand, comment et pourquoi · Lecture d\'un bilan simplifié de banque · Exercice : calculer un ratio de solvabilité basique sur des données simulées','duree'=>$s[1]],
                ['titre'=>'Blanchiment d\'argent et financement du terrorisme : comprendre les bases','contenus'=>'C\'est quoi le blanchiment ? Les 3 étapes : placement, empilement, intégration · Financement du terrorisme : différences avec le blanchiment · Pourquoi les banques doivent lutter contre le blanchiment · La CENTIF en Côte d\'Ivoire : son rôle et ses pouvoirs · KYC (Know Your Customer) : pourquoi on vous demande des documents · Signaux d\'alerte simples : transactions inhabituelles, cash suspect · Exercice : identifier les signaux d\'alerte dans 5 cas fictifs','duree'=>$s[2]],
                ['titre'=>'Procédures KYC et ouverture de compte : pas à pas','contenus'=>'Documents requis pour l\'ouverture de compte : particulier, entreprise, association · Vérification d\'identité : CNI, passeport, documents sociaux acceptés · Le bénéficiaire effectif : c\'est quoi et pourquoi le rechercher · Mise à jour périodique des dossiers clients · Catégories de risque : faible, moyen, élevé (PEP, OBNL, offshore) · Refus d\'ouverture de compte : dans quels cas et comment l\'expliquer · Exercice : instruction d\'un dossier d\'ouverture de compte personne morale','duree'=>$s[3]],
                ['titre'=>'Surveillance des opérations et déclaration de soupçon','contenus'=>'Quelles opérations surveiller : seuils, espèces, virements internationaux · Comment détecter une opération suspecte : grille d\'analyse simple · La Déclaration de Soupçon (DS) : quand, comment et à qui · Confidentialité de la déclaration de soupçon · Gel des avoirs : comment ça marche en pratique · Listes de sanctions : OFAC, Nations Unies, UE — comment consulter · Exercice : rédiger une déclaration de soupçon sur un cas fictif','duree'=>$s[4]],
                ['titre'=>'Risques bancaires et conformité au quotidien','contenus'=>'Risque de crédit : notion de créance saine, en souffrance, douteuse · Risque opérationnel : fraudes, pannes, erreurs humaines — exemples concrets · Risque de réputation : pourquoi la conformité protège la banque · Rôle du compliance officer : missions et quotidien · Politique de conformité : qu\'est-ce qu\'une charte éthique bancaire · Exercice : quiz de conformité sur 15 situations réelles en banque','duree'=>$s[5]],
                ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : analyse d\'un dossier KYC + détection d\'une opération suspecte + réponse à une question réglementaire sur cas inédit · Correction individualisée et feedback formateur · Remise du certificat IBIG EDUFORM "Réglementation BCEAO & Conformité Bancaire" Niveau Débutant et plan de progression','duree'=>$s[6]],
            ],
            'expert' => [
                ['titre'=>'Architecture prudentielle avancée : Bâle III complet et UEMOA','contenus'=>'Bâle III — Pilier 1 : CET1, AT1, T2, exigences minimales et coussins de conservation · Bâle III — Pilier 2 : ICAAP, stress tests inverses et dialogue superviseur · Bâle III — Pilier 3 : discipline de marché et reporting public · Transposition BCEAO : spécificités UEMOA et divergences avec Bâle III complet · NSFR et HQLA dans les marchés africains peu profonds · Planification du capital et gestion dynamique des fonds propres · Exercice : simulation ICAAP sur un bilan bancaire réel','duree'=>$s[0]],
                ['titre'=>'LBC/FT avancé : approche basée sur les risques et conformité GAFI','contenus'=>'Recommandations GAFI 2023 et leur applicabilité en Afrique de l\'Ouest · Approche basée sur les risques (ABR) : méthodologie de scoring et cartographie · Personnes Politiquement Exposées (PEP) : identification, diligences renforcées et monitoring · Tiers introducteurs et correspondance bancaire : chaîne de responsabilité · Gel des avoirs : implémentation technique et gestion des faux positifs · Évaluation nationale des risques (ENR) et adaptation de la politique interne · Exercice : refonte complète d\'une cartographie des risques LBC/FT','duree'=>$s[1]],
                ['titre'=>'Gestion avancée des risques bancaires : ALCO, stress tests et modélisation','contenus'=>'Comité ALCO : organisation, outils de mesure ALM (GAP analysis, duration) · Stress tests réguliers et inverses : scénarios macroéconomiques africains · Modèles de risque de crédit avancés : PD, LGD, EAD, RAROC · Risque de taux d\'intérêt dans le portefeuille bancaire (IRRBB) · Risque de change et couverture dans un bilan UEMOA · Risque de concentration sectorielle et géographique · Exercice : construction d\'un stress test macroéconomique pour la CI','duree'=>$s[2]],
                ['titre'=>'Fintech, Open Banking et supervision des risques numériques','contenus'=>'Cadre réglementaire BCEAO des EME (2015, révisé) : agréments et capitaux minimum · Open Banking en UEMOA : interopérabilité, API standards, responsabilités · Crypto-actifs et CBDC : position BCEAO et anticipation réglementaire · Cybersécurité bancaire : directive BCEAO, ISO 27001, tests de pénétration · KYC digital et e-KYC : encadrement réglementaire et risques · Supervision des risques algorithmiques et IA dans le crédit · Exercice : rédaction d\'un cadre de gouvernance des risques numériques','duree'=>$s[3]],
                ['titre'=>'Préparation aux inspections BCEAO et Commission Bancaire','contenus'=>'Anatomie d\'une mission d\'inspection BCEAO : phases, droits, obligations · Organisation de la préparation : war room, référent, dossiers maîtres · Lettre de recommandation (LR) : analyse et plan de réponse structuré · Sanctions administratives de la Commission Bancaire : niveaux et procédure · Plan de redressement (RRPF) : contenu, délais et suivi · Stratégie de dialogue superviseur : comment maintenir une relation constructive · Exercice : simulation d\'inspection avec préparation des dossiers et réponse LR','duree'=>$s[4]],
                ['titre'=>'Reporting réglementaire avancé et gouvernance de la conformité','contenus'=>'FINREP/COREP BCEAO : structure, calculs et processus de production · Reporting FATCA et CRS : obligations des banques ivoiriennes · Gouvernance de la conformité : trois lignes de défense, charte conformité · Comité de conformité : rôle, composition et reporting au CA · Indicateurs clés de risque (KRI) et tableau de bord de conformité · Meilleures pratiques internationales : BCBS 239, OCDE, FATF · Exercice : conception d\'un système de reporting de conformité pour un groupe bancaire régional','duree'=>$s[5]],
                ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : audit de conformité complet (ABR LBC/FT + calculs prudentiels + réponse LR BCEAO + plan de gouvernance) sur dossier bancaire inédit · Correction individualisée par un expert de la réglementation bancaire · Remise du certificat IBIG EDUFORM "Réglementation BCEAO & Conformité Bancaire" Niveau Expert et feuille de route de la fonction conformité','duree'=>$s[6]],
            ],
            default => [
                ['titre'=>'Architecture réglementaire BCEAO/UEMOA et agrément bancaire','contenus'=>'Organisation institutionnelle : BCEAO, Conseil des Ministres UEMOA, Commission Bancaire · Loi Bancaire UEMOA (2010) : champ d\'application et catégories d\'établissements · Procédure d\'agrément et conditions de capital minimum · Gouvernance des établissements de crédit : Conseil d\'Administration, Comité d\'Audit · Instructions et circulaires BCEAO : comment s\'y référer et les appliquer · Exercice : analyse d\'un texte BCEAO récent et identification des obligations','duree'=>$s[0]],
                ['titre'=>'Ratios prudentiels BCEAO et gestion des risques','contenus'=>'Ratio de solvabilité (Bâle II/III adapté UEMOA) : calcul et exigences · Ratio de liquidité : LCR et exigences BCEAO · Ratio de division des risques : concentration et plafonds · Grands risques et expositions sur les parties liées · Reporting prudentiel : états BCEAO, délais et procédures de transmission · Plan d\'urgence de trésorerie · Exercice : calcul des ratios prudentiels sur des données bilan simulées','duree'=>$s[1]],
                ['titre'=>'Dispositif LBC/FT : obligations KYC et surveillance des opérations','contenus'=>'Cadre légal : Loi Uniforme UEMOA relative à la LBC/FT · Obligations KYC (Know Your Customer) : identification, vérification, mise à jour · Bénéficiaire effectif : définition et procédure de recherche · Surveillance des opérations : transactions inhabituelles et seuils · Obligations déclaratives auprès de la CENTIF/CENTIB · Gel des avoirs et listes de sanctions internationales · Tiers introducteurs : obligations et responsabilités · Exercice : analyse d\'un cas de détection d\'opération suspecte','duree'=>$s[2]],
                ['titre'=>'Gestion des risques bancaires : crédit, marché et opérationnel','contenus'=>'Risque de crédit : classification des créances (saines, en souffrance, compromises) · Provisionnement et plans de désendettement · Risque de marché : risque de taux, de change et de prix · Risque opérationnel : incidents, fraudes, panne informatique · Stress tests et scénarios de crise · Comité de gestion actif-passif (ALCO) · Risk appetite statement et politique des risques · Exercice : classification d\'un portefeuille de crédit et calcul des provisions requises','duree'=>$s[3]],
                ['titre'=>'Fintech, Mobile Money et cadre réglementaire BCEAO','contenus'=>'Instruction BCEAO sur les établissements de monnaie électronique (EME) · Agrément et obligations prudentielles des EME · Interopérabilité Mobile Money en UEMOA · API Banking : enjeux réglementaires et contractuels · Open Banking : état des lieux en Afrique de l\'Ouest · Crypto-actifs : position de la BCEAO · Cybersécurité bancaire : directive BCEAO et obligations · Supervision des risques numériques','duree'=>$s[4]],
                ['titre'=>'Contrôle BCEAO, inspection et préparation aux audits réglementaires','contenus'=>'Mission d\'inspection de la BCEAO : droit de communication, dossiers à préparer · Lettre de recommandation post-inspection : procédure de réponse · Mise en demeure et sanctions de la Commission Bancaire · Reporting FINREP/COREP : format et délais · Préparation d\'un audit réglementaire externe · Check-list de conformité : auto-évaluation par domaine · Exercice : réponse structurée à une lettre de recommandation d\'inspection','duree'=>$s[5]],
                ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : analyse d\'un dossier de conformité bancaire complet (KYC + ratios + LBC/FT + reporting) sur cas inédit · Correction individualisée et feedback · Remise du certificat IBIG EDUFORM "Réglementation BCEAO & Conformité Bancaire"','duree'=>$s[6]],
            ],
        };
    }

    /* ══ Gestion de projet ONG / Cadre Logique AFD/UE/USAID ══ */
    if (preg_match('/cadre\s+logique|afd.*usaid|ue.*usaid|proj.{1,10}d[eé]veloppement|ong.*projet|rapportage.*bailleur|suivi.?[eé]valuation|kobo|odk\b|meal\b/ui', $nom)) {
        $s = $split(max(16, $heures), 7);
        return match($niveau) {
            'debutant' => [
                ['titre'=>'C\'est quoi un projet de développement ? Acteurs et vocabulaire','contenus'=>'Qu\'est-ce qu\'un projet de développement ? Différences avec une activité courante · Les bailleurs de fonds : AFD, UE, USAID, PNUD, Banque Mondiale — qui fait quoi · Les ONG, associations et gouvernements : rôles et responsabilités · Cycle de vie d\'un projet : de l\'idée à la clôture · Vocabulaire essentiel : ProDoc, PTA, MCL, rapportage, bailleur, UGP · Comment fonctionnent les financements : conventions, tranches, justifications · Exercice : identifier les acteurs d\'un projet de développement réel en CI','duree'=>$s[0]],
                ['titre'=>'Le Cadre Logique : comprendre la logique d\'intervention','contenus'=>'C\'est quoi un Cadre Logique (LFA) ? Pourquoi les bailleurs l\'exigent · La chaîne de résultats : activités → produits → effets → impact · La Matrice du Cadre Logique (MCL) : ses 4 lignes et 4 colonnes expliquées · Hypothèses et risques : pourquoi c\'est essentiel · Indicateurs SMART : formulation et exemples concrets · Lire et comprendre une MCL existante · Exercice : déchiffrer et expliquer la MCL d\'un projet social réel','duree'=>$s[1]],
                ['titre'=>'Construire sa première Matrice du Cadre Logique','contenus'=>'Identifier les problèmes avec l\'arbre à problèmes · De l\'arbre à problèmes à l\'arbre d\'objectifs · Choisir la stratégie d\'intervention optimale · Remplir les 16 cases de la MCL étape par étape · Rédiger des indicateurs quantitatifs et qualitatifs · Identifier les sources de vérification accessibles · Exercice encadré : construction de A à Z d\'une MCL pour un projet fictif','duree'=>$s[2]],
                ['titre'=>'Planifier un projet : PTA et budget pour débutants','contenus'=>'Qu\'est-ce qu\'un Plan de Travail Annuel (PTA) ? Structure et contenu · Lister les activités et les planifier dans le temps : diagramme de Gantt simplifié · Calculer un budget prévisionnel : lignes budgétaires, coûts unitaires, totaux · Règles de dépenses des bailleurs : ce qu\'on peut et ne peut pas acheter · Les per diem, frais de déplacement, coûts indirects : notions de base · Exercice : construction d\'un PTA et budget pour un projet de 3 mois','duree'=>$s[3]],
                ['titre'=>'Collecter les données sur le terrain avec KoBoToolbox','contenus'=>'Pourquoi et comment suivre les indicateurs d\'un projet · Outils de collecte numérique : KoBoToolbox et ODK Collect — installation et navigation · Créer un formulaire de collecte simple : questions fermées, ouvertes, GPS · Collecter les données sur mobile sans connexion internet · Importer et lire les données collectées sous Excel · Présenter les résultats en tableau et graphique simple · Exercice : création et utilisation d\'un formulaire KoBoToolbox pour un projet fictif','duree'=>$s[4]],
                ['titre'=>'Rédiger son premier rapport narratif à un bailleur','contenus'=>'Pourquoi les bailleurs demandent des rapports et ce qu\'ils vérifient · Structure d\'un rapport narratif intermédiaire : sections obligatoires · Comment rédiger clairement et professionnellement sur les activités réalisées · Lier les activités aux indicateurs de la MCL · Documenter les résultats : photos, listes de présence, statistiques · Expliquer les écarts et difficultés sans se disculper · Exercice : rédaction d\'un rapport narratif intermédiaire sur un projet fictif','duree'=>$s[5]],
                ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : construction d\'une MCL simple + PTA + rapport d\'activité sur un projet de développement inédit · Correction individualisée et feedback formateur · Remise du certificat IBIG EDUFORM "Gestion de Projet ONG / Cadre Logique" Niveau Débutant et plan de progression','duree'=>$s[6]],
            ],
            'expert' => [
                ['titre'=>'Stratégie de mobilisation de financements multi-bailleurs','contenus'=>'Cartographie des opportunités : fenêtres de financement AFD, UE, USAID, Banque Mondiale, Fonds Verts · Rédaction de notes conceptuelles compétitives : critères d\'évaluation et leviers de différenciation · Consortiums et partenariats stratégiques : montage, rôles et répartition des budgets · Négociation des conventions de financement : clauses sensibles et protections contractuelles · Gestion simultanée de plusieurs conventions et consolidation multi-bailleurs · Stratégie de sortie et durabilité post-projet : indicateurs de pérennité · Exercice : rédaction d\'une note conceptuelle pour un appel à projets réel','duree'=>$s[0]],
                ['titre'=>'Évaluation d\'impact : méthodes expérimentales et quasi-expérimentales','contenus'=>'Évaluation d\'impact vs évaluation de performance : enjeux et choix méthodologique · Essais contrôlés randomisés (ECR/RCT) : design, faisabilité et limites en Afrique · Méthodes quasi-expérimentales : DID, RDD, PSM, variables instrumentales · Théorie du changement avancée : identification des mécanismes causaux · Collecte des données de baseline et endline : protocoles rigoureux · Gestion de la qualité des données : protocoles de vérification terrain · Exercice : conception d\'un protocole d\'évaluation d\'impact pour un programme USAID','duree'=>$s[1]],
                ['titre'=>'Système MEAL avancé et redevabilité','contenus'=>'Architecture MEAL (Monitoring, Evaluation, Accountability, Learning) pour grands programmes · Indicateurs de niveau outcome et impact : construction et validation externe · Mécanismes de redevabilité envers les bénéficiaires (CRM, feedback loops) · Enquêtes de satisfaction et NPS dans les projets humanitaires · Qualité des données : évaluations DQA/RDQA, outils USAID · Gestion des connaissances : capitalisation, documentation des leçons apprises · Exercice : mise en place d\'un système MEAL complet pour un programme multi-pays','duree'=>$s[2]],
                ['titre'=>'Gestion financière avancée et audit des projets','contenus'=>'Règles d\'éligibilité avancées : coûts indirects, ICR USAID, overheads UE, fraude contractuelle · Gestion des taux de change et couverture dans les projets internationaux · Audit externe des projets : normes ISSAI, Single Audit Act USAID, ACA Union Européenne · Mise en conformité rapide lors d\'un constat d\'irrégularités · Gestion des recouvrements et pénalités bailleurs · Clôture financière : procédures et obligations post-projet · Exercice : analyse d\'un rapport d\'audit avec non-conformités et plan de réponse','duree'=>$s[3]],
                ['titre'=>'Gestion des crises, adaptation et résilience programmatique','contenus'=>'Gestion de crise en cours de projet : révisions substantielles, no-cost extensions · Adaptive management : ajustements programmatiques basés sur les données MEAL · Gestion de la sécurité en contexte fragile (MOSS, SMP, SRA) · Crise humanitaire et intégration développement-humanitaire-paix (nexus) · Communication de crise avec le bailleur et les médias · Retrait et transition programmatique : passation aux acteurs locaux · Exercice : simulation d\'une révision majeure de projet sous contrainte de crise','duree'=>$s[4]],
                ['titre'=>'Leadership organisationnel et développement de capacités','contenus'=>'Positionnement stratégique de l\'ONG sur le marché des appels d\'offres · Renforcement des capacités des partenaires locaux : approches et méthodes · Gestion des équipes multiculturelles et pluridisciplinaires · Négociation inter-institutionnelle : avec les gouvernements, bailleurs, communautés · Plan stratégique organisationnel : vision 5 ans, théorie du changement institutionnelle · Gouvernance des ONG : Conseil d\'Administration, responsabilités statutaires, transparence · Exercice : élaboration d\'un plan stratégique d\'organisation sur 3 ans','duree'=>$s[5]],
                ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : conception complète d\'un programme multi-bailleurs (MCL avancée + système MEAL + budget consolidé + protocole d\'évaluation d\'impact) sur cas inédit · Correction individualisée par un expert en gestion de projets de développement · Remise du certificat IBIG EDUFORM "Gestion de Projet ONG / Cadre Logique" Niveau Expert et plan de positionnement stratégique','duree'=>$s[6]],
            ],
            default => [
                ['titre'=>'Environnement des projets de développement et cycle de projet','contenus'=>'Acteurs : bailleurs (AFD, UE, USAID, PNUD, Banque Mondiale), ONG, gouvernements, bénéficiaires · Cycle de projet : identification, formulation, financement, mise en œuvre, évaluation · Documents types : Concept Note, Document de Projet (ProDoc), Convention de Financement · Structure de gouvernance d\'un projet (CP, UGP, bénéficiaires) · Sélection et gestion des partenaires de mise en œuvre · Exercice : analyse d\'une Convention de Financement AFD réelle','duree'=>$s[0]],
                ['titre'=>'Théorie du Changement et Cadre Logique (LFA)','contenus'=>'Théorie du Changement : hypothèses de causalité, chaîne de résultats · Approche du Cadre Logique (LFA) : principes et étapes · Matrice du Cadre Logique (MCL) : objectif global, objectif spécifique, résultats, activités · Indicateurs SMART : formulation, baseline et cible · Sources de vérification et fréquence de collecte · Hypothèses et risques dans la MCL · Exercice : construction d\'une MCL complète sur un projet fictif ou réel','duree'=>$s[1]],
                ['titre'=>'Planification opérationnelle : PTA, budget et gestion des ressources','contenus'=>'Plan de Travail Annuel (PTA) : structure, activités, responsables, calendrier · Budgétisation par activités : budget prévisionnel et justification · Règles d\'éligibilité des dépenses par bailleur (per diem, coûts indirects, contingences) · Passation des marchés et achats : procédures PPTE, USAID, UE · Gestion des ressources humaines du projet : recrutement et TDR · Exercice : construction d\'un PTA et d\'un budget prévisionnel d\'un projet ONG','duree'=>$s[2]],
                ['titre'=>'Suivi-Évaluation (S&E) et collecte des données terrain','contenus'=>'Plan de Suivi-Évaluation (PSE) : méthodes quantitatives et qualitatives · Collecte de données numériques : KoBoToolbox / ODK Collect · Conception du formulaire KoBoCollect : logique de saut, validation, types de réponses · Déploiement mobile et collecte sur le terrain · Gestion des données collectées : export, nettoyage, archivage · Visualisation des résultats : tableaux de bord MEAL · Exercice pratique : conception et déploiement d\'un formulaire KoBoCollect','duree'=>$s[3]],
                ['titre'=>'Reporting financier et narratif aux bailleurs','contenus'=>'Rapport narratif intermédiaire et final : structure et exigences des bailleurs · Format SF-425 USAID · Rapports financiers UE (annexes financières C.1 et C.2) · IFR et FM Reports Banque Mondiale · Gestion des avances et justifications de dépenses · Gestion des devises et taux de change de référence · Audit externe des projets (Single Audit USAID, ACA UE) · Exercice : rédaction d\'un rapport narratif intermédiaire sur cas réel','duree'=>$s[4]],
                ['titre'=>'Évaluation des projets : méthodologie, conduite et restitution','contenus'=>'Types d\'évaluation : à mi-parcours, finale, d\'impact, ex-ante · Termes de Référence de l\'évaluation : rédaction et négociation · Méthodes d\'évaluation : enquêtes, focus group, observations, données secondaires · Critères CAD/OCDE : pertinence, cohérence, efficacité, efficience, impact, durabilité · Rapport d\'évaluation : structure et qualité de l\'analyse · Restitution participative aux parties prenantes · Plan d\'apprentissage organisationnel : intégrer les leçons dans la pratique','duree'=>$s[5]],
                ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : construction complète d\'une MCL + PSE + rapport d\'avancement sur un projet inédit · Correction individualisée et feedback · Remise du certificat IBIG EDUFORM "Gestion de Projet ONG / Cadre Logique" et plan de développement','duree'=>$s[6]],
            ],
        };
    }

    /* ══ Business Plan / Entrepreneuriat / Création d'entreprise ══ */
    if (preg_match('/business\s+plan|cr[eé]ation\s+d.entreprise|cepici|entrepreneuriat|lancer\s+son|monter\s+son\s+projet/ui', $nom)) {
        $s = $split(max(16, $heures), 7);
        return match($niveau) {
            'debutant' => [
                ['titre'=>'Qu\'est-ce qu\'entreprendre ? De l\'idée au projet','contenus'=>'C\'est quoi un entrepreneur ? Mythes et réalités · Les qualités essentielles d\'un entrepreneur qui réussit en Côte d\'Ivoire · Comment passer d\'une idée floue à un concept précis · Les 3 questions fondamentales : quoi vendre ? à qui ? comment ? · Démarche de validation à faible coût : 5 techniques simples · Erreurs courantes des jeunes entrepreneurs ivoiriens : comment les éviter · Exercice : formulation et test de son idée en 30 minutes','duree'=>$s[0]],
                ['titre'=>'Connaître son marché sans être chercheur','contenus'=>'Pourquoi étudier son marché avant de se lancer · Les clients : qui sont-ils vraiment ? Techniques simples d\'identification · Comment faire une enquête terrain avec 10 personnes · Identifier ses concurrents et apprendre d\'eux · Trouver son avantage : ce qui rend mon offre différente · Fixer son prix : entre les coûts, la valeur et le marché · Exercice : réaliser une mini-étude de marché pour son idée','duree'=>$s[1]],
                ['titre'=>'Créer son entreprise légalement : démarches pas à pas','contenus'=>'Pourquoi se formaliser ? Avantages fiscaux, contrats, crédibilité · Choisir sa forme juridique simplement : EI, SARL à 1 associé, SARL classique · Le guichet CEPICI : visite virtuelle et étapes réelles · Documents nécessaires : liste complète et où les obtenir · RCCM, numéro fiscal, CNPS : à quoi servent-ils · Coût réel de création : budget prévisionnel des frais administratifs · Exercice : préparer le dossier complet de création de sa propre structure','duree'=>$s[2]],
                ['titre'=>'Comprendre les chiffres de son projet','contenus'=>'C\'est quoi un compte de résultat ? L\'expliquer avec des exemples de la vie réelle · Charges et produits : comment les lister pour son activité · Calculer son prix de revient et son seuil de rentabilité · C\'est quoi la trésorerie et pourquoi elle tue les entreprises qui gagnent de l\'argent · Prévisions financières simples sur 12 mois : tableau rempli ensemble · Les indicateurs à surveiller chaque mois : CA, marge, trésorerie · Exercice : construction des prévisions financières de son projet','duree'=>$s[3]],
                ['titre'=>'Trouver des financements pour démarrer','contenus'=>'Autofinancement et love money : comment convaincre famille et amis · Les microfinances accessibles aux jeunes en CI : COFINA, Advans, UNACOOPEC · Programmes de l\'État ivoirien pour les jeunes entrepreneurs : PEJEDEC, PNSD, C2D · Conditions réelles d\'accès au crédit bancaire : ce que la banque regarde vraiment · Financements non remboursables (subventions et concours) : où les chercher · Comment monter un dossier de financement convaincant · Exercice : identifier les 3 sources de financement les plus adaptées à son projet','duree'=>$s[4]],
                ['titre'=>'Mon premier Business Plan en 5 heures','contenus'=>'Structure d\'un Business Plan simple mais professionnel : 8 sections essentielles · Rédiger le résumé exécutif : la page la plus importante · Présenter son produit/service clairement · Intégrer les données de marché et de concurrence · Mettre ses chiffres en valeur · Préparer un pitch oral de 3 minutes · Exercice : rédaction collaborative du Business Plan du bénéficiaire','duree'=>$s[5]],
                ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : présentation de son Business Plan devant un jury bienveillant simulant un comité de financement · Feedback constructif sur la solidité du projet · Remise du certificat IBIG EDUFORM "Entrepreneuriat & Business Plan" Niveau Débutant et plan d\'action des 90 premiers jours','duree'=>$s[6]],
            ],
            'expert' => [
                ['titre'=>'Modèles d\'affaires innovants et stratégie de croissance','contenus'=>'Modèles d\'affaires disruptifs en Afrique : plateformes, marketplaces, SaaS, asset-light · Growth hacking et stratégies de croissance rapide avec peu de ressources · Écosystèmes d\'innovation africains : hubs tech, incubateurs, accélérateurs · Propriété intellectuelle : brevets, marques, trade secrets dans le contexte OAPI · Stratégies de prix avancées : freemium, dynamic pricing, yield management · Expansion géographique en CEDEAO : réglementation et opérations · Exercice : redesign du modèle d\'affaires d\'une PME ivoirienne existante','duree'=>$s[0]],
                ['titre'=>'Levée de fonds et financement de la croissance','contenus'=>'Capital-risque africain : acteurs (AfricInvest, Partech Africa, Orange Ventures, Oikocredit), stades et critères · Valorisation d\'une startup africaine : méthodes et benchmarks · Term sheet : négociation des clauses sensibles (anti-dilution, liquidation preference, drag-along) · Due diligence investisseur : préparation du data room · Instruments alternatifs : dette mezzanine, revenue-based financing, obligations convertibles · Introduction en Bourse régionale (BRVM) : conditions et processus · Exercice : montage d\'un dossier complet de levée de fonds Série A','duree'=>$s[1]],
                ['titre'=>'Finance avancée pour dirigeants : pilotage et création de valeur','contenus'=>'Plan stratégique financier sur 5 ans avec scenarios (optimiste, central, pessimiste) · Gestion de la dette et structure financière optimale (Modigliani-Miller en contexte africain) · Indicateurs de création de valeur : EVA, ROIC, TSR · Free Cash Flow et gestion du BFR dans les cycles d\'activité africains · Politique de distribution : dividendes vs réinvestissement · Modélisation financière avancée sous Excel / Python · Exercice : construction d\'un modèle financier 5 ans avec tableau de bord de pilotage','duree'=>$s[2]],
                ['titre'=>'Stratégies de marché et marketing avancé','contenus'=>'Analyse concurrentielle avancée : Porter 5 forces, Value Chain, Blue Ocean Strategy · Segmentation, ciblage et positionnement (STP) pour marchés africains · Marketing digital intégré : SEO, réseaux sociaux, e-mail, Mobile Money · Pricing strategy dans des marchés à faibles revenus : BOP (Base of Pyramid) · Partenariats stratégiques et développement de canaux B2B · Customer Lifetime Value (CLV) et gestion de la relation client · Exercice : conception d\'une stratégie de marché complète pour l\'expansion en CEDEAO','duree'=>$s[3]],
                ['titre'=>'Gouvernance, management et structuration d\'entreprise','contenus'=>'Gouvernance d\'une PME ivoirienne en croissance : CA, comités, séparation des pouvoirs · Structuration juridique d\'un groupe : holding, filiales, joint-ventures en OHADA · Management stratégique des équipes : recrutement, rétention, culture d\'entreprise · Tableaux de bord de gestion dirigeant : OKR, KPI, BSC · Gestion des associés et actionnaires : pacte d\'associés, droits et obligations · Exit strategy : IPO BRVM, cession industrielle, MBO, transmission familiale · Exercice : conception d\'un organigramme de gouvernance et d\'un pacte d\'associés','duree'=>$s[4]],
                ['titre'=>'Transformation digitale et internationalisation','contenus'=>'Audit digital de l\'entreprise : maturité, besoins et roadmap transformation · ERP et outils de gestion pour PME africaines en croissance · Commerce électronique transfrontalier : réglementation, logistique et paiements · Normes internationales : ISO 9001, certification qualité, labellisation · Expansion hors CI : étude de marché, partenariat local, implantation · Gestion des risques géopolitiques et de change en Afrique subsaharienne · Exercice : plan de transformation digitale et d\'internationalisation sur 3 ans','duree'=>$s[5]],
                ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : présentation d\'un plan stratégique complet de scale-up (modèle d\'affaires + financement + gouvernance + internationalisation) devant jury d\'experts · Feedback expert et recommandations stratégiques personnalisées · Remise du certificat IBIG EDUFORM "Entrepreneuriat & Business Plan" Niveau Expert et plan de développement stratégique','duree'=>$s[6]],
            ],
            default => [
                ['titre'=>'Valider son idée et construire son modèle d\'affaires','contenus'=>'Passage de l\'idée au concept viable : grille de validation en 10 points · Business Model Canvas (BMC) : les 9 blocs appliqués au contexte ivoirien · Analyse de marché : segmentation client, taille du marché, concurrents · Tests de concept : enquêtes terrain, MVP, prototype à faible coût · Identification des risques majeurs et hypothèses critiques · Exercice : construction du BMC du projet du bénéficiaire','duree'=>$s[0]],
                ['titre'=>'Étude de marché et stratégie commerciale','contenus'=>'Méthodologie de l\'étude de marché : sources primaires et secondaires · Enquêtes clients : rédaction du questionnaire, administration et analyse · Analyse de la concurrence : positionnement, avantages différenciants · Stratégie de prix : coûts, valeur perçue, prix du marché · Stratégie de distribution en Côte d\'Ivoire : circuits formels et informels · Plan de lancement commercial et acquisition des premiers clients','duree'=>$s[1]],
                ['titre'=>'Forme juridique, procédures CEPICI et formalités de création','contenus'=>'Formes juridiques disponibles en CI : SARL, SA, EI, GIE, SAS · Critères de choix selon le projet, les associés et la fiscalité · Procédures CEPICI (guichet unique) : étapes et délais réels · Rédaction des statuts et mentions obligatoires · Immatriculation RCCM, numéro SIUCEN et affiliation DGI · Affiliation CNPS et CMU · Ouverture du compte professionnel · Coûts réels de création en Côte d\'Ivoire','duree'=>$s[2]],
                ['titre'=>'Projections financières et plan de financement','contenus'=>'Compte de résultat prévisionnel sur 3 ans : hypothèses et construction · Bilan prévisionnel de départ et d\'ouverture · Plan de trésorerie mensuel : anticipation des besoins · Calcul du seuil de rentabilité et du délai de récupération · Besoin en Fonds de Roulement (BFR) de démarrage · Plan de financement : ressources propres, emprunts, subventions · Exercice : construction des projections financières du projet du bénéficiaire','duree'=>$s[3]],
                ['titre'=>'Sources de financement PME en Côte d\'Ivoire','contenus'=>'Banques ivoiriennes : critères d\'octroi, dossier type, garanties exigées · Microfinance (COFINA, Advans, UNACOOPEC) : produits et conditions · Fonds propres et love money : quand et comment · Capital-risque et business angels en Afrique : acteurs locaux · FGPME, BPI CI, CFA Franc : dispositifs d\'appui à la PME · Financement des jeunes (PEJEDEC, PNSD, C2D) · Financement participatif (Ulule, Miimosa, GoFundMe Afrique) · Exercice : sélection et montage du dossier de financement adapté','duree'=>$s[4]],
                ['titre'=>'Rédaction du Business Plan et pitch aux investisseurs','contenus'=>'Structure du Business Plan "bancable" : résumé exécutif percutant · Présentation de l\'équipe et des compétences clés · Présentation du marché et de la stratégie · Projections financières : clés à mettre en avant · Annexes convaincantes : lettres d\'intention, démo, données de marché · Elevator pitch (2 min) et pitch deck (10 slides) · Simulation de présentation devant un jury · Questions difficiles des investisseurs : comment y répondre','duree'=>$s[5]],
                ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : présentation orale du Business Plan du bénéficiaire devant un jury simulant un comité de financement · Feedback structuré sur la solidité du projet et du dossier · Remise du certificat IBIG EDUFORM "Entrepreneuriat & Business Plan" et plan d\'action des 90 jours post-formation','duree'=>$s[6]],
            ],
        };
    }

    /* ══ Microfinance / SFD ══ */
    if (preg_match('/microfinance|syst[eè]mes?\s+financiers?\s+d[eé]centralis[eé]s?|sfd\b|imf\b.*cr[eé]dit|warrantage|cr[eé]dit.{1,10}stockage/ui', $nom)) {
        $s = $split(max(16, $heures), 7);
        return match($niveau) {
            'debutant' => [
                ['titre'=>'C\'est quoi la microfinance ? Rôle et acteurs en Côte d\'Ivoire','contenus'=>'Définition de la microfinance : financer les exclus du système bancaire classique · Les SFD (Systèmes Financiers Décentralisés) : coopératives, caisses, IMF · Acteurs majeurs en CI : UNACOOPEC, COFINA, Advans, COOPEC-CI · Différence entre une banque et une institution de microfinance · Réglementation BCEAO : pourquoi la microfinance est encadrée · Les clients typiques d\'un SFD : qui sont-ils et quels sont leurs besoins · Exercice : visite virtuelle d\'un SFD et identification de ses services','duree'=>$s[0]],
                ['titre'=>'Les produits d\'épargne et de crédit d\'un SFD','contenus'=>'L\'épargne dans un SFD : compte courant, compte d\'épargne, épargne bloquée · Le crédit solidaire de groupe : comment ça marche, avantages et contraintes · Le crédit individuel : différence avec le groupe et conditions d\'accès · Warrantage agricole : financer les agriculteurs sur leurs stocks · Mobile Money dans les SFD : nouvelles possibilités de collecte et de crédit · Assurance micro-crédit et assurance vie emprunteur · Exercice : identifier le produit SFD adapté à 5 profils de clients fictifs','duree'=>$s[1]],
                ['titre'=>'Analyser un dossier de crédit simplement','contenus'=>'Pourquoi analyser avant de prêter ? La règle fondamentale du crédit · La visite terrain : observer, questionner, vérifier · Calculer simplement les revenus et les charges d\'un ménage ou d\'une micro-entreprise · La capacité de remboursement : calcul pas à pas · Les garanties acceptées dans un SFD : caution solidaire, nantissement, warrantage · Le taux d\'endettement : c\'est quoi et comment le calculer · Exercice : instruction guidée d\'un dossier de crédit pour un commerçant','duree'=>$s[2]],
                ['titre'=>'Gérer les remboursements et les impayés','contenus'=>'Suivi des remboursements : tableau de bord simple d\'un agent de crédit · PAR 30 et PAR 90 : c\'est quoi et comment les calculer · Les causes des impayés : problèmes économiques, mauvaise foi, malentendus · La relance amiable : comment aborder un client en retard respectueusement · La restructuration d\'un crédit : quand et comment · Provisions : pourquoi mettre de l\'argent de côté pour les mauvaises créances · Exercice : calcul du PAR d\'un portefeuille fictif et plan de recouvrement','duree'=>$s[3]],
                ['titre'=>'Performance financière d\'un SFD : les indicateurs de base','contenus'=>'Le compte de résultat d\'un SFD : produits financiers vs charges · Autosuffisance opérationnelle (OSS) : est-ce que le SFD couvre ses coûts ? · Le taux d\'intérêt effectif global (TIEG) : pourquoi il est plus élevé en microfinance · Coût par bénéficiaire et efficience opérationnelle · Lecture d\'un bilan simplifié de SFD · Les ratios BCEAO à surveiller : seuils et signaux d\'alerte · Exercice : calculer l\'OSS et le taux d\'intérêt réel d\'un SFD fictif','duree'=>$s[4]],
                ['titre'=>'Gouvernance et conformité dans un SFD','contenus'=>'Organisation d\'une coopérative : AG, Conseil d\'Administration, Comité de Surveillance · Rôle de chaque organe : décision, contrôle, gestion · Conflits d\'intérêts dans les SFD : exemples réels et comment les prévenir · Rapport de supervision BCEAO : comment se préparer en tant qu\'agent · Responsabilité pénale dans la gestion d\'un SFD · Introduction aux droits des membres et à la transparence · Exercice : simulation d\'une Assemblée Générale de coopérative','duree'=>$s[5]],
                ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : instruction d\'un dossier de crédit + calcul du PAR sur un portefeuille inédit + recommandations de recouvrement · Correction individualisée et feedback formateur · Remise du certificat IBIG EDUFORM "Microfinance & SFD" Niveau Débutant et plan de progression','duree'=>$s[6]],
            ],
            'expert' => [
                ['titre'=>'Stratégie de transformation et positionnement des SFD','contenus'=>'Analyse stratégique du secteur microfinance africain : consolidation, disruption fintech, réglementation croissante · Modèles de SFD en mutation : coopérative, SICAP, holding financier, banque de proximité · Planification stratégique sur 5 ans : diagnostic interne (SWOT), marché cible, projections · Positionnement concurrentiel en UEMOA : différenciation produit, prix, canaux · Extension géographique des SFD : agences, correspondants, Mobile Money · Alliances stratégiques : refinancement BCEAO, AFD, TriodosBank · Exercice : élaboration du plan stratégique quinquennal d\'un SFD de taille intermédiaire','duree'=>$s[0]],
                ['titre'=>'Gestion des risques avancée et modélisation du portefeuille','contenus'=>'Modèles de scoring crédit avancés pour la microfinance : variables, calibration et validation · Stress tests portefeuille : scénarios de crise saisonnière, chocs macroéconomiques · Gestion du risque de concentration sectorielle (agriculture, commerce, BTP) · Risque de liquidité dans les SFD saisonniers : modélisation GAP de liquidité · Risque opérationnel : fraudes internes, erreurs de système, risques fiduciaires · Gestion du risque de change pour les SFD refinancés en devises · Exercice : construction d\'un modèle de scoring et simulation de stress test','duree'=>$s[1]],
                ['titre'=>'Finance sociale et performance sociale des SFD','contenus'=>'Double bottom line : performance financière et performance sociale simultanées · Cadre SPTF (Social Performance Task Force) : indicateurs universels · Audit social SPI4 et certifications MIX Market · Inclusion financière des femmes : programmes ciblés et impact mesuré · Finance responsable : taux d\'intérêt éthiques et prévention du surendettement · Smart Campaign et principes de protection des clients · Exercice : réalisation d\'un audit de performance sociale SPI4 complet','duree'=>$s[2]],
                ['titre'=>'Fintech et transformation digitale des SFD','contenus'=>'Banque numérique pour les populations rurales : modèles africains innovants · Agent banking : réseau d\'agents, conformité et gestion des risques opérationnels · Digital lending : algorithmes de crédit, Big Data et données alternatives · Blockchain dans la microfinance : traçabilité des fonds, identité numérique · Interopérabilité Mobile Money et SFD : état de l\'art en UEMOA · SIG avancé pour SFD : Musoni, Mambu, Temenos T24 · Exercice : conception d\'une feuille de route de digitalisation complète pour un SFD','duree'=>$s[3]],
                ['titre'=>'Refinancement, levée de fonds et durabilité financière','contenus'=>'Sources de refinancement des SFD : BCEAO, banques commerciales, bailleurs (Oikocredit, responsAbility, LMDF) · Évaluation externe et notation (MFR, Planet Rating) : préparation et enjeux · Structuration d\'un véhicule de refinancement local · Obligations vertes et à impact social : structuration et émission · Durabilité sans subvention : trajectoire d\'OSS > 120% · Gestion actif-passif (ALM) dans les SFD à forte saisonnalité · Exercice : montage d\'un dossier de refinancement international','duree'=>$s[4]],
                ['titre'=>'Gouvernance avancée, supervision et réforme réglementaire','contenus'=>'Gouvernance institutionnelle d\'excellence : séparation des pouvoirs, audit interne indépendant · Préparation à une inspection BCEAO : war room, dossiers maîtres, réponse recommandations · Réforme réglementaire PARMEC post-2020 : impacts et adaptation stratégique · Fusion-absorption de SFD : due diligence, intégration des portefeuilles · Responsabilité pénale des dirigeants de SFD : jurisprudence et prévention · Plan de succession et continuité des dirigeants · Exercice : simulation d\'inspection BCEAO et plan de mise en conformité','duree'=>$s[5]],
                ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : diagnostic complet d\'un SFD (stratégie + risques + performance sociale + digitalisation + gouvernance) avec plan de transformation sur cas inédit · Correction individualisée par un expert en microfinance africaine · Remise du certificat IBIG EDUFORM "Microfinance & SFD" Niveau Expert et plan de développement institutionnel','duree'=>$s[6]],
            ],
            default => [
                ['titre'=>'Environnement réglementaire des SFD en UEMOA','contenus'=>'Loi PARMEC et son évolution : champ d\'application, catégories d\'IMF · Autorité de tutelle : BCEAO, Ministères des Finances, fédérations · Agrément des SFD : conditions, procédure et obligations post-agrément · Ratios prudentiels BCEAO spécifiques aux SFD · Gouvernance coopérative : AG, Conseil d\'Administration, Comité de Surveillance · Exercice : analyse des statuts d\'une coopérative d\'épargne et de crédit','duree'=>$s[0]],
                ['titre'=>'Produits financiers des SFD et analyse des besoins clients','contenus'=>'Épargne volontaire et obligatoire : caractéristiques et gestion · Crédit solidaire de groupe (modèle Grameen) : fonctionnement et gestion du groupe · Crédit individuel : conditions, garanties et suivi · Leasing et crédit-bail pour les petites entreprises · Assurance crédit et assurance-vie emprunteur · Produits saisonniers et warrantage · Mobile Money et portefeuilles numériques dans les SFD · Exercice : conception d\'un produit de crédit adapté à une clientèle cible','duree'=>$s[1]],
                ['titre'=>'Instruction et analyse des dossiers de crédit','contenus'=>'Collecte des informations : visite terrain, entretien, documents · Analyse des revenus et des charges du ménage ou de l\'entreprise · Capacité de remboursement et niveau d\'endettement · Analyse des garanties disponibles (caution, nantissement, warrantage) · Scoring de crédit simplifié adapté aux SFD · Comité de crédit : présentation et décision · Décaissement et gestion contractuelle · Exercice : instruction d\'un dossier de crédit agricole','duree'=>$s[2]],
                ['titre'=>'Gestion du risque de crédit et recouvrement','contenus'=>'Indicateurs de qualité du portefeuille : PAR 30, PAR 90, taux de perte · Classification BCEAO des créances des SFD · Causes des impayés : analyse terrain et préventions · Stratégies de recouvrement amiable : relance, négociation, restructuration · Recouvrement contentieux : voies juridiques (OHADA) · Provisions et passage en pertes · Audit du portefeuille et rapport au CA · Exercice : analyse d\'un portefeuille et calcul des provisions requises','duree'=>$s[3]],
                ['titre'=>'Performance financière et pilotage des SFD','contenus'=>'Compte de résultat d\'un SFD : revenus d\'intérêts, charges financières, frais opérationnels · Taux d\'intérêt effectif global (TIEG) et coût réel du crédit · Autosuffisance opérationnelle (OSS) et autosuffisance financière (FSS) · Indicateurs CGAP de performance sociale et financière · Système d\'Information de Gestion (SIG) : sélection et utilisation · Tableaux de bord de pilotage et rapport au Conseil d\'Administration · Exercice : construction d\'un tableau de bord de performance pour un SFD','duree'=>$s[4]],
                ['titre'=>'Gouvernance, transformation digitale et supervision','contenus'=>'Bonnes pratiques de gouvernance coopérative : prévention des conflits d\'intérêts · Digitalisation des SFD : Mobile Money, paiements numériques, KYC digital · Rapport de supervision BCEAO : préparation et procédure · Fusion et transformation des SFD : enjeux et procédures · Responsabilité sociale des SFD : inclusion financière et clientèle vulnérable · Plan stratégique d\'un SFD : méthodologie et priorités','duree'=>$s[5]],
                ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : instruction d\'un dossier de crédit + calcul du PAR + plan de redressement sur cas inédit · Correction individualisée et feedback · Remise du certificat IBIG EDUFORM "Microfinance & SFD" et plan de développement','duree'=>$s[6]],
            ],
        };
    }

    /* ══ Filière Cacao / Agrobusiness / Agriculture ══ */
    if (preg_match('/cacao|agrob[uo]siness|agro.{0,5}aliment|filière.*certif|tracabilit[eé]|eudr|rainforest|fairtrade/ui', $nom)) {
        $s = $split(max(14, $heures), 6);
        return match($niveau) {
            'debutant' => [
                ['titre'=>'La filière cacao ivoirienne : de la cabosseà la tablette de chocolat','contenus'=>'Pourquoi la Côte d\'Ivoire est le premier producteur mondial de cacao · Le voyage du cacao : plantation → récolte → fermentation → séchage → exportation → transformation · Les acteurs que tu rencontreras : producteur, coopérative, pisteur, exportateur, CCC · Le Conseil Café-Cacao (CCC) : son rôle dans la fixation du prix bord-champ · Pourquoi certifier son cacao ? Primes et accès aux marchés premium · Vocabulaire essentiel de la filière · Exercice : tracer la chaîne de valeur complète d\'une cabosseachetée en Hollande','duree'=>$s[0]],
                ['titre'=>'Les certifications cacao : ce qu\'elles exigent vraiment','contenus'=>'Rainforest Alliance (RA) : ce qu\'on doit faire et ne pas faire · Fairtrade / Max Havelaar : commerce équitable expliqué simplement · Certification biologique : cahier des charges et contraintes pour le producteur · Ce qu\'un auditeur regarde lors d\'un audit sur le terrain · Documents minimums à avoir dans une coopérative certifiée · Primes de durabilité : combien ça rapporte concrètement · Exercice : remplir une checklist d\'audit RA basique pour une exploitation fictive','duree'=>$s[1]],
                ['titre'=>'Tenir les registres de sa coopérative','contenus'=>'Pourquoi les registres sont obligatoires pour la certification · Registre des membres : comment le créer et le tenir à jour · Registre de collecte : enregistrer chaque livraison du producteur · Registre de vente et de traçabilité : lot par lot vs masse-bilan expliqués simplement · SECP du CCC : c\'est quoi et comment l\'utiliser · Photos et preuves : ce que les auditeurs aiment trouver · Exercice : tenue de registres sur un mois fictif de collecte','duree'=>$s[2]],
                ['titre'=>'Bonnes pratiques agricoles pour les producteurs','contenus'=>'Pourquoi les bonnes pratiques agricoles augmentent les rendements et la qualité · Agroforesterie simple : planter des arbres d\'ombrage sans perdre de productivité · Les maladies du cacao : reconnaître CSSV et pourriture brune facilement · Utiliser les engrais et pesticides en sécurité · Interdire le travail des enfants : comment comprendre et appliquer le protocole · Santé et sécurité : équipements de protection simples · Exercice : plan de mise en conformité BPA pour une parcelle de 2 hectares','duree'=>$s[3]],
                ['titre'=>'Vendre mieux : accès aux marchés et négociation','contenus'=>'Différence entre vente bord-champ et contrat d\'exportation · Comprendre les Incoterms dans la filière cacao : FOB Abidjan · Négocier le prix avec un exportateur : techniques et droits du producteur · Fonds de prime Fairtrade : comment est-il géré et utilisé · Financement de campagne : crédit de préfinancement, warrantage · Projets de développement communautaire financés par les primes · Exercice : simulation de négociation d\'un contrat de vente cacao certifié','duree'=>$s[4]],
                ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : audit interne guidé d\'une coopérative cacao fictive (registres + BPA + travail des enfants + gestion prime) avec plan d\'amélioration · Correction individualisée et feedback formateur · Remise du certificat IBIG EDUFORM "Filière Cacao & Certification" Niveau Débutant et plan de mise en conformité','duree'=>$s[5]],
            ],
            'expert' => [
                ['titre'=>'Stratégie de positionnement d\'une coopérative cacao sur les marchés premium','contenus'=>'Analyse des marchés premium : chocolat fin d\'origine, single origin, bean-to-bar · Benchmarking international : modèles de coopératives leaders (Ghana, Équateur, Pérou) · Stratégie de différenciation : terroir, fermentation contrôlée, qualité sensorielle · Structuration d\'un programme de durabilité multi-standards (RA + Fairtrade + Biologique) · Relation commerciale long terme avec les chocolatiers artisanaux et grandes marques · Valorisation des sous-produits : coques, beurre de cacao, gel de cacao · Exercice : plan stratégique de montée en gamme d\'une coopérative vers le marché premium','duree'=>$s[0]],
                ['titre'=>'EUDR et conformité réglementaire avancée','contenus'=>'Règlement européen EUDR (EU Deforestation Regulation) : texte complet, dates, obligations des opérateurs · Cartographie géospatiale des parcelles : méthodes GPS avancées, QGIS, polygones · Due Diligence EUDR : évaluation du risque déforestation, mesures d\'atténuation, déclaration · Systèmes de traçabilité numériques : CocoaTrace, Source Trace, systèmes propriétaires · Audit de conformité EUDR pour une coopérative de 5 000 membres · Stratégie de réponse aux non-conformités fournisseurs · Exercice : conception d\'un système EUDR opérationnel pour une grande coopérative','duree'=>$s[1]],
                ['titre'=>'Gestion agronomique avancée et amélioration de la qualité','contenus'=>'Clones performants KKM22, SCA6, CC36 : sélection et multiplication variétale · Fermentation contrôlée : protocoles scientifiques, mesure des températures et pH · Séchage optimisé : taux d\'humidité, durées, techniques alternatives · Défauts qualité et classification selon grading ICCO · Bonnes pratiques post-récolte pour réduire les mycotoxines (OTA) · Programme de réhabilitation des vergers vieillissants en CI · Exercice : conception d\'un programme qualité de fermentation pour 200 producteurs','duree'=>$s[2]],
                ['titre'=>'Gouvernance avancée des coopératives certifiées','contenus'=>'Gouvernance de haute performance : séparation des fonctions, audit interne annuel · Gestion des fonds de prime multi-standards : comptabilité ségrégée, priorisation des projets · Rapport annuel aux certifieurs : structure, données et standards d\'excellence · Systèmes de contrôle interne (SCI) pour la prévention des fraudes en coopérative · Travail des enfants : système CLMRS (Child Labour Monitoring and Remediation) · Mécanisme de plainte des membres : conformité aux standards sociaux · Exercice : conception d\'un système de gouvernance pour une coopérative de 10 000 membres','duree'=>$s[3]],
                ['titre'=>'Financement et investissement dans la filière agrobusiness','contenus'=>'Structuration financière d\'une coopérative : fonds propres, dette, refinancement AFD · Préfinancement de campagne : négociation avec les banques et fonds d\'investissement · Impact investing dans le cacao africain : Althelia, Root Capital, Nuveen · Certification carbone et marchés volontaires : REDD+, Gold Standard pour les coopératives · Transition vers la transformation locale : investir dans les équipements de première transformation · Exportation directe : agrément exportateur, Cocobod/CCC, logistique portuaire · Exercice : montage d\'un plan d\'investissement pour la première transformation locale','duree'=>$s[4]],
                ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : conception d\'un programme de conformité multi-standards (EUDR + RA + Fairtrade) + plan d\'investissement qualité + stratégie commerciale premium sur cas inédit · Correction individualisée par un expert de la filière cacao · Remise du certificat IBIG EDUFORM "Filière Cacao & Certification" Niveau Expert et plan de développement stratégique de la coopérative','duree'=>$s[5]],
            ],
            default => [
                ['titre'=>'Organisation de la filière et enjeux de la certification','contenus'=>'Structure de la filière cacao ivoirienne : production, traitement, exportation · Acteurs : producteurs, coopératives, pisteurs, exportateurs agréés, Conseil du Café-Cacao (CCC) · Prix bord-champ et mécanismes de fixation des prix · Certifications majeures : Rainforest Alliance (RA), Fairtrade/Max Havelaar, UTZ Certified · Avantages économiques et primes de durabilité · Exigences de base communes aux standards internationaux · Exercice : comparaison des exigences RA vs Fairtrade sur un cahier des charges réel','duree'=>$s[0]],
                ['titre'=>'Systèmes de traçabilité et gestion documentaire','contenus'=>'Principes de la traçabilité : lot par lot vs masse-bilan · SECP (Système Électronique de la Chaîne de Production) du CCC · Registres de production, de collecte et de livraison · Technologies de traçabilité : applications mobiles, QR codes, blockchain · Gestion des documents de certification : audits internes, checklists, plans d\'action · Archivage et durée de conservation · Exercice : mise en place d\'un système de registres pour une coopérative de 500 membres','duree'=>$s[1]],
                ['titre'=>'Loi EUDR et Due Diligence sur la déforestation','contenus'=>'Règlement européen EUDR (2024) : texte, dates d\'application et produits concernés · Obligation de Due Diligence : cartographie géospatiale des parcelles · Systèmes de géolocalisation : GPS, QGIS, satellites · Collecte des coordonnées GPS des parcelles producteurs · Plan de gestion du risque EUDR : évaluation et mesures d\'atténuation · Communication et accompagnement des producteurs · Sanctions et impact sur l\'accès au marché européen','duree'=>$s[2]],
                ['titre'=>'Bonnes Pratiques Agricoles (BPA) et durabilité','contenus'=>'BPA certifiées selon RA : gestion de l\'ombre, agroforesterie, rejuvénation · Lutte intégrée contre les maladies (CSSV, pourriture brune) · Gestion durable des sols et de l\'eau · Fertilisation raisonnée et réduction des intrants chimiques · Travail des enfants : protocole ivoirien et procédures de remédiation · Sécurité et santé au travail agricole : EPI, stockage des pesticides · Exercice : audit interne d\'une exploitation cacao selon le référentiel RA','duree'=>$s[3]],
                ['titre'=>'Gestion des coopératives certifiées et commercialisation premium','contenus'=>'Gouvernance coopérative : AG, Conseil d\'Administration, contrôle interne · Fonds de prime Fairtrade/RA : gestion et projets de développement communautaire · Contractualisation avec les acheteurs internationaux : contrats multi-annuels et prix de référence · Accès aux marchés premium : exportateurs, chocolatiers, grandes marques · Gestion de la trésorerie de campagne : financement et remboursement · Reporting aux organes de certification et préparation des audits externes','duree'=>$s[4]],
                ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : audit interne simulé d\'une coopérative cacao (RA ou Fairtrade) avec plan d\'action correctif · Correction individualisée et feedback · Remise du certificat IBIG EDUFORM "Filière Cacao & Certification" et plan de développement','duree'=>$s[5]],
            ],
        };
    }

    /* ══ Odoo ERP ══ */
    if (preg_match('/odoo|erp.*gestion|gestion.{1,10}int[eé]gr[eé]e/ui', $nom)) {
        $s = $split(max(16, $heures), 7);
        return match($niveau) {
            'debutant' => [
                ['titre'=>'C\'est quoi Odoo ? Découverte et premiers pas','contenus'=>'C\'est quoi un ERP et pourquoi une PME en a besoin · Odoo en Côte d\'Ivoire : qui l\'utilise et pour quoi · Navigation dans l\'interface : menus, listes, fiches, formulaires · Créer son premier utilisateur et comprendre les droits d\'accès · Paramétrer sa société : nom, logo, devise, langue française · Sauvegarder et restaurer la base de données · Exercice guidé : créer et configurer une société ivoirienne fictive de A à Z','duree'=>$s[0]],
                ['titre'=>'Mon premier devis et ma première facture','contenus'=>'Créer un contact client : formulaire, informations essentielles · Créer un produit ou service à facturer · Faire un devis : saisie, lignes, taxes TVA · Confirmer un devis en commande · Émettre une facture client et enregistrer le paiement · Suivre l\'état des factures impayées · Exercice : cycle complet devis → commande → facture → paiement pour un client fictif','duree'=>$s[1]],
                ['titre'=>'Gérer ses achats et son stock simplement','contenus'=>'Créer un fournisseur et passer une commande d\'achat · Réceptionner les marchandises et valider l\'entrée en stock · Voir son stock disponible par produit · Créer une facture fournisseur liée à la commande · Faire un inventaire physique et corriger les écarts · Produits de service vs produits stockés : la différence · Exercice : cycle d\'achat complet pour une PME de distribution ivoirienne','duree'=>$s[2]],
                ['titre'=>'Comptabilité de base sous Odoo','contenus'=>'Plan de comptes OHADA sous Odoo : comprendre la structure · Saisir une écriture manuelle · Lettrage des comptes clients et fournisseurs · Rapprochement bancaire simplifié : importer son relevé · Voir son bilan et son compte de résultat · Déclaration de TVA ivoirienne sous Odoo · Exercice : saisir un mois de comptabilité simple (10 opérations)','duree'=>$s[3]],
                ['titre'=>'RH et paie : les bases sous Odoo','contenus'=>'Créer une fiche employé complète · Gérer les congés et absences · Configurer un contrat de travail ivoirien · Comprendre la structure d\'un bulletin de paie Odoo · Paramétrer les cotisations CNPS et IRVM simplement · Éditer et imprimer un bulletin de paie · Exercice : création et édition du bulletin de paie d\'un employé fictif','duree'=>$s[4]],
                ['titre'=>'Tableaux de bord et premiers rapports','contenus'=>'Les tableaux de bord natifs d\'Odoo : ventes, achats, trésorerie · Personnaliser son tableau de bord en glissant-déposant les vignettes · Filtres et regroupements : retrouver ce qu\'on cherche · Exporter ses données vers Excel · Planifier l\'envoi automatique d\'un rapport · Exercice : construction du tableau de bord de gestion de sa société','duree'=>$s[5]],
                ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : traitement d\'un mois d\'activité complet (devis + achat + stock + facturation + paie) sur une société fictive · Correction individualisée et feedback formateur · Remise du certificat IBIG EDUFORM "Odoo ERP" Niveau Débutant et plan de mise en œuvre','duree'=>$s[6]],
            ],
            'expert' => [
                ['titre'=>'Architecture avancée et développement Odoo','contenus'=>'Architecture technique Odoo : ORM, API, modèles d\'héritage (classical, prototype, delegation) · Développement de modules personnalisés : structure, manifeste, modèles, vues, contrôleurs · Xpath et héritages de vues : personnalisation sans modification du code source · API JSON-RPC et XML-RPC : intégration avec systèmes tiers · Odoo.sh et déploiement cloud : CI/CD, branches, staging · Sécurité avancée : règles d\'accès, listes de droits, filtres d\'enregistrement · Exercice : développement d\'un module personnalisé de gestion métier','duree'=>$s[0]],
                ['titre'=>'Comptabilité avancée et conformité fiscale ivoirienne','contenus'=>'Plan de comptes OHADA complet : paramétrage analytique et multi-sociétés · Clôture fiscale SYSCOHADA sous Odoo : procédures et états à produire · TVA ivoirienne complexe : TVA sur encaissement, TVA non déductible, DAS2 · Liasse fiscale numérique pour la DGI : paramétrage et export · Consolidation multi-sociétés : intercos, éliminations et rapports consolidés · Reporting analytique : axes, plans analytiques, tableaux de bord finance · Exercice : paramétrage complet d\'un groupe multi-entités sous Odoo','duree'=>$s[1]],
                ['titre'=>'Supply chain avancée : MRP, qualité et logistique multi-entrepôts','contenus'=>'MRP (Manufacturing Resource Planning) : gammes, nomenclatures, ordres de fabrication · Gestion de la qualité : points de contrôle, alertes, non-conformités · Multi-entrepôts et règles de routage : flux push/pull, cross-docking · Valorisation des stocks : FIFO, prix moyen, prix standard · Prévision de demande et planification MRP2 · Intégration logistique : EDI, étiquettes GS1, code-barres · Exercice : paramétrage d\'une chaîne de production complète sous Odoo Manufacturing','duree'=>$s[2]],
                ['titre'=>'CRM avancé, marketing automation et ventes complexes','contenus'=>'Pipeline CRM avancé : scoring des leads, segmentation automatique · Marketing automation Odoo : séquences d\'emailing, nurturing · Abonnements et revenus récurrents (MRR/ARR) · Portail client : self-service, signature électronique, paiement en ligne · Règles de prix avancées : tarifs configurables, pricelists multi-devises · Programme de fidélité et gestion des promotions complexes · Exercice : conception d\'un dispositif complet de CRM B2B avec automatisation','duree'=>$s[3]],
                ['titre'=>'Odoo.io, BI avancé et intégrations système','contenus'=>'Odoo Studio : développement no-code avancé, applications personnalisées · Odoo BI et tableaux de bord analytiques : construction de vues pivots complexes · Intégration Power BI via OData : connexion et modèles de données · Connecteurs API : Zapier, Make, webhooks natifs Odoo · Intégration Mobile Money ivoirien : Orange, MTN, Wave via gateway · Migration de données : OCA migration tools, scripts d\'import avancés · Exercice : construction d\'un écosystème d\'intégration Odoo + BI + API','duree'=>$s[4]],
                ['titre'=>'Gestion de projet d\'implémentation Odoo','contenus'=>'Méthodologie de projet Odoo : Odoo Success Pack, Agile adapté ERP · Gestion du changement : formation des utilisateurs, résistances, adoption · Paramétrage des tests d\'acceptance (UAT) et gestion des recettes · SLA et contrats de support : SSII partenaire Odoo vs interne · Gouvernance de la données maîtres : référentiel, qualité, gouvernance · Roadmap évolutive : upgrades Odoo, nouvelles fonctionnalités, maintenance · Exercice : plan complet d\'implémentation Odoo pour une PME ivoirienne de 50 personnes','duree'=>$s[5]],
                ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : développement d\'un module personnalisé + paramétrage avancé multi-sociétés + plan d\'intégration API sur cas inédit · Correction individualisée par un expert Odoo certifié · Remise du certificat IBIG EDUFORM "Odoo ERP" Niveau Expert et plan de projet d\'implémentation','duree'=>$s[6]],
            ],
            default => [
                ['titre'=>'Architecture Odoo et navigation dans l\'interface','contenus'=>'Architecture technique d\'Odoo (MVC, ORM, modules) · Navigation : menus, formulaires, listes, kanban · Gestion des utilisateurs, profils et droits d\'accès · Paramètres généraux : société, devise, langue, exercice fiscal · Base de données : sauvegardes et restauration · Exercice : configuration de base d\'une nouvelle société ivoirienne','duree'=>$s[0]],
                ['titre'=>'Module Comptabilité : plan de comptes, facturation et rapprochement','contenus'=>'Plan de comptes OHADA sous Odoo : import et configuration · Saisie des factures clients et fournisseurs · Rapprochement bancaire : import des relevés et lettrage automatique · TVA ivoirienne : paramétrage des taxes et déclaration · États financiers natifs : bilan, compte de résultat, balance générale · Gestion des paiements partiels et des avoirs · Exercice : saisie d\'un mois de comptabilité d\'une PME ivoirienne','duree'=>$s[1]],
                ['titre'=>'Module Ventes & CRM : devis, commandes et pipeline commercial','contenus'=>'Paramétrage de la liste de prix et des conditions commerciales · Création et envoi de devis professionnels · Confirmation de commande et workflow de validation · Pipeline CRM : étapes, probabilité et prévision de chiffre d\'affaires · Gestion des clients et segmentation · Activités et relances commerciales automatiques · Rapports commerciaux : CA par vendeur, par client, par produit · Exercice : gestion complète d\'un cycle de vente','duree'=>$s[2]],
                ['titre'=>'Module Achats, Stocks et gestion d\'entrepôt','contenus'=>'Création des fournisseurs et conditions d\'achat · Appel d\'offres fournisseurs et comparaison · Réceptions et contrôle de qualité à l\'arrivée · Gestion des stocks : emplacements, lots et numéros de série · Règles de réapprovisionnement (min/max, orderpoints) · Inventaire physique et ajustements de stock · Transferts inter-entrepôts · Exercice : gestion d\'un cycle d\'achat complet','duree'=>$s[3]],
                ['titre'=>'Module RH, Paie et gestion des employés','contenus'=>'Création des fiches employés et structure salariale · Contrats de travail sous Odoo · Gestion des congés et des absences · Feuilles de temps et valorisation des coûts · Paie : règles salariales, cotisations CNPS, IRVM (paramétrages CI) · Évaluations et entretiens annuels · Rapport bilan social · Exercice : établissement d\'un bulletin de paie sous Odoo','duree'=>$s[4]],
                ['titre'=>'Reporting, personnalisation et bonnes pratiques d\'implémentation','contenus'=>'Moteur de reporting Odoo : personnalisation des états existants · Business Intelligence natif : tableaux de bord et filtres personnalisés · Export vers Excel et Power BI · Personnalisation sans code : studio Odoo · Bonnes pratiques d\'implémentation : conduite du changement, formation des utilisateurs · Maintenance évolutive et mises à jour · Exercice : construction d\'un tableau de bord de gestion PME sous Odoo','duree'=>$s[5]],
                ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : paramétrage complet d\'une société fictive et traitement d\'un mois d\'activité (achats + ventes + paie + états financiers) · Correction individualisée et feedback · Remise du certificat IBIG EDUFORM "Odoo ERP" et plan de mise en œuvre','duree'=>$s[6]],
            ],
        };
    }

    /* ══ Cybersécurité / Administration réseau ══ */
    if (preg_match('/cybers[eé]curit[eé]|s[eé]curit[eé]\s+inform|administration\s+r[eé]seau|hacking|pentest|pfsense|firewall.*pme/ui', $nom)) {
        $s = $split(max(16, $heures), 7);
        return match($niveau) {
            'debutant' => [
                ['titre'=>'Internet, réseaux et cybersécurité : les bases en langage simple','contenus'=>'C\'est quoi un réseau informatique ? LAN, Wi-Fi, Internet expliqués · Les équipements réseau : box internet, switch, routeur — que fait chacun · Une adresse IP : c\'est quoi et à quoi ça sert · Les dangers d\'Internet : virus, hameçonnage, arnaque en ligne · Pourquoi les entreprises ivoiriennes sont aussi ciblées · Les 10 règles d\'hygiène informatique à appliquer dès demain · Exercice : audit de ses propres habitudes numériques et plan de correction','duree'=>$s[0]],
                ['titre'=>'Protéger ses mots de passe et son identité numérique','contenus'=>'Les mots de passe : pourquoi "12345" est une catastrophe · Créer des mots de passe forts et s\'en souvenir · Gestionnaires de mots de passe : Bitwarden, 1Password — comment les utiliser · Authentification à deux facteurs (2FA) : activer et utiliser · Reconnaître un email de phishing : indices visuels et techniques · Vol d\'identité et usurpation : comment ça arrive, comment se protéger · Exercice : audit de sécurité de ses propres comptes en ligne','duree'=>$s[1]],
                ['titre'=>'Protéger son ordinateur et son smartphone','contenus'=>'Antivirus : à quoi ça sert vraiment, Windows Defender vs solutions tierces · Mises à jour : pourquoi ne jamais les reporter · Wi-Fi public et Wi-Fi personnel : risques et règles de prudence · Sauvegardes : la règle 3-2-1 expliquée simplement · Chiffrement du disque dur : activer Bitlocker ou FileVault en 5 minutes · Sécurité du smartphone : verrouillage, permissions, apps malveillantes · Exercice : mise en conformité sécurité d\'un PC et d\'un smartphone','duree'=>$s[2]],
                ['titre'=>'Sécurité en entreprise : règles de base pour les employés','contenus'=>'La sécurité informatique est l\'affaire de tous : exemples d\'erreurs humaines coûteuses · Politique d\'utilisation acceptable : ce qu\'on peut et ne peut pas faire · Gestion des accès : principe du moindre privilège expliqué simplement · Clés USB et appareils personnels : risques et règles · Comment signaler un incident ou une suspicion · Charte informatique : ce qu\'elle dit et pourquoi l\'appliquer · Exercice : identification des comportements à risque dans 10 scénarios d\'entreprise','duree'=>$s[3]],
                ['titre'=>'Reconnaître et réagir aux cyberattaques courantes','contenus'=>'Ransomware : comment ça fonctionne et que faire si ça arrive · Phishing ciblé (spear phishing) : comment le reconnaître dans un contexte africain · Fraude au virement et arnaque BEC (Business Email Compromise) · Ingénierie sociale : manipulation psychologique et techniques des escrocs · Premier réflexe en cas d\'incident : déconnecter, alerter, ne pas paniquer · Droits et recours en CI : ARTCI, PLCC, dépôt de plainte informatique · Exercice : simulation de phishing et réponse à un incident fictif','duree'=>$s[4]],
                ['titre'=>'Introduction aux réseaux et aux pare-feux de PME','contenus'=>'Schéma réseau d\'une PME type : composants et rôles · Configurer un réseau Wi-Fi sécurisé pour son bureau · Pare-feu : c\'est quoi et comment le box internet en fait un · Séparer le réseau professionnel du réseau invité · VPN : pourquoi et comment l\'utiliser pour le télétravail · Sauvegardes réseau NAS : introduction aux solutions abordables · Exercice : schématiser et sécuriser le réseau d\'une PME fictive','duree'=>$s[5]],
                ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : audit de sécurité d\'un poste de travail + identification de menaces + plan de sécurité pour une PME fictive · Correction individualisée et feedback formateur · Remise du certificat IBIG EDUFORM "Cybersécurité & Réseau" Niveau Débutant et guide de bonnes pratiques personnalisé','duree'=>$s[6]],
            ],
            'expert' => [
                ['titre'=>'Tests de pénétration avancés : méthodologie et outils offensifs','contenus'=>'Méthodologie pentest OWASP, PTES, TIBER-EU : phases et livrables · Reconnaissance passive et active : OSINT, Shodan, Recon-ng, theHarvester · Exploitation : Metasploit Framework, Burp Suite Pro, SQLmap — cas pratiques · Post-exploitation : escalade de privilèges, persistance, mouvement latéral · Pentest web avancé : SSRF, XXE, IDOR, désérialisation non sécurisée · Rapport de pentest professionnel : structure, CVSS, remédiation · Exercice : pentest complet d\'un scénario d\'entreprise ivoirienne en lab isolé','duree'=>$s[0]],
                ['titre'=>'Threat Hunting et détection des menaces avancées','contenus'=>'Threat Intelligence : MITRE ATT&CK, indicateurs de compromission (IoC) · SIEM (Security Information and Event Management) : Elastic SIEM / Splunk · Règles de détection Sigma : écriture et déploiement · Threat hunting proactif : hypothèses et méthodes de chasse · Malware analysis statique et dynamique : outils sandboxing (Any.run, Cuckoo) · APT (Advanced Persistent Threats) africains : cas documentés et défense · Exercice : investigation complète d\'un incident de sécurité sur logs réels','duree'=>$s[1]],
                ['titre'=>'Architecture Zero Trust et sécurité cloud','contenus'=>'Paradigme Zero Trust : principes, périmètre réseau effacé, identité comme nouveau périmètre · Micro-segmentation réseau : implémentation VMware NSX, Illumio · Cloud security posture management (CSPM) : AWS, Azure, GCP · IAM avancé : RBAC, ABAC, Privileged Access Management (PAM) · CASB (Cloud Access Security Broker) : contrôle des usages cloud non sanctionnés · Sécurisation des API REST : OAuth 2.0, JWT, rate limiting, WAF · Exercice : conception d\'une architecture Zero Trust pour une entreprise africaine','duree'=>$s[2]],
                ['titre'=>'Gouvernance de la cybersécurité et conformité','contenus'=>'ISO 27001 : structure, contrôles et certification · NIST Cybersecurity Framework : profil actuel, profil cible, roadmap · RGPD et loi ivoirienne protection des données (ARTCI) : conformité approfondie · Politique de Sécurité des Systèmes d\'Information (PSSI) : rédaction et mise en œuvre · Risk Management en cybersécurité : ISO 27005, évaluation et traitement des risques · Indicateurs de performance cybersécurité (KRI, KPI CISO) · Exercice : réalisation d\'un Risk Assessment complet ISO 27005','duree'=>$s[3]],
                ['titre'=>'Réponse aux incidents de haut niveau et cyber-résilience','contenus'=>'Équipe CSIRT/CERT : organisation, rôles, procédures d\'activation · Plans de réponse aux incidents avancés : runbooks, playbooks, SOAR · Investigation numérique (forensics) : acquisition de preuves, chaîne de custody · Timeline analysis et reconstruction d\'une attaque · Communication de crise cyber : équipes dirigeantes, régulateurs, médias · PRA / PCA orienté cyberattaque : RTO/RPO dans un contexte de ransomware · Exercice : simulation de crise cyber (tabletop exercise) avec scénario ransomware','duree'=>$s[4]],
                ['titre'=>'Sécurité opérationnelle OT/IoT et environnements spécifiques africains','contenus'=>'Convergence IT/OT : spécificités des systèmes industriels (ICS, SCADA) · Sécurité IoT en contexte africain : objets connectés, Mobile Money, smart metering · Cybersécurité bancaire avancée : directive BCEAO, SWIFT CSCF · Sécurité télécoms : SS7/Diameter vulnerabilities, protection des réseaux mobiles · Cybermenaces spécifiques à l\'Afrique : arnaque419, SIM swapping, fraude Mobile Money · Cadre légal des investigations cybercriminelles en CI : PLCC et coopération internationale · Exercice : conception d\'un programme de sécurité OT pour une infrastructure critique','duree'=>$s[5]],
                ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : pentest complet + analyse de logs SIEM + plan de gouvernance ISO 27001 sur infrastructure fictive inédite · Correction individualisée par un expert en cybersécurité certifié · Remise du certificat IBIG EDUFORM "Cybersécurité & Réseau" Niveau Expert et plan de sécurisation stratégique','duree'=>$s[6]],
            ],
            default => [
                ['titre'=>'Fondamentaux des réseaux : TCP/IP, routage et topologies','contenus'=>'Modèle OSI et TCP/IP : couches et protocoles · Adressage IP (IPv4/IPv6), sous-réseaux et masques CIDR · Équipements réseau : routeurs, switches, hubs, points d\'accès · LAN, WAN, VLAN et segmentation réseau · Protocoles essentiels : DNS, DHCP, HTTP/S, SMTP, FTP · Analyse du trafic réseau avec Wireshark · Exercice : conception du plan d\'adressage d\'un réseau de PME','duree'=>$s[0]],
                ['titre'=>'Administration Windows Server et Active Directory','contenus'=>'Installation et configuration de Windows Server 2019/2022 · Active Directory : domaine, UO, groupes et GPO · Gestion des comptes utilisateurs et politiques de mot de passe · Group Policy Objects (GPO) : déploiement de configurations et restrictions · DNS et DHCP intégrés à l\'AD · Sauvegarde et restauration de l\'AD · Exercice : mise en place d\'un domaine Active Directory pour une PME','duree'=>$s[1]],
                ['titre'=>'Pare-feux, VPN et sécurisation du périmètre','contenus'=>'pfSense / OPNsense : installation et configuration · Règles de filtrage : whitelisting, blacklisting, NAT · VPN IPsec et OpenVPN : accès distant sécurisé · Proxy et filtrage de contenu web · DMZ : conception et mise en œuvre · IDS/IPS : Snort/Suricata sur pfSense · Segmentation des réseaux Wi-Fi (WPA3, SSID séparés, 802.1X) · Exercice : configuration complète d\'un pare-feu pfSense sur VM','duree'=>$s[2]],
                ['titre'=>'Audit de sécurité et identification des vulnérabilités','contenus'=>'Méthodologie d\'audit de sécurité : périmètre, règles d\'engagement · Scan de vulnérabilités avec Nessus/OpenVAS · Tests de pénétration éthiques : Kali Linux, Nmap, Metasploit (notions) · Top 10 OWASP : vulnérabilités web et exemples d\'exploitation · Social engineering : phishing, vishing, sensibilisation des employés · Rapport d\'audit de sécurité : structure et recommandations · Exercice : audit simulé d\'un réseau de PME','duree'=>$s[3]],
                ['titre'=>'Protection des données, conformité et politiques de sécurité','contenus'=>'RGPD et loi ivoirienne sur la protection des données (ARTCI) · Politique de Sécurité des Systèmes d\'Information (PSSI) : rédaction · Classification des données et gestion des droits d\'accès · DLP (Data Loss Prevention) : outils et procédures · Chiffrement des données : disques, messagerie, communications · Gestion des mots de passe : gestionnaire, MFA, bonnes pratiques · Charte informatique : contenu et mise en œuvre contractuelle','duree'=>$s[4]],
                ['titre'=>'Réponse aux incidents, sauvegardes et continuité d\'activité','contenus'=>'Plan de Réponse aux Incidents (PRI) : étapes et responsabilités · Détection et qualification d\'un incident de sécurité · Investigation et forensics de base : logs, timeline, preuves · Plan de sauvegarde 3-2-1 : stratégie, outils et tests de restauration · Plan de Reprise d\'Activité (PRA) et Plan de Continuité (PCA) · RTO et RPO : calcul et objectifs · Gestion de la communication de crise en cas de cyberattaque','duree'=>$s[5]],
                ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : audit de sécurité + configuration pare-feu + plan de sécurité sur scénario de PME inédit · Correction individualisée et feedback · Remise du certificat IBIG EDUFORM "Cybersécurité & Réseau" et plan de mise en conformité','duree'=>$s[6]],
            ],
        };
    }

    /* ══ WordPress / SEO ══ */
    if (preg_match('/wordpress|woocommerce|seo\b|r[eé]f[eé]rencement\s+(naturel|web)|site\s+(web|professionnel).*cr[eé]er/ui', $nom)) {
        $s = $split(max(14, $heures), 6);
        return match($niveau) {
            'debutant' => [
                ['titre'=>'Mon premier site web : de l\'idée à la mise en ligne','contenus'=>'C\'est quoi WordPress ? Pourquoi c\'est la solution idéale pour débuter · Vocabulaire de base : hébergement, nom de domaine, CMS, thème, plugin · Choisir son hébergement en Côte d\'Ivoire : critères et coûts réels · Acheter son nom de domaine (.ci, .com, .africa) pas à pas · Installer WordPress en 5 clics depuis le cPanel · Premiers paramètres : titre du site, langue française, fuseau horaire · Exercice : mise en ligne de son premier site WordPress opérationnel','duree'=>$s[0]],
                ['titre'=>'Construire ses pages sans coder','contenus'=>'Le tableau de bord WordPress : les menus essentiels à connaître · Installer un thème gratuit professionnel adapté à son activité · Créer les pages indispensables : Accueil, À propos, Services, Contact · Utiliser l\'éditeur Gutenberg pour ajouter du texte, images, boutons · Ajouter un menu de navigation simple · Formulaire de contact : installation de Contact Form 7 en 10 minutes · Exercice : construction complète de son site personnel ou professionnel','duree'=>$s[1]],
                ['titre'=>'Publier du contenu : blog et actualités','contenus'=>'Articles de blog : c\'est quoi et pourquoi en écrire · Créer et publier son premier article pas à pas · Catégories et tags : organiser son contenu · Ajouter et optimiser les images : format, poids, taille · Planifier la publication d\'un article · Partager ses articles sur les réseaux sociaux ivoiriens · Exercice : publication de 3 articles de blog optimisés','duree'=>$s[2]],
                ['titre'=>'Le SEO en langage simple : être trouvé sur Google','contenus'=>'C\'est quoi le SEO (référencement naturel) et pourquoi c\'est important · Comment Google choisit quel site apparaît en premier · Mots-clés : comprendre ce que les gens cherchent avec Google · Installer Yoast SEO et configurer les bases · Écrire un titre et une description pour Google · Inscrire son entreprise sur Google Business Profile · Exercice : optimisation SEO basique de 3 pages de son site','duree'=>$s[3]],
                ['titre'=>'Vendre en ligne avec WooCommerce','contenus'=>'Installer et activer WooCommerce sur son site WordPress · Ajouter ses premiers produits : photos, description, prix · Configurer les modes de livraison en Côte d\'Ivoire · Ajouter un paiement Mobile Money (Orange, MTN, Wave) · Recevoir et traiter sa première commande · Promotions et codes coupon · Exercice : ouverture d\'une boutique e-commerce avec 5 produits réels','duree'=>$s[4]],
                ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : création d\'un site complet (pages + blog + SEO basique) pour une activité fictive donnée · Correction individualisée et feedback formateur · Remise du certificat IBIG EDUFORM "WordPress & SEO" Niveau Débutant et plan de développement de son site','duree'=>$s[5]],
            ],
            'expert' => [
                ['titre'=>'WordPress avancé : architecture, performance et sécurité','contenus'=>'Architecture WordPress : hooks, filtres, shortcodes, template hierarchy · Développement de thèmes enfants avancés : PHP, CSS custom, functions.php · Full Site Editing (FSE) et Block Themes : Gutenberg API avancée · Core Web Vitals et performance : LCP, CLS, INP — mesure et optimisation · Sécurité WordPress avancée : durcissement, WAF, masquage de la version, fail2ban · WordPress sur VPS (serveur dédié) : configuration Nginx, PHP-FPM, Redis · Exercice : mise en place d\'un WordPress hardened + optimisé Core Web Vitals','duree'=>$s[0]],
                ['titre'=>'SEO technique avancé et stratégie de contenu','contenus'=>'Architecture du site SEO : silos thématiques, maillage interne stratégique · Données structurées Schema.org : implémentation JSON-LD pour produits, FAQ, articles · International SEO : hreflang, ciblage géographique UEMOA · Log analysis SEO : identifier les pages crawlées et non indexées · Stratégie de contenu pillar-cluster : planification 12 mois · E-E-A-T : construire la crédibilité de son site pour Google · Exercice : audit SEO technique complet et plan d\'action priorisé','duree'=>$s[1]],
                ['titre'=>'Link building et netlinking en Afrique francophone','contenus'=>'Fonctionnement des backlinks : autorité de domaine, trust, diversity · Prospection de liens pertinents : journalistes, blogueurs, annuaires · Guest blogging dans l\'écosystème africain francophone · Récupération des liens cassés (broken link building) · Relations presse digitales et communiqués optimisés pour le SEO · Surveillance du profil de liens : Ahrefs/SEMrush, disavow des liens toxiques · Exercice : conception et exécution d\'une campagne de netlinking en Afrique','duree'=>$s[2]],
                ['titre'=>'E-commerce WooCommerce avancé et conversion','contenus'=>'WooCommerce haute performance : lazy loading, cache spécifique e-commerce · Optimisation de la fiche produit : photographie, descriptions IA, rich snippets · Tunnel de conversion optimisé : checkout en 1 page, abandon de panier · Personnalisation avancée : produits variables, configurateurs, bundles · Subscription et facturation récurrente sous WooCommerce · Marketing automation e-commerce : relances email, upsell, cross-sell · Exercice : audit CRO d\'une boutique WooCommerce et plan d\'optimisation','duree'=>$s[3]],
                ['titre'=>'Analytics avancé, acquisition et croissance','contenus'=>'Google Analytics 4 avancé : exploration de données, entonnoirs personnalisés · Tracking avancé : Google Tag Manager, événements personnalisés, server-side tracking · Google Search Console avancé : analyse des requêtes impressions/clics, optimisation CTR · SEO et publicité combinés : SERP dominance strategy · Social media SEO en Afrique : Facebook, WhatsApp Business, TikTok et référencement · Attribution multicanal et ROI des actions SEO · Exercice : construction d\'un tableau de bord de croissance organique complet','duree'=>$s[4]],
                ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : audit SEO technique complet + plan de netlinking + optimisation WooCommerce + stratégie de contenu 6 mois sur site inédit · Correction individualisée par un expert SEO · Remise du certificat IBIG EDUFORM "WordPress & SEO" Niveau Expert et feuille de route SEO 12 mois','duree'=>$s[5]],
            ],
            default => [
                ['titre'=>'Création et configuration d\'un site WordPress professionnel','contenus'=>'Hébergement web en Côte d\'Ivoire / Afrique : critères de choix et coûts · Nom de domaine (.ci, .com, .africa) : enregistrement et DNS · Installation de WordPress via cPanel ou FTP · Paramètres essentiels : langue, timezone, URLs permanentes · Choix du thème : thèmes gratuits vs premium, critères de sélection · Installation et activation d\'Elementor ou Gutenberg · Exercice : création et configuration d\'un site WordPress opérationnel','duree'=>$s[0]],
                ['titre'=>'Construction des pages et gestion du contenu','contenus'=>'Création de pages : Accueil, À propos, Services, Contact, Blog · Construction de mises en page avec Elementor : colonnes, sections, widgets · Articles de blog : catégories, tags, médias · Médiathèque : optimisation des images (WebP, compression) · Menu de navigation : structure et hiérarchie · Formulaire de contact (Contact Form 7 / WPForms) · Page tarifs et galerie photo professionnelle · Exercice : construction complète du site du bénéficiaire','duree'=>$s[1]],
                ['titre'=>'SEO on-page et technique','contenus'=>'Installation et configuration de Yoast SEO ou Rank Math · Recherche de mots-clés : Google Keyword Planner, Ubersuggest · Structure sémantique : balises H1-H6, méta-titre, méta-description · Maillage interne : liens, ancres et hiérarchie des pages · Optimisation des images : alt text, taille et formats · Vitesse de chargement : cache (WP Rocket), lazy load, hébergement · SEO local : Google Business Profile pour les entreprises ivoiriennes · Exercice : audit SEO on-page du site du bénéficiaire et plan d\'optimisation','duree'=>$s[2]],
                ['titre'=>'E-commerce avec WooCommerce et Mobile Money','contenus'=>'Installation et configuration de WooCommerce · Création des fiches produits : description, images, prix, stock · Configuration des modes de livraison et zones · Intégration des paiements : Mobile Money (Orange, MTN, Wave), PayDunya, Flutterwave · Gestion des commandes et processus de livraison · Coupons et promotions · Règles fiscales TVA e-commerce en CI · Exercice : création d\'une boutique e-commerce opérationnelle','duree'=>$s[3]],
                ['titre'=>'Google Analytics, Search Console et suivi des performances','contenus'=>'Configuration de Google Analytics 4 sur WordPress · Google Search Console : vérification, sitemap, erreurs d\'indexation · Suivi des conversions et événements · Lectures des rapports : acquisition, comportement, performance · Heatmaps et analyse de comportement (Hotjar) · Tableau de bord de reporting mensuel · Stratégie de contenu et calendrier éditorial SEO · Exercice : configuration complète des outils de suivi et premier rapport','duree'=>$s[4]],
                ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : audit complet d\'un site WordPress (SEO + performance + sécurité) avec plan d\'action · Correction individualisée et feedback · Remise du certificat IBIG EDUFORM "WordPress & SEO" et plan de développement','duree'=>$s[5]],
            ],
        };
    }

    /* ══ Mobile Money / Intégration paiements mobiles ══ */
    if (preg_match('/mobile\s+money|paiements?\s+mobiles?|orange\s+money\s+api|mtn\s+momo|wave\s+(business|api)|moneroo/ui', $nom)) {
        $s = $split(max(14, $heures), 6);
        return match($niveau) {
            'debutant' => [
                ['titre'=>'C\'est quoi le Mobile Money ? Comprendre l\'écosystème ivoirien','contenus'=>'Orange Money, MTN MoMo, Wave, Moov Money : les différences expliquées simplement · Comment fonctionne une transaction Mobile Money : de l\'envoi à la réception · Les acteurs de l\'écosystème : opérateurs, agents, marchands, agrégateurs · Réglementation BCEAO : pourquoi le Mobile Money est encadré · Volumes et usages réels en Côte d\'Ivoire · Les cas d\'usage courants : achats, paiement de salaires, remboursements · Exercice : cartographier les solutions disponibles pour son activité','duree'=>$s[0]],
                ['titre'=>'Devenir marchand Mobile Money : démarches et configuration','contenus'=>'C\'est quoi un compte marchand Orange Money / MTN / Wave ? · Démarches d\'inscription au compte business et documents requis · Tableau de bord du marchand : navigation et fonctionnalités de base · Recevoir un paiement : générer un QR code ou un lien de paiement · Effectuer un remboursement ou un transfert depuis son compte marchand · Frais de transaction : comparatif et impact sur la marge · Exercice : ouverture et configuration d\'un compte marchand sur une plateforme sandbox','duree'=>$s[1]],
                ['titre'=>'Intégrer un bouton de paiement sur son site sans coder','contenus'=>'C\'est quoi une intégration de paiement ? L\'expliquer à un non-technique · Les solutions plug-and-play : CinetPay, PayDunya, Flutterwave — présentation · Intégrer CinetPay sur WordPress / WooCommerce en 30 minutes · Générer un lien de paiement PayDunya pour envoyer par WhatsApp · Tester sa configuration en mode sandbox avant le lancement · Que se passe-t-il si un paiement échoue ? · Exercice : mise en place d\'un paiement Mobile Money sur un site fictif','duree'=>$s[2]],
                ['titre'=>'Suivre et réconcilier ses paiements Mobile Money','contenus'=>'Tableau de bord des transactions : comment les lire et les interpréter · Rapprochement quotidien simplifié : transactions reçues vs commandes · Exporter ses transactions en CSV pour Excel · Construire un tableau de suivi de trésorerie Mobile Money · Gérer les litiges : transaction non créditée, client qui réclame · Comment comptabiliser les paiements Mobile Money · Exercice : construction d\'un tableau de réconciliation mensuelle sous Excel','duree'=>$s[3]],
                ['titre'=>'Sécurité et bonnes pratiques du marchand Mobile Money','contenus'=>'Fraudes courantes ciblant les marchands Mobile Money en CI · Faux SMS de confirmation : comment les identifier infailliblement · Procédure de vérification avant de libérer une marchandise · Gestion sécurisée des accès au compte marchand · Que faire en cas de fraude : signalement opérateur et ARTCI · Obligations légales du marchand : fiscalité des revenus Mobile Money · Exercice : identification de 5 tentatives de fraude sur captures d\'écran réelles','duree'=>$s[4]],
                ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : configuration d\'un compte marchand + intégration d\'un bouton de paiement + réconciliation d\'un mois de transactions sur cas fictif · Correction individualisée et feedback formateur · Remise du certificat IBIG EDUFORM "Mobile Money & Paiements Mobiles" Niveau Débutant et plan de déploiement','duree'=>$s[5]],
            ],
            'expert' => [
                ['titre'=>'Architecture des systèmes de paiement et stratégie multi-opérateurs','contenus'=>'Architecture des systèmes de paiement Mobile Money : layers applicatifs, switch, core banking · Design d\'une stratégie de paiement multi-opérateurs : critères de routing, failover · Interopérabilité UEMOA : état technique des API communes et limites actuelles · Agrégateurs vs intégrations directes : analyse coût-bénéfice pour différents volumes · Régulations EME BCEAO avancées : obligations des marchands, responsabilités · Mobile Money dans les segments spécifiques : assurance, financement, salaires en masse · Exercice : design de l\'architecture de paiement d\'un fintech ivoirien de 100k transactions/mois','duree'=>$s[0]],
                ['titre'=>'Intégration API avancée : sécurité, scalabilité et observabilité','contenus'=>'Sécurité des API paiement : signature HMAC-SHA256, idempotency keys, replay attack prevention · Rate limiting côté client et côté serveur : patterns de gestion · Gestion des webhooks avancée : retry avec backoff exponentiel, dead letter queue · Architecture event-driven pour les paiements : Kafka/Redis Streams · Monitoring et observabilité des flux de paiement : APM, alertes, dashboards · Gestion des environnements : sandbox, staging, production, secrets management · Exercice : implémentation d\'une intégration API robuste avec tests de résilience','duree'=>$s[1]],
                ['titre'=>'Réconciliation automatisée et comptabilité des paiements électroniques','contenus'=>'Pipeline de réconciliation automatisée : ingestion, transformation, matching · Gestion des cas complexes : paiements multiples, remboursements partiels, disputes · Intégration comptable en temps réel : ERP (Odoo/Sage) via API · Reporting règlementaire : obligations des plateformes vis-à-vis de la DGI · Cash management et optimisation de la trésorerie Mobile Money · Float management : optimisation des soldes chez les opérateurs · Exercice : construction d\'un pipeline de réconciliation automatisé sous Python','duree'=>$s[2]],
                ['titre'=>'Prévention de la fraude et conformité PCI-DSS / BCEAO','contenus'=>'Typologies de fraude Mobile Money en Afrique : SIM swapping, social engineering, triangulation · Machine learning pour la détection de fraude : features engineering, modèles en production · Règles de scoring transactionnel : KYC enrichi, behavioral analytics · PCI-DSS pour les plateformes de paiement : exigences et certification · Conformité BCEAO pour les agrégateurs et intermédiaires de paiement · Programme AML/KYC avancé pour les plateformes de paiement · Exercice : conception d\'un système de scoring fraude pour une fintech africaine','duree'=>$s[3]],
                ['titre'=>'Expansion et scaling d\'une activité de paiements en Afrique','contenus'=>'Expansion multi-pays : Orange Money Sénégal, MTN Ghana, M-Pesa Kenya — différences techniques · Gestion multi-devises dans l\'espace UEMOA et hors zone CFA · Infrastructure de paiement scalable : microservices, cloud africain (AWS Africa, Google Cloud) · Partenariats stratégiques avec les opérateurs et banques : API partnerships, revenue sharing · Open Banking en Afrique : opportunités pour les fintechs · Levée de fonds fintech payments : métriques et due diligence investisseur · Exercice : plan d\'expansion d\'une solution de paiement ivoirienne vers 3 pays CEDEAO','duree'=>$s[4]],
                ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : design complet d\'une architecture de paiement multi-opérateurs + implémentation API sécurisée + pipeline de réconciliation + plan anti-fraude sur cas inédit · Correction individualisée par un expert en paiements africains · Remise du certificat IBIG EDUFORM "Mobile Money & Paiements Mobiles" Niveau Expert et plan de développement fintech','duree'=>$s[5]],
            ],
            default => [
                ['titre'=>'Écosystème Mobile Money en Afrique de l\'Ouest : acteurs et enjeux','contenus'=>'Panorama du Mobile Money en UEMOA : Orange Money, MTN MoMo, Wave, Moov Money · Volumes de transactions et pénétration du marché en CI · Cadre réglementaire BCEAO des établissements de monnaie électronique · Modèles économiques : float, commissions, agrégateurs · Cas d\'usage : marchands, b2b, transferts, salaires, assurances · Interopérabilité UEMOA : état d\'avancement et API communes · Exercice : cartographie des solutions de paiement disponibles pour une PME ivoirienne','duree'=>$s[0]],
                ['titre'=>'Fondamentaux des APIs REST et authentification','contenus'=>'Architecture REST : ressources, méthodes HTTP, codes de statut · Authentification : OAuth 2.0, clés API, Bearer Token · Utilisation de Postman pour tester les APIs · Format JSON : structure, parsing et validation · Gestion des erreurs et retry logic · Webhooks vs polling : conception event-driven · Sandbox vs production : bonnes pratiques de transition · Exercice : appels REST simples avec Postman sur une sandbox','duree'=>$s[1]],
                ['titre'=>'Intégration Orange Money API et MTN MoMo API','contenus'=>'Documentation officielle Orange Money Business CI : création du compte, credentials · Flow de paiement marchand : initiation, confirmation, statut · Gestion des callbacks (webhooks) en temps réel · MTN MoMo API (Collection, Disbursement, Remittance) : inscription et authentification · Sandbox MTN : création des utilisateurs et test des flux · Gestion des timeouts et cas d\'erreur · Exercice : intégration d\'un paiement Orange Money sur un formulaire web PHP/JS','duree'=>$s[2]],
                ['titre'=>'Intégration Wave Business et agrégateurs de paiement','contenus'=>'Wave Business API : documentation, endpoints et schéma de données · Paiement marchand Wave : initiation et confirmation · Moneroo (agrégateur CI) : avantages et workflow multi-opérateurs · Flutterwave et CinetPay : intégration simplifiée multi-pays · Comparaison des frais et des temps de traitement · Choix de la solution selon la taille et le secteur · Exercice : intégration de CinetPay ou Moneroo pour accepter plusieurs opérateurs','duree'=>$s[3]],
                ['titre'=>'Réconciliation financière, sécurité et conformité réglementaire','contenus'=>'Réconciliation des transactions : tableaux de bord et rapprochements · Gestion des échecs, doublons et chargebacks · Sécurité des APIs : HTTPS, signatures HMAC, IP whitelisting · Stockage des données de paiement : conformité PCI-DSS (notions) · Obligations BCEAO pour les marchands Mobile Money · Facturation électronique et intégration au logiciel comptable · Exercice : construction d\'un tableau de réconciliation automatisée sous Excel','duree'=>$s[4]],
                ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : intégration complète d\'un flux de paiement Mobile Money (initiation → webhook → réconciliation) sur un scénario marchand inédit · Correction individualisée et feedback · Remise du certificat IBIG EDUFORM "Mobile Money & Paiements Mobiles" et plan de déploiement','duree'=>$s[5]],
            ],
        };
    }

    /* ══ IA générative / ChatGPT / Claude / Outils IA ══ */
    if (preg_match('/chatgpt|gpt[-\s]?[34o]|ia\s+g[eé]n[eé]rative|intelligence\s+artificielle.*pratique|prompt\s+engineering|copilot|gemini|mistral|outils?\s+ia/ui', $nom)
        && !preg_match('/\bclaude\b/ui', $nom)) {
        $s = $split(max(14, $heures), 6);
        return match($niveau) {
            'debutant' => [
                ['titre'=>'C\'est quoi l\'intelligence artificielle ? Démystifier l\'IA','contenus'=>'L\'IA n\'est pas de la magie : explication simple de ce qu\'elle fait vraiment · ChatGPT, Claude, Gemini : c\'est quoi et comment y accéder · L\'IA comme assistant : ce qu\'elle peut faire, ce qu\'elle ne peut pas · Hallucinations et erreurs : pourquoi l\'IA se trompe parfois · Les risques : données personnelles, biais, fausses informations · L\'IA dans le contexte africain : usages réels en Côte d\'Ivoire · Exercice : première session avec ChatGPT ou Claude — poser les bonnes questions','duree'=>$s[0]],
                ['titre'=>'Dialoguer avec l\'IA : écrire de bons prompts','contenus'=>'C\'est quoi un prompt ? Pourquoi certaines questions donnent de mauvaises réponses · La recette d\'un bon prompt : contexte + tâche + format · Donner un rôle à l\'IA : "Tu es un expert en..." · Demander un format précis : liste, tableau, email professionnel · Reformuler quand la réponse n\'est pas bonne · Les erreurs de débutant les plus courantes · Exercice : améliorer 5 prompts mal formulés pour obtenir de vraies réponses utiles','duree'=>$s[1]],
                ['titre'=>'Travailler plus vite avec l\'IA au bureau','contenus'=>'Rédiger un email professionnel en 2 minutes avec l\'IA · Résumer un long document (rapport, contrat) en quelques secondes · Corriger et améliorer ses textes avec l\'IA · Créer un plan de réunion ou un ordre du jour · Trouver des idées et surmonter le syndrome de la page blanche · Traduire et adapter un texte pour son audience africaine · Exercice : réaliser 5 tâches bureautiques réelles avec l\'IA en 30 minutes','duree'=>$s[2]],
                ['titre'=>'Créer des images, des présentations et des vidéos avec l\'IA','contenus'=>'Générateurs d\'images IA : DALL-E, Canva IA, Adobe Firefly — créer des visuels professionnels · Créer une présentation PowerPoint complète avec Gamma ou Beautiful.ai · Sous-titres automatiques et traduction vidéo avec Whisper / CapCut IA · Cloner sa voix pour des enregistrements audio professionnels · Photo professionnelle sans photographe : outils de retouche IA · Créer son logo et ses supports visuels avec l\'IA · Exercice : créer une présentation professionnelle complète avec des visuels IA','duree'=>$s[3]],
                ['titre'=>'Automatiser ses tâches répétitives avec l\'IA','contenus'=>'C\'est quoi l\'automatisation ? Exemples concrets pour une PME ivoirienne · Zapier : connecter ses applications sans coder · WhatsApp Business et chatbot simple : répondre aux clients 24h/24 · Publier automatiquement sur les réseaux sociaux · Trier et classer ses emails automatiquement · Alertes et rapports automatiques · Exercice : mettre en place une automatisation simple qui fait gagner 1h par jour','duree'=>$s[4]],
                ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : réaliser 5 tâches professionnelles réelles (email + résumé + image + présentation + automatisation simple) en utilisant l\'IA · Correction individualisée et feedback formateur · Remise du certificat IBIG EDUFORM "IA Générative & Outils IA" Niveau Débutant et liste de 20 outils IA à utiliser dès demain','duree'=>$s[5]],
            ],
            'expert' => [
                ['titre'=>'Architecture avancée des LLMs et ingénierie des prompts à grande échelle','contenus'=>'Fonctionnement interne des LLMs : transformers, attention, fine-tuning, RLHF · Fenêtre de contexte, chunking et gestion de la mémoire dans les agents · Prompt engineering avancé : ReAct, Tree-of-Thought, meta-prompting, constitutional AI · Évaluation des LLMs : benchmarks, LLM-as-a-judge, evals automatisés · Fine-tuning vs RAG vs prompting : choisir la bonne approche selon le cas · Sécurité des prompts : injection de prompt, jailbreaking, défenses · Exercice : conception d\'un système d\'évaluation automatisé de la qualité des prompts','duree'=>$s[0]],
                ['titre'=>'Développement d\'agents IA et systèmes multi-agents','contenus'=>'Architecture des agents IA : perception, mémoire, outils, planification, action · Frameworks agents : LangChain, LlamaIndex, AutoGen, CrewAI · Outils et function calling : intégration d\'APIs externes dans un agent · Mémoire persistante : bases vectorielles (Pinecone, Chroma, Weaviate) · Systèmes multi-agents : orchestration, communication, consensus · Agents IA pour l\'Afrique : cas d\'usage spécifiques (paiements, langues locales) · Exercice : développement d\'un agent métier complet avec tools et mémoire','duree'=>$s[1]],
                ['titre'=>'RAG (Retrieval-Augmented Generation) et bases de connaissances IA','contenus'=>'Architecture RAG complète : indexation, chunking, embedding, retrieval, génération · Bases de données vectorielles : Pinecone, Chroma, Qdrant, pgvector · Stratégies d\'embedding : Ada-002, BGE-M3, modèles multilingues francophones · Advanced RAG : HyDE, re-ranking, contextual compression, RAPTOR · Évaluation RAG : RAGAS, métriques de fidélité et de pertinence · RAG multi-modal : texte + images + tableaux · Exercice : construction d\'un RAG sur la documentation technique d\'une entreprise africaine','duree'=>$s[2]],
                ['titre'=>'Intégration IA dans les systèmes d\'entreprise','contenus'=>'API Anthropic / OpenAI avancée : batching, streaming, function calling, caching · Déploiement de LLMs open-source : Llama 3.3, Mixtral sur VPS africain · Sécurité enterprise : données confidentielles, compliance ARTCI, chiffrement · MLOps pour LLMs : versionning de prompts, A/B testing, monitoring de la dérive · LLM en production : coûts, latence, optimisation des tokens, observabilité · Intégration avec les ERP africains (Odoo, Sage) et SIRH via API · Exercice : déploiement d\'un LLM open-source sur infrastructure cloud africaine','duree'=>$s[3]],
                ['titre'=>'Stratégie de transformation IA et gouvernance','contenus'=>'IA Act européen et régulation IA africaine émergente : impacts sur les entreprises CI · Gouvernance IA d\'entreprise : politique, comité IA, red teaming · ROI de l\'IA : méthodologies de mesure et tableaux de bord de valeur créée · Gestion du changement IA : adoption, résistances, formation des équipes · Éthique IA pratique : biais, équité, explicabilité dans le contexte africain · IA et emploi en Afrique : prospective et stratégie de reskilling · Exercice : conception d\'un programme de transformation IA pour une organisation de 200 personnes','duree'=>$s[4]],
                ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : développement d\'un système IA complet (agent + RAG + API) + plan de gouvernance IA + ROI modélisé sur un cas métier africain inédit · Correction individualisée par un expert en IA générative · Remise du certificat IBIG EDUFORM "IA Générative & Outils IA" Niveau Expert et feuille de route de transformation IA','duree'=>$s[5]],
            ],
            default => [
                ['titre'=>'Panorama de l\'IA générative et grands modèles de langage','contenus'=>'Qu\'est-ce qu\'un LLM (Large Language Model) : fonctionnement simplifié, tokens, context window · Principaux modèles : ChatGPT (GPT-4o), Claude 3.5/4, Gemini 1.5, Mistral · Différences clés : raisonnement, fiabilité, coût, fenêtre de contexte, multimodalité · Utilisation via interface web vs API · Limites des LLMs : hallucinations, biais, date de coupure · Enjeux éthiques : droits d\'auteur, données personnelles, biais · Positionnement stratégique de l\'IA dans le contexte africain','duree'=>$s[0]],
                ['titre'=>'Prompt engineering : techniques fondamentales et avancées','contenus'=>'Structure d\'un prompt efficace : rôle, contexte, tâche, format, contraintes · Zero-shot, one-shot et few-shot prompting · Chain-of-thought (CoT) et prompting pas à pas · Prompts négatifs et contraintes de format · Itération et amélioration progressive des prompts · Techniques avancées : XML structuring, personas, meta-prompting · Bibliothèque de prompts réutilisables pour les cas d\'usage du bénéficiaire · Exercice : conception et optimisation de 10 prompts métier','duree'=>$s[1]],
                ['titre'=>'Applications métier : rédaction, analyse et productivité','contenus'=>'Rédaction professionnelle : emails, rapports, propositions commerciales, TDR · Résumé et extraction d\'information de documents longs · Analyse de données avec interpréteur de code (Advanced Data Analysis) · Génération de code et de formules Excel/SQL · Traduction et adaptation culturelle pour le marché africain · Recherche et veille : stratégies pour fiabiliser les résultats · Création de contenu marketing : posts, newsletters, scripts · Exercice : automatisation de 5 tâches récurrentes du bénéficiaire','duree'=>$s[2]],
                ['titre'=>'Outils IA spécialisés : image, voix, vidéo et automatisation','contenus'=>'Génération d\'images : DALL-E 3, Midjourney, Stable Diffusion · Génération vidéo : Sora, Runway, Pika · Clonage vocal et podcasts IA : ElevenLabs, NotebookLM · Transcription et résumé de réunions : Otter.ai, Fireflies · Automatisation no-code : Zapier AI, Make (Integromat), n8n · Outils de présentation IA : Gamma, Beautiful.ai · Exercice : construction d\'un workflow d\'automatisation avec Make + ChatGPT','duree'=>$s[3]],
                ['titre'=>'Intégration IA dans les processus organisationnels','contenus'=>'Audit de l\'organisation : identification des tâches automatisables par l\'IA · ROI de l\'IA : gain de temps, réduction des erreurs, valeur créée · Cas d\'usage par domaine : RH, finance, commercial, marketing, juridique · Résistances au changement et conduite de l\'adoption IA · Politique d\'utilisation de l\'IA en entreprise : règles, formation, gouvernance · Souveraineté des données : que ne pas confier à une IA externe · Veille technologique : comment suivre l\'évolution des outils IA · Plan de transformation par l\'IA de l\'organisation du bénéficiaire','duree'=>$s[4]],
                ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : conception d\'un workflow IA complet appliqué au domaine du bénéficiaire (prompts + outils + automatisation + plan d\'adoption) · Correction individualisée et feedback · Remise du certificat IBIG EDUFORM "IA Générative & Outils IA" et plan d\'intégration IA personnalisé','duree'=>$s[5]],
            ],
        };
    }

    /* ══ Data Analyst RH ══ */
    if (preg_match('/data analyst\s*(rh|grh|ressources humaines|hr\b)?/ui', $nom)) {
        $h      = max(20, $heures);
        $eval_h = 2;                                        // évaluation finale = 2h fixes
        $dh     = max(3, (int)floor(($h - $eval_h) / 6)); // 6 modules de formation
        $extra  = ($h - $eval_h) - ($dh * 6);             // heures restantes → 1er module
        return match($niveau) {
            'debutant' => [
                ['titre'=>'C\'est quoi la Data RH ? Comprendre sans jargon','contenus'=>'Le rôle de Data Analyst RH expliqué simplement : à quoi ça sert ? · Les chiffres RH que toute entreprise suit : effectifs, absences, rotations · Qui utilise ces données : DRH, managers, direction · Pourquoi les entreprises ivoiriennes passent à la data RH maintenant · Tour de table : vos données RH aujourd\'hui, comment les gérez-vous ? · Exercice de démarrage : lire et commenter un tableau RH simple','duree'=>$dh + max(0,$extra)],
                ['titre'=>'Où sont vos données RH ? Inventaire et premiers repères','contenus'=>'Les sources de données RH : fiches de paie, registre du personnel, pointages, CNPS · Formats courants : fichiers Excel, CSV, papier numérisé · Ce qu\'on peut faire avec ces données et ce qu\'on ne peut pas · Exercice : cartographier les données RH de votre entreprise sur une fiche · Les droits et la confidentialité : qui a accès à quoi · Introduction à Excel comme premier outil de travail des données','duree'=>$dh],
                ['titre'=>'Nettoyer et organiser ses données RH sous Excel','contenus'=>'Ouvrir et structurer un fichier de données RH dans Excel · Identifier les erreurs courantes : doublons, cases vides, formats différents · Trier, filtrer et corriger simplement · Créer un tableau structuré avec les bonnes colonnes · Exercice guidé : nettoyer un fichier de 50 salariés fictifs · Sauvegarder correctement et nommer ses fichiers de travail','duree'=>$dh],
                ['titre'=>'Calculer vos premiers indicateurs RH','contenus'=>'C\'est quoi un indicateur RH ? Exemples concrets · Calculer le taux d\'absentéisme sous Excel, pas à pas · Calculer le taux de turnover : formule et interprétation · La pyramide des âges : la construire facilement · Comprendre la masse salariale : total, moyenne, comparaison · Exercice : calculer 5 indicateurs sur un fichier RH fictif','duree'=>$dh],
                ['titre'=>'Mon premier tableau de bord RH simple','contenus'=>'C\'est quoi un tableau de bord : la définition en pratique · Choisir les 5 indicateurs essentiels pour sa direction · Créer un graphique sous Excel pour chaque indicateur · Mettre en page : couleurs, titres, lisibilité · Exercice fil rouge : construire votre premier tableau de bord RH · Présenter oralement son tableau de bord à sa direction','duree'=>$dh],
                ['titre'=>'Cas pratique complet : analyser une situation RH réelle','contenus'=>'Mise en situation : vous êtes Data Analyst RH pour une PME ivoirienne · Recevoir un fichier de données brutes et le comprendre · Nettoyer, calculer, visualiser de A à Z · Rédiger un mini-rapport d\'analyse de 2 pages · Présenter vos conclusions devant le groupe · Retours du formateur et conseils personnalisés','duree'=>$dh],
                ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve guidée : analyser un jeu de données RH inédit et produire 3 indicateurs clés · Correction individualisée et feedback positif du formateur · Remise du certificat IBIG EDUFORM "Data Analyst RH Niveau Débutant"','duree'=>$eval_h],
            ],
            'expert' => [
                ['titre'=>'People Analytics stratégique et modèles prédictifs RH','contenus'=>'People Analytics : état de l\'art mondial et benchmark Afrique subsaharienne · Modèles prédictifs de turnover : régression logistique, arbres de décision · Prédiction de l\'absentéisme et des risques psychosociaux · Analyse des réseaux organisationnels (ONA) · Machine learning appliqué aux données RH avec Python ou R · Validation et biais des modèles : équité algorithmique et RGPD/ARTCI','duree'=>$dh + max(0,$extra)],
                ['titre'=>'Architecture SIRH, intégrations et data engineering RH','contenus'=>'Architectures SIRH modernes : cloud, on-premise, hybride · API REST des principaux SIRH (SAP SuccessFactors, Oracle HCM, Workday) · Data pipelines RH : ETL/ELT, Apache Airflow, dbt · Qualité des données en continu : Great Expectations, alertes automatiques · Gouvernance des données RH : data catalog, data lineage · Intégration avec les ERP africains : Sage, Odoo, solutions locales','duree'=>$dh],
                ['titre'=>'Analyse avancée : rémunération, équité et simulation de masse salariale','contenus'=>'Études de rémunération et positionnement marché : benchmarks sectoriels OHADA · Analyse des écarts de rémunération par genre, ancienneté, grade · Modèles de simulation de masse salariale sous scénarios de croissance · Optimisation de la politique d\'intéressement et de variable · Analyse de la productivité par capita et retour sur investissement RH · Conformité sociale : obligations DGI, CNPS, DUE et audits sociaux','duree'=>$dh],
                ['titre'=>'Data storytelling pour CODIR et tableaux de bord exécutifs','contenus'=>'Principes du data storytelling appliqués aux RH : relier données et décision · Conception de tableaux de bord exécutifs sous Power BI et Tableau · KPIs RH stratégiques pour le CODIR : scorecard, OKRs et pilotage · Narration visuelle : choisir le bon graphique pour le bon message · Présenter des données sensibles (masse salariale, turnover cadres) · Exercice de haut niveau : présenter un bilan social à un CA fictif','duree'=>$dh],
                ['titre'=>'IA générative et automatisation des analyses RH','contenus'=>'ChatGPT et Copilot pour automatiser la production de rapports RH · Fine-tuning de LLM sur des données RH internes (architecture RAG) · Agents IA pour la veille sociale et l\'analyse des verbatims d\'enquêtes · Automatisation des alertes RH (Power Automate, n8n) · Éthique et gouvernance de l\'IA RH : biais, transparence, droit à l\'explication · Mise en place d\'une roadmap IA-RH sur 18 mois pour PME/GE africaine','duree'=>$dh],
                ['titre'=>'Mise en place d\'une fonction Data RH pérenne','contenus'=>'Construire le business case pour une fonction Data RH · Recrutement et profils de la dream team Data RH · Choix technologique : build vs buy, open source vs SaaS · Déploiement progressif : MVP en 90 jours puis scale · Conformité données personnelles : ARTCI, RGPD si activité EU · Certification spécialisée : HRCI Analytics, IBM Data Science (roadmap)','duree'=>$dh],
                ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve de haut niveau : modélisation prédictive et présentation d\'une stratégie Data RH complète sur cas inédit · Correction par un expert People Analytics certifié · Remise du certificat IBIG EDUFORM "Data Analyst RH Expert" et plan de certification internationale','duree'=>$eval_h],
            ],
            default => [
                ['titre'=>'Fondamentaux de la Data RH : périmètre, enjeux et indicateurs clés','contenus'=>'Ce que recouvre le rôle de Data Analyst RH · Indicateurs stratégiques : turnover, absentéisme, masse salariale, pyramide des âges, index égalité F/H · Positionnement dans l\'organigramme RH et interfaces avec la DRH et la direction','duree'=>$dh + max(0,$extra)],
                ['titre'=>'Sources de données RH et architecture SIRH','contenus'=>'Cartographie des sources : paie, pointage, recrutement, formation, CNPS/CMU, DUE · Formats de données (CSV, Excel, SQL) · Évaluation de la qualité et de la cohérence des données · Connexion aux principaux SIRH utilisés en Afrique francophone','duree'=>$dh],
                ['titre'=>'Collecte, nettoyage et structuration des données RH','contenus'=>'Import et consolidation de données multi-sources · Nettoyage sous Excel et Power Query : doublons, valeurs manquantes, formats incohérents · Modélisation d\'un référentiel salarié · Automatisation des mises à jour mensuelles','duree'=>$dh],
                ['titre'=>'Analyse des indicateurs RH clés et détection de signaux','contenus'=>'Calcul et interprétation du taux de turnover, de l\'absentéisme, du coût de recrutement · Analyse de la masse salariale et des écarts de rémunération · Lecture des données CNPS, CMU et obligations sociales OHADA · Identification des signaux d\'alerte RH','duree'=>$dh],
                ['titre'=>'Visualisation et tableaux de bord RH sous Excel et Power BI','contenus'=>'Graphiques avancés sous Excel : sparklines, jauges, histogrammes dynamiques · Création de tableaux de bord RH interactifs sous Power BI · Mise en page et narration des données pour le CODIR · Export et diffusion sécurisée des rapports','duree'=>$dh],
                ['titre'=>'Mise en pratique : projet complet d\'analyse RH sur données réelles','contenus'=>'Traitement d\'un jeu de données RH réelles d\'une entreprise de l\'espace OHADA · Construction d\'un rapport d\'analyse complet (diagnostic + recommandations) · Corrections et plan d\'amélioration individuel fourni par le formateur','duree'=>$dh],
                ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : analyse d\'un cas RH inédit et production d\'un tableau de bord en temps limité · Correction individualisée et feedback détaillé du formateur · Remise du certificat IBIG EDUFORM "Data Analyst RH" et construction du plan de développement post-formation','duree'=>$eval_h],
            ],
        };
    }

    /* ══ Modules spécifiques : Excel / Power BI / Tableaux de bord ══ */
    if (preg_match('/excel|power\s*bi|tableau.{0,10}bord|reporting|data.*visualis/ui', $nom)) {
        $h      = max(16, $heures);
        $eval_h = 2;
        $dh     = max(2, (int)floor(($h - $eval_h) / 5));
        $extra  = ($h - $eval_h) - ($dh * 5);
        return match($niveau) {
            'debutant' => [
                ['titre'=>'Découvrir Excel : votre premier tableur pas à pas','contenus'=>'À quoi sert Excel et pourquoi l\'apprendre · Ouvrir Excel et comprendre l\'écran : cellules, colonnes, lignes · Saisir du texte et des chiffres · Enregistrer son fichier et ne pas perdre son travail · Exercice guidé : créer un tableau de dépenses personnelles · Raccourcis de base : copier, coller, annuler','duree'=>$dh + max(0,$extra)],
                ['titre'=>'Mes premières formules : calculer sans effort','contenus'=>'C\'est quoi une formule ? La logique expliquée simplement · Additionner, soustraire, multiplier, diviser dans une cellule · La formule SOMME : additionner toute une colonne · La formule MOYENNE : calculer une moyenne en un clic · Mettre en forme son tableau : couleurs, bordures, gras · Exercice : construire un budget mensuel avec des formules','duree'=>$dh],
                ['titre'=>'Trier, filtrer et organiser ses données','contenus'=>'Trier une liste : par ordre alphabétique ou par montant · Filtrer : afficher seulement ce qui m\'intéresse · Figer les volets : garder les titres visibles · Rechercher et remplacer un mot ou un chiffre · Mettre son tableau au format Tableau Excel (le bleu) · Exercice : gérer une liste de clients ou de produits','duree'=>$dh],
                ['titre'=>'Créer son premier graphique','contenus'=>'Choisir le bon graphique : barres, courbes, camembert · Créer un graphique à partir d\'un tableau, pas à pas · Ajouter un titre et des étiquettes · Changer les couleurs pour un rendu professionnel · Copier son graphique dans Word ou PowerPoint · Exercice : illustrer les ventes du mois avec un graphique','duree'=>$dh],
                ['titre'=>'Mon premier tableau de bord simple','contenus'=>'C\'est quoi un tableau de bord : exemples concrets · Rassembler 3 graphiques sur une même page · Ajouter des indicateurs (chiffres clés) en grand · Imprimer ou envoyer son tableau de bord par email · Exercice fil rouge : tableau de bord de gestion d\'une petite activité · Bilan : ce que je sais faire maintenant et la suite','duree'=>$dh],
                ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve guidée : créer un tableau avec formules et un graphique sur un cas simple · Correction bienveillante et conseils personnalisés du formateur · Remise du certificat IBIG EDUFORM "Excel Niveau Débutant"','duree'=>$eval_h],
            ],
            'expert' => [
                ['titre'=>'Excel haute performance : modélisation financière et audit de formules','contenus'=>'Modélisation financière avancée : états prévisionnels, DCF, LBO simplifié · Formules de tableau dynamique : FILTER, SORT, UNIQUE, XLOOKUP · Audit de formules complexes : repérage de dépendances, évaluation pas à pas · Power Query avancé : M language, paramètres dynamiques, API REST · Gestion de grands volumes (>1M lignes) : Power Pivot et modèle de données · Performance et optimisation : calcul manuel, nommage de plages, mémoire','duree'=>$dh + max(0,$extra)],
                ['titre'=>'DAX avancé et modélisation multidimensionnelle sous Power Pivot','contenus'=>'Modèle en étoile et en flocon : design optimal dans Power Pivot · DAX : mesures et colonnes calculées, contexte de filtre vs contexte de ligne · Fonctions Time Intelligence : TOTALYTD, DATEADD, SAMEPERIODLASTYEAR · Variables DAX et mesures complexes pour KPIs financiers · Relations bidirectionnelles et filtres croisés : pièges et bonnes pratiques · Débogage DAX : DAX Studio et Performance Analyzer','duree'=>$dh],
                ['titre'=>'Automatisation avancée avec VBA et Office Scripts','contenus'=>'VBA professionnel : classes, événements, gestion d\'erreurs robuste · UserForms dynamiques : interfaces métier sans développeur · Connexion VBA aux bases de données (ADODB), API REST et fichiers XML/JSON · Office Scripts (JavaScript) pour automatisation cloud et Power Automate · Déploiement et maintenance de macros en entreprise : XLAM, signatures numériques · Tests unitaires et documentation de code VBA','duree'=>$dh],
                ['titre'=>'Power BI Pro : DAX avancé, sécurité et architecture enterprise','contenus'=>'Power BI Service : workspaces, apps, pipelines de déploiement (dev/test/prod) · Row-Level Security dynamique et Object-Level Security · Dataflows Gen2 et réutilisation des transformations · Connexion en temps réel : streaming datasets et Azure Event Hubs · Embedded Analytics : intégrer Power BI dans une application web · Gouvernance BI : lineage, impact analysis, sensitivity labels','duree'=>$dh],
                ['titre'=>'Data storytelling et tableaux de bord stratégiques pour dirigeants','contenus'=>'Principes de Gestalt et neurologie de la perception visuelle appliqués à la BI · Design de tableau de bord exécutif : 5 métriques, 30 secondes de lecture · Storytelling avec les données : arc narratif, tension/résolution · Présenter une analyse de données à un CODIR ou un investisseur · Benchmarks internationaux de reporting : templates McKinsey, BCG · Atelier : relifter un tableau de bord existant en version "board-ready"','duree'=>$dh],
                ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve de niveau expert : modèle financier complet avec DAX, automatisation VBA et tableau de bord exécutif Power BI sur cas inédit · Correction par un formateur Microsoft Certified · Remise du certificat IBIG EDUFORM "Excel / Power BI Expert" et recommandation de certification Microsoft PL-300','duree'=>$eval_h],
            ],
            default => [
                ['titre'=>'Prise en main et environnement de travail','contenus'=>'Interface, raccourcis et ergonomie · Organisation des classeurs et feuilles · Mise en forme professionnelle · Paramètres d\'affichage et d\'impression','duree'=>$dh + max(0,$extra)],
                ['titre'=>'Fonctions avancées et traitement des données','contenus'=>'Fonctions RECHERCHEV, INDEX/EQUIV, SI imbriqués, SOMME.SI.ENS · Formules matricielles et fonctions texte · Nettoyage et transformation de données · Power Query : import et consolidation multi-sources','duree'=>$dh],
                ['titre'=>'Tableaux croisés dynamiques et analyse multidimensionnelle','contenus'=>'Création et configuration des TCD · Segments et chronologies · Champs calculés · Analyse des données RH, financières et commerciales','duree'=>$dh],
                ['titre'=>'Graphiques, visualisation et storytelling par les données','contenus'=>'Graphiques avancés : cascade, radar, bulles · Mise en forme conditionnelle avancée · Sparklines et mini-graphiques · Principes de data storytelling pour décideurs','duree'=>$dh],
                ['titre'=>'Tableaux de bord dynamiques et automatisation','contenus'=>'Architecture d\'un tableau de bord professionnel · Contrôles de formulaire et listes déroulantes · Liaisons entre feuilles et classeurs · Introduction aux macros VBA · Diffusion et protection du tableau de bord','duree'=>$dh],
                ['titre'=>'Évaluation finale et remise du certificat','contenus'=>'Épreuve pratique : conception d\'un tableau de bord complet sur un jeu de données inédit · Correction individualisée et feedback détaillé du formateur · Remise du certificat IBIG EDUFORM et plan d\'action individuel','duree'=>$eval_h],
            ],
        };
    }

    /* Extraction des sujets EN PREMIER — sert aussi à calibrer le nombre de modules */
    $topics = _extract_topics_gen($desc);
    if (count($topics) < 2) {
        $n = preg_replace('/\s*\([^)]*\)/', '', $nom);
        $parts = preg_split('/\s*[&,]\s*|\s+[eé]t\s+/ui', $n);
        $topics = array_values(array_filter(array_map('trim', $parts), fn($t) => mb_strlen(trim($t), 'UTF-8') > 4));
    }
    $topic_count = count($topics);

    /* Nombre de modules : durée + richesse du contenu + niveau */
    $nb_modules = 5;
    if ($heures >= 20) $nb_modules = 5;
    if ($heures >= 25) $nb_modules = 6;
    if ($heures >= 30) $nb_modules = 7;
    if ($heures >= 35) $nb_modules = 8;
    if ($heures >= 45) $nb_modules = 9;
    if ($heures >= 65) $nb_modules = 10;

    // Richesse du contenu : beaucoup de sujets → +1 module
    $topic_bonus = ($topic_count >= 5) ? 1 : 0;

    // Ajustement par niveau
    if ($niveau === 'debutant') {
        $nb_modules = max(5, $nb_modules - 1 + $topic_bonus);
        $nb_modules = min(8, $nb_modules);
    } elseif ($niveau === 'expert') {
        $nb_modules = min(11, $nb_modules + 1 + $topic_bonus);
    } else {
        $nb_modules = min(10, $nb_modules + $topic_bonus);
    }
    $nb_modules = max(5, $nb_modules); // minimum absolu : 5 modules


    /* Contenus rotatifs selon le niveau */
    if ($niveau === 'debutant') {
        $contenus_sets = [
            'Définition claire du concept, à quoi ça sert et pourquoi c\'est important · Vocabulaire essentiel expliqué simplement, sans jargon · Exemples concrets du quotidien professionnel africain pour ancrer la notion',
            'Les étapes de base à connaître absolument · Démonstration guidée pas à pas par le formateur · Exercice d\'application simple avec correction immédiate et bienveillante',
            'Les erreurs fréquentes des débutants et comment les éviter · Modèles et trames fournis pour gagner du temps · Exercice de mise en pratique sur situation professionnelle simple',
            'Ce qu\'on utilise concrètement dans les entreprises ivoiriennes · Découverte des outils de base : prise en main assistée · Exercice : reproduire un document ou une procédure standard',
            'Récapitulatif des notions clés vues jusqu\'ici · Questions-réponses et clarification des points flous · Mini-exercice de synthèse : vérifier ses acquis avant d\'aller plus loin',
        ];
    } elseif ($niveau === 'expert') {
        $contenus_sets = [
            'Benchmarks internationaux et meilleures pratiques mondiales dans ce domaine · Analyse critique des modèles avancés et de leur applicabilité en contexte africain · Positionnement stratégique et valeur ajoutée pour l\'organisation',
            'Techniques avancées et cas complexes multi-facteurs · Automatisation, optimisation et gains de performance mesurables · Arbitrages stratégiques : quand appliquer quelle approche et pourquoi',
            'Outils experts, solutions enterprise et intégrations systèmes · Retours d\'expérience de transformations organisationnelles réelles · Indicateurs de maturité et grilles d\'évaluation de niveau',
            'Pilotage à l\'échelle de l\'organisation : gouvernance, conformité, reporting CODIR · Management et transfert de compétences : former son équipe sur ce domaine · Construction du référentiel interne et documentation des bonnes pratiques',
            'Gestion des crises, des exceptions et des situations à fort enjeu · Intelligence prospective : anticiper les évolutions réglementaires et technologiques · Recommandations pour une certification internationale reconnue dans ce domaine',
        ];
    } else {
        $contenus_sets = [
            'Documents et outils associés · Procédures et circuits de validation · Cas pratiques sur dossiers simulés et corrections commentées',
            'Réglementation en vigueur, obligations et délais à respecter · Erreurs fréquentes constatées en entreprise · Exercices pratiques avec mise en situation professionnelle',
            'Outils, logiciels et tableaux de suivi utilisés dans les entreprises · Organisation, archivage et traçabilité des dossiers · Retours d\'expérience d\'entreprises ivoiriennes et de l\'espace OHADA',
            'Principes clés, définitions et ce que le praticien doit maîtriser en priorité · Mise en pratique guidée sur cas réels issus du terrain africain · Points de contrôle, indicateurs de qualité et critères de conformité',
            'Procédures internes types et comment les adapter à son entreprise · Communication inter-services, reporting et présentation des résultats · Simulation complète avec correction et plan d\'amélioration individuel',
        ];
    }

    /* Répartition horaire — évaluation finale = 2h fixes, reste réparti sur les autres modules */
    $heures_contenu = max(($nb_modules - 1) * 2, $heures - 2);
    $duree_base = max(2, (int)floor($heures_contenu / ($nb_modules - 1)));
    $extra = $heures_contenu - ($duree_base * ($nb_modules - 1));
    $give_extra = function() use (&$extra): int {
        if ($extra > 0) { $extra--; return 1; }
        return 0;
    };

    $modules = [];

    /* MODULE 1 — introduction différenciée par niveau */
    if ($niveau === 'debutant') {
        $m1_titre    = 'C\'est quoi ' . $nom . ' ? Découverte et premiers repères';
        $m1_contenus = 'À quoi sert ce domaine et pourquoi l\'apprendre maintenant · Les 5 mots clés à retenir absolument pour comprendre la suite · Qui fait quoi : les acteurs, les métiers et les responsabilités · Ce qu\'on attend d\'un débutant dans une entreprise ivoirienne · Autoévaluation de départ : ce que je sais déjà, ce que je vais apprendre';
    } elseif ($niveau === 'expert') {
        $m1_titre    = $nom . ' : état de l\'art, tendances avancées et vision stratégique';
        $m1_contenus = 'Positionnement mondial et benchmarks internationaux dans ce domaine · Évolutions réglementaires et technologiques récentes impactant la pratique · Enjeux stratégiques pour les organisations de l\'espace OHADA en 2024-2026 · Autodiagnostic expert : identifier ses angles morts et axes de progression à ce niveau · Objectifs de transformation personnelle et organisationnelle à l\'issue du parcours';
    } else {
        $m1_titre    = $nom . ' : périmètre, enjeux et positionnement professionnel';
        $m1_contenus = 'Ce que recouvre exactement ce domaine et la responsabilité du praticien · Acteurs, textes de référence et pratiques du marché en Afrique francophone · Autodiagnostic et identification des axes prioritaires de progression';
    }
    $modules[] = [
        'titre'    => $m1_titre,
        'contenus' => $m1_contenus,
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
                'contenus' => $contenus_sets[$setIdx % count($contenus_sets)],
                'duree'    => $duree_base + $give_extra(),
            ];
            $fi++;
            $setIdx++;
            $idx++;
        }
    }

    /* Avant-dernier module — différencié par niveau */
    if ($niveau === 'debutant') {
        $pratique_titre    = 'Ma première mise en pratique : exercice complet guidé';
        $pratique_contenus = 'Cas pratique simple sur une situation professionnelle du quotidien · Travail pas à pas avec le support du formateur · Production d\'un premier livrable concret (document, calcul, procédure) · Correction bienveillante et identification des points à renforcer · Conseils personnalisés pour continuer à progresser après la formation';
    } elseif ($niveau === 'expert') {
        $pratique_titre    = 'Projet de transformation : audit, plan d\'action et présentation au CODIR';
        $pratique_contenus = 'Audit complet de la situation actuelle de l\'organisation du bénéficiaire sur ce domaine · Identification des écarts par rapport aux meilleures pratiques et des opportunités de transformation · Élaboration d\'un plan de transformation sur 12-24 mois avec KPIs et jalons · Simulation de présentation devant un comité de direction fictif · Feedback expert et recommandations pour le déploiement opérationnel';
    } else {
        $pratique_titre    = 'Mise en pratique : cas d\'entreprises et travaux dirigés';
        $pratique_contenus = 'Traitement de dossiers et scénarios tirés d\'entreprises réelles (Côte d\'Ivoire, Afrique de l\'Ouest) · Travaux individuels et en binôme avec correction commentée · Projet de synthèse : production d\'un livrable professionnel complet';
    }
    $modules[] = [
        'titre'    => $pratique_titre,
        'contenus' => $pratique_contenus,
        'duree'    => $duree_base + $give_extra(),
    ];

    /* Dernier module — évaluation : toujours 2h, libellé différencié */
    $cert_label = match($niveau) {
        'debutant'      => 'Remise du certificat IBIG EDUFORM Niveau Débutant et conseils pour la suite du parcours',
        'expert'        => 'Remise du certificat IBIG EDUFORM Niveau Expert et recommandations pour la certification internationale',
        default         => 'Remise du certificat IBIG EDUFORM et construction du plan de développement post-formation',
    };
    $modules[] = [
        'titre'    => 'Évaluation finale et remise du certificat',
        'contenus' => 'Épreuve d\'évaluation couvrant l\'ensemble du programme · Correction individualisée et feedback détaillé du formateur · ' . $cert_label,
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

/* ═══════════════════════════════════════════════════════════════════════════
 * tdr_objectifs_locaux() — Génère objectifs / prérequis / public_cible
 * sans aucun appel API externe. Différencié par niveau ET par domaine.
 * ═══════════════════════════════════════════════════════════════════════════ */
if (!function_exists('tdr_objectifs_locaux')) {

function tdr_objectifs_locaux(string $nom, string $domaine, int $duree, string $desc = '', string $niveau = 'debutant'): array
{
    $haystack = mb_strtolower($nom . ' ' . $domaine, 'UTF-8');

    $is_rh        = (bool)preg_match('/\b(rh|grh|ressources humaines|paie|recrutement|talent|rémunération|gpec|sirh|hr\b)/ui', $haystack);
    $is_compta    = (bool)preg_match('/\b(compta|comptabilité|bilan|fiscal|tva|ohada|sage compta|comptable)/ui', $haystack);
    $is_finance   = (bool)preg_match('/\b(finance|trésorerie|investissement|budget|analyse financière|contrôle de gestion|microfinance|banque|crédit)/ui', $haystack);
    $is_marketing = (bool)preg_match('/\b(marketing|publicité|comm(unication)?|brand|marque|réseaux sociaux|social media|digital|content|seo|ads)/ui', $haystack);
    $is_it        = (bool)preg_match('/\b(informatique|développement|python|java|php|sql|base de données|réseau|cyber|web|programmation|logiciel|erp|crm|data|power bi|excel)/ui', $haystack);
    $is_mgmt      = (bool)preg_match('/\b(management|leadership|manager|direction|gouvernance|stratégie|prise de décision|chef de projet|pmo)/ui', $haystack);
    $is_vente     = (bool)preg_match('/\b(vente|commercial|négociation|prospection|closing|business dev|entrepreneuriat|entrepreneur|startup)/ui', $haystack);
    $is_logistique= (bool)preg_match('/\b(logistique|supply chain|achats|approvisionnement|stock|inventaire|transport|douane|import.?export)/ui', $haystack);
    $is_audit     = (bool)preg_match('/\b(audit|contrôle interne|conformité|risk|risque|fraude|iso|qualité|normes)/ui', $haystack);
    $is_juridique = (bool)preg_match('/\b(juridique|droit|contrat|légal|ohada|réglementation|compliance|tribunal)/ui', $haystack);
    $is_sante     = (bool)preg_match('/\b(santé|médical|infirmier|soins|clinique|hôpital|pharmacie|patient|urgences|kiné|sage-femme)/ui', $haystack);
    $is_formation = (bool)preg_match('/\b(formation|pédagogie|enseignement|formateur|e.?learning|ingénierie pédagogique|andragogie)/ui', $haystack);
    $is_btp       = (bool)preg_match('/\b(btp|construction|architecture|génie civil|bâtiment|travaux|topographie|urbanisme)/ui', $haystack);
    $is_agri      = (bool)preg_match('/\b(agriculture|agro|élevage|pêche|culture|sol|fertilisation|maraîchage|agroforesterie)/ui', $haystack);
    $is_env       = (bool)preg_match('/\b(environnement|développement durable|rse|green|écologie|énergie renouvelable|changement climatique|biodiversité)/ui', $haystack);
    $is_bureau    = (bool)preg_match('/\b(bureautique|secrétariat|assistanat|office|word|tableur|traitement de texte)/ui', $haystack);
    $is_langues   = (bool)preg_match('/\b(anglais|français|langue|traduction|interprétation|communication écrite|rédaction)/ui', $haystack);

    /* ── RH ── */
    if ($is_rh) return match($niveau) {
        'debutant' => [
            'objectifs'    => "Découvrir les fondamentaux de la gestion des ressources humaines et son rôle stratégique dans l'entreprise. Comprendre les principaux processus RH (recrutement, paie, administration du personnel) et savoir les appliquer dans un contexte professionnel africain.",
            'prerequis'    => "Aucun prérequis spécifique. Convient à toute personne souhaitant s'initier aux métiers RH.",
            'public_cible' => "Tout professionnel débutant ou en reconversion souhaitant intégrer une fonction RH. Idéal pour les assistants administratifs, les étudiants en gestion et toute personne souhaitant évoluer vers les métiers des ressources humaines.",
        ],
        'expert' => [
            'objectifs'    => "Piloter la stratégie RH d'une organisation et aligner les politiques de gestion des talents sur les objectifs business. Maîtriser les outils avancés de People Analytics, de GPEC et de conduite du changement en contexte africain et international.",
            'prerequis'    => "Expérience confirmée en gestion des RH (minimum 3 à 5 ans). Maîtrise des fondamentaux RH indispensable. Idéalement titulaire d'une formation supérieure en GRH ou management.",
            'public_cible' => "Directeurs et responsables RH expérimentés, DRH en poste ou en prise de fonction. Consultants RH souhaitant développer leur expertise stratégique et se positionner comme senior dans leur domaine.",
        ],
        default => [
            'objectifs'    => "Maîtriser les processus clés de la gestion des ressources humaines (recrutement, administration du personnel, paie, développement des compétences) et les appliquer de manière autonome. Développer une posture professionnelle RH adaptée aux réalités des entreprises africaines.",
            'prerequis'    => "Notions de base en gestion ou administration. Une première expérience en entreprise est souhaitable, même hors spécialité RH.",
            'public_cible' => "Professionnels exerçant ou souhaitant exercer dans une fonction RH, des services du personnel ou de l'administration. Responsables administratifs et managers souhaitant structurer leur pratique RH.",
        ],
    };

    /* ── Comptabilité ── */
    if ($is_compta) return match($niveau) {
        'debutant' => [
            'objectifs'    => "Comprendre les principes fondamentaux de la comptabilité et maîtriser les écritures de base (saisie, journaux, plan des comptes). Être capable de produire des documents comptables simples et de naviguer dans un logiciel de comptabilité courant.",
            'prerequis'    => "Aucun prérequis comptable. Maîtrise basique d'Excel recommandée.",
            'public_cible' => "Toute personne souhaitant s'initier à la comptabilité : assistants comptables débutants, créateurs d'entreprise, responsables administratifs sans formation comptable.",
        ],
        'expert' => [
            'objectifs'    => "Maîtriser les mécanismes avancés de la comptabilité OHADA, du droit fiscal et de la production des états financiers consolidés. Assurer la supervision comptable complète d'une entité et conseiller sur l'optimisation fiscale dans le respect des normes en vigueur.",
            'prerequis'    => "Solide expérience en comptabilité (minimum 3 ans). Maîtrise des normes OHADA et du droit fiscal. Idéalement Expert-comptable stagiaire ou titulaire d'un diplôme comptable supérieur.",
            'public_cible' => "Experts-comptables, chefs comptables expérimentés, responsables financiers souhaitant renforcer leur expertise technique et réglementaire.",
        ],
        default => [
            'objectifs'    => "Maîtriser les techniques comptables intermédiaires : enregistrement des opérations complexes, déclarations fiscales courantes, clôture des comptes et production des états financiers. Utiliser avec autonomie les principaux logiciels comptables du marché africain.",
            'prerequis'    => "Bases de la comptabilité (niveau débutant ou BAC orientation comptable). Pratique courante d'Excel.",
            'public_cible' => "Comptables en poste souhaitant progresser, gestionnaires voulant maîtriser la comptabilité de leur structure, collaborateurs des services financiers cherchant à gagner en autonomie.",
        ],
    };

    /* ── Finance ── */
    if ($is_finance) return match($niveau) {
        'debutant' => [
            'objectifs'    => "Comprendre les fondamentaux de la gestion financière : lecture des états financiers, notions de trésorerie, de budget et d'analyse de rentabilité. Savoir utiliser les outils financiers de base pour gérer une activité professionnelle.",
            'prerequis'    => "Aucun prérequis financier. Maîtrise basique d'Excel recommandée.",
            'public_cible' => "Entrepreneurs, managers non financiers, chefs de projet et toute personne souhaitant comprendre les enjeux financiers pour mieux piloter son activité.",
        ],
        'expert' => [
            'objectifs'    => "Concevoir et piloter des stratégies financières complexes : modélisation avancée, gestion de portefeuille, levée de fonds et structuration d'opérations. Assurer un pilotage financier stratégique en lien avec la gouvernance de l'organisation.",
            'prerequis'    => "Expérience confirmée en finance d'entreprise ou contrôle de gestion (minimum 3-5 ans). Maîtrise des outils d'analyse financière et des normes comptables.",
            'public_cible' => "Directeurs financiers, contrôleurs de gestion senior, analystes financiers expérimentés et consultants finance souhaitant atteindre un niveau stratégique.",
        ],
        default => [
            'objectifs'    => "Analyser les états financiers d'une entreprise, construire un budget prévisionnel et piloter la trésorerie avec autonomie. Maîtriser les outils d'analyse financière et de contrôle de gestion pour contribuer aux décisions stratégiques.",
            'prerequis'    => "Notions de comptabilité ou de gestion. Pratique d'Excel. Une première expérience en environnement financier est appréciée.",
            'public_cible' => "Contrôleurs de gestion, responsables financiers en prise de fonction, comptables souhaitant évoluer vers l'analyse financière, managers souhaitant piloter les finances de leur entité.",
        ],
    };

    /* ── Marketing / Digital ── */
    if ($is_marketing) return match($niveau) {
        'debutant' => [
            'objectifs'    => "Comprendre les bases du marketing et de la communication professionnelle. Savoir construire un message, choisir les bons canaux et créer des contenus adaptés à une audience cible sur les réseaux sociaux et le digital.",
            'prerequis'    => "Aucun prérequis. Accès à internet et à un smartphone ou ordinateur recommandé.",
            'public_cible' => "Entrepreneurs, commerçants, chargés de communication débutants, étudiants et toute personne souhaitant promouvoir une activité ou développer des compétences en marketing digital.",
        ],
        'expert' => [
            'objectifs'    => "Concevoir et piloter des stratégies marketing et communication multicanales à fort impact. Maîtriser les leviers avancés (SEA, automation marketing, data marketing, brand strategy) et mesurer précisément le ROI de chaque action.",
            'prerequis'    => "Expérience confirmée en marketing ou communication (minimum 3 ans). Maîtrise des outils digitaux et des plateformes publicitaires. Sens aigu de l'analyse de données.",
            'public_cible' => "Directeurs marketing, responsables communication expérimentés, consultants marketing digital souhaitant piloter des stratégies avancées et des programmes de mesure de performance.",
        ],
        default => [
            'objectifs'    => "Concevoir et mettre en œuvre un plan marketing et de communication adapté à son organisation. Maîtriser les outils digitaux incontournables (réseaux sociaux, email marketing, SEO) et produire des campagnes efficaces avec un budget maîtrisé.",
            'prerequis'    => "Notions de base en communication ou marketing. Utilisation courante des réseaux sociaux appréciée.",
            'public_cible' => "Chargés de marketing ou communication en poste, community managers, responsables de PME souhaitant structurer leur communication, entrepreneurs voulant développer leur présence digitale.",
        ],
    };

    /* ── Informatique / IT / Data ── */
    if ($is_it) return match($niveau) {
        'debutant' => [
            'objectifs'    => "Acquérir les bases pratiques de l'informatique et des outils numériques essentiels au monde professionnel. Comprendre les fondamentaux du domaine et être capable d'utiliser les outils courants de manière productive et autonome.",
            'prerequis'    => "Aucun prérequis technique. Savoir utiliser un ordinateur est suffisant.",
            'public_cible' => "Toute personne souhaitant développer ses compétences numériques : collaborateurs peu à l'aise avec les outils informatiques, nouveaux entrants sur le marché du travail, professionnels en reconversion vers le numérique.",
        ],
        'expert' => [
            'objectifs'    => "Maîtriser les techniques avancées du domaine et concevoir des solutions numériques complexes, sécurisées et scalables. Adopter une posture d'architecte ou de lead technique pour accompagner la transformation numérique des organisations.",
            'prerequis'    => "Expérience solide en informatique (minimum 3-5 ans). Maîtrise confirmée des fondamentaux techniques. Capacité à travailler en environnement complexe et à superviser des équipes techniques.",
            'public_cible' => "Développeurs senior, architectes techniques, chefs de projet IT, DSI et professionnels expérimentés du numérique visant une expertise de haut niveau.",
        ],
        default => [
            'objectifs'    => "Maîtriser les outils et techniques intermédiaires pour travailler en autonomie sur des projets professionnels réels. Développer une expertise opérationnelle et être capable de résoudre les problèmes courants avec efficacité et méthode.",
            'prerequis'    => "Maîtrise des bases du domaine (niveau débutant ou formation équivalente). Utilisation régulière d'un ordinateur.",
            'public_cible' => "Professionnels du numérique souhaitant approfondir leurs compétences, techniciens IT en progression, développeurs juniors visant l'autonomie professionnelle.",
        ],
    };

    /* ── Management / Leadership ── */
    if ($is_mgmt) return match($niveau) {
        'debutant' => [
            'objectifs'    => "Comprendre les fondamentaux du management et du leadership pour animer une équipe avec efficacité. Acquérir les outils de base de la gestion de projet, de la communication managériale et de la prise de décision.",
            'prerequis'    => "Aucun prérequis managérial. Convient à toute personne prenant ou souhaitant prendre des responsabilités d'encadrement.",
            'public_cible' => "Collaborateurs en première prise de poste managérial, futurs managers, chefs d'équipe souhaitant structurer leur pratique de leadership.",
        ],
        'expert' => [
            'objectifs'    => "Développer un leadership stratégique et piloter la transformation de son organisation avec vision et impact. Maîtriser les outils avancés de gouvernance, de conduite du changement et d'alignement stratégique pour influencer à haut niveau.",
            'prerequis'    => "Expérience managériale confirmée (minimum 5 ans d'encadrement). Maîtrise des fondamentaux du management et de la stratégie d'entreprise.",
            'public_cible' => "Directeurs et cadres dirigeants, managers expérimentés visant des responsabilités de direction générale, consultants en management souhaitant se positionner comme référence sectorielle.",
        ],
        default => [
            'objectifs'    => "Animer et développer son équipe avec efficacité, gérer les situations difficiles et piloter des projets dans un contexte professionnel exigeant. Adopter une posture de manager-coach et développer son leadership opérationnel.",
            'prerequis'    => "Une première expérience d'encadrement ou de coordination d'équipe est souhaitée. Bases en communication professionnelle.",
            'public_cible' => "Managers en poste souhaitant structurer leur pratique, responsables d'équipe en progression, chefs de projet aspirant à des responsabilités d'encadrement élargies.",
        ],
    };

    /* ── Vente / Commerce / Entrepreneuriat ── */
    if ($is_vente) return match($niveau) {
        'debutant' => [
            'objectifs'    => "Maîtriser les techniques de base de la vente et de la prospection commerciale. Comprendre le cycle de vente, savoir argumenter une offre et traiter les objections pour conclure des ventes avec confiance.",
            'prerequis'    => "Aucun prérequis. Convient à toute personne souhaitant développer ses compétences commerciales.",
            'public_cible' => "Débutants en commerce, entrepreneurs cherchant à vendre leur offre, collaborateurs souhaitant développer une dimension commerciale dans leur poste.",
        ],
        'expert' => [
            'objectifs'    => "Concevoir et piloter une stratégie commerciale avancée, gérer des comptes clés complexes et développer un portefeuille à fort potentiel. Maîtriser la négociation à haut niveau et les outils CRM pour maximiser la performance commerciale.",
            'prerequis'    => "Expérience commerciale confirmée (minimum 3-5 ans). Maîtrise du cycle de vente complet. Idéalement responsable commercial ou key account manager.",
            'public_cible' => "Directeurs commerciaux, key account managers, responsables des ventes expérimentés et entrepreneurs voulant structurer une force de vente haute performance.",
        ],
        default => [
            'objectifs'    => "Développer ses compétences commerciales pour prospecter efficacement, convaincre et fidéliser sa clientèle. Maîtriser les techniques de négociation, les outils CRM et les indicateurs de performance commerciale.",
            'prerequis'    => "Bases en communication commerciale. Une première expérience en contact client ou en vente est appréciée.",
            'public_cible' => "Commerciaux en poste souhaitant progresser, chargés de clientèle, account managers, entrepreneurs souhaitant structurer leur démarche commerciale.",
        ],
    };

    /* ── Logistique / Supply Chain ── */
    if ($is_logistique) return match($niveau) {
        'debutant' => [
            'objectifs'    => "Comprendre les fondamentaux de la chaîne logistique : approvisionnement, gestion des stocks, transport et distribution. Acquérir le vocabulaire et les outils de base pour travailler efficacement dans un environnement logistique.",
            'prerequis'    => "Aucun prérequis. Convient aux débutants dans les métiers de la logistique et des achats.",
            'public_cible' => "Débutants en logistique ou supply chain, magasiniers en progression, assistants achats et toute personne souhaitant s'orienter vers les métiers de la chaîne d'approvisionnement.",
        ],
        'expert' => [
            'objectifs'    => "Concevoir et optimiser des supply chains complexes en intégrant les contraintes du contexte africain (infrastructure, douanes, risques). Piloter la performance globale de la chaîne avec des indicateurs avancés et des outils de planification stratégique.",
            'prerequis'    => "Expérience confirmée en logistique ou supply chain (minimum 5 ans). Maîtrise des outils logistiques et des procédures d'import-export. Capacité à piloter des équipes et des projets complexes.",
            'public_cible' => "Directeurs supply chain, responsables logistiques expérimentés, consultants en optimisation de la chaîne d'approvisionnement et directeurs achats.",
        ],
        default => [
            'objectifs'    => "Piloter avec autonomie les opérations logistiques et les achats : planification des approvisionnements, gestion des fournisseurs, optimisation des stocks et suivi de la performance. Maîtriser les indicateurs clés du supply chain management.",
            'prerequis'    => "Notions de base en logistique ou en gestion. Une première expérience en entrepôt, achats ou transport est appréciée.",
            'public_cible' => "Responsables logistiques en progression, acheteurs, gestionnaires de stock et planificateurs souhaitant développer leur maîtrise opérationnelle de la supply chain.",
        ],
    };

    /* ── Audit / Contrôle / Qualité ── */
    if ($is_audit) return match($niveau) {
        'debutant' => [
            'objectifs'    => "Découvrir les principes fondamentaux de l'audit et du contrôle interne. Comprendre les enjeux de la maîtrise des risques et savoir contribuer à une mission d'audit sous supervision d'un auditeur expérimenté.",
            'prerequis'    => "Bases en comptabilité ou en gestion. Sens de la rigueur et de l'analyse requis.",
            'public_cible' => "Étudiants en comptabilité ou finance, collaborateurs souhaitant s'orienter vers l'audit interne ou externe, assistants comptables désirant élargir leurs compétences.",
        ],
        'expert' => [
            'objectifs'    => "Concevoir et superviser des missions d'audit complexes, évaluer le dispositif de contrôle interne d'une organisation et formuler des recommandations stratégiques. Maîtriser les normes internationales d'audit et le management des risques d'entreprise.",
            'prerequis'    => "Expérience confirmée en audit interne ou externe (minimum 5 ans). Connaissance des normes IIA/IFAC. Idéalement certifié CIA, CISA ou CGAP.",
            'public_cible' => "Directeurs audit interne, associés de cabinets d'audit, risk managers expérimentés et responsables conformité visant une maîtrise stratégique de leur domaine.",
        ],
        default => [
            'objectifs'    => "Conduire des missions d'audit interne de A à Z : planification, collecte des preuves, rédaction du rapport et suivi des recommandations. Évaluer l'efficacité des contrôles internes et contribuer à l'amélioration de la gouvernance organisationnelle.",
            'prerequis'    => "Bases en comptabilité et en gestion. Première expérience en audit ou contrôle. Connaissance des référentiels COSO ou ISO est un atout.",
            'public_cible' => "Auditeurs internes et externes en poste, contrôleurs de gestion, responsables conformité et risk managers souhaitant structurer et professionnaliser leur pratique.",
        ],
    };

    /* ── Droit / Juridique ── */
    if ($is_juridique) return match($niveau) {
        'debutant' => [
            'objectifs'    => "Acquérir les bases du droit applicable en entreprise : contrats, droit du travail, réglementation OHADA. Comprendre les risques juridiques courants et développer les réflexes essentiels pour sécuriser son activité.",
            'prerequis'    => "Aucun prérequis juridique. Convient à tout professionnel souhaitant comprendre les fondamentaux légaux.",
            'public_cible' => "Dirigeants de TPE/PME, managers, assistants juridiques débutants et tout professionnel souhaitant mieux comprendre le cadre légal de son activité.",
        ],
        'expert' => [
            'objectifs'    => "Maîtriser les aspects juridiques complexes de la vie des affaires : structuration juridique, contentieux, conformité réglementaire avancée et conseil stratégique. Piloter le département juridique d'une organisation ou conduire des missions de conseil à haut niveau.",
            'prerequis'    => "Formation juridique supérieure (MASTER droit ou équivalent). Expérience pratique en droit des affaires ou en contentieux. Maîtrise des normes OHADA.",
            'public_cible' => "Juristes d'entreprise expérimentés, avocats, responsables conformité et directeurs juridiques cherchant à renforcer leur expertise ou à se spécialiser.",
        ],
        default => [
            'objectifs'    => "Maîtriser les mécanismes juridiques essentiels à la gestion d'une entreprise : rédaction et négociation de contrats, gestion des litiges courants, respect des obligations légales et fiscales. Adopter une posture de référent juridique dans son organisation.",
            'prerequis'    => "Notions de droit des affaires ou droit du travail. Une première expérience en entreprise est souhaitée.",
            'public_cible' => "Juristes en prise de poste, responsables RH et administratifs, managers souhaitant développer des réflexes juridiques solides pour sécuriser la gestion de leur entité.",
        ],
    };

    /* ── Santé / Médical ── */
    if ($is_sante) return match($niveau) {
        'debutant' => [
            'objectifs'    => "Acquérir les connaissances et gestes essentiels pour intervenir de manière sécurisée dans un contexte de soins. Comprendre les protocoles de base, les règles d'hygiène et les fondamentaux de la relation soignant-patient.",
            'prerequis'    => "Aucun prérequis médical spécifique. Motivation et sens des responsabilités sont les qualités essentielles.",
            'public_cible' => "Personnes souhaitant s'initier aux métiers du soin, aides-soignants débutants, personnels de santé communautaire et volontaires souhaitant apporter des soins de base.",
        ],
        'expert' => [
            'objectifs'    => "Maîtriser les protocoles avancés de soins, gérer des situations cliniques complexes et développer une expertise reconnue dans sa spécialité. Assurer un encadrement clinique de qualité et contribuer à l'amélioration des pratiques de soins.",
            'prerequis'    => "Diplôme professionnel dans le domaine médical ou paramédical. Expérience clinique significative (minimum 3-5 ans). Maîtrise des protocoles standards.",
            'public_cible' => "Professionnels de santé expérimentés (infirmiers, médecins, sages-femmes) souhaitant se spécialiser ou prendre des responsabilités d'encadrement clinique.",
        ],
        default => [
            'objectifs'    => "Approfondir les compétences cliniques et relationnelles pour assurer des soins de qualité en toute autonomie. Maîtriser les protocoles professionnels, gérer les situations d'urgence courantes et travailler efficacement en équipe pluridisciplinaire.",
            'prerequis'    => "Diplôme ou formation initiale dans le domaine de la santé. Une première expérience pratique en contexte de soins est indispensable.",
            'public_cible' => "Professionnels de santé en exercice souhaitant actualiser et renforcer leurs compétences cliniques, paramédicaux désirant évoluer vers plus d'autonomie et de responsabilité.",
        ],
    };

    /* ── Formation / Pédagogie ── */
    if ($is_formation) return match($niveau) {
        'debutant' => [
            'objectifs'    => "Comprendre les principes de l'ingénierie pédagogique et de l'animation de formation. Acquérir les bases pour concevoir une séquence d'apprentissage simple, animer un groupe et évaluer les acquis des participants.",
            'prerequis'    => "Aucun prérequis pédagogique. Intérêt pour la transmission des savoirs et la relation d'apprentissage.",
            'public_cible' => "Formateurs occasionnels, experts souhaitant transmettre leur savoir, enseignants en reconversion, responsables souhaitant développer des compétences en animation.",
        ],
        'expert' => [
            'objectifs'    => "Concevoir des dispositifs de formation complexes et innovants intégrant le digital, l'évaluation des compétences et le transfert en situation de travail. Piloter une ingénierie de formation complète et mesurer son impact sur la performance organisationnelle.",
            'prerequis'    => "Expérience confirmée en ingénierie pédagogique ou formation professionnelle (minimum 5 ans). Maîtrise des référentiels de compétences et des outils e-learning.",
            'public_cible' => "Responsables formation, ingénieurs pédagogiques expérimentés, directeurs RH supervisant la politique formation et consultants en développement des compétences.",
        ],
        default => [
            'objectifs'    => "Concevoir et animer des sessions de formation professionnelle efficaces, adaptées aux adultes en contexte professionnel africain. Maîtriser les techniques d'animation interactive, d'évaluation des apprentissages et de conception de supports pédagogiques.",
            'prerequis'    => "Expérience en animation de groupe ou en formation est appréciée. Bases en communication. Aisance à l'oral.",
            'public_cible' => "Formateurs en activité souhaitant professionnaliser leur pratique, responsables de formation, managers-formateurs et tout professionnel ayant une mission de transmission des savoirs.",
        ],
    };

    /* ── BTP / Construction ── */
    if ($is_btp) return match($niveau) {
        'debutant' => [
            'objectifs'    => "Découvrir les bases du secteur de la construction : matériaux, techniques courantes, lecture de plans simples et normes de sécurité sur chantier. Comprendre l'organisation d'un projet de construction et ses acteurs principaux.",
            'prerequis'    => "Aucun prérequis technique. Sensibilité à l'espace bâti et aptitude à la lecture de documents techniques recommandées.",
            'public_cible' => "Conducteurs de travaux débutants, ouvriers qualifiés souhaitant évoluer, étudiants en BTP et toute personne souhaitant s'initier aux métiers de la construction.",
        ],
        'expert' => [
            'objectifs'    => "Piloter des projets de construction complexes dans le respect strict des délais, des coûts et des normes de qualité et de sécurité. Maîtriser les techniques avancées d'ingénierie, de management de projet BTP et d'optimisation des processus constructifs.",
            'prerequis'    => "Expérience confirmée dans le secteur BTP (minimum 5 ans). Maîtrise de la lecture de plans et des logiciels de conduite de projet. Ingénieur ou technicien supérieur BTP.",
            'public_cible' => "Ingénieurs BTP expérimentés, chefs de chantier senior, directeurs travaux et responsables de projets d'infrastructure souhaitant atteindre l'excellence opérationnelle.",
        ],
        default => [
            'objectifs'    => "Gérer avec autonomie les aspects techniques et organisationnels d'un chantier : planification, approvisionnement, management des équipes, respect des normes de qualité et sécurité. Maîtriser les outils et logiciels de suivi de projets BTP.",
            'prerequis'    => "Formation technique en BTP ou expérience pratique équivalente. Maîtrise des bases de la lecture de plans. Connaissance des normes de sécurité chantier.",
            'public_cible' => "Conducteurs de travaux, chefs de chantier, techniciens BTP souhaitant développer leur maîtrise technique et managériale pour prendre davantage de responsabilités.",
        ],
    };

    /* ── Agriculture / Agroalimentaire ── */
    if ($is_agri) return match($niveau) {
        'debutant' => [
            'objectifs'    => "Comprendre les fondamentaux des techniques agricoles modernes adaptées au contexte africain. Acquérir les bases de la production végétale ou animale, de la gestion du sol et des bonnes pratiques agro-économiques.",
            'prerequis'    => "Aucun prérequis technique. Intérêt pour l'agriculture et le développement rural.",
            'public_cible' => "Agriculteurs souhaitant moderniser leurs pratiques, jeunes ruraux portant un projet agricole, agents de développement rural et tout acteur de l'agro-alimentaire souhaitant renforcer ses bases techniques.",
        ],
        'expert' => [
            'objectifs'    => "Concevoir et piloter des projets agro-industriels à fort impact économique et environnemental. Maîtriser les technologies agricoles avancées, les chaînes de valeur agro-alimentaires et les mécanismes de financement de l'agriculture africaine.",
            'prerequis'    => "Expérience confirmée en agriculture ou agro-industrie (minimum 5 ans). Maîtrise des techniques agricoles de base et des outils de gestion de projet.",
            'public_cible' => "Ingénieurs agronomes, responsables d'exploitations agro-industrielles, porteurs de projets agricoles à grande échelle et acteurs du développement rural souhaitant maximiser leur impact.",
        ],
        default => [
            'objectifs'    => "Optimiser les productions agricoles en appliquant des techniques modernes et durables adaptées aux sols et aux marchés africains. Maîtriser la gestion agro-économique et les outils de valorisation des produits agricoles.",
            'prerequis'    => "Pratique de base en agriculture ou expérience rurale. Sensibilité aux enjeux économiques et environnementaux.",
            'public_cible' => "Agriculteurs en activité souhaitant moderniser leur exploitation, techniciens agricoles, agents de vulgarisation et responsables de coopératives agricoles.",
        ],
    };

    /* ── Environnement / RSE / Développement durable ── */
    if ($is_env) return match($niveau) {
        'debutant' => [
            'objectifs'    => "Comprendre les enjeux du développement durable et de la RSE dans le contexte professionnel africain. Acquérir les bases pour adopter des pratiques responsables et contribuer aux objectifs environnementaux de son organisation.",
            'prerequis'    => "Aucun prérequis. Sensibilité aux questions environnementales et sociales.",
            'public_cible' => "Tout professionnel souhaitant intégrer une dimension environnementale dans son travail, étudiants en développement durable, porteurs de projets verts.",
        ],
        'expert' => [
            'objectifs'    => "Concevoir et piloter des stratégies RSE et développement durable ambitieuses et mesurables. Accompagner les organisations dans leur transformation écologique et maîtriser les référentiels internationaux (ISO 14001, GRI, ODD).",
            'prerequis'    => "Expérience confirmée en RSE, environnement ou développement durable (minimum 3-5 ans). Connaissance des référentiels internationaux.",
            'public_cible' => "Directeurs RSE, responsables environnement, consultants en développement durable et experts souhaitant piloter des transformations écologiques à fort impact.",
        ],
        default => [
            'objectifs'    => "Concevoir et mettre en œuvre une démarche RSE ou développement durable dans son organisation. Maîtriser les outils de diagnostic environnemental, les normes applicables et les indicateurs de performance durable.",
            'prerequis'    => "Notions en développement durable ou RSE. Expérience en gestion de projet est appréciée.",
            'public_cible' => "Responsables RSE en prise de poste, chargés de mission développement durable, managers souhaitant intégrer les enjeux environnementaux dans leurs décisions.",
        ],
    };

    /* ── Bureautique / Secrétariat ── */
    if ($is_bureau) return match($niveau) {
        'debutant' => [
            'objectifs'    => "Maîtriser les outils bureautiques de base (traitement de texte, tableur, présentation) pour travailler efficacement au quotidien. Acquérir les bonnes pratiques de productivité numérique en contexte professionnel.",
            'prerequis'    => "Aucun prérequis bureautique. Savoir utiliser un ordinateur est suffisant.",
            'public_cible' => "Toute personne souhaitant acquérir ou rafraîchir ses compétences bureautiques : secrétaires débutants, collaborateurs peu à l'aise avec les outils informatiques, nouveaux entrants sur le marché du travail.",
        ],
        'expert' => [
            'objectifs'    => "Maîtriser les fonctionnalités avancées des outils bureautiques pour automatiser les tâches, produire des analyses complexes et créer des livrables professionnels de haute qualité. Devenir la référence bureautique et numérique de son organisation.",
            'prerequis'    => "Bonne maîtrise des outils bureautiques courants (Word, Excel, PowerPoint). Expérience pratique régulière en contexte professionnel.",
            'public_cible' => "Secrétaires et assistants de direction expérimentés, gestionnaires souhaitant automatiser leurs tâches, experts voulant maîtriser les fonctionnalités avancées d'Excel ou de PowerPoint.",
        ],
        default => [
            'objectifs'    => "Utiliser les outils bureautiques avec aisance et efficacité dans un environnement professionnel : mise en page avancée, formules et tableaux croisés dynamiques Excel, présentations percutantes. Gagner en productivité et en qualité dans ses livrables quotidiens.",
            'prerequis'    => "Bases en bureautique (ouverture de fichiers, saisie de texte, navigation dans un tableur). Pratique régulière d'un ordinateur.",
            'public_cible' => "Secrétaires, assistants administratifs, comptables et tout professionnel utilisant quotidiennement des outils bureautiques et souhaitant en maîtriser les fonctionnalités avancées.",
        ],
    };

    /* ── Langues / Communication écrite ── */
    if ($is_langues) return match($niveau) {
        'debutant' => [
            'objectifs'    => "Acquérir les bases de la communication écrite et orale pour s'exprimer avec clarté dans un contexte professionnel. Comprendre les règles fondamentales et développer sa confiance pour communiquer efficacement.",
            'prerequis'    => "Aucun prérequis. Motivation et régularité dans la pratique sont les clés du succès.",
            'public_cible' => "Toute personne souhaitant améliorer sa communication professionnelle, débutants en langue étrangère, collaborateurs souhaitant prendre la parole avec plus d'assurance.",
        ],
        'expert' => [
            'objectifs'    => "Atteindre une maîtrise professionnelle avancée de la langue pour communiquer avec précision dans des contextes complexes : négociations, rédaction de documents stratégiques, présentations à haute valeur ajoutée.",
            'prerequis'    => "Niveau intermédiaire confirmé dans la langue. Pratique régulière en contexte professionnel.",
            'public_cible' => "Professionnels bilingues souhaitant atteindre un niveau d'excellence, cadres travaillant en contexte international, traducteurs et interprètes en perfectionnement.",
        ],
        default => [
            'objectifs'    => "Communiquer avec aisance à l'écrit et à l'oral dans un contexte professionnel : rédaction de courriers, emails et rapports, animation de réunions. Enrichir son vocabulaire professionnel et affiner sa maîtrise des codes de communication.",
            'prerequis'    => "Niveau scolaire ou bases acquises dans la langue. Pratique minimale de la communication écrite.",
            'public_cible' => "Professionnels souhaitant améliorer leurs compétences rédactionnelles, collaborateurs amenés à communiquer à l'international, secrétaires et assistants voulant professionnaliser leur expression.",
        ],
    };

    /* ── Fallback générique — couvre toutes les autres formations ── */
    $short = trim(preg_replace('/\s*\([^)]*\)/', '', $nom));
    if (mb_strlen($short, 'UTF-8') > 60) {
        $short = mb_substr($short, 0, 57, 'UTF-8') . '…';
    }

    return match($niveau) {
        'debutant' => [
            'objectifs'    => "Découvrir les fondamentaux de « {$short} » et acquérir les bases pratiques pour intervenir de manière efficace dans ce domaine. Comprendre le vocabulaire essentiel, les outils de base et les réflexes professionnels nécessaires à une première prise en main réussie.",
            'prerequis'    => "Aucun prérequis spécifique. Cette formation est accessible à toute personne motivée, sans expérience préalable dans le domaine.",
            'public_cible' => "Tout professionnel débutant ou en reconversion souhaitant s'initier à « {$short} ». Idéal pour les étudiants, les collaborateurs en début de carrière et toute personne découvrant ce domaine pour la première fois.",
        ],
        'expert' => [
            'objectifs'    => "Maîtriser les dimensions stratégiques et avancées de « {$short} » pour exercer un leadership reconnu dans ce domaine. Développer une expertise de haut niveau permettant de concevoir des solutions innovantes, de piloter des projets complexes et d'accompagner la transformation des organisations.",
            'prerequis'    => "Expérience professionnelle confirmée dans le domaine ou un domaine connexe (minimum 3 à 5 ans). Maîtrise solide des fondamentaux et des pratiques intermédiaires. Capacité à travailler en autonomie sur des problématiques complexes.",
            'public_cible' => "Professionnels expérimentés, cadres et managers souhaitant atteindre un niveau d'excellence stratégique en « {$short} ». Consultants et formateurs voulant se positionner comme référence dans leur spécialité.",
        ],
        default => [
            'objectifs'    => "Maîtriser les compétences opérationnelles clés de « {$short} » pour exercer en autonomie dans ce domaine. Développer une pratique professionnelle solide, ancrée dans les réalités du terrain africain, et être capable de résoudre des problèmes concrets avec efficacité.",
            'prerequis'    => "Notions de base dans ce domaine ou expérience professionnelle connexe appréciée. Une première exposition au sujet, même informelle, facilite la progression.",
            'public_cible' => "Professionnels en activité souhaitant développer ou consolider leurs compétences en « {$short} ». Idéal pour les praticiens souhaitant structurer leur expérience et adopter une approche plus méthodique et professionnelle.",
        ],
    };
}

} // end if !function_exists('tdr_objectifs_locaux')
