$data = json_decode(file_get_contents('php://input'), true);

$stmt = $pdo->prepare("
  INSERT INTO site_events
  (session_key, action, label, utm_source, utm_campaign)
  VALUES (?, ?, ?, ?, ?)
");

$stmt->execute([
  $_SESSION['session_key'] ?? null,
  $data['action'] ?? null,
  $data['label'] ?? null,
  $_SESSION['utm_source'] ?? null,
  $_SESSION['utm_campaign'] ?? null
]);
