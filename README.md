# GovCMS Lagoon project

## Purpose

This project is used to create the images required by Lagoon, using the GovCMS distribution - it is only intended to 
be used by distribution/platform maintainers.

Images are published to the [govcms](https://hub.docker.com/u/govcms) namespace on Docker Hub.

Drupal 10 is supported through tags in Dockerhub and reference `3.x-master`. When new images are released - the current state of the master branch will be tagged and pushed by the GovCMS team to docker to ensure updated images are available.

There is also the equivalent project for [GovCMS Drupal 7 images](https://github.com/govcms/govcmslagoon). Please
be mindful that there is some duplication across the two projects, so consider whether pull requests for changes
should be accompanied by PRs on the other repository.

## Instructions

_Expected tools_

* [Docker](https://docs.docker.com/install/)
* [Ahoy](http://ahoy-cli.readthedocs.io/en/latest/#installation)
* [pygmy](https://docs.amazee.io/local_docker_development/pygmy.html#installation)

Clone this respository locally. You might copy `.env.default` to `.env` and modify.

Running `ahoy build` will build the containers. There are no file mounts from the host, but if you ssh into
one of the containers (eg `ahoy cli`) you will see the familiar /app/web, etc.

### Composer credentials

To avoid composer rate limiting you will need to providea personal access token that has read-only scope access to Github. Follow the [instructions from Github to create](https://docs.github.com/en/authentication/keeping-your-account-and-data-secure/managing-your-personal-access-tokens) a personal access token.

1. Create composer auth.json `composer config github-oauth.gitub.com <token>`
2. Enable docker swam `docker swarm init`
3. Create a docker secret `docker secret create composer-auth auth.json`

This will create a secret that is shared with the image during the build process


## Releasing a govcms/lagoon release to dockerhub

1. Prepare a release branch from master (release/lagoon-x.x.x - replace x with the correct version)
2. Update the .env.default GOVCMS_PROJECT_VERSION with the latest GovCMS release tag
3. Update the .env.default LAGOON_IMAGE_VERSION with the latest Lagoon release tag
4. Update the .env.default SITE_AUDIT_VERSION with the latest Site Audit release tag
