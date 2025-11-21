# Use official PHP + Apache image
FROM php:8.2-apache

# Install extensions you might need
RUN docker-php-ext-install pdo pdo_pgsql pgsql mysqli

# Copy project files into container
COPY . /var/www/html/

# Enable Apache rewrite module (if using .htaccess)
RUN a2enmod rewrite

# Expose port (Render uses 10000 by default)
EXPOSE 10000

# Start Apache in the foreground
CMD ["apache2-foreground"]
