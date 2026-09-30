<?php
declare(strict_types=1);

/**
 * ============================================================================
 * IBIG EDUFORM — PREINSCRIPTION SAMEDI PRO (A → Z) — PAGE UNIQUE
 * ----------------------------------------------------------------------------
 * - UI premium intégrée (dark + glass + chips)
 * - Sticky fields (valeurs conservées)
 * - Sélection formations par domaine (chips)
 * - Insertion CENTRALISÉE via enregistrer_preinscription()
 * - WhatsApp candidat + option admin (si présent)
 * - Anti double submit (redirect ?success=1)
 * - PHP 7.4+
 * ============================================================================
 */

/* =====================================================
   BOOTSTRAP + SERVICE CENTRAL
===================================================== */
require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/core/preinscription_service.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

$pdo = Database::connect();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

/* =====================================================
   HELPERS SAFE (si non existants)
===================================================== */
if (!function_exists('e')) {
  function e($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
  }
}

if (!function_exists('post')) {
  function post(string $key, $default = '') {
    return $_POST[$key] ?? $default;
  }
}

if (!function_exists('is_post')) {
  function is_post(): bool {
    return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
  }
}

if (!function_exists('clean')) {
  function clean($v): string {
    $v = is_string($v) ? trim($v) : '';
    $v = str_replace("\0", '', $v);
    return $v;
  }
}

/* =====================================================
   VARIABLES
===================================================== */
$pageTitle = "Préinscription – SAMEDI PRO | IBIG EDUFORM";

$success = false;
$error   = '';
$waLink  = '';
$waAdminLink = '';

/* Sticky */
$old = [
  'nom' => '',
  'prenoms' => '',
  'email' => '',
  'telephone' => '',
  'statut_professionnel' => '',
  'ville' => '',
  'message' => '',
  'selected_domain' => '',
];

/* Anti double submit : succès via redirect */
if (isset($_GET['success']) && $_GET['success'] === '1') {
  $success = true;

  // On peut récupérer WA en session (optionnel)
  $waLink = (string)($_SESSION['wa_link_last'] ?? '');
  $waAdminLink = (string)($_SESSION['wa_admin_link_last'] ?? '');
}

/* =====================================================
   DOMAINES & FORMATIONS SAMEDI PRO
===================================================== */
$domainesSamediPro = [

  'Gestion & Finance' => [
    'Maîtriser la trésorerie d’une PME',
    'Mettre en place une gestion financière simple et efficace',
    'Lire et comprendre les états financiers',
    'Contrôler les dépenses et sécuriser les finances',
    'Fiscalité pratique des PME',
    'Mettre en place un contrôle interne efficace',
  ],

  'Bureautique & Productivité' => [
    'Excel pour gérer efficacement son activité',
    'Excel avancé – Tableaux de bord et reporting',
    'Word professionnel',
    'PowerPoint professionnel',
    'Google Workspace – Outils collaboratifs',
  ],

  'Logiciels de gestion (SAGE)' => [
    'Sage Comptabilité',
    'Sage Paie & RH',
    'Sage Gestion Commerciale',
    'Sage Caisse – Gestion des encaissements',
  ],

  'ERP, Data & Décisionnel' => [
    'Initiation à la gestion avec Odoo',
    'Comprendre la gestion financière avec SAP FI',
    'Tableaux de bord avec Power BI',
  ],

  'Marketing & Vente' => [
    'Marketing digital pour PME',
    'Campagnes Facebook & Instagram',
    'Prospection et négociation commerciale',
    'Gestion de la relation clients (CRM)',
    'Améliorer la performance commerciale',
  ],

  'Ressources Humaines & Communication' => [
    'Structurer la gestion des ressources humaines',
    'Communication professionnelle en entreprise',
    'Rédaction de documents professionnels',
    'Prise de parole efficace',
  ],

  'Management & Leadership' => [
    'Management opérationnel',
    'Leadership et posture professionnelle',
    'Organisation personnelle et gestion du temps',
    'Productivité personnelle et professionnelle',
  ],

  'Entrepreneuriat & PME' => [
    'Créer et structurer son entreprise',
    'Business plan simplifié et opérationnel',
    'Piloter efficacement une PME',
    'Développer durablement son activité',
  ],

  'QHSE & Logistique' => [
    'Bases HSE en entreprise',
    'Évaluation des risques professionnels (ERP)',
    'Sécurité incendie et prévention',
    'Gestion des situations d’urgence',
    'Suivi et évaluation des projets',
  ],
];

