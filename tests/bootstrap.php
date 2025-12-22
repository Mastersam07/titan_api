<?php

declare(strict_types=1);

/**
 * PHPUnit Bootstrap File
 */

// Set error reporting
error_reporting(E_ALL);

// Load autoloader if using Composer
$autoloadFile = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoloadFile)) {
    require_once $autoloadFile;
}

// Load application files
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../controllers/ProductController.php';
require_once __DIR__ . '/../controllers/HealthController.php';
