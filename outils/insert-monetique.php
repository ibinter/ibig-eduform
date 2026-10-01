<?php
declare(strict_types=1);
require_once __DIR__ . '/../core/config.php';
$pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);

$confirm = $_GET['confirm'] ?? '';

// ─── DONNÉES DE LA FORMATION ──────────────────────────────────────────────────
$slug   = 'monetique-et-moyens-de-paiement';
$titre  = 'Monétique et moyens de paiement';
$domaine = 'Banque & Assurance';
$type_certificat = 'Certificat professionnel';

$description = "Formation professionnelle de 25 heures sur la monétique et les moyens de paiement électronique dans l'espace UEMOA. Conçue pour les professionnels du secteur bancaire, des établissements de monnaie électronique et des opérateurs de paiement mobile, cette formation couvre l'ensemble de l'écosystème monétique : des fondamentaux aux mécanismes de sécurité, en passant par les acteurs, les canaux d'acceptation et la gestion des litiges. Référence TDR : TDR-IBIG-2026-MON.";

$objectifs = "• Maîtriser les fondamentaux de la monétique et des systèmes de paiement électronique\n• Comprendre le cadre réglementaire BCEAO/UEMOA applicable aux paiements\n• Identifier les acteurs, les flux et les processus d'une transaction par carte\n• Analyser les canaux d'acceptation (TPE, ATM, e-commerce, mobile)\n• Comprendre le fonctionnement de la monnaie électronique et du paiement mobile\n• Mettre en œuvre les mesures de sécurité et de lutte contre la fraude (normes EMV, PCI DSS)\n• Gérer les litiges, rétrofacturations et rapprochements\n• Appliquer les connaissances sur des cas pratiques";

$modules = "M1 — Fondamentaux de la monétique (2h30) : définitions, histoire, enjeux, cadre réglementaire BCEAO\n" .
           "M2 — La carte de paiement : anatomie, types, normes EMV, cycle de vie (3h)\n" .
           "M3 — Acteurs et flux d'une transaction monétique : émetteurs, acquéreurs, réseaux (Visa, Mastercard, GIM-UEMOA) (3h30)\n" .
           "M4 — Canaux d'acceptation : TPE, GAB/ATM, e-commerce, paiement sans contact (3h)\n" .
           "M5 — Monnaie électronique et paiement mobile : Orange Money, Wave, Moov Money, MTN MoMo (3h)\n" .
           "M6 — Sécurité et lutte contre la fraude : tokenisation, 3D-Secure, PCI DSS, détection fraude (3h)\n" .
           "M7 — Litiges, rétrofacturations et rapprochements : procédures chargeback, réclamations (3h)\n" .
           "M8 — Cas pratique et évaluation finale (4h)";

$public_cible = "Agents de banques, établissements de monnaie électronique (EME), microfinance et opérateurs mobile money ; personnels des back-offices paiement et monétique ; équipes conformité, audit et contrôle interne ; informaticiens et chefs de projet IT ; commerçants et responsables financiers traitant des paiements électroniques ; étudiants en banque, finance et systèmes d'information.";

$prerequis = "Bac+2 minimum ou expérience professionnelle dans le secteur bancaire, financier ou des télécommunications.";

$duree           = '25H';
$mode            = 'hybride';
$tarif_en_ligne  = 200000;
$tarif_presentiel = 250000;
$frais_inscription = 50000;

