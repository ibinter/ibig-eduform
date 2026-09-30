<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../core/auth.php';

var_dump(auth_check());
var_dump($_SESSION);
