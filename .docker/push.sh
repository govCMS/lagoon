#!/usr/bin/env bash
##
# Build and push images to Dockerhub.
#

# Namespace for the image.
DOCKERHUB_NAMESPACE=${DOCKERHUB_NAMESPACE:-govcms8}
# Docker image version tag.
IMAGE_VERSION_TAG=${IMAGE_VERSION_TAG:-}
# Docker image tag prefix to be stripped from tag. Use " " (space) value to
# prevent stripping of the version.
IMAGE_VERSION_TAG_PREFIX=${IMAGE_VERSION_TAG_PREFIX:-8.x-}
# Docker image edge tag.
IMAGE_TAG_EDGE=${IMAGE_TAG_EDGE:-}
# Flag to force image build.
FORCE_IMAGE_BUILD=${FORCE_IMAGE_BUILD:-}
# Path prefix to Dockerfiles extension that is used as a name of the service.
FILE_EXTENSION_PREFIX=${FILE_EXTENSION_PREFIX:-.docker/Dockerfile.}
# CLI Image name
CLI_IMAGE=${DOCKERHUB_NAMESPACE:-govcms8lagoon}/govcms8

for file in $(echo $FILE_EXTENSION_PREFIX"*"); do
    service=${file/$FILE_EXTENSION_PREFIX/}

    version_tag=$IMAGE_VERSION_TAG
    [ "$IMAGE_VERSION_TAG_PREFIX" != "" ] && version_tag=${IMAGE_VERSION_TAG/$IMAGE_VERSION_TAG_PREFIX/}

    existing_image=$(docker images -q $DOCKERHUB_NAMESPACE/$service)

    # Only rebuild images if they do not exist or rebuild is forced.
    if [ "$existing_image" == "" ] || [ "$FORCE_IMAGE_BUILD" != "" ]; then
      echo "==> Building \"$service\" image from file $file for service \"$DOCKERHUB_NAMESPACE/$service\""
      docker build -f $file -t $DOCKERHUB_NAMESPACE/$service --build-arg CLI_IMAGE=${CLI_IMAGE} .
    fi

    # Tag images with 'edge' tag and push.
    if [ "$IMAGE_TAG_EDGE" != "" ]; then
      echo "==> Tagged and pushed \"$service\" image to $DOCKERHUB_NAMESPACE/$service:$IMAGE_TAG_EDGE"
      docker tag $DOCKERHUB_NAMESPACE/$service $DOCKERHUB_NAMESPACE/$service:$IMAGE_TAG_EDGE
      docker push $DOCKERHUB_NAMESPACE/$service:$IMAGE_TAG_EDGE
    fi

    # Tag images with version tag, if provided, and push.
    if [ "$version_tag" != "" ]; then
      CI_IMAGE_NAME=${CI_REGISTRY}/${DOCKERHUB_NAMESPACE}/$service;
      echo "==> Tagging and pushing \"$service\" image to $CI_IMAGE_NAME:$version_tag"
      docker tag $DOCKERHUB_NAMESPACE/$service $CI_IMAGE_NAME:$version_tag
      docker push $CI_IMAGE_NAME:$version_tag
    fi
done
