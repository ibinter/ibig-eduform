<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';
Middleware::requireAuth();
$pdo = Database::connect();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$confirm = $_GET['confirm'] ?? '';

// ─── DONNÉES DU TDR IBIG-EDU/COMP/2026/FISCDGI ───────────────────────────────
$slug_search     = 'fiscalite-pratique-des-entreprises'; // slug existant à rechercher
$nouveau_titre   = 'Fiscalité pratique des entreprises et déclarations DGI';
$duree           = '35H';
$mode            = 'hybride';
$tarif_en_ligne  = 395000;
$tarif_presentiel= 565000;
$tarif_hybride   = 480000;
$frais_inscription = 0;

$description = "Formation professionnelle de 35 heures sur la fiscalité pratique des entreprises et les déclarations DGI en Côte d'Ivoire. Conçue pour les comptables, assistants financiers, gestionnaires de PME et chefs d'entreprise, cette formation couvre l'ensemble du cycle fiscal : de la TVA aux impôts sur bénéfices, en passant par les déclarations mensuelles, la DSF et la préparation au contrôle fiscal. Format individuel, accompagnement personnalisé, 14 séances de 2h30 sur 7 semaines. Référence TDR : IBIG-EDU/COMP/2026/FISCDGI.";

$objectifs = "Identifier le régime d'imposition d'une entreprise et les obligations déclaratives qui en découlent\nCalculer, déclarer et payer la TVA, y compris dans les situations de crédit de TVA\nÉtablir les déclarations mensuelles d'impôts sur salaires et les retenues à la source\nDéterminer le résultat fiscal et calculer l'impôt sur les bénéfices et l'impôt minimum forfaitaire\nTraiter les autres impôts et taxes courants : IRVM, contribution des patentes, impôt foncier, droits d'enregistrement et de timbre\nAppliquer les règles propres aux loyers, aux prestataires non-résidents et aux marchés publics\nPréparer et déposer la déclaration statistique et fiscale (DSF)\nEffectuer ses télédéclarations et télépaiements sur la plateforme e-impôts\nIdentifier les avantages fiscaux dont l'entreprise peut bénéficier\nTenir un calendrier fiscal, assurer une veille sur les lois de finances et se préparer à un contrôle fiscal";

$modules = "M1 — Cadre fiscal et régimes d'imposition (3h) : Code général des impôts, loi de finances et annexe fiscale. Organisation de la DGI et rattachement des entreprises. Régimes du réel normal, réel simplifié, microentreprises. Calendrier fiscal. Compte e-impôts.\n" .
           "M2 — La TVA (5h) : Champ d'application, exonérations, taux. TVA collectée et déductible, règles de déduction, prorata, crédit de TVA. Facture normalisée électronique. Établissement et télédéclaration.\n" .
           "M3 — Impôts sur salaires et retenues à la source (4h) : Impôt sur les traitements et salaires et contributions employeur. Calcul sur bulletins réels, déclaration et paiement. Retenues à la source sur prestations de services, AIRSI et autres prélèvements.\n" .
           "M4 — Impôt sur les bénéfices (5h) : Passage du résultat comptable au résultat fiscal : charges déductibles, amortissements et provisions, réintégrations, déductions. Calcul de l'impôt sur les bénéfices et de l'impôt minimum forfaitaire. Acomptes et solde.\n" .
           "M5 — Autres impôts et opérations particulières (3h) : IRVM, contribution des patentes, impôt foncier, droits d'enregistrement et de timbre. Retenues sur loyers. Opérations avec prestataires non-résidents. TVA sur marchés publics.\n" .
           "M6 — DSF et télédéclaration (3h30) : Contenu et préparation de la déclaration statistique et fiscale. Tableaux fiscaux annexes. Délais de dépôt. Télédéclaration et télépaiement sur e-impôts, attestation de régularité fiscale.\n" .
           "M7 — Contrôle fiscal et contentieux (5h) : Formes de contrôle, droits et garanties du contribuable, déroulement d'une vérification. Pénalités et majorations. Simulation de vérification et rédaction d'une réponse à une notification de redressement. Réclamations et recours.\n" .
           "M8 — Avantages fiscaux et veille fiscale (1h30) : Code des investissements, exonérations et crédits d'impôt. Lecture de l'annexe fiscale et mise à jour des pratiques de l'entreprise.\n" .
           "M9 — Cas de synthèse et évaluation (5h) : Traitement complet du dossier fiscal annuel d'une entreprise : déclarations mensuelles, clôture, DSF. Évaluation finale, correction individuelle et remise du certificat.";

