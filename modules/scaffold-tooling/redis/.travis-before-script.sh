#!/bin/bash

set -e $DRUPAL_TI_DEBUG

# Ensure the right Drupal version is installed.
# Note: This function is re-entrant.
drupal_ti_ensure_drupal

# Add needed dependencies.
cd "$DRUPAL_TI_DRUPAL_DIR"

# Download predis
composer require predis/predis


# These variables come from environments/drupal-*.sh
mkdir -p "$DRUPAL_TI_MODULES_PATH"
cd "$DRUPAL_TI_MODULES_PATH"

#Enable php-redis
echo "extension = redis.so" >> ~/.phpenv/versions/$(phpenv version-name)/etc/php.ini