<?php

namespace Drupal\Tests\redis\Kernel;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\KernelTests\Core\Cache\GenericCacheBackendUnitTestBase;
use Symfony\Component\DependencyInjection\Reference;
use Drupal\Tests\redis\Traits\RedisTestInterfaceTrait;

/**
 * Tests Redis cache backend using GenericCacheBackendUnitTestBase.
 *
 * @group redis
 */
class RedisCacheTest extends GenericCacheBackendUnitTestBase {

  use RedisTestInterfaceTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system', 'redis'];

  public function register(ContainerBuilder $container) {
    self::setUpSettings();
    parent::register($container);
    // Replace the default checksum service with the redis implementation.
    if ($container->has('redis.factory')) {
      $container->register('cache_tags.invalidator.checksum', 'Drupal\redis\Cache\RedisCacheTagsChecksum')
        ->addArgument(new Reference('redis.factory'))
        ->addTag('cache_tags_invalidator');
    }
  }

  /**
   * Creates a new instance of PhpRedis cache backend.
   *
   * @return \Drupal\redis\Cache\PhpRedis
   *   A new PhpRedis cache backend.
   */
  protected function createCacheBackend($bin) {
    $cache = \Drupal::service('cache.backend.redis')->get($bin);
    $cache->setMinTtl(10);
    return $cache;
  }

}
