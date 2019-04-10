# GovCMS8 Lagoon project

[![CircleCI](https://circleci.com/gh/govCMS/govcms8lagoon.svg?style=svg)](https://circleci.com/gh/govCMS/govcms8lagoon)

## Requirements

* [Docker](https://docs.docker.com/install/)
* [pygmy](https://docs.amazee.io/local_docker_development/pygmy.html#installation) (you might need sudo for this depending on your ruby configuration)
* [Ahoy](http://ahoy-cli.readthedocs.io/en/latest/#installation)

## Purpose

This project is used to create the images required by Lagoon, using the GovCMS8 distribution - it is only intended to be used by distribution/platform maintainers.

## Commands

Additional commands are listed in `.ahoy.yml`.

## Releasing a govcms8lagoon release to dockerhub

1. Prepare a release branch from master (release/govcms8lagoon-x.x.x - replace x with the correct version)
2. Update the .env.default GOVCMS_PROJECT_VERSION with the latest GovCMS release tag (defaults to 8.x-1.x in docker-compose)
3. Update the .env.default LAGOON_IMAGE_VERSION with the latest Lagoon release tag (defaults to :latest in docker-compose)
4. Update the .env.default LAGOON_IMAGE_VERSION_PHP with the latest Lagoon release tag (defaults to null - equivalent to :latest - in docker-compose)
5. Update the .env.default SITE_AUDIT_VERSION with the latest Site Audit release tag (defaults to 8.x-1.x in docker-compose)
6. Add a 1.x.0-rc1 tag to this branch and push to Github - this will update the :beta and :1.x.0-rc1 tags on dockerhub
7. Deploy a couple of test projects to OpenShift on the :beta tags (you may need to refresh the beta tags on the docker-host)
8. When ready to release, push the 1.x.0 tag to Github, and follow up with the `ahoy release` process
