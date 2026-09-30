<?php
declare(strict_types=1);
/**
 * IBIG EDUFORM — /outils/devis-redownload.php
 * Régénère le PDF d'un devis existant depuis les données en BD.
 * Protégé par .htpasswd du dossier /outils/.
 */
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../core/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method Not Allowed'); }

$id = (int)($_POST['id'] ?? 0);
if (!$id) { http_response_code(400); exit('ID manquant'); }

/* ── Charger le devis depuis la BD ── */
try {
    $pdo = new PDO(
        'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4',
        DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $row = $pdo->prepare("SELECT * FROM devis WHERE id = ? LIMIT 1");
    $row->execute([$id]);
    $d = $row->fetch(PDO::FETCH_ASSOC);
} catch (\Throwable $e) {
    http_response_code(500); exit('Erreur BD : ' . htmlspecialchars($e->getMessage()));
}

if (!$d) { http_response_code(404); exit('Devis introuvable'); }

/* ── Republier vers devis-export-pdf.php via POST interne ── */
/* On reconstruit les champs POST attendus par devis-export-pdf.php */
$_POST = [
    'tdr_id'          => (string)($d['tdr_id'] ?? ''),
    'tdr_ref'         => $d['tdr_ref'] ?? '',
    'titre_formation' => $d['titre_formation'],
    'type_doc'        => 'formation',
    'categorie'       => $d['categorie'] ?? '',
    'duree'           => $d['duree'] ?? '',
    'prospect'        => $d['prospect'],
    'contact_nom'     => $d['contact_nom'] ?? '',
    'contact_email'   => $d['contact_email'] ?? '',
    'nb_participants' => (string)$d['nb_participants'],
    'mode_formation'  => $d['mode_formation'],
    'prix_unitaire_ht'=> (string)$d['prix_unitaire_ht'],
    'remise_pct'      => (string)$d['remise_pct'],
    'remise_grp_pct'  => (string)$d['remise_pct'],  // approximation — grille perdue
    'remise_extra_pct'=> '0',
    'tva_pct'         => (string)$d['tva_pct'],
    'montant_ht'      => (string)$d['montant_ht'],
    'montant_tva'     => (string)$d['montant_tva'],
    'montant_ttc'     => (string)$d['montant_ttc'],
    'validite_jours'  => (string)$d['validite_jours'],
    'date_formation'  => $d['date_formation'] ?? '',
    'notes'           => $d['notes_internes'] ?? '',
];
$_SERVER['REQUEST_METHOD'] = 'POST';

/* Remplacer la génération BD dans devis-export-pdf.php :
   On neutralise l'INSERT et le token QR en ré-injectant les valeurs existantes.
   Stratégie : inclure le fichier après avoir pré-défini les valeurs globales. */
define('DEVIS_REDOWNLOAD_MODE', true);
define('DEVIS_REDOWNLOAD_REF',   $d['ref_devis']);
define('DEVIS_REDOWNLOAD_TOKEN', $d['qr_token']);
define('DEVIS_REDOWNLOAD_ID',    (int)$d['id']);

require __DIR__ . '/devis-export-pdf.php';
