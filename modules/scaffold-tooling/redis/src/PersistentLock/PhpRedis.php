<?php

namespace Drupal\redis\PersistentLock;

use Drupal\redis\ClientFactory;

/**
 * PHpRedis persistent lock backend
 */
class PhpRedis extends \Drupal\redis\Lock\PhpRedis {

  /**
   * Creates a PHpRedis persistent lock backend.
   */
  public function __construct(ClientFactory $factory) {
    // Do not call the parent constructor to avoid registering a shutdown
    // function that releases all the locks at the end of a request.
    $this->client = $factory->getClient();
    // Set the lockId to a fixed string to make the lock ID the same across
    // multiple requests. The lock ID is used as a page token to relate all the
    // locks set during a request to each other.
    // @see \Drupal\Core\Lock\LockBackendInterface::getLockId()
    $this->lockId = 'persistent';
  }

}
