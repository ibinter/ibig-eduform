<?php
declare(strict_types=1);

/* ============================================================
   AUTH APPRENANT — magic-link session
   Session key : $_SESSION['apprenant']
   Séparée de la session admin ($_SESSION['user'])
============================================================ */

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function apprenant_check(): bool {
    return isset($_SESSION['apprenant']) && is_array($_SESSION['apprenant']) && !empty($_SESSION['apprenant']['email']);
}

function apprenant_get(): ?array {
    return apprenant_check() ? $_SESSION['apprenant'] : null;
}

function apprenant_login(string $email, string $nom, string $prenoms): void {
    $_SESSION['apprenant'] = [
        'email'   => $email,
        'nom'     => $nom,
        'prenoms' => $prenoms,
    ];
}

function apprenant_logout(): void {
    unset($_SESSION['apprenant']);
}

function apprenant_require(): void {
    if (!apprenant_check()) {
        header('Location: /apprenant/login.php');
        exit;
    }
}

/* Génère un token unique et l'insère en base. Retourne le token. */
function apprenant_create_token(PDO $pdo, string $email): string {
    $token = bin2hex(random_bytes(32)); // 64 chars hex

    // Crée la table si elle n'existe pas encore (sécurité)
    $pdo->exec("CREATE TABLE IF NOT EXISTS apprenant_tokens (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        token CHAR(64) NOT NULL UNIQUE,
        expires_at DATETIME NOT NULL,
        used_at DATETIME DEFAULT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_token (token),
        INDEX idx_email (email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Invalide les anciens tokens de cet email
    $pdo->prepare("DELETE FROM apprenant_tokens WHERE email = ?")->execute([$email]);

    // expires_at calculé en SQL (timezone MySQL) pour éviter les décalages PHP/MySQL
    $pdo->prepare("INSERT INTO apprenant_tokens (email, token, expires_at) VALUES (?, ?, NOW() + INTERVAL 1 HOUR)")
        ->execute([$email, $token]);

    return $token;
}

/* Vérifie le token, retourne l'email ou null. */
function apprenant_verify_token(PDO $pdo, string $token): ?string {
    $stmt = $pdo->prepare("
        SELECT email FROM apprenant_tokens
        WHERE token = ? AND expires_at > NOW() AND used_at IS NULL
        LIMIT 1
    ");
    $stmt->execute([$token]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) return null;

    // Marque le token comme utilisé
    $pdo->prepare("UPDATE apprenant_tokens SET used_at = NOW() WHERE token = ?")
        ->execute([$token]);

    return (string)$row['email'];
}
