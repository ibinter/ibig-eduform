<?php
declare(strict_types=1);
require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/apprenant_auth.php';

$pdo   = Database::connect();
$token = trim((string)($_GET['t'] ?? ''));
$email = $token !== '' ? apprenant_verify_token($pdo, $token) : null;

if (!$email) {
    $pageTitle = 'Lien expiré — IBIG EDUFORM';
    include __DIR__ . '/../partials/header.php';
    echo '<div style="max-width:480px;margin:120px auto;text-align:center;color:#e5e7eb">
        <div style="font-size:3rem">⏱️</div>
        <h2 style="color:#f59e0b">Lien expiré ou invalide</h2>
        <p style="color:#94a3b8">Ce lien de connexion est invalide ou a déjà été utilisé. Les liens sont valables 1 heure.</p>
        <a href="/apprenant/login.php" style="display:inline-block;margin-top:20px;padding:12px 24px;background:#f59e0b;color:#0a1733;font-weight:800;border-radius:10px;text-decoration:none">
            ← Demander un nouveau lien
        </a>
    </div>';
    include __DIR__ . '/../partials/footer.php';
    exit;
}

// Récupère les infos de l'apprenant depuis ses preinscriptions
$stmt = $pdo->prepare("SELECT nom, prenoms FROM preinscriptions WHERE LOWER(email) = ? ORDER BY created_at DESC LIMIT 1");
$stmt->execute([strtolower($email)]);
$row = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['nom' => '', 'prenoms' => ''];

apprenant_login($email, (string)$row['nom'], (string)$row['prenoms']);

header('Location: /apprenant/dashboard.php');
exit;
