#!/bin/bash
set -e

# Render sets PORT env var at runtime (default 10000)
PORT="${PORT:-10000}"

echo "Starting Apache on port ${PORT}..."

# Update Apache to listen on the correct port
sed -i "s/Listen 80/Listen ${PORT}/g" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/g" /etc/apache2/sites-available/000-default.conf

# Start Apache in foreground
exec apache2-foreground
