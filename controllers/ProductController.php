<?php

declare(strict_types=1);

use OpenApi\Attributes as OA;

require_once __DIR__ . '/../models/Product.php';

/**
 * Product Controller with OpenAPI documentation
 */
class ProductController
{
    private Product $product;
    
    public function __construct(?Product $product = null)
    {
        $this->product = $product ?? new Product();
    }
    
    /**
     * GET /products - List all products
     */
    #[OA\Get(
        path: '/products',
        summary: 'List Products',
        description: 'Retrieves a paginated list of products. Supports filtering by category and search.',
        tags: ['Products'],
        parameters: [
            new OA\Parameter(
                name: 'limit',
                in: 'query',
                description: 'Maximum number of products to return (max 100)',
                schema: new OA\Schema(type: 'integer', default: 100, maximum: 100)
            ),
            new OA\Parameter(
                name: 'offset',
                in: 'query',
                description: 'Number of products to skip for pagination',
                schema: new OA\Schema(type: 'integer', default: 0)
            ),
            new OA\Parameter(
                name: 'category',
                in: 'query',
                description: 'Filter products by category',
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'search',
                in: 'query',
                description: 'Search products by name or description',
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of products',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/Product')
                        ),
                        new OA\Property(ref: '#/components/schemas/PaginationMeta', property: 'meta'),
                    ]
                )
            ),
            new OA\Response(response: 500, description: 'Server error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function index(): void
    {
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 100;
        $offset = isset($_GET['offset']) ? (int) $_GET['offset'] : 0;
        $category = $_GET['category'] ?? null;
        $search = $_GET['search'] ?? null;
        
        // Cap limit at 100
        $limit = min($limit, 100);
        
        try {
            if ($search) {
                $products = $this->product->search($search, $limit);
                $total = count($products);
            } elseif ($category) {
                $products = $this->product->getByCategory($category, $limit, $offset);
                $total = count($products);
            } else {
                $products = $this->product->getAll($limit, $offset);
                $total = $this->product->count();
            }
            
            $this->jsonResponse([
                'success' => true,
                'data' => $products,
                'meta' => [
                    'total' => $total,
                    'limit' => $limit,
                    'offset' => $offset,
                ],
            ]);
        } catch (Exception $e) {
            $this->errorResponse('Failed to fetch products: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * GET /products/{id} - Get a single product
     */
    #[OA\Get(
        path: '/products/{id}',
        summary: 'Get Product',
        description: 'Retrieves a single product by its ID.',
        tags: ['Products'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Product ID',
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Product details',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(ref: '#/components/schemas/Product', property: 'data'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Product not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function show(int $id): void
    {
        try {
            $product = $this->product->getById($id);
            
            if (!$product) {
                $this->errorResponse('Product not found', 404);
                return;
            }
            
            $this->jsonResponse([
                'success' => true,
                'data' => $product,
            ]);
        } catch (Exception $e) {
            $this->errorResponse('Failed to fetch product: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * POST /products - Create a new product
     */
    #[OA\Post(
        path: '/products',
        summary: 'Create Product',
        description: 'Creates a new product with the provided data.',
        tags: ['Products'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/ProductInput')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Product created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Product created successfully'),
                        new OA\Property(ref: '#/components/schemas/Product', property: 'data'),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Invalid JSON data', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function store(): void
    {
        $data = $this->getJsonInput();
        
        if (!$data) {
            $this->errorResponse('Invalid JSON data', 400);
            return;
        }
        
        try {
            $product = $this->product->create($data);
            
            $this->jsonResponse([
                'success' => true,
                'message' => 'Product created successfully',
                'data' => $product,
            ], 201);
        } catch (InvalidArgumentException $e) {
            $this->errorResponse($e->getMessage(), 422);
        } catch (Exception $e) {
            $this->errorResponse('Failed to create product: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * PUT /products/{id} - Update a product
     */
    #[OA\Put(
        path: '/products/{id}',
        summary: 'Update Product',
        description: 'Updates an existing product. All fields are optional - only provided fields will be updated.',
        tags: ['Products'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Product ID',
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/ProductUpdateInput')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Product updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Product updated successfully'),
                        new OA\Property(ref: '#/components/schemas/Product', property: 'data'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Product not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function update(int $id): void
    {
        $data = $this->getJsonInput();
        
        if (!$data) {
            $this->errorResponse('Invalid JSON data', 400);
            return;
        }
        
        try {
            $product = $this->product->update($id, $data);
            
            if (!$product) {
                $this->errorResponse('Product not found', 404);
                return;
            }
            
            $this->jsonResponse([
                'success' => true,
                'message' => 'Product updated successfully',
                'data' => $product,
            ]);
        } catch (InvalidArgumentException $e) {
            $this->errorResponse($e->getMessage(), 422);
        } catch (Exception $e) {
            $this->errorResponse('Failed to update product: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * DELETE /products/{id} - Delete a product
     */
    #[OA\Delete(
        path: '/products/{id}',
        summary: 'Delete Product',
        description: 'Deletes a product by its ID.',
        tags: ['Products'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Product ID',
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Product deleted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Product deleted successfully'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Product not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function destroy(int $id): void
    {
        try {
            $deleted = $this->product->delete($id);
            
            if (!$deleted) {
                $this->errorResponse('Product not found', 404);
                return;
            }
            
            $this->jsonResponse([
                'success' => true,
                'message' => 'Product deleted successfully',
            ]);
        } catch (Exception $e) {
            $this->errorResponse('Failed to delete product: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Parse JSON input from request body
     */
    private function getJsonInput(): ?array
    {
        $input = file_get_contents('php://input');
        
        if (empty($input)) {
            return null;
        }
        
        $data = json_decode($input, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }
        
        return $data;
    }
    
    /**
     * Send a JSON response
     */
    private function jsonResponse(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
    
    /**
     * Send an error response
     */
    private function errorResponse(string $message, int $statusCode = 400): void
    {
        $this->jsonResponse([
            'success' => false,
            'error' => [
                'code' => $statusCode,
                'message' => $message,
            ],
        ], $statusCode);
    }
}