/* =====================================================
   TRAITEMENT FORMULAIRE
===================================================== */
if (is_post()) {

  csrf_check();

  $nom       = clean((string)post('nom'));
  $prenoms   = clean((string)post('prenoms'));
  $email     = clean((string)post('email'));
  $telephone = clean((string)post('telephone'));
  $statutPro = clean((string)post('statut_professionnel'));
  $ville     = clean((string)post('ville'));
  $message   = clean((string)post('message'));

  $selectedDomain = clean((string)post('selected_domain'));

  $formations = post('formations', []);
  if (!is_array($formations)) $formations = [];
  $formations = array_values(array_filter(array_map('clean', $formations)));

  /* Sticky */
  $old = [
    'nom' => $nom,
    'prenom'  => $prenoms,
    'prenoms' => $prenoms,
    'email' => $email,
    'telephone' => $telephone,
    'statut_professionnel' => $statutPro,
    'ville' => $ville,
    'message' => $message,
    'selected_domain' => $selectedDomain,
  ];

  /* Validation */
  if ($nom === '' || $prenoms === '' || $telephone === '' || $statutPro === '') {
    $error = "Merci de remplir tous les champs obligatoires.";
  } elseif (empty($formations)) {
    $error = "Veuillez sélectionner au moins une formation.";
  }

  if ($error === '') {
    try {
      $domaineInteret = implode(' | ', $formations);

        $messageCentral = "Préinscription SAMEDI PRO\n";
        $messageCentral .= "Domaine: " . ($selectedDomain !== '' ? $selectedDomain : '—') . "\n";
        $messageCentral .= "Formations: {$domaineInteret}\n";
        $messageCentral .= "Statut: {$statutPro}\n";
        if ($ville !== '')   $messageCentral .= "Ville: {$ville}\n";
        if ($message !== '') $messageCentral .= "\nMessage:\n{$message}\n";
        
        // 🔐 sécurité taille DB
        $messageCentral = mb_substr($messageCentral, 0, 1500, 'UTF-8');
        
        enregistrer_preinscription($pdo, [
          'type_preinscription'  => 'samedi_pro',
        
          // ✅ CRITIQUE : jamais NULL
          'formation_id'         => 0,
        
          'domaine_interet'      => $domaineInteret,
          'nom'                  => $nom,
        
          // ✅ compatibilité service
          'prenom'               => $prenoms,
          'prenoms'              => $prenoms,
        
          'email'                => ($email !== '' ? $email : null),
          'telephone'            => $telephone,
          'mode_formation'       => 'presentiel',
          'statut_professionnel' => $statutPro,
          'objectif'             => 'monter_competence',
          'disponibilite'        => null,
          'ville'                => ($ville !== '' ? $ville : null),
          'pays'                 => 'Côte d’Ivoire',
          'source'               => 'samedi_pro',
          'message'              => $messageCentral,
          'cv_path'              => null
        ]);

      /* WhatsApp (protégé) */
      $waFile = __DIR__ . '/notifications/whatsapp.php';
      if (is_file($waFile)) {
        require_once $waFile;

        if (function_exists('whatsapp_link_candidat')) {
          $waLink = (string)whatsapp_link_candidat([
            'nom'       => $nom,
            'prenom'    => $prenoms,
            'telephone' => $telephone,
            'email'     => ($email !== '' ? $email : null),
            'formation' => 'SAMEDI PRO – ' . $domaineInteret
          ]);
        }

        if (function_exists('whatsapp_link_admin')) {
          $waAdminLink = (string)whatsapp_link_admin([
            'nom'       => $nom,
            'prenom'    => $prenoms,
            'telephone' => $telephone,
            'email'     => ($email !== '' ? $email : null),
            'formation' => 'SAMEDI PRO – ' . $domaineInteret
          ]);
        }
      }

      // stocker en session pour page succès (redirect)
      $_SESSION['wa_link_last'] = $waLink;
      $_SESSION['wa_admin_link_last'] = $waAdminLink;

      header('Location: preinscription-samedi-pro.php?success=1');
      exit;

    } catch (Throwable $e) {
      echo "<pre style='background:#111;color:#ff4d4d;padding:20px'>";
      echo "MESSAGE : " . $e->getMessage() . "\n\n";
      echo "FICHIER : " . $e->getFile() . "\n";
      echo "LIGNE   : " . $e->getLine() . "\n\n";
      echo "TRACE:\n" . $e->getTraceAsString();
      echo "</pre>";
      exit;
    }
  }
}

