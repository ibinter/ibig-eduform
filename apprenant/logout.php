<?php
declare(strict_types=1);
require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/apprenant_auth.php';
apprenant_logout();
header('Location: /apprenant/login.php');
exit;
