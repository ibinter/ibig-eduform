<?php
require_once __DIR__ . '/../core/bootstrap.php';
header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><title>Humanitaire</title>
<style>body{font-family:sans-serif;max-width:900px;margin:40px auto;padding:0 20px}.ok{color:#16a34a;font-weight:bold}.err{color:#dc2626;font-weight:bold}.skip{color:#d97706}.row{padding:4px 0;border-bottom:1px solid #e5e7eb;font-size:.9rem}</style>
</head><body>
<h1>Insertion formations — Humanitaire & ONG (7 formations)</h1>
<?php

$formations = [
    [
        'titre'            => 'Coordination Inter-agences & Clusters Humanitaires',
        'domaine'          => 'Humanitaire & ONG',
        'description'      => 'Dans les réponses aux crises humanitaires, la coordination entre les acteurs — agences onusiennes (UNICEF, UNHCR, PAM, OMS), ONG internationales et nationales, autorités locales et bailleurs — est déterminante pour l\'efficacité de l\'aide. Cette formation couvre le système de coordination humanitaire internationale : l\'architecture clusters (protection, santé, nutrition, abris, EAH, sécurité alimentaire), le rôle de l\'OCHA comme coordinateur global, les mécanismes de coordination au niveau pays (HCT, ICCG, inter-clusters), la gestion des informations humanitaires (4W — Qui fait Quoi Où Quand), la participation aux réunions de coordination et la rédaction des SitReps. Exercices pratiques basés sur des crises réelles en Afrique de l\'Ouest (inondations, déplacements, épidémies). Adapté aux coordinateurs d\'ONG, agents onusiens et responsables des opérations humanitaires.',
        'duree'            => '3 jours (24h)',
        'mode'             => 'hybride',
        'tarif_en_ligne'   => 210000,
        'tarif_presentiel' => 260000,
        'slug'             => 'coordination-inter-agences-clusters-humanitaires',
    ],
    [
        'titre'            => 'Logistique Humanitaire & Gestion de la Chaîne d\'Approvisionnement d\'Urgence',
        'domaine'          => 'Humanitaire & ONG',
        'description'      => 'La logistique est souvent le facteur limitant d\'une réponse humanitaire : une mauvaise gestion des approvisionnements peut retarder l\'aide vitale à des populations en détresse. Cette formation couvre les spécificités de la chaîne d\'approvisionnement humanitaire : évaluation rapide des besoins logistiques, gestion des achats d\'urgence (procurement) en conformité avec les règles ECHO/USAID/ONU, gestion des stocks et entrepôts humanitaires, transport et distribution de l\'aide (NFI, vivres, médicaments), gestion de la flotte de véhicules, et traçabilité de l\'aide. Inclut les outils standards du secteur (Kobo Toolbox, ODK, LogAlert) et les procédures anti-fraude et de redevabilité. Adapté aux logisticiens d\'ONG, responsables de base et coordinateurs terrain.',
        'duree'            => '4 jours (32h)',
        'mode'             => 'hybride',
        'tarif_en_ligne'   => 220000,
        'tarif_presentiel' => 270000,
        'slug'             => 'logistique-humanitaire-chaine-approvisionnement-urgence',
    ],
    [
        'titre'            => 'Protection de l\'Enfance en Situation d\'Urgence — Standards & Intervention',
        'domaine'          => 'Humanitaire & ONG',
        'description'      => 'Les enfants sont les premières victimes des crises humanitaires : conflits, déplacements, catastrophes naturelles et épidémies les exposent à des risques majeurs (séparation familiale, recrutement armé, violences, traite, exploitation). Cette formation couvre le cadre de protection de l\'enfance en contexte humanitaire : les standards minimaux du cluster Protection de l\'Enfance, l\'identification et la gestion des cas (case management), la procédure de détermination du meilleur intérêt de l\'enfant (BIA/BID), la réunification familiale et le suivi post-crise, la prévention et réponse aux Violences Basées sur le Genre (VBG) touchant les enfants, et les obligations de signalement. Cas pratiques sur des contextes ouest-africains (enfants soldats, enfants séparés, enfants des rues). Adapté aux travailleurs sociaux, agents de protection et responsables programmes enfance.',
        'duree'            => '4 jours (32h)',
        'mode'             => 'hybride',
        'tarif_en_ligne'   => 210000,
        'tarif_presentiel' => 260000,
        'slug'             => 'protection-enfance-situation-urgence-standards-intervention',
    ],
    [
        'titre'            => 'Nutrition & Sécurité Alimentaire — Programmes d\'Urgence & Développement',
        'domaine'          => 'Humanitaire & ONG',
        'description'      => 'La malnutrition aiguë et l\'insécurité alimentaire touchent des millions de personnes en Afrique de l\'Ouest. Les acteurs humanitaires et de développement doivent maîtriser les outils d\'évaluation, de ciblage et d\'intervention. Cette formation couvre les indicateurs nutritionnels (MAG, MAS, retard de croissance), les enquêtes SMART et SQUEAC, la gestion de la malnutrition aiguë en ambulatoire (PCMA/PCIMAS) et en hospitalier, les distributions alimentaires (vivres, cash & voucher, filets sociaux), l\'analyse de la sécurité alimentaire (IPC, cadre harmonisé CILSS), et la programmation nutritionnelle intégrée WASH-Nutrition-Santé. Inclut les standards SPHERE et les protocoles OMS/UNICEF. Adapté aux nutritionnistes, agents de santé communautaire et coordinateurs programmes dans les contextes de crise alimentaire.',
        'duree'            => '4 jours (32h)',
        'mode'             => 'hybride',
        'tarif_en_ligne'   => 210000,
        'tarif_presentiel' => 260000,
        'slug'             => 'nutrition-securite-alimentaire-programmes-urgence-developpement',
    ],
    [
        'titre'            => 'Reporting Humanitaire & Communication Bailleurs — ECHO, OCHA & USAID',
        'domaine'          => 'Humanitaire & ONG',
        'description'      => 'Le reporting est une obligation contractuelle et un outil de redevabilité central dans le secteur humanitaire. Cette formation couvre les exigences spécifiques des principaux bailleurs humanitaires : ECHO (formulaires SitRep, rapports intermédiaires et finaux, indicateurs ECHO), OCHA (Flash Appeal, HRP, SitRep hebdomadaire), USAID/BHA (PMP, rapports FFATA), et les bailleurs bilatéraux (AFD, DFID). Programme : structurer un rapport narratif humanitaire percutant, rédiger les sections résultats/défis/leçons apprises, produire les annexes financières conformes, gérer les indicateurs du cadre logique, et communiquer efficacement en situation de crise. Exercices intensifs de rédaction sur des projets humanitaires fictifs mais réalistes. Adapté aux chargés de reporting, coordinateurs de projets et responsables communication d\'ONG.',
        'duree'            => '3 jours (24h)',
        'mode'             => 'hybride',
        'tarif_en_ligne'   => 200000,
        'tarif_presentiel' => 250000,
        'slug'             => 'reporting-humanitaire-communication-bailleurs-echo-ocha-usaid',
    ],
    [
        'titre'            => 'Droit International Humanitaire — Fondamentaux & Application sur le Terrain',
        'domaine'          => 'Humanitaire & ONG',
        'description'      => 'Le Droit International Humanitaire (DIH) — les Conventions de Genève et leurs Protocoles additionnels — constitue le cadre légal de protection des personnes en temps de conflit armé. Cette formation en donne une maîtrise opérationnelle : les principes fondamentaux du DIH (distinction, proportionnalité, précaution, humanité), les protections accordées aux combattants blessés, aux prisonniers de guerre et aux civils, le rôle et le mandat du CICR, les crimes de guerre et les mécanismes de responsabilité internationale, et l\'intégration du DIH dans les opérations humanitaires et les négociations d\'accès. Inclut les développements récents : DIH et cyberguerre, DIH et drones, DIH et groupes armés non étatiques dans le contexte sahélien et ouest-africain. Adapté aux humanitaires, juristes, militaires et diplomates.',
        'duree'            => '3 jours (24h)',
        'mode'             => 'hybride',
        'tarif_en_ligne'   => 210000,
        'tarif_presentiel' => 260000,
        'slug'             => 'droit-international-humanitaire-fondamentaux-application-terrain',
    ],
    [
        'titre'            => 'Sécurité du Personnel Humanitaire & Gestion des Risques sur le Terrain',
        'domaine'          => 'Humanitaire & ONG',
        'description'      => 'Les travailleurs humanitaires évoluent dans des environnements à risques croissants : zones de conflit, contextes de criminalité élevée, épidémies, catastrophes naturelles. Cette formation couvre les fondamentaux de la gestion de la sécurité dans les organisations humanitaires : évaluation des risques sécuritaires (matrice de risque, analyse des menaces), élaboration et mise à jour du plan de sécurité organisationnel (PSO) et des plans d\'urgence, les trois approches de sécurité (acceptation, protection, dissuasion), la négociation d\'accès humanitaire, la gestion des incidents (enlèvement, accident, agression), les premiers soins psychologiques post-incident, et les obligations légales de l\'employeur. Inclut des exercices pratiques de simulation d\'incidents et de communication de crise. Conforme aux standards UNDSS et aux bonnes pratiques INSO. Adapté aux responsables sécurité, coordinateurs terrain et tout personnel expatrié ou national déployé en zone à risque.',
        'duree'            => '3 jours (24h)',
        'mode'             => 'hybride',
        'tarif_en_ligne'   => 210000,
        'tarif_presentiel' => 260000,
        'slug'             => 'securite-personnel-humanitaire-gestion-risques-terrain',
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
