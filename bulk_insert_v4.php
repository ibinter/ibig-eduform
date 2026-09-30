<?php
if (($_GET['k'] ?? '') !== 'ibig-bulk4-2026') { http_response_code(403); exit('Forbidden'); }
header('Content-Type: text/plain; charset=utf-8');
require_once __DIR__ . '/core/config.php';
require_once __DIR__ . '/core/database.php';
$pdo = Database::connect();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function slugify4(string $t): string {
    $t = mb_strtolower(trim($t), 'UTF-8');
    $map = ['à'=>'a','â'=>'a','é'=>'e','è'=>'e','ê'=>'e','ë'=>'e','î'=>'i','ï'=>'i','ô'=>'o','û'=>'u','ù'=>'u','ü'=>'u','ç'=>'c','œ'=>'oe'];
    $t = strtr($t, $map);
    $t = preg_replace('/[^a-z0-9]+/', '-', $t);
    return trim((string)$t, '-');
}

$formations = [
    ['Gestion des associations et fondations','Direction & Administration',20,225000,275000],
    ['Intelligence économique et veille concurrentielle','Management & Leadership',20,225000,275000],
    ['Gestion de la marque employeur en Afrique','GRH',20,225000,275000],
];

$existingTitres = array_flip($pdo->query("SELECT LOWER(TRIM(titre)) FROM formations")->fetchAll(PDO::FETCH_COLUMN));
$existingSlugs  = array_flip($pdo->query("SELECT slug FROM formations")->fetchAll(PDO::FETCH_COLUMN));

$ins = $pdo->prepare("INSERT INTO formations (titre,slug,domaine,type_certificat,description,duree,mode,tarif_presentiel,tarif_en_ligne,tarif_hybride,frais_inscription,annee,statut,created_at,updated_at) VALUES (:titre,:slug,:domaine,'Certificat professionnel',:description,:duree,'hybride',:tp,:tel,:th,50000,0,'active',NOW(),NOW())");

$inserted = 0;
foreach ($formations as $f) {
    [$titre, $domaine, $duree, $tel, $tp] = $f;
    if (isset($existingTitres[strtolower(trim($titre))])) continue;
    $slug = slugify4($titre); $base=$slug; $i=1;
    while (isset($existingSlugs[$slug])) $slug=$base.'-'.$i++;
    $ins->execute([':titre'=>$titre,':slug'=>$slug,':domaine'=>$domaine,':description'=>'Formation professionnelle certifiante IBIG EDUFORM — '.$titre.'. Reconnue dans les 17 pays de l\'espace OHADA.',':duree'=>$duree.'h',':tel'=>$tel,':tp'=>$tp,':th'=>(int)(($tel+$tp)/2)]);
    $existingTitres[strtolower(trim($titre))] = true;
    $existingSlugs[$slug] = true;
    $inserted++;
}

$totalAll = (int)$pdo->query("SELECT COUNT(*) FROM formations WHERE statut='active'")->fetchColumn();
echo "Insérées : $inserted\nTotal MySQL : $totalAll\nPublic attendu : ".($totalAll*2)."\n✅ Supprimé.\n";
@unlink(__FILE__);
