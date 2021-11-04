group "default" {
    targets = ["cli", "test", "nginx", "php", "mariadb", "redis", "solr", "varnish"]
}

target "cli" {
    dockerfile = ".docker/Dockerfile.govcms"
    platforms = ["linux/amd64", "linux/arm64"]
    tags = ["docker.io/govcms/govcms:9.x-edge"]
}

target "test" {
    dockerfile = ".docker/Dockerfile.test"
    platforms = ["linux/amd64", "linux/arm64"]
    tags = ["docker.io/govcms/test:9.x-edge"]
}

target "nginx" {
    dockerfile = ".docker/Dockerfile.nginx-drupal"
    platforms = ["linux/amd64", "linux/arm64"]
    tags = ["docker.io/govcms/nginx-drupal:9.x-edge"]
}

target "php" {
    dockerfile = ".docker/Dockerfile.php"
    platforms = ["linux/amd64", "linux/arm64"]
    tags = ["docker.io/govcms/php:9.x-edge"]
}

target "mariadb" {
    dockerfile = ".docker/Dockerfile.mariadb-drupal"
    platforms = ["linux/amd64", "linux/arm64"]
    tags = ["docker.io/govcms/mariadb-drupal:9.x-edge"]
}

target "redis" {
    dockerfile = ".docker/Dockerfile.redis"
    platforms = ["linux/amd64", "linux/arm64"]
}

target "solr" {
    dockerfile = ".docker/Dockerfile.solr"
    platforms = ["linux/amd64", "linux/arm64"]
}

target "varnish" {
    dockerfile = ".docker/Dockerfile.varnish-drupal"
    platforms = ["linux/amd64", "linux/arm64"]
}
