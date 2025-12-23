<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Integration tests for the API endpoints
 * 
 * These tests verify the full request/response cycle.
 * Note: These require a running MySQL test database.
 */
class ApiIntegrationTest extends TestCase
{
    private PDO $pdo;
    private Product $product;
    
    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        
        $this->pdo->exec("
            CREATE TABLE products (
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
        
        Database::setInstance($this->pdo);
        $this->product = new Product($this->pdo);
    }
    
    protected function tearDown(): void
    {
        Database::resetInstance();
    }
    
    /**
     * Helper to seed products
     */
    private function seedProducts(int $count = 5): array
    {
        $ids = [];
        
        for ($i = 1; $i <= $count; $i++) {
            $result = $this->product->create([
                'name' => "Product {$i}",
                'description' => "Description for product {$i}",
                'price' => 9.99 * $i,
                'stock_quantity' => $i * 10,
                'category' => $i % 2 === 0 ? 'Electronics' : 'Furniture',
            ]);
            
            $ids[] = $result['id'];
        }
        
        return $ids;
    }

    public function testFullCrudWorkflow(): void
    {
        $createData = [
            'name' => 'Integration Test Product',
            'description' => 'Created during integration test',
            'price' => 199.99,
            'stock_quantity' => 25,
            'category' => 'Test',
        ];
        
        $created = $this->product->create($createData);
        
        $this->assertNotNull($created);
        $this->assertEquals('Integration Test Product', $created['name']);
        $this->assertEquals(199.99, $created['price']);
        
        $productId = $created['id'];

        $fetched = $this->product->getById($productId);
        
        $this->assertNotNull($fetched);
        $this->assertEquals($created['name'], $fetched['name']);

        $updated = $this->product->update($productId, [
            'name' => 'Updated Integration Product',
            'price' => 249.99,
        ]);
        
        $this->assertNotNull($updated);
        $this->assertEquals('Updated Integration Product', $updated['name']);
        $this->assertEquals(249.99, $updated['price']);
        $this->assertEquals('Test', $updated['category']);

        $deleted = $this->product->delete($productId);
        
        $this->assertTrue($deleted);
        $this->assertNull($this->product->getById($productId));
    }

    public function testPaginationWorkflow(): void
    {
        $this->seedProducts(10);

        $page1 = $this->product->getAll(3, 0);
        $this->assertCount(3, $page1);

        $page2 = $this->product->getAll(3, 3);
        $this->assertCount(3, $page2);

        $page1Ids = array_column($page1, 'id');
        $page2Ids = array_column($page2, 'id');
        
        $this->assertEmpty(array_intersect($page1Ids, $page2Ids));

        $lastPage = $this->product->getAll(3, 9);
        $this->assertCount(1, $lastPage);

        $total = $this->product->count();
        $this->assertEquals(10, $total);
    }

    public function testSearchAndFilterWorkflow(): void
    {
        $this->product->create(['name' => 'Apple iPhone 15', 'price' => 999, 'category' => 'Electronics']);
        $this->product->create(['name' => 'Apple MacBook Pro', 'price' => 1999, 'category' => 'Electronics']);
        $this->product->create(['name' => 'Samsung Galaxy', 'price' => 899, 'category' => 'Electronics']);
        $this->product->create(['name' => 'IKEA Desk', 'price' => 299, 'category' => 'Furniture']);

        $appleProducts = $this->product->search('Apple');
        $this->assertCount(2, $appleProducts);

        $electronics = $this->product->getByCategory('Electronics');
        $this->assertCount(3, $electronics);
        
        $furniture = $this->product->getByCategory('Furniture');
        $this->assertCount(1, $furniture);

        $limitedApple = $this->product->search('Apple', 1);
        $this->assertCount(1, $limitedApple);
    }

    public function testDataIntegrityAcrossOperations(): void
    {
        $original = $this->product->create([
            'name' => 'Integrity Test Product',
            'description' => 'Testing data integrity',
            'price' => 123.45,
            'image_url' => 'https://example.com/test.jpg',
            'stock_quantity' => 100,
            'category' => 'Test Category',
        ]);

        $fetched = $this->product->getById($original['id']);
        
        $this->assertEquals($original['name'], $fetched['name']);
        $this->assertEquals($original['description'], $fetched['description']);
        $this->assertEquals($original['price'], $fetched['price']);
        $this->assertEquals($original['image_url'], $fetched['image_url']);
        $this->assertEquals($original['stock_quantity'], $fetched['stock_quantity']);
        $this->assertEquals($original['category'], $fetched['category']);

        $this->product->update($original['id'], ['name' => 'Updated Name']);
        
        $afterUpdate = $this->product->getById($original['id']);
        
        $this->assertEquals('Updated Name', $afterUpdate['name']);
        $this->assertEquals($original['description'], $afterUpdate['description']);
        $this->assertEquals($original['price'], $afterUpdate['price']);
        $this->assertEquals($original['stock_quantity'], $afterUpdate['stock_quantity']);
    }

    public function testConcurrentOperations(): void
    {
        $ids = [];
        
        for ($i = 0; $i < 20; $i++) {
            $result = $this->product->create([
                'name' => "Concurrent Product {$i}",
                'price' => 10 + $i,
            ]);
            $ids[] = $result['id'];
        }

        $this->assertCount(20, $ids);
        $this->assertEquals(20, $this->product->count());

        foreach ($ids as $id) {
            $this->product->update($id, ['stock_quantity' => $id * 10]);
        }

        foreach ($ids as $id) {
            $product = $this->product->getById($id);
            $this->assertEquals($id * 10, $product['stock_quantity']);
        }

        for ($i = 0; $i < 10; $i++) {
            $this->product->delete($ids[$i]);
        }
        
        $this->assertEquals(10, $this->product->count());
    }

    public function testEdgeCases(): void
    {
        $zeroPrice = $this->product->create([
            'name' => 'Free Product',
            'price' => 0,
        ]);
        
        $this->assertEquals(0, $zeroPrice['price']);

        $longDesc = str_repeat('A', 10000);
        $longProduct = $this->product->create([
            'name' => 'Long Description Product',
            'price' => 50,
            'description' => $longDesc,
        ]);
        
        $this->assertEquals($longDesc, $longProduct['description']);

        $specialProduct = $this->product->create([
            'name' => "Product with 'quotes' and \"double quotes\" & ampersand",
            'price' => 99,
        ]);
        
        $this->assertStringContainsString('quotes', $specialProduct['name']);

        $this->product->update($specialProduct['id'], [
            'description' => null,
            'image_url' => null,
        ]);
        
        $nullProduct = $this->product->getById($specialProduct['id']);
        $this->assertNull($nullProduct['description']);
    }

    public function testLargeDatasetPerformance(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $this->product->create([
                'name' => "Bulk Product {$i}",
                'price' => rand(10, 1000),
                'category' => ['Electronics', 'Furniture', 'Accessories'][$i % 3],
            ]);
        }
        
        $startTime = microtime(true);

        $page = $this->product->getAll(20, 40);
        
        $paginationTime = microtime(true) - $startTime;
        
        $this->assertCount(20, $page);
        $this->assertLessThan(1.0, $paginationTime, 'Pagination should complete in under 1 second');

        $startTime = microtime(true);
        
        $searchResults = $this->product->search('Bulk', 50);
        
        $searchTime = microtime(true) - $startTime;
        
        $this->assertLessThan(1.0, $searchTime, 'Search should complete in under 1 second');

        $startTime = microtime(true);
        
        $categoryResults = $this->product->getByCategory('Electronics');
        
        $categoryTime = microtime(true) - $startTime;
        
        $this->assertLessThan(1.0, $categoryTime, 'Category filter should complete in under 1 second');
    }
}
