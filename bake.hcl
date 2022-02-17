variable "DEPLOY_TAG" {
  default = "9.x-edge"
}

variable "DOCKERHUB_NAMESPACE" {
  default = "govcms"
}

group "default" {
    targets = ["cli", "test", "nginx", "php", "mariadb", "redis", "solr", "varnish"]
}

target "cli" {
    dockerfile = ".docker/Dockerfile.govcms"
    tags = ["${DOCKERHUB_NAMESPACE}/govcms:${DEPLOY_TAG}"]
    platforms = ["linux/amd64", "linux/arm64"]
}

target "test" {
    dockerfile = ".docker/Dockerfile.test"
    tags = ["${DOCKERHUB_NAMESPACE}/test:${DEPLOY_TAG}"]
    platforms = ["linux/amd64", "linux/arm64"]
}

target "nginx" {
    dockerfile = ".docker/Dockerfile.nginx-drupal"
    tags = ["${DOCKERHUB_NAMESPACE}/nginx-drupal:${DEPLOY_TAG}"]
    platforms = ["linux/amd64", "linux/arm64"]
}

target "php" {
    dockerfile = ".docker/Dockerfile.php"
    tags = ["${DOCKERHUB_NAMESPACE}/php:${DEPLOY_TAG}"]
    platforms = ["linux/amd64", "linux/arm64"]
}

target "mariadb" {
    dockerfile = ".docker/Dockerfile.mariadb-drupal"
    tags = ["${DOCKERHUB_NAMESPACE}/mariadb:${DEPLOY_TAG}"]
    platforms = ["linux/amd64", "linux/arm64"]
}

target "redis" {
    dockerfile = ".docker/Dockerfile.redis"
    tags = ["${DOCKERHUB_NAMESPACE}/redis:${DEPLOY_TAG}"]
    platforms = ["linux/amd64", "linux/arm64"]
}

target "solr" {
    dockerfile = ".docker/Dockerfile.solr"
    tags = ["${DOCKERHUB_NAMESPACE}/solr:${DEPLOY_TAG}"]
    platforms = ["linux/amd64", "linux/arm64"]
}

target "varnish" {
    dockerfile = ".docker/Dockerfile.varnish-drupal"
    tags = ["${DOCKERHUB_NAMESPACE}/varnish:${DEPLOY_TAG}"]
    platforms = ["linux/amd64", "linux/arm64"]
}
