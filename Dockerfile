FROM php:8.2-apache

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo pdo_sqlite zip \
    && a2enmod rewrite \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Create database directory with proper permissions
RUN mkdir -p /var/www/html/database \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/database

# Run migration to create and seed database
RUN php migrations/migrate.php

# Configure Apache
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Expose port 80
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]