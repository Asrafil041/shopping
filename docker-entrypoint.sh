#!/bin/bash
set -e

echo "=== Verifying Apache MPM configuration ==="

# Count how many MPM .load files exist in mods-enabled.
# Apache will refuse to start if more than one MPM is loaded, so we
# catch that here with a clear error rather than a cryptic Apache message.
MPM_COUNT=$(ls /etc/apache2/mods-enabled/mpm_*.load 2>/dev/null | wc -l)
MPM_LIST=$(ls /etc/apache2/mods-enabled/mpm_*.load 2>/dev/null || echo "  (none)")

echo "Enabled MPM modules ($MPM_COUNT found):"
echo "$MPM_LIST"

if [ "$MPM_COUNT" -gt 1 ]; then
    echo ""
    echo "ERROR: More than one MPM module is enabled. Apache cannot start." >&2
    echo "Enabled MPMs: $MPM_LIST" >&2
    exit 1
fi

if [ "$MPM_COUNT" -eq 0 ]; then
    echo ""
    echo "ERROR: No MPM module is enabled. Apache cannot start." >&2
    exit 1
fi

echo ""
echo "MPM check passed — exactly one MPM is enabled."
echo ""
echo "Starting Apache..."
exec apache2-foreground
