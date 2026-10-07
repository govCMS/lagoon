#!/usr/bin/bash

# Prepare the custom synonyms.
# As the synonym filter is used in the query analyser,
# we do not need to recreate the Solr core and reindex data.
#
# The custom synonyms file should be placed at the path
# custom/solr/custom_synonyms_en.txt and be copied to Docker
# image using the following command:
#
# COPY custom/solr/custom_synonyms_en.txt /lagoon/solr/custom/custom_synonyms_en.txt
#
# The final custom synonyms file will not be generated if the
# custom file is missing. Solr will use the default file from
# the configset instead.

INDEX="${INDEX:-drupal}"
CONFIGSET="${CONFIGSET:-/opt/solr/server/solr/configsets/$INDEX}"
CUSTOM_FILE="/lagoon/solr/custom/custom_synonyms_en.txt"
DEFAULT_FILE="${CONFIGSET}/conf/synonyms_en.txt"
MERGED_FILE="/var/solr/data/${INDEX}/conf/synonyms_en.txt"

# Check if the custom synonyms file exists.
if [ ! -f "${CUSTOM_FILE}" ]; then
  echo "Custom synonyms file ${CUSTOM_FILE} does not exist."
fi

# Check if the default synonyms file exists.
if [ ! -f "${DEFAULT_FILE}" ]; then
  echo "Default synonyms file ${DEFAULT_FILE} does not exist."
fi

# Check if Solr active config exists.
if [ ! -f "${MERGED_FILE}" ]; then
  echo "Synonyms files will be merged after Solr core is created and Solr service is restarted."
fi

# Prepare the merged synonyms file.
if [ -f "${CUSTOM_FILE}" ] && [ -f "${DEFAULT_FILE}" ] && [ -f "${MERGED_FILE}" ]; then
  echo "Merging synonyms files${DEFAULT_FILE} ${CUSTOM_FILE} > ${MERGED_FILE}."
  cat "${DEFAULT_FILE}" "${CUSTOM_FILE}" > "${MERGED_FILE}"
fi
