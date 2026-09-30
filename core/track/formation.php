<?php
declare(strict_types=1);

function detect_formation_id(PDO $pdo, string $page): ?int
{
    if (isset($_GET['formation']) && ctype_digit((string)$_GET['formation'])) {
        return (int) $_GET['formation'];
    }
    if (preg_match('#^/formation/([^/?]+)#', $page, $m)) {
        $q = $pdo->prepare("SELECT id FROM formations WHERE slug = ? LIMIT 1");
        $q->execute([$m[1]]);
        return (int) ($q->fetchColumn() ?: null);
    }
    return null;
}