/* =====================================================
   HEADER
===================================================== */
include __DIR__ . '/partials/header.php';
?>

<style>
:root{
  --bg1:#0b3c5d;
  --bg2:#020617;
  --card:rgba(255,255,255,.06);
  --card2:rgba(255,255,255,.09);
  --line:rgba(255,255,255,.12);
  --txt:#e5e7eb;
  --muted:rgba(229,231,235,.78);
  --green:#25D366;
  --green2:#1ebe5d;
  --danger:#ef4444;
  --shadow:0 25px 60px rgba(0,0,0,.55);
  --radius:22px;
}
body{
  background:radial-gradient(circle at top,var(--bg1),var(--bg2));
  color:var(--txt);
  font-family:Inter,system-ui,sans-serif;
}
.wrap{
  max-width:980px;
  margin:70px auto;
  padding:0 14px;
}
.hero{
  background:linear-gradient(120deg, rgba(37,211,102,.18), rgba(255,255,255,.04));
  border:1px solid var(--line);
  border-radius:var(--radius);
  box-shadow:var(--shadow);
  padding:26px;
  margin-bottom:16px;
}
.hero h1{margin:0;font-size:28px;letter-spacing:.2px}
.hero p{margin:8px 0 0;color:var(--muted);line-height:1.5}

.card{
  background:var(--card);
  border:1px solid var(--line);
  border-radius:var(--radius);
  box-shadow:var(--shadow);
  padding:28px;
}

.grid{
  display:grid;
  grid-template-columns:repeat(2,minmax(0,1fr));
  gap:18px;
}
.full{grid-column:1/-1}

label{font-weight:800;margin-bottom:6px;display:block}
.req{color:rgba(255,255,255,.65);font-weight:700}
input,select,textarea{
  width:100%;
  padding:14px 14px;
  border-radius:14px;
  border:1px solid rgba(255,255,255,.10);
  background:rgba(2,6,23,.35);
  color:var(--txt);
  outline:none;
}
textarea{min-height:110px}
input::placeholder,textarea::placeholder{color:rgba(229,231,235,.55)}
input:focus,select:focus,textarea:focus{
  border-color:rgba(37,211,102,.55);
  box-shadow:0 0 0 3px rgba(37,211,102,.22);
  background:rgba(2,6,23,.25);
}

.btn{
  width:100%;
  border:none;
  border-radius:16px;
  padding:16px 18px;
  font-weight:950;
  cursor:pointer;
  background:var(--green);
  color:#062c1a;
  box-shadow:0 12px 28px rgba(37,211,102,.25);
  transition:all .2s ease;
}
.btn:hover{background:var(--green2); transform:translateY(-1px)}
.btn:active{transform:translateY(0)}

.alert{
  border-radius:16px;
  padding:16px 16px;
  margin-bottom:16px;
  border:1px solid var(--line);
}
.alert.error{background:rgba(239,68,68,.14);border-color:rgba(239,68,68,.35)}
.alert.success{background:rgba(34,197,94,.14);border-color:rgba(34,197,94,.35)}
.alert h2{margin:0 0 6px;font-size:18px}
.alert p{margin:0;color:var(--muted)}

.split{
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:10px;
  margin-top:12px;
}
.badge{
  display:inline-flex;
  align-items:center;
  gap:8px;
  padding:10px 12px;
  border-radius:999px;
  border:1px solid var(--line);
  background:rgba(255,255,255,.05);
  color:var(--txt);
  font-weight:900;
  font-size:13px;
}
.badge .dot{
  width:10px;height:10px;border-radius:50%;
  background:var(--green);
  box-shadow:0 0 0 4px rgba(37,211,102,.18);
}

