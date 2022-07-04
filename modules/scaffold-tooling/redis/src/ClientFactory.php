<?php

namespace Drupal\redis;
use Drupal\Core\Site\Settings;

/**
 * Common code and client singleton, for all Redis clients.
 */
class ClientFactory {
  /**
   * Redis default host.
   */
  const REDIS_DEFAULT_HOST = "127.0.0.1";

  /**
   * Redis default port.
   */
  const REDIS_DEFAULT_PORT = 6379;

  /**
   * Redis default database: will select none (Database 0).
   */
  const REDIS_DEFAULT_BASE = NULL;

  /**
   * Redis default password: will not authenticate.
   */
  const REDIS_DEFAULT_PASSWORD = NULL;

  /**
   * Cache implementation namespace.
   */
  const REDIS_IMPL_CACHE = '\\Drupal\\redis\\Cache\\';

  /**
   * Lock implementation namespace.
   */
  const REDIS_IMPL_LOCK = '\\Drupal\\redis\\Lock\\';

  /**
   * Lock implementation namespace.
   */
  const REDIS_IMPL_FLOOD = '\\Drupal\\redis\\Flood\\';

  /**
   * Persistent Lock implementation namespace.
   */
  const REDIS_IMPL_PERSISTENT_LOCK = '\\Drupal\\redis\\PersistentLock\\';

  /**
   * Client implementation namespace.
   */
  const REDIS_IMPL_CLIENT = '\\Drupal\\redis\\Client\\';

  /**
   * Queue implementation namespace.
   */
  const REDIS_IMPL_QUEUE = '\\Drupal\\redis\\Queue\\';

  /**
   * Reliable queue implementation namespace.
   */
  const REDIS_IMPL_RELIABLE_QUEUE = '\\Drupal\\redis\\Queue\\Reliable';

  /**
   * @var \Drupal\redis\ClientInterface
   */
  protected static $_clientInterface;

  /**
   * @var mixed
   */
  protected static $_client;

  public static function hasClient() {
    return isset(self::$_client);
  }

  /**
   * Set client proxy.
   */
  public static function setClient(ClientInterface $interface) {
    if (isset(self::$_client)) {
      throw new \Exception("Once Redis client is connected, you cannot change client proxy instance.");
    }

    self::$_clientInterface = $interface;
  }

  /**
   * Lazy instantiates client proxy depending on the actual configuration.
   *
   * If you are using a lock or cache backend using one of the Redis client
   * implementations, this will be overridden at early bootstrap phase and
   * configuration will be ignored.
   *
   * @return ClientInterface
   */
  public static function getClientInterface()
  {
    if (!isset(self::$_clientInterface))
    {
      $settings = Settings::get('redis.connection', []);
      if (!empty($settings['interface']))
      {
        $className = self::getClass(self::REDIS_IMPL_CLIENT, $settings['interface']);
        self::$_clientInterface = new $className();
      }
      elseif (class_exists('Predis\Client'))
      {
        // Transparent and arbitrary preference for Predis library.
        $className = self::getClass(self::REDIS_IMPL_CLIENT, 'Predis');
        self::$_clientInterface = new $className();
      }
      elseif (class_exists('Redis'))
      {
        // Fallback on PhpRedis if available.
        $className = self::getClass(self::REDIS_IMPL_CLIENT, 'PhpRedis');
        self::$_clientInterface = new $className();
      }
      else
      {
        if (!isset(self::$_clientInterface))
        {
          throw new \Exception("No client interface set.");
        }
      }
    }

    return self::$_clientInterface;
  }

  /**
   * Get underlying library name.
   *
   * @return string
   */
  public static function getClientName() {
    return self::getClientInterface()->getName();
  }

  /**
   * Get client singleton.
   */
  public static function getClient() {
    if (!isset(self::$_client)) {
      $settings = Settings::get('redis.connection', []);
      $settings += [
        'host' => self::REDIS_DEFAULT_HOST,
        'port' => self::REDIS_DEFAULT_PORT,
        'base' => self::REDIS_DEFAULT_BASE,
        'password' => self::REDIS_DEFAULT_PASSWORD,
        'persistent' => FALSE,
      ];

      // If using replication, lets create the client appropriately.
      if (isset($settings['replication']) && $settings['replication'] === TRUE) {
        foreach ($settings['replication.host'] as $key => $replicationHost) {
          if (!isset($replicationHost['port'])) {
            $settings['replication.host'][$key]['port'] = self::REDIS_DEFAULT_PORT;
          }
        }

        self::$_client = self::getClientInterface()->getClient(
          $settings['host'],
          $settings['port'],
          $settings['base'],
          $settings['password'],
          $settings['replication.host'],
          $settings['persistent']);
      }
      else {
        self::$_client = self::getClientInterface()->getClient(
          $settings['host'],
          $settings['port'],
          $settings['base'],
          $settings['password'],
          [], // There are no replication hosts.
          $settings['persistent']);
      }
    }

    return self::$_client;
  }

  /**
   * Get specific class implementing the current client usage for the specific
   * asked core subsystem.
   *
   * @param string $system
   *   One of the ClientFactory::IMPL_* constant.
   * @param string $clientName
   *   Client name, if fixed.
   *
   * @return string
   *   Class name, if found.
   *
   * @throws \Exception
   *   If not found.
   */
  public static function getClass($system, $clientName = NULL) {
    $className = $system . ($clientName ?: self::getClientName());

    if (!class_exists($className)) {
      throw new \Exception($className . " does not exists");
    }

    return $className;
  }

  /**
   * For unit testing only reset internals.
   */
  static public function reset() {
    self::$_clientInterface = null;
    self::$_client = null;
  }
}

