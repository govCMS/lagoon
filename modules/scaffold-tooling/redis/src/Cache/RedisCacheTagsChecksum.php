<?php

namespace Drupal\redis\Cache;

use Drupal\Core\Cache\CacheTagsChecksumInterface;
use Drupal\Core\Cache\CacheTagsChecksumTrait;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\redis\ClientFactory;
use Drupal\redis\RedisPrefixTrait;

/**
 * Cache tags invalidations checksum implementation that uses redis.
 */
class RedisCacheTagsChecksum implements CacheTagsChecksumInterface, CacheTagsInvalidatorInterface {

  use RedisPrefixTrait;
  use CacheTagsChecksumTrait;

  /**
   * Contains already loaded cache invalidations from the database.
   *
   * @var array
   */
  protected $tagCache = [];

  /**
   * A list of tags that have already been invalidated in this request.
   *
   * Used to prevent the invalidation of the same cache tag multiple times.
   *
   * @var array
   */
  protected $invalidatedTags = [];

  /**
   * {@inheritdoc}
   */
  protected $client;

  /**
   * @var string
   */
  protected $clientType;

  /**
   * Creates a PHpRedis cache backend.
   */
  public function __construct(ClientFactory $factory) {
    $this->client = $factory->getClient();
    $this->clientType = $factory->getClientName();
  }

  /**
   * {@inheritdoc}
   */
  public function doInvalidateTags(array $tags) {
    $keys = array_map([$this, 'getTagKey'], $tags);

    // We want to differentiate between PhpRedis and Redis clients.
    if ($this->clientType === 'PhpRedis') {
      $multi = $this->client->multi();
      foreach ($keys as $key) {
        $multi->incr($key);
      }
      $multi->exec();
    }
    elseif ($this->clientType === 'Predis') {

      $pipe = $this->client->pipeline();
      foreach ($keys as $key) {
        $pipe->incr($key);
      }
      $pipe->execute();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getTagInvalidationCounts(array $tags) {
    $keys = array_map([$this, 'getTagKey'], $tags);
    // The mget command returns the values as an array with numeric keys,
    // combine it with the tags array to get the expected return value and run
    // it through intval() to convert to integers and FALSE to 0.
    $values = $this->client->mget($keys);
    return $values ? array_map('intval', array_combine($tags, $values)) : [];
  }

  /**
   * Return the key for the given cache tag.
   *
   * @param string $tag
   *   The cache tag.
   *
   * @return string
   *   The prefixed cache tag.
   */
  protected function getTagKey($tag) {
    return $this->getPrefix() . ':cachetags:' . $tag;
  }

  /**
   * {@inheritdoc}
   */
  protected function getDatabaseConnection() {
    // This is not injected to avoid a dependency on the database in the
    // critical path. It is only needed during cache tag invalidations.
    return \Drupal::database();
  }

}
