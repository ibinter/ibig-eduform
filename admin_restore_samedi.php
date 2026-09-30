<?php
/* Script one-shot : diagnostic + restauration des tarifs Samedi Pro
   Récupère les prix depuis l'API Partners (source de vérité) et
   les réapplique en base locale pour les formations is_samedi_pro=1.
   Protégé par token. Supprimer après usage. */
if (($_GET['token'] ?? '') !== 'IBIG_RESTORE_2026') {
    http_response_code(403); exit('Accès refusé');
}
header('Content-Type: text/html; charset=utf-8');
$mode = $_GET['mode'] ?? 'preview';

/* ── DB ── */
try {
    $pdo = new PDO(
        'mysql:host=127.0.0.1;dbname=ibigs2689720_7ja7u;charset=utf8mb4',
        'ibigs2689720_7ja7u', 'eP2-Q9Rt2D-RF9n',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (Throwable $e) {
    die('<p style="color:red">DB error: '.htmlspecialchars($e->getMessage()).'</p>');
}

/* ── Samedi Pro en base ── */
$sam_rows = $pdo->query("
    SELECT id, titre, slug, tarif_en_ligne, tarif_presentiel, duree
    FROM formations
    WHERE COALESCE(is_samedi_pro,0) = 1 AND statut = 'active'
    ORDER BY titre ASC
")->fetchAll(PDO::FETCH_ASSOC);

/* ── Catalogue API Partners ── */
$api_formations = [];
$api_ok = false;
$ctx = stream_context_create(['http'=>['timeout'=>8,'method'=>'GET','header'=>"Accept: application/json\r\n"],'ssl'=>['verify_peer'=>true]]);
$json = @file_get_contents('https://www.ibigpartners.com/api/catalogue', false, $ctx);
if ($json) {
    $data = json_decode($json, true);
    if (!empty($data['ok']) && isset($data['formations'])) {
        $api_ok = true;
        foreach ($data['formations'] as $f) {
            $key = mb_strtolower(trim(preg_replace('/[\s\-–—_]+/',' ',(string)($f['name']??'')),'UTF-8'));
            $api_formations[$key] = $f;
        }
    }
}

$normalize = fn(string $s): string => mb_strtolower(trim(preg_replace('/[\s\-–—_]+/',' ',$s),'UTF-8'));

/* ── Préparer les actions ── */
$actions = [];
foreach ($sam_rows as $r) {
    $key = $normalize($r['titre']);
    $tel_actuel = (int)$r['tarif_en_ligne'];
    $tp_actuel  = (int)$r['tarif_presentiel'];

    if (isset($api_formations[$key])) {
        $af = $api_formations[$key];
        $tel_api = (int)($af['price'] ?? 0);
        // Récupérer grille de l'API si disponible
        $g = $af['grille'] ?? null;
        $tp_api = $g ? (int)($g['individuel_pres'] ?? 0) : 0;
        if ($tp_api <= 0 && $tel_api > 0) {
            $tp_api = (int)(round($tel_api * 10 / 7 / 5000) * 5000);
        }
        $actions[] = [
            'id' => (int)$r['id'],
            'titre' => $r['titre'],
            'tel_actuel' => $tel_actuel,
            'tp_actuel'  => $tp_actuel,
            'tel_api'    => $tel_api,
            'tp_api'     => $tp_api,
            'source'     => 'API Partners',
            'changed'    => ($tel_api > 0 && $tel_api !== $tel_actuel),
        ];
    } else {
        // Formation locale uniquement — on ne touche pas
        $actions[] = [
            'id' => (int)$r['id'],
            'titre' => $r['titre'],
            'tel_actuel' => $tel_actuel,
            'tp_actuel'  => $tp_actuel,
            'tel_api'    => 0,
            'tp_api'     => 0,
            'source'     => 'Local uniquement',
            'changed'    => false,
        ];
    }
}

/* ── Appliquer ── */
$nb_fixed = 0; $err_apply = null;
if ($mode === 'apply') {
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("UPDATE formations SET tarif_en_ligne=?, tarif_presentiel=? WHERE id=?");
        foreach ($actions as $a) {
            if ($a['changed'] && $a['tel_api'] > 0) {
                $stmt->execute([$a['tel_api'], $a['tp_api'] > 0 ? $a['tp_api'] : null, $a['id']]);
                $nb_fixed++;
            }
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        $err_apply = $e->getMessage();
    }
}

$nb_changed = count(array_filter($actions, fn($a) => $a['changed']));
$fmt = fn($n) => $n > 0 ? number_format((int)$n,0,',',' ').' F' : '—';
?><!DOCTYPE html>
<html lang="fr"><head><meta charset="UTF-8">
<title>Restauration Samedi Pro — IBIG EDUFORM</title>
<style>
body{font-family:monospace;font-size:13px;padding:20px;background:#f0f4ff}
h1{color:#0a1733}
.warn{background:#fff3cd;border-left:4px solid #f59e0b;padding:10px 14px;margin:12px 0;font-weight:bold}
.ok-box{background:#d4edda;border-left:4px solid #2e7d32;padding:10px 14px;margin:12px 0;font-weight:bold}
.err{background:#fde;border-left:4px solid red;padding:12px;margin:12px 0;font-weight:bold}
.summary{background:#fff;padding:12px 16px;border-left:4px solid #1565c0;margin:12px 0}
table{border-collapse:collapse;width:100%;margin-top:12px}
th{background:#1565c0;color:#fff;padding:7px 10px;text-align:left;font-size:12px}
td{padding:5px 10px;border-bottom:1px solid #cdd}
tr.changed td{background:#fff9c4}
tr.ok td{background:#f0fff0}
tr.local td{background:#f5f5f5;color:#888}
.actions{margin:14px 0;padding:14px;background:#fff;border:2px solid #1565c0;border-radius:8px}
a.btn{display:inline-block;padding:10px 20px;background:#1565c0;color:#fff;text-decoration:none;border-radius:6px;margin-right:8px;font-size:14px;font-weight:bold}
a.btn-apply{background:#c62828}
</style></head><body>
<h1>Restauration tarifs Samedi Pro</h1>
<p class="warn">⚠️ Formations <code>is_samedi_pro=1</code> uniquement. Les tarifs sont récupérés depuis l'API Partners.</p>

<?php if (!$api_ok): ?>
<div class="err">⚠️ API Partners inaccessible — impossible de récupérer les tarifs d'origine. Réessayez dans quelques minutes.</div>
<?php endif; ?>

<?php if ($err_apply): ?>
<div class="err">❌ Erreur : <?= htmlspecialchars($err_apply) ?></div>
<?php elseif ($mode === 'apply' && $nb_fixed > 0): ?>
<div class="ok-box">✅ <?= $nb_fixed ?> tarif(s) Samedi Pro restauré(s) depuis l'API Partners.</div>
<?php endif; ?>

<div class="summary">
  <strong>Total Samedi Pro en base :</strong> <?= count($sam_rows) ?> &nbsp;|&nbsp;
  <strong>Retrouvés dans l'API :</strong> <?= count(array_filter($actions, fn($a)=>$a['source']==='API Partners')) ?> &nbsp;|&nbsp;
  <strong style="color:#c62828">À restaurer (prix différent) :</strong> <?= $nb_changed ?>
  <?php if ($mode==='apply' && $nb_fixed>0): ?>&nbsp;|&nbsp;<strong style="color:#2e7d32">✅ <?= $nb_fixed ?> restauré(s)</strong><?php endif; ?>
</div>

<?php if ($mode==='preview' && $nb_changed > 0): ?>
<div class="actions">
  <strong><?= $nb_changed ?> tarif(s) Samedi Pro à restaurer vers les prix API Partners.</strong><br><br>
  <a class="btn btn-apply" href="?token=IBIG_RESTORE_2026&mode=apply">⚡ RESTAURER EN BASE</a>
  <a class="btn" href="?token=IBIG_RESTORE_2026&mode=preview">🔄 Rafraîchir</a>
</div>
<?php elseif ($mode==='preview' && $nb_changed === 0): ?>
<div class="ok-box">✅ Tous les tarifs Samedi Pro sont déjà conformes aux prix API Partners.</div>
<?php elseif ($mode==='apply'): ?>
<div class="actions" style="border-color:#2e7d32">
  <a class="btn" href="?token=IBIG_RESTORE_2026&mode=preview">🔄 Vérifier le résultat</a>
</div>
<?php endif; ?>

<table>
<thead>
  <tr><th>ID</th><th>Titre</th><th>En ligne actuel</th><th>→ API Partners</th><th>Présentiel actuel</th><th>→ API Partners</th><th>Source</th></tr>
</thead>
<tbody>
<?php foreach ($actions as $a):
  $cls = $a['source']==='Local uniquement' ? 'local' : ($a['changed'] ? 'changed' : 'ok');
?>
<tr class="<?= $cls ?>">
  <td><?= $a['id'] ?></td>
  <td><?= htmlspecialchars($a['titre']) ?></td>
  <td><?= $fmt($a['tel_actuel']) ?></td>
  <td><?= $a['tel_api'] > 0 ? ($a['changed'] ? '<strong>'.$fmt($a['tel_api']).'</strong>' : $fmt($a['tel_api'])) : '—' ?></td>
  <td><?= $fmt($a['tp_actuel']) ?></td>
  <td><?= $a['tp_api'] > 0 ? $fmt($a['tp_api']) : '—' ?></td>
  <td><?= $a['source'] ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</body></html>
