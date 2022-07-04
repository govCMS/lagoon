<?php

namespace Drupal\redis\Lock;

use Drupal\Core\Lock\LockBackendAbstract;
use Drupal\redis\ClientFactory;
use Drupal\redis\RedisPrefixTrait;

/**
 * Predis lock backend implementation.
 */
class PhpRedis extends LockBackendAbstract {

  use RedisPrefixTrait;

  /**
   * @var \Redis
   */
  protected $client;

  /**
   * Creates a PHpRedis cache backend.
   */
  public function __construct(ClientFactory $factory) {
    $this->client = $factory->getClient();
    // __destruct() is causing problems with garbage collections, register a
    // shutdown function instead.
    drupal_register_shutdown_function([$this, 'releaseAll']);
  }

  /**
   * Generate a redis key name for the current lock name.
   *
   * @param string $name
   *   Lock name.
   *
   * @return string
   *   The redis key for the given lock.
   */
  protected function getKey($name) {
    return $this->getPrefix() . ':lock:' . $name;
  }

  /**
   * {@inheritdoc}
   */
  public function acquire($name, $timeout = 30.0) {
    $key    = $this->getKey($name);
    $id     = $this->getLockId();

    // Insure that the timeout is at least 1 ms.
    $timeout = max($timeout, 0.001);

    // If we already have the lock, check for his owner and attempt a new EXPIRE
    // command on it.
    if (isset($this->locks[$name])) {

      // Create a new transaction, for atomicity.
      $this->client->watch($key);

      // Global tells us we are the owner, but in real life it could have expired
      // and another process could have taken it, check that.
      if ($this->client->get($key) != $id) {
        // Explicit UNWATCH we are not going to run the MULTI/EXEC block.
        $this->client->unwatch();
        unset($this->locks[$name]);
        return FALSE;
      }

      $result = $this->client->multi()
        ->psetex($key, (int) ($timeout * 1000), $id)
        ->exec();

      // If the set failed, someone else wrote the key, we failed to acquire
      // the lock.
      if (FALSE === $result) {
        unset($this->locks[$name]);
        // Explicit transaction release which also frees the WATCH'ed key.
        $this->client->discard();
        return FALSE;
      }

      return ($this->locks[$name] = TRUE);
    }
    else {
      // Use a SET with microsecond expiration and the NX flag, which will only
      // succeed if the key does not exist yet.
      $result = $this->client->set($key, $id, ['nx', 'px' => (int) ($timeout * 1000)]);

      // If the result is FALSE, we failed to acquire the lock.
      if (FALSE === $result) {
        return FALSE;
      }

      // Register the lock.
      return ($this->locks[$name] = TRUE);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function lockMayBeAvailable($name) {
    $key    = $this->getKey($name);
    $value = $this->client->get($key);

    return $value === FALSE || $value === NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function release($name) {
    $key    = $this->getKey($name);
    $id     = $this->getLockId();

    unset($this->locks[$name]);

    // Ensure the lock deletion is an atomic transaction. If another thread
    // manages to removes all lock, we can not alter it anymore else we will
    // release the lock for the other thread and cause race conditions.
    $this->client->watch($key);

    if ($this->client->get($key) == $id) {
      $this->client->multi();
      $this->client->del($key);
      $this->client->exec();
    }
    else {
      $this->client->unwatch();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function releaseAll($lock_id = NULL) {
    // We can afford to deal with a slow algorithm here, this should not happen
    // on normal run because we should have removed manually all our locks.
    foreach ($this->locks as $name => $foo) {
      $this->release($name);
    }
  }
}
