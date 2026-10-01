<?php
require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../auth/middleware.php';

/* ===============================
   SÉCURITÉ
================================ */
Middleware::requireAuth();

if (($_SESSION['user']['role'] ?? '') !== 'super_admin') {
  http_response_code(403);
  die('Acc&egrave;s refus&eacute;');
}

/* ===============================
   CONFIG PAGE
================================ */
$pageTitle  = "Param&egrave;tres g&eacute;n&eacute;raux";
$activeMenu = "settings";

$pdo = Database::connect();

$errors  = [];
$success = false;

/* ===============================
   CHARGER PARAMÈTRES EXISTANTS
================================ */
$settings = $pdo->query("
  SELECT setting_key, setting_value
  FROM settings
")->fetchAll(PDO::FETCH_KEY_PAIR);

/* Valeurs par d&eacute;faut */
$appName     = $settings['app_name']     ?? APP_NAME;
$adminEmail  = $settings['admin_email']  ?? '';
$adminPhone  = $settings['admin_phone']  ?? '';
$maintenance = $settings['maintenance'] ?? 'off';

/* Contacts / Chiffres / Promotions (avec valeurs par défaut) */
$contactEmail   = $settings['contact_email']       ?? 'formation@intermark-business.com';
$contactPhones  = $settings['contact_phones']      ?? "+225 07 78 88 25 92\n+225 07 78 88 25 92";
$whatsappNumber = $settings['whatsapp_number']     ?? '2250778882592';
$siteInstit     = $settings['site_institutionnel'] ?? 'intermark-business.com';
$statApprenants = $settings['stat_apprenants']     ?? '1 000+';
$statSatisf     = $settings['stat_satisfaction']   ?? '+90%';
$statExp        = $settings['stat_experience']     ?? '3 ans';
$statCert       = $settings['stat_certifiantes']   ?? '100%';
$homeApprenants = $settings['home_apprenants']     ?? '1000';
$homeInseres    = $settings['home_inseres']        ?? '250';
$homePratique   = $settings['home_pratique']       ?? '80';
$homeDomaines   = $settings['home_domaines']       ?? '26';
$ebAmountHigh   = $settings['earlybird_amount_high'] ?? '25000';
$ebAmountLow    = $settings['earlybird_amount_low']  ?? '20000';
$ebPercent      = $settings['earlybird_percent']   ?? '10';
$ebDays         = $settings['earlybird_days']      ?? '14';
$refPercent     = $settings['referral_percent']    ?? '10';

/* ===============================
   TRAITEMENT FORMULAIRES
================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  csrf_check();

  /* ===============================
     ACTION : PARAMÈTRES GÉNÉRAUX
  =============================== */
  if (($_POST['action'] ?? '') === 'update_settings') {

    $appName     = trim($_POST['app_name'] ?? '');
    $adminEmail  = trim($_POST['admin_email'] ?? '');
    $adminPhone  = trim($_POST['admin_phone'] ?? '');
    $maintenance = ($_POST['maintenance'] ?? 'off') === 'on' ? 'on' : 'off';

    $contactEmail   = trim($_POST['contact_email'] ?? '');
    $contactPhones  = trim($_POST['contact_phones'] ?? '');
    $whatsappNumber = preg_replace('/\D/', '', (string)($_POST['whatsapp_number'] ?? ''));
    $siteInstit     = trim($_POST['site_institutionnel'] ?? '');
    $statApprenants = trim($_POST['stat_apprenants'] ?? '');
    $statSatisf     = trim($_POST['stat_satisfaction'] ?? '');
    $statExp        = trim($_POST['stat_experience'] ?? '');
    $statCert       = trim($_POST['stat_certifiantes'] ?? '');
    $homeApprenants = (string)(int)($_POST['home_apprenants'] ?? 0);
    $homeInseres    = (string)(int)($_POST['home_inseres'] ?? 0);
    $homePratique   = (string)(int)($_POST['home_pratique'] ?? 0);
    $homeDomaines   = (string)(int)($_POST['home_domaines'] ?? 0);
    $ebAmountHigh   = (string)(int)($_POST['earlybird_amount_high'] ?? 0);
    $ebAmountLow    = (string)(int)($_POST['earlybird_amount_low'] ?? 0);
    $ebPercent      = (string)(int)($_POST['earlybird_percent'] ?? 0);
    $ebDays         = (string)(int)($_POST['earlybird_days'] ?? 0);
    $refPercent     = (string)(int)($_POST['referral_percent'] ?? 0);

    if ($appName === '') {
      $errors[] = "Le nom de l&rsquo;application est obligatoire.";
    }

    if ($adminEmail && !filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
      $errors[] = "Email administrateur invalide.";
    }

    if (empty($errors)) {

      $stmt = $pdo->prepare("
        INSERT INTO settings (setting_key, setting_value)
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
      ");

      $pairs = [
        'app_name'     => $appName,
        'admin_email'  => $adminEmail,
        'admin_phone'  => $adminPhone,
        'maintenance'  => $maintenance,
        'contact_email'       => $contactEmail,
        'contact_phones'      => $contactPhones,
        'whatsapp_number'     => $whatsappNumber,
        'site_institutionnel' => $siteInstit,
        'stat_apprenants'     => $statApprenants,
        'stat_satisfaction'   => $statSatisf,
        'stat_experience'     => $statExp,
        'stat_certifiantes'   => $statCert,
        'home_apprenants'     => $homeApprenants,
        'home_inseres'        => $homeInseres,
        'home_pratique'       => $homePratique,
        'home_domaines'       => $homeDomaines,
        'earlybird_amount_high' => $ebAmountHigh,
        'earlybird_amount_low'  => $ebAmountLow,
        'earlybird_percent'   => $ebPercent,
        'earlybird_days'      => $ebDays,
        'referral_percent'    => $refPercent,
      ];

      foreach ($pairs as $key => $value) {
        $stmt->execute([$key, $value]);
      }

      $success = true;
    }
  }

  /* ===============================
     ACTION : CHANGEMENT MOT DE PASSE
  =============================== */
  if (($_POST['action'] ?? '') === 'change_password') {

    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($new !== $confirm) {
      $errors[] = "Les nouveaux mots de passe ne correspondent pas.";
    }

    if (strlen($new) < 8) {
      $errors[] = "Le mot de passe doit contenir au moins 8 caract&egrave;res.";
    }

    if (empty($errors)) {

      $stmt = $pdo->prepare("SELECT password FROM admins WHERE id = ?");
      $stmt->execute([$_SESSION['user']['id']]);
      $hash = $stmt->fetchColumn();

      if (!$hash || !password_verify($current, $hash)) {
        $errors[] = "Mot de passe actuel incorrect.";
      } else {

        $newHash = password_hash($new, PASSWORD_DEFAULT);

        $upd = $pdo->prepare("
          UPDATE admins
          SET password = ?, updated_at = NOW()
          WHERE id = ?
        ");
        $upd->execute([$newHash, $_SESSION['user']['id']]);

        session_regenerate_id(true);
        $success = true;
      }
    }
  }
}

ob_start();
?>

<div class="card" style="max-width:900px">

  <h2>&#9881;&#65039; Param&egrave;tres g&eacute;n&eacute;raux</h2>

  <?php if ($success): ?>
    <div class="pill ok" style="margin-top:16px">
      &#9989; Op&eacute;ration effectu&eacute;e avec succ&egrave;s.
    </div>
  <?php endif; ?>

  <?php if (!empty($errors)): ?>
    <div class="pill wait" style="margin-top:16px">
      <ul style="margin:0;padding-left:18px">
        <?php foreach ($errors as $e): ?>
          <li>&#10060; <?= e($e); ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <!-- FORMULAIRE PARAMÈTRES -->
  <form method="post" style="margin-top:20px">
    <?= csrf_field(); ?>
    <input type="hidden" name="action" value="update_settings">

    <div class="form-group">
      <label>Nom de l&rsquo;application</label>
      <input name="app_name" required value="<?= e($appName); ?>">
    </div>

    <div class="form-group">
      <label>Email administrateur</label>
      <input type="email" name="admin_email" value="<?= e($adminEmail); ?>">
    </div>

    <div class="form-group">
      <label>T&eacute;l&eacute;phone administrateur</label>
      <input name="admin_phone" value="<?= e($adminPhone); ?>">
    </div>

    <div class="form-group">
      <label>
        <input type="checkbox" name="maintenance" value="on"
          <?= $maintenance === 'on' ? 'checked' : ''; ?>>
        Activer le mode maintenance
      </label>
    </div>

    <hr style="margin:28px 0">
    <h3>&#128222; Contacts (pied de page)</h3>
    <div class="form-group">
      <label>Email de contact</label>
      <input type="email" name="contact_email" value="<?= e($contactEmail); ?>">
    </div>
    <div class="form-group">
      <label>T&eacute;l&eacute;phones (un par ligne)</label>
      <textarea name="contact_phones" rows="4" style="width:100%"><?= e($contactPhones); ?></textarea>
    </div>
    <div class="form-group">
      <label>Num&eacute;ro WhatsApp (format international sans +, ex&nbsp;: 2250778882592)</label>
      <input name="whatsapp_number" value="<?= e($whatsappNumber); ?>">
    </div>
    <div class="form-group">
      <label>Site institutionnel</label>
      <input name="site_institutionnel" value="<?= e($siteInstit); ?>">
    </div>

    <hr style="margin:28px 0">
    <h3>&#128202; Chiffres cl&eacute;s (accueil &amp; fiches)</h3>
    <div class="form-group"><label>Apprenants form&eacute;s</label><input name="stat_apprenants" value="<?= e($statApprenants); ?>"></div>
    <div class="form-group"><label>Taux de satisfaction</label><input name="stat_satisfaction" value="<?= e($statSatisf); ?>"></div>
    <div class="form-group"><label>Ann&eacute;es d&rsquo;exp&eacute;rience</label><input name="stat_experience" value="<?= e($statExp); ?>"></div>
    <div class="form-group"><label>Formations certifiantes</label><input name="stat_certifiantes" value="<?= e($statCert); ?>"></div>

    <hr style="margin:28px 0">
    <h3>&#127968; Compteurs page d&rsquo;accueil (nombres)</h3>
    <div class="form-group"><label>Apprenants form&eacute;s</label><input type="number" name="home_apprenants" value="<?= e($homeApprenants); ?>"></div>
    <div class="form-group"><label>Ins&eacute;r&eacute;s / plac&eacute;s</label><input type="number" name="home_inseres" value="<?= e($homeInseres); ?>"></div>
    <div class="form-group"><label>% pratique</label><input type="number" name="home_pratique" value="<?= e($homePratique); ?>"></div>
    <div class="form-group"><label>Domaines de formation</label><input type="number" name="home_domaines" value="<?= e($homeDomaines); ?>"></div>

    <hr style="margin:28px 0">
    <h3>&#128176; Promotions</h3>
    <div class="form-group"><label>Offre Anticip&eacute;e — Certification tranche haute 250k/275k (FCFA)</label><input type="number" name="earlybird_amount_high" value="<?= e($ebAmountHigh); ?>"></div>
    <div class="form-group"><label>Offre Anticip&eacute;e — Certification tranche standard 200k/225k (FCFA)</label><input type="number" name="earlybird_amount_low" value="<?= e($ebAmountLow); ?>"></div>
    <div class="form-group"><label>Offre Anticip&eacute;e Samedi Pro — remise (%)</label><input type="number" name="earlybird_percent" value="<?= e($ebPercent); ?>"></div>
    <div class="form-group"><label>Offre Anticip&eacute;e — d&eacute;lai (jours avant le d&eacute;but)</label><input type="number" name="earlybird_days" value="<?= e($ebDays); ?>"></div>
    <div class="form-group"><label>Parrainage — remise parrain &amp; filleul (%)</label><input type="number" name="referral_percent" value="<?= e($refPercent); ?>"></div>

    <button class="btn btn-success">
      Enregistrer les param&egrave;tres
    </button>
  </form>

  <hr style="margin:40px 0">

  <!-- FORMULAIRE SÉCURITÉ -->
  <h3>&#128274; S&eacute;curit&eacute; du compte</h3>

  <form method="post" style="margin-top:20px">
    <?= csrf_field(); ?>
    <input type="hidden" name="action" value="change_password">

    <div class="form-group">
      <label>Mot de passe actuel</label>
      <input type="password" name="current_password" required>
    </div>

    <div class="form-group">
      <label>Nouveau mot de passe</label>
      <input type="password" name="new_password" required minlength="8">
    </div>

    <div class="form-group">
      <label>Confirmer le nouveau mot de passe</label>
      <input type="password" name="confirm_password" required>
    </div>

    <button class="btn btn-danger">
      Mettre &agrave; jour le mot de passe
    </button>
  </form>

</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout/layout.php';
