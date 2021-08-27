#!/usr/bin/env bash
##
# Build and push images to Dockerhub.
#

# Docker registry host - when set should contain /.
DOCKER_REGISTRY_HOST=${DOCKER_REGISTRY_HOST:-}
# Namespace for the image.
DOCKERHUB_NAMESPACE=${DOCKERHUB_NAMESPACE:-govcms8lagoon}
# Docker image version tag.
IMAGE_VERSION_TAG=${IMAGE_VERSION_TAG:-}
# Docker image tag prefix to be stripped from tag. Use " " (space) value to
# prevent stripping of the version.
IMAGE_VERSION_TAG_PREFIX=${IMAGE_VERSION_TAG_PREFIX:-8.x-}
# Docker image edge tag.
IMAGE_TAG_EDGE=${IMAGE_TAG_EDGE:-edge}
# Flag to force image build.
FORCE_IMAGE_BUILD=${FORCE_IMAGE_BUILD:-}
# Path prefix to Dockerfiles extension that is used as a name of the service.
FILE_EXTENSION_PREFIX=${FILE_EXTENSION_PREFIX:-.docker/Dockerfile.}
# CLI Image name
CLI_IMAGE=${DOCKERHUB_NAMESPACE:-govcms8lagoon}/${GOVCMS_CLI_IMAGE_NAME:-govcms8}

if [[ -n $CI_COMMIT_REF_SLUG ]]; then
  if [[ "$CI_COMMIT_REF_SLUG" =~ 1.x ]]; then
    IMAGE_TAG_EDGE="8.x-$IMAGE_TAG_EDGE";
  else
    IMAGE_TAG_EDGE="9.x-$IMAGE_TAG_EDGE";
  fi
fi

for file in $(echo $FILE_EXTENSION_PREFIX"*"); do
    service=${file/$FILE_EXTENSION_PREFIX/}

    # Support govcms8lagoon/govcms8 & govcms/govcms
    if [[ "$service" == "govcms" && "$GOVCMS_CLI_IMAGE_NAME" == "govcms8" ]]; then
      service=govcms8
    fi

    version_tag=$IMAGE_VERSION_TAG
    [ "$IMAGE_VERSION_TAG_PREFIX" != "" ] && version_tag=${IMAGE_VERSION_TAG/$IMAGE_VERSION_TAG_PREFIX/}

    existing_image=$(docker images -q $DOCKERHUB_NAMESPACE/$service)

    # Only rebuild images if they do not exist or rebuild is forced.
    if [ "$existing_image" == "" ] || [ "$FORCE_IMAGE_BUILD" != "" ]; then
      echo "$FORCE_IMAGE_BUILD==> Building \"$service\" image from file $file for service \"$DOCKERHUB_NAMESPACE/$service\""
      # Match the service's name to Docker file's name
      case $service in
        "govcms" | "govcms8")
          docker-compose build cli
          ;;
        "varnish-drupal")
          docker-compose build varnish
          ;;
        "mariadb-drupal")
          docker-compose build mariadb
          ;;
        "nginx-drupal")
          docker-compose build nginx
          ;;
        *)
          docker-compose build $service
      esac
    fi

    # Tag images with 'edge' tag and push.
  #  echo "==> Tagged and pushed \"$service\" image to $DOCKERHUB_NAMESPACE/$service:$IMAGE_TAG_EDGE"
  #  docker tag $DOCKERHUB_NAMESPACE/$service $DOCKER_REGISTRY_HOST$DOCKERHUB_NAMESPACE/$service:$IMAGE_TAG_EDGE
  #  docker push $DOCKER_REGISTRY_HOST$DOCKERHUB_NAMESPACE/$service:$IMAGE_TAG_EDGE

    # Tag images with version tag, if provided, and push.
    if [ "$version_tag" != "" ]; then
      echo "==> Tagging and pushing \"$service\" image to $DOCKERHUB_NAMESPACE/$service:$version_tag"
      if [[ "$service" == "chrome" ]]; then
        docker tag selenium/standalone-chrome $DOCKERHUB_NAMESPACE/chrome:$version_tag
      else
        docker tag $DOCKERHUB_NAMESPACE/$service $DOCKER_REGISTRY_HOST$DOCKERHUB_NAMESPACE/$service:$version_tag
      fi
      #docker push $DOCKER_REGISTRY_HOST$DOCKERHUB_NAMESPACE/$service:$version_tag
    fi
done
