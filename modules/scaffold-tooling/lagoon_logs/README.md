# Lagoon Logs

This module aims to be as close to a zero-configuration logging system for Drupal 8 sites running on the the Amazee.io Lagoon platform.

## Installation

Installation in Drupal 8 assumes a composer based workflow.

It's installed by running the following
```
composer require drupal/lagoon_logs
drush pm-enable -y lagoon_logs
```

## Use/configuration

Lagoon Logs is meant to be a Zero Configuration setup for Amazee.IO Lagoon projects.

Once the prerequisite modules and libraries have been installed, it will, by default send its logs to a Logstash instance at "application-logs.lagoon.svc:5140".

## License note

The file src/Logger/SocketHandler.php is pulled in from the [Monolog package](https://github.com/Seldaek/monolog) which uses the [MIT license](https://github.com/Seldaek/monolog/blob/master/LICENSE).
This is a temporary measure on our side to allow us to pull in the stable version of Monolog, while supporting large UDP packets (which the present stable version of Monolog doesn't).