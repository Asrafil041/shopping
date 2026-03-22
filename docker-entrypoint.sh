#!/bin/bash
set -e

echo "=== Checking Apache MPM configuration ==="
echo "Enabled MPM modules:"
grep -h "LoadModule mpm_" /etc/apache2/mods-enabled/*.load 2>/dev/null || echo "  No MPM modules found in mods-enabled"

echo ""
echo "Disallowed MPM modules (if any):"
ls -la /etc/apache2/mods-enabled/mpm_*.load 2>/dev/null | wc -l

echo ""
echo "Starting Apache..."
exec apache2-foreground
