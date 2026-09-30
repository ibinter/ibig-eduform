<?php
declare(strict_types=1);
ini_set('display_errors', '1');
error_reporting(E_ALL);
/**
 * IBIG EDUFORM — Migration système 3 niveaux
 * ─────────────────────────────────────────────────────────────────────
 * Crée les tables formation_niveaux + formation_niveau_modules,
 * puis peuple automatiquement le niveau correspondant à chaque formation
 * existante du catalogue (formations.php non touché).
 *
 * Règle d'assignation du niveau d'après la durée actuelle :
 *   ≤ 25h  → Débutant
 *   26-40h → Intermédiaire
 *   > 40h  → Expert
 *
 * La formation existante devient ACTIVE sur son niveau,
 * les deux autres niveaux sont créés en BROUILLON.
 *
 * Coefficients tarifaires :
 *   Débutant       : × 1,00 (tarif actuel)
 *   Intermédiaire  : × 1,15
 *   Expert         : × 1,35
 *
 * Exécuter UNE SEULE FOIS : php run-migration-niveaux.php
 */

// ── Sécurité : uniquement en CLI ou avec le token TDR_SECRET ──────────
if (PHP_SAPI !== 'cli') {
    require_once __DIR__ . '/core/config.php';
    $expected = defined('TDR_SECRET') ? TDR_SECRET : '';
    if ($expected === '' || ($_GET['token'] ?? '') !== $expected) {
        http_response_code(403);
        exit('<p style="font-family:sans-serif;color:#dc2626;padding:20px">Accès refusé. Token requis.</p>');
    }
}

require_once __DIR__ . '/core/config.php';
require_once __DIR__ . '/core/database.php';

$pdo = Database::connect();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$log = [];
$errors = [];

function log_msg(string $msg): void {
    global $log;
    $log[] = $msg;
    if (PHP_SAPI === 'cli') echo $msg . "\n";
}

function r5(float $v): int { return (int)(round($v / 5000) * 5000); }

// ── Coefficients par niveau ────────────────────────────────────────────
const COEFF = [
    'debutant'      => 1.00,
    'intermediaire' => 1.15,
    'expert'        => 1.35,
];

// Durées par niveau (heures) selon durée de base
function durees_par_niveau(int $heures_base): array {
    // Normalise la durée de base vers un palier connu
    if ($heures_base <= 0) $heures_base = 20;

    // Niveau débutant : toujours la plus courte (min 20h)
    // Niveau intermédiaire : + 50 % du débutant
    // Niveau expert : + 100 % du débutant
    $debut = max(20, (int)(round($heures_base / 5) * 5));
    $inter = max(25, (int)(round($debut * 1.5 / 5) * 5));
    $exper = max(35, (int)(round($debut * 2.0 / 5) * 5));

    return [
        'debutant'      => $debut,
        'intermediaire' => $inter,
        'expert'        => $exper,
    ];
}

// Calcule le tarif pour un niveau donné depuis le tarif de base
function tarif_niveau(int $tarif_base, string $niveau): int {
    $coeff = COEFF[$niveau];
    $t = r5((float)$tarif_base * $coeff);
    $min = ['debutant' => 200000, 'intermediaire' => 250000, 'expert' => 300000];
    return max($t, $min[$niveau]);
}

// Détermine le niveau principal d'après la durée en heures
function niveau_principal(int $h): string {
    if ($h <= 25) return 'debutant';
    if ($h <= 40) return 'intermediaire';
    return 'expert';
}

// Parse la durée textuelle en heures
function parse_duree(string $duree): int {
    $d = mb_strtolower(trim($duree));
    if (preg_match('/(\d+)\s*(?:jour|day)/', $d, $m)) return (int)$m[1] * 8;
    if (preg_match('/(\d+)\s*(?:semaine|week)/', $d, $m)) return (int)$m[1] * 40;
    if (preg_match('/(\d+)\s*(?:mois|month)/', $d, $m)) return (int)$m[1] * 80;
    if (preg_match('/(\d+)/', $d, $m)) return (int)$m[1];
    return 20; // fallback
}

// Ordre d'affichage
const ORDRE = ['debutant' => 1, 'intermediaire' => 2, 'expert' => 3];

// ── 1. Créer les tables ────────────────────────────────────────────────
log_msg("═══ MIGRATION — Système 3 niveaux formations ═══");
log_msg("1. Création des tables...");

$sql = file_get_contents(__DIR__ . '/migrations/2026_niveaux_formations.sql');
// Exécuter instruction par instruction (PDO ne supporte pas multi-statement)
$stmts = array_filter(array_map('trim', explode(';', $sql)));
foreach ($stmts as $stmt) {
    if (empty($stmt) || str_starts_with(ltrim($stmt), '--')) continue;
    try {
        $pdo->exec($stmt);
    } catch (PDOException $e) {
        // Ignorer les erreurs "déjà existe"
        if (!str_contains($e->getMessage(), 'already exists') &&
            !str_contains($e->getMessage(), 'Duplicate')) {
            $errors[] = "SQL error: " . $e->getMessage() . "\n  SQL: " . substr($stmt, 0, 100);
        }
    }
}
log_msg("   → Tables créées (ou déjà présentes).");

