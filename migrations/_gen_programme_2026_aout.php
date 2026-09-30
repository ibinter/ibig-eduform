<?php
/* Générateur du SQL du NOUVEAU programme AOÛT -> DÉCEMBRE 2026.
   Source : IBIG_EDUFORM_Programme_Aout_Decembre_2026_v3.docx
   30 événements : 21 Certifications Certifiantes + 9 Samedis Pro.
   JUIN et JUILLET sont CONSERVES (non modifies).
   Usage : php _gen_programme_2026_aout.php > 2026_programme_aout_decembre.sql */

/* Données source partagées (certifs + samedis) */
$DATA = require __DIR__ . '/_data_programme_2026_aout.php';
$certifs = $DATA['certifs'];
$samedis = $DATA['samedis'];

/* ------------------------- Helpers ------------------------- */
function translit(string $s): string {
  $map = ['à'=>'a','â'=>'a','ä'=>'a','á'=>'a','ã'=>'a','ç'=>'c','é'=>'e','è'=>'e','ê'=>'e','ë'=>'e',
          'î'=>'i','ï'=>'i','í'=>'i','ô'=>'o','ö'=>'o','ó'=>'o','õ'=>'o','ù'=>'u','û'=>'u','ü'=>'u','ú'=>'u',
          'ñ'=>'n','’'=>' ','\''=>' ','–'=>'-','&'=>' et '];
  $s = mb_strtolower($s, 'UTF-8');
  $s = strtr($s, $map);
  return $s;
}
function slugify(string $s): string {
  $s = translit($s);
  $s = preg_replace('/[^a-z0-9]+/', '-', $s);
  return trim($s, '-');
}
function q($v): string {
  if ($v === null) return 'NULL';
  if (is_int($v)) return (string)$v;
  return "'" . str_replace("'", "''", (string)$v) . "'";
}

$moisFr = [1=>'janvier',2=>'février',3=>'mars',4=>'avril',5=>'mai',6=>'juin',7=>'juillet',
           8=>'août',9=>'septembre',10=>'octobre',11=>'novembre',12=>'décembre'];

/* Contenu riche des landings (titres de modules, objectif, public) pour enrichir
   la table formations. Sert de source aux champs modules/description/objectifs/public_cible. */
$LAND = file_exists(__DIR__ . '/_landings_content_2026_aout.php')
  ? (require __DIR__ . '/_landings_content_2026_aout.php')
  : [];

/* Extrait les titres de modules d'un contenu_programme ("MODULE 1 – Titre"). */
function titresModules(string $prog): array {
  if (preg_match_all('/^\s*MODULE\s+\d+\s*[–\-]\s*(.+?)\s*$/mu', $prog, $m)) {
    return array_map('trim', $m[1]);
  }
  return [];
}

/* Construit la liste normalisée des enregistrements a inserer/mettre a jour */
$records = [];
$seenSlug = [];
foreach ($certifs as $c) {
  [$date,$mois,$titre,$domaine,$duree,$tel,$tp,$mods] = $c;
  $records[] = [
    'date'=>$date,'mois'=>$mois,'titre'=>$titre,'domaine'=>$domaine,'duree'=>$duree,
    'tel'=>$tel,'tp'=>$tp,'modules'=>$mods,'is_samedi'=>0,
    'type'=>'Formation Certifiante',
  ];
}
foreach ($samedis as $s) {
  [$date,$mois,$titre,$domaine,$duree,$tel,$tp] = $s;
  $records[] = [
    'date'=>$date,'mois'=>$mois,'titre'=>$titre,'domaine'=>$domaine,'duree'=>$duree,
    'tel'=>$tel,'tp'=>$tp,'modules'=>[],'is_samedi'=>1,
    'type'=>'Samedi Pro',
  ];
}
/* Tri chronologique */
usort($records, fn($a,$b) => strcmp($a['date'],$b['date']));

/* Attribution des codes (avec suffixe B/C si plusieurs sessions le meme jour) */
$seenCode = [];
foreach ($records as $i => $r) {
  $code = 'IBIG-'.str_replace('-','',$r['date']);
  if (isset($seenCode[$code])) { $seenCode[$code]++; $code .= chr(64 + $seenCode[$code]); } // B, C, ...
  else { $seenCode[$code] = 1; }
  $records[$i]['code'] = $code;
}

/* Liste des codes du nouveau programme (pour l'etape 1) */
$listeCodes = implode(', ', array_map(fn($r)=>q($r['code']), $records));

$out  = "-- =====================================================================\n";
$out .= "-- IBIG EDUFORM — NOUVEAU programme officiel AOUT -> DECEMBRE 2026\n";
$out .= "-- 30 evenements : 21 Certifications Certifiantes + 9 Samedis Pro.\n";
$out .= "-- Source : IBIG_EDUFORM_Programme_Aout_Decembre_2026_v3.docx\n";
$out .= "-- JUIN et JUILLET sont CONSERVES tels quels (non modifies).\n";
$out .= "-- A importer dans phpMyAdmin (base eduform).\n";
$out .= "-- Etape 1 : desactive l'ancien programme (sauf juin, juillet et le nouveau programme).\n";
$out .= "-- Etape 2 : UPSERT par code (met a jour si present, insere sinon) — actif.\n";
$out .= "-- Idempotent : re-executable sans doublon ni suppression (preinscriptions preservees).\n";
$out .= "-- =====================================================================\n\n";

