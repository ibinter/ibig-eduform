<?php
declare(strict_types=1);
/**
 * FIX TEMPORAIRE — remet annee=0 pour les formations non-Samedi-Pro
 * qui ont été insérées en mode programmation par erreur.
 * Accès protégé /outils/
 */
require_once __DIR__ . '/../core/secrets.php';

$pdo = new PDO(
    'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4',
    DB_USER, DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$action = $_GET['action'] ?? 'list';

// Formations non-SamediPro avec annee != 0
$rows = $pdo->query("
    SELECT id, titre, slug, annee, date_debut, statut, is_samedi_pro
    FROM formations
    WHERE is_samedi_pro = 0
      AND (annee != 0 AND annee IS NOT NULL)
    ORDER BY id DESC
")->fetchAll(PDO::FETCH_ASSOC);

if ($action === 'fix') {
    $ids = array_column($rows, 'id');
    if ($ids) {
        $in = implode(',', array_map('intval', $ids));
        $pdo->exec("UPDATE formations SET annee = 0, date_debut = NULL WHERE id IN ($in) AND is_samedi_pro = 0");
        echo json_encode(['ok'=>true,'fixed'=>count($ids),'ids'=>$ids]);
    } else {
        echo json_encode(['ok'=>true,'fixed'=>0]);
    }
    exit;
}

// Liste
header('Content-Type: text/html; charset=utf-8');
echo '<h2>Formations non-SamediPro avec annee != 0 ('.count($rows).')</h2>';
echo '<table border=1 cellpadding=4><tr><th>ID</th><th>Titre</th><th>Slug</th><th>Annee</th><th>Date debut</th><th>Statut</th></tr>';
foreach ($rows as $r) {
    echo '<tr><td>'.$r['id'].'</td><td>'.htmlspecialchars($r['titre']).'</td><td>'.$r['slug'].'</td><td>'.$r['annee'].'</td><td>'.$r['date_debut'].'</td><td>'.$r['statut'].'</td></tr>';
}
echo '</table>';
echo '<br><a href="?action=fix"><strong style="color:red">▶ CORRIGER : mettre annee=0 et date_debut=NULL pour toutes ces formations</strong></a>';