.chips{
  display:flex;
  flex-wrap:wrap;
  gap:10px;
  padding:12px;
  border-radius:16px;
  border:1px dashed rgba(255,255,255,.14);
  background:rgba(255,255,255,.03);
}
.chip{
  user-select:none;
  padding:10px 14px;
  border-radius:999px;
  background:rgba(255,255,255,.07);
  border:1px solid rgba(255,255,255,.10);
  cursor:pointer;
  font-weight:900;
  font-size:13px;
  transition:all .18s ease;
}
.chip:hover{filter:brightness(1.06)}
.chip.active{
  background:rgba(37,211,102,.25);
  border-color:rgba(37,211,102,.55);
  color:#d1fae5;
}

.small-note{
  margin-top:8px;
  color:var(--muted);
  font-size:12px;
  line-height:1.45;
}

.links{
  margin-top:14px;
  display:flex;
  flex-wrap:wrap;
  gap:12px;
}
.link{
  display:inline-block;
  padding:12px 14px;
  border-radius:14px;
  background:rgba(255,255,255,.06);
  border:1px solid var(--line);
  color:var(--txt);
  text-decoration:none;
  font-weight:950;
}
.link:hover{filter:brightness(1.05)}
.link.green{border-color:rgba(37,211,102,.5); background:rgba(37,211,102,.12);}

@media (max-width:820px){
  .grid{grid-template-columns:1fr}
  .wrap{margin:55px auto}
  .card{padding:22px}
  .hero{padding:20px}
}

/* ==============================
   FIX SELECT / OPTION DARK MODE
============================== */

/* Select global */
select{
  appearance: none;
  -webkit-appearance: none;
  -moz-appearance: none;
  background-color: rgba(2,6,23,.35);
  color: #e5e7eb;
  cursor: pointer;
}

/* Options du dropdown */
select option{
  background-color: #020617 !important;
  color: #e5e7eb !important;
}

/* Option sélectionnée / hover */
select option:checked,
select option:hover{
  background-color: #0b3c5d !important;
  color: #ffffff !important;
}

/* Désactiver le gris Windows */
select option:disabled{
  color: rgba(255,255,255,.4) !important;
}

</style>

