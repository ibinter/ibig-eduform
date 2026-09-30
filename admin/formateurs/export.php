<?php
declare(strict_types=1);

require_once __DIR__ . '/../_init.php';

/* Middleware admin */
$mw = realpath(__DIR__ . '/../auth/middleware.php');
if (!$mw) { die('Middleware introuvable'); }
require_once $mw;
Middleware::requireAuth();

$pdo = Database::connect();

$type = $_GET['type'] ?? 'excel';

/* ============================
   DONNÉES À EXPORTER
============================ */
$stmt = $pdo->query("
  SELECT
    c.id,
    c.nom,
    c.email,
    c.telephone,
    c.domaine,
    c.experience,
    c.statut,
    c.visible,
    c.created_at,
    f.statut AS fiche_statut
  FROM candidatures_formateurs c
  LEFT JOIN formateurs f
    ON f.candidature_id = c.id
  ORDER BY c.created_at DESC
");
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ============================
   EXPORT EXCEL
============================ */
if ($type === 'excel') {

  header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
  header('Content-Disposition: attachment; filename="candidatures_formateurs.xls"');
  header('Pragma: no-cache');
  header('Expires: 0');

  echo "\xEF\xBB\xBF"; // UTF-8 BOM

  echo "ID\tNom\tEmail\tTéléphone\tDomaine\tExpérience\tStatut\tVisible\tFiche\tDate\n";

  foreach ($data as $row) {
    echo implode("\t", [
      $row['id'],
      $row['nom'],
      $row['email'],
      $row['telephone'],
      $row['domaine'],
      $row['experience'],
      $row['statut'],
      $row['visible'] ? 'Oui' : 'Non',
      $row['fiche_statut'] ? 'Oui' : 'Non',
      date('d/m/Y', strtotime($row['created_at']))
    ]) . "\n";
  }
  exit;
}

/* ============================
   EXPORT PDF (HTML imprimable)
============================ */
if ($type === 'pdf') {

  header('Content-Type: text/html; charset=UTF-8');

  echo '<!DOCTYPE html>
  <html lang="fr">
  <head>
    <meta charset="UTF-8">
    <title>Export candidatures formateurs</title>
    <style>
      body { font-family: Arial, sans-serif; font-size: 12px; }
      h1 { text-align:center; }
      table { width:100%; border-collapse: collapse; margin-top:20px; }
      th, td { border:1px solid #000; padding:6px; }
      th { background:#eee; }
    </style>
  </head>
  <body onload="window.print()">

  <h1>Liste des candidatures formateurs</h1>

  <table>
    <thead>
      <tr>
        <th>Nom</th>
        <th>Email</th>
        <th>Téléphone</th>
        <th>Domaine</th>
        <th>Statut</th>
        <th>Visible</th>
        <th>Fiche</th>
        <th>Date</th>
      </tr>
    </thead>
    <tbody>';

  foreach ($data as $row) {
    echo '<tr>
      <td>'.htmlspecialchars($row['nom']).'</td>
      <td>'.htmlspecialchars($row['email']).'</td>
      <td>'.htmlspecialchars($row['telephone']).'</td>
      <td>'.htmlspecialchars($row['domaine']).'</td>
      <td>'.htmlspecialchars($row['statut']).'</td>
      <td>'.($row['visible'] ? 'Oui' : 'Non').'</td>
      <td>'.($row['fiche_statut'] ? 'Oui' : 'Non').'</td>
      <td>'.date('d/m/Y', strtotime($row['created_at'])).'</td>
    </tr>';
  }

  echo '</tbody>
  </table>

  <p style="margin-top:20px;text-align:center;">
    IBIG EDUFORM – Export généré le '.date('d/m/Y H:i').'
  </p>

  </body></html>';
  exit;
}
