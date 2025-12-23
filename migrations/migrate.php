<?php

declare(strict_types=1);

/**
 * Database Migration Script (SQLite)
 * 
 * Creates the products table and seeds it with sample data.
 * 
 * Usage: php migrations/migrate.php
 */

$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($key, $value) = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
    }
}

$dbPath = $_ENV['DB_PATH'] ?? __DIR__ . '/../database/titan.db';

echo "=== Titan Products API - Database Migration (SQLite) ===\n\n";

$dbDir = dirname($dbPath);
if (!is_dir($dbDir)) {
    echo "Creating database directory: {$dbDir}\n";
    mkdir($dbDir, 0755, true);
}

try {
    $pdo = new PDO("sqlite:{$dbPath}", null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    echo "Database: {$dbPath}\n\n";

    echo "Creating products table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS products (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            price DECIMAL(10, 2) NOT NULL,
            image_url VARCHAR(500),
            stock_quantity INTEGER DEFAULT 0,
            category VARCHAR(100),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");

    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_category ON products(category)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_created_at ON products(created_at)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_name ON products(name)");
    
    echo "Products table created successfully.\n\n";

    $stmt = $pdo->query("SELECT COUNT(*) as count FROM products");
    $count = $stmt->fetch()['count'];
    
    if ($count > 0) {
        echo "Products table already has {$count} records. Skipping seed.\n";
        echo "To reseed, delete the database file and run again.\n";
    } else {
        echo "Seeding sample products...\n";
        
        $products = [
            [
                'name' => 'Wireless Bluetooth Headphones',
                'description' => 'Premium noise-canceling wireless headphones with 30-hour battery life. Features include active noise cancellation, transparency mode, and premium audio drivers.',
                'price' => 149.99,
                'image_url' => 'https://picsum.photos/seed/headphones/400/400',
                'stock_quantity' => 50,
                'category' => 'Electronics',
            ],
            [
                'name' => 'Mechanical Gaming Keyboard',
                'description' => 'RGB backlit mechanical keyboard with Cherry MX switches. Programmable macros, dedicated media controls, and aircraft-grade aluminum frame.',
                'price' => 129.99,
                'image_url' => 'https://picsum.photos/seed/keyboard/400/400',
                'stock_quantity' => 75,
                'category' => 'Electronics',
            ],
            [
                'name' => 'Ergonomic Office Chair',
                'description' => 'Fully adjustable ergonomic chair with lumbar support, breathable mesh back, and 4D armrests. Perfect for long work sessions.',
                'price' => 399.99,
                'image_url' => 'https://picsum.photos/seed/chair/400/400',
                'stock_quantity' => 25,
                'category' => 'Furniture',
            ],
            [
                'name' => 'Smart Watch Pro',
                'description' => 'Advanced smartwatch with heart rate monitoring, GPS tracking, sleep analysis, and 7-day battery life. Water resistant to 50 meters.',
                'price' => 299.99,
                'image_url' => 'https://picsum.photos/seed/watch/400/400',
                'stock_quantity' => 100,
                'category' => 'Electronics',
            ],
            [
                'name' => 'Portable Power Bank 20000mAh',
                'description' => 'High-capacity portable charger with fast charging support. Features dual USB-A and USB-C ports, LED indicator, and compact design.',
                'price' => 49.99,
                'image_url' => 'https://picsum.photos/seed/powerbank/400/400',
                'stock_quantity' => 200,
                'category' => 'Electronics',
            ],
            [
                'name' => 'Standing Desk Converter',
                'description' => 'Height-adjustable standing desk converter with smooth gas spring mechanism. Spacious work surface with keyboard tray.',
                'price' => 249.99,
                'image_url' => 'https://picsum.photos/seed/desk/400/400',
                'stock_quantity' => 30,
                'category' => 'Furniture',
            ],
            [
                'name' => 'Wireless Mouse',
                'description' => 'Ergonomic wireless mouse with precision tracking and silent clicks. Long battery life and USB-C rechargeable.',
                'price' => 39.99,
                'image_url' => 'https://picsum.photos/seed/mouse/400/400',
                'stock_quantity' => 150,
                'category' => 'Electronics',
            ],
            [
                'name' => 'USB-C Hub 7-in-1',
                'description' => 'Multi-port USB-C hub with HDMI 4K output, SD card reader, USB 3.0 ports, and 100W power delivery pass-through.',
                'price' => 59.99,
                'image_url' => 'https://picsum.photos/seed/hub/400/400',
                'stock_quantity' => 80,
                'category' => 'Electronics',
            ],
            [
                'name' => 'LED Desk Lamp',
                'description' => 'Modern LED desk lamp with adjustable brightness and color temperature. Touch controls and USB charging port.',
                'price' => 44.99,
                'image_url' => 'https://picsum.photos/seed/lamp/400/400',
                'stock_quantity' => 60,
                'category' => 'Furniture',
            ],
            [
                'name' => 'Laptop Backpack',
                'description' => 'Water-resistant laptop backpack with padded compartment for up to 15.6" laptops. Multiple organizer pockets and USB charging port.',
                'price' => 69.99,
                'image_url' => 'https://picsum.photos/seed/backpack/400/400',
                'stock_quantity' => 90,
                'category' => 'Accessories',
            ],
        ];
        
        $stmt = $pdo->prepare("
            INSERT INTO products (name, description, price, image_url, stock_quantity, category)
            VALUES (:name, :description, :price, :image_url, :stock_quantity, :category)
        ");
        
        foreach ($products as $product) {
            $stmt->execute($product);
            echo "  - Added: {$product['name']}\n";
        }
        
        echo "\nSeeded " . count($products) . " products successfully.\n";
    }
    
    echo "\n=== Migration complete! ===\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}