<?php
require_once __DIR__ . '/../core/bootstrap.php';
header('Content-Type: text/plain; charset=utf-8');
$pdo = Database::connect();
$rows = $pdo->query("SELECT domaine, COUNT(*) as nb FROM formations WHERE is_samedi_pro=0 AND statut='active' AND (domaine LIKE '%Humanitaire%' OR domaine LIKE '%ONG%' OR domaine LIKE '%Développement%') GROUP BY domaine ORDER BY nb DESC")->fetchAll(PDO::FETCH_ASSOC);
foreach($rows as $r) echo $r['domaine'].' : '.$r['nb']." formation(s)\n";
echo "---\n";
$titres = $pdo->query("SELECT domaine, titre FROM formations WHERE is_samedi_pro=0 AND statut='active' AND (domaine LIKE '%Humanitaire%' OR domaine LIKE '%ONG%' OR domaine LIKE '%Développement%') ORDER BY domaine, titre")->fetchAll(PDO::FETCH_ASSOC);
foreach($titres as $r) echo "[".$r['domaine']."] ".$r['titre']."\n";
?>