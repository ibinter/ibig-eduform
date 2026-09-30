<?php
if (($_GET['token'] ?? '') !== 'IBIG_DIAG') { http_response_code(403); exit('Accès refusé'); }
header('Content-Type: text/html; charset=utf-8');
try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=ibigs2689720_7ja7u;charset=utf8mb4',
        'ibigs2689720_7ja7u', 'eP2-Q9Rt2D-RF9n',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (Throwable $e) { die('DB: '.$e->getMessage()); }

$rows = $pdo->query("
    SELECT id, titre, duree, tarif_en_ligne, tarif_presentiel, updated_at
    FROM formations
    WHERE COALESCE(is_samedi_pro,0)=1 AND statut='active'
    ORDER BY id ASC
")->fetchAll(PDO::FETCH_ASSOC);
$fmt = fn($n) => $n>0 ? number_format((int)$n,0,',',' ').' F' : '—';
?><!DOCTYPE html><html><head><meta charset="UTF-8">
<title>Diag Samedi Pro</title>
<style>body{font-family:monospace;font-size:13px;padding:20px}
th{background:#1565c0;color:#fff;padding:6px 10px}td{padding:5px 10px;border-bottom:1px solid #ddd}
.hi td{background:#fff3cd;font-weight:bold}</style></head><body>
<h2>Prix Samedi Pro en base (<?= count($rows) ?> formations)</h2>
<table border=0 cellspacing=0>
<tr><th>ID</th><th>Titre</th><th>Durée</th><th>En ligne</th><th>Présentiel</th><th>updated_at</th></tr>
<?php foreach ($rows as $r):
  $suspect = (int)$r['tarif_en_ligne'] >= 200000 || (int)$r['tarif_presentiel'] >= 200000;
?>
<tr class="<?= $suspect?'hi':'' ?>">
  <td><?=$r['id']?></td>
  <td><?=htmlspecialchars($r['titre'])?></td>
  <td><?=htmlspecialchars($r['duree']??'')?></td>
  <td><?=$fmt((int)$r['tarif_en_ligne'])?></td>
  <td><?=$fmt((int)$r['tarif_presentiel'])?></td>
  <td><?=htmlspecialchars($r['updated_at']??'')?></td>
</tr>
<?php endforeach; ?>
</table>
<p style="color:#888;font-size:11px">Lignes surlignées = tarif ≥ 200 000 F (suspects)</p>
</body></html>
