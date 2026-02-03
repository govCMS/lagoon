#!/usr/bin/bash

set -eu

# Prepare the index.
#
# If the release requires a configset update then an
# environment variable needs to be present, this will
# recreate the solr core otherwise the core will be
# precreated (or skipped if it exists).

INDEX="${INDEX:-drupal}"
CONFIGSET="${CONFIGSET:-/opt/solr/server/solr/configsets/drupal}"
GOVCMS_SOLR_RECREATE="${GOVCMS_SOLR_RECREATE:-}"

# The following scripts are provided by the base images.
if [ -n "$GOVCMS_SOLR_RECREATE" ]; then
  # solr-recreate will remove the core and configuration
  # and rebuild from the configset provided.
  solr-recreate "$INDEX" "$CONFIGSET"
else
  # solr-precreate will initialise the solr data/conf directories
  # if they exist on disk, this will skip.
  mkdir -p /var/solr/data
  solr-precreate "$INDEX" "$CONFIGSET"
fi
