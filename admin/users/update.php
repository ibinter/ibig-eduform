require_once __DIR__ . '/../auth/guard.php';
require_once __DIR__ . '/../auth/audit.php';

require_permission('manage_users');
csrf_verify();

// après update SQL
audit_log(
  'update',
  'user',
  $userId,
  'Modification du profil utilisateur'
);

audit_log(
  'create',
  'formateur',
  $formateurId,
  'Ajout nouveau formateur'
);