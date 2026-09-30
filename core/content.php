<?php
declare(strict_types=1);

/* =========================================================
   CONTENU ADMINISTRABLE — IBIG EDUFORM
   Lecture tolérante : si la table n'existe pas encore
   (migration non exécutée), on renvoie un repli vide/null
   et le site continue d'afficher son contenu figé.
========================================================= */

if (!function_exists('hero_slides_db')) {
    /**
     * Slides actifs du carrousel, triés par position.
     * @return array<int,array> [] si table absente/vide.
     */
    function hero_slides_db(): array
    {
        try {
            $pdo = Database::connect();
            $rows = $pdo->query(
                "SELECT * FROM hero_slides WHERE is_active = 1 ORDER BY position ASC, id ASC"
            )->fetchAll(PDO::FETCH_ASSOC);
            return $rows ?: [];
        } catch (Throwable $e) {
            return [];
        }
    }
}

if (!function_exists('page_get')) {
    /**
     * Page éditable publiée par slug, ou null (repli sur le contenu figé).
     */
    function page_get(string $slug): ?array
    {
        try {
            $pdo = Database::connect();
            $stmt = $pdo->prepare(
                "SELECT * FROM site_pages WHERE slug = ? AND is_published = 1 LIMIT 1"
            );
            $stmt->execute([$slug]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (Throwable $e) {
            return null;
        }
    }
}
