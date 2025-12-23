# Titan Products API
[![codecov](https://codecov.io/github/Mastersam07/titan_api/branch/dev/graph/badge.svg?token=5qjMM5ZwQC)](https://codecov.io/github/Mastersam07/titan_api)

A RESTful API for managing products, built with PHP and SQLite using a clean MVC architecture.

## 🏗️ Architecture

```
titan-api/
├── config/
│   └── database.php       # SQLite connection
├── controllers/
│   ├── ProductController.php
│   └── HealthController.php
├── models/
│   └── Product.php        # Data access layer
├── migrations/
│   └── migrate.php        # Database setup & seeding
├── database/
│   └── titan.db           # SQLite database (created on migrate)
├── tests/
├── index.php              # Entry point & router
└── Dockerfile             # For Render deployment
```

## 🚀 Quick Start

### Prerequisites

- PHP 8.1 or higher
- Composer

**No database server needed!** SQLite is built into PHP.

### Installation

```bash
cd titan-api

# Install dependencies
composer install

# Run migration (creates database/titan.db with sample data)
php migrations/migrate.php

# Start server
php -S localhost:8000
```

### Test It

```bash
# Health check
curl http://localhost:8000/health

# List products
curl http://localhost:8000/products

# Swagger docs
open http://localhost:8000/docs
```

## 📡 API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/` | API information |
| GET | `/health` | Health check |
| GET | `/docs` | Swagger UI documentation |
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
  -d '{"name": "Updated Name", "price": 39.99}'
```

### Search Products
```bash
curl "http://localhost:8000/products?search=wireless"
```

## 📚 API Documentation

Swagger UI is available at: **`/docs`**

The OpenAPI spec is auto-generated from PHP 8 attributes in the source code.

## 🧪 Testing

```bash
# Run all tests
composer test

# Run unit tests only
composer test:unit
```

## 🌐 Deploy to Render

**1. Push to GitHub**
```bash
git init
git add .
git commit -m "Initial commit"
gh repo create titan-api --public --push
```

**2. Deploy on Render**

1. Go to [render.com](https://render.com) → **New** → **Web Service**
2. Connect your GitHub repo
3. Settings:
   - **Runtime:** Docker
   - **Dockerfile Path:** `./Dockerfile`
4. Add Environment Variable:
   - `APP_DEBUG` = `false`
5. Click **Create Web Service**

**3. Done!**

Your API will be at: `https://your-app.onrender.com`

The database is embedded in the Docker image with sample data.

> **Note:** Free tier Render services spin down after inactivity. First request after idle may take ~30 seconds.

### Local Docker Testing

```bash
docker build -t titan-api .
docker run -p 8000:80 titan-api
# Visit http://localhost:8000
```

## 🔧 Configuration

### Environment Variables

| Variable | Default | Description |
|----------|---------|-------------|
| `DB_PATH` | `database/titan.db` | SQLite database path |
| `APP_DEBUG` | `false` | Show detailed errors |

## 📁 Project Files

- **index.php** - Router with CORS and error handling
- **database.php** - SQLite PDO singleton
- **Product.php** - Model with OpenAPI attributes
- **ProductController.php** - CRUD operations
- **migrate.php** - Creates tables and seeds data
