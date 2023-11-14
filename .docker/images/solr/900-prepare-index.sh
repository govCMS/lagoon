#!/usr/bin/bash

# Prepare the index.
#
# If the release requires a configset update then an
# environment variable needs to be present, this will
# recreate the solr core otherwise the core will be
# precreated (or skipped if it exists).

INDEX="${INDEX:-drupal}"
CONFIGSET="${CONFIGSET:-/opt/solr/server/solr/configsets/drupal}"

# The following scripts are provided by the base images.
if [ -n "$GOVCMS_SOLR_RECREATE" ]; then
  # solr-recreate will remove the index data and configuration
  # and rebuild from the configset provided.
  solr-recreate "$INDEX_NAME" "$CONFIGSET"
else
  # solr-precreate will initialise the solr data/conf directories
  # if they exist on disk, this will skip.
  solr-precreate "$INDEX_NAME" "$CONFIGSET"
fi
