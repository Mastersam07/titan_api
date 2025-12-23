<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the ProductController
 */
class ProductControllerTest extends TestCase
{
    private PDO $pdo;
    private Product $productModel;
    private ProductController $controller;
    
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
        
        $this->productModel = new Product($this->pdo);
        $this->controller = new ProductController($this->productModel);

        $_GET = [];
        $_POST = [];
    }
    
    protected function tearDown(): void
    {
        Database::resetInstance();
        $_GET = [];
        $_POST = [];
    }
    
    /**
     * Helper to seed a product
     */
    private function seedProduct(array $overrides = []): int
    {
        $defaults = [
            'name' => 'Test Product',
            'description' => 'Test Description',
            'price' => 99.99,
            'image_url' => 'https://example.com/image.jpg',
            'stock_quantity' => 10,
            'category' => 'Test Category',
        ];
        
        $data = array_merge($defaults, $overrides);
        
        $stmt = $this->pdo->prepare("
            INSERT INTO products (name, description, price, image_url, stock_quantity, category)
            VALUES (:name, :description, :price, :image_url, :stock_quantity, :category)
        ");
        
        $stmt->execute($data);
        
        return (int) $this->pdo->lastInsertId();
    }
    
    /**
     * Capture output from controller methods
     */
    private function captureOutput(callable $callback): array
    {
        ob_start();
        $callback();
        $output = ob_get_clean();
        
        return json_decode($output, true) ?? [];
    }

    public function testIndexReturnsEmptyArray(): void
    {
        $result = $this->captureOutput(fn() => $this->controller->index());
        
        $this->assertTrue($result['success']);
        $this->assertIsArray($result['data']);
        $this->assertEmpty($result['data']);
        $this->assertEquals(0, $result['meta']['total']);
    }
    
    public function testIndexReturnsProducts(): void
    {
        $this->seedProduct(['name' => 'Product 1']);
        $this->seedProduct(['name' => 'Product 2']);
        
        $result = $this->captureOutput(fn() => $this->controller->index());
        
        $this->assertTrue($result['success']);
        $this->assertCount(2, $result['data']);
        $this->assertEquals(2, $result['meta']['total']);
    }
    
    public function testIndexRespectsLimitParameter(): void
    {
        $this->seedProduct(['name' => 'Product 1']);
        $this->seedProduct(['name' => 'Product 2']);
        $this->seedProduct(['name' => 'Product 3']);
        
        $_GET['limit'] = 2;
        
        $result = $this->captureOutput(fn() => $this->controller->index());
        
        $this->assertCount(2, $result['data']);
        $this->assertEquals(2, $result['meta']['limit']);
    }
    
    public function testIndexRespectsOffsetParameter(): void
    {
        $this->seedProduct(['name' => 'Product 1']);
        $this->seedProduct(['name' => 'Product 2']);
        $this->seedProduct(['name' => 'Product 3']);
        
        $_GET['offset'] = 2;
        
        $result = $this->captureOutput(fn() => $this->controller->index());
        
        $this->assertCount(1, $result['data']);
        $this->assertEquals(2, $result['meta']['offset']);
    }
    
    public function testIndexFiltersByCategory(): void
    {
        $this->seedProduct(['name' => 'Phone', 'category' => 'Electronics']);
        $this->seedProduct(['name' => 'Chair', 'category' => 'Furniture']);
        
        $_GET['category'] = 'Electronics';
        
        $result = $this->captureOutput(fn() => $this->controller->index());
        
        $this->assertCount(1, $result['data']);
        $this->assertEquals('Phone', $result['data'][0]['name']);
    }
    
    public function testIndexSearchesProducts(): void
    {
        $this->seedProduct(['name' => 'Apple iPhone']);
        $this->seedProduct(['name' => 'Samsung Galaxy']);
        
        $_GET['search'] = 'Apple';
        
        $result = $this->captureOutput(fn() => $this->controller->index());
        
        $this->assertCount(1, $result['data']);
        $this->assertStringContainsString('Apple', $result['data'][0]['name']);
    }
    
    public function testIndexCapsLimitAt100(): void
    {
        $_GET['limit'] = 500;
        
        $result = $this->captureOutput(fn() => $this->controller->index());
        
        $this->assertEquals(100, $result['meta']['limit']);
    }

    public function testShowReturnsProduct(): void
    {
        $id = $this->seedProduct(['name' => 'Specific Product', 'price' => 49.99]);
        
        $result = $this->captureOutput(fn() => $this->controller->show($id));
        
        $this->assertTrue($result['success']);
        $this->assertEquals('Specific Product', $result['data']['name']);
        $this->assertEquals(49.99, $result['data']['price']);
    }
    
    public function testShowReturns404ForNonExistent(): void
    {
        $result = $this->captureOutput(fn() => $this->controller->show(999));
        
        $this->assertFalse($result['success']);
        $this->assertEquals(404, $result['error']['code']);
        $this->assertEquals('Product not found', $result['error']['message']);
    }

    public function testDestroyDeletesProduct(): void
    {
        $id = $this->seedProduct();
        
        $result = $this->captureOutput(fn() => $this->controller->destroy($id));
        
        $this->assertTrue($result['success']);
        $this->assertEquals('Product deleted successfully', $result['message']);
        $this->assertNull($this->productModel->getById($id));
    }
    
    public function testDestroyReturns404ForNonExistent(): void
    {
        $result = $this->captureOutput(fn() => $this->controller->destroy(999));
        
        $this->assertFalse($result['success']);
        $this->assertEquals(404, $result['error']['code']);
    }

    public function testSuccessResponseFormat(): void
    {
        $this->seedProduct();
        
        $result = $this->captureOutput(fn() => $this->controller->index());
        
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('meta', $result);
    }
    
    public function testErrorResponseFormat(): void
    {
        $result = $this->captureOutput(fn() => $this->controller->show(999));
        
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('error', $result);
        $this->assertArrayHasKey('code', $result['error']);
        $this->assertArrayHasKey('message', $result['error']);
    }
    
    public function testMetaContainsPaginationInfo(): void
    {
        $this->seedProduct();
        
        $_GET['limit'] = 10;
        $_GET['offset'] = 5;
        
        $result = $this->captureOutput(fn() => $this->controller->index());
        
        $this->assertArrayHasKey('total', $result['meta']);
        $this->assertArrayHasKey('limit', $result['meta']);
        $this->assertArrayHasKey('offset', $result['meta']);
        $this->assertEquals(10, $result['meta']['limit']);
        $this->assertEquals(5, $result['meta']['offset']);
    }
}
