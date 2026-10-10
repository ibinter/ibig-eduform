<?php
declare(strict_types=1);
/**
 * IBIG EDUFORM — formation-detail.php
 * Aperçu public d'une formation. TDR complet réservé aux inscrits.
 */

ob_start();

/* ─── Récupérer le slug ─────────────────────────────── */
$slug = trim(strip_tags((string)($_GET['slug'] ?? '')));
if ($slug === '') {
    header('Location: /catalogue-formations.php');
    exit;
}

/* ─── Helpers ───────────────────────────────────────── */
function r5d(float $v): int { return (int)(round($v / 5000) * 5000); }
function grille_d(int $prix): array {
    $ip  = r5d($prix * 10 / 7);
    $g35 = r5d($ip * 0.70);
    $g61 = r5d($ip * 0.55);
    $g10 = r5d($ip * 0.45);
    return [
        'elearning_online'   => r5d($prix * 0.5),
        'individuel_online'  => $prix,
        'individuel_pres'    => $ip,
        'hybride'            => r5d(($prix + $ip) / 2),
        'groupe_3_5_online'  => r5d($g35 * 0.70),
        'groupe_3_5_pres'    => $g35,
        'groupe_6_10_online' => r5d($g61 * 0.70),
        'groupe_6_10_pres'   => $g61,
        'groupe_10p_online'  => r5d($g10 * 0.70),
        'groupe_10p_pres'    => $g10,
    ];
}
function fd_fcfa(int $v): string { return number_format($v, 0, ',', ' ') . ' F CFA'; }

function fd_recategorise_autres(string $name): string {
    $n = mb_strtolower($name, 'UTF-8');
    if (preg_match('/accessibilité web|architecture logicielle|clean code|développement (saas|d.application)|cybersecurity|ethical hacking|digital forensics|incident response|pentesting|soc analyst|threat intelligence|phishing|tests automatisés|looker studio|microsoft 365|microsoft project|tableau (desktop|software)|tableaux de bord|sage 100|sage états/ui', $n)) return 'Informatique & Tech';
    if (preg_match('/\b(cloud|aws|azure|docker|kubernetes|linux|devops|git\b|node\.?js|react|vue\.?js|django|laravel|\bphp\b|python|fastapi|typescript|\bsql\b|nosql|mongodb|postgresql|\bapi\b|graphql|mlops|\bsre\b|ci\/cd|ansible|terraform|low.code|no.code|webflow|flutter|kotlin|android|ios|smart contract|test logiciel|\bqa\b|arduino|\bplc\b|automate|\biot\b|capteur|impression 3d|next\.js|typescript)/ui', $n)) return 'Informatique & Tech';
    if (preg_match('/chief (data|digital)|decision making.*data|détection de fraude.*data|marketplace.*digital|livestreaming|marketplace.*africaine/ui', $n)) return 'IA & Digitalisation';
    if (preg_match('/\b(ia\b|intelligence artificielle|machine learning|deep learning|llm|nlp|chatbot|prompt|copilot|\bai\b|data (analys|scien|engineer|visual|story)|data analyst|digitalisa|digital twin|smart (farm|factor|city)|voice ai|\brag\b|pytorch|tensorflow|gemini|finops|rev.?ops|sales (auto|oper)|procurement digital)/ui', $n)) return 'IA & Digitalisation';
    if (preg_match('/conseiller financier|contrôle budgétaire|finance de projets|gestion d.actifs|gestion financière pour non|rolling forecast|facture normalisée|gestion d.une coopérative|gestion des risques stratégiques/ui', $n)) return 'Comptabilité & Finance';
    if (preg_match('/\b(comptabl|fiscal|fiscalit|budget|trésorerie|audit|contrôl.*gest|finance (dur|décen|d.entr|pour|projets|intern)|financial|fin\.\s*plan|reporting financ|consolidation|valorisation|fusions.acqui|marchés financ|brvm|gestionnaire comptable|secrétaire comptable|chef comptable|directeur financ|cfo|green finance|impact invest|lutte.*fraude financ|lbc.ft|crypto.actif|token)/ui', $n)) return 'Comptabilité & Finance';
    if (preg_match('/future of work|gestion des conflits.*climat|gestion des compétences par la data/ui', $n)) return 'GRH';
    if (preg_match('/\b(ressources humaines|\bgrh\b|recrutement|paie\b|talent|workforce|personnel admin|people analytic|employer brand|employee engag|expérience collabor|diversité.*équit|mobilité.*salar|outplacement|reskilling|upskilling|succession plan|politique salar|gestionnaire de personnel|digital hr|hr tech|assessment center)/ui', $n)) return 'GRH';
    if (preg_match('/gestion d.entreprise familiale|gestion des risques stratégiques|lean six sigma/ui', $n)) return 'Management & Leadership';
    if (preg_match('/\b(management|leadership|\bceo\b|gouvernance|okr\b|kpi.*dirigeant|intelligence écon|veille concurr|gestion de crise|direction de centre|corporate ventur|facilitation.*réunion|résilience.*chang|strategic foresight|scale.up|succession plan|mindset.*entrepreneur)/ui', $n)) return 'Management & Leadership';
    if (preg_match('/\b(droit\b|juridique|réglementaire|rgpd|protection des données|reg.?tech|arbitrage|veille légale|marchés publics|passation.*marchés|appels d.offres|lutte.*blanchiment|lbc.ft)/ui', $n)) return 'Droit & Juridique';
    if (preg_match('/\b(logistique|supply chain|achats\b|approvisionnement|procurement|gestion des stocks|strategic sourc|e.procurement)/ui', $n)) return 'Logistique & Supply Chain';
    if (preg_match('/\b(btp\b|construction|bâtiment|bim\b|plomberie|menuiserie|carrelage|peinture décor|électrotechnic|automatisation industrielle|génie (civil|sanitaire|climati)|aménagement urbain|urbanisme|permis de construire|visualisation 3d architect)/ui', $n)) return 'BTP & Construction';
    if (preg_match('/\b(agricol|agroforest|apiculture|aviculture|hévéacult|maraîch|horticulture|permaculture|smart farm|irrigation)/ui', $n)) return 'Agriculture';
    if (preg_match('/\b(pétrole|mines\b|énergie|hydrogène|hse oil|transition énergét|décarbona|carbon account|net zero|industrie 4\.0|maintenance (industr|4\.0)|robotique industrielle|électrotechnicien industr)/ui', $n)) return 'Mines, Énergie & Pétrole';
    if (preg_match('/gestion des déchets|gestion des produits chimiques|lean six sigma|écoconception/ui', $n)) return 'QHSE';
    if (preg_match('/\b(qhse|hse\b|rse\b|développement durable|éco.concep|économie circulaire|gouvernance durable|green it|reporting (esg|extra)|responsabil.*sociét|responsable (rse|développement durable)|risques climatiques|carbon|csrd|esg\b|animateur hse)/ui', $n)) return 'QHSE';
    if (preg_match('/\b(santé|médecine|nutrition|paludisme|diabète|oncologie|maladies|naturopathie|herborist|secourisme|thérapie|hypnose)/ui', $n)) return 'Santé & Pharmacie';
    if (preg_match('/\b(beauté|bien.être|onglerie|soins du chev|coiffure|esthétique)/ui', $n)) return 'Beauté & Bien-être';
    if (preg_match('/\b(tourisme|hôtellerie|gastronomie|chef de rang|restauration|mice\b|circuits touristiques|animation touristique|sommellerie)/ui', $n)) return 'Tourisme & Hôtellerie';
    if (preg_match('/gestion immobilière|gestion locative|copropriété|locatif|syndic|marchand de biens|home staging|foncier|aménagement intérieur/ui', $n)) return 'Immobilier';
    if (preg_match('/after effects|vfx|\bux\b|typographie|design graphique|\b3d\b|visualisation 3d|revit|archicad|infographie/ui', $n)) return 'Infographie & Design';
    if (preg_match('/gescom|événementiel digital|meta ads|responsable centre d.appels|relation client/ui', $n)) return 'Gestion Commerciale & Marketing';
    if (preg_match('/\b(marketing|branding|brand content|storytelling|copywriting|social selling|linkedin|image de marque|pricing|sales|e.commerce|\bcrm\b|parcours d.achat|avis clients|e.réputation|gestion des fournisseurs)/ui', $n)) return 'Gestion Commerciale & Marketing';
    if (preg_match('/la fonction d.assistant.*direction/ui', $n)) return 'Direction & Administration';
    if (preg_match('/\b(secrétaire|assistante? de direction|secrétariat|administration générale|responsable admin|coordination admin|services généraux|gestion documentaire|archivage|suivi.*admin|suivi.*budgét|responsable.*opérationnel)/ui', $n)) return 'Direction & Administration';
    if (preg_match('/\b(entrepreneuriat|startup|start.up|\bpme\b|plan d.affaires|diaspora entrepreneur|crowdfunding|financement.*pme|scale.up|commerce intra.africain|zlecaf|corporate ventur|intrapreneuriat|coopérative)/ui', $n)) return 'Entrepreneuriat';
    if (preg_match('/\b(communication (prof|managér|instit|écrite|orale)|prise de parole|art oratoire|rédaction (admin|corporate|en ligne|de cont|de propos)|création.*livre blanc|français professionnel|personal branding|marque personnelle)/ui', $n)) return 'Communication Professionnelle';
    if (preg_match('/gestion des partenariats.*bailleurs|rédaction de propositions.*ong|évaluation des acquis/ui', $n)) return 'Éducation & Formation';
    if (preg_match('/\b(formation (à distance|internat)|coaching (scolaire|pédag)|mentorat|tutorat|gamification|consultant formateur|déplacement formateur|suivi.évaluation|meal\b|kobo|renforcement des capacités)/ui', $n)) return 'Éducation & Formation';
    if (preg_match('/\b(anglais|développement (de la carrière|personnel)|découverte.*domaine|empathie|écoute active|gestion de vie|mindset|motivation.*disciplin|préparation.*concours|résilience|développ.*carrière|employabilité|gestion du burnout)/ui', $n)) return 'Développement Personnel';
    return 'Autres';
}

