<?php

namespace Drupal\redis\Cache;

use \DateInterval;
use Drupal\Component\Assertion\Inspector;
use Drupal\Component\Serialization\SerializationInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Site\Settings;
use Drupal\redis\RedisPrefixTrait;

/**
 * Base class for redis cache backends.
 *
 *  *
 *
 */
abstract class CacheBase implements CacheBackendInterface {

  use RedisPrefixTrait;

  /**
   * Temporary cache items lifetime is infinite.
   */
  const LIFETIME_INFINITE = 0;

  /**
   * Default lifetime for permanent items.
   * Approximatively 1 year.
   */
  const LIFETIME_PERM_DEFAULT = 31536000;

  /**
   * Computed keys are let's say around 60 characters length due to
   * key prefixing, which makes 1,000 keys DEL command to be something
   * around 50,000 bytes length: this is huge and may not pass into
   * Redis, let's split this off.
   * Some recommend to never get higher than 1,500 bytes within the same
   * command which makes us forced to split this at a very low threshold:
   * 20 seems a safe value here (1,280 average length).
   */
  const KEY_THRESHOLD = 20;

  /**
   * Latest delete all flush KEY name.
   */
  const LAST_DELETE_ALL_KEY = '_redis_last_delete_all';

  /**
   * @var string
   */
  protected $bin;

  /**
   * The serialization class to use.
   *
   * @var \Drupal\Component\Serialization\SerializationInterface
   */
  protected $serializer;

  /**
   * Default TTL for CACHE_PERMANENT items.
   *
   * See "Default lifetime for permanent items" section of README.md
   * file for a comprehensive explanation of why this exists.
   *
   * @var int
   */
  protected $permTtl = self::LIFETIME_PERM_DEFAULT;

  /**
   * Minimal TTL to use.
   *
   * Note that this is for testing purposes. Do not specify the minimal TTL
   * outside of unit-tests.
   */
  protected $minTtl = 0;

  /**
   * @var \Drupal\redis\ClientInterface
   */
  protected $client;

  /**
   * The cache tags checksum provider.
   *
   * @var \Drupal\Core\Cache\CacheTagsChecksumInterface|\Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $checksumProvider;

  /**
   * The last delete timestamp.
   *
   * @var float
   */
  protected $lastDeleteAll = NULL;

  /**
   * Delayed deletions for deletions during a transaction.
   *
   * @var string[]
   */
  protected $delayedDeletions = [];

  /**
   * Get TTL for CACHE_PERMANENT items.
   *
   * @return int
   *   Lifetime in seconds.
   */
  public function getPermTtl() {
    return $this->permTtl;
  }

  /**
   * CacheBase constructor.
   * @param $bin
   *   The cache bin for which the object is created.
   * @param \Drupal\Component\Serialization\SerializationInterface $serializer
   *   The serialization class to use.
   */
  public function __construct($bin, SerializationInterface $serializer) {
    $this->bin = $bin;
    $this->serializer = $serializer;
    $this->setPermTtl();
  }

  /**
   * {@inheritdoc}
   */
  public function get($cid, $allow_invalid = FALSE) {
    $cids = [$cid];
    $cache = $this->getMultiple($cids, $allow_invalid);
    return reset($cache);
  }

