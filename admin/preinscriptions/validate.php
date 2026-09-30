require_once __DIR__ . '/../auth/guard.php';
require_once __DIR__ . '/../auth/audit.php';

require_permission('manage_preinscriptions');

// ... traitement validation ...

audit_log(
  'validate',
  'preinscription',
  (int) $_GET['id'],
  'Préinscription validée'
);

audit_log(
  'reject',
  'preinscription',
  $id,
  'Préinscription rejetée'
);