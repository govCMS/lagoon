<?php

namespace Drupal\Tests\redis\Traits;

use Drupal\Core\Site\Settings;

trait RedisTestInterfaceTrait {

  /**
 * Uses an env variable to set the redis client to use for this test.
 */
  public function setUpSettings() {

    // Write redis_interface settings manually.
    $redis_interface = self::getRedisInterfaceEnv();
    $settings = Settings::getAll();
    $settings['redis.connection']['interface'] = $redis_interface;
    new Settings($settings);
  }

  /**
   * Uses an env variable to set the redis client to use for this test.
   */
  public function getRedisInterfaceEnv() {

    // Get REDIS_INTERFACE from env variable.
    $redis_interface = getenv('REDIS_INTERFACE');

    // Default to PhpRedis is env variable not available.
    if ($redis_interface == FALSE) {
      $redis_interface = 'PhpRedis';
    }
    return $redis_interface;
  }

}
