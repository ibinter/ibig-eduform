<?php
declare(strict_types=1);
require_once __DIR__ . '/core/mail.php';

$result1 = send_mail(
    'patriceky1er@gmail.com',
    'TEST candidature formateur — accusé réception',
    '<h2>Test email formateur</h2><p>Ceci est un test d\'envoi depuis devenir-formateur.php</p>'
);

$result2 = send_mail(
    'formation@ibig-eduform.com',
    'TEST notification admin — nouvelle candidature',
    '<h2>Test notification admin</h2><p>Test depuis mail_formateur_test.php</p>'
);

echo json_encode([
    'candidat' => $result1 ? 'OK' : 'ECHEC',
    'admin'    => $result2 ? 'OK' : 'ECHEC',
]);
