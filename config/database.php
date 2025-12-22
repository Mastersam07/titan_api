<?php

declare(strict_types=1);

class Database
{
    private static ?PDO $instance = null;
    
    private function __construct() {}
    
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $host = $_ENV['DB_HOST'] ?? 'localhost';
            $dbname = $_ENV['DB_NAME'] ?? 'titan_products';
            $username = $_ENV['DB_USER'] ?? 'root';
            $password = $_ENV['DB_PASS'] ?? '';
            $charset = 'utf8mb4';
            
            $dsn = "mysql:host={$host};dbname={$dbname};charset={$charset}";
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            try {
                self::$instance = new PDO($dsn, $username, $password, $options);
            } catch (PDOException $e) {
                throw new RuntimeException('Database connection failed: ' . $e->getMessage());
            }
        }
        
        return self::$instance;
    }
    
    /**
     * For testing purposes - allows injecting a mock PDO instance
     */
    public static function setInstance(?PDO $pdo): void
    {
        self::$instance = $pdo;
    }
    
    /**
     * Reset the instance (useful for testing)
     */
    public static function resetInstance(): void
    {
        self::$instance = null;
    }
}
