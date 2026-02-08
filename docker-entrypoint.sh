#!/bin/bash
# Render sets PORT env var at runtime (default 10000)
PORT=${PORT:-10000}

# Update Apache to listen on the correct port
sed -i "s/Listen 80/Listen ${PORT}/g" /etc/apache2/ports.conf
sed -i "s/:80/:${PORT}/g" /etc/apache2/sites-available/000-default.conf

# Start Apache
exec apache2-foreground
