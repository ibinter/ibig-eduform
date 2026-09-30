<?php
// Sécurité
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ================================
   LANGUE COURANTE
================================ */
$lang = $_GET['lang'] ?? $_SESSION['lang'] ?? 'fr';
$lang = in_array($lang, ['fr', 'en']) ? $lang : 'fr';
$_SESSION['lang'] = $lang;

/* ================================
   CHARGEMENT DU FICHIER LANGUE
================================ */
$langFile = __DIR__ . '/../lang/' . $lang . '.php';

if (!file_exists($langFile)) {
    die('Fichier de langue introuvable : ' . $langFile);
}

require_once $langFile;

/* ================================
   FONCTION DE TRADUCTION
================================ */
if (!function_exists('t')) {
    function t(string $key): string
    {
        global $translations;
        return $translations[$key] ?? $key;
    }
}
