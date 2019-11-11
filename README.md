# GovCMS8 Lagoon project - Drupal 8

[![CircleCI](https://circleci.com/gh/govCMS/govcms8lagoon.svg?style=svg)](https://circleci.com/gh/govCMS/govcms8lagoon)

## Purpose

This project is used to create the images required by Lagoon, using the GovCMS distribution - it is only intended to
be used by distribution/platform maintainers.

Images are published to the [govcms8lagoon](https://hub.docker.com/u/govcms8lagoon) namespace on Docker Hub.

There is also the equivalent project for [GovCMS Drupal 7 images](https://github.com/govcms/govcmslagoon). Please
be mindful that there is some duplication across the two projects, so consider whether pull requests for changes
should be accompanied by PRs on the other repository.

## Instructions

_Expected tools_

* [Docker](https://docs.docker.com/install/)
* [pygmy](https://docs.amazee.io/local_docker_development/pygmy.html#installation)
* [Ahoy](http://ahoy-cli.readthedocs.io/en/latest/#installation)
* [Circle CI](https://circleci.com/docs/2.0/local-cli)

Clone this respository locally. You might copy `.env.default` to `.env` and modify, but running the CircleCI build will
overwrite it if you do (probably not ideal).

Running `ahoy build` will build the containers. There are no file mounts from the host, but if you ssh into
one of the containers (eg `ahoy cli`) you will see the familiar /app/web, etc.

Running `circleci build` will execute the build steps defined in `.circleci/config.yml` it will try to deploy to
Docker Hub - it's the final step so failure is an option if you are just testing the build.

## Releasing a govcms8lagoon release to dockerhub

1. Prepare a release branch from master (release/govcms8lagoon-x.x.x - replace x with the correct version)
2. Update the .env.default GOVCMS_PROJECT_VERSION with the latest GovCMS release tag (defaults to 8.x-1.x in docker-compose)
3. Update the .env.default LAGOON_IMAGE_VERSION with the latest Lagoon release tag (defaults to :latest in docker-compose)
4. Update the .env.default LAGOON_IMAGE_VERSION_PHP with the latest Lagoon release tag (defaults to null - equivalent to :latest - in docker-compose)
5. Update the .env.default SITE_AUDIT_VERSION with the latest Site Audit release tag (defaults to 8.x-1.x in docker-compose)
6. Add a 1.x.0-rc1 tag to this branch and push to Github - this will update the :beta and :1.x.0-rc1 tags on dockerhub
7. Deploy a couple of test projects to OpenShift on the :beta tags (you may need to refresh the beta tags on the docker-host)
8. When ready to release, push the 1.x.0 tag to Github, and follow up with the `ahoy release` process
