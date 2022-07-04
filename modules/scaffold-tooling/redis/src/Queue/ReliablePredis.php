<?php

namespace Drupal\redis\Queue;

/**
 * Redis queue implementation using Predis library backend.
 *
 * @ingroup queue
 */
class ReliablePredis extends ReliableQueueBase {

  /**
   * The Redis connection.
   *
   * @var \Predis\Client $client
   */
  protected $client;

  /**
   * Constructs a \Drupal\redis\Queue\Predis object.
   *
   * @param string $name
   *   The name of the queue.
   * @param array $settings
   *   Array of Redis-related settings for this queue.
   * @param \Predis\Client $client
   *   The Predis client.
   */
  public function __construct($name, array $settings, \Predis\Client $client) {
    parent::__construct($name, $settings);
    $this->client = $client;
  }

  /**
   * {@inheritdoc}
   */
  public function createItem($data) {
    $record = new \stdClass();
    $record->data = $data;
    $record->qid = $this->incrementId();
    // We cannot rely on REQUEST_TIME because many items might be created
    // by a single request which takes longer than 1 second.
    $record->timestamp = time();

    $pipe = $this->client->pipeline();
    $pipe->hsetnx($this->availableItems, $record->qid, serialize($record));
    $pipe->lLen($this->availableListKey);
    $pipe->lpush($this->availableListKey, $record->qid);
    $result = $pipe->execute();

    $success = $result[0] && $result[2] > $result[1];

    return $success ? $record->qid : FALSE;
  }

  /**
   * Gets next serial ID for Redis queue items.
   *
   * @return int
   *   Next serial ID for Redis queue item.
   */
  protected function incrementId() {
    return $this->client->incr($this->incrementCounterKey);
  }

  /**
   * {@inheritdoc}
   */
  public function numberOfItems() {
    return $this->client->lLen($this->availableListKey) + $this->client->lLen($this->claimedListKey);
  }

  /**
   * {@inheritdoc}
   */
  public function claimItem($lease_time = 30) {
    // Is it OK to do garbage collection here (we need to loop list of claimed
    // items)?
    $this->garbageCollection();
    $item = FALSE;

    if ($this->reserveTimeout !== NULL) {
      // A blocking version of claimItem to be used with long-running queue workers.
      $qid = $this->client->brpoplpush($this->availableListKey, $this->claimedListKey, $this->reserveTimeout);
    }
    else {
      $qid = $this->client->rpoplpush($this->availableListKey, $this->claimedListKey);
    }

    if ($qid) {
      $job = $this->client->hget($this->availableItems, $qid);
      if ($job) {
        $item = unserialize($job);
        $this->client->setex($this->leasedKeyPrefix . $item->qid, $lease_time, '1');
      }
    }

    return $item;
  }

  /**
   * {@inheritdoc}
   */
  public function releaseItem($item) {
    $this->client->pipeline()
      ->lrem($this->claimedListKey, -1, $item->qid)
      ->lpush($this->availableListKey, $item->qid)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function deleteItem($item) {
    $this->client->pipeline()
      ->lrem($this->claimedListKey, -1, $item->qid)
      ->lrem($this->availableListKey, -1, $item->qid)
      ->hdel($this->availableItems, $item->qid)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function deleteQueue() {
    // TODO: Fixme
    $keys_to_remove = [
      $this->claimedListKey,
      $this->availableListKey,
      $this->availableItems,
      $this->incrementCounterKey
    ];

    foreach ($this->client->keys($this->leasedKeyPrefix . '*') as $key) {
      $keys_to_remove[] = $key;
    }

    $this->client->del($keys_to_remove);
  }

  /**
   * Automatically release items, that have been claimed and exceeded lease time.
   */
  protected function garbageCollection() {
    foreach ($this->client->lrange($this->claimedListKey, 0, -1) as $qid) {
      if (!$this->client->exists($this->leasedKeyPrefix . $qid)) {
        // The lease expired for this ID.
        $this->client->lrem($this->claimedListKey, -1, $qid);
        $this->client->lpush($this->availableListKey, $qid);
      }
    }
  }
}