/* ─── Fetch API ─────────────────────────────────────── */
function fetch_formation(string $slug): ?array {
    $url = 'https://www.ibigpartners.com/api/catalogue';
    $ctx = stream_context_create([
        'http' => ['timeout' => 8, 'ignore_errors' => true, 'method' => 'GET',
                   'header'  => "Accept: application/json\r\n"],
        'ssl'  => ['verify_peer' => true],
    ]);
    $json = @file_get_contents($url, false, $ctx);
    if (!$json) return null;
    $data = json_decode($json, true);
    if (!is_array($data) || empty($data['ok'])) return null;
    foreach (($data['formations'] ?? []) as $f) {
        if (($f['slug'] ?? '') === $slug) return $f;
    }
    return null;
}

/* ─── BD locale en priorité (données maîtrisées), API en fallback ── */
$f = null;
require_once __DIR__ . '/core/config.php';
require_once __DIR__ . '/core/database.php';
try {
    $pdo  = Database::connect();
    $stmt = $pdo->prepare("
        SELECT *
        FROM formations
        WHERE slug = :slug
          AND statut = 'active'
          AND (annee IS NULL OR annee = 0 OR annee = YEAR(CURDATE()))
        LIMIT 1
    ");
    $stmt->execute([':slug' => $slug]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $f = [
            'id'          => (int)$row['id'],
            'name'        => (string)$row['titre'],
            'description' => (string)($row['description'] ?? ''),
            'price'       => (int)($row['tarif_en_ligne'] ?? 0),
            'price_pres'  => (int)($row['tarif_presentiel'] ?? 0),
            'price_hyb'   => (int)($row['tarif_hybride'] ?? 0),
            'category'    => (string)($row['domaine'] ?? 'Autres'),
            'slug'        => (string)$row['slug'],
            '_duree'      => (string)($row['duree'] ?? ''),
            '_local'      => true,
            '_obj_general'=> (string)($row['objectif_general'] ?? ''),
            '_objectifs'  => (string)($row['objectifs'] ?? ''),
            '_public'     => (string)($row['public_cible'] ?? ''),
            '_prerequis'  => (string)($row['prerequis'] ?? ''),
        ];
        // Charger les niveaux actifs de cette formation
        try {
            $niv_stmt = $pdo->prepare("
                SELECT niveau, duree_heures, tarif_en_ligne, tarif_presentiel, tarif_hybride, ordre_affichage,
                       objectifs, prerequis, public_cible
                FROM formation_niveaux
                WHERE formation_id = :fid AND statut = 'actif'
                ORDER BY ordre_affichage ASC
            ");
            $niv_stmt->execute([':fid' => (int)$row['id']]);
            $f['_niveaux'] = $niv_stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $_e) { $f['_niveaux'] = []; }
    }
} catch (\Exception $e) { /* silence */ }

/* Fallback API si pas trouvé en BD locale */
if (!$f) {
    $f = fetch_formation($slug);
}

if (!$f) {
    header('Location: /catalogue-formations.php');
    exit;
}

$_isLocal  = !empty($f['_local']);
$nom       = (string)($f['name'] ?? '');
$desc      = (string)($f['description'] ?? '');
$prix      = (int)($f['price'] ?? 0);
$cat       = (string)($f['category'] ?? 'Autres');
/* Recatégorisation Immobilier (cohérence avec catalogue-formations.php) */
$IMMO_SLUGS_FD = [
    'eduform-agent-immobilier-professionnel','eduform-charge-financement-immobilier',
    'eduform-charge-programmes-immobiliers','eduform-directeur-programme-immobilier',
    'eduform-droit-immobilier-litiges-fonciers','eduform-evaluation-expertise-immobiliere',
    'eduform-facility-management-maintenance','eduform-fiscalite-immobiliere',
    'eduform-foncier-urbanisme-droit-ci','eduform-gestionnaire-patrimoine-immobilier',
    'eduform-gestionnaire-immobilier','eduform-immobilier-durable-eco-construction',
    'eduform-immobilier-3en1','eduform-immobilier-social-logement-abordable',
    'eduform-negociateur-immobilier','eduform-promoteur-immobilier-lotissement',
    'eduform-promoteur-immobilier-junior','eduform-promotion-immobiliere',
    'eduform-specialiste-portefeuille-immobilier',
];
if (in_array($slug, $IMMO_SLUGS_FD, true)) { $cat = 'Immobilier'; }
if ($cat === 'Autres') { $cat = fd_recategorise_autres($nom); }

/* Correction tarifaire : prix formule officielle — Samedi Pro exclus, locales déjà corrigées */
$_isSamPro = stripos($nom, 'samedi') !== false;
if ($prix > 0 && !$_isSamPro && !$_isLocal) {
    if (preg_match('/\((\d+)h\)/', $nom . ' ' . $desc, $_m)) {
        $PRIX_FORMULE = [
            20 => 225000, 25 => 280000, 28 => 315000, 30 => 340000,
            35 => 395000, 40 => 450000, 45 => 505000, 55 => 620000,
            65 => 730000, 72 => 810000, 80 => 900000,
        ];
        $_h  = (int)$_m[1];
        $_pf = isset($PRIX_FORMULE[$_h])
            ? $PRIX_FORMULE[$_h]
            : (int)(round($_h * 11250 / 5000) * 5000);
        if ($prix < $_pf) $prix = $_pf;
        unset($_m, $_h, $_pf, $PRIX_FORMULE);
    }
    if ($prix < 200000) $prix = 200000;
}

$g         = $prix > 0 ? grille_d($prix) : null;
/* Formation locale : tarifs présentiel / hybride saisis en base prioritaires sur le calcul */
$_fdHideHyb = false;
if ($g && $_isLocal && (int)($f['price_pres'] ?? 0) > 0) {
    $g['individuel_pres'] = (int)$f['price_pres'];
    if ((int)($f['price_hyb'] ?? 0) > 0) {
        $g['hybride'] = (int)$f['price_hyb'];
    } else {
        $_fdHideHyb = true;
    }
}
$fdLines = static function (string $t): array {
    $t = trim(strip_tags($t));
    if ($t === '') return [];
    $parts = preg_split('/\r\n|\n|\r|\s*;\s*|\s*•\s*/u', $t) ?: [];
    return array_values(array_filter(array_map('trim', $parts), 'strlen'));
};
$fdObjGeneral = trim((string)($f['_obj_general'] ?? ''));
$fdObjectifs  = $fdLines((string)($f['_objectifs'] ?? ''));
$fdPublic     = trim(strip_tags((string)($f['_public'] ?? '')));
$fdPrerequis  = trim(strip_tags((string)($f['_prerequis'] ?? '')));

$pageTitle = $nom . ' — IBIG EDUFORM';
$ogTitle   = $nom . ' — Formation certifiante IBIG EDUFORM';
$inscUrl   = '/preinscription-generale.php?catalogue_nom=' . urlencode($nom) . '&formation_slug=' . urlencode($slug) . '&domaine=' . urlencode($cat) . ($prix > 0 ? '&catalogue_prix=' . $prix : '');
if ($_isLocal && preg_match('/^[a-z0-9][a-z0-9\-]*$/i', $slug)) {
    $inscUrl = '/preinscription/' . $slug;
}

$_descRaw  = strip_tags($desc);
// Supprimer uniquement les mentions de prix et codes internes
$_descClean = preg_replace('/\.?\s*(?:À partir de|à partir de)\s[\d\s]+(?:F\s?CFA|FCFA)[^.]*\.?/u', '', $_descRaw);
$_descClean = preg_replace('/\.?\s*Code\s*:\s*[A-Z0-9\-]+\.?/u', '', $_descClean);
$_descClean = trim(preg_replace('/\s{2,}/', ' ', $_descClean));
$ogDesc    = $_descRaw !== ''
    ? mb_substr($_descRaw, 0, 155, 'UTF-8') . (mb_strlen($_descRaw, 'UTF-8') > 155 ? '…' : '')
    : 'Formation professionnelle certifiante IBIG — ' . $cat . '. Disponible en ligne et en présentiel dans l\'espace OHADA.';
$ogUrl     = 'https://ibig-eduform.com/formation/' . $slug;
$ogImage   = 'https://ibig-eduform.com/assets/images/logo.png';

$extraHead = '<link rel="canonical" href="' . htmlspecialchars($ogUrl, ENT_QUOTES, 'UTF-8') . '">';
require_once __DIR__ . '/partials/header.php';
?>

<!-- ═══════════════════════════════════════════════════════════ STYLES -->
<style>
:root{--dk:#0a1733;--ac:#f59e0b;--ac2:#1d4ed8;--muted:#64748b;--border:#e5e7eb;--radius:12px}
*{box-sizing:border-box}

/* Hero */
.fd-hero{background:linear-gradient(135deg,#0a1733 0%,#1e3a6e 100%);color:#fff;padding:56px 20px 40px;text-align:center}
.fd-hero-badge{display:inline-block;background:rgba(245,158,11,.18);color:#f59e0b;border:1px solid rgba(245,158,11,.35);border-radius:999px;padding:4px 14px;font-size:.73rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;margin-bottom:14px}
.fd-hero h1{font-size:clamp(1.4rem,4vw,2.1rem);font-weight:900;line-height:1.2;margin:0 0 12px;max-width:820px;margin-inline:auto}
.fd-hero-sub{font-size:.9rem;opacity:.75;max-width:680px;margin:0 auto 24px}
.fd-hero-tags{display:flex;flex-wrap:wrap;gap:8px;justify-content:center}
.fd-tag{background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.2);border-radius:999px;padding:4px 12px;font-size:.72rem;font-weight:600}
.fd-back{display:inline-flex;align-items:center;gap:5px;color:rgba(255,255,255,.6);font-size:.78rem;text-decoration:none;margin-bottom:20px;transition:color .15s}
.fd-back:hover{color:#fff}

/* Layout */
.fd-body{max-width:1060px;margin:0 auto;padding:32px 16px 80px;display:grid;grid-template-columns:1fr 340px;gap:28px;align-items:start}
@media(max-width:820px){.fd-body{grid-template-columns:1fr}}

/* Sections */
.fd-main{display:flex;flex-direction:column;gap:20px}
.fd-card{background:#fff;border:1px solid var(--border);border-radius:var(--radius);padding:24px}
.fd-card h2{font-size:1rem;font-weight:800;color:var(--dk);margin:0 0 14px;display:flex;align-items:center;gap:8px}
.fd-card h2 .fd-ico{font-size:1.1rem}
.fd-card p{color:#374151;font-size:.88rem;line-height:1.65;margin:0}
.fd-card ul{margin:8px 0 0;padding-left:20px;color:#374151;font-size:.88rem;line-height:1.75}
.fd-card ul li{margin-bottom:2px}

/* Modules verrouillés */
.fd-locked{position:relative;overflow:hidden;border-radius:8px}
.fd-locked-inner{filter:blur(4px);user-select:none;pointer-events:none;opacity:.55}
.fd-locked-overlay{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;background:rgba(10,23,51,.55);border-radius:8px;color:#fff;text-align:center;padding:16px}
.fd-locked-overlay .fd-lock-icon{font-size:2rem;margin-bottom:8px}
.fd-locked-overlay p{font-size:.82rem;margin:0 0 12px;opacity:.9;max-width:260px}
.fd-locked-overlay a{background:#f59e0b;color:#0a1733;font-weight:800;font-size:.8rem;padding:8px 18px;border-radius:999px;text-decoration:none}

/* Modules list */
.fd-modules{list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:8px}
.fd-module{background:#f8fafc;border:1px solid var(--border);border-radius:8px;padding:10px 14px;display:flex;align-items:center;gap:10px;font-size:.86rem;font-weight:600;color:var(--dk)}
.fd-module::before{content:'📘';flex-shrink:0}

/* Boutons partage */
.fd-share-card{background:#fff;border:1px solid var(--border);border-radius:var(--radius);padding:18px 20px}
.fd-share-card h4{font-size:.82rem;font-weight:800;color:#0a1733;margin:0 0 12px;text-transform:uppercase;letter-spacing:.05em}
.fd-share-btns{display:flex;gap:8px;flex-wrap:wrap}
.fd-share-btn{display:inline-flex;align-items:center;gap:6px;padding:8px 14px;border-radius:999px;font-size:.78rem;font-weight:700;text-decoration:none;border:none;cursor:pointer;transition:opacity .15s}
.fd-share-btn:hover{opacity:.85}
.fd-share-wa{background:#25d366;color:#fff}
.fd-share-fb{background:#1877f2;color:#fff}
.fd-share-li{background:#0a66c2;color:#fff}
.fd-share-tw{background:#000;color:#fff}
.fd-share-cp{background:#f3f4f6;color:#374151;border:1.5px solid #e5e7eb}
.fd-share-cp.copied{background:#d1fae5;color:#065f46;border-color:#6ee7b7}

/* Sidebar */
.fd-side{display:flex;flex-direction:column;gap:18px;position:sticky;top:90px}
.fd-cta-card{background:linear-gradient(135deg,#0a1733,#1d4ed8);color:#fff;border-radius:var(--radius);padding:24px;text-align:center}
.fd-cta-card h3{font-size:1rem;font-weight:800;margin:0 0 6px}
.fd-cta-card p{font-size:.8rem;opacity:.8;margin:0 0 18px}
.fd-btn-primary{display:block;background:#f59e0b;color:#0a1733;font-weight:900;font-size:.95rem;padding:13px 20px;border-radius:999px;text-decoration:none;text-align:center;transition:opacity .15s}
.fd-btn-primary:hover{opacity:.88}
.fd-btn-sec{display:block;background:transparent;color:#fff;font-size:.8rem;border:1px solid rgba(255,255,255,.3);padding:9px 16px;border-radius:999px;text-decoration:none;text-align:center;margin-top:10px;transition:border-color .15s}
.fd-btn-sec:hover{border-color:#fff}

/* Tarifs card */
.fd-price-card{background:#fff;border:1px solid var(--border);border-radius:var(--radius);padding:20px}
.fd-price-card h3{font-size:.88rem;font-weight:800;color:var(--dk);margin:0 0 14px;display:flex;align-items:center;gap:6px}
.fd-tbl{width:100%;border-collapse:collapse;font-size:.78rem}
.fd-tbl th{background:#f1f5f9;color:var(--muted);font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;padding:6px 8px;text-align:center}
.fd-tbl th:first-child{text-align:left}
.fd-tbl td{padding:6px 8px;border-bottom:1px solid var(--border);color:#374151;text-align:center}
.fd-tbl td:first-child{text-align:left;font-weight:600;color:var(--dk)}
.fd-tbl tr:last-child td{border-bottom:none}
.fd-tbl .fd-hl td{background:#fffbeb}
.fd-tbl .fd-intra td{background:#f8fafc;font-style:italic;color:var(--muted)}
.fd-tbl-note{font-size:.68rem;color:var(--muted);margin:8px 0 0;line-height:1.5}
.fd-niv-tabs{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:12px}
.fd-niv-tab{font-size:.72rem;font-weight:700;padding:4px 14px;border-radius:999px;border:2px solid transparent;cursor:pointer;background:#f1f5f9;color:#475569}
.fd-niv-tab.active,.fd-niv-tab:focus{outline:none}
.fd-niv-tab.fd-niv-debutant.active{background:#dcfce7;color:#166534;border-color:#86efac}
.fd-niv-tab.fd-niv-intermediaire.active{background:#dbeafe;color:#1e40af;border-color:#93c5fd}
.fd-niv-tab.fd-niv-expert.active{background:#fce7f3;color:#9d174d;border-color:#f9a8d4}
.fd-niv-panel{display:none}.fd-niv-panel.active{display:block}
.fd-niv-dur{font-size:.75rem;color:var(--muted);margin-bottom:8px}

/* TDR */
.fd-tdr-card{background:#f0fdf4;border:1px solid #86efac;border-radius:var(--radius);padding:18px}
.fd-tdr-card h3{font-size:.88rem;font-weight:800;color:#15803d;margin:0 0 6px;display:flex;align-items:center;gap:6px}
.fd-tdr-card p{font-size:.78rem;color:#166534;margin:0 0 12px;line-height:1.5}
.fd-tdr-btn{display:block;background:#15803d;color:#fff;font-weight:700;font-size:.8rem;padding:9px 14px;border-radius:999px;text-decoration:none;text-align:center}

/* Contact */
.fd-contact-card{background:#fff;border:1px solid var(--border);border-radius:var(--radius);padding:18px}
.fd-contact-card h3{font-size:.88rem;font-weight:800;color:var(--dk);margin:0 0 12px}
.fd-contact-row{display:flex;align-items:center;gap:8px;font-size:.82rem;color:#374151;margin-bottom:6px}
.fd-contact-row a{color:var(--ac2);text-decoration:none;font-weight:600}

/* Compétences */
.fd-skills-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:8px;margin-top:4px}
@media(max-width:540px){.fd-skills-grid{grid-template-columns:1fr}}
.fd-skill{background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:9px 13px;font-size:.83rem;font-weight:600;color:#1e3a8a;display:flex;align-items:center;gap:7px}
.fd-skill::before{content:"✓";color:#2563eb;font-weight:900;flex-shrink:0}

/* Formateurs */
.fd-trainers{display:flex;flex-direction:column;gap:14px}
.fd-trainer{display:flex;align-items:flex-start;gap:14px}
.fd-trainer-avatar{background:linear-gradient(135deg,#0a1733,#1d4ed8);color:#fff;border-radius:12px;width:58px;height:58px;min-width:58px;display:flex;align-items:center;justify-content:center;font-size:.65rem;font-weight:800;text-align:center;line-height:1.2}
.fd-trainer-name{font-weight:800;font-size:.88rem;color:#0a1733;margin-bottom:3px}
.fd-trainer-bio{font-size:.78rem;color:#64748b;line-height:1.5}

/* Témoignages */
.fd-testimonials{display:flex;flex-direction:column;gap:14px}
.fd-testi{background:#f8fafc;border-left:3px solid #f59e0b;border-radius:0 10px 10px 0;padding:14px 16px}
.fd-testi-stars{color:#f59e0b;font-size:.9rem;margin-bottom:6px}
.fd-testi-text{font-size:.86rem;color:#374151;line-height:1.6;margin:0 0 8px;font-style:italic}
.fd-testi-author{font-size:.75rem;font-weight:700;color:#64748b}

/* FAQ */
.fd-faq{display:flex;flex-direction:column;gap:2px}
.fd-faq-item{border:1px solid var(--border);border-radius:8px;overflow:hidden;margin-bottom:6px}
.fd-faq-q{width:100%;text-align:left;background:#f8fafc;border:none;padding:12px 16px;font-size:.87rem;font-weight:700;color:#0a1733;cursor:pointer;display:flex;justify-content:space-between;align-items:center;gap:8px;transition:background .15s}
.fd-faq-q:hover{background:#f1f5f9}
.fd-faq-arrow{font-size:.7rem;transition:transform .2s;flex-shrink:0}
.fd-faq-a{display:none;padding:12px 16px;font-size:.85rem;color:#374151;line-height:1.65;border-top:1px solid var(--border);background:#fff}
.fd-faq-a.open{display:block}
</style>

<script>
function fdToggleFaq(btn) {
  var a = btn.nextElementSibling;
  var arrow = btn.querySelector('.fd-faq-arrow');
  a.classList.toggle('open');
  arrow.style.transform = a.classList.contains('open') ? 'rotate(180deg)' : '';
}
</script>

<!-- ═══════════════════════════════════════════════════════════ HERO -->
<div class="fd-hero">
  <a class="fd-back" href="/catalogue-formations.php">← Retour au catalogue</a>
  <a class="fd-hero-badge" href="/catalogue-formations.php?cat=<?= urlencode($cat) ?>" style="text-decoration:none;cursor:pointer"><?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?></a>
  <h1><?= htmlspecialchars($nom, ENT_QUOTES, 'UTF-8') ?></h1>
  <?php if ($desc): ?>
  <p class="fd-hero-sub"><?= htmlspecialchars(mb_substr($_descClean, 0, 320, 'UTF-8'), ENT_QUOTES, 'UTF-8') ?><?= mb_strlen($_descClean, 'UTF-8') > 320 ? '…' : '' ?></p>
  <?php endif; ?>
  <div class="fd-hero-tags">
    <span class="fd-tag">🔀 Hybride</span>
    <span class="fd-tag">🏛️ Présentiel</span>
    <span class="fd-tag">📜 Certificat IBIG</span>
    <span class="fd-tag">🌍 Espace OHADA</span>
  </div>
</div>

<!-- Fil d'Ariane -->
<nav aria-label="Fil d'Ariane" style="max-width:1060px;margin:16px auto 0;padding:0 16px;font-size:13px;color:#64748b">
  <a href="/" style="color:#64748b;text-decoration:none">Accueil</a>
  <span style="margin:0 6px">›</span>
  <a href="/catalogue-formations.php" style="color:#64748b;text-decoration:none">Catalogue</a>
  <span style="margin:0 6px">›</span>
  <a href="/catalogue-formations.php?cat=<?= urlencode($cat) ?>" style="color:#64748b;text-decoration:none"><?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?></a>
  <span style="margin:0 6px">›</span>
  <span style="color:#374151"><?= htmlspecialchars($nom, ENT_QUOTES, 'UTF-8') ?></span>
</nav>

<!-- ═══════════════════════════════════════════════════════════ BODY -->
<div class="fd-body">

  <!-- ── Colonne principale -->
  <div class="fd-main">

    <!-- À propos de la formation -->
    <?php if ($_descClean !== '' && mb_strlen($_descClean, 'UTF-8') > 320): ?>
    <div class="fd-card">
      <h2><span class="fd-ico">📋</span> À propos de cette formation</h2>
      <p style="line-height:1.75"><?= nl2br(htmlspecialchars($_descClean, ENT_QUOTES, 'UTF-8')) ?></p>
    </div>
    <?php endif; ?>

    <!-- Objectif général -->
    <?php $_objG = $fdObjGeneral ?: ''; ?>
    <?php if ($_objG !== ''): ?>
    <div class="fd-card">
      <h2><span class="fd-ico">🎯</span> Objectif général</h2>
      <p><?= htmlspecialchars($_objG, ENT_QUOTES, 'UTF-8') ?></p>
    </div>
    <?php endif; ?>

    <!-- Par niveau : Objectifs / Public cible / Prérequis -->
    <?php
    $_niveaux_fd = $f['_niveaux'] ?? [];
    $_niv_labels_main = ['debutant' => 'Débutant', 'intermediaire' => 'Intermédiaire', 'expert' => 'Expert'];
    $_niv_colors = [
        'debutant'      => ['bg'=>'#dcfce7','color'=>'#166534','border'=>'#86efac'],
        'intermediaire' => ['bg'=>'#dbeafe','color'=>'#1e40af','border'=>'#93c5fd'],
        'expert'        => ['bg'=>'#fce7f3','color'=>'#9d174d','border'=>'#f9a8d4'],
    ];
    // Filtrer les niveaux qui ont au moins objectifs OU public_cible OU prerequis
    $_niveaux_avec_contenu = array_filter($_niveaux_fd, function($nv) {
        return !empty(trim((string)($nv['objectifs'] ?? '')))
            || !empty(trim((string)($nv['public_cible'] ?? '')))
            || !empty(trim((string)($nv['prerequis'] ?? '')));
    });
    ?>
    <?php if (!empty($_niveaux_avec_contenu)): ?>
    <div class="fd-card" id="fd-niv-details">
      <h2><span class="fd-ico">🎓</span> Par niveau — Objectifs &amp; Public cible</h2>
      <?php if (count($_niveaux_avec_contenu) > 1): ?>
      <div class="fd-niv-tabs" style="margin-bottom:18px">
        <?php $__i = 0; foreach ($_niveaux_avec_contenu as $_nv): $__c = $_niv_colors[$_nv['niveau']] ?? $_niv_colors['intermediaire']; ?>
        <button type="button"
                class="fd-niv-tab<?= $__i === 0 ? ' active' : '' ?> fd-niv-<?= $_nv['niveau'] ?>"
                onclick="fdSelDetail(this,<?= $__i ?>)">
          <?= $_niv_labels_main[$_nv['niveau']] ?? $_nv['niveau'] ?>
        </button>
        <?php $__i++; endforeach; ?>
      </div>
      <?php endif; ?>
      <?php $__i = 0; foreach ($_niveaux_avec_contenu as $_nv):
        $_obj_nv  = trim((string)($nv['objectifs']    ?? ($nv = $_nv) ? (string)($_nv['objectifs'] ?? '') : ''));
        // Correction: use $_nv directly
        $_obj_nv  = trim((string)($_nv['objectifs']    ?? ''));
        $_pub_nv  = trim(strip_tags((string)($_nv['public_cible'] ?? '')));
        $_pre_nv  = trim(strip_tags((string)($_nv['prerequis']    ?? '')));
        $_dur_nv  = (int)($_nv['duree_heures'] ?? 0);
        $_lbl_nv  = $_niv_labels_main[$_nv['niveau']] ?? $_nv['niveau'];
        $_col_nv  = $_niv_colors[$_nv['niveau']] ?? $_niv_colors['intermediaire'];
      ?>
      <div class="fd-niv-panel<?= $__i === 0 ? ' active' : '' ?>" data-det-idx="<?= $__i ?>">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px">
          <span style="background:<?= $_col_nv['bg'] ?>;color:<?= $_col_nv['color'] ?>;border:1.5px solid <?= $_col_nv['border'] ?>;border-radius:999px;font-size:.75rem;font-weight:800;padding:4px 14px"><?= htmlspecialchars($_lbl_nv, ENT_QUOTES, 'UTF-8') ?></span>
          <?php if ($_dur_nv > 0): ?>
          <span style="font-size:.78rem;color:#64748b">⏱️ <?= $_dur_nv ?>h</span>
          <?php endif; ?>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
          <?php if ($_obj_nv !== ''): ?>
          <div style="grid-column:1/-1">
            <div style="font-weight:700;font-size:.82rem;color:#0a1733;margin-bottom:7px">✅ Objectifs</div>
            <p style="margin:0;font-size:.88rem;line-height:1.7;color:#374151"><?= nl2br(htmlspecialchars($_obj_nv, ENT_QUOTES, 'UTF-8')) ?></p>
          </div>
          <?php endif; ?>
          <?php if ($_pub_nv !== ''): ?>
          <div>
            <div style="font-weight:700;font-size:.82rem;color:#0a1733;margin-bottom:7px">👥 Public cible</div>
            <p style="margin:0;font-size:.88rem;line-height:1.65;color:#374151"><?= nl2br(htmlspecialchars($_pub_nv, ENT_QUOTES, 'UTF-8')) ?></p>
          </div>
          <?php endif; ?>
          <?php if ($_pre_nv !== ''): ?>
          <div>
            <div style="font-weight:700;font-size:.82rem;color:#0a1733;margin-bottom:7px">📋 Prérequis</div>
            <p style="margin:0;font-size:.88rem;line-height:1.65;color:#374151"><?= nl2br(htmlspecialchars($_pre_nv, ENT_QUOTES, 'UTF-8')) ?></p>
          </div>
          <?php endif; ?>
        </div>
      </div>
      <?php $__i++; endforeach; ?>
      <script>
      function fdSelDetail(btn, idx) {
        var card = btn.closest('#fd-niv-details');
        card.querySelectorAll('.fd-niv-tab').forEach(function(t){ t.classList.remove('active'); });
        card.querySelectorAll('.fd-niv-panel').forEach(function(p){ p.classList.remove('active'); });
        btn.classList.add('active');
        var panel = card.querySelector('[data-det-idx="'+idx+'"]');
        if (panel) panel.classList.add('active');
      }
      </script>
    </div>
    <?php else: ?>
    <!-- Fallback si pas de données par niveau -->
    <div class="fd-card">
      <h2><span class="fd-ico">👥</span> Public cible &amp; Prérequis</h2>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
        <div>
          <div style="font-weight:700;font-size:.82rem;color:#0a1733;margin-bottom:6px">Public cible</div>
          <?php if ($fdPublic !== ''): ?>
          <p style="margin:0"><?= htmlspecialchars($fdPublic, ENT_QUOTES, 'UTF-8') ?></p>
          <?php else: ?>
          <ul><li>Professionnels en activité</li><li>Demandeurs d'emploi qualifiés</li><li>Étudiants en fin de cursus</li><li>Entrepreneurs &amp; dirigeants</li></ul>
          <?php endif; ?>
        </div>
        <div>
          <div style="font-weight:700;font-size:.82rem;color:#0a1733;margin-bottom:6px">Prérequis</div>
          <?php if ($fdPrerequis !== ''): ?>
          <p style="margin:0"><?= htmlspecialchars($fdPrerequis, ENT_QUOTES, 'UTF-8') ?></p>
          <?php else: ?>
          <ul><li>Niveau BAC ou équivalent</li><li>Motivation et disponibilité</li><li>Accès à un ordinateur/smartphone</li></ul>
          <?php endif; ?>
        </div>
      </div>
      <?php if ($fdObjectifs): ?>
      <div style="margin-top:16px">
        <div style="font-weight:700;font-size:.82rem;color:#0a1733;margin-bottom:7px">✅ Objectifs spécifiques</div>
        <ul style="margin:0">
          <?php foreach ($fdObjectifs as $_o): ?>
          <li><?= htmlspecialchars($_o, ENT_QUOTES, 'UTF-8') ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Programme / Modules — verrouillé -->
    <div class="fd-card">
      <h2><span class="fd-ico">📚</span> Programme &amp; Modules</h2>
      <div class="fd-locked">
        <ul class="fd-modules fd-locked-inner">
          <li class="fd-module">Module 1 — Introduction et fondamentaux</li>
          <li class="fd-module">Module 2 — Méthodes et outils pratiques</li>
          <li class="fd-module">Module 3 — Études de cas et mise en situation</li>
          <li class="fd-module">Module 4 — Approfondissement et spécialisation</li>
          <li class="fd-module">Module 5 — Projet final et certification</li>
        </ul>
        <div class="fd-locked-overlay">
          <div class="fd-lock-icon">🔒</div>
          <p>Le programme complet (TDR) est réservé aux personnes inscrites.</p>
          <a href="<?= htmlspecialchars($inscUrl, ENT_QUOTES, 'UTF-8') ?>">✍️ S'inscrire pour accéder</a>
        </div>
      </div>
    </div>

    <!-- Méthodologie -->
    <div class="fd-card">
      <h2><span class="fd-ico">⚙️</span> Méthodologie</h2>
      <ul>
        <li><strong>Cours en ligne</strong> — vidéos, supports PDF, quiz interactifs</li>
        <li><strong>Séances présentiel</strong> — ateliers pratiques et mises en situation</li>
        <li><strong>Accompagnement</strong> — formateurs certifiés disponibles par WhatsApp/email</li>
        <li><strong>Évaluation continue</strong> — exercices, projets, examen final</li>
        <li><strong>Certification</strong> — délivrée par IBIG à l'issue du parcours validé</li>
      </ul>
    </div>

    <!-- FAQ -->
    <div class="fd-card">
      <h2><span class="fd-ico">❓</span> Questions fréquentes</h2>
      <div class="fd-faq">
        <div class="fd-faq-item">
          <button class="fd-faq-q" onclick="fdToggleFaq(this)">Quelle est la durée de la formation ? <span class="fd-faq-arrow">▼</span></button>
          <div class="fd-faq-a">La durée varie selon la formation (généralement entre 20h et 80h). Elle est précisée dans le programme complet (TDR) remis à l'inscription. Les formations sont disponibles en rythme intensif (quelques jours) ou étalé (plusieurs semaines).</div>
        </div>
        <div class="fd-faq-item">
          <button class="fd-faq-q" onclick="fdToggleFaq(this)">Peut-on suivre la formation à distance depuis n'importe quel pays ? <span class="fd-faq-arrow">▼</span></button>
          <div class="fd-faq-a">Oui. Les formations en ligne et hybrides sont accessibles depuis tout le continent africain et la diaspora. Vous avez uniquement besoin d'une connexion internet et d'un ordinateur ou smartphone.</div>
        </div>
        <div class="fd-faq-item">
          <button class="fd-faq-q" onclick="fdToggleFaq(this)">Le certificat IBIG est-il reconnu ? <span class="fd-faq-arrow">▼</span></button>
          <div class="fd-faq-a">Les certifications IBIG EDUFORM sont reconnues par les entreprises et institutions dans les 17 pays membres de l'espace OHADA (Afrique francophone). Chaque certificat est vérifiable en ligne sur ibig-eduform.com/verifier-certificat.php.</div>
        </div>
        <div class="fd-faq-item">
          <button class="fd-faq-q" onclick="fdToggleFaq(this)">Comment s'effectue le paiement ? <span class="fd-faq-arrow">▼</span></button>
          <div class="fd-faq-a">Nous acceptons : Mobile Money (Orange Money, Wave, MTN Money), virement bancaire, espèces en agence et paiement en ligne. Un paiement échelonné est possible (minimum 50% à l'inscription, solde à la première séance).</div>
        </div>
        <div class="fd-faq-item">
          <button class="fd-faq-q" onclick="fdToggleFaq(this)">Que se passe-t-il après l'inscription ? <span class="fd-faq-arrow">▼</span></button>
          <div class="fd-faq-a">Notre équipe vous recontacte sous 24h pour confirmer votre inscription, vous communiquer le programme détaillé (TDR), les dates de session, et les instructions de connexion ou d'accès à la salle. Vous recevez également votre facture pro forma.</div>
        </div>
      </div>
    </div>

  </div><!-- /.fd-main -->

  <!-- ── Sidebar -->
  <div class="fd-side">

    <!-- CTA inscription -->
    <div class="fd-cta-card">
      <h3>✍️ Vous êtes intéressé(e) ?</h3>
      <p>Inscrivez-vous en 2 minutes. Notre équipe vous recontacte sous 24h.</p>
      <a class="fd-btn-primary" href="<?= htmlspecialchars($inscUrl, ENT_QUOTES, 'UTF-8') ?>">S'inscrire maintenant</a>
      <a class="fd-btn-sec" href="https://wa.me/2250778882592?text=<?= urlencode('Bonjour, je suis intéressé(e) par la formation : ' . $nom) ?>" target="_blank" rel="noopener">💬 Demander via WhatsApp</a>
    </div>

    <!-- Tarifs -->
    <?php
    $niveaux_fd = $f['_niveaux'] ?? [];
    $niv_labels = ['debutant' => 'Débutant', 'intermediaire' => 'Intermédiaire', 'expert' => 'Expert'];
    if ($niveaux_fd): ?>
    <div class="fd-price-card">
      <h3>💰 Coûts &amp; Niveaux</h3>
      <?php if (count($niveaux_fd) > 1): ?>
      <div class="fd-niv-tabs">
        <?php foreach ($niveaux_fd as $i => $nv): ?>
        <button type="button" class="fd-niv-tab<?= $i === 0 ? ' active' : '' ?> fd-niv-<?= $nv['niveau'] ?>"
                onclick="fdSelectNiv(this,<?= $i ?>)"><?= $niv_labels[$nv['niveau']] ?></button>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
      <?php foreach ($niveaux_fd as $i => $nv): ?>
      <div class="fd-niv-panel<?= $i === 0 ? ' active' : '' ?>" data-niv-idx="<?= $i ?>">
        <div class="fd-niv-dur">⏱️ <?= (int)$nv['duree_heures'] ?> heures</div>
        <table class="fd-tbl">
          <thead>
            <tr><th>Modalité</th><th>💰 Tarif</th></tr>
          </thead>
          <tbody>
            <tr><td>💻 En ligne</td><td><?= fd_fcfa((int)$nv['tarif_en_ligne']) ?></td></tr>
            <?php if ((int)$nv['tarif_hybride'] > 0): ?>
            <tr><td>🔀 Hybride</td><td><?= fd_fcfa((int)$nv['tarif_hybride']) ?></td></tr>
            <?php endif; ?>
            <tr><td>🏛️ Présentiel</td><td><?= fd_fcfa((int)$nv['tarif_presentiel']) ?></td></tr>
            <tr class="fd-intra"><td colspan="2">👥 Groupe &amp; intra-entreprise — <strong>Sur devis</strong></td></tr>
          </tbody>
        </table>
      </div>
      <?php endforeach; ?>
      <p class="fd-tbl-note">Tarifs en F CFA, indicatifs. Prix par personne.</p>
    </div>
    <?php elseif ($g): ?>
    <div class="fd-price-card">
      <h3>💰 Coûts &amp; Modalités</h3>
      <table class="fd-tbl">
        <thead>
          <tr><th>Modalité</th><th>💻 En ligne</th><th>🏛️ Présentiel</th></tr>
        </thead>
        <tbody>
          <tr><td>👤 Individuel en ligne</td><td><?= fd_fcfa((int)$g['individuel_online']) ?></td><td>—</td></tr>
          <?php if (!$_fdHideHyb): ?>
          <tr><td>🔀 Hybride (En ligne et en présentiel)</td><td colspan="2" style="text-align:center"><?= fd_fcfa((int)$g['hybride']) ?></td></tr>
          <?php endif; ?>
          <tr><td>👤 Individuel présentiel</td><td>—</td><td><?= fd_fcfa((int)$g['individuel_pres']) ?></td></tr>
          <tr class="fd-intra"><td colspan="3">👥 Formation groupe &amp; intra-entreprise — <strong>Sur devis</strong></td></tr>
        </tbody>
      </table>
      <p class="fd-tbl-note">Tarifs en F CFA, indicatifs. Prix par personne.</p>
    </div>
    <?php endif; ?>

    <!-- TDR -->
    <div class="fd-tdr-card">
      <h3>📄 Programme complet (TDR)</h3>
      <p>Le document de référence (TDR) avec tous les modules, objectifs détaillés et planning vous est transmis après votre inscription.</p>
      <a class="fd-tdr-btn" href="<?= htmlspecialchars($inscUrl, ENT_QUOTES, 'UTF-8') ?>">🔒 S'inscrire pour recevoir le TDR</a>
    </div>

    <!-- Contact -->
    <div class="fd-contact-card">
      <h3>📞 Contact &amp; Informations</h3>
      <div class="fd-contact-row">📞 <a href="tel:+2250778882592">+225 07 78 88 25 92</a></div>
      <div class="fd-contact-row">📞 <a href="tel:+2250565904779">+225 05 65 90 47 79</a></div>
      <div class="fd-contact-row">📞 <a href="tel:+2252722276014">+225 27 22 27 60 14</a></div>
      <div class="fd-contact-row">📧 <a href="mailto:formation@ibig-eduform.com">formation@ibig-eduform.com</a></div>
      <div class="fd-contact-row">📧 <a href="mailto:formation@intermark-business.com">formation@intermark-business.com</a></div>
      <div class="fd-contact-row">📧 <a href="mailto:formation.ibigsarl@gmail.com">formation.ibigsarl@gmail.com</a></div>
      <div class="fd-contact-row">🕐 Lun–Ven : 8h–18h | Sam : 9h–13h</div>
    </div>

    <!-- Partage réseaux sociaux -->
    <?php
    $shareUrl   = 'https://ibig-eduform.com/formation-detail.php?slug=' . urlencode($slug);
    $shareText  = 'Découvrez cette formation certifiante : ' . $nom . ' — IBIG EDUFORM (espace OHADA)';
    $waUrl      = 'https://wa.me/?text=' . rawurlencode($shareText . "\n" . $shareUrl);
    $fbUrl      = 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode($shareUrl);
    $liUrl      = 'https://www.linkedin.com/sharing/share-offsite/?url=' . rawurlencode($shareUrl);
    $twUrl      = 'https://twitter.com/intent/tweet?text=' . rawurlencode($shareText) . '&url=' . rawurlencode($shareUrl);
    ?>
    <div class="fd-share-card">
      <h4>📤 Partager cette formation</h4>
      <div class="fd-share-btns">
        <a href="<?= htmlspecialchars($waUrl, ENT_QUOTES) ?>" target="_blank" rel="noopener" class="fd-share-btn fd-share-wa">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
          WhatsApp
        </a>
        <a href="<?= htmlspecialchars($fbUrl, ENT_QUOTES) ?>" target="_blank" rel="noopener" class="fd-share-btn fd-share-fb">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
          Facebook
        </a>
        <a href="<?= htmlspecialchars($liUrl, ENT_QUOTES) ?>" target="_blank" rel="noopener" class="fd-share-btn fd-share-li">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
          LinkedIn
        </a>
        <a href="<?= htmlspecialchars($twUrl, ENT_QUOTES) ?>" target="_blank" rel="noopener" class="fd-share-btn fd-share-tw">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.745l7.73-8.835L1.254 2.25H8.08l4.264 5.638 5.9-5.638zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
          X
        </a>
        <button type="button" class="fd-share-btn fd-share-cp" onclick="fdCopyLink(this,'<?= htmlspecialchars($shareUrl, ENT_QUOTES) ?>')">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
          Copier le lien
        </button>
      </div>
    </div>
    <script>
    function fdSelectNiv(btn, idx){
      var card = btn.closest('.fd-price-card');
      card.querySelectorAll('.fd-niv-tab').forEach(function(t){ t.classList.remove('active'); });
      card.querySelectorAll('.fd-niv-panel').forEach(function(p){ p.classList.remove('active'); });
      btn.classList.add('active');
      var panel = card.querySelector('[data-niv-idx="'+idx+'"]');
      if (panel) panel.classList.add('active');
    }
    function fdCopyLink(btn, url){
      try {
        navigator.clipboard.writeText(url).then(function(){
          btn.classList.add('copied');
          btn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Lien copié !';
          setTimeout(function(){ btn.classList.remove('copied'); btn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg> Copier le lien'; }, 2500);
        });
      } catch(e){
        var ta = document.createElement('textarea'); ta.value = url; document.body.appendChild(ta); ta.select(); document.execCommand('copy'); document.body.removeChild(ta);
        btn.textContent = '✅ Copié !';
        setTimeout(function(){ btn.textContent = '📋 Copier le lien'; }, 2500);
      }
    }
    </script>

  </div><!-- /.fd-side -->

</div><!-- /.fd-body -->

<?php
/* ─── Formations similaires ─────────────────────────── */
function fd_similaires(string $catCible, string $slugCourant, int $max = 4): array {
    $url  = 'https://www.ibigpartners.com/api/catalogue';
    $ctx  = stream_context_create(['http' => ['timeout' => 6, 'ignore_errors' => true]]);
    $json = @file_get_contents($url, false, $ctx);
    if (!$json) return [];
    $data = json_decode($json, true);
    if (!is_array($data) || empty($data['ok'])) return [];
    $result = [];
    foreach (($data['formations'] ?? []) as $f) {
        if (($f['slug'] ?? '') === $slugCourant) continue;
        $fCat = (string)($f['category'] ?? '');
        if ($fCat !== $catCible) continue;
        $result[] = $f;
        if (count($result) >= $max) break;
    }
    return $result;
}
$similaires = fd_similaires($cat, $slug);
?>

<?php if (!empty($similaires)): ?>
<div class="fd-similaires-wrap">
  <div class="fd-similaires-inner">
    <h2 class="fd-sim-title">Formations similaires en <span><?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?></span></h2>
    <p class="fd-sim-sub">D'autres formations du même domaine pourraient vous intéresser.</p>
    <div class="fd-sim-grid">
      <?php foreach ($similaires as $s):
        $sNom  = htmlspecialchars((string)($s['name']  ?? ''), ENT_QUOTES, 'UTF-8');
        $sDesc = htmlspecialchars(mb_substr(strip_tags((string)($s['description'] ?? '')), 0, 110, 'UTF-8'), ENT_QUOTES, 'UTF-8');
        $sSlug = htmlspecialchars((string)($s['slug']  ?? ''), ENT_QUOTES, 'UTF-8');
        $sPrix = (int)($s['price'] ?? 0);
        if ($sPrix > 0 && $sPrix < 200000) $sPrix = 200000;
        $sUrl  = '/formation-detail.php?slug=' . $sSlug;
      ?>
      <a href="<?= $sUrl ?>" class="fd-sim-card">
        <div class="fd-sim-cat"><?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?></div>
        <div class="fd-sim-name"><?= $sNom ?></div>
        <?php if ($sDesc): ?>
        <div class="fd-sim-desc"><?= $sDesc ?>…</div>
        <?php endif; ?>
        <div class="fd-sim-footer">
          <?php if ($sPrix > 0): ?>
          <span class="fd-sim-prix">À partir de <?= number_format($sPrix, 0, ',', ' ') ?> F CFA</span>
          <?php endif; ?>
          <span class="fd-sim-cta">Voir →</span>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
    <div style="text-align:center;margin-top:28px">
      <a href="/catalogue-formations.php?cat=<?= urlencode($cat) ?>" class="fd-sim-all">
        Voir toutes les formations <?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?> →
      </a>
    </div>
  </div>
</div>

<style>
.fd-similaires-wrap{background:#f8fafc;border-top:1px solid #e5e7eb;padding:52px 16px 64px}
.fd-similaires-inner{max-width:1060px;margin:0 auto}
.fd-sim-title{font-size:1.45rem;font-weight:900;color:#0a1733;margin:0 0 6px}
.fd-sim-title span{color:#1d4ed8}
.fd-sim-sub{font-size:.92rem;color:#64748b;margin:0 0 28px}
.fd-sim-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:16px}
.fd-sim-card{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:20px;text-decoration:none;display:flex;flex-direction:column;gap:8px;transition:.2s;color:inherit}
.fd-sim-card:hover{border-color:#1d4ed8;box-shadow:0 8px 24px rgba(29,78,216,.12);transform:translateY(-3px)}
.fd-sim-cat{font-size:.7rem;font-weight:800;color:#1d4ed8;text-transform:uppercase;letter-spacing:.05em}
.fd-sim-name{font-size:.92rem;font-weight:800;color:#0a1733;line-height:1.35}
.fd-sim-desc{font-size:.8rem;color:#64748b;line-height:1.5;flex:1}
.fd-sim-footer{display:flex;justify-content:space-between;align-items:center;margin-top:4px}
.fd-sim-prix{font-size:.78rem;font-weight:700;color:#15803d}
.fd-sim-cta{font-size:.78rem;font-weight:800;color:#1d4ed8}
.fd-sim-all{display:inline-block;padding:11px 24px;border-radius:999px;border:2px solid #1d4ed8;color:#1d4ed8;font-weight:800;font-size:.9rem;text-decoration:none;transition:.2s}
.fd-sim-all:hover{background:#1d4ed8;color:#fff}
</style>
<?php endif; ?>

<?php
/* ─── Schema.org : Course + BreadcrumbList ──────────── */
$schemaPrice = $prix > 0 ? [
    '@type'         => 'Offer',
    'price'         => $prix,
    'priceCurrency' => 'XOF',
    'availability'  => 'https://schema.org/InStock',
    'url'           => $ogUrl,
] : null;

$schemaCourse = [
    '@context'           => 'https://schema.org',
    '@type'              => 'Course',
    'name'               => $nom,
    'description'        => strip_tags($desc) ?: $ogDesc,
    'url'                => $ogUrl,
    'provider'           => [
        '@type' => 'EducationalOrganization',
        'name'  => 'IBIG EDUFORM',
        'url'   => 'https://ibig-eduform.com',
        'sameAs'=> ['https://ibig-eduform.com'],
    ],
    'educationalLevel'   => 'Professional',
    'inLanguage'         => 'fr',
    'availableLanguage'  => 'French',
    'courseMode'         => ['online', 'onsite'],
    'teaches'            => $cat,
    'image'              => $ogImage,
    'hasCourseInstance'  => [
        [
            '@type'      => 'CourseInstance',
            'courseMode' => 'online',
            'inLanguage' => 'fr',
        ],
        [
            '@type'      => 'CourseInstance',
            'courseMode' => 'onsite',
            'inLanguage' => 'fr',
        ],
    ],
];
if ($schemaPrice) {
    $schemaCourse['offers'] = $schemaPrice;
}

$schemaBreadcrumb = [
    '@context'        => 'https://schema.org',
    '@type'           => 'BreadcrumbList',
    'itemListElement' => [
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Accueil',              'item' => 'https://ibig-eduform.com/'],
        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Catalogue',             'item' => 'https://ibig-eduform.com/catalogue-formations.php'],
        ['@type' => 'ListItem', 'position' => 3, 'name' => htmlspecialchars($cat),  'item' => 'https://ibig-eduform.com/catalogue-formations.php?cat=' . urlencode($cat)],
        ['@type' => 'ListItem', 'position' => 4, 'name' => htmlspecialchars($nom),  'item' => $ogUrl],
    ],
];
?>
<script type="application/ld+json"><?= json_encode($schemaCourse,     JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
<script type="application/ld+json"><?= json_encode($schemaBreadcrumb, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>

<?php
require_once __DIR__ . '/partials/footer.php';
ob_end_flush();
