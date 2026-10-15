#!/bin/bash
set -e

# Disable all MPM modules to prevent conflicts on cloud platforms like Railway
a2dismod mpm_event 2>/dev/null || true
a2dismod mpm_worker 2>/dev/null || true
a2dismod mpm_prefork 2>/dev/null || true

# Enable only prefork MPM
a2enmod mpm_prefork

# Configure Apache port if PORT is provided by environment (e.g. Railway)
if [ -n "$PORT" ]; then
    sed -i "s/Listen 80/Listen $PORT/" /etc/apache2/ports.conf
    sed -i "s/<VirtualHost \*:80>/<VirtualHost \*:$PORT>/" /etc/apache2/sites-available/000-default.conf
fi

exec "$@"
