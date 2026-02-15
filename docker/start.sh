#!/usr/bin/env bash
set -e

PORT="${PORT:-80}"

sed -i "s/Listen 80/Listen ${PORT}/g" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost \*:${PORT}>/g" /etc/apache2/sites-available/000-default.conf

echo "Apache listening on port: ${PORT}"
apache2-foreground
