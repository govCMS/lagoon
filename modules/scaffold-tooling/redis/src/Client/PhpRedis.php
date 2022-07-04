<?php

namespace Drupal\redis\Client;

use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Site\Settings;
use Drupal\redis\ClientInterface;

/**
 * PhpRedis client specific implementation.
 */
class PhpRedis implements ClientInterface {

  /**
   * {@inheritdoc}
   */
  public function getClient($host = NULL, $port = NULL, $base = NULL, $password = NULL, $replicationHosts = [], $persistent = FALSE) {
    $client = new \Redis();

    // Sentinel mode, get the real master.
    if (is_array($host)) {
      $ip_host = $this->askForMaster($client, $host, $password);
      if (is_array($ip_host)) {
        list($host, $port) = $ip_host;
      }
    }

    if ($persistent) {
      $client->pconnect($host, $port);
    }
    else {
      $client->connect($host, $port);
    }

    if (isset($password)) {
      $client->auth($password);
    }

    if (isset($base)) {
      $client->select($base);
    }

    // Do not allow PhpRedis serialize itself data, we are going to do it
    // ourself. This will ensure less memory footprint on Redis size when
    // we will attempt to store small values.
    $client->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_NONE);

    return $client;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'PhpRedis';
  }

  /**
   * Connect to sentinels to get Redis master instance.
   *
   * Just asking one sentinels after another until given the master location.
   * More info about this mode at https://redis.io/topics/sentinel.
   *
   * @param \Redis $client
   *   The PhpRedis client.
   * @param array $sentinels
   *   An array of the sentinels' ip:port.
   * @param string $password
   *   An optional Sentinels' password.
   *
   * @return mixed
   *   An array with ip & port of the Master instance or NULL.
   */
  protected function askForMaster(\Redis $client, array $sentinels = [], $password = NULL) {

    $ip_port = NULL;
    $settings = Settings::get('redis.connection', []);
    $settings += ['instance' => NULL];

    if ($settings['instance']) {
      foreach ($sentinels as $sentinel) {
        list($host, $port) = explode(':', $sentinel);
        // Prevent fatal PHP errors when one of the sentinels is down.
        set_error_handler(function () {
          return TRUE;
        });
        // 0.5s timeout.
        $success = $client->connect($host, $port, 0.5);
        restore_error_handler();

        if (!$success) {
          continue;
        }

        if (isset($password)) {
          $client->auth($password);
        }

        if ($client->isConnected()) {
          $ip_port = $client->rawcommand('SENTINEL', 'get-master-addr-by-name', $settings['instance']);
          if ($ip_port) {
            break;
          }
        }
        $client->close();
      }
    }
    return $ip_port;
  }

}
