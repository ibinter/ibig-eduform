<?php
/* Script one-shot : audit + correction des tarifs sous 200 000 F CFA
   ⚠️  Les formations Samedi Pro (is_samedi_pro = 1) sont EXCLUES.
   Protégé par token. Supprimer après usage. */
if (($_GET['token'] ?? '') !== 'IBIG_FIX_2026') {
    http_response_code(403); exit('Accès refusé');
}

header('Content-Type: text/html; charset=utf-8');

$PRIX_FORMULE = [
    20=>225000,25=>280000,28=>315000,30=>340000,35=>395000,
    40=>450000,45=>505000,55=>620000,65=>730000,72=>810000,80=>900000
];
$PLANCHER = 200000;
$mode     = $_GET['mode'] ?? 'preview';

/* ── Connexion DB ── */
$err_db = null;
try {
    $pdo = new PDO(
        'mysql:host=127.0.0.1;dbname=ibigs2689720_7ja7u;charset=utf8mb4',
        'ibigs2689720_7ja7u', 'eP2-Q9Rt2D-RF9n',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (Throwable $e) {
    $err_db = $e->getMessage();
}
?><!DOCTYPE html>
<html lang="fr"><head><meta charset="UTF-8">
<title>Audit tarifs — IBIG EDUFORM</title>
<style>
body{font-family:monospace;font-size:13px;padding:20px;background:#f8f9fa}
h1{color:#0a1733}
.err{background:#fde;border-left:4px solid red;padding:12px;margin:12px 0;font-weight:bold}
.warn{background:#fff3cd;border-left:4px solid #f59e0b;padding:10px 14px;margin:12px 0;font-weight:bold}
.ok-box{background:#d4edda;border-left:4px solid #2e7d32;padding:10px 14px;margin:12px 0;font-weight:bold}
.summary{background:#fff;padding:12px 16px;border-left:4px solid #0a1733;margin:12px 0}
table{border-collapse:collapse;width:100%;margin-top:12px}
th{background:#0a1733;color:#fff;padding:7px 10px;text-align:left;font-size:12px}
td{padding:5px 10px;border-bottom:1px solid #e0e0e0;vertical-align:middle}
tr.corrige td{background:#fff3cd}
tr.ok td{background:#f0fff0}
tr.devis td,tr.ignore td{background:#f5f5f5;color:#999}
tr.samedi td{background:#e8f4fd;color:#1565c0;font-style:italic}
.badge{padding:2px 8px;border-radius:4px;font-size:11px;font-weight:bold}
.b-corr{background:#e65100;color:#fff}
.b-ok{background:#2e7d32;color:#fff}
.b-dev,.b-ign{background:#999;color:#fff}
.b-sam{background:#1565c0;color:#fff}
.actions{margin:14px 0;padding:14px;background:#fff;border:2px solid #0a1733;border-radius:8px}
a.btn{display:inline-block;padding:10px 20px;background:#0a1733;color:#fff;text-decoration:none;border-radius:6px;margin-right:8px;font-size:14px;font-weight:bold}
a.btn-apply{background:#c62828}
</style></head><body>
<h1>Audit &amp; correction tarifs — IBIG EDUFORM</h1>
<p class="warn">⚠️ Samedi Pro exclus. Seules les formations certifiantes sont concernées (plancher 200 000 F CFA).</p>

<?php if ($err_db): ?>
<div class="err">❌ Erreur connexion DB : <?= htmlspecialchars($err_db) ?></div>
</body></html>
<?php exit; endif; ?>

<?php
/* ── Lecture DB ── */
try {
    $rows = $pdo->query("
        SELECT id, titre, duree, tarif_en_ligne, tarif_presentiel, statut,
               COALESCE(is_samedi_pro, 0) AS is_samedi_pro
        FROM formations
        ORDER BY is_samedi_pro ASC, tarif_en_ligne ASC, titre ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    echo '<div class="err">❌ Erreur requête : '.htmlspecialchars($e->getMessage()).'</div>';
    exit;
}

$fixes = $rapport = [];

foreach ($rows as $r) {
    $id    = (int)$r['id'];
    $titre = $r['titre'];
    $tel   = (int)$r['tarif_en_ligne'];
    $tp    = (int)$r['tarif_presentiel'];
    $duree = (string)($r['duree'] ?? '');
    $isSam = (bool)(int)$r['is_samedi_pro'];

    /* Samedi Pro → EXCLURE */
    if ($isSam) {
        $rapport[] = ['id'=>$id,'titre'=>$titre,'tel'=>$tel,'tp'=>$tp,
                      'action'=>'SAMEDI PRO','raison'=>'Tarif libre, non modifié','cls'=>'samedi','badge'=>'b-sam'];
        continue;
    }

    /* Sans prix → sur devis */
    if ($tel <= 0) {
        $rapport[] = ['id'=>$id,'titre'=>$titre,'tel'=>$tel,'tp'=>$tp,
                      'action'=>'DEVIS','raison'=>'Pas de tarif affiché','cls'=>'devis','badge'=>'b-dev'];
        continue;
    }

    $nouveau_tel = $tel;
    $raison      = '';

    /* Correction formule heures */
    if (preg_match('/\((\d+)h\)/', $titre . ' ' . $duree, $m)) {
        $h  = (int)$m[1];
        $pf = isset($PRIX_FORMULE[$h])
            ? $PRIX_FORMULE[$h]
            : (int)(round($h * 11250 / 5000) * 5000);
        if ($tel < $pf) { $nouveau_tel = $pf; $raison = "{$h}h × 11 250 → ".number_format($pf,0,',',' ')." F"; }
    }

    /* Plancher absolu 200 000 F */
    if ($nouveau_tel < $PLANCHER) {
        $nouveau_tel = $PLANCHER;
        $raison = $raison ?: "Plancher 200 000 F CFA";
    }

    if ($nouveau_tel !== $tel) {
        $ip         = (int)(round($nouveau_tel * 10 / 7 / 5000) * 5000);
        $nouveau_tp = ($tp > 0 && $tp < $ip) ? $ip : $tp;
        $fixes[]    = ['id'=>$id,'nouveau_tel'=>$nouveau_tel,'nouveau_tp'=>$nouveau_tp,'ancien_tp'=>$tp];
        $rapport[]  = ['id'=>$id,'titre'=>$titre,'tel'=>$tel,'nouveau_tel'=>$nouveau_tel,
                       'tp'=>$tp,'nouveau_tp'=>$nouveau_tp,
                       'action'=>'CORRIGÉ','raison'=>$raison,'cls'=>'corrige','badge'=>'b-corr'];
    } else {
        $rapport[] = ['id'=>$id,'titre'=>$titre,'tel'=>$tel,'tp'=>$tp,
                      'action'=>'OK','raison'=>'Conforme','cls'=>'ok','badge'=>'b-ok'];
    }
}

/* ── Appliquer ── */
$nb_fixes = 0; $err_apply = null;
if ($mode === 'apply') {
    if (empty($fixes)) {
        $err_apply = 'Aucune correction à appliquer.';
    } else {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("UPDATE formations SET tarif_en_ligne=?, tarif_presentiel=? WHERE id=?");
            foreach ($fixes as $fx) {
                $stmt->execute([$fx['nouveau_tel'], $fx['nouveau_tp'] > 0 ? $fx['nouveau_tp'] : null, $fx['id']]);
                $nb_fixes++;
            }
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            $err_apply = $e->getMessage();
        }
    }
}

$nb_sam   = count(array_filter($rapport, fn($x) => $x['action']==='SAMEDI PRO'));
$nb_devis = count(array_filter($rapport, fn($x) => $x['action']==='DEVIS'));
$nb_ok    = count(array_filter($rapport, fn($x) => $x['action']==='OK'));
?>

<div class="summary">
  <strong>Total DB :</strong> <?= count($rows) ?> &nbsp;|&nbsp;
  <strong>Samedi Pro (exclus) :</strong> <?= $nb_sam ?> &nbsp;|&nbsp;
  <strong>Sur devis (exclus) :</strong> <?= $nb_devis ?> &nbsp;|&nbsp;
  <strong>Conformes :</strong> <?= $nb_ok ?> &nbsp;|&nbsp;
  <strong style="color:#e65100">À corriger : <?= count($fixes) ?></strong>
  <?php if ($mode==='apply' && $nb_fixes > 0): ?>&nbsp;|&nbsp;
    <strong style="color:#2e7d32">✅ <?= $nb_fixes ?> correction(s) appliquée(s)</strong>
  <?php endif; ?>
</div>

<?php if ($err_apply): ?>
  <div class="err">❌ Erreur lors de l'application : <?= htmlspecialchars($err_apply) ?></div>
<?php elseif ($mode==='apply' && $nb_fixes > 0): ?>
  <div class="ok-box">✅ <?= $nb_fixes ?> tarif(s) corrigé(s) en base de données.</div>
<?php endif; ?>

<?php if ($mode==='preview' && !empty($fixes)): ?>
<div class="actions">
  <strong><?= count($fixes) ?> tarif(s) à corriger (Samedi Pro exclus).</strong><br><br>
  <a class="btn btn-apply" href="?token=IBIG_FIX_2026&mode=apply">⚡ APPLIQUER LES CORRECTIONS EN BASE</a>
  <a class="btn" href="?token=IBIG_FIX_2026&mode=preview">🔄 Rafraîchir</a>
</div>
<?php elseif ($mode==='preview' && empty($fixes)): ?>
<div class="ok-box">✅ Tous les tarifs certifiants en base sont conformes (≥ 200 000 F CFA).</div>
<?php elseif ($mode==='apply'): ?>
<div class="actions" style="border-color:#2e7d32">
  <a class="btn" href="?token=IBIG_FIX_2026&mode=preview">🔄 Vérifier le résultat final</a>
</div>
<?php endif; ?>

<table>
<thead>
  <tr>
    <th>ID</th><th>Titre</th><th>En ligne actuel</th><th>→ Corrigé</th>
    <th>Présentiel actuel</th><th>→ Corrigé</th><th>Statut</th><th>Raison</th>
  </tr>
</thead>
<tbody>
<?php foreach ($rapport as $r): ?>
<tr class="<?= $r['cls'] ?>">
  <td><?= $r['id'] ?></td>
  <td><?= htmlspecialchars($r['titre']) ?></td>
  <td><?= $r['tel'] > 0 ? number_format($r['tel'],0,',',' ').' F' : '—' ?></td>
  <td><?= isset($r['nouveau_tel']) && $r['nouveau_tel'] !== $r['tel']
        ? '<strong>'.number_format($r['nouveau_tel'],0,',',' ').' F</strong>' : '' ?></td>
  <td><?= $r['tp'] > 0 ? number_format($r['tp'],0,',',' ').' F' : '—' ?></td>
  <td><?= isset($r['nouveau_tp']) && $r['nouveau_tp'] !== $r['tp']
        ? '<strong>'.number_format($r['nouveau_tp'],0,',',' ').' F</strong>' : '' ?></td>
  <td><span class="badge <?= $r['badge'] ?>"><?= $r['action'] ?></span></td>
  <td><?= htmlspecialchars($r['raison']) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</body></html>
