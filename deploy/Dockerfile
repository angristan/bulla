# Build stage for frontend assets
FROM node:20-alpine AS frontend

WORKDIR /app

# Copy package files
COPY package*.json ./
COPY embed/package*.json ./embed/

# Install dependencies
RUN npm ci
RUN cd embed && npm ci

# Copy source files
COPY . .

# Build assets
RUN npm run build
RUN cd embed && npm run build

# Production image with FrankenPHP
FROM dunglas/frankenphp:latest-php8.5-alpine

# Install PHP extensions
RUN install-php-extensions \
    pdo_sqlite \
    pdo_pgsql \
    opcache \
    intl \
    bcmath

# Set working directory
WORKDIR /app

# Copy composer files first for caching
COPY composer.json composer.lock ./

# Install PHP dependencies
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Copy application
COPY . .
COPY --from=frontend /app/public/build ./public/build
COPY --from=frontend /app/public/embed ./public/embed

# Generate optimized autoload
RUN composer dump-autoload --optimize

# Create storage directories
RUN mkdir -p storage/framework/{sessions,views,cache}
RUN mkdir -p storage/logs
RUN mkdir -p database

# Set permissions
RUN chown -R www-data:www-data storage bootstrap/cache database

# Copy FrankenPHP configuration
COPY deploy/Caddyfile /etc/caddy/Caddyfile

# Configure PHP
COPY deploy/php.ini /usr/local/etc/php/conf.d/99-app.ini

# Expose port
EXPOSE 8000

# Health check
HEALTHCHECK --interval=30s --timeout=5s --start-period=5s --retries=3 \
    CMD curl -f http://localhost:8000/api/config || exit 1

# Entry point
COPY deploy/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
