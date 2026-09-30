<?php
declare(strict_types=1);
/**
 * IBIG EDUFORM — /outils/tdr-catalogue-search.php
 * Endpoint interne : recherche dans le catalogue IBIG PARTNERS et retourne
 * les données d'une formation pour pré-remplir le générateur TDR.
 * Accessible uniquement depuis /outils/ (protégé par .htpasswd).
 */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=300'); // cache 5 min

$q    = trim($_GET['q'] ?? '');
$slug = trim($_GET['slug'] ?? '');

if (!$q && !$slug) {
    echo json_encode(['ok' => false, 'error' => 'Paramètre q ou slug requis']);
    exit;
}

/* ── Récupère le catalogue (avec cache fichier 10 min) ── */
$cacheFile = sys_get_temp_dir() . '/ibig_catalogue_cache.json';
$formations = [];

if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 600) {
    $cached = json_decode(file_get_contents($cacheFile), true);
    $formations = $cached['formations'] ?? [];
} else {
    $ctx = stream_context_create([
        'http' => ['timeout' => 8, 'ignore_errors' => true, 'method' => 'GET',
                   'header'  => "Accept: application/json\r\n"],
        'ssl'  => ['verify_peer' => true],
    ]);
    $json = @file_get_contents('https://www.ibigpartners.com/api/catalogue', false, $ctx);
    if ($json) {
        $data = json_decode($json, true);
        if (!empty($data['ok']) && isset($data['formations'])) {
            $formations = $data['formations'];
            file_put_contents($cacheFile, $json);
        }
    }
}

/* ── Formations locales (base IBIG EDUFORM) ── */
try {
    require_once __DIR__ . '/../core/database.php';
    $pdo_loc = Database::connect();
    $local_rows = $pdo_loc->query("
        SELECT id, titre AS name, slug, domaine AS category,
               GREATEST(COALESCE(tarif_en_ligne,0),200000) AS price,
               GREATEST(COALESCE(tarif_presentiel,0),250000) AS prix_presentiel,
               duree, description
        FROM formations
        WHERE statut = 'active'
          AND (annee = 0 OR annee IS NULL OR annee = YEAR(CURDATE()))
          AND titre NOT LIKE 'Tarif Groupe%'
          AND is_samedi_pro = 0
        ORDER BY titre ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
    /* Ajouter les locales en dédupliquant par slug */
    $api_slugs = array_column($formations, null, 'slug');
    foreach ($local_rows as $lr) {
        if (!isset($api_slugs[$lr['slug']])) {
            $formations[] = [
                'id'          => 'local_' . $lr['id'],
                'name'        => $lr['name'],
                'slug'        => $lr['slug'],
                'category'    => $lr['category'],
                'description' => $lr['description'] ?? '',
                'price'       => (int)$lr['price'],
                'prix_presentiel' => (int)$lr['prix_presentiel'],
                'duree'       => $lr['duree'] ?? '',
                'siteUrl'     => '/formation/' . $lr['slug'],
                '_local'      => true,
            ];
        }
    }
} catch (Throwable $_e) { /* silencieux si DB indisponible */ }

if (empty($formations)) {
    echo json_encode(['ok' => false, 'error' => 'API catalogue indisponible']);
    exit;
}

/* ── Mode slug : retourne UNE formation complète pour pré-remplissage ── */
if ($slug) {
    $found = null;
    foreach ($formations as $f) {
        if ($f['slug'] === $slug) { $found = $f; break; }
    }
    if (!$found) {
        echo json_encode(['ok' => false, 'error' => 'Formation non trouvée']);
        exit;
    }

    /* ── Construit les données TDR à partir de la formation ── */
    $grille = $found['grille'] ?? [];
    $prix_en_ligne   = max((int)($grille['individuel_online'] ?? 0), (int)($found['price'] ?? 0));
    $prix_presentiel = (int)($grille['individuel_pres'] ?? $found['prix_presentiel'] ?? 0);
    if ($prix_presentiel < $prix_en_ligne) $prix_presentiel = (int)round($prix_en_ligne * 1.25 / 5000) * 5000;

    /* Durée : depuis le champ duree si local, sinon estimée depuis le prix */
    $duree_h = 25; // défaut
    if (!empty($found['duree']) && preg_match('/(\d+)/', (string)$found['duree'], $dm)) {
        $duree_h = (int)$dm[1];
    } else {
        $tarif_ref = [20=>225000,25=>280000,28=>315000,30=>340000,35=>395000,40=>450000,45=>505000,55=>620000,65=>730000,72=>810000,80=>900000];
        foreach ($tarif_ref as $h => $t) {
            if ($prix_en_ligne <= $t) { $duree_h = $h; break; }
        }
    }

    echo json_encode([
        'ok'       => true,
        'formation' => [
            'id'          => $found['id'],
            'name'        => $found['name'],
            'slug'        => $found['slug'],
            'description' => $found['description'] ?? '',
            'category'    => $found['category'] ?? '',
            'siteUrl'     => $found['siteUrl'] ?? '',
            'prix_en_ligne'   => $prix_en_ligne,
            'prix_presentiel' => $prix_presentiel,
            'duree_h'     => $duree_h,
            'grille'      => $grille,
        ],
    ]);
    exit;
}

/* ── Mode recherche : retourne jusqu'à 12 suggestions ── */
/* Normalise les accents pour permettre la recherche sans accent */
$normalize = function(string $s): string {
    $s = mb_strtolower($s, 'UTF-8');
    $from = ['à','â','ä','á','ã','å','æ','ç','è','é','ê','ë','ì','í','î','ï','ñ','ò','ó','ô','õ','ö','ù','ú','û','ü','ý','ÿ'];
    $to   = ['a','a','a','a','a','a','ae','c','e','e','e','e','i','i','i','i','n','o','o','o','o','o','u','u','u','u','y','y'];
    return str_replace($from, $to, $s);
};

$needle = $normalize($q);

/* ── Score de pertinence : titre exact > titre commence par > titre contient > reste ── */
$scored = [];
$seen_names = []; // déduplique par nom normalisé
foreach ($formations as $f) {
    $name_norm = $normalize((string)($f['name'] ?? ''));
    if (isset($seen_names[$name_norm])) continue; // ignorer doublon
    $seen_names[$name_norm] = true;

    $title_norm = $name_norm;
    $haystack   = $title_norm . ' ' . $normalize(($f['category'] ?? '') . ' ' . ($f['description'] ?? ''));
    if (mb_strpos($haystack, $needle) === false) continue;

    // Score : 3 = titre commence par needle, 2 = titre contient needle, 1 = autre champ
    if (mb_strpos($title_norm, $needle) === 0)       $score = 3;
    elseif (mb_strpos($title_norm, $needle) !== false) $score = 2;
    else                                               $score = 1;

    $scored[] = [
        'name'     => $f['name'],
        'slug'     => $f['slug'],
        'category' => $f['category'] ?? '',
        'price'    => $f['price'] ?? 0,
        '_score'   => $score,
    ];
}

// Trier par score décroissant puis titre croissant
usort($scored, fn($a,$b) => $b['_score'] <=> $a['_score'] ?: strcmp($a['name'], $b['name']));

// Retourner les 20 meilleurs résultats, sans le champ interne _score
$results = array_map(fn($r) => ['name'=>$r['name'],'slug'=>$r['slug'],'category'=>$r['category'],'price'=>$r['price']], array_slice($scored, 0, 20));

echo json_encode(['ok' => true, 'results' => $results, 'total' => count($results)]);
