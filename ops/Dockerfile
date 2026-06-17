FROM dunglas/frankenphp:1-php8.4-alpine

# Set working directory
WORKDIR /var/www/html

# Install system dependencies
RUN apk add --no-cache \
    curl \
    libpng-dev \
    libxml2-dev \
    zip \
    unzip \
    git \
    postgresql-dev \
    libzip-dev \
    bash \
    nodejs \
    npm

# Install PHP extensions
RUN install-php-extensions \
    pcntl \
    pdo_pgsql \
    pgsql \
    zip \
    bcmath \
    gd \
    opcache

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy application files (optional, overridden by volumes in dev)
COPY . .

RUN composer install

# Set Server Name to bind Caddy to HTTP on port 80
ENV SERVER_NAME=":80"

# Expose ports: 80 for FrankenPHP, 5173 for Vite
EXPOSE 80
EXPOSE 5173
