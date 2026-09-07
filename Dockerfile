FROM php:8.2-apache

# Install MySQL extension for PHP
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Copy your website files into the container
COPY . /var/www/html/

# Expose port 80 for web traffic
EXPOSE 80
