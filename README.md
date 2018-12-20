# GovCMS8 Lagoon project

[![CircleCI](https://circleci.com/gh/govCMS/govcms8lagoon.svg?style=svg)](https://circleci.com/gh/govCMS/govcms8lagoon)

## Requirements

* [Docker](https://docs.docker.com/install/)
* [pygmy](https://docs.amazee.io/local_docker_development/pygmy.html#installation) (you might need sudo for this depending on your ruby configuration)
* [Ahoy](http://ahoy-cli.readthedocs.io/en/latest/#installation)


## Setup

1. Checkout project repo and confirm the path is in Docker's file sharing config (https://docs.docker.com/docker-for-mac/#file-sharing):

        git clone https://projects.govcms.gov.au/department/agency.git govcms-agency && cd $_

2. Make sure you don't have anything running on port 80 on the host machine (like a web server):

        pygmy up

3. Build and start the containers:

        ahoy up

4. Install GovCMS:

        ahoy install

5. Login to Drupal:

        ahoy login

## Commands

Additional commands are listed in `.ahoy.yml`.

## Releasing production version
Once this repo is ready for production release, the following actions needs to be taken:
- Replace `govcms8dev` with `govcms8` in all files
- Remove `DEPLOY_ANY_BRANCH` environment variable from CircleCI UI
- Remove this block
