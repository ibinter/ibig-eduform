<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/helpers.php';

$pdo = Database::connect();
function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$formationId = (int)($_GET['formation_id'] ?? 0);
if($formationId<=0){ http_response_code(404); exit("Formation introuvable."); }

$stmt = $pdo->prepare("
  SELECT f.*, l.*
  FROM formations f
  INNER JOIN formation_landings l ON l.formation_id = f.id
  WHERE f.id = ? AND f.statut='active'
  LIMIT 1
");
$stmt->execute([$formationId]);
$d = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$d){ http_response_code(404); exit("PDF indisponible."); }
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Fiche formation — <?= h($d['titre']); ?></title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
body{font-family:Arial, sans-serif; color:#111; margin:30px}
h1{margin:0 0 8px}
h2{margin:26px 0 10px; font-size:16px}
p{margin:8px 0; line-height:1.5}
hr{border:none;border-top:1px solid #ddd;margin:18px 0}
.small{color:#444;font-size:12px}
@media print{ .noprint{display:none} }
</style>
</head>
<body>

<div class="noprint small">
  Astuce : utilisez “Imprimer” puis “Enregistrer en PDF”.
  <button onclick="window.print()">Imprimer / PDF</button>
</div>

<h1><?= h($d['titre']); ?></h1>
<div class="small"><?= h($d['domaine']); ?> | <?= h(ucfirst(str_replace('_',' ', $d['mode'] ?? ''))); ?></div>

<hr>

<?php if(!empty($d['hero_subtitle'])): ?><p><strong>Sous-titre</strong><br><?= nl2br(h($d['hero_subtitle'])); ?></p><?php endif; ?>
<?php if(!empty($d['pitch_marketing'])): ?><p><strong>Pitch marketing</strong><br><?= nl2br(h($d['pitch_marketing'])); ?></p><?php endif; ?>

<?php if(!empty($d['contexte'])): ?><h2>Contexte & justification</h2><p><?= nl2br(h($d['contexte'])); ?></p><?php endif; ?>
<?php if(!empty($d['objectif_general'])): ?><h2>Objectif général</h2><p><?= nl2br(h($d['objectif_general'])); ?></p><?php endif; ?>
<?php if(!empty($d['objectifs_specifiques'])): ?><h2>Objectifs spécifiques</h2><p><?= nl2br(h($d['objectifs_specifiques'])); ?></p><?php endif; ?>
<?php if(!empty($d['contenu_programme'])): ?><h2>Programme</h2><p><?= nl2br(h($d['contenu_programme'])); ?></p><?php endif; ?>
<?php if(!empty($d['methodologie'])): ?><h2>Méthodologie</h2><p><?= nl2br(h($d['methodologie'])); ?></p><?php endif; ?>
<?php if(!empty($d['duree_organisation'])): ?><h2>Durée & organisation</h2><p><?= nl2br(h($d['duree_organisation'])); ?></p><?php endif; ?>
<?php if(!empty($d['debouches'])): ?><h2>Débouchés</h2><p><?= nl2br(h($d['debouches'])); ?></p><?php endif; ?>

<h2>Tarifs</h2>
<p>
  Présentiel : <?= number_format((int)$d['tarif_presentiel']); ?> FCFA<br>
  En ligne : <?= number_format((int)$d['tarif_en_ligne']); ?> FCFA
</p>

<?php if(!empty($d['contacts'])): ?><h2>Contacts</h2><p><?= nl2br(h($d['contacts'])); ?></p><?php endif; ?>

<script>
  // option : auto print
  // window.print();
</script>

</body>
</html>
