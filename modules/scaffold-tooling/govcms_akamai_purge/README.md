# GovCMS Akamai Purge

The companion module for the [Akamai Purge Service](https://projects.govcms.gov.au/GovCMS/akamai-purge-service) for purging cache tags in Akamai for GovCMS websites.

## Requirements

The following environment variables are used by the module:

  | Variable                        | Required | Default              |
  | ------------------------------- | -------- | -------------------- |
  | `LAGOON_PROJECT`                | Yes      |                      |
  | `AKAMAI_PURGE_TOKEN`            | Yes      |                      |
  | `AKAMAI_PURGE_SERVICE_HOSTNAME` | No       | akamai-purge-service |
  | `AKAMAI_PURGE_SERVICE_PORT`     | No       | 8080                 |
  | `AKAMAI_PURGE_SERVICE_SCHEME`   | No       | http                 |

## What it does

When the module is enabled and the required variables are set, it:

  - Adds the `Edge-Cache-Tag` header to all requests that are cacheable.
  - Hashes the tags to make them shorter before adding them to the above header.
  - Adds a purger instance with values dynamically set from the environment.
  - Sends a purge request to the service using the Purge Late Runtime processor.
