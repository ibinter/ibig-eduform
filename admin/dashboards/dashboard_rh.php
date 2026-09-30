<?php
declare(strict_types=1);

/* =====================
   DASHBOARD RH
===================== */

$pageTitle  = 'Tableau de bord RH';
$activeMenu = 'dashboard';

$pdo = Database::connect();

/* =====================
   KPI RH (SAFE)
===================== */
$nbUsers = (int)$pdo->query("
  SELECT COUNT(*) FROM users
")->fetchColumn();

$nbFormateurs = (int)$pdo->query("
  SELECT COUNT(*) FROM users WHERE role = 'formateur'
")->fetchColumn();

ob_start();
?>

<div class="grid kpi-grid">

  <div class="card kpi-card">
    <div class="kpi-title">&#128100; Utilisateurs</div>
    <div class="kpi-value"><?= $nbUsers; ?></div>
    <div class="muted">Total</div>
  </div>

  <div class="card kpi-card">
    <div class="kpi-title">&#127979; Formateurs</div>
    <div class="kpi-value"><?= $nbFormateurs; ?></div>
    <div class="muted">Enregistrés</div>
  </div>

</div>

<div class="grid" style="grid-template-columns:1fr 1fr;gap:20px;margin-top:24px">

  <div class="card">
    <strong>&#128221; Gestion des utilisateurs</strong>
    <div class="muted">Création, rôles et permissions</div>

    <a class="btn" style="margin-top:12px"
       href="/admin/users/index.php">
      &#128736;&#65039; Gérer les utilisateurs
    </a>
  </div>

  <div class="card">
    <strong>&#127891; Formateurs</strong>
    <div class="muted">Encadrement pédagogique</div>

    <a class="btn" style="margin-top:12px"
       href="/admin/users/index.php?role=formateur">
      &#128104;&#8205;&#127979; Voir les formateurs
    </a>
  </div>

</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout/layout.php';