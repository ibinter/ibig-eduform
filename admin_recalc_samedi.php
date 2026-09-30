<?php
if (($_GET['token'] ?? '') !== 'IBIG_RECALC') { http_response_code(403); exit('Accès refusé'); }
header('Content-Type: text/html; charset=utf-8');
$mode = $_GET['mode'] ?? 'preview';

try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=ibigs2689720_7ja7u;charset=utf8mb4',
        'ibigs2689720_7ja7u', 'eP2-Q9Rt2D-RF9n',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (Throwable $e) { die('DB: '.$e->getMessage()); }

// Uniquement les formations corrompues ce matin (updated_at = 04:26)
$rows = $pdo->query("
    SELECT id, titre, duree, tarif_en_ligne, tarif_presentiel
    FROM formations
    WHERE COALESCE(is_samedi_pro,0)=1 AND statut='active'
      AND tarif_en_ligne = 200000
    ORDER BY id ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Formule : h × 11 250, arrondi à 5 000 (vers le haut)
$r5 = fn(int $h): int => (int)(ceil($h * 11250 / 5000) * 5000);

$actions = [];
foreach ($rows as $r) {
    // Extraire les heures depuis le champ duree : "1 samedi (6h)", "2 samedis (12h)", "7H", etc.
    $heures = 0;
    if (preg_match('/\((\d+)h\)/i', $r['duree'], $m)) $heures = (int)$m[1];
    elseif (preg_match('/^(\d+)H$/i', trim($r['duree']), $m)) $heures = (int)$m[1];

    $nouveau_tp = $heures > 0 ? $r5($heures) : 0;
    $actions[] = [
        'id'          => (int)$r['id'],
        'titre'       => $r['titre'],
        'duree'       => $r['duree'],
        'heures'      => $heures,
        'tel_actuel'  => (int)$r['tarif_en_ligne'],
        'tp_actuel'   => (int)$r['tarif_presentiel'],
        'nouveau_tp'  => $nouveau_tp,
    ];
}

$nb_fixed = 0; $err = null;
if ($mode === 'apply') {
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("UPDATE formations SET tarif_en_ligne=0, tarif_presentiel=? WHERE id=? AND COALESCE(is_samedi_pro,0)=1");
        foreach ($actions as $a) {
            if ($a['nouveau_tp'] > 0) {
                $stmt->execute([$a['nouveau_tp'], $a['id']]);
                $nb_fixed++;
            }
        }
        $pdo->commit();
    } catch (Throwable $e) { $pdo->rollBack(); $err = $e->getMessage(); }
}

$fmt = fn($n) => $n > 0 ? number_format((int)$n, 0, ',', ' ').' F' : '—';
?><!DOCTYPE html><html><head><meta charset="UTF-8"><title>Recalcul Samedi Pro</title>
<style>body{font-family:monospace;font-size:13px;padding:20px;background:#f0f4ff}
h1{color:#1565c0}.ok{background:#d4edda;border-left:4px solid green;padding:10px;margin:10px 0;font-weight:bold}
.err{background:#fde;border-left:4px solid red;padding:10px;margin:10px 0}
table{border-collapse:collapse;width:100%;margin-top:10px}
th{background:#1565c0;color:#fff;padding:6px 10px}td{padding:5px 10px;border-bottom:1px solid #ddd}
tr.ok td{background:#f0fff0}tr.warn td{background:#fff3cd}
.btn{display:inline-block;padding:11px 22px;background:#c62828;color:#fff;text-decoration:none;border-radius:6px;font-weight:bold;margin-top:12px}
.btn2{background:#1565c0}</style></head><body>
<h1>Recalcul tarifs Samedi Pro (formule h × 11 250)</h1>
<p>Tarif présentiel = heures × 11 250 FCFA arrondi à 5 000. Tarif en ligne → 0 (Samedi Pro = présentiel uniquement).</p>

<?php if ($err): ?><div class="err">❌ <?= htmlspecialchars($err) ?></div><?php endif; ?>
<?php if ($mode==='apply' && $nb_fixed>0): ?><div class="ok">✅ <?= $nb_fixed ?> formation(s) recalculée(s).</div><?php endif; ?>

<?php if ($mode==='preview' && !empty($actions)): ?>
<div style="margin:12px 0;padding:12px;background:#fff;border:2px solid #c62828;border-radius:6px">
  <strong><?= count($actions) ?> formations à corriger.</strong><br><br>
  <a class="btn" href="?token=IBIG_RECALC&mode=apply">⚡ APPLIQUER</a>
  <a class="btn btn2" href="?token=IBIG_RECALC&mode=preview" style="margin-left:8px">🔄 Rafraîchir</a>
</div>
<?php elseif ($mode==='preview' && empty($actions)): ?>
<div class="ok">✅ Aucune formation corrompue détectée (tarif_en_ligne ≠ 200 000 F).</div>
<?php elseif ($mode==='apply'): ?>
<div style="margin:10px 0"><a class="btn btn2" href="?token=IBIG_RECALC&mode=preview">🔄 Vérifier</a></div>
<?php endif; ?>

<table><thead><tr>
  <th>ID</th><th>Titre</th><th>Durée</th><th>H</th>
  <th>En ligne actuel → nouveau</th><th>Présentiel actuel → nouveau</th>
</tr></thead><tbody>
<?php foreach ($actions as $a): $ok = $a['nouveau_tp']>0; ?>
<tr class="<?= $ok?'ok':'warn' ?>">
  <td><?= $a['id'] ?></td>
  <td><?= htmlspecialchars($a['titre']) ?></td>
  <td><?= htmlspecialchars($a['duree']) ?></td>
  <td><?= $a['heures'] ?: '?' ?></td>
  <td><?= $fmt($a['tel_actuel']) ?> → <strong>0 F</strong></td>
  <td><?= $fmt($a['tp_actuel']) ?> → <strong><?= $ok ? $fmt($a['nouveau_tp']) : '⚠️ heures non trouvées' ?></strong></td>
</tr>
<?php endforeach; ?>
</tbody></table>
</body></html>
