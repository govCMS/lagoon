<?php

namespace Drupal\redis\Queue;

use Drupal\redis\ClientFactory;

/**
 * Defines the queue factory for the Redis backend.
 */
class ReliableQueueRedisFactory extends QueueRedisFactory {

  /**
   * Queue implementation class namespace prefix.
   */
  const CLASS_NAMESPACE = ClientFactory::REDIS_IMPL_RELIABLE_QUEUE;

}
