## Configuration management

GovCMS8 has default configuration management built in. It assumes all configuration is tracked (in `config/default`).

1. Export all configuration for a build:

        Mac/Linux:  ahoy cex
        Windows:    docker-compose exec -T test drush cex sync

2. Import any configuration changes from the codebase:

        Mac/Linux:  ahoy cim
        Windows:    docker-compose exec -T test drush cim sync

3. Import development environment configuration overrides:

        Mac/Linux:  ahoy cim dev
        Windows:    docker-compose exec -T test drush cim dev --partial


*Note*: Configuration overrides are snippets of configuration that may be imported over the base configuration. These (optional) files should exist in `config/dev`.
For example a development project may include a file such as `config/dev/shield.settings.yml` which provides Shield authentication configuration that would only apply to a development environment, not production.
