<?php
require_once __DIR__ . '/../core/bootstrap.php';
header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><title>Plaidoyer</title>
<style>body{font-family:sans-serif;max-width:900px;margin:40px auto;padding:0 20px}.ok{color:#16a34a;font-weight:bold}.err{color:#dc2626;font-weight:bold}.skip{color:#d97706}.row{padding:4px 0;border-bottom:1px solid #e5e7eb;font-size:.9rem}</style>
</head><body>
<h1>Insertion formations — Plaidoyer & Communication Institutionnelle (3 formations)</h1>
<?php

$formations = [
    [
        'titre'            => 'Plaidoyer Budgétaire & Interpellation des Décideurs Publics',
        'domaine'          => 'Communication Institutionnelle',
        'description'      => 'Le plaidoyer budgétaire est un levier stratégique pour les organisations de la société civile, les ONG, les associations professionnelles et les collectivités locales souhaitant influencer les allocations de ressources publiques en faveur de leurs causes. Cette formation couvre les mécanismes du processus budgétaire en Côte d\'Ivoire (loi de finances, DPPD, PAP, RAP), les techniques d\'analyse du budget public et d\'identification des allocations prioritaires, la construction d\'un argumentaire de plaidoyer budgétaire (données probantes, cadre logique, analyse coût-bénéfice), les stratégies d\'accès aux décideurs (ministères, Assemblée Nationale, Sénat, collectivités), la mobilisation des alliés et la constitution de coalitions, et la communication vers les médias et l\'opinion publique pour renforcer la pression. Exercices pratiques de simulation d\'audiences parlementaires et de négociation budgétaire. Adapté aux responsables d\'ONG, cadres de la société civile, élus locaux et gestionnaires de programmes publics.',
        'duree'            => '3 jours (24h)',
        'mode'             => 'hybride',
        'tarif_en_ligne'   => 210000,
        'tarif_presentiel' => 260000,
        'slug'             => 'plaidoyer-budgetaire-interpellation-decideurs-publics',
    ],
    [
        'titre'            => 'Communication Institutionnelle pour Établissements Publics — Ministères, Mairies & Établissements',
        'domaine'          => 'Communication Institutionnelle',
        'description'      => 'Les institutions publiques ivoiriennes (ministères, mairies, établissements publics, régions) font face à des exigences croissantes de transparence, de redevabilité et d\'engagement citoyen. Cette formation spécialisée couvre les fondamentaux de la communication institutionnelle publique : élaboration d\'une stratégie de communication pour une institution publique, relations avec les médias et gestion des conférences de presse gouvernementales, rédaction des supports institutionnels (rapports annuels, plaquettes, communiqués officiels), communication digitale des institutions publiques (site web institutionnel, réseaux sociaux officiels, open data), protocole et étiquette dans les cérémonies officielles, gestion de la communication en période électorale ou de crise institutionnelle, et communication interculturelle dans les contextes multilingues africains. Inclut les spécificités réglementaires ivoiriennes (loi sur la communication audiovisuelle, rôle du HACA, accès à l\'information publique). Adapté aux chargés de communication des ministères, directions régionales, mairies et établissements publics.',
        'duree'            => '3 jours (24h)',
        'mode'             => 'hybride',
        'tarif_en_ligne'   => 200000,
        'tarif_presentiel' => 250000,
        'slug'             => 'communication-institutionnelle-etablissements-publics-ministeres-mairies',
    ],
    [
        'titre'            => 'Diplomatie Publique & Soft Power — Influence, Image Nationale & Relations Internationales',
        'domaine'          => 'Communication Institutionnelle',
        'description'      => 'La diplomatie publique désigne l\'ensemble des actions menées par un État, une organisation internationale ou une grande institution pour influencer l\'opinion publique étrangère, promouvoir son image et ses valeurs, et renforcer son soft power. Cette formation couvre les fondements théoriques et les outils pratiques de la diplomatie publique contemporaine : distinction entre diplomatie traditionnelle et diplomatie publique, les instruments du soft power (culture, langue, aide au développement, médias internationaux, diplomatie sportive), la gestion de l\'image nationale et la réputation internationale, les réseaux d\'influence culturelle (instituts culturels, diasporas, think tanks), la communication numérique dans les relations internationales (diplomatie des réseaux sociaux, e-diplomatie), et la gestion des crises d\'image à l\'international. Mise en perspective avec les stratégies de soft power des puissances africaines émergentes (Maroc, Afrique du Sud, Éthiopie, Côte d\'Ivoire). Adapté aux diplomates, fonctionnaires des ministères des Affaires Étrangères, responsables d\'institutions culturelles et cadres d\'organisations internationales.',
        'duree'            => '3 jours (24h)',
        'mode'             => 'hybride',
        'tarif_en_ligne'   => 220000,
        'tarif_presentiel' => 270000,
        'slug'             => 'diplomatie-publique-soft-power-influence-image-nationale-relations-internationales',
    ],
];

try { $pdo = Database::connect(); }
catch (Throwable $e) { die('<p style="color:red">Erreur DB : '.htmlspecialchars($e->getMessage()).'</p>'); }

$sql = "INSERT INTO formations
    (titre,domaine,description,duree,mode,tarif_en_ligne,tarif_presentiel,slug,statut,is_samedi_pro)
    VALUES(:titre,:domaine,:description,:duree,:mode,:tarif_en_ligne,:tarif_presentiel,:slug,'active',0)
    ON DUPLICATE KEY UPDATE titre=VALUES(titre),description=VALUES(description),
    duree=VALUES(duree),mode=VALUES(mode),tarif_en_ligne=VALUES(tarif_en_ligne),
    tarif_presentiel=VALUES(tarif_presentiel),statut='active'";

$chk = $pdo->prepare("SELECT id FROM formations WHERE slug=:s LIMIT 1");
$ins = $pdo->prepare($sql);
$ni=$nu=$ne=0;

echo "<h2>Résultats</h2>";
foreach($formations as $f){
    if($f['tarif_en_ligne']<200000||$f['tarif_presentiel']<250000){
        echo "<div class='row'><span class='err'>⛔ TARIF</span> — {$f['titre']}</div>"; $ne++; continue;
    }
    $chk->execute([':s'=>$f['slug']]); $exists=$chk->fetchColumn();
    try{
        $ins->execute([':titre'=>$f['titre'],':domaine'=>$f['domaine'],':description'=>$f['description'],
            ':duree'=>$f['duree'],':mode'=>$f['mode'],':tarif_en_ligne'=>$f['tarif_en_ligne'],
            ':tarif_presentiel'=>$f['tarif_presentiel'],':slug'=>$f['slug']]);
        if($exists){ echo "<div class='row'><span class='skip'>🔄 MÀJ</span> — ".htmlspecialchars($f['titre'])."</div>"; $nu++; }
        else        { echo "<div class='row'><span class='ok'>✅ INSÉRÉ</span> — ".htmlspecialchars($f['titre'])."</div>"; $ni++; }
    }catch(Throwable $e){
        echo "<div class='row'><span class='err'>❌</span> — ".htmlspecialchars($f['titre'])." : ".htmlspecialchars($e->getMessage())."</div>"; $ne++;
    }
}
echo "<h2>Bilan</h2><p><strong style='color:#16a34a'>✅ {$ni} insérée(s)</strong> | <strong style='color:#d97706'>🔄 {$nu} MÀJ</strong> | <strong style='color:#dc2626'>❌ {$ne} erreur(s)</strong></p>";
?>
</body></html>
