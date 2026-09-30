<?php
declare(strict_types=1);

/* =========================================
   EXPORT PDF — STATISTIQUES IBIG EDUFORM
   (Composer + TCPDF)
========================================= */

/* --- BLOQUER TOUT AFFICHAGE --- */
ini_set('display_errors', '0');
error_reporting(0);

/* --- BUFFER OUTPUT --- */
if (ob_get_level()) {
    ob_end_clean();
}
ob_start();

/* --- INCLUDES --- */
require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../auth/middleware.php';

Middleware::requireAuth();

/* --- COMPOSER AUTOLOAD --- */
require_once __DIR__ . '/../../vendor/autoload.php';

$pdo = Database::connect();

/* =========================================
   FILTRES
========================================= */
$period = $_GET['period'] ?? '30d';
$start  = $_GET['start'] ?? null;
$end    = $_GET['end'] ?? null;

$where  = "WHERE is_bot = 0";
$params = [];

switch ($period) {
  case 'today':
    $where .= " AND DATE(visited_at) = CURDATE()";
    break;
  case '7d':
    $where .= " AND visited_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
    break;
  case '30d':
    $where .= " AND visited_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
    break;
  case 'custom':
    if ($start && $end) {
      $where .= " AND DATE(visited_at) BETWEEN ? AND ?";
      $params[] = $start;
      $params[] = $end;
    }
    break;
}

/* =========================================
   DONNÉES
========================================= */
$stmt = $pdo->prepare("SELECT COUNT(*) FROM site_visits $where");
$stmt->execute($params);
$totalVisits = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(DISTINCT ip_hash) FROM site_visits $where");
$stmt->execute($params);
$uniqueVisitors = (int)$stmt->fetchColumn();

$pageStmt = $pdo->prepare("
  SELECT page, COUNT(*) total
  FROM site_visits
  $where
  GROUP BY page
  ORDER BY total DESC
  LIMIT 15
");
$pageStmt->execute($params);
$pages = $pageStmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================================
   TCPDF
========================================= */
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('IBIG EDUFORM');
$pdf->SetAuthor('IBIG EDUFORM');
$pdf->SetTitle('Statistiques du site');
$pdf->SetMargins(15, 18, 15);
$pdf->SetAutoPageBreak(true, 18);
$pdf->AddPage();

/* =========================================
   HTML PDF
========================================= */
$html = '
<h1 style="text-align:center;">Statistiques du site IBIG EDUFORM</h1>

<p>
<strong>P&eacute;riode :</strong> ' . htmlspecialchars($period) . '<br>
<strong>Date :</strong> ' . date('d/m/Y H:i') . '
</p>

<hr>

<h3>Indicateurs cl&eacute;s</h3>
<ul>
  <li>Total des visites : <strong>' . $totalVisits . '</strong></li>
  <li>Visiteurs uniques : <strong>' . $uniqueVisitors . '</strong></li>
</ul>

<h3>Pages les plus visit&eacute;es</h3>

<table border="1" cellpadding="6" cellspacing="0" width="100%">
<tr style="background:#eeeeee;">
  <th width="75%">Page</th>
  <th width="25%" align="center">Visites</th>
</tr>
';

foreach ($pages as $p) {
  $html .= '
  <tr>
    <td>' . htmlspecialchars($p['page']) . '</td>
    <td align="center">' . (int)$p['total'] . '</td>
  </tr>
  ';
}

$html .= '
</table>

<p style="font-size:10px;color:#666;margin-top:20px;">
Document g&eacute;n&eacute;r&eacute; automatiquement par IBIG EDUFORM.
</p>
';

$pdf->writeHTML($html, true, false, true, false, '');

/* --- NETTOYER BUFFER ET SORTIR PDF --- */
ob_end_clean();
$pdf->Output('statistiques-ibig-eduform.pdf', 'D');
exit;
