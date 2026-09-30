<?php
declare(strict_types=1);
/**
 * SYNC EDUFORM → IBIG PARTNERS
 * Endpoint : GET/POST /api/sync-to-partners.php?key=SECRET
 * Appelé par pseudo_cron toutes les heures.
 */

require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/database.php';

/* ── Clé d'accès ─────────────────────────────────────────────── */
$expectedKey = defined('PARTNERS_REGISTER_SECRET') ? substr(PARTNERS_REGISTER_SECRET, 0, 16) : '';
$providedKey = $_GET['key'] ?? $_SERVER['HTTP_X_SYNC_KEY'] ?? '';
if ($expectedKey === '' || $providedKey !== $expectedKey) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Accès refusé']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

/* ── Constantes Supabase ─────────────────────────────────────── */
$supabaseUrl = defined('SUPABASE_URL') ? SUPABASE_URL : '';
$supabaseKey = defined('SUPABASE_SERVICE_KEY') ? SUPABASE_SERVICE_KEY : '';

if ($supabaseUrl === '' || $supabaseKey === '') {
    echo json_encode(['ok' => false, 'error' => 'SUPABASE_URL ou SUPABASE_SERVICE_KEY manquant']);
    exit;
}

/* ── Rate-limit : 1 sync max toutes les 50 min ──────────────── */
$force    = ($_GET['force'] ?? '0') === '1';
$lockFile = sys_get_temp_dir() . '/ibig_sync_partners.lock';
if (!$force && file_exists($lockFile) && (time() - (int)file_get_contents($lockFile)) < 3000) {
    echo json_encode(['ok' => true, 'skipped' => true, 'reason' => 'Trop récent']);
    exit;
}
file_put_contents($lockFile, (string)time());

/* ── Génère un ID déterministe (même slug = même id) ─────────── */
function det_id(string $seed): string {
    return 'cl' . substr(md5('ibig-' . $seed), 0, 24);
}

/* ── Helper Supabase REST ────────────────────────────────────── */
function supabase(string $method, string $path, array $data = []): array
{
    global $supabaseUrl, $supabaseKey;
    $ch = curl_init(rtrim($supabaseUrl, '/') . '/rest/v1/' . $path);
    $headers = [
        'apikey: ' . $supabaseKey,
        'Authorization: Bearer ' . $supabaseKey,
        'Content-Type: application/json',
        'Prefer: resolution=merge-duplicates,return=minimal',
    ];
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    if ($data) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    $body   = (string)curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err    = curl_error($ch);
    curl_close($ch);
    return ['status' => $status, 'body' => json_decode($body, true), 'err' => $err];
}

/* ── 1. Branche "eduform" — upsert si inexistante ───────────── */
$branchData = [[
    'id'             => det_id('branch-eduform'),
    'slug'           => 'eduform',
    'name'           => 'IBIG EDUFORM',
    'tagline'        => 'Formations professionnelles certifiantes',
    'description'    => 'Formations courtes, longues et certifiantes en présentiel et distanciel en Afrique francophone.',
    'offerType'      => 'Cours / Formation',
    'commissionModel'=> '10/5/2%',
    'active'         => true,
    'order'          => 1,
    'website'        => 'https://ibig-eduform.com',
]];
$brUpsert = supabase('POST', 'Branch?on_conflict=slug', $branchData);
if (!in_array($brUpsert['status'], [200, 201], true)) {
    echo json_encode(['ok' => false, 'error' => 'Impossible de créer la branche', 'debug' => $brUpsert]);
    exit;
}

// Récupérer l'ID
$br = supabase('GET', 'Branch?slug=eq.eduform&select=id');
if (($br['status'] !== 200) || empty($br['body'][0]['id'])) {
    echo json_encode(['ok' => false, 'error' => 'Branche eduform introuvable après upsert', 'debug' => $br]);
    exit;
}
$branchId = $br['body'][0]['id'];

/* ── 2. Formations actives EDUFORM ──────────────────────────── */
$pdo  = Database::connect();
$rows = $pdo->query("
    SELECT f.id, f.titre, f.slug, f.tarif_en_ligne, f.tarif_presentiel,
           fl.pitch, fl.seo_description
    FROM formations f
    LEFT JOIN formation_landings fl ON fl.formation_id = f.id
    WHERE f.statut = 'active'
      AND (
        (f.annee IS NULL OR f.annee = 0)
        OR (f.date_fin IS NULL OR f.date_fin >= CURDATE() OR f.date_debut IS NULL)
      )
    ORDER BY f.titre ASC
")->fetchAll(PDO::FETCH_ASSOC);

/* ── 3. Upsert dans Supabase ─────────────────────────────────── */
$appUrl = defined('APP_URL') ? rtrim(APP_URL, '/') : 'https://ibig-eduform.com';
$synced = 0;
$errors = [];
$activeSlugs = [];

foreach ($rows as $r) {
    $slug = 'eduform-' . ($r['slug'] ?: preg_replace('/[^a-z0-9]+/', '-', strtolower((string)$r['titre'])));
    $activeSlugs[] = $slug;

    $product = [
        'id'          => det_id('product-' . $slug),
        'branchId'    => $branchId,
        'slug'        => $slug,
        'name'        => (string)$r['titre'],
        'description' => (string)($r['pitch'] ?: $r['seo_description'] ?: ''),
        'price'       => (int)($r['tarif_en_ligne'] ?: 200000),
        'pricingType' => 'COURSE',
        'rate'        => 10,
        'siteUrl'     => $appUrl . '/formation/' . rawurlencode((string)($r['slug'] ?? '')),
        'active'      => true,
    ];

    $res = supabase('POST', 'Product?on_conflict=slug', [$product]);
    if (in_array($res['status'], [200, 201], true)) {
        $synced++;
    } else {
        $errors[] = ['slug' => $slug, 'status' => $res['status'], 'body' => $res['body']];
    }
}

/* ── 4. Désactiver les formations supprimées ─────────────────── */
$existing = supabase('GET', 'Product?branchId=eq.' . $branchId . '&active=eq.true&select=id,slug');
if ($existing['status'] === 200 && is_array($existing['body'])) {
    foreach ($existing['body'] as $ep) {
        if (!in_array($ep['slug'], $activeSlugs, true)) {
            supabase('PATCH', 'Product?id=eq.' . $ep['id'], ['active' => false]);
        }
    }
}

echo json_encode([
    'ok'        => empty($errors),
    'total'     => count($rows),
    'synced'    => $synced,
    'errors'    => count($errors),
    'error_list'=> $errors,
    'synced_at' => date('c'),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
