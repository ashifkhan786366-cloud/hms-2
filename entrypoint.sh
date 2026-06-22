#!/bin/bash
set -e

# Use PORT environment variable if set by Render, otherwise default to 80
PORT_NUM=${PORT:-80}

echo "🚀 Configuring Apache to listen on port $PORT_NUM..."

# Replace default port 80 in Apache ports.conf
if [ -f /etc/apache2/ports.conf ]; then
    sed -i "s/Listen 80/Listen $PORT_NUM/g" /etc/apache2/ports.conf
fi

# Replace default port 80 in default virtualhost configuration
if [ -f /etc/apache2/sites-available/000-default.conf ]; then
    sed -i "s/<VirtualHost \*:80>/<VirtualHost \*:$PORT_NUM>/g" /etc/apache2/sites-available/000-default.conf
fi

# Run the original command
exec apache2-foreground
