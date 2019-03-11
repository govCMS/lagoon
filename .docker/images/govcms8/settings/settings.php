<?php

/**
 * @file
 * Lagoon Drupal 8 configuration file.
 *
 * You should not edit this file, please use environment specific files!
 * They are loaded in this order:
 * - all.settings.php
 *   For settings that should be applied to all environments (dev, prod, staging, docker, etc).
 * - all.services.yml
 *   For services that should be applied to all environments (dev, prod, staging, docker, etc).
 * - production.settings.php
 *   For settings only for the production environment.
 * - production.services.yml
 *   For services only for the production environment.
 * - development.settings.php
 *   For settings only for the development environment (devevlopment sites, docker).
 * - development.services.yml
 *   For services only for the development environment (devevlopment sites, docker).
 * - settings.local.php
 *   For settings only for the local environment, this file will not be commited in GIT!
 * - services.local.yml
 *   For services only for the local environment, this file will not be commited in GIT!
 *
 */

// Contrib path.
$contrib_path = 'modules/contrib';

// @see https://govdex.gov.au/jira/browse/GOVCMS-993
// @see https://github.com/drupal/drupal/blob/7.x/sites/default/default.settings.php#L518
// @see https://api.drupal.org/api/drupal/includes%21bootstrap.inc/function/drupal_fast_404/8.x
if (file_exists($contrib_path . '/fast404/fast404.inc')) {
  include_once $contrib_path . 'fast404/fast404.inc';
}
$settings['fast404_exts'] = '/^(?!robots)^(?!sites\/default\/files\/private).*\.(?:png|gif|jpe?g|svg|tiff|bmp|raw|webp|docx?|xlsx?|pptx?|swf|flv|cgi|dll|exe|nsf|cfm|ttf|bat|pl|asp|ics|rtf)$/i';
$settings['fast404_html'] = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML+RDFa 1.0//EN" "http://www.w3.org/MarkUp/DTD/xhtml-rdfa-1.dtd"><html xmlns="http://www.w3.org/1999/xhtml"><head><title>404 Not Found</title></head><body><h1>Not Found</h1><p>The requested URL "@path" was not found on this server.</p></body></html>';
$settings['fast404_whitelist'] = array('robots.txt', 'system/files');

// Allow custom themes to provide custom 404 pages.
// By placing a file called 404.html in the root of their theme repository.
// 404 pages must be less than 512KB to be used. This is a performance
// measure to ensure transfer, memory usage and disk reads are manageable.
if (!class_exists('govCms404Page')) {
  class govCms404Page {

    const MAX_FILESIZE = 5132288;

    protected $filepath;

    protected $default;

    public function __construct($fast_404_html) {
      $this->filepath = '/app/404.html';
      $this->default = $fast_404_html;
    }

    public function __toString() {
      // filesize() will check the file exists. So as long as
      // we suppress the output, it won't be an issue to not
      // check for the presence of a file first.
      $filesize = @filesize($this->filepath);
      if ($filesize === FALSE || $filesize > self::MAX_FILESIZE) {
        return $this->default;
      }

      return file_get_contents($this->filepath);
    }
  }
}

$settings['fast404_html'] = new govCms404Page($settings['fast404_html']);

// Ensure redirects created with the redirect module are able to set appropriate
// caching headers to ensure that Varnish and Akamai can cache the HTTP 301.
$settings['page_cache_invoke_hooks'] = TRUE;
$settings['redirect_page_cache'] = TRUE;

// Ensure that administrators do not block drush access through the UI.
$config['shield.settings']['allow_cli'] = TRUE;

// Configure seckit to emit the HSTS headers when a user is likely visiting
// govCMS using a domain with valid SSL.
//
// This includes:
//  - "*-site.test.govcms.gov.au" domains (TEST)
//  - "*-site.govcms.gov.au" domains (PROD)
//  - "*.gov.au" domains (PROD)
//  - "*.org.au" domains (PROD)
//
// When the domain likely does not have valid SSL, then HSTS is disabled
// explicitly (to prevent the database values being used).
//
// @see https://govdex.gov.au/jira/browse/GOVCMS-1109
// @see http://cgit.drupalcode.org/seckit/tree/includes/seckit.form.inc#n397
//
if (preg_match("~^.+(\.gov\.au|\.org\.au)$~i", $_SERVER['HTTP_HOST'])) {
  $config['seckit.settings']['seckit_ssl']['hsts'] = TRUE;
  $config['seckit.settings']['seckit_ssl']['hsts_max_age'] = 31536000;
  $config['seckit.settings']['seckit_ssl']['hsts_subdomains'] = FALSE;
}
else {
  $config['seckit.settings']['seckit_ssl']['hsts'] = FALSE;
  $config['seckit.settings']['seckit_ssl']['hsts_max_age'] = 0;
  $config['seckit.settings']['seckit_ssl']['hsts_subdomains'] = FALSE;
}

// Lagoon Database connection
if (getenv('LAGOON')) {
  $databases['default']['default'] = [
    'driver' => 'mysql',
    'database' => getenv('MARIADB_DATABASE') ?: 'drupal',
    'username' => getenv('MARIADB_USERNAME') ?: 'drupal',
    'password' => getenv('MARIADB_PASSWORD') ?: 'drupal',
    'host' => getenv('MARIADB_HOST') ?: 'mariadb',
    'port' => 3306,
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_general_ci',
  ];
}

// Lagoon Solr connection
if (getenv('LAGOON')) {
  $config['search_api.server']['backend_config']['connector_config']['host'] = getenv('SOLR_HOST') ?: 'solr';
  $config['search_api.server']['backend_config']['connector_config']['path'] = '/solr/' . getenv('SOLR_CORE') ?: 'drupal';
}

