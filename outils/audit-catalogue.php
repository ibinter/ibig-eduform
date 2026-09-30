<?php
require_once __DIR__ . '/../core/secrets.php';
header('Content-Type: application/json; charset=utf-8');
try {
    $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
} catch (Exception $e) { echo json_encode(['error'=>$e->getMessage()]); exit; }

// Colonnes disponibles
$cols = $pdo->query("DESCRIBE formations")->fetchAll(PDO::FETCH_COLUMN, 0);

// Toutes les formations hors samedi pro (sans ORDER BY pour éviter erreur de colonne)
$stmt = $pdo->query("SELECT * FROM formations WHERE is_samedi_pro = 0");
$formations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Détecter les noms de colonnes dynamiquement
$sample = $formations[0] ?? [];
$col_nom = isset($sample['titre']) ? 'titre' : (isset($sample['name']) ? 'name' : (isset($sample['nom']) ? 'nom' : (isset($sample['title']) ? 'title' : 'titre')));
$col_dom = isset($sample['domaine']) ? 'domaine' : (isset($sample['categorie']) ? 'categorie' : (isset($sample['category']) ? 'category' : 'domaine'));
$col_h   = isset($sample['duree_heures']) ? 'duree_heures' : (isset($sample['duree']) ? 'duree' : (isset($sample['heures']) ? 'heures' : 'duree_heures'));
$col_ol  = isset($sample['tarif_en_ligne']) ? 'tarif_en_ligne' : (isset($sample['prix_en_ligne']) ? 'prix_en_ligne' : 'tarif_en_ligne');
$col_pr  = isset($sample['tarif_presentiel']) ? 'tarif_presentiel' : (isset($sample['prix_presentiel']) ? 'prix_presentiel' : 'tarif_presentiel');
$col_act = isset($sample['is_active']) ? 'is_active' : (isset($sample['active']) ? 'active' : 'is_active');

$alertes_tarif = [];
$groupees = [];
$stats = ['total'=>0,'actives'=>0,'inactives'=>0,'tarif_ko'=>0,'colonnes_detectees'=>compact('col_nom','col_dom','col_h','col_ol','col_pr','col_act')];
$noms_by_domaine = [];

foreach ($formations as $f) {
    $stats['total']++;
    $actif = isset($f[$col_act]) ? (int)$f[$col_act] : 1;
    if ($actif) $stats['actives']++; else $stats['inactives']++;

    $ol = (int)($f[$col_ol] ?? 0);
    $pr = (int)($f[$col_pr] ?? 0);
    $h  = (int)($f[$col_h]  ?? 0);
    $nom = $f[$col_nom] ?? '';
    $dom = $f[$col_dom] ?? 'Autre';

    // Alertes tarifs
    $alertes = [];
    if ($ol > 0 && $ol < 200000) $alertes[] = "OL={$ol} < 200K";
    if ($pr > 0 && $pr < 250000) $alertes[] = "PR={$pr} < 250K";
    if ($h > 0 && $h <= 30 && ($ol >= 450000 || $pr >= 450000)) $alertes[] = "Prix>=450K pour {$h}h seulement";
    if ($ol > 0 && $pr > 0 && $pr < $ol) $alertes[] = "Présentiel<EnLigne";

    if ($alertes) {
        $alertes_tarif[] = ['id'=>$f['id']??0,'nom'=>$nom,'domaine'=>$dom,'h'=>$h,'ol'=>$ol,'pr'=>$pr,'alertes'=>$alertes];
        $stats['tarif_ko']++;
    }

    // Formations groupées
    $raison = '';
    if (preg_match('/\d\s*(formations?|modules?)\s*(en|dans)\s*(1|un)/ui', $nom)) $raison = 'N-en-1';
    elseif (preg_match('/pack\s+(de\s+)?\d/ui', $nom)) $raison = 'Pack N';
    elseif (substr_count($nom, ' + ') >= 2) $raison = 'Triple-plus';
    elseif (preg_match('/\b(combo|bundle)\b/ui', $nom)) $raison = 'Combo/Bundle';
    elseif (preg_match('/formation\s+\d+\s*[+&]\s*\d+/ui', $nom)) $raison = 'N+M groupées';
    if ($raison) $groupees[] = ['id'=>$f['id']??0,'nom'=>$nom,'domaine'=>$dom,'ol'=>$ol,'pr'=>$pr,'h'=>$h,'raison'=>$raison];

    // Index pour doublons
    $noms_by_domaine[$dom][] = ['id'=>$f['id']??0,'nom'=>$nom,'ol'=>$ol,'pr'=>$pr,'h'=>$h];
}

// Doublons similaires
$doublons = [];
foreach ($noms_by_domaine as $dom => $items) {
    for ($i = 0; $i < count($items); $i++) {
        for ($j = $i+1; $j < count($items); $j++) {
            $a = preg_replace('/[^a-z0-9]/ui', '', mb_strtolower($items[$i]['nom']));
            $b = preg_replace('/[^a-z0-9]/ui', '', mb_strtolower($items[$j]['nom']));
            if (strlen($a) < 5 || strlen($b) < 5) continue;
            similar_text($a, $b, $pct);
            if ($pct > 78) {
                $doublons[] = ['dom'=>$dom,'pct'=>round($pct,1),'a'=>$items[$i],'b'=>$items[$j]];
            }
        }
    }
}
usort($doublons, fn($x,$y) => $y['pct'] <=> $x['pct']);

echo json_encode([
    'colonnes'   => $cols,
    'stats'      => $stats,
    'alertes_tarif' => $alertes_tarif,
    'groupees'   => $groupees,
    'doublons'   => array_slice($doublons, 0, 80),
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
