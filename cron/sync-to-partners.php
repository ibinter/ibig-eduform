<?php
declare(strict_types=1);
/**
 * SYNC EDUFORM → IBIG PARTNERS
 * ─────────────────────────────────────────────────────────────
 * Pousse toutes les formations actives EDUFORM (MySQL) vers la
 * table Product de Supabase (PostgreSQL) via l'API REST PostgREST.
 *
 * Appelé :
 *  - par pseudo_cron (footer.php) toutes les heures
 *  - manuellement via admin/sync-partners.php
 *
 * Variables à définir dans core/secrets.php :
 *   SUPABASE_URL          https://xxxx.supabase.co
 *   SUPABASE_SERVICE_KEY  eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
 */

require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/database.php';

/* ── Constantes Supabase ─────────────────────────────────────── */
$supabaseUrl = defined('SUPABASE_URL') ? SUPABASE_URL : '';
$supabaseKey = defined('SUPABASE_SERVICE_KEY') ? SUPABASE_SERVICE_KEY : '';

if ($supabaseUrl === '' || $supabaseKey === '') {
    echo json_encode(['ok' => false, 'error' => 'SUPABASE_URL ou SUPABASE_SERVICE_KEY non défini dans secrets.php']);
    exit;
}

/* ── Rate-limit : 1 sync max toutes les 50 minutes ──────────── */
$lockKey  = 'sync_partners_last_run';
$lockFile = sys_get_temp_dir() . '/ibig_sync_partners.lock';
if (file_exists($lockFile) && (time() - (int)file_get_contents($lockFile)) < 3000) {
    echo json_encode(['ok' => true, 'skipped' => true, 'reason' => 'Trop récent']);
    exit;
}
file_put_contents($lockFile, (string)time());

/* ── Helpers Supabase REST ───────────────────────────────────── */
function supabase_request(string $method, string $table, array $data = [], array $params = []): array
{
    global $supabaseUrl, $supabaseKey;

    $url = rtrim($supabaseUrl, '/') . '/rest/v1/' . $table;
    if ($params) {
        $url .= '?' . http_build_query($params);
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_HTTPHEADER     => [
            'apikey: ' . $supabaseKey,
            'Authorization: Bearer ' . $supabaseKey,
            'Content-Type: application/json',
            'Prefer: resolution=merge-duplicates,return=representation',
        ],
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    if ($data) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    $body   = (string)curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err    = curl_error($ch);
    curl_close($ch);

    $decoded = json_decode($body, true);
    return ['status' => $status, 'body' => $decoded, 'error' => $err];
}

/* ── 1. Récupérer l'ID de la branche "eduform" ──────────────── */
$branchRes = supabase_request('GET', 'Branch', [], ['slug' => 'eq.eduform', 'select' => 'id,slug']);
if (($branchRes['status'] !== 200) || empty($branchRes['body'][0]['id'])) {
    echo json_encode([
        'ok'    => false,
        'error' => 'Branche "eduform" introuvable dans Supabase',
        'debug' => $branchRes,
    ]);
    exit;
}
$branchId = $branchRes['body'][0]['id'];

/* ── 2. Lire les formations actives depuis EDUFORM MySQL ─────── */
$pdo = Database::connect();
$rows = $pdo->query("
    SELECT
        f.id, f.titre, f.slug, f.domaine,
        f.tarif_en_ligne, f.tarif_presentiel,
        f.duree, f.is_samedi_pro,
        fl.pitch, fl.seo_description, fl.hero_image
    FROM formations f
    LEFT JOIN formation_landings fl ON fl.formation_id = f.id
    WHERE f.statut = 'active'
      AND (f.date_fin IS NULL OR f.date_fin >= CURDATE() OR f.date_debut IS NULL)
    ORDER BY f.titre ASC
")->fetchAll(PDO::FETCH_ASSOC);

/* ── 3. Upsert chaque formation dans Supabase ────────────────── */
$appUrl  = defined('APP_URL') ? rtrim(APP_URL, '/') : 'https://ibig-eduform.com';
$synced  = 0;
$errors  = [];

foreach ($rows as $r) {
    $slug      = 'eduform-' . ($r['slug'] ?: strtolower(preg_replace('/[^a-z0-9]+/', '-', strtolower($r['titre']))));
    $price     = (int)($r['tarif_en_ligne'] ?: 200000);
    $isSam     = !empty($r['is_samedi_pro']);
    $desc      = (string)($r['pitch'] ?: $r['seo_description'] ?: '');
    $siteUrl   = $appUrl . '/formation/' . rawurlencode((string)($r['slug'] ?? ''));

    $product = [
        'branchId'    => $branchId,
        'slug'        => $slug,
        'name'        => (string)$r['titre'],
        'description' => $desc ?: null,
        'price'       => $price,
        'pricingType' => 'COURSE',
        'rate'        => 10,   // 10% commission N1 pour les formations
        'siteUrl'     => $siteUrl,
        'active'      => true,
    ];

    // Upsert via POST avec Prefer: resolution=merge-duplicates
    $res = supabase_request('POST', 'Product?on_conflict=slug', [$product]);

    if (in_array($res['status'], [200, 201], true)) {
        $synced++;
    } else {
        $errors[] = [
            'slug'   => $slug,
            'status' => $res['status'],
            'body'   => $res['body'],
        ];
    }
}

/* ── 4. Désactiver dans Supabase les formations retirées ─────── */
// Récupérer tous les slugs actifs qu'on vient de sync
$activeSlugs = array_map(function ($r) {
    return 'eduform-' . ($r['slug'] ?: strtolower(preg_replace('/[^a-z0-9]+/', '-', strtolower($r['titre']))));
}, $rows);

// Récupérer les produits eduform existants dans Supabase
$existingRes = supabase_request('GET', 'Product', [], [
    'branchId' => 'eq.' . $branchId,
    'select'   => 'id,slug,active',
]);
if ($existingRes['status'] === 200 && is_array($existingRes['body'])) {
    foreach ($existingRes['body'] as $ep) {
        if ($ep['active'] && !in_array($ep['slug'], $activeSlugs, true)) {
            // Désactiver sans supprimer (historique des ventes préservé)
            supabase_request('PATCH', 'Product?id=eq.' . $ep['id'], ['active' => false]);
        }
    }
}

/* ── 5. Résultat ─────────────────────────────────────────────── */
$result = [
    'ok'         => count($errors) === 0,
    'total'      => count($rows),
    'synced'     => $synced,
    'errors'     => count($errors),
    'error_list' => $errors,
    'synced_at'  => date('c'),
];

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