// Lagoon Varnish & reverse proxy settings
if (getenv('LAGOON')) {
  $varnish_control_port = getenv('VARNISH_CONTROL_PORT') ?: '6082';
  $varnish_hosts = explode(',', getenv('VARNISH_HOSTS') ?: 'varnish');
  array_walk($varnish_hosts, function (&$value, $key) use ($varnish_control_port) {
    $value .= ":$varnish_control_port";
  });

  $settings['reverse_proxy'] = TRUE;
  $settings['reverse_proxy_addresses'] = array_merge(explode(',', getenv('VARNISH_HOSTS')), ['varnish']);
  $settings['varnish_control_terminal'] = implode($varnish_hosts, " ");
  $settings['varnish_control_key'] = getenv('VARNISH_SECRET') ?: 'lagoon_default_secret';
  $settings['varnish_version'] = 4;
}

// Redis configuration.
if (getenv('LAGOON') && (getenv('ENABLE_REDIS'))) {
  $settings['redis.connection']['interface'] = 'PhpRedis';
  $settings['redis.connection']['host'] = getenv('REDIS_HOST') ?: 'redis';
  $settings['redis.connection']['port'] = 6379;

  $settings['cache_prefix']['default'] = getenv('LAGOON_PROJECT') . '_' . getenv('LAGOON_GIT_SAFE_BRANCH');

  # Do not set the cache during installations of Drupal
  if (!drupal_installation_attempted()) {
    $settings['cache']['default'] = 'cache.backend.redis';

    // Include the default example.services.yml from the module, which will
    // replace all supported backend services (that currently includes the cache tags
    // checksum service and the lock backends, check the file for the current list)
    $settings['container_yamls'][] = 'modules/contrib/redis/example.services.yml';

    // Allow the services to work before the Redis module itself is enabled.
    $settings['container_yamls'][] = 'modules/contrib/redis/redis.services.yml';

    // Manually add the classloader path, this is required for the container cache bin definition below
    // and allows to use it without the redis module being enabled.
    $class_loader->addPsr4('Drupal\\redis\\', 'modules/contrib/redis/src');

    // Use redis for container cache.
    // The container cache is used to load the container definition itself, and
    // thus any configuration stored in the container itself is not available
    // yet. These lines force the container cache to use Redis rather than the
    // default SQL cache.
    $settings['bootstrap_container_definition'] = [
      'parameters' => [],
      'services' => [
        'redis.factory' => [
          'class' => 'Drupal\redis\ClientFactory',
        ],
        'cache.backend.redis' => [
          'class' => 'Drupal\redis\Cache\CacheBackendFactory',
          'arguments' => ['@redis.factory', '@cache_tags_provider.container', '@serialization.phpserialize'],
        ],
        'cache.container' => [
          'class' => '\Drupal\redis\Cache\PhpRedis',
          'factory' => ['@cache.backend.redis', 'get'],
          'arguments' => ['container'],
        ],
        'cache_tags_provider.container' => [
          'class' => 'Drupal\redis\Cache\RedisCacheTagsChecksum',
          'arguments' => ['@redis.factory'],
        ],
        'serialization.phpserialize' => [
          'class' => 'Drupal\Component\Serialization\PhpSerialize',
        ],
      ],
    ];
  }
}

// Public, private and temporary files paths.
if (getenv('LAGOON')) {
  $settings['file_public_path'] = 'sites/default/files';
  $settings['file_private_path'] = 'sites/default/files/private';
  $config['system.file']['path']['temporary'] = 'sites/default/files/private/tmp';
}

// ClamAV settings.
$config['clamav.settings']['scan_mode'] = 1;
$config['clamav.settings']['mode_executable']['executable_path'] = '/usr/bin/clamscan';

// Hash Salt
if (getenv('LAGOON')) {
  $settings['hash_salt'] = hash('sha256', getenv('LAGOON_PROJECT'));
}

// Loading settings for all environment types.
if (file_exists(__DIR__ . '/all.settings.php')) {
  include __DIR__ . '/all.settings.php';
}

// Environment specific settings files.
if (getenv('LAGOON_ENVIRONMENT_TYPE')) {
  if (file_exists(__DIR__ . '/' . getenv('LAGOON_ENVIRONMENT_TYPE') . '.settings.php')) {
    include __DIR__ . '/' . getenv('LAGOON_ENVIRONMENT_TYPE') . '.settings.php';
  }
}

// Configuration path settings.
$config_directories[CONFIG_SYNC_DIRECTORY] = '/app/config/default';
$config_directories['dev'] = '/app/config/dev';

// Last: this servers specific settings files.
if (file_exists(__DIR__ . '/settings.local.php')) {
  include __DIR__ . '/settings.local.php';
}

// Stage file proxy URL from production URL.
if (getenv('LAGOON_ENVIRONMENT_TYPE') != 'production') {

  if (getenv('LAGOON_PROJECT')) {
    $origin = 'https://nginx-' . getenv('LAGOON_PROJECT') . '-master.govcms.amazee.io';
    $config['stage_file_proxy.settings']['origin'] = $origin;
  }

  if (getenv('STAGE_FILE_PROXY_URL')) {
    $config['stage_file_proxy.settings']['origin'] = getenv('STAGE_FILE_PROXY_URL');
  }

  if (getenv('DEV_MODE')) {
    if (!drupal_installation_attempted()) {
      if (file_exists(__DIR__ . '/development.settings.php')) {
        include __DIR__ . '/development.settings.php';
      }
    }
  }

}
