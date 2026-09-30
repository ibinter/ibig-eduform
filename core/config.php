<?php
/* =========================================
   CONFIG — IBIG EDUFORM (STABLE)
========================================= */

/* APP */
define('APP_NAME', 'IBIG EDUFORM');
define('APP_ENV', getenv('APP_ENV') ?: 'production');
define('APP_DEBUG', APP_ENV !== 'production');

/* URL */
define('APP_URL', 'https://ibig-eduform.com');

/* PATHS */
define('BASE_PATH', dirname(__DIR__));
define('ADMIN_PATH', BASE_PATH . '/admin');

/* DB — identifiants chargés depuis un fichier de secrets protégé.
   core/ est déjà bloqué côté web (core/.htaccess : Require all denied).
   Idéalement, déplacer secrets.php AU-DESSUS de la racine web. */
require_once __DIR__ . '/secrets.php';
define('DB_CHARSET', 'utf8mb4');

/* SESSION — durcissement du cookie AVANT démarrage */
if (session_status() === PHP_SESSION_NONE) {
    $secure = (($_SERVER['HTTPS'] ?? '') === 'on')
           || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

/* ERREURS — selon l'environnement */
if (APP_DEBUG) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    ini_set('log_errors', '1');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
}

/* TIMEZONE */
date_default_timezone_set('Africa/Abidjan');

/* WHATSAPP — numéro de réception des demandes (international, sans +) */
define('WHATSAPP_ADMIN_PHONE', getenv('WHATSAPP_ADMIN_PHONE') ?: '2250778882592');

/* PAIEMENT — taux de commission répercuté sur le CLIENT (gross-up).
   Ex: 0.035 = 3,5%. Le client paie montant ÷ (1 − taux), l'institut
   reçoit le net. Mettez ici le taux réel de votre prestataire. */
define('PAYMENT_FEE_RATE', (float)(getenv('PAYMENT_FEE_RATE') ?: 0.035));

/* TDR — clé de signature des tokens de téléchargement (72h) */
define('TDR_SECRET', 'IBIG_TDR_2026_SECRET_KEY');

/* EMAIL */
define('MAIL_FROM', 'formation@ibig-eduform.com');
define('MAIL_FROM_NAME', 'IBIG EDUFORM');
define('ADMIN_EMAIL', 'formation@ibig-eduform.com');
