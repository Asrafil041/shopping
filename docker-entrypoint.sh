#!/bin/bash
set -e

APP_PORT="${PORT:-80}"

echo "=== PRE-START: Aggressive MPM cleanup ==="

# FORCE DELETE all non-prefork MPM modules before Apache starts
echo "Force removing all non-prefork MPM modules..."
rm -fv /etc/apache2/mods-enabled/mpm_event.load /etc/apache2/mods-enabled/mpm_event.conf 2>/dev/null || true
rm -fv /etc/apache2/mods-enabled/mpm_worker.load /etc/apache2/mods-enabled/mpm_worker.conf 2>/dev/null || true
rm -fv /etc/apache2/mods-enabled/mpm_itk.load /etc/apache2/mods-enabled/mpm_itk.conf 2>/dev/null || true
rm -fv /etc/apache2/mods-enabled/mpm_async.load /etc/apache2/mods-enabled/mpm_async.conf 2>/dev/null || true

# Also remove all symlinks to non-prefork MPMs
find /etc/apache2/mods-enabled -type l \( -name 'mpm_event*' -o -name 'mpm_worker*' -o -name 'mpm_itk*' -o -name 'mpm_async*' \) -delete -print 2>/dev/null || true

# Ensure prefork module loads are present
if [ ! -f /etc/apache2/mods-enabled/mpm_prefork.load ]; then
    echo "WARNING: mpm_prefork.load missing, attempting to enable..."
    a2enmod mpm_prefork || true
fi

echo ""
echo "=== Verifying final Apache MPM configuration ==="

# Count how many MPM .load files exist in mods-enabled after cleanup
MPM_COUNT=$(ls /etc/apache2/mods-enabled/mpm_*.load 2>/dev/null | wc -l)
MPM_LIST=$(ls -1 /etc/apache2/mods-enabled/mpm_*.load 2>/dev/null || echo "  (none)")

echo "Enabled MPM modules ($MPM_COUNT found):"
echo "$MPM_LIST"

if [ "$MPM_COUNT" -gt 1 ]; then
    echo ""
    echo "ERROR: More than one MPM module is enabled. Apache cannot start." >&2
    echo "Enabled MPMs:" >&2
    ls -laR /etc/apache2/mods-enabled/mpm_* 2>&1 >&2
    exit 1
fi

if [ "$MPM_COUNT" -eq 0 ]; then
    echo ""
    echo "ERROR: No MPM module is enabled. Apache cannot start." >&2
    exit 1
fi

echo ""
echo "=== Runtime Apache network config ==="
echo "Setting Apache to listen on port: ${APP_PORT}"

sed -ri "s/^Listen\s+[0-9]+/Listen ${APP_PORT}/" /etc/apache2/ports.conf
sed -ri "s#<VirtualHost \*:[0-9]+>#<VirtualHost *:${APP_PORT}>#" /etc/apache2/sites-available/000-default.conf

if grep -q '^ServerName' /etc/apache2/apache2.conf; then
    sed -ri 's/^ServerName\s+.*/ServerName localhost/' /etc/apache2/apache2.conf
else
    echo 'ServerName localhost' >> /etc/apache2/apache2.conf
fi

echo "Active Listen directives:"
grep -n '^Listen' /etc/apache2/ports.conf || true

echo ""
echo "✓ MPM check PASSED — exactly one MPM module (prefork) is enabled."
echo ""
echo "Starting Apache..."
exec apache2-foreground