$out .= "-- ETAPE 0 — Colonne public_cible si absente\n";
$out .= "ALTER TABLE formations ADD COLUMN IF NOT EXISTS public_cible TEXT NULL AFTER modules;\n\n";

$out .= "-- ETAPE 1 — Desactiver l'ancien programme, en CONSERVANT juin, juillet\n";
$out .= "--           et les sessions du nouveau programme aout->decembre.\n";
$out .= "--           On reattribue un slug unique (archive-<id>) aux lignes archivees\n";
$out .= "--           pour LIBERER les slugs (index unique NOT NULL) que le nouveau\n";
$out .= "--           programme va reutiliser (ex. Power BI deplace du 01 au 08 aout).\n";
$out .= "UPDATE formations\n";
$out .= "   SET statut = 'inactive', slug = CONCAT('archive-', id), updated_at = NOW()\n";
$out .= " WHERE code NOT LIKE 'IBIG-202606%'\n";
$out .= "   AND code NOT LIKE 'IBIG-202607%'\n";
$out .= "   AND (code IS NULL OR code NOT IN ($listeCodes));\n\n";

$out .= "-- ETAPE 2 — Nouveau programme AOUT -> DECEMBRE 2026 (UPSERT par code)\n\n";

foreach ($records as $r) {
  $date = $r['date'];
  $isSamedi = $r['is_samedi'];
  $titre = $r['titre'];
  $base = slugify($titre);
  if (isset($seenSlug[$base])) { $seenSlug[$base]++; $slug = $base.'-session-'.$seenSlug[$base]; }
  else { $seenSlug[$base] = 1; $slug = $base; }
  $code = $r['code'];
  $type = $r['type'];

  /* Enrichissement depuis la landing : modules = titres du programme, description,
     objectifs (objectif general), public_cible. Repli sur les axes/description simple. */
  $b = $LAND[$code] ?? null;
  $titres = ($b && !empty($b['contenu_programme'])) ? titresModules($b['contenu_programme']) : [];
  if (empty($titres)) { $titres = $r['modules']; }              // repli : axes du docx (certifs)
  $modulesStr = implode('; ', $titres);
  if (!empty($titres)) {
    $description = $type.' — '.implode(', ', $titres).'.';
  } else {
    $description = $type.' — '.$titre.'.';                       // repli ultime (samedi sans landing)
  }
  $objectifsStr = ($b['objectif_general'] ?? '');
  $publicStr    = ($b['public_cible'] ?? '');

  $jour = (int)substr($date,8,2);
  $moisNum = (int)substr($date,5,2);
  $session = 'Session du '.$jour.' '.$moisFr[$moisNum].' 2026';
  $tarifEnLigne    = (int)$r['tel'];
  $tarifPresentiel = (int)$r['tp'];
  $fraisInscription = $isSamedi ? 0 : 50000;
  $duree = $r['duree'];
  $domaine = $r['domaine'];
  $type = $r['type'];

  $out .= "-- {$session} — {$titre} ({$type})\n";

  /* UPSERT partie 1 : mise a jour si le code existe deja (ancien programme / re-execution) */
  $out .= "UPDATE formations SET\n";
  $out .= "  titre=".q($titre).", slug=".q($slug).", domaine=".q($domaine).", type_certificat=".q($type).",\n";
  $out .= "  description=".q($description).", objectifs=".q($objectifsStr).", modules=".q($modulesStr).", duree=".q($duree).", mode=".q('hybride').",\n";
  $out .= "  tarif_presentiel=".q($tarifPresentiel).", tarif_en_ligne=".q($tarifEnLigne).", tarif_hybride=0, frais_inscription=".q($fraisInscription).",\n";
  $out .= "  date_debut=".q($date).", date_fin=".q($date).", mois=".q($r['mois']).", annee=2026, session_label=".q($session).",\n";
  $out .= "  statut='active', is_samedi_pro=".q($isSamedi).", public_cible=".q($publicStr).", updated_at=NOW()\n";
  $out .= " WHERE code=".q($code).";\n";

  /* UPSERT partie 2 : insertion si le code est absent */
  $out .= "INSERT INTO formations\n";
  $out .= "  (titre, slug, domaine, type_certificat, description, objectifs, modules, duree, mode,\n";
  $out .= "   tarif_presentiel, tarif_en_ligne, tarif_hybride, frais_inscription, paiement_lien,\n";
  $out .= "   date_debut, date_fin, mois, annee, session_label, statut, is_samedi_pro, code, public_cible,\n";
  $out .= "   created_at, updated_at)\n";
  $out .= "SELECT ";
  $out .= q($titre).", ".q($slug).", ".q($domaine).", ".q($type).", ";
  $out .= q($description).", ".q($objectifsStr).", ".q($modulesStr).", ".q($duree).", ".q('hybride').",\n   ";
  $out .= q($tarifPresentiel).", ".q($tarifEnLigne).", 0, ".q($fraisInscription).", ".q('').",\n   ";
  $out .= q($date).", ".q($date).", ".q($r['mois']).", 2026, ".q($session).", ".q('active').", ".q($isSamedi).", ".q($code).", ".q($publicStr).",\n   ";
  $out .= "NOW(), NOW()\n";
  $out .= "FROM DUAL\n";
  $out .= "WHERE NOT EXISTS (SELECT 1 FROM (SELECT id FROM formations WHERE code = ".q($code).") AS x);\n\n";
}

echo $out;
