<?php
// Clé d'accès one-time
if (($_GET['k'] ?? '') !== 'ibig-fix-sup-2026') { http_response_code(403); exit('Forbidden'); }

header('Content-Type: text/plain; charset=utf-8');

$supabaseUrl = 'https://zuqjuqpldnnfaoycwkcr.supabase.co';
$supabaseKey = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6Inp1cWp1cXBsZG5uZmFveWN3a2NyIiwicm9sZSI6InNlcnZpY2Vfcm9sZSIsImlhdCI6MTc4MjQ4Mjk5NCwiZXhwIjoyMDk4MDU4OTk0fQ.8m3fbR4BcF7UlKuwkwHQu43Acuq1OQzaZGSIgYJ_3ug';

function sup(string $method, string $path, array $data = []): array {
    global $supabaseUrl, $supabaseKey;
    $ch = curl_init(rtrim($supabaseUrl, '/') . '/rest/v1/' . $path);
    $headers = [
        'apikey: ' . $supabaseKey,
        'Authorization: Bearer ' . $supabaseKey,
        'Content-Type: application/json',
        'Prefer: return=representation',
    ];
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_HTTPHEADER     => $headers,
    ]);
    if ($data) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    $body   = (string)curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['status' => $status, 'body' => json_decode($body, true)];
}

// 1. Récupérer l'ID de la branche eduform
$br = sup('GET', 'Branch?slug=eq.eduform&select=id');
if ($br['status'] !== 200 || empty($br['body'][0]['id'])) {
    echo "ERREUR: branche eduform introuvable\n";
    exit;
}
$branchId = $br['body'][0]['id'];
echo "Branche eduform ID: $branchId\n";

// 2. Compter les produits actifs et inactifs
$actifs = sup('GET', 'Product?branchId=eq.' . $branchId . '&active=eq.true&select=id');
$inactifs = sup('GET', 'Product?branchId=eq.' . $branchId . '&active=eq.false&select=id,slug');

$nbActifs   = is_array($actifs['body'])   ? count($actifs['body'])   : 0;
$nbInactifs = is_array($inactifs['body']) ? count($inactifs['body']) : 0;

echo "Produits actifs   : $nbActifs\n";
echo "Produits inactifs : $nbInactifs\n";
echo "---\n";

// 3. Réactiver tous les produits inactifs de la branche eduform
$reactivated = 0;
if ($nbInactifs > 0) {
    $res = sup('PATCH', 'Product?branchId=eq.' . $branchId . '&active=eq.false', ['active' => true]);
    if (in_array($res['status'], [200, 201, 204], true)) {
        $reactivated = $nbInactifs;
        echo "✅ $nbInactifs produits réactivés dans Supabase\n";
    } else {
        echo "❌ Erreur réactivation: HTTP " . $res['status'] . " — " . json_encode($res['body']) . "\n";
    }
}

// 4. Vérification finale
$apres = sup('GET', 'Product?branchId=eq.' . $branchId . '&active=eq.true&select=id');
$nbApres = is_array($apres['body']) ? count($apres['body']) : 0;
echo "Produits actifs APRES: $nbApres\n";
echo "\n✅ Script supprimé automatiquement.\n";

// 5. Auto-suppression
@unlink(__FILE__);
