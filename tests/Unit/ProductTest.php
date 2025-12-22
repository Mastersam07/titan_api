<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the Product model
 */
class ProductTest extends TestCase
{
    private PDO $pdo;
    private Product $product;
    
    protected function setUp(): void
    {
        // Create in-memory SQLite database for testing
        $this->pdo = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        
        // Create products table
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
        
        // Inject the test database
        Database::setInstance($this->pdo);
        
        $this->product = new Product($this->pdo);
    }
    
    protected function tearDown(): void
    {
        Database::resetInstance();
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
    
    // =========================================
    // getAll() Tests
    // =========================================
    
    public function testGetAllReturnsEmptyArrayWhenNoProducts(): void
    {
        $result = $this->product->getAll();
        
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
    
    public function testGetAllReturnsAllProducts(): void
    {
        $this->seedProduct(['name' => 'Product 1']);
        $this->seedProduct(['name' => 'Product 2']);
        $this->seedProduct(['name' => 'Product 3']);
        
        $result = $this->product->getAll();
        
        $this->assertCount(3, $result);
    }
    
    public function testGetAllRespectsLimit(): void
    {
        $this->seedProduct(['name' => 'Product 1']);
        $this->seedProduct(['name' => 'Product 2']);
        $this->seedProduct(['name' => 'Product 3']);
        
        $result = $this->product->getAll(2);
        
        $this->assertCount(2, $result);
    }
    
    public function testGetAllRespectsOffset(): void
    {
        $this->seedProduct(['name' => 'Product 1']);
        $this->seedProduct(['name' => 'Product 2']);
        $this->seedProduct(['name' => 'Product 3']);
        
        $result = $this->product->getAll(10, 2);
        
        $this->assertCount(1, $result);
    }
    
    // =========================================
    // count() Tests
    // =========================================
    
    public function testCountReturnsZeroWhenNoProducts(): void
    {
        $result = $this->product->count();
        
        $this->assertEquals(0, $result);
    }
    
    public function testCountReturnsCorrectCount(): void
    {
        $this->seedProduct();
        $this->seedProduct();
        $this->seedProduct();
        
        $result = $this->product->count();
        
        $this->assertEquals(3, $result);
    }
    
    // =========================================
    // getById() Tests
    // =========================================
    
    public function testGetByIdReturnsNullWhenNotFound(): void
    {
        $result = $this->product->getById(999);
        
        $this->assertNull($result);
    }
    
    public function testGetByIdReturnsProduct(): void
    {
        $id = $this->seedProduct(['name' => 'Specific Product', 'price' => 49.99]);
        
        $result = $this->product->getById($id);
        
        $this->assertNotNull($result);
        $this->assertEquals('Specific Product', $result['name']);
        $this->assertEquals(49.99, $result['price']);
    }
    
    public function testGetByIdReturnsAllFields(): void
    {
        $id = $this->seedProduct([
            'name' => 'Full Product',
            'description' => 'Full Description',
            'price' => 199.99,
            'image_url' => 'https://example.com/full.jpg',
            'stock_quantity' => 50,
            'category' => 'Full Category',
        ]);
        
        $result = $this->product->getById($id);
        
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('description', $result);
        $this->assertArrayHasKey('price', $result);
        $this->assertArrayHasKey('image_url', $result);
        $this->assertArrayHasKey('stock_quantity', $result);
        $this->assertArrayHasKey('category', $result);
        $this->assertArrayHasKey('created_at', $result);
        $this->assertArrayHasKey('updated_at', $result);
    }
    
    // =========================================
    // create() Tests
    // =========================================
    
    public function testCreateProductSuccessfully(): void
    {
        $data = [
            'name' => 'New Product',
            'price' => 29.99,
            'description' => 'New Description',
        ];
        
        $result = $this->product->create($data);
        
        $this->assertNotNull($result);
        $this->assertEquals('New Product', $result['name']);
        $this->assertEquals(29.99, $result['price']);
    }
    
    public function testCreateProductWithMinimalData(): void
    {
        $data = [
            'name' => 'Minimal Product',
            'price' => 9.99,
        ];
        
        $result = $this->product->create($data);
        
        $this->assertNotNull($result);
        $this->assertEquals('Minimal Product', $result['name']);
    }
    
    public function testCreateProductThrowsExceptionWithoutName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Product name is required');
        
        $this->product->create(['price' => 9.99]);
    }
    
    public function testCreateProductThrowsExceptionWithEmptyName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Product name is required');
        
        $this->product->create(['name' => '', 'price' => 9.99]);
    }
    
    public function testCreateProductThrowsExceptionWithoutPrice(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Valid product price is required');
        
        $this->product->create(['name' => 'No Price']);
    }
    
    public function testCreateProductThrowsExceptionWithNegativePrice(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Product price cannot be negative');
        
        $this->product->create(['name' => 'Negative Price', 'price' => -10]);
    }
    
    public function testCreateProductThrowsExceptionWithInvalidPrice(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Valid product price is required');
        
        $this->product->create(['name' => 'Invalid Price', 'price' => 'not a number']);
    }
    
