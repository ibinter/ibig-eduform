<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';
Middleware::requireAuth();
$pdo = Database::connect();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$confirm = $_GET['confirm'] ?? '';

// ─── DONNÉES ──────────────────────────────────────────────────────────────────
$slug            = 'monetique-et-moyens-de-paiement';
$titre           = 'Monétique et moyens de paiement';
$domaine         = 'Banque & Assurance';
$type_certificat = 'Certificat professionnel';
$duree           = '25H';
$mode            = 'hybride';
$tarif_en_ligne  = 250000;
$tarif_presentiel = 300000;
$tarif_hybride   = 0;
$frais_inscription = 0;
$paiement_lien   = '';
$date_debut      = null;
$date_fin        = null;
$mois            = null;
$annee           = 2026;
$session_label   = null;
$statut          = 'active';

$description = "Formation professionnelle de 25 heures sur la monétique et les moyens de paiement électronique dans l'espace UEMOA. Conçue pour les professionnels du secteur bancaire, des établissements de monnaie électronique et des opérateurs de paiement mobile, cette formation couvre l'ensemble de l'écosystème monétique : des fondamentaux aux mécanismes de sécurité, en passant par les acteurs, les canaux d'acceptation et la gestion des litiges. Référence TDR : TDR-IBIG-2026-MON.";

$objectifs = "Maîtriser les fondamentaux de la monétique et des systèmes de paiement électronique\nComprendre le cadre réglementaire BCEAO/UEMOA applicable aux paiements\nIdentifier les acteurs, les flux et les processus d'une transaction par carte\nAnalyser les canaux d'acceptation (TPE, ATM, e-commerce, mobile)\nComprendre le fonctionnement de la monnaie électronique et du paiement mobile\nMettre en œuvre les mesures de sécurité et de lutte contre la fraude (normes EMV, PCI DSS)\nGérer les litiges, rétrofacturations et rapprochements\nAppliquer les connaissances sur des cas pratiques";

$modules = "M1 — Fondamentaux de la monétique (2h30) : définitions, histoire, enjeux, cadre réglementaire BCEAO\n" .
           "M2 — La carte de paiement : anatomie, types, normes EMV, cycle de vie (3h)\n" .
           "M3 — Acteurs et flux d'une transaction monétique : émetteurs, acquéreurs, réseaux Visa / Mastercard / GIM-UEMOA (3h30)\n" .
           "M4 — Canaux d'acceptation : TPE, GAB/ATM, e-commerce, paiement sans contact (3h)\n" .
           "M5 — Monnaie électronique et paiement mobile : Orange Money, Wave, Moov Money, MTN MoMo (3h)\n" .
           "M6 — Sécurité et lutte contre la fraude : tokenisation, 3D-Secure, PCI DSS, détection fraude (3h)\n" .
           "M7 — Litiges, rétrofacturations et rapprochements : procédures chargeback, réclamations (3h)\n" .
           "M8 — Cas pratique et évaluation finale (4h)";

// ─── VÉRIFICATION SLUG ───────────────────────────────────────────────────────
$exists = (int)$pdo->prepare("SELECT COUNT(*) FROM formations WHERE slug=?")->execute([$slug])
    ? (int)$pdo->prepare("SELECT COUNT(*) FROM formations WHERE slug=?") && false
    : 0;
$chk = $pdo->prepare("SELECT COUNT(*) FROM formations WHERE slug=?");
$chk->execute([$slug]);
$exists = (int)$chk->fetchColumn();

