<?php
declare(strict_types=1);

/* =========================================
   DATABASE — IBIG EDUFORM
========================================= */

require_once __DIR__ . '/config.php';

final class Database
{
    private static ?PDO $pdo = null;

    /**
     * Retourne une instance PDO unique (Singleton)
     */
    public static function connect(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $dsn = 'mysql:host=' . DB_HOST .
               ';dbname=' . DB_NAME .
               ';charset=' . DB_CHARSET;

        try {
            self::$pdo = new PDO(
                $dsn,
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                    PDO::ATTR_PERSISTENT         => false,
                ]
            );
        } catch (PDOException $e) {
            // En production, ne jamais afficher l’erreur
            if (APP_ENV !== 'production') {
                die('Erreur connexion DB : ' . $e->getMessage());
            }

            http_response_code(500);
            exit('Erreur interne. Connexion à la base impossible.');
        }

        return self::$pdo;
    }

    /**
     * Empêche l’instanciation
     */
    private function __construct() {}
    private function __clone() {}
}
