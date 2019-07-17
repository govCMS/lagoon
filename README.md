# GovCMS8 Lagoon project - Drupal 8

[![CircleCI](https://circleci.com/gh/govCMS/govcms8lagoon.svg?style=svg)](https://circleci.com/gh/govCMS/govcms8lagoon)

## Purpose

This project is used to create the images required by Lagoon, using the GovCMS distribution - it is only intended to
be used by distribution/platform maintainers.

Images are published to the [govcms8lagoon](https://hub.docker.com/u/govcms8lagoon) namespace on Docker Hub.

There is also the equivalent project for [GovCMS Drupal 7 images](https://github.com/govcms/govcmslagoon)

## Instructions

_Requirements_

* [Docker](https://docs.docker.com/install/)
* [pygmy](https://docs.amazee.io/local_docker_development/pygmy.html#installation) (you might need sudo for this depending on your ruby configuration)
* [Ahoy](http://ahoy-cli.readthedocs.io/en/latest/#installation)

Clone this respository locally and then copy `.env.default` to `.env`. Note that .env.default may be updated for each
release so you should update your .env file as required.

Running `ahoy build` will build the containers. There are no file mounts from the host, but if you ssh into
one of the containers (eg `ahoy cli`) you will see the familiar /app/web, etc.

## Releasing a govcms8lagoon release to dockerhub

1. Prepare a release branch from master (release/govcms8lagoon-x.x.x - replace x with the correct version)
2. Update the .env.default GOVCMS_PROJECT_VERSION with the latest GovCMS release tag (defaults to 8.x-1.x in docker-compose)
3. Update the .env.default LAGOON_IMAGE_VERSION with the latest Lagoon release tag (defaults to :latest in docker-compose)
4. Update the .env.default LAGOON_IMAGE_VERSION_PHP with the latest Lagoon release tag (defaults to null - equivalent to :latest - in docker-compose)
5. Update the .env.default SITE_AUDIT_VERSION with the latest Site Audit release tag (defaults to 8.x-1.x in docker-compose)
6. Add a 1.x.0-rc1 tag to this branch and push to Github - this will update the :beta and :1.x.0-rc1 tags on dockerhub
7. Deploy a couple of test projects to OpenShift on the :beta tags (you may need to refresh the beta tags on the docker-host)
8. When ready to release, push the 1.x.0 tag to Github, and follow up with the `ahoy release` process
