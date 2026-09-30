<?php
declare(strict_types=1);

/* =====================================================
   BOOTSTRAP & DB
===================================================== */
require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/core/preinscription_service.php'; // <= CENTRAL

$pdo = Database::connect();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

/* =====================================================
   SAFE HELPERS (si e() n’existe pas dans ton core)
===================================================== */
if (!function_exists('e')) {
  function e(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
}

/* =====================================================
   VARIABLES
===================================================== */
$success = false;
$error   = '';
$waLinkAdmin = '';

/* Anti double submit : succès via redirect */
if (isset($_GET['success']) && $_GET['success'] === '1') {
  $success = true;
}

/* =====================================================
   TRAITEMENT FORMULAIRE
===================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  csrf_check();

  // Identité
  $type_demandeur = trim((string)($_POST['type_demandeur'] ?? ''));
  $nom            = trim((string)($_POST['nom'] ?? ''));
  $prenoms        = trim((string)($_POST['prenoms'] ?? ''));
  $email          = trim((string)($_POST['email'] ?? ''));
  $telephone      = trim((string)($_POST['telephone'] ?? ''));

  // Structure
  $structure_nom  = trim((string)($_POST['structure_nom'] ?? ''));
  $fonction       = trim((string)($_POST['fonction'] ?? ''));
  $secteur        = trim((string)($_POST['secteur'] ?? ''));

  // Besoin formation
  $domaine        = trim((string)($_POST['domaine_formation'] ?? ''));
  $theme          = trim((string)($_POST['theme_formation'] ?? ''));
  $objectif       = trim((string)($_POST['objectif'] ?? ''));
  $niveau         = (($_POST['niveau'] ?? '') !== '') ? (string)$_POST['niveau'] : null;

  $participants   = (($_POST['nombre_participants'] ?? '') !== '')
      ? (int)$_POST['nombre_participants']
      : null;

  $mode           = (($_POST['mode_formation'] ?? '') !== '') ? (string)$_POST['mode_formation'] : null;
  $lieu           = trim((string)($_POST['lieu'] ?? ''));
  $pays_formation = trim((string)($_POST['pays_formation'] ?? ''));

  $duree          = trim((string)($_POST['duree'] ?? ''));
  $periode        = trim((string)($_POST['periode_souhaitee'] ?? ''));

  // Budget & urgence
  $budget         = trim((string)($_POST['budget'] ?? ''));
  $urgence        = (($_POST['urgence'] ?? '') !== '') ? (string)$_POST['urgence'] : null;

  // Message
  $message        = trim((string)($_POST['message'] ?? ''));

  /* =========================
     VALIDATION MINIMALE
  ========================= */
  if ($type_demandeur === '' || $nom === '' || $prenoms === '' || $telephone === '' || $objectif === '') {
    $error = "Merci de remplir tous les champs obligatoires.";
  }

  /* =========================
     INSERTION SQL + CENTRALISATION
  ========================= */
  if ($error === '') {
    try {

      // Enrichir lieu si international
      $lieuFinal = $lieu;
      if (($mode ?? '') === 'international' && $pays_formation !== '') {
        $lieuFinal = trim($lieuFinal . ' — Pays: ' . $pays_formation);
      }

      // Transaction : demandes_formation + preinscriptions
      $pdo->beginTransaction();

      /* =========================
         1) TABLE METIER : demandes_formation
      ========================= */
      $stmt = $pdo->prepare("
        INSERT INTO demandes_formation (
          type_demandeur,
          nom,
          prenoms,
          email,
          telephone,
          structure_nom,
          fonction,
          secteur,
          domaine_formation,
          theme_formation,
          objectif,
          niveau,
          nombre_participants,
          mode_formation,
          lieu,
          duree,
          periode_souhaitee,
          budget,
          urgence,
          message,
          source,
          statut,
          ip_address,
          created_at
        ) VALUES (
          :type_demandeur,
          :nom,
          :prenoms,
          :email,
          :telephone,
          :structure_nom,
          :fonction,
          :secteur,
          :domaine,
          :theme,
          :objectif,
          :niveau,
          :participants,
          :mode,
          :lieu,
          :duree,
          :periode,
          :budget,
          :urgence,
          :message,
          'site_web',
          'nouvelle',
          :ip,
          NOW()
        )
      ");

      $stmt->execute([
        'type_demandeur' => $type_demandeur,
        'nom'            => $nom,
        'prenoms'        => $prenoms,
        'email'          => ($email !== '' ? $email : null),
        'telephone'      => $telephone,

        'structure_nom'  => ($structure_nom !== '' ? $structure_nom : null),
        'fonction'       => ($fonction !== '' ? $fonction : null),
        'secteur'        => ($secteur !== '' ? $secteur : null),

        'domaine'        => ($domaine !== '' ? $domaine : null),
        'theme'          => ($theme !== '' ? $theme : null),
        'objectif'       => $objectif,
        'niveau'         => $niveau,
        'participants'   => $participants,
        'mode'           => $mode,
        'lieu'           => ($lieuFinal !== '' ? $lieuFinal : null),

        'duree'          => ($duree !== '' ? $duree : null),
        'periode'        => ($periode !== '' ? $periode : null),

        'budget'         => ($budget !== '' ? $budget : null),
        'urgence'        => $urgence,
        'message'        => ($message !== '' ? $message : null),

        'ip'             => $_SERVER['REMOTE_ADDR'] ?? null
      ]);

      /* =========================
         2) TABLE CENTRALISÉE : preinscriptions
         => Type : sur_mesure
         => Domaine_interet : Domaine | Thème
         => Message enrichi (tous champs utiles)
      ========================= */
      $domaineInteret = trim($domaine);
      if ($theme !== '') {
        $domaineInteret = trim($domaineInteret . ' | ' . $theme);
      }
      if ($domaineInteret === '') {
        $domaineInteret = 'Formation sur mesure';
      }

      $messageCentral = "Demande formation sur mesure\n";
      $messageCentral .= "Type demandeur: {$type_demandeur}\n";
      $messageCentral .= "Structure: " . ($structure_nom !== '' ? $structure_nom : '—') . "\n";
      $messageCentral .= "Fonction: " . ($fonction !== '' ? $fonction : '—') . "\n";
      $messageCentral .= "Secteur: " . ($secteur !== '' ? $secteur : '—') . "\n";
      $messageCentral .= "Domaine: " . ($domaine !== '' ? $domaine : '—') . "\n";
      $messageCentral .= "Thème: " . ($theme !== '' ? $theme : '—') . "\n";
      $messageCentral .= "Niveau: " . ($niveau ?: '—') . "\n";
      $messageCentral .= "Participants: " . ($participants ? (string)$participants : '—') . "\n";
      $messageCentral .= "Mode: " . ($mode ?: '—') . "\n";
      $messageCentral .= "Lieu: " . ($lieuFinal !== '' ? $lieuFinal : '—') . "\n";
      $messageCentral .= "Durée: " . ($duree !== '' ? $duree : '—') . "\n";
      $messageCentral .= "Période: " . ($periode !== '' ? $periode : '—') . "\n";
      $messageCentral .= "Budget: " . ($budget !== '' ? $budget : '—') . "\n";
      $messageCentral .= "Urgence: " . ($urgence ?: '—') . "\n";
      if ($message !== '') {
        $messageCentral .= "\nMessage:\n{$message}\n";
      }

      // IMPORTANT : utilise bien ton service central
      enregistrer_preinscription($pdo, [
        'type_preinscription' => 'sur_mesure',
        'formation_id'        => null,
        'domaine_interet'     => $domaineInteret,

        'nom'                 => $nom,
        'prenoms'             => $prenoms,
        'email'               => ($email !== '' ? $email : null),
        'telephone'           => $telephone,

        'mode_formation'      => $mode,
        'statut_professionnel'=> null,
        'niveau'              => $niveau,
        'objectif'            => 'devis_sur_mesure',
        'disponibilite'       => null,

        'ville'               => ($lieuFinal !== '' ? $lieuFinal : null),
        'pays'                => 'Côte d’Ivoire',

        'source'              => 'besoin_formation',
        'statut'              => 'nouvelle',
        'message'             => $messageCentral,
        'cv_path'             => null
      ]);

      $pdo->commit();

      /* =========================
         NOTIF ADMIN (EMAIL + WHATSAPP)
      ========================= */
      $adminEmail = 'formation@intermark-business.com';
      $adminWhats = '2250778882592';

      $who = trim($prenoms . ' ' . $nom);

      $txt = "Nouvelle demande de formation\n\n";
      $txt .= "Type: " . $type_demandeur . "\n";
      $txt .= "Demandeur: " . $who . "\n";
      $txt .= "Telephone: " . $telephone . "\n";
      $txt .= "Email: " . ($email !== '' ? $email : '—') . "\n";
      $txt .= "Structure: " . ($structure_nom !== '' ? $structure_nom : '—') . "\n";
      $txt .= "Fonction: " . ($fonction !== '' ? $fonction : '—') . "\n";
      $txt .= "Secteur: " . ($secteur !== '' ? $secteur : '—') . "\n\n";
      $txt .= "Domaine: " . ($domaine !== '' ? $domaine : '—') . "\n";
      $txt .= "Theme: " . ($theme !== '' ? $theme : '—') . "\n";
      $txt .= "Objectif: " . $objectif . "\n";
      $txt .= "Niveau: " . ($niveau ?: '—') . "\n";
      $txt .= "Mode: " . ($mode ?: '—') . "\n";
      $txt .= "Participants: " . ($participants ? (string)$participants : '—') . "\n";
      $txt .= "Lieu: " . ($lieuFinal !== '' ? $lieuFinal : '—') . "\n";
      $txt .= "Duree: " . ($duree !== '' ? $duree : '—') . "\n";
      $txt .= "Periode: " . ($periode !== '' ? $periode : '—') . "\n";
      $txt .= "Budget: " . ($budget !== '' ? $budget : '—') . "\n";
      $txt .= "Urgence: " . ($urgence ?: '—') . "\n\n";
      $txt .= "Message: " . ($message !== '' ? $message : '—') . "\n";

      $subject = "Nouvelle demande de formation - " . $who;

      $headers  = "MIME-Version: 1.0\r\n";
      $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
      $headers .= "From: IBIG EDUFORM <no-reply@ibig-eduform.com>\r\n";

      @mail($adminEmail, $subject, $txt, $headers);

      $waLinkAdmin = "https://wa.me/" . $adminWhats . "?text=" . rawurlencode($txt);

      // Redirect anti double-submit
      header('Location: besoin-formation.php?success=1');
      exit;

    } catch (Throwable $e) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      error_log('[BESOIN FORMATION] ' . $e->getMessage());
      $error = "Une erreur technique est survenue. Merci de r&eacute;essayer.";
    }
  }
}

