# Use official PHP + Apache image
FROM php:8.2-apache

# Set working directory
WORKDIR /var/www/html

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql mysqli \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache modules
RUN a2enmod rewrite headers

# Copy project files into container
COPY . /var/www/html/

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Configure Apache for dynamic port binding
RUN echo 'ServerName localhost' >> /etc/apache2/apache2.conf

# Create startup script for dynamic port
RUN echo '#!/bin/bash\n\
PORT=${PORT:-10000}\n\
sed -i "s/Listen 80/Listen $PORT/g" /etc/apache2/ports.conf\n\
sed -i "s/:80/:$PORT/g" /etc/apache2/sites-available/000-default.conf\n\
apache2-foreground' > /start.sh \
    && chmod +x /start.sh

# Expose port (will be dynamic on Render)
EXPOSE ${PORT:-10000}

# Start Apache with dynamic port configuration
CMD ["/start.sh"]
