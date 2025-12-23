<?php

declare(strict_types=1);

/**
 * Titan Products API
 * 
 * A simple RESTful API for managing products.
 * 
 * @version 1.0.0
 */

$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($key, $value) = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
    }
}

set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

set_exception_handler(function ($exception) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => [
            'code' => 500,
            'message' => 'Internal Server Error',
            'details' => $_ENV['APP_DEBUG'] ?? false ? $exception->getMessage() : null,
        ],
    ]);
    exit;
});

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Max-Age: 86400');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$basePath = '/';
if (strpos($uri, '/api') === 0) {
    $basePath = '/api';
}
$uri = substr($uri, strlen($basePath)) ?: '/';

if ($uri !== '/' && substr($uri, -1) === '/') {
    $uri = rtrim($uri, '/');
}

if ($uri === '/docs' || $uri === '/docs/swagger.json') {
    require_once __DIR__ . '/vendor/autoload.php';
    require_once __DIR__ . '/config/openapi.php';
    require_once __DIR__ . '/config/database.php';
    require_once __DIR__ . '/models/Product.php';
    require_once __DIR__ . '/controllers/ProductController.php';
    require_once __DIR__ . '/controllers/HealthController.php';

    $openapi = \OpenApi\Generator::scan([
        __DIR__ . '/config',
        __DIR__ . '/models',
        __DIR__ . '/controllers',
    ]);

    if ($uri === '/docs/swagger.json') {
        header('Content-Type: application/json');
        header('Cache-Control: no-cache');
        echo $openapi->toJson();
        exit;
    }

    $spec = $openapi->toJson();
    header('Content-Type: text/html; charset=utf-8');
    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Titan Products API - Documentation</title>
    <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5/swagger-ui.css">
    <style>
        html { box-sizing: border-box; overflow-y: scroll; }
        *, *:before, *:after { box-sizing: inherit; }
        body { margin: 0; background: #fafafa; }
        .swagger-ui .topbar { display: none; }
        .header {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            color: white;
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        .header h1 { margin: 0; font-size: 1.5rem; }
        .header .version {
            background: rgba(255,255,255,0.2);
            padding: 5px 12px;
            border-radius: 4px;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🚀 Titan Products API</h1>
        <span class="version">v1.0.0</span>
    </div>
    <div id="swagger-ui"></div>
    <script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
    <script>
        window.onload = function() {
            SwaggerUIBundle({
                spec: {$spec},
                dom_id: '#swagger-ui',
                deepLinking: true,
                presets: [SwaggerUIBundle.presets.apis, SwaggerUIBundle.SwaggerUIStandalonePreset],
                layout: "BaseLayout",
                tryItOutEnabled: true,
                displayRequestDuration: true
            });
        };
    </script>
</body>
</html>
HTML;
    exit;
}

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/controllers/HealthController.php';
require_once __DIR__ . '/controllers/ProductController.php';

$routes = [
    'GET' => [
        '/' => [HealthController::class, 'info'],
        '/health' => [HealthController::class, 'health'],
        '/products' => [ProductController::class, 'index'],
        '/products/(\d+)' => [ProductController::class, 'show'],
    ],
    'POST' => [
        '/products' => [ProductController::class, 'store'],
    ],
    'PUT' => [
        '/products/(\d+)' => [ProductController::class, 'update'],
    ],
    'PATCH' => [
        '/products/(\d+)' => [ProductController::class, 'update'],
    ],
    'DELETE' => [
        '/products/(\d+)' => [ProductController::class, 'destroy'],
    ],
];

$handler = null;
$params = [];

if (isset($routes[$method])) {
    foreach ($routes[$method] as $pattern => $routeHandler) {
        $regex = '#^' . $pattern . '$#';
        if (preg_match($regex, $uri, $matches)) {
            $handler = $routeHandler;
            array_shift($matches);
            $params = $matches;
            break;
        }
    }
}

if (!$handler) {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => [
            'code' => 404,
            'message' => 'Endpoint not found',
            'available_endpoints' => [
                'GET /' => 'API information',
                'GET /health' => 'Health check',
                'GET /docs/' => 'API documentation (Swagger UI)',
                'GET /products' => 'List all products',
                'GET /products/{id}' => 'Get a single product',
                'POST /products' => 'Create a new product',
                'PUT /products/{id}' => 'Update a product',
                'DELETE /products/{id}' => 'Delete a product',
            ],
        ],
    ]);
    exit;
}

[$class, $method] = $handler;

if ($class === HealthController::class) {
    call_user_func_array([$class, $method], $params);
} else {
    $controller = new $class();
    call_user_func_array([$controller, $method], array_map('intval', $params));
}
