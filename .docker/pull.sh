#!/usr/bin/env bash

##
# Pull images from the registry.
#

# Docker registry host - when set should contain /.
DOCKER_REGISTRY_HOST=${DOCKER_REGISTRY_HOST:-}
# Namespace for the image.
DOCKERHUB_NAMESPACE=${DOCKERHUB_NAMESPACE:-govcms8}
# Docker image version tag.
IMAGE_VERSION_TAG=${IMAGE_VERSION_TAG:-}
# Docker image tag prefix to be stripped from tag. Use " " (space) value to
# prevent stripping of the version.
IMAGE_VERSION_TAG_PREFIX=${IMAGE_VERSION_TAG_PREFIX:-8.x-}
# Docker image edge tag.
IMAGE_TAG_EDGE=${IMAGE_TAG_EDGE:-beta}
# Flag to force image build.
FORCE_IMAGE_BUILD=${FORCE_IMAGE_BUILD:-}
# Path prefix to Dockerfiles extension that is used as a name of the service.
FILE_EXTENSION_PREFIX=${FILE_EXTENSION_PREFIX:-.docker/Dockerfile.}

for file in $(echo $FILE_EXTENSION_PREFIX"*"); do
  service=${file/$FILE_EXTENSION_PREFIX/}
  # Pull the images from the container.
  docker pull $DOCKER_REGISTRY_HOST$DOCKERHUB_NAMESPACE/$service:$IMAGE_TAG_EDGE
done
