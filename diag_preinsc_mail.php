<?php
declare(strict_types=1);
// Simule exactement ce que fait preinscription-generale.php pour l'email
ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/core/mail.php';

$email       = 'patriceky1er@gmail.com';
$nomFormation = 'Management des Ressources Humaines';
$nomProspect  = 'Patrice Kouakou';
$formatEmail  = 'individuel';
$modeEmail    = 'en_ligne';
$prixE        = 200000;
$tranche1     = 100000;
$tdrLink      = 'https://ibig-eduform.com/tdr-download.php?t=TEST';

echo "<h2>Étape 1 : chargement tdr_generator.php</h2>";
try {
    require_once __DIR__ . '/core/tdr_generator.php';
    echo "<p style='color:green'>OK — tdr_generator chargé</p>";
} catch (Throwable $e) {
    echo "<p style='color:red'>ERREUR : " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

echo "<h2>Étape 2 : envoi email test</h2>";
$html = '<h2>Test préinscription</h2><p>Formation : ' . htmlspecialchars($nomFormation) . '</p><p>Prospect : ' . htmlspecialchars($nomProspect) . '</p><p>Lien TDR : <a href="' . $tdrLink . '">' . $tdrLink . '</a></p>';

$result = send_mail($email, 'DIAG — Test email préinscription IBIG EDUFORM', $html);
echo "<p>send_mail result : <strong>" . ($result ? '<span style="color:green">OK</span>' : '<span style="color:red">ÉCHEC</span>') . "</strong></p>";

echo "<h2>Étape 3 : génération TDR HTML</h2>";
try {
    $formationData = [
        'name'        => $nomFormation,
        'category'    => 'rh',
        'slug'        => 'management-rh',
        'price'       => 200000,
        'description' => 'Formation en management des ressources humaines',
    ];
    $tdrOpts = [
        'mode_formation'   => $modeEmail,
        'format_formation' => $formatEmail,
        'date_debut'       => '',
        'creneau'          => '',
    ];
    $tdrHtml = generate_tdr_html($formationData, $nomProspect, $tdrOpts);
    echo "<p style='color:green'>OK — TDR généré (" . strlen($tdrHtml) . " octets)</p>";
} catch (Throwable $e) {
    echo "<p style='color:red'>ERREUR TDR : " . htmlspecialchars($e->getMessage()) . "</p>";
}