    public function testCreateProductThrowsExceptionWithNegativeStock(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Stock quantity cannot be negative');
        
        $this->product->create([
            'name' => 'Negative Stock',
            'price' => 9.99,
            'stock_quantity' => -5,
        ]);
    }
    
    // =========================================
    // update() Tests
    // =========================================
    
    public function testUpdateProductSuccessfully(): void
    {
        $id = $this->seedProduct(['name' => 'Original Name', 'price' => 50.00]);
        
        $result = $this->product->update($id, ['name' => 'Updated Name']);
        
        $this->assertNotNull($result);
        $this->assertEquals('Updated Name', $result['name']);
        $this->assertEquals(50.00, $result['price']); // Price unchanged
    }
    
    public function testUpdateProductReturnsNullWhenNotFound(): void
    {
        $result = $this->product->update(999, ['name' => 'Does Not Exist']);
        
        $this->assertNull($result);
    }
    
    public function testUpdateProductWithMultipleFields(): void
    {
        $id = $this->seedProduct();
        
        $result = $this->product->update($id, [
            'name' => 'Multi Update',
            'price' => 199.99,
            'category' => 'New Category',
        ]);
        
        $this->assertEquals('Multi Update', $result['name']);
        $this->assertEquals(199.99, $result['price']);
        $this->assertEquals('New Category', $result['category']);
    }
    
    public function testUpdateProductIgnoresInvalidFields(): void
    {
        $id = $this->seedProduct(['name' => 'Original']);
        
        $result = $this->product->update($id, [
            'name' => 'Valid Update',
            'invalid_field' => 'Should be ignored',
            'another_invalid' => 123,
        ]);
        
        $this->assertEquals('Valid Update', $result['name']);
    }
    
    public function testUpdateProductWithEmptyDataReturnsExisting(): void
    {
        $id = $this->seedProduct(['name' => 'No Changes']);
        
        $result = $this->product->update($id, []);
        
        $this->assertEquals('No Changes', $result['name']);
    }
    
    // =========================================
    // delete() Tests
    // =========================================
    
    public function testDeleteProductSuccessfully(): void
    {
        $id = $this->seedProduct();
        
        $result = $this->product->delete($id);
        
        $this->assertTrue($result);
        $this->assertNull($this->product->getById($id));
    }
    
    public function testDeleteProductReturnsFalseWhenNotFound(): void
    {
        $result = $this->product->delete(999);
        
        $this->assertFalse($result);
    }
    
    // =========================================
    // search() Tests
    // =========================================
    
    public function testSearchByName(): void
    {
        $this->seedProduct(['name' => 'Apple iPhone']);
        $this->seedProduct(['name' => 'Samsung Galaxy']);
        $this->seedProduct(['name' => 'Apple Watch']);
        
        $result = $this->product->search('Apple');
        
        $this->assertCount(2, $result);
    }
    
    public function testSearchByDescription(): void
    {
        $this->seedProduct(['name' => 'Product 1', 'description' => 'Contains keyword here']);
        $this->seedProduct(['name' => 'Product 2', 'description' => 'No match']);
        
        $result = $this->product->search('keyword');
        
        $this->assertCount(1, $result);
    }
    
    public function testSearchReturnsEmptyForNoMatch(): void
    {
        $this->seedProduct(['name' => 'Something Else']);
        
        $result = $this->product->search('nonexistent');
        
        $this->assertEmpty($result);
    }
    
    public function testSearchRespectsLimit(): void
    {
        $this->seedProduct(['name' => 'Match 1']);
        $this->seedProduct(['name' => 'Match 2']);
        $this->seedProduct(['name' => 'Match 3']);
        
        $result = $this->product->search('Match', 2);
        
        $this->assertCount(2, $result);
    }
    
    // =========================================
    // getByCategory() Tests
    // =========================================
    
    public function testGetByCategory(): void
    {
        $this->seedProduct(['name' => 'Phone', 'category' => 'Electronics']);
        $this->seedProduct(['name' => 'Laptop', 'category' => 'Electronics']);
        $this->seedProduct(['name' => 'Chair', 'category' => 'Furniture']);
        
        $result = $this->product->getByCategory('Electronics');
        
        $this->assertCount(2, $result);
    }
    
    public function testGetByCategoryReturnsEmptyForNoMatch(): void
    {
        $this->seedProduct(['name' => 'Product', 'category' => 'Electronics']);
        
        $result = $this->product->getByCategory('NonExistent');
        
        $this->assertEmpty($result);
    }
    
    public function testGetByCategoryRespectsLimitAndOffset(): void
    {
        $this->seedProduct(['name' => 'Product 1', 'category' => 'Test']);
        $this->seedProduct(['name' => 'Product 2', 'category' => 'Test']);
        $this->seedProduct(['name' => 'Product 3', 'category' => 'Test']);
        
        $result = $this->product->getByCategory('Test', 2, 1);
        
        $this->assertCount(2, $result);
    }
}
