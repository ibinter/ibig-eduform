<?php
declare(strict_types=1);
/* =========================================================
   IBIG EDUFORM — Calendrier complet (imprimable / PDF)
   Lead magnet : « Télécharger le programme complet ».
========================================================= */
require_once __DIR__ . '/core/bootstrap.php';
$pdo = Database::connect();

$rows = [];
try {
  $st = $pdo->query("SELECT titre, type_certificat, duree, date_debut, mois, tarif_en_ligne, tarif_presentiel, is_samedi_pro
                     FROM formations
                     WHERE statut='active' AND date_debut IS NOT NULL AND COALESCE(date_fin,date_debut) >= CURDATE()
                     ORDER BY date_debut ASC");
  $rows = $st->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) { $rows = []; }

function hh($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function fcfa($n): string { return number_format((int)$n, 0, ',', ' ') . ' FCFA'; }
$moisFr = [1=>'Janvier',2=>'Février',3=>'Mars',4=>'Avril',5=>'Mai',6=>'Juin',7=>'Juillet',8=>'Août',9=>'Septembre',10=>'Octobre',11=>'Novembre',12=>'Décembre'];
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Programme officiel des formations — IBIG EDUFORM</title>
<link rel="icon" href="/favicon.ico">
<style>
  :root{--blue:#0B4AA6;--dark:#0F2742;--red:#FF2E2E;--gold:#f5a623;--line:#e6eaf2;--muted:#5b6b8c}
  *{box-sizing:border-box}
  body{margin:0;font-family:Inter,system-ui,Segoe UI,Arial,sans-serif;color:#0f172a;background:#eef2fb;padding:24px 14px}
  .sheet{max-width:900px;margin:0 auto;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 24px 60px rgba(15,23,42,.15)}
  .head{background:linear-gradient(135deg,var(--dark),var(--blue));color:#fff;padding:28px 32px;display:flex;align-items:center;gap:16px;border-bottom:4px solid var(--gold)}
  .head img{height:50px;width:auto;object-fit:contain}
  .head .ph{width:50px;height:50px;border-radius:12px;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-weight:800}
  .head h1{margin:0;font-size:20px;font-weight:800}
  .head p{margin:3px 0 0;font-size:12.5px;opacity:.9}
  .body{padding:26px 32px}
  .mois{margin:22px 0 10px;font-size:15px;font-weight:800;color:var(--blue);border-bottom:2px solid var(--line);padding-bottom:6px}
  .mois:first-child{margin-top:0}
  table{width:100%;border-collapse:collapse;font-size:13px}
  th{text-align:left;color:var(--muted);font-weight:600;padding:8px 8px;border-bottom:1px solid var(--line);font-size:11px;text-transform:uppercase;letter-spacing:.4px}
  td{padding:10px 8px;border-bottom:1px solid #f0f3f9;vertical-align:top}
  .f-titre{font-weight:700;color:#0f172a}
  .f-type{display:inline-block;font-size:10.5px;font-weight:700;padding:2px 8px;border-radius:999px;margin-top:3px}
  .t-pack{background:rgba(11,74,166,.1);color:var(--blue)}
  .t-sam{background:rgba(245,166,35,.15);color:#9a6300}
  .prix{white-space:nowrap;font-weight:700}
  .foot{padding:18px 32px 28px;border-top:1px solid var(--line);color:var(--muted);font-size:11.5px;line-height:1.7}
  .actions{max-width:900px;margin:18px auto 0;display:flex;gap:10px;justify-content:center;flex-wrap:wrap}
  .btn{border:0;cursor:pointer;font-family:inherit;font-weight:700;font-size:13.5px;padding:13px 22px;border-radius:12px;text-decoration:none;display:inline-flex;align-items:center;gap:8px}
  .btn.p{background:linear-gradient(135deg,var(--blue),var(--dark));color:#fff}
  .btn.s{background:#fff;color:var(--blue);border:1.5px solid var(--line)}
  @media print{ body{background:#fff;padding:0} .sheet{box-shadow:none;border-radius:0;max-width:100%} .actions{display:none} .head{-webkit-print-color-adjust:exact;print-color-adjust:exact} }
</style>
</head>
<body>
  <div class="sheet">
    <div class="head">
      <img src="/assets/images/logo.png" alt="IBIG EDUFORM" onerror="this.outerHTML='<div class=&quot;ph&quot;>IB</div>'">
      <div>
        <h1>Programme officiel des formations</h1>
        <p>IBIG EDUFORM — Sessions à venir · Document généré le <?= hh(date('d/m/Y')); ?></p>
      </div>
    </div>
    <div class="body">
      <?php if (!$rows): ?>
        <p>Aucune session à venir pour le moment. Contactez-nous pour les prochaines dates.</p>
      <?php else: ?>
        <?php
          $currentKey = '';
          foreach ($rows as $r):
            $ts = strtotime((string)$r['date_debut']);
            $key = date('Y-m', $ts);
            if ($key !== $currentKey):
              if ($currentKey !== '') echo "</tbody></table>";
              $currentKey = $key;
              $libMois = ($moisFr[(int)date('n', $ts)] ?? '') . ' ' . date('Y', $ts);
        ?>
          <div class="mois"><?= hh($libMois); ?></div>
          <table>
            <thead><tr><th style="width:90px">Date</th><th>Formation</th><th style="width:60px">Durée</th><th style="width:130px">En ligne</th><th style="width:130px">Présentiel</th></tr></thead>
            <tbody>
        <?php endif; $sam = !empty($r['is_samedi_pro']); ?>
            <tr>
              <td><b><?= hh(date('d/m', $ts)); ?></b></td>
              <td>
                <span class="f-titre"><?= hh($r['titre']); ?></span><br>
                <span class="f-type <?= $sam ? 't-sam' : 't-pack'; ?>"><?= hh($r['type_certificat'] ?: ($sam ? 'Samedi Pro' : 'Pack Premium')); ?></span>
              </td>
              <td><?= hh($r['duree']); ?></td>
              <td class="prix"><?= fcfa($r['tarif_en_ligne']); ?></td>
              <td class="prix"><?= fcfa($r['tarif_presentiel']); ?></td>
            </tr>
        <?php endforeach; ?>
            </tbody></table>
      <?php endif; ?>
    </div>
    <div class="foot">
      📞 +225 07 78 88 25 92 &nbsp;·&nbsp; ✉️ formation@intermark-business.com &nbsp;·&nbsp; 🌐 ibig-eduform.com<br>
      Pack Premium = plusieurs certificats · Samedi Pro = attestation. Tarifs en FCFA, sous réserve de modification.
      © <?= hh(date('Y')); ?> IBIG EDUFORM.
    </div>
  </div>
  <div class="actions">
    <a href="#" class="btn p" onclick="window.print();return false;">📄 Télécharger / Imprimer (PDF)</a>
    <a href="/formations.php" class="btn s">← Voir les formations</a>
  </div>
</body>
</html>
