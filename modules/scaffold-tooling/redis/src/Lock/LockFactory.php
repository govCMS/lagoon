<?php

namespace Drupal\redis\Lock;

use Drupal\redis\ClientFactory;

/**
 * Lock backend singleton handling.
 */
class LockFactory {

  /**
   * @var \Drupal\redis\ClientInterface
   */
  protected $clientFactory;

  /**
   * Creates a redis LockFactory.
   */
  public function __construct(ClientFactory $client_factory) {
    $this->clientFactory = $client_factory;
  }

  /**
   * Get actual lock backend.
   *
   * @param bool $persistent
   *   (optional) Whether to return a persistent lock implementation or not.
   *
   * @return \Drupal\Core\Lock\LockBackendInterface
   *   Return lock backend instance.
   */
  public function get($persistent = FALSE) {
    $class_name = $this->clientFactory->getClass($persistent ? ClientFactory::REDIS_IMPL_PERSISTENT_LOCK : ClientFactory::REDIS_IMPL_LOCK);
    return new $class_name($this->clientFactory);
  }
}
