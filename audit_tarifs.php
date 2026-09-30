<?php
if (($_GET['k'] ?? '') !== 'ibig-audit-tarifs-2026') { http_response_code(403); exit('Forbidden'); }
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/core/config.php';
require_once __DIR__ . '/core/database.php';
$pdo = Database::connect();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

/* ── Formule officielle IBIG EDUFORM ── */
$PRIX_FORMULE = [
    20 => 225000, 25 => 280000, 28 => 315000, 30 => 340000,
    35 => 395000, 40 => 450000, 45 => 505000, 55 => 620000,
    65 => 730000, 72 => 810000, 80 => 900000,
];

function r5(float $v): int { return (int)(round($v / 5000) * 5000); }

function tarif_attendu(int $heures, array $PRIX_FORMULE): array {
    if ($heures <= 0) return [];
    // Valeur exacte si dans la table
    if (isset($PRIX_FORMULE[$heures])) {
        $tel = $PRIX_FORMULE[$heures];
    } else {
        // Interpolation : trouver les bornes encadrantes
        $keys = array_keys($PRIX_FORMULE);
        sort($keys);
        $tel = r5($heures * 11250);
        // Si entre deux valeurs connues, interpoler
        $inf = null; $sup = null;
        foreach ($keys as $k) {
            if ($k <= $heures) $inf = $k;
            if ($k >= $heures && $sup === null) $sup = $k;
        }
        if ($inf !== null && $sup !== null && $inf !== $sup) {
            $ratio = ($heures - $inf) / ($sup - $inf);
            $tel = r5($PRIX_FORMULE[$inf] + $ratio * ($PRIX_FORMULE[$sup] - $PRIX_FORMULE[$inf]));
        }
    }
    $tel = max($tel, 200000);
    $tp  = max(r5($tel * 10 / 7), 250000);
    $th  = r5(($tel + $tp) / 2);
    return ['tel' => $tel, 'tp' => $tp, 'th' => $th];
}

function parse_heures(string $duree): int {
    if (preg_match('/(\d+)\s*[hH]/', $duree, $m)) return (int)$m[1];
    return 0;
}

/* ── Lire toutes les formations locales (catalogue, pas sessions) ── */
$rows = $pdo->query("
    SELECT id, titre, slug, domaine, duree,
           COALESCE(tarif_en_ligne, 0)   AS tel,
           COALESCE(tarif_presentiel, 0) AS tp,
           COALESCE(tarif_hybride, 0)    AS th,
           statut, annee,
           COALESCE(is_samedi_pro, 0)    AS samedi_pro
    FROM formations
    WHERE statut = 'active'
      AND (annee IS NULL OR annee = 0)
    ORDER BY domaine, titre
")->fetchAll(PDO::FETCH_ASSOC);

$anomalies    = [];
$ok           = 0;
$sans_duree   = 0;
$samedi_pro   = 0;
$total        = count($rows);

foreach ($rows as $r) {
    if (!empty($r['samedi_pro'])) { $samedi_pro++; continue; }

    $h = parse_heures((string)($r['duree'] ?? ''));
    if ($h <= 0) { $sans_duree++; continue; }

    $att = tarif_attendu($h, $PRIX_FORMULE);
    if (empty($att)) continue;

    $tel_reel = (int)$r['tel'];
    $tp_reel  = (int)$r['tp'];
    $th_reel  = (int)$r['th'];

    $issues = [];

    // Tarif en ligne incorrect
    if ($tel_reel !== $att['tel']) {
        $issues[] = [
            'champ'    => 'tarif_en_ligne',
            'actuel'   => $tel_reel,
            'attendu'  => $att['tel'],
            'ecart'    => $tel_reel - $att['tel'],
        ];
    }
    // Tarif présentiel incorrect
    if ($tp_reel !== $att['tp']) {
        $issues[] = [
            'champ'    => 'tarif_presentiel',
            'actuel'   => $tp_reel,
            'attendu'  => $att['tp'],
            'ecart'    => $tp_reel - $att['tp'],
        ];
    }
    // Tarif hybride incorrect
    if ($th_reel !== $att['th']) {
        $issues[] = [
            'champ'    => 'tarif_hybride',
            'actuel'   => $th_reel,
            'attendu'  => $att['th'],
            'ecart'    => $th_reel - $att['th'],
        ];
    }
    // Planchers absolus
    if ($tel_reel < 200000) $issues[] = ['champ'=>'plancher_ligne','actuel'=>$tel_reel,'attendu'=>200000,'ecart'=>$tel_reel-200000];
    if ($tp_reel  < 250000) $issues[] = ['champ'=>'plancher_pres', 'actuel'=>$tp_reel, 'attendu'=>250000,'ecart'=>$tp_reel-250000];

    if (!empty($issues)) {
        $anomalies[] = [
            'id'      => $r['id'],
            'titre'   => $r['titre'],
            'domaine' => $r['domaine'],
            'duree'   => $r['duree'],
            'heures'  => $h,
            'tel_actuel' => $tel_reel,
            'tp_actuel'  => $tp_reel,
            'th_actuel'  => $th_reel,
            'tel_attendu'=> $att['tel'],
            'tp_attendu' => $att['tp'],
            'th_attendu' => $att['th'],
            'issues'     => $issues,
        ];
    } else {
        $ok++;
    }
}

/* ── Résumé par type d'écart ── */
$par_ecart = [];
foreach ($anomalies as $a) {
    foreach ($a['issues'] as $iss) {
        $par_ecart[$iss['champ']] = ($par_ecart[$iss['champ']] ?? 0) + 1;
    }
}

echo json_encode([
    'audit_date'   => date('Y-m-d H:i:s'),
    'total_local'  => $total,
    'samedi_pro_exclus' => $samedi_pro,
    'sans_duree'   => $sans_duree,
    'conformes'    => $ok,
    'anomalies_count' => count($anomalies),
    'par_ecart'    => $par_ecart,
    'anomalies'    => $anomalies,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