// ─── PREVIEW ──────────────────────────────────────────────────────────────────
$exists = (int)$pdo->query("SELECT COUNT(*) FROM formations WHERE slug='$slug'")->fetchColumn();

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
    echo '<h1>📋 Preview — Formation Monétique et moyens de paiement</h1>';

    if ($exists) {
        echo '<p class="skip">⚠️ ATTENTION : un enregistrement avec le slug <code>' . $slug . '</code> existe déjà en base. L\'insertion sera ignorée.</p>';
    } else {
        echo '<p class="ok">✓ Slug libre — prêt pour l\'insertion</p>';
    }

    echo '<table><thead><tr><th>Champ</th><th>Valeur</th></tr></thead><tbody>';
    $fields = [
        'titre'            => $titre,
        'slug'             => $slug,
        'domaine'          => $domaine,
        'type_certificat'  => $type_certificat,
        'duree'            => $duree,
        'mode'             => $mode,
        'tarif_en_ligne'   => number_format($tarif_en_ligne, 0, ',', ' ') . ' FCFA',
        'tarif_presentiel' => number_format($tarif_presentiel, 0, ',', ' ') . ' FCFA',
        'frais_inscription'=> number_format($frais_inscription, 0, ',', ' ') . ' FCFA',
        'statut'           => 'active',
    ];
    foreach ($fields as $k => $v) {
        echo '<tr><td><strong>' . $k . '</strong></td><td>' . htmlspecialchars($v) . '</td></tr>';
    }
    echo '</tbody></table>';

    echo '<h2>Description</h2><pre>' . htmlspecialchars($description) . '</pre>';
    echo '<h2>Objectifs</h2><pre>' . htmlspecialchars($objectifs) . '</pre>';
    echo '<h2>Modules (8)</h2><pre>' . htmlspecialchars($modules) . '</pre>';
    echo '<h2>Public cible</h2><pre>' . htmlspecialchars($public_cible) . '</pre>';
    echo '<h2>Prérequis</h2><pre>' . htmlspecialchars($prerequis) . '</pre>';

    if (!$exists) {
        echo '<a class="btn" href="?confirm=oui">🚀 CONFIRMER ET INSÉRER EN BASE</a>';
    }
    echo '</body></html>';
    exit;
}

// ─── INSERTION ────────────────────────────────────────────────────────────────
if ($exists) {
    echo '<html><head><meta charset="utf-8"></head><body style="font-family:monospace;background:#0d1f3c;color:#f87171;padding:20px">';
    echo '<h1>⚠️ Formation déjà présente</h1>';
    echo '<p>Le slug <code>' . $slug . '</code> existe déjà. Aucune insertion effectuée.</p>';
    echo '</body></html>';
    exit;
}

$pdo->beginTransaction();
try {
    $pdo->prepare("
        INSERT INTO formations (
            titre, slug, domaine, type_certificat, description, objectifs, modules,
            duree, mode, tarif_en_ligne, tarif_presentiel, frais_inscription,
            statut, annee, is_samedi_pro, public_cible
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            'active', 2026, 0, ?
        )
    ")->execute([
        $titre, $slug, $domaine, $type_certificat, $description, $objectifs, $modules,
        $duree, $mode, $tarif_en_ligne, $tarif_presentiel, $frais_inscription,
        $public_cible
    ]);

    $fid = (int)$pdo->lastInsertId();

    // Niveau intermédiaire (coefficient 1.15, durée min 25h)
    $pdo->prepare("
        INSERT INTO formation_niveaux (
            formation_id, niveau, duree_heures, tarif_en_ligne, tarif_presentiel, statut, ordre_affichage
        ) VALUES (?, 'intermediaire', 25, ?, ?, 'actif', 2)
    ")->execute([$fid, $tarif_en_ligne, $tarif_presentiel]);

    $pdo->commit();

    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Insertion OK</title>
    <style>body{font-family:monospace;background:#0d1f3c;color:#e2e8f0;padding:24px}
    h1{color:#34d399}.ok{color:#34d399}a{color:#f59e0b}</style></head><body>';
    echo '<h1>✅ Formation insérée avec succès</h1>';
    echo '<p class="ok">ID en base : <strong>' . $fid . '</strong></p>';
    echo '<p class="ok">Titre : <strong>' . htmlspecialchars($titre) . '</strong></p>';
    echo '<p class="ok">Slug : <strong>' . $slug . '</strong></p>';
    echo '<p class="ok">Niveau intermédiaire créé (formation_niveaux)</p>';
    echo '<p><a href="../admin/niveaux/edit.php?id=' . $fid . '">→ Gérer les niveaux</a></p>';
    echo '<p><a href="../admin/formations/edit.php?id=' . $fid . '">→ Éditer la formation</a></p>';
    echo '</body></html>';

} catch (Throwable $e) {
    $pdo->rollBack();
    echo '<html><head><meta charset="utf-8"></head><body style="font-family:monospace;background:#0d1f3c;color:#f87171;padding:20px">';
    echo '<h1>❌ Erreur lors de l\'insertion</h1>';
    echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
    echo '</body></html>';
}
