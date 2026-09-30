<?php
declare(strict_types=1);

require_once __DIR__ . '/core/config.php';
require_once __DIR__ . '/core/database.php';
require_once __DIR__ . '/core/mail.php';

$pageTitle = "Recrutement Formateurs — Vague 2026 | IBIG EDUFORM";
include __DIR__ . '/partials/header.php';

$success = false;
$error   = '';

/* Valeurs persistantes */
$nom = $email = $telephone = $domaine = $experience = $message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $nom        = trim($_POST['nom'] ?? '');
  $email      = trim($_POST['email'] ?? '');
  $telephone  = trim($_POST['telephone'] ?? '');
  $domaine    = trim($_POST['domaine'] ?? '');
  $experience = trim($_POST['experience'] ?? '');
  $message    = trim($_POST['message'] ?? '');

  if ($nom === '' || $email === '' || $telephone === '' || $domaine === '') {
    $error = "Veuillez remplir tous les champs obligatoires.";
  }
  elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = "Adresse email invalide.";
  }

  /* =======================
     UPLOAD CV
  ======================= */
  $cvPath = null;

  if (!$error && !empty($_FILES['cv']['name'])) {

    if ($_FILES['cv']['error'] !== UPLOAD_ERR_OK) {
      $error = "Erreur lors de l’envoi du CV.";
    } else {

      $ext  = strtolower(pathinfo($_FILES['cv']['name'], PATHINFO_EXTENSION));
      $size = (int)$_FILES['cv']['size'];

      if ($ext !== 'pdf') {
        $error = "Le CV doit être au format PDF.";
      }
      elseif ($size > 5 * 1024 * 1024) {
        $error = "Le CV ne doit pas dépasser 5 Mo.";
      }
      else {

        $uploadDir = __DIR__ . '/uploads/formateurs/';
        if (!is_dir($uploadDir)) {
          mkdir($uploadDir, 0755, true);
        }

        $filename = 'cv_formateur_' . date('Ymd_His') . '_' . random_int(1000,9999) . '.pdf';
        $dest = $uploadDir . $filename;

        if (move_uploaded_file($_FILES['cv']['tmp_name'], $dest)) {
          $cvPath = 'uploads/formateurs/' . $filename;
        } else {
          $error = "Impossible d’enregistrer le fichier CV.";
        }
      }
    }
  }

  // Insertion SQL
  if (!$error) {
    $pdo = Database::connect();

    $stmt = $pdo->prepare("
      INSERT INTO candidatures_formateurs
        (nom, email, telephone, domaine, experience, message, cv_path)
      VALUES
        (:nom, :email, :telephone, :domaine, :experience, :message, :cv)
    ");

    $stmt->execute([
      ':nom'        => $nom,
      ':email'      => $email,
      ':telephone'  => $telephone,
      ':domaine'    => $domaine,
      ':experience' => $experience,
      ':message'    => $message,
      ':cv'         => $cvPath
    ]);

    $success = true;

    // Email de notification à l'admin
    $adminHtml = '
<div style="font-family:Arial,sans-serif;max-width:600px;margin:auto;padding:24px;border:1px solid #e5e7eb;border-radius:12px">
  <h2 style="color:#0b3b82;margin-top:0">📋 Nouvelle candidature formateur</h2>
  <table style="width:100%;border-collapse:collapse;font-size:15px">
    <tr><td style="padding:8px 0;font-weight:600;color:#374151;width:160px">Nom & Prénoms</td><td style="padding:8px 0;color:#111827">' . htmlspecialchars($nom, ENT_QUOTES) . '</td></tr>
    <tr style="background:#f9fafb"><td style="padding:8px 4px;font-weight:600;color:#374151">Email</td><td style="padding:8px 4px;color:#111827">' . htmlspecialchars($email, ENT_QUOTES) . '</td></tr>
    <tr><td style="padding:8px 0;font-weight:600;color:#374151">Téléphone</td><td style="padding:8px 0;color:#111827">' . htmlspecialchars($telephone, ENT_QUOTES) . '</td></tr>
    <tr style="background:#f9fafb"><td style="padding:8px 4px;font-weight:600;color:#374151">Domaine</td><td style="padding:8px 4px;color:#111827">' . htmlspecialchars($domaine, ENT_QUOTES) . '</td></tr>
    <tr><td style="padding:8px 0;font-weight:600;color:#374151">Expérience</td><td style="padding:8px 0;color:#111827">' . htmlspecialchars($experience ?: '—', ENT_QUOTES) . '</td></tr>
  </table>
  ' . ($message ? '<hr style="border:none;border-top:1px solid #e5e7eb;margin:16px 0"><p style="font-weight:600;color:#374151;margin:0 0 6px">Présentation</p><p style="color:#374151;white-space:pre-line;margin:0">' . htmlspecialchars($message, ENT_QUOTES) . '</p>' : '') . '
  <p style="margin-top:20px;font-size:13px;color:#6b7280">Candidature reçue le ' . date('d/m/Y à H:i') . ' — IBIG EDUFORM</p>
</div>';

    send_mail('recrutement@ibig-eduform.com', '📋 Nouvelle candidature formateur Vague 2026 — ' . $nom, $adminHtml);

    // Accusé de réception au candidat
    $candidatHtml = '
<div style="font-family:Arial,sans-serif;max-width:600px;margin:auto;padding:24px">
  <div style="background:linear-gradient(135deg,#0b3b82,#0a2f66);padding:28px 24px;border-radius:12px 12px 0 0;text-align:center">
    <h1 style="color:#fff;margin:0;font-size:24px">IBIG EDUFORM</h1>
    <p style="color:rgba(255,255,255,.85);margin:6px 0 0;font-size:14px">Institut de formation professionnelle</p>
  </div>
  <div style="background:#fff;padding:28px 24px;border:1px solid #e5e7eb;border-top:none;border-radius:0 0 12px 12px">
    <p style="font-size:17px;font-weight:600;color:#111827;margin-top:0">Bonjour ' . htmlspecialchars($nom, ENT_QUOTES) . ',</p>
    <p style="color:#374151;line-height:1.6">Nous avons bien reçu votre candidature en tant que formateur partenaire IBIG EDUFORM dans le domaine <strong>' . htmlspecialchars($domaine, ENT_QUOTES) . '</strong>.</p>
    <p style="color:#374151;line-height:1.6">Notre équipe étudiera votre dossier avec attention et reviendra vers vous dans les meilleurs délais.</p>
    <div style="background:#f0f9ff;border-left:4px solid #0b3b82;padding:14px 16px;border-radius:0 8px 8px 0;margin:20px 0">
      <p style="color:#0b3b82;font-weight:600;margin:0 0 4px">Récapitulatif de votre candidature</p>
      <p style="color:#374151;margin:0;font-size:14px">Domaine : ' . htmlspecialchars($domaine, ENT_QUOTES) . '<br>Expérience : ' . htmlspecialchars($experience ?: 'Non précisé', ENT_QUOTES) . '</p>
    </div>
    <p style="color:#6b7280;font-size:13px;margin-bottom:0">Pour toute question, contactez-nous à <a href="mailto:formation@ibig-eduform.com" style="color:#0b3b82">formation@ibig-eduform.com</a></p>
  </div>
</div>';

    send_mail($email, 'Candidature formateur IBIG EDUFORM — bien reçue', $candidatHtml);
  }
}
?>

