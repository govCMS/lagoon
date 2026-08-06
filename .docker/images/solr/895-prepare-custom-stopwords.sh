#!/usr/bin/bash

set -eu

# Prepare the custom stopwords.
# As the stop filter is used in the query analyser,
# we do not need to recreate the Solr core and reindex data.

INDEX="${INDEX:-drupal}"
CONFIGSET="${CONFIGSET:-/opt/solr/server/solr/configsets/$INDEX}"
CUSTOM_FILE="/lagoon/solr/custom/custom_stopwords_en.txt"
DEFAULT_FILE="${CONFIGSET}/conf/stopwords_en.txt"
MERGED_FILE="/var/solr/data/${INDEX}/conf/stopwords_en.txt"

# Check if the custom stopwords file exists.
if [ ! -f "${CUSTOM_FILE}" ]; then
  echo "Custom stopwords file ${CUSTOM_FILE} does not exist."
fi

# Check if the default stopwords file exists.
if [ ! -f "${DEFAULT_FILE}" ]; then
  echo "Default stopwords file ${DEFAULT_FILE} does not exist."
fi

# Check if Solr active config exists.
if [ ! -f "${MERGED_FILE}" ]; then
  echo "Solr core active config does not exist. Skipped merging stopwords files."
  echo "Stopwords files will be merged after Solr core is created and Solr service is restarted."
fi

# Prepare the merged stopwords file.
if [ -f "${CUSTOM_FILE}" ] && [ -f "${DEFAULT_FILE}" ] && [ -f "${MERGED_FILE}" ]; then
  echo "Merging stopwords files."
  cat "${DEFAULT_FILE}" "${CUSTOM_FILE}" > "${MERGED_FILE}"
fi
