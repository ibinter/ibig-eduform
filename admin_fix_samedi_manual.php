<?php
/* Correction manuelle des tarifs Samedi Pro
   Affiche toutes les formations is_samedi_pro=1 avec champs éditables.
   Protégé par token. Supprimer après usage. */
if (($_GET['token'] ?? '') !== 'IBIG_SAM_2026') {
    http_response_code(403); exit('Accès refusé');
}
header('Content-Type: text/html; charset=utf-8');

try {
    $pdo = new PDO(
        'mysql:host=127.0.0.1;dbname=ibigs2689720_7ja7u;charset=utf8mb4',
        'ibigs2689720_7ja7u', 'eP2-Q9Rt2D-RF9n',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (Throwable $e) {
    die('<p style="color:red">DB error: '.htmlspecialchars($e->getMessage()).'</p>');
}

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['formations'])) {
    $stmt = $pdo->prepare("UPDATE formations SET tarif_en_ligne=?, tarif_presentiel=? WHERE id=? AND COALESCE(is_samedi_pro,0)=1");
    $nb = 0;
    foreach ($_POST['formations'] as $id => $vals) {
        $tel = isset($vals['tel']) && $vals['tel'] !== '' ? (int)$vals['tel'] : null;
        $tp  = isset($vals['tp'])  && $vals['tp']  !== '' ? (int)$vals['tp']  : null;
        $stmt->execute([$tel, $tp, (int)$id]);
        $nb++;
    }
    $msg = "<div class='ok'>✅ $nb formation(s) mise(s) à jour.</div>";
}

$rows = $pdo->query("
    SELECT id, titre, duree, tarif_en_ligne, tarif_presentiel
    FROM formations
    WHERE COALESCE(is_samedi_pro,0) = 1 AND statut = 'active'
    ORDER BY tarif_en_ligne ASC, titre ASC
")->fetchAll(PDO::FETCH_ASSOC);
$fmt = fn($n) => $n > 0 ? number_format((int)$n,0,',',' ').' F' : '—';
?><!DOCTYPE html>
<html lang="fr"><head><meta charset="UTF-8">
<title>Fix Samedi Pro — IBIG</title>
<style>
body{font-family:Arial,sans-serif;font-size:13px;padding:20px;background:#f0f4ff;max-width:900px;margin:0 auto}
h1{color:#1565c0}
.ok{background:#d4edda;border-left:4px solid #2e7d32;padding:10px 14px;margin:12px 0;font-weight:bold}
table{border-collapse:collapse;width:100%;margin-top:12px}
th{background:#1565c0;color:#fff;padding:8px 10px;text-align:left}
td{padding:6px 10px;border-bottom:1px solid #cdd;vertical-align:middle}
tr:nth-child(even) td{background:#f7f9ff}
input[type=number]{width:100px;padding:4px 6px;border:1px solid #aaa;border-radius:4px;font-size:13px}
input[type=number].changed{border-color:#c62828;background:#fff3e0}
.btn{display:inline-block;padding:11px 24px;background:#c62828;color:#fff;border:none;border-radius:6px;font-size:14px;font-weight:bold;cursor:pointer;margin-top:14px}
.note{color:#777;font-size:11px}
</style></head><body>
<h1>Correction manuelle — Tarifs Samedi Pro</h1>
<p>Modifiez directement les prix en ligne et présentiel. <strong>Laisser vide = pas de tarif (sur devis).</strong></p>
<?= $msg ?>
<form method="post" action="?token=IBIG_SAM_2026">
<table>
<thead><tr>
  <th>ID</th><th>Titre</th><th>Durée</th>
  <th>Actuel En ligne</th><th>→ Nouveau En ligne (F)</th>
  <th>Actuel Présentiel</th><th>→ Nouveau Présentiel (F)</th>
</tr></thead>
<tbody>
<?php foreach ($rows as $r): ?>
<tr>
  <td><?= $r['id'] ?></td>
  <td><strong><?= htmlspecialchars($r['titre']) ?></strong></td>
  <td class="note"><?= htmlspecialchars($r['duree'] ?? '') ?></td>
  <td><?= $fmt((int)$r['tarif_en_ligne']) ?></td>
  <td><input type="number" name="formations[<?= $r['id'] ?>][tel]"
       value="<?= $r['tarif_en_ligne'] > 0 ? (int)$r['tarif_en_ligne'] : '' ?>"
       placeholder="vide = devis" min="0" step="5000"></td>
  <td><?= $fmt((int)$r['tarif_presentiel']) ?></td>
  <td><input type="number" name="formations[<?= $r['id'] ?>][tp]"
       value="<?= $r['tarif_presentiel'] > 0 ? (int)$r['tarif_presentiel'] : '' ?>"
       placeholder="vide = devis" min="0" step="5000"></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<button class="btn" type="submit">💾 ENREGISTRER LES MODIFICATIONS</button>
</form>
</body></html>
