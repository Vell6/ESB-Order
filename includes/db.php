<?php
/**
 * RM bu ani - Database Connection Layer (PDO Singleton)
 */

require_once __DIR__ . '/../config.php';

class Database {
    private static ?PDO $instance = null;

    private function __construct() {}
    private function __clone() {}

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];

            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                // If database doesn't exist yet, handle gracefully (e.g. during initial setup)
                if (str_contains($e->getMessage(), 'Unknown database') || $e->getCode() == 1049) {
                    throw new Exception("Database '" . DB_NAME . "' belum dibuat. Silakan jalankan installer di <a href='" . BASE_URL . "setup_db.php'>setup_db.php</a>.");
                }
                throw new Exception("Koneksi Database Gagal: " . $e->getMessage());
            }
        }

        return self::$instance;
    }
}

/**
 * Global helper function to get DB instance quickly
 */
function getDB(): PDO {
    return Database::getConnection();
}
