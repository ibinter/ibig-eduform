<?php
// Script de diagnostic: poste vers preinscription-generale.php et capture la réponse
declare(strict_types=1);

// Étape 1: charger la page pour obtenir le token CSRF
$ch1 = curl_init('https://ibig-eduform.com/preinscription-generale.php');
curl_setopt_array($ch1, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_COOKIEJAR      => '/tmp/ibig_cookies.txt',
    CURLOPT_COOKIEFILE     => '/tmp/ibig_cookies.txt',
    CURLOPT_FOLLOWLOCATION => false,
    CURLOPT_TIMEOUT        => 15,
]);
$html = curl_exec($ch1);
curl_close($ch1);

// Extraire le token CSRF
preg_match('/<input[^>]+name=["\']csrf["\'][^>]+value=["\']([a-f0-9]+)["\']/', (string)$html, $m);
$csrf = $m[1] ?? '';
echo "CSRF token: " . ($csrf ? substr($csrf,0,10).'...' : 'NOT FOUND') . "\n";

if (!$csrf) {
    echo "ERREUR: impossible d'obtenir le token CSRF\n";
    exit;
}

// Étape 2: POST le formulaire
$postData = http_build_query([
    'csrf'                 => $csrf,
    'catalogue_nom'        => 'Management des Ressources Humaines',
    'catalogue_nom_display'=> 'Management des Ressources Humaines',
    'formation_slug'       => 'management-rh',
    'catalogue_domaine'    => 'rh',
    'catalogue_prix'       => '200000',
    'domaine_interet'      => 'rh',
    'nom'                  => 'DiagTest',
    'prenoms'              => 'SelfPost',
    'email'                => 'patriceky1er@gmail.com',
    'telephone'            => '+225 07 00 00 00 99',
    'mode_formation'       => 'en_ligne',
    'format_formation'     => 'individuel',
    'date_debut_souhaitee' => '',
    'creneau_prefere'      => '',
    'statut_professionnel' => 'demandeur_emploi',
    'objectif'             => 'monter_competence',
    'disponibilite'        => '',
    'ville'                => 'Abidjan',
    'pays'                 => "Côte d'Ivoire",
    'message'              => 'Test automatique diag_selfpost',
]);

$ch2 = curl_init('https://ibig-eduform.com/preinscription-generale.php');
curl_setopt_array($ch2, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $postData,
    CURLOPT_COOKIEJAR      => '/tmp/ibig_cookies.txt',
    CURLOPT_COOKIEFILE     => '/tmp/ibig_cookies.txt',
    CURLOPT_FOLLOWLOCATION => false,
    CURLOPT_HEADER         => true,
    CURLOPT_TIMEOUT        => 60,
]);
$resp = curl_exec($ch2);
$httpCode = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
$location = curl_getinfo($ch2, CURLINFO_REDIRECT_URL);
$errMsg = curl_error($ch2);
curl_close($ch2);

echo "HTTP Code: $httpCode\n";
echo "Redirect to: " . ($location ?: 'none') . "\n";
echo "Curl error: " . ($errMsg ?: 'none') . "\n";

// Chercher Location dans headers
if (preg_match('/Location:\s*(.+)/i', (string)$resp, $loc)) {
    echo "Location header: " . trim($loc[1]) . "\n";
}

// Chercher message de succès ou erreur dans le HTML
if (strpos((string)$resp, 'success=1') !== false) {
    echo "RÉSULTAT: REDIRECT success=1 détecté ✅\n";
} elseif (preg_match('/(alert-error|Erreur|invalide)[^<]{0,100}/i', (string)$resp, $err)) {
    echo "RÉSULTAT: ERREUR détectée — " . trim($err[0]) . "\n";
} else {
    echo "RÉSULTAT: ni success ni erreur trouvés dans la réponse\n";
    echo substr((string)$resp, 0, 500) . "\n";
}
