<?php
if (($_GET['k'] ?? '') !== 'ibig-fix-tarifs-2026') { http_response_code(403); exit('Forbidden'); }
header('Content-Type: text/plain; charset=utf-8');

require_once __DIR__ . '/core/config.php';
require_once __DIR__ . '/core/database.php';
$pdo = Database::connect();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

/* ── Grille tarifaire officielle IBIG EDUFORM ── */
$PRIX_FORMULE = [
    20 => 225000, 25 => 280000, 28 => 315000, 30 => 340000,
    35 => 395000, 40 => 450000, 45 => 505000, 55 => 620000,
    65 => 730000, 72 => 810000, 80 => 900000,
];

function r5(float $v): int { return (int)(round($v / 5000) * 5000); }

function tarif_officiel(int $heures, array $PF): array {
    if ($heures <= 0) return [];
    if (isset($PF[$heures])) {
        $tel = $PF[$heures];
    } else {
        // Interpolation entre bornes connues
        $keys = array_keys($PF); sort($keys);
        $tel = r5($heures * 11250);
        $inf = null; $sup = null;
        foreach ($keys as $k) {
            if ($k <= $heures) $inf = $k;
            if ($k >= $heures && $sup === null) $sup = $k;
        }
        if ($inf !== null && $sup !== null && $inf !== $sup) {
            $ratio = ($heures - $inf) / ($sup - $inf);
            $tel = r5($PF[$inf] + $ratio * ($PF[$sup] - $PF[$inf]));
        }
    }
    $tel = max($tel, 200000);
    $tp  = max(r5($tel * 10 / 7), 250000);
    $th  = r5(($tel + $tp) / 2);
    return ['tel' => $tel, 'tp' => $tp, 'th' => $th];
}

function parse_h(string $d): int {
    if (preg_match('/(\d+)\s*[hH]/', $d, $m)) return (int)$m[1];
    return 0;
}

/* ── Lire formations locales catalogue uniquement ── */
$rows = $pdo->query("
    SELECT id, titre, duree,
           COALESCE(tarif_en_ligne,   0) AS tel,
           COALESCE(tarif_presentiel, 0) AS tp,
           COALESCE(tarif_hybride,    0) AS th,
           COALESCE(is_samedi_pro,    0) AS samedi_pro
    FROM formations
    WHERE statut = 'active'
      AND (annee IS NULL OR annee = 0)
    ORDER BY id
")->fetchAll(PDO::FETCH_ASSOC);

$upd = $pdo->prepare("
    UPDATE formations
    SET tarif_en_ligne   = :tel,
        tarif_presentiel = :tp,
        tarif_hybride    = :th,
        updated_at       = NOW()
    WHERE id = :id
      AND (annee IS NULL OR annee = 0)
      AND COALESCE(is_samedi_pro, 0) = 0
");

$corrige = 0; $skipped = 0; $inchange = 0;
$log = [];

foreach ($rows as $r) {
    // Jamais toucher Samedi Pro
    if (!empty($r['samedi_pro'])) { $skipped++; continue; }

    $h = parse_h((string)($r['duree'] ?? ''));
    if ($h <= 0) { $skipped++; continue; }

    $att = tarif_officiel($h, $PRIX_FORMULE);
    if (empty($att)) { $skipped++; continue; }

    $tel_reel = (int)$r['tel'];
    $tp_reel  = (int)$r['tp'];
    $th_reel  = (int)$r['th'];

    if ($tel_reel === $att['tel'] && $tp_reel === $att['tp'] && $th_reel === $att['th']) {
        $inchange++;
        continue;
    }

    $upd->execute([':tel' => $att['tel'], ':tp' => $att['tp'], ':th' => $att['th'], ':id' => $r['id']]);
    $corrige++;

    $log[] = sprintf(
        "%-50s %3dh | ligne: %6d->%6d | pres: %6d->%6d | hyb: %6d->%6d",
        mb_substr($r['titre'], 0, 50),
        $h,
        $tel_reel, $att['tel'],
        $tp_reel,  $att['tp'],
        $th_reel,  $att['th']
    );
}

echo "=== FIX TARIFS IBIG EDUFORM ===\n\n";
echo "Total formations locales : " . count($rows) . "\n";
echo "Corrigees                : $corrige\n";
echo "Inchangees (deja OK)     : $inchange\n";
echo "Ignorees (sans duree/SP) : $skipped\n\n";
echo "=== DETAILS DES CORRECTIONS ===\n\n";
foreach ($log as $l) echo $l . "\n";

echo "\n✅ Correction terminee. Lancer sync-to-partners pour propager vers Supabase.\n";
@unlink(__FILE__);