  /**
   * {@inheritdoc}
   */
  public function setMultiple(array $items) {
    foreach ($items as $cid => $item) {
      $this->set($cid, $item['data'], isset($item['expire']) ? $item['expire'] : CacheBackendInterface::CACHE_PERMANENT, isset($item['tags']) ? $item['tags'] : []);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function delete($cid) {
    $this->deleteMultiple([$cid]);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteMultiple(array $cids) {
    $in_transaction = \Drupal::database()->inTransaction();
    if ($in_transaction) {
      if (empty($this->delayedDeletions)) {
        \Drupal::database()->addRootTransactionEndCallback([$this, 'postRootTransactionCommit']);
      }
      $this->delayedDeletions = array_unique(array_merge($this->delayedDeletions, $cids));
    }
    else {
      $this->doDeleteMultiple($cids);
    }
  }

  /**
   * Execute the deletion.
   *
   * This can be delayed to avoid race conditions.
   *
   * @param array $cids
   *   An array of cache IDs to delete.
   *
   * @see static::deleteMultiple()
   */
  protected abstract function doDeleteMultiple(array $cids);

  /**
   * Callback to be invoked after a database transaction gets committed.
   *
   * Invalidates all delayed cache deletions.
   *
   * @param bool $success
   *   Whether or not the transaction was successful.
   */
  public function postRootTransactionCommit($success) {
    if ($success) {
      $this->doDeleteMultiple($this->delayedDeletions);
    }
    $this->delayedDeletions = [];
  }

  /**
   * {@inheritdoc}
   */
  public function removeBin() {
    $this->deleteAll();
  }

  /**
   * {@inheritdoc}
   */
  public function invalidate($cid) {
    $this->invalidateMultiple([$cid]);
  }

  /**
   * Return the key for the given cache key.
   */
  public function getKey($cid = NULL) {
    if (NULL === $cid) {
      return $this->getPrefix() . ':' . $this->bin;
    }
    else {
      return $this->getPrefix() . ':' . $this->bin . ':' . $cid;
    }
  }

  /**
   * Calculate the correct expiration time.
   *
   * @param int $expire
   *   The expiration time provided for the cache set.
   *
   * @return int
   *   The default expiration if expire is PERMANENT or higher than the default.
   *   May return negative values if the item is already expired.
   */
  protected function getExpiration($expire) {
    if ($expire == Cache::PERMANENT || $expire > $this->permTtl) {
      return $this->permTtl;
    }
    return $expire - \Drupal::time()->getRequestTime();
  }
  /**
   * Return the key for the tag used to specify the bin of cache-entries.
   */
  protected function getTagForBin() {
    return 'x-redis-bin:' . $this->bin;
  }

  /**
   * Set the minimum TTL (unit testing only).
   */
  public function setMinTtl($ttl) {
    $this->minTtl = $ttl;
  }

  /**
   * Set the permanent TTL.
   */
  public function setPermTtl($ttl = NULL) {
    if (isset($ttl)) {
      $this->permTtl = $ttl;
    }
    else {
      // Attempt to set from settings.
      if (($settings = Settings::get('redis.settings', [])) && isset($settings['perm_ttl_' . $this->bin])) {
        $ttl = $settings['perm_ttl_' . $this->bin];
        if ($ttl === (int) $ttl) {
          $this->permTtl = $ttl;
        }
        else {
          if ($iv = DateInterval::createFromDateString($ttl)) {
            // http://stackoverflow.com/questions/14277611/convert-dateinterval-object-to-seconds-in-php
            $this->permTtl = ($iv->y * 31536000 + $iv->m * 2592000 + $iv->days * 86400 + $iv->h * 3600 + $iv->i * 60 + $iv->s);
          }
          else {
            // Log error about invalid ttl.
            trigger_error(sprintf("Parsed TTL '%s' has an invalid value: switching to default", $ttl));
            $this->permTtl = self::LIFETIME_PERM_DEFAULT;
          }

        }
      }
    }
  }

  /**
   * Prepares a cached item.
   *
   * Checks that items are either permanent or did not expire, and unserializes
   * data as appropriate.
   *
   * @param array $values
   *   The hash returned from redis or false.
   * @param bool $allow_invalid
   *   If FALSE, the method returns FALSE if the cache item is not valid.
   *
   * @return mixed|false
   *   The item with data unserialized as appropriate and a property indicating
   *   whether the item is valid, or FALSE if there is no valid item to load.
   */
  protected function expandEntry(array $values, $allow_invalid) {
    // Check for entry being valid.
    if (empty($values['cid'])) {
      return FALSE;
    }

    // Ignore items that are scheduled for deletion.
    if (in_array($values['cid'], $this->delayedDeletions)) {
      return FALSE;
    }

    $cache = (object) $values;

    $cache->tags = explode(' ', $cache->tags);

    // Check expire time, allow to have a cache invalidated explicitly, don't
    // check if already invalid.
    if ($cache->valid) {
      $cache->valid = $cache->expire == Cache::PERMANENT || $cache->expire >= \Drupal::time()->getRequestTime();

      // Check if invalidateTags() has been called with any of the items's tags.
      if ($cache->valid && !$this->checksumProvider->isValid($cache->checksum, $cache->tags)) {
        $cache->valid = FALSE;
      }
    }

    // Ensure the entry does not predate the last delete all time.
    $last_delete_timestamp = $this->getLastDeleteAll();
    if ($last_delete_timestamp && ((float)$values['created']) < $last_delete_timestamp) {
      return FALSE;
    }

    if (!$allow_invalid && !$cache->valid) {
      return FALSE;
    }

    if (!empty($cache->gz)) {
      // Uncompress, suppress warnings e.g. for broken CRC32.
      $cache->data = @gzuncompress($cache->data);
      // In such cases, void the cache entry.
      if ($cache->data === FALSE) {
        return FALSE;
      }
    }

    if ($cache->serialized) {
      $cache->data = $this->serializer->decode($cache->data);
    }

    return $cache;
  }

  /**
   * Create cache entry.
   *
   * @param string $cid
   * @param mixed $data
   * @param int $expire
   * @param string[] $tags
   *
   * @return array
   */
  protected function createEntryHash($cid, $data, $expire, array $tags) {
    // Always add a cache tag for the current bin, so that we can use that for
    // invalidateAll().
    $tags[] = $this->getTagForBin();
    assert(Inspector::assertAllStrings($tags), 'Cache Tags must be strings.');
    $hash = [
      'cid' => $cid,
      'created' => round(microtime(TRUE), 3),
      'expire' => $expire,
      'tags' => implode(' ', $tags),
      'valid' => 1,
      'checksum' => $this->checksumProvider->getCurrentChecksum($tags),
    ];

    // Let Redis handle the data types itself.
    if (!is_string($data)) {
      $hash['data'] = $this->serializer->encode($data);
      $hash['serialized'] = 1;
    }
    else {
      $hash['data'] = $data;
      $hash['serialized'] = 0;
    }

    if (Settings::get('redis_compress_length', 0) && strlen($hash['data']) > Settings::get('redis_compress_length', 0)) {
      $hash['data'] = @gzcompress($hash['data'], Settings::get('redis_compress_level', 1));
      $hash['gz'] = TRUE;
    }

    return $hash;
  }
  /**
   * {@inheritdoc}
   */
  public function invalidateMultiple(array $cids) {
    // Loop over all cache items, they are stored as a hash, so we can access
    // the valid flag directly, only write if it exists and is not 0.
    foreach ($cids as $cid) {
      $key = $this->getKey($cid);
      if ($this->client->hGet($key, 'valid')) {
        $this->client->hSet($key, 'valid', 0);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function invalidateAll() {
    // To invalidate the whole bin, we invalidate a special tag for this bin.
    $this->checksumProvider->invalidateTags([$this->getTagForBin()]);
  }

  /**
   * {@inheritdoc}
   */
  public function garbageCollection() {
    // @todo Do we need to do anything here?
  }

  /**
   *  Returns the last delete all timestamp.
   *
   * @return float
   *   The last delete timestamp as a timestamp with a millisecond precision.
   */
  protected function getLastDeleteAll() {
    // Cache the last delete all timestamp.
    if ($this->lastDeleteAll === NULL) {
      $this->lastDeleteAll = (float) $this->client->get($this->getKey(static::LAST_DELETE_ALL_KEY));
    }
    return $this->lastDeleteAll;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAll() {
    // The last delete timestamp is in milliseconds, ensure that no cache
    // was written in the same millisecond.
    // @todo This is needed to make the tests pass, is this safe enough for real
    //   usage?
    usleep(1000);
    $this->lastDeleteAll = round(microtime(TRUE), 3);
    $this->client->set($this->getKey(static::LAST_DELETE_ALL_KEY), $this->lastDeleteAll);
  }

}
