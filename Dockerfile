FROM php:8.2-apache

# Enable Apache rewrite module
RUN a2enmod rewrite

# Install system dependencies and PHP extensions
RUN apt-get update \
    && apt-get install -y --no-install-recommends libcurl4-openssl-dev \
    && docker-php-ext-install pdo pdo_mysql \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

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

# Render provides PORT env var at runtime; Apache must listen on it.
# Use sed at container start via the entrypoint script.
RUN chmod +x /var/www/html/docker-entrypoint.sh

EXPOSE 10000

ENTRYPOINT ["/var/www/html/docker-entrypoint.sh"]
