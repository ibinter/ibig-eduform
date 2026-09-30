<?php
declare(strict_types=1);
/**
 * IBIG EDUFORM — API JSON publique des formations actives.
 * Consommée par IBIG PARTNERS pour afficher le catalogue.
 * CORS autorisé pour ibigpartners.com / ibig-partners.vercel.app.
 */

/* ---------- CORS ---------- */
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowed = ['https://www.ibigpartners.com', 'https://ibigpartners.com', 'https://ibig-partners.vercel.app'];
if (in_array($origin, $allowed, true)) {
    header("Access-Control-Allow-Origin: {$origin}");
} else {
    header('Access-Control-Allow-Origin: *'); // fallback permissif (données publiques)
}
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=900'); // 15 min cache côté CDN

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/database.php';

try {
    $pdo = Database::connect();

    $rows = $pdo->query("
        SELECT
            f.id, f.titre AS titre, f.slug, f.domaine, f.type_certificat AS type,
            f.duree, f.is_samedi_pro,
            f.tarif_en_ligne, f.tarif_presentiel, f.frais_inscription,
            f.date_debut, f.date_fin,
            fl.hero_image, fl.pitch, fl.seo_description
        FROM formations f
        LEFT JOIN formation_landings fl ON fl.formation_id = f.id
        WHERE f.statut = 'active'
          AND (
            f.date_fin IS NULL
            OR f.date_fin >= CURDATE()
            OR f.date_debut IS NULL
          )
        ORDER BY f.date_debut ASC, f.titre ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    $appUrl = defined('APP_URL') ? rtrim(APP_URL, '/') : 'https://ibig-eduform.com';

    $formations = array_map(function (array $r) use ($appUrl): array {
        $tel   = (int)$r['tarif_en_ligne'];
        $tp    = (int)$r['tarif_presentiel'];
        $isSam = !empty($r['is_samedi_pro']);

        /* Grille tarifaire multi-modalités */
        $grille = [];
        if ($tel > 0) {
            $grille['elearning']   = null;            // e-learning asynchrone : N/A pour les certifs (présentiel/live)
            $grille['individuel']  = $tel;
            $grille['groupe_3_5']  = (int)round($tel * 0.87); // −13% / pers
            $grille['groupe_6_10'] = (int)round($tel * 0.80); // −20% / pers
            $grille['groupe_10p']  = (int)round($tel * 0.75); // −25% / pers
            $grille['presentiel']  = $tp > 0 ? $tp : null;
            $grille['intra']       = 'Sur devis';
        }

        /* URL fiche & image */
        $slug = (string)($r['slug'] ?? '');
        $url  = $slug !== '' ? $appUrl . '/formation/' . rawurlencode($slug) : $appUrl . '/formation.php?id=' . (int)$r['id'];
        $img  = !empty($r['hero_image']) ? $appUrl . '/' . ltrim((string)$r['hero_image'], '/') : null;

        return [
            'id'              => (int)$r['id'],
            'titre'           => (string)$r['titre'],
            'slug'            => $slug,
            'url'             => $url,
            'domaine'         => (string)($r['domaine'] ?? ''),
            'type'            => $isSam ? 'Samedi Pro' : 'Formation Certifiante',
            'duree'           => (string)($r['duree'] ?? ''),
            'pitch'           => (string)($r['pitch'] ?? $r['seo_description'] ?? ''),
            'image'           => $img,
            'date_debut'      => $r['date_debut'],
            'tarif_en_ligne'  => $tel ?: null,
            'tarif_presentiel'=> $tp ?: null,
            'frais_inscription'=> (int)($r['frais_inscription'] ?? 50000),
            'grille'          => $grille,
        ];
    }, $rows);

    echo json_encode([
        'ok'         => true,
        'total'      => count($formations),
        'updated_at' => date('c'),
        'source'     => $appUrl,
        'formations' => $formations,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Erreur serveur']);
}