<!-- HERO -->
<section class="hero-formateur">
  <div class="container">
    <div class="hero-badge-vague">&#x1F7E1; Recrutement ouvert &mdash; Vague 2026</div>
    <h1>Rejoignez notre réseau<br>de Formateurs</h1>
    <p>
      Partagez votre expertise avec des professionnels de toute l’Afrique francophone.
      Flexibilité totale, rémunération attractive, certificats reconnus dans <strong>17 pays OHADA</strong>.
    </p>
  </div>
</section>

<!-- AVANTAGES -->
<section class="section avantages-formateur-section">
  <div class="container">
    <div class="avantages-grid">
      <div class="avantage-item">
        <div class="avantage-icon">&#x1F4B0;</div>
        <div>
          <strong>Rémunération attractive</strong>
          <p>Honoraires négociés par session animée</p>
        </div>
      </div>
      <div class="avantage-item">
        <div class="avantage-icon">&#x1F550;</div>
        <div>
          <strong>Flexibilité totale</strong>
          <p>Compatible avec votre activité principale</p>
        </div>
      </div>
      <div class="avantage-item">
        <div class="avantage-icon">&#x1F4CB;</div>
        <div>
          <strong>Supports fournis</strong>
          <p>TDR, guides et ingénierie pédagogique</p>
        </div>
      </div>
      <div class="avantage-item">
        <div class="avantage-icon">&#x1F3C5;</div>
        <div>
          <strong>Certification OHADA</strong>
          <p>Certificats co-signés à votre nom</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- FORMULAIRE -->
