# Titan Products API

A RESTful API for managing products, built with PHP and MySQL using a clean MVC architecture.

## 🏗️ Architecture

This project follows a lightweight MVC pattern without framework dependencies:

```
titan-api/
├── config/
│   └── database.php       # Database singleton connection
├── controllers/
│   └── ProductController.php  # Request handling & responses
├── models/
│   └── Product.php        # Data access layer
├── migrations/
│   └── migrate.php        # Database setup & seeding
├── tests/
│   ├── Unit/              # Unit tests
│   └── Integration/       # Integration tests
├── docs/
│   └── swagger.json       # OpenAPI 3.0 documentation
├── index.php              # Entry point & router
├── .htaccess              # Apache URL rewriting
└── composer.json          # Dependencies & scripts
```

## 🚀 Quick Start

### Prerequisites

- PHP 8.1 or higher
- MySQL 5.7+ or MariaDB 10.3+
- Composer
- PDO PHP extension

### Installation

1. **Clone or extract the project:**
   ```bash
   cd titan-api
   ```

2. **Install dependencies:**
   ```bash
   composer install
   ```

3. **Configure environment:**
   ```bash
   cp .env.example .env
   ```
   
   Edit `.env` with your database credentials:
   ```env
   DB_HOST=localhost
   DB_NAME=titan_products
   DB_USER=root
   DB_PASS=your_password
   ```

4. **Run database migration:**
   ```bash
   composer migrate
   # or
   php migrations/migrate.php
   ```

5. **Start the development server:**
   ```bash
   composer serve
   # or
   php -S localhost:8000
   ```

6. **Verify installation:**
   ```bash
   curl http://localhost:8000/health
   ```

## 📡 API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/` | API information |
| GET | `/health` | Health check |
| GET | `/products` | List all products |
| GET | `/products/{id}` | Get single product |
| POST | `/products` | Create product |
| PUT | `/products/{id}` | Update product |
| DELETE | `/products/{id}` | Delete product |

### Query Parameters (GET /products)

| Parameter | Type | Description |
|-----------|------|-------------|
| `limit` | int | Max results (default: 100, max: 100) |
| `offset` | int | Skip results for pagination |
| `category` | string | Filter by category |
| `search` | string | Search name/description |

## 📖 Usage Examples

### List Products
```bash
curl http://localhost:8000/products
```

### Get Single Product
```bash
curl http://localhost:8000/products/1
```

### Create Product
```bash
curl -X POST http://localhost:8000/products \
  -H "Content-Type: application/json" \
  -d '{
    "name": "New Product",
    "description": "Product description",
    "price": 29.99,
    "stock_quantity": 100,
    "category": "Electronics"
  }'
```

### Update Product
```bash
curl -X PUT http://localhost:8000/products/1 \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Updated Name",
    "price": 39.99
  }'
```

### Delete Product
```bash
curl -X DELETE http://localhost:8000/products/1
```

### Search Products
```bash
curl "http://localhost:8000/products?search=wireless"
```

### Pagination
```bash
curl "http://localhost:8000/products?limit=10&offset=20"
```

### Filter by Category
```bash
curl "http://localhost:8000/products?category=Electronics"
```

## 📊 Response Format

### Success Response
```json
{
  "success": true,
  "data": { ... },
  "meta": {
    "total": 10,
    "limit": 100,
    "offset": 0
  }
}
```

### Error Response
```json
{
  "success": false,
  "error": {
    "code": 404,
    "message": "Product not found"
  }
}
```

## 🧪 Testing

### Run All Tests
```bash
composer test
```

### Run Unit Tests Only
```bash
composer test:unit
```

### Run Integration Tests Only
```bash
composer test:integration
```

### Generate Coverage Report
```bash
composer test:coverage
```
Coverage report will be generated in the `coverage/` directory.

### Static Analysis
```bash
composer analyse
```

## 📚 API Documentation

Swagger UI is served at: **`/docs`**

After starting the server, visit: `http://localhost:8000/docs`

The OpenAPI spec is **generated on-the-fly** from PHP 8 attributes in the source code - no static files to maintain. Just update the annotations in your code and refresh the docs page.

- Swagger UI: `http://localhost:8000/docs`
- Raw JSON spec: `http://localhost:8000/docs/swagger.json`

## 🔧 Configuration

### Environment Variables

| Variable | Default | Description |
|----------|---------|-------------|
| `DB_HOST` | localhost | Database host |
| `DB_NAME` | titan_products | Database name |
| `DB_USER` | root | Database username |
| `DB_PASS` | (empty) | Database password |
| `APP_DEBUG` | false | Show detailed errors |

### Apache Configuration

The included `.htaccess` handles URL rewriting. Ensure `mod_rewrite` is enabled:
```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

### Nginx Configuration

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}

location ~ \.php$ {
    fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
    fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
    include fastcgi_params;
}
```

## 🌐 Deployment Options

### Local Development with ngrok
```bash
# Start the server
php -S localhost:8000

# In another terminal, expose with ngrok
ngrok http 8000
```

### Shared Hosting
1. Upload files to `public_html` or `www`
2. Import database via phpMyAdmin
3. Update `.env` with production credentials

### Docker (optional)
```dockerfile
FROM php:8.2-apache
RUN docker-php-ext-install pdo pdo_mysql
RUN a2enmod rewrite
COPY . /var/www/html/
```

## 🛡️ Security Considerations

- Input validation on all endpoints
- Prepared statements prevent SQL injection
- CORS headers configured for API access
- Error details hidden in production (APP_DEBUG=false)

## 📁 Project Structure Explained

- **index.php**: Entry point with routing logic. Maps URLs to controller methods.
- **Database.php**: Singleton pattern for PDO connection management.
- **Product.php**: Model with CRUD operations and validation logic.
- **ProductController.php**: Handles HTTP requests and JSON responses.
- **migrate.php**: Creates tables and seeds sample data.

## 📄 License

MIT License - See LICENSE file for details.
