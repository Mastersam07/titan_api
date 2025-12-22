<?php

declare(strict_types=1);

use OpenApi\Attributes as OA;

require_once __DIR__ . '/../config/database.php';

/**
 * Product Model with OpenAPI Schema definitions
 */
#[OA\Schema(
    schema: 'Product',
    title: 'Product',
    description: 'Product entity',
    required: ['id', 'name', 'price'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'Wireless Bluetooth Headphones'),
        new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Premium noise-canceling wireless headphones with 30-hour battery life.'),
        new OA\Property(property: 'price', type: 'number', format: 'float', example: 149.99),
        new OA\Property(property: 'image_url', type: 'string', format: 'uri', nullable: true, example: 'https://picsum.photos/seed/headphones/400/400'),
        new OA\Property(property: 'stock_quantity', type: 'integer', example: 50),
        new OA\Property(property: 'category', type: 'string', nullable: true, example: 'Electronics'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2024-01-15T10:30:00Z'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2024-01-15T10:30:00Z'),
    ]
)]
#[OA\Schema(
    schema: 'ProductInput',
    title: 'ProductInput',
    description: 'Product creation payload',
    required: ['name', 'price'],
    properties: [
        new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'New Product'),
        new OA\Property(property: 'description', type: 'string', example: 'Product description here'),
        new OA\Property(property: 'price', type: 'number', format: 'float', minimum: 0, example: 29.99),
        new OA\Property(property: 'image_url', type: 'string', format: 'uri', example: 'https://example.com/image.jpg'),
        new OA\Property(property: 'stock_quantity', type: 'integer', minimum: 0, example: 100),
        new OA\Property(property: 'category', type: 'string', example: 'Electronics'),
    ]
)]
#[OA\Schema(
    schema: 'ProductUpdateInput',
    title: 'ProductUpdateInput',
    description: 'Product update payload (all fields optional)',
    properties: [
        new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'Updated Product Name'),
        new OA\Property(property: 'description', type: 'string', example: 'Updated description'),
        new OA\Property(property: 'price', type: 'number', format: 'float', minimum: 0, example: 39.99),
        new OA\Property(property: 'image_url', type: 'string', format: 'uri', example: 'https://example.com/new-image.jpg'),
        new OA\Property(property: 'stock_quantity', type: 'integer', minimum: 0, example: 75),
        new OA\Property(property: 'category', type: 'string', example: 'Accessories'),
    ]
)]
#[OA\Schema(
    schema: 'ErrorResponse',
    title: 'ErrorResponse',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: false),
        new OA\Property(
            property: 'error',
            type: 'object',
            properties: [
                new OA\Property(property: 'code', type: 'integer', example: 404),
                new OA\Property(property: 'message', type: 'string', example: 'Product not found'),
            ]
        ),
    ]
)]
#[OA\Schema(
    schema: 'PaginationMeta',
    title: 'PaginationMeta',
    properties: [
        new OA\Property(property: 'total', type: 'integer', example: 100),
        new OA\Property(property: 'limit', type: 'integer', example: 20),
        new OA\Property(property: 'offset', type: 'integer', example: 0),
    ]
)]
class Product
{
    private PDO $db;
    
    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getInstance();
    }
    
    /**
     * Get all products with optional pagination
     */
    public function getAll(int $limit = 100, int $offset = 0): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, name, description, price, image_url, stock_quantity, category, created_at, updated_at 
             FROM products 
             ORDER BY created_at DESC 
             LIMIT :limit OFFSET :offset'
        );
        
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Get total count of products
     */
    public function count(): int
    {
        $stmt = $this->db->query('SELECT COUNT(*) as total FROM products');
        $result = $stmt->fetch();
        
        return (int) $result['total'];
    }
    
    /**
     * Get a single product by ID
     */
    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, name, description, price, image_url, stock_quantity, category, created_at, updated_at 
             FROM products 
             WHERE id = :id'
        );
        
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch();
        
        return $result ?: null;
    }
    
    /**
     * Create a new product
     */
    public function create(array $data): array
    {
        $this->validateProductData($data);
        
        $stmt = $this->db->prepare(
            'INSERT INTO products (name, description, price, image_url, stock_quantity, category, created_at, updated_at) 
             VALUES (:name, :description, :price, :image_url, :stock_quantity, :category, NOW(), NOW())'
        );
        
        $stmt->execute([
            ':name' => $data['name'],
            ':description' => $data['description'] ?? null,
            ':price' => $data['price'],
            ':image_url' => $data['image_url'] ?? null,
            ':stock_quantity' => $data['stock_quantity'] ?? 0,
            ':category' => $data['category'] ?? null,
        ]);
        
        $id = (int) $this->db->lastInsertId();
        
        return $this->getById($id);
    }
    
    /**
     * Update an existing product
     */
    public function update(int $id, array $data): ?array
    {
        $existing = $this->getById($id);
        
        if (!$existing) {
            return null;
        }
        
        $fields = [];
        $params = [':id' => $id];
        
        $allowedFields = ['name', 'description', 'price', 'image_url', 'stock_quantity', 'category'];
        
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = :{$field}";
                $params[":{$field}"] = $data[$field];
            }
        }
        
        if (empty($fields)) {
            return $existing;
        }
        
        $fields[] = 'updated_at = NOW()';
        
        $sql = 'UPDATE products SET ' . implode(', ', $fields) . ' WHERE id = :id';
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $this->getById($id);
    }
    
    /**
     * Delete a product
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM products WHERE id = :id');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Search products by name or description
     */
    public function search(string $query, int $limit = 50): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, name, description, price, image_url, stock_quantity, category, created_at, updated_at 
             FROM products 
             WHERE name LIKE :query OR description LIKE :query 
             ORDER BY name ASC 
             LIMIT :limit'
        );
        
        $searchTerm = '%' . $query . '%';
        $stmt->bindValue(':query', $searchTerm, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Get products by category
     */
    public function getByCategory(string $category, int $limit = 100, int $offset = 0): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, name, description, price, image_url, stock_quantity, category, created_at, updated_at 
             FROM products 
             WHERE category = :category 
             ORDER BY created_at DESC 
             LIMIT :limit OFFSET :offset'
        );
        
        $stmt->bindValue(':category', $category, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Validate product data
     */
    private function validateProductData(array $data): void
    {
        if (empty($data['name'])) {
            throw new InvalidArgumentException('Product name is required');
        }
        
        if (strlen($data['name']) > 255) {
            throw new InvalidArgumentException('Product name must be less than 255 characters');
        }
        
        if (!isset($data['price']) || !is_numeric($data['price'])) {
            throw new InvalidArgumentException('Valid product price is required');
        }
        
        if ($data['price'] < 0) {
            throw new InvalidArgumentException('Product price cannot be negative');
        }
        
        if (isset($data['stock_quantity']) && $data['stock_quantity'] < 0) {
            throw new InvalidArgumentException('Stock quantity cannot be negative');
        }
    }
}
