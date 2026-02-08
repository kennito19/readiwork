FROM php:8.2-apache

# Enable Apache rewrite module
RUN a2enmod rewrite

# Install system dependencies and PHP extensions
RUN apt-get update \
    && apt-get install -y --no-install-recommends libcurl4-openssl-dev \
    && docker-php-ext-install pdo pdo_mysql \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Configure Apache to use PORT env var (Render sets this at runtime)
RUN sed -i 's/80/${PORT}/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

# Allow .htaccess overrides in document root
RUN printf '<Directory /var/www/html>\n    AllowOverride All\n    Require all granted\n</Directory>\n' \
    > /etc/apache2/conf-available/custom.conf \
    && a2enconf custom

# Copy project files
COPY . /var/www/html/

WORKDIR /var/www/html

# Use Render config (env-var based) as the active config.php
RUN cp config.render.php config.php

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Default port for Render
ENV PORT=10000
EXPOSE 10000

CMD ["apache2-foreground"]