// ── 2. Charger toutes les formations actives du catalogue ──────────────
// Exclure les formations Samedi Pro (calendrier)
log_msg("2. Chargement des formations catalogue...");

$formations = $pdo->query("
    SELECT id, titre, slug, domaine, duree, tarif_en_ligne, tarif_presentiel
    FROM formations
    WHERE statut IN ('active','inactive')
      AND (titre NOT LIKE 'Tarif Groupe%')
      AND COALESCE(is_samedi_pro, 0) = 0
    ORDER BY titre ASC
")->fetchAll(PDO::FETCH_ASSOC);

log_msg("   → " . count($formations) . " formations trouvées.");

// ── 3. Peupler formation_niveaux ───────────────────────────────────────
log_msg("3. Création des niveaux...");

$stmtInsert = $pdo->prepare("
    INSERT IGNORE INTO formation_niveaux
        (formation_id, niveau, duree_heures, tarif_en_ligne, tarif_presentiel,
         tarif_hybride, statut, ordre_affichage)
    VALUES
        (:fid, :niveau, :duree, :tarif_ol, :tarif_pr, :tarif_hy, :statut, :ordre)
");

$stmtUpdate = $pdo->prepare("
    UPDATE formation_niveaux SET
        duree_heures     = :duree,
        tarif_en_ligne   = :tarif_ol,
        tarif_presentiel = :tarif_pr,
        tarif_hybride    = :tarif_hy,
        statut           = :statut,
        ordre_affichage  = :ordre
    WHERE formation_id = :fid AND niveau = :niveau
    AND statut = 'brouillon'
");

$nb_crees = 0;
$nb_formas = 0;

foreach ($formations as $f) {
    $heures = parse_duree((string)($f['duree'] ?? ''));
    $tarif_ol = max(200000, (int)($f['tarif_en_ligne'] ?? 0));
    $tarif_pr = max(250000, (int)($f['tarif_presentiel'] ?? 0));

    // Durée de base pour le système 3 niveaux = durée du niveau débutant
    // On normalise pour que la durée actuelle soit le niveau principal
    $niv_principal = niveau_principal($heures);
    $durees = durees_par_niveau($heures);

    // Recalculer les tarifs à partir du tarif actuel
    // Le tarif actuel correspond au niveau principal
    $tarif_base_ol = r5((float)$tarif_ol / COEFF[$niv_principal]);
    $tarif_base_pr = r5((float)$tarif_pr / COEFF[$niv_principal]);

    $nb_formas++;

    foreach (['debutant', 'intermediaire', 'expert'] as $niv) {
        $t_ol = tarif_niveau($tarif_base_ol, $niv);
        $t_pr = tarif_niveau($tarif_base_pr, $niv);
        $t_hy = r5(($t_ol + $t_pr) / 2);
        $dur  = $durees[$niv];

        // Le niveau principal devient actif, les autres restent brouillons
        $statut = ($niv === $niv_principal) ? 'actif' : 'brouillon';

        // INSERT si n'existe pas
        $stmtInsert->execute([
            ':fid'      => (int)$f['id'],
            ':niveau'   => $niv,
            ':duree'    => $dur,
            ':tarif_ol' => $t_ol,
            ':tarif_pr' => $t_pr,
            ':tarif_hy' => $t_hy,
            ':statut'   => $statut,
            ':ordre'    => ORDRE[$niv],
        ]);
        $nb_crees += $pdo->lastInsertId() ? 1 : 0;
    }
}

log_msg("   → $nb_formas formations traitées, $nb_crees niveaux créés.");

// ── 4. Résumé par niveau ───────────────────────────────────────────────
log_msg("4. Résumé...");
$stats = $pdo->query("
    SELECT niveau, statut, COUNT(*) as nb
    FROM formation_niveaux
    GROUP BY niveau, statut
    ORDER BY FIELD(niveau,'debutant','intermediaire','expert'), statut
")->fetchAll(PDO::FETCH_ASSOC);

foreach ($stats as $s) {
    log_msg("   {$s['niveau']} / {$s['statut']} : {$s['nb']}");
}

// ── Rapport ────────────────────────────────────────────────────────────
log_msg("");
if ($errors) {
    log_msg("⚠  ERREURS (" . count($errors) . ") :");
    foreach ($errors as $e) log_msg("   " . $e);
} else {
    log_msg("✅ Migration terminée sans erreur.");
}
log_msg("");
log_msg("Prochaines étapes :");
log_msg("  • admin/niveaux/ — gérer les niveaux et modules");
log_msg("  • Activer les niveaux débutant/expert manuellement via l'admin");
log_msg("  • Générer les modules via outils/tdr-ai-generate.php");

if (PHP_SAPI !== 'cli') {
    header('Content-Type: text/html; charset=utf-8');
    echo '<pre style="font-family:monospace;padding:20px;background:#0a1733;color:#e8edf8">';
    echo implode("\n", array_map('htmlspecialchars', $log));
    echo '</pre>';
}
