<?php
/* =========================================================
   VIDER LE CACHE PHP (OPcache) — IBIG EDUFORM
   Usage : ouvrir https://ibig-eduform.com/vider-cache.php
   puis SUPPRIMER ce fichier du serveur (sécurité).
========================================================= */
header('Content-Type: text/plain; charset=UTF-8');

$done = [];

if (function_exists('opcache_reset')) {
    $done[] = opcache_reset() ? 'OPcache vidé ✅' : 'OPcache présent mais reset refusé';
} else {
    $done[] = 'OPcache non disponible (rien à vider de ce côté)';
}

if (function_exists('apcu_clear_cache')) {
    apcu_clear_cache();
    $done[] = 'APCu vidé ✅';
}

// Force aussi l'invalidation par date de modif si dispo
if (function_exists('opcache_invalidate')) {
    foreach (glob(__DIR__ . '/*.php') ?: [] as $f) {
        @opcache_invalidate($f, true);
    }
}

echo "Résultat :\n- " . implode("\n- ", $done) . "\n\n";
echo "⚠️ SUPPRIMEZ maintenant ce fichier (vider-cache.php) du serveur.\n";
echo "Puis faites Ctrl+F5 sur les pages admin.\n";
