FROM php:8.2-apache

# 1. Install system utilities and SSL certificates
RUN apt-get update && apt-get install -y \
    openssl \
    ca-certificates \
    && rm -rf /var/lib/apt/lists/*

# 2. Install MySQL extensions
RUN docker-php-ext-install pdo pdo_mysql mysqli

# 3. Copy files
COPY . /var/www/html/

EXPOSE 80