// ─── PREVIEW ─────────────────────────────────────────────────────────────────
if ($confirm !== 'oui') {
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Preview – Monétique</title>
    <style>body{font-family:monospace;background:#0d1f3c;color:#e2e8f0;padding:24px;font-size:13px}
    h1{color:#f59e0b}h2{color:#93c5fd}.ok{color:#34d399}.skip{color:#f87171}
    table{border-collapse:collapse;width:100%;margin-bottom:20px}
    th{background:#1e3a6e;color:#fff;padding:6px 10px;text-align:left}
    td{padding:5px 10px;border-bottom:1px solid #1e3a6e;vertical-align:top}
    pre{background:#1e3a6e;padding:10px;border-radius:4px;white-space:pre-wrap;font-size:12px}
    .btn{display:inline-block;padding:12px 28px;background:#f59e0b;color:#000;border-radius:6px;text-decoration:none;font-weight:bold;margin:16px 0}
    </style></head><body>';
    echo '<h1>📋 Preview — Monétique et moyens de paiement</h1>';

    if ($exists) {
        echo '<p class="skip">⚠️ Slug <code>' . $slug . '</code> déjà présent — pas d\'insertion.</p>';
    } else {
        echo '<p class="ok">✓ Slug libre — prêt pour l\'insertion</p>';
    }

    echo '<table><thead><tr><th>Champ</th><th>Valeur</th></tr></thead><tbody>';
    foreach ([
        'titre'            => $titre,
        'slug'             => $slug,
        'domaine'          => $domaine,
        'type_certificat'  => $type_certificat,
        'duree'            => $duree,
        'mode'             => $mode,
        'tarif_en_ligne'   => number_format($tarif_en_ligne, 0, ',', ' ') . ' FCFA (tout inclus)',
        'tarif_presentiel' => number_format($tarif_presentiel, 0, ',', ' ') . ' FCFA (tout inclus)',
        'frais_inscription'=> '0',
        'annee'            => $annee,
        'statut'           => $statut,
    ] as $k => $v) {
        echo '<tr><td><strong>' . $k . '</strong></td><td>' . htmlspecialchars($v) . '</td></tr>';
    }
    echo '</tbody></table>';

    echo '<h2>Description</h2><pre>' . htmlspecialchars($description) . '</pre>';
    echo '<h2>Objectifs</h2><pre>' . htmlspecialchars($objectifs) . '</pre>';
    echo '<h2>Modules (8)</h2><pre>' . htmlspecialchars($modules) . '</pre>';

    if (!$exists) {
        echo '<a class="btn" href="?confirm=oui">🚀 CONFIRMER ET INSÉRER EN BASE</a>';
    }
    echo '</body></html>';
    exit;
}

// ─── INSERTION ────────────────────────────────────────────────────────────────
if ($exists) {
    echo '<html><head><meta charset="utf-8"></head><body style="font-family:monospace;background:#0d1f3c;color:#f87171;padding:20px">';
    echo '<h1>⚠️ Slug déjà présent — aucune insertion.</h1></body></html>';
    exit;
}

$pdo->beginTransaction();
try {
    $pdo->prepare("
        INSERT INTO formations (
            titre, slug, domaine, type_certificat,
            description, objectifs, modules, duree,
            mode,
            tarif_presentiel, tarif_en_ligne, tarif_hybride,
            frais_inscription, paiement_lien,
            date_debut, date_fin, mois, annee, session_label,
            statut,
            created_at, updated_at
        ) VALUES (
            ?, ?, ?, ?,
            ?, ?, ?, ?,
            ?,
            ?, ?, ?,
            ?, ?,
            ?, ?, ?, ?, ?,
            ?,
            NOW(), NOW()
        )
    ")->execute([
        $titre, $slug, $domaine, $type_certificat,
        $description, $objectifs, $modules, $duree,
        $mode,
        $tarif_presentiel, $tarif_en_ligne, $tarif_hybride,
        $frais_inscription, $paiement_lien,
        $date_debut, $date_fin, $mois, $annee, $session_label,
        $statut,
    ]);

    $fid = (int)$pdo->lastInsertId();

    // Niveau intermédiaire (coefficient ×1.15, 25h)
    $pdo->prepare("
        INSERT INTO formation_niveaux (
            formation_id, niveau, duree_heures,
            tarif_en_ligne, tarif_presentiel, tarif_hybride,
            statut, ordre_affichage
        ) VALUES (?, 'intermediaire', 25, ?, ?, 0, 'actif', 2)
    ")->execute([$fid, $tarif_en_ligne, $tarif_presentiel]);

    $pdo->commit();

    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>OK</title>
    <style>body{font-family:monospace;background:#0d1f3c;color:#e2e8f0;padding:24px}
    h1{color:#34d399}.ok{color:#34d399}a{color:#f59e0b;margin-right:12px}</style></head><body>';
    echo '<h1>✅ Formation insérée avec succès</h1>';
    echo '<p class="ok">ID : <strong>' . $fid . '</strong></p>';
    echo '<p class="ok">Titre : <strong>' . htmlspecialchars($titre) . '</strong></p>';
    echo '<p class="ok">Niveau intermédiaire créé dans formation_niveaux</p>';
    echo '<p>';
    echo '<a href="../admin/niveaux/edit.php?id=' . $fid . '">→ Gérer les niveaux</a>';
    echo '<a href="../admin/formations/edit.php?id=' . $fid . '">→ Éditer la formation</a>';
    echo '</p></body></html>';

} catch (Throwable $e) {
    $pdo->rollBack();
    echo '<html><head><meta charset="utf-8"></head><body style="font-family:monospace;background:#0d1f3c;color:#f87171;padding:20px">';
    echo '<h1>❌ Erreur</h1><pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
    echo '</body></html>';
}
