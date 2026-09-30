<?php
declare(strict_types=1);
require_once __DIR__ . '/../core/config.php';
$pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);

$domaines = ['IBIG DIGITAL BUSINESS','IBIG E-COMMERCE','IBIG SALES ACADEMY','IBIG EXECUTIVE ACADEMY','IBIG DATA & AI','IBIG LEADERSHIP & COMMUNICATION'];

$placeholders = implode(',', array_fill(0, count($domaines), '?'));
$stmt = $pdo->prepare("UPDATE formations SET annee = 0, date_debut = '0000-00-00', mois = '', frais_inscription = 0, montant_inscription = 0 WHERE domaine IN ($placeholders)");
$stmt->execute($domaines);

$affected = $stmt->rowCount();
echo "OK : $affected formations mises à jour (annee=0)";
