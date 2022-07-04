<?php

namespace Drupal\redis\Queue;

use Drupal\Core\Queue\QueueInterface;

/**
 * Redis queue implementation.
 *
 * @ingroup queue
 */
abstract class QueueBase implements QueueInterface {

  /**
   * Prefix used with all keys.
   */
  const KEY_PREFIX = 'drupal:queue:';

  /**
   * The name of the queue this instance is working with.
   *
   * @var string
   */
  protected $name;

  /**
   * Key for list of available items.
   *
   * @var string
   */
  protected $availableListKey;

  /**
   * Key for list of claimed items.
   *
   * @var string
   */
  protected $claimedListKey;

  /**
   * Key prefix for items that are used to track expiration of leased items.
   *
   * @var string
   */
  protected $leasedKeyPrefix;

  /**
   * Key of increment counter key.
   *
   * @var string
   */
  protected $incrementCounterKey;

  /**
   * Key for hash table of available queue items.
   *
   * @var string
   */
  protected $availableItems;

  /**
   * Reserve timeout for blocking item claim.
   *
   * This will be set to number of seconds to wait for an item to be claimed.
   * Non-blocking approach will be used when set to NULL.
   *
   * @var int|null
   */
  protected $reserveTimeout;

  /**
   * Constructs a \Drupal\Core\Queue\DatabaseQueue object.
   *
   * @param string $name
   *   The name of the queue.
   * @param array $settings
   *   Array of Redis-related settings for this queue.
   */
  public function __construct($name, array $settings) {
    $this->name = $name;
    $this->reserveTimeout = $settings['reserve_timeout'];
    $this->availableListKey = static::KEY_PREFIX . $name . ':avail';
    $this->availableItems = static::KEY_PREFIX . $name . ':items';
    $this->claimedListKey = static::KEY_PREFIX . $name . ':claimed';
    $this->leasedKeyPrefix = static::KEY_PREFIX . $name . ':lease:';
    $this->incrementCounterKey = static::KEY_PREFIX . $name . ':counter';
  }

  /**
   * {@inheritdoc}
   */
  public function createQueue() {
    // Nothing to do here.
  }

}