<main class="wrap">

  <section class="hero">
    <div class="split">
      <div>
        <h1>Préinscription — SAMEDI PRO</h1>
        <p>Une compétence. Un samedi. Un impact immédiat. Sélectionnez votre domaine puis vos formations.</p>
      </div>
      <span class="badge"><span class="dot"></span> Présentiel (samedi)</span>
    </div>
  </section>

  <section class="card">

    <?php if ($success): ?>
      <div class="alert success">
        <h2>&#10004; Préinscription envoyée</h2>
        <p>Merci. L’équipe IBIG EDUFORM vous recontacte très rapidement.</p>

        <?php if ($waLink): ?>
          <div class="links">
            <a class="link green" href="<?= e($waLink); ?>" target="_blank" rel="noopener">
              Continuer sur WhatsApp
            </a>
            <?php if ($waAdminLink): ?>
              <a class="link" href="<?= e($waAdminLink); ?>" target="_blank" rel="noopener">
                Notifier l’équipe (WhatsApp)
              </a>
            <?php endif; ?>
          </div>

          <script>
            setTimeout(function(){
              window.open("<?= e($waLink); ?>","_blank","noopener");
            }, 700);
          </script>
        <?php endif; ?>
      </div>

    <?php else: ?>

      <?php if ($error !== ''): ?>
        <div class="alert error">
          <h2>Erreur</h2>
          <p><?= e($error); ?></p>
        </div>
      <?php endif; ?>

      <form method="post" novalidate>
        <?= csrf_field(); ?>

        <div class="grid">

          <div>
            <label>Nom <span class="req">*</span></label>
            <input name="nom" required value="<?= e($old['nom']); ?>" placeholder="Votre nom">
          </div>

          <div>
            <label>Prénoms <span class="req">*</span></label>
            <input name="prenoms" required value="<?= e($old['prenoms']); ?>" placeholder="Vos prénoms">
          </div>

          <div>
            <label>Email</label>
            <input type="email" name="email" value="<?= e($old['email']); ?>" placeholder="ex: nom@domaine.com">
          </div>

          <div>
            <label>Téléphone (WhatsApp) <span class="req">*</span></label>
            <input name="telephone" required value="<?= e($old['telephone']); ?>" placeholder="ex: +225 07 00 00 00 00">
          </div>

          <div>
            <label>Statut professionnel <span class="req">*</span></label>
            <select name="statut_professionnel" required>
              <option value="">— Choisir —</option>
              <option value="salarie"      <?= $old['statut_professionnel']==='salarie'?'selected':''; ?>>Salarié</option>
              <option value="entrepreneur" <?= $old['statut_professionnel']==='entrepreneur'?'selected':''; ?>>Entrepreneur</option>
              <option value="etudiant"     <?= $old['statut_professionnel']==='etudiant'?'selected':''; ?>>Étudiant</option>
              <option value="autre"        <?= $old['statut_professionnel']==='autre'?'selected':''; ?>>Autre</option>
            </select>
            <div class="small-note">Ce champ permet à IBIG EDUFORM d’adapter la proposition et le niveau.</div>
          </div>

          <div>
            <label>Ville</label>
            <input name="ville" value="<?= e($old['ville']); ?>" placeholder="ex: Abidjan">
          </div>

          <div class="full">
            <label>Domaine SAMEDI PRO <span class="req">*</span></label>
            <select id="domaine" aria-label="Domaine">
              <option value="">— Choisir —</option>
            </select>
            <div class="small-note">Après choix du domaine, sélectionnez vos formations via les chips.</div>

            <input type="hidden" name="selected_domain" id="selected_domain" value="<?= e($old['selected_domain']); ?>">
          </div>

          <div class="full">
            <label>Formations à sélectionner <span class="req">*</span></label>
            <div class="chips" id="chips">
              <div class="small-note">Choisissez un domaine pour afficher les formations.</div>
            </div>
            <div id="hidden"></div>
          </div>

          <div class="full">
            <label>Message (optionnel)</label>
            <textarea name="message" placeholder="Précisions utiles..."><?= e($old['message']); ?></textarea>
          </div>

          <div class="full">
            <button class="btn" type="submit">Envoyer ma préinscription</button>
          </div>

        </div>
      </form>

    <?php endif; ?>

  </section>

</main>

<script>
(function(){
  const DATA = <?= json_encode($domainesSamediPro, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

  const domaine = document.getElementById('domaine');
  const chips = document.getElementById('chips');
  const hidden = document.getElementById('hidden');
  const selectedDomain = document.getElementById('selected_domain');

  // remplir select
  Object.keys(DATA).forEach(d=>{
    const o=document.createElement('option');
    o.value=d; o.textContent=d;
    domaine.appendChild(o);
  });

  // si sticky domaine
  if (selectedDomain && selectedDomain.value) {
    domaine.value = selectedDomain.value;
  }

  function sync(){
    hidden.innerHTML = '';
    document.querySelectorAll('.chip.active').forEach(c=>{
      const i = document.createElement('input');
      i.type='hidden';
      i.name='formations[]';
      i.value=c.getAttribute('data-value') || c.textContent;
      hidden.appendChild(i);
    });
  }

  function render(domainName){
    chips.innerHTML='';

    const list = (DATA[domainName] || []);
    if (!domainName) {
      const div=document.createElement('div');
      div.className='small-note';
      div.textContent='Choisissez un domaine pour afficher les formations.';
      chips.appendChild(div);
      hidden.innerHTML='';
      return;
    }

    if (selectedDomain) selectedDomain.value = domainName;

    list.forEach(f=>{
      const c = document.createElement('div');
      c.className='chip';
      c.textContent=f;
      c.setAttribute('data-value', f);

      c.addEventListener('click', function(){
        c.classList.toggle('active');
        sync();
      });

      chips.appendChild(c);
    });

    // garder sélection après POST (basé sur hidden inputs existants)
    const already = new Set();
    document.querySelectorAll('#hidden input[name="formations[]"]').forEach(i=>already.add(i.value));

    // si page rechargée sans hidden (POST failed) on peut lire depuis PHP (non dispo ici)
    // => on garde le comportement : l'utilisateur re-clique, c'est ok.

    sync();
  }

  domaine.addEventListener('change', function(){
    render(domaine.value);
  });

  // initial
  render(domaine.value);
})();
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>