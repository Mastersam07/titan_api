<?php

declare(strict_types=1);

use OpenApi\Attributes as OA;

/**
 * Health and Info Controller with OpenAPI documentation
 */
class HealthController
{
    /**
     * GET / - API Information
     */
    #[OA\Get(
        path: '/',
        summary: 'API Information',
        description: 'Returns basic information about the API including version and available endpoints.',
        tags: ['Health'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'API information',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Titan Products API'),
                        new OA\Property(property: 'version', type: 'string', example: '1.0.0'),
                        new OA\Property(property: 'documentation', type: 'string', example: '/docs/'),
                        new OA\Property(property: 'endpoints', type: 'object'),
                    ]
                )
            ),
        ]
    )]
    public static function info(): void
    {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Titan Products API',
            'version' => '1.0.0',
            'documentation' => '/docs',
            'endpoints' => [
                'GET /products' => 'List all products (supports ?limit, ?offset, ?category, ?search)',
                'GET /products/{id}' => 'Get a single product',
                'POST /products' => 'Create a new product',
                'PUT /products/{id}' => 'Update a product',
                'DELETE /products/{id}' => 'Delete a product',
                'GET /health' => 'Health check endpoint',
                'GET /docs/' => 'API documentation (Swagger UI)',
            ],
        ], JSON_PRETTY_PRINT);
    }
    
    /**
     * GET /health - Health Check
     */
    #[OA\Get(
        path: '/health',
        summary: 'Health Check',
        description: 'Returns the health status of the API and its dependencies.',
        tags: ['Health'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Service is healthy',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'status', type: 'string', enum: ['healthy', 'unhealthy'], example: 'healthy'),
                        new OA\Property(property: 'timestamp', type: 'string', format: 'date-time', example: '2024-01-15T10:30:00+00:00'),
                        new OA\Property(
                            property: 'checks',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'database', type: 'string', example: 'connected'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 503,
                description: 'Service is unhealthy',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'status', type: 'string', example: 'unhealthy'),
                        new OA\Property(property: 'timestamp', type: 'string', format: 'date-time'),
                        new OA\Property(
                            property: 'checks',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'database', type: 'string', example: 'error: Connection refused'),
                            ]
                        ),
                    ]
                )
            ),
        ]
    )]
    public static function health(): void
    {
        $dbStatus = 'disconnected';
        
        try {
            $db = Database::getInstance();
            $db->query('SELECT 1');
            $dbStatus = 'connected';
        } catch (Exception $e) {
            $dbStatus = 'error: ' . $e->getMessage();
        }
        
        $healthy = $dbStatus === 'connected';
        
        http_response_code($healthy ? 200 : 503);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $healthy,
            'status' => $healthy ? 'healthy' : 'unhealthy',
            'timestamp' => date('c'),
            'checks' => [
                'database' => $dbStatus,
            ],
        ], JSON_PRETTY_PRINT);
    }
}
