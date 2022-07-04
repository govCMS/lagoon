<?php

namespace Drupal\redis\Cache;

use Drupal\Component\Serialization\SerializationInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheTagsChecksumInterface;

/**
 * Predis cache backend.
 */
class Predis extends CacheBase {

  /**
   * @var \Predis\Client
   */
  protected $client;

  /**
   * Creates a Predis cache backend.
   *
   * @param $bin
   *   The cache bin for which the object is created.
   * @param \Redis $client
   * @param \Drupal\Core\Cache\CacheTagsChecksumInterface $checksum_provider
   * @param \Drupal\redis\Cache\SerializationInterface $serializer
   *   The serialization class to use.
   */
  public function __construct($bin, \Predis\Client $client, CacheTagsChecksumInterface $checksum_provider, SerializationInterface $serializer) {
    parent::__construct($bin, $serializer);
    $this->client = $client;
    $this->checksumProvider = $checksum_provider;
  }

  /**
   * {@inheritdoc}
   */
  public function getMultiple(&$cids, $allow_invalid = FALSE) {
    // Avoid an error when there are no cache ids.
    if (empty($cids)) {
      return [];
    }

    $return = [];

    // Build the list of keys to fetch.
    $keys = array_map([$this, 'getKey'], $cids);

    // Optimize for the common case when only a single cache entry needs to
    // be fetched, no pipeline is needed then.
    if (count($keys) > 1) {
      $pipe = $this->client->pipeline();
      foreach ($keys as $key) {
        $pipe->hgetall($key);
      }
      $result = $pipe->execute();
    }
    else {
      $result = [$this->client->hGetAll(reset($keys))];
    }

    // Loop over the cid values to ensure numeric indexes.
    foreach (array_values($cids) as $index => $key) {
      // Check if a valid result was returned from Redis.
      if (isset($result[$index]) && is_array($result[$index])) {
        // Check expiration and invalidation and convert into an object.
        $item = $this->expandEntry($result[$index], $allow_invalid);
        if ($item) {
          $return[$item->cid] = $item;
        }
      }
    }

    // Remove fetched cids from the list.
    $cids = array_diff($cids, array_keys($return));

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function set($cid, $data, $expire = Cache::PERMANENT, array $tags = []) {

    $ttl = $this->getExpiration($expire);

    $key = $this->getKey($cid);

    // If the item is already expired, delete it.
    if ($ttl <= 0) {
      $this->delete($key);
    }

    // Build the cache item and save it as a hash array.
    $entry = $this->createEntryHash($cid, $data, $expire, $tags);
    $pipe = $this->client->pipeline();
    $pipe->hmset($key, $entry);
    $pipe->expire($key, $ttl);
    $pipe->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function doDeleteMultiple(array $cids) {
    if (!empty($cids)) {
      $keys = array_map([$this, 'getKey'], $cids);
      $this->client->del($keys);
    }
  }

}
