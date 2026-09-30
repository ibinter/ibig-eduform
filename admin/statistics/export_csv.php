<?php
require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../auth/middleware.php';

Middleware::requireAuth();
$pdo = Database::connect();

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

$stmt = $pdo->prepare("
  SELECT visited_at, page, referrer, country_name, device
  FROM site_visits
  $where
  ORDER BY visited_at DESC
");
$stmt->execute($params);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=visites.csv');

$out = fopen('php://output', 'w');
fputcsv($out, ['Date', 'Page', 'Referent', 'Pays', 'Device']);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
  fputcsv($out, [
    $row['visited_at'],
    $row['page'],
    $row['referrer'],
    $row['country_name'],
    $row['device'],
  ]);
}

fclose($out);
exit;