/* =====================================================
   HEADER
===================================================== */
$pageTitle = "Besoin de formation &ndash; IBIG EDUFORM";
include __DIR__ . '/partials/header.php';
?>

<style>
/* =====================================================
   BESOIN DE FORMATION — REFONTE PREMIUM (clair, charte)
   (classes inchangées : seul le style évolue)
===================================================== */
body{
  background:
    radial-gradient(1000px 480px at 12% 0%, rgba(31,63,224,.10), transparent 60%),
    radial-gradient(900px 460px at 100% 0%, rgba(232,36,44,.07), transparent 58%),
    linear-gradient(180deg,#eef2fc,#f6f8fe);
  color:#0e1530;
  font-family:Inter,"Plus Jakarta Sans",system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;
}
.container{
  max-width:940px;
  margin:46px auto 90px;
  background:#ffffff;
  padding:40px 40px 44px;
  border-radius:24px;
  border:1px solid rgba(14,21,48,.10);
  box-shadow:0 28px 70px rgba(14,21,48,.14);
  position:relative;
  overflow:hidden;
}
.container::before{
  content:"";position:absolute;left:0;right:0;top:0;height:5px;
  background:linear-gradient(90deg,#1f3fe0,#4f6bff,#e8242c);
}
h1{
  margin:6px 0 8px;
  font-size:clamp(24px,3.4vw,32px);
  font-weight:800;
  letter-spacing:-.5px;
  color:#0e1530;
}
h2{color:#0e1530;font-weight:800}
label{
  font-weight:700;
  margin-bottom:7px;
  display:block;
  color:#334155;
  font-size:13.5px;
}
label .muted{color:#e8242c;font-weight:900}   /* astérisque requis en rouge */
input,select,textarea{
  width:100%;
  padding:13px 14px;
  border-radius:12px;
  border:1px solid #e5e7eb;
  background:#fff;
  color:#0e1530;
  margin-bottom:18px;
  outline:none;
  font-size:15px;
  font-family:inherit;
  transition:border-color .15s ease, box-shadow .15s ease;
}
input::placeholder,textarea::placeholder{color:#9aa3b5}
input:focus,select:focus,textarea:focus{
  border-color:#1f3fe0;
  box-shadow:0 0 0 3px rgba(31,63,224,.12);
}
textarea{min-height:120px;resize:vertical}
button[type="submit"]{
  width:100%;
  background:linear-gradient(135deg,#1f3fe0,#4f6bff);
  color:#fff;
  font-weight:800;
  padding:16px;
  border-radius:14px;
  border:none;
  cursor:pointer;
  font-size:16px;
  box-shadow:0 16px 34px rgba(31,63,224,.30);
  transition:transform .16s ease, filter .16s ease;
}
button[type="submit"]:hover{transform:translateY(-2px);filter:brightness(1.03)}
.success{
  background:#ecfdf3;
  border:1px solid #abefc6;
  color:#166534;
  padding:24px;
  border-radius:16px;
}
.success h2{color:#15803d;margin:0 0 10px}
.error{
  background:#fef2f2;
  border:1px solid #fecaca;
  color:#991b1b;
  padding:14px 16px;
  border-radius:14px;
  margin-bottom:18px;
  font-weight:600;
}
.muted{color:#5b647c}
.hidden{display:none}
.grid{
  display:grid;
  grid-template-columns:repeat(2,minmax(0,1fr));
  gap:0 18px;
}
.full{grid-column:1/-1}
@media (max-width:760px){
  .container{margin:26px 14px 70px;padding:26px 22px 30px}
  .grid{grid-template-columns:1fr}
}
.small-note{
  font-size:12px;
  color:#64748b;
  margin-top:-12px;
  margin-bottom:16px;
}

/* mini CTA admin (après succès) */
.admin-cta{
  margin-top:14px;
  display:inline-flex;
  align-items:center;
  gap:8px;
  padding:11px 16px;
  border-radius:12px;
  background:#dcfce7;
  border:1px solid #86efac;
  color:#15803d;
  font-weight:800;
  text-decoration:none;
}
.admin-cta:hover{filter:brightness(1.02)}
</style>

<main>
  <div class="container">

    <?php if ($success): ?>

      <div class="success">
        <h2 style="margin:0 0 10px">Demande envoy&eacute;e avec succ&egrave;s</h2>
        <p class="muted" style="margin:0">
          Votre besoin en formation a &eacute;t&eacute; transmis.<br>
          Un conseiller IBIG EDUFORM vous contactera rapidement.
        </p>

        <?php if ($waLinkAdmin): ?>
          <div style="margin-top:14px">
            <a class="admin-cta" href="<?= e($waLinkAdmin); ?>" target="_blank" rel="noopener">
              Notifier l’&eacute;quipe sur WhatsApp
            </a>
          </div>
        <?php endif; ?>
      </div>

    <?php else: ?>

      <?php if ($error): ?>
        <div class="error"><?= e($error); ?></div>
      <?php endif; ?>

      <h1>Expression de besoin en formation</h1>
      <p class="muted" style="margin-top:-6px;margin-bottom:18px">
        Remplissez ce formulaire pour recevoir une proposition de programme et un devis.
      </p>

      <form method="post" novalidate>
        <?= csrf_field(); ?>

        <div class="grid">

          <div class="full">
            <label>Type de demandeur <span class="muted">*</span></label>
            <select name="type_demandeur" id="type_demandeur" required>
              <option value="">Choisir</option>
              <option value="particulier">Particulier</option>
              <option value="entreprise">Entreprise</option>
              <option value="groupe">Groupe</option>
              <option value="association">Association</option>
              <option value="ong">ONG</option>
              <option value="institution">Institution</option>
            </select>
          </div>

          <div>
            <label>Nom <span class="muted">*</span></label>
            <input name="nom" required>
          </div>

          <div>
            <label>Pr&eacute;noms <span class="muted">*</span></label>
            <input name="prenoms" required>
          </div>

          <div>
            <label>Email</label>
            <input type="email" name="email" placeholder="ex: nom@domaine.com">
          </div>

          <div>
            <label>T&eacute;l&eacute;phone (WhatsApp) <span class="muted">*</span></label>
            <input name="telephone" inputmode="tel" required placeholder="ex: +225 07 00 00 00 00">
          </div>

          <div id="bloc-structure" class="full hidden">
            <label>Informations sur la structure</label>
            <div class="small-note">Ces champs sont recommand&eacute;s pour les entreprises/ONG/associations/institutions.</div>

            <div class="grid">
              <div>
                <label>Nom de la structure</label>
                <input name="structure_nom" placeholder="ex: Nom de l'entreprise / ONG">
              </div>

              <div>
                <label>Fonction du demandeur</label>
                <input name="fonction" placeholder="ex: DRH, Responsable formation, Directeur...">
              </div>

              <div class="full">
                <label>Secteur d&rsquo;activit&eacute;</label>
                <input name="secteur" placeholder="ex: BTP, Finance, Industrie, Services...">
              </div>
            </div>
          </div>

          <div>
            <label>Domaine de formation</label>
            <input name="domaine_formation" value="<?= htmlspecialchars((string)($_GET['domaine'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="ex: RH, Comptabilit&eacute;, QHSE, Logistique...">
          </div>

          <div>
            <label>Th&egrave;me pr&eacute;cis souhait&eacute;</label>
            <input name="theme_formation" value="<?= htmlspecialchars((string)($_GET['theme'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="ex: Paie, Audit interne, Excel avanc&eacute;...">
          </div>

          <div class="full">
            <label>Objectif de la formation <span class="muted">*</span></label>
            <textarea name="objectif" required placeholder="D&eacute;crivez le r&eacute;sultat attendu (comp&eacute;tences, outils, livrables, etc.)"></textarea>
          </div>

          <div>
            <label>Niveau souhait&eacute;</label>
            <select name="niveau">
              <option value="">Choisir</option>
              <option value="debutant">D&eacute;butant</option>
              <option value="intermediaire">Interm&eacute;diaire</option>
              <option value="avance">Avanc&eacute;</option>
            </select>
          </div>

          <div>
            <label>Nombre de participants</label>
            <input type="number" name="nombre_participants" min="1" placeholder="ex: 12">
          </div>

          <div class="full">
            <label>Mode de formation</label>
            <select name="mode_formation" id="mode_formation">
              <option value="">Choisir</option>

              <option value="en_ligne">En ligne</option>
              <option value="presentiel">Pr&eacute;sentiel</option>
              <option value="hybride">Hybride</option>

              <option value="intra_entreprise">Intra-entreprise</option>
              <option value="inter_entreprise">Inter-entreprise</option>

              <option value="international">International</option>
            </select>
          </div>

          <div id="bloc-lieu" class="full hidden">
            <label id="label-lieu">Lieu (si applicable)</label>
            <input name="lieu" id="lieu" placeholder="ex: Abidjan, Cocody / Site client">
          </div>

          <div id="bloc-pays" class="full hidden">
            <label>Pays (formation internationale)</label>
            <input name="pays_formation" id="pays_formation" placeholder="ex: France, Maroc, Canada...">
          </div>

          <div>
            <label>Dur&eacute;e souhait&eacute;e</label>
            <input name="duree" placeholder="ex: 2 jours / 1 semaine / 2 mois">
          </div>

          <div>
            <label>P&eacute;riode souhait&eacute;e</label>
            <input name="periode_souhaitee" placeholder="ex: F&eacute;vrier 2026 / Semaine du 10/02">
          </div>

          <div>
            <label>Budget estimatif</label>
            <input name="budget" placeholder="ex: 300 000 FCFA">
          </div>

          <div>
            <label>Urgence</label>
            <select name="urgence">
              <option value="">Choisir</option>
              <option value="immediate">Imm&eacute;diate</option>
              <option value="court_terme">Court terme</option>
              <option value="moyen_terme">Moyen terme</option>
            </select>
          </div>

          <div class="full">
            <label>Message compl&eacute;mentaire</label>
            <textarea name="message" placeholder="Pr&eacute;cisions utiles (contraintes, r&eacute;f&eacute;rentiel, outils, dates, etc.)"></textarea>
          </div>

          <div class="full">
            <button type="submit">Envoyer la demande</button>
          </div>

        </div>
      </form>

    <?php endif; ?>

  </div>
</main>

<script>
(function(){
  var typeSelect = document.getElementById('type_demandeur');
  var blocStructure = document.getElementById('bloc-structure');

  var modeSelect = document.getElementById('mode_formation');
  var blocLieu   = document.getElementById('bloc-lieu');
  var blocPays   = document.getElementById('bloc-pays');

  function toggleStructure(){
    if (!typeSelect || !blocStructure) return;
    var v = typeSelect.value;
    if (v === 'entreprise' || v === 'association' || v === 'ong' || v === 'institution') {
      blocStructure.classList.remove('hidden');
    } else {
      blocStructure.classList.add('hidden');
    }
  }

  function toggleMode(){
    if (!modeSelect) return;
    var m = modeSelect.value;

    if (blocLieu) blocLieu.classList.add('hidden');
    if (blocPays) blocPays.classList.add('hidden');
    if (!m) return;

    if (m === 'presentiel' || m === 'hybride' || m === 'intra_entreprise' || m === 'inter_entreprise' || m === 'international') {
      if (blocLieu) blocLieu.classList.remove('hidden');
    }
    if (m === 'international') {
      if (blocPays) blocPays.classList.remove('hidden');
    }
  }

  if (typeSelect) {
    typeSelect.addEventListener('change', toggleStructure);
    toggleStructure();
  }
  if (modeSelect) {
    modeSelect.addEventListener('change', toggleMode);
    toggleMode();
  }
})();
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>