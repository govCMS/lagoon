<?php

namespace Drupal\govcms_akamai_purge\Config;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Configuration override for the GovCMS Akamai Purge service.
 */
class ConfigOverrides implements ConfigFactoryOverrideInterface {

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = [];

    $purge_token = getenv('AKAMAI_PURGE_TOKEN');
    $lagoon_project = getenv('LAGOON_PROJECT');
    $lagoon_branch = getenv('LAGOON_GIT_SAFE_BRANCH');

    // Do not enable the purger if no token or project.
    if (!$purge_token || !$lagoon_project || !$lagoon_branch) {
      return $overrides;
    }

    if (in_array('purge.plugins', $names)) {
      $overrides['purge.plugins']['purgers'][] = [
        'order_index' => 3,
        'instance_id' => 'govcms',
        'plugin_id' => 'govcms_httpbundled',
      ];
    }

    if (in_array('purge_purger_http.settings.govcms', $names)) {
      $purge_service_hostname = getenv('AKAMAI_PURGE_SERVICE_HOSTNAME');
      if ($purge_service_hostname) {
        $overrides['purge_purger_http.settings.govcms']['hostname'] = $purge_service_hostname;
      }
      $purge_service_port = getenv('AKAMAI_PURGE_SERVICE_PORT');
      if ($purge_service_port) {
        $overrides['purge_purger_http.settings.govcms']['port'] = $purge_service_port;
      }
      $purge_service_scheme = getenv('AKAMAI_PURGE_SERVICE_SCHEME');
      if ($purge_service_port) {
        $overrides['purge_purger_http.settings.govcms']['scheme'] = $purge_service_scheme;
      }
    }

    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'ConfigGovcmsOverrider';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($name) {
    return new CacheableMetadata();
  }

  /**
   * {@inheritdoc}
   */
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION) {
    return NULL;
  }

}
