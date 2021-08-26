# GovCMS Lagoon project

## Purpose

This project is used to create the images required by Lagoon, using the GovCMS distribution - it is only intended to
be used by distribution/platform maintainers.

Images are published to the [govcms](https://hub.docker.com/u/govcms) namespace on Docker Hub.

Drupal 8 and 9 are supported through tags in Dockerhub and reference `1.x-master` and `2.x-master` respectively. When new images are released - the current state of the master branch will be tagged and pushed by the GovCMS team to docker to ensure updated images are available.

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

## Releasing a govcms/lagoon release to dockerhub

1. Prepare a release branch from master (release/lagoon-x.x.x - replace x with the correct version)
2. Update the .env.default GOVCMS_PROJECT_VERSION with the latest GovCMS release tag (defaults to 2.x-dev in docker-compose)
3. Update the .env.default LAGOON_IMAGE_VERSION with the latest Lagoon release tag (defaults to :latest in docker-compose)
4. Update the .env.default SITE_AUDIT_VERSION with the latest Site Audit release tag (defaults to 7.x-3.x in docker-compose)
5. Add a 1.x.0-rc1 tag to this branch and push to Github - this will update the :beta and :1.x.0-rc1 tags on dockerhub
6. Deploy a couple of test projects to OpenShift on the :beta tags (you may need to refresh the beta tags on the docker-host)
7. When ready to release, push the 1.x.0 tag to Github, and follow up with the `ahoy release` process
