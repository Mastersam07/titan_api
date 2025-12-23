<?php

declare(strict_types=1);

class Database
{
    private static ?PDO $instance = null;
    
    private function __construct() {}
    
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $dbPath = $_ENV['DB_PATH'] ?? __DIR__ . '/../database/titan.db';
            
            $dbDir = dirname($dbPath);
            if (!is_dir($dbDir)) {
                mkdir($dbDir, 0755, true);
            }

            $dsn = "sqlite:{$dbPath}";

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            try {
                self::$instance = new PDO($dsn, null, null, $options);
                self::$instance->exec('PRAGMA foreign_keys = ON');
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