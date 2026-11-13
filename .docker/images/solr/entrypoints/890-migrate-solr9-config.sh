#!/usr/bin/bash

# Recreate the core if the existing core uses Solr 8 configset.

INDEX="${INDEX:-drupal}"
CONFIGSET="${CONFIGSET:-/opt/solr/server/solr/configsets/drupal}"
OLD_CONFIGSET="/var/solr/data/${INDEX}"

# Check if the schema.xml exists in the old configset.
if [ -f "${OLD_CONFIGSET}/conf/schema.xml" ]; then
  if grep "solr-8.x" "${OLD_CONFIGSET}/conf/schema.xml"; then
    # Recreate the core. Indexed data is untouched.
    solr-recreate "${INDEX}" "${CONFIGSET}"
  else
    echo "Solr8 configset not detected."
  fi
fi
