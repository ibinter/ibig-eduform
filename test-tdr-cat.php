<?php
// Test rapide — à supprimer après test
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/core/config.php';
require_once __DIR__ . '/core/tdr_generator.php';

$slug = 'eduform-accueil-vip-service-excellence';

$cacheFile = sys_get_temp_dir() . '/ibig_catalogue_cache.json';
$formations = [];
if (file_exists($cacheFile) && (time()-filemtime($cacheFile))<600) {
    $d = json_decode(file_get_contents($cacheFile),true);
    $formations = $d['formations'] ?? [];
} else {
    $json = @file_get_contents('https://www.ibigpartners.com/api/catalogue');
    if($json){ $d=json_decode($json,true); if(!empty($d['ok'])){ $formations=$d['formations']; file_put_contents($cacheFile,$json); } }
}
$found=null;
foreach($formations as $f){ if(($f['slug']??'')===$slug){$found=$f;break;} }
if(!$found){ die('Formation non trouvée'); }

$formation=['name'=>$found['name'],'category'=>$found['category']??'','slug'=>$found['slug'],'price'=>(int)($found['price']??0),'description'=>$found['description']??''];
$opts=['mode_formation'=>'en_ligne','format_formation'=>'individuel','date_debut'=>'','creneau'=>''];

$html = generate_tdr_html($formation, '', $opts);
echo '<pre style="font-size:11px">SLUG: '.$slug."\nNOM: ".$found['name']."\nHTML length: ".strlen($html)." chars\n\nDébut HTML:\n".htmlspecialchars(substr($html,0,500)).'</pre>';