<section class="section formateur-section">
  <div class="container">

    <div class="formateur-card">

      <div class="card-header-vague">
        <h2>Déposer ma candidature</h2>
        <span class="badge-vague">Vague 2026</span>
      </div>
      <p class="muted">Les champs marqués d’un * sont obligatoires.</p>

      <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
      <?php endif; ?>

      <?php if ($success): ?>
        <div class="alert alert-success">
          Votre candidature a bien été envoyée.<br>
          Notre équipe vous contactera après étude de votre dossier.
        </div>
      <?php endif; ?>

      <form method="post" enctype="multipart/form-data" class="form-grid">

        <div class="form-group">
          <label>Nom & Prénoms *</label>
          <input type="text" name="nom" required>
        </div>

        <div class="form-group">
          <label>Email *</label>
          <input type="email" name="email" required>
        </div>

        <div class="form-group">
          <label>Téléphone *</label>
          <input type="text" name="telephone" required>
        </div>

        <div class="form-group">
          <label>Domaine d’expertise *</label>
          <select name="domaine" required>
            <option value="">-- Sélectionner --</option>
            <option>Management &amp; Leadership</option>
            <option>Finance &amp; Comptabilité SYSCOHADA</option>
            <option>Marketing Digital</option>
            <option>Ressources Humaines</option>
            <option>Transport &amp; Logistique</option>
            <option>Informatique &amp; Intelligence Artificielle</option>
            <option>Gestion de projet</option>
            <option>Droit des affaires OHADA</option>
            <option>Immobilier &amp; BTP</option>
            <option>Développement personnel</option>
            <option>Autre domaine</option>
          </select>
        </div>

        <div class="form-group">
          <label>Années d’expérience</label>
          <input type="text" name="experience" placeholder="Ex : 5 ans">
        </div>

        <div class="form-group full">
          <label>Présentation / Message</label>
          <textarea name="message" rows="5"
            placeholder="Présentez brièvement votre parcours et vos domaines d’intervention"></textarea>
        </div>

        <div class="form-group full">
          <label>CV (PDF uniquement)</label>
          <input type="file" name="cv" accept=".pdf">
        </div>

        <div class="form-group full">
          <button type="submit" class="btn btn-primary btn-lg">
            Soumettre ma candidature
          </button>
        </div>

      </form>

    </div>

  </div>
</section>

