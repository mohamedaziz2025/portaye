#!/bin/sh
set -e

# Ensure token is provided
if [ -z "$CALENDLY_TOKEN" ]; then
  echo "⚠️  Warning: CALENDLY_TOKEN is not set. Booking feature will be disabled."
  printf 'window.CALENDLY_TOKEN = null;\nconsole.warn("Calendly token not configured");\n' > /usr/share/nginx/html/calendly-config.js
else
  # Escape special characters in token for JavaScript
  ESCAPED_TOKEN=$(printf '%s\n' "$CALENDLY_TOKEN" | sed -e 's/\\/\\\\/g' -e 's/"/\\"/g')
  printf 'window.CALENDLY_TOKEN = "%s";\nconsole.log("✓ Calendly token loaded");\n' "$ESCAPED_TOKEN" > /usr/share/nginx/html/calendly-config.js
  echo "✓ Calendly token injected successfully"
fi

exec nginx -g 'daemon off;'
