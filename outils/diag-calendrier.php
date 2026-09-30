<?php
declare(strict_types=1);
require_once __DIR__ . '/../core/config.php';
$pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);

// Formations du calendrier (avec date_debut)
$rows = $pdo->query("
    SELECT f.id, f.titre, f.slug, f.statut, f.is_samedi_pro,
           f.tarif_en_ligne, f.tarif_presentiel, f.date_debut, f.date_fin,
           l.modalites_participation
    FROM formations f
    LEFT JOIN formation_landings l ON l.formation_id = f.id
    WHERE f.date_debut IS NOT NULL AND f.date_debut != ''
    ORDER BY f.date_debut DESC
")->fetchAll(PDO::FETCH_ASSOC);

echo '<html><head><meta charset="utf-8"><title>Diag calendrier</title>
<style>
body{font-family:monospace;background:#0d1f3c;color:#e2e8f0;padding:20px;font-size:13px}
h1{color:#f59e0b;margin-bottom:16px}
table{border-collapse:collapse;width:100%}
th{background:#1e3a6e;color:#fff;padding:8px 10px;text-align:left;font-size:12px}
td{padding:7px 10px;border-bottom:1px solid #1e3a6e;vertical-align:top}
tr:hover td{background:#1a2d50}
.ok{color:#34d399}
.warn{color:#f59e0b}
.err{color:#f87171}
.sm{font-size:11px;opacity:.7}
</style></head><body>
<h1>🔍 Diagnostic — Formations du calendrier</h1>';

echo '<table><thead><tr>
<th>ID</th><th>Titre</th><th>Statut</th><th>Samedi Pro</th><th>Date début</th>
<th>Tarif DB en ligne</th><th>Tarif DB présentiel</th>
<th>Tarif hardcodé dans landing</th><th>Slug (TDR)</th>
</tr></thead><tbody>';

foreach ($rows as $r) {
    // Extraire tarif hardcodé dans modalites_participation
    $moda = (string)($r['modalites_participation'] ?? '');
    $tarif_hc = '';
    if (preg_match('/Tarif en ligne\s*:\s*([\d\s]+FCFA[^\.]*)/u', $moda, $m)) {
        $tarif_hc = trim($m[0]);
    }

    // Vérifier cohérence
    $tl = (int)$r['tarif_en_ligne'];
    $tp = (int)$r['tarif_presentiel'];
    $slug = (string)$r['slug'];

    // Extraire le tarif hardcodé numérique pour comparer
    $hc_match = false;
    if ($tarif_hc && preg_match('/Tarif en ligne\s*:\s*([\d\s]+)\s*FCFA/u', $tarif_hc, $nm)) {
        $hc_val = (int)preg_replace('/\D/', '', $nm[1]);
        $hc_match = ($hc_val === $tl);
    }

    $tarif_ok = !$tarif_hc || $hc_match;
    $slug_ok  = $slug !== '';

    $statut_cl = $r['statut'] === 'active' ? 'ok' : 'err';

    echo '<tr>';
    echo '<td>' . $r['id'] . '</td>';
    echo '<td>' . htmlspecialchars($r['titre']) . '</td>';
    echo '<td class="' . $statut_cl . '">' . $r['statut'] . '</td>';
    echo '<td>' . ($r['is_samedi_pro'] ? '<span class="warn">OUI</span>' : 'non') . '</td>';
    echo '<td>' . $r['date_debut'] . '</td>';
    echo '<td>' . ($tl > 0 ? number_format($tl,0,',',' ').' FCFA' : '<span class="err">—</span>') . '</td>';
    echo '<td>' . ($tp > 0 ? number_format($tp,0,',',' ').' FCFA' : '<span class="err">—</span>') . '</td>';
    echo '<td>';
    if (!$tarif_hc) {
        echo '<span class="ok">pas de tarif hardcodé</span>';
    } elseif ($tarif_ok) {
        echo '<span class="ok">✓ cohérent</span><br><span class="sm">' . htmlspecialchars(mb_substr($tarif_hc,0,60)) . '</span>';
    } else {
        echo '<span class="err">⚠ INCOHÉRENT</span><br><span class="sm">' . htmlspecialchars(mb_substr($tarif_hc,0,60)) . '</span>';
    }
    echo '</td>';
    echo '<td>' . ($slug_ok ? '<span class="ok">✓ ' . htmlspecialchars($slug) . '</span>' : '<span class="err">⚠ MANQUANT</span>') . '</td>';
    echo '</tr>';
}

echo '</tbody></table></body></html>';
