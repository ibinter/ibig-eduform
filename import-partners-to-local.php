<?php
declare(strict_types=1);
/* =========================================================
   IBIG EDUFORM — Import formations IBIG Partners → base locale
   Usage : exécuter UNE FOIS via navigateur, puis supprimer.
   Ne touche JAMAIS aux formations is_samedi_pro=1.
   Ne modifie JAMAIS les formations existantes.
   Insère uniquement les titres absents de la base locale.
========================================================= */
require_once __DIR__ . '/core/config.php';
require_once __DIR__ . '/core/database.php';

header('Content-Type: application/json; charset=utf-8');
set_time_limit(120);

/* ── Helpers ── */
function make_slug_imp(string $titre): string {
    $s = mb_strtolower(trim($titre), 'UTF-8');
    $s = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s) ?: $s;
    $s = str_replace('&', '', $s);
    $s = preg_replace('/[^a-z0-9\s\-]/', '', $s);
    $s = preg_replace('/[\s\-]+/', '-', $s);
    return trim($s, '-');
}

function normalize_imp(string $s): string {
    return mb_strtolower(trim(preg_replace('/[\s\-–—_]+/', ' ', $s)), 'UTF-8');
}

/* ── Fetch API Partners ── */
$url = 'https://www.ibigpartners.com/api/catalogue';
$ctx = stream_context_create(['http' => ['timeout' => 15, 'method' => 'GET',
    'header' => "Accept: application/json\r\n"], 'ssl' => ['verify_peer' => true]]);
$json = @file_get_contents($url, false, $ctx);
if (!$json) {
    echo json_encode(['ok' => false, 'error' => 'API Partners inaccessible']);
    exit;
}
$data = json_decode($json, true);
if (!is_array($data) || empty($data['ok']) || !isset($data['formations'])) {
    echo json_encode(['ok' => false, 'error' => 'Réponse API invalide']);
    exit;
}
$api_formations = $data['formations'];

/* ── Catégorie Partners → domaine IBIG EDUFORM ── */
$CAT_TO_DOMAINE = [
    'Agriculture'                   => 'Agriculture',
    'Banque & Assurance'            => 'Banque & Assurance',
    'Beauté & Bien-être'            => 'Développement Personnel',
    'BTP & Construction'            => 'BTP & Construction',
    'Communication'                 => 'Communication',
    'Communication Professionnelle' => 'Communication',
    'Comptabilité & Finance'        => 'Comptabilité & Finance',
    'Création de Contenu'           => 'Communication',
    'Direction & Administration'    => 'Direction & Management',
    'Droit & Juridique'             => 'Droit & Fiscalité',
    'Développement Personnel'       => 'Développement Personnel',
    'Entrepreneuriat'               => 'Entrepreneuriat',
    'Éducation & Formation'         => 'Formation & Pédagogie',
    'GRH'                           => 'Ressources Humaines',
    'Gestion Commerciale & Marketing' => 'Gestion Commerciale & Marketing',
    'IA & Digitalisation'           => 'Informatique & Digital',
    'Immobilier'                    => 'Immobilier',
    'Infographie & Design'          => 'Infographie & Design',
    'Informatique & Tech'           => 'Informatique & Digital',
    'Logistique & Supply Chain'     => 'Logistique & Supply Chain',
    'Management & Leadership'       => 'Direction & Management',
    'Mines, Énergie & Pétrole'      => 'Mines, Énergie & Pétrole',
    'QHSE'                          => 'QHSE',
    'Santé & Pharmacie'             => 'Santé & Pharmacie',
    'Tourisme & Hôtellerie'         => 'Tourisme & Hôtellerie',
];

/* ── Charger les titres déjà en base locale (normalisation) ── */
$pdo = Database::connect();
$existing_rows = $pdo->query("SELECT titre FROM formations WHERE statut = 'active'")->fetchAll(PDO::FETCH_COLUMN);
$existing_norms = [];
foreach ($existing_rows as $t) {
    $existing_norms[normalize_imp((string)$t)] = true;
}

/* ── Charger les slugs existants pour éviter les doublons ── */
$existing_slugs = $pdo->query("SELECT slug FROM formations")->fetchAll(PDO::FETCH_COLUMN);
$slug_set = array_flip($existing_slugs);

/* ── Insérer les formations manquantes ── */
$inserted = 0;
$skipped  = 0;
$errors   = [];

$stmt = $pdo->prepare("
    INSERT INTO formations
      (titre, slug, domaine, description, duree, tarif_en_ligne, tarif_presentiel,
       mode, statut, annee, is_samedi_pro, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, 'hybride', 'active', 0, 0, NOW())
");

foreach ($api_formations as $f) {
    $titre = trim((string)($f['name'] ?? ''));
    if ($titre === '') { $skipped++; continue; }

    /* Ignorer Samedi Pro */
    if (!empty($f['_samedi_pro']) || stripos($titre, 'samedi') !== false) {
        $skipped++; continue;
    }

    /* Déjà en base ? */
    if (isset($existing_norms[normalize_imp($titre)])) {
        $skipped++; continue;
    }

    /* Slug sans préfixe eduform- */
    $raw_slug = make_slug_imp($titre);
    /* Retirer le préfixe eduform- si présent dans le slug API */
    $api_slug = (string)($f['slug'] ?? '');
    if (str_starts_with($api_slug, 'eduform-')) {
        $api_slug = substr($api_slug, strlen('eduform-'));
    }
    /* Préférer le slug API nettoyé, sinon slug généré depuis le titre */
    $slug = $api_slug !== '' ? $api_slug : $raw_slug;

    /* Résoudre les conflits de slug */
    $base_slug = $slug;
    $suffix    = 1;
    while (isset($slug_set[$slug])) {
        $slug = $base_slug . '-' . $suffix++;
    }
    $slug_set[$slug] = true;

    $cat    = (string)($f['category'] ?? 'Autres');
    $domaine = $CAT_TO_DOMAINE[$cat] ?? 'Autres';
    $desc   = mb_substr(trim((string)($f['description'] ?? '')), 0, 1000);
    $duree  = trim((string)($f['_duree'] ?? ''));

    /* Tarifs : respecter la règle absolue ≥ 200 000 / 250 000 */
    $prix_api = (int)($f['price'] ?? 0);
    $tarif_ol = max($prix_api > 0 ? $prix_api : 200000, 200000);
    $tarif_pr = max((int)round($tarif_ol * 1.25 / 5000) * 5000, 250000);

    try {
        $stmt->execute([$titre, $slug, $domaine, $desc, $duree, $tarif_ol, $tarif_pr]);
        $existing_norms[normalize_imp($titre)] = true;
        $inserted++;
    } catch (Throwable $e) {
        $errors[] = ['titre' => mb_substr($titre, 0, 60), 'err' => $e->getMessage()];
    }
}

echo json_encode([
    'ok'         => true,
    'api_total'  => count($api_formations),
    'inserted'   => $inserted,
    'skipped'    => $skipped,
    'errors'     => count($errors),
    'error_sample' => array_slice($errors, 0, 5),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
