<?php

declare(strict_types=1);

use OpenApi\Attributes as OA;

/**
 * OpenAPI Documentation Configuration
 */
#[OA\Info(
    version: '1.0.0',
    title: 'Titan Products API',
    description: 'A RESTful API for managing products. Built with PHP and MySQL using a clean MVC architecture.

## Features
- Full CRUD operations for products
- Pagination support
- Search and filter by category
- Health check endpoint
- Comprehensive error handling

## Authentication
This API currently does not require authentication.',
    contact: new OA\Contact(
        name: 'API Support',
        email: 'support@example.com'
    ),
    license: new OA\License(
        name: 'MIT',
        url: 'https://opensource.org/licenses/MIT'
    )
)]
#[OA\Server(
    url: 'http://localhost:8000',
    description: 'Local development server'
)]
#[OA\Server(
    url: 'https://api.example.com',
    description: 'Production server'
)]
#[OA\Tag(
    name: 'Products',
    description: 'Product management operations'
)]
#[OA\Tag(
    name: 'Health',
    description: 'API health and status'
)]
class OpenApiSpec {}
