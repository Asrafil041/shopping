#!/bin/bash
set -e

echo "=== Verifying Apache MPM configuration ==="

# RUNTIME SAFETY: Before starting Apache, remove any leftover mpm_ modules
# that aren't prefork. This catches any missed symlinks or re-enabled modules.
echo "Removing any non-prefork MPM modules..."
find /etc/apache2/mods-enabled -type l \( -name 'mpm_event*' -o -name 'mpm_worker*' -o -name 'mpm_itk*' -o -name 'mpm_async*' \) -exec rm -fv {} \; 2>/dev/null || true

# Count how many MPM .load files exist in mods-enabled after cleanup
MPM_COUNT=$(ls /etc/apache2/mods-enabled/mpm_*.load 2>/dev/null | wc -l)
MPM_LIST=$(ls -1 /etc/apache2/mods-enabled/mpm_*.load 2>/dev/null || echo "  (none)")

echo "Enabled MPM modules ($MPM_COUNT found):"
echo "$MPM_LIST"

if [ "$MPM_COUNT" -gt 1 ]; then
    echo ""
    echo "ERROR: More than one MPM module is enabled. Apache cannot start." >&2
    echo "Enabled MPMs: $MPM_LIST" >&2
    echo "All mpm_* files in mods-enabled:" >&2
    ls -la /etc/apache2/mods-enabled/mpm_* 2>&1 || echo "  (none)" >&2
    exit 1
fi

if [ "$MPM_COUNT" -eq 0 ]; then
    echo ""
    echo "ERROR: No MPM module is enabled. Apache cannot start." >&2
    exit 1
fi

echo ""
echo "✓ MPM check passed — exactly one MPM module is enabled."
echo ""
echo "Starting Apache..."
exec apache2-foreground
