group "default" {
    targets = ["cli", "test", "nginx", "php", "mariadb", "redis", "solr", "varnish"]
}

target "cli" {
    dockerfile = ".docker/Dockerfile.govcms"
    platforms = ["linux/amd64", "linux/arm64"]
}

target "test" {
    dockerfile = ".docker/Dockerfile.test"
    platforms = ["linux/amd64", "linux/arm64"]
}

target "nginx" {
    dockerfile = ".docker/Dockerfile.nginx-drupal"
    platforms = ["linux/amd64", "linux/arm64"]
}

target "php" {
    dockerfile = ".docker/Dockerfile.php"
    platforms = ["linux/amd64", "linux/arm64"]
}

target "mariadb" {
    dockerfile = ".docker/Dockerfile.mariadb-drupal"
    platforms = ["linux/amd64", "linux/arm64"]
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
