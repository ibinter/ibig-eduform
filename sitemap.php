<?php
header('Content-Type: application/xml; charset=utf-8');
header('X-Robots-Tag: noindex');

$BASE = 'https://ibig-eduform.com';
$today = date('Y-m-d');

// Pages statiques
$pages = [
    ['/', '1.0', 'daily'],
    ['/catalogue-formations.php', '1.0', 'daily'],
    ['/formations.php', '0.9', 'weekly'],
    ['/formations-samedi-pro.php', '0.9', 'weekly'],
    ['/preinscription-generale.php', '0.8', 'weekly'],
    ['/preinscription-samedi-pro.php', '0.8', 'weekly'],
    ['/preinscription.php', '0.8', 'weekly'],
    ['/besoin-formation.php', '0.7', 'monthly'],
    ['/contact.php', '0.6', 'monthly'],
    ['/a-propos.php', '0.6', 'monthly'],
    ['/entreprises.php', '0.7', 'monthly'],
    ['/partenaires.php', '0.6', 'monthly'],
];

// Formations locales depuis la DB
$formation_urls = [];
try {
    require_once __DIR__ . '/core/config.php';
    require_once __DIR__ . '/core/database.php';
    $pdo = Database::connect();
    $rows = $pdo->query("SELECT slug FROM formations WHERE statut='active' AND slug != '' ORDER BY updated_at DESC")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        if (!empty($r['slug'])) {
            $formation_urls[] = '/formation/' . rawurlencode($r['slug']);
        }
    }
} catch (Throwable $e) { /* silencieux */ }

// Catégories du catalogue
$categories = [
    'Agriculture','Banque & Assurance','Beauté & Bien-être','BTP & Construction',
    'Communication Professionnelle','Comptabilité & Finance','Direction & Administration',
    'Droit & Juridique','Développement Personnel','Entrepreneuriat','GRH',
    'Gestion Commerciale & Marketing','IA & Digitalisation','Immobilier',
    'Informatique & Tech','Logistique & Supply Chain','Management & Leadership',
    'Mines, Énergie & Pétrole','QHSE','Santé & Pharmacie','Tourisme & Hôtellerie',
];

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

foreach ($pages as [$loc, $prio, $freq]) {
    // Vérifier si la page existe
    $file = __DIR__ . str_replace('/', DIRECTORY_SEPARATOR, $loc === '/' ? '/index.php' : $loc);
    if ($loc !== '/' && !file_exists($file)) continue;
    echo "  <url>\n";
    echo "    <loc>" . htmlspecialchars($BASE . $loc) . "</loc>\n";
    echo "    <lastmod>$today</lastmod>\n";
    echo "    <changefreq>$freq</changefreq>\n";
    echo "    <priority>$prio</priority>\n";
    echo "  </url>\n";
}

// Catalogue par catégorie
foreach ($categories as $cat) {
    echo "  <url>\n";
    echo "    <loc>" . htmlspecialchars($BASE . '/catalogue-formations.php?cat=' . rawurlencode($cat)) . "</loc>\n";
    echo "    <lastmod>$today</lastmod>\n";
    echo "    <changefreq>weekly</changefreq>\n";
    echo "    <priority>0.8</priority>\n";
    echo "  </url>\n";
}

// Formations individuelles
foreach ($formation_urls as $url) {
    echo "  <url>\n";
    echo "    <loc>" . htmlspecialchars($BASE . $url) . "</loc>\n";
    echo "    <lastmod>$today</lastmod>\n";
    echo "    <changefreq>monthly</changefreq>\n";
    echo "    <priority>0.7</priority>\n";
    echo "  </url>\n";
}

echo '</urlset>';