<style>
.hero-formateur{
  background: linear-gradient(135deg, #0d1f3c, #162d52);
  color:#fff;
  padding:72px 0 64px;
}

.hero-formateur .container{
  max-width:1000px;
  margin:0 auto;
  text-align:center;
}

.hero-badge-vague{
  display:inline-block;
  background:rgba(245,166,35,.15);
  border:1px solid rgba(245,166,35,.4);
  color:#f5a623;
  font-size:13px;
  font-weight:700;
  letter-spacing:1px;
  padding:6px 16px;
  border-radius:4px;
  margin-bottom:20px;
}

.hero-formateur h1{
  font-size:42px;
  line-height:1.15;
  margin-bottom:14px;
}

.hero-formateur h1 strong, .hero-formateur strong{
  color:#f5a623;
}

.hero-formateur p{
  font-size:18px;
  line-height:1.6;
  max-width:780px;
  margin:0 auto;
  opacity:.92;
}

/* Avantages */
.avantages-formateur-section{
  background:#fff;
  padding:48px 0;
  border-bottom:1px solid #e5e7eb;
}

.avantages-grid{
  max-width:1000px;
  margin:0 auto;
  display:grid;
  grid-template-columns:repeat(4,1fr);
  gap:24px;
}

.avantage-item{
  display:flex;
  gap:14px;
  align-items:flex-start;
}

.avantage-icon{
  font-size:26px;
  flex-shrink:0;
  line-height:1;
  margin-top:2px;
}

.avantage-item strong{
  display:block;
  font-size:14.5px;
  font-weight:700;
  color:#0d1f3c;
  margin-bottom:3px;
}

.avantage-item p{
  font-size:13px;
  color:#6b7280;
  margin:0;
  line-height:1.45;
}

/* Card header */
.card-header-vague{
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:12px;
  margin-bottom:6px;
}

.card-header-vague h2{margin:0}

.badge-vague{
  background:#f5a623;
  color:#0d1f3c;
  font-size:12px;
  font-weight:800;
  letter-spacing:1px;
  padding:4px 12px;
  border-radius:4px;
  text-transform:uppercase;
  flex-shrink:0;
}

@media(max-width:900px){
  .avantages-grid{grid-template-columns:repeat(2,1fr)}
}
@media(max-width:560px){
  .avantages-grid{grid-template-columns:1fr}
  .card-header-vague{flex-direction:column;align-items:flex-start}
}

.formateur-section{
  background:#f4f6f9;
  padding:64px 0;
}

.formateur-card{
  max-width:900px;
  margin:auto;
  background:#fff;
  padding:40px;
  border-radius:18px;
  box-shadow:0 24px 48px rgba(0,0,0,.08);
}

.form-grid{
  display:grid;
  grid-template-columns:repeat(2,1fr);
  gap:18px;
  margin-top:24px;
}

.form-group label{
  font-weight:600;
  margin-bottom:6px;
  display:block;
}

.form-group input,
.form-group select,
.form-group textarea{
  width:100%;
  padding:12px 14px;
  border-radius:10px;
  border:1px solid #d1d5db;
  font-size:15px;
}

.form-group.full{grid-column:1 / -1}

.alert{
  padding:14px;
  border-radius:10px;
  margin:16px 0;
}

.alert-error{background:#fee2e2;color:#991b1b}
.alert-success{background:#dcfce7;color:#166534}

@media(max-width:768px){
  .form-grid{grid-template-columns:1fr}
  .formateur-card{padding:24px}
}

.hero-formateur .container{
  max-width:1000px;
  margin:0 auto;
  text-align:center;
}

.hero-formateur p{
  margin:0 auto;
}

/* ===============================
   HERO – OPTIMISATION MOBILE
=============================== */
@media (max-width: 768px) {

  .hero-formateur{
    padding:48px 16px 40px;
  }

  .hero-formateur .container{
    max-width:100%;
    text-align:center;
  }

  .hero-formateur h1{
    font-size:30px;
    line-height:1.2;
    margin-bottom:12px;
  }

  .hero-formateur p{
    font-size:15.5px;
    line-height:1.55;
    max-width:100%;
    opacity:.95;
  }

}

</style>

<?php include __DIR__ . '/partials/footer.php'; ?>
