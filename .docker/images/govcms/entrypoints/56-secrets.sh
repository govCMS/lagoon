#!/bin/sh

# Copy the composer auth.json file to the user's home
# directory if present in secrets.
if [ -f /run/secrets/composer-github-auth ]; then
  cp /run/secrets/composer-github-auth /home/.composer/auth.json
fi