// ─── RECHERCHE DE LA FORMATION EXISTANTE ─────────────────────────────────────
$chk = $pdo->prepare("SELECT id, titre, slug, tarif_en_ligne, tarif_presentiel, duree FROM formations WHERE slug LIKE ? AND statut = 'active' LIMIT 1");
$chk->execute(['%fiscalite-pratique%']);
$existing = $chk->fetch(PDO::FETCH_ASSOC);

// Fallback : chercher par titre
if (!$existing) {
    $chk2 = $pdo->prepare("SELECT id, titre, slug, tarif_en_ligne, tarif_presentiel, duree FROM formations WHERE titre LIKE '%Fiscalit%pratique%' LIMIT 1");
    $chk2->execute();
    $existing = $chk2->fetch(PDO::FETCH_ASSOC);
}

// ─── PREVIEW ─────────────────────────────────────────────────────────────────
if ($confirm !== 'oui') {
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Update – Fiscalité DGI</title>
    <style>body{font-family:monospace;background:#0d1f3c;color:#e2e8f0;padding:24px;font-size:13px}
    h1{color:#f59e0b}h2{color:#93c5fd}.ok{color:#34d399}.skip{color:#f87171}.warn{color:#fbbf24}
    table{border-collapse:collapse;width:100%;margin-bottom:20px}
    th{background:#1e3a6e;color:#fff;padding:6px 10px;text-align:left}
    td{padding:5px 10px;border-bottom:1px solid #1e3a6e;vertical-align:top}
    pre{background:#1e3a6e;padding:10px;border-radius:4px;white-space:pre-wrap;font-size:12px}
    .btn{display:inline-block;padding:12px 28px;background:#f59e0b;color:#000;border-radius:6px;text-decoration:none;font-weight:bold;margin:16px 0}
    </style></head><body>';
    echo '<h1>📋 Update — Fiscalité pratique des entreprises et déclarations DGI</h1>';

    if (!$existing) {
        echo '<p class="skip">❌ Formation introuvable avec slug "%fiscalite-pratique%" — vérifier le slug en base.</p>';
    } else {
        echo '<p class="ok">✓ Formation trouvée — ID : <strong>' . (int)$existing['id'] . '</strong> | Slug : <strong>' . htmlspecialchars((string)$existing['slug']) . '</strong></p>';
        echo '<h2>Valeurs actuelles → nouvelles valeurs</h2>';
        echo '<table><thead><tr><th>Champ</th><th>Avant</th><th>Après</th></tr></thead><tbody>';
        foreach ([
            'titre'            => [(string)$existing['titre'],       $nouveau_titre],
            'duree'            => [(string)$existing['duree'],        $duree],
            'tarif_en_ligne'   => [number_format((int)$existing['tarif_en_ligne'], 0, ',', ' ') . ' FCFA',  number_format($tarif_en_ligne, 0, ',', ' ') . ' FCFA'],
            'tarif_presentiel' => [number_format((int)$existing['tarif_presentiel'], 0, ',', ' ') . ' FCFA', number_format($tarif_presentiel, 0, ',', ' ') . ' FCFA'],
            'tarif_hybride'    => ['—', number_format($tarif_hybride, 0, ',', ' ') . ' FCFA'],
            'frais_inscription'=> ['—', '0 (tout inclus)'],
        ] as $k => [$avant, $apres]) {
            $changed = $avant !== $apres ? ' style="background:rgba(245,158,11,.15)"' : '';
            echo '<tr' . $changed . '><td><strong>' . $k . '</strong></td><td>' . htmlspecialchars($avant) . '</td><td>' . htmlspecialchars($apres) . '</td></tr>';
        }
        echo '</tbody></table>';
    }

    echo '<h2>Description</h2><pre>' . htmlspecialchars($description) . '</pre>';
    echo '<h2>Objectifs (10)</h2><pre>' . htmlspecialchars($objectifs) . '</pre>';
    echo '<h2>Modules (9 — 35h)</h2><pre>' . htmlspecialchars($modules) . '</pre>';

    if ($existing) {
        echo '<a class="btn" href="?confirm=oui">🚀 CONFIRMER ET METTRE À JOUR EN BASE</a>';
    }
    echo '</body></html>';
    exit;
}

// ─── UPDATE ───────────────────────────────────────────────────────────────────
if (!$existing) {
    echo '<html><head><meta charset="utf-8"></head><body style="font-family:monospace;background:#0d1f3c;color:#f87171;padding:20px">';
    echo '<h1>❌ Formation introuvable — aucune mise à jour.</h1></body></html>';
    exit;
}

$fid = (int)$existing['id'];

$pdo->beginTransaction();
try {
    $pdo->prepare("
        UPDATE formations SET
            titre             = ?,
            description       = ?,
            objectifs         = ?,
            modules           = ?,
            duree             = ?,
            mode              = ?,
            tarif_en_ligne    = ?,
            tarif_presentiel  = ?,
            tarif_hybride     = ?,
            frais_inscription = ?,
            updated_at        = NOW()
        WHERE id = ?
    ")->execute([
        $nouveau_titre,
        $description,
        $objectifs,
        $modules,
        $duree,
        $mode,
        $tarif_en_ligne,
        $tarif_presentiel,
        $tarif_hybride,
        $frais_inscription,
        $fid,
    ]);

    // Mise à jour du niveau intermédiaire existant (ou création)
    $niv = $pdo->prepare("SELECT id FROM formation_niveaux WHERE formation_id = ? AND niveau = 'intermediaire' LIMIT 1");
    $niv->execute([$fid]);
    $niv_row = $niv->fetch(PDO::FETCH_ASSOC);

    if ($niv_row) {
        $pdo->prepare("
            UPDATE formation_niveaux SET
                duree_heures    = 35,
                tarif_en_ligne  = ?,
                tarif_presentiel= ?,
                tarif_hybride   = ?,
                updated_at      = NOW()
            WHERE id = ?
        ")->execute([$tarif_en_ligne, $tarif_presentiel, $tarif_hybride, (int)$niv_row['id']]);
    } else {
        $pdo->prepare("
            INSERT INTO formation_niveaux (formation_id, niveau, duree_heures, tarif_en_ligne, tarif_presentiel, tarif_hybride, statut, ordre_affichage)
            VALUES (?, 'intermediaire', 35, ?, ?, ?, 'actif', 2)
        ")->execute([$fid, $tarif_en_ligne, $tarif_presentiel, $tarif_hybride]);
    }

    $pdo->commit();

    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>OK</title>
    <style>body{font-family:monospace;background:#0d1f3c;color:#e2e8f0;padding:24px}
    h1{color:#34d399}.ok{color:#34d399}a{color:#f59e0b;margin-right:12px}</style></head><body>';
    echo '<h1>✅ Formation mise à jour avec succès</h1>';
    echo '<p class="ok">ID : <strong>' . $fid . '</strong></p>';
    echo '<p class="ok">Titre : <strong>' . htmlspecialchars($nouveau_titre) . '</strong></p>';
    echo '<p class="ok">Tarifs : En ligne ' . number_format($tarif_en_ligne,0,',',' ') . ' F · Présentiel ' . number_format($tarif_presentiel,0,',',' ') . ' F · Hybride ' . number_format($tarif_hybride,0,',',' ') . ' F</p>';
    echo '<p class="ok">Durée : 35H — 9 modules — niveau intermédiaire mis à jour</p>';
    echo '<p>';
    echo '<a href="../formation/' . htmlspecialchars((string)$existing['slug']) . '">→ Voir la fiche formation</a>';
    echo '<a href="formations/edit.php?id=' . $fid . '">→ Éditer</a>';
    echo '</p></body></html>';

} catch (Throwable $e) {
    $pdo->rollBack();
    echo '<html><head><meta charset="utf-8"></head><body style="font-family:monospace;background:#0d1f3c;color:#f87171;padding:20px">';
    echo '<h1>❌ Erreur</h1><pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
    echo '</body></html>';
}
